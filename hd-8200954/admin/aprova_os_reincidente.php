<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($cook_cliente_admin) {#HD 253575 - INICIO

	$admin_privilegios = "call_center";
	$layout_menu       = "callcenter";

} else {

	$admin_privilegios = "auditoria";

}#HD 253575 - FIM

include "autentica_admin.php";
include 'funcoes.php';

function verificaTodasAuditoria($status_oss) {
	$tipos = array(
		"67"  => array(19,90,13,131),
		"68"  => array(19,90,13,131),
		"70"  => array(19,90,13,131),
		"95"  => array(19,13),
		"134"  => array(19,90,13,131,135,139),
		"157"  => array(19,90,13,131),
	);

	TIRARSTATUS:

    foreach($tipos as $tipo_key => $tipo_value ) {
		if(in_array($tipo_key,$status_oss)){
			$os_status = array_search($tipo_key,$status_oss);
			foreach($tipo_value as $tipo_aprovado) {
				if(in_array($tipo_aprovado,$status_oss)){
					$status_os = array_search($tipo_aprovado,$status_oss);
					unset($status_oss[$os_status]);
					$status_oss_aux = $status_oss;
					unset($status_oss[$status_os]);
					if(in_array($tipo_key,$status_oss)){
						goto TIRARSTATUS;
					}

					if($os_status < $status_os ){
						unset($tipos[$tipo_key]);
						goto TIRARSTATUS;
					}else{
						if(in_array($tipo_aprovado,$status_oss)){
							$status_os2 = array_search($tipo_aprovado,$status_oss);
							unset($status_oss[$status_os2]);
							$status_oss[$status_os] = $tipo_aprovado;
							goto TIRARSTATUS;
						}
					}
				}
			}
			return false;
		}else{
			unset($tipos[$tipo_key]);
			goto TIRARSTATUS;
		}
	}
	return true;
}

$sql_tipo  = "67,68,70,134,19,95";
$aprovacao = " 157, 67, 131, 68, 70,95,134";

$vet_recusar = array(11,24,90,91,94,104,105,115,116,120,126,129,131,132,136,139,155,172);//HD 682454 hd_chamado=2598734

$fabricas_interacao          = array(14,24,50,52,90,91,126,129,131,132,136,139);
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {

		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo") {
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		} else {
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);

		if (pg_numrows ($res) > 0) {

			for ($i = 0; $i < pg_numrows($res); $i++) {
				$cnpj         = trim(pg_result($res, $i, 'cnpj'));
				$nome         = trim(pg_result($res, $i, 'nome'));
				$codigo_posto = trim(pg_result($res, $i, 'codigo_posto'));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}

		}

	}

	exit;

}


$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if (strlen($btn_acao) > 0 AND strlen($select_acao) > 0){

	 $checks = $_POST['checks'];

	$qtde_os    = trim($_POST["qtde_os"]);
	$observacao = trim(utf8_decode($_POST["observacao"]));

	if ($select_acao == "13" AND strlen($observacao) == 0) {
		$observacao = "OS recusada pelo fabricante";
	} else if (strlen($observacao) > 0) {
		$observacao = " $observacao ";
	}

	if ($login_fabrica == 14) {

		if ($select_acao == '163') {
			$observacao = $_POST['motivo_recusa'];
		}

	}

	if ($login_fabrica == 104 || $login_fabrica == 105 || $login_fabrica == 91 || $login_fabrica == 120 or $login_fabrica == 201) {

		if ($select_acao == '13' or $select_acao == '131') {

			if (strlen($_POST['observacao']) > 0) $observacao = $_POST['observacao'];
			else $msg_erro = "Informe o motivo para recusar a OS";

			if (strlen($msg_erro) > 0) $msg_erro_motivo = $msg_erro;
		}

	}


	if ($select_acao == "19" AND strlen($observacao) == 0) {
		$observacao = "OS aprovada pelo fabricante";
	} else if (strlen($observacao) > 0) {
		$observacao = " $observacao ";
	}

	if (strlen($qtde_os) == 0) {
		$qtde_os = 0;
	}

	for ($x = 0; $x < $qtde_os; $x++) {
			$xxos = trim($checks["check_".$x]);
		if (in_array($login_fabrica, array(52,72,91,126,131,136,139)) && strlen($xxos) > 0) {

			$sql_posto = "SELECT tbl_posto_fabrica.contato_email as email, tbl_posto_fabrica.posto FROM tbl_os JOIN tbl_posto_fabrica USING(posto, fabrica) WHERE os = $xxos;";
			$res_posto = @pg_exec($con, $sql_posto);

			if (@pg_numrows($res_posto) > 0) {

				$posto           = trim(pg_result($res_posto, 0, 'posto'));
				$remetente_email = trim(pg_result($res_posto, 0, 'email'));

			} else {

				$msg_erro = 'Erro ao buscar dados do posto!';

			}

		}
		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0) {

			$res_os = pg_exec($con, "BEGIN TRANSACTION");

			switch ($login_fabrica) {
				case 91 : $sql_tipo = "68, 157";
				break;
				case ($login_fabrica == 14 or $login_fabrica == 11 or $login_fabrica == 24 or $login_fabrica >= 90) : $sql_tipo = "67,68,70,95";
				break;
				case 52: $sql_tipo = "67,70,134";
				break;
			}

			$sql    = "SELECT status_os FROM tbl_os_status WHERE status_os IN ($sql_tipo) AND os = $xxos ORDER BY data DESC LIMIT 1";
			$res_os = pg_exec($con, $sql);

			if (in_array($login_fabrica, array(72, 91))){
				$sql = "SELECT sua_os,consumidor_revenda FROM tbl_os where os = $xxos";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0 ) {
					$xsua_os = pg_fetch_result($res,0 , 'sua_os');
					$xconsumidor_revenda = pg_fetch_result($res,0 , 'consumidor_revenda');

				}
			}

			if (pg_numrows($res_os) > 0) {

				$status_da_os = trim(pg_result($res_os, 0, 'status_os'));

				if (in_array($status_da_os, array(67,68,70,72,95,157))) {

					if ($login_fabrica == 72) {
						include_once '../class/communicator.class.php';
                        include_once "../class/email/PHPMailer/PHPMailerAutoload.php";
						$mailer = new PHPMailer();
						$mailTc = new TcComm($externalId);
					}

					if ($select_acao == "99" && $login_fabrica == 72) {
						$sql = "UPDATE tbl_os SET excluida = true WHERE os = $xxos";
						$res = pg_query($con, $sql);

						if (pg_last_error()) {
							$msg_erro .= "Erro ao cancelar a OS $xxos";
						} else {
							$assunto  = "A O.S $xxos FOI CANCELADA PELA AUDITORIA DE REINCIDÊNCIA.";
							$mensagem = "A O.S de Número $xxos, foi cancelada pelo fabricante.";
							
							$sql = "
								INSERT INTO tbl_comunicado( mensagem ,
																descricao ,
																tipo ,
																fabrica ,
																obrigatorio_site ,
																posto ,
																pais ,
																ativo ,
																remetente_email
													) VALUES ( 	'$mensagem' ,
																'$assunto' ,
																'Comunicado' ,
																$login_fabrica ,
																't' ,
																$posto ,
																'BR' ,
																't' ,
																'$remetente_email'
													);
							";
							$res = pg_query($con, $sql);

							$mailTc->setEmailSubject($assunto);
		                    $mailTc->addToEmailBody(strtoupper($mensagem));
		                    $mailTc->setEmailFrom("helpdesk@telecontrol.com.br");
		                    $mailTc->addEmailDest($remetente_email);
		                    $mailTc->sendMail();
						}
					} else if ($select_acao == "00") {

						$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
								VALUES ($xxos,19,current_timestamp,'OS aprovada pelo fabricante na auditoria de OS reincidente',$login_admin)";

						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if ($telecontrol_distrib && !isset($novaTelaOs)) {
				            if (!os_em_intervencao($xxos)) {

				              $descricao_status_anterior = get_ultimo_status_os($xxos);
				              
				              atualiza_status_checkpoint($xxos, $descricao_status_anterior);

				            }
				        }

						if (in_array($login_fabrica, array(72, 91))) {//HD 682454

							/* HD - 3317939*/
							if ($login_fabrica == 72 && empty($observacao)) {
								$observacao = "A O.S FOI APROVADA PELA AUDITORIA DE REINCIDÊNCIA.";
							}

							if(strlen($observacao) >0 && $login_fabrica == 91) {
								$mensagem_wanke = "&nbsp;<b>2</b> $observacao";
							}

							if ($xconsumidor_revenda == 'R'){
								$assunto  = 'A O.S '.$xsua_os.' FOI APROVADA PELA AUDITORIA DE REINCIDÊNCIA.';
							}else{
								$assunto  = 'A O.S '.$xxos.' FOI APROVADA PELA AUDITORIA DE REINCIDÊNCIA.';
							}


							if ($xconsumidor_revenda == 'R'){
								$mensagem = 'A O.S de Número '.$xsua_os.', foi aprovada pelo fabricante.'.$mensagem_wanke;
							}else{
								$mensagem = 'A O.S de Número '.$xxos.', foi aprovada pelo fabricante.'.$mensagem_wanke;
							}

							$header   = 'MIME-Version: 1.0' . "\r\n";
							$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
							$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

							$sql = "INSERT INTO tbl_comunicado( mensagem ,
																descricao ,
																tipo ,
																fabrica ,
																obrigatorio_site ,
																posto ,
																pais ,
																ativo ,
																remetente_email
													) VALUES ( 	'$mensagem' ,
																'$assunto' ,
																'Comunicado' ,
																$login_fabrica ,
																't' ,
																$posto ,
																'BR' ,
																't' ,
																'$remetente_email'
													);";

							$res = pg_exec($con, $sql);


							if ($login_fabrica == 72) {
								$mailTc->setEmailSubject($assunto);
			                    $mailTc->addToEmailBody(strtoupper($mensagem));
			                    $mailTc->setEmailFrom("helpdesk@telecontrol.com.br");
			                    $mailTc->addEmailDest($remetente_email);
			                    $mailTc->sendMail();
							} else {
								@mail($remetente_email, utf8_encode($assunto), utf8_encode($mensagem), $header);
							}

						}

					} else if ($select_acao == "90" and in_array($login_fabrica,array(91,126,131,136,139))) {

						$sql = "INSERT INTO tbl_os_status (
									os,
									status_os,
									data,
									observacao,
									admin
								) VALUES (
									{$xxos},
									90,
									current_timestamp,
									'OS aprovada sem pagamento pelo fabricante na auditoria de OS reincidente. Motivo: {$observacao}',
									{$login_admin}
								)";
						$res = pg_query($con, $sql);

						$sql = "SELECT os FROM tbl_os_extra WHERE os = {$xxos}";
							$res = pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);
							if (pg_num_rows($res) == 0 ) {
								$sql = "INSERT INTO tbl_os_extra (os, extrato) VALUES ({$xxos}, 0)";
								$res = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);
							} else {
								$sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$xxos}";
								$res = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);
							}


						if (!pg_last_error()) {
							if (strlen($observacao) > 0) {
								$mensagem_wanke = "&nbsp;<b>2</b> $observacao";
							}


							if ($xconsumidor_revenda == 'R'){
								$assunto  = 'A O.S '.$xsua_os.' FOI APROVADA SEM PAGAMENTO PELA AUDITORIA DE REINCIDÊNCIA.';
							}else{
								$assunto  = 'A O.S '.$xxos.' FOI APROVADA SEM PAGAMENTO PELA AUDITORIA DE REINCIDÊNCIA.';
							}


							if ($xconsumidor_revenda == 'R'){
								$mensagem = 'A O.S de Número '.$xsua_os.', foi aprovada sem pagamento pelo fabricante.'.$mensagem_wanke;
							}else{
								$mensagem = 'A O.S de Número '.$xxos.', foi aprovada sem pagamento pelo fabricante.'.$mensagem_wanke;
							}

							$header   = 'MIME-Version: 1.0' . "\r\n";
							$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
							$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

							$sql = "INSERT INTO tbl_comunicado (
										mensagem ,
										descricao ,
										tipo ,
										fabrica ,
										obrigatorio_site ,
										posto ,
										pais ,
										ativo ,
										remetente_email
									) VALUES (
										'$mensagem' ,
										'$assunto' ,
										'Comunicado' ,
										$login_fabrica ,
										't' ,
										$posto ,
										'BR' ,
										't' ,
										'$remetente_email'
									)";

							$res = pg_query($con, $sql);

							if (!pg_last_error()) {
								if (!empty($remetente_email) && filter_var($remetente_email, FILTER_VALIDATE_EMAIL)) {
									mail($remetente_email, utf8_encode($assunto), utf8_encode($mensagem), $header);
								}
							}
						}


						if (pg_last_error()) {
							$msg_erro = "Erro ao aprovar OS";
						}
					} else {

						if ( in_array($login_fabrica, array(11,172)) ) {

							$sql = "INSERT INTO tbl_os_status (
											os        ,
											status_os ,
											observacao,
											admin,
											status_os_troca
										) VALUES (
											'$xxos'      ,
											'13'         ,
											'$observacao',
											$login_admin,
											'f'
										);";

							$res = pg_query($con, $sql);

						} else if (in_array($login_fabrica,array(24,91,126,131,136,139,155))) {//HD 682454 hd_chamado=2598734

							$observacao = utf8_decode($observacao);

							$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
									VALUES ($xxos,15,current_timestamp,'Motivo: $observacao	- Os excluída pelo fabricante em Reincidência',$login_admin)";
							$res       = pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "UPDATE tbl_os set excluida = 't' where os = $xxos";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT fn_os_excluida($xxos, $login_fabrica, $login_admin)";
							$res = pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);

							#158147 Paulo/Waldir desmarcar se for reincidente
							$sql = "SELECT fn_os_excluida_reincidente($xxos, $login_fabrica)";
							$res = pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);


							if ($login_fabrica == 91) {//HD 682454

								if ($xconsumidor_revenda == 'R'){
									$assunto  = 'A O.S '.$xsua_os.' FOI RECUSADA PELA AUDITORIA DE REINCIDÊNCIA.';
								}else{
									$assunto  = 'A O.S '.$xxos.' FOI RECUSADA PELA AUDITORIA DE REINCIDÊNCIA.';
								}


								if ($xconsumidor_revenda == 'R'){
									$mensagem = 'A O.S de Número '.$xsua_os.', foi recusada. Motivo:'.$observacao;
								}else{
									$mensagem = 'A O.S de Número '.$xxos.',foi recusada. Motivo: '.$observacao;
								}


								$header   = 'MIME-Version: 1.0' . "\r\n";
								$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
								$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

								$sql = "INSERT INTO tbl_comunicado( mensagem ,
																	descricao ,
																	tipo ,
																	fabrica ,
																	obrigatorio_site ,
																	posto ,
																	pais ,
																	ativo ,
																	remetente_email
														) VALUES ( 	'$mensagem' ,
																	'$assunto' ,
																	'Comunicado' ,
																	$login_fabrica ,
																	't' ,
																	$posto ,
																	'BR' ,
																	't' ,
																	'$remetente_email'
														);";

								$res = pg_exec($con, $sql);

								@mail('swat2@wanke.com.br', utf8_encode($assunto), utf8_encode($mensagem), $header);

							}

						} else {
							$sql_motivo = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
							$res_motivo = pg_query($con, $sql_motivo);

							if (pg_num_rows($res_motivo) > 0) {

								if ($select_acao <> '163') {
									$motivo = pg_result($res_motivo, 0, 'motivo');
								} else {
									$motivo = $observacao;
								}

								$status_os = pg_result($res_motivo, 0, 'status_os');

							}

							if ($login_fabrica == 104 || $login_fabrica == 105 || $login_fabrica == 120 or $login_fabrica == 201) {
								$motivo = $observacao;
							}

							if ($status_os == 13 || (in_array($login_fabrica,array(52,90,94,104,105,120,201)) && $select_acao == 13)) {

								$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
										VALUES ($xxos, 131, current_timestamp, 'Motivo: $motivo - Os recusada pelo fabricante', $login_admin)";
								$res       = pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);

								if ($login_fabrica <> 94 && $select_acao != 172) {

									//3624446
									if ($login_fabrica == 120 or $login_fabrica == 201) {
										$sql = "UPDATE tbl_os set finalizada = null, data_fechamento = current_date, cancelada = 't',  status_checkpoint = 28 FROM tbl_os_extra where tbl_os.os = $xxos and tbl_os_extra.os = tbl_os.os and tbl_os_extra.extrato isnull";
									} else {
										$sql = "UPDATE tbl_os set finalizada = null, data_fechamento = null FROM tbl_os_extra where tbl_os.os = $xxos and tbl_os_extra.os = tbl_os.os and tbl_os_extra.extrato isnull";
									} 

									
									$res = pg_exec($con, $sql);
								}

								if ($login_fabrica == 52) {//HD 676626

									$assunto  = 'A O.S '.$xxos.' FOI RECUSADA PELA AUDITORIA DE REINCIDÊNCIA.';
									$mensagem = 'A O.S de Número '.$xxos.', foi recusada por apresentar irregularidades no seu preenchimento.';

									$header   = 'MIME-Version: 1.0' . "\r\n";
									$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
									$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

									$sql = "INSERT INTO tbl_comunicado( mensagem ,
																		descricao ,
																		tipo ,
																		fabrica ,
																		obrigatorio_site ,
																		posto ,
																		pais ,
																		ativo ,
																		remetente_email
															) VALUES ( 	'$mensagem' ,
																		'$assunto' ,
																		'Comunicado' ,
																		$login_fabrica ,
																		't' ,
																		$posto ,
																		'BR' ,
																		't' ,
																		'$remetente_email'
															);";

									$res = pg_exec($con, $sql);

									@mail($remetente_email, utf8_encode($assunto), utf8_encode($mensagem), $header);

								}


							}

							if ($status_os == "15") {

								$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
										VALUES ($xxos, 136, current_timestamp, '$motivo - Os excluída pelo fabricante em Reincidência', $login_admin)";

								$res       = pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os set excluida = 't' where os = $xxos";
								$res = pg_exec($con, $sql);

								#158147 Paulo/Waldir desmarcar se for reincidente
								$sql = "SELECT fn_os_excluida_reincidente($xxos, $login_fabrica)";
								$res = pg_exec($con, $sql);

							}

						}

					}

				}

				if ($status_da_os == 134) {
					if ($select_acao == "00") {

						$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
								VALUES ($xxos, 135, current_timestamp, 'OS aprovada pelo fabricante na auditoria de OS reincidente de peças e serviço', $login_admin)";

						$res       = pg_exec($con, $sql);
						$msg_erro .= pg_errormessage($con);

					} else if ($select_acao == "90" and $login_fabrica == 91) {
						$sql = "INSERT INTO tbl_os_status (
									os,
									status_os,
									data,
									observacao,
									admin
								) VALUES (
									{$xxos},
									90,
									current_timestamp,
									'OS aprovada sem pagamento pelo fabricante na auditoria de OS reincidente',
									{$login_admin}
								)";
						$res = pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);
							$sql = "SELECT os FROM tbl_os_extra WHERE os = {$xxos}";
							$res = pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);
							if (pg_num_rows($res) == 0 ) {
								$sql = "INSERT INTO tbl_os_extra (os, extrato) VALUES ({$xxos}, 0)";
								$res = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);
							} else {
								$sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$xxos}";
								$res = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);
							}

						if (pg_last_error()) {
							$msg_erro = "Erro ao aprovar OS";
						}
					} else {

						$sql       = "SELECT motivo_recusa, motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
						$res       = pg_exec($con, $sql);
						if(pg_num_rows($res) > 0 ) {
                            $motivo_recusa  = pg_result($res, 0, 'motivo_recusa');
							$motivo         = pg_result($res, 0, 'motivo');
							$status_os      = pg_result($res, 0, 'status_os');
							$select_acao    = $status_os;
						}

						if ($select_acao == "13") {

							$sql = "INSERT INTO tbl_os_status(os, status_os, data, observacao, admin)
									VALUES ($xxos, 13, current_timestamp, '$motivo - Os recusada pelo fabricante', $login_admin)";
							$res       = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
							if($motivo_recusa != 172){
                                $sql = "UPDATE tbl_os set finalizada = null, data_fechamento = null where os = $xxos";
                                $res = pg_exec($con,$sql);
                            }
							if ($login_fabrica == 52 || $login_fabrica == 91) {//HD 676626

								$assunto  = 'A O.S '.$xxos.' FOI RECUSADA PELA AUDITORIA DE REINCIDÊNCIA.';
								$mensagem = 'A O.S de Número '.$xxos.', foi recusada por apresentar irregularidades no seu preenchimento.';

								$header   = 'MIME-Version: 1.0' . "\r\n";
								$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
								$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

								$sql = "INSERT INTO tbl_comunicado( mensagem ,
																	descricao ,
																	tipo ,
																	fabrica ,
																	obrigatorio_site ,
																	posto ,
																	pais ,
																	ativo ,
																	remetente_email
														) VALUES ( 	'$mensagem' ,
																	'$assunto' ,
																	'Comunicado' ,
																	$login_fabrica ,
																	't' ,
																	$posto ,
																	'BR' ,
																	't' ,
																	'$remetente_email'
														);";

								$res = pg_exec($con, $sql);

								@mail($remetente_email, utf8_encode($assunto), utf8_encode($mensagem), $header);

							}

						}

						if ($status_os == "15") {

							$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
									VALUES ($xxos, 136, current_timestamp, '$motivo - Os excluída pelo fabricante em Reincidência', $login_admin)";

							$res       = pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "UPDATE tbl_os set excluida = 't' where os = $xxos";
							$res = pg_exec($con,$sql);

							#158147 Paulo/Waldir desmarcar se for reincidente
							$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
							$res = pg_exec($con, $sql);

						}

					}

				}

			}

			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
			} else {
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}

		}

	}

		if(strlen($msg_erro) == 0){
			$json = json_encode(array("success"=>"true"));
		}else{
			$json = json_encode(array("success"=>"false", "msg"=>"$msg_erro"));
		}
		echo $json;
		die;
}

