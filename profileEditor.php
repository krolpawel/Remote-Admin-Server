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
		if(isset($_POST["profileName"]) && isset($_POST["ssid"]) && isset($_POST["securityType"]) && isset($_POST["authType"]) && isset($_POST["encType"]) && isset($_POST["passPhrase"]) && isset($_POST["ipAddress"]) && isset($_POST["subnetMask"]) && isset($_POST["gateway"]) && isset($_POST["gateway2"]) && isset($_POST["dns"]) && isset($_POST["dns2"]) && isset($_POST["powerMode"]) && isset($_POST["mode"]) && isset($_POST["description"])) {
			$name=$_POST["profileName"];
			$ssid=$_POST["ssid"];
			$secType=$_POST["securityType"];
			$authType=$_POST["authType"];
			$encType=$_POST["encType"];
			$pass=$_POST["passPhrase"];
			
			$amm=$_POST["mixedMode"];
			$aamm=$_POST["AESmixedMode"];
			
			$addressing=$_POST["addressingMode"];
			$ip=$_POST["ipAddress"];
			$mask=$_POST["subnetMask"];
			$gateway=$_POST["gateway"];
			$gateway2=$_POST["gateway2"];
			$dns=$_POST["dns"];
			$dns2=$_POST["dns2"];
			$pwrMode=$_POST["powerMode"];
			$description=$_POST["description"];
			if($_POST["mode"]=='newProfile') {
				$code=randomString(16);
				mysql_query("INSERT INTO barcodesaddprofile VALUES ('','$name','$ssid','$secType','$authType','$encType','$pass','$amm','$aamm','$addressing','$ip','$mask','$gateway','$gateway2','$dns','$dns2','$pwrMode','$code','$description')");
			}
			else {
				$code=$_POST["mode"];
				mysql_query("UPDATE barcodesaddprofile SET Name='$name', Ssid='$ssid', SecType='$secType', AuthType='$authType', EncType='$encType', PassPhrase='$pass', AllowMixedMode='$amm', AESAllowMixedMode='$aamm', AddressingMode='$addressing', IpAddress='$ip', SubnetMask='$mask', Gateway='$gateway', Gateway2='$gateway2', Dns='$dns', Dns2='$dns2', PowerMode='$pwrMode', description='$description' WHERE code='$code'");
			}
			header('Location: index.php');
		}
	}
