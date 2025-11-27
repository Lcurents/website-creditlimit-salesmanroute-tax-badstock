<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = json_decode(file_get_contents('data/users.json'), true);
    $u = $_POST['username'];
    $p = $_POST['password'];
    
    $found = false;
    foreach ($users as $user) {
        if ($user['username'] === $u && $user['password'] === $p) {
            $_SESSION['user'] = $user; // Simpan data user di sesi
            header('Location: index.php'); // Lempar ke Dashboard
            exit;
        }
    }
    $error = "Username atau Password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2>LOGIN SYSTEM</h2>
        <p>Distribusi & Sales Order</p>
        
        <?php if($error): ?>
            <div style="background: #333; color: #fff; padding: 10px; margin-bottom: 10px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required style="width: 90%; margin-bottom: 15px;">
            <input type="password" name="password" placeholder="Password" required style="width: 90%; margin-bottom: 15px;">
            <button type="submit" style="width: 100%;">MASUK</button>
        </form>
        <p style="font-size: 12px; margin-top: 20px; color: #666;">
            *Simulasi: username: <b>faktur</b> / pass: <b>123</b>
        </p>
    </div>
</body>
</html>