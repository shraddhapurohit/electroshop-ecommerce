<?php
// ============================================================
//  includes/db.php  –  Database Connection (MySQLi + PDO)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'electroshop');

// ── MySQLi connection (used throughout the project) ─────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;color:#c00;padding:20px;">
         <h3>Database Connection Failed</h3>
         <p>' . $conn->connect_error . '</p>
         <p>Please check your MySQL credentials in <code>includes/db.php</code></p>
         </div>');
}

$conn->set_charset('utf8mb4');

// ── Helper: sanitise output to prevent XSS ──────────────────
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ── Helper: format price in Indian Rupees ───────────────────
function formatPrice($amount) {
    return '₹' . number_format($amount, 2);
}

// ── Helper: generate unique order number ────────────────────
function generateOrderNumber() {
    return 'ES' . date('Ymd') . rand(1000, 9999);
}

// ── Helper: create URL-safe slug ────────────────────────────
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return $string;
}

// ── Helper: star rating HTML ────────────────────────────────
function starRating($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - $rating < 1 && $i - $rating > 0) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $html;
}

// ── Helper: get cart count for logged-in user ───────────────
function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
    $count = 0;
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count;
}

// ── Helper: get primary image for a product ─────────────────
function getProductImage($conn, $product_id) {
    $image = '';
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $stmt->bind_result($image);
    $stmt->fetch();
    $stmt->close();
    return $image ?: 'placeholder.png';
}

// ── Helper: redirect ────────────────────────────────────────
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

// ── Start session if not already started ────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
