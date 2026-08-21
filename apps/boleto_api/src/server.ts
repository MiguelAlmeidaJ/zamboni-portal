import http from 'node:http';
import type { IncomingMessage, ServerResponse } from 'node:http';
import { appendFile, mkdir } from 'node:fs/promises';
import { config } from './config.js';
import { authenticate, closePool, customerPortal } from './database.js';
import { isValidCnpjShape, normalizeCnpj } from './formatters.js';
import {
  createSession,
  deleteSession,
  expiredSessionCookie,
  readSession,
  sessionCookie
} from './sessions.js';

const securityHeaders: Record<string, string> = {
  'Cross-Origin-Resource-Policy': 'same-site',
  'Permissions-Policy': 'camera=(), microphone=(), geolocation=()',
  'Referrer-Policy': 'no-referrer',
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'DENY'
};

interface LoginAttemptBucket {
  attempts: number;
  resetAt: number;
}

const loginAttempts = new Map<string, LoginAttemptBucket>();

class HttpError extends Error {
  constructor(public readonly status: number, message: string) {
    super(message);
  }
}

function normalizedOrigin(request: IncomingMessage): string | null {
  const origin = request.headers.origin;
  if (typeof origin !== 'string') return null;
  try {
    return new URL(origin).origin;
  } catch {
    return null;
  }
}

function isTrustedOrigin(request: IncomingMessage): boolean {
  const origin = normalizedOrigin(request);
  return origin !== null && config.frontendOrigins.includes(origin);
}

function corsHeaders(request: IncomingMessage): Record<string, string> {
  const origin = normalizedOrigin(request);
  const headers: Record<string, string> = { Vary: 'Origin' };
  if (origin && config.frontendOrigins.includes(origin)) {
    Object.assign(headers, {
      'Access-Control-Allow-Credentials': 'true',
      'Access-Control-Allow-Headers': 'Content-Type',
      'Access-Control-Allow-Methods': 'GET,POST,OPTIONS',
      'Access-Control-Allow-Origin': origin
    });
  }
  return headers;
}

function json(
  response: ServerResponse,
  request: IncomingMessage,
  status: number,
  body: unknown,
  headers: Record<string, string> = {}
): void {
  response.writeHead(status, {
    ...securityHeaders,
    ...corsHeaders(request),
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Security-Policy': "default-src 'none'; frame-ancestors 'none'",
    'Cache-Control': 'no-store',
    ...headers
  });
  response.end(JSON.stringify(body));
}

async function bodyAsJson(request: IncomingMessage): Promise<Record<string, unknown>> {
  const contentType = request.headers['content-type'] || '';
  if (!contentType.toLowerCase().startsWith('application/json')) {
    throw new HttpError(415, 'A requisição deve usar Content-Type application/json.');
  }

  const chunks: Buffer[] = [];
  let size = 0;
  for await (const chunk of request) {
    const buffer = Buffer.isBuffer(chunk) ? chunk : Buffer.from(chunk);
    size += buffer.length;
    if (size > 20_000) throw new HttpError(413, 'Corpo da requisição muito grande.');
    chunks.push(buffer);
  }

  try {
    const body = Buffer.concat(chunks).toString('utf8');
    return body ? JSON.parse(body) as Record<string, unknown> : {};
  } catch {
    throw new HttpError(400, 'JSON inválido.');
  }
}

function clientIp(request: IncomingMessage): string {
  if (config.trustProxy) {
    const forwarded = request.headers['x-forwarded-for'];
    const first = Array.isArray(forwarded) ? forwarded[0] : forwarded?.split(',')[0];
    if (first?.trim()) return first.trim();
  }
  return request.socket.remoteAddress || 'unknown';
}

function consumeLoginAttempt(request: IncomingMessage): { allowed: boolean; retryAfter: number; key: string } {
  const now = Date.now();
  const key = clientIp(request);
  const current = loginAttempts.get(key);
  const bucket = !current || current.resetAt <= now
    ? { attempts: 0, resetAt: now + config.loginRateLimit.windowSeconds * 1000 }
    : current;

  bucket.attempts += 1;
  loginAttempts.set(key, bucket);

  return {
    allowed: bucket.attempts <= config.loginRateLimit.maxAttempts,
    retryAfter: Math.max(1, Math.ceil((bucket.resetAt - now) / 1000)),
    key
  };
}

function maskedDocument(value: string): string {
  const normalized = normalizeCnpj(value);
  return normalized ? `***${normalized.slice(-4)}` : 'não informado';
}

async function logAccess(file: string, cnpj: string, detail: string): Promise<void> {
  if (!config.logDir) return;
  const now = new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Sao_Paulo', dateStyle: 'short', timeStyle: 'medium'
  }).format(new Date());
  await mkdir(config.logDir, { recursive: true });
  const safeDetail = detail.replace(/[\r\n]/g, ' ');
  await appendFile(`${config.logDir}/${file}`, `CNPJ: ${maskedDocument(cnpj)}; Data/Hora: ${now}; ${safeDetail}\r\n`);
}

