<?php 
	function insereOsRevendaCallcenter ($os_revenda, $hd_chamado, $consumidor_revenda, $obs_sac_adicionais=null) {
		global $con, $login_fabrica;
		$obs_sac_adicionais = str_replace("'", "", $obs_sac_adicionais);

		$tipo_atendimento = '';
			if (in_array($login_fabrica, [178])) {
                $sqlTipo = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = 178 AND UPPER(fn_retira_especiais(descricao)) = 'GARANTIA DOMICILIO' AND ativo = 't'";;
                $resTipo = pg_query($con, $sqlTipo);

                if (pg_num_rows($resTipo) > 0) {
                    $tipo_atendimento = pg_fetch_result($resTipo, 0, 'tipo_atendimento');
                    $campo_tipo_atendimento = ", tipo_atendimento";
                    $valor_tipo_atendimento = ", {$tipo_atendimento}";
                }
            }           

	
		if (empty($os_revenda)){
			$sql_in_revenda = " 
                INSERT INTO tbl_os_revenda (
                    fabrica,
                    data_abertura,
                    posto,
                    consumidor_revenda,
                    retorno_visita,
                    revenda,
                    hd_chamado ";
            #    if ($consumidor_revenda == "C"){
            $sql_in_revenda .= "   
                    ,consumidor_nome,
                    consumidor_cpf,
                    consumidor_cep,
                    consumidor_estado,
                    consumidor_cidade,
                    consumidor_bairro,
                    consumidor_endereco,
                    consumidor_numero,
                    consumidor_complemento,
                    consumidor_fone,
                    consumidor_email,
                    campos_extra,
					obs_causa 
					$campo_tipo_atendimento";
            #    }
            $sql_in_revenda .= "
                ) SELECT
                    $login_fabrica,
                    CURRENT_DATE,
                    hce.posto,
                    hce.consumidor_revenda,
                    't',
                    hce.revenda,
                    hc.hd_chamado ";
            #    if ($consumidor_revenda == "C"){
            $sql_in_revenda .= "
                    ,hce.nome,
                    hce.cpf,
                    hce.cep,
                    c.estado,
                    c.nome,
                    hce.bairro,
                    hce.endereco,
                    hce.numero,
                    hce.complemento,
                    CASE WHEN hce.celular IS NOT NULL THEN hce.celular ELSE hce.fone END,
                    hce.email,
                    ('{\"inscricao_estadual\": \"'||hce.rg||'\"}')::jsonb,
					'$obs_sac_adicionais'
					$valor_tipo_atendimento
	";
            #    }
            $sql_in_revenda .= "
                FROM tbl_hd_chamado hc
                JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
                JOIN tbl_cidade c ON c.cidade = hce.cidade
                WHERE hc.hd_chamado = $hd_chamado
                RETURNING os_revenda ";
            $res_in_revenda = pg_query($con, $sql_in_revenda);

            if (strlen(pg_last_error()) > 0){
            	throw new Exception("Erro ao gravar Ordem de Serviço #R2");
            }

            if (pg_num_rows($res_in_revenda) > 0){
                $os_revenda = pg_fetch_result($res_in_revenda, 0, 'os_revenda');
            }
        }
		return $os_revenda;
	}

	function insereOsRevendaItemCallcenter ($os_revenda, $hd_chamado, $cont_os, $revenda, $consumidor_revenda) {
		global $con, $login_fabrica;

		if (!empty($os_revenda)){

			$sql_qtde = "SELECT SUM(qtde) AS qtde FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado AND tbl_hd_chamado_item.os IS NOT NULL AND tbl_hd_chamado_item.qtde IS NOT NULL";
			$res_qtde = pg_query($con, $sql_qtde);

			$cont_os = pg_fetch_result($res_qtde, 0, "qtde");
			
			if (empty($cont_os)){
				$cont_os = 1;
			}else{
				$cont_os = $cont_os + 1;
			}
			
			$sql_hd = "
	            SELECT 
	                hce.posto,
	                hce.consumidor_revenda,
	                hce.hd_chamado,
	                hci.hd_chamado_item,
	                hci.produto,
	                hci.serie,
	                hci.nota_fiscal,
	                hci.data_nf,
	                hci.defeito_reclamado,
	                hci.defeito_reclamado_descricao,
	                hci.qtde,
	                hce.nome,
	                hce.cpf,
	                hce.rg,
	                hce.cep,
	                c.estado,
	                c.nome AS cidade_descricao, 
	                hce.cidade,
	                hce.bairro,
	                hce.endereco,
	                hce.numero,
	                hce.complemento,
	                hce.fone,
	                hce.celular,
	                hce.email
	            FROM tbl_hd_chamado hc
	            JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
	            JOIN tbl_hd_chamado_item hci ON hci.hd_chamado = hc.hd_chamado
	            LEFT JOIN tbl_cidade c ON c.cidade = hce.cidade 
	            WHERE hc.fabrica = $login_fabrica 
	            AND hci.os IS NULL
	            AND hci.comentario IS NULL
		    	AND hci.qtde IS NOT NULL
	            AND hc.hd_chamado = $hd_chamado";
	        $res_hd = pg_query($con, $sql_hd);

	        if (pg_num_rows($res_hd) > 0){
	        	$dadosHci = pg_fetch_all($res_hd);

	        	foreach ($dadosHci as $key => $value) {
	        		extract($value);

	        		if (empty($produto)){
	        			$produto = "NULL";
	        		}
	        		if (empty($defeito_reclamado)){
	        			$defeito_reclamado = "NULL";
	        		}
	        		if (empty($data_nf)){
	        			$data_nf = "NULL";
	        		}else{
	        			$data_nf = "'".$data_nf."'";
	        		}

	        		$sql_in_revenda_i = "
		                INSERT INTO tbl_os_revenda_item (
		                    os_revenda,
		                    produto,
		                    serie,
		                    nota_fiscal,
		                    data_nf,
		                    defeito_reclamado,
		                    defeito_reclamado_descricao,
		                    qtde,
		                    explodida
		                )VALUES(
		                    {$os_revenda},
		                    $produto,
		                    '$serie',
		                    '$nota_fiscal',
		                    $data_nf,
		                    $defeito_reclamado,
		                    '$defeito_reclamado_descricao',
		                    $qtde,
		                    't'
	                    )RETURNING os_revenda_item
	               	";
	               	$res_in_revenda_i = pg_query($con, $sql_in_revenda_i);
	               	
			    	if (strlen(pg_last_error()) > 0){
	               		throw new Exception("Erro ao gravar Ordem de Serviço #R3");
		            }

	               	$os_revenda_item = pg_fetch_result($res_in_revenda_i, 0, 0);
	               	$value["os_revenda"] = $os_revenda;
	               	$value["os_revenda_item"] = $os_revenda_item;
	               	#$value["cont_os"] = $cont_os;
	               	$value["revenda"] = $revenda;
	               	$value["consumidor_revenda"] = $consumidor_revenda;
	               	$value["inscricao_estadual"] = $rg;

	               	for ($i=0; $i < $qtde; $i++) { 
	               		$value["cont_os"] = $cont_os;
	               		abreOsExplodida($value);
	               		$cont_os++;
	               	}
	          	}

	          	$sqlUpdateOsRevenda = "UPDATE tbl_os_revenda SET sua_os = '$os_revenda' WHERE os_revenda = $os_revenda";
	          	$resUpdateOsRevenda = pg_query($con, $sqlUpdateOsRevenda);

	          	$sqlUpdateHci = " UPDATE tbl_hd_chamado_item set comentario = 'Aberta a Ordem de Serviço $os_revenda' WHERE hd_chamado_item = $hd_chamado_item AND hd_chamado = $hd_chamado ";
	            $resUpdateHci = pg_query($con, $sqlUpdateHci);
	        	insereComunicadoPosto($value);
	        }
		}
	}

	function abreOsExplodida ($dados){
		global $con, $login_fabrica;

		if (!empty($dados["os_revenda_item"])){
			extract($dados);
			
			if (!empty($endereco)){
				$endereco = str_replace("'", " ", $endereco);
			}

			if (!empty($cidade_descricao)){
				$cidade_descricao = str_replace("'", " ", $cidade_descricao);
			}

			if (!empty($bairro)){
				$bairro = str_replace("'", " ", $bairro);
			}

			if (!empty($complemento)){
				$complemento = str_replace("'", " ", $complemento);
			}

			if (!empty($nome)){
				$nome = str_replace("'", " ", $nome);
			}
			
			if (empty($data_nf)){
                $data_nf_os = "null";
            }else{
                $data_nf_os = "'".$data_nf."'";
            }

            if (!empty($revenda)) {

            	$sqlDadosRevenda = "SELECT nome, cnpj 
            						FROM tbl_revenda
            						WHERE revenda = {$revenda}";
            	$resDadosRevenda = pg_query($con, $sqlDadosRevenda);

            	if (pg_num_rows($resDadosRevenda) == 0) {
            		throw new Exception("Erro ao gravar Ordem de Serviço #R11");
            	}

            	$nome_revenda = pg_fetch_result($resDadosRevenda, 0, 'nome');
            	$cnpj_revenda = pg_fetch_result($resDadosRevenda, 0, 'cnpj');

            }
           
           	$tipo_atendimento = '';
			if (in_array($login_fabrica, [178])) {
                $sqlTipo = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = 178 AND UPPER(fn_retira_especiais(descricao)) = 'GARANTIA DOMICILIO' AND ativo = 't'";;
                $resTipo = pg_query($con, $sqlTipo);

                if (pg_num_rows($resTipo) > 0) {
                    $tipo_atendimento = pg_fetch_result($resTipo, 0, 'tipo_atendimento');
                    $campo_tipo_atendimento = ", tipo_atendimento";
                    $valor_tipo_atendimento = ", {$tipo_atendimento}";
                }
            }           

           $sqlIn = "
                INSERT INTO tbl_os (
                    fabrica,
                    data_abertura,
                    posto,
                    consumidor_revenda,
                    sua_os,
                    status_checkpoint,
                    hd_chamado";
            if (!empty($produto)){
            $sqlIn .= "
                    , 
                    nota_fiscal,
                    data_nf,
                    produto,
                    serie,
                    defeito_reclamado,
                    defeito_reclamado_descricao";
            }
            #if ($consumidor_revenda == "C"){
            $sqlIn .= "
                    , consumidor_nome,
                    consumidor_cpf,
                    consumidor_cep,
                    consumidor_estado,
                    consumidor_cidade,
                    consumidor_bairro,
                    consumidor_endereco,
                    consumidor_numero,
                    consumidor_complemento,
                    consumidor_fone,
                    consumidor_celular,
                    consumidor_email ";
            #}
            if (!empty($revenda)){
            $sqlIn .= ", revenda
            		   , revenda_nome
            		   , revenda_cnpj "; 
            }
            if (!empty($tipo_atendimento)) {
            	$sqlIn .= "{$campo_tipo_atendimento}";
            }
            $sqlIn .= "
                ) VALUES (
                    $login_fabrica,
                    CURRENT_DATE,
                    $posto,
                    '$consumidor_revenda',
                    '$os_revenda-$cont_os',
                    0,
                    $hd_chamado";
            if (!empty($produto)){
            $sqlIn .= "
                    , '$nota_fiscal',
                    $data_nf_os,
                    $produto,
                    '$serie',
                    $defeito_reclamado,
                    '$defeito_reclamado_descricao'";
            }
            #if ($consumidor_revenda == "C"){
            $sqlIn .= "
                    , '$nome',
                    '$cpf',
                    '$cep',
                    '$estado',
                    '$cidade_descricao',
                    '$bairro',
                    '$endereco',
                    '$numero',
                    '$complemento',
                    '$fone',
                    '$celular',
                    '$email' ";
            #}       
            if (!empty($revenda)){
            	$sqlIn .= ", $revenda 
            			   , '$nome_revenda'
            			   , '$cnpj_revenda'"; 
            }
            if (!empty($tipo_atendimento)) {
            	$sqlIn .= "{$valor_tipo_atendimento}";
            }
            $sqlIn .= " ) RETURNING os ";
            $resIn = pg_query($con, $sqlIn);
            $os_id = pg_fetch_result($resIn, 0, 'os');

            if (strlen(pg_last_error()) > 0){
            	throw new Exception("Erro ao gravar Ordem de Serviço #R4");
            }

            if (!empty($os_id)){
	            if (!empty($produto)){
	                $sqlInOsProduto = "
	                    INSERT INTO tbl_os_produto (
	                        os,
	                        produto,
	                        serie
	                    ) VALUES (
	                        {$os_id},
	                        {$produto},
	                        '{$serie}' 
	                    )";
	                $resInOsProduto = pg_query($con, $sqlInOsProduto);

	                if (strlen(pg_last_error()) > 0){
		            	throw new Exception("Erro ao gravar Ordem de Serviço #R5");
		            }
	            }
            
	            $sqlInOsExtra = "
	                INSERT INTO tbl_os_extra (
	                    os
	                ) VALUES (
	                    {$os_id}
	                )";
	            $resInOsExtra = pg_query($con, $sqlInOsExtra);

	            if (strlen(pg_last_error()) > 0){
	            	throw new Exception("Erro ao gravar Ordem de Serviço #R6");
	            }

	            if (!empty($consumidor_rg)){
	            	$insc_estadual = array();
	            	$insc_estadual["inscricao_estadual"] = $consumidor_rg;
	            	$campos_adicionais = json_encode($insc_estadual);

	            	$insert_campo = ", campos_adicionais";
	            	$insert_valor = ", '$campos_adicionais'";
	            }

	            $sqlInOsCampoExtra = "
	                INSERT INTO tbl_os_campo_extra (
	                    fabrica,
	                    os,
	                    os_revenda,
	                    os_revenda_item 
	                    $insert_campo
	                ) VALUES (
	                    {$login_fabrica},
	                    {$os_id},
	                    {$os_revenda},
	                    {$os_revenda_item}
	                    {$insert_valor}
	                )";
	            $resInOsCampoExtra = pg_query($con, $sqlInOsCampoExtra);
	            
	            if (strlen(pg_last_error()) > 0){
	            	throw new Exception("Erro ao gravar Ordem de Serviço #R7");
	            }

	            $sqlUpdateHci = " UPDATE tbl_hd_chamado_item set os = $os_id WHERE hd_chamado_item = $hd_chamado_item AND hd_chamado = $hd_chamado ";
	            $resUpdateHci = pg_query($con, $sqlUpdateHci);

	            if (strlen(pg_last_error()) > 0){
	            	throw new Exception("Erro ao gravar Ordem de Serviço #R8");
	            }

	            $updateStatus = "UPDATE tbl_os set status_checkpoint = 0 WHERE os = $os_id AND fabrica = $login_fabrica";
	            $resUpdateStatus = pg_query($con, $updateStatus);

	            if (strlen(pg_last_error()) > 0){
	            	throw new Exception("Erro ao gravar Ordem de Serviço #R9");
	            }
	        }
	    }
	}

	function insereComunicadoPosto ($dados){
		global $con, $login_fabrica;
		extract($dados);
		
		if (!empty($revenda)){
			$sql = "SELECT revenda, nome, endereco, cnpj FROM tbl_revenda WHERE revenda = $revenda";
			$res = pg_query($con, $sql);
			
			if (pg_num_rows($res) > 0){
				$revenda_nome = pg_fetch_result($res, 0, "nome");
				$revenda_endereco = pg_fetch_result($res, 0, "endereco");
				$cnpj = pg_fetch_result($res, 0, "cnpj");
			}
		}

		if (!empty($endereco)){
			$endereco = str_replace("'", " ", $endereco);
		}

		if (!empty($cidade_descricao)){
			$cidade_descricao = str_replace("'", " ", $cidade_descricao);
		}

		if (!empty($bairro)){
			$bairro = str_replace("'", " ", $bairro);
		}

		if (!empty($complemento)){
			$complemento = str_replace("'", " ", $complemento);
		}

		if (!empty($nome)){
			$nome = str_replace("'", " ", $nome);
		}

		if (!empty($revenda_nome)){
			$revenda_nome = str_replace("'", " ", $revenda_nome);
		}
		
		$mensagem = " 
			O Callcenter da fábrica Roca, abriu um atendimento que se tornou uma ORDEM DE SERVIÇO para ser atendida pelo seu posto autorizado. <br/>
			ORDEM DE SERVIÇO nº $os_revenda <br/>
			Atendimento do Call-Center nº $hd_chamado <br/>
			Consumidor: $nome <br/>
			Endereço: $endereco - Bairro: $bairro - nº $numero - Complemento: $complemento <br/>
			Revenda: $cnpj $revenda_nome <br/><br/>
			Favor realizar o agendamento com o consumidor o mais rápido possível, e qualquer dúvida, entrar em contato com a fábrica.
		";
		$sql = "INSERT INTO tbl_comunicado (
                    mensagem               ,
                    descricao              ,
                    tipo                   ,
                    fabrica                ,
                    obrigatorio_site       ,
                    posto                  ,
                    ativo
                )VALUES( 
                	'$mensagem',
                	'Nova ORDEM DE SERVIÇO nº $os_revenda',
                	'comunicado',
                	$login_fabrica,
                	't',
                	$posto,
                	't'
                )";
        $res = pg_query ($con,$sql);

        if (strlen(pg_last_error()) > 0){
        	throw new Exception("Erro ao gravar Ordem de Serviço #R10");
        }
	}

?>
