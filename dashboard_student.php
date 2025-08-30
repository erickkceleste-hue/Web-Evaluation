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

// ✅ Academic Year & Semester from Admin Dashboard
$school_year = $_SESSION["school_year"] ?? "2024-2025";
$semester    = $_SESSION["semester"] ?? "1st Semester";

// ✅ Check session
if (!isset($_SESSION["id_number"])) {
    die("Session 'id_number' is not set. Please ensure it's stored at login.");
}

$id_number = $_SESSION["id_number"];
$student_name = $_SESSION["name"]; // stored at login

// ✅ Get student section
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

date_default_timezone_set("Asia/Manila");
$currentDate = date("Y-m-d");
$currentTime = date("H:i:s");

$allowed = false;
$schedules = [];

// ✅ Get schedule
$sched_stmt = $conn->prepare("SELECT date, start_time, end_time FROM assigned_evaluations WHERE section = ?");
$sched_stmt->bind_param("s", $student_section);
$sched_stmt->execute();
$sched_result = $sched_stmt->get_result();
while ($row = $sched_result->fetch_assoc()) {
    $schedules[] = $row;
    if ($row['date'] === $currentDate && $currentTime >= $row['start_time'] && $currentTime <= $row['end_time']) {
        $allowed = true;
    }
}

// ✅ Get teachers for section
$teachers = [];
$ts_stmt = $conn->prepare("SELECT teacher_name, subject FROM teacher_sections WHERE section = ?");
$ts_stmt->bind_param("s", $student_section);
$ts_stmt->execute();
$ts_result = $ts_stmt->get_result();
while ($row = $ts_result->fetch_assoc()) {
    $teachers[] = $row;
}

// ✅ Get already evaluated teachers by this student
$evaluated_teachers = [];
$eval_stmt = $conn->prepare("SELECT DISTINCT teacher_name FROM evaluation_answers WHERE id_number = ?");
$eval_stmt->bind_param("s", $id_number);
$eval_stmt->execute();
$eval_result = $eval_stmt->get_result();
while ($row = $eval_result->fetch_assoc()) {
    $evaluated_teachers[] = $row['teacher_name'];
}

// ✅ Get questionnaire
$questions_sql = "SELECT * FROM questionnaire";
$questions_result = $conn->query($questions_sql);
if (!$questions_result) {
    die("Error fetching questions: " . $conn->error);
}
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

