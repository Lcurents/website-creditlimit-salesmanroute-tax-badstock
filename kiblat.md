# Laporan Kerja Praktik - Sistem Penentuan Kredit Limit PT. Teguh Asia Mandiri

## Informasi Umum Proyek

**Judul:** Perancangan Sistem Penentuan Kredit Limit Berbasis Website

**Mahasiswa:** Laurentius Dika Andreano Vega (2213007)

**Institusi:** Program Studi Informatika, Fakultas Sains dan Teknologi, Universitas Katolik Musi Charitas

**Perusahaan:** PT. Teguh Asia Mandiri (Distributor Consumer Goods)

**Periode:** 18 Maret 2025 - 7 Mei 2025

---

## Latar Belakang Masalah

### Kondisi Existing
- PT. Teguh Asia Mandiri bergerak di bidang distribusi consumer goods (permen, snack, makanan ringan)
- Cakupan distribusi: Sumatera Selatan, Bengkulu, dan Jambi
- Memiliki 600+ pelanggan tetap (toko-toko dan mitra)
- Sudah memiliki website manajemen perusahaan untuk pencatatan kas dan keluar masuk barang

### Permasalahan Utama
1. **Pemberian kredit masih subjektif** - bergantung pada penilaian owner secara personal
2. **Tidak ada batasan kredit (Credit Limit)** yang jelas dan terukur
3. **Sering terjadi kredit macet** hingga pelanggan melarikan diri
4. **Data pelanggan tidak lengkap** - sulit mencari informasi riwayat pembelian
5. **Risiko piutang tak tertagih** tinggi karena tidak ada mekanisme preventif

### Dampak Masalah
- Kerugian finansial akibat kredit macet
- Pengendalian risiko piutang yang lemah
- Pencatatan transaksi tidak terintegrasi dengan stok gudang
- Proses pengambilan keputusan kredit lambat dan tidak akurat

---

## Tujuan Sistem

Merancang website manajemen perusahaan penentuan kredit limit untuk:

1. **Menstandarisasi prosedur penilaian kelayakan kredit** pelanggan
2. **Membuat proses pengambilan keputusan lebih cepat, akurat, dan objektif**
3. **Menggunakan metode Smart Scoring** untuk penentuan limit
4. **Menghilangkan unsur subjektivitas** dalam pemberian kredit
5. **Memberikan alat kontrol efektif** untuk menekan risiko piutang tak tertagih

---

## Manfaat Sistem

1. **Standardisasi operasional distribusi**
2. **Peningkatan akurasi data keuangan**
3. **Mekanisme kontrol preventif** terhadap kelebihan piutang sebelum transaksi terjadi
4. **Otomatisasi perhitungan limit kredit** berdasarkan parameter yang dibakukan
5. **Efisiensi proses** dengan menghilangkan kebutuhan approval manual setiap transaksi kredit

---

## Teknologi yang Digunakan

### Stack Teknologi
- **Backend Framework:** Laravel 12
- **Database:** MySQL
- **Frontend:** Blade Templates (Laravel), Tailwind CSS
- **Server:** Web Server (Apache/Nginx)
- **Arsitektur:** MVC (Model-View-Controller)

### Alasan Pemilihan
- Laravel 12: Cepat dibangun, hasil memuaskan, fitur lengkap
- MySQL: Reliable, scalable untuk data transaksi
- Blade + Tailwind: Rapid development untuk UI/UX

---

## Metodologi Pengembangan

### Model Waterfall
Dipilih karena:
- Persyaratan proyek sudah dipahami dengan baik
- Cukup stabil (adaptasi sistem existing)
- Cocok untuk sistem manajemen dengan requirement jelas

### Tahapan Waterfall

#### 1. Communication (Komunikasi)
- Observasi langsung proses bisnis
- Wawancara dengan Business Operation Manager
- Pengumpulan kebutuhan:
  - Alur pengajuan kredit
  - Parameter penilaian kelayakan pelanggan
  - Proses persetujuan kredit existing

#### 2. Planning (Perencanaan)
- Estimasi waktu pengerjaan proyek
- Penentuan tugas per fase
- Pengumpulan sumber daya

#### 3. Modeling (Pemodelan)
- **Analisis:** Identifikasi kebutuhan sistem
- **Desain:** 
  - UML Diagrams (Use Case, Activity, Class, Sequence)
  - UI/UX Design dengan Figma
  - Database Schema Design

#### 4. Construction (Konstruksi)
- Coding dengan Laravel 12
- Unit Testing & Integration Testing
- Bug Fixing
- **Catatan:** Tahap ini belum dilakukan (hanya perancangan)

#### 5. Deployment
- Implementasi di lingkungan kerja
- Pelatihan pengguna
- Pemeliharaan sistem
- **Catatan:** Menjadi saran untuk implementasi masa depan

---

## Aktor dan Peran Sistem

### 1. Fakturis (Sales Admin)
**Tanggung Jawab:**
- Input Sales Order (multi-item)
- Cetak Surat Jalan (SJ)
- Konfirmasi pengiriman barang
- Cetak Faktur Penagihan
- Monitoring pesanan

**Hak Akses:**
- Menu Distribusi
- Menu Monitoring Pesanan
- Dashboard (view only)

### 2. Finance (Account Receivable)
**Tanggung Jawab:**
- Approval pesanan yang Over Limit (Bypass)
- Input pelunasan piutang (metode FIFO)
- Registrasi pelanggan baru (Smart Scoring)
- Monitoring piutang & deadline
- Manajemen Credit Limit

**Hak Akses:**
- Menu Keuangan (full access)
- Menu Distribusi (approval only)
- Menu Settings (customer management)
- Dashboard Eksekutif

### 3. Admin Gudang
**Tanggung Jawab:**
- Manajemen stok barang
- Restock barang (input barang masuk)
- CRUD Master Data Produk
- Verifikasi penerimaan barang dari pabrik

**Hak Akses:**
- Menu Produk (full access)
- Menu Restock
- Dashboard (view only)

---

## Fitur Utama Sistem

### A. Smart Scoring (Penilaian Kredit Otomatis)

#### Konsep
Sistem penilaian objektif untuk menentukan Credit Limit pelanggan berdasarkan 4 kriteria terukur dengan pembobotan.

#### Kriteria Penilaian

| No | Kriteria | Bobot | Range Poin | Keterangan |
|----|----------|-------|------------|------------|
| 1 | Rata-rata Transaksi | 35% | 0-700 poin | Nilai rata-rata pembelian per transaksi |
| 2 | Riwayat Telat Bayar | 30% | 0-600 poin | Ketepatan pembayaran (semakin jarang telat = skor tinggi) |
| 3 | Frekuensi Belanja | 20% | 0-400 poin | Seberapa sering melakukan pemesanan |
| 4 | Lama Langganan | 15% | 0-300 poin | Loyalitas pelanggan (berapa lama jadi pelanggan) |
| **TOTAL** | | **100%** | **0-2000 poin** | |

#### Rumus Perhitungan
```
Total Score = (Rata-rata Transaksi × 0.35) + 
              (Riwayat Telat Bayar × 0.30) + 
              (Frekuensi Belanja × 0.20) + 
              (Lama Langganan × 0.15)
```

#### Klasifikasi Credit Limit Berdasarkan Skor

| Skor Total | Klasifikasi | Credit Limit |
|------------|-------------|--------------|
| 0-400 | Risiko Sangat Tinggi | Rp 0 - Rp 5.000.000 |
| 401-800 | Risiko Tinggi | Rp 5.000.000 - Rp 20.000.000 |
| 801-1200 | Risiko Sedang | Rp 20.000.000 - Rp 50.000.000 |
| 1201-1600 | Risiko Rendah | Rp 50.000.000 - Rp 75.000.000 |
| 1601-2000 | Pelanggan Premium | Rp 75.000.000 - Rp 100.000.000 |

#### Proses Smart Scoring

1. **Input Data Pelanggan Baru**
   - Admin/Finance mengisi form registrasi
   - Data: Nama Toko, Alamat
   - Kriteria: Rata-rata transaksi, riwayat bayar, frekuensi, loyalitas

2. **Perhitungan Otomatis**
   - Sistem mengalikan setiap kriteria dengan bobot
   - Menjumlahkan hasil perkalian
   - Mendapatkan Total Score (0-2000)

3. **Penetapan Limit**
   - Sistem menentukan Credit Limit otomatis berdasarkan skor
   - Data tersimpan di database

4. **Transparansi**
   - User dapat melihat breakdown perhitungan di Profil Toko
   - Menampilkan bobot dan nilai setiap kriteria

---

### B. Sales Order Management (Multi-Item)

#### Fitur Utama
- Input pesanan dengan **multiple items** dalam satu nota
- Validasi stok **real-time**
- Validasi Credit Limit **otomatis**
- Status pesanan dinamis (Approved/On Hold)

#### Alur Proses Input Sales Order

