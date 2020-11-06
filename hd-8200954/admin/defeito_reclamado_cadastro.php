<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';

if($login_fabrica==1){
    include "defeito_reclamado_cadastro_sem_integridade_teste.php";
    exit;
}

include 'funcoes.php';
include_once '../class/AuditorLog.php';

unset($msg_erro);

$msg_erro = array();
$btn_acao = trim($_REQUEST['btn_acao']);
$defeito  = trim($_REQUEST["defeito"]);

if (isset($_POST["ajax_ativo_inativo"])) {

    $defeito_reclamado = $_POST["defeito_reclamado"];
    $ativo             = $_POST["ativo"];

    if (in_array($ativo, ["Não", "Nao", "No"])) {
        $ativo_inativo = "t";
    } else {
        $ativo_inativo = "f";
    }

    $sqlLog = "SELECT descricao, familia, ativo, duvida_reclamacao, fabrica FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$defeito_reclamado} AND fabrica = {$login_fabrica}";


    $sql = "UPDATE tbl_defeito_reclamado 
            SET    ativo = '{$ativo_inativo}'
            WHERE  defeito_reclamado = {$defeito_reclamado}
            AND    fabrica = {$login_fabrica};";
    
    pg_query($con,$sql);

    
    if (!pg_last_error()) {

        $auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_defeito_reclamado', $login_fabrica);

        echo "sucesso";
    } else {
        echo "erro";
    }

    exit;
}

if (isset($_REQUEST['excluir']) ) {

    $defeito = (int) $_GET['defeito'];

    if(!empty($defeito)) {

        $auditorLog = new AuditorLog;
        $auditorLog->retornaDadosSelect("SELECT codigo, descricao, ativo FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$defeito} AND fabrica = {$login_fabrica}");

        $sql = "DELETE FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$defeito} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);
        
        if (pg_affected_rows($res) > 0){

            $auditorLog->retornaDadosSelect()->enviarLog('delete', 'tbl_defeito_reclamado', $login_fabrica);

            $msg = traduz("Inativado com Sucesso!");

        }else{

            $msg_erro[] = traduz("Esse Defeito Reclamado não pôde ser excluído");

        }
    }
}

if (strlen($defeito) > 0) {
    $sql = "
        SELECT
            tbl_defeito_reclamado.linha,
            tbl_defeito_reclamado.familia,
            tbl_defeito_reclamado.duvida_reclamacao,
            tbl_defeito_reclamado.defeito_reclamado,
            tbl_defeito_reclamado.descricao,
            tbl_defeito_reclamado.codigo,
            tbl_defeito_reclamado.ativo,
            tbl_defeito_reclamado.codigo,
            tbl_defeito_reclamado.entrega_tecnica
        FROM tbl_defeito_reclamado
        WHERE tbl_defeito_reclamado.defeito_reclamado = {$defeito}
        AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
        ORDER BY tbl_defeito_reclamado.descricao ASC;
    ";

    $res = pg_query($con,$sql);

    if (pg_numrows($res) > 0) {
        $_RESULT['linha'] = trim(pg_result($res,0,linha));
        $_RESULT['familia'] = trim(pg_result($res,0,familia));
        $_RESULT['defeito'] = $defeito;
        $_RESULT['duvida_reclamacao'] = trim(pg_result($res,0,duvida_reclamacao));
        $_RESULT['descricao_defeito'] = trim(pg_result($res,0,descricao));
        $_RESULT['codigo_defeito'] = trim(pg_result($res,0,codigo));
        
        if(trim(pg_result($res,0,ativo)) == 't'){
            $_RESULT['ativo'] = 1;
        }
        if ($login_fabrica == 42) {
            if(trim(pg_result($res,0,entrega_tecnica)) == 't'){
                $_RESULT['entrega_tecnica'] = 1;
            }
        }

        if (isset($moduloTraducao)) {
            $sql2 = "
                SELECT
                    descricao
                FROM tbl_defeito_reclamado_idioma
                WHERE defeito_reclamado = {$defeito}
                AND idioma = 'ES';
            ";
            $res2 = pg_query($con,$sql2);

            if (pg_numrows($res2) > 0) {
                $_RESULT['descricao_defeito_es'] = trim(pg_result($res2,0,descricao));
            }

            $sql2 = "
                SELECT
                    descricao
                FROM tbl_defeito_reclamado_idioma
                WHERE defeito_reclamado = {$defeito}
                AND idioma = 'en-US';
            ";
            $res2 = pg_query($con,$sql2);

            if (pg_numrows($res2) > 0) {
                $_RESULT['descricao_defeito_en'] = trim(pg_result($res2,0,descricao));
            }

        }
    }

}

