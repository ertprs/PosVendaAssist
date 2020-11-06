<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';

if ($_POST["btn_acao"] == "submit") {
	$msg_erro = array();

	$hd_tipo_chamado    = $_POST["hd_tipo_chamado"];
	$codigo            	= trim($_POST["codigo"]);
	$descricao          = trim($_POST["descricao"]);

	if (!strlen($descricao)) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "descricao";
	}

	if (!count($msg_erro)) {
		if (empty($hd_tipo_chamado)) {

			$sql_origem = "SELECT hd_tipo_chamado FROM tbl_hd_tipo_chamado WHERE fabrica = {$login_fabrica} AND descricao ILIKE '$descricao%'";
			$res_origem = pg_query($con, $sql_origem);

			if(pg_num_rows($res_origem) > 0){
				$msg_erro["msg"][] = "Tipo de protocolo já cadastrado.";
			}else{
				$sql = "INSERT INTO tbl_hd_tipo_chamado (
							fabrica,
							descricao,
							codigo
						) VALUES (
							$login_fabrica,
							'$descricao',
							'$codigo'
						)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$msg_erro["msg"][] = "Erro ao gravar tipo de protocolo" ;
				}
			}
		} else {
			$sql = "UPDATE tbl_hd_tipo_chamado
					SET descricao = '$descricao',
					codigo = '$codigo'
					WHERE fabrica = $login_fabrica
					AND hd_tipo_chamado = $hd_tipo_chamado";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro["msg"][] = "Erro ao alterar tipo de protocolo";
			}
		}

		if (!count($msg_erro["msg"])) {
			$msg_success = true;
			unset($hd_tipo_chamado, $codigo, $descricao);
		}
	}
}

if ($_POST["btn_acao"] == "ativar") {
	$hd_tipo_chamado = $_POST["hd_tipo_chamado"];

	$sql = "SELECT hd_tipo_chamado FROM tbl_hd_tipo_chamado WHERE fabrica = {$login_fabrica} AND hd_tipo_chamado = {$hd_tipo_chamado}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_hd_tipo_chamado SET ativo = TRUE WHERE fabrica = {$login_fabrica} AND hd_tipo_chamado = {$hd_tipo_chamado}";
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
	$hd_tipo_chamado = $_POST["hd_tipo_chamado"];

	$sql = "SELECT hd_tipo_chamado FROM tbl_hd_tipo_chamado WHERE fabrica = {$login_fabrica} AND hd_tipo_chamado = {$hd_tipo_chamado}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {

		$sql = "UPDATE tbl_hd_tipo_chamado SET ativo = FALSE WHERE fabrica = {$login_fabrica} AND hd_tipo_chamado = {$hd_tipo_chamado}";
		$res = pg_query($con, $sql);
		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}
	exit;
}


if ($_POST['btn_acao'] == "editar") {
	$hd_tipo_chamado = $_POST["hd_tipo_chamado"];

	$sql = "SELECT hd_tipo_chamado, descricao, codigo
			FROM tbl_hd_tipo_chamado
			WHERE fabrica = $login_fabrica
			AND hd_tipo_chamado = $hd_tipo_chamado;";
	$res = pg_query($con, $sql);
	
	if (pg_num_rows($res) > 0) {
		$codigo           = pg_result($res, 0, 'codigo');
		$descricao        = pg_result($res, 0, 'descricao');

		$retorno = ["msg" => "ok", "codigo" => $codigo, "descricao" => $descricao];

	} else {
		$retorno = ["msg" => "Tipo de protocolo não encontrado"];
	}

	echo json_encode($retorno);

	exit;
}

$layout_menu = "cadastro";
$title       = "Cadastro de tipo de protocolo" ;
$title_page  = "Cadastro de tipo de protocolo";

include 'cabecalho_new.php';

if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Tipo de protocolo gravado com sucesso</h4>
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

		$(document).on("click", "button[name=editar]", function () {
			if (ajaxAction()) {
				
				var hd_tipo_chamado = $(this).attr("rel");
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "editar", hd_tipo_chamado: hd_tipo_chamado },
					complete: function (data) {
						data = JSON.parse(data.responseText);
						
						if (data.msg == "ok") {
							$("#hd_tipo_chamado").val(hd_tipo_chamado);
							$("#codigo").val(data.codigo);
							$("#descricao").val(data.descricao);
						}else{
							alert(data.msg);
						}
					}
				});
			}
		});

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var hd_tipo_chamado = $(this).attr("rel");
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", hd_tipo_chamado: hd_tipo_chamado },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
						console.log(data);
						if (data == "success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": "Inativar origem" });
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
				var hd_tipo_chamado = $(this).attr("rel");

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", hd_tipo_chamado: hd_tipo_chamado },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
						console.log(data);
						if (data == "success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": "Ativar origem" });
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
	<input type="hidden" name="hd_tipo_chamado" id="hd_tipo_chamado" value="<?=$hd_tipo_chamado?>" />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span3'>
				<label class='control-label' for='codigo'>Código</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<input type="text" name="codigo" id="codigo" size="12" class='span12' maxlength="30" value= "<?=$codigo?>" />
					</div>
				</div>
			</div>
			<div class='span5'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao'>Descrição</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao" id="descricao" size="12" class='span12' maxlength="80" value= "<?=$descricao?>" />
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
		if (strlen($_GET["hd_tipo_chamado"]) > 0) {
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
			<th>Código</th>
			<th>Descrição</th>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php
			$sql = "SELECT hd_tipo_chamado,codigo,descricao,ativo
					FROM tbl_hd_tipo_chamado
					WHERE fabrica = $login_fabrica
					ORDER BY descricao";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			for ($i = 0; $i < $rows; $i++) {
				$hd_tipo_chamado   = pg_fetch_result($res, $i, "hd_tipo_chamado");
				$descricao   = pg_fetch_result($res, $i, "descricao");
				$codigo   = pg_fetch_result($res, $i, "codigo");
				$ativo   = pg_fetch_result($res, $i, "ativo");

				echo "<tr>";

				echo "<td>{$codigo}</td>";
				echo "<td>{$descricao}</td>";
				
				echo "<td class='tac'>";

				echo "<button type='button' rel='{$hd_tipo_chamado}' name='editar' class='btn btn-small btn-info' title='Editar registro' >Editar</button> &nbsp;";

				if ($ativo != "t") {
					echo "<button type='button' rel='{$hd_tipo_chamado}' name='ativar' class='btn btn-small btn-success' title='Ativar registro' >Ativar</button>";
				} else {
					echo "<button type='button' rel='{$hd_tipo_chamado}' name='inativar' class='btn btn-small btn-danger' title='Inativar registro' >Inativar</button>";
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
