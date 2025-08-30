<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "teacher") {
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

// teacher name from session
$teacherName = $conn->real_escape_string($_SESSION["name"]);

// ✅ First, fetch unique student counts per section
$sqlStudents = "
    SELECT ts.section, COUNT(DISTINCT ea.student_name) AS total_students
    FROM teacher_sections ts
    LEFT JOIN evaluation_answers ea 
      ON ea.teacher_name = ts.teacher_name 
     AND ea.section = ts.section
    WHERE ts.teacher_name = '$teacherName'
    GROUP BY ts.section
";
$resStudents = $conn->query($sqlStudents);
$studentCounts = [];
while ($row = $resStudents->fetch_assoc()) {
    $studentCounts[$row["section"]] = (int)$row["total_students"];
}

// ✅ Now, fetch question/answer breakdown
$sql = "
    SELECT ts.section, ts.subject,
           ea.question_id, ea.answer,
           q.question
    FROM teacher_sections ts
    LEFT JOIN evaluation_answers ea 
      ON ea.teacher_name = ts.teacher_name 
     AND ea.section = ts.section
    LEFT JOIN questionnaire q 
      ON ea.question_id = q.id
    WHERE ts.teacher_name = '$teacherName'
    ORDER BY ts.section, ts.subject, ea.question_id
";
$res = $conn->query($sql);

// organize results
$classes = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $classKey = $row["section"];
        if (!isset($classes[$classKey])) {
            $classes[$classKey] = [
                "subject" => $row["subject"],
                // ✅ use unique count from earlier query
                "total_students" => $studentCounts[$classKey] ?? 0,
                "questions" => []
            ];
        }
        if ($row["answer"] !== null) {
            $qid = $row["question_id"];
            $qText = $row["question"] ?: "Question " . $qid; 
            if (!isset($classes[$classKey]["questions"][$qText])) {
                $classes[$classKey]["questions"][$qText] = [1=>0,2=>0,3=>0,4=>0,5=>0];
            }
            $classes[$classKey]["questions"][$qText][(int)$row["answer"]] += 1;
        }
    }
}

