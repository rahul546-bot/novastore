
<?php
session_start();
require_once "db.php";

// Login protection
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION["user_name"] ?? "Customer";
$user_email = $_SESSION["user_email"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | NovaStore</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .welcome-page {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 24px 50px;
        }

        .welcome-hero {
            position: relative;
            overflow: hidden;
            padding: 45px;
            border-radius: 22px;
            background: linear-gradient(130deg, #24243e, #5144df 65%, #8175ff);
            color: white;
            box-shadow: 0 15px 35px rgba(81, 68, 223, 0.18);
        }

        .welcome-hero::after {
            content: "✦";
            position: absolute;
            right: 45px;
            top: 10px;
            font-size: 150px;
            opacity: 0.12;
            pointer-events: none;
        }

        .welcome-label {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 30px;
            background: rgba(255,255,255,0.14);
            font-size: 12px;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .welcome-hero h1 {
            margin: 20px 0 12px;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.2;
        }

        .welcome-hero p {
            max-width: 600px;
            line-height: 1.8;
            color: #efefff;
            margin-bottom: 25px;
        }

        .welcome-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .welcome-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .welcome-btn:hover {
            transform: translateY(-2px);
        }

        .welcome-btn-primary {
            background: white;
            color: #5144df;
        }

        .welcome-btn-secondary {
            border: 1px solid rgba(255,255,255,0.45);
            color: white;
            background: rgba(255,255,255,0.08);
        }

        .account-card {
            margin-top: 25px;
            padding: 28px;
            border: 1px solid #e9eaf2;
            border-radius: 18px;
            background: white;
            box-shadow: 0 8px 25px rgba(30,30,70,0.04);
        }

        .account-heading {
            margin-bottom: 22px;
        }

        .account-heading h2 {
            margin: 0 0 7px;
            font-size: 21px;
            color: #25263a;
        }

        .account-heading p {
            margin: 0;
            color: #818294;
            font-size: 14px;
        }

        .account-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .account-detail {
            padding: 19px;
            border: 1px solid #eeeef5;
            border-radius: 12px;
            background: #fafaff;
            min-width: 0;
        }

        .account-detail span {
            display: block;
            margin-bottom: 9px;
            color: #85869a;
            font-size: 13px;
        }

        .account-detail strong {
            color: #292a3e;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .dashboard-note {
            margin-top: 25px;
            padding: 22px 25px;
            border: 1px solid #e8e5ff;
            border-radius: 16px;
            background: #f7f6ff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .dashboard-note h3 {
            margin: 0 0 7px;
            color: #34304f;
            font-size: 17px;
        }

        .dashboard-note p {
            margin: 0;
            color: #74728a;
            font-size: 14px;
            line-height: 1.6;
        }

        .dashboard-orders-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 17px;
            border-radius: 10px;
            background: #6558f5;
            color: white;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
            transition: 0.2s ease;
        }

        .dashboard-orders-btn:hover {
            background: #5144df;
            transform: translateY(-2px);
        }

        @media (max-width: 650px) {
            .welcome-page {
                margin: 25px auto;
                padding: 0 15px 35px;
            }

            .welcome-hero {
                padding: 28px 22px;
                border-radius: 17px;
            }

            .welcome-hero::after {
                right: 10px;
                font-size: 100px;
            }

            .welcome-hero p {
                font-size: 14px;
            }

            .welcome-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .account-card {
                padding: 21px 17px;
            }

            .account-details {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .dashboard-note {
                align-items: stretch;
                padding: 20px;
            }

            .dashboard-orders-btn {
                width: 100%;
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

<main class="welcome-page">

    <section class="welcome-hero">
        <span class="welcome-label">NOVASTORE · MY ACCOUNT</span>

        <h1>
            Welcome back,<br>
            <?php echo htmlspecialchars($user_name); ?>! 👋
        </h1>

        <p>
            Great to have you here! Explore our products, manage your account,
            and keep track of your purchases from one convenient place.
        </p>

        <div class="welcome-actions">
            <a href="index.php#products" class="welcome-btn welcome-btn-primary">
                🛍️ Explore Products
            </a>

            <a href="orders.php" class="welcome-btn welcome-btn-secondary">
                📦 View My Orders →
            </a>
        </div>
    </section>

    <section class="account-card">
        <div class="account-heading">
            <h2>👤 Account Information</h2>
            <p>Your registered account details.</p>
        </div>

        <div class="account-details">
            <div class="account-detail">
                <span>Full Name</span>
                <strong>
                    <?php echo htmlspecialchars($user_name); ?>
                </strong>
            </div>

            <div class="account-detail">
                <span>Email Address</span>
                <strong>
                    <?php echo htmlspecialchars($user_email); ?>
                </strong>
            </div>
        </div>
    </section>

    <section class="dashboard-note">
        <div>
            <h3>Want to track an order?</h3>
            <p>Check your order history and see the latest delivery status.</p>
        </div>

        <a href="orders.php" class="dashboard-orders-btn">
            View Order History →
        </a>
    </section>

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
    </div>

    <p class="copyright">
        © 2026 NovaStore. All rights reserved.
    </p>
</footer>

</body>
</html>
