-- =====================================================================
-- Côte d'Or Retreat - database update (November)
-- Import this in phpMyAdmin: click hotel_db on the left, then
-- Import > choose this file > Import.
-- It only ADDS things, so your rooms, photos, users and bookings stay.
-- It is safe to import more than once, and safe if you already
-- imported update_reviews.sql before.
-- =====================================================================

SET NAMES utf8mb4;

-- 1. Guest reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    rating INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

-- 2. Example stays for the test guest (guest / guest123), for the demo:
--    a finished stay in September (so "Rate your stay" shows),
--    a family checked in until 18 November (shows under Departures on 18/11),
--    and a couple arriving on 18 November (shows under Arrivals on 18/11).
INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, meal_plan, phone, special_request, total_price, status)
SELECT id, 2, '2026-09-14', '2026-09-18', 2, 'Breakfast', '+248 2 500 000', '', 1436.00, 'Confirmed'
FROM users WHERE username = 'guest'
AND NOT EXISTS (SELECT 1 FROM bookings WHERE bookings.user_id = users.id AND bookings.check_in = '2026-09-14');

INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, meal_plan, phone, special_request, total_price, status)
SELECT id, 3, '2026-11-15', '2026-11-18', 3, 'Half board', '+248 2 500 000', 'Family trip, late check-out if possible.', 1935.00, 'Checked in'
FROM users WHERE username = 'guest'
AND NOT EXISTS (SELECT 1 FROM bookings WHERE bookings.user_id = users.id AND bookings.check_in = '2026-11-15');

INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, meal_plan, phone, special_request, total_price, status)
SELECT id, 1, '2026-11-18', '2026-11-21', 2, 'Breakfast', '+248 2 500 000', 'Arriving on the afternoon ferry.', 1071.00, 'Confirmed'
FROM users WHERE username = 'guest'
AND NOT EXISTS (SELECT 1 FROM bookings WHERE bookings.user_id = users.id AND bookings.check_in = '2026-11-18');

-- 3. Settings page, guest notes, messages inbox and blocked dates
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guest_notes (
    user_id INT PRIMARY KEY,
    vip TINYINT NOT NULL DEFAULT 0,
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_status (
    message_id INT PRIMARY KEY,
    status VARCHAR(20) NOT NULL DEFAULT 'Read',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    rooms_blocked INT NOT NULL DEFAULT 1,
    reason VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('breakfast_price', '22'),
('halfboard_price', '78'),
('checkin_time', '12:00'),
('checkout_time', '10:00'),
('hotel_phone', '+248 423 5100'),
('hotel_whatsapp', '+248 2537 688'),
('hotel_email', 'cotedorretreat@seychelles.net');

INSERT INTO room_blocks (room_id, start_date, end_date, rooms_blocked, reason)
SELECT 3, '2026-11-24', '2026-11-28', 1, 'Bathroom renovation in apartment 4'
FROM rooms WHERE id = 3
AND NOT EXISTS (SELECT 1 FROM room_blocks WHERE room_id = 3 AND start_date = '2026-11-24');