// compute percentages (no change)
foreach ($classes as $section => &$c) {
    foreach ($c["questions"] as $qText => &$answers) {
        $sum = array_sum($answers);
        if ($sum > 0) {
            foreach ($answers as $rate => $cnt) {
                $answers[$rate] = round(($cnt / $sum) * 100) . "%";
            }
        } else {
            foreach ($answers as $rate => $cnt) {
                $answers[$rate] = "0%";
            }
        }
    }
}
unset($c);
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Evaluation System</title>
<style>
  *, *::before, *::after {
    box-sizing: border-box;
  }

  body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
      Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
    background: #e6e6e6;
    color: #000;
  }

  a {
    color: #0047e6;
    text-decoration: none;
  }
  a:hover, a:focus {
    text-decoration: underline;
  }

  /* Layout containers */
  .container {
    display: flex;
    min-height: 100vh;
  }

  /* Sidebar */
  nav.sidebar {
    width: 250px;
    background-color: #444444;
    color: #fff;
    padding: 30px 15px 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .sidebar-logo {
    width: 80px;
    height: 80px;
    margin-bottom: 30px;
    flex-shrink: 0;
    border-radius: 50%;
    overflow: hidden;
    background-color: #fff;
  }

  .sidebar-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }

  /* Sidebar nav links */
  .nav-links {
    width: 100%;
  }

  .nav-links button, 
  .nav-links a {
    display: block;
    width: 100%;
    background-color: #666666;
    border: none;
    padding: 12px 20px;
    margin-bottom: 12px;
    color: #eee;
    text-align: left;
    font-size: 16px;
    border-radius: 3px;
    cursor: pointer;
    outline-offset: 2px;
  }

  .nav-links button:hover,
  .nav-links a:hover,
  .nav-links button:focus,
  .nav-links a:focus {
    background-color: #888888;
    color: white;
  }

  .nav-links button.active {
    background-color: #b0b0b0;
    color: #0047e6;
    border-left: 5px solid #0047e6;
    cursor: default;
  }

  /* Main content area */
  main.content-area {
    flex-grow: 1;
    background-color: #e6e6e6;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  /* Header bar */
  .header { background-color: #0058ff; color: white; height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 30px 30px; position: relative; }

    .header-title { font-size: 1.2rem; font-weight: bold; margin-left: 6px; }

    /* Profile dropdown styles */
    .user-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; position: relative; margin-right: 40px; }
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

  /* Page content area below header */
  section.page-content {
    padding: 20px 30px;
    flex-grow: 1;
    overflow-y: auto;
  }

  section.page-content > h2 {
    font-weight: 600;
    margin-bottom: 5px;
  }

  section.page-content hr {
    border: none;
    border-bottom: 1px solid #c1c1d0;
    margin-bottom: 20px;
  }

  /* Results / Ratings Container */
  .results-ratings-container {
    display: flex;
    gap: 18px;
  }

  /* Classes list panel */
  .classes-list {
    background-color: #ccc;
    border-radius: 10px;
    padding: 15px;
    width: 280px;
    font-size: 15px;
  }

  .classes-list a {
    display: block;
    padding: 8px 12px;
    border-radius: 10px;
    margin-bottom: 10px;
    background-color: #d1d1d1;
    color: #0047e6;
    cursor: pointer;
  }

  .classes-list a.active,
  .classes-list a:hover,
  .classes-list a:focus {
    background-color: white;
    border-left: 6px solid #0047e6;
    color: #0047e6;
    text-decoration: underline;
  }

  /* Evaluation report panel */
  .evaluation-report {
    background-color: white;
    border-radius: 15px;
    padding: 25px 30px 30px;
    flex-grow: 1;
    font-size: 15px;
    box-shadow: 0 0 8px rgb(0 0 0 / 0.1);
    min-width: 320px;
  }

  .evaluation-report h3 {
    text-align: center;
    margin: 0 0 15px;
    font-weight: 600;
    font-size: 18px;
  }

  .eval-summary {
    display: flex;
    justify-content: space-between;
    font-weight: 500;
    margin-bottom: 16px;
    font-size: 14.5px;
  }
  .eval-summary .left,
  .eval-summary .right {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  /* Evaluation rating scale label */
  .rating-scale {
    background-color: #c8c8c8;
    border-radius: 5px;
    font-size: 14px;
    color: #222;
    padding: 15px 15px;
    line-height: 1.1;
    margin-bottom: 16px;
    text-align: center;
    user-select: none;
    display: flex;
    justify-content: space-between;
  }

  /* Table style */
  table.evaluation-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
  }

  thead tr {
    background-color: #666666;
    color: white;
  }

  thead th {
    padding: 20px 20px;
    
  }
  table th[scope="row"] {
  text-align: left;
  padding-left: 16px;
}
table th[scope="row"] {
  text-align: left;
  padding-left: 16px;
}

  thead th:first-child {
    text-align: left;
  }

  thead th:not(:first-child) {
    width: 8%;
    font-weight: 600;
  }

  tbody td {
    padding: 12px 10px;
    border-bottom: 1px solid #bbb;
    text-align: center;
  }

  tbody td:first-child {
    text-align: left;
  }

  tbody tr:not(:last-child) td {
    border-bottom: 1px solid #aaa;
  }

  /* Print button style */
  .print-button {
    margin-top: 20px;
    float: right;
    background-color: #0047e6;
    color: white;
    border: none;
    padding: 10px 19px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
  }

  .print-button:hover,
  .print-button:focus {
    background-color: #0033b7;
    outline: none;
  }
  .rating-print-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 30px;
}

.total-rating {
  font-weight: 600;
  font-size: 15px;
  color: #333;
}
  
  /* Small responsive */
  @media (max-width: 768px) {
    nav.sidebar {
      width: 75px;
      padding: 20px 10px 60px;
    }
    .sidebar-logo {
      width: 50px;
      height: 50px;
      margin-bottom: 20px;
    }
    .nav-links button, 
    .nav-links a {
      font-size: 0;
      padding: 12px 0;
      margin-bottom: 15px;
      border-radius: 8px;
      text-indent: -9999px;
      position: relative;
    }
    .nav-links button.active,
    .nav-links a.active {
      border-left: none;
      border-radius: 8px;
    }
    .nav-links button.active::after,
    .nav-links a.active::after {
      content: "";
      position: absolute;
      left: 10px;
      top: 15%;
      bottom: 15%;
      width: 5px;
      background: #0047e6;
      border-radius: 2px;
    }
    .container {
      flex-direction: column;
    }
    main.content-area {
      min-height: auto;
    }
    .results-ratings-container {
      flex-direction: column;
    }
    .classes-list {
      width: 100%;
      margin-bottom: 20px;
      border-radius: 6px;
    }
    .evaluation-report {
      width: 100%;
      border-radius: 6px;
      padding: 18px 20px 20px;
      font-size: 14px;
    }
  }
