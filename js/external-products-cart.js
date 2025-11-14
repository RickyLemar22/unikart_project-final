/**
 * External Products Cart Helper
 * Manages external product cart items in localStorage
 */

class ExternalProductCart {
    constructor() {
        this.cartKey = 'external_cart';
        this.wishlistKey = 'external_wishlist';
        this.init();
    }

    init() {
        // Initialize cart if doesn't exist
        if (!localStorage.getItem(this.cartKey)) {
            localStorage.setItem(this.cartKey, JSON.stringify([]));
        }
        if (!localStorage.getItem(this.wishlistKey)) {
            localStorage.setItem(this.wishlistKey, JSON.stringify([]));
        }
    }

    /**
     * Add item to cart
     */
    addToCart(product) {
        const cart = JSON.parse(localStorage.getItem(this.cartKey) || '[]');
        
        // Check if already in cart
        const existingItem = cart.find(item => item.product_id === product.product_id);
        
        if (existingItem) {
            existingItem.qty = (existingItem.qty || 1) + (product.qty || 1);
        } else {
            cart.push({
                product_id: product.product_id || Date.now(),
                name: product.name,
                price: product.price,
                image: product.image_url,
                source: product.source,
                qty: product.qty || 1,
                from_external: true
            });
        }
        
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
        return cart;
    }

    /**
     * Remove item from cart
     */
    removeFromCart(productId) {
        let cart = JSON.parse(localStorage.getItem(this.cartKey) || '[]');
        cart = cart.filter(item => item.product_id !== productId);
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
        return cart;
    }

    /**
     * Update quantity
     */
    updateQuantity(productId, qty) {
        const cart = JSON.parse(localStorage.getItem(this.cartKey) || '[]');
        const item = cart.find(item => item.product_id === productId);
        
        if (item) {
            item.qty = Math.max(1, qty);
        }
        
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
        return cart;
    }

    /**
     * Get all cart items
     */
    getCart() {
        return JSON.parse(localStorage.getItem(this.cartKey) || '[]');
    }

    /**
     * Get cart total
     */
    getTotal() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + (item.price * item.qty), 0);
    }

    /**
     * Get cart item count
     */
    getItemCount() {
        const cart = this.getCart();
        return cart.reduce((count, item) => count + item.qty, 0);
    }

    /**
     * Clear cart
     */
    clearCart() {
        localStorage.setItem(this.cartKey, JSON.stringify([]));
    }

    /**
     * Add to wishlist
     */
    addToWishlist(product) {
        const wishlist = JSON.parse(localStorage.getItem(this.wishlistKey) || '[]');
        
        // Check if already in wishlist
        if (!wishlist.find(item => item.product_id === product.product_id)) {
            wishlist.push({
                product_id: product.product_id || Date.now(),
                name: product.name,
                price: product.price,
                image: product.image_url,
                source: product.source,
                added_date: new Date().toISOString(),
                from_external: true
            });
        }
        
        localStorage.setItem(this.wishlistKey, JSON.stringify(wishlist));
        return wishlist;
    }

    /**
     * Remove from wishlist
     */
    removeFromWishlist(productId) {
        let wishlist = JSON.parse(localStorage.getItem(this.wishlistKey) || '[]');
        wishlist = wishlist.filter(item => item.product_id !== productId);
        localStorage.setItem(this.wishlistKey, JSON.stringify(wishlist));
        return wishlist;
    }

    /**
     * Get all wishlist items
     */
    getWishlist() {
        return JSON.parse(localStorage.getItem(this.wishlistKey) || '[]');
    }

    /**
     * Check if item in wishlist
     */
    isInWishlist(productId) {
        const wishlist = this.getWishlist();
        return wishlist.some(item => item.product_id === productId);
    }

    /**
     * Clear wishlist
     */
    clearWishlist() {
        localStorage.setItem(this.wishlistKey, JSON.stringify([]));
    }

    /**
     * Export cart data (for checkout)
     */
    exportCartData() {
        return {
            items: this.getCart(),
            total: this.getTotal(),
            count: this.getItemCount(),
            timestamp: new Date().toISOString()
        };
    }

    /**
     * Import cart data from localStorage
     */
    importCartData(data) {
        if (data && data.items && Array.isArray(data.items)) {
            localStorage.setItem(this.cartKey, JSON.stringify(data.items));
            return true;
        }
        return false;
    }
}

// Initialize global cart instance
const externalCart = new ExternalProductCart();

// Helper functions for quick access
function addExternalProduct(product) {
    return externalCart.addToCart(product);
}

function removeExternalProduct(productId) {
    return externalCart.removeFromCart(productId);
}

function getExternalCart() {
    return externalCart.getCart();
}

function getExternalCartTotal() {
    return externalCart.getTotal();
}

function clearExternalCart() {
    return externalCart.clearCart();
}

// Event listener for cart updates
window.addEventListener('storage', function(e) {
    if (e.key === 'external_cart' || e.key === 'external_wishlist') {
        // Emit custom event for other tabs/windows
        window.dispatchEvent(new CustomEvent('externalCartUpdated', {
            detail: {
                cart: externalCart.getCart(),
                wishlist: externalCart.getWishlist()
            }
        }));
    }
});
