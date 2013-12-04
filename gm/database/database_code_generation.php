<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>.: COORAD :.</title>
  <script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
  <script src="/gm/js/jquery-ui-1.10.2/ui/jquery-ui.js"></script>
  <link rel="shortcut icon" type="image/x-icon" href="/gm/favicon.ico">
  <link rel="stylesheet" type="text/css" href="/gm/js/jquery-ui-1.10.2/themes/base/jquery-ui.css" />
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

		$appName = '';
		$appContextPath = '';
		$appDbPrefix = '';
		$appDbHost = '';
		$appDbPort = '';
		$appDbName = '';
		$appDbUsername = '';
		$appDbPassword = '';
		$res = $DB->execute("SELECT name, context_path, db_prefix, db_host, db_port, db_name, db_username, db_password FROM gm_application WHERE id = ".$appID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$appName = $row->name;
				$appContextPath = $row->context_path;
				$appDbPrefix = $row->db_prefix;
				$appDbHost = $row->db_host;
				$appDbPort = $row->db_port;
				$appDbName = $row->db_name;
				$appDbUsername = $row->db_username;
				$appDbPassword = $row->db_password;
			}
			$res->close();
		}
		if ($appDbHost == $CFG->dbHost && $appDbPort == $CFG->dbPort && $appDbName == $CFG->dbName) {
			$APPDB = $DB;
			//echo 'Stesso DB';
		}
		else {
			$APPDB = new DB($appDbHost, $appDbUsername, $appDbPassword, $appDbName, $appDbPort, 0);
			$APPDB->connect();
			//echo 'ALTRO DB';
		}

		
		// schema DB (tables by DB_prefix)
		$tables = array();
		$colums = array();
		$columDetails = array();
		$res = $APPDB->execute("SHOW TABLES");
		if ($res) {
			while ($row = $res->fetch_array()) {
				if (stripos($row[0], $appDbPrefix) === 0 || ($appDbPrefix == '' && stripos($row[0], 'gm_') !== 0)) {
					$tables[] = $row[0];
					$colums[$row[0]] = array();
					$columDetails[$row[0]] = array();
				}
			}
			$res->close();			
		}
		for ($i = 0; $i < count($tables); $i++) {
			//echo $tables[$i].'<br />';
			$res = $APPDB->execute("SHOW COLUMNS FROM ".$tables[$i]);
			if ($res) {
				while ($row = $res->fetch_array(MYSQLI_ASSOC)) {
					//print_r($row);
					$colums[$tables[$i]][] = $row['Field'];
					$columDetails[$tables[$i]][$row['Field']] = array();
					$columDetails[$tables[$i]][$row['Field']]['type'] = $row['Type'];
					$columDetails[$tables[$i]][$row['Field']]['nullable'] = $row['Null'];
					$columDetails[$tables[$i]][$row['Field']]['default'] = $row['Default'];
					$columDetails[$tables[$i]][$row['Field']]['key'] = $row['Key'];
					$columDetails[$tables[$i]][$row['Field']]['extra'] = $row['Extra'];
				}
				$res->close();			
			}
		}

		
		// fields from DB
		$tableIdByDB = array();
		$entityByDB = array();
		$datamodelByDB = array();
		$attributeByDB = array();
		$attributeFromByDB = array();
		$attributeToByDB = array();
		$foreignKeysNumber = array();
		$res = $DB->execute("SELECT id, table_name, entity FROM gm_database_table WHERE id_application = ".$appID);
		if ($res) {
			while ($row = $res->fetch_object()) {
				$tableIdByDB[$row->table_name] = $row->id;
				$entityByDB[$row->table_name] = $row->entity;
				//if ($entityByDB[$row->table_name] == '') $entityByDB[$row->table_name] = $row->table_name;
				$datamodelByDB[$row->table_name] = array();
				$attributeByDB[$row->table_name] = array();
				$attributeFromByDB[$row->table_name] = array();
				$attributeToByDB[$row->table_name] = array();
				//echo $row->table_name.'='.$entityByDB[$row->table_name].'<br />';
			}
			$res->close();			
		}
		foreach ($tableIdByDB as $tableName => $tableId) {
			//echo $tableName.'<br />';
			$res = $DB->execute("SELECT column_name, data_model, attribute, attribute_from, attribute_to FROM gm_database_column WHERE id_table = ".$tableId);
			$foreignKeysNumber[$tableName] = 0;
			if ($res) {
				while ($row = $res->fetch_object()) {
					//print_r($row);
					$datamodelByDB[$tableName][$row->column_name] = $row->data_model;
					$attributeByDB[$tableName][$row->column_name] = $row->attribute;
					//if ($attributeByDB[$tableName][$row->column_name] == '') $attributeByDB[$tableName][$row->column_name] = $row->column_name;
					$attributeFromByDB[$tableName][$row->column_name] = $row->attribute_from;
					//if ($attributeFromByDB[$tableName][$row->column_name] == '') $attributeFromByDB[$tableName][$row->column_name] = 'From';
					$attributeToByDB[$tableName][$row->column_name] = $row->attribute_to;
					//if ($attributeToByDB[$tableName][$row->column_name] == '') $attributeToByDB[$tableName][$row->column_name] = 'To';
					if ($row->data_model == 'FOREIGN_KEY') $foreignKeysNumber[$tableName]++;
				}
				$res->close();			
			}
		}
		

		// form fields
		$entity = array();
		$datamodel = array();
		$attribute = array();
		$attributeFrom = array();
		$attributeTo = array();
		$bgcolor = array();
		for ($i = 0; $i < count($tables); $i++) {
			$entity[$tables[$i]] = '';
			if (isset($entityByDB[$tables[$i]])) $entity[$tables[$i]] = $entityByDB[$tables[$i]];
			if ($entity[$tables[$i]] == '') $entity[$tables[$i]] = $tables[$i];
			for ($j = 0; $j < count($colums[$tables[$i]]); $j++) {
				$warningLevel = 0;
				$datamodel[$tables[$i]][$colums[$tables[$i]][$j]] = '';
				if (isset($datamodelByDB[$tables[$i]][$colums[$tables[$i]][$j]])) $datamodel[$tables[$i]][$colums[$tables[$i]][$j]] = $datamodelByDB[$tables[$i]][$colums[$tables[$i]][$j]];
				if ($datamodel[$tables[$i]][$colums[$tables[$i]][$j]] == '') {
					if ($warningLevel < 1) $warningLevel = 1;
				}
				$attribute[$tables[$i]][$colums[$tables[$i]][$j]] = '';
				if (isset($attributeByDB[$tables[$i]][$colums[$tables[$i]][$j]])) $attribute[$tables[$i]][$colums[$tables[$i]][$j]] = $attributeByDB[$tables[$i]][$colums[$tables[$i]][$j]];
				if ($attribute[$tables[$i]][$colums[$tables[$i]][$j]] == '') {
					$attribute[$tables[$i]][$colums[$tables[$i]][$j]] = $colums[$tables[$i]][$j];
					if ($warningLevel < 1) $warningLevel = 1;
				}
				$attributeFrom[$tables[$i]][$colums[$tables[$i]][$j]] = '';
				if (isset($attributeFromByDB[$tables[$i]][$colums[$tables[$i]][$j]])) $attributeFrom[$tables[$i]][$colums[$tables[$i]][$j]] = $attributeFromByDB[$tables[$i]][$colums[$tables[$i]][$j]];
				/*if ($attributeFrom[$tables[$i]][$colums[$tables[$i]][$j]] == '') {
					$attributeFrom[$tables[$i]][$colums[$tables[$i]][$j]] = 'from';
					if ($warningLevel < 1) $warningLevel = 1;
				}*/
				$attributeTo[$tables[$i]][$colums[$tables[$i]][$j]] = '';
				if (isset($attributeToByDB[$tables[$i]][$colums[$tables[$i]][$j]])) $attributeTo[$tables[$i]][$colums[$tables[$i]][$j]] = $attributeToByDB[$tables[$i]][$colums[$tables[$i]][$j]];
				/*if ($attributeTo[$tables[$i]][$colums[$tables[$i]][$j]] == '') {
					$attributeTo[$tables[$i]][$colums[$tables[$i]][$j]] = 'to';
					if ($warningLevel < 1) $warningLevel = 1;
				}*/
				if ($warningLevel == 0) $bgcolor[$tables[$i]][$colums[$tables[$i]][$j]] = '';
				else if ($warningLevel == 1) $bgcolor[$tables[$i]][$colums[$tables[$i]][$j]] = '#ffff00';
			}
		}
		
		//print_r($tables); echo '<br />';
		//print_r($colums); echo '<br />';
		//print_r($columDetails); echo '<br />';
		//print_r($attribute);

		$pagemodelId = array();
		$pagemodelName = array();
		$pagemodelDescription = array();
		$res = $DB->execute("SELECT id, name, description FROM gm_page WHERE id_application=".$appID." AND name LIKE '%_ENTITY_%'");
		if ($res) {
			while ($row = $res->fetch_object()) {
				//echo $row->id."<br />";
				$pagemodelId[] = $row->id;
				$pagemodelName[$row->id] = $row->name;
				$pagemodelDescription[$row->id] = $row->description;
			}
			$res->close();			
		}
		
