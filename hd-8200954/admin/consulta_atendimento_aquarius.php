<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao       = $_POST['btn_acao'];


if($btn_acao == "submit"){
	$data_inicial		= $_POST["data_inicial"];
	$data_final			= $_POST["data_final"];
	$hd_chamado         = $_POST["hd_chamado"];
	$nome			= $_POST["nome"];
	$cpfcnpj				= $_POST["cpfcnpj"];

	# Validações
	if ((!strlen($data_inicial) || !strlen($data_final)) && !strlen($hd_chamado)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "data";
	}


	if(!count($msg_erro["msg"])){

		if(strlen($hd_chamado) > 0){
			$cond = " AND tbl_aquarius_hd_chamado.codigo = '$hd_chamado' ";
		}

		if(strlen($status) > 0){
			$cond .= " AND tbl_aquarius_hd_chamado.os = '{$status}' ";
		}

		if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

			list($d,$m,$y) = explode("/", $data_inicial);

			if(!checkdate($m, $d, $y)){
				$msg_erro['msg'] = "Data inicial inválida";
			}else{
				$data_inicial_formatada = "$y-$m-$d 00:00:00";
			}


			list($d,$m,$y) = explode("/", $data_final);

			if(!checkdate($m, $d, $y)){
				$msg_erro['msg'] = "Data final inválida";
			}else{
				$data_final_formatada = "$y-$m-$d 00:00:00";
			}

			$cond .= " AND tbl_aquarius_hd_chamado.datacadastro BETWEEN '$data_inicial_formatada' and '$data_final_formatada' ";

		}

	}

	if(!empty($descricao_posto)){
		$cond .= " AND tbl_aquarius_hd_chamado.posto ilike '$descricao_posto%' ";
	}

	if(!empty($produto_referencia)){
		$cond .= " AND tbl_aquarius_hd_chamado.produto = '$produto_referencia' ";
	}

	if(!empty($nome)){
		$cond .= " AND tbl_aquarius_hd_chamado.nome ilike '$nome%' ";
	}

	if(!empty($cpfcnpj)){
		$cpfcnpj = str_replace("-", "", $cpfcnpj);
		$cpfcnpj = str_replace(".", "", $cpfcnpj);
		$cpfcnpj = str_replace("/", "", $cpfcnpj);

		$cond .= " AND tbl_aquarius_hd_chamado.cpfcnpj = '$cpfcnpj' ";
	}

	if(count($msg_erro['msg']) == 0){

		$sql = "SELECT  tbl_aquarius_hd_chamado.codigo,
					    to_char(tbl_aquarius_hd_chamado.datacadastro,'DD/MM/YYYY') AS data_abertura,
						nome         ,
						cpfcnpj      ,
						contato      ,
						email        ,
						endereco     ,
						bairro       ,
						cidade       ,
						estado       ,
						telefone     ,
						celular      ,
						data         ,
						horaini      ,
						horafim      ,
						origemticket ,
						status       ,
						usuario      ,
						datacadastro ,
						acao

					FROM tbl_aquarius_hd_chamado
					WHERE 1 = 1 $cond";
		$resSubmit = pg_query($con,$sql);

	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_historico-atendimentos-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
			<table align='center' id='resultado_os' class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Atendente</th>
						<th>Data Abertura</th>
						<th>Nº Atendimento</th>
						<th>Cliente</th>
						<th>CPF/CNPJ</th>
						<th>Cidade</th>
						<th>Estado</th>
						<th>Status</th>
            		</tr>
                </thead>
				<tbody>
			";
			fwrite($file, $thead);

			for($j = 0; $j < pg_num_rows($resSubmit); $j++){

					$codigo 			= pg_fetch_result($resSubmit,$j,'codigo');
					$data_abertura 		= pg_fetch_result($resSubmit,$j,'data_abertura');
					$usuario 		= utf8_decode(pg_fetch_result($resSubmit,$j,'usuario'));
					$status 				= utf8_decode(pg_fetch_result($resSubmit,$j,'status'));
					$estado 				= pg_fetch_result($resSubmit,$j,'estado');
					$cidade 			= utf8_decode(pg_fetch_result($resSubmit,$j,'cidade'));
					$nome 		= utf8_decode(pg_fetch_result($resSubmit,$j,'nome'));
					$cpfcnpj 			= pg_fetch_result($resSubmit,$j,'cpfcnpj');
					$acao_cod 			= pg_fetch_result($resSubmit,$j,'acao_cod');

					$tbody .= "<tr>
							<td>$usuario</td>
							<td nowrap>$data_abertura</td>
							<td><button class='btn btn-link' onclick=\"window.open('atendimento_detalhado_aquarius.php?codigo=$codigo')\">$codigo</button></td>
							<td>$nome</td>
							<td>$cpfcnpj</td>
							<td>$cidade</td>
							<td>$estado</td>
							<td>$status</td>
													 </tr>";

			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='20' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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

$layout_menu = "callcenter";
$title = "CONSULTA HISTÓRICO ORDEM DE SERVIÇO";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include("plugin_loader.php");


$inputs = array(
	"data_inicial" => array(
		"span"     => 4,
		"label"    => "Data Inicial",
		"type"     => "input/text",
		"width"    => 5,
		"required" => true
	),
	"data_final" => array(
		"span"     => 4,
		"label"    => "Data Final",
		"type"     => "input/text",
		"width"    => 5,
		"required" => true
	),
	"hd_chamado" => array(
		"span"     => 4,
		"label"    => "Nº Atendimento",
		"type"     => "input/text",
		"width"    => 5
	),
	"nome" => array(
		"span"     => 4,
		"label"    => "Nome Cliente",
		"type"     => "input/text",
		"width"    => 5
	),
	"cpfcnpj" => array(
		"span"     => 4,
		"label"    => "CPF/CNPJ",
		"type"     => "input/text",
		"width"    => 5
	),

);



?>

	<script>
		$(function(){

			Shadowbox.init();

			$.datepickerLoad(Array("data_final", "data_inicial"));

			$('#data_inicial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");

			$("span[rel=descricao_posto],span[rel=codigo_posto],span[rel=produto_referencia],span[rel=produto_descricao]").click(function () {
				$.lupa($(this));
			});


		});

		function retorna_posto(retorno){
	        $("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
	    }

	    function retorna_produto (retorno) {
			$("#produto_referencia").val(retorno.referencia);
			$("#produto_descricao").val(retorno.descricao);
		}
	</script>

	<div class="container">

		<?php
		/* Erro */
		if (count($msg_erro["msg"]) > 0) {
		?>
			<div class="alert alert-error">
				<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
			</div>
		<?php } ?>

		<div class="container">
			<strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
		</div>

		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

			<div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>

			<? echo montaForm($inputs,null);?>

			<p>
				<br/>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>

			<br/>

		</form>

	</div>

</div>

<?php

	if($btn_acao == "submit"){

		if (pg_num_rows($resSubmit) > 0) {

			$count = pg_num_rows($resSubmit);

			?>
			<table align="center" id="resultado_os" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Atendente</th>
						<th>Data Abertura</th>
						<th>Nº Atendimento</th>
						<th>Cliente</th>
						<th>CPF/CNPJ</th>
						<th>Cidade</th>
						<th>Estado</th>
						<th>Status</th>
            		</tr>
                </thead>
				<tbody>
		<?php

				for($i = 0; $i < pg_num_rows($resSubmit); $i++){

					$codigo 			= pg_fetch_result($resSubmit,$i,'codigo');
					$data_abertura 		= pg_fetch_result($resSubmit,$i,'data_abertura');
					$usuario 		= utf8_decode(pg_fetch_result($resSubmit,$i,'usuario'));
					$status 				= utf8_decode(pg_fetch_result($resSubmit,$i,'status'));
					$estado 				= pg_fetch_result($resSubmit,$i,'estado');
					$cidade 			= utf8_decode(pg_fetch_result($resSubmit,$i,'cidade'));
					$nome 		= utf8_decode(pg_fetch_result($resSubmit,$i,'nome'));
					$cpfcnpj 			= pg_fetch_result($resSubmit,$i,'cpfcnpj');
					$acao_cod 			= pg_fetch_result($resSubmit,$i,'acao_cod');

					echo "<tr>
							<td>$usuario</td>
							<td nowrap>$data_abertura</td>
							<td><button class='btn btn-link' onclick=\"window.open('atendimento_detalhado_aquarius.php?codigo=$codigo')\">$codigo</button></td>
							<td>$nome</td>
							<td>$cpfcnpj</td>
							<td>$cidade</td>
							<td>$estado</td>
							<td>$status</td>
													 </tr>";
				}

				echo "</table>";

				if ($count > 50) {
				?>
					<script>
						$.dataTableLoad({ table: "#resultado_os" });
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

/* Rodapé */
	include 'rodape.php';
?>
