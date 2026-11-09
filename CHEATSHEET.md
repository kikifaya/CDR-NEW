# Exam cheat sheet – where everything is

## HTML or PHP?

- **HTML pages** (`experiences.html`, `contact.html`, `policies.html`) only show information, so they don't need PHP.
  They use HTML, CSS, Bootstrap and JavaScript. Their menu and footer are written into each page.
- **PHP pages** are used only where the exam asks for server-side work:
  - processing forms: `contact.php`, `subscribe.php`, `register.php`, `login.php`, `booking.php`, admin forms
  - reading from the database: `index.php` (the room cards), `rooms.php`, `my_bookings.php`, admin pages
  - login, sessions and protected pages: `login.php`, `logout.php`, `includes/auth_check.php`, `includes/admin_check.php`, `setup_admin.php`
- Example of "HTML form processed using PHP": `contact.html` has `<form action="contact.php" method="POST">`.
  `contact.php` saves the message in the database and shows the success or error message
  (just like `student_form` → `process.php` in the notes).

Use **Ctrl + F** in VS Code to find the words in quotes.

## Task 1 – Bootstrap 5

| Requirement | File | Look for |
|---|---|---|
| Containers and grid | every page | `container`, `row`, `col-12 col-md-6 col-lg-4` |
| Cards | `index.php`, `rooms.php`, `booking.php`, `my_bookings.php` | `card room-card`, `card booking-card` |
| Buttons | everywhere | `btn btn-lux`, `btn btn-lux-outline` |
| Forms | `contact.html`, `booking.php`, `register.php`, `login.php`, `admin_rooms.php`, footer newsletter | `form-control`, `form-select`, `form-label` |
| Alerts | every form page | `alert alert-success`, `alert alert-danger` |
| Responsive navbar | `includes/header.php` | `navbar navbar-expand-xl`, `navbar-toggler`, `collapse navbar-collapse` (on phones and tablets the collapse opens as a full-screen menu) |
| Modals | `includes/room_modal.php` (used on `rooms.php` and `booking.php`) | `modal fade`, `data-bs-toggle="modal"` (click a room photo) |
| Progress bars | `booking.php`, `admin_dashboard.php` | `progress`, `progress-bar` |
| Modal (3rd) | `admin_users.php` – Reset password window | `modal fade`, `openResetPassword()` |
| Image carousel | The room photo gallery in `includes/room_modal.php` (Rooms page → Photos & details) and the guest reviews slider on `index.php` | `carousel slide` |
| Modal (2nd) | `gallery.php` – the full-screen photo viewer | `modal fade lightbox` |

## Task 2 – JavaScript (`js/script.js`)

**Functions** (more than 2): `calculateTotal()`, `countNights()`, `getMealPrice()` (uses a **switch**),
`showWelcome()` (uses **if / else if / else**), `validateBooking()`, `validateRegister()`, `validateContact()`,
`openMenu()`, `closeMenu()`, `changeMenuBackground()`, `resetMenuBackground()`, `togglePassword()`, `updateProgress()`.

**Scroll animations** (like Rosa Group), also in `script.js`:
- `splitWords()` cuts a heading into words, and `revealOnScroll()` fades the words in one by one when the text comes on screen (`reveal-words` class). Cards and photos with the `reveal` class slide up.
- `moveParallax()` moves every element that has a `data-speed="..."` attribute at its own speed while you scroll: the overlapping photos, the "An island escape" photos, the spa banner, the big "C" and "2025". A bigger number moves faster.
- `openHeroWindow()` (new design) grows the round video window on the home page to full screen as you scroll. It works out how far you have scrolled (0 to 1) and saves it in the CSS variable `--p`, which the `clip-path: circle(...)` in `style.css` uses.
- `changeStory()` (new design) moves the "Moments to share" stories, like the Rosa site: five frames stay still and the photos **wipe** across them (a CSS `clip-path` animation). It runs from the round arrow buttons and the side frames (`onclick`), from a finger swipe on phones (`touchstart` / `touchend`), and by itself every 6 seconds (`setTimeout` in `startStoryTimer()`). `showStories()` puts the right photo in each frame, `storyAt()` makes the list wrap around (after the last story comes the first) and `updateStoryProgress()` fills the little lines under the middle story.
- **Quick booking bar** (home page): `openDatePicker()` opens the calendar when you click anywhere in a date box (`showPicker()`), `showQuickDates()` writes the chosen dates in words ("18 Dec 2026", "Friday") with `toLocaleDateString()`, `showQuickNights()` counts the nights, and `changeGuests()` is the − / + buttons (1 to 4 guests, saved in a hidden `guests` field that the GET form sends to `booking.php`).
- `setupCursorLabel()` and `moveCursorLabel()` (new design, like the Belmond site): over photos you can click, a small label ("Explore", "View room", "Back", "Next") follows the mouse. The text comes from the element's `data-cursor="..."` attribute (`getAttribute`), the label glides after the mouse (`mousemove` event + `requestAnimationFrame`) and tilts in the direction you move. It only runs on computers with a mouse (`matchMedia("(hover: hover) and (pointer: fine)")`).
- `openRoomStack()` (new design) makes the rooms on the home page a stack of cards: each card sticks (`position: sticky`), the next one slides over it, the card behind shrinks back, and the background changes to that room's colour.
- `showWhatsapp()` shows the WhatsApp button once the guest scrolls down; `goToBookingForm()` is the "Review & book" button in the phone bar on the booking page, and `calculateTotal()` also writes the total into that bar.
- The places strip ("Praslin · Côte d'Or Beach · …") moves by itself with a CSS animation (`@keyframes marquee` in `style.css`).
- All of this runs on the `scroll` event (`window.addEventListener("scroll", animateOnScroll)`).

