<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

include_once 'class/AuditorLog.php';
$login_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($login_cookie);

$login_fabrica = $cookie_login['cook_fabrica'];

header('Content-Type: text/html; charset=iso-8859-1');

$title = "RELATÓRIO DE LOG DE ALTERAÇÃO";
define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

function formatArrayItem($key, $val, $useHtml=false) {
    if (in_array($val, array('t', 'true', true), true)) {
        $val = $useHtml ? '<i class="icon-ok"></i>':'Sim';
    } else if (in_array($val, array('f', 'false', false), true)) {
        $val = $useHtml ? '<i class="icon-remove"></i>':'Não';
    // } else if (strpos($key, 'fone') || strpos($key, 'celu') !== false) {
    //     $val = phone_format($val);
    } else if ($useHtml and is_url($val)) {
        $val = createHTMLLink($val, $val, "target='_blank' class='azul_dn'");
    } else if ($useHtml and is_email($val)) {
        $val = "<a href='mailto:$val' target='_new'><i class='icon-envelope'></i> $val</a>";
    } else if (in_array($val, array(null, 'null','NULL'), true))  // Os 'null' viram traço, pra não ficarem em branco
        $val = ' &mdash; ';
    else if ($val === '')
        $val = '<i>&lt;vazio&gt;</i>';
    return $val;
}

$tabela = preg_match('/^\w+$/', $_GET['parametro']) ? $_GET['parametro'] : null;
$id     = preg_match('/^\d+$/', $_GET['id']) ? $_GET['id'] : null;
$titulo = preg_match('/^[^<>\/\$]*$/', $_GET['titulo']) ? $_GET['titulo'] : null;

$plugins = array(
    "dataTable"
);

$IPdev = array('novodevel' => '191.5.166.42','devel' => '54.232.125.171', 'localhost' => '127.0.0.1', 'telecontrol' => '191.5.166.42');

$LOG_template = array(
    'ext' => '<div class="fields %2$s">
    <span class="span" style="display:none"></span>%1$s
    </div>',
    'int' => "<div class='span11 dl-item'>" .
        "<strong class='span4'>%s:</strong>" .
        "<span class='span8'>%s</span>".
        "</div>\n",
    'CSS' => '.fields {
      background: white;
    }
    .dl-item:nth-child(2n) {
      background: whitesmoke;
    }
    tr>th+th+th {width: 40%}'
);

/***************************************************************
 * Este array configura as regras para relatórios específicos: *
 * - Campos a serem ignorados                                  *
 * - `include` a serem executados futuramente                  *
 * - templates específicos do log (`$LOG_template`)            *
 * - etc.                                                      *
 ***************************************************************/
