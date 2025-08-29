# PHPとApacheをベースイメージとして使用
FROM php:8.2-apache

# 必要な開発ライブラリをインストール
# libpq-dev: pdo_pgsql をビルドするために必要
# libonig-dev: mbstring をビルドするために必要
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# 必要なPHP拡張機能をインストール
RUN docker-php-ext-install pdo_pgsql mbstring mysqli

# アプリケーションのファイルをコンテナにコピー
COPY . /var/www/html/

# コンテナがリッスンするポートを公開
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]
