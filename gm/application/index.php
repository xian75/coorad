<!DOCTYPE html>
<html>
<head>
  <title>.: COORAD :.</title>
  <!--script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script-->
  <link rel="shortcut icon" type="image/x-icon" href="/gm/favicon.ico">
  <link rel="stylesheet" type="text/css" href="/gm/css/style.css" />
</head>
<body>

<?php
	require_once("../init.php");

	if (!isset($_GET['appId']) && !isset($_POST['appId'])) {
		echo '<h2>Sorry, no application selected</h2>';
	}
	else {
		$appID = 0;
		if (isset($_GET['appId'])) $appID = $_GET['appId'];
		else $appID = $_POST['appId'];

		// check logged user
		if ($CFG->authorization && !isset($_SESSION['user'])) header("location: /gm/login.php");
		if ($CFG->authorization && (!isset($_SESSION['role']) || ($_SESSION['role'] != 'superuser' && !in_array($appID, $_SESSION['apps'])))) header("location: /gm/index.php");
		if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="/gm/logout.php">Logout</a></div></div>';

		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT name, context_path FROM gm_application WHERE id = ".$appID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$appName = $row->name;
				$appContextPath = $row->context_path;
			}
		}
		$res->close();
?>

<div><a href="../index.php">Home</a> &gt; Configuration</div>
<h2 id="titlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h2>
<div><a href="/gm/database/?appId=<?php echo $appID; ?>">DATABASE</a></div>
<div><a href="/gm/page/?appId=<?php echo $appID; ?>">PAGES</a></div>
<div><a href="/gm/section/?appId=<?php echo $appID; ?>">SECTIONS</a></div>
<div><a href="/gm/action/?appId=<?php echo $appID; ?>">ACTIONS</a></div>
<div><a href="/gm/css_gui/?appId=<?php echo $appID; ?>">CSS</a></div>
<div><a href="/gm/js_gui/?appId=<?php echo $appID; ?>">JAVASCRIPT</a></div>
<div>&nbsp;</div>
<!--div><a href="/gm/engine_debug.php">engine(debug)</a></div-->
<div><a target="_blank" href="/gm/deploy/<?php echo $appContextPath; ?>/index.php">Application Site</a></div>
<div><a target="_blank" href="/gm/deploy/<?php echo $appContextPath; ?>/debug.php">Debug Application</a></div>

<?php
	}
?>

</body>
</html>