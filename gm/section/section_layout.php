<!DOCTYPE html>
<html>
<head>
  <script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
  <script src="/gm/js/jquery-ui-1.10.2/ui/jquery-ui.js"></script>
  <link rel="shortcut icon" type="image/x-icon" href="/gm/favicon.ico">
  <link rel="stylesheet" type="text/css" href="/gm/css/style.css" />
  <style id="dinamic_css" type="text/css">
  </style>
  <title>.: COORAD :.</title>
</head>
<body>
<?php
	require_once("../init.php");
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<h2>Sorry, no section selected</h2>';
	}
	else {
		$viewID = 0;
		if (isset($_GET['id'])) $viewID = $_GET['id'];
		else $viewID = $_POST['id'];
		
		// get appID from sectionID
		$appID = 0;
		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_application.context_path appContextPath FROM gm_section_view, gm_section, gm_application WHERE gm_application.id = gm_section.id_application AND gm_section.id = gm_section_view.id_section AND gm_section_view.id = ".$viewID);
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
		
		$section_width = 0;
		$section_height = 0;		
		// UPDATE section view
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			//echo $_POST['html']."<br />";
			//echo $_POST['htmlLayout']."<br />";
			$section_width = trim(str_replace("'","''",$_POST['section_width']));
			$section_height = trim(str_replace("'","''",$_POST['section_height']));
			//echo "<script>alert('".preg_replace('/[\r\t\n]/','',$_POST['html'])."');</script>";
			$DB->execute("UPDATE gm_section_view SET html='".preg_replace('/[\r\t\n]/','',str_replace("'","''",$_POST['html']))."', html_layout='".trim(str_replace("'","''",$_POST['htmlLayout']))."', width=".$section_width.", height=".$section_height." WHERE id=".$viewID);
			//echo "UPDATE gm_section_view SET html='".preg_replace('/[\r\t\n]/','',$_POST['html'])."', html_layout='".trim(str_replace("'","''",$_POST['htmlLayout']))."', width=".$section_width.", height=".$section_height." WHERE id=".$viewID;
		}

		// css
		$cssNameArray = array();
		$cssSrcArray = array();	
		$res = $DB->execute("SELECT id, name, css FROM gm_css WHERE id_application = ".$appID." ORDER BY name");
		if ($res) {
			while ($row = $res->fetch_object()) {
				$cssNameArray[$row->id] = $row->name;
				$cssSrcArray[$row->id] = str_replace("\$appContextPath", $appContextPath, $row->css);
			}
			$res->close();
		}
		$cssIdInSession = '0';
		if (isset($_SESSION['cssIdInSession'])) $cssIdInSession = $_SESSION['cssIdInSession'];
		if (isset($_GET['cssIdInSession'])) {
			$cssIdInSession = $_GET['cssIdInSession'];
			$_SESSION['cssIdInSession'] = $cssIdInSession;
		}
		else if (isset($_POST['cssIdInSession'])) {
			$cssIdInSession = $_POST['cssIdInSession'];
			$_SESSION['cssIdInSession'] = $cssIdInSession;
		}
		
		$res = $DB->execute("SELECT gm_section.id sectionId, gm_section.name sectionName, gm_section_view.view viewName, gm_section_view.html htmlSrc, gm_section_view.width sectionWidth, gm_section_view.height sectionHeight FROM gm_section, gm_section_view WHERE gm_section_view.id_section = gm_section.id and gm_section_view.id = ".$viewID);
		if (!$section = $res->fetch_object()) die('something wrong.');
		$sectionID = $section->sectionId;
		$sectionName = str_replace("'","&rsquo;",$section->sectionName);
		$viewName = str_replace("'","&rsquo;",$section->viewName);
		$htmlSrc = $section->htmlSrc;

		$doc = new DOMDocument();
		if (isset($htmlSrc) && $htmlSrc != '') {
			$doc->loadHTML($htmlSrc);
			$node = $doc->getElementsByTagName("a");           
			foreach($node as $node) {
				$node->setAttribute("onClick","return false;");
			}
			$node = $doc->getElementsByTagName("input");           
			foreach($node as $node) {
				$typeAttr = trim($node->getAttribute('type'));
				if (strpos($typeAttr, 'checkbox') !== 0) {
					$node->setAttribute("onClick","return false;");
				}
				else {
					$node->setAttribute("onFocus","body.focus();this.blur()");
				}
			}
			$node = $doc->getElementsByTagName("select");           
			foreach($node as $node) {
				$node->setAttribute("onFocus","body.focus();this.blur()");
			}
			$node = $doc->getElementsByTagName("textarea");           
			foreach($node as $node) {
				$node->setAttribute("onFocus","body.focus();this.blur()");
			}
			$node = $doc->getElementsByTagName("img");           
			foreach($node as $node) {
				$node->setAttribute("draggable","true");
			}
			$htmlSrc = urldecode($doc->saveHTML());
			$bodyTagPos = strpos($htmlSrc, '<body>');
			$htmlSrc = substr($htmlSrc, $bodyTagPos + 6);
			$bodyTagPos = strpos($htmlSrc, '</body>');
			$htmlSrc = substr($htmlSrc, 0, $bodyTagPos);
		}
		
		$section_width = $section->sectionWidth;
		$section_height = $section->sectionHeight;		
		$res->close();
