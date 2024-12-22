<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$auth = new SecureAuth();
$user = $auth->getUserInfo($_SESSION['user_id']);

// Ambil data untuk analitik
$totalLogins = $auth->getTotalLogins($_SESSION['user_id']);
$successLogins = $auth->getLoginsByStatus($_SESSION['user_id'], 'success');
$failedLogins = $auth->getLoginsByStatus($_SESSION['user_id'], 'failed');
$suspiciousLogins = $auth->getLoginsByStatus($_SESSION['user_id'], 'suspicious');

// Ambil data untuk grafik (30 hari terakhir)
$loginStats = $auth->getLoginStatistics($_SESSION['user_id'], 30);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Analytics - SecureAuth</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <nav class="dashboard-nav">
            <h1>Login Analytics</h1>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="profil.php">Profil</a>
                <a href="analytics.php">Analytics</a>
                <a href="security_score.php">Security Score</a>
                <a href="security.php">Pengaturan Keamanan</a>
                <a href="../auth/logout.php" class="btn btn-small">Logout</a>
            </div>
        </nav>

        <!-- Overview Cards -->
        <div class="analytics-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Login</h3>
                    <p class="stat-number"><?php echo $totalLogins; ?></p>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>Login Berhasil</h3>
                    <p class="stat-number"><?php echo $successLogins; ?></p>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-details">
                    <h3>Login Gagal</h3>
                    <p class="stat-number"><?php echo $failedLogins; ?></p>
                </div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-details">
                    <h3>Login Mencurigakan</h3>
                    <p class="stat-number"><?php echo $suspiciousLogins; ?></p>
                </div>
            </div>
        </div>

        <!-- Login Chart -->
        <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Aktivitas Login (30 Hari Terakhir)</h3>
            <canvas id="loginChart"></canvas>
        </div>

        <!-- Device Usage -->
        <div class="analytics-grid">
            <div class="chart-card">
                <h3><i class="fas fa-laptop"></i> Device Usage</h3>
                <canvas id="deviceChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-globe"></i> Browser Usage</h3>
                <canvas id="browserChart"></canvas>
            </div>
        </div>
    </div>

    <script>
    // Data untuk grafik dari PHP
    const loginData = <?php echo json_encode($loginStats); ?>;
    
    // Line Chart - Login Activity
    new Chart(document.getElementById('loginChart'), {
        type: 'line',
        data: {
            labels: loginData.dates,
            datasets: [{
                label: 'Login Berhasil',
                data: loginData.success,
                borderColor: '#28a745',
                tension: 0.1
            }, {
                label: 'Login Gagal',
                data: loginData.failed,
                borderColor: '#dc3545',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Aktivitas Login'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Pie Chart - Device Usage
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Desktop', 'Mobile', 'Tablet'],
            datasets: [{
                data: [65, 30, 5],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Pie Chart - Browser Usage
    new Chart(document.getElementById('browserChart'), {
        type: 'doughnut',
        data: {
            labels: ['Chrome', 'Firefox', 'Safari', 'Others'],
            datasets: [{
                data: [50, 25, 15, 10],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>

    <style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .analytics-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .stat-card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-icon {
        font-size: 24px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-details h3 {
        margin: 0;
        font-size: 14px;
        color: #666;
    }

    .stat-number {
        margin: 5px 0 0;
        font-size: 24px;
        font-weight: bold;
        color: #212121;
    }

    .success .stat-icon {
        color: #28a745;
        background: #d4edda;
    }

    .warning .stat-icon {
        color: #ffc107;
        background: #fff3cd;
    }

    .danger .stat-icon {
        color: #dc3545;
        background: #f8d7da;
    }

    .chart-container {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .chart-card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    h3 {
        margin-top: 0;
        color: #212121;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    h3 i {
        color: #666;
    }

    @media (max-width: 768px) {
        .analytics-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>