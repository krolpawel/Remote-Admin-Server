<?php
	session_start();
	include_once("access.php");
	include_once("functions.php");

	if(!LoggedChecker()) {
		header('Location: login.php');
	}
?>
<html>
	<head>
		<title>RA - Event Editor</title>
		<meta charset='utf-8'>
		<!--<meta http-equiv="X-UA-Compatible" content="IE=edge">  20150819 -->
		<meta http-equiv="Content-Language" content="pl">
		<link rel="shortcut icon" href="RA.ico">
		<link rel="Stylesheet" type="text/css" href="style.css" />
		<script src="lib/jquery.min.js"></script>
		<script type="text/javascript" src="lib/jquery.mousewheel-3.0.6.pack.js"></script>
		<script type="text/javascript" src="fancyBox/jquery.fancybox.js?v=2.1.5"></script>
		<script type='text/javascript'>
			$("document").ready(function(){
				$("#importF").on('change',function() {
					conf=confirm("Czy zaimportować do sesji plik "+$("#importF").val()+"?");
					if(conf==true) {
						document.getElementById("ticketsList").submit();
					}
				});
			});
			function importTickets() {
				/*fh = fopen(getScriptPath(), 0); // Open the file for reading
				if(fh!=-1) // If the file has been successfully opened
				{
					length = flength(fh);         // Get the length of the file    
					str = fread(fh, length);     // Read in the entire file
					fclose(fh);                    // Close the file
					
				// Display the contents of the file    
					write(str);    
				}*/
			}
			function changeState(ticket) {
				conf=confirm("Zmienić stan wybranego biletu?");
				if(conf==true) {
					$.ajax({
						type: 'POST',
						dataType: 'text',
						headers: {'X-Requested-With': 'XMLHttpRequest'},
						data: {
							action:'changeTicketState',
							tic:ticket,
							ev:$("#evId").val()
						},
						url: "updater.php",
						success: function(response){
							switch(response) {
								case 'OK-stateChanged':
									window.location.reload();
									break;
								default:
									console.log("BŁĄD");
							}
						},
						error: function() {
							console.log('request failed');
						}
					});
				}
			}
		</script>
	</head>
	<body>
	<?php
	/*
	if(isset($_FILES["importF"]) && isset($_POST["evId"])) {
		$myfile = fopen($_FILES["importF"]["tmp_name"], "r") or die("Unable to open file!");
		$i=0;
		$err=false;
		$evId=$_POST["evId"];
		while(!feof($myfile)) {
			$i++;
			$line=fgets($myfile);
			$line=trim($line);
			if(strpos($line,' ') !== false || strpos($line,'\'') !== false || strpos($line,'"') !== false) {
				echo"<script type='text/javascript'>alert('Błąd w pliku.\\nNie można zaimportować.\\nKod nie może zawierać znaków spacji, cudzysłowiu oraz apostrofu.');</script>";
				$err=true;
				break;
			}
		}
		fclose($myfile);
		if($err==false) {
			$myfile = fopen($_FILES["importF"]["tmp_name"], "r") or die("Unable to open file!");
			while(!feof($myfile)) {
				$line=fgets($myfile);
				$line=trim($line);
				mysql_query("INSERT INTO tickets (ticket,event) VALUES ('$line','$evId')");
			}
		}
		//echo"IN";
		//$_GET["id"]=$_POST["evId"];
	}
	*/
	?>
	<form name='ticketsList' id='ticketsList' action='ticList.php?id=<?php echo $_GET["id"]; ?>' method='POST' enctype='multipart/form-data'>
		<table>
			<tr>
				<th colspan='5'>
					Nowy import: <input type='file' accept='.txt' name='importF' id='importF' value='Nowy Import'>
					<input type='button' value='zamknij' style='background-color:lightcoral; color:white;' onclick='window.close()'>
				</th>
			</tr>
			<tr>
				<th>LP</th>
				<th>numer</th>
				<th>stan</th>
				<th>ostatnia zmiana</th>
				<th>Zmień</th>
			</tr>
		<?php
			echo"<input type='hidden' name='evId' id='evId' value='{$_GET["id"]}' />";
			$event=$_GET["id"];
			$q=mysql_query("SELECT * FROM tickets WHERE event='$event'");
			$counter=1;
			while($row=mysql_fetch_assoc($q)) {
				echo "<tr>
						<td>$counter</td>
						<td>{$row["ticket"]}</td>
						<td>{$row["state"]}</td>
						<td>{$row["lastChange"]}</td>
						<td><input type='button' value='zmień stan' onclick='changeState(\"{$row["ticket"]}\")'></td>
					</tr>";
				$counter++;
			}
		?>
		</table>
	</form>
	</body>
</html>