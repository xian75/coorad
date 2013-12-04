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
		echo '<h2>Sorry, no section selected</h2>';
	}
	else {
		$sectionID = 0;
		if (isset($_GET['id'])) $sectionID = $_GET['id'];
		else $sectionID = $_POST['id'];

		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_section.id sectionId, gm_section.name sectionName, gm_section.command_pre_init_query command_pre_init_query FROM gm_section, gm_application WHERE gm_application.id = gm_section.id_application AND gm_section.id = ".$sectionID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$appID = $section->appId;
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
			// UPDATE section_pre_init_query
			$command_pre_init_query = trim(str_replace("'","''",$_POST['command_pre_init_query']));
			//echo "<script>alert('".preg_replace('/[\r\t\n]/','',$_POST['command_pre_init_query'])."');</script>";
			$DB->execute("UPDATE gm_section SET command_pre_init_query='".$command_pre_init_query."' WHERE id=".$sectionID);
		}
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_section.id sectionId, gm_section.name sectionName, gm_section.command_pre_init_query command_pre_init_query FROM gm_section, gm_application WHERE gm_application.id = gm_section.id_application AND gm_section.id = ".$sectionID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$appID = $section->appId;
		$appName = $section->appName;
		$sectionID = $section->sectionId;
		$sectionName = str_replace("'","&rsquo;",$section->sectionName);
		$command_pre_init_query = str_replace("\\","\\\\",$section->command_pre_init_query);
		$res->close();

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Sections</a> &gt; Pre Init Query &gt; <a href="section_init_query.php?id=<?php echo $sectionID?>">Initialization Query Set</a> &gt; <a href="section_view_selection.php?id=<?php echo $sectionID?>">View Set</a></div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Section <i style="background-color:yellow;">"<?php echo $sectionName?>"</i> Pre Init Query</h2>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black; padding-right:655px;" href="index.php?appId=<?php echo $appID; ?>"><img src="/gm/img/backward.png" title="Back to View Set"/>&nbsp;&nbsp;Back to Sections</a>
<a style="text-decoration:none; color:black;" href="section_init_query.php?id=<?php echo $sectionID ?>">Show init query set&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Show pre init query set"/></a>
</div>
<form id="form" action="section_pre_init_query.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#aaaaff;">
		<div>&nbsp;Pre init query command set</div><div><textarea id="command_pre_init_query" name="command_pre_init_query" rows="25" cols="111"><?php echo $command_pre_init_query ?></textarea></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $sectionID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this pre init query command set</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>