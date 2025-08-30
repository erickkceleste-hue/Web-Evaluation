<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "user";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Copy all evaluation answers to past_evaluation
    $sql = "INSERT INTO past_evaluation 
            (teacher_name, section, question_id, answer, feedback, student_name, created_at)
            SELECT teacher_name, section, question_id, answer, feedback, student_name, NOW()
            FROM evaluation_answers";
    if (!$conn->query($sql)) {
        throw new Exception("Error moving data: " . $conn->error);
    }

    // 2. Clear current evaluations
    if (!$conn->query("TRUNCATE TABLE evaluation_answers")) {
        throw new Exception("Error clearing table: " . $conn->error);
    }

    // Commit
    $conn->commit();
    header("Location: evaluation_results.php?reset=success");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo "Reset failed: " . $e->getMessage();
}

$conn->close();
?>
