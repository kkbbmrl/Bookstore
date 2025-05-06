<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../connection.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'pages/checkout.php';
    header("Location: login.php");
    exit;
}

// Fetch user information to prefill form
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user_info = mysqli_fetch_assoc($user_result);
}

// Fetch cart items from session or database (depending on your implementation)
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
$total = 0;
$shipping = 0; // Free shipping
$discount = 5; // Hardcoded discount for now

// Calculate totals with proper type casting
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        // Debugging: Log price and quantity values to a file
        error_log("Price: " . var_export($item['price'], true) . ", Quantity: " . var_export($item['quantity'], true) . "\n", 3, __DIR__ . "/debug.log");

        // Ensure price and quantity are properly cast to numeric types
        $price = is_numeric($item['price']) ? (float)$item['price'] : 0;
        $quantity = is_numeric($item['quantity']) ? (int)$item['quantity'] : 0;
        $subtotal += $price * $quantity;
    }
    $total = $subtotal - $discount;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fassila - Online Book Store</title>
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
          <div class="search-box">
            <div class="search-field">
              <input
                type="text"
                placeholder="Search over 30 million Book titles"
              />
              <button class="search-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
              </button>
            </div>
          </div>
          <div class="nav-end">
            <button class="likebtn">
              <i class="fa-regular fa-heart"></i> <span>35</span>
            </button>
            <button class="cart">
              <a href="cart-item.html"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
            </button>
            <div class="profile-img">
              <img
                src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&usqp=CAU"
                alt=""
              />
            </div>
          </div>
        </nav>
      </header>
      <div class="breadcrumb-container">
        <ul class="breadcrumb">
          <li><a href="#" style="color: #6c5dd4">Shop</a></li>
          <li><a href="#">Checkout</a></li>
        </ul>
      </div>

      <section class="checkout-section page">
        <h2>Checkout</h2>
        <?php if (!empty($_SESSION['order_error'])): ?>
          <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
            <?php echo $_SESSION['order_error']; unset($_SESSION['order_error']); ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['order_success'])): ?>
          <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
            <?php echo $_SESSION['order_success']; unset($_SESSION['order_success']); ?>
          </div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
          <div class="empty-cart" style="text-align: center; padding: 20px;">
            <p>Your cart is empty. Please add some books to your cart before checkout.</p>
            <a href="book-filter.php" style="display: inline-block; margin-top: 10px; padding: 10px 15px; background-color: #6c5dd4; color: white; text-decoration: none; border-radius: 5px;">Continue Shopping</a>
          </div>
        <?php else: ?>
        <form action="place_order.php" method="POST">
          <div class="main">
            <div class="checkout-form">
              <h4>Billing & Shipping Address</h4>
              <div class="form-container">
                <div class="form-control Country-field">
                  <select name="country" class="select-box" style="border: 1px solid #f0f0f0;padding: 5px 10px;height: 45px;border-radius: 5px;width: 100%;    appearance: none;
                  background-image: url(../images/chevron-down.svg);
                  background-repeat: no-repeat;
                  background-size: 16px 20px;
                  background-position: right 0.75rem center;font-size: 15px;outline: none;" required>
                    <option value="">Select Country</option>
                    <option value="USA">USA</option>
                    <option value="India">India</option>
                    <option value="Australia">Australia</option>
                    <option value="New Zealand">New Zealand</option>
                    <option value="Russia">Russia</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Africa">Africa</option>
                    <option value="Sri Lanka">Sri Lanka</option>
                    <option value="Pakistan">Pakistan</option>
                  </select>
                </div>
                <div class="name-field input-field">
                  <input type="text" name="first_name" placeholder="First Name" required 
                    value="<?php echo isset($user_info['first_name']) ? htmlspecialchars($user_info['first_name']) : ''; ?>">
                  <input type="text" name="last_name" placeholder="Last Name" required 
                    value="<?php echo isset($user_info['last_name']) ? htmlspecialchars($user_info['last_name']) : ''; ?>">
                </div>
                <div class="address-field">
                  <textarea name="address" rows="3" placeholder="Address" required><?php echo isset($user_info['address']) ? htmlspecialchars($user_info['address']) : ''; ?></textarea>
                </div>
                <div class="input-field">
                  <input type="text" name="city" placeholder="City / Town" required 
                    value="<?php echo isset($user_info['city']) ? htmlspecialchars($user_info['city']) : ''; ?>">
                  <input type="text" name="state" placeholder="State" required 
                    value="<?php echo isset($user_info['state']) ? htmlspecialchars($user_info['state']) : ''; ?>">
                </div>
                <div class="input-field">
                  <input type="text" name="company" placeholder="Company Name" 
                    value="<?php echo isset($user_info['company']) ? htmlspecialchars($user_info['company']) : ''; ?>">
                  <input type="email" name="email" placeholder="Email" required 
                    value="<?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : (isset($user_info['email']) ? htmlspecialchars($user_info['email']) : ''); ?>">
                </div>
                <div class="input-field">
                  <input type="text" name="phone" placeholder="Phone no." maxlength="12" required 
                    value="<?php echo isset($user_info['phone']) ? htmlspecialchars($user_info['phone']) : ''; ?>"
                    pattern="[0-9]{3}[0-9]{3}[0-9]{4}" title="Please enter a valid phone number (10 digits)">
                  <input type="text" name="postcode" placeholder="Postcode/Zip" required 
                    value="<?php echo isset($user_info['postcode']) ? htmlspecialchars($user_info['postcode']) : ''; ?>">
                </div>
              </div>
            </div>
            <div class="your-order">
              <h4>Your Order</h4>
              <div class="order-table">
                <table cellspacing="0">
                  <tr class="heading">
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Total</th>
                  </tr>
                  <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $item): ?>
                      <tr>
                        <td>
                          <img src="<?php echo $item['image']; ?>" alt="">
                        </td>
                        <td><?php echo $item['name']; ?></td>
                        <td>$<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
                      </tr>
                      <input type="hidden" name="book_ids[]" value="<?php echo $item['id']; ?>">
                      <input type="hidden" name="quantities[]" value="<?php echo $item['quantity']; ?>">
                      <input type="hidden" name="prices[]" value="<?php echo $item['price']; ?>">
                    <?php endforeach; ?>
                  <?php endif; ?>
                </table>
              </div>
            </div>
          </div>
      </section>
      <section class="detail-payment">
        <div class="summary-section">
          <h4>Order Total</h4>
          <div class="order-detail-table">
            <table>
              <tr>
                <td>Order Subtotal</td>
                <td>$<?php echo number_format($subtotal, 2); ?></td>
              </tr>
              <tr>
                <td>Shipping</td>
                <td>Free Shipping</td>
              </tr>
              <tr>
                <td>Coupon</td>
                <td>$<?php echo number_format($discount, 2); ?></td>
              </tr>
              <tr>
                <td>Total</td>
                <td>$<?php echo number_format($total, 2); ?></td>
              </tr>
            </table>
            <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
            <input type="hidden" name="shipping" value="0">
            <input type="hidden" name="discount" value="<?php echo $discount; ?>">
            <input type="hidden" name="total" value="<?php echo $total; ?>">
          </div>
        </div>
        <div class="payment-section">
          <h4>Payment Method</h4>
          <div class="payment-form">
            <div class="payment-option">
              <select name="payment_method" required>
                <option value="">Select Payment Method</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Debit Card">Debit Card</option>
                <option value="PayPal">PayPal</option>
                <option value="Cash On Delivery">Cash On Delivery</option>
              </select>
            </div>
            <div id="card-fields" style="display: none;">
              <div class="card-name">
                <input type="text" name="card_holder" placeholder="Card Holder Name">
              </div>
              <div class="card-no">
                <input type="text" name="card_number" placeholder="Card Number">
              </div>
              <div class="card-meta">
                <input type="text" name="expiry" placeholder="MM/YY" onfocus="(this.type='month')">
                <input type="text" name="cvv" placeholder="CVV" maxlength="3">
                <input type="text" name="postal" placeholder="Postal">
              </div>
            </div>
            <button type="submit" name="place_order">Place Order Now</button>
          </div>
        </div>
      </section>
      </form>
      <?php endif; ?>
      <footer>
        <div class="container">
          <div class="logo-description">
            <div class="logo">
              <div class="img">
                <img src="../images/logo.png" alt="">
              </div>
              <div class="title">
                <h4>Fassila</h4>
                <small>Book Store Website</small>
              </div>
            </div>
            <div class="logo-body">
              <p>Fassila is an Algerian online bookstore where stories begin with a comma — a space to pause, explore, and discover books that inspire. From local voices to global classics, we bring the world of reading closer to you.s</p>
            </div>
            <div class="social-links">
              <h4>Follow Us</h4>
              <ul class="links">
                <li><a href=""><i class="fa-brands fa-facebook-f"></i></a></li>
                <li><a href=""><i class="fa-brands fa-youtube"></i></a></li>
                <li><a href=""><i class="fa-brands fa-twitter"></i></a></li>
                <li><a href=""><i class="fa-brands fa-linkedin"></i></a></li>
                <li><a href=""><i class="fa-brands fa-instagram"></i></a></li>
              </ul>
            </div>
          </div>
          <div class="categories list">
            <h4>Book Categories</h4>
            <ul>
              <li><a href="">Action</a></li>
              <li><a href="">Adventure</a></li>
              <li><a href="">Comedy</a></li>
              <li><a href="">Crime</a></li>
              <li><a href="">Drama</a></li>
              <li><a href="">Fantasy</a></li>
              <li><a href="">Horror</a></li>
            </ul>
          </div>
          <div class="quick-links list">
            <h4>Quick Links</h4>
            <ul>
              <li><a href="../index.html">About Us</a></li>
              <li><a href="contact.html">Contact Us</a></li>
              <li><a href="book-filter.html">Products</a></li>
              <li><a href="login.php">Login</a></li>
              <li><a href="registration.php">Sign Up</a></li>
              <li><a href="cart-item.html">Cart</a></li>
              <li><a href="checkout.html">Checkout</a></li>
            </ul>
          </div>
          <div class="our-store list">
            <h4>Our Store</h4>
            <div class="map" style="margin-top: 1rem;">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6310.594819201665!2d-122.42768319999999!3d37.73616639999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808f7e60a337d5f5%3A0xfa0bb626904e5ab2!2z4KSV4KWJ4KSy4KWH4KScIOCkueCkv-Cksiwg4KS44KS-4KSoIOCkq-CljeCksOCkvuCkguCkuOCkv-CkuOCljeCkleCliywg4KSV4KWI4KSy4KWA4KSr4KWL4KSw4KWN4KSo4KS_4KSv4KS-LCDgpK_gpYLgpKjgpL7gpIfgpJ_gpYfgpKEg4KS44KWN4KSf4KWH4KSf4KWN4oCN4KS4!5e0!3m2!1shi!2sin!4v1686917463994!5m2!1shi!2sin" height="70" style="width: 100%;border: none;border-radius: 5px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <ul>
              <li><a href=""><i class="fa-solid fa-location-dot"></i>832 Thompson Drive,San Fransisco CA 94 107,United States</a></li>
              <li><a href=""><i class="fa-solid fa-phone"></i>+12 1345678991</a></li>
              <li><a href=""><i class="fa-solid fa-envelope"></i>support@Fassila.id</a></li>
            </ul>
          </div>
        </div>
      </footer>
      <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>
      <script src="../js/back-to-top.js"></script>
      <script>
        // Show/hide card fields based on payment method
        document.addEventListener('DOMContentLoaded', function() {
          const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
          const cardFields = document.getElementById('card-fields');
          
          if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', function() {
              if (this.value === 'Credit Card' || this.value === 'Debit Card') {
                cardFields.style.display = 'block';
              } else {
                cardFields.style.display = 'none';
              }
            });
          }
        });
      </script>
</body>
</html>