?>

<div><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Sections</a> &gt; <a href="section_pre_init_query.php?id=<?php echo $sectionID?>">Pre Init Query</a> &gt; <a href="section_init_query.php?id=<?php echo $sectionID?>">Initialization Query Set</a> &gt; <a href="section_view_selection.php?id=<?php echo $sectionID?>">View Set</a> &gt; <a href="section_pre_layout.php?id=<?php echo $viewID?>">Pre Layout</a> &gt; Layout</div>
<h3 id="subtitlepage">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage">Section <i style="background-color:yellow;">"<?php echo $sectionName?>"</i> View <i style="background-color:yellow;">"<?php echo $viewName?>"</i> Layout
<span style="font-size:.6em">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;change view: <select id="view_select">
<?php

		$res = $DB->execute("SELECT id, view FROM gm_section_view WHERE id_section=".$sectionID." ORDER BY id ASC");
		if($res){
			while ($row = $res->fetch_object()) {
				if ($viewID == $row->id) echo '<option selected="selected" value="'.$row->id.'">'.$row->view.'</option>';
				else echo '<option value="'.$row->id.'">'.$row->view.'</option>';
			}
			$res->close();
		}

?>
</select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;css preview:<select id="css" name="css">
								<option value="0">-</option>
<?php
	foreach ($cssNameArray as $cssId => $cssName) {
		if ($cssId == $cssIdInSession) echo "<option value='".$cssId."' selected='selected'>".$cssName."</option>";
		else  echo "<option value='".$cssId."'>".$cssName."</option>";
	}
?>
</select>
</span>
</h2>

<div style="padding-bottom:10px;">
<a style="text-decoration:none; color:black; padding-right:742px;" href="section_pre_layout.php?id=<?php echo $viewID?>"><img src="/gm/img/backward.png" title="Back to Pre Layout"/>&nbsp;&nbsp;Back to Pre Layout</a>
<a style="text-decoration:none; color:black;" href="preview.php?section=<?php echo $sectionID ?>&view=<?php echo $viewID ?>" target="_blank">Show preview&nbsp;&nbsp;<img src="/gm/img/preview.png" title="Show preview"/></a>
</div>

	<!--div id="log">&nbsp;</div>
	<div id="log2">&nbsp;</div>
	<div>&nbsp;</div-->
	<div id="box" style="border:1px solid white; background-color:#ffffff; width:1002px; height:600px;">
		<div id="selected_element" style="padding-left:5px; padding-top:5px; background-color:#dddddd; width:997px; height:122px;">
			<h3>&nbsp;&nbsp;&nbsp;&nbsp;...add a new element.</h3>
		</div>
		<div id="container" style="border:1px dotted black; background-color:#eeeeee; width:1000px; height:400px; position:relative">
