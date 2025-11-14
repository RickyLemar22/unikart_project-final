<?php
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$transaction_id = $input['transaction_id'] ?? $_GET['transaction_id'];

if (!$transaction_id) {
    APIResponse::error('Transaction ID required');
}

try {
    // Get payment record
    $stmt = $conn->prepare("SELECT * FROM payments WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        APIResponse::error('Transaction not found');
    }
    
    // Simulate payment verification
    // In production, call payment gateway API
    $statuses = ['pending', 'completed', 'failed'];
    $simulated_status = $statuses[array_rand($statuses)];
    
    // Update payment status
    $update_stmt = $conn->prepare("UPDATE payments SET status = ? WHERE transaction_id = ?");
    $update_stmt->execute([$simulated_status, $transaction_id]);
    
    // If payment completed, update order status
    if ($simulated_status === 'completed') {
        $order_stmt = $conn->prepare("UPDATE orders SET order_status = 'confirmed' WHERE order_id = ?");
        $order_stmt->execute([$payment['order_id']]);
    }
    
    $response = [
        'transaction_id' => $transaction_id,
        'status' => $simulated_status,
        'order_id' => $payment['order_id'],
        'verified_at' => date('Y-m-d H:i:s')
    ];
    
    APIResponse::success($response, 'Payment verification completed');
    
} catch (Exception $e) {
    APIResponse::error('Payment verification failed: ' . $e->getMessage(), 500);
}
?>