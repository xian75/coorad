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
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<h2>Sorry, no action selected</h2>';
	}
	else {
		$actionID = 0;
		if (isset($_GET['id'])) $actionID = $_GET['id'];
		else $actionID = $_POST['id'];
		$res = $DB->execute("SELECT name, value FROM gm_action WHERE id=".$actionID);
		if (!$action = $res->fetch_object()) die('something wrong.');
		$actionName = str_replace("'","&rsquo;",$action->name.'='.$action->value);
		$res->close();
		// get appID from pageID
		$appID = 0;
		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_application.context_path appContextPath FROM gm_action, gm_application WHERE gm_application.id = gm_action.id_application AND gm_action.id = ".$actionID);
		if ($res) {
			while ($row = $res->fetch_object()) {
				$appID = $row->appId;
				$appName = $row->appName;
				$appContextPath = $row->appContextPath;
			}
			$res->close();
		}

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

?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Actions</a> &gt; <a href="action_command.php?id=<?php echo $actionID?>">Commands</a> &gt; Check Query Set &gt; <a href="action_check_field.php?id=<?php echo $actionID?>">Check Field Set</a> &gt; <a href="action_query_success.php?id=<?php echo $actionID?>">Query Set on Success</a> &gt; <a href="action_query_fail.php?id=<?php echo $actionID?>">Query Set on Fail</a> &gt; <a href="action_next_page.php?id=<?php echo $actionID ?>">Next Page</a></div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Query Set</h2>

<?php
	/*$i = 1;
	$j = 1;
	while (isset($_GET['c_'.$i.'_1'])) {
		//echo '<div>Query '.$i.'</div>';
		while (isset($_GET['c_'.$i.'_'.$j])) {
			//echo '<div>'.$i.'_'.$j.' -&gt; cond:'.$_GET['c_'.$i.'_'.$j].' - sql:'.$_GET['s_'.$i.'_'.$j];
			//if ($_GET['s_'.$i.'_'.$j] != null && $_GET['s_'.$i.'_'.$j] != '') echo ' OK';
			//echo '</div>';
			$j = $j + 1;
		}
		$j = 1;
		$i = $i + 1;
	}*/
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
		// DELETE action
		$DB->execute("DELETE FROM gm_action_check_query WHERE id_action = ".$actionID);
		$i = 1;
		$j = 1;
		$assigned_i = 1;
		$assigned_j = 0;
		$assigned = false; 
		while (isset($_POST['c_'.$i.'_1'])) {
			//echo '<div>Query '.$i.'</div>';
			$assigned_j = 0;
			$assigned = false; 
			while (isset($_POST['c_'.$i.'_'.$j])) {
				//echo '<div>'.$i.'_'.$j.' -&gt; condition='.$_POST['c_'.$i.'_'.$j].' sql='.$_POST['s_'.$i.'_'.$j];
				//if ($_POST['s_'.$i.'_'.$j] != null && $_POST['s_'.$i.'_'.$j] != '') echo ' OK';
				if ($_POST['s_'.$i.'_'.$j] != null && $_POST['s_'.$i.'_'.$j] != '') {
					$assigned = true;
					$assigned_j++;
					$DB->execute("INSERT INTO gm_action_check_query(condition_string, sql_string, index_query, index_subquery, id_action) VALUES('".str_replace("'","''",$_POST['c_'.$i.'_'.$j])."','".str_replace("'","''",$_POST['s_'.$i.'_'.$j])."',".$assigned_i.",".$assigned_j.",".$actionID.")");
				}
				// INSERT action
				//echo '</div>';
				$j = $j + 1;
			}
			$j = 1;
			$i = $i + 1;
			if ($assigned) $assigned_i++;
		}
	}
?>
<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black; padding-right:231px;" href="action_command.php?id=<?php echo $actionID?>"><img src="/gm/img/backward.png" title="Back to Commands"/>&nbsp;&nbsp;Back to Commands</a>
<span class="addable" style="vertical-align:top; cursor:pointer; padding-right:231px;"><img src="/gm/img/add.png" title="Add query"/>&nbsp;&nbsp;Add query</span>
<a style="text-decoration:none; color:black;" href="action_check_field.php?id=<?php echo $actionID ?>">Define this action check field set&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Define this action check field set"/></a>
</div>
<form id="form" action="action_check_query.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px; background-color:#bbbbbb;">

