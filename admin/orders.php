<?php
$pageTitle = "Order Management - Local Fresh Market";
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
    
    // Handle status update
    if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        // Refresh the page to show updated status
        header("Location: orders.php");
        exit();
    }
    
    // Fetch orders from database
    $stmt = $pdo->query("
        SELECT o.*, u.name as customer_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order Management</h2>
        <span class="badge bg-primary"><?php echo count($orders); ?> orders</span>
    </div>

    <div class="orders-list">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
            <div class="card order-card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">Order #<?php echo $order['id']; ?></h5>
                            <p class="card-text mb-1">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                            </p>
                            <p class="card-text mb-1">
                                <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                            <p class="card-text mb-1">
                                <strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                            </p>
                            <p class="card-text mb-1">
                                <strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge 
                                <?php 
                                if ($order['status'] == 'completed') echo 'bg-success';
                                elseif ($order['status'] == 'cancelled') echo 'bg-danger';
                                else echo 'bg-warning';
                                ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <div class="mt-3">
                                <!-- View Details Button -->
                                <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    View Details
                                </button>
                                
                                <!-- Update Status Dropdown -->
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Update
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $order['id']; ?>, 'pending')">Mark as Pending</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $order['id']; ?>, 'completed')">Mark as Completed</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $order['id']; ?>, 'cancelled')">Mark as Cancelled</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4>No Orders Yet</h4>
                <p class="text-muted">When customers place orders, they will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Status Update Form -->
<form id="statusUpdateForm" method="post" style="display: none;">
    <input type="hidden" name="update_status" value="1">
    <input type="hidden" name="order_id" id="update_order_id">
    <input type="hidden" name="status" id="update_status">
</form>

<style>
.order-card {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: box-shadow 0.2s ease;
}

.order-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
}

.card-text {
    color: #555;
    font-size: 0.9rem;
}

.badge {
    font-size: 0.8rem;
    padding: 6px 12px;
}

.btn {
    border-radius: 6px;
    font-size: 0.85rem;
    padding: 5px 12px;
}

.dropdown-menu {
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.dropdown-item {
    font-size: 0.85rem;
    padding: 6px 12px;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
// View Order Details
function viewOrderDetails(orderId) {
    // For now, just show a simple alert with the order ID
    // In a real application, you would fetch order details via AJAX
    alert('Viewing details for Order #' + orderId + '\n\nIn a full implementation, this would show:\n- Order items\n- Customer details\n- Shipping information\n- Payment details');
    
    // Example of what you would do with AJAX:
    /*
    fetch('get_order_details.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('orderDetailsContent').innerHTML = data.html;
            var modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();
        });
    */
}

// Update Order Status
function updateStatus(orderId, status) {
    if (confirm('Are you sure you want to mark Order #' + orderId + ' as ' + status + '?')) {
        document.getElementById('update_order_id').value = orderId;
        document.getElementById('update_status').value = status;
        document.getElementById('statusUpdateForm').submit();
    }
}

// Simple status update without confirmation (optional)
function quickUpdateStatus(orderId, status) {
    document.getElementById('update_order_id').value = orderId;
    document.getElementById('update_status').value = status;
    document.getElementById('statusUpdateForm').submit();
}
</script>

<?php 
// Close the main content and sidebar
if (isLoggedIn()): ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</body>
</html>