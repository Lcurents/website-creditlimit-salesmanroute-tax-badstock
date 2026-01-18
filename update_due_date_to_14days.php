<?php
/**
 * SCRIPT PERBAIKAN: Update Due Date dari 7 hari menjadi 14 hari
 * 
 * Script ini akan mengupdate semua order yang:
 * - Status: APPROVED, ON HOLD, atau DELIVERED (belum lunas)
 * - Due date masih menggunakan tempo 7 hari
 * - Mengubahnya menjadi 14 hari dari tanggal order dibuat
 * 
 * Cara menjalankan:
 * php update_due_date_to_14days.php
 */

require_once __DIR__ . '/config/database.php';

echo "========================================\n";
echo "UPDATE DUE DATE: 7 DAYS → 14 DAYS\n";
echo "========================================\n\n";

$db = Database::getInstance();

try {
    // 1. Cek berapa order yang akan diupdate
    $toUpdate = $db->query("
        SELECT COUNT(*) as total
        FROM orders 
        WHERE status IN ('APPROVED', 'ON HOLD', 'DELIVERED')
          AND due_date IS NOT NULL
          AND due_date = date(created_at, '+7 days')
    ");
    
    $count = $toUpdate[0]['total'];
    
    if ($count == 0) {
        echo "✅ Tidak ada order yang perlu diupdate.\n";
        echo "   Semua order sudah menggunakan tempo 14 hari atau sudah PAID.\n\n";
        exit(0);
    }
    
    echo "📋 Ditemukan {$count} order yang perlu diperbaiki.\n\n";
    
    // 2. Tampilkan preview
    echo "Preview Order yang akan diupdate:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-8s %-20s %-12s %-12s %-12s\n", "ID", "Customer", "Created", "Old Due", "New Due");
    echo str_repeat("-", 80) . "\n";
    
    $preview = $db->query("
        SELECT 
            o.id,
            c.name as customer_name,
            date(o.created_at) as created,
            o.due_date as old_due,
            date(o.created_at, '+14 days') as new_due,
            o.status
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.status IN ('APPROVED', 'ON HOLD', 'DELIVERED')
          AND o.due_date IS NOT NULL
          AND o.due_date = date(o.created_at, '+7 days')
        ORDER BY o.id
    ");
    
    foreach ($preview as $row) {
        printf(
            "%-8s %-20s %-12s %-12s %-12s\n",
            $row['id'],
            substr($row['customer_name'], 0, 20),
            $row['created'],
            $row['old_due'],
            $row['new_due']
        );
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    // 3. Konfirmasi
    echo "Lanjutkan update? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirm = trim(strtolower($line));
    fclose($handle);
    
    if ($confirm !== 'y' && $confirm !== 'yes') {
        echo "\n❌ Update dibatalkan.\n\n";
        exit(0);
    }
    
    // 4. Execute update
    echo "\n🔄 Memproses update...\n";
    
    $db->execute("
        UPDATE orders 
        SET due_date = date(created_at, '+14 days')
        WHERE status IN ('APPROVED', 'ON HOLD', 'DELIVERED')
          AND due_date IS NOT NULL
          AND due_date = date(created_at, '+7 days')
    ");
    
    echo "✅ Berhasil mengupdate {$count} order!\n";
    echo "\n📊 Ringkasan:\n";
    echo "   - Tempo lama: 7 hari\n";
    echo "   - Tempo baru: 14 hari (2 minggu)\n";
    echo "   - Total order diupdate: {$count}\n\n";
    
    // 5. Verifikasi
    $verify = $db->query("
        SELECT COUNT(*) as remaining
        FROM orders 
        WHERE status IN ('APPROVED', 'ON HOLD', 'DELIVERED')
          AND due_date IS NOT NULL
          AND due_date = date(created_at, '+7 days')
    ");
    
    if ($verify[0]['remaining'] == 0) {
        echo "✅ VERIFIKASI: Semua order sudah menggunakan tempo 14 hari.\n\n";
    } else {
        echo "⚠️ WARNING: Masih ada {$verify[0]['remaining']} order yang belum terupdate.\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "========================================\n";
echo "SELESAI\n";
echo "========================================\n";
