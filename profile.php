<?php
session_start();

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit;
}


// Get student info from session
$name = $_SESSION["name"];
$id_number = $_SESSION["id_number"];
$section = $_SESSION["section"] ?? "Not set";
$profileImage = $_SESSION["profile_image"] ?? "Image/profile icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
      padding: 40px;
      margin: 0;
    }

    .profile-box {
      max-width: 500px;
      margin: auto;
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }

    .profile-box img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #0000ff;
      margin-bottom: 20px;
    }

    .profile-box h2 {
      margin-bottom: 10px;
      color: #333;
    }

    .profile-box p {
      font-size: 16px;
      color: #555;
      margin: 5px 0;
    }

    .label {
      font-weight: bold;
      color: #000;
    }
  </style>
</head>
<body>

  <div class="profile-box">
    <img src="<?php echo $profileImage; ?>" alt="Profile Picture">
    <h2><?php echo htmlspecialchars($name); ?></h2>
    <p><span class="label">ID Number:</span> <?php echo htmlspecialchars($id_number); ?></p>
    <p><span class="label">Section:</span> <?php echo htmlspecialchars($section); ?></p>
  </div>

</body>
</html>
