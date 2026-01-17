<?php
/**
 * NEW DISTRIBUTION PAGE with SQLite
 * Multi-item Order with Real-time Credit Limit Check
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$user = check_login();
$db = Database::getInstance();

$msg = '';
$msg_type = 'success';

// ==========================================
// 1. HANDLE CREATE ORDER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_order') {
    
    if ($user['role'] !== 'FAKTURIS') {
        $msg = 'Akses ditolak! Hanya Fakturis yang bisa input order.';
        $msg_type = 'danger';
    } else {
        $customerId = (int)$_POST['customer_id'];
        $productIds = $_POST['product_id'];
        $quantities = $_POST['qty'];
        
        $db->beginTransaction();
        
        try {
            // 1. Validate & Calculate Total
            $grandTotal = 0;
            $orderItems = [];
            
            foreach ($productIds as $index => $productId) {
                $qty = (int)$quantities[$index];
                
                if ($qty > 0) {
                    // Get product data
                    $product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $productId])[0];
                    
                    // Check stock
                    if ($product['stock'] < $qty) {
                        throw new Exception("Stok tidak cukup untuk produk: {$product['name']}");
                    }
                    
                    $subtotal = $product['price'] * $qty;
                    $grandTotal += $subtotal;
                    
                    $orderItems[] = [
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'qty' => $qty,
                        'unit_price' => $product['price'],
                        'subtotal' => $subtotal
                    ];
                }
            }
            
            if (empty($orderItems)) {
                throw new Exception("Tidak ada item yang dipilih!");
            }
            
            // 2. Check Credit Limit
            $customer = $db->query("SELECT * FROM customers WHERE id = :id", ['id' => $customerId])[0];
            $futureDebt = $customer['current_debt'] + $grandTotal;
            
            if ($futureDebt > $customer['credit_limit']) {
                $status = 'ON HOLD';
                $msg = "⚠️ Order dibuat tapi ON HOLD karena melebihi limit kredit. Butuh approval Finance.";
                $msg_type = 'warning';
            } else {
                $status = 'APPROVED';
                
                // Update customer debt
                $db->execute("UPDATE customers SET current_debt = current_debt + :amount WHERE id = :id", [
                    'amount' => $grandTotal,
                    'id' => $customerId
                ]);
                
                // Update product stock
                foreach ($orderItems as $item) {
                    $db->execute("UPDATE products SET stock = stock - :qty WHERE id = :id", [
                        'qty' => $item['qty'],
                        'id' => $item['product_id']
                    ]);
                }
                
                $msg = "✅ Order berhasil dibuat dan langsung APPROVED!";
                $msg_type = 'success';
            }
            
            // 3. Insert Order
            $dueDate = date('Y-m-d', strtotime('+7 days'));
            
            $db->execute("INSERT INTO orders (customer_id, total_amount, status, due_date, created_by) 
                          VALUES (:customer_id, :total, :status, :due_date, :created_by)", [
                'customer_id' => $customerId,
                'total' => $grandTotal,
                'status' => $status,
                'due_date' => $dueDate,
                'created_by' => $user['id']
            ]);
            
            $orderId = $db->lastInsertId();
            
            // 4. Insert Order Items
            foreach ($orderItems as $item) {
                $db->execute("INSERT INTO order_items (order_id, product_id, qty, unit_price, subtotal) 
                              VALUES (:order_id, :product_id, :qty, :unit_price, :subtotal)", [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal']
                ]);
            }
            
            $db->commit();
            
            // AUTO-UPDATE CUSTOMER SCORE SETELAH ORDER BARU
            require_once __DIR__ . '/includes/scoring.php';
            updateCustomerScore($db, $customerId);
            
        } catch (Exception $e) {
            $db->rollback();
            $msg = "❌ Error: " . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}

// ==========================================
// 2. HANDLE APPROVE ORDER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'approve_order') {
    
    if ($user['role'] !== 'AR_FINANCE') {
        $msg = 'Hanya Finance yang bisa approve order!';
        $msg_type = 'danger';
    } else {
        $orderId = (int)$_POST['order_id'];
        
        $db->beginTransaction();
        
        try {
            $order = $db->query("SELECT * FROM orders WHERE id = :id AND status = 'ON HOLD'", ['id' => $orderId])[0];
            
            if ($order) {
                // ✅ VALIDASI STOK ULANG (Mencegah stok negatif)
                $items = $db->query("SELECT * FROM order_items WHERE order_id = :order_id", ['order_id' => $orderId]);
                
                foreach ($items as $item) {
                    $product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $item['product_id']])[0];
                    
                    if ($product['stock'] < $item['qty']) {
                        throw new Exception("Stok tidak cukup untuk {$product['name']}! Tersedia: {$product['stock']}, Dibutuhkan: {$item['qty']}");
                    }
                }
                
                // Update order status
                $db->execute("UPDATE orders SET status = 'APPROVED', approved_by = :approved_by WHERE id = :id", [
                    'approved_by' => $user['id'],
                    'id' => $orderId
                ]);
                
                // Update customer debt
                $db->execute("UPDATE customers SET current_debt = current_debt + :amount WHERE id = :id", [
                    'amount' => $order['total_amount'],
                    'id' => $order['customer_id']
                ]);
                
                // Update stock (get items and reduce)
                $items = $db->query("SELECT * FROM order_items WHERE order_id = :order_id", ['order_id' => $orderId]);
                
                foreach ($items as $item) {
                    $db->execute("UPDATE products SET stock = stock - :qty WHERE id = :id", [
                        'qty' => $item['qty'],
                        'id' => $item['product_id']
                    ]);
                }
                
                $db->commit();
                $msg = "✅ Order berhasil di-APPROVE! Stok telah dikurangi.";
                $msg_type = 'success';
            }
            
        } catch (Exception $e) {
            $db->rollback();
            $msg = "Error: " . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}

// ==========================================
// 3. HANDLE CONFIRM DELIVERY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'confirm_delivery') {
    $orderId = (int)$_POST['order_id'];
    
    $db->execute("UPDATE orders SET status = 'DELIVERED', delivered_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'APPROVED'", ['id' => $orderId]);
    
    $msg = "✅ Status order diubah menjadi DELIVERED!";
    $msg_type = 'success';
}

// ==========================================
// LOAD DATA FOR VIEW
// ==========================================
$customers = $db->query("SELECT * FROM customers ORDER BY name");
$products = $db->query("SELECT * FROM products ORDER BY name");
$orders = $db->query("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi - Sales Order</title>
    <link rel="stylesheet" href="style.css">
    <script>
        const products = <?= json_encode($products) ?>;
        const customers = <?= json_encode($customers) ?>;

        function updateCalculation() {
            let custId = document.getElementById('customer_select').value;
            let limitInfo = document.getElementById('limit_indicator');
            let totalDisplay = document.getElementById('grand_total_display');
            
            if(!custId) { 
                limitInfo.innerHTML = "Pilih Customer dulu"; 
                limitInfo.style.backgroundColor = "#ccc";
                return; 
            }

            let cust = customers.find(c => c.id == custId);
            let sisaLimit = cust.credit_limit - cust.current_debt;

            let totalBelanja = 0;
            let rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                let pId = row.querySelector('.p-select').value;
                let qty = row.querySelector('.q-input').value;
                if(pId && qty) {
                    let prod = products.find(p => p.id == pId);
                    totalBelanja += (prod.price * qty);
                }
            });

            totalDisplay.innerText = "Rp " + totalBelanja.toLocaleString('id-ID');

            if (totalBelanja > sisaLimit) {
                limitInfo.innerHTML = "⚠️ OVER LIMIT (Kurang Rp " + (totalBelanja - sisaLimit).toLocaleString('id-ID') + ")";
                limitInfo.style.backgroundColor = "#dc3545";
                limitInfo.style.color = "white";
            } else {
                limitInfo.innerHTML = "✅ AMAN (Sisa Limit: Rp " + (sisaLimit - totalBelanja).toLocaleString('id-ID') + ")";
                limitInfo.style.backgroundColor = "#28a745";
                limitInfo.style.color = "white";
            }
        }

        function addRow() {
            let container = document.getElementById('items_container');
            let div = document.createElement('div');
            div.className = 'item-row';
            div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px;';
            
            let html = `
                <select name="product_id[]" class="p-select" onchange="updateCalculation()" required style="flex:2; padding:8px;">
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= $p['name'] ?> @ <?= rupiah($p['price']) ?> (Stok: <?= $p['stock'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="qty[]" class="q-input" placeholder="Qty" oninput="updateCalculation()" required style="width:100px; padding:8px;" min="1">
                <button type="button" onclick="this.parentElement.remove(); updateCalculation()" style="background:#333; color:#fff; padding:8px 15px; border:none; cursor:pointer;">Hapus</button>
            `;
            div.innerHTML = html;
            container.appendChild(div);
        }
    </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content">
        <div class="header-box">
            <h1>📦 Distribusi & Logistik</h1>
            <p>Input Pesanan Multi-Item dengan Cek Limit Realtime</p>
            <?php if($msg): echo alert_message($msg, $msg_type); endif; ?>
        </div>

        <?php if($user['role'] == 'FAKTURIS'): ?>
        <div class="card" style="border-top: 5px solid #333;">
            <h3>➕ Input Pesanan Baru</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_order">
                
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px dashed #ccc;">
                    <label style="font-weight: bold; display: block; margin-bottom: 8px;">1. Pilih Pelanggan:</label>
                    <select name="customer_id" id="customer_select" onchange="updateCalculation()" required style="width:100%; padding:10px;">
                        <option value="">-- Pilih Warung/Toko --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= $c['name'] ?> (Limit: <?= rupiah($c['credit_limit']) ?> | Hutang: <?= rupiah($c['current_debt']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <span id="limit_indicator" style="display: inline-block; margin-top: 10px; padding: 10px 15px; background: #ccc; color: white; border-radius: 4px; font-weight: bold;">
                        Pilih pelanggan dulu...
                    </span>
                </div>

                <label style="font-weight: bold; display: block; margin-bottom: 10px;">2. Daftar Barang Belanjaan:</label>
                <div id="items_container" style="background: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                    <div class="item-row" style="display:flex; gap:10px; margin-bottom:10px;">
                        <select name="product_id[]" class="p-select" onchange="updateCalculation()" required style="flex:2; padding:8px;">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['name'] ?> @ <?= rupiah($p['price']) ?> (Stok: <?= $p['stock'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="qty[]" class="q-input" placeholder="Qty" oninput="updateCalculation()" required style="width:100px; padding:8px;" min="1">
                        <button type="button" disabled style="background:#ccc; padding:8px 15px; border:none;">Hapus</button>
                    </div>
                </div>

                <button type="button" onclick="addRow()" style="background: #333; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px;">
                    ➕ Tambah Barang Lain
                </button>

                <div style="text-align: right; border-top: 3px solid #333; padding-top: 15px;">
                    <h3>Total Estimasi: <span id="grand_total_display" style="color: #28a745;">Rp 0</span></h3>
                    <button type="submit" style="padding: 15px 40px; font-size: 16px; background: #333; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        PROSES PESANAN
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>📋 Monitoring Pesanan</h3>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Pelanggan</th>
                        <th>Detail Barang</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): 
                        // Get order items
                        $items = $db->query("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :id", ['id' => $o['id']]);
                        $itemsText = [];
                        foreach ($items as $item) {
                            $itemsText[] = $item['product_name'] . " (x{$item['qty']})";
                        }
                    ?>
                    <tr>
                        <td><?= status_badge($o['status']) ?></td>
                        <td>
                            <strong><?= $o['customer_name'] ?></strong><br>
                            <small style="color: #999;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></small>
                        </td>
                        <td style="font-size: 12px; max-width: 300px;">
                            <?= implode(', ', $itemsText) ?>
                        </td>
                        <td><strong><?= rupiah($o['total_amount']) ?></strong></td>
                        <td>
                            <?php if($o['status'] == 'ON HOLD' && $user['role'] == 'AR_FINANCE'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="approve_order">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <button type="submit" style="background:#28a745; color:white; padding:6px 12px; border:none; border-radius:3px; cursor:pointer; font-size:11px;">
                                        ✓ APPROVE
                                    </button>
                                </form>
                            <?php elseif($o['status'] == 'APPROVED'): ?>
                                <a href="print_sj.php?id=<?= $o['id'] ?>" target="_blank" style="background:#007bff; color:white; padding:6px 12px; text-decoration:none; border-radius:3px; font-size:11px;">
                                    🖨️ Cetak SJ
                                </a><br>
                                <form method="POST" onsubmit="return confirm('Barang sudah sampai?');" style="margin-top:5px;">
                                    <input type="hidden" name="action" value="confirm_delivery">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <button type="submit" style="background:#fff; color:#333; padding:6px 12px; border:2px solid #333; cursor:pointer; font-size:11px;">
                                        ✓ CONFIRM
                                    </button>
                                </form>
                            <?php elseif($o['status'] == 'DELIVERED'): ?>
                                <a href="print_invoice.php?id=<?= $o['id'] ?>" target="_blank" style="background:#fff; color:#333; padding:6px 12px; text-decoration:none; border:1px solid #333; border-radius:3px; font-size:11px;">
                                    📄 Faktur
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
