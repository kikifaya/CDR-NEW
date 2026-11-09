<?php
session_start();
require "includes/db_connect.php";
require "includes/functions.php";

$pageTitle = "Luxury Boutique Hotel on Praslin";
$currentPage = "home";

$sql = "SELECT * FROM rooms ORDER BY price ASC LIMIT 3";
$rooms = $conn->query($sql);

// Approved guest reviews for the reviews slider
$reviewSql = "SELECT reviews.rating, reviews.title, reviews.comment, reviews.created_at, users.full_name, rooms.room_name FROM reviews JOIN users ON reviews.user_id = users.id JOIN rooms ON reviews.room_id = rooms.id WHERE reviews.status = 'Approved' ORDER BY reviews.created_at DESC LIMIT 6";
$reviews = $conn->query($reviewSql);
$reviewStats = $conn->query("SELECT COUNT(*) AS total, AVG(rating) AS average FROM reviews WHERE status = 'Approved'")->fetch_assoc();

include "includes/header.php";
?>

    <section class="hero-scroll" id="home">
        <div class="hero-sticky" data-cursor="Scroll">
            <h1 class="hero-title">
                <span class="hero-line">A window</span>
                <span class="hero-line">to <em>paradise</em></span>
            </h1>

            <div class="hero-window">
                <img class="hero-poster" src="images/hero.jpg" alt="">
                <video autoplay muted loop playsinline poster="images/hero.jpg">
                    <source src="video/hero.mp4" type="video/mp4">
                </video>
                <div class="hero-window-shade"></div>

                <div class="hero-content">
                    <p class="eyebrow">Anse Volbert &middot; Praslin &middot; Seychelles</p>
                    <p class="hero-caption">Twenty-two rooms, one quiet beach.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="rooms.php" class="btn btn-lux-light" data-cursor="Explore">Discover the rooms</a>
                        <a href="booking.php" class="btn btn-lux" data-cursor="Explore">Book your stay</a>
                    </div>
                </div>
            </div>

            <a href="#story" class="scroll-cue">Scroll</a>
        </div>
    </section>

    <div class="quick-book-wrap">
        <div class="container">
            <div class="qb-head">
                <p class="eyebrow mb-0">Plan your stay</p>
                <p class="qb-note">Book direct with the hotel &middot; Free cancellation up to 30 days before arrival</p>
            </div>

            <form class="quick-book" id="quickBook" action="booking.php" method="GET" onsubmit="return validateQuickBook(event)" novalidate>
                <div class="qb-field qb-date-field" onclick="openDatePicker('qb_check_in')">
                    <label for="qb_check_in">Check-in</label>
                    <span class="qb-value" id="qbCheckInText"><span class="qb-empty">Add date</span></span>
                    <span class="qb-sub" id="qbCheckInSub">Arrival from <?php echo htmlspecialchars($settings["checkin_time"]); ?></span>
                    <input type="date" class="qb-date-input" id="qb_check_in" name="check_in" min="<?php echo date("Y-m-d"); ?>" onchange="updateQuickCheckout()">
                    <svg class="qb-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/></svg>
                </div>

                <div class="qb-field qb-date-field" onclick="openDatePicker('qb_check_out')">
                    <label for="qb_check_out">Check-out</label>
                    <span class="qb-value" id="qbCheckOutText"><span class="qb-empty">Add date</span></span>
                    <span class="qb-sub"><span id="qbCheckOutSub">Departure by <?php echo htmlspecialchars($settings["checkout_time"]); ?></span> <span class="qb-nights" id="qbNights"></span></span>
                    <input type="date" class="qb-date-input" id="qb_check_out" name="check_out" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>" onchange="showQuickNights()">
                    <svg class="qb-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/></svg>
                </div>

                <div class="qb-field qb-guests-field">
                    <span class="qb-label" id="qbGuestsLabel">Guests</span>
                    <div class="qb-stepper" role="group" aria-labelledby="qbGuestsLabel">
                        <button type="button" class="qb-step" id="qbLess" onclick="changeGuests(-1)" aria-label="One guest less">&minus;</button>
                        <span class="qb-value" id="qbGuestsText" aria-live="polite">2 Guests</span>
                        <button type="button" class="qb-step" id="qbMore" onclick="changeGuests(1)" aria-label="One guest more">+</button>
                    </div>
                    <span class="qb-sub">Up to 4 per room</span>
                    <input type="hidden" id="qb_guests" name="guests" value="2">
                </div>

                <button type="submit" class="btn btn-lux qb-submit">Check availability <span aria-hidden="true">&rarr;</span></button>
            </form>
            <p class="qb-error" id="qbError" role="alert"></p>
        </div>
    </div>

    <section class="intro-section" id="story">
        <div class="container intro-top">
            <div class="row">
                <div class="col-12 col-lg-5">
                    <p class="eyebrow">Since 2025</p>
                    <p class="statement reveal-words">
                        At Côte d'Or Retreat we have been creating slow, <em>unhurried</em> island days for every guest who walks through our garden.
                    </p>
                </div>
            </div>
        </div>

        <!-- Big overlapping photos, like the Rosa Group site. Each one moves at its own speed (data-speed). -->
        <div class="collage">
            <div class="collage-photos">
                <div class="photo photo-1" data-speed="0.04"><img src="images/intro-1.jpg" alt="A guest relaxing on the terrace among palm trees"></div>
                <div class="photo photo-2" data-speed="0.12"><img src="images/intro-2.jpg" alt="Waves breaking on the shore seen from above"></div>
                <div class="photo photo-3" data-speed="0.2"><img src="images/intro-3.jpg" alt="Palm trees by the sea"></div>
            </div>

            <div class="collage-text">
                <span class="collage-line"></span>
                <p class="statement statement-sm reveal-words">A family-run retreat a two-minute walk from the soft white sand of <em>Côte d'Or Beach</em>.</p>
                <p class="body-text reveal">
                    Twenty-two rooms and apartments, a quiet pool, Creole cooking and the slow rhythm of island life.
                </p>

                <div class="intro-stats reveal">
                    <div><strong>22</strong><span>Rooms &amp; suites</span></div>
                    <div><strong>150 m</strong><span>To the beach</span></div>
                    <div><strong>6 km</strong><span>Vallée de Mai</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="since-section" aria-label="Since 2025">
        <p class="since-word since-top" data-speed="-0.08">Since</p>
        <div class="since-photo photo"><img src="images/beach.jpg" alt="Côte d'Or Beach at Anse Volbert"></div>
        <p class="since-word since-bottom" data-speed="0.08">2025</p>
    </section>

    <section class="stories-section">
        <div class="container text-center">
            <p class="eyebrow">Stories</p>
            <h2 class="display-title reveal-words">Moments<br>to<br>share</h2>
        </div>

        <div class="stories" id="stories" aria-roledescription="carousel" aria-label="Moments to share">
            <!-- The stories. JavaScript shows them in the five frames below. -->
            <div class="story-list" id="storyList">
                <figure class="story-item"><img src="images/moment-1.jpg" alt="Morning swim in clear water"><figcaption>Morning swims at Côte d'Or</figcaption></figure>
                <figure class="story-item"><img src="images/pool.jpg" alt="The hotel pool"><figcaption>Long afternoons by the pool</figcaption></figure>
                <figure class="story-item"><img src="images/moment-2.jpg" alt="Giant palm forest"><figcaption>Walks through the Vallée de Mai</figcaption></figure>
                <figure class="story-item"><img src="images/restaurant.jpg" alt="Dinner table set by the sea"><figcaption>The catch of the day</figcaption></figure>
                <figure class="story-item"><img src="images/anse-lazio.jpg" alt="Anse Lazio beach"><figcaption>A day at Anse Lazio</figcaption></figure>
                <figure class="story-item"><img src="images/moment-3.jpg" alt="Sunset over the ocean"><figcaption>Sunsets over the Indian Ocean</figcaption></figure>
                <figure class="story-item"><img src="images/spa.jpg" alt="The spa"><figcaption>Coconut oil and island herbs</figcaption></figure>
            </div>

            <div class="story-slot" data-offset="-2"></div>
            <div class="story-slot" data-offset="-1" onclick="changeStory(-1)" data-cursor="Back"></div>
            <div class="story-slot story-center" data-offset="0"></div>
            <div class="story-slot" data-offset="1" onclick="changeStory(1)" data-cursor="Next"></div>
            <div class="story-slot" data-offset="2"></div>

            <button type="button" class="story-arrow story-prev" onclick="changeStory(-1)" aria-label="Previous story" data-cursor="Back">&larr;</button>
            <button type="button" class="story-arrow story-next" onclick="changeStory(1)" aria-label="Next story" data-cursor="Next">&rarr;</button>
        </div>
        <div class="story-progress" id="storyProgress" aria-hidden="true"></div>
    </section>

    <section class="rooms-intro">
        <div class="container text-center">
            <p class="eyebrow">Stay</p>
            <h2 class="display-title reveal-words"><em>Rooms</em> made<br>for slow days</h2>
            <p class="body-text mx-auto reveal">Three ways to stay, from a sea-breeze double to a whole apartment for the family. Every room opens onto the garden.</p>
        </div>
    </section>

    <?php
    // The rooms as a stack of cards: each new card slides up over the one before,
    // and the background colour changes for each room (like the divisions on the Rosa Group site)
    $stackColours = ["#5f6853", "#c2b09b", "#46303a", "#34474f"];
    $roomNumber = 0;
    ?>
    <section class="room-stack" id="roomStack" style="background-color: <?php echo $stackColours[0]; ?>;">
        <?php
        if ($rooms && $rooms->num_rows > 0) {
            while ($row = $rooms->fetch_assoc()) {
                $colour = $stackColours[$roomNumber % 4];
                $roomNumber++;
        ?>
            <article class="card room-card stack-card" data-colour="<?php echo $colour; ?>" data-cursor="View room">
                <div class="photo">
                    <img src="images/<?php echo htmlspecialchars($row["image"]); ?>" alt="<?php echo htmlspecialchars($row["room_name"]); ?>">
                </div>
                <div class="card-body stack-text">
                    <h3 class="card-title"><?php echo htmlspecialchars($row["room_name"]); ?></h3>
                    <p class="stack-sub"><?php echo htmlspecialchars($row["size_m2"]); ?> m² &middot; Up to <?php echo htmlspecialchars($row["max_guests"]); ?> guests &middot; From $<?php echo number_format($row["price"], 0); ?></p>
                    <a href="rooms.php#room-<?php echo $row["id"]; ?>" class="link-line stretched-link">View room</a>
                </div>
            </article>
        <?php
            }
        } else {
            echo '<div class="container py-5"><div class="alert alert-info">No rooms found.</div></div>';
        }
        ?>
    </section>

    <section class="immersive">
        <div class="immersive-photos">
            <div class="photo" data-speed="0.04"><img src="images/strip-1.jpg" alt="Granite rocks on a Praslin beach"></div>
            <div class="photo" data-speed="0.14"><img src="images/strip-2.jpg" alt="Tropical breakfast with fresh fruit"></div>
            <div class="photo" data-speed="0.08"><img src="images/strip-3.jpg" alt="Turquoise bay with white sand"></div>
            <div class="photo" data-speed="0.18"><img src="images/strip-4.jpg" alt="A swimming pool lined with palm trees"></div>
        </div>
        <h2 class="immersive-title reveal-words">An <em>island</em><br>escape</h2>
    </section>

    <section class="experiences-section">
        <div class="container text-center mb-5">
            <p class="eyebrow">Experiences</p>
            <p class="statement mx-auto reveal-words">Spa mornings, Creole evenings and island days, all a few steps from your door.</p>
        </div>

        <a href="experiences.html#spa" class="value-card value-inset" data-cursor="Explore">
            <div class="photo" data-speed="0.06"><img src="images/spa.jpg" alt="The spa"></div>
            <div class="value-text">
                <h3>The spa</h3>
                <p>Massages with coconut oil and island herbs, in a quiet garden pavilion.</p>
            </div>
        </a>
        <a href="experiences.html#dining" class="value-card" data-cursor="Explore">
            <div class="photo" data-speed="0.06"><img src="images/restaurant.jpg" alt="Dinner by the sea"></div>
            <div class="value-text">
                <h3>Creole dining</h3>
                <p>The catch of the day, fresh fruit and spices from Praslin's gardens.</p>
            </div>
        </a>
        <div class="value-pair">
            <a href="experiences.html#explore" class="value-card" data-cursor="Explore">
                <div class="photo"><img src="images/vallee-de-mai.jpg" alt="Vallée de Mai palm forest"></div>
                <div class="value-text">
                    <h3>Vallée de Mai</h3>
                    <p>The forest of the coco de mer, six kilometres away.</p>
                </div>
            </a>
            <a href="experiences.html#explore" class="value-card" data-cursor="Explore">
                <div class="photo"><img src="images/anse-lazio.jpg" alt="Anse Lazio beach"></div>
                <div class="value-text">
                    <h3>Anse Lazio</h3>
                    <p>One of the most beautiful beaches in the world.</p>
                </div>
            </a>
        </div>
    </section>

    <section class="promise-band">
        <div class="container text-center">
            <p class="eyebrow">Our promise</p>
            <p class="promise-text reveal-words">To make every stay so calm, so personal and so close to the sea that it becomes the memory of your year.</p>
        </div>
    </section>

    <?php if ($reviews && $reviews->num_rows > 0) { ?>
    <section class="reviews-section" id="reviews">
        <div class="container">
            <div class="text-center mb-5">
                <p class="eyebrow">Guest reviews</p>
                <h2 class="display-title display-md reveal-words">In our guests' <em>words</em></h2>
                <p class="reviews-score">
                    <span class="score"><?php echo number_format($reviewStats["average"], 1); ?></span>
                    <?php echo showStars(round($reviewStats["average"])); ?>
                    <span class="score-text">out of 5 &middot; <?php echo $reviewStats["total"]; ?> <?php echo $reviewStats["total"] == 1 ? "review" : "reviews"; ?></span>
                </p>
            </div>

            <div id="reviewsCarousel" class="carousel slide carousel-fade reviews-carousel" data-bs-ride="carousel" data-bs-interval="7000">
                <div class="carousel-inner">
                    <?php
                    $slide = 0;
                    while ($review = $reviews->fetch_assoc()) {
                        $firstName = explode(" ", trim($review["full_name"]))[0];
                    ?>
                        <div class="carousel-item <?php if ($slide == 0) { echo "active"; } ?>">
                            <figure class="review-quote">
                                <?php echo showStars($review["rating"]); ?>
                                <h3 class="review-title">&ldquo;<?php echo htmlspecialchars($review["title"]); ?>&rdquo;</h3>
                                <blockquote><p><?php echo htmlspecialchars($review["comment"]); ?></p></blockquote>
                                <figcaption><?php echo htmlspecialchars($firstName); ?> &middot; <?php echo htmlspecialchars($review["room_name"]); ?> &middot; <?php echo date("F Y", strtotime($review["created_at"])); ?></figcaption>
                            </figure>
                        </div>
                    <?php
                        $slide++;
                    }
                    ?>
                </div>

                <?php if ($slide > 1) { ?>
                    <div class="reviews-controls">
                        <button class="review-arrow" type="button" data-bs-target="#reviewsCarousel" data-bs-slide="prev" aria-label="Previous review">&larr;</button>
                        <div class="carousel-indicators reviews-dots">
                            <?php for ($i = 0; $i < $slide; $i++) { ?>
                                <button type="button" data-bs-target="#reviewsCarousel" data-bs-slide-to="<?php echo $i; ?>" class="<?php if ($i == 0) { echo "active"; } ?>" aria-label="Review <?php echo $i + 1; ?>"></button>
                            <?php } ?>
                        </div>
                        <button class="review-arrow" type="button" data-bs-target="#reviewsCarousel" data-bs-slide="next" aria-label="Next review">&rarr;</button>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="location-section">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="photo location-photo" data-speed="0.05"><img src="images/beach.jpg" alt="Côte d'Or Beach at Anse Volbert"></div>
                </div>
                <div class="col-12 col-lg-5 offset-lg-1">
                    <p class="eyebrow">Location</p>
                    <h2 class="display-title display-md reveal-words">Everything Praslin, <em>close by</em></h2>
                    <ul class="distance-list mt-4">
                        <li><strong>Côte d'Or Beach</strong><span>150 m</span></li>
                        <li><strong>Praslin ferry jetty</strong><span>3 km</span></li>
                        <li><strong>Vallée de Mai</strong><span>6 km</span></li>
                        <li><strong>Anse Lazio</strong><span>7 km</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="marquee" aria-label="Places around Côte d'Or Retreat">
        <div class="marquee-track">
            <span>Praslin</span><span>Côte d'Or Beach</span><span>Anse Lazio</span><span>Vallée de Mai</span><span>La Digue</span><span>Curieuse</span>
            <span>Praslin</span><span>Côte d'Or Beach</span><span>Anse Lazio</span><span>Vallée de Mai</span><span>La Digue</span><span>Curieuse</span>
        </div>
    </section>

    <section class="cta-band">
        <div class="cta-photo" data-speed="0.08" style="background-image: url('images/moment-3.jpg');"></div>
        <div class="container text-center">
            <p class="eyebrow">Your stay</p>
            <h2 class="display-title reveal-words">Ready for<br>the <em>island</em>?</h2>
            <a href="booking.php" class="btn btn-lux mt-4">Book your stay</a>
        </div>
    </section>

<?php
$conn->close();
include "includes/footer.php";
?>
