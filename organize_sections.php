<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}
include 'connect.php';

// For tab switching and edit
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$defaultTab = $editId || (isset($_GET['tab']) && $_GET['tab'] === 'sections') ? 'sections' : 'assign';
$assignments = $conn->query("SELECT * FROM teacher_sections ORDER BY teacher_name ASC, section ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_teacher'])) {
        $id = intval($_POST['id']);
        $teacher_name = trim($_POST['teacher_name']);
        $section = trim($_POST['section']);
        $subject = trim($_POST['subject']);

        if (!empty($teacher_name) && !empty($section) && !empty($subject)) {
            $stmt = $conn->prepare("UPDATE teacher_sections SET teacher_name = ?, section = ?, subject = ? WHERE id = ?");
            $stmt->bind_param("sssi", $teacher_name, $section, $subject, $id);
            $stmt->execute();
            header("Location: organize_sections.php?tab=sections");
            exit;
        }
    }

    if (isset($_POST['delete_teacher'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM teacher_sections WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: organize_sections.php?tab=sections");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Organize Sections</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: #f2f2f2; display: flex; }
    .sidebar { width: 250px; min-height: 100vh; background-color: #2c3e50; padding-top: 20px; position: fixed; }
    .sidebar img { width: 100px; margin: 0 auto 20px; display: block; }
    .nav-links { display: flex; flex-direction: column; gap: 10px; padding: 0 20px; }
    .nav-links a { color: white; text-decoration: none; padding: 10px 15px; background: #34495e; border-radius: 4px; transition: background 0.3s; }
    .nav-links a:hover { background: #1abc9c; }
    .logout { background: #e74c3c; }
    .logout:hover { background: #c0392b; }
    .topbar { position: fixed; padding: 30px 20px; top: 0; left: 250px; right: 0; background: #007bff; color: #fff; display: flex; justify-content: space-between; align-items: center; }
    .topbar .title { font-size: 20px; font-weight: bold; }
    .main-content { margin-left: 250px; padding: 100px 20px 30px 20px; width: 100%; }
    .tab-buttons { display: flex; gap: 20px; margin-bottom: 20px; justify-content: center; }
    .tab-button { padding: 15px 40px; border: none; border-radius: 40px; cursor: pointer; font-size: 18px; font-weight: 500; }
    .tab-button.active { background-color: blue; color: white; }
    .tab-button.inactive { background-color: #ddd; color: black; }
    .container { background: #fff; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2, h3 { text-align: center; margin-bottom: 20px; color: #333; }
    .topbar .admin { margin-right: 30px; font-size: 16px; font-weight: 500; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background-color: #3498db; color: white; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 10px; }
    .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    a.btn, a.edit-btn { text-decoration: none;}
    .btn { padding: 8px 10px; background: #5F4DFF; color: white; font-size: 15px; margin-top: 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
    .btn:hover { background: #2980b9; }
    .big-btn { width: 100%; padding: 15px 20px; background: #5F4DFF; color: white; font-size: 15px; margin-top: 15px; border: none; border-radius: 4px; cursor: pointer; }
    .big-btn:hover { background: #2980b9; }
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

<div class="topbar">
  <div class="title">Evaluation System</div>
  <div class="admin">Administrator</div>
</div>

<div class="main-content">

  <!-- Tabs -->
  <div class="tab-buttons">
    <button class="tab-button <?php echo $defaultTab === 'assign' ? 'active' : 'inactive'; ?>" onclick="showTab('assign')">Assign</button>
    <button class="tab-button <?php echo $defaultTab === 'sections' ? 'active' : 'inactive'; ?>" onclick="showTab('sections')">Sections</button>
  </div>

  <!-- Assign Form -->
  <div id="assign" class="tab-content" style="<?php echo $defaultTab === 'assign' ? '' : 'display:none;'; ?>">
    <div class="container">
      <h2>Assign Section to Teacher</h2>
      <form action="process_section.php" method="post">
        <div class="form-group">
          <label for="section">Section Name:</label>
          <input type="text" name="section" id="section" required>
        </div>
        <div class="form-group">
          <label for="subject">Subject:</label>
          <input type="text" name="subject" id="subject" required>
        </div>
        <div class="form-group">
          <label for="teacher">Assign Teacher:</label>
          <select name="teacher_name" id="teacher" required>
            <option value="" disabled selected>Select Teacher</option>
            <?php
            $result = $conn->query("SELECT name FROM teacher ORDER BY name ASC");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
            }
            ?>
          </select>
        </div>
        <button type="submit" class="big-btn">Assign</button>
      </form>
    </div>
  </div>

  <!-- Sections Table -->
  <div id="sections" class="tab-content" style="<?php echo $defaultTab === 'sections' ? '' : 'display:none;'; ?>">
    <div class="container">
      <h3>Assigned Sections</h3>
      <table>
        <thead>
          <tr>
            <th>Teacher</th>
            <th>Section</th>
            <th>Subject</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM teacher_sections ORDER BY id DESC");
          if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  if ($editId === intval($row['id'])) {
                      echo "<tr>
                          <form method='POST'>
                              <td><input type='text' name='teacher_name' value='" . htmlspecialchars($row['teacher_name']) . "' required></td>
                              <td><input type='text' name='section' value='" . htmlspecialchars($row['section']) . "' required></td>
                              <td><input type='text' name='subject' value='" . htmlspecialchars($row['subject']) . "' required></td>
                              <td>
                                  <input type='hidden' name='id' value='" . $row['id'] . "'>
                                  <button type='submit' name='update_teacher' class='btn'>Save</button>
                                  <a href='organize_sections.php?tab=sections' class='btn' style='background:#00aaff;'>Cancel</a>
                              </td>
                          </form>
                      </tr>";
                  } else {
                      echo "<tr>
                          <td>" . htmlspecialchars($row['teacher_name']) . "</td>
                          <td>" . htmlspecialchars($row['section']) . "</td>
                          <td>" . htmlspecialchars($row['subject']) . "</td>
                          <td>
                              <a href='organize_sections.php?edit=" . $row['id'] . "' class='btn'>Edit</a>
                              <form method='POST' style='display:inline;' onsubmit=\"return confirm('Delete this teacher?');\">
                                  <input type='hidden' name='id' value='" . $row['id'] . "'>
                                  <button type='submit' name='delete_teacher' class='btn' style='background:#e67e22;'>Delete</button>
                              </form>
                          </td>
                      </tr>";
                  }
              }
          } else {
              echo "<tr><td colspan='4'>No assignments found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function showTab(tab) {
  const assignTab = document.getElementById("assign");
  const sectionsTab = document.getElementById("sections");
  const buttons = document.querySelectorAll(".tab-button");

  if (tab === "assign") {
    assignTab.style.display = "block";
    sectionsTab.style.display = "none";
    buttons[0].classList.add("active");
    buttons[0].classList.remove("inactive");
    buttons[1].classList.remove("active");
    buttons[1].classList.add("inactive");
  } else {
    assignTab.style.display = "none";
    sectionsTab.style.display = "block";
    buttons[1].classList.add("active");
    buttons[1].classList.remove("inactive");
    buttons[0].classList.remove("active");
    buttons[0].classList.add("inactive");
  }
  const newUrl = window.location.pathname + '?tab=' + tab;
  history.replaceState(null, '', newUrl);
}
</script>

</body>
</html>
