# -----------------------------
# Base: install dependencies
# -----------------------------
FROM node:24-alpine AS deps
WORKDIR /app

RUN corepack enable && corepack prepare yarn@stable --activate

COPY package.json yarn.lock ./

RUN yarn install --frozen-lockfile

# -----------------------------
# Build: build the app
# -----------------------------
FROM node:24-alpine AS build
WORKDIR /app

RUN corepack enable && corepack prepare yarn@stable --activate

COPY --from=deps /app/node_modules ./node_modules
COPY . .

ARG NODE_ENV=production
ENV NODE_ENV=$NODE_ENV
RUN if [ "$NODE_ENV" = "production" ]; then yarn build; fi

# -----------------------------
# Run: minimal runtime
# -----------------------------
FROM node:24-alpine AS run
WORKDIR /app
ENV NODE_ENV=production

COPY --from=build /app/.output ./.output

EXPOSE 3000
CMD ["node", ".output/server/index.mjs"]
