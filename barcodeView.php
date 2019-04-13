<?php
	$codeName = (isset($_GET["codeName"])?$_GET["codeName"]:"noname");
	echo"<center><h1>$codeName</h1>";
	if(isset($_GET["text"]) && $_GET["size"]) {
		echo"<img src=\"barcodeGenerator.php?text={$_GET['text']}&size={$_GET['size']}\"/>";
	}
	echo"</center>";
?>