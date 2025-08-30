<?php
session_start();
$response = ["success" => false];

if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === 0) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir);

    $ext = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid("profile_", true) . "." . $ext;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $filePath)) {
        // Optional: save to DB
        $_SESSION["profile_image"] = $filePath;

        $response["success"] = true;
        $response["imagePath"] = $filePath;
    } else {
        $response["error"] = "Failed to save file.";
    }
} else {
    $response["error"] = "No valid file uploaded.";
}

header('Content-Type: application/json');
echo json_encode($response);
