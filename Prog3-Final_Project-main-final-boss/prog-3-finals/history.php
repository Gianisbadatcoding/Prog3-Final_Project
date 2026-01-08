<?php
$page_title = "Sales History";
require_once 'auth.php';
requireLogin();
require_once 'includes/header.php';
require_once 'dbconnection.php';

$conn = getDBConnection();

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// Get total count
$count_res = $conn->query("SELECT COUNT(*) as total FROM transactions");
$total_txns = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_txns / $per_page);

// Fetch Transactions with User info
$sql = "SELECT t.*, u.full_name as cashier_name 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.user_id 
        ORDER BY t.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="main-content">
    <div class="page-header">
        <div class="page-title-section">
            <h1 class="page-title">Sales History</h1>
            <p class="page-subtitle">View past transactions</p>
        </div>
    </div>

    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table-modern">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date & Time</th>
                        <th>Cashier</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="id-cell">#<?php echo str_pad($row['transaction_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="user-badge">
                                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($row['cashier_name']); ?>
                                    </span>
                                </td>
                                <td class="price-cell">&#8369;<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td>
                                    <button class="btn btn-view" onclick="viewDetails(<?php echo $row['transaction_id']; ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper" style="margin-top: 20px;">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>
</main>

<!-- Transaction Details Modal -->
<div id="txModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div class="modal-content" style="background: white; margin: 5% auto; padding: 0; width: 600px; border-radius: 12px; max-height: 80vh; overflow-y: auto;">
        <div class="modal-header" style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.5em; color: var(--color-primary-dark);">Receipt Details</h2>
            <button onclick="closeModal()" style="border: none; background: none; font-size: 1.5em; cursor: pointer;">&times;</button>
        </div>
        <div id="modalBody" style="padding: 20px;">
            <div style="text-align: center; color: #666;">Loading...</div>
        </div>
        <div class="modal-footer" style="padding: 20px; border-top: 1px solid #eee; text-align: right;">
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function viewDetails(id) {
        document.getElementById('txModal').style.display = 'block';
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        
        fetch('api/get_transaction_details.php?id=' + id)
            .then(res => res.text())
            .then(html => {
                document.getElementById('modalBody').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('modalBody').innerHTML = '<div style="color: red;">Error loading details.</div>';
            });
    }

    function closeModal() {
        document.getElementById('txModal').style.display = 'none';
    }
    
    // Close on outside click
    window.onclick = function(event) {
        if (event.target == document.getElementById('txModal')) {
            closeModal();
        }
    }
</script>

<?php 
$stmt->close();
closeDBConnection($conn);
require_once 'includes/footer.php'; 
?>