<?php
	echo $htmlSrc;
?>
		</div>
<!--script>alert('<?php echo $htmlSrc?>');</script-->
		<form id="form" action="section_layout.php" method="post">
			<table style="width:100%; border:solid 1px; color:#999999;">
				<tr>
					<td>
						<img id="basket" src="/gm/img/basket.png" style="width:40px; height:50px; vertical-align:middle; padding-left:5px; padding-right:20px; padding-top:5px;" title="Elimina"/>
						<select id="new_element" style="vertical-align:middle;">
							<option>add element...</option>
							<option>a</option>
							<option>img</option>
							<option>div</option>
							<option>text(input)</option>
							<option>password(input)</option>
							<option>hidden(input)</option>
							<option>checkbox(input)</option>
							<option>file(input)</option>
							<option>select</option>
							<option>textarea</option>
							<option>iframe</option>
							<option>submit(button)</option>
							<option>button(button)</option>
							<option>reset(button)</option>
						</select>
						<input id="html" name="html" type="hidden" value="" />
						<input id="htmlLayout" name="htmlLayout" type="hidden" value="" />
						<input name="id" type="hidden" value="<?php echo $viewID?>" />
						<!--input id="command" name="command" type="text" value="aaa" /-->
						<input id="cssIdInSession" name="cssIdInSession" type="hidden" value="<?php echo $cssIdInSession?>" />
					</td>
					<td style="text-align:center;">
						<div>width:<input id="section_width" name="section_width" size="4" type="text" value="<?php echo $section_width?>"/>px</div>
						<div>height:<input id="section_height" name="section_height" size="4" type="text" value="<?php echo $section_height?>"/>px</div>
					</td>
					<td style="text-align:right; padding-right:5px;">
						<!--input id="action" type="action" value="Salva" /-->
<?php if ($authorized) { ?>
						<button id="action" name="action" type="submit" value="save">Save this section layout</button>
<?php } ?>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<!--div id="elements_list">&nbsp;</div-->

<?php
		// Recupero delle "action"
		$doc = new DOMDocument();
		if (isset($htmlSrc) && $htmlSrc != '') {
			$doc->loadHTML($htmlSrc);
			// tag BUTTON
			$node = $doc->getElementsByTagName("button");
			echo '<div>ACTIONS:</div>';
			foreach($node as $node) {
				$name = trim($node->getAttribute('name'));
				$value = trim($node->getAttribute('value'));
				echo '<div>'.$name.'='.$value.'</div>';
			}
		}
?>

