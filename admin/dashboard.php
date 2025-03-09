<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('/index.php');
}

$conn = getDbConnection();

// Get today's stats
$today = date('Y-m-d');
$today_query = "
    SELECT 
        COUNT(*) as orders_count,
        COALESCE(SUM(total_amount), 0) as total_sales,
        COUNT(DISTINCT user_id) as unique_customers
    FROM orders 
    WHERE DATE(created_at) = ? AND status = 'completed'";
$stmt = $conn->prepare($today_query);
$stmt->bind_param('s', $today);
$stmt->execute();
$today_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get monthly stats
$month_start = date('Y-m-01');
$month_query = "
    SELECT 
        COUNT(*) as orders_count,
        COALESCE(SUM(total_amount), 0) as total_sales,
        COUNT(DISTINCT user_id) as unique_customers
    FROM orders 
    WHERE DATE(created_at) >= ? AND status = 'completed'";
$stmt = $conn->prepare($month_query);
$stmt->bind_param('s', $month_start);
$stmt->execute();
$month_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get daily sales for the last 7 days
$daily_sales_query = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders_count,
        COALESCE(SUM(total_amount), 0) as total_sales
    FROM orders 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date";
$daily_sales = $conn->query($daily_sales_query)->fetch_all(MYSQLI_ASSOC);

// Get top selling products
$top_products_query = "
    SELECT 
        p.title,
        COUNT(oi.id) as times_sold,
        SUM(oi.quantity) as units_sold,
        SUM(oi.quantity * oi.price_at_time) as revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 5";
$top_products = $conn->query($top_products_query)->fetch_all(MYSQLI_ASSOC);

$additional_css = '<style>
.admin-dashboard {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 60px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
}

.stat-card.primary {
    background: var(--bs-primary);
    color: white;
}

.stat-card.success {
    background: var(--bs-success);
    color: white;
}

.stat-card.info {
    background: var(--bs-info);
    color: white;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0;
}

.stat-change {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}

.chart-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chart-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2d3436;
    margin: 0;
}

.top-products-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.product-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.product-item:last-child {
    border-bottom: none;
}

.product-rank {
    width: 30px;
    height: 30px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 1rem;
}

.product-info {
    flex-grow: 1;
}

.product-name {
    font-weight: 600;
    color: #2d3436;
    margin-bottom: 0.25rem;
}

.product-stats {
    color: #6c757d;
    font-size: 0.9rem;
}

.product-revenue {
    font-weight: 600;
    color: var(--bs-success);
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
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

include '../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="container">
        <!-- Admin Navigation -->
        <nav class="admin-nav">
            <ul class="admin-nav-list">
                <li>
                    <a href="dashboard.php" class="admin-nav-link active">
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
                    <a href="reports.php" class="admin-nav-link">
                        <i class="bi bi-file-earmark-text me-2"></i>Reports
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-label">Today's Sales</div>
                <h3 class="stat-value"><?php echo formatIDR($today_stats['total_sales']); ?></h3>
                <div class="stat-change">
                    <i class="bi bi-cart-check me-1"></i>
                    <?php echo $today_stats['orders_count']; ?> orders
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Monthly Sales</div>
                <h3 class="stat-value"><?php echo formatIDR($month_stats['total_sales']); ?></h3>
                <div class="stat-change">
                    <i class="bi bi-people me-1"></i>
                    <?php echo $month_stats['unique_customers']; ?> customers
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-label">Monthly Orders</div>
                <h3 class="stat-value"><?php echo $month_stats['orders_count']; ?></h3>
                <div class="stat-change">
                    <i class="bi bi-graph-up me-1"></i>
                    <?php echo number_format($month_stats['orders_count'] / date('j'), 1); ?> orders/day
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales Chart -->
            <div class="col-lg-8">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Sales Last 7 Days</h3>
                    </div>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Top Selling Products</h3>
                    </div>
                    <ul class="top-products-list">
                        <?php foreach ($top_products as $index => $product): ?>
                            <li class="product-item">
                                <div class="product-rank"><?php echo $index + 1; ?></div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['title']); ?></div>
                                    <div class="product-stats">
                                        <?php echo $product['units_sold']; ?> units sold
                                    </div>
                                </div>
                                <div class="product-revenue">
                                    <?php echo formatIDR($product['revenue']); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize sales chart
const salesChart = new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($sale) {
            return date('M j', strtotime($sale['date']));
        }, $daily_sales)); ?>,
        datasets: [{
            label: 'Daily Sales',
            data: <?php echo json_encode(array_map(function($sale) {
                return $sale['total_sales'];
            }, $daily_sales)); ?>,
            borderColor: '#0d6efd',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?> 