```
1. Fakturis pilih Pelanggan (dropdown)
   ↓
2. Sistem tampilkan:
   - Credit Limit pelanggan
   - Sisa limit tersedia
   - Status limit (Aman/Bahaya)
   ↓
3. Fakturis input barang:
   - Pilih Produk (dropdown)
   - Input Quantity
   - [Tombol] "Tambah Barang Lain" → untuk multi-item
   ↓
4. Sistem hitung Total Estimasi Harga otomatis
   ↓
5. Fakturis klik "Proses Order"
   ↓
6. Sistem Validasi:
   
   A. CEK STOK GUDANG
      - Jika Qty > Stok → ERROR "Stok tidak cukup"
      - Jika Stok cukup → Lanjut ke B
   
   B. CEK CREDIT LIMIT
      Total Belanja Baru + Hutang Lama vs Credit Limit
      
      - Jika (Total + Hutang) ≤ Limit:
        * Status: APPROVED
        * Sistem POTONG STOK otomatis
        * Sistem TAMBAH HUTANG pelanggan
        * Tombol "Cetak SJ" muncul
      
      - Jika (Total + Hutang) > Limit:
        * Status: ON HOLD
        * Stok TIDAK dipotong
        * Menunggu Approval Finance
        * Tombol "Cetak SJ" disabled
   ↓
7. Data pesanan tersimpan di database
```

#### Validasi Sistem

**Validasi Stok:**
```sql
SELECT stock FROM products WHERE id = ?
IF (requested_qty > stock) THEN
    RETURN ERROR "Stok tidak cukup"
END IF
```

**Validasi Credit Limit:**
```sql
-- Ambil data pelanggan
SELECT credit_limit, total_hutang FROM customers WHERE id = ?

-- Hitung total belanja sekarang
total_order = SUM(qty × harga)

-- Validasi
IF (total_order + total_hutang) > credit_limit THEN
    status = 'ON HOLD'
ELSE
    status = 'APPROVED'
    -- Potong stok
    UPDATE products SET stock = stock - qty WHERE id IN (...)
    -- Tambah hutang
    UPDATE customers SET total_hutang = total_hutang + total_order WHERE id = ?
END IF
```

---

### C. Approval System (Bypass Credit Limit)

#### Tujuan
Memberikan fleksibilitas kepada Finance untuk menyetujui pesanan yang melebihi limit setelah evaluasi manual.

#### Alur Proses Approval

```
1. Finance buka Menu Distribusi
   ↓
2. Sistem tampilkan daftar pesanan:
   - Pesanan ON HOLD ditandai WARNA MERAH
   - Info: Nama pelanggan, total order, alasan hold
   ↓
3. Finance klik tombol "APPROVE (BYPASS)" pada pesanan
   ↓
4. Sistem konfirmasi: "Yakin approve pesanan ini?"
   ↓
5. Finance konfirmasi YA
   ↓
6. Sistem proses:
   a. UPDATE status order → 'APPROVED'
   b. POTONG stok barang fisik
   c. TAMBAH hutang pelanggan
   ↓
7. Pesanan berubah warna jadi HIJAU
   Tombol "Cetak SJ" aktif
```

#### Database Operation
```sql
-- Update status
UPDATE orders SET status = 'APPROVED', approved_by = ?, approved_at = NOW()
WHERE id = ? AND status = 'ON HOLD'

-- Potong stok (untuk setiap item)
UPDATE products p
INNER JOIN order_items oi ON p.id = oi.product_id
SET p.stock = p.stock - oi.qty
WHERE oi.order_id = ?

-- Tambah hutang
UPDATE customers 
SET total_hutang = total_hutang + (SELECT total FROM orders WHERE id = ?)
WHERE id = ?
```

#### Skenario Alternatif

**Tolak Pesanan:**
- Finance biarkan status tetap ON HOLD
- Atau klik tombol "Reject/Cancel" (jika tersedia)
- Pesanan dibatalkan sepenuhnya

**Stok Habis Saat Approval:**
```sql
-- Cek stok sebelum approval
SELECT stock FROM products WHERE id IN (
    SELECT product_id FROM order_items WHERE order_id = ?
)

IF (ANY stock < required_qty) THEN
    RETURN ERROR "Stok Gudang Habis, tidak dapat approve"
END IF
```

---

### D. FIFO Payment Allocation (Pelunasan Piutang)

#### Konsep FIFO (First-In First-Out)
Pembayaran dari pelanggan dialokasikan ke **faktur terlama terlebih dahulu** secara otomatis.

#### Tujuan
- Menjaga akurasi umur piutang (aging schedule)
- Memprioritaskan pelunasan tagihan lama
- Mencegah faktur lama mengendap

#### Alur Proses Pelunasan

```
1. Finance terima pembayaran dari pelanggan (cash/transfer)
   ↓
2. Finance buka Menu Keuangan → Input Pelunasan
   ↓
3. Finance input:
   - Pilih Nama Pelanggan (dropdown)
   - Masukkan Nominal Pembayaran (Rp)
   ↓
4. Finance klik "Proses Pelunasan"
   ↓
5. Sistem proses:
   
   a. KURANGI total hutang pelanggan
      UPDATE customers 
      SET total_hutang = total_hutang - [nominal_bayar]
      WHERE id = ?
   
   b. AMBIL faktur yang belum lunas (status DELIVERED)
      SELECT * FROM orders 
      WHERE customer_id = ? AND status = 'DELIVERED'
      ORDER BY created_at ASC  -- PENTING: Urutkan dari TERLAMA
   
   c. ALGORITMA FIFO:
      
      sisa_uang = nominal_bayar
      
      FOREACH faktur IN daftar_faktur:
          IF sisa_uang >= faktur.total THEN
              -- Lunas penuh
              UPDATE orders SET status = 'PAID' WHERE id = faktur.id
              sisa_uang = sisa_uang - faktur.total
          ELSE IF sisa_uang > 0 THEN
              -- Bayar sebagian (jika sistem support partial payment)
              UPDATE orders SET paid_amount = sisa_uang WHERE id = faktur.id
              sisa_uang = 0
              BREAK
          ELSE
              BREAK  -- Uang habis
          END IF
      END FOREACH
   
   d. Jika masih ada sisa_uang:
      -- Simpan sebagai deposit/kredit (tergantung kebijakan)
      INSERT INTO deposits (customer_id, amount) VALUES (?, sisa_uang)
   ↓
6. Sistem tampilkan:
   - Notifikasi sukses
   - Sisa hutang terbaru
   - List faktur yang terbayar
```

#### Pseudocode FIFO Algorithm

```php
function processFIFOPayment($customer_id, $payment_amount) {
    // 1. Update total hutang
    DB::table('customers')
        ->where('id', $customer_id)
        ->decrement('total_hutang', $payment_amount);
    
    // 2. Ambil faktur belum lunas (DELIVERED) urutkan terlama
    $unpaid_invoices = DB::table('orders')
        ->where('customer_id', $customer_id)
        ->where('status', 'DELIVERED')
        ->orderBy('created_at', 'ASC')
        ->get();
    
    // 3. Alokasi pembayaran
    $remaining_payment = $payment_amount;
    
    foreach ($unpaid_invoices as $invoice) {
        if ($remaining_payment <= 0) break;
        
        if ($remaining_payment >= $invoice->total) {
            // Lunas penuh
            DB::table('orders')
                ->where('id', $invoice->id)
                ->update(['status' => 'PAID', 'paid_at' => now()]);
            
            $remaining_payment -= $invoice->total;
        } else {
            // Bayar sebagian (optional feature)
            DB::table('orders')
                ->where('id', $invoice->id)
                ->update(['paid_amount' => $remaining_payment]);
            
            $remaining_payment = 0;
        }
    }
    
    // 4. Handle sisa uang (jika ada)
    if ($remaining_payment > 0) {
        // Simpan sebagai deposit atau return
        DB::table('customer_deposits')->insert([
            'customer_id' => $customer_id,
            'amount' => $remaining_payment,
            'created_at' => now()
        ]);
    }
    
    return [
        'success' => true,
        'invoices_paid' => count($unpaid_invoices),
        'remaining_debt' => DB::table('customers')
            ->where('id', $customer_id)
            ->value('total_hutang')
    ];
}
```

#### Contoh Kasus FIFO

**Data Awal:**
- Pelanggan: Toko Maju Jaya
- Total Hutang: Rp 15.000.000

**Faktur Belum Lunas (DELIVERED):**
| No Faktur | Tanggal | Total | Status |
|-----------|---------|-------|--------|
| INV-001 | 2025-01-05 | Rp 5.000.000 | DELIVERED |
| INV-003 | 2025-01-12 | Rp 7.000.000 | DELIVERED |
| INV-005 | 2025-01-20 | Rp 3.000.000 | DELIVERED |

**Pelanggan Bayar: Rp 10.000.000**

