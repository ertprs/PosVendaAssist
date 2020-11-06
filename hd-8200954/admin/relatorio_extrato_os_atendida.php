<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';
require_once '../helpdesk/mlg_funciones.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$tDocs = new TDocs($con,$login_fabrica,'avulso');

$layout_menu = "financeiro";
$title = "RELATÓRIO DE EXTRATOS AVULSOS LANÇADOS";

if(isset($_POST["btn_acao"])){	

	$data_inicial   = $_POST["data_inicial"];
	$data_final     = $_POST["data_final"];
	$codigo_posto   = $_POST["codigo_posto"];
	$nome_posto     = $_POST["nome_posto"];
	$numero_extrato = $_POST["extrato"];
	$os             = $_POST["os"];

	if($login_fabrica == 158){
		$sql_add_cidade = " tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome ";
		$join_cidade 	= " INNER JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio ";
	} else {
		$sql_add_cidade = " tbl_distribuidor_sla.unidade_negocio||' - '||tbl_cidade.nome ";
		$join_cidade 	= " INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_distribuidor_sla.cidade ";
	}


	$sql = "SELECT DISTINCT tbl_extrato.extrato,
			to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
			{$sql_add_cidade} AS unidade_negocio,
			tbl_os.sua_os,
			tbl_os.os,			
			tbl_tipo_atendimento.descricao AS tipo_atendimento,
			tbl_os.serie,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
			tbl_os.consumidor_nome,
			tbl_os.consumidor_cidade,
			tbl_os.consumidor_estado,
			tbl_produto.referencia,			
			tbl_produto.descricao,			
			coalesce(tbl_os.mao_de_obra,0) AS mo,
			coalesce(tbl_os.valores_adicionais,0) as valor_adicional,
			coalesce(tbl_os.qtde_km_calculada,0) as valor_km,
			(coalesce(tbl_os.mao_de_obra,0) + coalesce(tbl_os.valores_adicionais,0) + coalesce(tbl_os.qtde_km_calculada,0)) AS total
			FROM tbl_extrato
			INNER JOIN tbl_os_extra ON tbl_extrato.extrato = tbl_os_extra.extrato
			INNER JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = {$login_fabrica}
			INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
			INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			INNER JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			INNER JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato
			INNER JOIN tbl_distribuidor_sla ON tbl_extrato_agrupado.codigo=tbl_distribuidor_sla.unidade_negocio
			{$join_cidade}
			WHERE tbl_extrato.fabrica = {$login_fabrica}
			";

	if ((empty($numero_extrato) and empty($os))) {
		if ((!strlen($data_inicial) or !strlen($data_final))) {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";		
		} 
	}

	if ((!empty($numero_extrato) or !empty($os))) {	
		if(!empty($numero_extrato)) {
			$sql .= " AND tbl_extrato.extrato = {$extrato}";
		}

		if(!empty($os)) {
			$sql .= " AND (tbl_os.os = {$os} OR tbl_os.sua_os = '{$os}')";
		}

		$res_consulta = pg_query($con,$sql);
	}

	if(!empty($data_inicial) && !empty($data_final)){
		// TRANSFORMANDO DATA NO PADRAO AMERICANO
		$di_x = formata_data($data_inicial);
		$df_x = formata_data($data_final);

		$sql .= " AND tbl_extrato.data_geracao BETWEEN '{$di_x} 00:00:00' AND '{$df_x} 23:59:59'";

		if(!empty($codigo_posto) OR !empty($nome_posto)) {
			$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}'";			
			$res_posto = pg_query($con, $sql_posto);
			$posto = pg_fetch_result($res_posto, 0, "posto");			
			$sql .= " AND tbl_extrato.posto = {$posto}";			
		}

		if(!empty($numero_extrato)) {
			$sql .= " AND tbl_extrato.extrato = {$extrato}";
		}

		if(!empty($os)) {
			$sql .= " AND (tbl_os.os = {$os} OR tbl_os.sua_os = '{$os}')";
		}			
		//die(nl2br($sql));
		$res_consulta = pg_query($con,$sql);	
	}	
}
	
