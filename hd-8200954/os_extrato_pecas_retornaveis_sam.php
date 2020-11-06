<?
//arquivo alterado por takashi em 17/08/2006. Aparecia com a somatoria errada. Qdo agrupava por peça o valor total ficava diferente e o valor das pecas tambem. Arquivo anterior renomeado para os_extrato_pecas_retornaveis_ant.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$extrato = trim($_GET['extrato']);
if (strlen($extrato) == 0) $extrato = trim($_POST['extrato']);

$servico_realizado = trim($_GET['servico_realizado']);
if (strlen($servico_realizado) == 0) $servico_realizado = trim($_POST['servico_realizado']);

if(strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}
if($login_fabrica==6){
	header("Location: os_extrato_pecas_retornaveis_tectoy.php?extrato=$extrato");
	exit;
}
if($login_fabrica==24){
	header("Location: os_extrato_pecas_retornaveis_suggar.php?extrato=$extrato");
	exit;
}
$msg_erro = "";

$layout_menu = "os";
$title = "Relação de Peças Retornáveis ";
	if ($login_fabrica == 3) { 
		$title .= 