<?php
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die("Error: Missing config file at " . $configPath);
}
$config = require $configPath;

// Detect environment
$isDevEnvironment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false)
    || ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1';

// Session setup
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session']['name']);
    session_set_cookie_params([
        'lifetime' => $config['session']['lifetime'],
        'path' => $config['session']['path'],
        'domain' => $isDevEnvironment ? '' : $config['session']['domain'],
        'secure' => $isDevEnvironment ? false : true,
        'httponly' => $config['session']['httponly'],
        'samesite' => $isDevEnvironment ? 'Lax' : $config['session']['samesite']
    ]);
    session_start();
}

// Security
if (!isset($_SESSION['created']) || (time() - $_SESSION['created'] > 1800)) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Update auth check clearly:
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_page !== 'login.php') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Define $_SESSION['user'] consistently:
if (isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
    ];
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
