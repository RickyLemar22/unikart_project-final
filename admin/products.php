<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if(!$admin_id){
    header('location:admin_login.php');
    exit;
}

// Handle add product
if(isset($_POST['add_product'])){
    $name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $details = filter_var($_POST['details'], FILTER_SANITIZE_SPECIAL_CHARS);
    $category = filter_var($_POST['category'], FILTER_SANITIZE_SPECIAL_CHARS);
    $stock = filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT);
    
    // CORRECTED: Using your actual table columns
    $hot_deal = isset($_POST['hot_deal']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;

    $image_01 = $_FILES['image_01']['name'];
    $image_size = $_FILES['image_01']['size'];
    $image_tmp = $_FILES['image_01']['tmp_name'];
    $image_folder = '../uploaded_img/'.$image_01;

    $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
    $select_products->execute([$name]);

    if($select_products->rowCount() > 0){
        $message[] = 'Product name already exists!';
    } elseif($image_size > 2000000){
        $message[] = 'Image size is too large!';
    } else {
        // CORRECTED: Using actual column names from your table
        $insert_product = $conn->prepare("INSERT INTO `products` (name, description, price, category, image_url, stock, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_product->execute([$name, $details, $price, $category, $image_01, $stock, 1]); // Using supplier_id = 1 as default
        move_uploaded_file($image_tmp, $image_folder);
        $message[] = 'New product added!';
    }
}

// Handle delete product
if(isset($_GET['delete'])){
    $delete_id = (int)$_GET['delete'];
    // CORRECTED: Using product_id instead of id
    $fetch_delete = $conn->prepare("SELECT * FROM `products` WHERE product_id = ?");
    $fetch_delete->execute([$delete_id]);
    $product = $fetch_delete->fetch(PDO::FETCH_ASSOC);

    if($product){
        if(file_exists('../uploaded_img/'.$product['image_url'])){
            unlink('../uploaded_img/'.$product['image_url']);
        }
        // CORRECTED: Using product_id instead of id
        $conn->prepare("DELETE FROM `products` WHERE product_id = ?")->execute([$delete_id]);
        // Note: You might need to update cart and wishlist references if they exist
    }
    header('location:products.php');
    exit;
}

// Fetch all products
// CORRECTED: Using product_id instead of id and ordering by product_id
$select_products = $conn->prepare("SELECT * FROM `products` ORDER BY product_id DESC");
$select_products->execute();
$products = $select_products->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unikart | Products</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="add-products">
    <h1 class="heading">Add Product</h1>
    <form action="" method="post" enctype="multipart/form-data" class="add-product-form">
        <div class="flex">
            <div class="inputBox">
                <span>Name</span>
                <input type="text" name="name" class="box" required maxlength="100" placeholder="Product name">
            </div>
            <div class="inputBox">
                <span>Price (UGx)</span>
                <input type="number" name="price" class="box" required min="0" step="0.01" placeholder="Product price">
            </div>
            <div class="inputBox">
                <span>Stock Quantity</span>
                <input type="number" name="stock" class="box" required min="0" placeholder="Available stock">
            </div>
            <div class="inputBox">
                <span>Category</span>
                <select name="category" class="box" required>
                    <option value="computing">Computing & Accessories</option>
                    <option value="smartphones">Smartphones, Tablets & Accessories</option>
                    <option value="appliances">Hostel Electrical Appliances</option>
                    <option value="bedding">Bedding & Interiors</option>
                    <option value="scholastic">Scholastic Materials</option>
                    <option value="groceries">Drinks, Foods & Groceries</option>
                    <option value="personal">Personal Care & Beauty</option>
                    <option value="clothing">Clothing & Fashion</option>
                    <option value="kitchenware">Kitchenware</option>
                    <option value="sports">Sports & Fitness</option>
                </select>
            </div>
            <!-- REMOVED: hot_deal and featured checkboxes since columns don't exist -->
            <div class="inputBox">
                <span>Image</span>
                <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
            </div>
            <div class="inputBox">
                <span>Description</span>
                <textarea name="details" class="box" required maxlength="500" placeholder="Product description" cols="30" rows="5"></textarea>
            </div>
        </div>
        <input type="submit" value="Add Product" class="btn" name="add_product">
    </form>
</section>

<section class="show-products">
    <h1 class="heading">Products Added</h1>
    <div class="box-container">
        <?php if($products): ?>
            <?php foreach($products as $product): ?>
            <div class="box">
                <img src="../uploaded_img/<?= htmlspecialchars($product['image_url']); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                <div class="name"><?= htmlspecialchars($product['name']); ?></div>
                <div class="price">UGx <span><?= number_format($product['price'], 2); ?></span></div>
                <div class="stock">Stock: <?= htmlspecialchars($product['stock']); ?></div>
                <div class="category">Category: <?= htmlspecialchars($product['category']); ?></div>
                <div class="details"><span><?= htmlspecialchars($product['description']); ?></span></div>
                <div class="flex-btn">
                    <!-- CORRECTED: Using product_id instead of id -->
                    <a href="update_product.php?update=<?= $product['product_id']; ?>" class="option-btn">Update</a>
                    <a href="products.php?delete=<?= $product['product_id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?');">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">No products added yet!</p>
        <?php endif; ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>