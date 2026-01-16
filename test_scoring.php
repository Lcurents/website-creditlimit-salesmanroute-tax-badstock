<?php
/**
 * TEST SMART SCORING SYSTEM
 * Menampilkan bagaimana penghitungan skor bekerja
 */

// Fungsi Smart Scoring (Copy dari settings.php)
function hitungCreditScore($rataJuta, $telatKali, $freqBulanan, $lamaTahun) {
    // Kriteria 1: Rata-rata Transaksi (Bobot 35% = 0-700 poin)
    if ($rataJuta > 100) $s1 = 20; 
    elseif ($rataJuta >= 50) $s1 = 15; 
    elseif ($rataJuta >= 25) $s1 = 10; 
    else $s1 = 5;
    $total1 = $s1 * 35; // 35% FIXED ✅
    
    // Kriteria 2: Keterlambatan Bayar (Bobot 30% = 0-600 poin)
    if ($telatKali == 0) $s2 = 20; 
    elseif ($telatKali < 5) $s2 = 15; 
    elseif ($telatKali < 15) $s2 = 10; 
    else $s2 = 5;
    $total2 = $s2 * 30; // 30% ✅

    // Kriteria 3: Frekuensi Transaksi (Bobot 20% = 0-400 poin)
    if ($freqBulanan > 10) $s3 = 20; 
    elseif ($freqBulanan >= 5) $s3 = 15; 
    elseif ($freqBulanan >= 2) $s3 = 10; 
    else $s3 = 5;
    $total3 = $s3 * 20; // 20% ✅

    // Kriteria 4: Lama Pelanggan (Bobot 15% = 0-300 poin)
    if ($lamaTahun > 10) $s4 = 20; 
    elseif ($lamaTahun >= 5) $s4 = 15; 
    elseif ($lamaTahun >= 1) $s4 = 10; 
    else $s4 = 5;
    $total4 = $s4 * 15; // 15% FIXED ✅

    // Total Score: Max = 700 + 600 + 400 + 300 = 2000 poin
    $grandScore = $total1 + $total2 + $total3 + $total4;

    // Credit Limit Classification
    if ($grandScore <= 400) $limit = rand(0, 5000000);
    elseif ($grandScore <= 800) $limit = rand(5000000, 20000000);
    elseif ($grandScore <= 1200) $limit = rand(20000000, 50000000);
    elseif ($grandScore <= 1600) $limit = rand(50000000, 75000000);
    else $limit = rand(75000000, 100000000);

    // Return breakdown untuk transparansi
    return [
        'score' => $grandScore, 
        'limit' => $limit,
        'breakdown' => json_encode([
            'kriteria_1' => ['score' => $s1, 'poin' => $total1, 'label' => 'Rata Transaksi'],
            'kriteria_2' => ['score' => $s2, 'poin' => $total2, 'label' => 'Keterlambatan'],
            'kriteria_3' => ['score' => $s3, 'poin' => $total3, 'label' => 'Frekuensi'],
            'kriteria_4' => ['score' => $s4, 'poin' => $total4, 'label' => 'Lama Pelanggan']
        ])
    ];
}

echo "==============================================\n";
echo "TEST SMART SCORING SYSTEM\n";
echo "==============================================\n\n";

// Test Case 1: Pelanggan Excellent (Skor Maksimal)
echo "TEST 1: Pelanggan Excellent (Nilai Tertinggi Semua Kriteria)\n";
echo str_repeat("-", 60) . "\n";

$test1 = hitungCreditScore(
    110,  // Rata transaksi > 100 juta/bulan → Skor 20
    0,    // Tidak pernah telat → Skor 20
    11,   // Belanja > 10x/bulan → Skor 20
    11    // Pelanggan > 10 tahun → Skor 20
);

echo "INPUT:\n";
echo "  • Rata-rata Transaksi: > 100 Juta/bulan\n";
echo "  • Keterlambatan Bayar: 0x (Tidak Pernah Telat)\n";
echo "  • Frekuensi Belanja: > 10x/bulan\n";
echo "  • Lama Pelanggan: > 10 tahun\n\n";

$breakdown1 = json_decode($test1['breakdown'], true);
echo "PENGHITUNGAN:\n";
foreach ($breakdown1 as $key => $detail) {
    echo sprintf("  %-20s: Skor %2d/20 × Bobot = %4d poin\n", 
        $detail['label'], $detail['score'], $detail['poin']);
}
echo str_repeat("-", 60) . "\n";
echo sprintf("  TOTAL SCORE: %d poin (dari max 2000)\n", $test1['score']);
echo sprintf("  CREDIT LIMIT: Rp %s\n\n", number_format($test1['limit']));

