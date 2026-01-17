<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];

require_once __DIR__ . '/config/database.php';

// ==========================================
// 1. SETUP DATA & FILE JSON
// ==========================================
// Pastikan file JSON tersedia
$fileClaims = 'data/badstock_claims.json';
$fileInventory = 'data/badstock_inventory.json';

if (!file_exists($fileClaims)) file_put_contents($fileClaims, '[]');
if (!file_exists($fileInventory)) file_put_contents($fileInventory, '[]');

// Load Data
$claims = json_decode(file_get_contents($fileClaims), true);
$bsInventory = json_decode(file_get_contents($fileInventory), true);

// Load dari SQLite
$db = Database::getInstance();
$customers = $db->query("SELECT * FROM customers ORDER BY name");
$products = $db->query("SELECT * FROM products ORDER BY name");

$msg = "";
$activeTab = "form"; // Default tab

// ==========================================
// 2. HANDLE LOGIC (POST ACTION)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- AKSI: BUAT KLAIM BARU ---
    if ($_POST['action'] == 'create_claim') {
        // Handle Upload Gambar
        $fileName = "";
        if (!empty($_FILES['proof_image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir);
            $fileName = "BS_" . time() . "_" . basename($_FILES["proof_image"]["name"]);
            move_uploaded_file($_FILES["proof_image"]["tmp_name"], $targetDir . $fileName);
        }

        $newClaim = [
            "id" => "CLM-" . time(),
            "date" => date("Y-m-d H:i"),
            "customer_id" => $_POST['customer_id'],
            "product_id" => $_POST['product_id'],
            "qty" => (int)$_POST['quantity'],
            "reason" => $_POST['reason'],
            "proof_image" => $fileName,
            "status" => "PENDING", // PENDING, APPROVED, REJECTED
            "salesman" => $user['username']
        ];

        // Simpan ke Array (Paling atas = terbaru)
        array_unshift($claims, $newClaim);
        file_put_contents($fileClaims, json_encode($claims, JSON_PRETTY_PRINT));
        $msg = "Laporan Bad Stock berhasil dikirim. Menunggu Approval.";
        $activeTab = "list";
    }

    // --- AKSI: APPROVE KLAIM (ADMIN ONLY) ---
    if ($_POST['action'] == 'approve_claim') {
        // Cek Role (Sesuaikan dengan role admin di sistem Anda, misal AR_FINANCE atau buat role baru ADMIN)
        // Disini saya bebaskan semua role selain FAKTURIS boleh approve, atau sesuaikan kebutuhan.
        if ($user['role'] == 'FAKTURIS') {
            $msg = "Akses Ditolak. Hanya Admin/Gudang yang bisa approve.";
        } else {
            $claimId = $_POST['claim_id'];

            // 1. Update Status Klaim
            $key = array_search($claimId, array_column($claims, 'id'));
            if ($key !== false && $claims[$key]['status'] == 'PENDING') {
                $claims[$key]['status'] = 'APPROVED';
                $claims[$key]['approve_date'] = date("Y-m-d H:i");

                $prodId = $claims[$key]['product_id'];
                $qtyRusak = $claims[$key]['qty'];

                // 2. Potong Stok di Gudang Utama (products.json)
                $pkey = array_search($prodId, array_column($products, 'id'));
                if ($pkey !== false) {
                    $products[$pkey]['stock'] -= $qtyRusak;
                }

                // 3. Tambah Stok ke Gudang Bad Stock (badstock_inventory.json)
                // Cek apakah produk ini sudah ada di gudang badstock
                $bsKey = array_search($prodId, array_column($bsInventory, 'product_id'));
                if ($bsKey !== false) {
                    $bsInventory[$bsKey]['qty'] += $qtyRusak;
                } else {
                    // Cari nama produk untuk disimpan di badstock inventory (biar gampang baca)
                    $pName = $products[$pkey]['name'] ?? "Unknown Product";
                    $bsInventory[] = [
                        "product_id" => $prodId,
                        "product_name" => $pName,
                        "qty" => $qtyRusak
                    ];
                }

                // SIMPAN SEMUA
                file_put_contents($fileClaims, json_encode($claims, JSON_PRETTY_PRINT));
                
                // Update stok produk di SQLite
                $db->execute(
                    "UPDATE products SET stock = stock - :qty WHERE id = :id",
                    [
                        'qty' => $qtyRusak,
                        'id' => $prodId
                    ]
                );
                
                file_put_contents($fileInventory, json_encode($bsInventory, JSON_PRETTY_PRINT));

                $msg = "Klaim Disetujui. Stok Utama dipotong, masuk ke Bad Stock.";
                $activeTab = "list";
            }
        }
    }

    // --- AKSI: TOLAK KLAIM ---
    if ($_POST['action'] == 'reject_claim') {
        if ($user['role'] == 'FAKTURIS') {
            $msg = "Akses Ditolak.";
        } else {
            $claimId = $_POST['claim_id'];
            $key = array_search($claimId, array_column($claims, 'id'));
            if ($key !== false) {
                $claims[$key]['status'] = 'REJECTED';
                file_put_contents($fileClaims, json_encode($claims, JSON_PRETTY_PRINT));
                $msg = "Klaim Ditolak.";
                $activeTab = "list";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Manajemen Bad Stock</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Style Tambahan Khusus Halaman Ini */
        .tab-container {
            overflow: hidden;
            background-color: #333;
            margin-top: 20px;
            border-radius: 5px 5px 0 0;
        }

        .tab-button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 16px;
            transition: 0.3s;
            color: #ccc;
            font-weight: bold;
        }

        .tab-button:hover {
            background-color: #555;
            color: white;
        }

        .tab-button.active {
            background-color: #f4f4f4;
            color: #333;
            border-bottom: 3px solid #0056b3;
        }

        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            border-top: none;
            background: #fff;
        }

        .img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 1px solid #ccc;
            cursor: pointer;
        }

        .img-thumb:hover {
            transform: scale(2);
            transition: 0.2s;
            position: relative;
            z-index: 99;
        }
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
            if (evt) evt.currentTarget.className += " active";
        }
    </script>
</head>

<body onload="initTab()">
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Bad Stock Management</h1>
            <p>Kelola retur barang rusak dari toko.</p>
            <?php if ($msg): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin-top:10px;">
                    <?= $msg ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-container">
            <button class="tab-button" onclick="openTab(event, 'TabForm')" id="btnForm">Input Laporan</button>
            <button class="tab-button" onclick="openTab(event, 'TabList')" id="btnList">Daftar Klaim</button>
            <button class="tab-button" onclick="openTab(event, 'TabGudang')" id="btnGudang">Gudang Bad Stock</button>
        </div>

        <div id="TabForm" class="tab-content">
            <h3>Buat Laporan Barang Rusak</h3>
            <form method="POST" enctype="multipart/form-data" class="card" style="max-width: 600px;">
                <input type="hidden" name="action" value="create_claim">

                <label><b>Pilih Toko:</b></label><br>
                <select name="customer_id" required style="width:100%; padding:8px; margin-bottom:10px;">
                    <option value="">-- Pilih Toko --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>

                <label><b>Barang Rusak:</b></label><br>
                <select name="product_id" required style="width:100%; padding:8px; margin-bottom:10px;">
                    <option value="">-- Pilih Produk --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
                    <?php endforeach; ?>
                </select>

                <label><b>Jumlah (Qty):</b></label><br>
                <input type="number" name="quantity" min="1" required style="width:100%; padding:8px; margin-bottom:10px;">

                <label><b>Alasan Kerusakan:</b></label><br>
                <textarea name="reason" required style="width:100%; padding:8px; margin-bottom:10px;"></textarea>

                <label><b>Foto Bukti:</b></label><br>
                <input type="file" name="proof_image" accept="image/*" required style="margin-bottom:20px;">

                <button type="submit" style="padding:10px 20px; background: #0056b3; color:white; border:none;">KIRIM LAPORAN</button>
            </form>
        </div>

        <div id="TabList" class="tab-content">
            <h3>Daftar Klaim Masuk</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Toko / Sales</th>
                        <th>Barang</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($claims as $cl):
                        // Helper untuk cari nama
                        $custName = "Unknown";
                        $keyC = array_search($cl['customer_id'], array_column($customers, 'id'));
                        if ($keyC !== false) $custName = $customers[$keyC]['name'];

                        $prodName = "Unknown";
                        $keyP = array_search($cl['product_id'], array_column($products, 'id'));
                        if ($keyP !== false) $prodName = $products[$keyP]['name'];
                    ?>
                        <tr>
                            <td><?= $cl['date'] ?></td>
                            <td><?= $custName ?><br><small>By: <?= $cl['salesman'] ?></small></td>
                            <td><?= $prodName ?> (x<?= $cl['qty'] ?>)<br><small>Alasan: <?= $cl['reason'] ?></small></td>
                            <td>
                                <?php if ($cl['proof_image']): ?>
                                    <a href="uploads/<?= $cl['proof_image'] ?>" target="_blank">
                                        <img src="uploads/<?= $cl['proof_image'] ?>" class="img-thumb">
                                    </a>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background: 
                                <?= $cl['status'] == 'APPROVED' ? 'green' : ($cl['status'] == 'REJECTED' ? 'red' : 'orange') ?>">
                                    <?= $cl['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($cl['status'] == 'PENDING'): ?>
                                    <?php if ($user['role'] !== 'FAKTURIS'): ?>
                                        <form method="POST" onsubmit="return confirm('Setujui klaim? Stok utama akan dipotong.');" style="display:inline;">
                                            <input type="hidden" name="action" value="approve_claim">
                                            <input type="hidden" name="claim_id" value="<?= $cl['id'] ?>">
                                            <button type="submit" style="background:green; font-size:10px; margin-bottom:5px;">✔ ACC</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Tolak klaim ini?');" style="display:inline;">
                                            <input type="hidden" name="action" value="reject_claim">
                                            <input type="hidden" name="claim_id" value="<?= $cl['id'] ?>">
                                            <button type="submit" style="background:red; font-size:10px;">✖ TOLAK</button>
                                        </form>
                                    <?php else: ?>
                                        <small>Menunggu Admin</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="TabGudang" class="tab-content">
            <h3>Inventory Gudang Bad Stock (BS)</h3>
            <p>Barang di sini adalah barang rusak yang sudah di-approve dan ditarik dari stok utama.</p>
            <table>
                <thead>
                    <tr>
                        <th>ID Produk</th>
                        <th>Nama Barang</th>
                        <th>Qty Rusak (Terkumpul)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bsInventory as $bs): ?>
                        <tr>
                            <td><?= $bs['product_id'] ?></td>
                            <td><?= $bs['product_name'] ?></td>
                            <td style="font-weight:bold; color:red;"><?= $bs['qty'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bsInventory)) echo "<tr><td colspan='3'>Gudang Bad Stock Kosong.</td></tr>"; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        function initTab() {
            <?php if ($activeTab == 'list'): ?>
                document.getElementById('btnList').click();
            <?php elseif ($activeTab == 'gudang'): ?>
                document.getElementById('btnGudang').click();
            <?php else: ?>
                document.getElementById('btnForm').click();
            <?php endif; ?>
        }
    </script>
</body>

</html>