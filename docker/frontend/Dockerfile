# Frontend Vue: compila o build de produção e serve com nginx.
# O nginx também repassa /api para o container do backend, então o navegador fala só com a porta 3000.
FROM node:22-alpine AS build

WORKDIR /app

# O binário do Cypress só serve para testes E2E e não é necessário no build.
ENV CYPRESS_INSTALL_BINARY=0

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund --loglevel=error

COPY frontend/ ./
RUN npm run build

FROM nginx:alpine

COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
