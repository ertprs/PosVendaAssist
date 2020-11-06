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
        SELECT TC.acao1_comunicado, TC.envio AS email_enviado, TC.posto, TC.email, TC.contato_nome,
               P.nome, PA.codigo_posto
          FROM arquivo_acao1_comunicado AS TC
          JOIN tbl_posto             AS P  USING(posto)
          JOIN tbl_posto_fabrica     AS PA USING(posto)
         WHERE fabrica = 160";
           // AND envio IS NULL";

    if ($DEBUG)
        $sqlPostos .= " LIMIT 1";

    $res = pg_query($con, $sqlPostos);

    // Posto identificado. Vamos ver se tem pedidos sem processar.
    $postos = pg_fetch_all($res);

    pg_prepare(
        $con, 'pedidos_einhell',
        "SELECT pedido, data, valor_total, COUNT(peca) AS itens, SUM(qtde) AS total_pecas
           FROM arquivo_acao1_dados
          WHERE posto = $1
       GROUP BY pedido, data, valor_total
       ORDER BY data, pedido"
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
		'link' => '../login.php',
		'icon' => '../imagens/icone_telecontrol_branco.png',
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
		'link' => '../logout_2.php',
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

$msgTpl = "
<p>Caro posto autorizado Einhell,</p>

<p>Devido a recente aquisição da operação Einhell no Brasil (<strong><em>EINHELL DO BRASIL LTDA</em></strong>) pela sua nova controladora <strong>ÂNCORA</strong> (<strong><em>Âncora Sistemas de Fixação</strong></em>) com consequente transição de direção, a mesma está restruturando e organizando os estoques e pendências passadas.</p>
<p>É muito grande a preocupação desta nova controladora em atender com a devida qualidade e presteza toda a rede de Assistência Técnica. Para isso, levantamos em nosso banco de dados pedidos anteriores a esta nova gestão, porém se faz necessária a confirmação dos pedidos de compra de peças para faturamento.</p>
<p>Tal ação é importante para não haver envios indevidos e para atender e liquidar quaisquer pendências.</p>

<p>Por favor, <a href='%s?token=%s&codigo_posto=%s'>clique AQUI para acessar a página de nosso sistema</a> na qual você poderá confirmar ou cancelar os pedidos que estejam pendentes.</p>

<div>%s</div>

<p>Caso nenhuma opção seja selecionada até dia <strong>25/02/2018</strong>, o pedido será automaticamente considerado como <strong><em>cancelado</em></strong>.</p>

<div align='right'>Atenciosamente,<br>Telecontrol Networking.</div>\n";

echo "<div class='container-fluid'><div class='col-md-8 col-md-offset-1'>";
foreach ($Info as $posto => $infoPosto) {
    // pre_echo($infoPosto, 'Info do Posto', true);
    $codigo_posto  = $infoPosto['Dados']['codigo_posto'];
    $token         = sha1($posto . $codigo_posto);
    $contato_email = $infoPosto['Dados']['email'];
    $link          = 'http://posvenda.telecontrol.com.br/assist/externos/einhell/confirma_pedido_desativado.php';

    if ($DEBUG or $_serverEnvironment=='development') {
        $link = 'confirma_pedido_desativado.php';
        $contato_email = [
            'nicamlg@gmail.com',
            // 'tulio@telecontrolcom.br', 'gustavo.pinsard@telecontrol.com.br','waldir@telecontrol.com.br',
            // 'roberto@ancora.com.br', 'marcos.rossi@einhell.com.br'
        ];
    }

    $tabela = array2table($infoPosto['Pedidos'], 'Pedidos Faturados aguardando posição:');
    $msg    = sprintf($msgTpl, $link, $token, $codigo_posto, $tabela);
    $footer = "
        <div class='panel-footer'>
            <a class='btn btn-warning' href='?acao_ajax=$codigo_posto-enviar'>Enviar E-mail</a>
        </div>\n";

    if (is_null($infoPosto['Dados']['email_enviado'])) {
        if ($acao_ajax == $codigo_posto . '-enviar') {
            $mailOK = $mail->sendMail($contato_email, "Pedidos Faturados Einhell Pendentes", $msg);

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
        $panelClass = 'info';
        $data_envio = $infoPosto['Dados']['email_enviado'];
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
        <h3 class='panel-title'>Mensagem para o Posto $codigo_posto</h3>
      </div>
      <div class='panel-body'>$msg</div>$footer
    </div>
    ";
	flush();
}

echo "</div></div>";


