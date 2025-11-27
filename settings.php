<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$user = $_SESSION['user'];

// ==========================================
// 1. LOAD SEMUA DATA JSON
// ==========================================
$customers = json_decode(file_get_contents('data/customers.json'), true);
$products = json_decode(file_get_contents('data/products.json'), true);
$users = json_decode(file_get_contents('data/users.json'), true);

// Load Data Mobil (Buat file jika belum ada)
$fileCars = 'data/cars.json';
if (!file_exists($fileCars)) file_put_contents($fileCars, '[]');
$cars = json_decode(file_get_contents($fileCars), true);

$msg = "";
$activeTab = "pelanggan"; // Tab default

// ==========================================
// 2. LOGIC: CREDIT SCORING (Fungsi)
// ==========================================
function hitungCreditScore($rataJuta, $telatKali, $freqBulanan, $lamaTahun) {
    // A. RATA-RATA (10%)
    if ($rataJuta > 100) $s1 = 20; elseif ($rataJuta >= 50) $s1 = 15; elseif ($rataJuta >= 25) $s1 = 10; else $s1 = 5;
    $total1 = $s1 * 10;
    // B. KETERLAMBATAN (30%)
    if ($telatKali == 0) $s2 = 20; elseif ($telatKali < 5) $s2 = 15; elseif ($telatKali < 15) $s2 = 10; else $s2 = 5;
    $total2 = $s2 * 30;
    // C. FREKUENSI (20%)
    if ($freqBulanan > 10) $s3 = 20; elseif ($freqBulanan >= 5) $s3 = 15; elseif ($freqBulanan >= 2) $s3 = 10; else $s3 = 5;
    $total3 = $s3 * 20;
    // D. LAMA (40%)
    if ($lamaTahun > 10) $s4 = 20; elseif ($lamaTahun >= 5) $s4 = 15; elseif ($lamaTahun >= 1) $s4 = 10; else $s4 = 5;
    $total4 = $s4 * 40;

    $grandScore = $total1 + $total2 + $total3 + $total4;
    
    if ($grandScore < 500) $limit = 0;
    elseif ($grandScore < 1000) $limit = 25000000;
    elseif ($grandScore < 1500) $limit = 50000000;
    else $limit = 100000000;

    return ['score' => $grandScore, 'limit' => $limit];
}

