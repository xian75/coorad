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

	// Applications list
	$applist = array();
	$res = $DB->execute("SELECT id, name FROM gm_application ORDER BY name");
	if ($res) {
		while ($row = $res->fetch_object()) {
			$applist[$row->id] = $row->name;
		}
		$res->close();
	}

	// User ID
	$userID = 0;
	if (isset($_GET['id']) || isset($_POST['id'])) {
		if (isset($_GET['id'])) {
			$userID = $_GET['id'];
			$res = $DB->execute("SELECT gm_user.username username, gm_user.nickname nickname, gm_user_roleapp.role role, gm_user_roleapp.id_application userapp FROM gm_user LEFT JOIN gm_user_roleapp ON gm_user.id = gm_user_roleapp.id_user WHERE gm_user.id = ".$userID);
			if ($res) {
				while ($row = $res->fetch_object()) {
					$_POST['username'] = $row->username;
					$_POST['nickname'] = $row->nickname;
					$_POST['role'] = $row->role;
					$_POST['app_'.$row->userapp] = '1';
				}
			}
			$res->close();
		}
		else {
			$userID = $_POST['id'];
		}
	}
	
?>

<div><a href="../index.php">Home</a> &gt; <a href="index.php">Users</a> &gt; <?php echo $userID == 0 ? 'Add New' : 'Modify'; ?> User</div>
<h2 id="titlepage">Users</h2>
<div><a href="index.php" style="text-decoration:none; color: black;"><img src="/gm/img/backward.png" style="vertical-align:top;" title="Back to users"/>&nbsp;&nbsp;Back to users</a></div>

<?php

	$_ERROR = array();
	// check fields
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
		if (!isset($_POST['username']) || trim($_POST['username']) == '') $_ERROR['username'] = 'Username can not be empty and must be unique';
		if (!isset($_POST['nickname']) || trim($_POST['nickname']) == '') $_ERROR['nickname'] = 'Nickname can not be empty and must be unique';
		if ($userID == 0) {
			if (!isset($_POST['password']) || trim($_POST['password']) == '') $_ERROR['password'] = 'Password can not be empty';
			if (!isset($_POST['confirmpassword']) || trim($_POST['confirmpassword']) == '') $_ERROR['confirmpassword'] = 'Password confirmation can not be empty';
			if (isset($_POST['password']) && trim($_POST['password']) != '' && isset($_POST['confirmpassword']) && trim($_POST['confirmpassword']) != '' && $_POST['password'] != $_POST['confirmpassword']) $_ERROR['confirmpassword'] = 'Password confirmation does not match the password';
			$res = $DB->execute("SELECT id FROM gm_user WHERE username = '".trim($_POST['username'])."'");
			if ($res && $res->fetch_object()) {
				$_ERROR['username'] = "Username must be unique";
			}
			$res->close();
			$res = $DB->execute("SELECT id FROM gm_user WHERE nickname = '".trim($_POST['nickname'])."'");
			if ($res && $res->fetch_object()) {
				$_ERROR['nickname'] = "Nickname must be unique";
			}
			$res->close();
		}
		else {
			$res = $DB->execute("SELECT id FROM gm_user WHERE id <> ".$userID." AND username = '".trim($_POST['username'])."'");
			if ($res && $res->fetch_object()) {
				$_ERROR['username'] = "Username must be unique";
			}
			$res->close();
			$res = $DB->execute("SELECT id FROM gm_user WHERE id <> ".$userID." AND nickname = '".trim($_POST['nickname'])."'");
			if ($res && $res->fetch_object()) {
				$_ERROR['nickname'] = "Nickname must be unique";
			}
			$res->close();
			$res = $DB->execute("SELECT id FROM gm_user WHERE id = ".$userID);
			if (!$res || !$res->fetch_object()) {
				$res->close();
				header("location: index.php");
			}
			$res->close();
		}
	}

	// save user
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized && count($_ERROR) == 0) {
		if ($userID == 0) {
			// INSERT user
			$DB->execute("INSERT INTO gm_user(username, password, nickname) VALUES('".str_replace("'","''",trim($_POST['username']))."','".sha1(str_replace("'","''",trim($_POST['password'])))."','".str_replace("'","''",trim($_POST['nickname']))."')");
			$insertedId = $DB->getInsertedId();
			// INSERT roleapp
			if ($_POST['role'] == 'superuser') {
				$DB->execute("INSERT INTO gm_user_roleapp(role, id_user, id_application) VALUES('".$_POST['role']."',".$insertedId.",null)");
			}
			else foreach ($applist as $key => $value) {
				if (isset($_POST['app_'.$key])) $DB->execute("INSERT INTO gm_user_roleapp(role, id_user, id_application) VALUES('".$_POST['role']."',".$insertedId.",".$key.")");
			}
		}
		else {
			// UPDATE user
			$DB->execute("UPDATE gm_user SET username='".str_replace("'","''",trim($_POST['username']))."', nickname='".str_replace("'","''",trim($_POST['nickname']))."' WHERE id=".$userID);
			$DB->execute("DELETE FROM gm_user_roleapp WHERE id_user=".$userID);
			// INSERT roleapp
			if ($_POST['role'] == 'superuser') {
				$DB->execute("INSERT INTO gm_user_roleapp(role, id_user, id_application) VALUES('".$_POST['role']."',".$userID.",null)");
			}
			else foreach ($applist as $key => $value) {
				if (isset($_POST['app_'.$key])) $DB->execute("INSERT INTO gm_user_roleapp(role, id_user, id_application) VALUES('".$_POST['role']."',".$userID.",".$key.")");
			}
		}
		header("location: index.php");
	}
	