if ($login_fabrica == 52) {

	if ($cook_cliente_admin) {

		$layout_menu = "callcenter";
		$title       = "AUDITORIA DE OS REINCIDENTES";

	} else {

		$layout_menu = "auditoria";
		$title       = "AUDITORIA DE OS REINCIDENTES";

	}

} else {

	$layout_menu = "auditoria";
	$title       = "AUDITORIA DE OS REINCIDENTES";

}

include "cabecalho_new.php"; 
$plugins = array(
    /*"select2",*/
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
);

include("plugin_loader.php");
?>
<style type="text/css" media="screen">

	.status_checkpoint{
		width:15px;
		height:15px;
		margin:2px 5px;
		padding:0 5px;
		border:1px solid #666;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.espaco{
		padding-left:120px;
	}
	.subtitulo{
		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
	}
	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.titulo_coluna {
		background-color: #596D9B;
		color: #FFFFFF;
		font: bold 11px "Arial";
		text-align: center;
	}
	/*ELEMENTOS DE POSICIONAMENTO*/
	#container {
	  border: 0px;
	  padding:0px 0px 0px 0px;
	  margin:0px 0px 0px 0px;
	  background-color: white;
	}
	#tooltip{
		background: #FF9999;
		border:2px solid #000;
		display:none;
		padding: 2px 4px;
		color: #003399;
	}

	.titulo {
	font-family: Arial;
	font-size: 7pt;
	color: #000000;
	background: #ced7e7;
}
</style>

<script language="JavaScript" type="text/javascript">

	window.onload = function(){
		//tooltip.init();
	}

	function fnc_pesquisa_posto2 (campo, campo2, tipo) {

		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {

			var url = "";

			url = "posto_pesquisa_2.php?campo="+xcampo.value+"&tipo="+tipo+"&os=t";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;

			if ("<? echo $pedir_sua_os; ?>" == "t") {
				janela.proximo = document.frm_consulta.sua_os;
			} else {
				janela.proximo = document.frm_consulta.data_abertura;
			}

			janela.focus();

		} else {
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}

	}

	var ok   = false;
	var cont = 0;

	function checkaTodos() {

		f = document.frm_pesquisa2;

		if (!ok) {

			for (i = 0; i < f.length; i++) {

				if (f.elements[i].type == "checkbox") {

					f.elements[i].checked = true;
					ok = true;

					if (document.getElementById('linha_'+cont)) {
						document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
					}

					cont++;
				}

			}

		} else {

			for (i = 0; i < f.length; i++) {

				if (f.elements[i].type == "checkbox") {

					f.elements[i].checked = false;
					ok = false;

					if (document.getElementById('linha_'+cont)) {
						document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
					}

					cont++;

				}

			}

		}

	}

	function setCheck(theCheckbox,mudarcor,cor) {

		if (document.getElementById(mudarcor)) {
			document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
		}

	}
</script>

