<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "cadastros,call_center";
include 'autentica_admin.php';

$title       = "CADASTRO DE MOTIVO DE RECUSA X CONCUSLÃO";
$cabecalho   = "CADASTRO DE MOTIVO DE RECUSA X CONCUSLÃO";
$layout_menu = "cadastro";

$motivo_recusa_id = $_GET['motivo_recusa'];

/******** SQL GRAVAR ********/
if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {

    $id            = (strlen($_POST['id']) > 0) ? $_POST['id'] : NULL;
    $valor         = utf8_decode(trim($_POST['valor']));
    $ativo         = $_POST['ativo'];

    if (!empty($id)) {
        $condAlterar = "AND motivo_recusa != {$id}";
    }
    
    $campo_insert       = ($_POST['acao'] == 'gravar_motivo') ? "motivo" : "conclusao";

    $sqlVerifica = "SELECT motivo_recusa
                    FROM tbl_motivo_recusa
                    WHERE fabrica = {$login_fabrica}
                    AND UPPER({$campo_insert}) = UPPER('{$valor}')
                    {$condAlterar}";
    $resVerifica = pg_query($con, $sqlVerifica);

    if (pg_num_rows($resVerifica) == 0) {

        if (!empty($id)) {

            $campo_update = ($_POST['acao'] == 'gravar_motivo') ? ", motivo = '{$valor}'" : ", conclusao = '{$valor}'";

            /******** UPDATE ********/
            $sql_update = "UPDATE tbl_motivo_recusa SET
                                liberado        =  '{$ativo}'
                                {$campo_update}
                            WHERE fabrica       = {$login_fabrica}
                            AND   motivo_recusa = {$id};";
            $res_update = pg_query($con, $sql_update);

            if (pg_affected_rows($res_update) == 0 || pg_last_error()) {
                $msg_erro = "Erro ao alterar cadastro";
            } else {
                $msg_ok     = "Dados atualizados com sucesso";
            }

        } else {

            $campo_valor = ", '{$valor}'";
            
            /******** INSERT ********/
            $sql_insert = "INSERT INTO tbl_motivo_recusa(
                                liberado,
                                fabrica,
                                {$campo_insert}
                            ) VALUES (
                                '{$ativo}',
                                {$login_fabrica}
                                {$campo_valor}
                            )";
            $res_insert = pg_query($con, $sql_insert);

            if (pg_last_error()) {
                $msg_erro = "Erro ao realizar cadastro";
            } else {
                $msg_ok   = "Dados gravados com sucesso";
            }
            
        }
    } else {
        $msg_erro = "Já existe um cadastro com a descrição informada";
    }

    if (strlen($msg_erro) > 0) {
        exit(json_encode(array("erro" => utf8_encode($msg_erro))));
    } else {
        exit(json_encode(array("ok" => utf8_encode($msg_ok))));
    }
    exit;
}

/******** SQL ALTERAR ********/
if (isset($_GET['motivo_recusa']) && $_GET['motivo_recusa'] != '') {
    $motivo_recusa_id = $_GET['motivo_recusa'];
    
    /******** OBTENDO DADOS ********/
    $sql_busca  = "SELECT
                        mr.motivo,
                        mr.liberado,
                        mr.conclusao
                FROM    tbl_motivo_recusa mr
                WHERE   mr.motivo_recusa = {$motivo_recusa_id}
                AND     mr.fabrica       = {$login_fabrica}";
    $res_busca  = pg_query($con, $sql_busca);
    if (pg_num_rows($res_busca) > 0) {

        $id_alterar        = $motivo_recusa_id;
        $motivo_recusa     = pg_fetch_result($res_busca, 0, 'motivo');
        $conclusao         = pg_fetch_result($res_busca, 0, 'conclusao');
        $ativo             = pg_fetch_result($res_busca, 0, 'liberado');
    }
}

if (!empty($motivo_recusa_id)) {

    $lblBotao = "Alterar";

    if (!empty($conclusao)) {

        $displayFormMotivo     = "display: none;";

    } else {

        $displayFormConclusao  = "display: none;";

    }

} else {

    $lblBotao = "Adicionar";

}

/******** SQL CONSULTA ********/
$sql_consulta = "SELECT
                    mr.motivo_recusa,
                    mr.motivo,
                    mr.liberado
                FROM  tbl_motivo_recusa mr
                WHERE fabrica = {$login_fabrica}
                AND mr.conclusao IS NULL";
