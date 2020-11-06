<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once 'fn_traducao.php';

if (!$moduloGestaoContrato) {
    echo "<meta http-equiv=refresh content=\"0;URL=menu_gerencia.php\">";
}

use GestaoContrato\Contrato;
use GestaoContrato\ContratoStatus;
use GestaoContrato\ContratoStatusMovimento;
use GestaoContrato\Comunicacao;

$objContratoStatusMovimento = new ContratoStatusMovimento($login_fabrica, $con);
$objContratoStatus = new ContratoStatus($login_fabrica, $con);
$objContrato       = new Contrato($login_fabrica, $con);
$objComunicacao    = new Comunicacao($externalId);
$status_contrato   = $objContratoStatus->get();
$url_redir         = "<meta http-equiv=refresh content=\"0;URL=consulta_contrato.php\">";



if ($_POST) {

    $numero_contrato          = filter_input(INPUT_POST, 'numero_contrato', FILTER_SANITIZE_NUMBER_INT);

    if (strlen($numero_contrato) > 0) {
        $dadosContratos   = $objContrato->getRelatorioCustoProdutoByContrato($numero_contrato);

        foreach ($dadosContratos as $key => $value) {

        	$relatorio[$value["produto"]]["produto"] = $value["produto"];
        	$relatorio[$value["produto"]]["data_vigencia"] = $value["data_vigencia"];
        	$relatorio[$value["produto"]]["nome_produto"] = $value["nome_produto"];
        	$relatorio[$value["produto"]]["contrato"] = $value["contrato"];
        	$relatorio[$value["produto"]]["cliente_nome"] = $value["cliente_nome"];
        	$relatorio[$value["produto"]]["representante_nome"] = $value["representante_nome"];
        	$relatorio[$value["produto"]]["custo_km"] += $value["custo_km"];
        	$relatorio[$value["produto"]]["custo_mo"] += $value["custo_mo"];
        	$relatorio[$value["produto"]]["custo_peca"] += $value["custo_peca"];

        }


    } else {
        $msg_erro["msg"][] = traduz("O campo Número do Contrato é obrigatório");
        $msg_erro["campos"][] = "numero_contrato";
    }
}
function geraDataTimeNormal($data) {
    list($ano, $mes, $vetor) = explode("-", $data);
    $resto = explode(" ", $vetor);
    $dia = $resto[0];
    return $dia."/".$mes."/".$ano;
}
function geraDataBD($data) {
    list($dia, $mes, $ano) = explode("/", $data);
    return $ano."-".$mes."-".$dia;
}

$layout_menu       = "gerencia";
$admin_privilegios = "gerencia";
$title = traduz("Relatório Custo de Produtos por Nº Série");
include_once 'cabecalho_new.php';

$plugins = array(
    "dataTable",
   "multiselect",
   "datepicker",
   "shadowbox",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet",
   "font_awesome",
   "autocomplete"
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
    .titulo_th th{
        background: #333c51 !important;
        color: #fff;
    }
    .dropdown-menu {
        left: -95px !important;
    }
</style>
<script type="text/javascript" src="../externos/institucional/lib/mask/mask.min.js"></script>

<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        
    });

</script>
    <?php if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($msg_sucesso["msg"]) > 0){?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">  * <?php echo traduz("Campos obrigatórios");?> </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '><?php echo traduz("Parâmetros de Pesquisa");?></div>
        <br/>
        <div class='row-fluid'>
            <div class='span5'></div>
            <div class='span2'>
                <div class='control-group <?=(in_array("numero_contrato", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'><?php echo traduz("Número do Contrato");?></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $numero_contrato;?>" name="numero_contrato" id="numero_contrato">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?php echo traduz("Pesquisar");?></button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />
</div>
    <?php
        if ($dadosContratos["erro"]) {
            echo '<div class="alert alert-waring"><h4>'.$dadosContratos["msn"].'</h4></div>';
        } else {
        if (count($dadosContratos) > 0) {
    ?>

    <table class='table table-striped table-bordered table-fixed'>
        <thead>
            <tr class='titulo_coluna' >
                <th nowrap width="11%" align="left"><?php echo traduz("Nº Contrato");?></th>
                <th nowrap width="11%" align="left"><?php echo traduz("Data da Vigência");?></th>
                <th nowrap class="tal"><?php echo traduz("Representante");?></th>
                <th nowrap class="tal"><?php echo traduz("Cliente");?></th>
                <th nowrap class="tal"><?php echo traduz("Produto");?></th>
                <th nowrap><?php echo traduz("Custo KM");?></th>
                <th nowrap><?php echo traduz("Custo Mão de Obra");?></th>
                <th nowrap><?php echo traduz("Custos com Peças");?></th>
            </tr>
        </thead>
        <tbody>
        <?php 
            foreach ($relatorio as $k => $rows) {
        ?>
            <tr >
                <td <?php echo $cor;?> class='tac'><?php echo $rows["contrato"];?></td>
                <td <?php echo $cor;?> class='tac'><?php echo geraDataTimeNormal($rows["data_vigencia"]);?></td>
                <td <?php echo $cor;?> nowrap class='tal'><?php echo $rows["representante_nome"];?></td>
                <td <?php echo $cor;?> nowrap class='tal'><?php echo $rows["cliente_nome"];?></td>
                <td <?php echo $cor;?> nowrap class='tal'><?php echo $rows["nome_produto"];?></td>
                <td <?php echo $cor;?> class='tac' nowrap><?php echo 'R$ '.number_format($rows["custo_km"], 2, ',', '.');?></td>
                <td <?php echo $cor;?> class='tac' nowrap><?php echo 'R$ '.number_format($rows["custo_mo"], 2, ',', '.');?></td>
                <td <?php echo $cor;?> class='tac' nowrap><?php echo 'R$ '.number_format($rows["custo_peca"], 2, ',', '.');?></td>
                
            </tr>
        <?php }?>
        </tbody>
    </table>
    <?php } ?>
    <?php }?>
</div>
</div> 
<?php include 'rodape.php';?>
