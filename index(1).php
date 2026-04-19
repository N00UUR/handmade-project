<?php
session_start();
if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] !== true) {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <title>handmade - eCommerce Website</title>

  <!--
    - favicon
  -->
  <link rel="shortcut icon" href="logo.jpeg" type="image/x-icon">

  <!--
    - custom css link
  -->
  <link rel="stylesheet" href="./assets/css/style-prefix.css">

  <!--
    - google font link
  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">

</head>

<body>


  <div class="overlay" data-overlay></div>

  <!--
    - MODAL
  -->

  <div class="modal" data-modal>

    <div class="modal-close-overlay" data-modal-overlay></div>

    <div class="modal-content">

      <button class="modal-close-btn" data-modal-close>
        <ion-icon name="close-outline"></ion-icon>
      </button>

      <div class="newsletter-img">
        <img src="pexels-fecundap6-350417.jpg" alt="subscribe newsletter" width="400" height="400">
      </div>

      <div class="newsletter">

        <form action="#">

          <div class="newsletter-header">

            <h3 class="newsletter-title">Subscribe Newsletter.</h3>

            <p class="newsletter-desc">
              Subscribe the <b>handy</b> to get latest products and discount update.
            </p>

          </div>

          <input type="email" name="email" class="email-field" placeholder="Email Address" required>

          <button type="submit" class="btn-newsletter">Subscribe</button>

        </form>

      </div>

    </div>

  </div>





  <!--
    - NOTIFICATION TOAST
  -->

  <div class="notification-toast" data-toast>

    <button class="toast-close-btn" data-toast-close>
      <ion-icon name="close-outline"></ion-icon>
    </button>

    <div class="toast-banner">
      <img src="pexels-digitalbuggu-352899.jpg" alt="Rose Gold Earrings" width="80" height="70">
    </div>

    <div class="toast-detail">

      <p class="toast-message">
        Someone in new just bought
      </p>

      <p class="toast-title">
        Rose Gold Earrings
      </p>

      <p class="toast-meta">
        <time datetime="PT2M">2 Minutes</time> ago
      </p>

    </div>

  </div>





  <!--
    - HEADER
  -->

  <header>

    <div class="header-top">

      <div class="container">

        <ul class="header-social-container">

          <li>
            <a href="#" class="social-link">
              <ion-icon name="logo-facebook"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="social-link">
              <ion-icon name="logo-twitter"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="social-link">
              <ion-icon name="logo-instagram"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="social-link">
              <ion-icon name="logo-linkedin"></ion-icon>
            </a>
          </li>

        </ul>

        <div class="header-alert-news">
          <p>
            <b>Free Shipping</b>
            This Week Order Over - $55
          </p>
        </div>

        <div class="header-top-actions">

          <select name="currency">

            <option value="usd">USD &dollar;</option>
            <option value="eur">EUR &euro;</option>

          </select>

          <select name="language">

            <option value="en-US">English</option>
            <option value="es-ES">Espa&ntilde;ol</option>
            <option value="fr">Fran&ccedil;ais</option>

          </select>

        </div>

      </div>

    </div>

    <div class="header-main">

      <div class="container">

        <a href="#" class="header-logo">
          <img src="logo.jpeg" alt="hand's logo" width="120" height="36">
        </a>

        <div class="header-search-container">
          <input type="search" name="search" id="search-field" class="search-field" placeholder="Enter your product name..." onkeydown="handleEnter(event)">
          <button class="search-btn" onclick="redirectToSearch()">
            <ion-icon name="search-outline"></ion-icon>
          </button>
        </div>

        <script>
          function redirectToSearch() {
            const searchQuery = document.getElementById('search-field').value;
            if (searchQuery) {
              window.location.href = `index.php?search=${encodeURIComponent(searchQuery)}`;
            }
          }

          function handleEnter(event) {
            if (event.key === 'Enter') {
              redirectToSearch();
            }
          }
        </script>

        <div class="header-user-actions">

          <a href="logout.php" class="action-btn">
            <ion-icon name="exit-outline"></ion-icon>
          </a>

          <button class="action-btn">
            <ion-icon name="heart-outline"></ion-icon>
            <span class="count">0</span>
          </button>

          <?php
          if (session_status() == PHP_SESSION_NONE) {
            session_start();
          }
          require 'db_connect.php';

          $cart_count = 0;

          if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($cart_count);
            $stmt->fetch();
            $stmt->close();
            if ($cart_count == '') {
              $cart_count = 0;
            }
          }
          ?>

          <a href="cart.php"> <button class="action-btn">
              <ion-icon name="bag-handle-outline"></ion-icon>
              <span class="count"><?php echo $cart_count; ?></span>
            </button>
          </a>
        </div>

      </div>

    </div>

    <?php
    include 'db_connect.php';
    $sql = "SELECT DISTINCT category FROM products";
    $result = $conn->query($sql);

    $categories = [];
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
      }
    }

    $conn->close();
    ?>

    <nav class="desktop-navigation-menu">
      <div class="container">
        <ul class="desktop-menu-category-list">
          <li class="menu-category"><a href="index.php" class="menu-title">Home</a></li>
          <li class="menu-category">
            <a href="" class="menu-title">Categories</a>
            <div class="dropdown-panel">
              <ul class="dropdown-panel-list">
                <?php foreach ($categories as $category): ?>
                  <li class="panel-list-item">
                    <form action="index.php" method="GET">
                      <button type="submit" name="category" value="<?php echo $category; ?>" class="category-button">
                        <?php echo htmlspecialchars($category); ?>
                      </button>
                    </form>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </li>
        </ul>
      </div>
    </nav>


  </header>





  <!--
    - MAIN
  -->

  <main>

    <!--
      - BANNER
    -->

    <div class="banner">

      <div class="container">

        <div class="slider-container has-scrollbar">

          <div class="slider-item">

            <img src="pexels-julieaagaard-2766334.jpg" alt="handbag's latest fashion sale" class="banner-img">

            <div class="banner-content">

              <p class="banner-subtitle">Trending item</p>

              <h2 class="banner-title">handbag's latest fashion sale</h2>

              <p class="banner-text">
                starting at &dollar; <b>10</b>.00
              </p>

              <a href="#" class="banner-btn">Shop now</a>

            </div>

          </div>

          <div class="slider-item">

            <img src="./assets/images/banner-2.jpg" alt="modern sunglasses" class="banner-img">

            <div class="banner-content">

              <p class="banner-subtitle">Trending accessories</p>

              <h2 class="banner-title">Modern sunglasses</h2>

              <p class="banner-text">
                starting at &dollar; <b>15</b>.00
              </p>

              <a href="#" class="banner-btn">Shop now</a>

            </div>

          </div>

          <div class="slider-item">

            <img src="./assets/images/banner-3.jpg" alt="new fashion summer sale" class="banner-img">

            <div class="banner-content">

              <p class="banner-subtitle">Sale Offer</p>

              <h2 class="banner-title">New fashion summer sale</h2>

              <p class="banner-text">
                starting at &dollar; <b>29</b>.99
              </p>

              <a href="#" class="banner-btn">Shop now</a>

            </div>

          </div>

        </div>

      </div>

    </div>





    <!--
      - CATEGORY
    -->
    <?php
    include 'db_connect.php';

    $sql = "
    SELECT category, COUNT(*) as product_count, MIN(image_path) as first_image 
    FROM products 
    GROUP BY category
