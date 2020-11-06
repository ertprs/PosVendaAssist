<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';

 
if ($_POST["btn_acao"] == "submit") {
	$msg_erro = array();

	$mercado_gerencia     = $_POST["mercado_gerencia"];
	$descricao            = trim($_POST["descricao"]);

	if (!strlen($descricao)) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "descricao";
	}

	if (!count($msg_erro)) {
		if (empty($mercado_gerencia)) {

			$sql_origem = "SELECT mercado_gerencia FROM tbl_mercado_gerencia WHERE fabrica = {$login_fabrica} AND descricao ILIKE '$descricao%'";
			$res_origem = pg_query($con, $sql_origem);

			if(pg_num_rows($res_origem) > 0){
				$msg_erro["msg"][] = "Mercado Gerencia já cadastrada.";
			}else{
				$sql = "INSERT INTO tbl_mercado_gerencia (
							fabrica,
							descricao,
							ativo
						) VALUES (
							$login_fabrica,
							'$descricao',
							't'
						)";
				$res = pg_query($con, $sql);
				if (strlen(pg_last_error()) > 0) {
					$msg_erro["msg"][] = "Erro ao gravar Mercado Gerencia" ;
				}
			}
		} else {
			$sql = "UPDATE tbl_mercado_gerencia
					   SET descricao = '$descricao'
					 WHERE fabrica = $login_fabrica
					   AND mercado_gerencia = $mercado_gerencia";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][] = "Erro ao alterar Mercado Gerencia" ;
			}
		}

		if (!count($msg_erro["msg"])) {
			$msg_success = true;
			unset($mercado_gerencia, $descricao);
		}
	}
}

if ($_POST["btn_acao"] == "ativar") {
	$mercado_gerencia = $_POST["mercado_gerencia"];

	$sql = "SELECT mercado_gerencia 
	          FROM tbl_mercado_gerencia 
	         WHERE fabrica = {$login_fabrica} 
	           AND mercado_gerencia = {$mercado_gerencia}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_mercado_gerencia SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND mercado_gerencia = {$mercado_gerencia}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}
	exit;
}

if ($_POST["btn_acao"] == "inativar") {
	$mercado_gerencia = $_POST["mercado_gerencia"];

	$sql = "SELECT mercado_gerencia, descricao 
	          FROM tbl_mercado_gerencia 
	         WHERE fabrica = {$login_fabrica} 
	           AND mercado_gerencia = {$mercado_gerencia}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$descricao_origem = pg_fetch_result($res, 0, descricao);

		$sql = "UPDATE tbl_mercado_gerencia SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND mercado_gerencia = {$mercado_gerencia}";
		$res = pg_query($con, $sql);
		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}
	exit;
}

if (!empty($_GET["mercado_gerencia"])) {
	$mercado_gerencia = $_GET["mercado_gerencia"];

	$sql = "SELECT mercado_gerencia, descricao
			  FROM tbl_mercado_gerencia
			 WHERE fabrica = $login_fabrica
			   AND mercado_gerencia = $mercado_gerencia;";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$descricao           = pg_result($res, 0, 'descricao');
	} else {
		$msg_erro["msg"][] = "Classificação de atentimento não encontrada";
	}
}

$layout_menu = "cadastro";
$title       = "Cadastro de Mercado Gerencia";
$title_page  = "Cadastro de Mercado Gerencia";

if ($_GET["mercado_gerencia"] || strlen($mercado_gerencia) > 0) {
	$title_page = "Alteração de Mercado Gerencia";
}

include 'cabecalho_new.php';

if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Mercado Gerencia, gravada com sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<script type="text/javascript">
	$(function () {

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var mercado_gerencia = $(this).attr("rel");
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", mercado_gerencia: mercado_gerencia },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
						console.log(data);
						if (data == "success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": "Inativar" });
							$(that).text("Inativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
						}
						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var mercado_gerencia = $(this).attr("rel");

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", mercado_gerencia: mercado_gerencia },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
						console.log(data);
						if (data == "success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": "Ativar" });
							$(that).text("Ativar");
							$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
						}
						loading("hide");
					}
				});
			}
		});

	});
</script>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_condicao" method="POST" enctype="multipart/form-data" class="form-search form-inline tc_formulario" action="<?=$PHP_SELF?>" >
	<div class='titulo_tabela '><?=$title_page?></div>
	<br/>
	<input type="hidden" name="mercado_gerencia" value="<?=$mercado_gerencia?>" />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao'>Descrição</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao" id="descricao" size="12" class='span12' maxlength="50" value= "<?=$descricao?>" />
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>

	<p><br/>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<button class='btn' type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<?php
		if (strlen($_GET["mercado_gerencia"]) > 0) {
		?>
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		<?php
		}
		?>
	</p><br/>
</form>

<table id="classificacoes_cadastradas" class='table table-striped table-bordered table-hover table-fixed' >
	<thead>
		<tr class="titulo_coluna" >
			<th>Descrição</th>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php
			$sql = "SELECT mercado_gerencia,descricao,ativo
					FROM tbl_mercado_gerencia
					WHERE fabrica = $login_fabrica
					ORDER BY descricao";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			for ($i = 0; $i < $rows; $i++) {
				$descricao   = pg_fetch_result($res, $i, "descricao");
				$mercado_gerencia   = pg_fetch_result($res, $i, "mercado_gerencia");
				$ativo   = pg_fetch_result($res, $i, "ativo");

				echo "<tr>";

					echo "<td><a href='{$_SERVER['PHP_SELF']}?mercado_gerencia={$mercado_gerencia}' >{$descricao}</a></td>";

				echo "<td class='tac'>";

				if ($ativo != "t") {
					echo "<button type='button' rel='{$mercado_gerencia}' name='ativar' class='btn btn-small btn-success' title='Ativar' >Ativar</button>";
				} else {
					echo "<button type='button' rel='{$mercado_gerencia}' name='inativar' class='btn btn-small btn-danger' title='Inativar' >Inativar</button>";
				}
				echo "
					</td>
				</tr>";
			}
		?>
	</tbody>
</table>

<?php
include "rodape.php";
?>
