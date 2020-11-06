<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];

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
			}else {
				if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month'))
				{
					$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 1 mês.";
					$msg_erro["campos"][] = "data";
				}
			}
		}
	}

	if(!$msg_erro["msg"]){
		$sql = "
				SELECT
				   tbl_treinamento.titulo AS treinamento_titulo,
				   TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')  AS treinamento_data_incio,
				   TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')  AS treinamento_data_fim,
				   tbl_linha.nome AS linha_nome,
				   tbl_familia.descricao AS familia_descricao,
				   tbl_treinamento.vagas AS treinamento_vagas,
				   tbl_treinamento_posto.tecnico_nome AS tecnico_nome,
				   tbl_treinamento_posto.tecnico_cpf AS tecnico_cpf,
				   tbl_treinamento_posto.tecnico_rg AS tecnico_rg,
				   TO_CHAR(tbl_treinamento_posto.tecnico_data_nascimento,'DD/MM/YYYY')  AS tecnico_data_nascimento,
				   tbl_treinamento_posto.tecnico_celular AS tecnico_celular,
				   tbl_treinamento_posto.tecnico_email AS tecnico_email,
				   tbl_grupo_cliente.descricao AS grupo_descricao,
				   tbl_posto.nome AS posto_descricao,
				   CASE
				      WHEN tbl_treinamento_posto.posto IS NOT NULL THEN
				         'sim_pega_posto'
				      ELSE
					      'nao_pega_posto'
					END AS pega_posto
				FROM
				   tbl_treinamento
				      JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
				         LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_treinamento.linha
				         LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_treinamento.familia
				         LEFT JOIN tbl_posto_fabrica ON tbl_treinamento_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				            LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				         LEFT JOIN tbl_cliente ON tbl_cliente.cliente = tbl_treinamento_posto.cliente
				         LEFT JOIN tbl_grupo_cliente ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente
			
				WHERE
				   tbl_treinamento.ativo IS TRUE
				   AND tbl_treinamento.fabrica = $login_fabrica
				   AND tbl_treinamento.data_inicio BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
				   AND tbl_treinamento_posto.ativo IS TRUE
				   AND tbl_treinamento_posto.confirma_inscricao IS TRUE			   
		";

		//print_r("SQL: <br><br> $sql"); exit;
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_participacao-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
						<th colspan='14' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE PARTICIPAÇÃO
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Título</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de Início</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de Fim</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Linha</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Família</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Vagas</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CPF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>RG</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Grupo</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de Nascimento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>E-mail</th>
						</tr>
					</thead>
			";

			fwrite($file, $thead);
			$tbody = "<tbody>";

			$count = pg_num_rows($resSubmit);
			
			for ($i = 0; $i < $count; $i++) {
				$treinamento_titulo      = pg_fetch_result($resSubmit, $i, 'treinamento_titulo');
				$treinamento_data_incio  = pg_fetch_result($resSubmit, $i, 'treinamento_data_incio');
				$treinamento_data_fim    = pg_fetch_result($resSubmit, $i, 'treinamento_data_fim');
				$linha_nome              = pg_fetch_result($resSubmit, $i, 'linha_nome');
				$familia_descricao       = pg_fetch_result($resSubmit, $i, 'familia_descricao');
				$treinamento_vagas       = pg_fetch_result($resSubmit, $i, 'treinamento_vagas');
				$tecnico_nome            = pg_fetch_result($resSubmit, $i, 'tecnico_nome');
				$tecnico_cpf             = pg_fetch_result($resSubmit, $i, 'tecnico_cpf');
				$tecnico_rg              = pg_fetch_result($resSubmit, $i, 'tecnico_rg');
				$tecnico_data_nascimento = pg_fetch_result($resSubmit, $i, 'tecnico_data_nascimento');
				$tecnico_celular         = pg_fetch_result($resSubmit, $i, 'tecnico_celular');
				$tecnico_email           = pg_fetch_result($resSubmit, $i, 'tecnico_email');
				$grupo_descricao         = pg_fetch_result($resSubmit, $i, 'grupo_descricao');
				$pega_posto              = pg_fetch_result($resSubmit, $i, 'pega_posto');
				$descricao_posto         = pg_fetch_result($resSubmit, $i, 'descricao_posto');
				
				$tbody .= "
					<tr>
						<td nowrap align='center' valign='top'>{$treinamento_titulo}</td>
						<td nowrap align='center' valign='top'>{$treinamento_data_incio}</td>
						<td nowrap align='center' valign='top'>{$treinamento_data_fim}</td>
						<td nowrap align='center' valign='top'>{$linha_nome}</td>
						<td nowrap align='center' valign='top'>{$familia_descricao}</td>
						<td nowrap align='center' valign='top'>{$treinamento_vagas}</td>
						<td nowrap align='center' valign='top'>{$tecnico_nome}</td>
						<td nowrap align='center' valign='top'>{$tecnico_cpf}</td>
						<td nowrap align='center' valign='top'>{$tecnico_rg}</td>
						<td nowrap align='center' valign='top'>{$grupo_descricao}</td>						
				";

				if($pega_posto == "sim_pega_posto"){
					$tbody .= "
						<td nowrap align='center' valign='top'>{$descricao_posto}</td>
					";
				}else
				{
					$tbody .= "
						<td nowrap align='center' valign='top'> </td>
					";
				}

				$tbody .= "
						<td nowrap align='center' valign='top'>{$tecnico_data_nascimento}</td>
						<td nowrap align='center' valign='top'>{$tecnico_celular}</td>
						<td nowrap align='center' valign='top'>{$tecnico_email}</td>
					</tr>
				";
			}

			fwrite($file, $tbody);
			fwrite($file, "
						<tr>
							<th colspan='14' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

			exit;
		}
	}
}
	