if (strlen($btn_acao) > 0) {
    if ($btn_acao == "submit") {
        $defeito              = trim($_POST["defeito"]);
        $descricao_defeito    = trim($_POST['descricao_defeito']);
        $codigo_defeito       = trim($_POST['codigo_defeito']);
        $descricao_defeito_es = trim($_POST['descricao_defeito_es']);
        $descricao_defeito_en = trim($_POST['descricao_defeito_en']);
        $duvida_reclamacao    = trim($_POST['duvida_reclamacao']);
        $familia              = trim($_POST['familia']);
        $ativo                = array_values($_POST["ativo"]);

        if ($login_fabrica == 42) {
            $entrega_tecnica = array_values($_POST["entrega_tecnica"]);
            if (is_array($entrega_tecnica)) {
                $entrega_tecnica = 'true';
            }else{
                $entrega_tecnica = 'false';
            }
        }

        if (strlen($descricao_defeito) == 0) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios")." <br />";
            $msg_erro["campos"][] = "descricao_defeito";
        }

        if(strlen($codigo_defeito) == 0 AND !in_array($login_fabrica, array(191,193))){
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios")." <br />";
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios <br />";
            $msg_erro["campos"][] = "codigo_defeito";
        }

        if ($login_fabrica == 15) {
            $condDescricao = " descricao ILIKE '{$descricao_defeito}' ";
        } else {
            $condDescricao = " UPPER(descricao) = '".strtoupper($descricao_defeito)."' ";
        }

        $sql = "
            SELECT
                tbl_defeito_reclamado.defeito_reclamado
            FROM tbl_defeito_reclamado
            WHERE $condDescricao
            AND fabrica = {$login_fabrica};
        ";
        $res = pg_query($con,$sql);
        if(pg_numrows($res) > 0){
			$defeito_reclamado = pg_fetch_result($res,0, 'defeito_reclamado'); 
			if(empty($defeito)) {
	            $msg_erro["msg"][] = traduz("Já existe um defeito com essa descrição");
			}elseif($defeito <> $defeito_reclamado) {
	            $msg_erro["msg"][] = traduz("Já existe um defeito com essa descrição");
			}
        }


        if (is_array($ativo)) {
            $ativo = 'true';
        }else{
            $ativo = 'false';
        }

        if (strlen($familia) == 0) {
            $familia = 'null';
        }

        if (strlen($codigo) == 0) {
            $codigo = '';
        }

        if (in_array($login_fabrica, array(169,170)) && strlen($codigo_defeito) > 0 && strlen($defeito) > 0) {
            if (in_array($codigo_defeito, array("CONV","INST"))) {
                $msg_erro["msg"][] = traduz("Defeitos reclamados de Conversão/Instalação não podem ser alterados");
            }
        }

        if (count($msg_erro["msg"]) == 0) {
            pg_query($con,"BEGIN");

            $auditorLog = new AuditorLog;

            if (strlen($defeito) == 0) {
                if ($login_fabrica == 42) {
                    $sql_et_c = ", entrega_tecnica";
                    $sql_et_v = ", '$entrega_tecnica'";
                }

                $sql_cod_c = ", codigo";
                $sql_cod_v = ", '$codigo_defeito'";

                $sql = "
                    INSERT INTO tbl_defeito_reclamado (
    	                descricao,
                        familia,
                        ativo,
                        duvida_reclamacao,
                        fabrica
                        {$sql_et_c}
                        {$sql_cod_c}
                    ) VALUES (
                        '{$descricao_defeito}',
                        {$familia},
                        '{$ativo}',
                        '{$duvida_reclamacao}',
                        {$login_fabrica}
                        {$sql_et_v}
                        {$sql_cod_v}
                    );
                ";

                $res = pg_query($con,$sql);
                $res = pg_query($con,"SELECT CURRVAL('seq_defeito_reclamado')");
                $x_defeito_reclamado = pg_result($res,0,0);

                $sqlLog = "SELECT descricao, familia, ativo, duvida_reclamacao, fabrica {$sql_et_c} {$sql_cod_c} FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$x_defeito_reclamado} AND fabrica = {$login_fabrica}";

                $auditorLog->retornaDadosSelect($sqlLog)->enviarLog('insert', 'tbl_defeito_reclamado', $login_fabrica);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = traduz("Não foi possível realizar a gravação #1");
                }

            } else {
                if ($login_fabrica == 42) {
                    $sql_et = ", entrega_tecnica = '$entrega_tecnica'";
                }

                $sql_cod = ", codigo = '$codigo_defeito'";

                $sqlLog = "SELECT descricao, familia, ativo, duvida_reclamacao, fabrica {$sql_et_c} {$sql_cod_c} FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$defeito} AND fabrica = {$login_fabrica}";

                $auditorLog->retornaDadosSelect($sqlLog);

                $sql = "
                    UPDATE tbl_defeito_reclamado SET
                        descricao = '{$descricao_defeito}',
                        ativo = '{$ativo}',
                        familia = {$familia},
                        duvida_reclamacao = '{$duvida_reclamacao}',
                        fabrica = {$login_fabrica}
                        {$sql_et}
                        {$sql_cod}
                    WHERE defeito_reclamado = {$defeito}
                    AND fabrica = {$login_fabrica};
                ";
                $res = pg_query ($con,$sql);
                $x_defeito_reclamado = $defeito;

                $auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_defeito_reclamado', $login_fabrica);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = traduz("Não foi possível realizar a gravação #2");
                }
            }

            if (isset($moduloTraducao['es'])) {
                $sql = "
                    SELECT 
                        tbl_defeito_reclamado_idioma.defeito_reclamado
                    FROM tbl_defeito_reclamado_idioma
                    WHERE defeito_reclamado = {$x_defeito_reclamado}
                    AND idioma = 'ES';
                ";
                
                $res = pg_query($con,$sql);
                
                if (pg_num_rows($res) > 0) {
                    $x_defeito_reclamado  = trim(pg_result($res,0,defeito_reclamado));
                    $sql2 = "
                        UPDATE tbl_defeito_reclamado_idioma SET
                            descricao = '{$descricao_defeito_es}'
                        WHERE defeito_reclamado = {$x_defeito_reclamado}
                        AND idioma = 'ES';
                    ";
                }else{
                    $sql2 = "
                        INSERT INTO tbl_defeito_reclamado_idioma (
                            defeito_reclamado,
                            descricao,
                            idioma
                        ) VALUES (
                            {$x_defeito_reclamado},
                            '{$descricao_defeito_es}',
                            'ES'
                        );
                    ";
                }

                $res = pg_query($con,$sql2);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = traduz("Não foi possível realizar a gravação #3");
                }
            }

            if (isset($moduloTraducao['en-US'])) {
                $sql = "
                    SELECT 
                        tbl_defeito_reclamado_idioma.defeito_reclamado
                    FROM tbl_defeito_reclamado_idioma
                    WHERE defeito_reclamado = {$x_defeito_reclamado}
                    AND idioma = 'en-US';
                ";
                
                $res = pg_query($con,$sql);
                
                if (pg_num_rows($res) > 0) {
                    $x_defeito_reclamado  = trim(pg_result($res,0,defeito_reclamado));
                    $sql2 = "
                        UPDATE tbl_defeito_reclamado_idioma SET
                            descricao = '{$descricao_defeito_en}'
                        WHERE defeito_reclamado = {$x_defeito_reclamado}
                        AND idioma = 'en-US';
                    ";
                }else{
                    $sql2 = "
                        INSERT INTO tbl_defeito_reclamado_idioma (
                            defeito_reclamado,
                            descricao,
                            idioma
                        ) VALUES (
                            {$x_defeito_reclamado},
                            '{$descricao_defeito_en}',
                            'en-US'
                        );
                    ";
                }

                $res = pg_query($con,$sql2);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = traduz("Não foi possível realizar a gravação #3");
                }
            }

            if (count($msg_erro['msg']) == 0) {
                pg_query($con,"COMMIT");
                header("location:$PHP_SELF?suc=1&act=i");
            } else {
                pg_query($con,"ROLLBACK");
            }
        }
    } else if ($btn_acao == "deletar") {
        

        $defeito = trim($_REQUEST['defeito']);
        $codigo_defeito = trim($_REQUEST['codigo_defeito']);

        if (in_array($login_fabrica, array(169,170)) && strlen($codigo_defeito) > 0 && strlen($defeito) > 0) {
            if (in_array($codigo_defeito, array("CONV","INST"))) {
                $msg_erro["msg"][] = traduz("Defeitos reclamados de Conversão/Instalação não podem ser deletados");
            }
        }

        if (count($msg_erro["msg"]) == 0) {
                
            pg_query ($con,"BEGIN");

            if(isset($moduloTraducao)) {
                $sqlD = "
                    DELETE FROM tbl_defeito_reclamado_idioma
                    WHERE defeito_reclamado = {$defeito};
                ";

                $resD = pg_query($con,$sqlD);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = traduz('Este Registro está sendo Usado e não pode ser Apagado! #2');
                }
            }

            $sql = "
                DELETE FROM tbl_defeito_reclamado
                WHERE defeito_reclamado = {$defeito}
                AND fabrica = {$login_fabrica};
            ";

            $res = pg_query($con,$sql);

            if (strlen(pg_last_error()) > 0) {
                $msg_erro["msg"][] = traduz('Este Registro está sendo Usado e não pode ser Apagado! #1');
            }

            if (count($msg_erro['msg']) == 0){
                pg_query($con,"COMMIT");
                header("location:$PHP_SELF?suc=1&act=d");
            }else{
                pg_query ($con,"ROLLBACK");
            }
        }
    }//DELETAR
}//btn_acao

