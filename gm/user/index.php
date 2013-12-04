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

	if (isset($_GET['action']) && $_GET['action'] == 'delete' && $authorized) {
		if (isset($_GET['id'])) {
			$DB->execute("DELETE FROM gm_user WHERE id=".$_GET['id']);
		}
	}
	
	// Applications list
	$applist = array();
	$res = $DB->execute("SELECT id, name FROM gm_application ORDER BY name");
	if ($res) {
		while ($row = $res->fetch_object()) {
			$applist[$row->id] = $row->name;
		}
		$res->close();
	}

	// Users list
	$useridlist = array();
	$usernamelist = array();
	$nicknamelist = array();
	$userrole = array();
	$userappid = array();
	$res = $DB->execute("SELECT gm_user.id id, gm_user.username username, gm_user.nickname nickname, gm_user_roleapp.role role, gm_user_roleapp.id_application userapp FROM gm_user LEFT JOIN gm_user_roleapp ON gm_user.id = gm_user_roleapp.id_user");
	if ($res) {
		while ($row = $res->fetch_object()) {
			if (!in_array($row->username, $usernamelist)) {
				$useridlist[] = $row->id;
				$usernamelist[] = $row->username;
				$nicknamelist[] = $row->nickname;
				$userrole[] = $row->role;
				$userappid[$row->username] = array();
			}
			$userappid[$row->username][] = $row->userapp;
		}
		$res->close();
	}
	
?>

<div><a href="../index.php">Home</a> &gt; Users</div>
<h2 id="titlepage">Users</h2>
<div><a href="../index.php" style="text-decoration:none; color: black;"><img src="/gm/img/backward.png" style="vertical-align:top;" title="Back to applications"/>&nbsp;&nbsp;Back to applications</a>
<a href="user_addmod.php" style="text-decoration:none; color: black; padding-left: <?php echo 125 + count($applist) * 100; ?>px;"><img src="/gm/img/add.png" style="vertical-align:top;" title="Add User"/>&nbsp;&nbsp;Add User</a>
</div>

<form id="form" action="app_addmod.php" method="post">
	<table style="border: 1px solid black; border-collapse: collapse;">
		<tr style="background-color:#dddddd;">
			<td style="border: 1px dotted black; text-align:center; width: 70px;" rowspan="2">Username</td>
			<td style="border: 1px dotted black; text-align:center; width: 70px;" rowspan="2">Nickname</td>
			<td style="border: 1px dotted black; text-align:center; width: 70px;" rowspan="2">Role</td>
			<td style="border: 1px dotted black; text-align:center; background-color:#eeeeee; width: <?php echo count($applist) * 100; ?>px;" colspan="<?php echo count($applist); ?>">Applications</td>
			<td style="border: 1px dotted black; text-align:center; width: 60px;" rowspan="2"></td>
		</tr>
		<tr style="background-color:#eeeeee;">
			<?php foreach ($applist as $key => $value) { ?>
				<td style="border: 1px dotted black; text-align:center; width: 100px;"><?php echo $value ?></td>
			<?php } ?>
		</tr>
		<?php for ($i = 0; $i < count($usernamelist); $i++) { ?> 
			<tr>
				<td style="border: 1px dotted black;"><?php echo $usernamelist[$i]; ?></td>
				<td style="border: 1px dotted black;"><?php echo $nicknamelist[$i]; ?></td>
				<td style="border: 1px dotted black;"><?php echo $userrole[$i]; ?></td>
				<?php foreach ($applist as $key => $value) { ?>
					<td style="border: 1px dotted black; text-align:center;"><input type="checkbox" disabled="disabled" <?php if (in_array($key, $userappid[$usernamelist[$i]]) || $userrole[$i] == 'superuser') echo 'checked="checked"'; ?> /></td>
				<?php } ?>
				<td style="border: 1px dotted black;">
					<a href="index.php?action=delete&id=<?php echo $useridlist[$i]?>"><img src="/gm/img/del.png" style="vertical-align:bottom;" title="Delete this user (id=<?php echo $useridlist[$i]; ?>)" onClick="return confirm('Do you really want to cancel this user (id=' + <?php echo $useridlist[$i]; ?> + ')?')" /></a>
					<a href="user_addmod.php?id=<?php echo $useridlist[$i]; ?>"><img src="/gm/img/mod.png" style="vertical-align:bottom;" title="Modify this user (id=<?php echo $useridlist[$i]; ?>)" /></a>
					<a href="user_pwdmod.php?id=<?php echo $useridlist[$i]; ?>"><img src="/gm/img/pwd.png" style="vertical-align:bottom;" title="Change this user password (id=<?php echo $useridlist[$i]; ?>)" /></a>
				</td>
			</tr>
		<?php } ?>
	</table>
</form>

</body>
</html>