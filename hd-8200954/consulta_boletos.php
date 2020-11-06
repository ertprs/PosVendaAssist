<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once('autentica_usuario.php');
include_once('funcoes.php');

$layout_menu = "cadastro";
$title       = "Consulta 2º via de boletos de pedidos";
include __DIR__.'/cabecalho_new.php';

if ($_POST["btn_acao"]) {

    $retorno        = [];
    $condNota       = "";
    $condData       = "";
    $data_inicial   = $_POST['data_inicial'];
    $data_final     = $_POST['data_final'];
    $numero_nf      = $_POST["numero_nf"];


    if (strlen($numero_nf) == 0 && strlen($_POST['data_inicial']) == 0 && strlen($_POST['data_final']) == 0) {
        $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
        $msg_erro['campos'][]   = 'numero_nf';
    }


    if (strlen($numero_nf) == 0) {

        if (strlen($data_inicial) == 0) {
            $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
            $msg_erro['campos'][]   = 'data_inicial';
        } else {
            list($dia, $mes, $ano) = explode('/', $data_inicial);
            
            if (!strtotime("$ano-$mes-$dia")) {
                $msg_erro['msg'][]    = 'Data inicial inválida';
                $msg_erro['campos'][] = 'data_inicial';
            } else {
                $xdata_inicial = "$ano-$mes-$dia";
            }
        }

        if (strlen($data_final) == 0 && strlen($numero_nf) == 0) {
            $msg_erro['msg']['obg'] = 'Preencha os campos obrigatórios';
            $msg_erro['campos'][]   = 'data_final';
        } else {
            list($dia, $mes, $ano) = explode('/', $_POST['data_final']);
            
            if (!strtotime("$ano-$mes-$dia")) {
                $msg_erro['msg'][]    = 'Data final inválida';
                $msg_erro['campos'][] = 'data_final';
            } else {
                $xdata_final = "$ano-$mes-$dia";
            }
        }
        
        if (!empty($xdata_inicial) && !empty($xdata_final) && strtotime($xdata_final) < strtotime($xdata_inicial)) {
            $msg_erro['msg'][]    = 'Data final não pode ser inferior a data inicial';
            $msg_erro['campos'][] = 'data_inicial';
            $msg_erro['campos'][] = 'data_final';
        }
    }

    if (count($msg_erro['msg']) == 0) {

        if (strlen($numero_nf) > 0) {
            $condNota = " AND tbl_faturamento.nota_fiscal = '{$numero_nf}'";
        }
        if (strlen($xdata_inicial) > 0 && strlen($xdata_final) > 0) {
            $condData = " AND tbl_pagar.vencimento between  '{$xdata_inicial}' AND '{$xdata_final}'";
        }

        $sql = "SELECT distinct tbl_pagar.obs , tbl_faturamento.faturamento
                  FROM tbl_pagar
                  JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_pagar.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
                 WHERE tbl_pagar.fabrica = $login_fabrica
                   AND tbl_pagar.posto = {$login_posto}
                       {$condNota}
					{$condData}
				order by tbl_faturamento.faturamento desc";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {

            for ($i=0; $i <= pg_num_rows($res); $i++) { 

                $dadosRetorno = json_decode(pg_fetch_result($res, $i, 'obs'), 1);

                if (count($dadosRetorno) > 0) {

                    $retorno[$i]["nota_fiscal"]             = $dadosRetorno["nota_fiscal"];//"102030";
                    $retorno[$i]["emissao"]                 = geraDataNormal($dadosRetorno["emissao"]);//"12/12/2019";
                    $retorno[$i]["pedido"]                  = $dadosRetorno["pedido"]["pedido"];//"212121";
                    $retorno[$i]["total_pedido"]            = $dadosRetorno["total_pedido"];//"353.87";

                    if (count($dadosRetorno["boletos"]) > 0) {

                        foreach ($dadosRetorno["boletos"] as $key => $rows) {

                            $retorno[$i]["boletos"][$key]["titulo"]       = $rows["titulo"];//"fatura numero 10";
                            $retorno[$i]["boletos"][$key]["vencimento"]   = geraDataNormal($rows["vencimento"]);//"15/12/2019";
                            $retorno[$i]["boletos"][$key]["valor"]        = $rows["valor"];//"355.99";
                            $retorno[$i]["boletos"][$key]["nosso_numero"] = $rows["nosso_numero"];//"2019010122";
                            $retorno[$i]["boletos"][$key]["cnpj"]         = $rows["cnpj"];//"121222220000122";
                            $retorno[$i]["boletos"][$key]["codigo_banco"] = $rows["cod_banco"];//"001";
                            $retorno[$i]["boletos"][$key]["banco"]        = $rows["banco"];//"banco do brasil";
                            $retorno[$i]["boletos"][$key]["agencia"]      = $rows["agencia"];//"2122";
                            $retorno[$i]["boletos"][$key]["conta"]        = $rows["conta"];//"221991-2";

                        }

                    }

                }

            }

        }

    }

}

$links_banco = [
                "341" => "https://www.itau.com.br/servicos/boletos/segunda-via/",
                "237" => "https://banco.bradesco/html/classic/produtos-servicos/mais-produtos-servicos/segunda-via-boleto.shtm",
                "001" => "https://www63.bb.com.br/portalbb/boleto/boletos/hc21e,802,3322,10343.bbx",
                "033" => "https://www.santander.com.br/portal/wps/script/boleto_online_conv/EmissaoSegViaBoleto.do"
            ];

