<?php
require_once 'auth.php';
requireManager(); // Only managers can update items

require_once 'dbconnection.php';

$error = '';
$success = '';
$item = null;

// Get item ID
$item_id = $_GET['id'] ?? null;

if (!$item_id) {
    header("Location: items.php");
    exit();
}

$conn = getDBConnection();

// Get item data
$stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    closeDBConnection($conn);
    header("Location: items.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $isles = trim($_POST['isles'] ?? '');
    $shelf_position = trim($_POST['shelf_position'] ?? '');
    
    $image_path = $item['image_path'];
    
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
                // Determine if we need to store full path or relative. 
                // Based on create.php, we storing "uploads/filename".
                $image_path = $upload_path;
            } else {
                $error = "Error uploading image.";
            }
        }
    }
    
    // Validation
    if ($error) {
        // Skip
    } elseif (empty($item_name) || empty($price) || empty($quantity) || empty($category) || empty($isles) || empty($shelf_position)) {
        $error = "All fields are required!";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Price must be a positive number!";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error = "Quantity must be a non-negative number!";
    } else {
        $stmt = $conn->prepare("UPDATE items SET item_name = ?, price = ?, quantity = ?, category = ?, image_path = ?, isles = ?, shelf_position = ? WHERE item_id = ?");
        $stmt->bind_param("sdissssi", $item_name, $price, $quantity, $category, $image_path, $isles, $shelf_position, $item_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            closeDBConnection($conn);
            header("Location: items.php?success=updated");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - INCONVINIENCE STORE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container">
        <h1>Edit Product (ID: <?php echo htmlspecialchars($item['item_id']); ?>)</h1>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Current Image</label>
                    <?php if (!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                        <div class="current-image" style="margin: 10px 0;">
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="Product Image" style="max-width: 150px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic;">No image available</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="image">Change Image <span class="optional">(Optional)</span></label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="item_name">Item Name <span class="required">*</span></label>
                    <input type="text" id="item_name" name="item_name" required 
                           value="<?php echo htmlspecialchars($item['item_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="price">Price <span class="required">*</span></label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required 
                           value="<?php echo htmlspecialchars($item['price']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity <span class="required">*</span></label>
                    <input type="number" id="quantity" name="quantity" min="0" required 
                           value="<?php echo htmlspecialchars($item['quantity']); ?>">
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Category</option>
                        <?php 
                        $categories = ['Beverages', 'Snacks', 'Canned Goods', 'Household', 'Personal Care', 'Others'];
                        foreach ($categories as $cat) {
                            $selected = isset($item['category']) && $item['category'] == $cat ? 'selected' : '';
                            echo "<option value=\"$cat\" $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="isles">Aisle <span class="required">*</span></label>
                    <input type="text" id="isles" name="isles" required 
                           value="<?php echo htmlspecialchars($item['isles']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="shelf_position">Shelf Position <span class="required">*</span></label>
                    <input type="text" id="shelf_position" name="shelf_position" required 
                           value="<?php echo htmlspecialchars($item['shelf_position']); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="items.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


