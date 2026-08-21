import type { NextConfig } from 'next';
import { existsSync } from 'node:fs';
import path from 'node:path';

const workspaceRoot = path.resolve(process.cwd(), '../..');
const workspaceEnv = path.join(workspaceRoot, '.env');

if (existsSync(workspaceEnv) && typeof process.loadEnvFile === 'function') {
  process.loadEnvFile(workspaceEnv);
}

const apiOrigin = new URL(process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3331').origin;
const isHttpsDeployment = apiOrigin.startsWith('https://');
const contentSecurityPolicy = [
  "default-src 'self'",
  "base-uri 'self'",
  `connect-src 'self' ${apiOrigin}${process.env.NODE_ENV === 'development' ? ' ws: wss:' : ''}`,
  "font-src 'self' data:",
  "form-action 'self'",
  "frame-ancestors 'none'",
  `img-src 'self' data: ${apiOrigin}`,
  "object-src 'none'",
  "script-src 'self' 'unsafe-inline'",
  "style-src 'self' 'unsafe-inline'",
  ...(isHttpsDeployment ? ['upgrade-insecure-requests'] : [])
].join('; ');

const responseHeaders = [
  { key: 'Content-Security-Policy', value: contentSecurityPolicy },
  { key: 'Cross-Origin-Opener-Policy', value: 'same-origin' },
  { key: 'Cross-Origin-Resource-Policy', value: 'same-site' },
  { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=(), payment=(), usb=()' },
  { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'X-DNS-Prefetch-Control', value: 'off' },
  { key: 'X-Frame-Options', value: 'DENY' },
  ...(isHttpsDeployment
    ? [{ key: 'Strict-Transport-Security', value: 'max-age=31536000; includeSubDomains' }]
    : [])
];

const nextConfig: NextConfig = {
  poweredByHeader: false,
  outputFileTracingRoot: workspaceRoot,
  turbopack: {
    root: workspaceRoot
  },
  async headers() {
    return [{ source: '/(.*)', headers: responseHeaders }];
  }
};

export default nextConfig;
