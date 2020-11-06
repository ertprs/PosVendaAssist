<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = 'cadastro';
$title = "CADASTRAMENTO DE PEÇAS EM ANÁLISE";

unset($msg_erro);
$msg_erro = array();

$peca_analise   = trim($_REQUEST["peca_analise"]);
$peca           = trim($_REQUEST["peca"]);
$btnacao        = trim($_REQUEST["btn_acao"]);

if ($btnacao == "deletar" and strlen($peca_analise) > 0) {
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql = "DELETE FROM tbl_peca_analise
            WHERE  tbl_peca_analise.peca_analise  = $peca_analise
            AND    tbl_peca_analise.fabrica       = $login_fabrica;";
    $res = pg_query ($con,$sql);

    if (!pg_last_error() && count($msg_erro['msg']) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query ($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF?suc=1&act=d");
        exit;
    }else{
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $referencia   = $_REQUEST["referencia"];
        $descricao    = $_REQUEST["descricao"];
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
        $msg_erro["msg"][] = pg_last_error();
    }
}

if ($btnacao == "gravar") {
    if (strlen($_POST["referencia"]) > 0) {
        $aux_referencia = "'". trim($_POST["referencia"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = "Favor informar a referência.";
        $msg_erro["campos"][] = "referencia";
    }

    if (strlen($msg_erro) == 0) {
        $sql = "SELECT tbl_peca.peca
                FROM   tbl_peca
                WHERE  trim(tbl_peca.referencia) = $aux_referencia
                AND    tbl_peca.fabrica          = $login_fabrica;";
        $res = pg_query ($con,$sql);

        if (pg_numrows($res) == 0) {
            $msg_erro["msg"][]      = "Peça informada não encontrada.";
            $msg_erro["campos"][]   = "referencia";
        }
    }

    $sql = "SELECT  tbl_peca_analise.peca_analise
            FROM    tbl_peca_analise
            WHERE   referencia = $aux_referencia
    ";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        $msg_erro["msg"][] = "Peça já está cadastrada.";
    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query ($con,"BEGIN TRANSACTION");

        if (strlen($peca_analise) == 0) {
            ###INSERE NOVO REGISTRO
            $sql = "INSERT INTO tbl_peca_analise    (
                                                        fabrica           ,
                                                        referencia
                                                    ) VALUES (
                                                        $login_fabrica    ,
                                                        $aux_referencia
                                                    );
            ";
        }else{
            ###ALTERA REGISTRO
            $sql = "UPDATE  tbl_peca_analise
                    SET     referencia = $aux_referencia
                    WHERE   peca_analise = $peca_analise
                    AND     fabrica             = $login_fabrica;";
        }
        $res = pg_query ($con,$sql);
    }

    if (!pg_last_error() && count($msg_erro['msg']) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF?suc=1&act=i");

    }else{
        $referencia    = $_POST["referencia"];
        $descricao     = $_POST["descricao"];
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

###CARREGA REGISTRO
if (strlen($peca_analise) > 0) {
    $sql = "SELECT  tbl_peca_analise.referencia,
                    (
                    SELECT tbl_peca.descricao
                    FROM   tbl_peca
                    WHERE  tbl_peca.referencia = tbl_peca_analise.referencia
                    AND tbl_peca.fabrica = $login_fabrica
                    ) AS descricao
            FROM    tbl_peca_analise
            WHERE   tbl_peca_analise.fabrica       = $login_fabrica
            AND     tbl_peca_analise.peca_analise  = $peca_analise;";
    $res = pg_query ($con,$sql);

    if (pg_numrows($res) > 0) {
        $_RESULT['peca_analise'] = $peca_analise;
        $_RESULT['referencia'] = trim(pg_result($res,0,referencia));
        $_RESULT['descricao']  = trim(pg_result($res,0,descricao));
    }
}

/**
* - Carrega a tabela
*/
$sqlTab = "SELECT  tbl_peca_analise.peca_analise,
                tbl_peca_analise.referencia     ,
                (
                    SELECT tbl_peca.descricao
                    FROM   tbl_peca
                    WHERE  tbl_peca.referencia = tbl_peca_analise.referencia
                    AND    tbl_peca.fabrica = $login_fabrica
                ) AS descricao
        FROM    tbl_peca_analise
        WHERE   tbl_peca_analise.fabrica = $login_fabrica
        ORDER BY descricao";
        #echo nl2br($sqlTab);
$resTab = pg_query ($con,$sqlTab);

include "cabecalho_new.php";

$plugins = array("autocomplete",
                "tooltip",
                 "shadowbox",
                 "dataTable"
            );

include ("plugin_loader.php");
?>
<script type="text/javascript"    src="js/thickbox.js"></script>
<script type="text/javascript"    src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript"    src="js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<style type="text/css">

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}

.border {
    border: 1px solid #ced7e7;
}

.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #ffffff
}

input {
    font-size: 10px;
}

.top_list {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color:#596d9b;
    background-color: #d9e2ef;
}

.line_list {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: normal;
    color:#596d9b;
    background-color: #ffffff;
}
.titulo {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #000000;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:green;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
}

.subtitulo{

color: #7092BE
}


</style>

<script type="text/javascript">

$(function (){
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this), Array("pecaId", "posicao") );
    });
});

