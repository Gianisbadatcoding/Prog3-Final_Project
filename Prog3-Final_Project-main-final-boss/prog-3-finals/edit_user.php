<?php
$page_title = "Edit User";
require_once 'auth.php';
requireLogin();
requireManager();

require_once 'includes/header.php';
require_once 'dbconnection.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: users.php?error=Invalid ID");
    exit();
}

$conn = getDBConnection();

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: users.php?error=User not found");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    if (empty($fullName)) {
        $error = "Full Name is required";
    } else {
        if (!empty($password)) {
            // Update with password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET full_name = ?, role = ?, password = ? WHERE user_id = ?");
            $update->bind_param("sssi", $fullName, $role, $hashed, $id);
        } else {
            // Update without password
            $update = $conn->prepare("UPDATE users SET full_name = ?, role = ? WHERE user_id = ?");
            $update->bind_param("ssi", $fullName, $role, $id);
        }
        
        if ($update->execute()) {
            header("Location: users.php?success=updated");
            exit();
        } else {
            $error = "Update failed: " . $conn->error;
        }
    }
}
?>

<main class="main-content">
    <div class="form-container">
        <h1>Edit User</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label>Username (Cannot be changed)</label>
                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #f1f5f9;">
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="search-input" style="width: 100%;">
                    <option value="employee" <?php if($user['role'] == 'employee') echo 'selected'; ?>>Employee</option>
                    <option value="manager" <?php if($user['role'] == 'manager') echo 'selected'; ?>>Manager</option>
                </select>
            </div>

            <div class="form-group">
                <label>New Password (Leave blank to keep current)</label>
                <input type="password" name="password" placeholder="••••••••">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php 
$stmt->close();
closeDBConnection($conn);
require_once 'includes/footer.php'; 
?>
