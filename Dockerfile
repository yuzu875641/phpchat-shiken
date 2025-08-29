# PHPとApacheをベースイメージとして使用
FROM php:8.2-apache

# 必要なPHP拡張機能をインストール
# pdo_pgsql: PostgreSQLデータベースに接続するために必要
# mbstring: マルチバイト文字列関数を使用するために必要
# mysqli: MySQLを使用する可能性に備えて含める
RUN docker-php-ext-install pdo_pgsql mbstring mysqli

# アプリケーションのファイルをコンテナにコピー
# .htaccess、api.php、db.phpなどのすべてのファイルをApacheのドキュメントルートに配置
COPY . /var/www/html/

# コンテナがリッスンするポートを公開
# Apacheのデフォルトポートは80
EXPOSE 80

# Apacheをフォアグラウンドで実行
# ベースイメージのデフォルトコマンドを上書きする場合に必要
CMD ["apache2-foreground"]
