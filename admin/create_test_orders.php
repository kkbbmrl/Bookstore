<?php
require_once 'includes/header.php';

// Check if we have books and users
$booksQuery = $conn->query("SELECT id, title FROM books");
$usersQuery = $conn->query("SELECT id, name, email FROM users");

$books = $booksQuery->fetch_all(MYSQLI_ASSOC);
$users = $usersQuery->fetch_all(MYSQLI_ASSOC);

if (empty($books)) {
    die("No books found in the database. Please add some books first.");
}

if (empty($users)) {
    die("No users found in the database. Please add some users first.");
}

// Create test orders
$orderStatuses = ['pending', 'processing', 'shipped', 'delivered'];
$paymentMethods = ['Credit Card', 'PayPal', 'Cash on Delivery'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Create 5 test orders
    for ($i = 0; $i < 5; $i++) {
        // Select random user
        $user = $users[array_rand($users)];
        
        // Create order
        $status = $orderStatuses[array_rand($orderStatuses)];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        $totalAmount = 0;
        
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, status, total_amount, shipping_address, billing_address, payment_method, shipping_fee) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $shippingAddress = "123 Test Street\nTest City, 12345\nTest Country";
        $billingAddress = "123 Test Street\nTest City, 12345\nTest Country";
        $shippingFee = 5.00;
        
        $orderStmt->bind_param("isdsssd", 
            $user['id'],
            $status,
            $totalAmount,
            $shippingAddress,
            $billingAddress,
            $paymentMethod,
            $shippingFee
        );
        
        $orderStmt->execute();
        $orderId = $conn->insert_id;
        
        // Add 1-3 random books to the order
        $numBooks = rand(1, 3);
        $selectedBooks = array_rand($books, $numBooks);
        if (!is_array($selectedBooks)) {
            $selectedBooks = [$selectedBooks];
        }
        
        foreach ($selectedBooks as $bookIndex) {
            $book = $books[$bookIndex];
            $quantity = rand(1, 3);
            $price = rand(10, 50);
            
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            $itemStmt->bind_param("iiid", $orderId, $book['id'], $quantity, $price);
            $itemStmt->execute();
            
            $totalAmount += ($price * $quantity);
        }
        
        // Update order total
        $updateStmt = $conn->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
        $updateStmt->bind_param("di", $totalAmount, $orderId);
        $updateStmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    echo "Successfully created 5 test orders!";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error creating test orders: " . $e->getMessage();
}

// Redirect back to orders page after 3 seconds
header("refresh:3;url=orders.php");
?> 