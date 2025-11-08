<?php
session_start();

$servername = "127.0.0.1";
$username_db = "gbrugnara"; 
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
  $user_id_input = $_POST['username']; 
  $pass_input = $_POST['password'];

  
  $sql = "SELECT USER_ID, privilege, pwd FROM Users WHERE USER_ID=? AND pwd=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $user_id_input, $pass_input); 
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    
    $_SESSION['user_id'] = $row['USER_ID']; 
    $_SESSION['privilege'] = $row['privilege']; 

    
    if ((int)$row['privilege'] === 1) {
      header("Location: admin.php");
      exit();
    } else {
      header("Location: user.php");
      exit();
    }
  } else {
    $message = "<p class='error'>Invalid User ID or password</p>";
  }

  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - SkyBook</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="logo-wrapper">
      <img src="images/logo.JPG" alt="SkyBook Logo" class="logo-image">
      <div class="logo">SkyBook</div>
    </div>
  </header>

  <div class="login-container">
    <h2>Login - credentials in homepage - under global destinations</h2>
    <form method="post" action="login.php">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <?php echo $message; ?>

    <a href="index.php" class="abort-button">Abort Login</a>
  </div>
</body>
</html>
