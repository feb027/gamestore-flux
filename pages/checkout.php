<?php
require_once '../includes/config.php';

// Require login for checkout
requireLogin();

// Get cart items from database
$conn = getDbConnection();
$user_id = getCurrentUserId();
$cart_query = "SELECT ci.*, p.title, p.image_url, p.price 
               FROM cart_items ci 
               JOIN products p ON ci.product_id = p.id 
               WHERE ci.user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Redirect if cart is empty
if (empty($cart_items)) {
    setFlashMessage('warning', 'Your cart is empty');
    redirect('/pages/products.php');
}

// Calculate totals
$subtotal = 0;
$tax_rate = 0.11; // 11% tax rate

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $errors = [];
    $required_fields = ['name', 'email', 'phone'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Create order in database
            $order_query = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'completed')";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bind_param('id', $user_id, $total);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            $order_stmt->close();

            // 2. Add order items
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
            $item_stmt = $conn->prepare($item_query);
            
            foreach ($cart_items as $item) {
                $item_stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
            }
            
            $item_stmt->close();

            // 3. Clear cart
            $clear_cart_query = "DELETE FROM cart_items WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_cart_query);
            $clear_stmt->bind_param('i', $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();

            // Commit transaction
            $conn->commit();

            // 4. Set success message and redirect
            setFlashMessage('success', 'Order placed successfully! In a real system, you would receive an email with your game keys and download instructions.');
            redirect('/pages/order-success.php');
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            setFlashMessage('danger', 'An error occurred while processing your order. Please try again.');
            redirect('/pages/checkout.php');
        }
    }
}

// Custom CSS for checkout page
$additional_css = '<style>
.checkout-page {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.checkout-header {
    margin-bottom: 2rem;
}

.checkout-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3436;
    margin-bottom: 0.5rem;
}

.checkout-subtitle {
    color: #6c757d;
    font-size: 1.1rem;
}

.digital-notice {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.digital-notice i {
    font-size: 1.5rem;
    color: var(--bs-primary);
}

.digital-notice-text {
    margin: 0;
    color: #2d3436;
}

.checkout-form {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2d3436;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.form-floating {
    margin-bottom: 1rem;
}

.form-floating > label {
    color: #6c757d;
}

.form-control:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.order-summary {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 2rem;
}

.order-summary-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.order-summary-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2d3436;
    margin: 0;
}

.order-summary-body {
    padding: 1.5rem;
}

.cart-item-mini {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.cart-item-mini:last-child {
    border-bottom: none;
}

.cart-item-mini-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item-mini-details {
    flex-grow: 1;
}

.cart-item-mini-title {
    font-weight: 600;
    color: #2d3436;
    margin: 0 0 0.25rem 0;
    font-size: 0.95rem;
}

.cart-item-mini-meta {
    color: #6c757d;
    font-size: 0.85rem;
}

.cart-item-mini-price {
    font-weight: 600;
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
    margin-bottom: 0;
    padding-bottom: 0;
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
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #e9ecef;
}

.place-order-btn {
    margin-top: 1.5rem;
    padding: 1rem;
}

.place-order-btn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.spinner-border {
    width: 1.2rem;
    height: 1.2rem;
    margin-right: 0.5rem;
    display: none;
}

.place-order-btn:disabled .spinner-border {
    display: inline-block;
}

.place-order-btn:disabled .bi-lock-fill {
    display: none;
}

@media (max-width: 768px) {
    .order-summary {
        margin-top: 2rem;
    }
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("checkout-form");
    const submitBtn = form.querySelector("button[type=submit]");
    
    form.addEventListener("submit", function() {
        submitBtn.disabled = true;
        submitBtn.querySelector(".spinner-border").style.display = "inline-block";
        submitBtn.querySelector(".bi-lock-fill").style.display = "none";
    });
});
</script>';

include '../includes/header.php';
?>

<div class="checkout-page">
    <div class="container">
        <div class="checkout-header">
            <h1 class="checkout-title">Checkout</h1>
            <p class="checkout-subtitle">Complete your purchase to receive your digital games</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <form method="POST" class="checkout-form" id="checkout-form">
                    <div class="digital-notice">
                        <i class="bi bi-cloud-download"></i>
                        <p class="digital-notice-text">
                            All games are delivered digitally. After purchase, you'll receive an email with your game keys and download instructions.
                        </p>
                    </div>

                    <!-- Personal Information -->
                    <div class="form-section">
                        <h2 class="form-section-title">Personal Information</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name" 
                                           placeholder="Full Name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                    <label for="name">Full Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Email Address" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                    <label for="email">Email Address</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Phone Number" value="<?php echo $_POST['phone'] ?? ''; ?>" required>
                                    <label for="phone">Phone Number</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="form-section">
                        <h2 class="form-section-title">Additional Information</h2>
                        <div class="form-floating">
                            <textarea class="form-control" id="notes" name="notes" style="height: 100px" 
                                      placeholder="Order Notes"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                            <label for="notes">Order Notes (Optional)</label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <div class="order-summary-header">
                        <h2 class="order-summary-title">Order Summary</h2>
                    </div>
                    <div class="order-summary-body">
                        <!-- Cart Items -->
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item-mini">
                                <img src="<?php echo BASE_URL . ($item['image_url'] ?? '/images/games/placeholder.jpg'); ?>" 
                                     class="cart-item-mini-image" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <div class="cart-item-mini-details">
                                    <h3 class="cart-item-mini-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="cart-item-mini-meta">
                                        Quantity: <?php echo $item['quantity']; ?> 
                                        <span class="badge bg-primary ms-2">Digital</span>
                                    </p>
                                </div>
                                <div class="cart-item-mini-price">
                                    <?php echo formatIDR($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Summary Calculations -->
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value"><?php echo formatIDR($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tax (11%)</span>
                            <span class="summary-value"><?php echo formatIDR($tax); ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total</span>
                            <span><?php echo formatIDR($total); ?></span>
                        </div>

                        <!-- Place Order Button -->
                        <button type="submit" form="checkout-form" class="btn btn-primary btn-lg w-100 place-order-btn">
                            <span class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </span>
                            <i class="bi bi-lock-fill me-2"></i>
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 