/**
* - Tabela
*/
$sqlTabela = "
    SELECT
        tbl_defeito_reclamado.defeito_reclamado,
        tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
        tbl_defeito_reclamado.duvida_reclamacao,
        tbl_defeito_reclamado.ativo,
        tbl_defeito_reclamado.codigo,
        tbl_defeito_reclamado.entrega_tecnica
    FROM tbl_defeito_reclamado
    WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
";


$sqlTabela .="
    AND (duvida_reclamacao <> 'CC' OR duvida_reclamacao IS NULL)
    ORDER BY tbl_defeito_reclamado.descricao
";

$resTabela = pg_query($con,$sqlTabela);

$layout_menu = "cadastro";
$title = traduz("CADASTRO DE DEFEITOS RECLAMADOS");

include 'cabecalho_new.php';

$plugins = array("dataTable", "shadowbox");

include "plugin_loader.php";

if (strlen($defeito) == 0) {
    $title_page = "Cadastro";
} else {
    $title_page = traduz("Alteração de Cadastro");
} ?>

<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<script type="text/javascript">
    $(function () {
        $(".btn_ativo_inativo").click(function(){

            var btn               = $(this);
            var defeito_reclamado = $(btn).data("defeito");
            var ativo             = $(btn).data("ativo");
            var data_ativo        = $(btn).attr("data-ativo");

            $.ajax({
                url: "defeito_reclamado_cadastro.php",
                type: "POST",
                data: { 
                    ajax_ativo_inativo : true,
                    defeito_reclamado : defeito_reclamado,
                    ativo : ativo
                },
                beforeSend:function(){
                    $(btn).text("Alterando...");
                },
                complete: function (data) {
                    if (data != 'erro') {

                        /*
                            $(btn).toggleClass("btn-success btn-danger");

                            if (ativo == "Sim") {
                                $(btn).text("Inativo");
                                $(btn).class("btn-danger");
                            } else {
                                $(btn).text("Ativo");
                                $(btn).class("btn-success");
                            }
                        */
                        if (jQuery.inArray(ativo, ["Não", "Nao", "No"])) {

                            $(btn).toggleClass("btn-success btn-danger");
                            $(btn).text("Inativar");
                        } else {

                            $(btn).toggleClass("btn-success btn-danger");
                            $(btn).text("Ativar");
                        }

                    } else {

                        alert("Erro ao Ativar/Inativar Defeito");
                    }
                }
            });
        });
    });

    function apagar(defeito_constatado) {
        if (confirm("Deseja excluir esse registro?")) {
            document.forms["frm_defeito"].btn_acao.value = "deletar";
            document.forms["frm_defeito"].submit();
        }
    }