?>

<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Database</a> &gt; Code Generation</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Code generation</h2>
<div>
	&nbsp;&nbsp;&nbsp;Table: <input type="text" name="filter_name" id="filter_name" value="" /> 
	&nbsp;&nbsp;&nbsp;<input type="button" name="filter_button" id="filter_button" value="Filter" />
</div>
<div>
	&nbsp;&nbsp;&nbsp;<a style="text-decoration:none; color:black;" href="index.php?appId=<?php echo $appID; ?>"><img src="/gm/img/backward.png" title="Back to Database"/>&nbsp;&nbsp;Back to Database</a>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<!--a style="text-decoration:none; color:black;" href="database_code_generation.php?appId=<?php echo $appID; ?>">Code generation&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Code generation"/></a-->
</div>


<form id="form" action="database_code_generation.php" method="post">
<?php
		for ($i = 0; $i < count($tables); $i++) {
?>
<table class="dbtable" style="border: 1px solid black; border-collapse: collapse; width: 900px; margin: 15px; font-size: 11px;">
	<tr style="background-color:#dddddd;">
		<td colspan="7" class="dbtablename"><strong><?php echo $tables[$i]; ?></strong></td>
		<td style="text-align:right;"><strong>Table label:</strong></td>
		<td colspan="2"><?php echo $entity[$tables[$i]]; ?></td>
	</tr>
	<tr style="background-color:#eeeeee;">
		<td style="border: 1px dotted black; width: 12%;"><strong>Name</strong></td>
		<td style="border: 1px dotted black; width: 12%;"><strong>Type</strong></td>
		<td style="border: 1px dotted black; width: 7%;"><strong>Nullable</strong></td>
		<td style="border: 1px dotted black; width: 8%;"><strong>Default</strong></td>
		<td style="border: 1px dotted black; width: 5%;"><strong>Key</strong></td>
		<td style="border: 1px dotted black; width: 12%;"><strong>Extra</strong></td>
		<td style="border: 1px dotted black; width: 15%;"><strong>Model</strong></td>
		<td style="border: 1px dotted black; width: 13%;"><strong>Column Label</strong></td>
		<td style="border: 1px dotted black; width: 8%;"><strong>From label</strong></td>
		<td style="border: 1px dotted black; width: 8%;"><strong>To label</strong></td>
	</tr>

<?php
			for ($j = 0; $j < count($colums[$tables[$i]]); $j++) {
				$typeForModel = $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['type'];
				$keyForModel = $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['key'];
				$modelOptionLabelArray = array();
				$modelOptionValueArray = array();
				$maxSize = 0;
				if ($keyForModel == 'PRI') {
					$modelOptionLabelArray = array('PRIMARY KEY', 'UNUSED');
					$modelOptionValueArray = array('PRIMARY_KEY', 'UNUSED');
				}
				else if ($keyForModel == 'MUL') {
					$modelOptionLabelArray = array('FOREIGN KEY', 'NUMERIC', 'UNUSED');
					$modelOptionValueArray = array('FOREIGN_KEY', 'NUMERIC', 'UNUSED');
				}
				else if (preg_match("/char\([0-9]+\)/", $typeForModel)) {
					$modelOptionLabelArray = array('TEXT', 'IMAGE', 'FILE', 'URL', 'UNUSED');
					$modelOptionValueArray = array('TEXT', 'IMAGE', 'FILE', 'URL', 'UNUSED');
					preg_match("/[0-9]+/", $typeForModel, $matches);
					$maxSize = $matches[0];
				}
				else if (preg_match("/enum/", $typeForModel)) {
					$modelOptionLabelArray = array('TEXT', 'UNUSED');
					$modelOptionValueArray = array('TEXT', 'UNUSED');
				}
				else if (preg_match("/tinyint\(1\)/", $typeForModel)) {
					$modelOptionLabelArray = array('BOOLEAN', 'NUMERIC', 'UNUSED');
					$modelOptionValueArray = array('BOOLEAN', 'NUMERIC', 'UNUSED');
					$maxSize = 1;
				}
				else if (preg_match("/int\([0-9]+\)/", $typeForModel)) {
					$modelOptionLabelArray = array('NUMERIC', 'UNUSED');
					$modelOptionValueArray = array('NUMERIC', 'UNUSED');
					preg_match("/[0-9]+/", $typeForModel, $matches);
					$maxSize = $matches[0];
				}
				else if (preg_match("/decimal/", $typeForModel)) {
					$modelOptionLabelArray = array('NUMERIC', 'UNUSED');
					$modelOptionValueArray = array('NUMERIC', 'UNUSED');
				}
				else if (preg_match("/datetime/", $typeForModel)) {
					$modelOptionLabelArray = array('DATETIME', 'UNUSED');
					$modelOptionValueArray = array('DATETIME', 'UNUSED');
				}
				else if (preg_match("/date/", $typeForModel)) {
					$modelOptionLabelArray = array('DATE', 'UNUSED');
					$modelOptionValueArray = array('DATE', 'UNUSED');
				}
				else if (preg_match("/time/", $typeForModel)) {
					$modelOptionLabelArray = array('TIME', 'UNUSED');
					$modelOptionValueArray = array('TIME', 'UNUSED');
				}
				if ($datamodel[$tables[$i]][$colums[$tables[$i]][$j]] != '' && !in_array($datamodel[$tables[$i]][$colums[$tables[$i]][$j]], $modelOptionValueArray)) $bgcolor[$tables[$i]][$colums[$tables[$i]][$j]] = '#ff0000';
?>
	<tr style="background-color:<?php echo $bgcolor[$tables[$i]][$colums[$tables[$i]][$j]]; ?>;">
		<td style="border: 1px dotted black;"><?php echo $colums[$tables[$i]][$j]; ?></td>
		<td style="border: 1px dotted black;"><?php echo $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['type']; ?></td>
		<td style="border: 1px dotted black;"><?php echo $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['nullable']; ?></td>
		<td style="border: 1px dotted black;"><?php echo $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['default']; ?></td>
		<td style="border: 1px dotted black;"><?php echo $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['key']; ?></td>
		<td style="border: 1px dotted black;"><?php echo $columDetails[$tables[$i]][$colums[$tables[$i]][$j]]['extra']; ?></td>
		<td style="border: 1px dotted black;"><?php echo $datamodel[$tables[$i]][$colums[$tables[$i]][$j]]; ?></td>
		<td style="border: 1px dotted black;"><?php echo $attribute[$tables[$i]][$colums[$tables[$i]][$j]]; ?></td>
		<td style="border: 1px dotted black;"><?php echo $attributeFrom[$tables[$i]][$colums[$tables[$i]][$j]]; ?></td>
		<td style="border: 1px dotted black;"><?php echo $attributeTo[$tables[$i]][$colums[$tables[$i]][$j]]; ?></td>
	</tr>
<?php
			}
			for ($j = 0; $j < count($pagemodelId); $j++) {
				// per una sola fk
				/*if (($foreignKeysNumber[$tables[$i]] == 0 && preg_match('/__ENTITY__/', $pagemodelName[$pagemodelId[$j]])) ||
					($foreignKeysNumber[$tables[$i]] == 1 && preg_match('/__REL_ENTITY__/', $pagemodelName[$pagemodelId[$j]])) ||
					($foreignKeysNumber[$tables[$i]] == 2 && preg_match('/__REL2_ENTITY__/', $pagemodelName[$pagemodelId[$j]]))) {*/
				// per generalizzare il numero di fk
				/*if (($foreignKeysNumber[$tables[$i]] == 0 && preg_match('/__ENTITY__/', $pagemodelName[$pagemodelId[$j]])) ||
					($foreignKeysNumber[$tables[$i]] >= 1 && preg_match('/__REL_ENTITY__/', $pagemodelName[$pagemodelId[$j]]))) {*/
				if ($foreignKeysNumber[$tables[$i]] >= 0 && preg_match('/__REL_ENTITY__/', $pagemodelName[$pagemodelId[$j]])) {
?>
	<tr style="background-color:#dddddd;">
		<td style="border: 1px dotted black; border-right:0px;" colspan="7"><?php echo $pagemodelName[$pagemodelId[$j]].' ('.$pagemodelDescription[$pagemodelId[$j]].')'; ?></td>
		<td style="border: 1px dotted black; border-left:0px;" colspan="3">
			<a href="database_modelcheck_page_xml_export.php?id=<?php echo $pagemodelId[$j]; ?>&tableid=<?php echo $tableIdByDB[$tables[$i]]; ?>"><img src="/gm/img/check.png" style="vertical-align:middle; cursor:pointer;" title="Check model generateing xml page code"/></a>
			<a href="database_generated_page_xml_export.php?id=<?php echo $pagemodelId[$j]; ?>&tableid=<?php echo $tableIdByDB[$tables[$i]]; ?>"><img src="/gm/img/export.png" style="vertical-align:middle; cursor:pointer;" title="Export generated page code"/></a>
<?php if ($authorized) { ?>			
			<a class="reuse" href="database_generated_page_load.php?id=<?php echo $pagemodelId[$j]; ?>&tableid=<?php echo $tableIdByDB[$tables[$i]]; ?>"><img src="/gm/img/build.png" style="vertical-align:middle; cursor:pointer;" title="Load generated page code"/></a>
<?php } ?>			
		</td>
	</tr>
<?php
				}
			}
?>
</table>
<?php
		}
