<footer class="footer">
   <section class="grid">

      <div class="box">
         <h3>Quick Links</h3>
         <a href="shop.php"><i class="fas fa-store"></i> Shop</a>
         <a href="about.php"><i class="fas fa-info-circle"></i> About Us</a>
         <a href="contact.php"><i class="fas fa-envelope"></i> Customer Support</a>
      </div>

      <div class="box">
         <h3>Contact Info</h3>
         <div class="contact-info">
            <a href="mailto:unikart@gmail.com" class="contact-link">
               <i class="fas fa-envelope"></i> unikart@gmail.com
            </a>
            <a href="tel:+256708000000" class="contact-link">
               <i class="fas fa-phone"></i> +256 708 000 000
            </a>
            <a href="https://wa.me/256708000000" class="contact-link">
               <i class="fab fa-whatsapp"></i> +256 708 000 000
            </a>
            <div class="address">
               <i class="fas fa-map-marker-alt"></i> 
               <span> Mbarara,Uganda</span>
            </div>
         </div>
      </div>

      <div class="box">
         <h3>Follow Us</h3>
         <div class="social-links">
            <a href="#" target="_blank" class="social-link">
               <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="#" target="_blank" class="social-link">
               <i class="fab fa-x-twitter"></i> X
            </a>
            <a href="#" target="_blank" class="social-link">
               <i class="fab fa-instagram"></i> Instagram
            </a>
            <a href="#" target="_blank" class="social-link">
               <i class="fab fa-tiktok"></i> TikTok
            </a>
         </div>
         
         <div class="newsletter">
            <h4>Stay Updated By Subscribing To Our NewsLetter</h4>
            <form class="newsletter-form">
               <input type="email" placeholder="Enter your email" required>
               <button type="submit">Subscribe</button>
            </form>
         </div>
      </div>

   </section>

   <div class="footer-bottom">
      <div class="credit">&copy; <?= date('Y'); ?> <span>UniKart</span> | All rights reserved</div>
      <div class="payment-methods">
         <span>We accept:</span>
         <i class="fas fa-money-bill-wave" title="Cash On Delivery"></i>
         <i class="fas fa-mobile-alt" title="Mobile Money"></i>
      </div>
   </div>
</footer>

<script src="./js/unikart-api.js"></script>