<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$user = $_SESSION['user'];
$role = $user['role']; // Ambil role user

// ==========================================
// 1. LOAD DATA (SQLITE + JSON LEGACY)
// ==========================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Load customers dari SQLite
$db = Database::getInstance();
$customers = $db->query("SELECT * FROM customers ORDER BY name");

// Load products dari SQLite
$products = $db->query("SELECT * FROM products ORDER BY name");

// Legacy JSON (masih digunakan untuk users & mobil)
$users = json_decode(file_get_contents('data/users.json'), true);

// Load Data Mobil
$fileCars = 'data/cars.json';
if (!file_exists($fileCars)) file_put_contents($fileCars, '[]');
$cars = json_decode(file_get_contents($fileCars), true);

$msg = "";
$activeTab = "pelanggan"; // Default tab saat dibuka

// ==========================================
// 2. FUNGSI SMART SCORING (FIXED - BOBOT BENAR)
// ==========================================
/**
 * Smart Credit Scoring System
 * Kriteria 1 (Rata-rata Transaksi/bulan): Bobot 35%
 * Kriteria 2 (Keterlambatan Bayar): Bobot 30%
 * Kriteria 3 (Frekuensi Transaksi/bulan): Bobot 20%
 * Kriteria 4 (Lama Menjadi Pelanggan): Bobot 15%
 * 
 * Total Score Range: 0 - 2000 poin
 */
function hitungCreditScore($rataJuta, $telatKali, $freqBulanan, $lamaTahun) {
    // DETEKSI PELANGGAN BARU: Jika belum ada transaksi, beri limit default terendah
    $isNewCustomer = ($freqBulanan == 0 && $lamaTahun == 0 && $rataJuta == 0);
    
    if ($isNewCustomer) {
        // Pelanggan baru: default credit limit terendah
        return [
            'score' => 0,
            'limit' => 5000000, // Rp 5 juta (default untuk pemula)
            'breakdown' => json_encode([
                'kriteria_1' => ['score' => 0, 'poin' => 0, 'label' => 'Rata Transaksi', 'bobot' => 35],
                'kriteria_2' => ['score' => 0, 'poin' => 0, 'label' => 'Keterlambatan', 'bobot' => 30],
                'kriteria_3' => ['score' => 0, 'poin' => 0, 'label' => 'Frekuensi', 'bobot' => 20],
                'kriteria_4' => ['score' => 0, 'poin' => 0, 'label' => 'Lama Pelanggan', 'bobot' => 15]
            ])
        ];
    }
    
    // Kriteria 1: Rata-rata Transaksi (Bobot 35% = 0-700 poin)
    if ($rataJuta > 100) $s1 = 20; 
    elseif ($rataJuta >= 50) $s1 = 15; 
    elseif ($rataJuta >= 25) $s1 = 10; 
    elseif ($rataJuta >= 10) $s1 = 5;
    else $s1 = 0; // PERBAIKAN: 0 untuk transaksi < 10 juta
    $total1 = $s1 * 35;
    
    // Kriteria 2: Keterlambatan Bayar (Bobot 30% = 0-600 poin)
    // PERBAIKAN: Pelanggan dengan frekuensi rendah (<3 transaksi) tidak dapat skor maksimal
    if ($freqBulanan < 3) {
        // Belum cukup data
        $s2 = 0;
    } elseif ($telatKali == 0) {
        $s2 = 20; 
    } elseif ($telatKali < 5) {
        $s2 = 15; 
    } elseif ($telatKali < 15) {
        $s2 = 10; 
    } else {
        $s2 = 5;
    }
    $total2 = $s2 * 30;

    // Kriteria 3: Frekuensi Transaksi (Bobot 20% = 0-400 poin)
    if ($freqBulanan > 10) $s3 = 20; 
    elseif ($freqBulanan >= 5) $s3 = 15; 
    elseif ($freqBulanan >= 2) $s3 = 10; 
    elseif ($freqBulanan >= 1) $s3 = 5;
    else $s3 = 0; // PERBAIKAN: 0 untuk belum ada transaksi
    $total3 = $s3 * 20;

    // Kriteria 4: Lama Pelanggan (Bobot 15% = 0-300 poin)
    if ($lamaTahun > 10) $s4 = 20; 
    elseif ($lamaTahun >= 5) $s4 = 15; 
    elseif ($lamaTahun >= 1) $s4 = 10; 
    elseif ($lamaTahun >= 0.5) $s4 = 5; // Min 6 bulan
    else $s4 = 0; // PERBAIKAN: 0 untuk pelanggan < 6 bulan
    $total4 = $s4 * 15;

    // Total Score: Max = 700 + 600 + 400 + 300 = 2000 poin
    $grandScore = $total1 + $total2 + $total3 + $total4;

    // Credit Limit Classification (Fixed per kategori)
    if ($grandScore <= 400) $limit = 5000000;        // Rp 5 juta
    elseif ($grandScore <= 800) $limit = 15000000;   // Rp 15 juta
    elseif ($grandScore <= 1200) $limit = 30000000;  // Rp 30 juta
    elseif ($grandScore <= 1600) $limit = 50000000;  // Rp 50 juta
    else $limit = 100000000;                         // Rp 100 juta

    // Return breakdown untuk transparansi
    return [
        'score' => $grandScore, 
        'limit' => $limit,
        'breakdown' => json_encode([
            'kriteria_1' => ['score' => $s1, 'poin' => $total1, 'label' => 'Rata Transaksi', 'bobot' => 35],
            'kriteria_2' => ['score' => $s2, 'poin' => $total2, 'label' => 'Keterlambatan', 'bobot' => 30],
            'kriteria_3' => ['score' => $s3, 'poin' => $total3, 'label' => 'Frekuensi', 'bobot' => 20],
            'kriteria_4' => ['score' => $s4, 'poin' => $total4, 'label' => 'Lama Pelanggan', 'bobot' => 15]
        ])
    ];
}