</script>

<style type="text/css">

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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}

.sucesso{
    color:#FFFFFF;
    font:bold 16px "Arial";
    text-align:center;
}

</style>

<?php
if ($_GET['suc']) {
    if($_GET['act'] == 'd'){
        $msg = traduz("Defeito deletado com sucesso");
    }else if($_GET['act'] == 'i'){
        $msg = traduz("Defeito cadastrado com sucesso");
    }
?>
    <div class="alert alert-success">
        <h4><?=$msg?></h4>
    </div>
<?php
}else{
    if (pg_last_error() || count($msg_erro['msg']) > 0) {

        if (strlen(pg_last_error()) > 0) $msg_erro['msg'][] = pg_last_error();
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
    <?php
    }
}

$hiddens = array(
   "defeito"
);
if ($login_fabrica != 6) {
    $array_duvida_reclamacao = array(
        "DV"=>traduz("Dúvida"),
        "RC"=>traduz("Reclamação"),
        "IS"=>traduz("Insatisfação")
    );
} else {
    $array_duvida_reclamacao = array(
        "RC" => traduz("Reclamação"),
        "IN" => traduz("Informação"),
        "EN" => traduz("Engano"),
        "OA" => traduz("Outras Áreas")
    );
}

$inputs = array(
    "codigo_defeito" => array(
        "id"        => "codigo_defeito",
        "type"      => "input/text",
        "label"     => traduz("Código"),
        "span"      => 2,
        "width"     => 8,
        "maxlength" => 4,
        "required"  => ($login_fabrica != 191) ? true : false
    ),
    "descricao_defeito" => array(
        "id"        => "descricao_defeito",
        "type"      => "input/text",
        "label"     => traduz("Descrição"),
        "span"      => 4,
        "width"     => 11,
        "maxlength" => 50,
        "required"  => true
    )
);

