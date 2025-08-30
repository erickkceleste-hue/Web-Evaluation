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

/*
 past_evaluation table structure:
 id, teacher_name, section, question_id, answer, feedback, student_name, created_at

 teacher_sections table structure:
 id, teacher_name, section, subject
*/

function getAcademicYear($date) {
    $year = date("Y", strtotime($date));
    $month = date("n", strtotime($date));
    if ($month >= 6) {
        return $year . "-" . ($year + 1);
    } else {
        return ($year - 1) . "-" . $year;
    }
}

/* -------------------
   ✅ Handle Delete
--------------------*/
$deleteMessage = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_year"])) {
    $deleteYear = $_POST["delete_year"];
    if ($deleteYear !== "all") {
        // Build year range for deletion
        $parts = explode("-", $deleteYear);
        if (count($parts) == 2) {
            $startYear = intval($parts[0]);
            $endYear = intval($parts[1]);

            // delete where created_at falls within June(startYear) – May(endYear)
            $startDate = $startYear . "-06-01";
            $endDate = $endYear . "-05-31";

            $stmt = $conn->prepare("DELETE FROM past_evaluation WHERE created_at BETWEEN ? AND ?");
            $stmt->bind_param("ss", $startDate, $endDate);
            if ($stmt->execute()) {
                $deleteMessage = "✅ All evaluations for Academic Year $deleteYear have been deleted.";
            } else {
                $deleteMessage = "❌ Error deleting records.";
            }
            $stmt->close();
        }
    } else {
        // delete all
        if ($conn->query("DELETE FROM past_evaluation")) {
            $deleteMessage = "✅ All evaluation records have been deleted.";
        } else {
            $deleteMessage = "❌ Error deleting all records.";
        }
    }
}

// Fetch average rating per teacher/section from past evaluations
$sql = "SELECT 
            pe.teacher_name,
            pe.section,
            ts.subject,
            ROUND(AVG(pe.answer), 2) AS avg_rating,
            DATE(pe.created_at) AS eval_date
        FROM past_evaluation pe
        LEFT JOIN teacher_sections ts 
          ON pe.teacher_name = ts.teacher_name 
         AND pe.section = ts.section
        GROUP BY pe.teacher_name, pe.section, ts.subject, DATE(pe.created_at)
        ORDER BY pe.teacher_name, eval_date DESC";

$result = $conn->query($sql);

// Organize results by teacher
$teachers = [];
$academicYears = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $row["academic_year"] = getAcademicYear($row["eval_date"]);
    $teachers[$row["teacher_name"]][] = $row;
    $academicYears[$row["academic_year"]] = true;
  }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Past Evaluation Results</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; }

    .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; padding-top: 20px; position: fixed; top: 0; left: 0; z-index: 1000; }
    .sidebar img { width: 100px; margin: 0 auto 20px; display: block; }
    .nav-links { display: flex; flex-direction: column; gap: 10px; padding: 0 20px; }
    .nav-links a { color: white; text-decoration: none; padding: 10px 15px; background: #34495e; border-radius: 4px; transition: background 0.3s; }
    .nav-links a:hover { background: #1abc9c; }
    .logout { background: #e74c3c; }
    .logout:hover { background: #c0392b; }

    .main-content { margin-left: 250px; background-color: #fff; min-height: 100vh; }
    .topbar { display: flex; justify-content: space-between; align-items: center; background-color: #007bff; color: white; padding: 30px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-left { display: flex; align-items: center; gap: 15px; }
    .topbar-left h1 { font-size: 20px; white-space: nowrap; }
    .menu-toggle { display: none; flex-direction: column; cursor: pointer; }
    .menu-toggle span { height: 3px; width: 25px; background-color: white; margin: 4px 0; transition: 0.4s; }
    .admin-info { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; margin-right: 30px; }

    .main { padding: 2rem; }
    .content-container { max-width: 1000px; margin: 0 auto; }

    .search-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; }
    .search-bar input { padding: 0.5rem; width: 250px; border: 1px solid #ccc; border-radius: 5px; }
    select{padding: 10px; border-radius: 5px;}

    h2.teacher-name { margin-top: 2rem; font-size: 20px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    table { width: 100%; border-collapse: collapse; background-color: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); border-radius: 8px; overflow: hidden; margin-top: 1rem; }
    th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
    th { background-color: #f0f2f5; }
    tr:hover { background-color: #f9f9f9; }

    .message { margin: 1rem 0; font-weight: bold; color: green; }
    .error { color: red; }
  </style>
</head>
<body>

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

<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <div class="menu-toggle" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <h1>Evaluation System</h1>
    </div>
    <div class="admin-info">
      <i class="fas fa-user-shield"></i>
      Administrator
    </div>
  </div>

  <div class="main">
    <div class="content-container">

      <?php if ($deleteMessage): ?>
        <div class="message"><?php echo htmlspecialchars($deleteMessage); ?></div>
      <?php endif; ?>

      <div class="search-bar">
        <div>
          <label for="academicYear" style="margin-right: 10px; font-weight: 500;">Academic Year:</label>
          <select id="academicYear" onchange="filterByYear()">
            <option value="all">All</option>
            <?php foreach (array_keys($academicYears) as $year): ?>
              <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <input type="text" id="searchInput" placeholder="Search by subject, or section..." onkeyup="filterTables()" />
      </div>

      <?php if (!empty($teachers)): ?>
        <?php foreach ($teachers as $teacherName => $rows): ?>
          <h2 class="teacher-name"><?php echo htmlspecialchars($teacherName); ?></h2>
          <table class="evaluationTable">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Section</th>
                <th>Rating</th>
                <th>Date</th>
                <th>Academic Year</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr data-year="<?php echo $row['academic_year']; ?>">
                  <td><?php echo htmlspecialchars($row["subject"] ?? "N/A"); ?></td>
                  <td><?php echo htmlspecialchars($row["section"]); ?></td>
                  <td><?php echo htmlspecialchars($row["avg_rating"]); ?></td>
                  <td><?php echo htmlspecialchars($row["eval_date"]); ?></td>
                  <td><?php echo htmlspecialchars($row["academic_year"]); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No past evaluation data available.</p>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
function filterTables() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  const allTables = document.querySelectorAll(".evaluationTable");

  allTables.forEach(table => {
    const rows = table.querySelectorAll("tbody tr");
    let tableHasMatch = false;

    rows.forEach(row => {
      const cells = row.querySelectorAll("td");
      const combinedText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(" ");
      const match = combinedText.includes(input);
      row.style.display = match ? "" : "none";
      if (match) tableHasMatch = true;
    });

    const heading = table.previousElementSibling;
    table.style.display = tableHasMatch ? "" : "none";
    heading.style.display = tableHasMatch ? "" : "none";
  });
}

function filterByYear() {
  const year = document.getElementById("academicYear").value;
  const rows = document.querySelectorAll(".evaluationTable tbody tr");

  rows.forEach(row => {
    if (year === "all" || row.dataset.year === year) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
}
</script>

</body>
</html>
