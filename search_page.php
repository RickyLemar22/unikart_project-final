<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Initialize search variables
$search_box = '';
$search_results = [];
$external_results = [];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'local'; // local, external, all

if(isset($_POST['search_box']) || isset($_POST['search_btn'])){
   $search_box = $_POST['search_box'];
   
   // Search local products
   $select_products = $conn->prepare("
      SELECT 
        product_id,
        name, 
        price, 
        image_url,
        category,
        description,
        stock,
        hot_deal,
        featured
      FROM `products` 
      WHERE name LIKE ? OR category LIKE ? OR description LIKE ?
   "); 
   $select_products->execute(["%$search_box%", "%$search_box%", "%$search_box%"]);
   $search_results = $select_products->fetchAll(PDO::FETCH_ASSOC);
   
   // Search external products from all sources
   $external_results = searchExternalProducts($search_box);
}

/**
 * Search external products across all sources
 */
function searchExternalProducts($search_term) {
    if (empty($search_term)) {
        return [];
    }
    
    $all_results = [];
    
    // Search DummyJSON
    try {
        $url = "https://dummyjson.com/products/search?q=" . urlencode($search_term) . "&limit=10";
        $response = @file_get_contents($url);
        $data = json_decode($response, true);
        if (isset($data['products'])) {
            $all_results = array_merge($all_results, formatDummyJSONResults($data['products']));
        }
    } catch (Exception $e) {
        error_log("DummyJSON search error: " . $e->getMessage());
    }
    
    // Search Fake Store
    try {
        $url = "https://fakestoreapi.com/products";
        $response = @file_get_contents($url);
        $products = json_decode($response, true);
        if (is_array($products)) {
            $search_lower = strtolower($search_term);
            $filtered = array_filter($products, function($p) use ($search_lower) {
                return strpos(strtolower($p['title']), $search_lower) !== false ||
                       strpos(strtolower($p['description']), $search_lower) !== false;
            });
            $all_results = array_merge($all_results, formatFakeStoreResults(array_slice($filtered, 0, 10)));
        }
    } catch (Exception $e) {
        error_log("Fake Store search error: " . $e->getMessage());
    }
    
    // Search Platzi
    try {
        $url = "https://api.escuelajs.co/api/v1/products?limit=100";
        $response = @file_get_contents($url);
        $products = json_decode($response, true);
        if (is_array($products)) {
            $search_lower = strtolower($search_term);
            $filtered = array_filter($products, function($p) use ($search_lower) {
                return strpos(strtolower($p['title']), $search_lower) !== false ||
                       strpos(strtolower($p['description']), $search_lower) !== false;
            });
            $all_results = array_merge($all_results, formatPlatziResults(array_slice($filtered, 0, 10)));
        }
    } catch (Exception $e) {
        error_log("Platzi search error: " . $e->getMessage());
    }
    
    return array_slice($all_results, 0, 20); // Limit to 20 results
}

function formatDummyJSONResults($products) {
    $formatted = [];
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => 'ext_dj_' . $product['id'],
            'name' => $product['title'],
            'price' => $product['price'],
            'image_url' => $product['thumbnail'] ?? $product['images'][0] ?? '',
            'category' => $product['category'],
            'description' => $product['description'],
            'stock' => $product['stock'],
            'source' => 'DummyJSON',
            'rating' => $product['rating'] ?? 0,
            'external' => true
        ];
    }
    return $formatted;
}

function formatFakeStoreResults($products) {
    $formatted = [];
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => 'ext_fs_' . $product['id'],
            'name' => $product['title'],
            'price' => $product['price'],
            'image_url' => $product['image'],
            'category' => $product['category'],
            'description' => $product['description'],
            'stock' => 50,
            'source' => 'Fake Store',
            'rating' => $product['rating']['rate'] ?? 0,
            'external' => true
        ];
    }
    return $formatted;
}

