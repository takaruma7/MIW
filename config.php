<?php
/**
 * MIW Travel Management System - Unified Configuration
 * 
 * This file automatically detects the deployment environment and loads
 * the appropriate configuration. Supports:
 * - Heroku (PostgreSQL via DATABASE_URL)
 * - Render (PostgreSQL via environment detection)
 * - Railway (PostgreSQL via RAILWAY environment)
 * - Local development (MySQL)
 * 
 * @version 2.0.0
 * @author MIW Development Team
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors in production
ini_set('log_errors', 1);

// Initialize configuration variables
$db_config = [];
$pdo = null;
$conn = null;

/**
 * Environment Detection and Configuration Loading
 */

// 1. HEROKU ENVIRONMENT
if (isset($_ENV['DATABASE_URL']) || getenv('DATABASE_URL') || isset($_ENV['DYNO'])) {
    loadHerokuConfig();
}
// 2. RENDER ENVIRONMENT  
elseif (isset($_ENV['RENDER']) || isset($_ENV['RENDER_SERVICE_ID']) || 
        (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production' && 
         strpos(gethostname(), 'render') !== false)) {
    loadRenderConfig();
}
// 3. RAILWAY ENVIRONMENT
elseif (isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_ENV['RAILWAY_PROJECT_ID']) ||
        getenv('RAILWAY_ENVIRONMENT')) {
    loadRailwayConfig();
}
// 4. LOCAL DEVELOPMENT
else {
    loadLocalConfig();
}

/**
 * Heroku Configuration (PostgreSQL)
 */
function loadHerokuConfig() {
    global $db_config, $pdo, $conn;
    
    $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    
    if (!$database_url) {
        throw new Exception('DATABASE_URL not found in Heroku environment');
    }
    
    $url = parse_url($database_url);
    
    $db_config = [
        'type' => 'postgresql',
        'host' => $url['host'],
        'port' => $url['port'] ?? 5432,
        'database' => ltrim($url['path'], '/'),
        'username' => $url['user'],
        'password' => $url['pass'],
        'environment' => 'heroku'
    ];
    
    // Email configuration
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587);
    define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?? '');
    define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?? '');
    define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?? 'tls');
    
    // Performance settings
    define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? getenv('MAX_FILE_SIZE') ?? '10M');
    define('MAX_EXECUTION_TIME', $_ENV['MAX_EXECUTION_TIME'] ?? getenv('MAX_EXECUTION_TIME') ?? 300);
    
    ini_set('max_execution_time', MAX_EXECUTION_TIME);
    
    createPostgreSQLConnection();
}

/**
 * Render Configuration (PostgreSQL)
 */
function loadRenderConfig() {
    global $db_config, $pdo, $conn;
    
    $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    
    if (!$database_url) {
        // Alternative Render environment variables
        $db_config = [
            'type' => 'postgresql',
            'host' => $_ENV['PGHOST'] ?? getenv('PGHOST') ?? 'localhost',
            'port' => $_ENV['PGPORT'] ?? getenv('PGPORT') ?? 5432,
            'database' => $_ENV['PGDATABASE'] ?? getenv('PGDATABASE') ?? 'miw_db',
            'username' => $_ENV['PGUSER'] ?? getenv('PGUSER') ?? 'postgres',
            'password' => $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD') ?? '',
            'environment' => 'render'
        ];
    } else {
        $url = parse_url($database_url);
        $db_config = [
            'type' => 'postgresql',
            'host' => $url['host'],
            'port' => $url['port'] ?? 5432,
            'database' => ltrim($url['path'], '/'),
            'username' => $url['user'],
            'password' => $url['pass'],
            'environment' => 'render'
        ];
    }
    
    // Email configuration for Render
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
    define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
    define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
    define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
    
    define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? '10M');
    define('MAX_EXECUTION_TIME', $_ENV['MAX_EXECUTION_TIME'] ?? 300);
    
    createPostgreSQLConnection();
}

/**
 * Railway Configuration (PostgreSQL)
 */
