<?php
session_start();
$conn = new mysqli("localhost", "root", "", "user");

$showErrorModal = false;
$showSuccessModal = false;
$redirectURL = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Check if email or ID number is used
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $username);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE idnumber = ?");
        $stmt->bind_param("s", $username);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Basic session data for all users
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        // ✅ Additional session data for student
        if ($user["role"] === "student") {
            $_SESSION["id_number"] = $user["idnumber"];

            // ✅ Check if 'section' key exists to avoid warning
            if (isset($user["section"])) {
                $_SESSION["section"] = $user["section"];
            } else {
                $_SESSION["section"] = null; 
            }
        }

        $showSuccessModal = true;

        // ✅ Role-based redirection
        switch ($user["role"]) {
            case "admin":
                $redirectURL = "admin_dashboard.php";
                break;
            case "teacher":
                $redirectURL = "dashboard_teacher.php";
                break;
            case "student":
                $redirectURL = "dashboard_student.php";
                break;
        }
    } else {
        $showErrorModal = true;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Teacher Evaluation System</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f2f2f2;
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

    input {
      width: 100%;
      padding: 12px 15px;
      margin: 12px 0;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      background-color: #dcdcdc;
    }

    input:focus {
      outline: none;
      background-color: #eee;
    }

    button {
      width: 100%;
      background: #0000ff;
      color: #fff;
      border: none;
      padding: 12px;
      margin-top: 15px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: bold;
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

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: white;
      margin: 15% auto;
      padding: 30px;
      border-radius: 15px;
      width: 90%;
      max-width: 400px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .modal-content h3 {
      margin-bottom: 20px;
      font-size: 20px;
    }

    .modal-content .close-btn {
      margin-top: 20px;
      background-color: #0000ff;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-size: 1rem;
      cursor: pointer;
    }

    .modal-content .close-btn:hover {
      background-color: #0000cc;
    }
  </style>
</head>
<body>

<div class="main-container">
  <div class="left-panel">
    <img src="Image/ASASHS Logo.png" alt="School Logo">
    <h1>Welcome to <strong>ASASHS</strong></h1>
    <p>STUDENT TO TEACHER EVALUATION SYSTEM</p>
  </div>

  <div class="right-panel">
    <h2>LOGIN</h2>
    <form method="POST" action="login.php">
      <input type="text" name="username" placeholder="Email or Student ID Number" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit">LOGIN</button>

      <div class="form-footer">
        Don’t have an account? <a href="register.php">Register here</a>
      </div>
    </form>
  </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="modal">
  <div class="modal-content">
    <h3>Invalid username or password</h3>
    <button class="close-btn" onclick="document.getElementById('errorModal').style.display='none'">Close</button>
  </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
  <div class="modal-content">
    <h3>Successfully Logged In!</h3>
    <button class="close-btn" disabled>Redirecting...</button>
  </div>
</div>

<?php if ($showErrorModal): ?>
<script>
  document.getElementById('errorModal').style.display = 'block';
</script>
<?php endif; ?>

<?php if ($showSuccessModal): ?>
<script>
  const successModal = document.getElementById('successModal');
  successModal.style.display = 'block';

  setTimeout(() => {
    window.location.href = "<?php echo $redirectURL; ?>";
  }, 2000);
</script>
<?php endif; ?>

</body>
</html>
