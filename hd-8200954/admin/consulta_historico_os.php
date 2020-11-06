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
	$os                 = $_POST["os"];
	$codigo_posto       = $_POST["codigo_posto"];
	$descricao_posto    = $_POST["descricao_posto"];
	$produto_referencia	= $_POST["produto_referencia"];
	$produto_descricao	= $_POST["produto_descricao"];

	# Validações
	if ((!strlen($data_inicial) || !strlen($data_final)) && !strlen($os)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "data";
	}

	
	if(!count($msg_erro["msg"])){

		if(strlen($os) > 0){
			$cond = " AND tbl_os_unicoba.os = $os ";
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

			$cond .= " AND tbl_os_unicoba.data_abertura BETWEEN '$data_inicial_formatada' and '$data_final_formatada' ";

		}

	}

	if(!empty($descricao_posto)){
		$cond .= " AND tbl_os_unicoba.posto_autorizado ilike '$descricao_posto%' ";
	}

	if(!empty($produto_referencia)){
		$cond .= " AND tbl_os_unicoba.modelo = '$produto_referencia' ";
	}

	if(!empty($cliente)){
		$cond .= " AND tbl_os_unicoba.cliente ilike '$cliente%' ";
	}

	if(!empty($cpf)){
		$cpf = str_replace("-", "", $cpf);
		$cpf = str_replace(".", "", $cpf);
		$cpf = str_replace("/", "", $cpf);

		$cond .= " AND tbl_os_unicoba.cpf = '$cpf' ";
	}

	if(count($msg_erro['msg']) == 0){

		$sql = "SELECT  tbl_os_unicoba.os,
					    to_char(tbl_os_unicoba.data_abertura,'DD/MM/YYYY') AS data_abertura,
					    tbl_os_unicoba.serie,
					    tbl_os_unicoba.nota,
					    tbl_os_unicoba.data_compra,
					    tbl_os_unicoba.defeito_reclamado,
					    tbl_os_unicoba.acessorios,
					    tbl_os_unicoba.cliente,
					    tbl_os_unicoba.cpf,
					    tbl_os_unicoba.ddd_1,
					    tbl_os_unicoba.telefone_1,
					    tbl_os_unicoba.ddd_2,
					    tbl_os_unicoba.telefone_2,
					    tbl_os_unicoba.ddd_3,
					    tbl_os_unicoba.telefone_3,
					    tbl_os_unicoba.cep,
					    tbl_os_unicoba.endereco,
					    tbl_os_unicoba.complemento,
					    tbl_os_unicoba.bairro,
					    tbl_os_unicoba.cidade,
					    tbl_os_unicoba.uf,
					    tbl_os_unicoba.posto_autorizado,
					    tbl_os_unicoba.telefone_posto,
					    tbl_os_unicoba.ddd_dosto,
					    tbl_produto.referencia,
					    tbl_produto.descricao
					FROM tbl_os_unicoba 
					JOIN tbl_produto ON tbl_produto.referencia = tbl_os_unicoba.modelo AND tbl_produto.fabrica_i = $login_fabrica
					WHERE 1 = 1 $cond";
		$resSubmit = pg_query($con,$sql);

	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_historico-os-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='11' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE HISTÓRICO DE OS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nota</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data NF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Acessórios</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CPF/CNPJ</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone 1</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone 2</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone 3</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for($j = 0; $j < pg_num_rows($resSubmit); $j++){

				$os 				= pg_fetch_result($resSubmit,$j,'os');
				$data_abertura 		= pg_fetch_result($resSubmit,$j,'data_abertura');
				$nota 				= pg_fetch_result($resSubmit,$j,'nota');
				$serie 				= pg_fetch_result($resSubmit,$j,'serie');
				$data_compra 		= pg_fetch_result($resSubmit,$j,'data_compra');
				$defeito_reclamado 	= pg_fetch_result($resSubmit,$j,'defeito_reclamado');
				$acessorios 		= pg_fetch_result($resSubmit,$j,'acessorios');
				$cliente 			= pg_fetch_result($resSubmit,$j,'cliente');
				$cpf 				= pg_fetch_result($resSubmit,$j,'cpf');
				$ddd_1 				= pg_fetch_result($resSubmit,$j,'ddd_1');
				$telefone_1 		= pg_fetch_result($resSubmit,$j,'telefone_1');
				$ddd_2 				= pg_fetch_result($resSubmit,$j,'ddd_2');
				$telefone_2 		= pg_fetch_result($resSubmit,$j,'telefone_2');
				$ddd_3 				= pg_fetch_result($resSubmit,$j,'ddd_3');
				$telefone_3 		= pg_fetch_result($resSubmit,$j,'telefone_3');
				$cep 				= pg_fetch_result($resSubmit,$j,'cep');
				$endereco 			= pg_fetch_result($resSubmit,$j,'endereco');
				$complemento 		= pg_fetch_result($resSubmit,$j,'complemento');
				$bairro 			= pg_fetch_result($resSubmit,$j,'bairro');
				$cidade 			= pg_fetch_result($resSubmit,$j,'cidade');
				$uf 				= pg_fetch_result($resSubmit,$j,'uf');
				$posto_audorizado 	= pg_fetch_result($resSubmit,$j,'posto_autorizado');
				$telefone_posto 	= pg_fetch_result($resSubmit,$j,'telefone_posto');
				$ddd_posto 			= pg_fetch_result($resSubmit,$j,'ddd_dosto');
				$referencia_produto = pg_fetch_result($resSubmit,$j,'referencia');
				$descricao_produto 	= pg_fetch_result($resSubmit,$j,'descricao');

				$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$os}</td>
							<td nowrap align='center' valign='top'>{$data_abertura}</td>
							<td nowrap align='center' valign='top'>{$posto_audorizado}</td>
							<td nowrap align='center' valign='top'>{$ddd_posto} {$posto_autorizado}</td>
							<td nowrap align='center' valign='top'>{$referencia_produto} - {$descricao_produto}</td>
							<td nowrap align='center' valign='top'>{$serie}</td>
							<td nowrap align='left' valign='top'>{$nota}</td>
							<td nowrap align='center' valign='top'>{$data_compra}</td>
							<td nowrap align='center' valign='top'>{$defeito_reclamado}</td>
							<td nowrap align='center' valign='top'>{$acessorios}</td>
							<td nowrap align='center' valign='top'>{$cliente}</td>
							<td nowrap align='center' valign='top'>{$cpf}</td>
							<td nowrap align='center' valign='top'>{$ddd_1} {$telefone_1}</td>
							<td nowrap align='center' valign='top'>{$ddd_2} {$telefone_2}</td>
							<td nowrap align='center' valign='top'>{$ddd_3} {$telefone_3}</td>
							<td nowrap align='center' valign='top'>{$endereco}</td>
							<td nowrap align='center' valign='top'>{$cep}</td>
							<td nowrap align='center' valign='top'>{$bairro}</td>
							<td nowrap align='center' valign='top'>{$cidade}</td>
							<td nowrap align='center' valign='top'>{$uf}</td>
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
	"os" => array(
		"span"     => 8,
		"label"    => "Nº OS",
		"type"     => "input/text",
		"width"    => 5
	),
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
	"codigo_posto" => array(
		"span"      => 4,
		"label"     => "Código Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"		=> array(
				"name"   	=> "codigo_posto",
				"tipo"   	=> "posto",
				"parametro" => "codigo"
			)
	),
	"descricao_posto" => array(
		"span"      => 4,
		"label"     => "Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"		=> array(
				"name"   	=> "descricao_posto",
				"tipo"   	=> "posto",
				"parametro" => "nome"
			)
	),
    "produto_referencia"=> array(
        "span"=>4,
		"width" => 6,
        "type"=>"input/text",
        "label"=>"Referência Produto",
		"lupa"=>array("name"=>"produto_referencia", "tipo"=>"produto", "parametro"=>"referencia")
    ),
    "produto_descricao"=> array(
        "span"=>4,
        "width" => 12,
        "type"=>"input/text",
        "label"=>"Descrição Produto",
		"lupa"=>array("name"=>"produto_descricao", "tipo"=>"produto", "parametro"=>"descricao")
    ),
    "cliente" => array(
		"span"     => 4,
		"label"    => "Nome Cliente",
		"type"     => "input/text",
		"width"    => 12
	),
	"cpf" => array(
		"span"     => 4,
		"label"    => "CPF Cliente",
		"type"     => "input/text",
		"width"    => 12
	),
);



