<?php
/**
 * SHOES LAB - Global Header Partial
 * 
 * Reusable header with <head>, announcement bar, main navbar, offcanvas, and search modal.
 * Connects dynamically with 'ecomdb' via config/db.php.
 */

require_once __DIR__ . '/../config/db.php';

if (!isset($page_title) || empty($page_title)) {
    $page_title = "SHOES LAB | Luxury Men's Footwear & Handcrafted Leather Shoes Pakistan";
}

if (!isset($page_description) || empty($page_description)) {
    $page_description = "Discover SHOES LAB luxury men's footwear. Goodyear-welted Oxfords, burnished Penny Loafers, handcrafted Peshawari Chappals, and sleek leather sneakers. Cash on Delivery across Pakistan.";
}

$body_class_attr = !empty($body_class) ? ' class="' . htmlspecialchars($body_class) . '"' : '';

// Current page for active menu styling
$current_page = basename($_SERVER['PHP_SELF']);

// Load dynamic data from DB
$nav_categories = get_categories();
$store_settings = get_store_settings();
$brand_title = !empty($store_settings['brand_name']) ? $store_settings['brand_name'] : 'SHOES LAB';
$helpline_phone = !empty($store_settings['helpline_phone']) ? $store_settings['helpline_phone'] : '+92 42 111-746-375';
$delivery_threshold = !empty($store_settings['free_delivery_threshold_pkr']) ? (float)$store_settings['free_delivery_threshold_pkr'] : 10000;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=shoeslab">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body<?php echo $body_class_attr; ?>>

  <!-- Top Announcement Bar -->
  <div class="announcement-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="d-none d-md-flex align-items-center gap-3">
        <span><i class="bi bi-shield-check text-warning me-1"></i> 100% Genuine Full-Grain Leather</span>
        <span class="text-white-50">|</span>
        <span><i class="bi bi-truck text-warning me-1"></i> Cash on Delivery Across Pakistan</span>
      </div>
      <div class="mx-auto mx-md-0 text-center">
        <span>Complimentary Express Delivery on orders above <?php echo format_pkr($delivery_threshold); ?> <span class="badge-pak ms-1">PKR</span></span>
      </div>
      <div class="d-none d-lg-flex align-items-center gap-3">
        <a href="size-guide.php" class="text-white-50 text-decoration-none hover-white"><i
            class="bi bi-rulers me-1"></i> Size Guide</a>
        <a href="contact.php" class="text-white-50 text-decoration-none hover-white"><i
            class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($helpline_phone); ?></a>
        <a href="../dashboard/index.php" class="badge bg-secondary text-white text-decoration-none px-2 py-1"><i
            class="bi bi-speedometer2 me-1"></i> Admin Portal</a>
      </div>
    </div>
  </div>

  <!-- Main Navigation -->
  <header class="nova-navbar">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between">

        <!-- Mobile Menu Toggle Button -->
        <button class="navbar-toggler d-lg-none border-0 p-0 text-dark me-2" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#mobileNavOffcanvas" aria-controls="mobileNavOffcanvas">
          <i class="bi bi-list fs-2"></i>
        </button>

        <!-- Brand Identity -->
        <a href="index.php" class="brand-logo text-decoration-none me-4">
          <?php echo htmlspecialchars($brand_title); ?><span class="dot"></span>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="d-none d-lg-flex align-items-center gap-1">
          <a href="index.php" class="nav-link-nova <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
          <a href="shop.php" class="nav-link-nova <?php echo ($current_page == 'shop.php' || $current_page == 'product-details.php') ? 'active' : ''; ?>">Shop All</a>

          <div class="dropdown">
            <a href="category.php" class="nav-link-nova dropdown-toggle <?php echo ($current_page == 'category.php') ? 'active' : ''; ?>" data-bs-toggle="dropdown"
              aria-expanded="false">
              Categories
            </a>
            <ul class="dropdown-menu dropdown-menu-nova shadow-sm">
              <?php if (!empty($nav_categories)): ?>
                <?php foreach ($nav_categories as $cat): ?>
                  <li>
                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="category.php?id=<?php echo (int)$cat['id']; ?>">
                      <span><i class="bi bi-chevron-right me-2 text-muted small"></i> <?php echo htmlspecialchars($cat['name']); ?></span>
                      <?php if (!empty($cat['product_count'])): ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo (int)$cat['product_count']; ?></span>
                      <?php endif; ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li><a class="dropdown-item" href="shop.php">All Categories</a></li>
              <?php endif; ?>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item text-danger fw-semibold" href="shop.php?is_sale=1"><i class="bi bi-tag me-2"></i> View
                  Special Offers</a></li>
            </ul>
          </div>

          <?php 
          // Render top 3 categories dynamically in main navbar
          if (!empty($nav_categories)) {
              $top_cats = array_slice($nav_categories, 0, 3);
              foreach ($top_cats as $top_cat) {
                  $is_active = ($current_page == 'category.php' && isset($_GET['id']) && $_GET['id'] == $top_cat['id']);
                  echo '<a href="category.php?id=' . (int)$top_cat['id'] . '" class="nav-link-nova ' . ($is_active ? 'active' : '') . '">' . htmlspecialchars($top_cat['name']) . '</a>';
              }
          }
          ?>
          <a href="about.php" class="nav-link-nova <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About</a>
          <a href="shop.php?is_sale=1" class="nav-link-nova sale-link">Sale</a>
        </nav>

        <!-- Action Icons -->
        <div class="d-flex align-items-center gap-1">
          <button class="nav-action-btn" data-bs-toggle="modal" data-bs-target="#searchModal" title="Search catalog">
            <i class="bi bi-search"></i>
          </button>

          <div class="dropdown">
            <button class="nav-action-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
              title="Account">
              <i class="bi bi-person"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-nova shadow-sm">
              <li>
                <h6 class="dropdown-header text-uppercase text-muted small fw-bold">Customer Portal</h6>
              </li>
              <li><a class="dropdown-item" href="account.php"><i class="bi bi-person-circle me-2 text-muted"></i> My
                  Account</a></li>
              <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag-check me-2 text-muted"></i> Order
                  History</a></li>
              <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2 text-muted"></i> Wishlist</a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right me-2 text-muted"></i>
                  Sign In</a></li>
              <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus me-2 text-muted"></i> Create
                  Account</a></li>
            </ul>
          </div>

          <a href="wishlist.php" class="nav-action-btn" title="Saved Wishlist">
            <i class="bi bi-heart"></i>
            <span class="nav-action-badge wishlist-count-badge">0</span>
          </a>

          <a href="cart.php" class="nav-action-btn" title="Shopping Bag">
            <i class="bi bi-bag"></i>
            <span class="nav-action-badge cart-count-badge">0</span>
          </a>
        </div>

      </div>
    </div>
  </header>

  <!-- Mobile Offcanvas Menu -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNavOffcanvas" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title font-editorial fw-bold" id="mobileNavLabel"><?php echo htmlspecialchars($brand_title); ?></h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="mb-4">
        <form action="shop.php" method="GET">
          <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search catalog...">
            <button class="btn btn-nova-dark" type="submit"><i class="bi bi-search"></i></button>
          </div>
        </form>
      </div>

      <ul class="list-unstyled mb-4">
        <li class="mb-2"><a href="index.php" class="d-block py-2 text-decoration-none fw-semibold border-bottom">Home</a>
        </li>
        <li class="mb-2"><a href="shop.php" class="d-block py-2 text-decoration-none fw-semibold border-bottom">Shop
            All Footwear</a></li>
        
        <?php if (!empty($nav_categories)): ?>
          <li class="mb-2">
            <span class="d-block py-2 text-muted small text-uppercase fw-bold">Categories</span>
            <ul class="list-unstyled ps-2">
              <?php foreach ($nav_categories as $cat): ?>
                <li class="py-1">
                  <a href="category.php?id=<?php echo (int)$cat['id']; ?>" class="text-decoration-none text-dark d-flex justify-content-between">
                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                    <?php if (!empty($cat['product_count'])): ?>
                      <span class="badge bg-light text-dark"><?php echo (int)$cat['product_count']; ?></span>
                    <?php endif; ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endif; ?>

        <li class="mb-2"><a href="size-guide.php"
            class="d-block py-2 text-decoration-none fw-semibold border-bottom">Shoe Size Guide (EU 39-46)</a></li>
        <li class="mb-2"><a href="about.php" class="d-block py-2 text-decoration-none fw-semibold border-bottom">About
            <?php echo htmlspecialchars($brand_title); ?></a></li>
        <li class="mb-2"><a href="contact.php"
            class="d-block py-2 text-decoration-none fw-semibold border-bottom">Contact & Stores</a></li>
      </ul>

      <div class="border-top pt-3">
        <div class="d-flex flex-column gap-2">
          <a href="account.php" class="btn btn-outline-dark w-100 py-2"><i class="bi bi-person me-2"></i> Customer
            Account</a>
          <a href="../dashboard/index.php" class="btn btn-dark w-100 py-2"><i class="bi bi-shield-lock me-2"></i> Admin
            Dashboard</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Search Modal -->
  <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-uppercase fw-bold text-muted small mb-0">Search <?php echo htmlspecialchars($brand_title); ?> Catalog</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="shop.php" method="GET">
            <div class="input-group input-group-lg border rounded-1 overflow-hidden">
              <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" name="search" class="form-control border-0 shadow-none ps-0"
                placeholder="Type shoe title, material, occasion, or SKU..." autofocus>
              <button class="btn btn-nova-dark rounded-0 px-4" type="submit">Search</button>
            </div>
          </form>
          <div class="mt-3">
            <span class="text-muted small me-2">Explore Categories:</span>
            <?php if (!empty($nav_categories)): ?>
              <?php foreach (array_slice($nav_categories, 0, 4) as $cat): ?>
                <a href="category.php?id=<?php echo (int)$cat['id']; ?>" class="badge bg-light text-dark text-decoration-none me-1"><?php echo htmlspecialchars($cat['name']); ?></a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
