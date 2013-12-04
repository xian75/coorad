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
		$res = $DB->execute("SELECT name, value, command_on_success, command_on_fail, next_page_on_success, next_page_on_fail FROM gm_action WHERE id=".$actionID);
		if (!$action = $res->fetch_object()) die('something wrong.');
		$actionName = str_replace("'","&rsquo;",$action->name.'='.$action->value);
		$command_on_success = $action->command_on_success;
		$command_on_fail = $action->command_on_fail;
		$next_page_on_success = $action->next_page_on_success;
		$next_page_on_fail = $action->next_page_on_fail;
		$res->close();
		// get appID from pageID
		$appID = 0;
		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_application.context_path appContextPath FROM gm_action, gm_application WHERE gm_application.id = gm_action.id_application AND gm_action.id = ".$actionID);
		if ($res) {
			while ($row = $res->fetch_object()) {
				$appID = $row->appId;
				$appName = $row->appName;
				$appContextPath = $row->appContextPath;
			}
			$res->close();
		}

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

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Actions</a> &gt; <a href="action_command.php?id=<?php echo $actionID?>">Commands</a> &gt; <a href="action_check_query.php?id=<?php echo $actionID?>">Check Query Set</a> &gt; <a href="action_check_field.php?id=<?php echo $actionID?>">Check Field Set</a> &gt; <a href="action_query_success.php?id=<?php echo $actionID?>">Query Set on Success</a> &gt; <a href="action_query_fail.php?id=<?php echo $actionID?>">Query Set on Fail</a> &gt; Next Page</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Next Page</h2>

<?php
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			// UPDATE action
			$DB->execute("UPDATE gm_action SET command_on_success='".str_replace("'","''",$_POST['command_on_success'])."', command_on_fail='".str_replace("'","''",$_POST['command_on_fail'])."', next_page_on_success='".str_replace("'","''",$_POST['next_page_on_success'])."', next_page_on_fail='".str_replace("'","''",$_POST['next_page_on_fail'])."' WHERE id=".$actionID);
			$command_on_success = $_POST['command_on_success'];
			$command_on_fail = $_POST['command_on_fail'];
			$next_page_on_success = $_POST['next_page_on_success'];
			$next_page_on_fail = $_POST['next_page_on_fail'];		
		}
?>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black;" href="action_query_fail.php?id=<?php echo $actionID ?>"><img src="/gm/img/backward.png" title="Back to Query Set on Fail"/>&nbsp;&nbsp;Back to Query Set on Fail</a>
</div>
<form id="form" action="action_next_page.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#aaffaa;">
		<div>&nbsp;Commands on success</div><div><textarea id="command_on_success" name="command_on_success" rows="10" cols="111"><?php echo /*str_replace("\\","\\\\",$command_on_success);*/$command_on_success; ?></textarea></div>
		<div>&nbsp;Next Page on success <input type="text" id="next_page_on_success" name="next_page_on_success" title="Type next page on success"size="70" value="<?php echo $next_page_on_success ?>" /> <span style="font-size:.8em;">(leave empty if next page is equal to the previous one)</span></div>
	</div>
	<div>&nbsp;</div>
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#ffaaaa;">
		<div>&nbsp;Commands on fail</div><div><textarea id="command_on_fail" name="command_on_fail" rows="10" cols="111"><?php echo /*str_replace("\\","\\\\",$command_on_fail);*/$command_on_fail; ?></textarea></div>
		<div>&nbsp;Next Page on fail <input type="text" id="next_page_on_fail" name="next_page_on_fail" title="Type next page on fail"size="70" value="<?php echo $next_page_on_fail ?>" /> <span style="font-size:.8em;">(leave empty if next page is equal to the previous one)</span></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $actionID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this next page set</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>