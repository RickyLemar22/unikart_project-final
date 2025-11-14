<?php
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

APIValidator::validateRequired($input, ['order_id', 'amount', 'phone_number', 'provider']);

try {
    // For demo - simulate payment initiation
    // In production, integrate with MTN Mobile Money, Airtel Money APIs
    
    $transaction_id = 'TXN_' . uniqid() . '_' . time();
    $status = 'pending';
    
    // Simulate API call to payment gateway
    $payment_data = [
        'transaction_id' => $transaction_id,
        'order_id' => $input['order_id'],
        'amount' => $input['amount'],
        'currency' => 'UGX',
        'phone_number' => $input['phone_number'],
        'provider' => $input['provider'],
        'status' => $status,
        'payment_url' => null, // Some gateways provide payment URLs
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Store payment record in database
    $stmt = $conn->prepare("
        INSERT INTO payments 
        (order_id, transaction_id, amount, phone_number, provider, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $input['order_id'],
        $transaction_id,
        $input['amount'],
        $input['phone_number'],
        $input['provider'],
        $status
    ]);
    
    APIResponse::success($payment_data, 'Payment initiated successfully');
    
} catch (Exception $e) {
    APIResponse::error('Payment initiation failed: ' . $e->getMessage(), 500);
}
?>