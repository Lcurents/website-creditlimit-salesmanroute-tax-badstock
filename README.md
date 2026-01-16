# 📦 Distribusi App - Sales & Credit Management System

> Sistem manajemen distribusi modern dengan credit limit management, FIFO payment allocation, inventory tracking, dan badstock management. Dibangun dengan PHP & SQLite untuk performa maksimal dan kemudahan deployment.

![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?style=flat&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=flat&logo=sqlite&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red)

---

## 📑 Table of Contents

- [Quick Start](#-quick-start)
- [Fitur Utama](#-fitur-utama)
- [Panduan Lengkap](#-panduan-lengkap-per-modul)
- [Database Schema](#-database-schema-detail)
- [Instalasi](#-instalasi-lengkap)
- [Troubleshooting](#-troubleshooting)
- [Developer Guide](#-developer-guide)

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+ dengan SQLite3 extension
- Web server (Apache/Nginx) atau PHP built-in server

### Installation (3 Steps)

```bash
# 1. Install SQLite3 extension (Arch Linux)
./install_sqlite.sh

# 2. Setup database & migrate data
./setup.sh

# 3. Start server
php -S localhost:8000
```

### Login

Buka browser: **http://localhost:8000/login.php**

| Role | Username | Password | Akses |
|------|----------|----------|-------|
| **Fakturis (Sales)** | `faktur` | `123` | Input order, lihat schedule |
| **Finance** | `finance` | `123` | Approve order, input payment |
| **Warehouse** | `gudang` | `123` | Manage pajak, approve badstock |

---

## ✨ Fitur Utama

### 🎯 Core Features

1. **Multi-item Sales Orders**
   - Input pesanan dengan banyak item sekaligus
   - Real-time stock checking
   - Auto-calculation total harga
   
2. **Credit Limit Management**
   - Auto-check kredit limit pelanggan
   - Alert jika over-limit
   - Approval workflow untuk order over-limit

3. **FIFO Payment System**
   - Pelunasan otomatis melunasi faktur terlama
   - Payment allocation tracking
   - History pembayaran lengkap

4. **Order Approval Workflow**
   - Order over-limit masuk status ON HOLD
   - Finance bisa approve/reject
   - Notifikasi real-time

5. **Real-time Inventory**
   - Auto-reduce stock saat order approved
   - Alert stok habis
   - Tracking stock movement

---

## 📖 Panduan Lengkap Per Modul

### 1. 🔐 LOGIN & AUTHENTICATION

**Fitur:**
- Secure password hashing (bcrypt)
- Session-based authentication
- Role-based access control (RBAC)
- Auto-redirect based on role

**Cara Pakai:**
1. Buka `login.php`
2. Masukkan username & password
3. Sistem akan redirect ke dashboard sesuai role

**Security Features:**
- Password di-hash dengan `password_hash()`
- Input sanitization untuk prevent XSS
- Prepared statements untuk prevent SQL injection
- Session timeout otomatis

---

### 2. 📊 DASHBOARD

**Fitur:**
- **Omset Hari Ini**: Total penjualan hari ini (exclude order ON HOLD)
- **Order Pending**: Jumlah order yang butuh approval
- **Total Piutang**: Total hutang semua pelanggan
- **Recent Activities**: 5 order terakhir dengan status

**Cara Pakai:**
- Dashboard otomatis muncul setelah login
- Data real-time dari database
- Klik card untuk detail

**Metrics yang Ditampilkan:**
```
📊 Omset Hari Ini: Rp XXX,XXX
   - Total dari order dengan status APPROVED, DELIVERED, PAID
   - Filter berdasarkan tanggal hari ini

⏳ Butuh Approval: X order
   - Order dengan status ON HOLD
   - Hanya Finance yang bisa approve

💰 Total Piutang: Rp XXX,XXX
   - Sum dari current_debt di tabel customers
   - Uang yang belum dibayar pelanggan
```

---

### 3. 📦 DISTRIBUTION (Sales Order Management)

**Fitur Lengkap:**

#### A. Input Order Baru (Fakturis Only)
- **Multi-item Order**: Tambah banyak barang dalam 1 order
- **Real-time Calculation**: Hitung total otomatis saat input
- **Credit Limit Indicator**: 
  - 🟢 Hijau = Aman (dalam limit)
  - 🔴 Merah = Bahaya (over limit)
- **Stock Validation**: Cek stok sebelum submit
- **Auto Status**: 
  - Jika dalam limit → APPROVED
  - Jika over limit → ON HOLD

#### B. Monitoring Order
- **Filter by Status**: ON HOLD, APPROVED, DELIVERED, PAID
- **Detail per Order**: 
  - Customer info
  - List barang yang dipesan
  - Total amount
  - Tanggal order & due date
- **Actions**:
  - Approve (Finance only)
  - Cetak SJ (Surat Jalan)
  - Confirm Delivery
  - Cetak Faktur

**Workflow:**
```
1. Fakturis Input Order
   ↓
2. Sistem Check Credit Limit
   ↓
3a. Aman → Status: APPROVED
    ↓
    Cetak Surat Jalan
    ↓
    Kirim Barang
    ↓
    Confirm Delivery → Status: DELIVERED
    ↓
    Cetak Faktur

3b. Over Limit → Status: ON HOLD
    ↓
    Finance Review
    ↓
    Approve → ke step 3a
    atau
    Reject → Cancel order
```

**Contoh Use Case:**
```
Toko Jaya mau pesan:
- Minyak Goreng 10 dus @ Rp 150,000 = Rp 1,500,000
- Gula Pasir 5 karung @ Rp 100,000 = Rp 500,000
Total: Rp 2,000,000

Credit Limit Toko Jaya: Rp 5,000,000
Hutang Sekarang: Rp 3,500,000
Sisa Limit: Rp 1,500,000

Hasil: Over limit Rp 500,000 → Status ON HOLD
```

---

### 4. 💰 FINANCE (Keuangan & Pelunasan)

**Fitur Lengkap:**

#### A. Input Pembayaran (FIFO System)
- **Pilih Customer**: Dropdown customer yang punya hutang
- **Input Amount**: Jumlah uang yang disetor
- **Auto FIFO Allocation**: 
  - Sistem otomatis melunasi faktur terlama dulu
  - Update status faktur jadi PAID
  - Kurangi current_debt customer

#### B. Payment History
- 10 pembayaran terakhir
- Detail: Tanggal, customer, amount, processed by

#### C. Customer Debt Management
- List semua customer dengan hutang
- Indikator status:
  - 🟢 AMAN: < 70% dari limit
  - 🟡 WASPADA: 70-90% dari limit
  - 🔴 KRITIS: > 90% dari limit

**FIFO Payment Logic:**
```
Contoh:
Customer: Toko Makmur
Hutang Total: Rp 5,000,000

Faktur yang belum lunas (DELIVERED):
1. INV-001: Rp 2,000,000 (2024-12-01)
2. INV-005: Rp 1,500,000 (2024-12-15)
3. INV-008: Rp 1,500,000 (2025-01-05)

Bayar: Rp 3,000,000

Hasil FIFO:
- INV-001: Lunas Rp 2,000,000 → Status: PAID
- INV-005: Lunas Rp 1,000,000 (sisa Rp 500,000 masih DELIVERED)
- INV-008: Belum kena (masih DELIVERED)

Hutang baru: Rp 2,000,000
```

**Benefits FIFO:**
- Faktur lama tidak menumpuk
- Jelas tracking umur piutang
- Sesuai standar akuntansi

---

### 5. 🗑️ BADSTOCK (Barang Rusak/Kadaluarsa)

**Fitur:**

#### A. Klaim Badstock (Salesman)
- **Upload Bukti Foto**: Foto barang rusak
- **Detail Klaim**:
  - Customer yang komplain
  - Produk yang rusak
  - Quantity
  - Alasan (expired, rusak, dll)
- **Status**: PENDING → Tunggu approval

#### B. Approval Badstock (Admin/Warehouse)
- **Review Klaim**: 
  - Lihat foto bukti
  - Cek detail
- **Action**: 
  - Approve → Potong stok gudang, masukkan badstock inventory
  - Reject → Klaim ditolak

#### C. Badstock Inventory
- List barang rusak yang ada di gudang
- Total quantity per produk
- History dari klaim yang di-approve

**Workflow:**
```
1. Salesman dapat komplain customer
   ↓
2. Salesman buat klaim + upload foto
   ↓
3. Admin/Warehouse review
   ↓
4. Approve:
   - Stok gudang utama -X
   - Badstock inventory +X
   - Status klaim: APPROVED
   
   Reject:
   - Status klaim: REJECTED
   - Tidak ada perubahan stok
```

---

### 6. 📅 SCHEDULE (Jadwal Kunjungan Salesman)

**Fitur:**

#### A. Buat Jadwal
- **Assign Salesman**: Pilih salesman
- **Pilih Customer**: Customer yang akan dikunjungi
- **Set Tanggal**: Tanggal kunjungan
- **Sequence**: Urutan kunjungan (1, 2, 3, ...)

#### B. View Jadwal
- **Filter by Salesman**: Lihat jadwal per salesman
- **Filter by Date**: Lihat jadwal per tanggal
- **Route Planning**: Urutkan berdasarkan sequence

#### C. Print Schedule
- Cetak jadwal harian salesman
- Include: Nama toko, alamat, sequence

**Use Case:**
```
Salesman: Andi
Tanggal: 16 Januari 2026

Route:
1. Toko Makmur - Jl. Sudirman 123
2. Warung Jaya - Jl. Gatot Subroto 45
3. Toko Berkah - Jl. Ahmad Yani 78
4. Mini Market Sejahtera - Jl. Diponegoro 90

Print → Salesman bawa list ini saat berangkat
```

---

### 7. 🚗 PAJAK KENDARAAN

**Fitur:**

#### A. Data Kendaraan
- **Plat Nomor**: Nomor polisi kendaraan
- **Nama Kendaraan**: Mobil box, motor, dll
- **Driver**: Supir yang pakai
- **Tanggal Jatuh Tempo**: Kapan harus bayar pajak

#### B. Upload Bukti Bayar (Warehouse)
- Upload foto bukti bayar pajak
- Status: PENDING → tunggu validasi

#### C. Validasi Pajak (Fakturis)
- **Review Bukti**: Cek foto/scan bukti bayar
- **Action**:
  - Approve → Tanggal jatuh tempo +1 tahun
  - Reject → Harus upload ulang

**Workflow:**
```
1. Pajak kendaraan jatuh tempo (alert di sistem)
   ↓
2. Warehouse bayar pajak + upload bukti
   ↓
3. Fakturis validasi bukti
   ↓
4. Approve → Tanggal perpanjang 1 tahun
   Reject → Warehouse upload ulang
```

**Benefit:**
- Track pajak semua kendaraan
- Tidak ada yang telat bayar
- Audit trail lengkap

---

### 8. ⚙️ SETTINGS (Master Data)

**Fitur:**

#### A. Master Produk
- **CRUD Products**: Create, Read, Update, Delete
- **Fields**: Nama, harga, stok, unit (PCS/DUS/KARUNG)
- **Stock Management**: Update stok manual

#### B. Master Customer
- **CRUD Customers**: Manage data pelanggan
- **Fields**: 
  - Nama toko
  - Alamat & telpon
  - Credit limit
  - Current debt (auto-update dari order)

#### C. Master User
- **CRUD Users**: Manage user akun
- **Fields**:
  - Username
  - Password (auto-hash)
  - Role (FAKTURIS, AR_FINANCE, WAREHOUSE, CASHIER)
  - Fullname

**Role Permissions:**
```
FAKTURIS:
- Input order
- View schedule
- Lihat dashboard

AR_FINANCE:
- Approve order over-limit
- Input payment
- View reports

WAREHOUSE:
- Upload bukti pajak
- Approve badstock

CASHIER:
- Input payment (sama seperti Finance)
```

---

### 9. 🖨️ PRINT FEATURES

#### A. Print Invoice (Faktur)
- **Trigger**: Order status DELIVERED atau PAID
- **Content**:
  - Header: Company info
  - No Faktur: INV-{id}
  - Customer info
  - Detail barang (item, qty, harga, subtotal)
  - Total amount
  - Status pembayaran
  - Tanggal jatuh tempo
  - Tanda tangan area

#### B. Print Surat Jalan (SJ)
- **Trigger**: Order status APPROVED
- **Content**:
  - No SJ: SJ-{id}
  - Dari: Company
  - Kepada: Customer + alamat
  - List barang (nama, qty)
  - Tanda tangan: Pengirim, Penerima, Admin

**Format:**
- A4 Portrait
- Print-friendly CSS
- Auto-print option
- Professional layout

---

## 🗄️ Database Schema Detail

### ERD (Entity Relationship Diagram)

```
┌─────────────┐       ┌──────────────┐       ┌─────────────┐
│   USERS     │       │  CUSTOMERS   │       │  PRODUCTS   │
├─────────────┤       ├──────────────┤       ├─────────────┤
│ id (PK)     │       │ id (PK)      │       │ id (PK)     │
│ username    │       │ name         │       │ name        │
│ password    │       │ address      │       │ price       │
│ role        │       │ phone        │       │ stock       │
│ fullname    │       │ credit_limit │       │ unit        │
└─────────────┘       │ current_debt │       └─────────────┘
                      └──────────────┘
                             │
                             │ 1:N
                             ▼
                      ┌──────────────┐
                      │   ORDERS     │◄──────┐
                      ├──────────────┤       │
                      │ id (PK)      │       │ 1:N
                      │ customer_id  │       │
                      │ total_amount │       │
                      │ status       │       │
                      │ order_date   │       │
                      │ due_date     │       │
                      │ paid_date    │       │
                      └──────────────┘       │
                             │               │
                             │ 1:N           │
                             ▼               │
                      ┌──────────────┐       │
                      │ ORDER_ITEMS  │       │
                      ├──────────────┤       │
                      │ id (PK)      │       │
                      │ order_id (FK)│───────┘
                      │ product_id   │
                      │ qty          │
                      │ unit_price   │
                      │ subtotal     │
                      └──────────────┘
```

### Tables Detail

#### 1. **users** - User Authentication
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,  -- Hashed dengan password_hash()
    role TEXT NOT NULL,      -- FAKTURIS, AR_FINANCE, WAREHOUSE, CASHIER
    fullname TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Purpose:** Menyimpan data user untuk login & authorization

**Sample Data:**
```
| id | username | role       | fullname          |
|----|----------|------------|-------------------|
| 1  | faktur   | FAKTURIS   | Silvia Fakturis   |
| 2  | finance  | AR_FINANCE | Siti Finance      |
| 3  | gudang   | WAREHOUSE  | Pak Septa Gudang  |
```

---

#### 2. **customers** - Data Pelanggan/Toko
```sql
CREATE TABLE customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address TEXT,
    phone TEXT,
    credit_limit REAL DEFAULT 0,    -- Limit kredit (Rp)
    current_debt REAL DEFAULT 0,     -- Hutang saat ini (Rp)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Purpose:** Master data pelanggan dengan credit limit management

**Business Logic:**
- `credit_limit`: Maksimal hutang yang diperbolehkan
- `current_debt`: Total hutang yang belum dibayar
- `available_limit = credit_limit - current_debt`

**Sample Data:**
```
| id | name        | credit_limit | current_debt | available |
|----|-------------|--------------|--------------|-----------|
| 1  | Toko Makmur | 5,000,000    | 2,300,000    | 2,700,000 |
| 2  | Warung Jaya | 3,000,000    | 500,000      | 2,500,000 |
```

---

#### 3. **products** - Master Produk
```sql
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL,             -- Harga satuan (Rp)
    stock INTEGER DEFAULT 0,         -- Stok tersedia
    unit TEXT DEFAULT 'PCS',         -- Satuan (PCS/DUS/KARUNG)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Purpose:** Master data produk/barang

**Sample Data:**
```
| id | name            | price   | stock | unit   |
|----|-----------------|---------|-------|--------|
| 1  | Minyak Goreng   | 150,000 | 100   | DUS    |
| 2  | Gula Pasir      | 100,000 | 50    | KARUNG |
| 3  | Beras Premium   | 200,000 | 75    | KARUNG |
```

---

#### 4. **orders** - Header Order
```sql
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE,                   -- Tanggal jatuh tempo
    paid_date DATE,                  -- Tanggal lunas
    customer_id INTEGER NOT NULL,
    total_amount REAL NOT NULL,      -- Total order (Rp)
    status TEXT NOT NULL,            -- ON HOLD, APPROVED, DELIVERED, PAID
    created_by INTEGER,              -- User yang buat order
    approved_by INTEGER,             -- User yang approve
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

**Status Flow:**
```
ON HOLD → APPROVED → DELIVERED → PAID
   ↑         ↓
   └─────────┘ (jika reject)
```

**Sample Data:**
```
| id | customer_id | total_amount | status    | order_date | due_date   |
|----|-------------|--------------|-----------|------------|------------|
| 1  | 1           | 2,000,000    | PAID      | 2026-01-01 | 2026-01-08 |
| 2  | 2           | 1,500,000    | DELIVERED | 2026-01-10 | 2026-01-17 |
| 3  | 1           | 3,000,000    | ON HOLD   | 2026-01-15 | 2026-01-22 |
```

---

#### 5. **order_items** - Detail Item per Order
```sql
CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,            -- Jumlah barang
    unit_price REAL NOT NULL,        -- Harga saat order
    subtotal REAL NOT NULL,          -- qty * unit_price
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

**Purpose:** Detail barang per order (mendukung multi-item order)

**Sample Data:**
```
| id | order_id | product_id | qty | unit_price | subtotal  |
|----|----------|------------|-----|------------|-----------|
| 1  | 1        | 1          | 10  | 150,000    | 1,500,000 |
| 2  | 1        | 2          | 5   | 100,000    | 500,000   |
| 3  | 2        | 3          | 15  | 200,000    | 3,000,000 |
```

**Why Separate Table?**
- Support multi-item per order
- Track harga saat order (meski harga produk berubah)
- Easy to query detail

---

#### 6. **payments** - History Pembayaran
```sql
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_id INTEGER NOT NULL,
    amount REAL NOT NULL,            -- Jumlah bayar (Rp)
    payment_method TEXT DEFAULT 'CASH',
    notes TEXT,
    processed_by INTEGER,            -- User yang input
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);
```

**Purpose:** Track semua pembayaran customer

**Sample Data:**
```
| id | customer_id | amount    | payment_date | processed_by |
|----|-------------|-----------|--------------|--------------|
| 1  | 1           | 2,000,000 | 2026-01-08   | 2 (finance)  |
| 2  | 2           | 1,500,000 | 2026-01-12   | 2 (finance)  |
```

---

#### 7. **payment_allocations** - FIFO Mapping
```sql
CREATE TABLE payment_allocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_id INTEGER NOT NULL,
    order_id INTEGER NOT NULL,
    allocated_amount REAL NOT NULL,  -- Berapa yang dialokasikan
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

**Purpose:** Track kemana uang pembayaran dialokasikan (FIFO)

**Sample Data:**
```
| id | payment_id | order_id | allocated_amount |
|----|------------|----------|------------------|
| 1  | 1          | 1        | 2,000,000        |
| 2  | 2          | 2        | 1,000,000        |
| 3  | 2          | 5        | 500,000          |
```

**FIFO Logic:**
```
Payment #2 = Rp 1,500,000
Allocations:
- Order #2 (oldest): Lunas Rp 1,000,000
- Order #5 (next):   Parsial Rp 500,000
```

---

#### 8. **cars** - Data Kendaraan
```sql
CREATE TABLE cars (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_plate TEXT UNIQUE NOT NULL,  -- Plat nomor
    vehicle_name TEXT NOT NULL,          -- Nama kendaraan
    driver_name TEXT,
    tax_due_date DATE NOT NULL,          -- Jatuh tempo pajak
    proof_image TEXT,                    -- Bukti bayar
    validation_status TEXT DEFAULT 'NONE', -- NONE, PENDING, APPROVED, REJECTED
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Purpose:** Management pajak kendaraan operasional

**Sample Data:**
```
| id | license_plate | vehicle_name  | tax_due_date | validation_status |
|----|---------------|---------------|--------------|-------------------|
| 1  | B 1234 ABC    | Mobil Box     | 2026-06-15   | APPROVED          |
| 2  | B 5678 XYZ    | Motor Vario   | 2026-03-20   | PENDING           |
```

---

#### 9. **salesman_schedules** - Jadwal Kunjungan
```sql
CREATE TABLE salesman_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    salesman_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    visit_date DATE NOT NULL,
    sequence INTEGER DEFAULT 1,      -- Urutan kunjungan
    created_by TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (salesman_id) REFERENCES users(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

**Purpose:** Jadwal route salesman harian

**Sample Data:**
```
| id | salesman_id | customer_id | visit_date | sequence |
|----|-------------|-------------|------------|----------|
| 1  | 1 (Andi)    | 1           | 2026-01-16 | 1        |
| 2  | 1 (Andi)    | 2           | 2026-01-16 | 2        |
| 3  | 1 (Andi)    | 5           | 2026-01-16 | 3        |
```

---

#### 10. **badstock_claims** - Klaim Barang Rusak
```sql
CREATE TABLE badstock_claims (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    claim_code TEXT UNIQUE NOT NULL,     -- CLM-timestamp
    claim_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    reason TEXT,                         -- Alasan (rusak/expired)
    proof_image TEXT,                    -- Foto bukti
    status TEXT DEFAULT 'PENDING',       -- PENDING, APPROVED, REJECTED
    salesman_id INTEGER NOT NULL,
    approved_by INTEGER,
    approved_date TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (salesman_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

**Purpose:** Track klaim badstock dari customer

---

#### 11. **badstock_inventory** - Gudang Badstock
```sql
CREATE TABLE badstock_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    qty INTEGER DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE(product_id)
);
```

**Purpose:** Stok barang rusak yang dikembalikan

---

### Indexes untuk Performance

```sql
-- Optimize queries
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_payments_customer ON payments(customer_id);
CREATE INDEX idx_schedules_salesman ON salesman_schedules(salesman_id, visit_date);
```

**Why Indexes?**
- Faster query untuk filter by customer
- Faster sort by status
- Improve JOIN performance

---

## 🔧 Instalasi Lengkap

### Step 1: Install SQLite3 Extension

**Arch Linux:**
```bash
./install_sqlite.sh
```

**Ubuntu/Debian:**
```bash
sudo apt install php-sqlite3 php-pdo-sqlite
sudo systemctl restart apache2
```

**Fedora/RHEL:**
```bash
sudo dnf install php-pdo php-sqlite3
sudo systemctl restart php-fpm
```

**macOS:**
```bash
brew install php
# SQLite3 sudah included
```

### Step 2: Setup Database

```bash
# Auto setup (recommended)
./setup.sh

# Manual
php config/setup.php
```

Script ini akan:
1. Membuat database SQLite
2. Create semua tabel
3. Migrate data dari JSON (jika ada)
4. Hash semua password

### Step 3: Start Server

```bash
# Development
php -S localhost:8000

# Production (dengan Apache/Nginx)
# Copy project ke /var/www/html/ atau document root
```

### Step 4: Test

```bash
# Test SQLite3
php -r "echo class_exists('SQLite3') ? '✅ OK' : '❌ Error';"

# Test database
sqlite3 database/distribusi.db "SELECT COUNT(*) FROM users;"
```

---

## 🐛 Troubleshooting

### Error: SQLite3 extension tidak aktif

**Solusi:**
```bash
# Check extension
php -m | grep -i sqlite

# Edit php.ini
sudo nano /etc/php/php.ini

# Uncomment atau tambahkan:
extension=sqlite3
extension=pdo_sqlite

# Restart PHP
sudo systemctl restart php-fpm
```

### Error: Database is locked

**Penyebab:** Multiple concurrent write

**Solusi:**
```bash
# Restart server
pkill -f "php -S"
php -S localhost:8000

# Atau increase timeout di database.php
$this->db->busyTimeout(10000); // 10 detik
```

### Error: Table not found

**Solusi:**
```bash
# Reset database
rm database/distribusi.db
php config/setup.php
```

### Error: Login failed (password salah)

**Penyebab:** Password belum di-hash

**Solusi:**
```bash
# Re-run migration
rm database/distribusi.db
php config/setup.php
```

Password tetap sama (123), tapi di database sudah di-hash.

### Error: Permission denied

**Solusi:**
```bash
# Fix permissions
chmod 755 database/
chmod 644 database/distribusi.db
chmod 755 uploads/
```

---

## 👨‍💻 Developer Guide

### Project Structure

```
project/
├── config/
│   ├── database.php       # Database connection class
│   ├── schema.sql         # Database schema
│   └── setup.php          # Migration script
├── includes/
│   └── functions.php      # Helper functions
├── database/
│   └── distribusi.db      # SQLite database
├── uploads/               # File uploads
├── old_version/           # Backup old files
├── data_backup/           # Backup JSON
│
├── login.php             # Authentication
├── logout.php            # Logout handler
├── index.php             # Dashboard
├── distribution.php      # Sales orders
├── finance.php           # Finance & payments
├── badstock.php          # Badstock management
├── schedule.php          # Salesman schedule
├── pajak.php             # Vehicle tax
├── settings.php          # Master data CRUD
├── profile.php           # User profile
│
├── print_invoice.php     # Print faktur
├── print_sj.php          # Print surat jalan
│
├── sidebar.php           # Navigation
├── style.css             # Stylesheet
│
├── setup.sh              # Auto-setup script
└── README.md             # This file
```

### Database Class Usage

```php
<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

// SELECT Query
$users = $db->query(
    "SELECT * FROM users WHERE role = :role", 
    ['role' => 'FAKTURIS']
);

foreach ($users as $user) {
    echo $user['fullname'];
}

// INSERT/UPDATE/DELETE
$db->execute(
    "UPDATE products SET stock = stock - :qty WHERE id = :id",
    ['qty' => 10, 'id' => 1]
);

// Transaction
$db->beginTransaction();
try {
    $db->execute("INSERT INTO orders ...");
    $db->execute("UPDATE customers ...");
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    echo "Error: " . $e->getMessage();
}

// Last Insert ID
$id = $db->lastInsertId();
```

### Helper Functions

```php
<?php
require_once __DIR__ . '/includes/functions.php';

// Security
$user = check_login();                    // Check session
$user = check_role(['AR_FINANCE']);       // Check role
$clean = clean_input($_POST['name']);     // Sanitize input

// Formatting
echo rupiah(1500000);                     // Rp 1.500.000
echo tanggal_indo('2026-01-16');         // 16 Januari 2026
echo status_badge('APPROVED');            // HTML badge

// UI
echo alert_message('Success!', 'success'); // Alert box

// File Upload
$result = upload_file('photo');
if ($result['success']) {
    echo $result['filename'];
}

// Pagination
$page = paginate(100, 20, 1); // total, per_page, current
// Returns: ['total_pages', 'current_page', 'offset', 'limit']
```

### Adding New Module

1. **Create PHP file** (e.g., `reports.php`)
2. **Include dependencies:**
   ```php
   <?php
   session_start();
   require_once __DIR__ . '/config/database.php';
   require_once __DIR__ . '/includes/functions.php';
   
   $user = check_login();
   $db = Database::getInstance();
   ```

3. **Add to sidebar.php:**
   ```php
   <li>
       <a href="reports.php">REPORTS</a>
   </li>
   ```

4. **Follow existing pattern** from other modules

### Database Migration

Jika ada perubahan schema:

```bash
# 1. Backup database
cp database/distribusi.db database/distribusi.db.backup

# 2. Edit schema.sql
nano config/schema.sql

# 3. Apply changes
sqlite3 database/distribusi.db < config/schema.sql
```

### Security Best Practices

1. **Always use prepared statements**
   ```php
   // ✅ GOOD
   $db->query("SELECT * FROM users WHERE id = :id", ['id' => $id]);
   
   // ❌ BAD
   $db->query("SELECT * FROM users WHERE id = $id");
   ```

2. **Sanitize all inputs**
   ```php
   $name = clean_input($_POST['name']);
   ```

3. **Check authentication**
   ```php
   $user = check_login();
   ```

4. **Check authorization**
   ```php
   $user = check_role(['AR_FINANCE', 'CASHIER']);
   ```

5. **Hash passwords**
   ```php
   $hash = password_hash($password, PASSWORD_DEFAULT);
   password_verify($password, $hash);
   ```

---

## 📊 Performance Tips

### Query Optimization

```php
// ✅ GOOD: Use indexes
$db->query("SELECT * FROM orders WHERE customer_id = :id", ['id' => 1]);

// ❌ BAD: Full table scan
$db->query("SELECT * FROM orders WHERE LOWER(status) = 'approved'");
```

### Caching

```php
// Simple file-based cache
function get_cached($key, $ttl = 3600) {
    $file = "cache/$key.json";
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function set_cache($key, $data) {
    file_put_contents("cache/$key.json", json_encode($data));
}
```

### Pagination

```php
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$orders = $db->query(
    "SELECT * FROM orders ORDER BY order_date DESC LIMIT :limit OFFSET :offset",
    ['limit' => $limit, 'offset' => $offset]
);
```

---

## 📄 License

Proprietary - Internal Use Only

---

## 📞 Support

Untuk pertanyaan atau bug report, hubungi developer.

---

## 🎯 Roadmap

### v2.1 (Next Release)
- [ ] Migrate semua modul ke SQLite
- [ ] CSRF token protection
- [ ] Data export (Excel/PDF)
- [ ] Responsive mobile UI

### v2.2 (Future)
- [ ] REST API endpoints
- [ ] Real-time notifications
- [ ] Advanced reporting
- [ ] Multi-warehouse support

### v3.0 (Long Term)
- [ ] Full MVC framework (Laravel)
- [ ] Mobile app (React Native)
- [ ] Cloud deployment
- [ ] Advanced analytics

---

**Last Updated:** January 16, 2026  
**Version:** 2.0.0 (SQLite)  
**PHP Version:** 8.5.1  
**Database:** SQLite 3

---

Made with ❤️ for better distribution management
