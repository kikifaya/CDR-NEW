<?php
session_start();
require "includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: contact.html");
    exit();
}

$pageTitle = "Contact";
$currentPage = "contact";

$successMessage = "";
$errorMessage = "";

$name = trim($_POST["name"]);
$email = trim($_POST["email"]);
$subject = trim($_POST["subject"]);
$message = trim($_POST["message"]);

if ($name == "" || $email == "" || $subject == "" || $message == "") {
    $errorMessage = "Please fill in all the fields.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMessage = "Please enter a valid email address.";
} else {
    $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    if ($stmt->execute()) {
        $successMessage = "Thank you, " . $name . ". Your message has been sent and we will reply soon.";
    } else {
        $errorMessage = "Sorry, your message could not be sent. Please try again.";
    }

    $stmt->close();
}

include "includes/header.php";
?>

    <section class="page-hero" style="background-image: url('images/beach.jpg');">
        <div class="container">
            <p class="eyebrow">Contact</p>
            <h1>We'd love to <em>hear from you</em></h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-7">
                    <div class="form-panel">
                        <p class="eyebrow">Your message</p>

                        <?php
                        if ($successMessage != "") {
                            echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
                        ?>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <a href="index.php" class="btn btn-lux">Back to home</a>
                                <a href="contact.html" class="btn btn-lux-outline">Send another message</a>
                            </div>
                        <?php
                        } else {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
                        ?>
                            <button type="button" class="btn btn-lux mt-4" onclick="history.back()">Go back to the form</button>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
