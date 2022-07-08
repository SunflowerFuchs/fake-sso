### Multi-Stage build
FROM php:7.4-alpine AS prod

# For github container registry
LABEL org.opencontainers.image.source=https://github.com/sunflowerfuchs/fake-sso

RUN mkdir /data
COPY ./src/ /app
WORKDIR /app

CMD ["php", "-S", "0.0.0.0:80", "/app/Index.php"]
EXPOSE 80

### Second Stage

FROM prod AS dev
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del -f .build-deps