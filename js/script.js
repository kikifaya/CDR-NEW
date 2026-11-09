function openMenu() {
    document.getElementById("menuOverlay").classList.add("open");
    document.body.style.overflow = "hidden";
}

function closeMenu() {
    let menu = document.getElementById("menuOverlay");

    if (menu === null) {
        return;
    }

    menu.classList.remove("open");
    document.body.style.overflow = "";
}

function changeMenuBackground(link) {
    let image = link.getAttribute("data-image");
    document.getElementById("menuBg").style.backgroundImage = "url('images/" + image + "')";
    link.classList.add("active-link");
}

function resetMenuBackground(link) {
    document.getElementById("menuBg").style.backgroundImage = "url('images/hero.jpg')";
    link.classList.remove("active-link");
}

function updateNavbar() {
    let nav = document.getElementById("siteNav");

    // Admin pages have a sidebar instead of the navbar
    if (nav === null || nav.classList.contains("nav-solid")) {
        return;
    }

    if (window.scrollY > 60) {
        nav.classList.add("scrolled");
    } else {
        nav.classList.remove("scrolled");
    }
}

function hideMissingImages() {
    let images = document.querySelectorAll("img");

    for (let i = 0; i < images.length; i++) {
        if (images[i].complete && images[i].naturalWidth === 0) {
            images[i].classList.add("img-missing");
        }
        images[i].addEventListener("error", function () {
            this.classList.add("img-missing");
        });
    }
}

function showErrors(boxId, errors) {
    let box = document.getElementById(boxId);
    let list = "";

    for (let i = 0; i < errors.length; i++) {
        list += "<li>" + errors[i] + "</li>";
    }

    box.innerHTML = '<div class="alert alert-danger"><strong>Please check the form:</strong><ul class="mb-0">' + list + "</ul></div>";
    box.scrollIntoView({ behavior: "smooth", block: "center" });
}