<script type="text/javascript" charset="utf-8">

	$(function() {

		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$('.radio_btn').click(function() {

			valor_radio = $(this).val();

			if (valor_radio == 'reincidentes_cinco_dias') {
				$('#td_datas1').slideUp('fast');
				$('#td_datas2').slideUp('fast');
			} else {
				$('#td_datas1').slideDown('fast');
				$('#td_datas2').slideDown('fast');
			}

		});

	});


</script>

<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type="text/javascript" src='ajax.js'></script>
<script type="text/javascript" src="../js/bibliotecaAJAX.js"></script>

<script language="JavaScript">

	$().ready(function() {

		//HD -  INICIO

		$("#filtro_check_estado").click(function(){
			if ($('#filtro_check_estado').is(':checked')) {
				$('#filtro_pesquisa_estado').show();
			} else {
				$('#filtro_pesquisa_estado').hide();
			}

		});

		if ($('#filtro_check_estado').is(':checked')) {
				$('#filtro_pesquisa_estado').show();
		} else {
			$('#filtro_pesquisa_estado').hide();
		}

		//HD 722270 - FIM
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

	function abreInteracao(linha, os, tipo, posto) {

		var div  = document.getElementById('interacao_'+linha);
		var os   = os;
		var tipo = tipo;

		if($("#interacao_"+linha).is(":visible")){
			$("#interacao_"+linha).hide();
		}else{
			$("#interacao_"+linha).show();
		}

		$.ajax({

			url: 'ajax_grava_interacao.php',
			type: 'POST',
			data: {linha:linha,os:os,tipo:tipo,posto:posto},
			success: function(campos) {

				campos_array   = campos.split("|");
				resposta       = campos_array[0];
				linha          = campos_array[1];

				var div        = document.getElementById('interacao_'+linha);
				div.innerHTML  = resposta;

				var comentario = document.getElementById('comentario_'+linha);
				comentario.focus();

			}

		});

	}

	function div_detalhe_carrega2 (campos) {

		campos_array   = campos.split("|");
		resposta       = campos_array [0];
		linha          = campos_array [1];

		var div        = document.getElementById('interacao_'+linha);
		div.innerHTML  = resposta;

		var comentario = document.getElementById('comentario_'+linha);
		comentario.focus();

	}

	function gravarInteracao(linha, os, tipo, posto, email) {
		var comentario = $.trim($("#comentario_"+linha).val());

		if (comentario.length == 0) {
			alert("Insira uma mensagem para interagir");
		} else {
			$.ajax({
				url: "ajax_grava_interacao.php",
				type: "GET",
				data: {
					linha: linha,
					os: os,
					tipo: tipo,
					comentario: comentario
				},
				beforeSend: function () {
					$("#interacao_"+linha).hide();
					$("#loading_"+linha).show();
				},
				complete: function(data){
					data = data.responseText;

					if (data == "erro") {
						alert("Ocorreu um erro ao gravar interação");
					} else {
						$("#loading_"+linha).hide();
						$("#gravado_"+linha).show();

						setTimeout(function () {
							$("#gravado_"+linha).hide();
						}, 3000);

						$("#linha_"+linha).css({
							"background-color": "#FFCC00"
						});
					}

					$("#comentario_"+linha).val("");
				}
			});
		}
	}


	function div_detalhe_carrega(campos) {

		campos_array = campos.split("|");
		resposta     = campos_array [1];
		linha        = campos_array [2];
		os           = campos_array [3];

		if (resposta == 'ok') {

			document.getElementById('interacao_' + linha).innerHTML     = "Gravado Com sucesso!!!";
			document.getElementById('btn_interacao_' + linha).innerHTML = "<input type='button' value='Interagir' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'>";
			var table = document.getElementById('linha_'+linha);
			table.style.background = "#FFCC00";

		} else {

			alert('Erro ao gravar Registro!');

		}

	}

	function recusaFabricante() {

		var motivo = prompt("Qual o Motivo da Recusa da(s) OS(s)  ?",'',"Motivo da Recusa");

		if (motivo != null && $.trim(motivo) !="" && motivo.length > 0) {
				document.getElementById('motivo_recusa').value = motivo;
				document.frm_pesquisa2.submit();
		} else {
			alert('Digite um motivo por favor!','Erro');
		}

	}

	function fnc_revenda_pesquisa (campo, tipo) {

		if (campo.value != "") {

			var url = "";
			url = "cliente_admin_pesquisa.php?forma=reduzida&campo=" + campo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");

			janela.nome	= campo;
			janela.cliente_admin = document.frm_consulta.cliente_admin;
			janela.focus();

		} else {
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}

	}
	function acaoOS(){

		var form = $("form[name=frm_pesquisa2]");
		// var frm_pesquisa2 = document.frm_pesquisa2;
		var checks = {};
		//captura os checkbox checados no resultado da pesquisa
		$('input[name^=check_][type=checkbox]:checked').each(function(){

			var name = $(this).attr("name");
       		checks[name] = $(this).val();

		});

		var data_inicial 				= $(form).find("input[name=data_inicial]").val();
		var data_final 					= $(form).find("input[name=data_final]").val();
		var aprova 						= $(form).find("input[name=aprova]").val();
		var posto_codigo 				= $(form).find("input[name=posto_codigo]").val();
		var filtro_check_estado 		= $(form).find("input[name=filtro_check_estado]").val();
		var filtro_pesquisa_estado 		= $(form).find("input[name=filtro_pesquisa_estado]").val();
		var posto_nome 					= $(form).find("input[name=posto_nome]").val();
		var check_data_fechamento 		= $(form).find("input[name=check_data_fechamento]").val();
		var check_defeito_constatado	= $(form).find("input[name=check_defeito_constatado]").val();
		var qtde_os 					= $(form).find("input[name=qtde_os]").val();
		var select_acao 				= $(form).find("select[name=select_acao]").val();
		var observacao 					= $(form).find("input[name=observacao]").val();
		var motivo_recusa 				= $(form).find("input[name=motivo_recusa]").val();
		var btn_acao 					= $(form).find("input[name=btn_acao]").val();

		var dataAjax = {
			"data_inicial"				: data_inicial,
			"data_final"				: data_final,
			"aprova"					: aprova,
			"posto_codigo"				: posto_codigo,
			"filtro_check_estado"		: filtro_check_estado,
			"filtro_pesquisa_estado"	: filtro_pesquisa_estado,
			"posto_nome"				: posto_nome,
			"check_data_fechamento"		: check_data_fechamento,
			"check_defeito_constatado"	: check_defeito_constatado,
			"qtde_os"					: qtde_os,
			"select_acao"				: select_acao,
			"observacao"				: observacao,
			"motivo_recusa"				: motivo_recusa,
			"btn_acao"					: btn_acao,
			"checks"					: checks

		};

		$.ajax({
			url: "<?=$PHP_SELF?>",
			type:"POST",
			data: dataAjax,
			complete: function(retorno){

				var data = retorno.responseText;
				data = $.parseJSON(data);

				if(data.success == "true"){

					$('input[name^=check_][type=checkbox]:checked').each(function(){

			            var trChecked = $(this).parent("td").parent("tr");

			            $(trChecked).nextUntil("tr[id^=linha_]").remove();
			            $(trChecked).remove();
					});
					$("input[name=observacao]").val('');
				}else{
					alert(data.msg);
				}
			}
		});
	}
</script>
<?php

if ($btn_acao == 'Pesquisar' and empty($select_acao)) {

	$data_inicial 	= trim($_POST['data_inicial']);
	$data_final   	= trim($_POST['data_final']);
	$aprova       	= trim($_POST['aprova']);
	$os           	= trim($_POST['os']);
	$status_os    	= trim($_POST['status_os']);
	$tipoData       = trim($_POST['tipo_data']);


	if ($login_fabrica == 24 || $login_fabrica == 52) {//HD - 722270 - INICIO

		$filtro_check_estado = $_POST['filtro_check_estado'];

		if ($filtro_check_estado == 'true') {

			$filtro_pesquisa_estado = $_POST['filtro_pesquisa_estado'];

			if (strlen($filtro_pesquisa_estado) > 0) {

				$sql_filtro_estado = " AND tbl_posto.estado = '$filtro_pesquisa_estado' ";

			} else {

				$sql_filtro_estado = " ";
				if($login_fabrica == 24){
					$msg_erro = "Informe o ESTADO do filtro de pesquisa";
				}

			}

		}

	} //HD - 722270 - FIM

	if ($login_fabrica == 52) {
		$cliente_admin	= trim($_POST['cliente_admin']);
	}

	if (strlen($os) > 0) {
		$Xos = " AND tbl_os.sua_os = '$os' ";
	}

	if (strlen($aprova) == 0) {

		$aprova = "aprovacao";

		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE ";

	} else if ($aprova == "aprovacao") {

		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE";

	} else if ($aprova == "aprovadas") {

		$aprovacao = "19";
		$sql_add2  = " AND tbl_os_status.extrato IS NULL AND tbl_os.excluida IS NOT TRUE";

	} else if($aprova == "reprovadas") {

		$aprovacao = ( in_array($login_fabrica, array(11,24,172)) ) ? "13, 15 " : "15, 131";
		$sql_add2  = " AND tbl_os_status.extrato IS NULL ";

	} else if ($login_fabrica == 94 and $aprova == 'reincidentes_cinco_dias') {//HD 415029 - gabrielSilveira - 15/06/2011

		$aprovacao =  "67, 68, 70";

	} else if ($login_fabrica == 91 and $aprova == "aprovada_sem_pagamento") {
		$aprovacao =  "90";
	}

	if (strlen($status_os) > 0) {

		$sql_tipo = $status_os;
	} else {

		switch ($login_fabrica) {

			case ( in_array($login_fabrica, array(6,11,14,172)) ):
				$sql_tipo = "67, 68, 70, 131, 19, 13";
			break;
			case 52:
				$sql_tipo = "67, 134, 135, 13, 19, 131";
			break;
			case 24:

				$sql_tipo = "67, 68, 70, 131, 19, 13";

				#if ($tipoData == 'liberada_auditoria') $sql_tipo = "99";
				
			break;
			case 91:
				$sql_tipo = "15, 19, 157, 90, 131, 68";
			break;
			case 90:
				$sql_tipo = "67, 68, 70, 19";
			break;
			case 94:
				$sql_tipo = "67, 68, 70, 131, 135, 19";
			break;
			case 104:
				$sql_tipo = "67,68,70,134,19,131";
			break;
			case 105:
				$sql_tipo = "67,68,70,134,19,131";
			break;
			case 120:
				$sql_tipo = "67,68,70,134,19,131";
			break;
			case 155: //hd_chamado=2598734
				$sql_tipo = "70,19,15";
			break;

		}

	}

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];

	if ($login_fabrica == 91) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if(!checkdate($mi, $di, $yi)):
			$msg_erro = "Data Inicial Inválida <br />";
		endif;

		if(!checkdate($mf, $df, $yf)):
			$msg_erro = "Data Final Inválida <br />";
		endif;

		if (empty($msg_erro))
		{
			$x_data_inicial = "{$yi}-{$mi}-{$di}";
			$x_data_final   = "{$yf}-{$mf}-{$df}";

			if(strtotime($x_data_final) < strtotime($x_data_inicial)) {
				$msg_erro = "Data Final não pode ser menor que a Data Inicial <br />";
			}

			if (strtotime($x_data_inicial.'+12 month') < strtotime($x_data_final) ) {
				$msg_erro = "O intervalo entre as datas não pode ser maior que 12 meses <br />";
			}
		}
	}

	if ($aprova != 'reincidentes_cinco_dias') {//HD 415029 - gabrielSilveira - 15/06/2011

		if ((empty($data_inicial) OR empty($data_final)) AND empty($os)) {
		    $msg_erro = "Data Inválida";
		}else {

			if(!empty($data_inicial) AND !empty($data_final)){
				if (strlen($msg_erro) == 0) {

					list($di, $mi, $yi) = explode("/", $data_inicial);
					if (!checkdate($mi,$di,$yi)) $msg_erro = "Data Inválida";

				}

				if (strlen($msg_erro) == 0) {

					list($df, $mf, $yf) = explode("/", $data_final);
					if (!checkdate($mf,$df,$yf)) $msg_erro = "Data Inválida";

				}

				if (strlen($msg_erro) == 0) {

					$aux_data_inicial = "$yi-$mi-$di";
					$aux_data_final   = "$yf-$mf-$df";

				}

				if (strlen($msg_erro) == 0) {

					if (strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_inicial) > strtotime('today')) {
						$msg_erro = "Data Inválida.";
					}

				}
			}

		}
	}
	$posto_codigo = ($_POST['posto_codigo']) ? $_POST['posto_codigo'] : null;
	$posto_nome   = ($_POST['posto_nome'])   ? $_POST['posto_nome']   : null;

}

