<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
$text_class = "Classificação";
$text_subclass = "SubClassificação";
if ($login_fabrica == 189) {
	$text_class = "Registro Ref. a";
	$text_subclass = "Especificação de Ref. de Registro";
} 
if ($_POST["btn_acao"] == "ativar") {
	$hd_subclassificacao = $_POST["hd_subclassificacao"];

	$sql = "SELECT hd_subclassificacao FROM tbl_hd_subclassificacao WHERE fabrica = {$login_fabrica} AND hd_subclassificacao = {$hd_subclassificacao}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {

		$sql = "UPDATE tbl_hd_subclassificacao SET ativa = TRUE WHERE fabrica = {$login_fabrica} AND hd_subclassificacao = {$hd_subclassificacao}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}else{
		echo "error";
	}
	exit;
}

if ($_POST["btn_acao"] == "inativar") {
	$hd_subclassificacao = $_POST["hd_subclassificacao"];

	$sql = "SELECT hd_subclassificacao FROM tbl_hd_subclassificacao WHERE fabrica = {$login_fabrica} AND hd_subclassificacao = {$hd_subclassificacao}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {

		$sql = "UPDATE tbl_hd_subclassificacao SET ativa = FALSE WHERE fabrica = {$login_fabrica} AND hd_subclassificacao = {$hd_subclassificacao}";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			echo "success";
		} else {
			echo "error";
		}
	}else{
		echo "error";
	}

	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$classificacao		= $_POST['classificacao'];
	$sub_classificacao 	= $_POST['sub_classificacao'];

	if(empty($classificacao) && $login_fabrica <> 189){
		$msg_erro["msg"][]    = "Selecione uma Classificação.";
		$msg_erro["campos"][] = "classificacao";
	}else{
		if ($login_fabrica <> 189) {
			$sql = "SELECT hd_classificacao, descricao
					FROM tbl_hd_classificacao
					WHERE fabrica = $login_fabrica
					AND hd_classificacao = {$classificacao}
					AND ativo IS TRUE";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$xclassificacao = pg_fetch_result($res, 0, 'hd_classificacao');
			}else{
				$msg_erro["msg"][] = $text_class." não encontrada.";
			}
		}
	}



	if(strlen(trim($sub_classificacao)) == 0){
		$msg_erro["msg"][]    = "Favor preencher a Descrição da {$text_subclass}.";
		$msg_erro["campos"][] = "sub_classificacao";
	}

	if ($login_fabrica <> 189) {
		$cond = " AND hd_classificacao = {$xclassificacao}";
		$campo_class = "hd_classificacao,";
		$valor_class = "{$xclassificacao},";
	} else {
		$cond = "";
		$campo_class = "";
		$valor_class = "";
	}

	$sql = "SELECT hd_subclassificacao
			FROM tbl_hd_subclassificacao
			WHERE fabrica = {$login_fabrica}
			{$cond}
			AND descricao = '{$sub_classificacao}' ";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$msg_erro["msg"][] = "Já existe essa {$text_subclass} cadastrada para essa {$text_class}.";
	}else{
		if (!count($msg_erro["msg"])) {
			$sql = "INSERT INTO tbl_hd_subclassificacao(
						fabrica,
						{$campo_class}
						descricao
					)VALUES(
						{$login_fabrica},
						{$valor_class}
						'{$sub_classificacao}'
					)";
			$res = pg_query($con, $sql);
			if(strlen(pg_last_error())){
				$msg_erro["msg"][] = "Erro ao gravar dados.";
			}else{
				$msg_success = "Gravado com sucesso.";
			}
		}
	}
}


