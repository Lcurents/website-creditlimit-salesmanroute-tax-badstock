<?php
/**
 * NEW FINANCE PAGE with SQLite & FIFO Payment System
 * Improved Payment Allocation Logic
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$user = check_role(['AR_FINANCE', 'CASHIER']);
$db = Database::getInstance();

$msg = '';
$msg_type = 'success';

// ==========================================
// HANDLE PAYMENT SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)$_POST['customer_id'];
    $paymentAmount = (float)$_POST['amount'];
    
    if ($paymentAmount <= 0) {
        $msg = "Jumlah pembayaran harus lebih dari 0!";
        $msg_type = 'danger';
    } else {
        $db->beginTransaction();
        
        try {
            // 1. Insert Payment Record
            $db->execute("INSERT INTO payments (customer_id, amount, processed_by, payment_method) 
                          VALUES (:customer_id, :amount, :processed_by, :method)", [
                'customer_id' => $customerId,
                'amount' => $paymentAmount,
                'processed_by' => $user['id'],
                'method' => 'CASH'
            ]);
            
            $paymentId = $db->lastInsertId();
            
            // 2. Get Unpaid Orders (FIFO - oldest first)
            // Hanya ambil order yang masih DELIVERED (belum PAID)
            $unpaidOrders = $db->query(
                "SELECT o.* FROM orders o
                 WHERE o.customer_id = :customer_id 
                 AND o.status = 'DELIVERED' 
                 ORDER BY o.created_at ASC", 
                ['customer_id' => $customerId]
            );
            
            $remainingAmount = $paymentAmount;
            $paidOrdersCount = 0;
            
            // 3. Allocate Payment to Orders (FIFO)
            foreach ($unpaidOrders as $order) {
                if ($remainingAmount <= 0) break;
                
                $orderTotal = (float)$order['total_amount'];
                
                // Cek apakah order ini sudah pernah dibayar sebagian
                $previousPayments = $db->query(
                    "SELECT COALESCE(SUM(allocated_amount), 0) as total_paid 
                     FROM payment_allocations 
                     WHERE order_id = :order_id",
                    ['order_id' => $order['id']]
                );
                
                $totalPaid = (float)$previousPayments[0]['total_paid'];
                $orderOutstanding = $orderTotal - $totalPaid; // Sisa yang belum dibayar
                
                if ($orderOutstanding <= 0) {
                    // Sudah lunas, update status ke PAID
                    $db->execute("UPDATE orders SET status = 'PAID', paid_date = CURRENT_TIMESTAMP WHERE id = :id", [
                        'id' => $order['id']
                    ]);
                    continue; // Skip ke order berikutnya
                }
                
                // Tentukan berapa yang akan dialokasikan
                if ($remainingAmount >= $orderOutstanding) {
                    // Bisa melunasi order ini
                    $allocatedAmount = $orderOutstanding;
                    
                    // Update order status to PAID
                    $db->execute("UPDATE orders SET status = 'PAID', paid_date = CURRENT_TIMESTAMP WHERE id = :id", [
                        'id' => $order['id']
                    ]);
                    
                    $paidOrdersCount++;
                } else {
                    // Bayar sebagian (order tetap DELIVERED)
                    $allocatedAmount = $remainingAmount;
                }
                
                // Record allocation
                $db->execute("INSERT INTO payment_allocations (payment_id, order_id, allocated_amount) 
                              VALUES (:payment_id, :order_id, :amount)", [
                    'payment_id' => $paymentId,
                    'order_id' => $order['id'],
                    'amount' => $allocatedAmount
                ]);
                
                $remainingAmount -= $allocatedAmount;
            }
            
            // 4. Update Customer Debt
            $db->execute("UPDATE customers SET current_debt = current_debt - :amount WHERE id = :id", [
                'amount' => $paymentAmount,
                'id' => $customerId
            ]);
            
            // Ensure debt doesn't go negative
            $db->execute("UPDATE customers SET current_debt = 0 WHERE current_debt < 0 AND id = :id", [
                'id' => $customerId
            ]);
            
            $db->commit();
            
            // AUTO-UPDATE CUSTOMER SCORE SETELAH PEMBAYARAN
            require_once __DIR__ . '/includes/scoring.php';
            updateCustomerScore($db, $customerId);
            
            $msg = "✅ Pembayaran " . rupiah($paymentAmount) . " berhasil diterima. $paidOrdersCount faktur telah lunas (FIFO).";
            $msg_type = 'success';
            
        } catch (Exception $e) {
            $db->rollback();
            $msg = "❌ Error: " . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}

// ==========================================
// LOAD DATA
// ==========================================
$customers = $db->query("SELECT * FROM customers WHERE current_debt > 0 ORDER BY name");
$recentPayments = $db->query(
    "SELECT p.*, c.name as customer_name, u.fullname as processed_by_name 
     FROM payments p 
     LEFT JOIN customers c ON p.customer_id = c.id 
     LEFT JOIN users u ON p.processed_by = u.id 
     ORDER BY p.payment_date DESC 
     LIMIT 10"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan - Pelunasan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>💰 Keuangan & Penagihan (FIFO)</h1>
            <p>Pembayaran akan otomatis melunasi Faktur terlama terlebih dahulu</p>
            <?php if($msg): echo alert_message($msg, $msg_type); endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            
            <!-- FORM INPUT PEMBAYARAN -->
            <div class="card" style="border-top: 4px solid #28a745;">
                <h3>💵 Input Pelunasan / Setoran Sales</h3>
                <form method="POST">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Pilih Warung yang Bayar:</label>
                        <select name="customer_id" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= $c['name'] ?> | Hutang: <?= rupiah($c['current_debt']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Jumlah Uang yang Disetor:</label>
                        <input type="number" name="amount" placeholder="0" required min="1" 
                               style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                    </div>

                    <button type="submit" style="width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold;">
                        💾 SIMPAN PEMBAYARAN
                    </button>
                </form>
            </div>

            <!-- SUMMARY PIUTANG -->
            <div class="card" style="border-top: 4px solid #ffc107;">
                <h3>📊 Ringkasan Piutang</h3>
                
                <?php 
                $totalDebt = $db->query("SELECT SUM(current_debt) as total FROM customers")[0]['total'] ?? 0;
                $customersWithDebt = $db->query("SELECT COUNT(*) as total FROM customers WHERE current_debt > 0")[0]['total'] ?? 0;
                $overdueOrders = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'DELIVERED' AND due_date < DATE('now')")[0]['total'] ?? 0;
                ?>
                
                <div style="margin-bottom: 15px; padding: 15px; background: #fff; border: 2px solid #333;">
                    <div style="font-size: 12px; color: #856404; margin-bottom: 5px;">Total Piutang Tertunggak:</div>
                    <div style="font-size: 28px; font-weight: bold; color: #856404;"><?= rupiah($totalDebt) ?></div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1; padding: 10px; background: #f8f9fa; border-radius: 5px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?= $customersWithDebt ?></div>
                        <div style="font-size: 11px; color: #666;">Toko Berhutang</div>
                    </div>
                    <div style="flex: 1; padding: 10px; background: #f8f9fa; border-radius: 5px; text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?= $overdueOrders ?></div>
                        <div style="font-size: 11px; color: #666;">Faktur Jatuh Tempo</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE RECENT PAYMENTS -->
        <div class="card">
            <h3>🕒 10 Pembayaran Terakhir</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Jumlah Bayar</th>
                        <th>Diproses Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPayments)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999;">Belum ada pembayaran</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $payment): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($payment['payment_date'])) ?></td>
                            <td><strong><?= $payment['customer_name'] ?></strong></td>
                            <td><strong style="color: #28a745;"><?= rupiah($payment['amount']) ?></strong></td>
                            <td><?= $payment['processed_by_name'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TABLE CUSTOMER DEBTS -->
        <div class="card">
            <h3>📋 Daftar Pelanggan dengan Hutang</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Toko</th>
                        <th>Credit Limit</th>
                        <th>Hutang Saat Ini</th>
                        <th>Sisa Limit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $allCustomers = $db->query("SELECT * FROM customers ORDER BY current_debt DESC");
                    
                    foreach ($allCustomers as $customer): 
                        $sisaLimit = $customer['credit_limit'] - $customer['current_debt'];
                        $percentage = ($customer['current_debt'] / $customer['credit_limit']) * 100;
                        
                        if ($percentage >= 90) {
                            $statusColor = '#dc3545';
                            $statusText = 'KRITIS';
                        } elseif ($percentage >= 70) {
                            $statusColor = '#ffc107';
                            $statusText = 'WASPADA';
                        } else {
                            $statusColor = '#28a745';
                            $statusText = 'AMAN';
                        }
                    ?>
                    <tr>
                        <td><strong><?= $customer['name'] ?></strong></td>
                        <td><?= rupiah($customer['credit_limit']) ?></td>
                        <td><strong style="color: <?= $customer['current_debt'] > 0 ? '#dc3545' : '#666' ?>"><?= rupiah($customer['current_debt']) ?></strong></td>
                        <td><?= rupiah($sisaLimit) ?></td>
                        <td>
                            <span style="background: <?= $statusColor ?>; color: white; padding: 5px 10px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                <?= $statusText ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
