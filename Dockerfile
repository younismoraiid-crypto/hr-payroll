FROM php:8.3-cli
WORKDIR /app
COPY . /app
RUN mkdir -p /app/data && chmod 777 /app/data
EXPOSE 8080
CMD sh -c 'php -S 0.0.0.0:${PORT:-8080} -t /app'