**Events:**

| Event | Where | What happens |
|---|---|---|
| `onclick` | "Menu" button (every page), `login.php` "Show" button, `contact.php` "Go back" button | Opens the full-screen menu, shows the password, goes back with `history.back()` |
| `onchange` | `booking.php` room, dates, guests, meal plan; `rooms.php` sort | Updates the total price; reloads sorted rooms |
| `onmouseover` | Full-screen menu links (every page) | Changes the menu background photo and turns the link gold |
| `onmouseout` | Full-screen menu links (every page) | Puts the original background and colour back |
| `onsubmit` | `booking.php`, `register.php`, `contact.html` forms | Runs the validation function |
| `scroll`, `keydown` (addEventListener) | `script.js` bottom | Navbar turns solid when scrolling; Esc closes the menu |

**Validation (3+ fields):**
- `validateRegister()`: name not empty, email format, username 4–20 characters, password at least 6 characters, passwords match.
- `validateBooking()`: room chosen, check-in not in the past, check-out after check-in, guests within the room's limit, phone format.
- `validateContact()`: name, email, subject, message at least 10 characters.

## Task 3 – PHP

| Requirement | Where |
|---|---|
| Forms processed with PHP | `contact.html` → `contact.php`; footer newsletter → `subscribe.php`; booking, register, login and admin pages use `if ($_SERVER["REQUEST_METHOD"] == "POST")` |
| **GET** | quick booking bar on `index.php` → `booking.php?check_in=…&check_out=…&guests=2`, `review.php?booking_id=`, `admin_reviews.php?status=`, `rooms.php` (`$_GET["type"]`, `$_GET["sort"]`), `booking.php?room_id=`, `admin_bookings.php?status=`, `admin_rooms.php?edit=`, messages like `login.php?message=registered` |
| **POST** | every form that saves data |
| include / require | `include "includes/room_modal.php";`, `require "includes/db_connect.php";`, `require "includes/auth_check.php";`, `require "includes/admin_check.php";`, `include "includes/header.php";`, `include "includes/footer.php";`, `include "includes/admin_nav.php";` |
| Success and error messages | `$successMessage` / `$errorMessage` → `alert-success` / `alert-danger` |

## Task 4 – MySQL

- Database **`hotel_db`** with 11 tables: `users`, `rooms`, `room_photos`, `bookings`, `reviews`, `messages`, `subscribers`, `settings`, `guest_notes`, `message_status`, `room_blocks`.
- `room_photos` holds the extra gallery photos of each room (`room_id` links it to `rooms`). The pages read it with `SELECT * FROM room_photos` and group the photos by room.
- Connection: `includes/db_connect.php` → `new mysqli($host, $username, $password, $database)` and `connect_error`.
- **Insert:** `INSERT INTO bookings` (booking.php), `INSERT INTO users` (register.php), `INSERT INTO messages` (contact.php), `INSERT INTO rooms` (admin_rooms.php), `INSERT INTO subscribers` (subscribe.php).
- **Retrieve and display:** `SELECT * FROM rooms` (rooms.php), bookings with `JOIN` (my_bookings, admin_bookings), users, messages.
- **Update / delete** (extra): confirm/cancel bookings, edit rooms, delete bookings, rooms and messages.
- Every insert/update/delete uses **prepare → bind_param → execute** like in the notes.
  - `bind_param` letters: `s` = string, `i` = integer, `d` = decimal number.
