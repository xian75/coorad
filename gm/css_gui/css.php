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
		echo '<h2>Sorry, no css selected</h2>';
	}
	else {
		$cssID = 0;
		if (isset($_GET['id'])) $cssID = $_GET['id'];
		else $cssID = $_POST['id'];
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_css.name name, gm_css.css css FROM gm_css, gm_application WHERE gm_application.id = gm_css.id_application AND gm_css.id=".$cssID);
		if (!$css = $res->fetch_object()) die('something wrong.');
		$appID = $css->appId;
		$appName = $css->appName;
		$cssName = str_replace("'","&rsquo;",$css->name);
		$css = $css->css;
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
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">CSS</a> &gt; Script</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">CSS <i style="background-color:yellow;">"<?php echo $cssName?>"</i> Script</h2>

<?php
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			// UPDATE css
			$DB->execute("UPDATE gm_css SET css='".str_replace("'","''",$_POST['script'])."' WHERE id=".$cssID);
			$css = $_POST['script'];		
		}
?>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black;" href="index.php?appId=<?php echo $appID; ?>"><img src="/gm/img/backward.png" title="Back to CSS"/>&nbsp;&nbsp;Back to CSS</a>
</div>
<form id="form" action="css.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#aaffaa;">
		<div>&nbsp;Script</div><div><textarea id="script" name="script" title="Type script" rows="25" cols="111"><?php echo $css ?></textarea></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $cssID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this css</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>