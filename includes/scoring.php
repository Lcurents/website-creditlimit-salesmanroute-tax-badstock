<?php
/**
 * Fungsi untuk auto-update customer scoring berdasarkan data transaksi real
 */

function updateCustomerScore($db, $customerId) {
    // 1. Ambil data transaksi customer
    $orders = $db->query(
        "SELECT * FROM orders WHERE customer_id = :id ORDER BY created_at ASC",
        ['id' => $customerId]
    );
    
    $totalOrders = count($orders);
    
    if ($totalOrders == 0) {
        // Pelanggan baru tanpa transaksi
        $db->execute(
            "UPDATE customers SET total_score = 0, credit_limit = 5000000, scoring_breakdown = :breakdown WHERE id = :id",
            [
                'breakdown' => json_encode([
                    'kriteria_1' => ['score' => 0, 'poin' => 0, 'label' => 'Rata Transaksi', 'bobot' => 35],
                    'kriteria_2' => ['score' => 0, 'poin' => 0, 'label' => 'Keterlambatan', 'bobot' => 30],
                    'kriteria_3' => ['score' => 0, 'poin' => 0, 'label' => 'Frekuensi', 'bobot' => 20],
                    'kriteria_4' => ['score' => 0, 'poin' => 0, 'label' => 'Lama Pelanggan', 'bobot' => 15]
                ]),
                'id' => $customerId
            ]
        );
        return;
    }
    
    // 2. Hitung metrics
    $totalValue = 0;
    $latePayments = 0;
    $paidCount = 0;
    
    foreach ($orders as $order) {
        $totalValue += $order['total_amount'];
        
        if ($order['status'] == 'PAID' && $order['paid_date']) {
            $paidCount++;
            if ($order['due_date'] && $order['paid_date'] > $order['due_date']) {
                $latePayments++;
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
    
    // 3. SMART SCORING CALCULATION
    
    // S1: Rata-rata Transaksi (35%)
    if ($avgTransactionJuta > 100) $s1 = 20;
    elseif ($avgTransactionJuta >= 50) $s1 = 15;
    elseif ($avgTransactionJuta >= 25) $s1 = 10;
    elseif ($avgTransactionJuta >= 10) $s1 = 5;
    else $s1 = 0;
    $total1 = $s1 * 35;
    
    // S2: Keterlambatan Bayar (30%)
    if ($paidCount < 3) {
        $s2 = 0;
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
    
    // S3: Frekuensi Transaksi (20%)
    if ($ordersPerMonth > 10) $s3 = 20;
    elseif ($ordersPerMonth >= 5) $s3 = 15;
    elseif ($ordersPerMonth >= 2) $s3 = 10;
    elseif ($ordersPerMonth >= 1) $s3 = 5;
    else $s3 = 0;
    $total3 = $s3 * 20;
    
    // S4: Lama Pelanggan (15%)
    if ($monthsAsCustomer > 10) $s4 = 20;
    elseif ($monthsAsCustomer >= 5) $s4 = 15;
    elseif ($monthsAsCustomer >= 1) $s4 = 10;
    elseif ($monthsAsCustomer >= 0.5) $s4 = 5;
    else $s4 = 0;
    $total4 = $s4 * 15;
    
    $totalScore = $total1 + $total2 + $total3 + $total4;
    
    // 4. Tentukan Credit Limit
    if ($totalScore <= 400) $creditLimit = 5000000;
    elseif ($totalScore <= 800) $creditLimit = 15000000;
    elseif ($totalScore <= 1200) $creditLimit = 30000000;
    elseif ($totalScore <= 1600) $creditLimit = 50000000;
    else $creditLimit = 100000000;
    
    // 5. Update database
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
}
