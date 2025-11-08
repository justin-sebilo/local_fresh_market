<?php
// Clear any existing session when accessing register page
session_start();
session_destroy();
session_start();

include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    $user_type = $_POST['user_type'];
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Email already exists!";
    } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, location, phone, user_type) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $location, $phone, $user_type])) {
            // Auto-login after registration
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect based on user type
            if ($user['user_type'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Registration failed!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Local Fresh Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
        }
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo h1 {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .logo p {
            color: #666;
            margin-bottom: 0;
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        .user-type-selector {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        .user-type-selector.selected {
            border-color: var(--primary);
            background-color: #f8fff9;
        }
    </style>
    <script>
        function selectUserType(type) {
            document.querySelectorAll('.user-type-selector').forEach(selector => {
                selector.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            document.getElementById('user_type').value = type;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="logo">
                <h1>Local Fresh Market</h1>
                <p>Create Your Account</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name/Business Name</label>
                    <input type="text" class="form-control" name="name" placeholder="Jhuko" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" placeholder="jhuko@gmail.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location" placeholder="pagadian" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number (Optional)</label>
                    <input type="tel" class="form-control" name="phone" placeholder="9300469637">
                </div>

                <!-- User Type Selection -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Account Type</label>
                    <div class="user-type-selector selected" onclick="selectUserType('customer')">
                        <input type="radio" name="user_type_radio" checked> 
                        <strong>Customer</strong> - Browse and purchase products
                    </div>
                    <div class="user-type-selector" onclick="selectUserType('admin')">
                        <input type="radio" name="user_type_radio"> 
                        <strong>Admin</strong> - Manage products and orders
                    </div>
                    <input type="hidden" name="user_type" id="user_type" value="customer" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>
            </form>

            <div class="text-center">
                <a href="index.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>