<?php
	session_start();
	include_once("access.php");
	include_once("functions.php");

	if(!LoggedChecker()) {
		header('Location: login.php');
	}
	CheckIntegritySpot();
?>
<!-- NOTATKI
	
#BUGS
	+(dużo pracy w wielu miejscach)profileEditor przy ustawianiu zabezpieczeń i urzierzytelniania mają być dostępne tylko odpowiednie opcje a nie wszystkie. trzeba to sprawdzić w Fusionie w skanerze
		-obsługa certyfikatów
#TODO
	-problem z wartością textboxów nazw grup oraz adresów GK przy urządzeniach i kodach. jak się trochę pokombinuje to jest problem
	-wszystkie funkcje grupowe przerobić tak żeby przechodziły przez updateDatabase
	-zabezpieczyć system przed SQLInjection
	-oprogramowanie try-catch wszędzie gdzie trzeba
	-odnajdywanie serwera. po włączeniu programu jeśli od razu nie uda się połączyć wyświetl alert 'czy adres serwera jest inny?' i jeśli tak to zapytaj o IP. możliwość ustawienia w aplikacji adresu serwera ręcznie oraz adresu backupowego
	-praca programu na sieci firmowej i ISP. niezależnie od tego ustawienia - Connection Manager? ConnMgr?
Czynności końcowe:

