<?php
/**
 *
 * integracao-wevo.php
 *
 * Integração de Pedidos e OS's com Elgin através do endpoint Wevo
 *
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) . '/../../class/ComunicatorMirror.php';


if($_serverEnvironment == 'development'){
	define('ENV', 'dev');
	define('EMAIL_LOG', 'ronald.santos@telecontrol.com.br');
	define('API_URL', 'http://homolintegracaoapi.elgin.com.br/api/Integracao/ReceberPedidoTelecontrol');
	define('API_LOGIN','integracao.elginup');
	define('API_TOKEN', 'B1nwVxPH0i4njsJYhWvQ2');
}else{
	define('ENV', 'producao');
	define('EMAIL_LOG', 'helpdesk@telecontrol.com.br');
	define('API_URL', 'https://sap-integracaoapi.elgin.com.br:2122/api/Integracao/ReceberPedidoTelecontrol');
	define('API_LOGIN','integracao.telecontrol');
	define('API_TOKEN', 'm7toeFnK06Bfcbeub770');

}

$data_sistema	= Date('Y-m-d-H-m');

$arquivo_err = "/tmp/elgin/integracao-wevo-os-pedido-{$data_sistema}.err";
$arquivo_log = "/tmp/elgin/integracao-wevo-os-pedido-{$data_sistema}.log";
system ("mkdir /tmp/elgin/ 2> /dev/null ; chmod 777 /tmp/elgin/" );

$vet['dest'] 		= EMAIL_LOG;

try {


	// Elgin
	$fabrica = 117;
	$fabrica_nome = 'elgin';
	$data_corte = '2019-03-07 00:00';

	$sql = "CREATE TEMP TABLE tmp_dados_envio (dados json);";
	$res = pg_query($con,$sql);

	/**
	*
	* Resgatar pedidos de Venda
	*
	*/

	$sqlPedidoVenda = "SELECT tbl_pedido.pedido,
						tbl_posto.cnpj,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data,
						tbl_tipo_pedido.descricao AS tipo_pedido,
						tbl_condicao.codigo_condicao,
						tbl_condicao.descricao AS condicao_descricao,
						tbl_tabela.sigla_tabela,
						tbl_tabela.descricao AS tabela,
						tbl_pedido.posto
					INTO TEMP tmp_pedidos
					FROM tbl_pedido
					JOIN tbl_tipo_pedido USING(tipo_pedido,fabrica)
					JOIN tbl_condicao USING(condicao,fabrica)
					JOIN tbl_tabela ON tbl_pedido.tabela = tbl_tabela.tabela
					JOIN tbl_posto USING(posto)
					WHERE tbl_pedido.fabrica = $fabrica
					AND tbl_pedido.data > '$data_corte' 
					AND tbl_pedido.finalizado IS NOT NULL
					AND tbl_pedido.status_pedido NOT IN(14,18)
					AND tbl_tipo_pedido.pedido_faturado IS TRUE
					AND tbl_pedido.exportado IS NULL
				WITH pedidos AS(
					SELECT * FROM tmp_pedidos
				),
				itensPedido AS (
					SELECT 	tbl_pedido_item.pedido,
							tbl_peca.referencia,
							tbl_pedido_item.qtde,
							tbl_pedido_item.preco
					FROM tmp_pedidos
					JOIN tbl_pedido_item USING(pedido)
					JOIN tbl_peca USING(peca)
				)

				INSERT INTO tmp_dados_envio(dados)
				SELECT json_build_object(
										'Login','".API_LOGIN."',
										'Token','".API_TOKEN."',
										'TipoPedido','P',
										'PedidoVendaGarantia',json_build_object(
											'CodPedido',pedidos.pedido,
											'Data',pedidos.data,
											'CnpjAutorizada',pedidos.cnpj,
											'TipoPedido','V',
											'CondicaoPagamentoCodigo',pedidos.codigo_condicao,
											'CondicaoPagamentoDescricao',pedidos.condicao_descricao,
											'TabelaPrecoCodigo',pedidos.sigla_tabela,
											'TabelaPrecoDescricao',pedidos.tabela,
											'ItensPedido', json_agg(
												json_build_object(
													'PecaProdutoCodigo',itensPedido.referencia,
													'Quantidade',itensPedido.qtde,
													'Preco',itensPedido.preco
												)
											)
										)

					)::json
				FROM pedidos
				JOIN itensPedido ON pedidos.pedido = itensPedido.pedido
				GROUP BY pedidos.pedido,
				pedidos.cnpj,
				pedidos.data,
				pedidos.tipo_pedido,
				pedidos.codigo_condicao,
				pedidos.condicao_descricao,
				pedidos.sigla_tabela,
				pedidos.tabela";
	$resPedidoVenda = pg_query($con,$sqlPedidoVenda);


	/**
	*
	* Resgatar Ordens de Serviço
	*
	*/

	$sqlOS = "SELECT 	tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_os.os,
						tbl_os.sua_os,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						to_char(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto,
						to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
						CASE WHEN tbl_os.os_reincidente IS TRUE THEN 'T' ELSE 'F' END AS os_reincidente ,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_os.defeito_reclamado_descricao,
						tbl_defeito_constatado.descricao AS defeito_constatado,
						tbl_solucao.descricao AS solucao,
						tbl_os.consumidor_nome,
						tbl_os.consumidor_fone,
						tbl_os.consumidor_cpf,
						tbl_os.revenda_nome,
						tbl_os.nota_fiscal,
						tbl_os.data_nf
				INTO TEMP tmp_oss
				FROM tbl_os
				JOIN tbl_os_extra USING(os)
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $fabrica
				JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $fabrica
				JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $fabrica
				JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica 
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
				WHERE tbl_os.fabrica = $fabrica
				AND tbl_os.data_digitacao > '$data_corte'
				AND tbl_os.finalizada IS NOT NULL
				AND tbl_os.data_fechamento IS NOT NULL
				AND tbl_os.importacao_fabrica IS NULL
				GROUP BY tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_os.os,
				tbl_os.sua_os,
				tbl_os.data_abertura,
				tbl_os.data_digitacao,
				tbl_os.data_conserto,
				tbl_os.data_fechamento,
				tbl_os_extra.os_reincidente,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_os.defeito_reclamado_descricao,
				tbl_defeito_constatado.descricao, 
				tbl_solucao.descricao, 
				tbl_os.consumidor_nome,
				tbl_os.consumidor_fone,
				tbl_os.consumidor_cpf,
				tbl_os.revenda_nome,
				tbl_os.nota_fiscal,
				tbl_os.data_nf;


				WITH oss AS(
					SELECT * FROM tmp_oss
				),
				itensOS AS(
					SELECT tbl_os_produto.os,
					tbl_peca.referencia AS referencia_peca,
					tbl_os_item.qtde,
					tbl_servico_realizado.descricao AS servico
					FROM tbl_os_produto
					JOIN tmp_oss ON tbl_os_produto.os = tmp_oss.os
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				)

				INSERT INTO tmp_dados_envio(dados) 
				SELECT json_build_object(
										'Login','".API_LOGIN."',
										'Token','".API_TOKEN."',
										'TipoPedido','OS',
										'OrdemServico',json_build_object(
											'PostoCodigo', oss.codigo_posto,
											'PostoNome', oss.nome,
											'CodOrdemServico', oss.sua_os,
											'NumeroControleOs', oss.sua_os,
											'DataAbertura', oss.data_abertura,
											'DataDigitacao', oss.data_digitacao,
											'DataConserto', oss.data_conserto,
											'DataFechamento', oss.data_fechamento,
											'OsReincidente', oss.os_reincidente,
											'ProdutoReferencia', oss.referencia,
											'ProdutoDescricao', oss.descricao,
											'DefeitoReclamado', oss.defeito_reclamado_descricao,
											'DefeitoConstatado', oss.defeito_constatado,
											'Solucao', oss.solucao,
											'ConsumidorNome', oss.consumidor_nome,
											'ConsumidorTelefone', oss.consumidor_fone,
											'ConsumidorNumeroDocumento', oss.consumidor_cpf,
											'RevendaNome', oss.revenda_nome,
											'NumeroNF', oss.nota_fiscal,
											'DataNF', oss.data_nf,
											'ItensOS',json_agg(
												json_build_object(
													'PecaProdutoCodigo', itensOS.referencia_peca,
													'Quantidade', itensOS.qtde,
													'Servico', itensOS.servico
												)
											)
										)
									)::json
				FROM oss
				LEFT JOIN itensOS ON oss.os = itensOS.os
				GROUP BY
				oss.codigo_posto,
				oss.nome,
				oss.sua_os,
				oss.sua_os,
				oss.data_abertura,
				oss.data_digitacao,
				oss.data_conserto,
				oss.data_fechamento,
				oss.os_reincidente,
				oss.referencia,
				oss.descricao,
				oss.defeito_reclamado_descricao,
				oss.defeito_constatado,
				oss.solucao,
				oss.consumidor_nome,
				oss.consumidor_fone,
				oss.consumidor_cpf,
				oss.revenda_nome,
				oss.nota_fiscal,
				oss.data_nf;";
	$resOS = pg_query($con,$sqlOS);


	/**
	*
	* Resgatar Pedidos de Garantia
	*
	*/

	$sql_carrega_tmp_pedido = "SELECT tbl_pedido.pedido,
					tbl_posto.cnpj,
					tbl_pedido.data,
					tbl_tipo_pedido.descricao AS tipo_pedido,
					tbl_condicao.codigo_condicao,
					tbl_condicao.descricao AS condicao_descricao,
					tbl_tabela.sigla_tabela,
					tbl_tabela.descricao AS tabela,
					tbl_pedido.posto
					INTO TEMP tmp_pedidos_os
					FROM tbl_pedido
					JOIN tbl_tipo_pedido USING(tipo_pedido,fabrica)
					JOIN tbl_condicao USING(condicao,fabrica)
					JOIN tbl_tabela ON tbl_pedido.tabela = tbl_tabela.tabela
					JOIN tbl_posto USING(posto)
					WHERE tbl_pedido.fabrica = $fabrica
					AND tbl_pedido.data > '$data_corte' 
					AND tbl_tipo_pedido.pedido_em_garantia IS TRUE
					AND tbl_pedido.status_pedido NOT IN(14,18)
					AND tbl_pedido.exportado IS NULL";
	$query_carrega_tmp_pedido = pg_query($con, $sql_carrega_tmp_pedido);

	$sql_carrega_tmp_os = "SELECT tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_os.os,
			tbl_os.sua_os,
			tbl_os.data_abertura,
			tbl_os.data_digitacao,
			tbl_os.data_conserto,
			tbl_os.data_fechamento,
			tbl_os_extra.os_reincidente,
			tbl_produto.referencia,
			tbl_produto.descricao,
			tbl_os.defeito_reclamado_descricao,
			tbl_defeito_constatado.descricao AS defeito_constatado,
			tbl_solucao.descricao AS solucao,
			tbl_os.consumidor_nome,
			tbl_os.consumidor_fone,
			tbl_os.consumidor_cpf,
			tbl_os.revenda_nome,
			tbl_os.nota_fiscal,
			tbl_os.data_nf,
			tmp_pedidos_os.pedido AS pedido_os
			INTO TEMP tmp_oss_pedidos
			FROM tbl_os
			JOIN tbl_os_extra USING(os)
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $fabrica
			JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $fabrica
			JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $fabrica
			JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $fabrica
			JOIN tmp_pedidos_os ON tbl_os_item.pedido = tmp_pedidos_os.pedido
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica AND tbl_posto_fabrica.posto = tmp_pedidos_os.posto
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto AND tbl_posto.posto = tmp_pedidos_os.posto
			WHERE tbl_os.fabrica = $fabrica
			GROUP BY tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_os.os,
			tbl_os.sua_os,
			tbl_os.data_abertura,
			tbl_os.data_digitacao,
			tbl_os.data_conserto,
			tbl_os.data_fechamento,
			tbl_os_extra.os_reincidente,
			tbl_produto.referencia,
			tbl_produto.descricao,
			tbl_os.defeito_reclamado_descricao,
			tbl_defeito_constatado.descricao, 
			tbl_solucao.descricao, 
			tbl_os.consumidor_nome,
			tbl_os.consumidor_fone,
			tbl_os.consumidor_cpf,
			tbl_os.revenda_nome,
			tbl_os.nota_fiscal,
			tbl_os.data_nf,
			tmp_pedidos_os.pedido";
	$query_carrega_tmp_os = pg_query($con, $sql_carrega_tmp_os);

	$sql_json = "WITH pedidos AS(
				SELECT * 
				FROM tmp_pedidos_os
				JOIN tmp_oss_pedidos ON tmp_pedidos_os.pedido = tmp_oss_pedidos.pedido_os
				),
				itensPedido AS (
					SELECT 	tbl_pedido_item.pedido,
							tbl_peca.referencia,
							tbl_pedido_item.qtde,
							tbl_pedido_item.preco
					FROM tmp_pedidos_os
					JOIN tbl_pedido_item USING(pedido)
					JOIN tbl_peca USING(peca)
					GROUP BY
					tbl_pedido_item.pedido,
					tbl_peca.referencia,
					tbl_pedido_item.qtde,
					tbl_pedido_item.preco
				),
				itensOS AS(
					SELECT 	tbl_peca.referencia AS referencia_peca,
							tbl_os_item.qtde,
							tbl_servico_realizado.descricao AS servico,
							tbl_os_item.pedido
					FROM tbl_os_produto
					JOIN tmp_oss_pedidos ON tbl_os_produto.os = tmp_oss_pedidos.os
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
					GROUP BY
					tbl_peca.referencia,
					tbl_os_item.qtde,
					tbl_servico_realizado.descricao,
					tbl_os_item.pedido
				)

			INSERT INTO tmp_dados_envio(dados)
			SELECT json_build_object(
				'Login','".API_LOGIN."',
				'Token','".API_TOKEN."',
				'TipoPedido','P',
				'PedidoVendaGarantia',json_build_object(
					'CodPedido',pedidos.pedido,
					'Data',pedidos.data,
					'CnpjAutorizada',pedidos.cnpj,
					'TipoPedido','R',
					'CondicaoPagamentoCodigo',pedidos.codigo_condicao,
					'CondicaoPagamentoDescricao',pedidos.condicao_descricao,
					'TabelaPrecoCodigo',pedidos.sigla_tabela,
					'TabelaPrecoDescricao',pedidos.tabela,
					'ItensPedido', json_agg(
						json_build_object(
							'PecaProdutoCodigo',itensPedido.referencia,
							'Quantidade',itensPedido.qtde,
							'Preco',itensPedido.preco
						)
					)
				),
				'OrdemServico',json_build_object(
					'PostoCodigo', pedidos.codigo_posto,
					'PostoNome', pedidos.nome,
					'CodOrdemServico', 0,
					'NumeroControleOs', pedidos.sua_os,
					'DataAbertura', pedidos.data_abertura,
					'DataDigitacao', pedidos.data_digitacao,
					'DataConserto', pedidos.data_conserto,
					'DataFechamento', pedidos.data_fechamento,
					'OsReincidente', pedidos.os_reincidente,
					'ProdutoReferencia', pedidos.referencia,
					'ProdutoDescricao', pedidos.descricao,
					'DefeitoReclamado', pedidos.defeito_reclamado_descricao,
					'DefeitoConstatado', pedidos.defeito_constatado,
					'Solucao', pedidos.solucao,
					'ConsumidorNome', pedidos.consumidor_nome,
					'ConsumidorTelefone', pedidos.consumidor_fone,
					'ConsumidorNumeroDocumento', pedidos.consumidor_cpf,
					'RevendaNome', pedidos.revenda_nome,
					'NumeroNF', pedidos.nota_fiscal,
					'DataNF', pedidos.data_nf,
					'ItensOS',json_agg(
						json_build_object(
							'PecaProdutoCodigo', itensOS.referencia_peca,
							'Quantidade', itensOS.qtde,
							'Servico', itensOS.servico
						)
					)
				)
				
			)::json
			FROM pedidos
			JOIN itensPedido ON pedidos.pedido = itensPedido.pedido
			LEFT JOIN itensOS ON pedidos.pedido = itensOS.pedido
			GROUP BY pedidos.pedido,
			pedidos.cnpj,
			pedidos.data,
			pedidos.tipo_pedido,
			pedidos.codigo_condicao,
			pedidos.condicao_descricao,
			pedidos.sigla_tabela,
			pedidos.tabela,
			pedidos.codigo_posto,
			pedidos.nome,
			pedidos.sua_os,
			pedidos.data_abertura,
			pedidos.data_digitacao,
			pedidos.data_conserto,
			pedidos.data_fechamento,
			pedidos.os_reincidente,
			pedidos.referencia,
			pedidos.descricao,
			pedidos.defeito_reclamado_descricao,
			pedidos.defeito_constatado,
			pedidos.solucao,
			pedidos.consumidor_nome,
			pedidos.consumidor_fone,
			pedidos.consumidor_cpf,
			pedidos.revenda_nome,
			pedidos.nota_fiscal,
			pedidos.data_nf,
			pedidos.pedido_os";

	$query_json = pg_query($con,$sql_json);

	$sql = "SELECT * FROM tmp_dados_envio";
	$res = pg_query($con,$sql);
	
	$responses = [];
	$errors = [];

	$arqErr = fopen($arquivo_err,"w");
	$arqLog = fopen($arquivo_log,"w");
	
	for ($i = 0; $i < pg_num_rows($res); $i++) {
		
		try{
			$curl = curl_init();

			$json_request = utf8_encode(pg_fetch_result($res,$i,'dados'));
			curl_setopt_array($curl, array(
				CURLOPT_URL => API_URL,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $json_request,
				CURLOPT_HTTPHEADER => array(		    
					"Content-Type: application/json",		    
				),
			));

			$response = curl_exec($curl);		
			$responses[] = $response;
			$response = json_decode($response,1);

			$err = curl_error($curl);
			curl_close($curl);

			if($err != ""){
				$errors[] = $err;
			}
			if(array_key_exists("Message", $response) || $response['Erro'] != ""){
				$errors[] = json_encode($response);
				fwrite($arqErr,json_encode($response));
			}else{
				fwrite($arqLog,json_encode($response));

				$obj = json_decode($json_request);
				$pedido = $obj->PedidoVendaGarantia->CodPedido;
				$os = $obj->OrdemServico->NumeroControleOs;

				if(!empty($pedido)){
					$sql = "UPDATE tbl_pedido SET exportado = CURRENT_TIMESTAMP, status_pedido = 2 WHERE pedido = $pedido;";
				}else if(!empty($os)){
					$sql = "UPDATE tbl_os SET importacao_fabrica = CURRENT_TIMESTAMP WHERE os = $os;";
				}
				$resUp = pg_query($con,$sql);
			}

		}catch(Exception $e){
		
			$errors[] = $e->getMessage();
		}
	}

	fclose($arqErr);
	fclose($arqLog);
	

	if (count($errors) > 0) {
		$errors = implode($errors,"\n");		
		$communicatorMirror = new ComunicatorMirror();
		try{
			$res = $communicatorMirror->post($vet['dest'], "Erros Integração Elgin WEVO", $errors);
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}else{
		$responses = implode($responses,"\n");
		file_put_contents($arquivo_log, date("H:i:s")."\n\n".$responses."\n", FILE_APPEND);
	}
} catch (Exception $e) {
	$communicatorMirror = new ComunicatorMirror();
	$res = $communicatorMirror->post($vet['dest'], "Erros Integração Elgin WEVO", $e->getMessage());
}
