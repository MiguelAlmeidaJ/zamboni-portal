const net = require('node:net');
const path = require('node:path');
const { existsSync } = require('node:fs');
const { spawnSync } = require('node:child_process');

const rootDir = path.resolve(__dirname, '..');
const envFile = path.join(rootDir, '.env');

if (existsSync(envFile)) {
  if (typeof process.loadEnvFile !== 'function') {
    throw new Error('Este projeto requer Node.js 24 ou superior.');
  }
  process.loadEnvFile(envFile);
}

function portFromEnv(name, fallback) {
  const value = Number.parseInt(process.env[name] || '', 10);
  return Number.isInteger(value) && value > 0 && value <= 65535 ? value : fallback;
}

function portIsAvailable(port) {
  return new Promise((resolve) => {
    const server = net.createServer();
    server.unref();
    server.once('error', () => resolve(false));
    server.listen({ host: '127.0.0.1', port, exclusive: true }, () => {
      server.close(() => resolve(true));
    });
  });
}

async function main() {
  const requiredBuilds = [
    path.join(rootDir, 'apps/portal_boleto/.next/BUILD_ID'),
    path.join(rootDir, 'apps/boleto_api/dist/src/server.js')
  ];
  const missingBuilds = requiredBuilds.filter((file) => !existsSync(file));

  if (missingBuilds.length) {
    console.error('Os artefatos de produção não foram encontrados. Execute `pnpm build` primeiro.');
    process.exitCode = 1;
    return;
  }

  const ports = [
    { name: 'portal_boleto', port: portFromEnv('FRONTEND_PORT', 3330) },
    { name: 'boleto_api', port: portFromEnv('API_PORT', 3331) }
  ];
  const checks = await Promise.all(ports.map(async (item) => ({
    ...item,
    available: await portIsAvailable(item.port)
  })));
  const conflicts = checks.filter((item) => !item.available);

  if (conflicts.length) {
    console.error('PM2 não iniciado: há portas ocupadas:');
    for (const conflict of conflicts) {
      console.error(`- ${conflict.name}: 127.0.0.1:${conflict.port}`);
    }
    console.error('\nDocker e PM2 não podem executar os mesmos aplicativos simultaneamente.');
    console.error('Para trocar do Docker para PM2, execute: `pnpm pm2:takeover`.');
    console.error('Para manter o Docker, use: `docker compose ps`.');
    process.exitCode = 1;
    return;
  }

  const pnpmCli = process.env.npm_execpath;
  if (!pnpmCli) throw new Error('Não foi possível localizar o executável do PNPM.');

  const result = spawnSync(process.execPath, [
    pnpmCli,
    '--filter', 'process_manager',
    'exec', 'pm2',
    'start', path.join(rootDir, 'ecosystem.config.cjs'),
    '--env', 'production'
  ], {
    cwd: rootDir,
    env: process.env,
    stdio: 'inherit'
  });

  process.exitCode = result.status ?? 1;
}

main().catch((error) => {
  console.error(error instanceof Error ? error.message : error);
  process.exitCode = 1;
});
