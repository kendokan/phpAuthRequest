<?php
session_start();

// We want to check auth status as quickly as possible, and before any headers are sent, so do this first
if (isset($_REQUEST['status']))
  if(getAuthStatus())
    exit(http_response_code(200));
  else
    exit(http_response_code(401));
elseif(isset($_REQUEST['login']))
  login();
elseif(isset($_REQUEST['logout']))
  logout();
else {
  echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="css/util.css">
<link rel="stylesheet" type="text/css" href="css/main.css">
<title>Authenticate</title>
</head>
<body onLoad="document.forms.login.username.focus()">
EOT;

$loginForm = <<<EOT
<div class="limiter">
<div class="container-login100">
<div class="wrap-login100 p-t-90 p-b-30">
<form class="login100-form validate-form" name="login" action="{$_SERVER['PHP_SELF']}" method="post">
<input type="hidden" name="login">
<div class="wrap-input100 validate-input m-b-16" data-validate="Please enter your username">
<input class="input100" type="text" name="username" placeholder="Username">
<span class="focus-input100"></span>
</div>
<div class="wrap-input100 validate-input m-b-20" data-validate = "Please enter password">
<span class="btn-show-pass">
<i class="fa fa fa-eye"></i>
</span>
<input class="input100" type="password" name="password" placeholder="Password">
<span class="focus-input100"></span>
</div>
<div class="container-login100-form-btn">
<button class="login100-form-btn">
Login
</button>
</div>
</form>
</div>
</div>
</div>
EOT;

$logoutForm = <<<EOT
<div class="limiter">
<div class="container-login100">
<div class="wrap-login100 p-t-90 p-b-30">
<form class="login100-form validate-form" action="{$_SERVER['PHP_SELF']}" method="post">
<input type="hidden" name="logout">
<div class="container-login100-form-btn">
<button class="login100-form-btn">
Logout
</button>
</div>
</form>
</div>
</div>
</div>
EOT;

  if (getAuthStatus())
    echo $logoutForm;
  else
    echo $loginForm;

  echo <<<"EOT"
<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
EOT;
}

function login() {
  if (!isset($_REQUEST['username']))
    die('User name not provided.');
  else {
    $username = $_REQUEST['username'];
  }

  if (!isset($_REQUEST['password']))
    die('Password not provided.');
  else {
    $password = $_REQUEST['password'];
  }

  if (password_verify($password, getPasswordHash($username))) {
    session_regenerate_id(true);
    $_SESSION['phpAuthRequest-Authenticated'] = true;
    $_SESSION['phpAuthRequest-Timestamp'] = time();
    header("Location: {$_SERVER['PHP_SELF']}");
  } else {
    logout();
  }
}

// Clears and kills the session
function logout() {
  $_SESSION = array();

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
    header("Location: {$_SERVER['PHP_SELF']}");
  }

  session_destroy();
}

// Fetches password hash from sqlite
function getPasswordHash($username) {
  $pdo = new \PDO('sqlite:phpAuthRequest.sqlite3');
  $stmt = $pdo->prepare('SELECT password FROM users WHERE username=:username;');
  $stmt->bindValue(':username', $username);
  $stmt->execute();
  return $stmt->fetchColumn();
}

// Checks if session is authenticated
function getAuthStatus() {
  if (isset($_SESSION['phpAuthRequest-Authenticated'])) {
    if (($_SESSION['phpAuthRequest-Timestamp'] + 86400) > time()) {
      $_SESSION['phpAuthRequest-Timestamp'] = time();
      return true;
    }
  }
}
?>
