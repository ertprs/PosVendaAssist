<?php

class NotaFiscalServico{

	private $_fabrica;

	/* http://<ipservidor>:<porta>/<SisWeb>/rest/<Serviço> */
	protected
		$_url_servidor = "http://sisweb.mondialline.com.br/SIS_SND/rest/wsgravaspd",
		$_chave_acesso,
		$ambiente = 1;

	public function __construct($fabrica, $env = 'production') {
		global $_serverEnvironment;
		
		if($_serverEnvironment == 'development'){
			$this->_url_servidor = "http://sisweb-melhoria.mondialline.com.br/SIS_SND/rest/wsgravaspd";
			$this->ambiente = 2;
		}else{
			$this->_url_servidor = "http://sisweb.mondialline.com.br/SIS_SND/rest/wsgravaspd";
			$this->ambiente = 1;
		}	
		
		$this->_fabrica = $fabrica;
		$this->_chave_acesso = $this->getKey();
	}

	public function gravaDespesaWs($extrato = null, $ressarcimento = null, $codigo = null){
		if (!is_null($extrato)) {
			$dadosDespesa = $this->getDadosDespesa($extrato, null, $codigo);
		} else if (!is_null($ressarcimento)) {
			$dadosDespesa = $this->getDadosRessarcimento($ressarcimento);
		}

		$dadosDespesa["SdEntSPD"]["GrupoFinanceiroCodigo"] = "20602";
		$dadosDespesa["SdEntSPD"]["UnidadeOperacional"]    = 83;
		$dadosDespesa["SdEntSPD"]["UsuarioChaveGUID"]      = $this->_chave_acesso;
		$dadosDespesa["SdEntSPD"]["AmbienteTipo"]          = $this->ambiente; /* 1 - Produção | 2 - Homoloção */
		$dadosDespesa = json_encode($dadosDespesa);

		$ch = curl_init($this->_url_servidor);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosDespesa);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	public function comunicadoPosto($extrato, $data_previsao, $comunicado = false, $email = false, $extratos = null){
		global $con;

		$posto = $this->getPosto($extrato);

		if($posto == false){
			return false;
		}

		if (!empty($extratos)) {
			$extratos_msg = implode(",", $extratos);
			$mensagem = "Previsão de pagamento dos extratos {$extratos_msg} para o dia {$data_previsao}";
			$msg_insert = "Previsão para os pagamentos dos Extratos: {$extratos_msg}";
		} else {
			$mensagem = "Previsão de pagamento do extrato {$extrato} para o dia {$data_previsao}";
			$msg_insert = "Previsão para o pagamento do Extrato: {$extrato}";
		}


		if($comunicado == true){
			$sql = "INSERT INTO tbl_comunicado 
					(
						mensagem, 
						tipo,
						fabrica,
						descricao,
						posto,
						obrigatorio_site,
						ativo
					) 
					VALUES 
					(
						'{$mensagem}',
						'Com. Unico Posto',
						{$this->_fabrica},
						'$msg_insert',
						{$posto},
						true,
						true
					)";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				return false;
			}
		}

		if($email == true){
			// 2017-01-05 - TcComm
			include_once (__DIR__ . '/../../../../class/communicator.class.php');

			$TcMail = new TcComm($GLOBALS['externalId'], $GLOBALS['externalEmail']);

			$email_posto = $this->getEmailPosto($posto);
			#$email_posto = "matheus.knopp@telecontrol.com.br"; 
			$assunto = "Previsão para o pagamento do Extrato: {$extrato} - Mondial";
			$enviado = $TcMail->sendMail($email_posto, $assunto, $mensagem, $externalEmail);

			if (!$enviado) {
				$headers = 'From: suporte@telecontrol.com.br' . "\r\n" .
					'Reply-To: webmaster@example.com' . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

				if(!mail($email_posto, $assunto, $mensagem, $headers)){
					return false;
				}
			}
		}

