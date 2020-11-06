<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include ($_GET['lu_os'] == 'sim') ? "login_unico_autentica_usuario.php" : 'autentica_usuario.php';
include "funcoes.php";

include_once('anexaNF_S3.php');// Dentro do include estão definidas as fábricas que anexam imagem da NF e os parâmetros.

// HD 153966
$login_fabrica = (strlen($_GET['lu_fabrica']) > 0) ? $_GET['lu_fabrica'] : $login_fabrica;

if (isset($_GET["lu_fabrica"])) {

	$fabrica = $_GET["lu_fabrica"];

	$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
			tbl_posto_fabrica.posto,
			tbl_posto_fabrica.fabrica
		FROM tbl_posto_fabrica
		WHERE fabrica = $fabrica
		AND posto     = $cook_posto";

	$res = pg_query($con, $sql);

	if (pg_numrows($res) > 0) {

		setcookie("cook_posto_fabrica");
		setcookie("cook_posto");
		setcookie("cook_fabrica");
		setcookie("cook_login_posto");
		setcookie("cook_login_fabrica");
		setcookie("cook_login_pede_peca_garantia");
		setcookie("cook_login_tipo_posto");
		setcookie("cook_posto_fabrica", pg_result($res, 0, 'posto_fabrica'));
		setcookie("cook_posto", pg_result($res, 0, 'posto'));
		setcookie("cook_fabrica", pg_result($res, 0, 'fabrica'));

	}

}

$os = (isset($_GET['os'])) ? $_GET['os'] : $_POST['os'];
if  (trim($os) == '') header('Location: os_consulta_lite.php');

$sql_os = "SELECT os FROM tbl_os WHERE os = $os AND posto = $login_posto AND fabrica = $login_fabrica";
$res_os = pg_query($con, $sql_os);

if (pg_num_rows($res_os) != 1) $msg_erro = 'OS não localizada!';

/**
 *
 * HD 739078 - latinatec: os em auditoria (aberta a mais de 60 dias) não pode consultar
 *
 */
if ($login_fabrica == 15) {
	$os_bloq_tipo = '120, 122, 123, 126';
	$sqlStOs = "select status_os from tbl_os_status where status_os in ($os_bloq_tipo) and os = $os and fabrica_status = $login_fabrica order by data desc limit 1";
	$resStOs = pg_query($con, $sqlStOs);

	if (pg_num_rows($resStOs) > 0) {
		$status_atual = pg_result($resStOs, 0, 'status_os');
		if ($status_atual == 120) {
			echo '<div style="margin-top: 20px; color: #FF0000; font-weight: bold; text-align: center;">';
				echo 'OS fora do prazo para fechamento.<br/><br/>';
				echo '<input type="button" value=" Fechar " onClick="window.close()" />';
			echo '</div>';
			exit;
		}
	}
}

if ($login_fabrica == 7) {
    header ("Location: os_press_filizola.php?os=$os");
    exit;
}

//forçar o login do posto para distrib (consulta do embarque)
if (strlen($_GET['login_posto']) > 0) {
	$gambiara     = "t";
	$xlogin_posto = $_GET['login_posto'];
}

/*	HD 135436(+Mondial) HD 193563 (+Dynacom)
	Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
	na os_consulta_lite, os_press, admin/os_press e na admin/os_fechamento, sempre nesta função
*/
#HD 311411 - Adicionado Fábrica 6 (TecToy)
function usaDataConserto($posto, $fabrica) {
	if ($posto == 4311 or (($fabrica <> 11 and $fabrica <> 1) and $posto == 6359) or
		in_array($fabrica, array(2,3,5,6,7,11,14,15,20,43,45)) or $fabrica > 50) {
		return true;
	}
	return false;
}

//30/08/2010 MLG HD 283928  Fábricas que mostram o status de Intervenção e o histórico. Adicionar 43 (Nova Comp.)
$historico_intervencao = (in_array($login_fabrica, array(1,2,3,6,11,14,25,35,40,43,45,50,72,74)) or $login_fabrica > 84);

//28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item
$mostrar_valor_pecas = in_array($login_fabrica, array(1));

##GRAVA - Interações na OS
if ($btn_acao2 == 'gravar_interacao') {

	$msg = $_POST['interacao_msg'];

	if (strlen($interacao_msg) == 0) {
		$msg_erro = "Por favor, ensira algum comentário.";
	}

	if (strlen($msg_erro) == 0) {
		$sql = "INSERT INTO tbl_os_interacao(
								os             ,
								comentario
							)VALUES(
								$os         ,
								'$msg'
							)";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		header("Location: $PHP_SELF?os=$os");
	}

}

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == 670 || $btn_acao == 671 || $btn_acao == 733) {

	$msg_erro = "";

	$res = pg_query ($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao)
			VALUES ($os,64,current_timestamp,'Posto retirou intervenção da fábrica.')";

	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {

		$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (pg_num_rows($res) > 0) {

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$os_produto = pg_fetch_result($res,$i,os_produto);

				$sql1 = "UPDATE tbl_os_item
							SET servico_realizado = $btn_acao
							WHERE os_produto=$os_produto
							AND servico_realizado = 673";

				$res1 = pg_query($con,$sql1);
				$msg_erro .= pg_errormessage($con);

			}

		}

	}

	if (strlen($msg_erro) > 0) {

		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		echo "<script language='JavaScript'>alert('Operação não realizada.\nPor favor entre em contato com a TELECONTROL.\n\n');</script>";

	} else {

		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "<script language='JavaScript'>alert('Operação realizada com sucesso.');</script>";

	}

}

#if ($login_fabrica == 11 AND $login_posto == 6359) {
#    header ("Location: os_press_20080515.php?os=$os");
#    exit;
#}
// HD 61323
if (isset($_POST['gravarDataconserto']) AND isset($_POST['os'])) {

	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$os                 = trim($_POST['os']);

	if (strlen($os) > 0) {

		if (strlen($gravarDataconserto ) > 0) {

			$data = $gravarDataconserto.":00 ";
			$aux_ano  = substr($data,6,4);
			$aux_mes  = substr($data,3,2);
			$aux_dia  = substr($data,0,2);
			$aux_hora = substr($data,11,5).":00";
			$gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";

		} else {
			$gravarDataconserto ='null';
		}

		$erro = "";

		if ($gravarDataconserto != 'null') {

			$sql = "SELECT $gravarDataconserto > CURRENT_TIMESTAMP ";
			$res = pg_query($con,$sql);

			if (pg_fetch_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
			}

		}

		if ($gravarDataconserto != 'null') {

			$sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
			$res = pg_query($con, $sql);

			if (pg_fetch_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
			}

		}

		if (strlen($erro) == 0) {

			$sql = "UPDATE tbl_os SET data_conserto = $gravarDataconserto WHERE os = $os AND fabrica = $login_fabrica AND posto = $login_posto";
			$res = pg_query($con,$sql);

			echo "ok";

		} else {

			echo $erro;

		}

	}

	exit;

}

$fechar_os = $_GET['fechar'];

if (strlen ($fechar_os) > 0) {

	$msg_erro = "";
	$res = pg_query ($con,"BEGIN TRANSACTION");
	$sql = "SELECT status_os
			FROM tbl_os_status
			WHERE os = $fechar_os
			AND status_os IN (62,64,65,72,73,87,88,116,117,128)
			ORDER BY data DESC
			LIMIT 1";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {

		$status_os = trim(pg_fetch_result($res,0,status_os));

		if ($status_os == "72" || $status_os == "62" || $status_os == "87" || $status_os == "116") {
			$msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
		}

	}

	$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $fechar_os AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$msg_erro .= pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_finaliza_os($fechar_os, $login_fabrica)";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con) ;
	}


	if (strlen ($msg_erro) == 0) {

		$sql = "SELECT to_char(data_fechamento,'DD/MM/YYYY') as data_fechamento,
						to_char(finalizada,'DD/MM/YYYY') as finalizada
				FROM   tbl_os
				WHERE os = $fechar_os";

		$res = pg_query($con,$sql);
		$data_fechamento = pg_fetch_result($res,0,data_fechamento);
		$finalizada      = pg_fetch_result($res,0,finalizada);

		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "ok;$fechar_os;$data_fechamento;$finalizada";

	} else {

		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";

	}

	flush();
	exit;

}

//--=== VALIDA REINCIDENCIA DA OS ==================================================
$sql = "SELECT tbl_extrato.extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os AND tbl_extrato.aprovado IS NOT NULL ; ";
$res2 = pg_query ($con,$sql);
$reic_extrato = @pg_fetch_result($res2,0,0);

//  16/11/2009 HD 171349 - Waldir - também comentada linha 232
// if(strlen($reic_extrato) == 0){
// 	//echo "Passou aqui.";
// 	if($login_fabrica <> 56){
// 		$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
// 	}
// 	$res1 = pg_query ($con,$sql);

	if(strlen($_GET['os'])>0){
		$os=$_GET['os'];
		$sql = "SELECT  motivo_atraso ,
						observacao    ,
						os_reincidente,
						obs_reincidencia
				FROM tbl_os
				WHERE os = $os
				AND fabrica = $login_fabrica
				and finalizada is null";
	/*takashi 22/10/07 colocou and finalizada is null pois OS ja fechada e paga estava entrando no motivo do atraso, acho que nao há necessidade, se tiver necessidade comente as alterações.*/
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$motivo_atraso    = pg_fetch_result($res,0,motivo_atraso);
			$observacao       = pg_fetch_result($res,0,observacao);
			$os_reincidente   = pg_fetch_result($res,0,os_reincidente);
			$obs_reincidencia = pg_fetch_result($res,0,obs_reincidencia);

			if($login_fabrica == 2){
				if($os_reincidente=='t' AND (strlen($obs_reincidencia) == 0))
					header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
			} else {
				if($os_reincidente=='t' AND strlen($obs_reincidencia )==0 )
					header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
			}
		}
	}
// }


$interacao_os = $_POST['interacao_os'];
if(strlen($interacao_os) > 0){
    include_once 'class/email/mailer/class.phpmailer.php';
    $mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

	$interacao_msg              = $_POST['interacao_msg'];
	$interacao_msg2             = $_POST['interacao_msg2'];
	$interacao_exigir_resposta = $_POST['interacao_exigir_resposta'];
	
	if(strlen($interacao_msg) == 0){
		$msg_erro = traduz("por.favor.insira.algum.comentario",$con,$cook_idioma);
	}

	if($interacao_exigir_resposta <> 't'){
		$interacao_exigir_resposta = 'f';
	}
	if($login_fabrica == 3){
		$interacao_exigir_resposta = 't';
	}

	if(($login_fabrica == 3) and (strlen($interacao_msg) == 0 or strlen($interacao_msg2) == 0)){
		$msg_erro = traduz("e.obrigatorio.preencher.os.2.campos.de.enviar.duvida.ao.suporte.tecnico",$con,$cook_idioma);
	}

	if(strlen($interacao_msg) > 0 AND strlen($interacao_msg2) > 0){
		$interacao_msg = traduz("duvidas",$con,$cook_idioma).": <br>".$interacao_msg . "<br><br>".traduz("pontos.verificados.pelo.tecnico",$con,$cook_idioma).": <br>".$interacao_msg2;
	}


	if(strlen($msg_erro) == 0){
		$sql = "INSERT INTO tbl_os_interacao(
								os             ,
								comentario     ,
								exigir_resposta
							)VALUES(
								$os              ,
								'$interacao_msg' ,
								'$interacao_exigir_resposta'
							)";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro) == 0){
			if (in_array($login_fabrica, array(45,80))) { // HD 54576
				$sqlx = " SELECT contato_estado,
								 contato_email ,
								 codigo_posto
							FROM tbl_posto_fabrica
							WHERE fabrica = $login_fabrica
							AND   posto   = $login_posto   ";
				$resx = pg_query($con,$sqlx);
				if (pg_num_rows($resx) > 0) {
					$contato_estado = pg_fetch_result($resx,0,contato_estado);
					$contato_email  = pg_fetch_result($resx,0,contato_email);
					$codigo_posto   = pg_fetch_result($resx,0,codigo_posto);

					if ($login_fabrica == 45) { // HD 289251
						$atendentes = array(
							1 => array(
								'email'		=> 'atendimentoastec6@nksonline.com.br',
								'estados'	=> array('SP')
							),
							2 => array(
								'email'		=> 'atendimentoastec3@nksonline.com.br',
								'estados'	=> array('SC', 'RS', 'PR')
							),
							3 => array(
								'email'		=> 'atendimentoastec2@nksonline.com.br',
								'estados'	=> array('RJ', 'ES', 'MG')
							),
							4 => array(
								'email'		=> 'atendimentoastec5@nksonline.com.br',
								'estados'	=> array('GO', 'MS', 'MT', 'DF')
							),
							5 => array(
								'email'		=> 'atendimentoastec1@nksonline.com.br',
								'estados'	=> array('SE','AL', 'RN', 'MA', 'PE', 'PB', 'CE', 'PI', 'BA')
							),
							6 => array(
								'email'		=> 'atendimentoastec7@nksonline.com.br',
								'estados'	=> array('TO', 'PA', 'AP', 'RR', 'AM', 'AC', 'RO')
							)
						);

					}elseif ($login_fabrica == 80) {
						$atendentes = array(
							1 => array(
								'email'		=> 'assistec1@amvox.com.br',
								'estados'	=> array('PE','PB')
							),
							2 => array(
								'email'		=> 'assistec2@amvox.com.br',
								'estados'	=> array('AC', 'AM', 'AP', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PI', 'PR', 'RJ', 'RO', 'RR', 'RS', 'SC', 'TO')
							),
							3 => array(
								'email'		=> 'assistec3@amvox.com.br',
								'estados'	=> array('CE','RN','SP')
							),
							5 => array(
								'email'		=> 'assistec5@amvox.com.br',
								'estados'	=> array('BA','SE','AL')
							),
						);
					}
					
					if (strlen($contato_email) == 0) {
						$remetente    = "Suporte <helpdesk@telecontrol.com.br>";
					} else {
						$remetente    = "$codigo_posto <$contato_email>";
					}
					if (strlen($contato_estado) > 0) {

						foreach($atendentes as $atendente_posto) {
							extract($atendente_posto, EXTR_PREFIX_ALL, 'at');
							if (in_array($contato_estado, $at_estados)) break; // Sai do foreach assim que achar o estado
						}

						$sql_at = "SELECT REGEXP_REPLACE(nome_completo, E'^(\\\\w+).*\\\\s(\\\\w+)$', E'\\\\1 \\\\2') AS nome_atendente
									 FROM tbl_admin
									WHERE fabrica = $login_fabrica
									  AND email   = '$at_email'
									  AND ativo   IS TRUE";
						$res_at = pg_query($con, $sql_at); // Pega o nome (nome e último sobrenome...) do admin do banco.
						$destinatario = 'Suporte <'.$atendentes[1]['email'].'>'; // Se não achar o e-mail no banco, usar o primeiro email
						if (pg_num_rows($res_at) > 0) 
                            $destinatario = $at_email;//sprintf('"%s" <%s>', pg_fetch_result($res_at, 0, 0), $at_email);
						#die('<pre>' . print_r($atendentes, true).'</pre>' . htmlentities("Estado de $contato_estado. Enviando e-mail para: $destinatario"));
						#echo $at_email; die;
						$sql_os = "SELECT sua_os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
						$res_os = pg_query($con,$sql_os);
						if(pg_num_rows($res_os) > 0) {
							$sua_os = pg_fetch_result($res_os,0,sua_os);
						} else {
							$sua_os= $os;
						}

						if ($login_fabrica == 45) { 
							if(strlen($at_email) > 0){
								$destinatario = $at_email;	
							}else{
								$destinatario = 'helpdesk@telecontrol.com.br';
							}
						}

						$assunto      = "Interação na OS $sua_os";
						$mensagem     .="Posto $codigo_posto colocou seguinte interação na OS $sua_os:\n";
						$mensagem     .="<br>$interacao_msg\n";

                        $mailer->IsSMTP();
                        $mailer->IsHTML();                    
                        $mailer->AddAddress($destinatario);
                        $mailer->Subject = $assunto;
                        $mailer->Body = $mensagem;

                       if (!$mailer->Send()) {              
                            $msg_erro = "Erro ao enviar email para {$email_para}";
                            //echo $mailer->ErrorInfo;
                        }

					}
				}
			}
			header ("Location: $PHP_SELF?os=$os");
		}
	}
}

////////////// ADICIONADO POR FABIO 10/01/2007
function converte_data($date)
{
    $date = explode("-", str_replace('/', '-', $date));
    $date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
    if (sizeof($date)==3)
        return $date2;
    else return false;
}


#HD 44202 - intervenção OS aberta
$os= trim($_GET['os']);


if($login_fabrica == 3  AND strlen(trim($_POST['btn_acao']))>0 AND $_POST['btn_acao']=='gravar_justificativa'){
	$txt_justificativa_os_aberta = $_POST['txt_justificativa_os_aberta'];

	$res = @pg_query($con,"BEGIN TRANSACTION");

	$status_os = "";
	$sql = "SELECT status_os
			FROM  tbl_os_status
			WHERE os=$os
			AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
			ORDER BY data DESC LIMIT 1";
	$res_intervencao = pg_query($con, $sql);
	$msg_erro        = pg_errormessage($con);

	if (pg_num_rows ($res_intervencao) > 0 ){
		$status_os = pg_fetch_result($res_intervencao,0,status_os);

		if(strlen($txt_justificativa_os_aberta )== 0){
			$msg_erro .= "É necessário preencher a Justificativa para OS aberta.";
		} else {
			if ($status_os=="120") {
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao)
						VALUES ($os,122,current_timestamp,'$txt_justificativa_os_aberta')";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			} else if ($status_os=="140") {
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao)
						VALUES ($os,141,current_timestamp,'$txt_justificativa_os_aberta')";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}else {
		#$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		$res = @pg_query ($con,"COMMIT TRANSACTION");
	}
}

$mostra_valor_faturada = trim($_GET['mostra_valor_faturada']);

if($mostra_valor_faturada =='sim' and !empty($os)) { // HD 181964
	echo "<script>window.open('produto_valor_faturada.php?os=$os','','height=300, width=650, top=20, left=20, scrollbars=yes')</script>";
}


#HD 12657 - Posto causa a intervenção
$inter = trim($_GET['inter']);
if($login_fabrica==2 AND $inter=='1'){

	$res = @pg_query($con,"BEGIN TRANSACTION");

	$status_os = "";
	$sql = "SELECT status_os
			FROM  tbl_os_status
			WHERE os=$os
			AND status_os IN (62,64,65)
			ORDER BY data DESC LIMIT 1";
	$res_intervencao = pg_query($con, $sql);
	$msg_erro        = pg_errormessage($con);
	if (pg_num_rows ($res_intervencao) > 0){
		$status_os = pg_fetch_result($res_intervencao,0,status_os);
	}
	if (pg_num_rows ($res_intervencao) == 0 OR $status_os!="62"){
		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao)
			VALUES ($os,62,current_timestamp,'Auto intervenção.')";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	$status_os = "";
	$sql = "SELECT status_os
			FROM  tbl_os_status
			WHERE os=$os
			AND status_os IN (62,64,65)
			ORDER BY data DESC LIMIT 1";
	$res_intervencao = pg_query($con, $sql);
	if (pg_num_rows ($res_intervencao) > 0){
		$status_os = pg_fetch_result($res_intervencao,0,status_os);
		if ($status_os=="62"){
			$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao)
					VALUES ($os,65,current_timestamp,'Reparo do produto deve ser feito pela fábrica.')";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sql = "INSERT INTO tbl_os_retorno (os) VALUES ($os)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}else {
		#$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		$res = @pg_query ($con,"COMMIT TRANSACTION");
	}
}

if (($login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica == 14 OR $login_fabrica==6 OR $login_fabrica==11) AND strlen(trim($_POST['btn_acao']))>0 AND $_POST['btn_acao']=='gravar'){

    $nota_fiscal_envio_p = trim($_POST['txt_nota_fiscal']);
    $numero_rastreio_p   = trim($_POST['txt_rastreio']);
    $data_envio_p        = trim($_POST['txt_data_envio']);


	// login_fabrica <> 6 -> HD chamado 4156
	if (strlen($nota_fiscal_envio_p)==0 OR (strlen($numero_rastreio_p)==0 AND $login_fabrica<>6 AND $login_fabrica <> 14) OR strlen($data_envio_p)!=10){
		$msg_erro.= traduz("informacoes.do.envio.a.fabrica.incorretos",$con,$cook_idioma);
	} else {
		$data_envio_x = converte_data($data_envio_p);
		if ($data_envio_x==false) $msg_erro.= traduz("data.no.formato.invalido",$con,$cook_idioma);
	}

	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_retorno
				SET nota_fiscal_envio = '$nota_fiscal_envio_p',
					data_nf_envio     = '$data_envio_x',
					numero_rastreamento_envio = '$numero_rastreio_p'
				WHERE os=$os";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$msg_erro = traduz("erro.ao.gravar.verifique.as.informacoes.digitadas",$con,$cook_idioma);
		}
	}
}

if (($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica == 14 OR $login_fabrica==6 OR $login_fabrica==11) AND $_POST['btn_acao']=='confirmar'){
	$os_retorno = trim($_GET['chegada']);
	if (strlen($os_retorno)==0)
		$msg_erro .= traduz("os.invalida",$con,$cook_idioma).": $os_retorno";

	$data_chegada_retorno = trim($_POST['txt_data_chegada_posto']);
	if (strlen($data_chegada_retorno)!=10){
		$msg_erro.= strtoupper(traduz("data.invalida",$con,$cook_idioma));
	}
	else {
		$data_chegada_retorno = converte_data($data_chegada_retorno);
		if ($data_chegada_retorno==false) $msg_erro.= traduz("data.no.formato.invalido",$con,$cook_idioma);
	}

	$res = @pg_query($con,"BEGIN TRANSACTION");

	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_retorno
				SET retorno_chegada='$data_chegada_retorno'
				WHERE os=$os";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_status
				SET status_os=64
				WHERE os=$os";
		$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,64,current_timestamp,'Produto com reparo realizado pela fábrica e recebido pelo posto')";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}
	else {
		$res = @pg_query ($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF?os=$os&msg_erro=$msg_erro");
	}
}
////////////// FIM ////  ADICIONADO POR FABIO 10/01/2007


$sql = "SELECT  tbl_fabrica.os_item_subconjunto
        FROM    tbl_fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
    $os_item_subconjunto = pg_fetch_result ($res,0,os_item_subconjunto);
    if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

if($login_fabrica==19){//hd 19833 3/6/2008
	$sql_revendas = "tbl_revenda.cnpj AS revenda_cnpj                                          ,
					 tbl_revenda.nome AS revenda_nome                                          ,
					 tbl_revenda.fone AS revenda_fone                                          ,";

	$join_revenda = "LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda";
} else {//lpad 25/8/2008 HD 34515
	$sql_revendas = "tbl_os.revenda_nome                                                        ,
					lpad(tbl_os.revenda_cnpj, 14, '0') AS revenda_cnpj                          ,
					tbl_os.revenda_fone                                                         ,";
}

#------------ Le OS da Base de dados ------------#

$os = empty($os) ? $_GET['os'] : $os; // HTTP_GET_VARS nao funciona na versao nova do php


