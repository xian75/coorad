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

	// check logged user
	$authorized = true;
	if ($CFG->authorization && !isset($_SESSION['user'])) {
		header("location: /gm/login.php");
		$authorized = false;
	}
	if ($CFG->authorization && (!isset($_SESSION['role']) || $_SESSION['role'] != 'superuser')) {
		header("location: /gm/index.php");
		$authorized = false;
	}
	if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="/gm/logout.php">Logout</a></div></div>';

	// Application ID
	$appID = 0;
	if (isset($_GET['id']) || isset($_POST['id'])) {
		if (isset($_GET['id'])) {
			$appID = $_GET['id'];
			$res = $DB->execute("SELECT id, name, description, context_path, db_prefix, db_host, db_port, db_name, db_username, db_password FROM gm_application WHERE id = ".$appID);
			if ($res) {
				if ($row = $res->fetch_object()) {
					$_POST['name'] = $row->name;
					$_POST['description'] = $row->description;
					$_POST['context_path'] = $row->context_path;
					$_POST['db_prefix'] = $row->db_prefix;
					$_POST['db_host'] = $row->db_host;
					$_POST['db_port'] = $row->db_port;
					$_POST['db_name'] = $row->db_name;
					$_POST['db_username'] = $row->db_username;
					$_POST['db_password'] = $row->db_password;
				}
			}
			$res->close();
		}
		else {
			$appID = $_POST['id'];
		}
	}
	
?>

<div><a href="../index.php">Home</a> &gt; <?php echo $appID == 0 ? 'Add New' : 'Modify'; ?> Application</div>
<h2 id="titlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$_POST['name'].'"</i> (Context Path: <i style="background-color:yellow;">"'.$_POST['context_path'].'"</i>)'; ?></h2>
<div><a href="../index.php" style="text-decoration:none; color: black;"><img src="/gm/img/backward.png" style="vertical-align:top;" title="Back to applications"/>&nbsp;&nbsp;Back to applications</a></div>

