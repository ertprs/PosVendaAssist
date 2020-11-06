<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="callcenter";
$layout_menu = 'callcenter';
include "funcoes.php";

require('../class/email/mailer/class.phpmailer.php');

$hd_chamado = intval($_GET["hd_chamado"]);
$status = utf8_decode($_GET["status"]);
$motivo = utf8_decode($_GET["motivo"]);
$obs = utf8_decode($_GET["obs"]);
$acao = utf8_decode($_GET["acao"]);

if (strlen($hd_chamado)) {
	$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado AND fabrica=$login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$sql = "
		SELECT
		hd_chamado
		
		FROM
		tbl_hd_chamado_postagem
		
		WHERE
		hd_chamado=$hd_chamado
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
		}
		else {
			$msg_erro = "Nгo existe solicitaзгo de postagem para este chamado";
		}
	}
	else {
		$msg_erro = "Chamado nгo encontrado";
	}
}
else {
	$msg_erro = "Nъmero do chamado nгo informado";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
	die;
}

$sql = "BEGIN TRANSACTION";
$res = pg_query($con, $sql);

switch($status) {
	case "Aprovar":
		$sql = "
		SELECT
		hd_chamado

		FROM
		tbl_hd_chamado_postagem

		WHERE
		hd_chamado=$hd_chamado
		AND aprovado IS NULL
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$sql = "
			UPDATE
			tbl_hd_chamado_postagem

			SET
			data_aprovacao=NOW(),
			aprovado=true,
			admin=$login_admin,
			motivo='$motivo',
			obs='$obs'

			WHERE
			hd_chamado=$hd_chamado
			";
			@$res = pg_query($con, $sql);

			if (pg_errormessage($con)) {
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
			}
			else {
				$msg = "Postagem do chamado $hd_chamado aprovada";

				$sql = "SELECT email FROM tbl_admin WHERE admin=$login_admin";
				$res = pg_query($con, $sql);
				$remetente = pg_result($res, 0, email);
				
				$sql = "SELECT email FROM tbl_admin WHERE admin=(SELECT atendente FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado)";
				$res = pg_query($con, $sql);
				$destinatario = pg_result($res, 0, email);
				
				$assunto = "Postagem Aprovada - Chamado $hd_chamado";
				$mensagem = "Prezado,
				
				Foi aprovada a sua solicitaзгo para postagem do chamado $hd_chamado. Verifique o chamado e dк continuidade.
				
				CallCenter - Telecontrol";
				$mensagem = nl2br($mensagem);
				$headers = "From: $remetente\n";
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1\n";



				if(in_array($login_fabrica,array(24,35,81,86))){
					switch ($login_fabrica) {
						case 24:
							$username = 'tc.sac.suggar@gmail.com';
							$senha = 'tcsuggar';
							break;
						case 35:
							$username = 'tc.sac.cadence@gmail.com';
							$senha = 'tccadence';
							break;
						case 81:
							$username = 'tc.sac.bestway@gmail.com';
							$senha = 'tcbestway';
							break;	
						case 86:
							$username = 'tc.sac.famastil@gmail.com';
							$senha = 'tcfamastil';
							break;										
					}

					$mailer = new PhpMailer(true);

				    $mailer->IsSMTP();
				    $mailer->Mailer = "smtp";
				    
				    $mailer->Host = 'ssl://smtp.gmail.com';
				    $mailer->Port = '465';
				    $mailer->SMTPAuth = true;
		                   
				    $mailer->Username = $username;
				    $mailer->Password = $senha;
				    $mailer->SetFrom($username, $username); 
				    $mailer->AddAddress($destinatario,$destinatario ); 
				    $mailer->Subject = utf8_encode($assunto);
				    $mailer->Body = utf8_encode($mensagem);

				    try{
						$mailer->Send();									
				    }catch(Exception $e){
						$msg_erro = "Mensagem nгo enviada";
				    }
					

				}else{
					mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
				
			}
		}
		else {
			$msg_erro = "O status da postagem do chamado $hd_chamado nгo permite Aprovar";
		}
	break;

	case "Reprovar":
		$sql = "
		SELECT
		hd_chamado

		FROM
		tbl_hd_chamado_postagem

		WHERE
		hd_chamado=$hd_chamado
		AND aprovado IS NULL
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$sql = "
			UPDATE
			tbl_hd_chamado_postagem

			SET
			data_aprovacao=NOW(),
			aprovado=false,
			admin=$login_admin,
			motivo='$motivo',
			obs='$obs'

			WHERE
			hd_chamado=$hd_chamado
			";
			@$res = pg_query($con, $sql);

			if (pg_errormessage($con)) {
				pg_errormessage($con);
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
			}
			else {
				$msg = "Postagem do chamado $hd_chamado reprovada";

				$sql = "SELECT email FROM tbl_admin WHERE admin=$login_admin";
				$res = pg_query($con, $sql);
				$remetente = pg_result($res, 0, email);
				
				$sql = "SELECT email FROM tbl_admin WHERE admin=(SELECT atendente FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado)";
				$res = pg_query($con, $sql);
				$destinatario = pg_result($res, 0, email);
				
				$assunto = "Postagem Reprovada - Chamado $hd_chamado";
				$mensagem = "Prezado,
				
				Foi reprovada a sua solicitaзгo para postagem do chamado $hd_chamado. Verifique o chamado.
				
				CallCenter - Telecontrol";
				$mensagem = nl2br($mensagem);
				$headers = "From: $remetente\n";
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1\n";


				if(in_array($login_fabrica,array(24,35,81,86))){
					switch ($login_fabrica) {
						case 24:
							$username = 'tc.sac.suggar@gmail.com';
							$senha = 'tcsuggar';
							break;
						case 35:
							$username = 'tc.sac.cadence@gmail.com';
							$senha = 'tccadence';
							break;
						case 81:
							$username = 'tc.sac.bestway@gmail.com';
							$senha = 'tcbestway';
							break;	
						case 86:
							$username = 'tc.sac.famastil@gmail.com';
							$senha = 'tcfamastil';
							break;										
					}

					$mailer = new PhpMailer(true);

				    $mailer->IsSMTP();
				    $mailer->Mailer = "smtp";
				    
				    $mailer->Host = 'ssl://smtp.gmail.com';
				    $mailer->Port = '465';
				    $mailer->SMTPAuth = true;
		                   
				    $mailer->Username = $username;
				    $mailer->Password = $senha;
				    $mailer->SetFrom($username, $username); 
				    $mailer->AddAddress($destinatario,$destinatario ); 
				    $mailer->Subject = utf8_encode($assunto);
				    $mailer->Body = utf8_encode($mensagem);

				    try{
						$mailer->Send();									
				    }catch(Exception $e){
						$msg_erro = "Mensagem nгo enviada";
				    }
					

				}else{
					mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
		}
		else {
			$msg_erro = "O status da postagem do chamado $hd_chamado nгo permite Reprovar";
		}
	break;

	default:
		$msg_erro = "Opзгo de status invбlida";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
	$sql = "ROLLBACK TRANSACTION";
}
elseif (strlen($msg)) {
	echo "$acao|sucesso|$msg";
	$sql = "COMMIT";
}

$res = pg_query($con, $sql);

?>