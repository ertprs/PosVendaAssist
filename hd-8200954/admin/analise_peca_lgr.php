<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["btn_acao"] == "ativar") {
	$status_analise_peca = $_POST["status_analise_peca"];

	$sql = "SELECT status_analise_peca FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_status_analise_peca SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
		$res = pg_query($con, $sql);

		if (!strlen(pg_last_error())) {
			$retorno = array("sucesso" => true);
		} else {
			$retorno = array("erro" => utf8_encode("Erro ao ativar Análise de peça"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Análise de peça não encontrada"));
	}

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "inativar") {
	$status_analise_peca = $_POST["status_analise_peca"];

	$sql = "SELECT status_analise_peca FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_status_analise_peca SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
		$res = pg_query($con, $sql);

		if (!strlen(pg_last_error())) {
			$retorno = array("sucesso" => true);
		} else {
			$retorno = array("erro" => utf8_encode("Erro ao inativar Análise de peça"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Análise de peça não encontrada"));
	}

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "excluir") {
	$status_analise_peca = $_POST["status_analise_peca"];

	
	$sql = "SELECT status_analise_peca FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "SELECT status_analise_peca FROM tbl_faturamento_lgr WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca} LIMIT 1";
		$res = pg_query($con, $sql);
	
		if (pg_num_rows($res) == 0) {
			$sql = "DELETE FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
			$res = pg_query($con, $sql);

			if (!strlen(pg_last_error())) {
				$retorno = array("sucesso" => true);
			} else {
				$retorno = array("erro" => utf8_encode("Erro ao excluir análise de peça"));
			}
		} else {
			$retorno = array("erro" => utf8_encode("Erro ao excluir Análise de Peça, ela já está sendo utilizada"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Análise de peça não encontrada"));
	}

	if (isset($_POST["ajax"])) {
		exit(json_encode($retorno));
	} else {
		if (isset($retorno["erro"])) {
			$msg_erro["msg"][] = utf8_decode($retorno["erro"]);
		} else {
			$msg_deletado = true;
			unset($_POST);
			unset($status_analise_peca);
		}
	}
}

if ($_POST["btn_acao"] == "submit") {
	$descricao = trim($_POST["descricao"]);
	$ativo     = $_POST["ativo"];

	if (empty($ativo)) {
		$ativo = "f";
	}

	if (empty($descricao)) {
		$msg_erro["msg"][]    = "Digite uma descrição";
		$msg_erro["campos"][] = "descricao";
	} else {
		$status_analise_peca = $_REQUEST["status_analise_peca"];

		if (!strlen($status_analise_peca)) {
			$sql = "INSERT INTO tbl_status_analise_peca (fabrica, descricao, ativo) VALUES ({$login_fabrica}, '{$descricao}', '{$ativo}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][] = "Erro ao atualizar análise de peça";
			}
		} else {
			$sql = "UPDATE tbl_status_analise_peca SET descricao = '{$descricao}', ativo = '{$ativo}' WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][] = "Erro ao atualizar análise de peça";
			}
		}

		if (!strlen(pg_last_error())) {
			$msg_success = true;
			unset($_POST);
			unset($analise_peca);
		}
	}

	$descricao 				= "";
	$ativo     				= "";
	$status_analise_peca 	= "";
	
}

if (!empty($_GET["status_analise_peca"])) {
	$_RESULT["status_analise_peca"] = $_GET["status_analise_peca"];

	$sql = "SELECT 
				tbl_status_analise_peca.descricao,
				tbl_status_analise_peca.ativo
			FROM tbl_status_analise_peca
			WHERE tbl_status_analise_peca.status_analise_peca = {$_RESULT['status_analise_peca']}
			AND tbl_status_analise_peca.fabrica = {$login_fabrica}";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT["descricao"] = pg_fetch_result($res, 0, "descricao");
		$_RESULT["ativo"]     = pg_fetch_result($res, 0, "ativo");
	} else {
		$msg_erro["msg"][] = "Análise de peça não encontrada";
	}
}

$layout_menu = "cadastro";
$title       = "CADASTRO DE ANÁLISE DE PEÇA";
$title_page  = "Cadastro";

if (strlen($analise_peca)) {
	$title_page = "Alteração de cadastro";
}

include "cabecalho_new.php";

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");
?>

<script>
	
$(function() {
	$.dataTableLoad({
		table: "#analise_peca_table", 
		type: "custom", 
		config: [ "pesquisa" ]
	});

	$(document).on("click", "button[name=ativar]", function () {
		if (ajaxAction()) {
			var status_analise_peca = $(this).parent().find("input[name=status_analise_peca]").val();
			var that         = $(this);
			
			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { btn_acao: "ativar", status_analise_peca: status_analise_peca },
				beforeSend: function () {
					loading("show");
				},
				complete: function (data) {
					data = $.parseJSON(data.responseText);

					if (data.erro) {
						alert(data.erro);
					} else {
						$(that).removeClass("btn-success").addClass("btn-danger");
						$(that).attr({ "name": "inativar", "title": "Inativar análise de peça" });
						$(that).text("Inativar");
						$(that).parents("tr").find("img[name=ativo]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
					}

					loading("hide");
				}
			});
		}
	});

	$(document).on("click", "button[name=inativar]", function () {
		if (ajaxAction()) {
			var status_analise_peca = $(this).parent().find("input[name=status_analise_peca]").val();
			var that         = $(this);
						
			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { btn_acao: "inativar", status_analise_peca: status_analise_peca },
				beforeSend: function () {
					loading("show");
				},
				complete: function (data) {
					data = $.parseJSON(data.responseText);				

					if (data.erro) {
						alert(data.erro);
					} else {
						$(that).removeClass("btn-danger").addClass("btn-success");
						$(that).attr({ "name": "ativar", "title": "Ativar análise de peça" });
						$(that).text("Ativar");
						$(that).parents("tr").find("img[name=ativo]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
					}

					loading("hide");
				}
			});
		}
	});

	$(document).on("click", "button[name=excluir]", function () {
		if (ajaxAction()) {
			var status_analise_peca = $(this).parent().find("input[name=status_analise_peca]").val();
			var tr           = $(this).parents("tr");
			
			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { btn_acao: "excluir", status_analise_peca: status_analise_peca, ajax: true },
				beforeSend: function () {
					loading("show");
				},
				complete: function (data) {
					data = $.parseJSON(data.responseText);

					if (data.erro) {
						alert(data.erro);
					} else {
						$(tr).html("<td colspan='3'><div class='alert alert-danger' style='margin: 0px;'><h4>Análise de peça excluída</h4></div></td>");

						setTimeout(function() {
							$(tr).remove();
						}, 3000);
					}

					loading("hide");
				}
			});
		}
	});
});