<script>
		
	var positionbox = $("#box").offset();
    	
	$("#box").on('mousemove',function(e){
	  if (isMouseDown) {
		$(objDragged).offset({ top: Math.round((e.pageY - objOffsetTop) / 5) * 5, left: Math.round((e.pageX - objOffsetLeft) / 5) * 5 });
		if (e.pageX >= basketPosition.left && e.pageX <= basketPosition.left + $("#basket").width() && e.pageY >= basketPosition.top && e.pageY <= basketPosition.top + $("#basket").height()) $("#basket").attr("src", "/gm/img/basket_red.png");
		else $("#basket").attr("src", "/gm/img/basket.png");
	  }
	});

	var basketPosition = $("#basket").offset();
	//$("#log").text("basket.pageX: " + basketPosition.left + ", basket.pageY: " + basketPosition.top + ", basket.width: " + $("#basket").width() + ", basket.height: " + $("#basket").height());
	
	var isMouseDown = false;
	var objPosition = $("#box").offset();
	var objDragged = '';
	var objOffsetTop = 0;
	var objOffsetLeft = 0;
		
	$(".gm").on('mousedown',drag);
	
	$(".gm").on('mouseup',drop);
		
	function drag(e){
		isMouseDown = true;
		objDragged = $(this);
		//$("#log").text($(this).get(0).tagName + " " + $(this).attr("id"));
		//$(".gm").css("background-color", "#ffffff");
		//$(this).css("background-color", "#ffff00");
		objPosition = $(this).offset();
		objOffsetLeft = e.pageX - objPosition.left;
		objOffsetTop = e.pageY - objPosition.top;
		//$("#log").text("e.pageX: " + objOffsetLeft + ", e.pageY: " + objOffsetTop);
		var tagDragged = $(this).get(0).tagName;
		var typeDragged = $(this).get(0).type;
		if ($(this).attr("class") == "gm gm_hidden ") typeDragged = 'hidden';
		if ($(this).attr("class").indexOf("gm gm_iframe ") == 0) tagDragged = 'IFRAME';
		$("#selected_element").html('');
		if (tagDragged == 'A') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;a&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="50" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;label:</strong><input id="selected_element_label" type="text" size="45" value="' + $(this).text() + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', keyupText).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>href:</strong><input id="selected_element_href" type="text" size="100" value="' + $(this).attr("href") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'href'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			
			//$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;target:</strong><input id="selected_element_target" type="text" size="5" value="' + $(this).attr("target") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'target'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;target:</strong><select id="selected_element_target"><select>').on('change', changeTarget).appendTo("#selected_element");
			if ($(this).attr("target") == '_self')
				$('<option selected="selected">_self</option>').appendTo("#selected_element_target");
			else $('<option>_self</option>').appendTo("#selected_element_target");
			if ($(this).attr("target") == '_blank')
				$('<option selected="selected">_blank</option>').appendTo("#selected_element_target");
			else $('<option>_blank</option>').appendTo("#selected_element_target");
			if ($(this).attr("target") == '_top')
				$('<option selected="selected">_top</option>').appendTo("#selected_element_target");
			else $('<option>_top</option>').appendTo("#selected_element_target");
			if ($(this).attr("target") == '_parent')
				$('<option selected="selected">_parent</option>').appendTo("#selected_element_target");
			else $('<option>_parent</option>').appendTo("#selected_element_target");
			
			//$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;bg-color:</strong><input id="selected_element_bg-color" type="color" style="vertical-align:bottom" value="' + rgb2hex($(this).css("background-color")) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('change', {value: 'background-color'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="125" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;color:</strong><input id="selected_element_color" type="color" style="vertical-align:bottom" value="' + rgb2hex($(this).css("color")) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('change', {value: 'color'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
			objDragged.attr('onClick', 'return false;');
		}
		else if (tagDragged == 'IMG') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;img&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="62" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" size="62" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>src:</strong><input id="selected_element_src" type="text" size="100" value="' + $(this).attr("src") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'src'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;alt:</strong><input id="selected_element_src" type="text" size="39" value="' + $(this).attr("alt") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'alt'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="145" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
		}
		else if (tagDragged == 'DIV') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;div&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="90" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" size="35" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>label:</strong><input id="selected_element_label" type="text" size="100" value="' + $(this).text() + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', keyupText).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			//$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;bg-color:</strong><input id="selected_element_bg-color" type="color" style="vertical-align:bottom" value="' + rgb2hex($(this).css("background-color")) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('change', {value: 'background-color'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="125" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;color:</strong><input id="selected_element_color" type="color" style="vertical-align:bottom" value="' + rgb2hex($(this).css("color")) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('change', {value: 'color'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
		}
		else if (tagDragged == 'INPUT' && (typeDragged == 'text' || typeDragged == 'password')) {
			$('<div>').appendTo("#selected_element");
			if (typeDragged == 'text') $('<strong>Tag:&lt;' + typeDragged + '(input)&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="42" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			else $('<strong>Tag:&lt;' + typeDragged + '(input)&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="36" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;name:</strong><input id="selected_element_name" type="text" size="42" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>value:</strong><input id="selected_element_value" type="text" size="100" value="' + $(this).attr("value") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'value'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;size:</strong><input id="selected_element_size" type="text" size="3" value="' + $(this).attr("size") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'size'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;maxlength:</strong><input id="selected_element_maxlength" type="text" size="3" value="' + $(this).attr("maxlength") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'maxlength'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" size="148" type="text" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
			objDragged.attr('onFocus', 'body.focus();this.blur()');
		}
		else if (tagDragged == 'INPUT' && typeDragged == 'hidden') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;hidden(input)&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="57" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;name:</strong><input id="selected_element_name" type="text" size="57" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>value:</strong><input id="selected_element_value" type="text" size="100" value="' + $(this).attr("value") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'value'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			objDragged.attr('onFocus', 'body.focus();this.blur()');
		}
		else if (tagDragged == 'INPUT' && typeDragged == 'checkbox') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;checkbox(input)&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="57" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" size="57" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>name:</strong><input id="selected_element_name" type="text" size="144" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			//$('<strong>checked:</strong><input id="selected_element_checked" type="checkbox" ' + $(this).attr("checked") + '/>&nbsp;&nbsp;&nbsp;&nbsp;').on('click', {value: 'checked'}, keyupAttr).appendTo("#selected_element");
			$('<strong>checked if:</strong><input id="selected_element_checked" type="text" size="90" value="' + $(this).attr("default") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'default'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="140" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
			objDragged.attr('onClick', 'return false;');
		}
		else if (tagDragged == 'INPUT' && typeDragged == 'file') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;file(input)&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" size="66" type="text" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" size="55" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>name:</strong><input id="selected_element_name" type="text" size="70" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;accept:</strong><input id="selected_element_accept" type="text" size="65" value="' + $(this).attr("accept") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'accept'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="70" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
			objDragged.attr('onFocus', 'body.focus();this.blur()');
		}
		else if (tagDragged == 'SELECT') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;select&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="45" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;name:</strong><input id="selected_element_name" type="text" size="45" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>options:</strong><input id="selected_element_option" type="text" size="35" value="' + $(this).children().text() + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', keyupOptionText).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;values:</strong><input id="selected_element_value" type="text" size="35" value="' + $(this).children().attr("value") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', keyupOptionValue).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;default:</strong><input id="selected_element_value" type="text" size="47" value="' + $(this).attr("default") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'default'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;size:</strong><input id="selected_element_size" type="text" size="3" value="' + $(this).attr("size") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'size'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;multiple:</strong><input id="selected_element_multiple" type="checkbox" ' + $(this).attr("multiple") + '/>&nbsp;&nbsp;&nbsp;&nbsp;').on('click', {value: 'multiple'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="70" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
			objDragged.attr('onFocus', 'body.focus();this.blur()');
		}
		else if (tagDragged == 'TEXTAREA') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;textarea&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;name:</strong><input id="selected_element_name" type="text" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>value:</strong><input id="selected_element_value" type="text" size="100" value="' + $(this).text() + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', keyupTextEvenEmpty).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;rows:</strong><input id="selected_element_rows" type="text" size="3" value="' + $(this).attr("rows") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'rows'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;cols:</strong><input id="selected_element_cols" type="text" size="3" value="' + $(this).attr("cols") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'cols'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
			objDragged.attr('onFocus', 'body.focus();this.blur()');
		}
		else if (tagDragged == 'IFRAME') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;iframe&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="62" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" size="62" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>src:</strong><input id="selected_element_src" type="text" size="100" value="' + $(this).attr("src") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'src'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;alt:</strong><input id="selected_element_src" type="text" size="39" value="' + $(this).attr("alt") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'alt'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm gm_iframe <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(13) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'classiframe'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="145" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
		}
		else if (tagDragged == 'BUTTON') {
			$('<div>').appendTo("#selected_element");
			$('<strong>Tag:&lt;' + typeDragged + '(button)&gt;</strong><strong>&nbsp;&nbsp;&nbsp;&nbsp;id:</strong><input id="selected_element_id" type="text" size="45" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;title:</strong><input id="selected_element_title" type="text" value="' + $(this).attr("title") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'title'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;name:</strong><input id="selected_element_name" type="text" size="35" value="' + $(this).attr("name") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'name'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>label:</strong><input id="selected_element_label" type="text" size="70" value="' + $(this).text() + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', keyupText).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;value:</strong><input id="selected_element_value" size="67" type="text" value="' + $(this).attr("value") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'value'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>class:</strong> gm <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(3) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			//$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;bg-color:</strong><input id="selected_element_bg-color" type="color" style="vertical-align:bottom" value="' + rgb2hex($(this).css("background-color")) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('change', {value: 'background-color'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>display if:</strong><input id="selected_element_displayif" type="text" size="125" value="' + $(this).attr("displayif") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'displayif'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;color:</strong><input id="selected_element_color" type="color" style="vertical-align:bottom" value="' + rgb2hex($(this).css("color")) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('change', {value: 'color'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");		
		}
	}
	
	function rgb2hex(rgb) {
		rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
		function hex(x) {
			return ("0" + parseInt(x).toString(16)).slice(-2);
		}
		return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
	}	
	
	function drop(e){
		isMouseDown = false;
		//$("#log2").text("e.pageX: " + e.pageX + ", e.pageY: " + e.pageY);
		if (e.pageX >= basketPosition.left && e.pageX <= basketPosition.left + $("#basket").width() && e.pageY >= basketPosition.top && e.pageY <= basketPosition.top + $("#basket").height()) $(this).detach();
		$("#basket").attr("src", "/gm/img/basket.png");
		//$("#elements_list").text($("#container").html());
		// calcolo automatico della dimensione della section
		var section_width = 0;
		var section_height = 0;
		//alert('drop');
		$(".gm").each(function () {
			var element_width = $(this).css("width");
			var element_height = $(this).css("height");
			var element_top = $(this).css("top");
			var element_left = $(this).css("left");
			//alert(element_width);
			element_width = parseInt(element_width.replace(/px/g, ""));
			element_height = parseInt(element_height.replace(/px/g, ""));
			element_top = parseInt(element_top.replace(/px/g, ""));
			element_left = parseInt(element_left.replace(/px/g, ""));
			if (section_width < element_width + element_left) section_width = element_width + element_left;
			if (section_height < element_height + element_top) section_height = element_height + element_top;
			//alert(section_width + " " + element_width);
		});
		$("#section_width").attr('value',section_width);
		$("#section_height").attr('value',section_height);
	}

	function keyupAttr(e){
		/*if (e.data.value == 'checked') {
			//alert($("#selected_element_checked").prop('checked'));
			if ($("#selected_element_checked").prop('checked')) {
				objDragged.prop('checked', true);
				objDragged.attr('checked', 'checked');
			}
			else objDragged.removeAttr('checked');
		}
		else*/ if (e.data.value == 'multiple') {
			//alert($("#selected_element_multiple").prop('checked'));
			if ($("#selected_element_multiple").prop('checked')) {
				objDragged.prop('multiple', true);
				objDragged.attr('multiple', 'multiple');
			}
			else objDragged.removeAttr('multiple');
		}
		else if (e.data.value == 'class') {
			var str = $(this).val();
			str = str.replace(/"/g, "'");
			if ($(this).val() != str) $(this).val(str);
			objDragged.attr(e.data.value, 'gm ' + str);
		}
		else if (e.data.value == 'classiframe') {
			var str = $(this).val();
			str = str.replace(/"/g, "'");
			if ($(this).val() != str) $(this).val(str);
			objDragged.attr('class', 'gm gm_iframe ' + str);
		}		
		else {
			//if ($(this).val() == '') $(this).val('?');
			var str = $(this).val();
			str = str.replace(/"/g, "'");
			if ($(this).val() != str) $(this).val(str);
			objDragged.attr(e.data.value, str);
		}
	}

	function keyupCss(e){
		//alert($(this).val());
		var str = $(this).val();
		str = str.replace(/"/g, "'");
		if ($(this).val() != str) $(this).val(str);
		objDragged.css(e.data.value, str);
	}

	function keyupText(e){
		//if ($(this).val() == '') $(this).val('?');
		var str = $(this).val();
		if (str == '') str = "?";
		str = str.replace(/"/g, "'");
		if ($(this).val() != str) $(this).val(str);
		objDragged.text(str);
	}
	
	function keyupTextEvenEmpty(e){
		//if ($(this).val() == '') $(this).val('?');
		var str = $(this).val();
		str = str.replace(/"/g, "'");
		if ($(this).val() != str) $(this).val(str);
		objDragged.text(str);
	}
	
	function keyupOptionText(e){
		var str = $(this).val();
		str = str.replace(/"/g, "'");
		if ($(this).val() != str) $(this).val(str);
		objDragged.children().text(str);
	}

	function keyupOptionValue(e){
		var str = $(this).val();
		str = str.replace(/"/g, "'");
		if ($(this).val() != str) $(this).val(str);
		objDragged.children().attr("value", str);
	}

	function changeTarget(e) {
		var str = $(this).val();
		objDragged.attr('target', str);
	}
	
	$("#new_element").change(function(){
		var newElement = $("#new_element option:selected").text();
		//$("#log2").text("changed " + $("select option:selected").text());
		var id = prompt(newElement + " ID:");
		if (id != null) {
			if (id == '') id = '?';
			//$('<' + newElement + ' id="' + id + '" name="' + id + '" class="gm " style="cursor:move; width:50px; height:20px;"></' + newElement + '>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			if (newElement == 'a') $('<a id="' + id + '" class="gm " style="cursor:move; " onClick="return false;" href="#" title="" displayif="">' + id + '</a>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'img') $('<img id="' + id + '" class="gm " style="cursor:move; " src="#" title="" alt="" draggable="true" displayif="" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'div') $('<div id="' + id + '" class="gm " style="cursor:move; " title="" displayif="">' + id + '</div>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'text(input)') $('<input id="' + id + '" name="' + id + '" class="gm " type="text" style="cursor:move; " title="" size="" maxlength="" onFocus="body.focus();this.blur()" value="' + id + '" displayif="" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'password(input)') $('<input id="' + id + '" name="' + id + '" class="gm " type="password" style="cursor:move; " title="" size="" maxlength="" onFocus="body.focus();this.blur()" value="' + id + '" displayif="" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'hidden(input)') $('<input id="' + id + '" name="' + id + '" class="gm gm_hidden " type="text" style="cursor:move; background-color:#dddddd;" onFocus="body.focus();this.blur()" value="' + id + '" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'checkbox(input)') $('<input id="' + id + '" name="' + id + '" class="gm " type="checkbox" style="cursor:move; " title="" default="" onClick="return false;" displayif="" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'file(input)') $('<input id="' + id + '" name="' + id + '" class="gm " type="file" style="cursor:move;" title="" accept="" onFocus="body.focus();this.blur()" onClick="return false;" displayif="" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'select') $('<select id="' + id + '" name="' + id + '" class="gm " style="cursor:move; " title="" size="" default="" onFocus="body.focus();this.blur()" displayif=""><option value="">' + id + '</option></select>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'textarea') $('<textarea id="' + id + '" name="' + id + '" class="gm " style="cursor:move; " title="" rows="" cols="" onFocus="body.focus();this.blur()" displayif="">' + id + '</textarea>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'iframe') $('<img id="' + id + '" class="gm gm_iframe " style="cursor:move; " src="#" title="" alt="" draggable="true" displayif="" />').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'submit(button)') $('<button id="' + id + '" name="' + id + '" class="gm " type="submit" style="cursor:move; " title="" value="' + id + '" displayif="">' + id + '</button>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'button(button)') $('<button id="' + id + '" name="' + id + '" class="gm " type="button" style="cursor:move; " title="" value="' + id + '" displayif="">' + id + '</button>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else if (newElement == 'reset(button)') $('<button id="' + id + '" name="' + id + '" class="gm " type="reset" style="cursor:move; " title="" value="' + id + '" displayif="">' + id + '</button>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			else alert('L\'elemento selezionato non pu&ograve; essere aggiunto.');
		}
		$("#new_element option:selected").removeAttr("selected");
		$("#new_element option").eq(0).attr("selected", "selected");
	});

	$("#view_select").change(function(){
		//alert('ok');
		var viewIdSelected = $("#view_select option:selected").attr('value');
		//alert(viewIdSelected);
		window.location.href = "section_layout.php?id=" + viewIdSelected;
	});
	
	$("#css").change(function(){
		var cssSelected = $("#css option:selected").attr('value');
		//alert(cssSelected);
		if (cssSelected == '0') {
			$('#dinamic_css').text('');
			$('#cssIdInSession').attr('value','0');
		}
		else {
			// <>@!#$%^&*()_+[]{}?:;|'\"\\,./~`-=
			<?php foreach ($cssNameArray as $cssId => $cssName) { ?>
				if (cssSelected == '<?php echo $cssId ?>') {
					$('#dinamic_css').text('<?php echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$cssSrcArray[$cssId])))))))))))))))))))))))))))) ?>');
					$('#cssIdInSession').attr('value','<?php echo $cssId ?>');
				}
			<?php } ?>
		}
	});

	<?php if ($cssIdInSession != 0 && array_key_exists($cssIdInSession, $cssNameArray)) { ?>
		$('#dinamic_css').text('<?php echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$cssSrcArray[$cssIdInSession])))))))))))))))))))))))))))) ?>');
	<?php } ?>
	
	$("#action").click(function() {
		var gmTabindexArray = [];
		var gmNodeArray = [];
		$(".gm").each(function () {
			//alert($(this).text());
			//$(this).css("cursor", "");
			$(this).removeAttr("onClick");
			$(this).removeAttr("onFocus");
			$(this).removeAttr("draggable");			
			objPosition = $(this).offset();
			$(this).attr("tabindex", objPosition.top * 100 + objPosition.left);
			gmTabindexArray.push(objPosition.top * 100 + objPosition.left);
			gmNodeArray.push($(this));
		});
		gmTabindexArray.sort();
		$("#container").html('');
		for (var i = 0; i < gmTabindexArray.length; i++) {
			var sectionObject = '';
			for (var j = 0; j < gmNodeArray.length; j++) {
				if (gmTabindexArray[i] == $(gmNodeArray[j]).attr("tabindex")) {
					sectionObject = $(gmNodeArray[j]);
				}
				//alert(gmTabindexArray[i] + ' - ' + $(sectionObject).attr("tabindex"));
			}
			//alert(sectionObject + ' ' + $(sectionObject).attr("id"));
			sectionObject.removeAttr("tabindex");
			$("#container").append(sectionObject);
		}

		$("#html").attr("value", $("#container").html());
		
		$(".gm").each(function () {
			$(this).css("cursor", "");
			//alert($(this).attr('class'));
		});
		$(".gm_hidden").each(function () {
			$(this).attr("type", "hidden");
			//$(this).removeAttr("tabindex");
		});
		
		$(".gm_iframe").each(function () {
		    //alert($(this).attr('class'));
			var iframeHtml = $(this).get(0).outerHTML.replace(/^<img/, "<iframe").replace(/^<\/img/, "</iframe");
			//alert(iframeHtml);
			$(this).replaceWith(iframeHtml);
		});
		
		$("#htmlLayout").attr("value", $("#container").html());
		//return false;
	});
	
</script>

<?php
	}
	/*eval('?> <div>ciao <?php echo "xian"; ?></div> <?php');*/
?>

</body>
</html>