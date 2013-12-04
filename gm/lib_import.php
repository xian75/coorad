<?php

function isXmlActionCompliant($xml) {
	$doc = new DOMDocument();
	if ($xml != '') $doc->loadXML($xml);
	else return false;
	return $doc->schemaValidate('../xsd/action.xsd');
}

function isXmlSectionCompliant($xml) {
	$doc = new DOMDocument();
	if ($xml != '') $doc->loadXML($xml);
	else return false;
	return $doc->schemaValidate('../xsd/section.xsd');
}

function isXmlPageCompliant($xml) {
	$doc = new DOMDocument();
	if ($xml != '') $doc->loadXML($xml);
	else return false;
	return $doc->schemaValidate('../xsd/page.xsd');
}

function importActionFromXml($DB, $node, $appID, $timestamp, $reuse=false) {
	//$appID = '';
	//$appName = '';
	//$actionID = '';
	$actionName = '';
	$actionValue = '';
	$actionCommand = '';
	$actionCommandOnSuccess = '';
	$actionCommandOnFail = '';
	$actionNextPageOnSuccess = '';
	$actionNextPageOnFail = '';
	//$actionCheckQueryID = array();
	$actionCheckQueryCondition = array();
	$actionCheckQuerySql = array();
	$actionCheckQueryIndexQuery = array();
	$actionCheckQueryIndexSubquery = array();
	//$actionCheckFieldID = array();
	$actionCheckFieldCondition = array();
	$actionCheckFieldError = array();
	//$actionQueryOnSuccessID = array();
	$actionQueryOnSuccessCondition = array();
	$actionQueryOnSuccessSql = array();
	$actionQueryOnSuccessIndexQuery = array();
	$actionQueryOnSuccessIndexSubquery = array();
	//$actionQueryOnFailID = array();
	$actionQueryOnFailCondition = array();
	$actionQueryOnFailSql = array();
	$actionQueryOnFailIndexQuery = array();
	$actionQueryOnFailIndexSubquery = array();
	
	$childNodes = $node->childNodes;
	if ($childNodes != null) foreach ($childNodes as $childNode) {
		//if ($childNode->nodeName == 'action_id') $actionID = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_name') $actionName = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_value') $actionValue = $childNode->nodeValue;
		//if ($childNode->nodeName == 'action_idapplication') $appID = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_command') $actionCommand = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_commandonsuccess') $actionCommandOnSuccess = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_commandonfail') $actionCommandOnFail = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_nextpageonsuccess') $actionNextPageOnSuccess = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_nextpageonfail') $actionNextPageOnFail = $childNode->nodeValue;
		if ($childNode->nodeName == 'action_checkquerys') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				$subSubChildNodes = $subChildNode->childNodes;
				if ($subSubChildNodes != null) foreach ($subSubChildNodes as $subSubChildNode) {
					//if ($subSubChildNode->nodeName == 'action_checkquery_id') $actionCheckQueryID[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_checkquery_condition') $actionCheckQueryCondition[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_checkquery_sql') $actionCheckQuerySql[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_checkquery_indexquery') $actionCheckQueryIndexQuery[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_checkquery_indexsubquery') $actionCheckQueryIndexSubquery[] = $subSubChildNode->nodeValue;
				}
			}
		}
		if ($childNode->nodeName == 'action_checkfields') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				$subSubChildNodes = $subChildNode->childNodes;
				if ($subSubChildNodes != null) foreach ($subSubChildNodes as $subSubChildNode) {
					//if ($subSubChildNode->nodeName == 'action_checkfield_id') $actionCheckFieldID[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_checkfield_condition') $actionCheckFieldCondition[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_checkfield_error') $actionCheckFieldError[] = $subSubChildNode->nodeValue;					
				}
			}
		}
		if ($childNode->nodeName == 'action_queryonsuccesss') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				$subSubChildNodes = $subChildNode->childNodes;
				if ($subSubChildNodes != null) foreach ($subSubChildNodes as $subSubChildNode) {
					//if ($subSubChildNode->nodeName == 'action_queryonsuccess_id') $actionQueryOnSuccessID[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonsuccess_condition') $actionQueryOnSuccessCondition[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonsuccess_sql') $actionQueryOnSuccessSql[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonsuccess_indexquery') $actionQueryOnSuccessIndexQuery[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonsuccess_indexsubquery') $actionQueryOnSuccessIndexSubquery[] = $subSubChildNode->nodeValue;
				}
			}
		}
		if ($childNode->nodeName == 'action_queryonfails') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				$subSubChildNodes = $subChildNode->childNodes;
				if ($subSubChildNodes != null) foreach ($subSubChildNodes as $subSubChildNode) {
					//if ($subSubChildNode->nodeName == 'action_queryonfail_id') $actionQueryOnFailID[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonfail_condition') $actionQueryOnFailCondition[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonfail_sql') $actionQueryOnFailSql[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonfail_indexquery') $actionQueryOnFailIndexQuery[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'action_queryonfail_indexsubquery') $actionQueryOnFailIndexSubquery[] = $subSubChildNode->nodeValue;
				}
			}
		}
	}
	
	$existsActionID = 0;
	$existsRes = $DB->execute("SELECT gm_action.id existsId FROM gm_action WHERE gm_action.id_application = ".$appID." AND gm_action.name = '".$actionName."' AND gm_action.value = '".$actionValue."'");
	if ($existsAction = $existsRes->fetch_object()) {
		$existsActionID = $existsAction->existsId;
		$existsRes->close();
	}
	if ($existsActionID != 0 && !$reuse) $actionName = $actionName." ".$timestamp;
	if ($existsActionID != 0 && $reuse) return '[REUSED] '.$actionName.' = '.$actionValue; //'';
	else {
		$sql = "INSERT INTO gm_action(name, value, command, command_on_success, command_on_fail, next_page_on_success, next_page_on_fail, id_application) VALUES('".str_replace("'","''",$actionName)."','".str_replace("'","''",$actionValue)."','".str_replace("'","''",$actionCommand)."','".str_replace("'","''",$actionCommandOnSuccess)."','".str_replace("'","''",$actionCommandOnFail)."','".str_replace("'","''",$actionNextPageOnSuccess)."','".str_replace("'","''",$actionNextPageOnFail)."',".$appID.")";
		//echo $sql;
		$DB->execute($sql);
		$insertedId = $DB->getInsertedId();
		//echo $insertedId;

		$sql = "INSERT INTO gm_action_check_query(condition_string, sql_string, index_query, index_subquery, id_action) VALUES";
		for ($i = 0; $i < count($actionCheckQueryCondition); $i++) {
			$sql .= "('".str_replace("'","''",$actionCheckQueryCondition[$i])."',";
			$sql .= "'".str_replace("'","''",$actionCheckQuerySql[$i])."',";
			$sql .= $actionCheckQueryIndexQuery[$i].",";
			$sql .= $actionCheckQueryIndexSubquery[$i].",";
			$sql .= $insertedId."),";
		}
		$sql = substr($sql, 0, -1);
		//echo $sql;
		if (count($actionCheckQueryCondition) > 0) $DB->execute($sql);
		
		$sql = "INSERT INTO gm_action_check_field(condition_string, error, id_action) VALUES";
		for ($i = 0; $i < count($actionCheckFieldCondition); $i++) {
			$sql .= "('".str_replace("'","''",$actionCheckFieldCondition[$i])."',";
			$sql .= "'".str_replace("'","''",$actionCheckFieldError[$i])."',";
			$sql .= $insertedId."),";
		}
		$sql = substr($sql, 0, -1);
		//echo $sql;
		if (count($actionCheckFieldCondition) > 0) $DB->execute($sql);

		$sql = "INSERT INTO gm_action_query_success(condition_string, sql_string, index_query, index_subquery, id_action) VALUES";
		for ($i = 0; $i < count($actionQueryOnSuccessCondition); $i++) {
			$sql .= "('".str_replace("'","''",$actionQueryOnSuccessCondition[$i])."',";
			$sql .= "'".str_replace("'","''",$actionQueryOnSuccessSql[$i])."',";
			$sql .= $actionQueryOnSuccessIndexQuery[$i].",";
			$sql .= $actionQueryOnSuccessIndexSubquery[$i].",";
			$sql .= $insertedId."),";
		}
		$sql = substr($sql, 0, -1);
		//echo $sql;
		if (count($actionQueryOnSuccessCondition) > 0) $DB->execute($sql);
		
		$sql = "INSERT INTO gm_action_query_fail(condition_string, sql_string, index_query, index_subquery, id_action) VALUES";
		for ($i = 0; $i < count($actionQueryOnFailCondition); $i++) {
			$sql .= "('".str_replace("'","''",$actionQueryOnFailCondition[$i])."',";
			$sql .= "'".str_replace("'","''",$actionQueryOnFailSql[$i])."',";
			$sql .= $actionQueryOnFailIndexQuery[$i].",";
			$sql .= $actionQueryOnFailIndexSubquery[$i].",";
			$sql .= $insertedId."),";
		}
		$sql = substr($sql, 0, -1);
		//echo $sql;
		if (count($actionQueryOnFailCondition) > 0) $DB->execute($sql);

		return $actionName.' = '.$actionValue;		
	}
}

