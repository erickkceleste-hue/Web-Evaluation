<?php
// evaluation_result.php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "user";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Academic Year + Semester from session (set in dashboard)
$school_year = $_SESSION["school_year"] ?? "2024-2025";
$semester = $_SESSION["semester"] ?? "1st Semester";

// Fetch all teachers
$teachersQuery = "SELECT DISTINCT teacher_name FROM evaluation_answers";
$teachersResult = $conn->query($teachersQuery);

$teachers = [];
if ($teachersResult->num_rows > 0) {
    while ($row = $teachersResult->fetch_assoc()) {
        $teachers[] = $row['teacher_name'];
    }
}

// Fetch evaluation answers grouped by teacher and include subject
$evaluations = [];
foreach ($teachers as $teacher) {
    // Get subject from teacher_sections
    $subjectStmt = $conn->prepare("SELECT subject FROM teacher_sections WHERE teacher_name = ? LIMIT 1");
    $subjectStmt->bind_param("s", $teacher);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    $subjectRow = $subjectResult->fetch_assoc();
    $subject = $subjectRow['subject'] ?? 'N/A';

    // Fetch evaluations with actual question text from questionnaire table
    $stmt = $conn->prepare("SELECT ea.section, ea.question_id, ea.answer, ea.feedback, ea.student_name, q.question
                            FROM evaluation_answers ea
                            LEFT JOIN questionnaire q ON ea.question_id = q.id
                            WHERE ea.teacher_name = ?");
    $stmt->bind_param("s", $teacher);
    $stmt->execute();
    $result = $stmt->get_result();

    $teacherData = [
        'sections' => [],
        'students' => 0,
        'results' => [],          // summary for main table
        'student_answers' => [],  // per student
        'subject' => $subject
    ];

    $questionAggregates = [];
    $studentSet = []; // track unique students

    while ($row = $result->fetch_assoc()) {
        $teacherData['sections'][$row['section']] = $row['section'];
        $student = $row['student_name'];

        // track student uniqueness
        $studentSet[$student] = true;

        if (!isset($teacherData['student_answers'][$student])) {
            $teacherData['student_answers'][$student] = [];
        }
        $teacherData['student_answers'][$student][] = [
            'question_text' => $row['question'], // use actual question text
            'answer' => $row['answer'],
            'feedback' => $row['feedback']
        ];

        // Aggregate answers per question for main table
        $qtext = $row['question'];
        if (!isset($questionAggregates[$qtext])) {
            $questionAggregates[$qtext] = [
                1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0,
                'feedbacks' => []
            ];
        }
        $questionAggregates[$qtext][$row['answer']]++;
        if ($row['feedback']) $questionAggregates[$qtext]['feedbacks'][] = $row['feedback'];
    }

    // ✅ set correct student count
    $teacherData['students'] = count($studentSet);

    // Build results array for main table
    foreach ($questionAggregates as $qtext => $qdata) {
        $teacherData['results'][] = [
            'question_text' => $qtext,
            'counts' => [
                1 => $qdata[1],
                2 => $qdata[2],
                3 => $qdata[3],
                4 => $qdata[4],
                5 => $qdata[5]
            ],
            'feedback' => implode("; ", $qdata['feedbacks'])
        ];
    }

    $evaluations[$teacher] = $teacherData;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluation Report</title>
<style>
* {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      background: #f5f5f5;
    }

    .sidebar {
      width: 250px;
      height: 100vh;
      background-color: #2c3e50;
      padding-top: 20px;
      position: fixed;
      top: 0;
      left: 0;
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

    .page-wrapper {
      margin-left: 250px;
      min-height: 100vh;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #007bff;
      color: white;
      padding: 16px 20px;
      position: sticky;
      top: 0;
      z-index: 100;
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

    .main-content {
      display: flex;
      justify-content: flex-start;
      align-items: flex-start;
      gap: 30px;
      padding: 30px;
      max-width: 1200px;
      margin: auto;
      flex-wrap: wrap;
    }

    .teacher-card {
      background: #fff;
      border-radius: 10px;
      padding: 25px 20px;
      width: 250px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }

    .teacher-card h3 {
      text-align: center;
      margin-bottom: 20px;
    }

    .teacher-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .teacher-btn {
      padding: 10px 15px;
      border: none;
      background: #eee;
      cursor: pointer;
      border-radius: 6px;
      text-align: center;
      font-weight: 500;
      transition: background 0.3s;
    }

    .teacher-btn:hover {
      background: #ddd;
    }

    .teacher-btn.active {
      background: #007bff;
      color: white;
    }

    .container {
      flex: 1;
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      min-width: 500px;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
    }

    .info {
      display: flex;
      flex-wrap: wrap;
      gap: 15px 30px;
      justify-content: space-between;
      margin-bottom: 25px;
      font-weight: bold;
    }

    .legend {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 10px;
      background: #e0e0e0;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-weight: 500;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    th, td {
      padding: 12px;
      text-align: center;
      border: 1px solid #ddd;
    }

    th {
      background: #007bff;
      color: white;
    }

    tr:nth-child(even) {
      background: #f9f9f9;
    }

    .rating-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      margin-top: 20px;
    }

    .total-ratings {
      font-weight: bold;
      font-size: 16px;
    }

    .print-btn {
      background-color: #007bff;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }

    .print-btn:hover {
      background-color: #0056b3;
    }

    @media (max-width: 900px) {
      .main-content {
        flex-direction: column;
        align-items: stretch;
      }

      .teacher-card,
      .container {
        width: 100%;
      }

      .info {
        flex-direction: column;
        gap: 10px;
      }

      .legend {
        flex-direction: column;
        gap: 5px;
      }

      .rating-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }

    @media print {
      .sidebar, .topbar, .teacher-card, .print-btn, .show-students-btn {
        display: none !important;
      }

      .page-wrapper {
        margin: 0;
        padding: 0;
      }

      .main-content {
        padding: 0;
        margin: 0;
        display: block;
      }

      .container {
        box-shadow: none;
        padding: 0;
        margin: 0;
      }
    }
  /* Reset Success Modal */
#resetSuccessModal {
    display: none;
    position: fixed;
    z-index: 3000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
}

#resetSuccessContent {
    background: #fff;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    max-width: 95%;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

#resetSuccessContent h3 {
    margin-top: 0;
    color: #28a745;
}

#resetSuccessContent button {
    margin-top: 15px;
    padding: 10px 20px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

#resetSuccessContent button:hover {
    background: #0056b3;
}

/* Modal styles */
#studentModal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
}

