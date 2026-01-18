<?php
/**
 * RECALCULATE CUSTOMER SCORES - NEW FORMULA (SESUAI DIAGRAM ACTIVITY)
 * 
 * Script ini menghitung ulang semua customer scoring berdasarkan formula baru:
 * S1 (Frekuensi Order) × 10 = 0-200 poin
 * S2 (Nilai Transaksi) × 30 = 0-600 poin
 * S3 (Riwayat Pembayaran %) × 20 = 0-400 poin
 * S4 (Lama Kerjasama) × 40 = 0-800 poin
 * TOTAL = 0-2000 poin
 * 
 * Jalankan sekali saja setelah perubahan formula!
 */

require_once __DIR__ . '/config/database.php';

echo "==============================================\n";
echo "RECALCULATE CUSTOMER SCORES (NEW FORMULA)\n";
echo "Sesuai dengan Activity Diagram KP\n";
echo "==============================================\n\n";

$db = Database::getInstance();

// Ambil semua customer
$customers = $db->query("SELECT * FROM customers ORDER BY name");

foreach ($customers as $customer) {
    echo "Processing: {$customer['name']} (ID: {$customer['id']})\n";
    
    // Ambil semua order customer
    $orders = $db->query(
        "SELECT * FROM orders WHERE customer_id = :id ORDER BY created_at ASC",
        ['id' => $customer['id']]
    );
    
    $totalOrders = count($orders);
    
    if ($totalOrders == 0) {
        echo "  ⚠️  Belum ada transaksi → Skor = 0, Limit = Rp 5 juta\n\n";
        
        $db->execute(
            "UPDATE customers SET total_score = 0, credit_limit = 5000000, scoring_breakdown = :breakdown WHERE id = :id",
            [
                'breakdown' => json_encode([
                    'kriteria_1' => ['score' => 0, 'poin' => 0, 'label' => 'Frekuensi Order', 'bobot' => 10],
                    'kriteria_2' => ['score' => 0, 'poin' => 0, 'label' => 'Nilai Transaksi', 'bobot' => 30],
                    'kriteria_3' => ['score' => 0, 'poin' => 0, 'label' => 'Riwayat Pembayaran', 'bobot' => 20],
                    'kriteria_4' => ['score' => 0, 'poin' => 0, 'label' => 'Lama Kerjasama', 'bobot' => 40]
                ]),
                'id' => $customer['id']
            ]
        );
        continue;
    }
    
    // HITUNG METRICS
    $totalValue = 0;
    $onTimeCount = 0;
    $paidCount = 0;
    
    foreach ($orders as $order) {
        $totalValue += $order['total_amount'];
        
        if ($order['status'] == 'PAID' && $order['paid_date']) {
            $paidCount++;
            // On-time jika paid_date <= due_date
            if ($order['due_date'] && $order['paid_date'] <= $order['due_date']) {
                $onTimeCount++;
            }
        }
    }
    
    $avgTransactionValue = $totalValue / $totalOrders;
    $avgTransactionJuta = $avgTransactionValue / 1000000;
    
    $firstOrderDate = new DateTime($orders[0]['created_at']);
    $now = new DateTime();
    $monthsDiff = max(1, $firstOrderDate->diff($now)->m + ($firstOrderDate->diff($now)->y * 12));
    $monthsAsCustomer = $monthsDiff / 12;
    $ordersPerMonth = $totalOrders / max(1, $monthsDiff);
    
    // Hitung persentase on-time (dari transaksi yang sudah PAID)
    $onTimePercent = $paidCount > 0 ? ($onTimeCount / $paidCount) * 100 : 0;
    
    echo "  Data: \n";
    echo "    - Total Orders: {$totalOrders}\n";
    echo "    - Avg Transaction: Rp " . number_format($avgTransactionValue, 0, ',', '.') . " (Rp " . number_format($avgTransactionJuta, 2) . " juta)\n";
    echo "    - Orders/Month: " . number_format($ordersPerMonth, 2) . "\n";
    echo "    - On-time Payments: {$onTimeCount} / {$paidCount}\n";
    echo "    - On-time Percentage: " . number_format($onTimePercent, 1) . "%\n";
    echo "    - Customer since: " . number_format($monthsAsCustomer, 2) . " years\n";
    
    // SCORING CALCULATION (NEW FORMULA)
    
    // S1: Frekuensi Order (Bobot 10)
    if ($ordersPerMonth >= 6) {
        $s1 = 20;
        $s1_label = "6+ order/bulan";
    } elseif ($ordersPerMonth >= 3) {
        $s1 = 10;
        $s1_label = "3-5 order/bulan";
    } else {
        $s1 = 0;
        $s1_label = "0-2 order/bulan";
    }
    $total1 = $s1 * 10;
    
    // S2: Nilai Transaksi (Bobot 30)
    if ($avgTransactionJuta > 15) {
        $s2 = 20;
        $s2_label = "> Rp 15 juta";
    } elseif ($avgTransactionJuta >= 5) {
        $s2 = 10;
        $s2_label = "Rp 5-15 juta";
    } else {
        $s2 = 0;
        $s2_label = "< Rp 5 juta";
    }
    $total2 = $s2 * 30;
    
    // S3: Riwayat Pembayaran % (Bobot 20)
    if ($onTimePercent >= 80) {
        $s3 = 20;
        $s3_label = "On-time ≥ 80%";
    } elseif ($onTimePercent >= 50) {
        $s3 = 10;
        $s3_label = "On-time 50-79%";
    } else {
        $s3 = 0;
        $s3_label = "On-time < 50%";
    }
    $total3 = $s3 * 20;
    
    // S4: Lama Kerjasama (Bobot 40)
    if ($monthsAsCustomer > 1) {
        $s4 = 20;
        $s4_label = "> 1 tahun";
    } elseif ($monthsAsCustomer >= 0.5) {
        $s4 = 10;
        $s4_label = "6-12 bulan";
    } else {
        $s4 = 0;
        $s4_label = "< 6 bulan";
    }
    $total4 = $s4 * 40;
    
    $totalScore = $total1 + $total2 + $total3 + $total4;
    
    echo "  Scoring:\n";
    echo "    S1 (Frekuensi): {$s1}/20 ({$s1_label}) × 10 = {$total1} poin\n";
    echo "    S2 (Nilai Transaksi): {$s2}/20 ({$s2_label}) × 30 = {$total2} poin\n";
    echo "    S3 (Riwayat Bayar): {$s3}/20 ({$s3_label}) × 20 = {$total3} poin\n";
    echo "    S4 (Lama Kerjasama): {$s4}/20 ({$s4_label}) × 40 = {$total4} poin\n";
    echo "  TOTAL SCORE: {$totalScore} poin\n";
    
    // Tentukan Credit Limit
    if ($totalScore <= 400) {
        $creditLimit = 5000000;
        $category = "SANGAT RENDAH (0-400)";
    } elseif ($totalScore <= 800) {
        $creditLimit = 15000000;
        $category = "RENDAH (401-800)";
    } elseif ($totalScore <= 1200) {
        $creditLimit = 30000000;
        $category = "SEDANG (801-1200)";
    } elseif ($totalScore <= 1600) {
        $creditLimit = 50000000;
        $category = "TINGGI (1201-1600)";
    } else {
        $creditLimit = 100000000;
        $category = "SANGAT TINGGI (1601-2000)";
    }
    
    echo "  NEW CREDIT LIMIT: Rp " . number_format($creditLimit, 0, ',', '.') . " ({$category})\n";
    
    // Update database
    $breakdown = json_encode([
        'kriteria_1' => ['score' => $s1, 'poin' => $total1, 'label' => 'Frekuensi Order', 'bobot' => 10],
        'kriteria_2' => ['score' => $s2, 'poin' => $total2, 'label' => 'Nilai Transaksi', 'bobot' => 30],
        'kriteria_3' => ['score' => $s3, 'poin' => $total3, 'label' => 'Riwayat Pembayaran', 'bobot' => 20],
        'kriteria_4' => ['score' => $s4, 'poin' => $total4, 'label' => 'Lama Kerjasama', 'bobot' => 40]
    ]);
    
    $db->execute(
        "UPDATE customers SET total_score = :score, credit_limit = :limit, scoring_breakdown = :breakdown WHERE id = :id",
        [
            'score' => $totalScore,
            'limit' => $creditLimit,
            'breakdown' => $breakdown,
            'id' => $customer['id']
        ]
    );
    
    echo "  ✅ Score updated!\n\n";
}

echo "==============================================\n";
echo "RECALCULATION COMPLETE!\n";
echo "==============================================\n";
