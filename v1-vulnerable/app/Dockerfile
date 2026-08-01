FROM php:8.1-apache

# Habilitar extension mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

# VULN #11: display_errors activado a proposito (Security Misconfiguration)
RUN { \
    echo 'display_errors = On'; \
    echo 'display_startup_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'file_uploads = On'; \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
} > /usr/local/etc/php/conf.d/vuln-settings.ini

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Permisos abiertos en uploads (VULN #6: Unrestricted File Upload)
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

WORKDIR /var/www/html

EXPOSE 80
