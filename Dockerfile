FROM php:8.4-cli
RUN docker-php-ext-install pdo_mysql
WORKDIR /app
COPY . .
RUN chmod +x docker/start.sh
EXPOSE 8080
CMD ["docker/start.sh"]
