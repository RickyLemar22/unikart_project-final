// script.js - Combined JavaScript for all pages

// Common utility functions
const UniKart = {
    // Show loading state on buttons
    showLoading: function(button, text = 'Loading...') {
        button.setAttribute('data-original-html', button.innerHTML);
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
        button.disabled = true;
    },

    // Hide loading state on buttons
    hideLoading: function(button) {
        const originalHTML = button.getAttribute('data-original-html');
        if (originalHTML) {
            button.innerHTML = originalHTML;
            button.disabled = false;
            button.removeAttribute('data-original-html');
        }
    },

    // Auto-hide messages
    autoHideMessages: function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(message => {
            setTimeout(() => {
                if(message.parentNode) {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if(message.parentNode) {
                            message.remove();
                        }
                    }, 500);
                }
            }, 5000);
        });
    },

    // Initialize quantity inputs
    initQuantityInputs: function() {
        document.querySelectorAll('.modern-qty').forEach(input => {
            input.addEventListener('change', function() {
                const value = parseInt(this.value);
                if(value < 1) this.value = 1;
                if(value > 99) this.value = 99;
                if(isNaN(value)) this.value = 1;
            });
        });
    }
};

// Home page specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize common utilities
    UniKart.autoHideMessages();
    UniKart.initQuantityInputs();

    // Home page specific elements
    const addToCartButtons = document.querySelectorAll('button[name="add_to_cart"]');
    const addToWishlistButtons = document.querySelectorAll('button[name="add_to_wishlist"]:not(.favorited)');
    const productCards = document.querySelectorAll('.modern-product-card');
    const categoryItems = document.querySelectorAll('.modern-category-item');

    // Add to Cart button functionality
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            UniKart.showLoading(this, 'Adding...');
            
            // Auto-recover if still disabled after 5 seconds
            setTimeout(() => {
                if(this.disabled) {
                    UniKart.hideLoading(this);
                }
            }, 5000);
        });
    });
    
    // Add to Wishlist button functionality
    addToWishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            UniKart.showLoading(this, '');
            
            // Auto-recover if still disabled after 5 seconds
            setTimeout(() => {
                if(this.disabled) {
                    UniKart.hideLoading(this);
                }
            }, 5000);
        });
    });
    
    // Product card hover effects
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Category filter animations
    categoryItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all items
            categoryItems.forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
        });
    });

    // Header functionality (if exists)
    const header = document.querySelector('.uniform-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.style.background = 'linear-gradient(135deg, #5568d3 0%, #6a489c 100%)';
                header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.15)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            }
        });
    }

    // Dropdown functionality (if exists)
    const dropdownBtns = document.querySelectorAll('.user-dropdown-btn');
    dropdownBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.user-dropdown');
            
            // Toggle current dropdown
            dropdown.classList.toggle('active');
            
            // Close all other dropdowns
            document.querySelectorAll('.user-dropdown').forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('active');
                }
            });
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-dropdown')) {
            document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    // Close dropdown when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
});

// Global error handler
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
});
// Header functionality
function initializeHeader() {
    // Add scroll effect for header
    const header = document.querySelector('.uniform-header');
    
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.style.background = 'linear-gradient(135deg, #5568d3 0%, #6a489c 100%)';
                header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.15)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            }
        });
    }

    // Dropdown functionality
    const dropdownBtns = document.querySelectorAll('.user-dropdown-btn');
    
    dropdownBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.user-dropdown');
            
            // Close wishlist and cart dropdowns first
            const wcDropdowns = document.querySelectorAll('.wc-dropdown');
            const wcOverlays = document.querySelectorAll('.wc-overlay');
            
            wcDropdowns.forEach(wcDropdown => {
                wcDropdown.classList.remove('active');
            });
            wcOverlays.forEach(overlay => {
                overlay.classList.remove('active');
            });
            document.body.style.overflow = '';
            
            // Toggle current dropdown
            dropdown.classList.toggle('active');
            
            // Close all other dropdowns
            document.querySelectorAll('.user-dropdown').forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('active');
                }
            });
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-dropdown')) {
            document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
        
        // Also close on outside click for mobile
        if (window.innerWidth <= 768) {
            if (!e.target.closest('.user-dropdown-menu') && !e.target.closest('.user-dropdown-btn')) {
                document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        }
    });

    // Close dropdown when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            
            // Also close wishlist/cart dropdowns
            document.querySelectorAll('.wc-dropdown').forEach(wcDropdown => {
                wcDropdown.classList.remove('active');
            });
            document.querySelectorAll('.wc-overlay').forEach(overlay => {
                overlay.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
    });

    // Prevent dropdown close when clicking inside dropdown content
    document.querySelectorAll('.user-dropdown-menu').forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Mobile menu handling
    function handleMobileResize() {
        const dropdowns = document.querySelectorAll('.user-dropdown-menu');
        
        if (window.innerWidth <= 768) {
            dropdowns.forEach(menu => {
                menu.style.position = 'fixed';
                menu.style.top = '70px';
                menu.style.left = '50%';
                menu.style.transform = 'translateX(-50%) translateY(-10px)';
            });
        } else {
            dropdowns.forEach(menu => {
                menu.style.position = 'absolute';
                menu.style.top = '100%';
                menu.style.left = 'auto';
                menu.style.right = '0';
                menu.style.transform = 'translateY(-10px)';
            });
        }
    }

    // Initial call and resize listener
    handleMobileResize();
    window.addEventListener('resize', handleMobileResize);
}

