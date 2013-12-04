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
		$res = $DB->execute("SELECT name, context_path FROM gm_application WHERE id = ".$appID);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$appName = $row->name;
				$appContextPath = $row->context_path;
			}
		}
		$res->close();

		
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			$i = 1;
			$j = 1;
			while (isset($_POST['i_'.$i.'_1'])) {
				//echo '<div>Query '.$i.'</div>';
				while (isset($_POST['i_'.$i.'_'.$j])) {
					//echo '<div>'.$i.'_'.$j.' -&gt; id='.$_POST['i_'.$i.'_'.$j].' name='.$_POST['n_'.$i.'_'.$j].' value='.$_POST['d_'.$i.'_'.$j];
					if ($_POST['i_'.$i.'_'.$j] > 0) {
						// UPDATE action
						$DB->execute("UPDATE gm_action SET name='".str_replace("'","''",$_POST['n_'.$i.'_'.$j])."', value='".str_replace("'","''",$_POST['d_'.$i.'_'.$j])."' WHERE id=".$_POST['i_'.$i.'_'.$j]);
					}
					else {
						// INSERT action
						$DB->execute("INSERT INTO gm_action(name, value, command, command_on_success, command_on_fail, id_application) VALUES('".str_replace("'","''",$_POST['n_'.$i.'_'.$j])."','".str_replace("'","''",$_POST['d_'.$i.'_'.$j])."','','','',".$appID.")");
						//$insertedId = $DB->getInsertedId();
						//$DB->execute("INSERT INTO gm_action_view(id_section, is_default) VALUES(".$insertedId.",true)");
					}
					//echo '</div>';
					$j = $j + 1;
				}
				$j = 1;
				$i = $i + 1;
			}
			// DELETE action
			if (isset($_POST['idstodelete'])) {
				//echo $_POST['idstodelete'];
				$idstodelete = '('.substr($_POST['idstodelete'], 0, strlen($_POST['idstodelete']) - 1).')';
				$DB->execute("DELETE FROM gm_action WHERE id IN ".$idstodelete);
				//echo "DELETE FROM gm_action WHERE id IN ".$idstodelete;
			}
		}
		if (isset($_GET['duplicateid']) && $authorized) {
			$actionnameArrayForDuplication = array();
			$res = $DB->execute("SELECT name FROM gm_action WHERE id_application = ".$appID);
			if($res){
				while ($row = $res->fetch_object()){
					$actionnameArrayForDuplication[] = $row->name;
				}
				$res->close();
			}
			$duplicate_id = $_GET['duplicateid'];
			$res = $DB->execute("SELECT name, value, command, command_on_success, command_on_fail, next_page_on_success, next_page_on_fail, id_application FROM gm_action WHERE id = ".$duplicate_id);
			if ($res) {
				$duplicate_sql = "INSERT INTO gm_action(name, value, command, command_on_success, command_on_fail, next_page_on_success, next_page_on_fail, id_application) VALUES(";
				if ($row = $res->fetch_object()) {
					$duplicate_name = $row->name;
					$r = 0;
					while (in_array($duplicate_name, $actionnameArrayForDuplication)) {
						$r++;
						$duplicate_name = $row->name."_copy".$r;
					}
					$duplicate_sql .= "'".str_replace("'","''",$duplicate_name)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->value)."',";
					$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->command))."',";
					$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->command_on_success))."',";
					$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->command_on_fail))."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->next_page_on_success)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->next_page_on_fail)."',";
					$duplicate_sql .= $row->id_application.")";
				}
				$res->close();
				//echo $duplicate_sql;
				$DB->execute($duplicate_sql);
				$insertedId = $DB->getInsertedId();
				
				// gm_action_check_field
				$res = $DB->execute("SELECT condition_string, error FROM gm_action_check_field WHERE id_action = ".$duplicate_id);
				if ($res) {
					$duplicate_sql = "INSERT INTO gm_action_check_field(condition_string, error, id_action) VALUES";
					while ($row = $res->fetch_object()) {
						$duplicate_sql .= "('".str_replace("\\","\\\\",str_replace("'","''",$row->condition_string))."',";
						$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->error))."',";
						$duplicate_sql .= $insertedId."),";
					}
					$res->close();
					$duplicate_sql = substr($duplicate_sql, 0, -1);
					//echo $duplicate_sql;
					$DB->execute($duplicate_sql);
				}

				// gm_action_check_query
				$res = $DB->execute("SELECT condition_string, sql_string, index_query, index_subquery FROM gm_action_check_query WHERE id_action = ".$duplicate_id);
				if ($res) {
					$duplicate_sql = "INSERT INTO gm_action_check_query(condition_string, sql_string, index_query, index_subquery, id_action) VALUES";
					while ($row = $res->fetch_object()) {
						$duplicate_sql .= "('".str_replace("\\","\\\\",str_replace("'","''",$row->condition_string))."',";
						$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->sql_string))."',";
						$duplicate_sql .= $row->index_query.",";
						$duplicate_sql .= $row->index_subquery.",";
						$duplicate_sql .= $insertedId."),";
					}
					$res->close();
					$duplicate_sql = substr($duplicate_sql, 0, -1);
					//echo $duplicate_sql;
					$DB->execute($duplicate_sql);
				}
				
				// gm_action_query_success
				$res = $DB->execute("SELECT condition_string, sql_string, index_query, index_subquery FROM gm_action_query_success WHERE id_action = ".$duplicate_id);
				if ($res) {
					$duplicate_sql = "INSERT INTO gm_action_query_success(condition_string, sql_string, index_query, index_subquery, id_action) VALUES";
					while ($row = $res->fetch_object()) {
						$duplicate_sql .= "('".str_replace("\\","\\\\",str_replace("'","''",$row->condition_string))."',";
						$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->sql_string))."',";
						$duplicate_sql .= $row->index_query.",";
						$duplicate_sql .= $row->index_subquery.",";
						$duplicate_sql .= $insertedId."),";
					}
					$res->close();
					$duplicate_sql = substr($duplicate_sql, 0, -1);
					//echo $duplicate_sql;
					$DB->execute($duplicate_sql);
				}
				
				// gm_action_query_fail
				$res = $DB->execute("SELECT condition_string, sql_string, index_query, index_subquery FROM gm_action_query_fail WHERE id_action = ".$duplicate_id);
				if ($res) {
					$duplicate_sql = "INSERT INTO gm_action_query_fail(condition_string, sql_string, index_query, index_subquery, id_action) VALUES";
					while ($row = $res->fetch_object()) {
						$duplicate_sql .= "('".str_replace("\\","\\\\",str_replace("'","''",$row->condition_string))."',";
						$duplicate_sql .= "'".str_replace("\\","\\\\",str_replace("'","''",$row->sql_string))."',";
						$duplicate_sql .= $row->index_query.",";
						$duplicate_sql .= $row->index_subquery.",";
						$duplicate_sql .= $insertedId."),";
					}
					$res->close();
					$duplicate_sql = substr($duplicate_sql, 0, -1);
					//echo $duplicate_sql;
					$DB->execute($duplicate_sql);
				}
				
			}
		}
