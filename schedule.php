<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$user = $_SESSION['user'];

require_once __DIR__ . '/config/database.php';

// --- Helper Functions (Ambil data dari JSON) ---
function loadJson($file) {
    if (!file_exists($file)) file_put_contents($file, '[]');
    return json_decode(file_get_contents($file), true);
}
function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

$fileSchedules = 'data/salesman_schedules.json';
$schedules = loadJson($fileSchedules);

// Load customers dari SQLite
$db = Database::getInstance();
$customers = $db->query("SELECT * FROM customers ORDER BY name");

// Filter user yang punya role 'FAKTURIS' (Salesman)
$salesmen = array_filter(loadJson('data/users.json'), function($u) {
    return $u['role'] === 'FAKTURIS';
});

$msg = "";

// ==========================================
// 1. HANDLE LOGIC (POST ACTION)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- AKSI: TAMBAH JADWAL BARU (Manual) ---
    if ($_POST['action'] == 'add_schedule') {
        $salesmanId = (int)$_POST['salesman_id'];
        $customerId = (int)$_POST['customer_id'];
        $startDate = $_POST['visit_date'];
        $sequence = (int)$_POST['sequence'];
        
        $newSchedule = [
            'id' => time() + rand(1, 99999), // ID unik
            'salesman_id' => $salesmanId,
            'customer_id' => $customerId,
            'visit_date' => $startDate,
            'sequence' => $sequence,
            'created_by' => $user['fullname'] ?? $user['username']
        ];
        
        $schedules[] = $newSchedule;
        saveJson($fileSchedules, $schedules);
        
        $msgText = "Jadwal kunjungan berhasil ditambahkan.";
        $redirUrl = "schedule.php?salesman_id={$salesmanId}&visit_date={$startDate}&msg_type=add_success&msg_text=" . urlencode($msgText);
        header("Location: $redirUrl");
        exit;
    }

    // --- AKSI: HAPUS JADWAL ---
    if ($_POST['action'] == 'delete_schedule') {
        $idToDelete = (int)$_POST['schedule_id'];
        $salesmanId = $_POST['salesman_id'];
        $visitDate = $_POST['visit_date'];

        $schedules = array_filter($schedules, function($s) use ($idToDelete) {
            return $s['id'] !== $idToDelete;
        });
        saveJson($fileSchedules, array_values($schedules)); 
        
        $msgText = "Jadwal berhasil dihapus.";
        $redirUrl = "schedule.php?salesman_id={$salesmanId}&visit_date={$visitDate}&msg_type=delete_success&msg_text=" . urlencode($msgText);
        header("Location: $redirUrl");
        exit;
    }
}

// ==========================================
// 2. FILTER JADWAL YANG SUDAH ADA (Sebelum Auto-Add)
// ==========================================
$selectedSalesmanId = $_GET['salesman_id'] ?? array_column($salesmen, 'id')[0] ?? null;
$selectedDate = $_GET['visit_date'] ?? date('Y-m-d');
$filteredSchedules = [];

// Handle pesan sukses dari redirect
if (isset($_GET['msg_type'])) {
    $msg = htmlspecialchars($_GET['msg_text']);
}

$customersById = array_column($customers, null, 'id');

if ($selectedSalesmanId) {
    // A. Filter Jadwal yang SUDAH ADA
    $filteredSchedules = array_filter($schedules, function($s) use ($selectedSalesmanId, $selectedDate) {
        return (string)$s['salesman_id'] === (string)$selectedSalesmanId && $s['visit_date'] === $selectedDate;
    });
}

// Map data Pelanggan ke Jadwal yang sudah ada
foreach ($filteredSchedules as $k => $s) {
    $customerData = $customersById[$s['customer_id']] ?? null;
    if ($customerData) {
        $filteredSchedules[$k]['customer_data'] = $customerData;
    } else {
        $filteredSchedules[$k]['customer_data'] = ['name' => 'Pelanggan Tidak Ditemukan', 'address' => 'N/A'];
    }
}