?>
<!DOCTYPE html>
<html lang="pl">
	<head>
		<meta charset='utf-8'>
		<meta http-equiv="Content-Language" content="pl">
		<title>Profile Editor - Remote Admin - Ticketpro Polska PDA Service</title>
		<script src="lib/jquery.min.js"></script>
		<script type="text/javascript" src="lib/jquery.mousewheel-3.0.6.pack.js"></script>
		<link rel="shortcut icon" href="RA.ico">
		<link rel="Stylesheet" type="text/css" href="style.css" />
		<script type='text/javascript'>
			function DeleteProfile(code) {
				window.open("updater.php?deletecode="+code);
				window.close();
			}
			function formValidator() {
				if(
					Validator('tb','profileName') &&
					Validator('tb','ssid') &&
					Validator('tb','securityType') &&
					Validator('tb','authType') &&
					Validator('tb','encType') &&
					Validator('tb','passPhrase') &&
					Validator('ip','ipAddress') &&
					Validator('ip','subnetMask') &&
					Validator('ip','gateway') &&
					Validator('altIp','gateway2') &&
					Validator('ip','dns') &&
					Validator('altIp','dns2') &&
					Validator('tb','powerMode')) {
					
					document.getElementById('makeProfileForm').submit();
				}
			}
			function Validator(mode, id) { //mode=ip/altIp/tb
				v=$("#"+id).val();
				switch(mode) {
					case 'tb':
						if(v!="") {
							$("#"+id).css('border','');
							return true;
						}
						else {
							$("#"+id).css('border','3px dashed red');
							$('#errorContener').html('Uzupełnij wszystkie wymagane pola');
							return false;
						}
						break;
					case 'ip':
						if(v!="") {
							reg="^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$";
							oRegExp=new RegExp(reg);
							if(oRegExp.test(v)) {
								$("#"+id).css('border','');
								return true;
							}
							$("#"+id).css('border','3px dashed red');
							$('#errorContener').html('Adres IP ma niewłaściwy format');
							return false;
						}
						else {
							$("#"+id).css('border','3px dashed red');
							$('#errorContener').html('Uzupełnij wszystkie wymagane pola');
							return false;
						}
						break;
					case 'altIp':
						if(v=="") {
							$("#"+id).css('border','');
							return true;
						}
						else {
							reg="^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$";
							oRegExp=new RegExp(reg);
							if(oRegExp.test(v)) {
								$("#"+id).css('border','');
								return true;
							}
							$("#"+id).css('border','3px solid red');
							$('#errorContener').html('Adres IP ma niewłaściwy format. Pole może być puste');
							return false;
						}
						break;
				}
			}
			function securityChanged() {
				newVal=$("#securityType").val();
				switch(newVal) {
					case 'LEGACY':
					case 'WPA_ENTERPRISE':
					case 'WPA2_ENTERPRISE':
						$("#atNone").css("display","");
						$("#atEapTls").css("display","");
						$("#atEapFastMsChapV2").css("display","");
						$("#atEapFastTls").css("display","");
						$("#atEapFastEapGtc").css("display","");
						$("#atPeapTls").css("display","");
						$("#atPeapGtc").css("display","");
						$("#atPeapMsChapV2").css("display","");
						$("#atLeap").css("display","");
						$("#atTtls").css("display","");
						$("#atCertificate").css("display","none");
						break;
					case 'WAPI':
						$("#atNone").css("display","none");
						$("#atEapTls").css("display","none");
						$("#atEapFastMsChapV2").css("display","none");
						$("#atEapFastTls").css("display","none");
						$("#atEapFastEapGtc").css("display","none");
						$("#atPeapTls").css("display","none");
						$("#atPeapGtc").css("display","none");
						$("#atPeapMsChapV2").css("display","none");
						$("#atLeap").css("display","none");
						$("#atTtls").css("display","none");
						$("#atCertificate").css("display","");
						break;
					
					case 'WPA_PERSONAL':
					case 'WPA2_PERSONAL':
						$("#atNone").css("display","");
						$("#atEapTls").css("display","none");
						$("#atEapFastMsChapV2").css("display","none");
						$("#atEapFastTls").css("display","none");
						$("#atEapFastEapGtc").css("display","none");
						$("#atPeapTls").css("display","none");
						$("#atPeapGtc").css("display","none");
						$("#atPeapMsChapV2").css("display","none");
						$("#atLeap").css("display","none");
						$("#atTtls").css("display","none");
						$("#atCertificate").css("display","none");
						break;
				}
			}
		</script>
	</head>
	<body>
		<form name='makeProfileForm' id='makeProfileForm' action='profileEditor.php' method='POST'>
			<input type='hidden' name='mode' id='mode' value='newProfile'>
			<table>
				<tr>
					<th id='tablename' colspan='2'>Dodawanie profilu sieciowego</th>
				</tr>
				<tr>
					<td>Nazwa:</td>
					<td><input type='text' name='profileName' id='profileName'></td>
				</tr>
				<tr>
					<td>SSID:</td>
					<td><input type='text' id='ssid' name='ssid'></td>
				</tr>
				<tr>
					<td>Typ Zabezpieczeń:</td>
					<td>
						<select id='securityType' name='securityType' onChange="securityChanged()">
							<option value=''>Wybierz...</option>
							<option id='stLegacy' value='LEGACY'>Legacy</option>
							<option id='stWapi' value='WAPI'>WAPI</option>
							<option id='stWpaPersonal' value='WPA_PERSONAL'>WPA Personal</option>
							<option id='stWpaEnterprise' value='WPA_ENTERPRISE'>WPA Enterprise</option>
							<option id='stWpa2Personal' value='WPA2_PERSONAL'>WPA2 Personal</option>
							<option id='stWpa2Enterprise' value='WPA2_ENTERPRISE'>WPA2 Enterprise</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Typ Uwierzytelniania:</td>
					<td>
						<select id='authType' name='authType'>
							<option value=''>Wybierz...</option>
							<option style='display:none' id='atNone' value='NONE'>None</option>
							<option style='display:none' id='atEapTls' value='EAP-TLS'>EAP-TLS</option>
							<option style='display:none' id='atEapFastMsChapV2' value='EAP-FAST_MS_CHAP_V2'>EAP-FAST MS CHAP v2</option>
							<option style='display:none' id='atEapFastTls' value='EAP-FAST_TLS'>EAP-FAST TLS</option>
							<option style='display:none' id='atEapFastEapGtc' value='EAP-FAST_EAP-GTC'>EAP-FAST EAP-GTC</option>
							<option style='display:none' id='atPeapTls' value='PEAP_TLS'>PEAP TLS</option>
							<option style='display:none' id='atPeapGtc' value='PEAP_GTC'>PEAP GTC</option>
							<option style='display:none' id='atPeapMsChapV2' value='PEAP_MS_CHAP_V2'>PEAP MS CHAP v2</option>
							<option style='display:none' id='atLeap' value='LEAP'>LEAP</option>
							<option style='display:none' id='atTtls' value='TTLS'>TTLS</option>
							<option style='display:none' id='atCertificate' value='CERTIFICATE'>Certyfikat</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Typ szyfrowania:</td>
					<td>
						<select id='encType' name='encType'>
							<option value=''>Wybierz...</option>
							<option id='etAes' value='AES'>AES</option>
							<option id='etTkip' value='TKIP'>TKIP</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Hasło:</td>
					<td><input type='text' id='passPhrase' name='passPhrase'></td>
				</tr>
				<tr>
					<td>Tryb mieszany:</td>
					<td>
						<input type='radio' name='mixedMode' id='mixedModeOn' value='1' checked='checked'><label>TAK</label><br/>
						<input type='radio' name='mixedMode' id='mixedModeOff' value='0'><label>NIE</label>
					</td>
				</tr>
				<tr>
					<td>Tryb mieszany AES:</td>
					<td>
						<input type='radio' name='AESmixedMode' id='AESmixedModeOn' value='1' checked='checked'><label>TAK</label><br/>
						<input type='radio' name='AESmixedMode' id='AESmixedModeOff' value='0'><label>NIE</label>
					</td>
				</tr>
				<tr>
					<td>Tryb adresowania:</td>
					<td>
						<select id='addressingMode' name='addressingMode'>
							<option value='STATIC'>Statyczny</option>
							<option value='DHCP'>DHCP</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Adres IP:</td>
					<td><input type='text' id='ipAddress' name='ipAddress'></td>
				</tr>
				<tr>
					<td>Maska podsieci:</td>
					<td><input type='text' id='subnetMask' name='subnetMask'></td>
				</tr>
				<tr>
					<td>Brama:</td>
					<td><input type='text' id='gateway' name='gateway'></td>
				</tr>
				<tr>
					<td>Brama alternatywna:</td>
					<td><input type='text' id='gateway2' name='gateway2'></td>
				</tr>
				<tr>
					<td>DNS:</td>
					<td><input type='text' id='dns' name='dns'></td>
				</tr>
				<tr>
					<td>DNS alternatywny:</td>
					<td><input type='text' id='dns2' name='dns2'></td>
				</tr>
				<tr>
					<td>Tryb zasilania:</td>
					<td>
						<select id='powerMode' name='powerMode'>
							<option value=''>Wybierz...</option>
							<option value='CAM'>CAM</option>
							<option value='FAST'>FAST</option>
							<option value='MAX'>MAX</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Opis profilu:</td>
					<td>
						<textarea name='description' id='description'></textarea>
				<tr><td colspan='2' id='errorContener' style='color:red'></td></tr>
				<tr>
					<td colspan='2'>
						<input type='button' id='btMakeProfile' value='Stwórz profil' onclick='formValidator()'> 
						<input type='button' id='btCancel' value='Anuluj zmiany' onclick='window.close()' style='display:none;'>
						<input type='button' id='btDelete' value='Usuń profil' onclick='deleteProfile()' style='display:none;'>
					</td>
				</tr>
			</table>
		</form>
	</body>
