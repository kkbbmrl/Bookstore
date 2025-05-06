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

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user's orders
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Fassila Book Store</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <!-- Favicon links -->
    <style>
        .orders-section {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .orders-table th, .orders-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        .no-orders {
            text-align: center;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 1.5rem;
        }
        .btn-small {
            padding: 0.25rem 0.5rem;
            background-color: #6c5dd4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #6c5dd4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 1rem;
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
            <li><a href="#">My Orders</a></li>
        </ul>
    </div>
    
    <section class="orders-section page">
        <div class="orders-container">
            <h2>My Orders</h2>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="book-filter.php" class="btn">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo $order['order_status']; ?></td>
                                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn-small">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
