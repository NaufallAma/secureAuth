<?php
session_start();
require_once '../includes/functions.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter!";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } else {
        $auth = new SecureAuth();
        $result = $auth->createUser($email, $password);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <form class="auth-form" method="POST" action="">
            <h2>Register</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <p>Silakan <a href="login.php">login</a> untuk melanjutkan.</p>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">Register</button>
            
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </form>
    </div>
</body>
</html>
