<?php
$customers = json_decode(file_get_contents('data/customers.json'), true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Settings - Master Data</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>DISTRIBUSI APP</h2>
        <ul class="menu">
            <li><a href="index.php">TRANSAKSI (Sales)</a></li>
            <li><a href="settings.php" class="active">SETTINGS (Master Data)</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="header-box">
            <h1>Master Data</h1>
            <p>Kelola Data Warung, Barang, dan User.</p>
        </div>

        <div class="card">
            <h3>Data Pelanggan (Warung)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Warung</th>
                        <th>Limit Kredit</th>
                        <th>Hutang Berjalan</th>
                        <th>Sisa Limit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= $c['name'] ?></td>
                        <td>Rp <?= number_format($c['limit']) ?></td>
                        <td>Rp <?= number_format($c['debt']) ?></td>
                        <td><b>Rp <?= number_format($c['limit'] - $c['debt']) ?></b></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h3>Data User & Role</h3>
            <p><i>Fitur manajemen user akan ditambahkan di sini.</i></p>
            <ul>
                <li>Fakturis (Input Order)</li>
                <li>AR / Finance (Approval Credit)</li>
                <li>Admin Gudang (View Only)</li>
            </ul>
        </div>
    </div>
</body>
</html>