#studentModalContent {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    width: 500px;
    max-width: 95%;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

#studentModalContent h3 {
    margin-top: 0;
    text-align: center;
}

#closeModalBtn {
    background: #e74c3c;
    color: #fff;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    float: right;
    margin-bottom: 10px;
}

#studentSelect {
    margin: 10px 0;
    padding: 8px;
    width: 100%;
    border-radius: 5px;
}

#studentTable {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

#studentTable th, #studentTable td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

#studentTable th {
    background-color: #007bff;
    color: #fff;
}
</style>
</head>
<body>

<!-- Sidebar -->
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

  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Evaluation System</h1>
      </div>
      <div class="admin-info">Administrator</div>
    </div>

    <div class="main-content">
      <!-- Teacher Selection -->
      <div class="teacher-card">
        <h3>Teachers</h3>
        <div class="teacher-list">
          <?php
          $first = true;
          foreach ($teachers as $teacher) {
              $active = $first ? "active" : "";
              echo "<button class='teacher-btn $active' onclick=\"showResults('".htmlspecialchars($teacher)."', event)\">".htmlspecialchars($teacher)."</button>";
              $first = false;
          }
          ?>
        </div>
      </div>

      <!-- Report -->
      <div class="container">
        <h2>Evaluation Report</h2>
        <div class="info">
          <div><strong>Class:</strong> <span id="className"></span></div>
          <!-- ✅ Academic Year now dynamic -->
          <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($school_year . " " . $semester); ?></div>
          <div><strong>Subject:</strong> <span id="subject"></span></div>
          <div><strong>Total Student Evaluated:</strong> <span id="students"></span></div>
        </div>

        <div class="legend">
          <div>5 - Strongly Agree</div>
          <div>4 - Agree</div>
          <div>3 - Neutral</div>
          <div>2 - Disagree</div>
          <div>1 - Strongly Disagree</div>
        </div>

        <table>
          <thead>
            <tr>
              <th>Questions</th>
              <th>1</th>
              <th>2</th>
              <th>3</th>
              <th>4</th>
              <th>5</th>
            </tr>
          </thead>
          <tbody id="resultsBody"></tbody>
        </table>

        <div>
          <div class="total-ratings" id="totalRatings"></div>
        </div>

        <div class="rating-row">
          <button class="show-students-btn" onclick="showStudentResults(currentTeacherId)" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Show All Student Results</button>
          <button class="print-btn" onclick="window.print()">Print Evaluation Report</button>
          <form action="reset_evaluation.php" method="POST" style="display:inline;">
            <button type="submit" class="print-btn" style="background:#e74c3c;">Reset Evaluation</button>
          </form>
        </div>
      </div>
    </div>
  </div>

