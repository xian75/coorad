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
		echo '<h2>Sorry, no javascript selected</h2>';
	}
	else {
		$javascriptID = 0;
		if (isset($_GET['id'])) $javascriptID = $_GET['id'];
		else $javascriptID = $_POST['id'];
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_javascript.name name, gm_javascript.javascript javascript FROM gm_javascript, gm_application WHERE gm_application.id = gm_javascript.id_application AND gm_javascript.id=".$javascriptID);
		if (!$javascript = $res->fetch_object()) die('something wrong.');
		$appID = $javascript->appId;
		$appName = $javascript->appName;
		$javascriptName = str_replace("'","&rsquo;",$javascript->name);
		$javascript = $javascript->javascript;
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

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Javascript</a> &gt; Script</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Javascript <i style="background-color:yellow;">"<?php echo $javascriptName?>"</i> Script</h2>

<?php
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			// UPDATE javascript
			$DB->execute("UPDATE gm_javascript SET javascript='".str_replace("'","''",$_POST['script'])."' WHERE id=".$javascriptID);
			$javascript = $_POST['script'];		
		}
?>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black;" href="index.php?appId=<?php echo $appID; ?>"><img src="/gm/img/backward.png" title="Back to Javascript"/>&nbsp;&nbsp;Back to Javascript</a>
</div>
<form id="form" action="javascript.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#aaffaa;">
		<div>&nbsp;Script</div><div><textarea id="script" name="script" title="Type script" rows="25" cols="111"><?php echo $javascript ?></textarea></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $javascriptID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this javascript</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>