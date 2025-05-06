<?php
require_once '../connection.php';
session_start();

// Initialize cart variables
$cart_items = [];
$subtotal = 0;
$total = 0;
$shipping = 0; // Free shipping
$coupon_discount = 0;

// Get cart items if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Fetch cart items from database
    $stmt = $conn->prepare("
        SELECT c.id, c.book_id, c.quantity, b.title, b.price, b.old_price, b.cover_image 
        FROM cart_items c
        JOIN books b ON c.book_id = b.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Prepare cover image path
        $cover_image = !empty($row['cover_image']) ? $row['cover_image'] : "";
        
        // If it's just a filename, prepend the books directory path
        if (!empty($cover_image) && !strpos($cover_image, '/') && !strpos($cover_image, '\\')) {
            $cover_image = "../images/books/" . $cover_image;
        }
        
        // Verify the file exists, if not use default
        if (empty($cover_image) || !file_exists($cover_image)) {
            $cover_image = "../images/book-1.jpg";
        }
        
        // Store cart item data
        $item_total = $row['price'] * $row['quantity'];
        $subtotal += $item_total;
        
        $cart_items[] = [
            'id' => $row['id'],
            'book_id' => $row['book_id'],
            'title' => $row['title'],
            'price' => $row['price'],
            'old_price' => $row['old_price'],
            'quantity' => $row['quantity'],
            'cover_img' => $cover_image,
            'item_total' => $item_total
        ];
    }
    
    // Apply coupon if in session
    if (isset($_SESSION['coupon'])) {
        $coupon = $_SESSION['coupon'];
        if ($coupon['discount_type'] == 'percentage') {
            $coupon_discount = $subtotal * ($coupon['discount_value'] / 100);
        } else {
            $coupon_discount = $coupon['discount_value'];
        }
    }
    
    // Calculate final total
    $total = $subtotal - $coupon_discount + $shipping;
}

