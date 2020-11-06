<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

if (strlen($_POST['lancamento']) > 0) $lancamento = trim($_POST['lancamento']);
if (strlen($_GET['lancamento']) > 0)  $lancamento = trim($_GET['lancamento']);
$msg_sucesso = ( trim($_POST["msg_sucesso"]) ) ?  trim( $_POST["msg_sucesso"] ) : trim( $_GET["msg_sucesso"] )  ;
if ($btn_acao == "apagar"){
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_lancamento
			WHERE  tbl_lancamento.fabrica      = $login_fabrica
			AND    tbl_lancamento.lancamento   = $lancamento;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$descricao      = $_POST["descricao"];
		$debito_credito = $_POST["debito_credito"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "gravar") {

	$descricao      = trim($_POST['descricao']);
	$debito_credito = trim($_POST['debito_credito']);
	$esmaltec_item_servico = intval(trim($_POST['esmaltec_item_servico']));

	if (strlen($esmaltec_item_servico) == 0) {
		if ($login_fabrica == 30) {
			$msg_erro = traduz("Selecione o Item de Serviço");
		}
		else {
			$esmaltec_item_servico = "NULL";
		}
	}
	else {
		if ($login_fabrica == 30) {
			$sql = "SELECT esmaltec_item_servico FROM tbl_esmaltec_item_servico WHERE esmaltec_item_servico=$esmaltec_item_servico AND ativo";
			$res = pg_query($con, $sql);

			if (pg_numrows($res) == 0) {
				$msg_erro = traduz("Item de serviço inativo ou não encontrado");
			}
		}
		else {
			$esmaltec_item_servico = "NULL";
		}
	}

	if (strlen($debito_credito) == 0) $msg_erro = traduz("Selecione débito ou crédito.");
	else                              $xdebito_credito = "'". trim($_POST["debito_credito"]) ."'";

	if (strlen($descricao) == 0)      $msg_erro = traduz("Digite a descricao do Lançamento.");
	else                              $xdescricao = "'". trim($_POST["descricao"]) ."'";

	if(strlen($ativo)==0)             $xativo = "FALSE";
	else                              $xativo = "TRUE";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		if (strlen ($lancamento) == 0) {
			$sql = "INSERT INTO tbl_lancamento (
						fabrica         ,
						descricao       ,
						debito_credito  ,
						ativo,
						esmaltec_item_servico
					) VALUES (
						$login_fabrica  ,
						$xdescricao     ,
						$xdebito_credito,
						$xativo,
						$esmaltec_item_servico
					)";

		}else{
			$sql = "UPDATE tbl_lancamento SET
						descricao       = $xdescricao      ,
						debito_credito  = $xdebito_credito ,
						ativo           = $xativo,
						esmaltec_item_servico = $esmaltec_item_servico
					WHERE lancamento   = $lancamento";
		}
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/*================ LE DA BASE DE DADOS =========================*/
$lancamento = $_GET['lancamento'];
if (strlen ($lancamento) > 0) {
	$sql = "SELECT	*
			FROM	tbl_lancamento
			WHERE	lancamento = $lancamento";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$esmaltec_item_servico = pg_result ($res,0,esmaltec_item_servico);
		$descricao      = pg_result ($res,0,descricao);
		$debito_credito = pg_result ($res,0,debito_credito);
		$ativo          = pg_result ($res,0,ativo);
	}

}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/
if (strlen ($msg_erro) > 0) {
	$esmaltec_item_servico = $_POST['esmaltec_item_servico'];
	$descricao        = $_POST['descricao'];
	$debito_credito   = $_POST['debito_credito'];
	$ativo            = $_POST['ativo'];
}

$title       = traduz("CADASTRO DE LANÇAMENTOS AVULSOS");
$layout_menu = 'cadastro';

include 'cabecalho_new.php';
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);
include("plugin_loader.php");

?>
<? include "javascript_calendario.php"; ?>


<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>
<style type="text/css">

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

</style>

<? 
if (strlen ($msg_erro) > 0) {
	if(strpos($msg_erro,"violates foreign key constraint")) $msg_erro = traduz("Não é possível excluir este registro porque está sendo usado em outras partes do sistema");
?>
  	<div class="alert alert-error">
		<h4><?= $msg_erro ?></h4>
    </div>
<? } ?>

<? 
if ( strlen( $msg_sucesso ) > 0 && strlen( $msg_erro ) <= 0 ) {
?>
    <div class="alert alert-sucess">
		<? echo $msg_sucesso ?>
    </div>

<? } ?>

