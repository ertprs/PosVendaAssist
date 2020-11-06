<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

function converterParaDias($minutos){

	$d = floor ($minutos / 1440);
	$h = floor (($minutos - $d * 1440) / 60);
	$m = $minutos - ($d * 1440) - ($h * 60);

	$d = str_pad($d, 2 , '0' , STR_PAD_LEFT);
	$h = str_pad($h, 2 , '0' , STR_PAD_LEFT);
	$m = str_pad($m, 2 , '0' , STR_PAD_LEFT);

	return "{$d} dias";

}

if (isset($_POST['pesquisar'])) {

	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$qtde_postos   = $_POST['qtde_postos'];
	$estados       = $_POST['estados'];
	$os            = $_POST['os'];

	if (empty($os)) {

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

	} else {
		$condOs = "AND tbl_os.os = {$os}";
	}

	if (count($estados) > 0) {
		$estados_pesquisa = implode("','", $estados);
		$cond_estado      = "AND tbl_posto.estado IN ('{$estados_pesquisa}')";
	}

	if (!empty($qtde_postos)) {

		$limitOfensores = "LIMIT {$qtde_postos}";

	} else {

		$msg_erro['msg'][] 	  = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "qtde_postos";

	}

	if (isset($_POST['desconsiderar_ajuste'])) {
		$condDesconsideraAjuste = "
			AND (
				SELECT COUNT(*)
				FROM tbl_os_item
				JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				AND  tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				AND  (tbl_servico_realizado.troca_de_peca IS TRUE OR tbl_servico_realizado.troca_produto IS TRUE)
				WHERE fabrica_i = {$login_fabrica}
			) > 0
		";
	}



	$cond_periodo    			= "AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' and '{$aux_data_final}'";
	$cond_periodo_fechamento	= "AND tbl_os.finalizada BETWEEN '{$aux_data_inicial}' and '{$aux_data_final}'";

	if (count($msg_erro) == 0 && empty($os)) {

		$sqlDetalhado = "
			WITH dados_os AS (

				SELECT tbl_os.os,
					   tbl_os.sua_os,
					   tbl_os.data_abertura,
					   tbl_os.data_digitacao,
					   tbl_os.data_conserto,
					   tbl_os.finalizada,
					   tbl_posto_fabrica.codigo_posto,
					   tbl_posto.nome as nome_posto,
					   tbl_os.fabrica
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.finalizada IS NOT NULL
				{$condDesconsideraAjuste}
				{$cond_periodo_fechamento}
				{$cond_estado}

			), dados_os_item AS (
				
				SELECT dados_os.os,
					   MAX(tbl_os_item.digitacao_item) as ultima_digitacao_item,
					   tbl_os_item.admin
				FROM tbl_os_produto
				JOIN dados_os ON dados_os.os = tbl_os_produto.os
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				AND tbl_os_item.fabrica_i = dados_os.fabrica
				JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				AND tbl_servico_realizado.troca_produto IS NOT TRUE
				GROUP BY dados_os.os,
						 tbl_os_item.admin

			), dados_auditoria_os AS (
				
				SELECT dados_os.os,
					   MAX(liberada) as ultima_liberada
				FROM tbl_auditoria_os
				JOIN dados_os ON dados_os.os = tbl_auditoria_os.os
				WHERE observacao NOT ILIKE '%Troca de Produto%'
				GROUP BY dados_os.os

			), dados_embarque AS (
				
				SELECT dados_os.os,
					   MAX(tbl_embarque_item.embarcado) as ultima_data_embarque,
					   MAX(tbl_embarque_item.liberado)  as ultima_data_liberado
				FROM tbl_embarque_item
				JOIN tbl_os_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
				JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN dados_os ON tbl_os_produto.os = dados_os.os
				JOIN tbl_embarque ON tbl_embarque.embarque = tbl_embarque_item.embarque
				GROUP BY dados_os.os

			), dados_faturamento AS (
				
				SELECT dados_os.os,
					   MAX(tbl_faturamento.emissao) as ultima_data_emissao
				FROM tbl_faturamento_item
				JOIN dados_os ON tbl_faturamento_item.os = dados_os.os
				JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_faturamento_item.nota_fiscal_origem IS NULL
				GROUP BY dados_os.os

			), dados_faturamento_correio AS (

				SELECT dados_os.os,
					   MAX(postado.data) as data_postado,
					   MAX(entregue.data) as data_entregue
				FROM tbl_faturamento_item
				JOIN dados_os ON tbl_faturamento_item.os = dados_os.os
				JOIN tbl_faturamento_correio postado ON postado.faturamento = tbl_faturamento_item.faturamento
				AND postado.situacao ILIKE 'Objeto postado%'
				JOIN tbl_faturamento_correio entregue ON entregue.faturamento = tbl_faturamento_item.faturamento
				AND entregue.situacao ILIKE 'Objeto entregue%'
				GROUP BY dados_os.os

			), dados_os_historico_checkpoint AS (

				SELECT dados.os,
					   dados.status_checkpoint,
					   dados.ultima_data_input
				FROM (
					SELECT dados_os.os,
						   tbl_os_historico_checkpoint.status_checkpoint,
						   CASE WHEN tbl_os_historico_checkpoint.status_checkpoint = 37
						   		THEN MIN(tbl_os_historico_checkpoint.data_input)
						   		ELSE MAX(tbl_os_historico_checkpoint.data_input)
						   	END as ultima_data_input
					FROM tbl_os_historico_checkpoint
					JOIN dados_os ON dados_os.os = tbl_os_historico_checkpoint.os
					WHERE tbl_os_historico_checkpoint.tg_grava != 'rotina-BI'
					GROUP BY dados_os.os,
							 tbl_os_historico_checkpoint.status_checkpoint
				) dados

			)
			SELECT dados_os.os,
				   dados_os.sua_os,
				   dados_os.codigo_posto,
				   dados_os.nome_posto,
				   TO_CHAR(dados_os.data_abertura, 'dd/mm/yyyy') 						 as data_abertura,
				   TO_CHAR(dados_os.data_digitacao, 'dd/mm/yyyy HH24:MI') 				 as data_digitacao,
				   TO_CHAR(dados_os.data_conserto, 'dd/mm/yyyy HH24:MI') 				 as data_conserto,
				   TO_CHAR(dados_os.finalizada, 'dd/mm/yyyy HH24:MI') 					 as finalizada,
				   TO_CHAR(dados_os.finalizada, 'mm/yyyy') 								 as mes_ano_finalizada,
				   TO_CHAR(COALESCE(dados_os_item.ultima_digitacao_item, dados_os_item_adm.ultima_digitacao_item), 'dd/mm/yyyy HH24:MI') 	 as data_digitacao_item,
				   TO_CHAR(COALESCE(dados_auditoria_os.ultima_liberada, auditoria.ultima_data_input), 'dd/mm/yyyy HH24:MI') as data_liberada,
				   TO_CHAR(abastecimento.ultima_data_input, 'dd/mm/yyyy HH24:MI') 		  as data_ag_abastecimento,
				   TO_CHAR(dados_embarque.ultima_data_embarque, 'dd/mm/yyyy HH24:MI') 	  as data_embarque,
				   TO_CHAR(dados_embarque.ultima_data_liberado, 'dd/mm/yyyy HH24:MI')     as data_embarque_liberado, 
				   TO_CHAR(dados_faturamento.ultima_data_emissao, 'dd/mm/yyyy HH24:MI')   as data_emissao,
				   TO_CHAR(dados_faturamento_correio.data_postado, 'dd/mm/yyyy HH24:MI')  as data_postado,
				   TO_CHAR(dados_faturamento_correio.data_entregue, 'dd/mm/yyyy HH24:MI') as data_entregue,
				   TO_CHAR(
				   			(
								CASE 
									WHEN dados_faturamento_correio.data_entregue::date IS NULL 
									THEN dados_os.data_conserto::date
									ELSE dados_faturamento_correio.data_entregue::date
								END
							), 'dd/mm/yyyy HH24:MI') as ultimo_ag_conserto,
				   TO_CHAR(retirada.ultima_data_input, 'dd/mm/yyyy HH24:MI') 			  as ultimo_ag_retirada,
				   (dados_embarque.ultima_data_liberado::date - dados_embarque.ultima_data_embarque::date)                as dias_embarque_liberado,
				   (dados_faturamento.ultima_data_emissao::date - dados_embarque.ultima_data_liberado::date) 	    as dias_embarque_nf,
				   (dados_faturamento_correio.data_entregue::date - dados_faturamento_correio.data_postado::date)   as dias_em_transito,
				   greatest(0, 
				   		(dados_os.data_conserto::date - (
				   											CASE 
				   												WHEN dados_faturamento_correio.data_entregue::date IS NULL 
				   												THEN dados_os.data_conserto::date
				   												ELSE dados_faturamento_correio.data_entregue::date
				   											END
				   										))
				   ) as dias_em_conserto,
				   (dados_os.finalizada::date - retirada.ultima_data_input::date) 							 	    as dias_em_retirada,
				   (COALESCE(dados_os_item.ultima_digitacao_item::date, dados_os_item_adm.ultima_digitacao_item::date) - dados_os.data_abertura::date)	 		  	 	  as dias_abertura_diagnostico,
				   greatest(0, 
				    	(COALESCE(dados_auditoria_os.ultima_liberada, auditoria.ultima_data_input)::date - COALESCE(dados_os_item.ultima_digitacao_item::date, dados_os_item_adm.ultima_digitacao_item::date))
				   ) as dias_diagnostico_auditoria,
				   greatest(0 , 
				    	(dados_embarque.ultima_data_embarque::date - abastecimento.ultima_data_input::date)
				   ) as dias_auditoria_abastecimento
			FROM dados_os

			LEFT JOIN dados_os_item dados_os_item_adm  ON dados_os_item_adm.os      = dados_os.os
			AND dados_os_item_adm.admin IS NOT NULL

			LEFT JOIN dados_os_item  ON dados_os_item.os      = dados_os.os
			AND dados_os_item.admin IS NULL

			LEFT JOIN dados_auditoria_os 		ON dados_auditoria_os.os = dados_os.os
			LEFT JOIN dados_embarque 	    	ON dados_embarque.os     = dados_os.os
			LEFT JOIN dados_faturamento 		ON dados_faturamento.os  = dados_os.os
			LEFT JOIN dados_faturamento_correio ON dados_faturamento_correio.os  = dados_os.os
			LEFT JOIN dados_os_historico_checkpoint abastecimento ON abastecimento.os = dados_os.os
			AND abastecimento.status_checkpoint = 35
			LEFT JOIN dados_os_historico_checkpoint conserto 	  ON conserto.os = dados_os.os
			AND conserto.status_checkpoint = 3
			LEFT JOIN dados_os_historico_checkpoint retirada 	  ON retirada.os = dados_os.os
			AND retirada.status_checkpoint = 4
			LEFT JOIN dados_os_historico_checkpoint auditoria 	  ON auditoria.os = dados_os.os
			AND auditoria.status_checkpoint = 37
		";
		
		$resDetalhado = pg_query($con, $sqlDetalhado);

		if (!isset($_POST["gerar_excel"])) {

			$sqlPostosOfensores = "

				WITH dados as (
					SELECT os, 
						   data_abertura, 
						   data_fechamento,
						   data_fechamento - data_abertura as dias,
						   extract(month from data_fechamento) as mes,
						   extract(year from data_fechamento) as ano,
						   tbl_posto.nome as nome_posto,
						   tbl_posto_fabrica.codigo_posto as codigo_posto
					FROM tbl_os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					AND  tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					{$cond_periodo_fechamento}
					{$cond_estado}
					{$condDesconsideraAjuste}
				), dadosFaturamentoCorreio as (
									
					SELECT DISTINCT ON (tbl_faturamento.faturamento)
								   tbl_faturamento_item.os,
								   faturamento_objeto_postado.data as data_postado,
								   faturamento_objeto_entregue.data as data_entregue,
								   tbl_embarque.data AS data_embarque
					FROM tbl_faturamento_item
					JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
					JOIN tbl_embarque ON tbl_faturamento.embarque = tbl_embarque.embarque
					JOIN tbl_faturamento_correio faturamento_objeto_postado ON faturamento_objeto_postado.faturamento = tbl_faturamento_item.faturamento
					AND faturamento_objeto_postado.situacao ILIKE 'Objeto postado%'
					JOIN tbl_faturamento_correio faturamento_objeto_entregue ON faturamento_objeto_entregue.faturamento = tbl_faturamento_item.faturamento
					AND faturamento_objeto_entregue.situacao ILIKE 'Objeto entregue%'
					JOIN dados ON tbl_faturamento_item.os = dados.os

				), datasLiberacaoAuditoria as (

					  SELECT tbl_os_historico_checkpoint.os,
					         tbl_os_historico_checkpoint.data_input,
					         tbl_os_historico_checkpoint.status_checkpoint,

					         -- buscar o próximo status checkpoint (data de transicao entre os status) --
					         row_number() over ( order by tbl_os_historico_checkpoint.os, tbl_os_historico_checkpoint.os_historico_checkpoint )

					  FROM tbl_os_historico_checkpoint
					  JOIN dados ON tbl_os_historico_checkpoint.os = dados.os
					  WHERE tg_grava != 'rotina-BI'

				), dados_permanencia AS (
					SELECT DISTINCT ON (resultado_permanencia.os)
						   resultado_permanencia.os,
						   resultado_permanencia.dias_lancamento_pecas,
						   SUM(resultado_permanencia.dias_em_conserto)  			as total_dias_em_conserto,
						   resultado_permanencia.nome_posto,
						   resultado_permanencia.codigo_posto
					FROM (
							SELECT  tbl_os.os,
							   		(
										SELECT digitacao_item
										FROM tbl_os_item
										JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
										AND tbl_os_produto.os = tbl_os.os
										JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
										AND tbl_servico_realizado.troca_produto IS NOT TRUE
										WHERE tbl_os_item.fabrica_i = {$login_fabrica}
										ORDER BY tbl_os_item.digitacao_item ASC
										LIMIT 1
									) - data_digitacao AS dias_lancamento_pecas,
									tbl_os.finalizada - COALESCE(dados_faturamento.data_entregue, (SELECT datasLiberacaoAuditoria.data_input
																								   FROM datasLiberacaoAuditoria
																								   WHERE datasLiberacaoAuditoria.status_checkpoint = 3
																								   AND  datasLiberacaoAuditoria.os = tbl_os.os
																								   ORDER BY datasLiberacaoAuditoria.data_input DESC
																								   LIMIT 1)) 				AS dias_em_conserto,
									dados.nome_posto,
									dados.codigo_posto
							FROM tbl_os
							JOIN dados ON tbl_os.os = dados.os

							LEFT JOIN dadosFaturamentoCorreio dados_faturamento ON tbl_os.os = dados_faturamento.os

					) resultado_permanencia
					GROUP BY resultado_permanencia.os, resultado_permanencia.dias_lancamento_pecas, resultado_permanencia.codigo_posto, resultado_permanencia.nome_posto
				)
				SELECT dados.codigo_posto,
					   dados.nome_posto,
					   COUNT(*) as quantidade,
					   sum(dias) / count(*) as media,
					   EXTRACT(epoch FROM(sum(dados_permanencia.dias_lancamento_pecas) + sum(dados_permanencia.total_dias_em_conserto)) / COUNT(*)) / 60 as tempo_permanencia_posto
				FROM dados
				JOIN dados_permanencia ON dados_permanencia.os = dados.os 
				AND dados_permanencia.codigo_posto = dados.codigo_posto
				AND dados_permanencia.nome_posto = dados.nome_posto
				WHERE dias > 0
				GROUP BY dados.codigo_posto, dados.nome_posto
				ORDER BY quantidade DESC
				{$limitOfensores}

			";
			$resPostosOfensores = pg_query($con, $sqlPostosOfensores);

			$sqlEstados = "

				WITH dados as (
					SELECT os, 
						   data_abertura, 
						   data_fechamento,
						   data_fechamento - data_abertura as dias,
						   extract(month from data_fechamento) as mes,
						   extract(year from data_fechamento) as ano,
						   COALESCE(tbl_posto.estado, tbl_posto_fabrica.contato_estado) as estado
					FROM tbl_os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					AND  tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					{$cond_periodo_fechamento}
					{$cond_estado}
					{$condDesconsideraAjuste}
				)
				SELECT dados.estado,
					   tbl_estado.nome as nome_estado,
					   COUNT(*) as quantidade,
					   sum(dias) / count(*) as media
				FROM dados
				JOIN tbl_estado ON tbl_estado.estado = dados.estado
				WHERE dias > 0
				GROUP BY dados.estado, nome_estado
				ORDER BY quantidade DESC
				
			";
			$resEstados = pg_query($con, $sqlEstados);

		} else {

			if (pg_num_rows($resDetalhado) > 0) {

				$data = date("d-m-Y-H:i");

				$fileName = "tempo_permanencia_detalhado-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				$head = "<table border='1'>
							<thead>
								<tr>
									<th color='white' style='color: white !important;'>OS</th>
									<th  color='white' style='color: white !important;'>Cód. Posto</th>
									<th  color='white' style='color: white !important;'>Nome do Posto</th>
									<th  color='white' style='color: white !important;'>Data de Abertura</th>
									<th  color='white' style='color: white !important;'>Data de Digitação</th>
									<th  color='white' style='color: white !important;'>Data Carga de Diagnóstico</th>
									<th  color='white' style='color: white !important;'>AB >> Diagnóstico</th>
									<th  color='white' style='color: white !important;'>Liberação da Auditoria</th>
									<th  color='white' style='color: white !important;'>Diagnóstico >> Auditoria</th>
									<th  color='white' style='color: white !important;'>Aguardando Abastecimento de Estoque</th>
									<th  color='white' style='color: white !important;'>Auditoria >> Abastecimento de Estoque</th>
									<th  color='white' style='color: white !important;'>Data do Embarque</th>
									<th  color='white' style='color: white !important;'>Data Liberação do Embarque</th>
									<th  color='white' style='color: white !important;'>Embarque >> Liberação</th>
									<th  color='white' style='color: white !important;'>Data da Nota Fiscal</th>
									<th  color='white' style='color: white !important;'>Liberação Embarque >> Nota Fiscal</th>
									<th  color='white' style='color: white !important;'>Data em Trânsito</th>
									<th  color='white' style='color: white !important;'>Data da Entrega</th>
									<th  color='white' style='color: white !important;'>Tempo em Trânsito</th>
									<th  color='white' style='color: white !important;'>Data Aguardando Conserto</th>
									<th  color='white' style='color: white !important;'>Data do Conserto</th>
									<th  color='white' style='color: white !important;'>Aguardando Conserto >> Conserto</th>
									<th  color='white' style='color: white !important;'>Data Aguardando Entrega</th>
									<th  color='white' style='color: white !important;'>Aguardando Entrega >> Finalização</th>
						  			<th  color='white' style='color: white !important;'>Data de Finalização</th>
						  		</tr>
							</thead>
							<tbody>";

				$posicao = 1;
				while ($dadosDetalhado = pg_fetch_object($resDetalhado)) {

					
					$trOs .= "<tr>
								<td>{$dadosDetalhado->sua_os}</td>
								<td>{$dadosDetalhado->codigo_posto}</td>
								<td>{$dadosDetalhado->nome_posto}</td>
								<td>{$dadosDetalhado->data_abertura}</td>
								<td>{$dadosDetalhado->data_digitacao}</td>
								<td>{$dadosDetalhado->data_digitacao_item}</td>
								<td>{$dadosDetalhado->dias_abertura_diagnostico}</td>
								<td>{$dadosDetalhado->data_liberada}</td>
								<td>{$dadosDetalhado->dias_diagnostico_auditoria}</td>
								<td>{$dadosDetalhado->data_ag_abastecimento}</td>
								<td>{$dadosDetalhado->dias_auditoria_abastecimento}</td>
								<td>{$dadosDetalhado->data_embarque}</td>
								<td>{$dadosDetalhado->data_embarque_liberado}</td>
								<td>{$dadosDetalhado->dias_embarque_liberado}</td>
								<td>{$dadosDetalhado->data_emissao}</td>
								<td>{$dadosDetalhado->dias_embarque_nf}</td>
								<td>{$dadosDetalhado->data_postado}</td>
								<td>{$dadosDetalhado->data_entregue}</td>
								<td>{$dadosDetalhado->dias_em_transito}</td>
								<td>{$dadosDetalhado->ultimo_ag_conserto}</td>
								<td>{$dadosDetalhado->data_conserto}</td>
								<td>{$dadosDetalhado->dias_em_conserto}</td>
								<td>{$dadosDetalhado->ultimo_ag_retirada}</td>
								<td>{$dadosDetalhado->dias_em_retirada}</td>
								<td>{$dadosDetalhado->finalizada}</td>
							  </tr>";

				}


				fwrite($file, $head);
				fwrite($file, $trOs);
				fwrite($file, "</tbody>
							</table>");

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}

			}

			exit;

		}

	} else {

		$sqlOs = "SELECT os, 
					   TO_CHAR(data_abertura, 'dd/mm/yyyy') as data_abertura,
					   TO_CHAR(data_fechamento, 'dd/mm/yyyy') as data_fechamento,
					   tbl_posto.nome as nome_posto,
					   tbl_posto_fabrica.codigo_posto as codigo_posto,
					   COALESCE(data_fechamento, CURRENT_DATE) - data_abertura as dias
				  FROM tbl_os
				  JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			      AND  tbl_posto_fabrica.fabrica = {$login_fabrica}
				  WHERE tbl_os.fabrica = {$login_fabrica}
				  {$condOs}
				  ";
		$resOs = pg_query($con, $sqlOs);

	}
}

