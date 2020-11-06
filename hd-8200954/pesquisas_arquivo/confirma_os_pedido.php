<?php
define ('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
$no_pdo = true;
include_once APP_DIR . 'dbconfig.php';
include_once APP_DIR . 'includes/dbconnect-inc.php';

include_once APP_DIR . 'helpdesk/mlg_funciones.php';
include_once APP_DIR . 'class/json.class.php';
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

    $OS = pg_fetch_all(
        pg_query($con,
            "SELECT TD.os
                  , TD.sua_os
                  , TD.data_abertura
                  , TD.consumidor_nome
                  , TD.tipo_os
                  , TD.status
                  , TD.opcao
                  , TD.data_resposta
                  , TD.observacoes
                  , CURRENT_DATE - data_abertura AS aberta
               FROM arquivo_acao2_dados TD
              WHERE posto  = $login_posto
                AND status IS FALSE
              ORDER BY data_abertura, consumidor_nome"
        )
    );

    if (!$OS) throw new Exception(pg_last_error($con));

    $histBtn = "<button data-id='%s' class='btn btn-sm btn-default' rel='history' title='Ver Histórico'><i class='glyphicon glyphicon-list-alt'></i></button>"; // OS
    $contBtn = "<button data-id='%s' class='btn btn-sm btn-success' rel='confirmar' title='Manter a OS'><i class='glyphicon glyphicon-ok'></i></button>";
    $cancBtn = "<button data-id='%s' class='btn btn-sm btn-danger' rel='cancelar' title='Cancelar a OS'><i class='glyphicon glyphicon-remove'></i></button>";

    // pre_echo($OS,'Consulta',true);

    foreach ($OS as $i => $rowOS) {
        $os = $rowOS['os'];
        $comments = new Json($rowOS['observacoes']);
        $situacao = $opcoes[$rowOS['opcao']];
        $acoes = [sprintf($histBtn, $os)];

        if ($rowOS['status'] =='t') {
            $acoes[] = '<div class="label label-info">PROCESSADO</div>';
        } else {
            if ($rowOS['opcao'] != 1) // Não disse nada ou pediu para cancelar
                $acoes[] = sprintf($contBtn, $os);
            if ($rowOS['opcao'] != 2) // Não disse nada ou pediu para dar continuidade
                $acoes[] =sprintf($cancBtn, $os);
        }

        $oss[$i] = [
            'OS'          => "<span class='left-align' data-os='{$rowOS['os']}'>{$rowOS['sua_os']}</span>",
            'Abertura'    => $rowOS['data_abertura'],
            'Tipo'        => $rowOS['tipo_os'] == 'C' ? 'Consumidor':'Revenda',
            'Cliente'     => $rowOS['consumidor_nome'],
            'Dias Aberta' => $rowOS['aberta'],
            'Comentário'  => "<textarea cols='40' rows='1' data-id='$os'></textarea>",
            'Ações'       => "<div class='btn-group'>".implode('', $acoes).'</div>'
        ];
        unset ($acoes);
    }
    // pre_echo($oss, 'ARRAY PROCESSADO', true);
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

$layout_menu = "os";
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
        'layouts' => array('os'),
        'link'    => '#',
        'name'    => traduz('pedidos.faturados'),
    ),
    'Sair' => array(
        'name' => traduz('Sair'),
        'link' => 'logout_2.php',
    )
);

$tableAttrs = array(
    'tableAttrs' => 'id="os" data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
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
    #os.table th:nth-of-type(3) {text-align: right}
    #os.table td {text-align: center}
    #os.table td+td+td+td {text-align: left}
    #os.table td+td+td+td+td {text-align: right}
    #os.table td+td+td+td+td+td {text-align: center}
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

?>
    </div>
    <div class="container">
        <div class="page-header">
            <h2>Ordens de Serviço com Pendências
                <small><br />Abertas antes de Dezembro de 2017</small>
            </h2>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading">
          <h3 class="panel-title">Relação de OS para o Posto <?=$posto->nome?></h3>
          </div>
          <div class="panel-body">
          <?=$tabela = Convert(array2table($oss), 'HTML-ENTITIES', 'Latin1')?>
          </div>
    </div>
    <div id="aux" class="modal" role="dialog">
       <div class="modal-dialog" role='document'>
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title">Interações</h4>
              </div>
              <div class="modal-body"></div>
          </div>
      </div>
    </div>
    <script>
    $(function() {
        $('textarea').change(function() {
            var options = $(this).data();
            options.codigo_posto = '<?=$codigo_posto?>';
            options.token = '<?=$token?>';
            options.action = 'comment';
            options.text = $(this).val();

            // Não grava textos de menos de 3 caracteres
            if (options.text.length < 3)
                return true;

            $.post('ajax_confirma_os.php', options, function(resp) {
                var response = resp.split('|');
                var res = response[0];
                if (res === 'Texto salvo') {
                    alert ("Interação gravada com sucesso.");
                }
                if (res.indexOf('ERRO:') > -1) {
                    alert(res);
                    return true;
                }
            });
        });

        $('button').click(function() {
            var options = $(this).data();
            var btn     = this;
            options.codigo_posto = '<?=$codigo_posto?>';
            options.token = '<?=$token?>';
            options.action = $(btn).attr('rel');

            $.post('ajax_confirma_os.php', options, function(resp) {
                var response = resp.split('|');
                var res = response[0];

                if (options.action == 'history') {
                    $('#aux .modal-body').html(resp);
                    $('#aux').modal();
                }
                if (res === 'Registro atualizado') {
                    document.location.reload();
                }
                if (res.indexOf('ERRO:') > -1) {
                    alert(res);
                    return true;
                }
            });
        });
    });
    </script>
