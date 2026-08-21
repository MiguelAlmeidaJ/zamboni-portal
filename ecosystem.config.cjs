const { existsSync } = require('node:fs');
const path = require('node:path');

const rootDir = __dirname;
const envFile = path.join(rootDir, '.env');

if (existsSync(envFile)) {
  if (typeof process.loadEnvFile !== 'function') {
    throw new Error('O PM2 deste projeto requer Node.js 24 ou superior.');
  }
  process.loadEnvFile(envFile);
}

function portFromEnv(name, fallback) {
  const value = Number.parseInt(process.env[name] || '', 10);
  return Number.isInteger(value) && value > 0 && value <= 65535 ? value : fallback;
}

const frontendPort = portFromEnv('FRONTEND_PORT', 3330);
const apiPort = portFromEnv('API_PORT', 3331);

const common = {
  cwd: rootDir,
  exec_mode: 'fork',
  instances: 1,
  autorestart: true,
  watch: false,
  min_uptime: '10s',
  max_restarts: 10,
  restart_delay: 1000,
  kill_timeout: 10000,
  time: true
};

module.exports = {
  apps: [
    {
      ...common,
      name: 'portal_boleto',
      script: 'apps/portal_boleto/node_modules/next/dist/bin/next',
      args: `start apps/portal_boleto -p ${frontendPort} -H 127.0.0.1`,
      max_memory_restart: '512M',
      env_production: {
        NODE_ENV: 'production',
        PORT: String(frontendPort),
        HOSTNAME: '127.0.0.1'
      }
    },
    {
      ...common,
      name: 'boleto_api',
      script: 'apps/boleto_api/dist/src/server.js',
      max_memory_restart: '384M',
      env_production: {
        NODE_ENV: 'production',
        PORT: String(apiPort),
        HOST: '127.0.0.1'
      }
    }
  ]
};
