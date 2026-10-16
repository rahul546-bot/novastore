<?php

session_start();

require_once "db.php";

$message = "";

/*
|--------------------------------------------------------------------------
| LOAD PRODUCTS FROM DATABASE
|--------------------------------------------------------------------------
*/

$product_result = $conn->query(
    "SELECT id, name, description, price, image
     FROM products
     ORDER BY id DESC"
);


/*
|--------------------------------------------------------------------------
| CONTACT FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $product = trim($_POST["product"] ?? "");

    if ($name !== "" && $email !== "" && $product !== "") {

        $name = htmlspecialchars($name);
        $email = htmlspecialchars($email);
        $product = htmlspecialchars($product);

        $message =
            "Thank you, $name! Your request for $product has been received.";

    } else {

        $message = "Please fill in all fields.";

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NovaStore - Premium Products</title>

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

        <a href="#home">
            Home
        </a>

        <a href="#products">
            Products
        </a>

        <a href="#features">
            Features
        </a>

        <a href="#contact">
            Contact
        </a>
              <?php if (isset($_SESSION["user_id"])): ?>
    <a href="orders.php">My Orders</a>
<?php endif; ?>
        </nav>


    <div class="nav-actions">


        <?php if (isset($_SESSION["user_id"])): ?>


            <!-- LOGGED IN -->

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


        <?php else: ?>


            <!-- NOT LOGGED IN -->

            <a
                href="login.php"
                class="nav-login"
            >
                Login
            </a>


            <a
                href="signup.php"
                class="nav-signup"
            >
                Sign Up
            </a>


        <?php endif; ?>


        <a
            href="#products"
            class="nav-btn"
        >
            Shop Now
        </a>


    </div>

</header>



<!-- =========================================================
     HERO
========================================================= -->

<section
    class="hero"
    id="home"
>


    <div class="hero-content">


        <p class="small-title">
            ✨ NEW COLLECTION 2026
        </p>


        <h1>

            Discover Products

            <span>
                You'll Love.
            </span>

        </h1>


        <p class="hero-text">

            Premium products designed to make your everyday life
            easier, smarter and better.

        </p>


        <div class="hero-buttons">


            <a
                href="#products"
                class="primary-btn"
            >
                Explore Products
            </a>


            <a
                href="#features"
                class="secondary-btn"
            >
                Learn More
            </a>


        </div>


    </div>



    <div class="hero-product">


        <div class="product-circle">
            🎧
        </div>


        <div class="floating-card">

            <strong>
                Premium Quality
            </strong>

            <p>
                ★★★★★
            </p>

        </div>


    </div>


</section>



<!-- =========================================================
     FEATURES
========================================================= -->

<section
    class="features"
    id="features"
>


    <div class="section-heading">


        <p>
            WHY CHOOSE US
        </p>


        <h2>
            Made for Your Lifestyle
        </h2>


    </div>



    <div class="feature-container">


        <!-- FEATURE 1 -->

        <div class="feature-card">

            <div class="feature-icon">
                ⚡
            </div>

            <h3>
                Fast & Reliable
            </h3>

            <p>
                Products built with quality and performance
                in mind.
            </p>

        </div>



        <!-- FEATURE 2 -->

        <div class="feature-card">

            <div class="feature-icon">
                💎
            </div>

            <h3>
                Premium Quality
            </h3>

            <p>
                Carefully selected products that meet
                high standards.
            </p>

        </div>



        <!-- FEATURE 3 -->

        <div class="feature-card">

            <div class="feature-icon">
                🚚
            </div>

            <h3>
                Fast Delivery
            </h3>

            <p>
                Get your favorite products delivered quickly
                and safely.
            </p>

        </div>



        <!-- FEATURE 4 -->

        <div class="feature-card">

            <div class="feature-icon">
                🔒
            </div>

            <h3>
                Secure Shopping
            </h3>

            <p>
                Your information is handled with care
                and security.
            </p>

        </div>


    </div>


</section>



<!-- =========================================================
     PRODUCTS
========================================================= -->

<section
    class="products"
    id="products"
>


    <div class="section-heading">


        <p>
            OUR PRODUCTS
        </p>


        <h2>
            Popular Products
        </h2>


    </div>



    <div class="product-container">


        <?php if ($product_result && $product_result->num_rows > 0): ?>


            <?php while ($product = $product_result->fetch_assoc()): ?>


                <!-- PRODUCT CARD -->

                <div class="product-card">


                    <!-- PRODUCT IMAGE -->

                    <div class="product-image">


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

    <div class="no-image">
        🛍️
    </div>

<?php endif; ?>


                            <div class="no-image">
                                🛍️
                            </div>



                    </div>



                    <!-- PRODUCT INFORMATION -->

                    <div class="product-info">


                        <span class="category">
                            NovaStore
                        </span>


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $product["name"]
                            );
                            ?>

                        </h3>


                        <p>

                            <?php
                            echo htmlspecialchars(
                                $product["description"]
                            );
                            ?>

                        </p>



                        <div class="product-bottom">


                            <strong>

                                ₹<?php
                                echo number_format(
                                    $product["price"],
                                    2
                                );
                                ?>

                            </strong>



                            <?php if (isset($_SESSION["user_id"])): ?>


                                <!-- LOGGED IN -->

                                <a
                                    href="product.php?id=<?php echo $product["id"]; ?>"
                                    class="buy-btn"
                                >
                                    Buy Now
                                </a>


                            <?php else: ?>


                                <!-- NOT LOGGED IN -->

                                <a
                                    href="signup.php"
                                    class="buy-btn"
                                >
                                    Buy Now
                                </a>


                            <?php endif; ?>


                        </div>


                    </div>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="no-products">

                <h3>
                    No Products Available
                </h3>

                <p>
                    Products will appear here soon.
                </p>

            </div>


        <?php endif; ?>


    </div>


</section>



<!-- =========================================================
     PROMO
========================================================= -->

<section class="promo">


    <div>


        <p>
            LIMITED TIME OFFER
        </p>


        <h2>
            Upgrade Your Everyday.
        </h2>


        <span>
            Get up to 30% off on selected products.
        </span>


    </div>


    <a
        href="#products"
        class="promo-btn"
    >
        Shop Collection
    </a>


</section>



<!-- =========================================================
     CONTACT
========================================================= -->

<section
    class="contact"
    id="contact"
>


    <div class="section-heading">


        <p>
            GET IN TOUCH
        </p>


        <h2>
            Interested in a Product?
        </h2>


    </div>



    <?php if ($message !== ""): ?>


        <div class="message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>


    <?php endif; ?>



    <form
        method="POST"
        class="contact-form"
    >


        <!-- NAME -->

        <div class="input-group">


            <label>
                Your Name
            </label>


            <input
                type="text"
                name="name"
                placeholder="Enter your name"
                required
            >


        </div>



        <!-- EMAIL -->

        <div class="input-group">


            <label>
                Email Address
            </label>


            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >


        </div>



        <!-- PRODUCT -->

        <div class="input-group">


            <label>
                Select Product
            </label>


            <select
                name="product"
                required
            >


                <option value="">
                    Choose a product
                </option>


                <?php

                /*
                 * Run another query because the first
                 * product result has already been used
                 */

                $contact_products = $conn->query(
                    "SELECT name FROM products ORDER BY id DESC"
                );

                ?>


                <?php if ($contact_products): ?>


                    <?php while (
                        $contact_product =
                        $contact_products->fetch_assoc()
                    ): ?>


                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $contact_product["name"]
                            );
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $contact_product["name"]
                            );
                            ?>

                        </option>


                    <?php endwhile; ?>


                <?php endif; ?>


            </select>


        </div>



        <!-- SUBMIT -->

        <button
            type="submit"
            class="submit-btn"
        >
            Send Request
        </button>


    </form>


</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>


    <div class="footer-logo">

        Nova<span>
            Store
        </span>

    </div>


    <p>
        Premium products for modern lifestyles.
    </p>


    <div class="footer-links">


        <a href="#home">
            Home
        </a>


        <a href="#products">
            Products
        </a>


        <a href="#features">
            Features
        </a>


        <a href="#contact">
            Contact
        </a>


    </div>


    <p class="copyright">

        © 2026 NovaStore.
        All rights reserved.

    </p>


</footer>


</body>

</html>