$layout_menu = "gerencia";
$title = "Tempo de Permanência";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

$array_estados = $array_estados();

include("plugin_loader.php");
?>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<style>
	.tipos-header {
		background-color: #53a3b9;
		color: white;
		height: 40px;
		font-family: sans-serif;
		margin-bottom: 15px;
		font-size: 17px;
		border-radius: 7px;
		text-align: center;
		padding-top: 14px;
		display: block;
		cursor: pointer;
	}

	.tipos-header:hover {
		background-color: #297083;
		transition: 0.25s ease-in;
	}

</style>
<script>
	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$(".tipos-header").click(function(){
			$(this).next("div").slideToggle();
		});

		$("#estados").multiselect({
            selectedText: "selecionados # de #"
        });

        $(".ui-multiselect-all").hide();

        $("#visao_geral").click(function(){
        	$(this).prop("disabled", true);
        	$("#postos_ofensores, #por_uf").prop("disabled", false);
        	$("#tabela_geral, .gerar_excel_os, #gerar_excel").show("slow");
        	$("#tabela_ofensores, .gerar_excel_posto, #tabela_estados, .gerar_excel_estado").hide("slow");
        });

        $("#postos_ofensores").click(function(){
        	$(this).prop("disabled", true);
        	$("#visao_geral, #por_uf").prop("disabled", false);
        	$("#tabela_geral, #tabela_estados, .gerar_excel_os, .gerar_excel_estado, #gerar_excel").hide("slow");
        	$("#tabela_ofensores, .gerar_excel_posto").show("slow");
        });

        $("#por_uf").click(function(){
        	$(this).prop("disabled", true);
        	$("#postos_ofensores, #visao_geral").prop("disabled", false);
        	$("#tabela_geral, #tabela_ofensores, .gerar_excel_os, .gerar_excel_posto, #gerar_excel").hide("slow");
        	$("#tabela_estados, .gerar_excel_estado").show("slow");
        });

	});
	
