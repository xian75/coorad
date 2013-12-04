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
	require_once("../init.php");

	// check logged user
	$authorized = true;
	if ($CFG->authorization && !isset($_SESSION['user'])) {
		header("location: /gm/login.php");
		$authorized = false;
	}
	if ($CFG->authorization && (!isset($_SESSION['role']) || $_SESSION['role'] != 'superuser')) {
		header("location: /gm/index.php");
		$authorized = false;
	}
	if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="/gm/logout.php">Logout</a></div></div>';

	// User ID
	$userID = 0;
	if (isset($_GET['id']) || isset($_POST['id'])) {
		if (isset($_GET['id'])) {
			$userID = $_GET['id'];
			$res = $DB->execute("SELECT gm_user.username username, gm_user.nickname nickname FROM gm_user WHERE gm_user.id = ".$userID);
			if ($res) {
				if ($row = $res->fetch_object()) {
					$_POST['username'] = $row->username;
					$_POST['nickname'] = $row->nickname;
				}
			}
			$res->close();
		}
		else {
			$userID = $_POST['id'];
		}
	}
	
?>

<div><a href="../index.php">Home</a> &gt; <a href="index.php">Users</a> &gt; Change password</div>
<h2 id="titlepage">Change password for user: <?php if ($userID != 0) echo '<i style="background-color:yellow;">"'.$_POST['username'].'"</i> (Nickname: <i style="background-color:yellow;">"'.$_POST['nickname'].'"</i>)'; ?></h2>
<div><a href="index.php" style="text-decoration:none; color: black;"><img src="/gm/img/backward.png" style="vertical-align:top;" title="Back to users"/>&nbsp;&nbsp;Back to users</a></div>

<?php

	$_ERROR = array();
	// check fields
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
		if ($userID != 0) {
			if (!isset($_POST['password']) || trim($_POST['password']) == '') $_ERROR['password'] = 'New password can not be empty';
			if (!isset($_POST['confirmpassword']) || trim($_POST['confirmpassword']) == '') $_ERROR['confirmpassword'] = 'Password confirmation can not be empty';
			if (isset($_POST['password']) && trim($_POST['password']) != '' && isset($_POST['confirmpassword']) && trim($_POST['confirmpassword']) != '' && $_POST['password'] != $_POST['confirmpassword']) $_ERROR['confirmpassword'] = 'Password confirmation does not match the new password';
		}
	}

	// save password change
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized && count($_ERROR) == 0) {
		if ($userID != 0) {
			// UPDATE user
			$DB->execute("UPDATE gm_user SET password='".sha1(str_replace("'","''",trim($_POST['password'])))."' WHERE id=".$userID);
		}
		header("location: index.php");
	}
	
?>
<form id="form" action="user_pwdmod.php" method="post">
	<div style="background-color:#dddddd; border:solid 1px black; padding: 7px; width:700px;">
		<div>Nickname:&nbsp;&nbsp;&nbsp;<input id="nickname" name="nickname" type="text" readonly="readonly" style="background-color: #cccccc;" value="<?php if (isset($_POST['nickname'])) echo $_POST['nickname'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['nickname'])) echo $_ERROR['nickname'] ?></span></div>
		<div>&nbsp;</div>
		<div>Username:&nbsp;&nbsp;&nbsp;<input id="username" name="username" type="text" readonly="readonly" style="background-color: #cccccc;" value="<?php if (isset($_POST['username'])) echo $_POST['username'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['username'])) echo $_ERROR['username'] ?></span></div>
		<div>&nbsp;</div>
		<div>Password:&nbsp;&nbsp;&nbsp;<input id="password" name="password" type="password" value="<?php if (isset($_POST['password'])) echo $_POST['password'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['password'])) echo $_ERROR['password'] ?></span></div>
		<div>&nbsp;</div>
		<div>Confirm password:&nbsp;&nbsp;&nbsp;<input id="confirmpassword" name="confirmpassword" type="password" value="<?php if (isset($_POST['confirmpassword'])) echo $_POST['confirmpassword'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['confirmpassword'])) echo $_ERROR['confirmpassword'] ?></span></div>
		<div>&nbsp;</div>
		<div style="text-align: center;"><button id="action" name="action" type="submit" value="save">Save new password</button></div>
		<input id="id" name="id" type="hidden" value="<?php echo $userID ?>" />
	</div>
</form>

</body>
</html>