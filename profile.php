<?php
$pageTitle = "My Profile - Local Fresh Market";
include 'includes/header.php';
redirectIfNotLoggedIn();
redirectIfAdmin();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, location = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$name, $location, $phone, $_SESSION['user_id']])) {
        $_SESSION['user_name'] = $name;
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } else {
        $error = "Failed to update profile!";
    }
}
?>
                    
<h2>My Profile</h2>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label"><strong>Name:</strong></label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><strong>Email:</strong></label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                <small class="text-muted">Email cannot be changed</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label"><strong>Location:</strong></label>
                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($user['location']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><strong>Phone:</strong></label>
                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
        </div>
    </div>
    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
</form>

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