function geraDataNormal($data) {

	if(strpos($data,"-") !== false){
    		list($ano, $mes, $dia) = explode("-", $data);
	}else{
		$ano = substr($data,0,4);
		$mes = substr($data,4,2);
		$dia = substr($data,6,2);
	}
    return $dia.'/'.$mes.'/'.$ano;
}

$plugins = array(
    "datepicker",
    "shadowbox",
    "autocomplete",
    "dataTable"
);
include __DIR__.'/plugin_loader.php';

?>
<script language="javascript">
    $(function(){
        Shadowbox.init();
        $.autocompleteLoad(Array("posto"));

        $("#numero_nf").change(function() {
            if ($(this).val() == "") {
                $(this).attr("value", "");
                $("#data_inicial").prev(".asteristico").show();
                $("#data_final").prev(".asteristico").show();
            } else {
                $("#data_inicial").prev(".asteristico").hide();
                $("#data_final").prev(".asteristico").hide();

                $("#campo_data_inicial").removeClass("error");
                $("#campo_data_final").removeClass("error");
            }
        });

        $("#data_inicial").datepicker();
        $("#data_final").datepicker();
        $(".btn-ver").on("click", function(){
            var posicao = $(this).data("id");
            if( $(".tabela-"+posicao).is(":visible")){
              $(".tabela-"+posicao).hide();
            }else{
              $(".tabela-"+posicao).show();
            }
        });
    });
</script>
<style>
    #cke_descricao{
        width: 100% !important;
    }
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
    .table th, .table td {
        vertical-align: middle !important;
    }
    .btn {
        padding-bottom: 5px;
    }
    .titulo_coluna_interno th{
        background-color: #eee !important;
        font: bold 11px "Arial" !important;
        color: #222222 !important;
        text-align: center !important;
        padding: 10px  !important;
    }
</style>
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
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("numero_nf", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Número da nota fiscal</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span12" type="text" name="numero_nf" value="<?php echo $numero_nf;?>" id="numero_nf">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : "" ?>' id="campo_data_inicial">
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span12" type="text" name="data_inicial"  value="<?php echo $data_inicial;?>" id="data_inicial" />
                        </div>
                    </div>
                </div>
            </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : "" ?>' id="campo_data_final">
                        <label class='control-label' for='data_final'>Data Final</label>
                        <div class='controls controls-row'>
                            <div class='span6'>
                                <h5 class='asteristico'>*</h5>
                                 <input class="span12" type="text" value="<?php echo $data_final;?>" name="data_final" id="data_final" />
                            </div>
                        </div>
                    </div>
                </div>
            <div class="span2"></div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="submit" >Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='submit' />
        </p><br/>
    </form> <br />

</div>
<div class="container">
<?php  if (count($retorno) > 0) {?>
    <table class="table table-striped table-bordered table-fixed" style="margin: 0 auto;" >
        <thead>
            <tr class='titulo_coluna' >
                <th class='tac'></th>
                <th class='tac'>Nota Fiscal</th>
                <th class='tac'>Emissão</th>
                <th class='tac'>Pedido</th>
                <th class='tac'>Total Pedido</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($retorno as $key => $rows) {?>
            <tr>
                <td class='tac'>
                    <button class="btn btn-mini btn-primary btn-ver" data-id="<?php echo $key;?>" type="button">
                        <i class="icon-plus icon-white"></i>
                    </button>
                </td>
                <td class="tac"><b><?php echo $rows["nota_fiscal"];?></b></td>
                <td class="tac"><?php echo $rows["emissao"];?></td>
                <td class="tac"><a href="pedido_finalizado.php?pedido=<?php echo $rows["pedido"];?>" target="_blank"><b><?php echo $rows["pedido"];?></b></a></td>
                <td class="tac">R$ <?php echo $rows["total_pedido"];?></td>
            </tr>
            <tr class="tabela-<?php echo $key;?>" style="display: none;">
                <td colspan="5">
                    <table class="table table-bordered table-fixed">
                        <thead>
                            <tr class='titulo_coluna_interno' >
                                <th class='tal'>Titulo</th>
                                <th class='tac'>Vencimento</th>
                                <th class='tac' nowrap>Valor</th>
                                <th class='tac' nowrap>Nosso Número</th>
                                <th class='tac' nowrap>CNPJ Pag. / Benef.</th>
                                <th class='tac'>Banco</th>
                                <th class='tac'>Agencia</th>
                                <th class='tac'>Conta</th>
                                <th class='tac'></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows["boletos"] as $k => $row) {?>
                            <tr>
                                <td class='tal' nowrap><?php echo $row["titulo"];?></td>
                                <td class='tac'><?php echo $row["vencimento"];?></td>
                                <td class='tac' nowrap>R$ <?php echo $row["valor"];?></td>
                                <td class='tac'><?php echo $row["nosso_numero"];?></td>
                                <td class='tac'><?php echo $row["cnpj"];?></td>
                                <td class='tac' nowrap><?php echo $row["codigo_banco"] . ' - ' . $row["banco"];?></td>
                                <td class='tac'><?php echo $row["agencia"];?></td>
                                <td class='tac' nowrap><?php echo $row["conta"];?></td>
                                <td class='tac'><a href="<?php echo $links_banco[$row["codigo_banco"]];?>" target="_blank" title="Imprimir 2ª via do boleto" class="btn btn-success"><i class="icon-print icon-white"></i></a></td>
                            </tr>
                        <?php }?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php }?>

        </tbody>
    </table>
<?php 
    } else {
        echo "
        <div class='alert alert-warning'> 
        <h5>
            Nota Fiscal não encontrada ou não há títulos em aberto. <br />
            Para mais informações entre em contato com o Depto. Crédito Makita
        </h5>
        </div>";
    }
?>
</div>

<?php include 'rodape.php';?>