if (strlen ($os) > 0) {

// HD31887

$col_tec = ($login_fabrica == 59 OR $login_fabrica == 87) ? 'tbl_os.tecnico' : 'tbl_os.tecnico_nome';

if ($login_posto != 4311) {
	$cond = "AND tbl_posto_fabrica.fabrica      = $login_fabrica";
}

	$sql = "SELECT  tbl_os.sua_os                                                               ,
					tbl_os.sua_os_offline                                                       ,
					tbl_admin.login                              AS admin                       ,
					troca_admin.login                            AS troca_admin       ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao              ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura               ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento             ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada                  ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida               ,
					tbl_os.tipo_atendimento                                                     ,
					$col_tec                                                                    ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento          ,
					tbl_tipo_atendimento.codigo                    AS codigo_atendimento        ,
					tbl_os.consumidor_nome                                                      ,
					tbl_os.consumidor_fone                                                      ,
					tbl_os.consumidor_celular                                                   ,
					tbl_os.consumidor_fone_comercial                                            ,
					tbl_os.consumidor_fone_recado                                               ,
					tbl_os.consumidor_endereco                                                  ,
					tbl_os.consumidor_numero                                                    ,
					tbl_os.consumidor_complemento                                               ,
					tbl_os.consumidor_bairro                                                    ,
					tbl_os.consumidor_cep                                                       ,
					tbl_os.consumidor_cidade                                                    ,
					tbl_os.consumidor_estado                                                    ,
					tbl_os.consumidor_cpf                                                       ,
					tbl_os.consumidor_email                                                     ,
					$sql_revendas
					tbl_os.nota_fiscal                                                          ,
					tbl_os.nota_fiscal_saida                                                    ,
					tbl_os.cliente                                                              ,
					tbl_os.revenda                                                              ,
					tbl_os.rg_produto                                                           ,
					tbl_os.defeito_reclamado_descricao       AS defeito_reclamado_descricao_os  ,
					tbl_marca.marca                                                             ,
					tbl_marca.nome as marca_nome                                                ,
					tbl_os.qtde_produtos as qtde                                                ,
					tbl_os.tipo_os                                                              ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf                     ,
					tbl_defeito_reclamado.defeito_reclamado      AS defeito_reclamado           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado_descricao ,
					tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
					tbl_defeito_constatado.defeito_constatado    AS defeito_constatado          ,
					tbl_defeito_constatado.descricao             AS defeito_constatado_descricao,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo   ,
					tbl_causa_defeito.causa_defeito              AS causa_defeito               ,
					tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo        ,
					tbl_motivo_reincidencia.descricao            AS motivo_reincidencia_desc    ,
					tbl_os.obs_reincidencia                                                     ,
					tbl_os.aparencia_produto                                                    ,
					tbl_os.acessorios                                                           ,
					tbl_os.consumidor_revenda                                                   ,
					tbl_os.obs                                                                  ,
					tbl_os.excluida                                                             ,
					tbl_produto.produto                                                         ,
					tbl_produto.referencia                                                      ,
					tbl_produto.referencia_fabrica               AS modelo                      ,
					tbl_produto.descricao                                                       ,
					tbl_produto.voltagem                                                        ,
					tbl_produto.valor_troca                                                     ,
					tbl_produto.troca_obrigatoria                                               ,
					tbl_os.qtde_produtos                                                        ,
					tbl_os.serie                                                                ,
					tbl_os.codigo_fabricacao                                                    ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo                ,
					tbl_posto.nome                               AS posto_nome                  ,
					tbl_os.ressarcimento                                                        ,
					tbl_os.certificado_garantia                                                 ,
					tbl_os_extra.os_reincidente                                                 ,
					tbl_os_extra.recolhimento,
					tbl_os_extra.orientacao_sac                                                 ,
					tbl_os_extra.reoperacao_gas                                    			    ,
					tbl_os_extra.obs_nf                                          			    ,
					tbl_os.solucao_os                                                           ,
					tbl_os.posto                                                                ,
					tbl_os.promotor_treinamento                                                 ,
					tbl_os.fisica_juridica                                                      ,
					tbl_os.troca_garantia                                                       ,
					tbl_os.troca_garantia_admin                                                 ,
					tbl_os.troca_faturada                                                       ,
					tbl_os_extra.tipo_troca                                                     ,
					tbl_os_extra.serie_justificativa											,
                    tbl_os_extra.hora_tecnica									        		,
                    tbl_os_extra.qtde_horas										            	,
					tbl_os.os_posto                                                             ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI') as data_ressarcimento       ,
					serie_reoperado                                                             ,
					tbl_extrato.extrato                                                         ,
					to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_previsao,
					to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_pagamento,
					tbl_os.fabricacao_produto                                                   ,
					tbl_os.qtde_km                                                              ,
					tbl_os.os_numero
			FROM       tbl_os
			JOIN       tbl_posto              ON tbl_posto.posto                       = tbl_os.posto
			JOIN       tbl_posto_fabrica      ON tbl_posto_fabrica.posto               = tbl_os.posto $cond
			LEFT JOIN       tbl_motivo_reincidencia ON tbl_os.motivo_reincidencia           = tbl_motivo_reincidencia.motivo_reincidencia
			LEFT JOIN  tbl_os_extra           ON tbl_os.os                             = tbl_os_extra.os
			LEFT JOIN  tbl_extrato            ON tbl_extrato.extrato                   = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
			LEFT JOIN  tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato        = tbl_extrato.extrato
			LEFT JOIN  tbl_admin              ON tbl_os.admin                          = tbl_admin.admin
			LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
			LEFT JOIN  tbl_defeito_reclamado  ON tbl_os.defeito_reclamado              = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado             = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN  tbl_causa_defeito      ON tbl_os.causa_defeito                  = tbl_causa_defeito.causa_defeito
			LEFT JOIN  tbl_produto            ON tbl_os.produto                        = tbl_produto.produto
			LEFT JOIN  tbl_tipo_atendimento   ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
			$join_revenda
			WHERE   tbl_os.os = $os ";
    if ($login_e_distribuidor == "t") {
#        $sql .= "AND (tbl_os_extra.distribuidor = $login_posto OR tbl_os.posto = $login_posto) ";
    } else {
        $sql .= "AND tbl_os.posto = $login_posto ";
    }
    #echo nl2br($sql); 
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) > 0) {
		$sua_os                      = pg_fetch_result ($res,0,sua_os);
		$admin                       = pg_fetch_result ($res,0,admin);
		$data_digitacao              = pg_fetch_result ($res,0,data_digitacao);
		$data_abertura               = pg_fetch_result ($res,0,data_abertura);
		$data_fechamento             = pg_fetch_result ($res,0,data_fechamento);
		$data_finalizada             = pg_fetch_result ($res,0,finalizada);
		$data_nf_saida               = pg_fetch_result ($res,0,data_nf_saida);

		//--==== INFORMACOES DO CONSUMIDOR =================================================
		$consumidor_nome             = pg_fetch_result ($res,0,consumidor_nome);
		$consumidor_endereco         = pg_fetch_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_fetch_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_fetch_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_fetch_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_fetch_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_fetch_result ($res,0,consumidor_estado);
		$consumidor_cep              = pg_fetch_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_fetch_result ($res,0,consumidor_fone);
		$consumidor_celular          = pg_fetch_result ($res,0,consumidor_celular);
		$consumidor_fone_comercial   = pg_fetch_result ($res,0,consumidor_fone_comercial);
		$consumidor_fone_recado      = pg_fetch_result ($res,0,consumidor_fone_recado);
		$consumidor_cpf              = pg_fetch_result ($res,0,consumidor_cpf);
		$consumidor_email            = pg_fetch_result ($res,0,consumidor_email);
		$fisica_juridica             = pg_fetch_result ($res,0,fisica_juridica);
		$data_ressarcimento          = pg_fetch_result ($res,0,data_ressarcimento);
		$recolhimento				 = pg_fetch_result ($res,0,recolhimento);
		$reoperacao_gas              = pg_fetch_result($res,0,reoperacao_gas);
		$valor_troca                 = pg_fetch_result ($res,0,valor_troca);
        $hora_tecnica                 = pg_fetch_result ($res,0,hora_tecnica);
        $qtde_horas                 = pg_fetch_result ($res,0,qtde_horas);

		if($fisica_juridica=="F"){
			$fisica_juridica = traduz("pessoa.fisica",$con,$cook_idioma);
		}
		if($fisica_juridica=="J"){
			$fisica_juridica = traduz("pessoa.juridica",$con,$cook_idioma);
		}


		//--==== INFORMACOES DA REVENDA ====================================================
		$revenda_cnpj                = pg_fetch_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_fetch_result ($res,0,revenda_nome);
		$revenda_fone                = pg_fetch_result ($res,0,revenda_fone);
		$nota_fiscal                 = pg_fetch_result ($res,0,nota_fiscal);
		$nota_fiscal_saida           = pg_fetch_result ($res,0,nota_fiscal_saida);
		$data_nf                     = pg_fetch_result ($res,0,data_nf);
		$cliente                     = pg_fetch_result ($res,0,cliente);
		$revenda                     = pg_fetch_result ($res,0,revenda);
		$consumidor_revenda          = pg_fetch_result ($res,0,consumidor_revenda);

		//--==== INFORMACOES DO PRODUTO ====================================================
		$produto                      = pg_fetch_result ($res,0,produto);
		$aparencia_produto            = pg_fetch_result ($res,0,aparencia_produto);
		$acessorios                   = pg_fetch_result ($res,0,acessorios);
		$produto_referencia           = pg_fetch_result ($res,0,referencia);
		$produto_modelo               = pg_fetch_result ($res,0,modelo);
		$produto_descricao            = pg_fetch_result ($res,0,descricao);
		$produto_voltagem             = pg_fetch_result ($res,0,voltagem);
		$serie                        = pg_fetch_result ($res,0,serie);
		$codigo_fabricacao            = pg_fetch_result ($res,0,codigo_fabricacao);
		$troca_obrigatoria            = pg_fetch_result ($res,0,troca_obrigatoria);
		$rg_produto                   = pg_fetch_result ($res,0,rg_produto);
		$serie_justificativa          = pg_fetch_result ($res,0, serie_justificativa);

		//--==== DEFEITOS RECLAMADOS =======================================================
		$defeito_reclamado            = pg_fetch_result ($res,0,defeito_reclamado);
		$defeito_reclamado_descricao  = pg_fetch_result ($res,0,defeito_reclamado_descricao);
		$defeito_reclamado_descricao_os= pg_fetch_result ($res,0,defeito_reclamado_descricao_os);
		$os_posto                     = pg_fetch_result ($res,0,os_posto);

		if (strlen($defeito_reclamado_descricao)==0){
			$defeito_reclamado_descricao = $defeito_reclamado_descricao_os;
		}

		//HD 172561 - Seleciona defeito reclamado e digita defeito reclamado - por enquanto apenas para linha 528 - Informatica
		$sql = "
		SELECT
		tbl_linha.linha
		
		FROM
		tbl_produto
		JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha

		WHERE
		tbl_produto.produto=$produto
		AND tbl_linha.linha=528
		";
		$res_linha = pg_query($con, $sql);

		if (pg_num_rows($res_linha) > 0) {
			$sql = "SELECT defeito_reclamado_descricao FROM tbl_os WHERE os=$os";
			$res_linha = pg_query($con, $sql);
			$defeito_reclamado_descricao = pg_result($res_linha, 0, defeito_reclamado_descricao);
		}

		//--==== DEFEITOS CONSTATADO =======================================================
		$defeito_constatado           = pg_fetch_result ($res,0,defeito_constatado);
		$defeito_constatado_codigo    = pg_fetch_result ($res,0,defeito_constatado_codigo);
		$defeito_constatado_descricao = pg_fetch_result ($res,0,defeito_constatado_descricao);

		//--==== CAUSA DO DEFEITO ==========================================================
		$causa_defeito                = pg_fetch_result ($res,0,causa_defeito);
		$causa_defeito_codigo         = pg_fetch_result ($res,0,causa_defeito_codigo);
		$causa_defeito_descricao      = pg_fetch_result ($res,0,causa_defeito_descricao);
		$posto_codigo                 = pg_fetch_result ($res,0,posto_codigo);
		$posto_nome                   = pg_fetch_result ($res,0,posto_nome);
		$obs                          = pg_fetch_result ($res,0,obs);
		$qtde_produtos                = pg_fetch_result ($res,0,qtde_produtos);
		$excluida                     = pg_fetch_result ($res,0,excluida);
		$os_reincidente               = trim(pg_fetch_result ($res,0,os_reincidente));
		$orientacao_sac               = trim(pg_fetch_result ($res,0,orientacao_sac));
		$sua_os_offline               = trim(pg_fetch_result ($res,0,sua_os_offline));
		$solucao_os                   = trim (pg_fetch_result($res,0,solucao_os));
		$posto_verificado             = trim(pg_fetch_result ($res,0,posto));
		$marca_nome                   = trim(pg_fetch_result($res,0,marca_nome));
		$marca                        = trim(pg_fetch_result($res,0,marca));
		$ressarcimento                = trim(pg_fetch_result($res,0,ressarcimento));
		$certificado_garantia         = trim(pg_fetch_result($res,0,certificado_garantia));
		$troca_garantia               = trim(pg_fetch_result($res,0,troca_garantia));
		$troca_faturada               = trim(pg_fetch_result($res,0,troca_faturada));
		$troca_garantia_admin         = trim(pg_fetch_result($res,0,troca_garantia_admin));
		$troca_admin                  = trim(pg_fetch_result($res,0,troca_admin));
		$qtde                         = pg_fetch_result ($res,0,qtde);
		$tipo_os                      = pg_fetch_result ($res,0,tipo_os);
		$tipo_atendimento             = trim(pg_fetch_result($res,0,tipo_atendimento));
		if($login_fabrica == 59 OR $login_fabrica == 87)
			$tecnico_nome                 = trim(pg_fetch_result($res,0,tecnico));
		else
			$tecnico_nome             = trim(pg_fetch_result($res,0,tecnico_nome));
		$nome_atendimento             = trim(pg_fetch_result($res,0,nome_atendimento));
		$codigo_atendimento           = trim(pg_fetch_result($res,0,codigo_atendimento));
		$tipo_troca                   = trim(pg_fetch_result($res,0,tipo_troca));
		$numero_controle              = trim(pg_fetch_result($res,0,serie_reoperado)); //HD 56740

		//--==== AUTORIZAÇÃO CORTESIA =====================================
		//        $autorizacao_cortesia = trim(pg_fetch_result($res,0,autorizacao_cortesia));
		$promotor_treinamento         = trim(pg_fetch_result($res,0,promotor_treinamento));

		//--==== Dados Extrato HD 61132 ====================================
		$extrato                      = trim(pg_fetch_result($res,0,extrato));
		$data_previsao                = trim(pg_fetch_result($res,0,data_previsao));
		$data_pagamento               = trim(pg_fetch_result($res,0,data_pagamento));

		// HD 64152
		$fabricacao_produto           = trim(pg_fetch_result($res,0,fabricacao_produto));
		$qtde_km                      = trim(pg_fetch_result($res,0,qtde_km));
		$os_numero                    = trim(pg_fetch_result($res,0,os_numero));
		if(strlen($qtde_km) == 0) $qtde_km = 0;

		//HD 399700
		if($login_fabrica == 96){
			$motivo                    = trim(pg_fetch_result($res,0,obs_nf));
		}
		
		if ($login_fabrica == 52){
			$obs_reincidencia          = pg_result($res,0,'obs_reincidencia');
			$motivo_reincidencia_desc   = pg_fetch_result($res,0,motivo_reincidencia_desc);
		}


		if(strlen($promotor_treinamento)>0){
			$sql = "SELECT nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento";
			$res_pt = pg_query($con,$sql);
			if (@pg_num_rows($res_pt) >0) {
			$promotor_treinamento  = trim(@pg_fetch_result($res_pt,0,nome));
			}
		}

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		if(strlen($sistema_lingua)>0){
			if(strlen($produto)>0){
				$sql_idioma = " SELECT * FROM tbl_produto_idioma
								WHERE produto     = $produto
								AND upper(idioma) = '$sistema_lingua'";
				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$produto_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
			}
			if(strlen($defeito_constatado)>0){
				$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
								WHERE defeito_constatado = $defeito_constatado
								AND upper(idioma)        = '$sistema_lingua'";
				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$defeito_constatado_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
			}
			if(strlen($defeito_reclamado)>0){
				$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
								WHERE defeito_reclamado = $defeito_reclamado
								AND upper(idioma)        = '$sistema_lingua'";
				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$defeito_reclamado_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
			}
			if(strlen( $causa_defeito)>0){
				$sql_idioma = " SELECT * FROM tbl_causa_defeito_idioma
								WHERE causa_defeito = $causa_defeito
								AND upper(idioma)   = '$sistema_lingua'";
				$res_idioma = @pg_query($con,$sql_idioma);
				if (@pg_num_rows($res_idioma) >0) {
					$causa_defeito_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
				}
			}
		}

		# HD 13940 - Ultimo Status para as Aprovações de OS
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = pg_query($con,$sql);
		if (pg_num_rows($res_status) >0) {
			$status_recusa_status_os  = trim(pg_fetch_result($res_status,0,status_os));
			$status_recusa_observacao = trim(pg_fetch_result($res_status,0,observacao));
			if($status_recusa_status_os == 94){
				$os_recusada = 't';
			}
		}


		# HD 44202 - Ultimo Status para as Aprovações de OS aberta a mais de 90 dias
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (120,122,123,126,140,141,142,143)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = pg_query($con,$sql);
		if (pg_num_rows($res_status) >0) {
			$status_os_aberta     = trim(pg_fetch_result($res_status,0,status_os));
			$status_os_aberta_obs = trim(pg_fetch_result($res_status,0,observacao));
		}

		//--=== Tradução para outras linguas ================================================

		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep        ,
							tbl_revenda.email
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_fetch_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_fetch_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_fetch_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_fetch_result ($res1,0,bairro)));
				$revenda_email       = trim(pg_fetch_result ($res1,0,email));
				$revenda_cep         = trim(pg_fetch_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}

		if($aparencia_produto=='NEW'){
			$aparencia = traduz("bom.estado",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USL'){
			$aparencia = traduz("uso.intenso",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USN'){
			$aparencia = traduz("uso.normal",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USH'){
			$aparencia = traduz("uso.pesado",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='ABU'){
			$aparencia = traduz("uso.abusivo",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='ORI'){
			$aparencia = traduz("original.sem.uso",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='PCK'){
			$aparencia = traduz("embalagem",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = traduz("confirmacao.de.ordem.de.servico",$con,$cook_idioma);

$layout_menu = 'os';
include "cabecalho.php"; ?>

<script language='javascript' src='js/jquery-1.3.2.js'></script>
<script language='javascript' src='js/jquery.maskedinput-1.2.2.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script language='javascript'>

$(function() {
	$("#data_conserto").maskedinput("99/99/9999 99:99");
});

$().ready(function() {

	$("#data_conserto").blur(function() {

		var campo = $(this);
		$.post('<? echo $PHP_SELF; ?>',	{
				gravarDataconserto : campo.val(),
				os: campo.attr("alt")
			},
			function(resposta) {

				if (resposta == 'ok') {
					$('#consertado').html(campo.val());
				} else {
					alert(resposta);
					campo.val('');
				}

			}
		);

	});

});

function fechaOS (os , fechar) {

	var curDateTime = new Date();
	var fechamento  = document.getElementById('data_fechamento');
	var finalizada  = document.getElementById('finalizada');

	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'fechar=' + escape(os) + '&dt='+curDateTime,
		beforeSend: function(){
			$(fechar).slideUp('slow');
		},
		complete: function(http) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					fechar.src='/assist/imagens/pixel.gif';
					fechar.innerHTML = "";
					fechamento.innerHTML = results[2];
					finalizada.innerHTML = results[3];
					alert ('OS <? fecho("fechada.com.sucesso",$con,$cook_idioma) ?>');
				} else {

					$(fechar).show('slow');

					if (http.responseText.indexOf ('de-obra para instala') > 0) {
						alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.instalacao",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
						alert ('<? fecho("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
						alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.este.atendimento",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
						alert ('<? fecho("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
						alert ('<? fecho("type.informado.para.o.produto.nao.e.valido",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('OS com peças pendentes') > 0) {
						alert ('<? fecho("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os",$con,$cook_idioma) ?>');
					} else if(http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem') > 0) {
						alert ('<? fecho("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada') > 0) {
						alert ('<? fecho("os.nao.pode.ser.fechada,.kilometragem.recusada",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem') > 0) {
						alert ('<? fecho("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada') > 0) {
						alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS') > 0){
						alert ('<? fecho("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
					} else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO') > 0) {
						alert ('<? fecho("por.favor.informar.o.conserto.do.produto.na.tela.consertado",$con,$cook_idioma) ?>');
					} else {
						alert ('<? fecho("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma)?>');
					}
				}

			} else {
				alert ('<? fecho("fechamento.nao.processado",$con,$cook_idioma) ?>');
			}
		}
	});

}


function aprovaOrcamento(orcamento) {

	var qtde = document.getElementById('qtde_pecas_orcamento').value;
	var msg = "";
	for (i=0;i<qtde;i++) {
		msg = msg + document.getElementById('peca_orcamento_'+i).value + ' - no valor de '+ document.getElementById('preco_orcamento_'+i).value+' \n';
	}
	if (confirm('Tem certeza que deseja Aprovar este orçamento? Caso sim será faturada as seguintes peças:\n'+msg)== true) {
		requisicaoHTTP('GET','ajax_aprova_orcamento.php?orcamento='+orcamento+'&acao=aprovar', true , 'div_detalhe_carrega');
	}
	
}

function reprovaOrcamento(orcamento) {

	requisicaoHTTP('GET','ajax_aprova_orcamento.php?orcamento='+orcamento+'&acao=reprovar', true , 'div_detalhe_carrega');
}


function div_detalhe_carrega (campos) {
	campos_array = campos.split("|");
	orcamento = campos_array [0];
	var div = document.getElementById('msg_orcamento');
	var div_btn = document.getElementById('aprova_reprova');
	div.innerHTML = orcamento;
	div_btn.style.display = 'none';
}

function showHideGMap() {
	var gMapDiv = $('#gmaps');
	var newh    = (gMapDiv.css('height')=='5px') ? '486px' : '5px';
	gMapDiv.animate({height: newh}, 400);
	if (newh=='5px') gMapDiv.parent('td').css('height', '2em');
	if (newh!='5px') gMapDiv.parent('td').css('height', 'auto');
}
</script>
<style type="text/css">

.vermelho {color: #f00!important}

body {
    margin: 0px;
}
.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    padding-right: 1ex;
    text-transform: uppercase;
}

.titulo2 {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
    text-transform: uppercase;
}
.titulo3 {
    font-family: Arial;
    font-size: 10px;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:5px;
    padding-right: 1ex;
    text-transform: uppercase;
}

.titulo4 {
    font-family: Arial;
    font-size: 10px;
    text-align: left;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:0px;
}

.inicio {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
    padding-right: 1ex;
    text-transform: uppercase;
}

.conteudo {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #F4F7FB;
}

.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.inpu{
    border:1px solid #666;
}
.titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
}

table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}

.conteudo_sac {
    font-family: Arial;
    font-size: 10pt;
    text-align: left;
    background: #F4F7FB;
}
#gmaps {
	width:606px;
	height: 5px;
	/*display:none;*/
	margin:1ex auto;
	background-color:#CED7E7;
	border:6px solid #CED7E7;
	border-top-width:24px;
	border-radius:12px;
	-moz-border-radius:12px;
	cursor:help;
	overflow: hidden;
	z-index:100;
	/*transition: height 0.5s ease-in;
	-o-transition: height 0.5s ease-in;
	-ms-transition: height 0.5s ease-in;
	-moz-transition: height 0.5s ease-in;
	-webkit-transition: height 0.5s ease-in;*/
}

@media print {
	.mapa_gmaps {
		display:none;
	}
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style><?php

//Verifica se OS existe -- HD 735968
$sql = "SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND posto = $login_posto";
$res = pg_exec($con, $sql);

if (pg_numrows($res) == 0) {

	$sql_exc = "SELECT * FROM tbl_os_excluida WHERE os = $os";
	$res_exc = pg_exec($con, $sql_exc);

	if (pg_numrows($res_exc) > 0) {

		$sql_exc = "SELECT observacao FROM tbl_os_status WHERE os = $os and fabrica_status = $login_fabrica and status_os = 15";
		$res_exc = pg_exec($con, $sql_exc);

		if (pg_num_rows($res_exc)) {
			echo "<br />". pg_result($res_exc, 0, 'observacao');
		} else {
			echo "Existe um registro de exclusão para esta OS";
		}

	} else {

		echo '<center>OS não Encontrada</center>';

	}

	include 'rodape.php';
	exit;

}

if ($login_fabrica == 50 or $login_fabrica == 14 or $login_fabrica == 52 or $login_fabrica == 24) {

	$sql  = "SELECT os_interacao FROM tbl_os_interacao WHERE os = $os;";
	$res  = pg_query($con, $sql);
	$cont = 0;

	if (pg_num_rows($res) > 0) {?>

		<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
			<TR>
				<TD><font size='2' color='#FFFFFF'><center><b><? if ($login_fabrica ==3) echo "SUPORTE TÉCNICO"; else echo "INTERAGIR NA OS"; ?></b></center></font></TD>
			</TR>
			<TR>
				<TD class='conteudo'><?php

					$sql      = "SELECT excluida from tbl_os where os = $os";
					$res      = pg_query($con,$sql);
					$excluida = pg_fetch_result($res,0,0);

					if ($excluida <> 't') {?>

						<FORM NAME='frm_interacao' id='frm_interacao' METHOD=POST ACTION="<? echo "$PHP_SELF?os=$os"; ?>">
						<TABLE width='500' align='center' cellpadding='0' cellspacing='0'>
							<TR>
								<TD>
									<TABLE align='center' border='0' cellspacing='0' cellpadding='5'>
										<TR align='center'>
											<TD colspan='3'><INPUT TYPE="text" NAME="interacao_msg" size='60'></TD>
										</TR>
										<TR align='center'>
											<TD colspan='3'><input type="hidden" id="btn_acao2" name="btn_acao2" value="">
												<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.getElementById('btn_acao2').value == '') { document.getElementById('btn_acao2').value='gravar_interacao' ; document.getElementById('frm_interacao').submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Comentário" border='0' style="cursor:pointer;">
											</TD>
										</TR>
									</TABLE>
								</TD>
							</TR>
						</TABLE>
						</FORM><?php

					}

					$sql = "SELECT os_interacao               ,
									to_char(data,'DD/MM/YYYY HH24:MI') as data,
									comentario                ,
									interno                   ,
									tbl_admin.nome_completo
								FROM tbl_os_interacao
								LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
								WHERE os = $os
								ORDER BY os_interacao DESC;";
					$res  = pg_query($con,$sql);
					$cont = 0;

					if (pg_num_rows($res) > 0) {

						echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
							echo "<tr>";
								echo "<td class='titulo'><CENTER><b>Nº</b></CENTER></td>";
								echo "<td class='titulo'><CENTER><b>Data</b></CENTER></td>";
								echo "<td class='titulo'><CENTER><b>Mensagem</b></CENTER></td>";
								echo "<td class='titulo'><CENTER><b>Admin</b></CENTER></td>";
							echo "</tr>";
							echo "<tbody>";

								for ($i = 0; $i < pg_num_rows($res); $i++) {

									$os_interacao     = pg_fetch_result($res,$i,os_interacao);
									$interacao_msg    = pg_fetch_result($res,$i,comentario);
									$interacao_interno= pg_fetch_result($res,$i,interno);
									$interacao_data   = pg_fetch_result($res,$i,data);
									$interacao_nome   = pg_fetch_result($res,$i,nome_completo);

									if ($interacao_interno == 't') {
										$cor = "style='font-family: Arial; FONT-SIZE: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
									} else {
										$cor = "class='conteudo'";
									}

									$cont++;

									echo "<tr>";
										echo "<td width='25' $cor>"; echo $cont; echo "</td>";
										echo "<td width='90' $cor nowrap>$interacao_data</td>";
										echo "<td $cor>$interacao_msg</td>";
										echo "<td $cor nowrap>$interacao_nome</td>";
									echo "</tr>";

								}

							echo "</tbody>";
						echo "</TABLE>";
						echo "<br />&nbsp;";

					}

					echo "</TD>";

				echo "</TR>";

		echo "</TABLE>";
		echo "<br><br>";

	}

}

if ($login_fabrica == 81) {//HD 307124 - inicio - OS CANCELADA

	$sql = "SELECT cancelada from tbl_os where fabrica=$login_fabrica and os=$os";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		
		$cancelada = pg_result($res, 0, 0);
		
		if ($cancelada == 't') {?>
			<table class='msg_erro' align='center' width='700'>
				<tr>
					<td align='center'> OS CANCELADA </td>
				</tr>
			</table><?php

		}
	
	}

}//HD 307124 - fim - OS CANCELADA


if(($login_fabrica==81 or $login_fabrica==51)){
	$os=$_GET['os'];
	$sql_atr = "SELECT  motivo_atraso 
			FROM tbl_os
			WHERE os = $os
			AND fabrica = $login_fabrica
			and finalizada is null";
	$res_atr = pg_query($con,$sql_atr);
	if (pg_num_rows($res_atr) > 0 and strlen(pg_result($res_atr, 0, motivo_atraso))>0) {
		echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
		echo "<tr>";
		echo "<td align='center'><b><font size='1'>";
		echo strtoupper(traduz("MOTIVO DO ATRASO",$con,$cook_idioma));
		echo "</font></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'><b><font size='1'>";
		echo pg_result($res_atr, 0, motivo_atraso);
		echo "</font></b></td>";
		echo "</tr>";
		echo "</table>";
	}
}




if (strlen($os_reincidente) > 0 OR $reincidencia =='t') {
	$sql = "SELECT  tbl_os_status.status_os,
					tbl_os_status.observacao,
					(select posto from tbl_os WHERE tbl_os.os = tbl_os_extra.os_reincidente) as posto_reincidente
			FROM  tbl_os_extra JOIN tbl_os_status USING(os)
			WHERE tbl_os_extra.os = $os
			AND   tbl_os_status.status_os IN (67,68,70,86)";
	$res1 = pg_query ($con,$sql);

	if (pg_num_rows ($res1) > 0) {
		$status_os         = trim(pg_fetch_result($res1,0,status_os));
		$observacao        = trim(pg_fetch_result($res1,0,observacao));
		$posto_reincidente = trim(pg_fetch_result($res1,0,posto_reincidente));
	} else {
		$posto_reincidente = $login_posto;
	}

	//HD3646
	if(($login_fabrica==3 and $login_posto==$posto_reincidente) or ($login_fabrica<>3 and $login_fabrica<> 6)){
		echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
		echo "<tr>";
		echo "<td align='center'><b><font size='1'>";
		echo strtoupper(traduz("atencao",$con,$cook_idioma));
		echo "</font></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'><font size='1'>";

		if ($login_fabrica == 30) {
			$limit_reincidencia = "LIMIT 5";
		}

		if(strlen($os_reincidente)>0 ){
			$sql = "SELECT  tbl_os.sua_os,
							tbl_os.serie
					FROM    tbl_os
					WHERE   tbl_os.os = $os_reincidente;";
			$res1 = pg_query ($con,$sql);
			$sos   =   trim(pg_fetch_result($res1,0,sua_os));
			$serie_r = trim(pg_fetch_result($res1,0,serie));

			if($login_fabrica==1) $sos=$posto_codigo.$sos;
		} else {
			//CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
			$sql = "SELECT os,sua_os,posto
					FROM tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   serie   =  '$serie_r'
					AND     os      <> $os
					AND     fabrica =  $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.posto=$login_posto
					ORDER BY tbl_os.os
					$limit_reincidencia";
			$res2 = pg_query ($con,$sql);

			echo strtoupper(traduz("ordem.de.servico.com.numero.de.serie.%.reincidente.ordem.de.servico.anterior",$con,$cook_idioma,array($serie_r))).":<br>";

			if (pg_num_rows ($res2) > 0) {
				for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
					$sos_reinc  = trim(pg_fetch_result($res2,$i,sua_os));
					$os_reinc   = trim(pg_fetch_result($res2,$i,os));
					$posto_reinc   = trim(pg_fetch_result($res2,$i,posto));
					if($posto_reinc == $login_posto){
						echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
					} else {
						### Não pode mostrar o número da OS reincidente de outro posto!!!!
						### Alterado Samuel 26/04/2010 - HD 228477
						### echo "» $sos_reinc<br>";
					}

				}
			}

		}

		if($status_os==67){

			echo strtoupper(traduz("ordem.de.servico.com.numero.de.serie.%.reincidente.ordem.de.servico.anterior",$con,$cook_idioma,array($serie_r))).":<br>";

			if ($login_fabrica == 11) {
				$sql = "SELECT os_reincidente
						FROM tbl_os_extra
						WHERE os= $os";
				$res2 = pg_query($con,$sql);

				$osrein = pg_fetch_result($res2,0,os_reincidente);

				if (pg_num_rows($res2) > 0) {
					$sql = "SELECT os,sua_os
							FROM tbl_os
							WHERE   serie   = '$serie'
							AND     os      = $osrein
							AND     fabrica = $login_fabrica";
				}
				$res2 = pg_query($con,$sql);

				if (pg_num_rows($res2) > 0) {
					$sua_osrein = pg_fetch_result($res2,0,sua_os);
					echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
				}
			} else {
	
				if ($login_fabrica == 74) { // HD 708057

					$sql = "SELECT os_reincidente
							FROM tbl_os_extra
							WHERE os = $os";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)) {
				
						$os_reinc = pg_result($res,0,0);
						echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $os_reinc</a><br>";
				
					}

				}
				else {

					$sql = "SELECT os,sua_os,posto
					FROM tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   serie   = '$serie'
					AND     os     <> $os
					AND     fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.posto=$login_posto
					ORDER BY tbl_os.os DESC
					$limit_reincidencia";

					$res2 = pg_query ($con,$sql);
	
					if (pg_num_rows ($res2) > 0) {
						for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
							$sos_reinc  = trim(pg_fetch_result($res2,$i,sua_os));
							$os_reinc   = trim(pg_fetch_result($res2,$i,os));
							$posto_reinc   = trim(pg_fetch_result($res2,$i,posto));
							if($posto_reinc == $login_posto){
								echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
							} else {
								### Não pode mostrar o número da OS reincidente de outro posto!!!!
								### Alterado Samuel 26/04/2010 - HD 228477
								### echo "» $sos_reinc<br>";
							}
						}
					}
				}
			}
		}elseif($status_os==68){
			echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.e.nota.fiscal.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		}elseif($status_os==70){
			echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		}elseif($status_os==95){
			echo strtoupper(traduz("ordem.de.servico.com.mesma.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior",$con,$cook_idioma)).": <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		} else {
			echo traduz("os.reincidente",$con,$cook_idioma).":<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";
		}
		echo "";
		echo "</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}

if ($login_fabrica == 94 && $login_posto == 114768 ) {

	$sql = "SELECT tbl_os.sua_os
				FROM tbl_os_campo_extra 
				JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os_troca_origem AND tbl_os.fabrica = tbl_os_campo_extra.fabrica
				WHERE tbl_os_campo_extra.os = $os
				AND tbl_os_campo_extra.fabrica = $login_fabrica";
				
	$res = pg_query($con,$sql);
	
	if ( pg_num_rows($res) ) {
	
		echo '<table style="border: #D3BE96 1px solid; background-color: #FCF0D8" align="center" width="700">
					<tr>
						<td align="center"><font size="1">OS de Origem: &nbsp;'.pg_result($res,0,0).'</font></td>
					</tr>
				</table>';
	
	}

}

if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################

if ((strlen($tipo_atendimento)>0) and ($login_fabrica==1 OR $login_fabrica == 96)) {?>
<center>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='tabela'>
<TR>
<?if($tipo_os==13 OR $login_fabrica == 96){?>
	<TD class="inicio" height='20' width='210' nowrap>&nbsp;&nbsp;<?  fecho("tipo.de.atendimento",$con,$cook_idioma); ?>: </TD>
	<TD class="conteudo" height='20' width='400' nowrap><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
<?} else {?>
	<TD class="inicio" height='20' width='110' nowrap>&nbsp;&nbsp;<?  fecho("troca.de.produto",$con,$cook_idioma);?>: </TD>
	<TD class="conteudo" height='20' width='130' nowrap><? echo " &nbsp;&nbsp;$nome_atendimento"; ?></TD>
	<TD class="inicio" height='20' width='50' >&nbsp;&nbsp;<? fecho("motivo",$con,$cook_idioma);?>: </TD>
	<?  $sql_2 = "SELECT tbl_os_status.observacao FROM tbl_os_status JOIN tbl_os using(os) where os = '$os'; ";
		$res_2 = pg_query($con,$sql_2);
		if(pg_num_rows($res_2) > 0) $obs_status = pg_fetch_result($res_2,0,observacao);
	?>
	<TD class="conteudo" height='20'><? echo " &nbsp;&nbsp;$obs_status"; ?></TD>
<?}?>
</TR><?php

if ($login_fabrica == 1) { #HD 274932

	$sql_3 = "SELECT tbl_os_troca.observacao FROM tbl_os_troca JOIN tbl_os using(os) where os = '$os'";//HD 303195
	$res_3 = pg_query($con,$sql_3);

	if (pg_num_rows($res_3) > 0) {
		$obs_troca = pg_fetch_result($res_3,0,'observacao');?>
		<TR>
			<TD class="inicio" height='20' width='50' >&nbsp;&nbsp;<? fecho("obs",$con,$cook_idioma);?>: </TD>
			<TD class="conteudo" height='20' colspan='3'><? echo " &nbsp;&nbsp;$obs_troca"; ?></TD>
		</TR><?php
	}

}?>

</TABLE>
</center>
<?
}
if($login_fabrica ==1 AND strlen($os) > 0){ // HD 17284
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os_troca IS TRUE";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>".traduz("historico",$con,$cook_idioma)."</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>".traduz("motivo",$con,$cook_idioma).": $observacao_status</TD>";
			echo "</TR>";
		}
		echo "</TABLE></center>";
	}
}
//OR $login_fabrica ==50

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
	$sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
				  tbl_admin.login
			FROM tbl_os_status
			JOIN tbl_os using(os)
			JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			WHERE os=$os
			AND   tbl_os.os_reincidente IS TRUE
			AND   tbl_os_status.extrato IS NULL
			AND status_os IN (132,19)
			AND status_os_ultimo = 19
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		$data        = pg_fetch_result($res2,0,'data');
		$login       = pg_fetch_result($res2,0,'login');

		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' width='60%'>Admin(APROVOU REINCIDÊNCIA)</TD>";
		echo "<TD class='inicio'>Data</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo'>$login</TD>";
		echo "<TD class='conteudo'>$data</TD>";
		echo "</tr>";
		echo "</TABLE>";
	}
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
	$sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
				  tbl_admin.login
			FROM tbl_os_status
			JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			WHERE os=$os
			AND   tbl_os_status.extrato IS NULL
			AND status_os IN (103,104)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		$data        = pg_fetch_result($res2,0,'data');
		$login       = pg_fetch_result($res2,0,'login');

		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' width='60%'>Admin(APROVOU NÚMERO DE SÉRIE)</TD>";
		echo "<TD class='inicio'>Data</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo'>$login</TD>";
		echo "<TD class='conteudo'>$data</TD>";
		echo "</tr>";
		echo "</TABLE>";
	}
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 209166
	$sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
				  tbl_admin.login
			FROM tbl_os_status
			JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			WHERE os=$os
			AND   tbl_os_status.extrato IS NULL
			AND status_os IN (99,100,101)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		$data        = pg_fetch_result($res2,0,'data');
		$login       = pg_fetch_result($res2,0,'login');

		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' width='60%'>Admin(APROVOU KM)</TD>";
		echo "<TD class='inicio'>Data</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo'>$login</TD>";
		echo "<TD class='conteudo'>$data</TD>";
		echo "</tr>";
		echo "</TABLE>";
	}
}

