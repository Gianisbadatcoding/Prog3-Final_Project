<?php
require_once 'auth.php';
requireManager(); // Only managers can create items

require_once 'dbconnection.php';

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $isles = trim($_POST['isles'] ?? '');
    $shelf_position = trim($_POST['shelf_position'] ?? '');
    $image_path = null;
    
    // Image Upload Logic
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = $_FILES['image']['type'];
        $filesize = $_FILES['image']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = "Error: Please select a valid file format (jpg, jpeg, png, gif)";
        } else {
            // Create uploads directory if not exists
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $new_filename = uniqid() . "." . $ext;
            $upload_path = "uploads/" . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
            } else {
                $error = "Error uploading image.";
            }
        }
    }
    
    // Validation
    if ($error) {
        // Skip further processing if image error occurred
    } elseif (empty($item_name) || empty($price) || empty($quantity) || empty($category) || empty($isles) || empty($shelf_position)) {
        $error = "All fields are required!";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Price must be a positive number!";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error = "Quantity must be a non-negative number!";
    } else {
        $conn = getDBConnection();
        $currentUser = getCurrentUser();
        $user_id = $currentUser ? $currentUser['user_id'] : null;
        $stmt = $conn->prepare("INSERT INTO items (user_id, item_name, price, quantity, category, image_path, isles, shelf_position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddssss", $user_id, $item_name, $price, $quantity, $category, $image_path, $isles, $shelf_position);
        
        if ($stmt->execute()) {
            $stmt->close();
            closeDBConnection($conn);
            header("Location: items.php?success=created");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>
<?php
$page_title = "Add New Product";
require_once 'includes/header.php';
?>

<main class="main-content">
    <div class="page-header">
        <div class="page-title-section">
            <h1 class="page-title">Add New Product</h1>
            <p class="page-subtitle">Create a new product entry in the inventory</p>
        </div>
        <div class="page-actions">
            <a href="items.php" class="btn btn-secondary">← Back to Products</a>
        </div>
    </div>

    <section class="form-section">
        <div class="form-container-modern">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="product-form" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group-modern" style="grid-column: span 2;">
                        <label for="image" class="form-label">
                            <span class="label-icon"><i class="fas fa-image"></i></span>
                            Product Image
                            <span class="optional">(Optional)</span>
                        </label>
                        <input type="file" 
                               id="image" 
                               name="image" 
                               class="form-input-modern"
                               accept="image/*">
                    </div>

                    <div class="form-group-modern">
                        <label for="item_name" class="form-label">
                            <span class="label-icon"><i class="fas fa-tag"></i></span>
                            Product Name
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="item_name" 
                               name="item_name" 
                               class="form-input-modern"
                               placeholder="Enter product name"
                               required 
                               value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="price" class="form-label">
                            <span class="label-icon"><i class="fas fa-peso-sign"></i></span>
                            Price
                            <span class="required">*</span>
                        </label>
                        <div class="input-with-prefix">
                            <span class="input-prefix">&#8369;</span>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   class="form-input-modern"
                                   step="0.01" 
                                   min="0.01" 
                                   placeholder="0.00"
                                   required 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label for="category" class="form-label">
                            <span class="label-icon"><i class="fas fa-list"></i></span>
                            Category
                            <span class="required">*</span>
                        </label>
                        <select id="category" name="category" class="form-input-modern" required>
                            <option value="">Select Category</option>
                            <?php 
                            $categories = ['Beverages', 'Snacks','Fruits','Canned Goods', 'Household', 'Personal Care', 'Others'];
                            foreach ($categories as $cat) {
                                $selected = ($_POST['category'] ?? '') === $cat ? 'selected' : '';
                                echo "<option value=\"$cat\" $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="quantity" class="form-label">
                            <span class="label-icon"><i class="fas fa-boxes"></i></span>
                            Quantity
                            <span class="required">*</span>
                        </label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               class="form-input-modern"
                               min="0" 
                               placeholder="0"
                               required 
                               value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="isles" class="form-label">
                            <span class="label-icon"><i class="fas fa-map-marker-alt"></i></span>
                            Aisle
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="isles" 
                               name="isles" 
                               class="form-input-modern"
                               placeholder="e.g., Aisle 1"
                               required 
                               value="<?php echo htmlspecialchars($_POST['isles'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="shelf_position" class="form-label">
                            <span class="label-icon"><i class="fas fa-layer-group"></i></span>
                            Shelf Position
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="shelf_position" 
                               name="shelf_position" 
                               class="form-input-modern"
                               placeholder="e.g., Shelf A-3"
                               required 
                               value="<?php echo htmlspecialchars($_POST['shelf_position'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-actions-modern">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <a href="items.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>


