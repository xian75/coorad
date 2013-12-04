<!DOCTYPE html>
<html>
<head>
  <!--script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script-->
  <link rel="shortcut icon" type="image/x-icon" href="/gm/favicon.ico">
  <link rel="stylesheet" type="text/css" href="/gm/css/style.css" />
</head>
<body>
<h2>PREVIEW</h2>
<?php
	require_once("../init.php");

	// Report simple running errors
	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	// Reporting E_NOTICE can be good too (to report uninitialized
	// variables or catch variable name misspellings ...)
	//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	
	if (!isset($_GET['section']) && !isset($_POST['section'])) {
		echo '<h2>Sorry, no section selected... You need to set a GET or POST "section" parameter</h2>';
	}
	else {
		// Recupera il nome della SECTION
		$sectionID = 0;
		if (isset($_GET['section'])) $sectionID = $_GET['section'];
		else $sectionID = $_POST['section'];
		$res = $DB->execute("SELECT name, description FROM gm_section WHERE id=".$sectionID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$sectionName = str_replace("'","&rsquo;",$section->name);
		$res->close();
?>
<h3>Section: <i style="background-color:yellow;"><?php echo $sectionName ?></i></h3>
<?php
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
					echo "<br />";
				}
				// da notare il comando nel campo "if" : "RETURN ... ;"
				if ($row->condition_string == null || $row->condition_string == '') $row->condition_string = 'true';
				echo 'IF '.$row->condition_string.' THEN SQL.='.$row->sql_string.'<br />';
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
		echo '<br />';
		//$initQueryResArray = array();
		//$columnToResArray = array();
		$tableColumnNameArray = array();
		$_QUERY = array();
		foreach ($initQueryArray as $i => $value) {
			echo $initQueryArray[$i].'<br />';
			$res = $DB->execute($initQueryArray[$i]);
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
		echo '<br />Nomi delle colonne dei risultati delle query: ';
		print_r($tableColumnNameArray);
		echo '<br />Risultati delle query: ';
		print_r($_QUERY);
		echo '<br /><br />';
		
		// Esecuzione dei CHECK per la scelta della VIEW
		echo 'VIEW:<br />';
		$res = $DB->execute("SELECT id, condition_string, view, is_default, html_layout, width, height FROM gm_section_view WHERE id_section=".$sectionID." ORDER BY id ASC");
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
				if ($row->condition_string == null || $row->condition_string == '') $row->condition_string = 'true';
				echo 'IF '.$row->condition_string.' THEN view='.$row->view.'<br />';
// solo per la preview c'e' TRUE !!!
				if (true || eval("return ".$row->condition_string.";")) {
					if ($viewToSelect == 0 || $row->id == $viewToSelect) { 
						$viewId = $row->id;
						$viewName = $row->view;
						$htmlLayout = $row->html_layout;
						$sectionWidth = $row->width;
						$sectionHeight = $row->height;
						
						// DOM
						$doc = new DOMDocument();
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
						$doc->loadHTML($substituteHtmlLayout);

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
							$node->nodeValue = $substituteValue;
							// href
							$originalValue = trim($node->getAttribute('href'));
							eval('$substituteValue="'.$originalValue.'";');
							$node->setAttribute('href', $substituteValue);
							// title
							$originalValue = trim($node->getAttribute('title'));
							/*if (strpos($originalValue,'$_') === 0) {
								$substituteValue = eval("if (isset(".$originalValue.")) return ".$originalValue."; else return '';");
								$node->setAttribute('title', $substituteValue);
							}*/
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
							// id
							$originalValue = trim($node->getAttribute('id'));
							eval('$substituteValue="'.$originalValue.'";');
							$node->setAttribute('id', $substituteValue);
							//echo $node->getAttribute('id');
							// label
							$originalValue = trim($node->nodeValue);
							/*if (strpos($originalValue,'$_') === 0) {
								$substituteValue = eval("if (isset(".$originalValue.")) return ".$originalValue."; else return '';");
								$node->item($c)->nodeValue = $substituteValue;
							}*/
							eval('$substituteValue="'.$originalValue.'";');
							$node->nodeValue = $substituteValue;
							// title
							$originalValue = trim($node->getAttribute('title'));
							eval('$substituteValue="'.$originalValue.'";');
							$node->setAttribute('title', $substituteValue);
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
							// type = TEXT|HIDDEN|CHECKBOX
							if ($node->getAttribute('type') != 'checkbox') {
								// value (only for TEXT|HIDDEN)
								$originalValue = trim($node->getAttribute('value'));
								eval('$substituteValue="'.$originalValue.'";');
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
							$node->nodeValue = $substituteValue;
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
							/*$originalValue = trim($node->getAttribute('name'));
							eval('$substituteValue="'.$originalValue.'";');
							$node->setAttribute('name', $substituteValue);*/
							// label
							$originalValue = trim($node->nodeValue);
							eval('$substituteValue="'.$originalValue.'";');
							$node->nodeValue = $substituteValue;
							// value
							/*$originalValue = trim($node->getAttribute('value'));
							eval('$substituteValue="'.$originalValue.'";');
							$node->setAttribute('value', $substituteValue);*/
						} 
						
						// Salvataggio del DOM modificato con la sostituzione dei parametri:
						// $_GET['']
						// $_POST['']
						// $_SESSION['']
						$htmlLayoutToShow = urldecode($doc->saveHTML());
						$bodyTagPos = strpos($htmlLayoutToShow, '<body>');
						$htmlLayoutToShow = substr($htmlLayoutToShow, $bodyTagPos + 6);
						$bodyTagPos = strpos($htmlLayoutToShow, '</body>');
						$htmlLayoutToShow = substr($htmlLayoutToShow, 0, $bodyTagPos);
						$htmlLayoutToShow = "<div style='position:relative; width:".$sectionWidth."px; height:".$sectionHeight."px;'><div style='position:relative;'>".$htmlLayoutToShow."</div></div>";
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
						if (count($queryKeyOccurForValue) > 0) {
							$htmlLayoutToShow = '';
							foreach ($_QUERY[$queryKeyOccurForValue[0]] as $i => $value) {
								$valueString = $templateValueString;
								foreach ($queryKeyOccurForValue as $j => $value) {
									//echo 'originalValue::'.$valueString;
									$valueString = str_replace("#_QUERY[".$queryKeyOccurForValue[$j]."]", $_QUERY[$queryKeyOccurForValue[$j]][$i], $valueString);
									//echo ' - substituteValue::'.$valueString.'<br />';
								}
								$htmlLayoutToShow .= $valueString;
							}
						}
						
						
					}
				}
			}
			$res->close();
		}
		echo "<h3>View: <i style='background-color:yellow;'>".$viewName."</i></h3>";
		echo "<p><strong>HTML View Layout</strong></p><div style='position:relative; width:".$sectionWidth."px; height:".$sectionHeight."px;'>".$htmlLayout."</div>";
		echo "<div></div>";
		echo "<p><strong>HTML View Layout to Show</strong></p><div style='position:relative;'>".$htmlLayoutToShow."</div>";

		// Scansione del html del LAYOUT per recuperare tutti i parametri $_QUERY['...']
		
		// In base all'elemento html a cui il parametro $_QUERY['...'] è agganciato definire la lista di quelli che richiedono la scansione dei $res delle query

		// Sostituire al valore secco di $_QUERY['...'] l'array dei relativi risultati da DB
		
		// Chiudere i $res delle INIT QUERY
		/*foreach ($initQueryResArray as $resObj) {
			$resObj->close();
		}*/
?>






<?php
	}
?>
</body>
</html>