<?php
	/* praca w biurze 
	$DBhost="10.10.30.200";
	$DBuser="observer";
	$DBpass="sAxp5dwSZESafRec";
	*/
	/* praca w domu */
	$DBhost="localhost";
	$DBuser="root";
	$DBpass="";
	
	$conn=mysql_connect($DBhost,$DBuser,$DBpass) or die("błąd połączenia z bazą");
	mysql_query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'", $conn);
	$db_found=mysql_select_db("tp",$conn);
?>