<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$user = $_SESSION['user'];

// ==========================================
// 1. LOAD DATA DARI JSON
// ==========================================
$customers = json_decode(file_get_contents('data/customers.json'), true);
$products = json_decode(file_get_contents('data/products.json'), true);
$users = json_decode(file_get_contents('data/users.json'), true);

$msg = "";

// ==========================================
// 2. FUNGSI LOGIKA: CREDIT SCORING (JURNAL)
// ==========================================
function hitungCreditScore($rataJuta, $telatKali, $freqBulanan, $lamaTahun) {
    // A. KRITERIA 1: JUMLAH RATA-RATA TRANSAKSI (BOBOT 10)
    if ($rataJuta > 100) $s1 = 20;
    elseif ($rataJuta >= 50) $s1 = 15;
    elseif ($rataJuta >= 25) $s1 = 10;
    else $s1 = 5;
    $total1 = $s1 * 10;

    // B. KRITERIA 2: KETERLAMBATAN PEMBAYARAN (BOBOT 30)
    if ($telatKali == 0) $s2 = 20;
    elseif ($telatKali < 5) $s2 = 15;
    elseif ($telatKali < 15) $s2 = 10;
    else $s2 = 5;
    $total2 = $s2 * 30;

    // C. KRITERIA 3: FREKUENSI TRANSAKSI PER BULAN (BOBOT 20)
    if ($freqBulanan > 10) $s3 = 20;
    elseif ($freqBulanan >= 5) $s3 = 15;
    elseif ($freqBulanan >= 2) $s3 = 10;
    else $s3 = 5;
    $total3 = $s3 * 20;

    // D. KRITERIA 4: LAMANYA MENJADI PELANGGAN (BOBOT 40)
    if ($lamaTahun > 10) $s4 = 20;
    elseif ($lamaTahun >= 5) $s4 = 15;
    elseif ($lamaTahun >= 1) $s4 = 10;
    else $s4 = 5;
    $total4 = $s4 * 40;

    // TOTAL SCORE (Maksimal 2000)
    $grandScore = $total1 + $total2 + $total3 + $total4;

    // PENENTUAN LIMIT (TABEL 3 JURNAL)
    if ($grandScore < 500) $limit = 0; // Tunai
    elseif ($grandScore < 1000) $limit = 25000000; // 25 Juta
    elseif ($grandScore < 1500) $limit = 50000000; // 50 Juta
    else $limit = 100000000; // 100 Juta

    return ['score' => $grandScore, 'limit' => $limit];
}

// ==========================================
// 3. HANDLE FORM POST REQUESTS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- A. TAMBAH CUSTOMER (DENGAN SCORING) ---
    if ($_POST['action'] === 'add_customer_scoring') {
        // Ambil nilai simulasi untuk perhitungan
        $rata = (int)$_POST['rata_transaksi'];
        $telat = (int)$_POST['telat_bayar'];
        $freq = (int)$_POST['freq_transaksi'];
        $lama = (int)$_POST['lama_pelanggan'];

        // Jalankan Rumus
        $hasil = hitungCreditScore($rata, $telat, $freq, $lama);

        $newCust = [
            "id" => time(),
            "name" => $_POST['name'],
            "address" => $_POST['address'],
            "limit" => $hasil['limit'],     // Limit Otomatis
            "score_data" => $hasil['score'], // Simpan skor utk referensi
            "debt" => 0
        ];
        $customers[] = $newCust;
        file_put_contents('data/customers.json', json_encode($customers, JSON_PRETTY_PRINT));
        $msg = "Pelanggan ditambah! Skor: " . $hasil['score'] . " (Limit Rp " . number_format($hasil['limit']) . ")";
    }

    // --- B. HAPUS CUSTOMER ---
    if ($_POST['action'] === 'delete_customer') {
        $idToDelete = $_POST['id'];
        $customers = array_filter($customers, function($c) use ($idToDelete) {
            return $c['id'] != $idToDelete;
        });
        $customers = array_values($customers);
        file_put_contents('data/customers.json', json_encode($customers, JSON_PRETTY_PRINT));
        $msg = "Data pelanggan dihapus.";
    }

    // --- C. TAMBAH PRODUK ---
    if ($_POST['action'] === 'add_product') {
        $newProd = [
            "id" => time(),
            "name" => $_POST['name'],
            "price" => (int)$_POST['price'],
            "stock" => (int)$_POST['stock']
        ];
        $products[] = $newProd;
        file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
        $msg = "Produk baru berhasil ditambahkan.";
    }

    // --- D. RESTOCK PRODUK (TAMBAH STOK) ---
    if ($_POST['action'] === 'restock_product') {
        $id = $_POST['id'];
        $addQty = (int)$_POST['add_qty'];
        
        $key = array_search($id, array_column($products, 'id'));
        if ($key !== false) {
            $products[$key]['stock'] += $addQty;
            file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
            $msg = "Stok berhasil ditambahkan.";
        }
    }

    // --- E. HAPUS PRODUK ---
    if ($_POST['action'] === 'delete_product') {
        $idToDelete = $_POST['id'];
        $products = array_filter($products, function($p) use ($idToDelete) {
            return $p['id'] != $idToDelete;
        });
        $products = array_values($products);
        file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));
        $msg = "Data produk dihapus.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Settings - Master Data</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .form-row input, .form-row select { flex: 1; padding: 8px; }
        .btn-delete { background: #990000; color: #fff; border: none; padding: 5px 10px; cursor: pointer; }
        .section-title { border-bottom: 2px solid #333; margin-bottom: 15px; padding-bottom: 5px; }
        small { display: block; margin-bottom: 3px; font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Settings & Master Data</h1>
            <p>Kelola data Pelanggan (Scoring), Barang (Restock), dan User.</p>
            <?php if($msg): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin-top: 10px;">
                    Info: <b><?= $msg ?></b>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="section-title">1. Data Pelanggan (Smart Credit Scoring)</h3>
            <p style="font-size: 12px; color: #666; margin-bottom: 15px;">
                Sistem menghitung limit otomatis berdasarkan profil risiko (Metode Bobot Jurnal).
            </p>
            
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

                <button type="submit" style="width:100%; margin-top:10px; background: #0056b3;">HITUNG SKOR & SIMPAN DATA</button>
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
                        <td><?= $c['name'] ?><br><small><?= $c['address'] ?></small></td>
                        <td>
                            <?php if(isset($c['score_data'])): ?>
                                <span class="badge" style="background:#ddd; color:#000;"><?= $c['score_data'] ?></span>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td style="color: green; font-weight:bold;">Rp <?= number_format($c['limit']) ?></td>
                        <td>Rp <?= number_format($c['debt']) ?></td>
                        <td>
                            <?php if($c['debt'] > 0): ?>
                                <span style="font-size: 10px; color: red;">(Ada Hutang)</span>
                            <?php else: ?>
                                <form method="POST" onsubmit="return confirm('Hapus warung ini?');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="btn-delete">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="profile.php?id=<?= $c['id'] ?>" class="badge" style="background:blue; color:white; text-decoration:none;">
                                📊 Profil & Skor
                            </a>

                            <?php if($c['debt'] == 0): ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h3 class="section-title">2. Data Produk & Stok</h3>

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

        <div class="card">
            <h3 class="section-title">3. Data User System</h3>
            <ul>
                <?php foreach ($users as $u): ?>
                    <li>
                        <b><?= $u['username'] ?></b> - <?= $u['fullname'] ?> 
                        <span class="badge" style="font-size: 10px;"><?= $u['role'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>
</body>
</html>