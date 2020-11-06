<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_sucesso = '';

if($_GET['acao'] == "excluir"){
    $servico_realizado = $_GET['servico_realizado'];
    $sql = "DELETE FROM tbl_mao_obra_servico_realizado WHERE servico_realizado = $servico_realizado";
    $res = pg_query($con,$sql);

    $msg_erro = pg_last_error();
    if(strlen($msg_erro) == 0){
        echo "ok";
    }else{
        echo $msg_erro;
    }
    exit;
}

if($_GET['servico_realizado'] AND !$_GET['acao']){

    $servico_realizado = $_GET['servico_realizado'];
    
    $sql = "SELECT tbl_mao_obra_servico_realizado.servico_realizado, hora_trabalhada, tempo_estimado,descricao , tbl_mao_obra_servico_realizado.mao_de_obra
            FROM tbl_mao_obra_servico_realizado
            JOIN tbl_servico_realizado ON tbl_mao_obra_servico_realizado.servico_realizado = tbl_servico_realizado.servico_realizado
            WHERE tbl_mao_obra_servico_realizado.servico_realizado = $servico_realizado and tbl_servico_realizado.fabrica = $login_fabrica";
    $res = pg_query($con,$sql);

    $servico_realizado  = pg_fetch_result($res, 0, 'servico_realizado');
    $hora_trabalhada    = pg_fetch_result($res, 0, 'hora_trabalhada');
    $tempo              = pg_fetch_result($res, 0, 'tempo_estimado');
    $valor              = pg_fetch_result($res, 0, 'mao_de_obra');

    $valor = number_format($valor, 2, ',', ' ');
  
}

if ($_POST["btn_acao"]) {
    $btn_acao           = $_POST["btn_acao"];
    $servico_realizado  = $_POST['servico_realizado'];
    $valor              = $_POST['valor'];

    $valor = str_replace(",", ".", $valor);    

    if (!strlen($servico_realizado)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "servico_realizado";
    }

    $sql_pega_id_autorizada = "select tipo_posto from tbl_tipo_posto where fabrica = $login_fabrica and descricao = 'Autorizada' ";
    $res_pega_id_autorizado = pg_query($con, $sql_pega_id_autorizada);
    if(pg_num_rows($res_pega_id_autorizado) > 0 ){
        $tipo_posto = pg_fetch_result($res_pega_id_autorizado, 0, tipo_posto);
    }else{
        $msg_erro["msg"][]    = "Tipo Posto não encontrato";
    }

    if (count($msg_erro["msg"]) == 0) {
        
        if($btn_acao == "gravar"){
            $sql = "INSERT INTO tbl_mao_obra_servico_realizado(servico_realizado, mao_de_obra, tipo_posto)
                    VALUES($servico_realizado,'$valor', $tipo_posto)";
            $msg = 'cadastrado';
            $servico_realizado = '';
            $valor = '';
        }else{
            $sql = "UPDATE tbl_mao_obra_servico_realizado SET 
                            mao_de_obra     = '$valor'
                    WHERE servico_realizado = $servico_realizado";
            $msg = 'atualizado';
        }
        $res = pg_query($con,$sql);

        if (pg_last_error()) {
            $msg_erro['msg'][] = 'Erro ao cadastrar registro.';
        } else {
            $msg_sucesso = "<h4>Registro $msg com sucesso.</h4>";
        }
        

        

    }

}

$sqlCadastrados = "SELECT tbl_mao_obra_servico_realizado.servico_realizado, hora_trabalhada, tempo_estimado,descricao , tbl_mao_obra_servico_realizado.mao_de_obra
        FROM tbl_mao_obra_servico_realizado
        JOIN tbl_servico_realizado ON tbl_mao_obra_servico_realizado.servico_realizado = tbl_servico_realizado.servico_realizado
        WHERE tbl_servico_realizado.fabrica = $login_fabrica and tbl_servico_realizado.ativo = true";
$resCadastrados = pg_query($con,$sqlCadastrados);

$layout_menu = "cadastro";
$title = "MÃO DE OBRA POR SERVIÇO REALIZADO";
include 'cabecalho_new.php';


$plugins = array(
    "shadowbox",
    "mask",
    "dataTable",
    "price_format"
);

include("plugin_loader.php");

