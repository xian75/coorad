<!DOCTYPE html>
<html>
<head>
  <script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
  <script src="/gm/js/jquery-ui-1.10.2/ui/jquery-ui.js"></script>
  <script src="/gm/js/jquery-ui-1.10.2/jquery-ui-timepicker-addon.js"></script>
  <link rel="stylesheet" type="text/css" href="/gm/js/jquery-ui-1.10.2/themes/base/jquery-ui.css" />
  <link rel="stylesheet" type="text/css" href="/gm/css/style.css" />
  <link rel="shortcut icon" type="image/x-icon" href="<?php if (isset($appContextPath)) echo '/gm/deploy/'.$appContextPath.'/img/favicon.ico'; else echo '/gm/favicon.ico'; ?>">
  <style id="dinamic_css" type="text/css">
  </style>
  <title><?php if (isset($appName)) echo $appName; else echo '.: COORAD :.'; ?></title>
</head>
<body id="bodyengine" style="margin-left:0px; margin-right:0px; margin-top:0px; margin-bottom:0px; font-size:0px;">

<?php if (!isset($appDebuggable) || $appDebuggable) { ?>
<div id="debugbutton" style="cursor:pointer; background-color:#ffaaaa; margin-bottom:0px; font-size:14px;">DEBUG MODE: off</div>

<!-- apertura div di DEBUG -->
<div class="debug" style="font-size:14px;">
<?php } ?>

