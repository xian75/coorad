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
		echo '<h2>Sorry, no page selected</h2>';
	}
	else {
		$pageID = 0;
		if (isset($_GET['id'])) $pageID = $_GET['id'];
		else $pageID = $_POST['id'];

		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_page.id pageID, gm_page.name pageName, gm_page.command command FROM gm_page, gm_application WHERE gm_application.id = gm_page.id_application AND gm_page.id = ".$pageID);
		if (!$page = $res->fetch_object()) die('something wrong.');
		$appID = $page->appId;
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
			$DB->execute("UPDATE gm_page SET command='".$command."' WHERE id=".$pageID);
		}
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_page.id pageID, gm_page.name pageName, gm_page.command command FROM gm_page, gm_application WHERE gm_application.id = gm_page.id_application AND gm_page.id = ".$pageID);
		if (!$page = $res->fetch_object()) die('something wrong.');
		$appID = $page->appId;
		$appName = $page->appName;
		$pageID = $page->pageID;
		$pageName = str_replace("'","&rsquo;",$page->pageName);
		$command = str_replace("\\","\\\\",$page->command);
		$res->close();

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Pages</a> &gt; Commands &gt; <a href="page_layout.php?id=<?php echo $pageID ?>">Layout</a></div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Page <i style="background-color:yellow;">"<?php echo $pageName?>"</i> Commands</h2>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black; padding-right:705px;" href="index.php?appId=<?php echo $appID; ?>"><img src="/gm/img/backward.png" title="Back to Pages"/>&nbsp;&nbsp;Back to Pages</a>
<a style="text-decoration:none; color:black;" href="page_layout.php?id=<?php echo $pageID ?>">Show layout&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Show layout"/></a>
</div>
<form id="form" action="page_command.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#ccbbaa;">
		<div>&nbsp;Command set</div><div><textarea id="command" name="command" rows="25" cols="111"><?php echo $command ?></textarea></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $pageID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this command set</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>