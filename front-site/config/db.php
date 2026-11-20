<?php
/**
 * Database Connection & Data Access Layer for SHOES LAB Front-Site
 * Connects to 'ecomdb' (ecom-db)
 */

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'ecomdb');

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Log error or display message
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

// Global PDO instance for backward compatibility
$pdo = get_db_connection();

/**
 * Resolve product image URL properly
 */
function get_product_image_url($image_path, $fallback = null) {
    if (empty($fallback)) {
        $fallback = 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?q=80&w=800&auto=format&fit=crop';
    }

    if (empty($image_path)) {
        return $fallback;
    }

    $image_path = trim($image_path);

    // If already absolute URL
    if (preg_match('/^https?:\/\//i', $image_path)) {
        return $image_path;
    }

    $clean_path = ltrim($image_path, '/\\');

    // 1. Check if front-site upload exists (direct relative path e.g. uploads/products/...)
    $front_disk_path = __DIR__ . '/../' . $clean_path;
    if (file_exists($front_disk_path)) {
        return $clean_path;
    }

    // 2. Check if dashboard upload exists on disk
    $dashboard_disk_path = __DIR__ . '/../../dashboard/' . $clean_path;
    if (file_exists($dashboard_disk_path)) {
        $parent_dir = dirname($front_disk_path);
        if (!is_dir($parent_dir)) {
            @mkdir($parent_dir, 0777, true);
        }
        @copy($dashboard_disk_path, $front_disk_path);
        return $clean_path;
    }

    // 3. If starts with uploads/ return clean_path
    if (strpos($clean_path, 'uploads/') === 0) {
        return $clean_path;
    }

    return $fallback;
}

/**
 * Format price in PKR
 */
function format_pkr($amount) {
    if ($amount === null || $amount === '') return 'PKR 0';
    return 'PKR ' . number_format((float)$amount, 0);
}

/**
 * Get all categories with product count
 */
