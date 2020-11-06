<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include "autentica_admin.php";
include "funcoes.php";
use Lojavirtual\Loja;
$objLoja = new Loja();
$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_configuracao_forma_pagamento.php\">";

if ($_GET["editar_formas"] == true) {
    $xloja_b2b            = $_GET["xloja_b2b"];
    $configLojaPagamento  = $objLoja->getConfigLoja($xloja_b2b);
    $formas               = json_decode($configLojaPagamento["pa_forma_pagamento"], 1);
    $loja_escolhida       = $configLojaPagamento["loja_b2b"];
    $xmeio                = $_GET["xmeio"];

    foreach ($formas as $meio => $row) {
        foreach ($row as $nome_forma => $rows) {
            //cielo
            if ($nome_forma == "cielo") {
                $forma_cielo                  = "cielo";
                $nome_cielo                   = $rows["nome"];
                $ambiente_cielo               = $rows["ambiente"];
                $boleto_cielo                 = $rows["boleto"];
                $cartao_cielo                 = $rows["cartao"];
                $cartao_x_sem_juros_cielo     = $rows["cartao_x_sem_juros"];
                $merchant_id_producao_cielo   = $rows["merchant_id_producao"];
                $merchant_key_producao_cielo  = $rows["merchant_key_producao"];
                $merchant_id_sandbox_cielo    = $rows["merchant_id_sandbox"];
                $merchant_key_sandbox_cielo   = $rows["merchant_key_sandbox"];
                $url_req_producao_cielo       = $rows["url_req_producao"];
                $url_con_producao_cielo       = $rows["url_con_producao"];
                $url_req_sandbox_cielo        = $rows["url_req_sandbox"];
                $url_con_sandbox_cielo        = $rows["url_con_sandbox"];
                $status_cielo                 = $rows["status"];
                $bandeiras_cielo              = $rows["bandeiras"];
                $instrucao_boleto_cielo       = utf8_decode($rows["instrucao_boleto"]);
                $dias_vencimento_cielo        = $rows["dias_vencimento"];
            //maxipago
            } elseif ($nome_forma == "maxipago") {
                $forma_maxipago                  = "maxipago";
                $nome_maxipago                   = $rows["nome"];
                $ambiente_maxipago               = $rows["ambiente"];
                $boleto_maxipago                 = $rows["boleto"];
                $cartao_maxipago                 = $rows["cartao"];
                $cartao_x_sem_juros_maxipago     = $rows["cartao_x_sem_juros"];
                $merchant_id_producao_maxipago   = $rows["merchant_id_producao"];
                $merchant_key_producao_maxipago  = $rows["merchant_key_producao"];
                $merchant_id_sandbox_maxipago    = $rows["merchant_id_sandbox"];
                $merchant_key_sandbox_maxipago   = $rows["merchant_key_sandbox"];
                $bandeiras_maxipago              = $rows["bandeiras"];
                $instrucao_boleto_maxipago       = utf8_decode($rows["instrucao_boleto"]);
                $dias_vencimento_maxipago        = $rows["dias_vencimento"];
                $status_maxipago                 = $rows["status"];

            //pagseguro
            } elseif ($nome_forma == "pagseguro") {
                $forma_pagseguro                  = "pagseguro";
                $nome_pagseguro                   = $rows["nome"];
                $ambiente_pagseguro               = $rows["ambiente"];
                $boleto_pagseguro                 = $rows["boleto"];
                $cartao_pagseguro                 = $rows["cartao"];
                $cartao_x_sem_juros_pagseguro     = $rows["cartao_x_sem_juros"];
                $email_sandbox_pagseguro          = $rows["email_sandbox"];
                $token_sandbox_pagseguro          = $rows["token_sandbox"];
                $email_producao_pagseguro         = $rows["email_producao"];
                $token_producao_pagseguro         = $rows["token_producao"];
                $url_producao_ws_pagseguro        = $rows["url_producao_ws"];
                $url_sandbox_ws_pagseguro         = $rows["url_sandbox_ws"];
                $url_producao_js_pagseguro        = $rows["url_producao_js"];
                $url_sandbox_js_pagseguro         = $rows["url_sandbox_js"];
                $status_pagamento_pagseguro       = $rows["status_pagamento"];
                $status_pagseguro                 = $rows["status"];
            }
        }
    }
}