		return true;
	}

	public function getPosto($extrato = ""){
		global $con;

		if(!empty($extrato)){
			$sql = "SELECT posto FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$this->_fabrica}";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				return false;
			}else{
				return pg_fetch_result($res, 0, "posto");
			}
		}else{
			return false;
		}
	}

	public function getEmailPosto($posto = ""){
		global $con;

		if(!empty($posto)){
			$sql = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$this->_fabrica}";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				return false;
			}else{
				return pg_fetch_result($res, 0, "contato_email");
			}
		}else{
			return false;
		}
	}

	public function getDadosRessarcimento($ressarcimento) {
		global $con;

		if (empty($ressarcimento)) {
			return false;
		}

		$dadosDespesa = array();
		if ($this->_fabrica == 151) {
			$cond = " INNER JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.os = tbl_os.os
				  INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado AND tbl_hd_chamado.fabrica = {$this->_fabrica}"; 
		} else {
			MONDIALEXTRA:

			$cond = " INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
				  INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado AND tbl_hd_chamado.fabrica = {$this->_fabrica} ";
			$extraos = "sim";
		}

		$sql = "SELECT 
					tbl_ressarcimento.cpf,
					tbl_ressarcimento.nome,
					tbl_banco.codigo AS banco,
					tbl_ressarcimento.agencia,
					tbl_ressarcimento.conta,
					tbl_ressarcimento.tipo_conta,
					tbl_hd_classificacao.descricao as classificacao_descricao,
					tbl_os.nota_fiscal,
					TO_CHAR(tbl_os.data_nf, 'YYYY/MM/DD') AS data_nf,
					tbl_hd_chamado.hd_chamado,
					TO_CHAR(tbl_ressarcimento.previsao_pagamento, 'YYYY/MM/DD') AS previsao_pagamento,
					tbl_ressarcimento.valor_original,
					tbl_ressarcimento.observacao,
					TO_CHAR(tbl_ressarcimento.data_input,'YYYY/MM/DD') AS data_emissao
				FROM tbl_ressarcimento
				JOIN tbl_os ON tbl_os.os = tbl_ressarcimento.os AND tbl_os.fabrica = {$this->_fabrica}
				{$cond}
				LEFT JOIN tbl_hd_classificacao on tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao 
				JOIN tbl_banco ON tbl_banco.banco = tbl_ressarcimento.banco
				WHERE tbl_ressarcimento.fabrica = {$this->_fabrica}
				AND tbl_ressarcimento.ressarcimento = {$ressarcimento}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			if ($this->_fabrica == 151 and empty($extraos)) {
				goto MONDIALEXTRA;
			}
			return false;
		} else {
			$nota_fiscal = pg_fetch_result($res, 0, "nota_fiscal");
			$data_nf = pg_fetch_result($res, 0, "data_nf");
			
			$hd_classificacao_descricao = pg_fetch_result($res, 0, 'classificacao_descricao');

			list($agencia, $agencia_digito) = explode("-", pg_fetch_result($res, 0, "agencia"));
			list($conta, $conta_digito)     = explode("-", pg_fetch_result($res, 0, "conta"));

			$dadosDespesa["SdEntSPD"]["ParticipanteCPFCNPJ"] = pg_fetch_result($res, 0, "cpf");
			$dadosDespesa["SdEntSPD"]["BancoCodigo"]         = pg_fetch_result($res, 0, "banco");
			$dadosDespesa["SdEntSPD"]["AgenciaCodigo"]       = $agencia;
			$dadosDespesa["SdEntSPD"]["AgenciaDigito"]       = $agencia_digito;
			$dadosDespesa["SdEntSPD"]["TipoConta"]           = pg_fetch_result($res, 0, "tipo_conta");
			$dadosDespesa["SdEntSPD"]["ContaNumero"]         = $conta;
			$dadosDespesa["SdEntSPD"]["ContaDigito"]         = $conta_digito;
			$dadosDespesa["SdEntSPD"]["CPFCNPJTitular"]      = pg_fetch_result($res, 0, "cpf");
			$dadosDespesa["SdEntSPD"]["Nominal"]      = pg_fetch_result($res, 0, "nome");
			
			$dadosDespesa["SdEntSPD"]["NotaFiscalNumero"]    = $nota_fiscal;
			$dadosDespesa["SdEntSPD"]["DataEmissao"]         = pg_fetch_result($res, 0, "data_emissao");
			$dadosDespesa["SdEntSPD"]["FormaPagamento"]      = 2;
			$dadosDespesa["SdEntSPD"]["TipoDespesa"]         = 1;
			$dadosDespesa["SdEntSPD"]["ReferenciaExterna"]   = pg_fetch_result($res, 0, "hd_chamado");
			$dadosDespesa["SdEntSPD"]["Observacao"]          = utf8_encode(pg_fetch_result($res, 0, "observacao"));

			$dadosDespesa["SdEntSPD"]["Parcela"] = array(
				array(
					"DataVencimento"  => pg_fetch_result($res, 0, "previsao_pagamento"),
					"ValorVencimento" => pg_fetch_result($res, 0, "valor_original")
				)
			);

			if($hd_classificacao_descricao == "PROCON"){
				$dadosDespesa["SdEntSPD"]["Item"] = array(
					array(
					"CentroCusto" => "51621",
					"ProdutoCodigo" => "40010028", /* Verificar com a Mondial */
					"ContaContabilCodigo" => "311220416", /* Verificar com a Mondial */
					"Quantidade" => "1",
					"ValorUnitario" => pg_fetch_result($res, 0, "valor_original"),
					"FinalidadeCodigo" => 11
					)
				);

			}else{
				$dadosDespesa["SdEntSPD"]["Item"] = array(
					array(
						"CentroCusto"         => "51621",
						"ProdutoCodigo"       => "40010028", /* Verificar com a Mondial */
						"ContaContabilCodigo" => "311220415", /* Verificar com a Mondial */
						"Quantidade"          => "1",
						"ValorUnitario"       => pg_fetch_result($res, 0, "valor_original"),
						"FinalidadeCodigo"    => 11
					)
				);
			}			
		}

		return $dadosDespesa;
	}

	public function getDadosDespesa($extrato, $agrupado = null, $codigo = null){

		if(empty($extrato)){
			return false;
		}

		global $con;

		$dadosDespesa = array();

		$distinct = "";
		$campos   = " tbl_extrato.total,";
		$join     = "";
		$where    = " tbl_extrato.extrato = {$extrato} ";
		$group_by = "";

		if (!empty($codigo)) {
			$distinct = " DISTINCT ";
			$campos   = " SUM(tbl_extrato.total) AS total, ";
			$join     = " LEFT JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato ";
			$where    = " tbl_extrato_agrupado.codigo = '$codigo' ";
			$group_by = " GROUP BY tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.banco, tbl_posto_fabrica.agencia, tbl_posto_fabrica.tipo_conta, tbl_posto_fabrica.conta, tbl_posto_fabrica.cpf_conta, tbl_posto_fabrica.parametros_adicionais, tbl_posto_fabrica.obs_conta, tbl_extrato.previsao_pagamento, tbl_extrato_pagamento.nf_autorizacao, tbl_extrato_pagamento.serie_nf, tbl_extrato_pagamento.data_nf";
		}

		$sql = "SELECT $distinct
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.banco,
					tbl_posto_fabrica.agencia,
					tbl_posto_fabrica.tipo_conta,
					tbl_posto_fabrica.conta,
					tbl_posto_fabrica.cpf_conta,
					tbl_posto_fabrica.parametros_adicionais,
					tbl_posto_fabrica.obs_conta,
					$campos
					tbl_extrato.previsao_pagamento,
					tbl_extrato_pagamento.nf_autorizacao,
					tbl_extrato_pagamento.serie_nf,
					tbl_extrato_pagamento.data_nf 
				FROM tbl_extrato 
				JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto  
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica} 
				JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato 
				$join
				WHERE $where
				$group_by";

		$res = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			return false;
		}else{

			$tipo_conta = (strtolower(pg_fetch_result($res, 0, "tipo_conta")) == "conta corrente") ? "C" : "P";

			$valor = str_replace(",", "", number_format(pg_fetch_result($res, 0, "total"), 2));

			$parametros_adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);

			$digito_agencia = (isset($parametros_adicionais["digito_agencia"])) ? $parametros_adicionais["digito_agencia"] : "";
			$digito_conta   = (isset($parametros_adicionais["digito_conta"])) ? $parametros_adicionais["digito_conta"] : "";
			
			$dadosDespesa["SdEntSPD"]["ParticipanteCPFCNPJ"] = pg_fetch_result($res, 0, "cnpj");
			$dadosDespesa["SdEntSPD"]["BancoCodigo"]         = pg_fetch_result($res, 0, "banco");
			$dadosDespesa["SdEntSPD"]["AgenciaCodigo"]       = pg_fetch_result($res, 0, "agencia");
			$dadosDespesa["SdEntSPD"]["AgenciaDigito"]       = $digito_agencia;
			$dadosDespesa["SdEntSPD"]["TipoConta"]           = $tipo_conta; /* C - Contato Corrente | P - Poupança */
			$dadosDespesa["SdEntSPD"]["ContaNumero"]         = pg_fetch_result($res, 0, "conta");
			$dadosDespesa["SdEntSPD"]["ContaDigito"]         = $digito_conta;
			$dadosDespesa["SdEntSPD"]["CPFCNPJTitular"]      = pg_fetch_result($res, 0, "cpf_conta");
			$dadosDespesa["SdEntSPD"]["Nominal"]      = pg_fetch_result($res, 0, "nome");
			
			$dadosDespesa["SdEntSPD"]["NotaFiscalNumero"]    = pg_fetch_result($res, 0, "nf_autorizacao");
			$dadosDespesa["SdEntSPD"]["NotaFiscalSerie"]     = pg_fetch_result($res, 0, "serie_nf");
			$dadosDespesa["SdEntSPD"]["DataEmissao"]         = pg_fetch_result($res, 0, "data_nf");
			$dadosDespesa["SdEntSPD"]["FormaPagamento"]      = 2; /* 1 - Cheque | 2 - Depósito | 3 - DOC | 4 - Dinheiro | 5 - Boleto */ 
			$dadosDespesa["SdEntSPD"]["TipoDespesa"] = 2;
			if (!empty($codigo)) {
				$dadosDespesa["SdEntSPD"]["ReferenciaExterna"] = $codigo; // Envio do código agrupado HD-7240408
			} else {
				$dadosDespesa["SdEntSPD"]["ReferenciaExterna"] = $extrato;
			}
			$dadosDespesa["SdEntSPD"]["Observacao"] = utf8_encode(pg_fetch_result($res, 0, "obs_conta"));

			$dadosDespesa["SdEntSPD"]["Parcela"] = array(
				array(
					"DataVencimento"  => pg_fetch_result($res, 0, "previsao_pagamento"),
					"ValorVencimento" => $valor
				)
			);

			/*

			Dados Mondial

			*** Recompra ***
			Grupo financeiro: 20602
			Produto: 40010028
			Conta Contabil:311220411
			Centro de Custo:51621

			*** Mão de Obra ***
			Grupo financeiro: 20602
			Produto: 40010028
			Conta Contabil:311220401
			Centro de Custo:51621

			*/

			$dadosDespesa["SdEntSPD"]["Item"] = array(
				array(
					"CentroCusto"         => "51624",
					"ProdutoCodigo"       => "40010028", /* Verificar com a Mondial */
					"ContaContabilCodigo" => "311220401", /* Verificar com a Mondial */
					"Quantidade"          => "1",
					"ValorUnitario"       => $valor,
					"FinalidadeCodigo"    => 11
				)
			);

		}

		return $dadosDespesa;

	}


		public function getKey($servidor = null){

			global $con;

			if(strlen($servidor) == 0){
				$servidor = "mk_nordeste";
			}

			$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$this->_fabrica}";
			$res = pg_query($con, $sql);

			$dados = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
			return $dados["dados_api_send"][$servidor]['chave_seguranca_send'];

        	}

	/* Teste de inclusão de arquivo */
	public function run(){

		return "Classe iniciada com Sucesso!";

	}

}

?>
