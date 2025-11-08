<?php
$pageTitle = "Orders - Local Fresh Market";
include 'includes/header.php';
redirectIfNotLoggedIn();
redirectIfAdmin();

// Get user orders with proper grouping
$stmt = $pdo->prepare("
    SELECT o.id as order_id, o.total_amount, o.status, o.created_at,
           oi.product_id, oi.quantity, oi.price, 
           p.name as product_name
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orderData = $stmt->fetchAll();

// Group items by order properly
$orders = [];
foreach ($orderData as $item) {
    $orderId = $item['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'id' => $orderId,
            'total_amount' => $item['total_amount'],
            'status' => $item['status'],
            'created_at' => $item['created_at'],
            'items' => []
        ];
    }
    $orders[$orderId]['items'][] = [
        'name' => $item['product_name'],
        'price' => $item['price'],
        'quantity' => $item['quantity']
    ];
}
?>
                    
<h2>Orders</h2>

<?php if (isset($_GET['ordered'])): ?>
    <div class="alert alert-success">Order placed successfully!</div>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="alert alert-info">You have no orders yet.</div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <div class="order-item">
            <h5>Order #<?php echo $order['id']; ?> (<?php echo $_SESSION['user_email']; ?>)</h5>
            <ul class="list-unstyled">
                <?php foreach ($order['items'] as $item): ?>
                    <li><?php echo htmlspecialchars($item['name']); ?> - ₱<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total: ₱<?php echo number_format($order['total_amount'], 2); ?></strong></p>
            <p><small>Status: <?php echo ucfirst($order['status']); ?> | Date: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></small></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

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