$res_consulta_recusa = pg_query($con, $sql_consulta);

/******** SQL CONSULTA ********/
$sql_consulta = "SELECT
                    mr.motivo_recusa,
                    mr.liberado,
                    mr.conclusao
                FROM  tbl_motivo_recusa mr
                WHERE fabrica = {$login_fabrica}
                AND mr.motivo IS NULL";
$res_consulta_conclusao = pg_query($con, $sql_consulta);

/******** CABEÇALHO ********/
include "cabecalho_new.php";
    $plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);
include("plugin_loader.php");
?>
<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<div id="erro" class='alert alert-error' style="display:none;"><h4></h4></div>
<div id="success" class='alert alert-success' style="display:none;"><h4></h4></div>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<!-- CADASTRO -->
<FORM name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
    <div style="<?= $displayFormMotivo ?>">
        <div class="titulo_tabela">Cadastro de Motivo</div> <br>
        <input type="hidden" name="motivo_recusa_id" id="motivo_recusa_id" value= "<?= $motivo_recusa_id ?>">
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span5">
                <div class="control-grup">
                    <label class='control-label'>Motivo de Recusa</label>
                    <div class='controls controls-row'>
                        <h5 class='asteristico'>*</h5>
                        
                        <textarea type='text' class="span12" name='motivo_recusa' id='motivo_recusa' size='30' maxlength='255' value=''><?= $motivo_recusa ?></textarea>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
            <div class="span2">
                <div class="control-grup" style="text-align: center;">
                    <label class='control-label'>Ativo</label>
                    <div class='controls controls-row' style="text-align: center;">
                        <input type="checkbox" name="ativo_motivo" id="ativo_motivo" value="t" <?=($ativo == 't') ? 'checked=checked' : ''; ?> />
                    </div>  
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <br />
        <div class="row-fluid">
            <div class="span4"></div>
            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <button type="button" class="btn btn-gravar btn-primary" name="btn_gravar" value='gravar_motivo'> <?= $lblBotao ?> Motivo </button>
                    </div>
                </div>
            </div>
            <div class="span4"> </div>
        </div>
    </div>
    <div style="<?= $displayFormConclusao ?>">
        <div class="titulo_tabela">Cadastro de Conclusão</div> <br>
        <div class='row-fluid'>
            <div class="span2"></div> 
            <div class="span5">
                <div class="control-grup">
                    <label class='control-label'>Conclusão da Fábrica</label>
                    <div class='controls controls-row'>
                        <h5 class='asteristico'>*</h5>
                        <textarea type='text' class="span12" name='conclusao' id='conclusao' size='30' maxlength='255' value=''><?=$conclusao;?></textarea>
                    </div>  
                </div>
            </div>
            <div class="span1"></div>
            <div class="span2">
                <div class="control-grup" style="text-align: center;">
                    <label class='control-label'>Ativo</label>
                    <div class='controls controls-row' style="text-align: center;">
                        <input type="checkbox" name="ativo_conclusao" id="ativo_conclusao" value="t" <?=($ativo == 't') ? 'checked=checked' : ''; ?> />
                    </div>  
                </div>
            </div>
            <div class="span2"></div>
        </div> <br />
        <div class="row-fluid">
            <div class="span4"></div>
            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <button type="button" class="btn btn-primary btn-gravar" name="btn_gravar" value='gravar_conclusao'><?= $lblBotao ?> Conclusão</button>
                    </div>
                </div>
            </div>
            <div class="span4"> </div>
        </div>
    </div>
</form>

