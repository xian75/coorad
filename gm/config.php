<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbHost = 'localhost';
$CFG->dbUsername = 'gm';
$CFG->dbPassword = 'gm';
$CFG->dbName = 'gm';
$CFG->dbPort = 3306;
$CFG->dbSocket = 0;

$CFG->rootDir = 'C:/www/gm';

// Notice: The default installation provides a superuser account (admin/password)... use that to login
$CFG->authorization = false;

/*$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbsocket' => 0,
);*/

?>