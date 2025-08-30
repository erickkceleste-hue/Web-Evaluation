<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "user");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM assigned_evaluations WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: assign_evaluation.php?message=" . urlencode("Evaluation deleted successfully!"));
    exit;
}

// Handle Edit Request (Save changes)
if (isset($_POST['update_evaluation'])) {
    $id = intval($_POST['id']);
    $section = trim($_POST['section']);
    $date = trim($_POST['date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);

    if (!empty($section) && !empty($date) && !empty($start_time) && !empty($end_time)) {
        $stmt = $conn->prepare("UPDATE assigned_evaluations SET section=?, date=?, start_time=?, end_time=? WHERE id=?");
        $stmt->bind_param("ssssi", $section, $date, $start_time, $end_time, $id);
        $stmt->execute();
        header("Location: assign_evaluation.php?message=" . urlencode("Evaluation updated successfully!"));
        exit;
    }
}

// Fetch sections from teacher_sections table
$sections = $conn->query("SELECT DISTINCT section FROM teacher_sections");

// Fetch existing assigned evaluations
$assigned_evaluations = $conn->query("SELECT * FROM assigned_evaluations ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Evaluation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; } 
        body { font-family: 'Segoe UI', sans-serif; background: #f2f2f2; display: flex; } 
        .sidebar { width: 250px; min-height: 100vh; background-color: #2c3e50; padding-top: 20px; position: fixed; transition: transform 0.3s ease; } 
        .sidebar img { width: 100px; margin: 0 auto 20px; display: block; } 
        .nav-links { display: flex; flex-direction: column; gap: 10px; padding: 0 20px; } 
        .nav-links a { color: white; text-decoration: none; padding: 10px 15px; background: #34495e; border-radius: 4px; transition: background 0.3s; } 
        .nav-links a:hover { background: #1abc9c; } 
        .logout { background: #e74c3c; } .logout:hover { background: #c0392b; } 
        .topbar { position: fixed; padding: 30px 20px; top: 0; left: 250px; right: 0; background: #007bff; color: #fff; display: flex; justify-content: space-between; align-items: center; z-index: 100; } 
        .main-content { margin-left: 250px; padding: 100px 20px 30px 20px; width: 100%; display: flex; flex-direction: column; align-items: center; } 
        .container { background: #fff; padding: 30px; border-radius: 10px; max-width: 900px; width: 100%; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px; } 
        h2 { margin-bottom: 20px; color: #333; text-align: center; } 
        .topbar .admin { margin-right: 30px; font-size: 16px; font-weight: 500; }
        .form-group { margin-bottom: 20px; } 
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; } 
        .form-group select, .form-group input { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; } 
        .btn { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 2px; } 
        .big-btn {
            width: 100%;
            padding: 15px 20px;
            background: #5F4DFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 15px;
        }
        a.btn-danger {
            text-decoration: none !important;
        }
        
        .big-btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; color: #fff; } 
        .btn-danger:hover { background: #c0392b; } 
        .btn-edit { background: #5F4DFF; color: #fff; padding: 9px; width: 60px; } 
        .btn-edit:hover { background: #2980b9; } 
        table { width: 100%; border-collapse: collapse; margin-top: 15px; } 
        table th, table td { padding: 10px; border: 1px solid #ccc; text-align: center; } 
        .edit-form { display: none; margin-top: 10px; background: #f9f9f9; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>

<?php if (isset($_GET['message'])): ?>
<script>
    alert("<?= htmlspecialchars($_GET['message']) ?>");
</script>
<?php endif; ?>

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
    <!-- Assign Evaluation Form -->
    <div class="container">
        <h2>Assign Evaluation and Date to Section</h2>
        <form method="post" action="process_assign_evaluation.php">
            <div class="form-group">
                <label>Select Section:</label>
                <select name="section" id="sectionDropdown" required>
                    <option value="">-- Select Section --</option>
                    <?php while ($row = $sections->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['section']) ?>"><?= htmlspecialchars($row['section']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Start Time:</label>
                <input type="time" name="start_time" required>
            </div>

            <div class="form-group">
                <label>End Time:</label>
                <input type="time" name="end_time" required>
            </div>

            <div class="form-group">
                <label>Select Date:</label>
                <input type="date" name="date" required>
            </div>

            <p id="studentCount" style="font-weight:600;"></p>

            <button type="submit" class="big-btn">Assign Evaluation and Date</button>
        </form>
    </div>

    <!-- Assigned Evaluations List -->
    <div class="container">
        <h2>Existing Assigned Evaluations</h2>
        <?php if ($assigned_evaluations->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($eval = $assigned_evaluations->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($eval['section']) ?></td>
                        <td><?= htmlspecialchars($eval['date']) ?></td>
                        <td><?= date("h:i A", strtotime($eval['start_time'])) ?></td>
                        <td><?= date("h:i A", strtotime($eval['end_time'])) ?></td>
                        <td>
                            <button class="btn btn-edit" onclick="toggleEditForm(<?= $eval['id'] ?>)">Edit</button>
                            <a class="btn btn-danger" href="?delete=<?= $eval['id'] ?>" onclick="return confirm('Are you sure you want to delete this evaluation?')">Delete</a>
                        </td>
                    </tr>
                    <!-- Hidden Edit Form -->
                    <tr id="edit-form-<?= $eval['id'] ?>" class="edit-form">
                        <td colspan="5">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $eval['id'] ?>">
                                <label>Section:</label>
                                <input type="text" name="section" value="<?= htmlspecialchars($eval['section']) ?>" required>
                                <label>Date:</label>
                                <input type="date" name="date" value="<?= htmlspecialchars($eval['date']) ?>" required>
                                <label>Start Time:</label>
                                <input type="time" name="start_time" value="<?= htmlspecialchars($eval['start_time']) ?>" required>
                                <label>End Time:</label>
                                <input type="time" name="end_time" value="<?= htmlspecialchars($eval['end_time']) ?>" required>
                                <button type="submit" name="update_evaluation" class="btn btn-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;">No evaluations assigned yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleEditForm(id) {
        const formRow = document.getElementById("edit-form-" + id);
        formRow.style.display = formRow.style.display === "table-row" ? "none" : "table-row";
    }

    document.getElementById("sectionDropdown").addEventListener("change", function () {
        const section = this.value;
        fetch("get_student_count.php?section=" + section)
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById("studentCount");
                if (data.total !== undefined) {
                    countElement.textContent = "Total Students in this section: " + data.total;
                } else {
                    countElement.textContent = "Unable to load student count.";
                }
            });
    });
</script>

</body>
</html>
