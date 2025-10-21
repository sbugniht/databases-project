<?php
session_start();

$servername = "127.0.0.1";
$username = "gbrugnara";
$password = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username, $password, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $user = $_POST['username'];
  $pass = $_POST['password'];

  // Esempio tabella: Users(username, password, role)
  $sql = "SELECT * FROM Users WHERE username=? AND password=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $user, $pass);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['role'];

    // Redirect in base al ruolo
    if ($row['role'] === 'admin') {
      header("Location: admin.php");
      exit();
    } else {
      header("Location: user.php");
      exit();
    }
  } else {
    $message = "<p class='error'>Invalid username or password</p>";
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
    <h2>Login</h2>
    <form method="post" action="login.php">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <?php echo $message; ?>
  </div>
</body>
</html>
