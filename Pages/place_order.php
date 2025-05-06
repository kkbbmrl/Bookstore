<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['order_error'] = "You must be logged in to place an order.";
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get form data for shipping details
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $address = htmlspecialchars(trim($_POST['address']));
    $city = htmlspecialchars(trim($_POST['city']));
    $state = htmlspecialchars(trim($_POST['state']));
    $country = htmlspecialchars(trim($_POST['country']));
    $postcode = htmlspecialchars(trim($_POST['postcode']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $company = isset($_POST['company']) ? htmlspecialchars(trim($_POST['company'])) : '';
    
    // Get payment details
    $payment_method = htmlspecialchars(trim($_POST['payment_method']));
    
    // Get order details
    $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
    $shipping = isset($_POST['shipping']) ? floatval($_POST['shipping']) : 0;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;
    
    // Additional payment details if using card
    $card_holder = '';
    $card_number = '';
    $expiry = '';
    $cvv = '';
    
    if ($payment_method == 'Credit Card' || $payment_method == 'Debit Card') {
        $card_holder = isset($_POST['card_holder']) ? htmlspecialchars(trim($_POST['card_holder'])) : '';
        $card_number = isset($_POST['card_number']) ? htmlspecialchars(trim($_POST['card_number'])) : '';
        $expiry = isset($_POST['expiry']) ? htmlspecialchars(trim($_POST['expiry'])) : '';
        $cvv = isset($_POST['cvv']) ? htmlspecialchars(trim($_POST['cvv'])) : '';
        
        // Basic validation for card details
        if (empty($card_holder) || empty($card_number) || empty($expiry) || empty($cvv)) {
            $_SESSION['order_error'] = "Please fill in all card details.";
            header("Location: checkout.php");
            exit;
        }
        
        // Note: In a production environment, use a payment gateway instead of storing card details
    }
    
    // Validate cart is not empty
    if (empty($_SESSION['cart'])) {
        $_SESSION['order_error'] = "Your cart is empty. Please add items before checkout.";
        header("Location: checkout.php");
        exit;
    }
    
    // Get book IDs and quantities
    $book_ids = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order in database
        $order_date = date('Y-m-d H:i:s');
        $order_status = 'Pending';
        
        $order_query = "INSERT INTO orders (user_id, order_date, order_status, first_name, last_name, 
                        address, city, state, country, postcode, email, phone, company, 
                        payment_method, subtotal, shipping, discount, total) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $order_query);
        mysqli_stmt_bind_param($stmt, "isssssssssssssdddd", 
            $user_id, $order_date, $order_status, $first_name, $last_name,
            $address, $city, $state, $country, $postcode, $email, $phone, $company,
            $payment_method, $subtotal, $shipping, $discount, $total);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error creating order: " . mysqli_stmt_error($stmt));
        }
        
        // Get order ID
        $order_id = mysqli_insert_id($conn);
        
        // Insert order items
        for ($i = 0; $i < count($book_ids); $i++) {
            $book_id = $book_ids[$i];
            $quantity = $quantities[$i];
            $price = $prices[$i];
            
            $item_query = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_query);
            mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $book_id, $quantity, $price);
            
            if (!mysqli_stmt_execute($item_stmt)) {
                throw new Exception("Error adding order item: " . mysqli_stmt_error($item_stmt));
            }
            
            // Update book inventory (assuming you have a quantity column in books table)
            $update_query = "UPDATE books SET stock = stock - ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ii", $quantity, $book_id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Error updating inventory: " . mysqli_stmt_error($update_stmt));
            }
            
            mysqli_stmt_close($item_stmt);
            mysqli_stmt_close($update_stmt);
        }
        
        // Update user profile with shipping information if needed
        $update_user_query = "UPDATE users SET first_name = ?, last_name = ?, address = ?, city = ?, 
                              state = ?, postcode = ?, phone = ? WHERE id = ?";
        $user_stmt = mysqli_prepare($conn, $update_user_query);
        mysqli_stmt_bind_param($user_stmt, "sssssssi", $first_name, $last_name, $address, $city, $state, 
                              $postcode, $phone, $user_id);
        mysqli_stmt_execute($user_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Set success message
        $_SESSION['order_success'] = "Your order has been placed successfully! Your order ID is: " . $order_id;
        
        // Redirect to order confirmation page
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        $_SESSION['order_error'] = "Error placing order: " . $e->getMessage();
        header("Location: checkout.php");
        exit;
    }
    
} else {
    // If not POST request, redirect to checkout
    header("Location: checkout.php");
    exit;
}
