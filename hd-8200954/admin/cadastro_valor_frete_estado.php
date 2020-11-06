<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "CADASTRO DO VALOR DE FRETE POR ESTADO";

if(isset($_GET["editar"]) && isset($_GET["transportadora_padrao"])){

    $transportadora_padrao = $_GET["transportadora_padrao"];

    $sql = "SELECT * FROM tbl_transportadora_padrao WHERE transportadora_padrao = {$transportadora_padrao} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $transportadora_padrao = $transportadora_padrao;
        $valor_frete           = pg_fetch_result($res, 0, "valor_frete");
        $estado                = pg_fetch_result($res, 0, "estado");

    }

}

if(isset($_GET["deletar"]) && isset($_GET["transportadora_padrao"])){

    $transportadora_padrao = $_GET["transportadora_padrao"];

    $sql = "DELETE FROM tbl_transportadora_padrao WHERE transportadora_padrao = {$transportadora_padrao} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

}

if(isset($_POST["btn_acao"])){

    $valor_frete           = trim($_POST["valor_frete"]);
    $estado                = trim($_POST["estado"]);
    $transportadora_padrao = trim($_POST["transportadora_padrao"]);

    if(strlen($valor_frete) == 0){
        $msg_erro["msg"][]    = "Insira o Valor do Frete";
        $msg_erro["campos"][] = "valor_frete";
    }

    if(strlen($estado) == 0){
        $msg_erro["msg"][]    = "Insira o Estado";
        $msg_erro["campos"][] = "estado";
    }

    if(strlen($transportadora_padrao) == 0){

        $sql = "SELECT * FROM tbl_transportadora_padrao WHERE fabrica = {$login_fabrica} AND estado = '{$estado}' ";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $msg_erro["msg"][] = "Já existe um valor de frete cadastrado para esta estado";
        }

    }

    if(count($msg_erro) == 0){

        $valor_frete = str_replace(",", ".", $valor_frete);

        /* 5960 - Correios */

        if(strlen($transportadora_padrao) > 0){

            $sql = "UPDATE tbl_transportadora_padrao 
                    SET 
                        estado = '{$estado}', 
                        valor_frete = '{$valor_frete}' 
                    WHERE 
                        transportadora_padrao = {$transportadora_padrao} 
                        AND fabrica = {$login_fabrica}";

        }else{

            $sql = "INSERT INTO tbl_transportadora_padrao 
                (
                    transportadora,
                    fabrica,
                    valor_frete,
                    estado
                ) 
                VALUES 
                (
                    5960,
                    {$login_fabrica},
                    '{$valor_frete}',
                    '{$estado}'
                )";
        
        }

        $res = pg_query($con, $sql);

        $transportadora_padrao = "";
        $valor_frete = "";
        $estado = "";

    }

}

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric"
);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">
$(function() {

    $.datepickerLoad(Array("data_final", "data_inicial"));
    Shadowbox.init();
    $.dataTableLoad();

    $("#valor_frete").numeric({allow: ','})

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
?>

<div class="row">
    <strong class="obrigatorio pull-right">  * Campos obrigatórios </strong>
</div>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

    <input type="hidden" name="transportadora_padrao" value="<?php echo $transportadora_padrao; ?>" >

    <div class='titulo_tabela '>Cadastro</div>
    <br/>
    <div class='row-fluid'>
        <div class='span3'></div>
        <div class='span2'>
            <div class='control-group <?=(in_array("valor_frete", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='valor_frete'>Valor do Frete</label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="valor_frete" id="valor_frete" size="12" maxlength="10" class='span12' value= "<?=$valor_frete?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Estados</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            #O $array_estados() está no arquivo funcoes.php
                            foreach ($array_estados() as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : "";
                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <br />

    <button class='btn' id="btn_acao">Gravar</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='sim' />

    <br /> <br />

</form>

<?php
$sql = "SELECT * FROM tbl_transportadora_padrao WHERE fabrica = {$login_fabrica} ORDER BY estado ASC";
$result = pg_query($con, $sql);

$sql_num = pg_num_rows($result);

if(isset($result)){

    if($sql_num > 0){

        ?>

        </div>

        <br />

        <div style="margin: 5px !important;">

            <table class="table table-bordered table-striped" style="width: 850px;">
                <thead>
                    <tr class="titulo_tabela">
                        <th colspan="5">
                            Relatório de Frente por Estado
                        </th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th nowrap>#</th>
                        <th>Sigla</th>
                        <th>Estado</th>
                        <th nowrap>Valor do Frete</th>
                        <th nowrap>Ações</th>
                    </tr>
                </thead>
                <tbody>

                <?php

                $linha = 1;

                for ($i = 0; $i < $sql_num; $i++) { 
                    
                    $transportadora_padrao = pg_fetch_result($result, $i, "transportadora_padrao");
                    $estado = pg_fetch_result($result, $i, "estado");

                    foreach ($array_estados() as $sigla => $nome_estado) {
                        if($estado == $sigla){
                            $estado_nome  = $nome_estado;
                            $estado_sigla = $sigla;
                        }
                    }

                    $valor_frete = pg_fetch_result($result, $i, "valor_frete");

                    $valor_frete = number_format($valor_frete, 2, ",", ".");

                    echo "
                    <tr>
                        <td nowrap class='tac' width='10%'> {$linha} </td>
                        <td nowrap class='tac' width='10%'> {$estado_sigla} </td>
                        <td nowrap width='40%'> {$estado_nome} </td>
                        <td nowrap class='tac' width='20%' > {$valor_frete} </td>
                        <td nowrap class='tac' width='20%' >
                            <a href='cadastro_valor_frete_estado.php?transportadora_padrao={$transportadora_padrao}&editar=sim' class='btn btn-info' > Editar </a> 
                            <a href='cadastro_valor_frete_estado.php?transportadora_padrao={$transportadora_padrao}&deletar=sim' class='btn btn-danger' > Excluir </a> 
                        </td>
                    </tr>
                    ";

                    $linha++;

                }

                ?>
                    
                </tbody>
            </table>

        </div>

        <div class="container">

        <?php

    }else{

        echo "
            <div class='alert'>
                <h4>Nenhum resultado encontrado</h4>
            </div>
        ";

    }
}

?>

<br /> <br />

<?php include "rodape.php"; ?>
