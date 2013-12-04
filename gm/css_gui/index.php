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
					//echo '<div>'.$i.'_'.$j.' -&gt; id='.$_POST['i_'.$i.'_'.$j].' name='.$_POST['n_'.$i.'_'.$j].' description='.$_POST['d_'.$i.'_'.$j];
					if ($_POST['i_'.$i.'_'.$j] > 0) {
						// UPDATE css
						$DB->execute("UPDATE gm_css SET name='".str_replace("'","''",$_POST['n_'.$i.'_'.$j])."', description='".str_replace("'","''",$_POST['d_'.$i.'_'.$j])."' WHERE id=".$_POST['i_'.$i.'_'.$j]);
					}
					else {
						// INSERT css
						$DB->execute("INSERT INTO gm_css(name, description, id_application) VALUES('".str_replace("'","''",$_POST['n_'.$i.'_'.$j])."','".str_replace("'","''",$_POST['d_'.$i.'_'.$j])."',".$appID.")");
					}
					//echo '</div>';
					$j = $j + 1;
				}
				$j = 1;
				$i = $i + 1;
			}
			// DELETE css
			if (isset($_POST['idstodelete'])) {
				//echo $_POST['idstodelete'];
				$idstodelete = '('.substr($_POST['idstodelete'], 0, strlen($_POST['idstodelete']) - 1).')';
				$DB->execute("DELETE FROM gm_css WHERE id IN ".$idstodelete);
				//echo "DELETE FROM gm_css WHERE id IN ".$idstodelete;
			}
		}
		if (isset($_GET['duplicateid']) && $authorized) {
			$cssnameArrayForDuplication = array();
			$res = $DB->execute("SELECT name FROM gm_css WHERE id_application = ".$appID);
			if($res){
				while ($row = $res->fetch_object()){
					$cssnameArrayForDuplication[] = $row->name;
				}
				$res->close();
			}
			$duplicate_id = $_GET['duplicateid'];
			$res = $DB->execute("SELECT name, description, css, id_application FROM gm_css WHERE id = ".$duplicate_id);
			if ($res) {
				$duplicate_sql = "INSERT INTO gm_css(name, description, css, id_application) VALUES(";
				if ($row = $res->fetch_object()) {
					$duplicate_name = $row->name;
					$r = 0;
					while (in_array($duplicate_name, $cssnameArrayForDuplication)) {
						$r++;
						$duplicate_name = $row->name."_copy".$r;
					}
					$duplicate_sql .= "'".str_replace("'","''",$duplicate_name)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->description)."',";
					$duplicate_sql .= "'".str_replace("'","''",$row->css)."',";
					$duplicate_sql .= $row->id_application.")";
				}
				$res->close();
				//echo $duplicate_sql;
				$DB->execute($duplicate_sql);
			}
		}
?>
<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; CSS</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">CSS</h2>

<form id="form" action="index.php" method="post">
	<div id="queries" style="border: dotted 1px; width:920px;">

		<div id="div_1" style="width:910px; background-color:#dddddd; padding-top:2px; padding-left:5px; padding-right:5px;" class="querysortable"><img src="/gm/img/add.png" style="vertical-align:top; cursor:pointer;" class="piece_addable" title="Add css"/><span class="piece_addable" style="cursor:pointer;">&nbsp;&nbsp;Add css</span>
			<ul id="query_1" class="sortable" style="width:910px">

<?php
		$res = $DB->execute("SELECT id, name, description FROM gm_css WHERE id_application = ".$appID." ORDER BY name ASC");
		if($res){
			$r = 0;
			while ($row = $res->fetch_object()){
				$r++;
?>
			  <li style="background-color:#eeeeee; margin-bottom:1px;" title="<?php echo $row->id?>">
				<input name="id.1.<?php echo $r?>" type="hidden" value="<?php echo $row->id?>" class="editable" />
				&nbsp;name <input name="name.1.<?php echo $r?>" type="text" value="<?php echo $row->name?>" class="editable" />
				&nbsp;&nbsp;description <input name="desc.1.<?php echo $r?>" type="text" size="89" value="<?php echo $row->description?>" class="editable" />
				<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this css (id=<?php echo $row->id?>)"/>
<?php if ($authorized) { ?>
				<a href="index.php?appId=<?php echo $appID; ?>&duplicateid=<?php echo $row->id?>" onClick="return confirm('Do you really want to duplicate this css (id=<?php echo $row->id?>)?\nAll other chenges will be lost.');"><img src="/gm/img/duplicate.png" style="vertical-align:middle; cursor:pointer;" title="Duplicate this css (id=<?php echo $row->id?>)"/></a>
<?php } ?>
				<a href="css.php?id=<?php echo $row->id?>"><img src="/gm/img/forward.png" style="vertical-align:middle; cursor:pointer;" title="Select this css (id=<?php echo $row->id?>) to define its script"/></a>
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
	<button id="action" name="action" type="submit" value="save">Save css</button>
<?php } ?>
</form>

<script>
	//$(".sortable").sortable();

	$(".piece_addable").on('click', addQueryPiece);

	$(".piece_deletable").on('click', deleteQueryPiece);

	function deleteQueryPiece(e){
		var id = $(this).parent().attr('title');
		var cancelConfirm = '';
		if (id != 'new') cancelConfirm = confirm('Do you really want to cancel this css (id=' + id + ')?\nRemember to submit this form to remove it definitely.');
		else cancelConfirm = confirm('Do you really want to cancel this new css?\nRemember to submit this form to remove it definitely.');
		if (cancelConfirm == true) {
			if (id != 'new') {
				$("#idstodelete").attr('value', id + ',' + $("#idstodelete").attr('value'));
				// css
				$("#titlepage").html('CSS <span style="color:red">changed</span>');
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
		$('<img src="/gm/img/del.png" style="vertical-align:middle; cursor:pointer;" class="piece_deletable" title="Cancel this new css"/>').on('click', deleteQueryPiece).appendTo($('<li style="background-color:#ffffaa; margin-bottom:1px;" title="new">		<input name="id.' + i + '.' + max_j + '" type="hidden" value="" class="editable" />		&nbsp;name <input name="name.' + i + '.' + max_j + '" type="text" value="" class="editable" />		&nbsp;&nbsp;description <input name="desc.' + i + '.' + max_j + '" type="text" size="89" value="" class="editable" />	</li>').appendTo('#query_' + i));
		// css
		$("#titlepage").html('CSS <span style="color:red">changed</span>');
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

<?php
	}
?>

</body>
</html>