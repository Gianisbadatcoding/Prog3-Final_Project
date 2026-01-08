<?php
require_once '../auth.php';
requireLogin();
require_once '../dbconnection.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid ID");

$conn = getDBConnection();

// Get items
$sql = "SELECT ti.*, i.item_name, i.image_path 
        FROM transaction_items ti 
        LEFT JOIN items i ON ti.item_id = i.item_id 
        WHERE ti.transaction_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
?>

<div class="receipt-preview">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="text-align: left; padding: 10px;">Item</th>
                <th style="text-align: center; padding: 10px;">Qty</th>
                <th style="text-align: right; padding: 10px;">Price</th>
                <th style="text-align: right; padding: 10px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $subtotal = $row['quantity'] * $row['price_at_sale'];
                $total += $subtotal;
            ?>
            <tr style="border-bottom: 1px solid #f9f9f9;">
                <td style="padding: 10px;">
                    <?php echo htmlspecialchars($row['item_name']); ?>
                    <?php if (!$row['item_name']) echo "<em style='color:#999'>(Item Deleted)</em>"; ?>
                </td>
                <td style="text-align: center; padding: 10px;"><?php echo $row['quantity']; ?></td>
                <td style="text-align: right; padding: 10px;">&#8369;<?php echo number_format($row['price_at_sale'], 2); ?></td>
                <td style="text-align: right; padding: 10px;"><strong>&#8369;<?php echo number_format($subtotal, 2); ?></strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #333;">
                <td colspan="3" style="text-align: right; padding: 15px; font-weight: bold; font-size: 1.2em;">TOTAL</td>
                <td style="text-align: right; padding: 15px; font-weight: bold; font-size: 1.2em; color: var(--color-primary-dark);">
                    &#8369;<?php echo number_format($total, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>
    
    <div style="text-align: center; margin-top: 20px;">
        <p style="font-family: monospace; color: #666;">Transaction ID: #<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?></p>
        <button class="btn btn-secondary" onclick="window.print()" style="margin-top: 10px;">
            <i class="fas fa-print"></i> Print Receipt
        </button>

    </div>
</div>

<?php
$stmt->close();
closeDBConnection($conn);
?>