- `$conn->set_charset("utf8mb4");` in `includes/db_connect.php` makes special characters like **ô** in Côte and **²** in m² show correctly.
- `mysqli_report(MYSQLI_REPORT_OFF);` in `includes/db_connect.php`: newer PHP stops the page with an error
  instead of returning false. This line makes the `if ($stmt->execute()) { ... } else { ... }` messages work like in the notes.

## Extra features (good to mention in the demo)

- **Availability check** (`booking.php`): before saving, PHP counts the bookings that overlap the chosen dates
  (`SELECT COUNT(*) ... WHERE room_id = ? AND status != 'Cancelled' AND check_in < ? AND check_out > ?`).
  If that number reaches the room's `total_rooms`, the guest gets "fully booked" instead of a booking.
- **Check-out calendar:** `updateCheckoutMin()` in `script.js` (onchange on Check-in) stops guests picking a check-out date before the check-in date.
- **Admin photo upload** (`admin_photos.php` and the room form in `admin_rooms.php`): the form has `enctype="multipart/form-data"` and an `<input type="file">`.
  `upload_photo.php` (loaded with `require`) has the `uploadPhoto()` function: it reads `$_FILES`, checks the file is a JPG/PNG/WEBP photo (`getimagesize()`),
  gives it a new name, and saves it in `images` with `move_uploaded_file()`. Then the file name is saved in the database (`INSERT INTO room_photos` / `UPDATE rooms`).
  `previewPhoto()` in `script.js` (onchange) shows the photo before saving.
- **My account** (`account.php`): the guest updates their details (`UPDATE users`) and changes their password.
  The current password is checked with `password_verify()`, and the new one is saved with `password_hash()`. JS check: `validatePasswordChange()`.
- **Booking thank-you page** (`booking_confirmation.php?id=…`): after a booking is saved, `booking.php` sends the guest there with
  `header("Location: booking_confirmation.php?id=" . $newBookingId)`. Refreshing the page can't book twice. The page reads the booking with a `JOIN`
  of `bookings`, `rooms` and `users`. Only that guest (or the admin) can open it.
  - Booking reference: `bookingReference()` in `includes/functions.php` (loaded with `require`) turns booking 3 into **CDR-2026-0003**.
    It's shown on the thank-you page, in My bookings and on the admin pages.
  - Countdown: `showCountdown()` in `script.js` uses `if / else if / else` to show "begins in 64 days", "tomorrow" or "today".
  - Print or save as PDF: `onclick="window.print()"`. The `@media print` rules in `style.css` hide the menu, footer and buttons.
- **Map** on `contact.html` (Google Maps `<iframe>`, needs internet) and Instagram / Facebook links in the footer.

## New features (November update)

- **Quick booking bar** (home page, under the big photo): a `<form action="booking.php" method="GET">` with check-in,
  check-out and guests. The dates show in the address bar – that's **GET**.
  - JS in `script.js`: `updateQuickCheckout()` (onchange on Check-in: sets check-out to the next day),
    `showQuickNights()` (shows "3 nights"), `validateQuickBook()` (onsubmit: both dates, not in the past, check-out after check-in).
  - `booking.php` reads `$_GET["check_in"]`, `$_GET["check_out"]`, `$_GET["guests"]` (checked with `preg_match`), fills in the form,
    shows the "Your dates" box, and runs the availability query for each room → badges "Available", "Only 2 left", "Fully booked", "Sleeps up to 2 guests".
  - Not logged in? `auth_check.php` remembers the page in `$_SESSION["after_login"]`. After logging in or creating an account,
    `bookingReturnUrl()` in `includes/functions.php` sends the guest back to the booking page **with their dates**.
