<?php
	if (isset($actionIdInPage)) $_GET['id'] = $actionIdInPage;
	else {
		header('Content-disposition: attachment; filename=action.xml');
		header("Content-type: text/plain"); 
		require_once("../init.php");
	}
	
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<error>Sorry, no action selected</error>';
	}
	else {
		$actionID = 0;
		if (isset($_GET['id'])) $actionID = $_GET['id'];
		else $actionID = $_POST['id'];	
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_action.id actionID, gm_action.name actionName, gm_action.value actionValue, gm_action.command actionCommand, gm_action.command_on_success actionCommandOnSuccess, gm_action.command_on_fail actionCommandOnFail, gm_action.next_page_on_success actionNextPageOnSuccess, gm_action.next_page_on_fail actionNextPageOnFail FROM gm_action, gm_application WHERE gm_application.id = gm_action.id_application AND gm_action.id = ".$actionID);
		if ($action = $res->fetch_object()) {
			$appID = $action->appId;
			$appName = $action->appName;
			$actionID = $action->actionID;
			$actionName = $action->actionName;//str_replace("'","&rsquo;",$action->actionName);
			$actionValue = $action->actionValue;
			if (!isset($actionIdInPage)) header('Content-disposition: attachment; filename=Action_'.$actionName.'_-_'.$actionValue.'.xml');
			$actionCommand = $action->actionCommand;
			$actionCommandOnSuccess = $action->actionCommandOnSuccess;
			$actionCommandOnFail = $action->actionCommandOnFail;
			$actionNextPageOnSuccess = $action->actionNextPageOnSuccess;
			$actionNextPageOnFail = $action->actionNextPageOnFail;
			$res->close();
?>
				<action>
					<action_id><?php echo $actionID; ?></action_id>
					<action_name><?php echo $actionName; ?></action_name>
					<action_value><?php echo $actionValue; ?></action_value>
					<action_idapplication><?php echo $appID; ?></action_idapplication>
					
					<action_command><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCommand); ?>]]></action_command>

					<action_commandonsuccess><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCommandOnSuccess); ?>]]></action_commandonsuccess>

					<action_commandonfail><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCommandOnFail); ?>]]></action_commandonfail>

					<action_nextpageonsuccess><![CDATA[<?php echo str_replace('\\', '\\\\', $actionNextPageOnSuccess); ?>]]></action_nextpageonsuccess>

					<action_nextpageonfail><![CDATA[<?php echo str_replace('\\', '\\\\', $actionNextPageOnFail); ?>]]></action_nextpageonfail>			
					
					<action_checkquerys>	
<?php
			$res2 = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_check_query WHERE id_action = ".$actionID." ORDER BY index_query ASC, index_subquery ASC");
			while ($actionCheckQuery = $res2->fetch_object()) {
				$actionCheckQueryID = $actionCheckQuery->id;
				$actionCheckQueryCondition = $actionCheckQuery->condition_string;
				$actionCheckQuerySql = $actionCheckQuery->sql_string;
				$actionCheckQueryIndexQuery = $actionCheckQuery->index_query;
				$actionCheckQueryIndexSubquery = $actionCheckQuery->index_subquery;
?>
						<action_checkquery>
							<action_checkquery_id><?php echo $actionCheckQueryID; ?></action_checkquery_id>
							<action_checkquery_indexquery><?php echo $actionCheckQueryIndexQuery; ?></action_checkquery_indexquery>
							<action_checkquery_indexsubquery><?php echo $actionCheckQueryIndexSubquery; ?></action_checkquery_indexsubquery>			
							<action_checkquery_condition><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCheckQueryCondition); ?>]]></action_checkquery_condition>						
							<action_checkquery_sql><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCheckQuerySql); ?>]]></action_checkquery_sql>
						</action_checkquery>
<?php
			}
?>
					</action_checkquerys>
					<action_checkfields>	