function importSectionFromXml($DB, $node, $appID, $timestamp, $returnSectionId, $reuse=false) {
	//$appID = '';
	//$appName = '';
	//$sectionID = '';
	$sectionName = '';
	$sectionDescription = '';
	$sectionCommandPreInitQuery = '';
	//$sectionInitQueryID = array();
	$sectionInitQueryCondition = array();
	$sectionInitQuerySql = array();
	$sectionInitQueryIndexQuery = array();
	$sectionInitQueryIndexSubquery = array();
	//$sectionViewID = array();
	$sectionViewCondition = array();
	$sectionViewName = array();
	$sectionViewIsDefault = array();
	$sectionViewHtml = array();
	$sectionViewHtmlLayout = array();
	$sectionViewWidth = array();
	$sectionViewHeight = array();
	$sectionViewCommandPreLayout = array();
	$actionsInSectionArray = array();
	$allElementsImported = array();
	$allElementsImported[0] = '';
	
	$childNodes = $node->childNodes;
	if ($childNodes != null) foreach ($childNodes as $childNode) {
		if ($childNode->nodeName == 'section_id') $sectionID = $childNode->nodeValue;
		if ($childNode->nodeName == 'section_name') {
			$sectionName = $childNode->nodeValue;
			$allElementsImported[0] = $sectionName;
		}
		if ($childNode->nodeName == 'section_description') $sectionDescription = $childNode->nodeValue;
		//if ($childNode->nodeName == 'section_idapplication') $appID = $childNode->nodeValue;
		if ($childNode->nodeName == 'section_commandpreinitquery') $sectionCommandPreInitQuery = $childNode->nodeValue;
		if ($childNode->nodeName == 'section_initquerys') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				$subSubChildNodes = $subChildNode->childNodes;
				if ($subSubChildNodes != null) foreach ($subSubChildNodes as $subSubChildNode) {
					//if ($subSubChildNode->nodeName == 'section_initquery_id') $sectionInitQueryID[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_initquery_condition') $sectionInitQueryCondition[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_initquery_sql') $sectionInitQuerySql[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_initquery_indexquery') $sectionInitQueryIndexQuery[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_initquery_indexsubquery') $sectionInitQueryIndexSubquery[] = $subSubChildNode->nodeValue;
				}
			}
		}
		if ($childNode->nodeName == 'section_views') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				$subSubChildNodes = $subChildNode->childNodes;
				if ($subSubChildNodes != null) foreach ($subSubChildNodes as $subSubChildNode) {
					//if ($subSubChildNode->nodeName == 'section_view_id') $sectionViewID[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_condition') $sectionViewCondition[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_name') $sectionViewName[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_isdefault') $sectionViewIsDefault[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_width') $sectionViewWidth[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_height') $sectionViewHeight[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_commandprelayout') $sectionViewCommandPreLayout[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_html') $sectionViewHtml[] = $subSubChildNode->nodeValue;					
					if ($subSubChildNode->nodeName == 'section_view_htmllayout') $sectionViewHtmlLayout[] = $subSubChildNode->nodeValue;					
				}
			}
		}
		
		// ACTIONS della SECTION		
		if ($childNode->nodeName == 'actions') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) if ($subChildNode->nodeName == 'action') {
				$newname = importActionFromXml($DB, $subChildNode, $appID, $timestamp, $reuse);
				/*if ($newname != '')*/ $allElementsImported[] = $newname;
			}
		}
	}

	$existsSectionID = 0;
	$existsRes = $DB->execute("SELECT gm_section.id existsId FROM gm_section WHERE gm_section.id_application = ".$appID." AND gm_section.name = '".$sectionName."'");
	if ($existsSection = $existsRes->fetch_object()) {
		$existsSectionID = $existsSection->existsId;
		$existsRes->close();
	}
	if ($existsSectionID != 0 && !$reuse) {
		$sectionName = $sectionName." ".$timestamp;
		$allElementsImported[0] = $sectionName;
	}
	if ($existsSectionID != 0 && $reuse) {
		$sectionName = '[REUSED] '.$sectionName; //'';
		$allElementsImported[0] = $sectionName;
		if ($returnSectionId) {
			$allElementsImported[] = $sectionID; // old ID section
			$allElementsImported[] = $existsSectionID; // reuse existant ID section
		}
	}
	else {
		$sql = "INSERT INTO gm_section(name, description, command_pre_init_query, id_application) VALUES('".str_replace("'","''",$sectionName)."','".str_replace("'","''",$sectionDescription)."','".str_replace("'","''",$sectionCommandPreInitQuery)."',".$appID.")";
		//echo $sql;
		$DB->execute($sql);
		$insertedId = $DB->getInsertedId();
		//echo $insertedId;

		$sql = "INSERT INTO gm_section_init_query(condition_string, sql_string, index_query, index_subquery, id_section) VALUES";
		for ($i = 0; $i < count($sectionInitQueryCondition); $i++) {
			$sql .= "('".str_replace("'","''",$sectionInitQueryCondition[$i])."',";
			$sql .= "'".str_replace("'","''",$sectionInitQuerySql[$i])."',";
			$sql .= $sectionInitQueryIndexQuery[$i].",";
			$sql .= $sectionInitQueryIndexSubquery[$i].",";
			$sql .= $insertedId."),";
		}
		$sql = substr($sql, 0, -1);
		//echo $sql;
		if (count($sectionInitQueryCondition) > 0) $DB->execute($sql);
		
		$sql = "INSERT INTO gm_section_view(condition_string, view, is_default, html, html_layout, width, height, command_pre_layout, id_section) VALUES";
		for ($i = 0; $i < count($sectionViewCondition); $i++) {
			$sql .= "('".str_replace("'","''",$sectionViewCondition[$i])."',";
			$sql .= "'".str_replace("'","''",$sectionViewName[$i])."',";
			$sql .= $sectionViewIsDefault[$i].",";
			$sql .= "'".str_replace("'","''",$sectionViewHtml[$i])."',";
			$sql .= "'".str_replace("'","''",$sectionViewHtmlLayout[$i])."',";
			$sql .= $sectionViewWidth[$i].",";
			$sql .= $sectionViewHeight[$i].",";
			$sql .= "'".str_replace("'","''",$sectionViewCommandPreLayout[$i])."',";
			$sql .= $insertedId."),";
		}
		$sql = substr($sql, 0, -1);
		//echo $sql;
		if (count($sectionViewCondition) > 0) $DB->execute($sql);

		if ($returnSectionId) {
			$allElementsImported[] = $sectionID; // old ID section
			$allElementsImported[] = $insertedId; // new imported ID section
		}
	}
	
	return $allElementsImported;
}

