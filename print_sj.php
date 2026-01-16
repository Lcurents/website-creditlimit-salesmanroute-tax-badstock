<?php
/**
 * PRINT SURAT JALAN - Updated for SQLite
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$orderId = $_GET['id'] ?? 0;
$db = Database::getInstance();

$sql = "SELECT o.*, c.name as customer_name, c.address, c.phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.id = :id AND o.status = 'APPROVED'";
$orders = $db->query($sql, ['id' => $orderId]);

if (empty($orders)) {
    die("Surat Jalan tidak ditemukan atau order belum di-approve!");
}

$order = $orders[0];

$items = $db->query(
    "SELECT oi.*, p.name as product_name, p.unit 
     FROM order_items oi 
     LEFT JOIN products p ON oi.product_id = p.id 
     WHERE oi.order_id = :id", 
    ['id' => $orderId]
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - SJ-<?= $orderId ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 30px; max-width: 800px; margin: auto; }
        .sj-box { border: 3px double #000; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .title { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .info-section { display: flex; justify-content: space-between; margin: 20px 0; }
        .info-box { flex: 1; padding: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #333; padding: 10px; }
        th { background: #f0f0f0; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 30%; text-align: center; }
        .signature-line { margin-top: 60px; border-top: 1px solid #000; padding-top: 5px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="sj-box">
        <div class="header">
            <div class="title">SURAT JALAN</div>
            <div>Delivery Order / Shipping Document</div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <div style="font-weight: bold;">Dari:</div>
                <div style="font-size: 16px; font-weight: bold;">DISTRIBUSI APP</div>
                <div>Jl. Raya Distribusi No. 123</div>
            </div>
            <div class="info-box" style="text-align: right;">
                <div><strong>No. SJ-<?= $orderId ?></strong></div>
                <div>Tanggal: <?= tanggal_indo(date('Y-m-d')) ?></div>
            </div>
        </div>

        <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border: 2px solid #ddd;">
            <div><strong>Kepada:</strong></div>
            <div style="font-size: 18px; font-weight: bold;"><?= $order['customer_name'] ?></div>
            <div><?= $order['address'] ?? '-' ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center">No</th>
                    <th>Nama Barang</th>
                    <th class="text-center">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($items as $item): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $item['product_name'] ?></td>
                    <td class="text-center"><?= $item['qty'] ?> <?= $item['unit'] ?? 'PCS' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            <div class="signature-box">
                <strong>Pengirim</strong>
                <div class="signature-line">Driver</div>
            </div>
            <div class="signature-box">
                <strong>Penerima</strong>
                <div class="signature-line"><?= $order['customer_name'] ?></div>
            </div>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #28a745; color: white; border: none; cursor: pointer;">🖨️ CETAK</button>
        <button onclick="window.close()" style="padding: 10px 30px; background: #dc3545; color: white; border: none; cursor: pointer; margin-left: 10px;">✖ TUTUP</button>
    </div>
</body>
</html>