<?php
			$res2->close();
			$res3 = $DB->execute("SELECT id, condition_string, error FROM gm_action_check_field WHERE id_action = ".$actionID." ORDER BY id ASC");
			while ($actionCheckField = $res3->fetch_object()) {
				$actionCheckFieldID = $actionCheckField->id;
				$actionCheckFieldCondition = $actionCheckField->condition_string;
				$actionCheckFieldError = $actionCheckField->error;
?>
						<action_checkfield>
							<action_checkfield_id><?php echo $actionCheckFieldID; ?></action_checkfield_id>
							<action_checkfield_condition><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCheckFieldCondition); ?>]]></action_checkfield_condition>						
							<action_checkfield_error><![CDATA[<?php echo str_replace('\\', '\\\\', $actionCheckFieldError); ?>]]></action_checkfield_error>
						</action_checkfield>
<?php
			}
?>
					</action_checkfields>
					<action_queryonsuccesss>	
<?php
			$res3->close();
			$res4 = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_query_success WHERE id_action = ".$actionID." ORDER BY index_query ASC, index_subquery ASC");
			while ($actionQueryOnSuccess = $res4->fetch_object()) {
				$actionQueryOnSuccessID = $actionQueryOnSuccess->id;
				$actionQueryOnSuccessCondition = $actionQueryOnSuccess->condition_string;
				$actionQueryOnSuccessSql = $actionQueryOnSuccess->sql_string;
				$actionQueryOnSuccessIndexQuery = $actionQueryOnSuccess->index_query;
				$actionQueryOnSuccessIndexSubquery = $actionQueryOnSuccess->index_subquery;
?>
						<action_queryonsuccess>
							<action_queryonsuccess_id><?php echo $actionQueryOnSuccessID; ?></action_queryonsuccess_id>
							<action_queryonsuccess_indexquery><?php echo $actionQueryOnSuccessIndexQuery; ?></action_queryonsuccess_indexquery>
							<action_queryonsuccess_indexsubquery><?php echo $actionQueryOnSuccessIndexSubquery; ?></action_queryonsuccess_indexsubquery>			
							<action_queryonsuccess_condition><![CDATA[<?php echo str_replace('\\', '\\\\', $actionQueryOnSuccessCondition); ?>]]></action_queryonsuccess_condition>						
							<action_queryonsuccess_sql><![CDATA[<?php echo str_replace('\\', '\\\\', $actionQueryOnSuccessSql); ?>]]></action_queryonsuccess_sql>
						</action_queryonsuccess>
<?php
			}
?>
					</action_queryonsuccesss>
					<action_queryonfails>	
<?php
			$res4->close();
			$res5 = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_query_fail WHERE id_action = ".$actionID." ORDER BY index_query ASC, index_subquery ASC");
			while ($actionQueryOnFail = $res5->fetch_object()) {
				$actionQueryOnFailID = $actionQueryOnFail->id;
				$actionQueryOnFailCondition = $actionQueryOnFail->condition_string;
				$actionQueryOnFailSql = $actionQueryOnFail->sql_string;
				$actionQueryOnFailIndexQuery = $actionQueryOnFail->index_query;
				$actionQueryOnFailIndexSubquery = $actionQueryOnFail->index_subquery;
?>
						<action_queryonfail>
							<action_queryonfail_id><?php echo $actionQueryOnFailID; ?></action_queryonfail_id>
							<action_queryonfail_indexquery><?php echo $actionQueryOnFailIndexQuery; ?></action_queryonfail_indexquery>
							<action_queryonfail_indexsubquery><?php echo $actionQueryOnFailIndexSubquery; ?></action_queryonfail_indexsubquery>			
							<action_queryonfail_condition><![CDATA[<?php echo str_replace('\\', '\\\\', $actionQueryOnFailCondition); ?>]]></action_queryonfail_condition>						
							<action_queryonfail_sql><![CDATA[<?php echo str_replace('\\', '\\\\', $actionQueryOnFailSql); ?>]]></action_queryonfail_sql>
						</action_queryonfail>
<?php
			}
?>
					</action_queryonfails>
				</action>
<?php
			$res5->close();
		}
		else echo '<error>Sorry, no action found</error>';
	}
?>
