<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];

// LOAD DATA
$customers = json_decode(file_get_contents('data/customers.json'), true);
$products = json_decode(file_get_contents('data/products.json'), true);
$orders = json_decode(file_get_contents('data/orders.json'), true);

// PROSES INPUT (Hanya FAKTURIS yang boleh input)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] == 'FAKTURIS') {
    $custId = $_POST['customer_id'];
    $prodId = $_POST['product_id'];
    $qty = (int)$_POST['qty'];

    // Cari Data (Logic sama seperti sebelumnya)
    $custKey = array_search($custId, array_column($customers, 'id'));
    $prodKey = array_search($prodId, array_column($products, 'id'));
    $selectedCust = $customers[$custKey];
    $selectedProd = $products[$prodKey];
    $totalHarga = $selectedProd['price'] * $qty;
    
    // CEK CREDIT LIMIT
    $futureDebt = $selectedCust['debt'] + $totalHarga;
    if ($futureDebt > $selectedCust['limit']) {
        $status = "ON HOLD"; 
    } else {
        $status = "APPROVED";
        // Update Hutang
        $customers[$custKey]['debt'] += $totalHarga;
        file_put_contents('data/customers.json', json_encode($customers, JSON_PRETTY_PRINT));
    }

    // Simpan Order
    $newOrder = [
        "id" => time(),
        "date" => date("Y-m-d H:i"),
        "customer" => $selectedCust['name'],
        "product" => $selectedProd['name'],
        "qty" => $qty,
        "total" => $totalHarga,
        "status" => $status
    ];
    array_unshift($orders, $newOrder);
    file_put_contents('data/orders.json', json_encode($orders, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Distribusi - Sales Order</title>
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
            <li><a href="index.php">DASHBOARD</a></li>
            <li><a href="distribution.php" class="active">DISTRIBUSI (Sales)</a></li>
            <li><a href="settings.php">SETTINGS</a></li>
            <li><a href="logout.php" style="color: #ff9999;">LOGOUT</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="header-box">
            <h1>Distribusi & Sales Order</h1>
            <p>Menu pencatatan penjualan dan status pengiriman.</p>
        </div>

        <?php if($user['role'] == 'FAKTURIS'): ?>
        <div class="card">
            <h3>Buat Pesanan Baru</h3>
            <form method="POST">
                <select name="customer_id" required>
                    <option value="">-- Pilih Warung / Toko --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= $c['name'] ?> (Sisa Limit: Rp <?= number_format($c['limit'] - $c['debt']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="product_id" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= $p['name'] ?> (Stok: <?= $p['stock'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="qty" placeholder="Qty" min="1" required>
                <button type="submit">PROSES ORDER</button>
            </form>
        </div>
        <?php else: ?>
            <div class="card" style="background: #eee;">
                <p><i>Anda login sebagai <b><?= $user['role'] ?></b>. Anda tidak memiliki akses untuk membuat pesanan baru (Hanya Fakturis).</i></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Daftar Pesanan</h3>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Warung</th>
                        <th>Barang</th>
                        <th>Total (Rp)</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= $o['date'] ?></td>
                        <td><?= $o['customer'] ?></td>
                        <td><?= $o['product'] ?> (x<?= $o['qty'] ?>)</td>
                        <td><?= number_format($o['total']) ?></td>
                        <td>
                            <span class="badge <?= $o['status'] == 'APPROVED' ? 'approved' : 'hold' ?>">
                                <?= $o['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if($o['status'] == 'APPROVED'): ?>
                                <a href="#">[Cetak SJ]</a>
                            <?php elseif($o['status'] == 'ON HOLD' && $user['role'] == 'AR_FINANCE'): ?>
                                <button style="padding: 2px 5px; font-size:10px;">APPROVE (Bypass)</button>
                            <?php else: ?>
                                <span style="color:#aaa;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>