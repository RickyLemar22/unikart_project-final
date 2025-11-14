<?php
/**
 * External Products Display Component
 * Displays products from free APIs with local caching
 */

include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

include 'components/wishlist_cart.php';

// Cache configuration
define('CACHE_DIR', 'cache/');
define('CACHE_DURATION', 3600); // 1 hour

// Ensure cache directory exists
if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

// Check cache
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

// Save cache
function saveCache($key, $data) {
    $cache_file = CACHE_DIR . $key;
    file_put_contents($cache_file, json_encode($data));
}

// Get external products with caching
function getExternalProductsWithCache($source = 'dummyjson', $limit = 20, $page = 1, $search = '') {
    $cache_key = $source . '_' . md5($page . $limit . $search) . '.json';
    
    // Only use cache if no search term
    if (empty($search)) {
        $cached = checkCache($cache_key);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $products = [];
    
    switch($source) {
        case 'dummyjson':
            $products = fetchFromDummyJSON($page, $limit, $search);
            break;
        case 'fakestore':
            $products = fetchFromFakeStore($page, $limit, $search);
            break;
        case 'platzi':
            $products = fetchFromPlatzi($page, $limit, $search);
            break;
        default:
            $products = fetchFromDummyJSON($page, $limit, $search);
    }
    
    // Save to cache (only if no search)
    if (!empty($products) && empty($search)) {
        saveCache($cache_key, $products);
    }
    
    return $products;
}

// Fetch from DummyJSON
function fetchFromDummyJSON($page = 1, $limit = 20, $search = '') {
    if (!empty($search)) {
        // Use search endpoint for DummyJSON
        $url = "https://dummyjson.com/products/search?q=" . urlencode($search) . "&limit={$limit}";
    } else {
        $skip = ($page - 1) * $limit;
        $url = "https://dummyjson.com/products?skip={$skip}&limit={$limit}";
    }
    
    try {
        $response = @file_get_contents($url);
        $data = json_decode($response, true);
        
        if (isset($data['products'])) {
            return formatDummyJSONProducts($data['products']);
        }
    } catch (Exception $e) {
        error_log("DummyJSON Error: " . $e->getMessage());
    }
    
    return [];
}

// Fetch from Fake Store
function fetchFromFakeStore($page = 1, $limit = 20, $search = '') {
    $url = "https://fakestoreapi.com/products";
    
    try {
        $response = @file_get_contents($url);
        $products = json_decode($response, true);
        
        if (is_array($products)) {
            // Apply search filter
            if (!empty($search)) {
                $search_lower = strtolower($search);
                $products = array_filter($products, function($p) use ($search_lower) {
                    return strpos(strtolower($p['title']), $search_lower) !== false ||
                           strpos(strtolower($p['description']), $search_lower) !== false;
                });
                $products = array_values($products);
            }
            
            // Apply pagination
            $offset = ($page - 1) * $limit;
            $products = array_slice($products, $offset, $limit);
            return formatFakeStoreProducts($products);
        }
    } catch (Exception $e) {
        error_log("Fake Store Error: " . $e->getMessage());
    }
    
    return [];
}

// Fetch from Platzi
function fetchFromPlatzi($page = 1, $limit = 20, $search = '') {
    $offset = ($page - 1) * $limit;
    $url = "https://api.escuelajs.co/api/v1/products?offset={$offset}&limit={$limit}";
    
    try {
        $response = @file_get_contents($url);
        $products = json_decode($response, true);
        
        if (is_array($products)) {
            // Apply search filter
            if (!empty($search)) {
                $search_lower = strtolower($search);
                $products = array_filter($products, function($p) use ($search_lower) {
                    return strpos(strtolower($p['title']), $search_lower) !== false ||
                           strpos(strtolower($p['description']), $search_lower) !== false;
                });
                $products = array_values($products);
            }
            
            return formatPlatziProducts($products);
        }
    } catch (Exception $e) {
        error_log("Platzi Error: " . $e->getMessage());
    }
    
    return [];
}

// Format DummyJSON products
function formatDummyJSONProducts($products) {
    $formatted = [];
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => $product['id'],
            'name' => $product['title'],
            'description' => $product['description'],
            'price' => $product['price'],
            'image_url' => $product['thumbnail'] ?? $product['images'][0] ?? '',
            'category' => $product['category'],
            'stock' => $product['stock'],
            'rating' => $product['rating'] ?? 0,
            'source' => 'dummyjson'
        ];
    }
    return $formatted;
}

