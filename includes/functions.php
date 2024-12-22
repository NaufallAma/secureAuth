<?php
require_once 'db.php';

class SecureAuth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Fungsi untuk membuat user baru (register)
    public function createUser($email, $password) {
        try {
            // Cek email sudah terdaftar
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email sudah terdaftar!'];
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $stmt = $this->db->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            $stmt->execute([$email, $password_hash]);

            return ['success' => true, 'message' => 'Registrasi berhasil!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }

    // Fungsi untuk verifikasi login
    public function verifyLogin($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                if ($user) {
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    
                    $stmt = $this->db->prepare(
                        "INSERT INTO login_history (user_id, ip_address, user_agent, status) 
                         VALUES (?, ?, ?, 'failed')"
                    );
                    $stmt->execute([$user['id'], $ip, $user_agent]);

                    $stmt = $this->db->prepare(
                        "SELECT COUNT(*) as count FROM login_history 
                         WHERE user_id = ? AND status = 'failed' 
                         AND login_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
                    );
                    $stmt->execute([$user['id']]);
                    $result = $stmt->fetch();

                    if ($result['count'] >= 1) {
                        $_SESSION['login_alert'] = "Terlalu banyak percobaan login gagal!";
                        if (!isset($_SESSION['suspicious_logins'])) {
                            $_SESSION['suspicious_logins'] = [];
                        }
                        $_SESSION['suspicious_logins'][] = [
                            'time' => date('Y-m-d H:i:s'),
                            'ip' => $ip,
                            'reasons' => ['Terlalu banyak percobaan login gagal']
                        ];
                    }
                }
                return ['success' => false, 'message' => 'Email atau password salah!'];
            }

            return ['success' => true, 'user' => $user];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Fungsi untuk log login berhasil
    public function logLogin($user_id) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $is_suspicious = false;
            $suspicious_reasons = [];
            
            $stmt = $this->db->prepare(
                "SELECT ip_address FROM login_history 
                 WHERE user_id = ? AND status = 'success'
                 ORDER BY login_time DESC LIMIT 1"
            );
            $stmt->execute([$user_id]);
            $last_login = $stmt->fetch();
            
            if ($last_login && $last_login['ip_address'] !== $ip) {
                $is_suspicious = true;
                $suspicious_reasons[] = "Login dari IP berbeda";
            }
            
            $hour = (int)date('H');
            if ($hour >= 0 && $hour <= 23) {
                $is_suspicious = true;
                $suspicious_reasons[] = "Login pada waktu mencurigakan";
            }
            
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM login_history 
                 WHERE user_id = ? AND status = 'failed'
                 AND login_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result['count'] >= 1) {
                $is_suspicious = true;
                $suspicious_reasons[] = "Terlalu banyak percobaan login gagal sebelumnya";
            }

            $stmt = $this->db->prepare(
                "INSERT INTO login_history (user_id, ip_address, user_agent, status) 
                 VALUES (?, ?, ?, ?)"
            );
            $status = $is_suspicious ? 'suspicious' : 'success';
            $stmt->execute([$user_id, $ip, $user_agent, $status]);

            if ($is_suspicious) {
                if (!isset($_SESSION['suspicious_logins'])) {
                    $_SESSION['suspicious_logins'] = [];
                }
                $_SESSION['suspicious_logins'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'ip' => $ip,
                    'reasons' => $suspicious_reasons
                ];
                $_SESSION['login_alert'] = "Login mencurigakan terdeteksi: " . implode(", ", $suspicious_reasons);
            }

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Setup MFA
    public function setupMFA($user_id) {
        try {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $stmt = $this->db->prepare(
                    "UPDATE users 
                     SET secret_key = ?, is_mfa_enabled = TRUE 
                     WHERE id = ?"
                );
                if ($stmt->execute([$otp, $user_id])) {
                    return [
                        'success' => true, 
                        'message' => 'Kode OTP Anda adalah: ' . $otp,
                        'otp' => $otp
                    ];
                }
            }
            return ['success' => false, 'message' => 'Gagal setup MFA'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Verifikasi OTP
    public function verifyOTP($user_id, $otp) {
        try {
            $stmt = $this->db->prepare("SELECT secret_key FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || !$user['secret_key']) {
                return false;
            }

            return $otp === $user['secret_key'];
        } catch (PDOException $e) {
            return false;
        }
    }

    // Fungsi untuk mengambil riwayat login
    public function getLoginHistory($user_id, $limit = null) {
        try {
            $sql = "SELECT * FROM login_history WHERE user_id = ? ORDER BY login_time DESC";
            if ($limit) {
                $sql .= " LIMIT ?";
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($limit) {
                $stmt->execute([$user_id, $limit]);
            } else {
                $stmt->execute([$user_id]);
            }
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Generate backup codes
    public function generateBackupCodes($user_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM backup_codes WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $codes = [];
            $stmt = $this->db->prepare(
                "INSERT INTO backup_codes (user_id, backup_code) 
                 VALUES (?, ?)"
            );

            for ($i = 0; $i < 8; $i++) {
                $code = strtoupper(bin2hex(random_bytes(5)));
                $codes[] = $code;
                $stmt->execute([$user_id, $code]);
            }

            return ['success' => true, 'codes' => $codes];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get user info
    public function getUserInfo($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // Get last successful login
    public function getLastSuccessfulLogin($user_id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM login_history 
                 WHERE user_id = ? AND status = 'success'
                 ORDER BY login_time DESC LIMIT 1"
            );
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    // Check backup codes
    public function hasBackupCodes($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM backup_codes WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update profile
    public function updateProfile($user_id, $data) {
        try {
            $allowedFields = ['full_name', 'phone', 'address', 'bio'];
            $updates = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'Tidak ada data yang diupdate'];
            }

            $params[] = $user_id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Profil berhasil diperbarui'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Update profile picture
    public function updateProfilePicture($user_id, $file) {
        try {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Format file tidak didukung'];
            }

            if ($file['size'] > $maxSize) {
                return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
            }

            $fileName = 'profile_' . $user_id . '_' . time() . '.jpg';
            $uploadPath = '../uploads/profiles/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt = $this->db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$fileName, $user_id]);
                return ['success' => true, 'message' => 'Foto profil berhasil diupdate'];
            }

            return ['success' => false, 'message' => 'Gagal mengupload file'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Helper function untuk generate secret key
    private function generateSecretKey() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    // Mendapatkan total login
    public function getTotalLogins($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM login_history WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Mendapatkan jumlah login berdasarkan status
    public function getLoginsByStatus($user_id, $status) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM login_history WHERE user_id = ? AND status = ?");
            $stmt->execute([$user_id, $status]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Mendapatkan statistik login untuk grafik
    public function getLoginStatistics($user_id, $days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(login_time) as login_date,
                    status,
                    COUNT(*) as count
                FROM login_history 
                WHERE user_id = ? 
                    AND login_time >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
                GROUP BY DATE(login_time), status
                ORDER BY login_date
            ");
            $stmt->execute([$user_id, $days]);
            $results = $stmt->fetchAll();

            // Siapkan array untuk data grafik
            $dates = [];
            $success = [];
            $failed = [];
            
            // Isi data untuk 30 hari terakhir
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dates[] = date('d/m', strtotime($date));
                $success[$date] = 0;
                $failed[$date] = 0;
            }

            // Masukkan data dari database
            foreach ($results as $row) {
                $date = $row['login_date'];
                if ($row['status'] == 'success') {
                    $success[$date] = (int)$row['count'];
                } else {
                    $failed[$date] = (int)$row['count'];
                }
            }

            return [
                'dates' => array_values($dates),
                'success' => array_values($success),
                'failed' => array_values($failed)
            ];
        } catch (PDOException $e) {
            return [
                'dates' => [],
                'success' => [],
                'failed' => []
            ];
        }
    }

    // Mendapatkan statistik device
    public function getDeviceStatistics($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                        ELSE 'Desktop'
                    END as device_type,
                    COUNT(*) as count
                FROM login_history 
                WHERE user_id = ?
                GROUP BY device_type
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Mendapatkan statistik browser
    public function getBrowserStatistics($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                        WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                        WHEN user_agent LIKE '%Safari%' THEN 'Safari'
                        ELSE 'Others'
                    END as browser,
                    COUNT(*) as count
                FROM login_history 
                WHERE user_id = ?
                GROUP BY browser
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Menghitung skor keamanan akun
    public function calculateSecurityScore($user_id) {
        $user = $this->getUserInfo($user_id);
        if (!$user) {
            return [
                'password' => 0,
                'mfa' => 0,
                'devices' => 0,
                'activity' => 0,
                'total' => 0
            ];
        }

        $scores = [
            'password' => $this->calculatePasswordScore($user),
            'mfa' => $this->calculateMFAScore($user),
            'devices' => $this->calculateDeviceScore($user_id),
            'activity' => $this->calculateActivityScore($user_id)
        ];
        
        // Hitung total score (rata-rata)
        $total = array_sum($scores);
        $scores['total'] = count($scores) > 0 ? $total / count($scores) : 0;
        
        return $scores;
    }

    // Hitung skor password
    private function calculatePasswordScore($user) {
        return 75; // Skor default untuk password yang sudah di-hash
    }

    // Hitung skor MFA
    private function calculateMFAScore($user) {
        if (!isset($user['is_mfa_enabled'])) {
            return 0;
        }
        return $user['is_mfa_enabled'] ? 100 : 0;
    }

    // Hitung skor device
    private function calculateDeviceScore($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_agent) as device_count 
                FROM login_history 
                WHERE user_id = ? AND status = 'success'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return 0;
            }
            
            $deviceCount = $result['device_count'];
            
            if ($deviceCount <= 2) return 100;
            if ($deviceCount <= 4) return 75;
            if ($deviceCount <= 6) return 50;
            return 25;
            
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Hitung skor aktivitas
    private function calculateActivityScore($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    COUNT(*) as total_count
                FROM login_history 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if (!$result || $result['total_count'] == 0) {
                return 100; // Jika belum ada aktivitas, berikan skor maksimal
            }
            
            $ratio = ($result['success_count'] / $result['total_count']) * 100;
            return min(100, $ratio);
            
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Mendapatkan rekomendasi keamanan
    public function getSecurityRecommendations($user_id) {
        $user = $this->getUserInfo($user_id);
        if (!$user) {
            return [];
        }

        $recommendations = [];
        
        // Cek MFA
        if (!isset($user['is_mfa_enabled']) || !$user['is_mfa_enabled']) {
            $recommendations[] = [
                'title' => 'Aktifkan Two-Factor Authentication',
                'description' => 'Tingkatkan keamanan akun Anda dengan mengaktifkan autentikasi dua faktor.',
                'priority' => 'high',
                'icon' => 'fa-shield-alt',
                'action_url' => 'security.php',
                'action_text' => 'Aktifkan Sekarang'
            ];
        }

        // Rekomendasi untuk memeriksa perangkat
        $deviceCount = $this->getDeviceCount($user_id);
        if ($deviceCount > 3) {
            $recommendations[] = [
                'title' => 'Periksa Perangkat yang Terhubung',
                'description' => 'Anda memiliki lebih dari 3 perangkat yang terhubung. Periksa dan hapus perangkat yang tidak dikenal.',
                'priority' => 'medium',
                'icon' => 'fa-mobile-alt',
                'action_url' => 'security.php',
                'action_text' => 'Periksa Sekarang'
            ];
        }

        // Jika tidak ada rekomendasi
        if (empty($recommendations)) {
            $recommendations[] = [
                'title' => 'Keamanan Akun Optimal',
                'description' => 'Anda telah menerapkan semua praktik keamanan yang direkomendasikan. Tetap jaga keamanan akun Anda!',
                'priority' => 'low',
                'icon' => 'fa-check-circle'
            ];
        }

        return $recommendations;
    }

    // Tambahkan method ini untuk menghitung jumlah perangkat
    private function getDeviceCount($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_agent) as device_count 
                FROM login_history 
                WHERE user_id = ? AND status = 'success'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result ? $result['device_count'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}