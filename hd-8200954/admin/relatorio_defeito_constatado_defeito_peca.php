<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$defeito_constatado = $_POST['defeito_constatado'];

	if(strlen(trim($defeito_constatado)) > 0){
		$cond = " AND tbl_diagnostico.defeito_constatado = {$defeito_constatado} ";
	}

	$sql = "
		SELECT
			tbl_defeito.descricao AS descricao_defeito_peca,
			tbl_defeito_constatado.descricao AS descricao_defeito_constatado
		FROM tbl_diagnostico
		JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
		JOIN tbl_defeito ON tbl_defeito.defeito = tbl_diagnostico.defeito
		WHERE tbl_diagnostico.fabrica = {$login_fabrica}
		$cond
	";
	$resSubmit = pg_query($con, $sql);
}

$layout_menu = "cadastro";
$title = "RELATÓRIO DE DEFEITO CONSTATADO x DEFEITO DE PEÇAS";
include 'cabecalho_new.php';


$plugins = array(
	"select2",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$("select").select2();
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
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("defeito_constatado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='defeito_constatado'>Defeito constatado</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="defeito_constatado" id="defeito_constatado">
								<option value="">Selecione</option>
								<?php
								$sql = "SELECT defeito_constatado, descricao
										FROM tbl_defeito_constatado
										WHERE fabrica = $login_fabrica
										AND ativo ORDER BY descricao ASC";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_constatado = ( isset($defeito_constatado) and ($defeito_constatado == $key['defeito_constatado']) ) ? "SELECTED" : '' ;
								?>
									<option value="<?php echo $key['defeito_constatado']?>" <?php echo $selected_constatado ?> >
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
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {

		echo "<br />";
		$count = pg_num_rows($resSubmit);
	?>
		<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Defeito constatado</th>
					<th>Defeito peça</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++) {
					$descricao_defeito_peca 		= pg_fetch_result($resSubmit, $i, 'descricao_defeito_peca');
					$descricao_defeito_constatado = pg_fetch_result($resSubmit, $i, 'descricao_defeito_constatado');
				?>
					<tr>
						<td><?=$descricao_defeito_constatado?></td>
						<td><?=$descricao_defeito_peca?></td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
		<script>
			$.dataTableLoad({ table: "#resultado_os_atendimento" });
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
