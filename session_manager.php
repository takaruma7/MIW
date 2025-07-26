<?php
/**
 * Session Management for MIW Travel Management System
 * 
 * This file handles session initialization across all environments
 * and prevents session-related errors.
 * 
 * @version 1.0.0
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings for production security
    if (isProduction()) {
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }
    
    // Start the session
    session_start();
}

/**
 * Check if running in production environment
 */
function isProduction() {
    return isset($_ENV['DYNO']) || 
           isset($_ENV['RENDER']) || 
           isset($_ENV['RAILWAY_ENVIRONMENT']) ||
           (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
}

/**
 * Set session message
 */
function setSessionMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Get and clear session message
 */
function getSessionMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Set session error
 */
function setSessionError($error) {
    $_SESSION['error'] = $error;
}

/**
 * Get and clear session error
 */
function getSessionError() {
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
        return $error;
    }
    return null;
}
?>
