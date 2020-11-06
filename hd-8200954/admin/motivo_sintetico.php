<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

$admin_privilegios="cadastros";

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE MOTIVO SINT�TICO";

include "cabecalho_new.php";

$plugins = array("tooltip","price_format");

include "plugin_loader.php";

$motivo_sintetico = trim($_REQUEST['motivo_sintetico']);

if(strlen($motivo_sintetico) == 0){
    $title_page = "Cadastro de Motivo Sint�tico";
}else{
    $title_page = "Altera��o de Motivo Sint�tico";
}

if (isset($_POST["btn_acao"])) {
    $codigo                     = trim($_POST["codigo"]);
    $descricao                  = trim($_POST["descricao"]);
    $ativo                      = trim($_POST["ativo"]);

    if(strlen($descricao)==0){
        $msg_erro["msg"]["obg"] ="Por favor insira a descri��o do motivo sint�tico";
        $msg_erro["campos"][] = "descricao";
    }

    if (!empty($ativo)) {
        $ativo = 'true';
    }else{
        $ativo = 'false';
    } 

    if (count($msg_erro) == 0) {
        if(empty($motivo_sintetico)){

            $sql = "SELECT codigo 
                    FROM tbl_motivo_sintetico 
                    WHERE codigo = '$codigo'";       
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {    
                $msg_erro["msg"]["obg"] = "C�digo de motivo sint�tico j� cadastrado!";
                $msg_erro["campos"][] = "codigo";
            } else {
                $sql = "INSERT INTO tbl_motivo_sintetico (
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
                    unset($btn_acao, $motivo_sintetico, $codigo, $descricao, $ativo, $_POST);
                } 
            }                

        }else{

                $sql = "UPDATE  tbl_motivo_sintetico
                        SET     descricao       = '$descricao',
                                codigo = '$codigo',
                                ativo = $ativo
                        WHERE motivo_sintetico = $motivo_sintetico";

                $res = pg_query($con,$sql);
                        
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"]["obg"] ="Ocorreu um erro durante a grava��o dos dados";
                } else {
                    $msg_success = "Dados atualizados com sucesso";
                    unset($btn_acao, $motivo_sintetico, $codigo, $descricao, $ativo, $_POST);
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
if (!empty($motivo_sintetico)) {
    $sql = "SELECT  motivo_sintetico,
                    codigo,
                    descricao,
                    ativo
            FROM    tbl_motivo_sintetico 
            WHERE motivo_sintetico = $motivo_sintetico";
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
<form name="frm_motivo_sintetico" method="post" class="form-search form-inline tc_formulario" action="motivo_sintetico.php">
    <div class="titulo_tabela "><?=$title_page?></div>
    <input type='hidden' name='motivo_sintetico' value='<?= $motivo_sintetico ?>'>
    <br/>
    <br />
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
        <input class='btn' type="submit" name="btn_acao" value="Gravar" />
<?php
        if (strlen($_GET["motivo_sintetico"]) > 0) {
?>
            <button class='btn btn-warning' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
<?php
        }
?>
    </p><br/>
</form>
<br />
<?
 $sql = "SELECT  motivo_sintetico,
                codigo,
                descricao,
                ativo
        FROM    tbl_motivo_sintetico";
$res = pg_query($con,$sql);

 if (pg_numrows($res) > 0) { ?>

<div class='alert'>Para efetuar altera��es, clique na descri��o do motivo sint�tico.</div>

<table id="defeito_constatado" class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela">
            <th colspan="3" nowrap>Motivos sint�ticos cadastrados</th>
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
            $motivo_sintetico     = trim(pg_result($res,$x,motivo_sintetico));
            $descricao            = trim(pg_result($res,$x,descricao));
            $codigo               = trim(pg_result($res,$x,codigo));
            $ativo                = trim(pg_result($res,$x,ativo));

            ?>    
            <tr>
                <td class="tac">
                    <img src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Defeito ativo' : 'Defeito inativo'?>"/>
                </td>
                <td class="tac">
                    <a href="motivo_sintetico.php?motivo_sintetico=<?= $motivo_sintetico ?>">
                        <?= $codigo ?>
                    </a>
                </td>
                <td class="tal">
                    <a href="motivo_sintetico.php?motivo_sintetico=<?= $motivo_sintetico ?>">   <?= $descricao ?>   
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
    <div class='alert alert-warning'><h4>N�o existem motivos cadastrados</h4></div>
<?}    

include "rodape.php";

?>

