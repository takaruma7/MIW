<?php
/**
 * MIW Travel Management System - Minimized Configuration
 * 
 * This file automatically detects the deployment environment and loads
 * the appropriate configuration. Supports:
 * - Heroku (PostgreSQL via DATABASE_URL) - Production
 * - Local development (MySQL)
 * - Docker (MySQL)
 * - GitHub (for CI/CD workflows)
 * - SMTP (Postfix compatible)
 * 
 * @version 2.1.0
 * @author MIW Development Team
 */

// Set time limits for all operations
set_time_limit(20);
ini_set('max_execution_time', 20);

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

// 1. HEROKU ENVIRONMENT (Production)
if (isset($_ENV['DATABASE_URL']) || getenv('DATABASE_URL') || isset($_ENV['DYNO'])) {
    loadHerokuConfig();
}
// 2. LOCAL DEVELOPMENT / DOCKER
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
    
    // Email configuration for Heroku (SMTP - Postfix compatible)
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?? 'smtp.gmail.com');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?? 587);
    define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?? '');
    define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?? '');
    define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?? 'tls');
    
    // Email functionality settings
    define('EMAIL_ENABLED', true);
    define('EMAIL_FROM', SMTP_USERNAME);
    define('EMAIL_FROM_NAME', 'MIW Travel');
    define('ADMIN_EMAIL', SMTP_USERNAME);
    
    define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? getenv('MAX_FILE_SIZE') ?? '10M');
    define('MAX_EXECUTION_TIME', $_ENV['MAX_EXECUTION_TIME'] ?? getenv('MAX_EXECUTION_TIME') ?? 300);
    
    ini_set('max_execution_time', MAX_EXECUTION_TIME);
    
    createPostgreSQLConnection();
}

/**
 * Local Development Configuration (MySQL)
 * Also works for Docker environment
 */
function loadLocalConfig() {
    global $db_config, $pdo, $conn;
    
    // Check if running in Docker environment
    $isDocker = file_exists('/.dockerenv') || isset($_ENV['DOCKER_ENV']);
    
    $db_config = [
        'type' => 'mysql',
        'host' => $isDocker ? 'miw-db' : 'localhost', // Docker service name or localhost
        'port' => $isDocker ? 3306 : 3306,
        'database' => 'data_miw',
        'username' => 'root',
        'password' => $isDocker ? 'root_password' : '', // Docker has password, local doesn't
        'environment' => $isDocker ? 'docker' : 'local'
    ];
    
    // Email configuration for local development
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', '');
    define('SMTP_PASSWORD', '');
    define('SMTP_ENCRYPTION', 'tls');
    
    // Email functionality settings
    define('EMAIL_ENABLED', false); // Disabled for local/docker development
    define('EMAIL_FROM', 'noreply@miw-travel.local');
    define('EMAIL_FROM_NAME', 'MIW Travel (Development)');
    define('ADMIN_EMAIL', 'admin@miw-travel.local');
    
    define('MAX_FILE_SIZE', '10M');
    define('MAX_EXECUTION_TIME', 300);
    
    // Try to create MySQL connection, but don't fail if it doesn't work
    try {
        createMySQLConnection();
    } catch (Exception $e) {
        error_log("MySQL connection failed in " . $db_config['environment'] . " environment: " . $e->getMessage());
        // Continue without database connection for now
    }
}

/**
 * Create PostgreSQL connection (for Heroku)
 */
function createPostgreSQLConnection() {
    global $db_config, $pdo, $conn;
    
    try {
        $dsn = "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};sslmode=require";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ];
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        $conn = $pdo; // Alias for compatibility
        
        // Set timezone
        $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
        
    } catch (PDOException $e) {
        error_log("PostgreSQL Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Create MySQL connection (for local development and Docker)
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
    return getCurrentEnvironment() === 'heroku';
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
        // Use temp directory for Heroku (ephemeral storage)
        return sys_get_temp_dir() . '/uploads';
    } else {
        // Use local uploads directory for development/docker
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
    return '2.1.0';
}

/**
 * Legacy compatibility constants (for older code)
 */
if (!defined('DB_HOST') && isset($db_config['host'])) {
    define('DB_HOST', $db_config['host']);
    define('DB_PORT', $db_config['port']);
    define('DB_NAME', $db_config['database']);
    define('DB_USER', $db_config['username']);
    define('DB_PASS', $db_config['password']);
    define('DB_TYPE', $db_config['type']);
}

// Environment indicator for debugging (non-production only)
if (!isProduction()) {
    error_log("MIW Config: Environment = " . getCurrentEnvironment() . ", Database = " . getDatabaseType());
}
?>