</html>
<?php
if(isset($_GET["action"]) && $_GET["action"]=='editProfile' && isset($_GET["code"]) && $_GET["code"]!="") {
	$q=mysql_query("SELECT * FROM barcodesaddprofile where code='{$_GET["code"]}'");
	while($row=mysql_fetch_assoc($q)) {
		$name=$row["Name"];
		$ssid=$row["Ssid"];
		$secType=$row["SecType"];
		$authType=$row["AuthType"];
		$encType=$row["EncType"];
		$pass=$row["PassPhrase"];
		
		$amm=$row["AllowMixedMode"];
		$aamm=$row["AESAllowMixedMode"];
		
		$addressing=$row["AddressingMode"];
		$ip=$row["IpAddress"];
		$mask=$row["SubnetMask"];
		$gateway=$row["Gateway"];
		$gateway2=$row["Gateway2"];
		$dns=$row["Dns"];
		$dns2=$row["Dns2"];
		$pwrMode=$row["PowerMode"];
		$code=$row["code"];
		$description=$row["description"];
		echo"
		<script type='text/javascript'>
			$('#tablename').html('Edycja profilu sieciowego $name');
			$('#mode').val('$code');
			$('#btMakeProfile').val('Zatwierdź zmiany');
			$('#btDelete').attr('onclick',\"DeleteProfile('$code')\");
			$('#btDelete').css('display','inline-block');
			$('#btCancel').css('display','inline-block');
			
			$('#profileName').val('$name');
			$('#ssid').val('$ssid');
			$('#securityType').val('$secType');
			$('#authType').val('$authType');
			$('#encType').val('$encType');
			$('#passPhrase').val('$pass');
			if($amm=='0')
				$('#mixedModeOff').prop('checked',true);
			if($aamm=='0')
				$('#AESmixedModeOff').prop('checked',true);
			$('#addressingMode').val('$addressing');
			$('#ipAddress').val('$ip');
			$('#subnetMask').val('$mask');
			$('#gateway').val('$gateway');
			$('#gateway2').val('$gateway2');
			$('#dns').val('$dns');
			$('#dns2').val('$dns2');
			$('#powerMode').val('$pwrMode');
			$('#description').val('$description');
			
		</script>";
	}
}
?>