if ($_POST['gerar_excel']) {
	$count = pg_num_rows($res_consulta);
	$data = date("d-m-Y-H:i");
	$fileName = "relatorio_extrato_os_atendida-{$login_fabrica}-{$data}.csv";
	$file = fopen("/tmp/{$fileName}", "w");

	// CABECÇALHO DO ARQUIVO CSV
	$cabecalho_csv = "Data de Geração do Extrato;Extrato;Unidade de Negócio;OS;Tipo de OS;Código do Produto;Produto;Série;Código do Posto;Posto;Abertura;Fechamento;Consumidor;Cidade;Estado;Mão de Obra;Valor Adicional;Total KM;Total\n";
	
	fwrite($file, $cabecalho_csv);

	//CORPO DO ARQUIVO CSV
	for ($i = 0; $i < $count; $i++) {
		$data_geracao               = pg_fetch_result($res_consulta, $i, "data_geracao");
		$extrato                    = pg_fetch_result($res_consulta, $i, "extrato");
		$unidade_negocio            = pg_fetch_result($res_consulta, $i, "unidade_negocio");
		$os                         = pg_fetch_result($res_consulta, $i, "os");
		$tipo_os                    = pg_fetch_result($res_consulta, $i, "tipo_atendimento");
		$referencia                 = pg_fetch_result($res_consulta, $i, "referencia");
		$descricao                  = pg_fetch_result($res_consulta, $i, "descricao");
		$n_serie                    = pg_fetch_result($res_consulta, $i, "serie");	
		$codigo_posto               = pg_fetch_result($res_consulta, $i, "codigo_posto");
		$nome_posto                 = pg_fetch_result($res_consulta, $i, "nome");
		$data_abertura              = pg_fetch_result($res_consulta, $i, "data_abertura");
		$data_fechamento            = pg_fetch_result($res_consulta, $i, "data_fechamento");
		$consumidor_nome            = pg_fetch_result($res_consulta, $i, "consumidor_nome");
		$consumidor_cidade          = pg_fetch_result($res_consulta, $i, "consumidor_cidade");
		$consumidor_estado          = pg_fetch_result($res_consulta, $i, "consumidor_estado");
		$mo                         = pg_fetch_result($res_consulta, $i, "mo");
		$valor_adicional            = pg_fetch_result($res_consulta, $i, "valor_adicional");
		$valor_km                   = pg_fetch_result($res_consulta, $i, "valor_km");
		$valor_total                = pg_fetch_result($res_consulta, $i, "total");

		$mo_formatado				= number_format($mo, 2, ',', '.') ;
		$vadicional_formatado		= number_format($valor_adicional, 2, ',', '.');
		$vkm_formatado				= number_format($valor_km, 2, ',', '.');
		$vtotal_formatado			= number_format($valor_total, 2, ',', '.');

		$corpo_csv .= "{$data_geracao};{$extrato};{$unidade_negocio};{$os};{$tipo_os};{$referencia};{$descricao};{$n_serie};{$codigo_posto};{$nome_posto};{$data_abertura};{$data_fechamento};{$consumidor_nome};{$consumidor_cidade};{$consumidor_estado};{$mo_formatado};{$vadicional_formatado};{$vkm_formatado};{$vtotal_formatado}\n";	
	}	

	fwrite($file, $corpo_csv);	
	fclose($file);

	if (file_exists("/tmp/{$fileName}")) {
		system("mv /tmp/{$fileName} xls/{$fileName}");
		echo "xls/{$fileName}";
	}	
	exit;
}	

include "cabecalho_new.php";