?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; Actions</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Actions</h2>
<div style="padding-bottom:7px;">
	<form id="import_form" action="action_xml_import.php" method='post' enctype='multipart/form-data'>
		Name: <input type="text" name="filter_name" id="filter_name" class="filter_field" value="" /> 
		&nbsp;&nbsp;&nbsp;Value: <input type="text" name="filter_description" id="filter_description" class="filter_field" value="" />
		&nbsp;&nbsp;&nbsp;<!--input type="button" name="filter_button" id="filter_button" value="Filter" /-->&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="hidden" id="importAppId" name="importAppId" value="<?php echo $appID; ?>" />
<?php if ($authorized) { ?>
		<input type="file" id="xmlfile" name="xmlfile" title="Upload action xml file" accept="text/xml" />
		<input type="submit" id="import" name="import" value="Import" />
<?php } ?>
		<div id="reuseConfirmModalDialogBox" title="Objects reuse confirmation" style="display:none;">
			<div style="padding-bottom:10px; text-align:justify;">Do you want to reuse existing objects?</div>
			<div style="padding-bottom:10px; text-align:justify;">If an object exists and reuseing is confirmed then the system will reuse the existing one, otherwise it will create a new one appending time on its name.</div>
			<div style="padding-bottom:10px; text-align:justify;"><i>If you close the window without click any button then no import will be applied by the system.</i></div>
		</div>
		<input type="hidden" id="reuse" name="reuse" value="" />
	</form>
</div>
<form id="form" action="index.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px;">

		<div id="div_1" style="width:910px; background-color:#dddddd; padding-top:2px; padding-left:5px; padding-right:5px;" class="querysortable"><img src="/gm/img/add.png" style="vertical-align:top; cursor:pointer;" class="piece_addable" title="Add action"/><span class="piece_addable" style="cursor:pointer;">&nbsp;&nbsp;Add action</span>
		<span style="padding-left:440px;">&nbsp;</span><input type="checkbox" id="multi_deletable_all" name="multi_deletable_all" style="cursor:pointer;" />&nbsp;&nbsp;<span style="cursor:pointer;" id="multi_deletable_all_label">Select/Deselect all actions</span>&nbsp;&nbsp;<input type="button" id="cancel_selected_items" name="cancel_selected_items" value="Cancel selected actions" />
			<ul id="query_1" class="sortable" style="width:910px">

