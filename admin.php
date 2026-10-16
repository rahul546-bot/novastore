
<?php
session_start();
require_once "db.php";

// Admin access protection
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: index.php");
    exit;
}

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    $order_id = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";

    $allowed_statuses = [
        "Pending",
        "Processing",
        "Shipped",
        "Delivered"
    ];

    if ($order_id && in_array($status, $allowed_statuses, true)) {
        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin.php");
    exit;
}

// Fetch all orders
$sql = "SELECT
            orders.id,
            orders.product_name,
            orders.price,
            orders.quantity,
            orders.status,
            orders.created_at,
            users.name AS user_name,
            users.email AS user_email
        FROM orders
        JOIN users ON orders.user_id = users.id
        ORDER BY orders.id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load orders. Please check the database.");
}

// Summary statistics
$totalOrders = 0;
$pendingOrders = 0;
$processingOrders = 0;
$shippedOrders = 0;
$deliveredOrders = 0;
$totalRevenue = 0;
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $totalOrders++;

    switch ($row["status"]) {
        case "Pending":
            $pendingOrders++;
            break;
        case "Processing":
            $processingOrders++;
            break;
        case "Shipped":
            $shippedOrders++;
            break;
        case "Delivered":
            $deliveredOrders++;
            $totalRevenue += (float)$row["price"] * (int)$row["quantity"];
            break;
    }
}

