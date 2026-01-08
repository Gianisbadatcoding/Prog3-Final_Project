<?php
$page_title = "User Management";
require_once 'auth.php';
requireLogin();
requireManager(); // Extra protection

require_once 'includes/header.php';
require_once 'dbconnection.php';

$conn = getDBConnection();
$users = $conn->query("SELECT * FROM users ORDER BY user_id ASC");
?>

<main class="main-content">
    <div class="page-header">
        <div class="page-title-section">
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage employee accounts and access</p>
        </div>
        <div class="page-actions">
            <a href="register.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
            <span>
                <?php 
                if ($_GET['success'] == 'updated') echo "User updated successfully!";
                if ($_GET['success'] == 'deleted') echo "User deleted successfully!";
                ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <span class="alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <span><?php echo htmlspecialchars($_GET['error']); ?></span>
        </div>
    <?php endif; ?>

    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table-modern">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="id-cell">#<?php echo $row['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $row['role']; ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-edit" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($row['user_id'] != getCurrentUser()['user_id']): ?>
                                        <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('Are you sure? This cannot be undone.');"
                                           title="Delete User">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<style>
.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
}
.role-badge.manager {
    background: #e0e7ff;
    color: #4338ca;
}
.role-badge.employee {
    background: #dcfce7;
    color: #15803d;
}
</style>

<?php 
closeDBConnection($conn);
require_once 'includes/footer.php'; 
?>
