<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$hd_chamado_origem         = $_POST['hd_chamado_origem'];
	$hd_chamado         = $_POST['hd_chamado'];
	if (strlen($hd_chamado) == 0) {
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
	}

	if (!count($msg_erro["msg"])) {
	
		if (strlen($hd_chamado) > 0) {
			$cond = " AND tbl_hd_chamado.hd_chamado={$hd_chamado}";
		} else {
			$cond = " AND tbl_hd_chamado.data::DATE BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'";
		}
		if (strlen($hd_chamado_origem) > 0) {
			$mostra_produtos = false;
			$sqlOr = "SELECT * FROM tbl_hd_chamado_origem WHERE hd_chamado_origem={$hd_chamado_origem} AND fabrica=".$login_fabrica;
			$resOr = pg_query($con, $sqlOr);
			if (pg_num_rows($resOr) > 0) {
				$xdescricao = pg_fetch_result($resOr, 0, 'descricao');
				if (strtolower(retira_acentos($xdescricao)) == "producao") {
					$mostra_produtos = true;
					$cond .= " AND tbl_hd_chamado_item.produto IS NOT NULL ";
					$joinItens = " JOIN tbl_hd_chamado_item USING(hd_chamado)
								   JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_item.produto AND tbl_produto.fabrica_i={$login_fabrica}";
					$camposPD = "tbl_hd_chamado_item.qtde as produto_qtde,
						        TO_CHAR(tbl_hd_chamado_item.data_nf, 'DD/MM/YYYY') as produto_data_nf, 
						        tbl_hd_chamado_item.nota_fiscal as produto_nota_fiscal, 
						        tbl_hd_chamado_item.produto as produto_id, 
						        tbl_produto.referencia as produto_referencia,  
						        tbl_produto.descricao as produto_descricao,";

				}
			}

			$cond .= " AND tbl_hd_chamado_extra.hd_chamado_origem={$hd_chamado_origem}";
		} 

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";
	
		$cond_consumidor_revenda     =  '';

		if (in_array($login_fabrica, [189])) {
			$cond_consumidor_revenda = " tbl_hd_chamado_extra.consumidor_revenda, ";
		}

		$sql = "SELECT tbl_hd_chamado_custo.hd_chamado_custo,
		               tbl_hd_chamado_custo.hd_chamado_categoria_custo,
		               tbl_hd_chamado_custo.taxa_banco,
		               tbl_hd_chamado_custo.juros,
		               tbl_hd_chamado_custo.frete_ida,
		               tbl_hd_chamado_custo.frete_volta,
		               tbl_hd_chamado_custo.reentrega,
		               tbl_hd_chamado_custo.reprocesso,
		               tbl_hd_chamado_custo.extras,
		               tbl_hd_chamado.hd_chamado,
		               {$camposPD}
		               {$cond_consumidor_revenda}
		               tbl_hd_chamado.data::DATE AS data_atendimento,
		               tbl_hd_chamado_categoria_custo.descricao,
		               tbl_hd_chamado_extra.array_campos_adicionais,
		               tbl_pedido.cliente_nome AS nome_representante,
		               tbl_hd_chamado_extra.nome AS nome_cliente,
		               tbl_hd_chamado_extra.cpf AS cpf_cliente,
		               tbl_hd_chamado_origem.descricao AS depto_gerador,
		               tbl_hd_classificacao.descricao AS registro_ref,
		               tbl_hd_subclassificacao.descricao AS especif_ref_registro,
		               tbl_hd_motivo_ligacao.descricao AS acao,
		               (SELECT tbl_faturamento_item.nota_fiscal_origem 
		               	  FROM  tbl_faturamento 
		               	  JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento=tbl_faturamento.faturamento 
		               	  WHERE tbl_pedido.pedido=tbl_faturamento.pedido AND tbl_faturamento.fabrica=189 limit 1
		               	 ) AS nota_fiscal_origem,
		               tbl_transportadora.nome  AS nome_transportadora,
		               (SELECT tbl_hd_chamado_item.data::DATE FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado=tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.status_item='Resolvido' ORDER BY tbl_hd_chamado_item.hd_chamado_item ASC LIMIT 1) AS data_encerramento
				  FROM tbl_hd_chamado
				  JOIN tbl_hd_chamado_extra USING(hd_chamado)
				  {$joinItens}
			LEFT JOIN tbl_hd_chamado_custo USING(hd_chamado,fabrica)
			 LEFT JOIN tbl_hd_chamado_categoria_custo USING(hd_chamado_categoria_custo,fabrica)
	         LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao AND tbl_hd_classificacao.fabrica = $login_fabrica
	         LEFT JOIN tbl_hd_subclassificacao ON tbl_hd_subclassificacao.hd_subclassificacao = tbl_hd_chamado_extra.hd_subclassificacao 
	         LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
	         LEFT JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = $login_fabrica
		 	 LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_hd_chamado_extra.posto AND tbl_posto_fabrica.fabrica={$login_fabrica}
			 LEFT JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto
			 LEFT JOIN tbl_pedido ON tbl_pedido.pedido=tbl_hd_chamado_extra.pedido AND tbl_pedido.fabrica={$login_fabrica}
			 LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora=tbl_pedido.transportadora 
				 WHERE tbl_hd_chamado.fabrica = {$login_fabrica}  
				   AND tbl_hd_chamado.status <> 'Cancelado' 
				{$cond}
				{$limit}";
				//echo nl2br($sql);
		$resSubmit = pg_query($con, $sql);
	}

	$mostra_identificacao     = false;
	if (in_array($login_fabrica, [189])) {
		$mostra_identificacao = true;
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_custo_atendimento-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$th_produto = "";
			$xcolspan = 34;
			if ($mostra_produtos) {
				$th_produto = "
				<th nowrap>Referência Produto</th>
						<th nowrap>Nome Produtos</th>
						<th nowrap>Qtde</th>
						<th nowrap>Nota Fiscal</th>
						<th nowrap>Data Nota Fiscal</th>";
				$xcolspan = 39;
			}
			
			$th_identificacao     = '';
			if ($mostra_identificacao) {
				$th_identificacao = "<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Identificação</th>";
			}

			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='{$xcolspan}' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE CUSTOS DE ATENDIMENTOS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Nº Atendimento</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Data Atendimento</th>
	                        {$th_identificacao}
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Planta</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Registrado por</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>MÊS e ano da abertura do Atendimentos</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Mercado / Gerencia</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Representante</th>

	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Representante Manual</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Transportadora Manual</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Transportadora Redespacho Manual</th>

	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>CPF/CNPJ Cliente</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Código Cliente</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Cliente</th>
	                        {$th_produto}
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>NF Origem</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Transportadora</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Depto Gerador da RRC</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Registro Ref a</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Especificação de Referência de Registro</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Ação</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Taxa Banco</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Juros</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Frete Ida</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Frete Volta</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Reentrega</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Reprocesso / Descarte</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Custos Extras</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Total</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>ABS CLIENTE</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>ABS VIAPOL</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>ABS REPRES</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>ABS TRANSP</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>NFE de Devolução</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Dias p/ Atend.</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>DATA DO ENCERRAMENTO</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>CTE 1 - 2</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>ORDEM ENTRADA 1 - 2</th>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>REMESSA 1 - 2</th>

						</tr>
					</thead>
			";
			fwrite($file, $thead);


			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
   				$total    			    = 0;
   				$taxa_banco    			    = 0;
   				$juros    			    = 0;
   				$frete_ida    			    = 0;
   				$frete_volta    			    = 0;
   				$reentrega    			    = 0;
   				$reprocesso    			    = 0;
   				$extras    			    = 0;				
				$depto_gerador 			= pg_fetch_result($resSubmit, $i, 'depto_gerador');
				$registro_ref 			= pg_fetch_result($resSubmit, $i, 'registro_ref');
				$especif_ref_registro	= pg_fetch_result($resSubmit, $i, 'especif_ref_registro');
				$acao 					= pg_fetch_result($resSubmit, $i, 'acao');
				$nome_representante		= pg_fetch_result($resSubmit, $i, 'nome_representante');
				$nome_cliente 			= pg_fetch_result($resSubmit, $i, 'nome_cliente');
				$cpf_cliente 			= pg_fetch_result($resSubmit, $i, 'cpf_cliente');
				$cod_cliente 			= pg_fetch_result($resSubmit, $i, 'cod_cliente');
				$categoria    		    = pg_fetch_result($resSubmit, $i, 'descricao');
				$hd_chamado			    = pg_fetch_result($resSubmit, $i, 'hd_chamado');
				$data_atendimento       = pg_fetch_result($resSubmit, $i, 'data_atendimento');
				$produto_qtde 			= pg_fetch_result($resSubmit, $i, 'produto_qtde');
						$produto_data_nf 			= pg_fetch_result($resSubmit, $i, 'produto_data_nf');
						$produto_nota_fiscal 			= pg_fetch_result($resSubmit, $i, 'produto_nota_fiscal');
						$produto_referencia 			= pg_fetch_result($resSubmit, $i, 'produto_referencia');
						$produto_descricao 			= pg_fetch_result($resSubmit, $i, 'produto_descricao');
						$produto_id 			= pg_fetch_result($resSubmit, $i, 'produto_id');
				$taxa_banco			    += pg_fetch_result($resSubmit, $i, 'taxa_banco');
				$juros             	    += pg_fetch_result($resSubmit, $i, 'juros');
				$frete_ida         	    += pg_fetch_result($resSubmit, $i, 'frete_ida');
				$frete_volta	 	    += pg_fetch_result($resSubmit, $i, 'frete_volta');
				$reentrega 			    += pg_fetch_result($resSubmit, $i, 'reentrega');
				$reprocesso      	    += pg_fetch_result($resSubmit, $i, 'reprocesso');
				$extras      		    += pg_fetch_result($resSubmit, $i, 'extras');
				$data_encerramento      = pg_fetch_result($resSubmit, $i, 'data_encerramento');
				$nome_transportadora      = pg_fetch_result($resSubmit, $i, 'nome_transportadora');
				list($ano, $mes, $dia)  = explode("-", $data_atendimento);
				$total    			    = $taxa_banco+$juros+$frete_ida+$frete_volta+$reentrega+$reprocesso+$extras;

				if ($mostra_identificacao) {
					$consumidor_revenda = pg_fetch_result($resSubmit, $i, 'consumidor_revenda');

					switch ($consumidor_revenda) {
						case 'R': $consumidor_revenda = "Representante"; break;
						case 'V': $consumidor_revenda = "Viapol"; break;
						case 'C': $consumidor_revenda = "Clientes"; break;
						case 'T': $consumidor_revenda = "Transportadora"; break;							
						default:  $consumidor_revenda = $consumidor_revenda;
					}
				}

				$manual_nome_repre = "";
				$manual_nome_transp = "";
				$manual_nome_transp_redespacho = "";
				$nome_empresa = "";
				$cte_1 = "";
				$cte_2 = "";
				$n_ordem_entrada_1 = "";
				$n_ordem_entrada_2 = "";
				$n_remessa_1 = "";
				$n_remessa_2 = "";
				$n_nf_entrada_1 = "";
				$n_nf_entrada_2 = "";
				$mercado_gerencia = "";
				$planta = "";
				$codigo_cliente_revenda = "";
				$array_campos_adicionais = json_decode(pg_fetch_result($resSubmit, $i, 'array_campos_adicionais'), 1);

				if (isset($array_campos_adicionais["nome_empresa"])) {
					$nome_empresa 		    = $array_campos_adicionais["nome_empresa"];
				}
				if (isset($array_campos_adicionais["planta"])) {
					$planta 		    = $array_campos_adicionais["planta"];
				}
				if (isset($array_campos_adicionais["n_nf_entrada_1"])) {
					$n_nf_entrada_1 		    = $array_campos_adicionais["n_nf_entrada_1"];
				}
				if (isset($array_campos_adicionais["n_nf_entrada_2"])) {
					$n_nf_entrada_2 		    = $array_campos_adicionais["n_nf_entrada_2"];
				}


				if (isset($array_campos_adicionais["cte_1"])) {
					$cte_1 		    = $array_campos_adicionais["cte_1"];
				}

				if (isset($array_campos_adicionais["cte_2"])) {
					$cte_2 		    = $array_campos_adicionais["cte_2"];
				}


				if (isset($array_campos_adicionais["cte_2"])) {
					$n_ordem_entrada_1 		    = $array_campos_adicionais["cte_2"];
				}

				if (isset($array_campos_adicionais["n_ordem_entrada_1"])) {
					$n_ordem_entrada_2 		    = $array_campos_adicionais["n_ordem_entrada_2"];
				}


				if (isset($array_campos_adicionais["n_remessa_1"])) {
					$n_remessa_1 		    = $array_campos_adicionais["n_remessa_1"];
				}
				if (isset($array_campos_adicionais["n_remessa_2"])) {
					$n_remessa_2 		    = $array_campos_adicionais["n_remessa_2"];
				}



				if (isset($array_campos_adicionais["mercado_gerencia"])) {
					$mercado_gerencia 		    = $array_campos_adicionais["mercado_gerencia"];
					$mercado_gerencia       = str_replace(['"'], "", $mercado_gerencia);
				}

				if (isset($array_campos_adicionais["manual_nome_repre"])) {
					$manual_nome_repre 		    = $array_campos_adicionais["manual_nome_repre"];
				}
				if (isset($array_campos_adicionais["manual_nome_transp"])) {
					$manual_nome_transp 		    = $array_campos_adicionais["manual_nome_transp"];
				}
				if (isset($array_campos_adicionais["manual_nome_transp_redespacho"])) {
					$manual_nome_transp_redespacho 		    = $array_campos_adicionais["manual_nome_transp_redespacho"];
				}
				if (isset($array_campos_adicionais["codigo_cliente_revenda"])) {
					$codigo_cliente_revenda 		    = $array_campos_adicionais["codigo_cliente_revenda"];
				}

				if (strlen($mercado_gerencia) > 0) {

					$sqlMercado = "SELECT * FROM tbl_mercado_gerencia WHERE mercado_gerencia={$mercado_gerencia} AND fabrica=".$login_fabrica;
					$resMercado = pg_query($con, $sqlMercado);
					$xmercado_gerencia = "";
					if (pg_num_rows($resMercado) > 0) {
						$xmercado_gerencia = pg_fetch_result($resMercado, 0, 'descricao');
					}
				} else {
					$xmercado_gerencia = "";
				}

				if ($mostra_produtos) {
					$indice = $produto_id."_".$hd_chamado;
					$array_relatorio[$indice]["produto_qtde"] = $produto_qtde;
					$array_relatorio[$indice]["produto_data_nf"] = $produto_data_nf;
					$array_relatorio[$indice]["produto_nota_fiscal"] = $produto_nota_fiscal;
					$array_relatorio[$indice]["produto_referencia"] = $produto_referencia;
					$array_relatorio[$indice]["produto_descricao"] = $produto_descricao;
				} else {
					$indice = $hd_chamado;
				}

				if ($mostra_identificacao) {
					$array_relatorio[$indice]["consumidor_revenda"] = $consumidor_revenda;
				}

				$array_relatorio[$indice]["mercado_gerencia"] = $xmercado_gerencia;

				$array_relatorio[$indice]["mes_ano"] = mesPorExtenso($mes,$ano);
				$array_relatorio[$indice]["planta"] = str_replace(['"'], "", $planta);
				$array_relatorio[$indice]["data_atendimento"] = $data_atendimento;
				$array_relatorio[$indice]["registrado_por"] = str_replace(['"'], "", $nome_empresa);
				$array_relatorio[$indice]["nome_representante"] = $nome_representante;
				$array_relatorio[$indice]["nome_cliente"] = $nome_cliente;
				$array_relatorio[$indice]["cpf_cliente"] = $cpf_cliente;
				$array_relatorio[$indice]["cod_cliente"] = $cod_cliente;
				$array_relatorio[$indice]["depto_gerador"] = $depto_gerador;
				$array_relatorio[$indice]["registro_ref"] = $registro_ref;
				$array_relatorio[$indice]["especif_ref_registro"] = $especif_ref_registro;
				$array_relatorio[$indice]["acao"] = $acao;
				$array_relatorio[$indice]["categoria"] = $categoria;
				$array_relatorio[$indice]["data_encerramento"] = strlen($data_encerramento) > 0 ? geraDataNormal($data_encerramento) : "";
				$diasAtendimento = "";
				if (strlen($data_encerramento) > 0) {
					$sqlData = "SELECT '{$data_encerramento}'::date-'{$data_atendimento}'::date AS DIAS";
					$resData = pg_query($con, $sqlData);
					$diasAtendimento = pg_fetch_result($resData, 0, 'DIAS');
				}
				$array_relatorio[$indice]["dias_atendimento"] = $diasAtendimento;

				$array_relatorio[$indice]["hd_chamado"] = $hd_chamado;
				$array_relatorio[$indice]["manual_nome_repre"] = $manual_nome_repre;
				$array_relatorio[$indice]["cod_cliente"] = $codigo_cliente_revenda;
				$array_relatorio[$indice]["manual_nome_transp"] = $manual_nome_transp;
				$array_relatorio[$indice]["manual_nome_transp_redespacho"] = $manual_nome_transp_redespacho;
				$array_relatorio[$indice]["nome_transportadora"] = $nome_transportadora;
				$array_relatorio[$indice]["n_nf_entrada_1"] = str_replace(['"'], "", $n_nf_entrada_1);
				$array_relatorio[$indice]["n_nf_entrada_2"] = str_replace(['"'], "", $n_nf_entrada_2);

				$array_relatorio[$indice]["cte_1"] = str_replace(['"'], "", $cte_1);
				$array_relatorio[$indice]["cte_2"] = str_replace(['"'], "", $cte_2);
				$array_relatorio[$indice]["n_ordem_entrada_1"] = str_replace(['"'], "", $n_ordem_entrada_1);
				$array_relatorio[$indice]["n_ordem_entrada_2"] = str_replace(['"'], "", $n_ordem_entrada_2);
				$array_relatorio[$indice]["n_remessa_1"] = str_replace(['"'], "", $n_remessa_1);
				$array_relatorio[$indice]["n_remessa_2"] = str_replace(['"'], "", $n_remessa_2);


				$nota_fiscal = [];
				$sqlHDITEM = "SELECT DISTINCT nota_fiscal 
				                FROM tbl_hd_chamado_item 
				               WHERE produto IS NOT NULL 
				                 AND hd_chamado=".$hd_chamado;
				$resHDITEM = pg_query($con, $sqlHDITEM);

				if (pg_num_rows($resHDITEM) > 0) {
					foreach (pg_fetch_all($resHDITEM) as $k => $xrow) {
						if (strlen($xrow["nota_fiscal"]) > 0) {
							$nota_fiscal[] = $xrow["nota_fiscal"];
						}
					}
				}
				if (count($nota_fiscal) > 0) {
					$array_relatorio[$indice]["nota_fiscal_origem"] = implode("<br>", $nota_fiscal);
				}

		
				$array_relatorio[$indice]["custos"][]= [

					"taxa_banco" 	=> $taxa_banco,
					"juros" 		=> $juros,
					"frete_ida" 	=> $frete_ida,
					"frete_volta" 	=> $frete_volta,
					"reentrega" 	=> $reentrega,
					"reprocesso" 	=> $reprocesso,
					"extras" 		=> $extras,
					"total"			=> $total,
				];
				$array_relatorio[$indice][$categoria] =  [

					"total"			=> $total,
				];
				if(count($array_relatorio) > 10 or ($i+1 == pg_num_rows($resSubmit))){
					$resultado = "resultado_$i";
					$$resultado = $array_relatorio;
					unset($array_relatorio);
					$array_resultados[] = $i;
				}
			}

			foreach($array_resultados as $result) {
				$array_relatorio = "resultado_$result";

				foreach ($$array_relatorio as $key => $rows) {
					$taxa_banco  = 0;
					$juros 		 = 0;
					$frete_ida 	 = 0;
					$frete_volta = 0;
					$reentrega 	 = 0;
					$reprocesso  = 0;
					$extras 	 = 0;
					$total 		 = 0;		

					$nome_abs_viapol  = $rows["ABS VIAPOL"]["total"];
					$nome_abs_cliente = $rows["ABS CLIENTE"]["total"];
					$nome_abs_repres  = $rows["ABS REPRES"]["total"];
					$nome_abs_transp  = $rows["ABS TRANSP"]["total"];
					foreach ($rows["custos"] as $s => $r) {
						$taxa_banco 		+= $r["taxa_banco"];
						$juros 				+= $r["juros"];
						$frete_ida 			+= $r["frete_ida"];
						$frete_volta 		+= $r["frete_volta"];
						$reentrega 			+= $r["reentrega"];
						$reprocesso 		+= $r["reprocesso"];
						$extras 			+= $r["extras"];
						$total 				+= $r["total"];
					}
					$td_produtos = "";
					if ($mostra_produtos) {
						$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_referencia']."</td>";
						$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_descricao']."</td>";
						$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_qtde']."</td>";
						$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_nota_fiscal']."</td>";
						$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_data_nf']."</td>";
					}

					$td_identificao     = '';
					if ($mostra_identificacao) {
						$td_identificao = "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['consumidor_revenda']."</td>";
					}

					$body .= "<tr>
						<td nowrap class='tac' style='vertical-align: middle;'><b>".$rows['hd_chamado']."</b></td>
						<td nowrap class='tac' style='vertical-align: middle;'>".geraDataNormal($rows['data_atendimento'])."</td>
					{$td_identificao}
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['planta']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['registrado_por']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['mes_ano']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['mercado_gerencia']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nome_representante']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['manual_nome_repre']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['manual_nome_transp']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['manual_nome_transp_redespacho']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['cpf_cliente']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['cod_cliente']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nome_cliente']."</td>
					{$td_produtos}
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nota_fiscal_origem']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nome_transportadora']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['depto_gerador']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['registro_ref']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['especif_ref_registro']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['acao']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($taxa_banco, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($juros, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($frete_ida, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($frete_volta, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($reentrega, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($reprocesso, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($extras, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($total, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_cliente, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_viapol, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_repres, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_transp, 2, '.', '')."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['n_nf_entrada_1']."<br>".$rows['n_nf_entrada_2']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['dias_atendimento']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['data_encerramento']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['cte_1']."<br>".$rows['cte_2']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['n_ordem_entrada_1']."<br>".$rows['n_ordem_entrada_2']."</td>
					<td nowrap class='tac' style='vertical-align: middle;'>".$rows['n_remessa_1']."<br>".$rows['n_remessa_2']."</td>

					</tr>";
				}
			}
			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='{$xcolspan}' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".count($array_relatorio)." registros</th>
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

function geraDataNormal($data) {

	list($ano, $mes, $dia) = explode("-", $data);

	return $dia.'/'.$mes.'/'.$ano;
}
function mesPorExtenso($mes, $ano){

    switch ($mes){

        case 1: $mes = "Janeiro"; break;
        case 2: $mes = "Fevereiro"; break;
        case 3: $mes = "Março"; break;
        case 4: $mes = "Abril"; break;
        case 5: $mes = "Maio"; break;
        case 6: $mes = "Junho"; break;
        case 7: $mes = "Julho"; break;
        case 8: $mes = "Agosto"; break;
        case 9: $mes = "Setembro"; break;
        case 10: $mes = "Outubro"; break;
        case 11: $mes = "Novembro"; break;
        case 12: $mes = "Dezembro"; break;

    }

    $data_extenso = $mes . ' de ' . $ano;

    return $data_extenso;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CUSTOS DE ATENDIMENTOS";
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
<style>
	.titulo_coluna2 th{
		background: #ccc !important;
	}
	.tal{
		text-align: left;
	}
</style>
<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$(document).on("click", ".btn-ver-produtos", function() {
			var posicao  = $(this).data("posicao");
			if( $(".mostra_pd_"+posicao).is(":visible")){
			  $(".mostra_pd_"+posicao).hide();
			}else{
			  $(".mostra_pd_"+posicao).show();
			}
		});
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

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("hd_chamado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='hd_chamado'>Número do Atendimento</label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="hd_chamado" id="hd_chamado" class='span12' value="<? echo $hd_chamado;?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("hd_chamado_origem", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='hd_chamado_origem'>Depto Gerador da RRC</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<?php 
								$cond_hd_chamado_origem = '';	
								$isAdminBloq            = false;

								if (in_array($login_fabrica, [189])) {
									if ($login_admin == 12153) { // bloqueado para o usuário "qualidade"
										$sqlIdOrigem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE TRIM(UPPER(fn_retira_especiais(descricao))) = 'PRODUCAO' AND fabrica = {$login_fabrica}";
										$resIdOrigem            = pg_query($con, $sqlIdOrigem);
										$id_hd_chamado_origem   = (pg_num_rows($resIdOrigem) > 0) ? pg_fetch_result($resIdOrigem, 0, 'hd_chamado_origem') : '';
										$cond_hd_chamado_origem = " AND hd_chamado_origem = {$id_hd_chamado_origem}";
										$isAdminBloq            = true;	
									}
								}
							?>
							<select name="hd_chamado_origem" id="hd_chamado_origem" <?= ($isAdminBloq == true) ? 'readonly' : '' ?>>
								<option value="" <?= ($isAdminBloq == true) ? 'disabled' : '' ?>>Escolha</option>
								<?php 

									 $sqlOrigem = "
						                SELECT hd_chamado_origem, descricao
						                FROM tbl_hd_chamado_origem
						                WHERE fabrica = $login_fabrica
						                {$cond_hd_chamado_origem}
						                ORDER BY descricao
						            ";
						            $resOrigem = pg_query($con, $sqlOrigem);
						            foreach (pg_fetch_all($resOrigem) as $key => $rows) {
						            	$selected     = ($hd_chamado_origem == $rows["hd_chamado_origem"]) ? "selected" : "";

						            	if (in_array($login_fabrica, [189])) {
						            		$selected = ($hd_chamado_origem == $rows["hd_chamado_origem"] || $isSelected == true) ? "selected" : "";
						            	}
						            	
						            	echo '<option '.$selected.' value="'.$rows["hd_chamado_origem"].'">'.$rows["descricao"].'</option>';
						            }
								?>
							</select>
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
			<table id="resultado_os_atendimento" class='table table-striped table-bordered table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th nowrap>Nº Atendimento</th>
                        <th nowrap>Data Atendimento</th>
                        <?= ($mostra_identificacao) ? "<th nowrap>Identificação</th>" : "" ?>
                        <th nowrap>Planta</th>
                        <th nowrap>Registrado por</th>
                        <th nowrap>MÊS e ano da abertura do Atendimentos</th>
                        <th nowrap>Mercado / Gerencia</th>
                        <th nowrap>Representante</th>
                        <th nowrap>Representante Manual</th>
                        <th nowrap>Transportadora Manual</th>
                        <th nowrap>Transportadora Redespacho Manual</th>
                        <th nowrap>CPF/CNPJ Cliente</th>
                        <th nowrap>Código Cliente</th>
                        <th nowrap>Cliente</th>
						<?php if ($mostra_produtos) {?>
						<th nowrap>Referência Produto</th>
						<th nowrap>Nome Produtos</th>
						<th nowrap>Qtde</th>
						<th nowrap>Nota Fiscal</th>
						<th nowrap>Data Nota Fiscal</th>
						<?php }?>
                        <th nowrap>NF Origem</th>
                        <th nowrap>Transportadora</th>
                        <th nowrap>Depto Gerador da RRC</th>
                        <th nowrap>Registro Ref a</th>
                        <th nowrap>Especificação de Referência de Registro</th>
                        <th nowrap>Ação</th>
						<th nowrap>Taxa Banco</th>
						<th nowrap>Juros</th>
						<th nowrap>Frete Ida</th>
						<th nowrap>Frete Volta</th>
						<th nowrap>Reentrega</th>
						<th nowrap>Reprocesso / Descarte</th>
						<th nowrap>Custos Extras</th>
						<th nowrap>Total</th>
						<th nowrap>ABS CLIENTE</th>
						<th nowrap>ABS VIAPOL</th>
						<th nowrap>ABS REPRES</th>
						<th nowrap>ABS TRANSP</th>
						<th nowrap>NFE de Devolução</th>
						<th nowrap>Dias p/ Atend.</th>
						<th nowrap>DATA DO ENCERRAMENTO</th>
						<th nowrap>CTE 1 - 2</th>
						<th nowrap>ORDEM ENTRADA 1 - 2</th>
						<th nowrap>REMESSA 1 - 2</th>
					</tr>
				</thead>
				<tbody>
<?php
		
					for ($i = 0; $i < $count; $i++) {
           				$total    			    = 0;
           				$taxa_banco    			    = 0;
           				$juros    			    = 0;
           				$frete_ida    			    = 0;
           				$frete_volta    			    = 0;
           				$reentrega    			    = 0;
           				$reprocesso    			    = 0;
           				$extras    			    = 0;
						$depto_gerador 			= pg_fetch_result($resSubmit, $i, 'depto_gerador');
						$registro_ref 			= pg_fetch_result($resSubmit, $i, 'registro_ref');
						$especif_ref_registro	= pg_fetch_result($resSubmit, $i, 'especif_ref_registro');
						$acao 					= pg_fetch_result($resSubmit, $i, 'acao');
						$nome_representante		= pg_fetch_result($resSubmit, $i, 'nome_representante');
						$nome_cliente 			= pg_fetch_result($resSubmit, $i, 'nome_cliente');
						$cpf_cliente 			= pg_fetch_result($resSubmit, $i, 'cpf_cliente');
						$cod_cliente 			= pg_fetch_result($resSubmit, $i, 'cod_cliente');
						$produto_qtde 			= pg_fetch_result($resSubmit, $i, 'produto_qtde');
						$produto_data_nf 			= pg_fetch_result($resSubmit, $i, 'produto_data_nf');
						$produto_nota_fiscal 			= pg_fetch_result($resSubmit, $i, 'produto_nota_fiscal');
						$produto_referencia 			= pg_fetch_result($resSubmit, $i, 'produto_referencia');
						$produto_descricao 			= pg_fetch_result($resSubmit, $i, 'produto_descricao');
						$produto_id 			= pg_fetch_result($resSubmit, $i, 'produto_id');
						$categoria    		    = pg_fetch_result($resSubmit, $i, 'descricao');
						$hd_chamado			    = pg_fetch_result($resSubmit, $i, 'hd_chamado');
						$data_atendimento       = pg_fetch_result($resSubmit, $i, 'data_atendimento');
						$taxa_banco			    += pg_fetch_result($resSubmit, $i, 'taxa_banco');
						$juros             	    += pg_fetch_result($resSubmit, $i, 'juros');
						$frete_ida         	    += pg_fetch_result($resSubmit, $i, 'frete_ida');
						$frete_volta	 	    += pg_fetch_result($resSubmit, $i, 'frete_volta');
						$reentrega 			    += pg_fetch_result($resSubmit, $i, 'reentrega');
						$reprocesso      	    += pg_fetch_result($resSubmit, $i, 'reprocesso');
						$extras      		    += pg_fetch_result($resSubmit, $i, 'extras');
						$nome_transportadora    = pg_fetch_result($resSubmit, $i, 'nome_transportadora');
						$data_encerramento      = pg_fetch_result($resSubmit, $i, 'data_encerramento');
						list($ano, $mes, $dia)  = explode("-", $data_atendimento);

						if ($mostra_identificacao) {
							$consumidor_revenda = pg_fetch_result($resSubmit, $i, 'consumidor_revenda');

							switch ($consumidor_revenda) {
								case 'R': $consumidor_revenda = "Representante"; break;
								case 'V': $consumidor_revenda = "Viapol"; break;
								case 'C': $consumidor_revenda = "Clientes"; break;
								case 'T': $consumidor_revenda = "Transportadora"; break;							
								default:  $consumidor_revenda = $consumidor_revenda;
							}
						}

						$nome_empresa = "";
						$n_nf_entrada_1 = "";
						$n_nf_entrada_2 = "";
						$mercado_gerencia = "";
						$manual_nome_repre = "";
						$manual_nome_transp = "";
						$manual_nome_transp_redespacho = "";
						$codigo_cliente_revenda = "";
						$planta = "";
						$cte_1 = "";
						$cte_2 = "";
						$n_ordem_entrada_1 = "";
						$n_ordem_entrada_2 = "";
						$n_remessa_1 = "";
						$n_remessa_2 = "";
						$array_campos_adicionais = json_decode(pg_fetch_result($resSubmit, $i, 'array_campos_adicionais'), 1);

						if (isset($array_campos_adicionais["nome_empresa"])) {
							$nome_empresa 		    = $array_campos_adicionais["nome_empresa"];
						}
						if (isset($array_campos_adicionais["planta"])) {
							$planta 		    = $array_campos_adicionais["planta"];
						}
						if (isset($array_campos_adicionais["n_nf_entrada_1"])) {
							$n_nf_entrada_1 		    = $array_campos_adicionais["n_nf_entrada_1"];
						}
						if (isset($array_campos_adicionais["n_nf_entrada_2"])) {
							$n_nf_entrada_2 		    = $array_campos_adicionais["n_nf_entrada_2"];
						}
						if (isset($array_campos_adicionais["mercado_gerencia"])) {
							$mercado_gerencia 		    = $array_campos_adicionais["mercado_gerencia"];
							$mercado_gerencia       = str_replace(['"'], "", $mercado_gerencia);
						}
						if (isset($array_campos_adicionais["manual_nome_repre"])) {
							$manual_nome_repre 		    = $array_campos_adicionais["manual_nome_repre"];
						}
						if (isset($array_campos_adicionais["manual_nome_transp"])) {
							$manual_nome_transp 		    = $array_campos_adicionais["manual_nome_transp"];
						}
						if (isset($array_campos_adicionais["manual_nome_transp_redespacho"])) {
							$manual_nome_transp_redespacho 		    = $array_campos_adicionais["manual_nome_transp_redespacho"];
						}
						if (isset($array_campos_adicionais["codigo_cliente_revenda"])) {
							$codigo_cliente_revenda 		    = $array_campos_adicionais["codigo_cliente_revenda"];
						}

						if (isset($array_campos_adicionais["cte_1"])) {
							$cte_1                     = $array_campos_adicionais["cte_1"];
						}
						if (isset($array_campos_adicionais["cte_2"])) {
							$cte_2                     = $array_campos_adicionais["cte_2"];
						}
						if (isset($array_campos_adicionais["n_ordem_entrada_1"])) {
							$n_ordem_entrada_1                     = $array_campos_adicionais["n_ordem_entrada_1"];
						}
						if (isset($array_campos_adicionais["n_ordem_entrada_2"])) {
							$n_ordem_entrada_2                     = $array_campos_adicionais["n_ordem_entrada_2"];
						}
						if (isset($array_campos_adicionais["n_remessa_1"])) {
							$n_remessa_1                     = $array_campos_adicionais["n_remessa_1"];
						}
						if (isset($array_campos_adicionais["n_remessa_2"])) {
							$n_remessa_2                     = $array_campos_adicionais["n_remessa_2"];
						}


						$total    			    = $taxa_banco+$juros+$frete_ida+$frete_volta+$reentrega+$reprocesso+$extras;

						if (strlen($mercado_gerencia) > 0) {
							$sqlMercado = "SELECT * FROM tbl_mercado_gerencia WHERE mercado_gerencia={$mercado_gerencia} AND fabrica=".$login_fabrica;
							$resMercado = pg_query($con, $sqlMercado);
							$xmercado_gerencia = "";
							if (pg_num_rows($resMercado) > 0) {
								$xmercado_gerencia = pg_fetch_result($resMercado, 0, 'descricao');
							}

						} else {
							$xmercado_gerencia = "";
						}

						if ($mostra_produtos) {
							$indice = $produto_id. "_".$hd_chamado;
							$array_relatorio[$indice]["produto_qtde"] = $produto_qtde;
							$array_relatorio[$indice]["produto_data_nf"] = $produto_data_nf;
							$array_relatorio[$indice]["produto_nota_fiscal"] = $produto_nota_fiscal;
							$array_relatorio[$indice]["produto_referencia"] = $produto_referencia;
							$array_relatorio[$indice]["produto_descricao"] = $produto_descricao;
						} else {
							$indice = $hd_chamado;
						}

						if ($mostra_identificacao) {
							$array_relatorio[$indice]["consumidor_revenda"] = $consumidor_revenda;
						}

						$array_relatorio[$indice]["mercado_gerencia"] = $xmercado_gerencia;

						$array_relatorio[$indice]["mes_ano"] = mesPorExtenso($mes,$ano);
						$array_relatorio[$indice]["hd_chamado"] = $hd_chamado;
						$array_relatorio[$indice]["data_atendimento"] = $data_atendimento;
						$array_relatorio[$indice]["registrado_por"] = str_replace(['"'], "", $nome_empresa);
						$array_relatorio[$indice]["planta"] = str_replace(['"'], "", $planta);
						$array_relatorio[$indice]["nome_representante"] = $nome_representante;
						$array_relatorio[$indice]["nome_cliente"] = $nome_cliente;
						$array_relatorio[$indice]["cpf_cliente"] = $cpf_cliente;
						$array_relatorio[$indice]["cod_cliente"] = $cod_cliente;
						$array_relatorio[$indice]["depto_gerador"] = $depto_gerador;
						$array_relatorio[$indice]["registro_ref"] = $registro_ref;
						$array_relatorio[$indice]["especif_ref_registro"] = $especif_ref_registro;
						$array_relatorio[$indice]["acao"] = $acao;
						$array_relatorio[$indice]["categoria"] = $categoria;
						$array_relatorio[$indice]["data_encerramento"] = strlen($data_encerramento) > 0 ? geraDataNormal($data_encerramento) : "";
						$diasAtendimento = "";
						if (strlen($data_encerramento) > 0) {
							$sqlData = "SELECT '{$data_encerramento}'::date-'{$data_atendimento}'::date AS DIAS";
							$resData = pg_query($con, $sqlData);
							$diasAtendimento = pg_fetch_result($resData, 0, 'DIAS');
						}
						$array_relatorio[$indice]["dias_atendimento"] = $diasAtendimento;


						$array_relatorio[$indice]["cod_cliente"] = $codigo_cliente_revenda;
						$array_relatorio[$indice]["manual_nome_repre"] = $manual_nome_repre;
						$array_relatorio[$indice]["manual_nome_transp"] = $manual_nome_transp;
						$array_relatorio[$indice]["manual_nome_transp_redespacho"] = $manual_nome_transp_redespacho;
						$array_relatorio[$indice]["nome_transportadora"] = $nome_transportadora;
						$array_relatorio[$indice]["n_nf_entrada_1"] = str_replace(['"'], "", $n_nf_entrada_1);
						$array_relatorio[$indice]["n_nf_entrada_2"] = str_replace(['"'], "", $n_nf_entrada_2);

						$array_relatorio[$indice]["cte_1"] = str_replace(['"'], "", $cte_1);
						$array_relatorio[$indice]["cte_2"] = str_replace(['"'], "", $cte_2);
						$array_relatorio[$indice]["n_ordem_entrada_1"] = str_replace(['"'], "", $n_ordem_entrada_1);
						$array_relatorio[$indice]["n_ordem_entrada_2"] = str_replace(['"'], "", $n_ordem_entrada_2);
						$array_relatorio[$indice]["n_remessa_1"] = str_replace(['"'], "", $n_remessa_1);
						$array_relatorio[$indice]["n_remessa_2"] = str_replace(['"'], "", $n_remessa_2);

						$nota_fiscal = [];
						$sqlHDITEM = "SELECT DISTINCT nota_fiscal 
						                FROM tbl_hd_chamado_item 
						               WHERE produto IS NOT NULL 
						                 AND hd_chamado=".$hd_chamado;
						$resHDITEM = pg_query($con, $sqlHDITEM);

						if (pg_num_rows($resHDITEM) > 0) {
							foreach (pg_fetch_all($resHDITEM) as $k => $xrow) {
								if (strlen($xrow["nota_fiscal"]) > 0) {
									$nota_fiscal[] = $xrow["nota_fiscal"];
								}
							}
						}
						if (count($nota_fiscal) > 0) {
							$array_relatorio[$indice]["nota_fiscal_origem"] = implode("<br>", $nota_fiscal);
						}

						$array_relatorio[$indice]["custos"][]= [

							"taxa_banco" 	=> $taxa_banco,
							"juros" 		=> $juros,
							"frete_ida" 	=> $frete_ida,
							"frete_volta" 	=> $frete_volta,
							"reentrega" 	=> $reentrega,
							"reprocesso" 	=> $reprocesso,
							"extras" 		=> $extras,
							"total"			=> $total,
						];
						$array_relatorio[$indice][$categoria] =  [

							"total"			=> $total,
						];

						if(count($array_relatorio) > 10 or ($i+1 == pg_num_rows($resSubmit))){
							$resultado = "resultado_$i";
							$$resultado = $array_relatorio;
							unset($array_relatorio);
							$array_resultados[] = $i;
						}
					}


					foreach($array_resultados as $result) {
						$array_relatorio = "resultado_$result";
					foreach ($$array_relatorio as $key => $rows) {
						$taxa_banco  = 0;
						$juros 		 = 0;
						$frete_ida 	 = 0;
						$frete_volta = 0;
						$reentrega 	 = 0;
						$reprocesso  = 0;
						$extras 	 = 0;
						$total 		 = 0;		

						$nome_abs_viapol  = $rows["ABS VIAPOL"]["total"];
						$nome_abs_cliente = $rows["ABS CLIENTE"]["total"];
						$nome_abs_repres  = $rows["ABS REPRES"]["total"];
						$nome_abs_transp  = $rows["ABS TRANSP"]["total"];
						foreach ($rows["custos"] as $s => $r) {
							$taxa_banco 		+= $r["taxa_banco"];
							$juros 				+= $r["juros"];
							$frete_ida 			+= $r["frete_ida"];
							$frete_volta 		+= $r["frete_volta"];
							$reentrega 			+= $r["reentrega"];
							$reprocesso 		+= $r["reprocesso"];
							$extras 			+= $r["extras"];
							$total 				+= $r["total"];
						}
						$td_produtos = "";
						if ($mostra_produtos) {
							$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_referencia']."</td>";
							$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_descricao']."</td>";
							$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_qtde']."</td>";
							$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_nota_fiscal']."</td>";
							$td_produtos .= "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['produto_data_nf']."</td>";
						}

						$td_identificao     = '';
						if ($mostra_identificacao) {
							$td_identificao = "<td nowrap class='tac' style='vertical-align: middle;'>".$rows['consumidor_revenda']."</td>";
						}

						$body = "<tr>
									<td nowrap class='tac' style='vertical-align: middle;'><a href='callcenter_interativo_new.php?callcenter=".$rows['hd_chamado']."' target='_blank' >".$rows['hd_chamado']."</a></td>
									<td nowrap class='tac' style='vertical-align: middle;'>".geraDataNormal($rows['data_atendimento'])."</td>
									{$td_identificao}
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['planta']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['registrado_por']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['mes_ano']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['mercado_gerencia']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nome_representante']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['manual_nome_repre']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['manual_nome_transp']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['manual_nome_transp_redespacho']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['cpf_cliente']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['cod_cliente']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nome_cliente']."</td>
									{$td_produtos}
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nota_fiscal_origem']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['nome_transportadora']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['depto_gerador']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['registro_ref']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['especif_ref_registro']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['acao']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($taxa_banco, 2, '.', '')."</td>
						            <td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($juros, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($frete_ida, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($frete_volta, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($reentrega, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($reprocesso, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($extras, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($total, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_cliente, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_viapol, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_repres, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($nome_abs_transp, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['n_nf_entrada_1']."<br>".$rows['n_nf_entrada_2']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['dias_atendimento']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['data_encerramento']."</td>
																		<td nowrap class='tac' style='vertical-align: middle;'>".$rows['cte_1']."<br>".$rows['cte_2']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['n_ordem_entrada_1']."<br>".$rows['n_ordem_entrada_2']."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$rows['n_remessa_1']."<br>".$rows['n_remessa_2']."</td>

								</tr>";
						echo $body;

					}
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

include 'rodape.php';?>