- **Guest reviews** – new table `reviews` (`booking_id` is `UNIQUE`, so one review per stay).
  - `my_bookings.php`: a **Rate your stay** button appears on stays that are `Confirmed` and already finished (`check_out <= today`).
  - `review.php?booking_id=3`: star rating (radio buttons styled as stars), title and review. JS: `validateReview()` (3 fields),
    `showRatingText()` (onchange: "5 out of 5 – Excellent"), `countCharacters()` (**oninput**: "120 / 1000").
    PHP checks the booking belongs to the guest, then `INSERT INTO reviews` with status `Pending`.
  - `admin_reviews.php` (new **Reviews** tab): approve / hide (`UPDATE reviews SET status = ?`) or delete. Shows count, average and waiting.
  - Home page: approved reviews in a Bootstrap **carousel** with the average score (`AVG(rating)`), first name only. The section only shows when there's at least one approved review.
  - Rooms page: each room shows its average rating (`GROUP BY room_id`).
  - `showStars()` in `includes/functions.php` turns 4 into ★★★★☆.
- **Gallery page** (`gallery.php`, in the menu on every page): hotel photos from a PHP array **plus every room photo from the database**
  (`rooms` and `room_photos`), so photos the admin uploads appear automatically.
  - Filter buttons: `filterGallery()` (onclick) hides the other categories and updates "12 photos".
  - Click a photo → full-screen viewer (Bootstrap modal): `openLightbox()`, `nextPhoto()`, `prevPhoto()`, and the ← → keys (keydown).
- **Opening animation**: the logo fades in on a sand-coloured screen on the first page of each visit (`sessionStorage` in the `<head>` script),
  then lifts away. Pages fade in, and `leavePage()` fades them out when you click a link. Both are switched off for people who turn off animations
  in their computer settings (`prefers-reduced-motion`).

## Admin features (hotel system update)

- **Booking statuses:** `Pending → Confirmed → Checked in → Checked out`, or `Cancelled` / `No-show`.
  The list is `bookingStatuses()` in `includes/functions.php`; `statusClass()` turns "Checked in" into the CSS class `status-checked-in`.
  No-shows and cancellations free the room again (`status NOT IN ('Cancelled', 'No-show')` in the availability checks).
- **`booking_actions.php`** (loaded with `require` on the dashboard and Bookings page): one place that handles the buttons
  (`UPDATE bookings SET status = ? WHERE id = ?` and `DELETE`). `bookingButtons()` decides which buttons show for each status.
- **Today at the hotel** (`admin_dashboard.php`): arrivals (`check_in = today`), departures (`check_out <= today` and still checked in),
  rooms in use and guests in house (`SUM(guests)`), tomorrow's arrivals, with **Check in**, **No-show** and **Check out** buttons.
- **Search** (`admin_bookings.php?q=...`, GET): by name, email, phone or reference. `CDR-2026-0003` is turned into booking 3 with `preg_match`.
  One query handles every case: `WHERE (? = '' OR bookings.status = ?) AND (? = '' OR users.full_name LIKE ? ...)`.
- **Calendar** (`admin_calendar.php?month=2026-11`, GET): one row per room type, one column per day. PHP loops through the bookings of the month
  and counts how many rooms are taken each night (`check_in <= day < check_out`). White = free, light blue = some, dark = full.
  Point at a day for the guest names; click it to start a new booking for that room and date. Last column = occupancy % for the month.
- **New / edit booking** (`admin_booking_form.php`, `?id=5` to edit): for phone, WhatsApp and walk-in guests. Choose an existing guest or
  "+ New guest" (`toggleNewGuest()` shows the name and email boxes, onchange) – PHP then creates their account (`INSERT INTO users`).
  The total is worked out in PHP like in `booking.php`, and the availability check leaves out the booking being edited (`id != ?`).
- **Reset password** (`admin_users.php`): the Reset password window (Bootstrap modal). `makePassword()` makes a random 8-letter password,
  `validateResetPassword()` checks it, PHP saves it with `password_hash()` (`UPDATE users SET password = ?`). Use it for guests who forgot
  their password, and for walk-in guests created by the admin (they get a random password until you set one).
- **WhatsApp button** (`footer.php` and the three `.html` pages): a link to `https://wa.me/2482537688` with a ready-made message. Not shown on admin pages.
- **Policy tick box** (`booking.php`): `agree_policies` checkbox – checked in `validateBooking()` (JS) **and** in PHP (`isset($_POST["agree_policies"])`).

## Admin design (sidebar layout)

- Admin pages set `$currentPage = "admin"`. Then `header.php` doesn't show the website menu: it loads **`admin_sidebar.php`**
  (sidebar + top bar), and `footer.php` closes it instead of showing the website footer. Every admin page gets the new look automatically.
