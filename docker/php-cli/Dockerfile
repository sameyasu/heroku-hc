FROM php:7.3-alpine

RUN apk add --no-cache git

WORKDIR /usr/local/bin
RUN set -ex; \
    wget https://getcomposer.org/composer.phar; \
    chmod +x composer.phar; \
    ln -sf composer.phar composer

WORKDIR /app
RUN set -ex; \
    adduser -D runner; \
    chown runner /app

USER runner
RUN set -ex; \
    git clone --depth 1 https://github.com/sameyasu/heroku-hc.git /app; \
    composer install

ENTRYPOINT ["composer", "run-script", "runner"]
