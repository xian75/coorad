<?php
	header('Content-disposition: attachment; filename=generated_page.xml');
	header("Content-type: text/plain"); 
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

		//print_r($_SERVER);
		$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')).$s;
		$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'.$_SERVER['SERVER_PORT']);
		$xmlExportScript = $protocol.'://'.$_SERVER['SERVER_NAME'].$port.'/gm/page/page_xml_export.php?id='.$pageID;
		//echo $xmlExportScript;
		$xmlpage = file_get_contents($xmlExportScript);
		//echo 'Prima('.$pageID.')<textarea>'.$xmlpage.'</textarea>';

		$pagemodel = '';
		$pagename = '';
		$res = $DB->execute("SELECT name, description FROM gm_page WHERE id=".$pageID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				//echo $row->id."<br />";
				$pagemodel = $row->name.' ('.$row->description.')';
				$pagename = $row->name;
			}
			$res->close();			
		}
		$pagemodelPrefix = '';
		/*if (preg_match('/__ENTITY__/', $pagemodel)) $pagemodelPrefix = '';
		else*/ if (preg_match('/__REL_ENTITY__/', $pagemodel)) $pagemodelPrefix = 'REL_';
		else if (preg_match('/__REL2_ENTITY__/', $pagemodel)) $pagemodelPrefix = 'REL2_';
		
		if ($pagemodelPrefix == '') {
			$table = '';
			$entity = '';
			$column = array();
			$datamodel = array();
			$columnByDatamodel = array();
			$attribute = array();
			$attributeFrom = array();
			$attributeTo = array();
			$res = $DB->execute("SELECT table_name, entity FROM gm_database_table WHERE id = ".$tableID);
			if ($res && $row = $res->fetch_object()) {
				$table = $row->table_name;
				$entity = $row->entity;
				$res->close();			
			}
			$res = $DB->execute("SELECT column_name, data_model, attribute, attribute_from, attribute_to FROM gm_database_column WHERE id_table = ".$tableID);
			if ($res) {
				while ($row = $res->fetch_object()) {
					//print_r($row);
					$column[] = $row->column_name;
					if (!in_array($row->data_model, $datamodel)) $datamodel[] = $row->data_model;
					$attribute[$row->column_name] = $row->attribute;
					$attributeFrom[$row->column_name] = $row->attribute_from;
					$attributeTo[$row->column_name] = $row->attribute_to;
					if (!isset($columnByDatamodel[$row->data_model])) $columnByDatamodel[$row->data_model] = array();
					$columnByDatamodel[$row->data_model][] = $row->column_name;
				}
				$res->close();			
			}
			//print_r($datamodel);
			//print_r($columnByDatamodel);
			$pagename = str_replace('__ENTITY__', $entity, $pagename);
			$xmlpage = str_replace('__ENTITY__', $entity, $xmlpage);
			$xmlpage = str_replace('__TABLE__', $table, $xmlpage);
			if (isset($columnByDatamodel['PRIMARY_KEY']) && count($columnByDatamodel['PRIMARY_KEY']) != 0) $xmlpage = str_replace('__PRIMARY_KEY__', $columnByDatamodel['PRIMARY_KEY'][0], $xmlpage); // CONSTRAINT: Only 1 column as Primary Key !!!
		}
		else if ($pagemodelPrefix == 'REL_') {
			// REL_ENTITY 
			$appID = 0;
			$rel_table = '';
			$rel_entity = '';
			$rel_column = array();
			$rel_datamodel = array();
			$rel_columnByDatamodel = array();
			$rel_attribute = array();
			$rel_attributeFrom = array();
			$rel_attributeTo = array();
			$res = $DB->execute("SELECT table_name, entity, id_application FROM gm_database_table WHERE id = ".$tableID);
			if ($res && $row = $res->fetch_object()) {
				$rel_table = $row->table_name;
				$rel_entity = $row->entity;
				$appID = $row->id_application;
				$res->close();			
			}
						
			$res = $DB->execute("SELECT column_name, data_model, attribute, attribute_from, attribute_to FROM gm_database_column WHERE id_table = ".$tableID);
			if ($res) {
				while ($row = $res->fetch_object()) {
					//print_r($row);
					$rel_column[] = $row->column_name;
					if (!in_array($row->data_model, $rel_datamodel)) $rel_datamodel[] = $row->data_model;
					$rel_attribute[$row->column_name] = $row->attribute;
					$rel_attributeFrom[$row->column_name] = $row->attribute_from;
					$rel_attributeTo[$row->column_name] = $row->attribute_to;
					if (!isset($rel_columnByDatamodel[$row->data_model])) $rel_columnByDatamodel[$row->data_model] = array();
					$rel_columnByDatamodel[$row->data_model][] = $row->column_name;
				}
				$res->close();			
			}
			//print_r($rel_columnByDatamodel);
			//print_r($rel_attribute);

			// Recupero la connessione al DB dell'applicazione per poter fare query sull'information_schema del relativo DB
			$appDbHost = '';
			$appDbPort = '';
			$appDbName = '';
			$appDbUsername = '';
			$appDbPassword = '';
			$res = $DB->execute("SELECT name, context_path, db_prefix, db_host, db_port, db_name, db_username, db_password FROM gm_application WHERE id = ".$appID);
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
			
			// ENTITY

			$foreignKeyColumn = array();
			$foreignKeyRefTable = array();
			$foreignKeyRefColumn = array();
			$res = $APPDB->execute("SELECT k.COLUMN_NAME column_name, k.REFERENCED_TABLE_NAME ref_table_name, k.REFERENCED_COLUMN_NAME ref_column_name FROM information_schema.TABLE_CONSTRAINTS i LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND i.TABLE_NAME = '".$rel_table."'");
			if ($res) {
				while ($row = $res->fetch_object()) {
					if (in_array($row->column_name, $rel_columnByDatamodel['FOREIGN_KEY'])) {
						$foreignKeyColumn[] = $row->column_name;
						$foreignKeyRefTable[$row->column_name] = $row->ref_table_name;
						$foreignKeyRefColumn[$row->column_name] = $row->ref_column_name;
					}
				}
				$res->close();
			}
			//print_r($foreignKeyColumn);
			//print_r($foreignKeyRefTable);
			//print_r($foreignKeyRefColumn);
			
			$table = array();
			$entity = array();
			$column = array();
			$datamodel = array();
			$columnByDatamodel = array();
			$attribute = array();
			$attributeFrom = array();
			$attributeTo = array();
			for ($i = 0; $i < count($foreignKeyColumn); $i++) {
				$tableID = 0;
				$res = $DB->execute("SELECT id, table_name, entity FROM gm_database_table WHERE id_application = ".$appID." AND table_name = '".$foreignKeyRefTable[$foreignKeyColumn[$i]]."'");
				//echo "SELECT id, table_name, entity FROM gm_database_table WHERE id_application = ".$appID." AND table_name = '".$foreignKeyRefTable[$foreignKeyColumn[$i]]."'";
				if ($res && $row = $res->fetch_object()) {
					$tableID = $row->id;
					$table[] = $row->table_name;
					$entity[] = $row->entity;
					$res->close();			
				}
				$res = $DB->execute("SELECT column_name, data_model, attribute, attribute_from, attribute_to FROM gm_database_column WHERE id_table = ".$tableID);
				if ($res) {
					while ($row = $res->fetch_object()) {
						//print_r($row);
						$column[] = '.('.$i.').'.$row->column_name;
						if (!in_array($row->data_model.'_'.$i, $datamodel)) $datamodel[] = $row->data_model.'_'.$i;
						$attribute['.('.$i.').'.$row->column_name] = $row->attribute;
						$attributeFrom['.('.$i.').'.$row->column_name] = $row->attribute_from;
						$attributeTo['.('.$i.').'.$row->column_name] = $row->attribute_to;
						if (!isset($columnByDatamodel[$row->data_model.'_'.$i])) $columnByDatamodel[$row->data_model.'_'.$i] = array();
						$columnByDatamodel[$row->data_model.'_'.$i][] = '.('.$i.').'.$row->column_name;						
					}
					$res->close();			
				}
			}
			//print_r($datamodel);
			//print_r($columnByDatamodel);
			
			
			// REF_ENTITY

			$ref_foreignKeyColumn = array();
			$ref_foreignKeyRefTable = array();
			$ref_foreignKeyRefColumn = array();
			//$res = $APPDB->execute("SELECT DISTINCT k.REFERENCED_COLUMN_NAME column_name, i.TABLE_NAME ref_table_name, k.COLUMN_NAME ref_column_name FROM information_schema.TABLE_CONSTRAINTS i, information_schema.KEY_COLUMN_USAGE k, gm.gm_database_column c, gm.gm_database_table t WHERE i.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND c.data_model = 'FOREIGN_KEY' AND c.id_table = t.id AND t.table_name = i.TABLE_NAME AND c.column_name = k.COLUMN_NAME AND k.REFERENCED_TABLE_NAME = '".$rel_table."'");
			$res = $APPDB->execute("SELECT DISTINCT k.REFERENCED_COLUMN_NAME column_name, i.TABLE_NAME ref_table_name, k.COLUMN_NAME ref_column_name FROM information_schema.TABLE_CONSTRAINTS i, information_schema.KEY_COLUMN_USAGE k WHERE i.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND k.REFERENCED_TABLE_NAME = '".$rel_table."'");
			$i = 0;
			if ($res) {
				while ($row = $res->fetch_object()) {
					$ref_foreignKeyColumn[] = $i.$row->column_name;
					$ref_foreignKeyRefTable[$i.$row->column_name] = $row->ref_table_name;
					$ref_foreignKeyRefColumn[$i.$row->column_name] = $row->ref_column_name;
					$i++;
				}
			}
			//print_r($ref_foreignKeyColumn);
			//print_r($ref_foreignKeyRefTable);
			//print_r($ref_foreignKeyRefColumn);

			$ref_table = array();
			$ref_entity = array();
			$ref_column = array();
			$ref_datamodel = array();
			$ref_columnByDatamodel = array();
			$ref_attribute = array();
			$ref_attributeFrom = array();
			$ref_attributeTo = array();
			for ($i = 0; $i < count($ref_foreignKeyColumn); $i++) {
				$tableID = 0;
				$res = $DB->execute("SELECT id, table_name, entity FROM gm_database_table WHERE id_application = ".$appID." AND table_name = '".$ref_foreignKeyRefTable[$ref_foreignKeyColumn[$i]]."'");
				//echo "SELECT id, table_name, entity FROM gm_database_table WHERE id_application = ".$appID." AND table_name = '".$ref_foreignKeyRefTable[$ref_foreignKeyColumn[$i]]."'";
				if ($res && $row = $res->fetch_object()) {
					$tableID = $row->id;
					$ref_table[] = $row->table_name;
					$ref_entity[] = $row->entity;
					$res->close();			
				}
				$res = $DB->execute("SELECT column_name, data_model, attribute, attribute_from, attribute_to FROM gm_database_column WHERE id_table = ".$tableID);
				if ($res) {
					while ($row = $res->fetch_object()) {
						//print_r($row);
						if ($row->data_model == 'FOREIGN_KEY' && $row->column_name != $ref_foreignKeyRefColumn[$ref_foreignKeyColumn[$i]]) {
							//continue;
							$row->data_model = 'OTHERFOREIGNKEY';
						}
						$ref_column[] = '.('.$i.').'.$row->column_name;
						if (!in_array($row->data_model.'_'.$i, $ref_datamodel)) $ref_datamodel[] = $row->data_model.'_'.$i;
						$ref_attribute['.('.$i.').'.$row->column_name] = $row->attribute;
						$ref_attributeFrom['.('.$i.').'.$row->column_name] = $row->attribute_from;
						$ref_attributeTo['.('.$i.').'.$row->column_name] = $row->attribute_to;
						if (!isset($ref_columnByDatamodel[$row->data_model.'_'.$i])) $ref_columnByDatamodel[$row->data_model.'_'.$i] = array();
						$ref_columnByDatamodel[$row->data_model.'_'.$i][] = '.('.$i.').'.$row->column_name;
						/*if ($row->data_model == 'OTHERFOREIGNKEY') {
							$resbis = $DB->execute("SELECT k.COLUMN_NAME column_name, k.REFERENCED_TABLE_NAME ref_table_name, k.REFERENCED_COLUMN_NAME ref_column_name FROM information_schema.TABLE_CONSTRAINTS i LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND i.TABLE_NAME = '".$ref_foreignKeyRefTable[$ref_foreignKeyColumn[$i]]."' AND k.COLUMN_NAME = '".$row->column_name."'");
							if ($resbis) {
								while ($rowbis = $resbis->fetch_object()) {
									// OTHER TABLE
									$ref_column[] = '.('.$i.').'.$rowbis->ref_table_name;
									if (!in_array('OTHERFOREIGNTABLE'.'_'.$i, $ref_datamodel)) $ref_datamodel[] = 'OTHERFOREIGNTABLE'.'_'.$i;
									$ref_attribute['.('.$i.').'.$rowbis->ref_table_name] = '';
									$ref_attributeFrom['.('.$i.').'.$rowbis->ref_table_name] = '';
									$ref_attributeTo['.('.$i.').'.$rowbis->ref_table_name] = '';
									if (!isset($ref_columnByDatamodel['OTHERFOREIGNTABLE'.'_'.$i])) $ref_columnByDatamodel['OTHERFOREIGNTABLE'.'_'.$i] = array();
									$ref_columnByDatamodel['OTHERFOREIGNTABLE'.'_'.$i][] = '.('.$i.').'.$rowbis->ref_table_name;
									// OTHER COLUMN
									$ref_column[] = '.('.$i.').'.$rowbis->ref_column_name;
									if (!in_array('OTHERFOREIGNCOLUMN'.'_'.$i, $ref_datamodel)) $ref_datamodel[] = 'OTHERFOREIGNCOLUMN'.'_'.$i;
									$ref_attribute['.('.$i.').'.$rowbis->ref_column_name] = '';
									$ref_attributeFrom['.('.$i.').'.$rowbis->ref_column_name] = '';
									$ref_attributeTo['.('.$i.').'.$rowbis->ref_column_name] = '';
									if (!isset($ref_columnByDatamodel['OTHERFOREIGNCOLUMN'.'_'.$i])) $ref_columnByDatamodel['OTHERFOREIGNCOLUMN'.'_'.$i] = array();
									$ref_columnByDatamodel['OTHERFOREIGNCOLUMN'.'_'.$i][] = '.('.$i.').'.$rowbis->ref_column_name;
								}
								$resbis->close();
							}
						}*/
					}
					$res->close();			
				}
			}
			//print_r($ref_datamodel);
			//print_r($ref_columnByDatamodel);
		
			
			//print_r($rel_datamodel);
			//print_r($rel_columnByDatamodel);
			$pagename = str_replace('__REL_ENTITY__', $rel_entity, $pagename);			
			$xmlpage = str_replace('__REL_ENTITY__', $rel_entity, $xmlpage);
			$xmlpage = str_replace('__REL_TABLE__', $rel_table, $xmlpage);
			if (isset($rel_columnByDatamodel['PRIMARY_KEY']) && count($rel_columnByDatamodel['PRIMARY_KEY']) != 0) $xmlpage = str_replace('__REL_PRIMARY_KEY__', $rel_columnByDatamodel['PRIMARY_KEY'][0], $xmlpage); // CONSTRAINT: Only 1 column as Primary Key !!!

			//print_r($datamodel);
			//print_r($columnByDatamodel);

/*			$pagename = str_replace('__ENTITY__', $entity, $pagename);
			$xmlpage = str_replace('__ENTITY__', $entity, $xmlpage);
			$xmlpage = str_replace('__TABLE__', $table, $xmlpage);
			if (isset($columnByDatamodel['PRIMARY_KEY']) && count($columnByDatamodel['PRIMARY_KEY']) != 0) $xmlpage = str_replace('__PRIMARY_KEY__', $columnByDatamodel['PRIMARY_KEY'][0], $xmlpage); // CONSTRAINT: Only 1 column as Primary Key !!!
*/
			//$pagename = str_replace('__ENTITY__', '__ENTITY_nnn__', $pagename);
			/*$xmlpage = str_replace('__ENTITY__', '__ENTITY_nnn__', $xmlpage);
			$xmlpage = str_replace('__TABLE__', '__TABLE_nnn__', $xmlpage);
			if (isset($columnByDatamodel['PRIMARY_KEY']) && count($columnByDatamodel['PRIMARY_KEY']) != 0) $xmlpage = str_replace('__PRIMARY_KEY__', '__PRIMARY_KEY_nnn_', $xmlpage); // CONSTRAINT: Only 1 column as Primary Key !!!*/
/**/

		
		}
		header('Content-disposition: attachment; filename=generated_page_'.$pagename.'.xml');

		if ($pagemodelPrefix == 'REL_') {
			
			/***************************************************************************************************************************/
			/* Replica delle Section (e dei relativi DIV in PAGE_HTML) e delle Action nel cui nome compare __ENTITY__ o __REF_ENTITY__ */
			/***************************************************************************************************************************/
			$repeatingXmlTag = array(	'section',
										'action'
									);
			for ($i = 0; $i < count($repeatingXmlTag); $i++) {
				//echo $repeatingXmlTag[$i].'             ';
				$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
				$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
				while ($lt >= 0 && $gt > 3 + strlen($repeatingXmlTag[$i])) {
					//echo $lt.' '.$gt.' '.$endXmlTags.'   ';
					$substring = substr($xmlpage, $lt, $gt - $lt);
					// get section/action name
					$name = '';
					$lt_name = strpos($substring, '<'.$repeatingXmlTag[$i].'_name>') + 7 + strlen($repeatingXmlTag[$i]);
					$gt_name = strpos($substring, '</'.$repeatingXmlTag[$i].'_name>');
					if ($lt_name !== false && $gt_name !== false) {
						$name = substr($substring, $lt_name, $gt_name - $lt_name);
					}
					// add SECTIONACTIONREPLACED
					$substring = str_replace('<'.$repeatingXmlTag[$i].'>', '<'.$repeatingXmlTag[$i].'SECTIONACTIONREPLACED>', str_replace('</'.$repeatingXmlTag[$i].'>', '</'.$repeatingXmlTag[$i].'SECTIONACTIONREPLACED>', $substring));

					$substitutestring = '';
					if (!preg_match('/__ENTITY__/', $name)) {



						if (preg_match('/__REF_ENTITY__/', $name)) {
							$substitutestring = generateRepeatingBlockCodeReferredEntities($substring, $ref_table, $ref_entity, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, 0, 0, $repeatingXmlTag[$i]);
						}
						else {
							$substitutestring = $substring;
						}
						//$substitutestring = $substring;



					}
					else {
						$substitutestring = generateRepeatingBlockCodeRelatedEntities($substring, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, 0, 0, $repeatingXmlTag[$i]);
					}
					$xmlpage = substr($xmlpage, 0, $lt).$substitutestring.substr($xmlpage, $gt);
					$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
					$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
				}
				//echo $xmlpage;
			}
			$xmlpage = str_replace('SECTIONACTIONREPLACED', '', $xmlpage);
			//echo $xmlpage;

			
			/********************************************************************************************/
			/* Replica delle query e relative subquery se nella prima subquery compare il tag / *all* / */
			/********************************************************************************************/
			$repeatingXmlTag = array(	'section_initquery',
										'action_checkquery',
										/*'action_checkfield',*/
										'action_queryonsuccess',
										'action_queryonfail'
									);
			for ($i = 0; $i < count($repeatingXmlTag); $i++) {
				//echo $repeatingXmlTag[$i].'             ';
				$isAll_toReplicateForEntities = false;
				$indexquery = 0;
				$indexsubquery = 0;
				$last_indexquery = 0;
				$indexqueryToInsert = array();
				$queryString = '';
				$allQueryReplaced = array();
				$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
				$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
				$endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>');
				if ($lt > $endXmlTags) {
					while ($lt > $endXmlTags) $endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>', $endXmlTags + 1);
					//echo ' NEW ('.$repeatingXmlTag[$i].'):'.$endXmlTags.'         ';
				}
				while ($lt >= 0 && $gt > 3 + strlen($repeatingXmlTag[$i])) {
					//echo $lt.' '.$gt.' '.$endXmlTags.'   ';
					$substring = substr($xmlpage, $lt, $gt - $lt);
					// get index query
					$lt_indexquery = strpos($substring, '<'.$repeatingXmlTag[$i].'_indexquery>') + 13 + strlen($repeatingXmlTag[$i]);
					$gt_indexquery = strpos($substring, '</'.$repeatingXmlTag[$i].'_indexquery>');
					if (($lt_indexquery !== false && $gt_indexquery !== false) || $repeatingXmlTag[$i] == 'action_checkfield') {
						$indexquery = substr($substring, $lt_indexquery, $gt_indexquery - $lt_indexquery);
						if (($indexquery > $last_indexquery) || $repeatingXmlTag[$i] == 'action_checkfield') {
							$last_indexquery = $indexquery;
							$indexsubquery = 1;
							if ($queryString != '') {
								if (!$isAll_toReplicateForEntities) {
									$queryString = preg_replace('/<'.$repeatingXmlTag[$i].'_indexquery>[0-9]+<\/'.$repeatingXmlTag[$i].'_indexquery>/', '<'.$repeatingXmlTag[$i].'_indexquery>'.$indexqueryToInsert[count($allQueryReplaced)].'</'.$repeatingXmlTag[$i].'_indexquery>', $queryString);
								}
								else {
									$queryString = generateRepeatingBlockCodeRelatedEntities($queryString, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $indexqueryToInsert[count($allQueryReplaced)], 0, $repeatingXmlTag[$i]);
								}
								$allQueryReplaced[] = str_replace('/*all*/', '', $queryString);
							}
							$queryString = '';
							$isAll_toReplicateForEntities = false;
							if (strpos($substring, '/*all*/') !== false) {
								$isAll_toReplicateForEntities = true;								
							}
						}
						//echo $repeatingXmlTag[$i].' '.$indexquery.'   ';
					}
					// add ALLREPLACED
					$substring = str_replace('<'.$repeatingXmlTag[$i].'>', '<'.$repeatingXmlTag[$i].'ALLREPLACED>', str_replace('</'.$repeatingXmlTag[$i].'>', '</'.$repeatingXmlTag[$i].'ALLREPLACED>', $substring));
				
					$queryString .= $substring;
					
					if ($indexsubquery == 1 || $repeatingXmlTag[$i] == 'action_checkfield') {
						//echo 'ALLQUERYREPLACED_'.count($allQueryReplaced).'('.$repeatingXmlTag[$i].') ';
						$xmlpage = substr($xmlpage, 0, $lt).'ALLQUERYREPLACED_'.count($allQueryReplaced).substr($xmlpage, $gt);
						$endXmlTags += strlen('ALLQUERYREPLACED_'.count($allQueryReplaced)) - ($gt - $lt);
						if ($indexquery == 1 || $repeatingXmlTag[$i] == 'action_checkfield') $indexqueryToInsert[count($allQueryReplaced)] = 1;
						if (!$isAll_toReplicateForEntities) $indexqueryToInsert[count($allQueryReplaced) + 1] = $indexqueryToInsert[count($allQueryReplaced)] + 1;
						else {
							if (array_key_exists('FOREIGN_KEY', $rel_columnByDatamodel)) {
								$indexqueryToInsert[count($allQueryReplaced) + 1] = $indexqueryToInsert[count($allQueryReplaced)] + count($rel_columnByDatamodel['FOREIGN_KEY']);
							}
							else {
								$indexqueryToInsert[count($allQueryReplaced) + 1] = $indexqueryToInsert[count($allQueryReplaced)];
							}
						}
					}
					else {
						$xmlpage = substr($xmlpage, 0, $lt).substr($xmlpage, $gt);
						$endXmlTags = $endXmlTags - ($gt - $lt);
					}
					$indexsubquery++;
					$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
					$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
					if ($lt > $endXmlTags) {
						$last_indexquery = 0;
						while ($lt > $endXmlTags) $endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>', $endXmlTags + 1);
						//echo ' NEW ('.$repeatingXmlTag[$i].'):'.$endXmlTags.'         ';
					}
				}
				
				if ($queryString != '') {
					if (!$isAll_toReplicateForEntities) {
						$queryString = preg_replace('/<'.$repeatingXmlTag[$i].'_indexquery>[0-9]+<\/'.$repeatingXmlTag[$i].'_indexquery>/', '<'.$repeatingXmlTag[$i].'_indexquery>'.$indexqueryToInsert[count($allQueryReplaced)].'</'.$repeatingXmlTag[$i].'_indexquery>', $queryString);
					}
					else {
						$queryString = generateRepeatingBlockCodeRelatedEntities($queryString, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $indexqueryToInsert[count($allQueryReplaced)], 0, $repeatingXmlTag[$i]);
					}
					$allQueryReplaced[] = str_replace('/*all*/', '', $queryString);
				}
				
				for ($j = count($allQueryReplaced) - 1; $j >= 0; $j--) {
					//$xmlpage = str_replace('ALLQUERYREPLACED_'.$j, 'ALL-QUERY-REPL_'.$j.':'.$allQueryReplaced[$j], $xmlpage);
					$xmlpage = str_replace('ALLQUERYREPLACED_'.$j, $allQueryReplaced[$j], $xmlpage);
				}
				//echo $xmlpage;
				//print_r($allQueryReplaced);
			}
			$xmlpage = str_replace('ALLREPLACED', '', $xmlpage);
			//echo $xmlpage;

			/******************************************************************************/
			/* Replica delle subquery contenenti entities etichettate dal tag / *block* / */
			/******************************************************************************/
			$repeatingXmlTag = array(	'section_initquery',
										'action_checkquery',
										'action_checkfield',
										'action_queryonsuccess',
										'action_queryonfail'
									);
			for ($i = 0; $i < count($repeatingXmlTag); $i++) {
				//echo $repeatingXmlTag[$i].'             ';
				$indexquery = 0;
				$indexsubquery = 0;
				$last_indexquery = 0;
				$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
				$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
				$endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>');
				if ($lt > $endXmlTags) {
					while ($lt > $endXmlTags) $endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>', $endXmlTags + 1);
					//echo ' NEW ('.$repeatingXmlTag[$i].'):'.$endXmlTags.'         ';
				}
				while ($lt >= 0 && $gt > 3 + strlen($repeatingXmlTag[$i])) {
					//echo $lt.' '.$gt.' '.$endXmlTags.'   ';
					$substring = substr($xmlpage, $lt, $gt - $lt);
					// get index query
					$lt_indexquery = strpos($substring, '<'.$repeatingXmlTag[$i].'_indexquery>') + 13 + strlen($repeatingXmlTag[$i]);
					$gt_indexquery = strpos($substring, '</'.$repeatingXmlTag[$i].'_indexquery>');
					if ($lt_indexquery !== false && $gt_indexquery !== false) {
						$indexquery = substr($substring, $lt_indexquery, $gt_indexquery - $lt_indexquery);
						if ($indexquery > $last_indexquery) {
							$last_indexquery = $indexquery;
							$indexsubquery = 1;
						}
						//echo $repeatingXmlTag[$i].' '.$indexquery.'   ';
					}
					// add BLOCKREPLACED
					$substring = str_replace('<'.$repeatingXmlTag[$i].'>', '<'.$repeatingXmlTag[$i].'BLOCKREPLACED>', str_replace('</'.$repeatingXmlTag[$i].'>', '</'.$repeatingXmlTag[$i].'BLOCKREPLACED>', $substring));
					if (strpos($substring, '/*block*/') === false) {
						$substitutestring = $substring;
						$substitutestring = preg_replace('/<'.$repeatingXmlTag[$i].'_indexsubquery>[0-9]+<\/'.$repeatingXmlTag[$i].'_indexsubquery>/', '<'.$repeatingXmlTag[$i].'_indexsubquery>'.$indexsubquery.'</'.$repeatingXmlTag[$i].'_indexsubquery>', $substitutestring);
						$indexsubquery++;
					}
					else {

					
					
						//if (preg_match('/__REF_/', $substring)) {
						if (preg_match('/__REF_ENTITY__|__REF_TABLE__|__REF_PRIMARY_KEY__|__REF_FOREIGN_KEY__|__REF_ATTRIBUTE_FOREIGN_KEY_|__REF_COLUMN_[^0-9]*__|__REF_ATTRIBUTE_[^0-9]*__/', $substring)) {
							$substitutestring = generateRepeatingBlockCodeReferredEntities($substring, $ref_table, $ref_entity, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, 0, $indexsubquery, $repeatingXmlTag[$i]);
						}
						else {
							$substitutestring = generateRepeatingBlockCodeRelatedEntities($substring, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, 0, $indexsubquery, $repeatingXmlTag[$i]);
						}
						//$substitutestring = generateRepeatingBlockCodeRelatedEntities($substring, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, 0, $indexsubquery, $repeatingXmlTag[$i]);



						$indexsubquery += substr_count($substitutestring, 'BLOCKREPLACED>') / 2;
					}
					$xmlpage = substr($xmlpage, 0, $lt).$substitutestring.substr($xmlpage, $gt);
					$endXmlTags += strlen($substitutestring) - ($gt - $lt);
					$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
					$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
					if ($lt > $endXmlTags) {
						$last_indexquery = 0;
						while ($lt > $endXmlTags) $endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>', $endXmlTags + 1);
						//echo ' NEW ('.$repeatingXmlTag[$i].'):'.$endXmlTags.'         ';
					}
				}
				//echo $xmlpage;
			}
			$xmlpage = str_replace('BLOCKREPLACED', '', $xmlpage);
			//echo $xmlpage;
			
			/************************************************************************/
			/* Replica delle entities contenute nei tag / *<block* / e / *block>* / */
			/************************************************************************/
			$repeatingTag = array(	'page_codecss',
									'page_codejavascript',
									'page_html',
									'page_command',
									'section_commandpreinitquery',
									'section_initquery_condition',
									'section_initquery_sql',
									'section_view_condition',
									'section_view_commandprelayout',
									'section_view_html',
									'section_view_htmllayout',
									'action_command',
									'action_commandonsuccess',
									'action_commandonfail',
									'action_nextpageonsuccess',
									'action_nextpageonfail',
									'action_checkquery_condition',
									'action_checkquery_sql',
									'action_checkfield_condition',
									'action_checkfield_error',
									'action_queryonsuccess_condition',
									'action_queryonsuccess_sql',
									'action_queryonfail_condition',
									'action_queryonfail_sql'
								);
			
			$pagedoc = new DOMDocument();
			if ($xmlpage != '') $pagedoc->loadXML($xmlpage);
			for ($i = 0; $i < count($repeatingTag); $i++) {
				$pagenodes = $pagedoc->getElementsByTagName($repeatingTag[$i]);
				//echo $repeatingTag[$i].' ';
				foreach ($pagenodes as $pagenode) {
					$string = $pagenode->nodeValue;
					// $string = str_replace('\\', '\\\\', $string); e' gia' nell'export di actions, sections e pages
					$string = str_replace('/*&lt;block*/', '/*<block*/', $string); // per i tag di tipo html: decodifico il '<'
					$string = str_replace('/*block&gt;*/', '/*block>*/', $string); // per i tag di tipo html: decodifico il '>'
					
					$lt = strpos($string, '/*<block*/');
					$gt = strpos($string, '/*block>*/') + 10;
					while ($lt >= 0 && $gt > 10) {
						//echo $lt.' '.$gt.'   ';
						$substring = substr($string, $lt, $gt - $lt);
						
						
						
						//if (preg_match('/__REF_/', $substring)) {
						if (preg_match('/__REF_ENTITY__|__REF_TABLE__|__REF_PRIMARY_KEY__|__REF_FOREIGN_KEY__|__REF_ATTRIBUTE_FOREIGN_KEY_|__REF_COLUMN_[^0-9]*__|__REF_ATTRIBUTE_[^0-9]*__/', $substring)) {
							$substitutestring = generateRepeatingBlockCodeReferredEntities($substring, $ref_table, $ref_entity, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, 0, 0, '');
						}
						else {
							$substitutestring = generateRepeatingBlockCodeRelatedEntities($substring, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, 0, 0, '');
						}
						//$substitutestring = generateRepeatingBlockCodeRelatedEntities($substring, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, 0, 0, '');
						
						
						
						$string = substr($string, 0, $lt).$substitutestring.substr($string, $gt);
						$lt = strpos($string, '/*<block*/');
						$gt = strpos($string, '/*block>*/') + 10;
					}
					//echo $string;
					$pagenode->nodeValue = urlencode('<![CDATA['.$string.']]>');
				}
			}
			$xmlpage = urldecode($pagedoc->saveHTML());
			$xmlpage = preg_replace("<!DOCTYPE[^\>]+>","",$xmlpage);
			$xmlpage = str_replace("<html><body>","",str_replace("</body></html>","",str_replace("<>","",$xmlpage)));
			//echo 'Dopo('.$pageID.')<textarea>'.$xmlpage.'</textarea><br />';
			//echo $xmlpage;

			/*************************************************************/
			/* Replica dei nodi HTML associati ad entities nelle Section */
			/*************************************************************/
			$repeatingHtmlTag = array(	'page_html',
										'section_view_html',
										'section_view_htmllayout'
									);
			for ($i = 0; $i < count($repeatingHtmlTag); $i++) {
				//echo $repeatingHtmlTag[$i].'             ';
				$lt = strpos($xmlpage, '<'.$repeatingHtmlTag[$i].'>') + 2 + strlen($repeatingHtmlTag[$i]);
				$gt = strpos($xmlpage, '</'.$repeatingHtmlTag[$i].'>');
				while ($lt >= 2 + strlen($repeatingHtmlTag[$i]) && $gt > 0) {
					//echo $lt.' '.$gt.'   ';
					$substring = substr($xmlpage, $lt, $gt - $lt);
					$substring = str_replace("<![CDATA[", "", str_replace("]]>", "", $substring));
					//echo '+++++++++++++++++++++'.$substring.'+++++++++++++++++++++++';
					//echo '+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++';
					$htmldoc = new DOMDocument();
					$htmldoc->loadHTML($substring);
					$htmlnodes = $htmldoc->getElementsByTagName('*');
					$substitutestring = '';
					foreach ($htmlnodes as $htmlnode) {
						$htmlsubnodes = $htmlnode->childNodes;
						foreach ($htmlsubnodes as $htmlsubnode) {
							// get html node
							$htmltext = $htmlsubnode->c14n();
							//if ($repeatingHtmlTag[$i] == 'page_html') echo 'HTML:'.$htmltext.'###################';
							// elimino i nodi: body e text
							if ($htmlsubnode->nodeName != 'body' && $htmlsubnode->nodeName != 'option' && $htmlsubnode->nodeValue != $htmltext) {
								//echo '########'.$htmlsubnode->nodeName.'::'.$htmltext.'                    ';							
								$htmltext = str_replace('\\', '\\\\', $htmltext);
								
								
								
								//if (preg_match('/__REF_/', $htmltext)) {
								if (preg_match('/__REF_ENTITY__|__REF_TABLE__|__REF_PRIMARY_KEY__|__REF_FOREIGN_KEY__|__REF_ATTRIBUTE_FOREIGN_KEY_|__REF_COLUMN_[^0-9]*__|__REF_ATTRIBUTE_[^0-9]*__/', $htmltext)) {
									$htmltext = generateRepeatingBlockCodeReferredEntities($htmltext, $ref_table, $ref_entity, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, 0, 0, $repeatingHtmlTag[$i]);
								}
								
								
								
								if (!preg_match('/__ENTITY__|__TABLE__|__PRIMARY_KEY__|__REL_FOREIGN_KEY__|__REL_ATTRIBUTE_FOREIGN_KEY_|__COLUMN_[^0-9]*__|__ATTRIBUTE_[^0-9]*__/', $htmltext)) {
									$substitutestring .= $htmltext;
								}
								else {
									//echo $htmltext;
									$substitutestring .= generateRepeatingBlockCodeRelatedEntities($htmltext, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, 0, 0, $repeatingHtmlTag[$i]);
								}
								//if ($repeatingHtmlTag[$i] == 'page_html') echo 'HTML-SUBSTITUTE:'.$substitutestring.'***************';
							}
						}
					}
					//if ($repeatingHtmlTag[$i] == 'page_html') echo 'HTML-DEF-SUBSTITUTE:'.$substitutestring.'!!!!!!!!!!!!!!!!';
									
					$xmlpage = substr($xmlpage, 0, $lt - 2 - strlen($repeatingHtmlTag[$i]) ).'<'.$repeatingHtmlTag[$i].'ENTITYREPLACED><![CDATA['.$substitutestring.']]></'.$repeatingHtmlTag[$i].'ENTITYREPLACED>'.substr($xmlpage, $gt + 3 + strlen($repeatingHtmlTag[$i]) );

					//if ($repeatingHtmlTag[$i] == 'page_html') echo 'HTML-DEF-SUBSTITUTE:'.$xmlpage.'!!!!!!!!!!!!!!!!';
					
					// check for new section tag...
					$lt = strpos($xmlpage, '<'.$repeatingHtmlTag[$i].'>') + 2 + strlen($repeatingHtmlTag[$i]);
					$gt = strpos($xmlpage, '</'.$repeatingHtmlTag[$i].'>');
				}
				//echo $xmlpage;
			}
			$xmlpage = str_replace('ENTITYREPLACED', '', str_replace("</input>", "", str_replace("</img>", "", $xmlpage)));
			//echo $xmlpage;
		}
		
		
		
		
		
		
		/**********************************************************/
		/* Replica del codice contenuto nei tag / *<* / e / *>* / */
		/**********************************************************/
		$repeatingTag = array(	'page_codecss',
								'page_codejavascript',
								'page_html',
								'page_command',
								'section_commandpreinitquery',
								'section_initquery_condition',
								'section_initquery_sql',
								'section_view_condition',
								'section_view_commandprelayout',
								'section_view_html',
								'section_view_htmllayout',
								'action_command',
								'action_commandonsuccess',
								'action_commandonfail',
								'action_nextpageonsuccess',
								'action_nextpageonfail',
								'action_checkquery_condition',
								'action_checkquery_sql',
								'action_checkfield_condition',
								'action_checkfield_error',
								'action_queryonsuccess_condition',
								'action_queryonsuccess_sql',
								'action_queryonfail_condition',
								'action_queryonfail_sql'
							);
		
		$pagedoc = new DOMDocument();
		if ($xmlpage != '') $pagedoc->loadXML($xmlpage);
		for ($i = 0; $i < count($repeatingTag); $i++) {
			$pagenodes = $pagedoc->getElementsByTagName($repeatingTag[$i]);
			//echo $repeatingTag[$i].' ';
			foreach ($pagenodes as $pagenode) {
				$string = $pagenode->nodeValue;
				// $string = str_replace('\\', '\\\\', $string); e' gia' nell'export di actions, sections e pages
				$string = str_replace('/*&lt;*/', '/*<*/', $string); // per i tag di tipo html: decodifico il '<'
				$string = str_replace('/*&gt;*/', '/*>*/', $string); // per i tag di tipo html: decodifico il '>'
				
				$lt = strpos($string, '/*<*/');
				$gt = strpos($string, '/*>*/') + 5;
				while ($lt >= 0 && $gt > 5) {
					//echo $lt.' '.$gt.'   ';
					$substring = substr($string, $lt, $gt - $lt);
					if ($pagemodelPrefix == '') $substitutestring = generateRepeatingCode($substring, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, 0, '');
					//else if ($pagemodelPrefix == 'REL_') $substitutestring = generateRepeatingCodeRelated($substring, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, 0, '');
					else if ($pagemodelPrefix == 'REL_') $substitutestring = generateRepeatingCodeRelated($substring, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $ref_datamodel, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, 0, '');
					$string = substr($string, 0, $lt).$substitutestring.substr($string, $gt);
					$lt = strpos($string, '/*<*/');
					$gt = strpos($string, '/*>*/') + 5;
				}
				//echo $string;
				$pagenode->nodeValue = urlencode('<![CDATA['.$string.']]>');
			}
		}
		$xmlpage = urldecode($pagedoc->saveHTML());
		$xmlpage = preg_replace("<!DOCTYPE[^\>]+>","",$xmlpage);
		$xmlpage = str_replace("<html><body>","",str_replace("</body></html>","",str_replace("<>","",$xmlpage)));
		//echo 'Dopo('.$pageID.')<textarea>'.$xmlpage.'</textarea><br />';
		//echo $xmlpage;

		/**************************/
		/* Replica delle subquery */
		/**************************/
		$repeatingXmlTag = array(	'section_initquery',
									'action_checkquery',
									'action_checkfield',
									'action_queryonsuccess',
									'action_queryonfail'
								);
		for ($i = 0; $i < count($repeatingXmlTag); $i++) {
			//echo $repeatingXmlTag[$i].'             ';
			$indexquery = 0;
			$indexsubquery = 0;
			$last_indexquery = 0;
			$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
			$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
			$endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>');
			if ($lt > $endXmlTags) {
				while ($lt > $endXmlTags) $endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>', $endXmlTags + 1);
				//echo ' NEW ('.$repeatingXmlTag[$i].'):'.$endXmlTags.'         ';
			}
			while ($lt >= 0 && $gt > 3 + strlen($repeatingXmlTag[$i])) {
				//echo $lt.' '.$gt.' '.$endXmlTags.'   ';
				$substring = substr($xmlpage, $lt, $gt - $lt);
				// get index query
				$lt_indexquery = strpos($substring, '<'.$repeatingXmlTag[$i].'_indexquery>') + 13 + strlen($repeatingXmlTag[$i]);
				$gt_indexquery = strpos($substring, '</'.$repeatingXmlTag[$i].'_indexquery>');
				if ($lt_indexquery !== false && $gt_indexquery !== false) {
					$indexquery = substr($substring, $lt_indexquery, $gt_indexquery - $lt_indexquery);
					if ($indexquery > $last_indexquery) {
						$last_indexquery = $indexquery;
						$indexsubquery = 1;
					}
					//echo $repeatingXmlTag[$i].' '.$indexquery.'   ';
				}
				// add REPLACED
				$substring = str_replace('<'.$repeatingXmlTag[$i].'>', '<'.$repeatingXmlTag[$i].'REPLACED>', str_replace('</'.$repeatingXmlTag[$i].'>', '</'.$repeatingXmlTag[$i].'REPLACED>', $substring));
				if (!preg_match('/_PRIMARY_KEY_|_FOREIGN_KEY_|_COLUMN_|_ATTRIBUTE_/', $substring)) {
					$substitutestring = $substring;
					$substitutestring = preg_replace('/<'.$repeatingXmlTag[$i].'_indexsubquery>[0-9]+<\/'.$repeatingXmlTag[$i].'_indexsubquery>/', '<'.$repeatingXmlTag[$i].'_indexsubquery>'.$indexsubquery.'</'.$repeatingXmlTag[$i].'_indexsubquery>', $substitutestring);
					$indexsubquery++;
				}
				else {
					if ($pagemodelPrefix == '') $substitutestring = generateRepeatingCode($substring, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $indexsubquery, $repeatingXmlTag[$i]);
					//else if ($pagemodelPrefix == 'REL_') $substitutestring = generateRepeatingCodeRelated($substring, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $indexsubquery, $repeatingXmlTag[$i]);
					else if ($pagemodelPrefix == 'REL_') $substitutestring = generateRepeatingCodeRelated($substring, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $ref_datamodel, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, $indexsubquery, $repeatingXmlTag[$i]);
					$indexsubquery += substr_count($substitutestring, 'REPLACED>') / 2;
				}
				$xmlpage = substr($xmlpage, 0, $lt).$substitutestring.substr($xmlpage, $gt);
				$endXmlTags += strlen($substitutestring) - ($gt - $lt);
				$lt = strpos($xmlpage, '<'.$repeatingXmlTag[$i].'>');
				$gt = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'>') + 3 + strlen($repeatingXmlTag[$i]);
				if ($lt > $endXmlTags) {
					$last_indexquery = 0;
					while ($lt > $endXmlTags) $endXmlTags = strpos($xmlpage, '</'.$repeatingXmlTag[$i].'s>', $endXmlTags + 1);
					//echo ' NEW ('.$repeatingXmlTag[$i].'):'.$endXmlTags.'         ';
				}
			}
			//echo $$xmlpage;
		}
		$xmlpage = str_replace('REPLACED', '', $xmlpage);
		//echo $xmlpage;
		
		/********************************************************/
		/* Posizionamento e replica dei nodi HTML nelle Section */
		/********************************************************/
		$repeatingHtmlTag = array(	/*'page_html',*/
									'section_view_html',
									'section_view_htmllayout'
								);
		$sectionHeigthOffset = array();
		for ($i = 0; $i < count($repeatingHtmlTag); $i++) {
			//echo $repeatingHtmlTag[$i].'             ';
			$lt = strpos($xmlpage, '<'.$repeatingHtmlTag[$i].'>') + 2 + strlen($repeatingHtmlTag[$i]);
			$gt = strpos($xmlpage, '</'.$repeatingHtmlTag[$i].'>');
			while ($lt >= 2 + strlen($repeatingHtmlTag[$i]) && $gt > 0) {
				//echo $lt.' '.$gt.'   ';
				$substring = substr($xmlpage, $lt, $gt - $lt);
				$substring = str_replace("<![CDATA[", "", str_replace("]]>", "", $substring));
				//echo '+++++++++++++++++++++'.$substring.'+++++++++++++++++++++++';
				//echo '+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++';
				$htmldoc = new DOMDocument();
				$substitutestring = '';
				if ($substring != null && $substring != '') {
					$htmldoc->loadHTML($substring);
					$htmlnodes = $htmldoc->getElementsByTagName('*');
					$cssTop = 0;
					foreach ($htmlnodes as $htmlnode) {
						$htmlsubnodes = $htmlnode->childNodes;
						foreach ($htmlsubnodes as $htmlsubnode) {
							// get html node
							$htmltext = $htmlsubnode->c14n();					
							// elimino i nodi: body e text
							if ($htmlsubnode->nodeName != 'body' && $htmlsubnode->nodeName != 'option' && $htmlsubnode->nodeValue != $htmltext) {
								//echo '########'.$htmlsubnode->nodeName.'::'.$htmltext.'                    ';							
								$htmltext = str_replace('\\', '\\\\', $htmltext);
								if (!preg_match('/_PRIMARY_KEY_|_FOREIGN_KEY_|_COLUMN_|_ATTRIBUTE_/', $htmltext)) {
									// NO CSS TOP RESIZE // $htmltext = preg_replace('/top:[^;]*;/', 'top: '.$cssTop.'px;', $htmltext);
									$substitutestring .= $htmltext;
									// NO CSS TOP RESIZE // $cssTop = $cssTop + 20;
								}
								else {
									// NO CSS TOP RESIZE // $htmltext = preg_replace('/top:[^;]*;/', 'top: REPLACE_TOPpx;', $htmltext);
									if ($pagemodelPrefix == '') $substitutestring .= generateRepeatingCode($htmltext, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, 0, '');
									//else if ($pagemodelPrefix == 'REL_') $substitutestring .= generateRepeatingCodeRelated($htmltext, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, 0, '');
									else if ($pagemodelPrefix == 'REL_') $substitutestring .= generateRepeatingCodeRelated($htmltext, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $ref_datamodel, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, 0, '');
								}
							}
						}
					}
				}
				
				/* NO CSS TOP RESIZE if ($pagemodelPrefix == '') {
					for ($j = 0; $j < count($column); $j++) {
						//echo $cssTop.' ';
						if (preg_match('/REPLACE_'.$column[$j].'_TOP/', $substitutestring)) {
							$substitutestring = str_replace('REPLACE_'.$column[$j].'_TOP', $cssTop, $substitutestring);
							$cssTop = $cssTop + 20;
						}
					}
				}
				else if ($pagemodelPrefix == 'REL_') {
					for ($j = 0; $j < count($rel_column); $j++) {
						//echo $cssTop.' ';
						if (preg_match('/REPLACE_'.$rel_column[$j].'_TOP/', $substitutestring)) {
							$substitutestring = str_replace('REPLACE_'.$rel_column[$j].'_TOP', $cssTop, $substitutestring);
							$cssTop = $cssTop + 20;
						}
					}
					for ($j = 0; $j < count($column); $j++) {
						//echo $cssTop.' ';
						//echo $column[$j].':'.preg_match('/REPLACE_'.str_replace('.', '..', $column[$j]).'_TOP/', $substitutestring).'?';
						if (preg_match('/REPLACE_'.str_replace('.', '..', $column[$j]).'_TOP/', $substitutestring)) {
							$substitutestring = str_replace('REPLACE_'.$column[$j].'_TOP', $cssTop, $substitutestring);
							$cssTop = $cssTop + 20;
						}
					}
				}*/
				
				$xmlpage = substr($xmlpage, 0, $lt - 2 - strlen($repeatingHtmlTag[$i]) ).'<'.$repeatingHtmlTag[$i].'REPLACED><![CDATA['.$substitutestring.']]></'.$repeatingHtmlTag[$i].'REPLACED>'.substr($xmlpage, $gt + 3 + strlen($repeatingHtmlTag[$i]) );
				
				// fit section heigth			
				$ltHeigth = strpos($xmlpage, '<section_view_height>') + strlen('<section_view_height>');
				$gtHeigth = strpos($xmlpage, '</section_view_height>');
				$substringHeigth = substr($xmlpage, $ltHeigth, $gtHeigth - $ltHeigth);
				if ($ltHeigth >= strlen('<section_view_height>') && $gtHeigth > 0) {
					// NO CSS TOP RESIZE // $xmlpage = substr($xmlpage, 0, $ltHeigth - strlen('<section_view_height>')).'<section_view_heightREPLACED>'.$cssTop.'</section_view_heightREPLACED>'.substr($xmlpage, $gtHeigth + strlen('</section_view_height>'));
					$xmlpage = substr($xmlpage, 0, $ltHeigth - strlen('<section_view_height>')).'<section_view_heightREPLACED>'.$substringHeigth.'</section_view_heightREPLACED>'.substr($xmlpage, $gtHeigth + strlen('</section_view_height>'));
					// END: NO CSS TOP RESIZE
				}
				$ltDefault = strpos($xmlpage, '<section_view_isdefault>') + strlen('<section_view_isdefault>');
				$gtDefault = strpos($xmlpage, '</section_view_isdefault>');
				$substringIsDefault = substr($xmlpage, $ltDefault, $gtDefault - $ltDefault);
				if ($ltDefault >= strlen('<section_view_isdefault>') && $gtDefault > 0) {
					$xmlpage = substr($xmlpage, 0, $ltDefault - strlen('<section_view_isdefault>')).'<section_view_isdefaultREPLACED>'.$substringIsDefault.'</section_view_isdefaultREPLACED>'.substr($xmlpage, $gtDefault + strlen('</section_view_isdefault>'));
				}
				if ($substringIsDefault == '1') {
					$ltId = strpos($xmlpage, '<section_id>') + strlen('<section_id>');
					$gtId = strpos($xmlpage, '</section_id>');
					if ($ltId >= strlen('<section_id>') && $gtId > 0) {
						$substringId = substr($xmlpage, $ltId, $gtId - $ltId);
						$sectionHeigthOffset[$substringId] = $cssTop - $substringHeigth;
						if ($sectionHeigthOffset[$substringId] < 0) $sectionHeigthOffset[$substringId] = 0;
						//echo 'gtId:'.$gtId.' less then ltHeigth:'.$ltHeigth.' sectionId:'.$substringId.' cssTop:'.$cssTop.' substringHeigth:'.$substringHeigth.'<br />';
						$xmlpage = substr($xmlpage, 0, $ltId - strlen('<section_id>')).'<section_idREPLACED>'.$substringId.'</section_idREPLACED>'.substr($xmlpage, $gtId + strlen('</section_id>'));
					}
				}

				
				
				// check for new section tag...
				$lt = strpos($xmlpage, '<'.$repeatingHtmlTag[$i].'>') + 2 + strlen($repeatingHtmlTag[$i]);
				$gt = strpos($xmlpage, '</'.$repeatingHtmlTag[$i].'>');
			}
			//echo $xmlpage;
		}
		//print_r($sectionHeigthOffset);
		//echo $xmlpage;
		
		/***************************************************************************/
		/* fit sections container top and heigth in the page (eventually in pages) */
		/***************************************************************************/
		$ltPage = strpos($xmlpage, '<page_html>') + strlen('<page_html>');
		$gtPage = strpos($xmlpage, '</page_html>');
		while ($ltPage >= strlen('<page_html>') && $gtPage > 0) {
			$substringPage = substr($xmlpage, $ltPage, $gtPage - $ltPage);
			$substringPage = str_replace("<![CDATA[", "", str_replace("]]>", "", $substringPage));
			preg_match_all('/\<[^\>]*>[^\<]*<\/div>/', $substringPage, $divMatches);
			//preg_match_all('/id=\"[^\"]*\"/', $substringPage, $idMatches);
			preg_match_all('/title=\"[0-9]+\"/', $substringPage, $titleMatches);
			//preg_match_all('/class=\"[^\"]+\"/', $substringPage, $classMatches);
			preg_match_all('/width:[^;]*px;/', $substringPage, $widthMatches);
			preg_match_all('/height:[^;]*px;/', $substringPage, $heightMatches);
			preg_match_all('/top:[^;]*px;/', $substringPage, $topMatches);
			preg_match_all('/left:[^;]*px;/', $substringPage, $leftMatches);
			//print_r($divMatches); print_r($idMatches); print_r($titleMatches); print_r($classMatches); print_r($widthMatches); print_r($heightMatches); print_r($topMatches); print_r($leftMatches);
			$sectionDiv = array();
			//$sectionTitle = array();
			//$sectionClass = array();
			$sectionWidth = array();
			$sectionHeight = array();
			$sectionTop = array();
			$sectionLeft = array();
			if (count($titleMatches) != 0 && count($titleMatches[0]) != 0) for ($i = 0; $i < count($titleMatches[0]); $i++) {
				$titleMatchesSectionId = preg_replace('/title=|"/', "", $titleMatches[0][$i]);
				$sectionDiv[$titleMatchesSectionId] = $divMatches[0][$i];
				//$sectionTitle[$titleMatchesSectionId] = $titleMatches[0][$i];
				//$sectionClass[$titleMatchesSectionId] = $classMatches[0][$i];
				$sectionWidth[$titleMatchesSectionId] = trim(preg_replace('/width:|px;/', "", $widthMatches[0][$i]));
				$sectionHeight[$titleMatchesSectionId] = trim(preg_replace('/height:|px;/', "", $heightMatches[0][$i])) + $sectionHeigthOffset[$titleMatchesSectionId];
				$sectionTop[$titleMatchesSectionId] = trim(preg_replace('/top:|px;/', "", $topMatches[0][$i]));
				$sectionLeft[$titleMatchesSectionId] = trim(preg_replace('/left:|px;/', "", $leftMatches[0][$i]));
			}
			//print_r($sectionDiv);	print_r($sectionWidth);	print_r($sectionHeight); print_r($sectionTop); print_r($sectionLeft);
			//print_r($sectionTop);
			asort($sectionTop, SORT_NUMERIC);
			$sectionId = array_keys($sectionTop);
			//print_r($sectionTop);
			//print_r($sectionId);
			for ($i = 0; $i < count($sectionId); $i++) {
				for ($j = $i + 1; $j < count($sectionId); $j++) {
					// solo per le section "$j" sottostanti a "$i" calcolo le intersezioni
					$intersect = false;
					$otherSectionCorners = array(
						array($sectionLeft[$sectionId[$j]], $sectionTop[$sectionId[$j]]),
						array($sectionLeft[$sectionId[$j]], $sectionTop[$sectionId[$j]] + $sectionHeight[$sectionId[$j]]),
						array($sectionLeft[$sectionId[$j]] + $sectionWidth[$sectionId[$j]], $sectionTop[$sectionId[$j]] + $sectionHeight[$sectionId[$j]]),
						array($sectionLeft[$sectionId[$j]] + $sectionWidth[$sectionId[$j]], $sectionTop[$sectionId[$j]])
					);
					foreach ($otherSectionCorners as $otherSectionCorner) {
						if (
							$otherSectionCorner[0] > $sectionLeft[$sectionId[$i]] &&
							$otherSectionCorner[0] < $sectionLeft[$sectionId[$i]] + $sectionWidth[$sectionId[$i]] &&
							$otherSectionCorner[1] > $sectionTop[$sectionId[$i]] &&
							$otherSectionCorner[1] < $sectionTop[$sectionId[$i]] + $sectionHeight[$sectionId[$i]]
						) {
							$intersect = true;
						}
					}
					if (!$intersect) {
						// verifico il caso particolare in cui otherSection("$j") ha i vertici tutti fuori section("$i") pur intersecandolo
						if (!(
							$sectionTop[$sectionId[$j]] >= $sectionTop[$sectionId[$i]] + $sectionHeight[$sectionId[$i]] ||
							$sectionLeft[$sectionId[$j]] + $sectionWidth[$sectionId[$j]] <= $sectionLeft[$sectionId[$i]] ||
							$sectionLeft[$sectionId[$j]] >= $sectionLeft[$sectionId[$i]] + $sectionWidth[$sectionId[$i]]
						)) {
							$intersect = true;
						}
					}
					if ($intersect) {
						$sectionTop[$sectionId[$j]] += $sectionHeigthOffset[$sectionId[$i]];
						//echo "INTERSECT ";
					}
				}
			}
			
			$substringPage = '';
			foreach ($sectionDiv as $sectionDivId => $sectionDivHtml) {
				$substringPage .= preg_replace('/height:[^;]*px;/', 'height: '.$sectionHeight[$sectionDivId].'px;', preg_replace('/top:[^;]*px;/', 'top: '.$sectionTop[$sectionDivId].'px;', $sectionDivHtml));
			}
			
			$xmlpage = substr($xmlpage, 0, $ltPage - strlen('<page_html>')).'<page_htmlREPLACED><![CDATA['.$substringPage.']]></page_htmlREPLACED>'.substr($xmlpage, $gtPage + strlen('</page_html>'));
			$ltPage = strpos($xmlpage, '<page_html>') + strlen('<page_html>');
			$gtPage = strpos($xmlpage, '</page_html>');
		}
		
		$xmlpage = str_replace('REPLACED', '', str_replace("</input>", "", str_replace("</img>", "", $xmlpage)));
		/**/echo $xmlpage;		
	}
	
	function generateRepeatingCode($string, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $indexsubquery, $tag) {
		$replacedString = '';
		for ($i = 0; $i < count($datamodel); $i++) {
			//echo $datamodel[$i]."::";
			for ($j = 0; $j < count($columnByDatamodel[$datamodel[$i]]); $j++) {
				//echo $columnByDatamodel[$datamodel[$i]][$j]."::";
				$replacedSubString = $string;
				if ($datamodel[$i] == 'PRIMARY_KEY') $replacedSubString = str_replace('__PRIMARY_KEY__', $columnByDatamodel[$datamodel[$i]][$j], $replacedSubString);
				else $replacedSubString = str_replace('__COLUMN_'.$datamodel[$i].'__', $columnByDatamodel[$datamodel[$i]][$j], 
					str_replace('__ATTRIBUTE_'.$datamodel[$i].'__', $attribute[$columnByDatamodel[$datamodel[$i]][$j]], 
					str_replace('__ATTRIBUTE_'.$datamodel[$i].'_FROM__', $attributeFrom[$columnByDatamodel[$datamodel[$i]][$j]], 
					str_replace('__ATTRIBUTE_'.$datamodel[$i].'_TO__', $attributeTo[$columnByDatamodel[$datamodel[$i]][$j]], 
					$replacedSubString))));
				if ($replacedSubString != $string) {
					// only for /*<*/ and /*>*/
					$replacedSubString = str_replace('/*<*/', '', str_replace('/*>*/', '', $replacedSubString));
					// only for query and error
					if ($indexsubquery != 0) {
						$replacedSubString = preg_replace('/<'.$tag.'_indexsubquery>[0-9]+<\/'.$tag.'_indexsubquery>/', '<'.$tag.'_indexsubquery>'.$indexsubquery.'</'.$tag.'_indexsubquery>', $replacedSubString);
						$indexsubquery++;
					}
					// only for html
					$replacedSubString = str_replace('REPLACE_TOP', 'REPLACE_'.$columnByDatamodel[$datamodel[$i]][$j].'_TOP', $replacedSubString);
					$replacedString .= $replacedSubString;
				}
			}
		}
		return $replacedString;
	}

	function generateRepeatingCodeRelated($string, $rel_datamodel, $rel_columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $datamodel, $columnByDatamodel, $attribute, $attributeFrom, $attributeTo, $ref_datamodel, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, $indexsubquery, $tag) {
		$replacedString = '';
		for ($i = 0; $i < count($rel_datamodel); $i++) {
			//echo $rel_datamodel[$i]."::";
			for ($j = 0; $j < count($rel_columnByDatamodel[$rel_datamodel[$i]]); $j++) {
				//echo $rel_columnByDatamodel[$rel_datamodel[$i]][$j]."::";
				$replacedSubString = $string;
				if ($rel_datamodel[$i] == 'PRIMARY_KEY') $replacedSubString = str_replace('__REL_PRIMARY_KEY__', $rel_columnByDatamodel[$rel_datamodel[$i]][$j], $replacedSubString);
				else $replacedSubString = str_replace('__REL_COLUMN_'.$rel_datamodel[$i].'__', $rel_columnByDatamodel[$rel_datamodel[$i]][$j], 
					str_replace('__REL_ATTRIBUTE_'.$rel_datamodel[$i].'__', $rel_attribute[$rel_columnByDatamodel[$rel_datamodel[$i]][$j]], 
					str_replace('__REL_ATTRIBUTE_'.$rel_datamodel[$i].'_FROM__', $rel_attributeFrom[$rel_columnByDatamodel[$rel_datamodel[$i]][$j]], 
					str_replace('__REL_ATTRIBUTE_'.$rel_datamodel[$i].'_TO__', $rel_attributeTo[$rel_columnByDatamodel[$rel_datamodel[$i]][$j]], 
					$replacedSubString))));
				if ($replacedSubString != $string) {
					// only for /*<*/ and /*>*/
					$replacedSubString = str_replace('/*<*/', '', str_replace('/*>*/', '', $replacedSubString));
					// only for query and error
					if ($indexsubquery != 0) {
						$replacedSubString = preg_replace('/<'.$tag.'_indexsubquery>[0-9]+<\/'.$tag.'_indexsubquery>/', '<'.$tag.'_indexsubquery>'.$indexsubquery.'</'.$tag.'_indexsubquery>', $replacedSubString);
						$indexsubquery++;
					}
					// only for html
					$replacedSubString = str_replace('REPLACE_TOP', 'REPLACE_'.$rel_columnByDatamodel[$rel_datamodel[$i]][$j].'_TOP', $replacedSubString);
					$replacedString .= $replacedSubString;
				}
			}
		}
		//print_r($datamodel);
		for ($i = 0; $i < count($datamodel); $i++) {
			//echo $datamodel[$i]."::";
			for ($j = 0; $j < count($columnByDatamodel[$datamodel[$i]]); $j++) {
				//echo $columnByDatamodel[$datamodel[$i]][$j]."::";
				$replacedSubString = $string;
				$columnNameWithoutIndex = preg_replace('/..([0-9]+)../', '', $columnByDatamodel[$datamodel[$i]][$j]);
				if ($datamodel[$i] == 'PRIMARY_KEY') $replacedSubString = str_replace('__PRIMARY_KEY__', $columnNameWithoutIndex, $replacedSubString);
				else $replacedSubString = str_replace('__COLUMN_'.$datamodel[$i].'__', $columnNameWithoutIndex, 
					str_replace('__ATTRIBUTE_'.$datamodel[$i].'__', $attribute[$columnByDatamodel[$datamodel[$i]][$j]], 
					str_replace('__ATTRIBUTE_'.$datamodel[$i].'_FROM__', $attributeFrom[$columnByDatamodel[$datamodel[$i]][$j]], 
					str_replace('__ATTRIBUTE_'.$datamodel[$i].'_TO__', $attributeTo[$columnByDatamodel[$datamodel[$i]][$j]], 
					$replacedSubString))));
				if ($replacedSubString != $string) {
					// only for /*<*/ and /*>*/
					$replacedSubString = str_replace('/*<*/', '', str_replace('/*>*/', '', $replacedSubString));
					// only for query and error
					if ($indexsubquery != 0) {
						$replacedSubString = preg_replace('/<'.$tag.'_indexsubquery>[0-9]+<\/'.$tag.'_indexsubquery>/', '<'.$tag.'_indexsubquery>'.$indexsubquery.'</'.$tag.'_indexsubquery>', $replacedSubString);
						$indexsubquery++;
					}
					// only for html
					$replacedSubString = str_replace('REPLACE_TOP', 'REPLACE_'.$columnByDatamodel[$datamodel[$i]][$j].'_TOP', $replacedSubString);
					$replacedString .= $replacedSubString;
				}
			}
		}

		//print_r($ref_datamodel);
		for ($i = 0; $i < count($ref_datamodel); $i++) {
			//echo $ref_datamodel[$i]."::";
			for ($j = 0; $j < count($ref_columnByDatamodel[$ref_datamodel[$i]]); $j++) {
				//echo $ref_columnByDatamodel[$ref_datamodel[$i]][$j]."::";
				$replacedSubString = $string;
				$columnNameWithoutIndex = preg_replace('/..([0-9]+)../', '', $ref_columnByDatamodel[$ref_datamodel[$i]][$j]);
				if ($ref_datamodel[$i] == 'PRIMARY_KEY') $replacedSubString = str_replace('__REF_PRIMARY_KEY__', $columnNameWithoutIndex, $replacedSubString);
				else $replacedSubString = str_replace('__REF_COLUMN_'.$ref_datamodel[$i].'__', $columnNameWithoutIndex, 
					str_replace('__REF_ATTRIBUTE_'.$ref_datamodel[$i].'__', $ref_attribute[$ref_columnByDatamodel[$ref_datamodel[$i]][$j]], 
					str_replace('__REF_ATTRIBUTE_'.$ref_datamodel[$i].'_FROM__', $ref_attributeFrom[$ref_columnByDatamodel[$ref_datamodel[$i]][$j]], 
					str_replace('__REF_ATTRIBUTE_'.$ref_datamodel[$i].'_TO__', $ref_attributeTo[$ref_columnByDatamodel[$ref_datamodel[$i]][$j]], 
					$replacedSubString))));
				if ($replacedSubString != $string) {
					// only for /*<*/ and /*>*/
					$replacedSubString = str_replace('/*<*/', '', str_replace('/*>*/', '', $replacedSubString));
					// only for query and error
					if ($indexsubquery != 0) {
						$replacedSubString = preg_replace('/<'.$tag.'_indexsubquery>[0-9]+<\/'.$tag.'_indexsubquery>/', '<'.$tag.'_indexsubquery>'.$indexsubquery.'</'.$tag.'_indexsubquery>', $replacedSubString);
						$indexsubquery++;
					}
					// only for html
					$replacedSubString = str_replace('REPLACE_TOP', 'REPLACE_'.$ref_columnByDatamodel[$ref_datamodel[$i]][$j].'_TOP', $replacedSubString);
					$replacedString .= $replacedSubString;
				}
			}
		}
		
		return $replacedString;
	}

	function generateRepeatingBlockCodeRelatedEntities($string, $rel_columnByDatamodel, $table, $entity, $columnByDatamodel, $rel_attribute, $rel_attributeFrom, $rel_attributeTo, $indexquery, $indexsubquery, $tag) {
		$replacedString = '';
		$modelKeyword = array (	'TEXT',
							'IMAGE',
							'FILE',
							'URL',
							'NUMERIC',
							'BOOLEAN',
							'DATE',
							'TIME',
							'DATETIME'
						);
		if (array_key_exists('FOREIGN_KEY', $rel_columnByDatamodel)) for ($j = 0; $j < count($rel_columnByDatamodel['FOREIGN_KEY']); $j++) {
			$replacedSubString = $string;
			//$replacedSubString = str_replace('__ENTITY__', '__ENTITY_'.$j.'__', $replacedSubString);
			$replacedSubString = str_replace('__ENTITY__', $entity[$j], $replacedSubString);
			//$replacedSubString = str_replace('__TABLE__', '__TABLE_'.$j.'__', $replacedSubString);
			$replacedSubString = str_replace('__TABLE__', $table[$j], $replacedSubString);
			//$replacedSubString = str_replace('__PRIMARY_KEY__', '__PRIMARY_KEY_'.$j.'__', $replacedSubString);
			$columnNameWithoutIndex = preg_replace('/..([0-9]+)../', '', $columnByDatamodel['PRIMARY_KEY_'.$j][0]);
			$replacedSubString = str_replace('__PRIMARY_KEY__', $columnNameWithoutIndex, $replacedSubString);
			$replacedSubString = str_replace('__REL_FOREIGN_KEY__', $rel_columnByDatamodel['FOREIGN_KEY'][$j],
				str_replace('__REL_ATTRIBUTE_FOREIGN_KEY__', $rel_attribute[$rel_columnByDatamodel['FOREIGN_KEY'][$j]], 
				str_replace('__REL_ATTRIBUTE_FOREIGN_KEY_FROM__', $rel_attributeFrom[$rel_columnByDatamodel['FOREIGN_KEY'][$j]], 
				str_replace('__REL_ATTRIBUTE_FOREIGN_KEY_TO__', $rel_attributeTo[$rel_columnByDatamodel['FOREIGN_KEY'][$j]], 
				$replacedSubString))));				
			for ($i = 0; $i < count($modelKeyword); $i++) {
				$replacedSubString = str_replace('__COLUMN_'.$modelKeyword[$i].'__', '__COLUMN_'.$modelKeyword[$i].'_'.$j.'__',
					str_replace('__ATTRIBUTE_'.$modelKeyword[$i].'__', '__ATTRIBUTE_'.$modelKeyword[$i].'_'.$j.'__',
					str_replace('__ATTRIBUTE_'.$modelKeyword[$i].'_FROM__', '__ATTRIBUTE_'.$modelKeyword[$i].'_'.$j.'_FROM__',
					str_replace('__ATTRIBUTE_'.$modelKeyword[$i].'_TO__', '__ATTRIBUTE_'.$modelKeyword[$i].'_'.$j.'_TO__', $replacedSubString))));
			}
			if ($replacedSubString != $string) {
				// only for /*<block*/ and /*block>*/
				$replacedSubString = str_replace('/*<block*/', '', str_replace('/*block>*/', '', $replacedSubString));
				// only for query and error
				if ($indexquery != 0) {
					$replacedSubString = preg_replace('/<'.$tag.'_indexquery>[0-9]+<\/'.$tag.'_indexquery>/', '<'.$tag.'_indexquery>'.$indexquery.'</'.$tag.'_indexquery>', $replacedSubString);
					$indexquery++;
				}
				if ($indexsubquery != 0) {
					$replacedSubString = preg_replace('/<'.$tag.'_indexsubquery>[0-9]+<\/'.$tag.'_indexsubquery>/', '<'.$tag.'_indexsubquery>'.$indexsubquery.'</'.$tag.'_indexsubquery>', $replacedSubString);
					$replacedSubString = str_replace('/*block*/', '', $replacedSubString);
					$indexsubquery++;
				}
				// only for html
				if ($tag == 'page_html') {
					preg_match_all('/title=\"[0-9]+\"/', $replacedSubString, $titleMatches);
					$titleMatchesSectionId = preg_replace('/title=|"/', "", $titleMatches[0][0]);
					$generatedTitleId = '';
					for ($k = 0; $k <= $j; $k++) $generatedTitleId .= '0';
					$generatedTitleId .= $titleMatchesSectionId;
					$replacedSubString = preg_replace('/title="[0-9]+"/', 'title="'.$generatedTitleId.'"', $replacedSubString);
				}
				if ($tag == 'section') {
					$ltId = strpos($replacedSubString, '<section_id>') + 12;
					$gtId = strpos($replacedSubString, '</section_id>');
					if ($ltId >= 12 && $gtId > 0) $titleMatchesSectionId = substr($replacedSubString, $ltId, $gtId - $ltId);
					//echo $tag.':'.$titleMatchesSectionId.'###';
					$generatedTitleId = '';
					for ($k = 0; $k <= $j; $k++) $generatedTitleId .= '0';
					$generatedTitleId .= $titleMatchesSectionId;
					$replacedSubString = preg_replace('/<section_id>[0-9]+<\/section_id>/', '<section_id>'.$generatedTitleId.'</section_id>', $replacedSubString);
				}
				$replacedString .= $replacedSubString;
			}
		}
		return $replacedString;
	}

	function generateRepeatingBlockCodeReferredEntities($string, $ref_table, $ref_entity, $ref_columnByDatamodel, $ref_attribute, $ref_attributeFrom, $ref_attributeTo, $indexquery, $indexsubquery, $tag) {
		$replacedString = '';
		$modelKeyword = array (	'OTHERFOREIGNKEY',
							/*'OTHERFOREIGNTABLE',
							'OTHERFOREIGNCOLUMN',*/
							'TEXT',
							'IMAGE',
							'FILE',
							'URL',
							'NUMERIC',
							'BOOLEAN',
							'DATE',
							'TIME',
							'DATETIME'
						);
		for ($j = 0; $j < count($ref_table); $j++) {
			$replacedSubString = $string;
			$replacedSubString = str_replace('__REF_ENTITY__', $ref_entity[$j], $replacedSubString);
			$replacedSubString = str_replace('__REF_TABLE__', $ref_table[$j], $replacedSubString);
			$columnNameWithoutIndex = preg_replace('/..([0-9]+)../', '', $ref_columnByDatamodel['PRIMARY_KEY_'.$j][0]);
			$replacedSubString = str_replace('__REF_PRIMARY_KEY__', $columnNameWithoutIndex, $replacedSubString);
			$columnNameWithoutIndex = preg_replace('/..([0-9]+)../', '', $ref_columnByDatamodel['FOREIGN_KEY_'.$j][0]);
			$replacedSubString = str_replace('__REF_FOREIGN_KEY__', $columnNameWithoutIndex,
				str_replace('__REF_ATTRIBUTE_FOREIGN_KEY__', $ref_attribute[$ref_columnByDatamodel['FOREIGN_KEY_'.$j][0]], 
				str_replace('__REF_ATTRIBUTE_FOREIGN_KEY_FROM__', $ref_attributeFrom[$ref_columnByDatamodel['FOREIGN_KEY_'.$j][0]], 
				str_replace('__REF_ATTRIBUTE_FOREIGN_KEY_TO__', $ref_attributeTo[$ref_columnByDatamodel['FOREIGN_KEY_'.$j][0]], 
				$replacedSubString))));				
			for ($i = 0; $i < count($modelKeyword); $i++) {
				$replacedSubString = str_replace('__REF_COLUMN_'.$modelKeyword[$i].'__', '__REF_COLUMN_'.$modelKeyword[$i].'_'.$j.'__',
					str_replace('__REF_ATTRIBUTE_'.$modelKeyword[$i].'__', '__REF_ATTRIBUTE_'.$modelKeyword[$i].'_'.$j.'__',
					str_replace('__REF_ATTRIBUTE_'.$modelKeyword[$i].'_FROM__', '__REF_ATTRIBUTE_'.$modelKeyword[$i].'_'.$j.'_FROM__',
					str_replace('__REF_ATTRIBUTE_'.$modelKeyword[$i].'_TO__', '__REF_ATTRIBUTE_'.$modelKeyword[$i].'_'.$j.'_TO__', $replacedSubString))));
			}
			if ($replacedSubString != $string) {
				// only for / *<block* / and / *block>* /
				$replacedSubString = str_replace('/*<block*/', '', str_replace('/*block>*/', '', $replacedSubString));
				// only for query and error
				if ($indexquery != 0) {
					$replacedSubString = preg_replace('/<'.$tag.'_indexquery>[0-9]+<\/'.$tag.'_indexquery>/', '<'.$tag.'_indexquery>'.$indexquery.'</'.$tag.'_indexquery>', $replacedSubString);
					$indexquery++;
				}
				if ($indexsubquery != 0) {
					$replacedSubString = preg_replace('/<'.$tag.'_indexsubquery>[0-9]+<\/'.$tag.'_indexsubquery>/', '<'.$tag.'_indexsubquery>'.$indexsubquery.'</'.$tag.'_indexsubquery>', $replacedSubString);
					$replacedSubString = str_replace('/*block*/', '', $replacedSubString);
					$indexsubquery++;
				}
				// only for html
				if ($tag == 'page_html') {
					preg_match_all('/title=\"[0-9]+\"/', $replacedSubString, $titleMatches);
					$titleMatchesSectionId = preg_replace('/title=|"/', "", $titleMatches[0][0]);
					$generatedTitleId = '';
					for ($k = 0; $k <= $j; $k++) $generatedTitleId .= '0';
					$generatedTitleId .= $titleMatchesSectionId;
					$replacedSubString = preg_replace('/title="[0-9]+"/', 'title="'.$generatedTitleId.'"', $replacedSubString);
				}
				if ($tag == 'section') {
					$ltId = strpos($replacedSubString, '<section_id>') + 12;
					$gtId = strpos($replacedSubString, '</section_id>');
					if ($ltId >= 12 && $gtId > 0) $titleMatchesSectionId = substr($replacedSubString, $ltId, $gtId - $ltId);
					//echo $tag.':'.$titleMatchesSectionId.'###';
					$generatedTitleId = '';
					for ($k = 0; $k <= $j; $k++) $generatedTitleId .= '0';
					$generatedTitleId .= $titleMatchesSectionId;
					$replacedSubString = preg_replace('/<section_id>[0-9]+<\/section_id>/', '<section_id>'.$generatedTitleId.'</section_id>', $replacedSubString);
				}
				$replacedString .= $replacedSubString;
			}
		}
		return $replacedString;
	}

?>