<!-- Success Reset Modal -->
<div id="resetSuccessModal">
  <div id="resetSuccessContent">
    <h3>✅ Evaluation Reset</h3>
    <p>All evaluations have been successfully moved to past records.</p>
    <button onclick="closeResetModal()">OK</button>
  </div>
</div>


<!-- Modal for single student -->
<div id="studentModal">
    <div id="studentModalContent">
        <button id="closeModalBtn" onclick="closeModal()">Close</button>
        <h3>Student Evaluation Details</h3>
        <select id="studentSelect" onchange="renderStudentTable()"></select>
        <table id="studentTable">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
let currentTeacherId = <?php echo json_encode($teachers[0] ?? ''); ?>;
const evaluations = <?php echo json_encode($evaluations); ?>;

function getRemark(average) {
  if (average >= 4.5) return "Outstanding Performance";
  if (average >= 3.5) return "Excellent Performance";
  if (average >= 2.5) return "Average Performance";
  if (average >= 1.5) return "Below Average Performance";
  return "Poor";
}

function showResults(teacherId, event) {
  currentTeacherId = teacherId;
  document.querySelectorAll('.teacher-btn').forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');

  const data = evaluations[teacherId];
  document.getElementById("className").textContent = Object.keys(data.sections).join(", ");
  document.getElementById("subject").textContent = data.subject;
  document.getElementById("students").textContent = data.students;

  const tbody = document.getElementById("resultsBody");
  const totalRatingsDiv = document.getElementById("totalRatings");
  tbody.innerHTML = "";

  let totalScore = 0;
  let totalResponses = 0;

  data.results.forEach(item => {
    const tr = document.createElement("tr");
    const tdLabel = document.createElement("td");
    tdLabel.innerHTML = `<strong> ${item.question_text}</strong>`;
    tr.appendChild(tdLabel);

    for (let i = 1; i <= 5; i++) {
      const td = document.createElement("td");
      const count = item.counts[i] || 0;
      const percent = data.students ? ((count / data.students) * 100).toFixed(0) + "%" : "0%";
      td.textContent = percent;
      tr.appendChild(td);

      totalScore += i * count;
      totalResponses += count;
    }

    

    tbody.appendChild(tr);
  });

  const avg = totalResponses ? (totalScore / totalResponses).toFixed(2) : 0;
  totalRatingsDiv.textContent = `Total Rating: ${avg} - ${getRemark(avg)}`;
}

function showStudentResults(teacherId) {
  const modal = document.getElementById('studentModal');
  const select = document.getElementById('studentSelect');
  const tbody = document.querySelector('#studentTable tbody');

  const students = Object.keys(evaluations[teacherId].student_answers || {});
  select.innerHTML = "";
  tbody.innerHTML = "";

  if (students.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="3">No student evaluations yet.</td>`;
    tbody.appendChild(tr);
  } else {
    students.forEach(student => {
      const option = document.createElement('option');
      option.value = student;
      option.textContent = student;
      select.appendChild(option);
    });
    renderStudentTable(); // display first student by default
  }

  modal.style.display = "block";
}

function renderStudentTable() {
  const teacherData = evaluations[currentTeacherId];
  const student = document.getElementById('studentSelect').value;
  const answers = teacherData.student_answers[student] || [];

  const tbody = document.querySelector('#studentTable tbody');
  tbody.innerHTML = "";

  // keep a set to avoid repeating feedback
  const feedbackSet = new Set();

  answers.forEach(a => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${a.question_text}</td>
                    <td>${a.answer}</td>
                    <td>${feedbackSet.has(a.feedback) ? '' : (a.feedback || '-')}</td>`;
    tbody.appendChild(tr);

    if (a.feedback) feedbackSet.add(a.feedback); // store feedback to avoid repetition
  });
}

function closeModal() {
  document.getElementById('studentModal').style.display = "none";
}

// Close modal on outside click
window.onclick = function(event) {
  const modal = document.getElementById('studentModal');
  if (event.target == modal) modal.style.display = "none";
}

// Initial Load
const firstBtn = document.querySelector('.teacher-btn.active');
if (firstBtn) showResults(firstBtn.textContent, { target: firstBtn });
// Show Reset Success Modal if ?reset=success in URL
function getQueryParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(param);
}

if (getQueryParam("reset") === "success") {
  document.getElementById("resetSuccessModal").style.display = "block";
}

function closeResetModal() {
  document.getElementById("resetSuccessModal").style.display = "none";
  // Clean query param from URL
  window.history.replaceState({}, document.title, "evaluation_result.php");
}
</script>

</body>
</html>
