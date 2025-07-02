<?php
if (!defined('BASE_URL')) define('BASE_URL', '/inventory-system/');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', __DIR__ . '/../views/');
$config = [
    'session' => [
        'name' => 'InventorySystem',
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'httponly' => true,
        'samesite' => 'Strict'
    ],
    'db' => [
        'host' => 'localhost',                // SiteGround uses 'localhost'
        'user' => 'uayrk0dfe4apo',            // your database user
        'pass' => '1p6$w@c15$$e',       // the password you set
        'name' => 'dbg243cigjjfsj'            // your database name
    ]
];
if (!defined('DB_HOST')) define('DB_HOST', $config['db']['host']);
if (!defined('DB_USER')) define('DB_USER', $config['db']['user']);
if (!defined('DB_PASS')) define('DB_PASS', $config['db']['pass']);
if (!defined('DB_NAME')) define('DB_NAME', $config['db']['name']);
// REMOVE ALL SESSION HANDLING FROM HERE!
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
require_once __DIR__ . '/helpers.php';
return $config;