</script>
<style>
	.shadowboxOpen:hover, .shadowboxOpenPosto:hover  {
		cursor: pointer;
		background-color: darkblue !important;
		color: white;
		font-weight: bolder;
	}

	tfoot td:first-of-type {
		background-color: #596D9B !important;
		color: white;
	}
	tfoot td {
		font-weight: bolder;
		font-size: 15px;
		color: darkgreen;
	}
</style>
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
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os'>OS</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="text" name="os" id="os" size="12" maxlength="10" class='span12' value= "<?= $os ?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
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
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="<?=(in_array("qtde_postos", $msg_erro["campos"])) ? "error" : ""?>">
				<label class='control-label' for='data_final'>Postos Ofensores (Padrão: 15)</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="number" class="span4" name="qtde_postos" value="<?= empty($qtde_postos) ? 15 : $qtde_postos ?>" />
				</div>
			</div>
		</div>
		<div class="span4">
			<label class='control-label' for='data_final'>Estados</label>
			<div class='controls controls-row'>
				<select id="estados" name="estados[]" class="span12" multiple="multiple">
                    <?php
                    #O $array_estados está no arquivo funcoes.php
                    foreach ($array_estados as $sigla => $nome_estado) {
                        $selected = (in_array($sigla, $estados)) ? "selected" : "";

                        echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                    }
                    ?>
                </select>
			</div>
		</div>
	</div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="<?=(in_array("considerar_ajuste", $msg_erro["campos"])) ? "error" : ""?>">
				<label class='control-label'>
					<input type="checkbox" name="desconsiderar_ajuste" value="t" <?= (!isset($_POST['pesquisar']) || (isset($_POST['pesquisar']) && isset($_POST['desconsiderar_ajuste']))) ? "checked" : "" ?> />
					Desconsiderar OSs de ajuste
				</label>
			</div>
		</div>
	</div>
	<p>
		<button class='btn' id="btn_acao" name="pesquisar">Pesquisar</button>
	</p><br/>