**Proses FIFO:**
```
1. Alokasi ke INV-001 (terlama):
   Rp 10.000.000 >= Rp 5.000.000 → LUNAS
   Status INV-001 = PAID
   Sisa uang = Rp 5.000.000

2. Alokasi ke INV-003 (terlama kedua):
   Rp 5.000.000 < Rp 7.000.000 → BAYAR SEBAGIAN
   Status INV-003 = PARTIALLY PAID (paid_amount = Rp 5.000.000)
   Sisa uang = Rp 0

3. INV-005 belum terbayar (masih DELIVERED)
```

**Hasil Akhir:**
- Total Hutang: Rp 15.000.000 - Rp 10.000.000 = **Rp 5.000.000**
- Credit Limit pulih sebesar Rp 10.000.000

---

### E. Logistic Management (Surat Jalan & Faktur)

#### Dokumen yang Dihasilkan

**1. Surat Jalan (SJ)**
- **Fungsi:** Dokumen pengiriman barang (tanpa harga)
- **Dicetak:** Setelah pesanan APPROVED
- **Isi:** Nama toko, alamat, daftar barang (tanpa harga), qty
- **Ditandatangani:** Supir dan penerima barang

**2. Faktur Penagihan (Invoice)**
- **Fungsi:** Dokumen tagihan resmi (dengan harga)
- **Dicetak:** Setelah barang DELIVERED
- **Isi:** Semua info SJ + harga satuan + total + termin bayar
- **Diserahkan:** Ke tim penagihan

#### Alur Proses Logistik

```
1. Pesanan sudah APPROVED
   ↓
2. Fakturis klik "Cetak SJ"
   ↓
3. Sistem generate PDF Surat Jalan:
   
   SURAT JALAN NO: SJ-2025-001
   Tanggal: 16 Januari 2025
   Kepada: Toko Maju Jaya
   Alamat: Jl. Sudirman No. 123
   
   Daftar Barang:
   1. Permen Hacks Mint       - 50 pcs
   2. Biskuit Go Oriorio      - 100 pcs
   3. Kacang Jaipong Polong   - 75 pcs
   
   Supir: ___________  Penerima: ___________
   ↓
4. SJ dicetak dan dibawa supir
   ↓
5. Supir kirim barang → Pelanggan terima → TTD SJ
   ↓
6. Supir kembali dengan SJ bertanda tangan
   ↓
7. Fakturis input ke sistem:
   - Cari pesanan
   - Klik "Konfirmasi Sampai"
   ↓
8. Sistem update:
   UPDATE orders SET status = 'DELIVERED', delivered_at = NOW()
   WHERE id = ?
   ↓
9. Tombol "Cetak Faktur" muncul
   ↓
10. Fakturis cetak Faktur Penagihan:
    
    FAKTUR NO: INV-2025-001
    Tanggal: 16 Januari 2025
    Kepada: Toko Maju Jaya
    
    No  Barang                 Qty    Harga         Total
    1.  Permen Hacks Mint      50     Rp 2.000      Rp 100.000
    2.  Biskuit Go Oriorio     100    Rp 5.000      Rp 500.000
    3.  Kacang Jaipong         75     Rp 3.000      Rp 225.000
    
    TOTAL: Rp 825.000
    Termin: 30 hari
    Jatuh Tempo: 15 Februari 2025
    ↓
11. Faktur diserahkan ke Finance untuk penagihan
```

#### Status Pesanan Lifecycle

```
1. APPROVED     → Pesanan disetujui, stok terpotong
2. DELIVERED    → Barang diterima pelanggan (piutang resmi diakui)
3. PAID         → Faktur lunas terbayar (via FIFO)
```

---

### F. Stock Management & Restock

#### Fungsi
- Manajemen master data produk (CRUD)
- Input barang masuk (Restock)
- Monitoring stok real-time

#### Alur Restock Barang

```
1. Barang fisik datang dari pabrik/pemasok
   ↓
2. Admin Gudang verifikasi:
   - Cek kualitas barang
   - Cocokkan dengan Purchase Order
   ↓
3. Admin Gudang buka Menu Produk
   ↓
4. Tabel produk tampil:
   
   | Nama Barang | Harga Jual | Stok Saat Ini | Restock | Aksi |
   |-------------|------------|---------------|---------|------|
   | Permen Hacks| Rp 2.000   | 150           | [___]   | [+]  |
   | Biskuit Go  | Rp 5.000   | 80            | [___]   | [+]  |
   ↓
5. Admin input jumlah barang masuk pada kolom "Restock"
   Contoh: 200 (untuk Permen Hacks)
   ↓
6. Admin klik tombol [+]
   ↓
7. Sistem proses:
   
   stok_baru = stok_lama + qty_restock
   
   UPDATE products 
   SET stock = stock + [qty_restock],
       last_restock_at = NOW()
   WHERE id = ?
   ↓
8. Sistem tampilkan notifikasi: "Stok berhasil ditambahkan"
   ↓
9. Tabel update otomatis:
   
   | Nama Barang | Harga Jual | Stok Saat Ini | Restock | Aksi |
   |-------------|------------|---------------|---------|------|
   | Permen Hacks| Rp 2.000   | 350 ← UPDATE | [___]   | [+]  |
```

#### CRUD Master Data Produk

**Create (Tambah Produk Baru):**
```sql
INSERT INTO products (name, price, stock, created_at)
VALUES ('Permen Collins Mint', 2500, 100, NOW())
```

**Read (Lihat Daftar Produk):**
```sql
SELECT id, name, price, stock, last_restock_at
FROM products
ORDER BY name ASC
```

**Update (Edit Harga/Info Produk):**
```sql
UPDATE products
SET price = ?, name = ?
WHERE id = ?
```

**Delete (Hapus Produk):**
```sql
-- Soft delete (recommended)
UPDATE products SET deleted_at = NOW() WHERE id = ?

-- Hard delete (not recommended)
DELETE FROM products WHERE id = ?
```

---

### G. Dashboard & Monitoring

#### Dashboard Eksekutif

**Fitur:**
- Real-time business performance indicators
- Visual scorecards
- Recent activity log

**Konten Dashboard:**

**1. KPI Cards (Scorecard)**

```
┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
│  TOTAL OMSET HARI INI│  │ PESANAN OVER LIMIT  │  │   TOTAL PIUTANG     │
│                      │  │                      │  │                      │
│   Rp 25.000.000      │  │      12 Order       │  │   Rp 150.000.000    │
│                      │  │                      │  │                      │
│   ▲ +15% vs kemarin  │  │   ⚠ Perlu Approval  │  │  📊 Aging Schedule  │
└─────────────────────┘  └─────────────────────┘  └─────────────────────┘
    WARNA: HIJAU              WARNA: MERAH             WARNA: KUNING
```

**2. Aktivitas Terakhir (5 Transaksi Terbaru)**

| Waktu | Pelanggan | Aktivitas | Status | Total |
|-------|-----------|-----------|--------|-------|
| 14:30 | Toko Maju | Order Baru | APPROVED | Rp 2.500.000 |
| 14:15 | Toko Jaya | Pembayaran | PAID | Rp 5.000.000 |
| 13:50 | Warung Berkah | Order Baru | ON HOLD | Rp 8.000.000 |
| 13:20 | Toko Sentosa | Konfirmasi Terima | DELIVERED | Rp 3.200.000 |
| 13:00 | Toko Rezeki | Order Baru | APPROVED | Rp 1.800.000 |

**Query untuk KPI:**

```sql
-- Total Omset Hari Ini
SELECT SUM(total) as total_omset
FROM orders
WHERE DATE(created_at) = CURDATE()
AND status IN ('APPROVED', 'DELIVERED', 'PAID')

-- Pesanan Over Limit (ON HOLD)
SELECT COUNT(*) as count_hold
FROM orders
WHERE status = 'ON HOLD'

-- Total Piutang (Semua Pelanggan)
SELECT SUM(total_hutang) as total_piutang
FROM customers
```

#### Monitoring Piutang & Deadline

**Tujuan:**
- Memantau tagihan yang belum lunas
- Identifikasi piutang overdue
- Aging schedule analysis

**Tampilan:**

```
MONITORING PIUTANG - AGING SCHEDULE

Filter: [Semua Pelanggan ▼] [Semua Status ▼] [Cari...]

| Pelanggan | Total Hutang | 0-30 Hari | 31-60 Hari | 61-90 Hari | >90 Hari | Status |
|-----------|--------------|-----------|------------|------------|----------|--------|
| Toko Maju | Rp 8.500.000 | Rp 3.0jt  | Rp 5.5jt   | Rp 0       | Rp 0     | 🟡 Warning |
| Toko Jaya | Rp 2.000.000 | Rp 2.0jt  | Rp 0       | Rp 0       | Rp 0     | 🟢 Aman |
| Warung XYZ| Rp 12.000.000| Rp 1.0jt  | R 3.0jt    | Rp 5.0jt   | Rp 3.0jt | 🔴 OVERDUE |
```

