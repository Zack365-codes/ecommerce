<?php
require_once __DIR__ . '/config/db.php';

// Fetch all categories
$all_categories = get_categories();

// Determine current category
$current_cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_cat_name = isset($_GET['name']) ? trim($_GET['name']) : '';

$current_category = null;
if ($current_cat_id) {
    foreach ($all_categories as $c) {
        if ($c['id'] == $current_cat_id) {
            $current_category = $c;
            break;
        }
    }
} elseif (!empty($current_cat_name)) {
    foreach ($all_categories as $c) {
        if (strcasecmp($c['name'], $current_cat_name) === 0) {
            $current_category = $c;
            break;
        }
    }
}

// Fallback to first category with products, or simply first category
if (!$current_category && !empty($all_categories)) {
    $current_category = $all_categories[0];
}

$cat_title = $current_category ? $current_category['name'] : 'All Footwear Categories';
$page_title = $cat_title . " Collection | SHOES LAB Pakistan";
$page_description = "Discover handcrafted " . $cat_title . " made from authentic premium leather. Cash on delivery available across Pakistan.";

require_once 'partials/header.php';

// Sort param
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Fetch products for this category
$cat_products = [];
if ($current_category) {
    $cat_products = get_products([
        'category_id' => $current_category['id'],
        'sort'        => $sort,
    ]);
}
$product_count = count($cat_products);
?>

<main>
    <!-- Category Hero Banner -->
    <div class="position-relative bg-dark text-white py-5" style="background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?q=80&w=1600&auto=format&fit=crop') center/cover;">
      <div class="container py-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-nova text-white-50">
            <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php" class="text-white-50">Categories</a></li>
            <li class="breadcrumb-item active text-white" aria-current="page"><?php echo htmlspecialchars($cat_title); ?></li>
          </ol>
        </nav>
        <div class="max-w-700">
          <span class="badge bg-warning text-dark text-uppercase fw-bold px-3 py-1 mb-2">Category Showcase</span>
          <h1 class="display-5 fw-bold font-editorial text-white mb-2"><?php echo htmlspecialchars($cat_title); ?></h1>
          <p class="lead text-white-50 mb-0" style="max-width: 650px;">
            Explore our curated handcrafted <?php echo htmlspecialchars($cat_title); ?> collection, built with supreme attention to detail and traditional leathercraft.
          </p>
        </div>
      </div>
    </div>

    <!-- Category Switcher Nav -->
    <div class="bg-light border-bottom py-3">
      <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($all_categories as $cat): ?>
            <?php $is_curr = ($current_category && $current_category['id'] == $cat['id']); ?>
            <a href="category.php?id=<?php echo (int)$cat['id']; ?>" 
               class="btn btn-sm <?php echo $is_curr ? 'btn-dark' : 'btn-outline-secondary bg-white'; ?> rounded-pill px-3">
              <?php echo htmlspecialchars($cat['name']); ?> 
              <span class="badge <?php echo $is_curr ? 'bg-light text-dark' : 'bg-secondary text-white'; ?> ms-1"><?php echo (int)$cat['product_count']; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <a href="shop.php" class="small text-muted text-decoration-none">View All Products <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>

    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="text-muted small">Showing <strong><?php echo $product_count; ?></strong> handcrafted models in <?php echo htmlspecialchars($cat_title); ?></div>
        <div class="d-flex align-items-center gap-2">
          <label class="small text-muted mb-0">Sort:</label>
          <select class="form-select form-select-sm" style="width: 170px;" onchange="location = this.value;">
            <option value="category.php?id=<?php echo $current_category['id']; ?>&sort=newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Additions</option>
            <option value="category.php?id=<?php echo $current_category['id']; ?>&sort=price-asc" <?php echo $sort == 'price-asc' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="category.php?id=<?php echo $current_category['id']; ?>&sort=price-desc" <?php echo $sort == 'price-desc' ? 'selected' : ''; ?>>Price: High to Low</option>
          </select>
        </div>
      </div>

      <div class="row g-3 g-md-4">
        <?php if (!empty($cat_products)): ?>
          <?php foreach ($cat_products as $prod): ?>
            <?php 
              $img_src = get_product_image_url($prod['image_url']);
              $cat_name = !empty($prod['category_name']) ? $prod['category_name'] : $cat_title;
              $has_discount = (!empty($prod['original_price_pkr']) && $prod['original_price_pkr'] > $prod['price_pkr']);
              $prod_link = "product-details.php?id=" . (int)$prod['id'];
            ?>
            <div class="col-lg-3 col-md-6 col-6 col-product-grid">
              
              <div class="product-card h-100 d-flex flex-column justify-content-between">
                <div class="product-img-wrap position-relative">
                  <div class="product-badges">
                    <?php if (!empty($prod['is_sale'])): ?>
                      <span class="badge-sale">Sale</span>
                    <?php endif; ?>
                    <?php if (!empty($prod['is_new'])): ?>
                      <span class="badge-new">New</span>
                    <?php endif; ?>
                  </div>
                  <button class="product-wishlist-btn" title="Add to Wishlist" data-product-id="<?php echo htmlspecialchars($prod['sku']); ?>">
                    <i class="bi bi-heart"></i>
                  </button>
                  <a href="<?php echo $prod_link; ?>">
                    <img src="<?php echo htmlspecialchars($img_src); ?>" 
                         alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                         loading="lazy" 
                         style="width: 100%; height: 260px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?q=80&w=800&auto=format&fit=crop';">
                  </a>
                  <div class="product-quick-actions">
                    <a href="<?php echo $prod_link; ?>" class="quick-view-btn text-decoration-none">
                      <i class="bi bi-eye me-1"></i> View Details
                    </a>
                  </div>
                </div>
                <div class="product-info flex-grow-1 d-flex flex-column justify-content-between">
                  <div>
                    <div class="product-category"><?php echo htmlspecialchars($cat_name); ?></div>
                    <h3 class="product-name">
                      <a href="<?php echo $prod_link; ?>"><?php echo htmlspecialchars($prod['name']); ?></a>
                    </h3>
                  </div>
                  <div>
                    <div class="product-price-box">
                      <span class="current-price"><?php echo format_pkr($prod['price_pkr']); ?></span>
                      <?php if ($has_discount): ?>
                        <span class="original-price"><?php echo format_pkr($prod['original_price_pkr']); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                      <span class="badge bg-light text-muted border"><?php echo htmlspecialchars($prod['brand_name'] ?? 'SHOES LAB'); ?></span>
                      <a href="<?php echo $prod_link; ?>" class="btn btn-sm btn-outline-dark px-2 py-1">View Details</a>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 py-5 text-center">
            <div class="p-5 bg-white border rounded-1 shadow-sm">
              <i class="bi bi-tags fs-1 text-muted mb-3 d-block"></i>
              <h4 class="fw-bold">No Products In This Category Yet</h4>
              <p class="text-muted mb-4">We are preparing new handcrafted additions for the <?php echo htmlspecialchars($cat_title); ?> collection.</p>
              <a href="shop.php" class="btn btn-nova-dark px-4 py-2">Explore All Footwear</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
</main>

<?php require_once 'partials/footer.php'; ?>
