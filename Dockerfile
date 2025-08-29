# PHPとApacheをベースイメージとして使用
FROM php:8.2-apache

# PostgreSQLのクライアントライブラリと開発ヘッダをインストール
# これらは pdo_pgsql をビルドするために必要
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# 必要なPHP拡張機能をインストール
RUN docker-php-ext-install pdo_pgsql mbstring mysqli

# アプリケーションのファイルをコンテナにコピー
COPY . /var/www/html/

# コンテナがリッスンするポートを公開
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]
