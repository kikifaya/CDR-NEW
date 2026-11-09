<?php
session_start();
require "includes/db_connect.php";

$pageTitle = "Rooms & Apartments";
$currentPage = "rooms";

$allowedTypes = ["Double", "Self-Catering", "Apartment"];
$type = "";
$sort = "ASC";

if (isset($_GET["type"]) && in_array($_GET["type"], $allowedTypes)) {
    $type = $_GET["type"];
}

if (isset($_GET["sort"]) && $_GET["sort"] == "DESC") {
    $sort = "DESC";
}

if ($type != "") {
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE room_type = ? ORDER BY price " . $sort);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $rooms = $stmt->get_result();
} else {
    $sql = "SELECT * FROM rooms ORDER BY price " . $sort;
    $rooms = $conn->query($sql);
}

$roomPhotos = [];
$photoResult = $conn->query("SELECT * FROM room_photos ORDER BY room_id, id");

if ($photoResult && $photoResult->num_rows > 0) {
    while ($photo = $photoResult->fetch_assoc()) {
        $roomPhotos[$photo["room_id"]][] = $photo;
    }
}

// Average guest rating for each room (approved reviews only)
$roomRatings = [];
$ratingResult = $conn->query("SELECT room_id, COUNT(*) AS total, AVG(rating) AS average FROM reviews WHERE status = 'Approved' GROUP BY room_id");

if ($ratingResult && $ratingResult->num_rows > 0) {
    while ($ratingRow = $ratingResult->fetch_assoc()) {
        $roomRatings[$ratingRow["room_id"]] = $ratingRow;
    }
}

$modalAction = "book";

include "includes/header.php";
?>

    <section class="page-hero" style="background-image: url('images/room-double.jpg');">
        <div class="container">
            <p class="eyebrow">Rooms &amp; apartments</p>
            <h1>Your space<br>by <em>the sea</em></h1>
        </div>
    </section>

    <div class="tab-strip">
        <div class="tab-strip-links">
            <a href="rooms.php?sort=<?php echo $sort; ?>" class="<?php if ($type == "") { echo "active"; } ?>">All rooms</a>
            <?php
            foreach ($allowedTypes as $item) {
                $activeClass = ($type == $item) ? "active" : "";
                echo '<a href="rooms.php?type=' . urlencode($item) . '&sort=' . $sort . '" class="' . $activeClass . '">' . htmlspecialchars($item) . '</a>';
            }
            ?>
        </div>
        <form class="tab-strip-sort" action="rooms.php" method="GET">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <label for="sort">Price</label>
            <select id="sort" name="sort" onchange="this.form.submit()">
                <option value="ASC" <?php if ($sort == "ASC") { echo "selected"; } ?>>Lowest first</option>
                <option value="DESC" <?php if ($sort == "DESC") { echo "selected"; } ?>>Highest first</option>
            </select>
        </form>
    </div>

    <section class="rooms-list">
        <div class="container">
            <?php
            if ($rooms && $rooms->num_rows > 0) {
                $roomNumber = 0;
                while ($row = $rooms->fetch_assoc()) {
                    $roomId = $row["id"];
                    $roomNumber++;
            ?>
                <div class="row g-4 g-lg-5 align-items-center room-row" id="room-<?php echo $roomId; ?>">
                    <div class="col-12 col-lg-7 <?php if ($roomNumber % 2 == 0) { echo "order-lg-2"; } ?>">
                        <div class="card room-card reveal" data-cursor="Explore" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $roomId; ?>">
                            <div class="photo room-row-photo">
                                <img src="images/<?php echo htmlspecialchars($row["image"]); ?>" alt="<?php echo htmlspecialchars($row["room_name"]); ?>">
                                <?php if (isset($roomPhotos[$roomId])) { ?><span class="photo-count"><?php echo count($roomPhotos[$roomId]) + 1; ?> photos</span><?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="room-row-text reveal">
                            <p class="room-number"><?php echo str_pad($roomNumber, 2, "0", STR_PAD_LEFT); ?></p>
                            <p class="room-meta"><?php echo htmlspecialchars($row["room_type"]); ?> &middot; <?php echo htmlspecialchars($row["size_m2"]); ?> m² &middot; Up to <?php echo htmlspecialchars($row["max_guests"]); ?> guests</p>
                            <h2 class="card-title"><?php echo htmlspecialchars($row["room_name"]); ?></h2>
                            <?php if (isset($roomRatings[$roomId])) { ?>
                                <p class="room-rating">&#9733; <?php echo number_format($roomRatings[$roomId]["average"], 1); ?> <span>&middot; <?php echo $roomRatings[$roomId]["total"]; ?> <?php echo $roomRatings[$roomId]["total"] == 1 ? "review" : "reviews"; ?></span></p>
                            <?php } ?>
                            <p class="tagline"><?php echo htmlspecialchars($row["tagline"]); ?></p>
                            <p class="room-price">From $<?php echo number_format($row["price"], 0); ?> <small>/ night</small></p>
                            <div class="d-flex flex-wrap gap-3">
                                <button type="button" class="btn btn-lux-outline" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $roomId; ?>">Photos &amp; details</button>
                                <a href="booking.php?room_id=<?php echo $roomId; ?>" class="btn btn-lux">Book</a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $room = $row;
                include "includes/room_modal.php";
                ?>
            <?php
                }
            } else {
                echo '<div class="alert alert-info">No rooms found for this filter.</div>';
            }
            ?>
        </div>
    </section>

    <section class="facts-band">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-12 col-md-4">
                    <p class="room-meta mb-2">Breakfast</p>
                    <p class="fact-value">$<?php echo mealPrice($settings, "Breakfast"); ?> <small>per person / night</small></p>
                </div>
                <div class="col-12 col-md-4">
                    <p class="room-meta mb-2">Half board</p>
                    <p class="fact-value">$<?php echo mealPrice($settings, "Half board"); ?> <small>per person / night</small></p>
                </div>
                <div class="col-12 col-md-4">
                    <p class="room-meta mb-2">Check-in &middot; Check-out</p>
                    <p class="fact-value"><?php echo htmlspecialchars($settings["checkin_time"]); ?> &middot; <?php echo htmlspecialchars($settings["checkout_time"]); ?></p>
                </div>
            </div>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
