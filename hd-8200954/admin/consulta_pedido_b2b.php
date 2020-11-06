<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    $admin_privilegios = "cadastros";
    include_once('autentica_admin.php');
    include_once('funcoes.php');

} else {
    include_once('autentica_usuario.php');
    include_once('funcoes.php');
}

use Lojavirtual\Loja;
use Lojavirtual\CarrinhoCompra;
use Lojavirtual\Checkout;

$objCarrinhoCompra = new CarrinhoCompra();
$objCheckout = new Checkout();
$objLoja = new Loja();
$configLoja = $objLoja->getConfigLoja();

$configLojaPagamento = json_decode($configLoja["pa_forma_pagamento"], 1);
$status_pagamento = array();
if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1") {
    $status_pagamento = $configLojaPagamento["meio"]["pagseguro"]["status_pagamento"];
}
if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1") {

    $status_pagamento_cartao = $objCheckout->status_cielo_cartao_credito;
}

if (isset($configLojaPagamento["meio"]["maxipago"]) && $configLojaPagamento["meio"]["maxipago"]["status"] == "1") {
    $status_pagamento_boleto = $objCheckout->status_maxipago;
}

if ($_POST["btn_acao"] == "submit") {
    $dadosPedidos  = array();
    $data_inicial  = $_POST['data_inicial'];
    $data_final    = $_POST['data_final'];
    $pedido_status = $_POST['pedido_status'];
    $pedido_status_pagamento = $_POST['pedido_status_pagamento'];
    $posto_nome    = $_POST['nome_posto'];
    $posto_codigo  = $_POST['codigo_posto'];
    $numero_pedido = $_POST['numero_pedido'];

    if (empty($numero_pedido)){
        if (empty($data_inicial)) {
            $msg_erro['msg'][] = "Preencha os campos obrigatórios!";
            $msg_erro['campos'][] = "data_inicial";
        }

        if (empty($data_final)) {
            $msg_erro['msg'][] = "Preencha os campos obrigatórios!";
            $msg_erro['campos'][] = "data_final";
        }

        if (count($msg_erro['msg']) == 0){
            list($dia, $mes, $ano) = explode("/", $data_inicial);
            $aux_data_inicial      = $ano."-".$mes."-".$dia;

            list($dia, $mes, $ano) = explode("/", $data_final);
            $aux_data_final        = $ano."-".$mes."-".$dia;
        }
    } 
    
    if ($areaAdmin === false) {
        $posto_codigo = $login_codigo_posto;
    }

    if (strlen($posto_codigo) > 0) {

        $sqlPosto = "SELECT posto 
                       FROM tbl_posto_fabrica 
                      WHERE fabrica = {$login_fabrica} 
                        AND codigo_posto='{$posto_codigo}'";
        $resPosto = pg_query($con, $sqlPosto);

        if (pg_last_error($con)) {
            $msg_erro['msg'][] = "Erro ao buscar posto";
        } else {
            $posto = pg_fetch_result($resPosto, 0, 'posto');
        }
    }
    if (count($msg_erro['msg']) == 0) {

        if (!empty($numero_pedido)) {
            $condicoes["numero_pedido"] = $numero_pedido;
        }

        if (!empty($aux_data_inicial)) {
            $condicoes["data_inicial"] = $aux_data_inicial;
        }

        if (!empty($aux_data_final)) {
            $condicoes["data_final"] = $aux_data_final;
        }

        if (!empty($pedido_status)) {
            $condicoes["pedido_status"] = $pedido_status;
        }
        if (!empty($pedido_status_pagamento)) {
            $condicoes["pedido_status_pagamento"] = $pedido_status_pagamento;
        }
        if (!empty($posto)) {
            $condicoes["posto"] = $posto;
        }

        if ($login_fabrica == 42) {
            $dadosPedidos = $objCarrinhoCompra->getAllCarrinhoPedido($condicoes);
        } else {
            $dadosPedidos = $objCarrinhoCompra->getAllPedidoB2B($condicoes);
        }

        if (isset($dadosPedidos["erro"]) && !empty($dadosPedidos["erro"])) {
            $msg_erro['msg'][] = $dadosPedidos['msn'];
        } else {

        }
    } 

}

