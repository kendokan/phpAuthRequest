<?php


// Adds HTML comments to page source containing $_SESSION and $_REQUEST dumps.
$debug = false;


//Prepends path to PHP_SELF. Useful if calling this from NGINX proxy_pass.
// $baseUrl = '';
$baseUrl = '/login';
$selfUrl = $baseUrl . $_SERVER['PHP_SELF'];


// Database connection, in PDO syntax.
$database = array(
  'dsn'       => 'sqlite:../phpAuthRequest.db',
  'username'  => null,
  'password'  => null
);


//  Session must be started before continuing.
ini_set('session.use_strict_mode', '1');
session_start();


// Main action call block.
if (isset($_REQUEST['action'])) {
  switch ($_REQUEST['action']) {
    case 'status':
      getAuthStatus() ? exit(http_response_code(200)) /* OK */ : exit(http_response_code(401)) /* Unauthorized */;
      break;
    case 'login':
      login();
      break;
    case 'logout':
      logout();
      break;
  }
}


/**
 * Validates login credentials, and optionally redirects browser upon success.
 */
function login() {
  $res = getPasswordHash($_REQUEST['username']);

  if (password_verify($_REQUEST['password'], $res['password'])) {
    session_regenerate_id(true);
    $_SESSION['phpAuthRequest-Authenticated'] = true;
    $_SESSION['phpAuthRequest-Access-Level'] = $res['access_level'];
    $_SESSION['phpAuthRequest-Timestamp'] = time();

    if (!empty($_REQUEST['redirect'])) {
      header("Location: " . $_REQUEST['redirect']);
      exit();
    }

  } else {
    logout();
  }
}


/**
 * Removes cookies and destroys session.
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
 * @param  string $username username
 * @return array password hash and access level
 */
function getPasswordHash($username) {
  try {
    $dbh = new \PDO(
      $GLOBALS['database']['dsn'],
      $GLOBALS['database']['username'],
      $GLOBALS['database']['password']
    );

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $dbh->prepare('SELECT password, access_level FROM users WHERE username=lower(:username);');
    $stmt->bindParam(':username', strtolower($username));
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);

  } catch (PDOException $e) {
    if ($GLOBALS['debug'] === true)
      die($e->getMessage());
    else
      die("An unexpected error occured. I can't go on like this. Please try again later.");
  }
}


/**
 * Checks if an authenticated session exists. This function is called by
 * NGINX's auth_request on every single page load, so it needs to be _fast_.
 * Any delays in this function increase the user's page load time.
 * @return bool
 */
function getAuthStatus() {
  if (isset($_SESSION['phpAuthRequest-Authenticated'])) {

    // Fail if requested access level is lower than user's.
    if (!empty($_REQUEST['access-level']) && $_REQUEST['access-level'] > $_SESSION['phpAuthRequest-Access-Level'])
      return(false);

    // Clear the session after 24 hours.
    if (($_SESSION['phpAuthRequest-Timestamp'] + 86400) > time()) {
      $_SESSION['phpAuthRequest-Timestamp'] = time();
      return(true);
    } else {
      logout();
      return(false);
    }
  } else {
    return(false);
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="css/main.min.css">
  <link rel="stylesheet" type="text/css" href="css/util.min.css">
  <title>Authenticate</title>
</head>

<body>

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
<?php if (isset($_REQUEST['redirect'])): ?>
          <input type="hidden" name="redirect" value="<?= $_REQUEST['redirect'] ?>">
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

<?php if ($debug === true): ?>
<!-- $_SESSION
<?php var_dump($_SESSION); ?>
-->
<!-- $_REQUEST
<?php var_dump($_REQUEST); ?>
-->
<?php endif; ?>
</body>
</html>