<?php
		$res = $DB->execute("SELECT id, name, value FROM gm_action WHERE id_application = ".$appID." ORDER BY name ASC, value ASC");
		if($res){
			$r = 0;
			while ($row = $res->fetch_object()){
				$r++;
?>
			  <li style="background-color:#eeeeee; margin-bottom:1px;" title="<?php echo $row->id?>">
				<input name="id.1.<?php echo $r?>" type="hidden" value="<?php echo $row->id?>" class="editable" />
				&nbsp;name <input name="name.1.<?php echo $r?>" type="text" size="56" value="<?php echo $row->name?>" class="editable" />
				&nbsp;&nbsp;value <input name="desc.1.<?php echo $r?>" type="text" size="50" value="<?php echo $row->value?>" class="editable" />
				<input type="checkbox" class="multi_deletable" name="multidel_checkbox<?php echo $row->id?>" style="cursor:pointer;" />
				<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this action (id=<?php echo $row->id?>)"/>
<?php if ($authorized) { ?>
				<a href="index.php?appId=<?php echo $appID; ?>&duplicateid=<?php echo $row->id?>" onClick="return confirm('Do you really want to duplicate this action (id=<?php echo $row->id?>)?\nAll other chenges will be lost.');"><img src="/gm/img/duplicate.png" style="vertical-align:middle; cursor:pointer;" title="Duplicate this action (id=<?php echo $row->id?>)"/></a>
<?php } ?>
				<a href="action_xml_export.php?id=<?php echo $row->id?>"><img src="/gm/img/export.png" style="vertical-align:middle; cursor:pointer;" title="Select this action (id=<?php echo $row->id?>) to export its xml"/></a>
				<a href="action_command.php?id=<?php echo $row->id?>"><img src="/gm/img/forward.png" style="vertical-align:middle; cursor:pointer;" title="Select this action (id=<?php echo $row->id?>) to define its command set"/></a>
			  </li>
<?php
			}
			$res->close();
		}
?>
			
			</ul>
			<div style="height:10px;">&nbsp;</div>
		</div>
		  
	</div>
	<div>&nbsp;</div>
	<input id="idstodelete" name="idstodelete" type="hidden" value="" />
	<input id="appId" name="appId" type="hidden" value="<?php echo $appID; ?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save actions</button>
<?php } ?>
</form>

