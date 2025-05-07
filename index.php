<?php
// Start the session
session_start();

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
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fassila - Book Store Website</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
   <!--css link-->
    <link rel="stylesheet" href="css/style.css" />
    <!--- google font link-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />
    <!-- Fontawesome Link for Icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />

    <link rel="apple-touch-icon" sizes="57x57" href="favicon/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="favicon/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="favicon/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="favicon/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="favicon/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="favicon/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="favicon/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="favicon/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="favicon/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
<link rel="manifest" href="favicon/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">

  </head>
  <body>
    <!-- Add this right after the body tag or where you want to display the message -->
    <?php
    if(isset($_SESSION['login_success'])) {
        echo '<div class="success-message" style="background-color: #4CAF50; color: white; padding: 15px; margin: 10px 0; text-align: center; border-radius: 5px;">' . $_SESSION['login_success'] . '</div>';
        unset($_SESSION['login_success']); // Clear the message after displaying it
    }
    ?>
    
    <header>
      <nav class="navbar">
        <div class="logo">
          <div class="img">
            <img src="images/logo.png" alt="" />
          </div>
          <div class="logo-header">
            <h4><a href="index.php">Fassila</a></h4>
            <small>Book Store Website</small>
          </div>
        </div>
        <ul class="nav-list">
          <div class="logo">
            <div class="title">
              <div class="img">
                <img src="images/logo.png" alt="" />
              </div>
              <div class="logo-header">
                <h4><a href="index.php">Fassila</a></h4>
                <small>Book Store Website</small>
              </div>
            </div>
            <button class="close"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <li><a href="index.php">Home</a></li>
          <li><a href="pages/service.php">Service</a></li>
          <li><a href="pages/contact.php">Contact</a></li>
          <li><a href="Pages/book-filter.php">Books</a></li>
          
          <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <button class="logout">
              <i class="fa-solid fa-sign-out-alt"></i><a href="Pages/logout.php">Logout</a>
            </button>
          <?php else: ?>
            <button class="login"><a href="pages/login.php">Log In</a></button>
            <button class="signup">
              <i class="fa-solid fa-user"></i><a href="pages/registration.php">Sign Up</a>
            </button>
          <?php endif; ?>
        </ul>
        <div class="hamburger">
          <div class="line"></div>
          <div class="line"></div>
        </div>
      </nav>
    </header>

    <section class="hero">
      <div class="main">
        <div class="content">
          <small>Welcome Intellectuals</small>
          <h2>Fassila Website</h2>
          <h5>for our readers community</h5>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Doloribus,
            maiores? Eius culpa molestias expedita magni incidunt necessitatibus
            .
          </p>
          <div class="btns">
            <button>Get The Deal<i class="fa-solid fa-arrow-right"></i></button>
            <button>See other Promos</button>
          </div>
        </div>
        <div class="img">
          <img
            src="images/teenager-student-girl-yellow-pointing-finger-side-copy.gif"
            alt=""
          />
        </div>
      </div>
      <div class="square-dot">
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
        <i class="fa-solid fa-square"></i>
      </div>
      <div class="orange-circle"></div>
      <div class="blue-circle"></div>
    </section>

    <section class="service">
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

    <section class="suggestion">
      <div class="container">
        <div class="recommend">
          <h4>Recommended For You</h4>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Id,
            exercitationem.Lorem ipsum dolor sit amet.
          </p>
          <div class="book-container">
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-1.jpg" alt=""
                /></a>
              </figure>
            </div>
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-2.jpg" alt=""
                /></a>
              </figure>
            </div>
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-3.jpg" alt=""
                /></a>
              </figure>
            </div>
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-4.jpg" alt=""
                /></a>
              </figure>
            </div>
          </div>
          <div class="circle-1"></div>
          <div class="circle-2"></div>
          <div class="square-dot">
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
          </div>
        </div>
        <div class="popular">
          <h4>Popular in 2020</h4>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Id,
            exercitationem.
          </p>
          <div class="book-container">
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-5.jpg" alt=""
                /></a>
              </figure>
            </div>
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-6.jpg" alt=""
                /></a>
              </figure>
            </div>
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-7.jpg" alt=""
                /></a>
              </figure>
            </div>
            <div class="book">
              <figure>
                <a href="Pages/book-detail.html"
                  ><img src="images/book-8.jpg" alt=""
                /></a>
              </figure>
            </div>
          </div>
          <div class="circle-1"></div>
          <div class="circle-2"></div>
          <div class="square-dot">
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
            <i class="fa-solid fa-square"></i>
          </div>
        </div>
      </div>
    </section>

    <section class="offer">
      <div class="header">
        <h4>Special Offers</h4>
        <p>Discover our exclusive deals on featured books!</p>
      </div>
      <div class="offer-container">
        <?php
        // Include database connection
        include 'connection.php';

        // Fetch featured books from the database
        $query = "SELECT * FROM books WHERE featured = 1";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
          while ($book = $result->fetch_assoc()) {
            ?>
            <div class="offer">
              <div class="img">
                <a href="Pages/book-detail.php?id=<?php echo $book['id']; ?>">
                  <img src="images/books/<?php echo $book['cover_image'] ? $book['cover_image'] : 'book-1.jpg'; ?>" alt="<?php echo $book['title']; ?>" />
                </a>
              </div>
              <div class="offer-content">
                <h5 title="<?php echo $book['title']; ?>"><?php echo $book['title']; ?></h5>
                <div class="price">
                  <strong><span>$</span><?php echo number_format($book['price'], 2); ?></strong>
                  <?php if (!empty($book['old_price']) && $book['old_price'] > $book['price']): ?>
                    <span><strike>$<?php echo number_format($book['old_price'], 2); ?></strike></span>
                  <?php endif; ?>
                </div>
                <button class="cartbtn" data-book-id="<?php echo $book['id']; ?>"
                       data-book-name="<?php echo $book['title']; ?>"
                       data-book-price="<?php echo $book['price']; ?>"
                       data-book-image="<?php echo $book['cover_image']; ?>">
                  <i class="fa-solid fa-cart-shopping"></i>Add to cart
                </button>
              </div>
            </div>
            <?php
          }
        } else {
          echo '<p class="no-offers">No special offers available at the moment. <a href="Pages/book-filter.php">Browse all books</a> instead.</p>';
        }
        ?>
      </div>
    </section>

    <!-- <section class="sale">
      <div class="header">
        <h4>Flash Sale</h4>
        <p>
          Lorem ipsum dolor sit amet consectetur adipisicing elit. <br />
          Ipsum dignissimos quasi aliquid numquam.
        </p>
      </div>
      <div class="timer">
        <div class="hours">
          <span id="hour"></span>
          <small>Hours</small>
        </div>
        <div class="minutes">
          <span id="minute"></span>
          <small for="minute">Minutes</small>
        </div>
        <div class="seconds">
          <span id="second"></span>
          <small for="second">Seconds</small>
        </div>
      </div>
      <div class="book-container">
        <div class="book">
          <div class="img">
            <a href="Pages/book-detail.html"
              ><img src="images/book-9.jpg" alt="" />
            </a>
          </div>
          <h5>The Ruins Of Gorlan</h5>
          <small>Thriller</small>
          <div class="price">
            <span>$54.6</span>
            <span><strike>$70.00</strike></span>
          </div>
        </div>
        <div class="book">
          <div class="img">
            <a href="Pages/book-detail.html"
              ><img src="images/book-10.jpg" alt=""
            /></a>
          </div>
          <h5>Percy Jackson</h5>
          <small>Thriller</small>
          <div class="price">
            <span>$54.6</span>
            <span><strike>$70.00</strike></span>
          </div>
        </div>
        <div class="book">
          <div class="img">
            <a href="Pages/book-detail.html"
              ><img src="images/book-11.jpg" alt=""
            /></a>
          </div>
          <h5>Clever Lands</h5>
          <small>Thriller</small>
          <div class="price">
            <span>$54.6</span>
            <span><strike>$70.00</strike></span>
          </div>
        </div>
        <div class="book">
          <div class="img">
            <a href="Pages/book-detail.html"
              ><img src="images/book-12.jpg" alt=""
            /></a>
          </div>
          <h5>Dick Francis</h5>
          <small>Thriller</small>
          <div class="price">
            <span>$54.6</span>
            <span><strike>$70.00</strike></span>
          </div>
        </div>
        <div class="book">
          <div class="img">
            <a href="Pages/book-detail.html"
              ><img src="images/book-1.jpg" alt="" />
            </a>
          </div>
          <h5>The Giver</h5>
          <small>Thriller</small>
          <div class="price">
            <span>$54.6</span>
            <span><strike>$70.00</strike></span>
          </div>
        </div>
      </div>
    </section> -->

    <!-- <section class="book-sale">
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
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-1.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>The Giver</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-2.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>The Wright ...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-9.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>The Ruins Of...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-10.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>Percy Jackson</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-5.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>To kill a...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-6.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>Horry Potter</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
            <li class="card">
              <div class="img">
                <a href="Pages/book-detail.html"><img src="images/book-7.jpg" alt="" /></a>
                <span class="badge">50%</span>
              </div>
              <h5>Heroes of ...</h5>
              <div class="genre">
                <span>adventure,</span><span>survival</span>
              </div>
              <div class="footer">
                <span class="star"><i class="fa fa-star"></i> 4.7</span>
                <div class="price">
                  <span>$45.4</span>
                  <span><strike>$90.4</strike></span>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section> -->

    <!-- <section class="feature">
      <div class="main">
        <div class="content">
          <h4>Featured Books</h4>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. <br />
            Molestias, illum? Doloremque eius quis officiis rerum pariatur.
          </p>
          <div class="featured-book-card">
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-2.jpg" alt="" /></a>
            </div>
            <div class="card-description">
              <div class="card-header">
                <div class="icon">
                  <i class="fa-regular fa-bookmark"></i>
                  <i class="fa fa-star"></i>
                </div>
                <div class="title">
                  <h5>The Wright Brothers</h5>
                  <small>biography, adventure</small>
                </div>
              </div>
              <div class="card-body">
                <h6>Synopsis</h6>
                <p>
                  Lorem ipsum dolor sit amet consectetur, adipisicing elit.
                  Suscipit, aperiam magnam! Eius voluptas nihil repellendus quam
                  velit harum vel alias mollitia veritatis omnis. Ut vel, fugiat
                  obcaecati odio minus molestiae?
                </p>
                <div class="author-year">
                  <div class="author">
                    <small>Written by</small>
                    <strong>Kevin Smiley</strong>
                  </div>
                  <div class="year">
                    <small>Year</small>
                    <strong>2019</strong>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <div class="price">
                  <span>$84.78</span>
                  <strike>$90.00</strike>
                </div>
                <div class="cartbtn">
                  <button><i class="fa-solid fa-cart-shopping"></i>Add</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="book-section">
          <div class="container">
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-2.jpg" alt="" /></a>
            </div>
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-1.jpg" alt="" /></a>
            </div>
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-3.jpg" alt="" /></a>
            </div>
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-4.jpg" alt="" /></a>
            </div>
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-5.jpg" alt="" /></a>
            </div>
            <div class="img">
              <a href="pages/book-detail.html"><img src="images/book-6.jpg" alt="" /></a>
            </div>
          </div>
        </div>
      </div>
      <div class="circle-1"></div>
      <div class="circle-2"></div>
    </section> -->

    <!-- <section class="news">
      <div class="heading">
        <div class="title">
          <h4>Latest News</h4>
          <p>
            Lorem ipsum dolor sit, amet consectetur adipisicing elit. <br />
            Quia, laborum ad perspiciatis ab, sequi.
          </p>
        </div>
        <div class="btn">
          <button>View More <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
      <div class="news-container">
        <div class="post">
          <div class="img">
            <img src="images/news-1.avif" alt="" />
          </div>
          <h5>Why reading is important for our children?</h5>
          <p>
            Lorem ipsum dolor sit, amet consectetur adipisicing elit.
            Aspernatur, quo temporibus! Tenetur...
            <a href="">Continue reading</a>
          </p>
          <div class="post-footer">
            <div class="img">
              <img src="images/man1.png" alt="" />
            </div>
            <div class="details">
              <strong>James Bond</strong>
              <small>5 August 2020</small>
            </div>
          </div>
        </div>
        <div class="post">
          <div class="img">
            <img src="images/news-2.webp" alt="" />
          </div>
          <h5>Why reading is important for our children?</h5>
          <p>
            Lorem ipsum dolor sit, amet consectetur adipisicing elit.
            Aspernatur, quo temporibus! Tenetur...
            <a href="">Continue reading</a>
          </p>
          <div class="post-footer">
            <div class="img">
              <img src="images/man1.png" alt="" />
            </div>
            <div class="details">
              <strong>James Bond</strong>
              <small>5 August 2020</small>
            </div>
          </div>
        </div>
        <div class="post">
          <div class="img">
            <img src="images/news-3.jpg" alt="" />
          </div>
          <h5>Why reading is important for our children?</h5>
          <p>
            Lorem ipsum dolor sit, amet consectetur adipisicing elit.
            Aspernatur, quo temporibus! Tenetur...
            <a href="">Continue reading</a>
          </p>
          <div class="post-footer">
            <div class="img">
              <img src="images/man1.png" alt="" />
            </div>
            <div class="details">
              <strong>James Bond</strong>
              <small>5 August 2020</small>
            </div>
          </div>
        </div>
        <div class="post">
          <div class="img">
            <img src="images/news-4.jpg" alt="" />
          </div>
          <h5>Why reading is important for our children?</h5>
          <p>
            Lorem ipsum dolor sit, amet consectetur adipisicing elit.
            Aspernatur, quo temporibus! Tenetur...
            <a href="">Continue reading</a>
          </p>
          <div class="post-footer">
            <div class="img">
              <img src="images/man1.png" alt="" />
            </div>
            <div class="details">
              <strong>James Bond</strong>
              <small>5 August 2020</small>
            </div>
          </div>
        </div>
      </div>
    </section> -->

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
              <img src="images/logo.png" alt="" />
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
            <li><a href="Pages/book-filter.php">Action</a></li>
            <li><a href="Pages/book-filter.php">Adventure</a></li>
            <li><a href="Pages/book-filter.php">Comedy</a></li>
            <li><a href="Pages/book-filter.php">Crime</a></li>
            <li><a href="Pages/book-filter.php">Drama</a></li>
            <li><a href="Pages/book-filter.php">Fantasy</a></li>
            <li><a href="Pages/book-filter.php">Horror</a></li>
          </ul>
        </div>
        <div class="quick-links list">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="index.php">About Us</a></li>
            <li><a href="pages/contact.php">Contact Us</a></li>
            <li><a href="pages/book-filter.php">Products</a></li>
            <li><a href="pages/login.php">Login</a></li>
            <li><a href="pages/registration.php">Sign Up</a></li>
            <li><a href="pages/cart-item.html">Cart</a></li>
            <li><a href="pages/checkout.html">Checkout</a></li>
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
    <!--Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <script src="js/back-to-top.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"></script>
    <script src="js/script.js"></script>
    <script src="js/repeat-js.js"></script>
    <script src="js/add-to-cart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.3.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const cartButtons = document.querySelectorAll('.cartbtn');

        cartButtons.forEach(button => {
          button.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const bookName = this.dataset.bookName;
            const bookPrice = this.dataset.bookPrice;
            const bookImage = this.dataset.bookImage;
            const quantity = 1; // Default quantity

            const formData = new URLSearchParams();
            formData.append('book_id', bookId);
            formData.append('book_name', bookName);
            formData.append('book_price', bookPrice);
            formData.append('book_image', bookImage);
            formData.append('quantity', quantity);

            fetch('api/add_to_cart.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                alert('Book added to cart successfully!');
              } else {
                alert('Error: ' + data.message);
              }
            })
            .catch(error => {
              console.error('Error:', error);
            });
          });
        });
      });
    </script>
  </body>
</html>
