<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "funcoes.php";
	include "autentica_admin_cliente.php";

	$os = $_POST['os'];
	$opcao = $_POST['op'];

	if($opcao == "Aprovar"){
		$sql = "UPDATE tbl_orcamento_os_fabrica SET
					   data_aprovado = current_timestamp,
					   admin         = $login_admin
					WHERE tbl_orcamento_os_fabrica.os = ".$os;
	}else{
		$sql = "UPDATE tbl_orcamento_os_fabrica SET
					   data_reprovado = current_timestamp,
					   admin          = $login_admin
					WHERE os = $os";
	}
	

	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(strlen($msg_erro)== 0){
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)==0){
			if($login_fabrica == 96 AND $opcao == "Aprovar"){
				$sql = "SELECT email FROM tbl_admin WHERE admin IN (3374,3373)";
				$res = pg_query($con,$sql);
				
				if(pg_numrows($res) > 0){
					$destinatario = strtolower(pg_result($res,0,email).",".pg_result($res,1,email));
					
					$res = pg_query($con,"SELECT sua_os FROM tbl_os WHERE os = $os");
					$sua_os = pg_result($res,0,sua_os);

					$remetente    = "suporte@telecontrol.com.br";
					$assunto      = "Orçamento Aprovado - $sua_os";
					$mensagem     = "Prezado, <br> O orçamento de número $sua_os, foi aprovado. Favor verificar e dar continuidade caso necessário.<br><br>Att <br>Equipe Telecontrol";
					$headers="Return-Path: <$remetente>\nFrom:".$remetente."\nContent-type: text/html\n";

					mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
			echo "OK";
		}
		else{
			echo $msg_erro;
		}
	}
	else{
		echo $msg_erro;
	}

	exit;
?>