<?php
		$res = $DB->execute("SELECT id, condition_string, sql_string, index_query, index_subquery FROM gm_action_check_query WHERE id_action=".$actionID." ORDER BY index_query ASC, index_subquery ASC");
		if($res){
			$oldQueryIndexStart = 0;
			$oldQueryIndexEnd = 0;
			while ($row = $res->fetch_object()){
				if ($oldQueryIndexEnd == 0) $oldQueryIndexEnd = 1;
				if ($oldQueryIndexEnd != $row->index_query) {
					$oldQueryIndexEnd = $row->index_query;
?>
			</ul>
			<div style="height:10px;">&nbsp;</div>
		</div>
<?php
				}
				if ($oldQueryIndexStart != $row->index_query) {
					$oldQueryIndexStart = $row->index_query;
?>
		<div id="div_<?php echo $row->index_query?>" style="width:910px; background-color:#dddddd; margin-bottom:15px; padding-left:5px; padding-right:5px;" class="querysortable"><img src="/gm/img/updown.png" style="vertical-align:middle; cursor:pointer; padding-bottom:4px;"/>Query <?php echo $row->index_query?>
			<img src="/gm/img/add_small.png" style="vertical-align:middle; cursor:pointer;" class="piece_addable" title="Add query piece"/>
			<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="deletable" title="Cancel this query"/>
			<ul id="query_<?php echo $row->index_query?>" class="sortable" style="width:910px">
<?php
				}
				$textarea_rows = 1 + floor(strlen($row->condition_string) / 46);
				if ($textarea_rows < 1 + floor(strlen($row->sql_string) / 46)) $textarea_rows = 1 + floor(strlen($row->sql_string) / 46);
				
?>
			  <li style="background-color:#eeeeee; cursor:pointer; margin-bottom:1px;" title="<?php echo $row->id?>">
				<img src="/gm/img/updown_small.png" style="vertical-align:middle; cursor:pointer;"/>
				if <textarea name="cond.<?php echo $row->index_query?>.<?php echo $row->index_subquery?>" cols="46" rows="<?php echo $textarea_rows;?>" class="editable" style="vertical-align:top; resize:vertical;"><?php echo str_replace("\\","\\\\",$row->condition_string);?></textarea>
				then sql.= <textarea name="sql.<?php echo $row->index_query?>.<?php echo $row->index_subquery?>" cols="46" rows="<?php echo $textarea_rows;?>" class="editable" style="vertical-align:top; resize:vertical;"><?php echo str_replace("\\","\\\\",$row->sql_string);?></textarea>
				<img src="/gm/img/del_small.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this query piece (id=<?php echo $row->id?>)"/>
			  </li>
<?php
			}
			$res->close();
			if ($oldQueryIndexEnd != 0) {
?>
		</ul>
		<div style="height:10px;">&nbsp;</div>
	</div>
<?php
			}
		}
?>
	
	
	</div>
	<div>&nbsp;</div>
	<input name="id" type="hidden" value="<?php echo $actionID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save this action check query set</button>
<?php } ?>
</form>