if ($_POST["btn_acao"] == "submit") {

    $msg_erro    = array();
    $msg_success = array();

    $loja_escolhida  = $_POST["loja_escolhida"];
    $forma_escolhida = $_POST["forma_escolhida"];

    if (strlen($loja_escolhida) == 0) {
        $msg_erro["msg"][] = "Escolha Fábrica (Loja)";
    }

    /*if (strlen($forma_escolhida) == 0) {
        $msg_erro["msg"][] = "Escolha uma Forma";
    }*/
    if (count($msg_erro["msg"]) == 0) {
        $retorno = $objLoja->gravaConfigPagamento($_POST);
        if ($retorno["erro"]) {
            $msg_erro["msg"][] = $retorno["msn"];
        } else {
            $msg_success["msg"][] = $retorno["msn"];
            echo $url_redir;
        }
    }
}

$layout_menu = "cadastro";
$title       = "Configuração Forma de Pagamento";

include "cabecalho_new.php";

$plugins = array(
    "shadowbox",
    "price_format",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    $(function () {
        Shadowbox.init();

        $("#forma_escolhida").change(function(){

            var forma = $(this).val();

            if (forma == '') {
                $("#pagseguro").hide();
            } else {
                $("#"+forma).show();
            }

        });
        $(".btn-ver-detalhe").click(function(){
            var xloja_b2b = $(this).data("id");
            var nome_forma = $(this).data("nome");
            Shadowbox.open({
                content: "loja_configuracao_forma_pagamento.php?ajax_ver_detalhes=true&xloja_b2b="+xloja_b2b+"&nome_forma="+nome_forma,
                player: "iframe",
                height: 600
            });
        });
    });
</script>

<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
    </div>
<?php }?>
<?php if (count($msg_success["msg"]) > 0){?>
    <div class="alert alert-success">
        <h4><?php echo implode("<br />", $msg_success["msg"]);?></h4>
    </div>
<?php }?>
<style>
    .tal{text-align: left;}
    ul.bandeiras{
        list-style-type: none;
    }
    ul.bandeiras li{
        display: inline-block;
        background: #fff;
        margin-bottom: 10px;
        border-radius: 5px;
    }
    ul.bandeiras li:hover{
        display: inline-block;
        background: #f5f5f5;
        margin-bottom: 10px;
        border-radius: 5px;
    }
    .tabs-left > .nav-tabs {
        float: left;
        margin-right: -1px !important;
        border-right: 1px solid #ddd !important;
    }
    .tab-content {
        overflow: hidden !important;
        margin-left: -23px !important;
        padding: 10px;
        border: solid 1px #ddd;
    }
