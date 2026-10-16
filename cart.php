
<?php
session_start();
require_once "db.php";

// Login protection
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION["user_name"] ?? "Customer";

// Initialize cart
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Add product
if (isset($_GET["add"])) {
    $product_id = (int) $_GET["add"];

    if ($product_id > 0) {
        if (isset($_SESSION["cart"][$product_id])) {
            $_SESSION["cart"][$product_id]++;
        } else {
            $_SESSION["cart"][$product_id] = 1;
        }
    }

    header("Location: cart.php");
    exit;
}

// Remove product
if (isset($_GET["remove"])) {
    $product_id = (int) $_GET["remove"];

    unset($_SESSION["cart"][$product_id]);

    header("Location: cart.php");
    exit;
}

// Update quantities
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["quantities"]) && is_array($_POST["quantities"])) {
        foreach ($_POST["quantities"] as $product_id => $quantity) {
            $product_id = (int) $product_id;
            $quantity = (int) $quantity;

            if ($product_id > 0) {
                if ($quantity <= 0) {
                    unset($_SESSION["cart"][$product_id]);
                } else {
                    $_SESSION["cart"][$product_id] = $quantity;
                }
            }
        }
    }

    header("Location: cart.php");
    exit;
}

// Fetch cart products
$cart_products = [];
$total = 0;

