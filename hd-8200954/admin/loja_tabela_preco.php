<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\LojaTabelaPreco;

$objTabela = new LojaTabelaPreco();
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
$nova_tabela   = $_GET["nova_tabela"];
//UPLOAD DE TABELA
if(!empty($_POST["grava_upload_tabela"])) {
    $msg_erro = "";

    $registro    = array();
    $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["upload"]["name"]));
    $loja_b2b_tabela = $_POST["loja_b2b_tabela"];

    if (empty($loja_b2b_tabela)) {
        $msg_erro .= "Selecione uma Tabela <br />";
    }

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
    if (count($registro) > 0 && strlen($msg_erro) == 0) {

        foreach ($registro as $key => $rows) {

            $res = pg_query($con,"BEGIN TRANSACTION");

            $REFERENCIA     = trim($rows[0]);
            $PRECO          = trataValor($rows[1]);

            if (strlen($REFERENCIA) > 0) {
                $retornoProduto = $objTabela->add($REFERENCIA, $PRECO, $loja_b2b_tabela);

                if ($retornoProduto["sucesso"] == "update") {

                    $retorno["update"][] = $retornoProduto["sucesso"];

                } elseif ($retornoProduto["sucesso"] == "insert") {

                    $retorno["insert"][] = $retornoProduto["sucesso"];

                } else {

                    $retorno["erro"][] = $retornoProduto["msn"];

                }   
            }

            if (strlen($retorno["erro"]) == 0 ) {
                $res = pg_query($con,"COMMIT TRANSACTION");
                $msg_sucesso = true;
            } else {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
                $msg_sucesso = false;
            }
        }

    } 
}

//NOVA TABELA
if(!empty($_POST["grava_nova_tabela"])) {
    $msg_erro = "";

    $descricao = $_POST["descricao"];

    if (empty($descricao)) {
        $msg_erro .= "Digite o nome da Tabela <br />";
    }

    if (strlen($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN TRANSACTION");

        $retorno = $objTabela->addTabela($descricao, $login_admin);

        if ($retorno["erro"]) {
            $msg_erro .= $retorno["msn"];
        }
        if (strlen($msg_erro) == 0 ) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            $msg_sucesso = true;
            $descricao = "";
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $msg_sucesso = false;
        }
    }

}


$layout_menu = "cadastro";
$title = "Tabela de Preço - Loja Virtual";
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

        $(".btn-ver-peca").click(function(){
            var tabela = $(this).data("tabela");
            Shadowbox.open({
                content: "loja_tabela_preco_peca.php?tabela="+tabela,
                player: "iframe",
                title:  "Gerenciamento de Peças",
                width:  800,
                height: 500
            });
        });
    });
</script>
<?php
    if (empty($objTabela->_loja)) {
        exit('<div class="alert alert-error"><h4>Loja não encontrada.</h4></div>');
    }
?>

<?php if ($upload_tabela) {?>
<!-- UPLOAD TABELA -->
    <?php if (count($retorno["erro"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo "Ocorreu erro em ".count($retorno["erro"])." de ".count($registro).", ao efetuar a importação.";?></h4>
        </div>
    <?php }?>
    <?php if (strlen($msg_erro) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo $msg_erro;?></h4>
        </div>
    <?php }?>

    <?php if ((count($retorno["update"]) > 0 || count($retorno["insert"]) > 0)) {?>
        <div class="alert alert-success">
            <p style="font-size: 16px;margin-top: 10px;">
            <?php echo (count($retorno["insert"]) > 0) ? "Foram <b>inserida(s) ".count($retorno["insert"])."</b> peça(s).<br />" : "" ;?>
            <?php echo (count($retorno["update"]) > 0) ? "Foram <b>atualizada(s) ".count($retorno["update"])."</b> peça(s).<br />" : "" ;?>
                
            </p>
        </div>
    <?php }?>
    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='loja_tabela_preco.php?upload_tabela=true' align='center' class='form-search form-inline tc_formulario' >
        <input type='hidden' name='grava_upload_tabela' value='true' />

        <div class='titulo_tabela'>Importação de tabela de preço</div><br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="alert">
                    <b>Arquivo deve ser no formato .CSV ou .TXT</b>, separados por ponto e virgula(;).<br />
                    <b>Layout:</b> <em><b> REFERENCIA; PRECO</b></em>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <b>Tabela:</b>
                <div class='control-group tac'>
                    <h5 class='asteristico'>*</h5>
                    <select name="loja_b2b_tabela" required id="loja_b2b_tabela" class="span12">
                        <option value="">Escolha uma Tabela</option>
                        <?php foreach ($objTabela->get() as $key => $rows) {?>
                        <option value="<?php echo $rows['loja_b2b_tabela'];?>"><?php echo $rows['descricao'];?></option>
                        <?php }?>
                    </select>
                </div>
            </div>
            <div class='span4'>
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

            <a href="loja_tabela_preco.php" class="btn">Lista Tabela de Preço</a>
        </p><br/>
    </form> <br />

<!-- FIM UPLOAD TABELA -->
<?php } elseif ($nova_tabela) {?>
<!-- NOVA TABELA -->
    <?php if (strlen($msg_erro) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo $msg_erro;?></h4>
        </div>
    <?php }?>
    <?php if ($msg_sucesso && strlen($msg_erro) == 0) { ?>
        <div class="alert alert-success">
            <h4>Gravado com sucesso.</h4>
        </div>
    <?php }?>
    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='loja_tabela_preco.php?nova_tabela=true' align='center' class='form-search form-inline tc_formulario' >
        <input type='hidden' name='grava_nova_tabela' value='true' />

        <div class='titulo_tabela'>Nova de tabela de preço</div><br/>
        
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <b>Nome da Tabela:</b>
                <div class='control-group'>
                    <h5 class='asteristico'>*</h5>
                    <input type='text' class="span12" required="required" value="<?php echo $descricao;?>" name='descricao' id='descricao'/>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
            <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' /> 

            <a href="loja_tabela_preco.php" class="btn">Lista Tabela de Preço</a>
        </p><br/>
    </form> <br />

<!-- FIM NOVA TABELA -->
<?php } else {?>
<!-- LISTAGEM DE TABELA -->

<div class="tc_formulario">
    <div class='titulo_tabela'>Tabela de Preço</div><br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8 tac'>
                <a href="loja_tabela_preco.php?upload_tabela=true" class="btn btn-primary">Upload de Tabela</a>
                <a href="loja_tabela_preco.php?nova_tabela=true" class="btn btn-success">Nova Tabela</a>
        </div>
        <div class='span2'></div>
    </div>
</div><br />
<table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
    <thead>
        <tr class='titulo_coluna' >
            <th align="left">Nome da Tabela</th>
            <th>Status</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($objTabela->get() as $k => $row) {?>
    <tr>
        <td class='tal'><?php echo $row["descricao"];?></td>
        <td class='tac'>
            <?php echo ($row["ativa"] == 't') ? '<span class="label label-success">Ativo</span>' : '<span class="label label-important">Inativo</span>';?>
        </td>
        <td class='tac'>
            <button type="button" data-tabela="<?php echo $row["loja_b2b_tabela"];?>" class="btn btn-ver-peca"><i class="icon-search"></i> Ver peças</button>
        </td>
    </tr>
    <?php }?>
    </tbody>
</table>

<!-- FIM LISTAGEM TABELA -->
<?php }?>

<?php include "rodape.php";?>