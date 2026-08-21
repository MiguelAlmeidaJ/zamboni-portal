import { config } from './config.js';

const RENDERERS: Record<string, string> = {
  '001': 'cobBrasil.php',
  '033': 'cobSantander.php',
  '104': 'cobCaixa.php',
  '237': 'cobBradesco.php',
  '336': 'cobC6.php',
  '341': 'cobItau.php',
  '399': 'cobHSBC.php',
  '422': 'cobSafra.php',
  '655': 'cobVotorantim.php',
  '707': 'cobDaycoval.php',
  '745': 'cobCiti.php',
  '756': 'cobSicoob.php'
};

const ZAMBONI_ONLY_BANKS = new Set(['399', '745']);

export class LegacyBoletoError extends Error {
  constructor(public readonly status: number, message: string) {
    super(message);
  }
}

export interface LegacyBoletoRequest {
  cnpj: string;
  password: string;
  nossoNumero: string;
  banco: string;
  empresa: string;
}

function legacyBaseUrl(): URL {
  if (!config.legacyBoletoBaseUrl) {
    throw new LegacyBoletoError(503, 'O visualizador de boletos não está configurado no servidor.');
  }

  let url: URL;
  try {
    url = new URL(config.legacyBoletoBaseUrl);
  } catch {
    throw new LegacyBoletoError(503, 'A URL interna do visualizador de boletos é inválida.');
  }

  if (url.protocol !== 'http:' && url.protocol !== 'https:') {
    throw new LegacyBoletoError(503, 'A URL interna do visualizador de boletos deve usar HTTP ou HTTPS.');
  }
  if (!url.pathname.endsWith('/')) url.pathname += '/';
  url.search = '';
  url.hash = '';
  return url;
}

export function rendererForLegacyBoleto(bank: string, company: string): string | null {
  const normalizedBank = String(bank).padStart(3, '0');
  const normalizedCompany = String(company).trim().toUpperCase();
  if (ZAMBONI_ONLY_BANKS.has(normalizedBank) && normalizedCompany !== 'ZAMB') return null;
  return RENDERERS[normalizedBank] || null;
}

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function extractLegacyCookie(response: Response): string {
  const raw = response.headers.get('set-cookie') || '';
  const match = raw.match(/(?:^|,\s*)(COBIZAB=[^;,\s]+)/i) || raw.match(/(COBIZAB=[^;]+)/i);
  return match?.[1] || '';
}

async function decodeLegacyText(response: Response): Promise<string> {
  const bytes = new Uint8Array(await response.arrayBuffer());
  return new TextDecoder('windows-1252').decode(bytes);
}

async function legacyFetch(url: URL, init: RequestInit = {}): Promise<Response> {
  try {
    return await fetch(url, {
      ...init,
      signal: AbortSignal.timeout(config.httpRequestTimeout)
    });
  } catch {
    throw new LegacyBoletoError(502, 'Não foi possível acessar o renderizador interno de boletos.');
  }
}

async function legacyLogin(cnpj: string, password: string): Promise<{ cookie: string; listing: string }> {
  const base = legacyBaseUrl();
  const indexUrl = new URL('index.php', base);

  const initial = await legacyFetch(indexUrl, { redirect: 'manual' });
  let cookie = extractLegacyCookie(initial);
  if (!cookie) {
    throw new LegacyBoletoError(502, 'O sistema legado não iniciou uma sessão de boleto.');
  }

  const login = await legacyFetch(indexUrl, {
    method: 'POST',
    redirect: 'manual',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      Cookie: cookie
    },
    body: new URLSearchParams({ bCnpj: cnpj, bPass: password, busca: 'ok' })
  });

  cookie = extractLegacyCookie(login) || cookie;
  if (login.status < 200 || login.status >= 400) {
    throw new LegacyBoletoError(502, 'O sistema legado recusou a autenticação interna do boleto.');
  }

  const listingResponse = await legacyFetch(indexUrl, {
    headers: { Cookie: cookie },
    redirect: 'manual'
  });
  if (!listingResponse.ok) {
    throw new LegacyBoletoError(502, 'Não foi possível consultar os boletos no sistema legado.');
  }

  return { cookie, listing: await decodeLegacyText(listingResponse) };
}

function hashFromListing(listing: string, nossoNumero: string): string | null {
  const number = escapeRegExp(nossoNumero);
  const match = listing.match(new RegExp(
    `<a\\s+[^>]*href=["']([^"']*cobi\\.php\\?hsh=[^"']+)["'][^>]*>\\s*${number}\\s*</a>`,
    'i'
  ));
  if (!match) return null;

  try {
    const href = match[1].replace(/&amp;/g, '&');
    return new URL(href, legacyBaseUrl()).searchParams.get('hsh');
  } catch {
    return null;
  }
}