// Format Fake Store products
function formatFakeStoreProducts($products) {
    $formatted = [];
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => $product['id'],
            'name' => $product['title'],
            'description' => $product['description'],
            'price' => $product['price'],
            'image_url' => $product['image'],
            'category' => $product['category'],
            'rating' => $product['rating']['rate'] ?? 0,
            'source' => 'fakestore'
        ];
    }
    return $formatted;
}

// Format Platzi products
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
            'image_url' => $product['images'][0] ?? '',
            'category' => $product['category']['name'] ?? 'Uncategorized',
            'source' => 'platzi'
        ];
    }
    return $formatted;
}

// Get parameters
$source = $_GET['source'] ?? 'dummyjson';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get products
$products = getExternalProductsWithCache($source, $limit, $page, $search);

// Filter by search if provided (in addition to API search)
if (!empty($search)) {
    $search_lower = strtolower($search);
    $products = array_filter($products, function($p) use ($search_lower) {
        return strpos(strtolower($p['name']), $search_lower) !== false ||
               strpos(strtolower($p['description']), $search_lower) !== false;
    });
    $products = array_values($products);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>External Products - UniKart</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   
   <style>
       .api-source-selector {
           background: var(--white);
           padding: 2rem;
           margin-bottom: 2rem;
           border-radius: 0.5rem;
           box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
           display: flex;
           gap: 1rem;
           flex-wrap: wrap;
           align-items: center;
       }
       
       .api-source-selector button {
           padding: 0.75rem 1.5rem;
           border: 2px solid var(--light-color);
           background: var(--white);
           border-radius: 0.25rem;
           cursor: pointer;
           font-weight: 600;
           transition: all 0.3s ease;
       }
       
       .api-source-selector button.active {
           background: var(--main-color);
           color: var(--white);
           border-color: var(--main-color);
       }
       
       .api-source-selector button:hover:not(.active) {
           border-color: var(--main-color);
       }
       
       .source-info {
           font-size: 0.9rem;
           color: var(--light-color);
           margin-left: auto;
       }
       
       .product-grid {
           display: grid;
           grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
           gap: 1.5rem;
           margin-top: 2rem;
       }
       
       .external-product-card {
           background: var(--white);
           border-radius: 0.5rem;
           overflow: hidden;
           box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
           transition: all 0.3s ease;
           display: flex;
           flex-direction: column;
       }
       
       .external-product-card:hover {
           box-shadow: 0 1rem 2rem rgba(0,0,0,0.15);
           transform: translateY(-5px);
       }
       
       .product-image-container {
           width: 100%;
           height: 250px;
           background: var(--light-color);
           position: relative;
           overflow: hidden;
       }
       
       .product-image-container img {
           width: 100%;
           height: 100%;
           object-fit: cover;
       }
       
       .product-image-container .no-image {
           display: flex;
           align-items: center;
           justify-content: center;
           height: 100%;
           font-size: 3rem;
           color: var(--light-color);
       }
       
       .wishlist-badge {
           position: absolute;
           top: 1rem;
           right: 1rem;
           background: var(--white);
           border: none;
           width: 2.5rem;
           height: 2.5rem;
           border-radius: 50%;
           cursor: pointer;
           display: flex;
           align-items: center;
           justify-content: center;
           font-size: 1.2rem;
           color: var(--main-color);
           transition: all 0.3s ease;
           box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
       }
       
       .wishlist-badge:hover {
           background: var(--main-color);
           color: var(--white);
       }
       
       .wishlist-badge.added {
           background: var(--main-color);
           color: var(--white);
       }
       
       .product-details {
           padding: 1.5rem;
           flex-grow: 1;
           display: flex;
           flex-direction: column;
       }
       
       .product-name {
           font-weight: 600;
           color: var(--black);
           margin-bottom: 0.5rem;
           font-size: 0.95rem;
           line-height: 1.4;
       }
       
       .product-source {
           font-size: 0.75rem;
           color: var(--light-color);
           text-transform: capitalize;
           margin-bottom: 0.5rem;
       }
       
       .product-price {
           font-size: 1.5rem;
           color: var(--main-color);
           font-weight: bold;
           margin-bottom: 0.5rem;
       }
       
       .product-rating {
           display: flex;
           align-items: center;
           gap: 0.25rem;
           margin-bottom: 1rem;
           font-size: 0.9rem;
       }
       
       .rating-stars {
           color: #ffc107;
       }
       
       .rating-value {
           color: var(--light-color);
       }
       
       .product-category {
           font-size: 0.85rem;
           background: var(--light-color);
           padding: 0.25rem 0.75rem;
           border-radius: 0.25rem;
           width: fit-content;
           margin-bottom: 1rem;
       }
       
       .add-to-cart-btn {
           background: var(--main-color);
           color: var(--white);
           border: none;
           padding: 0.75rem;
           border-radius: 0.25rem;
           cursor: pointer;
           font-weight: 600;
           transition: background 0.3s ease;
           margin-top: auto;
       }
       
       .add-to-cart-btn:hover {
           background: var(--main-color-dark);
       }

       .search-filter-container {
           background: var(--white);
           padding: 2rem;
           margin-bottom: 2rem;
           border-radius: 0.5rem;
           box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
           display: flex;
           gap: 1rem;
           flex-wrap: wrap;
           align-items: center;
       }

       .search-input-group {
           display: flex;
           gap: 0.5rem;
           flex: 1;
           min-width: 250px;
       }

       .search-input-group input {
           flex: 1;
           padding: 0.75rem;
           border: 2px solid var(--light-color);
           border-radius: 0.25rem;
           font-size: 1rem;
       }

       .search-input-group input:focus {
           outline: none;
           border-color: var(--main-color);
           box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
       }

       .search-btn {
           background: var(--main-color);
           color: var(--white);
           border: none;
           padding: 0.75rem 1.5rem;
           border-radius: 0.25rem;
           cursor: pointer;
           font-weight: 600;
           transition: background 0.3s ease;
       }

       .search-btn:hover {
           background: #5568d3;
       }

       .clear-search-btn {
           background: var(--light-color);
           color: var(--black);
           border: none;
           padding: 0.75rem 1.5rem;
           border-radius: 0.25rem;
           cursor: pointer;
           font-weight: 600;
           transition: background 0.3s ease;
       }

       .clear-search-btn:hover {
           background: #ddd;
       }

       .search-info {
           font-size: 0.9rem;
           color: var(--light-color);
           flex: 1;
           text-align: right;
       }

       .active-search-tag {
           background: #667eea;
           color: white;
           padding: 0.5rem 1rem;
           border-radius: 20px;
           font-size: 0.9rem;
           display: inline-flex;
           align-items: center;
           gap: 0.5rem;
       }

       .active-search-tag button {
           background: none;
           border: none;
           color: white;
           cursor: pointer;
           font-size: 1.2rem;
           padding: 0;
       }
   </style>
</head>
<body>
   
<?php 
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '') {
    include 'components/user_header.php';
} else {
    include 'components/guest_user_header.php';
}
?>

