FROM php:7.3-alpine

RUN apk add --no-cache git

WORKDIR /usr/local/bin
RUN set -ex; \
    wget https://getcomposer.org/composer.phar; \
    chmod +x composer.phar; \
    ln -sf composer.phar composer

ADD ./install-app.sh /usr/local/bin

WORKDIR /app
RUN set -ex; \
    adduser -D runner; \
    chown runner /app; \
    su - runner -c "sh /usr/local/bin/install-app.sh"; \
    rm -f /usr/local/bin/install-app.sh

USER runner
ENTRYPOINT ["composer", "run-script", "runner"]