// Handle ajax requests for cart operations
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log in to manage your cart'
        ]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update':
            // Update cart item quantity
            if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            $cart_id = $_POST['cart_id'];
            $quantity = max(1, intval($_POST['quantity'])); // Ensure at least 1
            
            $update_stmt = $conn->prepare("
                UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?
            ");
            $update_stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            
            if ($update_stmt->execute()) {
                // Get updated price info
                $price_stmt = $conn->prepare("
                    SELECT b.price FROM cart_items c
                    JOIN books b ON c.book_id = b.id
                    WHERE c.id = ?
                ");
                $price_stmt->bind_param("i", $cart_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                $price_row = $price_result->fetch_assoc();
                
                // Calculate new totals
                $item_total = $price_row['price'] * $quantity;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cart updated',
                    'quantity' => $quantity,
                    'item_total' => $item_total
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            }
            exit;
            
        case 'remove':
            // Remove cart item
            if (!isset($_POST['cart_id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing cart item ID']);
                exit;
            }
            
            $cart_id = $_POST['cart_id'];
            
            $delete_stmt = $conn->prepare("
                DELETE FROM cart_items WHERE id = ? AND user_id = ?
            ");
            $delete_stmt->bind_param("ii", $cart_id, $user_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
            }
            exit;
            
        case 'apply_coupon':
            // Apply coupon code
            if (!isset($_POST['coupon_code'])) {
                echo json_encode(['success' => false, 'message' => 'No coupon code provided']);
                exit;
            }
            
            $coupon_code = $_POST['coupon_code'];
            
            // Check if coupon exists and is valid
            $coupon_stmt = $conn->prepare("
                SELECT * FROM coupons 
                WHERE code = ? 
                AND active = 1 
                AND valid_from <= NOW() 
                AND valid_to >= NOW() 
                AND (usage_limit IS NULL OR times_used < usage_limit)
            ");
            $coupon_stmt->bind_param("s", $coupon_code);
            $coupon_stmt->execute();
            $coupon_result = $coupon_stmt->get_result();
            
            if ($coupon_result->num_rows > 0) {
                $coupon = $coupon_result->fetch_assoc();
                
                // Check if user has already used this coupon
                $user_coupon_check = $conn->prepare("
                    SELECT id FROM user_coupons 
                    WHERE user_id = ? AND coupon_id = ?
                ");
                $user_coupon_check->bind_param("ii", $user_id, $coupon['id']);
                $user_coupon_check->execute();
                
                if ($user_coupon_check->get_result()->num_rows > 0) {
                    echo json_encode(['success' => false, 'message' => 'You have already used this coupon']);
                    exit;
                }
                
                // Check minimum purchase requirement
                if ($subtotal < $coupon['minimum_purchase']) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'You need to spend at least $' . number_format($coupon['minimum_purchase'], 2) . ' to use this coupon'
                    ]);
                    exit;
                }
                
                // Calculate discount
                if ($coupon['discount_type'] == 'percentage') {
                    $discount = $subtotal * ($coupon['discount_value'] / 100);
                    $discount_text = $coupon['discount_value'] . '% ($' . number_format($discount, 2) . ')';
                } else {
                    $discount = min($subtotal, $coupon['discount_value']); // Can't discount more than subtotal
                    $discount_text = '$' . number_format($discount, 2);
                }
                
                // Store in session
                $_SESSION['coupon'] = [
                    'id' => $coupon['id'],
                    'code' => $coupon['code'],
                    'discount_type' => $coupon['discount_type'],
                    'discount_value' => $coupon['discount_value'],
                    'discount' => $discount
                ];
                
                // Calculate new total
                $new_total = $subtotal - $discount + $shipping;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Coupon applied successfully',
                    'discount' => $discount_text,
                    'new_total' => '$' . number_format($new_total, 2)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
            }
            exit;
    }
}

// Get books on sale for the carousel
$sale_books = [];
$sale_stmt = $conn->prepare("
    SELECT id, title, price, old_price, cover_image 
    FROM books 
    WHERE old_price > price AND stock_quantity > 0
    ORDER BY (old_price - price) DESC
    LIMIT 7
");
$sale_stmt->execute();
$sale_result = $sale_stmt->get_result();

while ($row = $sale_result->fetch_assoc()) {
    // Check if we have a valid cover image
    $cover_image = !empty($row['cover_image']) ? $row['cover_image'] : "../images/book-1.jpg";
    
    // If it's just a filename, prepend the directory
    if (!strpos($cover_image, '/') && !strpos($cover_image, '\\')) {
        $cover_image = "../images/books/" . $cover_image;
    }
    
    // If file doesn't exist, use default
    if (!file_exists($cover_image)) {
        $cover_image = "../images/book-1.jpg";
    }
    
    // Calculate discount percentage
    $discount = 0;
    if ($row['old_price'] > 0) {
        $discount = round(100 - ($row['price'] / $row['old_price'] * 100));
    }
    
    $sale_books[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'price' => $row['price'],
        'old_price' => $row['old_price'],
        'cover_img' => $cover_image,
        'discount' => $discount
    ];
}

// If we don't have enough sale books, add some sample ones
if (count($sale_books) < 5) {
    $sample_books = [
        ['id' => 1, 'title' => 'The Giver', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-1.jpg'],
        ['id' => 2, 'title' => 'The Wright Brothers', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-2.jpg'],
        ['id' => 3, 'title' => 'The Ruins Of Gorlan', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-9.jpg'],
        ['id' => 4, 'title' => 'Percy Jackson', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-10.jpg'],
        ['id' => 5, 'title' => 'To Kill a Mockingbird', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-5.jpg'],
        ['id' => 6, 'title' => 'Harry Potter', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-6.jpg'],
        ['id' => 7, 'title' => 'Heroes of Olympus', 'price' => 45.40, 'old_price' => 90.80, 'discount' => 50, 'cover_img' => '../images/book-7.jpg']
    ];
    
    foreach ($sample_books as $book) {
        if (count($sale_books) < 7) {
            $sale_books[] = $book;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fassila - Shopping Cart</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="book-filter.css" />
    <!-- Fontawesome Link for Icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <!--- google font link-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />
    <link rel="apple-touch-icon" sizes="57x57" href="../favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="../favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <style>
      #toast-container {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 1000;
      }
      
      .toast {
          min-width: 250px;
          background-color: #6c5dd4;
          color: white;
          padding: 12px;
          border-radius: 4px;
          margin-top: 10px;
          box-shadow: 0 2px 5px rgba(0,0,0,0.2);
          opacity: 0;
          transition: opacity 0.3s ease-in-out;
      }
      
      .empty-cart {
          text-align: center;
          padding: 50px 20px;
      }
      
      .empty-cart i {
          font-size: 48px;
          color: #ccc;
          margin-bottom: 20px;
      }
      
      .empty-cart h3 {
          font-size: 24px;
          margin-bottom: 15px;
      }
      
      .empty-cart p {
          color: #666;
          margin-bottom: 25px;
      }
      
      .browse-button {
          display: inline-block;
          background: #6c5dd4;
          color: white;
          padding: 10px 20px;
          text-decoration: none;
          border-radius: 4px;
          font-weight: 500;
          transition: background 0.2s;
      }
      
      .browse-button:hover {
          background: #5a4cbe;
      }
      
      .product-table .quantity-field {
          width: 45px !important;
          text-align: center;
      }
      
      .fa-trash {
          cursor: pointer;
          color: #f44336;
          transition: color 0.2s;
      }
      
      .fa-trash:hover {
          color: #d32f2f;
      }
      
      .data td img {
          max-height: 80px;
          width: auto;
          object-fit: cover;
      }
    </style>
  </head>
  <body>
    <header>
      <nav class="navbar">
        <div class="logo">
          <div class="img">
            <img src="../images/logo.png" alt="" />
          </div>
          <div class="logo-header">
            <h4><a href="../index.php">Fassila</a></h4>
            <small>Book Store Website</small>
          </div>
        </div>
        <ul class="nav-list">
          <div class="logo">
            <div class="title">
              <div class="img">
                <img src="../images/logo.png" alt="" />
              </div>
              <div class="logo-header">
                <h4><a href="../index.php">Fassila</a></h4>
                <small>Book Store Website</small>
              </div>
            </div>
            <button class="close"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <li><a href="../index.php">Home</a></li>
          <li><a href="service.php">Service</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="book-filter.php">Books</a></li>
          
          <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <button class="logout">
              <i class="fa-solid fa-sign-out-alt"></i><a href="logout.php">Logout</a>
            </button>
          <?php else: ?>
            <button class="login"><a href="login.php">Log In</a></button>
            <button class="signup">
              <i class="fa-solid fa-user"></i><a href="registration.php">Sign Up</a>
            </button>
          <?php endif; ?>
        </ul>
        <div class="hamburger">
          <div class="line"></div>
          <div class="line"></div>
        </div>
      </nav>
    </header>
    
    <div class="breadcrumb-container">
        <ul class="breadcrumb">
          <li><a href="../index.php">Home</a></li>
          <li><a href="#">Cart</a></li>
        </ul>
    </div>

    <section class="cart-item page">
        <h2>Shopping Cart</h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our catalog and add books to your cart</p>
                <a href="book-filter.php" class="browse-button">Browse Books</a>
            </div>
        <?php else: ?>
            <div class="product-table">
                <table cellspacing=0 id="cart-table">
                    <tr class="heading">
                        <th>Product Image</th>
                        <th>Product Name</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Remove</th>
                    </tr>
                    <?php foreach ($cart_items as $item): ?>
                    <tr class="data" data-cart-id="<?php echo $item['id']; ?>">
                        <td>
                            <a href="book-detail.php?id=<?php echo $item['book_id']; ?>">
                                <img src="<?php echo htmlspecialchars($item['cover_img']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            </a>
                        </td>
                        <td><a href="book-detail.php?id=<?php echo $item['book_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($item['title']); ?></a></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <div class="input-group">
                                <div class="quantity">
                                    <input
                                      type="button"
                                      value="-"
                                      class="button-minus"
                                      data-field="quantity"
                                    />
                                    <input
                                      type="text"
                                      step="1"
                                      min="1"
                                      value="<?php echo $item['quantity']; ?>"
                                      name="quantity"
                                      class="quantity-field"
                                      data-cart-id="<?php echo $item['id']; ?>"
                                    />
                                    <input
                                      type="button"
                                      value="+"
                                      class="button-plus"
                                      data-field="quantity"
                                    />
                                </div>
                            </div>
                        </td>
                        <td class="item-total">$<?php echo number_format($item['item_total'], 2); ?></td>
                        <td>
                            <i class="fa-solid fa-trash remove-item" data-cart-id="<?php echo $item['id']; ?>"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($cart_items)): ?>
    <section class="discount-summary">
      <div class="discount-section">
        <h4>Discount Coupon</h4>
        <div class="discount-form">
          <input type="text" id="coupon-code" placeholder="Enter Coupon Code" style="text-transform: uppercase;">
          <button id="apply-coupon">Apply Coupon</button>
        </div>
      </div>
      <div class="summary-section">
        <h4>Cart Summary</h4>
        <div class="order-detail-table">
          <table>
            <tr>
              <td>Subtotal</td>
              <td id="subtotal">$<?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <tr>
              <td>Shipping</td>
              <td><?php echo ($shipping > 0) ? '$' . number_format($shipping, 2) : 'Free Shipping'; ?></td>
            </tr>
            <tr id="coupon-row" <?php echo ($coupon_discount > 0) ? '' : 'style="display: none;"'; ?>>
              <td>Coupon Discount</td>
              <td id="coupon-discount">$<?php echo number_format($coupon_discount, 2); ?></td>
            </tr>
            <tr>
              <td>Total</td>
              <td id="total">$<?php echo number_format($total, 2); ?></td>
            </tr>
          </table>
          <button id="checkout-button"><a href="checkout.php">Proceed To Checkout</a></button>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="book-sale">
      <div class="heading">
        <h4>Books On Sale</h4>
        <div class="arrowbtn">
          <i id="left" class="fa-solid fa-angle-left"></i>
          <i id="right" class="fa-solid fa-angle-right"></i>
        </div>
      </div>
      <div class="book-container">
        <div class="wrapper">
          <ul class="carousel">
            <?php foreach ($sale_books as $book): ?>
            <li class="card">
              <div class="img">
                <a href="book-detail.php?id=<?php echo $book['id']; ?>">
                    <img src="<?php echo htmlspecialchars($book['cover_img']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" />
                </a>
                <span class="badge"><?php echo $book['discount']; ?>%</span>
              </div>
              <h5><?php echo htmlspecialchars(substr($book['title'], 0, 15) . (strlen($book['title']) > 15 ? '...' : '')); ?></h5>
              <div class="genre">
                <?php
                // Generate genres based on book title
                $title = strtolower($book['title']);
                if (strpos($title, 'harry') !== false || strpos($title, 'potter') !== false) {
                    echo '<span>fantasy,</span><span>magic</span>';
                } elseif (strpos($title, 'percy') !== false || strpos($title, 'olympus') !== false) {
                    echo '<span>mythology,</span><span>adventure</span>';
                } elseif (strpos($title, 'giver') !== false) {
                    echo '<span>dystopian,</span><span>sci-fi</span>';
                } elseif (strpos($title, 'mockingbird') !== false) {
                    echo '<span>classic,</span><span>drama</span>';
                } elseif (strpos($title, 'ruins') !== false || strpos($title, 'gorlan') !== false) {
                    echo '<span>adventure,</span><span>fantasy</span>';
                } else {
                    echo '<span>fiction,</span><span>bestseller</span>';
                }
                ?>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$<?php echo number_format($book['price'], 2); ?></span>
                  <span><strike>$<?php echo number_format($book['old_price'], 2); ?></strike></span>
                </div>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <footer>
      <div class="container">
        <div class="logo-description">
          <div class="logo">
            <div class="img">
              <img src="../images/logo.png" alt="" />
            </div>
            <div class="title">
              <h4>Fassila</h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <div class="logo-body">
            <p>
            Fassila is an Algerian online bookstore where stories begin with a comma 
            a space to pause, explore, and discover books that inspire. 
            From local voices to global classics, we bring the world of reading closer to you.
            </p>
          </div>
          <div class="social-links">
            <h4>Follow Us</h4>
            <ul class="links">
              <li>
                <a href=""><i class="fa-brands fa-facebook-f"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-youtube"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-twitter"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-linkedin"></i></a>
              </li>
              <li>
                <a href=""><i class="fa-brands fa-instagram"></i></a>
              </li>
            </ul>
          </div>
        </div>
        <div class="categories list">
          <h4>Book Categories</h4>
          <ul>
            <li><a href="book-filter.php">Action</a></li>
            <li><a href="book-filter.php">Adventure</a></li>
            <li><a href="book-filter.php">Comedy</a></li>
            <li><a href="book-filter.php">Crime</a></li>
            <li><a href="book-filter.php">Drama</a></li>
            <li><a href="book-filter.php">Fantasy</a></li>
            <li><a href="book-filter.php">Horror</a></li>
          </ul>
        </div>
        <div class="quick-links list">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="../index.php">About Us</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="book-filter.php">Products</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="registration.php">Sign Up</a></li>
            <li><a href="cart-item.php">Cart</a></li>
            <li><a href="checkout.php">Checkout</a></li>
          </ul>
        </div>
        <div class="our-store list">
          <h4>Our Store</h4>
          <div class="map" style="margin-top: 1rem">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6310.594819201665!2d-122.42768319999999!3d37.73616639999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808f7e60a337d5f5%3A0xfa0bb626904e5ab2!2z4KSV4KWJ4KSy4KWH4KScIOCkueCkv-Cksiwg4KS44KS-4KSoIOCkq-CljeCksOCkvuCkguCkuOCkv-CkuOCljeCkleCliywg4KSV4KWI4KSy4KWA4KSr4KWL4KSw4KWN4KSo4KS_4KSv4KS-LCDgpK_gpYLgpKjgpL7gpIfgpJ_gpYfgpKEg4KS44KWN4KSf4KWH4KSf4KWN4oCN4KS4!5e0!3m2!1shi!2sin!4v1686917463994!5m2!1shi!2sin"
              height="70"
              style="width: 100%; border: none; border-radius: 5px"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
          </div>
          <ul>
            <li>
              <a href=""
                ><i class="fa-solid fa-location-dot"></i>CENTRE SECTION 192 GROUPE 08 SETIF, SETIF, Wilaya de Setif 19000</a
              >
            </li>
            <li>
              <a href=""><i class="fa-solid fa-phone"></i>+12 1345678991</a>
            </li>
            <li>
              <a href=""
                ><i class="fa-solid fa-envelope"></i>support@Fassila.id</a
              >
            </li>
          </ul>
        </div>
      </div>
    </footer>
    
    <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>

    <div id="toast-container"></div>

    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js"
    integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
    ></script>
    <script src="../js/repeat-js.js"></script>
    <script src="../js/increment-decrement.js"></script>
    <script src="../js/back-to-top.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables for cart totals
            let subtotal = <?php echo $subtotal; ?>;
            let couponDiscount = <?php echo $coupon_discount; ?>;
            let shipping = <?php echo $shipping; ?>;
            
            // Handle quantity change
            const quantityFields = document.querySelectorAll('.quantity-field');
            quantityFields.forEach(field => {
                field.addEventListener('change', function() {
                    const cartId = this.getAttribute('data-cart-id');
                    const quantity = parseInt(this.value);
                    
                    // Update via AJAX
                    updateCartItem(cartId, quantity);
                });
            });
            
            // Event delegation for increment/decrement buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('button-plus') || e.target.classList.contains('button-minus')) {
                    // Find the quantity field
                    const quantityField = e.target.parentElement.querySelector('.quantity-field');
                    
                    // Wait for the increment-decrement.js script to update the field
                    setTimeout(() => {
                        const cartId = quantityField.getAttribute('data-cart-id');
                        const quantity = parseInt(quantityField.value);
                        
                        // Update via AJAX
                        updateCartItem(cartId, quantity);
                    }, 10);
                }
            });
            
            // Handle removing items
            const removeButtons = document.querySelectorAll('.remove-item');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const cartId = this.getAttribute('data-cart-id');
                    removeCartItem(cartId);
                });
            });
            
            // Handle apply coupon button
            const applyCouponButton = document.getElementById('apply-coupon');
            if (applyCouponButton) {
                applyCouponButton.addEventListener('click', function() {
                    const couponCode = document.getElementById('coupon-code').value.trim();
                    
                    if (!couponCode) {
                        showToast('Please enter a coupon code');
                        return;
                    }
                    
                    applyCoupon(couponCode);
                });
            }
            
            // Function to update cart item
            function updateCartItem(cartId, quantity) {
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('cart_id', cartId);
                formData.append('quantity', quantity);
                
                fetch('cart-item.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update item total in UI
                        const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
                        if (row) {
                            const itemTotalCell = row.querySelector('.item-total');
                            itemTotalCell.textContent = '$' + data.item_total.toFixed(2);
                            
                            // Recalculate page totals
                            updateTotals();
                            showToast('Cart updated successfully');
                        }
                    } else {
                        showToast(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while updating the cart');
                });
            }
            
            // Function to remove cart item
            function removeCartItem(cartId) {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('cart_id', cartId);
                
                fetch('cart-item.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove row from table
                        const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
                        if (row) {
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                
                                // Check if cart is now empty
                                const table = document.getElementById('cart-table');
                                if (table && table.rows.length <= 1) {
                                    // Only header row left, show empty cart
                                    document.querySelector('.cart-item.page').innerHTML = `
                                        <h2>Shopping Cart</h2>
                                        <div class="empty-cart">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                            <h3>Your cart is empty</h3>
                                            <p>Browse our catalog and add books to your cart</p>
                                            <a href="book-filter.php" class="browse-button">Browse Books</a>
                                        </div>
                                    `;
                                    
                                    // Hide summary section
                                    const summarySection = document.querySelector('.discount-summary');
                                    if (summarySection) {
                                        summarySection.style.display = 'none';
                                    }
                                } else {
                                    // Recalculate totals
                                    updateTotals();
                                }
                                
                                showToast('Item removed from cart');
                                
                                // Update cart count in header
                                updateCartCount();
                            }, 300);
                        }
                    } else {
                        showToast(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while removing the item');
                });
            }
            
            // Function to apply coupon
            function applyCoupon(couponCode) {
                const formData = new FormData();
                formData.append('action', 'apply_coupon');
                formData.append('coupon_code', couponCode);
                
                fetch('cart-item.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show coupon row
                        const couponRow = document.getElementById('coupon-row');
                        couponRow.style.display = '';
                        
                        // Update discount and total
                        document.getElementById('coupon-discount').textContent = data.discount;
                        document.getElementById('total').textContent = data.new_total;
                        
                        showToast('Coupon applied successfully');
                        
                        // Disable coupon form
                        document.getElementById('coupon-code').disabled = true;
                        document.getElementById('apply-coupon').disabled = true;
                    } else {
                        showToast(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while applying the coupon');
                });
            }
            
            // Function to update totals
            function updateTotals() {
                // Calculate subtotal from all item totals
                subtotal = 0;
                const itemTotals = document.querySelectorAll('.item-total');
                itemTotals.forEach(cell => {
                    // Convert "$XX.XX" to number
                    const itemTotal = parseFloat(cell.textContent.replace('$', ''));
                    subtotal += itemTotal;
                });
                
                // Update subtotal in UI
                document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
                
                // Recalculate total (with existing coupon discount)
                const total = subtotal - couponDiscount + shipping;
                document.getElementById('total').textContent = '$' + total.toFixed(2);
            }
            
            // Function to update cart count in header
            function updateCartCount() {
                // Get count from the number of rows in the cart table
                const table = document.getElementById('cart-table');
                let count = 0;
                
                if (table) {
                    count = table.rows.length - 1; // Subtract 1 for header row
                }
                
                // Update cart count in header
                const cartCountSpan = document.querySelector('.nav-end .cart span');
                if (cartCountSpan) {
                    cartCountSpan.textContent = count;
                }
            }
            
            // Function to show toast message
            function showToast(message) {
                const toastContainer = document.getElementById('toast-container');
                
                // Create toast element
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.textContent = message;
                
                // Add toast to container
                toastContainer.appendChild(toast);
                
                // Show the toast
                setTimeout(() => {
                    toast.style.opacity = '1';
                }, 10);
                
                // Hide and remove the toast after a delay
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        toastContainer.removeChild(toast);
                    }, 300);
                }, 3000);
            }
        });
    </script>
  </body>
</html>
