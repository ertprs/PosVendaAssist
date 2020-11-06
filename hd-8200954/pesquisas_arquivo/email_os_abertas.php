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
    SELECT TC.posto
         , DATE_TRUNC ('SECOND', TC.envio) AS email_enviado
         , TC.codigo_posto
         , TC.razao_social
         , TC.email
         , telefones
         , (SELECT COUNT(os)
              FROM arquivo_acao2_dados TD
             WHERE TD.posto = TC.posto
           ) AS qtde_os
         , envio AS email_enviado
      FROM arquivo_acao2_comunicado AS TC
     WHERE envio IS NULL";
       // AND (SELECT COUNT(pedido)
       //        FROM arquivo_acao1_dados TD
       //       WHERE TD.posto = TC.posto
       //         AND COALESCE(TD.opcao, 0) = 0
       //    ) > 0

  if ($DEBUG)
    $sqlPostos .= " LIMIT 1";

  $res = pg_query($con, $sqlPostos);

  // Posto identificado. Vamos ver se tem pedidos sem processar.
  $postos = pg_fetch_all($res);

  pg_prepare(
    $con, 'os_einhell', "
    SELECT sua_os AS \"Núm. OS\"
         , data_abertura
             , CASE tipo_os
                    WHEN 'R' THEN 'Revenda'
                    ELSE 'Consumidor'
               END         AS tipo_OS
             , consumidor_nome
             , CURRENT_DATE - data_abertura AS \"Dias Aberta\"
             -- , opcao
      FROM arquivo_acao2_dados
     WHERE posto = $1
           AND status IS FALSE
    "
  );

  $Info = array();

  foreach ($postos as $Posto) {
    $posto   = $Posto['posto'];
    $pedidos = array();

    unset($Posto['posto']);

    $resOS = pg_execute($con, 'os_einhell', [$posto]);

    if (!is_resource($resOS))
      throw new Exception(pg_last_error($con), 400);

    // echo "PEDIDOS DO POSTO $posto:<br>";

    $Info[$posto]['Dados'] = $Posto; // Dados do posto
    $Info[$posto]['OSs']   = pg_fetch_all($resOS);
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

$layout_menu = "os";
$title       = traduz("ENVIO DE EMAILS OS EINHELL PARADOS");
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
    'layouts' => array('OS'),
    'link'    => '#',
    'name'    => traduz('ordens.de.servico'),
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
  <?=$cabecalho->headers?>
  <style>
  .main2 .right {text-align: right;}
  .cabecalho .table {height: auto}
  .table th {text-align: center;text-transform: capitalize}
  .table td {text-align: center}
  .table td {text-align: left}
  .table td+td+td {text-align: right; white-space: nowrap}
  .table td+td+td+td+td {text-align: center}
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
<p>Caro posto autorizado <strong>%s</strong>,</p>

<p>
Como parte das ações que visam garantir a qualidade na prestação dos serviços
aos consumidores dos produtos Einhell, identificamos a existência de %d OS em
aberto com mais de 90 dias e precisamos tomar as ações necessárias para a
conclusão destes casos.
</p>
<p></p>
<div>%s</div>
<p></p>
<p>
Durante os próximos dias um de nossos atendentes entrará em contato por
telefone para colher as informações à respeito destas OS. Assim, por favor
prepare as informações e comentários que tenha a fazer antecipadamente de
maneira a aproveitar a oportunidade de nosso contato.
</p>

<p>Caso tenha dúvidas, por favor entre em contato com <strong><em>SAC Einhell</em></strong>
pelo telefone <strong><code>0800-7187825</code></strong>.</p>

<div align='right'>Atenciosamente,<br>Telecontrol Networking.</div>\n";

echo "<div class='container-fluid'>
  <div class='row'>&nbsp;</div>
    <div class='col-md-10 col-md-offset-1'>";

/********************************************************************************************
 * No vai ser enviado e-mail, estou desativando toda a parte do e-mail e criando uma tabela *
 * no estilo dos pedidos.                                                                   *
 ********************************************************************************************/

$tabela = [];

foreach ($Info as $posto => $infoPosto) {
  // pre_echo($infoPosto, 'Info do Posto', true);
  $codigo_posto  = $infoPosto['Dados']['codigo_posto'];
  $razao_social  = $infoPosto['Dados']['razao_social'];
  $token         = sha1($posto . $codigo_posto);
  // $contato_email = $infoPosto['Dados']['email'];
  $link          = 'http://posvenda.telecontrol.com.br/assist/externos/einhell/confirma_os_pedido.php';
  $data_envio    = $infoPosto['Dados']['email_enviado'];
  $enviada       = is_date($data_envio) < is_date('2018-03-10');
  $telefones     = $infoPosto['Dados']['telefones'];

  if ($DEBUG or $_serverEnvironment=='development') {
    $link = 'confirma_os_pedido.php';
    $contato_email = [
      'nicamlg@gmail.com',
      // 'tulio@telecontrolcom.br', 'gustavo.pinsard@telecontrol.com.br','waldir@telecontrol.com.br',
      // 'roberto@ancora.com.br', 'marcos.rossi@einhell.com.br'
    ];
  }

  $tabela[] = [
    'Posto' => $codigo_posto,
    'Razão Social' => $razao_social,
    'Qtd. OS' => $infoPosto['Dados']['qtde_os'],
    'Tel. Contato' => array_unique(
        array_map(
          'phone_format',
          pg_parse_array($telefones)
        )
      ),
    'Link' => "<a class='btn btn-sm btn-default' target='new' href='$link?token=$token&codigo_posto=$codigo_posto'>Acessar Painel</a>"
  ];
}

echo array2table($tabela) . "</div></div>";
// vim: sts=2 ts=2 sw=2 et

