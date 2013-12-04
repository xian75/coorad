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
	require_once("init.php");
	// check logged user
	if ($CFG->authorization && !isset($_SESSION['user'])) header("location: login.php");
	if ($CFG->authorization && isset($_SESSION['user'])) echo '<div style="text-align:right;"><div>User: <strong>'.$_SESSION['user'].'</strong> - <a href="logout.php">Logout</a></div></div>';
?>
<!--div><a href="../index.php">Home</a> &gt; Applications</div-->
<div>Home</div>
<h2 id="titlepage">Applications</h2>
<?php
	//require_once("init.php");	

	function delTree($dir) { 
		$files = array_diff(scandir($dir), array('.','..')); 
		foreach ($files as $file) { 
			(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
		} 
		return rmdir($dir); 
	} 
	
	if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && $_GET['id'] != '' && (!$CFG->authorization || (isset($_SESSION['role']) && $_SESSION['role'] == 'superuser'))) {
		$oldContextPath = '';
		$res = $DB->execute("SELECT context_path FROM gm_application WHERE id = ".$_GET['id']);
		if ($res) {
			if ($row = $res->fetch_object()) {
				$oldContextPath = $row->context_path;
			}
		}
		$res->close();
		if ($oldContextPath != '') {
			if (delTree($CFG->rootDir.'/deploy/'.$oldContextPath)) {
				$DB->execute("DELETE FROM gm_application WHERE id = ".$_GET['id']);
			}
		}
	}
	
	/*if (isset($_POST['action']) && $_POST['action'] == 'save') {
		$i = 1;
		$j = 1;
		while (isset($_POST['i_'.$i.'_1'])) {
			//echo '<div>Query '.$i.'</div>';
			while (isset($_POST['i_'.$i.'_'.$j])) {
				//echo '<div>'.$i.'_'.$j.' -&gt; id='.$_POST['i_'.$i.'_'.$j].' name='.$_POST['n_'.$i.'_'.$j].' description='.$_POST['d_'.$i.'_'.$j];
				if ($_POST['i_'.$i.'_'.$j] > 0) {
					// UPDATE application
					$DB->execute("UPDATE gm_section SET name='".str_replace("'","''",$_POST['n_'.$i.'_'.$j])."', description='".str_replace("'","''",$_POST['d_'.$i.'_'.$j])."' WHERE id=".$_POST['i_'.$i.'_'.$j]);
				}
				else {
					// INSERT application
					$DB->execute("INSERT INTO gm_section(name, description) VALUES('".str_replace("'","''",$_POST['n_'.$i.'_'.$j])."','".str_replace("'","''",$_POST['d_'.$i.'_'.$j])."')");
					$insertedId = $DB->getInsertedId();
					$DB->execute("INSERT INTO gm_section_view(id_section, is_default) VALUES(".$insertedId.",true)");
				}
				//echo '</div>';
				$j = $j + 1;
			}
			$j = 1;
			$i = $i + 1;
		}
		// DELETE application
		if (isset($_POST['idstodelete'])) {
			//echo $_POST['idstodelete'];
			$idstodelete = '('.substr($_POST['idstodelete'], 0, strlen($_POST['idstodelete']) - 1).')';
			$DB->execute("DELETE FROM gm_section WHERE id IN ".$idstodelete);
			//echo "DELETE FROM gm_section WHERE id IN ".$idstodelete;
		}
	}*/
	/*if (isset($_GET['duplicateid'])) {
		$sectionnameArrayForDuplication = array();
		$res = $DB->execute("SELECT name FROM gm_section");
		if($res){
			while ($row = $res->fetch_object()){
				$sectionnameArrayForDuplication[] = $row->name;
			}
			$res->close();
		}
		$duplicate_id = $_GET['duplicateid'];
		$res = $DB->execute("SELECT name, description, command_pre_init_query FROM gm_section WHERE id = ".$duplicate_id);
		if ($res) {
			$duplicate_sql = "INSERT INTO gm_section(name, description, command_pre_init_query) VALUES(";
			if ($row = $res->fetch_object()) {
				$duplicate_name = $row->name;
				$r = 0;
				while (in_array($duplicate_name, $sectionnameArrayForDuplication)) {
					$r++;
					$duplicate_name = $row->name."_copy".$r;
				}
				$duplicate_sql .= "'".str_replace("'","''",$duplicate_name)."',";
				$duplicate_sql .= "'".str_replace("'","''",$row->description)."',";
				$duplicate_sql .= "'".str_replace("'","''",$row->command_pre_init_query)."')";
			}
			$res->close();
			//echo $duplicate_sql;
			$DB->execute($duplicate_sql);
			$insertedId = $DB->getInsertedId();

			// gm_section_init_query
			$res = $DB->execute("SELECT condition_string, sql_string, index_query, index_subquery FROM gm_section_init_query WHERE id_section = ".$duplicate_id);
			if ($res) {
				$duplicate_sql = "INSERT INTO gm_section_init_query(condition_string, sql_string, index_query, index_subquery, id_section) VALUES";
				while ($row = $res->fetch_object()) {
					$duplicate_sql .= "('".str_replace("'","''",$row->condition_string)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->sql_string)."',";
					$duplicate_sql .= $row->index_query.",";
					$duplicate_sql .= $row->index_subquery.",";
					$duplicate_sql .= $insertedId."),";
				}
				$res->close();
				$duplicate_sql = substr($duplicate_sql, 0, -1);
				//echo $duplicate_sql;
				$DB->execute($duplicate_sql);
			}
			
			// gm_section_view
			$res = $DB->execute("SELECT condition_string, view, is_default, html, html_layout, width, height, command_pre_layout FROM gm_section_view WHERE id_section = ".$duplicate_id);
			if ($res) {
				$duplicate_sql = "INSERT INTO gm_section_view(condition_string, view, is_default, html, html_layout, width, height, command_pre_layout, id_section) VALUES";
				while ($row = $res->fetch_object()) {
					$duplicate_sql .= "('".str_replace("'","''",$row->condition_string)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->view)."',";
					$duplicate_sql .= $row->is_default.",";
					$duplicate_sql .= "'".str_replace("'","''",$row->html)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->html_layout)."',";
					$duplicate_sql .= $row->width.",";
					$duplicate_sql .= $row->height.",";
					$duplicate_sql .= "'".str_replace("'","''",$row->command_pre_layout)."',";
					$duplicate_sql .= $insertedId."),";
				}
				$res->close();
				$duplicate_sql = substr($duplicate_sql, 0, -1);
				//echo $duplicate_sql;
				$DB->execute($duplicate_sql);
			}
			
		}
	}*/
