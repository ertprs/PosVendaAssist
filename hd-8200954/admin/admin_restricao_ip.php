<?php
include_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$admin_privilegios = 'gerencia';
include_once 'autentica_admin.php';

include_once '../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

$title       = 'Gerenciamento de Restrição por IP';
$layout_menu = 'gerencia';

$IpMaskRegEx = '^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|[1-2][0-9]|3[0-2]))?$';

$tableAttrs = array(
    'tableAttrs' => 'data-toggle="table" class="table table-fixed table-bordered table-hover table-striped "',
    'headerAttrs' => 'class="titulo_coluna"',
);

if (count(array_filter($_REQUEST))) {
    // pre_echo($_POST);

    // processa
    if ($_POST['gravar'] == 'admin') {
        $sqlON = "
        UPDATE tbl_admin
           SET parametros_adicionais = JSONB_SET(
                   COALESCE(parametros_adicionais::JSONB, '{}'::JSONB),
                   '{restricao_ip}',
                   '\"t\"'
               )::TEXT
         WHERE " .
         sql_where([
            'admin' => $_POST['admin'],
            'fabrica' => $login_fabrica
        ]);

        // Limpa a restrição de IPs de todos os que a tiverem
        pg_query(
            $con,
            "UPDATE tbl_admin
                SET parametros_adicionais = parametros_adicionais::JSONB-'restricao_ip'
              WHERE fabrica = $login_fabrica
                AND parametros_adicionais::JSONB ? 'restricao_ip'"
        );

        $resON = pg_query($con, $sqlON);
        if (!pg_last_error($con)) {
            $c = pg_affected_rows($resON);
            $msg[] = "Atualização de <b>$c</b> usuários restritos executada corretamente!";
        } else {
            $msg_erro['msg'] = $_serverEnvironment=='development'
                ? $sqlON
                : 'Erro ao atualizar as resições de IP.';
        }
    }

    if ($_POST['new_ip']) {
        $new_ip = $_POST['new_ip'];
        if (preg_match("/$IpMaskRegEx/", $new_ip)) {
            $sqlValida = pg_fetch_result(
                pg_query(
                    $con,
                    "SELECT ip_address FROM tbl_ip_lista WHERE ip_address = '$new_ip'"
                ), 0, 0
            );

            if ($sqlValida === false) {
                $sqlIns = sql_cmd(
                    'tbl_ip_lista', [
                        'fabrica'      => $login_fabrica,
                        'tipo_ip'      => 'whitelist',
                        'ip_address'   => $new_ip,
                    ]
                );
                $resIns = pg_query($con, $sqlIns);

                if (pg_affected_rows($res) === 1 and pg_last_error($con) == '') {
                    $msg[] = (strpos($new_ip, '/')>0 ? "Faixa de IPs" : "IP").
                        " cadastrada corretamente!";
                } else {
                    $msg_erro['msg'][] = 'Erro ao gravar a IP ou faixa de IPs! ';//.pg_last_error($con)
                }
            } else {
                $msg_erro['msg'][] = 'IP ou faixa de IP já cadastrada!';
            }
        } else {
            $msg_erro['msg'][] = 'IP ou faixa de IP inválida!';
        }
    }

    if ($_POST['ajax'] == 'sim') {
        switch ($_POST['acao']) {
        case 'Desativar':
            $ip_lista = (int)getPost('id');
            $ativo    = false;
            break;

        case 'Ativar':
            $ip_lista = (int)getPost('id');
            $ativo    = true;
            break;

        case 'Excluir':
            $ip_lista = (int)getPost('id');
            $sql = sql_cmd(
                'tbl_ip_lista',
                'delete', [
                    'ip_lista' => $ip_lista,
                    'fabrica' => $login_fabrica
                ]
            );
            break;

        default:
            die('');

        }
        $fabrica  = $login_fabrica;

        if (is_bool($ativo)) {
            $sql = sql_cmd(
                'tbl_ip_lista',
                ['ativo' => $ativo],
                compact('ip_lista', 'fabrica')
            );
        }

        $res = pg_query($con, $sql);
        if (!is_resource($res)) die ('Erro ao atualizar o registro!');
        if (!pg_affected_rows($res)) die ("Nenhum registro alterado!");

        die ('ok');
    }
}

