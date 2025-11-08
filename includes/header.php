<?php
include 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Local Fresh Market'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
        }
        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .nav-link {
            color: #333;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .nav-link.active {
            background-color: #e8f5e8;
            color: var(--primary);
            font-weight: bold;
        }
        .nav-link:hover {
            background-color: #f1f8e9;
        }
        .welcome-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .main-content {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .cart-item {
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
        }
        .order-item {
            border-left: 4px solid var(--primary);
            padding-left: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'index.php' && basename($_SERVER['PHP_SELF']) != 'register.php'): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="sidebar p-3 mt-3">
                    <div class="welcome-header">
                        <h5>Welcome <?php echo $_SESSION['user_name']; ?></h5>
                        <small><?php echo $_SESSION['user_email']; ?></small>
                    </div>
                    <nav class="nav flex-column">
                        <?php if (isAdmin()): ?>
                            <!-- Admin Navigation -->
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                                <i class="fas fa-boxes me-2"></i> Manage Products
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                                <i class="fas fa-clipboard-list me-2"></i> View Orders
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        <?php else: ?>
                            <!-- User Navigation -->
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'marketplace.php' ? 'active' : ''; ?>" href="marketplace.php">
                                <i class="fas fa-store me-2"></i> Marketplace
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>" href="cart.php">
                                <i class="fas fa-shopping-cart me-2"></i> My Cart 
                                <?php
                                $cartCount = 0;
                                if (!isAdmin()) {
                                    $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $cartCount = $stmt->fetch()['count'];
                                }
                                if ($cartCount > 0): ?>
                                    <span class="badge bg-primary"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                                <i class="fas fa-clipboard-list me-2"></i> Orders
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                        <?php endif; ?>
                        
                        <div class="mt-3 pt-3 border-top">
                            <?php if (isAdmin()): ?>
                                <a class="nav-link text-danger" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            <?php else: ?>
                                <a class="nav-link text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            <?php endif; ?>
                        </div>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="main-content mt-3">
    <?php endif; ?>