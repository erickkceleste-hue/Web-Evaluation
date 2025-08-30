<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "user");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$section = $_GET['section'] ?? '';
$response = [];

if (!empty($section)) {
    $stmt = $conn->prepare("SELECT teacher_name FROM teacher_sections WHERE section = ?");
    $stmt->bind_param("s", $section);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                'name' => $row['teacher_name']
            ];
        }
        echo json_encode($response);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Query failed"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "No section provided"]);
}


?>