<div class="modern-layout">
    <main class="modern-content" style="width: 100%;">
        <div class="welcome-banner">
            <h1>External Products</h1>
            <p>Browse products from trusted global sources</p>
        </div>

        <!-- Search & Filter Container -->
        <div class="search-filter-container">
            <form method="GET" style="display: flex; gap: 1rem; flex: 1; flex-wrap: wrap; align-items: center;">
                <!-- Hidden source field to maintain current source -->
                <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">
                
                <div class="search-input-group">
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?= htmlspecialchars($search) ?>" 
                           style="flex: 1;">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="?source=<?= urlencode($source) ?>" class="clear-search-btn">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>

                <div class="search-info">
                    <?php if (!empty($search)): ?>
                    <span class="active-search-tag">
                        <i class="fas fa-filter"></i>
                        Searching: "<?= htmlspecialchars(substr($search, 0, 15)) . (strlen($search) > 15 ? '...' : '') ?>"
                    </span>
                    <?php else: ?>
                    <small><i class="fas fa-info-circle"></i> Found <?= count($products) ?> product(s)</small>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- API Source Selector -->
        <div class="api-source-selector">
            <span style="font-weight: 600;">Select Source:</span>
            <button class="<?= $source === 'dummyjson' ? 'active' : '' ?>" onclick="changeSource('dummyjson')">
                <i class="fas fa-cube"></i> DummyJSON
            </button>
            <button class="<?= $source === 'fakestore' ? 'active' : '' ?>" onclick="changeSource('fakestore')">
                <i class="fas fa-store"></i> Fake Store
            </button>
            <button class="<?= $source === 'platzi' ? 'active' : '' ?>" onclick="changeSource('platzi')">
                <i class="fas fa-shopping-bag"></i> Platzi
            </button>
            <span class="source-info">
                <i class="fas fa-info-circle"></i>
                Cached for 1 hour | Auto-refreshes
            </span>
        </div>

        <!-- Products Grid -->
        <div class="product-grid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $product): ?>
                <div class="external-product-card">
                    <div class="product-image-container">
                        <?php if(!empty($product['image_url'])): ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($user_id != ''): ?>
                        <button type="button" class="wishlist-badge" onclick="addToWishlistExternal(this, '<?= addslashes($product['name']) ?>', '<?= $product['price'] ?>', '<?= htmlspecialchars($product['image_url']) ?>')">
                            <i class="fas fa-heart"></i>
                        </button>
                        <?php else: ?>
                        <a href="user_login.php" class="wishlist-badge">
                            <i class="far fa-heart"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-details">
                        <div class="product-source"><?= ucfirst($product['source']) ?></div>
                        <div class="product-name"><?= htmlspecialchars(substr($product['name'], 0, 50)) ?></div>
                        <div class="product-category"><?= htmlspecialchars(substr($product['category'], 0, 20)) ?></div>
                        
                        <?php if(isset($product['rating']) && $product['rating'] > 0): ?>
                        <div class="product-rating">
                            <span class="rating-stars">
                                <?php 
                                $rating = round($product['rating']);
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </span>
                            <span class="rating-value"><?= number_format($product['rating'], 1) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                        
                        <?php if($user_id != ''): ?>
                        <button type="button" class="add-to-cart-btn" onclick="addExternalToCart(this, '<?= addslashes($product['name']) ?>', '<?= $product['price'] ?>', '<?= htmlspecialchars($product['image_url']) ?>')">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <?php else: ?>
                        <a href="user_login.php" class="add-to-cart-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none;">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: var(--light-color); margin-bottom: 1rem;"></i>
                    <h3>No products found</h3>
                    <p>Try a different source or check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include 'components/footer.php'; ?>