?>
	<input id="appId" name="appId" type="hidden" value="<?php echo $appID; ?>" />
	<!--button id="action" name="action" type="submit" value="save">Generate selected pages code</button-->
</form>

<div id="reuseConfirmModalDialogBox" title="Objects reuse confirmation" style="display:none;">
	<div style="padding-bottom:10px; text-align:justify;">Do you want to reuse existing objects?</div>
	<div style="padding-bottom:10px; text-align:justify;">If an object exists and reuseing is confirmed then the system will reuse the existing one, otherwise it will create a new one appending time on its name.</div>
	<div style="padding-bottom:10px; text-align:justify;"><i>If you close the window without click any button then no import will be applied by the system.</i></div>
</div>

<?php
	}
?>

<script>
	$("#apply_from_to").click(function() {
		//alert($('#all_attribute_from').val());
		$('.attribute_from').attr('value', $('#all_attribute_from').val());
		$('.attribute_to').attr('value', $('#all_attribute_to').val());
	});

	$('#filter_button').click(function() {
		//alert($('#filter_name').val());
		$(".dbtable").each(function () {
			if (($(this).children().children().children('.dbtablename').text().toLowerCase()).indexOf($.trim($('#filter_name').val().toLowerCase())) == -1)
				//alert($(this).children().children().children('.dbtablename').text());
				$(this).css('display','none');
			else $(this).css('display','block');
		});
	});
	
	$("#action").click(function() {
		var allFieldsFilled = true;
		$(".attribute").each(function () {
			//alert($.trim($(this).val()));
			if ($.trim($(this).val()) == '') {
				allFieldsFilled = false;
				$(this).css('background-color', '#ffaa00');
			}
		});
		if (!allFieldsFilled) alert('Some column name missing.');
		return allFieldsFilled;
	});
	
	$('.reuse').click(function() {
		var href = $(this).attr('href');
		$('#reuseConfirmModalDialogBox').dialog({
			modal: true,
			resizable: false,
			closeText: "Close",
			buttons: {
				"Reuse": function() {
					href = href + '&reuse=true';
					//alert('Reuse ' + href);
					$(this).dialog('close');
					window.location.href = href;
				},
				"Don\'t reuse": function() {
					href = href + '&reuse=false';
					//alert('Don\'t reuse ' + href);
					$(this).dialog('close');
					window.location.href = href;
				}
			},
			close: function() {
				//$('#reuse').attr('value','');
			}
		});
		$('.ui-dialog').css('font-size','12px');
		return false;
	});
	
</script>

</body>
</html>