$configLog = array(
    'tbl_posto' => array(
        'sql' => array(
            'nome' => array(
                'sql' => "SELECT nome AS nome FROM tbl_posto WHERE fabrica = $1 and posto = $2",
                'filtro' => array('login_fabrica', 'val') // 'val' é o valor do campo com o nome da chave, neste caso 'tipo_posto'
            ),
            'ie' => array(
                'sql' => "SELECT ie AS ie FROM tbl_posto WHERE fabrica = $1 and posto = $2",
                'filtro' => array('login_fabrica', 'val') // 'val' é o valor do campo com o nome da chave, neste caso 'tipo_posto'
            )
        )
    ),
    'tbl_posto_fabrica' => array(
        'ignorar' => array('data_alteracao', 'admin'),
        'sql' => array(
            'tipo_posto' => array(
                'sql' => "SELECT descricao AS tipo_posto FROM tbl_tipo_posto WHERE fabrica = $1 and tipo_posto = $2",
                'filtro' => array('login_fabrica', 'val') // 'val' é o valor do campo com o nome da chave, neste caso 'tipo_posto'
            ),
            'admin_sap' => array(
                'sql' => "SELECT nome_completo FROM tbl_admin WHERE fabrica = $1 AND admin = $2",
                'filtro' => array('login_fabrica', 'val')
            )
        ),
        'join' => array('tbl_posto_linha')
    ),
    'tbl_pedido' => array(
        'join' => array('tbl_pedido_item'),
        'sql' => array(
            'tipo_pedido' => "SELECT codigo || ' - ' || descricao AS tipo_pedido FROM tbl_tipo_pedido WHERE tipo_pedido = $1"
        ),
    ),
    'tbl_os' => array(
        'ignorar' => array('data_modificacao'),
        'join' => array('tbl_os_produto','tbl_os_item','tbl_os_extra'),
        'sql' => array(
            'defeito_reclamado' => array(
                'sql' => "SELECT defeito_reclamado || ' - ' || descricao AS defeito_reclamado FROM tbl_defeito_reclamado WHERE defeito_reclamado = $1",
                'filtro' => array('val')
            ),
            'defeito_constatado' => array(
                'sql' => "SELECT defeito_constatado || ' - ' || descricao AS defeito_constatado FROM tbl_defeito_constatado WHERE defeito_constatado = $1",
                'filtro' => array('val')
            ),
            'servico_realizado' => array(
                'sql' => "SELECT servico_realizado || ' - ' || descricao AS servico_realizado FROM tbl_servico_realizado WHERE servico_realizado = $1",
                'filtro' => array('val')
            ),
            'tipo_atendimento' => array(
                'sql' => "SELECT tipo_atendimento || ' - ' || descricao AS tipo_atendimento FROM tbl_tipo_atendimento WHERE tipo_atendimento = $1",
                'filtro' => array('val')
            ),
            'tecnico' => array(
                'sql' => "SELECT tecnico || ' - ' || nome AS tecnico FROM tbl_tecnico WHERE tecnico = $1",
                'filtro' => array('val')
            ),
            'produto' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS produto FROM tbl_produto WHERE produto = $1",
                'filtro' => array('val')
            ),
            'status_checkpoint' => array(
                'sql' => "SELECT status_checkpoint || ' - ' || descricao AS status_checkpoint FROM tbl_status_checkpoint WHERE status_checkpoint = $1",
                'filtro' => array('val')
            ),
            'peca' => array(
                'sql' => "SELECT referencia || ' - ' || descricao AS peca FROM tbl_peca WHERE peca = $1",
                'filtro' => array('val')
            ),
            'revenda' => array(
                'sql' => "SELECT revenda || ' - ' || nome AS revenda FROM tbl_revenda WHERE revenda = $1",
                'filtro' => array('val')
            ),
            'pac' => array(
                'sql' => "SELECT CASE
                            WHEN pac = 'sem_rastreio' THEN 'Correios: Sem código de rastreio'
                            WHEN pac ~ 'balc.o'       THEN 'Retirada no balcão'
                            WHEN pac <> ''            THEN 'Correios: ' || pac
                            ELSE ''
                        END AS tipo_entrega FROM (SELECT $1::TEXT AS pac) AS os_extra",
                'filtro' => array('val'),
            )
        ),
    ),
);

