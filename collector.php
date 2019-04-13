<?php
// collector.php
	include_once("access.php");
	class config {
		public $name="";
		public $ip="";
		public $update=0; 
		/*
			oznaczenia update:
			0 - nie wymaga aktualizacji
			1 - przeznaczony do aktualizacji
			2 - wysłano aktualizacje, oczekuje na potwierdzenie zmian
		*/
		public $brightness=0;
		public $gkAddress="";
		public $scanMode=0;
	}
	$conn=mysql_connect($DBhost,$DBuser,$DBpass) or die("błąd połączenia z bazą");
	$db_found=mysql_select_db("tp",$conn);
	//mysql_query("set names utf8");
	if($db_found) {
		if(isset($_POST["device"]) && $_POST["device"]!="" && $_POST["battLvl"]!="" && $_POST["acStatus"]!="") {
			
			$data=date("Y-m-d H:i:s");
			
			$rec=new config();
			$rec->name=$_POST["device"];
			$rec->ip=$_POST["IP"];
			$rec->update=0;
			$rec->brightness=$_POST["bright"];
			$rec->gkAddress=$_POST["gkSrvAdr"];
			$rec->scanMode=$_POST["scanMode"];
			
			//definicje zmiennych
			$dev=$_POST["device"];
			$batt=$_POST["battLvl"];
			$ac=$_POST["acStatus"];
			$bright=$_POST["bright"];
			$gkSrvAdr=$_POST["gkSrvAdr"];
			$IP=$_POST["IP"];
			$timerInterval=$_POST["timer"];
			$localPass=$_POST["localPass"];
			$scanMode=$_POST["scanMode"];
			
			//sprawdzanie czy to pierwsze odwołanie skanera i wpis do rejestru urządzeń
			$zm=mysql_fetch_assoc(mysql_query("SELECT Id FROM devices WHERE name='$dev';"));
			if(!$zm["Id"]) 
				mysql_query("INSERT INTO devices VALUES ('','$dev','".$data."','0','$bright','$gkSrvAdr','$IP','$timerInterval','','0','$scanMode');");
			
			//pobieranie ID urządzenia
			$idT=mysql_fetch_assoc(mysql_query("SELECT Id FROM devices WHERE name='$dev';"));
			$id=$idT["Id"];
			
			//zapis danych bieżących
			$query = "INSERT INTO battstatus VALUES ('$id','$batt','$data','$ac')";
			mysql_query($query);
			
			//pobieranie konfiguracji z bazy
			$row=mysql_fetch_assoc(mysql_query("SELECT * FROM devices WHERE Id='$id';"));
			$lPassDbT=mysql_fetch_assoc(mysql_query("SELECT val From config WHERE param='localPin'"));
			$lPassDb=$lPassDbT["val"];
			$update=$row["updt"];	//aktywuje moduł gdy administrator zarządzi zmianę
			if($row["message"]!="" || $lPassDb!=$localPass || $row["brightness"]!=$bright || $row["gkServerAddress"]!=$gkSrvAdr || $row["timer"]!=$timerInterval || $row["scanMode"]!=$scanMode) //aktywuje moduł gdy użytkownik coś zmieni wbrew ustawieniom.
				$update=1;
			if($update!=0) {
				$db=new config();
				$db->name=$row["name"];
				$db->ip=$row["IP"];
				$db->update=$row["updt"];
				$db->brightness=$row["brightness"];
				$db->gkAddress=$row["gkServerAddress"];
				//echo $row["name"];
				//xml creating
				$xml = new SimpleXMLElement('<Configuration/>');
					//$cnf=$xml->addChild('Configuration');
					$xml->addChild('brightness',$row["brightness"]);
					$xml->addChild('gk',$row["gkServerAddress"]);
					$xml->addChild('timerInterval',$row["timer"]);
					$xml->addChild('lPass',$lPassDb);
					$xml->addChild('message',$row["message"]);
					$xml->addChild('scanMode',$row["scanMode"]);
				echo $xml->asXML();	
			}
			//echo"data collected!";
			
		}
		//http://localhost/battService/collector.php?device=44&data=12-08-2015_14:32:55&battLvl=99&acStatus=n
	}
	mysql_close();
?>