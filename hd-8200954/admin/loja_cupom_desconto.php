<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\CupomDesconto;

$objCupomDesconto  = new CupomDesconto();
$dadosCupomDesconto = $objCupomDesconto->get();

$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_cupom_desconto.php\">";

if ($_GET["acao"] == "delete" && $_GET["loja_b2b_cupom_desconto"] > 0) {
    $loja_b2b_cupom_desconto = $_GET['loja_b2b_cupom_desconto'];

    if (empty($loja_b2b_cupom_desconto)) {
        $msg_erro["msg"][]    = "Cupom não encontrado!";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if (!empty($objCupomDesconto)) {
            $retorno = $objCupomDesconto->delete($loja_b2b_cupom_desconto);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Cupom removido com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            echo $url_redir;
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_GET["acao"] == "edit" && $_GET["loja_b2b_cupom_desconto"] > 0) {
    
    $loja_b2b_cupom_desconto = $_GET['loja_b2b_cupom_desconto'];
    $dataCupom      = $objCupomDesconto->get($loja_b2b_cupom_desconto);
    $descricao      = $dataCupom['descricao'];
    $codigo_cupom   = $dataCupom['codigo_cupom'];
    $data_validade  = $dataCupom['data_validade'];
    list($ano, $mes, $dia) = explode("-", $data_validade);
    $data_validade  = $dia . "/" . $mes . "/" . $ano;
    $desconto       = $dataCupom['desconto'];
    $qtde_cupom     = $dataCupom['qtde_cupom'];
    $limite_cupom   = $dataCupom['limite_cupom'];
    $tipo_acao   = "edit";

}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  != "edit") {
    $descricao      = $_POST['descricao'];
    $codigo_cupom   = $_POST['codigo_cupom'];
    $data_validade  = $_POST['data_validade'];
    list($dia, $mes, $ano) = explode("/", $data_validade);
    $xdata_validade = $ano . "-" . $mes . "-" . $dia;
    $desconto       = $_POST['desconto'];
    $qtde_cupom     = $_POST['qtde_cupom'];
    $limite_cupom   = $_POST['limite_cupom'];
 
    if (empty($descricao) || empty($codigo_cupom) || empty($data_validade) || empty($desconto) || empty($qtde_cupom) || empty($limite_cupom)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
        $msg_erro["campos"][] = "codigo_cupom";
        $msg_erro["campos"][] = "data_validade";
        $msg_erro["campos"][] = "desconto";
        $msg_erro["campos"][] = "qtde_cupom";
        $msg_erro["campos"][] = "limite_cupom";
    }
 
    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                            "descricao"  => $descricao,
                            "codigo_cupom"  => $codigo_cupom,
                            "data_validade"  => $xdata_validade,
                            "desconto"  => $desconto,
                            "qtde_cupom"  => $qtde_cupom,
                            "limite_cupom"  => $limite_cupom,
                     );

        if (!empty($objCupomDesconto)) {
            $retorno = $objCupomDesconto->save($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Cupom cadastrado com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $descricao      = $_POST['descricao'];
            $codigo_cupom   = $_POST['codigo_cupom'];
            $data_validade  = $_POST['data_validade'];
            $desconto       = $_POST['desconto'];
            $qtde_cupom     = $_POST['qtde_cupom'];
            $limite_cupom   = $_POST['limite_cupom'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }

}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  == "edit") {

    $loja_b2b_cupom_desconto      = $_POST['loja_b2b_cupom_desconto'];
    $descricao      = $_POST['descricao'];
    $codigo_cupom   = $_POST['codigo_cupom'];
    $data_validade  = $_POST['data_validade'];
    list($dia, $mes, $ano) = explode("/", $data_validade);
    $xdata_validade = $ano . "-" . $mes . "-" . $dia;
    $desconto       = $_POST['desconto'];
    $qtde_cupom     = $_POST['qtde_cupom'];
    $limite_cupom   = $_POST['limite_cupom'];
 
    if (empty($descricao) || empty($codigo_cupom) || empty($data_validade) || empty($desconto) || empty($qtde_cupom) || empty($limite_cupom)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
        $msg_erro["campos"][] = "codigo_cupom";
        $msg_erro["campos"][] = "data_validade";
        $msg_erro["campos"][] = "desconto";
        $msg_erro["campos"][] = "qtde_cupom";
        $msg_erro["campos"][] = "limite_cupom";
    }
 
    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                            "loja_b2b_cupom_desconto"  => $loja_b2b_cupom_desconto,
                            "descricao"  => $descricao,
                            "codigo_cupom"  => $codigo_cupom,
                            "data_validade"  => $xdata_validade,
                            "desconto"  => $desconto,
                            "qtde_cupom"  => $qtde_cupom,
                            "limite_cupom"  => $limite_cupom,
                     );

        if (!empty($objCupomDesconto)) {
            $retorno = $objCupomDesconto->update($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Cupom atualizado com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $loja_b2b_cupom_desconto = $_POST['loja_b2b_cupom_desconto'];
            $descricao      = $_POST['descricao'];
            $codigo_cupom   = $_POST['codigo_cupom'];
            $data_validade  = $_POST['data_validade'];
            $desconto       = $_POST['desconto'];
            $qtde_cupom     = $_POST['qtde_cupom'];
            $limite_cupom   = $_POST['limite_cupom'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}
$layout_menu = "cadastro";
$title = "Cupom de Desconto - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "price_format",
    "datepicker",
    "mask",
    "multiselect"
);

include("plugin_loader.php");
?>
<script language="javascript">
    $(function() {
        var hoje = '<?php echo date("d/m/Y");?>';
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
        $("#desconto").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });
        $('#data_validade').datepicker({
                minDate: hoje
        });
        $('[data-toggle="popover"]').popover(); 
    });

