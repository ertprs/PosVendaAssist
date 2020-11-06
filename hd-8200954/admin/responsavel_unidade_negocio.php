<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro,gerencia,cadastro";

include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['btn_cadastro'])) {

	$unidade_negocio = $_POST['unidade_negocio'];
	$admin = $_POST['admin'];

	if (empty($admin)) {
		$msg_erro["msg"][]    = "Informe um admin";
		$msg_erro["campos"][] = "admin";
	}

	if (count($unidade_negocio) == 0) {
		$msg_erro["msg"][]    = "Informe ao menos uma unidade de negócio";
		$msg_erro["campos"][] = "unidade_negocio";
	}

	if (count($msg_erro) == 0) {
		$sqlParametrosAdmin = "SELECT parametros_adicionais
							   FROM tbl_admin
							   WHERE fabrica = {$login_fabrica}
							   AND admin = {$admin}";
		$resParametrosAdmin = pg_query($con, $sqlParametrosAdmin);

		$array_parametros = json_decode(pg_fetch_result($resParametrosAdmin, 0, 'parametros_adicionais', true));
		$array_parametros['unidades_interacao_os'] = $unidade_negocio;
		$json_parametros = json_encode($array_parametros);

		pg_query($con, "UPDATE tbl_admin SET parametros_adicionais = '{$json_parametros}'
						WHERE admin = $admin AND fabrica = {$login_fabrica}");

		if (!pg_last_error()) {
			$msg_sucesso 	   = "Cadastro/Alteração realizado com sucesso";
			$admin = "";
		} else {
			$msg_erro["msg"][] = "Erro ao efetuar cadastro, entrar em contato com a Telecontrol";
		}

	}
} else if (isset($_POST['btn_exclui'])) {
	$admin = $_POST['admin'];

	$sqlParametrosAdmin = "SELECT parametros_adicionais
							   FROM tbl_admin
							   WHERE fabrica = {$login_fabrica}
							   AND admin = {$admin}";
	$resParametrosAdmin = pg_query($con, $sqlParametrosAdmin);

	$array_parametros = json_decode(pg_fetch_result($resParametrosAdmin, 0, 'parametros_adicionais', true));
	unset($array_parametros['unidades_interacao_os']);
	$json_parametros = json_encode($array_parametros);

	if ($json_parametros == 'null') {
		$json_parametros = "";
	}

	pg_query($con, "UPDATE tbl_admin SET parametros_adicionais = '{$json_parametros}'
					WHERE admin = $admin AND fabrica = {$login_fabrica}");

	if (!pg_last_error()) {
		$msg_sucesso 	   = "Registro excluído com sucesso";
		$admin = "";
	} else {
		$msg_erro["msg"][] = "Erro ao excluir registro, entre em contato com a Telecontrol";
	}
}

$sqlPesquisa = "SELECT admin,
					   nome_completo,
					   parametros_adicionais::jsonb->>'unidades_interacao_os' as unidades,
					   email
				FROM tbl_admin
				WHERE parametros_adicionais IS NOT NULL
				AND parametros_adicionais NOT IN ('{}','f','t','')
				AND parametros_adicionais::jsonb->>'unidades_interacao_os' IS NOT NULL
				AND fabrica = {$login_fabrica}";
$resPesquisa = pg_query($con, $sqlPesquisa);

$layout_menu = "cadastro";
$title = "Unidades de Negócios x E-mails";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");

?>
<script>
	$(function(){
		$("#unidade_negocio").multiselect({
            selectedText: "selecionados # de #"
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
} else if (!empty($msg_sucesso)) { ?>
	<div class="alert alert-success">
		<h4><?= $msg_sucesso ?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela'>Parâmetros de Cadastro</div>
	<br/>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("admin", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='admin'>Admin</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<?php
						if (isset($_GET['admin'])) {
							$cond_admin = "AND admin = ".$_GET['admin'];
							$readonly   = "readonly";
						}
						?>
						<select name="admin" id="admin" <?= $readonly ?>>
							
							<?php
							if (!isset($_GET['admin'])) { ?>
								<option value=""></option>
							<?php
							}

							$sql = "SELECT admin, nome_completo
									FROM tbl_admin
									WHERE ativo IS TRUE
									AND fabrica = {$login_fabrica}
									{$cond_admin}";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_admin = (isset($admin) and ($admin == $key['admin'])) ? "SELECTED" : '';

							?>
								<option value="<?php echo $key['admin']?>" <?php echo $selected_admin ?> >

									<?php echo $key['nome_completo']?>

								</option>
							<?php
							} ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='unidade_negocio'>Unidade de Negócio</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<select name="unidade_negocio[]" id="unidade_negocio" multiple>
							<?php
							$sql = "SELECT unidade_negocio, codigo, nome
									FROM tbl_unidade_negocio";
							$res = pg_query($con,$sql);

							if (isset($_GET['admin'])) {
								$sqlUnidadesAdmin = "SELECT parametros_adicionais::jsonb->>'unidades_interacao_os' as unidades
													FROM tbl_admin
													WHERE parametros_adicionais IS NOT NULL
													AND parametros_adicionais NOT IN ('{}','f','t')
													AND parametros_adicionais::jsonb->>'unidades_interacao_os' IS NOT NULL
													AND fabrica = {$login_fabrica}
													AND admin = ".$_GET['admin'];
								$resUnidadesAdmin = pg_query($con, $sqlUnidadesAdmin);

								$unidades_negocio = json_decode(pg_fetch_result($resUnidadesAdmin, 0, 'unidades'), true);

							}

							foreach (pg_fetch_all($res) as $key) {
								$selected_un = (count($unidades_negocio) > 0 && in_array($key['unidade_negocio'], $unidades_negocio)) ? "SELECTED" : '' ;

							?>
								<option value="<?php echo $key['unidade_negocio']?>" <?php echo $selected_un ?> >

									<?= $key['codigo'] . " - " . $key['nome'] ?>

								</option>
							<?php
							} ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<br />
	<div class="row-fluid tac">
		<input class="btn btn-primary" type="submit" name="btn_cadastro" value="<?= (isset($_GET['admin'])) ? "Alterar" : "Cadastrar" ?>" />
		<?php
		if (isset($_GET['admin'])) { ?>
			<input class="btn btn-danger" type="submit" name="btn_exclui" value="Excluir" />
		<?php
		} ?>
	</div>
</form>

<?php
if (pg_num_rows($resPesquisa) > 0) { ?>
	<table class="table table-bordered" style="width: 800px;">
		<thead>
			<tr class="titulo_coluna">
				<th>Admin</th>
				<th>E-mail</th>
				<th>Unidades de Negócio</th>
			</tr>
		</thead>
		<tbody>
		<?php
		while ($linha = pg_fetch_array($resPesquisa)) { 

			$admin            = $linha['admin'];
			$nome_completo    = $linha['nome_completo'];
			$email            = $linha['email'];
			$unidades_negocio = json_decode($linha['unidades'], true);
			$atende_unidades  = implode(",", $unidades_negocio);

			$sqlUnidades = "SELECT string_agg(codigo || ' - ' || nome, '<br />') as unidades_atende
							FROM tbl_unidade_negocio
							WHERE unidade_negocio IN ($atende_unidades)";
			$resUnidades = pg_query($con, $sqlUnidades);

			$unidades_admin_atende = pg_fetch_result($resUnidades, 0, 'unidades_atende');
		?>
			<tr>
				<td style="font-size: 14px; text-align: center;vertical-align: middle;">
					<a href="<?= $_SERVER['PHP_SELF'] ?>?admin=<?= $admin ?>">
						<?= $nome_completo ?>
					</a>
				</td>
				<td style="font-size: 14px; text-align: center;vertical-align: middle;"><?= $email ?></td>
				<td><?= $unidades_admin_atende ?></td>
			</tr>
		<?php
		} ?>
		</tbody>
	</table>
<script>
	$.dataTableLoad({ table: ".table" });
</script>
<?php
}

include 'rodape.php';
?>