// ==========================================
// 3. HANDLE POST REQUESTS (Semua Tab)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- TAB 1: PELANGGAN ---
    if ($_POST['action'] === 'add_customer_scoring') {
        $hasil = hitungCreditScore((int)$_POST['rata_transaksi'], (int)$_POST['telat_bayar'], (int)$_POST['freq_transaksi'], (int)$_POST['lama_pelanggan']);
        $customers[] = [
            "id" => time(),
            "name" => $_POST['name'],
            "address" => $_POST['address'],
            "limit" => $hasil['limit'],
            "score_data" => $hasil['score'],
            "debt" => 0,
            "join_date" => date('Y-m-d')
        ];
        file_put_contents('data/customers.json', json_encode($customers, JSON_PRETTY_PRINT));
        $msg = "Pelanggan ditambah! Skor: " . $hasil['score'];
        $activeTab = "pelanggan";
    }
    if ($_POST['action'] === 'delete_customer') {
        $customers = array_values(array_filter($customers, fn($c) => $c['id'] != $_POST['id']));
        file_put_contents('data/customers.json', json_encode($customers, JSON_PRETTY_PRINT));
        $msg = "Pelanggan dihapus.";
        $activeTab = "pelanggan";
    }

    // --- TAB 2: PRODUK ---
    if ($_POST['action'] === 'add_product') {
        $products[] = [ "id" => time(), "name" => $_POST['name'], "price" => (int)$_POST['price'], "stock" => (int)$_POST['stock'] ];
        file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
        $msg = "Produk ditambahkan.";
        $activeTab = "produk";
    }
    if ($_POST['action'] === 'restock_product') {
        $key = array_search($_POST['id'], array_column($products, 'id'));
        if ($key !== false) {
            $products[$key]['stock'] += (int)$_POST['add_qty'];
            file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
            $msg = "Stok bertambah.";
        }
        $activeTab = "produk";
    }
    if ($_POST['action'] === 'delete_product') {
        $products = array_values(array_filter($products, fn($p) => $p['id'] != $_POST['id']));
        file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
        $msg = "Produk dihapus.";
        $activeTab = "produk";
    }

    // --- TAB 3: KENDARAAN (BARU) ---
    if ($_POST['action'] === 'add_car') {
        $cars[] = [
            "id" => time(),
            "plat" => strtoupper($_POST['plat']),
            "mobil" => $_POST['mobil'],
            "pemilik" => $_POST['pemilik'],
            "pajak_tahunan" => (int)$_POST['pajak_tahunan'],
            "tgl_pajak" => $_POST['tgl_pajak']
        ];
        file_put_contents($fileCars, json_encode($cars, JSON_PRETTY_PRINT));
        $msg = "Kendaraan ditambahkan.";
        $activeTab = "kendaraan";
    }
    if ($_POST['action'] === 'extend_tax') {
        $id = $_POST['id'];
        foreach ($cars as $k => $c) {
            if ($c['id'] == $id) {
                $cars[$k]['tgl_pajak'] = date('Y-m-d', strtotime('+1 year', strtotime($c['tgl_pajak'])));
                break;
            }
        }
        file_put_contents($fileCars, json_encode($cars, JSON_PRETTY_PRINT));
        $msg = "Pajak diperpanjang 1 tahun.";
        $activeTab = "kendaraan";
    }
    if ($_POST['action'] === 'delete_car') {
        $cars = array_values(array_filter($cars, fn($c) => $c['id'] != $_POST['id']));
        file_put_contents($fileCars, json_encode($cars, JSON_PRETTY_PRINT));
        $msg = "Kendaraan dihapus.";
        $activeTab = "kendaraan";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Master Data & Settings</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* STYLE KHUSUS TABS */
        .tab-container { overflow: hidden; border: 1px solid #ccc; background-color: #333; margin-top: 10px; }
        .tab-button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; color: #ccc; font-weight: bold; font-family: inherit; }
        .tab-button:hover { background-color: #555; color: white; }
        .tab-button.active { background-color: #fff; color: #333; border-bottom: 2px solid #fff; }
        
        /* STYLE KONTEN TAB */
        .tab-content { display: none; padding: 20px; border: 1px solid #ccc; border-top: none; background: #fff; animation: fadeEffect 0.5s; }
        @keyframes fadeEffect { from {opacity: 0;} to {opacity: 1;} }

        /* Helpers */
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .form-row input, .form-row select { flex: 1; }
        .btn-delete { background: #990000; color: #fff; border: none; padding: 5px 10px; cursor: pointer; font-size: 10px; }
        
        small { display: block; margin-bottom: 3px; font-weight: bold; color: #555; }
    </style>
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            // Sembunyikan semua konten
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            // Hapus class active dari semua tombol
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            // Tampilkan tab yang dipilih
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
</head>
<body onload="document.getElementById('btnDefault').click();">
    
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Settings & Master Data</h1>
            <p>Pusat Kontrol: Pelanggan, Barang, Aset Kendaraan, dan User.</p>
            <?php if($msg): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb;">
                    Info: <b><?= $msg ?></b>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-container">
            <button class="tab-button" onclick="openTab(event, 'TabPelanggan')" id="btnDefault">1. Pelanggan (Scoring)</button>
            <button class="tab-button" onclick="openTab(event, 'TabProduk')">2. Produk (Stok)</button>
            <button class="tab-button" onclick="openTab(event, 'TabKendaraan')">3. Aset Kendaraan (Pajak)</button>
            <button class="tab-button" onclick="openTab(event, 'TabUser')">4. User System</button>
        </div>

        <div id="TabPelanggan" class="tab-content">
            <h3>Management Pelanggan & Limit Kredit</h3>
            <form method="POST" style="background: #eef; padding: 15px; border: 1px solid #99c; margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_customer_scoring">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Nama Warung" required>
                    <input type="text" name="address" placeholder="Alamat" required>
                </div>
                <hr style="border:0; border-top:1px dashed #ccc; margin:10px 0;">
                <label style="font-size:12px; font-weight:bold;">Parameter Scoring:</label>
                <div class="form-row">
                    <select name="rata_transaksi" required><option value="10">Trx < 25 Juta</option><option value="30">Trx 25-50 Juta</option><option value="60">Trx 50-100 Juta</option><option value="110">Trx > 100 Juta</option></select>
                    <select name="telat_bayar" required><option value="0">Telat 0x (Bagus)</option><option value="3">Telat < 5x</option><option value="10">Telat 5-15x</option><option value="20">Telat > 15x</option></select>
                </div>
                <div class="form-row">
                    <select name="freq_transaksi" required><option value="1">Belanja Jarang</option><option value="3">Belanja Cukup</option><option value="6">Belanja Sering</option><option value="11">Sangat Sering</option></select>
                    <select name="lama_pelanggan" required><option value="0">Baru Gabung</option><option value="2">1-5 Tahun</option><option value="6">5-10 Tahun</option><option value="11">> 10 Tahun</option></select>
                </div>
                <button type="submit" style="width:100%; background:#0056b3;">SIMPAN DATA PELANGGAN</button>
            </form>

            <table>
                <thead><tr><th>Nama</th><th>Skor</th><th>Limit</th><th>Hutang</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= $c['name'] ?><br><small><?= $c['address'] ?></small></td>
                        <td><?= $c['score_data'] ?? '-' ?></td>
                        <td style="color:green; font-weight:bold;">Rp <?= number_format($c['limit']) ?></td>
                        <td>Rp <?= number_format($c['debt']) ?></td>
                        <td>
                            <?php if($c['debt'] == 0): ?>
                                <form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="action" value="delete_customer"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn-delete">Hapus</button></form>
                            <?php else: ?> <span style="color:red; font-size:10px;">Ada Hutang</span> <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="TabProduk" class="tab-content">
            <h3>Gudang & Stok Barang</h3>
            <form method="POST" style="background: #f9f9f9; padding: 15px; border: 1px dashed #999; margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_product">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Nama Barang" required>
                    <input type="number" name="price" placeholder="Harga Jual" required>
                    <input type="number" name="stock" placeholder="Stok Awal" required>
                    <button type="submit">TAMBAH</button>
                </div>
            </form>

            <table>
                <thead><tr><th>Barang</th><th>Harga</th><th>Stok</th><th>Restock</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['name'] ?></td>
                        <td>Rp <?= number_format($p['price']) ?></td>
                        <td style="font-weight:bold; font-size:16px;"><?= $p['stock'] ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="action" value="restock_product">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="number" name="add_qty" placeholder="+Qty" style="width:60px;" required>
                                <button type="submit" style="background:#333; padding:2px 8px;">+</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Hapus?');"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn-delete">X</button></form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="TabKendaraan" class="tab-content">
            <h3>Manajemen Aset Kendaraan & Pajak</h3>
            <div style="background: #fff3cd; padding: 10px; margin-bottom: 15px; border: 1px solid #ffeeba; color: #856404; font-size: 12px;">
                💡 Sistem akan otomatis memberi warna <b>MERAH</b> jika pajak mati, dan <b>KUNING</b> jika H-30 hari.
            </div>

            <form method="POST" style="background: #eef; padding: 15px; border: 1px solid #99c; margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_car">
                <div class="form-row">
                    <input type="text" name="plat" placeholder="Plat Nomor (Mis: B 1234 XY)" required style="flex:1">
                    <input type="text" name="mobil" placeholder="Jenis Mobil (Mis: GranMax)" required style="flex:2">
                </div>
                <div class="form-row">
                    <input type="text" name="pemilik" placeholder="Atas Nama (STNK)" required style="flex:2">
                    <input type="number" name="pajak_tahunan" placeholder="Biaya Pajak (Rp)" required style="flex:1">
                    <input type="date" name="tgl_pajak" required style="flex:1" title="Tanggal Jatuh Tempo">
                </div>
                <button type="submit" style="width:100%; background: #0056b3;">SIMPAN DATA KENDARAAN</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Plat</th>
                        <th>Kendaraan</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Biaya</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalBeban = 0;
                    foreach ($cars as $c): 
                        $totalBeban += $c['pajak_tahunan'];
                        $today = new DateTime();
                        $due = new DateTime($c['tgl_pajak']);
                        $diff = $today->diff($due);
                        $days = $diff->days;
                        $isLate = $today > $due;
                        
                        $bg = ""; $status = "Aman";
                        if ($isLate) { $bg = "#ffe6e6"; $status = "TELAT " . $days . " Hari"; }
                        elseif ($days <= 30) { $bg = "#fff3cd"; $status = "Warning (H-$days)"; }
                    ?>
                    <tr style="background: <?= $bg ?>">
                        <td style="font-weight:bold;"><?= $c['plat'] ?></td>
                        <td><?= $c['mobil'] ?><br><small>a.n <?= $c['pemilik'] ?></small></td>
                        <td><?= date('d M Y', strtotime($c['tgl_pajak'])) ?></td>
                        <td style="font-weight:bold; color: <?= $isLate ? 'red' : 'black' ?>"><?= $status ?></td>
                        <td>Rp <?= number_format($c['pajak_tahunan']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Perpanjang 1 tahun?');">
                                <input type="hidden" name="action" value="extend_tax">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" style="background:green; font-size:10px; padding:5px;">BAYAR</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus?');">
                                <input type="hidden" name="action" value="delete_car">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button class="btn-delete">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($cars)): ?><tr><td colspan="6" align="center">Belum ada data.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="TabUser" class="tab-content">
            <h3>Daftar Pengguna Sistem</h3>
            <ul>
                <?php foreach ($users as $u): ?>
                    <li style="padding: 10px; border-bottom: 1px solid #eee;">
                        <b><?= $u['username'] ?></b> - <?= $u['fullname'] ?> 
                        <span class="badge" style="background:#333; color:#fff; font-size:10px;"><?= $u['role'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>

    <script>
        // Default Buka Tab 1
        // Jika Anda ingin canggih bisa pakai LocalStorage, tapi ini cukup default ke Pelanggan.
        <?php if($activeTab == 'kendaraan'): ?>
            document.querySelector("button[onclick*='TabKendaraan']").click();
        <?php elseif($activeTab == 'produk'): ?>
            document.querySelector("button[onclick*='TabProduk']").click();
        <?php endif; ?>
    </script>
</body>
</html>