<?php if (!isset($appDebuggable) || $appDebuggable) { ?>
<h2>ENGINE DEBUG</h2>
<?php } ?>
<!--div id="sampleurl">SAMPLE: http://localhost:8080/gm/engine_debug.php?azione=Azione+1&amp;username=cnatale&amp;section=45</div-->
<?php
	require_once("init.php");
	require("PHPMailer_v5.1/class.phpmailer.php");

	// Report simple running errors
	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	// Reporting E_NOTICE can be good too (to report uninitialized
	// variables or catch variable name misspellings ...)
	//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	
	if (isset($appID)) {
		if (!isset($appDebuggable) || $appDebuggable) $formAction = 'debug.php';
		else $formAction = 'index.php';
	} else $formAction = 'engine_debug.php';
	
	if (isset($appID)) {
		$appDbHost = '';
		$appDbPort = '';
		$appDbName = '';
		$appDbUsername = '';
		$appDbPassword = '';
		$res = $DB->execute("SELECT db_host, db_port, db_name, db_username, db_password FROM gm_application WHERE id = ".$appID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$appDbHost = $row->db_host;
				$appDbPort = $row->db_port;
				$appDbName = $row->db_name;
				$appDbUsername = $row->db_username;
				$appDbPassword = $row->db_password;
			}
		}
		$res->close();
		if ($appDbHost == $CFG->dbHost && $appDbPort == $CFG->dbPort && $appDbName == $CFG->dbName) {
			$APPDB = $DB;
			//echo 'Stesso DB';
		}
		else {
			$APPDB = new DB($appDbHost, $appDbUsername, $appDbPassword, $appDbName, $appDbPort, 0);
			$APPDB->connect();
			//echo 'ALTRO DB';
		}
	} 
	else {
		$APPDB = $DB;
		//echo 'Stesso DB';
	}
	
	
	/**********/
	/* ACTION */
	/**********/
	$next_page = 'home';
	// Recupero della eventuale ACTION
	$actionID = 0;
	$command_on_success = '';
	$command_on_fail = '';
	$next_page_on_success = '';
	$next_page_on_fail = '';
	$res = $DB->execute("SELECT id, name, value, command, command_on_success, command_on_fail, next_page_on_success, next_page_on_fail FROM gm_action WHERE id_application = ".$appID);
	if($res) {
		while ($row = $res->fetch_object()) {
			//if ((isset($_GET[$row->name]) && $_GET[$row->name] == $row->value) ||
			//	(isset($_POST[$row->name]) && $_POST[$row->name] == $row->value)) {
			$actionValue = '';
			if (isset($_GET[$row->name])) $actionValue = $_GET[$row->name];
			else if (isset($_POST[$row->name])) $actionValue = $_POST[$row->name];
			$actionValueSplitted = explode(';', $actionValue);
			//if (!isset($appDebuggable) || $appDebuggable) print_r($actionValueSplitted); echo '<br />';
			if ($actionValueSplitted[0] == $row->value) {
				if (isset($_GET[$row->name])) $_GET[$row->name] = $actionValueSplitted[0];
				else if (isset($_POST[$row->name])) $_POST[$row->name] = $actionValueSplitted[0];
				for ($i = 1; $i < count($actionValueSplitted); $i++) {
					$actionSubParameter = explode(':', $actionValueSplitted[$i]);
					if (count($actionSubParameter) == 2) {
						if (isset($_GET[$row->name])) $_GET[$actionSubParameter[0]] = $actionSubParameter[1];
						else if (isset($_POST[$row->name])) $_POST[$actionSubParameter[0]] = $actionSubParameter[1];
						if (!isset($appDebuggable) || $appDebuggable) echo $actionSubParameter[0].'='.$actionSubParameter[1].'<br />';
					}
				}
				// ACTION selezionata in base al parametro GET/POST "action_name = action_value"
				$actionID = $row->id;
				$actionName = str_replace("'","&rsquo;",$row->name.'='.$row->value);
				$actionCommand = $row->command;
				$command_on_success = $row->command_on_success;
				$command_on_fail = $row->command_on_fail;
				$next_page_on_success = $row->next_page_on_success;
				$next_page_on_fail = $row->next_page_on_fail;
				if ($next_page_on_success == '') {
					if (isset($_GET['previous_page'])) $next_page_on_success = $_GET['previous_page'];
					if (isset($_POST['previous_page'])) $next_page_on_success = $_POST['previous_page'];
				}
				if ($next_page_on_fail == '') {
					if (isset($_GET['previous_page'])) $next_page_on_fail = $_GET['previous_page'];
					if (isset($_POST['previous_page'])) $next_page_on_fail = $_POST['previous_page'];
				}
			}
		}
	}
	$res->close();
	if ($actionID > 0) {
		if (!isset($appDebuggable) || $appDebuggable) echo '<h2 id="titlepage">Action <i style="background-color:yellow;">"'.$actionName.'"</i> Next Page</h2>';
		// Esegue i comandi della action
		if (!isset($appDebuggable) || $appDebuggable) echo 'actionCommand: '.$actionCommand.'<br />';
		if ($actionCommand != '') eval($actionCommand);
		// Esegue le CHECK QUERY
		$checkQueryArray = array();
		$sql = "";
		$res = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_check_query WHERE id_action=".$actionID." ORDER BY index_query ASC, index_subquery ASC");
		if($res){
			$oldQueryIndexStart = 0;
			$oldQueryIndexEnd = 0;
			$isNewQuery = true;
			$isAddable = false;
			while ($row = $res->fetch_object()){
				if ($oldQueryIndexEnd == 0) $oldQueryIndexEnd = 1;
				if ($oldQueryIndexEnd != $row->index_query) {
					$oldQueryIndexEnd = $row->index_query;
					// end query
					if ($isAddable) $checkQueryArray[$oldQueryIndexStart] = $sql;
				}
				if ($oldQueryIndexStart != $row->index_query) {
					$oldQueryIndexStart = $row->index_query;
					// start new query
					$sql = "";
					$isNewQuery = true;
					$isAddable = false;
					if (!isset($appDebuggable) || $appDebuggable) echo "<br />";
				}
				// da notare il comando nel campo "if" : "RETURN ... ;"
				if ($row->condition_string == null || str_replace('/*block*/', '', str_replace('/*all*/', '', $row->condition_string)) == '') $row->condition_string = 'true';
				if (!isset($appDebuggable) || $appDebuggable) echo 'IF '.$row->condition_string.' THEN SQL.='.$row->sql_string.'<br />';
				if (eval("return ".$row->condition_string.";")) {
					eval('$sql.="'.$row->sql_string.' ";');
					if ($isNewQuery) $isAddable = true;
				}
				$isNewQuery = false;
			}
			$res->close();
			if ($oldQueryIndexEnd != 0) {
				// end last query
				if ($isAddable) $checkQueryArray[$oldQueryIndexStart] = $sql;
			}
		}
		if (!isset($appDebuggable) || $appDebuggable) echo '<br />';
		//$initQueryResArray = array();
		//$columnToResArray = array();
		$tableColumnNameArray = array();
		$_QUERY = array();
		foreach ($checkQueryArray as $i => $value) {
			if (!isset($appDebuggable) || $appDebuggable) echo $checkQueryArray[$i].'<br />';
			$res = $APPDB->execute($checkQueryArray[$i]);
			if ($res) {
				//$initQueryResArray[] = $res;
				$tableColumnKey = '';
				if ($row = $res->fetch_array(MYSQLI_ASSOC)) {
					$tableColumnKey = array_keys($row);
					$tableColumnNameArray = array_merge($tableColumnNameArray, $tableColumnKey);
					//$_QUERY = array_merge($_QUERY, $row);
					foreach ($tableColumnKey as $j => $value) {
						$_QUERY[$tableColumnKey[$j]] = array($row[$tableColumnKey[$j]]);
					}
					//$columnToResArrayToMerge = $row;
					//foreach ($columnToResArrayToMerge as $j => $value) $columnToResArrayToMerge[$j] = count($initQueryResArray) - 1;
					//$columnToResArray = array_merge($columnToResArray, $columnToResArrayToMerge);
				}
				while ($row = $res->fetch_array(MYSQLI_ASSOC)) {
					foreach ($tableColumnKey as $j => $value) {
						$_QUERY[$tableColumnKey[$j]][] = $row[$tableColumnKey[$j]];
					}
					//printf ("%s (%s)\n", $row["persona.nome"], $row["persona.cognome"]);
					/*foreach ($row as $key) {
						echo $row[$val];
					}*/
					//print_r ($row);
				}
				$res->close();
			}
		}
		//echo '<br />Res Buffer Array: ';
		//print_r($initQueryResArray);
		//echo '<br />Indice del buffer dei risultati $res delle query in base alla KEY di $_QUERY: ';
		//print_r($columnToResArray);
		if (!isset($appDebuggable) || $appDebuggable) { 
			echo '<br />Nomi delle colonne dei risultati delle check query: ';
			print_r($tableColumnNameArray);
			echo '<br />Risultati delle check query: ';
			print_r($_QUERY);
			echo '<br /><br />';
		}
		
		// Esegue i CHECK FIELD
		$_ERROR = array();
		$res = $DB->execute("SELECT condition_string, error FROM gm_action_check_field WHERE id_action=".$actionID);
		if($res){
			while ($row = $res->fetch_object()){
				// da notare il comando nel campo "if" : "RETURN ... ;"
				if ($row->condition_string == null || str_replace('/*block*/', '', str_replace('/*all*/', '', $row->condition_string)) == '') $row->condition_string = 'true';
				if (!isset($appDebuggable) || $appDebuggable) echo 'IF '.$row->condition_string.' THEN ERROR... '.$row->error.'<br />';
				
				//if (eval("return ".$row_condition_string.";")) {
				//	eval($row_error);
				//}
				
				$row_condition_string = $row->condition_string;
				// sostituzione dei parametri $_QUERY[nnn][x]
				$queryMatrixKeyIndexOccur = array();
				$queryMatrixKeyOccur = array();
				$queryMatrixIndexOccur = array();
				preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $row_condition_string, $queryMatrixKeyIndexOccur);
				//print_r($queryMatrixKeyIndexOccur);
				foreach ($queryMatrixKeyIndexOccur as $k => $value) {
					foreach ($queryMatrixKeyIndexOccur[$k] as $m => $value) {
						$strKeyPos = strpos($queryMatrixKeyIndexOccur[$k][$m], ']');
						$keyOcc = substr($queryMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
						$queryMatrixKeyOccur[] = $keyOcc;
						$strIndexPos = strripos($queryMatrixKeyIndexOccur[$k][$m], ']');
						$indexOcc = substr($queryMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
						$queryMatrixIndexOccur[] = $indexOcc;
						//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
					}
				}
				foreach ($queryMatrixKeyOccur as $j => $value) {
					$row_condition_string = str_replace("\$_QUERY[".$queryMatrixKeyOccur[$j]."][".$queryMatrixIndexOccur[$j]."]", $_QUERY[$queryMatrixKeyOccur[$j]][$queryMatrixIndexOccur[$j]], $row_condition_string);
				}
				$row_error = $row->error;
				$queryMatrixKeyIndexOccur = array();
				$queryMatrixKeyOccur = array();
				$queryMatrixIndexOccur = array();
				preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $row_error, $queryMatrixKeyIndexOccur);
				//print_r($queryMatrixKeyIndexOccur);
				foreach ($queryMatrixKeyIndexOccur as $k => $value) {
					foreach ($queryMatrixKeyIndexOccur[$k] as $m => $value) {
						$strKeyPos = strpos($queryMatrixKeyIndexOccur[$k][$m], ']');
						$keyOcc = substr($queryMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
						$queryMatrixKeyOccur[] = $keyOcc;
						$strIndexPos = strripos($queryMatrixKeyIndexOccur[$k][$m], ']');
						$indexOcc = substr($queryMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
						$queryMatrixIndexOccur[] = $indexOcc;
						//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
					}
				}
				foreach ($queryMatrixKeyOccur as $j => $value) {
					$row_error = str_replace("\$_QUERY[".$queryMatrixKeyOccur[$j]."][".$queryMatrixIndexOccur[$j]."]", $_QUERY[$queryMatrixKeyOccur[$j]][$queryMatrixIndexOccur[$j]], $row_error);
				}
				
				// sostituzione dei tag $_QUERY con #_QUERY
				// per evitare che vengano sostituiti con la
				// stringa 'Array' nei cicli successivi
				$row_condition_string = str_replace("\$_QUERY[", "#_QUERY[", $row_condition_string);
				$row_error = str_replace("\$_QUERY[", "#_QUERY[", $row_error);
				
				// Replica della QUERY secondo i campi $_QUERY[nnn]
				$queryKeyOccurForValue = array();
				preg_match_all("/#\_QUERY\[[^\]]+]/", $row_condition_string, $queryKeyOccurrences);
				//print_r($queryKeyOccurrences);
				foreach ($queryKeyOccurrences as $k => $value) {
					foreach ($queryKeyOccurrences[$k] as $m => $value) {
						$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
						if (!in_array($occ, $queryKeyOccurForValue)) {
							$queryKeyOccurForValue[] = $occ;
							//echo 'occ:'.$occ.'<br />';
						}
					}
				}
				preg_match_all("/#\_QUERY\[[^\]]+]/", $row_error, $queryKeyOccurrences);
				//print_r($queryKeyOccurrences);
				foreach ($queryKeyOccurrences as $k => $value) {
					foreach ($queryKeyOccurrences[$k] as $m => $value) {
						$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
						if (!in_array($occ, $queryKeyOccurForValue)) {
							$queryKeyOccurForValue[] = $occ;
							//echo 'occ:'.$occ.'<br />';
						}
					}
				}
				//print_r($queryKeyOccurForValue);
				if (count($queryKeyOccurForValue) > 0) {
					foreach ($_QUERY[$queryKeyOccurForValue[0]] as $i => $value) {
						$row_condition_string_item = $row_condition_string;
						$row_error_item = $row_error;
						/**/if (!isset($appDebuggable) || $appDebuggable) echo 'originalValue::'.$row_condition_string_item.'<br />';
						/**/if (!isset($appDebuggable) || $appDebuggable) echo 'originalValue::'.$row_error_item.'<br />';
						foreach ($queryKeyOccurForValue as $j => $value) {
							//echo 'originalValue::'.$row_condition_string_item.'<br />';
							$row_condition_string_item = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $row_condition_string_item);
							//echo ' - substituteValue::'.$row_condition_string_item.'<br />';
							//echo 'originalValue::'.$row_error_item.'<br />';
							$row_error_item = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $row_error_item);
							//echo ' - substituteValue::'.$row_error_item.'<br />';
						}
						/**/if (!isset($appDebuggable) || $appDebuggable) echo ' - substituteValue::'.$row_condition_string_item.'<br />';
						/**/if (!isset($appDebuggable) || $appDebuggable) echo ' - substituteValue::'.$row_error_item.'<br />';
						if (eval("return ".$row_condition_string_item.";")) {
							eval($row_error_item);
						}
					}
				}
				else {
				if (!isset($appDebuggable) || $appDebuggable) echo $row_condition_string."<br />";
				if (!isset($appDebuggable) || $appDebuggable) echo $row_error."<br />";
					if (eval("return ".$row_condition_string.";")) {
						eval($row_error);
					}
				}			
			}
		}
		if (!isset($appDebuggable) || $appDebuggable) echo "<div>&nbsp;</div><div>ERRORI</div>";
		foreach($_ERROR as $key => $value) {
			if (!isset($appDebuggable) || $appDebuggable) echo "<div>".$key."=".$value."</div>";
		}
		
		// SUCCESS OR FAIL
		//echo "<div>Numero Errori = ".count($_ERROR)."</div>";
		if (count($_ERROR) == 0) {
			// SUCCESS
			if (!isset($appDebuggable) || $appDebuggable) echo "<div>SUCCESS</div>";
			// Esegue le QUERY on SUCCESS
			$queryOnSuccessArray = array();
			$sql = "";
			$res = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_query_success WHERE id_action=".$actionID." ORDER BY index_query ASC, index_subquery ASC");
			if($res){
				$oldQueryIndexStart = 0;
				$oldQueryIndexEnd = 0;
				$isNewQuery = true;
				$isAddable = false;
				
				$queryOnSuccessArrayMulti = array();
				$sqlMulti = array();
				$isAddableMulti = array();
				if (count($tableColumnNameArray) > 0) {
					foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
						$queryOnSuccessArrayMulti[$i] = array();
						$sqlMulti[$i] = "";
						$isAddableMulti[$i] = false;
					}
				}
				
				while ($row = $res->fetch_object()){
					if ($oldQueryIndexEnd == 0) $oldQueryIndexEnd = 1;
					if ($oldQueryIndexEnd != $row->index_query) {
						$oldQueryIndexEnd = $row->index_query;
						// end query
						if ($isAddable) $queryOnSuccessArray[$oldQueryIndexStart] = $sql;
						if (count($tableColumnNameArray) > 0) {
							foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
								if ($isAddableMulti[$i]) $queryOnSuccessArrayMulti[$i][$oldQueryIndexStart] = $sqlMulti[$i];
							}
						}
					}
					if ($oldQueryIndexStart != $row->index_query) {
						$oldQueryIndexStart = $row->index_query;
						// start new query
						$sql = "";
						$isNewQuery = true;
						$isAddable = false;
						if (count($tableColumnNameArray) > 0) {
							foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
								$sqlMulti[$i] = "";
								$isAddableMulti[$i] = false;
							}
						}
						if (!isset($appDebuggable) || $appDebuggable) echo "<br />";
					}
					// da notare il comando nel campo "if" : "RETURN ... ;"
					if ($row->condition_string == null || str_replace('/*block*/', '', str_replace('/*all*/', '', $row->condition_string)) == '') $row->condition_string = 'true';
					if (!isset($appDebuggable) || $appDebuggable) echo 'IF '.$row->condition_string.' THEN SQL.='.$row->sql_string.'<br />';

					// sostituzione dei parametri $_QUERY[nnn][x] in condition_string
					$querySuccessMatrixKeyIndexOccur = array();
					$querySuccessMatrixKeyOccur = array();
					$querySuccessMatrixIndexOccur = array();
					$conditionSuccessString = $row->condition_string;
					//preg_match_all("/\\$\_QUERY\[[^\]]+]/", $conditionSuccessString, $querySuccessMatrixKeyIndexOccur);
					preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $conditionSuccessString, $querySuccessMatrixKeyIndexOccur);
					//print_r($querySuccessMatrixKeyIndexOccur);
					foreach ($querySuccessMatrixKeyIndexOccur as $k => $value) {
						foreach ($querySuccessMatrixKeyIndexOccur[$k] as $m => $value) {
							$strKeyPos = strpos($querySuccessMatrixKeyIndexOccur[$k][$m], ']');
							$keyOcc = substr($querySuccessMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
							$querySuccessMatrixKeyOccur[] = $keyOcc;
							$strIndexPos = strripos($querySuccessMatrixKeyIndexOccur[$k][$m], ']');
							$indexOcc = substr($querySuccessMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
							$querySuccessMatrixIndexOccur[] = $indexOcc;
							//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
						}
					}
					foreach ($querySuccessMatrixKeyOccur as $j => $value) {
						$conditionSuccessString = str_replace("\$_QUERY[".$querySuccessMatrixKeyOccur[$j]."][".$querySuccessMatrixIndexOccur[$j]."]", $_QUERY[$querySuccessMatrixKeyOccur[$j]][$querySuccessMatrixIndexOccur[$j]], $conditionSuccessString);
					}
					
					// sostituzione dei parametri $_QUERY[nnn][x] in sql_string
					$querySuccessMatrixKeyIndexOccur = array();
					$querySuccessMatrixKeyOccur = array();
					$querySuccessMatrixIndexOccur = array();
					$sqlSuccessString = $row->sql_string;
					//preg_match_all("/\\$\_QUERY\[[^\]]+]/", $sqlSuccessString, $querySuccessMatrixKeyIndexOccur);
					preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $sqlSuccessString, $querySuccessMatrixKeyIndexOccur);
					//print_r($querySuccessMatrixKeyIndexOccur);
					foreach ($querySuccessMatrixKeyIndexOccur as $k => $value) {
						foreach ($querySuccessMatrixKeyIndexOccur[$k] as $m => $value) {
							$strKeyPos = strpos($querySuccessMatrixKeyIndexOccur[$k][$m], ']');
							$keyOcc = substr($querySuccessMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
							$querySuccessMatrixKeyOccur[] = $keyOcc;
							$strIndexPos = strripos($querySuccessMatrixKeyIndexOccur[$k][$m], ']');
							$indexOcc = substr($querySuccessMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
							$querySuccessMatrixIndexOccur[] = $indexOcc;
							//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
						}
					}
					foreach ($querySuccessMatrixKeyOccur as $j => $value) {
						$sqlSuccessString = str_replace("\$_QUERY[".$querySuccessMatrixKeyOccur[$j]."][".$querySuccessMatrixIndexOccur[$j]."]", $_QUERY[$querySuccessMatrixKeyOccur[$j]][$querySuccessMatrixIndexOccur[$j]], $sqlSuccessString);
					}
					
					// sostituzione dei parametri $_FILES[nnn][x] in sql_string
					$querySuccessMatrixKeyIndexOccur = array();
					$querySuccessMatrixKeyOccur = array();
					$querySuccessMatrixIndexOccur = array();
					//preg_match_all("/\\$\_FILES\[[^\]]+]/", $sqlSuccessString, $querySuccessMatrixKeyIndexOccur);
					preg_match_all("/\\$\_FILES\[[^\]]+]\[[^\]]+]/", $sqlSuccessString, $querySuccessMatrixKeyIndexOccur);
					//print_r($querySuccessMatrixKeyIndexOccur);
					foreach ($querySuccessMatrixKeyIndexOccur as $k => $value) {
						foreach ($querySuccessMatrixKeyIndexOccur[$k] as $m => $value) {
							$strKeyPos = strpos($querySuccessMatrixKeyIndexOccur[$k][$m], ']');
							$keyOcc = substr($querySuccessMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
							$querySuccessMatrixKeyOccur[] = $keyOcc;
							$strIndexPos = strripos($querySuccessMatrixKeyIndexOccur[$k][$m], ']');
							$indexOcc = substr($querySuccessMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
							$querySuccessMatrixIndexOccur[] = $indexOcc;
							//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
						}
					}
					foreach ($querySuccessMatrixKeyOccur as $j => $value) {
						$sqlSuccessString = str_replace("\$_FILES[".$querySuccessMatrixKeyOccur[$j]."][".$querySuccessMatrixIndexOccur[$j]."]", $_FILES[$querySuccessMatrixKeyOccur[$j]][$querySuccessMatrixIndexOccur[$j]], $sqlSuccessString);
					}

					// sostituzione dei tag $_QUERY con #_QUERY
					// per evitare che vengano sostituiti con la
					// stringa 'Array' nei cicli successivi
					$conditionSuccessString = str_replace("\$_QUERY[", "#_QUERY[", $conditionSuccessString);
					$sqlSuccessString = str_replace("\$_QUERY[", "#_QUERY[", $sqlSuccessString);
					
					// Replica della QUERY secondo i campi $_QUERY[nnn]
					$queryKeyOccurForValue = array();
					preg_match_all("/#\_QUERY\[[^\]]+]/", $conditionSuccessString, $queryKeyOccurrences);
					//print_r($queryKeyOccurrences);
					foreach ($queryKeyOccurrences as $k => $value) {
						foreach ($queryKeyOccurrences[$k] as $m => $value) {
							$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
							if (!in_array($occ, $queryKeyOccurForValue)) {
								$queryKeyOccurForValue[] = $occ;
								//echo 'occ:'.$occ.'<br />';
							}
						}
					}
					preg_match_all("/#\_QUERY\[[^\]]+]/", $sqlSuccessString, $queryKeyOccurrences);
					//print_r($queryKeyOccurrences);
					foreach ($queryKeyOccurrences as $k => $value) {
						foreach ($queryKeyOccurrences[$k] as $m => $value) {
							$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
							if (!in_array($occ, $queryKeyOccurForValue)) {
								$queryKeyOccurForValue[] = $occ;
								//echo 'occ:'.$occ.'<br />';
							}
						}
					}
					//print_r($queryKeyOccurForValue);
					
					if (count($queryKeyOccurForValue) > 0) {
						foreach ($_QUERY[$queryKeyOccurForValue[0]] as $i => $value) {
							$conditionSuccessStringItem = $conditionSuccessString;
							$sqlSuccessStringItem = $sqlSuccessString;
							/**/if (!isset($appDebuggable) || $appDebuggable) echo 'originalValue::'.$conditionSuccessStringItem.'<br />';
							/**/if (!isset($appDebuggable) || $appDebuggable) echo 'originalValue::'.$sqlSuccessStringItem.'<br />';
							foreach ($queryKeyOccurForValue as $j => $value) {
								//echo 'originalValue::'.$conditionSuccessStringItem.'<br />';
								$conditionSuccessStringItem = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $conditionSuccessStringItem);
								//echo ' - substituteValue::'.$conditionSuccessStringItem.'<br />';
								//echo 'originalValue::'.$sqlSuccessStringItem.'<br />';
								$sqlSuccessStringItem = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $sqlSuccessStringItem);
								//echo ' - substituteValue::'.$sqlSuccessStringItem.'<br />';
							}
							/**/if (!isset($appDebuggable) || $appDebuggable) echo ' - substituteValue::'.$conditionSuccessStringItem.'<br />';
							/**/if (!isset($appDebuggable) || $appDebuggable) echo ' - substituteValue::'.$sqlSuccessStringItem.'<br />';
							if (eval("return ".$conditionSuccessStringItem.";")) {
								eval('$sqlMulti['.$i.'].="'.$sqlSuccessStringItem.' ";');
								if ($isNewQuery) $isAddableMulti[$i] = true;
							}
						}
					}
					else {
						if (eval("return ".$conditionSuccessString.";")) {
							//eval('$sql.="'.$row->sql_string.' ";');
							eval('$sql.="'.$sqlSuccessString.' ";');
							if ($isNewQuery) $isAddable = true;
						}
					}
					//if (eval("return ".$conditionSuccessString.";")) {
					//	//eval('$sql.="'.$row->sql_string.' ";');
					//	eval('$sql.="'.$sqlSuccessString.' ";');
					//	if ($isNewQuery) $isAddable = true;
					//}
					$isNewQuery = false;
				}
				$res->close();
				if ($oldQueryIndexEnd != 0) {
					// end last query
					if ($isAddable) $queryOnSuccessArray[$oldQueryIndexStart] = $sql;
					if (count($tableColumnNameArray) > 0) {
						foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
							if ($isAddableMulti[$i]) $queryOnSuccessArrayMulti[$i][$oldQueryIndexStart] = $sqlMulti[$i];
						}
					}
				}
			}
			if (!isset($appDebuggable) || $appDebuggable) echo '<br />';	
			
			// execute success query
			foreach ($queryOnSuccessArray as $i => $value) {
				if (!isset($appDebuggable) || $appDebuggable) echo 'Execute SingleSQL: '.$queryOnSuccessArray[$i].'<br />';
				$res = $APPDB->execute($queryOnSuccessArray[$i]);
			}
			if (count($tableColumnNameArray) > 0) {
				foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
					foreach ($queryOnSuccessArrayMulti[$i] as $j => $sqlvalue) {
						if (!isset($appDebuggable) || $appDebuggable) echo 'Execute MultiSQL: '.$queryOnSuccessArrayMulti[$i][$j].'<br />';
						$res = $APPDB->execute($queryOnSuccessArrayMulti[$i][$j]);
					}
				}
			}
			if (!isset($appDebuggable) || $appDebuggable) echo '<br /><br />';
			
			// command on success
			if ($command_on_success != '') eval($command_on_success);

			// next page on success
			//$next_page = $next_page_on_success;			
			if ($next_page_on_success != '') eval('$next_page_on_success="'.$next_page_on_success.'";');
			$next_page = $next_page_on_success;
		}
		else {
			// FAIL
			if (!isset($appDebuggable) || $appDebuggable) echo "<div>FAIL</div>";
			// Esegue le QUERY on FAIL
			$queryOnFailArray = array();
			$sql = "";
			$res = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_query_fail WHERE id_action=".$actionID." ORDER BY index_query ASC, index_subquery ASC");
			if($res){
				$oldQueryIndexStart = 0;
				$oldQueryIndexEnd = 0;
				$isNewQuery = true;
				$isAddable = false;
				
				$queryOnFailArrayMulti = array();
				$sqlMulti = array();
				$isAddableMulti = array();
				if (count($tableColumnNameArray) > 0) {
					foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
						$queryOnFailArrayMulti[$i] = array();
						$sqlMulti[$i] = "";
						$isAddableMulti[$i] = false;
					}
				}
				
				while ($row = $res->fetch_object()){
					if ($oldQueryIndexEnd == 0) $oldQueryIndexEnd = 1;
					if ($oldQueryIndexEnd != $row->index_query) {
						$oldQueryIndexEnd = $row->index_query;
						// end query
						if ($isAddable) $queryOnFailArray[$oldQueryIndexStart] = $sql;
						if (count($tableColumnNameArray) > 0) {
							foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
								if ($isAddableMulti[$i]) $queryOnFailArrayMulti[$i][$oldQueryIndexStart] = $sqlMulti[$i];
							}
						}
					}
					if ($oldQueryIndexStart != $row->index_query) {
						$oldQueryIndexStart = $row->index_query;
						// start new query
						$sql = "";
						$isNewQuery = true;
						$isAddable = false;
						if (count($tableColumnNameArray) > 0) {
							foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
								$sqlMulti[$i] = "";
								$isAddableMulti[$i] = false;
							}
						}
						if (!isset($appDebuggable) || $appDebuggable) echo "<br />";
					}
					// da notare il comando nel campo "if" : "RETURN ... ;"
					if ($row->condition_string == null || str_replace('/*block*/', '', str_replace('/*all*/', '', $row->condition_string)) == '') $row->condition_string = 'true';
					if (!isset($appDebuggable) || $appDebuggable) echo 'IF '.$row->condition_string.' THEN SQL.='.$row->sql_string.'<br />';

					// sostituzione dei parametri $_QUERY[nnn][x] in condition_string
					$queryFailMatrixKeyIndexOccur = array();
					$queryFailMatrixKeyOccur = array();
					$queryFailMatrixIndexOccur = array();
					$conditionFailString = $row->condition_string;
					//preg_match_all("/\\$\_QUERY\[[^\]]+]/", $conditionFailString, $queryFailMatrixKeyIndexOccur);
					preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $conditionFailString, $queryFailMatrixKeyIndexOccur);
					//print_r($queryFailMatrixKeyIndexOccur);
					foreach ($queryFailMatrixKeyIndexOccur as $k => $value) {
						foreach ($queryFailMatrixKeyIndexOccur[$k] as $m => $value) {
							$strKeyPos = strpos($queryFailMatrixKeyIndexOccur[$k][$m], ']');
							$keyOcc = substr($queryFailMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
							$queryFailMatrixKeyOccur[] = $keyOcc;
							$strIndexPos = strripos($queryFailMatrixKeyIndexOccur[$k][$m], ']');
							$indexOcc = substr($queryFailMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
							$queryFailMatrixIndexOccur[] = $indexOcc;
							//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
						}
					}
					foreach ($queryFailMatrixKeyOccur as $j => $value) {
						$conditionFailString = str_replace("\$_QUERY[".$queryFailMatrixKeyOccur[$j]."][".$queryFailMatrixIndexOccur[$j]."]", $_QUERY[$queryFailMatrixKeyOccur[$j]][$queryFailMatrixIndexOccur[$j]], $conditionFailString);
					}
					
					// sostituzione dei parametri $_QUERY[nnn][x] in sql_string
					$queryFailMatrixKeyIndexOccur = array();
					$queryFailMatrixKeyOccur = array();
					$queryFailMatrixIndexOccur = array();
					$sqlFailString = $row->sql_string;
					//preg_match_all("/\\$\_QUERY\[[^\]]+]/", $sqlFailString, $queryFailMatrixKeyIndexOccur);
					preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $sqlFailString, $queryFailMatrixKeyIndexOccur);
					//print_r($queryFailMatrixKeyIndexOccur);
					foreach ($queryFailMatrixKeyIndexOccur as $k => $value) {
						foreach ($queryFailMatrixKeyIndexOccur[$k] as $m => $value) {
							$strKeyPos = strpos($queryFailMatrixKeyIndexOccur[$k][$m], ']');
							$keyOcc = substr($queryFailMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
							$queryFailMatrixKeyOccur[] = $keyOcc;
							$strIndexPos = strripos($queryFailMatrixKeyIndexOccur[$k][$m], ']');
							$indexOcc = substr($queryFailMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
							$queryFailMatrixIndexOccur[] = $indexOcc;
							//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
						}
					}
					foreach ($queryFailMatrixKeyOccur as $j => $value) {
						$sqlFailString = str_replace("\$_QUERY[".$queryFailMatrixKeyOccur[$j]."][".$queryFailMatrixIndexOccur[$j]."]", $_QUERY[$queryFailMatrixKeyOccur[$j]][$queryFailMatrixIndexOccur[$j]], $sqlFailString);
					}
					
					// sostituzione dei parametri $_FILES[nnn][x] in sql_string
					$queryFailMatrixKeyIndexOccur = array();
					$queryFailMatrixKeyOccur = array();
					$queryFailMatrixIndexOccur = array();
					//preg_match_all("/\\$\_FILES\[[^\]]+]/", $sqlFailString, $queryFailMatrixKeyIndexOccur);
					preg_match_all("/\\$\_FILES\[[^\]]+]\[[^\]]+]/", $sqlFailString, $queryFailMatrixKeyIndexOccur);
					//print_r($queryFailMatrixKeyIndexOccur);
					foreach ($queryFailMatrixKeyIndexOccur as $k => $value) {
						foreach ($queryFailMatrixKeyIndexOccur[$k] as $m => $value) {
							$strKeyPos = strpos($queryFailMatrixKeyIndexOccur[$k][$m], ']');
							$keyOcc = substr($queryFailMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
							$queryFailMatrixKeyOccur[] = $keyOcc;
							$strIndexPos = strripos($queryFailMatrixKeyIndexOccur[$k][$m], ']');
							$indexOcc = substr($queryFailMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
							$queryFailMatrixIndexOccur[] = $indexOcc;
							//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
						}
					}
					foreach ($queryFailMatrixKeyOccur as $j => $value) {
						$sqlFailString = str_replace("\$_FILES[".$queryFailMatrixKeyOccur[$j]."][".$queryFailMatrixIndexOccur[$j]."]", $_FILES[$queryFailMatrixKeyOccur[$j]][$queryFailMatrixIndexOccur[$j]], $sqlFailString);
					}

					// sostituzione dei tag $_QUERY con #_QUERY
					// per evitare che vengano sostituiti con la
					// stringa 'Array' nei cicli successivi
					$conditionFailString = str_replace("\$_QUERY[", "#_QUERY[", $conditionFailString);
					$sqlFailString = str_replace("\$_QUERY[", "#_QUERY[", $sqlFailString);
					
					// Replica della QUERY secondo i campi $_QUERY[nnn]
					$queryKeyOccurForValue = array();
					preg_match_all("/#\_QUERY\[[^\]]+]/", $conditionFailString, $queryKeyOccurrences);
					//print_r($queryKeyOccurrences);
					foreach ($queryKeyOccurrences as $k => $value) {
						foreach ($queryKeyOccurrences[$k] as $m => $value) {
							$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
							if (!in_array($occ, $queryKeyOccurForValue)) {
								$queryKeyOccurForValue[] = $occ;
								//echo 'occ:'.$occ.'<br />';
							}
						}
					}
					preg_match_all("/#\_QUERY\[[^\]]+]/", $sqlFailString, $queryKeyOccurrences);
					//print_r($queryKeyOccurrences);
					foreach ($queryKeyOccurrences as $k => $value) {
						foreach ($queryKeyOccurrences[$k] as $m => $value) {
							$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
							if (!in_array($occ, $queryKeyOccurForValue)) {
								$queryKeyOccurForValue[] = $occ;
								//echo 'occ:'.$occ.'<br />';
							}
						}
					}
					//print_r($queryKeyOccurForValue);
					
					if (count($queryKeyOccurForValue) > 0) {
						foreach ($_QUERY[$queryKeyOccurForValue[0]] as $i => $value) {
							$conditionFailStringItem = $conditionFailString;
							$sqlFailStringItem = $sqlFailString;
							/**/if (!isset($appDebuggable) || $appDebuggable) echo 'originalValue::'.$conditionFailStringItem.'<br />';
							/**/if (!isset($appDebuggable) || $appDebuggable) echo 'originalValue::'.$sqlFailStringItem.'<br />';
							foreach ($queryKeyOccurForValue as $j => $value) {
								//echo 'originalValue::'.$conditionFailStringItem.'<br />';
								$conditionFailStringItem = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $conditionFailStringItem);
								//echo ' - substituteValue::'.$conditionFailStringItem.'<br />';
								//echo 'originalValue::'.$sqlFailStringItem.'<br />';
								$sqlFailStringItem = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $sqlFailStringItem);
								//echo ' - substituteValue::'.$sqlFailStringItem.'<br />';
							}
							/**/if (!isset($appDebuggable) || $appDebuggable) echo ' - substituteValue::'.$conditionFailStringItem.'<br />';
							/**/if (!isset($appDebuggable) || $appDebuggable) echo ' - substituteValue::'.$sqlFailStringItem.'<br />';
							if (eval("return ".$conditionFailStringItem.";")) {
								eval('$sqlMulti['.$i.'].="'.$sqlFailStringItem.' ";');
								if ($isNewQuery) $isAddableMulti[$i] = true;
							}
						}
					}
					else {
						if (eval("return ".$conditionFailString.";")) {
							//eval('$sql.="'.$row->sql_string.' ";');
							eval('$sql.="'.$sqlFailString.' ";');
							if ($isNewQuery) $isAddable = true;
						}
					}
					//if (eval("return ".$conditionFailString.";")) {
					//	//eval('$sql.="'.$row->sql_string.' ";');
					//	eval('$sql.="'.$sqlFailString.' ";');
					//	if ($isNewQuery) $isAddable = true;
					//}
					$isNewQuery = false;
				}
				$res->close();
				if ($oldQueryIndexEnd != 0) {
					// end last query
					if ($isAddable) $queryOnFailArray[$oldQueryIndexStart] = $sql;
					if (count($tableColumnNameArray) > 0) {
						foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
							if ($isAddableMulti[$i]) $queryOnFailArrayMulti[$i][$oldQueryIndexStart] = $sqlMulti[$i];
						}
					}
				}
			}
			if (!isset($appDebuggable) || $appDebuggable) echo '<br />';	
			
			// execute fail query
			foreach ($queryOnFailArray as $i => $value) {
				if (!isset($appDebuggable) || $appDebuggable) echo 'Execute SingleSQL: '.$queryOnFailArray[$i].'<br />';
				$res = $APPDB->execute($queryOnFailArray[$i]);
			}
			if (count($tableColumnNameArray) > 0) {
				foreach ($_QUERY[$tableColumnNameArray[0]] as $i => $value) {
					foreach ($queryOnFailArrayMulti[$i] as $j => $sqlvalue) {
						if (!isset($appDebuggable) || $appDebuggable) echo 'Execute MultiSQL: '.$queryOnFailArrayMulti[$i][$j].'<br />';
						$res = $APPDB->execute($queryOnFailArrayMulti[$i][$j]);
					}
				}
			}
			if (!isset($appDebuggable) || $appDebuggable) echo '<br /><br />';
			
			// command on fail
			if ($command_on_fail != '') eval($command_on_fail);

			// next page on fail
			//$next_page = $next_page_on_fail;			
			if ($next_page_on_fail != '') eval('$next_page_on_fail="'.$next_page_on_fail.'";');
			$next_page = $next_page_on_fail;
		}
	}

	
	/*****************/
	/* PAGE/SECTIONS */
	/*****************/
	// Recupera la PAGE dal suo nome se non c'è nessuna ACTION
	if ($actionID == 0) {
		if (isset($_GET['page'])) $next_page = $_GET['page'];
		if (isset($_POST['page'])) $next_page = $_POST['page'];

		/*if (strpos($next_page,'[header]') !== false && strpos(trim($next_page),'[header]') == 0) {
			header('location: '.str_replace('[header]', '', $next_page));
			echo "HEADER";
		}*/
		
		$next_pageSplitted = explode(';', $next_page);
		//if (!isset($appDebuggable) || $appDebuggable) print_r($next_pageSplitted); echo '<br />';
		$next_page = $next_pageSplitted[0];
		for ($i = 1; $i < count($next_pageSplitted); $i++) {
			$next_pageSubParameter = explode(':', $next_pageSplitted[$i]);
			if (count($next_pageSubParameter) == 2) {
				if (isset($_GET['page'])) $_GET[$next_pageSubParameter[0]] = $next_pageSubParameter[1];
				else if (isset($_POST['page'])) $_POST[$next_pageSubParameter[0]] = $next_pageSubParameter[1];
				if (!isset($appDebuggable) || $appDebuggable) echo $next_pageSubParameter[0].'='.$next_pageSubParameter[1].'<br />';
			}
		}		
	}
	/*else if (strpos($next_page,'[header]') !== false && strpos(trim($next_page),'[header]') == 0) {
		header('location: '.str_replace('[header]', '', $next_page));
		echo "HEADER";
	}*/

	$pageID = 0;
	$pageres = $DB->execute("SELECT id, name, html, width, height, align, command, is_template, use_template, id_css, id_javascript FROM gm_page WHERE id_application = ".$appID." AND name='".$next_page."'");
	if($page = $pageres->fetch_object()) {
		$pageID = $page->id;
		$pageName = str_replace("'","&rsquo;",$page->name);
		$pageHtml = $page->html;
		$pageWidth = $page->width;
		$pageHeight = $page->height;
		$pageAlign = $page->align;
		$pageCommand = $page->command;
		$page_is_template = $page->is_template;		
		$page_use_template = $page->use_template;		
		$pageIdCss = $page->id_css;		
		$pageIdJavascript = $page->id_javascript;		
		$pageres->close();
		if ($page_is_template == 0 && $page_use_template != '') { 
			$templateres = $DB->execute("SELECT html, width, height, align, id_css, id_javascript FROM gm_page WHERE id_application = ".$appID." AND name='".$page_use_template."'");
			if($template = $templateres->fetch_object()) {
				if (isset($template->html)) {
					//$pageHtml = str_replace("gm_section","gm_section_template",$template->html).$pageHtml;
					$pageHtml = $template->html.$pageHtml;
				}
				$pageWidth = $template->width;
				$pageHeight = $template->height;
				$pageAlign = $template->align;
				$pageIdCss = $template->id_css;		
				$pageIdJavascript = $template->id_javascript;
				$templatePageCommand = $template->command;
				// Esegue i comandi della template page
				if (!isset($appDebuggable) || $appDebuggable) echo 'templatePageCommand: '.$templatePageCommand.'<br />';
				if ($templatePageCommand != '') eval($templatePageCommand);
			}
		}
		
		// Altezza pagina = 0   ->   L'altezza della pagina è data dalla sezione più in basso
		$pageHeight = 0;
		
		// Esegue i comandi della page
		if (!isset($appDebuggable) || $appDebuggable) echo 'pageCommand: '.$pageCommand.'<br />';
		if ($pageCommand != '') eval($pageCommand);
		
		$css_res = $DB->execute("SELECT css FROM gm_css WHERE id = ".$pageIdCss);
		$css_source = "";
		if ($css_res) {
			if ($css_row = $css_res->fetch_object()) {
				$css_source = str_replace("\$appContextPath", $appContextPath, $css_row->css);
			}
			$css_res->close();
		}
		$javascript_res = $DB->execute("SELECT javascript FROM gm_javascript WHERE id = ".$pageIdJavascript);
		$javascript_source = "";
		if ($javascript_res) {
			if ($javascript_row = $javascript_res->fetch_object()) {
				$javascript_source = $javascript_row->javascript;
			}
			$javascript_res->close();
		}
		if (!isset($appDebuggable) || $appDebuggable) echo "pageIdJavascript:".$pageIdJavascript." javascript_source:".$javascript_source;

		if (!isset($appDebuggable) || $appDebuggable) {
?>
<h3>Page: <i style="background-color:yellow;"><?php echo $pageName ?></i></h3>
<?php
		}
		$pagedoc = new DOMDocument();
		if ($pageHtml != '') $pagedoc->loadHTML($pageHtml);
		$pagenodes = $pagedoc->getElementsByTagName("div");

		// riordinamento delle section nella pagina dall'alto veros il basso e da sinistra verso destra
		$sortedPagenodes = array();
		foreach ($pagenodes as $pagenode) {
			$pagenodeStyle = $pagenode->getAttribute('style');
			if (!isset($appDebuggable) || $appDebuggable) echo "pagenodeStyle=".$pagenodeStyle."<br />";
			$pagenodeStyle = str_replace(" ","",$pagenodeStyle);
			$pagenodeStyleItem = array();
			$pagenodeStyleArray = explode(";", $pagenodeStyle);
			foreach ($pagenodeStyleArray as $pagenodeStyleArrayEl) {
				$pagenodeStyleArrayElArray = explode(":", $pagenodeStyleArrayEl);
				if (count($pagenodeStyleArrayElArray) == 2) {
					$pagenodeStyleItem[$pagenodeStyleArrayElArray[0]] = str_replace("px","",str_replace(";","",$pagenodeStyleArrayElArray[1]));
				}
			}
			$tabindex = $pagenodeStyleItem['top'] * 100 + $pagenodeStyleItem['left'];
			//$pagenode->setAttribute('tabindex', $tabindex);
			while (isset($sortedPagenodes[$tabindex])) $tabindex++;
			$sortedPagenodes[$tabindex] = $pagenode;
		}
		ksort($sortedPagenodes);
		$pagedoc2 = new DOMDocument();
		foreach ($sortedPagenodes as $sortedPagenodeKey => $sortedPagenode) {
			$clonenode = $pagedoc2->importNode($sortedPagenode, true);
			$pagedoc2->appendChild($clonenode);
		}
		$pagedoc = $pagedoc2;
		
		// Array per SECTION BLOCKS
		$sectionBlockTop = array();
		$sectionBlockLeft = array();
		$sectionBlockBottom = array();
		$sectionBlockRight = array();
		
		// Inserimento delle Section nella page 
		$pagenodes = $pagedoc->getElementsByTagName("div");	
		$i_pagenode = 0;
		foreach ($pagenodes as $pagenode) {
			$i_pagenode++;
			$divSectionClass = trim($pagenode->getAttribute('class'));
			if (strpos($divSectionClass, 'gm_section') >= 0) {
				$sectionID = trim($pagenode->getAttribute('title')); // title del div coincide col l'ID della SECTION
				$pagenode->removeAttribute('title');
				//$pagenode->setAttribute('style',str_replace('border:1px dotted black;','',$pagenode->getAttribute('style')));
				$replicationDirection = 'vertical';
				$replicationLinesNumber = 1;
				if ($pagenode->getAttribute('replication-direction') != '') {
					$replicationDirection = trim($pagenode->getAttribute('replication-direction'));
					$pagenode->removeAttribute('replication-direction');
				}
				if ($pagenode->getAttribute('replication-linesnumber') != '') {
					$replicationLinesNumber = trim($pagenode->getAttribute('replication-linesnumber'));
					$pagenode->removeAttribute('replication-linesnumber');
				}
				/*****************/
				/* SECTIONS LOOP */
				/*****************/
				$res = $DB->execute("SELECT name, description, command_pre_init_query FROM gm_section WHERE id=".$sectionID);
				if ($section = $res->fetch_object()) { //die('something wrong.');
					$sectionName = str_replace("'","&rsquo;",$section->name);
					$commandPreInitQuery = $section->command_pre_init_query;
					$res->close();
				}
				$totalSectionHeight = 0;
				if ($sectionID > 0) {
					if (!isset($appDebuggable) || $appDebuggable) {
	?>
	<h3>Section: <i style="background-color:yellow;"><?php echo $sectionName ?></i></h3>
	<?php
					}
					
					// Esegue i comandi di PRE INIT QUERY
					if (!isset($appDebuggable) || $appDebuggable) echo 'commandPreInitQuery: '.$commandPreInitQuery.'<br />';
					if ($commandPreInitQuery != '') eval($commandPreInitQuery);

					
					// Esegue le INIT QUERY
					$initQueryArray = array();
					$sql = "";
					$res = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_section_init_query WHERE id_section=".$sectionID." ORDER BY index_query ASC, index_subquery ASC");
					if($res){
						$oldQueryIndexStart = 0;
						$oldQueryIndexEnd = 0;
						$isNewQuery = true;
						$isAddable = false;
						while ($row = $res->fetch_object()){
							if ($oldQueryIndexEnd == 0) $oldQueryIndexEnd = 1;
							if ($oldQueryIndexEnd != $row->index_query) {
								$oldQueryIndexEnd = $row->index_query;
								// end query
								if ($isAddable) $initQueryArray[$oldQueryIndexStart] = $sql;
							}
							if ($oldQueryIndexStart != $row->index_query) {
								$oldQueryIndexStart = $row->index_query;
								// start new query
								$sql = "";
								$isNewQuery = true;
								$isAddable = false;
								if (!isset($appDebuggable) || $appDebuggable) echo "<br />";
							}
							// da notare il comando nel campo "if" : "RETURN ... ;"
							if ($row->condition_string == null || str_replace('/*block*/', '', str_replace('/*all*/', '', $row->condition_string)) == '') $row->condition_string = 'true';
							if (!isset($appDebuggable) || $appDebuggable) echo 'IF '.$row->condition_string.' THEN SQL.='.$row->sql_string.'<br />';
							if (eval("return ".$row->condition_string.";")) {
								eval('$sql.="'.$row->sql_string.' ";');
								if ($isNewQuery) $isAddable = true;
							}
							$isNewQuery = false;
						}
						$res->close();
						if ($oldQueryIndexEnd != 0) {
							// end last query
							if ($isAddable) $initQueryArray[$oldQueryIndexStart] = $sql;
						}
					}
					if (!isset($appDebuggable) || $appDebuggable) echo '<br />';
					//$initQueryResArray = array();
					//$columnToResArray = array();
					$tableColumnNameArray = array();
					$_QUERY = array();
					foreach ($initQueryArray as $i => $value) {
						if (!isset($appDebuggable) || $appDebuggable) echo $initQueryArray[$i].'<br />';
						$res = $APPDB->execute($initQueryArray[$i]);
						if ($res && $res !== true) {
							//$initQueryResArray[] = $res;
							$tableColumnKey = '';
							if ($row = $res->fetch_array(MYSQLI_ASSOC)) {
								$tableColumnKey = array_keys($row);
								$tableColumnNameArray = array_merge($tableColumnNameArray, $tableColumnKey);
								//$_QUERY = array_merge($_QUERY, $row);
								foreach ($tableColumnKey as $j => $value) {
									$_QUERY[$tableColumnKey[$j]] = array($row[$tableColumnKey[$j]]);
								}
								//$columnToResArrayToMerge = $row;
								//foreach ($columnToResArrayToMerge as $j => $value) $columnToResArrayToMerge[$j] = count($initQueryResArray) - 1;
								//$columnToResArray = array_merge($columnToResArray, $columnToResArrayToMerge);
							}
							while ($row = $res->fetch_array(MYSQLI_ASSOC)) {
								foreach ($tableColumnKey as $j => $value) {
									$_QUERY[$tableColumnKey[$j]][] = $row[$tableColumnKey[$j]];
								}
								//printf ("%s (%s)\n", $row["persona.nome"], $row["persona.cognome"]);
								/*foreach ($row as $key) {
									echo $row[$val];
								}*/
								//print_r ($row);
							}
							$res->close();
						}
					}
					//echo '<br />Res Buffer Array: ';
					//print_r($initQueryResArray);
					//echo '<br />Indice del buffer dei risultati $res delle query in base alla KEY di $_QUERY: ';
					//print_r($columnToResArray);
					if (!isset($appDebuggable) || $appDebuggable) {
						echo '<br />Nomi delle colonne dei risultati delle query: ';
						print_r($tableColumnNameArray);
						echo '<br />Risultati delle query: ';
						print_r($_QUERY);
						echo '<br /><br />';
					}
					
					// Esecuzione dei CHECK per la scelta della VIEW
					if (!isset($appDebuggable) || $appDebuggable) echo 'VIEW:<br />';
					$res = $DB->execute("SELECT id, condition_string, view, is_default, html_layout, width, height, command_pre_layout FROM gm_section_view WHERE id_section=".$sectionID." ORDER BY id ASC");
					$viewId = 0;
					$viewToSelect = 0;
					if (isset($_GET['view'])) $viewToSelect = $_GET['view'];
					else if (isset($_POST['view'])) $viewToSelect = $_POST['view'];
					$viewName = '';
					$sectionWidth = 0;
					$sectionHeight = 0;
					$htmlLayout = '';
					$htmlLayoutToShow = '';
					if($res){
						while ($row = $res->fetch_object()){
							// da notare il comando nel campo "if" : "RETURN ... ;"
							if ($row->condition_string == null || str_replace('/*block*/', '', str_replace('/*all*/', '', $row->condition_string)) == '') $row->condition_string = 'true';
							if (!isset($appDebuggable) || $appDebuggable) echo 'IF '.$row->condition_string.' THEN view='.$row->view.'<br />';
							if (eval("return ".$row->condition_string.";")) {
								if ($viewToSelect == 0 || $row->id == $viewToSelect) {
									$viewToSelect = -1;
									$viewId = $row->id;
									$viewName = $row->view;
									$htmlLayout = $row->html_layout;
									$sectionWidth = $row->width;
									$sectionHeight = $row->height;
									$totalSectionHeight = $sectionHeight;
									$commandPreLayout = $row->command_pre_layout;
									if ($commandPreLayout != '') eval($commandPreLayout);
									if (!isset($appDebuggable) || $appDebuggable) echo 'COMMAND PRE LAYOUT: '.$commandPreLayout.'<br />';
									
									// DOM
									$doc = new DOMDocument();
									$htmlLayout = str_replace('/*&lt;*/', '', str_replace('/*&gt;*/', '', str_replace('/*&lt;block*/', '', str_replace('/*block&gt;*/', '', str_replace('/*block*/', '', str_replace('/*all*/', '', $htmlLayout))))));
									//$htmlLayout = str_replace('/*&lt;block*/', '', $htmlLayout);
									$doc->loadHTML($htmlLayout);
									
									// tag SELECT for QUERY set
									$node = $doc->getElementsByTagName("select");  
									foreach ($node as $node) {
										$nodeOption = $node->getElementsByTagName("option");
										$optionValue = '';
										$optionLabel = '';
										// originariamente c'è una sola option
										foreach ($nodeOption as $nodeOption) {
											// value
											$queryKeyOccurForValue = array();
											$originalValue = trim($nodeOption->getAttribute('value'));
											$templateValueString = $originalValue;
											preg_match_all("/\\$\_QUERY\[[^\]]+]/", $originalValue, $queryKeyOccurrences);
											//print_r($queryKeyOccurrences);
											foreach ($queryKeyOccurrences as $k => $value) {
												foreach ($queryKeyOccurrences[$k] as $m => $value) {
													$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
													$queryKeyOccurForValue[] = $occ;
													//echo 'occ:'.$occ.'-'.strlen($queryKeyOccurrences[$k][$m]).'<br />';
												}
											}
											
											// label
											$queryKeyOccurForLabel = array();
											$originalValue = trim($nodeOption->nodeValue);
											$templateLabelString = $originalValue;
											preg_match_all("/\\$\_QUERY\[[^\]]+]/", $originalValue, $queryKeyOccurrences);
											//print_r($queryKeyOccurrences);
											foreach ($queryKeyOccurrences as $k => $value) {
												foreach ($queryKeyOccurrences[$k] as $m => $value) {
													$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
													$queryKeyOccurForLabel[] = $occ;
													//echo 'occ:'.$occ.'-'.strlen($queryKeyOccurrences[$k][$m]).'<br />';
												}
											}

											// remove the only config OPTION element from SECTION
											$node->removeChild($nodeOption);
										}
										
										// add OPTIONS to SELECT element replaceing $_QUERY[x]
										if (count($queryKeyOccurForValue) > 0) {
											foreach ($_QUERY[$queryKeyOccurForValue[0]] as $i => $value) {
												$valueString = $templateValueString;
												foreach ($queryKeyOccurForValue as $j => $value) {
													//echo 'originalValue::'.$valueString;
													$valueString = str_replace("\$_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $valueString);
													//echo ' - substituteValue::'.$valueString.'<br />';
												}
												$labelString = $templateLabelString;
												foreach ($queryKeyOccurForLabel as $j => $value) {
													//echo 'originalValue::'.$labelString;
													$labelString = str_replace("\$_QUERY[".$queryKeyOccurForLabel[$j]."]", $_QUERY[$queryKeyOccurForLabel[$j]][$i], $labelString);
													//echo ' - substituteValue::'.$labelString.'<br />';
												}
												$newNode = $doc->createElement('option', $labelString);
												$newAttribute = $doc->createAttribute('value');
												$newAttribute->value = $valueString;
												$newNode->appendChild($newAttribute);
												$node->appendChild($newNode);
											}
										}
									} 
									
									// salvataggio parziale del HTML sorgente dopo l'inserimento delle OPTION nelle SELECT
									// ed URL decodeing
									$substituteHtmlLayout = urldecode($doc->saveHTML());

									$substituteHtmlLayout = preg_replace("<!DOCTYPE[^\>]+>","",$substituteHtmlLayout);
									$substituteHtmlLayout = str_replace("<html><body>","",str_replace("</body></html>","",str_replace("<>","",$substituteHtmlLayout)));

									//echo $substituteHtmlLayout;

									// sostituzione dei parametri $_QUERY[nnn][x]
									$queryMatrixKeyIndexOccur = array();
									$queryMatrixKeyOccur = array();
									$queryMatrixIndexOccur = array();
									//preg_match_all("/\\$\_QUERY\[[^\]]+]/", $substituteHtmlLayout, $queryMatrixKeyIndexOccur);
									preg_match_all("/\\$\_QUERY\[[^\]]+]\[[^\]]+]/", $substituteHtmlLayout, $queryMatrixKeyIndexOccur);
									//print_r($queryMatrixKeyIndexOccur);
									foreach ($queryMatrixKeyIndexOccur as $k => $value) {
										foreach ($queryMatrixKeyIndexOccur[$k] as $m => $value) {
											$strKeyPos = strpos($queryMatrixKeyIndexOccur[$k][$m], ']');
											$keyOcc = substr($queryMatrixKeyIndexOccur[$k][$m], 8, $strKeyPos - 8);
											$queryMatrixKeyOccur[] = $keyOcc;
											$strIndexPos = strripos($queryMatrixKeyIndexOccur[$k][$m], ']');
											$indexOcc = substr($queryMatrixKeyIndexOccur[$k][$m], $strKeyPos + 2, $strIndexPos - $strKeyPos - 2);
											$queryMatrixIndexOccur[] = $indexOcc;
											//echo 'keyOcc:'.$keyOcc.' indexOcc:'.$indexOcc.' <br />';
										}
									}
									foreach ($queryMatrixKeyOccur as $j => $value) {
										$substituteHtmlLayout = str_replace("\$_QUERY[".$queryMatrixKeyOccur[$j]."][".$queryMatrixIndexOccur[$j]."]", $_QUERY[$queryMatrixKeyOccur[$j]][$queryMatrixIndexOccur[$j]], $substituteHtmlLayout);
									}
									
									// sostituzione dei tag $_QUERY con #_QUERY
									// per evitare che vengano sostituiti con la
									// stringa 'Array' nei cicli successivi
									$substituteHtmlLayout = str_replace("\$_QUERY[", "#_QUERY[", $substituteHtmlLayout);
									
									// caricamento nuovo DOM a partire da $substituteHtmlLayout
/*									$doc->loadHTML($substituteHtmlLayout);
									
									// Salvataggio del DOM modificato con la sostituzione dei parametri:
									// $_GET['']
									// $_POST['']
									// $_SESSION['']
									$htmlLayoutToShow = urldecode($doc->saveHTML());
									$bodyTagPos = strpos($htmlLayoutToShow, '<body>');
									$htmlLayoutToShow = substr($htmlLayoutToShow, $bodyTagPos + 6);
									$bodyTagPos = strpos($htmlLayoutToShow, '</body>');
									$htmlLayoutToShow = substr($htmlLayoutToShow, 0, $bodyTagPos);*/
									$htmlLayoutToShow = "<div style='position:relative; width:".$sectionWidth."px; height:".$sectionHeight."px;'><div style='position:relative;'>"./*$htmlLayoutToShow*/$substituteHtmlLayout."</div></div>";
									//echo $htmlLayoutToShow;
									
									// Replica della SECTION secondo i campi $_QUERY[nnn]
									$queryKeyOccurForValue = array();
									$originalValue = trim($htmlLayoutToShow);
									$templateValueString = $originalValue;
									preg_match_all("/#\_QUERY\[[^\]]+]/", $originalValue, $queryKeyOccurrences);
									//print_r($queryKeyOccurrences);
									foreach ($queryKeyOccurrences as $k => $value) {
										foreach ($queryKeyOccurrences[$k] as $m => $value) {
											$occ = substr($queryKeyOccurrences[$k][$m], 8, strlen($queryKeyOccurrences[$k][$m]) - 9);
											if (!in_array($occ, $queryKeyOccurForValue)) {
												$queryKeyOccurForValue[] = $occ;
												//echo 'occ:'.$occ.'<br />';
											}
										}
									}
									//$replicationDirection = 'vertical';					
									//$replicationLinesNumber = 2;					
									if (count($queryKeyOccurForValue) > 0) {
										$htmlLayoutToShow = '';
										$totalSectionHeight = 0;
										//print_r($queryKeyOccurForValue); 
										foreach ($_QUERY[$queryKeyOccurForValue[0]] as $i => $value) {
											$valueString = $templateValueString;
											foreach ($queryKeyOccurForValue as $j => $value) {
												//echo 'originalValue::'.$valueString;
												$valueString = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $valueString);
												//echo ' - substituteValue::'.$valueString.'<br />';
											}
											
											if ($replicationDirection == 'horizontal') {
												if ($i % $replicationLinesNumber == 0) $valueString = '<td>'.$valueString;
												if ($i % $replicationLinesNumber == $replicationLinesNumber - 1) $valueString = $valueString.'</td>';
											}
											if ($replicationDirection != 'horizontal' && $replicationLinesNumber != 1) {
												$valueString = '<td>'.$valueString.'</td>';
												if ($i % $replicationLinesNumber == 0) $valueString = '<tr>'.$valueString;
												if ($i % $replicationLinesNumber == $replicationLinesNumber - 1) $valueString = $valueString.'</tr>';
											}
																					
											$htmlLayoutToShow .= $valueString;
											if ($replicationDirection != 'horizontal' && $replicationLinesNumber != 1) {
												if ($i % $replicationLinesNumber == 0) $totalSectionHeight += $sectionHeight;
											}
											else $totalSectionHeight += $sectionHeight;
										}

										if ($replicationDirection == 'horizontal') {
											$missingHeigth = ($replicationLinesNumber - 1 - ((count($_QUERY[$queryKeyOccurForValue[0]]) - 1) % $replicationLinesNumber)) * $sectionHeight;
											if ($missingHeigth > 0) $htmlLayoutToShow = $htmlLayoutToShow.'<div style="position:relative; width:'.$sectionWidth.'px; height:'.$missingHeigth.'px;"></div></td>';
											if (!isset($appDebuggable) || $appDebuggable) echo 'REPLICATION: '.$replicationLinesNumber.' '.$missingHeigth;
											$htmlLayoutToShow = '<table style="border-spacing:0; border-collapse:collapse;"><tr>'.$htmlLayoutToShow.'</tr></table>';
											$totalSectionHeight = $replicationLinesNumber * $sectionHeight;
										}
										if ($replicationDirection != 'horizontal' && $replicationLinesNumber != 1) {
											$htmlLayoutToShow = '<table style="border-spacing:0; border-collapse:collapse;"><tr>'.$htmlLayoutToShow.'</tr></table>';
										}
									
									}

									// caricamento nuovo DOM a partire da $htmlLayoutToShow
									/*if ($htmlLayoutToShow != '')*/ $doc->loadHTML($htmlLayoutToShow);
									//else $doc->loadHTML("<span></span>");

////////////////////////////////////////////
									// sostituzione dei parametri nel DOM:
									// $_GET['']
									// $_POST['']
									// $_SESSION['']
									// con EVAL ...						

									// tag A
									$node = $doc->getElementsByTagName("a");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// label
										$originalValue = trim($node->nodeValue);
										eval('$substituteValue="'.$originalValue.'";');
										$substituteValue = urlencode(str_replace('/*<*/', '', str_replace('/*>*/', '', $substituteValue)));
										$node->nodeValue = $substituteValue;
										// href
										$originalValue = trim($node->getAttribute('href'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('href', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
									} 

									// tag IMG
									$node = $doc->getElementsByTagName("img");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// alt
										$originalValue = trim($node->getAttribute('alt'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('alt', $substituteValue);
										// src
										$originalValue = trim($node->getAttribute('src'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('src', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
									} 

									// tag DIV
									$node = $doc->getElementsByTagName("div");           
									foreach ($node as $node) {
										$classValue = trim($node->getAttribute('class'));
										if ($classValue != '' && strpos($classValue,"gm") !== false) {
											// id
											$originalValue = trim($node->getAttribute('id'));
											eval('$substituteValue="'.$originalValue.'";');
											$node->setAttribute('id', $substituteValue);
											//echo $node->getAttribute('id');
											// label
											$originalValue = trim($node->nodeValue);
											eval('$substituteValue="'.$originalValue.'";');
											$substituteValue = urlencode(str_replace('/*<*/', '', str_replace('/*>*/', '', $substituteValue)));
											$node->nodeValue = $substituteValue;
											// title
											$originalValue = trim($node->getAttribute('title'));
											eval('$substituteValue="'.$originalValue.'";');
											$node->setAttribute('title', $substituteValue);
										}
									} 
									
									// tag INPUT
									$node = $doc->getElementsByTagName("input");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
										// name
										$originalValue = trim($node->getAttribute('name'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('name', $substituteValue);
										// type = TEXT|HIDDEN|CHECKBOX|PASSWORD|FILE
										if ($node->getAttribute('type') != 'checkbox') {
											// value (only for TEXT|HIDDEN)
											$originalValue = trim($node->getAttribute('value'));
											eval('$substituteValue="'.$originalValue.'";');
											$substituteValue = urlencode(str_replace('/*<*/', '', str_replace('/*>*/', '', $substituteValue)));
											$node->setAttribute('value', $substituteValue);
										} else {
											// default/checked (only for CHECKBOX)
											$originalDefault = trim($node->getAttribute('default'));
											if (eval("return ".$originalDefault.";")) {
												$node->setAttribute('checked', 'checked');
											}
											$node->removeAttribute('default');
										}
									} 

									// tag SELECT
									$node = $doc->getElementsByTagName("select");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
										// name
										$originalValue = trim($node->getAttribute('name'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('name', $substituteValue);
										// default/checked (only for CHECKBOX)
										$originalValue = trim($node->getAttribute('default'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('default', $substituteValue);
										// apply SELECTED to defualt OPTION
										$originalDefault = trim($node->getAttribute('default'));
										$nodeOption = $node->getElementsByTagName("option");
										foreach ($nodeOption as $nodeOption) {
											$originalValue = trim($nodeOption->getAttribute('value'));
											//echo 'originalValue:'.$originalValue.' originalDefault:'.$originalDefault;
											if ($originalValue == $originalDefault) {
												//echo 'selected';
												$newAttribute = $doc->createAttribute('selected');
												$newAttribute->value = 'selected';
												$nodeOption->appendChild($newAttribute);
											}
										}
										$node->removeAttribute('default');
									} 
									
									// tag TEXTAREA
									$node = $doc->getElementsByTagName("textarea");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
										// name
										$originalValue = trim($node->getAttribute('name'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('name', $substituteValue);
										// value
										$originalValue = trim($node->nodeValue);
										eval('$substituteValue="'.$originalValue.'";');
										$substituteValue = urlencode(str_replace('/*<*/', '', str_replace('/*>*/', '', $substituteValue)));
										$node->nodeValue = $substituteValue;
									} 
									
									// tag IFRAME
									$node = $doc->getElementsByTagName("iframe");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// alt
										$originalValue = trim($node->getAttribute('alt'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('alt', $substituteValue);
										// src
										$originalValue = trim($node->getAttribute('src'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('src', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
									} 

									// tag BUTTON
									$node = $doc->getElementsByTagName("button");           
									foreach($node as $node) {
										// id
										$originalValue = trim($node->getAttribute('id'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('id', $substituteValue);
										// title
										$originalValue = trim($node->getAttribute('title'));
										eval('$substituteValue="'.$originalValue.'";');
										$node->setAttribute('title', $substituteValue);
										// name
										/**/$originalValue = trim($node->getAttribute('name'));
										/**/eval('$substituteValue="'.$originalValue.'";');
										/**/$node->setAttribute('name', $substituteValue);
										// label
										$originalValue = trim($node->nodeValue);
										eval('$substituteValue="'.$originalValue.'";');
										$substituteValue = urlencode(str_replace('/*<*/', '', str_replace('/*>*/', '', $substituteValue)));
										$node->nodeValue = $substituteValue;
										// value
										/**/$originalValue = trim($node->getAttribute('value'));
										/**/eval('$substituteValue="'.$originalValue.'";');
										/**/$node->setAttribute('value', $substituteValue);
									}
////////////////////////////////////////////									
									
									// displayif
									$node = $doc->getElementsByTagName("*");           
									foreach($node as $node) {
										$originalValue = trim($node->getAttribute('displayif'));
										if ($originalValue == '') $originalValue = 'true';
										//echo "### ".$originalValue." ###<br/>";
										if (!eval("return ".$originalValue.";")) {
											$originalValue = trim($node->getAttribute('style'));
											$substituteValue = $or1iginalValue.' display:none;';
											$node->setAttribute('style', $substituteValue);
										}
										$node->removeAttribute('displayif');
									}
									$htmlLayoutToShow = urldecode($doc->saveHTML());
									
									
									$htmlLayoutToShow = preg_replace("<!DOCTYPE[^\>]+>","",$htmlLayoutToShow);
									$htmlLayoutToShow = str_replace("<html><body>","",str_replace("</body></html>","",str_replace("<>","",$htmlLayoutToShow)));
								}
							}
						}
						$res->close();
					}
/*					if (!isset($appDebuggable) || $appDebuggable) {
						echo "<hr /><h3>View: <i style='background-color:yellow;'>".$viewName."</i></h3>";
						echo "<p><strong>HTML View Layout</strong></p><div style='position:relative; width:".$sectionWidth."px; height:".$sectionHeight."px;'>".$htmlLayout."</div></td>";
						echo "<div>&nbsp;</div><hr />";
						echo "<p><strong>HTML View Layout to Show</strong></p>";
						//echo "</div>"; // chiusura div di DEBUG
						//echo "<form action='engine_debug.php' method='post'><div style='position:relative;'>".$htmlLayoutToShow."</div></form>";
						echo "<div style='position:relative;'>".$htmlLayoutToShow."</div><hr />";
					}*/
				}
				
				$sectionHtml = urlencode($htmlLayoutToShow);
				// if ($sectionHtml == '') $sectionHtml = 'EMPTY'; // for debug
				if ($sectionHtml != '') {
					$frag = $pagedoc->createDocumentFragment(); // create fragment
					$frag->appendXML($sectionHtml); // insert arbitary html into the fragment
					$pagenode->appendChild($frag); // stuff the fragment into the original tree
				
					// Calcolo del Delta
					$pagenodeStyle = $pagenode->getAttribute('style');
					if (!isset($appDebuggable) || $appDebuggable) echo "pagenodeStyle=".$pagenodeStyle."<br />";
					$pagenodeStyle = str_replace(" ","",$pagenodeStyle);
					$pagenodeStyleItem = array();
					$pagenodeStyleArray = explode(";", $pagenodeStyle);
					foreach ($pagenodeStyleArray as $pagenodeStyleArrayEl) {
						$pagenodeStyleArrayElArray = explode(":", $pagenodeStyleArrayEl);
						if (count($pagenodeStyleArrayElArray) == 2) {
							$pagenodeStyleItem[$pagenodeStyleArrayElArray[0]] = str_replace("px","",str_replace(";","",$pagenodeStyleArrayElArray[1]));
						}
					}
					if ($pagenodeStyleItem['overflow'] == '') $pagenodeStyleItem['overflow'] = "visible";
					$deltaHeight = 0;
					if ($pagenodeStyleItem['overflow'] == 'visible' && $totalSectionHeight > $pagenodeStyleItem['height']) {
						$deltaHeight = $totalSectionHeight - $pagenodeStyleItem['height'];
						// applicazione del Delta alla pagina
						if ($pagenodeStyleItem['top'] + $totalSectionHeight > $pageHeight) $pageHeight += $deltaHeight;
						// applicazione del Delta alla section "$pagenode"
						$pagenode->setAttribute("style","width:".$pagenodeStyleItem['width']."px;height:".$totalSectionHeight."px;top:".$pagenodeStyleItem['top']."px;left:".$pagenodeStyleItem['left']."px;overflow:".$pagenodeStyleItem['overflow'].";");
						// sostituisco $totalSectionHeight a $pagenodeStyleItem['height']
/**/					$pagenodeStyleItem['height'] = $totalSectionHeight;
					}
					if (!isset($appDebuggable) || $appDebuggable) echo "totalSectionHeight=".$totalSectionHeight." width=".$pagenodeStyleItem['width']." height=".$pagenodeStyleItem['height']." top=".$pagenodeStyleItem['top']." left=".$pagenodeStyleItem['left']." overflow=".$pagenodeStyleItem['overflow']." <strong>deltaHeight=".$deltaHeight."</strong><br />";
					// sostituisco $totalSectionHeight a $pagenodeStyleItem['height']
//					$pagenodeStyleItem['height'] = $totalSectionHeight;
					
					// Recupero della section-block
					$pagenodeSectionBlock = $pagenode->getAttribute('section-block');
					if ($pagenodeSectionBlock != '') {
						if (array_key_exists($pagenodeSectionBlock, $sectionBlockTop)) {
							if ($sectionBlockTop[$pagenodeSectionBlock] > $pagenodeStyleItem['top']) $sectionBlockTop[$pagenodeSectionBlock] = $pagenodeStyleItem['top'];
							if ($sectionBlockLeft[$pagenodeSectionBlock] > $pagenodeStyleItem['left']) $sectionBlockLeft[$pagenodeSectionBlock] = $pagenodeStyleItem['left'];
							if ($sectionBlockBottom[$pagenodeSectionBlock] < $pagenodeStyleItem['top'] + $pagenodeStyleItem['height']) $sectionBlockBottom[$pagenodeSectionBlock] = $pagenodeStyleItem['top'] + $pagenodeStyleItem['height'];
							if ($sectionBlockRight[$pagenodeSectionBlock] < $pagenodeStyleItem['left'] + $pagenodeStyleItem['width']) $sectionBlockRight[$pagenodeSectionBlock] = $pagenodeStyleItem['left'] + $pagenodeStyleItem['width'];
						}
						else {
							$sectionBlockTop[$pagenodeSectionBlock] = $pagenodeStyleItem['top'];
							$sectionBlockLeft[$pagenodeSectionBlock] = $pagenodeStyleItem['left'];
							$sectionBlockBottom[$pagenodeSectionBlock] = $pagenodeStyleItem['top'] + $pagenodeStyleItem['height'];
							$sectionBlockRight[$pagenodeSectionBlock] = $pagenodeStyleItem['left'] + $pagenodeStyleItem['width'];
						}
					}
					//print_r($sectionBlockTop);
					
					// applicazione del Delta alle section sovrapposte e sottostanti "$pagenode"
					if ($deltaHeight > 0) {
						$i_otherPagenode = 0;
						foreach ($pagenodes as $otherPagenode) {
							$i_otherPagenode++;
							$otherPagenodeSectionBlock = $otherPagenode->getAttribute('section-block');
							if (!isset($appDebuggable) || $appDebuggable) echo "pagenodeSectionBlock=".$pagenodeSectionBlock." otherPagenodeSectionBlock=".$otherPagenodeSectionBlock."<br />";
							if ($i_otherPagenode != $i_pagenode && $otherPagenodeSectionBlock != '' && $otherPagenodeSectionBlock == $pagenodeSectionBlock) {
								// recupero di width, height, top e left di "$otherPagenode" 
								$otherPagenodeStyle = $otherPagenode->getAttribute('style');
								if (!isset($appDebuggable) || $appDebuggable) echo "otherPagenodeStyle=".$otherPagenodeStyle."<br />";
								$otherPagenodeStyle = str_replace(" ","",$otherPagenodeStyle);
								$otherPagenodeStyleItem = array();
								$otherPagenodeStyleArray = explode(";", $otherPagenodeStyle);
								foreach ($otherPagenodeStyleArray as $otherPagenodeStyleArrayEl) {
									$otherPagenodeStyleArrayElArray = explode(":", $otherPagenodeStyleArrayEl);
									if (count($otherPagenodeStyleArrayElArray) == 2) {
										$otherPagenodeStyleItem[$otherPagenodeStyleArrayElArray[0]] = str_replace("px","",str_replace(";","",$otherPagenodeStyleArrayElArray[1]));
									}
								}
								if ($otherPagenodeStyleItem['overflow'] == '') $otherPagenodeStyleItem['overflow'] = "visible";
								if ($otherPagenodeStyleItem['top'] > $pagenodeStyleItem['top']) {
									// solo per le section sottostanti a "$pagenode" calcolo le intersezioni
									$intersect = false;
									$otherPagenodeCorners = array(
										array($otherPagenodeStyleItem['left'], $otherPagenodeStyleItem['top']),
										array($otherPagenodeStyleItem['left'], $otherPagenodeStyleItem['top'] + $otherPagenodeStyleItem['height']),
										array($otherPagenodeStyleItem['left'] + $otherPagenodeStyleItem['width'], $otherPagenodeStyleItem['top'] + $otherPagenodeStyleItem['height']),
										array($otherPagenodeStyleItem['left'] + $otherPagenodeStyleItem['width'], $otherPagenodeStyleItem['top'])
									);
									foreach ($otherPagenodeCorners as $otherPagenodeCorner) {
										if (
											$otherPagenodeCorner[0] > $pagenodeStyleItem['left'] &&
											$otherPagenodeCorner[0] < $pagenodeStyleItem['left'] + $pagenodeStyleItem['width'] &&
											$otherPagenodeCorner[1] > $pagenodeStyleItem['top'] &&
											$otherPagenodeCorner[1] < $pagenodeStyleItem['top'] + $pagenodeStyleItem['height']
										) {
											$intersect = true;
										}
									}
									if (!$intersect) {
										// verifico il caso particolare in cui otherPagenode ha i vertici tutti fuori pagenode pur intersecandolo
										if (!(
											$otherPagenodeStyleItem['top'] >= $pagenodeStyleItem['top'] + $pagenodeStyleItem['height'] ||
											$otherPagenodeStyleItem['left'] + $otherPagenodeStyleItem['width'] <= $pagenodeStyleItem['left'] ||
											$otherPagenodeStyleItem['left'] >= $pagenodeStyleItem['left'] + $pagenodeStyleItem['width']
										)) {
											$intersect = true;
										}
									}
									if ($intersect) {
										$otherPagenode->setAttribute("style","width:".$otherPagenodeStyleItem['width']."px;top:".($otherPagenodeStyleItem['top'] + $deltaHeight)."px;height:".$otherPagenodeStyleItem['height']."px;left:".$otherPagenodeStyleItem['left']."px;overflow:".$otherPagenodeStyleItem['overflow'].";");
									}
								}
							}
						}
					}
				}
				else {
					if (!isset($appDebuggable) || $appDebuggable) echo 'EMPTY SECTION... REMOVED!';
				}
			}
		}
			
		$htmlPageToShow = urldecode($pagedoc->saveHTML());
		// Remove Empty Sections
		$htmlPageToShow = preg_replace('/<div[^\>]+><\/div>/', '', $htmlPageToShow);
		
		// Posizionamento delle SECTION secondo il BLOCK associato
		if (!isset($appDebuggable) || $appDebuggable) {
			echo "<br />";
			echo "pageHeight=".$pageHeight;
			echo '<br />BLOCKS: ';
			print_r($sectionBlockTop);
			print_r($sectionBlockBottom);
			//print_r($sectionBlockLeft);
			//print_r($sectionBlockRight);
			echo "<br />";
		}
		$deltaBlock = array();
		$sectionBlockKey = array_keys($sectionBlockTop);
		for ($i_occ = 0; $i_occ < count($sectionBlockKey); $i_occ++) {
			$i = $sectionBlockKey[$i_occ];
			//$intersectOneAtLeast = false;
			for ($j_occ = $i_occ + 1; $j_occ < count($sectionBlockKey); $j_occ++) {
				$intersect = false;
				$j = $sectionBlockKey[$j_occ];
				if ($sectionBlockTop[$j] > $sectionBlockTop[$i] && $sectionBlockTop[$j] < $sectionBlockBottom[$i] &&
					$sectionBlockLeft[$j] > $sectionBlockLeft[$i] && $sectionBlockLeft[$j] < $sectionBlockRight[$i]) $intersect = true;
				else if ($sectionBlockTop[$j] > $sectionBlockTop[$i] && $sectionBlockTop[$j] < $sectionBlockBottom[$i] &&
					$sectionBlockRight[$j] > $sectionBlockLeft[$i] && $sectionBlockRight[$j] < $sectionBlockRight[$i]) $intersect = true;
				else if ($sectionBlockBottom[$j] > $sectionBlockTop[$i] && $sectionBlockBottom[$j] < $sectionBlockBottom[$i] &&
					$sectionBlockLeft[$j] > $sectionBlockLeft[$i] && $sectionBlockLeft[$j] < $sectionBlockRight[$i]) $intersect = true;
				else if ($sectionBlockBottom[$j] > $sectionBlockTop[$i] && $sectionBlockBottom[$j] < $sectionBlockBottom[$i] &&
					$sectionBlockRight[$j] > $sectionBlockLeft[$i] && $sectionBlockRight[$j] < $sectionBlockRight[$i]) $intersect = true;
				if (!$intersect) {
					// verifico il caso particolare in cui otherPagenode ha i vertici tutti fuori pagenode pur intersecandolo
					if (!(
						$sectionBlockTop[$j] >= $sectionBlockBottom[$i] ||
						$sectionBlockRight[$j] <= $sectionBlockLeft[$i] ||
						$sectionBlockLeft[$j] >= $sectionBlockRight[$i]
					)) {
						$intersect = true;
					}
				}
				if ($intersect) {
					if (!isset($appDebuggable) || $appDebuggable) echo $j."-INTERSECT-".$i." +".($sectionBlockBottom[$i] - $sectionBlockTop[$i]); 
					$deltaBlock[$j] += $sectionBlockBottom[$i] - $sectionBlockTop[$i];
					$sectionBlockTop[$j] += $sectionBlockBottom[$i] - $sectionBlockTop[$i];
					$sectionBlockBottom[$j] += $sectionBlockBottom[$i] - $sectionBlockTop[$i];
					//$intersectOneAtLeast = true;
					if ($sectionBlockBottom[$j] > $pageHeight) $pageHeight = $sectionBlockBottom[$j];
					if (!isset($appDebuggable) || $appDebuggable) {
						print_r($sectionBlockTop);
						echo "<br />";
						echo "pageHeight=".$pageHeight."<br />";
					}
				}
			}
			//if ($intersectOneAtLeast) $pageHeight += $sectionBlockBottom[$i] - $sectionBlockTop[$i];
		}
		if (!isset($appDebuggable) || $appDebuggable) {
			echo 'BLOCKS RELOCATED: ';
			print_r($sectionBlockTop);
			print_r($sectionBlockBottom);
			//print_r($sectionBlockLeft);
			//print_r($sectionBlockRight);
			echo "<br />";
		}
		


		// BLOCKS RELOCATION and pageHeigth setting
		$pagedoc = new DOMDocument();
		if ($htmlPageToShow != '') $pagedoc->loadHTML($htmlPageToShow);
		$pagenodes = $pagedoc->getElementsByTagName("div");
		foreach ($pagenodes as $pagenode) {
			$divSectionBlock = trim($pagenode->getAttribute('section-block'));
			if ($divSectionBlock != '' && isset($deltaBlock[$divSectionBlock])) {
				$pagenode->removeAttribute('section-block');
				$pagenodeStyle = $pagenode->getAttribute('style');
				//if (!isset($appDebuggable) || $appDebuggable) echo "pagenodeStyle=".$pagenodeStyle."<br />";
				$pagenodeStyle = str_replace(" ","",$pagenodeStyle);
				$pagenodeStyleItem = array();
				$pagenodeStyleArray = explode(";", $pagenodeStyle);
				foreach ($pagenodeStyleArray as $pagenodeStyleArrayEl) {
					$pagenodeStyleArrayElArray = explode(":", $pagenodeStyleArrayEl);
					if (count($pagenodeStyleArrayElArray) == 2) {
						$pagenodeStyleItem[$pagenodeStyleArrayElArray[0]] = str_replace("px","",str_replace(";","",$pagenodeStyleArrayElArray[1]));
					}
				}
				if ($pagenodeStyleItem['overflow'] == '') $pagenodeStyleItem['overflow'] = "visible";
				// applicazione del Delta alla section "$pagenode"
				$pagenode->setAttribute("style","width:".$pagenodeStyleItem['width']."px;height:".$pagenodeStyleItem['height']."px;top:".($pagenodeStyleItem['top'] + $deltaBlock[$divSectionBlock])."px;left:".$pagenodeStyleItem['left']."px;overflow:".$pagenodeStyleItem['overflow'].";");
				if (!isset($appDebuggable) || $appDebuggable) {
					echo 'BLOCK '.$divSectionBlock.' RELOCATION: '.$pagenodeStyleItem['top'].' + '.$deltaBlock[$divSectionBlock].' = '.($pagenodeStyleItem['top'] + $deltaBlock[$divSectionBlock]).'<br />';
				}
				if ($pageHeight < $pagenodeStyleItem['top'] + $pagenodeStyleItem['height'] + $deltaBlock[$divSectionBlock]) $pageHeight = $pagenodeStyleItem['top'] + $pagenodeStyleItem['height'] + $deltaBlock[$divSectionBlock];
			}
			else {
				$pagenodeStyle = $pagenode->getAttribute('style');
				//if (!isset($appDebuggable) || $appDebuggable) echo "pagenodeStyle=".$pagenodeStyle."<br />";
				$pagenodeStyle = str_replace(" ","",$pagenodeStyle);
				$pagenodeStyleItem = array();
				$pagenodeStyleArray = explode(";", $pagenodeStyle);
				foreach ($pagenodeStyleArray as $pagenodeStyleArrayEl) {
					$pagenodeStyleArrayElArray = explode(":", $pagenodeStyleArrayEl);
					if (count($pagenodeStyleArrayElArray) == 2) {
						$pagenodeStyleItem[$pagenodeStyleArrayElArray[0]] = str_replace("px","",str_replace(";","",$pagenodeStyleArrayElArray[1]));
					}
				}
				if ($pageHeight < $pagenodeStyleItem['top'] + $pagenodeStyleItem['height']) $pageHeight = $pagenodeStyleItem['top'] + $pagenodeStyleItem['height'];
			}
		}
		$htmlPageToShow = urldecode($pagedoc->saveHTML());
		
		

		if (!isset($appDebuggable) || $appDebuggable) {
			echo "<p><strong>HTML Layout to Show</strong></p>";
			echo "</div>"; // chiusura div di DEBUG<div id="box" style="width:100%; text-align:$pageAlign;">
		}
?>
<script>
	$('#dinamic_css').text('<?php echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$css_source)))))))))))))))))))))))))))) ?>');
</script>
<?php
		echo "<form action='".$formAction."' method='post' enctype='multipart/form-data'><div id='main' style='width:100%; text-align:".$pageAlign.";'><div id='container' style='position:relative; display: inline-block; width:".$pageWidth."px; height:".$pageHeight."px; font-size:16px;'>".$htmlPageToShow."</div></div><input type='hidden' name='previous_page' value='".$next_page."' /></form>";
		//echo "<form action='".$formAction."' method='post' enctype='multipart/form-data'><div id='main' style='width:100%; text-align:".$pageAlign.";'><div id='container' style='position:relative; display: inline-block; width:".$pageWidth."px; font-size:16px;'>".$htmlPageToShow."</div></div><input type='hidden' name='previous_page' value='".$next_page."' /></form>";
	}
	else {
		if (!isset($appDebuggable) || $appDebuggable) {
			echo "</div>"; // chiusura div di DEBUG
			echo "<div style='font-size:16px;'>NO PAGE FOUND (".$next_page.")</div>";
		}
		//echo "<div style='font-size:16px;'>".$next_page.'-'.$next_page_on_success.'-'.$next_page_on_fail.'-'.$_GET['previous_page'].'-'.$_POST['previous_page']."</div>";
	}
?>

<script>
	$('#debugbutton').on('click', switchdebug);

	function switchdebug(e){
		if ($(".debug").css("display") == 'none') {
			$(".debug").css("display","inline");
			$(this).text('DEBUG MODE: on');
			$(this).css('background-color','#aaffaa');
		}
		else {
			$(".debug").css("display","none");
			$(this).text('DEBUG MODE: off');
			$(this).css('background-color','#ffaaaa');
		}
	}
		
	/*$('#dinamic_css').text(...);*/

	<?php echo preg_replace('/[\r\t\n]/','',$javascript_source); ?>	

</script>

</body>
</html>