try {
    $mostraTeste = isset($_serverEnvironment) and $_serverEnvironment == 'development';

    $AuditorLog = new AuditorLog;
    $tabelas = (isset($configLog[$tabela]['join'])) ?
        array_merge((array)$tabela, $configLog[$tabela]['join']):
        $tabelas = $tabela;

    $res = $AuditorLog->getLog($tabelas, $login_fabrica."*".$id, 50);

    if (!is_array($res)) {
        throw new Exception("Nenhum registro de LOG encontrado");
    }

    if (!array_key_exists(0, $res)) {
        $res = array(0 => array('data' => $res));
    }

    pg_prepare($con, 'nomeAdmin', "SELECT login AS nome, nome_completo FROM tbl_admin WHERE fabrica = $1 AND admin = $2");
    pg_prepare($con, 'nomePosto', "SELECT nome, fantasia AS nome_completo FROM tbl_posto WHERE posto = $1");
    pg_prepare($con, 'nomeLoginUnico', "SELECT nome AS nome_completo FROM tbl_login_unico WHERE login_unico = $1");

    if (array_key_exists($tabela, $configLog))
        extract($configLog[$tabela], EXTR_PREFIX_ALL, 'LOG');

    // Prepara as consultas para usar depois
    if (isset($LOG_sql) and is_array($LOG_sql)) {
        foreach ($LOG_sql as $queryName => $queryStr):

            if (!pg_prepare($con, $queryName, $queryStr['sql']))
                throw new Exception(
                    "Erro na definição da consulta:<br />".
                    "<h3>$queryName</h3>".
                    "<code>$queryStr</code>".
                    "<h3>Erro:</h3><pre>".pg_last_error($con).'</pre>'
                );
        endforeach;
        $LOG_sql_keys = array_keys($LOG_sql);
    }

    $dataTable = array();

    foreach ($res as $idx => $data) {
        //$data = $rec['data'];

        // Se o IP do registro é de desenvolvimento, mas o acesso é desde o ambiente de
        // produção, não mostra o registro.

        $RegistroTeste = (substr($data['ip_access'], 0, 7) == '192.168' or in_array($data['ip_access'], $IPdev));

        if ($RegistroTeste and !$mostraTeste)
            continue;

        //$extraStyle = $develSrcData = $RegistroTeste ? ' alert' : '';

        $userType = $data['user_level'];

        if ($userType == 'login_unico') {
            $resUserName = pg_execute($con, 'nomeLoginUnico', (array)$data['user']);
        }

        if ($userType == 'posto') {
            $resUserName = pg_execute($con, 'nomePosto', (array)$data['user']);
        }
 
        if ($userType == 'admin') {
            $resUserName = pg_execute($con, 'nomeAdmin', array($login_fabrica, $data['user']));
        }

        list($login, $nome) = pg_fetch_array($resUserName);

        $Antes  = $data['content']['antes'];
        $Depois = $data['content']['depois'];

        // Unifica o formato do array: quando tem apenas um registro, a API não envolve o registro
        // dentro de um array, deixando os dados do registro no primeiro nível. Aqui adicionamos
        // um nível para usar apenas uma lógica de processamento.
        if (!array_key_exists(0, $Antes) && count($Antes)) {
            $Antes = array(0 => $Antes);
        }
        if (!array_key_exists(0, $Depois) && count($Depois)) {
            $Depois = array(0 => $Depois);
        }

        $campo_tabela = str_replace('tbl_', '', $tabela);
        msort($Antes, $campo_tabela); msort($Depois, $campo_tabela);

        $dadosLog = AuditorLog::verificaLog($Antes, $Depois, $LOG_ignorar);

        if (isset($LOG_include) and !is_null($LOG_include)) {
            foreach ((array)$LOG_include as $filename) {
                include (__DIR__ . DIRECTORY_SEPARATOR . $filename);
            }
        }

        if (!is_array($dadosLog))
            continue;

        $dataTable[$idx] = array(
            'Usuário / Nome' => $nome ? "$login ($nome)" : $login,
            'Data / Horário' => is_date($data['created'], 'U', 'EUR')
        );
        $dadosAntes = $dadosDepois = '';

        $Inserido = $Excluido = 0;

        if ($dadosLog['antes'][key($dadosLog['antes'])] == null) {
            $Inserido = 1;
        }
        if ($dadosLog['depois'][key($dadosLog['depois'])] == null) {
            $Excluido = 1;
        }

        if($Inserido !== 1){
            foreach ($dadosLog['antes'] as $logItens) {
                foreach ($logItens as $key=>$val) {

                    // Se tem um SELECT para o campo, executa e altera o valor
                    if ($LOG_sql_keys and in_array($key, $LOG_sql_keys)) {
                        $val = pg_fetch_result(
                            pg_execute(
                                $con, $key,
                                compact($LOG_sql[$key]['filtro'])
                            ), 0, 0
                        ) ? : $val;
                    }

                    $key = ucfirst(str_replace('_', ' ', $key));
                    $val = formatArrayItem($key, $val);
                    $dadosAntes .= sprintf($LOG_template['int'], $key, $val);
                    if ($Excluido == 1) {
                        $val = formatArrayItem($key, "");
                        $dadosDepois .= sprintf($LOG_template['int'], $key, $val);
                    }
                }
            }
        }

        if ($Excluido !== 1) {
            foreach ($dadosLog['depois'] as $logItens) {

                foreach ($logItens as $key=>$val) {
                    // Se tem um SELECT para o campo, executa e altera o valor
                    if ($LOG_sql_keys and in_array($key, $LOG_sql_keys)) {
                        $val = pg_fetch_result(
                            pg_execute(
                                $con, $key,
                                compact($LOG_sql[$key]['filtro'])
                            ), 0, 0
                        ) ? : $val;
                    }

                    $key = ucfirst(str_replace('_', ' ', $key));
                    $val = formatArrayItem($key, $val);
                    $dadosDepois .= sprintf($LOG_template['int'], $key, $val);
                    if ($Inserido == 1) {
                        $val = formatArrayItem($key, "");
                        $dadosAntes .= sprintf($LOG_template['int'], $key, $val);
                    }
                }
            }
        }

        $dadosAntes = sprintf($LOG_template['ext'], $dadosAntes, $extraStyle);
        $dadosDepois = sprintf($LOG_template['ext'], $dadosDepois, $extraStyle);

        if ($Inserido == 1 || $Excluido == 1) {
            if ($Inserido == 1) {
                //$dataTable[$idx]['Antes']  = "<p class='alert alert-success' style='text-align: center'><em>&lt;REGISTRO CADASTRADO&gt;</em></p>";
                $dataTable[$idx]['Antes'] = $dadosAntes;
                $dataTable[$idx]['Depois'] = $dadosDepois;
            }else{
                $dataTable[$idx]['Antes'] = $dadosAntes;
                $dataTable[$idx]['Depois'] = $dadosDepois;
                //$dataTable[$idx]['Depois'] = "<p class='alert alert-error' style='text-align: center'><em>&lt;REGISTRO EXCLUIDO&gt;</em></p>";
            }
        }else{
            $dataTable[$idx]['Antes']  = $dadosAntes;
            $dataTable[$idx]['Depois'] = $dadosDepois;
        }
    }
    if (!count($dataTable)) {
        throw new Exception("Nenhum registro de LOG encontrado");
    }
} catch (Exception $e) {
    $msg = $e->getMessage();
}

