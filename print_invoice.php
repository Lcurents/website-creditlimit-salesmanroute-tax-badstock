<?php
/**
 * PRINT INVOICE - Updated for SQLite
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$orderId = $_GET['id'] ?? 0;
$db = Database::getInstance();

// Get order with customer info
$sql = "SELECT o.*, c.name as customer_name, c.address, c.phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.id = :id";
$orders = $db->query($sql, ['id' => $orderId]);

if (empty($orders)) {
    die("Faktur tidak ditemukan!");
}

$order = $orders[0];

// Get order items
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
    <title>Faktur - INV-<?= $orderId ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            padding: 30px; 
            max-width: 800px; 
            margin: auto;
        }
        .invoice-box { 
            border: 2px solid #000; 
            padding: 20px; 
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .company-info { flex: 1; }
        .invoice-info { 
            flex: 1; 
            text-align: right; 
            font-size: 12px;
        }
        .title { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 10px;
        }
        .customer-info { 
            margin: 20px 0; 
            padding: 10px; 
            background: #f5f5f5; 
            border: 1px solid #ddd;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
        }
        th, td { 
            border: 1px solid #333; 
            padding: 10px; 
            text-align: left; 
        }
        th { 
            background: #333; 
            color: white; 
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-section { 
            margin-top: 20px; 
            text-align: right; 
        }
        .total-row { 
            padding: 5px 0; 
            font-size: 14px;
        }
        .grand-total { 
            font-size: 18px; 
            font-weight: bold; 
            border-top: 2px solid #000; 
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer { 
            margin-top: 30px; 
            display: flex; 
            justify-content: space-between;
        }
        .signature { 
            text-align: center; 
            margin-top: 60px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- HEADER -->
        <div class="header">
            <div class="company-info">
                <div class="title">DISTRIBUSI APP</div>
                <div style="font-size: 12px;">
                    Jl. Raya Distribusi No. 123<br>
                    Telp: (021) 1234-5678<br>
                    Email: info@distribusiapp.com
                </div>
            </div>
            <div class="invoice-info">
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 5px;">FAKTUR</div>
                <div>No: <strong>INV-<?= $orderId ?></strong></div>
                <div>Tanggal: <?= tanggal_indo(date('Y-m-d', strtotime($order['order_date']))) ?></div>
                <div>Jatuh Tempo: <?= tanggal_indo($order['due_date']) ?></div>
            </div>
        </div>

        <!-- CUSTOMER INFO -->
        <div class="customer-info">
            <strong>Kepada Yth:</strong><br>
            <strong style="font-size: 16px;"><?= $order['customer_name'] ?></strong><br>
            <?= $order['address'] ?? '-' ?><br>
            Telp: <?= $order['phone'] ?? '-' ?>
        </div>

        <!-- ITEMS TABLE -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Deskripsi Barang</th>
                    <th style="width: 80px;" class="text-center">Qty</th>
                    <th style="width: 120px;" class="text-right">Harga Satuan</th>
                    <th style="width: 140px;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $grandTotal = 0;
                foreach ($items as $item): 
                    $grandTotal += $item['subtotal'];
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $item['product_name'] ?></td>
                    <td class="text-center"><?= $item['qty'] ?> <?= $item['unit'] ?? 'PCS' ?></td>
                    <td class="text-right"><?= rupiah($item['unit_price']) ?></td>
                    <td class="text-right"><strong><?= rupiah($item['subtotal']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- TOTAL -->
        <div class="total-section">
            <div class="total-row">
                Subtotal: <strong><?= rupiah($order['total_amount']) ?></strong>
            </div>
            <div class="total-row">
                PPN (0%): <strong>Rp 0</strong>
            </div>
            <div class="grand-total">
                TOTAL: <strong><?= rupiah($order['total_amount']) ?></strong>
            </div>
        </div>

        <!-- PAYMENT INFO -->
        <div style="margin-top: 20px; padding: 10px; background: #fff3cd; border: 1px solid #ffc107;">
            <strong>Status Pembayaran:</strong> <?= status_badge($order['status']) ?><br>
            <?php if ($order['paid_date']): ?>
                <strong>Tanggal Bayar:</strong> <?= tanggal_indo($order['paid_date']) ?>
            <?php else: ?>
                <em>Belum dibayar - Jatuh tempo: <?= tanggal_indo($order['due_date']) ?></em>
            <?php endif; ?>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div style="width: 45%;">
                <div><strong>Hormat Kami,</strong></div>
                <div class="signature">
                    _________________<br>
                    Sales / Admin
                </div>
            </div>
            <div style="width: 45%;">
                <div><strong>Penerima,</strong></div>
                <div class="signature">
                    _________________<br>
                    <?= $order['customer_name'] ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; font-size: 10px; text-align: center; color: #666;">
            Faktur ini dicetak otomatis oleh sistem - <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #333; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px;">
            🖨️ CETAK FAKTUR
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; background: #dc3545; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; margin-left: 10px;">
            ✖ TUTUP
        </button>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
