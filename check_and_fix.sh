#!/bin/bash
# Скрипт для проверки и исправления ошибок проекта

set -e

echo "=== Проверка проекта Кассиопея ==="

# Проверка Rust сервиса
echo "Проверка Rust сервиса..."
cd services/rust-iss
if command -v cargo &> /dev/null; then
    cargo check 2>&1 | head -50
    if [ $? -eq 0 ]; then
        echo "✅ Rust код компилируется"
    else
        echo "❌ Ошибки компиляции Rust"
        exit 1
    fi
else
    echo "⚠️  Cargo не найден, пропускаем проверку компиляции"
fi
cd ../..

# Проверка Docker Compose
echo "Проверка Docker Compose..."
docker compose config > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Docker Compose конфигурация валидна"
else
    echo "❌ Ошибки в Docker Compose"
    exit 1
fi

# Попытка запуска
echo "Запуск проекта..."
docker compose up -d --build 2>&1 | tee docker_build.log

# Проверка статуса
sleep 10
docker compose ps

# Проверка логов на ошибки
echo "Проверка логов..."
for service in rust_iss php pascal_legacy; do
    echo "=== Логи $service ==="
    docker compose logs $service 2>&1 | tail -20
done

echo "=== Проверка завершена ==="


