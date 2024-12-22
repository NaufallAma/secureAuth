<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'secureauth');
define('DB_USER', 'root');  // Sesuaikan dengan username MySQL Anda
define('DB_PASS', '');      // Sesuaikan dengan password MySQL Anda



// Security configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);     // 15 minutes in seconds 