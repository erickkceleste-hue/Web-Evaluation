<?php
$conn = new mysqli("localhost", "root", "", "user");

// Fetch sections from teacher_sections table
$sections = [];
$sectionQuery = $conn->query("SELECT DISTINCT section FROM teacher_sections ORDER BY section ASC");
if ($sectionQuery && $sectionQuery->num_rows > 0) {
    while ($row = $sectionQuery->fetch_assoc()) {
        $sections[] = $row['section'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST["role"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    if ($role == "student") {
        $name = $_POST["name"];
        $idnumber = $_POST["idnumber"];
        $section = $_POST["section"];

        $check = $conn->prepare("SELECT * FROM users WHERE idnumber = ?");
        $check->bind_param("s", $idnumber);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Student with this ID number is already registered!'); window.history.back();</script>";
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users (name, idnumber, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $idnumber, $password, $role);
        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("INSERT INTO student (id_number, name, section, role) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("ssss", $idnumber, $name, $section, $role);
            $stmt2->execute();
            echo "<script>alert('Student registered successfully!'); window.location.href='login.php';</script>";
        }

    } elseif ($role == "teacher") {
        $name = $_POST["name"];
        $email = $_POST["email"];
        $position = $_POST["position"];

        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Teacher with this email is already registered!'); window.history.back();</script>";
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("INSERT INTO teacher (name, position) VALUES (?, ?)");
            $stmt2->bind_param("ss", $name, $position);
            $stmt2->execute();
            echo "<script>alert('Teacher registered successfully!'); window.location.href='login.php';</script>";
        }

    } elseif ($role == "admin") {
        $email = $_POST["email"];

        $adminCount = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'admin'");
        $countResult = $adminCount->fetch_assoc();

        if ($countResult['count'] >= 2) {
            echo "<script>alert('Maximum of 2 admins already registered.'); window.history.back();</script>";
            exit();
        }

        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Admin with this email is already registered!'); window.history.back();</script>";
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $password, $role);
        if ($stmt->execute()) {
            echo "<script>alert('Admin registered successfully!'); window.location.href='login.php';</script>";
        }
    }
}
?>






<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register | Teacher Evaluation System</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      background: #f2f2f2;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .main-container {
      display: flex;
      width: 90%;
      max-width: 1000px;
      background-color: white;
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .left-panel {
      background-color: #0000ff;
      color: white;
      padding: 40px 30px;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .left-panel img {
      width: 150px;
      height: auto;
      margin-bottom: 20px;
    }

    .left-panel h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .left-panel p {
      font-size: 16px;
    }

    .right-panel {
      background-color: #ffffff;
      flex: 1;
      padding: 50px 30px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .right-panel h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 24px;
      font-weight: bold;
    }

    select, input {
      width: 100%;
      padding: 12px 15px;
      margin: 12px 0;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      background-color: #dcdcdc;
    }

    input:focus, select:focus {
      outline: none;
      background-color: #eee;
    }

    button {
      width: 100%;
      background: #0000ff;
      color: #fff;
      border: none;
      padding: 12px;
      margin-top: 10px;
      font-weight: bold;
      border-radius: 10px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #0000cc;
    }

    .form-footer {
      text-align: center;
      margin-top: 15px;
      font-size: 0.95rem;
    }

    .form-footer a {
      color: #0000ff;
      text-decoration: underline;
    }

    .form-footer a:hover {
      color: #0000cc;
    }

    .hidden {
      display: none;
    }

    @media (max-width: 768px) {
      .main-container {
        flex-direction: column;
        border-radius: 0;
      }

      .left-panel, .right-panel {
        flex: none;
        width: 100%;
        padding: 30px 20px;
      }

      .left-panel {
        border-radius: 0;
      }
    }
  </style>

  <script>
    function toggleFields() {
    const role = document.getElementById("role").value;
    document.getElementById("name-field").style.display = (role === "student" || role === "teacher") ? "block" : "none";
    document.getElementById("idnumber-field").style.display = (role === "student") ? "block" : "none";
    document.getElementById("section-field").style.display = (role === "student") ? "block" : "none";
    document.getElementById("position-field").style.display = (role === "teacher") ? "block" : "none";
    document.getElementById("email-field").style.display = (role === "admin" || role === "teacher") ? "block" : "none";
  }

  window.onload = function () {
    toggleFields();
  };
  </script>
</head>
<body>
  <div class="main-container">
    <div class="left-panel">
      <img src="Image/ASASHS Logo.png" alt="School Logo" />
      <h1>Welcome to <strong>ASASHS</strong></h1>
      <p>STUDENT TO TEACHER EVALUATION SYSTEM</p>
    </div>

    <div class="right-panel">
      <h2>REGISTER</h2>
      <form method="POST" action="register.php">
        <div id="name-field" class="hidden">
          <input type="text" name="name" placeholder="Full Name" />
        </div>

        <div id="idnumber-field" class="hidden">
          <input type="text" name="idnumber" placeholder="Student ID Number" />
        </div>

        <div id="section-field" class="hidden">
          <select name="section">
            <option value="">-- Select Section --</option>
            <?php foreach ($sections as $sec): ?>
              <option value="<?php echo htmlspecialchars($sec); ?>"><?php echo htmlspecialchars($sec); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="position-field" class="hidden">
          <input type="text" name="position" placeholder="Position" />
        </div>

        <div id="email-field" class="hidden">
          <input type="email" name="email" placeholder="Email" />
        </div>

        <input type="password" name="password" placeholder="Password" required />

        <select name="role" id="role" onchange="toggleFields()" required>
          <option value="">Select Role</option>
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
          <option value="admin">Admin</option>
        </select>

        <button type="submit">Register</button>

        <div class="form-footer">
          Already have an account? <a href="login.php">Login here</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>


