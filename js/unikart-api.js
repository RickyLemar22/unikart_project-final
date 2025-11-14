class UniKartAPI {
    constructor(apiKey = 'unikart_mobile_2024') {
        this.baseURL = '/api'; // Relative path to your API
        this.apiKey = apiKey;
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        const config = {
            headers: {
                'X-API-Key': this.apiKey,
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    // 🔑 AUTHENTICATION
    async login(emailOrPhone, password) {
        return this.request('/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({
                login: emailOrPhone,
                password: password
            })
        });
    }
    
    async register(userData) {
        return this.request('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }
    
    // 🛍️ PRODUCTS
    async getProducts(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.request(`/products/?${params}`);
    }
    
    async getProduct(productId) {
        return this.request(`/products/?product_id=${productId}`);
    }
    
    // 🛒 ORDERS
    async createOrder(orderData) {
        return this.request('/orders/', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }
    
    async getOrders(userId) {
        return this.request(`/orders/?user_id=${userId}`);
    }
    
    // 💳 PAYMENT
    async initiatePayment(paymentData) {
        return this.request('/payment/initiate.php', {
            method: 'POST',
            body: JSON.stringify(paymentData)
        });
    }
    
    async verifyPayment(transactionId) {
        return this.request('/payment/verify.php', {
            method: 'POST',
            body: JSON.stringify({ transaction_id: transactionId })
        });
    }
    
    // 🔔 NOTIFICATIONS
    async getNotifications(userId) {
        return this.request(`/notifications/?user_id=${userId}`);
    }
}

// Create global instance
window.unikartAPI = new UniKartAPI();