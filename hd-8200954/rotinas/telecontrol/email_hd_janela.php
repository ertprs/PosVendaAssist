<?php
/***
 * @name:		emal_hd_janela.php
 * @author:		Manuel López para Telecontrol Networking Ltda.
 * @param	-d / --debug	(optional, optional value)	Seta o modo debug, optionalmente com valor 2 para debug de envio de e-mail
 * 														email_hd_janela -d       -> ativa o modo debug, não manda os e-mails
 * 														email_hd_janela -debug=2 -> ativa o modo debug, e envia os e-mails
 * @param	-t / --teste	(optional, optional value)  Seta o modo de teste de e-mail.
 * 														Valores opcionais: e-mail para envio. Ex.:
 * 														email_hd_janela --teste=manuel.lopez,suporte  envia apenas para estes endereços
 * @param	-f / --from		(optional, required value)  Estabelece um remetente diferente (o padrão é 'sistema@telecontrol.com.br')
 * @param	--titulo		(optional, required value)  Estabelece um título diferente para o e-mail enviado
 * @param	--logto			(optional, required value)  Estabelece um e-mail diferente para o envio de log de erro. Ex.:
 * 														email_hd_janela --logto=boaz envia o log de erro para boaz@telecontrol.com.br
 * 														email_hd_janela --logto=teste@gmail.com envia para este endereço
 *
 * Se o programa é executado pelo root (cron, p.e.), não há modo debug, mas ainda pode ter modo teste.
 * Este programa está pensado para rodar em terminal e não em tela.
 ***/

define('isCLI', (PHP_SAPI == 'cli'));
define('isRoot', (posix_geteuid() == 0));

define('remetentePadrao', 'sistema@telecontrol.com.br');

if (!isRoot and isCLI)
	error_reporting(E_ERROR);

include      __DIR__ . '/../../dbconfig.php';
include      __DIR__ . '/../../includes/dbconnect-inc.php';
require_once __DIR__ . '/../funcoes.php';

require_once __DIR__ . '/../../helpdesk/mlg_funciones.php';

$titulo_email        = 'Informa Janelas HD - ERRO';
$titulo_email_janela = "Comunicados Telecontrol - A Janela de Aprovação de Chamados está Aberta.";
$titulo_email_fim    = "Comunicados Telecontrol - A Janela de Aprovação de Chamados encerra hoje.";
$anexoInicio         = __DIR__ . '/../../helpdesk/imagens/Nuvem_Abertura_Janela_HD.png';
$anexoFim            = __DIR__ . '/../../helpdesk/imagens/Nuvem_Fim_Janela_HD.png';

$anexoEmail = $anexoInicio;

$logErro = array(
	'fabrica'	=> 'Telecontrol',
	'tipo'		=> 'helpdesk',
	'log'		=> 2
);

$log = array(
	'fabrica'	=> 'Telecontrol',
	'tipo'		=> 'helpdesk',
	'log'		=> 1
);



/*************************************************************************************************
 * Interpreta os argumentos do CLI:                                                              *
 * -d      modo Debug                                                                            *
 * --debug  ídem                                                                                 *
 *                                                                                               *
 * -t                                 modo teste, pode passar um e-mail para testar, p.e.        *
 *                           email_hd_janela -t=suporte[@telecontrol.com.br]                     *
 *                                    (o @telecontrol.com.br é opcional, se passar só o usuário, *
 *                                    adiciona o servidor)                                       *
 *                           email_hd_janela -t=meu_email@gmail.com.br,outro_email@gmail.com.br  *
 * --teste[="usuario[@servidor]"]     ídem                                                       *
 *                                                                                               *
 * -f , --from="usuario[@servidor]"   endereço do remetente (padrão: sistema@telecontrol.com.br) *
 *                                                                                               *
 * --force[=fim]                      força o envio do comunicado (padrão: 'ini')                *
 *                                                                                               *
 * --titulo="Título para o e-mail"                                                               *
 * --logto="email" / --logto=usuario  muda o destinatário do e-mail de log de erro               *
 *************************************************************************************************/


$sArgs ="d::t::f:h";
$longArgs = array('debug::','teste::','from:','force::','titulo:','logto:','help');