function importPageFromXml($DB, $node, $appID, $timestamp, $reuse=false) {
	//$appID = '';
	//$appName = '';
	//$pageID = '';
	$pageName = '';
	$pageDescription = '';
	$pageWidth = '';
	$pageHeight = '';
	$pageAlign = '';
	$pageIsTemplate = '';
	$pageUseTemplate = '';
	//$pageIsHome = '';
	$pageIdCss = 0;
	$pageNameCss = '';
	$pageDescriptionCss = '';
	$pageCodeCss = '';
	$pageIdJavascript = 0;
	$pageNameJavascript = '';
	$pageDescriptionJavascript = '';
	$pageCodeJavascript = '';
	$pageHtml = '';
	$pageCommand = '';
	$allElementsImported = array();
	$allElementsImported[0] = '';
	$allElementsImported[1] = '';
	
	$childNodes = $node->childNodes;
	if ($childNodes != null) foreach ($childNodes as $childNode) {
		//if ($childNode->nodeName == 'page_id') $pageID = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_name') {
			$pageName = $childNode->nodeValue;
			$allElementsImported[0] = $pageName;
		}
		if ($childNode->nodeName == 'page_description') $pageDescription = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_width') $pageWidth = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_height') $pageHeight = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_align') $pageAlign = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_istemplate') $pageIsTemplate = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_usetemplate') $pageUseTemplate = $childNode->nodeValue;
		//if ($childNode->nodeName == 'page_ishome') $pageIsHome = $childNode->nodeValue;
		
		// CSS
		if ($childNode->nodeName == 'page_css') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				if ($subChildNode->nodeName == 'page_idcss') $pageIdCss = $subChildNode->nodeValue;
				if ($subChildNode->nodeName == 'page_namecss') $pageNameCss = $subChildNode->nodeValue;
				if ($subChildNode->nodeName == 'page_descriptioncss') $pageDescriptionCss = $subChildNode->nodeValue;
				if ($subChildNode->nodeName == 'page_codecss') $pageCodeCss = $subChildNode->nodeValue;
			}
			if ($pageIdCss != 0) {
				$existsCssID = 0;
				$existsRes = $DB->execute("SELECT gm_css.id existsId FROM gm_css WHERE gm_css.id_application = ".$appID." AND gm_css.name = '".$pageNameCss."'");
				if ($existsCss = $existsRes->fetch_object()) {
					$existsCssID = $existsCss->existsId;
					$existsRes->close();
				}
				if ($existsCssID != 0 && !$reuse) $pageNameCss = $pageNameCss." ".$timestamp;
				if ($existsCssID != 0 && $reuse) {
					$pageIdCss = $existsCssID;
					$pageNameCss = '[REUSED] '.$pageNameCss;
				}
				else {
					$sql = "INSERT INTO gm_css(name, description, css, id_application) VALUES('".str_replace("'","''",$pageNameCss)."','".str_replace("'","''",$pageDescriptionCss)."','".str_replace("'","''",$pageCodeCss)."',".$appID.")";
					//echo $sql;
					$DB->execute($sql);
					$pageIdCss = $DB->getInsertedId();
				}
				$allElementsImported[] = 'C'.$pageNameCss;
			}
		}
		
		// Javascript
		if ($childNode->nodeName == 'page_javascript') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) {
				if ($subChildNode->nodeName == 'page_idjavascript') $pageIdJavascript = $subChildNode->nodeValue;
				if ($subChildNode->nodeName == 'page_namejavascript') $pageNameJavascript = $subChildNode->nodeValue;
				if ($subChildNode->nodeName == 'page_descriptionjavascript') $pageDescriptionJavascript = $subChildNode->nodeValue;
				if ($subChildNode->nodeName == 'page_codejavascript') $pageCodeJavascript = $subChildNode->nodeValue;
			}
			if ($pageIdJavascript != 0) {
				$existsJavascriptID = 0;
				$existsRes = $DB->execute("SELECT gm_javascript.id existsId FROM gm_javascript WHERE gm_javascript.id_application = ".$appID." AND gm_javascript.name = '".$pageNameJavascript."'");
				if ($existsJavascript = $existsRes->fetch_object()) {
					$existsJavascriptID = $existsJavascript->existsId;
					$existsRes->close();
				}
				if ($existsJavascriptID != 0 && !$reuse) $pageNameJavascript = $pageNameJavascript." ".$timestamp;
				if ($existsJavascriptID != 0 && $reuse) {
					$pageIdJavascript = $existsJavascriptID;
					$pageNameJavascript = '[REUSED] '.$pageNameJavascript;
				}
				else {
					$sql = "INSERT INTO gm_javascript(name, description, javascript, id_application) VALUES('".str_replace("'","''",$pageNameJavascript)."','".str_replace("'","''",$pageDescriptionJavascript)."','".str_replace("'","''",$pageCodeJavascript)."',".$appID.")";
					//echo $sql;
					$DB->execute($sql);
					$pageIdJavascript = $DB->getInsertedId();
				}
				$allElementsImported[] = 'J'.$pageNameJavascript;
			}
		}

		//if ($childNode->nodeName == 'page_idapplication') $appID = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_html') $pageHtml = $childNode->nodeValue;
		if ($childNode->nodeName == 'page_command') $pageCommand = $childNode->nodeValue;

		// SECTIONS della PAGE		
		if ($childNode->nodeName == 'sections') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) if ($subChildNode->nodeName == 'section') {
				$newname = importSectionFromXml($DB, $subChildNode, $appID, $timestamp, true, $reuse);
				$allElementsImported[] = 'S'.$newname[0];
				$allElementsImported[] = $newname[1]; // old ID section
				$allElementsImported[] = $newname[2]; // new imported or old existant ID section
			}
		}
		
		// ACTIONS della PAGE		
		if ($childNode->nodeName == 'actions') {
			$subChildNodes = $childNode->childNodes;
			if ($subChildNodes != null) foreach ($subChildNodes as $subChildNode) if ($subChildNode->nodeName == 'action') {
				$newname = importActionFromXml($DB, $subChildNode, $appID, $timestamp, $reuse);
				/*if ($newname != '')*/ $allElementsImported[] = 'A'.$newname;
			}
		}
	}



	$existsPageID = 0;
	$existsRes = $DB->execute("SELECT gm_page.id existsId FROM gm_page WHERE gm_page.id_application = ".$appID." AND gm_page.name = '".$pageName."'");
	if ($existsPage = $existsRes->fetch_object()) {
		$existsPageID = $existsPage->existsId;
		$existsRes->close();
	}
	if ($existsPageID != 0 && !$reuse) {
		$pageName = $pageName." ".$timestamp;
		$allElementsImported[0] = $pageName;
	}
	if ($existsPageID != 0 && $reuse) {
		$pageName = '[REUSED] '.$pageName;
		$allElementsImported[0] = $pageName;
		$allElementsImported[1] = $existsPageID;
	}
	else {
		$sql = "INSERT INTO gm_page(name, description, html, width, height, align, command, is_template, use_template, id_css, id_javascript, id_application) VALUES('".str_replace("'","''",$pageName)."','".str_replace("'","''",$pageDescription)."','".str_replace("'","''",$pageHtml)."',".$pageWidth.",".$pageHeight.",'".str_replace("'","''",$pageAlign)."','".str_replace("'","''",$pageCommand)."',".$pageIsTemplate.",'".$pageUseTemplate."',".$pageIdCss.",".$pageIdJavascript.",".$appID.")";
		//echo $sql;
		$DB->execute($sql);
		$insertedId = $DB->getInsertedId();
		//echo $insertedId;
		$allElementsImported[1] = $insertedId;
	}
	
	return $allElementsImported;
}
?>