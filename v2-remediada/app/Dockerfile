FROM php:8.1-fpm

# Extensiones necesarias (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# SEC-FIX: configuracion endurecida de PHP
RUN { \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/php_errors.log'; \
    echo 'expose_php = Off'; \
    echo 'file_uploads = On'; \
    echo 'upload_max_filesize = 50M'; \
    echo 'post_max_size = 50M'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 1'; \
    echo 'session.use_strict_mode = 1'; \
} > /usr/local/etc/php/conf.d/security-hardening.ini

# Carpeta de uploads privada, fuera del webroot, con permisos restrictivos
RUN mkdir -p /var/www/uploads_privados && chown -R www-data:www-data /var/www/uploads_privados && chmod 750 /var/www/uploads_privados

# Instalar Composer para gestionar dependencias actualizadas
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
