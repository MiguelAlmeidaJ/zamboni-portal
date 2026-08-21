import { randomBytes } from 'node:crypto';
import type { IncomingMessage } from 'node:http';
import { config } from './config.js';

interface SessionRecord {
  cnpj: string;
  expiresAt: number;
}

export interface PortalSession extends SessionRecord {
  id: string;
}

const sessions = new Map<string, SessionRecord>();
const maxAgeSeconds = config.sessionHours * 60 * 60;
const cookieName = config.cookieSecure ? '__Host-portal_session' : 'portal_session';

setInterval(() => {
  const now = Date.now();
  for (const [id, session] of sessions) {
    if (session.expiresAt <= now) sessions.delete(id);
  }
}, Math.min(maxAgeSeconds, 3600) * 1000).unref();

function parseCookies(header = ''): Record<string, string> {
  return Object.fromEntries(
    header.split(';').filter(Boolean).map((item) => {
      const separator = item.indexOf('=');
      return [item.slice(0, separator).trim(), decodeURIComponent(item.slice(separator + 1))];
    })
  );
}

export function createSession(cnpj: string): string {
  const id = randomBytes(32).toString('hex');
  sessions.set(id, { cnpj, expiresAt: Date.now() + maxAgeSeconds * 1000 });
  return id;
}

export function readSession(request: IncomingMessage): PortalSession | null {
  const id = parseCookies(request.headers.cookie)[cookieName];
  const session = id ? sessions.get(id) : null;

  if (!session || session.expiresAt < Date.now()) {
    if (id) sessions.delete(id);
    return null;
  }

  return { id, ...session };
}

export function deleteSession(request: IncomingMessage): void {
  const id = parseCookies(request.headers.cookie)[cookieName];
  if (id) sessions.delete(id);
}

export function sessionCookie(id: string): string {
  const secure = config.cookieSecure ? '; Secure' : '';
  return `${cookieName}=${id}; HttpOnly; SameSite=Strict; Path=/; Max-Age=${maxAgeSeconds}${secure}`;
}

export function expiredSessionCookie(): string {
  const secure = config.cookieSecure ? '; Secure' : '';
  return `${cookieName}=; HttpOnly; SameSite=Strict; Path=/; Max-Age=0${secure}`;
}
