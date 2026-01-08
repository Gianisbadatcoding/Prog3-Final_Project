<?php
$page_title = "Point of Sale";
require_once 'auth.php';
requireLogin();
require_once 'includes/header.php';
?>

<style>
    .pos-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        height: calc(100vh - 140px);
    }
    
    .pos-left {
        display: flex;
        flex-direction: column;
        gap: 20px;
        height: 100%;
    }
    
    .pos-right {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        height: 100%;
        position: sticky;
        top: 20px;
        border: 1px solid #e2e8f0;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
        overflow-y: auto;
        padding-right: 5px;
        padding-bottom: 20px;
    }

    .product-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: var(--color-primary);
    }
    
    .product-card.out-of-stock {
        opacity: 0.6;
        cursor: not-allowed;
        background: #f8fafc;
    }

    .pos-thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .product-price {
        font-weight: bold;
        color: var(--color-primary-dark);
        font-size: 1.1em;
    }

    .product-stock {
        font-size: 0.8em;
        color: #64748b;
    }

    /* Cart Styles */
    .cart-header {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 12px 12px 0 0;
    }

    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .cart-item-info {
        flex: 1;
    }
    
    .cart-item-title {
        font-weight: 600;
        font-size: 0.95em;
        display: block;
    }
    
    .cart-item-price {
        font-size: 0.85em;
        color: #64748b;
    }

    .cart-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-btn {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 1px solid #cbd5e1;
        background: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    
    .qty-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    
    .cart-qty {
        font-weight: 600;
        width: 20px;
        text-align: center;
    }

    .cart-footer {
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 0 0 12px 12px;
    }

    .cart-total {
        display: flex;
        justify-content: space-between;
        font-size: 1.25em;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .btn-checkout {
        width: 100%;
        padding: 12px;
        font-size: 1.1em;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    
    /* Search Bar in POS */
    .pos-search {
        display: flex;
        gap: 10px;
    }
    
    .pos-search input {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
    }
    
    .category-pills {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 5px;
    }
    
    .category-pill {
        padding: 6px 14px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 0.9em;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }
    
    .category-pill:hover, .category-pill.active {
        background: var(--color-primary);
        color: white;
        border-color: var(--color-primary);
    }
</style>

<main class="main-content" style="padding-top: 10px;">
    <div class="pos-container">
        <!-- Left Side: Product Grid -->
        <div class="pos-left">
            <div class="search-section" style="margin-bottom: 0;">
                <div class="pos-search">
                    <input type="text" id="searchInput" placeholder="Search products or scan barcode..." autofocus>
                </div>
                <div class="category-pills" id="categoryFilters" style="margin-top: 15px;">
                    <button class="category-pill active" data-cat="">All Items</button>
                    <?php 
                    $categories = ['Beverages', 'Snacks', 'Canned Goods', 'Household', 'Personal Care', 'Others'];
                    foreach ($categories as $cat) {
                        echo "<button class='category-pill' data-cat='$cat'>$cat</button>";
                    }
                    ?>
                </div>
            </div>
            
            <div id="productGrid" class="product-grid">
                <!-- Products will be loaded here via JS -->
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading products...
                </div>
            </div>
        </div>

        <!-- Right Side: Cart -->
        <div class="pos-right">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-cart"></i> Current Order</h3>
            </div>
            
            <div class="cart-items" id="cartItems">
                <div style="text-align: center; padding: 40px; color: #94a3b8; font-style: italic;">
                    Cart is empty
                </div>
            </div>
            
            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total</span>
                    <span id="cartTotal">&#8369;0.00</span>
                </div>
                <button class="btn btn-primary btn-checkout" id="checkoutBtn" onclick="processCheckout()" disabled>
                    <i class="fas fa-receipt"></i> Checkout
                </button>
            </div>
        </div>
    </div>
</main>

<script>
    let products = [];
    let cart = [];
    let currentCategory = '';
    let searchQuery = '';

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        fetchProducts();
        
        // Search Listener
        document.getElementById('searchInput').addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase();
            renderProducts();
        });

        // Category Filter Listener
        document.querySelectorAll('.category-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.category-pill').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentCategory = btn.dataset.cat;
                renderProducts();
            });
        });
    });

    async function fetchProducts() {
        try {
            const response = await fetch('api/get_products.php');
            products = await response.json();
            renderProducts();
        } catch (error) {
            console.error('Error fetching products:', error);
            document.getElementById('productGrid').innerHTML = '<div style="color: red; padding: 20px;">Error loading products. Check console.</div>';
        }
    }

    function renderProducts() {
        const grid = document.getElementById('productGrid');
        grid.innerHTML = '';
        
        const filtered = products.filter(p => {
            const matchesSearch = p.item_name.toLowerCase().includes(searchQuery) || p.item_id.toString() === searchQuery;
            const matchesCategory = currentCategory === '' || p.category === currentCategory;
            return matchesSearch && matchesCategory;
        });

        if (filtered.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #64748b;">No products found</div>';
            return;
        }

        filtered.forEach(p => {
            const card = document.createElement('div');
            card.className = `product-card ${p.quantity <= 0 ? 'out-of-stock' : ''}`;
            if (p.quantity > 0) {
                card.onclick = () => addToCart(p);
            }

            const img = p.image_path ? p.image_path : 'https://via.placeholder.com/80?text=No+Img';
            
            card.innerHTML = `
                <img src="${img}" class="pos-thumb" alt="${p.item_name}" onerror="this.src='https://via.placeholder.com/80?text=No+Img'">
                <div class="product-title" style="font-weight: 600; margin-bottom: 5px;">${p.item_name}</div>
                <div class="product-price">&#8369;${parseFloat(p.price).toFixed(2)}</div>
                <div class="product-stock">${p.quantity} in stock</div>
            `;
            grid.appendChild(card);
        });
    }

    function addToCart(product) {
        // Check if already in cart
        const existing = cart.find(item => item.id === product.item_id);
        
        if (existing) {
            if (existing.qty < product.quantity) {
                existing.qty++;
            } else {
                alert('Max stock reached!');
            }
        } else {
            cart.push({
                id: product.item_id,
                name: product.item_name,
                price: parseFloat(product.price),
                qty: 1,
                max: product.quantity
            });
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        renderCart();
    }

    function updateQty(id, change) {
        const item = cart.find(i => i.id === id);
        if (item) {
            const newQty = item.qty + change;
            if (newQty > 0 && newQty <= item.max) {
                item.qty = newQty;
            } else if (newQty <= 0) {
                removeFromCart(id);
                return; // renderCart called in removeFromCart
            } else {
                alert('Max available stock reached (' + item.max + ')');
            }
        }
        renderCart();
    }

    function setQty(id, value) {
        const newQty = parseInt(value);
        const item = cart.find(i => i.id === id);
        
        if (item) {
            if (newQty > 0 && newQty <= item.max) {
                item.qty = newQty;
            } else {
                alert('Invalid quantity. Max stock: ' + item.max);
            }
        }
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const totalEl = document.getElementById('cartTotal');
        const checkoutBtn = document.getElementById('checkoutBtn');
        
        if (cart.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #94a3b8; font-style: italic;">Cart is empty</div>';
            totalEl.innerHTML = '&#8369;0.00';
            checkoutBtn.disabled = true;
            return;
        }

        container.innerHTML = '';
        let total = 0;

        cart.forEach(item => {
            total += item.price * item.qty;
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <div class="cart-item-info">
                    <span class="cart-item-title">${item.name}</span>
                    <span class="cart-item-price">&#8369;${item.price.toFixed(2)} x ${item.qty}</span>
                </div>
                <div class="cart-controls">
                    <button class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                    <input type="number" class="cart-qty-input" value="${item.qty}" min="1" max="${item.max}" 
                           onchange="setQty(${item.id}, this.value)" style="width: 50px; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px;">
                    <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                    <button class="qty-btn" onclick="removeFromCart(${item.id})" style="border-color: #ef4444; color: #ef4444; margin-left: 5px;"><i class="fas fa-trash"></i></button>
                </div>
            `;
            container.appendChild(div);
        });

        totalEl.innerHTML = '&#8369;' + total.toFixed(2);
        checkoutBtn.disabled = false;
    }

    async function processCheckout() {
        if (!confirm('Process transaction?')) return;

        const btn = document.getElementById('checkoutBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            const response = await fetch('api/process_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ items: cart })
            });

            const result = await response.json();

            if (result.success) {
                alert('Transaction Successful!');
                cart = [];
                renderCart();
                fetchProducts(); // Refresh stock
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('System Error. Please try again.');
            console.error(error);
        } finally {
            btn.disabled = cart.length === 0;
            btn.innerHTML = '<i class="fas fa-receipt"></i> Checkout';
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>
