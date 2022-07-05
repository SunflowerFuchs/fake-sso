FROM php:7.4-alpine

# For github container registry
LABEL org.opencontainers.image.source=https://github.com/sunflowerfuchs/fake-sso

RUN mkdir /data
COPY ./src/ /app
WORKDIR /app

CMD ["php", "-S", "0.0.0.0:80", "/app/Index.php"]
EXPOSE 80
