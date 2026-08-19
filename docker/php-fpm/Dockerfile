FROM php:8.4-fpm

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

    RUN install-php-extensions \
        pdo_mysql \
        mysqli \
        intl \
        zip \
        bcmath \
        pcntl \
        opcache \
        sockets \
        redis \
        excimer \
        pcov \
        @composer

    WORKDIR /var/www/project

    RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

    RUN mkdir -p /var/www/.config/psysh /var/www/.cache/composer \
        && chown -R www-data:www-data /var/www/.config /var/www/.cache /var/www/project

    COPY --chown=www-data:www-data . .

    USER www-data

    EXPOSE 9000

    CMD ["php-fpm"]
