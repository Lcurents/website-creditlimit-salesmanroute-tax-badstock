<?php
/**
 * UPDATE CUSTOMER LAMA DENGAN BREAKDOWN
 * Generate scoring breakdown untuk customer yang belum punya
 */

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

// Fungsi Smart Scoring
function generateBreakdownFromScore($totalScore) {
    // Reverse engineering: coba estimasi breakdown dari total score
    // Untuk customer dengan score 950, estimasi:
    
    if ($totalScore >= 1800) {
        // Excellent customer
        return json_encode([
            'kriteria_1' => ['score' => 20, 'poin' => 700, 'label' => 'Rata Transaksi'],
            'kriteria_2' => ['score' => 20, 'poin' => 600, 'label' => 'Keterlambatan'],
            'kriteria_3' => ['score' => 20, 'poin' => 400, 'label' => 'Frekuensi'],
            'kriteria_4' => ['score' => 20, 'poin' => 300, 'label' => 'Lama Pelanggan']
        ]);
    } elseif ($totalScore >= 1100) {
        // Good customer (1150)
        return json_encode([
            'kriteria_1' => ['score' => 15, 'poin' => 525, 'label' => 'Rata Transaksi'],
            'kriteria_2' => ['score' => 15, 'poin' => 450, 'label' => 'Keterlambatan'],
            'kriteria_3' => ['score' => 10, 'poin' => 200, 'label' => 'Frekuensi'],
            'kriteria_4' => ['score' => 10, 'poin' => 150, 'label' => 'Lama Pelanggan']
        ]);
    } elseif ($totalScore >= 900) {
        // Medium customer (950)
        return json_encode([
            'kriteria_1' => ['score' => 5, 'poin' => 175, 'label' => 'Rata Transaksi'],
            'kriteria_2' => ['score' => 15, 'poin' => 450, 'label' => 'Keterlambatan'],
            'kriteria_3' => ['score' => 10, 'poin' => 200, 'label' => 'Frekuensi'],
            'kriteria_4' => ['score' => 10, 'poin' => 150, 'label' => 'Lama Pelanggan']
        ]);
    } else {
        // Low customer
        return json_encode([
            'kriteria_1' => ['score' => 5, 'poin' => 175, 'label' => 'Rata Transaksi'],
            'kriteria_2' => ['score' => 10, 'poin' => 300, 'label' => 'Keterlambatan'],
            'kriteria_3' => ['score' => 5, 'poin' => 100, 'label' => 'Frekuensi'],
            'kriteria_4' => ['score' => 5, 'poin' => 75, 'label' => 'Lama Pelanggan']
        ]);
    }
}

echo "==============================================\n";
echo "UPDATE CUSTOMER LAMA DENGAN BREAKDOWN\n";
echo "==============================================\n\n";

// Get customers tanpa breakdown
$customers = $db->query("SELECT id, name, total_score, scoring_breakdown FROM customers WHERE scoring_breakdown IS NULL OR scoring_breakdown = ''");

if (empty($customers)) {
    echo "✅ Semua customer sudah punya breakdown!\n";
    exit;
}

echo "Found " . count($customers) . " customer(s) tanpa breakdown:\n\n";

foreach ($customers as $customer) {
    echo "Customer: {$customer['name']}\n";
    echo "  Score: {$customer['total_score']} poin\n";
    
    $breakdown = generateBreakdownFromScore($customer['total_score']);
    $breakdownData = json_decode($breakdown, true);
    
    echo "  Breakdown (Estimasi):\n";
    foreach ($breakdownData as $key => $detail) {
        echo sprintf("    %-20s: Skor %2d/20 → %4d poin\n", 
            $detail['label'], $detail['score'], $detail['poin']);
    }
    
    // Update database
    $db->execute("UPDATE customers SET scoring_breakdown = :breakdown WHERE id = :id", [
        'breakdown' => $breakdown,
        'id' => $customer['id']
    ]);
    
    echo "  ✅ Breakdown berhasil di-generate!\n\n";
}

echo "==============================================\n";
echo "UPDATE SELESAI!\n";
echo "==============================================\n";
echo "\nSekarang semua customer punya breakdown detail.\n";
echo "Silakan klik tombol '📊 Detail' di halaman Settings.\n";
?>
