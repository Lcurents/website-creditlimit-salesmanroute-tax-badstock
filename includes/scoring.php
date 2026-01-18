<?php
/**
 * Fungsi untuk auto-update customer scoring berdasarkan data transaksi real
 * FORMULA SESUAI DIAGRAM ACTIVITY:
 * S1 (Frekuensi Order) × 10 = 0-200 poin
 * S2 (Nilai Transaksi) × 30 = 0-600 poin
 * S3 (Riwayat Pembayaran %) × 20 = 0-400 poin
 * S4 (Lama Kerjasama) × 40 = 0-800 poin
 * TOTAL = 0-2000 poin
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
                    'kriteria_1' => ['score' => 0, 'poin' => 0, 'label' => 'Frekuensi Order', 'bobot' => 10],
                    'kriteria_2' => ['score' => 0, 'poin' => 0, 'label' => 'Nilai Transaksi', 'bobot' => 30],
                    'kriteria_3' => ['score' => 0, 'poin' => 0, 'label' => 'Riwayat Pembayaran', 'bobot' => 20],
                    'kriteria_4' => ['score' => 0, 'poin' => 0, 'label' => 'Lama Kerjasama', 'bobot' => 40]
                ]),
                'id' => $customerId
            ]
        );
        return;
    }
    
    // 2. Hitung metrics
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
    
    // 3. SMART SCORING CALCULATION (SESUAI DIAGRAM)
    
    // S1: Frekuensi Order (Bobot 10 = Max 200 poin)
    if ($ordersPerMonth >= 6) $s1 = 20;     // 6+ order/bulan = 20 poin
    elseif ($ordersPerMonth >= 3) $s1 = 10; // 3-5 order/bulan = 10 poin
    else $s1 = 0;                           // 0-2 order/bulan = 0 poin
    $total1 = $s1 * 10;
    
    // S2: Nilai Transaksi (Bobot 30 = Max 600 poin)
    if ($avgTransactionJuta > 15) $s2 = 20;      // > Rp 15 juta = 20 poin
    elseif ($avgTransactionJuta >= 5) $s2 = 10;  // Rp 5-15 juta = 10 poin
    else $s2 = 0;                                // < Rp 5 juta = 0 poin
    $total2 = $s2 * 30;
    
    // S3: Riwayat Pembayaran % (Bobot 20 = Max 400 poin)
    if ($onTimePercent >= 80) $s3 = 20;     // On-time ≥ 80% = 20 poin
    elseif ($onTimePercent >= 50) $s3 = 10; // On-time 50-79% = 10 poin
    else $s3 = 0;                           // On-time < 50% = 0 poin
    $total3 = $s3 * 20;
    
    // S4: Lama Kerjasama (Bobot 40 = Max 800 poin)
    if ($monthsAsCustomer > 1) $s4 = 20;        // > 1 tahun = 20 poin
    elseif ($monthsAsCustomer >= 0.5) $s4 = 10; // 6-12 bulan = 10 poin
    else $s4 = 0;                               // < 6 bulan = 0 poin
    $total4 = $s4 * 40;
    
    $totalScore = $total1 + $total2 + $total3 + $total4;
    
    // 4. Tentukan Credit Limit
    if ($totalScore <= 400) $creditLimit = 5000000;
    elseif ($totalScore <= 800) $creditLimit = 15000000;
    elseif ($totalScore <= 1200) $creditLimit = 30000000;
    elseif ($totalScore <= 1600) $creditLimit = 50000000;
    else $creditLimit = 100000000;
    
    // 5. Update database
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
            'id' => $customerId
        ]
    );
}
