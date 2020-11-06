<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$mensagem_sucesso  = "none";
$mensagem_erro     = "none";
$campo_obrigatorio = "";
$msg_erro          = "";
$valor_minimo_lgr  = 0.00;

if($_POST["btn_acao"] == "gravar"){
	$btn_acao         = $_POST["btn_acao"];
	$valor_minimo_lgr = $_POST["valor_minimo_lgr"];

	if(empty($valor_minimo_lgr)){
		$msg_erro          = "Valor mínimo não informado";
		$campo_obrigatorio = "campo_obrigatorio";
		$mensagem_erro     = "";
	}else{
		pg_query($con,"BEGIN TRANSACTION");

		$valor_minimo_lgr = str_replace(",", ".", $valor_minimo_lgr);

		$sql = "UPDATE tbl_fabrica SET
				valor_minimo_extrato = $valor_minimo_lgr
			WHERE tbl_fabrica.fabrica = $login_fabrica";
		pg_query($con,$sql);

		if(strlen(pg_num_rows(result)) > 0){
			pg_query($con,"ROLLBACK");
			$msg_erro      = "Ocorreu um erro ao gravar o valor mínimo.";
			$mensagem_erro = "";
		}else{
			pg_query($con,"COMMIT");
			$mensagem_sucesso = "";
			$valor_minimo_lgr  = 0.00;
		}
	}
}

$title       = "CADASTRO DE VALOR MÍNIMO PARA LGR";
$cabecalho   = "CADASTRO DE VALOR MÍNIMO PARA LGR";
$layout_menu = "cadastro";

include "cabecalho_new.php";

$plugins = array(
	"price_format"
);

include("plugin_loader.php");
?>
<style type="text/css">
	#valor_minimo_lgr {
		width: 150%;
	}

	#btn_gravar{
		margin-top:30%;
	}

	.campo_obrigatorio {
		border: 1px solid red !important;
	}
</style>
<script type="text/javascript">
	$(function(){
		$("#btn_gravar").on("click",function(){
			if(confirm("Deseja gravar o valor mínimo?")){
				$("#btn_acao").val("gravar");
				$("#frm_valor_minimo_lgr").submit();
			}
		});
	});
</script>
<div class="mensagem_erro alert alert-error" style="display:<?=$mensagem_erro?>"><h4><?=$msg_erro?></h4></div>
<div class="mensagem_gravado alert alert-success" style="display:<?=$mensagem_sucesso?>"><h4>Gravado com sucesso</h4></div>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form id="frm_valor_minimo_lgr" method="POST" class="form-search form-inline tc_formulario" action="<? echo $PHP_SELF; ?> ">
	<div class="titulo_tabela">Cadastro</div>
	<br />
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span3">
			<div class="control-group">
				<label class="control-label" for="tipo_credenciamento">Valor Mínimo LGR</label>
				<div class="controls controls-row">
					<div class="span4">
						<h5 class='asteristico'>*</h5>
						<input type="text" id="valor_minimo_lgr" name="valor_minimo_lgr" price="true" size="12" maxlength="10" class="span12  <?=$campo_obrigatorio?>" value="<?=$valor_minimo_lgr?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span3">
			<div class="control-group">
				<div class="controls controls-row">
					<div class="span4 ">
					<input type="button" id="btn_gravar" class="btn" value="Gravar"/>
					<input type="hidden" id="btn_acao" name="btn_acao" value="<?=$btn_acao?>" />
					</div>
				</div>
			</div>
		</div>
	</div>
	<br/>
</form>
<?php
$sql = "SELECT tbl_fabrica.valor_minimo_extrato FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
	$valor_minimo_extrato = number_format(pg_fetch_result($res, 0, "valor_minimo_extrato"), 2, ",", ".");

		?>
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead class="titulo_coluna">
				<tr>
					<td colspan="2" style="text-align: center; font-size:14px">Valor mínimo cadastro</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Valor Mínimo LGR</td>
					<td><?=$valor_minimo_extrato?></td>
				</tr>
			</tbody>
		</table>
		<?php
}else{
	?>
	<div class="alert"><h4>Nenhum valor mínimo cadastrado</h4></div>
	<?php
}
include "rodape.php";
?>