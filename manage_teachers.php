<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_teacher'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        if (!empty($name) && !empty($position)) {
            $stmt = $conn->prepare("UPDATE teacher SET name = ?, position = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $position, $id);
            $stmt->execute();
            header("Location: manage_teachers.php?tab=list");
            exit;
        }
    }

    if (isset($_POST['delete_teacher'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM teacher WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: manage_teachers.php?tab=list");
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

// Determine default tab (based on URL or edit action)
$defaultTab = isset($_GET['edit']) || (isset($_GET['tab']) && $_GET['tab'] === 'list') ? 'list' : 'manage';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Teachers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            top: 0;
            left: 0;
            z-index: 998;
            transition: transform 0.3s ease;
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
        .topbar {
            position: fixed;
            padding: 30px 20px;
            top: 0;
            left: 250px;
            right: 0;
            background: #007bff;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }
        .topbar .title {
            font-size: 20px;
            font-weight: bold;
        }
        .topbar .admin {
            display: flex;
            align-items: center;
            margin-right: 30px;
            gap: 8px;
            font-size: 16px;
            font-weight: 500;
        }
        .topbar .admin i {
            font-size: 18px;
        }
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            flex-direction: column;
            cursor: pointer;
            z-index: 1001;
        }
        .menu-toggle span {
            height: 3px;
            width: 25px;
            background-color: #2c3e50;
            margin: 4px 0;
            transition: 0.4s;
        }
        .main-content {
            margin-left: 250px;
            padding: 100px 20px 30px 20px;
            width: 100%;
            transition: margin-left 0.3s ease;
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
        }
        .tab-button.active {
            background-color: blue;
            color: white;
        }
        .tab-button.inactive {
            background-color: #ddd;
            color: black;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
            width: 100%;
            margin: 0 auto 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2, h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        .btn {
            padding: 8px 10px;
            background: #5F4DFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 5px;
            
        }
        .btn:hover {
            background: #2980b9;
        }
        .big-btn {
            width: 100%;
            padding: 15px 20px;
            background: #5F4DFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 5px;
            font-size: 15px;
            margin-top: 15px;
        }
        .big-btn:hover {
            background: #2980b9;
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
            border: 1px solid #ccc;
            border-radius: 4px;
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
            body { flex-direction: column; }
            .sidebar {
                transform: translateX(-100%);
                position: absolute;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .topbar {
                left: 0;
            }
            .menu-toggle {
                display: flex;
            }
            .main-content {
                margin-left: 0;
                padding-top: 100px;
            }
        }
    </style>
</head>
<body>

<div class="menu-toggle" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</div>

<div class="sidebar" id="sidebar">
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

<div class="topbar">
    <div class="title">Evaluation System</div>
    <div class="admin">Administrator</div>
</div>

<div class="main-content">

    <!-- Tabs -->
    <div class="tab-buttons">
        <button class="tab-button <?php echo $defaultTab === 'manage' ? 'active' : 'inactive'; ?>" onclick="showTab('manage')">Insert</button>
        <button class="tab-button <?php echo $defaultTab === 'list' ? 'active' : 'inactive'; ?>" onclick="showTab('list')">Teacher List</button>
    </div>

    <!-- Manage Tab -->
    <div id="manage" class="tab-content" style="<?php echo $defaultTab === 'manage' ? '' : 'display:none;'; ?>">
        <div class="container">
            <h2>Manage Teachers</h2>
            <form method="POST" action="add_teacher.php">
                <div class="form-group">
                    <label for="name">Teacher Name:</label>
                    <input type="text" name="name" placeholder="Last Name, First name, Middle Initial" required>
                </div>
                <div class="form-group">
                    <label for="position">Position:</label>
                    <input type="text" name="position" placeholder="Master Teacher II" required>
                </div>
                <button type="submit" class="big-btn">Add Teacher</button>
            </form>
        </div>
    </div>

    <!-- Teacher List Tab -->
    <div id="list" class="tab-content" style="<?php echo $defaultTab === 'list' ? '' : 'display:none;'; ?>">
        <div class="container">
            <h3>Teacher List</h3>
            <table>
                <thead>
                    <tr><th>Name</th><th>Position</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
                    $result = $conn->query("SELECT * FROM teacher ORDER BY id DESC");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            if ($editId === intval($row['id'])) {
                                echo "<tr>
                                    <form method='POST'>
                                        <td><input type='text' name='name' value='" . htmlspecialchars($row['name']) . "' required></td>
                                        <td><input type='text' name='position' value='" . htmlspecialchars($row['position']) . "' required></td>
                                        <td>
                                            <input type='hidden' name='id' value='" . $row['id'] . "'>
                                            <button type='submit' name='update_teacher' class='btn'>Save</button>
                                            <a href='manage_teachers.php?tab=list' class='btn' style='background:#00aaff;'>Cancel</a>
                                        </td>
                                    </form>
                                </tr>";
                            } else {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['name']) . "</td>
                                    <td>" . htmlspecialchars($row['position']) . "</td>
                                    <td>
                                        <a href='manage_teachers.php?edit=" . $row['id'] . "' class='btn'>Edit</a>
                                        <form method='POST' style='display:inline;' onsubmit=\"return confirm('Delete this teacher?');\">
                                            <input type='hidden' name='id' value='" . $row['id'] . "'>
                                            <button type='submit' name='delete_teacher' class='btn' style='background:#e67e22;'>Delete</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        }
                    } else {
                        echo "<tr><td colspan='3'>No teachers found.</td></tr>";
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
    history.replaceState(null, '', 'manage_teachers.php');
  }
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
    }

    function showTab(tab) {
        const manageTab = document.getElementById("manage");
        const listTab = document.getElementById("list");
        const buttons = document.querySelectorAll(".tab-button");

        if (tab === "manage") {
            manageTab.style.display = "block";
            listTab.style.display = "none";
            buttons[0].classList.add("active");
            buttons[0].classList.remove("inactive");
            buttons[1].classList.remove("active");
            buttons[1].classList.add("inactive");
        } else {
            manageTab.style.display = "none";
            listTab.style.display = "block";
            buttons[1].classList.add("active");
            buttons[1].classList.remove("inactive");
            buttons[0].classList.remove("active");
            buttons[0].classList.add("inactive");
        }

        // Update URL without reloading
        const newUrl = window.location.pathname + '?tab=' + tab;
        history.replaceState(null, '', newUrl);
    }
</script>

</body>
</html>
