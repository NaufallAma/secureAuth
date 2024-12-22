<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$auth = new SecureAuth();
$user = $auth->getUserInfo($_SESSION['user_id']);
$lastLogin = $auth->getLastSuccessfulLogin($_SESSION['user_id']);
$loginHistory = $auth->getLoginHistory($_SESSION['user_id'], 5); // Ambil 5 riwayat terakhir
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="dashboard-nav">
            <h1>Dashboard</h1>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="profil.php">Profil</a>
                <a href="analytics.php">Analytics</a>
                <a href="security_score.php">Security Score</a>
                <a href="security.php">Pengaturan Keamanan</a>
                <a href="../auth/logout.php" class="btn btn-small">Logout</a>
            </div>
        </nav>

        <!-- Profile Card - Desain Baru -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="<?php echo $user['profile_picture'] ? '../uploads/profiles/' . htmlspecialchars($user['profile_picture']) : '../assets/images/default-profile.png'; ?>" 
                         alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['full_name'] ?? 'Pengguna'); ?></h2>
                    <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if ($user['phone']): ?>
                        <p class="phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($user['bio']): ?>
                <div class="profile-bio">
                    <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Fitur Lama - Security Icons -->
        <div class="security-overview">
            <div class="security-item">
                <i class="fas fa-shield-alt"></i>
                <span>MFA <?php echo $user['is_mfa_enabled'] ? 'Aktif' : 'Nonaktif'; ?></span>
            </div>
            <div class="security-item">
                <i class="fas fa-clock"></i>
                <span>Login Terakhir: <?php echo $lastLogin ? date('d M Y H:i', strtotime($lastLogin['login_time'])) : 'Tidak ada data'; ?></span>
            </div>
            <div class="security-item">
                <i class="fas fa-history"></i>
                <span>Total Login: <?php echo count($loginHistory); ?></span>
            </div>
        </div>

        <!-- Fitur Lama - Login History -->
        <div class="history-section">
            <h3><i class="fas fa-history"></i> Riwayat Login Terakhir</h3>
            <div class="history-table">
                <table>
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
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($login['login_time'])); ?></td>
                                <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($login['user_agent']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $login['status']; ?>">
                                        <?php echo ucfirst($login['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alert Section -->
        <?php if (isset($_SESSION['login_alert'])): ?>
            <div class="alert alert-warning">
                <?php 
                echo htmlspecialchars($_SESSION['login_alert']);
                unset($_SESSION['login_alert']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['suspicious_logins'])): ?>
            <div class="security-alerts">
                <h3><i class="fas fa-exclamation-triangle"></i> Peringatan Keamanan</h3>
                <?php foreach ($_SESSION['suspicious_logins'] as $login): ?>
                    <div class="alert alert-danger">
                        <strong>Login Mencurigakan Terdeteksi!</strong><br>
                        Waktu: <?php echo htmlspecialchars($login['time']); ?><br>
                        IP: <?php echo htmlspecialchars($login['ip']); ?><br>
                        Alasan: <?php echo htmlspecialchars(implode(", ", $login['reasons'])); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .profile-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .profile-image img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #FFC107;
    }

    .profile-info h2 {
        margin: 0;
        color: #212121;
    }

    .profile-info .email {
        color: #666;
        margin: 5px 0;
    }

    .profile-info .phone {
        color: #666;
        margin: 5px 0;
    }

    .profile-bio {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    /* Security Overview Style */
    .security-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .security-item {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .security-item i {
        font-size: 24px;
        color: #FFC107;
    }

    /* History Table Style */
    .history-section {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .history-section h3 {
        margin-top: 0;
        color: #212121;
    }

    .history-table {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    th {
        background-color: #f8f9fa;
        color: #212121;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
    }

    .status-badge.success {
        background-color: #d4edda;
        color: #155724;
    }

    .status-badge.failed {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-badge.suspicious {
        background-color: #fff3cd;
        color: #856404;
    }

    .alert {
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    .alert-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .security-overview {
            grid-template-columns: 1fr;
        }

        .history-table {
            font-size: 14px;
        }
    }
    </style>
</body>
</html>