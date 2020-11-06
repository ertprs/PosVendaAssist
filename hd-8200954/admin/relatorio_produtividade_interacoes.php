<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUTIVIDADE DE INTERAÇÕES";

if (filter_input(INPUT_POST,'btn_acao')) {
    $data_inicial   = filter_input(INPUT_POST,'data_inicial');
    $data_final     = filter_input(INPUT_POST,'data_final');
    $atendente      = filter_input(INPUT_POST,'atendente');

    if (!strlen($data_inicial) || !strlen($data_final)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

        }
    }

    if (!empty($atendente)) {
        $whereAt = "
            AND tbl_admin.admin = $atendente
        ";
    } else {
        $whereAt = "
            AND tbl_admin.parametros_adicionais::JSON->>'sacTelecontrol' = 'true'
            AND tbl_admin.parametros_adicionais <> 'f'
        ";
    }

    $sql = "
        WITH count_os AS (
            SELECT  tbl_admin.admin,
                    COUNT(tbl_os_interacao) AS total
            FROM    tbl_os_interacao
            JOIN    tbl_admin USING(admin,fabrica)
            WHERE   tbl_os_interacao.fabrica = $login_fabrica
            AND     tbl_admin.ativo IS TRUE
            $whereAt
            AND     tbl_os_interacao.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
      GROUP BY      tbl_admin.admin, 
                    tbl_admin.nome_completo
      ORDER BY      tbl_admin.nome_completo
        ),
        count_pedidos AS (
            SELECT  tbl_admin.admin,
                    count(tbl_interacao) AS total
            FROM    tbl_interacao
            JOIN    tbl_admin USING(admin,fabrica)
            WHERE   tbl_interacao.fabrica = $login_fabrica
            AND     tbl_admin.ativo IS TRUE
            $whereAt
            AND     tbl_interacao.contexto = 2
            AND     tbl_interacao.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
      GROUP BY      tbl_admin.admin, tbl_admin.nome_completo
      ORDER BY      tbl_admin.nome_completo
        )
            
        SELECT  tbl_admin.nome_completo, 
                count_os.total                  AS total_int_os,
                COALESCE(count_pedidos.total,0) AS total_int_pedido
        FROM    count_os
   LEFT JOIN    count_pedidos ON count_os.admin = count_pedidos.admin
        JOIN    tbl_admin ON tbl_admin.admin    = count_os.admin    
    ";
    $resSubmit = pg_query($con,$sql);
}

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

?>
<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));

    });
</script>
<div class="container">

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio_interacoes' MEthOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>

        <br />

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="data_inicial" name="data_inicial" class='span12' maxlength="20" value="<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="data_final" name="data_final" class='span12' value="<?=$data_final?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='atendente'>Atendente</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <select id="atendente" name="atendente">
                                <option value="">SELECIONE</option>
<?php
    $sqlAtendentes = "
        SELECT  tbl_admin.admin,
                UPPER(tbl_admin.nome_completo) AS nome_completo
        FROM    tbl_admin
        WHERE   tbl_admin.ativo IS TRUE
        AND     tbl_admin.fabrica = $login_fabrica
        AND     tbl_admin.parametros_adicionais::JSON->>'sacTelecontrol' = 'true'
        AND     tbl_admin.parametros_adicionais <> 'f'
  ORDER BY      tbl_admin.nome_completo 
    ";
    $resAtendentes = pg_query($con,$sqlAtendentes);

    while ($atendentes = pg_fetch_object($resAtendentes)) {
?>
                                <option value="<?=$atendentes->admin?>" <?=($atendentes->admin == $atendente) ? "selected" : ""?>><?=$atendentes->nome_completo?></option>
<?php
    }
?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p>
            <br/>
            <button class='btn' id="btn_acao" type="button"  onclick=" submitForm($(this).parents('form'),'pesquisar');">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>

        <br/>
    </form>
</div>

<?php

if (filter_input(INPUT_POST,"btn_acao")) {
    if (strlen ($msg_erro["msg"]) == 0 && pg_num_rows($resSubmit) > 0) {
        $thead = "ATENDENTE;INTERAÇÃO OS;INTERAÇÃO PEDIDO;TOTAL".PHP_EOL;
?>
<table id="resultado_atendimentos" class = 'table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class = 'titulo_coluna'>
            <th>Atendente</th>
            <th>Interações OS</th>
            <th>Interações Pedido</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
<?php
    while ($interacoes = pg_fetch_object($resSubmit)) {
        $soma = $interacoes->total_int_os + $interacoes->total_int_pedido;
?>
        <tr>
            <td><?=$interacoes->nome_completo?></td>
            <td><?=$interacoes->total_int_os?></td>
            <td><?=$interacoes->total_int_pedido?></td>
            <td><?=$soma?></td>
        </tr>
<?php
        $tbody .= $interacoes->nome_completo.";".$interacoes->total_int_os.";".$interacoes->total_int_pedido.";".$soma.PHP_EOL;
    }

    $fp = fopen("xls/relatorio_produtividade.csv","w");

    fputs($fp,$thead);
    fputs($fp,$tbody);

    fclose($fp);

?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:center;">
                <a href="xls/relatorio_produtividade.csv" target="_BLANK" role="button" class="btn btn-success"><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' />Gerar Planilha</a>
            </td>
        </tr>
    </tfoot>
</table>
<?php
    } else {
?>
        <div class="container">
            <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
            </div>
        </div>
<?php
    }
}
?>
<?php
include 'rodape.php';
?>