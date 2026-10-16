<?php

session_start();

require_once "db.php";

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$product_id = intval($_GET["id"] ?? 0);

if ($product_id <= 0) {
    header("Location: index.php#products");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT FROM DATABASE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id, name, description, price, image
     FROM products
     WHERE id = ?"
);

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    header("Location: index.php#products");
    exit;
}

$product = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($product["name"]); ?> - NovaStore
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="navbar">

    <div class="logo">
        Nova<span>Store</span>
    </div>


    <nav>

        <a href="index.php#home">
            Home
        </a>

        <a href="index.php#products">
            Products
        </a>

        <a href="index.php#features">
            Features
        </a>

        <a href="index.php#contact">
            Contact
        </a>

    </nav>


    <div class="nav-actions">

        <a
            href="dashboard.php"
            class="nav-login"
        >
            👤
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
        </a>


        <a
            href="logout.php"
            class="nav-signup"
        >
            Logout
        </a>


        <a
            href="index.php#products"
            class="nav-btn"
        >
            Shop Now
        </a>

    </div>

</header>



<!-- =========================================================
     PRODUCT DETAILS
========================================================= -->

<section class="product-details-page">

    <div class="product-details-container">


        <!-- PRODUCT IMAGE -->

        <div class="product-details-image">

            <?php

            $image_file = "images/" . ($product["image"] ?? "");

            if (
                !empty($product["image"]) &&
                file_exists($image_file)
            ):

            ?>

                <img
                    src="<?php echo htmlspecialchars($image_file); ?>"
                    alt="<?php echo htmlspecialchars($product["name"]); ?>"
                >

            <?php else: ?>

                <div class="product-placeholder">
                    🛍️
                </div>

            <?php endif; ?>

        </div>



        <!-- PRODUCT INFORMATION -->

        <div class="product-details-info">


            <span class="category">
                NovaStore Premium
            </span>


            <h1>
                <?php
                echo htmlspecialchars($product["name"]);
                ?>
            </h1>


            <p class="product-details-description">

                <?php
                echo htmlspecialchars($product["description"]);
                ?>

            </p>


            <div class="product-details-price">

                ₹<?php
                echo number_format(
                    $product["price"],
                    2
                );
                ?>

            </div>


            <div class="product-features-list">

                <div>
                    ✓ Premium Quality
                </div>

                <div>
                    ✓ Fast Delivery
                </div>

                <div>
                    ✓ Secure Shopping
                </div>

                <div>
                    ✓ Customer Support
                </div>

            </div>


            <!-- ADD CART -->

         <a
    href="cart.php?add=<?php echo $product['id']; ?>"
    class="product-buy-btn"
>
    🛒 Add to Cart
</a>

            <a
                href="index.php#products"
                class="back-products"
            >
                ← Back to Products
            </a>


        </div>


    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="footer-logo">
        Nova<span>Store</span>
    </div>

    <p>
        Premium products for modern lifestyles.
    </p>

    <div class="footer-links">

        <a href="index.php#home">
            Home
        </a>

        <a href="index.php#products">
            Products
        </a>

        <a href="index.php#features">
            Features
        </a>

        <a href="index.php#contact">
            Contact
        </a>

    </div>

    <p class="copyright">
        © 2026 NovaStore. All rights reserved.
    </p>

</footer>


</body>

</html>