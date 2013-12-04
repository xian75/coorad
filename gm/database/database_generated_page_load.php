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
	require_once("../lib_import.php");
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
		$authorized = true;
		if ($CFG->authorization && !isset($_SESSION['user'])) {
			header("location: /gm/login.php");
			$authorized = false;
		}
		if ($CFG->authorization && (!isset($_SESSION['role']) || ($_SESSION['role'] != 'superuser' && !in_array($appID, $_SESSION['apps'])) || $_SESSION['role'] == 'guestapp')) {
			header("location: /gm/index.php");
			$authorized = false;
		}
		if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="/gm/logout.php">Logout</a></div></div>';

?>

<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Database</a> &gt; <a href="database_code_generation.php?appId=<?php echo $appID; ?>">Code generation</a> &gt; Load page</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage" style="margin-left:8px;">Loaded page <i style="background-color:yellow;">"<?php echo $pageName?>"</i></h2>

<?php
		if ($authorized) {
			$reuse = true;
			if (isset($_GET['reuse']) && $_GET['reuse'] == 'false') $reuse = 0;
			//print_r($_SERVER);
			$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
			$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
			$protocol = substr($sp, 0, strpos($sp, '/')).$s;
			$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'.$_SERVER['SERVER_PORT']);
			$xmlExportScript = $protocol.'://'.$_SERVER['SERVER_NAME'].$port.'/gm/database/database_generated_page_xml_export.php?id='.$pageID.'&tableid='.$tableID;
			//echo $xmlExportScript;
			$xmlpage = file_get_contents($xmlExportScript);
			//echo $xmlpage;
			$doc = new DOMDocument();
			if ($xmlpage != '') $doc->loadXML($xmlpage);
			$nodes = $doc->getElementsByTagName("page");
			$timestamp = date("d/m/Y H:i:s");
			$newname = array();
			foreach ($nodes as $node) {
				$newname = importPageFromXml($DB, $node, $appID, $timestamp, $reuse);
			}
?>
		<div>XML has been imported correctly. The imported (or reused) page name is: <?php echo '<i style="background-color:yellow;">"'.str_replace('[REUSED]', '<span style="background-color:orange;">[REUSED]</span>', $newname[0]).'"</i>' ?></div>
<?php
			if (count($newname) > 2) {
?>
			<div>And the following css/javascript/sections/actions have been imported (or reused) too:</div>
<?php
				$swappingIdSections = array();
				for ($i = 2; $i < count($newname); $i++) {
					$elementType = '';
					$elementTypeIndex = substr($newname[$i],0,1);
					if ($elementTypeIndex == 'C') $elementType = '(CSS)';
					if ($elementTypeIndex == 'J') $elementType = '(JAVASCRIPT)';
					if ($elementTypeIndex == 'S') $elementType = '(SECTION)';
					if ($elementTypeIndex == 'A') $elementType = '(ACTION)';
					//if (!($elementTypeIndex == 'S' && $newname[$i + 2] < 0)) {
?>
					<div><?php echo $elementType.' <i style="background-color:yellow;">"'.str_replace('[REUSED]', '<span style="background-color:orange;">[REUSED]</span>', substr($newname[$i],1)).'"</i>' ?></div>
<?php
					//}
					if ($elementTypeIndex == 'S') {
						//if ($newname[$i + 2] < 0) $newname[$i + 2] = -1 * $newname[$i + 2]; 
						$swappingIdSections[$newname[$i + 1]] = $newname[$i + 2];
						//echo '<div>'.$newname[$i + 1]." = ".$swappingIdSections[$newname[$i + 1]].'</div>';
						$i = $i + 2;
					}
				}
				// replace old sections ID with the new ones
				$pagehtml = '';
				$res = $DB->execute("SELECT html FROM gm_page WHERE id = ".$newname[1]);
				if ($res && $row = $res->fetch_object()) {
					$pagehtml = $row->html;
				}
				$res->close();
				//print_r($swappingIdSections);
				//echo 'PAGE HTML: '.$pagehtml;
				if ($pagehtml != '') {
					$pagedoc = new DOMDocument();
					$pagedoc->loadHTML($pagehtml);
					$pagenodes = $pagedoc->getElementsByTagName("div");
					foreach ($pagenodes as $pagenode) {
						$sectionIdInPage = $pagenode->getAttribute('title');
						//echo $sectionIdInPage." ";
						if (array_key_exists($sectionIdInPage, $swappingIdSections)) $pagenode->setAttribute('title',$swappingIdSections[$sectionIdInPage]);
					}
					$substituteHtml = urldecode($pagedoc->saveHTML());
					$substituteHtml = preg_replace("<!DOCTYPE[^\>]+>","",$substituteHtml);
					$substituteHtml = str_replace("<html><body>","",str_replace("</body></html>","",str_replace("<>","",$substituteHtml)));
					$DB->execute("UPDATE gm_page SET html='".str_replace("'","''",$substituteHtml)."' WHERE id=".$newname[1]);
				}
			}
		}
?>			
		<div><i>Notice: actions in section have original names; rename actions to their original name or change section layout if you want to make them work.</i></div>
		<div>&nbsp;</div>
		<div><form action="database_code_generation.php" method='post'>
			<input type="hidden" id="appId" name="appId" value="<?php echo $appID; ?>" />
			<input type="submit" id="back" name="back" value="Back to Code Generation" />
		</form></div>
<?php
	}
?>
