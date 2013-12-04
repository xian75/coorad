<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>.: COORAD :.</title>
  <script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
  <script src="/gm/js/jquery-ui-1.10.2/ui/jquery-ui.js"></script>
  <link rel="shortcut icon" type="image/x-icon" href="/gm/favicon.ico">
  <link rel="stylesheet" type="text/css" href="/gm/css/style.css" />
</head>
<body>

<?php
	require_once("init.php");
	$errorMessage = '';
	if (isset($_POST['username']) && isset($_POST['password'])) {
		$username = str_replace("'","''",$_POST['username']);
		$password = str_replace("'","''",$_POST['password']);
		/*if ($_POST['username'] == 'admin' && $_POST['password'] == 'password') {
			$_SESSION['user'] = 'admin';
			$_SESSION['role'] = 'superuser';
			header("location: index.php");
		}
		else if ($_POST['username'] == 'guest' && $_POST['password'] == 'guest') {
			$_SESSION['user'] = 'guest';
			$_SESSION['role'] = 'guestapp';
			$_SESSION['apps'] = array();
			$_SESSION['apps'][] = 29;
			$_SESSION['apps'][] = 31;
			$_SESSION['apps'][] = 32;
			header("location: index.php");
		}*/
		$userapp = array();
		$res = $DB->execute("SELECT gm_user.nickname nickname, gm_user_roleapp.role role, gm_user_roleapp.id_application userapp FROM gm_user_roleapp, gm_user WHERE gm_user.id = gm_user_roleapp.id_user AND gm_user.username = '".$username."' AND gm_user.password = '".sha1($password)."'");
		if ($res) {
			$_SESSION['apps'] = array();
			while ($row = $res->fetch_object()) {
				$_SESSION['user'] = $row->nickname;
				$_SESSION['role'] = $row->role;
				$_SESSION['apps'][] = $row->userapp;
			}
			$res->close();
		}
		if (isset($_SESSION['user'])) {
			header("location: index.php");
		}
		else {
			$errorMessage = 'Access denied. Username/Password not valid.';
		}
	}
?>

<!--div><a href="../index.php">Home</a> &gt; Applications</div-->
<!--h2 id="titlepage">COORAD IDE LOGIN</h2-->
<img src="/gm/img/coorad.png" style="width:200px; height:33px; padding-bottom:10px;" />

<form action="login.php" method="post" >
	<?php if ($errorMessage != '') echo '<p style="color:red;">'.$errorMessage."</p>"; ?>
	<fieldset style="width:300px;">
		<div>Username: <input type="text" name="username" /></div>
		<div>Password: <input type="password" name="password" /></div>
	</fieldset>
	<p><input type="submit" name="login" value="Login" /></p>
</form>

</body>
</html>