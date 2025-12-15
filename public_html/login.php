<?php
session_start();
include_once 'logTracker.php';

$servername = "127.0.0.1";
$username_db = "gbrugnara"; 
$password_db = "KeRjnLwqj+rTTG3E";
$dbname = "db_gbrugnara";
$conn = new mysqli($servername, $username_db, $password_db, $dbname, null, "/run/mysql/mysql.sock");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$message = "";

// LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
  
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

    $user_id = $row['USER_ID'];
    $privilege_level = (int)$row['privilege'];
    $event_message = ($privilege_level === 1) ? "Admin User ID:" .$user_id . " logged in." : 
                                                "Customer User ID:" .$user_id . " logged in.";

    log_event("LOGIN_SUCCESS", $event_message, $user_id);                                         
    if ((int)$row['privilege'] === 1) {
      header("Location: admin.php");
    } else {
      header("Location: user.php");
    }
    exit();
  } else {
    $message = "<p class='error'>Invalid User ID or password</p>";
    log_event("LOGIN_FAILURE", "Failed login attempt for User ID: " . $user_id_input, $user_id_input);
  }
  $stmt->close();
}

// Return messages from registration
if (isset($_GET['msg'])) {
    $cls = ($_GET['status'] === 'success') ? 'success' : 'error';
    $message = "<p class='$cls'>" . htmlspecialchars($_GET['msg']) . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login & Register - SkyBook</title>
  <link rel="stylesheet" href="style.css">
  <style>
      .auth-wrapper { display: flex; gap: 40px; justify-content: center; flex-wrap: wrap; }
      .login-container { margin: 0; flex: 1; min-width: 300px; max-width: 400px; }
      h2 { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <header>
    <div class="logo-wrapper">
      <img src="images/logo.JPG" alt="SkyBook Logo" class="logo-image">
      <div class="logo">SkyBook</div>
      <a href="index.php" class="abort-button">Back to Home</a>
    </div>
  </header>

  <div style="padding: 40px;">
      <?php echo $message; ?>
      
      <div class="auth-wrapper">
          <div class="login-container">
            <h2>Login</h2>
            <p>Access your account</p>
            <form method="post" action="login.php">
              <input type="hidden" name="action" value="login">
              <label>User ID</label>
              <input type="text" name="username" placeholder="e.g. 1002" required>
              <label>Password</label>
              <input type="password" name="password" placeholder="Password" required>
              <button type="submit" class="btn-primary" style="width:100%; margin-top:10px;">Login</button>
            </form>
          </div>

          <div class="login-container">
            <h2>Register</h2>
            <p>New Customer? Create an account.</p>
            <form method="post" action="manage_users.php">
              <input type="hidden" name="action" value="register_customer">
              
              <label>Choose User ID (Number)</label>
              <input type="number" name="new_user_id" placeholder="e.g. 2025" required>
              
              <label>Choose Password</label>
              <input type="password" name="new_password" placeholder="Password" required>
              
              <button type="submit" class="btn-secondary" style="width:100%; margin-top:10px; background-color: var(--secondary-color);">Register</button>
            </form>
          </div>
      </div>
  </div>
</body>
</html>