<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/upload_photo.php";

$pageTitle = "Room photos";
$currentPage = "admin";
$adminTab = "photos";
$solidNav = true;

$successMessage = "";
$errorMessage = "";
$image = "";
$caption = "";
$roomId = 0;

if (isset($_GET["room_id"])) {
    $roomId = (int) $_GET["room_id"];
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    $conn->close();
    header("Location: admin_rooms.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST)) {
    $errorMessage = "The photo is too big to upload. The largest size allowed is " . ini_get("post_max_size") . "B.";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $deleteId = (int) $_POST["delete_id"];

    $stmt = $conn->prepare("DELETE FROM room_photos WHERE id = ? AND room_id = ?");
    $stmt->bind_param("ii", $deleteId, $roomId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $successMessage = "The photo has been removed from the gallery.";
    } else {
        $errorMessage = "Unable to remove the photo.";
    }

    $stmt->close();
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $caption = trim($_POST["caption"]);

    if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] == UPLOAD_ERR_NO_FILE) {
        $errorMessage = "Please choose a photo from your computer.";
    } elseif ($caption == "") {
        $errorMessage = "Please write a short caption for the photo.";
    } else {
        $upload = uploadPhoto($_FILES["photo"], "room" . $roomId);

        if ($upload[1] != "") {
            $errorMessage = $upload[1];
        } else {
            $image = $upload[0];

            $stmt = $conn->prepare("INSERT INTO room_photos (room_id, image, caption) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $roomId, $image, $caption);

            if ($stmt->execute()) {
                $successMessage = "The photo has been added to the " . $room["room_name"] . " gallery.";
                $caption = "";
            } else {
                $errorMessage = "Unable to add the photo.";
            }

            $stmt->close();
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM room_photos WHERE room_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$photos = $stmt->get_result();

include "includes/header.php";
?>

    <section class="admin-wrap">
        <div class="container">
            <?php include "includes/admin_nav.php"; ?>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h2 class="mb-0"><?php echo htmlspecialchars($room["room_name"]); ?> &middot; gallery</h2>
                <a href="admin_rooms.php" class="btn btn-sm btn-lux-outline">Back to rooms</a>
            </div>

            <?php
            if ($successMessage != "") {
                echo '<div class="alert alert-success">' . htmlspecialchars($successMessage) . '</div>';
            }
            if ($errorMessage != "") {
                echo '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
            }
            ?>

            <div class="row g-5">
                <div class="col-12 col-lg-4">
                    <div class="form-panel">
                        <h3 class="mb-3">Add a photo</h3>
                        <p class="text-muted small">Choose a photo from your computer and give it a caption.</p>

                        <form action="admin_photos.php?room_id=<?php echo $roomId; ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="photo" class="form-label">Photo</label>
                                <div class="photo upload-preview mb-2">
                                    <img id="galleryPreview" src="" alt="">
                                </div>
                                <input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp" onchange="previewPhoto(this, 'galleryPreview')" required>
                            </div>
                            <div class="mb-4">
                                <label for="caption" class="form-label">Caption</label>
                                <input type="text" class="form-control" id="caption" name="caption" placeholder="Sunset from the terrace" value="<?php echo htmlspecialchars($caption); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-lux w-100">Add to gallery</button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="card booking-card h-100">
                                <div class="photo admin-photo"><img src="images/<?php echo htmlspecialchars($room["image"]); ?>" alt="Main photo"></div>
                                <div class="card-body p-3">
                                    <p class="room-meta mb-1">Main photo</p>
                                    <p class="small mb-1"><?php echo htmlspecialchars($room["image"]); ?></p>
                                    <a href="admin_rooms.php?edit=<?php echo $roomId; ?>" class="small">Change on the Rooms page</a>
                                </div>
                            </div>
                        </div>

                        <?php
                        if ($photos->num_rows > 0) {
                            while ($row = $photos->fetch_assoc()) {
                        ?>
                            <div class="col-6 col-md-4">
                                <div class="card booking-card h-100">
                                    <div class="photo admin-photo"><img src="images/<?php echo htmlspecialchars($row["image"]); ?>" alt="<?php echo htmlspecialchars($row["caption"]); ?>"></div>
                                    <div class="card-body p-3">
                                        <p class="mb-1"><?php echo htmlspecialchars($row["caption"]); ?></p>
                                        <p class="small text-muted mb-2"><?php echo htmlspecialchars($row["image"]); ?></p>
                                        <form action="admin_photos.php?room_id=<?php echo $roomId; ?>" method="POST">
                                            <input type="hidden" name="delete_id" value="<?php echo $row["id"]; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this photo from the gallery?')">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php
                            }
                        } else {
                            echo '<div class="col-12 col-md-8"><div class="alert alert-info">This room has no extra photos yet.</div></div>';
                        }
                        $stmt->close();
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
