<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auth = new SecureAuth();
$error = '';
$success = '';
$otp_display = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    
    if (empty($otp)) {
        $error = "Kode OTP harus diisi!";
    } else {
        if ($auth->verifyOTP($_SESSION['user_id'], $otp)) {
            $success = "MFA berhasil diaktifkan!";
        } else {
            $error = "Kode OTP tidak valid!";
        }
    }
} else {
    // Generate dan tampilkan OTP
    $result = $auth->setupMFA($_SESSION['user_id']);
    if ($result['success']) {
        $otp_display = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup MFA - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Setup MFA</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p><a href="../dashboard/" class="btn">Kembali ke Dashboard</a></p>
                </div>
            <?php else: ?>
                <div class="mfa-setup-instructions">
                    <?php if ($otp_display): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($otp_display); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="otp">Masukkan kode OTP:</label>
                            <input type="text" id="otp" name="otp" maxlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn">Aktifkan MFA</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>