export function rewriteLegacyAssets(fragment: string): string {
  return fragment.replace(/(?:\.\/)?img\/([A-Za-z0-9._-]+)/g, '/api/boleto-asset?file=$1');
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function documentHtml(fragment: string, nossoNumero: string): string {
  const title = escapeHtml(`Boleto ${nossoNumero}`);
  return `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title}</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#f4f1f0;color:#231719;font-family:Arial,sans-serif}.viewer-header{max-width:900px;margin:30px auto 0;background:#fff;border:1px solid #ddd4d5;border-bottom:3px solid #c8102e;border-radius:6px 6px 0 0;padding:20px 24px;display:flex;align-items:center;gap:18px}.viewer-title{flex:1}.viewer-title strong{display:block;font-size:22px}.viewer-title span{display:block;margin-top:4px;color:#74696b;font-size:13px}.viewer-actions{display:flex;gap:12px}.viewer-actions button{height:44px;padding:0 22px;border-radius:6px;border:1px solid #c8102e;background:#fff;color:#b00827;font-weight:700;cursor:pointer}.viewer-actions button.primary{background:#bd0627;color:#fff}.viewer-body{max-width:900px;min-height:500px;margin:0 auto 40px;background:#fff;padding:32px 24px;overflow:auto;border:1px solid #ddd4d5;border-top:0}.viewer-body>#container,.viewer-body>#boleto{margin-left:auto!important;margin-right:auto!important}@media(max-width:760px){.viewer-header{margin-top:0;border-radius:0;align-items:flex-start;flex-direction:column}.viewer-actions{width:100%}.viewer-actions button{flex:1;padding:0 10px}.viewer-body{padding:20px 8px}}@media print{body{background:#fff}.viewer-header{display:none}.viewer-body{max-width:none;margin:0;padding:0;border:0;overflow:visible}}
  </style>
</head>
<body>
  <header class="viewer-header">
    <div class="viewer-title"><strong>Visualização do boleto</strong><span>Documento bancário</span></div>
    <div class="viewer-actions"><button type="button" onclick="window.print()">Salvar em PDF</button><button class="primary" type="button" onclick="window.print()">Imprimir</button></div>
  </header>
  <main class="viewer-body">${fragment}</main>
</body>
</html>`;
}

export async function renderLegacyBoleto(input: LegacyBoletoRequest): Promise<string> {
  const renderer = rendererForLegacyBoleto(input.banco, input.empresa);
  if (!renderer) {
    throw new LegacyBoletoError(422, 'Este banco não possui um layout de boleto disponível para a empresa informada.');
  }

  const { cookie, listing } = await legacyLogin(input.cnpj, input.password);
  const hash = hashFromListing(listing, input.nossoNumero);
  if (!hash) {
    throw new LegacyBoletoError(404, 'O boleto não foi localizado no sistema de emissão.');
  }

  const renderUrl = new URL(`include/${renderer}`, legacyBaseUrl());
  renderUrl.searchParams.set('hsh', hash);
  renderUrl.searchParams.set('iobs', '');
  const response = await legacyFetch(renderUrl, { headers: { Cookie: cookie } });
  if (!response.ok) {
    throw new LegacyBoletoError(502, 'O sistema de emissão não conseguiu montar o boleto.');
  }

  const fragment = rewriteLegacyAssets(await decodeLegacyText(response));
  if (!fragment.trim() || /dados do boleto invalidos/i.test(fragment)) {
    throw new LegacyBoletoError(502, 'O sistema de emissão retornou um boleto inválido.');
  }

  return documentHtml(fragment, input.nossoNumero);
}

function contentTypeFor(file: string): string {
  const extension = file.toLowerCase().split('.').pop();
  if (extension === 'png') return 'image/png';
  if (extension === 'gif') return 'image/gif';
  if (extension === 'webp') return 'image/webp';
  return 'image/jpeg';
}

export async function fetchLegacyAsset(file: string): Promise<{ body: Buffer; contentType: string }> {
  if (!/^[A-Za-z0-9._-]{1,100}$/.test(file)) {
    throw new LegacyBoletoError(400, 'Arquivo de imagem inválido.');
  }

  const url = new URL(`img/${file}`, legacyBaseUrl());
  const response = await legacyFetch(url);
  if (!response.ok) {
    throw new LegacyBoletoError(404, 'Imagem do boleto não encontrada.');
  }

  return {
    body: Buffer.from(await response.arrayBuffer()),
    contentType: response.headers.get('content-type') || contentTypeFor(file)
  };
}