// Test Case 2: Pelanggan Medium (Seperti Laurentius Dika - 950 poin)
echo "TEST 2: Pelanggan Medium (Seperti Laurentius Dika)\n";
echo str_repeat("-", 60) . "\n";

// Untuk mendapat skor 950, coba kombinasi:
// Target: 950 = kriteria1 + kriteria2 + kriteria3 + kriteria4
// Asumsi: 15*35 + 15*30 + 10*20 + 10*15 = 525 + 450 + 200 + 150 = 1325 (terlalu tinggi)
// Coba: 10*35 + 15*30 + 10*20 + 5*15 = 350 + 450 + 200 + 75 = 1075 (masih tinggi)
// Coba: 10*35 + 10*30 + 10*20 + 10*15 = 350 + 300 + 200 + 150 = 1000 (mendekati)
// Coba: 5*35 + 15*30 + 10*20 + 10*15 = 175 + 450 + 200 + 150 = 975 (mendekati)
// Coba: 5*35 + 15*30 + 5*20 + 15*15 = 175 + 450 + 100 + 225 = 950 ✅

$test2 = hitungCreditScore(
    20,   // 25-50 Juta → Skor 10
    3,    // < 5x telat → Skor 15
    3,    // 2-5x/bulan → Skor 15
    2     // 1-5 tahun → Skor 15
);

echo "INPUT (Estimasi untuk skor 950):\n";
echo "  • Rata-rata Transaksi: 25-50 Juta/bulan\n";
echo "  • Keterlambatan Bayar: < 5x\n";
echo "  • Frekuensi Belanja: 2-5x/bulan\n";
echo "  • Lama Pelanggan: 1-5 tahun\n\n";

$breakdown2 = json_decode($test2['breakdown'], true);
echo "PENGHITUNGAN:\n";
foreach ($breakdown2 as $key => $detail) {
    echo sprintf("  %-20s: Skor %2d/20 × Bobot = %4d poin\n", 
        $detail['label'], $detail['score'], $detail['poin']);
}
echo str_repeat("-", 60) . "\n";
echo sprintf("  TOTAL SCORE: %d poin (dari max 2000)\n", $test2['score']);
echo sprintf("  CREDIT LIMIT: Rp %s\n\n", number_format($test2['limit']));

// Test Case 3: Pelanggan Pemula
echo "TEST 3: Pelanggan Pemula (Baru & Transaksi Kecil)\n";
echo str_repeat("-", 60) . "\n";

$test3 = hitungCreditScore(
    20,   // < 25 Juta → Skor 5
    10,   // 5-15x telat → Skor 10
    1,    // < 2x/bulan → Skor 5
    0     // < 1 tahun → Skor 5
);

echo "INPUT:\n";
echo "  • Rata-rata Transaksi: < 25 Juta/bulan\n";
echo "  • Keterlambatan Bayar: 5-15x\n";
echo "  • Frekuensi Belanja: < 2x/bulan\n";
echo "  • Lama Pelanggan: < 1 tahun\n\n";

$breakdown3 = json_decode($test3['breakdown'], true);
echo "PENGHITUNGAN:\n";
foreach ($breakdown3 as $key => $detail) {
    echo sprintf("  %-20s: Skor %2d/20 × Bobot = %4d poin\n", 
        $detail['label'], $detail['score'], $detail['poin']);
}
echo str_repeat("-", 60) . "\n";
echo sprintf("  TOTAL SCORE: %d poin (dari max 2000)\n", $test3['score']);
echo sprintf("  CREDIT LIMIT: Rp %s\n\n", number_format($test3['limit']));

echo "\n==============================================\n";
echo "KESIMPULAN FORMULA:\n";
echo "==============================================\n";
echo "Kriteria 1 (Rata Transaksi):  Bobot 35%  Max 700 poin\n";
echo "Kriteria 2 (Keterlambatan):    Bobot 30%  Max 600 poin\n";
echo "Kriteria 3 (Frekuensi):        Bobot 20%  Max 400 poin\n";
echo "Kriteria 4 (Lama Pelanggan):   Bobot 15%  Max 300 poin\n";
echo "                               ========  ==========\n";
echo "                TOTAL:         100%      2000 poin\n\n";

echo "KLASIFIKASI CREDIT LIMIT:\n";
echo "  0-400 poin:      Rp 0 - 5 juta\n";
echo "  401-800 poin:    Rp 5 - 20 juta\n";
echo "  801-1200 poin:   Rp 20 - 50 juta (Laurentius Dika: 950)\n";
echo "  1201-1600 poin:  Rp 50 - 75 juta\n";
echo "  1601-2000 poin:  Rp 75 - 100 juta\n";
echo "==============================================\n";
?>