<script>
	//$(".sortable").sortable();

	$(".piece_addable").on('click', addQueryPiece);

	$(".piece_deletable").on('click', deleteQueryPiece);

	function deleteQueryPiece(e){
		var id = $(this).parent().attr('title');
		var cancelConfirm = '';
		if (id != 'new') cancelConfirm = confirm('Do you really want to cancel this action (id=' + id + ')?\nRemember to submit this form to remove it definitely.');
		else cancelConfirm = confirm('Do you really want to cancel this new action?\nRemember to submit this form to remove it definitely.');
		if (cancelConfirm == true) {
			if (id != 'new') {
				$("#idstodelete").attr('value', id + ',' + $("#idstodelete").attr('value'));
				// css
				$("#titlepage").html('Actions <span style="color:red">changed</span>');
				$("#div_1").css('background-color','#eeee88');
			}
			$(this).parent().remove();
		}
	}

	function addQueryPiece(e){
		//alert($(this).parent().html());
		var ulElement = $(this).parent().children("ul");
		var i = ulElement.attr("id");
		i = i.replace(/query_/, "");
		var j = 0;
		var max_j = 0;
		ulElement.children().each(function () {
			$(this).children("input").each(function () {
				j = ($(this).attr("name")).lastIndexOf(".");
				j = ($(this).attr("name")).substr(j + 1);
				//alert(j);
				if (max_j < parseInt(j)) max_j = parseInt(j);
			});
		});
		//alert(max_j);
		max_j++;
		//alert(i + 'x' + max_j);
		$('<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this new action"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; margin-bottom:1px;" title="new">		<input name="id.' + i + '.' + max_j + '" type="hidden" value="" class="editable" />		&nbsp;name <input name="name.' + i + '.' + max_j + '" type="text" size="57" value="" class="editable" />		&nbsp;&nbsp;value <input name="desc.' + i + '.' + max_j + '" type="text" size="57" value="" class="editable" />	</li>').appendTo('#query_' + i));
		// css
		$("#titlepage").html('Actions <span style="color:red">changed</span>');
		$("#div_1").css('background-color','#eeee88');
	}

	$("#action").click(function() {
		//alert($("#query_1").html());
		//var t = "";
		var queryId = "";
		var oldInputArrayToNewLabel = [];
		var prefix = "";
		var condFound = false;
		var sqlFound = false;
		var i = 1;
		var j = 1;
		$(".sortable").each(function () {
			queryId = $(this).attr("id");
			//t = t + ' ' + queryId;
			$(".editable").each(function () {
				if ($(this).parent().parent().attr("id") == queryId) {
					//alert(i + " " + j);
					//t = t + ' ' + $(this).attr("name");
					if (($(this).attr("name")).indexOf('id') != -1) {
						prefix = "i_";
						condFound = true;
					}
					else if (($(this).attr("name")).indexOf('name') != -1) {
						prefix = "n_";
						condFound = true;
					}
					else {
						prefix = "d_";
						sqlFound = true;
					}
					oldInputArrayToNewLabel[$(this).attr("name")] = prefix + i + "_" + j;
					if (condFound && sqlFound) {
						condFound = false;
						sqlFound = false;
						j = j + 1;
					}
				}
			});
			j = 1;
			i = i + 1;
		});
		$(".editable").each(function () {
			$(this).attr("name", oldInputArrayToNewLabel[$(this).attr("name")]);
		});		
		//alert(t);
		//$("#html").attr("value", $("#query_1").html());
		//return false;
	});
	
	//$('#filter_button').click(function() {
	$('.filter_field').keyup(function() {
		//alert($('#filter_name').val());
		$("li").each(function () {
			//$(this).css('display','none');
			if (($(this).children('input[name*="name"]').attr("value").toLowerCase()).indexOf($.trim($('#filter_name').val().toLowerCase())) == -1 ||
			($(this).children('input[name*="desc"]').attr("value").toLowerCase()).indexOf($.trim($('#filter_description').val().toLowerCase())) == -1)
				//alert($(this).children('input[name*="name"]').attr("name"));
				$(this).css('display','none');
			else $(this).css('display','block');
		});
	});
	
	$('#import').click(function() {
		//alert($('#xmlfile').val());
		if ($('#xmlfile').val() == '') {
			alert('Select your xml file before.');
			return false;
		}
		else {
			$('#reuse').attr('value','');
			$('#reuseConfirmModalDialogBox').dialog({
				modal: true,
				resizable: false,
				closeText: "Close",
				buttons: {
					"Reuse": function() {
						//alert('Reuse');
						$('#reuse').attr('value','true');
						$(this).dialog('close');
						$('#import_form').submit();
					},
					"Don\'t reuse": function() {
						//alert('Don\'t reuse');
						$('#reuse').attr('value','false');
						$(this).dialog('close');
						$('#import_form').submit();
					}
				},
				close: function() {
					//$('#reuse').attr('value','');
				}
			});
			$('.ui-dialog').css('font-size','12px');
			return false;
		}
	});

	$('#multi_deletable_all').click(function() {
		if ($('#multi_deletable_all').prop('checked')) {
			//$('.multi_deletable').prop('checked', true);
			$(".multi_deletable").each(function () {
				if ($(this).parent().css('display') != 'none') $(this).prop('checked', true);
			});
		} else {
			//$('.multi_deletable').prop('checked', false);
			$(".multi_deletable").each(function () {
				if ($(this).parent().css('display') != 'none') $(this).prop('checked', false);
			});
		}
	});
	
	$('#multi_deletable_all_label').click(function() {
		if ($('#multi_deletable_all').prop('checked')) {
			$('#multi_deletable_all').prop('checked', false);
			//$('.multi_deletable').prop('checked', false);
			$(".multi_deletable").each(function () {
				if ($(this).parent().css('display') != 'none') $(this).prop('checked', false);
			});
		} else {
			$('#multi_deletable_all').prop('checked', true);
			//$('.multi_deletable').prop('checked', true);
			$(".multi_deletable").each(function () {
				if ($(this).parent().css('display') != 'none') $(this).prop('checked', true);
			});
		}
	});
	
	$('#cancel_selected_items').click(function() {
		var cancelConfirm = confirm('Do you really want to cancel selected actions?\nRemember to submit this form to remove them definitely.');
		if (cancelConfirm == true) {
			$(".multi_deletable").each(function () {
				if ($(this).prop('checked')) {
					var id = $(this).parent().attr('title');
					if (id != 'new') {
						$("#idstodelete").attr('value', id + ',' + $("#idstodelete").attr('value'));
						// css
						$("#titlepage").html('Pages <span style="color:red">changed</span>');
						$("#div_1").css('background-color','#eeee88');
					}
					$(this).parent().remove();
				}
			});
		}
	});
	
</script>

<?php
	}
?>

</body>
</html>