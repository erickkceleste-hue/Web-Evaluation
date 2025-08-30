<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "user"; // Make sure this is your correct DB name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Safety check for session 'id_number'
if (!isset($_SESSION["id_number"])) {
    die("Session 'id_number' is not set. Please ensure it's stored at login.");
}

$id_number = $_SESSION["id_number"];

// ✅ Check if student exists and get section
$student_sql = "SELECT section FROM student WHERE id_number = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student with ID $id_number not found.");
}

$student_section = $student['section'];

// ✅ Get assigned teachers for the student's section
$teacher_sql = "SELECT * FROM teacher WHERE section = ?";
$stmt = $conn->prepare($teacher_sql);
$stmt->bind_param("s", $student_section);
$stmt->execute();
$teacher_result = $stmt->get_result();
$teachers = [];
while ($row = $teacher_result->fetch_assoc()) {
    $teachers[] = $row;
}

// ✅ Get all questions from the questionnaire table
$questions_sql = "SELECT * FROM questionnaire"; // No WHERE clause since there's no 'active' column
$questions_result = $conn->query($questions_sql);

if (!$questions_result) {
    die("Error: Failed to fetch questions from 'questionnaire' table.<br>" . $conn->error);
}

$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Evaluation System</title>
  <style>
    /* Existing styles (abbreviated for clarity) */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body, html { height: 100%; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa; color: #222; }
    .app { display: flex; height: 100vh; }
    .sidebar { width: 230px; background-color: #404040; padding: 1.5rem 1rem; display: flex; flex-direction: column; align-items: center; gap: 2rem; }
    .sidebar-logo { width: 100px; height: 100px; border-radius: 50%; overflow: hidden; background-color: white; }
    .sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
    .sidebar-nav { width: 100%; display: flex; flex-direction: column; gap: 10px; }
    .nav-btn { background-color: #666; color: white; border: none; padding: 10px 14px; font-size: 1rem; border-radius: 5px; text-align: left; transition: background 0.3s ease; }
    .nav-btn.active, .nav-btn:hover { background-color: #0058ff; }

    .main-content { flex-grow: 1; display: flex; flex-direction: column; }
    .header { background-color: #0058ff; color: white; height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 30px 30px; position: relative; }

    .header-title { font-size: 1.2rem; font-weight: bold; }

    /* Profile dropdown styles */
    .user-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; position: relative; margin-right: 34px; }
    .user-profile img { width: 32px; height: 32px; border-radius: 50%; background-color: #ccc; border: 2px solid white; }
    .user-profile .name { color: white; font-weight: 500; }

    .dropdown-menu {
      position: absolute;
      top: 64px;
      right: 24px;
      background-color: white;
      color: #333;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      border-radius: 6px;
      width: 160px;
      display: none;
      flex-direction: column;
      z-index: 100;
    }

    .dropdown-menu.show {
      display: flex;
    }

    .dropdown-menu a {
      padding: 10px 16px;
      text-decoration: none;
      color: #222;
      border-bottom: 1px solid #eee;
      transition: background-color 0.2s;
    }

    .dropdown-menu a:hover {
      background-color: #f0f0f0;
    }

    .dropdown-menu a:last-child {
      border-bottom: none;
    }

    /* Main page body styling (same as yours) */
    .page-body { padding: 24px; overflow-y: auto; }
    .section-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 16px; border-bottom: 2px solid #0058ff; display: inline-block; padding-bottom: 4px; }
    .evaluate-container { display: flex; gap: 24px; flex-wrap: wrap; }
    .teacher-list { display: flex; flex-direction: column; gap: 12px; min-width: 200px; }
    .teacher-btn { background-color: #666; color: white; border: none; padding: 10px 16px; border-radius: 5px; text-align: left; transition: background 0.2s; }
    .teacher-btn.active, .teacher-btn:hover { background-color: #0058ff; }

    .evaluation-panel { background: white; flex-grow: 1; padding: 24px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
    .evaluation-title { font-weight: 600; font-size: 1rem; margin-bottom: 16px; border-bottom: 2px solid #0058ff; padding-bottom: 6px; }

    .rating-legend { font-size: 0.9rem; margin-bottom: 12px; color: #555; }
    .rating-scores { display: flex; justify-content: space-between; background-color: #eee; padding: 10px; margin-bottom: 20px; border-radius: 6px; font-size: 0.85rem; }
    .rating-scores div { flex: 1; text-align: center; }
    .rating-scores div:first-child { text-align: left; }

    table.criteria-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .criteria-table th, .criteria-table td { padding: 12px; border-bottom: 1px solid #ccc; text-align: center; }
    .criteria-table th:first-child, .criteria-table td:first-child { text-align: left; }

    .feedback-label { margin-bottom: 6px; font-weight: 500; }
    .feedback-textarea { width: 100%; min-height: 100px; padding: 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; resize: vertical; margin-bottom: 20px; }

    .submit-btn-container { display: flex; justify-content: flex-end; }
    .submit-btn { background-color: #0058ff; color: white; padding: 12px 24px; font-weight: bold; border: none; border-radius: 8px; transition: background 0.3s; }
    .submit-btn:hover { background-color: #0044cc; }

    @media (max-width: 768px) {
      .evaluate-container { flex-direction: column; }
      .teacher-list { flex-direction: row; overflow-x: auto; }
      .teacher-btn { white-space: nowrap; }
    }
  </style>
</head>
<body>
  <div class="app">
    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="sidebar-logo">
        <img src="Image/ASASHS Logo.png" alt="School Logo">
      </div>
      <div class="sidebar-nav">
  <button id="dashboardBtn" class="nav-btn">Dashboard</button>
  <button id="evaluateBtn" class="nav-btn">Evaluate</button>
</div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <header class="header">
        <div class="header-title">EVALUATION SYSTEM</div>

        <div class="user-profile" id="profileDropdownBtn">
          <img src="Image/profile icon.png" alt="User Icon">
          <span class="name"><?php echo htmlspecialchars($_SESSION["name"]); ?></span>

          <!-- Dropdown Menu -->
          <div class="dropdown-menu" id="profileDropdownMenu">
            <a href="profile.php">Profile</a>
            <a id="logoutBtn">Logout</a>
          </div>
        </div>
      </header>

      <div class="page-body">
        <h2 class="section-title">Evaluate</h2>
        <div class="evaluate-container">
          <!-- Teachers -->
          <div class="teacher-list">
  <button class="teacher-btn active" data-teacher-id="1">Mr. Juan Dela Cruz</button>
  <button class="teacher-btn" data-teacher-id="2">Ms. Maria Santos</button>
  <button class="teacher-btn" data-teacher-id="3">Sir Jose Rizal</button>
  <button class="teacher-btn" data-teacher-id="4">Ma'am Clara Rodriguez</button>
  <button class="teacher-btn" data-teacher-id="5">Prof. Andres Bonifacio</button>
</div>


          <!-- Evaluation Panel -->
          <form class="evaluation-panel" id="evaluationForm">
            <div class="evaluation-title">Evaluation Questionnaire (2024 - 2025 2nd Sem)</div>
            <div class="rating-legend">Rating Legend:</div>
            <div class="rating-scores">
              <div>5 - Excellent</div>
              <div>4 - Very Good</div>
              <div>3 - Satisfactory</div>
              <div>2 - Needs Improvement</div>
              <div>1 - Poor</div>
            </div>

            <table class="criteria-table">
  <thead>
    <tr>
      <th>Criteria</th>
      <th>1</th>
      <th>2</th>
      <th>3</th>
      <th>4</th>
      <th>5</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($questions as $index => $q): ?>
      <tr>
        <td><?php echo htmlspecialchars($q['question']); ?></td>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <td><input type="radio" name="q<?php echo $index; ?>" value="<?php echo $i; ?>" required></td>
        <?php endfor; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

            <label for="feedback" class="feedback-label">Feedback:</label>
            <textarea id="feedback" class="feedback-textarea" placeholder="Write your feedback here..." name="feedback"></textarea>

            <div class="submit-btn-container">
              <button type="submit" class="submit-btn">Submit Evaluation</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- JS: Profile dropdown & evaluation validation -->
  <script>
    // Toggle profile dropdown
    const profileBtn = document.getElementById('profileDropdownBtn');
    const menu = document.getElementById('profileDropdownMenu');

    profileBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('show');
    });

    document.addEventListener('click', function () {
      menu.classList.remove('show');
    });

    // Teacher button activation
    document.querySelectorAll('.teacher-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.teacher-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });

    document.getElementById("logoutBtn").addEventListener("click", function () {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "logout.php"; // Make sure logout.php destroys the session
    }
  });

    // Sidebar button navigation
  document.getElementById('dashboardBtn').addEventListener('click', function () {
    window.location.href = 'dashboard_student.php';
  });

  document.getElementById('evaluateBtn').addEventListener('click', function () {
    window.location.href = 'evaluate.php';
  });

    // Evaluation form validation
    document.getElementById('evaluationForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const required = ['q1', 'q2'];
      for (let q of required) {
        if (!document.querySelector(`input[name="${q}"]:checked`)) {
          alert("Please answer all questions before submitting.");
          return;
        }
      }
      alert("Thank you for your evaluation!");
      this.reset();
    });
  </script>
</body>
</html>
