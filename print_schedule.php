<?php
// --- Helper Functions (Ambil data dari JSON) ---
function loadJson($file) {
    if (!file_exists($file)) file_put_contents($file, '[]');
    return json_decode(file_get_contents($file), true);
}

$salesmanId = $_GET['salesman_id'] ?? die("ID Salesman hilang.");
$visitDate = $_GET['visit_date'] ?? die("Tanggal Kunjungan hilang.");

$schedules = loadJson('data/salesman_schedules.json');
$customers = loadJson('data/customers.json');
$users = loadJson('data/users.json');

// Cari Nama Salesman
$salesmanKey = array_search($salesmanId, array_column($users, 'id'));
$salesmanName = $users[$salesmanKey]['fullname'] ?? $users[$salesmanKey]['username'] ?? 'Tidak Dikenal';

// Filter Jadwal
$filteredSchedules = array_filter($schedules, function($s) use ($salesmanId, $visitDate) {
    return (string)$s['salesman_id'] === $salesmanId && $s['visit_date'] === $visitDate;
});
usort($filteredSchedules, function($a, $b) {
    return $a['sequence'] - $b['sequence'];
});

// Map data Pelanggan ke Jadwal
foreach ($filteredSchedules as $k => $s) {
    $custKey = array_search($s['customer_id'], array_column($customers, 'id'));
    if ($custKey !== false) {
        $filteredSchedules[$k]['customer_data'] = $customers[$custKey];
    } else {
        // Data fallback (Owner dan Phone tidak lagi diperlukan di sini, tapi dipertahankan untuk menghindari error)
        $filteredSchedules[$k]['customer_data'] = ['name' => 'Pelanggan Dihapus/Tidak Ditemukan', 'address' => 'N/A', 'owner' => 'N/A', 'phone' => 'N/A'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Jadwal Kunjungan</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; padding: 20px; border: 1px solid #000; width: 800px; margin: auto; }
        h1 { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .meta { margin-bottom: 20px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #eee; }
        .ttd { margin-top: 50px; display: flex; justify-content: space-around; text-align: center; }
        .ttd div { width: 40%; padding-top: 10px; }
    </style>
</head>
<body onload="window.print()">
    <h1>DAFTAR KUNJUNGAN SALESMAN</h1>
    
    <div class="meta">
        <b>Salesman:</b> <?= $salesmanName ?><br>
        <b>Tanggal Kunjungan:</b> <?= $visitDate ?><br>
        <b>Jumlah Kunjungan:</b> <?= count($filteredSchedules) ?> Toko
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Toko</th>
                <th>Alamat Toko</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filteredSchedules as $s): ?>
                <tr>
                    <td><?= $s['sequence'] ?></td>
                    <td><?= $s['customer_data']['name'] ?></td>
                    <td><?= $s['customer_data']['address'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="ttd">
        <div>
            Dibuat Oleh,<br><br><br>
            (<?= $salesmanName ?>)
        </div>
        <div>
            Disetujui Oleh,<br><br><br>
            (Supervisor)
        </div>
    </div>
</body>
</html>