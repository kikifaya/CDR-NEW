<?php
function uploadPhoto($file, $prefix)
{
    if ($file["error"] == UPLOAD_ERR_INI_SIZE || $file["error"] == UPLOAD_ERR_FORM_SIZE) {
        return ["", "The photo is too big. The largest size allowed is " . ini_get("upload_max_filesize") . "B."];
    }

    if ($file["error"] != UPLOAD_ERR_OK) {
        return ["", "The photo could not be uploaded. Please try again."];
    }

    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($extension, $allowedTypes)) {
        return ["", "Please choose a JPG, PNG or WEBP photo."];
    }

    if (getimagesize($file["tmp_name"]) === false) {
        return ["", "This file is not a photo."];
    }

    $fileName = $prefix . "-" . date("Ymd-His") . "-" . rand(100, 999) . "." . $extension;

    if (move_uploaded_file($file["tmp_name"], "images/" . $fileName)) {
        return [$fileName, ""];
    }

    return ["", "The photo could not be saved in the images folder."];
}
?>
