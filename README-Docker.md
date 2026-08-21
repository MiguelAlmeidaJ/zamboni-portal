# Portal de boletos

O ambiente foi separado em três serviços:

- `apps/portal_boleto`: frontend Next.js na porta 3330;
- `apps/boleto_api`: API HTTP em Node.js puro na porta 3331;
- `boleto_legacy`: serviço PHP interno para preservar os cálculos bancários.

O frontend e a API ficam em `apps/` e formam um monorepo TypeScript gerenciado por PNPM.

## Estrutura

```text
.
├── apps/
│   ├── boleto_api/       # API Node.js + TypeScript
│   └── portal_boleto/    # Frontend Next.js + TypeScript
├── docker/               # Configurações do PHP e Apache
├── infra/nginx/          # Modelo de proxy reverso e TLS
├── logs/                 # Logs mascarados de autenticação da API
├── tools/process_manager # Dependência e comandos do PM2
├── via2/                 # Código PHP legado esperado pelo Compose
├── compose.yaml          # Orquestração dos três serviços
├── Dockerfile            # Imagem do serviço PHP legado
├── package.json          # Scripts do monorepo
└── pnpm-workspace.yaml   # Definição dos workspaces em apps/* e tools/*
```

## Subir

```powershell
docker compose up -d --build
```

Para instalar e validar o monorepo fora do Docker:

```powershell
pnpm install
pnpm typecheck
pnpm test
pnpm build
```

Acesse <http://localhost:3330>. A API responde em <http://localhost:3331/health>.

## Homologação

Os endereços definidos para homologação são:

- portal: <https://hmlzamboni.nivel3ti.com.br>;
- API: <https://apibzamboni.nivel3ti.com.br>.

Copie `.env.hml.example` para `.env`, preencha os segredos e faça o build somente
depois disso, pois `NEXT_PUBLIC_API_URL` é incorporada ao frontend durante o build.
O modelo de proxy reverso e TLS está em `infra/nginx/zamboni-hml.conf.example`.

```powershell
Copy-Item .env.hml.example .env
pnpm install --frozen-lockfile
pnpm build
docker compose up -d boleto_legacy
pnpm pm2:takeover
```

Docker e PM2 são modos alternativos para o portal e a API: não execute os dois
ao mesmo tempo nas portas 3330/3331. `pm2:takeover` para os dois containers Node,
mantém o PHP legado no Docker e inicia o portal e a API pelo PM2. O legado fica
disponível apenas no loopback, em `127.0.0.1:8082`.

Se os containers Node já estiverem parados, use apenas `pnpm pm2:start`. Esse
comando verifica o build e as portas antes de criar os processos.

Para acompanhar ou reiniciar os processos:

```powershell
pnpm pm2:logs
pnpm pm2:status
pnpm pm2:restart
pnpm pm2:stop
```

Use sempre os comandos `pnpm pm2:*`, que executam a versão do PM2 fixada pelo
projeto. Um `pm2` instalado globalmente pode exibir uma versão diferente.

No Linux, execute uma vez `pnpm --filter process_manager exec pm2 startup` e
depois `pnpm --filter process_manager exec pm2 save` para restaurar os processos
após reinicialização do servidor.

O PM2 inicia uma instância de cada aplicação em `127.0.0.1`: portal na porta
3330 e API na 3331. O Nginx é responsável por TLS e pelos domínios públicos.

## Banco de dados

As credenciais ficam no arquivo `.env`, ignorado pelo Git. O servidor precisa ser
resolvido e acessível de dentro do container. Para um SQL Server executado no
Windows local, configure:

```dotenv
DB_HOST=host.docker.internal,1433
```

Para acompanhar a aplicação:

```powershell
docker compose ps
docker compose logs -f portal_boleto boleto_api boleto_legacy
```

Para encerrar:

```powershell
docker compose down
```

Consulte [SECURITY.md](SECURITY.md) antes de publicar o ambiente.
