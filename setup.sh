#!/bin/bash

# ==========================================
# QUICK SETUP SCRIPT
# Automatic migration dari JSON ke SQLite
# ==========================================

echo "=========================================="
echo "🚀 DISTRIBUSI APP - SQLITE MIGRATION"
echo "=========================================="
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP tidak terinstall! Install PHP terlebih dahulu."
    exit 1
fi

echo "✓ PHP version: $(php -v | head -n 1)"
echo ""

# Check if SQLite3 extension is enabled
php -r "if (!extension_loaded('sqlite3')) { echo '❌ SQLite3 extension tidak aktif!'; exit(1); }" || exit 1
echo "✓ SQLite3 extension aktif"
echo ""

# Run migration
echo "📦 Menjalankan migrasi database..."
php config/setup.php

if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✅ MIGRASI BERHASIL!"
    echo "=========================================="
    echo ""
    echo "Database location: database/distribusi.db"
    echo ""
    echo "🌐 Cara testing:"
    echo "1. Jalankan PHP built-in server:"
    echo "   php -S localhost:8000"
    echo ""
    echo "2. Buka browser:"
    echo "   http://localhost:8000/login_new.php"
    echo ""
    echo "3. Login dengan:"
    echo "   - Username: faktur / Password: 123 (Fakturis)"
    echo "   - Username: finance / Password: 123 (Finance)"
    echo "   - Username: gudang / Password: 123 (Warehouse)"
    echo ""
    echo "📖 Baca MIGRATION_GUIDE.md untuk detail lengkap"
    echo ""
else
    echo ""
    echo "❌ Migrasi gagal! Cek error di atas."
    exit 1
fi
