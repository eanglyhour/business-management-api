#run php image
FROM php:8.4-cli   

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY . .

EXPOSE 9000

CMD ["php", "-S", "0.0.0.0:9000", "-t", "public"]