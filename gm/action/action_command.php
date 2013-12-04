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
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<h2>Sorry, no action selected</h2>';
	}
	else {
		$actionID = 0;
		if (isset($_GET['id'])) $actionID = $_GET['id'];
		else $actionID = $_POST['id'];
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_action.id actionID, gm_action.name name, gm_action.value value, gm_action.command command FROM gm_action, gm_application WHERE gm_application.id = gm_action.id_application AND gm_action.id = ".$actionID);
		if (!$action = $res->fetch_object()) die('something wrong.');
		$appID = $action->appId;
		$res->close();

		// check logged user
		$authorized = true;
		if ($CFG->authorization && !isset($_SESSION['user'])) {
			header("location: /gm/login.php");
			$authorized = false;
		}
		if ($CFG->authorization && (!isset($_SESSION['role']) || ($_SESSION['role'] != 'superuser' && !in_array($appID, $_SESSION['apps'])))) {
			header("location: /gm/index.php");
			$authorized = false;
		}
		if (isset($_SESSION['role']) && $_SESSION['role'] == 'guestapp') {
			$authorized = false;
		}
		if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="/gm/logout.php">Logout</a></div></div>';
		
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			// UPDATE command
			$command = trim(str_replace("'","''",$_POST['command']));
			//echo "<script>alert('".preg_replace('/[\r\t\n]/','',$_POST['command'])."');</script>";
			$DB->execute("UPDATE gm_action SET command='".$command."' WHERE id=".$actionID);
		}	
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_action.id actionID, gm_action.name name, gm_action.value value, gm_action.command command FROM gm_action, gm_application WHERE gm_application.id = gm_action.id_application AND gm_action.id = ".$actionID);
		if (!$action = $res->fetch_object()) die('something wrong.');
		$appID = $action->appId;
		$appName = $action->appName;
		$actionID = $action->actionID;
		$actionName = str_replace("'","&rsquo;",$action->name.'='.$action->value);
		$command = str_replace("\\","\\\\",$action->command);
		$res->close();

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Actions</a> &gt; Commands &gt; <a href="action_check_query.php?id=<?php echo $actionID?>">Check Query Set</a> &gt; <a href="action_check_field.php?id=<?php echo $actionID?>">Check Field Set</a> &gt; <a href="action_query_success.php?id=<?php echo $actionID?>">Query Set on Success</a> &gt; <a href="action_query_fail.php?id=<?php echo $actionID?>">Query Set on Fail</a> &gt; <a href="action_next_page.php?id=<?php echo $actionID ?>">Next Page</a></div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Commands</h2>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black; padding-right:640px;" href="index.php?appId=<?php echo $appID; ?>"><img src="/gm/img/backward.png" title="Back to Actions"/>&nbsp;&nbsp;Back to Actions</a>
<a style="text-decoration:none; color:black;" href="action_check_query.php?id=<?php echo $actionID ?>">Show check query set&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Show check query set"/></a>
</div>
<form id="form" action="action_command.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#bbaacc;">
		<div>&nbsp;Command set</div><div><textarea id="command" name="command" rows="25" cols="111"><?php echo $command ?></textarea></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $actionID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this command set</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>