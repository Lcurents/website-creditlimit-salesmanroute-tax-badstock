<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

// CONFIG
$file = 'data/cars.json';
if (!file_exists($file)) file_put_contents($file, '[]');
$cars = json_decode(file_get_contents($file), true);
$msg = "";

// ==========================================
// HANDLE LOGIC (Sesuai Activity Diagram)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // 1. UPLOAD BUKTI BAYAR (Langkah Awal)
    if ($_POST['action'] == 'upload_proof') {
        $id = $_POST['id'];
        
        // Handle File Upload
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir); // Buat folder jika belum ada
        
        $fileName = time() . "_" . basename($_FILES["bukti_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        // Simpan File
        if(move_uploaded_file($_FILES["bukti_file"]["tmp_name"], $targetFilePath)){
            // Update JSON
            foreach ($cars as $k => $c) {
                if ($c['id'] == $id) {
                    $cars[$k]['bukti_img'] = $fileName;
                    $cars[$k]['status_validasi'] = 'PENDING'; // Status Menunggu Admin
                    break;
                }
            }
            file_put_contents($file, json_encode($cars, JSON_PRETTY_PRINT));
            $msg = "Bukti berhasil diupload. Menunggu Validasi Admin.";
        } else {
            $msg = "Gagal upload gambar.";
        }
    }

    // 2. VALIDASI PEMBAYARAN (Sesuai Diagram: Setujui / Tolak)
    if ($_POST['action'] == 'validate_payment') {
        $id = $_POST['id'];
        $keputusan = $_POST['decision']; // 'APPROVE' atau 'REJECT'

        foreach ($cars as $k => $c) {
            if ($c['id'] == $id) {
                if ($keputusan == 'APPROVE') {
                    // Update Tanggal (Perpanjang 1 Tahun)
                    $newDate = date('Y-m-d', strtotime('+1 year', strtotime($c['tgl_pajak'])));
                    $cars[$k]['tgl_pajak'] = $newDate;
                    $cars[$k]['status_validasi'] = 'APPROVED';
                    $sysMsg = "Laporan Disetujui. Pajak diperpanjang.";
                } else {
                    // Jika Ditolak
                    $cars[$k]['status_validasi'] = 'REJECTED';
                    $sysMsg = "Laporan Ditolak. Harap upload bukti baru.";
                }
                break;
            }
        }
        file_put_contents($file, json_encode($cars, JSON_PRETTY_PRINT));
        $msg = $sysMsg;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Validasi Pajak - Activity Diagram</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS Tambahan untuk Modal & Status */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 50%; text-align: center; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
        
        .btn-upload { background: #007bff; color: white; border: none; cursor: pointer; font-size: 11px; padding: 5px 10px; }
        .btn-check { background: #ffc107; color: black; border: none; cursor: pointer; font-weight: bold; font-size: 11px; padding: 5px 10px; }
        
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; color: white; }
        .st-pending { background: orange; }
        .st-approved { background: green; }
        .st-rejected { background: red; }
        .st-none { background: #ccc; color: #333; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="header-box">
            <h1>Validasi Pajak Kendaraan</h1>
            <p>Sistem Approval Bukti Pembayaran (Sesuai Diagram).</p>
            <?php if($msg): ?>
                <div style="background: #dff0d8; color: #3c763d; padding: 10px; margin-top:10px; border: 1px solid #d6e9c6;">
                    <?= $msg ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Daftar Validasi</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kendaraan</th>
                        <th>Jatuh Tempo</th>
                        <th>Status Validasi</th>
                        <th>Bukti Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($cars as $c): 
                        // Cek Status Validasi
                        $statusVal = $c['status_validasi'] ?? 'NONE';
                        $imgBukti = $c['bukti_img'] ?? '';
                        
                        // Hitung Telat/Tidak (Hanya visual)
                        $isLate = date('Y-m-d') > $c['tgl_pajak'];
                    ?>
                    <tr>
                        <td>
                            <b><?= $c['plat'] ?></b><br>
                            <?= $c['mobil'] ?>
                        </td>
                        <td style="color: <?= $isLate ? 'red' : 'black' ?>">
                            <?= date('d M Y', strtotime($c['tgl_pajak'])) ?>
                            <?= $isLate ? '<br><small>(Perlu Bayar)</small>' : '' ?>
                        </td>
                        <td>
                            <?php if($statusVal == 'PENDING'): ?>
                                <span class="status-badge st-pending">MENUNGGU VALIDASI</span>
                            <?php elseif($statusVal == 'APPROVED'): ?>
                                <span class="status-badge st-approved">DISETUJUI</span>
                            <?php elseif($statusVal == 'REJECTED'): ?>
                                <span class="status-badge st-rejected">DITOLAK</span>
                            <?php else: ?>
                                <span class="status-badge st-none">BELUM UPLOAD</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($imgBukti): ?>
                                <a href="uploads/<?= $imgBukti ?>" target="_blank" style="font-size:12px; color:blue;">Lihat File</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($statusVal == 'NONE' || $statusVal == 'REJECTED' || $statusVal == 'APPROVED'): ?>
                                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:5px; align-items:center;">
                                    <input type="hidden" name="action" value="upload_proof">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <input type="file" name="bukti_file" required style="width:180px; font-size:10px;">
                                    <button type="submit" class="btn-upload">Upload Bukti</button>
                                </form>

                            <?php elseif($statusVal == 'PENDING'): ?>
                                <button onclick="openValidationModal('<?= $c['id'] ?>', '<?= $c['plat'] ?>', 'uploads/<?= $imgBukti ?>')" class="btn-check">
                                    🔍 PERIKSA / VALIDASI
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="validationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Validasi Pembayaran</h2>
            <p>Kendaraan: <b id="modalPlat"></b></p>
            
            <div style="margin: 20px 0; background: #eee; padding: 10px;">
                <img id="modalImg" src="" alt="Bukti Bayar" style="max-width: 100%; max-height: 300px; border: 1px solid #333;">
                <p>Silakan periksa bukti transfer di atas.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="validate_payment">
                <input type="hidden" name="id" id="modalId">
                
                <div style="display: flex; gap: 20px; justify-content: center;">
                    <button type="submit" name="decision" value="REJECT" 
                        style="background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer;"
                        onclick="return confirm('Yakin ingin MENOLAK bukti ini?');">
                        ❌ TOLAK
                    </button>
                    
                    <button type="submit" name="decision" value="APPROVE" 
                        style="background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;"
                        onclick="return confirm('Bukti valid? Pajak akan diperpanjang.');">
                        ✅ SETUJUI
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Javascript untuk mengatur Modal Popup
        var modal = document.getElementById("validationModal");

        function openValidationModal(id, plat, imgSrc) {
            document.getElementById("modalId").value = id;
            document.getElementById("modalPlat").innerText = plat;
            document.getElementById("modalImg").src = imgSrc;
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        // Tutup modal jika klik di luar kotak
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>