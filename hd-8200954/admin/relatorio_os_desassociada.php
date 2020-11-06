<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro,gerencia,auditoria";

include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
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

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			} else {
				if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) ) {
					$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 1 mês";
					$msg_erro["campos"][] = "data";
				} 
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			//$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		$sql = "SELECT tbl_hd_chamado_extra.os
				INTO TEMP tmp_hd_chamado_os
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado_extra.os IS NOT NULL";
		$res = pg_query($con, $sql);

		$sql = "SELECT tbl_os.os, tbl_os.sua_os, tbl_os.hd_chamado, tbl_os_extra.extrato
				FROM tbl_os
				JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
				LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = {$login_fabrica}
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.hd_chamado IS NOT NULL
				AND tbl_os.data_digitacao >= '2014-03-28'
				AND tbl_os.os NOT IN (
					SELECT os FROM tmp_hd_chamado_os
				)
				AND (
					(tbl_os_extra.extrato IS NOT NULL AND tbl_os_extra.extrato NOT IN (0) AND tbl_extrato.aprovado IS NULL)
					OR
					(tbl_os_extra.extrato IS NULL)
				)
				AND tbl_os_extra.os_faturamento IS NULL
				{$cond_posto}";
		$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "auditoria";
$title       = "RELATÓRIO DE OS DESASSOCIADA";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask"
);

include("plugin_loader.php");
?>

<style>
	.alert-success, .alert-error {
		padding-bottom: 2px;
		padding-top: 2px;
		margin-bottom: 0px;
	}
</style>

<script>
	$(function () {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("button[name=btn_aprova_os_extrato]").click(function () {
			if (confirm("Deseja realmente aprovar a entrada da OS no proximo extrato gerado para o posto ?")) {
				var td   = $(this).parents("td");
				var data = getData(td);

				$.ajax({
					url: "relatorio_os_desassociada_ajax.php",
					type: "POST",
					data: { acao: "aprova_os_extrato", os: data.os },
					beforeSend: function () {
						$(td).find("div[name=acao]").hide();
						$(td).find("div[name=loading]").show();
					},
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.erro) {
							alert(data.erro);
							$(td).find("div[name=loading]").show();
						} else {
							$(td).html("<div class='alert alert-success'><h4>OS aprovada para extrato</h4></div>");
						}

						$(td).find("div[name=loading]").hide();
					}
				});
			}
		});

		$("button[name=btn_reprova_os_extrato]").click(function () {
			if (confirm("Deseja realmente reprovar a entrada da OS no extrato ? A OS não irá mais entrar em nenhum extrato gerado")) {
				var td   = $(this).parents("td");
				var data = getData(td);

				$.ajax({
					url: "relatorio_os_desassociada_ajax.php",
					type: "POST",
					data: { acao: "reprova_os_extrato", os: data.os },
					beforeSend: function () {
						$(td).find("div[name=acao]").hide();
						$(td).find("div[name=loading]").show();
					},
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.erro) {
							alert(data.erro);
							$(td).find("div[name=loading]").show();
						} else {
							$(td).html("<div class='alert alert-error'><h4>OS reprovada para extrato</h4></div>");
						}

						$(td).find("div[name=loading]").hide();
					}
				});
			}
		});

		$("button[name=btn_exclui_os_extrato]").click(function () {
			if (confirm("Deseja realmente excluir a OS do extrato e recalcular o extrato ?")) {
				var td   = $(this).parents("td");
				var data = getData(td);

				$.ajax({
					url: "relatorio_os_desassociada_ajax.php",
					type: "POST",
					data: { acao: "exclui_os_extrato", os: data.os, extrato: data.extrato },
					beforeSend: function () {
						$(td).find("div[name=acao]").hide();
						$(td).find("div[name=loading]").show();
					},
					complete: function (data) {
						data = $.parseJSON(data.responseText);

						if (data.erro) {
							alert(data.erro);
							$(td).find("div[name=loading]").show();
						} else {
							$(td).html("<div class='alert alert-error'><h4>OS excluida do extrato</h4></div>");
						}

						$(td).find("div[name=loading]").hide();
					}
				});
			}
		});
	});

	function getData (td) {
		return {
			os: $(td).find("input[name=os]").val(),
			extrato: $(td).find("input[name=extrato]").val()
		};
	}

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
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

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
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php
if (isset($resSubmit)) {
	$rows = pg_num_rows($resSubmit);

	if ($rows > 0) {
		echo "<br />";
		?>

		<table class="table table-striped table-bordered table-hover table-large" >
			<thead>
				<tr class="titulo_coluna" >
					<th>OS</th>
					<th>Atendimento</th>
					<th>Extrato</th>
					<th>Ação</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $rows; $i++) {
					$os         = pg_fetch_result($resSubmit, $i, "os");
					$sua_os     = pg_fetch_result($resSubmit, $i, "sua_os");
					$hd_chamado = pg_fetch_result($resSubmit, $i, "hd_chamado");
					$extrato    = pg_fetch_result($resSubmit, $i, "extrato");

					echo "<tr>
						<td class='tac' ><a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a></td>
						<td class='tac' ><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank'>{$hd_chamado}</a></td>
						<td class='tac' ><a href='extrato_consulta_os.php?extrato={$extrato}' target='_blank'>{$extrato}</a></td>
						<td>
							<input type='hidden' name='os' value='{$os}' />
							<input type='hidden' name='extrato' value='{$extrato}' />
							<div class='tac' name='acao'>";
								if (!empty($extrato)) {
									echo "<button type='button' class='btn btn-small btn-danger' name='btn_exclui_os_extrato' title='Excluir a OS do extrato e recalcular o extrato' >Excluir do extrato</button>";
								} else {
									echo "<button type='button' class='btn btn-small btn-success' name='btn_aprova_os_extrato' title='Aprovar a entrada da OS no extrato' >Aprovar</button>
									<button type='button' class='btn btn-small btn-danger' name='btn_reprova_os_extrato' title='Reprovar a entrada da OS no extrato' >Reprovar</button>";
								}
							echo "</div>
							<div class='tac' name='loading' style='display: none;'>
								<img src='imagens/loading_img.gif' style='width: 22px; height: 22px;' />
							</div>
						</td>
					</tr>";
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
		echo "<div class='container'>
			<div class='alert'>
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>";
	}
}

include 'rodape.php';
?>
