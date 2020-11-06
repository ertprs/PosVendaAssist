<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$linha              = $_POST['linha'];
	$tipo_posto 		= $_POST['tipo_posto'];
	
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

	if (strlen($linha) > 0) {
		$sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND linha = {$linha}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Linha não encontrada";
			$msg_erro["campos"][] = "linha";
		}
	}

	if (strlen($tipo_posto) > 0) {
		$sql = "SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$tipo_posto}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Tipo de posto não encontrado";
			$msg_erro["campos"][] = "linha";
		}
	}

	if (!count($msg_erro["msg"])) {

		if (!empty($posto)) {
			$cond_posto = " AND tbl_posto.posto = {$posto} ";
		}

		if (!empty($linha)) {
			$cond_linha = " AND tbl_posto_linha.linha = {$linha} ";
		}

		if (!empty($tipo_posto)){
			$cond_tipo_posto = "AND tbl_tipo_posto.tipo_posto = {$tipo_posto} ";
		}
		
		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "
			SELECT
				tbl_posto.nome,
				tbl_posto.cnpj,
				tbl_posto_fabrica.senha,
				tbl_posto_fabrica.codigo_posto,
				tbl_linha.nome AS linha,
				tbl_tipo_posto.descricao AS tipo_posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
			JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto
			JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_tipo_posto.codigo NOT IN ('Rev', 'Int')
			{$cond_posto}
			{$cond_tipo_posto}
			{$cond_linha}";
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_os_atendimento-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='6' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								POSTOS PRIMEIRO ACESSO
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CÓDIGO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>NOME</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>LINHA</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>TIPO POSTO	</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>PRIMEIRO ACESSO</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				$senha      = pg_fetch_result($resSubmit, $i, 'senha');
				$nome       = pg_fetch_result($resSubmit, $i, 'nome');
				$cnpj       = pg_fetch_result($resSubmit, $i, 'cnpj');
				$codigo     = pg_fetch_result($resSubmit, $i, 'codigo_posto');
				$linha      = pg_fetch_result($resSubmit, $i, 'linha');
				$tipo_posto = pg_fetch_result($resSubmit, $i, 'tipo_posto');
				if(strlen(trim($senha)) > 3){
					$senha = "SIM";
				}else{
					$senha = "NÃO";
				}
				$body .="
							<tr>
								<td nowrap align='center' valign='top'>{$codigo}</td>
								<td nowrap align='center' valign='top'>{$nome}</td>
								<td nowrap align='center' valign='top'>{$cnpj}</td>
								<td nowrap align='center' valign='top'>{$linha}</td>
								<td nowrap align='center' valign='top'>{$tipo_posto}</td>
								<td nowrap align='center' valign='top'>{$senha}</td>
							</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='6' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
}
$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRIMEIRO ACESSO POSTO";
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
		$.autocompleteLoad(Array("posto"));
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
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<!-- <div class='row-fluid'>
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
		</div> -->
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
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="linha" id="linha">
								<option value=""></option>
								<?php
								$sql = "SELECT linha, nome
										FROM tbl_linha
										WHERE fabrica = $login_fabrica
										AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

										<?php echo $key['nome']?>

									</option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("tipo_posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='tipo_posto'>Tipo Posto</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="tipo_posto" id="tipo_posto">
								<option value=""></option>
								<?php
								$sql = "SELECT tipo_posto, descricao, codigo
										FROM tbl_tipo_posto
										WHERE fabrica = $login_fabrica
										AND ativo
										AND codigo NOT IN ('Rev', 'Int')";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_tipo_posto = ( isset($tipo_posto) and ($tipo_posto == $key['tipo_posto']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['tipo_posto']?>" <?php echo $selected_tipo_posto ?> >

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
</div>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
			<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna'>
						<th>CÓDIGO</th>
						<th>NOME</th>
						<th>CNPJ</th>
                        <th>LINHA</th>
                        <th>TIPO POSTO</th>
						<th>PRIMEIRO ACESSO</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$senha      = pg_fetch_result($resSubmit, $i, 'senha');
						$nome       = pg_fetch_result($resSubmit, $i, 'nome');
						$cnpj       = pg_fetch_result($resSubmit, $i, 'cnpj');
						$codigo     = pg_fetch_result($resSubmit, $i, 'codigo_posto');
						$linha      = pg_fetch_result($resSubmit, $i, 'linha');
						$tipo_posto = pg_fetch_result($resSubmit, $i, 'tipo_posto');

						if(strlen(trim($senha)) > 3){
							$ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
						}else{
							$ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
						}
					?>
						<tr>
							<td class='tac'><?=$codigo?></td>
							<td><?=$nome?></td>
							<td class='tac'><?=$cnpj?></td>
							<td><?=$linha?></td>
							<td><?=$tipo_posto?></td>
							<td class='tac'><?=$ativo?></td>
						</tr>
					<?php	
					}
					?>
				</tbody>
			</table>

			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_atendimento" });
				</script>
			<?php
			}
			?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
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
