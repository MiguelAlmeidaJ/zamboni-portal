FROM node:24-bookworm-slim

ENV PNPM_HOME=/pnpm
ENV PATH=$PNPM_HOME:$PATH
RUN corepack enable

WORKDIR /workspace

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY apps/boleto_api/package.json ./apps/boleto_api/package.json
COPY apps/portal_boleto/package.json ./apps/portal_boleto/package.json
RUN pnpm install --frozen-lockfile --filter boleto_api...

COPY apps/boleto_api ./apps/boleto_api
RUN pnpm --filter boleto_api build
RUN mkdir -p /app/log && chown -R node:node /app/log /workspace/apps/boleto_api

ENV NODE_ENV=production
USER node
EXPOSE 3331

CMD ["node", "apps/boleto_api/dist/src/server.js"]
