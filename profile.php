<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

require_once __DIR__ . '/config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) header("Location: settings.php");

// Load dari SQLite
$db = Database::getInstance();
$customers = $db->query("SELECT * FROM customers");
$orders = $db->query("SELECT * FROM orders");

// Cari Customer
$key = array_search($id, array_column($customers, 'id'));
$c = $customers[$key];

// ==========================================
// 1. DATA MINING (MENGGALI DATA DARI HISTORY)
// ==========================================

$myOrders = [];
$totalBelanja = 0;
$countTelat = 0;
$transaksiPertama = $c['join_date'] ?? date('Y-m-d'); // Fallback jika data lama gak ada join_date

foreach ($orders as $o) {
    if ($o['customer_id'] == $id && $o['status'] != 'ON HOLD') {
        $myOrders[] = $o;
        $totalBelanja += $o['total'];
        
        // Logika Cek Telat (Jika sudah status delivered tapi tanggal sekarang melewati due_date dan belum lunas)
        // Catatan: Ini simulasi sederhana. Idealnya ada paid_date yang valid.
        if ($o['status'] == 'DELIVERED') {
            $today = date('Y-m-d');
            if ($today > $o['due_date']) { // LOGIKA TELAT
                 // Disini kita anggap telat jika status masih DELIVERED (Belum PAID/LUNAS) lewat tanggal
                 // Atau kita bisa tambah field 'is_late' di order history nantinya
                 $countTelat++;
            }
        }
    }
}

// ==========================================
// 2. HITUNG VARIABEL RUMUS (AUTO CALCULATE)
// ==========================================

// A. Rata-rata Transaksi
$jumlahTransaksi = count($myOrders);
$rataRata = $jumlahTransaksi > 0 ? ($totalBelanja / $jumlahTransaksi) : 0;
$rataJuta = $rataRata / 1000000; // Konversi ke Juta

// B. Lama Menjadi Pelanggan
$date1 = new DateTime($transaksiPertama);
$date2 = new DateTime();
$interval = $date1->diff($date2);
$lamaTahun = $interval->y + ($interval->m / 12); // Tahun + Bulan (desimal)

// C. Frekuensi (Transaksi per Bulan)
// Jika baru 1 bulan, pembaginya 1. Jika 0 bulan, pembaginya 1 (biar gak error division by zero)
$bulanAktif = ($lamaTahun * 12) < 1 ? 1 : ($lamaTahun * 12);
$freqBulanan = $jumlahTransaksi / $bulanAktif;

// D. Riwayat Telat
// Menggunakan variabel $countTelat dari loop di atas

// ==========================================
// 3. LOGIKA SKORING (SAMA SEPERTI JURNAL)
// ==========================================

// 1. RATA-RATA (10%)
if ($rataJuta > 100) $s1 = 20; elseif ($rataJuta >= 50) $s1 = 15; elseif ($rataJuta >= 25) $s1 = 10; else $s1 = 5;
$n1 = $s1 * 10;

// 2. KETERLAMBATAN (30%)
if ($countTelat == 0) $s2 = 20; elseif ($countTelat < 5) $s2 = 15; elseif ($countTelat < 15) $s2 = 10; else $s2 = 5;
$n2 = $s2 * 30;

// 3. FREKUENSI (20%)
if ($freqBulanan > 10) $s3 = 20; elseif ($freqBulanan >= 5) $s3 = 15; elseif ($freqBulanan >= 2) $s3 = 10; else $s3 = 5;
$n3 = $s3 * 20;

// 4. LAMA PELANGGAN (40%)
if ($lamaTahun > 10) $s4 = 20; elseif ($lamaTahun >= 5) $s4 = 15; elseif ($lamaTahun >= 1) $s4 = 10; else $s4 = 5;
$n4 = $s4 * 40;

$totalScore = $n1 + $n2 + $n3 + $n4;

// TENTUKAN LIMIT
if ($totalScore < 500) $limitBaru = 0;
elseif ($totalScore < 1000) $limitBaru = 25000000;
elseif ($totalScore < 1500) $limitBaru = 50000000;
else $limitBaru = 100000000;

