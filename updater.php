<?php
	session_start();
	include_once("access.php");
	include_once("functions.php");
	$conn=mysql_connect($DBhost,$DBuser,$DBpass) or die("błąd połączenia z bazą");
	mysql_query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'", $conn);
	$db_found=mysql_select_db("tp",$conn);
	if(!LoggedChecker()) {
		header('Location: login.php');
	}
	
	if($db_found) {
		//usunięcie grupy i przestawienie skanerów z tej grupy
		if(isset($_POST["action"]) && $_POST["action"]=="DeleteGroup" && isset($_POST["id"])) {
			mysql_query("UPDATE devices SET spot='0' WHERE spot='{$_POST["id"]}'");
			mysql_query("DELETE FROM groups WHERE id='{$_POST["id"]}'");
			echo"OK-GroupRemoved";
		}
		elseif(isset($_POST["action"]) && $_POST["action"]=='deleteEvent' && isset($_POST["id"]) && $_POST["id"]!="") {
			$evId=$_POST["id"];
			mysql_query("DELETE FROM tickets WHERE event='$evId'");
			mysql_query("DELETE FROM events WHERE Id='$evId'");
			echo "OK-eventDeleted";
		}
		//zmiana stanu biletu przez administratora
		elseif(isset($_POST["action"]) && $_POST["action"]=='changeTicketState' && isset($_POST["tic"]) && isset($_POST["ev"]) && $_POST["ev"]!="") {
			$ev=$_POST["ev"];
			$tic=$_POST["tic"];
			$qT=mysql_fetch_assoc(mysql_query("SELECT state FROM tickets WHERE ticket='$tic' AND event='$ev'"));
			$state=$qT["state"];
			$dt=date("Y-m-d H:i:s");
			if($state==1) {
				mysql_query("UPDATE tickets SET state='0', lastChange='$dt', device='admin' WHERE ticket='$tic' AND event='$ev'");
				echo"OK-stateChanged";
			}
			elseif($state==0) {
				mysql_query("UPDATE tickets SET state='1', lastChange='$dt', device='admin' WHERE ticket='$tic' AND event='$ev'");
				echo"OK-stateChanged";
			}
		} 
		//Dodanie kodu konfiguracyjnego
		elseif (isset($_POST["action"]) && $_POST["action"]=="AddConfCode") {
			$code=GenerateCode(16);
			mysql_query("INSERT INTO barcodesconfig VALUES('$code','{$_POST["name"]}','{$_POST["bright"]}','{$_POST["gkAdr"]}','')");
			//echo "code: $code, name: {$_POST["name"]}, bright: {$_POST["bright"]}, gk: {$_POST["gkAdr"]}";
			echo"OK-addConfCode";
		}
		//Dodanie kodu aktywującego
		elseif (isset($_POST["action"]) && $_POST["action"]=="AddActivateCode") {
			$code=GenerateCode(16);
			mysql_query("INSERT INTO barcodesactiveprofile VALUES('','{$_POST["name"]}','$code','')");
			//echo "code: $code, name: {$_POST["name"]}, bright: {$_POST["bright"]}, gk: {$_POST["gkAdr"]}";
			echo"OK-addActivateCode";
		}
		//usuwanie kodu konfiguracyjnego
		elseif(isset($_GET["deletecode"]) && $_GET["deletecode"]!="") {
			$code=$_GET["deletecode"];
			mysql_query("DELETE FROM barcodesconfig WHERE code='$code' LIMIT 1");
			mysql_query("DELETE FROM barcodesaddprofile WHERE code='$code' LIMIT 1");
			mysql_query("DELETE FROM barcodesactiveprofile WHERE code='$code' LIMIT 1");
			echo"<script>window.close()</script>";
		}
		//zlecenie update'u tabeli codeconf
		elseif(isset($_POST["action"]) && $_POST["action"] == "updateConfCode" && isset($_POST["parameter"]) && isset($_POST["value"]) && isset($_POST["identifier"])) {
			$par=$_POST["parameter"];
			$val=$_POST["value"];
			$id=$_POST["identifier"];
			
			mysql_query("UPDATE barcodesconfig SET $par='$val' where code='$id'");
			echo "OK";
		}
		//zmiana nazwy barcode'u konfigurującego
		elseif(isset($_POST["action"]) && $_POST["action"]=="ConfCodeNameChange" && isset($_POST["id"]) && isset($_POST["name"])) {
			mysql_query("UPDATE barcodesconfig SET name='{$_POST["name"]}' WHERE code='{$_POST["id"]}'");
			echo"OK-ConfigurationCodeNameChanged";
		}
		//zmiana nazwy grupy
		elseif(isset($_POST["action"]) && $_POST["action"]=="GroupNameChange" && isset($_POST["id"]) && isset($_POST["name"])) {
			mysql_query("UPDATE groups SET name='{$_POST["name"]}' WHERE id='{$_POST["id"]}'");
			echo"OK-GroupNameChanged";
		}
		//zmiana koloru grupy
		elseif(isset($_POST["action"]) && $_POST["action"]=="GroupColorChanged" && isset($_POST["id"]) && isset($_POST["color"])) {
			mysql_query("UPDATE groups SET color='{$_POST["color"]}' WHERE id='{$_POST["id"]}'");
			echo"OK-GroupColorChanged";
		}
		//Dodawanie grupy
		elseif(isset($_POST["action"]) && $_POST["action"]=="AddGroup" && isset($_POST["name"]) && isset($_POST["color"])) {
			mysql_query("INSERT INTO groups VALUES ('','{$_POST["name"]}','{$_POST["color"]}','')");
			$q=mysql_query("SELECT id FROM devices WHERE spot<0");
			mysql_query("UPDATE devices SET spot=(SELECT MAX(Id) FROM groups) WHERE spot<0");
			echo"OK-addGroup";
		}
		//autoryzacja przy zmianie kluczowych parametrów
		elseif(isset($_POST["action"]) && $_POST["action"]=="CheckPassword") {
			$passDBT=mysql_fetch_assoc(mysql_query("SELECT password FROM users where login='{$_SESSION['login']}'"));
			$passDB=$passDBT["password"];
			if($passDB==PasswordEncryptor($_POST["password"]))
				echo"OK-pass";
		}
		//reset bazy danych - odwołanie wstępne
		elseif(isset($_GET["action"]) && $_GET["action"]=='resetDatabase') {
			echo"<form id='logForReset' method='POST' action='updater.php'>
				<input type='hidden' name='action' value='resetDatabaseConfirmed'/>
				<table style='text-align:center;'>
					<tr>
						<th colspan='2' style='color:red;'>Reset bazy spowoduje CAŁKOWITE usunięcie <br/>
						wszystkich informacji o skanerach!<br/>
						Pozostaną tylko ustawienia ogólne.</th>
					</tr>
					<tr>
						<th>Hasło: </th>
						<td><input type='password' name='pass'>";
						if(isset($_GET["info"])) {
							$info=str_replace('_',' ',$_GET["info"]);
							echo"<p style='color:orange;'>$info</p>";
						}
						echo"</td>
					</tr>
					<tr>
						<td colspan='2'><input type='submit' value='Resetuj'/></td>
					</tr>
				</table>
			</form>
			";
		}
		//reset bazy potwierdzenie
		elseif(isset($_POST["action"]) && $_POST["action"]=='resetDatabaseConfirmed' && isset($_POST["pass"]) && isset($_POST["mode"])) {
			$pass=PasswordEncryptor($_POST["pass"]);
			$qT=mysql_fetch_assoc(mysql_query("SELECT password FROM users WHERE login='{$_SESSION['login']}'"));
			$passDB=$qT["password"];
			$mode=$_POST["mode"];
			if($pass==$passDB) {
				mysql_query("DELETE FROM battstatus WHERE 1");
				if($mode!='onlyHistory') {
					mysql_query("DELETE FROM devices WHERE 1");
					mysql_query("ALTER TABLE devices AUTO_INCREMENT = 1");
					if($mode=='all') {
						mysql_query("DELETE FROM groups WHERE 1");
						mysql_query("ALTER TABLE groups AUTO_INCREMENT = 1");
					}
				}
				echo"Baza danych oczyszczona";
			}
			else {
				echo"Invalid password";
			}
		}
		//usunięcie oczekującej wiadomości
		elseif(isset($_POST["device"]) && isset($_POST["message"]) && $_POST["message"]=="cancel") {
			$dev=$_POST["device"];
			mysql_query("UPDATE devices SET message='' WHERE Id='$dev'");
			echo"OK-cancelMsg";
		}
		//wysyłanie wiadomości z serwera do skanerów
		elseif(isset($_POST["message"]) && $_POST["message"]=="toSend" && isset($_POST["taMsg"])) {
			if(isset($_POST["devices"])) {
				$idCollection="";
				foreach($_POST["devices"] as $dev) {
					$idCollection=$idCollection.$dev.",";
				}
				$idCollection=substr($idCollection,0,strlen($idCollection)-1);
				mysql_query("UPDATE devices SET message='{$_POST["taMsg"]}' WHERE Id in($idCollection);");
				echo"<script>window.location.replace('index.php');</script>";
			}
			else { echo"Musisz zaznaczyć do kogo wysyłasz wiadomość!";}
		}
		//zlecenie update'u bazy 'groups'
		elseif(isset($_POST["action"]) && $_POST["action"]=="updateGroups" && isset($_POST["parameter"]) && isset($_POST["value"]) && isset($_POST["identifier"])) {
			$par=$_POST["parameter"];
			$val=$_POST["value"];
			$id=$_POST["identifier"];
			//echo"par: $par, val: $val, id: $id";
			mysql_query("UPDATE groups SET $par='$val' where Id=$id");
			echo "OK-grp";
		}
		//zlecenie updateu bazy 'config'
		elseif(isset($_POST["action"]) && $_POST["action"]=="updateConfigDB" && isset($_POST["parameter"]) && isset($_POST["value"])) {
			$par=$_POST["parameter"];
			$val=$_POST["value"];
			mysql_query("UPDATE config SET val='$val' where param='$par'");
			echo "OK-cnf";
		}
		//zlecenie update'u bazy devices
		elseif(isset($_POST["action"]) && $_POST["action"]=="updateDevices" &&isset($_POST["parameter"]) && isset($_POST["value"]) && isset($_POST["identifier"]) && $_POST["identifier"]>0) {
			$par=$_POST["parameter"];
			$val=$_POST["value"];
			$id=$_POST["identifier"];
			if($par=="spot" && $val=="-1") {
				$q=mysql_fetch_assoc(mysql_query("SELECT spot FROM devices WHERE id='$id'"));
				$spot=$q["spot"];
				if($spot==0)
					$spot=-99999;
				else
					$spot=$spot*(-1);
				mysql_query("UPDATE devices SET spot=$spot WHERE id='$id'");
			}
			else
				mysql_query("UPDATE devices SET $par='$val' where Id=$id");
			echo "OK";
		}
		else {
			echo"Coś poszło nie tak...";
		}
	}
	mysql_close();
?>