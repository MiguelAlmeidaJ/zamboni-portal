FROM node:24-bookworm-slim AS dependencies
ENV PNPM_HOME=/pnpm
ENV PATH=$PNPM_HOME:$PATH
RUN corepack enable
WORKDIR /workspace
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY apps/portal_boleto/package.json ./apps/portal_boleto/package.json
COPY apps/boleto_api/package.json ./apps/boleto_api/package.json
RUN pnpm install --frozen-lockfile --filter portal_boleto...

FROM node:24-bookworm-slim AS build
ENV PNPM_HOME=/pnpm
ENV PATH=$PNPM_HOME:$PATH
RUN corepack enable
WORKDIR /workspace
ARG NEXT_PUBLIC_API_URL=http://localhost:3331
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
COPY --from=dependencies /workspace/node_modules ./node_modules
COPY --from=dependencies /workspace/apps/portal_boleto/node_modules ./apps/portal_boleto/node_modules
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY apps/portal_boleto ./apps/portal_boleto
COPY apps/boleto_api/package.json ./apps/boleto_api/package.json
RUN pnpm --filter portal_boleto build

FROM node:24-bookworm-slim AS runtime
WORKDIR /workspace
ARG NEXT_PUBLIC_API_URL=http://localhost:3331
ENV NODE_ENV=production
ENV PORT=3330
ENV HOSTNAME=0.0.0.0
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
COPY --chown=node:node --from=build /workspace/node_modules ./node_modules
COPY --chown=node:node --from=build /workspace/apps/portal_boleto ./apps/portal_boleto
USER node
EXPOSE 3330
CMD ["node", "apps/portal_boleto/node_modules/next/dist/bin/next", "start", "apps/portal_boleto", "-p", "3330"]
