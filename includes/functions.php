<?php
/**
 * Common Functions & Helpers
 * Utility functions yang sering dipakai
 */

// Security: Sanitize Input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Security: Check User Session
function check_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
    return $_SESSION['user'];
}

// Security: Check User Role
function check_role($required_roles = []) {
    $user = check_login();
    
    if (!in_array($user['role'], $required_roles)) {
        header("Location: index.php");
        exit;
    }
    
    return $user;
}

// Format Currency
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Format Date Indonesia
function tanggal_indo($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode('-', $date);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Generate Random Code
function generate_code($prefix = 'INV') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Alert Message
function alert_message($message, $type = 'success') {
    $colors = [
        'success' => '#d4edda',
        'danger' => '#f8d7da',
        'warning' => '#fff3cd',
        'info' => '#d1ecf1'
    ];
    
    $text_colors = [
        'success' => '#155724',
        'danger' => '#721c24',
        'warning' => '#856404',
        'info' => '#0c5460'
    ];
    
    return "<div style='background: {$colors[$type]}; color: {$text_colors[$type]}; padding: 12px; margin: 10px 0; border-radius: 5px; border: 1px solid {$text_colors[$type]};'>
                {$message}
            </div>";
}

// Get Status Badge HTML
function status_badge($status) {
    $colors = [
        'ON HOLD' => 'background: orange; color: white;',
        'APPROVED' => 'background: green; color: white;',
        'DELIVERED' => 'background: black; color: white;',
        'PAID' => 'background: blue; color: white;',
        'PENDING' => 'background: orange; color: white;',
        'REJECTED' => 'background: red; color: white;'
    ];
    
    $style = $colors[$status] ?? 'background: gray; color: white;';
    
    return "<span class='badge' style='$style padding: 5px 10px; border-radius: 3px; font-size: 11px; font-weight: bold;'>$status</span>";
}

// Upload File Helper
function upload_file($file_input_name, $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 2097152) {
    $target_dir = __DIR__ . "/../uploads/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    $file = $_FILES[$file_input_name];
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['error' => 'File terlalu besar. Maksimal ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    // Check extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        return ['error' => 'Format file tidak diizinkan. Hanya: ' . implode(', ', $allowed_extensions)];
    }
    
    // Generate unique filename
    $new_filename = time() . '_' . uniqid() . '.' . $file_ext;
    $target_path = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['error' => 'Gagal mengupload file'];
    }
}

// Delete File Helper
function delete_file($filename) {
    $file_path = __DIR__ . "/../uploads/" . $filename;
    
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

// Pagination Helper
function paginate($total_items, $items_per_page = 20, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $items_per_page
    ];
}
