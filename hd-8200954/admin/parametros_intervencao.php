<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';


include 'funcoes.php';

$layout_menu = "gerencia";
$title = "CONFIGURAÇÃO DE PARÂMETROS PARA INTERVENÇÃO";

include "cabecalho_new.php";

if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {
    $qtde_pecas = filter_input(INPUT_POST,'qtde_pecas',FILTER_SANITIZE_NUMBER_INT);
    $qtde_km    = filter_input(INPUT_POST,'qtde_km',FILTER_SANITIZE_NUMBER_INT);

    $res = pg_query($con,"BEGIN TRANSACTION");

    $sqlVerifica = "
        SELECT  parametros_adicionais
        FROM    tbl_fabrica
        WHERE   fabrica = $login_fabrica
    ";
    $resVerifica = pg_query($con,$sqlVerifica);
    $arrayParametros = pg_fetch_result($resVerifica,0,parametros_adicionais);
    $arrayParametros = json_decode($arrayParametros,TRUE);

    $arrayParametros['qtdeKmCidade'] = $qtde_km;

    $sql = "UPDATE  tbl_fabrica
            SET     qtde_pecas_intervencao = $qtde_pecas,
                    parametros_adicionais = '".json_encode($arrayParametros)."'
            WHERE   fabrica = $login_fabrica
    ";
    $res = pg_query($con,$sql);

    if(!pg_last_error($con)){
        $res = pg_query($con,"COMMIT TRANSACTION");
        $msg = "Gravado com Sucesso!";
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        $msg_erro = "Não foi possível atualizar a configuração";
    }
}

$sql = "SELECT  tbl_fabrica.qtde_pecas_intervencao,
                tbl_fabrica.parametros_adicionais
        FROM    tbl_fabrica
        WHERE   tbl_fabrica.fabrica = $login_fabrica
";
$res = pg_query($con,$sql);

$qtde_pecas = pg_fetch_result($res,0,qtde_pecas_intervencao);
$parametros = pg_fetch_result($res,0,parametros_adicionais);
$aux = json_decode($parametros,TRUE);
$qtde_km = $aux['qtdeKmCidade'];

if (strlen($msg_erro) > 0) {
?>
    <div class="alert alert-error">
        <h4><?echo $msg_erro;?></h4>
    </div>
<?
}
if (strlen($msg) > 0) {
?>
    <div class="alert alert-success">
        <h4><?echo $msg;$msg="";?></h4>
    </div>
<? } ?>

<form class='form-search form-inline tc_formulario' name="frm_qtde" method="post" action="<? echo $PHP_SELF; ?>">

<div class="titulo_tabela">Parâmetros para Intervenções</div>
<br/>
<div class="row-fluid">
	<!-- Margem -->
	<div class="span2"></div>
	<div class="span4">
		<div class="control-group">
			<label class="control-label" for=''>Qtde limite de peças OS</label>
			<div class='controls controls-row'>
			      <input class="span10" type="text" id="qtde_pecas" name="qtde_pecas" value="<? echo $qtde_pecas?>" maxlength="30" />
		    </div>
		</div>
	</div>
	<div class="span4">
		<div class="control-group">
			<label class="control-label" for=''>Qtde KM por cidade</label>
			<div class='controls controls-row'>
			      <input class="span10" type="text" id="qtde_km" name="qtde_km" value="<? echo $qtde_km?>" maxlength="30" />
		    </div>
		</div>
	</div>
</div>
<br/>
<p>
        <button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
</p>
<br/>
</form>

<?
include "rodape.php";
?>