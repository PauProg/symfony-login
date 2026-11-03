FROM php:8.4.1-cli
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN apt-get update && apt-get install git unzip -y && rm -rf /var/lib/apt/lists/*