<script>
	$(".sortable").sortable();
	
	$("#queries").sortable();

	$(".piece_addable").on('click', addQueryPiece);

	$(".piece_deletable").on('click', deleteQueryPiece);

	$(".addable").on('click', addQuery);
	
	$(".deletable").on('click', deleteQuery);

	function deleteQueryPiece(e){
		var id = $(this).parent().attr('title');
		var cancelConfirm = '';
		if (id != 'new') cancelConfirm = confirm('Do you really want to cancel this conditional sql piece (id=' + id + ')?\nRemember to submit this form to remove it definitely.');
		else cancelConfirm = confirm('Do you really want to cancel this new conditional sql piece?\nRemember to submit this form to remove it definitely.');
		if (cancelConfirm == true) {
			if (id != 'new') {
				// css
				$("#titlepage").html('Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Query Set <span style="color:red">changed</span>');
				$(this).parent().parent().parent().css('background-color','#eeee88');
				$(this).parent().parent().parent().parent().css('background-color','#dddd44');
			}
			$(this).parent().remove();
		}
	}

	function deleteQuery(e){
		var cancelConfirm = confirm('Do you really want to cancel this query?');
		if (cancelConfirm == true) {
			$("#titlepage").html('Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Query Set <span style="color:red">changed</span>');
			$(this).parent().parent().css('background-color','#dddd44');
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
			$(this).children("textarea").each(function () {
				j = ($(this).attr("name")).lastIndexOf(".");
				j = ($(this).attr("name")).substr(j + 1);
				//alert(j);
				if (max_j < parseInt(j)) max_j = parseInt(j);
			});
		});
		//alert(max_j);
		max_j++;
		//alert(i + 'x' + max_j);
		$('<img src="/gm/img/del_small.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this new query piece"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; cursor:pointer; margin-bottom:1px;" title="new">		<img src="/gm/img/updown_small.png" style="vertical-align:middle; cursor:pointer;"/>		if <textarea name="cond.' + i + '.' + max_j + '" cols="46" rows="3" class="editable" style="vertical-align:top; resize:vertical;"></textarea>		then sql.= <textarea name="sql.' + i + '.' + max_j + '" cols="46" rows="3" class="editable" style="vertical-align:top; resize:vertical;"></textarea>	</li>').appendTo('#query_' + i));		
		// css
		$("#titlepage").html('Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Query Set <span style="color:red">changed</span>');
		$(this).parent().css('background-color','#eeee88');
		$("#queries").css('background-color','#dddd44');
	}

	function addQuery(e){
		var i = 0;
		var max_i = 0;
		$('.sortable').each(function () {
			var i = $(this).attr("id");
			i = i.replace(/query_/, "");
			if (max_i < parseInt(i)) max_i = parseInt(i);
		});
		//alert(max_i);
		max_i++;
		//alert(max_i);
		$('<div id="div_' + max_i + '" style="width:910px; background-color:#eeee88; margin-bottom:15px; padding-left:5px; padding-right:5px;"><img src="/gm/img/updown.png" style="vertical-align:middle; cursor:pointer; padding-bottom:4px;"/>Query ' + max_i + '&nbsp;</div>').appendTo('#queries');
		$('<img src="/gm/img/add_small.png" style="vertical-align:middle; cursor:pointer;" class="piece_addable" title="Add query piece"/>').on('click', addQueryPiece).appendTo('#div_' + max_i);
		$('<span>&nbsp;</span>').appendTo('#div_' + max_i);
		$('<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="deletable" title="Cancel this new query"/>').on('click', deleteQuery).appendTo('#div_' + max_i);
		$('<ul id="query_' + max_i + '" class="sortable" style="width:910px">	</ul>	<div style="height:10px;">&nbsp;</div>').appendTo('#div_' + max_i);

		$('<img src="/gm/img/del_small.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this new query piece"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; cursor:pointer; margin-bottom:1px;" title="new">		<img src="/gm/img/updown_small.png" style="vertical-align:middle; cursor:pointer;"/>		if <textarea name="cond.' + max_i + '.1" cols="46" rows="8" class="editable" style="vertical-align:top; resize:vertical;"></textarea>		then sql.= <textarea name="sql.' + max_i + '.1" cols="46" rows="8" class="editable" style="vertical-align:top; resize:vertical;"></textarea>	</li>').appendTo('#query_' + max_i));		
		
		$('#query_' + max_i).sortable();
		// css
		$("#titlepage").html('Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Query Set <span style="color:red">changed</span>');
		$("#queries").css('background-color','#dddd44');
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
				//alert($(this).parent().parent().attr("id"));			
				if ($(this).parent().parent().attr("id") == queryId) {
					//alert(i + " " + j);
					//t = t + ' ' + $(this).attr("name");
					if (($(this).attr("name")).indexOf('cond') != -1) {
						prefix = "c_";
						condFound = true;
					}
					else {
						prefix = "s_";
						sqlFound = true;
					}
					oldInputArrayToNewLabel[$(this).attr("name")] = prefix + i + "_" + j;
					//alert($(this).attr("name") + " -> " + oldInputArrayToNewLabel[$(this).attr("name")]);
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
	
</script>

<?php
	}
?>

</body>
</html>