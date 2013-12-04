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

	if (!isset($_GET['importAppId']) && !isset($_POST['importAppId'])) {
		echo '<h2>Sorry, no application selected</h2>';
	}
	else {
		$appID = 0;
		if (isset($_GET['importAppId'])) $appID = $_GET['importAppId'];
		else $appID = $_POST['importAppId'];

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

		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT name FROM gm_application WHERE id = ".$appID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$appName = $row->name;
			}
		}
		$res->close();
?>

<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Sections</a> &gt; Import</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Import</h2>

<?php
		// execute import
		if (isset($_POST['xmlfilename']) && $authorized) {
			$reuse = true;
			if (isset($_POST['reuse']) && $_POST['reuse'] == 'false') $reuse = 0;
			$xmlfile = file_get_contents($_POST['xmlfilename']);
			unlink($_POST['xmlfilename']);
			$doc = new DOMDocument();
			if ($xmlfile != '') $doc->loadXML($xmlfile);
			$nodes = $doc->getElementsByTagName("section");
			$timestamp = date("d/m/Y H:i:s");
			$newname = array();
			foreach ($nodes as $node) {
				$newname = importSectionFromXml($DB, $node, $appID, $timestamp, false, $reuse);
			}
?>
			<div>XML has been imported correctly. The imported (or reused) section name is: <?php echo '<i style="background-color:yellow;">"'.str_replace('[REUSED]', '<span style="background-color:orange;">[REUSED]</span>', $newname[0]).'"</i>' ?></div>
<?php
			if (count($newname) > 1) {
?>
				<div>And the following actions have been imported (or reused) too:</div>
<?php
				for ($i = 1; $i < count($newname); $i++) {
?>
					<div><?php echo '<i style="background-color:yellow;">"'.str_replace('[REUSED]', '<span style="background-color:orange;">[REUSED]</span>', $newname[$i]).'"</i>' ?></div>
<?php
				}
			}
?>			
			<div><i>Notice: actions in section have original names; rename actions to their original name or change section layout if you want to make them work.</i></div>
			<div>&nbsp;</div>
			<div><form action="index.php" method='post'>
				<input type="hidden" id="appId" name="appId" value="<?php echo $appID; ?>" />
				<input type="submit" id="back" name="back" value="Back to Sections" />
			</form></div>
<?php
		}
		else if ($authorized) {
			$xmlfile = '';
			$xmlerror = '';
			if ($_FILES['xmlfile']['name'] == '') $xmlerror = 'XML file not found.';
			else if ($_FILES['xmlfile']['size'] > 1048576) $xmlerror = 'XML file too long (1 MB limit).';
			else if ($_FILES['xmlfile']['type'] != 'text/xml') $xmlerror = 'File type error (XML required).';
			if ($xmlerror == '') {
				$xmlfile = file_get_contents($_FILES['xmlfile']['tmp_name']);			
				if (!isXmlSectionCompliant($xmlfile)) $xmlerror = 'Bad XML file.';
			}
			if ($xmlerror == '') {
				$tmpxmlfile = $CFG->rootDir.'/tmp/'.str_replace(" ","",str_replace(".","",microtime().rand())).'.xml';
				move_uploaded_file($_FILES['xmlfile']['tmp_name'], $tmpxmlfile);			
?>
				<form action="section_xml_import.php" method='post'>
					<div>XML is ready to be imported.
					<input type="hidden" id="xmlfilename" name="xmlfilename" value="<?php echo $tmpxmlfile; ?>" />
					<input type="hidden" id="importAppId" name="importAppId" value="<?php echo $appID; ?>" />
					<input type="hidden" id="reuse" name="reuse" value="<?php echo $_POST['reuse']; ?>" />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" id="import" name="import" value="Ok, import now !!!" /></div>
				</form>
<?php
			}
			else {
?>
				<div style="color:red"><?php echo $xmlerror; ?></div>
<?php
			}
			if ($xmlfile != '') {
?>
				<h2>XML</h2>
				<hr />
				<pre><?php echo htmlentities($xmlfile); ?></pre>
<?php
			}
		}
	}
?>

</body>
</html>