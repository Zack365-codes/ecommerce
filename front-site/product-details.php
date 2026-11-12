<?php
require_once __DIR__ . '/config/db.php';

// Determine product from query params
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$product = get_product_by_id_or_slug($product_id, $product_slug);

// Fallback to first available active product if none found
if (!$product) {
    $fallback_products = get_products(['limit' => 1]);
    if (!empty($fallback_products)) {
        $product = get_product_by_id_or_slug($fallback_products[0]['id']);
    }
}

if ($product) {
    $page_title = htmlspecialchars($product['name']) . " | SHOES LAB Luxury Footwear";
    $page_description = !empty($product['short_description']) 
        ? htmlspecialchars($product['short_description']) 
        : "Handcrafted " . htmlspecialchars($product['name']) . " by SHOES LAB. Premium quality leather footwear.";
} else {
    $page_title = "Product Details | SHOES LAB Luxury Footwear";
    $page_description = "Handcrafted footwear by SHOES LAB. Premium quality leather shoes.";
}

require_once 'partials/header.php';

if (!$product) {
    echo '<main class="py-5"><div class="container text-center py-5">
            <h2 class="fw-bold mb-3">Product Not Found</h2>
            <p class="text-muted mb-4">The footwear model you are looking for is currently unavailable or has been archived.</p>
            <a href="shop.php" class="btn btn-nova-dark px-4 py-2">Explore Catalog</a>
          </div></main>';
    require_once 'partials/footer.php';
    exit;
}

// Product computations
$main_image_url = get_product_image_url($product['image_url']);
$cat_name = !empty($product['category_name']) ? $product['category_name'] : 'Footwear';
$brand_name = !empty($product['brand_name']) ? $product['brand_name'] : 'SHOES LAB';
$has_discount = (!empty($product['original_price_pkr']) && $product['original_price_pkr'] > $product['price_pkr']);
$discount_pct = $has_discount ? round((($product['original_price_pkr'] - $product['price_pkr']) / $product['original_price_pkr']) * 100) : 0;

// Variants: Extract unique sizes & colors
$variant_sizes = [];
$variant_colors = [];
if (!empty($product['variants'])) {
    foreach ($product['variants'] as $v) {
        if (!empty($v['size_eu']) && !in_array($v['size_eu'], $variant_sizes)) {
            $variant_sizes[] = (int)$v['size_eu'];
        }
        if (!empty($v['color_name']) && !in_array($v['color_name'], $variant_colors)) {
            $variant_colors[] = $v['color_name'];
        }
    }
    sort($variant_sizes);
}

if (empty($variant_sizes)) {
    $variant_sizes = [39, 40, 41, 42, 43, 44, 45, 46];
}
if (empty($variant_colors)) {
    $variant_colors = ['Classic Leather'];
}

// Fetch Related Products (same category or others, excluding current)
$related_products = [];
$all_candidates = get_products([
    'category_id' => !empty($product['category_id']) ? $product['category_id'] : null,
    'limit'       => 5
]);
foreach ($all_candidates as $cand) {
    if ($cand['id'] != $product['id']) {
        $related_products[] = $cand;
    }
    if (count($related_products) >= 4) break;
}
if (count($related_products) < 4) {
    $extra_candidates = get_products(['limit' => 6]);
    foreach ($extra_candidates as $cand) {
        if ($cand['id'] != $product['id'] && !in_array($cand['id'], array_column($related_products, 'id'))) {
            $related_products[] = $cand;
        }
        if (count($related_products) >= 4) break;
    }
}
?>

