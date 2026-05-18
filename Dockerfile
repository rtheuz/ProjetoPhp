FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080"]