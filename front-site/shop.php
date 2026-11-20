<?php
$page_title = "Shop All Men's Footwear | SHOES LAB Pakistan";
$page_description = "Explore SHOES LAB's complete footwear catalogue. Handcrafted leather shoes, loafers, sneakers, and sandals with cash on delivery across Pakistan.";
require_once 'partials/header.php';

// Retrieve filter params
$selected_cat_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : (isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0);
$selected_cat_name = isset($_GET['category']) ? trim($_GET['category']) : '';
$selected_brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
$is_sale = !empty($_GET['is_sale']) ? 1 : 0;
$is_new = !empty($_GET['is_new']) ? 1 : 0;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Load filter datasets
$categories = get_categories();
$brands = get_brands();

// If cat_id passed, find category name
if ($selected_cat_id && empty($selected_cat_name)) {
    foreach ($categories as $c) {
        if ($c['id'] == $selected_cat_id) {
            $selected_cat_name = $c['name'];
            break;
        }
    }
}

// Fetch products from database
$filter_args = [
    'category_id'   => $selected_cat_id ?: null,
    'category_name' => $selected_cat_name ?: null,
    'brand_id'      => $selected_brand_id ?: null,
    'search'        => $search_query ?: null,
    'is_sale'       => $is_sale ?: null,
    'is_new'        => $is_new ?: null,
    'sort'          => $sort,
];

$products = get_products($filter_args);
$total_products = count($products);