**Kode Warna:**
- 🟢 Aman: Semua piutang < 30 hari
- 🟡 Warning: Ada piutang 31-60 hari
- 🔴 Overdue: Ada piutang > 60 hari

**Query Aging Schedule:**

```sql
SELECT 
    c.name as customer_name,
    c.total_hutang,
    SUM(CASE WHEN DATEDIFF(NOW(), o.created_at) <= 30 THEN o.total ELSE 0 END) as age_0_30,
    SUM(CASE WHEN DATEDIFF(NOW(), o.created_at) BETWEEN 31 AND 60 THEN o.total ELSE 0 END) as age_31_60,
    SUM(CASE WHEN DATEDIFF(NOW(), o.created_at) BETWEEN 61 AND 90 THEN o.total ELSE 0 END) as age_61_90,
    SUM(CASE WHEN DATEDIFF(NOW(), o.created_at) > 90 THEN o.total ELSE 0 END) as age_over_90
FROM customers c
LEFT JOIN orders o ON c.id = o.customer_id AND o.status = 'DELIVERED'
GROUP BY c.id, c.name, c.total_hutang
ORDER BY c.total_hutang DESC
```

---

## Database Schema

### Tabel Utama

#### 1. users
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- hashed
    role ENUM('fakturis', 'finance', 'gudang') NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. customers
```sql
CREATE TABLE customers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    
    -- Smart Scoring Fields
    avg_transaction DECIMAL(15,2) DEFAULT 0,
    late_payment_count INT DEFAULT 0,
    purchase_frequency INT DEFAULT 0,
    membership_months INT DEFAULT 0,
    
    total_score INT DEFAULT 0, -- 0-2000
    credit_limit DECIMAL(15,2) DEFAULT 0, -- Auto calculated
    
    -- Financial
    total_hutang DECIMAL(15,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 3. products
```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    last_restock_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL -- Soft delete
);
```

#### 4. orders
```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL, -- INV-2025-001
    customer_id BIGINT NOT NULL,
    
    total DECIMAL(15,2) NOT NULL,
    status ENUM('APPROVED', 'ON HOLD', 'DELIVERED', 'PAID') DEFAULT 'APPROVED',
    
    -- Approval
    approved_by BIGINT NULL, -- user_id Finance
    approved_at TIMESTAMP NULL,
    
    -- Logistic
    delivered_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

#### 5. order_items
```sql
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10,2) NOT NULL, -- Harga saat transaksi
    subtotal DECIMAL(15,2) NOT NULL, -- qty × price
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### 6. payments
```sql
CREATE TABLE payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    processed_by BIGINT NOT NULL, -- user_id Finance
    
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);
```

---

## Use Case Scenarios (Detail)

### Use Case 1: Input Sales Order (Multi-Item)

**Aktor:** Fakturis

**Precondition:**
- Fakturis sudah login
- Pelanggan sudah terdaftar di sistem
- Produk tersedia di master data

**Main Flow:**
1. Fakturis membuka menu "Distribusi Sales"
2. Fakturis memilih nama Pelanggan dari dropdown
3. Sistem menampilkan:
   - Credit Limit pelanggan
   - Total hutang saat ini
   - Sisa limit tersedia
   - Status (Aman/Bahaya)
4. Fakturis memilih Produk dari dropdown
5. Fakturis memasukkan Quantity
6. Sistem menampilkan subtotal = qty × harga
7. (Opsional) Fakturis klik "Tambah Barang Lain" untuk item kedua, ketiga, dst
8. Sistem menghitung Total Estimasi secara real-time
9. Fakturis klik "Proses Order"
10. Sistem validasi:
    - **Cek Stok:** Jika ada item qty > stok → Error
    - **Cek Limit:** Jika (total + hutang lama) > limit → Status ON HOLD
11. Jika validasi OK:
    - Status = APPROVED
    - Stok terpotong otomatis
    - Hutang bertambah
    - Tombol "Cetak SJ" muncul
12. Pesanan tersimpan di database

**Alternative Flow:**

**A1: Stok Tidak Cukup**
- Di step 10, jika qty > stok
- Sistem tampilkan: "Stok [Nama Produk] tidak mencukupi. Tersedia: [X], Diminta: [Y]"
- Fakturis adjust qty atau hapus item
- Kembali ke step 9

**A2: Over Limit**
- Di step 10, jika total + hutang > limit
- Sistem simpan dengan status ON HOLD
- Stok TIDAK dipotong
- Sistem kirim notifikasi ke Finance
- Tombol "Cetak SJ" disabled
- Finance harus approve manual

**A3: Data Kosong**
- Di step 9, jika belum pilih produk
- Sistem tampilkan: "Pilih minimal 1 produk"
- Kembali ke step 4

**Postcondition:**
- Pesanan tercatat dengan status yang sesuai
- Jika APPROVED: stok berkurang, hutang bertambah
- Jika ON HOLD: menunggu approval

---

### Use Case 2: Approval Order (Bypass Limit)

**Aktor:** Finance

**Precondition:**
- Finance sudah login
- Ada pesanan dengan status ON HOLD

**Main Flow:**
1. Finance membuka menu "Distribusi Sales"
2. Sistem menampilkan daftar pesanan
3. Pesanan ON HOLD ditandai warna MERAH
4. Finance melihat detail pesanan:
   - Nama pelanggan
   - Total order
   - Credit limit pelanggan
   - Alasan hold (over limit)
5. Finance mengevaluasi risiko
6. Finance klik "APPROVE (BYPASS)"
7. Sistem konfirmasi: "Yakin menyetujui pesanan ini?"
8. Finance klik "Ya"
9. Sistem proses:
   - Update status → APPROVED
   - Potong stok barang
   - Tambah hutang pelanggan
10. Warna pesanan berubah jadi HIJAU
11. Tombol "Cetak SJ" aktif

**Alternative Flow:**

**A1: Tolak Pesanan**
- Di step 6, Finance klik "Reject" (jika ada)
- Sistem update status → CANCELLED
- Stok tetap tidak berubah
- Notifikasi ke Fakturis

**A2: Stok Habis Saat Approval**
- Di step 9, saat validasi stok
- Jika ada item yang stoknya habis (diambil order lain)
- Sistem tampilkan: "Gagal approve. Stok [Produk] habis."
- Status tetap ON HOLD
- Finance harus koordinasi dengan Gudang

**Postcondition:**
- Status berubah menjadi APPROVED
- Stok berkurang
- Pesanan siap dikirim

---

### Use Case 3: Input Pelunasan (Metode FIFO)

**Aktor:** Finance

**Precondition:**
- Finance sudah login
- Pelanggan memiliki hutang (faktur DELIVERED)
- Pelanggan melakukan pembayaran

**Main Flow:**
1. Finance membuka menu "Keuangan"
2. Finance klik "Input Pelunasan"
3. Finance memilih Nama Pelanggan
4. Sistem menampilkan:
   - Total hutang pelanggan
   - Daftar faktur belum lunas (DELIVERED)
5. Finance memasukkan Nominal Pembayaran (Rp)
6. Finance klik "Proses Pelunasan"
7. Sistem validasi: nominal > 0
8. Sistem proses:
   - Kurangi total_hutang pelanggan
   - Ambil faktur DELIVERED, urutkan by created_at ASC
   - Loop FIFO:
     ```
     FOR EACH faktur IN faktur_list:
         IF sisa_uang >= faktur.total:
             faktur.status = PAID
             sisa_uang -= faktur.total
         ELSE IF sisa_uang > 0:
             faktur.paid_amount = sisa_uang
             sisa_uang = 0
             BREAK
     ```
9. Sistem simpan perubahan
10. Sistem tampilkan:
    - "Pelunasan berhasil"
    - Daftar faktur yang terbayar
    - Sisa hutang terbaru

**Alternative Flow:**

**A1: Nominal Invalid**
- Di step 7, jika nominal ≤ 0
- Sistem tampilkan: "Masukkan nominal valid (> 0)"
- Kembali ke step 5

**A2: Uang Berlebih**
- Di step 8, setelah loop FIFO
- Jika sisa_uang > 0 (semua faktur lunas)
- Sistem tampilkan konfirmasi:
  "Pembayaran melebihi hutang. Sisa Rp [X]. Simpan sebagai deposit?"
- Finance pilih: Ya → Simpan deposit / Tidak → Kembalikan uang

**Postcondition:**
- Total hutang berkurang
- Faktur lama berubah status PAID
- Credit limit pelanggan pulih

---

### Use Case 4: Tambah Pelanggan (Smart Scoring)

**Aktor:** Finance / Admin

**Precondition:**
- Admin sudah login
- Data calon pelanggan tersedia

**Main Flow:**
1. Admin membuka menu "Settings"
2. Admin klik "Tambah Pelanggan"
3. Admin mengisi form:
   - **Data Identitas:**
     - Nama Toko
     - Alamat
     - No. Telepon
   - **Kriteria Penilaian:**
     - Rata-rata Transaksi (dropdown: 0-700 poin)
     - Riwayat Telat Bayar (dropdown: 0-600 poin)
     - Frekuensi Belanja (dropdown: 0-400 poin)
     - Lama Langganan (dropdown: 0-300 poin)
4. Admin klik "Hitung Skor & Simpan"
5. Sistem proses:
   ```
   total_score = (avg_trans × 0.35) + 
                 (late_pay × 0.30) + 
                 (frequency × 0.20) + 
                 (membership × 0.15)
   ```
6. Sistem tentukan Credit Limit:
   ```
   IF total_score <= 400:       limit = 0 - 5jt
   ELSE IF total_score <= 800:  limit = 5jt - 20jt
   ELSE IF total_score <= 1200: limit = 20jt - 50jt
   ELSE IF total_score <= 1600: limit = 50jt - 75jt
   ELSE:                        limit = 75jt - 100jt
   ```
7. Sistem simpan data pelanggan
8. Sistem tampilkan:
   - "Pelanggan berhasil ditambahkan"
   - Total Score: [X] poin
   - Credit Limit: Rp [Y]

**Alternative Flow:**

**A1: Data Tidak Lengkap**
- Di step 4, jika ada field kosong
- Sistem tampilkan: "Lengkapi semua data"
- Kembali ke step 3

**Postcondition:**
- Pelanggan terdaftar dengan Credit Limit otomatis
- Data tersimpan di tabel customers

---

### Use Case 5: Logistik (Kirim & Konfirmasi)

**Aktor:** Fakturis

**Precondition:**
- Pesanan sudah APPROVED
- Barang siap kirim

**Main Flow:**
1. Fakturis membuka menu "Distribusi Sales"
2. Fakturis cari pesanan yang APPROVED
3. Fakturis klik "Cetak SJ"
4. Sistem generate PDF Surat Jalan:
   - Header: PT. Teguh Asia Mandiri
   - No. SJ, Tanggal
   - Data Toko: Nama, Alamat
   - Daftar barang (TANPA HARGA)
   - TTD Supir & Penerima
5. Fakturis cetak SJ
6. SJ diserahkan ke supir
7. Supir kirim barang ke toko
8. Toko terima barang → TTD SJ
9. Supir kembali dengan SJ bertanda tangan
10. Fakturis klik "Konfirmasi Sampai" pada pesanan
11. Sistem update:
    - status = DELIVERED
    - delivered_at = NOW()
12. Tombol "Cetak Faktur" muncul
13. Fakturis klik "Cetak Faktur"
14. Sistem generate PDF Faktur Penagihan:
    - Semua info SJ + HARGA
    - Total, Termin bayar, Jatuh tempo
15. Faktur diserahkan ke Finance

**Alternative Flow:**

**A1: Barang Tidak Sampai**
- Di step 10, jika ada kendala
- Fakturis tidak konfirmasi
- Status tetap APPROVED
- Koordinasi dengan logistik

**Postcondition:**
- Status = DELIVERED
- Piutang resmi tercatat
- Faktur terbit untuk penagihan

---

## Activity Diagram Details

### 1. Login Process

```
START
  ↓
