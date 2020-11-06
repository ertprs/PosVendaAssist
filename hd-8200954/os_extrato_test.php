<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica<>1 and $login_fabrica<>20) include "autentica_usuario_financeiro.php";

//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
//			 não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
//			 de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
//			 SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
//			 O array abaixo define quais fábricas estão enquadradas no processo novo
$fabricas_acerto_extrato = array(42,43, 45);

if (in_array($login_fabrica,array(81,51,90,91))) {
	header ("Location: os_extrato_novo_lgr.php");
	exit;
}

if ($login_fabrica == 3) {
	if ($login_e_distribuidor == 't') {
		header ("Location: new_extrato_distribuidor.php");
		exit;
	} else {
		$sql = "SELECT codigo
				FROM tbl_extrato
				JOIN tb