<?php
require_once '../config.php';

$auth = APIAuth::authenticate();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

try {
    switch($method) {
        case 'GET':
            handleGetNotifications($conn, $input);
            break;
        case 'POST':
            APIAuth::requirePermission('write');
            handleCreateNotification($conn, $input);
            break;
        case 'PUT':
            APIAuth::requirePermission('write');
            handleMarkAsRead($conn, $input);
            break;
        default:
            APIResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    APIResponse::error($e->getMessage(), 500);
}

function handleGetNotifications($conn, $params) {
    $user_id = $params['user_id'] ?? null;
    
    if (!$user_id) {
        APIResponse::error('User ID required');
    }
    
    $page = max(1, intval($params['page'] ?? 1));
    $limit = min(50, max(1, intval($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $unread_only = filter_var($params['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    $where = ["user_id = ?"];
    $queryParams = [$user_id];
    
    if ($unread_only) {
        $where[] = "is_read = 0";
    }
    
    $whereClause = implode(" AND ", $where);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM user_notifications WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($queryParams);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get notifications
    $query = "SELECT * FROM user_notifications 
              WHERE $whereClause 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($queryParams);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'notifications' => $notifications,
        'unread_count' => $unread_only ? $total : getUnreadCount($conn, $user_id),
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ];
    
    APIResponse::success($response);
}

function getUnreadCount($conn, $user_id) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $countStmt->execute([$user_id]);
    return $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function handleMarkAsRead($conn, $data) {
    APIValidator::validateRequired($data, ['notification_id', 'user_id']);
    
    $stmt = $conn->prepare("UPDATE user_notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$data['notification_id'], $data['user_id']]);
    
    APIResponse::success(null, 'Notification marked as read');
}
?>