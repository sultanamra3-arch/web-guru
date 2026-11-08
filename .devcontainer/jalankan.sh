#!/usr/bin/env bash
# Menyalakan database dan server PHP SiGuru di port 8080.
cd "$(dirname "$0")/.."
sudo service mariadb start >/dev/null 2>&1 || true
pkill -f "php -S 0.0.0.0:8080" 2>/dev/null || true
setsid nohup php -S 0.0.0.0:8080 -t . > /tmp/siguru.log 2>&1 < /dev/null &
sleep 1
echo "SiGuru jalan di port 8080 (tab PORTS)."