<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_lancamento" method="post" class='formulario' action="<? echo $PHP_SELF ?>?msg_sucesso=Gravado com Sucesso!">
<div class='titulo_tabela '><?=traduz('Cadastro')?></div>
<br>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao'><?=traduz('Descrição')?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" class="span12" name="descricao" maxlength="50" value="<? echo $descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='debito_credito'><?=traduz('Débito ou Crédito')?></label>
					<div class='controls controls-row'>
						<div class='span6'>
							<label class="radio">
						        <input type="radio" name="debito_credito" value="C" <? if (strlen($debito_credito) == 0 OR $debito_credito == 'C') echo " checked"; ?>> 
						        <?=traduz('Crédito')?>:
						    </label>
						</div>
						<div class='span6'>
							<label class="radio">
						        <input type="radio" class="frm" name="debito_credito" value="D" <? if ($debito_credito == 'D') echo " checked"; ?>> 
						        <?=traduz('Débito')?>:
						    </label>
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<label class="radio">&nbsp;</label>
				<label class="radio">
			        <input type="checkbox" name="ativo" value="TRUE" <? if ($ativo =='t') echo " checked"; ?>>
			        <?=traduz('Ativo')?>
			    </label>
			</div>
			<?php if($login_fabrica == 30){?>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='item_servico'><?=traduz('Item de Serviço')?></label>
						<div class='controls controls-row'>
							<div class='span12'>
								<?php 
									if (strlen($esmaltec_item_servico)) {
										$sql_add = "OR esmaltec_item_servico = $esmaltec_item_servico";
									}

									$sql = "
									SELECT
									esmaltec_item_servico AS combo_esmaltec_item_servico,
									codigo AS combo_codigo,
									descricao AS combo_descricao

									FROM
									tbl_esmaltec_item_servico

									WHERE
									(ativo $sql_add)
									";
									$res = pg_query($con, $sql);

									echo "<select name='esmaltec_item_servico' id='esmaltec_item_servico' class='span12'>";

									for($i = 0; $i < pg_numrows($res); $i++) {
										extract(pg_fetch_array($res));

										$selected = $combo_esmaltec_item_servico == $esmaltec_item_servico ? "selected" : "";

										echo "
											<option value='$combo_esmaltec_item_servico' $selected>$combo_codigo - $combo_descricao</option>";
									}

									echo "</select>";


								?>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>

		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span8'>
				<center>
				<br>
				<input class="span12" type="hidden" name="lancamento" value="<? echo $lancamento; ?>">
				<input type='hidden' name='btn_acao' value=''>
				<input type="button" class='btn' value='<?=traduz("Gravar")?>' ONCLICK="javascript: document.frm_lancamento.btn_acao.value='gravar' ; document.frm_lancamento.submit();" ALT="Gravar formulário" border='0' >&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button" class='btn' value='<?=traduz("Apagar")?>' ONCLICK="javascript: if (document.frm_lancamento.btn_acao.value == '' ) { document.frm_lancamento.btn_acao.value='apagar' ; document.frm_lancamento.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Lançamento" border='0' >&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button"  class='btn' value='<?=traduz("Limpar")?>'
							 ONCLICK="javascript: if (document.frm_lancamento.btn_acao.value == '' ) { document.frm_lancamento.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' >
				</center>							 
			</div>
		<div class='span2'></div>
	</div>

</form>
<br>

<?

$sql = "SELECT  *
	FROM    tbl_lancamento
	WHERE   tbl_lancamento.fabrica = $login_fabrica
	ORDER BY tbl_lancamento.ativo DESC, tbl_lancamento.descricao;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0){
	echo "<table name='relatorio' id='relatorio' class='table table-striped table-bordered table-hover table-fixed' >";
	echo "<thead>";
	echo "<tr class='titulo_coluna'>";
	echo "<th><b>".traduz("Descrição")."</b></th>";
	if ($login_fabrica == 30) { echo "<th><b>".traduz("Item de Serviço")."</b></th>"; }
	echo "<th><b><u>D</u>ébito ou <u>C</u>rédito</b></th>";
	echo "<th>".traduz("Status")."</th>";
	echo "</tr>";	
	echo "</thead>";
	echo "<tbody>";
	for ($i = 0; $i < pg_numrows($res); $i++){


		( $i % 2 == 0 ) ? $cor ='#F7F5F0' : $cor = '#F1F4FA';

		$lancamento     = pg_result($res,$i,lancamento);
		$descricao      = pg_result($res,$i,descricao);
		$debito_credito = pg_result($res,$i,debito_credito);
		$ativo          = pg_result($res,$i,ativo);

		if($debito_credito=='C') $debito_credito = "<img src='imagens/status_azul.gif' />Crédito";
		else                     $debito_credito = "<img src='imagens/status_cinza.gif' />Débito";

		if($ativo=='t')          $ativo = "<img src='imagens/status_verde.gif'>".traduz("Ativo")."</font>";
		else                     $ativo = "<img src='imagens/status_vermelho.gif'><font color='#FF0000'>".traduz("Inativo")."</font>";
		echo "<tr bgcolor='$cor'>";
		echo "<td align='left'>&nbsp; <a href='$PHP_SELF?lancamento=$lancamento'>$descricao</a></td>";

		if ($login_fabrica == 30) {
			echo "<td align='left'>&nbsp;";

			$esmaltec_item_servico = pg_result($res,$i,esmaltec_item_servico);
			
			if (strlen($esmaltec_item_servico) > 0) {
				$sql = "SELECT codigo AS consulta_codigo, descricao AS consulta_descricao FROM tbl_esmaltec_item_servico WHERE esmaltec_item_servico=$esmaltec_item_servico";
				$res_item_servico = pg_query($con, $sql);

				if (pg_numrows($res_item_servico) == 1) {
					extract(pg_fetch_array($res_item_servico));

					echo "<acronym title='$consulta_descricao'>$consulta_codigo</acronym>";
				}
			}

			echo "</td>";
		}

		echo "<td align='left'>&nbsp; $debito_credito</td>";
		echo "<td align='left'>&nbsp; $ativo</td>";
		echo "</tr>";	
	}
	echo "</tbody>";
	echo "</table>";
	echo "</div>";
}
?>
<? include "rodape.php"; ?>