User Input Username & Password
  ↓
Auth Controller Process
  ↓
Open DB Connection
  ↓
SELECT User FROM users WHERE username = ?
  ↓
[User Found?] → NO → Display "Login Gagal" → END
  ↓ YES
Verify Password Hash
  ↓
[Password Match?] → NO → Display "Login Gagal" → END
  ↓ YES
Create Session (session_start)
  ↓
Detect User Role (fakturis/finance/gudang)
  ↓
Close DB Connection
  ↓
Redirect to Dashboard
  ↓
END
```

### 2. Sales Order & Approval Process

```
START (Fakturis)
  ↓
Input Customer & Products
  ↓
Calculate Total
  ↓
Submit Order
  ↓
System Validate Stock
  ↓
[Stock Available?] → NO → ERROR "Stok tidak cukup" → END
  ↓ YES
System Check Credit Limit
  ↓
Calculate: total_order + existing_debt
  ↓
[Exceeds Limit?]
  ↓ YES → Status = ON HOLD
  |         ↓
  |       Wait for Finance Approval
  |         ↓
  |       [Finance Approve?]
  |         ↓ YES → Continue to next step
  |         ↓ NO → Status = REJECTED → END
  ↓ NO
Status = APPROVED
  ↓
Cut Stock (products.stock -= qty)
  ↓
Add Debt (customers.total_hutang += total)
  ↓
Save Order to DB
  ↓
Enable "Cetak SJ" Button
  ↓
END
```

### 3. FIFO Payment Process

```
START (Finance)
  ↓
Select Customer
  ↓
Input Payment Amount
  ↓
Submit Payment
  ↓
System: UPDATE customers SET total_hutang -= amount
  ↓
System: SELECT orders WHERE customer_id = ? AND status = DELIVERED
         ORDER BY created_at ASC
  ↓
Initialize: remaining_payment = amount
  ↓
FOR EACH invoice IN invoice_list:
  ↓
  [remaining_payment > 0?] → NO → BREAK LOOP
  ↓ YES
  [remaining_payment >= invoice.total?]
    ↓ YES
    UPDATE orders SET status = PAID WHERE id = invoice.id
    remaining_payment -= invoice.total
    ↓
  [remaining_payment < invoice.total BUT > 0?]
    ↓ YES
    UPDATE orders SET paid_amount = remaining_payment WHERE id = invoice.id
    remaining_payment = 0
    BREAK LOOP
  ↓
END LOOP
  ↓
[remaining_payment > 0?]
  ↓ YES → Save as Deposit
  ↓ NO
Display Success + Updated Debt
  ↓
END
```

### 4. Smart Scoring Process

```
START (Admin)
  ↓
Input Customer Data:
  - Name, Address, Phone
  - avg_transaction
  - late_payment_count
  - purchase_frequency
  - membership_months
  ↓
Click "Hitung Skor & Simpan"
  ↓
System Calculate:
  score_1 = avg_transaction × 0.35
  score_2 = (600 - late_payment_count×10) × 0.30
  score_3 = purchase_frequency × 0.20
  score_4 = membership_months × 0.15
  ↓
  total_score = score_1 + score_2 + score_3 + score_4
  ↓
System Determine Credit Limit:
  IF total_score <= 400:       credit_limit = RANDOM(0, 5000000)
  ELSE IF total_score <= 800:  credit_limit = RANDOM(5000000, 20000000)
  ELSE IF total_score <= 1200: credit_limit = RANDOM(20000000, 50000000)
  ELSE IF total_score <= 1600: credit_limit = RANDOM(50000000, 75000000)
  ELSE:                        credit_limit = RANDOM(75000000, 100000000)
  ↓
INSERT INTO customers (name, address, ..., total_score, credit_limit)
  ↓
Display: "Pelanggan berhasil ditambahkan"
         "Score: [total_score]"
         "Limit: Rp [credit_limit]"
  ↓
END
```

### 5. Stock Restock Process

```
START (Admin Gudang)
  ↓
Receive Physical Goods from Supplier
  ↓
Verify Quality & Quantity
  ↓
Open Menu "Produk"
  ↓
Find Product in Table
  ↓
Input Restock Quantity
  ↓
Click [+] Button
  ↓
System:
  Open DB Connection
  ↓
  SELECT stock FROM products WHERE id = ?
  ↓
  new_stock = current_stock + restock_qty
  ↓
  UPDATE products 
  SET stock = new_stock, last_restock_at = NOW()
  WHERE id = ?
  ↓
  Close DB Connection
  ↓
Display: "Stok berhasil ditambahkan"
  ↓
Update Table Display (show new stock)
  ↓
END
```

### 6. Logout Process

```
START
  ↓
User Click "Logout" Button
  ↓
System Identify Active Session
  ↓
System Clear Session Data (session_destroy)
  ↓
System Redirect to Login Page
  ↓