$plugins = array( 
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include "plugin_loader.php";

if (count($msg_erro["msg"]) > 0) {
?>
    <div id="dados" class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>			
<form name="frm_relatorio" METHOD="POST" ACTION="<?= $PHP_SELF ?>" class='form-search form-inline'>
	<div class="tc_formulario">
		<div class='titulo_tabela'>Parâmetros de Pesquisa</div>
		<br />	
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<input class='span12' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<?= $data_inicial ?>" >
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
								<input class='span12' type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<?= $data_final ?>">
							</div>	
						</div>
					</div>	
				</div>		
				<div class='span2'></div>	
			</div>
			<div class='row-fluid'>	
				<div class='span2'></div>
					<div class='span4'>
						<div class='control-group'>	
							<div class='controls controls-row'>
								<label class='control-label' for='codigo_posto'>Código Posto</label>
								<div class='controls controls-row'>
									<div class="span7 input-append">
										<input class='span12' type="text" name="codigo_posto" id="codigo_posto" value="<?= $codigo_posto ?>" class="Caixa">
										<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
										<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
									</div>
								</div>		
							</div>	
						</div>
					</div>
					<div class='span4'>
						<div class='control-group'>
							<div class='controls controls-row'>
								<label class='control-label' for='nome_posto'>Nome do Posto</label>
								<div class='controls controls-row'>
									<div class='span12 input-append'>
										<input  class='span12' type="text" name="posto_nome" id="descricao_posto"  value="<?= $posto_nome?>" class="Caixa">
										<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
										<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
									</div>
								</div>	
							</div>
						</div>	
					</div>		
				<div class='span2'></div>			
			</div>		
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("extrato", $msg_erro2["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='extrato'>Número do Extrato</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
									<input type="text" name="extrato" size="8"  value="<? echo $extrato ?>" >
							</div>
						</div>
					</div>
				</div>					
				<div class='span4'>
					<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='os'>Número da OS</label>
						<div class='controls controls-row'>
							<div class='span7'>
								<input type="text" name="os" id="os" class='span12' value="<? echo $os ?>" >
							</div>
						</div>
					</div>			
				</div>
				<div class='span2'></div>
			</div>
			<p><br />
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p><br />
		</div>		
	</div>	
</form>

<?php

if(isset($_POST["btn_acao"])){
	if (isset($res_consulta)) {
		if (pg_num_rows($res_consulta) > 0) {
			if (pg_num_rows($res_consulta) > 500) {
				$count = 500;
	?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo CSV no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($res_consulta);				
			}
			?>
			<table style="max-width: 95%; table-layout: fixed" id="resultado" class='table table-bordered' >
				<thead>
					<tr class='titulo_coluna'>
						<th>Data de Geração do Extrato</th>	
						<th>Extrato</th>	
						<th width="200px">Unidade de Negócio</th>
						<th>OS</th>
						<th>Tipo de OS</th>											
						<th>Código do Produto</th>
						<th>Produto</th>
						<th>Série</th>
						<th>Código do Posto</th>
						<th>Posto</th>
						<th>Abertura</th>
						<th>Fechamento</th>
						<th>Consumidor</th>
						<th>Cidade</th>
						<th>Estado</th>
						<th>M.O.</th>
						<th>Valor Adicional</th>
						<th>Total KM</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$data_geracao               = pg_fetch_result($res_consulta, $i, "data_geracao");
						$extrato                    = pg_fetch_result($res_consulta, $i, "extrato");
						$unidade_negocio            = pg_fetch_result($res_consulta, $i, "unidade_negocio");
						$os                         = pg_fetch_result($res_consulta, $i, "os");
						$tipo_os                    = pg_fetch_result($res_consulta, $i, "tipo_atendimento");
						$referencia                 = pg_fetch_result($res_consulta, $i, "referencia");	
						$descricao                  = pg_fetch_result($res_consulta, $i, "descricao");	
						$n_serie                    = pg_fetch_result($res_consulta, $i, "serie");	
						$codigo_posto               = pg_fetch_result($res_consulta, $i, "codigo_posto");
						$nome_posto                 = pg_fetch_result($res_consulta, $i, "nome");
						$data_abertura              = pg_fetch_result($res_consulta, $i, "data_abertura");
						$data_fechamento            = pg_fetch_result($res_consulta, $i, "data_fechamento");
						$consumidor_nome            = pg_fetch_result($res_consulta, $i, "consumidor_nome");
						$consumidor_cidade          = pg_fetch_result($res_consulta, $i, "consumidor_cidade");
						$consumidor_estado          = pg_fetch_result($res_consulta, $i, "consumidor_estado");
						$mo                         = pg_fetch_result($res_consulta, $i, "mo");
						$valor_adicional            = pg_fetch_result($res_consulta, $i, "valor_adicional");
						$valor_km                   = pg_fetch_result($res_consulta, $i, "valor_km");
						$valor_total                = pg_fetch_result($res_consulta, $i, "total");	

						$body = "<tr>
									<td valign='top'>{$data_geracao}</td>
									<td valign='top'>{$extrato}</td>
									<td valign='top'>{$unidade_negocio}</td>
									<td valign='top'>{$os}</td>
									<td valign='top'>{$tipo_os}</td>		
									<td valign='top'>{$referencia}</td>
									<td valign='top'>{$descricao}</td>
									<td valign='top'>{$n_serie}</td>
									<td valign='top'>{$codigo_posto}</td>
									<td valign='top'>{$nome_posto}</td>
									<td valign='top'>{$data_abertura}</td>
									<td valign='top'>{$data_fechamento}</td>
									<td valign='top'>{$consumidor_nome}</td>
									<td valign='top'>{$consumidor_cidade}</td>
									<td valign='top'>{$consumidor_estado}</td>	
									<td valign='top'>R$ " . number_format($mo, 2, ',', '.') . "</td>
									<td valign='top'>R$ " . number_format($valor_adicional, 2, ',', '.') . "</td>
									<td valign='top'>R$ " . number_format($valor_km, 2, ',', '.') . "</td>
									<td valign='top'>R$ " . number_format($valor_total, 2, ',', '.') . "</td>
								</tr>
							";

						echo $body;
						$somar_mo				+= $mo;
						$somar_valor_adicional	+= $valor_adicional;
						$somar_km				+= $valor_km;
						$somar_total			+= $valor_total; 
					} 
					echo "<tr class='titulo_coluna'>
							<td valign='top' colspan='14'>Soma dos Totais</td>
							<td valign='top'>R$ " . number_format($somar_mo, 2, ',', '.') . "</td>
							<td valign='top'>R$ " . number_format($somar_valor_adicional, 2, ',', '.') . "</td>
							<td valign='top'>R$ " . number_format($somar_km, 2, ',', '.') . "</td>
							<td valign='top'>R$ " . number_format($somar_total, 2, ',', '.') . "</td>
						</tr>";							
		
		$jsonPOST = excelPostToJson($_REQUEST);
		$jsonPOST = utf8_decode($jsonPOST);	
	?>
	</tbody></table><br />			
	<div id='gerar_excel' class='btn_excel'>
		<input type='hidden' id='jsonPOST' value='<?=$jsonPOST?>' />
		<span><img src='imagens/icon_csv.png' /></span>
		<span class='txt'>Gerar Arquivo CSV</span>
	</div><br />
	<?php }	?>	
	
<?php
}
}
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		 $("#data_inicial").datepicker().mask("99/99/9999");
		 $("#data_final").datepicker().mask("99/99/9999");

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
	include "rodape.php";
?>
