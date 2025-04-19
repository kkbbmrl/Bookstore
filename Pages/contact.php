<?php
// Start session at the very beginning
session_start();
?>
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
            <h4>Fassila<i class="fa-solid fa-grid"></i></h4>
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
            <a href="cart-item.php"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
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
        <li><a href="contact.html">Contact</a></li>
      </ul>
    </div>

    <section class="contact">
      <h3>Contact Us</h3>
      <div class="main">
        <div class="map">
            <iframe
              src="https://maps.app.goo.gl/SAkXe8RfUDQ5izK38"
              height="70"
              style="width: 100%; border: none; border-radius: 5px"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </div>
        <div class="contact-form">
          <h4>Contact Us</h4>
          <p>Get in touch with us</p>
          
          <?php
          // Display success or error messages
          if(isset($_SESSION['contact_success'])) {
            echo '<div class="success-message" style="color: green; margin-bottom: 15px; background-color: #e8f5e9; padding: 10px; border-radius: 4px;">' . $_SESSION['contact_success'] . '</div>';
            unset($_SESSION['contact_success']);
          }
          if(isset($_SESSION['contact_error'])) {
            echo '<div class="error-message" style="color: red; margin-bottom: 15px; background-color: #ffebee; padding: 10px; border-radius: 4px;">' . $_SESSION['contact_error'] . '</div>';
            unset($_SESSION['contact_error']);
          }
          ?>
          
          <form action="contact_process.php" method="post">
            <div class="input-form">
              <div class="input-field">
                <label for="name">Full Name *</label>
                <input type="text" name="name" id="name" placeholder="Full Name" required />
              </div>
              <div class="input-field">
                <label for="email">E-mail *</label>
                <input type="email" name="email" id="email" placeholder="Email Address" required />
              </div>
              <div class="input-field">
                <label for="phone">Phone No. *</label>
                <input type="text" name="phone" id="phone" placeholder="Phone Number" required />
              </div>
              <div class="message">
                <label for="message">Message *</label>
                <textarea placeholder="Message" id="message" name="message" required></textarea>
              </div>
              <button type="submit" name="submit">Submit</button>
            </div>
          </form>
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
            <li><a href="checkout.html">Checkout</a></li>
          </ul>
        </div>
        <div class="our-store list">
          <h4>Our Store</h4>
          <div class="map" style="margin-top: 1rem">
            <iframe
              src="https://www.google.pl/maps/place/La+Citadelle/@36.0731987,4.7631778,17.63z/data=!4m15!1m8!3m7!1s0x128c913befaefcf7:0x2bf41af2fb4d69b3!2sBordj+Bou+Arreridj!3b1!8m2!3d36.0739925!4d4.7630271!16zL20vMDVzN193!3m5!1s0x128cbda273a1b465:0xdf3406ca7ab19b58!8m2!3d36.0730004!4d4.7636508!16s%2Fg%2F11q_c3n8tt?entry=ttu&g_ep=EgoyMDI1MDQxMy4wIKXMDSoJLDEwMjExNjQwSAFQAw%3D%3D"
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
                ><i class="fa-solid fa-location-dot"></i>832 Thompson Drive,San
                Fransisco CA 94 107,United States</a
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
