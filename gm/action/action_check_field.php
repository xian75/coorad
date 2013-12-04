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

<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Actions</a> &gt; <a href="action_command.php?id=<?php echo $actionID?>">Commands</a> &gt; <a href="action_check_query.php?id=<?php echo $actionID?>">Check Query Set</a> &gt; Check Field Set &gt; <a href="action_query_success.php?id=<?php echo $actionID?>">Query Set on Success</a> &gt; <a href="action_query_fail.php?id=<?php echo $actionID?>">Query Set on Fail</a> &gt; <a href="action_next_page.php?id=<?php echo $actionID ?>">Next Page</a></div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Field Set</h2>

<?php
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
		$i = 1;
		$j = 1;
		while (isset($_POST['i_'.$i.'_1'])) {
			//echo '<div>Query '.$i.'</div>';
			while (isset($_POST['i_'.$i.'_'.$j])) {
				//echo '<div>'.$i.'_'.$j.' -&gt; id='.$_POST['i_'.$i.'_'.$j].' name='.$_POST['n_'.$i.'_'.$j].' description='.$_POST['d_'.$i.'_'.$j];
				if ($_POST['i_'.$i.'_'.$j] > 0) {
					// UPDATE action
					$DB->execute("UPDATE gm_action_check_field SET condition_string='".str_replace("'","''",$_POST['c_'.$i.'_'.$j])."', error='".str_replace("'","''",$_POST['v_'.$i.'_'.$j])."' WHERE id=".$_POST['i_'.$i.'_'.$j]);
				}
				else {
					// INSERT action
					$DB->execute("INSERT INTO gm_action_check_field(condition_string, error, id_action) VALUES('".str_replace("'","''",$_POST['c_'.$i.'_'.$j])."','".str_replace("'","''",$_POST['v_'.$i.'_'.$j])."',".$actionID.")");
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
			$DB->execute("DELETE FROM gm_action_check_field WHERE id IN ".$idstodelete);
			//echo "DELETE FROM gm_action_check_field WHERE id IN ".$idstodelete;
		}
	}
?>
<form id="form" action="action_check_field.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px;">

		<div id="div_1" style="width:910px; background-color:#dddddd; padding-top:2px; padding-left:5px; padding-right:5px;" class="querysortable">
		<a style="text-decoration:none; color:black; padding-right:190px;" href="action_check_query.php?id=<?php echo $actionID?>"><img src="/gm/img/backward.png" title="Back to Check Query Set"/>&nbsp;&nbsp;Back to Check Query Set</a>
		<span class="piece_addable" style="vertical-align:top; cursor:pointer; padding-right:190px;"><img src="/gm/img/add.png" title="Add error"/>&nbsp;&nbsp;Add error</span>
		<a style="text-decoration:none; color:black;" href="action_query_success.php?id=<?php echo $actionID?>">Define this action query set on success&nbsp;&nbsp;<img src="/gm/img/forward.png" title="Define this action query set on success"/></a>
			<ul id="query_1" class="sortable" style="width:910px">

<?php
		$res = $DB->execute("SELECT id, condition_string, error FROM gm_action_check_field WHERE id_action=".$actionID." ORDER BY id ASC");
		if($res){
			$r = 0;
			while ($row = $res->fetch_object()){
				$r++;
				$textarea_rows = 1 + floor(strlen($row->condition_string) / 46);
				if ($textarea_rows < 1 + floor(strlen($row->error) / 46)) $textarea_rows = 1 + floor(strlen($row->error) / 46);
?>
			  <li style="background-color:#eeeeee; margin-bottom:1px; vertical-align:top;" title="<?php echo $row->id?>">
				<input name="id.1.<?php echo $r?>" type="hidden" value="<?php echo $row->id?>" class="editable" />
				&nbsp;if <textarea name="cond.1.<?php echo $r?>" cols="46" rows="<?php echo $textarea_rows;?>" class="editable" style="vertical-align:top; resize:vertical;"><?php echo str_replace("\\","\\\\",$row->condition_string);?></textarea>
				&nbsp;&nbsp;then exec <textarea name="view.1.<?php echo $r?>" cols="46" rows="<?php echo $textarea_rows;?>" class="editable" style="vertical-align:top; resize:vertical;"><?php echo str_replace("\\","\\\\",$row->error);?></textarea>
				<img src="/gm/img/del.png" style="vertical-align:top; cursor:pointer;" class="piece_deletable" title="Cancel this error (id=<?php echo $row->id?>)"/>
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
	<input name="id" type="hidden" value="<?php echo $actionID?>" />
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
		if (id != 'new') cancelConfirm = confirm('Do you really want to cancel this error (id=' + id + ')?\nRemember to submit this form to remove it definitely.');
		else cancelConfirm = confirm('Do you really want to cancel this new error?\nRemember to submit this form to remove it definitely.');
		if (cancelConfirm == true) {
			if (id != 'new') {
				$("#idstodelete").attr('value', id + ',' + $("#idstodelete").attr('value'));
				// css
				$("#titlepage").html('Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Field Set <span style="color:red">changed</span>');
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
		$('<img src="/gm/img/del.png" style="vertical-align:top; cursor:pointer;" class="piece_deletable" title="Cancel this new error"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; margin-bottom:1px; vertical-align:top;" title="new">		<input name="id.' + i + '.' + max_j + '" type="hidden" value="" class="editable" />		&nbsp;if <textarea name="cond.' + i + '.' + max_j + '" cols="46" rows="4" class="editable" style="vertical-align:top; resize:vertical;"></textarea>		&nbsp;&nbsp;then exec <textarea name="view.' + i + '.' + max_j + '" cols="46" rows="4" class="editable" style="vertical-align:top; resize:vertical;"></textarea>	</li>').appendTo('#query_' + i));
		// css
		$("#titlepage").html('Action <i style="background-color:yellow;">"<?php echo $actionName?>"</i> Check Field Set <span style="color:red">changed</span>');
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
					else if (($(this).attr("name")).indexOf('cond') != -1) {
						prefix = "c_";
						condFound = true;
					}
					else {
						prefix = "v_";
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
	
</script>

<?php
	}
?>

</body>
</html>