if($login_fabrica ==35 AND strlen($os) > 0){ // HD 56418
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (13,19,127)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>".traduz("historico",$con,$cook_idioma)."</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
		}
		echo "</TABLE></center>";
	}
}

if(($login_fabrica ==14 or $login_fabrica == 52) AND strlen($os) > 0){ // HD 65661
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  tbl_os_status.observacao,
				  status_os
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			JOIN tbl_os using(os)
			WHERE os=$os
			AND   tbl_os.os_reincidente IS TRUE
			AND   tbl_os_status.extrato IS NULL
			AND status_os IN (13,19)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>".traduz("historico",$con,$cook_idioma)."</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$status_os       = pg_fetch_result($res2,$i,status_os);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status";
			if($status_os == 13) {
				echo "- Motivo: $observacao_status";
			}
			if($status_os == 19) {
				echo " da reincidência";
			}
			echo "</TD>";
			echo "</tr>";
		}
		echo "</TABLE></center>";
	}
}


if ($excluida == "t") {
?>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela' >
<TR>
    <TD  bgcolor="#FFE1E1" height='20'>

	<h1>
	<?
	if ($login_fabrica==20 AND $os_recusada =='t'){
		#HD 13940
		echo strtoupper(traduz("os.recusada",$con,$cook_idioma ))." - ".$status_recusa_observacao;
	} else {
		echo strtoupper(traduz("ordem.de.servico.excluida",$con,$cook_idioma));
	}
	?>
	</h1>
	</TD>
	<?
		if($login_fabrica==3 AND strlen($os)>0){
			$sqlE = "SELECT tbl_admin.login
					 FROM tbl_os
					 JOIN tbl_admin on tbl_admin.admin = tbl_os.admin_excluida
					 WHERE tbl_os.os = $os";
			$resE = pg_exec($con,$sqlE);

			if(pg_numrows($resE)>0){
				$admin_nome = pg_result($resE,0,login);
				echo "<TD bgcolor='#FFE1E1' height='20'>";
					echo "<h1>Admin exclusão: $admin_nome</h1>";
				echo "</TD>";
			}
		}
	?>
</TR>
</TABLE>
</center>
<?
}

//HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
if ($login_fabrica == 81) {
	$sql = "SELECT troca_revenda FROM tbl_os_troca WHERE os=$os ";
	$res_troca_revenda = pg_query($con, $sql);

	if (pg_num_rows($res_troca_revenda)) {
		$troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
	}
}

if ($troca_revenda == "t") {
	echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
	echo "<TR height='30'>";
	echo "<TD align='left' colspan='3'>";
	echo "<font family='arial' size='2' color='#ffffff'><b>";
	echo "Autorização de Troca pela Revenda";
	echo "</b></font>";
	echo "</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='titulo3' style='text-align:left' height='15' >Responsável</TD>";
	echo "<TD class='titulo3' style='text-align:left' height='15' >Data</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $troca_admin;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</TD>";
	echo "<TD class='conteudo' height='15' nowrap>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $data_fechamento ;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";
}
// HD 11068 8/1/2008
############################################################################
elseif ($ressarcimento == "t") {
	echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
	echo "<TR height='30'>";
	echo "<TD align='left' colspan='3'>";
	echo "<font family='arial' size='2' color='#ffffff'><b> ";
	fecho ("ressarcimento.financeiro",$con,$cook_idioma);
	echo "</b></font>";
	echo "</TD>";
	echo "</TR>";

	//4/1/2008 HD 11068
	if($login_fabrica == 45 or $login_fabrica == 11){
		$sql = "SELECT
					observacao,descricao
				FROM tbl_os_troca
				LEFT JOIN tbl_causa_troca USING (causa_troca)
				WHERE tbl_os_troca.os = $os";
		$resY = pg_query ($con,$sql);

		if (pg_num_rows ($resY) > 0) {
			$troca_observacao = pg_fetch_result ($resY,0,observacao);
			$troca_causa      = pg_fetch_result ($resY,0,descricao);
		}
	}
	echo "<tr>";
	echo "<TD class='titulo2'  height='15' >".traduz("responsavel",$con,$cook_idioma)."</TD>";
	echo "<TD class='titulo2'  height='15' >".traduz("data",$con,$cook_idioma)."</TD>";
	if($login_fabrica == 45){
		echo "<TD class='titulo2'  height='15' >".traduz("observacao",$con,$cook_idioma)."</TD>";
	}elseif($login_fabrica == 11){
		echo "<TD class='titulo2'  height='15' >".traduz("causa",$con,$cook_idioma)."</TD>";
	} else {
		echo "<TD class='titulo3'  height='15' >&nbsp;</TD>";
	}
	echo "</tr>";

	echo "<tr>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $troca_admin;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	if($login_fabrica ==11) { // HD 56237
		echo $data_ressarcimento;
	} else {
		echo $data_fechamento ;
	}
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";

	if($login_fabrica == 45){
		echo "<TD class='conteudo' height='15' width='80%'>$troca_observacao</td>";
	}elseif($login_fabrica == 11){
		echo "<TD class='conteudo'  height='15' >$troca_causa</TD>";
	} else {
		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
	}
	echo "</tr>";

	if($login_fabrica==11) { // hd 56237
		echo "<tr>";
		echo "<TD class='conteudo' height='15' colspan='100%'>OBS: $troca_observacao</td>";
		echo "</tr>";
	}

	echo "</table>";
}
############################################################################

##########################################################################################
####################### INFORMÇÕES DE TROCA TECTOY HD 311414 24/03/2011 ##################
##########################################################################################

if ( $login_fabrica == 6 ){
	
	$sql_peca_tectoy = "
							SELECT 
								tbl_os_troca.peca
							from tbl_os_troca
							where os = $os
							
						";
							
	$res_peca_tectoy = pg_query ($con,$sql_peca_tectoy);
	if ( pg_num_rows($res_peca_tectoy)>0 ) {
	
		$peca_troca_tectoy = pg_result($res_peca_tectoy,0,"peca");
		
		if ( strlen($peca_troca_tectoy)>0){
			$sql_peca_desc_tectoy = "SELECT descricao from tbl_peca where peca=$peca_troca_tectoy";
			
			$res_peca_desc_tectoy = pg_query($con,$sql_peca_desc_tectoy);
			
			if ( pg_num_rows($res_peca_desc_tectoy)>0 ){
				$peca_desc_troca_tectoy = pg_result($res_peca_desc_tectoy,0,'descricao');
			}
		}
		
		
	}
	
		
		
		if ($peca_desc_troca_tectoy){
			echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
				echo "<tr>";
				
					echo "<td class='inicio' align='center' colspan='100%'>";
						echo "Informações de Troca";
					echo "</td>";
					
				echo "</tr>";
			
				echo "<tr>";
					echo "<td class='conteudo' align='center' colspan='100%'>";
						echo "Trocado para o produto: ";
						echo "$peca_desc_troca_tectoy";
					echo "</td>";
				echo "</tr>";
			echo "</table>";
		}
	
	
}

########################### INFORMÇÕES DE TROCA TECTOY - FIM #############################

// Verifica se o pedido de peça foi cancelado ou autorizado caso a peça esteja bloqueada para garantia
#Fabrica 25 - HD 14830
# HD 13618 - NKS
# HD 12657 - Dynacom
if ($historico_intervencao) {
	if(in_array($login_fabrica, array(40))){
        $sql_status = "SELECT
                        status_os,
                        observacao,
                        tbl_admin.login,
                        to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
                    FROM tbl_os_status
                        LEFT JOIN tbl_admin USING(admin)
                    WHERE os=$os
                    ORDER BY tbl_os_status.data DESC LIMIT 1";    
    }else{
        $sql_status = "SELECT    status_os,
                    observacao,
                    tbl_admin.login,
                    to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data,
                    tbl_os_status.data as date
                    FROM tbl_os_status
                    LEFT JOIN tbl_admin USING(admin)
                    WHERE os=$os
                    AND status_os IN (72,73,62,64,65,67,87,88,98,99,100,101,102,103,104,116,117)
                    ORDER BY date DESC LIMIT 1";
    }
	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado > 0){
		$status_os          = trim(pg_fetch_result($res_status,0,status_os));
		$status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
		$data_status        = trim(pg_fetch_result($res_status,0,data));
		$intervencao_admin  = trim(pg_fetch_result($res_status,0,login));

		if ($status_os==88){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==87){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". ";
				if ($login_fabrica==1){
					echo "<br>".traduz("entrar.em.contato.com.o.suporte.de.sua.regiao",$con,$cook_idioma)."</b>";
				} else {
					echo "<br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma)."</b>";
				}
			echo "</div>
				</center><br>
			";
			if ($login_fabrica==1){
				echo "<script language='JavaScript'>alert('".traduz("os.em.intervencao.gentileza.entre.em.contato.com.o.suporte.de.sua.regiao",$con,$cook_idioma)."');</script>";
			}
		}
		if ($status_os==72 or $status_os==116){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==73 or $status_os==117){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==62){
			if($login_fabrica == 106){
				echo "<br>
					<center>
					<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
						<b style='font-size:12px;color:red;width:100%'>Ordem de Serviço sob intervenção do fabricante,<br>
						favor aguardar a liberação para proceder com o reparo do produto, <br> qualquer dúvida favor entrar em contato pelo e-mail sac@houston.com.br ou pelo 0800 979 3434 </b><br>
						<b style='font-size:11px'>$status_observacao</b>
					</div>
					</center><br>
				";
			} else{
				echo "<br>
					<center>
					<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
						<b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
						<b style='font-size:11px'>$status_observacao</b>
					</div>
					</center><br>
				";
			}
		}
		if ($status_os==118){
            echo "<br>
                <center>
                <div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
                    <b style='font-size:14px;color:red;width:100%'>".traduz("os.sob.intervencao.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
                    <b style='font-size:11px'>$status_observacao</b>
                </div>
                </center><br>
            ";
		}

		if ($status_os==64){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>".traduz("os.liberada.da.assistencia.tecnica.da.fabrica",$con,$cook_idioma)."</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if($login_fabrica==50 || $login_fabrica == 74){
			# HD 42933 - Alterei para a Colormaq, não estava mostrando a
			#   última intervenção na OS
			/*if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){*/
				$sql_status = #"select descricao from tbl_status_os where status_os = $status_os";
							"SELECT
								tbl_os_status.status_os,
								tbl_os_status.observacao,
								tbl_admin.login,
								tbl_status_os.descricao,
								to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
							FROM tbl_os_status
							JOIN tbl_status_os USING (status_os)
							LEFT JOIN tbl_admin USING (admin)
							WHERE os = $os
							ORDER BY tbl_os_status.data DESC LIMIT 1";
				$res_status = pg_query($con, $sql_status );
				if(pg_num_rows($res_status)>0){
					$data_status = pg_fetch_result($res_status, 0, data);;
					$descricao_status = pg_fetch_result($res_status, 0, descricao);
					$intervencao_admin = pg_fetch_result($res_status, 0, login);
					$descricao_status = pg_fetch_result($res_status, 0, descricao);
					$status_observacao = pg_fetch_result($res_status, 0, observacao);

					echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
							echo "<TR>";
								echo "<TD class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='4'>&nbsp;".traduz("status.os",$con,$cook_idioma )."</TD>";
							echo "</TR>";
							echo "<TR>";
								echo "<TD class='inicio'>".traduz("data",$con,$cook_idioma )."</TD>";
								echo "<TD class='inicio'>".traduz("admin",$con,$cook_idioma)."</TD>";
								echo "<TD class='inicio'>".traduz("status",$con,$cook_idioma)."</TD>";
								echo "<TD class='inicio'>".traduz("motivo",$con,$cook_idioma)."</TD>";
							echo "</TR>";
							echo "<TR>";
								echo "<TD class='conteudo' width='10%'>&nbsp; $data_status </TD>";
								echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
								echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
								echo "<TD class='conteudo'>&nbsp;$status_observacao </TD>";
							echo "</TR>";
					echo "</TABLE>";
				}
		#}
		}
	}
}

if(strlen($extrato)>0 AND $login_fabrica==50){ //HD 61132
	echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
		echo "<TR>";
			echo "<TD class='inicio'>".traduz("extrato",$con,$cook_idioma)."</TD>";
			echo "<TD class='inicio'>".traduz("previsao",$con,$cook_idioma)."</TD>";
			echo "<TD class='inicio'>".traduz("pagamento",$con,$cook_idioma)."</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$extrato </TD>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$data_pagamento </TD>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$data_previsao </TD>";
		echo "</TR>";
	echo "</TABLE>";
}


if($login_fabrica ==50 AND strlen($os) > 0){ // HD 37276
	# HD 42933 - Retirado o resultado da tela, deixado apenas um link
	#   que abre um pop-up mostrando todo o histórico da OS
	/*$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (98,99,100, 101,102,103,104,116,117)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){*/
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>";
		?>
		<a style='cursor:pointer;' onclick="javascript:window.open('historico_os.php?os=<? echo $os ?>','mywindow','menubar=1,resizable=1,width=500,height=350')">&nbsp;<?php
		fecho("ver.historico.da.os",$con,$cook_idioma);?></a>
		<?php
		echo "</TD>";
		echo "</TR>";
		/*for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
			echo "</TR>";
		}*/
		echo "</TABLE></center>";
	#}
}

////////////////////////////// OS RETORNO  - FABIO 10/01/2007  - INICIO //////////////////////////////
// informações de postagem para envio do produto para a Fábrica
// ADICIONADO POR FABIO 03/01/2007
// Dynacom - HD 12657
if ($login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==14 OR $login_fabrica==6 OR $login_fabrica==11){
	$sql = "SELECT  nota_fiscal_envio,
				TO_CHAR(data_nf_envio,'DD/MM/YYYY')  AS data_nf_envio,
				numero_rastreamento_envio,
				TO_CHAR(envio_chegada,'DD/MM/YYYY')  AS envio_chegada,
				nota_fiscal_retorno,
				TO_CHAR(data_nf_retorno,'DD/MM/YYYY')  AS data_nf_retorno,
				numero_rastreamento_retorno,
				TO_CHAR(retorno_chegada,'DD/MM/YYYY')  AS retorno_chegada
			FROM tbl_os_retorno
			WHERE   os = $os;";
	$res = pg_query ($con,$sql);
	if (@pg_num_rows($res)==1){
		$retorno=1;
		$nota_fiscal_envio            = trim(pg_fetch_result($res,0,nota_fiscal_envio));
		$data_nf_envio                = trim(pg_fetch_result($res,0,data_nf_envio));
		$numero_rastreamento_envio    = trim(pg_fetch_result($res,0,numero_rastreamento_envio));
		$envio_chegada                = trim(pg_fetch_result($res,0,envio_chegada));
		$nota_fiscal_retorno          = trim(pg_fetch_result($res,0,nota_fiscal_retorno));
		$data_nf_retorno              = trim(pg_fetch_result($res,0,data_nf_retorno));
		$numero_rastreamento_retorno  = trim(pg_fetch_result($res,0,numero_rastreamento_retorno));
		$retorno_chegada              = trim(pg_fetch_result($res,0,retorno_chegada));
	} else{
		$retorno=0;
	}
}

if ($retorno==1 AND strlen($nota_fiscal_envio)==0){
	$sql_status = "SELECT status_os, observacao
					FROM tbl_os_status
					WHERE os=$os
					AND status_os IN (72,73,62,64,65,87,88)
					ORDER BY data DESC LIMIT 1";
	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado==1){
		$status_os          = trim(pg_fetch_result($res_status,0,status_os));
		$status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
		if ($status_os==65){
			if ($login_fabrica==3){
				echo "<br>
					<center>
					<b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br>
					<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
						<b style='font-size:14px;color:red'>".strtoupper(traduz("urgente.produto.para.reparo",$con,$cook_idioma))."</b><br><br>
						<b style='font-size:14px'>BRITÂNIA ELETRODOMÉSTICOS LTDA</b>.<br>
						<b style='font-size:12px'>Rua Dona Francisca, 8300 Mod 4 e 5 Bloco A<br>
						Cep 89.239-270 - Joinville - SC<br>
						A/C ASSISTÊNCIA TÉCNICA</b>
					</div></center><br>
				";
			} else {
				echo "<br>
					<center>
					<b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br></center><br>
				";
			}
		}
		if ($status_os==72){
			echo "<br>
				<center>
				<b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
				<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
					<b style='font-size:12px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
				</div></center><br>
			";
		}
		if ($status_os==73){
			echo "<br>
				<center>
				<b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
				<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
					<b style='font-size:12px'>$status_observacao</b>
				</div></center><br>
			";
		}
	}
}

if ($retorno==1 AND strlen($msg_erro)>0){
	if (strpos($msg_erro,'date')){
		//$msg_erro = "Data de envio incorreto!";
	}
	echo "<center>
			<div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
				<b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro </b>
			</div></center>";
}else {
	if (strlen($msg)>0){
		echo "<center>
			<div style='font-family:verdana;width:400px;align:center;' align='center'>
				<b style='font-size:14px;color:black'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg</b>
			</div></center>";
	}
}
if (strlen($msg_erro)>0){
	echo "<center>
			<div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
				<b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro</b>
			</div></center>";
}

if ($retorno==1 AND !$nota_fiscal_envio AND !$data_nf_envio AND (!$numero_rastreamento_envio OR $login_fabrica==6 OR $login_fabrica == 14)) {
?>
<br>
<form name="frm_consulta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
    <TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px'><? echo strtoupper(traduz("preencha.os.dados.do.envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><br>
				<? echo traduz("numero.da.nota.fiscal",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_nota_fiscal" size="25" maxlength="6" value="<? echo 	$nota_fiscal_envio_p ?>">
				<br>
				<? echo  traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="txt_data_envio" size="25" maxlength="10" value="<? echo $data_envio_p ?>">
				<br>

				<?  if ($login_fabrica <> 6){ ?>
					<? echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="txt_rastreio" size="25" maxlength="13" value="<? echo $numero_rastreio_p ?>"> <br>
					Ex.: SS987654321
					<br>
				<? } ?>

				<center><input type="hidden" name="btn_acao" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='gravar' ; document.frm_consulta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
				</TD>
			</TR>
    </TABLE>
</form><br><br>
<?
}

/***************************************************************************************************/
if ($login_fabrica==51){ //HD 48003
	$sql_status = "SELECT
				status_os,
				observacao,
				tbl_admin.login,
				to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
				FROM tbl_os_status
				LEFT JOIN tbl_admin USING(admin)
				WHERE os=$os
				AND status_os IN (72,73,62,64,65,87,88,98,99,100,101,102,103,104,116,117,128)
				ORDER BY tbl_os_status.data DESC LIMIT 1";

	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado==1){
		$data_status        = trim(pg_fetch_result($res_status,0,data));
		$status_os          = trim(pg_fetch_result($res_status,0,status_os));
		$status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
		$intervencao_admin  = trim(pg_fetch_result($res_status,0,login));

		if (strlen($intervencao_admin)>0 AND $login_fabrica<>50){
			$intervencao_admin = "<br><b>OS em intervenção colocada pela Fábrica ($intervencao_admin)</b>";
		}

		if ($status_os==72 or $status_os==116) {
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>A peça solicitada necessita de autorização. <br>O PA aguarda a fábrica analisar o pedido</b>
					$intervencao_admin
				</div>
				</center><br>
			";
		}
		if ($status_os==73 or $status_os==117) {
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==62){			
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
					<b style='font-size:11px'>$status_observacao</b>
					<br>entrar em contato com a GAMA ITALY pelo telefone (11) 2940-7400
				</div>
				</center><br>";
		}
		if ($status_os==64){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>OS Liberada da Assistência Técnica da Fábrica</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==88){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==87){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>A peça solicitada necessita de autorização. ";
				if ($login_fabrica==1){
					echo "<br>Entrar em contato com o Suporte de sua região.</b>";
				} else {
					echo "<br>Aguarde a fábrica analisar seu pedido.</b>";
				}
			echo "</div>
				</center><br>
			";
		}

	}
}
/**********************************************************************************************/