// ==========================================
// 4. UPDATE OTOMATIS KE DATABASE SQLite
// ==========================================
// Update limit otomatis jika berubah
if ($c['credit_limit'] != $limitBaru) {
    $db->execute(
        "UPDATE customers SET credit_limit = :limit, total_score = :score WHERE id = :id",
        [
            'limit' => $limitBaru,
            'score' => $totalScore,
            'id' => $id
        ]
    );
    $c['credit_limit'] = $limitBaru; // Update variabel tampilan
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Toko - <?= $c['name'] ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .score-box { display: flex; gap: 20px; margin-bottom: 20px; }
        .score-card { flex: 1; border: 1px solid #333; padding: 15px; text-align: center; }
        .score-val { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .formula-table { width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 20px; }
        .formula-table th, .formula-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .formula-table th { background: #eee; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <a href="settings.php">&laquo; Kembali ke Settings</a>
        
        <div class="header-box" style="margin-top: 10px;">
            <h1>Profil Toko: <?= $c['name'] ?></h1>
            <p>Bergabung sejak: <?= $c['join_date'] ?? '-' ?> (<?= number_format($lamaTahun, 1) ?> Tahun)</p>
        </div>

        <div style="background: #333; color: #fff; padding: 20px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3>CREDIT LIMIT SAAT INI (AUTO)</h3>
                <h1 style="font-size: 48px; margin: 0;">Rp <?= number_format($c['limit']) ?></h1>
            </div>
            <div style="text-align: right;">
                <h3>TOTAL SKOR</h3>
                <h1 style="font-size: 48px; margin: 0; color: yellow;"><?= $totalScore ?></h1>
                <small>/ 2000 Poin</small>
            </div>
        </div>

        <h3>Data Perilaku Transaksi (Real-time Analysis)</h3>
        <div class="score-box">
            <div class="score-card">
                <small>Rata-rata Transaksi</small>
                <div class="score-val">Rp <?= number_format($rataRata) ?></div>
                <small>Total: Rp <?= number_format($totalBelanja) ?></small>
            </div>
            <div class="score-card">
                <small>Frekuensi Belanja</small>
                <div class="score-val"><?= number_format($freqBulanan, 1) ?>x</div>
                <small>per Bulan</small>
            </div>
            <div class="score-card" style="background: <?= $countTelat > 0 ? '#ffcccc' : '#ccffcc' ?>">
                <small>Riwayat Telat Bayar</small>
                <div class="score-val"><?= $countTelat ?>x</div>
                <small>Melewati Deadline (7 Hari)</small>
            </div>
            <div class="score-card">
                <small>Loyalitas</small>
                <div class="score-val"><?= number_format($lamaTahun, 1) ?> Thn</div>
                <small>Sejak <?= $c['join_date'] ?? 'N/A' ?></small>
            </div>
        </div>

        <h3>Transparansi Perhitungan Skor</h3>
        <p>Limit kredit dihitung menggunakan rumus pembobotan otomatis:</p>
        <table class="formula-table">
            <thead>
                <tr>
                    <th>Kriteria</th>
                    <th>Data Toko</th>
                    <th>Skor (0-20)</th>
                    <th>Bobot</th>
                    <th>Total Poin</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:left;">1. Rata-rata Transaksi</td>
                    <td>Rp <?= number_format($rataRata) ?></td>
                    <td><?= $s1 ?></td>
                    <td>10 %</td>
                    <td><b><?= $n1 ?></b></td>
                </tr>
                <tr>
                    <td style="text-align:left;">2. Keterlambatan Pembayaran</td>
                    <td><?= $countTelat ?> kali</td>
                    <td><?= $s2 ?></td>
                    <td>30 %</td>
                    <td><b><?= $n2 ?></b></td>
                </tr>
                <tr>
                    <td style="text-align:left;">3. Frekuensi per Bulan</td>
                    <td><?= number_format($freqBulanan, 1) ?> kali</td>
                    <td><?= $s3 ?></td>
                    <td>20 %</td>
                    <td><b><?= $n3 ?></b></td>
                </tr>
                <tr>
                    <td style="text-align:left;">4. Lama Menjadi Pelanggan</td>
                    <td><?= number_format($lamaTahun, 1) ?> tahun</td>
                    <td><?= $s4 ?></td>
                    <td>40 %</td>
                    <td><b><?= $n4 ?></b></td>
                </tr>
                <tr style="background: #333; color: #fff;">
                    <td colspan="4" style="text-align:right; font-weight:bold;">TOTAL SCORE FINAL</td>
                    <td style="font-weight:bold; font-size: 18px;"><?= $totalScore ?></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; border: 1px dashed #333; padding: 10px;">
            <b>Legenda Limit:</b><br>
            < 500 Poin = Rp 0 (Tunai)<br>
            500 - 1000 Poin = Rp 25 Juta<br>
            1000 - 1500 Poin = Rp 50 Juta<br>
            > 1500 Poin = Rp 100 Juta
        </div>

    </div>
</body>
</html>