function get_categories($only_with_products = false) {
    $db = get_db_connection();
    if (!$db) return [];

    try {
        $sql = "
            SELECT c.*, COUNT(p.id) AS product_count 
            FROM categories c 
            LEFT JOIN products p ON p.category_id = c.id AND (p.status = 'Active' OR p.status IS NULL)
            GROUP BY c.id 
            ORDER BY c.name ASC
        ";
        $stmt = $db->query($sql);
        $categories = $stmt->fetchAll();

        if ($only_with_products) {
            $categories = array_filter($categories, function($cat) {
                return $cat['product_count'] > 0;
            });
        }
        return $categories;
    } catch (PDOException $e) {
        error_log("get_categories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all brands with product count
 */
function get_brands() {
    $db = get_db_connection();
    if (!$db) return [];

    try {
        $sql = "
            SELECT b.*, COUNT(p.id) AS product_count 
            FROM brands b 
            LEFT JOIN products p ON p.brand_id = b.id AND (p.status = 'Active' OR p.status IS NULL)
            GROUP BY b.id 
            ORDER BY b.name ASC
        ";
        return $db->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("get_brands error: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch products with dynamic filters
 */
function get_products($filters = []) {
    $db = get_db_connection();
    if (!$db) return [];

    $where = [];
    $params = [];

    // Filter by status (default active, allow 'all' for admin preview)
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where[] = "p.status = :status";
        $params[':status'] = $filters['status'];
    } elseif (empty($filters['allow_draft'])) {
        // Show Active products by default, or all if fewer than 2 active exist
        $where[] = "(p.status = 'Active' OR p.status = 'Draft')";
    }

    // Filter by Category
    if (!empty($filters['category_id'])) {
        $where[] = "p.category_id = :cat_id";
        $params[':cat_id'] = $filters['category_id'];
    } elseif (!empty($filters['category_name'])) {
        $where[] = "c.name LIKE :cat_name";
        $params[':cat_name'] = '%' . $filters['category_name'] . '%';
    }

    // Filter by Brand
    if (!empty($filters['brand_id'])) {
        $where[] = "p.brand_id = :brand_id";
        $params[':brand_id'] = $filters['brand_id'];
    }

    // Filter by Search Keyword
    if (!empty($filters['search'])) {
        $where[] = "(p.name LIKE :q OR p.sku LIKE :q OR p.material LIKE :q OR p.occasion LIKE :q OR c.name LIKE :q OR b.name LIKE :q)";
        $params[':q'] = '%' . $filters['search'] . '%';
    }

    // Price range filters
    if (!empty($filters['min_price'])) {
        $where[] = "p.price_pkr >= :min_price";
        $params[':min_price'] = (float)$filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $where[] = "p.price_pkr <= :max_price";
        $params[':max_price'] = (float)$filters['max_price'];
    }

    // Is Sale
    if (!empty($filters['is_sale'])) {
        $where[] = "p.is_sale = 1";
    }

    // Is New
    if (!empty($filters['is_new'])) {
        $where[] = "p.is_new = 1";
    }

    $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Sorting
    $order_by = "p.id DESC";
    $sort = $filters['sort'] ?? '';
    switch ($sort) {
        case 'price-asc':
            $order_by = "p.price_pkr ASC";
            break;
        case 'price-desc':
            $order_by = "p.price_pkr DESC";
            break;
        case 'name-asc':
            $order_by = "p.name ASC";
            break;
        case 'name-desc':
            $order_by = "p.name DESC";
            break;
        case 'oldest':
            $order_by = "p.id ASC";
            break;
        case 'newest':
        default:
            $order_by = "p.id DESC";
            break;
    }

    // Pagination / Limit
    $limit_sql = "";
    if (!empty($filters['limit'])) {
        $limit = (int)$filters['limit'];
        $offset = !empty($filters['offset']) ? (int)$filters['offset'] : 0;
        $limit_sql = "LIMIT $offset, $limit";
    }

    $sql = "
        SELECT 
            p.*, 
            c.name AS category_name, 
            b.name AS brand_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        $where_sql 
        ORDER BY $order_by 
        $limit_sql
    ";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("get_products error: " . $e->getMessage());
        return [];
    }
}

/**
 * Helper alias for single product or products list (prevents undefined function errors)
 */
function get_product($param = null) {
    if (is_array($param)) {
        return get_products($param);
    }
    if (is_numeric($param)) {
        return get_product_by_id_or_slug((int)$param);
    }
    if (is_string($param)) {
        return get_product_by_id_or_slug(null, $param);
    }
    return get_products();
}

/**
 * Get product by ID or Slug
 */
function get_product_by_id_or_slug($id = null, $slug = null) {
    $db = get_db_connection();
    if (!$db) return null;

    $where = [];
    $params = [];

    if (!empty($id)) {
        $where[] = "p.id = :id";
        $params[':id'] = (int)$id;
    } elseif (!empty($slug)) {
        $where[] = "p.slug = :slug";
        $params[':slug'] = $slug;
    } else {
        // Fallback: get first available product
        $where[] = "1=1";
    }

    $where_sql = implode(" AND ", $where);
    $sql = "
        SELECT 
            p.*, 
            c.name AS category_name, 
            b.name AS brand_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE $where_sql 
        LIMIT 1
    ";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $prod = $stmt->fetch();
        if ($prod) {
            $prod['variants'] = get_product_variants($prod['id']);
        }
        return $prod ?: null;
    } catch (PDOException $e) {
        error_log("get_product_by_id_or_slug error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get variants for a product
 */
function get_product_variants($product_id) {
    $db = get_db_connection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT * FROM product_variants 
            WHERE product_id = :pid 
            ORDER BY size_eu ASC
        ");
        $stmt->execute([':pid' => (int)$product_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("get_product_variants error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get store settings key-value pair
 */
function get_store_settings() {
    $db = get_db_connection();
    if (!$db) return [];

    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM store_settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get website content (hero headlines, etc.)
 */
function get_website_content() {
    $db = get_db_connection();
    if (!$db) return [];

    try {
        $stmt = $db->query("SELECT * FROM website_content ORDER BY id DESC LIMIT 1");
        return $stmt->fetch() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}
