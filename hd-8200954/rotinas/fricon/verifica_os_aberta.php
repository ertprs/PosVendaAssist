<?php

define('ENV', 'producao');

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

	$msg_erro      = array();
	$fabrica       = 52;

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	$vet['fabrica'] = 'fricon';
	$vet['tipo']    = 'verifica_os_aberta';
	if (ENV == 'testes'){
		$vet['dest']    = '@telecontrol.com.br'; //INSIRA O SEU EMAIL PARA TESTES -  TEM QUE ALTERAR LA EM CIMA A CONSTANTE 'ENV' para 'testes'
	}else{
		$vet['dest']    = 'helpdesk@telecontrol.com.br';
	}

	$vet['log']     = 2;
/*
*
* Rotina verifica tempo das OS's abertas de todos os postos
* Dispara Email de acordo com os assuntos abaixo:
*
*1 dia  - envia email para - coordenacao.sac@fricon.com.br
*2 dias - envia email para - coordenacao.sac@fricon.com.br ; sup.dspv@fricon.com.br
*3 dias - envia email para - coordenacao.sac@fricon.com.br ; sup.dspv@fricon.com.br ; posvenda@fricon.com.br
*
*/
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';


/*FUNCTIONS*/
function dateDiff($start, $end) {

	$start_ts = strtotime($start);

	$end_ts = strtotime($end);

	$diff = $end_ts - $start_ts;

	return round($diff / 86400);

}

function enviaEmail($message,$subject,$tipo){

	$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor
	
	$tipo = 0;

	switch ($tipo) {
		case '24':
			$destinatarios = 'ger.posvenda1@fricon.com.br coordenacao.sac@fricon.com.br dspv4@mercofricon.com.br dspv12@mercofricon.com.br dspv14@mercofricon.com.br dspv15@mercofricon.com.br';
			break;

		case '48':
			$destinatarios = 'ger.posvenda1@fricon.com.br coordenacao.sac@fricon.com.br dspv4@mercofricon.com.br dspv12@mercofricon.com.br dspv14@mercofricon.com.br dspv15@mercofricon.com.br';
			break;

		case '72':
			$destinatarios = 'ger.posvenda1@fricon.com.br coordenacao.sac@fricon.com.br dspv4@mercofricon.com.br dspv12@mercofricon.com.br dspv14@mercofricon.com.br dspv15@mercofricon.com.br';
			break;

		default:
			$destinatarios = 'ana.clara@fricon.com.br maria.fernanda@fricon.com.br diogenes.marinho@mercofricon.com.br tiago.ribeiro@mercofricon.com.br inaldo.felix@fricon.com.br marcos.vinicius@fricon.com.br pedro.jorge@mercofricon.com.br';
			break;
	}

	$file = fopen('/tmp/fricon/tempo_os_aberta.xls','w');
	fwrite($file,$message);
	fclose($file);


	$mensagem  = "Segue em anexo, OSs Abertas";
	foreach ($destinatarios as $destinatario) {
		$mailer->AddAddress($destinatario);
	}

	$mailer->Subject = $subject;
	$mailer->Body = $mensagem;
	$mailer->AddAttachment('/tmp/fricon/tempo_os_aberta.xls');
	$mailer->IsHTML(true);

	system("echo $mensagem | mail -s '$subject' -A /tmp/fricon/tempo_os_aberta.xls $destinatarios"); 
	return true;

}

/*
Verifica as OS's que estão abertas
*/

$sql = "SELECT tbl_os.sua_os as os,to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura, data_abertura as dt_ab,tbl_posto.nome,tbl_posto_fabrica.contato_cidade,tbl_posto_fabrica.contato_estado,(current_date-data_abertura) as dias from tbl_os join tbl_posto using(posto) join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $fabrica where tbl_os.fabrica = $fabrica and finalizada is null and excluida is not true order by tbl_posto_fabrica.contato_estado,tbl_posto_fabrica.contato_cidade,nome,dias";
$res = pg_query($con,$sql);
echo pg_last_error($res);
	// var_dump(pg_fetch_all($res));

