<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "", "user");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $section = trim($_POST['section']);
    $date = trim($_POST['date']); // <-- updated from day to date
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);

    if (!empty($section) && !empty($date) && !empty($start_time) && !empty($end_time)) {

        // Prevent duplicate assignments (same section, same date, overlapping time)
        $check = $conn->prepare("SELECT * FROM assigned_evaluations 
                                 WHERE section = ? AND date = ? 
                                 AND ((start_time <= ? AND end_time >= ?) 
                                 OR (start_time <= ? AND end_time >= ?))");
        $check->bind_param("ssssss", $section, $date, $start_time, $start_time, $end_time, $end_time);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Already assigned → send back with message
            header("Location: assign_evaluation.php?message=" . urlencode("This evaluation schedule already overlaps with an existing one."));
            exit;
        }

        // Insert new assignment
        $stmt = $conn->prepare("INSERT INTO assigned_evaluations (section, date, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $section, $date, $start_time, $end_time);

        if ($stmt->execute()) {
            header("Location: assign_evaluation.php?message=" . urlencode("Evaluation assigned successfully!"));
            exit;
        } else {
            header("Location: assign_evaluation.php?message=" . urlencode("Error: Could not assign evaluation."));
            exit;
        }
    } else {
        header("Location: assign_evaluation.php?message=" . urlencode("Please fill in all fields."));
        exit;
    }
} else {
    header("Location: assign_evaluation.php");
    exit;
}
?>
