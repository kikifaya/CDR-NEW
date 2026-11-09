# Côte d'Or Retreat – Hotel Reservation System

A luxury redesign of the Côte d'Or Retreat website (Anse Volbert, Praslin, Seychelles) with a working
booking system. Built with HTML, CSS, JavaScript, Bootstrap 5.3.8, PHP and MySQL (MySQLi).

## New design (November 2026)

The whole guest website was redesigned in a dark, cinematic style inspired by the Rosa Group website:
charcoal background, big capital headlines in **Bodoni Moda** with one word in terracotta italic,
a round photo window on the home page that opens to full screen as you scroll, rooms shown as colour
bands, "Moments to share" stories with round arrow buttons, a full-screen menu on phones and a
"Your total / Review & book" bar at the bottom of the booking page on phones. All features and
database work are the same as before.

**Folder structure** (as in the class notes, *Web Application Authentication, Publishing and Deployment*):

```
cotedor-new/
├── index.php, rooms.php, booking.php, login.php ...   the pages
├── contact.html, experiences.html, policies.html      pages that only show information
├── setup_admin.php                                     run once, then delete
├── includes/      db_connect.php, header.php, footer.php, functions.php, admin_check.php ...
├── css/           style.css (guest website), admin.css (admin), bootstrap.min.css
├── js/            script.js, bootstrap.bundle.min.js
├── fonts/         Bodoni Moda and Jost
├── images/        photos and logos (room photos uploaded in the admin are saved here too)
└── video/         hero.mp4
```

**Setting up the new version next to the old one:** unzip it into `C:\wamp64\www\` (you get a
`cotedor-new` folder), copy your `images` and `video` folders from the old `cotedor` folder into
`cotedor-new`, and copy your `db_connect.php` (with your password) into `cotedor-new\includes\`.
The database stays the same `hotel_db`. Then open **http://localhost/cotedor-new/**.

Every page loads the shared files with `require "includes/db_connect.php";` and
`include "includes/header.php";`.

**Login** works like the class example: `password_verify()`, then `session_regenerate_id(true)`, with the
messages "Username not found." and "Incorrect password.". `logout.php` uses `session_unset()` and
`session_destroy()`.

## 1. Run it with WampServer

1. Start **WampServer** and wait until its icon turns **green**.
2. Copy the whole `cotedor` folder into `C:\wamp64\www\`, so you have `C:\wamp64\www\cotedor\index.php`.
3. Open phpMyAdmin (left-click the Wamp icon → **phpMyAdmin**, or the address from class, e.g.
   `http://localhost/phpmyadmin5.2.3/`), log in with user `root` and an empty password.