-->
<!DOCTYPE html>
<html lang="pl">
	<head>
		<meta charset='utf-8'>
		<!--<meta http-equiv="X-UA-Compatible" content="IE=edge">  20150819 -->
		<meta http-equiv="Content-Language" content="pl">
		<!--<meta http-equiv="refresh" content="100">-->
		<title>Remote Admin - Ticketpro Polska PDA Service</title>
		<script src="lib/jquery.min.js"></script>
		
		<script src="jqueryUI/jquery-ui.js"></script>
		<link rel="Stylesheet" type="text/css" href="jqueryUI/jquery-ui.css" />
		
		<script type="text/javascript" src="lib/jquery.mousewheel-3.0.6.pack.js"></script>
		<script type="text/javascript" src="fancyBox/jquery.fancybox.js?v=2.1.5"></script>
		<script type="text/javascript" src="lib/CryptoJS.js"></script>
		<link rel="stylesheet" type="text/css" href="fancyBox/jquery.fancybox.css?v=2.1.5" media="screen" />
		<script type="text/javascript" src="fancyBox/helpers/jquery.fancybox-media.js?v=1.0.6"></script>
		<link rel="shortcut icon" href="RA.ico">
		<link rel="Stylesheet" type="text/css" href="style.css" />
		
	</head>
	<body>
	<script type="text/javascript" language="javascript">
			
			//
			//FUNKCJE STAŁE/ZEWNĘTRZNE/OGÓLNE
			//
			
			//skrypt dla FancyBox (wyświetlanie animowanych ramek z obrazami)
			$(document).ready(function() {
				$(".various").fancybox({
					maxWidth	: 800,
					maxHeight	: 600,
					fitToView	: true,
					width		: '90%',
					height		: '90%',
					autoSize	: false,
					closeClick	: true,
					openEffect	: 'elastic',
					closeEffect	: 'elastic'
				});
			});
			//inicjalizacja kart jqueryUI
			$(function() {
				$("#tabs").tabs();
				$("#tabs-1").show();
				$("#tabs-2").hide();
				$("#tabs-3").hide();
				$("#tabs-4").hide();
				$("#tabs-5").hide();
			})
			//timer do automatycznego wylogowania
			$(function(){
				var t=5*1*1000;
				function resetTimer() {
					clearTimeout(t);
					t = setTimeout(Logout,15*60*1000);
				}
				document.onmousemove=resetTimer;
				document.onkeypress=resetTimer;
				resetTimer(); // Start the timer when the page loads
			});
			//wylogowanie
			function Logout() {
				window.location.replace("login.php?action=logout");
			}
			
			function ChangeMasterAdminPassword(konto) {
				window.open("changePass.php");
			}
			function resetDataBase(level) {
				if(level=='prepare') {
					$("#spResetDBTb").css("display","block");
					$("#btResetDB").attr("value","Zatwierdź");
					$("#btResetDB").attr("onclick","resetDataBase('finish')");
				}
				if(level=='finish') {
					if(confirm("Reset bazy spowoduje CAŁKOWITE usunięcie wybranych informacji! \nBez zmian pozostaną kody konfiguracyjne i profilowe.\n\nCzy jesteś pewny?")) {
						pwd=$("#tbResetDB").val();
						md=$("input[name=rbResetDbMode]:checked").val();
						if(pwd!="") {
							$.ajax({
								type: 'POST',
								dataType: 'text',
								headers: {'X-Requested-With': 'XMLHttpRequest'},
								data: {
									action:"resetDatabaseConfirmed",
									pass:pwd,
									mode:md
								},
								url: "updater.php",
								success: function(response){
									if(response=='Invalid password') {
										alert("Hasło nieprawidłowe!");
									}
									else {
										alert(response);
										$("#spResetDBTb").css("display","none");
										$("#btResetDB").attr("value","RESET");
										$("#btResetDB").attr("onclick","resetDataBase('prepare')");
										window.location.reload();
									}
								},
								error: function() {
									console.log('request failed');
								}
							});
						}
					}
				}
			}
			//funkcja walidująca pola tekstowe
			function Validator(str, mode) {
				if(mode=="password") {
					if(str.length<4 || str.length>30) {
						return false;
					}
					return true;
				}
				else if(mode=="sendMessage") {
					if($("#taMsg").val()=="") {
						return false;
					}
					return true;
				}
				else if(mode=="gk-cc") {
					if(str=="")
						return true;
					else
						mode="gk";
				}
				if(mode=="gk") {
					reg="^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$";
					oRegExp=new RegExp(reg);
					if(oRegExp.test(str)) {
						return true;
					}
					return false;
				}
				return false;
			}
			//zmiana PINu lokalnego
			function ChangePin(level) {
				if(level=="prepare") {
					$("#spPinTb").css("display","block");
					$("#pinbutton").css("display","none");
					$("#changePin").attr("value","Zatwierdź");
					$("#changePin").attr("onclick","ChangePin('confirm')");
					$("#changePinA").css("display","inline");
				}
				else if(level=='confirm') {
					if(Validator($("#tbPin").val(),'password') && $("#tbPinPass").val()!="") {
						pin=$("#tbPin").val();
						CheckPassword($("#tbPinPass").val());
					}
					else {
						alert("Pin musi się mieścić w przedziale 4-30 znaków.\nHasło jest wymagane, aby dokonać zmiany.");
					}
				}
				else if(level=='cancel') {
					$("#changePinA").css("display","none");
					$("#spPinTb").css("display","none");
					$("#tbPin").val("");
					$("#tbPinPass").val("");
					$("#pinbutton").css("display","block");
					$("#changePin").attr("value","Zmień");
					$("#changePin").attr("onclick","ChangePin('prepare')");
					$("#pinbutton").attr("onclick","showPinToggle('"+pin+"')");
					
				}
			}
			//Autoryzacja przy zmianie kluczowych parametrów
			function CheckPassword(pass) {
				$.ajax({
					type: 'POST',
					dataType: 'text',
					headers: {'X-Requested-With': 'XMLHttpRequest'},
					data: {
						action:"CheckPassword",
						password:pass
					},
					url: "updater.php",
					success: function(response){
						switch(response) {
							case 'OK-pass':
								updateDatabase("updateConfigDB","localPin",pin,"","#spPin");
								$("#spPinTb").css("display","none");
								$("#tbPin").val("");
								$("#tbPinPass").val("");
								
								$("#pinbutton").css("display","block");
								$("#changePin").attr("value","Zmień");
								$("#changePin").attr("onclick","ChangePin('prepare')");
								$("#pinbutton").attr("onclick","showPinToggle('"+pin+"')");
								$("#changePinA").css("display","none");
								return true;
								break;
							default:
								console.log("def");
								return false;
						}
						console.log(response);
					},
					error: function() {
						console.log('request failed');
						return false;
					}
				});
			}
			
			
			//
			//		PRZEŁĄCZNIKI PROSTE
			//
			
			//zaznacz/odznacz wszystkie
			function cbToggle(mode) {
				if(mode=='select')
					$("input[name='devices[]']").prop("checked",true);
				else if(mode=='deselect')
					$("input[name='devices[]']").prop("checked",false);
			}
			//przełącznik do pokazywania **** lub prawdziwej zawartości pola PIN
			function showPinToggle(pin) {
				if($("#spPin").html()=="****") {
					$("#spPin").text(pin);
					$("#pinbutton").attr("value","Ukryj");
				}
				else {
					$("#spPin").text("****");
					$("#pinbutton").attr("value","Pokaż");
				}
			}
			
			
			//
			//		FUNKCJE GRUP
			//
			
			//drag and drop skanerów do grup
			function dragDev(ev,devId) {
				ev.dataTransfer.setData("text", devId);
			}
			function allowDrop(ev) {
				ev.preventDefault();
			}
			function dropDev(ev,grId) {
				ev.preventDefault();
				var devId = ev.dataTransfer.getData("text");
				if(grId>0) {
					updateDatabase("updateDevices","spot",grId,devId,"");
					setTimeout(function() {
						window.location.reload();
					},200);
				}
				else if(grId==-1) {
					AddGroup('prepare');
					updateDatabase("updateDevices","spot",grId,devId,"");
					i=1;
					setTimeout(function() {
						$("tr[name=trNewGroup]").css("border","#7FFF00 4px solid");
						setTimeout(function() {
							$("tr[name=trNewGroup]").css("border","");
						},300);
					},300);
				}
			}
			//Dodawanie zaznaczonych skanerów do zaznaczonej grupy
			function ToGroup() {
				if($("input[name=rbGroup]:checked").attr("Id")) {
					grId=$("input[name=rbGroup]:checked").attr("Id");
					grId=grId.substring(4);
					$("input[name='devices[]']:checked").each(function() {
						
						var element=$(this);
						DevId=element.attr("id").substring(2);
						updateDatabase("updateDevices","spot",grId,DevId,"");
					});
					setTimeout(function() {
						window.location.reload();
					},200);
				}
				else {
					alert("Narzędzie zmiany grupy.\n\nNie można wykonać!\n\nMusisz wybrać skanery, oraz ich grupę docelową.")
				}
			}
			//Usunięcie grupy
			function DeleteGroup(ident) {
				if(confirm("Czy na pewno chcesz usunąć grupę?\n\n Jeśli do grupy należą jakieś skanery, trafią do grupy niezdefiniowanej")) {
					$.ajax({
						type: 'POST',
						dataType: 'text',
						headers: {'X-Requested-With': 'XMLHttpRequest'},
						data: {
							action:"DeleteGroup",
							id:ident
						},
						url: "updater.php",
						success: function(response){
							switch(response) {
								case 'OK-GroupRemoved':
									window.location.reload();
									break;
								default:
									console.log("Group Removed - bad response");
							}
							console.log(response);
						},
						error: function() {
							console.log('request failed');
						}
					});
				}
			}
			//jasność dla grupy
			function updateBrightnessGr() {
				//$("input[name='devices[]']").prop("checked")
				$("input[name='devices[]']:checked").each(function() {
					var element=$(this);
					id=element.attr("id").substring(2);
					newValue=$("#rangeGroup").val();
					updateRangeValue(newValue, id, 'range');
				});
			}
			//tryb skanowania dla grupy
			function updateScanModeGr(mode) {
				$("input[name='devices[]']:checked").each(function() {
					var element=$(this);
					id=element.attr("id").substring(2);
					updateDatabase('updateDevices','scanMode',mode,id);
				});
			}
			//obsługa warstwy wizualnej procesu dodawania grupy
			function AddGroup(level) {
				if(level=='prepare') {
					$("#trNewGroup").css("display","");
					$("#btAddGroup").val("Anuluj dodawanie");
					$("#btAddGroup").attr("onclick","AddGroup('cancel')");
					$("#tbNewGroup").focus();
				}
				else if(level=='cancel') {
					$("#tbNewGroup").val("");
					$("#btAddGroup").val("Dodaj grupę");
					$("#btAddGroup").attr("onclick","AddGroup('prepare')");
					$("#trNewGroup").css("display","none");
				}
				else if(level=='finish') {
					if($("#tbNewGroup").val()!="") {
						$.ajax({
							type: 'POST',
							dataType: 'text',
							headers: {'X-Requested-With': 'XMLHttpRequest'},
							data: {
								action:"AddGroup",
								color:$("#ddlNewGroupColor").val(),
								name:$("#tbNewGroup").val()
							},
							url: "updater.php",
							success: function(response){
								switch(response) {
									case 'OK-addGroup':
										window.location.reload();
										break;
									default:
										console.log("Adding Group - bad response");
								}
								console.log(response);
							},
							error: function() {
								console.log('request failed');
							}
						});
					}
					else {
						alert("Musisz podać nazwę grupy");
					}
				}
			}
			//zmiana nazwy grupy
			function NameChange(level,ident,target) { //target=Gr/ConfCodeName
				if(level=='prepare') {
					$("#bt"+target+ident).css('display','none');
					$("#tb"+target+ident).css('display','block');
					$("#tb"+target+ident).focus(function() { $(this).select(); } );
					$("#tb"+target+ident).focus();
				}
				else if(level=='finish') {
					if($("#tb"+target+ident).val()!="") {
						if(target=="Gr")
							updateDatabase('updateGroups','Name',$("#tb"+target+ident).val(),ident,"");
						else if(target=="ConfCodeName")
							updateDatabase('updateConfCode','name',$("#tb"+target+ident).val(),ident,"");
						$("#bt"+target+ident).val($("#tb"+target+ident).val());
						NameChange('cancel',ident,target);
						$("#tb"+target+ident).val($("#bt"+target+ident).val());
						
					}
					else
						alert("wpisz nazwę lub naciśnij 'Esc' aby anulować");
				}
				else if(level=='cancel') {
					$("#bt"+target+ident).css('display','block');
					$("#tb"+target+ident).css('display','none');
				}
			}
			
			
			//
			//		FUNKCJE KODÓW KRESKOWYCH
			//
			function AddActivateCode(level) {
				if(level=='prepare') {
					$("#trNewActivateCode").css("display","");
					$("#btAddActivateCode").val("Anuluj dodawanie");
					$("#btAddActivateCode").attr("onclick","AddActivateCode('cancel')");
					$("#tbNewActivateCode").focus();
				}
				else if(level=='cancel') {
					$("#tbNewActivateCode").val("");
					$("#btAddActivateCode").val("Dodaj kod");
					$("#btAddActivateCode").attr("onclick","AddActivateCode('prepare')");
					$("#trNewActivateCode").css("display","none");
				}
				else if(level=='finish') {
					if($("#tbNewActivateCode").val()!="") {
						$.ajax({
							type: 'POST',
							dataType: 'text',
							headers: {'X-Requested-With': 'XMLHttpRequest'},
							data: {
								action:"AddActivateCode",
								name:$("#tbNewActivateCode").val()
							},
							url: "updater.php",
							success: function(response){
								switch(response) {
									case 'OK-addActivateCode':
										window.location.reload();
										break;
									default:
										console.log("Adding Activation Code - bad response");
								}
								console.log(response);
							},
							error: function() {
								console.log('request failed');
							}
						});
					}
					else {
						alert("Musisz podać nazwę profilu który ma być aktywowany");
					}
				}
			}
			//dodawanie kodu konfiguracyjnego
			function AddConfCode(level) {
				if(level=='prepare') {
					$("#trNewConfCode").css("display","");
					$("#btAddConfCode").val("Anuluj dodawanie");
					$("#btAddConfCode").attr("onclick","AddConfCode('cancel')");
					$("#tbNewConfCode").focus();
				}
				else if(level=='cancel') {
					$("#tbNewConfCode").val("");
					$("#btAddConfCode").val("Dodaj kod");
					$("#btAddConfCode").attr("onclick","AddConfCode('prepare')");
					$("#trNewConfCode").css("display","none");
				}
				else if(level=='finish') {
					if($("#tbNewConfCode").val()!="" && ($("#tbNewConfCodeGk").val() !="" || !$("#cbTbNewConfCodeGk").is(":checked"))) {
						rng=$("#rngNewConfCode").val();
						gk=$("#tbNewConfCodeGk").val();
						$.ajax({
							type: 'POST',
							dataType: 'text',
							headers: {'X-Requested-With': 'XMLHttpRequest'},
							data: {
								action:"AddConfCode",
								bright:rng,
								gkAdr:gk,
								name:$("#tbNewConfCode").val()
							},
							url: "updater.php",
							success: function(response){
								switch(response) {
									case 'OK-addActivateCode':
										window.location.reload();
										break;
									default:
										console.log("Adding Configuration Code - bad response");
								}
								console.log(response);
							},
							error: function() {
								console.log('request failed');
							}
						});
					}
					else {
						alert("Musisz podać nazwę konfiguracji");
					}
				}
			}
			//usuwanie kodu
			function confirmDeleteCode(code) {
				if(confirm("Czy na pewno usunąć ten kod?")) {
					window.open("updater.php?deletecode="+code);
					window.location.reload();
				}
			}
			function goToEditProfile(code) {
				window.open('profileEditor.php?action=editProfile&code='+code);
			}
			//
			//		FUNKCJE URZĄDZEŃ
			//
			
			//update jasności ekranu wywołany przez zmianę wartości suwaka
			function updateRangeValue(newValue, id, target) { //target=range/confCodeRange
				var idd = target + id;
				document.getElementById(idd).innerHTML=newValue;
				if(target=='range')
					updateDatabase("updateDevices","brightness",newValue,id,"#"+target);
				else if(target=='confCodeRange')
					updateDatabase("updateConfCode","brightness",newValue,id,"#"+target);
			}
			//zmiana adresu GK - warstwa wizualna
			function changeGkAdr(id,mode,target) { //target=GkAdr/ConfCodeGkAdr zależnie czy odwołanie dotyczy urządzenia czy kodu konfiguracyjnego
				idC="#tb"+target+id;
				idCbt="#bt"+target+id;
				idCbtA="#bt"+target+"A"+id;
				if(mode=='prepare') {
					$(idC).css("display","block");
					$(idCbt).attr("onclick","updateGkAdr('"+id+"','"+target+"')");
					$(idCbt).attr("value","Accept");
					$(idCbtA).css("display","inline");
					$(idC).focus(function() { $(this).select(); } );
					$(idC).focus();
				}
				else if(mode=='finish') {
					//$(idC).val(document.getElementById("sp"+target+id).innerHTML);
					$(idC).css("display","none");
					$(idCbt).attr("onclick","changeGkAdr('"+id+"','prepare','"+target+"')");
					$(idCbt).attr("value","Change");
					$(idCbtA).css("display","none");
				}
			}
			//zmiana adresu Gk - moduł główny 
			function updateGkAdr(id,target) { //target=GkAdr/ConfCodeGkAdr zależnie czy odwołanie dotyczy urządzenia czy kodu konfiguracyjnego
				idC="#tb"+target+id;
				idCbt="#bt"+target+id;
				idCsp="#sp"+target+id;
				newVal=$(idC).val();
				validatorResult=false;
				if(target=='GkAdr') {
					if(Validator(newVal,'gk')) {
						updateDatabase("updateDevices","gkServerAddress",newVal,id,"#spGkAdr");
						changeGkAdr(id,'finish','GkAdr');
						validatorResult=true;
					}
				}
				else if(target=='ConfCodeGkAdr') {
					if(Validator(newVal,'gk-cc')) {
						updateDatabase("updateConfCode","gkServerAddress",newVal,id,"#spConfCodeGkAdr");
						changeGkAdr(id,'finish','ConfCodeGkAdr');
						validatorResult=true;
					}
				}
				if(validatorResult==false) {
					$(idC).val("");
					$(idCsp).text("must be IP addres!");
					$(idCsp).css("color", "red");
				}
			}
			//zmiana czasu odpowiedzi skanera - warstwa wizualna
			function ChangeInterval(id,mode) {
				idC="#tbInter"+id;
				idCbt="#btInter"+id;
				idCbtA="#btInterA"+id;
				if(mode=='prepare') {
					$(idC).css("display","block");
					$(idCbt).attr("onclick","updateInterval("+id+")");
					$(idCbt).attr("value","Accept");
					$(idCbtA).css("display","inline");
				}
				else if(mode=='finish') {
					$(idC).val("");
					$(idC).css("display","none");
					$(idCbt).attr("onclick","ChangeInterval("+id+",'prepare')");
					$(idCbt).attr("value","Change");
					$(idCbtA).css("display","none");
				}
			}
			//zmiana czasu odpowiedzi skanera - moduł główny
			function updateInterval(id) {
				//document.write("in");
				idC="#tbInter"+id;
				idCbt="#btInter"+id;
				idCsp="#spInter"+id;
				newVal=$(idC).val();
				if(newVal!="" && newVal>=1) {
					updateDatabase("updateDevices","timer",newVal,id,"#spInter");
					ChangeInterval(id,'finish');
				}
				else {
					$(idC).val("");
					$(idCsp).text("must be unasigned integer!");
					$(idCsp).css("color", "red");
				}
			}
			//update bazy barcodesconf
			function updateDatabase(act,param,val,id,name) { 
				$.ajax({
					type: 'POST',
					dataType: 'text',
					headers: {'X-Requested-With': 'XMLHttpRequest'},
					data: {
						action:act,
						parameter:param,
						value:val,
						identifier:id
					},
					url: "updater.php",
					success: function(response){
						Fid=name+id;
						switch(response) {
							case 'OK-ConfCodeBrightness':
								$(Fid).text(val+" - Succesfully updated!")
								$(Fid).css("color", "green");
								break;
							case 'OK-ConfCodeGkAdr':
								$(Fid).text(val+" - Succesfully updated!")
								$(Fid).css("color", "green");
								break;
							case 'OK-cnf':
								$(Fid).text("updated!")
								break;
							case 'OK':
								//$(Fid).text(val+" - Succesfully updated!")
								//$(Fid).css("color", "green");
								window.location.reload();
								break;
							case 'OK-grp':
								window.location.reload();
								break;
							default:
								console.log("def");
						}
						console.log(response);
					},
					error: function() {
						console.log('request failed');
					}
				});
			}
			
			
			//
			//		FUNKCJE WIADOMOŚCI
			//
			
			//usuwanie z bazy wiadomości oczekującej na odbiór 
			function CancelMessage(id) {
				$.ajax({
					type: 'POST',
					dataType: 'text',
					headers: {'X-Requested-With': 'XMLHttpRequest'},
					data: {
						message:'cancel',
						device:id
					},
					url: "updater.php",
					success: function(response){
						switch(response) {
							case 'OK-cancelMsg':
								window.location.replace("index.php");
								break;
							default:
								console.log("def");
						}
					},
					error: function() {
						console.log('request failed');
					}
				});
			}
			//wysyłania formularza - w praktyce używane tylko przy rozsyłaniu wiadomości (27.07.2015)
			function formToUpdate(mode) {
				if(mode=="sendMessage") {
					if(Validator("",'sendMessage')) {
						$("#mainForm").submit();
					}
					else {
						$("#spTaMsg").text("Pole nie może byc puste");
						$("#spTaMsg").css("color","red");
					}
				}
			}
			function infoBox(code) {
				msg="";
				switch(code) {
					case 1:
						msg="Podświetlenie.\n\nUstawienie wartości '-1' oznacza, że wczytanie kodu nie wpłynie na jasność ekranu.";
						break;
					case 2:
						msg="Adres Gatekeepera\n\nWprowadzona wartość musi być adresem IP.\nPozostawienie pola pustego oznacza, że wczytanie kodu nie wpłynie na adres serwera ustawiony w GK";
				}
				alert(msg);
			}
			//komunikacja z edytorem sesji
			function evEdit(mode,id) {
				if(mode=='edit') {
					window.open('eventEditor.php?action=editEvent&id='+id);
				}
				else if (mode=='new') {
					window.open('eventEditor.php?action=newEvent');
				}
			}
			//komunikacja z listami biletów
			function ticList(id) {
				window.open('ticList.php?id='+id);
			}
		</script>
		<div id="tabs">
			<ul>
				<li><a href="#tabs-1">Strona główna</a></li>
				<li><a href="#tabs-2">Kody konfiguracyjne</a></li>
				<li><a href="#tabs-3">Profile sieciowe</a></li>
				<li><a href="#tabs-4">Sesje i bilety</a></li>
				<li><a href="#tabs-5">Administracja</a></li>
			</ul>
		