</style>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php if ($_GET["editar_formas"] == true && strlen($xloja_b2b) > 0) {?>
            <input type="hidden" name="xloja_b2b" value="<?php echo $_GET["xloja_b2b"];?>">
        <?php }?>
        <div class='titulo_tabela '>Forma de Pagamento</div>
        <br/>
      
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label'>Fábrica (Loja)</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select class='span12' name="loja_escolhida" id="loja_escolhida">
                                <option value="">Selecione ...</option>
                                <?php foreach ($objLoja->getByLoja() as $key => $rows) {
                                    if ($rows["checkout"] == 'f') {
                                        continue;
                                    }
                                ?>
                                <option <?php echo ($rows["loja_b2b"] == $loja_escolhida) ? "selected" : "";?> value="<?php echo $rows["loja_b2b"];?>"><?php echo $rows["fabrica_nome"];?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div><br />
        <div class="tabbable tabs-left" style="background: #fff;margin:10px;padding:20px">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#cielo" data-toggle="tab">Cielo Ecommerce</a></li>
                <li><a href="#maxipago" data-toggle="tab">MaxiPago</a></li>
                <li><a href="#pagseguro" data-toggle="tab">PagSeguro</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="cielo">
                    <!-- INICIO CONFIGURAÇÃO DO CIELO -->
                        <div class='titulo_tabela '>Configuração da Cielo</div><br/>
                        <div class='row-fluid'>
                            <div class='span12 tac' align="center">
                                <?php echo "<img src='../loja/layout/img/pagamentos/cielo.png' />";?>

                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span7'>
                                <div class='control-group'>
                                    <label class='control-label'>Descrição</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="cielo[nome]" value="<?php echo (!empty($nome_cielo)) ? $nome_cielo : "Cielo";?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Ambiente</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="cielo[ambiente]">
                                                <option value="">Selecione ...</option>
                                                <option value="producao" <?php echo ($ambiente_cielo == "producao") ? "selected" : "";?>>Produção</option>
                                                <option value="sandbox" <?php echo ($ambiente_cielo == "sandbox") ? "selected" : "";?>>Sandbox (Teste)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span2'>
                                <div class='control-group'>
                                    <label class='control-label'>Habilitar</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="cielo[status]">
                                                <option value="">Selecione ...</option>
                                                <option value="1" <?php echo ($status_cielo == "1") ? "selected" : "";?>>Sim</option>
                                                <option value="0" <?php echo ($status_cielo == "0") ? "selected" : "";?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usa boleto</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="checkbox" name="cielo[boleto]" <?php echo ($boleto_cielo == 1) ? "checked" : "";?> value="1"> Sim
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usa Cartão Crédito</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="checkbox" name="cielo[cartao]" <?php echo ($cartao_cielo == 1) ? "checked" : "";?> value="1"> Sim
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Parcelas s/ juros no cartão</label>
                                    <div class='controls controls-row'>
                                        <div class='span9'>
                                            <select class='span12' name="cielo[cartao_x_sem_juros]">
                                                <option value="">Selecione ...</option>
                                                <?php for ($i=1; $i <= 12; $i++) { ?>
                                                <option value="<?php echo $i;?>" <?php echo ($cartao_x_sem_juros_cielo == $i) ? "selected" : "";?>><?php echo $i;?> s/ juros</option>
                                                <?php }?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT ID Produção</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_id_producao_cielo;?>" name="cielo[merchant_id_producao]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT KEY Produção</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_key_producao_cielo;?>" name="cielo[merchant_key_producao]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT ID Sandbox (teste)</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_id_sandbox_cielo;?>" name="cielo[merchant_id_sandbox]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT KEY Sandbox (teste)</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_key_sandbox_cielo;?>" name="cielo[merchant_key_sandbox]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url produção API Requisições</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_req_producao_cielo)) ? $url_req_producao_cielo : "https://api.cieloecommerce.cielo.com.br/";?>" name="cielo[url_req_producao]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url produção API Consultas</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_con_producao_cielo)) ? $url_con_producao_cielo : "https://apiquery.cieloecommerce.cielo.com.br/";?>" name="cielo[url_con_producao]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url sandbox (teste) API Requisições</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_req_sandbox_cielo)) ? $url_req_sandbox_cielo : "https://apisandbox.cieloecommerce.cielo.com.br";?>" name="cielo[url_req_sandbox]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url sandbox (teste) API Consultas</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_con_sandbox)) ? $url_con_sandbox : "https://apiquerysandbox.cieloecommerce.cielo.com.br";?>" name="cielo[url_con_sandbox]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span8'>
                                <label class='control-label'>Instruções para Boleto</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" class='span12' value="<?php echo $instrucao_boleto_cielo;?>" name="cielo[instrucao_boleto]" >
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <label class='control-label'>Dia Vencimento Boleto</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" class='span12' value="<?php echo $dias_vencimento_cielo;?>" name="cielo[dias_vencimento]" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span12'>
                                <div class='control-group'>
                                    <label class='control-label'>Cartões Aceitos</label>
                                    <div class='controls controls-row'>
                                        <ul class="bandeiras">
                                        <?php foreach ($bandeiras_cielo as $key => $rows) {?>
                                            <li> <input type="hidden" name="cielo[bandeiras][<?php echo $key;?>]" value="<?php echo $rows;?>" > <img src="../loja/layout/img/bandeiras/<?php echo strtolower($rows);?>.png" alt=""> </li>
                                        <?php }?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <!-- FIM CONFIGURAÇÃO DO CIELO -->
                </div>
                <div class="tab-pane" id="maxipago">
                    <!-- INICIO CONFIGURAÇÃO DO MAXIPAGO -->
                        <div class='titulo_tabela '>Configuração da MaxiPago</div><br/>
                        <div class='row-fluid'>
                            <div class='span12 tac' align="center">
                                <?php echo "<img src='../loja/layout/img/pagamentos/maxipago.png' />";?>

                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span7'>
                                <div class='control-group'>
                                    <label class='control-label'>Descrição</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="maxipago[nome]" value="<?php echo (!empty($nome_maxipago)) ? $nome_maxipago : "MaxiPago";?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Ambiente</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="maxipago[ambiente]">
                                                <option value="">Selecione ...</option>
                                                <option value="LIVE" <?php echo ($ambiente_maxipago == "LIVE") ? "selected" : "";?>>Produção</option>
                                                <option value="TEST" <?php echo ($ambiente_maxipago == "TEST") ? "selected" : "";?>>Sandbox (Teste)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span2'>
                                <div class='control-group'>
                                    <label class='control-label'>Habilitar</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="maxipago[status]">
                                                <option value="">Selecione ...</option>
                                                <option value="1" <?php echo ($status_maxipago == "1") ? "selected" : "";?>>Sim</option>
                                                <option value="0" <?php echo ($status_maxipago == "0") ? "selected" : "";?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usa boleto</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="checkbox" name="maxipago[boleto]" <?php echo ($boleto_maxipago == 1) ? "checked" : "";?> value="1"> Sim
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usa Cartão Crédito</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="checkbox" name="maxipago[cartao]" <?php echo ($cartao_maxipago == 1) ? "checked" : "";?> value="1"> Sim
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Parcelas s/ juros no cartão</label>
                                    <div class='controls controls-row'>
                                        <div class='span10'>
                                            <select class='span12' name="maxipago[cartao_x_sem_juros]">
                                                <option value="">Selecione ...</option>
                                                <?php for ($i=1; $i <= 12; $i++) { ?>
                                                <option value="<?php echo $i;?>" <?php echo ($cartao_x_sem_juros_maxipago == $i) ? "selected" : "";?>><?php echo $i;?> s/ juros</option>
                                                <?php }?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT ID Produção</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_id_producao_maxipago;?>" name="maxipago[merchant_id_producao]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT KEY Produção</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_key_producao_maxipago;?>" name="maxipago[merchant_key_producao]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT ID Sandbox (teste)</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_id_sandbox_maxipago;?>" name="maxipago[merchant_id_sandbox]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>MERCHANT KEY Sandbox (teste)</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' value="<?php echo $merchant_key_sandbox_maxipago;?>" name="maxipago[merchant_key_sandbox]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span8'>
                                <label class='control-label'>Instruções para Boleto</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" class='span12' value="<?php echo $instrucao_boleto_maxipago;?>" name="maxipago[instrucao_boleto]" >
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <label class='control-label'>Dia Vencimento Boleto</label>
                                <div class='controls controls-row'>
                                    <div class='span12'>
                                        <input type="text" class='span12' value="<?php echo $dias_vencimento_maxipago;?>" name="maxipago[dias_vencimento]" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span12'>
                                <div class='control-group'>
                                    <label class='control-label'>Cartões Aceitos</label>
                                    <div class='controls controls-row'>
                                        <ul class="bandeiras">
                                        <?php foreach ($bandeiras_maxipago as $key => $rows) {?>
                                            <li> <input type="hidden" name="maxipago[bandeiras][<?php echo $key;?>]" value="<?php echo $rows;?>" > <img src="../loja/layout/img/bandeiras/<?php echo strtolower($rows);?>.png" alt=""> </li>
                                        <?php }?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <!-- FIM CONFIGURAÇÃO DO MAXIPAGO -->
                </div>
                <div class="tab-pane" id="pagseguro">
                    <!-- INICIO CONFIGURAÇÃO DO PAGSEGURO -->
                        <div class='titulo_tabela '>Configuração do PagSeguro</div><br/>
                        <div class='row-fluid'>
                            <div class='span12 tac'  align="center">
                                <?php echo "<img src='../loja/layout/img/pagamentos/pagseguro.png' />";?>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span7'>
                                <div class='control-group'>
                                    <label class='control-label'>Descrição</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="pagseguro[nome]" value="<?php echo $nome_pagseguro;?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span3'>
                                <div class='control-group'>
                                    <label class='control-label'>Ambiente</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="pagseguro[ambiente]" id="ambiente">
                                                <option value="">Selecione ...</option>
                                                <option value="producao" <?php echo ($ambiente_pagseguro == "producao") ? "selected" : "";?>>Produção</option>
                                                <option value="sandbox" <?php echo ($ambiente_pagseguro == "sandbox") ? "selected" : "";?>>Sandbox (Teste)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span2'>
                                <div class='control-group'>
                                    <label class='control-label'>Habilitar</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select class='span12' name="pagseguro[status]">
                                                <option value="">Selecione ...</option>
                                                <option value="1" <?php echo ($status_pagseguro == "1") ? "selected" : "";?>>Sim</option>
                                                <option value="0" <?php echo ($status_pagseguro == "0") ? "selected" : "";?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usa boleto</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="checkbox" <?php echo ($boleto_pagseguro == 1) ? "checked" : "";?> name="pagseguro[boleto]" value="1"> Sim
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Usa Cartão Crédito</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="checkbox" name="pagseguro[cartao]" <?php echo ($cartao_pagseguro == 1) ? "checked" : "";?> value="1"> Sim
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span4'>
                                <div class='control-group'>
                                    <label class='control-label'>Parcelas s/ juros no cartão</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <select name="pagseguro[cartao_x_sem_juros]" id="cartao_x_sem_juros">
                                                <option value="">Selecione ...</option>
                                                <?php for ($j=1; $j <= 24; $j++) { ?>
                                                <option  value="<?php echo $j;?>" <?php echo (trim($cartao_x_sem_juros_pagseguro) == $j) ? "selected" : "";?>><?php echo $j;?> s/ juros</option>
                                                <?php }?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>E-mail Produção</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="pagseguro[email_producao]" value="<?php echo $email_producao_pagseguro;?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Token Produção</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="pagseguro[token_producao]" value="<?php echo $token_producao_pagseguro;?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>E-mail Sandbox (teste)</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="pagseguro[email_sandbox]" value="<?php echo $email_sandbox_pagseguro;?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Token Sandbox (teste)</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" class='span12' name="pagseguro[token_sandbox]" value="<?php echo $token_sandbox_pagseguro;?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url produção API</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_producao_ws_pagseguro)) ? $url_producao_ws_pagseguro : "https://ws.pagseguro.uol.com.br/v2/";?>" name="pagseguro[url_producao_ws]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url produção API JS</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_producao_js_pagseguro)) ? $url_producao_js_pagseguro : "https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/";?>" name="pagseguro[url_producao_js]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url sandbox (teste) API</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_sandbox_ws_pagseguro)) ? $url_sandbox_ws_pagseguro : "https://ws.sandbox.pagseguro.uol.com.br/v2/";?>" name="pagseguro[url_sandbox_ws]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Url sandbox (teste) API JS</label>
                                    <div class='controls controls-row'>
                                        <div class='span12'>
                                            <input type="text" readonly class='span12' value="<?php echo (!empty($url_sandbox_js_pagseguro)) ? $url_sandbox_js_pagseguro : "https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/";?>" name="pagseguro[url_sandbox_js]" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='row-fluid'>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>Status Pagamento</label>
                                    <div class='controls controls-row'>
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][1]" value="Aguardando pagamento" ><br />
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][2]" value="Em análise" ><br />
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][3]" value="Paga" ><br />
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][4]" value="Disponí­vel" ><br />
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][5]" value="Em disputa" ><br />
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][6]" value="Devolvida" ><br />
                                        <input type="text" readonly class='span12' name="pagseguro[status_pagamento][7]" value="Cancelada" >
                                    </div>
                                </div>
                            </div>
                            <div class='span6'>
                                <div class='control-group'>
                                    <label class='control-label'>&nbsp;</label>
                                    <div class='controls controls-row'>
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[8])) ? $status_pagamento_pagseguro[8] : "";?>" name="pagseguro[status_pagamento][8]"><br />
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[9])) ? $status_pagamento_pagseguro[9] : "";?>" name="pagseguro[status_pagamento][9]"><br />
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[10])) ? $status_pagamento_pagseguro[10] : "";?>" name="pagseguro[status_pagamento][10]"><br />
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[11])) ? $status_pagamento_pagseguro[11] : "";?>" name="pagseguro[status_pagamento][11]"><br />
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[12])) ? $status_pagamento_pagseguro[12] : "";?>" name="pagseguro[status_pagamento][12]"><br />
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[13])) ? $status_pagamento_pagseguro[13] : "";?>" name="pagseguro[status_pagamento][13]"><br />
                                        <input type="text" class='span12' value="<?php echo (!empty($status_pagamento_pagseguro[14])) ? $status_pagamento_pagseguro[14] : "";?>" name="pagseguro[status_pagamento][14]">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <!-- FIM CONFIGURAÇÃO DO PAGSEGURO -->
                </div>
            </div>
        </div>
        <p><br/>
                <button class='btn btn-primary' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                <a href="loja_configuracao_forma_pagamento.php" class="btn">Listagem</a>
            </p><br/>
    </form> <br />
    <table class="table table-bordered table-striped table-hover table-fixed">
        <thead>
            <tr class='titulo_coluna' >
                <th>FORMA DE PAGAMENTO</th>
                <th class="tal">LOJA (FÁBRICA)</th>
                <th>AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($objLoja->getAllConfigLoja() as $key => $configLojaPagamento) {
                $formas = json_decode($configLojaPagamento["pa_forma_pagamento"], 1);
                $dadosLoja = $objLoja->getByLoja($configLojaPagamento["loja_b2b"]);
            ?>
            <tr>
                <td class="tac">
                    <?php 
                        foreach ($formas["meio"] as $kforma => $forma) {
                            if ($forma["status"] <> "1") {
                                continue;
                            }
                            echo "<img width='100' src='../loja/layout/img/pagamentos/{$kforma}.png' />";
                        }
                    ?>
                </td>
                <td class="tal"><?php echo $dadosLoja["fabrica_nome"];?></td>
                <td class="tac">
                    <a href="loja_configuracao_forma_pagamento.php?editar_formas=true&xloja_b2b=<?php echo $configLojaPagamento["loja_b2b"];?>"  class="btn btn-warning btn-mini"><i class="icon-edit icon-white"></i> Editar</a>
                </td>
            </tr>
            <?php }?>
        </tbody>
    </table>
<?php  include "rodape.php"; ?>