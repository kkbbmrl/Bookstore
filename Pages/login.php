<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fassila - Online Book Store website</title>
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
                <h4><a href="../index.php" style="text-decoration: none; color: inherit;">Fassila<i class="fa-solid fa-grid"></i></a></h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <div class="search-box">
  <form action="search.php" method="GET">
    <div class="search-field">
      <input
        type="text"
        name="query"
        placeholder="Search over 30 million Book titles"
        required
      />
      <button class="search-icon" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
  </form>
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
        <li><a href="../index.php" style="color: #6c5dd4">Home</a></li>
        <li><a href="#">Login</a></li>
      </ul>
    </div>

   <section class="login">
      <h3>Login</h3>
     <div class="login-form">
      <form action="login_process.php" method="post">
        <h4>Login</h4>
        <?php
        // Start session and display any error messages
        session_start();
        if(isset($_SESSION['login_error'])) {
          echo '<div class="error" style="color: red; margin-bottom: 15px;">' . $_SESSION['login_error'] . '</div>';
          unset($_SESSION['login_error']);
        }
        ?>
        <p>If you have an account with us, please log in.</p>
        <div class="input-form">
          <div class="input-field">
            <label for="email">Email *</label>
            <input type="email" name="email" id="email" placeholder="Your Email" required>
          </div>
          <div class="input-field">
            <label for="password">Password *</label>
            <input type="password" name="password" id="password" placeholder="Password" required>
          </div>
          
          <p>Forgot Password ?<a href=""> Click Here</a></p>
          <button type="submit" name="login">Login Account</button>
          <p>Don't Have an Account ? <a href="registration.php">Create Account</a></p>
        </div>
      </form>
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
              Lorem ipsum, dolor sit amet consectetur adipisicing elit. Magnam
              voluptates eius quasi reiciendis recusandae provident veritatis
              sequi, dolores architecto dolor possimus quos
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
            <li><a href="../index.php">About Us</a></li>
            <li><a href="contact.html">Contact Us</a></li>
            <li><a href="book-filter.html">Products</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="registration.php">Sign Up</a></li>
            <li><a href="cart-item.html">Cart</a></li>
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
    <script src="../js/back-to-top.js"></script>
  </body>
</html>
