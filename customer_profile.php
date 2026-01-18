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
            background: #333;
            color: white;
            padding: 30px;
            border: 3px solid #000;
            margin-bottom: 20px;
            box-shadow: 5px 5px 0px #000;
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
            border-left: 4px solid #333;
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
            background: #333;
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
            background: #fff;
            color: #333;
            border: 3px solid #333;
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
            background: #f8f9fa;
            border: 2px solid #ddd;
        }
        .credit-info strong {
            color: #333;
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

                <!-- STATUS KELAYAKAN KREDIT (SESUAI DIAGRAM ACTIVITY) -->
                <?php if ($customer['total_score'] < 500): ?>
                    <div style="margin: 15px 0; padding: 15px; background: #fff3cd; border: 3px solid #ff9800; border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 32px;">💵</span>
                            <div>
                                <strong style="color: #f57c00; font-size: 16px;">⚠️ STATUS: TUNAI ONLY</strong><br>
                                <span style="font-size: 14px; color: #666;">
                                    Skor customer masih <strong><?= $customer['total_score'] ?> poin</strong> (di bawah 500).<br>
                                    Belum eligible untuk fasilitas kredit. Semua transaksi harus TUNAI.
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin: 15px 0; padding: 15px; background: #e8f5e9; border: 3px solid #4caf50; border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 32px;">✅</span>
                            <div>
                                <strong style="color: #2e7d32; font-size: 16px;">STATUS: ELIGIBLE UNTUK KREDIT</strong><br>
                                <span style="font-size: 14px; color: #666;">
                                    Skor customer: <strong><?= $customer['total_score'] ?> poin</strong> (≥ 500).<br>
                                    Dapat melakukan transaksi kredit dengan limit <strong>Rp <?= number_format($customer['credit_limit'], 0, ',', '.') ?></strong>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- FORMULA EXPLANATION -->
                <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border: 2px solid #333;">
                    <strong>📐 Rumus Perhitungan Smart Scoring (Sesuai Diagram Activity):</strong>
                    <div style="margin-top: 10px; font-size: 13px; line-height: 1.8;">
                        <div style="margin: 8px 0; padding: 8px; background: #fff; border-left: 3px solid #333;">
                            <strong>S1 (Frekuensi Order):</strong><br>
                            • 0-2 order/bulan = 0 poin<br>
                            • 3-5 order/bulan = 10 poin<br>
                            • 6+ order/bulan = 20 poin<br>
                            <em>Bobot: 10 → Poin = Skor × 10 (Max: 200 poin)</em>
                        </div>
                        <div style="margin: 8px 0; padding: 8px; background: #fff; border-left: 3px solid #333;">
                            <strong>S2 (Nilai Transaksi):</strong><br>
                            • < Rp 5 juta/order = 0 poin<br>
                            • Rp 5-15 juta/order = 10 poin<br>
                            • > Rp 15 juta/order = 20 poin<br>
                            <em>Bobot: 30 → Poin = Skor × 30 (Max: 600 poin)</em>
                        </div>
                        <div style="margin: 8px 0; padding: 8px; background: #fff; border-left: 3px solid #333;">
                            <strong>S3 (Riwayat Pembayaran):</strong><br>
                            • On-time < 50% = 0 poin<br>
                            • On-time 50-79% = 10 poin<br>
                            • On-time ≥ 80% = 20 poin<br>
                            <em>Bobot: 20 → Poin = Skor × 20 (Max: 400 poin)</em>
                        </div>
                        <div style="margin: 8px 0; padding: 8px; background: #fff; border-left: 3px solid #333;">
                            <strong>S4 (Lama Kerjasama):</strong><br>
                            • < 6 bulan = 0 poin<br>
                            • 6-12 bulan = 10 poin<br>
                            • > 1 tahun = 20 poin<br>
                            <em>Bobot: 40 → Poin = Skor × 40 (Max: 800 poin)</em>
                        </div>
                        <div style="margin-top: 12px; padding: 10px; background: #333; color: #fff; text-align: center; font-weight: bold;">
                            TOTAL SKOR = S1×10 + S2×30 + S3×20 + S4×40<br>
                            (Maksimal: 20×10 + 20×30 + 20×20 + 20×40 = 2000 poin)
                        </div>
                        
                        <?php
                        // Tampilkan perhitungan REAL customer ini
                        if ($breakdown) {
                            $s1 = $breakdown['kriteria_1']['score'];
                            $s2 = $breakdown['kriteria_2']['score'];
                            $s3 = $breakdown['kriteria_3']['score'];
                            $s4 = $breakdown['kriteria_4']['score'];
                            
                            $p1 = $breakdown['kriteria_1']['poin'];
                            $p2 = $breakdown['kriteria_2']['poin'];
                            $p3 = $breakdown['kriteria_3']['poin'];
                            $p4 = $breakdown['kriteria_4']['poin'];
                            
                            $total = $customer['total_score'];
                        ?>
                        <div style="margin-top: 10px; padding: 12px; background: #e3f2fd; border: 3px solid #1976d2;">
                            <strong style="color: #1565c0;">🧮 PERHITUNGAN UNTUK <?= strtoupper(htmlspecialchars($customer['name'])) ?>:</strong><br>
                            <div style="margin-top: 8px; font-family: 'Courier New', monospace; font-size: 14px; line-height: 2;">
                                TOTAL SKOR = <strong><?= $s1 ?></strong>×10 + <strong><?= $s2 ?></strong>×30 + <strong><?= $s3 ?></strong>×20 + <strong><?= $s4 ?></strong>×40<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;= <?= $p1 ?> + <?= $p2 ?> + <?= $p3 ?> + <?= $p4 ?><br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;= <strong style="color: #1565c0; font-size: 16px;"><?= $total ?> poin</strong>
                            </div>
                            <div style="margin-top: 8px; padding: 8px; background: #fff; border-left: 4px solid #1976d2; font-size: 13px;">
                                💡 Credit Limit: <strong>Rp <?= number_format($customer['credit_limit'], 0, ',', '.') ?></strong>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <div style="margin-top: 10px; padding: 8px; background: #fffacd; border: 2px dashed #ffa500;">
                            <strong>⚠️ CATATAN PENTING (Sesuai Activity Diagram):</strong><br>
                            • Bobot 10, 30, 20, 40 adalah <strong>pengali tetap</strong> (bukan persentase 0.1, 0.3, dst!)<br>
                            • <strong style="color: #d32f2f;">Skor < 500 poin = TUNAI ONLY</strong> (tidak dapat fasilitas kredit)<br>
                            • <strong style="color: #388e3c;">Skor ≥ 500 poin = ELIGIBLE KREDIT</strong> sesuai klasifikasi limit<br>
                            • Formula sesuai dengan <strong>Activity Diagram Smart Credit Scoring</strong><br>
                            • Contoh umum: S1=20, S2=10, S3=20, S4=10 → Total = (20×10)+(10×30)+(20×20)+(10×40) = 200+300+400+400 = <strong>1300 poin</strong>
                        </div>
                    </div>
                </div>

                <!-- MAPPING SKOR KE CREDIT LIMIT -->
                <div style="margin: 20px 0; padding: 15px; background: #fff; border: 3px solid #333;">
                    <strong style="font-size: 15px;">🎯 Konversi Skor ke Credit Limit (Sesuai Diagram Activity):</strong>
                    <div style="margin: 15px 0; padding: 12px; background: #f8f9fa; border-left: 4px solid #333;">
                        <strong>📋 Formula Perhitungan:</strong><br>
                        <code style="background: #fff; padding: 3px 8px; border: 1px solid #ddd; font-family: monospace;">
                            Credit Limit = NILAI TETAP berdasarkan kategori skor
                        </code>
                        <br><small style="color: #666;">* Setiap range skor mendapat credit limit yang sudah ditentukan</small>
                    </div>
                    <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #333; color: #fff;">
                                <th style="padding: 10px; border: 2px solid #000;">Range Skor</th>
                                <th style="padding: 10px; border: 2px solid #000;">Kategori</th>
                                <th style="padding: 10px; border: 2px solid #000;">Credit Limit</th>
                                <th style="padding: 10px; border: 2px solid #000;">Status Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background: #fff3cd;">
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><strong>0 - 400</strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">SANGAT RENDAH</td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Rp 5.000.000</td>
                                <td style="padding: 8px; border: 1px solid #ddd; color: #f57c00;"><strong>💵 TUNAI ONLY</strong></td>
                            </tr>
                            <tr style="background: #fff3cd;">
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><strong>401 - 499</strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">RENDAH (Batas)</td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Rp 15.000.000</td>
                                <td style="padding: 8px; border: 1px solid #ddd; color: #f57c00;"><strong>💵 TUNAI ONLY</strong></td>
                            </tr>
                            <tr style="background: #e8f5e9;">
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><strong>500 - 800</strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">RENDAH</td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Rp 15.000.000</td>
                                <td style="padding: 8px; border: 1px solid #ddd; color: #2e7d32;"><strong>✅ KREDIT OK</strong></td>
                            </tr>
                            <tr style="background: #e8f5e9;">
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><strong>801 - 1200</strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">SEDANG</td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Rp 30.000.000</td>
                                <td style="padding: 8px; border: 1px solid #ddd; color: #2e7d32;"><strong>✅ KREDIT OK</strong></td>
                            </tr>
                            <tr style="background: #e8f5e9;">
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><strong>1201 - 1600</strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">TINGGI</td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Rp 50.000.000</td>
                                <td style="padding: 8px; border: 1px solid #ddd; color: #2e7d32;"><strong>✅ KREDIT OK</strong></td>
                            </tr>
                            <tr style="background: #e8f5e9;">
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><strong>1601 - 2000</strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">SANGAT TINGGI</td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Rp 100.000.000</td>
                                <td style="padding: 8px; border: 1px solid #ddd; color: #2e7d32;"><strong>✅ KREDIT OK</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    $current_score = $customer['total_score'];
                    $current_category = '';
                    $current_range = '';
                    if ($current_score <= 400) {
                        $current_category = 'SANGAT RENDAH';
                        $current_range = '0-400';
                    } elseif ($current_score <= 800) {
                        $current_category = 'RENDAH';
                        $current_range = '401-800';
                    } elseif ($current_score <= 1200) {
                        $current_category = 'SEDANG';
                        $current_range = '801-1200';
                    } elseif ($current_score <= 1600) {
                        $current_category = 'TINGGI';
                        $current_range = '1201-1600';
                    } else {
                        $current_category = 'SANGAT TINGGI';
                        $current_range = '1601-2000';
                    }
                    ?>
                    <div style="margin-top: 15px; padding: 12px; background: #333; color: #fff; border: 3px solid #000;">
                        <strong style="font-size: 14px;">
                            ✓ Skor Anda: <?= $current_score ?> poin → Range: <?= $current_range ?> (<?= $current_category ?>)<br>
                            → Credit Limit Diberikan: <?= rupiah($customer['credit_limit']) ?>
                        </strong>
                    </div>
                    <div style="margin-top: 10px; padding: 10px; background: #fff; border: 2px dashed #333; font-size: 13px;">
                        💡 <strong>Contoh:</strong> Skor 950 masuk range <strong>801-1200 (SEDANG)</strong>, 
                        sehingga otomatis mendapat credit limit tetap sebesar <strong>Rp 30.000.000</strong>.
                    </div>
                </div>

                <?php if ($breakdown): ?>
                <div class="score-breakdown">
                    <strong>📋 Breakdown Perhitungan Customer Ini:</strong>
                    
                    <?php 
                    $totalPoints = array_sum(array_column($breakdown, 'poin'));
                    if ($totalPoints == 0): 
                    ?>
                    <div style="margin: 15px 0; padding: 15px; background: #fff3cd; border: 2px solid #333;">
                        <strong>⚠️ PELANGGAN BARU</strong><br>
                        <p style="margin: 10px 0; line-height: 1.6;">
                            Anda adalah pelanggan baru yang belum memiliki riwayat transaksi. 
                            Credit limit awal diberikan sebesar <strong>Rp 5.000.000</strong> 
                            sebagai masa percobaan.
                        </p>
                        <p style="margin: 10px 0; line-height: 1.6;">
                            Untuk meningkatkan credit limit, Anda perlu:<br>
                            • Melakukan minimal 3 transaksi<br>
                            • Membayar tepat waktu<br>
                            • Meningkatkan nilai dan frekuensi transaksi
                        </p>
                    </div>
                    <?php else: ?>
                    
                    <?php foreach ($breakdown as $key => $detail): ?>
                    <div class="score-item">
                        <div>
                            <strong><?= $detail['label'] ?></strong>
                            <br>
                            <small>Skor: <?= $detail['score'] ?>/20 × Bobot <?= $detail['bobot'] ?> = <?= $detail['poin'] ?> poin</small>
                        </div>
                        <div style="font-size: 18px; font-weight: bold; color: #333;">
                            <?= $detail['poin'] ?> poin
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 15px; padding: 12px; background: #333; color: #fff; text-align: center; border: 3px solid #000;">
                        <strong style="font-size: 16px;">
                            TOTAL: <?= array_sum(array_column($breakdown, 'poin')) ?> poin
                        </strong>
                    </div>
                    <?php endif; ?>
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
                    <strong style="color: #333;"><?= rupiah($customer['current_debt']) ?></strong>
                </div>
                
                <div class="credit-info" style="background: #fff; border: 3px solid #333;">
                    <span><strong>Sisa Limit Tersedia:</strong></span>
                    <strong style="color: #333; font-size: 20px;">
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

                <div style="margin-top: 20px; padding: 15px; background: #fff; border: 2px solid #333;">
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