/* ------------------------
   ✅ Handle form submission
-------------------------*/
$show_modal_message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_evaluation"])) {
    $teacher = $_POST["teacher"] ?? "";
    $feedback = $_POST["feedback"] ?? "";

    if (empty($teacher)) {
        die("Teacher is required.");
    }

    // Prevent duplicate evaluation
    if (in_array($teacher, $evaluated_teachers)) {
        $show_modal_message = "⚠️ You already submitted an evaluation for $teacher.";
    } else {
        $insert_sql = "INSERT INTO evaluation_answers 
            (id_number, student_name, section, teacher_name, question_id, answer, feedback, date_submitted) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);

        foreach ($questions as $index => $q) {
            $qid = $q['id'];
            $answer = $_POST["q$index"] ?? null;

            if ($answer) {
                $insert_stmt->bind_param(
                    "ssssiss", 
                    $id_number, 
                    $student_name, 
                    $student_section, 
                    $teacher, 
                    $qid, 
                    $answer, 
                    $feedback
                );
                $insert_stmt->execute();
            }
        }
        $show_modal_message = "✅ Thank you! Your evaluation for $teacher has been submitted.";
    }
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
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body, html { height: 100%; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f7fa; color: #222; }
    .app { display: flex; height: 100vh; }
    .main-content { flex-grow: 1; display: flex; flex-direction: column; }
    .header { background-color: #0058ff; color: white; height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 30px 30px; }
    .header-title { font-size: 1.2rem; font-weight: bold; }
    .user-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; position: relative; margin-right: 34px; }
    .user-profile img { width: 32px; height: 32px; border-radius: 50%; background-color: #ccc; border: 2px solid white; }
    .user-profile .name { color: white; font-weight: 500; }
    .dropdown-menu { position: absolute; top: 64px; right: 24px; background-color: white; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.2); border-radius: 6px; width: 160px; display: none; flex-direction: column; z-index: 100; }
    .dropdown-menu.show { display: flex; }
    .dropdown-menu a { padding: 10px 16px; text-decoration: none; color: #222; border-bottom: 1px solid #eee; transition: background-color 0.2s; }
    .dropdown-menu a:hover { background-color: #f0f0f0; }
    .dropdown-menu a:last-child { border-bottom: none; }
    .page-body { padding: 24px; overflow-y: auto; }
    .section-title { font-size: 2rem; font-weight: 600; margin-bottom: 16px; border-bottom: 3px solid #0058ff; display: inline-block; padding-bottom: 8px; }
    .evaluate-container { display: flex; gap: 24px; flex-wrap: wrap; }
    .teacher-list { display: flex; flex-direction: column; gap: 12px; min-width: 200px; }
    .teacher-btn { background-color: #666; color: white; border: none; padding: 10px 16px; border-radius: 5px; text-align: left; transition: background 0.2s; cursor: pointer; }
    .teacher-btn.active, .teacher-btn:hover { background-color: #0058ff; }
    .teacher-btn:disabled { background-color: #aaa; cursor: not-allowed; }

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
    .submit-btn { background-color: #0058ff; color: white; padding: 12px 24px; font-weight: bold; border: none; border-radius: 8px; transition: background 0.3s; cursor: pointer; }
    .submit-btn:hover { background-color: #0044cc; }
    .disabled-msg { background: #ffe5e5; color: #b30000; padding: 20px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 1.1rem; }
    .schedule-list { margin-top: 10px; font-size: 1rem; color: #333; }
    .schedule-list ul { list-style: none; padding: 0; }
    .schedule-list li { margin: 5px 0; }

    /* Modal */
    .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:200; }
    .modal-content { background:white; padding:20px; border-radius:10px; max-width:400px; text-align:center; }
    .modal-content button { margin-top:15px; padding:8px 16px; border:none; background:#0058ff; color:white; border-radius:6px; cursor:pointer; }
    .modal-content button:hover { background:#0044cc; }
  </style>
</head>
<body>
  <div class="app">
    <div class="main-content">
      <header class="header">
        <div class="header-title">EVALUATION SYSTEM</div>
        <div class="user-profile" id="profileDropdownBtn">
          <img src="Image/profile icon.png" alt="User Icon">
          <span class="name"><?php echo htmlspecialchars($_SESSION["name"]); ?></span>
          <div class="dropdown-menu" id="profileDropdownMenu">
            <a href="profile.php">Profile</a>
            <a id="logoutBtn">Logout</a>
          </div>
        </div>
      </header>

      <div class="page-body">
        <h2 class="section-title">Evaluate</h2>

        <?php if (!$allowed): ?>
          <div class="disabled-msg">
            🚫 Evaluation is disabled. Please come back on your scheduled date and time.<br>
            Current Philippine Time: <strong><?php echo date("l, F j, Y h:i A"); ?></strong>

            <?php if (!empty($schedules)): ?>
              <div class="schedule-list">
                <p><strong>Your Schedule:</strong></p>
                <ul>
                  <?php foreach ($schedules as $sched): ?>
                    <li>
                      <?php echo date("F j, Y", strtotime($sched['date'])); ?> 
                      (<?php echo date("h:i A", strtotime($sched['start_time'])); ?> - 
                       <?php echo date("h:i A", strtotime($sched['end_time'])); ?>)
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="evaluate-container">
            <div class="teacher-list">
              <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $index => $t): ?>
                  <button 
                    class="teacher-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                    data-teacher="<?php echo htmlspecialchars($t['teacher_name']); ?>"
                    <?php echo in_array($t['teacher_name'], $evaluated_teachers) ? 'disabled' : ''; ?>>
                    <?php echo htmlspecialchars($t['teacher_name']); ?> 
                    (<?php echo htmlspecialchars($t['subject']); ?>)
                  </button>
                <?php endforeach; ?>
              <?php else: ?>
                <p>No teachers assigned to your section.</p>
              <?php endif; ?>
            </div>

            <!-- Evaluation Panel -->
            <form class="evaluation-panel" method="POST" action="">
              <input type="hidden" name="teacher" id="selectedTeacher" value="">

              <div class="evaluation-title">
                Evaluation Questionnaire (<?php echo htmlspecialchars($school_year . " " . $semester); ?>)
              </div>
              <div class="rating-legend">Rating Legend:</div>
              <div class="rating-scores">
                <div>5 - Strongly Agree</div>
                <div>4 - Agree</div>
                <div>3 - Neutral</div>
                <div>2 - Strongly Disagree</div>
                <div>1 - Disagree</div>
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
                <button type="submit" name="submit_evaluation" class="submit-btn">Submit Evaluation</button>
              </div>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="messageModal" class="modal">
    <div class="modal-content">
      <p id="modalMessage"></p>
      <button onclick="closeModal()">OK</button>
    </div>
  </div>

<script>
  // Profile dropdown
  const profileBtn = document.getElementById('profileDropdownBtn');
  const menu = document.getElementById('profileDropdownMenu');
  profileBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    menu.classList.toggle('show');
  });
  document.addEventListener('click', function () {
    menu.classList.remove('show');
  });

  document.getElementById("logoutBtn").addEventListener("click", function () {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "logout.php";
    }
  });

  // Handle teacher selection
  document.querySelectorAll(".teacher-btn").forEach(btn => {
    btn.addEventListener("click", function () {
      if(this.disabled) return;
      document.querySelectorAll(".teacher-btn").forEach(b => b.classList.remove("active"));
      this.classList.add("active");
      document.getElementById("selectedTeacher").value = this.getAttribute("data-teacher");
    });
  });

  // Set default first teacher
  const firstTeacher = document.querySelector(".teacher-btn.active");
  if(firstTeacher){
    document.getElementById("selectedTeacher").value = firstTeacher.getAttribute("data-teacher");
  }

  // Modal handling
  function showModal(message) {
    document.getElementById("modalMessage").innerText = message;
    document.getElementById("messageModal").style.display = "flex";
  }
  function closeModal() {
    document.getElementById("messageModal").style.display = "none";
    window.location.href = "dashboard_student.php";
  }

  <?php if (!empty($show_modal_message)): ?>
    showModal("<?php echo $show_modal_message; ?>");
  <?php endif; ?>
</script>
</body>
</html>
