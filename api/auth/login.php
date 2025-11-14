<?php
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

APIValidator::validateRequired($input, ['login', 'password']);

$login = $input['login'];
$password = $input['password'];

try {
    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
    
    if($isEmail){
        $query = "SELECT ua.*, s.password, s.full_name, s.contact, s.address 
                  FROM user_account ua 
                  LEFT JOIN student s ON ua.university_email = s.university_email 
                  WHERE ua.university_email = ? AND ua.account_status = 'active'";
    } else {
        $query = "SELECT ua.*, s.password, s.full_name, s.contact, s.address 
                  FROM user_account ua 
                  LEFT JOIN student s ON ua.university_email = s.university_email 
                  WHERE s.contact = ? AND ua.account_status = 'active'";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['password'])) {
        // Update last login
        $update = $conn->prepare("UPDATE user_account SET last_login = NOW() WHERE account_id = ?");
        $update->execute([$user['account_id']]);
        
        // Generate API token (simple version - in production use JWT)
        $api_token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Store token in database
        $token_stmt = $conn->prepare("
            INSERT INTO user_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $token_stmt->execute([$user['account_id'], $api_token, $token_expiry]);
        
        $response = [
            'user' => [
                'user_id' => $user['account_id'],
                'email' => $user['university_email'],
                'full_name' => $user['full_name'],
                'contact' => $user['contact'],
                'address' => $user['address']
            ],
            'token' => $api_token,
            'expires_at' => $token_expiry
        ];
        
        APIResponse::success($response, 'Login successful');
    } else {
        APIResponse::error('Invalid credentials', 401);
    }
} catch (Exception $e) {
    APIResponse::error('Login failed: ' . $e->getMessage(), 500);
}
?>