if (strlen($msg_erro) > 0) {
	echo "<div class='alert alert-danger'><h4>$msg_erro</h4></div>";
}

//HD 415029 - SETA DISPLAY PARA OS INPUTS DAS DATAS DE ACORDO COM O RADIO SELECIONADO NO GRUPO "Mostrar as OS:"
if ($login_fabrica == 94) {

	if (empty($_POST['aprova'])) {

		$display_data = "display:none";

	} else {

		if ($aprova != 'reincidentes_cinco_dias') {

			$display_data = "display:true";

		} else {

			$display_data = "display:none";

		}

	}

}?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_consulta" class='form-search form-inline tc_formulario' method="post" action="<?echo $PHP_SELF?>">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span8'>
					<div class='control-group'>
						<label class='control-label' for='os'>Número da OS</label>
						<div class='controls controls-row'>
							<input type="text" name="os" id="os" size="20" maxlength="20" value="<?php echo $os ?>">
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?= (in_array('Data', explode(" ",$msg_erro))) ? "error" : "" ?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="span12">
							</div>
						</div>
					</div>
				</div>
				
				<div class='span4'>
					<div class='control-group <?= (in_array('Data', explode(" ",$msg_erro))) ? "error" : "" ?>'>
						<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="span12">
							</div>
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<br />
		<?php if ($login_fabrica == 24) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span6'>
					<div class='control-group'>
						<label class='control-label' for='codigo_posto'>Tipo de Data:</label>
						<br>
						<div class='controls controls-row'>
							<div class='span4'>
								<label class="radio">
		        					<input type="radio" name="tipo_data" value='abertura' <? if (strlen($tipoData) > 0 && $tipoData == 'abertura' || strlen($tipoData) == 0) echo "checked='checked'"; ?> tabindex='6'>Abertura
		    					</label>
	    					</div>
	    					<div class='span4'>
		    					<label class="radio">
							        <input type="radio" name="tipo_data" value='digitacao' <? if (strlen($tipoData) > 0 && $tipoData == 'digitacao') echo "checked='checked'"; ?> tabindex='6'>Digitação
							    </label>
							</div>
				    		<div class='span4'>
								 <label class="radio">
							        <input type="radio" name="tipo_data" value='liberada_auditoria' <? if (strlen($tipoData) > 0 && $tipoData == 'liberada_auditoria') echo "checked='checked'"; ?> tabindex='8'>
							        Liberada Auditoria
							    </label>
							</div>	
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<br>
		<?php } ?>

		<? if($login_fabrica!=104 AND $login_fabrica!=105){?>
			<div class="titulo_tabela">Informações do Posto</div>
		<? }else{ ?>
			&nbsp;
		<? } ?>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="posto_codigo" id="codigo_posto" size="15"  value="<? echo $posto_codigo ?>" class="span12">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
				
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="posto_nome" id="descricao_posto" size="40"  value="<? echo $posto_nome ?>" class="frm">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php

		if ($login_fabrica == 24 || $login_fabrica == 52) { // HD 722270
			$checked_filtro_estado = (!empty($_POST['filtro_check_estado'])) ? "CHECKED" : null;?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Filtrar por:</label>
						<div class='controls controls-row'>
							<label>
								<input type="checkbox" name="filtro_check_estado" id="filtro_check_estado" value="true" class="radio_btn" <?= $checked_filtro_estado ?> />
								Estado
							</label>	
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group' id="filtro_pesquisa_estado" style="display:none;">
					<label class='control-label' for='estado'>Estado</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<select name="filtro_pesquisa_estado"><?php
									$array_estado = array(
										"AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
									  	"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
									  	"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
									  	"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
									  	"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
									  	"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
									  	"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
									  	"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins"
									);

									foreach ($array_estado as $k => $v) {
								    	list($sigla_estado, $nome_estado) = explode("-", $v);
								    	$v = $nome_estado;

								    	$selected = ($_POST["filtro_pesquisa_estado"] == $k) ? "selected" : "";

										echo "<option value=".$k." {$selected}>".$v."</option>";
									}

								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>	
					  
		<?php

		}

		if ($login_fabrica == 52) {//HD 253575?>


			<div class="titulo_tabela">Informações do Cliente Fricon</div>
			<br/>
			<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" class='frm' name="nome" size="40" maxlength="60" value="<? echo $nome ?>" style="width:300px">
							<span class="add-on" style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_revenda_pesquisa(document.frm_consulta.nome,'nome')"><i class='icon-search' ></i></span>
							<input type="hidden" name="cliente_admin" value="<? echo $cliente_admin ?>">
				
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br />
		<?php

		}
		?>
		<div class="titulo_tabela">Informações da OS</div>
		<br />
		<?php
		if (!in_array($login_fabrica, array(104,105,94,91,155))) { ?>
			<div class="row-fluid">	
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>	
						<label class='control-label' for='status'>Status</label>
						<div class='controls controls-row'>
							<div class='span7'>
							<select class='frm' name='status_os'>
								<option> </option><?php
								$sql = "SELECT * FROM tbl_status_os WHERE status_os IN(67,131,19,13)";

								$res = pg_exec($con, $sql);

								for ($i = 0; $i < pg_numrows($res); $i++) {
									$status_os_x = pg_result($res, $i, 'status_os');
									$descricao   = pg_result($res, $i, 'descricao'); ?>
									<option value="<? echo $status_os_x;?>" <? if ($status_os == $status_os_x) echo "SELECTED";?>><?=$descricao;?></option><?php
								}?>
							</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
	<?php
		}

		if(!in_array($login_fabrica, array(115,116))){ ?>
		<?php
				if ($login_fabrica == 104 || $login_fabrica == 105) {
					$center = " text-align: center; ";
					$margin = "margin: auto auto auto 40%; text-align: center;";
				}
				echo "<br /><div class='span12'><div class='span1'></div><b>Mostrar as OS's:</b></div>";
					if ($login_fabrica == 104 || $login_fabrica == 105) {?>
						<p>
							<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovacao' value='aprovacao' <? if ((trim($aprova) == 'aprovacao' OR trim($aprova) == 0) || $login_fabrica != 94) echo "checked='checked'"; ?>>
							<label for="aprovacao" style="cursor:pointer">Em aprovação</label>
							<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovadas' value='aprovadas' <? if (trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>
							<label for="aprovadas" style="cursor:pointer">Aprovadas</label>
							<label>
								<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" value='reprovadas' <? if (trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
							</label>
						</p><?php
					} else{?>
					<div class="row-fluid">	
					<? if ($login_fabrica == 94) {?> ?>
						<div class='span1'></div>
					<? } else { ?>
						<div class='span2'></div>
					<? } ?>
						
						<div class='span2'>
							<div class='control-group'>
								<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovacao' value='aprovacao' <? if ((trim($aprova) == 'aprovacao' OR trim($aprova) == 0) || $login_fabrica != 94) echo "checked='checked'"; ?>>
								<label for="aprovacao" style="cursor:pointer">Em aprovação</label>
							</div>
						</div>
						<div class='span2'>
							<div class='control-group'>
								<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovadas' value='aprovadas' <? if (trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>
							<label for="aprovadas" style="cursor:pointer">Aprovadas</label>
							</div>
						</div>
						<div class='span2'>
							<div class='control-group'>
							<?
							if ($login_fabrica != 90) {
							?>
									<label>
										<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" value='reprovadas' <? if (trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>> Reprovadas
									</label>

							<?php
							} 
							?>
		
							</div>
						</div>
						<? if ($login_fabrica == 91 OR $login_fabrica == 126) {
						?>
							<div class='span3'>
								<div class='control-group'>
									<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovada_sem_pagamento' value='aprovada_sem_pagamento' <? if (trim($aprova) == 'aprovada_sem_pagamento') echo "checked='checked'"; ?> />
									<label for="aprovada_sem_pagamento" style="cursor:pointer">Aprovada sem pagamento</label>
								</div>
							</div>	
						<?php
						}
						if ($login_fabrica == 94) {?>
						<div class='span5'>
								<div class='control-group'>
									<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='reincidentes_cinco_dias' value='reincidentes_cinco_dias' <? if (trim($aprova) == 'reincidentes_cinco_dias' || ($login_fabrica == 94 && empty($aprova) ) ) echo "checked='checked'"; ?> >
									<label for="reincidentes_cinco_dias" style='cursor:pointer'>OS que entraram em reincidências nos últimos 5 dias</label>
								</div>
						</div>
						<?php
						} ?>
					</div>
						<?php
					}

					if ( in_array($login_fabrica, array(11,172)) ) {?>
					<div class="row-fluid">
						<div class='span2'></div>
						<div class='span4'>
							<div class='control-group'>
								<input type="checkbox" class='radio_btn' style='cursor:pointer' name="check_data_fechamento" id="check_data_fechamento" value='t' <? if (trim($check_data_fechamento) == 't') echo "checked='checked'"; ?> />
								<label for="check_data_fechamento" style='cursor:pointer'>Com Data de Fechamento</label>
							</div>
							<div class='control-group'>
								<input type="checkbox" class='radio_btn' style='cursor:pointer' name="check_defeito_constatado" id="check_defeito_constatado" value='t' <? if (trim($check_defeito_constatado) == 't') echo "checked='checked'"; ?> />
								<label for="check_defeito_constatado" style='cursor:pointer'>Com Defeito Constatado</label>
							</div>
						</div>
						<div class='span2'></div>
					</div>	
							
				
						<?php

					}?>

		<?php } ?>
				<br>
				<input type='hidden' name='btn_acao' value=''>
				<input class='btn' type='button' onclick="javascript: if ( document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='Pesquisar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " value='Pesquisar'>

<br />
<br />

</form><?php

