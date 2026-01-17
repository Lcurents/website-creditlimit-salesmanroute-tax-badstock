<?php
/**
 * Script untuk menghitung ulang skor customer berdasarkan data transaksi REAL
 * Dijalankan otomatis atau manual untuk update scoring
 */
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

echo "=== RECALCULATE CUSTOMER SCORES (REAL DATA) ===\n\n";

// Ambil semua customer
$customers = $db->query("SELECT * FROM customers ORDER BY name");

foreach ($customers as $customer) {
    $customerId = $customer['id'];
    $customerName = $customer['name'];
    
    echo "Processing: $customerName (ID: $customerId)\n";
    
    // 1. Ambil data transaksi customer
    $orders = $db->query(
        "SELECT * FROM orders WHERE customer_id = :id ORDER BY created_at ASC",
        ['id' => $customerId]
    );
    
    $totalOrders = count($orders);
    
    if ($totalOrders == 0) {
        echo "  → No orders, keeping score 0\n\n";
        continue;
    }
    
    // 2. Hitung metrics
    $totalValue = 0;
    $latePayments = 0;
    $paidCount = 0;
    
    foreach ($orders as $order) {
        $totalValue += $order['total_amount'];
        
        // Cek keterlambatan (jika ada due_date dan paid_date)
        if ($order['status'] == 'PAID' && $order['paid_date']) {
            $paidCount++;
            if ($order['due_date'] && $order['paid_date'] > $order['due_date']) {
                $latePayments++;
            }
        }
    }
    
    $avgTransactionValue = $totalValue / $totalOrders; // Rata-rata per transaksi
    $avgTransactionJuta = $avgTransactionValue / 1000000; // Dalam juta
    
    // Frekuensi per bulan (estimasi sederhana: total orders / lama jadi customer dalam bulan)
    $firstOrderDate = new DateTime($orders[0]['created_at']);
    $now = new DateTime();
    $monthsDiff = max(1, $firstOrderDate->diff($now)->m + ($firstOrderDate->diff($now)->y * 12));
    $monthsAsCustomer = $monthsDiff / 12; // Dalam tahun
    $ordersPerMonth = $totalOrders / max(1, $monthsDiff);
    
    // On-time payment percentage
    $onTimePercentage = $paidCount > 0 ? (($paidCount - $latePayments) / $paidCount) * 100 : 100;
    
    echo "  Data: \n";
    echo "    - Total Orders: $totalOrders\n";
    echo "    - Avg Transaction: Rp " . number_format($avgTransactionValue, 0, ',', '.') . " (Rp " . round($avgTransactionJuta, 2) . " juta)\n";
    echo "    - Orders/Month: " . round($ordersPerMonth, 2) . "\n";
    echo "    - Late Payments: $latePayments / $paidCount\n";
    echo "    - On-time: " . round($onTimePercentage, 1) . "%\n";
    echo "    - Customer since: " . round($monthsAsCustomer, 2) . " years\n";
    
    // 3. SMART SCORING CALCULATION
    
    // Kriteria 1: Rata-rata Transaksi (Bobot 35%)
    if ($avgTransactionJuta > 100) $s1 = 20;
    elseif ($avgTransactionJuta >= 50) $s1 = 15;
    elseif ($avgTransactionJuta >= 25) $s1 = 10;
    elseif ($avgTransactionJuta >= 10) $s1 = 5;
    else $s1 = 0;
    $total1 = $s1 * 35;
    
    // Kriteria 2: Keterlambatan Bayar (Bobot 30%)
    if ($paidCount < 3) {
        $s2 = 0; // Belum cukup data
    } elseif ($latePayments == 0) {
        $s2 = 20;
    } elseif ($latePayments < 5) {
        $s2 = 15;
    } elseif ($latePayments < 15) {
        $s2 = 10;
    } else {
        $s2 = 5;
    }
    $total2 = $s2 * 30;
    
    // Kriteria 3: Frekuensi Transaksi (Bobot 20%)
    if ($ordersPerMonth > 10) $s3 = 20;
    elseif ($ordersPerMonth >= 5) $s3 = 15;
    elseif ($ordersPerMonth >= 2) $s3 = 10;
    elseif ($ordersPerMonth >= 1) $s3 = 5;
    else $s3 = 0;
    $total3 = $s3 * 20;
    
    // Kriteria 4: Lama Pelanggan (Bobot 15%)
    if ($monthsAsCustomer > 10) $s4 = 20;
    elseif ($monthsAsCustomer >= 5) $s4 = 15;
    elseif ($monthsAsCustomer >= 1) $s4 = 10;
    elseif ($monthsAsCustomer >= 0.5) $s4 = 5; // 6 bulan
    else $s4 = 0;
    $total4 = $s4 * 15;
    
    $totalScore = $total1 + $total2 + $total3 + $total4;
    
    // 4. Tentukan Credit Limit
    if ($totalScore <= 400) $creditLimit = 5000000;
    elseif ($totalScore <= 800) $creditLimit = 15000000;
    elseif ($totalScore <= 1200) $creditLimit = 30000000;
    elseif ($totalScore <= 1600) $creditLimit = 50000000;
    else $creditLimit = 100000000;
    
    echo "  Scoring:\n";
    echo "    S1 (Transaksi): $s1/20 × 35 = $total1 poin\n";
    echo "    S2 (Keterlambatan): $s2/20 × 30 = $total2 poin\n";
    echo "    S3 (Frekuensi): $s3/20 × 20 = $total3 poin\n";
    echo "    S4 (Loyalitas): $s4/20 × 15 = $total4 poin\n";
    echo "  TOTAL SCORE: $totalScore poin\n";
    echo "  NEW CREDIT LIMIT: Rp " . number_format($creditLimit, 0, ',', '.') . "\n";
    
    // 5. Update ke database
    $breakdown = json_encode([
        'kriteria_1' => ['score' => $s1, 'poin' => $total1, 'label' => 'Rata Transaksi', 'bobot' => 35],
        'kriteria_2' => ['score' => $s2, 'poin' => $total2, 'label' => 'Keterlambatan', 'bobot' => 30],
        'kriteria_3' => ['score' => $s3, 'poin' => $total3, 'label' => 'Frekuensi', 'bobot' => 20],
        'kriteria_4' => ['score' => $s4, 'poin' => $total4, 'label' => 'Lama Pelanggan', 'bobot' => 15]
    ]);
    
    $db->execute(
        "UPDATE customers SET total_score = :score, credit_limit = :limit, scoring_breakdown = :breakdown WHERE id = :id",
        [
            'score' => $totalScore,
            'limit' => $creditLimit,
            'breakdown' => $breakdown,
            'id' => $customerId
        ]
    );
    
    echo "  ✅ Score updated!\n\n";
}

echo "=== ALL DONE ===\n";