<script>
function changeSource(source) {
    const searchParam = new URLSearchParams(window.location.search).get('search');
    let url = `external_products_display.php?source=${source}&limit=20&page=1`;
    if (searchParam) {
        url += `&search=${encodeURIComponent(searchParam)}`;
    }
    window.location.href = url;
}

function addExternalToCart(button, name, price, image) {
    // This creates a temporary cart item for external products
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-check"></i> Added!';
    
    // Add to session/cart
    const cartItem = {
        name: name,
        price: price,
        image: image,
        qty: 1,
        from_external: true
    };
    
    // Store in localStorage for now (you can extend this)
    let cart = JSON.parse(localStorage.getItem('external_cart') || '[]');
    cart.push(cartItem);
    localStorage.setItem('external_cart', JSON.stringify(cart));
    
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
    }, 2000);
}

function addToWishlistExternal(button, name, price, image) {
    button.classList.add('added');
    button.innerHTML = '<i class="fas fa-heart"></i>';
    
    // Add to external wishlist
    let wishlist = JSON.parse(localStorage.getItem('external_wishlist') || '[]');
    wishlist.push({name, price, image});
    localStorage.setItem('external_wishlist', JSON.stringify(wishlist));
}

// Auto-focus search input if there's already a search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput && searchInput.value) {
        searchInput.focus();
        searchInput.select();
    }
});
</script>

</body>
</html>
