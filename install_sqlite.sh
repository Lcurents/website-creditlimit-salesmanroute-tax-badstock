#!/bin/bash

echo "=========================================="
echo "🔧 SQLITE3 SETUP UNTUK ARCH LINUX"
echo "=========================================="
echo ""

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo "⚠️  Jangan run sebagai root!"
   echo "Jalankan: ./install_sqlite.sh"
   exit 1
fi

echo "📦 Installing PHP SQLite3 extension..."
echo ""

# Install php-sqlite
sudo pacman -S --needed php-sqlite

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ php-sqlite berhasil terinstall!"
    echo ""
    
    # Enable extension in php.ini
    echo "🔧 Mengaktifkan extension di php.ini..."
    
    # Check if already enabled
    if grep -q "^extension=sqlite3" /etc/php/php.ini 2>/dev/null; then
        echo "✅ Extension sudah aktif di php.ini"
    else
        # Add extension
        echo "📝 Menambahkan extension=sqlite3 ke php.ini..."
        echo "   (Perlu sudo password)"
        
        sudo sed -i '/^;extension=sqlite3/s/^;//' /etc/php/php.ini 2>/dev/null || \
        sudo bash -c 'echo "extension=sqlite3" >> /etc/php/php.ini'
        
        if [ $? -eq 0 ]; then
            echo "✅ Extension berhasil diaktifkan!"
        else
            echo "⚠️  Gagal auto-enable. Silakan edit manual:"
            echo "   sudo nano /etc/php/php.ini"
            echo "   Uncomment atau tambahkan: extension=sqlite3"
        fi
    fi
    
    echo ""
    echo "🧪 Testing SQLite3..."
    php -m | grep -i sqlite
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "=========================================="
        echo "✅ SETUP BERHASIL!"
        echo "=========================================="
        echo ""
        echo "Sekarang jalankan:"
        echo "  ./setup.sh"
        echo ""
    else
        echo ""
        echo "⚠️  SQLite3 belum terdeteksi."
        echo "Coba restart terminal atau jalankan:"
        echo "  sudo systemctl restart php-fpm"
        echo ""
    fi
else
    echo ""
    echo "❌ Gagal install php-sqlite"
    echo "Coba manual:"
    echo "  sudo pacman -S php-sqlite"
    echo ""
fi
