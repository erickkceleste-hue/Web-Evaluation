<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $teacher_name = trim($_POST["teacher_name"]);
    $section = trim($_POST["section"]);
    $subject = trim($_POST["subject"]);

    if (!empty($teacher_name) && !empty($section) && !empty($subject)) {
        // Check if the same section & subject is already assigned to the teacher
        $check = $conn->prepare("SELECT * FROM teacher_sections WHERE teacher_name = ? AND section = ? AND subject = ?");
        $check->bind_param("sss", $teacher_name, $section, $subject);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Section + subject already assigned
            header("Location: organize_sections.php?error=already_assigned");
            exit;
        }

        // Assign section & subject to teacher
        $stmt = $conn->prepare("INSERT INTO teacher_sections (teacher_name, section, subject) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $teacher_name, $section, $subject);

        if ($stmt->execute()) {
            header("Location: organize_sections.php?success=1");
            exit;
        } else {
            echo "Error: Could not assign section.";
        }
    } else {
        echo "Please provide teacher name, section, and subject.";
    }
}
?>