if (pg_num_rows($res)>0){

	//Arrays das OS's
	$os_24_horas = array();
	$os_48_horas = array();
	$os_72_horas = array();

	$current_date = date('Y-m-d');
	$posto = '';

	foreach (pg_fetch_all($res) as $values) {

		/*Pega a diferença de dias da data de abertura para a data atual*/
		$diff = dateDiff($values['dt_ab'],$current_date);


		if ($diff == 1){
			$os_24_horas[] = $values;
		}

		if ($diff == 2){
			$os_48_horas[] = $values;
		}

		if ($diff >= 3){
			$os_72_horas[] = $values;
		}


	}

	//ENVIA EMAIL PARA AS OS 24 HRAS
	if (count($os_24_horas)>0) {
		$table = "<table border=1><tr><td>OS</td><td>Data Abertura</td><td>Posto</td><td>Cidade</td><td>Estado</td><td>Dias Aberto</td></tr>";

		foreach($os_24_horas as $key => $value){
			$sua_os        = $value['os'];
			$data_abertura = $value['data_abertura'];
			$posto_nome    = $value['nome'];
			$cidade        = $value['contato_cidade'];
			$estado        = $value['contato_estado'];
			$dias          = $value['dias'];

			$table .= "<tr>";
			$table .= "<td>$sua_os</td>";
			$table .= "<td>$data_abertura</td>";
			$table .= "<td>$posto_nome</td>";
			$table .= "<td>$cidade</td>";
			$table .= "<td>$estado</td>";
			$table .= "<td>$dias</td>";
			$table .= "</tr>";

		}

		$table .="</table>";
		$message =$table;

		$subject = "FRICON - ALERTA de O.S. ABERTA A MAIS DE 24 HORAS";

		$tipo = '24';

		if (!enviaEmail($message,$subject,$tipo)){
			$msg_erro[] = "Email 24h não enviado";
		}
	}

	//ENVIA EMAIL PARA AS OS 48 HRAS
	if (count($os_48_horas)>0) {

		$table = "<table border=1><tr><td>OS</td><td>Data Abertura</td><td>Posto</td><td>Cidade</td><td>Estado</td><td>Dias Aberto</td></tr>";

		foreach($os_48_horas as $key => $value){
			$sua_os        = $value['os'];
			$data_abertura = $value['data_abertura'];
			$posto_nome    = $value['nome'];
			$cidade        = $value['contato_cidade'];
			$estado        = $value['contato_estado'];
			$dias          = $value['dias'];

			$table .= "<tr>";
			$table .= "<td>$sua_os</td>";
			$table .= "<td>$data_abertura</td>";
			$table .= "<td>$posto_nome</td>";
			$table .= "<td>$cidade</td>";
			$table .= "<td>$estado</td>";
			$table .= "<td>$dias</td>";
			$table .= "</tr>";

		}

		$table .="</table>";
		$message =$table;

		$subject = "FRICON - ALERTA de O.S. ABERTA A MAIS DE 48 HORAS";

		$tipo = '48';

		if (!enviaEmail($message,$subject,$tipo)){
			$msg_erro[] = "Email 48h não enviado";
		}

	}

	//ENVIA EMAIL PARA AS OS 72 HRAS
	if (count($os_72_horas)>0) {

		$table = "<table border=1><tr><td>OS</td><td>Data Abertura</td><td>Posto</td><td>Cidade</td><td>Estado</td><td>Dias Aberto</td></tr>";

		foreach($os_72_horas as $key => $value){
			$sua_os        = $value['os'];
			$data_abertura = $value['data_abertura'];
			$posto_nome    = $value['nome'];
			$cidade        = $value['contato_cidade'];
			$estado        = $value['contato_estado'];
			$dias          = $value['dias'];

			$table .= "<tr>";
			$table .= "<td>$sua_os</td>";
			$table .= "<td>$data_abertura</td>";
			$table .= "<td>$posto_nome</td>";
			$table .= "<td>$cidade</td>";
			$table .= "<td>$estado</td>";
			$table .= "<td>$dias</td>";
			$table .= "</tr>";

		}

		$table .="</table>";
		$message =$table;
		$subject = "FRICON - ALERTA de O.S. ABERTA A MAIS DE 72 HORAS";

		$tipo = '72';

		if (!enviaEmail($message,$subject,$tipo)){
			$msg_erro[] = "Email 72h não enviado";
		}

	}


	if (count($msg_erro) > 0) {

		$msg_erro = implode('<br>', $msg_erro);

		Log::envia_email($vet, 'Log - ERRO Verifica OS Aberta - FRICON', $msg_erro);

	}

	$phpCron->termino();

}
