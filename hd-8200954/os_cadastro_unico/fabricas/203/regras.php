<?php

	$regras["os|data_abertura"]["obrigatorio"]      = true;
	$regras["os|tipo_atendimento"]["obrigatorio"] 	= true;
	$regras["os|defeito_reclamado"]["obrigatorio"]  = true;
	$regras["os|aparencia_produto"]["obrigatorio"]  = true;
	$regras["os|acessorios"]["obrigatorio"]      	= true;
	$regras["os|os_contato"]["obrigatorio"]      	= true;
	$regras["consumidor|nome"]["obrigatorio"]      	= true;
	$regras["consumidor|cep"]["obrigatorio"]      	= true;
	$regras["consumidor|cpf"]["obrigatorio"]      	= true;
	$regras["consumidor|bairro"]["obrigatorio"]     = true;
	$regras["consumidor|endereco"]["obrigatorio"]   = true;
	$regras["consumidor|numero"]["obrigatorio"]   	= true;
	$regras["consumidor|email"]["obrigatorio"]   	= true;
	//$regras["produto|contador"]["obrigatorio"]   	= true;
	
	$valida_anexo_boxuploader = "valida_anexo_boxuploader";
	
	$regras["consumidor|telefone"] = array(
	    "obrigatorio" => false,
	    "function" => array("valida_consumidor_contato")
	);

	$regras["os|status_orcamento"] = array(
	    "obrigatorio" => false,
	    "function" => array("valida_status_orcamento")
	);

	if (getValue("os[valida_atendimento]") == "Orçamento") {
		$regras["revenda|nome"]["obrigatorio"]      	= false;
		$regras["revenda|cnpj"]["obrigatorio"]   		= false;
		$regras["revenda|cidade"]["obrigatorio"]   		= false;
		$regras["revenda|estado"]["obrigatorio"]   		= false;
		$regras["os|nota_fiscal"]["obrigatorio"]   		= false;
		$regras["os|data_compra"]["obrigatorio"]   		= false;
		$regras["os|defeito_reclamado"]["obrigatorio"]  = false;
		$regras_pecas["servico_realizado"] 				= false;
	} else {
/*		$regras["produto|serie"] = array(
			"obrigatorio" => true,
		);*/

		$regras["produto|referencia"] = array(
			"function" => array("valida_contador")
		);
	}

	$posto = (strlen($_REQUEST["posto"]["id"]) > 0) ? $_REQUEST["posto"]["id"] : $login_posto;
	if (posto_interno($posto) == true) {
		if (getValue("os[valida_atendimento]") != "Orçamento") { 
			$auditorias = array(
			    "auditoria_defeito_constatado",
			    "auditoria_numero_serie"
			);
		}
	} else {
		$auditorias = array(
		    "auditoria_peca_critica",
		    "auditoria_troca_obrigatoria",
		    "auditoria_pecas_excedentes",
		    "auditoria_km",
		    "auditoria_os_reincidente_brother",
		    "auditoria_os_reincidente_brother_1",
		    "auditoria_defeito_constatado",
		    "auditoria_suprimento",
		    "auditoria_valores_adicionais",
		    "auditoria_numero_serie",
		    "auditoria_peca_lancada_brother"
		);
	}
	

	$id_orcamento = 0;
	/* Resgata o ID do Tipo de Atendimento Orçamento */
    function id_tipo_atendimento_orcamento(){
    	global $con, $login_fabrica;

        $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao = 'Orçamento'";
        $res = pg_query($con, $sql);

        return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, "tipo_atendimento") : 0;

    }

    function auditoria_km(){
	    global $login_fabrica, $campos, $os, $con, $login_admin;

	    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
	    $auditoria_status = 2;
	    $qtde_km          = $campos["os"]["qtde_km"];
	    $qtde_km_hidden   = $campos["os"]["qtde_km_hidden"];

	    $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento";
	    $res = pg_query($con, $sql);
	    if(strlen(trim(pg_last_error($con)))>0){
	    	$msg_erro = "Erro ao encontrar tipo atendimento - Auditoria de KM";
	    }

	    if(pg_num_rows($res)>0){
	    	$descricao = pg_fetch_result($res, 0, 'descricao');

	    	if($descricao == "Garantia com Deslocamento"){
	        	$sql_update = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";

	      		$res_update = pg_query($con, $sql_update);
	        	if(pg_num_rows($res_update) ==0 or ($qtde_km != $qtde_km_hidden)){

	            	if($qtde_km != $qtde_km_hidden){
	                	$observacao = "Auditoria de Km - $qtde_km - Km Alterado Manualmente";
		            }else{
		                $observacao = "Auditoria de Km - $qtde_km";
		            }

	              	$sql_insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria_status, '$observacao')";
	              	$res_insert = pg_query($con, $sql_insert);
	              	if(strlen(trim(pg_last_error($con)))>0){
	                    $msg_erro = "Erro gravar auditoria de KM - Auditoria de KM";
	              	}
	          	}
	      	}
	    }
	}

	function auditoria_os_reincidente_brother() {
		global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

		if(strlen(trim($campos['produto']['serie'])) > 0){

			$sql = "SELECT garantia, parametros_adicionais
						FROM tbl_produto
					INNER JOIN tbl_os on tbl_os.produto = tbl_produto.produto
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_produto.fabrica_i = {$login_fabrica}
					AND tbl_os.os = {$os}";
			$res = pg_query($con,$sql);

			$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);

			$suprimento = $parametros_adicionais['suprimento'];

			if ($suprimento == true) {
				$garantia = 90;
			} else {

				if(strlen(pg_num_rows($res))){
					$garantia = pg_fetch_result($res, 0, "garantia");
				}else{
					$garantia = 0;
				}

			}

			$sql = "SELECT os,data_nf FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$data_nf = pg_fetch_result($res,0,'data_nf');
				if($campos["os"]["valida_atendimento"] == "Orçamento"){
					$cond = " AND (tbl_os.data_abertura < CURRENT_DATE - INTERVAL '90 days')";
				}else{
					$cond = " AND tbl_os.data_abertura > '$data_nf' 
						  AND tbl_os.data_abertura < ('$data_nf'::date + INTERVAL '".$garantia." months') ";
				}

				$select = "SELECT tbl_os.os
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
						WHERE tbl_os.fabrica = {$login_fabrica}
						$cond
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.posto = {$campos['posto']['id']}
						AND tbl_os.os < {$os}
						AND tbl_os.serie = '{$campos['produto']['serie']}'
						ORDER BY tbl_os.data_abertura DESC
						LIMIT 1";
				$resSelect = pg_query($con, $select);

				if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
					$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

					$busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

					if($busca['resultado']){
						$auditoria_status = $busca['auditoria'];
					}
					$observacao = "OS Reincidente com mesmo número de série, OS reincidente: ".$os_reincidente_numero;
		            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
		                    ({$os}, $auditoria_status, '$observacao')";
		            $res = pg_query($con, $sql);

		            if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço #4");
					} else {
						$os_reincidente = true;
					}
				}
			}
		}
	}

	function auditoria_os_reincidente_brother_1() {
		global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

		$fabrica1 = 167;

		if(strlen(trim($campos['produto']['serie'])) > 0){

			$sql = "SELECT garantia, parametros_adicionais
				FROM tbl_produto
				INNER JOIN tbl_os on tbl_os.produto = tbl_produto.produto
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_produto.fabrica_i = {$login_fabrica}
				AND tbl_os.os = {$os}";
			$res = pg_query($con,$sql);

			$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);

			$suprimento = $parametros_adicionais['suprimento'];

			if ($suprimento == true) {
				$garantia = 90;
				} else {

			if(strlen(pg_num_rows($res))){
				$garantia = pg_fetch_result($res, 0, "garantia");
				}else{                                                                                                    
					$garantia = 0;
					} 
			}

			$sql = "SELECT os,data_nf FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$data_nf = pg_fetch_result($res,0,'data_nf');

				if($campos["os"]["valida_atendimento"] == "Orçamento"){
					$cond = " AND (tbl_os.data_abertura < CURRENT_DATE - INTERVAL '90 days')";
					}else{
						$cond = " AND tbl_os.data_abertura > '$data_nf' 
							AND tbl_os.data_abertura < ('$data_nf'::date + INTERVAL '".$garantia." months') ";
						}

					$select = "SELECT tbl_os.os
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
						WHERE tbl_os.fabrica = {$fabrica1}
						$cond
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.os < {$os}
						AND tbl_os.serie = '{$campos['produto']['serie']}'
						ORDER BY tbl_os.data_abertura DESC
						LIMIT 1";
					$resSelect = pg_query($con, $select);
												      
				if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os)      === true) {
					$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

					$busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

					if($busca['resultado']){
						$auditoria_status = $busca['auditoria'];
					}

					$observacao = "OS Reincidente com mesmo número de série da Fábrica 1, OS reincidente: ".$os_reincidente_numero;
					$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
						({$os}, $auditoria_status, '$observacao')";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço #4");
					} else {
						$os_reincidente = true;
					}
				}
			}
		}
	}

	function auditoria_defeito_constatado(){
		global $con, $login_fabrica, $os, $campos, $login_admin;

		if(strlen(trim($campos['produto']['defeito_constatado'])) > 0){
			$auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

			if($auditoria['resultado']){
				$auditoria_status = $auditoria['auditoria'];
			}

			$auditoria = false;

			$sql = "
				SELECT tbl_auditoria_os.auditoria_os,
					tbl_auditoria_os.liberada,
					tbl_auditoria_os.reprovada,
					tbl_os.defeito_constatado
				FROM tbl_auditoria_os
				INNER JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
				WHERE tbl_auditoria_os.os = {$os}
				AND tbl_auditoria_os.auditoria_status = $auditoria_status
				AND tbl_auditoria_os.observacao ILIKE '%Auditoria de Garantia%'
				AND tbl_auditoria_os.cancelada IS NULL
				ORDER BY tbl_auditoria_os.data_input DESC
				LIMIT 1
			";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$liberada           = pg_fetch_result($res, 0, "liberada");
				$reprovada          = pg_fetch_result($res, 0, "reprovada");
				$defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

				if(!empty($liberada) && ($defeito_constatado != $campos['produto']['defeito_constatado'])){
					$auditoria = true;
				} else if (!empty($reprovada) && verifica_peca_lancada(true) === true) {
					$auditoria = true;
				}
			}else{
				$auditoria = true;
			}

			if ($auditoria == true) {
				$sql = "INSERT INTO tbl_auditoria_os
						(os, auditoria_status, observacao, admin)
						VALUES
						({$os}, $auditoria_status, 'Auditoria de Garantia',$login_admin)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço #1");
				}
			}
		}
	}

	function auditoria_suprimento(){
		global $con, $login_fabrica, $os, $campos, $login_admin;

		$id_produto = $campos["produto"]["id"];

		$sql = "SELECT parametros_adicionais FROM tbl_produto WHERE produto = {$id_produto} AND fabrica_i = $login_fabrica";
		$res = pg_query($con, $sql);

		$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);

		$suprimento = $parametros_adicionais['suprimento'];

		if($suprimento == true){
			$auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");
			if($auditoria['resultado']){
				$auditoria_status = $auditoria['auditoria'];
			}

			$auditoria = false;

			$sql = "SELECT tbl_auditoria_os.auditoria_os,
					tbl_auditoria_os.liberada,
					tbl_os.defeito_constatado
				FROM tbl_auditoria_os
				INNER JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
				WHERE tbl_auditoria_os.os = {$os}
				AND tbl_auditoria_os.auditoria_status = $auditoria_status
				AND tbl_auditoria_os.observacao ILIKE '%Auditoria de Suprimento%'";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$liberada           = pg_fetch_result($res, 0, "liberada");
				$defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

				if(!empty($liberada) && ($defeito_constatado != $campos['produto']['defeito_constatado'])){
					$auditoria = true;
				}
			}else{
				$auditoria = true;
			}

			if ($auditoria == true) {
				$sql = "INSERT INTO tbl_auditoria_os
						(os, auditoria_status, observacao, admin)
						VALUES
						({$os}, $auditoria_status, 'Auditoria de Suprimento',$login_admin)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço #2");
				}
			}
		}
	}

	function auditoria_valores_adicionais() {
		global $con, $os, $campos;

		if(count($campos["os"]["valor_adicional"]) > 0){

			foreach ($campos["os"]["valor_adicional"] as $key => $value) {
				list($chave,$valor) = explode("|", $value);
				$valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);
			}

			$valores = json_encode($valores);

			$valores = str_replace("\\", "\\\\", $valores);

			grava_valor_adicional($valores,$os);

			if (verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%valores adicionais%'", $os) === true) {
				$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

				if($busca['resultado']){
					$auditoria_status = $busca['auditoria'];
				}

				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
	                    ({$os}, $auditoria_status, 'OS em auditoria de Valores Adicionais', false)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço #3");
				}
			}
		}
	}

	/* Verifica se o posto é do tipo Posto */
    function posto_interno($posto_param = ""){
        global $con, $login_fabrica, $campos, $login_posto;

        if(strlen($posto_param) > 0){
            $posto = $posto_param;
        }else{
            $posto = (strlen($campos["posto"]["id"]) > 0) ? $campos["posto"]["id"] : $login_posto;
        }

        $sql = "SELECT
                    tbl_tipo_posto.posto_interno
                FROM tbl_posto_fabrica
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                WHERE
                    tbl_posto_fabrica.fabrica = {$login_fabrica}
                    AND tbl_posto_fabrica.posto = {$posto}";
        $res = pg_query($con, $sql);

        $posto_interno = pg_fetch_result($res, 0, "posto_interno");

        return ($posto_interno == "t") ? true : false;

    }

	function valida_consumidor_contato() {
	    global $campos, $msg_erro, $con, $login_fabrica;

	    $tipo_os = $campos["os"]["consumidor_revenda"];
	    $posto = $campos["posto"]["id"];
	    $email = $campos["consumidor"]["email"];

	    if ($tipo_os == "C") {
	        $telefone = trim($campos["consumidor"]["telefone"]);
	        $celular  = trim($campos["consumidor"]["celular"]);

	        $telefone = preg_replace("/[^0-9]/", "",$telefone);
	        $celular = preg_replace("/[^0-9]/", "",$celular);

	        if (empty($telefone) && empty($celular)) {
	            $msg_erro["msg"][]    = "É obrigatório informar Telefone ou Celular";
	            $msg_erro["campos"][] = "consumidor[telefone]";
	            $msg_erro["campos"][] = "consumidor[celular]";
	        }
	    }

	    $sql = "SELECT contato_email, contato_fone_comercial, contato_cel, contato_fax FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

        	$email_posto = pg_fetch_result($res, 0, 'contato_email');
        	$fone_posto = pg_fetch_result($res, 0, 'contato_fone_comercial');
        	$fax_posto = pg_fetch_result($res, 0, 'contato_fax');
        	$celular_posto = pg_fetch_result($res, 0, 'contato_cel');

        	$fone_posto = preg_replace("/[^0-9]/", "",$fone_posto);
        	$fax_posto = preg_replace("/[^0-9]/", "",$fax_posto);
        	$celular_posto = preg_replace("/[^0-9]/", "",$celular_posto);

        	if(strlen(trim($telefone)) > 0){
        		if($telefone == $fone_posto OR $telefone == $fax_posto){
	        		$msg_erro["msg"][]    = "Telefone inválido";
	            	$msg_erro["campos"][] = "consumidor[telefone]";
	            }
        	}

        	if(strlen(trim($celular)) > 0){
        		if($celular == $fone_posto OR $celular == $fax_posto){
	        		$msg_erro["msg"][]    = "Celular inválido";
		            $msg_erro["campos"][] = "consumidor[celular]";
				}
        	}

        	if(strlen(trim($celular)) > 0){
        		if($celular == $celular_posto OR $celular == $fax_posto OR $celular == $fone_posto){
					$msg_erro["msg"][]    = "Celular inválido";
		            $msg_erro["campos"][] = "consumidor[celular]";
				}
        	}

        	if(strlen(trim($email)) > 0){
				if($email == $email_posto){
	        		$msg_erro["msg"][]    = "Email inválido.";
		            $msg_erro["campos"][] = "consumidor[email]";
	        	}
			}
        }
	}

	function valida_status_orcamento() {
	    global $campos, $msg_erro, $con, $login_fabrica;


	    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
	    $status_orcamento = $campos["os"]["status_orcamento"];

		$sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento ";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res, 0, 'descricao');

			if($descricao == "Orçamento" AND strlen(trim($status_orcamento)) == 0){
				$msg_erro["msg"][]    = "É obrigatório informar status do Orçamento";
	            $msg_erro["campos"][] = "os[status_orcamento]";
			}
		}
	}

	function valida_cep() {
	    global $campos, $con;

	    $cep = $campos["consumidor"]["cep"];

	    if (strlen($cep) > 0) {
	        $cep = preg_replace("/\D/", "", $cep);

	        $sql = "SELECT cep FROM tbl_cep WHERE cep = '{$cep}'";
	        $res = pg_query($con, $sql);

	        if (!pg_num_rows($res)) {
	            throw new Exception("CEP Inválido");
	        }
	    }
	}

	function auditoria_numero_serie() {

	    global $con, $campos, $login_fabrica, $os;

	    $produto = $campos['produto']['id'];
		$serie   = $campos['produto']['serie'];
		$posto   = $campos['posto']['id'];

		if (!empty($produto) && !empty($serie) AND strlen($campos['produto']['defeito_constatado']) > 0) {

			$sql = "SELECT produto
					FROM tbl_numero_serie
					WHERE produto = {$produto}
					AND   serie = '$serie'
					AND   fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) == 0 ) {
				if (verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND tbl_auditoria_os.observacao ILIKE '%OS em Auditoria de Número de Série%'", $os)) {
				 
					$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
	                        ({$os}, 5, 'OS em Auditoria de Número de Série')";
	            	$res = pg_query($con, $sql);
				}
			}
	    }
	}


	/**
	 * Função para validar anexo
	 */

	function valida_contador(){
		global $campos, $con, $login_fabrica, $msg_erro;
		$produto = $campos["produto"]["id"];
		$contador = $campos["produto"]["contador"];

		$sql = "SELECT 
					tbl_familia.descricao 
				FROM tbl_produto 
				INNER JOIN tbl_familia USING(familia) 
				WHERE 
					tbl_produto.produto = $produto 
					AND tbl_produto.fabrica_i = $login_fabrica";
		$res = pg_query($con, $sql);

		$familia_nome = pg_fetch_result($res, 0, 'descricao');

		if(strstr($familia_nome, "Impressora") == true OR strstr($familia_nome, "Multifunciona") == true){
			if(strlen(trim($contador)) == 0 AND strlen($campos['produto']['defeito_constatado']) > 0){
				$msg_erro["msg"][] = traduz("É obrigatório o preencher o contador");
				$msg_erro["campos"][] = "produto[contador]";
			}
		}

		$sqlLinha = "SELECT tbl_linha.codigo_linha
	                  FROM tbl_produto
	                  JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
	                  JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
	                  WHERE tbl_produto.produto = ".getValue("produto[id]");
	    $resLinha = pg_query($con, $sqlLinha);

	    if (pg_num_rows($resLinha) > 0) {
	        $xCodigoLinha        = pg_fetch_result($resLinha, 0, 'codigo_linha');
	        
	        if (strlen(trim($contador)) == 0 AND strlen($campos['produto']['defeito_constatado']) > 0 AND in_array($xCodigoLinha, ['2','02'])) {
	            $msg_erro["msg"][] = traduz("É obrigatório o preencher o contador");
	            $msg_erro["campos"][] = "produto[contador]";
	        }
	    }	
	}

	function valida_anexo_brother() {
		global $campos, $msg_erro, $con, $login_fabrica, $anexos_obrigatorios;

		$produto      		= $campos["produto"]["id"];
		$valida_atendimento = $campos["os"]["valida_atendimento"];

		foreach ($campos["anexo"] as $key => $value) {
			if (strlen($value) > 0) {
				$count_anexo[] = "ok";
			}
		}

		if($valida_atendimento == "Orçamento"){
			$anexos_obrigatorios = "";
		}

		$sql = "SELECT 
					tbl_familia.descricao 
				FROM tbl_produto 
				INNER JOIN tbl_familia USING(familia) 
				WHERE 
					tbl_produto.produto = $produto 
					AND tbl_produto.fabrica_i = $login_fabrica";
		$res = pg_query($con, $sql);

		$familia_nome = pg_fetch_result($res, 0, 'descricao');

		if((!count($count_anexo) && $valida_atendimento != "Orçamento" && strstr($familia_nome, "Impressora") == true || (strstr($familia_nome, "Multifuncional") == true) && !posto_interno($posto)) && strlen($campos['produto']['defeito_constatado']) > 0){
			//$msg_erro["msg"][] = traduz("Os anexos são obrigatórios");
			$anexos_obrigatorios[] = "contador";
		}

		if((strstr($familia_nome, "Impressora") == true || strstr($familia_nome, "Multifuncional") == true) && $valida_atendimento != "Orçamento"){
			if(count($count_anexo) <= 1 AND strlen($campos['produto']['defeito_constatado']) > 0 AND !in_array($login_fabrica, array(203))){
				$msg_erro["msg"][] = traduz("É obrigatório o anexo da nota fiscal e do contador");		
			}
		}
	}

	$valida_anexo = "valida_anexo_brother";

	/* Grava OS Fábrica */
	function grava_os_fabrica(){

	    global $campos, $con;

	    $campos_bd = array();

	    $descricao_status_orcamento = $campos["os"]["status_orcamento"];

	    $sql_status = "SELECT status_os FROM tbl_status_os WHERE UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais(trim('{$descricao_status_orcamento}')))";
		$res_status = pg_query($con, $sql_status);

		if(pg_num_rows($res_status) > 0){
			$id_status_os = pg_fetch_result($res_status, 0, 'status_os');
			$campos_bd["status_os_ultimo"] = $id_status_os;
		}

		if(strlen($campos["os"]["os_contato"]) > 0){
	    	$os_contato = "'".$campos["os"]["os_contato"]."'";
	    	$campos_bd["consumidor_nome_assinatura"] = $os_contato;
	    }

	    if(strlen($campos["produto"]["contador"]) > 0){
	    	$campos_bd["condicao"] = $campos["produto"]["contador"];
	    }

	    if(strlen($campos["os"]["defeito_reclamado"]) > 0){
	        $campos_bd["defeito_reclamado"] = $campos["os"]["defeito_reclamado"];
	    }

	    return $campos_bd;
	}

	/* Grava Nova OS */
	function prepareData($values, $columns) {
        $arr = array();

        foreach ($columns as $c => $t) {
            $v = $values[$c];

            switch ($t) {
                case "numeric":
                    $v = (!strlen($v)) ? "null" : $v;
                    break;

                case "string":
                    $v = utf8_decode("E'{$v}'");
                    break;

                case "boolean":
                    $v = ($v == "t") ? "true" : "false";
                    break;

                case "date":
                    $v = (!strlen($v)) ? "null" : "'{$v}'";
                    break;
            }

            $arr[$c] = $v;
        }

        return $arr;
    }
	function getColumns($table) {
        global $con;

        $sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '{$table}'";
        $res = pg_query($con, $sql);

        $columns = array();

        foreach (pg_fetch_all($res) as $i => $column) {
            switch ($column["data_type"]) {
                case 'character':
                case 'text':
                case 'character varying':
                    $type = "string";
                    break;

                case 'double precision':
                case 'integer':
                    $type = "numeric";
                    break;

                case 'date':
                case 'timestamp without time zone':
                    $type = "date";
                    break;

                case 'boolean':
                    $type = "boolean";
                    break;
            }

            $columns[$column["column_name"]] = $type;
        }

        return $columns;
    }

	function grava_nova_os(){
		global $campos, $msg_erro, $con, $login_fabrica, $os, $nova_os_id, $valida_anexo;

		$id_tipo_atendimento = $campos["os"]["tipo_atendimento"];
		$os_antiga = $os;

		$sql = "SELECT descricao, tipo_atendimento FROM tbl_tipo_atendimento WHERE tipo_atendimento = $id_tipo_atendimento";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res, 0, 'descricao');
			$valida_anexo = "";

			if($descricao ==  "Garantia Recusada"){
				$sql_os = "SELECT * FROM tbl_os WHERE tbl_os.os = {$os} and fabrica = {$login_fabrica} LIMIT 1";
				$res_os = pg_query($con,$sql_os);

				$columns = getColumns("tbl_os");

				while ($row = pg_fetch_array($res_os)) {
					$data = prepareData($row, $columns);

					foreach ($data as $key => $value) {
						if(empty($value) OR $value == "E''"){
							unset($key);
						}else{
							if($key  ==  "os"){
								unset($key);
							}else{
								$campos_insert[] = $key;
								$campos_value[] = $value;
							}
						}
					}
					$sql_Os = "INSERT INTO tbl_os
		                    (".implode(", ", $campos_insert).")
		                    VALUES
		                    (".implode(", ", $campos_value).")
		                	RETURNING os";
		            $res_OS = pg_query($con, $sql_Os);
		           	$nova_os_id = pg_fetch_result($res_OS, 0, "os");
		        }

		        $sql = "SELECT produto, defeito_constatado, serie, mao_de_obra FROM tbl_os_produto where os = {$os_antiga}";
		        $res = pg_query($con, $sql);

		        if(pg_num_rows($res) > 0){
		        	$id_produto_os = pg_fetch_result($res, 0, 'produto');
		        	$n_serie = pg_fetch_result($res, 0, 'serie');
		        	$m_obra = pg_fetch_result($res, 0, 'mao_de_obra');
		        	$id_defeito_constatado_os = pg_fetch_result($res, 0, 'defeito_constatado');

					if(!empty($id_defeito_constatado_os)) {
						$campo_dc = ",defeito_constatado";
						$valor_dc = ", $id_defeito_constatado_os ";
					}
		        	$sql_insert = "INSERT INTO tbl_os_produto (os, produto, serie, mao_de_obra $campo_dc )VALUES({$nova_os_id},{$id_produto_os},'{$n_serie}',{$m_obra} $valor_dc)";
		        	$res_insert = pg_query($con, $sql_insert);
		        }

		        $sql_at = "SELECT tipo_atendimento FROM tbl_tipo_atendimento  WHERE fabrica = $login_fabrica AND descricao = 'Orçamento' ";
				$res_at = pg_query($con, $sql_at);

				$novo_at = pg_fetch_result($res_at, 0, 'tipo_atendimento');

				if(!empty($novo_at)) {
					$sql_os_up = "UPDATE tbl_os SET tipo_atendimento = {$novo_at}, os_numero = {$os_antiga}, sua_os = '{$nova_os_id}' WHERE os = $nova_os_id AND fabrica = $login_fabrica";
					$res_os_up = pg_query($con, $sql_os_up);
				}
				$sql_up = "UPDATE tbl_os SET os_numero = {$nova_os_id} WHERE os = {$os_antiga} AND fabrica = $login_fabrica";
				$res_up = pg_query($con, $sql_up);
			}
		}
	}

	$funcoes_fabrica = array("grava_nova_os","verifica_estoque_peca");

	/* Grava OS Item */
	$grava_os_item_function = "grava_os_item_brother";
	function grava_os_item_brother($os_produto, $subproduto = "produto_pecas") {

		global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

		$id_peca = $campos['produto']['id'];

		$sql_produto_suprimento = "SELECT tbl_produto.parametros_adicionais FROM tbl_produto WHERE produto = $id_peca AND fabrica_i = $login_fabrica";
		$res_suprimento = pg_query($con, $sql_produto_suprimento);

		$suprimento = pg_fetch_result($res_suprimento, 0, parametros_adicionais);
		$suprimento = json_decode($suprimento, true);
		$suprimento = $suprimento['suprimento'];


		$tipo_atendimento = $campos["os"]["tipo_atendimento"];

		$sql_aud = "
			SELECT descricao
			FROM tbl_tipo_atendimento
			WHERE fabrica = $login_fabrica
			AND tipo_atendimento = $tipo_atendimento
		";
		$res_aud = pg_query($con, $sql_aud);

		if(pg_num_rows($res_aud) > 0 && pg_fetch_result($res_aud, 0, "descricao") == "Garantia Recusada"){
			$bloqueia_pecas = true;
		}

		if($bloqueia_pecas == false){
			if(empty($suprimento) OR $suprimento <> "t"){
				if (function_exists("grava_custo_peca") ) {
					/**
					 * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
					 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
					 */
					$custo_peca = grava_custo_peca();
					if($custo_peca==false){
						unset($custo_peca);
					}
				}

				if($historico_alteracao === true){
					$historico = array();
				}
				unset($campos[$subproduto]['__modelo__']);

				foreach ($campos[$subproduto] as $posicao => $campos_peca) {

					if (strlen($campos_peca["id"]) > 0) {

						if($historico_alteracao === true){
							include "$login_fabrica/historico_alteracao.php";
						}

						if (!empty($campos_peca['servico_realizado'])) {
							$sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
							$res = pg_query($con, $sql);

							$troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");
						}

						if ($troca_de_peca == "t") {
							$sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$campos_peca['id']}";
							$res = pg_query($con, $sql);

							$devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

							if ($devolucao_obrigatoria == "t") {
								$devolucao_obrigatoria = "TRUE";
							} else {
								$devolucao_obrigatoria = "FALSE";
							}
						} else {
							$devolucao_obrigatoria = "FALSE";

							if (empty($campos_peca['servico_realizado'])) {
								$campos_peca['servico_realizado'] = (empty($campos_peca['servico_realizado'])) ? "null" : $campos_peca['servico_realizado'];
							}

						}

						$login_admin = (empty($login_admin)) ? "null" : $login_admin;

						$campo_valor = $campos_peca['valor'];
	                    $campo_valor = str_replace(".", "", $campo_valor);
	                    $campo_valor = str_replace(",", ".", $campo_valor);

						$campos_peca['valor'] = $campo_valor;

						if (empty($campos_peca["os_item"])) {
							$sql = "INSERT INTO tbl_os_item
									(
										os_produto,
										peca,
										qtde,
										servico_realizado,
										peca_obrigatoria,
										admin
										".(($grava_defeito_peca == true) ? ", defeito" : "")."
										".((strlen(trim($campos_peca['serie_peca'])) > 0) ? ", peca_serie" : "")."
										".((strlen(trim($campos_peca['valor'])) > 0) ? ", preco" : "")."
									)
									VALUES
									(
										{$os_produto},
										{$campos_peca['id']},
										{$campos_peca['qtde']},
										{$campos_peca['servico_realizado']},
										{$devolucao_obrigatoria},
										{$login_admin}
										".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "")."
										".((strlen(trim($campos_peca['serie_peca'])) > 0) ? ", '".$campos_peca['serie_peca']."'" : "")."
										".((strlen(trim($campos_peca['valor'])) > 0) ? ", ".$campos_peca['valor'] : "")."
									)
									RETURNING os_item";

							$acao = "insert";
							$res = pg_query($con, $sql);
							if (strlen(pg_last_error()) > 0) {
								throw new Exception("Erro ao gravar Ordem de Serviço #9".$sql);
							}

							$campos[$subproduto][$posicao]["os_item_insert"] = pg_fetch_result($res, 0, "os_item");

							if ($campos_peca['motivo_segunda_peca'] != "") {
								$id_os_item = pg_fetch_result($res, 0, "os_item");

								$sql_atualiza = "SELECT parametros_adicionais FROM tbl_os_item WHERE os_item = $id_os_item";
								$res_atualiza = pg_query($con, $sql_atualiza);
								if (pg_num_rows($res_atualiza) > 0) {
									$pp = json_decode(pg_fetch_result($res_atualiza, 0, 'parametros_adicionais'), 1);
									$pp["pecaReenviada"] = true;
								} else {
									$pp["pecaReenviada"] = true;
								}

							    $sqlUP = "UPDATE tbl_os_item 
							               SET obs='".str_replace(["'", '"'], "", $campos_peca["motivo_segunda_motivo"])."', parametros_adicionais='".json_encode($pp)."'
							               WHERE os_item = $id_os_item";
							    $resUP = pg_query($con, $sqlUP);
							}

						} else {
							$wherePedido = " AND tbl_os_item.pedido IS NULL ";

							$qTipoAtendimento = "SELECT tipo_atendimento
												FROM tbl_tipo_atendimento
												WHERE fabrica = {$login_fabrica}
												AND descricao ILIKE 'Orçamento'";
							$rTipoAtendimento = pg_query($con, $qTipoAtendimento);
							$orcAtendimento   = pg_fetch_result($rTipoAtendimento, 0, 'tipo_atendimento');
							if ($orcAtendimento == $campos['os']['tipo_atendimento'])
								$wherePedido = "";

							$sql = "SELECT tbl_os_item.os_item
									FROM tbl_os_item
									LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
									WHERE tbl_os_item.os_produto = {$os_produto}
									AND tbl_os_item.os_item = {$campos_peca['os_item']}
									{$wherePedido}
									AND (UPPER(tbl_servico_realizado.descricao) NOT IN('CANCELADO', 'TROCA PRODUTO') OR tbl_os_item.servico_realizado isnull)";
							$res = pg_query($con, $sql);

							if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
								continue;
							}

							if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
								continue;
							}

							if (pg_num_rows($res) > 0) {
								if (strlen(trim($campos_peca["valor"])) > 0) {
									$campo_valor = ", preco = {$campos_peca['valor']}";
								} else {
									$campo_valor = "";
								}

								if ($grava_defeito_peca == true) {
									$campo_defeito = ", defeito = {$campos_peca['defeito_peca']}";
								} else {
									$campo_defeito = "";
								}
								
								if (strlen(trim($campos_peca["serie_peca"])) > 0) {
									$campo_serie = ", peca_serie = '{$campos_peca['serie_peca']}'";
								} else {
									$campo_serie = "";
								}

								$sql = "UPDATE tbl_os_item SET
											qtde = {$campos_peca['qtde']},
											servico_realizado = {$campos_peca['servico_realizado']}
											{$campo_valor}
											{$campo_defeito}
											{$campo_serie}
										WHERE os_produto = {$os_produto}
										AND os_item = {$campos_peca['os_item']}";
								$acao = "update";
								$res = pg_query($con, $sql);

								if (strlen(pg_last_error()) > 0) {
									throw new Exception("Erro ao gravar Ordem de Serviço #10");
								}
							}
						}
					}
				}

				if (!empty($objLog)) {//logositem
					$objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os_item", $login_fabrica."*".$os);
				}
				unset($objLog);

				if($historico_alteracao === true){

					if(count($historico) > 0){

						grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);

					}

				}
			}
		}
	}

    function grava_os_campo_extra_fabrica() {
	    global $campos;

	    $valor_adicional_peca_produto = $campos["os"]["valor_adicional_peca_produto"];
	    $produto_recebido_via_correios = (isset($campos["produto"]["recebido_via_correios"])) ? "t" : "f";

	    $return = array();
	    
	    if(strlen($valor_adicional_peca_produto) > 0){
	        $return["valor_adicional_peca_produto"] = $valor_adicional_peca_produto;
	    }

	    if (!empty($campos['consumidor']['nascimento'])) {
			$return['consumidor_nascimento'] = $campos['consumidor']['nascimento'];
		}	

	    $return['produto_recebido_via_correios'] = $produto_recebido_via_correios;

	    return $return;
	}

	$regras_pecas["serie_peca"] = true;

	$antes_valida_campos = "valida_serie_peca";

	function valida_serie_peca(){
		global $campos, $regras_pecas, $login_fabrica, $con;

		$tipo_atendimento = $campos["os"]["tipo_atendimento"];

		$sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento ";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res, 0, 'descricao');

			if($descricao == "Orçamento"){
				$regras_pecas["serie_peca"] = false;				
			}
		}
	}

	function valida_serie_obrigatoria () {
		global $campos, $msg_erro, $regras_pecas, $login_fabrica, $con;

		$produto_id = $campos["produto"]["id"];
		$produto_serie = $campos["produto"]["serie"];

		$sql = "SELECT produto
				FROM tbl_produto WHERE fabrica_i = $login_fabrica 
				AND produto = $produto_id 
				AND numero_serie_obrigatorio IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && empty($produto_serie)) {
			$msg_erro["msg"][] = traduz("Preencha os campos obrigatórios");
			$msg_erro["campos"][] = "produto[serie]";
		}
	}

	$valida_pecas = "valida_pecas_brother";

	/**
	 * Função que valida as peças do produto $regras_pecas
	 */
	function valida_pecas_brother($nome = "produto_pecas") {

	    global $con, $msg_erro, $login_fabrica, $regras_pecas, $regras_subproduto_pecas, $campos , $areaAdmin, $os;

	    if(verifica_peca_lancada(false) === true){

	        $pecas_os = array();

	        foreach ($campos[$nome] as $posicao => $campos_peca) {
	            $peca       = $campos_peca["id"];
	            $cancelada  = $campos_peca["cancelada"];
	            $pedido     = $campos_peca["pedido"];
	            $referencia = $campos_peca["referencia"];
				$servico_id = $campos_peca["servico_realizado"];

	            if (empty($peca)) {
	                continue;
	            }

	            if (!empty($peca) && empty($campos_peca["qtde"])) {
	                $msg_erro["msg"]["peca_qtde"] = traduz('informe.uma.quantidade.para.a.peca.%', null, null, $referencia);
	                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
	                continue;
	            }

	            if ($nome == "subproduto_pecas") {
	                $regra_validar = $regras_subproduto_pecas;
	            } else {
	                $regra_validar = $regras_pecas;
	            }

	            if(isset($campos_peca["defeito_peca"]) && empty($campos_peca["defeito_peca"])){
	                $msg_erro["msg"]["peca_qtde"] = traduz('favor.informar.o.defeito.da.peca.%', null, null, $referencia);
	                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
	                continue;
	            }

	            foreach ($regra_validar as $tipo_regra => $regra) {
	                switch ($tipo_regra) {
	                    case 'lista_basica':
	                        if ($nome == "subproduto_pecas") {
	                            $produto = $campos["subproduto"]["id"];
	                        } else {
	                            $produto = $campos["produto"]["id"];
	                        }

	                        $peca_qtde = $campos_peca["qtde"];

	                        if ($regra == true && !empty($produto)) {
	                            $sql = "SELECT qtde
	                                    FROM tbl_lista_basica
	                                    WHERE fabrica = {$login_fabrica}
	                                    AND produto = {$produto}
	                                    AND peca = {$peca}";
	                            $res = pg_query($con, $sql);

	                            if (!pg_num_rows($res)) {
	                                if(strlen(trim($pedido))>0){
	                                    continue;
	                                }
	                                $msg_erro["msg"][]    = traduz("Peça não consta na lista básica do produto");
	                                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
	                            } else {
	                                $lista_basica_qtde = pg_fetch_result($res, 0, "qtde");
	                                

	                                if(array_key_exists($peca, $pecas_os)){
	                                    $pecas_os[$peca]["qtde"] += $peca_qtde;
	                                }else{
	                                    $pecas_os[$peca]["qtde"] = $peca_qtde;
	                                }

	                                if($cancelada > 0){
	                                    $pecas_os[$peca]["qtde"] -= $cancelada;
	                                }


	                                $reenviaPeca = false;
									if(!empty($pedido)) {
										$sql = "SELECT tbl_os_item.obs, tbl_os_item.parametros_adicionais
												FROM tbl_os_item
												JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
												JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
												WHERE tbl_os.fabrica = {$login_fabrica} 
												AND tbl_os_item.pedido={$pedido}
												AND tbl_os_item.peca={$peca}";
										$res = pg_query($con, $sql);
										if (pg_num_rows($res) > 0) {
											$result = pg_fetch_all($res);
											foreach ($result as $ky => $val) {
												$parametrosAdd = json_decode($val['parametros_adicionais'],1);
												if (empty($parametrosAdd)) {
													continue;
												}
												if (isset($parametrosAdd["tevePecaReenviada"]) && $parametrosAdd["tevePecaReenviada"]) {
													$reenviaPeca = true;
													$pecas_os[$peca]["qtde"] -= 1;
													break;
												}
											}
										}
									}


	                                if ($pecas_os[$peca]["qtde"] > $lista_basica_qtde && !$reenviaPeca) {
	                                    $msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça maior que a permitida na lista básica");
	                                    $msg_erro["campos"][]                 = "{$nome}[{$posicao}]";
	                                }
	                            }
	                        }
	                        break;
	                    case 'servico_realizado':
	                        if ($regra === true && !empty($campos_peca["id"]) && empty($campos_peca["servico_realizado"])) {
	                            $msg_erro["msg"]["servico_realizado"] = traduz("Selecione o serviço da peça".$cont);
	                            $msg_erro["campos"][] = "{$nome}[{$posicao}]";
	                        }
	                        break;
	                    case 'serie_peca':
	                        if(strlen(trim($campos_peca['id'])) > 0 AND $regra === true){ //HD-3428297
	                            $sql_serie = "SELECT tbl_peca.peca FROM tbl_peca WHERE peca = {$campos_peca['id']} AND fabrica = {$login_fabrica} AND numero_serie_peca IS TRUE ";
	                            $res_serie = pg_query($con, $sql_serie);
	                            if(pg_num_rows($res_serie) > 0 AND strlen(trim($campos_peca["serie_peca"])) == 0){
	                                $msg_erro["msg"][] = traduz("Preencha a série da peça");
	                                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
	                            }
	                        }
							break;
						case 'bloqueada_garantia':
							if($areaAdmin === false) {
								if(strlen(trim($campos_peca['id'])) > 0){
									$sql_peca = "SELECT tbl_peca.peca FROM tbl_peca WHERE peca = {$campos_peca['id']} AND fabrica = {$login_fabrica} AND bloqueada_garantia ";
									$res_peca = pg_query($con, $sql_peca);
									if(!empty($servico_id)) {
										$sql_ge = "SELECT descricao FROM tbl_servico_realizado where servico_realizado = $servico_id and gera_pedido";
										$res_ge = pg_query($con, $sql_ge);
									}
									if(pg_num_rows($res_peca) > 0  and pg_num_rows($res_ge) > 0){
										$msg_erro["msg"][] = traduz("Peça bloqueada para garantia, entrar em contato com fabricante");
										$msg_erro["campos"][] = "{$nome}[{$posicao}]";
									}
								}
							}
							break;

	                }
	            }
	        }

	    }

	}

	function valida_qtde_lista_basica_brother(){

	global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;
	
		if($areaAdmin == false){

			$sql = "select tbl_os_produto.produto, tbl_peca.referencia, tbl_peca.descricao,
				    os_produto, e.peca,  e.admin , sum(qtde) as qtde
				from tbl_os_produto
				join tbl_os_item e using(os_produto)
				join tbl_peca on tbl_peca.peca = e.peca
				join tbl_servico_realizado on e.servico_realizado = tbl_servico_realizado.servico_realizado
				where os = $os and e.admin is null and tbl_servico_realizado.descricao <> 'Cancelado' and (json_field('tevePecaReenviada', e.parametros_adicionais) = 'false' or json_field('tevePecaReenviada', e.parametros_adicionais) IS NULL)
				group by
				tbl_os_produto.produto, tbl_peca.referencia, tbl_peca.descricao,
	                os_produto, e.peca,  e.admin , qtde, tbl_servico_realizado.servico_realizado ";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res)>0){
				for($i=0; $i<pg_num_rows($res); $i++ ){
					$peca 		= pg_fetch_result($res, $i, 'peca');
					$qtde   	= pg_fetch_result($res, $i, 'qtde');
					$descricao   	= pg_fetch_result($res, $i, 'descricao');
					$referencia   	= pg_fetch_result($res, $i, 'referencia');
					$produto   	= pg_fetch_result($res, $i, 'produto');

					$sql_lb = "SELECT qtde
								FROM tbl_lista_basica
								WHERE fabrica = {$login_fabrica}
								AND produto = {$produto}
								AND peca = {$peca}";
					$res_lb = pg_query($con, $sql_lb);
					if(pg_num_rows($res_lb)>0){
					 	$qtde_lb   	= pg_fetch_result($res_lb, 0, 'qtde');
					 	if($qtde > $qtde_lb){
					 		throw new Exception("Quantidade da peça  $referencia - $descricao  maior que a permitida na lista básica");
					 	}
					}else{
						throw new Exception("Peça $referencia - $descricao não consta na lista básica do produto");
					}
				}
			}
		}

	}
	$valida_qtde_lista_basica = "valida_qtde_lista_basica_brother";

	function auditoria_peca_lancada_brother() {
	    global $con, $os, $login_fabrica, $campos;

	    $tem_pedido = false;
	    foreach ($campos['produto_pecas'] as $xkey => $xvalue) {
	    	if (!empty($xvalue['pedido'])) {
	    		$tem_pedido = true;
	    	}
	    }

	    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

	    if (valida_fora_garantia($tipo_atendimento) == false) {

	        $posto_id = $campos["posto"]["id"];

	        if (verifica_peca_lancada(false)) {
	            $sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
	                    FROM tbl_os_item 
	                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
	                    /*INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE*/
	                    WHERE tbl_os_produto.os = {$os}
	                    AND JSON_FIELD('pecaReenviada', tbl_os_item.parametros_adicionais) = 'true'";
	            $res = pg_query($con, $sql);

	            if ((pg_num_rows($res) > 0 && pg_fetch_result($res, 0, "qtde_pecas") > 0) || $tem_pedido) {
	                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

	                if($busca['resultado']){
	                    $auditoria_status = $busca['auditoria'];
	                }

	                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%lançamento de peça%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%lançamento de peça%'")) {

	                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ({$os}, $auditoria_status, 'OS em auditoria de lançamento de peça.')";
	                    $res = pg_query($con, $sql);

	                    if (strlen(pg_last_error()) > 0) {
	                        throw new Exception("Erro ao lançar ordem de serviço");
	                    }
	                }
	            }        
	        }
	    }
	}

	function valida_fora_garantia($tipo_atendimento){
	    global $con, $login_fabrica;

	    $sql = "SELECT fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}; ";
	    $res = pg_query($con,$sql);
	    $fora_garantia = pg_fetch_result($res, 0, fora_garantia);

	    if ($fora_garantia == 't') {
	        return true;
	    } else {
	        return false;
	    }    
	}

	function valida_garantia_brother($boolean = false) {
		global $con, $login_fabrica, $campos, $msg_erro;

		$data_compra   = $campos["os"]["data_compra"];
		$data_abertura = $campos["os"]["data_abertura"];
		$produto       = $campos["produto"]["id"];

		if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
			$sql = "SELECT garantia, parametros_adicionais
					FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

				$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);
				$suprimento = $parametros_adicionais['suprimento'];

				if ($suprimento == true) {
					$garantia = 3;
				} else {
					$garantia = pg_fetch_result($res, 0, "garantia");
				}

				if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
					if ($boolean == false) {
						$msg_erro["msg"][] = traduz("Produto fora de garantia");
					} else {
						return false;
					}
				} else if ($boolean == true) {
					return true;
				}
			}
		}
	}

	$valida_garantia = "valida_garantia_brother";

	/* Insere o ID do tipo atendimento Orçamento para o posto interno */
    if(posto_interno() == true || $areaAdmin == true){
    	$id_orcamento = id_tipo_atendimento_orcamento();
    }

    $data_abertura_fixa = true;
?>