if (isCLI):
	$cliArgs = getopt($sArgs, $longArgs);
else:
	$cliArgs = array_filter('anti_injection', $_GET);
endif;

// Ajuda
if (isCLI and isset($cliArgs['h']) or isset($cliArgs['help'])) {
	echo <<<HELP
Uso:
php email_hd_janela.php [[-d|--debug][={1,2,3}]
                        [[-t|--teste][=email[,email...]]]
                        [--titulo="texto"] [--logto=email]
                        [[-f|--from]=email] [-h|--help]
                        [--force=[fim]]

Propósito:
Confere se há alguma janela de aprovação de chamados aberta e envia um email
aos supervisores de HelpDesk ativos.

Opções:

    -h, --help      Esta ajuda

    -d, --debug     Habilita o modo debug. Neste modo, alguns dados como queries,
                    resultados e outros são jogados em tela para análise
                    Aceita opcionalmetne alguns valores:

                    debug=1     Não envia o email, mas processa o resto
                    debug=2     Como o nível 1, mas eniva o e-mail
                    debug=3     Alguns extras, como as queries, são mostradas

    -t, --teste     Realiza todo o processo, mas o e-mail é enviado aos endereços
                    de teste pré-definidos ou aos informados como valor:
                      --teste=suporte    ou
                      --teste=suporte@telecontrol.com.br

                    Se o e-mail não tem o  servidor (como o primeiro exemplo)
                    ele é completado com '@telecontrol.com.br'. Assim, 
                      --teste=analistas

                    enviaria um e-mail ao endereço 'analistas@telecontrol.com.br'.
                    Podem ser informados vários destinatários, separando com 
                    vírgulas.

    --titulo        Altera o título padrão do e-mail para o informado.
                    Se tiver espaços, usar aspas duplas:
                      --titulo="Título Alternativo"

    --logto         Envia o e-mail com o log de erro para o endereço 
                    informado. Mesmas regras que para --teste

    -f, --from      Altera o remetente do e-mail de 'sistema@telecontrol.com.br'
                    para o informado. Mesmas regras, mas sópode ser um e-mail.

    --force         Permite forçar o envio do e-mail, ignorando a janela de 
                    abertura de chamados. Opcionalmente pode passar o valor
                    'fim' para enviar o e-mail:

                      php email_hd_janela --teste=suporte --force=fim

                    Envia um e-mail de fim de janela para o endereço de
                    teste 'suporte@telecontrol.com.br'

HELP;
	die();
}

// Parse arguments
if (isset($cliArgs['t'])) {
	$tAddr = $cliArgs['t'];
	$testMode = true;

	if ($tAddr) {
		// Admite valores separados por vírgulas
		$a_addr = explode(',', $tAddr);

		foreach ($a_addr as $em) {

			// Adiciona o servidor se passar só o usuário. Ex.: suporte passa a ser suporte@telecontrol.com.br
			if (preg_match('/^(\w|\.)+$/', $em))
				$em .= '@telecontrol.com.br';

			if (is_email($em))
				$emailTest[]['email'] = $em;
		}

		unset($a_addr, $em);
	}
}
if (isset($cliArgs['teste'])) {
	$tAddr = $cliArgs['teste'];
	$testMode = true;

	if ($tAddr) {
		// Admite valores separados por vírgulas
		$a_addr = explode(',', $tAddr);

		foreach ($a_addr as $em) {

			// Adiciona o servidor se passar só o usuário. Ex.: suporte passa a ser suporte@telecontrol.com.br
			if (preg_match('/^(\w|\.)+$/', $em))
				$em .= '@telecontrol.com.br';

			if (is_email($em))
				$emailTest[]['email'] = $em;
		}
		unset($a_addr, $em);
	}
}

if ($cliArgs['from'] != '') {
	$tAddr = $cliArgs['from'];

	// Adiciona o servidor se passar só o usuário. Ex.: suporte passa a ser suporte@telecontrol.com.br
	if (preg_match('/^(\w|\.)+$/', $tAddr))
		$tAddr .= '@telecontrol.com.br';

	$emailFrom = (is_email($tAddr)) ? $tAddr : remetentePadrao;
}
if ($cliArgs['logto'] != '') {
	$tAddr = $cliArgs['logto'];

	// Adiciona o servidor se passar só o usuário. Ex.: suporte passa a ser suporte@telecontrol.com.br
	if (preg_match('/^(\w|\.)+$/', $tAddr))
		$tAddr .= '@telecontrol.com.br';

	$emailLog = (is_email($tAddr)) ? $tAddr : remetentePadrao;
}

// Se estiver rodando como ROOT, NÃO tem debug em tela, vai
// tudo pro log / log de erro
$debug = (($_GET['debug']=='t' or isset($cliArgs['d']) or isset($cliArgs['debug']))
   			and !isRoot);

if ($debug) {
	echo "--- DEBUG MODE ON ---\n";
	$dv = $cliArgs['d'];
	$dv = $cliArgs['debug'];

	$debug = (strlen($dv) > 0) ? (string) $dv : true;
}

if (isCLI and $debug) print_r($cliArgs);

$logErro['dest'] = ($emailLog) ? $emailLog : remetentePadrao;

// Usar a função de log padrão
function w2log($tipo_log, $msg, $die = false, $sendMail = false) {

	global $logErro, $log, $titulo_email, $cliArgs;

	$dados_envio = ($tipo_log == 2) ? $logErro : $log;

	Log::log2($dados_envio, $msg);

	if ($sendMail) {

		// Já deveria vir, mas se não...
		if (!isset($dados_envio['dest']))
			$dados_envio['dest'] =  'helpdesk@telecontrol.com.br';

		// O padrão da classe é iso8859-1 e desde helpdesk...
		// Como dá opção de já enviar o cabeçalho, persinalizei.
		$dados_envio['head'] =  "MIME-Version: 1.0 \n" .
								"Content-type: text/html, charset=utf-8\n" .
								"To: ".$dados_envio['dest']." \n" .
								"From: sistema@telecontrol.com.br";

		Log::envia_email($dados_envio, $titulo_email, nl2br($msg));
	}

	if (!isCLI)
		$msg= nl2br($mlg);

	// $debug só pode ser TRUE se !isRoot.
	if ($debug && !$die) {
		if (isCLI):
			echo($msg) . chr(10);
		else:
			p_echo($msg);
		endif;
	}

	// Se pede para finalizar o script, e não é Root, mostra o erro em tela/terminal
	if ($die) {
		if (isRoot) die();
		if (isCLI) die($msg);
		//else
		p_echo($msg);
		die();
	}
}

if (!isCLI)
	header('Content-Type: text/html; charset=utf-8');

if (!is_resource($con)) {
	w2log(2, 'Erro de conexão com o banco de dados!', true, true); // Grava a mensagem no log e sai com a mesma mensagem
}

if (!file_exists($anexoInicio) or !file_exists($anexoFim)) {
	w2log(2, "Não foi possível localizar os arquivos a serem enviados por e-mail", true, true);
}

if (isset($cliArgs['force'])) {
	if ($cliArgs['force'] == 'fim') {
		$tipo = 'final';
		$anexoEmail = $anexoFim;
		$titulo_email_janela = $titulo_email_fim;
	}
} else {
	$sql_l = "SELECT hd_janela, data_inicial, data_final,
				CASE
					WHEN (data_inicial::date = CURRENT_DATE) THEN 'inicio'
					WHEN (data_final::date   = CURRENT_DATE) THEN 'final'
					ELSE NULL
				END AS tipo
				FROM tbl_hd_janela
				WHERE fabricas IS NULL
				AND CURRENT_DATE BETWEEN data_inicial::date AND data_final::date;
	";
	$res_l = pg_query($con, $sql_l);

	if (!is_resource($res_l)){
		w2log(2, "Erro ao consultar as janelas de HelpDesk.\n\n" . pg_last_error($con), true, true);
	}

	if (!pg_num_rows($res_l)) {

		$msg = 'Sem resultados para esta consulta.' . chr(10);
		if ($debug >= '1') {
			$msg .= (isCLI) ? "[36;1m $sql_l [0m\n" : "<code>$sql_l</code>\n";
		}
		w2log(1, $msg, true); //Loga e sai

	}
	if ($debug == '1')
		echo (isCLI) ? print_r(pg_fetch_assoc($res_l, 0), true) : array2table(pg_fetch_assoc($res_l, 0));

	if (pg_fetch_result($res_l, 0, 'tipo') == 'final') {
		$anexoEmail = $anexoFim;
		$titulo_email_janela = $titulo_email_fim;
	}
}

// O título informado na linha de comandos tem preferência
if ($cliArgs['titulo'] != '') {
	$titulo_email_janela = $cliArgs['titulo'];
	if ($debug >= 1 and isCLI)
		echo "Novo título: $titulo_email_janela\n";
}

$sql_sup = "SELECT DISTINCT	email, nome_completo AS nome, tbl_admin.fabrica
              FROM tbl_admin
			  JOIN tbl_fabrica USING(fabrica)
             WHERE ativo IS TRUE
               AND fabrica NOT IN (0, 10, 46, 63, 92, 93, 102, 103, 109)
			   AND ativo_fabrica
               AND help_desk_supervisor IS TRUE
               AND is_email(email, true)";

$res_sup = pg_query($con, $sql_sup);

if (!is_resource($res_sup)) {
	w2log(2, "Erro na consulta.\n<code>\n$sql_sup\n</code>\n" . pg_last_error($con), true, true);
}

$total_emails = pg_num_rows($res_sup);

if ($total_emails) {
	$emails = pg_fetch_all($res_sup);

	$boundary   = "XYZ-" . date("dmYis") . "-ZYX";
	$remetente  = (empty($from))   ? '"Comunicados Telecontrol" <sistema@telecontrol.com.br>' : $from;
	$titulo	    = $titulo_email_janela;
	$returnPath = $remetente;

	$file_att   = file_to_eml_part($anexoEmail, $boundary, 'comunicado_janela_hd.png');

	$file_contents = $file_att['eml_part'];
	$cid           = $file_att['cid'];

	unset($file_att);

	$htmlMsg .= "\n<br /><img src=\"cid:" . $cid . "\" />\n";

	$msn_headers = <<<MSNHEADS
MIME-Version: 1.0
From: $remetente
Reply-To: $remetente
Return-Path: $returnPath
Content-type: multipart/mixed; boundary="$boundary"\n
Content-Transfer-Encoding: 8bits
Content-Type: text/html; charset="UTF-8"\n
MSNHEADS;

	$headers	 = <<<FHEADS
MIME-Version: 1.0
From: $remetente
Reply-To: $remetente
Return-Path: $returnPath\n
FHEADS;

	if (isset($file_contents)) {
		$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\n";
		$mensagem = "--$boundary\n";
		$mensagem.= "Content-Transfer-Encoding: 8bits\n";
		$mensagem.= "Content-Type: text/html; charset=\"UTF-8\"\n\n";
		$mensagem.= $htmlMsg;
		//$mensagem.= "--$boundary\n\n";
		$mensagem.= $file_contents;
	} else {
		$headers .= "Content-Transfer-Encoding: 8bits\n".
					"Content-Type: text/html; charset=\"UTF-8\"\n\n";

		$mensagem = $htmlMsg;
	}

	//$msn_msg .= "<center><img src='http://ww2.telecontrol.com.br/img/Cartao-de-Natal-2012.jpg' alt='Feliz Natal de parte da Equipe Telecontrol' /></center>";

	if ($debug) {
		w2log(1, array2table($emails, "Lista de Destinatários"));
		if (isCLI) {
			echo "Enviando [37;1m$total_emails[0m mensagens...\n\n";
			echo "[36;1m Fonte da Mensagem [35;1m$titulo_email_janela[0m\n";
			echo "[33;1m" . $headers . substr($mensagem, 0, 1024) . "\n[0m\n";
		} else {
			echo "Enviando $total_emails mensagens...";
			//echo array2table($emails, "Lista de Destinatários");
			pre_echo($headers . substr($mensagem, 0, 1024), "Fonte da mensagem");
		}
	}

	if ($testMode) { // Opção TESTAR EMAIL: substitui os endereços localizados pelos de teste
		$dest_org = array2table($emails, "Lista de Destinatários Originais");

		unset($emails);

		if ($emailTest) {
			$emails = $emailTest;
		} else {
			$emails = array(
				0 => array('email' => 'mlopezgva@gmail.com'),
					 array('email' => 'ronaldo@telecontrol.com.br'),
				//	 array('email' => 'sergio@telecontrol.com.br'),
				//	 array('email' => 'nica_mlg@hotmail.com'),
				//	 array('email' => 'rodrigo.perina@telecontrol.com.br'),
			);
		}
		if (isCLI) {
			echo "Enviando e-mail para os endereços de teste.\n" . 'Seriam enviadas ' . $total_emails . ' mensagens.';
		} else {
			p_echo('Enviando e-mail para os endereços de teste.<br />Seriam enviadas ' . $total_emails . ' mensagens.');
		}
	} else {
		if (isCLI) {
			echo "Enviando e-mail para $total_emails endereços.\n\n";
		} else {
			p_echo("Enviando e-mail para $total_emails " . ' endereços.');
		}
	}

	$enviados = array();

	// $emails[] = array(
	// 	'nome'	=> 'Manuel Teste',
	// 	'email'	=> 'manuel.lopez@telecontrol.com.br'
	// );

	foreach($emails as $destinatario) {

		$email_destino = $destinatario['email'];
		$nome_destino  = utf8_encode($destinatario['nome']);

		// Se já foi enviada mensagem para este e-mail, pular para o próximo!
		if (in_array($email_destino, $enviados)) {
			$dupes[] = $email_destino;
			continue;
		}

		if ($nome_destino != '')
			$email_destino = "\"$nome_destino\" <$email_destino>"; // Padrão RFC
	/*
		if (preg_match("/hotmail|msn\.com|live\.com|[bu]ol\.com\.br/", $email_destino)) {
			p_echo("<b>Alterando conteúdo para endereço de e-mail</b>: " . $email_destino);
			//continue; //Por enquanto, não enviar para hotmail.
			$enviou = mail($email_destino, $titulo, $msn_msg, $msn_headers);
		} else {
			$enviou = mail($email_destino, $titulo, $mensagem, $headers);
		}
	 */
		// Debug nível 2, envia o e-mail
		if (!$debug or $debug >= 2):
			$enviou = mail($email_destino, $titulo, $mensagem, $headers);
		else:
			$enviou = true; // Simula envio OK
		endif;

		if ($enviou) {
			$enviados[] = $destinatario['email']; // Para verificar se já foi enviado e-mail para este endereço
		} else {
			$falidos[] = $destinatario['email'];  // Para o log de erro
		}
	}

	if (count($enviados)) {
		$msg = "\nEnviados " . count($enviados) . " e-mails.\n\n";

		if ($debug > '2') {
			echo (isCLI) ? "\n[34;1mLista de enviados:[0m\n" . implode("\n", $enviados) . chr(10) : array2table($enviados, 'Lista de enviados');
		}

		$msg .= "\nLista de enviados:\n" . implode("\n", $enviados) . chr(10);

		w2log(1, "$msg\n");
	}

	if (count($falidos)) {
		w2log(2, "Erro ao enviar para...\n" . implode("\n", $falidos) . chr(10), false, true);
		if ($debug > 1) // Só se o debug mode permite enviar emails
			echo (isCLI) ?  "Erro ao enviar para...\n" . implode("\n", $falidos) . chr(10) : array2table($falidos, 'Erro ao enviar para...');
	}

	if (count($dupes)) {
		w2log(1, ' E-mail duplicados...' . chr(10) . implode("\n", $dupes) . chr(10));
		if ($debug >= 2)
			echo (isCLI) ?  count($dupes) . ' E-mail duplicados...' . chr(10) . implode("\n", $dupes) . chr(10) : array2table($dupes, 'E-mail duplicados...');
	}

} else {
	w2log(2, $sql_emails, 'Erro: '. pg_last_error($con) . chr(10) . $sql_emails, false, true);
}
