<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'financeiro';

include 'autentica_admin.php';

if ($_POST['btn_acao']) {
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $posto_id     = $_POST['posto_id'];
    
    if (empty($data_inicial)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'data_inicial';
    }
    
    if (empty($data_final)) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'data_final';
    }
    
    if (!empty($data_inicial) && !empty($data_final)) {
        list($mes, $ano) = explode('/', $data_inicial);
        $data_inicial = "$ano-$mes-01";
        
        list($mes, $ano) = explode('/', $data_final);
        $data_final = date('Y-m-t', strtotime("$ano-$mes-01"));
        
        if (strtotime($data_final) < strtotime($data_inicial)) {
            $msg_erro['msg'][] = 'Data final não pode ser inferior a data inicial';
            $msg_erro['campos'][]   = 'data_inicial';
            $msg_erro['campos'][]   = 'data_final';
        } else if (strtotime($data_inicial.' +6 months') < strtotime($data_final)) {
            $msg_erro['msg'][] = 'O intervalo entre as datas não pode ser superior a 6 meses';
            $msg_erro['campos'][]   = 'data_inicial';
            $msg_erro['campos'][]   = 'data_final';
        }
    }
    
    if (!count($msg_erro['msg'])) {
        if (!empty($posto_id)) {
            $wherePosto = "AND e.posto = {$posto_id}";
        }
        
        $sql = "
            SELECT 
                e.extrato, 
                pf.codigo_posto, 
                p.nome, 
                TO_CHAR(e.data_geracao, 'MM/YYYY') AS data_geracao, 
                TO_CHAR(ep.data_pagamento, 'DD/MM/YYYY') AS data_pagamento, 
                e.total
            FROM tbl_extrato e
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = e.posto AND pf.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto p ON p.posto = pf.posto
            LEFT JOIN tbl_extrato_pagamento ep ON ep.extrato = e.extrato
            WHERE e.fabrica = {$login_fabrica}
            AND (e.data_geracao BETWEEN '{$data_inicial}' AND '{$data_final}')
            {$wherePosto}
            ORDER BY e.data_geracao ASC, p.nome ASC
        ";
        $resSubmit = pg_query($con, $sql);
        
        if (!pg_num_rows($resSubmit)) {
            $msg_erro['msg'][] = 'Nenhum resultado encontrado';
        } else {
            $extratos = array(
                'pagos'     => array(),
                'nao_pagos' => array()
            );
            
            while ($row = pg_fetch_object($resSubmit)) {
                if (empty($row->data_pagamento)) {
                    $extratos['nao_pagos'][] = (array) $row;
                } else {
                    $extratos['pagos'][] = (array) $row;
                }
            }
        }
    }
}

$layout_menu = 'financeiro';
$title       = 'Relatório de extratos pagos x pendente de pagamento';
$title_page  = 'Parâmetros de pesquisa';

include 'cabecalho_new.php';

$plugins = array(
    'shadowbox',
    'dataTable',
    'font_awesome',
    'mask',
    'datetimepickerbs2'
);
include 'plugin_loader.php';
?>

<style>
    
.dataTables_wrapper > .row {
    margin-left: 0px !important;
}
    
</style>

<?php
if (count($msg_erro['msg']) > 0) {
?>
    <div class='alert alert-error' >
        <h4><?=implode('<br />', $msg_erro['msg'])?></h4>
    </div>
<?php
}
?>

<div class='row' >
    <b class='obrigatorio pull-right' >  * Campos obrigatórios </b>
</div>

<form method='POST' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <br />

    <!--linhas-->
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span3' >
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='data_inicial' >Data inicial</label>
                <div class='controls controls-row' >
                    <div class='input-append span12 date' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='data_inicial' name='data_inicial' class='span10' value='<?=getValue("data_inicial")?>' />
                        <span class='add-on'><i class='fa fa-calendar'></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class='span3' >
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : "" ?>' >
                <label class='control-label' for='data_final' >Data final</label>
                <div class='controls controls-row' >
                    <div class='input-append span12 date' >
                        <h5 class='asteristico'>*</h5>
                        <input type='text' id='data_final' name='data_final' class='span10' value='<?=getValue("data_final")?>' />
                        <span class='add-on'><i class='fa fa-calendar'></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid' >
        <div class='span2' ></div>
        <?php
        if (strlen(getValue('posto_id')) > 0) {
            $posto_input_readonly     = 'readonly';
            $posto_span_rel           = 'trocar_posto';
            $posto_input_append_icon  = 'time';
            $posto_input_append_title = 'title="Trocar Posto"';
        } else {
            $posto_input_readonly     = '';
            $posto_span_rel           = 'lupa';
            $posto_input_append_icon  = 'search';
            $posto_input_append_title = '';
        }
        ?>
        <div class='span3' >
            <div class='control-group' >
                <label class='control-label' for='posto_codigo' >Código do Posto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input id='posto_codigo' name='posto_codigo' class='span12' type='text' value='<?=getValue("posto_codigo")?>' <?=$posto_input_readonly?> />
                        <span class='add-on' rel='<?=$posto_span_rel?>' >
                            <i class='fa fa-<?=$posto_input_append_icon?>' <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type='hidden' name='lupa_config' tipo='posto' parametro='codigo' />
                        <input type='hidden' id='posto_id' name='posto_id' value='<?=getValue("posto_id")?>' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4' >
            <div class='control-group' >
                <label class='control-label' for='posto_nome' >Nome do Posto</label>
                <div class='controls controls-row' >
                    <div class='span10 input-append' >
                        <input id='posto_nome' name='posto_nome' class='span12' type='text' value='<?=getValue("posto_nome")?>' <?=$posto_input_readonly?> />
                        <span class='add-on' rel='<?=$posto_span_rel?>' >
                            <i class='fa fa-<?=$posto_input_append_icon?>' <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type='hidden' name='lupa_config' tipo='posto' parametro='nome' />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p>
        <br />
        <input type='hidden' id='btn_click' name='btn_acao' />
        <button class='btn' type='button' onclick='submitForm($(this).parents("form"));' >Pesquisar</button>
    </p>
    <br />
