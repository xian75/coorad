<?php
	require_once("init.php");
	
	unset($_SESSION['user']);
	unset($_SESSION['role']);
	unset($_SESSION['apps']);

	header("location: login.php");
?>