$layout_menu = "cadastro";
$title       = "Consulta de Pedido B2B - Loja Virtual";
if ($areaAdmin === true) {
    include __DIR__.'/cabecalho_new.php';
} else {
    include __DIR__.'/../cabecalho_new.php';
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

        $("#numero_pedido").change(function() {
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


        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    });
    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }
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
</style>
    <?php
        if (empty($objLoja->_loja)) {
            exit('<div class="alert alert-error"><h4>Loja não encontrada.</h4></div>');
        }
    ?>
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
                <div class='control-group <?=(in_array("numero_pedido", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Número do Pedido</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <input class="span12" type="text" name="numero_pedido" value="<?php echo $numero_pedido;?>" id="numero_pedido">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group' id="campo_data_inicial">
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
                    <div class='control-group' id="campo_data_final">
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
        <?php if ($areaAdmin === true) {?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group" id="campo_cod_posto">
                    <label class="control-label" for="">Código Posto</label>
                    <div class="controls controls-row">
                        <div class='span7 input-append'>
                            <INPUT class='span12' TYPE="text" class="frm" NAME="codigo_posto" id="codigo_posto" value='<?= $_REQUEST['codigo_posto'] ?>'>
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group" id="campo_nome_posto">
                    <label class="control-label" for="">Nome Posto</label>
                    <div class="controls controls-row">
                        <div class="span7 input-append">
                            <INPUT TYPE="text" class="frm" NAME="nome_posto" id="descricao_posto" value="<?= $_REQUEST['nome_posto'] ?>">
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <?php }

        if ($login_fabrica != 42) {
        ?>
            <div class='row-fluid'>
                <div class='span2'></div>
               
                <div class="span4">
                    <label class="control-label" for="">Status do Pedido</label> 
                    <div class="controls controls-row">
                        <select name="pedido_status" id="pedido_status">
                            <option value='' selected>Selecione ...</option>
                            <?php 
                                $sql = "SELECT DISTINCT tbl_status_pedido.status_pedido,
                                                        tbl_status_pedido.descricao
                                          FROM tbl_pedido
                                          JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                                         WHERE fabrica = $login_fabrica";

                                $res = pg_query($con,$sql);
                                if (pg_num_rows($res) > 0) {
                                    for ($i=0; $i < pg_num_rows($res); $i++) { 
                                        $status_pedido = pg_fetch_result($res, $i, 'status_pedido');
                                        $descricao = pg_fetch_result($res, $i, 'descricao');
                            ?>
                            <option value='<?php echo $status_pedido;?>' <?php echo ($pedido_status == $status_pedido) ? "selected" : "" ;?>><?php echo $descricao;?></option>
                            <?php }}?>
                        </select>
                    </div>
                </div>
                <div class="span4">
                    <label class="control-label" for="">Status do Pagamento</label> 
                    <div class="controls controls-row">
                        <select name="pedido_status_pagamento" id="pedido_status_pagamento">
                            <option value='' selected>Selecione ...</option>
                            <?php 
                                if (!empty($status_pagamento)) {
                                    foreach ($status_pagamento as $indice => $value) {
                            ?>
                            <option value='<?php echo $indice;?>' <?php echo ($pedido_status_pagamento == $indice) ? "selected" : "" ;?>><?php echo utf8_decode($value);?></option>
                            <?php }}?>
                        </select>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
        <?php
        }
        ?>
        <p><br/>
            <button class='btn' id="btn_acao" type="submit" >Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='submit' />
        </p><br/>
    </form> <br />

