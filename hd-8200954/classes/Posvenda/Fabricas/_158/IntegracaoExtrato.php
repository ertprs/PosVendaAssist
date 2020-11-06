	<?

	require_once dirname(__FILE__)."/../../Extrato.php";

	class IntegracaoExtrato {

	    private $extrato;
	    private $_fabrica;
	    private $con;
	    private $_serverEnvironment;
	    public  $errorMessage;
	    private $url;

	    public function __construct($fabrica, $con = null)
	    {

		$this->_fabrica = $fabrica;
		
		if ($con != null) {
		    $this->con = $con;
		} else {
		    $this->extrato = new Extrato($this->_fabrica);
		}

		include "/etc/telecontrol.cfg";		

		$this->_serverEnvironment = $_serverEnvironment;

		if ($this->_serverEnvironment == 'development') {
                	# Verificar local para seus testes - Comentado para gerar erro e o progrmador ajustar
                	# include "/www/assist/www/rotinas/imbera/funcoes.php";
					# include_once dirname(__FILE__)."/../../../../rotinas/imbera/funcoes.php";
        	} else {
                	include "/www/assist/www/rotinas/imbera/funcoes.php";
        	}

		$this->url = urlSap();	
	    }

	    public function getExtratoSapByStatus($posto = null, $status)
	    {

		if ($posto != null) {
		    $wherePosto = "AND e.posto = {$posto}";
		}

		if ($status == "aprovados") {
		    $whereStatus = "AND e.exportado IS NOT NULL AND e.liberado IS NULL";
		} else if ($status == "liberados") {
		    $whereStatus = "AND e.exportado IS NOT NULL AND e.liberado IS NOT NULL";
		}

		$qry = "
		    SELECT
			e.posto,
			e.extrato,
			e.exportado,
			e.liberado,
			ep.autorizacao_pagto
		    FROM tbl_extrato e
		    JOIN tbl_extrato_pagamento ep ON ep.extrato = e.extrato
		    WHERE e.fabrica = {$this->_fabrica}
		    AND ep.data_pagamento IS NULL
		    {$wherePosto}
		    {$whereStatus};
		";

		if (!empty($this->con)) {
		    $exec = pg_query($this->con, $qry);
		} else {
		    $pdo = $this->extrato->_model->getPDO();
		    $query = $pdo->prepare($qry);
		    $exec = $query->execute();
		}

		if (!$exec) {
		    throw new Exception("Ocorreu um erro buscando dados para integração");
		} else {

		    if (!empty($this->con)) {
			$res = pg_fetch_all($exec);
		    } else {
			$res = $query->fetchAll(\PDO::FETCH_ASSOC);
		    }

		    if (count($res) == 0) {
			return false;
		    } else {
			return $res;
		    }

		}
		    
	    }

	    public function BuscaDadosExtrato($extrato)
	    {	    	

		if (empty($extrato)) {
		    throw new Exception("Número de extrato necessário para buscar os dados");            
		}

		$extrato_integracao = array();
		$sql = "SELECT extrato FROM tbl_extrato WHERE extrato = $extrato AND protocolo = 'Garantia'"; 
				if (!empty($this->con)) {
		    $exec = pg_query($this->con, $sql);
		} else {
		    $pdo = $this->extrato->_model->getPDO();
		    $query = $pdo->prepare($sql);
		    $exec = $query->execute();
		}
		
		if (!$exec) {
		    throw new Exception("Ocorreu um erro buscando dados para integração");
		} else {
		    
		    if (!empty($this->con)) {
			$dadosExtrato = pg_fetch_all($exec);
		    } else {
			$dadosExtrato = $query->fetchAll(\PDO::FETCH_ASSOC);
		    }

		    if ($dadosExtrato && count($dadosExtrato) > 0) {
			$sql = "
			    SELECT
				e.extrato,
				e.protocolo,
				CASE WHEN e.protocolo != 'Garantia' THEN TRUE ELSE FALSE END AS fora_garantia,
				ta.grupo_atendimento,
						f.codigo_familia,
						pst.codigo_posto,
						pst.conta_contabil,
						ea.codigo AS unidade_negocio,
						CASE WHEN e.protocolo != 'Garantia' THEN CASE WHEN e.avulso < 0 THEN COALESCE(JSON_FIELD('valor_mao_obra', pst.parametros_adicionais)::NUMERIC,0)+COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0)+e.avulso ELSE COALESCE(JSON_FIELD('valor_mao_obra', pst.parametros_adicionais)::NUMERIC,0)+COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0) END ELSE CASE WHEN e.avulso < 0 THEN COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0)+e.avulso ELSE COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0) END END AS total
							FROM tbl_extrato e
								JOIN tbl_os_extra oe USING(extrato)
								JOIN tbl_os o USING(os,fabrica)
								JOIN tbl_os_produto op USING(os)
								JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$this->_fabrica}
								JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$this->_fabrica}
								JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$this->_fabrica}
								JOIN tbl_posto_fabrica pst ON pst.posto = e.posto AND pst.fabrica = {$this->_fabrica}
								JOIN tbl_extrato_agrupado ea ON ea.extrato = e.extrato
								WHERE e.fabrica = {$this->_fabrica}
								AND e.extrato = {$extrato}
								AND (COALESCE(o.mao_de_obra,0)+COALESCE(o.valores_adicionais,0) > 0
								OR case when length(JSON_FIELD('valor_mao_obra', pst.parametros_adicionais)) =0 then 0 else JSON_FIELD('valor_mao_obra', pst.parametros_adicionais)::numeric end +COALESCE(o.valores_adicionais,0) > 0)
								GROUP BY e.extrato,e.protocolo,ta.grupo_atendimento,f.codigo_familia,pst.codigo_posto,pst.conta_contabil,ea.codigo,e.avulso,pst.parametros_adicionais
								UNION
								SELECT DISTINCT
								e.extrato,
								e.protocolo,
								CASE WHEN e.protocolo != 'Garantia' THEN TRUE ELSE FALSE END AS fora_garantia,
									CASE WHEN e.protocolo != 'Garantia' THEN '' ELSE 'G' END AS grupo_atendimento,
										'' AS codigo_familia,
										pst.codigo_posto,
										pst.conta_contabil,
										ea.codigo AS unidade_negocio,
										COALESCE(ROUND(e.avulso::NUMERIC,2),0) AS total
										FROM tbl_extrato e
										JOIN tbl_os_extra oe USING(extrato)
										JOIN tbl_os o USING(os,fabrica)
										JOIN tbl_os_produto op USING(os)
										JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$this->_fabrica}
										JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$this->_fabrica}
										JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$this->_fabrica}
										JOIN tbl_posto_fabrica pst ON pst.posto = e.posto AND pst.fabrica = {$this->_fabrica}
										JOIN tbl_extrato_agrupado ea ON ea.extrato = e.extrato
										WHERE e.fabrica = {$this->_fabrica}
										AND e.extrato = {$extrato}
										AND e.avulso > 0
										GROUP BY e.extrato,e.protocolo,f.codigo_familia,pst.codigo_posto,pst.conta_contabil,ea.codigo,e.avulso;";

			}else{
				$sql = "
					SELECT
						e.extrato,
						e.protocolo,
						CASE WHEN e.protocolo != 'Garantia' THEN TRUE ELSE FALSE END AS fora_garantia,
						ta.grupo_atendimento,
						f.codigo_familia,
						pst.codigo_posto,
						pst.conta_contabil,
						ea.codigo AS unidade_negocio,
						CASE WHEN e.protocolo != 'Garantia' THEN CASE WHEN e.avulso < 0 THEN COALESCE(ppu.preco,0)+COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0)+e.avulso ELSE COALESCE(ppu.preco,0)+COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0) END ELSE CASE WHEN e.avulso < 0 THEN COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0)+e.avulso ELSE COALESCE(SUM(o.mao_de_obra),0)+COALESCE(SUM(o.qtde_km_calculada),0)+COALESCE(SUM(o.valores_adicionais),0) END END AS total
						FROM tbl_extrato e
						JOIN tbl_os_extra oe USING(extrato)
						JOIN tbl_os o USING(os,fabrica)
						JOIN tbl_os_produto op USING(os)
						JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$this->_fabrica}
						JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$this->_fabrica}
						JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$this->_fabrica}
						JOIN tbl_posto_fabrica pst ON pst.posto = e.posto AND pst.fabrica = {$this->_fabrica}
						JOIN tbl_extrato_agrupado ea ON ea.extrato = e.extrato
						LEFT JOIN tbl_posto_preco_unidade ppu ON ppu.posto = pst.posto AND (SELECT COUNT(*) FROM tbl_distribuidor_sla WHERE ppu.distribuidor_sla = tbl_distribuidor_sla.distribuidor_sla AND ea.codigo = tbl_distribuidor_sla.unidade_negocio) > 0
						WHERE e.fabrica = {$this->_fabrica}
						AND e.extrato = {$extrato}
						AND (COALESCE(o.mao_de_obra,0)+COALESCE(o.valores_adicionais,0) > 0
						OR COALESCE(ppu.preco,0)+COALESCE(o.valores_adicionais,0) > 0)
						GROUP BY e.extrato,e.protocolo,ta.grupo_atendimento,f.codigo_familia,pst.codigo_posto,pst.conta_contabil,ea.codigo,e.avulso,ppu.preco
						UNION
						SELECT DISTINCT
						e.extrato,
						e.protocolo,
						CASE WHEN e.protocolo != 'Garantia' THEN TRUE ELSE FALSE END AS fora_garantia,
						CASE WHEN e.protocolo != 'Garantia' THEN '' ELSE 'G' END AS grupo_atendimento,
							'' AS codigo_familia,
							pst.codigo_posto,
							pst.conta_contabil,
							ea.codigo AS unidade_negocio,
							COALESCE(ROUND(e.avulso::NUMERIC,2),0) AS total
							FROM tbl_extrato e
							JOIN tbl_os_extra oe USING(extrato)
							JOIN tbl_os o USING(os,fabrica)
							JOIN tbl_os_produto op USING(os)
							JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$this->_fabrica}
							JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$this->_fabrica}
							JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$this->_fabrica}
							JOIN tbl_posto_fabrica pst ON pst.posto = e.posto AND pst.fabrica = {$this->_fabrica}
							$cond_join JOIN tbl_extrato_agrupado ea ON ea.extrato = e.extrato
							WHERE e.fabrica = {$this->_fabrica}
							AND e.extrato = {$extrato}
							AND e.avulso > 0
							GROUP BY e.extrato,e.protocolo,f.codigo_familia,pst.codigo_posto,pst.conta_contabil,ea.codigo,e.avulso;
				";
			}
		}

		if (!empty($this->con)) {
		    $exec = pg_query($this->con, $sql);
		} else {
		    $pdo = $this->extrato->_model->getPDO();
		    $query = $pdo->prepare($sql);
		    $exec = $query->execute();
		}
		
		if (!$exec) {
		    throw new Exception(utf8_decode("Ocorreu um erro buscando dados para integração"));
		} else {
		    
		    if (!empty($this->con)) {
			$dadosExtrato = pg_fetch_all($exec);
		    } else {
			$dadosExtrato = $query->fetchAll(\PDO::FETCH_ASSOC);
		    }

		    if (count($dadosExtrato) == 0 || !$dadosExtrato) {
			throw new Exception("Dados do extrato não encontrados para integração");
		    }

		    $codigosFamiliaCentroCusto = array(
			"01" => "20607", // Refrigerador
			"02" => "20608", // PostMix
			"03" => "20608", // Chopeira
			"04" => "20608", // Máquina de Café
			"05" => "20608" // Vending Machine
		    );

		    foreach ($dadosExtrato as $e => $dados) {

			if (empty($extrato_integracao)) {
			    $extrato_integracao[$dados['extrato']] = array(
				    "codigo_posto"      => $dados['codigo_posto'],
				    "protocolo"         => $dados['protocolo'],
				"conta_contabil"    => $dados['conta_contabil'],
				"unidade_negocio"   => $dados['unidade_negocio'],
			    );
			}

			/*$extrato_integracao[$dados['extrato']]['totais'][$e] = array(
			    "fora_garantia"     => $dados['fora_garantia'],
			    "grupo_atendimento" => $dados['grupo_atendimento'],
			    "codigo_familia"    => trim($dados['codigo_familia']),
			    "total"             => $dados['total']
			);*/
			if ($dados['fora_garantia'] == "t" && $dados['grupo_atendimento'] != "S") {
				$k = "fora_garantia_".$codigosFamiliaCentroCusto[trim($dados['codigo_familia'])];
			} else if ($dados['fora_garantia'] != "t" && $dados['grupo_atendimento'] != "S") {
				$k = "garantia";
			} else {
				$k = "S";
			}

			$extrato_integracao[$dados['extrato']]['totais'][$k]['fora_garantia'] = $dados['fora_garantia'];
			$extrato_integracao[$dados['extrato']]['totais'][$k]['grupo_atendimento'] = $dados['grupo_atendimento'];
			$extrato_integracao[$dados['extrato']]['totais'][$k]['codigo_familia'] = trim($dados['codigo_familia']);
			$extrato_integracao[$dados['extrato']]['totais'][$k]['total'] += $dados['total'];
		    }

		    return $extrato_integracao;

		}
	    }

	    public function dadosUnidadeNegocio($codigo_unidade) {

	    	$sql = " 
				SELECT DISTINCT tbl_unidade_negocio.codigo,
								tbl_unidade_negocio.centro_custo,
								tbl_unidade_negocio.codigo_centro_custo,
								tbl_unidade_negocio.grupo_centro_custo,
								tbl_unidade_negocio.centro_custo_garantia
		        FROM tbl_unidade_negocio
		        WHERE tbl_unidade_negocio.codigo = '{$codigo_unidade}'
	        ";
	        $query = pg_query($this->con, $sql);

	        $dados = pg_fetch_all($query);

	        return $dados;

	    }

	    public function ExportaExtrato($dadosExtrato, $env = null)
	    {

		$arrayCentroCusto = array(
		    "fora_garantia" => array(
				"20607" => "AP-COM-L7",
				"20608" => "AP-COM-L7",
				"20740" => "AP-COM-L7",
				"10607" => "AP-COM-L7",
				"50710" => "AP-COM-L7",
				"50720" => "AP-COM-L7",
				"50730" => "AP-COM-L7",
				"50740" => "AP-COM-L7",
				"50750" => "AP-COM-L7",
				"50760" => "AP-COM-L7",
				"50770" => "AP-COM-L7",
				"50790" => "AP-COM-L7",
				"10622" => "AP-COM-N4",
				"622"   => "AP-COM-M4",
		    ),
		    "garantia" => array(
				"10622" => "AP-COM-N4",
				"622"   => "AP-COM-M4",
				"50720" => "AP-COM-L7",
				"50730" => "AP-COM-L7",
				"50740" => "AP-COM-L7",
				"50780" => "AP-COM-L7",
				"50790" => "AP-COM-L7",
		    ),
		    "S" => array(
				"20609" => "AP-COM-L7"
		    )
		);

		$codigosFamiliaCentroCusto = array(
		    "01" => "20607", // Refrigerador
		    "02" => "20608", // PostMix
		    "03" => "20608", // Chopeira
		    "04" => "20608", // Máquina de Café
		    "05" => "20608" // Vending Machine
		);

		if (is_array($dadosExtrato)) {

		    foreach($dadosExtrato as $e => $dados) {

				$extrato = $e;

				$dadosExportacao = $this->dadosUnidadeNegocio($dados['unidade_negocio']);
				
				$centroCustoForaGarantia  = $dadosExportacao[0]['centro_custo'];
				$centroCustoGarantia  	  = $dadosExportacao[0]['centro_custo_garantia'];
				$codigoCentroCusto  	  = $dadosExportacao[0]['codigo_centro_custo'];
				$grupoCentroCusto   	  = $dadosExportacao[0]['grupo_centro_custo'];

				$itens_extrato = '';

				foreach ($dados['totais'] as $item) {

					$centroCusto = $centroCustoGarantia;

				    if ($item['grupo_atendimento'] == 'S') {

						$codigoCentroCusto = '20609';

				    } else if ($item['fora_garantia'] == 't' && $item['grupo_atendimento'] != 'G') {

				    	if ($centroCusto == 6200) {
				    		$codigoCentroCusto = $codigosFamiliaCentroCusto[$item['codigo_familia']];
				    	}

				    	if ($centroCusto == 6200 and strlen(trim($item['codigo_familia'])) == 0) {
				    		$codigoCentroCusto = "20607";
				    	}

				    	$centroCusto = $centroCustoForaGarantia;

				    }
				    
				    // Adicionado calculo com coeficiente de desconto
					$item['total'] = $item['total'] * 0.9075;
					
				    $itens_extrato .= '
					<ITENS>
					    <PLANT>'.$centroCusto.'</PLANT>
					    <QUANTITY>1</QUANTITY>
					    <PO_UNIT>SER</PO_UNIT>
					    <SHORT_TEXT>Extrato '.$extrato.', Centro de Custo '.$codigoCentroCusto.'</SHORT_TEXT>
					    <MATL_GROUP>'.$grupoCentroCusto.'</MATL_GROUP>
					    <COSTCENTER>'.str_pad($codigoCentroCusto, 10, '0', STR_PAD_LEFT).'</COSTCENTER>
					    <PRECO>'.str_replace(',','',number_format($item['total'], 2)).'</PRECO>
					</ITENS>
				    ';
				}

				if ($this->_serverEnvironment == 'development') {

				    $url = $this->url."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_CriaPedido_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

				    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

				} else {

				    $url = $this->url."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_CriaPedido_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

				    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

				}

			$xml_post_string = '
			    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
				<soapenv:Header/>
				<soapenv:Body>
				    <tel:MT_CriaPedido_Req>
					<I_INPUT>
					    <LIFNR>'.$dados['conta_contabil'].'</LIFNR>
					    <EXTRATO>'.$extrato.'</EXTRATO>
					    '.$itens_extrato.'
					</I_INPUT>
				    </tel:MT_CriaPedido_Req>
				</soapenv:Body>
			    </soapenv:Envelope>
			';
			$headers = array(
                            "Content-type: text/xml;charset=\"utf-8\"",
                            "Accept: text/xml",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Content-length: ".strlen($xml_post_string),
                            $authorization
                        );

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 180);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$retornoCurl = curl_exec($ch);
			
			$erroCurl = curl_error($ch);

			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$retornoCurl = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$retornoCurl);
	                $retornoXML = new \SimpleXMLElement(utf8_encode($retornoCurl));
	                $retornoXML = $retornoXML->xpath('//E_OUTPUT');
			$retornoSoap = json_decode(json_encode((array) $retornoXML), true);
			$retornoSoap = $retornoSoap[0];

			if ($this->_serverEnvironment == "development") {
	                    $file = fopen('/tmp/imbera-ws.log','a');
			} else {
                            $file = fopen('/mnt/webuploads/imbera/logs/imbera-ws.log','a');
			}

			fwrite($file, 'Resquest \n\r');
			fwrite($file, $url.'\n\r');
			fwrite($file, $xml_post_string);

			fwrite($file, 'Response \n\r');
			fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
			fwrite($file, 'Http Code: '.$httpcode.'\n\r');
			fwrite($file, utf8_decode($retornoCurl));
			fclose($file);

			if (empty($retornoSoap['PEDIDO'])) {

			    foreach($retornoSoap as $index => $value) {
				if ($index == 'RETURN') {
				    $tipo = $value['TYPE'];
				    if ($tipo == 'E') {
					$erro  .= $value['message']. "<br>";
				    }
				}
			    }

			    if (!empty($erro)) {
				$sqlErro = "UPDATE tbl_extrato_extra set obs = '$erro' where extrato = $extrato";
				if (!empty($this->con)) {
				    $res = pg_query($this->con, $sqlErro);
				    if (!$res) {
					return false;
				    }
				}
			    }

			    return false;

			} else {

			    $pedidoSap = $retornoSoap['PEDIDO'];
			    if (!empty($this->con)) {
				pg_query($this->con, "BEGIN");
			    } else {
				$pdo = $this->extrato->_model->getPDO();
				$pdo->beginTransaction();
			    }

			    $sqlExtratoPagto = "SELECT * FROM tbl_extrato_pagamento WHERE extrato = {$extrato};";
			    
			    if (!empty($this->con)) {
				$resExtratoPagto = pg_query($this->con, $sqlExtratoPagto);
				if (!$resExtratoPagto) {
				    pg_query($this->con, "ROLLBACK");
				    return false;
				}
				$extratoPagto = pg_fetch_all($resExtratoPagto);
			    } else {
				$query = $pdo->prepare($sqlExtratoPagto);
				if (!$query->execute()) {
				    $pdo->rollBack();
				    return false;
				}
				$extratoPagto = $query->fetchAll(\PDO::FETCH_ASSOC);
			    }

			    if (count($extratoPagto) > 0 && $extratoPagto != false) {
				$gravaPedidoPagto = "UPDATE tbl_extrato_pagamento SET autorizacao_pagto = {$pedidoSap} WHERE extrato = {$extrato};";
			    } else {
				$gravaPedidoPagto = "INSERT INTO tbl_extrato_pagamento (extrato, autorizacao_pagto) VALUES ({$extrato}, {$pedidoSap});";
			    }

			    if (!empty($this->con)) {
				$res = pg_query($this->con, $gravaPedidoPagto);
				if (!$res) {
				    pg_query($this->con, "ROLLBACK");
				    return false;
				}
			    } else {
				$query = $pdo->prepare($gravaPedidoPagto);
				if (!$query->execute()) {
				    $pdo->rollBack();
				    return false;
				}
			    }

			    $updateExtratoExp = "UPDATE tbl_extrato SET exportado = now() WHERE extrato = {$extrato};";

			    if (!empty($this->con)) {
				$res = pg_query($this->con, $updateExtratoExp);
				if (!$res) {
				    pg_query($this->con, "ROLLBACK");
				    return false;
				}
			    } else {
				$query = $pdo->prepare($updateExtratoExp);
				if (!$query->execute()) {
                            $pdo->rollBack();
                            return false;
                        }
                    }

                    if (!empty($this->con)) {
                        pg_query($this->con, "COMMIT");
                    } else {
                        $pdo->commit();
                    }
                }
            }
        }
        return true;
    }

}
