<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "user"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["id_number"], $_SESSION["name"])) {
    die("Session missing. Please log in again.");
}

$id_number = $_SESSION["id_number"];
$name = $_SESSION["name"];

// ✅ Fetch student section
$stmt = $conn->prepare("SELECT section FROM student WHERE id_number = ?");
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$section = $student ? $student['section'] : "";

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["teacher_name"])) {
    $teacher = $_POST["teacher_name"];
    $feedback = $_POST["feedback"];

    // Insert each answer into evaluation_answers
    foreach ($_POST as $key => $value) {
        if (strpos($key, "q") === 0) { // question inputs
            $question_id = substr($key, 1); // remove "q" prefix
            $answer = intval($value);

            $insert = $conn->prepare("INSERT INTO evaluation_answers 
                (id_number, name, section, teacher_name, question_id, answer, feedback, date_submitted) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $insert->bind_param("ssssiss", $id_number, $name, $section, $teacher, $question_id, $answer, $feedback);
            $insert->execute();
        }
    }

    header("Location: dashboard_student.php?message=Evaluation submitted successfully");
    exit;
} else {
    die("Invalid submission.");
}
?>
