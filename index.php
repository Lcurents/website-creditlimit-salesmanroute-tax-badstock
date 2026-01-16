<?php
/**
 * NEW DASHBOARD with SQLite
 * Improved Performance & Security
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$user = check_login();
$db = Database::getInstance();

// === DASHBOARD STATISTICS ===
$today = date("Y-m-d");

// 1. Omset Hari Ini
$sql_omset = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_omset 
              FROM orders 
              WHERE DATE(order_date) = :today AND status != 'ON HOLD'";
$omset_data = $db->query($sql_omset, ['today' => $today])[0];
$omsetHariIni = $omset_data['total_omset'] ?? 0;
$orderHariIni = $omset_data['total_orders'] ?? 0;

// 2. Order Pending Approval
$sql_pending = "SELECT COUNT(*) as total FROM orders WHERE status = 'ON HOLD'";
$pendingCount = $db->query($sql_pending)[0]['total'];

// 3. Total Piutang
$sql_piutang = "SELECT SUM(current_debt) as total_debt FROM customers";
$totalPiutang = $db->query($sql_piutang)[0]['total_debt'] ?? 0;

// 4. Recent Activities (5 terakhir)
$sql_recent = "SELECT o.*, c.name as customer_name 
               FROM orders o 
               LEFT JOIN customers c ON o.customer_id = c.id 
               ORDER BY o.order_date DESC 
               LIMIT 5";
$recentOrders = $db->query($sql_recent);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Distribusi App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>📊 Dashboard Eksekutif</h1>
            <p>Ringkasan performa bisnis per <b><?= tanggal_indo(date("Y-m-d")) ?></b></p>
        </div>

        <!-- CARDS STATISTIK -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
            
            <!-- Card 1: Omset -->
            <div class="card" style="text-align: center; border-left: 5px solid #28a745;">
                <h3 style="color: #666; margin-bottom: 10px;">💰 Omset Hari Ini</h3>
                <h1 style="font-size: 36px; margin: 10px 0; color: #28a745;">
                    <?= rupiah($omsetHariIni) ?>
                </h1>
                <p style="color: #999;"><?= $orderHariIni ?> Transaksi Masuk</p>
            </div>

            <!-- Card 2: Pending -->
            <div class="card" style="text-align: center; border-left: 5px solid #dc3545;">
                <h3 style="color: #666; margin-bottom: 10px;">⏳ Butuh Approval</h3>
                <h1 style="font-size: 36px; margin: 10px 0; color: <?= $pendingCount > 0 ? '#dc3545' : '#333' ?>;">
                    <?= $pendingCount ?>
                </h1>
                <p style="color: #999;">Order Over Limit</p>
            </div>

            <!-- Card 3: Piutang -->
            <div class="card" style="text-align: center; border-left: 5px solid #ffc107;">
                <h3 style="color: #666; margin-bottom: 10px;">📋 Total Piutang</h3>
                <h1 style="font-size: 36px; margin: 10px 0; color: #ffc107;">
                    <?= rupiah($totalPiutang) ?>
                </h1>
                <p style="color: #999;">Uang di Pelanggan</p>
            </div>

        </div>

        <!-- TABLE: RECENT ACTIVITIES -->
        <div class="card">
            <h3>🕒 5 Aktivitas Order Terakhir</h3>
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
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999;">Belum ada aktivitas hari ini</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                            <td><?= $order['customer_name'] ?></td>
                            <td><?= status_badge($order['status']) ?></td>
                            <td><strong><?= rupiah($order['total_amount']) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- QUICK LINKS -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px;">
            <a href="distribution.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.2s;">
                <h4>📦 Buat Order Baru</h4>
                <p style="color: #999; font-size: 13px;">Input pesanan dari pelanggan</p>
            </a>
            
            <a href="finance.php" class="card" style="text-decoration: none; color: inherit; transition: transform 0.2s;">
                <h4>💵 Input Pembayaran</h4>
                <p style="color: #999; font-size: 13px;">Terima setoran dari sales</p>
            </a>
        </div>

    </div>

    <style>
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>