<main class="py-4">
    <div class="container">
      
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-nova">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
          <?php if (!empty($product['category_id'])): ?>
            <li class="breadcrumb-item"><a href="category.php?id=<?php echo (int)$product['category_id']; ?>"><?php echo htmlspecialchars($cat_name); ?></a></li>
          <?php endif; ?>
          <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
      </nav>

      <div class="row g-4 g-lg-5">
        
        <!-- Gallery Col -->
        <div class="col-lg-7 product-gallery-container">
          <div class="product-gallery-main position-relative">
            <span class="badge bg-dark position-absolute top-0 start-0 m-3 text-uppercase px-2 py-1"><?php echo htmlspecialchars($brand_name); ?></span>
            <img id="mainProductImage" src="<?php echo htmlspecialchars($main_image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; max-height: 520px; object-fit: cover;">
          </div>

          <!-- Thumbnails -->
          <div class="row g-2 mt-2">
            <div class="col-3">
              <div class="product-thumb-item active" data-img-src="<?php echo htmlspecialchars($main_image_url); ?>">
                <img src="<?php echo htmlspecialchars($main_image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 80px; object-fit: cover;">
              </div>
            </div>
          </div>
        </div>

        <!-- Product Purchasing Information Col -->
        <div class="col-lg-5">
          <div class="product-purchase-box">
            
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-uppercase text-muted small fw-bold tracking-wider"><?php echo htmlspecialchars($cat_name); ?> &bull; SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
              <?php if ($product['stock_quantity'] > 0): ?>
                <span class="badge bg-success bg-opacity-10 text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> In Stock (<?php echo (int)$product['stock_quantity']; ?> left)</span>
              <?php else: ?>
                <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Out of Stock</span>
              <?php endif; ?>
            </div>

            <h1 class="h2 fw-bold font-editorial mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>

            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="product-rating mb-0">
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <span class="count ms-1 text-muted small">(4.9 Verified Reviews)</span>
              </div>
            </div>

            <div class="d-flex align-items-baseline gap-3 mb-4 pb-3 border-bottom">
              <span class="fs-2 fw-bold text-dark"><?php echo format_pkr($product['price_pkr']); ?></span>
              <?php if ($has_discount): ?>
                <span class="fs-5 text-muted text-decoration-line-through"><?php echo format_pkr($product['original_price_pkr']); ?></span>
                <span class="badge bg-danger">Save <?php echo $discount_pct; ?>%</span>
              <?php endif; ?>
            </div>

            <p class="text-muted small mb-4" style="line-height: 1.7;">
              <?php 
              if (!empty($product['short_description'])) {
                  echo nl2br(htmlspecialchars($product['short_description']));
              } elseif (!empty($product['description'])) {
                  echo nl2br(htmlspecialchars(substr($product['description'], 0, 200) . '...'));
              } else {
                  echo "Handcrafted from selected leather with superior craftsmanship and durable construction.";
              }
              ?>
            </p>

            <!-- Color Options -->
            <div class="mb-4">
              <label class="form-label-nova d-flex justify-content-between">
                <span>Selected Color: <strong id="selectedColorLabel" class="text-dark"><?php echo htmlspecialchars($variant_colors[0]); ?></strong></span>
              </label>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($variant_colors as $idx => $color): ?>
                  <span class="color-option <?php echo $idx === 0 ? 'active' : ''; ?>" 
                        data-color="<?php echo htmlspecialchars($color); ?>" 
                        title="<?php echo htmlspecialchars($color); ?>"
                        style="background-color: <?php echo (stripos($color, 'black') !== false) ? '#141414' : ((stripos($color, 'brown') !== false || stripos($color, 'tan') !== false) ? '#5a381e' : '#333'); ?>;">
                  </span>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Size Selector -->
            <div class="mb-4 size-selector-wrap">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label-nova mb-0">Select EU Size: <strong id="selectedSizeLabel" class="text-dark">EU <?php echo $variant_sizes[0]; ?></strong></label>
                <a href="size-guide.php" class="small text-warning text-decoration-underline"><i class="bi bi-rulers me-1"></i> Size Guide (cm)</a>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($variant_sizes as $idx => $size): ?>
                  <button type="button" class="size-pill <?php echo $idx === 0 ? 'active' : ''; ?>" data-size="EU <?php echo $size; ?>"><?php echo $size; ?></button>
                <?php endforeach; ?>
              </div>
              <small class="text-muted d-block mt-2">True to size. South Asian regular fitting.</small>
            </div>

            <!-- Quantity & Actions -->
            <div class="mb-4 d-flex align-items-center gap-3">
              <div class="quantity-control">
                <button type="button" class="quantity-btn quantity-btn-dec"><i class="bi bi-dash"></i></button>
                <input type="text" class="quantity-input" value="1" readonly>
                <button type="button" class="quantity-btn quantity-btn-inc"><i class="bi bi-plus"></i></button>
              </div>

              <button type="button" class="btn btn-nova-dark flex-grow-1" id="btnAddToCart">
                <i class="bi bi-bag-plus me-1"></i> Add To Bag
              </button>

              <button type="button" class="btn btn-outline-secondary px-3 py-2 btn-wishlist-action" title="Save to wishlist">
                <i class="bi bi-heart"></i>
              </button>
            </div>

            <!-- Instant Buy Now -->
            <div class="mb-4">
              <a href="checkout.php" class="btn btn-nova-tan w-100 py-3 text-center d-block">
                <i class="bi bi-lightning-charge me-1"></i> Buy Now &bull; Express Checkout
              </a>
            </div>

            <!-- Assurance Box -->
            <div class="bg-light p-3 rounded-1 border small">
              <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span><strong>Complimentary Delivery</strong> on qualifying orders across Pakistan.</span>
              </div>
              <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                <i class="bi bi-arrow-repeat text-primary fs-5"></i>
                <span><strong>Easy Exchange Policy</strong> with convenient courier pickup.</span>
              </div>
              <div class="d-flex align-items-center gap-2 text-dark">
                <i class="bi bi-cash-stack text-warning fs-5"></i>
                <span><strong>Cash on Delivery (COD)</strong> accepted nationwide.</span>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- Product Specifications Tabs -->
      <div class="mt-5 pt-4 border-top">
        <ul class="nav nav-tabs nav-tabs-nova mb-4" id="productDetailTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc-pane" type="button" role="tab">Description & Fit</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs-pane" type="button" role="tab">Specifications</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping-pane" type="button" role="tab">Shipping & Returns</button>
          </li>
        </ul>

        <div class="tab-content bg-white p-4 border rounded-1 shadow-sm" id="productDetailTabContent">
          <!-- Tab 1 -->
          <div class="tab-pane fade show active" id="desc-pane" role="tabpanel">
            <div class="row">
              <div class="col-lg-8">
                <h4 class="font-editorial mb-3"><?php echo htmlspecialchars($product['name']); ?></h4>
                <div class="text-muted" style="line-height: 1.8;">
                  <?php if (!empty($product['description'])): ?>
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                  <?php else: ?>
                    <p>Designed with meticulous attention to detail, this footwear model is shaped with ergonomic contours to deliver supreme comfort and longevity.</p>
                  <?php endif; ?>
                </div>
                <h5 class="fs-6 fw-bold text-uppercase mt-4 mb-2">Key Highlights:</h5>
                <ul class="text-muted small">
                  <li>Genuine leather craftsmanship with precision stitching.</li>
                  <li>Durable sole construction providing flexibility and grip.</li>
                  <li>Comfort-cushioned insole suitable for long hours of wear.</li>
                  <li>Hand-buffed edge dressing and finish.</li>
                </ul>
              </div>
              <div class="col-lg-4 border-start ps-lg-4">
                <h6 class="text-uppercase fw-bold text-muted small mb-3">Fitting Advice</h6>
                <p class="small text-muted mb-2">Standard South Asian regular fit. Designed to gently conform to your foot arch within days of wear.</p>
                <p class="small text-muted">Use genuine leather shoe polish or cream periodically to maintain luster.</p>
              </div>
            </div>
          </div>

          <!-- Tab 2 -->
          <div class="tab-pane fade" id="specs-pane" role="tabpanel">
            <table class="table table-bordered small">
              <tbody>
                <tr><th class="w-25 bg-light">Product SKU</th><td><?php echo htmlspecialchars($product['sku']); ?></td></tr>
                <tr><th class="bg-light">Brand</th><td><?php echo htmlspecialchars($brand_name); ?></td></tr>
                <tr><th class="bg-light">Category</th><td><?php echo htmlspecialchars($cat_name); ?></td></tr>
                <?php if (!empty($product['material'])): ?>
                  <tr><th class="bg-light">Upper Material</th><td><?php echo htmlspecialchars($product['material']); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['sole_type'])): ?>
                  <tr><th class="bg-light">Sole Construction</th><td><?php echo htmlspecialchars($product['sole_type']); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['occasion'])): ?>
                  <tr><th class="bg-light">Recommended Occasion</th><td><?php echo htmlspecialchars($product['occasion']); ?></td></tr>
                <?php endif; ?>
                <tr><th class="bg-light">Stock Status</th><td><?php echo ($product['stock_quantity'] > 0) ? 'Available in Stock (' . (int)$product['stock_quantity'] . ' units)' : 'Currently Unavailable'; ?></td></tr>
              </tbody>
            </table>
          </div>

          <!-- Tab 3 -->
          <div class="tab-pane fade" id="shipping-pane" role="tabpanel">
            <div class="row g-4">
              <div class="col-md-6">
                <h5 class="fs-6 fw-bold text-uppercase">Nationwide Shipping</h5>
                <p class="text-muted small">
                  Dispatched within 24 business hours via premier couriers (TCS, Leopard, CallCourier).
                </p>
                <ul class="text-muted small">
                  <li>Major Cities (Lahore, Karachi, Islamabad, Rawalpindi): 2-3 business days.</li>
                  <li>Rest of Pakistan: 3-5 business days.</li>
                  <li>Cash on Delivery available on all domestic deliveries.</li>
                </ul>
              </div>
              <div class="col-md-6 border-start ps-md-4">
                <h5 class="fs-6 fw-bold text-uppercase">Hassle-Free Exchange</h5>
                <p class="text-muted small">
                  If the size does not fit comfortably, contact our customer helpline to arrange a swift size swap. Footwear must remain unworn and in original boxed packaging.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <?php if (!empty($related_products)): ?>
      <section class="mt-5 pt-4">
        <div class="d-flex justify-content-between align-items-end mb-4">
          <div>
            <span class="text-uppercase text-muted small fw-bold tracking-wider">Discover More</span>
            <h2 class="h3 fw-bold font-editorial mb-0">You May Also Admire</h2>
          </div>
          <a href="shop.php" class="btn btn-sm btn-nova-outline">View Catalog <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-3 g-md-4">
          <?php foreach ($related_products as $rel): ?>
            <?php 
              $rel_img = get_product_image_url($rel['image_url']);
              $rel_link = "product-details.php?id=" . (int)$rel['id'];
              $rel_cat = !empty($rel['category_name']) ? $rel['category_name'] : 'Footwear';
            ?>
            <div class="col-lg-3 col-md-6 col-6 col-product-grid">
              <div class="product-card h-100 d-flex flex-column justify-content-between">
                <div class="product-img-wrap position-relative">
                  <a href="<?php echo $rel_link; ?>">
                    <img src="<?php echo htmlspecialchars($rel_img); ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>" loading="lazy" style="width: 100%; height: 240px; object-fit: cover;">
                  </a>
                  <div class="product-quick-actions">
                    <a href="<?php echo $rel_link; ?>" class="quick-view-btn text-decoration-none">
                      <i class="bi bi-eye me-1"></i> View Details
                    </a>
                  </div>
                </div>
                <div class="product-info flex-grow-1 d-flex flex-column justify-content-between">
                  <div>
                    <div class="product-category"><?php echo htmlspecialchars($rel_cat); ?></div>
                    <h3 class="product-name">
                      <a href="<?php echo $rel_link; ?>"><?php echo htmlspecialchars($rel['name']); ?></a>
                    </h3>
                  </div>
                  <div class="product-price-box mt-2">
                    <span class="current-price"><?php echo format_pkr($rel['price_pkr']); ?></span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

    </div>
  </main>

<?php require_once 'partials/footer.php'; ?>