if (!in_array($login_fabrica, array(50,90))) {
    $inputs["duvida_reclamacao"] = array(
        "type"      => "select",
        "span"      => 3,
        "width"     => 11,
        "label"     => traduz("Tipo"),
        "options"   => $array_duvida_reclamacao
    );
}

if (in_array($login_fabrica, array(14,66))) {

    $sql = "
        SELECT 
            familia,
            descricao
        FROM tbl_familia
        WHERE fabrica = {$login_fabrica}
        ORDER BY descricao;
    ";

    $res = pg_query($con,$sql);

    for($c = 0; $c < pg_num_rows($res); $c++){
        $aux_familia    = trim(pg_fetch_result($res,$c,familia));
        $aux_descricao  = trim(pg_fetch_result($res,$c,descricao));
        $options_familia[$aux_familia] = $aux_descricao;
    }

    $inputs["familia"] = array(
        "type" => "select",
        "span" => 3,
        "width" => 10,
        "label" => traduz("Família"),
        "options" => $options_familia
    );

}

$inputs["ativo"] = array(
    "span"  => 1,
    "type"  => "checkbox",
    "width" => 1,
    "checks" => array(
        "1" => traduz("Ativo")
    )
);

if ($login_fabrica == 42) {
    $inputs["entrega_tecnica"] = array(
        "span"  => 1,
        "type"  => "checkbox",
        "width" => 1,
        "checks" => array(
            "1" => traduz("Entrega Técnica")
        )
    );
}

