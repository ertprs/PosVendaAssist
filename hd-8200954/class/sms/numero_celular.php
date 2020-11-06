<?php
include_once "../../dbconfig.php";
include_once "../../includes/dbconnect-inc.php";
include_once "sms.class.php";

$sms = new SMS();

$destinatario 	= (isset($_GET['celular'])) ? $_GET['celular'] : "14996884210";

echo (!$sms->validaDestinatario($destinatario)) ? "numero invalido" : "numero valido";

