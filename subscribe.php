<?php
session_start();
require "includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$pageTitle = "Newsletter";

$successMessage = "";
$errorMessage = "";
$email = trim($_POST["email"]);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMessage = "Please enter a valid email address.";
} else {
    $stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errorMessage = "This email is already subscribed to our newsletter.";
    } else {
        $insert = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
        $insert->bind_param("s", $email);

        if ($insert->execute()) {
            $successMessage = "Thank you! " . $email . " is now subscribed to island news and seasonal offers.";
        } else {
            $errorMessage = "Sorry, we could not subscribe you. Please try again.";
        }

        $insert->close();
    }

    $stmt->close();
}

include "includes/header.php";
?>

    <section class="page-hero" style="background-image: url('images/hero.jpg');">
        <div class="container">
            <p class="eyebrow">Newsletter</p>
            <h1>Stay in <em>touch</em></h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-7">
                    <div class="form-panel">
                        <?php
                        if ($successMessage != "") {
                            echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
                        } else {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                        }
                        ?>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a href="index.php" class="btn btn-lux">Back to home</a>
                            <a href="rooms.php" class="btn btn-lux-outline">See the rooms</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