</div>
<?php
    if (count($dadosPedidos) && count($msg_erro['msg']) == 0 && !isset($dadosPedidos['naoencontrado'])) {

?>
<script>
    $(function(){
            $.dataTableLoad({ table: "#resultado_pesquisa" });

    })
</script>
    <table id="resultado_pesquisa" class="table table-striped table-bordered table-hover table-fixed" style="margin: 0 auto;" >
        <thead>
            <tr class='titulo_coluna' >
                <th class='tac'>Pedido</th>
                <th class='tal'>Data</th>
                <?php
                if ($login_fabrica == 42) { ?>
                    <th class='tal'>Fornecedor</th>
                <?php
                } ?>
                <th class='tal'>Posto</th>
                <th class='tac'>Total Pedido</th>
                <?php if ($objLoja->_usacheckout == 'S') {?>
                <th class='tac'>Tipo Pagamento</th>
                <th class='tac'>Status Pagamento</th>
                <?php }

                if ($login_fabrica != 42) {
                ?>
                    <th class='tac'>Status</th>
                    <?php
                }
                ?>
                <th class='tac' width="10%">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($login_fabrica != 42) {
            foreach ($dadosPedidos as $key => $rows) {
                $total_pedido = array();
                foreach ($rows["itens"] as $ki => $rowsiten) {
                    $total_pedido[] = $rowsiten["qtde"]*$rowsiten["preco"];
                }
                if ($areaAdmin === true) {
                    $link_impressao = "imprimir_pedido_b2b.php?pedido=".$rows["pedido"];
                } else {
                    $link_impressao = "loja/print/imprimir_pedido.php?pedido=".$rows["pedido"];
                }

        ?>
        <tr>
            <td class='tac'>
                <a href="<?php echo $link_impressao;?>" target="_blank" class="btn btn-link" title="Imprimir Pedido">
                    <?php echo $rows["pedido"];?>
                </a>
            </td>
            <td class='tal'><?php echo $rows["data_pedido"];?></td>
            <td class='tal'><?php echo $rows["dadosposto"]["nome_posto"];?></td>
            <td class='tac'><?php echo 'R$ '.number_format(array_sum($total_pedido), 2, ',', '.');?></td>
            <?php if ($objLoja->_usacheckout == 'S') {?>
            <td class='tac'>
                <?php 
                    if ($rows["dados_pagamento"]["tipo_pagamento"] == "B") {
                        echo "Boleto";
                    } 
                    if ($rows["dados_pagamento"]["tipo_pagamento"] == "C") {
                        echo "Cartão de Crédito" ;
                    } 

                ?>
            </td>
            <td class='tac'>
                <?php 
                    if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1") {
                        echo $status_pagamento[$rows["dados_pagamento"]["status_pagamento"]];
                    } else {
                        if ($rows["dados_pagamento"]["tipo_pagamento"] == "B") {
                            echo $status_pagamento_boleto[$rows["dados_pagamento"]["status_pagamento"]];
                        }
                        if ($rows["dados_pagamento"]["tipo_pagamento"] == "C") {
                            echo $status_pagamento_cartao[$rows["dados_pagamento"]["status_pagamento"]];
                        }
                    }
                ?>
            </td>
            <?php }?>
            <td class='tac'>
                <?php echo $rows["status"]["descricao"];?>
            </td>
            <td class='tac'>
                <a href="<?php echo $link_impressao;?>" target="_blank" class="btn btn-info btn-mini" title="Imprimir Pedido"><i class="icon-print icon-white"></i> Imprimir Pedido</a>
            </td>
        </tr>
        <?php 
            }
        } else {
            foreach ($dadosPedidos as $key => $rows) {
                $total_pedido = array();
                foreach ($rows["itens"] as $ki => $rowsiten) {
                    $total_pedido[] = ($rowsiten["qtde"]*$rowsiten["valor_unitario"]);
                }
                if ($areaAdmin === true) {
                    $link_impressao = "imprimir_pedido_b2b.php?pedido=".$rows["loja_b2b_carrinho"];
                } else {
                    $link_impressao = "loja/print/imprimir_pedido.php?pedido=".$rows["loja_b2b_carrinho"];
                }

            ?>
            <tr>
                <td class='tac'>
                    <a href="<?php echo $link_impressao;?>" target="_blank" class="btn btn-link" title="Imprimir Pedido">
                        <?php echo $rows["loja_b2b_carrinho"];?>
                    </a>
                </td>
                <td class='tac'><?php echo $rows["data_pedido"];?></td>
                <td class='tal'><?php echo $rows["nome_fornecedor"]; ?></td>
                <td class='tal'><?php echo $rows["dadosposto"]["nome_posto"];?></td>
                <td class='tac'><?php echo 'R$ '.number_format(array_sum($total_pedido) + abs($rows["itens"][0]['total_frete']), 2, ',', '.');?></td>
                <td class='tac'>
                    <a href="<?php echo $link_impressao;?>" target="_blank" class="btn btn-info btn-mini" title="Imprimir Pedido"><i class="icon-print icon-white"></i> Imprimir Pedido</a>
                </td>
            </tr>
        <?php 
            }
        }?>
        </tbody>
    </table>
<?php } else {?>
    <div class="container">
        <div class="alert alert-important">
            <p>Nenhum pedido encontrado.</p>
        </div>
    </div>
<?php }?>
<?php include 'rodape.php';?>