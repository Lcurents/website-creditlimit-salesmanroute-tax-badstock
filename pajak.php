<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

$user = $_SESSION['user'];
$role = $user['role']; // Ambil role (WAREHOUSE atau FAKTURIS)

// CONFIG
$file = 'data/cars.json';
if (!file_exists($file)) file_put_contents($file, '[]');
$cars = json_decode(file_get_contents($file), true);
$msg = "";

// ==========================================
// HANDLE LOGIC (UPLOAD & VALIDASI)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // 1. WAREHOUSE UPLOAD
    if ($_POST['action'] == 'upload_proof' && $role == 'WAREHOUSE') {
        $id = $_POST['id'];
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir); 
        
        $fileName = time() . "_" . basename($_FILES["bukti_file"]["name"]);
        if(move_uploaded_file($_FILES["bukti_file"]["tmp_name"], $targetDir . $fileName)){
            foreach ($cars as $k => $c) {
                if ($c['id'] == $id) {
                    $cars[$k]['bukti_img'] = $fileName;
                    $cars[$k]['status_validasi'] = 'PENDING';
                    break;
                }
            }
            file_put_contents($file, json_encode($cars, JSON_PRETTY_PRINT));
            $msg = "Bukti terupload! Menunggu approval Fakturis.";
        }
    }

    // 2. FAKTURIS VALIDASI
    if ($_POST['action'] == 'validate_payment' && $role == 'FAKTURIS') {
        $id = $_POST['id'];
        $keputusan = $_POST['decision']; // 'APPROVE' atau 'REJECT'

        foreach ($cars as $k => $c) {
            if ($c['id'] == $id) {
                if ($keputusan == 'APPROVE') {
                    $newDate = date('Y-m-d', strtotime('+1 year', strtotime($c['tgl_pajak'])));
                    $cars[$k]['tgl_pajak'] = $newDate;
                    $cars[$k]['status_validasi'] = 'APPROVED';
                    $msg = "Disetujui. Pajak diperpanjang.";
                } else {
                    $cars[$k]['status_validasi'] = 'REJECTED';
                    $msg = "Ditolak. Gudang harus upload ulang.";
                }
                break;
            }
        }
        file_put_contents($file, json_encode($cars, JSON_PRETTY_PRINT));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pajak Kendaraan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .modal { display: none; position: fixed; z-index: 99; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 40%; text-align: center; }
        .status-badge { padding: 5px 10px; font-size: 10px; color: white; border-radius: 4px; font-weight: bold; }
        .st-pending { background: orange; } .st-approved { background: green; } .st-rejected { background: red; } .st-none { background: #999; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="header-box">
            <h1>Pajak Kendaraan (<?= $role ?>)</h1>
            <p>Sistem Validasi Pajak: Gudang Lapor -> Fakturis Validasi.</p>
            <?php if($msg): ?><div style="background:#dff0d8; padding:10px; border:1px solid green;"><?= $msg ?></div><?php endif; ?>
        </div>

        <div class="card">
            <h3>Daftar Kendaraan Operasional</h3>
            <table>
                <thead>
                    <tr>
                        <th>Plat</th>
                        <th>Nama Kendaraan</th>
                        <th>Driver</th>
                        <th>Jatuh Tempo</th>
                        <th>Status H-</th>
                        <th>Status Validasi</th>
                        <th>Lihat Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $c): 
                        $status = $c['status_validasi'] ?? 'NONE';
                        $img = $c['bukti_img'] ?? '';
                        
                        // Cek Jatuh Tempo (H-30)
                        $today = new DateTime();
                        $due = new DateTime($c['tgl_pajak']);
                        $diff = $today->diff($due);
                        $daysLeft = $diff->invert ? -1 * $diff->days : $diff->days;
                        $isDue = ($daysLeft <= 30); // Muncul jika H-30 atau sudah lewat
                    ?>
                    <tr>
                        <td style="font-weight:bold"><?= $c['plat'] ?></td>
                        <td><?= $c['mobil'] ?></td>
                        <td><?= $c['driver'] ?? $c['pemilik'] ?></td>
                        <td>
                            <?= date('d-m-Y', strtotime($c['tgl_pajak'])) ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if($daysLeft < 0): ?>
                                <span style="background: #dc3545; color: white; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-block; white-space: nowrap;">
                                    🔴 TELAT <?= abs($daysLeft) ?> HARI
                                </span>
                            <?php elseif($daysLeft <= 7): ?>
                                <span style="background: #ff6b6b; color: white; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-block; white-space: nowrap;">
                                    🟠 H-<?= $daysLeft ?>
                                </span>
                            <?php elseif($daysLeft <= 30): ?>
                                <span style="background: #ffc107; color: #000; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-block; white-space: nowrap;">
                                    🟡 H-<?= $daysLeft ?>
                                </span>
                            <?php else: ?>
                                <span style="background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-block;">
                                    ✅ AMAN
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if($status == 'PENDING'): ?><span class="status-badge st-pending">MENUNGGU VALIDASI</span>
                            <?php elseif($status == 'APPROVED'): ?><span class="status-badge st-approved">SELESAI</span>
                            <?php elseif($status == 'REJECTED'): ?><span class="status-badge st-rejected">DITOLAK (UPLOAD ULANG)</span>
                            <?php else: ?><span class="status-badge st-none">BELUM ADA</span><?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if($img): ?>
                                <a href="uploads/<?= $img ?>" target="_blank" style="background: #007bff; color: white; padding: 5px 12px; border-radius: 5px; text-decoration: none; font-size: 11px; display: inline-block;">
                                    🖼️ LIHAT FOTO
                                </a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 11px;">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($role == 'WAREHOUSE'): ?>
                                <?php if (($status == 'NONE' || $status == 'REJECTED' || $status == 'APPROVED') && $isDue): ?>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="upload_proof">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="file" name="bukti_file" required style="font-size:10px; width:90px;">
                                        <button style="font-size:10px; background:blue;">UPLOAD</button>
                                    </form>
                                <?php elseif ($status == 'PENDING'): ?>
                                    <small><i>Menunggu Fakturis...</i></small>
                                <?php else: ?>
                                    <small style="color:green;">Aman</small>
                                <?php endif; ?>
                            
                            <?php elseif ($role == 'FAKTURIS'): ?>
                                <?php if ($status == 'PENDING'): ?>
                                    <button onclick="openModal('<?= $c['id'] ?>', '<?= $c['plat'] ?>', 'uploads/<?= $img ?>')" style="background:orange; color:black;">🔍 VALIDASI</button>
                                <?php else: ?>
                                    <small>-</small>
                                <?php endif; ?>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="valModal" class="modal">
        <div class="modal-content">
            <span onclick="document.getElementById('valModal').style.display='none'" style="float:right; cursor:pointer; font-weight:bold;">X</span>
            <h3>Validasi Pembayaran: <span id="mPlat"></span></h3>
            <img id="mImg" src="" style="max-height:200px; border:1px solid #333; margin:10px 0;">
            <form method="POST">
                <input type="hidden" name="action" value="validate_payment">
                <input type="hidden" name="id" id="mId">
                <br>
                <button name="decision" value="REJECT" style="background:red;">TOLAK ❌</button>
                <button name="decision" value="APPROVE" style="background:green;">SETUJUI ✅</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id, plat, img) {
            document.getElementById('mId').value = id;
            document.getElementById('mPlat').innerText = plat;
            document.getElementById('mImg').src = img;
            document.getElementById('valModal').style.display = 'block';
        }
    </script>
</body>
</html>