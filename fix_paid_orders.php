<?php
/**
 * Script untuk memperbaiki order yang sudah lunas tapi masih berstatus DELIVERED
 */
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

echo "=== FIX PAID ORDERS SCRIPT ===\n\n";

// Cari semua order DELIVERED
$deliveredOrders = $db->query("SELECT * FROM orders WHERE status = 'DELIVERED' ORDER BY created_at ASC");

$fixedCount = 0;

foreach ($deliveredOrders as $order) {
    // Hitung total yang sudah dibayar untuk order ini
    $payments = $db->query(
        "SELECT COALESCE(SUM(allocated_amount), 0) as total_paid 
         FROM payment_allocations 
         WHERE order_id = :order_id",
        ['order_id' => $order['id']]
    );
    
    $totalPaid = (float)$payments[0]['total_paid'];
    $orderTotal = (float)$order['total_amount'];
    
    echo "Order #{$order['id']} - Total: Rp " . number_format($orderTotal, 0, ',', '.') . 
         " | Paid: Rp " . number_format($totalPaid, 0, ',', '.') . "\n";
    
    // Jika sudah lunas atau bahkan lebih, update ke PAID
    if ($totalPaid >= $orderTotal) {
        $db->execute(
            "UPDATE orders SET status = 'PAID', paid_date = CURRENT_TIMESTAMP WHERE id = :id",
            ['id' => $order['id']]
        );
        echo "  ✅ Status updated to PAID\n";
        $fixedCount++;
    } else {
        echo "  ⚠️  Outstanding: Rp " . number_format($orderTotal - $totalPaid, 0, ',', '.') . "\n";
    }
    
    echo "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total orders fixed: $fixedCount\n";
echo "Done!\n";
