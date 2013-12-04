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
		echo '<h2>Sorry, no section selected</h2>';
	}
	else {
		$sectionID = 0;
		if (isset($_GET['id'])) $sectionID = $_GET['id'];
		else $sectionID = $_POST['id'];
		$res = $DB->execute("SELECT name, description FROM gm_section WHERE id=".$sectionID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$sectionName = str_replace("'","&rsquo;",$section->name);
		$res->close();
		// get appID from sectionID
		$appID = 0;
		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_application.context_path appContextPath FROM gm_section, gm_application WHERE gm_application.id = gm_section.id_application AND gm_section.id = ".$sectionID);
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

<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Sections</a> &gt; <a href="section_pre_init_query.php?id=<?php echo $sectionID?>">Pre Init Query</a> &gt; <a href="section_init_query.php?id=<?php echo $sectionID?>">Initialization Query Set</a> &gt; View Set</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Section <i style="background-color:yellow;">"<?php echo $sectionName?>"</i> View Set</h2>

<?php
	if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
		$i = 1;
		$j = 1;
		while (isset($_POST['i_'.$i.'_1'])) {
			//echo '<div>Query '.$i.'</div>';
			while (isset($_POST['i_'.$i.'_'.$j])) {
				//echo '<div>'.$i.'_'.$j.' -&gt; id='.$_POST['i_'.$i.'_'.$j].' name='.$_POST['n_'.$i.'_'.$j].' description='.$_POST['d_'.$i.'_'.$j];
				if ($_POST['i_'.$i.'_'.$j] > 0) {
					// UPDATE section
					$DB->execute("UPDATE gm_section_view SET condition_string='".str_replace("'","''",$_POST['c_'.$i.'_'.$j])."', view='".str_replace("'","''",$_POST['v_'.$i.'_'.$j])."' WHERE id=".$_POST['i_'.$i.'_'.$j]);
				}
				else {
					// INSERT section
					$DB->execute("INSERT INTO gm_section_view(condition_string, view, id_section, html, html_layout) VALUES('".str_replace("'","''",$_POST['c_'.$i.'_'.$j])."','".str_replace("'","''",$_POST['v_'.$i.'_'.$j])."',".$sectionID.",'','')");
				}
				//echo '</div>';
				$j = $j + 1;
			}
			$j = 1;
			$i = $i + 1;
		}
		// DELETE section
		if (isset($_POST['idstodelete'])) {
			//echo $_POST['idstodelete'];
			$idstodelete = '('.substr($_POST['idstodelete'], 0, strlen($_POST['idstodelete']) - 1).')';
			$DB->execute("DELETE FROM gm_section_view WHERE is_default = false AND id IN ".$idstodelete);
			//echo "DELETE FROM gm_section_view WHERE id IN ".$idstodelete;
		}
	}
	if (isset($_GET['duplicateid']) && $authorized) {
		$viewnameArrayForDuplication = array();
		$res = $DB->execute("SELECT view FROM gm_section_view");
		if($res){
			while ($row = $res->fetch_object()){
				$viewnameArrayForDuplication[] = $row->view;
			}
			$res->close();
		}
		$duplicate_id = $_GET['duplicateid'];
		$res = $DB->execute("SELECT condition_string, view, is_default, html, html_layout, width, height, command_pre_layout, id_section FROM gm_section_view WHERE id = ".$duplicate_id);
		if ($res) {
			$duplicate_sql = "INSERT INTO gm_section_view(condition_string, view, is_default, html, html_layout, width, height, command_pre_layout, id_section) VALUES(";
			if ($row = $res->fetch_object()) {
				$duplicate_view = $row->view;
				$r = 0;
				while (in_array($duplicate_view, $viewnameArrayForDuplication)) {
					$r++;
					$duplicate_view = $row->view."_copy".$r;
				}
				$duplicate_sql .= "'".str_replace("'","''",$row->condition_string)."',";
				$duplicate_sql .= "'".str_replace("'","''",$duplicate_view)."',";
				$duplicate_sql .= "0,"; //$row->is_default.",";
				$duplicate_sql .= "'".str_replace("'","''",$row->html)."',";
				$duplicate_sql .= "'".str_replace("'","''",$row->html_layout)."',";
				$duplicate_sql .= $row->width.",";
				$duplicate_sql .= $row->height.",";
				$duplicate_sql .= "'".str_replace("'","''",$row->command_pre_layout)."',";
				$duplicate_sql .= $row->id_section.")";
			}
			$res->close();
			//echo $duplicate_sql;
			$DB->execute($duplicate_sql);
		}
	}	
?>
<form id="form" action="section_view_selection.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px;">

		<div id="div_1" style="width:910px; background-color:#dddddd; padding-top:2px; padding-left:5px; padding-right:5px;" class="querysortable">
		<a style="text-decoration:none; color:black; padding-right:605px;" href="section_init_query.php?id=<?php echo $sectionID?>"><img src="/gm/img/backward.png" title="Back to Initialization Query Set"/>&nbsp;&nbsp;Back to Initialization Query Set</a>
		<img src="/gm/img/add.png" style="vertical-align:top; cursor:pointer;" class="piece_addable" title="Add view"/><span class="piece_addable" style="cursor:pointer;">&nbsp;&nbsp;Add view</span>
			<ul id="query_1" class="sortable" style="width:910px">

<?php
		$res = $DB->execute("SELECT id, condition_string, view, is_default FROM gm_section_view WHERE id_section=".$sectionID." ORDER BY id ASC");
		if($res){
			$r = 0;
			while ($row = $res->fetch_object()){
				$r++;
				$textarea_rows = 1 + floor(strlen($row->condition_string) / 67);
				if ($textarea_rows < 1 + floor(strlen($row->view) / 67)) $textarea_rows = 1 + floor(strlen($row->view) / 67);
?>
			  <li style="background-color:#eeeeee; margin-bottom:1px;" title="<?php echo $row->id?>">
				<input name="id.1.<?php echo $r?>" type="hidden" value="<?php echo $row->id?>" class="editable" />
				&nbsp;if <textarea name="cond.1.<?php echo $r?>" cols="67" rows="<?php echo $textarea_rows;?>" class="editable" style="vertical-align:top; resize:vertical;"><?php echo $row->condition_string?></textarea>
				<?php if (!$row->is_default) { ?>
					&nbsp;&nbsp;then view= <textarea name="view.1.<?php echo $r?>" cols="20" rows="<?php echo $textarea_rows;?>" class="editable" style="vertical-align:top; resize:vertical;"><?php echo $row->view?></textarea>
					<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this view (id=<?php echo $row->id?>)"/>
				<?php } else { ?>
					&nbsp;&nbsp;then view= <textarea name="view.1.<?php echo $r?>" cols="20" rows="<?php echo $textarea_rows;?>" class="editable" readonly style="vertical-align:top; resize:vertical; background-color:#cccccc;" /><?php echo $row->view?></textarea>
					<img src="/gm/img/del_deny.png" style="vertical-align:middle; cursor:pointer;" title="You can't cancel the default view (id=<?php echo $row->id?>)"/>
				<?php } ?>
<?php if ($authorized) { ?>
				<a href="section_view_selection.php?id=<?php echo $sectionID?>&duplicateid=<?php echo $row->id?>" onClick="return confirm('Do you really want to duplicate this view (id=<?php echo $row->id?>)?\nAll other chenges will be lost.');"><img src="/gm/img/duplicate.png" style="vertical-align:middle; cursor:pointer;" title="Duplicate this view (id=<?php echo $row->id?>)"/></a>
<?php } ?>
				<a href="section_pre_layout.php?id=<?php echo $row->id?>"><img src="/gm/img/forward.png" style="vertical-align:middle; cursor:pointer;" title="Select this view (id=<?php echo $row->id?>) to define its pre layout command set"/></a>
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
	<input name="id" type="hidden" value="<?php echo $sectionID?>" />
<?php if ($authorized) { ?>
	<button id="action" name="action" type="submit" value="save">Save views</button>
<?php } ?>
</form>

<script>
	//$(".sortable").sortable();

	$(".piece_addable").on('click', addQueryPiece);

	$(".piece_deletable").on('click', deleteQueryPiece);

	function deleteQueryPiece(e){
		var id = $(this).parent().attr('title');
		var cancelConfirm = '';
		if (id != 'new') cancelConfirm = confirm('Do you really want to cancel this view (id=' + id + ')?\nRemember to submit this form to remove it definitely.');
		else cancelConfirm = confirm('Do you really want to cancel this new view?\nRemember to submit this form to remove it definitely.');
		if (cancelConfirm == true) {
			if (id != 'new') {
				$("#idstodelete").attr('value', id + ',' + $("#idstodelete").attr('value'));
				// css
				$("#titlepage").html('Section <i style="background-color:yellow;">"<?php echo $sectionName?>"</i> View Set <span style="color:red">changed</span>');
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
		$('<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this new view"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; margin-bottom:1px;" title="new">		<input name="id.' + i + '.' + max_j + '" type="hidden" value="" class="editable" />		&nbsp;if <input name="cond.' + i + '.' + max_j + '" type="text" size="93" value="" class="editable" />		&nbsp;&nbsp;then view= <input name="view.' + i + '.' + max_j + '" type="text" value="" class="editable" />	</li>').appendTo('#query_' + i));
		// css
		$("#titlepage").html('Section <i style="background-color:yellow;">"<?php echo $sectionName?>"</i> View Set <span style="color:red">changed</span>');
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