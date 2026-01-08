<?php
require_once 'auth.php';
requireLogin(); // All logged-in users can view items

require_once 'dbconnection.php';

$user = getCurrentUser();

// Get search parameter
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search_param = '';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$conn = getDBConnection();
$total_items = 0;
$total_pages = 1;

// Build role-based and search filters
$whereClauses = [];
$params = [];
$paramTypes = '';

// Regular employees see only their own items; managers see all
if (!isManager()) {
    $whereClauses[] = "user_id = ?";
    $params[] = $user['user_id'];
    $paramTypes .= 'i';
}

if (!empty($category_filter)) {
    $whereClauses[] = "category = ?";
    $params[] = $category_filter;
    $paramTypes .= 's';
}

if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $whereClauses[] = "(item_name LIKE ? OR isles LIKE ? OR shelf_position LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $paramTypes .= 'sss';
}

$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) AS total FROM items $whereSql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($paramTypes, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_assoc();
$total_items = (int) ($row['total'] ?? 0);
$count_stmt->close();

$total_pages = max(1, (int) ceil($total_items / $items_per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $items_per_page;

// Get current page of results
$data_sql = "SELECT * FROM items $whereSql ORDER BY item_id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($data_sql);

if (!empty($params)) {
    $fullParamTypes = $paramTypes . 'ii';
    $paramsWithPaging = array_merge($params, [$items_per_page, $offset]);
    $stmt->bind_param($fullParamTypes, ...$paramsWithPaging);
} else {
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<?php
$page_title = "Products";
require_once 'includes/header.php';
?>

<main class="main-content">
    <div class="page-header">
        <div class="page-title-section">
            <h1 class="page-title">Products</h1>
            <p class="page-subtitle">Manage your store inventory</p>
        </div>
        <div class="page-actions">
            <?php if (isManager()): ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            <?php endif; ?>
            <a href="pdf.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
            <span>
            <?php 
            if ($_GET['success'] == 'created') echo "Product added successfully!";
            elseif ($_GET['success'] == 'updated') echo "Product updated successfully!";
            elseif ($_GET['success'] == 'deleted') echo "Product deleted successfully!";
            ?>
            </span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 'delete_failed'): ?>
        <div class="alert alert-error">
            <span class="alert-icon"><i class="fas fa-times-circle"></i></span>
            <span>Failed to delete product. Please try again.</span>
        </div>
    <?php endif; ?>

    <section class="search-section">
        <form method="GET" action="items.php" class="search-form-modern">
            <div class="search-input-wrapper">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <select name="category" class="search-input-modern" style="width: 200px;">
                    <option value="">All Categories</option>
                    <?php 
                    $categories = ['Beverages', 'Snacks', 'Canned Goods', 'Household', 'Personal Care', 'Others'];
                    foreach ($categories as $cat) {
                        $selected = ($category_filter === $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
                </select>
                <input type="text" name="search" class="search-input-modern" 
                       placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary search-btn">Search</button>
                <?php if (!empty($search) || !empty($category_filter)): ?>
                    <a href="items.php" class="btn btn-secondary clear-btn">Clear</a>
                <?php endif; ?>
            </div>
            <?php if (!empty($search)): ?>
                <div class="search-results">
                    <p>
                        Showing <strong><?php echo $result->num_rows; ?></strong> 
                        of <strong><?php echo $total_items; ?></strong> result<?php echo $total_items != 1 ? 's' : ''; ?> 
                        for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    </p>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <section class="table-section">
        <div class="table-wrapper">
            <table class="data-table-modern">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Aisle</th>
                        <th>Shelf</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="image-cell">
                                    <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['item_name']); ?>" class="product-thumb">
                                    <?php else: ?>
                                        <div class="product-thumb" style="background: #eee; display: flex; align-items: center; justify-content: center; color: #aaa;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="id-cell">#<?php echo htmlspecialchars($row['item_id']); ?></td>
                                <td class="name-cell">
                                    <strong><?php echo htmlspecialchars($row['item_name']); ?></strong>
                                </td>
                                <td class="price-cell">&#8369;<?php echo number_format($row['price'], 2); ?></td>
                                <td class="category-cell"><?php echo htmlspecialchars($row['category'] ?? '-'); ?></td>
                                <td class="quantity-cell">
                                    <span class="quantity-badge <?php echo $row['quantity'] < 10 ? 'low-stock' : ''; ?>">
                                        <?php echo htmlspecialchars($row['quantity']); ?>
                                    </span>
                                </td>
                                <td class="aisle-cell"><?php echo htmlspecialchars($row['isles']); ?></td>
                                <td class="shelf-cell"><?php echo htmlspecialchars($row['shelf_position']); ?></td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo $row['item_id']; ?>" class="btn btn-view" title="View Details"><i class="fas fa-eye"></i></a>
                                        <?php if (isManager()): ?>
                                            <a href="update.php?id=<?php echo $row['item_id']; ?>" class="btn btn-edit" title="Edit Product"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?php echo $row['item_id']; ?>" 
                                               class="btn btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this product?');"
                                               title="Delete Product"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-message">
                                    <span class="empty-icon"><i class="fas fa-box-open"></i></span>
                                    <p>
                                        <?php if (!empty($search)): ?>
                                            No products found matching your search.
                                            <a href="items.php">View all products</a>
                                        <?php else: ?>
                                            No products found.
                                            <?php if (isManager()): ?>
                                                <a href="create.php">Add your first product</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($total_pages > 1): ?>
    <section class="pagination-section">
        <div class="pagination-wrapper">
            <?php
            // Helper to build page URL while preserving search
            function buildPageUrl($pageNumber, $searchTerm, $categoryTerm) {
                $params = ['page' => $pageNumber];
                if (!empty($searchTerm)) {
                    $params['search'] = $searchTerm;
                }
                if (!empty($categoryTerm)) {
                    $params['category'] = $categoryTerm;
                }
                return 'items.php?' . http_build_query($params);
            }
            ?>

            <ul class="pagination">
                <!-- Previous button -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <?php if ($page <= 1): ?>
                        <span class="page-link">&laquo; Prev</span>
                    <?php else: ?>
                        <a class="page-link" href="<?php echo buildPageUrl($page - 1, $search, $category_filter); ?>">&laquo; Prev</a>
                    <?php endif; ?>
                </li>

                <?php
                // Determine page range to show
                $max_links = 5;
                $start = max(1, $page - 2);
                $end = min($total_pages, $start + $max_links - 1);
                if ($end - $start + 1 < $max_links) {
                    $start = max(1, $end - $max_links + 1);
                }

                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php if ($i == $page): ?>
                            <span class="page-link"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?php echo buildPageUrl($i, $search, $category_filter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <!-- Next button -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <?php if ($page >= $total_pages): ?>
                        <span class="page-link">Next &raquo;</span>
                    <?php else: ?>
                        <a class="page-link" href="<?php echo buildPageUrl($page + 1, $search, $category_filter); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php 
if (isset($stmt)) {
    $stmt->close();
}
closeDBConnection($conn); 
require_once 'includes/footer.php'; 
?>