#HD 44202 - intervenção OS aberta
if (strlen($status_os_aberta)>0 AND ($login_fabrica==3 or $login_fabrica==14) ) {
	$status_os_aberta_inter= "";
	if ($status_os_aberta == 122 ||$status_os_aberta == 141) {
		$status_os_aberta_inter = "<br><b style='font-size:11px'>". traduz("os.com.intervencao.da.fabrica.aguardando.liberacao",$con,$cook_idioma). "</b>";
	}
	echo "<br>
		<center>
		<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
			<b style='font-size:14px;color:red;width:100%'>".traduz("status.os",$con,$cook_idioma)."</b>
			 $status_os_aberta_inter <br>
			<b style='font-size:11px'>$status_os_aberta_obs </b>
		</div>
		</center><br>";

	if ($status_os_aberta == 120 || $status_os_aberta == 140) {
	?>
	<form name="frm_os_aberta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
		<TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo traduz("os.em.intervencao",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px'><? echo traduz("digite.a.justificativa",$con,$cook_idioma).":";?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><br>
				<? echo traduz("justificativa",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_justificativa_os_aberta" size="60" maxlength="60" value="">
				<br>
				<center><input type="hidden" name="btn_acao" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os_aberta.btn_acao.value == '' ) { document.frm_os_aberta.btn_acao.value='gravar_justificativa' ; document.frm_os_aberta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar_justificativa' border='0' style="cursor:pointer;"></center><br>
				</TD>
			</TR>
		</TABLE>
	</form>
	<?
	}
}

if ($retorno==1 AND $nota_fiscal_envio AND $data_nf_envio AND ($numero_rastreamento_envio OR $login_fabrica==6 or $login_fabrica == 14)) {
	if (strlen($envio_chegada)==0){
		echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.enviado.mas.a.fabrica.ainda.nao.confirmou.seu.recebimento",$con,$cook_idioma).".<br> .".traduz("aguarde.a.fabrica.confirmar.o.recebimento.efetuar.o.reparo.e.retornar.o.produto.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
	}else {
		if (strlen($data_nf_retorno)==0){
			echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.recebido.pela.fabrica.em.%",$con,$cook_idioma,array($envio_chegada))."<br> ".traduz("aguarde.a.fabrica.efetuar.o.reparo.e.enviar.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
		}
		else{
			if (strlen($retorno_chegada)==0){
				echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.reparo.do.produto.foi.feito.pela.fabrica.e.foi.enviado.ao.seu.posto.em.%",$con,$cook_idioma,array($data_nf_retorno))."<br>".traduz("confirme.apos.o.recebimento",$con,$cook_idioma)."</b><BR>";
			}
			else {
				#echo "<BR><b style='font-size:14px;color:#990033'>O REPARO DO PRODUTO FOI FEITO PELA FÁBRICA.</b><BR>";
			}
		}
	}
	?>
	<?
	if ($nota_fiscal_retorno AND $retorno_chegada=="") {?>
	<form name="frm_confirm" method="post" action="<?echo "$PHP_SELF?os=$os&chegada=$os"?>">
		<TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
				<TR>
					<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> <?echo traduz("confirme.a.data.do.recebimento",$con,$cook_idioma);?></TD>
				</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'><?echo traduz("o.produto.foi.enviado.para.seu.posto.confirme.seu.recebimento",$con,$cook_idioma);?></TD>
			</TR>
					<TD class="titulo3"><br>
					<?echo traduz("data.da.chegada.do.produto",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_data_chegada_posto" size="20" maxlength="10" value=""> <br><br>
					<center>
					<input type="hidden" name="btn_acao" value="">
					<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_confirm.btn_acao.value == '' ) { document.frm_confirm.btn_acao.value='confirmar' ; document.frm_confirm.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
					</TD>
				</TR>
        </TABLE>
    </form>
    <?}?>

    <br>
    <TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'><?echo traduz("informacoes.do.envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo traduz("numero.da.nota.fiscal.de.envio",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_envio ?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo $data_nf_envio ?></TD>
			</TR>
			<?  if ($login_fabrica <> 6){ ?>
			<TR>
				<TD class="titulo3"><?echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo "<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_envio"."BR' target='_blank'>$numero_rastreamento_envio</a>" ?></TD>
			</TR>
			<? } ?>
			<TR>
				<TD class="titulo3"><?echo traduz("data.da.chegada.a.fabrica",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo $envio_chegada; ?></TD>
			</TR>
			<TR>
				<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo traduz("retorno.do.produto.da.fabrica.ao.posto",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'><?echo traduz("informacoes.do.retorno.do.produto.ao.posto",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo traduz("numero.da.nota.fiscal.do.retorno",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_retorno ?></TD>
			</TR>
			<TR>
				<TD class="titulo3"><?echo traduz("data.do.retorno",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo $data_nf_retorno ?></TD>
			</TR>
			<?  if ($login_fabrica <> 6){ ?>
			<TR>
				<TD class="titulo3"><?echo traduz("numero.o.objeto.pac.de.retorno",$con,$cook_idioma);?> </TD>
				<TD class="conteudo" >&nbsp;<? echo ($numero_rastreamento_retorno)?"<a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>":""; ?></TD>
			</TR>
			<? } ?>
			<TR>
				<TD class="titulo3" ><?echo traduz("data.da.chegada.ao.posto",$con,$cook_idioma);?></TD>
				<TD class="conteudo" >&nbsp;<? echo $retorno_chegada ?></TD>
			</TR>
    </TABLE>
<br><br>
<?
}

//////////////// OS RETORNO - FABIO 10/01/2007  - FIM  ///////////////////////////////////

##########################################################################################
####################### INFORMÇÕES DE TROCA LENOXX HD 20774 04/06/2008 ###################
##########################################################################################
if($login_fabrica==11 or $login_fabrica==3){//HD 69245
	if ($ressarcimento <> "t") {
		if ($troca_garantia == "t") {
			echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
			echo "<TR height='30'>";
			echo "<TD align='left' colspan='4'>";
			echo "<font family='arial' size='2' color='#ffffff'><b>";
			echo "&nbsp;".traduz("produto.trocado",$con,$cook_idioma);
			echo "</b></font>";
			echo "</TD>";
			echo "</TR>";

			echo "<tr>";
			if($login_fabrica<>3) echo "<TD align='left' class='titulo4'  height='15' >".traduz("responsavel",$con,$cook_idioma)."</TD>";
			echo "<TD align='left' class='titulo4'  height='15' >".traduz("data",$con,$cook_idioma)."</TD>";
			echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("trocado.por",$con,$cook_idioma)."</TD>";
		#	echo "<TD class='titulo'  height='15' >&nbsp;</TD>";
			echo "</tr>";
			$sql = "SELECT TO_CHAR(data,'dd/mm/yyyy hh:mm') AS data            ,
							setor                                              ,
							situacao_atendimento                               ,
							tbl_os_troca.observacao                            ,
							tbl_peca.referencia             AS peca_referencia ,
							tbl_peca.descricao              AS peca_descricao  ,
							tbl_causa_troca.descricao       AS causa
					FROM tbl_os_troca
					JOIN tbl_peca        USING(peca)
					JOIN tbl_causa_troca USING(causa_troca)
					JOIN tbl_os          ON tbl_os_troca.os = tbl_os.os
					WHERE tbl_os_troca.os = $os
					AND  tbl_os.fabrica = $login_fabrica; ";
			$resX = pg_query ($con,$sql);
			if (pg_num_rows ($resX) > 0) {
				$troca_data           = pg_fetch_result ($resX,0,data);
				$troca_setor          = pg_fetch_result ($resX,0,setor);
				$troca_situacao       = pg_fetch_result ($resX,0,situacao_atendimento);
				$troca_observacao     = pg_fetch_result ($resX,0,observacao);
				$troca_peca_ref       = pg_fetch_result ($resX,0,peca_referencia);
				$troca_peca_des       = pg_fetch_result ($resX,0,peca_descricao);
				$troca_causa          = pg_fetch_result ($resX,0,causa);

				if($troca_situacao == 0) $troca_situacao = "Garantia";
				else                     $troca_situacao .= "% Faturado";

				echo "<tr>";
				if($login_fabrica<>3){
					echo "<TD class='conteudo' align='left' height='15' nowrap>";
					echo "&nbsp;&nbsp;&nbsp;";
					echo $troca_admin;
					echo "&nbsp;&nbsp;&nbsp;";
					echo "</td>";
				}

				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $troca_data;
				echo "&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				echo "<TD colspan='2' class='conteudo' align='left' height='15' nowrap >";
				echo $troca_peca_ref . " - " . $troca_peca_des;
				echo "</td>";
				echo "</tr>";
				if($login_fabrica<>3){
					echo "<tr>";
					echo "<TD align='left' class='titulo4'  height='15' >".traduz("setor",$con,$cook_idioma)."</TD>";
					echo "<TD align='left' class='titulo4'  height='15' >".traduz("situacao.do.atendimento",$con,$cook_idioma)."</TD>";
					if($login_fabrica==11) {
						echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("causa",$con,$cook_idioma)."</TD>";
					} else {
						echo "<TD align='left' class='titulo4'  height='15' colspan='2'>".traduz("causa.da.troca",$con,$cook_idioma)."</TD>";
					}
					echo "</tr>";
					echo "<tr>";
					echo "<TD class='conteudo' align='left' height='15' nowrap>";
					echo "&nbsp;&nbsp;&nbsp;";
					echo $troca_setor;
					echo "&nbsp;&nbsp;&nbsp;";
					echo "</td>";
					echo "<TD class='conteudo' align='left' height='15' nowrap>";
					echo "&nbsp;&nbsp;&nbsp;";
					echo $troca_situacao;
					echo "&nbsp;&nbsp;&nbsp;";
					echo "<TD class='conteudo' align='left' height='15' nowrap colspan='2'>";
					echo $troca_causa;
					echo "</td>";
					echo "</tr>";

					echo "<tr>";
					echo "<TD class='conteudo' align='left' height='15'  colspan='4'><b>OBS:</b>";
					echo $troca_observacao;
					echo "</td>";
					echo "</tr>";
			#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
					echo "</tr>";
				}

			}else if($login_fabrica<>3) {
				$sql = "SELECT tbl_peca.referencia , tbl_peca.descricao
						FROM tbl_peca
						JOIN tbl_os_item USING (peca)
						JOIN tbl_os_produto USING (os_produto)
						JOIN tbl_os_extra USING (os)
						WHERE tbl_os_produto.os = $os
						AND   tbl_peca.produto_acabado IS TRUE ";
				$resX = pg_query ($con,$sql);
				if (pg_num_rows ($resX) > 0) {
					$troca_por_referencia = pg_fetch_result ($resX,0,referencia);
					$troca_por_descricao  = pg_fetch_result ($resX,0,descricao);
				}


				echo "<tr>";
				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $troca_admin;
				echo "&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $data_fechamento;
				echo "&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				echo "<TD class='conteudo' align='left' height='15' nowrap >";
				echo $troca_por_referencia . " - " . $troca_por_descricao;
				echo "</td>";

		#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
				echo "</tr>";
			}
		}
	}
}
########################### INFORMÇÕES DE TROCA LENOXX - FIM #############################



?>

<?
// Mostra número do Extrato que esta OS's está - A pedido da Edina
// Fabio
// 29/12/2006
if ($login_fabrica==2){
	if (strlen(trim($data_finalizada))>0){
		$query = "SELECT extrato,
					to_char(data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
						data_vencimento
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				JOIN tbl_extrato using(extrato)
				LEFT JOIN tbl_extrato_pagamento using(extrato)
				WHERE tbl_os.os = $os
				AND tbl_os.fabrica = 2;";
		$result = pg_query ($con,$query);
		if (pg_num_rows ($result) > 0) {
			$extrato = pg_fetch_result ($result,0,extrato);
			$data_pg = pg_fetch_result ($result,0,data_pagamento);
			$data_vcto = pg_fetch_result ($result,0,data_vencimento);
			?>
			<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
					<TR ><TD class='inicio' style='text-align:center;'  colspan='4'><?echo traduz("extrato",$con,$cook_idioma);?></td></tr>
					<tr>
						<TD class='titulo' style='padding:0px 5px;' width='120' ><?echo traduz("n.extrato",$con,$cook_idioma);?></td>
						<td    class='conteudo' style='padding:0px 5px;' width='226' >
							<a href='os_extrato_detalhe.php?extrato=<? echo $extrato; ?>&posto=<? echo $login_posto; ?>' ><? echo $extrato; ?></a>
						</td>
						<td class='titulo' style='padding:0px 5px;' width='120' ><? echo traduz("data.do.pagamento",$con,$cook_idioma);?></td>
						<td class='conteudo' style='padding:0px 5px;' width='226' >	&nbsp;<b><? echo $data_pg; ?></b>
						</TD>
					</TR>
			</TABLE><br>
			<?
		}
	}
}// fim mostra número do Extrato

if ($login_fabrica == 3 AND $login_e_distribuidor == "t"){
?>
	<center>
	<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
			<TR>
				<TD class="titulo" colspan="4"><?echo traduz("posto",$con,$cook_idioma);?></TD>
			</TR>
			<TR>
				<TD class="conteudo" colspan="4"><? echo "$posto_codigo - $posto_nome"; ?></TD>
			</TR>
	</TABLE>
	</center>
<?
}
if($login_fabrica == 96 and !empty($motivo)){
	$linhas = 5;
}
else{
	$linhas = 4;
}
$td_os_rowspan = $linhas + usaDataConserto($login_posto, $login_fabrica);


//hd-744257
if($login_fabrica == 80){

	$sql = "SELECT os, status_os, observacao, extrato FROM tbl_os_status WHERE os = $os AND status_os = 13 AND extrato notnull;";

	$res = pg_query($con, $sql);
	if(pg_num_rows($res) > 0){
		$os         = pg_fetch_result($res, 0, os);
		$status_os  = pg_fetch_result($res, 0, status_os);
		$observacao = pg_fetch_result($res, 0, observacao);
		$extrato    = pg_fetch_result($res, 0, extrato);

		echo "<table align='center' width='700' cellspacing='1' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
				echo "<td>";
					echo "ORDEM DE SERVIÇO RECUSADA DO EXTRATO: ".$extrato;
				echo "</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td>";
					echo "<b>MOTIVO DA RECUSA: <font color='red'>".$observacao."</font></b>";
				echo "</td>";
			echo "</tr>";
		echo "</table>";

	}
}
?>


<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <tr >
        <td rowspan='<?=$td_os_rowspan?>' class='conteudo' width='300' >
            <center>
                <?echo traduz("os.fabricante",$con,$cook_idioma);?><br>&nbsp;
                <b><FONT SIZE='6' COLOR='#C67700'>
                <?
                if ($login_fabrica == 1) echo $posto_codigo;

                if (strlen($consumidor_revenda) > 0 AND $login_fabrica <> 87) 
                    echo $sua_os ."</FONT> - ". $consumidor_revenda;
                else 
                    echo $sua_os;

                if($login_fabrica==3){ echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";}

				 if($login_fabrica==104){ 
					 $marca_nome = ($marca_nome == "DWT") ? $marca_nome : "Vonder";
					 echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";
				}

                if(strlen($sua_os_offline)>0){
                    echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
                        echo "<tr >";
                            echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>";
                                if($login_fabrica==20) fecho ("os.interna",$con,$cook_idioma);
                                else                   fecho ("os.off.line",$con,$cook_idioma);
                                echo " - $sua_os_offline";
                            echo "</center></td>";
                        echo "</tr>";
                    echo "</table>";
                }?>
            </b></center>
        </td>
        <td class='inicio' height='15' colspan='4'>&nbsp;<?echo traduz("datas.da.os",$con,$cook_idioma);?></td>
    </TR>
    <TR>
        <td class='titulo' width='100' height='15'><?echo traduz("abertura",$con,$cook_idioma);?></TD>
        <td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
        <td class='titulo' width='100' height='15'><?echo traduz("digitacao",$con,$cook_idioma);?></TD>
        <td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
    </tr>
    <tr>
        <td class='titulo' width='100' height='15'><?echo traduz("fechamento",$con,$cook_idioma);?></TD>
        <td class='conteudo' width='100' height='15' id='data_fechamento'>&nbsp;<? echo $data_fechamento ?></td>
        <td class='titulo' width='100' height='15'><?echo traduz("finalizada",$con,$cook_idioma);?></TD>
        <td class='conteudo' width='100' height='15' id='finalizada'>&nbsp;<? echo $data_finalizada ?></td>

    </tr>
    <tr>
        <TD class="titulo"  height='15'><?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
        <td class='titulo' width='100' height='15'><?echo traduz("fechado.em",$con,$cook_idioma);?>

 </TD>
        <td class='conteudo' width='100' height='15'>&nbsp;
        <?
        if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
			//HD 204146: Fechamento automático de OS
			if ($login_fabrica == 3) {
				$sql = "SELECT sinalizador FROM tbl_os WHERE os=$os";
				$res_sinalizador = pg_query($con, $sql);
				$sinalizador = pg_result($res_sinalizador, 0, sinalizador);
			}

			if ($sinalizador == 18) {
				echo "<font color='#FF0000'>FECHAMENTO<br>AUTOMÁTICO</font>";
			}
			else {
				$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
				$resD = pg_query ($con,$sql_data);

				if (pg_num_rows ($resD) > 0) {
					$total_de_dias_do_conserto = pg_fetch_result ($resD,0,'final');
				}
				if($total_de_dias_do_conserto==0) {
					fecho("no.mesmo.dia",$con,$cook_idioma) ;
				}
				else echo $total_de_dias_do_conserto;
				if($total_de_dias_do_conserto==1) {
					echo " ".traduz("dia",$con,$cook_idioma) ;
				}
				if($total_de_dias_do_conserto>1) {
					echo " ".traduz("dias",$con,$cook_idioma);
				}
			}
        } else {
            echo mb_strtoupper(traduz("nao.finalizado",$con,$cook_idioma));
        }
        ?>
        </td>
    </tr>
	<? if (usaDataConserto($login_posto, $login_fabrica)) { /*HD 13239 HD 14121 56101*/ ?>
		<tr>
		<td class='titulo' width='100' height='15'><?echo traduz("consertado",$con,$cook_idioma);?>&nbsp; </td>
		<td class='conteudo' width='100' height='15' colspan ='1' id='consertado'>&nbsp;
		<?
				$sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' ) as data_conserto
										FROM tbl_os
										WHERE os=$os";
				$resdc = pg_query ($con,$sql_data_conserto);
				if (pg_num_rows ($resdc) > 0) {
					$data_conserto= pg_fetch_result ($resdc,0,data_conserto);
				}
				if(strlen($data_conserto)>0){
					echo $data_conserto;
				} else {
					echo "&nbsp;";
				}
			echo "</td>";
			echo "<td class='titulo' width='100'height='15'></TD>";
			echo "<td class='conteudo' width='100' height='15'> </tr>";

		 }

		if(strlen($obs_reincidencia)>0){
		?>
			<tr><td colspan='5' bgcolor='#FF0000' size='2' align='center'><b><font color='#FFFF00'>Justifica: <?=$obs_reincidencia?></font></b></td></tr>
		<?}
		
		if(strlen($motivo_reincidencia_desc)>0 and $login_fabrica == 52){
		?>
			<tr><td colspan='5' bgcolor='#FF0000' size='2' align='center'><b><font color='#FFFF00'>Motivo da Reincidência: <?=$motivo_reincidencia_desc?></font></b></td></tr>
		<?}
		
		if($login_fabrica == 96 and !empty($motivo)){ //HD 399700 Mosta motivo caso OS for fora de Garantia?>
			<tr>
				<td class='titulo'>Motivo</td>
				<td colspan='3' class='conteudo'><?php echo $motivo; ?></td>
			</tr>
		<?
		}
	?>

</table>
<?
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
// adicionado para ibbl (90) HD#316365
if($login_fabrica==19 || $login_fabrica ==90){
	if(strlen($tipo_os)>0){
		$sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
		$ress = pg_query($con,$sqll);
		$tipo_os_descricao = pg_fetch_result($ress,0,0);
	}
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class="titulo"  height='15' width='90'>
		<?echo traduz("atendimento",$con,$cook_idioma);?>
	</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_atendimento.' - '.$nome_atendimento ?></TD>
	<?php if($login_fabrica != 90) { ?>
		<TD class="titulo"  height='15' width='90'><? echo traduz("motivo",$con,$cook_idioma);?></TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_os_descricao; ?></TD>
		<TD class="titulo" height='15' width='90'><?echo traduz("nome.do.tecnico",$con,$cook_idioma);?></TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
	<?php } else{ ?>
		<TD class="titulo"  height='15' width='90'>Recolhimento</TD>
		<TD class="conteudo" height='15'>
			&nbsp;<? echo $recolhimento == 'f' ? 'NÃO' : 'SIM'; ?>
		</TD>
		<?if (strlen($reoperacao_gas) > 0 and $login_fabrica==90){?>
			<TD class="titulo" height="15" width="110" nowrap>Reoperação de Gás</TD>
			<td class="conteudo" height="15">&nbsp;<?= $reoperacao_gas == 'f' ? "NÃO" : "SIM";?></td>
		<? 	} 
	 } ?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA LORENZETTI

// CAMPOS ADICIONAIS SOMENTE PARA BOSCH
if($login_fabrica==20 OR ($login_fabrica==15 and strlen($tipo_atendimento)>0)){
	if($login_fabrica==20 AND $tipo_atendimento==13 AND $tipo_troca==1){
		$tipo_atendimento = 00;
		$nome_atendimento = "Troca em Cortesia Comercial";
	}
	
	//HD 275256
	if($login_fabrica ==  15 and ($tipo_atendimento == 21 || $tipo_atendimento == 22) ){
		$sql = "select qtde_km from tbl_os where os=$os";
		$res = pg_query($con,$sql);
		
		$qtde_km = pg_result($res,0,0);
	}

	if ($login_fabrica == 15) {

		function get_status_img ($id) {

			switch ($id) {

				case 98 : $cor = 'status_amarelo.gif'; break;
				case 99 : $cor = 'status_verde.gif'; break;
				case 100: $cor = 'status_azul.gif'; break;
				case 101: $cor = 'status_vermelho.gif'; break;

				default : '';

			}

			return $cor;

		}

		$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE os = $os
					AND status_os IN (98,99,100,101)
					ORDER BY os_status DESC
					LIMIT 1";
		$res = pg_query($con,$sql);
		$status = @pg_result($res,0,0);

		$img         = get_status_img($status);
		$img = empty($img) ? 'status_verde.gif' : $img;

	}	
	
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class="titulo"  height='15' width='90'><? echo traduz("atendimento",$con,$cook_idioma);?></TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_atendimento.' - '.$nome_atendimento ?></TD>
	<?if( $tecnico_nome){?>
	<TD class="titulo" height='15'width='90'><?echo traduz("nome.do.tecnico",$con,$cook_idioma);?></TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
	<?}?>
	<?if($tipo_atendimento=='15' or $tipo_atendimento=='16'){?>
			<TD class="titulo"  height='15' width='90'><?echo traduz("promotor",$con,$cook_idioma);?></TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $promotor_treinamento ?></TD>
	<?}?>
</TR>
<?//HD 275256
if($login_fabrica ==  15 and ($tipo_atendimento == 21 || $tipo_atendimento == 22)){
?>
<TR>
	<TD class="titulo" height="15" width="90">Qtde. KM</TD>
	<TD class="conteudo" height="15">&nbsp;<?echo $qtde_km."km"; if ($login_fabrica == 15) echo '&nbsp;<img src="admin/imagens_admin/'. $img .'" />'; ?></TD>			
</TR>		
<?
}
?>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA BOSCH
/*
			<TD class="titulo"  height='15' width='90'>AUTORIZAÇÃO&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $autorizacao_cortesia ?></TD>
*/
?>
<?php if(in_array($login_fabrica, array(87))){?>
    <table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
        <tr>
            <td class="titulo" height='15' >Tipo Atendimento</td>
            <td class="conteudo"  height='15' >&nbsp;
            <?php 
                if(intval($tipo_atendimento) > 0){
                    $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                    $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

                    echo  pg_fetch_result($res_tipo_atendimento,0,'descricao');    
                }
            ?>
            </td>
            <td class="titulo" height='15' >Horas Trabalhadas</td>
            <td class="conteudo" height='15' width='40' >&nbsp;<? echo $hora_tecnica; ?></td>
            <td class="titulo" height='15' >Horas Técnicas</td>
            <td class="conteudo" height='15'width='40'  >&nbsp;<? echo $qtde_horas; ?></td>
            <td class="titulo" height='15' width='100' >Quantidade de KM</td>
            <td class="conteudo" height='15' >&nbsp;<? echo $qtde_km; ?> KM</td>
        </tr>
    </table>
<?php }?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
    <?
    #######CONTEUDO ADICIONAL LENOXX - SÓ PARA O POSTO: 14254 - JUNDSERVICE    ###############
    if((($login_posto==14254)and($login_fabrica==11)) OR $login_fabrica == 96){?>
        <tr >
            <TD class="titulo" colspan='2' height='15' ><?echo traduz("nota.fiscal.saida",$con,$cook_idioma);?></TD>
            <TD class="conteudo" colspan='1' height='15' >&nbsp;<? echo $nota_fiscal_saida; ?></TD>
            <TD class="titulo" height='15' ><? echo traduz("data.nf.saida",$con,$cook_idioma);?></TD>
            <TD class="conteudo" colspan='3' height='15' >&nbsp;<? echo $data_nf_saida; ?></TD>
        </tr>
    <?}
    ################  FIM CONTEUDO LENOXX ##################

	################## CONTEUDO LENOXX ##################
	if($login_fabrica==11){
		if(strlen($troca_garantia_admin)>0){
			$sql = "SELECT login,nome_completo
					FROM tbl_admin
					WHERE admin = $troca_garantia_admin";
			$res2 = pg_query ($con,$sql);

			if (pg_num_rows($res2) > 0) {
				$login                = pg_fetch_result ($res2,0,login);
				$nome_completo        = pg_fetch_result ($res2,0,nome_completo);
				?>
					<TR>
						<TD class="titulo"  height='15' ><?fecho("usuarios",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' colspan='3'>&nbsp;<? if($nome_completo )echo $nome_completo; else echo $login;  ?></TD>
						<TD class="titulo" height='15'><?fecho("data",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' colspan="3">
						<? echo $troca_garantia_data ?></TD>
					</TR>
					<TR>
						<TD class="conteudo"  height='15' colspan='8'>
						<?
						if($troca_garantia=='t')
							echo '<b><center>'.traduz("troca.direta",$con,$cook_idioma).'</center></b>';
						else
							echo '<b><center>'.traduz("troca.via.distribuidor",$con,$cook_idioma).'</center></b>';
						?>
						</TD>
					</TR>
		<?
			}
		}
	}
	################ FIM CONTEUDO LENOXX ##################
	if($login_fabrica ==50 AND strlen($os) > 0){ // HD 79844
		$sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
				FROM tbl_os
				JOIN tbl_numero_serie USING (serie)
				WHERE os=$os ";
		$res2=pg_query($con,$sql2);
		if(pg_num_rows($res2) > 0){
			$data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
		}
	}

	//HD 671828
	if ($login_fabrica == 91) {
		$sql = "SELECT TO_CHAR(data_fabricacao, 'DD/MM/YYYY') AS data_fabricacao FROM tbl_os_extra WHERE os=$os";
		$res2 = pg_query($con, $sql);
		$data_fabricacao = pg_result($res2, 0, 'data_fabricacao');
	}

	if($login_fabrica ==59 AND strlen($os) > 0){ // HD 79844
		$sql2="SELECT versao
				FROM tbl_os
				WHERE os=$os ";
		$res2=pg_query($con,$sql2);
		if(pg_num_rows($res2) > 0){
			$versao = pg_fetch_result($res2,0,versao);
		}
	}

	
	if($login_fabrica == 1 and $tipo_atendimento ==18) {
		$sql = " SELECT total_troca
				FROM tbl_os_troca 
				WHERE os = $os";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_troca = pg_fetch_result($res,0,total_troca);
		}
	}
?>

    <tr>
        <td class='inicio' height='15' colspan='8'>&nbsp;<?echo traduz("informacoes.do.produto",$con,$cook_idioma);?></TD>
    </tr>
    <tr>
		<? //MLG - 03/06/2011 - HD 675023
		if ($login_fabrica == 96) { ?>
			<TD class="titulo" height='15' width='90'><?echo traduz("modelo",$con,$cook_idioma);?></TD>
			<TD class="conteudo" height='15' >&nbsp;<? echo $produto_modelo ?></TD>
			<TD class="titulo" height='15' width='90'><? echo traduz("n.de.serie",$con,$cook_idioma);?>&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?>&nbsp;</TD>
			<TD class="titulo" height='15' width='100'><?echo traduz("referencia",$con,$cook_idioma);?></TD>
			<TD class="conteudo" height='15' width='100'>&nbsp;<? echo $produto_referencia ?></TD>
		</tr>
		<tr>
			<TD class="titulo"   colspan='1' height='15' width='90'><?echo traduz("descricao",$con,$cook_idioma);?></TD>
			<TD class="conteudo" colspan='5' height='15' >&nbsp;<? echo $produto_descricao ?></TD>
		<?} else {?>
			<TD class="titulo" height='15' width='90'><?echo traduz("referencia",$con,$cook_idioma);?></TD>
			<TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
			<TD class="titulo" height='15' width='90'><?echo traduz("descricao",$con,$cook_idioma);?></TD>
			<TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
			<TD class="titulo" height='15' width='90'><? echo ($login_fabrica == 35) ? 'PO#' : traduz("n.de.serie",$con,$cook_idioma);?>&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?>&nbsp;</TD>
		<?}?>
    <?if(strlen($data_fabricacao) > 0){?>
        <TD class="titulo" height='15' width='90'>DATA FABRICAÇÃO</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $data_fabricacao ?>&nbsp;</TD>
    <?}?>
    <?if($login_fabrica==19){?>
        <TD class="titulo" height='15' width='90'><?echo traduz("qtde",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $qtde ?>&nbsp;</TD>
    <?}


	if (($login_fabrica<>87 &&$login_fabrica<>14 && $login_fabrica <> 91 AND ($login_posto==6359 OR $login_posto == 4311) OR ($login_fabrica==6 AND $login_posto==4262)) || $login_fabrica == 11) {//HD 317527 - ADICIONEI PARA A FABRICA 11 ?>
        <TD class="titulo" height='15' width='90'><?echo traduz("rg.produto",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<?=(strlen(trim($rg_produto)) > 0 ? $rg_produto : '---')?></TD><?php
	}?>
	<?	if($login_fabrica == 86 and $serie_justificativa != 'null'){  // HD 328591 ?>
		</tr>
		<tr>
			<td class="titulo" height='15' width='90'>
				<? echo traduz("justificativa.numero.serie",$con,$cook_idioma);?>
			</td>
			<td colspan='7' class="conteudo" height='15'>&nbsp; <? echo $serie_justificativa; ?> </td>
		</tr>
		<tr>
	
	<?
		}
	?>
	<?if($login_fabrica==14 AND ($login_posto==6359 OR $login_posto == 7214)){?>
        <TD class="titulo" height='15' width='90'><?echo traduz("numero.controle",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $numero_controle ?>&nbsp;</TD>
	<?}?>
	<?if($login_fabrica==59){?>
        <TD class="titulo" height='15' width='90'>VERSÃO</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $versao ?>&nbsp;</TD>
	<?}?>

    </tr>
    <? if ($login_fabrica == 1) { ?>
    <tr>
        <TD class="titulo" height='15' width='90'><?echo traduz("voltagem",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
        <TD class="titulo" height='15' width='110'><?echo traduz("codigo.fabricacao",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>
        
		<?if($tipo_atendimento == 18 and strlen($total_troca) > 0) { ?>
		<TD class="titulo" height='15' width='110' style='font-weight:bold;' nowrap>VALOR DA TROCA FATURADA&nbsp;</TD>
		<TD class="conteudo" height='15' style='font-weight:bold; color:red'>R$&nbsp;<? echo number_format($total_troca,2,",","."); ?></TD>
		<? } else { ?>
		<TD class="conteudo" height='15' colspan='2'></TD>
		<? } ?>
    </tr>
    <? } ?>
</table>
<? if (strlen($aparencia_produto) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
    <td class='titulo' height='15' width='300'><?echo traduz("aparencia.geral.do.aparelho.produto",$con,$cook_idioma);?></TD>
    <td class="conteudo">&nbsp;<? echo $aparencia_produto ?></td>
</TR>
</TABLE>
<? } ?>
<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
    <TD class='titulo' height='15' width='300'><?echo traduz("acessorios.deixados.junto.com.o.aparelho",$con,$cook_idioma);?></TD>
    <TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>
<? if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
    <TR>
        <TD class='titulo' height='15'width='300'>&nbsp;<?echo traduz("informacoes.sobre.o.defeito",$con,$cook_idioma);?></TD>
        <TD class="conteudo" >&nbsp;
            <?
            if (strlen($defeito_reclamado) > 0) {
                $sql = "SELECT tbl_defeito_reclamado.descricao
                        FROM   tbl_defeito_reclamado
                        WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";


                $res = pg_query ($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $descricao_defeito = trim(pg_fetch_result($res,0,descricao));

					//HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
					//tbl_defeito_reclamado.descricao em outro
					if ($login_fabrica == 3) {
						echo $defeito_reclamado_descricao;
					}
					else {
						echo $descricao_defeito ." - ".$defeito_reclamado_descricao;
					}
                }
            }
            ?>
        </TD>
    </TR>
</TABLE>
<? } ?>
<? if ($login_fabrica == 19 and (strlen($fabricacao_produto) > 0 or strlen($qtde_km) > 0)) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
	<TR>
		<TD class='titulo' height='15'width='300'>Mês e Ano de Fabricação do Produto</TD>
		<TD class="conteudo" >&nbsp;<?echo $fabricacao_produto;?>
		</TD>
		<TD class='titulo' height='15'width='100'>Quilometragem </TD>
		<TD class="conteudo" >&nbsp;<?echo $qtde_km;?>
		</TD>
	</TR>
</TABLE>
<? } ?>
<?if(in_array($login_fabrica,array(2, 6, 30, 59,94))){?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<TR>
		<?php if($login_fabrica != 59 && $login_fabrica != 94){ ?>
		<TD class="titulo" height='15' width='300' align='right'><?
			if ($login_fabrica == 6 or $login_fabrica == 2) echo traduz("os.posto",$con,$cook_idioma);
			else                     echo traduz("os.revendedor",$con,$cook_idioma);?></TD>
		<TD class="conteudo" >&nbsp;<? echo $os_posto ?>&nbsp;</TD>
		<?php } ?>
		<?if($login_fabrica == 30 || $login_fabrica == 59 || $login_fabrica == 94){?>
		<?php
			// HD 415550
			if ($login_fabrica == 94) {
				$sql = "SELECT posto
						FROM tbl_posto_fabrica
						JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
						WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
						AND tbl_posto_fabrica.posto = " . $login_posto;
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)) {
					$posto_interno = true;
				}
			}
			$width_tecnico = ($posto_interno === true ) ? 300 : 100;
			$width_tecnico2 = ($posto_interno === true) ? 217 : 'auto';
			if(($login_fabrica == 59 OR $login_fabrica == 87) && !empty($tecnico_nome)) {
				// @todo ajustar a pesquisa do nome do tecnico na consulta principal, na area do admin tb
				$sql_tec = "SELECT nome 
							FROM tbl_tecnico 
							WHERE tecnico = " . $tecnico_nome . " AND fabrica = " . $login_fabrica;
				$tec_res = pg_query($con,$sql_tec);
				if(pg_numrows($tec_res)>0)
					$tecnico_nome = pg_result($tec_res, 0, 'nome');
			}
		?>
		<?php if( ($login_fabrica == 94 and $posto_interno === true) || $login_fabrica != 94) { ?>
			<TD class="titulo" height='15' width='<?=$width_tecnico;?> align='right'>Técnico</TD>
			<TD class="conteudo" width='<?=$width_tecnico2;?>'>&nbsp;<? echo $tecnico_nome ?>&nbsp;</td>
		<?php } ?>
		<?php
			// HD 415550
			if($login_fabrica == 94 && $posto_interno === true) {
				
				$sql = "SELECT mao_de_obra FROM tbl_os WHERE os = $os";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)) {
				
					echo '<td class="titulo">MÃO-DE-OBRA&nbsp;</td>
						  <td class="conteudo" align="right">&nbsp;R$ '.number_format( pg_result($res,0,0),2,',','.' ).'</td>';
				
				}
	
			}
		
		?>
		<?}?>
	</TR>

</table>
<?}?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <TR>
        <TD  height='15' class='inicio' colspan='4'>&nbsp;<?echo traduz("defeitos",$con,$cook_idioma);?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15' width='90'><?echo traduz("reclamado",$con,$cook_idioma);?></TD>

		<TD class="conteudo" height='15' width='140' <?if ($login_fabrica == 30 or $login_fabrica == 43) echo "colspan=4"?>> &nbsp;<?
			// HD 22820
			if($login_fabrica==1){
				if($troca_garantia=='t' or $troca_faturada=='t')	
                    echo $descricao_defeito ;
				else 
                    echo $descricao_defeito ; 
               
                if($defeito_reclamado_descricao)
                    echo " - ".$defeito_reclamado_descricao;

			}elseif($login_fabrica == 19){
				$sql = "SELECT tbl_defeito_reclamado.codigo,tbl_defeito_reclamado.descricao
					FROM tbl_os_defeito_reclamado_constatado
					JOIN tbl_defeito_reclamado USING(defeito_reclamado)
					WHERE os=$os";
				$res = pg_query ($con,$sql);

				$array_integridade_reclamado = array();

				if(@pg_num_rows($res)>0){
					for ($i=0;$i<pg_num_rows($res);$i++){
						$aux_defeito_reclamado = pg_fetch_result($res,$i,1);
						array_push($array_integridade_reclamado,$aux_defeito_reclamado);
					}
				}
				$lista_defeitos_reclamados = implode($array_integridade_reclamado,", ");
				echo "$lista_defeitos_reclamados";

			} else {
				echo $descricao_defeito; 

				if($defeito_reclamado_descricao) {
					//HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
					//tbl_defeito_reclamado.descricao em outro
					if ($login_fabrica != 3) {
						echo " - ".$defeito_reclamado_descricao;
					}
				}
			}
			?>
			</TD>
			<? if($login_fabrica == 95 || $login_fabrica == 94){
				echo "<td class='titulo'>&nbsp;</td>";
				echo "<td class='conteudo'>&nbsp;</td>";
			}
			?>
		<?if (!in_array($login_fabrica, array(30, 43, 59, 95,94))) {?>
        <TD class="titulo" height='15' width='90'><? if($login_fabrica==20){echo traduz("reparo",$con,$cook_idioma);}else echo traduz("constatado",$con,$cook_idioma);?> </TD>
        <td class="conteudo" height='15'>&nbsp;
			<?
			//HD 17683 - VÁRIOS DEFEITOS CONSTATADOS
			if($login_fabrica==30 or $login_fabrica ==19 or $login_fabrica == 43){

				$sql = "SELECT DISTINCT tbl_defeito_constatado.codigo,tbl_defeito_constatado.descricao
					FROM tbl_os_defeito_reclamado_constatado
					JOIN tbl_defeito_constatado USING(defeito_constatado)
					WHERE os=$os";

				$res = pg_query ($con,$sql);

				$array_integridade = array();

				if(@pg_num_rows($res)>0){
					for ($i=0;$i<pg_num_rows($res);$i++){
						$aux_defeito_constatado = pg_fetch_result($res,$i,0).'-'.pg_fetch_result($res,$i,1);
						array_push($array_integridade,$aux_defeito_constatado);
					}
				}
				$lista_defeitos = implode($array_integridade,", ");
				echo "$lista_defeitos";
			} else {
			if( $login_fabrica==1){
					if($troca_garantia=='t' or $troca_faturada=='t'){
						echo $defeito_reclamado_descricao;
					} else {
						echo $defeito_constatado_descricao;
					}
				} else {
					if($login_fabrica==20)echo $defeito_constatado_codigo.' - ';
				echo $defeito_constatado_descricao;
				}
			}
			?>
        </TD>
    </TR>
    <?php if(in_array($login_fabrica,array(87))){
			if(!empty($tecnico_nome)) {
				$sql_tec = "SELECT nome 
							FROM tbl_tecnico 
							WHERE tecnico = " . $tecnico_nome . " AND fabrica = " . $login_fabrica;
				$tec_res = pg_query($con,$sql_tec);
				if(pg_numrows($tec_res)>0)
					$tecnico_nome = pg_result($tec_res, 0, 'nome');
			}
        ?>    
        <tr>
            <td class="titulo"><?php  echo traduz("tecnico",$con,$cook_idioma);?></td>
            <td class="conteudo" colspan='3'>&nbsp;<?php echo $tecnico_nome;?></td>
        </tr>

    <?php }?>    

    <?php if(!in_array($login_fabrica,array(87))){?>
        <TR>
            <TD class="titulo" height='15' width='90'>
                <?
                if(in_array($login_fabrica, array(3,6,11,15,24,40,43,50,59,74)) or $login_fabrica >= 80)
                    echo traduz("solucao",$con,$cook_idioma);
                elseif($login_fabrica==20){
                    echo traduz("defeito",$con,$cook_idioma);
                } else {
                    echo traduz("causa",$con,$cook_idioma);
                }
                ?>
            &nbsp;</td>
            <td class="conteudo" colspan='3' height='15'>&nbsp;
            <?
            if((in_array($login_fabrica, array(24, 40, 43, 59,74)) or $login_fabrica >= 80) and strlen($solucao_os)>0) { //takashi 30-11
                $sql="SELECT descricao FROM tbl_solucao WHERE solucao=$solucao_os AND fabrica=$login_fabrica LIMIT 1";
                $xres = pg_query($con, $sql);
                $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                echo "$xsolucao";
            }

            if(in_array($login_fabrica, array(3,6,11,15,50))) {
            if (strlen($solucao_os)>0){
                //chamado 1451 - não estava validando a data...
                $sql_data = "SELECT SUM(validada - '2006-11-05') AS total_dias FROM tbl_os WHERE os=$os";
                $resD = pg_query ($con,$sql_data);
                if (pg_num_rows ($resD) > 0) {
                    $total_dias = pg_fetch_result ($resD,0,total_dias);
                }
                //if($ip=="201.27.30.194") echo $total_dias;
                if ( ($total_dias > 0 AND $login_fabrica==6) OR $login_fabrica==11 or $login_fabrica==15 or $login_fabrica==3 or $login_fabrica == 50){
                    $sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
                    $xres = pg_query($con, $sql);
                    if (pg_num_rows($xres)>0){
                        $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                        echo "$xsolucao";
                    } else {
                        $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
                        $xres = pg_query($con, $xsql);
                        $xsolucao = trim(@pg_fetch_result($xres,0,descricao));
                        echo "$xsolucao";
                    }
                //if($ip=="201.27.30.194") echo $sql;
                } else {
                    $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
                    $xres = pg_query($con, $xsql);
                    if (pg_num_rows($xres)>0){
                        $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                        echo "$xsolucao  - $data_digitacao";
                    } else {
                        $sql="select descricao from tbl_solucao where solucao=$solucao_os and     fabrica=$login_fabrica limit 1";
                        $xres = pg_query($con, $sql);
                        $xsolucao = trim(pg_fetch_result($xres,0,descricao));
                        echo "$xsolucao";
                    }
                }
            }
            } else {
                if($login_fabrica==20)echo $causa_defeito_codigo.' - ' ;
                echo $causa_defeito_descricao;
                }
                }
             ?>
            </TD>
         </TR>
     <?php }?>
<? if (in_array($login_fabrica, array(30, 43, 59, 95,94))) {

		$sql_cons = "SELECT
					tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao         ,
					tbl_defeito_constatado.codigo,
					tbl_solucao.solucao,
					tbl_solucao.descricao as solucao_descricao
			FROM tbl_os_defeito_reclamado_constatado
			JOIN tbl_defeito_constatado USING(defeito_constatado)
			LEFT JOIN tbl_solucao USING(solucao)
			WHERE os = $os";

	$res_dc = pg_query($con, $sql_cons);
	if(pg_num_rows($res_dc) > 0){

		for($x=0;$x<pg_num_rows($res_dc);$x++){
			$dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
			$dc_solucao = pg_fetch_result($res_dc,$x,solucao);

			$dc_descricao = pg_fetch_result($res_dc,$x,descricao);
			$dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
			$dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

		echo "<tr>";
				echo "<td class='titulo' height='15'>Defeito Constatado</td>";
				if ($login_fabrica == 30 || $login_fabrica == 94 ){
				echo "<td class='conteudo' colspan=4>$dc_codigo - $dc_descricao</td>";
				}
				else {
				echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
				}
				if ($login_fabrica <> 30 && $login_fabrica != 94 ){
				echo "<td class='titulo' height='15'>Solucão</td>";
				echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";
				}
		echo "</tr>";

		}
	}
}
	?>


    <?
    if($login_fabrica==20){
        if($solucao_os){
            $xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
            $xres = pg_query($con, $xsql);

            $xsolucao = trim(pg_fetch_result($xres,0,descricao));

            $sql_idioma = " SELECT * FROM tbl_servico_realizado_idioma
                            WHERE servico_realizado = $solucao_os
                            AND upper(idioma)   = '$sistema_lingua'";
            $res_idioma = @pg_query($con,$sql_idioma);
            if (@pg_num_rows($res_idioma) >0) $xsolucao  = trim(@pg_fetch_result($res_idioma,0,descricao));

            echo "<tr>";
            echo "<td class='titulo' height='15' width='90'>".traduz("identificacao",$con,$cook_idioma)."</TD>";
            echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
            echo "</tr>";
        }
    }
    ?>
</TABLE>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?echo traduz("informacoes.sobre.o.consumidor",$con,$cook_idioma);?></TD>
    </tr>
    <TR>
        <TD class="titulo" height='15'><?echo traduz("nome",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_nome ?></TD>
        <TD class="titulo"><?echo traduz("telefone.residencial",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
    </TR>
    <?php if(!in_array($login_fabrica,array(87))){?>
        <TR>
            <TD class="titulo" height='15'><?echo traduz("telefone.celular",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_celular ?></TD>
            <TD class="titulo"><?echo traduz("telefone.comercial",$con,$cook_idioma);?></TD>
            <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone_comercial ?></TD>
        </TR>
    <?php }?>
    <TR>
        <TD class="titulo" height='15'><?echo traduz("cpf.consumidor",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
        <TD class="titulo" height='15'><?echo traduz("cep",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15'><? echo traduz("endereco",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
        <TD class="titulo" height='15'><?echo traduz("numero",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
    </TR>
    <TR>
        <TD class="titulo" height='15'><?echo traduz("complemento",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
        <TD class="titulo" height='15'><?echo traduz("bairro",$con,$cook_idioma);?></TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
    </TR>
    <TR>
        <TD class="titulo"><?echo traduz("cidade",$con,$cook_idioma);?></TD>
        <TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
        <TD class="titulo"><?echo traduz("estado",$con,$cook_idioma);?></TD>
        <TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
    </TR>
   <TR>
        <TD class="titulo"><?echo traduz("email",$con,$cook_idioma);?></TD>
        <TD class="conteudo">&nbsp;<? echo $consumidor_email ?></TD>
		<?if($login_fabrica==1){?>
			<TD class="titulo"><?echo traduz("tipo.consumidor",$con,$cook_idioma);?></TD>
			<TD class="conteudo">&nbsp;<? echo $fisica_juridica ?></TD>
		<?}elseif($login_fabrica==11){?>
			<TD class="titulo"><? echo traduz("fone.rec",$con,$cook_idioma);?></TD>
			<TD class="conteudo">&nbsp;<? echo $consumidor_fone_recado ?></TD>
		<?} else {?>
			<TD class="titulo">&nbsp;</TD>
			<TD class="conteudo">&nbsp;</TD>
		<?}?>
    </TR>

<?		//HD 367384 - INICIO
		//EBANO: não funciona fora do Brasil
		$sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) >0) {
			$sigla_pais = pg_fetch_result ($res,0,pais);
		}

		if ($consumidor_revenda[0] == 'C' && $sigla_pais == "BR") {
			// Endereços do posto e do consumidor
			$sql_end_posto = "SELECT TRIM(contato_endereco)||','||TRIM(contato_numero)||','||TRIM(contato_cidade)||','||TRIM(contato_estado)||',Brasil' AS endereco,
									 longitude::text||','||latitude::text AS LatLong /* Lat,Long estão ao contrário no banco! */
				FROM tbl_posto_fabrica JOIN tbl_posto USING(posto) WHERE posto=$login_posto AND fabrica=$login_fabrica";
			$res_end_posto = pg_query($con, $sql_end_posto);
			list($end_posto, $ll_posto)  = pg_fetch_row($res_end_posto);
			$end_posto  =  implode(',', array_filter(explode(',', $end_posto))); //Limpa os campos em branco
			$end_cons   =  urlencode(implode(',', array_filter(array($consumidor_endereco,$consumidor_numero,$consumidor_cidade,$consumidor_cep,$consumidor_estado,'Brasil'))));
			//$ll_param = (preg_match("/^[+-]?\d{1,2}\.?\d+,[+-]?\d{1,3}\.?\d+$/", $ll_posto)) ? "&ll=$ll_posto":'&z=14';
			$ll_param   = ($ll_posto != ',')?"&ll=$ll_posto":"&center=$end_posto";
			$end_posto  = (preg_match("/^[+-]?\d{1,2}\.?\d+,[+-]?\d{1,3}\.?\d+$/", $ll_posto))?$ll_posto:urlencode($end_posto);
			$ilink = 'http://'."maps.google.com/maps?f=d&source=s_d&saddr=$end_posto".
					 "&daddr=$end_cons". //+($consumidor_nome)
					 $ll_param.
					 '&doflg=ptk&oe=iso-8859-1&output=embed&hl=pt-BR&ie=iso-8859-1'; //zoom: &z=14
			?>
	<tr class='mapa_gmaps' style='display:none'>
		<td colspan="4" style='text-align:center;height: 2em;overflow:hidden'>
			<button type='button' onclick='showHideGMap()'>Mapa entre o Posto e o Consumidor</button>
			<div id="gmaps">
				<iframe src='<?=$ilink?>' height='450' width='600'></iframe>
				<p style='text-align:right;color:#669' onclick="$('#gmaps').height('auto');$('#lista_mapa_ko').slideToggle('fast')">
				O mapa está incorreto?</p>
				<ul id='lista_mapa_ko' style='display:none;color:#336'>
					<li style='list-style:none'>Se não aparece o trajeto, ou o mapa não mostra o local exato ou aproximado, pode ser porque <i>Google</i> não achou algum dos endereços, o que pode acontecer por diversas causas:<br></li>
					<li>A cidade ou logradouro não constam na base de dados do <a href="http://maps.google.com" target="_blank">GoogleMaps</a></li>
					<li>O endereço não foi escrito corretamente (ex. <i>R. 4</i> ao invés de <b>Rua Quatro</b>, ou <i>A. 15 de nov.</i> ao invés de <b>Av. Quinze de Novembro</b>)</li>
					<li>O endereço (ou CEP) foi alterado recentemente e a base de dados do Google não está atualizada</li>
					<li style='list-style:none'><br>Para mais informações, pode <a href='<?=str_replace('embed','html',$ilink)?>' target='_blank'>abrir o <i>GoogleMaps</i></a> em outra janela.</li>
				</ul>
			</div>
		</td>
	</tr>
	<?}
	//HD 367384 - FIM
	?>
</TABLE>



<?
/*COLORMAQ TEM 2 REVENDAS*/
if($login_fabrica==50){

	$sql = "SELECT
				cnpj,
				to_char(data_venda, 'dd/mm/yyyy') as data_venda
			FROM tbl_numero_serie
			WHERE serie = trim('$serie')";

	$res_serie = pg_query ($con,$sql);

	if (pg_num_rows ($res_serie) > 0) {


		$txt_cnpj       = trim(pg_fetch_result($res_serie,0,cnpj));
		$data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

		$sql = "SELECT      tbl_revenda.nome              ,
							tbl_revenda.revenda           ,
							tbl_revenda.cnpj              ,
							tbl_revenda.cidade            ,
							tbl_revenda.fone              ,
							tbl_revenda.endereco          ,
							tbl_revenda.numero            ,
							tbl_revenda.complemento       ,
							tbl_revenda.bairro            ,
							tbl_revenda.cep               ,
							tbl_revenda.email             ,
							tbl_cidade.nome AS nome_cidade,
							tbl_cidade.estado
				FROM        tbl_revenda
				LEFT JOIN   tbl_cidade USING (cidade)
				LEFT JOIN   tbl_estado using(estado)
				WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

		$res_revenda = pg_query ($con,$sql);

		# HD 31184 - Francisco Ambrozio (06/08/08) - detectei que pode haver
		#   casos em que o SELECT acima não retorna resultado nenhum.
		#   Acrescentei o if para que não dê erros na página.
		$msg_revenda_info = "";
		if (pg_num_rows ($res_revenda) > 0) {
			$revenda_nome_1       = trim(pg_fetch_result($res_revenda,0,nome));
			$revenda_cnpj_1       = trim(pg_fetch_result($res_revenda,0,cnpj));

			$revenda_bairro_1     = trim(pg_fetch_result($res_revenda,0,bairro));
			$revenda_cidade_1     = trim(pg_fetch_result($res_revenda,0,cidade));
			$revenda_fone_1       = trim(pg_fetch_result($res_revenda,0,fone));
		} else {
			$msg_revenda_info = traduz("nao.foi.possivel.obter.informacoes.da.revenda.cliente.colormaq.nome.cnpj.e.telefone",$con,$cook_idioma);
		}

?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?if($sistema_lingua=='ES')echo traduz("informacoes.da.revenda",$con,$cook_idioma);else echo traduz("informacoes.da.revenda",$con,$cook_idioma )."(CLIENTE COLORMAQ)";?></td>
    </tr>
	<? if (strlen($msg_revenda_info) > 0){
					echo "<tr>";
					echo "<td class='conteudo' colspan= '4' height='15'><center>$msg_revenda_info</center></td>";
					echo "</tr>";
				} ?>
    <TR>
        <TD class="titulo"  height='15' ><?echo traduz("nome",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome_1 ?></TD>
        <TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj_1 ?></TD>
    </TR>
    <TR>
	<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <TD class="titulo"  height='15'><?echo traduz("fone",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<?=$revenda_fone_1?></TD>
        <TD class="titulo"  height='15'>&nbsp;<?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<?=$data_venda; ?></TD>
    </TR>
</TABLE>
<?
	}
}
/*COLORMAQ TEM 2 REVENDAS - FIM*/
?>

<? // hd 45748
$sql = "SELECT os
		FROM tbl_os_troca_motivo
		WHERE os = $os ";
$res = pg_query($con,$sql);
if($login_fabrica==20 AND pg_num_rows($res)>0) {
	$motivo1 = "Não são fornecidas peças de reposição para este produto";
	$motivo2 = "Há peça de reposição, mas está em falta";
	$motivo3 = "Vicio do produto";
	$motivo4 = "Divergência de voltagem entre embalagem e produto";
	$motivo5 = "Informações adicionais";
	$motivo6 = "Informações complementares";
	$troca = true;
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>
<?
if($sistema_lingua=='ES')echo "Informaciones sobre la RAZÓN DE CAMBIO";
else {
	echo "Informações sobre o MOTIVO DA TROCA";
}
?>
		
		</td>
	</tr>
	<tr>
		<td class="conteudo">
<div>
	<div>
<?

		$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
						tbl_causa_defeito.codigo        AS causa_codigo     ,
						tbl_causa_defeito.descricao     AS causa_defeito
				FROM   tbl_os_troca_motivo
				JOIN   tbl_servico_realizado USING(servico_realizado)
				JOIN   tbl_causa_defeito     USING(causa_defeito)
				WHERE os     = $os
				AND   motivo = '$motivo1'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)==1){
			echo "OK";
			$identificacao1 = pg_fetch_result($res,0,servico_realizado);
			$causa_defeito1 = pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
		?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Data de entrada do produto na assistência técnica
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $data_abertura; ?>
				</div>
			</div>

			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo1; ?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Identificação do defeito
				</div>
				<div id="contentleft2" style="width: 250px; ">
					Defeito
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $identificacao1; ?>
				</div>
				<div id="contentleft" style="width: 250px;font:75%">
					<? echo $causa_defeito1; ?>
				</div>
			</div>
			<?
			}
			$sql = "SELECT
							TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
							pedido                                              ,
							PE.referencia                     AS peca_referencia,
							PE.descricao                      AS peca_descricao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_peca            PE USING(peca)
					WHERE os     = $os
					AND   motivo = '$motivo2'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)==1){
				$peca_referencia = pg_fetch_result($res,0,peca_referencia);
				$peca_descricao  = pg_fetch_result($res,0,peca_descricao);
				$data_pedido     = pg_fetch_result($res,0,data_pedido);
				$pedido          = pg_fetch_result($res,0,pedido);



			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo2?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Código da Peça
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Data do Pedido
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Número do Pedido
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $peca_referencia."-".$peca_descricao; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $data_pedido; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $pedido; ?>
				</div>
			</div>
			<?
			}

			$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
							tbl_causa_defeito.codigo        AS causa_codigo     ,
							tbl_causa_defeito.descricao     AS causa_defeito    ,
							observacao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_servico_realizado USING(servico_realizado)
					JOIN   tbl_causa_defeito     USING(causa_defeito)
					WHERE os     = $os
					AND   motivo = '$motivo3'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)==1){
				$identificacao2 = pg_fetch_result($res,0,servico_realizado);
				$causa_defeito2 =  pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
				$observacao1    = pg_fetch_result($res,0,observacao);

			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo3?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Identificação do Defeito
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Defeito
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Quais as OS’s deste produto:
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $identificacao2; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $causa_defeito2; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $observacao1; ?>
				</div>
			</div>
			<?
			}

			$sql = "SELECT observacao
					FROM   tbl_os_troca_motivo
					WHERE os     = $os
					AND   motivo = '$motivo4'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)==1){
				$observacao2    = pg_fetch_result($res,0,observacao);
			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo4; ?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 650px; " nowrap>
					Qual a divergência:
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $observacao2; ?>
				</div>
			</div>
			<?
			}
			?>
		</h2>
	</div>
</div>
<?
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo5'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)==1){
		$observacao3    = pg_fetch_result($res,0,observacao);


		?>
		<div>
			<div>
				<h2><?=$motivo5?>
				<div id="contentcenter" style="width: 650px;">
					<div id="contentleft" style="width: 650px;font:75%"><? echo $observacao3;?></div>
				</div>
				</h2>
			</div>
		</div>
		<?
	}
	/* HD 43302 - 26/9/2008 */
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo6'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)==1){
		$observacao4    = pg_fetch_result($res,0,observacao);
		?>
		<div>
			<div>
				<h2><?fecho("informacoes.complementares",$con,$cook_idioma);?>
				<div id="contentcenter" style="width: 650px;">
					<div id="contentleft" style="width: 650px;font:75%"><? echo $observacao4;?></div>
				</div>
				</h2>
			</div>
		</div>
		<?
	}
}
?>
		</td>
	</tr>
</table>


<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>&nbsp;<?echo traduz("informacoes.da.revenda",$con,$cook_idioma); if($login_fabrica==50){ echo " (".traduz("consumidor",$con,$cook_idioma).")";}?></td>
    </tr>
    <TR>
        <TD class="titulo"  height='15' ><?echo traduz("nome",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
        <TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
    </TR>
    <TR>
	<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
        <TD class="titulo"  height='15'><?echo traduz("nf.numero",$con,$cook_idioma);?></TD>
        <TD class="conteudo vermelho"  height='15'>&nbsp;<? if($login_fabrica==6 and $login_posto==4260 and strlen($nota_fiscal_saida)>0) echo $nota_fiscal_saida ; else echo $nota_fiscal ?></FONT></TD>
        <TD class="titulo"  height='15'><?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15'>&nbsp;<? if($login_fabrica==6 and $login_posto==4260 and strlen($data_nf_saida)>0) echo $data_nf_saida ; else echo $data_nf; ?></TD>
    </TR>
    <TR>
        <TD class="titulo"  height='15' ><?echo traduz("fone",$con,$cook_idioma);?></TD>
        <TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_fone ?></TD>
		 <TD class="titulo"  height='15'>
		 <?if($login_fabrica==11) {
			echo traduz("email",$con,$cook_idioma);
		}?></TD>
        <TD class="conteudo"  height='15'>&nbsp; <?if($login_fabrica==11) { echo $revenda_email; }?></TD>
    </TR>


<? //////ADICIONA OS DOIS NOVOS CAMPOS NO RELATÓRIO PARA LENOXX

	if($login_fabrica==11 OR $login_fabrica == 96){

		$sql = "SELECT nota_fiscal_saida,
			to_char(data_nf_saida, 'DD/MM/YYYY') as data_nf_saida
			FROM   tbl_os
			WHERE os     = $os
			";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res)==1){

			$nota_fiscal_saida    = pg_fetch_result($res,0,nota_fiscal_saida);
			$data_nf_saida        = pg_fetch_result($res,0,data_nf_saida);

			?>
			 <TR>
				<TD class="titulo"  height='15' >NF DE SAIDA</TD>
				<TD class="conteudo"  height='15' width='300'><? echo $nota_fiscal_saida;?></TD>
				<TD class="titulo"  height='15'>DATA&nbsp;NF&nbsp;DE&nbsp;SAIDA</TD>
				<TD class="conteudo"  height='15'><? echo $data_nf_saida;?></TD>
			</TR>
		<?
		}

	}
	if ($login_fabrica == 3){
		
		$sql = "SELECT 
					tbl_revenda_fabrica.contato_endereco,
					tbl_revenda_fabrica.contato_numero,
					tbl_revenda_fabrica.contato_complemento,
					tbl_revenda_fabrica.contato_bairro,
					tbl_ibge.cidade,
					tbl_ibge.estado
				FROM tbl_os
				JOIN tbl_revenda on (tbl_os.revenda_cnpj = tbl_revenda.cnpj) 
				JOIN tbl_revenda_fabrica on (tbl_revenda.cnpj = tbl_revenda_fabrica.cnpj and tbl_revenda.revenda = tbl_revenda_fabrica.revenda)
				JOIN tbl_fabrica on (tbl_revenda_fabrica.fabrica = tbl_fabrica.fabrica and tbl_fabrica.fabrica=$login_fabrica) 
				LEFT JOIN tbl_ibge on (tbl_revenda_fabrica.contato_cidade = tbl_ibge.cod_ibge)

				WHERE tbl_os.os = $os 
				AND tbl_os.fabrica=$login_fabrica
		";

		$res = pg_query($con,$sql);

		$contato_endereco    = pg_result($res,0,0);
		$contato_numero      = pg_result($res,0,1);
		$contato_complemento = pg_result($res,0,2);
		$contato_bairro      = pg_result($res,0,3);
		$cidade              = pg_result($res,0,4);
		$estado              = pg_result($res,0,5);

	?>

		<TR>
			<TD class="titulo"  height='15'>ENDEREÇO&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<?echo $contato_endereco;?></TD>
			<TD class="titulo"  height='15'>NÚMERO&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<? echo $contato_numero; ?></TD>
		</TR>

		<TR>
			<TD class="titulo"  height='15'>COMPLEMENTO&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<?echo $contato_complemento;?></TD>
			<TD class="titulo"  height='15'>BAIRRO&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<? echo $contato_bairro; ?></TD>
		</TR>

		<TR>
			<TD class="titulo"  height='15'>CIDADE&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<?echo $cidade;?></TD>
			<TD class="titulo"  height='15'>ESTADO&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<? echo $estado; ?></TD>
		</TR>
	<?
	}
	?>
</TABLE>

<?
	/* HD 26244 */
	if ($login_fabrica==30 AND strlen($certificado_garantia)>0){

		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (105,106,107)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

				if ($estendida_status_os == 105){
					$estendida_observacao = "OS em auditoria";
				}
				if ($estendida_status_os == 106){
					$estendida_observacao = "OS Aprovada na Auditoria";
				}
				if ($estendida_status_os == 107){
					$estendida_observacao = "OS Recusada na Auditoria";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;GARANTIA ESTENDIDA </td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='90'>LGI</TD>
				<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $certificado_garantia ?></TD>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>

<?
	/* HD 209166 */
	if ($login_fabrica==30){

		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (132,19)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

				if ($estendida_status_os == 132){
					$estendida_observacao = "OS em auditoria de reincidência";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;Auditoria de Reincidência</td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>

<?
	/* HD 209166 */
	if ($login_fabrica==30){

		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (102,103,104)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

				if ($estendida_status_os == 102){
					$estendida_observacao = "OS em auditoria de número de série";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;Auditoria de Número de Série</td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>

<?
	/* HD 209166 */
	if ($login_fabrica==30){
		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (98,99,100,101)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

				if ($estendida_status_os == 98){
					$estendida_observacao = "OS em auditoria de ". number_format($qtde_km, 2, ',', '.'). " Km";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;Auditoria de KM</td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>

<?
/*takashi compressores*/
if($login_fabrica==1){
	if($tipo_os == 13){
		$where_visita= " os_revenda=$os_numero";
	} else {
		$where_visita= "os=$os";
	}
	$sql = "SELECT     os                                  ,
					to_char(data, 'DD/MM/YYYY') as  data,
					to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
					to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
					km_chegada_cliente   as km          ,
					valor_adicional                     ,
					justificativa_valor_adicional,
					qtde_produto_atendido
			FROM tbl_os_visita
			WHERE $where_visita";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){

		echo "<table border='0' cellpadding='0' cellspacing='1' width='700px' align='center' class='Tabela'>";
		echo "<tr class='inicio'>";
		if($tipo_os == 13){
			echo "<td width='100%' colspan='6'>&nbsp;DESPESAS DA OS GEO METAL: $os_numero</td>";
		} else {
			echo "<td width='100%' colspan='6'>&nbsp;".traduz("despesas.de.compressores",$con,$cook_idioma)."</td>";
		}
		echo "</tr>";
		echo "<tr>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("data.da.visita",$con,$cook_idioma)."</font></td>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("hora.inicio",$con,$cook_idioma)."</font></td>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("hora.fim",$con,$cook_idioma)."</font></td>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("km",$con,$cook_idioma)."</font></td>";
		if($tipo_os ==13){
			echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("qtde.produto.atendido",$con,$cook_idioma)."</font></td>";
		}

		echo "<td nowrap class='titulo2' colspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("despesas.adicionais",$con,$cook_idioma)."</font></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td nowrap class='titulo2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("valor",$con,$cook_idioma)."</font></td>";
		echo "<td nowrap class='titulo2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz("justificativa",$con,$cook_idioma)."</font></td>";
		echo "</tr>";

		for($i=0;$i<pg_num_rows($res);$i++){

			$data                          = pg_fetch_result($res,$i,data);
			$inicio                        = pg_fetch_result($res,$i,inicio);
			$fim                           = pg_fetch_result($res,$i,fim);
			$km                            = pg_fetch_result($res,$i,km);
			$valor_adicional               = pg_fetch_result($res,$i,valor_adicional);
			$justificativa_valor_adicional = pg_fetch_result($res,$i,justificativa_valor_adicional);
			$qtde_produto_atendido         = pg_fetch_result($res,$i,qtde_produto_atendido);

			echo "<tr class='conteudo'>";
			echo "<td align='center'>&nbsp;$data                         </td>";
			echo "<td align='center'>&nbsp;$inicio                       </td>";
			echo "<td align='center'>&nbsp;$fim                          </td>";
			echo "<td align='center'>&nbsp;$km                           </td>";
			if($tipo_os ==13){
				echo "<td align='center'>&nbsp;$qtde_produto_atendido    </td>";
			}
			echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."         </td>";
			echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
			echo "</tr>";
		}

		echo "</table>";

	}
}
 ?>

<?
    $sql = "SELECT  tbl_produto.referencia                                        ,
                    tbl_produto.descricao                                         ,
                    tbl_os_produto.serie                                          ,
                    tbl_os_produto.versao                                         ,
                    tbl_os_item.serigrafia                                        ,
                    tbl_os_item.pedido    AS pedido                               ,
                    tbl_os_item.peca                                              ,
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
                    tbl_defeito.descricao AS defeito                              ,
                    tbl_peca.referencia   AS referencia_peca                      ,
                    tbl_os_item_nf.nota_fiscal                                    ,
                    tbl_peca.descricao    AS descricao_peca                       ,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao,
                    tbl_status_pedido.descricao     AS status_pedido              ,
                    tbl_produto.referencia          AS subproduto_referencia      ,
                    tbl_produto.descricao           AS subproduto_descricao       ,
                    tbl_lista_basica.posicao
            FROM    tbl_os_produto
            JOIN    tbl_os_item USING (os_produto)
            JOIN    tbl_produto USING (produto)
            JOIN    tbl_peca    USING (peca)
            JOIN    tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
                                           AND tbl_lista_basica.peca    = tbl_peca.peca
            LEFT JOIN    tbl_defeito USING (defeito)
            LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os_produto.os = $os
            ORDER BY tbl_peca.descricao";

	# HD 153693
	$ordem = ($login_fabrica == 11) ? " ORDER BY tbl_os_item.digitacao_item ,tbl_peca.referencia  " : " ORDER BY tbl_peca.descricao ";

    $sql = "/*( */
			SELECT  tbl_produto.referencia                                         ,
                    tbl_produto.descricao                                          ,
                    tbl_os_produto.serie                                           ,
                    tbl_os_produto.versao                                          ,
                    tbl_os_item.os_item                                            ,
                    tbl_os_item.serigrafia                                         ,
                    tbl_os_item.pedido                                             ,
                    tbl_os_item.pedido_item                                        ,
                    tbl_os_item.peca                                               ,
                    tbl_os_item.peca_causadora                                     ,
                    tbl_os_item.soaf                                               ,
                    tbl_os_item.posicao                                            ,
                    tbl_os_item.obs                                                ,
                    tbl_os_item.custo_peca                                         ,
                    tbl_os_item.servico_realizado AS servico_realizado_peca        ,
					tbl_os_item.peca_serie                                         ,
					tbl_os_item.peca_serie_trocada                                 ,
                    TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
                    case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
                    else
                        lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
                    end                                      AS pedido_blackedecker,
					tbl_pedido.seu_pedido                    AS seu_pedido         ,
                    tbl_pedido.distribuidor                                        ,
                    tbl_defeito.descricao           AS defeito                     ,
                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.peca_critica           AS peca_critica                ,
                    tbl_peca.retorna_conserto       AS retorna_conserto            ,
					tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
					tbl_os_item_nf.nota_fiscal                                     ,
					TO_CHAR(tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf        ,
                    tbl_peca.descricao              AS descricao_peca              ,";
//28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item
    $sql.= ($mostrar_valor_pecas) ? "
                    tbl_os_item.custo_peca
                                                    AS preco_peca                  ,
                    tbl_os_item.custo_peca*qtde
                                                    AS total_peca                  ," : '';
    $sql.= "
                    tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                    tbl_status_pedido.descricao     AS status_pedido               ,
                    tbl_produto.referencia          AS subproduto_referencia       ,
                    tbl_produto.descricao           AS subproduto_descricao        ,
                    tbl_os_item.preco                                              ,
                    tbl_os_item.qtde                                               ,
					tbl_os_item.faturamento_item    AS faturamento_item
            FROM    tbl_os_produto
            JOIN    tbl_os_item USING (os_produto)
            JOIN    tbl_produto USING (produto)
            JOIN    tbl_peca    USING (peca)
            LEFT JOIN tbl_defeito USING (defeito)
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
            LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
            LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os_produto.os = $os
            $ordem
			/*
		)UNION(
			SELECT  tbl_produto.referencia                                         ,
                    tbl_produto.descricao                                          ,
                    NULL                   AS  serie                               ,
                    NULL                   AS  versao                              ,
                    tbl_orcamento_item.orcamento_item                              ,
                    NULL                   AS serigrafia                           ,
                    tbl_orcamento_item.pedido                                      ,
                    tbl_orcamento_item.pedido_item                                 ,
                    tbl_orcamento_item.peca                                        ,
                    NULL AS posicao                                                ,
                    NULL AS obs                                                    ,
                    NULL as custo_peco                                             ,
                    tbl_orcamento_item.servico_realizado AS servico_realizado_peca ,
					tbl_os_item.peca_serie                                         ,
					tbl_os_item.peca_serie_trocada                                 ,
                    NULL AS digitacao_item                                         ,
                    CASE WHEN tbl_pedido.pedido_blackedecker > 99999 then
                         LPAD((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
                    ELSE
                        LPAD(tbl_pedido.pedido_blackedecker::text,5,'0')
                    end                                      AS pedido_blackedecker,
					tbl_pedido.seu_pedido           AS seu_pedido                  ,
                    tbl_pedido.distribuidor                                        ,
                    tbl_defeito.descricao           AS defeito                     ,
                    tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.peca_critica           AS peca_critica                ,
                    tbl_peca.retorna_conserto       AS retorna_conserto            ,
					tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
                    NULL AS nota_fiscal                                            ,
                    NULL AS data_nf                                                ,
                    tbl_peca.descricao              AS descricao_peca              ,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao ,
                    tbl_status_pedido.descricao     AS status_pedido               ,
                    tbl_produto.referencia          AS subproduto_referencia       ,
                    tbl_produto.descricao           AS subproduto_descricao        ,
                    tbl_orcamento_item.preco                                       ,
                    tbl_orcamento_item.qtde                                        ,
					NULL AS faturamento_item
            FROM    tbl_os
			JOIN    tbl_orcamento ON tbl_orcamento.os = tbl_os.os
            JOIN    tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
            JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
            JOIN    tbl_peca    USING (peca)
            LEFT JOIN tbl_defeito USING (defeito)
            LEFT JOIN tbl_servico_realizado USING (servico_realizado)
            LEFT JOIN tbl_pedido         ON tbl_orcamento_item.pedido       = tbl_pedido.pedido
            LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE   tbl_os.os = $os
            ORDER BY tbl_peca.descricao
		)*/";
	// Adicionei Este UNION - Fabio 09-10-2007
	$res = pg_query($con,$sql);
	$total = pg_num_rows($res);

    //echo nl2br($sql);

	if ($login_fabrica == 45 or $login_fabrica == 24) {
		$sql_orcamento = "SELECT 
							tbl_orcamento_item.pedido                                      ,
							tbl_orcamento_item.pedido_item                                 ,
							tbl_orcamento_item.peca                                        ,
							tbl_orcamento_item.pedido                                      ,
							tbl_peca.referencia             AS referencia_peca             ,
							tbl_peca.descricao              AS descricao_peca              ,
							tbl_orcamento_item.servico_realizado AS servico_realizado_peca ,
							tbl_servico_realizado.descricao AS servico_realizado_descricao ,
							tbl_defeito.descricao           AS defeito                     ,
							tbl_orcamento_item.preco                                       ,
							tbl_orcamento_item.preco_venda                                 ,
							tbl_orcamento.aprovado                                         ,
							TO_CHAR (tbl_orcamento.data_digitacao,'DD/MM') AS data_digitacao,
							tbl_orcamento_item.qtde                                        
							FROM
							tbl_os
							JOIN    tbl_orcamento ON tbl_orcamento.os = tbl_os.os
							JOIN    tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
							JOIN    tbl_peca    USING (peca)
							LEFT JOIN tbl_defeito USING (defeito)
							LEFT JOIN tbl_servico_realizado USING (servico_realizado)
							WHERE tbl_os.os = $os
							ORDER BY tbl_peca.descricao";
		$res_orcamento = pg_query($con,$sql_orcamento);
	}

	?>
	
	<!-- Qtde de KM Wanke HD 375933 -->
	<? if(in_array($login_fabrica, array(91))){ ?>
		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
			<TR>
				<TD class='inicio' colspan='2'>QUANTIDADE DE KM</TD>
			</TR>
			<TR>
				<TD class="titulo" width='100'>DESLOCAMENTO&nbsp;</TD>
				<TD class="conteudo">&nbsp;<? echo number_format($qtde_km,2,',','.'); ?> KM</TD>
			</TR>
		</TABLE>
	<? }

	if ($login_fabrica == 1) {//HD 235182

		$sql = "SELECT codigo,
					   TO_CHAR(garantia_inicio, 'DD/MM/YYYY') as garantia_inicio,
					   TO_CHAR(garantia_fim, 'DD/MM/YYYY') as garantia_fim,
					   motivo
				  FROM tbl_certificado
				 WHERE os = $os
				   AND fabrica = $login_fabrica";

		$res_certificado = pg_query($con, $sql);
		$tot = pg_num_rows($res_certificado);

		if ($tot > 0) {?>

			<table width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='tabela'>
				<tr>
					<td class='inicio'>&nbsp;CERTIFICADO DE GARANTIA</td>
				</tr>
				<tr>
					<th class="titulo2">Código</th>
					<th class="titulo2">Data Inicio</th>
					<th class="titulo2">Data Termino</th>
					<th class="titulo2">Motivo</th>
				</tr><?php
				for ($i = 0; $i < $tot; $i++) {
					echo '<tr>';
						echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'codigo').'</td>';
						echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'garantia_inicio').'</td>';
						echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'garantia_fim').'</td>';
						echo '<td class="conteudo" style="text-align:center;">'.pg_fetch_result($res_certificado, $i, 'motivo').'</td>';
					echo '</tr>';
				}?>
			</table><?php

		}

	}?>
	<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
	<TR>
		<TD colspan="<? echo ($login_fabrica == 1)?"9":"7"?>" class='inicio'>
<?echo "&nbsp;".traduz("diagnosticos.componentes.manutencoes.executadas",$con,$cook_idioma);?>

</TD>
	</TR>
	<TR>
	<!--     <TD class="titulo">EQUIPAMENTO</TD> -->
		<?
		if($os_item_subconjunto == 't') {
			echo "<TD class='titulo2'>".traduz("subconjunto",$con,$cook_idioma)."</TD>";
			echo "<TD class='titulo2'>".traduz("posicao",$con,$cook_idioma)."</TD>";
		}
		?>
		<TD class="titulo2">
		<? echo traduz("componente",$con,$cook_idioma); ?>
</TD>
		<TD class="titulo2">
		<? echo traduz("qtd",$con,$cook_idioma); ?></TD>
		<? if ($login_fabrica == 1 and 1==2) echo "<TD class='titulo'>".traduz("preco",$con,$cook_idioma)."</TD>"; ?>
	<? if ($mostrar_valor_pecas) { //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item?>
    <TD class='titulo2'><?fecho('preco', $con, $cook_idioma)?></TD>
    <TD class='titulo2'><?fecho('total', $con, $cook_idioma)?></TD>
    <?}?>
		<TD class="titulo2"><?echo traduz("digita",$con,$cook_idioma);?></TD>
		<TD class="titulo2">
		<? if($login_fabrica == 20 || $login_fabrica == 42){
				echo traduz("preco.bruto",$con,$cook_idioma);
			} else {
                if($login_fabrica == 87)
                    echo "Causa Falha";
                else
				    echo traduz("defeito",$con,$cook_idioma);
			}?>
        </TD>

        <?php if($login_fabrica == 87 AND 1 == 2){?>
		    <TD class="titulo2">Item Causador</TD>
        <?php }?>


        <TD class="titulo2">
            <? if($login_fabrica == 20 || $login_fabrica == 42){
                echo traduz("preco.liquido",$con,$cook_idioma);
            } else if($login_fabrica == 96){
                echo "Free of charge";
            } else {
                echo traduz("servico",$con,$cook_idioma);
            }?>
        </TD>

        <?php if($login_fabrica == 87){?>
		    <TD class="titulo2">SOAF</TD>
        <?php }?>

		<TD class="titulo2"><?echo traduz("pedido",$con,$cook_idioma);?></TD>

		<?//chamado 141 - exibir nf do fabricante para distrib apenas britania?>
		<?if ($login_fabrica == 3) { /* ALTERADO TODA A ROTINA DE NF - HD 8973 */?>
			<TD class="titulo2" colspan='2' nowrap><?echo traduz("n.f.fabricante",$con,$cook_idioma);?></TD>
		<?}?>

		<TD class="titulo2">
            <?php 
                if($login_fabrica == 87){
                    echo "NF";
            }else{
                    echo traduz("nota.fiscal",$con,$cook_idioma);
            }?>
        </TD>

		<?if ($login_fabrica <> 3) {?>
			<TD class="titulo2"><?echo traduz("emissao",$con,$cook_idioma);?></TD>
		<?}

		//Gustavo 12/12/2007 HD 9095
		if ($login_fabrica == 35 or $login_fabrica==45) {?>
		<TD class="titulo2"><?echo traduz("conhecimento",$con,$cook_idioma);echo "</TD>";
		}
		//linha de informatica da Britania
		$sqllinha =	"SELECT tbl_linha.informatica
					FROM    tbl_os
					JOIN    tbl_produto USING (produto)
					JOIN    tbl_linha USING (linha)
					WHERE   tbl_os.fabrica = $login_fabrica
					AND     tbl_linha.informatica = 't'
					AND     tbl_os.os = $os";
		$reslinha = pg_query($con,$sqllinha);

		if (pg_num_rows($reslinha) > 0) {
			$linhainf = trim(pg_fetch_result($reslinha,0,informatica)); //linha informatica para britania
		}
		if ($linhainf == 't') {
			echo "<TD class='titulo2'>".traduz("serie.peca",$con,$cook_idioma)."</TD>";
			echo "<TD class='titulo2'>".traduz("serie.peca.trocada",$con,$cook_idioma)."</TD>";
		}
		?>
	</TR>

	<?
	# Exibe legenda de Peças de Retorno Obrigatório para a Gama
	$exibe_legenda = 0;
	$manual_ja_imprimiu = 0;
	for ($i = 0 ; $i < $total ; $i++) {
		$pedido                  = trim(pg_fetch_result($res,$i,pedido));
		$pedido_item             = trim(pg_fetch_result($res,$i,pedido_item));
		$pedido_blackedecker     = trim(pg_fetch_result($res,$i,pedido_blackedecker));
		$seu_pedido              = trim(pg_fetch_result($res,$i,seu_pedido));
		$os_item                 = trim(pg_fetch_result($res,$i,os_item));
		$peca                    = trim(pg_fetch_result($res,$i,peca));
        $peca_causadora          = trim(pg_fetch_result($res,$i,peca_causadora));
        $soaf                    = trim(pg_fetch_result($res,$i,'soaf'));
		$faturamento_item        = trim(pg_fetch_result($res,$i,faturamento_item));
		//chamado 141 - britania - pega nota fiscal do distribuidor
		if ($login_fabrica == 3) {
			$nota_fiscal_distrib = trim(pg_fetch_result($res,$i,nota_fiscal));
			$data_nf_distrib     = trim(pg_fetch_result($res,$i,data_nf));
			$nota_fiscal         = "";
			$data_nf             = "";
			$link_distrib        = 0;
		} else {
			$nota_fiscal         = trim(pg_fetch_result($res,$i,nota_fiscal));
			$data_nf             = trim(pg_fetch_result($res,$i,data_nf));
		}
		$status_pedido           = trim(pg_fetch_result($res,$i,status_pedido));
		$obs_os_item             = trim(pg_fetch_result($res,$i,obs));
		$distribuidor            = trim(pg_fetch_result($res,$i,distribuidor));
		$digitacao               = trim(pg_fetch_result($res,$i,digitacao_item));
		$preco                   = trim(pg_fetch_result($res,$i,preco));
		$descricao_peca          = trim(pg_fetch_result($res,$i,descricao_peca));
		$preco                   = number_format($preco,2,',','.');

		$peca_serie              = trim(pg_fetch_result($res,$i,peca_serie));
		$peca_serie_trocada      = trim(pg_fetch_result($res,$i,peca_serie_trocada));
        $servico_realizado_descricao      = trim(pg_fetch_result($res,$i,servico_realizado_descricao));

		/*Nova forma de pegar o número do Pedido - SEU PEDIDO  HD 34403 */
		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

		$res_idioma = @pg_query($con,$sql_idioma);
		if (@pg_num_rows($res_idioma) >0) {
			$descricao_peca  = trim(@pg_fetch_result($res_idioma,0,descricao));
		}
		//--=== Tradução para outras linguas ===================================================================

		/*====--------- INICIO DAS NOTAS FISCAIS ----------===== */
		/* ALTERADO TODA A ROTINA DE NF - HD 8973 */
		/*############ BLACKEDECKER ############*/
		if ($login_fabrica == 1 OR $login_fabrica == 96){
			if (strlen ($nota_fiscal) == 0) {
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(nota_fiscal) As nota_fiscal ,
							TO_CHAR(data, 'DD/MM/YYYY') AS emissao
							FROM    tbl_pendencia_bd_novo_nf
							WHERE   posto        = $login_posto
							AND     pedido_banco = $pedido
							AND     peca         = $peca";
					$resx = pg_query ($con,$sql);
					// HD22338
					if (pg_num_rows ($resx) > 0 AND 1==2) {
						$nf   = trim(pg_fetch_result($resx,0,nota_fiscal));
						$link = 0;
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
					} else {
						// HD 30781
						$sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
							TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
							FROM    tbl_os
							JOIN    tbl_os_produto USING (os)
							JOIN    tbl_os_item USING (os_produto)
							JOIN    tbl_peca USING(peca)
							WHERE   posto        = $login_posto
							AND     tbl_os_item.pedido= $pedido
							AND     tbl_os_item.peca         = $peca
							AND     tbl_peca.produto_acabado IS TRUE ";
						$resnf = pg_query ($con,$sql);
						if(pg_num_rows($resnf) >0){
							$nf   = trim(pg_fetch_result($resnf,0,nota_fiscal_saida));
							$link = 0;
							$data_nf = trim(pg_fetch_result($resnf,0,data_nf_saida));
						} else {
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
						}
					}
				} else {
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			} else {
				$nf = $nota_fiscal;
			}

		/*############ BRITANIA ############*/
		}elseif ($login_fabrica == 3){

			//Nota do fabricante para distribuidor
			//NF para BRITANIA (DISTRIBUIDORES E FABRICANTES chamado 141) =============================

			if (strlen($pedido) > 0) {
				if(strlen($distribuidor) > 0){

					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento_item.pedido  = $pedido
							AND     tbl_faturamento_item.peca    = $peca
							AND     tbl_faturamento.posto = $distribuidor";

					$resx = pg_query ($con,$sql);
					if (pg_num_rows ($resx) > 0) {
						$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
						$link    = 0;
					} else {
						$nf      = 'Pendente'; #HD 16354
						$data_nf = '';
						$link    = 0;
					}

					if ($distribuidor == 4311) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido  = $pedido
								/*AND     tbl_faturamento_item.peca  = $peca*/
								AND     tbl_faturamento_item.os_item = $os_item
								";
								//retirado por Samuel 4/12/2007 - Um nf do distrib atendendo 2 os não tem como gravar 2 os_item.
								// Coloquei AND     tbl_faturamento_item.os_item = $os_item - Fabio - HD 7591

						if ($login_posto != 4311) {
						if ($login_e_distribuidor == "t"){
							$sql .= "AND     tbl_faturamento.posto        = $posto_verificado ";
						} else {
							$sql .= "AND     tbl_faturamento.posto        = $login_posto ";
							}
						}

						$sql .= "AND     tbl_faturamento.distribuidor = 4311";

						$resx = pg_query ($con,$sql);
						if (pg_num_rows ($resx) > 0) {
							$nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
							$link_distrib        = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link_distrib        = 0;
						}
					}

					if($distribuidor != 4311) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca   = $peca
								AND     tbl_faturamento.posto       <> $distribuidor;";
						$resx = pg_query ($con,$sql);

						if (pg_num_rows ($resx) > 0) {
							$nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
							$link_distrib        = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link_distrib        = 0;
						}
					}
				} else {
					//(tbl_faturamento_item.os = $os) --> HD3709
					/*HD 72977*/
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
								TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento_item.pedido = $pedido
						AND     tbl_faturamento_item.peca   = $peca
						AND     (length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os = $os";

						if($gambiara=='t'){
							$sql .= "OR tbl_faturamento_item.os_item = $os_item )
							AND     tbl_faturamento.posto       = $xlogin_posto";
						} else {
							$sql  .=  ")
							AND     tbl_faturamento.posto       = $login_posto";
						}
						$resx = pg_query ($con,$sql);

					if (pg_num_rows ($resx) > 0){
						$nf                  = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf             = trim(pg_fetch_result($resx,0,emissao));
						//se fabrica atende direto posto seta a mesma nota

						//hd 22576
						if ($login_posto <> 4311) {
							$nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
							$link = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link                = 0;
						}
					} else {
						//HD 77790 HD 125880
						$sqly = "SELECT	tbl_faturamento.nota_fiscal                                         ,
													to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
									FROM tbl_faturamento_item
									JOIN   tbl_faturamento  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = $login_fabrica
									JOIN   tbl_peca             ON tbl_faturamento_item.peca = tbl_peca.peca
									JOIN tbl_os_troca ON tbl_faturamento_item.os = tbl_os_troca.os AND tbl_os_troca.pedido = $pedido
									WHERE tbl_faturamento_item.pedido = $pedido
									AND     (
													(length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os IS NULL)  OR tbl_faturamento_item.os = $os";
						if($gambiara=='t'){
							$sqly .= "OR tbl_faturamento_item.os_item = $os_item )
							AND     tbl_faturamento.posto       = $xlogin_posto";
						} else {
							$sqly  .=  ")
							AND     tbl_faturamento.posto       = $login_posto";
						}
						$resy = pg_query ($con,$sqly);

						if (pg_num_rows ($resy) > 0){
							$nf                  = trim(pg_fetch_result($resy,0,nota_fiscal));
							$data_nf             = trim(pg_fetch_result($resy,0,emissao));
							//se fabrica atende direto posto seta a mesma nota

							//hd 22576
							if ($login_posto <> 4311) {
								$nota_fiscal_distrib = trim(pg_fetch_result($resy,0,nota_fiscal));
								$data_nf_distrib     = trim(pg_fetch_result($resy,0,emissao));
								$link = 1;
							} else {
								$nota_fiscal_distrib = "";
								$data_nf_distrib     = "";
								$link                = 0;
							}
						} else {
							$nf                  = "Pendente";
							$data_nf             = "";
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link                = 0;
						}
					}
				}
			} else {
				$nf                  = "";
				$data_nf             = "";
				$nota_fiscal_distrib = "";
				$data_nf_distrib     = "";
				$link = 0;
			}

		/*############ LENOXX ############*/
		}elseif ($login_fabrica==11){
				 # Agora o pedido da peça ta amarrado no faturamento item: Fabio 09/08/2007
				if (strlen($faturamento_item)>0){
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento.fabrica=$login_fabrica
								AND     tbl_faturamento_item.faturamento_item = $faturamento_item";
						$resx = pg_query ($con,$sql);
						#echo nl2br($sql);
						if (pg_num_rows ($resx) > 0) {
							$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf = trim(pg_fetch_result($resx,0,emissao));
							$link = 1;
						} else {
							$nf ="Pendente";
							$data_nf="";
							$link = 0;
						}
				} else {
					if (strlen($pedido) > 0) {
							$nf ="Pendente";
							$data_nf="";
							$link = 0;
					} else {
							$nf ="";
							$data_nf="";
							$link = 0;
					}
				}

		/*############ CADENCE ############*/
		}elseif ($login_fabrica==35 or $login_fabrica == 45) {
			if (strlen ($nota_fiscal) == 0){
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_query ($con,$sql);

					if (pg_num_rows ($resx) > 0) {
						$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
						$link = 1;
					} else {
						//cadence relaciona pedido_item na os_item
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
										tbl_faturamento.posto,
										tbl_faturamento.conhecimento
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido      = $pedido
								AND     tbl_faturamento_item.peca        = $peca";
						if($login_fabrica == 35) {
							$sql.= " AND     tbl_faturamento_item.pedido_item = $pedido_item";
						}
						$resx = pg_query ($con,$sql);

						if (pg_num_rows ($resx) > 0) {
							$nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf      = trim(pg_fetch_result($resx,0,emissao));
							$conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
							$link         = 1;
						} else {
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
						}
					}
				} else {
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			} else {
				$nf = $nota_fiscal;
			}
		/*############ DEMAIS FABRICANTES ############*/
		} else {
			if (strlen ($nota_fiscal) == 0){
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca ";
					if($login_fabrica == 51 or $login_fabrica == 81) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
					if ($login_fabrica == 2) {
						$sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal     ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
							JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
							JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
							JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica = $login_fabrica
							WHERE    tbl_faturamento_item.peca = $peca";
					}
					$resx = pg_query ($con,$sql);

					if (pg_num_rows ($resx) > 0) {
						$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
						$link = 1;
					} else {
						$condicao_01 = "";
						if (strlen ($distribuidor) > 0) {
							$condicao_01 = " AND tbl_faturamento.distribuidor = $distribuidor ";
						}
						$sql  = "SELECT
									trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
									tbl_faturamento.posto                            AS posto
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca   = $peca
								$condicao_01 ";
						if($login_fabrica == 51 or $login_fabrica == 81) $sql.=" AND     tbl_faturamento_item.os_item = $os_item ";
						$resx = pg_query ($con,$sql);

						if (pg_num_rows ($resx) > 0) {
							$nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf      = trim(pg_fetch_result($resx,0,emissao));
							$link         = 1;
						} else {
							//Faturamento manual do distrib
							$sqlm = "SELECT tbl_faturamento.nota_fiscal              AS nota_fiscal ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao
									FROM    tbl_faturamento
									JOIN    tbl_faturamento_item USING (faturamento)
									WHERE   tbl_faturamento.fabrica = 10
									AND     tbl_faturamento_item.os = $os
									AND     tbl_faturamento_item.peca = $peca
									LIMIT 1";
							$resm = pg_query ($con,$sqlm);
							if (pg_num_rows ($resm) > 0) {
								$nf           = pg_fetch_result($resm,0,nota_fiscal);
								$data_nf      = trim(pg_fetch_result($resm,0,emissao));
								$link    = 1;
								$manual_ja_imprimiu = 1;
							}else{ 
								$nf      = "Pendente";
								$data_nf = "";
								$link    = 1;
								if($login_fabrica==6 and strlen($data_finalizada)>0){ //hd 3437
									$nf = "Atendido";
								}
							}
						}
					}
				} else {
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			} else {
				$nf = $nota_fiscal;
			}
		}
		//HD 18479
		if($fabrica==3){
			if((strlen($pedido)>0 AND strlen($peca)>0) AND $nf=="Pendente"){
				$sql = "SELECT motivo
						FROM   tbl_pedido_cancelado
						WHERE  pedido = $pedido
						AND    peca   = $peca
						AND    posto  = $login_posto;";
				$resx = pg_query ($con,$sql);
				if (pg_num_rows ($resx) > 0) {
					$motivo = pg_fetch_result($resx,0,motivo);
					$nf           = "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
					$data_nf      = "-";
					$link         = 1;
				}
			}
			//HD 20787
			if(strlen(trim($nota_fiscal_distrib))==0 AND $nf<>'Pendente' and strlen($pedido) > 0){
				$sql = "SELECT motivo
						FROM   tbl_pedido_cancelado
						WHERE  pedido = $pedido
						AND    peca   = $peca
						AND    posto  = $login_posto;";
				$resx = pg_query ($con,$sql);
				if (pg_num_rows ($resx) > 0) {
					$motivo = pg_fetch_result($resx,0,motivo);
					$nota_fiscal_distrib = "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
				}
			}
		}
		/*====--------- FIM DAS NOTAS FISCAIS ----------===== */

		// $status_os -> variavel pegada lá em cima
		$msg_peca_intervencao="";

		$bloqueada_pc           = pg_fetch_result($res,$i,bloqueada_pc);
		$peca_critica           = pg_fetch_result($res,$i,peca_critica);
		$servico_realizado_peca = pg_fetch_result($res,$i,servico_realizado_peca);
		$retorna_conserto       = pg_fetch_result($res,$i,retorna_conserto);

		$devolucao_obrigatoria  = pg_fetch_result($res,$i,devolucao_obrigatoria);

		if (($login_fabrica==1 OR $login_fabrica==3 OR $login_fabrica==6 OR $login_fabrica==11) AND ( $bloqueada_pc=='t' OR $retorna_conserto=='t' OR $peca_critica=='t')){

			if ($login_fabrica==11) {
				$id_servico_realizado			= 61;
				$id_servico_realizado_ajuste	= 498;
			}
			if ($login_fabrica==6) {
				$id_servico_realizado			= 1;
				$id_servico_realizado_ajuste	= 35;
			}
			if ($login_fabrica==3) {
				$id_servico_realizado			= 20;
				$id_servico_realizado_ajuste	= 96;
			}
			if ($login_fabrica==1) {
				$id_servico_realizado			= 62;
				$id_servico_realizado_ajuste	= 64;
			}

			$cor_intervencao = "#FF6666";

			if ($login_fabrica==1 AND $status_os=='87' AND $peca_critica=='t'){
				$cor_intervencao = "#FFFFFF";
			}

			if (($status_os=='62' OR $status_os=='87' OR $status_os=='72' OR $status_os=='116') AND $servico_realizado_peca==$id_servico_realizado){
				$msg_peca_intervencao=" <b style='font-weight:normal;color:$cor_intervencao;font-size:10px'>(".traduz("aguardando.autorizacao.da.fabrica",$con,$cook_idioma).")</b>";
			}

			if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado){
				$msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(".traduz("autorizado.pela.fabrica",$con,$cook_idioma).")</b>";
				$cancelou_peca = "sim";
			}

			if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado_ajuste){
				$msg_peca_intervencao=" <b style='font-weight:normal;color:#CC0000;font-size:10px'>(".traduz("pedido.cancelado.pela.fabrica",$con,$cook_idioma).")</b>";
				$cancelou_peca = "sim";
			}

			if (($status_os=='62' OR $status_os=='73' OR $status_os=='87' OR $status_os=='116') AND strlen($pedido) > 0 AND $servico_realizado_peca==$id_servico_realizado) {
				$msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(".traduz("autorizado.pela.fabrica",$con,$cook_idioma).")</b>";
				$cancelou_peca = "sim";
			}
		}

		$cor_linha_peca = "";
		if ($login_fabrica==1 AND $status_os=='87' AND $peca_critica=='t'){
			$cor_linha_peca = " ;background-color:#FF2D2D";
		}

		?>
		<TR class="conteudo"
		<?php
			if ($devolucao_obrigatoria == "t" and ($login_fabrica == 51 or $login_fabrica == 81)){
				$exibe_legenda++;
				echo " style='background-color:#FFC0D0'";
			}?>
		>
		<?
		if($os_item_subconjunto == 't') {
			echo "<TD style=\"text-align:left;\">".pg_fetch_result($res,$i,subproduto_referencia) . " - " . pg_fetch_result($res,$i,subproduto_descricao)."</TD>";
			echo "<TD style=\"text-align:center;\">".pg_fetch_result($res,$i,posicao)."</TD>";
		}
		?>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="text-align:left;<?=$cor_linha_peca?>"><? echo pg_fetch_result($res,$i,referencia_peca) . " - " . $descricao_peca; echo $msg_peca_intervencao?></TD>
		<TD style="text-align:center;<?=$cor_linha_peca?>"><? echo pg_fetch_result($res,$i,qtde) ?></TD>
    	<?
    	if ($mostrar_valor_pecas/* and ($nf != 'Cancelada' and $nf != ''*/) { //28/08/2010 MLG HD 237471  Fábricas que mostra o valor da peça, baseado na tbl_os_item?>
		<TD style='text-align:right;'><?=number_format(pg_fetch_result($res,$i,preco_peca),2,",",".")?></TD>
		<TD style='text-align:right;'><?=number_format(pg_fetch_result($res,$i,total_peca),2,",",".")?></TD>    	
		<?}
		if($login_fabrica==20){
			$sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = (select tbl_posto_fabrica.tabela from tbl_posto_fabrica JOIN tbl_os USING (posto) WHERE tbl_os.os = $os AND tbl_posto_fabrica.fabrica = $login_fabrica)";
			$res2 = pg_query ($con,$sql);
			$preco_bruto = number_format (@pg_fetch_result($res2,0,preco),2,",",".");
		}

		if($login_fabrica==42){ // HD 341053
			
			$produto_referencia = pg_fetch_result($res,$i,referencia_peca);
			// se nao tiver extrato, zerar valores
			$sql_ex = "select extrato from tbl_os_extra where os = $os AND extrato is not null;";
			$res_ex = pg_query($con,$sql_ex);
			if(pg_num_rows($res_ex) == 0) {
				$preco_bruto = 0.00;
				$preco		 = 0.00;
			}
			else {

				$sql_preco = "SELECT tbl_os_item.custo_peca,
								     tbl_os_item.preco
							  FROM tbl_os_item 
							  JOIN tbl_os_produto USING (os_produto)
							  JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca 
							  AND tbl_peca.referencia = '$produto_referencia'
							  AND tbl_peca.fabrica = $login_fabrica 
							  WHERE os = $os ";
				//echo nl2br($sql_preco);
				$res_preco = pg_query($con,$sql_preco);
				if(pg_num_rows($res_preco)>0)
					$makita_preco_bruto = number_format (pg_result($res_preco,0,preco),2,".",".");
					$preco = number_format (pg_result($res_preco,0,custo_peca),2,".",".");
				/*
				$sql_ipi = "SELECT ipi from tbl_peca where peca = $peca";
				$res_ipi = pg_exec($con,$sql_ipi);
				if(pg_num_rows($res_ipi)>0) {

					$ipi			= pg_result($res_ipi,0,0);
					$preco_liq_val	= $makita_preco_bruto * ($ipi/100);
					$preco_liq		= $preco_liq_val + $makita_preco_bruto;
					$preco			= $preco_liq * 1.2;
					$preco			= number_format($preco,2,'.','');

				}
				*/
				$preco_bruto = $makita_preco_bruto ;
				unset($makita_preco);

			}

		} // fim hd 341053
		?>
		<TD style="text-align:center;<?=$cor_linha_peca?>"><? echo pg_fetch_result($res,$i,digitacao_item) ?></TD>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="<?=$cor_linha_peca?>"><?   if($login_fabrica == 20 || $login_fabrica == 42)echo $preco_bruto; else echo pg_fetch_result($res,$i,defeito); ?></TD>
        
        <?php if($login_fabrica == 87){
            $sql_peca_causadora = "SELECT referencia, descricao FROM tbl_peca WHERE peca = {$peca_causadora}";                
            $res_peca_causadora = pg_query( $con, $sql_peca_causadora);
        ?>
            
            <?php if(1 ==2){?>
            <td nowrap style="<?=$cor_linha_peca?>">
                <?php 
                    if(pg_num_rows($res_peca_causadora) ){
                       $referencia_causadora = pg_fetch_result($res_peca_causadora,0,'referencia');
                       $descricao_causadora = pg_fetch_result($res_peca_causadora,0,'descricao');
                    
                        echo "{$referencia_causadora} - {$descricao_causadora}";
                    }else{
                        echo "&nbsp;";
                    }
                ?>
            </td>
            <? }?>
            <td><?php echo $servico_realizado_descricao;?></td>
            <td>
                <?php
                    if(!empty($soaf)){
                        $sql_soaf = "SELECT descricao from tbl_tipo_soaf WHERE fabrica = $login_fabrica  AND tipo_soaf = $soaf;";
                        $res_soaf = pg_query($con, $sql_soaf);
                        if(pg_num_rows($res_soaf)){
                            echo pg_fetch_result($res_soaf, 0, 'descricao');   
                        }else echo "&nbsp;";
                    }else echo "&nbsp;";
                ?>
            </td>
        <?php }?>


		<?php if($login_fabrica <> 87){?>
            <TD
            <?php
                if ($login_fabrica == 51){
                    echo " nowrap ";
            }?>
                style="text-align:right;<?=$cor_linha_peca?>"><?   if($login_fabrica == 20 || $login_fabrica == 42)echo $preco; else echo pg_fetch_result($res,$i,servico_realizado_descricao) ?></TD>
        <?php }?>
		<TD
		<?php
			if ($login_fabrica == 51){
				echo " nowrap ";
		}?>
		style="text-align:CENTER;<?=$cor_linha_peca?>">
		<? if(strtolower($nf) <> 'atendido'){?>
			<a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'>
		<?}
			if ($login_fabrica == 1){
				echo $pedido_blackedecker;
			} else {
				echo $pedido;
			}?>
		<? if(strtolower($nf) <> 'atendido'){?>
			</a>
			<?}?>
        </TD>

		<TD style="text-align:CENTER;<?=$cor_linha_peca?>" nowrap <? if (strlen($data_nf)==0) echo "colspan='2'"; ?>>
        <?php
		if (strtolower($nf) <> 'pendente' and strtolower($nf) <> 'atendido') {

			if ($link == 1) {
				echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=".$nf."&peca=$peca' target='_blank'> $nf </a>";
			} else {
				echo "<acronym title='Nota Fiscal do fabricante.' style='cursor:help;'> $nf </acronym>";
			}

		} else {

			if ($login_fabrica == 51 or $login_fabrica == 81) {

				if ($login_posto == 4311) { // HD 52445

					$sql  = "SELECT tbl_embarque.embarque,
								to_char(liberado ,'DD/MM/YYYY') as liberado,
								to_char(embarcado ,'DD/MM/YYYY') as embarcado,
								faturar
						FROM tbl_embarque
						JOIN tbl_embarque_item USING (embarque)
						WHERE tbl_embarque_item.os_item = $os_item ";

					$resX = pg_query ($con,$sql);
					if (pg_num_rows ($resX) > 0) {
						$liberado  = pg_fetch_result($resX,0,liberado);
						$embarcado = pg_fetch_result($resX,0,embarcado);
						$faturar   = pg_fetch_result($resX,0,faturar);

						if (strlen($embarcado) > 0 and strlen($faturar) == 0){
							echo traduz("embarque",$con,$cook_idioma)." " . pg_fetch_result ($resX,0,embarque);
						} else {
							echo traduz("embarcada",$con,$cook_idioma)." ". pg_fetch_result($resX,0,liberado);
						}
					} else {
						$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
						$resY = pg_query ($con,$sql);

						if (pg_num_rows ($resY) > 0) {
							echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
						} else {
							if( strtolower($nf) <> 'atendido'){
							echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
							}
							echo "$nf &nbsp;";
						}
					}

				} else {

					$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
					$resY = pg_query ($con,$sql);

					if (pg_num_rows ($resY) > 0) {
						echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
					} else {
						if( strtolower($nf) <> 'atendido'){
						echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
						}
						echo "$nf &nbsp;";
					}

				}

			} 
			elseif($login_fabrica == 94){
					if(strlen($peca)>0 AND strlen($pedido)>0){
						$sql  = "SELECT * FROM tbl_pedido_cancelado
											WHERE peca=$peca and pedido=$pedido";
						$resY = pg_query ($con,$sql);
						if (pg_num_rows ($resY) > 0) {
							echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
						}else{
							echo "$nf &nbsp;";
						}
					}	
			}
			else {

				$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
				$resY = pg_query ($con,$sql);

				if (pg_num_rows ($resY) > 0) {
					echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
				} else {

					if( strtolower($nf) <> 'atendido'){
						echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
					}

					echo "$nf &nbsp;";

				}

			}

		}?>
		</TD>

		<?//incluido data de emissao por Wellington chamado 141 help-desk

		if (strlen($data_nf) > 0){
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "$data_nf ";
			echo "</TD>";
		}

		//Gustavo 12/12/2007 HD 9095
		if ($login_fabrica == 35 or $login_fabrica == 45){
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "<A HREF='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$conhecimento' target = '_blank'>";
			 echo $conhecimento;
			echo "</A>";
			echo "</TD>";
		}

		//nf do distribuidor - chamado 141
		if ($login_fabrica==3) {
			echo "<TD style='text-align:CENTER;' nowrap>";

			if (strlen($nota_fiscal_distrib) > 0) {

				if ($link_distrib == 1) {
					echo "<acronym title='".traduz("nota.fiscal.do.distribuidor",$con,$cook_idioma).".' style='cursor:help;'><a href='nota_fiscal_detalhe.php?nota_fiscal=".$nota_fiscal_distrib."&peca=$peca' target='_blank'>$nota_fiscal_distrib  - $data_nf_distrib</a>";
				} else {
					echo "<acronym title='".traduz("nota.fiscal.do.distribuidor",$con,$cook_idioma).".' style='cursor:help;'> $nota_fiscal_distrib"." - ".$data_nf_distrib;
				}
			} else {
//				echo "a $nota_fiscal_distrib";
				//se não tiver nota do distrib verifica se está em embarque e exibe numero do embarque
				$sql  = "SELECT tbl_embarque.embarque,
								to_char(liberado ,'DD/MM/YYYY') as liberado,
								to_char(embarcado ,'DD/MM/YYYY') as embarcado,
								faturar
						FROM tbl_embarque
						JOIN tbl_embarque_item USING (embarque)
						WHERE tbl_embarque_item.os_item = $os_item ";

				// HD 7319 Paulo alterou para mostrar dia que liberou o embarque
				$resX = pg_query ($con,$sql);
				if (pg_num_rows ($resX) > 0) {
					$liberado  = pg_fetch_result($resX,0,liberado);
					$embarcado = pg_fetch_result($resX,0,embarcado);
					$faturar   = pg_fetch_result($resX,0,faturar);

					if(strlen($embarcado) > 0 and strlen($faturar) == 0){
						echo traduz("embarque",$con,$cook_idioma)." " . pg_fetch_result ($resX,0,embarque);
					} else {
						echo traduz("embarcada",$con,$cook_idioma)." ". pg_fetch_result($resX,0,liberado);
					}
				} else {
					//HD 20787
					if(strlen(trim($nota_fiscal_distrib))==0 and $nf<>'Pendente'){
						$sql = "SELECT motivo
								FROM   tbl_pedido_cancelado
								WHERE  pedido = $pedido
								AND    peca   = $peca
								;";
						$resx = @pg_query ($con,$sql);
						if (@pg_num_rows ($resx) > 0) {
							$motivo = pg_fetch_result($resx,0,motivo);
							echo  "<a href='#' title='$motivo'>".traduz("cancelada",$con,$cook_idioma)."</a>";
						}
					}
					// HD 7319 Fim
				}
			}
			echo "</TD>";
		}
		//linha de informatica da Britania
		if ($linhainf == 't') {
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "$peca_serie";
			echo "</TD>";
			echo "<TD style='text-align:CENTER;' nowrap>";
			echo "$peca_serie_trocada";
			echo "</TD>";
		}
		//linha de informatica da Britania?>
	</TR><?php
		// HD 8412
		/**
		 * @since HD 749085 - Black
		 */
		$mostra_obs = array(1, 3, 14, 35);
		if (in_array($login_fabrica, $mostra_obs) and strlen($obs_os_item) >0) {

			$obs_dez_percento = null;

			if ($login_fabrica == 14) {//HD 212179

				$sql_dez = "SELECT tbl_os_item.obs as obs
							  FROM tbl_os_item
							 WHERE tbl_os_item.os_item = $os_item
							   AND tbl_os_item.obs     = '### PEÇA INFERIOR A 10% DO VALOR DE MÃO-DE-OBRA ###'";

				$res_dez = pg_exec($con, $sql_dez);

				if (pg_numrows($res_dez) > 0) {
					$obs_dez_percento = '<font color="red"><b>Esta peça não será reposta em garantia conforme regra de reposição de peças</b></font>';
				}

			}

			echo "<tr>";
				echo "<td class='conteudo' colspan='100%'>";
					echo "Obs: ". ($obs_dez_percento != null ? $obs_dez_percento : $obs_os_item);
				echo "</td>";
			echo "</tr>";
		}
	}
	//NOTA FISCAL MANUAL - Se já achou e imprimiu no item, não precisa emitir
	if (($login_fabrica == 51 or $login_fabrica == 81) and $manual_ja_imprimiu == 0){
		$sqlm = "SELECT tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_faturamento_item.qtde,
					tbl_faturamento.nota_fiscal              AS nota_fiscal ,
					TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao
					FROM    tbl_faturamento
					JOIN    tbl_faturamento_item USING (faturamento)
					JOIN    tbl_peca on tbl_peca.peca = tbl_faturamento_item.peca
					WHERE   tbl_faturamento.fabrica = 10
					AND     tbl_faturamento_item.os = $os
					LIMIT 1";
		$resm = pg_query ($con,$sqlm);
		if (pg_num_rows ($resm) > 0) {
			$referenciam  = pg_fetch_result($resm,0,referencia);
			$descricaom   = pg_fetch_result($resm,0,descricao);
			$qtdem        = pg_fetch_result($resm,0,qtde);
			$nf           = pg_fetch_result($resm,0,nota_fiscal);
			$data_nf      = trim(pg_fetch_result($resm,0,emissao));
			echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
			echo "<tr><td align='center' class='inicio'>";
			echo "NOTA FISCAL MANUAL DO DISTRIBUIDOR";
			echo "</td></tr>";
			echo "<tr>";
				echo "<td align='center' class='conteudo_sac'>$referenciam</td>";
				echo "<td align='center' class='conteudo_sac'>$descricaom</td>";
				echo "<td align='center' class='conteudo_sac'>$qtdem</td>";
				echo "<td align='center' class='conteudo_sac'><a href='nota_fiscal_detalhe.php?nota_fiscal=".$nf."' target='_blank'>$nf</a></td>";
				echo "<td align='center' class='conteudo_sac'>$data_nf</td>";
			echo "</tr>";
			echo "</table>";
		}
	}
	
	//ORCAMENTO - WALDIR
	
	if ($login_fabrica == 45 or $login_fabrica == 24) {
		$num_pecas_orcamento = pg_num_rows($res_orcamento);
		
		echo "<input type='hidden' value='$num_pecas_orcamento' id='qtde_pecas_orcamento' name='qtde_pecas_orcamento'>";

		for ($f=0;$f<pg_num_rows($res_orcamento);$f++) {

			$peca_descricao_orcamento	= pg_fetch_result($res_orcamento,$f,descricao_peca);
			$peca_referencia_orcamento	= pg_fetch_result($res_orcamento,$f,referencia_peca);
			$qtde_orcamento				= pg_fetch_result($res_orcamento,$f,qtde);
			$pedido_orcamento			= pg_fetch_result($res_orcamento,$f,pedido);
			$data_digitacao_orcamento	= pg_fetch_result($res_orcamento,$f,data_digitacao);
			$defeito_descricao_orcamento= pg_fetch_result($res_orcamento,$f,defeito);
			$preco_orcamento			= pg_fetch_result($res_orcamento,$f,preco);
			$preco_venda_orcamento		= pg_fetch_result($res_orcamento,$f,preco_venda);
			$aprovado_orcamento			= pg_fetch_result($res_orcamento,$f,aprovado);
			$servico_descricao_orcamento= pg_fetch_result($res_orcamento,$f,servico_realizado_descricao);
			$preco_orcamento			= number_format($preco_orcamento,2,",",".");
			$preco_venda_orcamento		= number_format($preco_venda_orcamento,2,",",".");

			echo "<input type='hidden' value='$peca_referencia_orcamento-$peca_descricao_orcamento' id='peca_orcamento_$f' name='peca_orcamento_$f'>";
			echo "<input type='hidden' value='$preco_orcamento' id='preco_orcamento_$f' name='preco_orcamento_$f'>";
			
			if ($aprovado_orcamento == 'f') {
				$cor = '#FF6633';
			}

			if ($aprovado_orcamento == 't') {
				$cor = '#3399FF';
			}
			
			echo "<tr class='conteudo' style=background-color:$cor>";
			echo "<td  nowrap>$peca_referencia_orcamento - $peca_descricao_orcamento - R$ $preco_venda_orcamento</td>";
			echo "<td style='text-align:CENTER;' nowrap>$qtde_orcamento</td>";
			echo "<td style='text-align:CENTER;' nowrap>$data_digitacao_orcamento</td>";
			echo "<td style='text-align:RIGHT;' nowrap>$defeito_descricao_orcamento</td>";
			echo "<td style='text-align:RIGHT;' nowrap>$servico_descricao_orcamento</td>";
			echo "<td style='text-align:CENTER;' nowrap><a href='pedido_finalizado.php?pedido=$pedido_orcamento' target='_blank'>$pedido_orcamento</td>";
			echo "<td colspan=2 style='text-align:CENTER;' nowrap></td>";
			echo "<tr>";
		}
	}	
	
	//HD 145639:	ALTERADA A SQL PARA PUXAR O TIPO DE ATENDIMENTO DA tbl_os E NÃO DA tbl_os_troca, POIS COM ESTE
	//				CHAMADO A tbl_os_troca PODE TER MAIS DE UM ITEM, MAS O CAMPO situacao_atendimento

	//Chamado 2365
	/* HD 145639: ALTERANDO PARA MOSTRAR MAIS DE UM PRODUTO */
	if($login_fabrica == 1 && (in_array($tipo_atendimento,array(17,18,35,64,65,69)))) {
		#HD 15198
		$sql  = "
		SELECT
		tbl_os_troca.ri AS pedido,
		tbl_os.nota_fiscal_saida AS nota_fiscal,
		TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf,
		tbl_produto.descricao

		FROM
		tbl_os_troca
		JOIN tbl_os USING(os)
		JOIN tbl_produto ON tbl_os_troca.produto=tbl_produto.produto

		WHERE
		tbl_os.os = $os
		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os.posto = $login_posto
		";
		$resX = pg_query ($con,$sql);
		if(pg_num_rows($resX) > 0){
			for ($p = 0; $p < pg_num_rows($resX); $p++) {
				$Xpedido      = pg_fetch_result($resX, $p, pedido);
				$Xnota_fiscal = pg_fetch_result($resX, $p, nota_fiscal);
				$Xdata_nf     = pg_fetch_result($resX, $p, data_nf);
				$Xdescricao   = pg_fetch_result($resX, $p, descricao);

				echo "<tr align='center'>";
					echo "<td class='conteudo' align='center'><center>$Xdescricao</center></td>";
					echo "<td class='conteudo'></td>";
					echo "<td class='conteudo'></td>";
					echo "<td class='conteudo'></td>";
					echo "<td class='conteudo'></td>";
					echo "<td class='conteudo' align='center'><center>$Xpedido</center></td>";
					echo "<td class='conteudo' align='center'><center>$Xnota_fiscal</center></td>";
					echo "<td class='conteudo' align='center'><center>$Xdata_nf</center></td>";
				echo "<tr>";
			}
		}
		
	}

	?>
</TABLE>
<?php
	
	if($login_fabrica == 96){
		$sql = "SELECT 
					status.descricao as status_descricao
				FROM 
					tbl_os as os 
					JOIN tbl_status_checkpoint as status USING (status_checkpoint)
				WHERE 
					os.os = $os AND 
					os.fabrica = $login_fabrica AND
					os.status_checkpoint in (5,6,7) AND 
					os.tipo_atendimento = 93 
					";
		$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$status_descricao = pg_fetch_result($res,0,'status_descricao');

				$sql = "SELECT 
							total, 
							total_horas
						FROM 
							tbl_orcamento_os_fabrica
						WHERE 
							os = $os AND 
							fabrica = $login_fabrica";
				$res = pg_query($con, $sql);

				$total = strlen(pg_fetch_result($res,0,'total')) > 0 ? pg_fetch_result($res,0,'total') : "00";
				$total_horas = strlen(pg_fetch_result($res,0,'total_horas')) > 0 ? pg_fetch_result($res,0,'total_horas') : "&nbsp;";

				echo "<table width='700px' border='0'  cellspacing='1' cellpadding='2' align='center' class='Tabela'>";
					echo "<tr>";
						echo "<td class='inicio'>ORÇAMENTO</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td class='titulo2'>Descrição do Status</td>";
						echo "<td class='titulo2' style='text-align: right'>Total</td>";
						echo "<td class='titulo2' style='text-align: right'>Total de Horas</td>";
					echo "<tr>";
					echo "<tr>";
						echo "<td class='conteudo'>$status_descricao</td>";
						echo "<td class='conteudo' style='text-align: right'> R$ ".number_format($total, 2, ',', ' ')."</td>";
						echo "<td class='conteudo' style='text-align: right'>$total_horas</td>";
					echo "<tr>";
				echo "</table>";
			}
	}

//HD 214236: Auditoria Prévia de OS, mostrando status
if ($login_fabrica == 14 || $login_fabrica == 43) {

	$sql = "
	SELECT
	tbl_os_auditar.os_auditar,
	tbl_os_auditar.cancelada,
	tbl_os_auditar.liberado,
	TO_CHAR(tbl_os_auditar.data, 'DD/MM/YYYY HH24:MI') AS data ,
	TO_CHAR(CASE
		WHEN tbl_os_auditar.liberado_data IS NOT NULL THEN tbl_os_auditar.liberado_data
		WHEN tbl_os_auditar.cancelada_data IS NOT NULL THEN tbl_os_auditar.cancelada_data
		ELSE null
	END, 'DD/MM/YYYY HH24:MI') AS data_saida,
	tbl_os_auditar.justificativa

	FROM
	tbl_os_auditar

	WHERE
	tbl_os_auditar.os=$os
	";
	$res = pg_query($con, $sql);
	$n = pg_numrows($res);

	if ($n > 0) {
		echo "
		<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
			<TR>
				<TD class='inicio' style='text-align:center;' colspan='4' width='700'>
				AUDITORIA PRÉVIA
				</TD>
			</TR>
			<TR align='center'>
				<TD class='titulo2' align='center' width='70'>Status</TD>
				<TD class='titulo2' align='center' width='70'>Data Entrada</TD>
				<TD class='titulo2' align='center' width='70'>Data Saída</TD>
				<TD class='titulo2' align='center' width='490'>Justificativa</TD>
			</TR>";
		
		for ($i = 0; $i < $n; $i++) {
			//Recupera os valores do resultado da consulta
			$valores_linha = pg_fetch_array($res, $i);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha);

			if ($liberado == 'f') {
				if ($cancelada == 'f') {
					$legenda_status = "em análise";
					$cor_status = "#FFFF44";
				}
				elseif ($cancelada == 't') {
					$legenda_status = "reprovada";
					$cor_status = "#FF7744";
				}
				else {
					$legenda_status = "";
					$cor_status = "";
				}
			}
			elseif ($liberado == 't') {
				$legenda_status = "aprovada";
				$cor_status = "#44FF44";
			}
			else {
				$legenda_status = "";
				$cor_status = "";
			}

			echo "
			<TR align='center' style='background-color: $cor_status;'>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$legenda_status</TD>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data</TD>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data_saida</TD>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$justificativa</TD>
			</TR>";
		}
		
		echo "
		</TABLE>";
	}
}

if ($login_fabrica == 51 and $exibe_legenda > 0){
	echo "<BR>\n";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'>\n";
	echo "<TR style='line-height: 12px'>\n";
	echo "<TD width='5' bgcolor='#FFC0D0'>&nbsp;</TD>\n";
	echo "<TD style='padding-left: 10px; font-size: 14px;'><strong>Peças para Vistoria</strong></TD>\n";
	echo "</TR></TABLE>\n";
}

# adicionado por Fabio - 26/03/2007 - hd chamado 1392
# HD 14830 - HBTech
# HD 13618 - NKS
# HD 12657 - Dynacom
#HD 283928- Nova
if ($historico_intervencao) {
			/* HD 233857 Samuel alterou para mostrar todas as interações */
			$sql_status = "SELECT
						status_os,
						observacao,
						to_char(data, 'DD/MM/YYYY')   as data_status,
						admin,
						tbl_status_os.descricao
					FROM tbl_os_status
					JOIN tbl_status_os using(status_os)
					WHERE os=$os
					/* AND status_os IN (72,73,62,64,65,87,88,116,117,128) */
					ORDER BY data DESC";
	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		if ($login_fabrica==25){
			echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("justificativa.do.pedido.de.peca",$con,$cook_idioma)."</TD>\n";
		} else {
			echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("historico.de.intervencao",$con,$cook_idioma)."</TD>\n";
		}
		echo "</TR>\n";

		/* HD 233857 Samuel alterou para mostrar todas as interações */
		echo "<TR>\n";
		echo "<TD class='titulo2'>Data</TD>\n";
		echo "<TD class='titulo2'>Tipo intervenção/interação</TD>\n";
		echo "<TD class='titulo2'>Justificativa</TD>\n";
		echo "</TR>\n";
		for ($j=0;$j<$resultado;$j++){
			$status_os          = trim(pg_fetch_result($res_status,$j,status_os));
			$status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
			$status_data        = trim(pg_fetch_result($res_status,$j,data_status));
			$status_admin       = trim(pg_fetch_result($res_status,$j,admin));
			$descricao          = trim(pg_fetch_result($res_status,$j,descricao));

			if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0){
				$status_observacao = strstr($status_observacao,"Justificativa:");
				$status_observacao = str_replace("Justificativa:","",$status_observacao);
			}

			$status_observacao = trim($status_observacao);

			if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
			if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

			if ($login_fabrica==11 AND strlen($status_admin)>0){
				$status_observacao = trim(pg_fetch_result($res_status,$j,observacao));
			}

			echo "<TR>\n";
			echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";
			echo "<TD  class='justificativa' width='140px'  align='left' nowrap>$descricao<b></b></TD>\n";
			/* HD 233857 Samuel alterou para mostrar todas as interações
			if ($status_os==72){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("justificativa.do.posto",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==73){
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>".traduz("resposta.da.fabrica",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==62){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("os.em.intervencao",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==65){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("os.em.reparo.na.fabrica",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==64){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("resposta.da.fabrica",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==87 OR $status_os==116){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>".traduz("fabrica",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==88 OR $status_os==117){
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>".traduz("fabrica",$con,$cook_idioma)."</b></TD>\n";
			}
			if ($status_os==128){
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>".traduz("Retirou intervenção",$con,$cook_idioma)."</b></TD>\n";
			}
*/
			echo "<TD  class='justificativa' width='450px' align='left' colspan='5' >&nbsp;$status_observacao</TD>\n";
			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}
?>

<? //hd 24288
if ($login_fabrica==3) {
		$sql_status = "SELECT  tbl_os.os                            ,
								(SELECT tbl_status_os.descricao FROM tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
								tbl_os_status.observacao              ,
								to_char(tbl_os_status.data, 'dd/mm/yyy') AS data
								FROM tbl_os
						LEFT JOIN tbl_os_status USING(os)
						WHERE tbl_os.os    = $os
						AND   tbl_os.posto = $login_posto
						AND   tbl_os_status.status_os IN(
								SELECT status_os
								FROM tbl_os_status
								WHERE tbl_os.os = tbl_os_status.os
								AND status_os IN (98,99,101) ORDER BY tbl_os_status.data DESC
						)";
	$res_km = pg_query($con,$sql_status);

	if(pg_num_rows($res_km)>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='7' class='inicio'>&nbsp;".traduz("historico.atendimento.domicilio",$con,$cook_idioma)."</TD>\n";
		echo "</TR>\n";

		for($x=0; $x<pg_num_rows($res_km); $x++){
			$status_os    = pg_fetch_result($res_km, $x, status_os);
			$observacao   = pg_fetch_result($res_km, $x, observacao);
			$data         = pg_fetch_result($res_km, $x, data);

			echo "<tr>";
				echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_os</font></td>";
				echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$observacao</font></td>";
				echo "<td class='justificativa' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>


<?
# adicionado por Fabio
# HD 13940 - Bosch
if ($login_fabrica==20) {
	$sql_status = "SELECT
					tbl_os_status.status_os                                    ,
					tbl_os_status.observacao                                   ,
					to_char(tbl_os_status.data, 'DD/MM/YYYY')   as data_status ,
					tbl_os_status.admin                                        ,
					tbl_status_os.descricao                                    ,
					tbl_admin.nome_completo AS nome                            ,
					tbl_admin.email                                            ,
					tbl_promotor_treinamento.nome  AS nome_promotor            ,
					tbl_promotor_treinamento.email AS email_promotor
				FROM tbl_os
				JOIN tbl_os_status USING(os)
				LEFT JOIN tbl_status_os USING(status_os)
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
				LEFT JOIN tbl_promotor_treinamento ON tbl_os.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data ASC";
	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='4' class='inicio'>&nbsp;">traduz("historico",$con,$cook_idioma)."</TD>\n";
		echo "</TR>\n";
		echo "<TR>\n";
		echo "<TD  class='titulo2' width='100px' align='center'><b>".traduz("data",$con,$cook_idioma)."</b></TD>\n";
		echo "<TD  class='titulo2' width='170px' align='left'><b>".traduz("status",$con,$cook_idioma)."</b></TD>\n";
		echo "<TD  class='titulo2' width='260px' align='left'><b>".traduz("observacao",$con,$cook_idioma)."</b></TD>\n";
		echo "<TD  class='titulo2' width='170px' align='left'><b>".traduz("promotor",$con,$cook_idioma)."</b></TD>\n";
		echo "</TR>\n";
		for ($j=0;$j<$resultado;$j++){
			$status_os          = trim(pg_fetch_result($res_status,$j,status_os));
			$status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
			$status_data        = trim(pg_fetch_result($res_status,$j,data_status));
			$status_admin       = trim(pg_fetch_result($res_status,$j,admin));
			$descricao          = trim(pg_fetch_result($res_status,$j,descricao));
			$nome               = trim(strtoupper(pg_fetch_result($res_status,$j,nome)));
			$email              = trim(pg_fetch_result($res_status,$j,email));
			$nome_promotor      = trim(strtoupper(pg_fetch_result($res_status,$j,nome_promotor)));
			$email_promotor     = trim(pg_fetch_result($res_status,$j,email_promotor));

			echo "<TR>\n";
			echo "<TD  class='justificativa' align='center'><b>".$status_data."</b></TD>\n";
			echo "<TD  class='justificativa' align='left' nowrap>".$descricao."</TD>\n";
			echo "<TD  class='justificativa' align='left'>".$status_observacao."</TD>\n";
			echo "<TD  class='justificativa' align='left' nowrap>";
			if($status_os == 92) { // HD 55196
				echo "<acronym title='".traduz("nome",$con,$cook_idioma).": ".$nome_promotor." - \nEmail:".$email_promotor."'>".$nome_promotor;
			} else {
				echo "<acronym title='".traduz("nome",$con,$cook_idioma).": ".$nome." - \nEmail:".$email."'>".$nome;
			}
			echo "</TD>\n";
			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}
?>


<?// adicionado por Fabio - 05/11/2007 - HD chamado 6525
if (($login_fabrica == 3 or $login_fabrica==24 or $login_fabrica == 45) AND $login_posto == 6359) {
	$sql = "SELECT orcamento
			FROM tbl_orcamento
			WHERE os = $os";
	$res_orca = pg_query($con,$sql);
	$resultado = pg_num_rows($res_orca);
	if ($resultado>0){
		$orcamento = trim(pg_fetch_result($res_orca,0,orcamento));
		$sql = "SELECT	tbl_hd_chamado_item.hd_chamado_item,
						TO_CHAR(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data,
						tbl_hd_chamado_item.comentario
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado =  tbl_hd_chamado.hd_chamado
				WHERE tbl_hd_chamado.orcamento = $orcamento
				ORDER BY tbl_hd_chamado_item.data ASC";
		$res_orca = pg_query($con,$sql);
		$resultado = pg_num_rows($res_orca);
		if ($resultado>0){
			echo "<BR>\n";
			echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
			echo "<TR>\n";
			echo "<TD colspan='2' class='inicio'>".traduz("historico.de.orcamento",$con,$cook_idioma)."</TD>\n";
			echo "</TR>\n";
			for ($j=0;$j<$resultado;$j++){
				$orca_hd_chamado_item = trim(pg_fetch_result($res_orca,$j,hd_chamado_item));
				$orca_data            = trim(pg_fetch_result($res_orca,$j,data));
				$orca_comentario      = trim(pg_fetch_result($res_orca,$j,comentario));

				echo "<TR>\n";
				echo "<TD  class='justificativa' width='100px' align='center'><b>$orca_data</b></TD>\n";
				echo "<TD  class='justificativa' width='450px' align='left'>$orca_comentario</TD>\n";
				echo "</TR>\n";
			}
			echo "</TABLE>\n";
		}
	}
}
?>

<?
/* Fabio - 09/11/2007 - HD Chamado 7452 */
$sql="SELECT orcamento,
			total_mao_de_obra,
			total_pecas,
			aprovado,
			TO_CHAR(data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
			TO_CHAR(data_reprovacao,'DD/MM/YYYY') AS data_reprovacao,
			motivo_reprovacao
		FROM tbl_orcamento
		WHERE empresa = $login_fabrica
		AND   os      = $os";
$resOrca = pg_query ($con,$sql);
if (pg_num_rows($resOrca)>0){
	$orcamento         = pg_fetch_result($resOrca,0,orcamento);
	$total_mao_de_obra = pg_fetch_result($resOrca,0,total_mao_de_obra);
	$total_pecas       = pg_fetch_result($resOrca,0,total_pecas);
	$aprovado          = pg_fetch_result($resOrca,0,aprovado);
	$data_aprovacao    = pg_fetch_result($resOrca,0,data_aprovacao);
	$data_reprovacao   = pg_fetch_result($resOrca,0,data_reprovacao);
	$motivo_reprovacao = pg_fetch_result($resOrca,0,motivo_reprovacao);

	$total_mao_de_obra = number_format($total_mao_de_obra,2,",",".");
	$total_pecas       = number_format($total_pecas,2,",",".");

	if ($aprovado=='t'){
		$msg_orcamento = traduz("orcamento.aprovado",$con,$cook_idioma).". ( ".traduz("data",$con,$cook_idioma).": $data_aprovacao )";
	}elseif ($aprovado=='f'){
		$msg_orcamento = traduz("orcamento",$con,$cook_idioma)." <b style='color:red'>".strtoupper(traduz("reprovado",$con,$cook_idioma))."</b>. ".traduz("motivo",$con,$cook_idioma).": $motivo_reprovacao ( ".traduz("data",$con,$cook_idioma).": $data_reprovacao )";
	} else {
		$msg_orcamento = traduz("orcamento.aguardando.aprovacao",$con,$cook_idioma).".";
	}
	echo "<BR>\n";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
	echo "<TR>\n";
	echo "<TD colspan='2' class='inicio'>".traduz("orcamento",$con,$cook_idioma)."</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD  class='titulo' align='left'><b>".traduz("valor.mao.de.obra",$con,$cook_idioma)."</b></TD>\n";
	echo "<TD  class='justificativa' width='450px' align='left' style='padding-left:10px'>$total_mao_de_obra</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD  class='titulo' align='left'><b>".traduz("valor.pecas",$con,$cook_idioma)."</b></TD>\n";
	echo "<TD  class='justificativa' align='left' style='padding-left:10px'>$total_pecas</TD>\n";
	echo "</TR>\n";
	echo "<TR>\n";
	echo "<TD  class='titulo' align='left'><b>".traduz("aprovacao",$con,$cook_idioma)."</b></TD>\n";
	echo "<TD  class='justificativa' align='left' style='padding-left:10px'><div id='msg_orcamento'>$msg_orcamento</div></TD>\n";
	echo "</TR>\n";
	if (strlen($aprovado)==0) {
	echo "<TR>\n";
	echo "<TD  colspan='2' class='justificativa' align='center' style='padding-left:10px'><div id='aprova_reprova'><input type='button' name='aprovar_orcamento' value='Aprovar' onclick='aprovaOrcamento($orcamento)'> <input type='button' name='reprova_orcamento' value='Reprovar' onclick='reprovaOrcamento($orcamento)'></div></TD>\n";
	echo "</TR>\n";
	}
	echo "</TABLE>\n";
}
?>


<?
//incluido por Welligton 29/09/2006 - Fabricio chamado 472

if (strlen($orientacao_sac) > 0){
	echo "<BR>";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
	echo "<TR>";
	echo "<TD colspan=7 class='inicio'>&nbsp;".traduz("orientacoes.do.sac.ao.posto.autorizado",$con,$cook_idioma)."</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='conteudo_sac'>Obs: ".nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$orientacao_sac)))))))."</TD>";
	echo "</TR>";
	echo "</TABLE>";
}
?>
<?
//incluido por takashi 19/10/2007 - hd4536
//qdo OS é fechada com peças ainda pedente o posto tem que informar o motivo, o motivo a gente mostra aqui
if ($login_fabrica == 3){
	$sql = "SELECT obs_fechamento from tbl_os_extra where os=$os";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		$motivo_fechamento = pg_fetch_result($res,0,0);
		if(strlen($motivo_fechamento)>0){
			echo "<BR>";
			echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
			echo "<TR>";
			echo "<TD colspan=7 class='inicio'>&nbsp;".traduz("justificativa.fechamento.de.os.com.peca.ainda.pendente",$con,$cook_idioma)."</TD>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD class='conteudo'>$motivo_fechamento</TD>";
			echo "</TR>";
			echo "</TABLE>";
		}
	}
}
?>




<?
//Colocado por Fabio - HD 14344
//mostra o status da OS: acumulada ou resucasa
if ($login_fabrica == 25){
	$sql = "SELECT	TO_CHAR(data,'DD/MM/YYYY') AS data,
					tbl_os_status.status_os    AS status_os,
					tbl_os_status.observacao   AS observacao
			FROM tbl_os_extra
			JOIN tbl_os_status USING(os)
			WHERE os = $os
			AND tbl_os_status.status_os IN (13,14)
			AND tbl_os_extra.extrato IS NULL
			ORDER BY tbl_os_status.data DESC
			LIMIT 1";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		echo "<BR>";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
		echo "<TR>";
		echo "<TD colspan=7 class='inicio'>".traduz("extrato",$con,$cook_idioma)." - ".traduz("status.da.os",$con,$cook_idioma)."</TD>";
		echo "</TR>";
		for ($i=0; $i<pg_num_rows($res); $i++){
			$status_data       = pg_fetch_result($res,0,data);
			$status_status_os  = pg_fetch_result($res,0,status_os);
			$status_observacao = pg_fetch_result($res,0,observacao);

			if ($status_status_os==13){
				$status_status_os = "Recusada";
			}elseif ($status_status_os==14){
				$status_status_os = "Acumulada";
			} else {
				$status_status_os = "-";
			}

			echo "<TR>";
			echo "<TD class='conteudo'>$status_data</TD>";
			echo "<TD class='conteudo'>$status_status_os</TD>";
			echo "<TD class='conteudo' colspan=5>$status_observacao</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}
}
# 53003 - mostrar todas as ocorrências e o admin
if ($login_fabrica == 45){
	$sql = "SELECT	TO_CHAR(data,'DD/MM/YYYY') AS data,
					tbl_os_status.status_os    AS status_os,
					tbl_os_status.observacao   AS observacao,
					tbl_os_status.extrato,
					tbl_admin.nome_completo
			FROM tbl_os_extra
			JOIN tbl_os_status USING(os)
			LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
			WHERE os = $os
			AND tbl_os_status.status_os IN (13,14)
			AND tbl_os_extra.extrato IS NULL
			ORDER BY tbl_os_status.data DESC";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		echo "<BR>";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
		echo "<TR>";
		echo "<TD colspan=7 class='inicio'>EXTRATO - STATUS DA OS</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titulo2' align='center'>DATA</TD>";
		echo "<TD class='titulo2' align='center'>ADMIN</TD>";
		echo "<TD class='titulo2' align='center'>EXTRATO</TD>";
		echo "<TD class='titulo2' align='center'>STATUS</TD>";
		echo "<TD class='titulo2' align='center'>OBSERVAÇÃO</TD>";
		echo "</TR>";

		for ($i=0; $i<pg_num_rows($res); $i++){
			$status_data       = pg_fetch_result($res,$i,data);
			$status_status_os  = pg_fetch_result($res,$i,status_os);
			$status_observacao = pg_fetch_result($res,$i,observacao);
			$zextrato          = pg_fetch_result($res,$i,extrato);
			$admin_nome        = pg_fetch_result($res,$i,nome_completo);

			if ($status_status_os==13){
				$status_status_os = "Recusada";
			}elseif ($status_status_os==14){
				$status_status_os = "Acumulada";
			} else {
				$status_status_os = "-";
			}

			echo "<TR>";
			echo "<TD class='conteudo' style='text-align: center'>$status_data</TD>";
			echo "<TD class='conteudo' style='text-align: center'>$admin_nome</TD>";
			echo "<TD class='conteudo' style='text-align: center'>$zextrato</TD>";
			echo "<TD class='conteudo' style='padding-left: 5px'>$status_status_os</TD>";
			echo "<TD class='conteudo' style='padding-left: 5px'>$status_observacao</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}
}

if (strlen($obs) > 0) {
	echo "<br />";
	echo"<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
		echo "<tr>";
			echo "<TD class='conteudo'><b>".traduz("obs",$con,$cook_idioma).":</b>&nbsp;$obs</TD>";
		echo "</tr>";
	echo "</table>";
}?>

<?php
	if($login_fabrica == 42) { // HD 341053

		$sql = "select extrato from tbl_os_extra where os = $os AND extrato is not null;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0)
			$totaliza_makita = 's';

	}
?>

<!--            Valores da OS           --><?php
if ($login_fabrica == "20" or $login_fabrica == "30" or $login_fabrica=="50" or ($login_fabrica == 42 && $totaliza_makita == 's' ) || $login_fabrica == 15 ) {

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$sql = "SELECT mao_de_obra
			FROM tbl_produto_defeito_constatado
			WHERE produto = (
				SELECT produto
				FROM tbl_os
				WHERE os = $os
			)
			AND defeito_constatado = (
				SELECT defeito_constatado
				FROM tbl_os
				WHERE os = $os
			)";

	/* HD 19054 */
	if ($login_fabrica==50){
		$sql = "SELECT mao_de_obra
				FROM tbl_os
				WHERE os = $os
				AND fabrica = $login_fabrica";
	}

	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) == 1) {
		$mao_de_obra = pg_fetch_result ($res,0,mao_de_obra);
	}

	$sql = "SELECT  tabela,
					desconto,
					desconto_acessorio
			FROM  tbl_posto_fabrica
			WHERE posto = $login_posto
			AND   fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$tabela             = pg_fetch_result ($res,0,tabela)            ;
		$desconto           = pg_fetch_result ($res,0,desconto)          ;
		$desconto_acessorio = pg_fetch_result ($res,0,desconto_acessorio);
	}

	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$pecas = pg_fetch_result ($res,0,0);
		}
	} else {
		$pecas = "0";
	}

	echo "<br><table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";

	if ($login_fabrica==50 or $login_fabrica==30 || $login_fabrica == 15){
		/* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
			caso este for igual a 0*/
		$sql = "SELECT tbl_os.qtde_km_calculada
				FROM tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				WHERE tbl_os.os = $os
					AND tbl_os.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$qte_km_vd = pg_fetch_result ($res,0,qtde_km_calculada);
		if ($qte_km_vd<>0){
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("valor.deslocamento",$con,$cook_idioma);
			echo "</b></td>";
		}
		if($login_fabrica == 30){
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("valor.das.pecas",$con,$cook_idioma);
			echo "</b></td>";
		}

		echo "<td align='center' colspan='2' bgcolor='#E1EAF1'><b>".traduz("mao.de.obra",$con,$cook_idioma)."</b></td>";
		echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";

	} else {
		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		fecho ("valor.das.pecas",$con,$cook_idioma);
		echo "</b></td>";
		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		fecho ("mao.de.obra",$con,$cook_idioma);
		echo "</b></td>";
		if($sistema_lingua=='ES'){
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("desconto.iva",$con,$cook_idioma);
			echo "</b></td>";
		}
		echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";
	}
	echo "</tr>";

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {
		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) == 1) {
			$produto = pg_fetch_result ($res,0,0);
		}
		//echo 'peca'.$pecas;
		if( $produto == '20567' ){
			$desconto_acessorio = '0.2238';
			$valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

		} else {
			$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
		}

		$valor_liquido = $pecas - $valor_desconto ;

	}

	if($login_fabrica==20 ){
		$sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) >0) {
			$sigla_pais = pg_fetch_result ($res,0,pais);
		}
	}

	$acrescimo = 0;

	if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$valor_liquido = pg_fetch_result ($res,0,pecas);
			$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
		}
		$sql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$imposto_al   = pg_fetch_result ($res,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
	}

	//Foi comentado HD chamado 17175 4/4/2008

	//HD 9469 - Alteração no cálculo da BOSCH do Brasil
	if($login_pais=="BR") {
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) == 1) {
			$valor_liquido = pg_fetch_result ($res,0,pecas);
			//$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
		}
	}

	if($login_fabrica == 30 || $login_fabrica == 42){ // makita hd 341053
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$valor_liquido = pg_fetch_result ($res,0,pecas);
			$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
		}
	}

	/* HD 19054 */
	$valor_km = 0;
	if($login_fabrica == 50 or $login_fabrica == 30 or $login_fabrica == 15){
		$sql = "SELECT	tbl_os.mao_de_obra,
						tbl_os.qtde_km_calculada,
						tbl_os_extra.extrato
				FROM tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				WHERE tbl_os.os = $os
				AND   tbl_os.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
			$valor_km      = pg_fetch_result ($res,0,qtde_km_calculada);
			$extrato       = pg_fetch_result ($res,0,extrato);
		}
	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo + $valor_km;

	$total          = number_format ($total,2,",",".")         ;
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format ($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");
	$valor_km       = number_format ($valor_km ,2,",",".");

	echo "<tr style='font-size: 12px ; background:white;'>";
	/* HD 19054 */
	if ($login_fabrica==50 or $login_fabrica==30 or $login_fabrica == 15){
		/* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
			caso este for igual a 0*/
		if ($valor_km<>0){
			echo "<td align='right'><font color='#333377'><b>$valor_km</b></td>";
		}
		if($login_fabrica == 30){
			echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
		}
		echo "<td align='center' colspan='2'>$mao_de_obra</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></font></td>";

	} else {
		echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
		echo "<td align='center'>$mao_de_obra</td>";
		if($sistema_lingua=='ES')echo "<td align='center'>+ $acrescimo</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	}
	echo "</tr>";

	/* HD 19054 */
	if ($login_fabrica==50 and strlen($extrato)==0){
		echo "<tr style='font-size: 12px ; color:#000000 '>";
		echo "<td colspan='3'>";
		echo "<font color='#757575'>".traduz("valores.sujeito.a.alteracao.ate.fechamento.do.extrato",$con,$cook_idioma) ;
		echo "</td>";
		echo "</tr>";
	}
    echo "</table>";

}
?>

<?
	if ($login_fabrica==2 and strlen($data_finalizada)==0 and $login_posto==6359){
		$status_os = "";
		$sql_status = "SELECT status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (72,73,62,64,65,87,88)
						ORDER BY data DESC
						LIMIT 1";
		$res_status = pg_query($con,$sql_status);
		if (pg_num_rows($res_status) >0) {
			$status_os = pg_fetch_result ($res_status,0,status_os);
		}
		if ($status_os != "65"){
			echo "<br>";
			echo "<a href='".$PHP_SELF."?os=$os&inter=1'>".strtoupper(traduz("enviar.produto.para.centro.de.reparo",$con,$cook_idioma))."</a>";
			echo "<br>";
		}
	}
?>


<?	if(strlen(trim($_GET['lu_fabrica'])) == 0) {

	if((($login_fabrica == 11 OR $login_fabrica == 10) AND $login_posto == 6359) OR in_array($login_fabrica,array(3,45,40,51)) OR $login_fabrica >=80 ) {?>
	<br>
	<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
	<TR>
		<TD><font size='2' color='#FFFFFF'><center><b><? if ($login_fabrica == 3) echo strtoupper(traduz("enviar.duvida.ao.suporte.tecnico",$con,$cook_idioma)); else echo strtoupper(traduz("interagir.na.os",$con,$cook_idioma)); ?></b></center></font></TD>
	</TR>
	<TR><TD class='conteudo'>

	<FORM NAME='frm_interacao' METHOD=POST ACTION="<? echo "$PHP_SELF?os=$os"; ?>">
	<TABLE width='600' align='center'>
	<TR>
		<TD>
		<TABLE align='center' border='0' cellspacing='0' cellpadding='5'>
		<? if($login_fabrica ==3 ){ // HD 17334 ?>
		<tr>
			<TD align='center'><?fecho ("duvidas",$con,$cook_idioma);?>:</td>
		</tr>
		<? } ?>

		<TR>
			<TD align='center'>
			<INPUT TYPE="text" NAME="interacao_msg" size='60' >&nbsp;
			<? if($login_fabrica <>3 ){ // HD 17334 ?>
			<INPUT TYPE="checkbox" NAME="interacao_exigir_resposta" value='t'>&nbsp;<font size='1'><?fecho ("enviar.p.o.fabricante",$con,$cook_idioma);?>.</font>
			<? } ?>
			</TD>
		</TR>
		<? if ($login_fabrica ==3) { // HD 17334 ?>
		<TR>
			<TD align='center' nowrap><?fecho ("pontos.verificados.pelo.tecnico",$con,$cook_idioma);?>:</td>
		</TR>
		<TR>
			<TD align='center'><INPUT TYPE="text" NAME="interacao_msg2" size='60'></TD>
		</TR>
		<? } ?>

		<TR align='center'>
			<TD><input type="hidden" name="interacao_os" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_interacao.interacao_os.value == '' ) { document.frm_interacao.interacao_os.value='gravar' ; document.frm_interacao.submit() } else { alert ('<?fecho("aguarde.submissao",$con,$cook_idioma);?>') }"
				<? if($login_fabrica == 3) { echo "alt='".traduz("enviar.duvida",$con,$cook_idioma)."'";} else { echo " 'ALT='".traduz("gravar.comentario",$con,$cook_idioma)."'";} ?> border='0' style="cursor:pointer;">
			</TD>
		</TR>
		</TABLE>
		</TD>
	</TR>
	</TABLE>
	</FORM>

	<?
	$sql = "SELECT os_interacao,
					to_char(data,'DD/MM/YYYY HH24:MI') as data,
					comentario,
					tbl_admin.nome_completo
				FROM tbl_os_interacao
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
				WHERE os = $os
				AND interno IS FALSE
				ORDER BY os_interacao DESC;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){

		for($i=0;$i<pg_num_rows($res);$i++){
			$os_interacao     = pg_fetch_result($res,$i,os_interacao);
			$interacao_msg    = pg_fetch_result($res,$i,comentario);
			$interacao_data   = pg_fetch_result($res,$i,data);
			$interacao_nome   = pg_fetch_result($res,$i,nome_completo);
			if($i==0){
				echo "<br>";
				echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#F3F5CF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp;<b>".traduz("interacao.da.fabrica",$con,$cook_idioma)."</b></b></font></td>";
				echo "</tr>";
				echo "</table>";

				echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
				echo "<tr>";
				echo "<td class='titulo'><CENTER>".traduz("n",$con,$cook_idioma)."</CENTER></td>";
				echo "<td class='titulo'><CENTER>".traduz("data",$con,$cook_idioma)."</CENTER></td>";
				echo "<td class='titulo'><CENTER>".traduz("mensagem",$con,$cook_idioma)."</CENTER></td>";
				echo "<td class='titulo'><CENTER>".traduz("fabrica",$con,$cook_idioma)."</CENTER></td>";
				echo "</tr>";
			}
			if(strlen($interacao_nome) > 0){
				$cor = "style='font-family: Arial; FONT-SIZE: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
			} else {
				$cor = "class='conteudo'";
			}
			echo "<tr>";
			echo "<td width='25' $cor>"; echo pg_num_rows($res) - $i; echo "</td>";
			echo "<td width='90' $cor nowrap>$interacao_data</td>";
			echo "<td $cor>$interacao_msg</td>";
			echo "<td $cor nowrap>$interacao_nome</td>";
			echo "</tr>";
		}
		echo "</TABLE><br>";
	}
	echo "</TD></TR></TABLE>";
}
}

		if ($anexaNotaFiscal) {
			$temImg = temNF($os, 'bool');

			$tabelaAnexos = ($login_fabrica == 96) ? temNF($os, 'linkEx') : temNF($os, 'link');

			if($temImg) {
				echo $tabelaAnexos . $include_imgZoom;
			}
		}

