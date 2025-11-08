<?php
// Start session and include config at the very top
include '../includes/config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add/Edit Product
    if (isset($_POST['save_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $product_id = $_POST['product_id'] ?? null;
        
        // Handle image upload
        $image_url = $_POST['existing_image'] ?? '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
                $image_url = 'assets/images/products/' . $file_name;
                
                // Delete old image if exists and we're uploading a new one
                if (!empty($_POST['existing_image']) && $_POST['existing_image'] != $image_url) {
                    $old_image_path = '../' . $_POST['existing_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            }
        }
        
        if ($product_id) {
            // Update existing product
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, image_url = ?, price = ?, stock = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $image_url, $price, $stock, $product_id])) {
                header("Location: products.php?updated=1");
                exit();
            } else {
                $error = "Failed to update product!";
            }
        } else {
            // Add new product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, image_url, price, stock) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $image_url, $price, $stock])) {
                header("Location: products.php?added=1");
                exit();
            } else {
                $error = "Failed to add product!";
            }
        }
    }
    
    // Delete product
    if (isset($_POST['delete_product'])) {
        $productId = $_POST['product_id'];
        
        try {
            // First get image path to delete file
            $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            // Delete from cart first (to avoid foreign key constraints)
            $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Delete from order_items first (to avoid foreign key constraints)
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$productId])) {
                // Delete image file if exists
                if (!empty($product['image_url'])) {
                    $image_path = '../' . $product['image_url'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                header("Location: products.php?deleted=1");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Cannot delete product: " . $e->getMessage();
        }
    }
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}

// Now include the rest
$pageTitle = "Manage Products - Local Fresh Market";
include '../includes/header.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Get all products with order count
$stmt = $pdo->query("
    SELECT p.*, COUNT(oi.id) as order_count 
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    GROUP BY p.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();
?>
                    
<h2>Manage Products</h2>

<!-- Success Messages -->
<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">Product added successfully!</div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Product updated successfully!</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Product deleted successfully!</div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Add/Edit Product Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas <?php echo $edit_product ? 'fa-edit' : 'fa-plus'; ?> me-2"></i>
            <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $edit_product ? $edit_product['id'] : ''; ?>">
            <input type="hidden" name="existing_image" value="<?php echo $edit_product ? $edit_product['image_url'] : ''; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="product_image" accept="image/*">
                        <small class="text-muted">Optional: Upload product image (JPG, PNG, GIF)</small>
                        <?php if ($edit_product && !empty($edit_product['image_url'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($edit_product['image_url']); ?>" 
                                     alt="Current image" 
                                     style="max-height: 100px; max-width: 100%;" 
                                     class="img-thumbnail">
                                <small class="d-block text-muted">Current image</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" 
                               value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" name="stock" min="0" 
                               value="<?php echo $edit_product ? $edit_product['stock'] : '0'; ?>" 
                               required>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="save_product" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> 
                    <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                </button>
                
                <?php if ($edit_product): ?>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Products List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Existing Products</h5>
        <span class="badge bg-primary"><?php echo count($products); ?> products</span>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                <h4>No products found</h4>
                <p class="mb-0">Add your first product using the form above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Details</th>
                            <th>Stock</th>
                            <th>Orders</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="<?php echo $product['stock'] == 0 ? 'table-warning' : ''; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover;" 
                                                 class="rounded me-3">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <?php if ($product['stock'] == 0): ?>
                                                <span class="badge bg-danger ms-1">Out of Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($product['description']); ?></small>
                                    <strong class="text-primary">₱<?php echo number_format($product['price'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        if ($product['stock'] > 10) echo 'success';
                                        elseif ($product['stock'] > 5) echo 'info';
                                        elseif ($product['stock'] > 0) echo 'warning';
                                        else echo 'danger';
                                    ?>">
                                        <?php echo $product['stock']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['order_count'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $product['order_count']; ?> orders</span>
                                    <?php else: ?>
                                        <span class="text-muted">No orders</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- Edit Button -->
                                        <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($product['name']); ?>? This will remove it from the database permanently.')">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

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