// Initialize header when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeHeader();
    
    // Also initialize wishlist cart component if function exists
    if (typeof initializeWishlistCartComponent === 'function') {
        initializeWishlistCartComponent();
    }
});

// Message close functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('fa-times') && e.target.parentElement.classList.contains('message')) {
        e.target.parentElement.remove();
    }
});

// Add loading state to forms in header
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.closest('.uniform-search') || form.closest('.uniform-nav')) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        }
    }
});

/* ===== SESSION TIMEOUT WARNING ===== */
// Warn user before session timeout (55 minutes)
let sessionWarningShown = false;
function checkSessionTimeout() {
    // Check every minute
    setInterval(function() {
        fetch('components/session_check.php')
            .then(response => response.json())
            .then(data => {
                if(data.time_remaining && data.time_remaining < 300 && !sessionWarningShown) {
                    // Less than 5 minutes remaining
                    sessionWarningShown = true;
                    if(confirm('Your session will expire in ' + Math.floor(data.time_remaining / 60) + ' minutes due to inactivity. Click OK to stay logged in.')) {
                        // Refresh session by making a request
                        fetch('components/refresh_session.php');
                        sessionWarningShown = false;
                    }
                }
            })
            .catch(err => console.log('Session check failed'));
    }, 60000); // Check every minute
}

// Start session check if user is logged in
if(document.querySelector('.user-dropdown')) {
    checkSessionTimeout();
}

// ===== WISHLIST HEART ICON INTERACTIVE FEEDBACK =====
document.addEventListener('DOMContentLoaded', function() {
    // Handle wishlist button clicks with visual feedback
    const wishlistButtons = document.querySelectorAll('button[name="add_to_wishlist"]');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!this.disabled) {
                // Add visual feedback
                const heartIcon = this.querySelector('i');
                
                // Change from outline to solid heart
                if (heartIcon) {
                    heartIcon.classList.remove('far');
                    heartIcon.classList.add('fas');
                }
                
                // Add favorited class for background color
                this.classList.add('favorited');
                
                // Add heartbeat animation
                this.style.animation = 'heartBeat 0.5s ease';
                
                // Show loading state briefly
                const originalHTML = this.innerHTML;
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }, 100);
                
                // Update header wishlist count if element exists
                setTimeout(() => {
                    const wishlistBadge = document.querySelector('.wishlist-icon .count-badge');
                    if (wishlistBadge) {
                        let currentCount = parseInt(wishlistBadge.textContent) || 0;
                        currentCount++;
                        wishlistBadge.textContent = currentCount;
                        wishlistBadge.style.animation = 'heartPulse 0.3s ease';
                    } else {
                        // Create badge if it doesn't exist
                        const wishlistIcon = document.querySelector('.wishlist-icon');
                        if (wishlistIcon && !wishlistIcon.querySelector('.count-badge')) {
                            const badge = document.createElement('span');
                            badge.className = 'count-badge';
                            badge.textContent = '1';
                            badge.style.animation = 'heartPulse 0.3s ease';
                            wishlistIcon.appendChild(badge);
                        }
                    }
                }, 200);
            }
        });
    });
    
    // Add hover effect for wishlist buttons
    wishlistButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (!this.disabled && !this.classList.contains('favorited')) {
                const heartIcon = this.querySelector('i');
                if (heartIcon && heartIcon.classList.contains('far')) {
                    heartIcon.style.transform = 'scale(1.2)';
                    heartIcon.style.transition = 'transform 0.2s ease';
                }
            }
        });
        
        button.addEventListener('mouseleave', function() {
            const heartIcon = this.querySelector('i');
            if (heartIcon) {
                heartIcon.style.transform = 'scale(1)';
            }
        });
    });
    
    // Add click effect for add to cart buttons
    const cartButtons = document.querySelectorAll('button[name="add_to_cart"]');
    cartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!this.disabled) {
                // Add visual feedback
                this.style.animation = 'heartPulse 0.3s ease';
                
                // Show loading state
                const originalHTML = this.innerHTML;
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                }, 50);
                
                // Update cart count if element exists
                setTimeout(() => {
                    const cartBadge = document.querySelector('.cart-icon .count-badge');
                    if (cartBadge) {
                        let currentCount = parseInt(cartBadge.textContent) || 0;
                        currentCount++;
                        cartBadge.textContent = currentCount;
                        cartBadge.style.animation = 'heartPulse 0.3s ease';
                    }
                }, 200);
            }
        });
    });
});