if (!empty($_SESSION["cart"])) {
    $product_ids = array_map("intval", array_keys($_SESSION["cart"]));
    $product_ids = array_filter($product_ids, fn($id) => $id > 0);

    if (!empty($product_ids)) {
        $placeholders = implode(",", array_fill(0, count($product_ids), "?"));
        $types = str_repeat("i", count($product_ids));

        $stmt = $conn->prepare(
            "SELECT id, name, description, price, image
             FROM products
             WHERE id IN ($placeholders)"
        );

        $product_ids = array_values($product_ids);
        $stmt->bind_param($types, ...$product_ids);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($product = $result->fetch_assoc()) {
            $id = (int) $product["id"];
            $quantity = (int) ($_SESSION["cart"][$id] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $product["quantity"] = $quantity;
            $product["subtotal"] = (float) $product["price"] * $quantity;

            $total += $product["subtotal"];
            $cart_products[] = $product;
        }

        $stmt->close();
    }
}

$cart_count = 0;
foreach ($cart_products as $item) {
    $cart_count += $item["quantity"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | NovaStore</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .cart-page {
            min-height: 65vh;
            padding: 45px 20px 70px;
            background: #f7f8fc;
        }

        .cart-container {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }

        .cart-heading {
            margin-bottom: 30px;
        }

        .cart-heading > span {
            display: inline-block;
            margin-bottom: 10px;
            color: #6558f5;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.7px;
        }

        .cart-heading h1 {
            margin: 0 0 10px;
            color: #202137;
            font-size: clamp(30px, 4vw, 42px);
        }

        .cart-heading p {
            margin: 0;
            color: #77798d;
            line-height: 1.7;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            align-items: start;
            gap: 25px;
        }

        .cart-items {
            display: grid;
            gap: 16px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 125px minmax(0, 1fr);
            gap: 20px;
            padding: 20px;
            border: 1px solid #e9eaf2;
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 7px 22px rgba(30, 30, 70, 0.035);
        }

        .cart-item-image {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 125px;
            height: 130px;
            overflow: hidden;
            border-radius: 13px;
            background: #f1f0ff;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .cart-placeholder {
            font-size: 40px;
        }

        .cart-item-info {
            min-width: 0;
        }

        .cart-item-info > span {
            color: #6558f5;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .cart-item-info h2 {
            margin: 8px 0;
            color: #25263a;
            font-size: 19px;
            line-height: 1.4;
        }

        .cart-item-info p {
            margin: 0 0 12px;
            color: #77798d;
            font-size: 13px;
            line-height: 1.6;
        }

        .cart-unit-price {
            color: #24253a;
            font-size: 17px;
        }

        .cart-item-controls {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            grid-column: 1 / -1;
            padding-top: 17px;
            border-top: 1px solid #eeeef4;
        }

        .cart-quantity label,
        .cart-subtotal span {
            display: block;
            margin-bottom: 8px;
            color: #77798d;
            font-size: 12px;
            font-weight: 600;
        }

        .cart-quantity input {
            width: 100px;
            min-height: 42px;
            padding: 8px 10px;
            border: 1px solid #dedfed;
            border-radius: 9px;
            background: #fff;
            color: #25263a;
            font: inherit;
        }

        .cart-quantity input:focus {
            outline: 2px solid #dcd8ff;
            border-color: #6558f5;
        }

        .cart-subtotal strong {
            color: #25263a;
            font-size: 16px;
        }

        .cart-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 39px;
            height: 39px;
            border: 1px solid #ffd9df;
            border-radius: 10px;
            background: #fff3f5;
            color: #d93654;
            font-size: 19px;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .cart-remove:hover {
            background: #ffe1e7;
            transform: translateY(-2px);
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .update-cart-btn,
        .continue-shopping-btn,
        .place-order-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .update-cart-btn {
            border: 1px solid #dcd8ff;
            background: #efedff;
            color: #5144df;
        }

        .update-cart-btn:hover {
            background: #e3dfff;
        }

        .continue-shopping-btn {
            border: 1px solid #dedbff;
            background: #fff;
            color: #5144df;
        }

        .continue-shopping-btn:hover {
            background: #f3f1ff;
            transform: translateY(-2px);
        }

        .cart-summary {
            position: sticky;
            top: 22px;
            padding: 25px;
            border: 1px solid #e9e8f5;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(30, 30, 70, 0.055);
        }

        .cart-summary-label {
            color: #6558f5;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
        }

        .cart-summary h2 {
            margin: 8px 0 22px;
            color: #25263a;
            font-size: 22px;
        }

        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 0;
            border-bottom: 1px solid #eeeef4;
            color: #77798d;
            font-size: 14px;
        }

        .cart-summary-row strong {
            color: #25263a;
        }

        .cart-summary-row.cart-grand-total {
            padding: 20px 0;
            border-bottom: 0;
            color: #25263a;
            font-size: 15px;
            font-weight: 700;
        }

        .cart-grand-total strong {
            color: #5144df;
            font-size: 22px;
        }

        .place-order-btn {
            width: 100%;
            margin-top: 8px;
            border: 0;
            background: linear-gradient(110deg, #5d52ef, #8070ff);
            color: #fff;
            box-shadow: 0 8px 18px rgba(101, 88, 245, 0.22);
        }

        .place-order-btn:hover {
            box-shadow: 0 11px 23px rgba(101, 88, 245, 0.3);
            transform: translateY(-2px);
        }

        .cart-secure-note {
            margin: 16px 0 0;
            color: #85869a;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        .empty-cart {
            padding: 65px 25px;
            border: 1px solid #e9e8f5;
            border-radius: 20px;
            background: #fff;
            text-align: center;
            box-shadow: 0 10px 30px rgba(30, 30, 70, 0.04);
        }

        .empty-cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 85px;
            height: 85px;
            margin: 0 auto 22px;
            border-radius: 50%;
            background: #f0eeff;
            font-size: 38px;
        }

        .empty-cart h2 {
            margin: 0 0 10px;
            color: #25263a;
            font-size: 25px;
        }

        .empty-cart p {
            max-width: 430px;
            margin: 0 auto 23px;
            color: #77798d;
            line-height: 1.7;
        }

        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }
        }

        @media (max-width: 550px) {
            .cart-page {
                padding: 30px 14px 45px;
            }

            .cart-item {
                grid-template-columns: 85px minmax(0, 1fr);
                gap: 13px;
                padding: 14px;
            }

            .cart-item-image {
                width: 85px;
                height: 100px;
            }

            .cart-item-info h2 {
                font-size: 16px;
            }

            .cart-item-info p {
                font-size: 12px;
            }

            .cart-item-controls {
                align-items: center;
            }

            .cart-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .cart-actions > * {
                width: 100%;
            }

            .cart-summary {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="logo">
        Nova<span>Store</span>
    </div>

    <nav>
        <a href="index.php#home">Home</a>
        <a href="index.php#products">Products</a>
        <a href="index.php#features">Features</a>
        <a href="index.php#contact">Contact</a>
        <a href="orders.php">My Orders</a>
    </nav>

    <div class="nav-actions">
        <a href="dashboard.php" class="nav-login">
            👤 <?php echo htmlspecialchars($user_name); ?>
        </a>
        <a href="logout.php" class="nav-signup">Logout</a>
    </div>
</header>

<main class="cart-page">
    <div class="cart-container">

        <div class="cart-heading">
            <span>NOVASTORE SHOPPING CART</span>
            <h1>Your Cart 🛒</h1>
            <p>Review your selected products before proceeding to checkout.</p>
        </div>

        <?php if (empty($cart_products)): ?>

            <section class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p>
                    Looks like you haven't added any products yet.
                    Explore our collection and find something you'll love.
                </p>
                <a href="index.php#products" class="continue-shopping-btn">
                    🛍️ Start Shopping
                </a>
            </section>

        <?php else: ?>

            <div class="cart-layout">

                <section class="cart-products-section">
                    <form method="POST" action="cart.php" class="cart-form">

                        <div class="cart-items">

                            <?php foreach ($cart_products as $product): ?>
                                <article class="cart-item">

                                    <div class="cart-item-image">
                                        <?php
                                        $image_name = basename(
                                            (string) ($product["image"] ?? "")
                                        );
                                        $image_file = "images/" . $image_name;
                                        ?>

                                        <?php if (
                                            $image_name !== "" &&
                                            is_file(__DIR__ . "/" . $image_file)
                                        ): ?>
                                            <img
                                                src="<?php echo htmlspecialchars($image_file); ?>"
                                                alt="<?php echo htmlspecialchars($product["name"]); ?>"
                                            >
                                        <?php else: ?>
                                            <div class="cart-placeholder">🛍️</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="cart-item-info">
                                        <span>NovaStore Collection</span>

                                        <h2>
                                            <?php echo htmlspecialchars($product["name"]); ?>
                                        </h2>

                                        <p>
                                            <?php echo htmlspecialchars((string) ($product["description"] ?? "")); ?>
                                        </p>

                                        <strong class="cart-unit-price">
                                            ₹<?php echo number_format((float) $product["price"], 2); ?>
                                        </strong>
                                    </div>

                                    <div class="cart-item-controls">

                                        <div class="cart-quantity">
                                            <label for="quantity-<?php echo (int) $product["id"]; ?>">
                                                Quantity
                                            </label>

                                            <input
                                                id="quantity-<?php echo (int) $product["id"]; ?>"
                                                type="number"
                                                name="quantities[<?php echo (int) $product["id"]; ?>]"
                                                value="<?php echo (int) $product["quantity"]; ?>"
                                                min="0"
                                                max="99"
                                                required
                                            >
                                        </div>

                                        <div class="cart-subtotal">
                                            <span>Item Subtotal</span>
                                            <strong>
                                                ₹<?php echo number_format((float) $product["subtotal"], 2); ?>
                                            </strong>
                                        </div>

                                        <a
                                            href="cart.php?remove=<?php echo (int) $product["id"]; ?>"
                                            class="cart-remove"
                                            aria-label="Remove <?php echo htmlspecialchars($product["name"]); ?>"
                                            title="Remove product"
                                        >✕</a>

                                    </div>
                                </article>
                            <?php endforeach; ?>

                        </div>

                        <div class="cart-actions">
                            <a href="index.php#products" class="continue-shopping-btn">
                                ← Continue Shopping
                            </a>

                            <button type="submit" class="update-cart-btn">
                                ↻ Update Cart
                            </button>
                        </div>

                    </form>
                </section>

                <aside class="cart-summary">
                    <span class="cart-summary-label">YOUR PURCHASE</span>
                    <h2>Order Summary</h2>

                    <div class="cart-summary-row">
                        <span>Items</span>
                        <strong><?php echo (int) $cart_count; ?></strong>
                    </div>

                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <strong>₹<?php echo number_format((float) $total, 2); ?></strong>
                    </div>

                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <strong>Calculated at checkout</strong>
                    </div>

                    <div class="cart-summary-row cart-grand-total">
                        <span>Order Total</span>
                        <strong>₹<?php echo number_format((float) $total, 2); ?></strong>
                    </div>

                    <a href="checkout.php" class="place-order-btn">
                        🛒 Proceed to Checkout →
                    </a>

                    <p class="cart-secure-note">
                        🔒 Please review your items and quantities before checkout.
                    </p>
                </aside>

            </div>

        <?php endif; ?>

    </div>
</main>

<footer>
    <div class="footer-logo">
        Nova<span>Store</span>
    </div>

    <p>Premium products for modern lifestyles.</p>

    <div class="footer-links">
        <a href="index.php#home">Home</a>
        <a href="index.php#products">Products</a>
        <a href="index.php#features">Features</a>
        <a href="index.php#contact">Contact</a>
        <a href="orders.php">My Orders</a>
    </div>

    <p class="copyright">
        © 2026 NovaStore. All rights reserved.
    </p>
</footer>

</body>
</html>
