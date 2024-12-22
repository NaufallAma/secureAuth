<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$auth = new SecureAuth();
$user = $auth->getUserInfo($_SESSION['user_id']);
$securityScore = $auth->calculateSecurityScore($_SESSION['user_id']);
$recommendations = $auth->getSecurityRecommendations($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Score - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="dashboard-nav">
            <h1>Security Score</h1>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="profil.php">Profil</a>
                <a href="analytics.php">Analytics</a>
                <a href="security_score.php">Security Score</a>
                <a href="security.php">Pengaturan Keamanan</a>
                <a href="../auth/logout.php" class="btn btn-small">Logout</a>
            </div>
        </nav>

        <!-- Score Overview -->
        <div class="score-overview">
            <div class="score-circle">
                <svg viewBox="0 0 36 36" class="circular-chart">
                    <path d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="#eee"
                        stroke-width="2.5"
                    />
                    <path d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="<?php echo getScoreColor($securityScore['total']); ?>"
                        stroke-width="2.5"
                        stroke-dasharray="<?php echo $securityScore['total']; ?>, 100"
                    />
                    <text x="18" y="20.35" class="score-text">
                        <?php echo number_format($securityScore['total']); ?>%
                    </text>
                </svg>
            </div>
            <div class="score-details">
                <h2>Skor Keamanan Akun Anda</h2>
                <p><?php echo getScoreMessage($securityScore['total']); ?></p>
            </div>
        </div>

        <!-- Score Breakdown -->
        <div class="score-breakdown">
            <h3><i class="fas fa-chart-bar"></i> Breakdown Skor Keamanan</h3>
            <div class="breakdown-items">
                <div class="breakdown-item">
                    <div class="item-header">
                        <span class="item-title">
                            <i class="fas fa-key"></i> Password Strength
                        </span>
                        <span class="item-score <?php echo getScoreClass($securityScore['password']); ?>">
                            <?php echo $securityScore['password']; ?>%
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $securityScore['password']; ?>%"></div>
                    </div>
                </div>

                <div class="breakdown-item">
                    <div class="item-header">
                        <span class="item-title">
                            <i class="fas fa-shield-alt"></i> Two-Factor Authentication
                        </span>
                        <span class="item-score <?php echo getScoreClass($securityScore['mfa']); ?>">
                            <?php echo $securityScore['mfa']; ?>%
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $securityScore['mfa']; ?>%"></div>
                    </div>
                </div>

                <div class="breakdown-item">
                    <div class="item-header">
                        <span class="item-title">
                            <i class="fas fa-mobile-alt"></i> Device Security
                        </span>
                        <span class="item-score <?php echo getScoreClass($securityScore['devices']); ?>">
                            <?php echo $securityScore['devices']; ?>%
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $securityScore['devices']; ?>%"></div>
                    </div>
                </div>

                <div class="breakdown-item">
                    <div class="item-header">
                        <span class="item-title">
                            <i class="fas fa-user-shield"></i> Account Activity
                        </span>
                        <span class="item-score <?php echo getScoreClass($securityScore['activity']); ?>">
                            <?php echo $securityScore['activity']; ?>%
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $securityScore['activity']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="recommendations">
            <h3><i class="fas fa-lightbulb"></i> Rekomendasi Peningkatan Keamanan</h3>
            <div class="recommendation-items">
                <?php foreach ($recommendations as $rec): ?>
                    <div class="recommendation-item <?php echo $rec['priority']; ?>">
                        <div class="rec-icon">
                            <i class="fas <?php echo $rec['icon']; ?>"></i>
                        </div>
                        <div class="rec-content">
                            <h4><?php echo htmlspecialchars($rec['title']); ?></h4>
                            <p><?php echo htmlspecialchars($rec['description']); ?></p>
                            <?php if (isset($rec['action_url'])): ?>
                                <a href="<?php echo htmlspecialchars($rec['action_url']); ?>" class="btn btn-small">
                                    <?php echo htmlspecialchars($rec['action_text']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="rec-priority">
                            <?php echo ucfirst($rec['priority']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .score-overview {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .score-circle {
        width: 150px;
        flex-shrink: 0;
    }

    .circular-chart {
        width: 150px;
        height: 150px;
    }

    .score-text {
        font-family: Arial, sans-serif;
        font-size: 8px;
        text-anchor: middle;
        font-weight: bold;
    }

    .score-details h2 {
        margin: 0 0 10px 0;
        color: #212121;
    }

    .score-details p {
        margin: 0;
        color: #666;
    }

    .score-breakdown {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .breakdown-items {
        display: grid;
        gap: 20px;
        margin-top: 20px;
    }

    .breakdown-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .item-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #212121;
    }

    .item-score {
        font-weight: bold;
    }

    .item-score.good { color: #28a745; }
    .item-score.warning { color: #ffc107; }
    .item-score.danger { color: #dc3545; }

    .progress-bar {
        background: #e9ecef;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress {
        height: 100%;
        background: #4e73df;
        transition: width 0.3s ease;
    }

    .recommendations {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .recommendation-items {
        display: grid;
        gap: 15px;
        margin-top: 20px;
    }

    .recommendation-item {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 20px;
        padding: 20px;
        border-radius: 8px;
        background: #f8f9fa;
        align-items: center;
    }

    .recommendation-item.high {
        border-left: 4px solid #dc3545;
    }

    .recommendation-item.medium {
        border-left: 4px solid #ffc107;
    }

    .recommendation-item.low {
        border-left: 4px solid #28a745;
    }

    .rec-icon {
        width: 40px;
        height: 40px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #4e73df;
    }

    .rec-content h4 {
        margin: 0 0 5px 0;
        color: #212121;
    }

    .rec-content p {
        margin: 0 0 10px 0;
        color: #666;
    }

    .rec-priority {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
    }

    .high .rec-priority {
        background: #dc3545;
        color: #fff;
    }

    .medium .rec-priority {
        background: #ffc107;
        color: #212121;
    }

    .low .rec-priority {
        background: #28a745;
        color: #fff;
    }

    @media (max-width: 768px) {
        .score-overview {
            flex-direction: column;
            text-align: center;
        }

        .recommendation-item {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .rec-icon {
            margin: 0 auto;
        }
    }
    </style>
</body>
</html>

<?php
function getScoreColor($score) {
    if ($score >= 80) return '#28a745';
    if ($score >= 60) return '#ffc107';
    return '#dc3545';
}

function getScoreMessage($score) {
    if ($score >= 80) return 'Keamanan akun Anda sangat baik!';
    if ($score >= 60) return 'Keamanan akun Anda cukup baik, namun masih bisa ditingkatkan.';
    return 'Keamanan akun Anda perlu ditingkatkan segera.';
}

function getScoreClass($score) {
    if ($score >= 80) return 'good';
    if ($score >= 60) return 'warning';
    return 'danger';
}
?>