- Sidebar: Bootstrap **offcanvas** (`offcanvas-lg`) – a fixed menu on a computer, a slide-in menu on a phone (the ☰ button).
- Top bar: search box (`<form action="admin_bookings.php" method="GET">`), **New booking**, the bell (Bootstrap **dropdown**) with
  bookings to confirm, reviews to approve and arrivals today, and the profile menu.
- Icons: `adminIcon("calendar")` in `admin_icons.php` returns a small SVG drawing.
- **Overview** (`admin_dashboard.php`):
  - KPI cards: revenue booked (with % change against the period before), guests in house, occupancy tonight, waiting for you.
  - **Today / Week / Month** = `admin_dashboard.php?period=week` (GET) changes the revenue card.
  - Mini bar charts: `miniBars()` turns 14 numbers into bars (CSS heights in %).
  - **Revenue flow**: `revenueChart()` draws an SVG line from the money per month (`GROUP BY` month of arrival).
  - **Booking status donut**: a CSS `conic-gradient`, one slice per status, plus the progress bars.
  - **Live timeline**: the newest bookings, reviews, messages and guest accounts, mixed and sorted by time with `usort()`.
  - Print report: `window.print()`; the sidebar and buttons are hidden when printing.

## More admin pages

- **Settings** (`admin_settings.php`): saves the breakfast and half board prices, check-in/out times and contact details in the
  `settings` table (`INSERT ... ON DUPLICATE KEY UPDATE`). `loadSettings()` in `includes/functions.php` reads them (with default values if the
  table is empty), `mealPrice()` gives the price of a meal plan. The booking page puts the price on each meal option (`data-price`),
  so `getMealPrice()` in `script.js` uses the same price as PHP. The plain `.html` pages keep their own text.
