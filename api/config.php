<?php
// api/config.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include database connection
require_once '../components/connect.php';

// API Response Helper
class APIResponse {
    public static function send($data, $status = 200, $message = '') {
        http_response_code($status);
        echo json_encode([
            'status' => $status >= 200 && $status < 300 ? 'success' : 'error',
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
        exit;
    }
    
    public static function error($message, $status = 400) {
        self::send(null, $status, $message);
    }
    
    public static function success($data, $message = '') {
        self::send($data, 200, $message);
    }
}

// Authentication Middleware
class APIAuth {
    public static function authenticate() {
        $headers = getallheaders();
        $api_key = $headers['X-API-Key'] ?? $_GET['api_key'] ?? null;
        
        if (!$api_key) {
            APIResponse::error('API key required', 401);
        }
        
        // Validate API key (you can store these in database)
        $valid_keys = [
            'unikart_web_key_2024' => ['type' => 'web', 'permissions' => ['read', 'write']],
            'unikart_mobile_key_2024' => ['type' => 'mobile', 'permissions' => ['read', 'write']],
            'unikart_public_key_2024' => ['type' => 'public', 'permissions' => ['read']]
        ];
        
        if (!isset($valid_keys[$api_key])) {
            APIResponse::error('Invalid API key', 401);
        }
        
        return $valid_keys[$api_key];
    }
    
    public static function requirePermission($permission) {
        $auth = self::authenticate();
        if (!in_array($permission, $auth['permissions'])) {
            APIResponse::error('Insufficient permissions', 403);
        }
        return true;
    }
}

// Input validation
class APIValidator {
    public static function validateRequired($data, $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                APIResponse::error("Field '$field' is required");
            }
        }
    }
    
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            APIResponse::error('Invalid email format');
        }
    }
    
    public static function validateNumber($number, $min = null, $max = null) {
        if (!is_numeric($number)) {
            APIResponse::error('Must be a number');
        }
        if ($min !== null && $number < $min) {
            APIResponse::error("Must be at least $min");
        }
        if ($max !== null && $number > $max) {
            APIResponse::error("Must be at most $max");
        }
    }
}
?>