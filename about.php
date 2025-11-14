<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About Us - UniKart</title>

   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'components/guest_user_header.php'; ?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="hero-content">
        <h1>About UniKart</h1>
        <p>Your trusted campus marketplace for quality products and unbeatable deals</p>
    </div>
</section>

<!-- About Content -->
<section class="modern-about">
    <div class="about-content">
        <div class="about-image">
            <img src="images/about-img.svg" alt="About UniKart">
        </div>
        
        <div class="about-text">
            <h2>Why Choose UniKart?</h2>
            <p>We are the premier online marketplace dedicated to serving the university community. Our mission is to make campus life easier by providing access to quality products at student-friendly prices.</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Fast Delivery</h3>
                    <p>Quick delivery to campus pickup stations within 24 hours</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <h3>Best Prices</h3>
                    <p>Student-exclusive discounts and competitive pricing</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Quality Assured</h3>
                    <p>All products verified for quality and authenticity</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Dedicated customer support for all your needs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section class="modern-reviews">
    <div class="reviews-header">
        <h2>What Students Say About Us</h2>
    </div>

    <div class="swiper reviews-slider">
        <div class="swiper-wrapper">
            <div class="swiper-slide modern-slide">
                <img src="images/img-3.jpg" alt="Aramanya Lucky">
                <p>"I ordered a dress for a special occasion and it exceeded my expectations. The quality was amazing and delivery was super fast!"</p>
                <div class="modern-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <h4>Aramanya Lucky</h4>
                <small>Computer Science Student</small>
            </div>

            <div class="swiper-slide modern-slide">
                <img src="images/img-1.jpg" alt="Mukundane Medard">
                <p>"The inventory management has improved significantly. Now I always get what I order, and the customer service is very responsive."</p>
                <div class="modern-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <h4>Mukundane Medard</h4>
                <small>Engineering Student</small>
            </div>

            <div class="swiper-slide modern-slide">
                <img src="images/img-2.jpg" alt="Amurinzire Daniel">
                <p>"I recently purchased a laptop and I was extremely satisfied with the services. The delivery was fast and the product was exactly as described."</p>
                <div class="modern-stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <h4>Amurinzire Daniel</h4>
                <small>Medical Student</small>
            </div>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</section>

<!-- CTA Section -->
<section class="modern-about">
    <div class="cta-section">
        <h2>Ready to Experience UniKart?</h2>
        <p>Join thousands of students who trust us for their campus shopping needs</p>
        <a href="shop.php" class="cta-btn">
            <i class="fas fa-shopping-bag"></i> Start Shopping Now
        </a>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
<script src="js/script.js"></script>

<script>
var swiper = new Swiper(".reviews-slider", {
   loop: true,
   spaceBetween: 30,
   pagination: {
      el: ".swiper-pagination",
      clickable: true,
   },
   autoplay: {
      delay: 5000,
      disableOnInteraction: false,
   },
   breakpoints: {
      0: {
        slidesPerView: 1,
      },
      768: {
        slidesPerView: 2,
      },
      991: {
        slidesPerView: 3,
      },
   },
});

// Add animation to stats
document.addEventListener('DOMContentLoaded', function() {
    const statItems = document.querySelectorAll('.stat-item h3');
    statItems.forEach(stat => {
        const target = parseInt(stat.textContent);
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            stat.textContent = Math.floor(current) + (stat.textContent.includes('%') ? '%' : '+');
        }, 50);
    });
});
</script>

</body>
</html>