$form = [
    'gerenciaadmin' => [
        'span'   => 4,
        'width'  => 11,
        'type'   => 'input/submit',
        'class'  => 'btn',
        'extra' => [
            'value'  => 'Gerenciar Usuários',
        ]
    ],
    'gerenciaip' => [
        'span'   => 4,
        'width'  => 11,
        'type'   => 'input/submit',
        'class'  => 'btn',
        'extra' => [
            'value'  => 'Gerenciar IPs',
        ]
    ],
];

include_once 'cabecalho_new.php';

if (count(array_filter($_REQUEST))) {
    $plugins = ['dataTable', 'shadowbox'];
    include_once 'plugin_loader.php';
}
?>
    <style>
    table.table.ips tr td:first-child {text-align: right}
    </style>
    <div class="container">
<?php if (count($msg_erro["msg"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
        </div>
<?php } ?>
<?php if (count($msg) > 0) { ?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg)?></h4>
        </div>
<?php } ?>
        <form name='frm_cancela_os' method='POST' align='center' class='form-search form-inline tc_formulario'>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>
            <? echo montaForm($form, []);?>
            <br/>
        </form>
<?php
if ($_REQUEST['gerenciaadmin'] == 'Gerenciar Usuários') {
    $sql = "
        SELECT admin
		     , email
		     , privilegios
		     , nome_completo
		     , parametros_adicionais::JSON->'restricao_ip' AS restricao_ip
          FROM tbl_admin
         WHERE fabrica = $login_fabrica
           AND ativo  IS TRUE
         ORDER BY data_input, nome_completo";

    $res = pg_query($con, $sql);
    $admins = array_group(pg_fetch_all($res), 'admin');

    foreach($admins as $admin => $informacao) {
        $info = reset($informacao);
        // pre_echo($info, "USER: $admin", true);
        $checked = $info['restricao_ip'] ? 'checked' : '';
        $tabela[] = [
            'Login' => "<label for='adm_$admin'>{$info['email']}</label>",
            'Nome'  => "<label for='adm_$admin'>{$info['nome_completo']}</label>",
            'Restrito?' => "<input id='adm_$admin' class='input-large' $checked type='checkbox' name='admin[]' value='$admin'>"
        ];

//         $inputs .= <<<CHECK
//         <div class="span4">
//             <div class="control-group">
//                 <div class="controls">
//                     <label class="checkbox" for="adm_$admin" title="{$info['nome_completo']}">
//                         {$info['nome_completo']}<br/><span class="text muted">{$info['email']}</span>
//                     </label>
//                 </div>
//             </div>
//         </div>
// CHECK;
    }
	// pre_echo($tabela, 'ADMINs', (bool)count($tabela));
?>
        <form name="restricao_admin" class="form-inline tc_formulario" method="POST" align="center">
            <div class="titulo_tabela">Usuários com acesso restrito por IP</div>
            <p>&nbsp;</p>
            <!-- <div class="row offset1"><?=$inputs?></div> -->
            <div class="container"><?=array2table($tabela)?></div>
            <p>&nbsp;</p>
            <div class="row">
                <div class="span4 offset2">
                    <button id="adminClear" class="btn btn-warning" type="button">Desmarcar todos</button>
                </div>
                <div class="span4">
                    <button id="setAdmin" name="gravar" class="btn btn-default" value="admin">Atualizar</button>
                </div>
                <p>&nbsp;</p>
            </div>
            <p>&nbsp;</p>
            <div style="display:none" id="checks" />
        </form>
<?php }

    if (!count($_REQUEST) or $_REQUEST['gerenciaip'] == 'Gerenciar IPs') {
        $i = 0;
        $sql = "SELECT ip_lista, tipo_ip, ip_address, ativo
                  FROM tbl_ip_lista
                 WHERE fabrica = $login_fabrica
                   AND tipo_ip = 'whitelist'
              ORDER BY data_input, ip_address";
        $IPs = pg_fetch_all(pg_query($con, $sql));

        $btnDesAtivar = '<span class="%1$s btn btn-mini btn-%3$s" data-id="%2$s">%1$s</span>';
        $btnAtivo     = "<span class='label label-success'>Ativa</span>&nbsp;";
        $btnInativo   = "<span class='label label-danger'>Inativa</span>&nbsp;";

        foreach ($IPs as $i => $IP) {
            $id = $IP['ip_lista'];
            $ip = $IP['ip_address'];
            $situacao = $IP['ativo'] == 't'
                ? sprintf($btnDesAtivar, 'Desativar', $id, 'default')
                : sprintf($btnDesAtivar, 'Ativar', $id, 'default');

            $tabela[] = [
                '#' => $i + 1,
                "IP/máscara" => $ip,
                'Ativo' => $IP['ativo'] == 't' ? $btnAtivo : $btnInativo,
                'Ações' => "<div class='btn-group'>" .
                    $situacao .
                    sprintf($btnDesAtivar, 'Excluir', $id, 'danger').
                    '</div>'
            ];
        }
        $insTable[] = [
            '#' => '&ndash;',
            'IP/máscara' => '<input type="text" placeholder="10.0.0.1/16" pattern="'.$IpMaskRegEx.'" name="new_ip" />',
            'Ações' => '<button id="gravar_ip" class="Gravar btn btn-default btn-small">Gravar</button>'
        ];

?>
        <form name="form_ips" class="form-inline tc_formulario" method="POST" align="center">
            <div class='titulo_tabela'>Manutenção de IPs permitidos</div>
            <p>&nbsp;</p>
            <div class="row">
                <div class="span5 offset3">
                    <?=array2table($insTable)?>
                </div>
            </div>
            <p>&nbsp;</p>
            <div class="row">
                <div class="span5 offset3">
                    <?php
                    $tableAttrs = array(
                        'tableAttrs' => 'data-toggle="table" class="table ips table-fixed table-bordered table-hover table-striped "',
                        'headerAttrs' => 'class="titulo_coluna"',
                    );
                    echo array2table($tabela)?>
                    <p>&nbsp;</p>
                </div>
            </div>
            <br/>
        </form>
    </div>
<?php } ?>
<script>
    $(function() {
        $('#adminClear').click(function() {
            $(".row.offset1 input:checkbox").removeAttr('checked');
        });

        $(".btn.Gravar").click(function() {
            if (document.forms['form_ips'].checkValidity() === false) {
                return false;
            }
            this.form.submit();
        });

        $("#setAdmin").click(function() {
            var table = $('#DataTables_Table_0').DataTable();
            $("#checks").append(table.$("input:checked"));
        });

        $(".btn.Excluir,.btn.Desativar,.btn.Ativar").click(function() {
            var postData = {id: $(this).data('id'), ajax: 'sim'};
            postData.acao = $(this).text().trim();

            if (postData.acao == 'Excluir') {
                var confirma = confirm("Confirma a EXCLUSÃO deste registro?");
                if (!confirma) {
                    return true;
                }
            }

            $.post(
                document.location.pathname,
                postData,
                function(ret) {
                    if (ret == 'ok') {
                        document.location.reload();
                    } else {
                        alert(ret);
                        return false;
                    }
                }
            )
        });
    });
<?php if (isset($tabela) and count($tabela) > 40): ?>
    $.dataTableLoad('.table.ips');
<?php endif; ?>
</script>
<?php
include_once 'rodape.php';

