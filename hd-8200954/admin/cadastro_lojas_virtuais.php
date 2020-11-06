<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\Loja;
$objLoja   = new Loja($login_fabrica, $con);

if($login_fabrica <> 10){
    header("Location: menu_cadastro.php");
    exit;
}

if ($_POST["btn_acao"] == "submit") {
    $xxfabricas = $_POST['xxfabrica'];
    $ativo      = (!empty($_POST['ativo'])) ? $_POST['ativo'] : "f";
    $externa    = (!empty($_POST['externa'])) ? $_POST['externa'] : "f";
    $sucesso    = false;
    $erro       = false;
    $dataSave   = array();

    if (count($xxfabricas) == 0) {
        $msg_erro["msg"][]    = "Escolha pelo menos uma Fábrica";
        $msg_erro["campos"][] = "fabrica";
        $erro = true;
    }

    if (count($msg_erro) == 0) {

        foreach ($xxfabricas as $xxxfabrica) {

            $res = pg_query($con,"BEGIN");

            $dataSave = array(
                            "fabrica"   => $xxxfabrica,
                            "checkout"  => $checkout,
                            "ativo"     => $ativo,
                            "externa"   => $externa,
                         );

            if (!empty($objLoja)) {
                $retorno = $objLoja->criaLoja($dataSave);

                if ($retorno["erro"]) {
                    $erro = true;
                    $msg_erro["msg"][] = $retorno["msn"];
                    $res  = pg_query($con,"ROLLBACK");
                } else {
                    $sucesso = true;
                    $res     = pg_query($con,"COMMIT");
                }
            }

        }
    }
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE LOJAS VIRTUAIS";
include 'cabecalho_new.php';

$plugins = array(
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>

<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
    });
</script>

<?php if ($erro) {?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
    </div>
<?php } ?>

<?php if ($sucesso) {?>
    <div class="alert alert-success">
        <h4>Loja criada com sucesso</h4>
    </div>
<?php }?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Cadastro</div>
    <br/>
    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("fabrica", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='xxfabrica'>Fábrica</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                        <select name="xxfabrica[]" id="xxfabrica" class="multiple" multiple="multiple" >
                            <?php
                                $sql = "SELECT fabrica, nome
                                          FROM tbl_fabrica
                                         WHERE ativo_fabrica
                                      ORDER BY nome";
                                $res = pg_query($con,$sql);

                                foreach (pg_fetch_all($res) as $key) {
                                    $selected_fabrica = ( isset($fabrica) and ($xfabrica == $key['fabrica']) ) ? "SELECTED" : '' ;
                            ?>
                                <option value="<?php echo $key['fabrica']?>" <?php echo $selected_fabrica ?> ><?php echo $key['nome']?></option>
                            <?php }?>
                        </select>
                        <div><strong></strong></div>
                    </div>  
                </div>
            </div>
        </div>
        <div class='span1'>
            <div class='control-group'>
                <label class='control-label' for='ativo'>Status</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                    <input type="checkbox" name="ativo" checked id="ativo" value="t" > Ativa
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label' for='checkout'>Integração de Pagamento</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="checkbox" name="checkout" id="checkout" value="t" > Sim
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='externa'>B2B externo</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="checkbox" name="externa" id="externa" value="t" > Sim
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
    $lojasCadastradas = $objLoja->getByLoja();
    if ($lojasCadastradas["erro"]) {
        echo '<div class="alert alert-error"><h4>'.$lojasCadastradas["msn"].'</h4></div>';
    } else {
?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th class='tac'>ID Loja</th>
                <th class='tac'>Cadastrada em</th>
                <th class='tal'>Fábrica</th>
                <th>Integração de Pagamento</th>
                <th>Externo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
                foreach ($lojasCadastradas as $key => $dadosLoja) {
                    $data_cadastro  = $dadosLoja["data_input"];
                    $externa        = ($dadosLoja["externa"] == 't') ? '<span class="label label-info">Sim</span>' : '<span class="label label-important">Não</span>';
                    $fabrica        = $dadosLoja["fabrica"] . " - " . $dadosLoja["fabrica_nome"];
                    $checkout       = ($dadosLoja["checkout"] == 't')   ? '<span class="label label-success">Sim</span>' : '<span class="label label-important">Não</span>';
                    $ativo          = ($dadosLoja["ativo"] == 't')   ? '<span class="label label-success">Ativo</span>' : '<span class="label label-important">Inativo/span>';
                    $loja           = $dadosLoja["loja_b2b"];
            ?>
            <tr>
                <td class='tac'><?php echo $loja;?></td>
                <td class='tac'><?php echo $data_cadastro;?></td>
                <td class='tal'><?php echo $fabrica;?></td>
                <td class='tac'><?php echo $checkout;?></td>
                <td class='tac'><?php echo $externa;?></td>
                <td class='tac'><?php echo $ativo;?></td>
            </tr>
            <?php }?>
        </tbody>
    </table>
<?php }?>
</div>
<?php include 'rodape.php';?>
