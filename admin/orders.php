<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('/index.php');
}

$conn = getDbConnection();

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$sort = $_GET['sort'] ?? 'newest';

// Base query
$query = "
    SELECT 
        o.id,
        o.total_amount,
        o.status,
        o.created_at,
        u.username,
        u.email,
        COUNT(oi.id) as items_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

// Apply filters
$params = [];
$types = '';

if ($status !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($search) {
    $search = "%$search%";
    $query .= " AND (o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
    $types .= 'sss';
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Group by and sort
$query .= " GROUP BY o.id";
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY o.created_at ASC";
        break;
    case 'highest':
        $query .= " ORDER BY o.total_amount DESC";
        break;
    case 'lowest':
        $query .= " ORDER BY o.total_amount ASC";
        break;
    default: // newest
        $query .= " ORDER BY o.created_at DESC";
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get order statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue
    FROM orders
";
$stats = $conn->query($stats_query)->fetch_assoc();

$additional_css = '<style>
.order-filters {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.order-table {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.order-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.order-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.order-details {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stats-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.admin-dashboard {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 60px);
}

.admin-nav {
    background: white;
    padding: 1rem;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.admin-nav-list {
    display: flex;
    gap: 1rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-nav-link {
    color: #6c757d;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.admin-nav-link:hover {
    background: #f8f9fa;
    color: var(--bs-primary);
}

.admin-nav-link.active {
    background: var(--bs-primary);
    color: white;
}

@media (max-width: 768px) {
    .admin-nav-list {
        flex-wrap: wrap;
    }
    
    .admin-nav-link {
        width: 100%;
    }
}
</style>';

include '../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="container">
        <!-- Admin Navigation -->
        <nav class="admin-nav">
            <ul class="admin-nav-list">
                <li>
                    <a href="dashboard.php" class="admin-nav-link">
                        <i class="bi bi-graph-up me-2"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="admin-nav-link active">
                        <i class="bi bi-cart-check me-2"></i>Orders
                    </a>
                </li>
                <li>
                    <a href="products.php" class="admin-nav-link">
                        <i class="bi bi-grid me-2"></i>Products
                    </a>
                </li>
                <li>
                    <a href="customers.php" class="admin-nav-link">
                        <i class="bi bi-people me-2"></i>Customers
                    </a>
                </li>
                </li>
                <li>
                    <a href="reports.php" class="admin-nav-link">
                        <i class="bi bi-file-earmark-text me-2"></i>Reports
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Order Statistics -->
        <div class="row mb-4">
            <div class="col">
                <div class="order-details">
                    <div class="row text-center">
                        <div class="col">
                            <div class="stats-number"><?php echo $stats['total_orders']; ?></div>
                            <div class="stats-label">Total Orders</div>
                        </div>
                        <div class="col">
                            <div class="stats-number"><?php echo $stats['completed_orders']; ?></div>
                            <div class="stats-label">Completed</div>
                        </div>
                        <div class="col">
                            <div class="stats-number"><?php echo $stats['pending_orders']; ?></div>
                            <div class="stats-label">Pending</div>
                        </div>
                        <div class="col">
                            <div class="stats-number"><?php echo $stats['cancelled_orders']; ?></div>
                            <div class="stats-label">Cancelled</div>
                        </div>
                        <div class="col">
                            <div class="stats-number"><?php echo formatIDR($stats['total_revenue']); ?></div>
                            <div class="stats-label">Total Revenue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Filters -->
        <div class="order-filters">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order ID, customer...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="highest" <?php echo $sort === 'highest' ? 'selected' : ''; ?>>Highest Amount</option>
                        <option value="lowest" <?php echo $sort === 'lowest' ? 'selected' : ''; ?>>Lowest Amount</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="order-table table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($order['username']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                            </td>
                            <td><?php echo $order['items_count']; ?> items</td>
                            <td><?php echo formatIDR($order['total_amount']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td class="order-actions">
                                <button type="button" class="btn btn-sm btn-primary view-order" data-bs-toggle="modal" data-bs-target="#orderModal" data-order-id="<?php echo $order['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-success update-status" data-order-id="<?php echo $order['id']; ?>" data-status="completed">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger update-status" data-order-id="<?php echo $order['id']; ?>" data-status="cancelled">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                No orders found matching your criteria
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded dynamically -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View order details
    const orderModal = document.getElementById('orderModal');
    const modalBody = orderModal.querySelector('.modal-body');
    
    document.querySelectorAll('.view-order').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            
            // Load order details
            fetch(`get_order_details.php?id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading order details. Please try again.
                        </div>
                    `;
                });
        });
    });

    // Update order status
    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to update this order\'s status?')) return;

            const orderId = this.dataset.orderId;
            const status = this.dataset.status;

            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating order status. Please try again.');
                }
            })
            .catch(error => {
                alert('Error updating order status. Please try again.');
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 