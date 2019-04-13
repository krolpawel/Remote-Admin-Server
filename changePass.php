<?php
	session_start();
	include_once("access.php");
	include_once("functions.php");
	$conn=mysql_connect($DBhost,$DBuser,$DBpass) or die("błąd połączenia z bazą");
	$db_found=mysql_select_db("tp",$conn);
	if(!LoggedChecker()) {
		header('Location: login.php');
	}
?>
<!DOCTYPE html>
<html lang="pl">
	<head>
		<meta charset='utf-8'>
		<meta http-equiv="Content-Language" content="pl">
		<title>Remote Admin - Ticketpro Polska PDA Service</title>
		<script src="lib/jquery.min.js"></script>
		<link rel="shortcut icon" href="RA.ico">
		<link rel="Stylesheet" type="text/css" href="style.css" />
		<script type="text/javascript" language="javascript">
			function ValidatePass() {
				oldPass=$("#oldPass").val();
				newPass=$("#newPass").val();
				newPassConfirm=$("#newPassConfirm").val();
				if(oldPass!="" && newPass!="" && newPassConfirm!="") {
					if(newPass==newPassConfirm) {
						if(newPass.length>=4) {
							$("#changePass").submit();
						}
						else
							$("#errorBox").text("Długość hasła musi mieścić się<br/>w przedziale 4-30 znaków");
					}
					else
						$("#errorBox").text("Nowe hasła nie pasją do siebie");
				}
				else
					$("#errorBox").text("Wszystkie pola muszą być wypełnione");
			}
			function ShowConfirm() {
				alert("Hasło zostało zmienione. Zaloguj się ponownie!");
			}
		</script>
	</head>
	<body>
<?php	
	if($db_found) {
		if(isset($_POST['oldPass']) && isset($_POST["newPass"])) {
			$q=mysql_fetch_assoc(mysql_query("SELECT password FROM users WHERE login='{$_POST['login']}'"));
			$passDB=$q["password"];
			$pass=PasswordEncryptor($_POST["oldPass"]);
			$newPass=PasswordEncryptor($_POST["newPass"]);
			if($passDB==$pass) {
				mysql_query("UPDATE users SET password='$newPass' WHERE login='{$_POST['login']}'");
				echo"<script>ShowConfirm();</script>";
				unset($_SESSION["login"]);
				mysql_query("UPDATE users SET session='' WHERE login='{$_POST['login']}'");
				header('Location: login.php');
			}
			else { $_POST['errMsg']="Stare hasło jest nieprawidłowe!"; }
		}
	}
?>
<form id='changePass' method="POST" action="changePass.php">
	<input type='hidden' name='login' value="<?php echo $_SESSION["login"]; ?>">
	<table>
		<tr>
			<td>Użytkownik:</td>
			<td><b><?php echo $_SESSION["login"]; ?></b></td>
		</tr>
		<tr>
			<td>Stare hasło:</td>
			<td><input type='password' name='oldPass' id='oldPass' required/></td>
		</tr>
		<tr>
			<td>Nowe hasło:</td>
			<td><input type='password' name='newPass' id='newPass' required/></td>
		</tr>
		<tr>
			<td>Potwierdź<br/>nowe hasło:</td>
			<td><input type='password' id='newPassConfirm' required/></td>
		</tr>
		<tr>
			<td colspan='2' id='errorBox' style='color:red;'><?php if(isset($_POST['errMsg'])) echo $_POST['errMsg']; ?></td>
		</tr>
		<tr>
			<td colspan='2'><input type='button' id='btSendForm' value='Zmień' onclick='ValidatePass()'/></td>
		</tr>
	</table>
</form>
</body>
<script type='text/javascript' language='javascript'>
	$('#newPassConfirm').keyup(function(event){
		if(event.keyCode == 13){
			$('#btSendForm').click();
		}
	});
</script>
</html>