?>

<script src="js/novo_highcharts.js"></script>
<script src="js/modules/exporting.js"></script>

<script>
		$(function(){
		
			Shadowbox.init();
		
			$.datepickerLoad(Array("data_final", "data_inicial"));

			$('#data_inicial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");

			$("span[rel=descricao_posto]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=codigo_posto]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=produto_referencia]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=produto_descricao]").click(function () {
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
						<th>OS</th>
						<th>Posto</th>
						<th>Produto</th>
						<th>Data Abertura</th>
            		</tr>
                </thead>
				<tbody>
		<?php
				
				for($i = 0; $i < pg_num_rows($resSubmit); $i++){

					$os = pg_fetch_result($resSubmit, $i, 'os');
					$posto_autorizado = pg_fetch_result($resSubmit, $i, 'posto_autorizado');
					$modelo = pg_fetch_result($resSubmit, $i, 'referencia') ." - ".pg_fetch_result($resSubmit, $i, 'descricao');
					$data_abertura = pg_fetch_result($resSubmit, $i, 'data_abertura');

					echo "<tr>
							<td><button class='btn btn-link' onclick=\"window.open('os_historico_detalhada.php?os=$os')\">$os</button></td>
							<td nowrap>$posto_autorizado</td>
							<td>$modelo</td>
							<td class='tac'>$data_abertura</td>
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
