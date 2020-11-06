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
	$os 				= $_POST['os'];
	$codigo_posto       = $_POST["codigo_posto"];
	$descricao_posto    = $_POST["descricao_posto"];
	$produto_referencia	= $_POST["produto_referencia"];
	$produto_descricao	= $_POST["produto_descricao"];
	$cliente			= $_POST["cliente"];
	$cpf				= $_POST["cpf"];

	# Validações
	if ((!strlen($data_inicial) || !strlen($data_final)) && !strlen($hd_chamado)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "data";
	}

	
	if(!count($msg_erro["msg"])){

		if(strlen($hd_chamado) > 0){
			$cond = " AND tbl_mondial_hd_chamado.protocolo = '$hd_chamado' ";
		}

		if(strlen($os) > 0){
			$cond .= " AND tbl_mondial_hd_chamado.os = '{$os}' ";
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

			$cond .= " AND tbl_mondial_hd_chamado.data_atendimento BETWEEN '$data_inicial_formatada' and '$data_final_formatada' ";

		}

	}

	if(!empty($descricao_posto)){
		$cond .= " AND tbl_mondial_hd_chamado.posto ilike '$descricao_posto%' ";
	}

	if(!empty($produto_referencia)){
		$cond .= " AND tbl_mondial_hd_chamado.produto = '$produto_referencia' ";
	}

	if(!empty($cliente)){
		$cond .= " AND tbl_mondial_hd_chamado.consumidor ilike '$cliente%' ";
	}

	if(!empty($cpf)){
		$cpf = str_replace("-", "", $cpf);
		$cpf = str_replace(".", "", $cpf);
		$cpf = str_replace("/", "", $cpf);

		$cond .= " AND tbl_mondial_hd_chamado.cpf = '$cpf' ";
	}

	if(count($msg_erro['msg']) == 0){

		$sql = "SELECT  tbl_mondial_hd_chamado.protocolo,
					    to_char(tbl_mondial_hd_chamado.data_atendimento,'DD/MM/YYYY') AS data_abertura,
					    tbl_mondial_hd_chamado.historico,
					    tbl_mondial_hd_chamado.responsavel,
					    tbl_mondial_hd_chamado.fantasia,
					    tbl_mondial_hd_chamado.tipo_providencia,
					    tbl_mondial_hd_chamado.produto,
					    tbl_mondial_hd_chamado.posto,
					    tbl_mondial_hd_chamado.os,
					    tbl_mondial_hd_chamado.data_retorno,
					    tbl_mondial_hd_chamado.acao,
					    tbl_mondial_hd_chamado.data_encerramento,
					    tbl_mondial_hd_chamado.pais,
					    tbl_mondial_hd_chamado.cep,
					    tbl_mondial_hd_chamado.endereco,
					    tbl_mondial_hd_chamado.complemento,
					    tbl_mondial_hd_chamado.numero,
					    tbl_mondial_hd_chamado.bairro,
					    tbl_mondial_hd_chamado.uf,
					    tbl_mondial_hd_chamado.cidade,
					    tbl_mondial_hd_chamado.pais_nome,
					    tbl_mondial_hd_chamado.cpf,
					    tbl_mondial_hd_chamado.email,
					    tbl_mondial_hd_chamado.consumidor
					FROM tbl_mondial_hd_chamado 
					WHERE 1 = 1 $cond";
		$resSubmit = pg_query($con,$sql);

	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_historico-atendimentos-{$data}.xls";

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
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Encerramento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Providência</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Protocolo</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fantasia</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CPF/CNPJ</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pais</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>E-mail</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for($j = 0; $j < pg_num_rows($resSubmit); $j++){

				$protocolo 		= pg_fetch_result($resSubmit,$j,'protocolo');
				$data_abertura 		= pg_fetch_result($resSubmit,$j,'data_abertura');
				$historico 		= pg_fetch_result($resSubmit,$j,'historico');
				$responsavel 		= pg_fetch_result($resSubmit,$j,'responsavel');
				$fantasia 		= pg_fetch_result($resSubmit,$j,'fantasia');
				$tipo_providencia 	= pg_fetch_result($resSubmit,$j,'tipo_providencia');
				$produto 		= pg_fetch_result($resSubmit,$j,'produto');
				$posto 			= pg_fetch_result($resSubmit,$j,'posto');
				$os 			= pg_fetch_result($resSubmit,$j,'os');
				$data_retorno 		= pg_fetch_result($resSubmit,$j,'data_retorno');
				$acao 			= pg_fetch_result($resSubmit,$j,'acao');
				$data_encerramento 	= pg_fetch_result($resSubmit,$j,'data_encerramento');
				$pais 			= pg_fetch_result($resSubmit,$j,'pais');
				$cep 			= pg_fetch_result($resSubmit,$j,'cep');
				$endereco 		= pg_fetch_result($resSubmit,$j,'endereco');
				$complemento 		= pg_fetch_result($resSubmit,$j,'complemento');
				$numero 		= pg_fetch_result($resSubmit,$j,'numero');
				$bairro 		= pg_fetch_result($resSubmit,$j,'bairro');
				$uf 			= pg_fetch_result($resSubmit,$j,'uf');
				$cidade 		= pg_fetch_result($resSubmit,$j,'cidade');
				$pais_nome 		= pg_fetch_result($resSubmit,$j,'pais_nome');
				$cpf 			= pg_fetch_result($resSubmit,$j,'cpf');
				$email 			= pg_fetch_result($resSubmit,$j,'email');
				$consumidor 		= pg_fetch_result($resSubmit,$j,'consumidor');
				$acao_cod 		= pg_fetch_result($resSubmit,$j,'acao_cod');

				$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$responsavel}</td>
							<td nowrap align='center' valign='top'>{$data_abertura}</td>
							<td nowrap align='center' valign='top'>{$data_encerramento}</td>
							<td nowrap align='center' valign='top'>{$tipo_providencia}</td>
							<td nowrap align='center' valign='top'>{$produto}</td>
							<td nowrap align='center' valign='top'>{$protocolo}</td>
							<td nowrap align='center' valign='top'>{$consumidor}</td>
							<td nowrap align='center' valign='top'>{$fantasil}</td>
							<td nowrap align='center' valign='top'>{$cpf}</td>
							<td nowrap align='center' valign='top'>{$endereco}</td>
							<td nowrap align='center' valign='top'>{$numero}</td>
							<td nowrap align='center' valign='top'>{$complemento}</td>
							<td nowrap align='center' valign='top'>{$bairro}</td>
							<td nowrap align='center' valign='top'>{$cep}</td>
							<td nowrap align='center' valign='top'>{$cidade}</td>
							<td nowrap align='center' valign='top'>{$uf}</td>
							<td nowrap align='center' valign='top'>{$pais_nome}</td>
							<td nowrap align='center' valign='top'>{$email}</td>
							<td nowrap align='center' valign='top'>{$os}</td>
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
	"hd_chamado" => array(
		"span"     => 4,
		"label"    => "Nº Atendimento",
		"type"     => "input/text",
		"width"    => 5
	),
	"os" => array(
		"span"	   => 4,
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
						<th>Data Encerramento</th>
						<th>Providência</th>
						<th>Produto</th>
						<th>Nº Protocolo</th>
						<th>Cliente</th>
						<th>Cidade</th>
						<th>Estado</th>
						<th>Posto</th>
						<th>OS</th>
            		</tr>
                </thead>
				<tbody>
		<?php
				
				for($i = 0; $i < pg_num_rows($resSubmit); $i++){

					$protocolo 			= pg_fetch_result($resSubmit,$i,'protocolo');
					$data_abertura 		= pg_fetch_result($resSubmit,$i,'data_abertura');
					$responsavel 		= pg_fetch_result($resSubmit,$i,'responsavel');
					$tipo_providencia 	= pg_fetch_result($resSubmit,$i,'tipo_providencia');
					$produto 			= pg_fetch_result($resSubmit,$i,'produto');
					$posto 				= pg_fetch_result($resSubmit,$i,'posto');
					$os 				= pg_fetch_result($resSubmit,$i,'os');					
					$data_encerramento 	= pg_fetch_result($resSubmit,$i,'data_encerramento');					
					$uf 				= pg_fetch_result($resSubmit,$i,'uf');
					$cidade 			= pg_fetch_result($resSubmit,$i,'cidade');					
					$consumidor 		= pg_fetch_result($resSubmit,$i,'consumidor');
					$acao_cod 			= pg_fetch_result($resSubmit,$i,'acao_cod');

					echo "<tr>
							<td>$responsavel</td>							
							<td nowrap>$data_abertura</td>
							<td>$data_encerramento</td>
							<td class='tac'>$tipo_providencia</td>
							<td>$produto</td>	
							<td><button class='btn btn-link' onclick=\"window.open('atendimento_historico_detalhado.php?protocolo=$protocolo')\">$protocolo</button></td>
							<td>$consumidor</td>	
							<td>$cidade</td>	
							<td>$uf</td>	
							<td>$posto</td>	
							<td>$os</td>								
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
