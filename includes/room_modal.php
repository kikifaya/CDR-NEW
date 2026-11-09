<?php
$photos = [];
$photos[] = ["image" => $room["image"], "caption" => $room["room_name"]];

if (isset($roomPhotos[$room["id"]])) {
    foreach ($roomPhotos[$room["id"]] as $photo) {
        $photos[] = $photo;
    }
}

$modalId = "roomModal" . $room["id"];
$galleryId = "roomGallery" . $room["id"];
$photoTotal = str_pad(count($photos), 2, "0", STR_PAD_LEFT);
?>
                    <div class="modal fade room-modal" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content">
                                <button type="button" class="room-modal-close" data-bs-dismiss="modal">Close <span>&times;</span></button>

                                <div class="room-modal-grid">
                                    <div class="room-modal-gallery">
                                        <div id="<?php echo $galleryId; ?>" class="carousel slide carousel-fade gallery">
                                            <div class="carousel-inner">
                                                <?php foreach ($photos as $index => $photo) { ?>
                                                    <div class="carousel-item <?php if ($index == 0) { echo "active"; } ?>">
                                                        <div class="photo">
                                                            <img src="images/<?php echo htmlspecialchars($photo["image"]); ?>" alt="<?php echo htmlspecialchars($photo["caption"]); ?>">
                                                        </div>
                                                        <div class="gallery-caption">
                                                            <span class="gallery-count"><?php echo str_pad($index + 1, 2, "0", STR_PAD_LEFT); ?> <small>/ <?php echo $photoTotal; ?></small></span>
                                                            <span class="gallery-text"><?php echo htmlspecialchars($photo["caption"]); ?></span>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <?php if (count($photos) > 1) { ?>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $galleryId; ?>" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Previous photo</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $galleryId; ?>" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Next photo</span>
                                                </button>
                                            <?php } ?>
                                        </div>

                                        <?php if (count($photos) > 1) { ?>
                                            <div class="gallery-thumbs">
                                                <?php foreach ($photos as $index => $photo) { ?>
                                                    <button type="button" class="thumb <?php if ($index == 0) { echo "active"; } ?>" data-gallery="<?php echo $galleryId; ?>" data-bs-target="#<?php echo $galleryId; ?>" data-bs-slide-to="<?php echo $index; ?>" aria-label="Show photo <?php echo $index + 1; ?>">
                                                        <img src="images/<?php echo htmlspecialchars($photo["image"]); ?>" alt="">
                                                    </button>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="room-modal-info">
                                        <p class="eyebrow"><?php echo htmlspecialchars($room["room_type"]); ?> &middot; Côte d'Or Retreat</p>
                                        <h2 class="room-modal-title" id="<?php echo $modalId; ?>Label"><?php echo htmlspecialchars($room["room_name"]); ?></h2>
                                        <p class="room-modal-tagline"><?php echo htmlspecialchars($room["tagline"]); ?></p>
                                        <span class="gold-line"></span>

                                        <div class="room-specs">
                                            <div>
                                                <strong><?php echo htmlspecialchars($room["size_m2"]); ?> m²</strong>
                                                <span>Size</span>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($room["max_guests"]); ?></strong>
                                                <span>Guests</span>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($room["total_rooms"]); ?></strong>
                                                <span>Rooms</span>
                                            </div>
                                        </div>

                                        <p class="room-modal-text"><?php echo htmlspecialchars($room["description"]); ?></p>

                                        <p class="room-meta mb-3">In the room</p>
                                        <ul class="lux-list">
                                            <li>Air conditioning</li>
                                            <li>Free Wi-Fi</li>
                                            <li>Flat-screen TV</li>
                                            <li>Mini fridge</li>
                                            <li>Private terrace or balcony</li>
                                            <?php if ($room["room_type"] != "Double") { ?>
                                                <li>Kitchen</li>
                                            <?php } ?>
                                            <?php if ($room["room_type"] == "Apartment") { ?>
                                                <li>Living and dining area</li>
                                            <?php } ?>
                                        </ul>

                                        <div class="room-modal-book">
                                            <div class="room-modal-price">
                                                <span>From</span>
                                                $<?php echo number_format($room["price"], 0); ?>
                                                <span>per night</span>
                                            </div>
                                            <?php if ($modalAction == "select") { ?>
                                                <button type="button" class="btn btn-lux w-100" onclick="selectRoomFromModal(<?php echo $room["id"]; ?>, this)">Select this room</button>
                                            <?php } else { ?>
                                                <a href="booking.php?room_id=<?php echo $room["id"]; ?>" class="btn btn-lux w-100">Book this room</a>
                                            <?php } ?>
                                            <p class="room-modal-note">Breakfast $<?php echo mealPrice($settings, "Breakfast"); ?> &middot; Half board $<?php echo mealPrice($settings, "Half board"); ?> per person, per night<br>Check-in from <?php echo htmlspecialchars($settings["checkin_time"]); ?> &middot; Check-out by <?php echo htmlspecialchars($settings["checkout_time"]); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