</script>

<?php
if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Análise de peça, gravada com sucesso</h4>
    </div>
<?php
}

if ($msg_deletado) {
?>
    <div class="alert alert-success">
		<h4>Análise de peça, excluída com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" >
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form method="post" style="margin: 0 auto;" class="form-search form-inline tc_formulario" action="analise_peca_lgr.php" >
	<div class="titulo_tabela" ><?=$title_page?></div>

	<br/>

	<input type="hidden" name="status_analise_peca" value="<?=$status_analise_peca?>" />

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="descricao" >Descrição</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<h5 class="asteristico" >*</h5>
						<input type="text" name="descricao" id="descricao" class="span12" value="<?=getValue('descricao')?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group" >
				<label class="control-label" for="ativo" >Ativo</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<input type="checkbox" name="ativo" id="ativo"  value="t" <?=(getValue("ativo") == "t") ? "checked" : ""?> />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<br />

	<p>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn' type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<? if(strlen($analise_peca) > 0){ ?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
			<button type="button" class="btn btn-danger"  onclick="submitForm($(this).parents('form'),'excluir');" alt="Excluir análise de peça" >Excluir</button>
		<? } ?>
	</p>

	<br />	
</form>

<br />

<table id="analise_peca_table" class='table table-striped table-bordered table-hover table-fixed' style="table-layout: fixed;" >
	<thead>
		<tr class="titulo_coluna" >
			<th>Descrição</th>
			<th>Ativo</th>
			<th>Ações</th>
		</tr>
	</thead>
	<?php
	$sql = "SELECT 
				tbl_status_analise_peca.status_analise_peca,
				tbl_status_analise_peca.descricao,
				tbl_status_analise_peca.ativo
			FROM tbl_status_analise_peca
			WHERE tbl_status_analise_peca.fabrica = {$login_fabrica}
			ORDER BY tbl_status_analise_peca.descricao ASC";
	$res = pg_query($con, $sql);

	for ($i = 0; $i < pg_num_rows($res); $i++) {
		$status_analise_peca 	= pg_fetch_result($res, $i, "status_analise_peca");
		$descricao    			= pg_fetch_result($res, $i, "descricao");
		$ativo        			= pg_fetch_result($res, $i, "ativo");
		?>

		<tr>
			<td class="tal" ><a href="<?=$_SERVER['PHP_SELF']?>?status_analise_peca=<?=$status_analise_peca?>" ><?=$descricao?></a></td>
			<td class="tac" ><img name="ativo" src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Análise de peça ativa' : 'Análise de peça inativa'?>" /></td>
			<td class="tac">
				<input type="hidden" name="status_analise_peca" value="<?=$status_analise_peca?>" />
				<?php
				if ($ativo == "f") {
					echo "<button type='button' name='ativar' class='btn btn-small btn-success' title='Ativar análise de peça' >Ativar</button>";
				} else {
					echo "<button type='button' name='inativar' class='btn btn-small btn-danger' title='Inativar análise de peça' >Inativar</button>";
				}	
				?>
				<button type='button' name='excluir' class='btn btn-small btn-danger' title='Excluir análise de peça' >Excluir</button>
			</td>
		</tr>
	<?php
	}
	?>
</table>

<?	include "rodape.php"; ?>
