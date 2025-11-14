<?php
require_once '../config.php';

// Authenticate request
$auth = APIAuth::authenticate();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

try {
    switch($method) {
        case 'GET':
            handleGetProducts($conn, $input);
            break;
        case 'POST':
            APIAuth::requirePermission('write');
            handleCreateProduct($conn, $input);
            break;
        case 'PUT':
            APIAuth::requirePermission('write');
            handleUpdateProduct($conn, $input);
            break;
        case 'DELETE':
            APIAuth::requirePermission('write');
            handleDeleteProduct($conn, $input);
            break;
        default:
            APIResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    APIResponse::error($e->getMessage(), 500);
}

function handleGetProducts($conn, $params) {
    $page = max(1, intval($params['page'] ?? 1));
    $limit = min(50, max(1, intval($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $where = [];
    $queryParams = [];
    
    // Search filters
    if (!empty($params['search'])) {
        $where[] = "(name LIKE ? OR description LIKE ?)";
        $searchTerm = "%{$params['search']}%";
        $queryParams[] = $searchTerm;
        $queryParams[] = $searchTerm;
    }
    
    if (!empty($params['category'])) {
        $where[] = "category = ?";
        $queryParams[] = $params['category'];
    }
    
    if (!empty($params['min_price'])) {
        $where[] = "price >= ?";
        $queryParams[] = floatval($params['min_price']);
    }
    
    if (!empty($params['max_price'])) {
        $where[] = "price <= ?";
        $queryParams[] = floatval($params['max_price']);
    }
    
    if (!empty($params['in_stock'])) {
        $where[] = "stock > 0";
    }
    
    if (!empty($params['featured'])) {
        $where[] = "featured = 1";
    }
    
    if (!empty($params['hot_deal'])) {
        $where[] = "hot_deal = 1";
    }
    
    // Build query
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM products $whereClause";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($queryParams);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get products
    $orderBy = match($params['sort'] ?? 'newest') {
        'price_low' => 'price ASC',
        'price_high' => 'price DESC',
        'name' => 'name ASC',
        'popular' => '(SELECT COUNT(*) FROM order_items WHERE order_items.product_id = products.product_id) DESC',
        default => 'created_at DESC'
    };
    
    $query = "SELECT 
                product_id, name, description, price, stock, 
                image_url, category, featured, hot_deal,
                created_at, updated_at
              FROM products 
              $whereClause 
              ORDER BY $orderBy 
              LIMIT ? OFFSET ?";
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ],
        'filters' => [
            'search' => $params['search'] ?? null,
            'category' => $params['category'] ?? null,
            'min_price' => $params['min_price'] ?? null,
            'max_price' => $params['max_price'] ?? null
        ]
    ];
    
    APIResponse::success($response);
}

function handleCreateProduct($conn, $data) {
    APIValidator::validateRequired($data, ['name', 'category', 'price', 'stock']);
    
    $query = "INSERT INTO products 
              (name, category, price, stock, description, image_url, featured, hot_deal, supplier_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $data['name'],
        $data['category'],
        floatval($data['price']),
        intval($data['stock']),
        $data['description'] ?? '',
        $data['image_url'] ?? '',
        $data['featured'] ?? 0,
        $data['hot_deal'] ?? 0,
        $data['supplier_id'] ?? 1
    ]);
    
    $product_id = $conn->lastInsertId();
    
    // Get created product
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    APIResponse::success($product, 'Product created successfully');
}

function handleUpdateProduct($conn, $data) {
    APIValidator::validateRequired($data, ['product_id']);
    
    $allowed_fields = ['name', 'category', 'price', 'stock', 'description', 'image_url', 'featured', 'hot_deal'];
    $updates = [];
    $params = [];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        APIResponse::error('No fields to update');
    }
    
    $params[] = $data['product_id'];
    
    $query = "UPDATE products SET " . implode(', ', $updates) . " WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    // Get updated product
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$data['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    APIResponse::success($product, 'Product updated successfully');
}

function handleDeleteProduct($conn, $data) {
    APIValidator::validateRequired($data, ['product_id']);
    
    // Check if product exists in any orders
    $check_orders = $conn->prepare("
        SELECT COUNT(*) as order_count 
        FROM order_items 
        WHERE product_id = ?
    ");
    $check_orders->execute([$data['product_id']]);
    $order_count = $check_orders->fetch(PDO::FETCH_ASSOC)['order_count'];
    
    if ($order_count > 0) {
        // Soft delete - set stock to 0 instead of deleting
        $stmt = $conn->prepare("UPDATE products SET stock = 0 WHERE product_id = ?");
        $stmt->execute([$data['product_id']]);
        APIResponse::success(null, 'Product disabled (exists in orders)');
    } else {
        // Hard delete
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$data['product_id']]);
        APIResponse::success(null, 'Product deleted successfully');
    }
}
?>