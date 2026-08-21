# Portal de boletos Zamboni

Monorepo PNPM em TypeScript, executado com PM2:

- `apps/portal_boleto`: frontend Next.js na porta 3330;
- `apps/boleto_api`: API Node.js na porta 3331;
- `tools/process_manager`: versão do PM2 usada pelo projeto.

## Estrutura

```text
.
├── apps/
│   ├── boleto_api/
│   └── portal_boleto/
├── logs/
├── scripts/
├── tools/process_manager/
├── ecosystem.config.cjs
├── package.json
└── pnpm-workspace.yaml
```

## Executar localmente

Requisitos: Node.js 24 ou superior e PNPM 11.

```powershell
pnpm install --frozen-lockfile
pnpm build
pnpm pm2:start
```

Acessos locais:

- portal: <http://localhost:3330>;
- API: <http://localhost:3331/health>.

O comando de início verifica se o build existe e se as portas estão livres.

```powershell
pnpm pm2:status
pnpm pm2:logs
pnpm pm2:restart
pnpm pm2:stop
pnpm pm2:delete
```

Use os comandos `pnpm pm2:*` para garantir que a versão fixada no monorepo seja
utilizada, independentemente de instalações globais existentes na máquina.

Para desenvolvimento com recarregamento automático, execute em terminais separados:

```powershell
pnpm dev:portal
pnpm dev:api
```

## Homologação

Endereços públicos:

- portal: <https://hmlzamboni.nivel3ti.com.br>;
- API: <https://apibzamboni.nivel3ti.com.br>.

No servidor, crie o `.env` usando `.env.hml.example`, preencha o acesso ao SQL
Server e faça o build depois de definir `NEXT_PUBLIC_API_URL`:

```powershell
pnpm install --frozen-lockfile
pnpm build
pnpm pm2:start
```

Os dois processos escutam em `0.0.0.0`. Configure o Traefik externo com os alvos:

- `IP_DO_SERVIDOR:3330` para `hmlzamboni.nivel3ti.com.br`;
- `IP_DO_SERVIDOR:3331` para `apibzamboni.nivel3ti.com.br`.

Restrinja as portas 3330/3331 no firewall para que somente o Traefik consiga
acessá-las. O encerramento TLS fica no Traefik.

## Banco de dados

Configure no `.env`:

```dotenv
DB_HOST=localhost,1433
DB_NAME=BOLETO
DB_USER=portal_boleto_app
DB_PASSWORD=troque_aqui
DB_ENCRYPT=true
DB_TRUST_CERTIFICATE=false
```

A consulta lista os títulos disponíveis no SQL Server. A visualização e a
impressão dos boletos são geradas nativamente pela API TypeScript a partir dos
dados de `dbo.Boleto_Titulo_Ativo`, sem dependência do sistema PHP legado. Os
layouts atualmente portados cobrem os bancos habilitados no portal.

Consulte [SECURITY.md](SECURITY.md) antes de publicar o ambiente.
