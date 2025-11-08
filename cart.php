<?php
// Start session and include config at the very top
include 'includes/config.php';

// Remove from cart functionality - must be at the TOP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_cart'])) {
    $cartId = $_POST['cart_id'];
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $_SESSION['user_id']]);
    
    header("Location: cart.php");
    exit();
}

// Update quantity functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $cartId = $_POST['cart_id'];
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cartId, $_SESSION['user_id']]);
    }
    
    header("Location: cart.php");
    exit();
}

// Checkout functionality - must be at the TOP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    // Get cart items first
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.stock, p.image_url
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
    
    if (!empty($cartItems)) {
        // Calculate total
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $orderId = $pdo->lastInsertId();
        
        // Add order items
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        header("Location: orders.php?ordered=1");
        exit();
    }
}

// Now include the rest
$pageTitle = "My Cart - Local Fresh Market";
include 'includes/header.php';
redirectIfNotLoggedIn();
redirectIfAdmin();

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.stock, p.image_url
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();
?>
                    
<h2>My Cart</h2>

<?php if (empty($cartItems)): ?>
    <div class="alert alert-info">Your cart is empty.</div>
<?php else: ?>
    <div id="cart-items">
        <?php 
        $total = 0;
        foreach ($cartItems as $item): 
            $itemTotal = $item['price'] * $item['quantity'];
            $total += $itemTotal;
        ?>
            <div class="cart-item">
                <div class="row align-items-center">
                    <!-- Product Image -->
                    <div class="col-md-2">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 style="max-height: 80px; max-width: 100%;" 
                                 class="img-fluid rounded">
                        <?php else: ?>
                            <div class="bg-light rounded text-center py-3">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Details -->
                    <div class="col-md-4">
                        <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p class="mb-0 text-muted">₱<?php echo number_format($item['price'], 2); ?> each</p>
                    </div>
                    
                    <!-- Quantity Controls -->
                    <div class="col-md-3">
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <div class="input-group" style="max-width: 150px;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        onclick="updateCartQuantity(<?php echo $item['id']; ?>, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       id="cart_quantity_<?php echo $item['id']; ?>" 
                                       name="quantity" 
                                       class="form-control form-control-sm text-center" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['stock'] + $item['quantity']; ?>"
                                       onchange="updateCartQuantityDirect(<?php echo $item['id']; ?>)">
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        onclick="updateCartQuantity(<?php echo $item['id']; ?>, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button type="submit" name="update_quantity" class="btn btn-link btn-sm ms-2" style="display: none;" id="update_btn_<?php echo $item['id']; ?>">
                                <i class="fas fa-check text-success"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Item Total and Remove -->
                    <div class="col-md-3 text-end">
                        <p class="mb-2"><strong>₱<?php echo number_format($itemTotal, 2); ?></strong></p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_from_cart" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Cart Total and Checkout -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Total: ₱<?php echo number_format($total, 2); ?></h4>
                <form method="POST">
                    <button type="submit" name="checkout" class="btn btn-success btn-lg">
                        <i class="fas fa-shopping-bag me-1"></i> Checkout
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function updateCartQuantity(cartId, change) {
    const input = document.getElementById('cart_quantity_' + cartId);
    let currentValue = parseInt(input.value);
    const maxStock = parseInt(input.max);
    
    let newValue = currentValue + change;
    if (newValue >= 1 && newValue <= maxStock) {
        input.value = newValue;
        document.getElementById('update_btn_' + cartId).style.display = 'inline-block';
    }
}

function updateCartQuantityDirect(cartId) {
    document.getElementById('update_btn_' + cartId).style.display = 'inline-block';
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