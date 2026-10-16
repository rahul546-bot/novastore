
<?php
session_start();
require_once "db.php";

// Login required
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Fetch only this user's orders
$stmt = $conn->prepare(
    "SELECT id, product_name, price, quantity, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | NovaStore</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .orders-page {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .orders-page h1 {
            margin-bottom: 10px;
        }

        .orders-table-wrap {
            overflow-x: auto;
            margin-top: 25px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            color: #222;
        }

        .orders-table th,
        .orders-table td {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .orders-table th {
            background: #222;
            color: white;
        }

        .order-status {
            font-weight: bold;
        }

        .orders-message {
            margin-top: 25px;
            padding: 20px;
            background: #f3f4f6;
            border-radius: 10px;
        }

        .orders-back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
        }
        
.orders-page {
    max-width: 1200px;
    margin: 45px auto;
    padding: 25px;
}

.orders-page h1 {
    margin-bottom: 8px;
    font-size: 32px;
    color: #171717;
}

.orders-page > p {
    color: #666;
    margin-bottom: 25px;
}

.orders-table-wrap {
    overflow-x: auto;
    margin-top: 25px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    color: #222;
}

.orders-table th,
.orders-table td {
    padding: 16px;
    border-bottom: 1px solid #eee;
    text-align: left;
    white-space: nowrap;
}

.orders-table th {
    background: #20212a;
    color: #fff;
    font-size: 12px;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}

.orders-table tbody tr:hover {
    background: #f8f8ff;
}

.orders-table tbody tr:last-child td {
    border-bottom: none;
}

.order-status {
    display: inline-block;
    padding: 8px 13px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-shipped {
    background: #ede9fe;
    color: #6d28d9;
}

.status-delivered {
    background: #dcfce7;
    color: #166534;
}

.orders-message {
    margin-top: 25px;
    padding: 35px 20px;
    background: #fff;
    border: 1px solid #eee;
    border-radius: 14px;
    text-align: center;
}

.continue-shopping {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 28px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #625bff, #8b5cf6);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 5px 15px rgba(98, 91, 255, 0.22);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.continue-shopping:hover {
    transform: translateY(-3px);
    box-shadow: 0 9px 22px rgba(98, 91, 255, 0.32);
}

@media (max-width: 768px) {
    .orders-page {
        margin: 20px auto;
        padding: 15px;
    }

    .orders-page h1 {
        font-size: 26px;
    }

    .orders-table th,
    .orders-table td {
        padding: 12px;
    }
}

    </style>
</head>

<body>

<header class="navbar">
    <div class="logo">Nova<span>Store</span></div>

    <nav>
        <a href="index.php">Home</a>
        <a href="index.php#products">Products</a>
        <a href="index.php#features">Features</a>
        <a href="index.php#contact">Contact</a>
        <a href="orders.php">My Orders</a>
    </nav>

    <div class="nav-actions">
        <a href="dashboard.php" class="nav-login">
            👤 <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
        </a>
        <a href="logout.php" class="nav-signup">Logout</a>
    </div>
</header>

<main class="orders-page">
    <h1>My Orders 📦</h1>
    <p>Track your purchases and check their current status.</p>

    <?php if ($result->num_rows > 0): ?>
        <div class="orders-table-wrap">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo (int)$order["id"]; ?></td>

                            <td>
                                <?php echo htmlspecialchars($order["product_name"]); ?>
                            </td>

                            <td>
                                <?php echo (int)$order["quantity"]; ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$order["price"], 2); ?>
                            </td>

                            <td>
                                ₹<?php
                                    echo number_format(
                                        (float)$order["price"] * (int)$order["quantity"],
                                        2
                                    );
                                ?>
                            </td>

                           
<td>
    <?php
        $status = $order["status"];

        $statusClass = match ($status) {
            "Pending" => "status-pending",
            "Processing" => "status-processing",
            "Shipped" => "status-shipped",
            "Delivered" => "status-delivered",
            default => "status-pending"
        };
    ?>
    <span class="order-status <?php echo $statusClass; ?>">
        <?php echo htmlspecialchars($status); ?>
    </span>
 </td>


                            <td>
                                <?php echo htmlspecialchars($order["created_at"]); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="orders-message">
            <h3>No orders yet!</h3>
            <p>Your placed orders will appear here.</p>
            <a href="index.php#products">Start Shopping</a>
        </div>
    <?php endif; ?>

   
<a href="index.php#products" class="continue-shopping">
    <span>←</span>
    <span>Continue Shopping</span>
    <span>🛍️</span>
</a>

</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
s