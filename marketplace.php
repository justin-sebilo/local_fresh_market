<?php
// Start session and include config at the very top
include 'includes/config.php';

// Add to cart functionality - must be at the TOP before any HTML output
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    
    // Validate quantity
    if ($quantity < 1) {
        $error = "Quantity must be at least 1!";
    } else {
        // Check if product exists and has enough stock
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock >= ?");
        $stmt->execute([$productId, $quantity]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Check if already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $productId]);
            $existingCartItem = $stmt->fetch();
            
            if ($existingCartItem) {
                // Update quantity
                $newQuantity = $existingCartItem['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$newQuantity, $_SESSION['user_id'], $productId]);
            } else {
                // Add to cart
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $productId, $quantity]);
            }
            
            header("Location: marketplace.php?added=1");
            exit();
        } else {
            $error = "Not enough stock available!";
        }
    }
}

// Now include the rest
$pageTitle = "Marketplace - Local Fresh Market";
include 'includes/header.php';
redirectIfNotLoggedIn();
redirectIfAdmin();

// Get products
$stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
                    
<h2>Marketplace</h2>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">Product added to cart successfully!</div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <?php if (empty($products)): ?>
        <div class="col-12">
            <div class="alert alert-info">No products available at the moment.</div>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="product-card h-100">
                    <!-- Product Image -->
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="text-center mb-3">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 style="max-height: 200px; max-width: 100%;" 
                                 class="img-fluid rounded">
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-3 bg-light rounded py-4">
                            <i class="fas fa-image fa-3x text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No Image</p>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="product-title">
                        <?php echo htmlspecialchars($product['name']); ?>
                        <!-- Stock Status Badges -->
                        <?php if ($product['stock'] == 0): ?>
                            <span class="badge bg-danger ms-2">Out of Stock</span>
                        <?php elseif ($product['stock'] < 5): ?>
                            <span class="badge bg-warning ms-2">Low Stock</span>
                        <?php endif; ?>
                    </h5>
                    
                    <p class="text-muted product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="h5 text-primary mb-0">₱<?php echo number_format($product['price'], 2); ?></span>
                        <span class="badge bg-<?php 
                            if ($product['stock'] > 10) echo 'success';
                            elseif ($product['stock'] > 5) echo 'info';
                            elseif ($product['stock'] > 0) echo 'warning';
                            else echo 'danger';
                        ?>">
                            Stock: <?php echo $product['stock']; ?>
                        </span>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <!-- Quantity Selector -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Quantity:</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="decreaseQuantity(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       id="quantity_<?php echo $product['id']; ?>" 
                                       name="quantity" 
                                       class="form-control text-center" 
                                       value="1" 
                                       min="1" 
                                       max="<?php echo $product['stock']; ?>" 
                                       required>
                                <button type="button" class="btn btn-outline-secondary" onclick="increaseQuantity(<?php echo $product['id']; ?>, <?php echo $product['stock']; ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-muted">Max: <?php echo $product['stock']; ?> available</small>
                        </div>
                        
                        <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                            <i class="fas fa-cart-plus me-2"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function increaseQuantity(productId, maxStock) {
    const input = document.getElementById('quantity_' + productId);
    let currentValue = parseInt(input.value);
    if (currentValue < maxStock) {
        input.value = currentValue + 1;
    }
}

function decreaseQuantity(productId) {
    const input = document.getElementById('quantity_' + productId);
    let currentValue = parseInt(input.value);
    if (currentValue > 1) {
        input.value = currentValue - 1;
    }
}

// Validate quantity on form submission
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const quantityInput = this.querySelector('input[name="quantity"]');
        const maxStock = parseInt(quantityInput.max);
        const quantity = parseInt(quantityInput.value);
        
        if (quantity < 1) {
            e.preventDefault();
            alert('Quantity must be at least 1!');
            return false;
        }
        
        if (quantity > maxStock) {
            e.preventDefault();
            alert('Quantity cannot exceed available stock!');
            return false;
        }
    });
});
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