</style>
</head>
<body>
<main class="content-area">
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

  <section class="page-content" aria-labelledby="pageTitle">
    <h2 id="pageTitle">Results / Ratings</h2>
    <hr />

    <div class="results-ratings-container">
      <!-- Class list -->
      <nav class="classes-list" aria-label="Class selection">
        <?php $first = true; foreach ($classes as $section => $info): ?>
          <a href="#" class="<?php echo $first ? 'active' : ''; ?>" data-section="<?php echo htmlspecialchars($section); ?>">
            <?php echo htmlspecialchars($section); ?>
          </a>
        <?php $first = false; endforeach; ?>
      </nav>

      <!-- Evaluation report -->
      <section class="evaluation-report" aria-labelledby="evaluationTitle" role="region">
        <h3 id="evaluationTitle">Evaluation Report</h3>
        <div class="eval-summary" aria-live="polite">
          <div class="left">
            <div><strong>Class:</strong> <span id="className"></span></div>
            <div><strong>Total Student Evaluated:</strong> <span id="totalStudents"></span></div>
          </div>
          <div class="right">
            <div><strong>Subject:</strong> <span id="subjectName"></span></div>
          </div>
        </div>

        <div class="rating-scale">
          <span>5 - Strongly Agree</span>
          <span>4 - Agree</span>
          <span>3 - Neutral</span>
          <span>2 - Disagree</span>
          <span>1 - Strong Disagree</span>
        </div>

        <table class="evaluation-table">
          <thead>
            <tr>
              <th scope="col">Criteria</th>
              <th scope="col">1</th>
              <th scope="col">2</th>
              <th scope="col">3</th>
              <th scope="col">4</th>
              <th scope="col">5</th>
            </tr>
          </thead>
          <tbody id="tableBody">
          </tbody>
        </table>

        <div class="rating-print-container">
          <div class="total-rating"><strong>Total Rating:</strong> <span id="totalRating"></span></div>
          <button class="print-button" type="button" id="printReportBtn">Print Evaluation Report</button>
        </div>
      </section>
    </div>
  </section>
</main>

<script>
const data = <?php echo json_encode($classes); ?>;

function updateReport(section) {
  const info = data[section];
  if (!info) return;

  document.getElementById("className").textContent = section;
  document.getElementById("totalStudents").textContent = info.total_students;
  document.getElementById("subjectName").textContent = info.subject;

  const tbody = document.getElementById("tableBody");
  tbody.innerHTML = "";
  let totalScore = 0, count = 0;

  Object.keys(info.questions).forEach((qText) => {
    const row = document.createElement("tr");
    const th = document.createElement("th");
    th.scope = "row";
    th.textContent = qText;
    row.appendChild(th);

    for (let rate = 1; rate <= 5; rate++) {
      const td = document.createElement("td");
      td.textContent = info.questions[qText][rate]; // raw count
      row.appendChild(td);

      const num = parseInt(info.questions[qText][rate]) || 0;
      totalScore += num * rate;
      count += num;
    }
    tbody.appendChild(row);
  });

  let avg = count ? totalScore / count : 0; // proper average
  let label = "";
  if (avg >= 4.5) label = "Outstanding Performance";
  else if (avg >= 3.5) label = "Excellent Performance";
  else if (avg >= 2.5) label = "Average Performance";
  else if (avg >= 1.5) label = "Below Average Performance";
  else label = "Poor";

  document.getElementById("totalRating").textContent = avg.toFixed(2) + " " + label;
}

// init with first section
const firstLink = document.querySelector(".classes-list a");
if (firstLink) {
  updateReport(firstLink.dataset.section);
}

document.querySelectorAll(".classes-list a").forEach(link => {
  link.addEventListener("click", e => {
    e.preventDefault();
    document.querySelectorAll(".classes-list a").forEach(l => l.classList.remove("active"));
    link.classList.add("active");
    updateReport(link.dataset.section);
  });
});

document.getElementById("printReportBtn").addEventListener("click", () => window.print());


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

  // Profile and Logout button functionality
  document.getElementById("profileBtn").addEventListener("click", function () {
    window.location.href = "profile.php"; // Adjust this to your actual profile page
  });

  document.getElementById("logoutBtn").addEventListener("click", function () {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "logout.php"; // Make sure logout.php destroys the session
    }
  });
  const profileImg = document.querySelector(".user-profile img");
const fileInput = document.getElementById("profileImageInput");
const form = document.getElementById("uploadProfileForm");

// Click profile image to upload
profileImg.addEventListener("click", () => {
  fileInput.click();
});


</script>
</body>
</html>
