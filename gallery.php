<?php
session_start();
require "includes/db_connect.php";

$pageTitle = "Gallery";
$currentPage = "gallery";

// Photo categories: key => name shown on the filter button
$categories = [
    "rooms" => "Rooms",
    "pool" => "Pool & spa",
    "dining" => "Dining",
    "island" => "Beach & island"
];

// Hotel photos that are not linked to a room
$photos = [
    ["file" => "pool.jpg", "caption" => "The hotel pool", "category" => "pool"],
    ["file" => "beach.jpg", "caption" => "Côte d'Or Beach at Anse Volbert", "category" => "island"],
    ["file" => "restaurant.jpg", "caption" => "Dinner table set by the sea", "category" => "dining"],
    ["file" => "spa.jpg", "caption" => "Massage room in the spa", "category" => "pool"],
    ["file" => "intro-1.jpg", "caption" => "Relaxing on the terrace among the palm trees", "category" => "island"],
    ["file" => "strip-2.jpg", "caption" => "Tropical breakfast with fresh fruit", "category" => "dining"],
    ["file" => "strip-3.jpg", "caption" => "Turquoise bay with white sand", "category" => "island"],
    ["file" => "story-2.jpg", "caption" => "Spa details with flowers and stones", "category" => "pool"],
    ["file" => "moment-1.jpg", "caption" => "Morning swim in clear water", "category" => "island"],
    ["file" => "story-1.jpg", "caption" => "Cocktail at the bar", "category" => "dining"],
    ["file" => "strip-4.jpg", "caption" => "A swimming pool lined with palm trees", "category" => "pool"],
    ["file" => "anse-lazio.jpg", "caption" => "Anse Lazio beach", "category" => "island"],
    ["file" => "strip-1.jpg", "caption" => "Granite rocks on a Praslin beach", "category" => "island"],
    ["file" => "vallee-de-mai.jpg", "caption" => "Palm forest in the Vallée de Mai", "category" => "island"],
    ["file" => "moment-3.jpg", "caption" => "Sunset over the Indian Ocean", "category" => "island"],
    ["file" => "intro-2.jpg", "caption" => "Waves breaking on the shore", "category" => "island"]
];

// Room photos come from the database, so new photos added by the admin appear here too
$roomResult = $conn->query("SELECT id, room_name, image FROM rooms ORDER BY price ASC");

if ($roomResult && $roomResult->num_rows > 0) {
    while ($room = $roomResult->fetch_assoc()) {
        if ($room["image"] != "") {
            $photos[] = ["file" => $room["image"], "caption" => $room["room_name"], "category" => "rooms"];
        }

        $photoStmt = $conn->prepare("SELECT image, caption FROM room_photos WHERE room_id = ? ORDER BY id");
        $photoStmt->bind_param("i", $room["id"]);
        $photoStmt->execute();
        $photoResult = $photoStmt->get_result();

        while ($photo = $photoResult->fetch_assoc()) {
            $caption = $room["room_name"];
            if ($photo["caption"] != "") {
                $caption = $room["room_name"] . " – " . $photo["caption"];
            }
            $photos[] = ["file" => $photo["image"], "caption" => $caption, "category" => "rooms"];
        }

        $photoStmt->close();
    }
}

include "includes/header.php";
?>

    <section class="page-hero" style="background-image: url('images/strip-3.jpg');">
        <div class="container">
            <p class="eyebrow">Gallery</p>
            <h1>Moments at<br><em>Côte d'Or</em></h1>
        </div>
    </section>

    <div class="tab-strip gallery-filters">
        <div class="tab-strip-links">
            <button type="button" class="btn active" onclick="filterGallery('all', this)">All</button>
            <?php foreach ($categories as $key => $name) { ?>
                <button type="button" class="btn" onclick="filterGallery('<?php echo $key; ?>', this)"><?php echo htmlspecialchars($name); ?></button>
            <?php } ?>
        </div>
        <p class="tab-strip-note" id="galleryCount"><?php echo count($photos); ?> photos</p>
    </div>

    <section class="gallery-section">
        <div class="container">
            <p class="statement text-center mx-auto mb-5 reveal-words">The rooms, the pool and <em>the island</em>. Tap any photo to see it full screen.</p>

            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($photos as $photo) { ?>
                    <figure class="gallery-item" data-cursor="Explore" data-category="<?php echo $photo["category"]; ?>" onclick="openLightbox(this)">
                        <div class="photo">
                            <img src="images/<?php echo htmlspecialchars($photo["file"]); ?>" alt="<?php echo htmlspecialchars($photo["caption"]); ?>" loading="lazy">
                        </div>
                        <figcaption>
                            <span class="gallery-cat"><?php echo htmlspecialchars($categories[$photo["category"]]); ?></span>
                            <?php echo htmlspecialchars($photo["caption"]); ?>
                        </figcaption>
                    </figure>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="cta-photo" data-speed="0.08" style="background-image: url('images/moment-1.jpg');"></div>
        <div class="container text-center">
            <p class="eyebrow">Your stay</p>
            <h2 class="display-title reveal-words">Picture<br>yourself <em>here</em></h2>
            <a href="booking.php" class="btn btn-lux mt-4">Book your stay</a>
        </div>
    </section>

    <div class="modal fade lightbox" id="lightbox" tabindex="-1" aria-label="Photo viewer" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <button type="button" class="lightbox-close" data-bs-dismiss="modal">Close <span>&times;</span></button>

                <div class="lightbox-stage">
                    <button type="button" class="lightbox-arrow lightbox-prev" onclick="prevPhoto()" aria-label="Previous photo">&larr;</button>
                    <img id="lightboxImage" src="" alt="">
                    <button type="button" class="lightbox-arrow lightbox-next" onclick="nextPhoto()" aria-label="Next photo">&rarr;</button>
                </div>

                <div class="lightbox-info">
                    <p class="lightbox-caption" id="lightboxCaption"></p>
                    <p class="lightbox-counter" id="lightboxCounter"></p>
                </div>
            </div>
        </div>
    </div>

<?php
$conn->close();
include "includes/footer.php";
?>
