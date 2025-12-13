#!/usr/bin/env bash
set -e
echo "[pascal] compiling legacy.pas"
fpc -O2 -S2 legacy.pas
echo "[pascal] running legacy CSV generator and importer"
./legacy &
LEGACY_PID=$!

# Запускаем конвертер CSV в XLSX в фоне
echo "[pascal] starting CSV to XLSX converter"
python3 csv_to_xlsx.py /data/csv &
CONVERTER_PID=$!

# Ждем завершения основного процесса
wait $LEGACY_PID
