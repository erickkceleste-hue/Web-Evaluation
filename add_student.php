<?php
include 'connect.php'; // your DB connection file

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_number = trim($_POST['id_number']);
    $name = trim($_POST['name']);
    $section = trim($_POST['section']);

    if (!empty($id_number) && !empty($name) && !empty($section)) {
        // Check if ID already exists
        $check = $conn->prepare("SELECT id FROM student WHERE id_number = ?");
        $check->bind_param("s", $id_number);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('ID Number already exists!'); window.history.back();</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO student (id_number, name, section) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $id_number, $name, $section);

            if ($stmt->execute()) {
                header("Location: manage_students.php?success=1");
                exit;
            } else {
                echo "Execution Error: " . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
        $conn->close();
    } else {
        echo "<script>alert('Please provide all fields: ID Number, Name, and Section.'); window.history.back();</script>";
    }
}
?>