function retorna_peca(json){

    if(json.referencia !== undefined){
        $("#peca").val(json.peca);
        $("#peca_referencia").val(json.referencia);
        $("#peca_descricao").val(json.descricao);
    }
}
</script>
<?
if (pg_last_error() || count($msg_erro['msg']) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}else{
    if ($_GET['suc']) {
        if($_GET['act'] == 'd'){
            $msg = "Peça deletada com sucesso";
        }else if($_GET['act'] == 'i'){
            $msg = "Peça cadastrada com sucesso";
        }
?>
    <div class="alert alert-success">
        <h4><?=$msg?></h4>
    </div>
<?
    }
}


$hiddens = array(
    "peca_analise",
    "peca"
);
$inputs =   array(
                "referencia" => array(
                    "id" => "peca_referencia",
                    "type" => "input/text",
                    "label" => "Referência",
                    "span" => 4,
                    "width" =>6,
                    "maxlength" => 20,
                    "lupa" => array(
                        "name" => "lupa",
                        "tipo" => "peca",
                        "parametro" => "referencia",
                        "extra" => array(
                            "pecaId" => "true"
                        )
                    ),
                    "required" => true
                ),
                "descricao" => array(
                    "id" => "peca_descricao",
                    "type" => "input/text",
                    "label" => "Descrição",
                    "span" => 4,
                    "maxlength" => 80,
                    "lupa" => array(
                        "name" => "lupa",
                        "tipo" => "peca",
                        "parametro" => "descricao",
                        "extra" => array(
                            "pecaId" => "true"
                        )
                    ),
                    "required" => true
                )
            );
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_peca_analise" method="post" action="<? $PHP_SELF ?>" class='form-search form-inline tc_formulario'>
<?if(strlen($produto) > 0){
    ?><div class="titulo_tabela">Alterando cadastro</div><?
}else{
    ?><div class="titulo_tabela">Cadastro</div><?
}?>

        <br/>
<?
    echo montaForm($inputs, $hiddens);
?>

    <input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
    <div class="row-fluid">
        <!-- margem -->
        <div class="span4"></div>

        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <? if (strlen($peca_analise) > 0){
                        $onclick        = "onclick=\"if (confirm('Você irá atualizar um produto! Confirma esta ação? Caso deseje apenas inserir um novo, cancele a operação, limpe as informações da tela e insira o produto!')) { submitForm($(this).parents('form'),'gravar');} \" ";
                        $onclickApaga = "onclick=\"if (document.frm_peca_analise.btn_acao.value == '' ) { submitForm($(this).parents('form'),'deletar');} return false;\" ";
                    }else{
                        $onclick = "onclick=\"if (document.frm_peca_analise.btn_acao.value == '' ) { submitForm($(this).parents('form'),'gravar');} return false;\" ";
                    }?>

                    <button type="button" class="btn" value="Gravar" alt="Gravar formulário" <?php echo $onclick;?> > Gravar</button>

                    <? if (strlen($peca_analise) > 0){?>
                        <button type="button" class="btn btn-warning" value="Limpar" onclick="javascript:  window.location='<? echo $PHP_SELF ?>'; return false;" alt="Limpar campos">Limpar</button>
                        <button type="button" class="btn btn-danger" value="Excluir" <?=$onclickApaga?> alt="Apagar Campos">Excluir</button>
                    <?}?>
                </div>
            </div>
        </div>

        <!-- margem -->
        <div class="span4"></div>
    </div>
</form>

<?
if (pg_numrows($resTab) > 0) {
?>
<table id="listagemPeca" style="margin: 0 auto;" class="tabela_item table table-striped table-bordered table-hover table-large">
    <thead>
        <tr class='titulo_coluna'>
            <th nowrap>Código</th>
            <th nowrap>Descrição</th>
        </tr>
    </thead>
    <tbody>
<?
    for ($y = 0 ; $y < pg_numrows($resTab) ; $y++){
        $peca_analise = trim(pg_result($resTab,$y,peca_analise));
        $referencia   = trim(pg_result($resTab,$y,referencia));
        $descricao    = trim(pg_result($resTab,$y,descricao));
?>
        <tr>
            <td class='tac'><a href="<?=$PHP_SELF?>?peca_analise=<?=$peca_analise?>"><?=$referencia?></a></td>
            <td class='tac'><a href="<?=$PHP_SELF?>?peca_analise=<?=$peca_analise?>"><?=$descricao?></a></td>
        </tr>
<?
    }
}
?>
    </tbody>
</table>
<?
include "rodape.php";
?>