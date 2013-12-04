<?php
	if (isset($sectionIdInPage)) $_GET['id'] = $sectionIdInPage;
	else {
		header('Content-disposition: attachment; filename=section.xml');
		header("Content-type: text/plain"); 
		require_once("../init.php");
	}
	
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<error>Sorry, no section selected</error>';
	}
	else {
		$sectionID = 0;
		if (isset($_GET['id'])) $sectionID = $_GET['id'];
		else $sectionID = $_POST['id'];	
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_section.id sectionID, gm_section.name sectionName, gm_section.description sectionDescription, gm_section.command_pre_init_query sectionCommandPreInitQuery FROM gm_section, gm_application WHERE gm_application.id = gm_section.id_application AND gm_section.id = ".$sectionID);
		if ($section = $res->fetch_object()) {
			$appID = $section->appId;
			$appName = $section->appName;
			$sectionID = $section->sectionID;
			$sectionName = $section->sectionName;//str_replace("'","&rsquo;",$section->sectionName);
			if (!isset($sectionIdInPage)) header('Content-disposition: attachment; filename=Section_'.$sectionName.'.xml');
			$sectionDescription = $section->sectionDescription;
			$sectionCommandPreInitQuery = $section->sectionCommandPreInitQuery;
			$res->close();
?>
		<section>
			<section_id><?php echo $sectionID; ?></section_id>
			<section_name><?php echo $sectionName; ?></section_name>
			<section_description><?php echo $sectionDescription; ?></section_description>
			<section_idapplication><?php echo $appID; ?></section_idapplication>
			
			<section_commandpreinitquery><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionCommandPreInitQuery); ?>]]></section_commandpreinitquery>
			
			<section_initquerys>	
<?php
			$res2 = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_section_init_query WHERE id_section = ".$sectionID." ORDER BY index_query ASC, index_subquery ASC");
			while ($sectionInitQuery = $res2->fetch_object()) {
				$sectionInitQueryID = $sectionInitQuery->id;
				$sectionInitQueryCondition = $sectionInitQuery->condition_string;
				$sectionInitQuerySql = $sectionInitQuery->sql_string;
				$sectionInitQueryIndexQuery = $sectionInitQuery->index_query;
				$sectionInitQueryIndexSubquery = $sectionInitQuery->index_subquery;
?>
				<section_initquery>
					<section_initquery_id><?php echo $sectionInitQueryID; ?></section_initquery_id>
					<section_initquery_indexquery><?php echo $sectionInitQueryIndexQuery; ?></section_initquery_indexquery>
					<section_initquery_indexsubquery><?php echo $sectionInitQueryIndexSubquery; ?></section_initquery_indexsubquery>			
					<section_initquery_condition><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionInitQueryCondition); ?>]]></section_initquery_condition>						
					<section_initquery_sql><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionInitQuerySql); ?>]]></section_initquery_sql>
				</section_initquery>
<?php
			}
?>
			</section_initquerys>
			<section_views>
<?php
			$res2->close();
			$res3 = $DB->execute("SELECT id, condition_string, view, is_default, html, html_layout, width, height, command_pre_layout FROM gm_section_view WHERE id_section = ".$sectionID." ORDER BY id ASC");
			$actionsInSectionArray = array();
			while ($sectionView = $res3->fetch_object()) {
				$sectionViewID = $sectionView->id;
				$sectionViewCondition = $sectionView->condition_string;
				$sectionViewName = $sectionView->view;
				$sectionViewIsDefault = $sectionView->is_default;
				$sectionViewHtml = $sectionView->html;
				$sectionViewHtmlLayout = $sectionView->html_layout;
				$sectionViewWidth = $sectionView->width;
				$sectionViewHeight = $sectionView->height;
				$sectionViewCommandPreLayout = $sectionView->command_pre_layout;
				// get actions from section views
				$viewdoc = new DOMDocument();
				if ($sectionViewHtml != '') $viewdoc->loadHTML($sectionViewHtml);
				$viewnodes = $viewdoc->getElementsByTagName("button");
				foreach ($viewnodes as $viewnode) {
					if ($viewnode->getAttribute('type') == 'submit') {
						$attributeValue = explode(';', $viewnode->getAttribute('value'));
						//echo 'ooooo '.print_r($attributeValue);
						//echo 'ooooo '.$attributeValue[0];
						$res4 = $DB->execute("SELECT id FROM gm_action WHERE id_application = ".$appID." AND name = '".$viewnode->getAttribute('name')."' AND value = '".$attributeValue[0]."'");
						if ($actionView = $res4->fetch_object()) {
							if (isset($actionsArray)) {
								if (!in_array($actionView->id, $actionsArray)) $actionsArray[] = $actionView->id;
							}
							else {
								if (!in_array($actionView->id, $actionsInSectionArray)) $actionsInSectionArray[] = $actionView->id;
							}
						}
						$res4->close();
					}
				}
?>
				<section_view>
					<section_view_id><?php echo $sectionViewID; ?></section_view_id>
					<section_view_condition><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionViewCondition); ?>]]></section_view_condition>
					<section_view_name><?php echo $sectionViewName; ?></section_view_name>
					<section_view_isdefault><?php echo $sectionViewIsDefault; ?></section_view_isdefault>			
					<section_view_width><?php echo $sectionViewWidth; ?></section_view_width>
					<section_view_height><?php echo $sectionViewHeight; ?></section_view_height>
					<section_view_commandprelayout><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionViewCommandPreLayout); ?>]]></section_view_commandprelayout>						
					<section_view_html><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionViewHtml); ?>]]></section_view_html>						
					<section_view_htmllayout><![CDATA[<?php echo str_replace('\\', '\\\\', $sectionViewHtmlLayout); ?>]]></section_view_htmllayout>
				</section_view>
<?php
			}
			$res3->close();
?>
			</section_views>
<?php
			//print_r($actionsInSectionArray);
			if (!isset($actionsArray)) {
?>
			<actions>
<?php
				foreach ($actionsInSectionArray as $actionIdInPage) {
					//echo $actionIdInPage." ";
					include("../action/action_xml_export.php");
				}
?>
			</actions>
<?php
			}
?>
		</section>
<?php
		}
		else echo '<error>Sorry, no section found</error>';
	}
?>
