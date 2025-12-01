<div class="sidebar">
    <h2>DISTRIBUSI APP</h2>
    <div style="padding: 10px 20px; font-size: 12px; color: #aaa;">
        User: <?= $_SESSION['user']['fullname'] ?? 'Guest' ?><br>
        Role: <b><?= $_SESSION['user']['role'] ?? '-' ?></b>
    </div>
    <ul class="menu">
        <li>
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                DASHBOARD
            </a>
        </li>
        <li>
            <a href="distribution.php" class="<?= basename($_SERVER['PHP_SELF']) == 'distribution.php' ? 'active' : '' ?>">
                DISTRIBUSI (Sales)
            </a>
        </li>
        <li>
            <a href="finance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'finance.php' ? 'active' : '' ?>">
                KEUANGAN (Bayar)
            </a>
        </li>
        <li>
            <a href="pajak.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pajak.php' ? 'active' : '' ?>">
                PAJAK KENDARAAN
            </a>
        </li>
        <li>
            <a href="schedule.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pajak.php' ? 'active' : '' ?>">
                SALESMAN
            </a>
        </li>
        <li>
            <a href="badstock.php" class="<?= basename($_SERVER['PHP_SELF']) == 'badstock.php' ? 'active' : '' ?>">
                BADSTOCK
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                SETTINGS
            </a>
        </li>
        <li>
            <a href="logout.php" style="color: #ff9999;">LOGOUT</a>
        </li>
    </ul>
</div>