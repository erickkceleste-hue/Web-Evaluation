<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

include 'connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id) {
    $stmt = $conn->prepare("DELETE FROM teacher_sections WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: organize_sections.php");
exit;
?>