if ($login_fabrica == 189) {
	$sql = "SELECT 		tbl_hd_subclassificacao.hd_subclassificacao,
					tbl_hd_subclassificacao.descricao AS subclassificacao_descricao,
					tbl_hd_subclassificacao.ativa
			FROM 	tbl_hd_subclassificacao
			WHERE 	tbl_hd_subclassificacao.fabrica = $login_fabrica ";


} else {
	$sql = "SELECT 		tbl_hd_subclassificacao.hd_subclassificacao,
					tbl_hd_subclassificacao.descricao AS subclassificacao_descricao,
					tbl_hd_classificacao.descricao AS classificacao_descricao,
					tbl_hd_subclassificacao.ativa
			FROM 	tbl_hd_subclassificacao
			JOIN    tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_subclassificacao.hd_classificacao
				AND 	tbl_hd_classificacao.fabrica = $login_fabrica
			WHERE 	tbl_hd_subclassificacao.fabrica = $login_fabrica ";

}




$resSubmit = pg_query($con, $sql);

$layout_menu = "cadastro";
$title = "{$text_subclass} Call-Center";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$("select").select2();

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var that     = $(this);
				var hd_subclassificacao = $(this).attr("rel");
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", hd_subclassificacao: hd_subclassificacao },
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
				var hd_subclassificacao = $(this).attr("rel");

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", hd_subclassificacao: hd_subclassificacao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;
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

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if(strlen($msg_success) > 0){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<?php if ($login_fabrica <> 189) {?>
			<div class='span4'>
				<div class='control-group <?=(in_array("classificacao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='classificacao'><?php echo $text_class;?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name="classificacao" id="classificacao">
								<option value=""></option>
								<?php
								$sql = "SELECT hd_classificacao, descricao
										FROM tbl_hd_classificacao
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_classificacao = ( isset($classificacao) and ($classificacao == $key['hd_classificacao']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['hd_classificacao']?>" <?php echo $selected_classificacao ?> >
										<?php echo $key['descricao']?>
									</option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?php }?>
			<div class='span4'>
				<div class='control-group <?=(in_array("sub_classificacao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='sub_classificacao'>Descrição <?php echo $text_subclass;?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="sub_classificacao" name="sub_classificacao" class='span12' maxlength="145" value="<? echo $sub_classificacao ?>" >
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<button class='btn btn-warning' type="button"  onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';">Limpar</button>
		</p><br/>
</form>

<?php
if (isset($resSubmit)) {

		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";
			$count = pg_num_rows($resSubmit);
		?>
			<table id="resultado" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<?php if ($login_fabrica <> 189) {?>
						<th><?php echo $text_class;?></th>
						<?php }?>
						<th><?php echo $text_subclass;?></th>
						<th>Ação</th>
                    </tr>
				</thead>
				<tbody>
					<?php
					// tbl_hd_subclassificacao.hd_subclassificacao,
					// tbl_hd_subclassificacao.descricao AS subclassificacao_descricao,
					// tbl_hd_classificacao.descricao AS classificacao_descricao

					for ($i = 0; $i < $count; $i++) {
						$hd_subclassificacao            = pg_fetch_result($resSubmit, $i, 'hd_subclassificacao');
						$classificacao_descricao 		= pg_fetch_result($resSubmit, $i, 'classificacao_descricao');
						$subclassificacao_descricao 	= pg_fetch_result($resSubmit, $i, 'subclassificacao_descricao');
						$ativo  						= pg_fetch_result($resSubmit, $i, 'ativa');

					?>
						<tr>
								<?php if ($login_fabrica <> 189) {?>
								<td style='vertical-align: middle;'><?=$classificacao_descricao?></td>
								<?php }?>
								<td style='vertical-align: middle;'><?=$subclassificacao_descricao?></td>
								<td style='vertical-align: middle;' class='tac'>
								<?php
									if ($ativo != "t") {
										echo "<button type='button' rel='{$hd_subclassificacao}' name='ativar' class='btn btn-small btn-success' title='Ativar classificação' >Ativar</button>";
									} else {
										echo "<button type='button' rel='{$hd_subclassificacao}' name='inativar' class='btn btn-small btn-danger' title='Inativar classificação' >Inativar</button>";
									}
								?>
								</td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>

			<script>
				$.dataTableLoad({ table: "#resultado" });
			</script>
			<br />
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}



include 'rodape.php';?>
