<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['pesquisar'])) {

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$atendente    = $_POST['atendente'];

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
			}
		}
	}

	if (!empty($atendente)) {

		$sql = "SELECT external_id
				FROM tbl_admin
				WHERE admin = {$atendente}
				AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$external_id_adm = pg_fetch_result($res, 0, 'external_id');

	}

	if (count($msg_erro) == 0) {

		$queryString = "https://api2.telecontrol.com.br/telefonia/relatorio-ligacoes-fabrica/inicio/{$aux_data_inicial}/final/{$aux_data_final}/companhia/10/fabrica/{$login_fabrica}/setor/SAC";

		if (!empty($external_id_adm)) {
			$queryString .= "/externalId/".$external_id_adm;
		}

		$curlData = curl_init();

		curl_setopt_array($curlData, array(
			CURLOPT_URL => $queryString,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_HTTPHEADER => array(
			 	"Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
	            "Access-Env: PRODUCTION",
	            "Cache-Control: no-cache",
	            "Content-Type: application/json"
			),
		));

		$responseData = curl_exec($curlData);
				
		$dadosTelefonia = json_decode($responseData, true);

	}

	if (isset($_POST['gerar_excel'])) {

		$resExcel = pg_query($con, $sqlExcel);

		$data = date("d-m-Y-H:i");
		$fileName = "relatorio_atendentes-{$data}.csv";

		$file = fopen("/tmp/{$fileName}", "w");

		fwrite($file, "Atendente;Telefone Discado;Data Discagem;Início da Ligação;Final da Ligação;Duração da Ligação \n");

		foreach ($dadosTelefonia as $chave => $value) {

			$tbody .= $value['nome']." ".$value["sobrenome"].";".
					  $value['telefone_cliente'].";".
					  mostra_data($value["criado"]).";".
					  mostra_data($value['inicio']).";".
					  mostra_data($value['final']).";".
					  $value['duracao_ligacao'].";\n";
		
		}

		fwrite($file, $tbody);

		fclose($file);

		if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");

			echo "xls/{$fileName}";
		}

		exit;

	}

}

$layout_menu = "gerencia";
$title = "Relatório Atendentes SAC";
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
	$(function() {

		Shadowbox.init();
		$.datepickerLoad(Array("data_final", "data_inicial"));

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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
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
	<div class="row-fluid">
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='data_inicial'>Atendente</label>
				<div class='controls controls-row'>
					<div class='span5'>
						<select name="atendente" id="atendente" class="form-contro">
							<option value="">Selecionar</option>
							<?php
							$sqlAtendentes = "SELECT admin, nome_completo
											  FROM tbl_admin
											  WHERE fabrica = {$login_fabrica}
											  AND ativo IS TRUE
											  AND JSON_FIELD('sacTelecontrol', tbl_admin.parametros_adicionais) = 'true'
											  ORDER BY nome_completo";
							$resAtendentes = pg_query($con, $sqlAtendentes);

							while ($dadosAtendentes = pg_fetch_object($resAtendentes)) {

								$selected = ($atendente == $dadosAtendentes->admin) ? "selected" : "";

								?>

								<option value="<?= $dadosAtendentes->admin ?>" <?= $selected ?>><?= $dadosAtendentes->nome_completo ?></option>

							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
    <br />
    <div class="row-fluid tac">
    	<input type="submit" class="btn" name="pesquisar" value="pesquisar" />
    </div>
</form>
</div>
<br />
<?php
if (isset($_POST["pesquisar"])) {

	$jsonPOST = excelPostToJson($_POST);

?>
	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
		<span class="txt">Gerar Arquivo CSV</span>
	</div>
	<br />
	<table class="table table-bordered table-large">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="7">Relatório de Ligações Atendentes</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Atendente</th>
				<th>Telefone Discado</th>
				<th>Data Discagem</th>
				<th>Inicio da Ligação</th>
				<th>Final da Ligação</th>
				<th>Duração da Ligação</th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ($dadosTelefonia as $chave => $value) { ?>
			<tr>
				<td class="tac"><?= $value['nome']." ".$value["sobrenome"] ?></td>
				<td><?= $value['telefone_cliente'] ?></td>
				<td class="tac"><?= mostra_data($value["criado"]) ?></td>
				<td class="tac"><?= mostra_data($value['inicio']) ?></td>
				<td class="tac"><?= mostra_data($value['final']) ?></td>
				<td class="tac"><?= $value['duracao_ligacao'] ?></td>
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

include("rodape.php");
?>