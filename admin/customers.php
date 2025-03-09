<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('/index.php');
}

$conn = getDbConnection();

// Get filter parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$status = $_GET['status'] ?? 'all';

// Base query
$query = "
    SELECT 
        u.*,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent,
        MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.id != ?
    GROUP BY u.id
";

// Apply filters
$params = [$_SESSION['user_id']]; // Exclude current admin
$types = 'i';

if ($search) {
    $search = "%$search%";
    $query = str_replace('GROUP BY u.id', 'AND (u.username LIKE ? OR u.email LIKE ?) GROUP BY u.id', $query);
    $params = array_merge($params, [$search, $search]);
    $types .= 'ss';
}

// Apply sorting
switch ($sort) {
    case 'username_asc':
        $query .= " ORDER BY u.username ASC";
        break;
    case 'username_desc':
        $query .= " ORDER BY u.username DESC";
        break;
    case 'most_spent':
        $query .= " ORDER BY total_spent DESC";
        break;
    case 'most_orders':
        $query .= " ORDER BY total_orders DESC";
        break;
    case 'last_order':
        $query .= " ORDER BY last_order_date DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY u.created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY u.created_at DESC";
}

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get customer statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_customers,
        SUM(CASE WHEN EXISTS (
            SELECT 1 FROM orders o 
            WHERE o.user_id = u.id AND o.status = 'completed'
        ) THEN 1 ELSE 0 END) as active_customers,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as total_orders,
        (SELECT SUM(total_amount) FROM orders WHERE status = 'completed') as total_revenue
    FROM users u
    WHERE u.id != ?
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$additional_css = '<style>
.customer-filters {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.customer-table {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.customer-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.customer-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    height: 100%;
}

.stats-icon {
    width: 48px;
    height: 48px;
    background: var(--bs-primary);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
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

.customer-avatar {
    width: 40px;
    height: 40px;
    background: var(--bs-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
}

.activity-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.activity-active {
    background: var(--bs-success);
}

.activity-inactive {
    background: var(--bs-danger);
}

.order-timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-line {
    position: absolute;
    left: 0;
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
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--bs-primary);
    border: 2px solid white;
    margin-top: 0.25rem;
}

.timeline-content {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
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
                    <a href="orders.php" class="admin-nav-link">
                        <i class="bi bi-cart-check me-2"></i>Orders
                    </a>
                </li>
                <li>
                    <a href="products.php" class="admin-nav-link">
                        <i class="bi bi-grid me-2"></i>Products
                    </a>
                </li>
                <li>
                    <a href="customers.php" class="admin-nav-link active">
                        <i class="bi bi-people me-2"></i>Customers
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="admin-nav-link">
                        <i class="bi bi-file-earmark-text me-2"></i>Reports
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Customer Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['total_customers']; ?></div>
                    <div class="stats-label">Total Customers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['active_customers']; ?></div>
                    <div class="stats-label">Active Customers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['total_orders']; ?></div>
                    <div class="stats-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stats-number"><?php echo formatIDR($stats['total_revenue'] ?? 0); ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
            </div>
        </div>

        <!-- Customer Filters -->
        <div class="customer-filters">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Customers</h5>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by username or email...">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="username_asc" <?php echo $sort === 'username_asc' ? 'selected' : ''; ?>>Username (A-Z)</option>
                        <option value="username_desc" <?php echo $sort === 'username_desc' ? 'selected' : ''; ?>>Username (Z-A)</option>
                        <option value="most_spent" <?php echo $sort === 'most_spent' ? 'selected' : ''; ?>>Most Spent</option>
                        <option value="most_orders" <?php echo $sort === 'most_orders' ? 'selected' : ''; ?>>Most Orders</option>
                        <option value="last_order" <?php echo $sort === 'last_order' ? 'selected' : ''; ?>>Last Order Date</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="customer-table table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px"></th>
                        <th>Customer</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($customer['username'], 0, 1)); ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="activity-indicator <?php echo $customer['total_orders'] > 0 ? 'activity-active' : 'activity-inactive'; ?>"></span>
                                    <div>
                                        <div><?php echo htmlspecialchars($customer['username']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $customer['total_orders']; ?> orders</td>
                            <td><?php echo formatIDR($customer['total_spent'] ?? 0); ?></td>
                            <td>
                                <?php if ($customer['last_order_date']): ?>
                                    <?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">No orders yet</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                            <td class="customer-actions">
                                <button type="button" class="btn btn-sm btn-primary view-customer" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#customerModal"
                                        data-customer-id="<?php echo $customer['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-people text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                No customers found matching your criteria
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Details</h5>
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
    const customerModal = document.getElementById('customerModal');
    const modalBody = customerModal.querySelector('.modal-body');
    
    // View customer details
    document.querySelectorAll('.view-customer').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.dataset.customerId;
            
            // Load customer details
            fetch(`get_customer_details.php?id=${customerId}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading customer details. Please try again.
                        </div>
                    `;
                });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 