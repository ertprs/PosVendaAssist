<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'auditoria,cadastros';

include 'autentica_admin.php';

if ($_GET["ajax_aprovar"]) {
    try {
        $posto_ferramenta = $_GET["posto_ferramenta"];
        
        if (!strlen($posto_ferramenta)) {
            throw new \Exception("Ferramenta não informada");
        }
        
        $sql = "
            SELECT posto_ferramenta FROM tbl_posto_ferramenta WHERE fabrica = {$login_fabrica} AND posto_ferramenta = {$posto_ferramenta}
        ";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception("Ferramenta inválida");
        }
        
        $sql = "UPDATE tbl_posto_ferramenta SET aprovado = CURRENT_TIMESTAMP WHERE posto_ferramenta = {$posto_ferramenta}";
        $res = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
            throw new \Exception("Erro ao aprovar ferramenta");
        }
        
        exit(json_encode(array("sucesso" => true)));
    } catch(\Exception $e) {
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_GET["ajax_reprovar"]) {
    try {
        $transaction = false;
        $posto_ferramenta = $_GET["posto_ferramenta"];
        $motivo           = utf8_decode(trim($_GET["motivo"]));
        
        if (!strlen($posto_ferramenta)) {
            throw new \Exception("Ferramenta não informada");
        }
        
        if (empty($motivo)) {
            throw new \Exception("É obrigatório informar o motivo para reprovar a ferramenta");
        }
        
        $sql = "
            SELECT * FROM tbl_posto_ferramenta WHERE fabrica = {$login_fabrica} AND posto_ferramenta = {$posto_ferramenta}
        ";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception("Ferramenta inválida");
        }
        
        $ferramenta = pg_fetch_assoc($res);
        
        $transaction = true;
        
        $sql = "UPDATE tbl_posto_ferramenta SET reprovado = CURRENT_TIMESTAMP, ativo = FALSE WHERE posto_ferramenta = {$posto_ferramenta}";
        $res = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
            throw new \Exception("Erro ao reprovar ferramenta");
        }
        
        $sql = "INSERT INTO tbl_comunicado
                (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem
                )
                VALUES
                (
                    {$login_fabrica},
                    {$ferramenta['posto']},
                    true,
                    'Com. Unico Posto',
                    true,
                    'Auditoria de Ferramenta',
                    E'Ferramenta {$ferramenta["descricao"]} reprovada pela fábrica: {$motivo}'
                )
        ";
        $res = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0) {
            throw new \Exception("Erro ao reprovar ferramenta");
        }
        
        pg_query($con, "COMMIT");
        
        exit(json_encode(array("sucesso" => true)));
    } catch(\Exception $e) {
        if ($transaction === true) {
            pg_query($con, "ROLLBACK");
        }
        
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_GET["ajax_cancelar"]) {
    try {
        $transaction      = false;
        $posto_ferramenta = $_GET["posto_ferramenta"];
        $motivo           = utf8_decode(trim($_GET["motivo"]));
        
        if (!strlen($posto_ferramenta)) {
            throw new \Exception("Ferramenta não informada");
        }
        
        if (empty($motivo)) {
            throw new \Exception("É obrigatório informar o motivo para cancelar a ferramenta");
        }
        
        $sql = "SELECT * FROM tbl_posto_ferramenta WHERE fabrica = {$login_fabrica} AND posto_ferramenta = {$posto_ferramenta}";
        $res = pg_query($con, $sql);
        
        if (!pg_num_rows($res)) {
            throw new \Exception("Ferramenta inválida");
        }
        
        $ferramenta  = pg_fetch_assoc($res);
        $transaction = true;

        $JsonCancelado = json_encode(["cancelado" => "t", "motivo" => utf8_encode($motivo), "data" => date('d/m/Y')]);
        $sql           = "UPDATE tbl_posto_ferramenta SET cancelado = '{$JsonCancelado}' WHERE posto_ferramenta = {$posto_ferramenta}";
        $res         = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) != 1) {
            throw new \Exception("Erro ao cancelar ferramenta");
        }
        
        pg_query($con, "COMMIT");
        
        exit(json_encode(array("sucesso" => true)));
    } catch(\Exception $e) {
        if ($transaction === true) {
            pg_query($con, "ROLLBACK");
        }
        
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

$wherePesquisa = array();

if ($_POST["btn_acao"]) {
    $posto_id         = $_POST["posto_id"];
    $grupo_ferramenta = $_POST["grupo_ferramenta"];
    $status           = $_POST['status'];
    
    if (!empty($posto_id)) {
        $wherePesquisa[] = "AND pf.posto = {$posto_id}";
    }
    
    if (!empty($grupo_ferramenta)) {
        $wherePesquisa[] = "AND pf.grupo_ferramenta = {$grupo_ferramenta}";
    }

    if (!empty($status)) {

        switch ($status) {
            case 'aprovado':
                $wherePesquisa[] = "AND (pf.aprovado IS NOT NULL) and pf.validade_certificado >= current_date"; 
                $wherePesquisa[] = "AND pf.ativo IS TRUE";
                if (in_array($login_fabrica, [175])) {
                    $wherePesquisa[] = "AND pf.cancelado IS NULL";
                }
                break;
            case 'reprovado':
                $wherePesquisa[] = "AND (pf.reprovado IS NOT NULL)"; 
                $wherePesquisa[] = "AND pf.ativo IS FALSE";
                if (in_array($login_fabrica, [175])) {
                    $wherePesquisa[] = "AND pf.cancelado IS NULL";
                }
                break;
            case 'vencida':
                if (in_array($login_fabrica, [175])) {
                    $wherePesquisa[] = " AND ";
                    $wherePesquisa[] = " (pf.validade_certificado < current_date AND pf.ativo IS TRUE) ";
                    $wherePesquisa[] = " OR ";
                    $wherePesquisa[] = " (pf.cancelado::jsonb->>'cancelado' = 't') ";
                } else {
                    $wherePesquisa[] = " AND pf.validade_certificado < current_date ";
                    $wherePesquisa[] = " AND pf.ativo IS TRUE ";
                }
                break;
            default:
                $wherePesquisa[] = "AND (pf.aprovado IS NULL AND pf.reprovado IS NULL)";
                $wherePesquisa[] = "AND pf.ativo IS TRUE";
                if (in_array($login_fabrica, [175])) {
                    $wherePesquisa[] = "AND pf.cancelado IS NULL";
                }
                break;
        }

    }
} else {
    $wherePesquisa[] = "AND pf.ativo IS TRUE";
}

$layout_menu = 'auditoria';
$title       = 'Ferramentas';
$title_page  = 'Parâmetros de Pesquisa';

include 'cabecalho_new.php';

$plugins = array(
    'shadowbox',
    'select2',
    'dataTable'
);
include 'plugin_loader.php';

if (count($msg_erro['msg']) > 0) {
?>
    <div class='alert alert-error' >
        <h4><?=implode('<br />', $msg_erro['msg'])?></h4>
    </div>
<?php
}
?>

<div class="alert alert-warning">
    <h4>Caso não for selecionado nenhum parâmetro de pesquisa será mostrada todas as ferramentas pendentes de auditoria</h4>
</div>

<div class='row' >
    <b class='obrigatorio pull-right' >  * Campos obrigatórios </b>
</div>

<form method='POST' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela' ><?=$title_page?></div>
    <br />

    <div class='row-fluid' >
        <div class='span2' ></div>
        <?php
        if (strlen(getValue('posto_id')) > 0) {
            $posto_input_readonly     = 'readonly';
            $posto_span_rel           = 'trocar_posto';
            $posto_input_append_icon  = 'remove';
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
                            <i class='icon-<?=$posto_input_append_icon?>' <?=$posto_input_append_title?> ></i>
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
                            <i class='icon-<?=$posto_input_append_icon?>' <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type='hidden' name='lupa_config' tipo='posto' parametro='nome' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row-fluid' >
        <div class='span2' ></div>
        <div class='span3' >
            <div class='control-group' >
                <label class='control-label' for='grupo_ferramenta' >Grupo da Ferramenta</label>
                <div class='controls controls-row' >
                    <div class='span12' >
                        <select id='grupo_ferramenta' name='grupo_ferramenta' class='span12' />
                            <option value='' >Selecione</option>
                            <?php
                            $sql = "
                                SELECT * FROM tbl_grupo_ferramenta WHERE fabrica = {$login_fabrica}
                            ";
                            $res = pg_query($con, $sql);
                            if (pg_num_rows($res) > 0) {
                                while ($row = pg_fetch_object($res)) {
                                    $selected = (getValue('grupo_ferramenta') == $row->grupo_ferramenta) ? 'selected' : '';
                                    echo "<option value='{$row->grupo_ferramenta}' {$selected} >{$row->descricao}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4' >
            <div class='control-group' >
                <label class='control-label' for='grupo_ferramenta' >Status</label>
                <div class='controls controls-row' >
                    <div class='span11' >
                        <select id='status' name='status' class='span12' />
                            <option value='aguardando' <?= ($_POST['status'] == 'aguardando' || !isset($_POST['status'])) ? 'selected' : '' ?>>Aguardando Aprovação</option>
                            <option value='aprovado' <?= ($_POST['status'] == 'aprovado') ? 'selected' : '' ?>>Aprovado</option>
                            <option value='reprovado' <?= ($_POST['status'] == 'reprovado') ? 'selected' : '' ?>>Reprovado</option>
                            <option value='vencida' <?= ($_POST['status'] == 'vencida') ? 'selected' : '' ?>>Vencida</option>
                        </select>
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
</div>

<?php

if (!isset($_POST['btn_acao'])) {
    $wherePesquisa[] = "AND (pf.aprovado IS NULL AND pf.reprovado IS NULL)";
}

$fieldCancelado     = '';

if (in_array($login_fabrica, [175])) {
    $fieldCancelado = " , pf.cancelado";
}

$sql = "
    SELECT pf.*, gf.descricao AS grupo_ferramenta_descricao, p.nome AS nome_posto, psf.codigo_posto {$fieldCancelado}
    FROM tbl_posto_ferramenta pf
    INNER JOIN tbl_grupo_ferramenta gf ON gf.grupo_ferramenta = pf.grupo_ferramenta AND gf.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto_fabrica psf ON psf.posto = pf.posto AND psf.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto p ON p.posto = psf.posto
    WHERE pf.fabrica = {$login_fabrica}
    ".implode(" ", $wherePesquisa)."
    ORDER BY pf.data_input ASC
";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
?>
    <br />
<?php if (in_array($login_fabrica, [175])) { ?> 
    <div class="container2 tc_container" id="id_tc_container">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
<?php } ?>

    <table id="<?= (in_array($login_fabrica, [175])) ? 'dataTable' : ''; ?>" class='table table-striped table-bordered table-hover table-normal table-center' >
        <thead>
            <tr class='warning' >
                <th colspan='11' >Ferramentas aguardando auditoria</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Descrição</th>
                <th>Grupo da Ferramenta</th>
                <th>Posto Autorizado</th>
                <th>Fabricante</th>
                <th>Modelo</th>
                <th>Número de Série</th>
                <th>Certificado</th>
                <th>Validade do Certificado</th>
                <th>Status</th>
                <th>Anexo(s)</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($res)) {

                $isCancelado       = false; 

                if (in_array($login_fabrica, [175])) {
                    $JsonCancelado = json_decode($row->cancelado, true);
                    $isCancelado   = ($JsonCancelado['cancelado'] == 't') ? true : false;
                    $descricaoCancelado = " Data de Cancelamento: {$JsonCancelado['data']}\r\n Motivo do Cancelamento: {$JsonCancelado['motivo']}";
                }
                
                if ($isCancelado == true) {
                    $status        = '<span class="label label-important" title="'.$descricaoCancelado.'">'.traduz('Obsoleto').'</span>';

                } else if (!empty($row->reprovado)) {

                    $status = '<span class="label label-important">'.traduz('Reprovado').'</span>';

                } else if (date("Y-m-d", strtotime($row->validade_certificado)) < date('Y-m-d')) {

                    $status = '<span class="label label-warning">'.traduz('Vencida').'</span>';

                } else if (!empty($row->aprovado)) {

                    $status = '<span class="label label-success">'.traduz('Aprovado').'</span>';
                    
                } else {

                    $status = '<span class="label label-warning">'.traduz('Aguardando Aprovação').'</span>';

                }
                ?>
                <tr>
                    <td><?=$row->descricao?></td>
                    <td><?=$row->grupo_ferramenta_descricao?></td>
                    <td><?=$row->codigo_posto." - ".$row->nome_posto?></td>
                    <td><?=$row->fabricante?></td>
                    <td><?=$row->modelo?></td>
                    <td><?=$row->numero_serie?></td>
                    <td><?=$row->certificado?></td>
                    <td><?=date("d/m/Y", strtotime($row->validade_certificado))?></td>
                    <td class='tac' ><?=$status?></td>
                    <td class='tac' nowrap >
                    <?php
                    $boxUploader = array(
                        'context' => 'ferramenta',
                        'titulo' => traduz('Ferramenta')." {$row->descricao} - ".traduz('Anexo(s)'),
                        'unique_id' => $row->posto_ferramenta,
                        'div_id' => $row->posto_ferramenta
                    );
                    include 'box_uploader_viewer.php';
                    ?>
                    </td>
                    <td nowrap >
                        <?php
                        if (empty($row->aprovado) && empty($row->reprovado)) {
                        ?>
                            <button type='button' class='btn btn-success btn-aprovar' data-posto-ferramenta='<?=$row->posto_ferramenta?>' ><i class="icon-check icon-white" ></i> Aprovar</button>
                            <button type='button' class='btn btn-danger btn-reprovar' data-posto-ferramenta='<?=$row->posto_ferramenta?>' ><i class="icon-remove icon-white" ></i> Reprovar</button>
                        <?php
                        }

                        if (in_array($login_fabrica, [175]) && !empty($row->aprovado) && empty($row->reprovado) && $isCancelado != 't') { 
                        ?>  
                            <button type='button' class='btn btn-danger btn-cancelar' data-posto-ferramenta='<?=$row->posto_ferramenta?>' ><i class="icon-remove icon-white" ></i> Cancelar</button>
                        <?php 
                        }                            
                        ?>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    
<?php if (in_array($login_fabrica, [175])) { ?>     
            </div>
        </div>
    </div>
<?php
    }
} else {
?>
    <div class="alert alert-danger">
        <h4>Nenhum resultado encontrado</h4>
    </div>
<?php
}
?>

<style type="text/css">
    .container2 {
        width: 75% !important;  
        margin-right: 15%;
    }
</style>
<script>

$(function(){
    Shadowbox.init();
    
    $('select').select2();

    $("#dataTable").dataTable();
    
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
        .removeClass('icon-remove')
        .addClass('icon-search')
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
        .removeClass('icon-search')
        .addClass('icon-remove')
        .attr({ title: 'Trocar Posto' });
    }
    
    $(document).on('click', '.btn-aprovar', function() {
        if (confirm("Deseja realmente aprovar a ferramenta?")) {
            var that = $(this);
            var id = $(that).data("posto-ferramenta");
            
            $(that).parent().find("button").prop({ disabled: true });
            $(that).html("<i class='icon-check icon-white' ></i> Aprovando...");
            
            $.ajax({
                url: window.location,
                type: 'get',
                async: true,
                timeout: 60000,
                data: { ajax_aprovar: true, posto_ferramenta: id }
            }).fail(function(res){
                $(that).html("<i class='icon-check icon-white' ></i> Aprovar");
                $(that).parent().find("button").prop({ disabled: false });
                alert("Erro ao aprovar a ferramenta");
            }).done(function(res, req){
                $(that).html("<i class='icon-check icon-white' ></i> Aprovar");
                $(that).parent().find("button").prop({ disabled: false });
                
                if (req == "success") {
                    res = JSON.parse(res);
                    
                    if (res.erro) {
                        $(that).html("<i class='icon-check icon-white' ></i> Aprovar");
                        $(that).parent().find("button").prop({ disabled: false });
                        alert(res.erro)
                    } else {
                        $(that).parent().prev().prev().html("<span class='label label-success'>Aprovado</span>");
                        $(that).parent().html("");
                    }
                } else {
                    $(that).html("<i class='icon-check icon-white' ></i> Aprovar");
                    $(that).parent().find("button").prop({ disabled: false });
                    alert("Erro ao aprovar a ferramenta");
                }
            });
        }
    });
    
    $(document).on('click', '.btn-reprovar', function() {
        var that = $(this);
        var id = $(that).data("posto-ferramenta");
        var motivo = prompt("Informe o motivo para reprovar a ferramenta");
        
        motivo = motivo.trim();
        
        if (motivo.length > 0) {
            $(that).parent().find("button").prop({ disabled: true });
            $(that).html("<i class='icon-remove icon-white' ></i> Reprovando...");
            
            $.ajax({
                url: window.location,
                type: 'get',
                async: true,
                timeout: 60000,
                data: { ajax_reprovar: true, posto_ferramenta: id, motivo: motivo }
            }).fail(function(res){
                $(that).html("<i class='icon-remove icon-white' ></i> Reprovar");
                $(that).parent().find("button").prop({ disabled: false });
                alert("Erro ao reprovar a ferramenta");
            }).done(function(res, req){
                $(that).html("<i class='icon-remove icon-white' ></i> Reprovar");
                $(that).parent().find("button").prop({ disabled: false });
                
                if (req == "success") {
                    res = JSON.parse(res);
                    
                    if (res.erro) {
                        $(that).html("<i class='icon-remove icon-white' ></i> Reprovar");
                        $(that).parent().find("button").prop({ disabled: false });
                        alert(res.erro)
                    } else {
                        $(that).parent().prev().prev().html("<span class='label label-important'>Reprovado</span>");
                        $(that).parent().html("");
                    }
                } else {
                    $(that).html("<i class='icon-remove icon-white' ></i> Reprovar");
                    $(that).parent().find("button").prop({ disabled: false });
                    alert("Erro ao reprovar a ferramenta");
                }
            });
        } else {
            alert("É obrigatório informar o motivo para reprovar a ferramenta");
        }
    });

    <?php if (in_array($login_fabrica, [175])) { ?> 
    $(document).on('click', '.btn-cancelar', function() {
        var that = $(this);
        var id = $(that).data("posto-ferramenta");
        var motivo = prompt("Informe o motivo para cancelar a ferramenta");
        
        motivo = motivo.trim();
        
        if (motivo.length > 0) {
            $(that).parent().find("button").prop({ disabled: true });
            $(that).html("<i class='icon-remove icon-white' ></i> Reprovando...");
            
            $.ajax({
                url: window.location,
                type: 'get',
                async: true,
                timeout: 60000,
                data: { ajax_cancelar: true, posto_ferramenta: id, motivo: motivo }
            }).fail(function(res){
                $(that).html("<i class='icon-remove icon-white' ></i> Cancelar");
                $(that).parent().find("button").prop({ disabled: false });
                alert("Erro ao cancelar a ferramenta");
            }).done(function(res, req){
                $(that).html("<i class='icon-remove icon-white' ></i> Cancelar");
                $(that).parent().find("button").prop({ disabled: false });
                
                if (req == "success") {
                    res = JSON.parse(res);
                    
                    if (res.erro) {
                        $(that).html("<i class='icon-remove icon-white' ></i> Cancelar");
                        $(that).parent().find("button").prop({ disabled: false });
                        alert(res.erro)
                    } else {
                        $(that).parent().prev().prev().html("<span class='label label-important'>Obsoleto</span>");
                        $(that).parent().html("");
                    }
                } else {
                    $(that).html("<i class='icon-remove icon-white' ></i> Cancelar");
                    $(that).parent().find("button").prop({ disabled: false });
                    alert("Erro ao cancelar a ferramenta");
                }
            });
        } else {
            alert("É obrigatório informar o motivo para cancelar a ferramenta");
        }
    });
    <?php } ?>
});

</script>

<?php
include 'rodape.php';
?>
