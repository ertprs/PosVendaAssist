<?php
define ('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
$no_pdo = true;
include_once APP_DIR . 'dbconfig.php';
include_once APP_DIR . 'includes/dbconnect-inc.php';

include_once APP_DIR . 'helpdesk/mlg_funciones.php';
include_once APP_DIR . 'class/fn_sql_cmd.php';
include_once APP_DIR . 'fn_traducao.php';

include_once APP_DIR . 'regras/menu_posto/menu.helper.php';

header('Content-Type: text/html; charset=ISO-8859-1');

global $login_posto, $login_fabrica, $login_nome;

try {
    $token         = $_REQUEST['token'];
    $codigo_posto  = $_REQUEST['codigo_posto'];
    $codigo_posto  = preg_replace('/[^[a-zA-Z0-9_.-]]/', '', $codigo_posto);
    $login_fabrica = 160;
    $login_posto   = pg_fetch_result(
        pg_query(
            $con,
            "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'"
        ), 0, 0
    );

    if (!$login_posto) {
        throw new Exception("Posto não encontrado. Tente novamente ou contate com o Suporte da Telecontrol.", 404);
        // throw new Exception("Erro ao validar as informações de identificação. Por favor, tente novamente daqui alguns minutos.", 500);
    }

    $DBtoken = sha1($login_posto . $codigo_posto);

    if ($DBtoken !== $token) {
        throw new Exception("Erro ao validar o identificador. Verifique se o link é o que consta no e-mail. Se está correto, por favor entre em contato com o Suporte da Telecontrol.", 403);
    }

    pg_query($con, "SET DateStyle TO 'SQL, DMY'");

    $posto = pg_fetch_object(
        pg_query(
            $con,
            "SELECT posto, codigo_posto, nome
               FROM tbl_posto_fabrica AS PA
               JOIN tbl_posto AS P USING(posto)
              WHERE fabrica = $login_fabrica
                AND posto = $login_posto"
        ), 0
    );

    $login_nome = $posto->nome;

} catch (\Exception $e) {
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
        'name'    => traduz('pedidos.faturados'),
    ),
    'Sair' => array(
        'name' => traduz('Sair'),
        'link' => 'logout_2.php',
    )
);

$tableAttrs = array(
    'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
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
    <style>
    .main2 .right {text-align: right;}
    .cabecalho .table {height: auto}
    .table th {text-align: center;text-transform: capitalize}
    .table th:nth-of-type(3) {text-align: right}
    .table td {text-align: center}
    .table td+td {text-align: left}
    .table td+td+td {text-align: right}
    .table td+td+td+td {text-align: center}
    </style>
</head>
<body style="margin-top: 45px;">
<?php
echo $cabecalho->setFw($menuFw)->navBar($layout_menu);
?>
<div class="clearfix">&nbsp;</div>
<?php
// Se é para mostrar o cabeçalho...
echo $cabecalho->cabecalho($title, $banner);

if ($desabilita_tela):
    echo $cabecalho->alert($desabilita_tela, 'danger', 'lock');
    include_once('rodape.php');
    exit;
endif;

echo $bodyHTML;

// pre_echo($pedidos, 'Pedidos', true);

?>
    </div>
    <div class="container">
          <!-- <h1>Pedidos Faturados Einhell</h1> -->
          <p class="lead">Prezado Posto Autorizado <strong><?=$login_nome?></strong>:</p>
          <p class="lead">Obrigado por seu interesse em informar sua opção referente aos pedidos pendentes.<br />
             A fase de opção <em>online</em> está encerrada.</p>
          <p class="lead">Se precisar alterar sua opção para algum pedido, por favor entre em contato diretamente<br />
             com o <strong>SAC da <em>Einhell</em></strong>
             pelo telefone <strong><code>0800-742422</code></strong>.
          </p>
    </div>
</body>
</html>