function loadRailwayConfig() {
    global $db_config, $pdo, $conn;
    
    $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    
    if (!$database_url) {
        // Railway-specific environment variables
        $db_config = [
            'type' => 'postgresql',
            'host' => $_ENV['PGHOST'] ?? getenv('PGHOST') ?? 'localhost',
            'port' => $_ENV['PGPORT'] ?? getenv('PGPORT') ?? 5432,
            'database' => $_ENV['PGDATABASE'] ?? getenv('PGDATABASE') ?? 'railway',
            'username' => $_ENV['PGUSER'] ?? getenv('PGUSER') ?? 'postgres',
            'password' => $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD') ?? '',
            'environment' => 'railway'
        ];
    } else {
        $url = parse_url($database_url);
        $db_config = [
            'type' => 'postgresql',
            'host' => $url['host'],
            'port' => $url['port'] ?? 5432,
            'database' => ltrim($url['path'], '/'),
            'username' => $url['user'],
            'password' => $url['pass'],
            'environment' => 'railway'
        ];
    }
    
    // Email configuration for Railway
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
    define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
    define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
    define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
    
    define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? '10M');
    define('MAX_EXECUTION_TIME', $_ENV['MAX_EXECUTION_TIME'] ?? 300);
    
    createPostgreSQLConnection();
}

/**
 * Local Development Configuration (MySQL)
 */
function loadLocalConfig() {
    global $db_config, $pdo, $conn;
    
    $db_config = [
        'type' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'data_miw',
        'username' => 'root',
        'password' => '',
        'environment' => 'local'
    ];
    
    // Local email configuration (for testing)
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', '');
    define('SMTP_PASSWORD', '');
    define('SMTP_ENCRYPTION', 'tls');
    
    define('MAX_FILE_SIZE', '50M');
    define('MAX_EXECUTION_TIME', 0); // No limit for local
    
    createMySQLConnection();
}

/**
 * Create PostgreSQL Connection
 */
function createPostgreSQLConnection() {
    global $db_config, $pdo, $conn;
    
    try {
        $dsn = "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 30,
        ];
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        $conn = $pdo; // Alias for compatibility
        
        // Set timezone
        $pdo->exec("SET timezone = 'Asia/Jakarta'");
        
    } catch (PDOException $e) {
        error_log("PostgreSQL Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Create MySQL Connection
 */
function createMySQLConnection() {
    global $db_config, $pdo, $conn;
    
    try {
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        $conn = $pdo; // Alias for compatibility
        
        // Set timezone
        $pdo->exec("SET time_zone = '+07:00'");
        
    } catch (PDOException $e) {
        error_log("MySQL Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Utility Functions
 */

/**
 * Get current environment name
 */
function getCurrentEnvironment() {
    global $db_config;
    return $db_config['environment'] ?? 'unknown';
}

/**
 * Check if running in production
 */
function isProduction() {
    return in_array(getCurrentEnvironment(), ['heroku', 'render', 'railway']);
}

/**
 * Get database type
 */
function getDatabaseType() {
    global $db_config;
    return $db_config['type'] ?? 'unknown';
}

/**
 * Get upload directory based on environment
 */
function getUploadDirectory() {
    if (isProduction()) {
        // Use temp directory for production (ephemeral storage)
        return sys_get_temp_dir() . '/uploads';
    } else {
        // Use local uploads directory for development
        return __DIR__ . '/uploads';
    }
}

/**
 * Ensure upload directory exists
 */
function ensureUploadDirectory() {
    $upload_dir = getUploadDirectory();
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    return $upload_dir;
}

/**
 * Get application version
 */
function getAppVersion() {
    return '2.0.0';
}

/**
 * Legacy compatibility constants
 */
if (!defined('DB_HOST')) {
    define('DB_HOST', $db_config['host'] ?? 'localhost');
    define('DB_PORT', $db_config['port'] ?? 3306);
    define('DB_NAME', $db_config['database'] ?? 'data_miw');
    define('DB_USER', $db_config['username'] ?? 'root');
    define('DB_PASS', $db_config['password'] ?? '');
}

// Environment indicator for debugging
if (!isProduction()) {
    error_log("MIW Config: Environment = " . getCurrentEnvironment() . ", Database = " . getDatabaseType());
}
?>
