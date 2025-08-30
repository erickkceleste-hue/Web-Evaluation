<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

include 'connect.php';

// Handle Academic Year & Semester selection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["school_year"], $_POST["semester"])) {
    $_SESSION["school_year"] = $_POST["school_year"];
    $_SESSION["semester"] = $_POST["semester"];
}

// Default values if none set
$school_year = $_SESSION["school_year"] ?? "2024-2025";
$semester = $_SESSION["semester"] ?? "1st Semester";

$studentCount = $conn->query("SELECT COUNT(*) as count FROM student")->fetch_assoc()['count'];
$teacherCount = $conn->query("SELECT COUNT(*) as count FROM teacher")->fetch_assoc()['count'];
$sectionCount = $conn->query("SELECT COUNT(*) as count FROM teacher_sections")->fetch_assoc()['count'];

// ✅ Get average rating per teacher (directly from evaluation_answers)
$ratingsQuery = "
    SELECT teacher_name, 
           ROUND(AVG(answer),2) AS avg_rating
    FROM evaluation_answers
    GROUP BY teacher_name
";
$ratingsResult = $conn->query($ratingsQuery);

$teacherNames = [];
$teacherRatings = [];

if ($ratingsResult && $ratingsResult->num_rows > 0) {
    while ($row = $ratingsResult->fetch_assoc()) {
        $teacherNames[] = $row['teacher_name'];
        $teacherRatings[] = $row['avg_rating'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard | Evaluation System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; }

    /* Sidebar */
    .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; padding-top: 20px; position: fixed; top: 0; left: 0; transition: transform 0.3s ease; z-index: 1000; }
    .sidebar img { width: 100px; margin: 0 auto 20px; display: block; }
    .nav-links { display: flex; flex-direction: column; gap: 10px; padding: 0 20px; }
    .nav-links a { color: white; text-decoration: none; padding: 10px 15px; background: #34495e; border-radius: 4px; transition: background 0.3s; }
    .nav-links a:hover { background: #1abc9c; }
    .logout { background: #e74c3c; }
    .logout:hover { background: #c0392b; }

    /* Topbar */
    .main-content { margin-left: 250px; background-color: #fff; min-height: 100vh; }
    .topbar { display: flex; justify-content: space-between; align-items: center; background-color: #007bff; color: white; padding: 30px 20px; position: sticky; top: 0; z-index: 100; }
    .topbar-left { display: flex; align-items: center; gap: 15px; }
    .topbar-left h1 { font-size: 20px; white-space: nowrap; }
    .menu-toggle { display: none; flex-direction: column; cursor: pointer; }
    .menu-toggle span { height: 3px; width: 25px; background-color: white; margin: 4px 0; transition: 0.4s; }
    .admin-info { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; margin-right: 30px; }
    .admin-info i { font-size: 18px; }

    /* Content */
    .content { padding: 30px; }
    .greeting-box { background-color: #f0f0f0; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    .greeting-box h2 { font-size: 20px; margin-bottom: 10px; }
    .greeting-box p { font-size: 16px; }
    .cards { display: flex; gap: 20px; flex-wrap: wrap; }
    .card { flex: 1; min-width: 200px; background-color: #e6e6e6; padding: 25px; border-radius: 10px; text-align: center; }
    .card h1 { font-size: 40px; margin-bottom: 10px; color: #333; }
    .card p { font-size: 16px; color: #555; }
    .chart-container { display: flex; justify-content: center; align-items: center; margin-top: 50px; margin-bottom: 50px; }
    #chartCanvas { width: 100%; max-width: 900px; height: 500px; }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); position: fixed; z-index: 200; }
      .sidebar.active { transform: translateX(0); }
      .main-content { margin-left: 0; }
    }
    @media screen and (max-width: 576px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .menu-toggle { display: flex; }
      .main-content { margin-left: 0; }
    }
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
        <span></span><span></span><span></span>
      </div>
      <h1>Evaluation System</h1>
    </div>
    <div class="admin-info">Administrator</div>
  </div>

  <div class="content">
    <div class="greeting-box">
      <h2>Welcome Administrator</h2>
      <!-- Academic Year Form -->
      <form method="POST" style="margin-bottom: 15px;">
        <label for="school_year"><strong>Academic Year:</strong></label>
        <input type="text" id="school_year" name="school_year" value="<?php echo htmlspecialchars($school_year); ?>" required style="padding:5px; border:1px solid #ccc; border-radius:5px;">
        <select name="semester" required style="padding:5px; border:1px solid #ccc; border-radius:5px;">
          <option value="1st Semester" <?php if ($semester==="1st Semester") echo "selected"; ?>>1st Semester</option>
          <option value="2nd Semester" <?php if ($semester==="2nd Semester") echo "selected"; ?>>2nd Semester</option>
        </select>
        <button type="submit" style="padding:5px 10px; background:#007bff; color:white; border:none; border-radius:5px;">Set</button>
      </form>
      <p><strong>Current Academic Year:</strong> <?php echo htmlspecialchars($school_year . " " . $semester); ?></p>
    </div>

    <div class="cards">
      <div class="card"><h1><?php echo $teacherCount; ?></h1><p>Total Registered Teachers</p></div>
      <div class="card"><h1><?php echo $studentCount; ?></h1><p>Total Registered Students</p></div>
      <div class="card"><h1><?php echo $sectionCount; ?></h1><p>Total Classes</p></div>
    </div>

    <!-- Chart -->
    <div class="chart-container">
      <canvas id="chartCanvas"></canvas>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("active");
}

const ctx = document.getElementById('chartCanvas').getContext('2d');

const teacherNames = <?php echo json_encode($teacherNames); ?>;
const teacherRatings = <?php echo json_encode($teacherRatings); ?>;

new Chart(ctx, {
  type: 'bar',
  data: {
    labels: teacherNames,
    datasets: [{
      label: 'Average Rating',
      data: teacherRatings,
      backgroundColor: '#007bff'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        max: 5,
        ticks: { stepSize: 1 },
        title: { display: true, text: 'Rating (out of 5)'}
      },
      x: {
        title: { display: true, text: 'Teachers', font: {size: 17} }
      }
    },
    plugins: {
      title: {
        display: true,
        text: 'Evaluation Ratings per Teacher',
        font: { size: 18 }
      },
      legend: { display: false }
    }
  }
});
</script>

</body>
</html>