if (isset($moduloTraducao['es'])) {
    $inputs["descricao_defeito_es"] = array(
        "id"        => "descricao_defeito_es",
        "type"      => "input/text",
        "label"     => traduz("Descrição Espanhol"),
        "span"      => 4,
        "width"     => 6,
        "maxlength" => 20,
        "required"  => false
    );
}

if (isset($moduloTraducao['en-US'])) {
    $inputs["descricao_defeito_en"] = array(
        "id"        => "descricao_defeito_en",
        "type"      => "input/text",
        "label"     => traduz("Descrição Inglês"),
        "span"      => 4,
        "width"     => 6,
        "maxlength" => 20,
        "required"  => false
    );
}

?>
<div class="row">
    <b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios')?></b>
</div>
<form name='frm_defeito' method='post' class="form-search form-inline tc_formulario" action='<?=$PHP_SELF?>'>
<div class="titulo_tabela "><?=$title_page?></div>
<br/>
<?= montaForm($inputs, $hiddens); ?>
<p>
    <br/>
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
    <button class='btn' type="button" onclick="submitForm($(this).parents('form'));"><?=traduz('Gravar')?></button>
    <? if (strlen($_GET["defeito"]) > 0) { ?>
        <button class='btn btn-danger' type="button" onclick="javascript:apagar();"><?=traduz('Apagar')?></button>
        <button class='btn btn-warning' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';"><?=traduz('Limpar')?></button>
    <? } ?>
</p>
<br/>
</form>

