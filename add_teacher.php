<?php
include 'connect.php'; // Ensure this file properly defines $conn

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';

    if (!empty($name) && !empty($position)) {
        $stmt = $conn->prepare("INSERT INTO teacher (name, position) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $name, $position);

            if ($stmt->execute()) {
                header("Location: manage_teachers.php?success=1");
                exit;
            } else {
                echo "<script>alert('Execution Error: " . $stmt->error . "'); window.history.back();</script>";
            }

            $stmt->close();
        } else {
            echo "<script>alert('Statement Error: " . $conn->error . "'); window.history.back();</script>";
        }

        $conn->close();
    } else {
        echo "<script>alert('Please provide both name and position.'); window.history.back();</script>";
    }
}
?>