// hd 21896 - Francisco Ambrozio - inclusão do laudo técnico
if ($login_fabrica == 1 or $login_fabrica == 19){
	$sql = "SELECT tbl_laudo_tecnico_os.*
			FROM tbl_laudo_tecnico_os
			WHERE os = $os
			ORDER BY ordem;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
?>
		<BR>
		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
		<TR>
		<TD colspan="9" class='inicio'>&nbsp;<?echo traduz("laudo.tecnico",$con,$cook_idioma);?></TD>
<?
		echo "<tr>";
		if ($login_fabrica==19) {
			echo "<td class='titulo' style='width: 30%'><CENTER>".traduz("questao",$con,$cook_idioma)."</CENTER></td>";
		} else {
			echo "<td class='titulo' style='width: 30%'><CENTER>".traduz("titulo",$con,$cook_idioma)."</CENTER></td>";
		}
		echo "</CENTER></td>";
		echo "<td class='titulo' style='width: 10%'><CENTER>".traduz("afirmativa",$con,$cook_idioma)."</CENTER></td>";
		echo "<td class='titulo' style='width: 60%'><CENTER>".traduz("observacao",$con,$cook_idioma)."</CENTER></td>";
		echo "</tr>";

		for($i=0;$i<pg_num_rows($res);$i++){
			$laudo		 = pg_fetch_result($res,$i,laudo_tecnico_os);
			$titulo      = pg_fetch_result($res,$i,titulo);
			$afirmativa  = pg_fetch_result($res,$i,afirmativa);
			$observacao  = pg_fetch_result($res,$i,observacao);

			echo "<tr>";
			echo "<td class='conteudo' align='left' style='width: 30%'>&nbsp;$titulo</td>";
			if(strlen($afirmativa) > 0){
				echo "<td class='conteudo' style='width: 10%'><CENTER>"; if($afirmativa == 't'){ echo traduz("sim",$con,$cook_idioma)."</CENTER></td>";} else { echo traduz("nao",$con,$cook_idioma)."</CENTER></td>";}
			} else {
				echo "<td class='conteudo' style='width: 10%'>&nbsp;</td>";
			}
			if(strlen($observacao) > 0){
				echo "<td class='conteudo' style='width: 60%'><CENTER>$observacao</CENTER></td>";
			} else {
				echo "<td class='conteudo' style='width: 60%'>&nbsp;</td>";
			}
			echo "</tr>";
		} ?>
</TR>
</TABLE> <?
	}
} 