</form>

<?php
if (pg_num_rows($resSubmit) > 0) {
?>
    <div class='row-fluid' >
        <div class='span12' >
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        Extratos pendente de pagamento 
                    </h3>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-hover extratos-pendente-pagamento" style='width: 100%;' >
                        <thead>
                            <tr class='titulo_coluna'>
                                <th>Extrato</th>
                                <th>Posto Autorizado</th>
                                <th>Data de Geração</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!count($extratos['nao_pagos'])) {
                            ?>
                                <tr class='error'>
                                    <td colspan='4'>Nenhum resultado encontrado</td>
                                </tr>
                            <?php
                            } else {
                                foreach ($extratos['nao_pagos'] as $extrato) {
                                ?>
                                    <tr>
                                        <td><a href='extrato_consulta_os.php?extrato=<?=$extrato['extrato']?>' target='_blank'><?=$extrato['extrato']?></a></td>
                                        <td><?=$extrato['codigo_posto']?> - <?=$extrato['nome']?></td>
                                        <td><?=$extrato['data_geracao']?></td>
                                        <td><?=number_format($extrato['total'], 2, '.', '')?></td>
                                    </tr>
                                <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid' >
        <div class='span12' >
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        Extratos pagos
                    </h3>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-hover extratos-pagos" style='width: 100%;' >
                        <thead>
                            <tr class='titulo_coluna'>
                                <th>Extrato</th>
                                <th>Posto Autorizado</th>
                                <th>Data de Geração</th>
                                <th>Data de Pagamento</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!count($extratos['pagos'])) {
                            ?>
                                <tr class='error'>
                                    <td colspan='5'>Nenhum resultado encontrado</td>
                                </tr>
                            <?php
                            } else {
                                foreach ($extratos['pagos'] as $extrato) {
                                ?>
                                    <tr>
                                        <td><a href='extrato_consulta_os.php?extrato=<?=$extrato['extrato']?>' target='_blank'><?=$extrato['extrato']?></a></td>
                                        <td><?=$extrato['codigo_posto']?> - <?=$extrato['nome']?></td>
                                        <td><?=$extrato['data_geracao']?></td>
                                        <td><?=$extrato['data_pagamento']?></td>
                                        <td><?=number_format($extrato['total'], 2, '.', '')?></td>
                                    </tr>
                                <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>

<script>

Shadowbox.init();

$('#data_inicial, #data_final').mask('99/9999').on('focus', function() {
    $(this).next('span').trigger('click');
});

$('.date').datetimepicker({
    format: 'MM/yyyy',
    viewMode: 1,
    minViewMode: 1,
    pickTime: false
}).on('changeDate', function(e) {
    $(e.target).datetimepicker('hide');
});

$(document).on('click', 'span[rel=lupa]', function() {
    $.lupa($(this));
});

$(document).on('click', 'span[rel=trocar_posto]', function() {
    $('#posto_id, #posto_codigo, #posto_nome').val('');

    $('#posto_codigo, #posto_nome')
    .prop({ readonly: false })
    .next('span[rel=trocar_posto]')
    .attr({ rel: 'lupa' })
    .find('i')
    .removeClass('fa-times')
    .addClass('fa-search')
    .removeAttr('title');
});

window.retorna_posto = function(retorno) {
    $('#posto_id').val(retorno.posto);
    $('#posto_codigo').val(retorno.codigo);
    $('#posto_nome').val(retorno.nome);

    $('#posto_codigo, #posto_nome')
    .prop({ readonly: true })
    .next('span[rel=lupa]')
    .attr({ rel: 'trocar_posto' })
    .find('i')
    .removeClass('fa-search')
    .addClass('fa-times')
    .attr({ title: 'Trocar Posto' });
}

<?php
if (count($extratos['pagos']) > 0) {
?>
    $.dataTableLoad({ 
        table: '.extratos-pagos'
    });
<?php
}

if (count($extratos['nao_pagos']) > 0) {
?>
    $.dataTableLoad({
        table: '.extratos-pendente-pagamento'
    });
<?php
}
?>

</script>

<?php
include 'rodape.php';
?>