<?php

	if (!isset($_POST['db_host']) || trim($_POST['db_host']) == '') $_POST['db_host'] = 'localhost';
	if (!isset($_POST['db_port']) || trim($_POST['db_port']) == '') $_POST['db_port'] = 3306;
	if (!isset($_POST['db_name']) || trim($_POST['db_name']) == '') $_POST['db_name'] = 'gm';
	if (!isset($_POST['db_username']) || trim($_POST['db_username']) == '') $_POST['db_username'] = 'gm';
	if (!isset($_POST['db_password']) || trim($_POST['db_password']) == '') $_POST['db_password'] = 'gm';

	$_ERROR = array();
	// check fields
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
		if (!isset($_POST['name']) || trim($_POST['name']) == '') $_ERROR['name'] = 'Application name can not be empty';
		if (!isset($_POST['context_path']) || trim($_POST['context_path']) == '') $_ERROR['context_path'] = 'Application context path can not be empty and must be unique';
		if ($appID == 0) {
			$res = $DB->execute("SELECT id FROM gm_application WHERE context_path = '".trim($_POST['context_path'])."'");
			if ($res && $res->fetch_object()) {
				$_ERROR['context_path'] = "Application context path must be unique";
			}
			$res->close();
		}
		else {
			$res = $DB->execute("SELECT id FROM gm_application WHERE id <> ".$appID." AND context_path = '".trim($_POST['context_path'])."'");
			if ($res && $res->fetch_object()) {
				$_ERROR['context_path'] = "Application context path must be unique";
			}
			$res->close();
			$res = $DB->execute("SELECT id FROM gm_application WHERE id = ".$appID);
			if (!$res || !$res->fetch_object()) {
				$res->close();
				header("location: ../index.php");
			}
			$res->close();
		}
		if (!is_numeric(trim($_POST['db_port']))) $_ERROR['db_port'] = 'Port must be a number';
	}

	// save application
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized && count($_ERROR) == 0) {
		if ($appID == 0) {
			// INSERT application
			$DB->execute("INSERT INTO gm_application(name, description, context_path, db_prefix, db_host, db_port, db_name, db_username, db_password) VALUES('".str_replace("'","''",trim($_POST['name']))."','".str_replace("'","''",trim($_POST['description']))."','".str_replace("'","''",trim($_POST['context_path']))."','".str_replace("'","''",trim($_POST['db_prefix']))."','".str_replace("'","''",trim($_POST['db_host']))."','".str_replace("'","''",trim($_POST['db_port']))."','".str_replace("'","''",trim($_POST['db_name']))."','".str_replace("'","''",trim($_POST['db_username']))."','".str_replace("'","''",trim($_POST['db_password']))."')");
			$insertedId = $DB->getInsertedId();
			// INSERT (home)page
			$DB->execute("INSERT INTO gm_page(name, description, is_home, id_application, html, command) VALUES('home','Application Home Page',1,".$insertedId.",'','')");
			// init FILE SYSTEM
			mkdir($CFG->rootDir.'/deploy/'.trim($_POST['context_path']));
			mkdir($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/img');
			mkdir($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/upload');
			mkdir($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/upload/images');
			mkdir($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/upload/files');
			// Base images
			copy($CFG->rootDir.'/deploy_startup/img/asc.png', $CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/img/asc.png');
			copy($CFG->rootDir.'/deploy_startup/img/desc.png', $CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/img/desc.png');
			copy($CFG->rootDir.'/deploy_startup/img/ascnum.png', $CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/img/ascnum.png');
			copy($CFG->rootDir.'/deploy_startup/img/descnum.png', $CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/img/descnum.png');
			copy($CFG->rootDir.'/deploy_startup/img/favicon.ico', $CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/img/favicon.ico');
			// Application file: index.php
			$fp = fopen($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/index.php', 'w');
			fwrite($fp, "<?php\r\n");
			fwrite($fp, '	$appID = '.$insertedId.";\r\n");
			fwrite($fp, '	$appName = \''.trim($_POST['name'])."';\r\n");
			fwrite($fp, '	$appContextPath = \''.trim($_POST['context_path'])."';\r\n");
			fwrite($fp, '	$appDebuggable = false;'."\r\n");
			fwrite($fp, "	require_once(\"../../engine_debug.php\");\r\n");
			fwrite($fp, "?>\r\n");
			fclose($fp);
			chmod($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/index.php', 0755);
			// Application file: debug.php
			$fp2 = fopen($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/debug.php', 'w');
			fwrite($fp2, "<?php\r\n");
			fwrite($fp2, '	$appID = '.$insertedId.";\r\n");
			fwrite($fp2, '	$appName = \''.trim($_POST['name'])."';\r\n");
			fwrite($fp2, '	$appContextPath = \''.trim($_POST['context_path'])."';\r\n");
			fwrite($fp2, '	$appDebuggable = true;'."\r\n");
			fwrite($fp2, "	require_once(\"../../engine_debug.php\");\r\n");
			fwrite($fp2, "?>\r\n");
			fclose($fp2);
			chmod($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/debug.php', 0755);
			// CSS base file
			//$cssfile = file_get_contents($CFG->rootDir.'/deploy_startup/init.css');
			//$DB->execute("INSERT INTO gm_css(name, description, css, id_application) VALUES('default','Application default CSS','".str_replace("'","''",$cssfile)."',".$insertedId.")");
			// Javascript base file
			//$javascriptfile = file_get_contents($CFG->rootDir.'/deploy_startup/init.js');
			//$DB->execute("INSERT INTO gm_javascript(name, description, javascript, id_application) VALUES('default','Application default Javascript','".str_replace("'","''",$javascriptfile)."',".$insertedId.")");
			
		}
		else {
			// UPDATE application
			$oldContextPath = '';
			$res = $DB->execute("SELECT context_path FROM gm_application WHERE id = ".$appID);
			if ($res) {
				if ($row = $res->fetch_object()) {
					$oldContextPath = $row->context_path;
				}
			}
			$res->close();
			$DB->execute("UPDATE gm_application SET name='".str_replace("'","''",trim($_POST['name']))."', description='".str_replace("'","''",trim($_POST['description']))."', context_path='".str_replace("'","''",trim($_POST['context_path']))."', db_prefix='".str_replace("'","''",trim($_POST['db_prefix']))."', db_host='".str_replace("'","''",trim($_POST['db_host']))."', db_port='".str_replace("'","''",trim($_POST['db_port']))."', db_name='".str_replace("'","''",trim($_POST['db_name']))."', db_username='".str_replace("'","''",trim($_POST['db_username']))."', db_password='".str_replace("'","''",trim($_POST['db_password']))."' WHERE id=".$appID);
			// change FILE SYSTEM
			if (trim($_POST['context_path']) != $oldContextPath) rename($CFG->rootDir.'/deploy/'.$oldContextPath, $CFG->rootDir.'/deploy/'.trim($_POST['context_path']));
			// Application file: index.php
			$fp = fopen($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/index.php', 'w');
			fwrite($fp, "<?php\r\n");
			fwrite($fp, '	$appID = '.$appID.";\r\n");
			fwrite($fp, '	$appName = \''.trim($_POST['name'])."';\r\n");
			fwrite($fp, '	$appContextPath = \''.trim($_POST['context_path'])."';\r\n");
			fwrite($fp, '	$appDebuggable = false;'."\r\n");
			fwrite($fp, "	require_once(\"../../engine_debug.php\");\r\n");
			fwrite($fp, "?>\r\n");
			fclose($fp);
			// Application file: debug.php
			$fp2 = fopen($CFG->rootDir.'/deploy/'.trim($_POST['context_path']).'/debug.php', 'w');
			fwrite($fp2, "<?php\r\n");
			fwrite($fp2, '	$appID = '.$appID.";\r\n");
			fwrite($fp2, '	$appName = \''.trim($_POST['name'])."';\r\n");
			fwrite($fp2, '	$appContextPath = \''.trim($_POST['context_path'])."';\r\n");
			fwrite($fp2, '	$appDebuggable = true;'."\r\n");
			fwrite($fp2, "	require_once(\"../../engine_debug.php\");\r\n");
			fwrite($fp2, "?>\r\n");
			fclose($fp2);
		}
		header("location: ../index.php");
	}
	
?>
<form id="form" action="app_addmod.php" method="post">
	<div style="background-color:#dddddd; border:solid 1px black; padding: 7px; width:306px;">
		<div>Name:&nbsp;&nbsp;&nbsp;<input id="name" name="name" type="text" value="<?php if (isset($_POST['name'])) echo $_POST['name'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['name'])) echo $_ERROR['name'] ?></span></div>
		<div>&nbsp;</div>
		<div>Description:</div><div><textarea id="description" name="description" type="text" value="" style="width:300px; height:80px; resize: none;"><?php if (isset($_POST['description'])) echo $_POST['description'] ?></textarea></div>
		<div>&nbsp;</div>
		<div>Context Path:&nbsp;&nbsp;&nbsp;<input id="context_path" name="context_path" type="text" value="<?php if (isset($_POST['context_path'])) echo $_POST['context_path'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['context_path'])) echo $_ERROR['context_path'] ?></span></div>
		<div>DB Table Prefix:&nbsp;&nbsp;&nbsp;<input id="db_prefix" name="db_prefix" type="text" value="<?php if (isset($_POST['db_prefix'])) echo $_POST['db_prefix'] ?>" /></div>

		<div>DB Host:&nbsp;&nbsp;&nbsp;<input id="db_host" name="db_host" type="text" value="<?php if (isset($_POST['db_host'])) echo $_POST['db_host'] ?>" /></div>
		<div>DB Port:&nbsp;&nbsp;&nbsp;<input id="db_port" name="db_port" type="text" value="<?php if (isset($_POST['db_port'])) echo $_POST['db_port'] ?>" /> <span style="color:red;"><?php if (isset($_ERROR['db_port'])) echo $_ERROR['db_port'] ?></span></div>
		<div>DB Name:&nbsp;&nbsp;&nbsp;<input id="db_name" name="db_name" type="text" value="<?php if (isset($_POST['db_name'])) echo $_POST['db_name'] ?>" /></div>
		<div>DB Username:&nbsp;&nbsp;&nbsp;<input id="db_username" name="db_username" type="text" value="<?php if (isset($_POST['db_username'])) echo $_POST['db_username'] ?>" /></div>
		<div>DB Password:&nbsp;&nbsp;&nbsp;<input id="db_password" name="db_password" type="password" value="<?php if (isset($_POST['db_password'])) echo $_POST['db_password'] ?>" /></div>
		
		<div>&nbsp;</div>
		<div style="text-align: center;"><button id="action" name="action" type="submit" value="save">Save application</button></div>
		<input id="id" name="id" type="hidden" value="<?php echo $appID ?>" />
	</div>
</form>

</body>
</html>