</script>
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
</style>
    <?php
        if (empty($objCupomDesconto->_loja)) {
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
        <?php if ($tipo_acao == "edit") {?>
        <input type="hidden" name="tipo_acao" value="edit">
        <input type="hidden" name="loja_b2b_cupom_desconto" value="<?php echo $loja_b2b_cupom_desconto;?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Nome do Cupom</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" placeholder="EX: Promoção Queima de Estoque" name="descricao" value="<?php echo $descricao;?>" id="descricao">
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("codigo_cupom", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Código do Cupom</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" placeholder="EX: QUEIMAESTOQUE2018" name="codigo_cupom" value="<?php echo $codigo_cupom;?>" id="codigo_cupom">
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span2'>
                <div class='control-group <?=(in_array("data_validade", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Data de Validade</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <span class="add-on"><i class="icon-calendar"></i></span>
                                <input class="span11" type="text" name="data_validade" value="<?php echo $data_validade;?>" id="data_validade">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("desconto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Desconto em R$</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <span class="add-on">R$</span>
                                <input class="span10" type="text" name="desconto" value="<?php echo number_format($desconto, 2, '.', '');?>" id="desconto">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("qtde_cupom", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Qtde de Cupom <i class="icon-question-sign" data-toggle="popover" data-placement="top" data-content="Quantidade de cupom disponível para ser utilizado nessa promoção." title="" data-original-title="Qtde de Cupom"></i></label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" name="qtde_cupom" value="<?php echo $qtde_cupom;?>" id="qtde_cupom">
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("limite_cupom", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Limite de Cupom <i class="icon-question-sign" data-toggle="popover" data-html="true" data-placement="top" data-content="Limite de utilização do cupom por cliente.<br /> Ex: 1 <br /> Observação: Cada cliente só poderá utilizar uma vez esse cupom." title="" data-original-title="Limite de Cupom"></i></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $limite_cupom;?>" name="limite_cupom" id="limite_cupom">
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
    </form> <br />

    <?php
        if ($dadosCupomDesconto["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosCupomDesconto["msn"].'</h4></div>';
        } else {
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th align="left">Descrição Cupom</th>
                <th align="left">Código do Cupom</th>
                <th class='tac'>Data Validade</th>
                <th class='tac'>Desconto</th>
                <th class='tac'>Utilizado</th>
                <th class='tac'>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($dadosCupomDesconto as $kCupomDesconto => $rowsCupomDesconto) {
                list($ano, $mes, $dia) = explode("-", $rowsCupomDesconto["data_validade"]);
                $xdata_validade = $dia . '/' . $mes . '/' . $ano;
                ?>
            <tr>
                <td class='tal'><?php echo $rowsCupomDesconto["descricao"];?></td>
                <td class='tal'><?php echo $rowsCupomDesconto["codigo_cupom"];?></td>
                <td class='tac'><?php echo $xdata_validade;?></td>
                <td class='tac'><?php echo 'R$ '.number_format($rowsCupomDesconto["desconto"], 2, ',', '.');?></td>
                <td class='tac'>
                    <?php echo ($rowsCupomDesconto["status"] == 't') ? '<span class="label label-success">Ativo</span>' : '<span class="label label-important">Inativo</span>';?>

                </td>
                <td class='tac'>
                    <a href="loja_cupom_desconto.php?acao=edit&loja_b2b_cupom_desconto=<?php echo $rowsCupomDesconto["loja_b2b_cupom_desconto"];?>"  class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
                    <a onclick="if (confirm('Deseja remover este registro?')) window.location='loja_cupom_desconto.php?acao=delete&loja_b2b_cupom_desconto=<?php echo $rowsCupomDesconto["loja_b2b_cupom_desconto"];?>';return false;" href="#" class="btn btn-danger btn-mini" title="Remover"><i class="icon-remove icon-white"></i></a>
                </td>
            </tr>
            <?php }?>
        </tbody>
    </table>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
