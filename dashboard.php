<?php
$pageTitle = "Dashboard - Local Fresh Market";
include 'includes/header.php';
redirectIfNotLoggedIn();
redirectIfAdmin();
?>
                    
<h2>Dashboard</h2>
<div class="card mt-3">
    <div class="card-body">
        <h3>Welcome to Local Fresh Market</h3>
        <p class="mb-0">Your one-stop online marketplace for fresh, locally sourced products.</p>
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