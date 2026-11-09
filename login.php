<?php
session_start();
require "includes/db_connect.php";
require "includes/functions.php";

if (isset($_SESSION["user_id"])) {
    if ($_SESSION["role"] == "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: my_bookings.php");
    }
    exit();
}

$pageTitle = "Log in";
$currentPage = "login";
$solidNav = true;

$errorMessage = "";
$infoMessage = "";
$username = "";

// If the guest opened the login page by themselves (not because a page asked them
// to log in), forget any page that was remembered earlier
if ($_SERVER["REQUEST_METHOD"] == "GET" && (!isset($_GET["message"]) || $_GET["message"] != "login_required")) {
    unset($_SESSION["after_login"]);
}

if (isset($_GET["message"])) {
    if ($_GET["message"] == "login_required" && bookingReturnUrl() != "") {
        $infoMessage = "Log in or create an account to finish your booking. We'll keep your dates.";
    } elseif ($_GET["message"] == "login_required") {
        $infoMessage = "Please log in to continue. New here? Create an account in a minute.";
    } elseif ($_GET["message"] == "registered") {
        $infoMessage = "Your account has been created. You can now log in.";
    } elseif ($_GET["message"] == "logged_out") {
        $infoMessage = "You have been logged out. See you soon.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($username == "" || $password == "") {
        $errorMessage = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                $returnUrl = bookingReturnUrl();
                unset($_SESSION["after_login"]);

                if ($user["role"] == "admin") {
                    header("Location: admin_dashboard.php");
                } elseif ($returnUrl != "") {
                    header("Location: " . $returnUrl);
                } else {
                    header("Location: my_bookings.php?message=welcome");
                }
                exit();
            } else {
                $errorMessage = "Incorrect password.";
            }
        } else {
            $errorMessage = "Username not found.";
        }

        $stmt->close();
    }
}

include "includes/header.php";
?>

    <div class="auth-page">
        <div class="auth-photo" style="background-image: url('images/pool.jpg');"></div>

        <div class="auth-form">
            <div class="inner">
                <p class="eyebrow">Welcome back</p>
                <h1 class="section-title">Log in</h1>

                <?php
                if ($infoMessage != "") {
                    echo '<div class="alert alert-info">' . htmlspecialchars($infoMessage) . '</div>';
                }
                if ($errorMessage != "") {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                }
                ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="btn btn-lux-outline" onclick="togglePassword('password', this)">Show</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-lux w-100">Log in</button>
                </form>

                <p class="mt-4 mb-0">New to Côte d'Or Retreat? <a href="register.php">Create an account</a></p>
            </div>
        </div>
    </div>

<?php
$conn->close();
include "includes/footer.php";
?>
