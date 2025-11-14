"use strict";

/* ================= Navbar & Profile Toggle ================= */
const menuBtn = document.querySelector('#menu-btn');
const userBtn = document.querySelector('#user-btn');
const navbar = document.querySelector('.header .flex .navbar');
const profile = document.querySelector('.header .flex .profile');

const closeAllMenus = () => {
    navbar.classList.remove('active');
    profile.classList.remove('active');
};

if (menuBtn) {
    menuBtn.addEventListener('click', () => {
        navbar.classList.toggle('active');
        profile.classList.remove('active');
    });
}

if (userBtn) {
    userBtn.addEventListener('click', () => {
        profile.classList.toggle('active');
        navbar.classList.remove('active');
    });
}

// Close menus on scroll
window.addEventListener('scroll', closeAllMenus);

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    if (!navbar.contains(e.target) && !menuBtn.contains(e.target) && navbar.classList.contains('active')) {
        navbar.classList.remove('active');
    }
});

/* ================= Product Image Preview ================= */
const updateImagePreview = (inputSelector, mainImgSelector) => {
    const imageInput = document.querySelector(inputSelector);
    const mainImage = document.querySelector(mainImgSelector);

    if (imageInput && mainImage) {
        imageInput.addEventListener('change', () => {
            const file = imageInput.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                mainImage.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }
};

// Initialize image previews for multiple product image fields
updateImagePreview('.update-product input[name="image_01"]', '.update-product .image-container .main-image img');
;

/* ================= Optional: Smooth hover effect for cards ================= */
const cards = document.querySelectorAll('.box-container .box');
cards.forEach(box => {
    box.addEventListener('mouseenter', () => box.style.transform = 'translateY(-5px)');
    box.addEventListener('mouseleave', () => box.style.transform = 'translateY(0)');
});

// Logout Modal Functions - Made globally accessible
window.openLogoutModal = function() {
    const modal = document.getElementById('logoutModal');
    if(modal) {
        modal.style.display = 'flex';
    }
}

window.closeLogoutModal = function() {
    const modal = document.getElementById('logoutModal');
    if(modal) {
        modal.style.display = 'none';
    }
}

// Mobile Menu Toggle
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    const navbar = document.getElementById('navbar');
    navbar.classList.toggle('active');
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeLogoutModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLogoutModal();
    }
});

// Auto-remove notifications after 5 seconds
setTimeout(() => {
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        if(notification.parentNode) {
            notification.remove();
        }
    });
}, 5000);

/* ================= ADDITIONAL ADMIN FEATURES ================= */

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            if(message.parentNode) {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => {
                    if(message.parentNode) {
                        message.remove();
                    }
                }, 500);
            }
        }, 5000);
    });
    
    // Add loading state to delete buttons
    const deleteButtons = document.querySelectorAll('a[href*="delete"]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
                return false;
            }
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        });
    });
    
    // Add loading state to form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('input[type="submit"], button[type="submit"]');
            if(submitBtn) {
                const originalText = submitBtn.value || submitBtn.innerHTML;
                if(submitBtn.tagName === 'INPUT') {
                    submitBtn.value = 'Processing...';
                } else {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                }
                submitBtn.disabled = true;
            }
        });
    });
});