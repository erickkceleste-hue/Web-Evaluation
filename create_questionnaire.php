<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "user");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add"])) {
        $question = trim($_POST["question"]);
        $scale = $_POST["scale"];
        if (!empty($question)) {
            $stmt = $conn->prepare("INSERT INTO questionnaire (question, scale) VALUES (?, ?)");
            $stmt->bind_param("ss", $question, $scale);
            $stmt->execute();
        }
    } elseif (isset($_POST["update"])) {
        $id = intval($_POST["id"]);
        $question = trim($_POST["question"]);
        $scale = $_POST["scale"];
        $stmt = $conn->prepare("UPDATE questionnaire SET question = ?, scale = ? WHERE id = ?");
        $stmt->bind_param("ssi", $question, $scale, $id);
        $stmt->execute();
    } elseif (isset($_POST["delete"])) {
        $id = intval($_POST["id"]);
        $conn->query("DELETE FROM questionnaire WHERE id = $id");
    }
}

$defaultTab = isset($_GET['tab']) && $_GET['tab'] === 'existing' ? 'existing' : 'create';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Questionnaire</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* All your combined styles (same from previous messages) */
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
    .logout { background: #e74c3c; }
    .logout:hover { background: #c0392b; }

    .topbar {
        position: fixed;
        top: 0;
        left: 250px;
        right: 0;
        padding: 30px 20px;
        background: #007bff;
        color: #fff;
        display: flex;
        justify-content: space-between;
        z-index: 100;
    }
    .topbar .title { font-size: 20px; font-weight: bold; }
    .topbar .admin { margin-right: 30px; font-size: 16px; font-weight: 500; }

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
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
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
        margin: 0 auto 30px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-group input, .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .btn {
        width: 100%;
        padding: 8px 15px;
        background: #5F4DFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 15px;
        font-size: 15px;
    }
    .btn:hover { background: #2980b9; }
    .btn.delete-btn { background: #e74c3c; }
    .btn.delete-btn:hover { background: #c0392b; }
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
    .big-btn:hover { background: #2980b9; }

    #scale-description {
        background: #ecf0f1;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
    }

    .question-item {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    .question-item input{
        padding: 15px;
        width: 100%;
    }
    select{
        text-align: center;
        margin-top: 10px;
        width: 100px;
        padding: 8px;
    }

    .question-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        body { flex-direction: column; }
        .sidebar {
            transform: translateX(-100%);
            position: absolute;
        }
        .sidebar.show { transform: translateX(0); }
        .topbar { left: 0; }
        .menu-toggle { display: flex; }
        .main-content { margin-left: 0; }
    }
  </style>
</head>
<body>

<div class="menu-toggle" onclick="toggleSidebar()"><span></span><span></span><span></span></div>

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
    <div class="admin"> Administrator</div>
</div>

<div class="main-content">
    <!-- Tab Buttons -->
    <div class="tab-buttons">
        <button class="tab-button <?php echo $defaultTab === 'create' ? 'active' : 'inactive'; ?>" onclick="showTab('create')">Create</button>
        <button class="tab-button <?php echo $defaultTab === 'existing' ? 'active' : 'inactive'; ?>" onclick="showTab('existing')">Existing</button>
    </div>

    <!-- Create Tab -->
    <div id="create" class="tab-content" style="<?php echo $defaultTab === 'create' ? '' : 'display:none'; ?>">
        <div class="container">
            <h2>Create Evaluation Questionnaire</h2>
            <form method="post">
                <div class="form-group">
                    <label>Question:</label>
                    <input type="text" name="question" required>
                </div>

                <div class="form-group">
                    <label>Scale Description:</label>
                    <div id="scale-description">Select a scale to see the criteria.</div>
                </div>

                <div class="form-group">
                    <label>Rating Scale:</label>
                    <select name="scale" id="scale-select">
                        <option value="1-5">1-5</option>
                        
                    </select>
                </div>

                <button type="submit" name="add" class="big-btn">Add Question</button>
            </form>
        </div>
    </div>

    <!-- Existing Tab -->
    <div id="existing" class="tab-content" style="<?php echo $defaultTab === 'existing' ? '' : 'display:none'; ?>">
        <div class="container">
            <h3>Existing Questions</h3>
            <div class="question-list">
                <?php
                $result = $conn->query("SELECT * FROM questionnaire ORDER BY id DESC");
                while ($row = $result->fetch_assoc()):
                ?>
                <div class="question-item">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="text" name="question" value="<?= htmlspecialchars($row['question']) ?>" required>
                        <select name="scale">
                            <option value="1-5" <?= $row['scale'] == '1-5' ? 'selected' : '' ?>>1-5</option>
                        </select>
                        <div class="question-actions">
                            <button type="submit" name="update" class="btn">Update</button>
                            <button type="submit" name="delete" class="btn delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
                        </div>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
}

function showTab(tab) {
    const createTab = document.getElementById("create");
    const existingTab = document.getElementById("existing");
    const buttons = document.querySelectorAll(".tab-button");

    if (tab === "create") {
        createTab.style.display = "block";
        existingTab.style.display = "none";
        buttons[0].classList.add("active");
        buttons[0].classList.remove("inactive");
        buttons[1].classList.remove("active");
        buttons[1].classList.add("inactive");
    } else {
        createTab.style.display = "none";
        existingTab.style.display = "block";
        buttons[1].classList.add("active");
        buttons[1].classList.remove("inactive");
        buttons[0].classList.remove("active");
        buttons[0].classList.add("inactive");
    }

    const newUrl = window.location.pathname + '?tab=' + tab;
    history.replaceState(null, '', newUrl);
}

// Scale description update
const scaleSelect = document.getElementById("scale-select");
const descriptionDiv = document.getElementById("scale-description");

const descriptions = {
    "1-5": `<strong>1-5 Scale Criteria:</strong><br>5 - Strongly Agree<br>4 - Agree<br>3 - Neutral<br>2 - Disagree<br>1 - Strongly Disagree`,
};

scaleSelect.addEventListener("change", () => {
    const selected = scaleSelect.value;
    descriptionDiv.innerHTML = descriptions[selected] || "Select a scale to see the criteria.";
});

scaleSelect.dispatchEvent(new Event("change"));
</script>

</body>
</html>
