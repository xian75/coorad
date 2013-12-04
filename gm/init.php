<?php
	date_default_timezone_set('Europe/Paris');
	
	require_once("db.php");
	require_once("config.php");

	// no output before session_start(), please
	session_start();
	//echo 'session_id = '.session_id().'<br />';
	
	unset($DB);
	global $DB;
	
	$DB = new DB($CFG->dbHost, $CFG->dbUsername, $CFG->dbPassword, $CFG->dbName, $CFG->dbPort, $CFG->dbSocket);
	$DB->connect();

?>