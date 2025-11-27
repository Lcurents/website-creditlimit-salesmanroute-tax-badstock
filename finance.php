<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
$user = $_SESSION['user'];

if ($user['role'] !== 'AR_FINANCE' && $user['role'] !== 'CASHIER') {
    header("Location: index.php"); exit;
}

$customers = json_decode(file_get_contents('data/customers.json'), true);
$orders = json_decode(file_get_contents('data/orders.json'), true);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custId = $_POST['customer_id'];
    $bayar = (int)$_POST['amount']; // Uang yang disetor

    // 1. UPDATE TOTAL HUTANG (Logic Lama)
    $custKey = array_search($custId, array_column($customers, 'id'));
    $customers[$custKey]['debt'] -= $bayar;
    if($customers[$custKey]['debt'] < 0) $customers[$custKey]['debt'] = 0;

    // 2. ALGORITMA FIFO: UPDATE STATUS ORDER JADI 'PAID' (Logic Baru)
    // Kita cari order milik customer ini yang statusnya DELIVERED
    // Note: $orders di JSON sudah urut dari Baru -> Lama (karena array_unshift)
    // Jadi kita harus balik urutannya atau iterasi dari belakang jika ingin melunasi yang lama dulu.
    // Tapi untuk simplifikasi PHP Array, kita filter dulu.
    
    // Ambil semua index order milik user ini yang DELIVERED
    $targetIndexes = [];
    foreach ($orders as $idx => $o) {
        if ($o['customer_id'] == $custId && $o['status'] == 'DELIVERED') {
            $targetIndexes[] = $idx;
        }
    }

    // Urutkan index dari yang paling BESAR ke KECIL 
    // (Karena di JSON, data lama ada di index bawah/besar)
    rsort($targetIndexes); 

    $sisaUang = $bayar;
    $countLunas = 0;

    foreach ($targetIndexes as $idx) {
        if ($sisaUang <= 0) break; // Uang habis

        $tagihanOrder = $orders[$idx]['total'];

        // Jika uang cukup untuk melunasi satu faktur ini
        if ($sisaUang >= $tagihanOrder) {
            $orders[$idx]['status'] = 'PAID'; // Ubah status jadi LUNAS
            $orders[$idx]['paid_date'] = date('Y-m-d'); // Catat tgl bayar
            $sisaUang -= $tagihanOrder;
            $countLunas++;
        } 
        // Jika uang tidak cukup (Bayar nyicil), status tetap DELIVERED
        // atau Anda bisa buat status 'PARTIAL'. 
        // Untuk sistem ini, kita biarkan DELIVERED sampai lunas full.
        else {
            $sisaUang = 0; // Uang habis dipakai nyicil
        }
    }

    // SIMPAN SEMUA PERUBAHAN
    file_put_contents('data/customers.json', json_encode($customers, JSON_PRETTY_PRINT));
    file_put_contents('data/orders.json', json_encode($orders, JSON_PRETTY_PRINT));
    
    $msg = "Pembayaran Rp " . number_format($bayar) . " diterima. $countLunas Faktur lama statusnya berubah jadi PAID.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Keuangan - Pelunasan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Keuangan & Penagihan (FIFO)</h1>
            <p>Pembayaran akan otomatis melunasi Faktur terlama terlebih dahulu.</p>
            <?php if($msg): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb;">
                    <?= $msg ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Input Pelunasan / Setoran Sales</h3>
            <form method="POST">
                <label>Pilih Warung yang Bayar:</label><br>
                <select name="customer_id" required style="width: 100%; margin-bottom: 10px; padding: 10px;">
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= $c['name'] ?> | Hutang Total: Rp <?= number_format($c['debt']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label>Nominal Pembayaran (Rp):</label><br>
                <input type="number" name="amount" placeholder="Contoh: 1000000" required style="width: 100%; padding: 10px;">
                
                <button type="submit" style="width: 100%; margin-top: 10px; background: green;">PROSES PELUNASAN</button>
            </form>
        </div>

        <div class="card">
            <h3>Monitoring Status Faktur (Belum Lunas)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Order</th>
                        <th>Jatuh Tempo</th>
                        <th>Pelanggan</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    foreach ($orders as $o): 
                        if($o['status'] == 'DELIVERED'): // Hanya tampilkan yang belum lunas
                        $count++;
                        
                        // Cek Telat
                        $isLate = date('Y-m-d') > ($o['due_date'] ?? '9999-99-99');
                    ?>
                    <tr style="<?= $isLate ? 'background-color: #ffe6e6;' : '' ?>">
                        <td><?= $o['date'] ?></td>
                        <td>
                            <?= $o['due_date'] ?? '-' ?>
                            <?php if($isLate) echo "<br><b style='color:red; font-size:10px;'>(OVERDUE)</b>"; ?>
                        </td>
                        <td><?= $o['customer'] ?></td>
                        <td>Rp <?= number_format($o['total']) ?></td>
                        <td><span class="badge" style="background:black; color:white;">BELUM LUNAS</span></td>
                    </tr>
                    <?php 
                        endif;
                    endforeach; 
                    
                    if($count == 0) echo "<tr><td colspan='5' style='text-align:center'>Semua tagihan bersih (Lunas).</td></tr>";
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>