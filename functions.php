<?php
	//generowanie sessionID - ta funkcja musi być na początku każdego pliku!
	if(!isset($_SESSION["sesId"])) {
		$_SESSION["sesId"]=randomString(32);
	}
	function SqlProtector($str, $mode) {
		$str=mysql_escape_string($str);
		return $str;
	}
	function stringCutter($str, $len) {
		return substr($str,0,$len)."...";
	}
	function scanModeDecoder($mode) {
		switch($mode) {
			case 0:
				return "STOP";
				break;
			case 1:
				return "Entry";
				break;
			case 2:
				return "Exit";
				break;
			case 3:
				return "Bidir";
				break;
			default:
				return "ERROR";
				break;
		}
	}
	function intervalPresenter($lastDate,$firstDate) {
		$DTlastDate=new DateTime($lastDate);
		$file="intervalLog.txt";
		$DTfirstDate=new DateTime($firstDate);
		$interval=$DTfirstDate->diff($DTlastDate);
		file_put_contents($file,$interval->i);
		$str="";
		if($interval->s>0)
			$str="Over ".$interval->s." second(s) ago";
		if($interval->i>0)
			$str="Over ".$interval->i." minute(s) ago";
		if($interval->h>0)
			$str="Over ".$interval->h." hour(s) ago";
		if($interval->d>0)
			$str="Over ".$interval->d." day(s)";
		if($interval->m>0)
			$str="Over month";
		if($interval->y>0)
			$str="Over year ago";
		return $str;
	}
	function GenerateCode($size) {
		$code="";
		/*for($i=0;$i<$size;$i++) {
			$code .= rand(0,9);
		}*/
		$code=RandomString(16);
		return $code;
	}
	//zawijanie tekstu w komórkach. na razie do wykorzystania w tabelce kodów dodających profile w kolumnie opis
	function StringWrapper($charsCount) {
		
	}
	function CheckIntegritySpot() {
		mysql_query("UPDATE devices SET spot=0 WHERE spot='-99999'");
		mysql_query("UPDATE devices SET spot=spot*-1 WHERE spot<0");
	}
	function SessionEncryptor($str) {
		return $str;
	}
	function PasswordEncryptor($str) {
		$str=md5($str);
		return $str;
	}
	function ColorSet($nameOfSelect, $onch) {
		echo"
			<select name='$nameOfSelect' id='$nameOfSelect' onchange=\"$onch\">
				<option value=''>Wybierz...</option>
				<option value='#5CA4A9' style='background-color:#5CA4A9;'></option>
				<option value='#9BC1BC' style='background-color:#9BC1BC;'></option>
				<option value='#1BE7FF' style='background-color:#1BE7FF;'></option>
				<option value='#84DCC6' style='background-color:#84DCC6;'></option>
				<option value='#6EEB83' style='background-color:#6EEB83;'></option>
				<option value='#A5FFD6' style='background-color:#A5FFD6;'></option>
				<option value='#9AB87A' style='background-color:#9AB87A;'></option>
				<option value='#FF865E' style='background-color:#FF865E;'></option>
				<option value='#EBFF54' style='background-color:#EBFF54;'></option>
				<option value='#F8F991' style='background-color:#F8F991;'></option>
				<option value='#F4F1BB' style='background-color:#F4F1BB;'></option>
				<option value='#E3FFA3' style='background-color:#E3FFA3;'></option>
			</select>";
	}
	function DateTimeSelectBuilder($name,$onch,$param, $def) {
		echo"<select name='$name' id='$name' onchange=\"$onch\">";
		if($param=='Y') {
			for($i=2014;$i<2025;$i++) {
				echo"<option value='$i'";
				if($def==$i)
					echo" selected=selected ";
				echo">$i</option>";
			}
		}
		else if($param=='M'){ //$tab={{'01','styczeń'},{'02','luty'},{'03','marzec'},{'04','kwiecień'},{'05','maj'},{'06','czerwiec'},{'07','lipiec'},{'08','sierpień'},{'09','wrzesień'},{'10','październik'},{'11','listopad'},{'12','grudzień'},};
			$tab=array('styczeń','luty','marzec','kwiecień','maj','czerwiec','lipiec','sierpień','wrzesień','październik','listopad','grudzień');
			for($i=1;$i<13;$i++) {
				echo"<option value='$i'";
				if($i==$def)
					echo " selected=selected ";
				echo">{$tab[$i-1]}</option>";
			}
			/*echo"<option value='01'>styczeń</option>";
			echo"<option value='02'>luty</option>";
			echo"<option value='03'>marzec</option>";
			echo"<option value='04'>kwiecień</option>";
			echo"<option value='05'>maj</option>";
			echo"<option value='06'>czerwiec</option>";
			echo"<option value='07'>lipiec</option>";
			echo"<option value='08'>sierpień</option>";
			echo"<option value='09'>wrzesień</option>";
			echo"<option value='10'>październik</option>";
			echo"<option value='11'>listopad</option>";
			echo"<option value='12'>grudzień</option>";*/
		}
		else if($param=='D') {
			for($i=1;$i<=31;$i++) {
				echo"<option value='$i'";
				if($def==$i)
					echo" selected=selected ";
				echo">$i</option>";
			}
		}
		else if($param=='H') {
			for($i=0;$i<23;$i++) {
				echo"<option value='$i'";
				if($def==$i)
					echo" selected=selected ";
				echo">$i</option>";
			}
		}
		else if($param=='i') {
			for($i=0;$i<60;$i++) {
				echo"<option value='$i'";
				if($def==$i)
					echo" selected=selected ";
				echo">$i</option>";
			}
		}
		else if($param=='s') {
			for($i=0;$i<60;$i++) {
				echo"<option value='$i'";
				if($def==$i)
					echo" selected=selected ";
				echo">$i</option>";
			}
		}
		echo"</select>";
	}
	//sprawdzenie poprawności zalogowania
	function LoggedChecker() {
		if(isset($_SESSION["login"]) && $_SESSION["login"]) {
			$q=mysql_fetch_assoc(mysql_query("SELECT session FROM users WHERE login='".$_SESSION["login"]."'"));
			$sessionDB=$q["session"];
			if($sessionDB!="" && $sessionDB==SessionEncryptor($_SESSION["sesId"]))
				return true;
			else {
				unset($_SESSION["login"]);
				header('Location: login.php');
			}
		}
		else {
			header("Location: login.php");
		}
		return false;
	}
	//generowanie session id
	function randomString($size) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < $size; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
?>