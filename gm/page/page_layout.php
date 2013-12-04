<!DOCTYPE html>
<html>
<head>
  <script src="/gm/js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
  <script src="/gm/js/jquery-ui-1.10.2/ui/jquery-ui.js"></script>
  <link rel="shortcut icon" type="image/x-icon" href="/gm/favicon.ico">
  <link rel="stylesheet" type="text/css" href="/gm/css/style.css" />
  <style type="text/css">
	.gm_section {
		border:1px dotted black;
	}
  </style>
  <style id="dinamic_css" type="text/css">
  </style>
  <title>.: COORAD :.</title>
</head>
<body style="margin-left:0px; margin-right:0px; margin-bottom:0px;">
<?php
	require_once("../init.php");
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<h2>Sorry, no page selected</h2>';
	}
	else {
		$pageID = 0;
		if (isset($_GET['id'])) $pageID = $_GET['id'];
		else $pageID = $_POST['id'];

		// get appID from pageID
		$appID = 0;
		$appName = '';
		$appContextPath = '';
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_application.context_path appContextPath FROM gm_page, gm_application WHERE gm_application.id = gm_page.id_application AND gm_page.id = ".$pageID);
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
		
		$page_width = 0;
		$page_height = 0;
		$page_align = 'center';
		$page_id_css = 0;
		$page_id_javascript = 0;
		// UPDATE page view
		if (isset($_POST['action']) && $_POST['action'] == 'save' && $authorized) {
			//echo $_POST['html']."<br />";
			//echo $_POST['htmlLayout']."<br />";
			$page_width = trim(str_replace("'","''",$_POST['page_width']));
			$page_height = trim(str_replace("'","''",$_POST['page_height']));
			$page_align = trim(str_replace("'","''",$_POST['page_align']));
			$page_is_template = 0;
			if (isset($_POST['is_template'])) $page_is_template = 1;
			$page_use_template = '';
			if (isset($_POST['use_template'])) $page_use_template = $_POST['use_template'];
			if (isset($_POST['css'])) $page_id_css = $_POST['css'];
			if (isset($_POST['javascript'])) $page_id_javascript = $_POST['javascript'];
			//echo '<script>alert(\''.preg_replace('/[\r\t\n]/','',$_POST['html']).'\');</script>';
			$DB->execute("UPDATE gm_page SET html='".trim(str_replace("'","''",preg_replace('/[\r\t\n]/','',$_POST['html'])))."', width=".$page_width.", height=".$page_height.", align='".$page_align."', is_template=".$page_is_template.", use_template='".$page_use_template."', id_css=".$page_id_css.", id_javascript=".$page_id_javascript." WHERE id=".$pageID);
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
		// javascript
		$javascriptNameArray = array();
		$javascriptSrcArray = array();	
		$res = $DB->execute("SELECT id, name, javascript FROM gm_javascript WHERE id_application = ".$appID." ORDER BY name");
		if ($res) {
			while ($row = $res->fetch_object()) {
				$javascriptNameArray[$row->id] = $row->name;
				$javascriptSrcArray[$row->id] = $row->javascript;
			}
			$res->close();
		}
		// template/page
		$templateNameArray = array();
		$templateHtmlArray = array();
		$templateWidthArray = array();
		$templateHeightArray = array();
		$templateAlignArray = array();
		$res = $DB->execute("SELECT name, html, width, height, align, id_css, id_javascript FROM gm_page WHERE id_application = ".$appID." AND is_template = 1 AND id <> ".$pageID);
		if ($res) {
			while ($row = $res->fetch_object()) {
				$templateNameArray[] = $row->name;
				$templateHtmlArray[$row->name] = $row->html;
				$templateWidthArray[$row->name] = $row->width;
				$templateHeightArray[$row->name] = $row->height;
				$templateAlignArray[$row->name] = $row->align;
				$templateCssArray[$row->name] = $row->id_css;
				$templateJavascriptArray[$row->name] = $row->id_javascript;
			}
			$res->close();
		}
		$res = $DB->execute("SELECT id, name, html, width, height, align, is_template, use_template, is_home, id_css, id_javascript FROM gm_page WHERE id = ".$pageID);
		if (!$page = $res->fetch_object()) die('something wrong.');
		$pageID = $page->id;
		$pageName = str_replace("'","&rsquo;",$page->name);
		$htmlSrc = $page->html;
		$page_width = $page->width;
		$page_height = $page->height;		
		$page_align = $page->align;		
		$page_is_template = $page->is_template;		
		$page_use_template = $page->use_template;		
		$page_id_css = $page->id_css;		
		$page_id_javascript = $page->id_javascript;		
		$page_is_home = $page->is_home;		
		$res->close();
		if ($page_is_template == 0 && $page_use_template != '') { 
			if (isset($templateHtmlArray[$page_use_template])) {
				$htmlSrc = str_replace("gm_section","gm_section_template",$templateHtmlArray[$page_use_template]).$htmlSrc;
			}
			if (isset($templateWidthArray[$page_use_template])) $page_width = $templateWidthArray[$page_use_template];
			if (isset($templateHeightArray[$page_use_template])) $page_height = $templateHeightArray[$page_use_template];
			if (isset($templateAlignArray[$page_use_template])) $page_align = $templateAlignArray[$page_use_template];
			if (isset($templateCssArray[$page_use_template])) $page_id_css = $templateCssArray[$page_use_template];
			if (isset($templateJavascriptArray[$page_use_template])) $page_id_javascript = $templateJavascriptArray[$page_use_template];
		}
		$sectionNameArray = array();
		$sectionWidthArray = array();
		$sectionHeightArray = array();
		$sectionViewArrayOfArray = array();
		$sectionHtmlArrayOfArray = array();
		$res = $DB->execute("SELECT gm_section.id id, gm_section.name name, MAX(gm_section_view.width) width, MAX(gm_section_view.height) height FROM gm_section, gm_section_view WHERE gm_section.id_application = ".$appID." AND gm_section.id = gm_section_view.id_section GROUP BY gm_section_view.id_section ORDER BY gm_section.name");
		if($res){
			while ($row = $res->fetch_object()) {
				$sectionNameArray[$row->id] = $row->name;
				$sectionWidthArray[$row->id] = $row->width;
				$sectionHeightArray[$row->id] = $row->height;
				$sectionViewArrayOfArray[$row->id] = array();
				$sectionHtmlArrayOfArray[$row->id] = array();
			}
			$res->close();
		}
		$res = $DB->execute("SELECT gm_section.id id, gm_section.name name, gm_section_view.view view, gm_section_view.html html FROM gm_section, gm_section_view WHERE gm_section.id_application = ".$appID." AND gm_section.id = gm_section_view.id_section");
		if($res){
			while ($row = $res->fetch_object()) {
				$sectionViewArrayOfArray[$row->id][] = $row->view;
				//$sectionHtmlArrayOfArray[$row->id][] = $row->html;

				// inserimento di onclick, onfocus e draggable nei tag della section
				$doc = new DOMDocument();
				if ($row->html != '') $doc->loadHTML($row->html);
				$node = $doc->getElementsByTagName("a");           
				foreach($node as $node) {
					$node->setAttribute("onClick","return false;");
				}
				$node = $doc->getElementsByTagName("input");           
				foreach($node as $node) {
					$typeAttr = trim($node->getAttribute('type'));
					if (strpos($typeAttr, 'checkbox') !== false) {
						$node->setAttribute("onClick","return false;");						
					}
					else {
						$node->setAttribute("onFocus","body.focus();this.blur()");
						if (strpos($typeAttr, 'file') !== false) $node->setAttribute("onClick","return false;");
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
				// salvataggio
				$sectionHtmlWithDisableTag = urldecode($doc->saveHTML());
				$bodyTagPos = strpos($sectionHtmlWithDisableTag, '<body>');
				$sectionHtmlWithDisableTag = substr($sectionHtmlWithDisableTag, $bodyTagPos + 6);
				$bodyTagPos = strpos($sectionHtmlWithDisableTag, '</body>');
				$sectionHtmlArrayOfArray[$row->id][] = substr($sectionHtmlWithDisableTag, 0, $bodyTagPos);			

			}
			$res->close();
		}
		
		// inserimento delle section nella page
		// DOM
		$htmlPageToShow = '';
		if ($htmlSrc != '') {
			$doc = new DOMDocument();
			$doc->loadHTML($htmlSrc);
			$node = $doc->getElementsByTagName("div");  
			foreach ($node as $node) {
				// sposto di -1,-1 il vertice top,left per poi riaggiungerlo in fase di salvataggio
				$pagenodeStyle = $node->getAttribute('style');
				$pagenodeStyle = str_replace(" ","",$pagenodeStyle);
				$pagenodeStyleItem = array();
				$pagenodeStyleArray = explode(";", $pagenodeStyle);
				foreach ($pagenodeStyleArray as $pagenodeStyleArrayEl) {
					$pagenodeStyleArrayElArray = explode(":", $pagenodeStyleArrayEl);
					if (count($pagenodeStyleArrayElArray) == 2) {
						$pagenodeStyleItem[$pagenodeStyleArrayElArray[0]] = str_replace("px","",str_replace(";","",$pagenodeStyleArrayElArray[1]));
					}
				}
				$pagenodeStyle = preg_replace('/top:[^;]*;/', 'top: '.($pagenodeStyleItem['top'] - 1).'px;', $pagenodeStyle);
				$pagenodeStyle = preg_replace('/left:[^;]*;/', 'left: '.($pagenodeStyleItem['left'] - 1).'px;', $pagenodeStyle);
				$node->setAttribute('style', $pagenodeStyle);
			
				$divClass = trim($node->getAttribute('class'));
				if (strpos($divClass, 'gm_section') >= 0) {
					if (strpos($divClass, 'gm_section_template') === false) $node->setAttribute('style',($node->getAttribute('style').' cursor:move;'));
					$divTitle = trim($node->getAttribute('title')); // coincide col l'ID della SECTION
					//echo $sectionHtmlArrayOfArray[$divTitle][0];
					$frag = $doc->createDocumentFragment(); // create fragment
					//$sectionHtml = urlencode($sectionHtmlArrayOfArray[$divTitle][0]);
					if (isset($sectionHtmlArrayOfArray[$divTitle][0])) {
						$sectionHtml = urlencode(str_replace('onclick=""','onClick="return false;"',str_replace('onfocus=""','onFocus="body.focus();this.blur();"',str_replace("'","'",$sectionHtmlArrayOfArray[$divTitle][0]))));
						if ($sectionHtml != '') {
							$frag->appendXML($sectionHtml); // insert arbitary html into the fragment
							$node->appendChild($frag); // stuff the fragment into the original tree
						}
					}
				}
			}
			// primo salvataggio
			$htmlPageToShow = urldecode($doc->saveHTML());
		}
		
		// inserimento delle section nelle template pages
		// DOM
		$templateWithSectionsHtmlArray = array();
		foreach ($templateNameArray as $templateName) {
			if ($templateHtmlArray[$templateName] != '') {
				$doc = new DOMDocument();
				$doc->loadHTML(str_replace("gm_section","gm_section_template",$templateHtmlArray[$templateName]));
				$node = $doc->getElementsByTagName("div");  
				foreach ($node as $node) {
					$divClass = trim($node->getAttribute('class'));
					if (strpos($divClass, 'gm_section_template') >= 0) {
						$divTitle = trim($node->getAttribute('title')); // coincide col l'ID della SECTION
						//echo $sectionHtmlArrayOfArray[$divTitle][0];
						$frag = $doc->createDocumentFragment(); // create fragment
						//$sectionHtml = urlencode($sectionHtmlArrayOfArray[$divTitle][0]);
						$sectionHtml = urlencode(str_replace('onclick=""','onClick="return false;"',str_replace('onfocus=""','onFocus="body.focus();this.blur();"',str_replace("'","'",$sectionHtmlArrayOfArray[$divTitle][0]))));
						if ($sectionHtml != '') {
							$frag->appendXML($sectionHtml); // insert arbitary html into the fragment
							$node->appendChild($frag); // stuff the fragment into the original tree
						}
					}
				}
				// primo salvataggio
				$templateWithSectionsHtmlArray[$templateName] = urldecode($doc->saveHTML());
				$bodyTagPos = strpos($templateWithSectionsHtmlArray[$templateName], '<body>');
				$templateWithSectionsHtmlArray[$templateName] = substr($templateWithSectionsHtmlArray[$templateName], $bodyTagPos + 6);
				$bodyTagPos = strpos($templateWithSectionsHtmlArray[$templateName], '</body>');
				$templateWithSectionsHtmlArray[$templateName] = substr($templateWithSectionsHtmlArray[$templateName], 0, $bodyTagPos);
				//echo $templateWithSectionsHtmlArray[$templateName];				
			}
		}
?>

<div style="margin-left:8px;"><a href="../index.php">Home</a> &gt; <a href="../application/?appId=<?php echo $appID; ?>">Configuration</a> &gt; <a href="index.php?appId=<?php echo $appID; ?>">Pages</a> &gt; <a href="page_command.php?id=<?php echo $pageID ?>">Commands</a> &gt; Layout</div>
<h3 id="subtitlepage" style="margin-left:8px;">Application <?php if ($appID != 0) echo '<i style="background-color:yellow;">"'.$appName.'"</i>'; ?></h3>
<h2 id="titlepage" style="margin-left:8px;">Page <i style="background-color:yellow;">"<?php echo $pageName?>"</i> Layout</h2>

<div style="padding-bottom:3px;">
<table style="width:100%"><tr><td style="text-align:left;">
<a style="text-decoration:none; color:black;" href="page_command.php?id=<?php echo $pageID ?>"><img src="/gm/img/backward.png" title="Back to Commands"/>&nbsp;&nbsp;Back to Commands</a>
</td><td style="text-align:right;">
<a style="text-decoration:none; color:black;" href="/gm/deploy/<?php echo $appContextPath; ?>/debug.php?page=<?php echo $pageName ?>" target="_blank">Show preview&nbsp;&nbsp;<img src="/gm/img/preview.png" title="Show preview"/></a>
</td></tr></table>
</div>

	<!--div id="log">&nbsp;</div>
	<div id="log2">&nbsp;</div>
	<div>&nbsp;</div-->
	<div id="box" style="background-color:#eeeeee; width:100%; text-align:<?php echo $page_align?>; font-size:0px;">
		<div id="selected_element" style="padding-left:5px; padding-top:5px; background-color:#dddddd; height:59px; text-align:left; font-size:14px;">
			<h3>&nbsp;&nbsp;&nbsp;&nbsp;...add a section.</h3>
		</div>
		<form id="form" action="page_layout.php" method="post" style=" font-size:14px;">
			<table style="width:100%; border:solid 1px; color:#999999; background-color:#ffffff;">
				<tr>
					<td style="text-align:left;">
						<img id="basket" src="/gm/img/basket.png" style="width:40px; height:50px; vertical-align:middle; padding-left:5px; padding-right:20px; padding-top:5px;" title="Elimina"/>
						<select id="new_element" style="vertical-align:middle;">
							<option>add section...</option>
<?php
	foreach ($sectionNameArray as $key => $value) {
		echo "<option value='".$key."'>".$value."</option>";
	}
?>
							</select>
						<input id="html" name="html" type="hidden" value="" />
						<!--input id="htmlLayout" name="htmlLayout" type="hidden" value="" /-->
						<input name="id" type="hidden" value="<?php echo $pageID?>" />
						<!--input id="command" name="command" type="text" value="aaa" /-->
					</td>
					<td style="text-align:center; color:black;">
						<div>
							width:<input id="page_width" name="page_width" size="4" type="text" value="<?php echo $page_width?>" <?php if ($page_is_template == 0 && $page_use_template != '') echo 'readonly="readonly"';?> />px
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;height:<input id="page_height" name="page_height" size="4" type="text" value="<?php echo $page_height?>" <?php if ($page_is_template == 0 && $page_use_template != '') echo 'readonly="readonly"';?> />px
							<img id="align_left" src="/gm/img/align_left.png" style="cursor:pointer; width:16px; height:16px; vertical-align:top; padding-left:50px; padding-right:5px; padding-top:5px; <?php if ($page_is_template == 0 && $page_use_template != '') echo 'display:none;"';?>" title="Align to the left"/>
							<img id="align_center" src="/gm/img/align_center.png" style="cursor:pointer; width:16px; height:16px; vertical-align:top; padding-left:5px; padding-right:5px; padding-top:5px; <?php if ($page_is_template == 0 && $page_use_template != '') echo 'display:none;"';?>" title="Align to the center"/>
							<img id="align_right" src="/gm/img/align_right.png" style="cursor:pointer; width:16px; height:16px; vertical-align:top; padding-left:5px; padding-right:5px; padding-top:5px; <?php if ($page_is_template == 0 && $page_use_template != '') echo 'display:none;"';?>" title="Align to the right"/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							css:<select id="css" name="css" <?php if ($page_is_template == 0 && $page_use_template != '') echo 'disabled="disabled"';?>>
								<option value="0">-</option>
<?php
	foreach ($cssNameArray as $cssId => $cssName) {
		if ($page_id_css == $cssId) echo "<option selected='selected' value='".$cssId."'>".$cssName."</option>";
		else echo "<option value='".$cssId."'>".$cssName."</option>";
	}
?>
							</select>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							javascript:<select id="javascript" name="javascript" <?php if ($page_is_template == 0 && $page_use_template != '') echo 'disabled="disabled"';?>>
								<option value="0">-</option>
<?php
	foreach ($javascriptNameArray as $javascriptId => $javascriptName) {
		if ($page_id_javascript == $javascriptId) echo "<option selected='selected' value='".$javascriptId."'>".$javascriptName."</option>";
		else echo "<option value='".$javascriptId."'>".$javascriptName."</option>";
	}
?>
							</select>
						</div>
						<div style="vertical-align:bottom;">
							<input id="page_align" name="page_align" type="hidden" value="<?php echo $page_align?>"/>
							<span style="vertical-align:bottom; <?php if ($page_is_home) echo 'display:none;';?>">this page is a template <input id="is_template" name="is_template" type="checkbox" <?php if ($page_is_template) echo 'checked="checked"';?> /> or</span> use the following template
							<select id="use_template" name="use_template" <?php if ($page_is_template) echo 'disabled="disabled"';?>>
								<option value="">-</option>
<?php
	foreach ($templateNameArray as $templateName) {
		if ($page_use_template == $templateName) echo "<option selected='selected' value='".$templateName."'>".$templateName."</option>";
		else echo "<option value='".$templateName."'>".$templateName."</option>";
	}
?>
							</select>
						</div>
					</td>
					<td style="text-align:right; padding-right:5px;">
						<!--input id="action" type="action" value="Salva" /-->
<?php if ($authorized) { ?>						
						<button id="action" name="action" type="submit" value="save">Save this page layout</button>
<?php } ?>
					</td>
				</tr>
			</table>
		</form>
		<!--div id="container" style="border:1px dotted black; background-color:#eeeeee; height:100%; position:relative"-->
		<div id="main">
			<div id="container" style="border:1px dotted black; /*background-color:#ffffff;*/ width:<?php echo $page_width?>px; height:<?php echo $page_height?>px; position:relative; display: inline-block; font-size:16px;">
<?php
	echo $htmlPageToShow;
?>
			</div>
		</div>
	</div>
	<!--div id="elements_list">&nbsp;</div-->

<script>
	var positionbox = $("#box").offset();
    	
	$("#box").on('mousemove',function(e){
	  if (isMouseDown) {
		//$(objDragged).offset({ top: Math.round((e.pageY - objOffsetTop) / 5) * 5, left: Math.round((e.pageX - objOffsetLeft) / 5) * 5 });
		$(objDragged).offset({ top: Math.round(e.pageY - objOffsetTop), left: Math.round(e.pageX - objOffsetLeft)});
		//alert(Math.round(e.pageY - objOffsetTop) + ' ' + Math.round(e.pageX - objOffsetLeft));
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
		
	$(".gm_section").on('mousedown',drag);
	
	$(".gm_section").on('mouseup',drop);
	
	$("#page_width").on('keyup', {value: 'value'}, keyupAttr);

	$("#page_height").on('keyup', {value: 'value'}, keyupAttr);
	
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
		$("#selected_element").html('');
		//if (tagDragged == 'DIV') {
			$('<div>').appendTo("#selected_element");
			$('<strong>id:</strong><input id="selected_element_id" type="text" size="90" value="' + $(this).attr("id") + '"/>').on('keyup', {value: 'id'}, keyupAttr).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;class:</strong> gm_section <input id="selected_element_class" type="text" value="' + ($(this).attr("class")).substring(10) + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'class'}, keyupAttr).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
			$('<div>').appendTo("#selected_element");
			$('<strong>width:</strong><input id="selected_element_width" type="text" size="3" value="' + $(this).css("width") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'width'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;height:</strong><input id="selected_element_height" type="text" size="3" value="' + $(this).css("height") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");

			//$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;overflow:</strong><input id="selected_element_overflow" type="text" size="3" value="' + $(this).css("overflow") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'overflow'}, keyupCss).appendTo("#selected_element");
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;overflow:</strong><select id="selected_element_overflow"><select>').on('change', changeOverflow).appendTo("#selected_element");
			if ($(this).css("overflow") == 'visible')
				$('<option selected="selected">visible</option>').appendTo("#selected_element_overflow");
			else $('<option>visible</option>').appendTo("#selected_element_overflow");
			if ($(this).css("overflow") == 'hidden')
				$('<option selected="selected">hidden</option>').appendTo("#selected_element_overflow");
			else $('<option>hidden</option>').appendTo("#selected_element_overflow");
			if ($(this).css("overflow") == 'scroll')
				$('<option selected="selected">scroll</option>').appendTo("#selected_element_overflow");
			else $('<option>scroll</option>').appendTo("#selected_element_overflow");
			if ($(this).css("overflow") == 'auto')
				$('<option selected="selected">auto</option>').appendTo("#selected_element_overflow");
			else $('<option>auto</option>').appendTo("#selected_element_overflow");
			//if ($(this).css("overflow") == 'inherit')
			//	$('<option selected="selected">inherit</option>').appendTo("#selected_element_overflow");
			//else $('<option>inherit</option>').appendTo("#selected_element_overflow");

			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;replication direction:</strong><select id="selected_element_replication_direction"><select>').on('change', changeReplicationDirection).appendTo("#selected_element");
			if ($(this).attr("replication-direction") == 'horizontal') {
				$('<option selected="selected">horizontal</option>').appendTo("#selected_element_replication_direction");
				$('<option>vertical</option>').appendTo("#selected_element_replication_direction");
			}
			else {
				$('<option>horizontal</option>').appendTo("#selected_element_replication_direction");
				$('<option selected="selected">vertical</option>').appendTo("#selected_element_replication_direction");
			}

			var replicationlinesnumber = $(this).attr("replication-linesnumber");
			if (replicationlinesnumber == undefined) replicationlinesnumber = '';
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;replication lines number:</strong><input id="selected_element_replication_linesnumber" type="text" size="2" value="' + replicationlinesnumber + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'replication-linesnumber'}, keyupAttr).appendTo("#selected_element");

			var sectionblock = $(this).attr("section-block");
			if (sectionblock == undefined) sectionblock = '';
			$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;block:</strong><input id="selected_element_section_block" type="text" size="20" value="' + sectionblock + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'section-block'}, keyupAttr).appendTo("#selected_element");
			
			//$('<strong>&nbsp;&nbsp;&nbsp;&nbsp;view:</strong><input id="selected_element_view" type="text" size="3" value="' + $(this).css("view") + '"/>&nbsp;&nbsp;&nbsp;&nbsp;').on('keyup', {value: 'height'}, keyupCss).appendTo("#selected_element");
			$('</div>').appendTo("#selected_element");
		//}
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
		// calcolo automatico della dimensione della page
		/*var page_width = 0;
		var page_height = 0;
		//alert('drop');
		$(".gm_section").each(function () {
			var element_width = $(this).css("width");
			var element_height = $(this).css("height");
			var element_top = $(this).css("top");
			var element_left = $(this).css("left");
			//alert(element_width);
			element_width = parseInt(element_width.replace(/px/g, ""));
			element_height = parseInt(element_height.replace(/px/g, ""));
			element_top = parseInt(element_top.replace(/px/g, ""));
			element_left = parseInt(element_left.replace(/px/g, ""));
			if (page_width < element_width + element_left) page_width = element_width + element_left;
			if (page_height < element_height + element_top) page_height = element_height + element_top;
			//alert(page_width + " " + element_width);
		});
		$("#page_width").attr('value',page_width);
		$("#page_height").attr('value',page_height);*/
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
		else*/ if (e.data.value == 'value') {
			if ($(this).attr("id") == 'page_width') {
				//alert('width !');
				$('#container').css('width',$(this).val());
			}
			else if ($(this).attr("id") == 'page_height') {
				//alert('height !');
				$('#container').css('height',$(this).val());
			}
		}
		else if (e.data.value == 'multiple') {
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
			objDragged.attr(e.data.value, 'gm_section ' + str);
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

	function changeOverflow(e) {
		var str = $(this).val();
		objDragged.css('overflow', str);
	}

	function changeReplicationDirection(e) {
		var str = $(this).val();
		objDragged.attr('replication-direction', str);
	}
	
	$("#new_element").change(function(){
		//var newElement = $("#new_element option:selected").text();
		var idSection = $("#new_element option:selected").attr('value');
		var id = prompt("Section ID:", $("#new_element option:selected").text());
		if (id != null) {
			if (id == '') id = '?';
			/*if (newElement == 'div') $('<div id="' + id + '" class="gm_section " style="cursor:move; " title="">' + id + '</div>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");*/
			<?php foreach ($sectionNameArray as $key => $value) { ?>
				if (idSection == <?php echo $key ?>) $('<div id="' + id + '" title="<?php echo $key; ?>" class="gm_section " style="cursor:move; width:<?php echo $sectionWidthArray[$key]; ?>px; height:<?php echo $sectionHeightArray[$key]; ?>px;"></div>').html('<?php echo str_replace('onclick=""','onClick="return false;"',str_replace('onfocus=""','onFocus="body.focus();this.blur();"',str_replace("'","\'",$sectionHtmlArrayOfArray[$key][0]))) ?>').on('mousedown', drag).on('mouseup', drop).appendTo("#container");
			<?php } ?>
		}
		$("#new_element option:selected").removeAttr("selected");
		$("#new_element option").eq(0).attr("selected", "selected");
	});
	
	$("#action").click(function() {
		$("#css").removeAttr("disabled");
		$("#javascript").removeAttr("disabled");
		//alert($("#container").html());
		$(".gm_section_template").remove();
		//alert($("#container").html());
		//$("#html").attr("value", $("#container").html());		
		$(".gm_section").each(function () {
			//alert($(this).text());
			$(this).html('');
			$(this).css("cursor", "");
			var strTop = $(this).css("top");
			strTop = 1 * strTop.replace(/px/g, "");
			var strLeft = $(this).css("left");
			strLeft = 1 * strLeft.replace(/px/g, "");
			//alert(strTop + ' x ' + strLeft);
			// risposto di +1,+1 il vertice top,left avendolo sottratto in fase di caricamento
			strTop = Math.round(0.999 + strTop);
			strLeft = Math.round(0.999 + strLeft);
			//alert(strTop + ' x ' + strLeft);
			$(this).css("top", strTop + "px");
			$(this).css("left", strLeft + "px");
			$(this).removeAttr("onClick");
			$(this).removeAttr("onFocus");
			$(this).removeAttr("draggable");			
			//objPosition = $(this).offset();
			//$(this).attr("tabindex", objPosition.top * 100 + objPosition.left);
		});
		//alert($("#container").html());
		//$("#htmlLayout").attr("value", $("#container").html());
		$("#html").attr("value", $("#container").html());
		//alert($("#container").html());
		//return false;
	});

	$("#align_left").click(function() {
		$("#box").css('text-align', 'left');
		$('#page_align').attr('value','left');
	});

	$("#align_center").click(function() {
		$("#box").css('text-align', 'center');
		$('#page_align').attr('value','center');
	});

	$("#align_right").click(function() {
		$("#box").css('text-align', 'right');
		$('#page_align').attr('value','right');
	});
	
	$("#is_template").click(function() {
		if ($("#is_template").prop('checked')) {
			$("#use_template").attr("disabled","disabled");
			$('#page_width').removeAttr('readonly');
			$('#page_height').removeAttr('readonly');
			$('#align_left').css('display','inline');
			$('#align_center').css('display','inline');
			$('#align_right').css('display','inline');
			$("#css").removeAttr("disabled");
			$("#css option:selected").removeAttr("selected");
			$("#css option[value='0']").attr("selected", "selected");
			$('#dinamic_css').text('');
			$("#javascript").removeAttr("disabled");
			$("#javascript option:selected").removeAttr("selected");
			$("#javascript option[value='0']").attr("selected", "selected");
			$(".gm_section_template").remove();
		}
		else {
			$("#use_template").removeAttr("disabled");
			$(".gm_section_template").remove();
			var templateSelected = $("#use_template option:selected").attr('value');
			if (templateSelected != '') {
				$('#page_width').attr('readonly','readonly');
				$('#page_height').attr('readonly','readonly');
				$('#align_left').css('display','none');
				$('#align_center').css('display','none');
				$('#align_right').css('display','none');
				$("#css").attr("disabled","disabled");
				$("#javascript").attr("disabled","disabled");
			}
			//alert(templateSelected);
			<?php foreach ($templateNameArray as $templateName) { ?>
				if (templateSelected == '<?php echo $templateName ?>') {
					$("#container").prepend('<?php echo str_replace("'","\'",preg_replace('/[\r\t\n]/','',$templateWithSectionsHtmlArray[$templateName])); ?>');
					$('#container').css('width', '<?php echo $templateWidthArray[$templateName] ?>');
					$('#page_width').attr('value','<?php echo $templateWidthArray[$templateName] ?>');				
					$('#container').css('height', '<?php echo $templateHeightArray[$templateName] ?>');
					$('#page_height').attr('value','<?php echo $templateHeightArray[$templateName] ?>');				
					$('#box').css('text-align', '<?php echo $templateAlignArray[$templateName] ?>');
					$('#page_align').attr('value','<?php echo $templateAlignArray[$templateName] ?>');				
					$("#css option:selected").removeAttr("selected");
					$("#css option[value='0']").attr("selected", "selected");
					$("#css option:selected").removeAttr("selected");
					$("#css option[value='<?php echo $templateCssArray[$templateName] ?>']").attr("selected", "selected");
					$('#dinamic_css').text('<?php if ($templateCssArray[$templateName] != 0) echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$cssSrcArray[$templateCssArray[$templateName]])))))))))))))))))))))))))))); ?>');
					$("#javascript option:selected").removeAttr("selected");
					$("#javascript option[value='0']").attr("selected", "selected");
					$("#javascript option:selected").removeAttr("selected");
					$("#javascript option[value='<?php echo $templateJavascriptArray[$templateName] ?>']").attr("selected", "selected");
				}
			<?php } ?>
		}
	});
	
	$("#use_template").change(function(){
		$(".gm_section_template").remove();
		var templateSelected = $("#use_template option:selected").attr('value');
		if (templateSelected != '') {
			$('#page_width').attr('readonly','readonly');
			$('#page_height').attr('readonly','readonly');
			$('#align_left').css('display','none');
			$('#align_center').css('display','none');
			$('#align_right').css('display','none');
			$("#css").attr("disabled","disabled");
			$("#javascript").attr("disabled","disabled");
		} else {
			$('#page_width').removeAttr('readonly');
			$('#page_height').removeAttr('readonly');
			$('#align_left').css('display','inline');
			$('#align_center').css('display','inline');
			$('#align_right').css('display','inline');
			$("#css").removeAttr("disabled");
			$("#css option:selected").removeAttr("selected");
			$("#css option[value='0']").attr("selected", "selected");
			$('#dinamic_css').text('');
			$("#javascript").removeAttr("disabled");
			$("#javascript option:selected").removeAttr("selected");
			$("#javascript option[value='0']").attr("selected", "selected");
		}
		//alert(templateSelected);
		<?php foreach ($templateNameArray as $templateName) { ?>
			if (templateSelected == '<?php echo $templateName ?>') {
				$("#container").prepend('<?php echo str_replace("'","\'",preg_replace('/[\r\t\n]/','',$templateWithSectionsHtmlArray[$templateName])); ?>');
				$('#container').css('width', '<?php echo $templateWidthArray[$templateName] ?>');
				$('#page_width').attr('value','<?php echo $templateWidthArray[$templateName] ?>');				
				$('#container').css('height', '<?php echo $templateHeightArray[$templateName] ?>');
				$('#page_height').attr('value','<?php echo $templateHeightArray[$templateName] ?>');				
				$('#box').css('text-align', '<?php echo $templateAlignArray[$templateName] ?>');
				$('#page_align').attr('value','<?php echo $templateAlignArray[$templateName] ?>');
				$("#css option:selected").removeAttr("selected");
				$("#css option[value='0']").attr("selected", "selected");
				$("#css option:selected").removeAttr("selected");
				$("#css option[value='<?php echo $templateCssArray[$templateName] ?>']").attr("selected", "selected");
				$('#dinamic_css').text('<?php if ($templateCssArray[$templateName] != 0) echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$cssSrcArray[$templateCssArray[$templateName]])))))))))))))))))))))))))))); ?>');
				//alert('<?php echo $templateName ?>' + " " + <?php echo $templateCssArray[$templateName] ?>);
				$("#javascript option:selected").removeAttr("selected");
				$("#javascript option[value='0']").attr("selected", "selected");
				$("#javascript option:selected").removeAttr("selected");
				$("#javascript option[value='<?php echo $templateJavascriptArray[$templateName] ?>']").attr("selected", "selected");
				//alert('<?php echo $templateName ?>' + " " + <?php echo $templateJavascriptArray[$templateName] ?>);
			}
		<?php } ?>
	});
	
	$("#css").change(function(){
		var cssSelected = $("#css option:selected").attr('value');
		//alert(cssSelected);
		if (cssSelected == '0') $('#dinamic_css').text('');
		else {
			// <>@!#$%^&*()_+[]{}?:;|'\"\\,./~`-=
			<?php foreach ($cssNameArray as $cssId => $cssName) { ?>
				if (cssSelected == '<?php echo $cssId ?>') {
					$('#dinamic_css').text('<?php echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$cssSrcArray[$cssId])))))))))))))))))))))))))))) ?>');				
				}
			<?php } ?>
		}
	});

	<?php if ($page_id_css != 0) { ?>
		$('#dinamic_css').text('<?php echo str_replace("|", "\|", str_replace("[", "\[", str_replace("]", "\]", str_replace("+", "\+", str_replace("(", "\(", str_replace(")", "\)", str_replace("*", "\*", str_replace("&", "\&", str_replace("^", "\^", str_replace("$", "\$", str_replace("%", "\%", str_replace("!", "\!", str_replace("?", "\?", str_replace("<", "\<", str_replace(">", "\>", str_replace("@", "\@", str_replace('"', '\"', str_replace("'", "\'", str_replace("/", "\/", str_replace("\\", "\\", str_replace(".", "\.", str_replace(",", "\,", str_replace(":", "\:", str_replace(";", "\;", str_replace("#", "\#", str_replace("}", "\}", str_replace("{", "\{", preg_replace('/[\r\t\n]/','',$cssSrcArray[$page_id_css])))))))))))))))))))))))))))) ?>');
	<?php } ?>		
	
</script>

<?php
	}
	/*eval('?> <div>ciao <?php echo "xian"; ?></div> <?php');*/
?>

</body>
</html>