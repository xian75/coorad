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
		$viewID = 0;
		if (isset($_GET['id'])) $viewID = $_GET['id'];
		else $viewID = $_POST['id'];

		$res = $DB->execute("SELECT gm_section.id sectionId, gm_section.name sectionName, gm_section_view.view viewName, gm_section_view.command_pre_layout command_pre_layout FROM gm_section, gm_section_view WHERE gm_section_view.id_section = gm_section.id and gm_section_view.id = ".$viewID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$sectionID = $section->sectionId;
		$res->close();
		// get appID from sectionID
		$appID = 0;
		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_application.context_path appContextPath FROM gm_section, gm_application WHERE gm_application.id = gm_section.id_application AND gm_section.id = ".$sectionID);
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
		
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			// UPDATE section_pre_layout_view
			$command_pre_layout = trim(str_replace("'","''",$_POST['command_pre_layout']));
			//echo "<script>alert('".preg_replace('/[\r\t\n]/','',$_POST['command_pre_layout'])."');</script>";
			$DB->execute("UPDATE gm_section_view SET command_pre_layout='".$command_pre_layout."' WHERE id=".$viewID);
		}
		
		$res = $DB->execute("SELECT gm_section.id sectionId, gm_section.name sectionName, gm_section_view.view viewName, gm_section_view.command_pre_layout command_pre_layout FROM gm_section, gm_section_view WHERE gm_section_view.id_section = gm_section.id and gm_section_view.id = ".$viewID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$sectionID = $section->sectionId;
		$sectionName = str_replace("'","&rsquo;",$section->sectionName);
		$viewName = str_replace("'","&rsquo;",$section->viewName);
		$command_pre_layout = str_replace("\\","\\\\",$section->command_pre_layout);
		$res->close();

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Sections</a> &gt; <a href="section_pre_init_query.php?id=<?php echo $sectionID?>">Pre Init Query</a> &gt; <a href="section_init_query.php?id=<?php echo $sectionID?>">Initialization Query Set</a> &gt; <a href="section_view_selection.php?id=<?php echo $sectionID?>">View Set</a> &gt; Pre Layout &gt; <a href="section_layout.php?id=<?php echo $viewID ?>">Layout</a></div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Section <i style="background-color:yellow;">"<?php echo $sectionName?>"</i> View <i style="background-color:yellow;">"<?php echo $viewName?>"</i> Layout</h2>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black; padding-right:690px;" href="section_view_selection.php?id=<?php echo $sectionID?>"><img src="/gm/img/backward.png" title="Back to View Set"/>&nbsp;&nbsp;Back to View Set</a>
<a style="text-decoration:none; color:black;" href="section_layout.php?id=<?php echo $viewID ?>">Show layout&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Show layout"/></a>
</div>
<form id="form" action="section_pre_layout.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#ffffaa;">
		<div>&nbsp;Pre layout command set</div><div><textarea id="command_pre_layout" name="command_pre_layout" rows="25" cols="111"><?php echo $command_pre_layout ?></textarea></div>
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $viewID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this pre layout command set</button>
<?php } ?>
</form>

<?php
	}
?>

</body>
</html>