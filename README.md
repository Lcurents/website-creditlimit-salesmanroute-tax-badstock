# 📦 Distribusi App - Sales & Credit Management System

> Modern sales distribution system dengan credit limit management, FIFO payment allocation, dan inventory tracking.

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+ dengan SQLite3 extension
- Web server (Apache/Nginx) atau PHP built-in server

### Installation

**Option 1: Automatic Setup (Recommended)**
```bash
./setup.sh
```

**Option 2: Manual Setup**
```bash
# 1. Setup database & migrate data
php config/setup.php

# 2. Start development server
php -S localhost:8000

# 3. Open browser
http://localhost:8000/login.php
```

### Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Fakturis (Sales) | `faktur` | `123` |
| Finance | `finance` | `123` |
| Warehouse | `gudang` | `123` |

---

## ✨ Features

### 🎯 Core Features
- ✅ **Multi-item Sales Orders** - Input pesanan dengan banyak item sekaligus
- ✅ **Credit Limit Management** - Auto-check kredit limit pelanggan
- ✅ **FIFO Payment System** - Pelunasan otomatis melunasi faktur terlama
- ✅ **Order Approval Workflow** - Approval untuk order over-limit
- ✅ **Real-time Stock Checking** - Validasi stok sebelum order diproses
- ✅ **Payment Allocation** - Track pembayaran per faktur

### 📊 Modules
1. **Dashboard** - Ringkasan omset, piutang, dan pending orders
2. **Distribution** - Input & monitoring sales orders
3. **Finance** - Pelunasan & piutang management
4. **Badstock** - Klaim & inventory barang rusak
5. **Schedule** - Jadwal kunjungan salesman
6. **Pajak** - Management pajak kendaraan operasional
7. **Settings** - Management produk, customer, dan user

---

## 📁 Project Structure

```
website-creditlimit-salesmanroute-tax-badstock/
├── config/
│   ├── database.php       # Database connection class
│   ├── schema.sql         # Database schema
│   └── setup.php          # Migration script
├── includes/
│   └── functions.php      # Helper functions
├── database/
│   └── distribusi.db      # SQLite database
├── uploads/               # File uploads (bukti pajak, badstock)
├── data_backup/           # Backup JSON files
├── old_version/           # Backup old PHP files
│
├── login.php             # Authentication
├── logout.php            # Logout handler
├── index.php             # Dashboard
├── distribution.php      # Sales orders
├── finance.php           # Finance & payments
├── badstock.php          # Badstock management
├── schedule.php          # Salesman schedule
├── pajak.php             # Vehicle tax
├── settings.php          # Settings
├── profile.php           # User profile
│
├── print_invoice.php     # Print faktur
├── print_sj.php          # Print surat jalan
├── print_schedule.php    # Print jadwal
│
├── sidebar.php           # Sidebar navigation
├── style.css             # Stylesheet
│
└── setup.sh              # Auto-setup script
```

---

## 🗄️ Database Schema

### Main Tables

**users** - User authentication & roles
```sql
- id, username, password (hashed), role, fullname
- Roles: FAKTURIS, AR_FINANCE, WAREHOUSE, CASHIER
```

**customers** - Pelanggan/toko
```sql
- id, name, address, phone, credit_limit, current_debt
```

**products** - Produk/barang
```sql
- id, name, price, stock, unit
```

**orders** - Order header
```sql
- id, order_date, due_date, paid_date, customer_id, total_amount, status
- Status: ON HOLD, APPROVED, DELIVERED, PAID
```

**order_items** - Order detail
```sql
- id, order_id, product_id, qty, unit_price, subtotal
```

**payments** - Payment history
```sql
- id, payment_date, customer_id, amount, processed_by
```

**payment_allocations** - FIFO mapping
```sql
- id, payment_id, order_id, allocated_amount
```

---

## 🔧 Technical Details

### Security Features
- ✅ Password hashing dengan `password_hash()` & `password_verify()`
- ✅ Prepared statements untuk prevent SQL injection
- ✅ Input sanitization
- ✅ Session-based authentication
- ✅ Role-based access control

### Database
- **Engine**: SQLite3
- **Transaction Support**: ACID compliance
- **Foreign Keys**: Enabled
- **Indexes**: Optimized queries

### Code Quality
- **Architecture**: Modular structure
- **Pattern**: MVC-like separation
- **Error Handling**: Try-catch blocks
- **Code Style**: Consistent naming