function requireSession(request: IncomingMessage, response: ServerResponse) {
  const session = readSession(request);
  if (!session) json(response, request, 401, { erro: 'Sessão expirada. Faça uma nova consulta.' });
  return session;
}

const server = http.createServer(async (request, response) => {
  const url = new URL(request.url || '/', `http://${request.headers.host || 'localhost'}`);

  if (request.method === 'OPTIONS') {
    if (!isTrustedOrigin(request)) {
      json(response, request, 403, { erro: 'Origem não autorizada.' });
      return;
    }
    response.writeHead(204, { ...securityHeaders, ...corsHeaders(request) });
    response.end();
    return;
  }

  try {
    if (request.method === 'POST' && url.pathname.startsWith('/api/') && !isTrustedOrigin(request)) {
      json(response, request, 403, { erro: 'Origem não autorizada.' });
      return;
    }

    if (request.method === 'GET' && url.pathname === '/health') {
      json(response, request, 200, { status: 'ok', servico: 'boleto_api' });
      return;
    }

    if (request.method === 'POST' && url.pathname === '/api/consulta') {
      const attempt = consumeLoginAttempt(request);
      if (!attempt.allowed) {
        json(response, request, 429, { erro: 'Muitas tentativas. Aguarde antes de tentar novamente.' }, {
          'Retry-After': String(attempt.retryAfter)
        });
        return;
      }

      const body = await bodyAsJson(request);
      const cnpj = normalizeCnpj(body.cnpj);
      const password = String(body.senha || '');

      if (!isValidCnpjShape(cnpj)) {
        await logAccess('log_falha', String(body.cnpj || ''), 'ERRO: CNPJ invalido');
        json(response, request, 400, { erro: 'CNPJ inválido!' });
        return;
      }
      if (password.length <= 4) {
        await logAccess('log_falha', String(body.cnpj || ''), 'ERRO: Senha com digitos invalidos');
        json(response, request, 400, { erro: 'A senha possui menos do que 5 dígitos e por isso é inválida.' });
        return;
      }

      const authentication = await authenticate(cnpj, password);
      if (authentication === 'invalid_password') {
        await logAccess('log_falha', String(body.cnpj || ''), 'ERRO: Senha invalida');
        json(response, request, 401, { erro: 'Senha inválida! Tente outra senha ou entre em contato com o Setor de Cobrança: (32) 3462-0072.' });
        return;
      }
      if (authentication === 'not_found') {
        await logAccess('log_falha', String(body.cnpj || ''), 'ERRO: Nao existe o CNPJ digitado.');
        json(response, request, 404, { erro: `Títulos não localizados para o CNPJ (${String(body.cnpj || '')}) informado.` });
        return;
      }

      const portal = await customerPortal(cnpj);
      const sessionId = createSession(cnpj);
      loginAttempts.delete(attempt.key);
      await logAccess('log_ok', cnpj, `Documento(s) Listado(s): ${portal?.titulos.map((item) => item.nossoNumero).join(' | ') || ''}`);
      json(response, request, 200, portal, { 'Set-Cookie': sessionCookie(sessionId) });
      return;
    }

    if (request.method === 'GET' && url.pathname === '/api/session') {
      const session = requireSession(request, response);
      if (session) json(response, request, 200, await customerPortal(session.cnpj));
      return;
    }

    if (request.method === 'POST' && url.pathname === '/api/logout') {
      deleteSession(request);
      json(response, request, 200, { status: 'ok' }, { 'Set-Cookie': expiredSessionCookie() });
      return;
    }

    json(response, request, 404, { erro: 'Rota não encontrada.' });
  } catch (error: unknown) {
    if (error instanceof HttpError) {
      json(response, request, error.status, { erro: error.message });
      return;
    }
    console.error(error);
    const knownError = error as { code?: string; name?: string };
    const databaseError = knownError?.code === 'ESOCKET' || knownError?.code === 'ETIMEOUT' || knownError?.name === 'ConnectionError';
    json(response, request, databaseError ? 503 : 500, {
      erro: databaseError
        ? 'Não foi possível conectar ao banco de dados. Verifique a rede ou VPN e tente novamente.'
        : 'Não foi possível concluir a solicitação.'
    });
  }
});

server.requestTimeout = config.httpRequestTimeout;
server.headersTimeout = Math.min(10_000, config.httpRequestTimeout);
server.keepAliveTimeout = 5_000;
server.maxHeadersCount = 100;

setInterval(() => {
  const now = Date.now();
  for (const [key, bucket] of loginAttempts) {
    if (bucket.resetAt <= now) loginAttempts.delete(key);
  }
}, config.loginRateLimit.windowSeconds * 1000).unref();

server.listen(config.port, config.host, () => {
  console.log(`boleto_api disponível em ${config.host}:${config.port}`);
});

async function shutdown() {
  server.close();
  await closePool().catch(() => {});
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