<div class='alert'><?=traduz('Para efetuar alterações, clique na descrição do defeito constatado.')?></div>
<table id="defeito_reclamado" class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela" >
            <th colspan="100">
                <?=traduz('Relação de Defeitos Reclamados')?>
            </th>
        </tr>
        <tr class='titulo_coluna'>
            <th><?=traduz('Ativo')?></th>
            <? if(!in_array($login_fabrica, array(50,90,158,165))){ ?>
                <th>Tipo</th>
            <? } ?>
            <? if(!in_array($login_fabrica, array(90))){ ?>
            <th><?=traduz('Código')?></th>
            <? } ?>
            <th><?=traduz('Defeito Reclamado')?></th>
            <? if ($login_fabrica == 42) { ?>
                <th><?=traduz('Entrega Técnica')?></th>
            <? }
            if (isset($moduloTraducao['es'])) { ?>
                <th><?=traduz('Espanhol')?></th>
            <? } 

            if (isset($moduloTraducao['en-US'])) { ?>
                <th><?=traduz('Inglês')?></th>
            <? } 

            if ($login_fabrica == 50) { ?>
                <th><?=traduz('Ações')?></th> 
            <? } ?>
        </tr>
    </thead>
    <tbody>
        <? for ($y = 0; $y < pg_num_rows($resTabela); $y++) {
            $codigo = trim(pg_result($resTabela,$y,codigo));
            $defeito_reclamado = trim(pg_result($resTabela,$y,defeito_reclamado));
            $defeito_reclamado_descricao = trim(pg_result($resTabela,$y,defeito_reclamado_descricao));
            $duvida_reclamacao = trim(pg_result($resTabela,$y,duvida_reclamacao));

            if ($login_fabrica == 42) {
                $entrega_tecnica = pg_result($resTabela, $y, "entrega_tecnica");
            }
            
            $ativo  = trim(pg_result($resTabela,$y,ativo));
            $ativo = ($ativo == "t") ? traduz("Sim") : traduz("Não");

            if ($login_fabrica != 6) {
                if ($duvida_reclamacao == 'DV') $duvida_reclamacao =traduz("Dúvida");
                if ($duvida_reclamacao == 'RC') $duvida_reclamacao =traduz("Reclamação");
                if ($duvida_reclamacao == 'IS') $duvida_reclamacao =traduz("Insatisfação");
            } else {
                if ($duvida_reclamacao == 'RC') $duvida_reclamacao =traduz("Reclamação");
                if ($duvida_reclamacao == 'IN') $duvida_reclamacao =traduz("Informação");
                if ($duvida_reclamacao == 'IS') $duvida_reclamacao =traduz("Insatisfação");
                if ($duvida_reclamacao == 'TP') $duvida_reclamacao =traduz("Troca de Produto");
                if ($duvida_reclamacao == 'EN') $duvida_reclamacao =traduz("Engano");
                if ($duvida_reclamacao == 'OA') $duvida_reclamacao =traduz("Outras Áreas");
            }

            $entrega_tecnica = ($entrega_tecnica == "t") ? traduz("Sim") : traduz("Não") ;

            $cor = ($y % 2 == 0) ? "#F7F5F0": '#F1F4FA'; ?>
            <tr style="background-color:<?=$cor?>">
                <td class="tac">
                    <button data-defeito="<?= $defeito_reclamado ?>" data-ativo="<?= $ativo ?>" type="button" class="btn_ativo_inativo btn btn-small <?=(in_array($ativo, ['Sim','Sí'])) ? 'btn-danger' : 'btn-success'?>"><?=(in_array($ativo, ['Sim','Sí'])) ? traduz('Inativar') : traduz('Ativar')?></button>
                </td>
                <? if (!in_array($login_fabrica, array(50,90,158,165))) { ?>
                    <td class="tac"><?=$duvida_reclamacao?></td>
                <? } ?>
                <? if (!in_array($login_fabrica, array(90))) { ?>
    	        <td><?=$codigo?></td>
                <? } ?>
                <td>
                    <a href='<?=$PHP_SELF?>?defeito=<?=$defeito_reclamado?>'><?=$defeito_reclamado_descricao?></a>
                </td>
                <? if ($login_fabrica == 42) { ?>
                    <td class="tac">
                        <img src="imagens/<?=($entrega_tecnica == 'Sim') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($entrega_tecnica == 'sim') ? traduz('Com entrega técnica') : traduz('Sem entrega técnica')?>"/>
                    </td>
                <? }
                if (isset($moduloTraducao['es'])) {
                    $sql2 = "
                        SELECT
                            descricao
                        FROM tbl_defeito_reclamado_idioma
                        WHERE defeito_reclamado = {$defeito_reclamado}
                        AND idioma = 'ES';
                    ";
                    $res2 = pg_query($con,$sql2);
                ?>
                <td>
                    <?php
                    if (pg_numrows($res2) > 0) { ?>
                        <?= trim(pg_result($res2,0,descricao)); ?>
                    <? } ?>
                </td>
                <?php
                }  
                if (isset($moduloTraducao['en-US'])) {
                    $sql2 = "
                        SELECT
                            descricao
                        FROM tbl_defeito_reclamado_idioma
                        WHERE defeito_reclamado = {$defeito_reclamado}
                        AND idioma = 'en-US';
                    ";
                    $res2 = pg_query($con,$sql2);
                ?>
                <td>
                    <?php
                    if (pg_numrows($res2) > 0) { ?>
                        <?= trim(pg_result($res2,0,descricao)); ?>
                    <? } ?>
                </td>
                <?php
                } ?>
            </tr>
        <? } ?>
    </tbody>
</table>

<br />
<div class='tac'>
    <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_defeito_reclamado&titulo=CADASTRO DE DEFEITOS RECLAMADOS'><?=traduz('Visualizar Log Auditor')?></a>
</div>
<br />

<script>
    $(function(){

        Shadowbox.init();

        $.dataTableLoad({ table: "#defeito_reclamado" });

    });
</script>

<? include "rodape.php"; ?>
</body>
</html>
