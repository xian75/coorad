<?php
	header('Content-disposition: attachment; filename=page.xml');
	header("Content-type: text/plain"); 
	require_once("../init.php");
	if (!isset($_GET['id']) && !isset($_POST['id'])) {
		echo '<error>Sorry, no page selected</error>';
	}
	else {
		$pageID = 0;
		if (isset($_GET['id'])) $pageID = $_GET['id'];
		else $pageID = $_POST['id'];	
		
		$res = $DB->execute("SELECT gm_application.id appId, gm_application.name appName, gm_page.id pageID, gm_page.name pageName, gm_page.description pageDescription, gm_page.html pageHtml, gm_page.width pageWidth, gm_page.height pageHeight, gm_page.align pageAlign, gm_page.command pageCommand, gm_page.is_template pageIsTemplate, gm_page.use_template pageUseTemplate, gm_page.is_home pageIsHome, gm_page.id_css pageIdCss, gm_page.id_javascript pageIdJavascript FROM gm_page, gm_application WHERE gm_application.id = gm_page.id_application AND gm_page.id = ".$pageID);
		if ($page = $res->fetch_object()) {
			$appID = $page->appId;
			$appName = $page->appName;
			$pageID = $page->pageID;
			$pageName = $page->pageName;//str_replace("'","&rsquo;",$page->pageName);
			header('Content-disposition: attachment; filename=Page_'.$pageName.'.xml');
			$pageDescription = $page->pageDescription;
			$pageHtml = $page->pageHtml;
			$pageWidth = $page->pageWidth;
			$pageHeight = $page->pageHeight;
			$pageAlign = $page->pageAlign;
			$pageCommand = $page->pageCommand;
			$pageIsTemplate = $page->pageIsTemplate;
			$pageUseTemplate = $page->pageUseTemplate;
			$pageIsHome = $page->pageIsHome;
			$pageIdCss = $page->pageIdCss;
			$pageIdJavascript = $page->pageIdJavascript;
			$res->close();
			// CSS
			$cssName = '';
			$cssDescription = '';
			$cssCode = '';
			if ($pageIdCss != 0) { 
				$res = $DB->execute("SELECT gm_css.name cssName, gm_css.description cssDescription, gm_css.css cssCode FROM gm_css WHERE gm_css.id = ".$pageIdCss);
				if ($css = $res->fetch_object()) {
					$cssName = $css->cssName;
					$cssDescription = $css->cssDescription;
					$cssCode = $css->cssCode;
					$res->close();
				}
			}
			// Javascript
			$javascriptName = '';
			$javascriptDescription = '';
			$javascriptCode = '';
			if ($pageIdJavascript != 0) { 
				$res = $DB->execute("SELECT gm_javascript.name javascriptName, gm_javascript.description javascriptDescription, gm_javascript.javascript javascriptCode FROM gm_javascript WHERE gm_javascript.id = ".$pageIdJavascript);
				if ($javascript = $res->fetch_object()) {
					$javascriptName = $javascript->javascriptName;
					$javascriptDescription = $javascript->javascriptDescription;
					$javascriptCode = $javascript->javascriptCode;
					$res->close();
				}
			}
?>
<page>
	<page_id><?php echo $pageID; ?></page_id>
	<page_name><?php echo $pageName; ?></page_name>
	<page_description><?php echo $pageDescription; ?></page_description>
	<page_width><?php echo $pageWidth; ?></page_width>
	<page_height><?php echo $pageHeight; ?></page_height>
	<page_align><?php echo $pageAlign; ?></page_align>
	<page_istemplate><?php echo $pageIsTemplate; ?></page_istemplate>
	<page_usetemplate><?php echo $pageUseTemplate; ?></page_usetemplate>
	<page_ishome><?php echo $pageIsHome; ?></page_ishome>
<?php
	if ($pageIdCss != 0) {
?>
	<page_css>
		<page_idcss><?php echo $pageIdCss; ?></page_idcss>
		<page_namecss><?php echo $cssName; ?></page_namecss>
		<page_descriptioncss><?php echo $cssDescription; ?></page_descriptioncss>
		<page_codecss><![CDATA[<?php echo str_replace('\\', '\\\\', $cssCode); ?>]]></page_codecss>
	</page_css>
<?php
	}
	if ($pageIdJavascript != 0) {
?>
	<page_javascript>
		<page_idjavascript><?php echo $pageIdJavascript; ?></page_idjavascript>
		<page_namejavascript><?php echo $javascriptName; ?></page_namejavascript>
		<page_descriptionjavascript><?php echo $javascriptDescription; ?></page_descriptionjavascript>
		<page_codejavascript><![CDATA[<?php echo str_replace('\\', '\\\\', $javascriptCode); ?>]]></page_codejavascript>
	</page_javascript>		
<?php
	}
?>
	<page_idapplication><?php echo $appID; ?></page_idapplication>
	
	
	<page_html><![CDATA[<?php echo str_replace('\\', '\\\\', $pageHtml); ?>]]></page_html>
	
	
	<page_command><![CDATA[<?php echo str_replace('\\', '\\\\', $pageCommand); ?>]]></page_command>
	
	
	<sections>
<?php
	$actionsArray = array();
	$pagedoc = new DOMDocument();
	if ($pageHtml != '') $pagedoc->loadHTML($pageHtml);
	$pagenodes = $pagedoc->getElementsByTagName("div");
	foreach ($pagenodes as $pagenode) {
		$sectionIdInPage = $pagenode->getAttribute('title');
		//echo $sectionIdInPage." ";
		include("../section/section_xml_export.php");
	}
?>
	</sections>
	<actions>
<?php
				foreach ($actionsArray as $actionIdInPage) {
					//echo $actionIdInPage." ";
					include("../action/action_xml_export.php");
				}
?>
	</actions>
</page>
<?php
		}
		else echo '<error>Sorry, no page found</error>';
	}
?>
