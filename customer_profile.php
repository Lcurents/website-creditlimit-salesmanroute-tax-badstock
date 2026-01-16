<?php
/**
 * CUSTOMER PROFILE - Smart Scoring Transparency
 * Menampilkan detail scoring breakdown untuk customer
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$user = check_login();
$db = Database::getInstance();

$customerId = $_GET['id'] ?? 0;

// Get customer data
$customer = $db->query("SELECT * FROM customers WHERE id = :id", ['id' => $customerId]);

if (empty($customer)) {
    die("Pelanggan tidak ditemukan!");
}

$customer = $customer[0];

// Parse scoring breakdown
$breakdown = json_decode($customer['scoring_breakdown'], true);

// Get customer orders history
$orders = $db->query(
    "SELECT * FROM orders WHERE customer_id = :id ORDER BY created_at DESC LIMIT 10",
    ['id' => $customerId]
);

// Calculate statistics
$totalOrders = $db->query("SELECT COUNT(*) as total FROM orders WHERE customer_id = :id", ['id' => $customerId])[0]['total'];
$paidOrders = $db->query("SELECT COUNT(*) as total FROM orders WHERE customer_id = :id AND status = 'PAID'", ['id' => $customerId])[0]['total'];
$onTimePayment = $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pelanggan - <?= htmlspecialchars($customer['name']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-box h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .score-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .score-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-left: 4px solid #667eea;
        }
        .score-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 8px;
        }
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.5s ease;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 8px;
            margin: 10px 0;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .credit-info {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 6px;
        }
        .credit-info strong {
            color: #1976d2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <a href="settings.php" class="back-btn">← Kembali ke Settings</a>
        
        <div class="profile-header">
            <h1>📊 Profil Pelanggan & Smart Scoring</h1>
            <h2 style="margin: 10px 0;"><?= htmlspecialchars($customer['name']) ?></h2>
            <p style="margin: 5px 0;">📍 <?= htmlspecialchars($customer['address'] ?? '-') ?></p>
            <p style="margin: 5px 0;">📞 <?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
        </div>

        <div class="profile-grid">
            <!-- CREDIT SCORE & LIMIT -->
            <div class="info-box">
                <h3>💯 Smart Credit Score</h3>
                
                <div class="stat-card">
                    <div class="stat-label">Total Score</div>
                    <div class="stat-value"><?= $customer['total_score'] ?></div>
                    <div class="stat-label">dari 2000 poin</div>
                </div>

                <div class="score-bar">
                    <div class="score-fill" style="width: <?= ($customer['total_score'] / 2000) * 100 ?>%">
                        <?= round(($customer['total_score'] / 2000) * 100, 1) ?>%
                    </div>
                </div>

                <?php if ($breakdown): ?>
                <div class="score-breakdown">
                    <strong>📋 Breakdown Perhitungan:</strong>
                    
                    <?php foreach ($breakdown as $key => $detail): ?>
                    <div class="score-item">
                        <div>
                            <strong><?= $detail['label'] ?></strong>
                            <br>
                            <small>Skor: <?= $detail['score'] ?>/20</small>
                        </div>
                        <div style="font-size: 18px; font-weight: bold; color: #667eea;">
                            <?= $detail['poin'] ?> poin
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color: #999; margin-top: 15px;">
                    <em>Breakdown scoring tidak tersedia (pelanggan lama sebelum sistem scoring)</em>
                </p>
                <?php endif; ?>
            </div>

            <!-- CREDIT LIMIT & DEBT -->
            <div class="info-box">
                <h3>💰 Informasi Kredit</h3>
                
                <div class="credit-info">
                    <span>Credit Limit:</span>
                    <strong><?= rupiah($customer['credit_limit']) ?></strong>
                </div>
                
                <div class="credit-info">
                    <span>Total Hutang Saat Ini:</span>
                    <strong style="color: #dc3545;"><?= rupiah($customer['current_debt']) ?></strong>
                </div>
                
                <div class="credit-info" style="background: #d4edda; border: 2px solid #28a745;">
                    <span><strong>Sisa Limit Tersedia:</strong></span>
                    <strong style="color: #28a745; font-size: 20px;">
                        <?= rupiah($customer['credit_limit'] - $customer['current_debt']) ?>
                    </strong>
                </div>

                <div style="margin-top: 20px;">
                    <strong>Utilisasi Kredit:</strong>
                    <div class="score-bar">
                        <?php 
                        $utilization = $customer['credit_limit'] > 0 
                            ? ($customer['current_debt'] / $customer['credit_limit']) * 100 
                            : 0;
                        $barColor = $utilization > 80 ? '#dc3545' : ($utilization > 50 ? '#ffc107' : '#28a745');
                        ?>
                        <div class="score-fill" style="width: <?= $utilization ?>%; background: <?= $barColor ?>">
                            <?= round($utilization, 1) ?>%
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px;">
                    <strong>📈 Statistik:</strong>
                    <div style="margin-top: 10px;">
                        <div>Total Order: <strong><?= $totalOrders ?></strong></div>
                        <div>Order Lunas: <strong><?= $paidOrders ?></strong></div>
                        <div>On-time Payment: <strong><?= $onTimePayment ?>%</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ORDER HISTORY -->
        <div class="info-box">
            <h3>📦 Riwayat Order Terakhir</h3>
            
            <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Jatuh Tempo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['id'] ?></strong></td>
                        <td><?= tanggal_indo(date('Y-m-d', strtotime($order['created_at']))) ?></td>
                        <td><?= rupiah($order['total_amount']) ?></td>
                        <td><?= status_badge($order['status']) ?></td>
                        <td><?= $order['due_date'] ? tanggal_indo($order['due_date']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 20px;">Belum ada riwayat order</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
