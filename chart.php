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
<!doctype html>
<html>
	<head>
		<title>Remote Admin - Wykres Zużycia Baterii</title>
		<script src="lib/Chart.js"></script>
		<link rel="shortcut icon" href="RA.ico">
		<style>
		.scName {
			text-align:center;
			font-family: "Comic Sans MS";
		}
		</style>
	</head>
	<body>
		
<?php
	include_once("access.php");
	$conn=mysql_connect($DBhost,$DBuser,$DBpass) or die("błąd połączenia z bazą");
	$db_found=mysql_select_db("tp",$conn) or die("Nie można ustalić bazy");
	//mysql_query("set names utf8");
	if($db_found && isset($_GET["Id"])) {
		$query2=mysql_fetch_assoc(mysql_query("SELECT name FROM devices WHERE id='{$_GET['Id']}'"));
		$name=$query2['name'];
		echo"<h1 class='scName'>$name</h1>";
		$countT=mysql_fetch_assoc(mysql_query("SELECT count(batt) FROM battstatus WHERE Id='".$_GET["Id"]."' ORDER BY ts ASC;"));
		$count = (integer)$countT["count(batt)"];
		//var_dump($count);
		if($count>30)
			$interval=floor($count/30);
		else
			$interval=1;
		$counter=0;
		$counter2=0;
		$query="SELECT batt,ts,acStatus FROM battstatus WHERE Id='".$_GET["Id"]."' ORDER BY ts ASC";
		$q=mysql_query($query);
		while($row=mysql_fetch_assoc($q)) {
			$counter++;
			//var_dump($row, $counter, NULL);
			if($counter==$interval) {
				$battlvl[$counter2]=$row["batt"];
				$ts[$counter2]=$row["ts"];
				$ac[$counter2]=$row["acStatus"];
				$counter2++;
				$counter=0;
			}
		}
		/*for($i=0;$i<Count($ts);$i++) {
			echo $ts[$i]." --> ".$battlvl[$i]."<br>";
		}
		echo Count($ts);*/
		echo"
			<script>
				var lineChartData = {
					labels : [";
					for($i=0;$i<Count($ts);$i++) {
						if($i!=0)
							echo",";
						echo "\"".substr($ts[$i],11)."\"";
					}
					echo"],
					datasets : [
						{
							label: \"first label\",
							fillColor : \"rgba(80,0,0,0.2)\",
							strokeColor : \"rgba(150,100,100,1)\",
							pointColor : \"rgba(200,0,0,1)\",
							pointStrokeColor : \"#fff\",
							pointHighlightFill : \"#fff\",
							pointHighlightStroke : \"rgba(255,0,0,1)\",
							data : [";
								for($i=0;$i<Count($battlvl);$i++) {
									if($i!=0)
										echo",";
									echo ($battlvl[$i]);
								}
							echo"]
						},
						{
							label: \"second label\",
							fillColor : \"rgba(0,80,0,0.1)\",
							strokeColor : \"rgba(100,150,100,1)\",
							pointColor : \"rgba(0,200,0,1)\",
							pointStrokeColor : \"#fff\",
							pointHighlightFill : \"#fff\",
							pointHighlightStroke : \"rgba(0,255,0,1)\",
							data : [";
								for($i=0;$i<Count($battlvl);$i++) {
									if($i!=0)
										echo",";
									if($ac[$i]==1)
										$ac[$i]=100;
									echo ($ac[$i]);
								}
							echo"]
						}
					]
				}
			</script>
		";
	}
?>
		<div style="width:100%">
			<div>
				<canvas id="canvas" height="320" width="600"></canvas>
			</div>
		</div>
<script type="text/javascript">
	window.onload = function(){
		var ctx = document.getElementById("canvas").getContext("2d");
		window.myLine = new Chart(ctx).Line(lineChartData, {
			responsive: true
		});
	}
</script>
</body>
</html>