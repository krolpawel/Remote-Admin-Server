<?php
	class Authentication{
		private $login;
		private $password;
		
		public function __construct($log,$pass) {
			$this->login=$log;
			$this->password=$pass;
		}
		public function TryLogin() {
			$passT=mysql_fetch_assoc(mysql_query("SELECT login,password FROM users WHERE login='{$this->login}'"));
			if(isset($passT["password"])) {
				$passDB=$passT["password"];
				if($passDB==PasswordEncryptor($_POST["pass"])) {
					$_SESSION["login"]=$passT["login"];
					mysql_query("UPDATE users SET session='".SessionEncryptor($_SESSION["sesId"])."'");
					echo("OK");
					return false;
				}
				else {
					return false;
				}
			}
		}
	}
?>