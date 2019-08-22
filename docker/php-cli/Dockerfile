FROM php:7.3-alpine

RUN apk add --no-cache git

WORKDIR /usr/local/bin
RUN set -ex; \
    wget https://getcomposer.org/composer.phar; \
    chmod +x composer.phar; \
    ln -sf composer.phar composer

WORKDIR /app
RUN set -ex; \
    git clone --depth 1 https://github.com/sameyasu/heroku-hc.git .; \
    composer install

RUN set -ex; \
    adduser -D runner

USER runner
ENTRYPOINT ["composer", "run-script", "runner"]
