<?php
/**
 * Database Setup & Migration Script
 * Jalankan file ini untuk membuat database dan migrate data dari JSON
 */

require_once __DIR__ . '/../config/database.php';

echo "==============================================\n";
echo "DATABASE SETUP & MIGRATION\n";
echo "==============================================\n\n";

$db = Database::getInstance()->getConnection();

// 1. BUAT TABLES dari schema.sql
echo "1. Creating database tables...\n";
$schema = file_get_contents(__DIR__ . '/../config/schema.sql');
$db->exec($schema);
echo "   ✓ Tables created successfully!\n\n";

// 2. MIGRATE DATA dari JSON ke SQLite
echo "2. Migrating data from JSON files...\n";

// 2A. MIGRATE USERS
echo "   - Migrating users...\n";
$users_json = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
foreach ($users_json as $user) {
    // Hash password
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (id, username, password, role, fullname) 
            VALUES (:id, :username, :password, :role, :fullname)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':username', $user['username'], SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
    $stmt->bindValue(':role', $user['role'], SQLITE3_TEXT);
    $stmt->bindValue(':fullname', $user['fullname'], SQLITE3_TEXT);
    $stmt->execute();
}
echo "     ✓ Users migrated (" . count($users_json) . " records)\n";

// 2B. MIGRATE CUSTOMERS
echo "   - Migrating customers...\n";
$customers_json = json_decode(file_get_contents(__DIR__ . '/../data/customers.json'), true);
foreach ($customers_json as $customer) {
    $sql = "INSERT INTO customers (id, name, address, phone, credit_limit, current_debt, total_score, scoring_breakdown) 
            VALUES (:id, :name, :address, :phone, :limit, :debt, :score, :breakdown)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $customer['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':name', $customer['name'], SQLITE3_TEXT);
    $stmt->bindValue(':address', $customer['address'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':phone', $customer['phone'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':limit', $customer['limit'], SQLITE3_FLOAT);
    $stmt->bindValue(':debt', $customer['debt'], SQLITE3_FLOAT);
    $stmt->bindValue(':score', $customer['score_data'] ?? 0, SQLITE3_INTEGER);
    $stmt->bindValue(':breakdown', $customer['scoring_breakdown'] ?? null, SQLITE3_TEXT);
    $stmt->execute();
}
echo "     ✓ Customers migrated (" . count($customers_json) . " records)\n";

// 2C. MIGRATE PRODUCTS
echo "   - Migrating products...\n";
$products_json = json_decode(file_get_contents(__DIR__ . '/../data/products.json'), true);
foreach ($products_json as $product) {
    $sql = "INSERT INTO products (id, name, price, stock, unit) 
            VALUES (:id, :name, :price, :stock, :unit)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $product['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':name', $product['name'], SQLITE3_TEXT);
    $stmt->bindValue(':price', $product['price'], SQLITE3_FLOAT);
    $stmt->bindValue(':stock', $product['stock'], SQLITE3_INTEGER);
    $stmt->bindValue(':unit', $product['unit'] ?? 'PCS', SQLITE3_TEXT);
    $stmt->execute();
}
echo "     ✓ Products migrated (" . count($products_json) . " records)\n";

// 2D. MIGRATE ORDERS (simplified - karena struktur JSON order kompleks)
echo "   - Migrating orders...\n";
$orders_json = json_decode(file_get_contents(__DIR__ . '/../data/orders.json'), true);
foreach ($orders_json as $order) {
    // Insert order (gunakan 'date' sebagai created_at)
    $sql = "INSERT INTO orders (id, created_at, due_date, delivered_at, paid_date, customer_id, total_amount, status) 
            VALUES (:id, :created_at, :due_date, :delivered_at, :paid_date, :customer_id, :total, :status)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $order['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':created_at', $order['date'], SQLITE3_TEXT);
    $stmt->bindValue(':due_date', $order['due_date'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':delivered_at', $order['delivered_date'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':paid_date', $order['paid_date'] ?? null, SQLITE3_TEXT);
    $stmt->bindValue(':customer_id', $order['customer_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':total', $order['total'], SQLITE3_FLOAT);
    $stmt->bindValue(':status', $order['status'], SQLITE3_TEXT);
    $stmt->execute();
    
    // Insert order items (simplified - ambil dari product_id jika bukan "MULTI")
    if (isset($order['product_id']) && $order['product_id'] !== 'MULTI') {
        $sql_item = "INSERT INTO order_items (order_id, product_id, qty, unit_price, subtotal) 
                     VALUES (:order_id, :product_id, :qty, :unit_price, :subtotal)";
        
        $stmt_item = $db->prepare($sql_item);
        $stmt_item->bindValue(':order_id', $order['id'], SQLITE3_INTEGER);
        $stmt_item->bindValue(':product_id', $order['product_id'], SQLITE3_INTEGER);
        $stmt_item->bindValue(':qty', $order['qty'] ?? 1, SQLITE3_INTEGER);
        $stmt_item->bindValue(':unit_price', $order['total'], SQLITE3_FLOAT);
        $stmt_item->bindValue(':subtotal', $order['total'], SQLITE3_FLOAT);
        $stmt_item->execute();
    }
}
echo "     ✓ Orders migrated (" . count($orders_json) . " records)\n";

// 2E. MIGRATE CARS
if (file_exists(__DIR__ . '/../data/cars.json')) {
    echo "   - Migrating cars...\n";
    $cars_json = json_decode(file_get_contents(__DIR__ . '/../data/cars.json'), true);
    foreach ($cars_json as $car) {
        $sql = "INSERT INTO cars (id, license_plate, vehicle_name, driver_name, tax_due_date, proof_image, validation_status) 
                VALUES (:id, :plate, :name, :driver, :tax_date, :proof, :status)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $car['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':plate', $car['plat'], SQLITE3_TEXT);
        $stmt->bindValue(':name', $car['nama'], SQLITE3_TEXT);
        $stmt->bindValue(':driver', $car['driver'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':tax_date', $car['tgl_pajak'], SQLITE3_TEXT);
        $stmt->bindValue(':proof', $car['bukti_img'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':status', $car['status_validasi'] ?? 'NONE', SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "     ✓ Cars migrated (" . count($cars_json) . " records)\n";
}

// 2F. MIGRATE SCHEDULES
if (file_exists(__DIR__ . '/../data/salesman_schedules.json')) {
    echo "   - Migrating salesman schedules...\n";
    $schedules_json = json_decode(file_get_contents(__DIR__ . '/../data/salesman_schedules.json'), true);
    foreach ($schedules_json as $schedule) {
        $sql = "INSERT INTO salesman_schedules (id, salesman_id, customer_id, visit_date, sequence, created_by) 
                VALUES (:id, :salesman_id, :customer_id, :visit_date, :sequence, :created_by)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $schedule['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':salesman_id', $schedule['salesman_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':customer_id', $schedule['customer_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':visit_date', $schedule['visit_date'], SQLITE3_TEXT);
        $stmt->bindValue(':sequence', $schedule['sequence'] ?? 1, SQLITE3_INTEGER);
        $stmt->bindValue(':created_by', $schedule['created_by'] ?? 'SYSTEM', SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "     ✓ Schedules migrated (" . count($schedules_json) . " records)\n";
}

// 2G. MIGRATE BADSTOCK
if (file_exists(__DIR__ . '/../data/badstock_claims.json')) {
    echo "   - Migrating badstock claims...\n";
    $claims_json = json_decode(file_get_contents(__DIR__ . '/../data/badstock_claims.json'), true);
    foreach ($claims_json as $claim) {
        $sql = "INSERT INTO badstock_claims (claim_code, claim_date, customer_id, product_id, qty, reason, proof_image, status, salesman_id) 
                VALUES (:code, :date, :customer_id, :product_id, :qty, :reason, :proof, :status, :salesman_id)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':code', $claim['id'], SQLITE3_TEXT);
        $stmt->bindValue(':date', $claim['date'], SQLITE3_TEXT);
        $stmt->bindValue(':customer_id', $claim['customer_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':product_id', $claim['product_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':qty', $claim['qty'], SQLITE3_INTEGER);
        $stmt->bindValue(':reason', $claim['reason'], SQLITE3_TEXT);
        $stmt->bindValue(':proof', $claim['proof_image'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':status', $claim['status'], SQLITE3_TEXT);
        $stmt->bindValue(':salesman_id', $claim['salesman'] ?? 1, SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo "     ✓ Badstock claims migrated (" . count($claims_json) . " records)\n";
}

if (file_exists(__DIR__ . '/../data/badstock_inventory.json')) {
    echo "   - Migrating badstock inventory...\n";
    $inventory_json = json_decode(file_get_contents(__DIR__ . '/../data/badstock_inventory.json'), true);
    foreach ($inventory_json as $item) {
        $sql = "INSERT INTO badstock_inventory (product_id, qty) 
                VALUES (:product_id, :qty)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':product_id', $item['product_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':qty', $item['qty'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo "     ✓ Badstock inventory migrated (" . count($inventory_json) . " records)\n";
}

echo "\n==============================================\n";
echo "✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
echo "==============================================\n";
echo "\nDatabase location: " . __DIR__ . "/../database/distribusi.db\n";
echo "\nNOTE: Password semua user sudah di-hash.\n";
echo "Login credentials tetap sama (username/password dari JSON lama).\n\n";
