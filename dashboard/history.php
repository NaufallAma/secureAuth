<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$auth = new SecureAuth();
$error = '';
$loginHistory = $auth->getLoginHistory($_SESSION['user_id']);

// Fungsi untuk mendapatkan informasi browser
function getBrowserInfo($userAgent) {
    if (strpos($userAgent, 'Firefox') !== false) {
        return 'Firefox';
    } elseif (strpos($userAgent, 'Chrome') !== false) {
        return 'Chrome';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        return 'Safari';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        return 'Edge';
    } else {
        return 'Browser lain';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Login - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="dashboard-nav">
            <h1>Riwayat Login</h1>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="security.php">Pengaturan Keamanan</a>
                <a href="../auth/logout.php" class="btn btn-small">Logout</a>
            </div>
        </nav>

        <?php if (isset($_SESSION['login_alert'])): ?>
            <div class="alert alert-warning">
                <?php 
                echo htmlspecialchars($_SESSION['login_alert']);
                unset($_SESSION['login_alert']); 
                ?>
            </div>
        <?php endif; ?>

        <div class="history-container">
            <?php if (empty($loginHistory)): ?>
                <p>Belum ada riwayat login.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>IP Address</th>
                            <th>Browser</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loginHistory as $login): ?>
                            <tr class="<?php echo ($login['status'] === 'failed' || $login['status'] === 'suspicious') ? 'suspicious' : ''; ?>">
                                <td><?php echo date('d/m/Y H:i:s', strtotime($login['login_time'])); ?></td>
                                <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars(getBrowserInfo($login['user_agent'])); ?></td>
                                <td>
                                    <?php if ($login['status'] === 'failed'): ?>
                                        <span class="badge badge-warning">Login Gagal</span>
                                    <?php elseif ($login['status'] === 'suspicious'): ?>
                                        <span class="badge badge-warning">Mencurigakan</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .alert-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    tr.suspicious {
        background-color: #fff3cd;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        cursor: help;
    }

    .badge-warning {
        background-color: #ffc107;
        color: #000;
    }

    .badge-success {
        background-color: #28a745;
        color: #fff;
    }

    .history-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    </style>
</body>
</html>