<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

$admin_privilegios="cadastros";

$layout_menu = "cadastro";
$title = "CADASTRAMENTO AN�LISE DE PRODUTOS";

include "cabecalho_new.php";

$plugins = array("tooltip","price_format");

include "plugin_loader.php";

$analise_produto = trim($_REQUEST['analise_produto']);

if(strlen($analise_produto) == 0){
    $title_page = "Cadastro de An�lise Produtos";
}else{
    $title_page = "Altera��o de An�lise Produtos";
}

if (isset($_POST["btn_acao"])) {
    $codigo                     = trim($_POST["codigo"]);
    $descricao                  = trim($_POST["descricao"]);
    $ativo                      = trim($_POST["ativo"]);

    if(strlen($descricao)==0){
        $msg_erro["msg"]["obg"] ="Por favor insira a descri��o da An�lise";
        $msg_erro["campos"][] = "descricao";
    }

    if (!empty($ativo)) {
        $ativo = 'true';
    }else{
        $ativo = 'false';
    } 

    if (count($msg_erro) == 0) {
        if(empty($analise_produto)){

            $sql = "SELECT codigo 
                    FROM tbl_analise_produto 
                    WHERE codigo = '$codigo'";       
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {    
                $msg_erro["msg"]["obg"] = "C�digo de An�lise j� cadastrado!";
                $msg_erro["campos"][] = "codigo";
            } else {
                $sql = "INSERT INTO tbl_analise_produto (
                                                descricao,
                                                codigo,
                                                ativo) 
                               VALUES (        
                                    '$descricao',
                                    '$codigo',
                                    $ativo )";
                $res = pg_query($con,$sql);
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"]["obg"] ="Ocorreu um erro durante a grava��o dos dados";
                } else {
                    $msg_success = "Dados gravados com sucesso";
                    unset($btn_acao, $analise_produto, $codigo, $descricao, $ativo, $_POST);
                } 
            }                

        }else{

                $sql = "UPDATE  tbl_analise_produto
                        SET     descricao       = '$descricao',
                                codigo = '$codigo',
                                ativo = $ativo
                        WHERE analise_produto = $analise_produto";

                $res = pg_query($con,$sql);
                        
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"]["obg"] ="Ocorreu um erro durante a grava��o dos dados";
                } else {
                    $msg_success = "Dados atualizados com sucesso";
                    unset($btn_acao, $analise_produto, $codigo, $descricao, $ativo, $_POST);
                }
        }
    }
}

?>

<script type="text/javascript">
    $(function (){
        
    });
</script>
<?
if (!empty($analise_produto)) {
    $sql = "SELECT  analise_produto,
                    codigo,
                    descricao,
                    ativo
            FROM    tbl_analise_produto 
            WHERE analise_produto = $analise_produto";
    $res = pg_query($con,$sql);

    $codigo             = trim(pg_result($res,0,codigo));
    $descricao          = trim(pg_result($res,0,descricao));
    $ativo              = trim(pg_result($res,0,ativo));


}    
?>
<?php
if (strlen($msg_success) > 0) {
?>
    <div class="alert alert-success">
        <h4><?= $msg_success ?></h4>
    </div>
<?php
}else{
    $msg_erro["msg"] = array_filter($msg_erro["msg"]);
    if (count($msg_erro["msg"]) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
    <?php
    }
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigat�rios </b>
</div>
<form name="frm_analise_produto" method="post" class="form-search form-inline tc_formulario" action="analise_produto.php">
    <div class="titulo_tabela "><?=$title_page?></div>
    <br/>
    <input type='hidden' name='analise_produto' value='<?= $analise_produto ?>'>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?= (in_array("codigo", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class="control-label" for="descricao">C�digo</label>
                <div class="controls controls-row">
                    <div class="span6">
                        <h5 class="asteristico">*</h5>
                        <input type="text" name="codigo" value="<?= $codigo; ?>" class='span12' maxlength='20' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?= (in_array("descricao", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class="control-label" for="descricao">Descri��o</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input type="text" name="descricao" value="<?= $descricao; ?>" class='span12' maxlength='50' />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class='span2'>
            <div class="span12">
                <label class="control-label" for="ativo">Ativo</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <input type='checkbox' name='ativo' value='t' <?= ($ativo == 't') ? "checked" : ""; ?> />
                    </div>
                </div>
            </div>
        </div>
    </div>    
    <p>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <button class='btn' type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
<?php
        if (strlen($_GET["analise_produto"]) > 0) {
?>
            <button class='btn btn-warning' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
<?php
        }
?>
    </p><br/>
</form>
<br />
<?
$sql = "SELECT  analise_produto,
                codigo,
                descricao,
                ativo
        FROM    tbl_analise_produto";
$res = pg_query($con,$sql);
 if (pg_numrows($res) > 0) { ?>

<div class='alert'>Para efetuar altera��es, clique na descri��o da an�lise do produto.</div>

<table id="analise_produto" class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela">
            <th colspan="3" nowrap>An�lises do produto cadastradas</th>
        </tr>
        <tr class="titulo_coluna">
            <th nowrap>Ativo</th>
            <th nowrap>C�digo</th>
            <th nowrap>Descri��o</th>
        </tr>    
    </thead>        
    <tbody>    
    <?
        for ($x = 0 ; $x < pg_numrows($res) ; $x++){ 
            $analise_produto     = trim(pg_result($res,$x,analise_produto));
            $descricao            = trim(pg_result($res,$x,descricao));
            $codigo               = trim(pg_result($res,$x,codigo));
            $ativo                = trim(pg_result($res,$x,ativo));

            ?>    
            <tr>
                <td class="tac">
                    <img src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Defeito ativo' : 'Defeito inativo'?>"/>
                </td>
                <td class="tac">
                    <a href="analise_produto.php?analise_produto=<?= $analise_produto ?>">
                        <?= $codigo ?>
                    </a>
                </td>
                <td class="tal">
                    <a href="analise_produto.php?analise_produto=<?= $analise_produto ?>">   <?= $descricao ?>   
                    </a>
                </td>
            </tr>
            <?
        }
    ?>
    </tbody>
</table>    
<?
} else { ?>
    <div class='alert alert-warning'><h4>N�o existem an�lises cadastradas</h4></div>
<?}    

include "rodape.php";

?>

