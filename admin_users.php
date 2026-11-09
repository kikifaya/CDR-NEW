<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";

$pageTitle = "Users";
$currentPage = "admin";
$adminTab = "users";
$solidNav = true;

$successMessage = "";
$errorMessage = "";

// Reset a guest's password (the form in the "Reset password" window)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_user_id"])) {
    $resetId = (int) $_POST["reset_user_id"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_new_password"];

    if (strlen($newPassword) < 6) {
        $errorMessage = "The new password must be at least 6 characters long.";
    } elseif ($newPassword != $confirmPassword) {
        $errorMessage = "The two passwords don't match.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $resetId);

        if ($stmt->execute() && $stmt->affected_rows == 1) {
            $nameStmt = $conn->prepare("SELECT full_name, username FROM users WHERE id = ?");
            $nameStmt->bind_param("i", $resetId);
            $nameStmt->execute();
            $resetUser = $nameStmt->get_result()->fetch_assoc();
            $nameStmt->close();

            $successMessage = "The password for " . $resetUser["full_name"] . " has been changed. Give them their username (" . $resetUser["username"] . ") and the new password.";
        } else {
            $errorMessage = "The password could not be changed. Please try again.";
        }

        $stmt->close();
    }
}

$sql = "SELECT users.id, users.full_name, users.username, users.email, users.phone, users.role, users.created_at, COUNT(bookings.id) AS booking_total FROM users LEFT JOIN bookings ON bookings.user_id = users.id GROUP BY users.id ORDER BY users.created_at DESC";
$users = $conn->query($sql);

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container">
            <?php include "includes/admin_nav.php"; ?>

            <?php
            if ($successMessage != "") {
                echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
            }
            if ($errorMessage != "") {
                echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
            }
            ?>

            <p class="text-muted"><?php echo $users->num_rows; ?> account(s) in total.</p>

            <div class="table-responsive">
                <table class="table table-lux mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Bookings</th>
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($users->num_rows > 0) {
                            while ($row = $users->fetch_assoc()) {
                                $roleClass = ($row["role"] == "admin") ? "status-confirmed" : "status-pending";
                                echo "<tr>";
                                echo "<td>" . $row["id"] . "</td>";
                                echo '<td><a href="admin_guest.php?id=' . $row["id"] . '">' . htmlspecialchars($row["full_name"]) . "</a></td>";
                                echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["phone"]) . "</td>";
                                echo '<td><span class="badge badge-status ' . $roleClass . '">' . htmlspecialchars($row["role"]) . "</span></td>";
                                echo "<td>" . $row["booking_total"] . "</td>";
                                echo "<td>" . date("d M Y", strtotime($row["created_at"])) . "</td>";
                                echo '<td class="text-nowrap"><a href="admin_guest.php?id=' . $row["id"] . '" class="btn btn-sm btn-lux me-1">Profile</a><button type="button" class="btn btn-sm btn-lux-outline" onclick="openResetPassword(' . $row["id"] . ', this)" data-name="' . htmlspecialchars($row["full_name"]) . '">Reset password</button></td>';
                                echo "</tr>";
                            }
                        } else {
                            echo '<tr><td colspan="9">No users found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="admin_users.php" method="POST" onsubmit="return validateResetPassword(event)" novalidate>
                    <div class="modal-header">
                        <h2 class="modal-title" id="resetPasswordTitle">Reset password</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>New password for <strong id="resetName"></strong>. Tell the guest their new password, and they can change it later on their account page.</p>
                        <div id="resetErrors"></div>
                        <input type="hidden" name="reset_user_id" id="reset_user_id">

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New password</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="new_password" name="new_password" autocomplete="new-password">
                                <button type="button" class="btn btn-lux-outline" onclick="makePassword()">Make one</button>
                            </div>
                            <div class="form-text">At least 6 characters.</div>
                        </div>
                        <div>
                            <label for="confirm_new_password" class="form-label">Type it again</label>
                            <input type="text" class="form-control" id="confirm_new_password" name="confirm_new_password" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-lux-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-lux">Save new password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
$conn->close();
include "includes/footer.php";
?>