<?php if (pg_num_rows($res_consulta_recusa) > 0) { ?>
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class="titulo_tabela">
            <th colspan="100%">Motivos de Recusa</th>
        </tr>
        <tr class='titulo_coluna'>
            <td colspan="1" style='text-align: center;'>Motivo de Recusa</td>
            <td colspan="1" style='text-align: center;'>Ativo</td>
            <td colspan="1" style='text-align: center;'>Ação</td>
        </tr>
    </thead>
    <tbody>
        <?php for ($i_consulta=0; $i_consulta<pg_num_rows($res_consulta_recusa); $i_consulta++) { 
                $res_motivo_id = pg_fetch_result($res_consulta_recusa, $i_consulta, 'motivo_recusa');
                $res_motivo    = pg_fetch_result($res_consulta_recusa, $i_consulta, 'motivo');
                $res_ativo     = pg_fetch_result($res_consulta_recusa, $i_consulta, 'liberado');
        ?>
        <tr>
            <td style="width: 50%;"><?=$res_motivo?></td>
            <td style="width: 15%;" class="tac"><?= ($res_ativo == 't') ? "<img src='imagens/status_verde.png'>" : "<img src='imagens/status_vermelho.png'>" ?></td>
            <td style="width: 35%;" class="tac"> <a href='?motivo_recusa=<?=$res_motivo_id;?>' class='btn btn-info'>  Alterar  <i class="fas fa-edit"></i> </a> </td>
        </tr>
                
        <?php 
        } ?>
    </tbody>
</table>
<?php } else { ?>
        <span class="alert alert-warning"><h4>Nenhum motivo de recusa cadastrado</h4></span>
<?php } ?>
<br />
<?php
 if (pg_num_rows($res_consulta_conclusao) > 0) { ?>
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class="titulo_tabela">
            <th colspan="100%">Conclusões da Fábrica</th>
        </tr>
        <tr class='titulo_coluna'>
            <td colspan="1" style='text-align: center;'>Conclusão da Fábrica</td>
            <td colspan="1" style='text-align: center;'>Ativo</td>
            <td colspan="1" style='text-align: center;'>Ação</td>
        </tr>
    </thead>
    <tbody>
        <?php for ($i_consulta=0; $i_consulta<pg_num_rows($res_consulta_conclusao); $i_consulta++) { 
                $res_motivo_id = pg_fetch_result($res_consulta_conclusao, $i_consulta, 'motivo_recusa');
                $res_conclusao = pg_fetch_result($res_consulta_conclusao, $i_consulta, 'conclusao');
                $res_ativo     = pg_fetch_result($res_consulta_conclusao, $i_consulta, 'liberado');
        ?>
        <tr>
            <td style="width: 50%;"><?=$res_conclusao?></td>
            <td style="width: 15%;" class="tac"><?= ($res_ativo == 't') ? "<img src='imagens/status_verde.png'>" : "<img src='imagens/status_vermelho.png'>" ?></td>
            <td style="width: 35%;" class="tac"> <a href='?motivo_recusa=<?=$res_motivo_id;?>' class='btn btn-info'>  Alterar  <i class="fas fa-edit"></i> </a></td>
        </tr>
                
        <?php 
        } ?>
    </tbody>
</table>
<?php } else { ?>
        <span class="alert alert-warning"><h4>Nenhuma conclusão cadastrada</h4></span>
<?php } ?>

<!-------- SCRIPTS -------->
<script type="text/javascript">
    $('.btn-gravar').on('click', function(){

        var erro = false;

        var motivo_recusa_id= $("#motivo_recusa_id").val();

        var conclusao       = $("#conclusao").val();
        var motivo          = $("#motivo_recusa").val();

        var ativo_conclusao = ($("#ativo_conclusao").is(":checked")) ? "t" : "f";
        var ativo_motivo    = ($("#ativo_motivo").is(":checked")) ? "t" : "f";

        if ($(this).val() == "gravar_motivo") {

            if (motivo == "") {
                var erro = true;
                $("#erro").html('<h4> <b>Preecha a o motivo de recusa.</b> </h4>');
                $("#erro").show();
            }

            var data_json = {
                ajax:      "sim",
                acao:      "gravar_motivo",
                id:        motivo_recusa_id,
                valor:     motivo,
                ativo:     ativo_motivo
              };

        } else {

            if (conclusao == "") {
                var erro = true;
                $("#erro").html('<h4> <b>Preecha a conclusão da fábrica.</b> </h4>');
                $("#erro").show();
            }

            var data_json = {
                ajax:      "sim",
                acao:      "gravar_conclusao",
                id:        motivo_recusa_id,
                valor:     conclusao,
                ativo:     ativo_conclusao
            };
        }

        if (!erro) {

            $("#erro").hide();

            $.ajax("cadastro_motivo_recusa_conclusao.php",{
              method: "POST",
              data: data_json
            }).done(function(data){
                data = JSON.parse(data);

                if (data.ok !== undefined) {
                    $("#success").html('<h4> <b>'+data.ok+'.</b> </h4>');
                    $("#success").show();
                    setTimeout(function() {
                       $('#success').fadeOut('fast');
                       window.location.href = "cadastro_motivo_recusa_conclusao.php";
                    }, 2000);
                } else {
                    $("#erro").html('<h4> <b>'+data.erro+'.</b> </h4>');
                    $("#erro").show();
                }
            });
        }
    });
</script>