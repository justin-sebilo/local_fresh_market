<?php
$pageTitle = "Admin Dashboard - Local Fresh Market";
include '../includes/header.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Database connection
try {
    $host = 'localhost';
    $dbname = 'fresh_market';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch real-time data from database using your table structure
    // Total Revenue (sum of all completed orders)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE status = 'completed'");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
    
    // Total Orders count
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
    
    // Total Customers count
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM users WHERE user_type = 'customer'");
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];
    
    // Total Products count
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    // Today's Revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
    $todayRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['today_revenue'];
    
    // Pending Orders count
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];
    
    // Weekly sales data for chart (last 7 days)
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as day,
            COALESCE(SUM(total_amount), 0) as daily_sales
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND status = 'completed'
        GROUP BY DATE(created_at)
        ORDER BY day
    ");
    $weeklySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create chart data
    $chartLabels = [];
    $chartData = [];
    
    // Fill in missing days with zero sales
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('D', strtotime($date));
        
        $found = false;
        foreach ($weeklySales as $sale) {
            if ($sale['day'] == $date) {
                $chartData[] = (float)$sale['daily_sales'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $chartData[] = 0;
        }
    }
    
    // Recent orders for activity feed
    $stmt = $pdo->query("
        SELECT o.id, u.name, o.total_amount, o.created_at 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 3
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle database errors gracefully
    error_log("Database error: " . $e->getMessage());
    $totalRevenue = $totalOrders = $totalCustomers = $totalProducts = $todayRevenue = $pendingOrders = 0;
    $chartLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $chartData = [0, 0, 0, 0, 0, 0, 0];
    $recentOrders = [];
}
?>
                    
<div class="dashboard-container">
    <div class="dashboard-header mb-4">
        <h2 class="mb-2">Admin Dashboard</h2>
        <p class="text-muted">Welcome to your administration panel</p>
    </div>

    <!-- Real-time Sales Analytics Section -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card analytics-card">
                <div class="card-body text-center">
                    <div class="analytics-icon mb-3">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3 class="text-primary">$<?php echo number_format($totalRevenue, 2); ?></h3>
                    <p class="text-muted mb-0">Total Revenue</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i> 
                        $<?php echo number_format($todayRevenue, 2); ?> today
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card analytics-card">
                <div class="card-body text-center">
                    <div class="analytics-icon mb-3">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3 class="text-success"><?php echo $totalOrders; ?></h3>
                    <p class="text-muted mb-0">Total Orders</p>
                    <small class="text-warning">
                        <i class="fas fa-clock"></i> 
                        <?php echo $pendingOrders; ?> pending
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card analytics-card">
                <div class="card-body text-center">
                    <div class="analytics-icon mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-info"><?php echo $totalCustomers; ?></h3>
                    <p class="text-muted mb-0">Customers</p>
                    <small class="text-info">
                        <i class="fas fa-user-check"></i> 
                        Registered users
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card analytics-card">
                <div class="card-body text-center">
                    <div class="analytics-icon mb-3">
                        <i class="fas fa-cube"></i>
                    </div>
                    <h3 class="text-warning"><?php echo $totalProducts; ?></h3>
                    <p class="text-muted mb-0">Products</p>
                    <small class="text-primary">
                        <i class="fas fa-boxes"></i> 
                        In catalog
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card main-card">
                <div class="card-body">
                    <h3 class="card-title">Welcome to Local Fresh Market Admin Panel</h3>
                    <p class="card-text">Manage products, view orders, and configure system settings.</p>
                    
                    <div class="dashboard-features mt-4">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="feature-box">
                                    <div class="feature-icon">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h5>Product Catalog</h5>
                                        <p class="text-muted">Manage your inventory and product listings</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="feature-box">
                                    <div class="feature-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h5>Order Management</h5>
                                        <p class="text-muted">Process and track customer orders</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="feature-box">
                                    <div class="feature-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h5>Sales Analytics</h5>
                                        <p class="text-muted">View sales reports and insights</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="feature-box">
                                    <div class="feature-icon">
                                        <i class="fas fa-cogs"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h5>System Settings</h5>
                                        <p class="text-muted">Configure store preferences</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card sidebar-card">
                <div class="card-body">
                    <h5 class="card-title">Getting Started</h5>
                    <div class="tips-list">
                        <div class="tip-item">
                            <i class="fas fa-plus-circle text-primary"></i>
                            <span>Add your first product to the catalog</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-users text-success"></i>
                            <span>Review pending customer orders</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-chart-line text-info"></i>
                            <span>Check your sales performance</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-bell text-warning"></i>
                            <span>Set up inventory alerts</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Sales Chart -->
            <div class="card sidebar-card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Weekly Sales</h5>
                    <div class="mini-chart">
                        <canvas id="salesChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card sidebar-card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Recent Orders</h5>
                    <div class="activity-list">
                        <?php if (!empty($recentOrders)): ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-shopping-cart text-success"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1">Order #<?php echo $order['id']; ?> from <?php echo htmlspecialchars($order['name']); ?></p>
                                        <small class="text-muted">$<?php echo number_format($order['total_amount'], 2); ?> • <?php echo time_elapsed_string($order['created_at']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">No recent orders</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-header h2 {
    color: #2c3e50;
    font-weight: 600;
    border-bottom: 3px solid #3498db;
    padding-bottom: 10px;
    display: inline-block;
}

/* Analytics Cards */
.analytics-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
    background: #ffffff;
}

.analytics-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.analytics-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

.analytics-card:nth-child(2) .analytics-icon {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.analytics-card:nth-child(3) .analytics-icon {
    background: rgba(155, 89, 182, 0.1);
    color: #9b59b6;
}

.analytics-card:nth-child(4) .analytics-icon {
    background: rgba(241, 196, 15, 0.1);
    color: #f1c40f;
}

.analytics-card h3 {
    font-weight: 700;
    margin: 10px 0 5px 0;
}

.analytics-card small {
    font-size: 0.8rem;
}

/* Activity List */
.activity-list .activity-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
}

.activity-list .activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    background: rgba(46, 204, 113, 0.1);
}

.activity-content p {
    margin: 0;
    font-size: 0.9rem;
    color: #2c3e50;
}

.activity-content small {
    font-size: 0.8rem;
}

/* Rest of the existing styles */
.main-card {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.main-card .card-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 15px;
}

.sidebar-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    background: #ffffff;
}

.sidebar-card .card-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.1rem;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.feature-box {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: #ffffff;
    border: 1px solid #e9ecef;
}

.feature-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #3498db;
}

.feature-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 1.2rem;
}

.feature-box:nth-child(2) .feature-icon {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.feature-box:nth-child(3) .feature-icon {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.feature-box:nth-child(4) .feature-icon {
    background: linear-gradient(135deg, #f39c12, #e67e22);
}

.feature-content h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 5px;
}

.tips-list .tip-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
}

.tips-list .tip-item:last-child {
    border-bottom: none;
}

.tips-list .tip-item i {
    margin-right: 10px;
    font-size: 1.1rem;
    width: 20px;
}

.tips-list .tip-item span {
    color: #6c757d;
    font-size: 0.9rem;
}

.mini-chart {
    position: relative;
    height: 120px;
    width: 100%;
}

.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
}
</style>

<!-- Chart.js for sales chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Sales ($)',
                data: <?php echo json_encode($chartData); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    display: false,
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<?php 
// Helper function for time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Close the main content and sidebar
if (isLoggedIn()): ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</body>
</html>