if($login_fabrica == 1) {
	$sql = " SELECT 
				laudo_tecnico
			FROM tbl_os
			WHERE os = $os
			AND fabrica= $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<br/>";
		echo "<center>";
		echo "<table width='500' align='center' class='Tabela'>";
		echo "<tr class='inicio'>";
		echo "<td  align='center'>Laudo Técnico</td>";
		echo "</tr>";

		$laudo = pg_fetch_result($res,0,laudo_tecnico);

		echo "<tr>";
		echo "<td class='conteudo' align='left' >&nbsp;$laudo</td>";
		echo "</tr>";
		echo "</table></center>";
	}
}

?>

<!-- Finaliza inclusão do laudo técnico -->

<?
if(strlen(trim($_GET['lu_fabrica'])) == 0) { # HD 153966
	if (($login_fabrica==51 and $status_os==62) or ($login_fabrica==81 and $status_os==62) ){ ?>
	<form name='frm_intervencao' method=post action="<? echo "$PHP_SELF?os=$os"; ?>">
		<br>
		<table width='700px' border='0' cellspacing='1' cellpadding='2' align='center' class='tabela'>
			<tr>
				<td class='inicio'> Retirar a intervenção da fábrica</td>
			</tr>
			<tr>
				<td class='conteudo'><center>
					<input type="hidden" name="btn_acao" value="">
					<input type="button" name="btn_ajuste1" style='cursor:pointer;background:#C0C0C0;' value="Ajuste elétrico"
					onclick="
						if(document.frm_intervencao.btn_acao.value == ''){
							document.frm_intervencao.btn_acao.value='670';
							document.frm_intervencao.submit()
						} else {
							alert('Aguarde submissao.')
						}">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" name="btn_ajuste2" style='cursor:pointer;background:#C0C0C0;' value="Ajuste mecânico"
					onclick="
						if(document.frm_intervencao.btn_acao.value == ''){
							document.frm_intervencao.btn_acao.value='671';
							document.frm_intervencao.submit()
						} else {
							alert('Aguarde submissao.')
						}">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" name="btn_sem_peca" style='cursor:pointer;background:#C0C0C0;' value="Conserto sem peça"
					onclick="
						if(document.frm_intervencao.btn_acao.value == ''){
							document.frm_intervencao.btn_acao.value='733';
							document.frm_intervencao.submit()
						} else {
							alert('Aguarde submissao.')
						}">
					<br>ou entre em contato com a GAMA ITALY pelo telefone (11) 2940-7400<br>
				</td>
			</tr>
		</table>
	</form>
<?}?>

<BR><BR>
<!-- =========== FINALIZA TELA NOVA============== -->

<?
	$origem = $_GET['origem'];
?>

<table cellpadding='10' cellspacing='0' border='0' align='center'>
<tr>
<? if($sistema_lingua == "ES"){ ?>
	<td><a href="os_cadastro.php"><img src="imagens/btn_lanzarnovaos.gif"></a></td>
<? }elseif ($origem == 'troca'){ ?>
	<td><a href="os_cadastro_troca.php"><img src="imagens/btn_lancanovaos.gif"></a></td>
<? } else {?>
	<td><a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a></td>
<? } if($login_fabrica == 20){
		echo "<TD><a href='os_comprovante_servico_print.php?os=$os'><img src='imagens/";
		if($sistema_lingua=="ES")echo "es_";
		echo "btn_comprovante.gif'></a></TD>";

}?>
	<td><a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a></td>
	<td>
	<?
	if($login_fabrica == 20) {
		if(strlen($data_fechamento) == 0) { // HD 61323
			echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,fechar) ; }\"><img id='fechar' src='/assist/imagens/btn_fecha.gif' height='18'></a>";
		}
	}
	?>
	</td>
