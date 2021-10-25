FROM php:7.4-alpine

COPY ./src/ /app
WORKDIR /app

CMD ["php", "-S", "0.0.0.0:80", "/app/Index.php"]
EXPOSE 80