- **Guest profile** (`admin_guest.php?id=2`, open it from Guest accounts or by clicking a guest's name): totals worked out in PHP,
  all stays (`SELECT ... WHERE bookings.user_id = ?`), reviews, and the **VIP** tick + private notes in the `guest_notes` table.
  The notes and VIP badge also show on the dashboard's arrivals and in the bookings table (`LEFT JOIN guest_notes`).
- **Messages inbox** (`admin_messages.php`): New / Read / Replied. The status is kept in `message_status`; a message with no row there is "New"
  (`COALESCE(message_status.status, 'New')`). "Reply by email" is a `mailto:` link with the subject filled in. The sidebar and bell count new messages.
- **Reports** (`admin_reports.php?year=2026`): each night of a stay is counted in the month it falls in. Occupancy = nights sold ÷ (rooms × days).
  **Download for Excel** = `?export=csv`: PHP sends `header("Content-Type: text/csv")` and writes the rows with `fputcsv()`.
- **Block dates** (`admin_blocks.php`): closes rooms in `room_blocks`. `roomsBlocked()` in `includes/functions.php` is added to the availability checks in
  `booking.php` and `admin_booking_form.php`, so closed rooms can't be booked. The calendar shows them striped.

## Task 5 – Login

- `login.php`: finds the user with `SELECT ... WHERE username = ?`, then `password_verify()` checks the password.
- Passwords are saved with `password_hash()` (in `register.php`), so the database never stores the real password.
- After login: `$_SESSION["user_id"]`, `$_SESSION["full_name"]`, `$_SESSION["role"]`.
- **Protected pages:** `require "includes/auth_check.php";` (booking, my bookings) – no session → sent to the login page.
- **Admin only:** `require "includes/admin_check.php";` (all `admin_` pages) – a normal user is sent back with "only for the hotel admin".
- **Logout:** `logout.php` → `session_unset(); session_destroy();` (as in the class example).
- **Wrong login:** "Username not found." or "Incorrect password." (as in the class example).
- **First admin account:** `setup_admin.php` creates it with `password_hash("admin123", PASSWORD_DEFAULT)`; delete the file afterwards.
- `htmlspecialchars()` is used when **displaying** data, so the text is shown safely and isn't saved twice-encoded.

## Demo order (about 5 minutes)

1. Home (`index.php`): scroll so the round video window opens to full screen (`openHeroWindow()`), click the round arrows of "Moments to share" (`changeStory()` with `onclick`), keep scrolling to see the room cards stack up (`openRoomStack()`), hover the **Menu** links. On a phone: the full-screen menu.
   Explain: HTML for pages that only show information, PHP only for server-side work.
2. Rooms: filter buttons (look at the **URL** – that's GET), sort, open a room modal, "Book this room".
3. Not logged in → sent to Log in (protected page).
4. Create an account with wrong data (JS errors), then correct data (success message).
5. Log in → welcome message → book a room: watch the total and progress bar change → success alert.
6. My bookings → cancel. Then try `admin_dashboard.php` as this user → blocked.
7. Log out, log in as **admin** → dashboard, confirm a booking, add / edit / delete a room, messages.
8. Show phpMyAdmin: the new rows in `users`, `bookings` and `messages`.

**Showing the new features** (add these to the demo):
- Home: the logo intro (open the site in a new window to see it again), then the **quick booking bar** → pick dates → Check availability → log in → the booking page already has your dates and shows which rooms are free.
- Log in as **guest** → My bookings → **Rate your stay** on the September stay → leave a review (try sending it empty first for the JS errors).
- Log in as **admin** → Reviews → **Approve** → go to the home page: the review is now in the slider, and the room shows its rating on the Rooms page.
- **Gallery**: filter by Rooms, click a photo, use the arrow keys.
- **Admin dashboard → Today at the hotel.** On 18 November the example data shows a family leaving and a couple arriving: press **Check out** and **Check in**.
  On another day: Bookings → **+ New booking** → "+ New guest", check-in today → Save → back on the dashboard it's under Arrivals.
- **Calendar**: point at the blue days to see who's staying, click a free day to start a booking.
- **Search**: type "Sample" or a reference like CDR-2026-0005.
- **Users → Reset password** → Make one → Save, then log in as that guest with the new password.
- As a guest, try booking without ticking the policy box (JS error).

## Changes she might ask for

| She asks | What to do |
|---|---|
| Change a success message | Search the text, e.g. "Your message has been sent", and edit it |
| Add a field to the contact form (e.g. phone) | 1) phpMyAdmin: add column `phone VARCHAR(30)` to `messages`. 2) Add an `<input name="phone">` in `contact.html`. 3) In `contact.php`: `$phone = trim($_POST["phone"]);` and add `phone` and one more `?` to the INSERT, and one more `s` in `bind_param("sssss", ...)` |
| Change the breakfast price | Admin → **Settings** → Breakfast → Save (no code needed). In code: the default is in `loadSettings()` in `includes/functions.php` |
| Make the password at least 8 characters | `script.js` → `validateRegister()` `password.length < 6`, and `register.php` → `strlen($password) < 6` |
| Show rooms in a different order | `rooms.php`: change `ORDER BY price` (`$sort`) |
| Show only confirmed bookings | `admin_bookings.php?status=Confirmed` or add `WHERE status = 'Confirmed'` |
| Change a colour | `css/style.css` top: `--ocean-700`, `--sand-50`, `--accent` |
| Add a new menu link | `header.php` **and** the three `.html` pages: copy one `<li class="nav-item">` (navbar) and one `<li>` in `menu-links` |
| Add a link to the admin sidebar | `admin_sidebar.php`: add a line to `$menuGroups`, e.g. `["admin_messages.php", "messages", "messages", "Messages"]` (page, tab name, icon, label) |
| Change the admin colours | `style.css`, `.admin-body` at the start of the ADMIN AREA part: `--admin-green`, `--admin-orange`... |
| Allow reviews with at least 10 characters | `script.js` → `validateReview()` `comment.length < 20`, and `review.php` → `strlen($comment) < 20` (both!) |
| Add a photo to the gallery | Put the photo in `images`, then add one line to the `$photos` array at the top of `gallery.php` |
| Add a gallery category | `gallery.php`: add it to `$categories` (e.g. `"events" => "Events"`) and use that key in `$photos` |
| Make the intro show every time | `header.php` (and the three `.html` pages): delete the `if (!sessionStorage...)` check, keep `classList.add("intro-on")` |
| Add a new booking status (e.g. "Paid") | `functions.php` → add it to `bookingStatuses()`, add a CSS class `.status-paid` in `style.css`, and a button in `bookingButtons()` + `$newStatuses` in `booking_actions.php` |
| Show 2 weeks in the calendar instead of a month | `admin_calendar.php`: change `$daysInMonth` |
| Change the WhatsApp message | `footer.php` and the three `.html` pages: the `?text=` part of the `wa.me` link (spaces are `%20`) |
| Count something new on the dashboard | `admin_dashboard.php`: copy a `SELECT COUNT(*) AS total ...` block and a `stat-card` |
