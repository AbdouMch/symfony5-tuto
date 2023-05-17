ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-apache-bullseye


COPY ./conf.d/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./conf.d/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# configure ssl certificate
RUN a2enmod rewrite && a2enmod ssl && a2enmod socache_shmcb
RUN sed -i '/SSLCertificateFile.*snakeoil\.pem/c\SSLCertificateFile \/etc\/ssl\/certs\/mycert.crt' /etc/apache2/sites-available/default-ssl.conf && sed -i '/SSLCertificateKeyFile.*snakeoil\.key/cSSLCertificateKeyFile /etc/ssl/private/mycert.key\' /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

ENV APACHE_DOCUMENT_ROOT /srv/app/public
# for nodejs when compiling https://bobbyhadz.com/blog/react-error-digital-envelope-routines-unsupported
ENV NODE_OPTIONS="--openssl-legacy-provider"

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    apt-utils \
    gnupg \
    bash \
    curl \
    locales \
    iproute2 \
    git \
    libicu-dev \
    g++ \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libxslt-dev \
    wkhtmltopdf \
    xvfb \
    ;

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get update && \
    apt-get install -y nodejs;

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && \
    apt-get install yarn;

RUN pecl install apcu && \
	pecl install xdebug-3.1.5 && \
	pecl install redis && \
    pecl clear-cache && \
	docker-php-ext-enable apcu \
    xdebug \
    opcache \
    redis \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure sysvsem --enable-sysvsem \
    && docker-php-ext-install pdo_mysql gd intl zip calendar dom mbstring zip gd xsl sysvsem;

COPY ./conf.d/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# configure wkhtmltopdf
RUN ln -s /usr/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf;
RUN chmod +x /usr/local/bin/wkhtmltopdf;

# install dependencies
COPY ./install-vendors.sh /usr/local/bin/install-vendors.sh
RUN chmod +x /usr/local/bin/install-vendors.sh
RUN /usr/local/bin/install-vendors.sh

WORKDIR /srv/app

