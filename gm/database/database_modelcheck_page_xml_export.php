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
	//header("Content-type: text/plain"); 
	require_once("../init.php");
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<error>Sorry, no page selected</error>';
	}
	else {
		$pageID = 0;
		if (isset($_GET['id'])) $pageID = $_GET['id'];
		else $pageID = $_POST['id'];	
		$tableID = 0;
		if (isset($_GET['tableid'])) $tableID = $_GET['tableid'];
		else $tableID = $_POST['tableid'];	
		$appID = 0;
		$res = $DB->execute("SELECT gm_application.id appid, gm_application.name appname, gm_page.name pagename FROM gm_application, gm_page WHERE gm_page.id_application = gm_application.id AND gm_page.id = ".$pageID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$appID = $row->appid;
				$appName = $row->appname;
				$pageName = $row->pagename;
			}
			$res->close();
		}

		// check logged user
		if ($CFG->authorization && !isset($_SESSION['user'])) header("location: /gm/login.php");
		if ($CFG->authorization && (!isset($_SESSION['role']) || ($_SESSION['role'] != 'superuser' && !in_array($appID, $_SESSION['apps'])))) header("location: /gm/index.php");
		if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="/gm/logout.php">Logout</a></div></div>';

		//print_r($_SERVER);
		$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')).$s;
		$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'.$_SERVER['SERVER_PORT']);
		$xmlGeneratedScript = $protocol.'://'.$_SERVER['SERVER_NAME'].$port.'/gm/database/database_generated_page_xml_export.php?id='.$pageID.'&tableid='.$tableID;
		//echo $xmlExportScript;
		$xmlpage = file_get_contents($xmlGeneratedScript);
		$xmlpage = htmlentities($xmlpage);
		$xmlpage = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', str_replace(array("\r\n", "\n", "\r"), '<br />', $xmlpage));
		// check model
		$correct = false;
		if (!preg_match('/_ENTITY_|_TABLE_|_PRIMARY_KEY_|_FOREIGN_KEY_|_COLUMN_|_ATTRIBUTE_/', $xmlpage)) $correct = true;
		else {
			$xmlpage = str_replace('_ENTITY_', '<span style="background-color: red;">_ENTITY_</span>', $xmlpage);
			$xmlpage = str_replace('_TABLE_', '<span style="background-color: red;">_TABLE_</span>', $xmlpage);
			$xmlpage = str_replace('_PRIMARY_KEY_', '<span style="background-color: red;">_PRIMARY_KEY_</span>', $xmlpage);
			$xmlpage = str_replace('_FOREIGN_KEY_', '<span style="background-color: red;">_FOREIGN_KEY_</span>', $xmlpage);
			$xmlpage = str_replace('_COLUMN_', '<span style="background-color: red;">_COLUMN_</span>', $xmlpage);
			$xmlpage = str_replace('_ATTRIBUTE_', '<span style="background-color: red;">_ATTRIBUTE_</span>', $xmlpage);
		}
?>
		<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Database</a> &gt; <a href="database_code_generation.php?appId=<?php echo $appID; ?>">Code generation</a> &gt; Check model</div>
		<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
		<h2 id="titlepage" style="margin-left:8px;">Check model for page <i style="background-color:yellow;">"<?php echo $pageName?>"</i></h2>
<?php
		if ($correct) {
?>	
			<div style="color: #00bb00;">Check OK! The model is compliant.</div>
			<div><a style="text-decoration:none; color:black;" href="database_generated_page_xml_export.php?id=<?php echo $pageID; ?>&tableid=<?php echo $tableID; ?>"><img src="/gm/img/export.png" style="vertical-align:middle; cursor:pointer;" title="Export generated page code"/>&nbsp;&nbsp;Export generated page code</a></div>
<?php
		} else {
?>		
			<div>Check ERROR! Something wrong in your model.</div>
			<div><span style="background-color: red;">Highlighted error in the xml fragment below.</span></div>
<?php
		}
?>				
		<h2>XML</h2>
		<hr />
		<?php echo $xmlpage; ?>
<?php		
	}
?>

</body>
</html>