<?php

	function anexa_nf_explodida($os){
		global $con, $login_fabrica, $login_posto;

		$sql = "SELECT tipo_atendimento from tbl_os where os=$os and fabrica=$login_fabrica";
		$res = pg_query($con,$sql);
		$tipo_atendimento = (pg_num_rows($res)>0) ? pg_fetch_result($res, 0, 0) : '';

		if ($tipo_atendimento == 12) {

			$sql_data = "SELECT data_hora_abertura FROM tbl_os where os=$os";
			$res_data = pg_query($con,$sql_data);

			if (pg_num_rows($res_data)>0) {
				$data_hora = pg_fetch_result($res_data, 0, 0);

				if(strlen($data_hora) > 0){
					$sql_os_anexo = "SELECT os FROM tbl_os where data_hora_abertura='$data_hora' and posto = $login_posto and fabrica = $login_fabrica and tipo_atendimento = $tipo_atendimento ";
					$res_os_anexo = pg_query($con,$sql_os_anexo);
					if (pg_num_rows($res_os_anexo)>0) {
						$json_campos_adicionais = array(
				              "os_anexo" => $os
			            );
						for ($u=0; $u < pg_num_rows($res_os_anexo); $u++) {
							$os_explodida_nf = pg_fetch_result($res_os_anexo, $u, 'os');

				            $select_campo_extra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os_explodida_nf}";
				            $res_campo_extra    = pg_query($con, $select_campo_extra);

				            if (pg_num_rows($res_campo_extra) > 0) {
				              $json_res_campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");

				              if (!empty($json_res_campos_adicionais)) {
				                $json_res_campos_adicionais = json_decode($json_res_campos_adicionais, true);
				                $json_campos_adicionais     = array_merge($json_res_campos_adicionais, $json_campos_adicionais);
				              }

				              $query_campo_extra = "UPDATE tbl_os_campo_extra
				                                    SET campos_adicionais = '".json_encode($json_campos_adicionais)."'
				                                    WHERE fabrica = {$login_fabrica}
				                                    AND os = {$os_explodida_nf}";
				            }else{

				              $query_campo_extra = "INSERT INTO tbl_os_campo_extra
				                                    (os, fabrica, campos_adicionais)
				                                    VALUES
				                                    ({$os_explodida_nf}, {$login_fabrica}, '".json_encode($json_campos_adicionais)."')";
				            }
				            $res_query = pg_query($con, $query_campo_extra);
						}
					}
				}
			}
		}

	}

	function finalizaExplodeOs($os, $data_fechamento){
		global $con, $login_fabrica, $login_posto;
		$sql = "SELECT '$data_fechamento' > CURRENT_TIMESTAMP AS data_maior";
		$res = @pg_query($con,$sql);
		$msg_erro   = pg_errormessage($con);

		if(strpos($msg_erro,"out of range")>0){
			$msg_erro = "A hora está incorreta";
		}

		if(strlen($msg_erro)==0) $data_maior = pg_result($res,0,data_maior);

		if($data_maior=="t"){
			$msg_erro = "A data do fechamento não pode ser maior que a data atual";
		}

		if (empty($msg_erro)) {
			$sql = "SELECT tipo_atendimento from tbl_os where os=$os and fabrica=$login_fabrica";
			$res = pg_query($con,$sql);
			$tipo_atendimento = (pg_num_rows($res)>0) ? pg_fetch_result($res, 0, 0) : '';

			if ($tipo_atendimento == 12) {

				$sql_hora_abertura = "SELECT data_hora_abertura FROM tbl_os where os=$os";
				$res_hora_abertura = pg_query($con,$sql_hora_abertura);

				if (pg_num_rows($res_hora_abertura)>0) {
					$data_hora_abertura = pg_fetch_result($res_hora_abertura, 0, 0);

					if(strlen($data_hora_abertura) > 0){
						$sql_os_explodida = "SELECT os FROM tbl_os where data_hora_abertura='$data_hora_abertura' and posto = $login_posto and fabrica = $login_fabrica and tipo_atendimento = $tipo_atendimento ";
						$res_os_explodida = pg_query($con,$sql_os_explodida);
						if (pg_num_rows($res_os_explodida)>0) {
							for ($u=0; $u < pg_num_rows($res_os_explodida); $u++) {
								$os_explodida = pg_fetch_result($res_os_explodida, $u, 'os');
								$sql = "UPDATE tbl_os set 	data_fechamento      = '$data_fechamento',
											  				data_hora_fechamento = '$data_fechamento'
										WHERE fabrica = $login_fabrica
										AND   os      = $os_explodida";

								$res = @pg_query($con,$sql);
								if (pg_last_error($con)) {
									$msg_erro = pg_last_error($con);
								}

								if (strlen ($msg_erro) == 0) {
									$sql = "SELECT fn_finaliza_os($os_explodida, $login_fabrica)";
									$res = @pg_query($con, $sql);
									if (pg_last_error($con)) {
										$msg_erro = pg_last_error($con);
									}
								}
							}
						}
					}
				}
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = @pg_query ($con,"COMMIT TRANSACTION");
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			$error = $msg_erro;
			$error = str_replace("ERROR:", '', $error);
		}
	}

	function explodirOSConsumidor($os){

	  global $con;
	  $sql = "SELECT os_item,
			  peca,
			  peca_causadora,
			  qtde,
			  defeito,
			  servico_realizado,
			  tbl_os_item.os_produto,
			  tbl_os_produto.produto,
			  tbl_os_produto.serie
		  FROM tbl_os_item
		  JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_produto.os = $os";
	  $res = pg_query($con,$sql);

	  if(pg_num_rows($res) > 0){

	    for($i = 0; $i < pg_num_rows($res); $i++){
			$os_item	 		= pg_result($res,$i,'os_item');
			$os_produto 			= pg_result($res,$i,'os_produto');
			$peca_id		 	= pg_result($res,$i,'peca');
			$peca_causadora  		= pg_result($res,$i,'peca_causadora');
			$qtde 				= pg_result($res,$i,'qtde');
			$defeito 			= pg_result($res,$i,'defeito');
			$servico 			= pg_result($res,$i,'servico_realizado');
			$produto_id			= pg_result($res,$i,'produto');
			$serie				= pg_result($res,$i,'serie');
			$serie = (empty($serie)) ? 'null' : $serie;
			$servico = (empty($serie)) ? 'null' : $servico;

			if($qtde > 1){

			  for($j = 1; $j < $qtde; $j++){

			    gravaNovaOs($os,$os_item,$peca_id,$peca_causadora,$defeito,$servico,$produto_id,$serie);

			  }
			}
	    }
	  }
	}


	function gravaNovaOs($os,$os_item,$peca_id,$peca_causadora,$defeito,$servico,$produto_id,$serie){

		global $con, $login_fabrica, $cook_idioma;
		$sql = "INSERT INTO tbl_os(
					fabrica,
					posto,
					data_abertura,
					consumidor_revenda,
					tipo_atendimento,
					tecnico,
					produto,
					serie,
					nota_fiscal,
					data_nf,
					prateleira_box,
					defeito_reclamado_descricao,
					aparencia_produto,
					acessorios,
					consumidor_cpf,
					consumidor_nome,
					consumidor_fone,
					consumidor_cep,
					consumidor_endereco,
					consumidor_numero,
					consumidor_complemento,
					consumidor_bairro,
					consumidor_cidade,
					consumidor_estado,
					consumidor_email,
					pedagio,
					qtde_km,
					defeito_reclamado,
					defeito_constatado,
					solucao_os,
					causa_defeito,
					promotor_treinamento,
					segmento_atuacao,
					revenda,
					revenda_cnpj,
					revenda_nome,
					revenda_fone,
					cod_ibge,
					obs,
					data_hora_abertura
				)

				(
				SELECT fabrica,
					posto,
					data_abertura,
					consumidor_revenda,
					tipo_atendimento,
					tecnico,
					produto,
					serie,
					nota_fiscal,
					data_nf,
					prateleira_box,
					defeito_reclamado_descricao,
					aparencia_produto,
					acessorios,
					consumidor_cpf,
					consumidor_nome,
					consumidor_fone,
					consumidor_cep,
					consumidor_endereco,
					consumidor_numero,
					consumidor_complemento,
					consumidor_bairro,
					consumidor_cidade,
					consumidor_estado,
					consumidor_email,
					pedagio,
					qtde_km,
					defeito_reclamado,
					defeito_constatado,
					solucao_os,
					causa_defeito,
					promotor_treinamento,
					segmento_atuacao,
					revenda,
					revenda_cnpj,
					revenda_nome,
					revenda_fone,
					cod_ibge,
					obs,
					data_hora_abertura
				FROM tbl_os
				WHERE os = $os
				)RETURNING os";
		$res = pg_query($con, $sql);
		$error = pg_last_error($con);
		$error = str_replace("ERROR:", '', $error);
		if (pg_last_error($con)) throw new Exception(traduz("falha.ao.cadastrar.nova.os", $con, $cook_idioma).':'.$error);

		$nova_os = pg_fetch_result($res, 0, "os");

		$sql = "INSERT INTO tbl_os_produto(
					os,
					produto,
					serie
				)VALUES(
					{$nova_os},
					{$produto_id},
					{$serie}
				)RETURNING os_produto;";
		$res = pg_query($con, $sql);

		if (pg_last_error($con)) throw new Exception(traduz("falha.ao.cadastrar.o.produto.na.os", $con, $cook_idioma));

		$os_produto = pg_fetch_result($res, 0, "os_produto");

		$peca_causadora 	= empty ($peca_causadora) ? 'null' : $peca_causadora;
		$defeito 			= empty ($defeito) ? 'null' : $defeito;
		$servico 			= empty ($servico) ? 'null' : $servico;

		$sql = "
			INSERT INTO tbl_os_item(
				os_produto,
				peca,
				peca_causadora,
				qtde,
				defeito,
				servico_realizado
			)VALUES(
				{$os_produto},
				{$peca_id},
				{$peca_causadora},
				1,
				{$defeito},
				{$servico}
			)RETURNING os_item;";
		$res = pg_query($con, $sql);
		$error = pg_last_error($con);
		$error = str_replace("ERROR:", '', $error);
		if (pg_last_error($con)) throw new Exception(traduz("falha.ao.cadastrar.o.item.na.os", $con, $cook_idioma).':'.$error);

		$nova_os_item = pg_fetch_result($res, 0, "os_item");

		$res = pg_query($con, "SELECT fn_valida_os($nova_os, $login_fabrica)");
		$error = pg_last_error($con);
		$error = str_replace("ERROR:", '', $error);
		if (pg_last_error($con)) throw new Exception(traduz("falha.ao.validar.os", $con, $cook_idioma).':'.$error);

		$res = pg_query($con, "SELECT fn_valida_os_item($nova_os, $login_fabrica)");
		#$error = pg_last_error($con);
		#$error = str_replace("ERROR:", '', $error);
		$error = str_replace(",","",pg_last_error($con));
		$error = explode("ERROR:",$error);
		$error = explode("CONTEXT:",$error[1]);
		$error = $error[0];

		if (pg_last_error($con)) throw new Exception(traduz("falha.ao.validar.item.da.os", $con, $cook_idioma).'.'.$error);

		$sql = "UPDATE tbl_os_item SET qtde = (qtde - 1) WHERE os_item = $os_item AND peca = $peca_id";
		$res = pg_query($con, $sql);

	}