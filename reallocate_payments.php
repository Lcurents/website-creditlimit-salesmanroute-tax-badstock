<?php
/**
 * Script untuk realokasi ulang semua pembayaran dengan benar (FIFO)
 */
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();
$customerId = 1764216386; // Customer Gultom

echo "=== REALLOCATE PAYMENTS (FIFO) ===\n\n";

// 1. Hapus semua alokasi lama
$db->execute("DELETE FROM payment_allocations WHERE payment_id IN (SELECT id FROM payments WHERE customer_id = :cid)", ['cid' => $customerId]);
echo "✅ Old allocations deleted\n\n";

// 2. Ambil semua pembayaran (urut dari lama)
$payments = $db->query("SELECT * FROM payments WHERE customer_id = :cid ORDER BY payment_date ASC", ['cid' => $customerId]);

// 3. Ambil semua order yang perlu dibayar (DELIVERED)
$orders = $db->query("SELECT * FROM orders WHERE customer_id = :cid AND status IN ('DELIVERED', 'APPROVED') ORDER BY created_at ASC", ['cid' => $customerId]);

echo "Total payments: " . count($payments) . "\n";
echo "Total orders to pay: " . count($orders) . "\n\n";

$orderIndex = 0;
$orderRemaining = count($orders) > 0 ? (float)$orders[0]['total_amount'] : 0;

foreach ($payments as $payment) {
    $paymentAmount = (float)$payment['amount'];
    echo "Processing payment #{$payment['id']}: Rp " . number_format($paymentAmount, 0, ',', '.') . "\n";
    
    $remainingPayment = $paymentAmount;
    
    while ($remainingPayment > 0 && $orderIndex < count($orders)) {
        $currentOrder = $orders[$orderIndex];
        
        if ($remainingPayment >= $orderRemaining) {
            // Lunas order ini
            $allocated = $orderRemaining;
            
            // Insert allocation
            $db->execute("INSERT INTO payment_allocations (payment_id, order_id, allocated_amount) VALUES (:pid, :oid, :amt)", [
                'pid' => $payment['id'],
                'oid' => $currentOrder['id'],
                'amt' => $allocated
            ]);
            
            // Update order ke PAID
            $db->execute("UPDATE orders SET status = 'PAID', paid_date = CURRENT_TIMESTAMP WHERE id = :id", ['id' => $currentOrder['id']]);
            
            echo "  → Order #{$currentOrder['id']} PAID: Rp " . number_format($allocated, 0, ',', '.') . "\n";
            
            $remainingPayment -= $allocated;
            $orderIndex++;
            $orderRemaining = $orderIndex < count($orders) ? (float)$orders[$orderIndex]['total_amount'] : 0;
        } else {
            // Bayar sebagian
            $allocated = $remainingPayment;
            
            $db->execute("INSERT INTO payment_allocations (payment_id, order_id, allocated_amount) VALUES (:pid, :oid, :amt)", [
                'pid' => $payment['id'],
                'oid' => $currentOrder['id'],
                'amt' => $allocated
            ]);
            
            echo "  → Order #{$currentOrder['id']} partial: Rp " . number_format($allocated, 0, ',', '.') . " (outstanding: Rp " . number_format($orderRemaining - $allocated, 0, ',', '.') . ")\n";
            
            $orderRemaining -= $allocated;
            $remainingPayment = 0;
        }
    }
    
    if ($remainingPayment > 0) {
        echo "  ℹ️  Overpayment: Rp " . number_format($remainingPayment, 0, ',', '.') . " (deposit/kredit)\n";
    }
    
    echo "\n";
}

echo "\n=== DONE ===\n";
