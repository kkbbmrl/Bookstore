<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../connection.php';
// Include ChargilyPayService
require_once '../services/ChargilyPayService.php';

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
    $cart_check_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
    $cart_check_stmt = mysqli_prepare($conn, $cart_check_query);
    mysqli_stmt_bind_param($cart_check_stmt, "i", $user_id);
    mysqli_stmt_execute($cart_check_stmt);
    $cart_result = mysqli_stmt_get_result($cart_check_stmt);
    $cart_count = mysqli_fetch_assoc($cart_result)['count'];

    if ($cart_count == 0) {
        $_SESSION['order_error'] = "Your cart is empty. Please add items before checkout.";
        header("Location: checkout.php");
        exit;
    }
    
    // Get book IDs and quantities
    $book_ids = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    
    // Check stock availability before starting transaction
    $stock_error = false;
    $error_message = "The following item(s) are out of stock or have insufficient quantity: ";
    $out_of_stock_items = [];
    
    for ($i = 0; $i < count($book_ids); $i++) {
        $book_id = $book_ids[$i];
        $quantity = $quantities[$i];
        
        // Check available stock
        $stock_query = "SELECT title, stock_quantity FROM books WHERE id = ?";
        $stock_stmt = mysqli_prepare($conn, $stock_query);
        mysqli_stmt_bind_param($stock_stmt, "i", $book_id);
        mysqli_stmt_execute($stock_stmt);
        $stock_result = mysqli_stmt_get_result($stock_stmt);
        $book_data = mysqli_fetch_assoc($stock_result);
        
        if (!$book_data || $book_data['stock_quantity'] < $quantity) {
            $stock_error = true;
            $out_of_stock_items[] = $book_data['title'] . " (Requested: $quantity, Available: " . ($book_data['stock_quantity'] ?? 0) . ")";
        }
        
        mysqli_stmt_close($stock_stmt);
    }
    
    if ($stock_error) {
        $_SESSION['order_error'] = $error_message . implode(", ", $out_of_stock_items);
        header("Location: checkout.php");
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order in database
        $order_date = date('Y-m-d H:i:s');
        $status = 'pending'; // Changed from order_status to status to match DB schema
        
        $order_query = "INSERT INTO orders (user_id, order_date, status, total_amount, 
                        shipping_address, billing_address, payment_method, shipping_fee) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $shipping_address = json_encode([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'postcode' => $postcode,
            'phone' => $phone,
            'email' => $email,
            'company' => $company
        ]);
        
        $billing_address = $shipping_address; // Use same address for billing
        
        $stmt = mysqli_prepare($conn, $order_query);
        mysqli_stmt_bind_param($stmt, "issssssd", 
            $user_id, $order_date, $status, $total, 
            $shipping_address, $billing_address, $payment_method, $shipping);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error creating order: " . mysqli_stmt_error($stmt));
        }
        
        // Get order ID
        $order_id = mysqli_insert_id($conn);
        
        // Insert order items and update inventory
        for ($i = 0; $i < count($book_ids); $i++) {
            $book_id = $book_ids[$i];
            $quantity = $quantities[$i];
            $price = $prices[$i];
            
            // Insert order item
            $item_query = "INSERT INTO order_items (order_id, book_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_query);
            mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $book_id, $quantity, $price);
            
            if (!mysqli_stmt_execute($item_stmt)) {
                throw new Exception("Error adding order item: " . mysqli_stmt_error($item_stmt));
            }
            
            // Update book inventory using correct column name stock_quantity
            $update_query = "UPDATE books SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "iii", $quantity, $book_id, $quantity);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Error updating inventory for book: " . $book_data['title']);
            }
            
            // We already verified stock, but as a safety measure, check affected rows
            if (mysqli_stmt_affected_rows($update_stmt) === 0) {
                // Get book title for better error message
                $book_query = "SELECT title FROM books WHERE id = ?";
                $book_stmt = mysqli_prepare($conn, $book_query);
                mysqli_stmt_bind_param($book_stmt, "i", $book_id);
                mysqli_stmt_execute($book_stmt);
                $book_result = mysqli_stmt_get_result($book_stmt);
                $book_title = mysqli_fetch_assoc($book_result)['title'] ?? "Unknown book";
                mysqli_stmt_close($book_stmt);
                
                throw new Exception("Unexpected inventory problem with: " . $book_title);
            }
            
            mysqli_stmt_close($item_stmt);
            mysqli_stmt_close($update_stmt);
        }
        
        // Update user profile with shipping information if needed
        // Only update the fields that exist in the users table: name, address, phone
        $update_user_query = "UPDATE users SET name = ?, address = ?, phone = ? WHERE id = ?";
        $user_stmt = mysqli_prepare($conn, $update_user_query);
        $full_name = $first_name . ' ' . $last_name; // Combine first and last name since users table has single name field
        mysqli_stmt_bind_param($user_stmt, "sssi", $full_name, $address, $phone, $user_id);
        mysqli_stmt_execute($user_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Handle payment method
        if ($payment_method === 'Chargily Pay') {
            try {
                // Initialize Chargily Pay service
                $chargilyPayService = new \Bookstore\Services\ChargilyPayService();
                $chargilyClient = $chargilyPayService->getClient();
                
                // Prepare line items for Chargily checkout
                $lineItems = [];
                for ($i = 0; $i < count($book_ids); $i++) {
                    $book_id = $book_ids[$i];
                    $quantity = $quantities[$i];
                    $price = $prices[$i];
                    
                    // Get book details
                    $book_query = "SELECT title FROM books WHERE id = ?";
                    $book_stmt = mysqli_prepare($conn, $book_query);
                    mysqli_stmt_bind_param($book_stmt, "i", $book_id);
                    mysqli_stmt_execute($book_stmt);
                    $book_result = mysqli_stmt_get_result($book_stmt);
                    $book_data = mysqli_fetch_assoc($book_result);
                    
                    // Format price for Chargily - they require specific valid price values
                    // Converting to integer (no decimals) and ensuring it's at least 100 (1 DZD/TND)
                    $priceInSmallestUnit = (int)round($price * 100);
                    
                    // Ensure price meets minimum requirement and is acceptable by Chargily
                    if ($priceInSmallestUnit < 100) {
                        $priceInSmallestUnit = 100; // Minimum price
                    }
                    
                    // Create a product first
                    $product = $chargilyClient->products()->create([
                        'name' => $book_data['title'],
                        'description' => 'Book: ' . $book_data['title']
                    ]);
                    
                    if (!$product) {
                        throw new Exception("Failed to create product for book: " . $book_data['title']);
                    }
                    
                    // Create a price for the product
                    $priceObj = $chargilyClient->prices()->create([
                        'product_id' => $product->getId(),
                        'amount' => $priceInSmallestUnit,
                        'currency' => 'dzd'
                    ]);
                    
                    if (!$priceObj) {
                        throw new Exception("Failed to create price for book: " . $book_data['title']);
                    }
                    
                    $lineItems[] = [
                        'price' => $priceObj->getId(),
                        'quantity' => $quantity
                    ];
                    
                    mysqli_stmt_close($book_stmt);
                }
                
                // Add debug logging to see what we're sending
                error_log("Attempting Chargily checkout with items: " . json_encode($lineItems));
                
                // Ensure the protocol matches what your site is using
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                
                // Add debug logging
                error_log("Creating Chargily checkout for order #$order_id");
                
                // Create checkout with only supported parameters according to error message
                $checkoutParams = [
                    'success_url' => $protocol . $host . '/Bookstore/pages/order_confirmation.php?order_id=' . $order_id,
                    'failure_url' => $protocol . $host . '/Bookstore/pages/checkout.php',
                    'description' => 'Bookstore Order #' . $order_id,
                    'locale' => 'en',
                    'items' => $lineItems,
                    'metadata' => [
                        'order_id' => $order_id,
                        'customer_email' => $email,
                        'customer_name' => $first_name . ' ' . $last_name
                    ]
                ];
                
                // Remove unsupported parameters (cancel_url, customer_email, customer_name)
                error_log("Sending Chargily checkout request with params: " . json_encode($checkoutParams));
                $checkout = $chargilyClient->checkouts()->create($checkoutParams);
                
                // Debug the checkout object to see its structure
                error_log("Chargily checkout object structure: " . print_r($checkout, true));
                
                // Use the correct property names based on the object structure
                // Let's first check if the checkout object is valid
                if (!$checkout) {
                    throw new Exception("Failed to create Chargily checkout - null response");
                }
                
                // Get the checkout ID using the proper method
                $checkout_id = $checkout->getId();
                if (!$checkout_id) {
                    throw new Exception("Could not determine Chargily checkout ID");
                }
                
                // Log successful checkout creation
                error_log("Chargily checkout created successfully with ID: " . $checkout_id);
                
                // Save Chargily checkout ID to the order
                $update_order = "UPDATE orders SET chargily_checkout_id = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_order);
                mysqli_stmt_bind_param($update_stmt, "si", $checkout_id, $order_id);
                mysqli_stmt_execute($update_stmt);
                
                // Get the checkout URL using the proper method
                $checkout_url = $checkout->getUrl();
                if (!$checkout_url) {
                    throw new Exception("Could not determine Chargily checkout URL");
                }
                
                // Clear cart items from database for Chargily Pay orders too
                $clear_cart_query = "DELETE FROM cart_items WHERE user_id = ?";
                $clear_cart_stmt = mysqli_prepare($conn, $clear_cart_query);
                mysqli_stmt_bind_param($clear_cart_stmt, "i", $user_id);
                mysqli_stmt_execute($clear_cart_stmt);
                
                // Redirect to Chargily checkout URL
                header("Location: " . $checkout_url);
                exit;
                
            } catch (\Exception $e) {
                // Payment processing failed
                $_SESSION['order_error'] = "Payment processing failed: " . $e->getMessage();
                header("Location: checkout.php");
                exit;
            }
        } else {
            // Clear cart items from database
            $clear_cart_query = "DELETE FROM cart_items WHERE user_id = ?";
            $clear_cart_stmt = mysqli_prepare($conn, $clear_cart_query);
            mysqli_stmt_bind_param($clear_cart_stmt, "i", $user_id);
            mysqli_stmt_execute($clear_cart_stmt);
            
            // Set success message
            $_SESSION['order_success'] = "Your order has been placed successfully! Your order ID is: " . $order_id;
            
            // Redirect to order confirmation page
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit;
        }
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
?>