</tr>

<?php

    if ($login_fabrica == 15) {

        $sql = "SELECT os
                FROM tbl_os
                WHERE os = $os
                AND tipo_atendimento IN (20,21)";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res)) {

?>

            <table style="font-size:11px;" width="700px">
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_verde.gif" />&nbsp; Aprovadas Automaticamente e da Auditoria de KM</td>
                </tr>
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_azul.gif" />&nbsp;Aprovadas com Alteração da Auditoria de KM</td>
                </tr>
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_amarelo.gif" />&nbsp;Em análise de KM</td>
                </tr>
                <tr>
                    <td align="left"><img src="admin/imagens_admin/status_vermelho.gif" />&nbsp;Reprovadas da Auditoria de KM</td>
                </tr>
            </table>

<?php

            $sql = "SELECT
                        status_os,
                        observacao,
                        tbl_status_os.descricao
                    FROM tbl_os_status
                    JOIN tbl_status_os using(status_os)
                    WHERE os=$os
                    ORDER BY data DESC";
            $res = pg_query($con,$sql);

            echo '<table class="tabela" width="700px" cellspacing="1" style="">
                    <tr>
                       <td colspan="2" class="titulo_tabela">STATUS DA OS</td>
                   </tr>
                   <tr class="titulo_coluna">
                       <td>Status</td>
                       <td>OBS</td>
                   </tr>';

           for ($i=0; $i< pg_num_rows($res); $i++) {

               $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

               $desc_status = pg_result($res,$i,'descricao');
               $obs         = pg_result($res,$i,'observacao');
               $status      = pg_result($res,$i,'status_os');

               $img         = get_status_img($status);

               $img_src     = !empty($img) ? '<img src="admin/imagens_admin/'.$img.'" />' : '';

               echo '  <tr bgcolor="'.$cor.'">
                           <td align="left"> '.$img_src.' &nbsp;'.$desc_status.'</td>
                           <td align="left">'.$obs.'</td>
                       </tr>';

           }

           if (pg_num_rows($res) == 0) {

               echo '  <tr bgcolor="'.$cor.'">
                           <td align="left" colspan="2">
                               &nbsp;<img src="admin/imagens_admin/status_verde.gif" /> &nbsp;OS Aprovada Automaticamente
                           </td>
                       </tr>';

           }

           echo '</table>';
		}
	}
?>

<? if($login_fabrica == 20) {?>
<tr>
	<td colspan='100%' align='left'>
	<? fecho('data.de.conserto',$con,$cook_idioma);?> <input type='text' name='data_conserto' id='data_conserto' alt='<? echo $os;?>' value='<? echo $data_conserto;?>' <?if(strlen($data_conserto) > 0) echo " disabled ";?>>
	</td>
	<td id='mensagem'></td>
</tr>
<?}?>
</table>

<?
}
//HD 150981 - Mostra a imagem da nota fiscal se disponíl
/*
 echo "CAMINHO DA IMAGEM =".$imagem_nota = "nf_digitalizada/" . $os . ".jpg";

 if (file_exists($imagem_nota))
 {
         echo "
         <div align=center>
         <br>
         <font style='font-size:12pt'>Nota fiscal do produto:</font><br>
 	<img src='$imagem_nota'>
 	</div>
	";
 }
*/

include ($_GET['lu_os'] == 'sim') ? "login_unico_rodape.php" :"rodape.php"; ?>
