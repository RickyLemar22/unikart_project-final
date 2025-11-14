<?php
require_once '../config.php';

// Authenticate request
$auth = APIAuth::authenticate();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

try {
    switch($method) {
        case 'GET':
            handleGetOrders($conn, $input);
            break;
        case 'POST':
            APIAuth::requirePermission('write');
            handleCreateOrder($conn, $input);
            break;
        case 'PUT':
            APIAuth::requirePermission('write');
            handleUpdateOrder($conn, $input);
            break;
        default:
            APIResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    APIResponse::error($e->getMessage(), 500);
}

function handleGetOrders($conn, $params) {
    $user_id = $params['user_id'] ?? null;
    $page = max(1, intval($params['page'] ?? 1));
    $limit = min(50, max(1, intval($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $where = ["1=1"];
    $queryParams = [];
    
    if ($user_id) {
        $where[] = "account_id = ?";
        $queryParams[] = $user_id;
    }
    
    if (!empty($params['status'])) {
        $where[] = "order_status = ?";
        $queryParams[] = $params['status'];
    }
    
    $whereClause = implode(" AND ", $where);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM orders WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($queryParams);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get orders
    $query = "SELECT * FROM orders 
              WHERE $whereClause 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($queryParams);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $itemsQuery = "SELECT oi.*, p.name, p.image_url 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.product_id 
                       WHERE oi.order_id = ?";
        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([$order['order_id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $response = [
        'orders' => $orders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ];
    
    APIResponse::success($response);
}

function handleCreateOrder($conn, $data) {
    APIValidator::validateRequired($data, [
        'account_id', 'items', 'delivery_method', 'payment_method'
    ]);
    
    if (!is_array($data['items']) || empty($data['items'])) {
        APIResponse::error('Order must contain items');
    }
    
    try {
        $conn->beginTransaction();
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            // Verify product exists and has enough stock
            $product_stmt = $conn->prepare("
                SELECT price, stock, name 
                FROM products 
                WHERE product_id = ? AND stock >= ?
            ");
            $product_stmt->execute([$item['product_id'], $item['quantity']]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product {$item['product_id']} not available or insufficient stock");
            }
            
            $total_amount += $product['price'] * $item['quantity'];
        }
        
        // Create order
        $order_query = "
            INSERT INTO orders 
            (account_id, total_amount, delivery_method, delivery_date, delivery_time,
             pickup_station, delivery_address, payment_method, mobile_money_provider, 
             mobile_money_number, order_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ";
        
        $delivery_date = date('Y-m-d', strtotime('+1 day'));
        $delivery_time = '14:00:00';
        
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->execute([
            $data['account_id'],
            $total_amount,
            $data['delivery_method'],
            $delivery_date,
            $delivery_time,
            $data['pickup_station'] ?? null,
            $data['delivery_address'] ?? null,
            $data['payment_method'],
            $data['mobile_money_provider'] ?? null,
            $data['mobile_money_number'] ?? null
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Add order items and update stock
        foreach ($data['items'] as $item) {
            // Get product price
            $product_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
            $product_stmt->execute([$item['product_id']]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Insert order item
            $item_query = "
                INSERT INTO order_items 
                (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ";
            $item_stmt = $conn->prepare($item_query);
            $item_stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $product['price']
            ]);
            
            // Update product stock
            $update_stock = $conn->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE product_id = ?
            ");
            $update_stock->execute([$item['quantity'], $item['product_id']]);
        }
        
        $conn->commit();
        
        // Return created order
        $order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get order items
        $items_stmt = $conn->prepare("
            SELECT oi.*, p.name, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.product_id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order_id]);
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        APIResponse::success($order, 'Order placed successfully');
        
    } catch (Exception $e) {
        $conn->rollBack();
        APIResponse::error('Order placing failed: ' . $e->getMessage(), 500);
    }
}
?>