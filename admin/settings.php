<?php
$pageTitle = "Settings - Local Fresh Market";
include '../includes/header.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4">Settings</h2>
            
            <!-- Admin Information -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Admin Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@localhost'); ?></p>
                            <p><strong>Role:</strong> Administrator</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Last Login:</strong> <?php echo date('M j, Y g:i A'); ?></p>
                            <p><strong>Account Created:</strong> <?php echo date('M j, Y'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                            <p><strong>Database:</strong> MySQL</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>System Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
                            <p><strong>Platform:</strong> Local Fresh Market</p>
                        </div>
                    </div>
                </div>
            </div>
<style>
.card {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.card-header {
    border-bottom: 1px solid #e0e0e0;
    font-weight: 600;
}

.status-item {
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.status-item:last-child {
    border-bottom: none;
}

.activity-item {
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.btn {
    border-radius: 6px;
    padding: 8px 16px;
}

.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}
</style>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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