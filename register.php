<?php
session_start();
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Create an account";
$currentPage = "login";
$solidNav = true;

$errorMessage = "";
$fullName = "";
$email = "";
$phone = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if ($fullName == "" || $email == "" || $username == "" || $password == "") {
        $errorMessage = "Please fill in all the required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } elseif (!preg_match("/^[A-Za-z0-9_]{4,20}$/", $username)) {
        $errorMessage = "The username must be 4 to 20 letters, numbers or underscores.";
    } elseif (strlen($password) < 6) {
        $errorMessage = "The password must be at least 6 characters long.";
    } elseif ($password != $confirmPassword) {
        $errorMessage = "The two passwords don't match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errorMessage = "This username is already taken. Please choose another one.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";

            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $fullName, $username, $email, $phone, $hashedPassword, $role);

            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id;
                $stmt->close();
                $check->close();
                $conn->close();

                session_regenerate_id(true);
                $_SESSION["user_id"] = $newUserId;
                $_SESSION["username"] = $username;
                $_SESSION["full_name"] = $fullName;
                $_SESSION["role"] = $role;

                $returnUrl = bookingReturnUrl();
                unset($_SESSION["after_login"]);

                if ($returnUrl != "") {
                    if (strpos($returnUrl, "?") !== false) {
                        header("Location: " . $returnUrl . "&message=new_account");
                    } else {
                        header("Location: " . $returnUrl . "?message=new_account");
                    }
                } else {
                    header("Location: my_bookings.php?message=new_account");
                }
                exit();
            } else {
                $errorMessage = "Sorry, your account could not be created. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

include "includes/header.php";
?>

    <div class="auth-page">
        <div class="auth-photo" style="background-image: url('images/beach.jpg');"></div>

        <div class="auth-form">
            <div class="inner">
                <p class="eyebrow">Join us</p>
                <h1 class="section-title">Create an account</h1>
                <p class="text-muted">With an account you can book rooms and see all your bookings in one place.</p>

                <?php
                if ($errorMessage != "") {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                }
                ?>
                <div id="formErrors"></div>

                <form action="register.php" method="POST" onsubmit="return validateRegister(event)" novalidate>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="phone" class="form-label">Phone (optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-lux w-100">Create account</button>
                </form>

                <p class="mt-4 mb-0">Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    </div>

<?php
$conn->close();
include "includes/footer.php";
?>
