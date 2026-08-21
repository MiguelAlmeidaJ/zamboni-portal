# Segurança do Portal de Boletos

## Controles aplicados

- CORS com allowlist exata de origens.
- Validação de `Origin` nas rotas `POST` para reduzir risco de CSRF.
- Cookie `HttpOnly`, `SameSite=Strict` e, em HTTPS, `Secure` com prefixo `__Host-`.
- Limite de tentativas de autenticação por IP.
- Limite de 20 KB e exigência de JSON nas requisições da API.
- CSP, proteção contra clickjacking, `nosniff` e políticas de referência e permissões.
- Timeouts HTTP, limite de headers e limpeza periódica de sessões e rate limits.
- CNPJ mascarado nos arquivos de log.
- Processos gerenciados por uma versão de PM2 fixada no lockfile.

## Checklist de homologação

1. Criar `.env` a partir de `.env.hml.example` e nunca versioná-lo.
2. Usar uma conta SQL dedicada com somente as permissões necessárias.
3. Manter `DB_ENCRYPT=true` e `DB_TRUST_CERTIFICATE=false` com certificado válido.
4. Permitir acesso às portas 3330/3331 somente a partir do Traefik.
5. Garantir que o Traefik sobrescreva `X-Forwarded-For` antes de habilitar `TRUST_PROXY`.
6. Rotacionar credenciais e revisar os logs de autenticação periodicamente.
7. Executar `pnpm audit --prod`, testes e build antes de cada implantação.

## Riscos residuais

- As sessões ficam em memória. A API está limitada a uma instância; escalar exige
  armazenamento compartilhado, como Redis.
- A senha é comparada com o valor armazenado atualmente no SQL Server. Migrar
  para hash forte exige coordenação com o sistema responsável pela gravação.
- A CSP permite scripts inline exigidos pela hidratação atual do Next.js. Uma CSP
  com nonce pode ser adotada posteriormente.
- O TLS termina no Traefik; o tráfego entre ele e os processos deve permanecer em
  rede privada e restrita por firewall.

Não inclua credenciais, CNPJ ou documentos de cobrança em issues públicas.