// Get distinct available sizes from database
$db = get_db_connection();
$available_sizes = [];
if ($db) {
    try {
        $available_sizes = $db->query("SELECT DISTINCT size_eu FROM product_variants WHERE size_eu > 0 ORDER BY size_eu ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}
if (empty($available_sizes)) {
    $available_sizes = [39, 40, 41, 42, 43, 44, 45, 46];
}
?>

<main class="py-4">
    <div class="container">
      
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-nova">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active" aria-current="page">Shop All Footwear</li>
          <?php if (!empty($selected_cat_name)): ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($selected_cat_name); ?></li>
          <?php endif; ?>
        </ol>
      </nav>

      <!-- Page Header -->
      <div class="border-bottom pb-4 mb-4">
        <div class="row align-items-end">
          <div class="col-lg-8">
            <h1 class="h2 fw-bold font-editorial mb-2">
              <?php 
              if (!empty($selected_cat_name)) {
                  echo htmlspecialchars($selected_cat_name) . " Collection";
              } elseif (!empty($search_query)) {
                  echo "Search Results for \"" . htmlspecialchars($search_query) . "\"";
              } elseif ($is_sale) {
                  echo "Special Offers & Sale Footwear";
              } else {
                  echo "Men's Handcrafted Footwear Collection";
              }
              ?>
            </h1>
            <p class="text-muted mb-0 small">
              Engineered with full-grain leather, welted soles, and timeless silhouettes. Hand-finished for contemporary life.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <span class="badge bg-light text-dark border p-2">Showing <strong id="visibleProductsCount"><?php echo $total_products; ?></strong> Products</span>
          </div>
        </div>
      </div>

      <div class="row g-4">
        
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
          <div class="p-3 bg-white border rounded-1 shadow-sm shop-sidebar">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
              <h5 class="fs-6 fw-bold text-uppercase mb-0"><i class="bi bi-funnel me-1"></i> Refine Catalog</h5>
              <a href="shop.php" class="text-muted small text-decoration-underline">Reset All</a>
            </div>

            <!-- Category Filter -->
            <div class="mb-4">
              <label class="form-label-nova">Category</label>
              <div class="d-flex flex-column gap-1">
                <?php if (!empty($categories)): ?>
                  <?php foreach ($categories as $cat): ?>
                    <?php 
                      $cat_checked = ($selected_cat_id == $cat['id'] || strcasecmp($selected_cat_name, $cat['name']) === 0);
                    ?>
                    <div class="form-check">
                      <input class="form-check-input filter-category-chk" type="checkbox" 
                             value="<?php echo htmlspecialchars($cat['name']); ?>" 
                             id="cat_<?php echo $cat['id']; ?>"
                             <?php echo $cat_checked ? 'checked' : ''; ?>
                             onchange="window.location.href = this.checked ? 'shop.php?category_id=<?php echo $cat['id']; ?>' : 'shop.php';">
                      <label class="form-check-label small text-muted d-flex justify-content-between" for="cat_<?php echo $cat['id']; ?>">
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="badge bg-light text-dark"><?php echo (int)$cat['product_count']; ?></span>
                      </label>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted small">No categories found.</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Brand Filter -->
            <?php if (!empty($brands)): ?>
            <div class="mb-4">
              <label class="form-label-nova">Atelier Brand</label>
              <div class="d-flex flex-column gap-1">
                <?php foreach ($brands as $brand): ?>
                  <?php $brand_checked = ($selected_brand_id == $brand['id']); ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="brand_<?php echo $brand['id']; ?>"
                           <?php echo $brand_checked ? 'checked' : ''; ?>
                           onchange="window.location.href = this.checked ? 'shop.php?brand_id=<?php echo $brand['id']; ?>' : 'shop.php';">
                    <label class="form-check-label small text-muted d-flex justify-content-between" for="brand_<?php echo $brand['id']; ?>">
                      <span><?php echo htmlspecialchars($brand['name']); ?></span>
                      <span class="badge bg-light text-dark"><?php echo (int)$brand['product_count']; ?></span>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Shoe Size Filter -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label-nova mb-0">Shoe Size (EU)</label>
                <a href="size-guide.php" class="small text-warning text-decoration-none">Guide</a>
              </div>
              <div class="d-flex flex-wrap gap-1">
                <?php foreach ($available_sizes as $sz): ?>
                  <a href="shop.php?size=<?php echo (int)$sz; ?>" class="size-pill py-1 px-2 small text-decoration-none text-dark">
                    <?php echo (int)$sz; ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Special Filter Badges -->
            <div class="mb-4">
              <label class="form-label-nova">Offers & Collections</label>
              <div class="d-flex flex-column gap-2">
                <a href="shop.php?is_sale=1" class="btn btn-sm <?php echo $is_sale ? 'btn-danger' : 'btn-outline-danger'; ?> text-start">
                  <i class="bi bi-tag-fill me-1"></i> Special Discounted Items
                </a>
                <a href="shop.php?is_new=1" class="btn btn-sm <?php echo $is_new ? 'btn-dark' : 'btn-outline-dark'; ?> text-start">
                  <i class="bi bi-sparkles me-1"></i> New Season Arrivals
                </a>
              </div>
            </div>

          </div>
        </div>

        <!-- Product Grid & Toolbar -->
        <div class="col-lg-9">
          
          <!-- Toolbar -->
          <div class="bg-white p-3 border rounded-1 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <form action="shop.php" method="GET" class="w-100" style="max-width: 340px;">
              <?php if ($selected_cat_id): ?>
                <input type="hidden" name="category_id" value="<?php echo $selected_cat_id; ?>">
              <?php endif; ?>
              <?php if ($selected_brand_id): ?>
                <input type="hidden" name="brand_id" value="<?php echo $selected_brand_id; ?>">
              <?php endif; ?>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" id="shopSearchInput" class="form-control border-start-0" 
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       placeholder="Filter by shoe title or style...">
                <?php if (!empty($search_query)): ?>
                  <a href="shop.php" class="btn btn-outline-secondary border-start-0"><i class="bi bi-x"></i></a>
                <?php endif; ?>
              </div>
            </form>

            <div class="d-flex align-items-center gap-3 w-100 w-md-auto justify-content-end">
              <label for="shopSortSelect" class="small text-muted text-nowrap mb-0 fw-semibold">Sort By:</label>
              <select id="shopSortSelect" class="form-select form-select-sm" style="min-width: 180px;" onchange="location = this.value;">
                <option value="shop.php?sort=newest<?php echo $selected_cat_id ? '&category_id='.$selected_cat_id : ''; ?>" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest Additions</option>
                <option value="shop.php?sort=price-asc<?php echo $selected_cat_id ? '&category_id='.$selected_cat_id : ''; ?>" <?php echo $sort == 'price-asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="shop.php?sort=price-desc<?php echo $selected_cat_id ? '&category_id='.$selected_cat_id : ''; ?>" <?php echo $sort == 'price-desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="shop.php?sort=name-asc<?php echo $selected_cat_id ? '&category_id='.$selected_cat_id : ''; ?>" <?php echo $sort == 'name-asc' ? 'selected' : ''; ?>>Name: A to Z</option>
              </select>
            </div>
          </div>

          <!-- Product Grid -->
          <div class="row g-3 g-md-4" id="shopProductGrid">
            <?php if (!empty($products)): ?>
              <?php foreach ($products as $prod): ?>
                <?php 
                  $img_src = get_product_image_url($prod['image_url']);
                  $cat_name = !empty($prod['category_name']) ? $prod['category_name'] : 'Footwear';
                  $brand_name = !empty($prod['brand_name']) ? $prod['brand_name'] : 'SHOES LAB';
                  $has_discount = (!empty($prod['original_price_pkr']) && $prod['original_price_pkr'] > $prod['price_pkr']);
                  $prod_link = "product-details.php?id=" . (int)$prod['id'];
                ?>
                <div class="col-lg-4 col-md-6 col-6 col-product-grid shop-product-item" 
                     data-name="<?php echo htmlspecialchars($prod['name']); ?>" 
                     data-category="<?php echo htmlspecialchars($cat_name); ?>" 
                     data-price="<?php echo (float)$prod['price_pkr']; ?>">
                  
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
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <span class="product-category"><?php echo htmlspecialchars($cat_name); ?></span>
                          <span class="badge bg-light text-muted small"><?php echo htmlspecialchars($brand_name); ?></span>
                        </div>
                        <h3 class="product-name">
                          <a href="<?php echo $prod_link; ?>"><?php echo htmlspecialchars($prod['name']); ?></a>
                        </h3>
                        <?php if (!empty($prod['material'])): ?>
                          <div class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($prod['material']); ?></div>
                        <?php endif; ?>
                      </div>

                      <div>
                        <div class="product-price-box">
                          <span class="current-price"><?php echo format_pkr($prod['price_pkr']); ?></span>
                          <?php if ($has_discount): ?>
                            <span class="original-price"><?php echo format_pkr($prod['original_price_pkr']); ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                          <span class="small text-muted"><i class="bi bi-box-seam me-1"></i><?php echo ($prod['stock_quantity'] > 0) ? 'In Stock (' . (int)$prod['stock_quantity'] . ')' : 'Out of Stock'; ?></span>
                          <a href="<?php echo $prod_link; ?>" class="btn btn-sm btn-outline-dark px-2 py-1"><i class="bi bi-bag-plus"></i> Buy</a>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12 py-5 text-center">
                <div class="py-5 bg-white border rounded-1 p-4">
                  <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                  <h4 class="fw-bold">No Products Found</h4>
                  <p class="text-muted mb-4">No products match your selected criteria. Try adjusting your filters or search terms.</p>
                  <a href="shop.php" class="btn btn-nova-dark px-4 py-2">View All Products</a>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Pagination Info -->
          <?php if ($total_products > 0): ?>
          <div class="mt-4 pt-3 border-top text-center text-muted small">
            Displaying all <strong><?php echo $total_products; ?></strong> product models from our database inventory.
          </div>
          <?php endif; ?>

        </div>

      </div>

    </div>
  </main>

<?php require_once 'partials/footer.php'; ?>