?>
<form id="form" action="user_addmod.php" method="post">
	<div style="background-color:#dddddd; border:solid 1px black; padding: 7px; width:700px;">
		<div>Nickname:&nbsp;&nbsp;&nbsp;<input id="nickname" name="nickname" type="text" value="<?php if (isset($_POST['nickname'])) echo $_POST['nickname'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['nickname'])) echo $_ERROR['nickname'] ?></span></div>
		<div>&nbsp;</div>
		<div>Username:&nbsp;&nbsp;&nbsp;<input id="username" name="username" type="text" value="<?php if (isset($_POST['username'])) echo $_POST['username'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['username'])) echo $_ERROR['username'] ?></span></div>
		<div>&nbsp;</div>
<?php if ($userID == 0) { ?>
		<div>Password:&nbsp;&nbsp;&nbsp;<input id="password" name="password" type="password" value="<?php if (isset($_POST['password'])) echo $_POST['password'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['password'])) echo $_ERROR['password'] ?></span></div>
		<div>&nbsp;</div>
		<div>Confirm password:&nbsp;&nbsp;&nbsp;<input id="confirmpassword" name="confirmpassword" type="password" value="<?php if (isset($_POST['confirmpassword'])) echo $_POST['confirmpassword'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['confirmpassword'])) echo $_ERROR['confirmpassword'] ?></span></div>
		<div>&nbsp;</div>
<?php } ?>
		<div>Role:&nbsp;&nbsp;&nbsp;<select id="role" name="role">
			<option <?php if ($_POST['role'] == 'guestapp') echo 'selected="selected"'; ?>>guestapp</option>
			<option <?php if ($_POST['role'] == 'adminapp') echo 'selected="selected"'; ?>>adminapp</option>
			<option <?php if ($_POST['role'] == 'superuser') echo 'selected="selected"'; ?>>superuser</option>
		</select></div>
		<div>&nbsp;</div>
		<div id="apps_block" <?php if ($_POST['role'] == 'superuser') echo 'style="display:none;"'; ?>>
			<div>Applications:</div>
			<div>&nbsp;</div>
	<?php foreach ($applist as $key => $value) { ?>
			<div><?php echo $value; ?>:&nbsp;&nbsp;&nbsp;<input id="app_<?php echo $key; ?>" name="app_<?php echo $key; ?>" type="checkbox" value="1" <?php if (isset($_POST['app_'.$key]) && $_POST['app_'.$key] == '1') echo 'checked="checked"'; ?> /></div>
			<div>&nbsp;</div>
	<?php } ?> 
		</div>
		<div style="text-align: center;"><button id="action" name="action" type="submit" value="save">Save user</button></div>
		<input id="id" name="id" type="hidden" value="<?php echo $userID ?>" />
	</div>
</form>

<script>
	$("#role").change(function(){
		var roletext = $("#role option:selected").text();
		if (roletext == 'superuser') $("#apps_block").css('display','none');
		else $("#apps_block").css('display','inline');
	});
</script>

</body>
</html>