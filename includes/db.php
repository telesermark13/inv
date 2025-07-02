<?php
require_once __DIR__ . '/config.php';
try {
    // Use either the config array OR the direct variables, not both
    $conn = new mysqli(
        $config['db']['host'], 
        $config['db']['user'], 
        $config['db']['pass'], 
        $config['db']['name']
    );
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection error. Please try again later.");
}