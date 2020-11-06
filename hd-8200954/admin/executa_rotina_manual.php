<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

define("PATH", __DIR__ ."/../rotinas");

switch ($login_fabrica) {
	case 10:

		$configRotinasPastas = array_filter(
			scandir(PATH), function ($f) use($d) {
		       return is_dir($d . DIRECTORY_SEPARATOR . $f);
		   }
		);

	break;
	case 169:
		
		$configRotinas = [
			"/midea/gera-extrato.php" => "Gerar Extrato"
		];

	break;
	
	
}

if ($_POST["btn_acao"] == "submit") {

	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];
	$rotina  		 = $_POST['rotina'];

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!is_file(PATH.$rotina)) {
		$msg_erro["msg"][]    = "Arquivo não encontrado";
		$msg_erro["campos"][] = "rotina";
	}

	if (count($msg_erro) == 0) {

		$retorno = system("php ".PATH.$rotina." {$posto}", $ret);
		$msgSuccess = "Rotina executada com sucesso";

	} 

}


$layout_menu = "gerencia";
$title = "Execução Manual de Rotinas";
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

<script type="text/javascript">
	$(function() {
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} else if (!empty($msgSuccess)) { ?>
    <div class="alert alert-success">
                <h4><?= $msgSuccess ?></h4>
    </div>
<?php	
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<?php
		if ($login_fabrica != 10) {
		?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("rotina", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'>Ação</label>
						<div class='controls controls-row'>
								<select name="rotina" class="form-control">
									<?php
									foreach ($configRotinas as $caminho => $descricao) { ?>
										<option value="<?= $caminho ?>"><?= $descricao ?></option>
									<?php
									} ?>
								</select>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<?php
		} else { ?>
                	<div class='row-fluid'>
                                <div class='span2'></div>
                                <div class='span4'>
                                        <div class='control-group <?=(in_array("rotina", $msg_erro["campos"])) ? "error" : ""?>'>
                                                <label class='control-label' for='codigo_posto'>Ação</label>
                                                <div class='controls controls-row'>
                                                         <select name="rotina" class="form-control">
                                                          <?php
                                                                        foreach ($configRotinas as $caminho => $descricao) { ?>
                                                                                <option value="<?= $caminho ?>"><?= $descricao ?></option>
                                                                        <?php
                                                                        } ?>
                                                         </select>
                                                </div>
                                        </div>
                                </div>
                                <div class='span2'></div>
			</div>
		<?php
		}
		?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gerar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>
<?php
include 'rodape.php';?>