// Mostra a tabela com os resultados
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?=$title?></title>
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tooltips.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/ajuste.css" />

        <!--[if lt IE 10]>
        <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
        <![endif]-->

        <script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
        <script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
        <script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
        <script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
        <script src="<?=BI_BACK?>bootstrap/js/bootstrap.js"></script>

        <?php include("plugin_loader.php"); ?>
        <?php if (strlen($LOG_template['CSS'])): ?>
        <style>
            <?=$LOG_template['CSS']?>
        </style>
        <?php endif; ?>
    </head>
<body>
<?php if ($msg): ?>
    <div style="align-items: center; display: flex; min-height: 100%; min-height: 100vh;">
        <div class='container'>
            <div class='row-fluid'>
                <div class='span12'>
                    <div class='alert alert-warning'>
                        <h4><?=$msg?></h4>
                        Data início do LOG: 03/2017
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif;

if (count($dataTable)):
    $dataTable = array_merge(
        $dataTable,
        array(
            'attrs' => array(
                'tableAttrs' => ' class="table table-striped table-bordered table-hover table-fixed"',
                'captionAttrs' => ' class="titulo_tabela"',
                'headerAttrs' => ' class="titulo_coluna"',
            )
        )
    );
?>
    <div class="container-fluid">
        <div class="lead text-info"><?=$titulo?></div>
        <div class="row-fluid">
        <?=array2table($dataTable, 'Logs de Alteração')?>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
