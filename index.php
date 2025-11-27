<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$user = $_SESSION['user'];

// LOAD SEMUA DATA
$orders = json_decode(file_get_contents('data/orders.json'), true);
$customers = json_decode(file_get_contents('data/customers.json'), true);

// VARIABEL DASHBOARD
$today = date("Y-m-d");
$omsetHariIni = 0;
$orderHariIni = 0;
$pendingCount = 0;
$totalPiutang = 0;

// LOGIC HITUNG ORDER
foreach ($orders as $o) {
    // Cek Omset Hari Ini (Berdasarkan tanggal)
    if (strpos($o['date'], $today) === 0) {
        $orderHariIni++; // Hitung jumlah transaksi
        if ($o['status'] !== 'ON HOLD') {
            $omsetHariIni += $o['total']; // Hitung duit (kecuali yang hold)
        }
    }
    // Cek Pending Approval
    if ($o['status'] === 'ON HOLD') {
        $pendingCount++;
    }
}

// LOGIC HITUNG PIUTANG (Uang macet di luar)
foreach ($customers as $c) {
    $totalPiutang += $c['debt'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Distribusi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Dashboard Eksekutif</h1>
            <p>Ringkasan performa bisnis per <b><?= date("d F Y") ?></b></p>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div class="card" style="flex: 1; text-align: center; border-left: 5px solid green;">
                <h3>Omset Hari Ini</h3>
                <h1 style="font-size: 36px; margin: 10px 0; color: green;">
                    Rp <?= number_format($omsetHariIni) ?>
                </h1>
                <p><?= $orderHariIni ?> Transaksi Masuk</p>
            </div>

            <div class="card" style="flex: 1; text-align: center; border-left: 5px solid red;">
                <h3>Butuh Approval</h3>
                <h1 style="font-size: 36px; margin: 10px 0; color: <?= $pendingCount > 0 ? 'red' : '#333' ?>;">
                    <?= $pendingCount ?>
                </h1>
                <p>Order Over Limit</p>
            </div>

            <div class="card" style="flex: 1; text-align: center; border-left: 5px solid orange;">
                <h3>Total Piutang Toko</h3>
                <h1 style="font-size: 36px; margin: 10px 0; color: orange;">
                    Rp <?= number_format($totalPiutang) ?>
                </h1>
                <p>Uang di Pelanggan</p>
            </div>
        </div>

        <div class="card">
            <h3>5 Aktivitas Order Terakhir</h3>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Ambil 5 data teratas saja
                    $recent = array_slice($orders, 0, 5);
                    foreach ($recent as $r): 
                    ?>
                    <tr>
                        <td><?= $r['date'] ?></td>
                        <td><?= $r['customer'] ?></td>
                        <td><span class="badge"><?= $r['status'] ?></span></td>
                        <td>Rp <?= number_format($r['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>