<?php
require_once 'dbconnection.php';

try {
    $conn = getDBConnection();
    echo "Connected to database.\n";

    echo "Generating 100 dummy items...\n";

    // Pre-defined data for generation
    $categories = ['Beverages', 'Snacks', 'Canned Goods', 'Household', 'Personal Care', 'Others'];
    $adjectives = ['Premium', 'Organic', 'Imported', 'Local', 'Fresh', 'Spicy', 'Sweet', 'Family Size', 'Budget', 'Express'];
    $nouns = [
        'Beverages' => ['Cola', 'Orange Juice', 'Coffee', 'Tea', 'Water', 'Energy Drink', 'Soda', 'Milk'],
        'Snacks' => ['Chips', 'Cookies', 'Crackers', 'Nuts', 'Chocolate', 'Candy', 'Popcorn', 'Pretzels'],
        'Canned Goods' => ['Tuna', 'Sardines', 'Corned Beef', 'Peas', 'Corn', 'Beans', 'Soup', 'Spam'],
        'Household' => ['Detergent', 'Soap', 'Bleach', 'Tissue', 'Trash Bags', 'Sponge', 'Air Freshener'],
        'Personal Care' => ['Shampoo', 'Conditioner', 'Toothpaste', 'Deodorant', 'Lotion', 'Body Wash'],
        'Others' => ['Batteries', 'Light Bulb', 'Matches', 'Tape', 'Notebook', 'Pen']
    ];

    $stmt = $conn->prepare("INSERT INTO items (item_name, price, quantity, category, status, isles, shelf_position) VALUES (?, ?, ?, ?, 'active', ?, ?)");

    $conn->begin_transaction();

    for ($i = 0; $i < 100; $i++) {
        // Pick Category
        $category = $categories[array_rand($categories)];
        
        // Generate Name
        $nounList = $nouns[$category] ?? $nouns['Others'];
        $name = $adjectives[array_rand($adjectives)] . ' ' . $nounList[array_rand($nounList)] . ' ' . mt_rand(1, 100);

        // Generate Price (10.00 to 500.00)
        $price = mt_rand(1000, 50000) / 100;

        // Generate Quantity
        $quantity = mt_rand(10, 200);

        // Generate Location
        $isle = 'Aisle ' . mt_rand(1, 10);
        $shelf = 'Shelf ' . chr(mt_rand(65, 70)) . '-' . mt_rand(1, 5);

        $stmt->bind_param("sdisss", $name, $price, $quantity, $category, $isle, $shelf);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting item: " . $stmt->error);
        }

        if (($i + 1) % 10 == 0) {
            echo ".";
        }
    }

    $conn->commit();
    echo "\nSuccessfully inserted 100 items!\n";

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo "\nError: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) closeDBConnection($conn);
}
?>