if (strlen($btn_acao) > 0 AND (strlen($msg_erro) == 0 OR strlen($msg_erro_motivo) > 0)) {



	if (in_array($login_fabrica,$fabricas_interacao)) { ?>
	<div style="width:1200px;text-align:center;margin: 0 auto">
		<div class="status_checkpoint" style="background-color:<?php echo '#FFCC00';?>;float:left"></div>
		<div style="float:left"><b>Admin Interagiu</b></div>
		<div style="clear:both"></div>
		<div class="status_checkpoint" style="background-color:<?php echo '#669900';?>;float:left"></div>
		<div style="float:left"><b>Posto Interagiu</b></div>
	</div>

	<?}


	$posto_codigo = trim($_POST["posto_codigo"]);

	if (strlen($posto_codigo) > 0) {

		$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$posto_codigo' and fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);

		$posto = pg_result($res, 0, 0);

		$sql_add .= " AND tbl_os.posto = '$posto' ";

	}

	if ( in_array($login_fabrica, array(11,172)) ) {

		$sql_data = " AND tbl_os.data_digitacao > '2009-10-01 00:00:00' ";

		if (!empty($check_defeito_constatado)) {
			$sql_defeito_constatado = " AND tbl_os.defeito_constatado IS NOT NULL";
		}

		if (!empty($check_data_fechamento)) {
			$sql_data_fechamento = " AND  tbl_os.data_fechamento IS NOT NULL";
		}

	}


	if ($login_fabrica == 24) {

		$adminAuditor = " 
						  (SELECT tbl_os_status.data 
						   FROM tbl_os_status
						   WHERE tbl_os_status.os = tbl_os.os
						   ORDER BY tbl_os_status DESC LIMIT 1) AS data_os_status,
						  (SELECT tbl_admin.nome_completo 
						   FROM tbl_os_status
						   LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_os_status.admin)
						   WHERE tbl_os_status.os = tbl_os.os
						   ORDER BY tbl_os_status DESC LIMIT 1) AS nome_admin, ";

		$groupAuditor = " data_os_status, nome_admin, ";

		if (strlen($tipoData) > 0 and !empty($aux_data_inicial) and !empty($aux_data_final)) {

			$tipos = [ 'abertura'  => 'tbl_os.data_abertura',
	    			   'digitacao' => 'tbl_os.data_digitacao',
	    			   'liberada_auditoria' => 'tbl_os_status.data' ];

    		$sql_data2 .=  " AND " . $tipos[$tipoData] . " BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";

    		if ($tipoData == 'liberada_auditoria') {

    			$joinAuditor  = " JOIN tbl_os_status ON (tbl_os_status.os = tbl_os.os) ";
    			$sql_data2 .= " AND tbl_os_status.status_os = 19 ";
    		}
    		
    		if ($tipoData != 'liberada_auditoria' && $aprova == 'aprovadas') {

    			#$sql_data2 .= " AND status_os = 19 "; 
    		} 
    	}

	} else {

		if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0) {

			$sql_data2 .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";

		} else {

			if ($aprova == 'reincidentes_cinco_dias') {//HD 415029

				$sql = "select current_timestamp::date - 5 as data;";
				$res = pg_query($con, $sql);

				$data_cinco_dias = pg_result($res, 0, 0);

				$sql_data2     .= " AND tbl_os_status.data >= '$data_cinco_dias 00:00:00' ";
				$join_os_status = " JOIN tbl_os_status on (tbl_os.os = tbl_os_status.os and tbl_os.fabrica = tbl_os_status.fabrica_status) ";

			}

		}
	}

	$sql = "SELECT interv.os, interv.ultima_data
			INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT	ultima.os,
						(
							SELECT status_os
							FROM tbl_os_status
							JOIN tbl_os USING(os)
							".(($aprova == "reprovadas") ? " LEFT JOIN tbl_os_excluida ON tbl_os.os = tbl_os_excluida.os " : "")."
							WHERE status_os IN ($sql_tipo)
							AND tbl_os_status.os = ultima.os
							".(($aprova == "reprovadas") ? " AND (tbl_os_status.fabrica_status = tbl_os.fabrica OR tbl_os_status.fabrica_status = tbl_os_excluida.fabrica) " : " AND tbl_os_status.fabrica_status = tbl_os.fabrica ")."
							".(($aprova == "reprovadas") ? " AND (tbl_os.fabrica = $login_fabrica OR tbl_os_excluida.fabrica = $login_fabrica) " : " AND tbl_os.fabrica = $login_fabrica ")."
							AND tbl_os.os_reincidente IS TRUE
							AND tbl_os_status.extrato IS NULL
							$sql_add2
							$sql_add
							$sql_data
							$sql_data2
							$sql_data_fechamento
							$sql_defeito_constatado
							$Xos
							ORDER BY os_status DESC
							LIMIT 1
						) AS ultimo_status,
						(
							SELECT tbl_os_status.data
							FROM tbl_os_status
							JOIN tbl_os USING(os)
							".(($aprova == "reprovadas") ? " LEFT JOIN tbl_os_excluida ON tbl_os.os = tbl_os_excluida.os " : "")."
							WHERE status_os IN ($sql_tipo)
							AND tbl_os_status.os = ultima.os
							".(($aprova == "reprovadas") ? " AND (tbl_os_status.fabrica_status = tbl_os.fabrica OR tbl_os_status.fabrica_status = tbl_os_excluida.fabrica) " : " AND tbl_os_status.fabrica_status = tbl_os.fabrica ")."
							".(($aprova == "reprovadas") ? " AND (tbl_os.fabrica = $login_fabrica OR tbl_os_excluida.fabrica = $login_fabrica) " : " AND tbl_os.fabrica = $login_fabrica ")."
							AND tbl_os.os_reincidente IS TRUE
							AND tbl_os_status.extrato IS NULL
							$sql_add2
							$sql_add
							$sql_data
							$sql_data2
							$sql_data_fechamento
							$sql_defeito_constatado
							$Xos
							ORDER BY os_status DESC
							LIMIT 1
						) AS ultima_data

				FROM (
						SELECT DISTINCT tbl_os.os
						FROM tbl_os_status
						JOIN tbl_os USING(os)
						".(($aprova == "reprovadas") ? " LEFT JOIN tbl_os_excluida ON tbl_os.os = tbl_os_excluida.os " : "")."
						WHERE status_os IN ($sql_tipo)
						".(($aprova == "reprovadas") ? " AND (tbl_os_status.fabrica_status = tbl_os.fabrica OR tbl_os_status.fabrica_status = tbl_os_excluida.fabrica) " : " AND tbl_os_status.fabrica_status = tbl_os.fabrica ")."
							".(($aprova == "reprovadas") ? " AND (tbl_os.fabrica = $login_fabrica OR tbl_os_excluida.fabrica = $login_fabrica) " : " AND tbl_os.fabrica = $login_fabrica ")."
						AND tbl_os.os_reincidente IS TRUE
						$sql_add
						$sql_data
						$sql_data2
						$sql_data_fechamento
						$sql_defeito_constatado
						$Xos
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao);

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			SELECT	tbl_os.os                                                   ,
					to_char(X.ultima_data,'DD/MM/YYYY') AS data_status                                      ,
					{$adminAuditor}
					tbl_os.serie                                                ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.revenda_nome                                         ,
					tbl_os.consumidor_revenda                                   ,
					tbl_os.consumidor_fone                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.nota_fiscal                                          ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
					tbl_os.fabrica                                              ,
					tbl_os.posto                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto.estado                   AS posto_estado          ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					tbl_os_extra.os_reincidente                                 ,
					(select  array_to_string(array_agg(os_status || '|||'||status_os), ',') from tbl_os_status where tbl_os_status.os = tbl_os.os) as status_os_array,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_descricao,
					tbl_os.obs_reincidencia                                    ,
					(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
					(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
					".(($aprova == "reprovadas") ? ", CASE WHEN tbl_os_excluida.os IS NOT NULL THEN TRUE ELSE FALSE END AS os_excluida " : "");

	if ($login_fabrica == 52) {#HD 253575 - INICIO

		$sql .= " , tbl_cliente_admin.nome            AS cliente_admin        ,
				tbl_cliente_admin.codigo              AS codigo_cliente_admin ,
				tbl_motivo_reincidencia.descricao     AS motivo_reincidencia  ";

	}#HD 253575 - FIM

	$sql .= " FROM tmp_interv_$login_admin X
				JOIN tbl_os            ON tbl_os.os           = X.os
				JOIN tbl_os_extra      ON tbl_os.os           = tbl_os_extra.os
				{$joinAuditor}
				".(($aprova == "reprovadas") ? " LEFT JOIN tbl_os_excluida ON tbl_os.os = tbl_os_excluida.os " : "")."
				$join_os_status
				JOIN tbl_produto       ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto         ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_os.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";

	if ($login_fabrica == 52) { #HD 253575 - INICIO

		$sql .= " LEFT JOIN tbl_cliente_admin       ON (tbl_os.cliente_admin = tbl_cliente_admin.cliente_admin)
					LEFT JOIN tbl_motivo_reincidencia ON (tbl_os.motivo_reincidencia = tbl_motivo_reincidencia.motivo_reincidencia and tbl_os.fabrica = tbl_motivo_reincidencia.fabrica) ";

	}#HD 253575 - FIM

	$sql .= " WHERE ".(($aprova == "reprovadas") ? " (tbl_os.fabrica = $login_fabrica OR tbl_os_excluida.fabrica = $login_fabrica) " : " tbl_os.fabrica = $login_fabrica ");

	if ($login_fabrica <> 24 and $login_fabrica <> 52 and ($login_fabrica <> 91 and $aprovacao <> "aprovada_sem_pagamento")) {
		$sql .=	" AND  tbl_os_extra.extrato IS NULL ";
	} else {
		$sql .= "$sql_filtro_estado";
	}

	if ($login_fabrica == 14) {
		$sql .= " AND  tbl_os.data_fechamento IS NOT NULL";
	}

	if ($login_fabrica == 120 or $login_fabrica == 201) {
		$sql .= " AND  tbl_os.cancelada is not true";
	}


	if ($login_fabrica == 52 and $cook_cliente_admin) {//HD 253575 - inicio
		$sql .= " AND tbl_os.cliente_admin = $cook_cliente_admin";
	}

	if ($login_fabrica == 52 and $_POST['cliente_admin']) {
		$sql .= " AND tbl_os.cliente_admin = $cliente_admin " ;
	}//HD 253575 - fim

	if($login_fabrica == 52 AND $aprova == "aprovacao"){
		$sql .= " AND tbl_os_extra.extrato ISNULL";
	}

	$sql .= " $sql_add
			  $sql_data
			  $sql_data2 ";

							$sql .= " GROUP BY tbl_os.os,
								X.ultima_data,
						tbl_os.serie ,
						tbl_os.sua_os ,
						tbl_os.consumidor_nome ,
						tbl_os.revenda_nome ,
						tbl_os.consumidor_revenda ,
						tbl_os.consumidor_fone ,
						tbl_os.data_abertura,
						tbl_os.data_fechamento,
						tbl_os.data_digitacao,
						tbl_os.nota_fiscal ,
						tbl_os.data_nf,
						tbl_os.fabrica ,
						tbl_os.posto ,
						tbl_os.consumidor_nome ,
						tbl_posto.nome ,
						tbl_posto.estado,
						tbl_posto_fabrica.codigo_posto ,
						tbl_posto_fabrica.contato_email ,
						tbl_produto.referencia ,
						tbl_produto.descricao ,
						tbl_produto.voltagem ,
						tbl_os_extra.os_reincidente ,
						tbl_os.obs_reincidencia,
						{$groupAuditor}
						tbl_os.defeito_constatado,
						tbl_os.defeito_reclamado ";

	if ($aprova == "reprovadas") {
		$sql .= " , os_excluida, tbl_os_excluida.os ";
	}

	if ($login_fabrica == 52) {#HD 253575 - INICIO

		$sql .= " , tbl_cliente_admin.nome             ,
					tbl_cliente_admin.codigo           ,
					tbl_motivo_reincidencia.descricao  ";

	}#HD 253575 - FIM

	$sql .= " ORDER BY tbl_posto.nome, status_observacao, tbl_os.os";
 	#echo nl2br($sql);
	$res  = pg_query($con,$sql);
	if (pg_numrows($res) > 0) {
		$rows_res = pg_num_rows($res);
		$rows = pg_num_rows($res);

		if (in_array($login_fabrica, [24,52,91])) {#HD 253575 -  XLS PARA FRICON - INICIO

			$data = date("d-m-Y-H-i");

			$arquivo_nome_c = "os_reincidentes-$login_fabrica-$data.xls";
			//$path           = "/www/assist/www/admin/xls/";
			$path = "xls/";
			$path_tmp       = "/tmp/assist/";

			if (!is_dir($path_tmp)) {//HD 676626
				mkdir($path_tmp);
				chmod($path_tmp, 0777);
			}

			$arquivo_completo     = $path.$arquivo_nome_c;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome_c;

			echo `rm $arquivo_completo.zip `;
			echo `rm $arquivo_completo `;
			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo_tmp.zip `;

			$fp = fopen ($arquivo_completo_tmp,"w+");

			fputs ($fp,"<html>");
				fputs ($fp,"<head>");

					fputs ($fp,"<title>OS's REINCIDENTES</title>");
					fputs ($fp,"<meta content=\"text/html; charset=iso-8859-1\">");

				fputs ($fp,"</head>");

				fputs ($fp,"<body>");

					fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='1'>");

						fputs ($fp,"<tr>");

							if ($login_fabrica == 52) {
								fputs ($fp,"<TD align='center'><b>&nbsp</b></TD>");
								fputs ($fp,"<TD align='center'><b>OS</b></TD>");
								fputs ($fp,"<TD align='center'><b>Série</b></TD>");
								fputs ($fp,"<TD align='center'><b>Data Abertura</b></TD>");
								fputs ($fp,"<TD align='center'><b>Data Fechamento</b></TD>");
								fputs ($fp,"<TD align='center'><b>Posto</b></TD>");
								fputs ($fp,"<TD align='center'><b>Nota Fiscal</b></TD>");
								fputs ($fp,"<TD align='center'><b>Consumidor</b></TD>");
								fputs ($fp,"<TD align='center'><b>Produto</b></TD>");
								fputs ($fp,"<TD align='center'><b>Defeito Constatado</b></TD>");
								fputs ($fp,"<TD align='center'><b>Status</b></TD>");
								fputs ($fp,"<TD align='center'><b>Admin</b></TD>");
								fputs ($fp,"<TD align='center'><b>Motivo Reincidência</b></TD>");
								fputs ($fp,"<TD align='center'><b>Motivo do Posto</b></TD>");
							}

							if (in_array($login_fabrica, [24, 91])) {

								fputs ($fp,"<TD align='center'><b>&nbsp</b></TD>");
								fputs ($fp,"<TD align='center'><b>OS</b></TD>");
								
								if ($login_fabrica == 91) {
									fputs ($fp,"<TD align='center'><b>Tipo</b></TD>");
								}

								fputs ($fp,"<TD align='center'><b>Série</b></TD>");
								fputs ($fp,"<TD align='center'><b>Data Abertura</b></TD>");
								fputs ($fp,"<TD align='center'><b>Data Fechamento</b></TD>");

								if($login_fabrica == 24 AND in_array($aprova,["aprovadas","reprovadas"])){
									fputs ($fp,"<TD align='center'><b>Data Aprovação</b></TD>");
								}

								fputs ($fp,"<TD align='center'><b>Posto</b></TD>");
								fputs ($fp,"<TD align='center'><b>UF</b></TD>");
								fputs ($fp,"<TD align='center'><b>Nota Fiscal</b></TD>");
								fputs ($fp,"<TD align='center'><b>Consumidor</b></TD>");
								fputs ($fp,"<TD align='center'><b>Produto</b></TD>");
								fputs ($fp,"<TD align='center'><b>Defeito Constatado</b></TD>");
								fputs ($fp,"<TD align='center'><b>Status</b></TD>");
								fputs ($fp,"<TD align='center'><b>Motivo Reincidência</b></TD>");

								if ($login_fabrica == 24) {

									fputs ($fp,"<TD align='center'><b>Auditada pelo Admin</b></TD>");
								}
							}

						fputs ($fp,"</tr>");

		}#HD 253575 -  XLS PARA FRICON

		echo "</div><BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial' 				value='$data_inicial' />";
		echo "<input type='hidden' name='data_final'   				value='$data_final' />";
		echo "<input type='hidden' name='aprova'       				value='$aprova' />";
		echo "<input type='hidden' name='posto_codigo'				value='$posto_codigo' />";
		echo "<input type='hidden' name='filtro_check_estado'		value='$filtro_check_estado' />";
		echo "<input type='hidden' name='filtro_pesquisa_estado'	value='$filtro_pesquisa_estado' />";
		echo "<input type='hidden' name='posto_nome'   				value='$posto_nome' />";
		echo "<input type='hidden' name='check_data_fechamento'  	value='$check_data_fechamento' />";
		echo "<input type='hidden' name='check_defeito_constatado'	value='$check_defeito_constatado' />";

		if ($login_fabrica == 91 && pg_num_rows($res) > 500) {
			echo "<h2 style='color: #FF0000; font-size: 14px;'>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h2>";
			$rows = 500;
		}

		echo "<table class='table table-bordered table-large'>";

		$colspan_fricon = ($login_fabrica == 52) ? "16" : "13";
		if ($login_fabrica == 104 or $login_fabrica == 105) $colspan_fricon = 14;

        if (in_array($login_fabrica, [24,91])) {
            
            $colspan_fricon = '16';
        }

		echo "<tr class='titulo_tabela'>";
			echo "<th colspan='$colspan_fricon'>";
				echo "Este relatório considera a data de digitação da OS";
			echo "</th>";
		echo "</tr>";

		echo "<tr name='tr_titulo' class='titulo_coluna'>";

		if ($login_fabrica == 52) { #HD 253575 - INICIO

			if (!$cook_cliente_admin) {
				echo "<th><label>Todos<input type='checkbox' onclick='checkaTodos()' /></label></th>";
			} else {
				echo "<th>&nbsp;</th>";
			}

		} else {
			echo "<th><label>Todos<input type='checkbox' onclick='checkaTodos()' /></label></th>";
		}#HD 253575 - FIM

		echo "<th>OS</th>";

        if ($login_fabrica == 91) {
            echo "<th>Tipo</th>";
        }

		if ($login_fabrica != 104 and $login_fabrica != 105) { echo "<th>Série</th>"; }
		echo "<th>Data Abertura</th>";
		if ($login_fabrica != 104 and $login_fabrica != 105) { echo "<th>Data Fechamento</th>"; }

		if($login_fabrica == 24){
			if($aprova == "aprovadas"){
				echo "<th>Data Aprovação</th>";
			}else if($aprova == "reprovadas"){
				echo "<th>Data Reprovação</th>";
			}
		}

		echo "<th width='20'>Posto</th>";
		echo "<th width='20'>UF</th>";
		echo "<th>Nota Fiscal</th>";
		echo "<th>Consum.</th>";
		echo "<th>Produto</th>";
		echo "<th>Defeito Constatado</th>";
		echo "<th>Status</th>";
		echo ($login_fabrica == 52) ? " <th> Cliente Fricon </th> <th>Motivo Reincidência</th>" : null;
		echo "<th>Motivo do Posto</th>";

		if ($login_fabrica == 24) {

			echo "<th>Auditada pelo Admin</th>";
		}

		if (in_array($login_fabrica,$fabricas_interacao)) {

			if ($login_fabrica == 52) {#HD 253575 - INICIO

				if (!$cook_cliente_admin) {
					echo "<th>Interação</th>";
				}

			} else if ($login_fabrica != 94) {

				echo "<th>Interação</th>";

			}#HD 253575 - FIM

		}

		if($login_fabrica == 126){
			echo "<th>Ação</th>";
		}

		echo "</tr>";

		$cores            = '';
		$qtde_intervencao = 0;

		for ($x = 0; $x < $rows; $x++) {

			$os						= pg_result($res, $x, 'os');
			$serie					= pg_result($res, $x, 'serie');
			$data_abertura			= pg_result($res, $x, 'data_abertura');
			$data_fechamento		= pg_result($res, $x, 'data_fechamento');
			$data_status			= pg_result($res, $x, 'data_status');
			$sua_os					= pg_result($res, $x, 'sua_os');
			$codigo_posto			= pg_result($res, $x, 'codigo_posto');
			$posto					= pg_result($res, $x, 'posto');
			$posto_nome				= pg_result($res, $x, 'posto_nome');
			$posto_email			= pg_result($res, $x, 'posto_email');
			$posto_estado			= pg_result($res, $x, 'posto_estado');
			$nota_fiscal			= pg_result($res, $x, 'nota_fiscal');
			$data_nf				= pg_result($res, $x, 'data_nf');
			$consumidor_nome		= pg_result($res, $x, 'consumidor_nome');
			$consumidor_revenda     = pg_result($res, $x, 'consumidor_revenda');
			$revenda_nome           = pg_result($res, $x, 'revenda_nome');
			$consumidor_fone		= pg_result($res, $x, 'consumidor_fone');
			$produto_referencia		= pg_result($res, $x, 'produto_referencia');
			$produto_descricao		= pg_result($res, $x, 'produto_descricao');
			$produto_voltagem		= pg_result($res, $x, 'voltagem');
			$data_digitacao			= pg_result($res, $x, 'data_digitacao');
			$data_abertura			= pg_result($res, $x, 'data_abertura');
			$status_os				= pg_result($res, $x, 'status_os');
			$status_observacao		= pg_result($res, $x, 'status_observacao');
			$status_descricao		= pg_result($res, $x, 'status_descricao');
			$os_reincidente			= pg_result($res, $x, 'os_reincidente');
			$obs_reincidencia		= pg_result($res, $x, 'obs_reincidencia');
			$defeito_constatado		= pg_result($res, $x, 'defeito_constatado');
			$defeito_reclamado		= pg_result($res, $x, 'defeito_reclamado');
			$nome_admin				= pg_result($res, $x, 'nome_admin');

			if ($aprova == "reprovadas") {
				$os_excluida = pg_fetch_result($res, $x, "os_excluida");
			}

			if ($login_fabrica == 52) {#HD 253575 - INICIO

				$cliente_admin 	      = pg_fetch_result($res, $x, 'cliente_admin');
				$codigo_cliente_admin = pg_fetch_result($res, $x, 'codigo_cliente_admin');
				$motivo_reincidencia  = pg_fetch_result($res, $x, 'motivo_reincidencia');

			}#HD 253575 - FIM

			if($aprova == "aprovacao") {
                $statuss= array();
                $status_oss    = explode(",",pg_result($res, $x, 'status_os_array'));

                foreach($status_oss as $valor){
                    list($os_status,$status_os) = explode('|||',$valor);
                    $statuss[$os_status] = $status_os;
                }

                ksort($statuss);
				$verifica_aprovado = verificaTodasAuditoria($statuss);
				if($verifica_aprovado) continue;
			}

			if (strlen($os_reincidente) > 0) {

				$sql =  "SELECT	tbl_os.os                                                   ,
								tbl_os.serie                                                ,

								tbl_os.sua_os                                               ,
								tbl_os.consumidor_nome                                      ,
								tbl_os.revenda_nome                                         ,
								tbl_os.consumidor_revenda                                   ,
								tbl_os.consumidor_fone                                      ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
								TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
								TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
								tbl_os.nota_fiscal                                          ,
								TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
								tbl_os.fabrica                                              ,
								tbl_os.consumidor_nome                                      ,
								tbl_posto.nome                     AS posto_nome            ,
								tbl_posto.estado                                            ,
								tbl_posto_fabrica.codigo_posto                              ,
								tbl_posto_fabrica.contato_email       AS posto_email        ,
								tbl_produto.referencia             AS produto_referencia    ,
								tbl_produto.descricao              AS produto_descricao     ,
								tbl_produto.voltagem                                        ,
								tbl_os_extra.os_reincidente                                 ,
								(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
								(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
						FROM tbl_os
						JOIN tbl_os_extra             ON tbl_os.os = tbl_os_extra.os
						JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
						JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
						JOIN tbl_posto_fabrica        ON tbl_os.posto     = tbl_posto_fabrica.posto
						WHERE tbl_os.os = $os_reincidente
						AND tbl_os.fabrica = $login_fabrica
						LIMIT 1";

				$res_reinc = pg_exec($con, $sql);

				if ($login_admin == 1375) echo "<br />OS $os_reincidente, Núm. registros: " . pg_num_rows($res_reinc) . "<br />";

				if (pg_num_rows($res_reinc) > 0) { //HD 347704 INICIO

					$reinc_os					= pg_result($res_reinc, 0, 'os');
					$reinc_serie				= pg_result($res_reinc, 0, 'serie');
					$reinc_data_abertura		= pg_result($res_reinc, 0, 'data_abertura');
					$reinc_data_fechamento		= pg_result($res_reinc, 0, 'data_fechamento');
					$reinc_sua_os				= pg_result($res_reinc, 0, 'sua_os');
					$reinc_codigo_posto			= pg_result($res_reinc, 0, 'codigo_posto');
					$reinc_posto_nome			= pg_result($res_reinc, 0, 'posto_nome');
					$reinc_posto_estado			= pg_result($res_reinc, 0, 'estado');
					$reinc_posto_email			= pg_result($res_reinc, 0, 'posto_email');
					$reinc_nota_fiscal			= pg_result($res_reinc, 0, 'nota_fiscal');
					$reinc_data_nf				= pg_result($res_reinc, 0, 'data_nf');
					$reinc_consumidor_nome		= pg_result($res_reinc, 0, 'consumidor_nome');
					$reinc_revenda_nome		    = pg_result($res_reinc, 0, 'revenda_nome');
					$reinc_consumidor_revenda   = pg_result($res_reinc, 0, 'consumidor_revenda');
					$reinc_consumidor_fone		= pg_result($res_reinc, 0, 'consumidor_fone');
					$reinc_produto_referencia	= pg_result($res_reinc, 0, 'produto_referencia');
					$reinc_produto_descricao	= pg_result($res_reinc, 0, 'produto_descricao');
					$reinc_produto_voltagem		= pg_result($res_reinc, 0, 'voltagem');
					$reinc_data_digitacao		= pg_result($res_reinc, 0, 'data_digitacao');
					$reinc_data_abertura		= pg_result($res_reinc, 0, 'data_abertura');
					$reinc_defeito_constatado	= pg_result($res_reinc, 0, 'defeito_constatado');
					$reinc_defeito_reclamado	= pg_result($res_reinc, 0, 'defeito_reclamado');

				} //HD 347704 FIM

			}

			$cores++;

			if ( in_array($login_fabrica, array(11,172)) ) {
				$cor = ($cores % 2 == 0) ? "#E4DECD": '#E2E9F5';
			} else {
				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';
			}

			$sqlint = "SELECT os_interacao, admin from tbl_os_interacao WHERE os = $os ORDER BY os_interacao DESC limit 1";
			$resint = pg_exec($con, $sqlint);

			if (pg_num_rows($resint) > 0) {

				$admin = pg_result($resint, 0, 'admin');

				if (in_array($login_fabrica,$fabricas_interacao)) {
					if (strlen($admin) > 0) {
						$cor = "#FFCC00";
					} else {
						$cor = "#669900";
					}
				}
			}

			if (strlen($sua_os) == 0) $sua_os = $os;

			echo "<tr bgcolor='$cor' id='linha_$x'>";

			if ($login_fabrica == 52) {#HD 253575 - INICIO

				if (!$cook_cliente_admin) {

					echo "<td class='tac' align='center' width='0'>";

					if ($aprova == "aprovacao") {

						echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";

						if (strlen($msg_erro) > 0) {

							if (strlen($_POST["check_".$x]) > 0) {
								echo " CHECKED ";
							}

						}

						echo ">";

					}

					echo "</td>";

				} else {
					echo "<td>&nbsp;</td>";
				}

			} else {
				echo "<td class='tac'>";
                if (in_array($aprova,array("aprovacao","reincidentes_cinco_dias"))) {
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";

					if (strlen($msg_erro) > 0) {

						if (strlen($_POST["check_".$x]) > 0) {
							echo " CHECKED ";
						}

					}

					echo ">";

				}

				echo "</td>";

			} #HD 253575 _ FIM

			echo "<td class='tac'><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a> </td>";

            if ($login_fabrica == 91) {
                $cr = ['C' => 'Consumidor', 'R' => 'Revenda'];
                echo "<td class='tac'>{$cr[$consumidor_revenda]}</td>";
            }

			if ($login_fabrica != 104 and $login_fabrica != 105) { echo "<td>$serie</td>"; }
			echo "<td class='tac'>$data_abertura</td>";
			if ($login_fabrica != 104 and $login_fabrica != 105) { echo "<td>$data_fechamento </td>"; }

			if($login_fabrica == 24 AND $aprova == "aprovadas"){
				echo "<td>$data_status </td>";
			}

			echo "<td align='left' title='".$codigo_posto." - ".$posto_nome."'>".substr($posto_nome,0,20) ."...</td>";
			echo "<td class='tac'>$posto_estado</td>";
			echo "<td class='tac'>$nota_fiscal</td>";

			if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;//HD 119665

			if ($consumidor_revenda == 'R') {

				if (strlen($revenda_nome) == 0) {
					$revenda_nome = $consumidor_nome;
				}

			}

			echo "<td>$consumidor_nome</td>";
			echo "<td align='left' title='Produto: $produto_referencia - $produto_descricao' style='cursor:help'>".substr($produto_descricao,0,20)."</td>";
			echo "<td>$defeito_constatado</td>";
			echo "<td title='Observação: ".$status_observacao."'>".str_replace('CNPJ','CNPJ <BR>',$status_descricao). "</td>";

			if ($login_fabrica == 52) {
				echo "<td>$codigo_cliente_admin <br /> $cliente_admin</td>";
				echo "<td>$motivo_reincidencia</td>";
			}

			echo "<td title='$sua_os - Motivo: ".$obs_reincidencia."'>".substr($obs_reincidencia,0,50). "</td>";

			if ($login_fabrica == 24) {
				
				echo "<td>$nome_admin</td>";
			}

			if (in_array($login_fabrica,$fabricas_interacao)) {

				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao WHERE os = $os ORDER BY os_interacao DESC limit 1";
				$resint = pg_exec($con,$sqlint);

				$onclick = "onclick='abreInteracao($x, $os, \"Mostrar\", \"$posto\");'";

				if (pg_num_rows($resint) == 0) {

					$botao = "<input type='button' class='btn btn-primary' value='Interagir' $onclick title='Enviar Interação com Posto'>";

				} else {

					$admin = pg_result($resint, 0, 'admin');

					if (strlen($admin) > 0) {
						$botao = "<input type='button' class='btn btn-primary' value='Interagir' $onclick title='Aguardando Resposta do Posto'>";
					} else {
						$botao = "<input type='button' class='btn btn-primary' value='Interagir' $onclick title='Posto Respondeu, clique aqui para visualizar'>";
					}

				}

				if ($login_fabrica == 52) {

					if (!$cook_cliente_admin) {
						echo "<td><div id=btn_interacao_".$x.">$botao</div></td>";
					}

				} else if ($login_fabrica != 94) {
					if ($login_fabrica == 91 && $os_excluida == "t") {
						echo "<td><b style='color: #FF0000;'>OS EXCLUIDA</b></td>";
					} else {
						echo "<td><div id=btn_interacao_".$x.">$botao</div></td>";
					}
				}

			}

			if($login_fabrica == 126){
				echo "<td> <input class='btn btn-primary' type='button' value='Trocar' onclick=\"window.open('os_cadastro.php?os={$os}&osacao=trocar')\" /> </td>";
			}
			echo "</tr>";

			$colspan_fricon = ($login_fabrica == 24 || $login_fabrica == 52 || $login_fabrica == 91) ? "16" : "13";
			if ($login_fabrica == 104 or $login_fabrica == 105) $colspan_fricon = 14;

			if (!$cook_cliente_admin) {
				$colspan_fricon_reinc = ($login_fabrica == 52) ? "5" : "4";
			} else {
				$colspan_fricon_reinc = ($login_fabrica == 52) ? "5" : "4";
			}

			if ($login_fabrica == 52) {

				if (!$cook_cliente_admin) {

					echo "<tr>";
						echo "<td colspan='$colspan_fricon'>
								<div id='loading_$x' style='display: none;'><img src='imagens/ajax-loader.gif' /></div>
								<div id='gravado_$x' style='font-size: 14px; background-color: #669900; color: #FFFFFF; font-weight: bold; display: none;'>Interação gravada</div>
								<div id='interacao_".$x."' style='display: none;'></div>
							  </td>";
					echo "</tr>";

				}

			} else if ($login_fabrica != 94) {

				echo "<tr >";
					echo "<td colspan='$colspan_fricon'>
							<div id='loading_$x' style='display: none;'><img src='imagens/ajax-loader.gif' /></div>
							<div id='gravado_$x' style='font-size: 14px; background-color: #669900; color: #FFFFFF; font-weight: bold; display: none;'>Interação gravada</div>
							<div id='interacao_".$x."' style='display: none;'></div>
						  </td>";
				echo "</tr>";

			}

			/* ---------------- OS REINCIDENTE -------------------*/

			echo "<tr bgcolor='$cor'>";
			echo "<td class='tac' align='center' width='0'>Reinc.</td>";
			echo "<td class='tac'>$reinc_sua_os</a></td>";
            if ($login_fabrica == 91) {
                echo "<td class='tac'>{$cr[$reinc_consumidor_revenda]}</td>";
            }
			if ($login_fabrica != 104 and $login_fabrica != 105) { echo "<td>$reinc_serie</td>"; }
			echo "<td class='tac'>$reinc_data_abertura</td>";
			if ($login_fabrica != 104 and $login_fabrica != 105) { echo "<td>$reinc_data_fechamento </td>"; }

			if($login_fabrica == 24 AND in_array($aprova,["aprovadas","reprovadas"])){
				echo "<td class='tac'></td>";
			}

			echo "<td align='left'>".substr($reinc_posto_nome,0,20) ."...</td>";
			echo "<td class='tac'>$reinc_posto_estado</td>";
			echo "<td class='tac'>$reinc_nota_fiscal</td>";

			if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;//HD 119665

			if ($reinc_consumidor_revenda == 'R') {

				if (strlen($reinc_revenda_nome) == 0) {
					$reinc_revenda_nome = $reinc_consumidor_nome;
				}

			}

			echo "<td>$reinc_consumidor_nome</td>";
			echo "<td align='left' style='cursor:help'>$reinc_produto_referencia - ".substr($reinc_produto_descricao ,0,20)."</td>";
			echo "<td>";
			echo $reinc_defeito_constatado;
			echo "</td>";
			echo "<td title='Observação: ".$reinc_status_observacao."' colspan='$colspan_fricon_reinc'>".$reinc_status_descricao. "</td>";
			echo "</tr>";

		}

		for ($x = 0; $x < $rows_res; $x++) {

			$os						= pg_result($res, $x, 'os');
			$serie					= pg_result($res, $x, 'serie');
			$data_abertura			= pg_result($res, $x, 'data_abertura');
			$data_fechamento		= pg_result($res, $x, 'data_fechamento');
			$data_stadus			= pg_result($res,$x, 'data_status');
			$sua_os					= pg_result($res, $x, 'sua_os');
			$codigo_posto			= pg_result($res, $x, 'codigo_posto');
			$posto					= pg_result($res, $x, 'posto');
			$posto_nome				= pg_result($res, $x, 'posto_nome');
			$posto_email			= pg_result($res, $x, 'posto_email');
			$posto_estado			= pg_result($res, $x, 'posto_estado');
			$nota_fiscal			= pg_result($res, $x, 'nota_fiscal');
			$data_nf				= pg_result($res, $x, 'data_nf');
			$consumidor_nome		= pg_result($res, $x, 'consumidor_nome');
			$consumidor_revenda     = pg_result($res, $x, 'consumidor_revenda');
			$revenda_nome           = pg_result($res, $x, 'revenda_nome');
			$consumidor_fone		= pg_result($res, $x, 'consumidor_fone');
			$produto_referencia		= pg_result($res, $x, 'produto_referencia');
			$produto_descricao		= pg_result($res, $x, 'produto_descricao');
			$produto_voltagem		= pg_result($res, $x, 'voltagem');
			$data_digitacao			= pg_result($res, $x, 'data_digitacao');
			$data_abertura			= pg_result($res, $x, 'data_abertura');
			$status_os				= pg_result($res, $x, 'status_os');
			$status_observacao		= pg_result($res, $x, 'status_observacao');
			$status_descricao		= pg_result($res, $x, 'status_descricao');
			$os_reincidente			= pg_result($res, $x, 'os_reincidente');
			$obs_reincidencia		= pg_result($res, $x, 'obs_reincidencia');
			$defeito_constatado		= pg_result($res, $x, 'defeito_constatado');
			$defeito_reclamado		= pg_result($res, $x, 'defeito_reclamado');
			$nome_admin  			= pg_result($res, $x, 'nome_admin');

			if ($login_fabrica == 52) {#HD 253575 - INICIO

				$cliente_admin 	      = pg_fetch_result($res, $x, 'cliente_admin');
				$codigo_cliente_admin = pg_fetch_result($res, $x, 'codigo_cliente_admin');
				$motivo_reincidencia  = pg_fetch_result($res, $x, 'motivo_reincidencia');

			}#HD 253575 - FIM

			if (strlen($os_reincidente) > 0) {

				$sql =  "SELECT	tbl_os.os                                                   ,
								tbl_os.serie                                                ,

								tbl_os.sua_os                                               ,
								tbl_os.consumidor_nome                                      ,
								tbl_os.revenda_nome                                         ,
								tbl_os.consumidor_revenda                                   ,
								tbl_os.consumidor_fone                                      ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
								TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
								TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
								tbl_os.nota_fiscal                                          ,
								TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
								tbl_os.fabrica                                              ,
								tbl_os.consumidor_nome                                      ,
								tbl_posto.nome                     AS posto_nome            ,
								tbl_posto.estado                                            ,
								tbl_posto_fabrica.codigo_posto                              ,
								tbl_posto_fabrica.contato_email       AS posto_email        ,
								tbl_produto.referencia             AS produto_referencia    ,
								tbl_produto.descricao              AS produto_descricao     ,
								tbl_produto.voltagem                                        ,
								tbl_os_extra.os_reincidente                                 ,
								(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
								(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
						FROM tbl_os
						JOIN tbl_os_extra             ON tbl_os.os = tbl_os_extra.os
						JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
						JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
						JOIN tbl_posto_fabrica        ON tbl_os.posto     = tbl_posto_fabrica.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os_reincidente
						LIMIT 1";

				$res_reinc = pg_exec($con, $sql);

				if (pg_num_rows($res_reinc) > 0) { //HD 347704 INICIO

					$reinc_os					= pg_result($res_reinc, 0, 'os');
					$reinc_serie				= pg_result($res_reinc, 0, 'serie');
					$reinc_data_abertura		= pg_result($res_reinc, 0, 'data_abertura');
					$reinc_data_fechamento		= pg_result($res_reinc, 0, 'data_fechamento');
					$reinc_sua_os				= pg_result($res_reinc, 0, 'sua_os');
					$reinc_codigo_posto			= pg_result($res_reinc, 0, 'codigo_posto');
					$reinc_posto_nome			= pg_result($res_reinc, 0, 'posto_nome');
					$reinc_posto_estado			= pg_result($res_reinc, 0, 'estado');
					$reinc_posto_email			= pg_result($res_reinc, 0, 'posto_email');
					$reinc_nota_fiscal			= pg_result($res_reinc, 0, 'nota_fiscal');
					$reinc_data_nf				= pg_result($res_reinc, 0, 'data_nf');
					$reinc_consumidor_nome		= pg_result($res_reinc, 0, 'consumidor_nome');
					$reinc_revenda_nome		    = pg_result($res_reinc, 0, 'revenda_nome');
					$reinc_consumidor_revenda   = pg_result($res_reinc, 0, 'consumidor_revenda');
					$reinc_consumidor_fone		= pg_result($res_reinc, 0, 'consumidor_fone');
					$reinc_produto_referencia	= pg_result($res_reinc, 0, 'produto_referencia');
					$reinc_produto_descricao	= pg_result($res_reinc, 0, 'produto_descricao');
					$reinc_produto_voltagem		= pg_result($res_reinc, 0, 'voltagem');
					$reinc_data_digitacao		= pg_result($res_reinc, 0, 'data_digitacao');
					$reinc_data_abertura		= pg_result($res_reinc, 0, 'data_abertura');
					$reinc_defeito_constatado	= pg_result($res_reinc, 0, 'defeito_constatado');
					$reinc_defeito_reclamado	= pg_result($res_reinc, 0, 'defeito_reclamado');

				} //HD 347704 FIM

			}

			$cores++;

			if ( in_array($login_fabrica, array(11,172)) ) {
				$cor = ($cores % 2 == 0) ? "#E4DECD": '#E2E9F5';
			} else {
				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';
			}

			$sqlint = "SELECT os_interacao, admin from tbl_os_interacao WHERE os = $os ORDER BY os_interacao DESC limit 1";
			$resint = pg_exec($con, $sqlint);

			if (pg_num_rows($resint) > 0) {

				$admin = pg_result($resint, 0, 'admin');

				if (in_array($login_fabrica,$fabricas_interacao)) {
					if (strlen($admin) > 0) {
						$cor = "#FFCC00";
					} else {
						$cor = "#669900";
					}
				}
			}

			if (in_array($login_fabrica, [24,52,91])) { #HD 253575 - XLS - PARA OS Ñ REINCIDENTES

				fputs ($fp,"<tr bgcolor='$cor'>");
					if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;

					if ($consumidor_revenda == 'R') {

						if (strlen($revenda_nome) == 0) {
							$revenda_nome = $consumidor_nome;
						}

					}


					if ($login_fabrica == 52) {
						fputs ($fp,"<TD ><b>&nbsp</b></TD>");
						fputs ($fp,"<TD class='tac'><b>$sua_os</b></TD>");
						fputs ($fp,"<TD ><b>$serie</b></TD>");
						fputs ($fp,"<TD class='tac'><b>$data_abertura</b></TD>");
						fputs ($fp,"<TD class='tac'><b>$data_fechamento</b></TD>");
						fputs ($fp,"<TD ><b>$posto_nome</b></TD>");
						fputs ($fp,"<TD class='tac'><b>$nota_fiscal</b></TD>");
						fputs ($fp,"<TD ><b>$consumidor_nome</b></TD>");
						fputs ($fp,"<TD ><b>$produto_descricao</b></TD>");
						fputs ($fp,"<TD ><b>$defeito_constatado</b></TD>");
						fputs ($fp,"<TD ><b>$status_descricao</b></TD>");
						fputs ($fp,"<TD ><b>$cliente_admin</b></TD>");
						fputs ($fp,"<TD ><b>$motivo_reincidencia</b></TD>");
						fputs ($fp,"<TD ><b>$obs_reincidencia</b></TD>");
					}

					if (in_array($login_fabrica, [24,91])) {
						fputs ($fp,"<TD ><b>&nbsp</b></TD>");
						fputs ($fp,"<TD ><b>$sua_os</b></TD>");
						if ($login_fabrica == 91) {
							fputs ($fp,"<TD ><b>{$cr[$consumidor_revenda]}</b></TD>");
						}
						fputs ($fp,"<TD ><b>$serie</b></TD>");
						fputs ($fp,"<TD ><b>$data_abertura</b></TD>");
						fputs ($fp,"<TD ><b>$data_fechamento</b></TD>");

						if($login_fabrica == 24 AND in_array($aprova,["aprovadas","reprovadas"])){
							fputs ($fp,"<TD ><b>$data_status</b></TD>");
						}

						fputs ($fp,"<TD ><b>$posto_nome</b></TD>");
						fputs ($fp,"<TD ><b>$posto_estado</b></TD>");
						fputs ($fp,"<TD ><b>$nota_fiscal</b></TD>");
						fputs ($fp,"<TD ><b>$consumidor_nome</b></TD>");
						fputs ($fp,"<TD ><b>$produto_descricao</b></TD>");
						fputs ($fp,"<TD ><b>$defeito_constatado</b></TD>");
						fputs ($fp,"<TD ><b>$status_descricao</b></TD>");
						fputs ($fp,"<TD ><b>$obs_reincidencia</b></TD>");
						if ($login_fabrica == 24) {
							fputs ($fp,"<TD ><b>$nome_admin</b></TD>");
						}
					}

				fputs ($fp,"</tr >");

			}#HD 253575 - XLS

			if (strlen($sua_os) == 0) $sua_os = $os;

			if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;//HD 119665

			if ($consumidor_revenda == 'R') {

				if (strlen($revenda_nome) == 0) {
					$revenda_nome = $consumidor_nome;
				}

			}

			$colspan_fricon = ($login_fabrica == 52 || $login_fabrica == 91) ? "16" : "13";
			if ($login_fabrica == 104 or $login_fabrica == 105) $colspan_fricon = 14;
			if ($login_fabrica == 24) $colspan_fricon = "16";

			if (!$cook_cliente_admin) {
				$colspan_fricon_reinc = ($login_fabrica == 52) ? "5" : "3";
			} else {
				$colspan_fricon_reinc = ($login_fabrica == 52) ? "5" : "3";
			}

			/* ---------------- OS REINCIDENTE -------------------*/

			#HD 253575 - XLS - OS's REINCIDENTES - INICIO

			if (in_array($login_fabrica, [24,52,91])) {

				fputs ($fp,"<tr bgcolor='$cor'>");
					if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;
					if ($reinc_consumidor_revenda=='R'){
						if (strlen($reinc_revenda_nome) == 0){
							$reinc_revenda_nome = $reinc_consumidor_nome;
						}
					}

					if ($login_fabrica == 52) {
						fputs ($fp,"<TD><font color='#FF0000' >Reinc.</font></TD>");
						fputs ($fp,"<TD>$reinc_sua_os</TD>");
						fputs ($fp,"<TD>$reinc_serie</TD>");
						fputs ($fp,"<TD>$reinc_data_abertura</TD>");
						fputs ($fp,"<TD>$reinc_data_fechamento</TD>");
						fputs ($fp,"<TD>$reinc_posto_nome</TD>");
						fputs ($fp,"<TD>$reinc_nota_fiscal</TD>");
						fputs ($fp,"<TD>$reinc_consumidor_nome</TD>");
						fputs ($fp,"<TD>$reinc_produto_descricao</TD>");
						fputs ($fp,"<TD>$reinc_defeito_constatado</TD>");
						fputs ($fp,"<TD>$reinc_status_descricao</TD>");
					}

					if (in_array($login_fabrica, [24,91])) {
						fputs ($fp,"<TD><font color='#FF0000' >Reinc.</font></TD>");
						fputs ($fp,"<TD>$reinc_sua_os</TD>");
						
						if ($login_fabrica == 91) {
							fputs ($fp,"<TD>{$cr[$reinc_consumidor_revenda]}</TD>");
						}

						fputs ($fp,"<TD>$reinc_serie</TD>");
						fputs ($fp,"<TD>$reinc_data_abertura</TD>");
						fputs ($fp,"<TD>$reinc_data_fechamento</TD>");

						if($login_fabrica == 24 AND in_array($aprova,["aprovadas","reprovadas"])){ 
							fputs ($fp,"<TD></TD>");
						}
						fputs ($fp,"<TD>$reinc_posto_nome</TD>");
						fputs ($fp,"<TD>$reinc_posto_estado</TD>");
						fputs ($fp,"<TD>$reinc_nota_fiscal</TD>");
						fputs ($fp,"<TD>$reinc_consumidor_nome</TD>");
						fputs ($fp,"<TD>$reinc_produto_descricao</TD>");
						fputs ($fp,"<TD>$reinc_defeito_constatado</TD>");
						fputs ($fp,"<TD>$reinc_status_descricao</TD>");
					}

				fputs ($fp,"</tr>");

			}
			#HD 253575 - XLS - OS's REINCIDENTES - FIM

			if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;//HD 119665

			if ($reinc_consumidor_revenda == 'R') {

				if (strlen($reinc_revenda_nome) == 0) {
					$reinc_revenda_nome = $reinc_consumidor_nome;
				}

			}
		}

		if (in_array($login_fabrica, [24,52,91])) { #HD 253575 |-| FIM XLS |-| INICIO

			fputs ($fp,"</table>");

			fputs ($fp,"</body>");
			fputs ($fp, "</html>");

			fclose ($fp);

			//echo `cd $path_tmp; rm -rf $arquivo_nome_c.zip; zip -o $arquivo_nome_c.zip $arquivo_nome_c > /dev/null ; mv  $arquivo_nome_c.zip $path `;
			echo `cp $arquivo_completo_tmp $arquivo_completo`;

		}#HD 253575 |-| FIM XLS |-|FIM


		#HD 253575 - INICIO - IF para cook_cliente_admin
		if (!$cook_cliente_admin) {

			echo "<tr id='linha_final' class='titulo_tabela'>";
			echo "<td height='20' colspan='$colspan_fricon' align='left'> ";
			echo "<input id='qtde_os' type='hidden' name='qtde_os' value='$x'>";
			if (trim($aprova) == 'aprovacao' || $login_fabrica == 94) {

				echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; Com Marcados: &nbsp;";
				echo "<select name='select_acao' size='1' class='frm' >";
				echo "<option value=''></option>";


				if ($login_fabrica <> 94) {
					echo "<option value='00'"; if ($_POST["select_acao"] == "00") echo " selected"; echo ">OS APROVADA</option>";
				}

				if ($login_fabrica == 72) {
					echo "<option value='99'"; if ($_POST["select_acao"] == "99") echo " selected"; echo ">OS CANCELADA</option>";
				}

				if (in_array($login_fabrica,array(91,126,131,132,136,139))) {
					echo "<option value='90'"; if ($_POST["select_acao"] == "90") echo " selected"; echo ">OS APROVADA SEM PAGAMENTO</option>";
				}

				if (in_array($login_fabrica,$vet_recusar)) {

					echo "<option value='13'"; if ($_POST["select_acao"] == "13") echo " selected"; echo ">RECUSAR OS</option>";

				} else {

					$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND liberado IS TRUE;";
					$res = pg_exec($con,$sql);

					if (pg_numrows($res) > 0) {

						echo "<option value='13'"; if ($_POST["select_acao"] == "13") echo " selected"; echo ">RECUSAR OS</option>";

						for ($l = 0; $l < pg_numrows($res); $l++) {

							$motivo_recusa = pg_result($res, $l, 'motivo_recusa');
							$motivo        = pg_result($res, $l, 'motivo');
							$motivo        = substr($motivo, 0, 50);

							echo "<option value='$motivo_recusa'>$motivo</option>";

						}

					}
				}

				echo "</select>";

				if (in_array($login_fabrica,$vet_recusar)) {
					echo "&nbsp;&nbsp;Motivo: <input class='frm' type='text' name='observacao' id='observacao' size='50' maxlength='900' value='' ";  echo ">";
				}
					echo "&nbsp;&nbsp;&nbsp;&nbsp; <input class='btn' type='button' value='Gravar' style='cursor:pointer' onclick='
					if (document.frm_pesquisa2.select_acao.value == 163){recusaFabricante();}else {acaoOS();}'
					style='cursor:pointer;' border='0'>"; // </td></tr>
			}

			if ($login_fabrica == 91) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp; Total de Ordens de Serviço:  <input class='text' type='text' value='$rows_res'
					style='cursor:pointer;' border='0' readonly>";
			}

		}

		echo "</table>";
		echo "<input type='hidden' name='motivo_recusa' id='motivo_recusa'>";
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";

		if (in_array($login_fabrica, [24,52,91])) {	#HD 253575 - BOTÃO DE DOWNLOAD EXCEL

			echo "<p class='container tac'>
					<a href='../admin/xls/$arquivo_nome_c' target='_blank'>
						<img src='imagens/excel.png' width='60px' height='80px' />
						<span class='txt'>Gerar Arquivo XLS</span>
					</a>
				</p>
				<br />";

		}#HD 253575 - FIM

		echo "</form>";

	} else {
		echo "<br />";
		echo "<div class='alert alert-warning'><h4>Nenhuma OS Encontrada.</h4></div>";
	}

	$msg_erro = '';

}

include "rodape.php" ?>
</div>
