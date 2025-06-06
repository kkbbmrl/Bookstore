<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fassila - Online Book Store website</title>
    <meta name="description" content="Fassila Bookstore - Find and purchase books from our vast collection with our online book search powered by Open Library API">
    <meta name="keywords" content="books, bookstore, search books, online library, book api">
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
              <a href="cart-item.php"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
            </button>
            <div class="profile-img">
              <a href="account.php">
                
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&amp;usqp=CAU" alt="">
                
              
              </a>
            </div>
          </div>
        </nav>
      </header>
      <div class="breadcrumb-container">
        <ul class="breadcrumb">
            <li><a href="../index.php" style="color: #6c5dd4">Home</a></li>
          <li><a href="#">Service</a></li>
        </ul>
      </div>
      <div class="service-title">
          <h3>Service</h3>
      </div>
      <section class="service" style="margin-top: 3rem;">
        <div class="service-container">
          <div class="service-card">
            <div class="icon">
              <i class="fa-solid fa-bolt-lightning"></i>
            </div>
            <div class="service-content">
              <h5>Quick Delivery</h5>
              <p>
              Fast and reliable delivery across all 58 wilayas of Algeria. Get your order wherever you are!
              </p>
            </div>
          </div>
          <div class="service-card">
            <div class="icon">
              <i class="fa-solid fa-shield"></i>
            </div>
            <div class="service-content">
              <h5>Secure Payment</h5>
              <p>
              Secure payment available via Baridi Mob and other trusted methods. Shop with confidence!
              </p>
            </div>
          </div>
          <div class="service-card">
            <div class="icon">
              <i class="fa-solid fa-thumbs-up"></i>
            </div>
            <div class="service-content">
              <h5>Best Quality</h5>
              <p>
              Discover our collection of top-quality books — carefully selected for the best reading experience.
              </p>
            </div>
          </div>
          <div class="service-card">
            <div class="icon">
              <i class="fa-solid fa-star"></i>
            </div>
            <div class="service-content">
              <h5>Return Guarantee</h5>
              <p>
              Enjoy peace of mind with our 7-day return guarantee — your satisfaction is our priority.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="countdown">
        <div class="container">
          <div class="customer counter">
            <div class="icon">
              <i class="fa-solid fa-user-group"></i>
            </div>
            <div class="content">
              <h4 class="count">125,663</h4>
              <small>Happy Customers</small>
            </div>
          </div>
          <div class="book counter">
            <div class="icon">
              <i class="fa-solid fa-book"></i>
            </div>
            <div class="content">
              <h4 class="count">50,672</h4>
              <small>Book Collections</small>
            </div>
          </div>
          <div class="store counter">
            <div class="icon">
              <i class="fa-solid fa-store"></i>
            </div>
            <div class="content">
              <h4 class="count">1,562</h4>
              <small>Our Stores</small>
            </div>
          </div>
          <div class="writer counter">
            <div class="icon">
              <i class="fa-solid fa-feather"></i>
            </div>
            <div class="content">
              <h4 class="count">457</h4>
              <small>Famous Writer</small>
            </div>
          </div>
        </div>
      </section>
      <section class="subscription">
        <div class="container">
          <h4>
            Subscribe our newsletter for Latest <br />
            books updates
          </h4>
          <div class="input">
            <input type="text" placeholder="Type your email here" />
            <button>subscribe</button>
          </div>
        </div>
        <div class="circle-1"></div>
        <div class="circle-2"></div>
      </section>

      <footer>
        <div class="container">
          <div class="logo-description">
            <div class="logo">
              <div class="img">
                <img src="../images/logo.png" alt="" />
              </div>
              <div class="title">
                <h4><a href="../index.php" style="text-decoration: none; color: inherit;">Fassila</a></h4>
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

      <script src="../js/back-to-top.js"></script>
      <script>
document.addEventListener('DOMContentLoaded', function() {
  // Get search form
  const searchForm = document.querySelector('.search-box form');
  
  // Add event listener for searching
  searchForm.addEventListener('submit', function(e) {
    const searchButton = this.querySelector('.search-icon');
    searchButton.closest('.search-field').classList.add('loading');
    
    // No need to prevent default - we want the form to submit to search.php
    
    // Store search in localStorage for analytics (optional)
    const searchQuery = document.querySelector('input[name="query"]').value;
    localStorage.setItem('lastSearch', searchQuery);
    
    // Track popular searches (optional)
    let popularSearches = JSON.parse(localStorage.getItem('popularSearches') || '[]');
    popularSearches.push({
      query: searchQuery,
      timestamp: new Date().toISOString()
    });
    
    // Keep only last 10 searches
    if (popularSearches.length > 10) {
      popularSearches = popularSearches.slice(-10);
    }
    
    localStorage.setItem('popularSearches', JSON.stringify(popularSearches));
  });
  
  // Show recent searches (optional enhancement)
  const searchInput = document.querySelector('input[name="query"]');
  searchInput.addEventListener('focus', function() {
    const lastSearch = localStorage.getItem('lastSearch');
    if (lastSearch) {
      // Create a suggestion element if you want
      // This is optional enhancement
    }
  });
});
</script>

<style>
    .user {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .user img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-right: 10px;
        cursor: pointer;
    }

    .user img:hover {
        opacity: 0.8;
    }

    .user a {
        text-decoration: none;
        color: #333;
        font-weight: bold;
    }

    .user a:hover {
        text-decoration: underline;
    }
</style>


</body>
</html>