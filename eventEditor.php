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
		<script type="text/javascript">
			function formValidator() {
				var err=false;
				if($('#evName').val()=="") {
					alert("Podaj nazwę!");
					err=true;
				}
				var startDate="";
				startDate=new Date($('#sY').val(),$('#sM').val(),$('#sD').val(),$('#sH').val(),$('#si').val(),$('#ss').val(),0);
				endDate=new Date($('#eY').val(),$('#eM').val(),$('#eD').val(),$('#eH').val(),$('#ei').val(),$('#es').val(),0);
				var diff=(endDate-startDate)/1000; //zapis w sekundach
				if(diff<1) {
					alert("Błędnie wprowadzone daty");
					err=true;
				}
				if(err==false) {
					document.getElementById('event').submit();
				}
			}
			function anuluj() {
				window.close();
			}
			function deleteEvent() {
				ident=$("#evId").val();
				conf=confirm("Sesja zostanie usunięta bezpowrotnie.\nBilety z nią związane również!\n\nJesteś pewny?");
				if(conf==true) {
					$.ajax({
						type: 'POST',
						dataType: 'text',
						headers: {'X-Requested-With': 'XMLHttpRequest'},
						data: {
							action:'deleteEvent',
							id:ident
						},
						url: "updater.php",
						success: function(response){
							switch(response) {
								case 'OK-eventDeleted':
									window.close();
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
	//odbiór danych i update bazy
	if(isset($_POST['evName']) && isset($_POST['evId'])) {
		$name=$_POST["evName"];
		$id=$_POST["evId"];
		$startDate=$_POST["sY"]."-".$_POST["sM"]."-".$_POST["sD"]." ".$_POST["sH"].":".$_POST["si"].":".$_POST["ss"];
		$endDate=$_POST["eY"]."-".$_POST["eM"]."-".$_POST["eD"]." ".$_POST["eH"].":".$_POST["ei"].":".$_POST["es"];
		if($id=='new') {
			mysql_query("INSERT INTO events (name,start,end) VALUES ('$name','$startDate','$endDate')");
		}
		else {
			mysql_query("UPDATE events SET name='$name', start='$startDate', end='$endDate' WHERE Id='$id'");
		}
		echo "<script type='text/javascript'>anuluj();</script>";
	}
	//formularz tworzenia sesji
	if(isset($_GET["action"]) && $_GET["action"]=="newEvent"){
		$idT=mysql_fetch_assoc(mysql_query("SELECT MAX(Id) as Id FROM events"));
		$id=$idT["Id"]+1;
		echo"<form name='event' id='event' method='POST' action='eventEditor.php'><table>
			<tr>
				<th colspan='2'>Nowa sesja</th>
			</tr>
			<tr>
				<th>Id</th>
				<td>
					(new)
					<input type='hidden' name='evId' id='evId' value='new'>
				</td>
			</tr>
			<tr>
				<th>Nazwa</th>
				<td><input type='textbox' name='evName' id='evName'></td>
			</tr>
			<tr>
				<th>Data<br/>rozpoczęcia</th>
				<td>";
				echo DateTimeSelectBuilder('sY','','Y',date('Y'))." - ";
				echo DateTimeSelectBuilder('sM','','M',date('n'))." - ";
				echo DateTimeSelectBuilder('sD','','D',date('j'))."<br/>";
				echo DateTimeSelectBuilder('sH','','H',date('H'))." : ";
				echo DateTimeSelectBuilder('si','','i',date('i'))." : ";
				echo DateTimeSelectBuilder('ss','','s',date('s'));
				echo"</td>
			</tr>
			<tr>
				<th>Data<br/>zakończenia</th>
				<td>";
				$dateAfter=strtotime(date('Y-n-j H:i:s'))+3600;
				echo DateTimeSelectBuilder('eY','','Y',date('Y',$dateAfter))." - ";
				echo DateTimeSelectBuilder('eM','','M',date('n',$dateAfter))." - ";
				echo DateTimeSelectBuilder('eD','','D',date('j',$dateAfter))."<br/>";
				echo DateTimeSelectBuilder('eH','','H',date('H',$dateAfter))." : ";
				echo DateTimeSelectBuilder('ei','','i',date('i',$dateAfter))." : ";
				echo DateTimeSelectBuilder('es','','s',date('s',$dateAfter));
				echo"</td>
			</tr>
			<tr>
				<td colspan='2'>
					<input type='button' value='zatwierdź' style='background-color: lightgreen;' onclick=\"formValidator()\"> 
					<input type='button' value='anuluj' style='background-color: yellow;' onclick='anuluj()'>
		</table>";
	}
	//formularz edycji sesji
	else if(isset($_GET["action"]) && $_GET["action"]=="editEvent" && $_GET["id"]!="") {
		$id=$_GET["id"];
		$row=mysql_fetch_assoc(mysql_query("SELECT * FROM events WHERE Id='$id'"));
		$name=$row["name"];
		$start=strtotime($row["start"]);
		$end=strtotime($row['end']);
		echo"<form name='event' id='event' method='POST' action='eventEditor.php'><table>
			<tr>
				<th colspan='2'>Edytor sesji</th>
			</tr>
			<tr>
				<th>Id</th>
				<td>
					$id
					<input type='hidden' name='evId' id='evId' value='$id'>
				</td>
			</tr>
			<tr>
				<th>Nazwa</th>
				<td><input type='textbox' name='evName' id='evName' value='$name'></td>
			</tr>
			<tr>
				<th>Data<br/>rozpoczęcia</th>
				<td>";
				echo DateTimeSelectBuilder('sY','','Y',date('Y',$start))." - ";
				echo DateTimeSelectBuilder('sM','','M',date('n',$start))." - ";
				echo DateTimeSelectBuilder('sD','','D',date('j',$start))."<br/>";
				echo DateTimeSelectBuilder('sH','','H',date('H',$start))." : ";
				echo DateTimeSelectBuilder('si','','i',date('i',$start))." : ";
				echo DateTimeSelectBuilder('ss','','s',date('s',$start));
				echo"</td>
			</tr>
			<tr>
				<th>Data<br/>zakończenia</th>
				<td>";
				$dateAfter=strtotime(date('Y-n-j H:i:s'))+3600;
				echo DateTimeSelectBuilder('eY','','Y',date('Y',$end))." - ";
				echo DateTimeSelectBuilder('eM','','M',date('n',$end))." - ";
				echo DateTimeSelectBuilder('eD','','D',date('j',$end))."<br/>";
				echo DateTimeSelectBuilder('eH','','H',date('H',$end))." : ";
				echo DateTimeSelectBuilder('ei','','i',date('i',$end))." : ";
				echo DateTimeSelectBuilder('es','','s',date('s',$end));
				echo"</td>
			</tr>
			<tr>
				<td>
					<input type='button' value='Zatwierdź' style='background-color: lightgreen;' onclick='formValidator()'> 
					<input type='button' value='Anuluj' style='background-color: yellow;' onclick='anuluj()'>
				</td>
				<td>
					<input type='button' value='Usuń sesję' style='background-color: orange;' onclick='deleteEvent()'/>
		</table>";
	}
	
?>
</body></html>