<?php
session_start();
require_once '../includes/functions.php';

// Cek jika tidak ada temp_user_id
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    
    if (empty($otp)) {
        $error = "Kode OTP harus diisi!";
    } else {
        $auth = new SecureAuth();
        
        if ($auth->verifyOTP($_SESSION['temp_user_id'], $otp)) {
            // Ambil data user
            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['temp_user_id']]);
            $user = $stmt->fetch();
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            unset($_SESSION['temp_user_id']);
            
            // Log login
            $auth->logLogin($user['id']);
            
            header("Location: ../dashboard/");
            exit();
        } else {
            $error = "Kode OTP tidak valid!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <form class="auth-form" method="POST" action="">
            <h2>Verifikasi OTP</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="otp">Masukkan Kode OTP:</label>
                <input type="text" id="otp" name="otp" maxlength="6" required>
            </div>

            <button type="submit" class="btn">Verifikasi</button>
        </form>
    </div>
</body>
</html> 