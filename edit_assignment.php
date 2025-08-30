<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

include 'connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch current assignment
$stmt = $conn->prepare("SELECT * FROM teacher_sections WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();

if (!$assignment) {
    echo "Assignment not found.";
    exit;
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_name = $_POST['teacher_name'];
    $section = $_POST['section'];

    $update = $conn->prepare("UPDATE teacher_sections SET teacher_name = ?, section = ? WHERE id = ?");
    $update->bind_param("ssi", $teacher_name, $section, $id);
    if ($update->execute()) {
        header("Location: organize_sections.php");
        exit;
    } else {
        echo "Error updating assignment.";
    }
}
// Get teachers for dropdown
$teachers = $conn->query("SELECT id, name FROM teacher");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Assignment</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 1em;
            color: #007bff;
        }
        label {
            display: block;
            margin: 0.5em 0 0.3em;
        }
        select, input[type="text"] {
            width: 100%;
            padding: 0.6em;
            margin-bottom: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.7em 1.5em;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Assignment</h2>
    <form method="post">
        <label for="teacher_id">Teacher:</label>
        <select name="teacher_id" required>
            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                <option value="<?= $teacher['name'] ?>" <?= $teacher['name'] == $assignment['teacher_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($teacher['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="section">Section:</label>
        <input type="text" name="section" value="<?= htmlspecialchars($assignment['section']) ?>" required>

        <button type="submit">Update Assignment</button>
    </form>
</div>
</body>
</html>