<?php
	echo"<p>Zalogowany jako <b>".$_SESSION['login']."</b><br/>
		<input type='button' value='Wyloguj' onclick='Logout()'/>
		<input type='button' value='Zmień Hasło' onclick=\"ChangeMasterAdminPassword('".$_SESSION['login']."')\"/></p>";
	$query="SELECT DISTINCT Id,name,brightness,gkServerAddress,timer,message,spot,scanMode FROM devices ORDER BY spot, name";
	if($db_found) {
		echo"<form name='mainForm' id='mainForm' action='updater.php' method='POST'><div id='tabs-1'><div class='partContent'><table>
				<tr>
					<th>
						<input type='button' value='Wszystkie' onclick=\"cbToggle('select')\"/><br/>
						<input type='button' value='Żodyn' onclick=\"cbToggle('deselect')\"/>
					</th>
					<th>URZĄDZENIE</th>
					<th>Poziom baterii</th>
					<th>AC<br/>status</th>
					<th>Ostatnia<br/>odpowiedź</th>
					<th>Podświetlenie</th>
					<th>Adres GK</th>
					<th>Interwał<br/>odpowiedzi (s)</th>
					<th>Scan Mode</th>
					<th>MSG</th>
				</tr>";
					
		$counter=0;
		$result=mysql_query($query);
		$lastGrId=-1;
		while($row=mysql_fetch_assoc($result)) {
			$mq=mysql_query("SELECT * FROM battstatus where Id='{$row["Id"]}' and ts=(SELECT MAX(ts) from battstatus where Id='{$row["Id"]}')"); // pobranie aktualnego stanu baterii
			
			if(mysql_num_rows($mq)==0) {
				$row2=array(
					"batt"=>"-1",
					"ts"=>"0",
					"acStatus"=>"0",
					"Id"=>$row["Id"],
				);
			}
			else {
				$row2 = mysql_fetch_assoc($mq);
			}
			//echo("<script type='text/javascript'>alert('$bt');</script>");
			
				//echo $row['Id'];
				$counter++;
				//dostosowanie wyświetlania jednostek czasu
				if($row2["ts"]!='0') {
					$interval=abs(strtotime(date("Y-m-d H:i:s")) - strtotime($row2["ts"]));
					$unit=" sekund temu";
					if($interval>60) {
						$interval/=60;
						$unit=" minut temu";
						if($interval>60) {
							$interval/=60;
							$unit=" godzin temu";
							if($interval>24) {
								$interval/=24;
								$unit=" dni temu";
							}
						}
					}
					$interval=floor($interval);
				}
				else {
					$interval="";
					$unit="Brak zgłoszenia";
				}
				//wykrywanie niskiego stanu baterii
				$bc1="";
				if($row2["batt"]<30 && $row2["batt"]>-1 && !$row2["acStatus"])
					$bc1="background-color:#FF9C9C";
				
				//nagłówek grupy
				if($row["spot"]!=$lastGrId) {
					$qGrT=null;
					if($row["spot"]==0) {
						$GrName="Niezdefiniowane";
						$GrColor="lightgray";
					}
					else {
						$qGrT=mysql_fetch_assoc(mysql_query("SELECT name,color,Id FROM groups WHERE id='{$row["spot"]}'"));
						$GrName=$qGrT["name"];
						$GrColor=$qGrT["color"];
					}
					$lastGrId=$row["spot"];
					echo"<tr>
							<th colspan='10' style='background-color: $GrColor; color:black;' ondrop=\"dropDev(event,'{$qGrT["Id"]}')\" ondragover='allowDrop(event)'>$GrName</th>
						</tr>";
				}
				echo"<tr>
						<td style='background-color: $GrColor'><input type='checkbox' name='devices[]' id='cb{$row["Id"]}' value='{$row["Id"]}'/>
						<td name='tdDevName' style='cursor:pointer' draggable='true' ondragstart=\"dragDev(event,'{$row["Id"]}')\">".$row["name"]."</td>
						<td style=\"".$bc1."\">";
						if($row2["batt"]>-1)
							echo"
							<a class=\"various\" data-fancybox-type=\"iframe\" href='chart.php?Id={$row["Id"]}'>{$row2["batt"]}%<br>
								<progress value='{$row2["batt"]}' max='100'>{$row2["batt"]}%</progress>
							</a>";
						else 
							echo"Brak danych";
						echo"
						</td><td>";
							//wyświetlanie ikonki AC gdy skaner podłączony
							if($row2["acStatus"])
								echo"<img src='charging.png' width='40px'/>"; 
							echo"</td>
						<td>$interval$unit</td>
						<td>
							<span style='text-align:center;' id='range{$row["Id"]}'>{$row["brightness"]}</span><br/>
							<input type='range' min='0' max='63' value='{$row["brightness"]}' onchange=\"updateRangeValue(this.value, '{$row["Id"]}', 'range')\" />
						</td>
						<td>
							<span id='spGkAdr{$row["Id"]}'>{$row["gkServerAddress"]}
							</span><br />
							<input type='text' style='display:none;' id='tbGkAdr{$row["Id"]}' size='15' value='{$row["gkServerAddress"]}'/>
								<script type='text/javascript' language='javascript'>
									
									$('#tbGkAdr{$row["Id"]}').keyup(function(event){
										if(event.keyCode == 13){
											$('#btGkAdr{$row["Id"]}').click();
										}
									});
								</script>
							<input type='button' id='btGkAdrA{$row["Id"]}' value='Anuluj' style='display:none;' onClick=\"changeGkAdr({$row["Id"]},'finish','GkAdr')\"/> 
							<input type='button' value='Zmień' id='btGkAdr{$row["Id"]}' onclick=\"changeGkAdr({$row["Id"]},'prepare','GkAdr')\">
						</td>
						<td>
							<span id='spInter{$row["Id"]}'>{$row["timer"]}
							</span><br />
							<input type='number' step='1' style='display:none;' id='tbInter{$row["Id"]}' size='3'/>
								<script type='text/javascript' language='javascript'>
									$('#tbInter{$row["Id"]}').keyup(function(event){
										if(event.keyCode == 13){
											$('#btInter{$row["Id"]}').click();
										}
									});
								</script>
							<input type='button' id='btInterA{$row["Id"]}' value='Anuluj' style='display:none;' onClick=\"ChangeInterval({$row["Id"]},'finish')\"/> 
							<input type='button' id='btInter{$row["Id"]}' value='Change' onClick=\"ChangeInterval({$row["Id"]},'prepare')\"/>
						</td>
						<td>";
						echo scanModeDecoder($row["scanMode"]);
					echo"</td>
						<td>
							<span id='spMsg' style='color:orange;'>"; 
							//wykrywanie stanu wysyłania wiadomości do skanera
							if($row["message"]!="") {
								echo"Waiting for deliver<br/>
								<input type='button' value='Cancel' onclick='CancelMessage({$row["Id"]})'";
							}
							echo"</span>
						</td>
					</tr>";
			
		}
		echo"
			<tr>
				<td colspan='10' style='background-color: lightgray; color:black;' ondrop=\"dropDev(event,'-1')\" ondragover='allowDrop(event)'>Nowa Grupa</th>
			</tr>
		</table></div>";
		//tabela ustawień grupowych 
		echo"
		<div class='partContent'><div class='insideContent'>
		<table style='display:inline-block; vertical-align:top;'>
			<tr>
				<th colspan='3'>Ustawienia grupowe</th>
			</tr>
			<tr>
				<td>Wiadomość</td>
				<td>Treść:<br/><textarea id='taMsg' name='taMsg' rows='3' cols='20' ></textarea></td>
				<td>
					<input type='button' value='Wyślij' onclick=\"formToUpdate('sendMessage')\" />
					<input type='hidden' name='message' value='toSend'/>
					<span id='spTaMsg'></span>
				</td>
			</tr>
			<tr>
				<td>Jasność</td>
				<td>
					<span style='text-align:center;' id='spRangeGroup'></span><br/>
					<input id='rangeGroup' type='range' min='0' max='63' value='0' />
				</td>
				<td><input type='button' value='Zatwierdź' onclick='updateBrightnessGr()'/></td>
			</tr>
			<tr>
				<th>Tryb<br/>skanowania</th>
				<td colspan='2'>
					<input type='button' value='! STOP !' onclick='updateScanModeGr(\"0\")' style='background-color:lightcoral; color:white;'/><br/>
					<input type='button' value='->| Wejście' onclick='updateScanModeGr(\"1\")' style='background-color:lightgreen;'/> 
					<input type='button' value='|<- Wyjście' onclick='updateScanModeGr(\"2\")' style='background-color:lightblue;'/><br/>
					<input type='button' value='<-> Dwukierunkowo' onclick='updateScanModeGr(\"3\")' style='background-color:cyan;'/><br/>
				</td>
			</tr>
		</table>";
		//Tabelka grup
		echo"<table style='display:inline-block; vertical-align:top;'>
				<tr>
					<th colspan='4'>Grupy</th>
				</tr>
				<tr>
					<td colspan='4'>
						<input type='button' value='Dodaj grupę' id='btAddGroup' onclick=\"AddGroup('prepare')\"/>
						<input type='button' value='->Do grupy' id='btToGroup' onclick=\"ToGroup();\"/>
					</td>
				</tr>
				<tr name='trNewGroup' id='trNewGroup' style='display:none;'>
					<td></td>
					<td><input type='text' size='10' id='tbNewGroup'/></td>
					<td>";ColorSet("ddlNewGroupColor","");echo"</td>
					<td><input type='button' value='Wprowadź' onClick=\"AddGroup('finish')\"/></td>
				</tr>
				<tr>
					<th></th>
					<th>Nazwa</th>
					<th>Kolor</th>
					<th>Usuń</th>
				</tr>
				";
		$qGr=mysql_query("SELECT * FROM groups ORDER BY Id");
		while($grRow=mysql_fetch_assoc($qGr)) {
			echo"<tr>
					<td><input type='radio' id='rbGr{$grRow["Id"]}' name='rbGroup'/>
					<td style='background-color:{$grRow["Color"]};' ondrop=\"dropDev(event,'{$grRow["Id"]}')\" ondragover='allowDrop(event)'>
						<input type='button' id='btGr{$grRow["Id"]}' value='{$grRow["Name"]}' onclick=\"NameChange('prepare', '{$grRow["Id"]}','Gr')\"/>
						<input type='text' id='tbGr{$grRow["Id"]}' size='8' style='display:none;' value='{$grRow["Name"]}'/>
						<script type='text/javascript' language='javascript'>
							$('#tbGr{$grRow["Id"]}').keyup(function(event){
								if(event.keyCode == 13){
									NameChange('finish','{$grRow["Id"]}','Gr');
								}
								if(event.keyCode == 27) {
									NameChange('cancel','{$grRow["Id"]}','Gr');
								}
							});
							$('#tbGr{$grRow["Id"]}').blur(function(e) {
								NameChange('cancel','{$grRow["Id"]}','Gr');
							});
						</script>
					</td>
					<td>";
						ColorSet("ddlGroupColor{$grRow["Id"]}","updateDatabase('updateGroups','Color',this.value,{$grRow["Id"]},'')");
					echo"</td>
					<td><input type='button' value='Usuń' onclick=\"DeleteGroup('{$grRow["Id"]}')\"/></td>
				</tr>";
		}
		echo"</table></div></div></div>";
		//Tabelka ustawień globalnych
		echo"
		<div id='tabs-5'><div class='partContent'>
		<table style='vertical-align:top;'>
			<tr><th colspan='3'>Ustawienia globalne</th></tr>
			<tr>
				<th>Parametr</th>
				<th>Wartość</th>
			</tr>";
			$q=mysql_fetch_assoc(mysql_query("SELECT val from config WHERE param='localPin'"));
			$localPIN=$q["val"];
			echo"
			<tr>
				<td>PIN admina<br/>na skanerze</td>
				<td>
					<span id='spPin'>****</span>	
					<br/>
					<span id='spPinTb' style='display:none;'>
						Nowy PIN: <input type='password' id='tbPin' size='8'/><br/>
						Twoje hasło: <input type='password' id='tbPinPass' size='8'/><br/>
					</span>
					
					<input type='button' id='pinbutton' value='Pokaż' onClick=\"showPinToggle('$localPIN');\"/>
					<input type='button' id='changePinA' value='Anuluj' style='display:none;' onClick=\"ChangePin('cancel')\"/>
					<input type='button' id='changePin' value='Zmień' onClick=\"ChangePin('prepare')\"/>
					<script type='text/javascript' language='javascript'>
						$('#tbPin').keyup(function(event){
							if(event.keyCode == 13){
								$('#changePin').click();
							}
						});
						$('#tbPinPass').keyup(function(event){
							if(event.keyCode == 13){
								$('#changePin').click();
							}
						});
					</script>
				</td>
			</tr>
			<tr>
				<td style='color: red;'>Zresetuj bazę</td>
				<td>
					<span id='spResetDB'></span>
					<span id='spResetDBTb' style='display:none;'>
						<input type='radio' name='rbResetDbMode' value='all' checked='checked'/>Cała baza<br/>
						<input type='radio' name='rbResetDbMode' value='exceptGroups'/>Pozostaw grupy<br/>
						<input type='radio' name='rbResetDbMode' value='onlyHistory'/>Skasuj tylko historię odwołań<br/>
						Twoje hasło: <input type='password' id='tbResetDB'/>
					</span>
					<input type='button' id='btResetDB' value='RESET' onClick=\"resetDataBase('prepare')\"/>
					<script type='text/javascript' language='javascript'>
						$('#tbResetDB').keyup(function(event){
							if(event.keyCode == 13){
								$('#btResetDB').click();
							}
						});
					</script>
				</td>
			</tr>
		</table></div><div class='clear'></div></div><br/>";
		//tabela barcode'ów konfiguracyjnych
		echo"<div id='tabs-2'><div class='partContent'>
		<table style='vertical-align:top;'>
			<tr>
				<th colspan='5'>Kody konfiguracyjne</th>
			</tr>
			<tr>
				<th>Nazwa</th>
				<th>Kod</th>
				<th>Podświetlenie <img src='info.png' width='10px' onclick=\"infoBox(1)\"></th>
				<th>GK <img src='info.png' width='10px' onclick=\"infoBox(2)\"></th>
				<th></th>
			</tr>
			<tr>
				<td colspan='5'><input type='button' id='btAddConfCode' value='Dodaj kod' onclick=\"AddConfCode('prepare')\"></td>
			</tr>
			<tr name='trNewConfCode' id='trNewConfCode' style='display:none;'>
				<td><input type='text' size='10' id='tbNewConfCode'/></td>
				<td></td>
				<td>
					<label id='lblNewConfCode'>-1</label><br/>
					<input type='range' id='rngNewConfCode' min='-1' max='63' value='-1' onchange=\"updateRangeValue(this.value,'','lblNewConfCode')\"><br/>
				</td>
				<td>
					<input type='text' id='tbNewConfCodeGk'></br>
				</td>
				<td><input type='button' value='Wprowadź' onClick=\"AddConfCode('finish')\"/></td>
			</tr>
			";
			$bccQ=mysql_query("SELECT * FROM barcodesconfig");
			while($row=mysql_fetch_assoc($bccQ)) {
				$name=$row["name"];
				$code=$row["code"];
				$codeSize=strlen($code);
				$brightness=$row["brightness"];
				$gkAdr=$row["gkServerAddress"];
				$IP=$row["IP"];
				echo"<tr>
						<td>
							<input type='button' id='btConfCodeName$code' value='$name' onclick=\"NameChange('prepare', '$code', 'ConfCodeName')\"/>
							<input type='text' id='tbConfCodeName$code' size='8' style='display:none;' value='$name'/>
							<script type='text/javascript' language='javascript'>
								$('#tbConfCodeName$code').keyup(function(event){
									if(event.keyCode == 13){
										NameChange('finish','$code','ConfCodeName');
									}
									if(event.keyCode == 27) {
										NameChange('cancel','$code','ConfCodeName');
									}
								});
								$('#tbConfCodeName$code').blur(function(e) {
									NameChange('cancel','$code','ConfCodeName');
								});
							</script>
						</td>
						<td><a class=\"various\" data-fancybox-type=\"iframe\" href='barcodeView.php?text=$code&size=$codeSize&codeName=$name'>";
						echo stringCutter($code,5);
						echo"</a></td>
						<td>
							<span style='text-align:center;' id='confCodeRange$code'>$brightness</span><br/>
							<input type='range' min='-1' max='63' value='$brightness' onchange=\"updateRangeValue(this.value,'$code','confCodeRange')\" />
						</td>
						<td>
							<span id='spConfCodeGkAdr$code'>$gkAdr</span><br />
							<input type='text' style='display:none;' id='tbConfCodeGkAdr$code' size='15'/>
								<script type='text/javascript' language='javascript'>
									$('#tbConfCodeGkAdr$code').keyup(function(event){
										if(event.keyCode == 13){
											$('#btConfCodeGkAdr$code').click();
										}
									});
								</script>
							<input type='button' id='btConfCodeGkAdrA$code' value='Anuluj' style='display:none;' onClick=\"changeGkAdr($code,'finish','ConfCodeGkAdr')\"/> 
							<input type='button' value='Zmień' id='btConfCodeGkAdr$code' onclick=\"changeGkAdr('$code','prepare','ConfCodeGkAdr')\">
						</td>
						<td><input type='button' value='usuń' onclick=\"confirmDeleteCode('$code')\"></td>
					</tr>";
						
			}
			echo"
		</table></div></div>";
		//tabela kodów dodających profile
		echo"<div id='tabs-3'><div class=partContent><table style='vertical-align:top;'>
			<tr>
				<th colspan='4'>Kody dodające profile sieciowe</th>
			</tr>
			<tr>
				<th>Nazwa</th>
				<th>Kod</th>
				<th>Opis</th>
				<th></th>
			</tr>
			<tr>
				<td colspan='4'><input type='button' value='Dodaj profil' onclick=\"window.open('profileEditor.php')\"></td>
			</tr>";
			$q=mysql_query("SELECT * FROM barcodesaddprofile");
			while($row=mysql_fetch_assoc($q)) {
				$name=$row["Name"];
				$code=$row["code"];
				echo"<tr>
						<td>{$row["Name"]}</td>
						<td><a class=\"various\" data-fancybox-type=\"iframe\" href='barcodeView.php?text=$code&size=16&codeName=$name'>";
						echo stringCutter($code,5);
						echo"</a></td>
						<td style='word-wrap:break-word;'>{$row["description"]}</td>
						<td><input type='button' value='Edytuj' onclick=\"goToEditProfile('$code')\"></td>
					</tr>";
			}
			echo"</table>";
		//tabela kodów AKTYWUJĄCYCH profile
		echo"<br/><br/>
		<table style='vertical-align:top;'>
			<tr>
				<th colspan='4'>Kody aktywujące profile sieciowe</th>
			</tr>
			<tr>
				<th>Nazwa</th>
				<th>Kod</th>
				<th>Opis</th>
				<th></th>
			</tr>
			<tr>
				<td colspan='4'><input type='button' value='Dodaj kod' onclick=\"AddActivateCode('prepare')\"></td>
			</tr>
			<tr name='trNewActivateCode' id='trNewActivateCode' style='display:none;'>
				<td colspan='2'><input type='text' size='10' id='tbNewActivateCode'/></td>
				<td><input type='button' value='Wprowadź' onClick=\"AddActivateCode('finish')\"/></td>
			</tr>";
			$q=mysql_query("SELECT * FROM barcodesactiveprofile");
			while($row=mysql_fetch_assoc($q)) {
				$name=$row["Name"];
				$code=$row["Code"];
				$description=$row["description"];
				echo"<tr>
						<td>{$row["Name"]}</td>
						<td><a class=\"various\" data-fancybox-type=\"iframe\" href='barcodeView.php?text=$code&size=16&codeName=$name'>";
						echo stringCutter($code,5);
						echo"</a></td>
						<td style='word-wrap:break-word;'>$description</td>
						<td><input type='button' value='Usuń' onclick=\"window.open('updater.php?deletecode=$code'); window.location.reload();\"></td>
					</tr>";
			}
		echo"
		</table>
		</div></div>
		<div id='dMsg'>";
		//tabela sesji
			echo"</div>
		<div id='tabs-4'>
		<div class='partContent'>
			<table style='vertical-align:top;'>
				<tr>
					<th colspan='6'>Zarządzanie sesjami</th>
				</tr>
				<tr>
					<th colspan='6'><input type='button' value='Dodaj nową sesję' onclick=\"evEdit('new')\"></th>
				</tr>
				<tr>
					<th>Trwa</th>
					<th>Nazwa</th>
					<th>Rozpoczęcie</th>
					<th>Zakończenie</th>
					<th></th>
					<th></th>
				</tr>";
			$q=mysql_query("SELECT * FROM events");
			while($row=mysql_fetch_assoc($q)) {
				$name=$row["name"];
				$start=$row["start"];
				$end=$row["end"];
				$id=$row["Id"];
				echo"
				<tr>
					<td>";
					$dateNow=date("Y-m-d H:i:s");
					$dateStart=date("Y-m-d H:i:s",strtotime($start));
					$dateEnd=date("Y-m-d H:i:s",strtotime($end));
					if($dateNow>$dateStart && $dateNow<$dateEnd)
						echo"<img src='green_arrow.png' width='30px' height='30px' />";
					echo"</td>
					<td>$name</td>
					<td>$start</td>
					<td>$end</td>
					<td><input type='button' value='edytuj' onclick=\"evEdit('edit','$id')\"></td>
					<td><input type='button' value='bilety' onclick=\"ticList('$id')\">";
					$count=mysql_fetch_assoc(mysql_query("SELECT count(*) as rows FROM tickets WHERE event='$id'"));
					echo"(".$count["rows"].")";
					echo"</td>
				</tr>";
			}
		echo"</table>
		</div></div>
		</form></div>";
	}
	mysql_close($conn);
	include_once("footer.html");
?>
	</body>
</html>