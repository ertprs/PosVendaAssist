<?php
	
	$pre_funcoes_fabrica = array('valida_numero_de_serie_jfa');
	$funcoes_fabrica = array("auditoria_numero_de_serie_jfa");

	function grava_os_explodida_fabrica($array_produto){

	    $tecnico = $array_produto['tecnico'];

	    if (empty($tecnico)) {
	        $tecnico = "null";
	    }

	    return array(
	        "tecnico"   => "{$tecnico}"
	    );

	}

	function valida_numero_de_serie_jfa(){
    	global $con, $campos, $login_fabrica, $msg_erro;

		if (isset($campos['produtos'])) {
			
			foreach ($campos['produtos'] as $key => $value) {
	
				if ($key === "__modelo__") {
					continue;
				}

				$cond = "";

				if (!empty($value['serie'])) {
					$serie = $value['serie'];
				}

				if (!empty($value['referencia'])) {
					$produto_ref  = $value['referencia'];
					$cond .= " AND tbl_produto.referencia = '$produto_ref'";
				}

				if (!empty($value['descricao'])) {
					$produto_desc = $value['descricao'];
					$cond .= " AND tbl_produto.descricao ILIKE '%$produto_desc%'";
				}

				if (empty($produto_ref) && empty($produto_desc)) {
					continue;
				}

				$sql_produto = "SELECT produto, 
									   numero_serie_obrigatorio
							    FROM tbl_produto 
							    WHERE fabrica_i = $login_fabrica $cond";
				$res_produto = pg_query($con, $sql_produto);

				if (pg_num_rows($res_produto) > 0) {

					$produto                  = pg_fetch_result($res_produto, 0, 'produto');
					$numero_serie_obrigatorio = pg_fetch_result($res_produto, 0, 'numero_serie_obrigatorio'); 

					if($numero_serie_obrigatorio == "t" && empty($serie)){
			           $msg_erro["msg"][] = "Para este produto $produto_ref - $produto_desc o número de Série é obrigatório";
			        } else {
			            $dataSerieInvalida = false;
			            $serieData = substr($serie, 0,4);
			            $serieDataMes = substr($serie, 0,2);
			            $serieDataAno = substr($serie, 2,2);
			            $serieReferencia = substr($serie, 4,3);
			            
			            if (!is_numeric($serieData)) {
			                $dataSerieInvalida = true;
			            }

			            if (!preg_match('/^(0[1-9]|1[0-2])$/', $serieDataMes)) {
			                $dataSerieInvalida = true;
			            }

			            if (!preg_match('/^(10|[0-9][0-9])$/', $serieDataAno)) {
			                $dataSerieInvalida = true;
			            }

			            if ($dataSerieInvalida == true) {
			                $msg_erro["msg"][$key] = "Número de Série $serie do produto $produto_ref - $produto_desc inválido";
			            } 
			        }
				}
			}
		}
		return (count($msg_erro["msg"]) > 0) ? $msg_erro : true;
	}

	function auditoria_numero_de_serie_jfa() {
	    global $con, $login_fabrica, $campos, $os_revenda, $posto_id, $msg_erro;

	    if (count($msg_erro["msg"]) == 0) {
	    	
	    	$campos_validos = [];
	    	$posicao_os_revenda = 1;

	    	foreach ($campos['produtos'] as $posicao => $campo) {
				if (empty($campo['os_revenda_item']) && empty($campo['id'])) {
					continue;
				}
				$campos_validos[]	= $campo;				    		
	    	}

			foreach ($campos_validos as $key => $value) {
								
				$os_revenda_item = $value['os_revenda_item'];
				$produto_id = $value['id'];

			    $sql_os_revenda = "SELECT os_revenda FROM tbl_os_revenda_item WHERE os_revenda_item = $os_revenda_item AND produto = $produto_id";
	    	    $res_os_revenda = pg_query($con, $sql_os_revenda);

	    		$os_revenda_id = pg_fetch_result($res_os_revenda, 0, 'os_revenda');
	    		$os_revenda_id = $os_revenda_id."-".$posicao_os_revenda;
	    		$posicao_os_revenda ++;

		    	$sql_os = "SELECT os FROM tbl_os WHERE sua_os = '$os_revenda_id' AND fabrica = $login_fabrica ORDER BY data_abertura DESC";
	        	$res_os = pg_query($con, $sql_os);

		   	 	if (pg_num_rows($res_os) == 0) {
		   	 		throw new Exception("Erro ao lançar ordem de serviço");
		   	 	}

		    	$os = pg_fetch_result($res_os, 0, 'os');
		    	$dataSerieInvalida = false;

				if (!empty($value['serie'])) {
					$serie = $value['serie'];
				}

				if (!empty($value['referencia'])) {
					$produto_ref  = $value['referencia'];
					$cond = " AND tbl_produto.referencia = '$produto_ref'";
				}

				if (!empty($value['descricao'])) {
					$produto_desc = $value['descricao'];
					$cond .= " AND tbl_produto.descricao ILIKE '%$produto_desc%'";
				}

				if (empty($produto_ref) && empty($produto_desc)) {
					continue;
				}

				$sql_produto = "SELECT produto, 
									   numero_serie_obrigatorio
							    FROM tbl_produto 
							    WHERE fabrica_i = $login_fabrica $cond";
				$res_produto = pg_query($con, $sql_produto);

				if (pg_num_rows($res_produto) > 0) {
					$produto                  = pg_fetch_result($res_produto, 0, 'produto');
					$numero_serie_obrigatorio = pg_fetch_result($res_produto, 0, 'numero_serie_obrigatorio'); 
		    		
					$notPostoInterno = verifica_tipo_posto("posto_interno", "FALSE", $posto_id);

				    if (!empty($serie) && $notPostoInterno == TRUE) {
				        $serieData = substr($serie, 0,4);
				        $serieDataMes = substr($serie, 0,2);
				        $serieDataAno = substr($serie, 2,2);
				        $serieReferencia = substr($serie, 4,3);
				        
				        if (!is_numeric($serie) || strlen($serie) != 13) {
				            $dataSerieInvalida = true;
				        }

				        if (!preg_match('/^(0[1-9]|1[0-2])$/', $serieDataMes)) {
				            $msg_erro["msg"][] = "Número de Série $serie do produto $produto_ref - $produto_desc inválido";
				        }

				        //validar se os dois ultimos digitos do ano atual é menor que o informado
				        //valida se o ano é 00
				        if ( !preg_match('/^[0-9]{2}$/', $serieDataAno) || ($serieDataAno == '00') || ($serieDataAno > date('y')) ) {
				            $msg_erro["msg"][] = "Número de Série $serie do produto $produto_ref - $produto_desc inválido";
				        }

				        //verificar se é a referencia do produto
				        if ( mb_strtoupper($produto_ref) != mb_strtoupper($serieReferencia) ) {
				            $dataSerieInvalida = true;
				        }
				    }
				}


				if ($dataSerieInvalida === true) {
				  	if (verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND tbl_auditoria_os.observacao ILIKE '%número de série%'", $os) === true) {

		                $busca = buscaAuditoria("tbl_auditoria_status.numero_serie = 't'");
		    
		                if($busca['resultado']){
		                    $auditoria_status = $busca['auditoria'];

		                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
		                            ({$os}, $auditoria_status, 'OS aguardando aprovação de número de série', true)";
		                    $res = pg_query($con, $sql);

		                    if (strlen(pg_last_error()) > 0) {
		                        throw new Exception("Erro ao lançar ordem de serviço");
		                    }
		                } else {
		                    throw new Exception("Erro ao lançar ordem de serviço");
		                }
		            }
				}	
			}
		}

		if (count($msg_erro["msg"]) > 0) {
			throw new Exception();
		} else {
			return true; 
		}
	}

?>
