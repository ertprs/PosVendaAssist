<?php
session_start();	
//require('autentica_usuario.php');
include_once('banco.inc.php');
//require_once("chat.php");
//$submit = new chat();

$l_login= $_SESSION['sess_login'];
$l_msg= trim($_GET["chat"]);

$query="INSERT INTO chat (username,texto,data) VALUES ('$l_login','$l_msg',NOW())";
$res = pg_exec ($con,$query);



?>