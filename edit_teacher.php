<?php
include 'connect.php';

// Handle Update if POSTed
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_name = trim($_POST['edit_name']);
    $edit_section = trim($_POST['edit_section']);

    if (!empty($edit_name) && !empty($edit_section)) {
        $stmt = $conn->prepare("UPDATE teacher SET name = ?, section = ? WHERE id = ?");
        $stmt->bind_param("ssi", $edit_name, $edit_section, $edit_id);
        $stmt->execute();
    }
}

// Handle Add New Teacher
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_teacher'])) {
    $name = trim($_POST['name']);
    $section = trim($_POST['section']);
    if (!empty($name) && !empty($section)) {
        $stmt = $conn->prepare("INSERT INTO teacher (name, section) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $section);
        $stmt->execute();
    }
}

// Fetch All Teachers
$teachers = $conn->query("SELECT * FROM teacher ORDER BY id DESC");
?>