END
```

---

## Sequence Diagram Details

### 1. Login Sequence

```
User → LoginPage: Input credentials
LoginPage → AuthController: POST /login
AuthController → Database: SELECT * FROM users WHERE username = ?
Database → AuthController: User data
AuthController → AuthController: Verify password hash
AuthController → Session: Create session (user_id, role)
Session → AuthController: Session created
AuthController → Dashboard: Redirect (based on role)
Dashboard → User: Display dashboard
```

### 2. Sales Order Sequence

```
Fakturis → OrderForm: Input customer & products
OrderForm → OrderController: POST /orders/create
OrderController → Database: SELECT stock FROM products WHERE id IN (...)
Database → OrderController: Stock data
OrderController → Database: SELECT credit_limit, total_hutang FROM customers WHERE id = ?
Database → OrderController: Customer data
OrderController → OrderController: Validate stock & limit
OrderController → Database: IF over_limit THEN status=ON_HOLD ELSE status=APPROVED
OrderController → Database: IF approved THEN UPDATE products SET stock -= qty
OrderController → Database: IF approved THEN UPDATE customers SET total_hutang += total
OrderController → Database: INSERT INTO orders (...)
OrderController → Database: INSERT INTO order_items (...)
Database → OrderController: Order saved
OrderController → OrderForm: Return order_id & status
OrderForm → Fakturis: Display success + status
```

### 3. FIFO Payment Sequence

```
Finance → PaymentForm: Select customer & input amount
PaymentForm → FinanceController: POST /payments/create
FinanceController → Database: UPDATE customers SET total_hutang -= amount WHERE id = ?
FinanceController → Database: SELECT * FROM orders WHERE customer_id = ? AND status = DELIVERED ORDER BY created_at ASC
Database → FinanceController: Unpaid invoices (sorted oldest first)
FinanceController → FinanceController: FIFO allocation loop
FinanceController → Database: UPDATE orders SET status = PAID WHERE id IN (...)
FinanceController → Database: INSERT INTO payments (customer_id, amount, ...)
Database → FinanceController: Payment recorded
FinanceController → PaymentForm: Return updated debt & paid invoices
PaymentForm → Finance: Display success + summary
```

### 4. Smart Scoring Sequence

```
Admin → CustomerForm: Input customer data & criteria
CustomerForm → CustomerController: POST /customers/create
CustomerController → CustomerController: Calculate total_score (weighted sum)
CustomerController → CustomerController: Determine credit_limit based on score
CustomerController → Database: INSERT INTO customers (name, ..., total_score, credit_limit)
Database → CustomerController: Customer created (id)
CustomerController → CustomerForm: Return customer_id, score, limit
CustomerForm → Admin: Display "Customer added" + score + limit
```

### 5. Restock Sequence

```
Admin Gudang → ProductTable: Input restock qty
ProductTable → ProductController: POST /products/{id}/restock
ProductController → Database: SELECT stock FROM products WHERE id = ?
Database → ProductController: Current stock
ProductController → ProductController: new_stock = current_stock + restock_qty
ProductController → Database: UPDATE products SET stock = new_stock, last_restock_at = NOW() WHERE id = ?
Database → ProductController: Stock updated
ProductController → ProductTable: Return new stock value
ProductTable → Admin Gudang: Display "Stok berhasil ditambahkan" + updated stock
```

### 6. Logout Sequence

```
User → Dashboard: Click "Logout"
Dashboard → AuthController: GET /logout
AuthController → Session: session_destroy()
Session → AuthController: Session destroyed
AuthController → LoginPage: Redirect /login
LoginPage → User: Display login form
```

---

## Class Diagram Relationships

### User Class
```php
class User {
    - id: bigint
    - username: string
    - password: string (hashed)
    - role: enum (fakturis, finance, gudang)
    - name: string
    
    + login(): bool
    + logout(): void
    + hasRole(role): bool
}
```

### Customer Class
```php
class Customer {
    - id: bigint
    - name: string
    - address: string
    - phone: string
    
    // Smart Scoring
    - avg_transaction: decimal
    - late_payment_count: int
    - purchase_frequency: int
    - membership_months: int
    - total_score: int (0-2000)
    - credit_limit: decimal (auto-calculated)
    
    // Financial
    - total_hutang: decimal
    
    + calculateScore(): int
    + determineCreditLimit(): decimal
    + getRemainingLimit(): decimal
    + canPlaceOrder(amount): bool
}

Relationships:
- Customer 1 ---- * Order
- Customer 1 ---- * Payment
```

### Product Class
```php
class Product {
    - id: bigint
    - name: string
    - price: decimal
    - stock: int
    - last_restock_at: datetime
    
    + restock(qty): void
    + reduceStock(qty): bool
    + isAvailable(qty): bool
}

Relationships:
- Product 1 ---- * OrderItem
```

### Order Class
```php
class Order {
    - id: bigint
    - order_number: string (INV-2025-XXX)
    - customer_id: bigint
    - total: decimal
    - status: enum (APPROVED, ON HOLD, DELIVERED, PAID)
    - approved_by: bigint (user_id)
    - approved_at: datetime
    - delivered_at: datetime
    - paid_at: datetime
    
    + approve(user_id): void
    + reject(): void
    + confirmDelivery(): void
    + markAsPaid(): void
    + canPrint SJ(): bool
    + canPrintInvoice(): bool
}

Relationships:
- Order * ---- 1 Customer
- Order 1 ---- * OrderItem
- Order * ---- 0..1 User (approver)
```

### OrderItem Class
```php
class OrderItem {
    - id: bigint
    - order_id: bigint
    - product_id: bigint
    - qty: int
    - price: decimal (harga saat transaksi)
    - subtotal: decimal (qty × price)
    
    + calculateSubtotal(): decimal
}

Relationships:
- OrderItem * ---- 1 Order (composition)
- OrderItem * ---- 1 Product
```

### Payment Class
```php
class Payment {
    - id: bigint
    - customer_id: bigint
    - amount: decimal
    - payment_date: datetime
    - notes: string
    - processed_by: bigint (user_id)
    
    + allocateFIFO(): array
}

