<?php
/**
 * NEW LOGIN SYSTEM with SQLite & Password Hash
 * Security Improved Version
 */
session_start();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password']; // Jangan di-clean password karena bisa menghilangkan karakter khusus
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $db = Database::getInstance();
        
        // Query user dari database
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $users = $db->query($sql, ['username' => $username]);
        
        if (!empty($users)) {
            $user = $users[0];
            
            // Verify password dengan password_verify (secure)
            if (password_verify($password, $user['password'])) {
                // Login success - simpan data user ke session
                unset($user['password']); // Jangan simpan password di session
                $_SESSION['user'] = $user;
                
                // Redirect ke dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = "Username atau Password salah!";
            }
        } else {
            $error = "Username atau Password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Distribusi App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2>🔐 LOGIN SYSTEM</h2>
        <p>Distribusi & Sales Order Management</p>
        
        <?php if($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #f5c6cb;">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label for="username" style="display: block; margin-bottom: 5px; font-weight: bold;">Username:</label>
                <input type="text" name="username" id="username" placeholder="Masukkan username" required 
                       style="width: 100%; padding: 12px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">Password:</label>
                <input type="password" name="password" id="password" placeholder="Masukkan password" required 
                       style="width: 100%; padding: 12px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <button type="submit" style="width: 100%; padding: 12px; background: #333; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
                MASUK
            </button>
        </form>
        
        <p style="font-size: 12px; margin-top: 20px; color: #666; border-top: 1px solid #ddd; padding-top: 15px;">
            <strong>Demo Accounts:</strong><br>
            • Fakturis: <code>faktur</code> / <code>123</code><br>
            • Finance: <code>finance</code> / <code>123</code><br>
            • Gudang: <code>gudang</code> / <code>123</code>
        </p>
    </div>
</body>
</html>
