FROM php:8.2-fpm

# Set apt sources
RUN echo "deb https://deb.debian.org/debian bullseye main\ndeb https://deb.debian.org/debian-security bullseye-security main\ndeb https://deb.debian.org/debian bullseye-updates main\n" > /etc/apt/sources.list

# Installing and Updating Packages
RUN apt-get update && apt-get install -y \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    postgresql-client \
    && docker-php-ext-install zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install sockets \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd sockets

#####################################
# DEBUG
#####################################

ARG DEBUG=true
RUN if [ ${DEBUG} = true ]; then \
    # Install the xdebug extension
    pecl install xdebug && \
    docker-php-ext-enable xdebug \
;fi

# RUN if [ ${DEBUG} = true ]; then \
#     ip -4 route list match 0/0 | awk '{print $3 " host.docker.internal"}' >> /etc/hosts \
# ;fi

RUN if [ ${DEBUG} = true ]; then \
    cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
;fi

RUN if [ ${DEBUG} != true ]; then \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
;fi

RUN if [ ${DEBUG} = true ]; then \
    echo '\n\
[XDebug]\n\
xdebug.mode = debug\n\
xdebug.start_with_request = yes\n\
xdebug.discover_client_host = false\n\
xdebug.client_host = host.docker.internal\n\
xdebug.idekey = XDEBUG\n\
xdebug.client_port = 9003\n'\
    >> /usr/local/etc/php/php.ini \
;fi

# Setting Working Directory
WORKDIR /var/www/html

# # Copy existing application directory contents
# COPY src /var/www/html
# COPY .docker/devel.env /var/www/html/.env

# # User Permission Stuff
RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

# # Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html

# Change current user to www
USER www-data

# # Reset composer state
# RUN rm -f composer.lock
# RUN rm -rf vendor

# # Running Composer Install
# RUN env $(cat /var/www/html/.env | grep COMPOSER_AUTH) composer install --no-scripts

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["/bin/sh", "-c", "composer install && php-fpm"]