---

## 📖 User Guide

### 1. Login
Buka `login.php` dan masukkan username & password sesuai role Anda.

### 2. Dashboard
Lihat ringkasan:
- Omset hari ini
- Order pending approval
- Total piutang

### 3. Buat Order (Fakturis)
1. Pilih pelanggan
2. Tambah barang yang dipesan (bisa multi-item)
3. Sistem akan auto-check:
   - Stok barang
   - Credit limit pelanggan
4. Jika over-limit → status ON HOLD (butuh approval Finance)
5. Jika aman → langsung APPROVED

### 4. Approve Order (Finance)
Finance bisa approve order yang ON HOLD dari menu Distribution.

### 5. Confirm Delivery
Setelah barang dikirim, ubah status jadi DELIVERED dan cetak faktur.

### 6. Input Pembayaran (Finance/Cashier)
1. Pilih pelanggan yang bayar
2. Input jumlah uang
3. Sistem akan auto-alokasi FIFO:
   - Melunasi faktur terlama dulu
   - Update status jadi PAID
   - Kurangi hutang customer

---

## 🔄 Migration dari JSON

Project ini sudah di-migrate dari JSON ke SQLite. File JSON lama ada di `data_backup/` sebagai backup.

### Kenapa Migrate ke SQLite?
1. **Security**: Password di-hash, prevent SQL injection
2. **Performance**: Query indexed, lebih cepat dari read full JSON
3. **Concurrent Access**: Multiple users tanpa data corruption
4. **Transaction**: ACID compliance, rollback on error
5. **Relational**: Easy join antar tabel

### Rollback (jika perlu)
File PHP lama ada di `old_version/` dan data JSON di `data_backup/`.

---

## 🐛 Troubleshooting

### Database Error
```bash
# Reset database
rm database/distribusi.db
php config/setup.php
```

### Permission Error
```bash
chmod 755 database/
chmod 644 database/distribusi.db
chmod 755 uploads/
```

### Login Error (Password Salah)
Pastikan sudah menjalankan `setup.php` untuk hash password.
Password tetap sama seperti di JSON lama, hanya di-hash di database.

---

## 🎯 TODO / Roadmap

- [ ] Migrate remaining files (badstock, schedule, pajak, settings) ke SQLite
- [ ] Implement CSRF token protection
- [ ] Add data export (Excel/PDF)
- [ ] Add pagination untuk list data
- [ ] Implement logging & audit trail
- [ ] Add unit testing
- [ ] API endpoints (REST API)
- [ ] Mobile responsive UI

---

## 📝 Changelog

### Version 2.0.0 (2026-01-16)
- ✅ Migrate dari JSON ke SQLite
- ✅ Password hashing & security improvements
- ✅ FIFO payment allocation system
- ✅ Multi-item order support
- ✅ Transaction support dengan rollback
- ✅ Modular code structure
- ✅ Helper functions library

### Version 1.0.0
- Initial release dengan JSON storage
- Basic order & payment management

---

## 👨‍💻 Developer Notes

### Helper Functions (`includes/functions.php`)
- `check_login()` - Validate user session
- `check_role($roles)` - Check user role
- `clean_input($data)` - Sanitize input
- `rupiah($angka)` - Format currency
- `tanggal_indo($date)` - Format tanggal Indonesia
- `status_badge($status)` - Generate status badge
- `alert_message($msg, $type)` - Generate alert box
- `upload_file()` - Handle file upload
- `paginate()` - Pagination helper

### Database Class (`config/database.php`)
```php
$db = Database::getInstance();

// Query (SELECT)
$users = $db->query("SELECT * FROM users WHERE role = :role", ['role' => 'FAKTURIS']);

// Execute (INSERT/UPDATE/DELETE)
$db->execute("UPDATE products SET stock = stock - :qty WHERE id = :id", ['qty' => 10, 'id' => 1]);

// Transaction
$db->beginTransaction();
try {
    $db->execute("...");
    $db->execute("...");
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
}

// Last Insert ID
$id = $db->lastInsertId();
```

---

## 📄 License

Proprietary - Internal Use Only

---

## 📞 Support

Untuk pertanyaan atau bug report, hubungi developer.

---

**Last Updated:** January 16, 2026  
**Version:** 2.0.0 (SQLite)
