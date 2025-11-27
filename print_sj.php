<?php
// LOAD DATA
$id = $_GET['id'] ?? 0;
$orders = json_decode(file_get_contents('data/orders.json'), true);
$key = array_search($id, array_column($orders, 'id'));

if($key === false) die("Pesanan tidak ditemukan.");
$o = $orders[$key];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Surat Jalan - <?= $id ?></title>
    <style>
        body { font-family: monospace; padding: 20px; border: 1px solid #000; width: 600px; margin: auto; }
        h1 { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .meta { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
        .ttd { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; }
        .ttd div { width: 30%; border-top: 1px solid #000; padding-top: 10px; }
    </style>
</head>
<body onload="window.print()">
    <h1>SURAT JALAN (DELIVERY ORDER)</h1>
    
    <div class="meta">
        <b>No. SJ :</b> SJ-<?= $o['id'] ?><br>
        <b>Tanggal:</b> <?= $o['date'] ?><br>
        <b>Kepada :</b> <?= $o['customer'] ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Qty (Jumlah)</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><?= $o['product'] ?></td>
                <td><?= $o['qty'] ?></td>
                <td>Unit/Dus</td>
            </tr>
        </tbody>
    </table>

    <p><i>Mohon diperiksa kembali. Barang yang sudah dibeli tidak dapat dikembalikan.</i></p>

    <div class="ttd">
        <div style="border:none;"> <br><br>( ...................... )<br>Penerima</div>
        <div style="border:none;"> <br><br>( ...................... )<br>Supir / Pengirim</div>
        <div style="border:none;"> <br><br>( ...................... )<br>Kepala Gudang</div>
    </div>
</body>
</html>