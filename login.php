<?php
	session_start();
	include_once("access.php");
	include_once("functions.php");
	include_once("Authentication.class.php");	
	
	//Wylogowanie
	if(isset($_GET["action"]) && $_GET["action"]=='logout') {
		mysql_query("UPDATE users SET session='' WHERE login='".$_SESSION["login"]."'");
		unset($_SESSION["login"]);
		unset($_SESSION["sesId"]);
		header('Location: login.php');
	}
	//przesłane dane do sprawdzenia i logowanie
	if(isset($_POST["login"]) && $_POST["login"]!="" && isset($_POST["pass"]) && $_POST["pass"]!="") {
		$auth=new Authentication($_POST["login"],$_POST["pass"]);
		if($auth->TryLogin()==true) {
			header('Location:index.php');
		}
	}
	if(isset($_SESSION["login"]) && $_SESSION["login"]!="") {
		header('Location: index.php');
	}
	?>
<!DOCTYPE html>
<html lang="pl">
	<head>
		<meta charset='utf-8'>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta http-equiv="Content-Language" content="pl">
		<title>Remote Admin - Ticketpro Polska PDA Service - Login</title>
		<script src="lib/jquery.min.js"></script>
		<link rel="shortcut icon" href="RA.ico">
		<link rel="Stylesheet" type="text/css" href="style.css" />
	</head>
	<body>	
<?php
echo"
	<form id='loginForm' method='POST' action='login.php'>
		<table style='text-align:center;'>
			<tr>
				<th colspan='2'>Remote Admin - Logowanie</th>
			<tr>
				<th>Login: </th>
				<td><input type='text' id='tbLogin' name='login'></td>
			</tr>
			<tr>
				<th>Hasło: </th>
				<td><input type='password' name='pass'></td>
			</tr>
			<tr>
				<td colspan='2'>";
				if(isset($_POST["info"])) {
					$info=str_replace('_',' ',$_POST["info"]);
					echo"<p style='color:red;'>$info</p>";
				}
				echo"
				</td>
			</tr>
			<tr>
				<td colspan='2'><input type='submit' value='Zaloguj'/></td>
			</tr>
		</table>
	</form>
</body>
<script>
	$('#tbLogin').focus();
</script>
</html>";
?>