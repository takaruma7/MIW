<?php
// PostgreSQL configuration for Render deployment
$config = [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '5432',
        'dbname' => $_ENV['DB_NAME'] ?? 'data_miw',
        'username' => $_ENV['DB_USER'] ?? 'miw_user',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8',
        'driver' => 'pgsql' // PostgreSQL driver
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
        'environment' => $_ENV['APP_ENV'] ?? 'development',
        'max_execution_time' => $_ENV['MAX_EXECUTION_TIME'] ?? 300,
        'secure_headers' => $_ENV['SECURE_HEADERS'] ?? 'true'
    ]
];

// Create PostgreSQL PDO connection
try {
    $dsn = "pgsql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']}";
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
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

// Security headers for production
if ($config['app']['secure_headers'] === 'true') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

return $config;
?>
