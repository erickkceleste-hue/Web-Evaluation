<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $section = trim($_POST['section']);
    $teacher_name = trim($_POST['teacher_name']); // ✅ teacher_name is coming directly
    $day = trim($_POST['day']);

    // Validate input
    if (empty($section) || empty($teacher_name) || empty($day)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: assign_evaluation.php");
        exit;
    }

    // Check if assignment already exists
    $check = $conn->prepare("SELECT * FROM assigned_evaluations WHERE section = ?");
    $check->bind_param("s", $section);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Update existing assignment
        $update = $conn->prepare("UPDATE assigned_evaluations SET teacher_name = ?, day = ? WHERE section = ?");
        $update->bind_param("sss", $teacher_name, $day, $section);
        $update->execute();
    } else {
        // Insert new assignment
        $insert = $conn->prepare("INSERT INTO assigned_evaluations (teacher_name, section, day) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $teacher_name, $section, $day);
        $insert->execute();
    }

    header("Location: assign_evaluation.php?message=Saved successfully");
    exit;
}
?>