";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
      echo '<div class="category">
            <div class="container">
              <div class="category-item-container has-scrollbar">';

      while ($row = $result->fetch_assoc()) {
        $categoryName = htmlspecialchars($row['category']);
        $productCount = $row['product_count'];
        $firstImage = htmlspecialchars($row['first_image']);

        echo '
        <div class="category-item">
            <div class="category-img-box">
                <img src="' . $firstImage . '" alt="' . $categoryName . '" width="30">
            </div>
            <div class="category-content-box">
                <div class="category-content-flex">
                    <h3 class="category-item-title">' . $categoryName . '</h3>
                    <p class="category-item-amount">(' . $productCount . ')</p>
                </div>
                <a href="index.php?category=' . urlencode($categoryName) . '" class="category-btn">Show all</a>
            </div>
        </div>';
      }

      echo '    </div>
            </div>
          </div>';
    } else {
      echo "<p>No categories found.</p>";
    }

    $conn->close();
    ?>





    <!--
      - PRODUCT
    -->

    <div class="product-container">

      <div class="container">


        <!--
          - SIDEBAR
        -->


        <div class="sidebar  has-scrollbar" data-mobile-menu>

          <div class="sidebar-category">

            <div class="sidebar-top">
              <div class="sidebar-category">
                <div class="sidebar-top">
                  <h2 class="sidebar-title">Category</h2>
                  <button class="sidebar-close-btn" data-mobile-menu-close-btn>
                    <ion-icon name="close-outline"></ion-icon>
                  </button>
                </div>

                <?php
                include 'db_connect.php';
                $categoryQuery = "SELECT DISTINCT category FROM products";
                $categoryResult = $conn->query($categoryQuery);

                if ($categoryResult->num_rows > 0) {
                  while ($categoryRow = $categoryResult->fetch_assoc()) {
                    $category = $conn->real_escape_string($categoryRow['category']);

                    $firstProductImageQuery = "SELECT image_path FROM products WHERE category='$category' LIMIT 1";
                    $firstProductImageResult = $conn->query($firstProductImageQuery);
                    $firstProductImage = $firstProductImageResult->fetch_assoc()['image_path'] ?? 'default-image.jpg';
                ?>
                    <li class="sidebar-menu-category">
                      <button class="sidebar-accordion-menu" data-accordion-btn>
                        <div class="menu-title-flex">
                          <img src="<?php echo $firstProductImage; ?>" alt="<?php echo $category; ?>" width="20" height="20" class="menu-title-img">
                          <p class="menu-title"><?php echo $category; ?></p>
                        </div>
                        <div>
                          <ion-icon name="add-outline" class="add-icon"></ion-icon>
                          <ion-icon name="remove-outline" class="remove-icon"></ion-icon>
                        </div>
                      </button>

                      <ul class="sidebar-submenu-category-list" data-accordion>
                        <?php
                        $productQuery = "SELECT name, description FROM products WHERE category='$category'";
                        $productResult = $conn->query($productQuery);

                        if ($productResult->num_rows > 0) {
                          while ($productRow = $productResult->fetch_assoc()) {
                            $productName = $productRow['name'];
                            $productDescription = $productRow['description'];
                        ?>
                            <li class="sidebar-submenu-category">
                              <a href="index.php?category=<?php echo $category; ?>" class="sidebar-submenu-title">
                                <p class="product-name"><?php echo $productName; ?></p>
                                <data class="stock" title="Product Description"><?php echo $productDescription; ?></data>
                              </a>
                            </li>
                        <?php
                          }
                        } else {
                          echo "<li><p>No products available in this category.</p></li>";
                        }
                        ?>
                      </ul>
                    </li>
                <?php
                  }
                } else {
                  echo "<p>No categories available.</p>";
                }
                ?>

                </ul>
              </div>
            </div>
          </div>
          <?php
          include 'db_connect.php';

          $productQuery = "SELECT * FROM products LIMIT 4";
          $productResult = $conn->query($productQuery);

          if ($productResult->num_rows > 0) {
          ?>
            <div class="product-showcase">
              <h3 class="showcase-heading">Best Sellers</h3>
              <div class="showcase-wrapper">
                <div class="showcase-container">
                  <?php
                  while ($productRow = $productResult->fetch_assoc()) {
                    $productId = $productRow['id'];
                    $productName = $productRow['name'];
                    $productPrice = $productRow['price'];
                    $productImage = $productRow['image_path'];
                  ?>
                    <div class="showcase">
                      <a href="product_details.php?id=<?php echo $productId; ?>" class="showcase-img-box">
                        <img src="<?php echo $productImage; ?>" alt="<?php echo $productName; ?>" width="75" height="75" class="showcase-img">
                      </a>

                      <div class="showcase-content">
                        <a href="product_details.php?id=<?php echo $productId; ?>" class="showcase-img-box">
                          <h4 class="showcase-title"><?php echo $productName; ?></h4>
                        </a>

                        <div class="showcase-rating">
                          <?php
                          echo '<ion-icon name="star"></ion-icon>';
                          echo '<ion-icon name="star"></ion-icon>';
                          echo '<ion-icon name="star"></ion-icon>';
                          echo '<ion-icon name="star"></ion-icon>';
                          echo '<ion-icon name="star-half-outline"></ion-icon>';
                          ?>
                        </div>

                        <div class="price-box">
                          <p class="price">$<?php echo $productPrice; ?></p>
                        </div>
                      </div>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          <?php
          } else {
            echo "<p>No products available at the moment.</p>";
          }
          ?>

        </div>



        <div class="product-box">

          <?php
          include 'db_connect.php';
          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }

          $sql = "SELECT * FROM products";
          $result = $conn->query($sql);
          $product_count = 0;
          $product_count = $result->num_rows;
          $colum_product_number = ($product_count / 3);

          ?>


          <!--
            - PRODUCT MINIMAL
          -->

          <div class="product-minimal">
            <div class="product-showcase">

              <h2 class="title">New Arrivals</h2>

              <div class="showcase-wrapper has-scrollbar">

                <div class="showcase-container">
                  <?php
                  $i = 0;
                  while ($product = $result->fetch_assoc()) {
                  ?>
                    <div class="showcase">

                      <a href="product_details.php?id=<?php echo $product['id']; ?>" class="showcase-img-box">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="showcase-img" width="70">
                      </a>

                      <div class="showcase-content">

                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="showcase-img-box">
                          <h4 class="showcase-title"> <?php echo htmlspecialchars($product['name']); ?></h4>
                        </a>

                        <a href="#" class="showcase-category"><?php echo htmlspecialchars($product['category']); ?></a>

                        <div class="price-box">
                          <p class="price"><?php echo '$' . htmlspecialchars($product['price']); ?></p>
                          <del><?php echo '$' . htmlspecialchars($product['price']) + 9; ?></del>
                        </div>

                      </div>

                    </div>
                  <?php
                    $i++;
                    if ($i > $colum_product_number || $i == 4) {
                      break;
                    }
                  } ?>
                </div>
              </div>
            </div>

            <div class="product-showcase">

              <h2 class="title">Trending</h2>

              <div class="showcase-wrapper  has-scrollbar">

                <div class="showcase-container">

                  <?php
                  $i = 0;
                  while ($product = $result->fetch_assoc()) {
                  ?>
                    <div class="showcase">

                      <a href="product_details.php?id=<?php echo $product['id']; ?>" class="showcase-img-box">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="showcase-img" width="70">
                      </a>

                      <div class="showcase-content">

                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="showcase-img-box">
                          <h4 class="showcase-title"> <?php echo htmlspecialchars($product['name']); ?></h4>
                        </a>

                        <a href="#" class="showcase-category"><?php echo htmlspecialchars($product['category']); ?></a>

                        <div class="price-box">
                          <p class="price"><?php echo '$' . htmlspecialchars($product['price']); ?></p>
                          <del><?php echo '$' . htmlspecialchars($product['price']) + 9; ?></del>
                        </div>

                      </div>

                    </div>
                  <?php
                    $i++;
                    if ($i > $colum_product_number || $i == 4) {
                      break;
                    }
                  } ?>
                </div>
              </div>
            </div>
            <div class="product-showcase">

              <h2 class="title">Trending</h2>

              <div class="showcase-wrapper  has-scrollbar">

                <div class="showcase-container">
                  <?php
                  $i = 0;
                  while ($product = $result->fetch_assoc()) {
                  ?>
                    <div class="showcase">

                      <a href="product_details.php?id=<?php echo $product['id']; ?>" class="showcase-img-box">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="showcase-img" width="70">
                      </a>

                      <div class="showcase-content">

                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="showcase-img-box">
                          <h4 class="showcase-title"> <?php echo htmlspecialchars($product['name']); ?></h4>
                        </a>

                        <a href="#" class="showcase-category"><?php echo htmlspecialchars($product['category']); ?></a>

                        <div class="price-box">
                          <p class="price"><?php echo '$' . htmlspecialchars($product['price']); ?></p>
                          <del><?php echo '$' . htmlspecialchars($product['price']) + 9; ?></del>
                        </div>

                      </div>

                    </div>
                  <?php
                    $i++;
                    if ($i > $colum_product_number || $i == 4) {
                      break;
                    }
                  } ?>
                </div>
              </div>
            </div>

          </div>



          <!--
            - PRODUCT FEATURED
          -->
          <?php
          include 'db_connect.php';

          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }

          $sql = "SELECT * FROM products ORDER BY RAND() LIMIT 1";
          $result = $conn->query($sql);

          if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
          } else {
            $product = null;
          }
          ?>

          <div class="product-featured">
            <h2 class="title">Deal of the day</h2>
            <div class="showcase-wrapper has-scrollbar">
              <?php if ($product): ?>
                <div class="showcase-container">
                  <div class="showcase">
                    <div class="showcase-banner">
                      <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="showcase-img">
                    </div>
                    <div class="showcase-content">
                      <div class="showcase-rating">
                        <?php for ($i = 0; $i < 3; $i++) { ?>
                          <ion-icon name="star"></ion-icon>
                        <?php } ?>
                        <?php for ($i = 0; $i < 2; $i++) { ?>
                          <ion-icon name="star-outline"></ion-icon>
                        <?php } ?>
                      </div>
                      <a href="#">
                        <h3 class="showcase-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                      </a>
                      <p class="showcase-desc">
                        "Discover unique, artisan-crafted <?php echo htmlspecialchars($product['description']); ?>."
                      </p>
                      <div class="price-box">
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                        <del>$<?php echo number_format($product['price'] + 9.99, 2); ?></del>
                      </div>
                      <button class="add-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">add to cart</button>
                      <div class="showcase-status">
                        <div class="wrapper">
                          <p>available: <b><?php echo $product['available_count']; ?></b></p>
                        </div>
                        <div class="showcase-status-bar"></div>
                      </div>
                      <div class="countdown-box">
                        <p class="countdown-desc">Hurry Up! Offer ends in:</p>
                        <div class="countdown">
                          <div class="countdown-content">
                            <p class="display-number">360</p>
                            <p class="display-text">Days</p>
                          </div>
                          <div class="countdown-content">
                            <p class="display-number">24</p>
                            <p class="display-text">Hours</p>
                          </div>
                          <div class="countdown-content">
                            <p class="display-number">59</p>
                            <p class="display-text">Min</p>
                          </div>
                          <div class="countdown-content">
                            <p class="display-number">00</p>
                            <p class="display-text">Sec</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <p>No featured product available at the moment.</p>
              <?php endif; ?>
            </div>
          </div>


          <!--
            - PRODUCT GRID
          -->

          <?php
          include 'db_connect.php';
          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }

          $sql = "SELECT * FROM products";
          $result = $conn->query($sql);


          ?>


          <?php
          include 'db_connect.php';
          $searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

          $category = isset($_GET['category']) ? $_GET['category'] : null;

          if ($category) {
            $sql = "SELECT * FROM products WHERE category = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category);
          } else if (isset($_GET['search'])) {
            $sql = "SELECT * FROM products WHERE name LIKE ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $searchTerm);
          } else {
            $sql = "SELECT * FROM products";
            $stmt = $conn->prepare($sql);
          }

          $stmt->execute();
          $result = $stmt->get_result();

          ?>
          <div class="product-main">
            <h2 class="title"><?php echo $category . ' Products' ?></h2>
            <div class="product-grid">
              <?php
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
              ?>
                  <div class="showcase">
                    <div class="showcase-banner">
                      <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" width="300" class="product-img default">
                      <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" width="300" class="product-img hover">
                      <p class="showcase-badge">15%</p>
                      <div class="showcase-actions">
                        <button class="btn-action">
                          <ion-icon name="heart-outline"></ion-icon>
                        </button>
                        <button class="btn-action">
                          <ion-icon name="eye-outline"></ion-icon>
                        </button>
                        <button class="btn-action">
                          <ion-icon name="repeat-outline"></ion-icon>
                        </button>
                        <button class="btn-action" onclick="addToCart(<?php echo $row['id']; ?>)">
                          <ion-icon name="bag-add-outline"></ion-icon>
                        </button>
                      </div>
                    </div>

                    <div class="showcase-content">
                      <a href="product_details.php?id=<?php echo $row['id']; ?>" class="showcase-category"><?php echo htmlspecialchars($row['category']); ?></a>
                      <a href="product_details.php?id=<?php echo $row['id']; ?>">
                        <h3 class="showcase-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                      </a>
                      <div class="showcase-rating">
                        <ion-icon name="star"></ion-icon>
                        <ion-icon name="star"></ion-icon>
                        <ion-icon name="star"></ion-icon>
                        <ion-icon name="star-outline"></ion-icon>
                        <ion-icon name="star-outline"></ion-icon>
                      </div>

                      <div class="price-box">
                        <p class="price">$<?php echo number_format($row['price'], 2); ?></p>
                        <del>$75.00</del>
                      </div>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo "<p>No products found.</p>";
              }
              $stmt->close();
              $conn->close();
              ?>
            </div>
          </div>

        </div>

      </div>

    </div>





    <!--
      - TESTIMONIALS, CTA & SERVICE
    -->

    <div>

      <div class="container">

        <div class="testimonials-box">

          <!--
            - TESTIMONIALS
          -->

          <div class="testimonial">

            <h2 class="title">testimonial</h2>

            <div class="testimonial-card">

              <img src="blog-2.jpg" alt="alan doe" class="testimonial-banner" width="80" height="80">

              <p class="testimonial-name">Alan Doe</p>

              <p class="testimonial-title">CEO & Founder Invision</p>

              <img src="blog-1.jpg" alt="quotation" class="quotation-img" width="26">

              <p class="testimonial-desc">
                "Elevate your brand with our collection of premium handmade products, designed to showcase craftsmanship and sustainability.
                Partner with us to offer your customers a unique, eco-conscious experience."
              </p>

            </div>

          </div>



          <!--
            - CTA
          -->

          <div class="cta-container">

            <img src="clothing4.jpeg" alt="summer collection" class="cta-banner">

            <a href="#" class="cta-content">

              <p class="discount">25% Discount</p>

              <h2 class="cta-title">Summer collection</h2>

              <p class="cta-text">Starting @ $10</p>

              <button class="cta-btn">Shop now</button>

            </a>

          </div>



          <!--
            - SERVICE
          -->

          <div class="service">

            <h2 class="title">Our Services</h2>

            <div class="service-container">

              <a href="#" class="service-item">

                <div class="service-icon">
                  <ion-icon name="boat-outline"></ion-icon>
                </div>

                <div class="service-content">

                  <h3 class="service-title">Worldwide Delivery</h3>
                  <p class="service-desc">For Order Over $100</p>

                </div>

              </a>

              <a href="#" class="service-item">

                <div class="service-icon">
                  <ion-icon name="rocket-outline"></ion-icon>
                </div>

                <div class="service-content">

                  <h3 class="service-title">Next Day delivery</h3>
                  <p class="service-desc">UK Orders Only</p>

                </div>

              </a>

              <a href="#" class="service-item">

                <div class="service-icon">
                  <ion-icon name="call-outline"></ion-icon>
                </div>

                <div class="service-content">

                  <h3 class="service-title">Best Online Support</h3>
                  <p class="service-desc">Hours: 8AM - 11PM</p>

                </div>

              </a>

              <a href="#" class="service-item">

                <div class="service-icon">
                  <ion-icon name="arrow-undo-outline"></ion-icon>
                </div>

                <div class="service-content">

                  <h3 class="service-title">Return Policy</h3>
                  <p class="service-desc">Easy & Free Return</p>

                </div>

              </a>

              <a href="#" class="service-item">

                <div class="service-icon">
                  <ion-icon name="ticket-outline"></ion-icon>
                </div>

                <div class="service-content">

                  <h3 class="service-title">30% money back</h3>
                  <p class="service-desc">For Order Over $100</p>

                </div>

              </a>

            </div>

          </div>

        </div>

      </div>

    </div>





    <!--
      - BLOG
    -->

    <div class="blog">

      <div class="container">

        <div class="blog-container has-scrollbar">

          <div class="blog-card">

            <a href="#">
              <img src="clothing.jpeg" alt="the best clothes for women handmade" width="300" class="blog-banner">
            </a>

            <div class="blog-content">

              <a href="#" class="blog-category">Gallery</a>

              <a href="#">
                <h3 class="blog-title">the best clothes for women handmade</h3>
              </a>

              <p class="blog-meta">
                By <cite>Mr Admin</cite> / <time datetime="2024-04-06">Apr 06, 2024</time>
              </p>

            </div>

          </div>

          <div class="blog-card">

            <a href="#">
              <img src="gift.jpeg" alt="here you can choose gift for your friends"
                class="blog-banner" width="300">
            </a>

            <div class="blog-content">

              <a href="#" class="blog-category">Gift</a>

              <h3>
                <a href="#" class="blog-title">here you can choose gift for your friends</a>
              </h3>

              <p class="blog-meta">
                By <cite>Mr Robin</cite> / <time datetime="2024-01-18">Jan 18, 2024</time>
              </p>

            </div>

          </div>

          <div class="blog-card">

            <a href="#">
              <img src="giftmen2.jpeg" alt="here you can choose gift for him"
                class="blog-banner" width="300">
            </a>

            <div class="blog-content">

              <a href="#" class="blog-category">gift</a>

              <h3>
                <a href="#" class="blog-title">here you can choose gift for him</a>
              </h3>

              <p class="blog-meta">
                By <cite>Mr Selsa</cite> / <time datetime="2024-02-10">Feb 10, 2024</time>
              </p>

            </div>

          </div>

          <div class="blog-card">

            <a href="#">
              <img src="art print3.jpeg" alt="you can see many of art print to choose"
                class="blog-banner" width="300">
            </a>

            <div class="blog-content">

              <a href="#" class="blog-category">art print</a>

              <h3>
                <a href="#" class="blog-title">you can see many of art print to choose</a>
              </h3>

              <p class="blog-meta">
                By <cite>Mr Pawar</cite> / <time datetime="2024-03-15">Mar 15, 2024</time>
              </p>

            </div>

          </div>

        </div>

      </div>

    </div>

  </main>





  <!--
    - FOOTER
  -->

  <?php
  include 'db_connect.php';

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT DISTINCT category FROM products";
  $result = $conn->query($sql);

  $categories = [];
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $categories[] = $row['category'];
    }
  } else {
    echo "No categories found.";
  }
  ?>

  <footer>
    <div class="footer-category">
      <div class="container">
        <h2 class="footer-category-title">Brand directory</h2>
        <div class="footer-category-box">
          <h3 class="category-box-title">Categories:</h3>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
              <a href="index.php?category=<?php echo urlencode($category); ?>" class="footer-category-link">
                <?php echo htmlspecialchars($category); ?>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No categories available.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-nav">
      <div class="container">

        <ul class="footer-nav-list">

          <li class="footer-nav-item">
            <h2 class="nav-title">Popular Categories</h2>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Fashion</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Wallet</a>
          </li>

        </ul>

        <ul class="footer-nav-list">

          <li class="footer-nav-item">
            <h2 class="nav-title">Products</h2>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Prices drop</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">New products</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Best sales</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Contact us</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Sitemap</a>
          </li>

        </ul>

        <ul class="footer-nav-list">

          <li class="footer-nav-item">
            <h2 class="nav-title">Our Company</h2>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Delivery</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Legal Notice</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Terms and conditions</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">About us</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Secure payment</a>
          </li>

        </ul>

        <ul class="footer-nav-list">

          <li class="footer-nav-item">
            <h2 class="nav-title">Services</h2>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Prices drop</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">New products</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Best sales</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Contact us</a>
          </li>

          <li class="footer-nav-item">
            <a href="#" class="footer-nav-link">Sitemap</a>
          </li>

        </ul>

        <ul class="footer-nav-list">

          <li class="footer-nav-item">
            <h2 class="nav-title">Contact</h2>
          </li>

          <li class="footer-nav-item flex">
            <div class="icon-box">
              <ion-icon name="location-outline"></ion-icon>
            </div>

            <address class="content">
              419 State 414 Rte
              Beaver Dams, New York(NY), 14812, USA
            </address>
          </li>

          <li class="footer-nav-item flex">
            <div class="icon-box">
              <ion-icon name="call-outline"></ion-icon>
            </div>

            <a href="tel:+607936-8058" class="footer-nav-link">(607) 936-8058</a>
          </li>

          <li class="footer-nav-item flex">
            <div class="icon-box">
              <ion-icon name="mail-outline"></ion-icon>
            </div>

            <a href="mailto:example@gmail.com" class="footer-nav-link">example@gmail.com</a>
          </li>

        </ul>

        <ul class="footer-nav-list">

          <li class="footer-nav-item">
            <h2 class="nav-title">Follow Us</h2>
          </li>

          <li>
            <ul class="social-link">

              <li class="footer-nav-item">
                <a href="#" class="footer-nav-link">
                  <ion-icon name="logo-facebook"></ion-icon>
                </a>
              </li>

              <li class="footer-nav-item">
                <a href="#" class="footer-nav-link">
                  <ion-icon name="logo-twitter"></ion-icon>
                </a>
              </li>

              <li class="footer-nav-item">
                <a href="#" class="footer-nav-link">
                  <ion-icon name="logo-linkedin"></ion-icon>
                </a>
              </li>

              <li class="footer-nav-item">
                <a href="#" class="footer-nav-link">
                  <ion-icon name="logo-instagram"></ion-icon>
                </a>
              </li>

            </ul>
          </li>

        </ul>

      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <img src="./assets/images/payment.png" alt="payment method" class="payment-img">
        <p class="copyright">
          Copyright &copy; <a href="#">handmade</a> all rights reserved.
        </p>
      </div>
    </div>
  </footer>





  <!--
    - custom js link
  -->
  <script src="./assets/js/script.js"></script>

  <!--
    - ionicon link
  -->
  <script src="js/script.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>

</html>