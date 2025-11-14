<?php
/**
 * External Products API Integration
 * Uses free APIs: DummyJSON, Fake Store API, Platzi Fake Store
 * Includes caching for better performance
 */

require_once '../config.php';

// Cache configuration
define('CACHE_DIR', '../../cache/');
define('CACHE_DURATION', 3600); // 1 hour


if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

try {
    switch($method) {
        case 'GET':
            handleGetExternalProducts($input);
            break;
        default:
            APIResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    APIResponse::error($e->getMessage(), 500);
}

/**
 * Get products from external APIs
 * 
 */
function handleGetExternalProducts($params) {
    $source = $params['source'] ?? 'dummyjson'; // dummyjson, fakestore, platzi, local
    $page = max(1, intval($params['page'] ?? 1));
    $limit = min(50, max(1, intval($params['limit'] ?? 20)));
    $search = $params['search'] ?? '';
    $category = $params['category'] ?? '';
    
    $products = [];
    
    switch($source) {
        case 'dummyjson':
            $products = getFromDummyJSON($page, $limit, $search, $category);
            break;
        case 'fakestore':
            $products = getFromFakeStore($page, $limit, $search, $category);
            break;
        case 'platzi':
            $products = getFromPlatzi($page, $limit, $search, $category);
            break;
        case 'all':
            // Combine multiple sources
            $products = array_merge(
                getFromDummyJSON(1, 10, $search, $category),
                getFromFakeStore(1, 10, $search, $category),
                getFromPlatzi(1, 10, $search, $category)
            );
            break;
        default:
            APIResponse::error('Invalid source. Use: dummyjson, fakestore, platzi, or all');
    }
    
    $response = [
        'source' => $source,
        'products' => $products,
        'count' => count($products),
        'pagination' => [
            'page' => $page,
            'limit' => $limit
        ]
    ];
    
    APIResponse::success($response);
}

/**
 * Get products from DummyJSON API
 * Best option: High quality product data with descriptions and reviews
 */
function getFromDummyJSON($page = 1, $limit = 20, $search = '', $category = '') {
    $skip = ($page - 1) * $limit;
    $cache_key = 'dummyjson_' . md5($page . $limit . $search . $category) . '.json';
    
    // Check cache
    $cached = checkCache($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    
    $url = "https://dummyjson.com/products";
    
    // Add filters
    if (!empty($search)) {
        $url = "https://dummyjson.com/products/search?q=" . urlencode($search);
    } elseif (!empty($category)) {
        $url = "https://dummyjson.com/products/category/" . urlencode($category);
    }
    
    // Add pagination
    $url .= (strpos($url, '?') ? '&' : '?') . "skip=$skip&limit=$limit";
    
    try {
        $response = @file_get_contents($url);
        $data = json_decode($response, true);
        
        if (isset($data['products'])) {
            $products = formatDummyJSONProducts($data['products']);
            saveCache($cache_key, $products);
            return $products;
        }
    } catch (Exception $e) {
        error_log("DummyJSON API Error: " . $e->getMessage());
    }
    
    return [];
}

/**
 * Get products from Fake Store API
 * Simple and reliable with basic product info
 */
function getFromFakeStore($page = 1, $limit = 20, $search = '', $category = '') {
    $cache_key = 'fakestore_' . md5($page . $limit . $search . $category) . '.json';
    
    // Check cache
    $cached = checkCache($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    
    $url = "https://fakestoreapi.com/products";
    
    if (!empty($category)) {
        $url .= "?category=" . urlencode($category);
    }
    
    try {
        $response = @file_get_contents($url);
        $products = json_decode($response, true);
        
        // Apply search filter
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $products = array_filter($products, function($p) use ($search_lower) {
                return strpos(strtolower($p['title']), $search_lower) !== false ||
                       strpos(strtolower($p['description']), $search_lower) !== false;
            });
        }
        
        // Apply pagination
        $offset = ($page - 1) * $limit;
        $products = array_slice($products, $offset, $limit);
        
        $formatted = formatFakeStoreProducts($products);
        saveCache($cache_key, $formatted);
        return $formatted;
    } catch (Exception $e) {
        error_log("Fake Store API Error: " . $e->getMessage());
    }
    
    return [];
}

/**
 * Get products from Platzi Fake Store API
 * Another reliable free API with good product data
 */
function getFromPlatzi($page = 1, $limit = 20, $search = '', $category = '') {
    $cache_key = 'platzi_' . md5($page . $limit . $search . $category) . '.json';
    
    // Check cache
    $cached = checkCache($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    
    $offset = ($page - 1) * $limit;
    $url = "https://api.escuelajs.co/api/v1/products?offset=$offset&limit=$limit";
    
    if (!empty($category)) {
        $url = "https://api.escuelajs.co/api/v1/categories/" . urlencode($category) . "/products";
    }
    
    try {
        $response = @file_get_contents($url);
        $products = json_decode($response, true);
        
        // Apply search filter
        if (!empty($search) && is_array($products)) {
            $search_lower = strtolower($search);
            $products = array_filter($products, function($p) use ($search_lower) {
                return strpos(strtolower($p['title']), $search_lower) !== false ||
                       strpos(strtolower($p['description']), $search_lower) !== false;
            });
        }
        
        $formatted = formatPlatziProducts($products);
        saveCache($cache_key, $formatted);
        return $formatted;
    } catch (Exception $e) {
        error_log("Platzi API Error: " . $e->getMessage());
    }
    
    return [];
}

/**
 * Check if data exists in cache
 */
function checkCache($key) {
    $cache_file = CACHE_DIR . $key;
    
    if (file_exists($cache_file)) {
        $file_age = time() - filemtime($cache_file);
        if ($file_age < CACHE_DURATION) {
            return json_decode(file_get_contents($cache_file), true);
        }
    }
    
    return null;
}

/**
 * Save data to cache
 */
function saveCache($key, $data) {
    $cache_file = CACHE_DIR . $key;
    file_put_contents($cache_file, json_encode($data));
}

/**
 * Format DummyJSON products to standard format
 */
function formatDummyJSONProducts($products) {
    $formatted = [];
    
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => $product['id'],
            'name' => $product['title'],
            'description' => $product['description'],
            'price' => $product['price'],
            'currency' => 'USD',
            'image_url' => $product['thumbnail'] ?? $product['images'][0] ?? '',
            'category' => $product['category'],
            'stock' => $product['stock'],
            'rating' => $product['rating'] ?? 0,
            'reviews_count' => $product['reviews'] ?? 0,
            'discount_percent' => $product['discountPercentage'] ?? 0,
            'brand' => $product['brand'] ?? '',
            'sku' => $product['sku'] ?? '',
            'source' => 'dummyjson'
        ];
    }
    
    return $formatted;
}

/**
 * Format Fake Store products to standard format
 */
function formatFakeStoreProducts($products) {
    $formatted = [];
    
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => $product['id'],
            'name' => $product['title'],
            'description' => $product['description'],
            'price' => $product['price'],
            'currency' => 'USD',
            'image_url' => $product['image'],
            'category' => $product['category'],
            'rating' => $product['rating']['rate'] ?? 0,
            'reviews_count' => $product['rating']['count'] ?? 0,
            'source' => 'fakestore'
        ];
    }
    
    return $formatted;
}

/**
 * Format Platzi products to standard format
 */
function formatPlatziProducts($products) {
    if (!is_array($products)) {
        return [];
    }
    
    $formatted = [];
    
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => $product['id'],
            'name' => $product['title'],
            'description' => $product['description'],
            'price' => $product['price'],
            'currency' => 'USD',
            'image_url' => $product['images'][0] ?? '',
            'category' => $product['category']['name'] ?? 'Uncategorized',
            'creation_date' => $product['creationAt'] ?? '',
            'source' => 'platzi'
        ];
    }
    
    return $formatted;
}
?>
