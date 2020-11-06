<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

$admin_privilegios="cadastros";

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE MOTIVO ANALÍTICO";

include "cabecalho_new.php";

$plugins = array("tooltip","price_format");

include "plugin_loader.php";

$motivo_analitico = trim($_REQUEST['motivo_analitico']);

if(strlen($motivo_analitico) == 0){
    $title_page = "Cadastro de Motivo Analítico";
}else{
    $title_page = "Alteração de Motivo Analítico";
}

if (isset($_POST["btn_acao"])) {
    $codigo                     = trim($_POST["codigo"]);
    $descricao                  = trim($_POST["descricao"]);
    $ativo                      = trim($_POST["ativo"]);

    if(strlen($descricao)==0){
        $msg_erro["msg"]["obg"] ="Por favor insira a descrição do motivo analítico";
        $msg_erro["campos"][] = "descricao";
    }

    if (!empty($ativo)) {
        $ativo = 'true';
    }else{
        $ativo = 'false';
    } 

    if (count($msg_erro) == 0) {
        if(empty($motivo_analitico)){

            $sql = "SELECT codigo 
                    FROM tbl_motivo_analitico 
                    WHERE codigo = '$codigo'";       
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {    
                $msg_erro["msg"]["obg"] = "Código de motivo analítico já cadastrado!";
                $msg_erro["campos"][] = "codigo";
            } else {
                $sql = "INSERT INTO tbl_motivo_analitico (
                                                descricao,
                                                codigo,
                                                ativo) 
                               VALUES (        
                                    '$descricao',
                                    '$codigo',
                                    $ativo )";
                $res = pg_query($con,$sql);
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"]["obg"] ="Ocorreu um erro durante a gravação dos dados";
                } else {
                    $msg_success = "Dados gravados com sucesso";
                    unset($btn_acao, $motivo_analitico, $codigo, $descricao, $ativo, $_POST);
                } 
            }                

        }else{

                $sql = "UPDATE  tbl_motivo_analitico
                        SET     descricao       = '$descricao',
                                codigo = '$codigo',
                                ativo = $ativo
                        WHERE motivo_analitico = $motivo_analitico";

                $res = pg_query($con,$sql);
                        
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"]["obg"] ="Ocorreu um erro durante a gravação dos dados";
                } else {
                    $msg_success = "Dados atualizados com sucesso";
                    unset($btn_acao, $motivo_analitico, $codigo, $descricao, $ativo, $_POST);
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
if (!empty($motivo_analitico)) {
    $sql = "SELECT  motivo_analitico,
                    codigo,
                    descricao,
                    ativo
            FROM    tbl_motivo_analitico 
            WHERE motivo_analitico = $motivo_analitico";
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
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_motivo_analitico" method="post" class="form-search form-inline tc_formulario" action="motivo_analitico.php">
    <div class="titulo_tabela "><?=$title_page?></div>
    <br/>
    <input type='hidden' name='motivo_analitico' value='<?= $motivo_analitico ?>'>
    <br/>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?= (in_array("codigo", $msg_erro["campos"])) ? "error" : ""; ?>'>
                <label class="control-label" for="descricao">Código</label>
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
                <label class="control-label" for="descricao">Descrição</label>
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
    <p>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <button class='btn' type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
<?php
        if (strlen($_GET["motivo_analitico"]) > 0) {
?>
            <button class='btn btn-warning' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
<?php
        }
?>
    </p><br/>
</form>
<br />
<?
$sql = "SELECT  motivo_analitico,
                codigo,
                descricao,
                ativo
        FROM    tbl_motivo_analitico";
$res = pg_query($con,$sql);
 if (pg_numrows($res) > 0) { ?>

<div class='alert'>Para efetuar alterações, clique na descrição do motivo analítico.</div>

<table id="motivo_analitico" class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela">
            <th colspan="3" nowrap>Motivos analíticos cadastrados</th>
        </tr>
        <tr class="titulo_coluna">
            <th nowrap>Ativo</th>
            <th nowrap>Código</th>
            <th nowrap>Descrição</th>
        </tr>    
    </thead>        
    <tbody>    
    <?
        for ($x = 0 ; $x < pg_numrows($res) ; $x++){ 
            $motivo_analitico     = trim(pg_result($res,$x,motivo_analitico));
            $descricao            = trim(pg_result($res,$x,descricao));
            $codigo               = trim(pg_result($res,$x,codigo));
            $ativo                = trim(pg_result($res,$x,ativo));

            ?>    
            <tr>
                <td class="tac">
                    <img src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Defeito ativo' : 'Defeito inativo'?>"/>
                </td>
                <td class="tac">
                    <a href="motivo_analitico.php?motivo_analitico=<?= $motivo_analitico ?>">
                        <?= $codigo ?>
                    </a>
                </td>
                <td class="tal">
                    <a href="motivo_analitico.php?motivo_analitico=<?= $motivo_analitico ?>">   <?= $descricao ?>   
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
    <div class='alert alert-warning'><h4>Não existem motivos cadastrados</h4></div>
<?}    

include "rodape.php";

?>

