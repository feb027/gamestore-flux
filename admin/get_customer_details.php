<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    http_response_code(403);
    exit('Access denied');
}

// Get customer ID
$customer_id = $_GET['id'] ?? 0;

$conn = getDbConnection();

// Get customer details with statistics
$customer_query = "
    SELECT 
        u.*,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent,
        COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN oi.product_id END) as unique_games,
        MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE u.id = ?
    GROUP BY u.id
";

$stmt = $conn->prepare($customer_query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    echo '<div class="alert alert-danger">Customer not found.</div>';
    exit;
}

// Get recent orders
$orders_query = "
    SELECT 
        o.*,
        COUNT(oi.id) as items_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get favorite genres
$genres_query = "
    SELECT 
        p.genre,
        COUNT(*) as purchase_count
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND o.status = 'completed'
    GROUP BY p.genre
    ORDER BY purchase_count DESC
    LIMIT 3
";

$stmt = $conn->prepare($genres_query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$favorite_genres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="customer-details">
    <!-- Customer Overview -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="customer-avatar me-3" style="font-size: 2rem; width: 64px; height: 64px; background: linear-gradient(45deg, var(--bs-primary), var(--bs-info));">
                            <?php echo strtoupper(substr($customer['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <h5 class="mb-1 d-flex align-items-center">
                                <?php echo htmlspecialchars($customer['username']); ?>
                                <span class="badge <?php echo $customer['total_orders'] > 0 ? 'bg-success' : 'bg-danger'; ?> ms-2" style="font-size: 0.7rem;">
                                    <?php echo $customer['total_orders'] > 0 ? 'Active' : 'Inactive'; ?>
                                </span>
                            </h5>
                            <div class="text-muted d-flex align-items-center">
                                <i class="bi bi-envelope-fill me-2"></i>
                                <?php echo htmlspecialchars($customer['email']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light-subtle">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-calendar-check me-1"></i> Member Since
                                </div>
                                <div class="fw-medium"><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light-subtle">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-bag-check me-1"></i> Last Order
                                </div>
                                <div class="fw-medium">
                                    <?php if ($customer['last_order_date']): ?>
                                        <?php echo date('F j, Y', strtotime($customer['last_order_date'])); ?>
                                    <?php else: ?>
                                        No orders yet
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 bg-primary bg-opacity-10 me-3">
                                    <i class="bi bi-cart text-primary"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0"><?php echo $customer['total_orders']; ?></div>
                                    <div class="text-muted small">Total Orders</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 bg-success bg-opacity-10 me-3">
                                    <i class="bi bi-currency-dollar text-success"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0"><?php echo formatIDR($customer['total_spent'] ?? 0); ?></div>
                                    <div class="text-muted small">Total Spent</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 bg-info bg-opacity-10 me-3">
                                    <i class="bi bi-controller text-info"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0"><?php echo $customer['unique_games']; ?></div>
                                    <div class="text-muted small">Games Owned</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 bg-warning bg-opacity-10 me-3">
                                    <i class="bi bi-graph-up text-warning"></i>
                                </div>
                                <div>
                                    <div class="h5 mb-0">
                                        <?php 
                                            echo $customer['total_orders'] > 0 
                                                ? formatIDR(($customer['total_spent'] ?? 0) / $customer['total_orders'])
                                                : '0';
                                        ?>
                                    </div>
                                    <div class="text-muted small">Avg. Order Value</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title d-flex align-items-center mb-4">
                        <i class="bi bi-graph-up-arrow me-2"></i>
                        Customer Insights
                    </h6>
                    
                    <!-- Favorite Genres -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="text-muted">Favorite Genres</div>
                            <div class="ms-auto small text-primary">Top 3</div>
                        </div>
                        <?php if (!empty($favorite_genres)): ?>
                            <?php foreach ($favorite_genres as $genre): ?>
                                <?php 
                                    $percentage = round(($genre['purchase_count'] / $customer['unique_games']) * 100);
                                    $gradientClass = $percentage > 66 ? 'bg-success' : ($percentage > 33 ? 'bg-info' : 'bg-primary');
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <div class="fw-medium"><?php echo htmlspecialchars($genre['genre']); ?></div>
                                        <div class="small text-muted">
                                            <?php echo $genre['purchase_count']; ?> games
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?php echo $gradientClass; ?>" 
                             role="progressbar" 
                             style="width: <?php echo $percentage; ?>%"
                             aria-valuenow="<?php echo $percentage; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-controller text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                <div class="text-muted">No purchase history yet</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Customer Value -->
                    <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="text-muted">Customer Value</div>
                            <div class="ms-auto small text-primary">Lifetime</div>
                        </div>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-muted small mb-1">Total Revenue</div>
                                        <div class="h4 mb-0"><?php echo formatIDR($customer['total_spent'] ?? 0); ?></div>
                                    </div>
                                    <?php if ($customer['total_spent'] > 0): ?>
                                        <div class="col-6">
                                            <div class="text-muted small mb-1">Average Order</div>
                                            <div class="h4 mb-0">
                                                <?php echo formatIDR(($customer['total_spent'] ?? 0) / $customer['total_orders']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="card-title d-flex align-items-center mb-4">
                <i class="bi bi-clock-history me-2"></i>
                Recent Orders
            </h6>
            <?php if (!empty($recent_orders)): ?>
                <div class="order-timeline">
                    <div class="timeline-line"></div>
                    <?php foreach ($recent_orders as $order): ?>
                        <?php
                            $statusColors = [
                                'completed' => 'success',
                                'pending' => 'warning',
                                'cancelled' => 'danger',
                                'processing' => 'info'
                            ];
                            $statusColor = $statusColors[$order['status']] ?? 'secondary';
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot bg-<?php echo $statusColor; ?>"></div>
                            <div class="timeline-content border-start border-4 border-<?php echo $statusColor; ?> bg-white p-3 rounded-3 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-0">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                        <span class="badge bg-<?php echo $statusColor; ?> bg-opacity-10 text-<?php echo $statusColor; ?> small">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-muted">
                                        <i class="bi bi-box me-1"></i>
                                        <?php echo $order['items_count']; ?> items
                                    </div>
                                    <div class="fw-medium">
                                        <?php echo formatIDR($order['total_amount']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-cart text-muted d-block mb-2" style="font-size: 2rem;"></i>
                    <div class="text-muted">No orders yet</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.bg-light-subtle {
    background-color: rgba(var(--bs-light-rgb), 0.1);
}

.customer-details .card {
    transition: transform 0.2s;
}

.customer-details .card:hover {
    transform: translateY(-2px);
}

.order-timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-line {
    position: absolute;
    left: 8px;
    top: 0;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -2rem;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 3px solid white;
    margin-top: 0.25rem;
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.timeline-content {
    margin-left: 0.5rem;
    transition: transform 0.2s;
}

.timeline-content:hover {
    transform: translateX(4px);
}

.progress {
    background-color: rgba(var(--bs-light-rgb), 0.2);
}

.customer-avatar {
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.15);
}
</style> 