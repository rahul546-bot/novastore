<?php

session_start();

require_once "db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                header("Location: dashboard.php");
                exit;

            } else {

                $message = "Incorrect email or password.";
                $message_type = "error";
            }

        } else {

            $message = "Incorrect email or password.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - NovaStore</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 55px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 7px;
            font-size: 20px;
            line-height: 1;
            color: #b9b2e8;
        }

        .password-toggle:focus-visible {
            outline: 2px solid #8c78ff;
            border-radius: 6px;
        }
    </style>
</head>

<body>

    <div class="auth-page">

        <div class="signup-box">

            <div class="auth-logo">
                Nova<span>Store</span>
            </div>

            <h1>Welcome Back</h1>

            <p class="auth-subtitle">
                Login to your NovaStore account
            </p>

            <?php if ($message !== ""): ?>
                <div class="auth-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="input-group">
                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="input-group">
                    <label for="password">Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >👁️</button>
                    </div>
                </div>

                <button type="submit" class="auth-btn">
                    Login
                </button>

            </form>

            <p class="auth-bottom">
                Don't have an account?
                <a href="signup.php">Create Account</a>
            </p>

            <a href="index.php" class="back-home">
                ← Back to Store
            </a>

        </div>

    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const toggleButton = document.getElementById("togglePassword");

        toggleButton.addEventListener("click", function () {

            const isHidden = passwordInput.type === "password";

            passwordInput.type = isHidden ? "text" : "password";

            toggleButton.textContent = isHidden ? "🙈" : "👁️";

            toggleButton.setAttribute(
                "aria-label",
                isHidden ? "Hide password" : "Show password"
            );

            toggleButton.setAttribute(
                "aria-pressed",
                isHidden ? "true" : "false"
            );
        });
    </script>

</body>
</html>