?>
<script language="javascript">
    
    $(function() {
        $("#tempo").change(function(){
            var hora = $("#hora").val();
            var tempo = $(this).val();
            var mobra = parseFloat((hora / 60) * tempo);
            $("#valor").val(mobra.toFixed(2));
        });

        $("#hora").change(function(){
            var hora = $(this).val();
            var tempo = $("#tempo").val();

            if (!tempo) {
                return false;
            }

            var mobra = parseFloat((hora / 60) * tempo);
            $("#valor").val(mobra.toFixed(2));
        });

        $(".excluir").click(function(){
            var servico_realizado = $(this).attr("rel");
            var btn = $(this);

            $.ajax({
                url : "mobra_servico_realizado.php",
                type: "GET",
                data: {acao: 'excluir',servico_realizado:servico_realizado},
                complete: function(data){
                    if(data.responseText == "ok"){
                        $(btn).parents("tr").remove();
                        $('#success').html('<h4>Registro excluido com sucesso.</h4>');
                        $('#success').css('display', 'block');
                    }
                }
            })
        });

    }); 
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if (empty($msg_sucesso)) {
    $display = 'none';    
} else {
    $display = 'block';
}
?>

<div id="success" class="alert alert-success" style="display: <?php echo $display ?>;">
    <?php echo $msg_sucesso ?>
</div>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Cadastro</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("servico_realizado", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='servico_realizado'>Serviço</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <select name="servico_realizado" id="servico_realizado">
                                <option value=""></option>
                                <?php
                                $sql = "SELECT servico_realizado, descricao
                                        FROM tbl_servico_realizado
                                        WHERE fabrica = $login_fabrica
                                        AND ativo
                                        ORDER BY descricao";
                                $res = pg_query($con,$sql);

                                foreach (pg_fetch_all($res) as $key) {
                                    $selected_linha = ( isset($servico_realizado) and ($servico_realizado == $key['servico_realizado']) ) ? "SELECTED" : '' ;

                                ?>
                                    <option value="<?php echo $key['servico_realizado']?>" <?php echo $selected_linha ?> >

                                        <?php echo $key['descricao']?>

                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php if (!in_array($login_fabrica, array(162))){?>
            <div class='span1'>
                <div class='control-group <?=(in_array("hora", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tempo'>Valor (H)</label>
                    <div class='controls controls-row'>
                        <div class='span12input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="hora" id="hora" class='span12' value="<? echo $hora_trabalhada ?>" >
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if (!in_array($login_fabrica, array(162))){?>
            <div class='span1'>
                <div class='control-group <?=(in_array("tempo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tempo'>Tempo</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="tempo" id="tempo" class='span12' value="<? echo $tempo ?>" >
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

            <div class='span2'>
                <div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
                    <div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='valor'>Valor M.O.</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" name="valor" id="valor" price="true" class='span12' value="<? echo $valor ?>" >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>       

        <p><br/>

            <input type='hidden' id="btn_click" name='btn_acao' value='' />

            <?php
            if (strlen($_GET["servico_realizado"]) > 0) {
                $servico = $_GET['servico_realizado'];
                $value_btn = "atualizar";
            ?>
                
            <?php
            }else{
                $value_btn = "gravar";
            }
            ?>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'<?=$value_btn?>');">Gravar</button>
        </p><br/>
</form>

<?php
    if(pg_num_rows($resCadastrados) > 0){
?>

        <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Serviço</th>
                    <th>Mão de Obra</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
<?php
            for ($i = 0; $i < pg_num_rows($resCadastrados); $i++) {
                $servico_realizado     = pg_fetch_result($resCadastrados, $i, 'servico_realizado');
                $descricao             = pg_fetch_result($resCadastrados, $i, 'descricao');
                $mao_de_obra           = number_format(pg_fetch_result($resCadastrados, $i, 'mao_de_obra'), 2, ',', ' ');

                $body = "<tr>
                            <td class='tac'><a href='mobra_servico_realizado.php?servico_realizado={$servico_realizado}' >{$descricao}</a></td>
                            <td class='tac'>{$mao_de_obra}</td>
                            <td><center><button class='btn btn-danger excluir' type='button' rel='$servico_realizado'>Excluir</button></center></td>
                        </tr>";
                echo $body;
            }
    }
?>
            </tbody>
        </table>
</div>
<?php
    if (pg_num_rows($resCadastrados) > 50) {
    ?>
        <script>
            $.dataTableLoad({ table: "#resultado_os_atendimento" });
        </script>
    <?php
    }

    include 'rodape.php';
?>
