<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_admin.php';

switch($_GET["tipo"]) {
	
	case "atualizar":
	$destinatario = intval($_GET["destinatario"]);
	$situacao = $_GET["situacao"];
	if(($situacao=="f")||($situacao=="t")){
	$sql="UPDATE tbl_destinatario SET ativo='{$situacao}' WHERE destinatario={$destinatario} RETURNING ativo";
	$res = pg_query($con, $sql);
	$situacao = pg_fetch_result($res, 0, 0);
	$situacao = strtoupper($situacao);
	if($situacao=="T"){
			$situacao="S";
			$label = "Desativar";
			}
		else{
			$situacao="N";
			$label = "Ativar";
			}
			
	echo "{$destinatario}|{$situacao}|{$label}";
	}
	else{
	$situacao=0;
	echo $situacao;
	}
			
	break;
}
?>