<?php
require_once '../includes/config.php';

// Require login
requireLogin();

// Redirect if no success message
if (!isset($_SESSION['flash_messages']['success'])) {
    redirect('/pages/products.php');
}

// Get user's cart count for header
$conn = getDbConnection();
$user_id = getCurrentUserId();
$cart_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['count'];
$stmt->close();

// Redirect if cart is not empty (order wasn't completed)
if ($cart_count > 0) {
    redirect('/pages/cart.php');
}

$additional_css = '<style>
.success-page {
    padding: 4rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.success-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    padding: 2rem;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: #d4edda;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.success-icon i {
    font-size: 2.5rem;
    color: #198754;
}

.success-title {
    color: #198754;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.success-message {
    color: #6c757d;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.next-steps {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: left;
    margin-bottom: 2rem;
}

.next-steps-title {
    font-size: 1.2rem;
    color: #2d3436;
    margin-bottom: 1rem;
}

.next-steps-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.next-steps-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: #495057;
}

.next-steps-list li:last-child {
    margin-bottom: 0;
}

.next-steps-list i {
    color: var(--bs-primary);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

@media (max-width: 576px) {
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
}
</style>';

include '../includes/header.php';
?>

<div class="success-page">
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 class="success-title">Order Successful!</h1>
            <p class="success-message">
                Thank you for your purchase! Your digital games will be delivered shortly.
            </p>

            <div class="next-steps">
                <h2 class="next-steps-title">Next Steps:</h2>
                <ul class="next-steps-list">
                    <li>
                        <i class="bi bi-envelope"></i>
                        Check your email for order confirmation and game keys
                    </li>
                    <li>
                        <i class="bi bi-cloud-download"></i>
                        Follow the download instructions in the email
                    </li>
                    <li>
                        <i class="bi bi-controller"></i>
                        Install your games and start playing!
                    </li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="<?php echo BASE_URL; ?>/pages/products.php" class="btn btn-primary">
                    <i class="bi bi-bag me-2"></i>Continue Shopping
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/order-history.php" class="btn btn-outline-primary">
                    <i class="bi bi-clock-history me-2"></i>View Order History
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>