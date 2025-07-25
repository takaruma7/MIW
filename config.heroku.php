<?php
// Heroku configuration for MIW deployment
$config = [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? parse_url($_ENV['DATABASE_URL'] ?? '', PHP_URL_HOST),
        'port' => $_ENV['DB_PORT'] ?? parse_url($_ENV['DATABASE_URL'] ?? '', PHP_URL_PORT),
        'dbname' => $_ENV['DB_NAME'] ?? ltrim(parse_url($_ENV['DATABASE_URL'] ?? '', PHP_URL_PATH), '/'),
        'username' => $_ENV['DB_USER'] ?? parse_url($_ENV['DATABASE_URL'] ?? '', PHP_URL_USER),
        'password' => $_ENV['DB_PASS'] ?? parse_url($_ENV['DATABASE_URL'] ?? '', PHP_URL_PASS),
        'charset' => 'utf8',
        'driver' => 'pgsql' // Heroku uses PostgreSQL
    ],
    'email' => [
        'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
        'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
        'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
    ],
    'upload' => [
        'max_file_size' => $_ENV['MAX_FILE_SIZE'] ?? '10M',
        'upload_path' => '/tmp/uploads/',
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']
    ],
    'app' => [
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'max_execution_time' => $_ENV['MAX_EXECUTION_TIME'] ?? 300,
        'secure_headers' => $_ENV['SECURE_HEADERS'] ?? 'true',
        'port' => $_ENV['PORT'] ?? 8080 // Heroku assigns PORT
    ]
];

// Create PostgreSQL PDO connection
try {
    // Heroku provides DATABASE_URL, parse it
    if (isset($_ENV['DATABASE_URL'])) {
        $dbUrl = parse_url($_ENV['DATABASE_URL']);
        $dsn = "pgsql:host={$dbUrl['host']};port={$dbUrl['port']};dbname=" . ltrim($dbUrl['path'], '/');
        $username = $dbUrl['user'];
        $password = $dbUrl['pass'];
    } else {
        // Fallback to individual environment variables
        $dsn = "pgsql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']}";
        $username = $config['database']['username'];
        $password = $config['database']['password'];
    }
    
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Set timezone
    $pdo->exec("SET timezone = 'UTC'");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if ($config['app']['environment'] === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please check configuration.");
    }
}

// Set PHP configurations
ini_set('max_execution_time', $config['app']['max_execution_time']);
ini_set('upload_max_filesize', $config['upload']['max_file_size']);
ini_set('post_max_size', $config['upload']['max_file_size']);

// Set compatibility variables for legacy code
$conn = $pdo; // Ensure $conn is available for older code

// Define constants for compatibility
define('SMTP_HOST', $config['email']['smtp_host']);
define('SMTP_USERNAME', $config['email']['smtp_username']);
define('SMTP_PASSWORD', $config['email']['smtp_password']);
define('SMTP_PORT', $config['email']['smtp_port']);
define('SMTP_ENCRYPTION', $config['email']['smtp_encryption']);
define('SMTP_SECURE', $config['email']['smtp_encryption']);

// Other configurations
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable for production
date_default_timezone_set('Asia/Jakarta');

// Email settings
define('EMAIL_FROM', $config['email']['smtp_username']);
define('EMAIL_FROM_NAME', 'MIW Travel');
define('EMAIL_SUBJECT', 'Pendaftaran Umroh/Haji Anda');
define('ADMIN_EMAIL', $config['email']['smtp_username']);
define('EMAIL_ENABLED', true);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers for production
if ($config['app']['secure_headers'] === 'true') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

?>
