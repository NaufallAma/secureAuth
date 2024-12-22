<?php
session_start();
require_once '../includes/functions.php';

// Cek jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$auth = new SecureAuth();
$error = '';
$success = '';
$user = null;
$backupCodes = [];

try {
    $conn = Database::getInstance()->getConnection();
    
    // Ambil data user
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Jika request untuk generate ulang backup codes
        if (isset($_POST['generate_backup'])) {
            $result = $auth->generateBackupCodes($_SESSION['user_id']);
            if ($result['success']) {
                $backupCodes = $result['codes'];
                $success = "Kode backup baru berhasil di-generate!";
            } else {
                $error = "Gagal generate kode backup.";
            }
        }

        // Jika request untuk nonaktifkan MFA
        if (isset($_POST['disable_mfa'])) {
            $stmt = $conn->prepare("UPDATE users SET is_mfa_enabled = FALSE, secret_key = NULL WHERE id = ?");
            if ($stmt->execute([$_SESSION['user_id']])) {
                // Hapus backup codes saat MFA dinonaktifkan
                $stmt = $conn->prepare("DELETE FROM backup_codes WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $success = "MFA berhasil dinonaktifkan.";
                $user['is_mfa_enabled'] = false;
                $user['secret_key'] = null;
            } else {
                $error = "Gagal menonaktifkan MFA.";
            }
        }
    }

    // Ambil backup codes yang aktif jika MFA enabled
    if ($user['is_mfa_enabled']) {
        $stmt = $conn->prepare(
            "SELECT backup_code FROM backup_codes 
             WHERE user_id = ? AND is_used = FALSE 
             ORDER BY created_at DESC"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $activeCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Keamanan - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="dashboard-nav">
            <h1>Pengaturan Keamanan</h1>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="history.php">Riwayat Login</a>
                <a href="../auth/logout.php" class="btn btn-small">Logout</a>
            </div>
        </nav>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="security-settings">
            <!-- MFA Settings -->
            <div class="settings-card">
                <h2>Multi-Factor Authentication (MFA)</h2>
                <div class="mfa-status">
                    <p>Status: 
                        <?php if ($user['is_mfa_enabled']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Tidak Aktif</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($user['is_mfa_enabled']): ?>
                        <form method="POST" class="inline-form">
                            <button type="submit" name="disable_mfa" class="btn btn-danger" 
                                    onclick="return confirm('Yakin ingin menonaktifkan MFA? Ini akan mengurangi keamanan akun Anda.')">
                                Nonaktifkan MFA
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="../auth/setup-mfa.php" class="btn btn-primary">Aktifkan MFA</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Backup Codes -->
            <?php if ($user['is_mfa_enabled']): ?>
            <div class="settings-card">
                <h2>Kode Backup</h2>
                <p class="info-text">
                    Kode backup digunakan jika Anda kehilangan akses ke perangkat MFA Anda.
                    Simpan kode-kode ini di tempat yang aman.
                </p>
                
                <?php if (!empty($backupCodes)): ?>
                    <div class="backup-codes-container">
                        <h3>Kode Backup Baru:</h3>
                        <div class="backup-codes">
                            <?php foreach ($backupCodes as $code): ?>
                                <div class="backup-code"><?php echo htmlspecialchars($code); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <p class="warning-text">
                            Simpan kode-kode ini di tempat yang aman! 
                            Kode ini hanya akan ditampilkan sekali.
                        </p>
                    </div>
                <?php elseif (!empty($activeCodes)): ?>
                    <p>Anda memiliki <?php echo count($activeCodes); ?> kode backup yang aktif.</p>
                <?php else: ?>
                    <p class="warning-text">Anda tidak memiliki kode backup yang aktif.</p>
                <?php endif; ?>

                <form method="POST" class="backup-form">
                    <button type="submit" name="generate_backup" class="btn btn-secondary"
                            onclick="return confirm('Generate kode backup baru? Kode lama akan tidak berlaku.')">
                        Generate Kode Backup Baru
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Security Tips -->
            <div class="settings-card">
                <h2>Tips Keamanan</h2>
                <ul class="security-tips">
                    <li>Gunakan password yang kuat dan unik</li>
                    <li>Aktifkan MFA untuk keamanan tambahan</li>
                    <li>Jangan bagikan kode OTP dengan siapapun</li>
                    <li>Simpan kode backup di tempat yang aman</li>
                    <li>Periksa riwayat login secara berkala</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>