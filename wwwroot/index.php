<?php
session_start();

// $debug = true;
$baseUrl = '/auth';
$selfUrl = $baseUrl . $_SERVER['PHP_SELF'];


if (isset($_REQUEST['status']))
  if (getAuthStatus())
    exit(http_response_code(200));
  else
    exit(http_response_code(401));
elseif (isset($_REQUEST['login']))
  login($_REQUEST['username'], $_REQUEST['password']);
elseif(isset($_REQUEST['logout']))
  logout();


function login($username, $password) {
  if (password_verify($password, getPasswordHash($username))) {
    $_SESSION['phpAuthRequest-Authenticated'] = true;
    $_SESSION['phpAuthRequest-Timestamp'] = time();
    session_regenerate_id(true);

    if (isset($_REQUEST['req']))
      header("Location: " . $_REQUEST['req']);
  }
}


function logout() {
  $_SESSION = array();

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }

  session_destroy();
}


function getPasswordHash($username) {
  $pdo = new \PDO('sqlite:../phpAuthRequest.sqlite3');
  $stmt = $pdo->prepare('SELECT password FROM users WHERE username=:username;');
  $stmt->bindParam(':username', $username);
  $stmt->execute();
  return $stmt->fetchColumn();
}


function getAuthStatus() {
  if (isset($_SESSION['phpAuthRequest-Authenticated'])) {
    if (($_SESSION['phpAuthRequest-Timestamp'] + 86400) > time()) {
      $_SESSION['phpAuthRequest-Timestamp'] = time();
      return true;
    }
  }
}
?>
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

  <div class="limiter">
    <div class="container-login100">
      <div class="wrap-login100 p-t-90 p-b-30">

        <form class="login100-form validate-form" action="<?= $selfUrl ?>" method="post">
<?php if (getAuthStatus()): ?>
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
<?php else: ?>
<?php if (isset($_REQUEST['req'])): ?>
          <input type="hidden" name="req" value="<?= $_REQUEST['req'] ?>">
<?php endif; ?>
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
<?php endif; ?>

  <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
  <script src="js/main.js"></script>

<?php if (isset($debug)): ?>
<!-- $_SESSION
<?php var_dump($_SESSION); ?>
-->
<!-- $_REQUEST
<?php var_dump($_REQUEST); ?>
-->
<?php endif; ?>
</body>
</html>