Relationships:
- Payment * ---- 1 Customer
- Payment * ---- 1 User (processor)
```

---

## UI/UX Design Specifications

### 1. Login Page
**Layout:**
- Centered login box (400px width)
- Background: Neutral gray (#F5F5F5)
- Login box: White with shadow

**Elements:**
- Logo PT. Teguh Asia Mandiri (top)
- Form fields:
  - Username (text input)
  - Password (password input)
- Button "MASUK" (primary blue)
- Demo credentials info (bottom)

**Validation:**
- Required fields
- Min length: username (3), password (6)
- Error messages inline

---

### 2. Dashboard
**Layout:**
- Sidebar navigation (left, 250px)
- Main content area (right, fluid)
- Header with user info & logout

**KPI Cards (Top):**
```
Row 1: 3 Cards (Equal width, gap 20px)
├─ Card 1: Total Omset Hari Ini
│  Color: Green (#10B981)
│  Icon: 💰
│  Value: Rp 25.000.000
│  Trend: ▲ +15%
│
├─ Card 2: Pesanan Over Limit
│  Color: Red (#EF4444)
│  Icon: ⚠️
│  Value: 12 Order
│  Action: "Lihat Detail"
│
└─ Card 3: Total Piutang
   Color: Yellow (#F59E0B)
   Icon: 📊
   Value: Rp 150.000.000
   Link: "Aging Schedule"
```

**Recent Activity Table:**
- 5 rows max
- Columns: Waktu, Pelanggan, Aktivitas, Status, Total
- Auto-refresh every 30s

---

### 3. Distribusi Sales (Input Order)
**Layout:**
- Form container (max-width 800px)
- Sticky header with customer info

**Form Sections:**

**Section 1: Customer Selection**
```
Pilih Pelanggan: [Dropdown - searchable]
Credit Limit: Rp 50.000.000
Hutang Saat Ini: Rp 20.000.000
Sisa Limit: Rp 30.000.000 [Badge: Aman/Bahaya]
```

**Section 2: Product Items (Repeatable)**
```
┌─────────────────────────────────────────────────┐
│ Item #1                                   [×]   │
│ Produk: [Dropdown]                              │
│ Qty: [Number] × Rp 5.000 = Rp 50.000          │
└─────────────────────────────────────────────────┘
[+ Tambah Barang Lain]

Total Estimasi: Rp 50.000
```

**Section 3: Actions**
```
[Batal]  [Proses Order]
```

**Validation:**
- Real-time stock check on product select
- Limit warning when approaching limit
- Submit disabled if validation fails

---

### 4. Monitoring Pesanan
**Layout:**
- Filter bar (top)
- Data table (main)
- Pagination (bottom)

**Table Columns:**
| Status | No. Order | Pelanggan | Barang | Total | Tanggal | Aksi|
|--------|-----------|-----------|--------|-------|---------|------|

**Status Colors:**
- APPROVED: Blue
- ON HOLD: Red
- DELIVERED: Yellow
- PAID: Green

**Action Buttons (Conditional):**
- APPROVED: [Cetak SJ] [Konfirmasi Sampai]
- ON HOLD: [Approve] [Reject] (Finance only)
- DELIVERED: [Cetak Faktur]
- PAID: [Lihat Detail]

---

### 5. Tambah Pelanggan (Smart Scoring)
**Layout:**
- Two-column form
- Left: Basic Info
- Right: Scoring Criteria

**Form Structure:**
```
┌─────────────────────┬─────────────────────────┐
│ Data Toko           │ Profil Penilaian Kredit │
│                     │                         │
│ Nama: [____]        │ Rata-rata Transaksi:    │
│ Alamat: [____]      │ [Dropdown 0-700]        │
│ Telp: [____]        │                         │
│                     │ Riwayat Telat Bayar:    │
│                     │ [Dropdown 0-600]        │
│                     │                         │
│                     │ Frekuensi Belanja:      │
│                     │ [Dropdown 0-400]        │
│                     │                         │
│                     │ Lama Langganan:         │
│                     │ [Dropdown 0-300]        │
└─────────────────────┴─────────────────────────┘

[Hitung Skor & Simpan Data]
```

**Result Display (After Save):**
```
✓ Pelanggan berhasil ditambahkan!

Total Score: 1450 poin
Credit Limit: Rp 60.000.000

[Lihat Profil] [Tambah Lagi]
```

---

### 6. Profil Toko (Smart Scoring Detail)
**Layout:**
- Header: Customer name & basic info
- Score summary card
- Breakdown table
- Transaction history

**Score Card:**
```
┌──────────────────────────────────┐
│ CREDIT SCORE                     │
│ 1450 / 2000 poin                 │
│                                  │
│ CREDIT LIMIT                     │
│ Rp 60.000.000                    │
│                                  │
│ Klasifikasi: Risiko Rendah      │
└──────────────────────────────────┘
```

**Breakdown Table:**
```
Transparansi Perhitungan Skor

Kriteria            | Nilai | Bobot | Skor
--------------------|-------|-------|-------
Rata-rata Transaksi | 600   | 35%   | 210
Riwayat Telat Bayar | 500   | 30%   | 150
Frekuensi Belanja   | 350   | 20%   | 70
Lama Langganan      | 200   | 15%   | 30
--------------------|-------|-------|-------
TOTAL SCORE         |       |       | 1450
```

**Real-time Data:**
```
Data Transaksi Real-time

Total Transaksi: 85 kali
Rata-rata per Transaksi: Rp 2.500.000
Total Belanja: Rp 212.500.000
Keterlambatan: 2 kali (2.35%)
Member Sejak: 15 bulan yang lalu
```

---

### 7. Restock Barang
**Layout:**
- Product table with inline edit
- Search & filter bar (top)

**Table Structure:**
```
Cari: [_______] [Filter: Semua Kategori ▼]

Nama Barang       | Harga Jual | Stok | Restock | Aksi
------------------|------------|------|---------|------
Permen Hacks Mint | Rp 2.000   | 150  | [___]   | [+]
Biskuit Go Oriorio| Rp 5.000   | 80   | [___]   | [+]
Kacang Jaipong    | Rp 3.000   | 200  | [___]   | [+]
```

**Interaction:**
1. Admin input qty di kolom "Restock"
2. Klik tombol [+]
3. AJAX update stock
4. Cell "Stok" animasi update (highlight green)
5. Toast notification: "Stok berhasil ditambahkan"

---

### 8. Monitoring Piutang
**Layout:**
- Filter toolbar
- Aging schedule table
- Export button

**Filter:**
```
[Pelanggan ▼] [Status ▼] [Periode ▼] [Cari...] [Export Excel]
```

**Aging Table:**
```
Pelanggan    | Total    | 0-30  | 31-60 | 61-90 | >90   | Status
-------------|----------|-------|-------|-------|-------|--------
Toko Maju    | 8.5 jt   | 3 jt  | 5.5jt | 0     | 0     | 🟡 Warning
Toko Jaya    | 2 jt     | 2 jt  | 0     | 0     | 0     | 🟢 Aman
Warung XYZ   | 12 jt    | 1 jt  | 3 jt  | 5 jt  | 3 jt  | 🔴 OVERDUE
```

**Color Coding:**
- Green row: All debt < 30 days
- Yellow row: Has 31-60 days debt
- Red row: Has >60 days debt

---

## Business Rules & Validations

### 1. Credit Limit Rules

**Rule 1: Order Validation**
```
IF (order_total + customer.total_hutang) > customer.credit_limit THEN
    order.status = 'ON HOLD'
    NOTIFY Finance for approval
ELSE
    order.status = 'APPROVED'
    DEDUCT stock
    ADD debt
END IF
```

**Rule 2: Limit Recovery**
```
WHEN payment processed:
    customer.total_hutang -= payment.amount
    // Credit limit automatically available again
```

**Rule 3: Zero Limit Policy**
```
IF customer.credit_limit = 0 THEN
    ONLY allow Cash On Delivery (COD)
    OR Prepaid orders
END IF
```

### 2. Stock Rules

**Rule 1: Real-time Validation**
```
BEFORE order submission:
    FOR EACH item IN order_items:
        IF item.qty > product.stock THEN
            REJECT order
            SHOW "Stok [product.name] tidak cukup"
        END IF
    END FOR
```

**Rule 2: Stock Reservation**
```
WHEN order status = 'ON HOLD':
    DO NOT deduct stock
    // Stock stays available for other orders
    
WHEN order status changes to 'APPROVED':
    DEDUCT stock atomically
```

**Rule 3: Negative Stock Prevention**
```
UPDATE products 
SET stock = stock - ?
WHERE id = ? AND stock >= ?
// Use WHERE condition to prevent negative values
```

### 3. FIFO Payment Rules

**Rule 1: Oldest First**
```
ORDER BY created_at ASC
// MUST sort by date ascending
```

**Rule 2: Full Payment Priority**
```
IF payment >= invoice.total THEN
    invoice.status = 'PAID'
    payment -= invoice.total
    CONTINUE to next invoice
ELSE
    invoice.paid_amount += payment
    payment = 0
    STOP allocation
END IF
```

**Rule 3: Overpayment Handling**
```
IF payment > total_hutang THEN
    Pay all invoices
    remainder = payment - total_hutang
    
    OPTIONS:
    a) Save as customer deposit
    b) Return to customer
    c) Apply to next order
END IF
```

### 4. Smart Scoring Rules

**Rule 1: Score Calculation**
```
total_score = (criterion_1 × 0.35) +
              (criterion_2 × 0.30) +
              (criterion_3 × 0.20) +
              (criterion_4 × 0.15)

WHERE SUM(weights) = 1.00 (100%)
```

**Rule 2: Limit Determination**
```
CASE
    WHEN score <= 400:  RETURN RANDOM(0, 5000000)
    WHEN score <= 800:  RETURN RANDOM(5000000, 20000000)
    WHEN score <= 1200: RETURN RANDOM(20000000, 50000000)
    WHEN score <= 1600: RETURN RANDOM(50000000, 75000000)
    ELSE:               RETURN RANDOM(75000000, 100000000)
END CASE
```

**Rule 3: Dynamic Adjustment**
```
// Future enhancement
WHEN customer behavior changes:
    Recalculate score quarterly
    Adjust limit automatically
```

### 5. Status Transition Rules

**Order Status Flow:**
```
[NEW] 
  ↓
[APPROVED] ← Manual approval from ON HOLD
  ↓
[DELIVERED] ← Confirmation after delivery
  ↓
[PAID] ← FIFO payment allocation
```

**Allowed Transitions:**
```
NEW → APPROVED (automatic if within limit)
NEW → ON HOLD (automatic if over limit)
ON HOLD → APPROVED (manual by Finance)
ON HOLD → REJECTED (manual by Finance)
APPROVED → DELIVERED (by Fakturis)
DELIVERED → PAID (by FIFO algorithm)
```

**Forbidden Transitions:**
```
PAID → any other status (final state)
DELIVERED → APPROVED (backward not allowed)
```

---

## Testing Scenarios

### 1. Smart Scoring Test Cases

**Test Case 1: Low Score Customer**
```
Input:
  avg_transaction = 100 (out of 700)
  late_payment = 50 (out of 600)
  frequency = 80 (out of 400)
  membership = 40 (out of 300)

Expected:
  total_score = 100×0.35 + 50×0.30 + 80×0.20 + 40×0.15
              = 35 + 15 + 16 + 6
              = 72 poin
  
  credit_limit = Rp 0 - Rp 5.000.000 (Risiko Sangat Tinggi)
```

**Test Case 2: High Score Customer**
```
Input:
  avg_transaction = 700
  late_payment = 600
  frequency = 400
  membership = 300

Expected:
  total_score = 700×0.35 + 600×0.30 + 400×0.20 + 300×0.15
              = 245 + 180 + 80 + 45
              = 550 poin (ERROR! Max should be 2000)

// Note: This reveals a scoring system issue
// Correct implementation should normalize inputs
```

**Corrected Scoring:**
```
// Criteria should be normalized to 0-100 scale first
normalized_avg = (actual_avg / max_avg) × 100
criterion_score = (normalized_value / 100) × max_points

Example:
avg_transaction_score = (2000000 / 5000000) × 700 = 280 poin
```

### 2. Order Validation Test Cases

**Test Case 1: Stock Sufficient, Limit OK**
```
Given:
  Product A stock = 100
  Order qty = 50
  Customer limit = Rp 10.000.000
  Customer debt = Rp 2.000.000
  Order total = Rp 3.000.000

