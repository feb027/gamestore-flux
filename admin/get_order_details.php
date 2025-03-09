<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

// Get order ID
$order_id = $_GET['id'] ?? 0;

$conn = getDbConnection();

// Get order details
$order_query = "
    SELECT 
        o.*,
        u.username,
        u.email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
";

$stmt = $conn->prepare($order_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    exit;
}

// Get order items
$items_query = "
    SELECT 
        oi.*,
        p.title,
        p.image_url
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";

$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="order-details">
    <!-- Order Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h6 class="mb-1">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
            <div class="text-muted">
                Placed on <?php echo date('F j, Y \a\t H:i', strtotime($order['created_at'])); ?>
            </div>
        </div>
        <span class="status-badge status-<?php echo $order['status']; ?>">
            <?php echo ucfirst($order['status']); ?>
        </span>
    </div>

    <!-- Customer Info -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Customer Information</h6>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">
                        <strong>Order Date:</strong> <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Order Time:</strong> <?php echo date('H:i', strtotime($order['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Order Items</h6>
            <div class="table-responsive">
                <table class="table table-borderless">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                 class="me-3"
                                                 style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                                        <?php endif; ?>
                                        <div>
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo formatIDR($item['price_at_time']); ?></td>
                                <td class="text-end"><?php echo formatIDR($item['price_at_time'] * $item['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Amount</strong></td>
                            <td class="text-end"><strong><?php echo formatIDR($order['total_amount']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Timeline -->
    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Order Timeline</h6>
            <div class="position-relative">
                <div class="timeline-line"></div>
                <div class="timeline-item">
                    <div class="timeline-indicator bg-primary">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <div class="timeline-content">
                        <h6 class="mb-1">Order Placed</h6>
                        <p class="text-muted mb-0">
                            <?php echo date('F j, Y \a\t H:i', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <?php if ($order['status'] !== 'pending'): ?>
                    <div class="timeline-item">
                        <div class="timeline-indicator <?php echo $order['status'] === 'completed' ? 'bg-success' : 'bg-danger'; ?>">
                            <i class="bi <?php echo $order['status'] === 'completed' ? 'bi-check-lg' : 'bi-x-lg'; ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Order <?php echo ucfirst($order['status']); ?></h6>
                            <p class="text-muted mb-0">
                                Status updated to <?php echo $order['status']; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-line {
    position: absolute;
    left: 15px;
    top: 0;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-left: 45px;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-indicator {
    position: absolute;
    left: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    z-index: 1;
}

.timeline-indicator i {
    font-size: 1rem;
}

.timeline-content {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}
</style> 