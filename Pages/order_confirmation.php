<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: checkout.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Fetch order details
$order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: index.php");
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Fetch order items
$items_query = "SELECT oi.*, b.title, b.image FROM order_items oi 
                JOIN books b ON oi.book_id = b.id 
                WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$order_items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Fassila Book Store</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <!-- Favicon links -->
    <link rel="apple-touch-icon" sizes="57x57" href="../favicon/apple-icon-57x57.png">
    <!-- Add the rest of favicon links as in checkout.php -->
    <style>
        .confirmation-section {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .order-success {
            background-color: #d4edda;
            padding: 1.5rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .order-success i {
            color: #28a745;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .order-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .order-info, .shipping-info {
            flex-basis: 48%;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .order-summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-summary th, .order-summary td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        .item-info {
            display: flex;
            align-items: center;
        }
        .item-info img {
            max-width: 60px;
            margin-right: 1rem;
        }
        .continue-shopping {
            text-align: center;
            margin-top: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #6c5dd4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 0.5rem;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar-2">
          <div class="logo">
            <div class="img">
              <img src="../images/logo.png" alt="" />
            </div>
            <div class="title">
              <h4>Fassila<i class="fa-solid fa-grid"></i></h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <!-- Rest of header code -->
        </nav>
    </header>
    
    <div class="breadcrumb-container">
        <ul class="breadcrumb">
            <li><a href="../index.php">Home</a></li>
            <li><a href="checkout.php">Checkout</a></li>
            <li><a href="#">Order Confirmation</a></li>
        </ul>
    </div>
    
    <section class="confirmation-section page">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <h2>Order Confirmation</h2>
                <div class="order-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <h3>Thank You for Your Order!</h3>
                    <p>Your order has been received and is being processed.</p>
                </div>
            </div>
            
            <div class="order-details">
                <div class="order-info">
                    <h4>Order Information</h4>
                    <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo $order['payment_method']; ?></p>
                    <p><strong>Status:</strong> <?php echo $order['order_status']; ?></p>
                </div>
                
                <div class="shipping-info">
                    <h4>Shipping Address</h4>
                    <p><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></p>
                    <p><?php echo $order['address']; ?></p>
                    <p><?php echo $order['city'] . ', ' . $order['state'] . ' ' . $order['postcode']; ?></p>
                    <p><?php echo $order['country']; ?></p>
                    <p>Email: <?php echo $order['email']; ?></p>
                    <p>Phone: <?php echo $order['phone']; ?></p>
                </div>
            </div>
            
            <div class="order-summary">
                <h4>Order Summary</h4>
                <table class="order-items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="item-info">
                                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>">
                                        <span><?php echo $item['title']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Subtotal</td>
                            <td>$<?php echo number_format($order['subtotal'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3">Shipping</td>
                            <td><?php echo ($order['shipping'] > 0) ? '$' . number_format($order['shipping'], 2) : 'Free'; ?></td>
                        </tr>
                        <tr>
                            <td colspan="3">Discount</td>
                            <td>$<?php echo number_format($order['discount'], 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3">Total</td>
                            <td>$<?php echo number_format($order['total'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="continue-shopping">
                <a href="book-filter.php" class="btn">Continue Shopping</a>
                <a href="user_orders.php" class="btn">View All Orders</a>
            </div>
        </div>
    </section>
    
    <!-- Include footer -->
    <footer>
        <!-- Footer content same as checkout.php -->
    </footer>
    
    <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>
    <script src="../js/back-to-top.js"></script>
</body>
</html>
