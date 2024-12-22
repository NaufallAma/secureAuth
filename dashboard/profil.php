<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$auth = new SecureAuth();
$error = '';
$success = '';
$user = $auth->getUserInfo($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = $auth->updateProfile($_SESSION['user_id'], [
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'bio' => $_POST['bio']
        ]);
        
        if ($result['success']) {
            $success = $result['message'];
            $user = $auth->getUserInfo($_SESSION['user_id']); // Refresh user data
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_FILES['profile_picture'])) {
        $result = $auth->updateProfilePicture($_SESSION['user_id'], $_FILES['profile_picture']);
        if ($result['success']) {
            $success = $result['message'];
            $user = $auth->getUserInfo($_SESSION['user_id']); // Refresh user data
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
    <title>Profil Pengguna - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="dashboard-nav">
            <h1>Profil Pengguna</h1>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="profile.php">Profil</a>
                <a href="security_score.php">Security Score</a>
                <a href="security.php">Pengaturan Keamanan</a>
                <a href="../auth/logout.php" class="btn btn-small">Logout</a>
            </div>
        </nav>

        <div class="profile-container">
            <div class="profile-section">
                <h2>Informasi Pribadi</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="full_name">Nama Lengkap:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Telepon:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Alamat:</label>
                        <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio:</label>
                        <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn">Simpan Perubahan</button>
                </form>
            </div>

            <div class="profile-section">
                <h2>Foto Profil</h2>
                <div class="profile-picture">
                    <img src="<?php echo $user['profile_picture'] ? '../uploads/profiles/' . htmlspecialchars($user['profile_picture']) : '../assets/images/default-profile.png'; ?>" 
                         alt="Profile Picture">
                    <form method="POST" enctype="multipart/form-data" class="picture-form">
                        <input type="file" name="profile_picture" accept="image/*" required>
                        <button type="submit" class="btn">Upload Foto</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    .profile-container {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 20px;
        margin-top: 20px;
    }

    .profile-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .profile-picture {
        text-align: center;
        margin-bottom: 20px;
    }

    .profile-picture img {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
    }

    .picture-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .profile-form,
    .preferences-form {
        display: grid;
        gap: 15px;
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>