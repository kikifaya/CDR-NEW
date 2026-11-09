<?php
session_start();
require "includes/auth_check.php";
require "includes/db_connect.php";

$pageTitle = "My account";
$currentPage = "my_bookings";
$solidNav = true;

$detailsSuccess = "";
$detailsError = "";
$passwordSuccess = "";
$passwordError = "";
$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form_type"]) && $_POST["form_type"] == "details") {
    $fullName = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);

    if ($fullName == "" || $email == "") {
        $detailsError = "Please enter your full name and email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $detailsError = "Please enter a valid email address.";
    } elseif ($phone != "" && !preg_match("/^[0-9+ ]{7,20}$/", $phone)) {
        $detailsError = "Please enter a valid phone number.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $fullName, $email, $phone, $userId);

        if ($stmt->execute()) {
            $_SESSION["full_name"] = $fullName;
            $detailsSuccess = "Your details have been updated.";
        } else {
            $detailsError = "Sorry, your details could not be updated.";
        }

        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form_type"]) && $_POST["form_type"] == "password") {
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($currentPassword, $user["password"])) {
        $passwordError = "Your current password is not correct.";
    } elseif (strlen($newPassword) < 6) {
        $passwordError = "The new password must be at least 6 characters long.";
    } elseif ($newPassword != $confirmPassword) {
        $passwordError = "The two new passwords don't match.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);

        if ($stmt->execute()) {
            $passwordSuccess = "Your password has been changed.";
        } else {
            $passwordError = "Sorry, your password could not be changed.";
        }

        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT full_name, username, email, phone, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

include "includes/header.php";
?>

    <section class="account-wrap">
        <div class="container">
            <p class="eyebrow">Your account</p>
            <h1 class="section-title">My <em>account</em></h1>
            <p class="text-muted mb-4">
                Signed in as <strong><?php echo htmlspecialchars($account["username"]); ?></strong>
                &middot; member since <?php echo date("F Y", strtotime($account["created_at"])); ?>
                &middot; <a href="<?php echo $account["role"] == "admin" ? "admin_dashboard.php" : "my_bookings.php"; ?>"><?php echo $account["role"] == "admin" ? "Admin dashboard" : "My bookings"; ?></a>
            </p>

            <div class="row g-5">
                <div class="col-12 col-lg-6">
                    <div class="form-panel h-100">
                        <h3 class="mb-4">Your details</h3>

                        <?php
                        if ($detailsSuccess != "") {
                            echo '<div class="alert alert-success">' . htmlspecialchars($detailsSuccess) . '</div>';
                        }
                        if ($detailsError != "") {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($detailsError) . '</div>';
                        }
                        ?>

                        <form action="account.php" method="POST">
                            <input type="hidden" name="form_type" value="details">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($account["full_name"]); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($account["email"]); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($account["phone"]); ?>">
                            </div>
                            <button type="submit" class="btn btn-lux">Save details</button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="form-panel h-100">
                        <h3 class="mb-4">Change password</h3>

                        <?php
                        if ($passwordSuccess != "") {
                            echo '<div class="alert alert-success">' . htmlspecialchars($passwordSuccess) . '</div>';
                        }
                        if ($passwordError != "") {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($passwordError) . '</div>';
                        }
                        ?>
                        <div id="formErrors"></div>

                        <form action="account.php" method="POST" onsubmit="return validatePasswordChange(event)" novalidate>
                            <input type="hidden" name="form_type" value="password">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm new password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-lux">Change password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