// ==========================================
// 3. HANDLE POST REQUESTS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- PELANGGAN (MIGRASI KE SQLITE) ---
    if ($_POST['action'] === 'add_customer_scoring') {
        require_once __DIR__ . '/config/database.php';
        
        $hasil = hitungCreditScore(
            (int)$_POST['rata_transaksi'], 
            (int)$_POST['telat_bayar'], 
            (int)$_POST['freq_transaksi'], 
            (int)$_POST['lama_pelanggan']
        );
        
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO customers (name, address, phone, credit_limit, total_score, scoring_breakdown) 
                 VALUES (:name, :address, :phone, :limit, :score, :breakdown)",
                [
                    'name' => $_POST['name'],
                    'address' => $_POST['address'],
                    'phone' => $_POST['phone'] ?? '',
                    'limit' => $hasil['limit'],
                    'score' => $hasil['score'],
                    'breakdown' => $hasil['breakdown']
                ]
            );
            $msg = "✅ Pelanggan berhasil ditambahkan! Skor: " . $hasil['score'] . " poin, Limit: Rp " . number_format($hasil['limit']);
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
        
        $activeTab = "pelanggan";
    }

    if ($_POST['action'] === 'delete_customer') {
        require_once __DIR__ . '/config/database.php';
        
        try {
            $db = Database::getInstance();
            $db->execute("DELETE FROM customers WHERE id = :id", ['id' => $_POST['id']]);
            $msg = "✅ Data pelanggan berhasil dihapus.";
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
        
        $activeTab = "pelanggan";
    }

    // --- PRODUK (SQLITE) ---
    if ($_POST['action'] === 'add_product') {
        require_once __DIR__ . '/config/database.php';
        
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO products (name, price, stock) VALUES (:name, :price, :stock)",
                [
                    'name' => $_POST['name'],
                    'price' => (int)$_POST['price'],
                    'stock' => (int)$_POST['stock']
                ]
            );
            $msg = "✅ Produk berhasil ditambahkan.";
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
        
        $activeTab = "produk";
    }

    if ($_POST['action'] === 'restock_product') {
        require_once __DIR__ . '/config/database.php';
        
        try {
            $db = Database::getInstance();
            $db->execute(
                "UPDATE products SET stock = stock + :qty WHERE id = :id",
                [
                    'qty' => (int)$_POST['add_qty'],
                    'id' => $_POST['id']
                ]
            );
            $msg = "✅ Stok berhasil ditambahkan.";
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
        
        $activeTab = "produk";
    }

    if ($_POST['action'] === 'delete_product') {
        require_once __DIR__ . '/config/database.php';
        
        try {
            $db = Database::getInstance();
            $db->execute("DELETE FROM products WHERE id = :id", ['id' => $_POST['id']]);
            $msg = "✅ Produk berhasil dihapus.";
        } catch (Exception $e) {
            $msg = "❌ Error: " . $e->getMessage();
        }
        
        $activeTab = "produk";
    }

    // --- KENDARAAN (FITUR SAVE: BISA ADD / EDIT) ---
    // Saya ubah dari 'add_car' menjadi 'save_car' untuk menghandle edit juga
    if ($_POST['action'] === 'save_car') {
        $editId = $_POST['edit_id'] ?? ''; // Cek apakah ada ID edit

        if (!empty($editId)) {
            // === LOGIKA EDIT ===
            foreach ($cars as $k => $c) {
                if ($c['id'] == $editId) {
                    $cars[$k]['plat'] = strtoupper($_POST['plat']);
                    $cars[$k]['mobil'] = $_POST['mobil'];
                    $cars[$k]['driver'] = $_POST['driver'] ?? '-';
                    $cars[$k]['tgl_pajak'] = $_POST['tgl_pajak'] ?? date('Y-m-d');
                    break;
                }
            }

            $msg = "Data Kendaraan Diperbarui.";
        } else {
            // === LOGIKA ADD BARU ===
            $cars[] = [
                "id" => time(),
                "plat" => strtoupper($_POST['plat']),
                "mobil" => $_POST['mobil'],
                "driver" => $_POST['driver'] ?? '-', 
                "tgl_pajak" => $_POST['tgl_pajak'] ?? date('Y-m-d'),
                "bukti_img" => "",
                "status_validasi" => "NONE"
            ];
            $msg = "Kendaraan Baru Ditambahkan.";
        }
        
        file_put_contents($fileCars, json_encode($cars, JSON_PRETTY_PRINT));
        $activeTab = "kendaraan";
    }

    if ($_POST['action'] === 'delete_car') {
        $idToDelete = $_POST['id'];
        $cars = array_filter($cars, function($c) use ($idToDelete) { return $c['id'] != $idToDelete; });
        $cars = array_values($cars);
        file_put_contents($fileCars, json_encode($cars, JSON_PRETTY_PRINT));
        $msg = "Kendaraan dihapus.";
        $activeTab = "kendaraan";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Settings - Master Data</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS TAB DESIGN */
        .tab-container { overflow: hidden; background-color: #333; margin-top: 20px; border-radius: 5px 5px 0 0; }
        .tab-button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; color: #ccc; font-weight: bold; font-family: inherit; font-size: 14px; }
        .tab-button:hover { background-color: #555; color: white; }
        .tab-button.active { background-color: #f4f4f4; color: #333; border-bottom: 3px solid #0056b3; }
        
        /* CONTENT DESIGN */
        .tab-content { display: none; padding: 20px; border: 1px solid #ccc; border-top: none; background: #fff; animation: fadeEffect 0.5s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        @keyframes fadeEffect { from {opacity: 0;} to {opacity: 1;} }

        /* FORM HELPERS */
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .form-row input, .form-row select { flex: 1; padding: 10px; }
        .btn-delete { background: #990000; color: #fff; border: none; padding: 5px 10px; cursor: pointer; font-size: 11px; }
        .section-title { border-bottom: 2px solid #333; margin-bottom: 15px; padding-bottom: 5px; }
        small { display: block; margin-bottom: 3px; font-weight: bold; color: #555; }

        /* EDIT BUTTON STYLE */
        .btn-edit { background: #ffc107; color: #000; border: none; padding: 5px 10px; cursor: pointer; font-size: 11px; font-weight: bold; margin-right: 5px; }
    </style>
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            if(evt) evt.currentTarget.className += " active";
        }

        // FUNGSI JAVASCRIPT UNTUK EDIT
        function editCar(id, plat, mobil, driver, tgl) {
            document.getElementById('car_edit_id').value = id;
            document.getElementById('car_plat').value = plat;
            document.getElementById('car_mobil').value = mobil;
            document.getElementById('car_driver').value = driver;
            document.getElementById('car_tgl').value = tgl;
            
            var btn = document.getElementById('btnSaveCar');
            btn.innerHTML = "UPDATE DATA KENDARAAN";
            btn.style.backgroundColor = "#ffc107"; // Warna Kuning
            btn.style.color = "#000";
        }

        // FUNGSI RESET FORM (Saat pindah tab / selesai simpan)
        function resetCarForm() {
            document.getElementById('car_edit_id').value = "";
            document.getElementById('car_plat').value = "";
            document.getElementById('car_mobil').value = "";
            document.getElementById('car_driver').value = "";
            document.getElementById('car_tgl').value = "";
            
            var btn = document.getElementById('btnSaveCar');
            btn.innerHTML = "SIMPAN DATA KENDARAAN";
            btn.style.backgroundColor = "#0056b3"; // Warna Biru
            btn.style.color = "#fff";
        }
    </script>
</head>
<body onload="initTab()">
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Settings & Master Data</h1>
            <p>Kelola data Pelanggan, Barang, Aset Kendaraan, dan User.</p>
            <?php if($msg): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin-top: 10px;">
                    Info: <b><?= $msg ?></b>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-container">
            <button class="tab-button" onclick="openTab(event, 'TabPelanggan')" id="btnPelanggan">1. Pelanggan (Scoring)</button>
            <button class="tab-button" onclick="openTab(event, 'TabProduk')" id="btnProduk">2. Produk (Stok)</button>
            <?php if($role != 'WAREHOUSE'): ?>
            <button class="tab-button" onclick="openTab(event, 'TabKendaraan')" id="btnKendaraan" onclick="resetCarForm()">3. Aset Kendaraan</button>
            <?php endif; ?>
            <button class="tab-button" onclick="openTab(event, 'TabUser')" id="btnUser">4. User System</button>
        </div>

        <div id="TabPelanggan" class="tab-content">
            <h3 class="section-title">Data Pelanggan (Smart Credit Scoring)</h3>
            
            <form method="POST" style="background: #eef; padding: 15px; border: 1px solid #99c; margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_customer_scoring">
                
                <div class="form-row">
                    <input type="text" name="name" placeholder="Nama Warung" required>
                    <input type="text" name="address" placeholder="Alamat/Wilayah" required>
                </div>

                <hr style="border: 0; border-top: 1px dashed #ccc; margin: 10px 0;">
                <label style="font-weight:bold; font-size:12px; display:block; margin-bottom:10px;">PROFIL PENILAIAN KREDIT:</label>

                <div class="form-row">
                    <div style="flex:1">
                        <small>Rata-rata Transaksi (Juta):</small>
                        <select name="rata_transaksi" required>
                            <option value="0">Belum Ada Transaksi</option>
                            <option value="10">< 25 Juta (Kecil)</option>
                            <option value="30">25 - 50 Juta (Sedang)</option>
                            <option value="60">50 - 100 Juta (Besar)</option>
                            <option value="110">> 100 Juta (Prioritas)</option>
                        </select>
                    </div>
                    <div style="flex:1">
                        <small>Riwayat Telat Bayar:</small>
                        <select name="telat_bayar" required>
                            <option value="0">0x (Sangat Bagus)</option>
                            <option value="3">< 5x (Wajar)</option>
                            <option value="10">5 - 15x (Kurang Baik)</option>
                            <option value="20">>= 15x (Buruk)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div style="flex:1">
                        <small>Frekuensi Belanja/Bulan:</small>
                        <select name="freq_transaksi" required>
                            <option value="0">Belum Ada Transaksi</option>
                            <option value="1">< 2x (Jarang)</option>
                            <option value="3">2 - 5x (Cukup)</option>
                            <option value="6">5 - 10x (Sering)</option>
                            <option value="11">> 10x (Sangat Sering)</option>
                        </select>
                    </div>
                    <div style="flex:1">
                        <small>Lama Menjadi Pelanggan:</small>
                        <select name="lama_pelanggan" required>
                            <option value="0">< 1 Tahun (Baru)</option>
                            <option value="2">1 - 5 Tahun (Lama)</option>
                            <option value="6">5 - 10 Tahun (Setia)</option>
                            <option value="11">> 10 Tahun (Sangat Setia)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" style="width:100%; margin-top:10px; background: #333; color: #fff; border: 2px solid #000;">HITUNG SKOR & SIMPAN DATA</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Nama Warung</th>
                        <th>Skor</th>
                        <th>Limit (Auto)</th>
                        <th>Hutang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                            <small style="color: #666;"><?= htmlspecialchars($c['address'] ?? '-') ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background:#fff; color:#333; padding: 5px 10px; border: 2px solid #333;">
                                <?= $c['total_score'] ?? 0 ?> poin
                            </span>
                        </td>
                        <td style="color: green; font-weight:bold;"><?= rupiah($c['credit_limit']) ?></td>
                        <td style="color: <?= $c['current_debt'] > 0 ? 'red' : '#999' ?>; font-weight: bold;">
                            <?= rupiah($c['current_debt']) ?>
                        </td>
                        <td>
                            <a href="customer_profile.php?id=<?= $c['id'] ?>" 
                               class="badge" 
                               style="background:#333; color:white; text-decoration:none; padding: 5px 10px; border: 2px solid #000; margin-right: 5px;">
                                📊 Detail
                            </a>
                            <?php if($c['current_debt'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus pelanggan ini?');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="btn-delete" style="padding: 5px 10px;">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="TabProduk" class="tab-content">
            <h3 class="section-title">Data Produk & Stok</h3>

            <form method="POST" style="background: #f9f9f9; padding: 15px; border: 1px dashed #999; margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_product">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Nama Barang Baru" required>
                    <input type="number" name="price" placeholder="Harga Jual (Rp)" required>
                    <input type="number" name="stock" placeholder="Stok Awal" required>
                    <button type="submit">TAMBAH PRODUK</button>
                </div>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Harga</th>
                        <th>Stok Saat Ini</th>
                        <th style="width: 200px;">Restock (Tambah)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['name'] ?></td>
                        <td>Rp <?= number_format($p['price']) ?></td>
                        <td style="font-weight: bold; font-size: 16px;"><?= $p['stock'] ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="action" value="restock_product">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="number" name="add_qty" placeholder="+Qty" style="width: 70px; padding: 5px;" min="1" required>
                                <button type="submit" style="padding: 5px; background: #333; color: white; cursor:pointer;">+</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Hapus produk ini?');">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="btn-delete">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if($role != 'WAREHOUSE'): ?>
        <div id="TabKendaraan" class="tab-content">
            <h3 class="section-title">Master Aset Kendaraan</h3>
            <p style="font-size:12px; margin-bottom:10px;">Input data kendaraan operasional dan Driver.</p>

            <form method="POST" style="background: #eef; padding: 15px; border: 1px solid #99c; margin-bottom: 20px;">
                <input type="hidden" name="action" value="save_car">
                <!-- HIDDEN INPUT UNTUK ID EDIT -->
                <input type="hidden" name="edit_id" id="car_edit_id" value="">

                <div class="form-row">
                    <input type="text" name="plat" id="car_plat" placeholder="Plat Nomor (Mis: B 1234 XY)" required style="flex:1">
                    <input type="text" name="mobil" id="car_mobil" placeholder="Jenis Mobil (Mis: GranMax)" required style="flex:2">
                </div>
                <div class="form-row">
                    <input type="text" name="driver" id="car_driver" placeholder="Nama Driver / Penanggung Jawab" required style="flex:2">
                    <input type="date" name="tgl_pajak" id="car_tgl" required style="flex:1" title="Tanggal Jatuh Tempo">
                </div>
                <button type="submit" id="btnSaveCar" style="width:100%; background: #333; color: #fff; border: 2px solid #000;">SIMPAN DATA KENDARAAN</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Plat</th>
                        <th>Kendaraan</th>
                        <th>Driver</th>
                        <th>Jatuh Tempo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $c): ?>
                    <tr>
                        <td><?= $c['plat'] ?></td>
                        <td><?= $c['mobil'] ?></td>
                        <td><?= $c['driver'] ?? $c['pemilik'] ?? '-' ?></td> 
                        <td><?= date('d-m-Y', strtotime($c['tgl_pajak'] ?? date('Y-m-d'))) ?></td>
                        <td>
                            <!-- TOMBOL EDIT -->
                            <button type="button" class="btn-edit" 
                                onclick="editCar('<?= $c['id'] ?>', '<?= $c['plat'] ?>', '<?= $c['mobil'] ?>', '<?= $c['driver'] ?? $c['pemilik'] ?? '-' ?>', '<?= $c['tgl_pajak'] ?? '' ?>')">
                                Edit
                            </button>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus data mobil ini?');">
                                <input type="hidden" name="action" value="delete_car">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button class="btn-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div id="TabUser" class="tab-content">
            <h3 class="section-title">Data User System</h3>
            <ul>
                <?php foreach ($users as $u): ?>
                    <li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                        <div>
                            <b><?= $u['username'] ?></b><br>
                            <?= $u['fullname'] ?>
                        </div>
                        <span class="badge" style="background:#333; color:#fff; height: fit-content;"><?= $u['role'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>

    <script>
        function initTab() {
            <?php if($activeTab == 'kendaraan'): ?>
                document.getElementById('btnKendaraan').click();
            <?php elseif($activeTab == 'produk'): ?>
                document.getElementById('btnProduk').click();
            <?php else: ?>
                document.getElementById('btnPelanggan').click();
            <?php endif; ?>
        }
    </script>
</body>
</html>