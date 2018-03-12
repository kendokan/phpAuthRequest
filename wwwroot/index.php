<?php
/**
 * Todo:
 *
 * Error handling for database connection problems.
 * Admin console.
 * Allow user to change own password.
 *
 */


// Adds HTML comments to page source containing $_SESSION and $_REQUEST dumps.
$debug = false;


<<<<<<< HEAD
//Prepends path to PHP_SELF. Useful if calling this from NGINX proxy_pass.
=======
// $debug = true;
>>>>>>> d7be8ee23c8b0dedbe29414885cf46bd5cfd491b
$baseUrl = '/login';
$selfUrl = $baseUrl . $_SERVER['PHP_SELF'];


// Database connection, in PDO syntax.
$database = array(
  'dsn'       => 'sqlite:../phpAuthRequest.sqlite3',
  'username'  => null,
  'password'  => null
);


// Sanitize redirect URL if it exists.
$redirect = isset($_REQUEST['redirect']) ? filter_var($_REQUEST['redirect']) : null;


//  Session must be started before continuing.
session_start();


// Main action call block.
if (isset($_REQUEST['action'])) {
  switch ($_REQUEST['action']) {
    case 'status':
      getAuthStatus() ? exit(http_response_code(200)) /* OK */ : exit(http_response_code(401)) /* Unauthorized */;
      break;
    case 'login':
      login($database, $redirect, $_REQUEST['username'], $_REQUEST['password']);
      break;
    case 'logout':
      logout();
      break;
  }
}


/**
 * Validates login credentials, and optionally redirects browser upon success.
 * @param  string $database database connection, in PDO syntax
 * @param  string $redirect redirect browser to this URI after login success
 * @param  string $username username
 * @param  string $password password
 * @return null
 */
function login($database, $redirect, $username, $password) {
  if (password_verify($password, getPasswordHash($database, $username))) {
    $_SESSION['phpAuthRequest-Authenticated'] = true;
    $_SESSION['phpAuthRequest-Timestamp'] = time();
    session_regenerate_id(true);

    if (isset($redirect))
      header("Location: " . $redirect);
  } else {
    logout();
  }
}


/**
 * Removes cookies and destroys session.
 * @return null
 */
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


/**
 * Connects to database and gets user's password hash.
 * @param  string $database database connection, in PDO syntax
 * @param  string $username username
 * @return string           password hash
 */
function getPasswordHash($database, $username) {
  $pdo = new \PDO($database['dsn'], $database['username'], $database['password']);
  $stmt = $pdo->prepare('SELECT password FROM users WHERE username=:username;');
  $stmt->bindParam(':username', $username);
  $stmt->execute();
  return $stmt->fetchColumn();
}


/**
 * Checks if an authenticated session exists. This function is called by
 * NGINX's auth_request on every single page load, so it needs to be _fast_.
 * Any delays in this function increase the user's page load time.
 * @return bool true if authenticated
 */
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
          <input type="hidden" name="action" value="logout">
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
<?php if (isset($redirect)): ?>
          <input type="hidden" name="redirect" value="<?= $redirect ?>">
<?php endif; ?>
          <input type="hidden" name="action" value="login">
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

  <script src="js/jquery-3.2.1.min.js"></script>
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