</form>

<input type="hidden" id="aux_data_inicial" value="<?= $aux_data_inicial ?>" />
<input type="hidden" id="aux_data_final"   value="<?= $aux_data_final ?>" />
<input type="hidden" id="aux_tipo_pesquisa"    value="<?= $tipo_pesquisa ?>" />


<?php
if (isset($_POST['pesquisar']) && empty($os)) {
	
    $arquivoOs 	    = "xls/relatorio-ofensores-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";
    $arquivoOsPosto = "xls/relatorio-ofensores-postos-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";
    $arquivoEstado  = "xls/relatorio-ofensores-estados-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";

	?>
	<button class="btn btn-large btn-info" id="visao_geral" disabled="" style="float: left;width: 190px;">Visão geral</button>
	<button class="btn btn-large btn-info" id="por_uf" style="margin-left: 140px;width: 190px;">Por UF(s)</button>
	<button class="btn btn-large btn-info" id="postos_ofensores" style="float: right;width: 190px;">Postos Ofensores</button>
	
	<br /><br /><br />
	<br />

	<?php
		$jsonPOST = excelPostToJson($_POST);
	?>
	<div id='gerar_excel' class="btn_excel" style="width: 250px;float: left;margin-left: 50px;">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Detalhado</span>
	</div>
	<div class='gerar_excel_os btn_excel' onclick="window.open('<?= $arquivoOs ?>')" style="float: right;margin-right: 50px;">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Geral</span>
	</div>
	<br />
	<div class='gerar_excel_posto btn_excel' onclick="window.open('<?= $arquivoOsPosto ?>')" style="width: 210px;display: none;">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Ofensores</span>
	</div>

	<div class='gerar_excel_estado btn_excel' onclick="window.open('<?= $arquivoEstado ?>')" style="width: 210px;display: none;">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Estados</span>
	</div>
<br /><br />
	<div id="tabela_geral">
		<div class="alert alert-info" style="text-align: left;">
			<strong>Permanência Assistência:</strong> 
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Abertura da OS até Lançamento de peças + Data entrega correios até data de finalização da OS<br />
			<strong>Permanência Telecontrol:</strong>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo até aprovação da auditoria + data chegada das peças no estoque até postagem do objeto nos correios<br />
			<strong>Permanência Fábrica:</strong>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempo em que a OS ficou com o status Aguardando abastecimento de estoque<br />
			<strong>Permanência Transportadora:</strong> 
			Data da postagem até a data da entrega<br />
		</div>
		<div id="permanencia" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
		<?php
		ob_start();
		?>
		<table class="table table-bordered table-fixed">
			<thead>
				<tr class="titulo_tabela">
					<th colspan="7">OSs no período de <?= $data_inicial ?> - <?= $data_final ?></th>
				</tr>
				<tr class="titulo_coluna">
					<th>Mês/Ano</th>
					<th>Qtde Fechadas no mês</th>
					<th>Média Dias em Aberto</th>
					<th>Média Tempo Permanência Assistência</th>
					<th>Média Tempo Permanência Telecontrol</th>
					<th>Média Tempo Permanência Fábrica</th>
					<th>Média Tempo Permanência Transportadora</th>
				</tr>
			</thead>
			<tbody>
				<?php

				$mesesGrafico  				 	= [];
				$mediaGeral    				 	= [];
				$totalFechadas 				 	= [];
				$mediaPermanenciaPosto 		 	= [];
				$mediaPermanenciaTelecontrol 	= [];
				$mediaPermanenciaFabrica     	= [];
				$mediaPermanenciaTransportadora = [];

				$mediaPermanencia[] 	   	  = ["name" => utf8_encode("Posto"), "data" => []];
				$mediaPermanencia[] 	  	  = ["name" => utf8_encode("Telecontrol"), "data" => []];
				$mediaPermanencia[]     	  = ["name" => utf8_encode("Fábrica"), "data" => []];
				$mediaPermanencia[] 		  = ["name" => utf8_encode("Transportadora"), "data" => []];

				while ($dadosOs = pg_fetch_object($resDetalhado)) {

					$arrDadosMesAno[$dadosOs->mes_ano_finalizada]["posto"] 		 	+= (int) ($dadosOs->dias_abertura_diagnostico + $dadosOs->dias_em_conserto);
					$arrDadosMesAno[$dadosOs->mes_ano_finalizada]["telecontrol"] 	+= (int) ($dadosOs->dias_diagnostico_auditoria + $dadosOs->dias_embarque_nf + $dadosOs->dias_embarque_liberado);
					$arrDadosMesAno[$dadosOs->mes_ano_finalizada]["fabrica"] 	 	+= (int) $dadosOs->dias_auditoria_abastecimento;
					$arrDadosMesAno[$dadosOs->mes_ano_finalizada]["transportadora"] += (int) $dadosOs->dias_em_transito;

					$arrTotalMesAno[$dadosOs->mes_ano_finalizada]++;

				}

				foreach ($arrDadosMesAno as $mes_ano => $arrValores) {

					$totalOsMes   = $arrTotalMesAno[$mes_ano];
					$totalDiasMes = ($arrValores["posto"] + $arrValores["telecontrol"] + $arrValores["fabrica"] + $arrValores["transportadora"]) / $totalOsMes;

					$mesesGrafico[]  = $mes_ano;
					$mediaGeral[]    = (int) $totalDiasMes;
					$totalFechadas[] = (int) $totalOsMes;

					$mediaPermanencia[0]["data"][] = (float) number_format($arrValores["posto"] / $totalOsMes, 2);
					$mediaPermanencia[1]["data"][] = (float) number_format($arrValores["telecontrol"] / $totalOsMes, 2);
					$mediaPermanencia[2]["data"][] = (float) number_format($arrValores["fabrica"] / $totalOsMes, 2);
					$mediaPermanencia[3]["data"][] = (float) number_format($arrValores["transportadora"] / $totalOsMes, 2);

					$mediaPermanenciaPosto[] 		  = (float) number_format($arrValores["posto"] / $totalOsMes, 2);
					$mediaPermanenciaTelecontrol[] 	  = (float) number_format($arrValores["telecontrol"] / $totalOsMes, 2);
					$mediaPermanenciaFabrica[] 		  = (float) number_format($arrValores["fabrica"] / $totalOsMes, 2);
					$mediaPermanenciaTransportadora[] = (float) number_format($arrValores["transportadora"] / $totalOsMes, 2);

					?>
					<tr>
						<td class="tac" style="background-color: rgb(89, 109, 155);color: white;font-weight: bolder;">
							<span hidden><?= implode("", array_reverse(explode("/", $mes_ano))) ?></span>
							<?= $mes_ano ?>
						</td>
						<td class="tac"><?= $totalOsMes ?></td>
						<td class="tac"><?= number_format($totalDiasMes, 2) ?></td>
						<td class="tac" style="background-color: rgb(124,181,236);color: white;font-weight: bolder;text-shadow: 1px 1px black;">
							<?= number_format($arrValores["posto"] / $totalOsMes, 2) ?>
						</td>
						<td class="tac" style="background-color: rgb(67,67,72);color: white;font-weight: bolder;text-shadow: 1px 1px black;">
							<?= number_format($arrValores["telecontrol"] / $totalOsMes, 2) ?>
						</td>
						<td class="tac" style="background-color: rgb(144,237,125);color: white;font-weight: bolder;text-shadow: 1px 1px black;">
							<?= number_format($arrValores["fabrica"] / $totalOsMes, 2) ?>
						</td>
						<td class="tac" style="background-color: rgb(247,163,92);color: white;font-weight: bolder;text-shadow: 1px 1px black;">
							<?= number_format($arrValores["transportadora"] / $totalOsMes, 2) ?>
						</td>
					</tr>
				<?php
				}

				$mediaPermanenciaGrafico = json_encode($mediaPermanencia); 

				$jsonMesesGrafico		 = json_encode($mesesGrafico);
				$jsonMediaGeral  		 = json_encode($mediaGeral);
				$jsonFechadasGrafico     = json_encode($totalFechadas);
				$jsonTransportadora      = json_encode($mediaPermanenciaTransportadora);
				$jsonTelecontrol         = json_encode($mediaPermanenciaTelecontrol);
				$jsonFabrica         	 = json_encode($mediaPermanenciaFabrica);
				$jsonPosto      		 = json_encode($mediaPermanenciaPosto);

				?>
			</tbody>
			<tfoot>
				<tr>

				</tr>
			</tfoot>
		</table>
		<?php
			$excel = ob_get_contents();
			$fp = fopen($arquivoOs,"w");
	        fwrite($fp, $excel);
	        fclose($fp);
		?>
		<script>
			Highcharts.chart('permanencia', {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: 'Média Tempo de Permanência de OSs (dias)'
			    },
			    subtitle: {
				    text: ''
				},
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>,
			    },
			    yAxis: {
			        min: 0,
			        title: {
			            text: ''
			        },
			        stackLabels: {
			            enabled: true,
			            style: {
			                fontWeight: 'bold',
			                color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
			            }
			        }
			    },
			    legend: {
			        align: 'right',
			        x: -30,
			        verticalAlign: 'top',
			        y: 25,
			        floating: true,
			        backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
			        borderColor: '#CCC',
			        borderWidth: 1,
			        shadow: false
			    },
			    tooltip: {
			        headerFormat: '<b>{point.x}</b><br/>',
			        pointFormat: '{series.name}: {point.y}<br/>Horas: {point.stackTotal}'
			    },
			    plotOptions: {
			        column: {
			            stacking: 'normal',
			            dataLabels: {
			                enabled: true,
			                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
			            }
			        }
			    },
			    series: <?= $mediaPermanenciaGrafico ?>
			});
		</script>
		<br />
		<div class="tipos-header">
			Qtde. OSs Fechadas
		</div>
		<div id="qtde_fechadas" style="width: 800px; height: auto; margin: 0 auto;" hidden></div>
		<script>
			Highcharts.chart("qtde_fechadas", {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: "Total de OSs fechadas por mês"
			    },
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Qtde. Fechadas",
			        data: <?= $jsonFechadasGrafico ?>
			    }]
			});
		</script>
		<div class="tipos-header">
			Média dias em aberto
		</div>
		<div id="media_geral" style="width: 800px; height: auto; margin: 0 auto;" hidden></div>
		<script>
			Highcharts.chart("media_geral", {
			    chart: {
			        type: 'bar'
			    },
			    title: {
			        text: "Média dias abertura > fechamento por mês"
			    },
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Média de dias",
			        data: <?= $jsonMediaGeral ?>
			    }]
			});
		</script>
		<div class="tipos-header">
			Média tempo permanência assistência técnica
		</div>
		<div id="qtde_posto" style="width: 800px; height: auto; margin: 0 auto;" hidden></div>
		<script>
			Highcharts.chart("qtde_posto", {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: "Média tempo permanência assistência técnica"
			    },
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Média",
			        data: <?= $jsonPosto ?>
			    }]
			});
		</script>
		<div class="tipos-header">
			Média tempo permanência telecontrol
		</div>
		<div id="qtde_telecontrol" style="width: 800px; height: auto; margin: 0 auto;" hidden></div>
		<script>
			Highcharts.chart("qtde_telecontrol", {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: "Média tempo permanência telecontrol"
			    },
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Média",
			        data: <?= $jsonTelecontrol ?>
			    }]
			});
		</script>
		<div class="tipos-header">
			Média tempo permanência fábrica
		</div>
		<div id="qtde_fabrica" style="width: 800px; height: auto; margin: 0 auto;" hidden></div>
		<script>
			Highcharts.chart("qtde_fabrica", {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: "Média tempo permanência fábrica"
			    },
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Média",
			        data: <?= $jsonFabrica ?>
			    }]
			});
		</script>
		<div class="tipos-header">
			Média tempo permanência trasportadora
		</div>
		<div id="qtde_transportadora" style="width: 800px; height: auto; margin: 0 auto;" hidden></div>
		<script>
			Highcharts.chart("qtde_transportadora", {
			    chart: {
			        type: 'column'
			    },
			    title: {
			        text: "Média tempo permanência transportadora"
			    },
			    xAxis: {
			        categories: <?= $jsonMesesGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Média",
			        data: <?= $jsonTransportadora ?>
			    }]
			});
		</script>
	</div>
	<div id="tabela_ofensores" style="display: none;">
		<?php
		ob_start();
		?>
		<table class="table table-bordered table-fixed">
			<thead>
				<tr class="titulo_tabela">
					<th colspan="4">Lista dos <?= $qtde_postos ?> postos com mais Ordens de Serviço dentro do período selecionado</th>
				</tr>
				<tr class="titulo_coluna">
					<th>Posto</th>
					<th>Qtde OSs</th>
					<th>Média Dias em Aberto</th>
					<th>Média Tempo Permanência Assistência</th>
				</tr>
			</thead>
			<tbody>
				
					<?php


					while ($dadosPostos = pg_fetch_object($resPostosOfensores)) {
					?>
						<tr>
							<td><?= $dadosPostos->codigo_posto ?> - <?= $dadosPostos->nome_posto ?></td>
							<td class="tac"><?= $dadosPostos->quantidade ?></td>
							<td class="tac"><?= $dadosPostos->media ?></td>
							<td class="tac" style="background-color: rgb(124,181,236);color: white;font-weight: bolder;text-shadow: 1px 1px black;"><?= converterParaDias($dadosPostos->tempo_permanencia_posto) ?></td>
						</tr>
					<?php
					}
					?>
			</tbody>
			<tfoot>
				<tr>
				</tr>
			</tfoot>
		</table>
		<?php
		$excel = ob_get_contents();
		$fp = fopen($arquivoOsPosto,"w");
	    fwrite($fp, $excel);
	    fclose($fp);
	    ?>
	</div>
	<div id="tabela_estados" style="display: none;">
	    <?php
		ob_start();
		?>
		<table class="table table-bordered table-striped table-fixed table-hover">
			<thead>
				<tr class="titulo_tabela">
					<th colspan="3">OSs abertas por</th>
				</tr>
				<tr class="titulo_coluna">
					<th>Estado</th>
					<th>Qtde Fechadas no período</th>
					<th>Média Dias em Aberto</th>
				</tr>
			</thead>
			<tbody>
				<?php

				$estadosGrafico = [];
				$mediaGrafico   = [];

				while ($dadosEstados = pg_fetch_object($resEstados)) {

					$estadosGrafico[] = utf8_encode($dadosEstados->nome_estado);
					$mediaGrafico[]   = (int) $dadosEstados->media;

					?>
					<tr>
						<td><?= $dadosEstados->nome_estado ?> - <?= $dadosEstados->estado ?></td>
						<td class="tac"><?= $dadosEstados->quantidade ?></td>
						<td class="tac"><?= $dadosEstados->media ?></td>
					</tr>
				<?php
				}

				$jsonEstadosGrafico = json_encode($estadosGrafico);
				$jsonMediaGrafico   = json_encode($mediaGrafico);

				?>
			</tbody>
			<tfoot>
				<tr>
				</tr>
			</tfoot>
		</table>
		<div id="media_estado" style="width: 800px; height: 400px; margin: 0 auto;"></div>
		<script>
			Highcharts.chart("media_estado", {
			    chart: {
			        type: 'bar'
			    },
			    title: {
			        text: "Média dias abertura > fechamento por Estado"
			    },
			    xAxis: {
			        categories: <?= $jsonEstadosGrafico ?>
			    },
			    credits: {
			        enabled: false
			    },
			    series: [{
			        name: "Estado",
			        data: <?= $jsonMediaGrafico ?>
			    }]
			});
		</script>
	<?php

		$excel = ob_get_contents();
		$fp = fopen($arquivoEstado,"w");
	    fwrite($fp, $excel);
	    fclose($fp);
	?>
	<br />
	</div>
	<script>
		$.dataTableLoad({ table: ".table" });
	</script>
