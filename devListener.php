<?php
	include_once("access.php");
	include_once("functions.php");
	$conn=mysql_connect($DBhost,$DBuser,$DBpass) or die("błąd połączenia z bazą");
	$db_found=mysql_select_db("tp",$conn);
	
	if($db_found) {
		//sprawdzenie kodu biletu z bazą i odpowiedź
		if(isset($_POST["action"]) && $_POST["action"]=="checkTicket" && isset($_POST["code"]) && isset($_POST["name"])) {
		//if(isset($_GET["action"]) && $_GET["action"]=="checkTicket" && isset($_GET["code"]) && isset($_GET["name"])) {
			$devName=$_POST["name"];
			$code=$_POST["code"];
			$devName=$_POST["name"];
		//	$devName=$_GET["name"];
		//	$code=$_GET["code"];
		//	$devName=$_GET["name"];
			
			$xml = new SimpleXMLElement('<CheckTicket/>');
			$xml->addChild('ticket',$code);
			
			$modeT=mysql_fetch_assoc(mysql_query("SELECT scanMode FROM devices WHERE name='$devName'"));
			//sprawdzenie czy skaner widnieje w bazie danych
			if(!isset($modeT["scanMode"])) {
				$xml->addChild('validate','notInDB');
			}
			else if($modeT["scanMode"]==0)
				$xml->addChild('validate','modeStop');
			else {
				$mode=$modeT['scanMode'];
				$dateNow=date("Y-m-d H:i:s");
				$ev=mysql_query("SELECT * FROM events WHERE start<'$dateNow' and end>'$dateNow'");
				while($r=mysql_fetch_assoc($ev)) {
					$evId=$r["Id"];
					$tic=mysql_fetch_assoc(mysql_query("SELECT * FROM tickets WHERE ticket='$code' AND event='$evId'"));
					if(is_array($tic)) {
						//tryb wejścia
						if($tic["state"]==0 && ($mode==1 || $mode==3)) {
							mysql_query("UPDATE tickets SET state='1', lastChange='$dateNow', device='$devName' WHERE ticket='$code' AND event='$evId'");
							$result=mysql_affected_rows($conn);
							if($result==1) {
								$xml->addChild('validate',"ValidIn");
							}
							else
								$xml->addChild('validate',"error");
						}
						//tryb wyjścia
						else if($tic["state"]==1 && ($mode==2 || $mode==3)) {
							mysql_query("UPDATE tickets SET state='0', lastChange='$dateNow', device='$devName' WHERE ticket='$code' AND event='$evId'");
							$result=mysql_affected_rows($conn);
							if($result==1) {
								$xml->addChild('validate',"ValidOut");
							}
							else
								$xml->addChild('validate',"error");
						}
						//invalid dla wejścia i wyjścia
						else if(($tic["state"]==1 && $mode==1) || ($tic["state"]==0 && $mode==2)) {
							$xml->addChild('validate',"NotValid");
							$xml->addChild('Time',intervalPresenter($dateNow,$tic["lastChange"]));
							$xml->addChild('Time2',"(".$tic["lastChange"].")");
							if($tic['device']==$devName)
								$xml->addChild('Device',"this ($devName)");
							else
								$xml->addChild('Device',$tic["device"]);
						}
					}
				}
				if(!is_array($tic)) {
					$xml->addChild('validate','NotFound');
				}
			}
			echo $xml->asXML();
			//$row=mysql_fetch_assoc(mysql_query("SELECT * FROM barcodesconfig WHERE code='{$_POST["code"]}'"));
		}
		//sprawdzenie kodu konfiguracyjnego z bazą i odpowiedź
		if(isset($_POST["action"]) && $_POST["action"]=="configBarcodeRequest" && isset($_POST["code"]) && isset($_POST["name"])) {
			$devName=$_POST["name"];
			//przeszukiwanie konfiguracji
			$row=mysql_fetch_assoc(mysql_query("SELECT * FROM barcodesconfig WHERE code='{$_POST["code"]}'"));
			if(isset($row["name"])) {
				$name=$row["name"];
				$brightness=$row["brightness"];
				$gkAdr=$row["gkServerAddress"];
				if($gkAdr=="")
					$grAdr=" ";
				$IP=$row["IP"];
				$xml = new SimpleXMLElement('<Configuration/>');
						$xml->addChild('brightness',$brightness);
						$xml->addChild('gk'," ");
						$xml->addChild('timerInterval',-1);
						$xml->addChild('lPass'," ");
						$xml->addChild('message'," ");
				echo $xml->asXML();
				if($brightness!=" " && $brightness!="" && $brightness!="-1")
					mysql_query("UPDATE devices SET brightness='$brightness' WHERE name='$devName'");
				if($gkAdr!=" " && $gkAdr!="")
					mysql_query("UPDATE devices SET gkServerAddress='$gkAdr' WHERE name='$devName'");
			}
			else {
				//przeszukiwanie bazy zdefiniowanych pełnych profili do dodania
				$row=mysql_fetch_assoc(mysql_query("SELECT * FROM barcodesaddprofile WHERE code='{$_POST["code"]}'"));
				if(isset($row["Name"])) {
					$xml = new SimpleXMLElement('<ArrayOfProfile/>');
							$profile=$xml->addChild('Profile');
							$profile->addChild('Name',$row["Name"]);
							$profile->addChild('Ssid',$row["Ssid"]);
							$profile->addChild('SecurityType',$row["SecType"]);
							$profile->addChild('AuthenticationType',$row["AuthType"]);
							$profile->addChild('EncryptionType',$row["EncType"]);
							$profile->addChild('PassPhrase',$row["PassPhrase"]);
							$profile->addChild('AllowMixedMode',$row["AllowMixedMode"]);
							$profile->addChild('AESAllowMixedMode',$row["AESAllowMixedMode"]);
							$profile->addChild('AddressingMode',$row["AddressingMode"]);
							$profile->addChild('IpAddress',$row["IpAddress"]);
							$profile->addChild('SubnetMask',$row["SubnetMask"]);
							$profile->addChild('Gateway',$row["Gateway"]);
							$profile->addChild('Gateway2',$row["Gateway2"]);
							$profile->addChild('Dns',$row["Dns"]);
							$profile->addChild('Dns2',$row["Dns2"]);
							$profile->addChild('PowerMode',$row["PowerMode"]);
					echo $xml->asXML();
				}
				else {
					//przeszukiwanie bazy kodów aktywujących profile
					$row=mysql_fetch_assoc(mysql_query("SELECT * FROM barcodesactiveprofile WHERE code='{$_POST["code"]}'"));
					if(isset($row["Name"])) {
						$xml = new SimpleXMLElement('<ActiveProfile/>');
						$xml->addChild('ProfileName',$row["Name"]);
					}
					echo $xml->asXML();
				}
			}
			//else echo "Brak kodu w bazie!";
		}
		//potwierdzenie odbioru wiadomości wysłane ze skanera
		elseif(isset($_POST["device"]) && isset($_POST["message"]) && $_POST["message"]=="received") {
			$dev=$_POST["device"];
			mysql_query("UPDATE devices SET message='' WHERE name='$dev'");
		}
		//zlecenie update'u bazy devices wysłane ze skanera z uprawnieniami administratora
		elseif(isset($_POST["admin"]) && $_POST["admin"]=="tpAdmin" && isset($_POST["device"]) && isset($_POST["bright"]) && isset($_POST["timer"]) && isset($_POST["scanMode"])) {
			$dev=$_POST["device"];
			$bright=$_POST["bright"];
			$timer=$_POST["timer"];
			$scanMode=$_POST["scanMode"];
			mysql_query("UPDATE devices SET brightness='$bright', timer='$timer', scanMode='$scanMode' where name='$dev'");
			echo "OK-adm";
		}
	}
?>