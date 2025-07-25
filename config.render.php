<?php
// PostgreSQL Configuration for Render.com
$host = getenv('DB_HOST') ?: (getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'))['host'] : 'localhost');
$dbname = getenv('DB_NAME') ?: (getenv('DATABASE_URL') ? ltrim(parse_url(getenv('DATABASE_URL'))['path'], '/') : 'data_miw');
$username = getenv('DB_USER') ?: (getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'))['user'] : 'postgres');
$password = getenv('DB_PASS') ?: (getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'))['pass'] : '');
$port = getenv('DB_PORT') ?: (getenv('DATABASE_URL') ? parse_url(getenv('DATABASE_URL'))['port'] : 5432);

// Handle Render's DATABASE_URL format
if (getenv('DATABASE_URL')) {
    $db_url = parse_url(getenv('DATABASE_URL'));
    $host = $db_url['host'];
    $port = $db_url['port'];
    $dbname = ltrim($db_url['path'], '/');
    $username = $db_url['user'];
    $password = $db_url['pass'];
}

try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// Email configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

// Security configurations for production
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// File upload settings
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
?>