<?php
} else if (isset($_POST['pesquisar']) && !empty($os)) { ?>
	<table class="table table-bordered table-striped table-fixed table-hover">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="5">OS <?= $os ?></th>
			</tr>
			<tr class="titulo_coluna">
				<th>OS</th>
				<th>Posto</th>
				<th>Data Abertura</th>
				<th>Data Fechamento</th>
				<th>Dias Em aberto</th>
			</tr>
		</thead>
		<tbody>
			<?php

			$mesesGrafico = [];
			$mediaGeral   = [];

			while ($dadosOs = pg_fetch_object($resOs)) {

				$mesesGrafico[] = $dadosOs->os;
				$mediaGeral[]   = (int) $dadosOs->dias;

				?>
				<tr>
					<td class="tac"><?= $dadosOs->os ?></td>
					<td class="tac"><?= $dadosOs->codigo_posto ?> - <?= $dadosOs->nome_posto ?></td>
					<td class="tac"><?= $dadosOs->data_abertura ?></td>
					<td class="tac"><?= $dadosOs->data_fechamento ?></td>
					<td class="tac"><?= $dadosOs->dias ?></td>
				</tr>
			<?php
			}

			$jsonMesesGrafico = json_encode($mesesGrafico);
			$jsonMediaGeral   = json_encode($mediaGeral);

			?>
		</tbody>
	</table>
	<?php
		$excel = ob_get_contents();
		$fp = fopen($arquivoOs,"w");
	    fwrite($fp, $excel);
	    fclose($fp);
	?>
	<div id="media_geral" style="width: 800px; height: auto; margin: 0 auto;float: left;"></div>
	<script>
		Highcharts.chart("media_geral", {
		    chart: {
		        type: 'bar'
		    },
		    title: {
		        text: "Dias em aberto OS <?= $os ?>"
		    },
		    xAxis: {
		        categories: <?= $jsonMesesGrafico ?>
		    },
		    credits: {
		        enabled: false
		    },
		    series: [{
		        name: "Dias",
		        data: <?= $jsonMediaGeral ?>
		    }]
		});
	</script>
<?php
} else if (isset($_POST['pesquisar']) && count($msg_erro) == 0) { ?>
	<div class="alert alert-warning">
		<h4>Não foram encontrados resultados para a consulta</h4>
	</div>
<?php
}
?>
<?php
include 'rodape.php';
?>