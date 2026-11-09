<?php
session_start();
require "includes/admin_check.php";
require "includes/db_connect.php";
require "includes/upload_photo.php";

$pageTitle = "Rooms";
$currentPage = "admin";
$adminTab = "rooms";
$solidNav = true;

$successMessage = "";
$errorMessage = "";
$roomTypes = ["Double", "Self-Catering", "Apartment"];

$editId = 0;
$roomName = "";
$roomType = "";
$price = "";
$size = "";
$maxGuests = "";
$totalRooms = "";
$image = "";
$tagline = "";
$description = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST)) {
    $errorMessage = "The photo is too big to upload. The largest size allowed is " . ini_get("post_max_size") . "B.";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $deleteId = (int) $_POST["delete_id"];

    $check = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE room_id = ?");
    $check->bind_param("i", $deleteId);
    $check->execute();
    $bookingTotal = $check->get_result()->fetch_assoc()["total"];
    $check->close();

    if ($bookingTotal > 0) {
        $errorMessage = "This room has " . $bookingTotal . " booking(s), so it can't be deleted. Delete or move those bookings first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $deleteId);

        if ($stmt->execute()) {
            $successMessage = "The room has been deleted.";
        } else {
            $errorMessage = "Unable to delete the room.";
        }

        $stmt->close();
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $editId = (int) $_POST["room_id"];
    $roomName = trim($_POST["room_name"]);
    $roomType = $_POST["room_type"];
    $price = $_POST["price"];
    $size = $_POST["size_m2"];
    $maxGuests = $_POST["max_guests"];
    $totalRooms = $_POST["total_rooms"];
    $image = trim($_POST["image"]);
    $tagline = trim($_POST["tagline"]);
    $description = trim($_POST["description"]);
    $hasUpload = isset($_FILES["photo_upload"]) && $_FILES["photo_upload"]["error"] != UPLOAD_ERR_NO_FILE;

    if ($roomName == "" || $price == "" || $size == "" || $maxGuests == "" || $totalRooms == "") {
        $errorMessage = "Please fill in all the required fields.";
    } elseif (!in_array($roomType, $roomTypes)) {
        $errorMessage = "Please choose a room type.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $errorMessage = "The price must be a number greater than 0.";
    } elseif ((int) $size < 1 || (int) $maxGuests < 1 || (int) $totalRooms < 1) {
        $errorMessage = "Size, guests and number of rooms must be at least 1.";
    } else {
        if ($hasUpload) {
            $upload = uploadPhoto($_FILES["photo_upload"], "room");

            if ($upload[1] != "") {
                $errorMessage = $upload[1];
            } else {
                $image = $upload[0];
            }
        }
    }

    if ($errorMessage == "") {
        $price = (float) $price;
        $size = (int) $size;
        $maxGuests = (int) $maxGuests;
        $totalRooms = (int) $totalRooms;

        if ($editId > 0) {
            $stmt = $conn->prepare("UPDATE rooms SET room_name = ?, room_type = ?, price = ?, size_m2 = ?, max_guests = ?, total_rooms = ?, image = ?, tagline = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssdiiisssi", $roomName, $roomType, $price, $size, $maxGuests, $totalRooms, $image, $tagline, $description, $editId);

            if ($stmt->execute()) {
                $successMessage = "The room \"" . $roomName . "\" has been updated.";
            } else {
                $errorMessage = "Unable to update the room.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO rooms (room_name, room_type, price, size_m2, max_guests, total_rooms, image, tagline, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiiisss", $roomName, $roomType, $price, $size, $maxGuests, $totalRooms, $image, $tagline, $description);

            if ($stmt->execute()) {
                $successMessage = "The room \"" . $roomName . "\" has been added.";
            } else {
                $errorMessage = "Unable to add the room.";
            }
        }

        $stmt->close();

        if ($successMessage != "") {
            $editId = 0;
            $roomName = "";
            $roomType = "";
            $price = "";
            $size = "";
            $maxGuests = "";
            $totalRooms = "";
            $image = "";
            $tagline = "";
            $description = "";
        }
    }
} elseif (isset($_GET["edit"])) {
    $editId = (int) $_GET["edit"];

    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($room) {
        $roomName = $room["room_name"];
        $roomType = $room["room_type"];
        $price = $room["price"];
        $size = $room["size_m2"];
        $maxGuests = $room["max_guests"];
        $totalRooms = $room["total_rooms"];
        $image = $room["image"];
        $tagline = $room["tagline"];
        $description = $room["description"];
    } else {
        $editId = 0;
        $errorMessage = "Room not found.";
    }
}

$rooms = $conn->query("SELECT * FROM rooms ORDER BY price ASC");

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

            <div class="row g-5">
                <div class="col-12 col-lg-5">
                    <div class="form-panel">
                        <h3 class="mb-4"><?php echo $editId > 0 ? "Edit room" : "Add a room"; ?></h3>

                        <form action="admin_rooms.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="room_id" value="<?php echo $editId; ?>">

                            <div class="mb-3">
                                <label for="room_name" class="form-label">Room name</label>
                                <input type="text" class="form-control" id="room_name" name="room_name" value="<?php echo htmlspecialchars($roomName); ?>" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="room_type" class="form-label">Type</label>
                                    <select class="form-select" id="room_type" name="room_type" required>
                                        <option value="">Choose</option>
                                        <?php
                                        foreach ($roomTypes as $item) {
                                            $selected = ($roomType == $item) ? "selected" : "";
                                            echo '<option value="' . $item . '" ' . $selected . '>' . $item . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="price" class="form-label">Price / night ($)</label>
                                    <input type="number" step="0.01" min="1" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-4">
                                    <label for="size_m2" class="form-label">Size m²</label>
                                    <input type="number" min="1" class="form-control" id="size_m2" name="size_m2" value="<?php echo htmlspecialchars($size); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label for="max_guests" class="form-label">Guests</label>
                                    <input type="number" min="1" class="form-control" id="max_guests" name="max_guests" value="<?php echo htmlspecialchars($maxGuests); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label for="total_rooms" class="form-label">Rooms</label>
                                    <input type="number" min="1" class="form-control" id="total_rooms" name="total_rooms" value="<?php echo htmlspecialchars($totalRooms); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="photo_upload" class="form-label">Main photo</label>
                                <div class="photo upload-preview mb-2">
                                    <img id="mainPreview" src="<?php echo $image != "" ? "images/" . htmlspecialchars($image) : ""; ?>" alt="">
                                </div>
                                <input type="file" class="form-control" id="photo_upload" name="photo_upload" accept=".jpg,.jpeg,.png,.webp" onchange="previewPhoto(this, 'mainPreview')">
                                <div class="form-text">Choose a photo from your computer. Leave it empty to keep the current photo.</div>
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($image); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="tagline" class="form-label">Tagline</label>
                                <input type="text" class="form-control" id="tagline" name="tagline" value="<?php echo htmlspecialchars($tagline); ?>">
                            </div>
                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-lux w-100"><?php echo $editId > 0 ? "Save changes" : "Add room"; ?></button>
                            <?php if ($editId > 0) { ?>
                                <a href="admin_rooms.php" class="btn btn-lux-outline w-100 mt-2">Cancel editing</a>
                            <?php } ?>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="table-responsive">
                        <table class="table table-lux mb-0">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Guests</th>
                                    <th>Rooms</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($rooms && $rooms->num_rows > 0) {
                                    while ($row = $rooms->fetch_assoc()) {
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row["room_name"]); ?><br><small class="text-muted"><?php echo htmlspecialchars($row["size_m2"]); ?> m²</small></td>
                                        <td><?php echo htmlspecialchars($row["room_type"]); ?></td>
                                        <td>$<?php echo number_format($row["price"], 2); ?></td>
                                        <td><?php echo $row["max_guests"]; ?></td>
                                        <td><?php echo $row["total_rooms"]; ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="admin_rooms.php?edit=<?php echo $row["id"]; ?>" class="btn btn-sm btn-lux-outline">Edit</a>
                                                <a href="admin_photos.php?room_id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-lux-outline">Photos</a>
                                                <form action="admin_rooms.php" method="POST">
                                                    <input type="hidden" name="delete_id" value="<?php echo $row["id"]; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this room?')">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6">No rooms found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
