<?php
session_start();
require_once '../includes/functions.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } else {
        $auth = new SecureAuth();
        $result = $auth->verifyLogin($email, $password);
        
        if ($result['success']) {
            $user = $result['user'];
            
            if ($user['is_mfa_enabled']) {
                $_SESSION['temp_user_id'] = $user['id'];
                header("Location: verify-otp.php");
                exit();
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                
                $auth->logLogin($user['id']);
                header("Location: ../dashboard/");
                exit();
            }
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
    <title>Login - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <form class="auth-form" method="POST" action="">
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
            
            <p>Belum punya akun? <a href="register.php">Register di sini</a></p>
        </form>
    </div>
</body>
</html>
