FROM php:8.2-apache

# PostgreSQL PDO extensionを有効化
RUN docker-php-ext-install pdo_pgsql

# アプリケーションのコードをコンテナにコピー
COPY . /var/www/html/

# Apacheの設定を更新してURLリライトを有効にする
RUN a2enmod rewrite

# ディレクトリ権限を設定
RUN chown -R www-data:www-data /var/www/html

CMD ["apache2-foreground"]
