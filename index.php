<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>DISTRIBUSI APP</h2>
        <div style="padding: 10px 20px; font-size: 12px; color: #aaa;">
            Halo, <?= $user['fullname'] ?><br>
            Role: <b><?= $user['role'] ?></b>
        </div>
        <ul class="menu">
            <li><a href="index.php" class="active">DASHBOARD</a></li>
            <li><a href="distribution.php">DISTRIBUSI (Sales)</a></li>
            <li><a href="settings.php">SETTINGS</a></li>
            <li><a href="logout.php" style="color: #ff9999;">LOGOUT</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="header-box">
            <h1>Dashboard Utama</h1>
            <p>Ringkasan aktivitas perusahaan hari ini.</p>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="card" style="flex: 1; text-align: center;">
                <h3>Total Order Hari Ini</h3>
                <h1 style="font-size: 48px; margin: 10px 0;">0</h1>
                <p>Pesanan Masuk</p>
            </div>
            <div class="card" style="flex: 1; text-align: center;">
                <h3>Status Pending</h3>
                <h1 style="font-size: 48px; margin: 10px 0;">0</h1>
                <p>Menunggu Approval AR</p>
            </div>
            <div class="card" style="flex: 1; text-align: center;">
                <h3>Target Sales</h3>
                <h1 style="font-size: 48px; margin: 10px 0;">0%</h1>
                <p>Pencapaian Bulan Ini</p>
            </div>
        </div>

        <div class="card">
            <h3>Pengumuman</h3>
            <p>Belum ada pengumuman penting dari manajemen.</p>
        </div>
    </div>
</body>
</html>