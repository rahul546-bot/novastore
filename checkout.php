<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* SUCCESS PAGE DATA */
$order_success = isset($_GET["success"])
    && $_GET["success"] === "1"
    && !empty($_SESSION["order_ids"]);

$order_ids = [];
$order_total = 0;

if ($order_success) {
    $order_ids = $_SESSION["order_ids"];
    $order_total = (float) ($_SESSION["order_total"] ?? 0);

    unset($_SESSION["order_ids"], $_SESSION["order_total"]);
}

/* CART AND PRODUCT DATA */
$cart_items = [];
$total = 0;
$error = "";

if (!$order_success) {
    if (empty($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
        header("Location: cart.php");
        exit;
    }

    $cart = $_SESSION["cart"];

    $product_ids = array_values(array_unique(array_filter(
        array_map("intval", array_keys($cart)),
        fn($id) => $id > 0
    )));

    if (empty($product_ids)) {
        header("Location: cart.php");
        exit;
    }

    $placeholders = implode(",", array_fill(0, count($product_ids), "?"));
    $types = str_repeat("i", count($product_ids));

    $sql = "SELECT id, name, description, price, image
            FROM products
            WHERE id IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $product_id = (int) $product["id"];
        $quantity = (int) ($cart[$product_id] ?? 0);

        if ($quantity < 1) {
            continue;
        }

        $product["quantity"] = $quantity;
        $product["subtotal"] = (float) $product["price"] * $quantity;

        $cart_items[] = $product;
        $total += $product["subtotal"];
    }

    $stmt->close();

    if (empty($cart_items)) {
        header("Location: cart.php");
        exit;
    }

    /* PLACE ORDER */
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $transaction_started = false;

        try {
            $conn->begin_transaction();
            $transaction_started = true;

            $insert = $conn->prepare(
                "INSERT INTO orders
                (user_id, product_id, product_name, price, quantity, status)
                VALUES (?, ?, ?, ?, ?, 'Pending')"
            );

            $new_order_ids = [];

            foreach ($cart_items as $item) {
                $product_id = (int) $item["id"];
                $product_name = $item["name"];
                $price = (float) $item["price"];
                $quantity = (int) $item["quantity"];

                $insert->bind_param(
                    "iisdi",
                    $user_id,
                    $product_id,
                    $product_name,
                    $price,
                    $quantity
                );

                $insert->execute();
                $new_order_ids[] = $conn->insert_id;
            }

            $insert->close();
            $conn->commit();
            $transaction_started = false;

            $_SESSION["cart"] = [];
            $_SESSION["order_ids"] = $new_order_ids;
            $_SESSION["order_total"] = $total;

            header("Location: checkout.php?success=1");
            exit;

        } catch (Throwable $e) {
            if ($transaction_started) {
                $conn->rollback();
            }

            $error = "Order place nahi ho saka. Please try again.";
        }
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | NovaStore</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f6f5ff;
            color: #24213a;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .navbar {
            background: #fff;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            box-shadow: 0 3px 18px rgba(30, 20, 80, .07);
            flex-wrap: wrap;
        }

        .logo {
            color: #6c45d9;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .nav-links {
            display: flex;
            gap: 22px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            font-size: 14px;
            font-weight: 600;
            color: #514d66;
        }

        .nav-links a:hover {
            color: #7048df;
        }

        .page {
            width: min(1120px, 90%);
            margin: 42px auto 65px;
        }

        .page-heading {
            margin-bottom: 28px;
        }

        .eyebrow {
            color: #7048df;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            margin: 9px 0;
            font-size: clamp(30px, 5vw, 42px);
            letter-spacing: -1px;
        }

        .muted {
            color: #77738b;
            line-height: 1.6;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, .85fr);
            gap: 25px;
            align-items: start;
        }

        .card {
            background: #fff;
            border: 1px solid #eeebfa;
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 12px 35px rgba(45, 29, 105, .055);
            margin-bottom: 22px;
        }

        .card h2 {
            margin: 0 0 22px;
            font-size: 21px;
        }

        .product {
            display: flex;
            gap: 17px;
            align-items: center;
            padding: 17px 0;
            border-bottom: 1px solid #efedf6;
        }

        .product:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .product-image {
            width: 94px;
            height: 94px;
            flex-shrink: 0;
            object-fit: cover;
            border-radius: 14px;
            background: #f0ecff;
        }

        .image-placeholder {
            width: 94px;
            height: 94px;
            flex-shrink: 0;
            border-radius: 14px;
            background: #f0ecff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-info h3 {
            margin: 0 0 8px;
            font-size: 17px;
            overflow-wrap: anywhere;
        }

        .product-info p {
            margin: 5px 0;
            color: #77738b;
            font-size: 14px;
        }

        .product-price {
            color: #6c45d9;
            font-size: 17px;
            font-weight: 800;
            white-space: nowrap;
        }

        .customer-row {
            padding: 12px 0;
            border-bottom: 1px solid #efedf6;
            display: flex;
            justify-content: space-between;
            gap: 14px;
        }

        .customer-row:last-child {
            border-bottom: 0;
        }

        .customer-row span:first-child {
            color: #77738b;
        }

        .customer-row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 15px 0;
            color: #69657c;
        }

        .summary-total {
            border-top: 1px dashed #dcd6f1;
            margin-top: 20px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 19px;
            font-weight: 800;
        }

        .summary-total strong {
            color: #6c45d9;
        }

        .secure-note {
            background: #f3f0ff;
            color: #5d42a5;
            padding: 13px;
            border-radius: 12px;
            margin: 20px 0;
            font-size: 13px;
            line-height: 1.6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 12px;
            padding: 14px 18px;
            background: #7048df;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            transition: .2s;
        }

        .btn:hover {
            background: #5834c1;
            transform: translateY(-1px);
        }

        .btn-full {
            width: 100%;
        }

        .back-link {
            display: inline-block;
            color: #6c45d9;
            font-weight: 700;
            font-size: 14px;
            margin-top: 18px;
        }

        .error {
            background: #fff0f0;
            color: #a51f32;
            border: 1px solid #ffd4d9;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 20px;
        }

        .success-card {
            max-width: 720px;
            margin: 35px auto;
            text-align: center;
            padding: clamp(25px, 6vw, 55px);
        }

        .success-icon {
            width: 82px;
            height: 82px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e7faef;
            color: #16864c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .success-card h1 {
            color: #2b2450;
        }

        .success-total {
            background: #f4f1ff;
            border-radius: 14px;
            padding: 20px;
            margin: 25px 0;
        }

        .success-total strong {
            display: block;
            margin-top: 8px;
            font-size: 27px;
            color: #6c45d9;
        }

        .order-id-list {
            overflow-wrap: anywhere;
            font-weight: 700;
        }

        .success-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-secondary {
            background: #eee9ff;
            color: #603cc5;
        }

        .btn-secondary:hover {
            background: #e1d8ff;
        }

        .footer {
            padding: 25px;
            text-align: center;
            color: #89849b;
            font-size: 13px;
        }

        @media (max-width: 760px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .page {
                margin-top: 28px;
            }

            .card {
                padding: 20px;
            }

            .navbar {
                padding: 17px 5%;
            }

            .nav-links {
                gap: 14px;
            }
        }

        @media (max-width: 430px) {
            .product {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .product-image,
            .image-placeholder {
                width: 76px;
                height: 76px;
            }

            .product-price {
                margin-left: 93px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <a class="logo" href="index.php">NovaStore<span style="color:#28233e">.</span></a>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="cart.php">Cart</a>
        <a href="orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<main class="page">

<?php if ($order_success): ?>

    <section class="card success-card">
        <div class="success-icon">✓</div>

        <div class="eyebrow">Order confirmed</div>
        <h1>Thank you for shopping!</h1>

        <p class="muted">
            Your NovaStore order has been placed successfully.
            You can check its status in your order history.
        </p>

        <div class="success-total">
            <span class="muted">Order total</span>
            <strong>₹<?php echo number_format($order_total, 2); ?></strong>
        </div>

        <p>
            <strong>Order ID(s):</strong>
        </p>

        <p class="order-id-list">
            <?php echo e(implode(", ", $order_ids)); ?>
        </p>

        <div class="success-actions" style="margin-top:28px">
            <a class="btn" href="index.php">Continue Shopping</a>
            <a class="btn btn-secondary" href="orders.php">View My Orders</a>
        </div>
    </section>

<?php else: ?>

    <header class="page-heading">
        <div class="eyebrow">Almost there</div>
        <h1>Checkout</h1>
        <p class="muted">Review your items and confirm your order.</p>
    </header>

    <?php if ($error !== ""): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="checkout-grid">

        <div>
            <section class="card">
                <h2>🛍️ Your Order</h2>

                <?php foreach ($cart_items as $item): ?>
                    <div class="product">

                        <?php if (!empty($item["image"])): ?>
                            <img
                                class="product-image"
                                src="<?php echo e($item["image"]); ?>"
                                alt="<?php echo e($item["name"]); ?>"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                            >
                            <div class="image-placeholder" style="display:none">🛍️</div>
                        <?php else: ?>
                            <div class="image-placeholder">🛍️</div>
                        <?php endif; ?>

                        <div class="product-info">
                            <h3><?php echo e($item["name"]); ?></h3>

                            <p>
                                Unit price: ₹<?php echo number_format((float) $item["price"], 2); ?>
                            </p>

                            <p>Quantity: <?php echo (int) $item["quantity"]; ?></p>
                        </div>

                        <div class="product-price">
                            ₹<?php echo number_format((float) $item["subtotal"], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="card">
                <h2>👤 Customer Information</h2>

                <div class="customer-row">
                    <span>Name</span>
                    <span><?php echo e($_SESSION["user_name"] ?? "Customer"); ?></span>
                </div>

                <div class="customer-row">
                    <span>Email</span>
                    <span><?php echo e($_SESSION["user_email"] ?? ""); ?></span>
                </div>

                <p class="muted" style="font-size:13px;margin-bottom:0">
                    Please check that your account details are correct.
                </p>
            </section>
        </div>

        <aside>
            <section class="card">
                <h2>Order Summary</h2>

                <div class="summary-line">
                    <span>Items (<?php
                        echo array_sum(array_column($cart_items, "quantity"));
                    ?>)</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>

                <div class="summary-line">
                    <span>Delivery</span>
                    <span>To be confirmed</span>
                </div>

                <div class="summary-total">
                    <span>Total</span>
                    <strong>₹<?php echo number_format($total, 2); ?></strong>
                </div>

                <div class="secure-note">
                    🔒 <strong>Demo checkout</strong><br>
                    No online payment will be collected. Clicking Place Order
                    saves your order in the NovaStore database.
                </div>

                <form method="POST" action="checkout.php">
                    <button class="btn btn-full" type="submit">
                        ✓ Place Order
                    </button>
                </form>

                <a class="back-link" href="cart.php">← Back to Cart</a>
            </section>
        </aside>

    </div>

<?php endif; ?>

</main>

<footer class="footer">
    © <?php echo date("Y"); ?> NovaStore. Made with care.
</footer>

</body>
</html>
```