function statusClass($status) {
    switch ($status) {
        case "Pending":
            return "pending";
        case "Processing":
            return "processing";
        case "Shipped":
            return "shipped";
        case "Delivered":
            return "delivered";
        default:
            return "pending";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | NovaStore</title>
    <link rel="stylesheet" href="style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f6fb;
            color: #202234;
            font-family: Arial, Helvetica, sans-serif;
        }

        .admin-nav {
            min-height: 76px;
            padding: 16px 5%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e9eaf1;
            box-shadow: 0 3px 15px rgba(25, 25, 60, 0.04);
        }

        .admin-logo {
            color: #202234;
            font-size: 27px;
            font-weight: 800;
            text-decoration: none;
            letter-spacing: -1px;
        }

        .admin-logo span {
            color: #6558f5;
        }

        .admin-nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .admin-nav-links a {
            text-decoration: none;
            color: #55576a;
            padding: 10px 14px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .admin-nav-links a:hover {
            background: #f0efff;
            color: #6558f5;
        }

        .admin-nav-links a.logout-link {
            color: #fff;
            background: #6558f5;
        }

        .admin-nav-links a.logout-link:hover {
            background: #5144df;
        }

        .admin-container {
            max-width: 1500px;
            margin: 0 auto;
            padding: 35px 5%;
        }

        .admin-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .admin-heading h1 {
            font-size: 31px;
            margin: 0 0 9px;
            letter-spacing: -0.7px;
        }

        .admin-heading p {
            color: #77798b;
            margin: 0;
            line-height: 1.6;
        }

        .admin-tag {
            display: inline-block;
            background: #eae8ff;
            color: #5144df;
            padding: 10px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #ececf3;
            border-radius: 16px;
            padding: 23px;
            box-shadow: 0 5px 18px rgba(30, 30, 70, 0.035);
            transition: transform 0.2s, box-shadow 0.2s;
            min-width: 0;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(30, 30, 70, 0.07);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .stat-label {
            color: #77798b;
            font-size: 13px;
            font-weight: 600;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 20px;
        }

        .icon-purple { background: #eeebff; }
        .icon-yellow { background: #fff3d6; }
        .icon-blue { background: #e1edff; }
        .icon-green { background: #ddf8e8; }

        .stat-value {
            font-size: 30px;
            font-weight: 800;
            color: #24263a;
            overflow-wrap: anywhere;
        }

        .stat-note {
            margin-top: 8px;
            color: #999aaa;
            font-size: 12px;
        }

        .orders-panel {
            background: #fff;
            border: 1px solid #e9eaf1;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(30, 30, 70, 0.04);
        }

        .orders-panel-heading {
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border-bottom: 1px solid #eeeef4;
        }

        .orders-panel-heading h2 {
            margin: 0 0 7px;
            font-size: 20px;
        }

        .orders-panel-heading p {
            margin: 0;
            font-size: 13px;
            color: #88899a;
        }

        .order-count {
            background: #f0efff;
            color: #5c50e8;
            border-radius: 9px;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 700;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1120px;
        }

        .admin-table th {
            padding: 16px;
            background: #242532;
            color: #fff;
            text-align: left;
            font-size: 11px;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .admin-table td {
            padding: 17px 16px;
            border-bottom: 1px solid #eeeef3;
            font-size: 13px;
            vertical-align: middle;
        }

        .admin-table tbody tr:hover {
            background: #fafaff;
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-number {
            font-weight: 800;
            color: #6558f5;
        }

        .customer-name {
            font-weight: 700;
            color: #303246;
            margin-bottom: 5px;
        }

        .customer-email {
            color: #898a9b;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .product-name {
            font-weight: 600;
            color: #44465a;
        }

        .money {
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff2c9;
            color: #8b6500;
        }

        .status-processing {
            background: #dceaff;
            color: #1d4ed8;
        }

        .status-shipped {
            background: #eee7ff;
            color: #6d28d9;
        }

        .status-delivered {
            background: #d9f9e6;
            color: #167344;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 205px;
        }

        .status-select {
            min-width: 120px;
            padding: 9px 8px;
            border: 1px solid #dedfeb;
            background: #fff;
            color: #34364a;
            border-radius: 8px;
            font-size: 12px;
            outline: none;
        }

        .status-select:focus {
            border-color: #6558f5;
            box-shadow: 0 0 0 3px rgba(101, 88, 245, 0.12);
        }

        .update-btn {
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            background: #6558f5;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .update-btn:hover {
            background: #5144df;
        }

        .empty-orders {
            padding: 55px 20px;
            text-align: center;
            color: #77798b;
        }

        .empty-orders .empty-icon {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .empty-orders h3 {
            margin: 0 0 8px;
            color: #292b3e;
        }

        .admin-footer {
            text-align: center;
            padding: 25px 15px;
            color: #999aaa;
            font-size: 12px;
        }

        @media (max-width: 1000px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .admin-nav {
                padding: 15px 5%;
            }

            .admin-logo {
                font-size: 24px;
            }

            .admin-nav-links {
                width: 100%;
                gap: 5px;
            }

            .admin-nav-links a {
                padding: 9px 10px;
                font-size: 12px;
            }

            .admin-container {
                padding: 25px 4%;
            }

            .admin-heading h1 {
                font-size: 26px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 24px;
            }

            .stat-label {
                font-size: 12px;
            }

            .stat-icon {
                width: 34px;
                height: 34px;
                font-size: 16px;
            }

            .orders-panel-heading {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<nav class="admin-nav">
    <a href="admin.php" class="admin-logo">Nova<span>Store</span></a>

    <div class="admin-nav-links">
        <a href="index.php">🏠 Home</a>
        <a href="dashboard.php">👤 Dashboard</a>
        <a href="orders.php">📦 My Orders</a>
        <a href="logout.php" class="logout-link">Logout ↗</a>
    </div>
</nav>

<main class="admin-container">

    <section class="admin-heading">
        <div>
            <h1>Admin Dashboard 👋</h1>
            <p>Manage orders, monitor statuses and review customer purchases.</p>
        </div>
        <span class="admin-tag">🛡️ Administrator</span>
    </section>

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Orders</span>
                <span class="stat-icon icon-purple">📦</span>
            </div>
            <div class="stat-value"><?php echo $totalOrders; ?></div>
            <div class="stat-note">All customer orders</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Pending Orders</span>
                <span class="stat-icon icon-yellow">⏳</span>
            </div>
            <div class="stat-value"><?php echo $pendingOrders; ?></div>
            <div class="stat-note">Awaiting processing</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Processing</span>
                <span class="stat-icon icon-blue">🔄</span>
            </div>
            <div class="stat-value"><?php echo $processingOrders; ?></div>
            <div class="stat-note">Orders being prepared</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Delivered</span>
                <span class="stat-icon icon-green">✅</span>
            </div>
            <div class="stat-value"><?php echo $deliveredOrders; ?></div>
            <div class="stat-note">Delivered orders</div>
        </div>

    </section>

    <section class="orders-panel">

        <div class="orders-panel-heading">
            <div>
                <h2>Customer Orders</h2>
                <p>View order details and update delivery status.</p>
            </div>
            <span class="order-count">
                <?php echo $totalOrders; ?> orders
            </span>
        </div>

        <div class="table-wrap">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Update Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($orders) > 0): ?>

                    <?php foreach ($orders as $order): ?>

                        <tr>
                            <td>
                                <span class="order-number">
                                    #<?php echo (int)$order["id"]; ?>
                                </span>
                            </td>

                            <td>
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($order["user_name"]); ?>
                                </div>
                                <div class="customer-email">
                                    <?php echo htmlspecialchars($order["user_email"]); ?>
                                </div>
                            </td>

                            <td>
                                <span class="product-name">
                                    <?php echo htmlspecialchars($order["product_name"]); ?>
                                </span>
                            </td>

                            <td class="money">
                                ₹<?php echo number_format((float)$order["price"], 2); ?>
                            </td>

                            <td><?php echo (int)$order["quantity"]; ?></td>

                            <td class="money">
                                ₹<?php
                                    echo number_format(
                                        (float)$order["price"] * (int)$order["quantity"],
                                        2
                                    );
                                ?>
                            </td>

                            <td>
                                <span class="status-badge status-<?php
                                    echo statusClass($order["status"]);
                                ?>">
                                    <?php echo htmlspecialchars($order["status"]); ?>
                                </span>
                            </td>

                            <td>
                                <form method="POST" class="status-form">
                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?php echo (int)$order["id"]; ?>"
                                    >

                                    <select name="status" class="status-select">
                                        <?php
                                        foreach (
                                            ["Pending", "Processing", "Shipped", "Delivered"]
                                            as $option
                                        ):
                                        ?>
                                            <option
                                                value="<?php echo $option; ?>"
                                                <?php
                                                if ($order["status"] === $option) {
                                                    echo "selected";
                                                }
                                                ?>
                                            >
                                                <?php echo $option; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button
                                        type="submit"
                                        name="update_status"
                                        class="update-btn"
                                    >
                                        Update
                                    </button>
                                </form>
                            </td>

                            <td>
                                <?php
                                    echo htmlspecialchars($order["created_at"]);
                                ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9">
                            <div class="empty-orders">
                                <div class="empty-icon">📦</div>
                                <h3>No orders yet</h3>
                                <p>Customer orders will appear here when placed.</p>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </section>

</main>

<footer class="admin-footer">
    © <?php echo date("Y"); ?> NovaStore · Admin Dashboard
</footer>

</body>
</html>

<?php
$conn->close();
?>
