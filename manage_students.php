<?php
// manage_students.php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

include 'connect.php';

$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$defaultTab = $editId ? 'list' : 'manage';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_student'])) {
        $id = intval($_POST['id']);
        $id_number = trim($_POST['id_number']);
        $name = trim($_POST['name']);
        $section = trim($_POST['section']);

        if (!empty($id_number) && !empty($name) && !empty($section)) {
            $stmt = $conn->prepare("UPDATE student SET id_number = ?, name = ?, section = ? WHERE id = ?");
            $stmt->bind_param("sssi", $id_number, $name, $section, $id);
            $stmt->execute();
            header("Location: manage_students.php?tab=list");
            exit;
        }
    }

    if (isset($_POST['delete_student'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM student WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: manage_students.php?tab=list");
        exit;
    }
}

// Fetch sections from teacher_sections table
$sections = [];
$sectionQuery = $conn->query("SELECT DISTINCT section FROM teacher_sections ORDER BY section ASC");
if ($sectionQuery && $sectionQuery->num_rows > 0) {
    while ($row = $sectionQuery->fetch_assoc()) {
        $sections[] = $row['section'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* --- same CSS as teacher management page --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f2f2;
            display: flex;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #2c3e50;
            padding-top: 20px;
            position: fixed;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar img {
            width: 100px;
            margin: 0 auto 20px;
            display: block;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: #34495e;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: #1abc9c;
        }

        .logout {
            background: #e74c3c;
        }

        .logout:hover {
            background: #c0392b;
        }

        .main-content {
            margin-left: 250px;
            width: 100%;
        
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #007bff;
            color: white;
            padding: 30px 20px;
        }

        .topbar-left h1 {
            font-size: 20px;
            white-space: nowrap;
        }

        .admin-info {
            font-size: 16px;
            font-weight: 500;
            margin-right: 30px;
        }

        .content {
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .tab-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .tab-button {
            padding: 15px 40px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 500;
            margin-top: -17px;
        }
        .tab-button.active {
            background-color: blue;
            color: white;
        }
        .tab-buttons.inactive {
            background-color: #ddd;
            color: black;
        }

        .tab-content {
            width: 100%;
            max-width: 800px;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2, h3 {
            margin-bottom: 10px;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .form-group select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .btn {
            
            padding: 8px 10px;
            border: none;
            background: #5F4DFF;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            
        }

        .btn:hover {
            background: #2980b9;
        }
        .big-btn {
            width: 100%;
            padding: 15px 20px;
            border: none;
            background: #5F4DFF;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 15px;
            font-size: 15px;
        }

        .big-btn:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #3498db;
            color: white;
        }
        .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }

  .modal {
    background: white;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    animation: fadeIn 0.3s ease;
  }

  .modal h2 {
    margin-bottom: 15px;
    color: #28a745;
  }

  .modal p {
    margin-bottom: 20px;
  }

  .modal button {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
  }

  .modal button:hover {
    background: #0056b3;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            table, th, td {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <img src="Image/ASASHS Logo.png" alt="School Logo">
    <div class="nav-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_teachers.php">Teachers</a>
        <a href="create_questionnaire.php">Questionnaire</a>
        <a href="assign_evaluation.php">Assign Evaluations</a>
        <a href="organize_sections.php">Sections</a>
        <a href="evaluation_results.php">Results</a>
        <a href="past_evaluations.php">Evaluation</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1>Evaluation System</h1>
        </div>
        <div class="admin-info">Administrator</div>
    </div>

    <div class="content">
        <div class="tab-buttons">
            <button class="tab-button <?php echo $defaultTab === 'manage' ? 'active' : ''; ?>" onclick="showTab('manage')">Insert</button>
            <button class="tab-button <?php echo $defaultTab === 'list' ? 'active' : ''; ?>" onclick="showTab('list')">Student List</button>
        </div>

        <div id="manage" class="tab-content" style="<?php echo $defaultTab === 'manage' ? '' : 'display:none;'; ?>">
            <h2>Manage Students</h2>
            <form method="post" action="add_student.php">
                <div class="form-group">
                    <label>ID Number:</label>
                    <input type="text" name="id_number" placeholder="0001" required>
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="name" placeholder="Last Name, First name, Middle Initial" required>
                </div>
                <div class="form-group">
                    <label>Section:</label>
                    <select name="section" required>
                        <option value="">-- Select Section --</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo htmlspecialchars($sec); ?>"><?php echo htmlspecialchars($sec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_student" class="big-btn">Add Student</button>
            </form>
        </div>

        <div id="list" class="tab-content" style="<?php echo $defaultTab === 'list' ? '' : 'display:none;'; ?>">
            <h3>Student List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Section</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM student ORDER BY id DESC");

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            if ($editId === intval($row['id'])) {
                                echo "<tr>
                                    <form method='POST'>
                                        <td><input type='text' name='id_number' value='" . htmlspecialchars($row['id_number']) . "' required></td>
                                        <td><input type='text' name='name' value='" . htmlspecialchars($row['name']) . "' required></td>
                                        <td><input type='text' name='section' value='" . htmlspecialchars($row['section']) . "' required></td>
                                        <td>
                                            <input type='hidden' name='id' value='" . $row['id'] . "'>
                                            <button type='submit' name='update_student' class='btn'>Save</button>
                                            <a href='manage_students.php?tab=list' class='btn'>Cancel</a>
                                        </td>
                                    </form>
                                </tr>";
                            } else {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                                echo "<td>
                                    <a href='manage_students.php?edit=" . $row['id'] . "' class='btn'>Edit</a>
                                    <form method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure you want to delete this student?');\">
                                        <input type='hidden' name='id' value='" . $row['id'] . "'>
                                        <button type='submit' name='delete_student' class='btn' style='background:#FF0004;'>Delete</button>
                                    </form>
                                </td>";
                                echo "</tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='4'>No students found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<div id="successModal" class="modal-overlay">
  <div class="modal">
    <h2>Success</h2>
    <p>Student added successfully!</p>
    <button onclick="closeModal()">Close</button>
  </div>
</div>
<?php endif; ?>

<script>
    function closeModal() {
    document.getElementById('successModal').style.display = 'none';
    history.replaceState(null, '', 'manage_students.php');
  }
    function showTab(tabId) {
        document.getElementById("manage").style.display = (tabId === "manage") ? "block" : "none";
        document.getElementById("list").style.display = (tabId === "list") ? "block" : "none";

        document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
        document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`).classList.add("active");
    }
</script>

</body>
</html>