?>
<!-- 
<form id="form" action="index.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px;">

		<div id="div_1" style="width:910px; background-color:#dddddd; padding-top:2px; padding-left:5px; padding-right:5px;" class="querysortable"><img src="/gm/img/add.png" style="vertical-align:top; cursor:pointer;" class="piece_addable" title="Add application"/><span class="piece_addable" style="cursor:pointer;">&nbsp;&nbsp;Add application</span>
			<ul id="query_1" class="sortable" style="width:910px">

<?php
		$res = $DB->execute("SELECT id, name, description, context_path, db_prefix FROM gm_application ORDER BY name ASC");
		if($res){
			$r = 0;
			while ($row = $res->fetch_object()){
				$r++;
?>
			  <li style="background-color:#eeeeee; margin-bottom:1px;" title="<?php echo $row->id?>">
				<input name="id.1.<?php echo $r?>" type="hidden" value="<?php echo $row->id?>" class="editable" />
				&nbsp;name <input name="name.1.<?php echo $r?>" type="text" size="45" value="<?php echo $row->name?>" class="editable" />
				&nbsp;&nbsp;description <input name="desc.1.<?php echo $r?>" type="text" size="64" value="<?php echo $row->description?>" class="editable" />
				<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this application (id=<?php echo $row->id?>)"/>
				<a href="index.php?duplicateid=<?php echo $row->id?>" onClick="return confirm('Do you really want to duplicate this application (id=<?php echo $row->id?>)?\nAll other chenges will be lost.');"><img src="/gm/img/duplicate.png" style="vertical-align:middle; cursor:pointer;" title="Duplicate this application (id=<?php echo $row->id?>)"/></a>
				<a href="section_pre_init_query.php?id=<?php echo $row->id?>"><img src="/gm/img/forward.png" style="vertical-align:middle; cursor:pointer;" title="Select this section (id=<?php echo $row->id?>) to define its pre initial query set"/></a>
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
	<button id="action" name="action" type="submit" value="save">Save applications</button>
</form>
-->

<?php if (!$CFG->authorization || (isset($_SESSION['role']) && $_SESSION['role'] == 'superuser')) { ?>
<a href="application/app_addmod.php" style="text-decoration:none; color: black;"><img src="/gm/img/add.png" style="vertical-align:top;" title="Add application"/>&nbsp;&nbsp;Add application</a>
<?php 
	}
	if ($CFG->authorization && isset($_SESSION['role']) && $_SESSION['role'] == 'superuser') {
?>
<a href="user/index.php" style="text-decoration:none; color: black; padding-left:660px;"><img src="/gm/img/users.png" style="vertical-align:top;" title="Manage Users"/>&nbsp;&nbsp;Manage Users</a>
<?php } ?>

<table style="border: 1px solid black; border-collapse: collapse; width: 900px;">
	<tr style="background-color:#dddddd;">
<?php if (!$CFG->authorization || (isset($_SESSION['role']) && $_SESSION['role'] == 'superuser')) { ?>
		<td style="border: 1px dotted black; width: 10%;">Name</td>
		<td style="border: 1px dotted black; width: 20%;">Description</td>
		<td style="border: 1px dotted black; width: 10%;">Context Path</td>
		<td style="border: 1px dotted black; width: 10%;">DB Host</td>
		<td style="border: 1px dotted black; width: 10%;">DB Port</td>
		<td style="border: 1px dotted black; width: 10%;">DB Name</td>
		<td style="border: 1px dotted black; width: 10%;">DB User</td>
		<td style="border: 1px dotted black; width: 10%;">DB Table Prefix</td>
		<td style="border: 1px dotted black; width: 10%;"></td>
<?php } else { ?>
		<td style="border: 1px dotted black; width: 25%;">Name</td>
		<td style="border: 1px dotted black; width: 30%;">Description</td>
		<td style="border: 1px dotted black; width: 25%;">Context Path</td>
		<td style="border: 1px dotted black; width: 20%;"></td>
<?php } ?>
	</tr>
<?php
		$res = $DB->execute("SELECT id, name, description, context_path, db_prefix, db_host, db_port, db_name, db_username FROM gm_application ORDER BY name ASC");
		if($res){
			$r = 0;
			while ($row = $res->fetch_object()){
				$r++;
				if (!$CFG->authorization || (isset($_SESSION['role']) && ($_SESSION['role'] == 'superuser' || (($_SESSION['role'] == 'adminapp' || $_SESSION['role'] == 'guestapp') && in_array($row->id, $_SESSION['apps']))))) {
?>
				  <tr>
					<td style="border: 1px dotted black;"><?php echo $row->name?></td>
					<td style="border: 1px dotted black;"><?php echo $row->description?></td>
					<td style="border: 1px dotted black;"><?php echo $row->context_path?></td>
<?php if (!$CFG->authorization || (isset($_SESSION['role']) && $_SESSION['role'] == 'superuser')) { ?>
					<td style="border: 1px dotted black;"><?php echo $row->db_host?></td>
					<td style="border: 1px dotted black;"><?php echo $row->db_port?></td>
					<td style="border: 1px dotted black;"><?php echo $row->db_name?></td>
					<td style="border: 1px dotted black;"><?php echo $row->db_username?></td>
					<td style="border: 1px dotted black;"><?php echo $row->db_prefix?></td>
<?php } ?>
					<td style="border: 1px dotted black;">
<?php
					if (!$CFG->authorization || (isset($_SESSION['role']) && $_SESSION['role'] == 'superuser')) {
?>
						<a href="index.php?action=delete&id=<?php echo $row->id?>"><img src="/gm/img/del.png" style="vertical-align:bottom;" title="Delete this application (id=<?php echo $row->id?>)" onClick="return confirm('Do you really want to cancel this application (id=' + <?php echo $row->id?> + ')?')" /></a>
						<a href="application/app_addmod.php?id=<?php echo $row->id?>"><img src="/gm/img/mod.png" style="vertical-align:bottom;" title="Modify this application (id=<?php echo $row->id?>)" /></a>
<?php
					}
?>					
						<a href="application/index.php?appId=<?php echo $row->id?>"><img src="/gm/img/forward.png" style="vertical-align:bottom;" title="Configure this application (id=<?php echo $row->id?>)" /></a>
					</td>
				  </tr>
<?php
				}
			}
			$res->close();
		}
?></table>

<script>
	//$(".sortable").sortable();

	$(".piece_addable").on('click', addQueryPiece);

	$(".piece_deletable").on('click', deleteQueryPiece);

	function deleteQueryPiece(e){
		var id = $(this).parent().attr('title');
		var cancelConfirm = '';
		if (id != 'new') cancelConfirm = confirm('Do you really want to cancel this section (id=' + id + ')?\nRemember to submit this form to remove it definitely.');
		else cancelConfirm = confirm('Do you really want to cancel this new section?\nRemember to submit this form to remove it definitely.');
		if (cancelConfirm == true) {
			if (id != 'new') {
				$("#idstodelete").attr('value', id + ',' + $("#idstodelete").attr('value'));
				// css
				$("#titlepage").html('Sections <span style="color:red">changed</span>');
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
		$('<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this new section"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; margin-bottom:1px;" title="new">		<input name="id.' + i + '.' + max_j + '" type="hidden" value="" class="editable" />		&nbsp;name <input name="name.' + i + '.' + max_j + '" type="text" size="45" value="" class="editable" />		&nbsp;&nbsp;description <input name="desc.' + i + '.' + max_j + '" type="text" size="64" value="" class="editable" />	</li>').appendTo('#query_' + i));
		// css
		$("#titlepage").html('Sections <span style="color:red">changed</span>');
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
	
</script>

</body>
</html>