#!/usr/bin/env bash
# Menyiapkan SiGuru untuk uji coba di Codespaces: MariaDB + data contoh (fiktif).
set -e
cd "$(dirname "$0")/.."
sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mariadb-server >/dev/null
php -m | grep -qi pdo_mysql || sudo docker-php-ext-install pdo_mysql >/dev/null
sudo service mariadb start
sudo mysql -e "CREATE DATABASE IF NOT EXISTS siguru_demo CHARACTER SET utf8mb4;
  CREATE USER IF NOT EXISTS 'demo'@'localhost' IDENTIFIED BY 'demo';
  CREATE USER IF NOT EXISTS 'demo'@'127.0.0.1' IDENTIFIED BY 'demo';
  GRANT ALL ON siguru_demo.* TO 'demo'@'localhost';
  GRANT ALL ON siguru_demo.* TO 'demo'@'127.0.0.1';"
if ! sudo mysql siguru_demo -e "SELECT 1 FROM guru_akun LIMIT 1" >/dev/null 2>&1; then
  sudo mysql siguru_demo < .devcontainer/demo.sql
fi
[ -f config.php ] || cp .devcontainer/config.demo.php config.php
echo "SiGuru siap. Masuk: admin / admin123"
