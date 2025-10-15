# Stage 1: Build React app
FROM node:20-alpine AS build

WORKDIR /app

# копируем package.json и lock-файлы
COPY frontend/package*.json ./

# устанавливаем зависимости
RUN npm install --legacy-peer-deps

# копируем все исходники фронтенда
COPY frontend/ ./

# билдим React
RUN npm run build


# Stage 2: Serve with nginx
FROM nginx:alpine

# копируем собранный фронт в nginx
COPY --from=build /app/build /usr/share/nginx/html

# подключаем конфиг nginx из docker/nginx/frontend.conf
COPY docker/nginx/frontend.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]