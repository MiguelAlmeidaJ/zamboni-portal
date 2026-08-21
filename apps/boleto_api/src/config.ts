function numberFromEnv(name: string, fallback: number): number {
  const value = Number.parseInt(process.env[name] || '', 10);
  return Number.isInteger(value) && value > 0 ? value : fallback;
}

function booleanFromEnv(name: string, fallback: boolean): boolean {
  const value = process.env[name]?.trim().toLowerCase();
  if (!value) return fallback;
  return value === 'true' || value === '1' || value === 'yes';
}

function originsFromEnv(value: string | undefined): string[] {
  return (value || 'http://localhost:3330')
    .split(',')
    .map((origin) => new URL(origin.trim()).origin);
}

function databaseAddress(value: string | undefined): { server: string; port?: number } {
  const address = value || 'localhost';
  const match = address.match(/^(.*),(\d+)$/);

  return match
    ? { server: match[1], port: Number.parseInt(match[2], 10) }
    : { server: address };
}

export const config = {
  host: process.env.HOST || '0.0.0.0',
  port: numberFromEnv('PORT', 3331),
  frontendOrigins: originsFromEnv(process.env.FRONTEND_ORIGIN),
  sessionHours: numberFromEnv('SESSION_HOURS', 8),
  cookieSecure: booleanFromEnv('COOKIE_SECURE', false),
  trustProxy: booleanFromEnv('TRUST_PROXY', false),
  loginRateLimit: {
    maxAttempts: numberFromEnv('LOGIN_RATE_LIMIT_MAX', 10),
    windowSeconds: numberFromEnv('LOGIN_RATE_LIMIT_WINDOW_SECONDS', 900)
  },
  httpRequestTimeout: numberFromEnv('HTTP_REQUEST_TIMEOUT', 30000),
  logDir: process.env.LOG_DIR || '',
  database: {
    ...databaseAddress(process.env.DB_HOST),
    database: process.env.DB_NAME || 'BOLETO',
    user: process.env.DB_USER || 'sa',
    password: process.env.DB_PASSWORD || '',
    connectionTimeout: numberFromEnv('DB_CONNECTION_TIMEOUT', 10000),
    requestTimeout: numberFromEnv('DB_REQUEST_TIMEOUT', 15000),
    options: {
      encrypt: process.env.DB_ENCRYPT !== 'false',
      trustServerCertificate: process.env.DB_TRUST_CERTIFICATE !== 'false',
      useUTC: true
    },
    pool: { min: 0, max: 5, idleTimeoutMillis: 30000 }
  }
};
