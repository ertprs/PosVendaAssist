<?php
define ('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
$DEBUG = (bool)$_GET['debug'];

$no_pdo = true;
include_once APP_DIR . 'dbconfig.php';
include_once APP_DIR . 'includes/dbconnect-inc.php';

include_once APP_DIR . 'helpdesk/mlg_funciones.php';
include_once APP_DIR . 'class/fn_sql_cmd.php';
include_once APP_DIR . 'class/communicator.class.php';
include_once APP_DIR . 'fn_traducao.php';

include_once APP_DIR . 'regras/menu_posto/menu.helper.php';
global $login_posto, $login_fabrica, $externalEmail, $externalId;

header('Content-Type: text/html; charset=ISO-8859-1');

$login_fabrica = 160;
$externalEmail = 'suporte@telecontrol.com.br';
$externalId    = 'posvenda@tc';

try {
	$mail = new TcComm('noreply@tc', $externalEmail);
	$externalEmail = 'suporte@telecontrol.com.br';

	pg_query("SET DateStyle TO 'SQL, DMY'");
	$sqlPostos = "
	SELECT TC.acao1_comunicado
	     , DATE_TRUNC('SECOND', TC.envio) AS email_enviado
	     , TC.posto
	     , P.nome AS razao_social
	     , TC.email
	     , TC.contato_nome
	     , P.nome
	     , PA.codigo_posto
	     , ARRAY[P.fone, PA.contato_fone_comercial]||PA.contato_telefones AS fones
	  FROM arquivo_acao1_comunicado AS TC
	  JOIN tbl_posto             AS P  USING(posto)
	  JOIN tbl_posto_fabrica     AS PA USING(posto)
	 WHERE fabrica = $login_fabrica
	   AND (SELECT COUNT(pedido)
	          FROM arquivo_acao1_dados TD
	         WHERE TD.posto = TC.posto
	           AND COALESCE(TD.opcao, 0) = 0
	       ) > 0
  "; // AND envio IS NULL";

	if ($DEBUG)
		$sqlPostos .= " LIMIT 1";

	$res = pg_query($con, $sqlPostos);

	if (!is_resource($res))
		throw new Exception(pg_last_error($con), 400);

	// Posto identificado. Vamos ver se tem pedidos sem processar.
	$postos = pg_fetch_all($res);

	pg_prepare(
		$con, 'pedidos_einhell', "
		SELECT pedido
		     , data
		     , valor_total
		     , COUNT(peca) AS itens
		     , SUM(qtde)   AS total_pecas
		  FROM arquivo_acao1_dados
		 WHERE posto = $1
		 GROUP BY pedido, data, valor_total, status
		HAVING status = FALSE
		"
	);

	$Info = array();

	foreach ($postos as $Posto) {
		$posto   = $Posto['posto'];
		$pedidos = array();

		unset($Posto['posto']);

		$resPedido = pg_execute($con, 'pedidos_einhell', [$posto]);

		if (!is_resource($resPedido))
			throw new Exception(pg_last_error($con), 400);

		// echo "PEDIDOS DO POSTO $posto:<br>";

		while ($rowPedido = pg_fetch_assoc($resPedido)) {
			// echo "<br>Pedido ".
			$pedido = $rowPedido['pedido'];
			$rowPedido['data'] = is_date($rowPedido['data'], '', 'EUR');
			$rowPedido['valor_total'] = number_format($rowPedido['valor_total'], 2, ',' , '.');
			// unset($rowPedido['pedido']);

			$pedidos[$pedido] = $rowPedido;
			// $pedidos[$pedido]['pecas'] = pg_fetch_all(pg_execute, 'itens_einhell', [$posto, $pedido]);
		}
		$Info[$posto]['Dados'] = $Posto; // Dados do posto
		$Info[$posto]['Pedidos'] = $pedidos;
	}

	// pre_echo($Info, 'TUDO', true);

} catch (\Exception $e) {
	echo $e->getLine();

	$error = [
		'code' => $e->getCode(),
		'msg'  => $e->getMessage()
	];

	header(sprintf("HTTP/1.1 %d %s", $error['code'], $error['msg']));

	if (file_exists(APP_DIR . '40x.php'))
		include(APP_DIR . '40x.php');
	die;
}

$layout_menu = "pedido";
$title       = traduz("ENVIO DE EMAILS PEDIDOS EINHELL PARADOS");
$login_fabrica = 160;
$login_posto   = 4311;

$menu = array(
	'HOME' => array(
		'link' => APP_DIR . 'login.php',
		'icon' => APP_DIR . 'imagens/icone_telecontrol_branco.png',
		'name' => '', // sem texto
		'attr' => ['title' => traduz('menu.inicial')],
	),
	'Pedidos' => array(
		'layouts' => array('pedido'),
		'link'    => '#',
		'name'    => traduz('pedidos'),
	),
	'Sair' => array(
		'name' => traduz('Sair'),
		'link' => APP_DIR . 'logout_2.php',
	)
);

$tableAttrs = array(
	'tableAttrs' => 'cellpadding="4" border="1" data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
);

$cabecalho = new MenuPosto($menu, 'FMC');
$cabecalho->logo = '<img src="../logos/logo_einhell.jpg" style="height:50px" />';
?>
<!DOCTYPE html>
<html lang="<?=$cook_idioma?>">
<head>
	<meta charset="ISO-8859-1">
	<title>Telecontrol - Gerenciamento de Pós-Venda &ndash; <?=$title?></title>
	<link href="../imagens/tc_2009.ico" rel="shortcut icon">
	<link rel="stylesheet" type="text/css" href="../fmc/css/styles.css">
	<link media="screen" type="text/css" rel="stylesheet" href="../externos/bootstrap3/css/bootstrap.min.css" />
	<link media="screen" type="text/css" rel="stylesheet" href="../externos/bootstrap3/css/bootstrap-theme.min.css" />
	<script type="text/javascript" src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
	<script type="text/javascript" src="../externos/bootstrap3/js/bootstrap.min.js"></script>
	<!-- <link media="screen" type="text/css" rel="stylesheet" href="../../css/tc_css.css" /> -->
	<?=$cabecalho->headers?>
	<style>
	.main2 .right {text-align: right;}
	.cabecalho .table {height: auto}
	.table th {text-align: center;text-transform: capitalize}
	.table th+th+th {text-align: right}
	.table td {text-align: center}
	.table td+td+td {text-align: right}
	</style>
</head>
<body style="margin-top: 45px;">
<?php
echo $cabecalho->setFw($menuFw)->navBar($layout_menu);
?>
<div class="clearfix">&nbsp;</div>
</div>
<?php
// Se é para mostrar o cabeçalho...
echo $cabecalho->cabecalho($title, $banner);

/**
 * Mensagens de erro e sucesso/êxito
 * ---------------------------------
 * Se `$error_alert` é TRUE, processa as variáveis $msg_erro (string ou array)
 * $msg (string ou array).
 * Se não tem outras informações, a primeira é para erro (vermelho) e a segunda
 * para informações (azul), usando as cores e formatação formecidas pela FMC.
 */
if ($msg_alerts):
	echo $cabecalho->alert($msg_alerts, 'danger', 'exclamation-triangle');
endif;

if (count($msg) and $error_alert === true):
	echo $cabecalho->alert($msg, 'info', 'check-circle');
endif;

if (count($msg_erro) and $error_alert === true):
	if (array_key_exists('msg', $msg_erro)):
		if (count($msg_erro['msg'])) {
			echo $cabecalho->alert($msg_erro['msg'], 'danger', 'ban');
		}
		if (array_key_exists('campos')):
			echo $cabecalho->alert($msg_erro['campos'], 'danger', 'exclamation-triangle');
		endif;
	else:
		echo $cabecalho->alert($msg_erro, 'danger', 'ban');
	endif;
endif;

if ($desabilita_tela):
	echo $cabecalho->alert($desabilita_tela, 'danger', 'lock');
	include_once('rodape.php');
	exit;
endif;

echo $bodyHTML;

$listaEnviados = array();

$msgTpl = "
<p>Caro posto autorizado Einhell,</p>

<p>Sua opção por confirmar ou cancelar os pedidos de peças para revenda que estão pendentes
não foi informada durante o primeiro prazo. Agora você tem uma segunda e última oportunidade
para informar se deseja confirmar ou cancelar os pedidos faturados que estejam pendentes.</p>
<p>Por favor, faça sua opção através de nosso painel exclusivo até o dia <strong>07/03/2018</strong>.</p>
<p></p>
<p><a href='%s?token=%s&codigo_posto=%s'>Acesse AQUI seu Painel de Confirmação</a> onde você
poderá confirmar ou cancelar os pedidos que estejam pendentes.</p>

<div>%s</div>
<p></p>
<p><b>Pedidos não confirmados até o dia 07/03/2018 serão cancelados automaticamente</b></p>
<p></p>
<p>Caso tenha dúvidas, por favor entre em contato com <strong><em>SAC Einhell</em></strong>
pelo telefone <strong><code>0800-7187825</code></strong>.</p>

<div align='right'>Atenciosamente,<br>Telecontrol Networking.</div>\n";

echo "<div class='container-fluid'>
	<div class='fluid-row'>
		<div class='col-md-offset-5 col-md-span-2'><a class='btn btn-default' href='?acao_ajax=enviar_tudo'>Enviar TODOS</a></div>
	</div>
<div class='row'>&nbsp;</div>
	<div class='col-md-9 col-md-offset-1'>";

foreach ($Info as $posto => $infoPosto) {
	// pre_echo($infoPosto, 'Info do Posto', true);
	$codigo_posto  = $infoPosto['Dados']['codigo_posto'];
	$token         = sha1($posto . $codigo_posto);
	$contato_email = $infoPosto['Dados']['email'];
	$link          = 'http://posvenda.telecontrol.com.br/assist/externos/einhell/confirma_pedido.php';
	$data_envio    = $infoPosto['Dados']['email_enviado'];
	$enviada       = is_date($data_envio) < is_date('2018-03-01');

	if ($DEBUG or $_serverEnvironment=='development') {
		$link = 'confirma_pedido.php';
		$contato_email = [
			'nicamlg@gmail.com',
			// 'tulio@telecontrolcom.br', 'gustavo.pinsard@telecontrol.com.br','waldir@telecontrol.com.br',
			// 'roberto@ancora.com.br', 'marcos.rossi@einhell.com.br'
		];
	}

	$tabela = array2table($infoPosto['Pedidos'], 'Pedidos Faturados aguardando posição:');
	$msg    = sprintf($msgTpl, $link, $token, $codigo_posto, $tabela);
	$lastSendtDate = ($data_envio) ? "\n\t\t\t<p class='pull-right text-muted'>Último envio em $data_envio</p>" : '';
	$footer = "
		<div class='panel-footer'>$lastSendtDate
			<a class='btn btn-warning' href='?acao_ajax=$codigo_posto-enviar'>Enviar E-mail</a>
			<a class='btn btn-default' target='new' href='$link?token=$token&codigo_posto=$codigo_posto''>Acessar Painel</a>
		</div>\n";
	if ($data_envio)

	// Data de corte dos priemiros e-mails, nada foi enviado em março da primeira leva.
	if (is_date($infoPosto['Dados']['email_enviado'], '', 'U') < is_date('2018-03-01', '', 'U')) {
		if ($acao_ajax == 'enviar_tudo' or $acao_ajax == $codigo_posto . '-enviar') {
			// $mailOK = $mail->sendMail($contato_email, "Pedidos Faturados Einhell Pendentes", $msg);

			// $mailOK = !$mailOK;

			if ($mailOK) {
				$panelClass = 'success';
				$footer = "
		<div class='panel-footer'>
			<div class='text text-success'>Mensagem enviada para <code>".join(', ', (array)$contato_email)."</code>!</div>
		</div>\n";
				pg_query($con, "UPDATE arquivo_acao1_comunicado SET envio = CURRENT_TIMESTAMP WHERE posto = $posto");
			} else {
				$panelClass = 'danger';
				$footer = "
		<div class='panel-footer'>
			<div class='text text-danger'>Mensagem NÃO enviada ({$mail->OK})!</div>
		</div>\n";
			}
		} else {
			$panelClass = 'warning';
		}

	} else {
		// Cria uma tabela com os dados de envio e as ações, mostra apenas se o usuário pediu
		$listaEnviados[] = [
			'Código' => $codigo_posto,
			'Posto' => str_words($infoPosto['Dados']['razao_social'], 4, true),
			'Contatos' => array_unique(
				array_map(
					'phone_format',
					pg_parse_array($infoPosto['Dados']['fones'])
				)
			),
			'Pedidos' => count($infoPosto["Pedidos"]),
			'Enviado' => $data_envio,
			'Painel' => "<a class='btn btn-default' target='new' href='$link?token=$token&codigo_posto=$codigo_posto'><i class='glyphicon glyphicon-new-window'</a></div>",
			'Reenviar' => "<a class='btn btn-info' href='?acao_ajax=$codigo_posto-enviar'><i class='glyphicon glyphicon-envelope'></i></a>"
		];
		continue; // desabilita o código abaixo

		$panelClass = 'info';
		$msg = "
			<h3>Mensagem enviada em $data_envio</h3>
			Para acessar o painel do Posto Autorizado, clique no botão de acesso:<br />
		";
		$footer = str_replace(
			['Enviar', 'btn-warning', '</div>'],
			['Reenviar', 'btn-info', "<a class='btn btn-default' target='new' href='$link?token=$token&codigo_posto=$codigo_posto''>Acessar Painel</a></div>"],
			$footer
		);
	}

	echo "<div class='panel panel-$panelClass'>
		<div class='panel-heading'>
		<h3 class='panel-title'>Mensagem para o Posto $codigo_posto <small>{$posto->nome}</small></h3>
		</div>
		<div class='panel-body'>$msg</div>$footer
	</div>
	";
	flush();
}

$tableAttrs = array(
	'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "',
);
echo array2table($listaEnviados, 'Email enviados: '.count($listaEnviados));
?>
			</div>
		</div>
	<script>
	$("table.table thead>tr").addClass('info');
	</script>
<?php
// vim: sts=2 ts=2 sw=2 noet