function isValidEmail(email) {
    let pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

function getMealPrice(mealPlan) {
    // The prices come from the Settings page: each meal option has a data-price
    let option = document.querySelector('#meal_plan option[value="' + mealPlan + '"]');
    if (option && option.getAttribute("data-price") !== null) {
        return Number(option.getAttribute("data-price"));
    }

    switch (mealPlan) {
        case "Breakfast":
            return 22;
        case "Half board":
            return 78;
        default:
            return 0;
    }
}

function countNights(checkIn, checkOut) {
    if (checkIn === "" || checkOut === "") {
        return 0;
    }

    let start = new Date(checkIn);
    let end = new Date(checkOut);
    let nights = (end - start) / (1000 * 60 * 60 * 24);

    return nights > 0 ? nights : 0;
}

function updateCheckoutMin() {
    let checkIn = document.getElementById("check_in").value;
    let checkOutField = document.getElementById("check_out");

    if (checkIn === "") {
        return;
    }

    let nextDay = new Date(checkIn);
    nextDay.setDate(nextDay.getDate() + 1);
    let minDate = nextDay.toISOString().split("T")[0];

    checkOutField.min = minDate;

    if (checkOutField.value !== "" && checkOutField.value < minDate) {
        checkOutField.value = "";
    }
}

function calculateTotal() {
    let roomSelect = document.getElementById("room_id");
    let selected = roomSelect.options[roomSelect.selectedIndex];
    let price = Number(selected.getAttribute("data-price")) || 0;
    let nights = countNights(document.getElementById("check_in").value, document.getElementById("check_out").value);
    let guests = Number(document.getElementById("guests").value) || 0;
    let mealPlan = document.getElementById("meal_plan").value;
    let mealPrice = getMealPrice(mealPlan);

    let roomCost = nights * price;
    let mealCost = nights * guests * mealPrice;
    let total = roomCost + mealCost;

    document.getElementById("summaryRoom").textContent = roomSelect.value === "" ? "Not selected" : selected.text;
    document.getElementById("summaryNights").textContent = nights;
    document.getElementById("summaryGuests").textContent = guests;
    document.getElementById("summaryRoomCost").textContent = "$" + roomCost.toFixed(2);
    document.getElementById("summaryMeals").textContent = mealPlan + " ($" + mealCost.toFixed(2) + ")";
    document.getElementById("summaryTotal").textContent = "$" + total.toFixed(2);

    // the bar at the bottom of the screen on phones shows the same total
    let mobileTotal = document.getElementById("mobileTotal");
    if (mobileTotal) {
        mobileTotal.textContent = "$" + total.toFixed(2);
    }

    updateProgress();
    return total;
}

function updateQuickCheckout() {
    let checkIn = document.getElementById("qb_check_in").value;
    let checkOutField = document.getElementById("qb_check_out");

    if (checkIn !== "") {
        let nextDay = new Date(checkIn);
        nextDay.setDate(nextDay.getDate() + 1);
        let minDate = nextDay.toISOString().split("T")[0];

        checkOutField.min = minDate;

        if (checkOutField.value === "" || checkOutField.value < minDate) {
            checkOutField.value = minDate;
        }
    }

    showQuickNights();
}

function showQuickNights() {
    let nights = countNights(document.getElementById("qb_check_in").value, document.getElementById("qb_check_out").value);
    let label = document.getElementById("qbNights");

    if (nights > 0) {
        label.textContent = nights + (nights === 1 ? " night" : " nights");
    } else {
        label.textContent = "";
    }

    document.getElementById("qbError").textContent = "";
    showQuickDates();
}

// Shows the chosen dates in words, e.g. "12 Dec 2026" with "Friday" underneath
function showQuickDates() {
    let fields = [
        ["qb_check_in", "qbCheckInText", "qbCheckInSub"],
        ["qb_check_out", "qbCheckOutText", "qbCheckOutSub"]
    ];

    for (let i = 0; i < fields.length; i++) {
        let value = document.getElementById(fields[i][0]).value;
        let text = document.getElementById(fields[i][1]);
        let sub = document.getElementById(fields[i][2]);

        if (value === "") {
            text.innerHTML = '<span class="qb-empty">Add date</span>';
            sub.textContent = sub.getAttribute("data-default") || sub.textContent;
        } else {
            if (!sub.getAttribute("data-default")) {
                sub.setAttribute("data-default", sub.textContent);
            }
            let date = new Date(value + "T00:00:00");
            text.textContent = date.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
            sub.textContent = date.toLocaleDateString("en-GB", { weekday: "long" });
        }
    }
}

// Opens the calendar when the guest clicks anywhere in a date field
function openDatePicker(fieldId) {
    let field = document.getElementById(fieldId);

    try {
        field.showPicker();
    } catch (error) {
        field.focus();
    }
}

// The - and + buttons next to the number of guests (1 to 4)
function changeGuests(step) {
    let field = document.getElementById("qb_guests");
    let guests = Number(field.value) + step;

    if (guests < 1) {
        guests = 1;
    } else if (guests > 4) {
        guests = 4;
    }

    field.value = guests;
    document.getElementById("qbGuestsText").textContent = guests + (guests === 1 ? " Guest" : " Guests");
    document.getElementById("qbLess").disabled = (guests === 1);
    document.getElementById("qbMore").disabled = (guests === 4);
}

function validateQuickBook(event) {
    let checkIn = document.getElementById("qb_check_in").value;
    let checkOut = document.getElementById("qb_check_out").value;
    let today = new Date().toISOString().split("T")[0];
    let error = "";

    if (checkIn === "" || checkOut === "") {
        error = "Please choose your check-in and check-out dates.";
    } else if (checkIn < today) {
        error = "The check-in date can't be in the past.";
    } else if (checkOut <= checkIn) {
        error = "The check-out date must be after the check-in date.";
    }

    if (error !== "") {
        event.preventDefault();
        document.getElementById("qbError").textContent = error;
        return false;
    }

    return true;
}

function toggleNewGuest() {
    let guestSelect = document.getElementById("user_id");
    let fields = document.querySelectorAll(".new-guest-field");
    let isNew = guestSelect.value === "new";

    for (let i = 0; i < fields.length; i++) {
        fields[i].style.display = isNew ? "" : "none";
    }

    document.getElementById("new_name").required = isNew;
    document.getElementById("new_email").required = isNew;

    // Fill in the phone number of an existing guest
    let selected = guestSelect.options[guestSelect.selectedIndex];
    let phoneField = document.getElementById("phone");
    if (selected.getAttribute("data-phone") && phoneField.value === "") {
        phoneField.value = selected.getAttribute("data-phone");
    }
}

function showRoomInfo() {
    let roomSelect = document.getElementById("room_id");
    let selected = roomSelect.options[roomSelect.selectedIndex];
    let info = document.getElementById("roomInfo");

    if (roomSelect.value === "") {
        info.textContent = "";
    } else {
        info.textContent = "You selected the " + selected.text + ", for up to " + selected.getAttribute("data-guests") + " guests.";
    }

    highlightRoomCard();
    calculateTotal();
}

function selectRoom(roomId) {
    document.getElementById("room_id").value = roomId;
    showRoomInfo();

    let panel = document.getElementById("bookingPanel");
    let top = panel.getBoundingClientRect().top + window.scrollY - 110;
    window.scrollTo({ top: top, behavior: "smooth" });
}

function selectRoomFromModal(roomId, button) {
    let modalElement = button.closest(".modal");

    modalElement.addEventListener("hidden.bs.modal", function () {
        selectRoom(roomId);
    }, { once: true });

    bootstrap.Modal.getInstance(modalElement).hide();
}

function setupGalleries() {
    let galleries = document.querySelectorAll(".gallery");

    for (let i = 0; i < galleries.length; i++) {
        galleries[i].addEventListener("slide.bs.carousel", function (event) {
            let thumbs = document.querySelectorAll('[data-gallery="' + this.id + '"]');

            for (let j = 0; j < thumbs.length; j++) {
                if (j === event.to) {
                    thumbs[j].classList.add("active");
                } else {
                    thumbs[j].classList.remove("active");
                }
            }
        });
    }
}

function highlightRoomCard() {
    let roomId = document.getElementById("room_id").value;
    let cards = document.querySelectorAll(".booking-room");

    for (let i = 0; i < cards.length; i++) {
        let button = cards[i].querySelector(".select-room-btn");

        if (cards[i].id === "roomCard" + roomId) {
            cards[i].classList.add("selected");
            button.textContent = "Selected";
        } else {
            cards[i].classList.remove("selected");
            button.textContent = "Select this room";
        }
    }
}

function updateProgress() {
    let fields = ["room_id", "check_in", "check_out", "guests", "meal_plan", "phone"];
    let filled = 0;

    for (let i = 0; i < fields.length; i++) {
        if (document.getElementById(fields[i]).value.trim() !== "") {
            filled++;
        }
    }

    let percent = Math.round((filled / fields.length) * 100);
    let bar = document.getElementById("bookingProgress");
    bar.style.width = percent + "%";
    bar.parentElement.setAttribute("aria-valuenow", percent);
    document.getElementById("progressText").textContent = percent + "%";
}

function validateBooking(event) {
    let errors = [];
    let roomSelect = document.getElementById("room_id");
    let checkIn = document.getElementById("check_in").value;
    let checkOut = document.getElementById("check_out").value;
    let guests = Number(document.getElementById("guests").value);
    let phone = document.getElementById("phone").value.trim();
    let today = new Date().toISOString().split("T")[0];

    if (roomSelect.value === "") {
        errors.push("Please choose a room.");
    }

    if (checkIn === "") {
        errors.push("Please choose a check-in date.");
    } else if (checkIn < today) {
        errors.push("The check-in date can't be in the past.");
    }

    if (checkOut === "") {
        errors.push("Please choose a check-out date.");
    } else if (checkOut <= checkIn) {
        errors.push("The check-out date must be after the check-in date.");
    }

    if (roomSelect.value !== "") {
        let maxGuests = Number(roomSelect.options[roomSelect.selectedIndex].getAttribute("data-guests"));
        if (guests < 1 || guests > maxGuests) {
            errors.push("This room allows 1 to " + maxGuests + " guests.");
        }
    }

    if (!/^[0-9+\s]{7,20}$/.test(phone)) {
        errors.push("Please enter a valid phone number (numbers, spaces and + only).");
    }

    if (!document.getElementById("agree_policies").checked) {
        errors.push("Please tick the box to agree to the hotel policies.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function validateRegister(event) {
    let errors = [];
    let fullName = document.getElementById("full_name").value.trim();
    let email = document.getElementById("email").value.trim();
    let username = document.getElementById("username").value.trim();
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirm_password").value;

    if (fullName === "") {
        errors.push("Please enter your full name.");
    }

    if (!isValidEmail(email)) {
        errors.push("Please enter a valid email address.");
    }

    if (!/^[A-Za-z0-9_]{4,20}$/.test(username)) {
        errors.push("The username must be 4 to 20 letters, numbers or underscores.");
    }

    if (password.length < 6) {
        errors.push("The password must be at least 6 characters long.");
    } else if (password !== confirmPassword) {
        errors.push("The two passwords don't match.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function validateContact(event) {
    let errors = [];
    let name = document.getElementById("name").value.trim();
    let email = document.getElementById("email").value.trim();
    let subject = document.getElementById("subject").value;
    let message = document.getElementById("message").value.trim();

    if (name === "") {
        errors.push("Please enter your name.");
    }

    if (!isValidEmail(email)) {
        errors.push("Please enter a valid email address.");
    }

    if (subject === "") {
        errors.push("Please choose a subject.");
    }

    if (message.length < 10) {
        errors.push("Your message must be at least 10 characters long.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function showRatingText(rating) {
    let words = ["", "Poor", "Fair", "Good", "Very good", "Excellent"];
    document.getElementById("ratingText").textContent = rating + " out of 5 – " + words[rating];
}

function countCharacters() {
    let length = document.getElementById("comment").value.length;
    document.getElementById("charCount").textContent = length + " / 1000";
}

function validateReview(event) {
    let errors = [];
    let rating = document.querySelector('input[name="rating"]:checked');
    let title = document.getElementById("title").value.trim();
    let comment = document.getElementById("comment").value.trim();

    if (rating === null) {
        errors.push("Please choose a rating from 1 to 5 stars.");
    }

    if (title.length < 3) {
        errors.push("Please give your review a title (at least 3 characters).");
    }

    if (comment.length < 20) {
        errors.push("Please write at least 20 characters about your stay.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function openResetPassword(userId, button) {
    document.getElementById("reset_user_id").value = userId;
    document.getElementById("resetName").textContent = button.getAttribute("data-name");
    document.getElementById("new_password").value = "";
    document.getElementById("confirm_new_password").value = "";
    document.getElementById("resetErrors").innerHTML = "";

    bootstrap.Modal.getOrCreateInstance(document.getElementById("resetPasswordModal")).show();
}

function makePassword() {
    let letters = "abcdefghjkmnpqrstuvwxyz23456789";
    let password = "";

    for (let i = 0; i < 8; i++) {
        password += letters.charAt(Math.floor(Math.random() * letters.length));
    }

    document.getElementById("new_password").value = password;
    document.getElementById("confirm_new_password").value = password;
}

function validateResetPassword(event) {
    let errors = [];
    let password = document.getElementById("new_password").value;
    let confirmPassword = document.getElementById("confirm_new_password").value;

    if (password.length < 6) {
        errors.push("The new password must be at least 6 characters long.");
    } else if (password !== confirmPassword) {
        errors.push("The two passwords don't match.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        document.getElementById("resetErrors").innerHTML = '<div class="alert alert-danger">' + errors.join("<br>") + "</div>";
        return false;
    }

    return true;
}

function validateSettings(event) {
    let errors = [];
    let breakfast = document.getElementById("breakfast_price").value;
    let halfBoard = document.getElementById("halfboard_price").value;
    let email = document.getElementById("hotel_email").value.trim();

    if (breakfast === "" || Number(breakfast) < 0 || Number(breakfast) > 500) {
        errors.push("The breakfast price must be a number from 0 to 500.");
    }

    if (halfBoard === "" || Number(halfBoard) < 0 || Number(halfBoard) > 500) {
        errors.push("The half board price must be a number from 0 to 500.");
    }

    if (!isValidEmail(email)) {
        errors.push("Please enter a valid email address.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function validateBlock(event) {
    let errors = [];
    let roomSelect = document.getElementById("room_id");
    let start = document.getElementById("check_in").value;
    let end = document.getElementById("check_out").value;
    let count = Number(document.getElementById("rooms_blocked").value);
    let reason = document.getElementById("reason").value.trim();

    if (roomSelect.value === "") {
        errors.push("Please choose a room type.");
    } else {
        let total = Number(roomSelect.options[roomSelect.selectedIndex].getAttribute("data-total"));
        if (count < 1 || count > total) {
            errors.push("You can close 1 to " + total + " rooms of this type.");
        }
    }

    if (start === "" || end === "") {
        errors.push("Please choose the dates.");
    } else if (end <= start) {
        errors.push("The room must open again after the first closed night.");
    }

    if (reason === "") {
        errors.push("Please write a short reason.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function validatePasswordChange(event) {
    let errors = [];
    let currentPassword = document.getElementById("current_password").value;
    let newPassword = document.getElementById("new_password").value;
    let confirmPassword = document.getElementById("confirm_password").value;

    if (currentPassword === "") {
        errors.push("Please enter your current password.");
    }

    if (newPassword.length < 6) {
        errors.push("The new password must be at least 6 characters long.");
    } else if (newPassword !== confirmPassword) {
        errors.push("The two new passwords don't match.");
    }

    if (errors.length > 0) {
        event.preventDefault();
        showErrors("formErrors", errors);
        return false;
    }

    return true;
}

function previewPhoto(input, previewId) {
    let preview = document.getElementById(previewId);

    if (input.files.length > 0) {
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove("img-missing");
    }
}

function showCountdown() {
    let countdown = document.getElementById("countdown");
    let checkIn = new Date(countdown.getAttribute("data-checkin") + "T00:00:00");
    let today = new Date();
    today.setHours(0, 0, 0, 0);

    let days = Math.round((checkIn - today) / (1000 * 60 * 60 * 24));

    if (days > 1) {
        countdown.innerHTML = "Your island escape begins in <strong>" + days + " days</strong>";
    } else if (days === 1) {
        countdown.innerHTML = "Your island escape begins <strong>tomorrow</strong>";
    } else if (days === 0) {
        countdown.innerHTML = "Your island escape begins <strong>today</strong>";
    } else {
        countdown.textContent = "We hope you enjoyed your stay";
    }
}

let galleryItems = [];
let galleryIndex = 0;

function filterGallery(category, button) {
    let items = document.querySelectorAll(".gallery-item");
    let buttons = document.querySelectorAll(".gallery-filters .btn");
    let shown = 0;

    for (let i = 0; i < items.length; i++) {
        if (category === "all" || items[i].getAttribute("data-category") === category) {
            items[i].classList.remove("is-hidden");
            shown++;
        } else {
            items[i].classList.add("is-hidden");
        }
    }

    for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove("active");
    }
    button.classList.add("active");

    document.getElementById("galleryCount").textContent = shown + (shown === 1 ? " photo" : " photos");
}

function openLightbox(item) {
    let items = document.querySelectorAll(".gallery-item");
    galleryItems = [];

    for (let i = 0; i < items.length; i++) {
        if (!items[i].classList.contains("is-hidden")) {
            galleryItems.push(items[i]);
        }
    }

    galleryIndex = galleryItems.indexOf(item);
    showLightboxPhoto();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("lightbox")).show();
}

function showLightboxPhoto() {
    let image = galleryItems[galleryIndex].querySelector("img");
    let lightboxImage = document.getElementById("lightboxImage");

    lightboxImage.classList.remove("is-shown", "img-missing");
    lightboxImage.src = image.getAttribute("src");
    lightboxImage.alt = image.alt;
    document.getElementById("lightboxCaption").textContent = image.alt;
    document.getElementById("lightboxCounter").textContent = (galleryIndex + 1) + " / " + galleryItems.length;

    window.requestAnimationFrame(function () {
        lightboxImage.classList.add("is-shown");
    });
}

function nextPhoto() {
    galleryIndex = (galleryIndex + 1) % galleryItems.length;
    showLightboxPhoto();
}

function prevPhoto() {
    galleryIndex = (galleryIndex - 1 + galleryItems.length) % galleryItems.length;
    showLightboxPhoto();
}

function togglePassword(fieldId, button) {
    let field = document.getElementById(fieldId);

    if (field.type === "password") {
        field.type = "text";
        button.textContent = "Hide";
    } else {
        field.type = "password";
        button.textContent = "Show";
    }
}

function showWelcome(name) {
    let hour = new Date().getHours();
    let greeting;

    if (hour < 12) {
        greeting = "Good morning";
    } else if (hour < 18) {
        greeting = "Good afternoon";
    } else {
        greeting = "Good evening";
    }

    document.getElementById("welcomeMessage").textContent = greeting + ", " + name + ". Welcome back to Côte d'Or Retreat.";
}

function splitWords(element) {
    let nodes = Array.from(element.childNodes);
    let count = 0;
    element.innerHTML = "";

    for (let i = 0; i < nodes.length; i++) {
        let node = nodes[i];

        if (node.nodeType === 3) {
            let parts = node.textContent.split(/(\s+)/);
            for (let j = 0; j < parts.length; j++) {
                if (parts[j].trim() === "") {
                    element.appendChild(document.createTextNode(parts[j]));
                } else {
                    let word = document.createElement("span");
                    word.className = "word";
                    word.textContent = parts[j];
                    word.style.transitionDelay = (count * 0.06) + "s";
                    element.appendChild(word);
                    count++;
                }
            }
        } else if (node.nodeName === "BR") {
            element.appendChild(node);
        } else {
            let word = document.createElement("span");
            word.className = "word";
            word.style.transitionDelay = (count * 0.06) + "s";
            word.appendChild(node);
            element.appendChild(word);
            count++;
        }
    }
}

function prepareAnimations() {
    let texts = document.querySelectorAll(".reveal-words");

    for (let i = 0; i < texts.length; i++) {
        splitWords(texts[i]);
    }
}

function revealOnScroll() {
    let items = document.querySelectorAll(".reveal, .reveal-words");

    for (let i = 0; i < items.length; i++) {
        let top = items[i].getBoundingClientRect().top;

        if (top < window.innerHeight * 0.88) {
            items[i].classList.add("is-visible");
        }
    }
}

function moveParallax() {
    let items = document.querySelectorAll("[data-speed]");
    let isSmallScreen = window.innerWidth < 992;

    for (let i = 0; i < items.length; i++) {
        let item = items[i];

        if (isSmallScreen && item.closest(".collage")) {
            item.style.transform = "";
            continue;
        }

        let box = item.parentElement.getBoundingClientRect();
        let distance = (box.top + box.height / 2) - (window.innerHeight / 2);
        let speed = Number(item.getAttribute("data-speed"));
        let extra = item.classList.contains("mark-year") ? "translateX(-50%) " : "";

        item.style.transform = extra + "translateY(" + (distance * speed).toFixed(1) + "px)";
    }
}

function spreadStack() {
    let stack = document.getElementById("experienceStack");

    if (!stack) {
        return;
    }

    let box = stack.getBoundingClientRect();
    let progress = (window.innerHeight - box.top) / (window.innerHeight * 0.8);
    progress = Math.min(Math.max(progress, 0), 1);

    let middle = stack.querySelector(".stack-middle");
    let back = stack.querySelector(".stack-back");
    let gap = window.innerWidth < 992 ? 45 : 60;

    middle.style.transform = "translateY(" + ((1 - progress) * gap) + "px)";
    back.style.transform = "translateY(" + ((1 - progress) * gap * 2) + "px)";
}

// Home page: the round photo window grows to full screen as you scroll.
// --p goes from 0 (top of the page) to 1 (window fully open) and the CSS uses it.
function openHeroWindow() {
    let hero = document.querySelector(".hero-scroll");

    if (!hero) {
        return;
    }

    let scrollable = hero.offsetHeight - window.innerHeight;
    let progress = -hero.getBoundingClientRect().top / scrollable;
    progress = Math.min(Math.max(progress, 0), 1);

    hero.style.setProperty("--p", progress.toFixed(3));

    if (progress > 0.75) {
        hero.classList.add("hero-open");
    } else {
        hero.classList.remove("hero-open");
    }
}

// Home page: the rooms are a stack of cards. When the next card slides up over a card,
// that card is pushed back (smaller and higher), and the background takes the colour
// of the room you are looking at.
function openRoomStack() {
    let stack = document.getElementById("roomStack");

    if (!stack) {
        return;
    }

    let cards = stack.querySelectorAll(".stack-card");
    let reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    for (let i = 0; i < cards.length; i++) {
        let stickyTop = parseFloat(window.getComputedStyle(cards[i]).top);
        let height = cards[i].offsetHeight;
        let depth = 0;

        // how far each later card has covered this one (0 = not at all, 1 = fully)
        for (let j = i + 1; j < cards.length; j++) {
            let nextTop = cards[j].getBoundingClientRect().top;
            let covered = (stickyTop + height - nextTop) / height;
            depth += Math.min(Math.max(covered, 0), 1);
        }

        if (reduceMotion) {
            cards[i].style.transform = "";
        } else {
            cards[i].style.transform = "translateY(" + (-depth * 34) + "px) scale(" + (1 - depth * 0.06) + ")";
        }
        cards[i].style.filter = "brightness(" + (1 - depth * 0.25) + ")";

        // the card in the middle of the screen chooses the background colour
        let box = cards[i].getBoundingClientRect();
        if (box.top < window.innerHeight * 0.6) {
            stack.style.backgroundColor = cards[i].getAttribute("data-colour");
        }
    }
}

// Home page: the "Moments to share" stories.
// Five frames stay still (two on each side of the middle one) and the photos wipe across them.
let stories = [];
let storyIndex = 0;
let storyTimer = null;
let storyBusy = false;
let storyTouchX = null;
const STORY_TIME = 6000;

function setupStories() {
    let list = document.getElementById("storyList");

    if (!list) {
        return;
    }

    let items = list.querySelectorAll(".story-item");

    for (let i = 0; i < items.length; i++) {
        stories.push(items[i]);
    }

    // one line for each story under the middle frame
    let progress = document.getElementById("storyProgress");
    for (let i = 0; i < stories.length; i++) {
        progress.appendChild(document.createElement("span"));
    }

    showStories("");

    // phones: swipe left or right on the stories
    let box = document.getElementById("stories");
    box.addEventListener("touchstart", function (event) {
        storyTouchX = event.touches[0].clientX;
    });
    box.addEventListener("touchend", function (event) {
        if (storyTouchX === null) {
            return;
        }
        let distance = event.changedTouches[0].clientX - storyTouchX;
        if (distance < -40) {
            changeStory(1);
        } else if (distance > 40) {
            changeStory(-1);
        }
        storyTouchX = null;
    });
}

// The story number that belongs in a frame (it wraps around: after the last comes the first)
function storyAt(offset) {
    let total = stories.length;
    return ((storyIndex + offset) % total + total) % total;
}

// Puts the right photo in every frame. direction is "next", "prev" or "" (no animation).
function showStories(direction) {
    let slots = document.querySelectorAll("#stories .story-slot");

    for (let i = 0; i < slots.length; i++) {
        let offset = Number(slots[i].getAttribute("data-offset"));
        let layer = document.createElement("div");
        layer.className = "story-layer";
        layer.innerHTML = stories[storyAt(offset)].innerHTML;

        if (direction !== "") {
            layer.classList.add("wipe-" + direction);
        }

        slots[i].appendChild(layer);

        // when the wipe has finished, remove the old photo underneath
        let oldLayers = slots[i].querySelectorAll(".story-layer");
        if (direction === "") {
            for (let j = 0; j < oldLayers.length - 1; j++) {
                oldLayers[j].remove();
            }
        } else {
            layer.addEventListener("animationend", function (event) {
                if (event.target !== layer) {
                    return;
                }
                for (let j = 0; j < oldLayers.length - 1; j++) {
                    oldLayers[j].remove();
                }
                layer.classList.remove("wipe-next", "wipe-prev");
                storyBusy = false;
            });
        }
    }

    updateStoryProgress();
    startStoryTimer();
}

// The lines under the middle frame: earlier stories full, the current one filling up
function updateStoryProgress() {
    let lines = document.querySelectorAll("#storyProgress span");

    for (let i = 0; i < lines.length; i++) {
        lines[i].classList.remove("active", "done");

        if (i < storyIndex) {
            lines[i].classList.add("done");
        }
    }

    // reading offsetWidth restarts the CSS animation of the current line
    void lines[storyIndex].offsetWidth;
    lines[storyIndex].classList.add("active");
}

// onclick of the round arrows (and the side frames): 1 = next story, -1 = previous story
function changeStory(direction) {
    if (storyBusy || stories.length === 0) {
        return;
    }

    let reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    storyBusy = !reduceMotion;
    storyIndex = storyAt(direction);

    if (reduceMotion) {
        showStories("");
    } else if (direction > 0) {
        showStories("next");
    } else {
        showStories("prev");
    }
}

// The stories move on by themselves every 6 seconds, but only while they are on screen
function startStoryTimer() {
    clearTimeout(storyTimer);

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    storyTimer = setTimeout(function () {
        let box = document.getElementById("stories").getBoundingClientRect();

        if (box.top < window.innerHeight && box.bottom > 0 && !document.hidden) {
            changeStory(1);
        } else {
            updateStoryProgress();
            startStoryTimer();
        }
    }, STORY_TIME);
}

// The label that follows the mouse over photos you can click (like the Belmond site).
// Elements with data-cursor="View room" show that text. The label trails behind the
// mouse a little and tilts in the direction it is moving.
let cursorX = 0;
let cursorY = 0;
let labelX = 0;
let labelY = 0;
let labelTilt = 0;

function setupCursorLabel() {
    let label = document.getElementById("cursorLabel");

    // only on computers with a mouse
    if (!label || !window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
        return;
    }

    document.addEventListener("mousemove", function (event) {
        cursorX = event.clientX;
        cursorY = event.clientY;

        let target = event.target.closest("[data-cursor]");
        if (target) {
            label.textContent = target.getAttribute("data-cursor");
            label.classList.add("is-visible");
        } else {
            label.classList.remove("is-visible");
        }
    });

    document.addEventListener("mouseleave", function () {
        label.classList.remove("is-visible");
    });

    moveCursorLabel();
}

function moveCursorLabel() {
    let label = document.getElementById("cursorLabel");
    let reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    // move 18% of the way to the mouse each frame, so the label glides after it
    let speed = reduceMotion ? 1 : 0.18;
    let stepX = (cursorX + 18 - labelX) * speed;
    labelX += stepX;
    labelY += (cursorY + 20 - labelY) * speed;

    // tilt with the sideways speed, but never more than 25 degrees
    let targetTilt = reduceMotion ? 0 : Math.max(-25, Math.min(25, stepX * 0.9));
    labelTilt += (targetTilt - labelTilt) * 0.2;

    label.style.transform = "translate(" + labelX.toFixed(1) + "px, " + labelY.toFixed(1) + "px) rotate(" + labelTilt.toFixed(1) + "deg)";

    window.requestAnimationFrame(moveCursorLabel);
}

// Phones: the "Review & book" button in the bottom bar scrolls to the form
function goToBookingForm() {
    let panel = document.getElementById("bookingPanel");
    let top = panel.getBoundingClientRect().top + window.scrollY - 90;
    window.scrollTo({ top: top, behavior: "smooth" });
    return false;
}

// The WhatsApp button appears once the guest starts scrolling
// (on pages without a big photo at the top it is there straight away)
function showWhatsapp() {
    let button = document.querySelector(".whatsapp-float");
    let nav = document.getElementById("siteNav");

    if (!button) {
        return;
    }

    if (window.scrollY > 500 || (nav && nav.classList.contains("nav-solid"))) {
        button.classList.add("show");
    } else {
        button.classList.remove("show");
    }
}

// Phones: stop the page behind the full-screen menu from scrolling
function setupPhoneMenu() {
    let menu = document.getElementById("mainNav");

    if (!menu) {
        return;
    }

    menu.addEventListener("show.bs.collapse", function () {
        document.body.classList.add("menu-open");
    });

    menu.addEventListener("hidden.bs.collapse", function () {
        document.body.classList.remove("menu-open");
    });
}

let animationWaiting = false;

function animateOnScroll() {
    if (animationWaiting) {
        return;
    }

    animationWaiting = true;
    window.requestAnimationFrame(function () {
        revealOnScroll();
        showWhatsapp();
        openRoomStack();

        if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            moveParallax();
            spreadStack();
            openHeroWindow();
        }

        animationWaiting = false;
    });
}

function leavePage(event) {
    let link = event.target.closest("a");

    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }

    let href = link.getAttribute("href");

    if (href === null || href.charAt(0) === "#" || href.indexOf("mailto:") === 0 || href.indexOf("tel:") === 0) {
        return;
    }

    if (link.target === "_blank" || link.hasAttribute("download") || link.hasAttribute("data-bs-toggle")) {
        return;
    }

    if (link.hostname !== window.location.hostname) {
        return;
    }

    if (link.pathname === window.location.pathname && link.hash !== "") {
        return;
    }

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    event.preventDefault();
    document.body.classList.add("page-leaving");

    setTimeout(function () {
        window.location.href = link.href;
    }, 320);
}

document.addEventListener("click", leavePage);

window.addEventListener("pageshow", function () {
    document.body.classList.remove("page-leaving");
});

window.addEventListener("scroll", updateNavbar);
window.addEventListener("scroll", animateOnScroll);
window.addEventListener("resize", animateOnScroll);

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeMenu();

        let phoneMenu = document.getElementById("mainNav");
        if (phoneMenu && phoneMenu.classList.contains("show")) {
            bootstrap.Collapse.getOrCreateInstance(phoneMenu).hide();
        }
    }

    let lightbox = document.getElementById("lightbox");
    if (lightbox && lightbox.classList.contains("show")) {
        if (event.key === "ArrowRight") {
            nextPhoto();
        } else if (event.key === "ArrowLeft") {
            prevPhoto();
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    updateNavbar();
    hideMissingImages();
    prepareAnimations();
    setupGalleries();
    setupStories();
    setupCursorLabel();

    if (document.getElementById("quickBook")) {
        showQuickNights();
        changeGuests(0);
    }
    setupPhoneMenu();
    animateOnScroll();

    if (document.getElementById("countdown")) {
        showCountdown();
    }

    let welcome = document.getElementById("welcomeMessage");
    if (welcome) {
        showWelcome(welcome.getAttribute("data-name"));
    }

    if (document.getElementById("bookingForm")) {
        showRoomInfo();
    }

    if (document.querySelector(".mobile-book-bar")) {
        document.body.classList.add("has-book-bar");
    }

    if (document.getElementById("user_id") && document.getElementById("new_name")) {
        toggleNewGuest();
    }

    if (document.getElementById("comment") && document.getElementById("charCount")) {
        countCharacters();

        let chosen = document.querySelector('input[name="rating"]:checked');
        if (chosen) {
            showRatingText(Number(chosen.value));
        }
    }
});
