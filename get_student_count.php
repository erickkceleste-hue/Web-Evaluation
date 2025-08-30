<?php
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "root", "", "user");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$section = $_GET['section'] ?? '';

if (!empty($section)) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM student WHERE section = ?");
    $stmt->bind_param("s", $section);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        echo json_encode(["total" => $data['total']]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Query failed"]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "No section provided"]);
    exit;
}