4. Click **Import**, choose `hotel_db.sql` from the project folder, and click **Import** (or **Go**) at the bottom.
   This creates the `hotel_db` database with all tables and sample data.

   **Already have `hotel_db` with your own rooms and photos?** Don't import `hotel_db.sql` again (it would replace them).
   Click `hotel_db` on the left → **Import** → choose `update_database.sql` → **Import**. It only adds the new `reviews` table
   and a few example stays for the guest account (a finished stay for "Rate your stay", and stays around 18 November for the
   dashboard's arrivals and departures). It's safe to import more than once.
5. Open **http://localhost/cotedor/** in your browser (this opens `index.php`).

> Open the site through `http://localhost/...`. Double-clicking a `.php` file will not work.

## 2. Test accounts

| Role  | Username | Password   | Opens                      |
|-------|----------|------------|----------------------------|
| Admin | `admin`  | `admin123` | Admin dashboard            |
| User  | `guest`  | `guest123` | My bookings, can book rooms |

You can also create new User accounts on the **Create account** page.

## 3. Photos and video

The site works without photos (it shows sand-coloured frames), but it looks its best with them.

**Quick way:** double-click **`download-photos.bat`** in the project folder (internet needed). It downloads
31 free Pexels photos and the beach video into `images` and `video` with the right names. Photos that are
already in the folder are skipped, so it never replaces your own. The list of photos is in `CREDITS.md`.

**Photos from the official Côte d'Or Retreat website:** open cotedorretreat.com, right-click a photo →
**Save image as…**, and save it into the `images` folder using one of the names below (for example the real
pool as `pool.jpg`, the real rooms as `room-double.jpg`, `room-selfcatering.jpg`, `room-apartment.jpg`).
Do this **before** running `download-photos.bat`, or save over the downloaded file afterwards.
If a photo saves as `.webp`, just rename it to the `.jpg` name; it will still show.

To change any photo later, save a new one into the `images` folder with **exactly** the same name:

| File name | What to look for | Where it shows |
|---|---|---|
| `hero.jpg` | Wide shot of a tropical beach or bay | Top of home page, menu background |
| `intro-1.jpg` | Tall photo, person relaxing among palms / terrace | Home – "Since 2025" collage (big) |
| `intro-2.jpg` | Aerial shot of waves on a beach | Home collage (middle) |
| `intro-3.jpg` | Palm trees by the sea | Home collage (small) |
| `strip-1.jpg` | Granite rocks on a Seychelles beach | Home – "An island escape" strip |
| `strip-2.jpg` | Tropical breakfast with fruit | Home strip |
| `strip-3.jpg` | Turquoise bay with white sand | Home strip |
| `strip-4.jpg` | Pool with palm trees | Home strip |
| `room-double.jpg` | Bright hotel bedroom, double bed | Double Room, menu "Rooms" |
| `room-selfcatering.jpg` | Hotel room with small kitchen | Self-Catering room |
| `room-apartment.jpg` | Apartment living room | Two-Bedroom Apartment |
| `spa.jpg` | Spa / massage room | Spa banner, Experiences |
| `restaurant.jpg` | Restaurant table by the sea | Dining section |
| `story-1.jpg` | Cocktail at a bar | Dining section (small) |
| `story-2.jpg` | Spa details – flowers, stones, oils | Experiences – spa |
| `pool.jpg` | Hotel swimming pool | Carousel, Booking page, Log in |
| `beach.jpg` | Calm beach with soft sand | Location, Contact, Register |
| `moment-1.jpg` | Person swimming in clear water | Carousel slide 1 |
| `moment-2.jpg` | Palm forest (search "Vallée de Mai" or "palm forest") | Carousel slide 3 |
| `moment-3.jpg` | Sunset over the ocean | Carousel slide 4 |
| `vallee-de-mai.jpg` | Palm forest / coco de mer | Experiences – Explore |
| `anse-lazio.jpg` | Beach with granite boulders (search "Anse Lazio" or "Praslin") | Experiences – Explore |
| `room-double-2.jpg` … `-4.jpg` | Double Room: bathroom, terrace with hammock, view | Room photo gallery |
| `room-selfcatering-2.jpg` … `-4.jpg` | Self-Catering: kitchenette, bathtub, terrace | Room photo gallery |
| `room-apartment-2.jpg` … `-4.jpg` | Apartment: second bedroom, kitchen, balcony | Room photo gallery |

Useful searches: "Seychelles", "Praslin", "La Digue", "tropical beach", "luxury hotel room", "spa".

**Top-of-page video:** download a free beach or ocean video from **pexels.com/videos** (search "tropical beach
drone"), choose the HD (1280 or 1920 wide) size, and save it as `video/hero.mp4`. Keep it under about 15 MB.
If there is no video, `hero.jpg` shows instead.

## 4. Things to update

  hotel's real details in `includes/header.php` (menu) and `includes/footer.php`, and in the three `.html` pages
  (they have their own copy of the menu and footer). Use VS Code's **Find in Files** (Ctrl + Shift + F) to change them all at once.
- **Room prices** come from the database.
- **Room photos:** in the admin area, open **Rooms → Photos** for a room, click **Choose file**, pick a photo from your computer, add a caption and click **Add to gallery**. To change a room's main photo, click **Edit** and choose a new photo.
- **Photo too big?** WampServer allows 2 MB per upload by default. To allow bigger photos: left-click the Wamp icon → **PHP** → **PHP settings** → set **upload_max_filesize** and **post_max_size** to 64M (or edit them in php.ini) and restart Wamp.
- **Instagram and Facebook links** in the footer point to the main Instagram and Facebook pages for now. Change them to the hotel's own pages in `includes/footer.php`, `contact.html` and `experiences.html`. Change them on the admin **Rooms** page.

## 5. Before you submit

1. Test everything on localhost.
2. In phpMyAdmin, select `hotel_db` → **Export** → **Go**, and save the file as `hotel_db.sql`
   (this is the `.sql` file you submit, with your latest data).
3. Upload the folder to GitHub.

## Files

**HTML** is used for pages that only show information. **PHP** is used only where the exam needs
server-side work: processing forms, the database, login, sessions and protected pages.


| File | What it does |
|---|---|
| `includes/db_connect.php` | Connects to MySQL with MySQLi (required by every page that uses the database) |
| `includes/header.php`, `includes/footer.php` | Shared navbar, full-screen menu and footer (included on every PHP page) |
| `includes/auth_check.php` | Protects pages that need a logged-in user |
| `includes/admin_check.php` | Protects admin pages (Admin role only) |
| `includes/admin_nav.php` | The heading at the top of every admin page (title, subtitle, buttons) |
| `includes/admin_sidebar.php` | The admin layout: sidebar menu, top bar with search, New booking, notifications and profile menu (loaded by `header.php` on admin pages) |
| `includes/admin_icons.php` | `adminIcon()` – the small line icons of the admin area (SVG), and `timeAgo()` ("5 min ago") |
| `admin_settings.php` | Settings: meal prices, check-in/out times and contact details, saved in the `settings` table and used across the site |
| `admin_guest.php` | Guest profile (`?id=2`): contact details, VIP tick and private notes (`guest_notes` table), all stays, totals and reviews |
| `admin_reports.php` | Reports for a year (`?year=2026`): revenue, nights sold, occupancy and average price per month and per room type, Excel (CSV) download |
| `admin_blocks.php` | Block dates: close rooms for repairs (`room_blocks` table). Closed rooms can't be booked and show on the calendar |
| `includes/room_modal.php` | The room window with the photo gallery (included on the Rooms and Booking pages) |
| `index.php` | Home page: quick booking bar, rooms from the database, approved guest reviews |
| `gallery.php` | Photo gallery: hotel photos + every room photo from the database, filters, full-screen viewer |
| `review.php` | A guest rates a finished stay (stars, title, review) → `reviews` table |
| `admin_reviews.php` | Approve, hide or delete guest reviews |
| `update_database.sql` | Adds the newer tables (`reviews`, `settings`, `guest_notes`, `message_status`, `room_blocks`) and example data to an existing `hotel_db` without deleting anything |
| `admin_calendar.php` | Occupancy calendar: rooms × days for a month (`?month=2026-11`) |
| `admin_booking_form.php` | Admin creates or edits a booking (phone, WhatsApp and walk-in guests, can create a new guest) |
| `includes/booking_actions.php` | The confirm / check in / check out / no-show / cancel / delete buttons, shared by the dashboard and Bookings page |
| `policies.html` | Hotel policies: check-in and check-out, cancellation, rates and taxes, rooms (plain HTML) |
| `experiences.html` | Spa, dining, pool and places to explore (plain HTML) |
| `contact.html` | Contact form (HTML form, sent to `contact.php`) |
| `contact.php` | Processes the contact form: saves it in the `messages` table and shows a success or error message |
| `subscribe.php` | Processes the footer newsletter form: saves it in the `subscribers` table and shows a message |
| `rooms.php` | Rooms read from the database, GET filter and sort, details modals |
| `register.php`, `login.php`, `logout.php` | Accounts, login with sessions, logout |
| `booking.php` | Booking form (login required, POST → `bookings` table) |
| `my_bookings.php` | A guest's own bookings, cancel a pending booking |
| `booking_confirmation.php` | The thank-you page after booking: reference, summary, countdown, print |
| `includes/functions.php` | `bookingReference()` (CDR-2026-0003), `bookingReturnUrl()` (back to booking after login) and `showStars()` (★★★★☆) |
| `account.php` | A guest's own details and password change |
| `admin_dashboard.php` | Overview: KPI cards with mini charts, Today/Week/Month, quick actions, today's arrivals and departures, live timeline, revenue chart, booking status donut, room popularity, guest rating, latest bookings |
| `admin_bookings.php` | All bookings: search, filter by status, confirm, check in/out, no-show, cancel, edit, delete |
| `admin_rooms.php` | Add, edit and delete rooms |
| `admin_photos.php` | Upload and remove the extra gallery photos of a room |
| `includes/upload_photo.php` | The `uploadPhoto()` function used by the admin pages to save uploaded photos |
| `admin_users.php` | All registered accounts, reset a guest's password |
| `admin_messages.php` | Messages inbox: New / Read / Replied (`message_status` table), reply by email, newsletter subscribers |
| `css/style.css` | The guest website's styles (dark design: charcoal, cream, terracotta) |
| `css/admin.css` | The admin dashboard's styles (the last section gives it the same fonts and colours) |
| `fonts/` | Bodoni Moda (headlines) and Jost (text), saved in the project so they work without internet |
| `setup_admin.php` | Run once to create the admin account (password stored with `password_hash()`), then delete it |
| `js/script.js` | All JavaScript functions |
| `css/bootstrap.min.css`, `js/bootstrap.bundle.min.js` | Bootstrap 5.3.8 (local copy, so it works without internet) |
| `images/logo-mark.png`, `logo-mark-white.png`, `logo-full.png`, `favicon.png` | The hotel's logo: colour emblem, white emblem (over photos), full logo (printouts) and browser-tab icon |
| `hotel_db.sql` | Database: tables and sample data |
