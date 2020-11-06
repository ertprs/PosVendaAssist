<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\Produto;

$objProduto = new Produto();
$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_tabela_preco.php\">";

function trataValor($valor) {
    $valor = str_replace(array("R$ ", "R$"), "", strtoupper(trim($valor)));
    $valorInicial = explode(".", $valor);

    if (count($valorInicial) > 2) {
        $valorTratado  = ''; 
        $valorFinal = $valorInicial[count($valorInicial)-1];
        foreach ($valorInicial as $key => $value) {
            if ($value == $valorFinal) {
                $valorTratado .= "," . $value;
            } else {
                $valorTratado .= $value;
            }
        }
    } else {
        $valorTratado     = str_replace(array("R$ ", "."), "", $valor);
    }
    $valorTratado     = str_replace(",", ".", $valorTratado);
    return $valorTratado;

}
$upload_tabela = $_GET["upload_tabela"];
//UPLOAD DE TABELA
if(!empty($_POST["grava_upload_tabela"])) {
    $msg_erro = "";

    $registro    = array();
    $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["upload"]["name"]));

    if (empty($_FILES["upload"]["name"])) {
        $msg_erro .= "Selecione um Arquivo <br />";
    }

    if (!in_array(strtolower($extensao), array("csv", "txt"))) {
        $msg_erro .= "Formado de arquivo inválido <br />";
    }

    $arquivo = fopen($_FILES['upload']['tmp_name'], 'r+');

    if ($arquivo && strlen($msg_erro) == 0) {

        while(!feof($arquivo)){

            $linha = fgets($arquivo,4096);
            
            if (strlen(trim($linha)) > 0) {
                $registro[] = explode(";", $linha);
            }

        }

        fclose($f);
    }
    $retorno = array();
    $sucesso_ao_atualizar = array();
    if (count($registro) > 0 && strlen($msg_erro) == 0) {
        foreach ($registro as $key => $rows) {

            $REFERENCIA     = trim($rows[0]);
            $QUANTIDADE     = trataValor($rows[1]);

            if ($QUANTIDADE <= 0) {
                $erro_produto_nao_encontrado[] = $REFERENCIA;
                continue;
            }

        	$validaProduto = $objProduto->getPecaLojaByRef($REFERENCIA);
        	if ($validaProduto["erro"] || empty($validaProduto)) {
        		$erro_produto_nao_encontrado[] = $REFERENCIA;
        		continue;
        	}
            $res = pg_query($con,"BEGIN TRANSACTION");

            if (strlen($REFERENCIA) > 0) {
                $retornoProduto = $objProduto->atualizaEstoque($validaProduto["codigo_peca"], $QUANTIDADE);

                if ($retornoProduto["erro"]) {

                    $erro_ao_atualizar[] = $validaProduto["ref_peca"] . ' - ' .$validaProduto["nome_peca"];

                }  else {

                    $sucesso_ao_atualizar[] = $validaProduto["ref_peca"] . ' - ' .$validaProduto["nome_peca"];;

                }   
            }

            if (strlen($erro_ao_atualizar) == 0 ) {
                $res = pg_query($con,"COMMIT TRANSACTION");
                $msg_sucesso = true;
            } else {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                $msg_sucesso = false;
            }
        }

    } 
}

$layout_menu = "cadastro";
$title = "Carga de Estoque - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
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
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
    });
</script>
<?php
    if (empty($objProduto->_loja)) {
        exit('<div class="alert alert-error"><h4>Loja não encontrada.</h4></div>');
    }
?>

<!-- UPLOAD TABELA -->
    <?php if (count($erro_produto_nao_encontrado) > 0) { ?>
        <div class="alert alert-error">
            <p style="font-size: 16px;margin-top: 10px;"><?php echo "Erro: <b>".count($erro_produto_nao_encontrado)."</b> de <b>".count($registro)."</b> peças não fora encontradas.";?></p>
        </div>
    <?php }?>
    <?php if (strlen($msg_erro) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo $msg_erro;?></h4>
        </div>
    <?php }?>
    <?php if (count($erro_ao_atualizar) > 0) { ?>
        <div class="alert alert-error">
             <p style="font-size: 16px;margin-top: 10px;">Erro ao importar: <?php echo implode("<br>", $erro_ao_atualizar);?></p>
        </div>
    <?php }?>

    <?php if ((count($sucesso_ao_atualizar) > 0)) {?>
        <div class="alert alert-success">
            <p style="font-size: 16px;margin-top: 10px;">
            <?php echo (count($sucesso_ao_atualizar) > 0) ? "Atualizado estoque <b> ".count($sucesso_ao_atualizar)."</b>  de <b>".count($registro)."</b> peças.<br />" : "" ;?>
                
            </p>
        </div>
    <?php }?>
    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='loja_movimenta_estoque.php' align='center' class='form-search form-inline tc_formulario' >
        <input type='hidden' name='grava_upload_tabela' value='true' />

        <div class='titulo_tabela'>CARGA DE ESTOQUE</div><br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="alert">
                    <b>Arquivo deve ser no formato .CSV ou .TXT</b>, separados por ponto e virgula(;).<br />
                    <b>Layout:</b> <em><b> REFERENCIA; QUANTIDADE</b></em>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span6'>
                <b>Arquivo:</b>
                <div class='control-group tac'>
                    <h5 class='asteristico'>*</h5>
                    <input type='file' required="required" name='upload' id='upload'/>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
            <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Efetuar o Upload</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />

        </p><br/>
    </form> <br />

<!-- FIM UPLOAD TABELA -->

<table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
    <thead>
        <tr class='titulo_coluna' >
            <th align="left">Peça</th>
            <th>Estoque</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($objProduto->get() as $k => $row) {?>
    <tr>
        <td class='tal'><?php echo $row["ref_peca"];?> - <?php echo $row["nome_peca"];?></td>
        <td class='tac'><?php echo $row["qtde_estoque_peca"];?></td>
    </tr>
    <?php }?>
    </tbody>
</table>

<!-- FIM LISTAGEM TABELA -->

<?php include "rodape.php";?>