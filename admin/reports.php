<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('/index.php');
}

$conn = getDbConnection();

// Get date range filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'daily';

// Base query for sales data
$sales_query = "
    SELECT 
        DATE(o.created_at) as date,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(o.total_amount) as revenue,
        COUNT(DISTINCT o.user_id) as unique_customers,
        SUM(oi.quantity) as items_sold
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$sales_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get top selling products
$top_products_query = "
    SELECT 
        p.id,
        p.title,
        p.price,
        COUNT(DISTINCT o.id) as order_count,
        SUM(oi.quantity) as units_sold,
        SUM(oi.quantity * p.price) as total_revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.title, p.price
    ORDER BY total_revenue DESC
    LIMIT 5
";

$stmt = $conn->prepare($top_products_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get sales by genre
$genre_sales_query = "
    SELECT 
        p.genre,
        COUNT(DISTINCT o.id) as order_count,
        SUM(oi.quantity) as units_sold,
        SUM(oi.quantity * p.price) as total_revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.genre
    ORDER BY total_revenue DESC
";

$stmt = $conn->prepare($genre_sales_query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$genre_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate summary statistics
$total_revenue = 0;
$total_orders = 0;
$total_items = 0;
$unique_customers = [];

foreach ($sales_data as $day) {
    $total_revenue += $day['revenue'];
    $total_orders += $day['total_orders'];
    $total_items += $day['items_sold'];
    $unique_customers[] = $day['unique_customers'];
}

$unique_customer_count = array_sum($unique_customers);
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Prepare data for charts
$dates = [];
$revenues = [];
$orders = [];
$items = [];

foreach ($sales_data as $day) {
    $dates[] = date('M j', strtotime($day['date']));
    $revenues[] = $day['revenue'];
    $orders[] = $day['total_orders'];
    $items[] = $day['items_sold'];
}

$additional_css = '
<style>
.report-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    height: 100%;
    border: none;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.report-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
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

.stat-number {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--bs-dark);
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.product-rank {
    width: 24px;
    height: 24px;
    background: var(--bs-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.genre-progress {
    height: 8px;
    border-radius: 4px;
}

.date-filter {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
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
        <nav class="admin-nav mb-4">
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
                    <a href="customers.php" class="admin-nav-link">
                        <i class="bi bi-people me-2"></i>Customers
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="admin-nav-link active">
                        <i class="bi bi-file-earmark-text me-2"></i>Reports
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Date Range Filter -->
        <div class="date-filter">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Period</label>
                    <select class="form-select" name="period">
                        <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="report-card">
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-number"><?php echo formatIDR($total_revenue); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card">
                    <div class="stat-icon bg-success">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card">
                    <div class="stat-icon bg-info">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-number"><?php echo $unique_customer_count; ?></div>
                    <div class="stat-label">Unique Customers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="report-card">
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="stat-number"><?php echo formatIDR($avg_order_value); ?></div>
                    <div class="stat-label">Average Order Value</div>
                </div>
            </div>
        </div>

        <!-- Sales Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="card-title mb-4">Sales Overview</h5>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products and Genre Analysis -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="report-card">
                    <h5 class="card-title mb-4">Top Selling Products</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 40px"></th>
                                    <th>Product</th>
                                    <th class="text-end">Units</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-rank"><?php echo $index + 1; ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?php echo htmlspecialchars($product['title']); ?></div>
                                            <small class="text-muted"><?php echo $product['order_count']; ?> orders</small>
                                        </td>
                                        <td class="text-end"><?php echo $product['units_sold']; ?></td>
                                        <td class="text-end fw-medium"><?php echo formatIDR($product['total_revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h5 class="card-title mb-4">Sales by Genre</h5>
                    <?php
                    $total_genre_revenue = array_sum(array_column($genre_sales, 'total_revenue'));
                    foreach ($genre_sales as $genre):
                        $percentage = $total_genre_revenue > 0 ? ($genre['total_revenue'] / $total_genre_revenue) * 100 : 0;
                    ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-medium"><?php echo htmlspecialchars($genre['genre']); ?></div>
                                    <small class="text-muted">
                                        <?php echo $genre['order_count']; ?> orders, 
                                        <?php echo $genre['units_sold']; ?> units
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-medium"><?php echo formatIDR($genre['total_revenue']); ?></div>
                                    <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                                </div>
                            </div>
                            <div class="progress genre-progress">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $percentage; ?>%" 
                                     aria-valuenow="<?php echo $percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Orders',
                    data: <?php echo json_encode($orders); ?>,
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 