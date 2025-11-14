<?php
// components/wishlist_cart.php

if(isset($_POST['add_to_wishlist'])){

   if($user_id == ''){
      header('location:user_login.php');
      exit();
   }else{
      $product_id = $_POST['pid'];
      $product_id = filter_var($product_id, FILTER_SANITIZE_NUMBER_INT);

      // Check if product already exists in wishlist using product_id
      $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE product_id = ? AND user_id = ?");
      $check_wishlist->execute([$product_id, $user_id]);

      // Check if product already exists in cart
      $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE product_id = ? AND user_id = ?");
      $check_cart->execute([$product_id, $user_id]);

      if($check_wishlist->rowCount() > 0){
         $message[] = 'Product already in your favorites!';
      }elseif($check_cart->rowCount() > 0){
         $message[] = 'Product already in cart!';
      }else{
         // Insert into wishlist using correct column names
         $insert_wishlist = $conn->prepare("INSERT INTO `wishlist` (user_id, product_id, quantity) VALUES(?,?,?)");
         $insert_wishlist->execute([$user_id, $product_id, 1]);
         $message[] = 'Product added to favorites!';
      }
   }
}

if(isset($_POST['add_to_cart'])){

   if($user_id == ''){
      header('location:user_login.php');
      exit();
   }else{
      $product_id = $_POST['pid'];
      $product_id = filter_var($product_id, FILTER_SANITIZE_NUMBER_INT);
      $price = $_POST['price'];
      $price = filter_var($price, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      $qty = $_POST['qty'];
      $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);

      // Validate quantity
      if($qty < 1) $qty = 1;
      if($qty > 99) $qty = 99;

      // Check if product already exists in cart using product_id
      $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE product_id = ? AND user_id = ?");
      $check_cart->execute([$product_id, $user_id]);

      if($check_cart->rowCount() > 0){
         $message[] = 'Product already in cart!';
      }else{
         // Remove from wishlist if present
         $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE product_id = ? AND user_id = ?");
         $check_wishlist->execute([$product_id, $user_id]);

         if($check_wishlist->rowCount() > 0){
            $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE product_id = ? AND user_id = ?");
            $delete_wishlist->execute([$product_id, $user_id]);
         }

         $insert_cart = $conn->prepare("INSERT INTO `cart` (user_id, product_id, quantity, price) VALUES(?,?,?,?)");
         $insert_cart->execute([$user_id, $product_id, $qty, $price]);
         $message[] = 'Product added to cart!';
      }
   }
}
?>