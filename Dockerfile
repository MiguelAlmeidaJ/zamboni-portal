FROM php:8.3-apache-bookworm

ARG SQLSRV_VERSION=5.12.0

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        libgssapi-krb5-2 \
        libonig-dev \
        unixodbc-dev \
        $PHPIZE_DEPS \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list \
        -o /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && docker-php-ext-install mbstring \
    && pecl install sqlsrv-${SQLSRV_VERSION} pdo_sqlsrv-${SQLSRV_VERSION} \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && a2enmod headers \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS libonig-dev unixodbc-dev gnupg \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache-servername.conf /etc/apache2/conf-enabled/servername.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zamboni.ini

WORKDIR /var/www/html