Expected:
  Validation passes
  Status = APPROVED
  Stock after = 50
  Debt after = Rp 5.000.000
```

**Test Case 2: Stock Insufficient**
```
Given:
  Product A stock = 30
  Order qty = 50

Expected:
  Validation fails
  Error: "Stok tidak cukup"
  Status = N/A (order not created)
  Stock unchanged
```

**Test Case 3: Over Limit**
```
Given:
  Customer limit = Rp 10.000.000
  Customer debt = Rp 8.000.000
  Order total = Rp 5.000.000
  (8M + 5M = 13M > 10M limit)

Expected:
  Status = ON HOLD
  Stock unchanged
  Debt unchanged
  Notify Finance
```

### 3. FIFO Payment Test Cases

**Test Case 1: Partial Payment**
```
Given:
  Invoice 1: Rp 5.000.000 (2025-01-01) DELIVERED
  Invoice 2: Rp 3.000.000 (2025-01-05) DELIVERED
  Invoice 3: Rp 2.000.000 (2025-01-10) DELIVERED
  Payment: Rp 7.000.000

Expected:
  Invoice 1: PAID (paid Rp 5.000.000)
  Invoice 2: PAID (paid Rp 2.000.000)
  Invoice 3: DELIVERED (unpaid)
  Remaining payment: Rp 0
  Total debt: Rp 2.000.000
```

**Test Case 2: Overpayment**
```
Given:
  Total debt: Rp 10.000.000
  Payment: Rp 12.000.000

Expected:
  All invoices: PAID
  Remaining: Rp 2.000.000
  Action: Save as deposit OR return
  Total debt: Rp 0
```

**Test Case 3: FIFO Order Validation**
```
Given:
  Invoice A: 2025-01-15 (newer)
  Invoice B: 2025-01-10 (older)
  Invoice C: 2025-01-12 (middle)

Expected Processing Order:
  1st: Invoice B (oldest)
  2nd: Invoice C
  3rd: Invoice A
```

### 4. Approval Test Cases

**Test Case 1: Finance Approve ON HOLD**
```
Given:
  Order status = ON HOLD
  Product stock = 100
  Order qty = 50

Action:
  Finance clicks APPROVE

Expected:
  Status = APPROVED
  Stock = 50
  Debt += order total
  SJ printable = true
```

**Test Case 2: Stock Depleted During Hold**
```
Given:
  Order A status = ON HOLD (qty = 100)
  Order B created (qty = 100, APPROVED)
  Product stock = 100

Action:
  Order B processed → stock = 0
  Finance tries to approve Order A

Expected:
  Approval FAILS
  Error: "Stok habis"
  Status remains ON HOLD
```

---

## API Endpoints (Laravel Routes)

```php
// Authentication
POST   /login
GET    /logout

// Dashboard
GET    /dashboard

// Orders
GET    /orders                    // List all orders
POST   /orders                    // Create new order
GET    /orders/{id}               // View order detail
PUT    /orders/{id}/approve       // Approve ON HOLD order
PUT    /orders/{id}/deliver       // Mark as delivered
GET    /orders/{id}/print-sj      // Generate SJ PDF
GET    /orders/{id}/print-invoice // Generate Invoice PDF

// Customers
GET    /customers                 // List customers
POST   /customers                 // Create customer (Smart Scoring)
GET    /customers/{id}            // View customer profile
PUT    /customers/{id}            // Update customer
GET    /customers/{id}/debt       // View debt details

// Products
GET    /products                  // List products
POST   /products                  // Create product
PUT    /products/{id}             // Update product
DELETE /products/{id}             // Delete product
POST   /products/{id}/restock     // Add stock

// Payments
POST   /payments                  // Process payment (FIFO)
GET    /payments/history          // Payment history

// Reports
GET    /reports/aging-schedule    // Piutang aging
GET    /reports/sales-summary     // Sales dashboard
GET    /reports/customer-analysis // Customer behavior
```

---

## Kesimpulan & Rekomendasi Implementasi

### Kesimpulan Perancangan

1. **Standardisasi Tercapai**
   - Smart Scoring menghilangkan subjektivitas
   - Kriteria terukur dengan bobot jelas
   - Limit otomatis berdasarkan skor

2. **Mitigasi Risiko Efektif**
   - Validasi real-time sebelum transaksi
   - Status ON HOLD untuk over-limit
   - Approval berjenjang oleh Finance

3. **Efisiensi Keuangan**
   - FIFO otomatis untuk aging control
   - Monitoring piutang terstruktur
   - Dashboard real-time untuk decision making

### Rekomendasi Implementasi

#### Fase 1: Core System (Priority High)
1. **Authentication & Authorization**
   - Login system dengan role-based access
   - Session management

2. **Master Data**
   - CRUD Products
   - CRUD Customers (dengan Smart Scoring)

3. **Sales Order Flow**
   - Multi-item order input
   - Stock & limit validation
   - Status management (APPROVED/ON HOLD)

#### Fase 2: Financial System (Priority High)
1. **Approval Mechanism**
   - Finance approval untuk ON HOLD orders
   - Stock deduction trigger

2. **FIFO Payment**
   - Payment allocation algorithm
   - Debt tracking

3. **Logistic Documents**
   - SJ PDF generation
   - Invoice PDF generation

#### Fase 3: Monitoring & Reporting (Priority Medium)
1. **Dashboard KPI**
   - Real-time metrics
   - Activity log

2. **Aging Schedule**
   - Debt analysis by period
   - Overdue alerts

3. **Customer Profiling**
   - Score breakdown transparency
   - Transaction history

#### Fase 4: Enhancement (Priority Low)
1. **Automated Alerts**
   - Email/SMS untuk jatuh tempo
   - Low stock notification

2. **Advanced Analytics**
   - Sales trend analysis
   - Customer segmentation

3. **Mobile App**
   - Sales force mobile access
   - Real-time order tracking

### Testing Strategy

1. **Unit Testing**
   - Smart Scoring calculation
   - FIFO algorithm
   - Stock validation

2. **Integration Testing**
   - Order → Stock → Debt flow
   - Payment → FIFO → Status update

3. **User Acceptance Testing**
   - Fakturis workflow
   - Finance approval process
   - Gudang restock

4. **Performance Testing**
   - Database query optimization
   - Concurrent order handling

---

## Prompt untuk Validasi Proyek

```
Saya telah mengembangkan Sistem Penentuan Kredit Limit untuk PT. Teguh Asia Mandiri berdasarkan laporan kerja praktik. Mohon validasi apakah implementasi saya sudah sesuai dengan spesifikasi berikut:

**A. SMART SCORING SYSTEM**
1. Apakah perhitungan skor menggunakan 4 kriteria dengan bobot: 35%, 30%, 20%, 15%?
2. Apakah range skor 0-2000 poin sudah benar?
3. Apakah penetapan Credit Limit otomatis berdasarkan klasifikasi skor?
4. Apakah ada tampilan transparansi breakdown perhitungan di profil pelanggan?

**B. SALES ORDER MANAGEMENT**
1. Apakah support multi-item dalam satu order?
2. Apakah ada validasi stok real-time sebelum submit?
3. Apakah validasi limit: (total order + hutang lama) vs credit limit?
4. Apakah status otomatis ON HOLD jika over limit?
5. Apakah stok HANYA dipotong saat status APPROVED (bukan ON HOLD)?

**C. FIFO PAYMENT ALLOCATION**
1. Apakah pelunasan mengalokasikan ke faktur terlama terlebih dahulu?
2. Apakah query ORDER BY created_at ASC?
3. Apakah status faktur berubah PAID setelah lunas?
4. Apakah total_hutang pelanggan berkurang otomatis?

**D. APPROVAL SYSTEM**
1. Apakah Finance bisa approve order ON HOLD?
2. Apakah saat approve: stok dipotong DAN hutang ditambah?
3. Apakah ada validasi stok ulang saat approve (mencegah stok habis)?

**E. LOGISTIC FLOW**
1. Apakah Surat Jalan (SJ) tanpa harga, hanya daftar barang?
2. Apakah Faktur dengan harga muncul setelah status DELIVERED?
3. Apakah lifecycle status: APPROVED → DELIVERED → PAID benar?

**F. DATABASE STRUCTURE**
1. Apakah tabel customers punya field: total_score, credit_limit, total_hutang?
2. Apakah tabel orders punya field: status, approved_by, delivered_at, paid_at?
3. Apakah tabel order_items terpisah (relasi 1-to-many) untuk multi-item?

**G. ROLE-BASED ACCESS**
1. Fakturis: Input order, cetak SJ/Faktur, konfirmasi delivered
2. Finance: Approve order, input payment, registrasi customer
3. Gudang: Restock, CRUD produk

Tolong periksa kode saya dan beri feedback apakah sudah sesuai requirement di atas.
```

---

**END OF DOCUMENTATION**