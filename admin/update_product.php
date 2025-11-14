<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if(!$admin_id){
    header('location:admin_login.php');
    exit;
}

$categories = [
    'computing' => 'Computing & Accessories',
    'smartphones' => 'Smartphones, Tablets & Accessories',
    'appliances' => 'Hostel Electrical Appliances',
    'bedding' => 'Bedding & Interiors',
    'scholastic' => 'Scholastic Materials',
    'groceries' => 'Drinks, Foods & Groceries',
    'personal' => 'Personal Care & Beauty',
    'clothing' => 'Clothing & Fashion',
    'kitchenware' => 'Kitchenware',
    'sports' => 'Sports & Fitness'
];

if(isset($_POST['update'])){
    $pid = filter_var($_POST['pid'], FILTER_SANITIZE_NUMBER_INT);
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
    $category = htmlspecialchars($_POST['category'], ENT_QUOTES, 'UTF-8');

    // Update product details
    $update_product = $conn->prepare("UPDATE `products` SET name = ?, price = ?, description = ?, category = ? WHERE product_id = ?");
    $update_product->execute([$name, $price, $description, $category, $pid]);
    $message[] = 'Product updated successfully!';

    // Handle image update
    $old_image = $_POST['old_image'];
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0 && !empty($_FILES['image']['name'])){
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $image_name = $_FILES['image']['name'];
        $image_size = $_FILES['image']['size'];
        $image_tmp = $_FILES['image']['tmp_name'];
        
        // Get file extension
        $file_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        // Validate extension
        if(!in_array($file_extension, $allowed_extensions)){
            $message[] = 'Invalid file type! Only JPG, PNG, and WEBP are allowed.';
        } elseif($image_size > 2000000){
            $message[] = 'Image size is too large! Maximum 2MB allowed.';
        } else {
            // Generate unique filename to prevent overwrites
            $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
            $image_folder = '../uploaded_img/' . $unique_name;
            
            if(move_uploaded_file($image_tmp, $image_folder)){
                // Update database with new image
                $update_image = $conn->prepare("UPDATE `products` SET image_url = ? WHERE product_id = ?");
                $update_image->execute([$unique_name, $pid]);
                
                // Delete old image if it exists
                if(!empty($old_image) && file_exists('../uploaded_img/' . $old_image)){
                    unlink('../uploaded_img/' . $old_image);
                }
                
                $message[] = 'Product image updated successfully!';
            } else {
                $message[] = 'Failed to upload image!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unikart | Update Product</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="update-product">

    <h1 class="heading">Update Product</h1>

    <?php
    if(isset($message)){
        foreach($message as $msg){
            echo '<div class="message"><span>'.htmlspecialchars($msg).'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
        }
    }

    $update_id = $_GET['update'] ?? null;
    if($update_id){
        $select_product = $conn->prepare("SELECT * FROM `products` WHERE product_id = ?");
        $select_product->execute([$update_id]);
        if($select_product->rowCount() > 0){
            $product = $select_product->fetch(PDO::FETCH_ASSOC);
    ?>

    <form action="" method="post" enctype="multipart/form-data" class="add-product-form">
        <input type="hidden" name="pid" value="<?= $product['product_id']; ?>">
        <input type="hidden" name="old_image" value="<?= htmlspecialchars($product['image_url']); ?>">

        <div class="image-preview">
            <?php if(!empty($product['image_url']) && file_exists('../uploaded_img/' . $product['image_url'])): ?>
                <img src="../uploaded_img/<?= htmlspecialchars($product['image_url']); ?>" alt="Product Image">
            <?php else: ?>
                <div class="no-image">
                    <i class="fas fa-image"></i>
                    <p>No image available</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="inputBox">
            <label>Product Name <span style="color: red;">*</span></label>
            <input type="text" name="name" required maxlength="100" placeholder="Enter product name" value="<?= htmlspecialchars($product['name']); ?>" class="box">
        </div>

        <div class="inputBox">
            <label>Product Price (UGX) <span style="color: red;">*</span></label>
            <input type="number" name="price" required min="0" step="0.01" max="9999999999" placeholder="Enter price" value="<?= htmlspecialchars($product['price']); ?>" class="box">
        </div>

        <div class="inputBox">
            <label>Product Description <span style="color: red;">*</span></label>
            <textarea name="description" required cols="30" rows="10" placeholder="Enter product description" class="box"><?= htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="inputBox">
            <label>Category <span style="color: red;">*</span></label>
            <select name="category" required class="box">
                <option value="" disabled>Select category</option>
                <?php
                foreach($categories as $key => $label){
                    $selected = ($product['category'] === $key) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($key) . "' $selected>" . htmlspecialchars($label) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="inputBox">
            <label>Update Image (Optional)</label>
            <input type="file" name="image" accept="image/jpg,image/jpeg,image/png,image/webp" class="box">
            <small style="color: #666; display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i> Max size: 2MB | Formats: JPG, PNG, WEBP
            </small>
        </div>

        <div class="flex-btn">
            <input type="submit" name="update" value="Update Product" class="btn">
            <a href="products.php" class="option-btn">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
    </form>

    <?php
        } else {
            echo '<p class="empty"><i class="fas fa-exclamation-triangle"></i> No product found!</p>';
        }
    } else {
        echo '<p class="empty"><i class="fas fa-exclamation-triangle"></i> Invalid product ID!</p>';
    }
    ?>

</section>

<script src="../js/admin_script.js"></script>
</body>
</html>