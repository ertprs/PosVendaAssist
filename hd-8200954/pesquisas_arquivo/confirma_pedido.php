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
        throw new Exception("Posto não encontrado. Tente novamente ou contate com o Suporte da Telecontrol.", 500);
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
               JOIN tbl_posto         AS P USING(posto)
              WHERE fabrica = $login_fabrica
                AND posto = $login_posto"
        ), 0
    );

    $login_nome = $posto->nome;

    pg_prepare(
        $con, 'pedidos_einhell',
        "SELECT pedido, data, valor_total, COUNT(peca) AS itens, SUM(qtde) AS total_pecas
           FROM arquivo_acao1_dados
          WHERE posto = $1
       GROUP BY pedido, data, valor_total, status
         HAVING status = FALSE
       ORDER BY data, pedido"
    );

    pg_prepare(
        $con, 'itens_einhell',
        "SELECT acao1_dados AS id,
                peca,
                referencia,
                descricao,
                qtde,
                status,
                CASE opcao
                    WHEN 0 THEN 'Não atualizado'
                    WHEN 1 THEN 'Confirmado'
                    WHEN 2 THEN 'CANCELADO'
                    ELSE 'Não atualizado'
                END AS opcao,
                DATE_TRUNC ('SECOND', data_resposta) AS data_resposta
           FROM arquivo_acao1_dados
          WHERE posto  = $1
            AND pedido = $2
          ORDER BY pedido, descricao
            "
    );

    $pedidos = array();

    $resPedido = pg_execute($con, 'pedidos_einhell', [$login_posto]);

    while ($rowPedido = pg_fetch_assoc($resPedido)) {
        $pedido = $rowPedido['pedido'];
        $rowPedido['valor_total'] = number_format($rowPedido['valor_total'], 2, ',' , '.');
        $pedidos[$pedido] = $rowPedido;

        // Processar as peças, criar links e botões
        $PECAS     = pg_fetch_all(pg_execute('itens_einhell', [$login_posto, $pedido]));
        $Pecas     = [];
        $atendidas = 0;

        foreach ($PECAS as $Peca) {
            $acoes = [];
            extract($Peca, EXTR_PREFIX_ALL, 'PC');

            if ($PC_opcao == 'Não atualizado' or $PC_opcao == 'CANCELADO') {
                $acoes[0] = "<button class='btn btn-default' type='button' data-pedido='$pedido' data-peca='$PC_peca' title='Confirmar pedido da peça'  data-action='confirmar'><i class='glyphicon glyphicon-ok'></i></button>";
            }
            if ($PC_opcao == 'Não atualizado' or $PC_opcao == 'Confirmado') {
                $acoes[1] = "<button class='btn btn-danger' type='button' data-pedido='$pedido' data-peca='$PC_peca' title='Cancelar o pedido da peça' data-action='cancelar'><i class='glyphicon glyphicon-white glyphicon-remove'></i></button>";
            }

            if ($PC_status == 't') {
                // unset($acoes);
                $PC_opcao = '<span class="label label-primary">' . $PC_opcao . '</span>';
                $acoes    = ['<span class="label label-success">Peça atendida</span>'];
                $atendidas++;
            }

            $Pecas[$PC_id] = array(
                "Ref."          => $PC_referencia,
                "Peça"          => $PC_descricao,
                "Qtde. Pedida"  => $PC_qtde,
                "Situação"      => $PC_opcao,
                "Data Atualiz." => $PC_data_resposta,
                "Ações"         => implode("\n",$acoes)
            );
        }
        if ($atendidas < count($Pecas))
            $pedidos[$pedido]['pecas'] = $Pecas;
        // pre_echo ($pedidos, 'PD', true);
    }
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
		'link' => APP_DIR . 'login.php',
		'icon' => APP_DIR . 'imagens/icone_telecontrol_branco.png',
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

// pre_echo($pedidos, 'Pedidos', true);

?>
    </div>
    <div class="container">
        <div class="page-header">
            <h2>Pedidos Faturados
                <small><br />Informe sua op&ccedil;&atilde;o para pedidos e pe&ccedil;as</small>
            </h2>
            <em class="text text-default">
                Também poderá acompanhar a situação dos pedidos confirmados, peça por peça, conforme
                sejam atendidos pelo Distribuidor.
            </em>
            <h4><b>Pedidos não confirmados até o dia 07/03/2018 serão cancelados automaticamente</b></h4>
            <p></p>
            <p>Caso tenha dúvidas, por favor entre em contato com <strong><em>SAC Einhell</em></strong>
            pelo telefone <strong><code>0800-7187825</code></strong>.</p>
        </div>
    <?php
    foreach ($pedidos as $pedido => $PEDIDO) {
        $Pecas = $PEDIDO['pecas'];
    ?>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">N&uacute;m. Pedido: <span class="text text-primary"><?=$pedido?></span></h3>
          </div>
          <div class="panel-body">
    <?=$tabela = Convert(array2table($Pecas), 'HTML-ENTITIES', 'Latin1')?>
          </div>
    <?php
        $countPecasPedido = count($Pecas);
        $contaAtendidas = 0;
        str_replace('atendida</span>', '', $tabela, $contaAtendidas);

        if ($countPecasPedido == $contaAtendidas) {
            echo '    </div>'.PHP_EOL;
            continue;
        }
    ?>
          <div class="panel-footer">
          <button data-pedido="<?=$pedido?>" data-action="confirmar"
                        class="btn btn-primary">CONFIRMAR o Pedido</button>
          <button data-pedido="<?=$pedido?>" data-action="cancelar"
                        class="btn btn-danger">Cancelar o Pedido</button>
          <button data-pedido="<?=$pedido?>" data-action="redefinir"
                        class="btn btn-default">Redefinir Pedido</button>
          </div>
        </div>
    <?php } ?>
    </div>
    <script>
    $(function() {
        $('button').click(function() {
            var options = $(this).data();
            var btn     = this;
            options.codigo_posto = '<?=$codigo_posto?>';
            options.token = '<?=$token?>';

            $.post('ajax_confirma_pedido.php', options, function(resp) {
                var response = resp.split('|');
                var res = response[0];
                if (res === 'Registros atualizados') {
                    document.location.reload();
                }
                if (res.indexOf('ERRO:') > -1) {
                    alert(res);
                    return true;
                }

                var data_atualiz = response[1];
                if (options.peca !== undefined) {
                    var acao = options.action == 'confirmar' ? 'Confirmado' : 'Cancelado';
                    $(btn).parent().find('button').show().end();
                    $(btn).hide();
                    $(btn).parents('tr').find('td:nth-of-type(5)').text(data_atualiz);
                    $(btn).parents('tr').find('td:nth-of-type(4)').text(acao);
                }
            });
        });
    });
    </script>
