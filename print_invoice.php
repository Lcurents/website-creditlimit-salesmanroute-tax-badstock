<?php
$id = $_GET['id'] ?? 0;
$orders = json_decode(file_get_contents('data/orders.json'), true);
$key = array_search($id, array_column($orders, 'id'));
if($key === false) die("Pesanan tidak ditemukan.");
$o = $orders[$key];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Faktur - <?= $id ?></title>
    <style>
        body { font-family: sans-serif; padding: 40px; border: 1px solid #ccc; width: 700px; margin: auto; }
        .header { text-align: right; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: bold; float: left; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #eee; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
    </style>
</head>
<body onload="window.print()">
    <div class="title">FAKTUR PENJUALAN</div>
    <div class="header">
        No. Inv: INV-<?= $o['id'] ?><br>
        Tanggal: <?= $o['date'] ?><br>
        Customer: <b><?= $o['customer'] ?></b>
    </div>
    <div style="clear:both"></div>

    <table>
        <thead>
            <tr>
                <th>Deskripsi Barang</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $o['product'] ?></td>
                <td><?= $o['qty'] ?></td>
                <td>Rp <?= number_format($o['total'] / $o['qty']) ?></td>
                <td>Rp <?= number_format($o['total']) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        TOTAL TAGIHAN: Rp <?= number_format($o['total']) ?>
    </div>

    <p style="margin-top: 50px; font-size: 12px;">
        Pembayaran harap ditransfer ke BCA 123-456-789 a/n PT Distribusi Sukses.<br>
        Jatuh tempo pembayaran sesuai kesepakatan kredit.
    </p>
</body>
</html>