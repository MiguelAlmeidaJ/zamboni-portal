# Segurança do Portal de Boletos

## Controles aplicados

- CORS com allowlist exata de origens, sem reflexão de origem arbitrária.
- Validação de `Origin` nas rotas `POST` para reduzir risco de CSRF.
- Cookie de sessão `HttpOnly`, `SameSite=Strict` e, em HTTPS, `Secure` com prefixo `__Host-`.
- Limite de tentativas de autenticação por IP e limite adicional no proxy Nginx.
- Limite de 20 KB e exigência de JSON nas requisições da API.
- Headers CSP, anti-clickjacking, `nosniff`, política de referência e de permissões.
- Timeouts HTTP, limite de headers e limpeza periódica de sessões e rate limits.
- CNPJ mascarado nos arquivos de log.
- Containers Node executados como usuário sem privilégios.
- Portas dos containers publicadas apenas em `127.0.0.1`.
- Erros do PHP ocultos em respostas e assinatura do Apache/PHP reduzida.

## Checklist de homologação

1. Criar `.env` a partir de `.env.hml.example` e nunca versioná-lo.
2. Gerar `LEGACY_INTERNAL_TOKEN` aleatório com ao menos 32 caracteres.
3. Substituir o usuário `sa` por um usuário SQL dedicado com somente as permissões necessárias.
4. Manter `DB_ENCRYPT=true` e usar `DB_TRUST_CERTIFICATE=false` com certificado SQL válido.
5. Publicar somente as portas 80/443; as portas 3330/3331 devem permanecer locais.
6. Instalar certificados TLS válidos para os dois domínios e habilitar renovação automática.
7. Configurar o proxy para sobrescrever, e não confiar no valor recebido de, `X-Forwarded-For`.
8. Rotacionar segredos e revisar os logs de autenticação periodicamente.
9. Executar `pnpm audit --prod`, testes e build antes de cada implantação.

## Riscos residuais

- As sessões ficam em memória. Por isso a API está limitada a uma instância no PM2; escalar exige um armazenamento compartilhado, como Redis.
- A senha é comparada com o valor legado armazenado no SQL Server. Uma migração para hash forte exige planejamento conjunto com o sistema que grava essas senhas.
- A CSP ainda permite scripts inline exigidos pela hidratação atual do Next.js. Uma CSP com nonce pode ser adotada em uma etapa posterior.
- O TLS termina no proxy; os processos Node escutam somente no loopback por essa razão.
- O diretório `via2/` não está presente atualmente no workspace. O código PHP legado não pôde ser revisado e o serviço não pode ser reconstruído de forma reproduzível até essa origem ser restaurada.

Relate vulnerabilidades de forma privada à equipe responsável pelo ambiente; não inclua credenciais, CNPJ ou boletos em issues públicas.