$layout_menu = "tecnica";
$title = "Relatório de Participação";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
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
		<table id="resultado_relatorio_participacao" class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class="titulo_coluna">
					<th>Título</th>
					<th>Data de Início</th>
					<th>Data de Fim</th>
					<th>Linha</th>
					<th>Família</th>
					<th>Vagas</th>
					<th>Nome</th>
					<th>CPF</th>
					<th>RG</th>
					<th>Grupo</th>
					<th>Posto</th>
					<th>Data de Nascimento</th>
					<th>Telefone</th>
					<th>E-mail</th>
				</tr>
			<thead>
			<tbody>
				<?
					for ($i = 0; $i < $count; $i++) {
						$treinamento_titulo      = pg_fetch_result($resSubmit, $i, 'treinamento_titulo');
						$treinamento_data_incio  = pg_fetch_result($resSubmit, $i, 'treinamento_data_incio');
						$treinamento_data_fim    = pg_fetch_result($resSubmit, $i, 'treinamento_data_fim');
						$linha_nome              = pg_fetch_result($resSubmit, $i, 'linha_nome');
						$familia_descricao       = pg_fetch_result($resSubmit, $i, 'familia_descricao');
						$treinamento_vagas       = pg_fetch_result($resSubmit, $i, 'treinamento_vagas');
						$tecnico_nome            = pg_fetch_result($resSubmit, $i, 'tecnico_nome');
						$tecnico_cpf             = pg_fetch_result($resSubmit, $i, 'tecnico_cpf');
						$tecnico_rg              = pg_fetch_result($resSubmit, $i, 'tecnico_rg');
						$tecnico_data_nascimento = pg_fetch_result($resSubmit, $i, 'tecnico_data_nascimento');
						$tecnico_celular         = pg_fetch_result($resSubmit, $i, 'tecnico_celular');
						$tecnico_email           = pg_fetch_result($resSubmit, $i, 'tecnico_email');
						$grupo_descricao         = pg_fetch_result($resSubmit, $i, 'grupo_descricao');
						$pega_posto              = pg_fetch_result($resSubmit, $i, 'pega_posto');
						$descricao_posto         = pg_fetch_result($resSubmit, $i, 'descricao_posto');

						$body = "
							<tr>
								<td class='tal'>{$treinamento_titulo}</td>
								<td class='tal'>{$treinamento_data_incio}</td>
								<td class='tal'>{$treinamento_data_fim}</td>
								<td class='tal'>{$linha_nome}</td>
								<td class='tal'>{$familia_descricao}</td>
								<td class='tal'>{$treinamento_vagas}</td>
								<td class='tal'>{$tecnico_nome}</td>
								<td class='tal'>{$tecnico_cpf}</td>
								<td class='tal'>{$tecnico_rg}</td>
								<td class='tal'>{$grupo_descricao}</td>
						";

						if($pega_posto == "sim_pega_posto"){
							$body .= "
								<td class='tal'>{$descricao_posto}</td>
							";
						}else
						{
							$body .= "
								<td class='tal'> </td>
							";
						}

						$body .= "
								<td class='tal'>{$tecnico_data_nascimento}</td>
								<td class='tal'>{$tecnico_celular}</td>
								<td class='tal'>{$tecnico_email}</td>
							</tr>
						";

						echo $body;
					}
				?>
			</tbody>
		</table>

		<?
			if ($count > 50) {
		?>
				<script>
					$.dataTableLoad({ table: "#resultado_relatorio_participacao" });
				</script>
		<? } ?>

		<br>

		<?
			$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
		
		<? } else { ?>

			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>
		<?
	}
}

include 'rodape.php';?>