function formatPlatziResults($products) {
    if (!is_array($products)) {
        return [];
    }
    
    $formatted = [];
    foreach ($products as $product) {
        $formatted[] = [
            'product_id' => 'ext_pl_' . $product['id'],
            'name' => $product['title'],
            'price' => $product['price'],
            'image_url' => $product['images'][0] ?? '',
            'category' => $product['category']['name'] ?? 'Products',
            'description' => $product['description'],
            'stock' => 50,
            'source' => 'Platzi',
            'rating' => 0,
            'external' => true
        ];
    }
    return $formatted;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Products - UniKart</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      .search-tabs {
         background: var(--white);
         padding: 1rem 0;
         margin-bottom: 2rem;
         border-bottom: 3px solid var(--light-color);
         display: flex;
         gap: 2rem;
         padding: 1rem 2rem;
      }

      .search-tabs a {
         padding: 0.75rem 1.5rem;
         background: transparent;
         border: 2px solid transparent;
         border-bottom: 3px solid transparent;
         cursor: pointer;
         font-weight: 600;
         text-decoration: none;
         color: var(--black);
         transition: all 0.3s ease;
         display: inline-flex;
         align-items: center;
         gap: 0.5rem;
      }

      .search-tabs a:hover {
         color: var(--main-color);
      }

      .search-tabs a.active {
         color: var(--main-color);
         border-bottom-color: var(--main-color);
      }

      .result-badge {
         background: var(--main-color);
         color: white;
         padding: 0.25rem 0.5rem;
         border-radius: 12px;
         font-size: 0.85rem;
      }

      .source-tag {
         display: inline-block;
         background: #667eea;
         color: white;
         padding: 0.25rem 0.75rem;
         border-radius: 4px;
         font-size: 0.75rem;
         margin-top: 0.5rem;
         font-weight: 600;
      }

      .external-product-card {
         position: relative;
      }

      .external-product-card::after {
         content: attr(data-source);
         position: absolute;
         top: 1rem;
         left: 1rem;
         background: rgba(102, 126, 234, 0.9);
         color: white;
         padding: 0.5rem 0.75rem;
         border-radius: 4px;
         font-size: 0.75rem;
         font-weight: 600;
      }
   </style>
<body>
   
<?php include 'components/guest_user_header.php'; ?>

<!-- Search Results Section -->
<section class="search-results">
   <div class="container">
      <?php if(!empty($search_box)): ?>
         <div class="results-header">
            <h3>Search Results for "<?= htmlspecialchars($search_box) ?>"</h3>
            <p class="results-count">
               Found: 
               <strong><?= count($search_results) ?></strong> local product(s)
               | 
               <strong><?= count($external_results) ?></strong> global product(s)
            </p>
         </div>

         <!-- Result Tabs -->
         <div class="search-tabs">
            <a href="?tab=local&search_box=<?= urlencode($search_box) ?>" class="<?= $active_tab === 'local' ? 'active' : '' ?>">
               <i class="fas fa-store"></i> Local Results
               <span class="result-badge"><?= count($search_results) ?></span>
            </a>
            <a href="?tab=external&search_box=<?= urlencode($search_box) ?>" class="<?= $active_tab === 'external' ? 'active' : '' ?>">
               <i class="fas fa-globe"></i> Global Results
               <span class="result-badge"><?= count($external_results) ?></span>
            </a>
            <a href="?tab=all&search_box=<?= urlencode($search_box) ?>" class="<?= $active_tab === 'all' ? 'active' : '' ?>">
               <i class="fas fa-th"></i> All Results
               <span class="result-badge"><?= count($search_results) + count($external_results) ?></span>
            </a>
         </div>
      <?php endif; ?>

      <div class="modern-products-grid">
         <?php 
         // Determine which results to display
         $display_results = [];
         if ($active_tab === 'local') {
            $display_results = $search_results;
         } elseif ($active_tab === 'external') {
            $display_results = $external_results;
         } elseif ($active_tab === 'all') {
            $display_results = array_merge($search_results, $external_results);
         }
         
         if(!empty($display_results)): ?>
            <?php foreach($display_results as $fetch_product): 
               $product_id = $fetch_product['product_id'];
               $product_name = $fetch_product['name'];
               $product_price = $fetch_product['price'];
               $product_image = $fetch_product['image_url'];
               $product_stock = $fetch_product['stock'] ?? 50;
               $is_external = $fetch_product['external'] ?? false;
            ?>
            <form action="" method="post" class="modern-product-card <?= $is_external ? 'external-product-card' : '' ?>" data-source="<?= $fetch_product['source'] ?? 'Local' ?>">
               <input type="hidden" name="pid" value="<?= $product_id; ?>">
               <input type="hidden" name="name" value="<?= htmlspecialchars($product_name); ?>">
               <input type="hidden" name="price" value="<?= $product_price; ?>">
               <input type="hidden" name="image" value="<?= $product_image; ?>">
               
               <div class="modern-product-image">
                  <?php if(!empty($product_image)): ?>
                     <?php if($is_external): ?>
                        <img src="<?= htmlspecialchars($product_image) ?>" alt="<?= htmlspecialchars($product_name); ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">
                     <?php else: ?>
                        <img src="uploaded_img/<?= $product_image; ?>" alt="<?= htmlspecialchars($product_name); ?>">
                     <?php endif; ?>
                  <?php else: ?>
                     <div class="no-image-placeholder">
                        <i class="fas fa-image"></i>
                        <span>No Image</span>
                     </div>
                  <?php endif; ?>
                  
                  <?php if(!$is_external && isset($fetch_product['hot_deal']) && $fetch_product['hot_deal']): ?>
                     <div class="hot-deal-badge">Hot Deal</div>
                  <?php endif; ?>
                  <?php if(!$is_external && isset($fetch_product['featured']) && $fetch_product['featured']): ?>
                     <div class="featured-badge">Featured</div>
                  <?php endif; ?>
                  
                  <?php if($is_external): ?>
                     <div class="source-tag"><?= htmlspecialchars($fetch_product['source']) ?></div>
                  <?php endif; ?>
                  
                  <button type="submit" name="add_to_wishlist" class="modern-wishlist-btn">
                     <i class="far fa-heart"></i>
                  </button>
               </div>
               
               <div class="modern-product-info">
                  <div class="modern-product-name"><?= htmlspecialchars($product_name); ?></div>
                  <div class="modern-product-category"><?= htmlspecialchars($fetch_product['category'] ?? 'Uncategorized'); ?></div>
                  
                  <?php if(isset($fetch_product['rating']) && $fetch_product['rating'] > 0): ?>
                     <div style="font-size: 0.9rem; color: #ffc107; margin: 0.5rem 0;">
                        ★ <?= number_format($fetch_product['rating'], 1) ?>
                     </div>
                  <?php endif; ?>
                  
                  <div class="modern-product-price">
                     <?= $is_external ? '$' : 'UGX ' ?>
                     <?= number_format($product_price, 2); ?>
                  </div>
                  
                  <div class="modern-product-stock">
                     <?php if($product_stock > 0): ?>
                        <span class="in-stock">In Stock (<?= $product_stock ?> available)</span>
                     <?php else: ?>
                        <span class="out-of-stock">Out of Stock</span>
                     <?php endif; ?>
                  </div>
                  
                  <div class="modern-product-controls">
                     <input type="number" name="qty" class="modern-qty" min="1" max="<?= $product_stock > 0 ? $product_stock : 0 ?>" value="1" <?= $product_stock == 0 ? 'disabled' : '' ?>>
                     <button type="submit" name="add_to_cart" class="modern-add-cart" <?= $product_stock == 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-shopping-cart"></i> 
                        <?= $product_stock > 0 ? 'Add to Cart' : 'Out of Stock' ?>
                     </button>
                  </div>
               </div>
            </form>
            <?php endforeach; ?>
         <?php elseif(!empty($search_box)): ?>
            <div class="modern-empty-state">
               <i class="fas fa-search"></i>
               <h3>No products found</h3>
               <p>We couldn't find any products matching "<?= htmlspecialchars($search_box) ?>" in <?= $active_tab === 'all' ? 'any category' : $active_tab ?> results.</p>
               <p>Try checking your spelling or using different keywords.</p>
               <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                  <a href="shop.php" class="modern-add-cart browse-all-btn">
                     <i class="fas fa-store"></i> Browse Local Products
                  </a>
                  <a href="external_products_display.php" class="modern-add-cart browse-all-btn">
                     <i class="fas fa-globe"></i> Browse Global Products
                  </a>
               </div>
            </div>
         <?php else: ?>
            <div class="modern-empty-state">
               <i class="fas fa-search"></i>
               <h3>Start Searching</h3>
               <p>Enter the product name in the search box above to find what you're looking for.</p>
               <p>We'll search both local and global products!</p>
               <div class="search-suggestions">
                  <h4>Popular Searches:</h4>
                  <div class="suggestion-tags">
                     <a href="search_page.php?search_box=smartphone&tab=all" class="suggestion-tag">Smartphones</a>
                     <a href="search_page.php?search_box=laptop&tab=all" class="suggestion-tag">Laptops</a>
                     <a href="search_page.php?search_box=headphones&tab=all" class="suggestion-tag">Headphones</a>
                     <a href="search_page.php?search_box=watch&tab=all" class="suggestion-tag">Watches</a>
                  </div>
               </div>
            </div>
         <?php endif; ?>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>