// ==========================================
// 3. LOGIC PENAMBAHAN JADWAL 14 HARI (OTOMATIS)
// ==========================================

if ($selectedSalesmanId) {
    $selectedDateDT = new DateTime($selectedDate);
    $twoWeeksInSeconds = 14 * 24 * 60 * 60;
    $schedulesUpdated = false; // Flag untuk menandakan ada penambahan otomatis
    $autoAddedCount = 0;

    // Cari semua jadwal Salesman yang dipilih (termasuk tanggal lalu)
    $salesmanAllSchedules = array_filter($schedules, function($s) use ($selectedSalesmanId) {
        return (string)$s['salesman_id'] === (string)$selectedSalesmanId;
    });

    // Mengelompokkan kunjungan berdasarkan customer_id dan mencari kunjungan terakhir
    $lastVisits = [];
    foreach ($salesmanAllSchedules as $s) {
        $cId = (string)$s['customer_id'];
        if (!isset($lastVisits[$cId]) || $s['visit_date'] > $lastVisits[$cId]['visit_date']) {
            $lastVisits[$cId] = $s;
        }
    }

    // Iterasi kunjungan terakhir untuk membuat jadwal OTOMATIS
    foreach ($lastVisits as $lastSchedule) {
        $visitDateDT = new DateTime($lastSchedule['visit_date']);
        
        // 1. Pastikan tanggal kunjungan terakhir di masa lalu
        if ($visitDateDT < $selectedDateDT) {
            
            $diffSeconds = $selectedDateDT->getTimestamp() - $visitDateDT->getTimestamp();
            
            // 2. Cek apakah selisih waktu adalah kelipatan 14 hari
            // Toleransi 1 jam untuk menghindari masalah perbedaan waktu/timestamp
            if ($diffSeconds > 0 && abs($diffSeconds % $twoWeeksInSeconds) < 3600) {
                
                // 3. Cek apakah toko ini BELUM ADA di jadwal yang sedang ditampilkan
                $alreadyScheduled = false;
                foreach ($filteredSchedules as $fs) {
                    if ((string)$fs['customer_id'] === (string)$lastSchedule['customer_id']) {
                        $alreadyScheduled = true;
                        break;
                    }
                }
                
                if (!$alreadyScheduled) {
                    // ** LAKUKAN PENAMBAHAN JADWAL OTOMATIS **
                    $newSequence = count($filteredSchedules) + 1;
                    $newSchedule = [
                        'id' => time() + rand(1, 99999), 
                        'salesman_id' => $selectedSalesmanId,
                        'customer_id' => $lastSchedule['customer_id'],
                        'visit_date' => $selectedDate,
                        'sequence' => $newSequence,
                        'created_by' => 'SYSTEM_14_DAY_AUTO' 
                    ];
                    
                    // Tambahkan ke array jadwal utama & set flag
                    $schedules[] = $newSchedule;
                    $schedulesUpdated = true;
                    $autoAddedCount++;
                    
                    // Tambahkan ke filteredSchedules agar langsung muncul di tabel
                    $customerData = $customersById[$lastSchedule['customer_id']] ?? ['name' => 'Tidak Ditemukan', 'address' => 'N/A'];
                    $newSchedule['customer_data'] = $customerData;
                    $filteredSchedules[] = $newSchedule; 
                }
            }
        }
    }
    
    // Hanya simpan dan atur pesan jika ada perubahan otomatis
    if ($schedulesUpdated) {
        saveJson($fileSchedules, $schedules);
        
        // Setelah penambahan otomatis, urutkan ulang filteredSchedules berdasarkan sequence
        usort($filteredSchedules, function($a, $b) {
            return $a['sequence'] - $b['sequence'];
        });
        
        // Tampilkan pesan sukses otomatis
        if (!isset($_GET['msg_type'])) { // Jangan tumpuk dengan pesan dari redirect
             $msg = "✅ **{$autoAddedCount}** Jadwal kunjungan 14-hari berhasil ditambahkan secara otomatis.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Jadwal Kunjungan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Mengubah style agar sesuai tema B&W */
        .filter-form { 
            margin-bottom: 20px; 
            padding: 15px; 
            border: 1px solid #333; /* Menggunakan border hitam */
            background: #fff; /* Latar belakang putih */
        }
        .add-form { 
            margin-top: 30px; 
            padding: 15px; 
            border: 2px solid #333; /* Border lebih tebal */
            background: #eee; /* Latar belakang abu-abu muda */
        }
        .add-form label { display: inline-block; margin-right: 10px; font-weight: bold; }
        .add-form .field-group { margin-bottom: 10px; }
        .add-form select, .add-form input[type="date"], .add-form input[type="number"] { 
            padding: 8px; 
            border: 1px solid #333; 
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h1>Jadwal Kunjungan Salesman</h1>

        <?php 
        // Mengubah warna pesan dari lightgreen menjadi netral (background: #eee)
        if($msg): ?>
            <div class="header-box" style="background: #eee; border: 2px solid #333; color: #333;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="filter-form">
            <label>Salesman:</label>
            <select name="salesman_id" required>
                <?php foreach ($salesmen as $sm): ?>
                    <option value="<?= $sm['id'] ?>" <?= (string)$sm['id'] === (string)$selectedSalesmanId ? 'selected' : '' ?>>
                        <?= $sm['fullname'] ?? $sm['username'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label>Tanggal:</label>
            <input type="date" name="visit_date" value="<?= $selectedDate ?>" required>
            
            <button type="submit">Tampilkan Jadwal</button>
            <?php if (!empty($filteredSchedules)): ?>
            <a href="print_schedule.php?salesman_id=<?= $selectedSalesmanId ?>&visit_date=<?= $selectedDate ?>" target="_blank" class="badge" style="background:#333; color:white;">🖨️ CETAK JADWAL</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <h3>Jadwal Kunjungan: <?= $selectedDate ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Toko</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredSchedules)): ?>
                        <tr><td colspan="4" style="text-align:center;">Tidak ada jadwal kunjungan untuk hari ini.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($filteredSchedules as $s): ?>
                        <tr>
                            <td><?= $s['sequence'] ?></td>
                            <td>
                                <b><?= $s['customer_data']['name'] ?></b>
                                <?php if (($s['created_by'] ?? '') === 'SYSTEM_14_DAY_AUTO'): ?>
                                    <span style="font-size:10px; color: green; font-weight: bold;">(Auto 14 Hari)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['customer_data']['address'] ?></td>
                            <td>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus jadwal ini?');">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="salesman_id" value="<?= $selectedSalesmanId ?>">
                                    <input type="hidden" name="visit_date" value="<?= $selectedDate ?>">
                                    <button type="submit" style="background:#ccc; color:#333; font-size:10px; border: 1px solid #333;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card add-form">
            <h3>+ Tambah Kunjungan Baru (Manual)</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_schedule">
                <input type="hidden" name="salesman_id" value="<?= $selectedSalesmanId ?>">
                
                <div class="field-group">
                    <label>Tanggal Kunjungan:</label>
                    <input type="date" name="visit_date" value="<?= $selectedDate ?>" required>
                    
                    <label>Urutan:</label>
                    <input type="number" name="sequence" value="<?= count($filteredSchedules) + 1 ?>" min="1" style="width: 50px;" required>
                </div>
                
                <div class="field-group">
                    <label>Toko:</label>
                    <select name="customer_id" required>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= $c['name'] ?> (<?= $c['address'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" style="margin-top: 10px;">Tambahkan Jadwal</button>
            </form>

            <p style="margin-top: 20px;">
                Jika toko belum terdaftar, tambahkan dulu: 
                <a href="settings.php" target="_blank" class="badge" style="background:#333; color:white;">+ Pelanggan Baru</a>
            </p>
        </div>
    </div>
</body>
</html>