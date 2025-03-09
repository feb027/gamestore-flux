<?php
require_once '../includes/config.php';

// Require login for cart access
requireLogin();

$user_id = getCurrentUserId();
$conn = getDbConnection();

// Get cart items with product details
$stmt = $conn->prepare('
    SELECT ci.*, p.title, p.price, p.image_url, p.genre 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.user_id = ?
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate cart totals
$subtotal = 0;
$tax_rate = 0.11; // 11% tax rate
$shipping = 0; // Free shipping for now

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax + $shipping;

// Custom CSS for cart page
$additional_css = '<style>
.cart-page {
    padding: 2rem 0;
}

.cart-header {
    margin-bottom: 2rem;
}

.cart-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3436;
}

.cart-count {
    color: #6c757d;
    font-size: 1.1rem;
}

.cart-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.cart-table thead {
    background: #f8f9fa;
}

.cart-table th {
    font-weight: 600;
    color: #2d3436;
    padding: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.cart-table td {
    padding: 1.5rem 1rem;
    vertical-align: middle;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cart-item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
}

.cart-item-details h5 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.cart-item-details p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-input {
    width: 80px;
    text-align: center;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.5rem;
}

.price {
    font-weight: 600;
    color: #2d3436;
}

.remove-btn {
    color: #dc3545;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: opacity 0.2s;
}

.remove-btn:hover {
    opacity: 0.7;
}

.cart-summary {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.cart-summary h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #2d3436;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.summary-row:last-child {
    border: none;
    margin-bottom: 1.5rem;
}

.summary-label {
    color: #6c757d;
}

.summary-value {
    font-weight: 600;
    color: #2d3436;
}

.total-row {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--bs-primary);
}

.empty-cart {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.empty-cart i {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-cart h2 {
    font-size: 1.5rem;
    color: #2d3436;
    margin-bottom: 1rem;
}

.empty-cart p {
    color: #6c757d;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .cart-item {
        flex-direction: column;
        align-items: flex-start;
        text-align: center;
    }

    .cart-item-image {
        width: 100%;
        height: 150px;
        margin-bottom: 1rem;
    }

    .quantity-control {
        justify-content: center;
        width: 100%;
    }

    .cart-table td {
        padding: 1rem 0.5rem;
    }
}
</style>';

include '../includes/header.php';
?>

<div class="cart-page">
    <div class="container">
        <div class="cart-header">
            <h1 class="cart-title">Shopping Cart</h1>
            <?php if (!empty($cart_items)): ?>
                <p class="cart-count"><?php echo count($cart_items); ?> item(s) in your cart</p>
            <?php endif; ?>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any games to your cart yet.</p>
                <a href="<?php echo BASE_URL; ?>/pages/products.php" class="btn btn-primary">
                    <i class="bi bi-controller me-2"></i>Browse Games
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="table-responsive cart-table">
                        <table class="table table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th width="50%">Game</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="cart-item">
                                                <img src="<?php echo BASE_URL . ($item['image_url'] ?? '/images/games/placeholder.jpg'); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                     class="cart-item-image">
                                                <div class="cart-item-details">
                                                    <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                                    <p><?php echo htmlspecialchars($item['genre']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="price"><?php echo formatIDR($item['price']); ?></td>
                                        <td>
                                            <form action="../cart/update.php" method="POST" class="quantity-control">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                       min="1" class="form-control quantity-input"
                                                       onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="price"><?php echo formatIDR($item['price'] * $item['quantity']); ?></td>
                                        <td>
                                            <form action="../cart/remove.php" method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <button type="submit" class="remove-btn">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value"><?php echo formatIDR($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tax (11%)</span>
                            <span class="summary-value"><?php echo formatIDR($tax); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Shipping</span>
                            <span class="summary-value">Free</span>
                        </div>
                        <div class="summary-row total-row">
                            <span class="summary-label">Total</span>
                            <span class="summary-value"><?php echo formatIDR($total); ?></span>
                        </div>
                        <a href="../pages/checkout.php" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?> 