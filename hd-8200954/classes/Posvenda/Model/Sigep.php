<?php
namespace Posvenda\Model;

class Sigep extends AbstractModel{

	private $_fabrica;
    public $_conn;
    private $_url;

	public function __construct($fabrica){

		parent::__construct('tbl_fabrica_correio');
		$this->_fabrica = $fabrica;
        $this->_conn = $this->getPDO();
        $this->_url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";
        // $this->_url = "https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

	}

	public function dadosContrato($tipo, $lista_etiqueta = ""){

		switch ($tipo) {
			case 'remetente': 	
				$sql = "SELECT  tbl_fabrica.logo,
						tbl_posto_fabrica.contato_endereco AS endereco,
						tbl_posto_fabrica.contato_cidade AS cidade,
						tbl_posto_fabrica.contato_estado AS estado,
						tbl_posto_fabrica.contato_fone_comercial AS fone,
						tbl_posto_fabrica.contato_cep AS cep,
						tbl_posto.cnpj,
						tbl_posto.nome AS razao_social,
						tbl_posto_fabrica.contato_numero AS numero
					FROM tbl_fabrica
					JOIN tbl_posto_fabrica USING(fabrica)
					JOIN tbl_posto USING(posto)
					WHERE tbl_fabrica.fabrica = {$this->_fabrica}
					AND tbl_posto_fabrica.posto = tbl_fabrica.posto_fabrica";
				$query  = $this->_conn->query($sql);
        		$res    = $query->fetch(\PDO::FETCH_ASSOC);

				if (count($res)>0) {
					$dados_acesso = array(
						"nome"     => retira_acentos($res["razao_social"]),
						"cidade"   => retira_acentos($res["cidade"]),
						"cnpj"     => utf8_encode($res["cnpj"]),
						"estado"   => utf8_encode($res["estado"]),
						"telefone" => utf8_encode($res["fone"]),
						"cep"      => utf8_encode($res["cep"]),
						"endereco" => retira_acentos($res["endereco"]),
						"numero"   => $res['numero'],
						"bairro"   => utf8_encode(retira_acentos($res["bairro"]))
					);
				}
			break;

			case 'destinatario':
				if($lista_etiqueta == ""){
					return false;
				}

				$sql = "SELECT 		tbl_etiqueta_servico.servico_correio,
							tbl_etiqueta_servico.etiqueta,
							tbl_etiqueta_servico.digito,
							tbl_etiqueta_servico.embarque,
							tbl_etiqueta_servico.peso,
							replace(tbl_etiqueta_servico.preco::text,'.',',') as preco,
							tbl_etiqueta_servico.caixa,
							tbl_etiqueta_servico.prazo_entrega,
							tbl_servico_correio.codigo,
							tbl_servico_correio.descricao,
							tbl_servico_correio.chave_servico,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto_fabrica.posto,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.contato_estado,
							tbl_posto_fabrica.contato_fone_comercial,
							tbl_posto_fabrica.contato_cep,
							tbl_posto_fabrica.contato_endereco,
							tbl_posto_fabrica.contato_numero,
							tbl_posto_fabrica.contato_complemento,
							tbl_posto_fabrica.contato_bairro,
							tbl_faturamento.nota_fiscal,
							replace(to_char(tbl_faturamento.total_nota::real,'99999D99')::text,'.',',') as total_nota,
							tbl_pedido.fabrica,
							array_to_string(array_agg(tbl_pedido.pedido),',') AS pedido,
							tbl_tipo_posto.posto_interno
					FROM tbl_etiqueta_servico
						JOIN tbl_pedido ON tbl_pedido.etiqueta_servico = tbl_etiqueta_servico.etiqueta_servico
							AND tbl_pedido.fabrica = {$this->_fabrica}
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto

						JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
						LEFT JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio = tbl_etiqueta_servico.servico_correio
						LEFT JOIN tbl_faturamento ON tbl_faturamento.embarque = tbl_etiqueta_servico.embarque AND tbl_faturamento.fabrica <> 0
					WHERE tbl_etiqueta_servico.etiqueta IN (".$lista_etiqueta.")
						AND tbl_etiqueta_servico.fabrica = {$this->_fabrica}
						GROUP BY tbl_etiqueta_servico.servico_correio,
							tbl_etiqueta_servico.etiqueta,
							tbl_etiqueta_servico.digito,
							tbl_etiqueta_servico.embarque,
							tbl_etiqueta_servico.peso,
							preco,
							tbl_etiqueta_servico.caixa,
							tbl_etiqueta_servico.prazo_entrega,
							tbl_servico_correio.codigo,
							tbl_servico_correio.descricao,
							tbl_servico_correio.chave_servico,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto_fabrica.posto,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.contato_estado,
							tbl_posto_fabrica.contato_fone_comercial,
							tbl_posto_fabrica.contato_cep,
							tbl_posto_fabrica.contato_endereco,
							tbl_posto_fabrica.contato_numero,
							tbl_posto_fabrica.contato_complemento,
							tbl_posto_fabrica.contato_bairro,
							tbl_faturamento.nota_fiscal,
							total_nota,
							tbl_pedido.fabrica,
							tbl_tipo_posto.posto_interno
						ORDER BY tbl_pedido.fabrica";
//echo "<pre>".print_r($sql,1)."</pre>";exit;
				$query  = $this->_conn->query($sql);
        		$res    = $query->fetchAll(\PDO::FETCH_ASSOC);
				$countRes = count($res);
				
				if ($countRes > 0) {

					for($i=0; $i < $countRes; $i++){
						$caixa      = $res[$i]["caixa"];
						$caixa      = explode(",",$caixa);
						$cubagem    = ($caixa[0]*$caixa[1]*$caixa[2])/6000;
						$nome 		= $res[$i]["nome"];
						$nome 		= substr($nome,0,60);

						$dados_acesso[$i] = array(
							"servico_correio" => $res[$i]["servico_correio"],
							"codigo"          => $res[$i]["codigo"],
							"pedido"          => $res[$i]["pedido"],
							"chave_servico"   => $res[$i]["chave_servico"],
							"descricao"       => trim($res[$i]["descricao"]),
							"etiqueta"        => $res[$i]["etiqueta"],
							"nota_fiscal"     => $res[$i]["nota_fiscal"],
							"total_nota"      => $res[$i]["total_nota"],
							"peso"            => $res[$i]["peso"],
							"preco"           => $res[$i]["preco"],
							"caixa"           => str_replace(",", " x ", $res[$i]["caixa"]),
							"comprimento"     => $caixa[0],
							"largura"         => $caixa[1],
							"altura"          => $caixa[2],
							"cubagem"         => $cubagem,
							"prazo_entrega"   => $res[$i]["prazo_entrega"],
							"posto"           => $res[$i]["posto"],
							"nome"            => retira_acentos(str_replace("&","", $nome)),
							"cidade"          => retira_acentos($res[$i]["contato_cidade"]),
							"cnpj"            => $res[$i]["cnpj"],
							"estado"          => $res[$i]["contato_estado"],
							"fone"            => $res[$i]["contato_fone_comercial"],
							"cep"             => $res[$i]["contato_cep"],
							"endereco"        => $res[$i]["contato_endereco"],
							"numero"          => $res[$i]["contato_numero"],
							"complemento"	  => $res[$i]["contato_complemento"],
							"fabrica"         => $res[$i]["fabrica"],
							"bairro"          => retira_acentos($res["contato_bairro"])
						);

						if($res[$i]['posto_interno'] == "t"){

							$sql = "SELECT DISTINCT tbl_os.consumidor_nome,
									tbl_os.consumidor_cpf AS cnpj,
									tbl_os.consumidor_cidade,
									tbl_os.consumidor_estado,
									tbl_os.consumidor_endereco,
									tbl_os.consumidor_bairro,
									tbl_os.consumidor_numero,
									tbl_os.consumidor_complemento,
									tbl_os.consumidor_cep,
									tbl_os.consumidor_fone
								FROM tbl_os
								JOIN tbl_os_produto USING(os)
								JOIN tbl_os_item USING(os_produto)
								WHERE tbl_os.fabrica = {$this->_fabrica}
								AND tbl_os.posto = {$res[$i]['posto']}
								AND tbl_os_item.pedido IN({$res[$i]['pedido']})";
							$query  = $this->_conn->query($sql);
							$resC = $query->fetchAll(\PDO::FETCH_ASSOC);
							$countResC = count($resC);
							if($countResC > 0){

								$dados_acesso[$i]["nome"]     = retira_acentos(str_replace("&","", $resC[0]["consumidor_nome"]));
								$dados_acesso[$i]["cidade"]   = retira_acentos($resC[0]["consumidor_cidade"]);
								$dados_acesso[$i]["cnpj"]     = $resC[0]["cnpj"];
								$dados_acesso[$i]["estado"]   = $resC[0]["consumidor_estado"];
								$dados_acesso[$i]["fone"]     = $resC[0]["consumidor_fone"];
								$dados_acesso[$i]["cep"]      = $resC[0]["consumidor_cep"];
								$dados_acesso[$i]["endereco"] = $resC[0]["consumidor_endereco"];
								$dados_acesso[$i]["numero"]   = $resC[0]["consumidor_numero"];
								$dados_acesso[$i]["complemento"] = $resC[0]["consumidor_complemento"];
								$dados_acesso[$i]["bairro"]   = retira_acentos($resC[0]["consumidor_bairro"]);	
							}
						}
					}
				}
			break;

			case 'destinatarioDeclaracao':
				if($lista_etiqueta == ""){
					return false;
				}
				$sql = "SELECT tbl_pedido.etiqueta_servico,
							tbl_etiqueta_servico.etiqueta,
							tbl_etiqueta_servico.peso,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto_fabrica.contato_cidade,
							tbl_posto_fabrica.contato_estado,
							tbl_posto_fabrica.contato_cep,
							tbl_posto_fabrica.contato_endereco,
							tbl_posto_fabrica.contato_numero,
							tbl_posto_fabrica.contato_complemento,
							tbl_posto_fabrica.contato_bairro,
							tbl_pedido.pedido,
							tbl_pedido_item.qtde,
							replace(tbl_pedido_item.preco::text,'.',',') as preco,
							(tbl_pedido_item.preco * tbl_pedido_item.qtde) AS total_peca,
							tbl_peca.descricao,
							tbl_tipo_posto.posto_interno,
							tbl_pedido.posto
					FROM tbl_etiqueta_servico
						JOIN tbl_pedido ON tbl_pedido.etiqueta_servico = tbl_etiqueta_servico.etiqueta_servico
							AND tbl_pedido.fabrica = {$this->_fabrica}
						JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
						JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
							AND tbl_peca.fabrica = tbl_pedido.fabrica
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto

					WHERE tbl_etiqueta_servico.etiqueta IN (".$lista_etiqueta.") 
						AND tbl_etiqueta_servico.fabrica = {$this->_fabrica}
						ORDER BY tbl_pedido.etiqueta_servico";
				$query  = $this->_conn->query($sql);
        		$res    = $query->fetchAll(\PDO::FETCH_ASSOC);
				$countRes = count($res);
				
				if ($countRes > 0) {
					$dados_acesso["destinatario"] = Array(
						"nome"     => $res[0]["nome"],
						"cnpj"     => $res[0]["cnpj"],
						"cidade"   => $res[0]["contato_cidade"],
						"estado"   => $res[0]["contato_estado"],
						"cep"      => $res[0]["contato_cep"],
						"endereco" => $res[0]["contato_endereco"],
						"numero"   => $res[0]["contato_numero"],
						"complemento" => $res[0]["contato_complemento"],
						"bairro"   => $res[0]["contato_bairro"]
					);

					if($res[0]["posto_interno"] == "t"){
						$sql = "SELECT DISTINCT tbl_os.consumidor_nome,
								tbl_os.consumidor_cpf AS cnpj,
								tbl_os.consumidor_cidade,
								tbl_os.consumidor_estado,
								tbl_os.consumidor_endereco,
								tbl_os.consumidor_bairro,
								tbl_os.consumidor_numero,
								tbl_os.consumidor_complemento,
								tbl_os.consumidor_cep,
								tbl_os.consumidor_fone
							FROM tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_item USING(os_produto)
							WHERE tbl_os.fabrica = {$this->_fabrica}
							AND tbl_os.posto = {$res[0]['posto']}
							AND tbl_os_item.pedido = {$res[0]['pedido']}";
						$query  = $this->_conn->query($sql);
						$resC    = $query->fetchAll(\PDO::FETCH_ASSOC);
						$countResC = count($resC);

						if ($countResC > 0) {
							$dados_acesso["destinatario"]["nome"]     = retira_acentos(str_replace("&","", $resC[0]["consumidor_nome"]));
							$dados_acesso["destinatario"]["cidade"]   = retira_acentos($resC[0]["consumidor_cidade"]);
							$dados_acesso["destinatario"]["cnpj"]     = $resC[0]["cnpj"];
							$dados_acesso["destinatario"]["estado"]   = $resC[0]["consumidor_estado"];
							$dados_acesso["destinatario"]["fone"]     = $resC[0]["consumidor_fone"];
							$dados_acesso["destinatario"]["cep"]      = $resC[0]["consumidor_cep"];
							$dados_acesso["destinatario"]["endereco"] = $resC[0]["consumidor_endereco"];
							$dados_acesso["destinatario"]["numero"]   = $resC[0]["consumidor_numero"];
							$dados_acesso["destinatario"]["complemento"]   = $resC[0]["consumidor_complemento"];
							$dados_acesso["destinatario"]["bairro"]   = retira_acentos($resC[0]["consumidor_bairro"]);
						}
					}

					for($i=0; $i < $countRes; $i++){
						$dados_acesso["etiquetas"][$res[$i]["etiqueta"]][] = Array(
							"etiqueta_servico" => $res[$i]["etiqueta_servico"],
							"peso"             => $res[$i]["peso"],
							"total_nota"       => $res[$i]["total_nota"],
							"pedido"           => $res[$i]["pedido"],
							"qtde"             => $res[$i]["qtde"],
							"preco"            => $res[$i]["preco"],
							"total_peca"       => $res[$i]["total_peca"],
							"descricao"        => $res[$i]["descricao"]
						);
					}
				}
			break;

			case 'buscaIdPlp':
				if($lista_etiqueta == ""){
					return false;
				}
				$sql = "SELECT tbl_lista_postagem.plp_correio,
						array_to_string (array_agg(tbl_etiqueta_servico.etiqueta), ',') AS etiqueta
					FROM tbl_lista_postagem
						JOIN tbl_plp_etiqueta ON tbl_plp_etiqueta.lista_postagem = tbl_lista_postagem.lista_postagem
						JOIN tbl_etiqueta_servico ON tbl_etiqueta_servico.etiqueta_servico = tbl_plp_etiqueta.etiqueta_servico
							AND tbl_etiqueta_servico.fabrica = {$this->_fabrica}

					WHERE tbl_lista_postagem.lista_postagem = {$lista_etiqueta}
						GROUP BY tbl_lista_postagem.plp_correio";
				$query  = $this->_conn->query($sql);
        		$res    = $query->fetchAll(\PDO::FETCH_ASSOC);
				$countRes = count($res);
				
				if ($countRes > 0) {
					$dados_acesso = Array(
						"idplp"    => $res[0]["plp_correio"],
						"etiqueta" => $res[0]["etiqueta"]
					);
				}
			break;

			case 'producao': 	
				$sqlAcesso = "	SELECT 	id_correio AS usuario,
														tbl_fabrica_correios.senha,
														tbl_fabrica_correios.codigo as codigo_administrativo,
														tbl_fabrica_correios.contrato,
														tbl_fabrica_correios.cartao,
														tbl_fabrica.cnpj,
														tbl_fabrica.cep
												FROM tbl_fabrica_correios
												JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_fabrica_correios.fabrica
												WHERE tbl_fabrica_correios.fabrica = $this->_fabrica 
												AND (tipo_contrato = 'plp' OR tipo_contrato IS NULL)";
				$query  = $this->_conn->query($sqlAcesso);
        		$res    = $query->fetch(\PDO::FETCH_ASSOC);

				if (count($res)>0) {
					$dados_acesso = pg_fetch_array($res);

					$res["senha"] = (in_array($this->_fabrica,array(11,147,153,160,172,186,187))) ? $res["senha"] : "eagojr";

					 $dados_acesso = array(
								 			"usuario" 				=> $res["usuario"],
										 	"senha" 				=> $res["senha"],
										 	"codigo_administrativo" => $res["codigo_administrativo"],
										 	"contrato" 				=> $res["contrato"],
										 	"cartao" 				=> $res["cartao"],
										 	"cnpj" 					=> $res["cnpj"],
										 	"cep" 					=> $res["cep"],
										 	"ano" 					=> date('Y')
										);
					
				}
			break;

			case 'servico': 	
				$sqlAcesso = "	SELECT 	tbl_servico_correio.servico_correio,
														descricao,
														codigo,
														chave_servico
												FROM tbl_servico_correio
												JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio = tbl_servico_correio.servico_correio
												WHERE fabrica = $aux AND tbl_servico_correio.ativo = 't'";
				$res = pg_query($this->_conn, $sqlAcesso);

				if (pg_num_rows($res)>0) {
					$dados_acesso = $res;
				}
			break;

			case 'posto': 		
				$sqlAcesso = "	SELECT 	TO_CHAR (tbl_embarque.data,'DD/MM') AS data_embarque,
														tbl_posto.posto,
														tbl_posto.nome,
														tbl_posto.cidade,
														tbl_posto.cnpj,
														tbl_posto.estado,
														tbl_posto.fone,
														tbl_posto.cep,
														tbl_posto.endereco,
														tbl_posto.numero,
														tbl_posto.bairro
												FROM tbl_embarque
												JOIN (SELECT distinct embarque FROM tbl_embarque_item WHERE liberado IS NOT NULL
													) emb ON emb.embarque = tbl_embarque.embarque
												JOIN tbl_posto USING (posto)
												WHERE tbl_embarque.embarque IN (".$aux.")";
				$res = pg_query($con, $sqlAcesso);

				if (pg_num_rows($res)>0) {
					$dados_acesso = $res;
				}
			break;

			case 'usuario':		
				$dados_acesso = array(	"usuario" 				=> '66494691000135',
										"senha" 				=> 'eagojr',
										"codigo_administrativo" => '14341190',
										"contrato" 				=> '9912358441',
										// "cartao" 				=> '0069835535',
										"cnpj" 					=> '66494691000135',
										"ano" 					=> date('Y'));
			break;
			
			case 'einhell':		
				$dados_acesso = array("usuario" 				=> '67647412000199',
						              "senha"                   => 'ir31um',
						              "codigo_administrativo" 	=> '13286013',
						              "contrato"                => '9912329529',
						              "cartao"                  => '0073789933',
						              "cnpj"                    => '67647412000199',
						              "ano"                     => date('Y'));

			break;
			
			case 'aulik':		
				$dados_acesso = array("usuario" 				=> '05256426',
						              "senha"                   => 'n4nov',
						              "codigo_administrativo" 	=> '14407655',
						              "contrato"                => '9912361764',
						              "cartao"                  => '0070109605',
						              "cnpj"                    => '05256426000124',
						              "ano"                     => date('Y'));
			break;

			case 'desenvolvimento':	
				$dados_acesso =  array(	'usuario'				=> 'sigep',
										'senha'			    	=> 'n5f9t8',
										'codigo_administrativo' => '08082650',
										'contrato'				=> '9912208555',
										'cartao'				=> '0057018901',
										'cnpj'			    	=> '34028316000103',
										'ano'			    	=> date('Y'));
			break;

			default: 			
				$dados_acesso =  array('usuario'				=> 'sigep',
										'senha'			    	=> 'n5f9t8',
										'codigo_administrativo' => '08082650',
										'contrato'				=> '9912208555',
										'cartao'				=> '0057018901',
										'cnpj'			    	=> '34028316000103',
										'ano'			    	=> date('Y'));
			break;
		}

	 	return $dados_acesso;
	}


	public function servicoContrato(){		
		
		$sql = "SELECT usuario,senha,contrato,cartao FROM tbl_fabrica_correios WHERE (tipo_contrato = 'plp' OR tipo_contrato IS NULL) AND fabrica = {$this->_fabrica}";
		$query  = $this->_conn->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

		$array_request = (object) array('usuario'			=> $res["usuario"], 
										'senha'			   	=> $res["senha"],
										'idContrato'	   	=> $res["contrato"],
								 		'idCartaoPostagem' 	=> $res["cartao"]
									);
/*
		$array_request = (object) array('usuario'=> "66494691000135",
			'senha'			   => "eagojr",
			'idContrato'	   => "9912358441",
			'idCartaoPostagem' => "0069835535");
*/





		try {

			$dadosWs = $this->headersCorreios();
			$client = new \SoapClient($this->_url, $dadosWs);

		} catch (\Exception $e) {
			$response[] = array("resultado" => false, array($e));
		    	return $response;
		}

	    $result = "";
	    try {
			$result = $client->__soapCall("buscaServicos", array($array_request));
		} catch (\Exception $e) {
			$response[] = array("resultado" => false, array($e));
	    	return $response;
		}

	    $resultServico = $result->return;
	    $resposta = array();

		if(count($resultServico) > 0){
	    	$res = pg_query($con,"BEGIN TRANSACTION");
		    $countServico = count($resultServico);

		    for($i = 0; $i<$countServico; $i++){
		    	unset($resultServico[$i]->servicoSigep->chancela);
		        $codigo_servico = str_replace(" ","",$resultServico[$i]->codigo);

		    	if(stripos($resultServico[$i]->descricao, " ") != false){
					$formatacao                   = explode(" ",$resultServico[$i]->descricao);
					$resultServico[$i]->descricao = "";
					$resultServico[$i]->descricao = $formatacao[0];
					$countF                       = count($formatacao);

		    		for($k=1; $k<$countF; $k++){
		    			$resultServico[$i]->descricao .= " ".$formatacao[$k];
		    		}
		    	}

			    $descricao 	    = trim($resultServico[$i]->descricao);
			    $chave_servico  = $resultServico[$i]->id;

			    $sql = "SELECT servico_correio FROM tbl_servico_correio JOIN tbl_servico_correio_fabrica USING(servico_correio) WHERE codigo LIKE '$codigo_servico' AND descricao LIKE '$descricao%' AND fabrica = {$this->_fabrica}";
				$query             = $this->_conn->query($sql);
				$resServicoCorreio = $query->fetch(\PDO::FETCH_ASSOC);
				$resServicoCorreio = array_filter($resServicoCorreio);
			    
			    if(count($resServicoCorreio) == 0){

					$sql = "SELECT servico_correio FROM tbl_servico_correio WHERE codigo = '$codigo_servico'";
					$query  = $this->_conn->query($sql);
	        		$res    = $query->fetch(\PDO::FETCH_ASSOC);

					$res = array_filter($res);
					if(count($res) == 0){
				   		$sql = "INSERT INTO tbl_servico_correio (descricao, codigo, chave_servico) VALUES ('".$descricao."','".$codigo_servico."','$chave_servico') on conflict do nothing RETURNING servico_correio";
						$query  = $this->_conn->query($sql);
						$res    = $query->fetch(\PDO::FETCH_ASSOC);
					}

			    	if (strlen(pg_last_error()) > 0 ) {
						$msg_erro = "erro";
						break;
					}

					if($msg_erro == ""){
						$servico_correio = $res["servico_correio"];

				    	$sql = "INSERT INTO tbl_servico_correio_fabrica (fabrica, servico_correio) VALUES ({$this->_fabrica},{$servico_correio})";
				    	$query  = $this->_conn->query($sql);

				    	if (strlen(pg_last_error()) > 0 ) {
							$msg_erro = "erro";
							break;
						}
				    }
				}
		    }

		    if($msg_erro == ""){
		    	$query  = $this->_conn->query("COMMIT TRANSACTION");
		    	$resposta = array("resultado" => true,"servicos" => $resultServico);
		    }else{
		    	$query  = $this->_conn->query("ROLLBACK TRANSACTION");
		    	$resposta = array(
					"resultado"   => false,
					"faultstring" => utf8_encode("Ocorreu um erro ao gravar os serviços disponíveis do correio no sistema, tente novamente mais tarde!"), 
					"servicos"    => $resultServico
				);
		    }
	    }else{
	    	unset($resposta);
	    	$resposta = array(
				"resultado"   => false,
				"faultstring" => utf8_encode("ERRO ao consultar os serviços no webservice dos correios, tente novamente mais tarde!")
			);
	    }
	    
	    return $resposta;
	}

	public function calcPrecoPrazo($dados){

		$servicoDisponivel = $this->servicoContrato();
		
		if(isset($servicoDisponivel["resultado"]) === false){
			return $servicoDisponivel["faultstring"];
		}
		
		$dadosRemetente = $this->dadosContrato("producao");
		
		$cepOrigem             = $dadosRemetente['cep'];
		$codigo_administrativo = $dadosRemetente['codigo_administrativo'];
		$senha                 = $dadosRemetente['senha'];

		$comprimento = $dados['comprimento'];
		$largura 	 = $dados['largura'];
		$altura 	 = $dados['altura'];
		$peso 	     = $dados['peso'];
		$valor 		 = $dados['valor'];
		$cepDestino  = $dados['cepDestino'];
//		$cepDestino  = "13201-002";

		if(empty($cepOrigem)){

			$response[] = array(
				"resultado" => false,
				"mensagem" => "CEP de Origem em branco"
			);
			return $response;
		}

		if(empty($cepDestino)){
			$response[] = array(
				"resultado" => false,
				"mensagem" => "CEP de Destino em branco"
			);
			return $response;
		}

		if($largura < 11){
			$largura = 11;
		}

		if($comprimento < 16){
			$comprimento = 16;
		}

		if(strripos($peso, ",") == true){
			$peso = str_replace(",", ".", $peso);
		}

		$valor = str_replace(",", ".", $valor);
		$valor = floatval($valor);

		$nCdServico   = "";
		$countServico = count($servicoDisponivel['servicos']);

		for($i=0; $i<$countServico; $i++){
			if($i > 0){
				$nCdServico .= ",";
			}

			$nCdServico .= trim($servicoDisponivel['servicos'][$i]->codigo);
		}

		$valor = ($valor < 19.5) ? 0 : $valor;

		$sCepOrigem          = $cepOrigem;   //'17500120';
		$sCepDestino         = $cepDestino;  //'13083775';
		$nVlPeso             = $peso; 		 // Peso em kg
		$nCdFormato          = '1'; 		 // Formato, 1-Caixa/pacote, 2-Rolo/prisma, 3-Envelope
		$nVlComprimento      = $comprimento; // Comprimento em cm
		$nVlAltura           = $altura; 	 // Altura em cm
		$nVlLargura          = $largura; 	 // Largura em cm
		$nVlDiametro         = '0'; 		 // Diametro em cm
		$sCdMaoPropria       = 'N'; 		 // Servico mão própria
		$nVlValorDeclarado   = $valor; 		 // Declarar valor - 0 - desabilitado
		$sCdAvisoRecebimento = 'N'; 		 // Aviso de recebimento
		// $codigo_administrativo = "14341190";
		// $senha 				   = "jk77nr";

		$array_request = (object) array(
			'nCdEmpresa'          => $codigo_administrativo,
			'sDsSenha'            => $senha,
			'nCdServico'          => $nCdServico,
			'sCepOrigem'          => $sCepOrigem,
			'sCepDestino'         => $sCepDestino,
			'nVlPeso'             => $nVlPeso,
			'nCdFormato'          => $nCdFormato,
			'nVlComprimento'      => $nVlComprimento,
			'nVlAltura'           => $nVlAltura,
			'nVlLargura'          => $nVlLargura,
			'nVlDiametro'         => $nVlDiametro,
			'sCdMaoPropria'       => $sCdMaoPropria,
			'nVlValorDeclarado'   => $nVlValorDeclarado,
			'sCdAvisoRecebimento' => $sCdAvisoRecebimento
		);
		$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx?WSDL";

		try {
			$headersCorreios = $this->headersCorreios();
			$client          = new \SoapClient($url, $headersCorreios);

		} catch (Exception $e) {
			$response = array(
				"resultado" => false, 
				"mensagem"  => "ERRO NA CONEXÃO COM O SERVIDOR DOS CORREIOS SIGEP"
			);
	    	return $response;
		}

	    $result = "";

	  	$result = $client->__soapCall("CalcPrecoPrazo", array($array_request));

		$resultCalculo  = $result->CalcPrecoPrazoResult->Servicos->cServico;

		$dados_postagem = array();
		$response       = array();
		$valores        = array();
		$countCalculo   = count($resultCalculo);

		for($i = 0; $i<$countCalculo; $i++)	{
			$valorresult = $resultCalculo[$i]->Valor;
			if(strlen($valorresult) == 0 ) {
				continue;
			}

	    	if(str_replace(",",".",$resultCalculo[$i]->Valor)){
				$resultCalculo[$i]->Valor = str_replace(",",".",$resultCalculo[$i]->Valor);
			}

			$j = 0;

			while($servicoDisponivel['servicos'][$j]->codigo != $resultCalculo[$i]->Codigo){
				$j++;
			}

	    	$dados_postagem[$i] = array(
				'codigo'        => utf8_encode($resultCalculo[$i]->Codigo),
				'valor'         => $resultCalculo[$i]->Valor,
				'descricao'     => $servicoDisponivel['servicos'][$j]->descricao,
				'valor_real'    => str_replace(",", ".", $resultCalculo[$i]->Valor),
				'prazo_entrega' => $resultCalculo[$i]->PrazoEntrega,
				'obs_fim'		=> $resultCalculo[$i]->obsFim
			);

	    	$valores[$i] = str_replace(",", ".", $resultCalculo[$i]->Valor);
	    }

	    asort($valores);
	    foreach ($valores as $chave => $valor) {
	    	$response[] = $dados_postagem[$chave];
		}

		$array_sem_valor = array();

	    if(count($response) > 0){
	    	$count = count($response);
	    	for($i = 0; $i<$count; $i++){

	    		if((int) $response[$i]["valor"] == 0){
	    			$array_sem_valor[] = $response[$i];
	    		}else{
		    		$resultado[] = $response[$i];
	    		}
			}

			$response = array_merge(array("resultado" => true),$resultado, $array_sem_valor);

	    	return $response;

	    }else{
	    	return array(
				"resultado" => false,
				"mensagem"  => utf8_encode("Não existe serviço disponível neste contrato!")
			);
	    }
	}

	public function buscaEtiquetasPedidos($dados){

		$codigo           = $dados['codigo'];
		$valor            = $dados['valor'];
		$peso             = $dados['peso'];
		$caixa            = $dados['caixa'];
		$pedidos          = $dados['pedidos'];
		$etiqueta_servico = $dados['etiqueta_servico'];
		
		$sql = "SELECT pedido FROM tbl_pedido 
			WHERE fabrica = {$this->_fabrica} 
				AND pedido IN (".implode(",",$pedidos).")
				AND etiqueta_servico IS NOT NULL";
		
		$query = $this->_conn->query($sql);
		$res   = $query->fetch(\PDO::FETCH_ASSOC);
		$res   = array_filter($res);

		if(count($res) > 0){
			$response = array(
				"resultado" => false,
				"msg"       => utf8_encode("O(s) pedido(s) ".implode(",",$pedidos)." já possuem etiquetas")
			);
			return $response;
		}

		$sql = "SELECT tbl_etiqueta_servico.etiqueta_servico, etiqueta 
				FROM tbl_etiqueta_servico
					JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio = tbl_etiqueta_servico.servico_correio
					JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio = tbl_etiqueta_servico.servico_correio
					LEFT JOIN tbl_pedido ON tbl_pedido.etiqueta_servico = tbl_etiqueta_servico.etiqueta_servico
						AND tbl_pedido.fabrica = {$this->_fabrica}
				WHERE tbl_servico_correio_fabrica.fabrica = {$this->_fabrica}
					AND (tbl_etiqueta_servico.fabrica IS NULL OR tbl_etiqueta_servico.fabrica = {$this->_fabrica})
					AND tbl_servico_correio.codigo = lpad('{$codigo}',5,'0')
					AND embarque IS NULL
					AND tbl_pedido.pedido IS NULL
				ORDER BY etiqueta_servico 
				LIMIT 1";
		$query = $this->_conn->query($sql);
		$res   = $query->fetch(\PDO::FETCH_ASSOC);
		$res   = array_filter($res);

		if (strlen(pg_last_error()) > 0) {
            throw new \Exception("Erro ao consultar etiqueta disponível") ;
        }

		if(count($res) == 0){
			$sqlC  = "SELECT tbl_servico_correio.servico_correio, chave_servico 
				FROM tbl_servico_correio
				JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio=tbl_servico_correio.servico_correio AND tbl_servico_correio_fabrica.fabrica = {$this->_fabrica}
				WHERE codigo = lpad('{$codigo}',5,'0') 
					AND tbl_servico_correio.ativo LIMIT 1";
			$query = $this->_conn->query($sqlC);
			$resC  = $query->fetch(\PDO::FETCH_ASSOC);

			$servico       = $resC['servico_correio'];
			$chave_servico = $resC['chave_servico'];

			$this->solicitaEtiquetasCorreios($servico,$chave_servico);

			$query = $this->_conn->query($sql);
			$res   = $query->fetch(\PDO::FETCH_ASSOC);
			$res   = array_filter($res);
		}

		if(count($res) > 0){
			$etiqueta = $res;

			if(strlen(pg_last_error()) == 0){
				
				foreach ($pedidos as $key => $pedido) {
					$res = pg_query($this->_conn,"BEGIN TRANSACTION");

					$sql = "UPDATE tbl_pedido SET
								etiqueta_servico = {$etiqueta['etiqueta_servico']}
							WHERE pedido = {$pedido}";
					$query = $this->_conn->query($sql);

			    	if (pg_last_error() > 0) {
						$query = $this->_conn->query("ROLLBACK TRANSACTION");
						throw new \Exception("Erro ao relacionar a etiqueta para o pedido ".$pedido);
					}

					$query = $this->_conn->query("COMMIT TRANSACTION");
				}

				if(strlen(pg_last_error()) == 0){
					$res = pg_query($this->_conn,"BEGIN TRANSACTION");

					if(strripos($peso, ",") == true){
						$peso = str_replace(",", ".", $peso);
					}

					$sql = "UPDATE tbl_etiqueta_servico SET
									preco    = {$valor},
									peso     = {$peso},
									caixa    = '{$caixa}'
							WHERE etiqueta_servico = {$etiqueta['etiqueta_servico']}";
					$query = $this->_conn->query($sql);

			    	if (pg_last_error() > 0) {
						$query = $this->_conn->query("ROLLBACK TRANSACTION");
						throw new \Exception("Erro ao atualizar dados de etiqueta para o(s) pedido(s) ".$pedidos);

					} else {
						$query    = $this->_conn->query("COMMIT TRANSACTION");
						$response = array("resultado" 	   => true,
										"etiqueta"         => $etiqueta['etiqueta'],
										"etiqueta_servico" => $etiqueta['etiqueta_servico']);
						return $response;
					}
				}
			}
		}		
	}

	public function buscaEtiquetaGerada($dados){
		$pedido = $dados['pedido'];

		$sql = "SELECT etiqueta FROM tbl_etiqueta_servico WHERE fabrica = {$this->_fabrica} AND pedido IN $pedido";
		
		$query  = $this->_conn->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);
        $res = array_filter($res);

		if(count($res) > 0){
			$response = array(
				"resultado" => true,
				"msg"       => $res["etiqueta"]
			);
			return $response;
		}
	}

	public function solicitaEtiquetasCorreios($servico,$chave_servico,$quantidade_etiqueta = null){

		try{
			if (in_array($this->_fabrica, [11,172])){
				$dados_contrato = $this->dadosContrato("aulik");
			}else{
				$dados_contrato = $this->dadosContrato("producao");
				// $dados_contrato = $this->dadosContrato("usuario");
			}

			$quantidade_etiqueta = (!empty($quantidade_etiqueta)) ? $quantidade_etiqueta : 10;

			$msg_erro            = "";
			$tipoDestinatario    = 'C';
			$identificador       = ($this->_fabrica == 186) ? "22104417000137" : $dados_contrato['cnpj'];
			$idServico           = $chave_servico;
			$qtdEtiquetas        = $quantidade_etiqueta;
			$usuario             = $dados_contrato['usuario'];
			$senha               = $dados_contrato['senha'];

			$array_request = (object) array('tipoDestinatario' => $tipoDestinatario,
				'identificador'	   => $identificador,
				'idServico'		   => $idServico,
				'qtdEtiquetas'	   => $qtdEtiquetas,
				'usuario'	 	   => $usuario,
				'senha'	 		   => $senha
			);


			try {
				$headersCorreios = $this->headersCorreios();
				$client = new \SoapClient($this->_url, $headersCorreios);

			} catch (\Exception $e) {
				$response = array("resultado" => "false", "msg" => $e->getMessage());
				throw new \Exception($e->getMessage());
			}

		    try{
		    	$result = $client->__soapCall("solicitaEtiquetas", array($array_request));
		    } catch (\Exception $e) {
		    	$response = array("resultado" => "false", "msg" => $e->getMessage());
		    	throw new \Exception($e->getMessage());
		    	
			}

		    $resultServico = $result->return;

			$resultServico   = explode(",", $resultServico);
			$etiqueta_inicio = explode(" ", $resultServico[0]);
			$etiqueta_final  = explode(" ", $resultServico[1]);
			$sigla           = substr($etiqueta_inicio[0], 0, 2);
			$etiqueta_inicio = substr($etiqueta_inicio[0], 2);
			$etiqueta_final  = substr($etiqueta_final[0], 2);

		    if($etiqueta_inicio <= $etiqueta_final){

			    $xmlEtiqueta;
			    $cont = $etiqueta_inicio;
			    $zero = substr($cont,0,1);

			    if($zero != "0"){
			    	$zero = "";
			    }

			    $xmlEtiqueta['etiquetas'][] = $sigla."".$etiqueta_inicio."BR";

			    for($i = 1; $i < $quantidade_etiqueta; $i++){
			    	$xmlEtiqueta['etiquetas'][] = $sigla.$zero.($etiqueta_inicio+$i)."BR";
			    }
			    
			    $xmlEtiqueta['usuario'] = $usuario;
				$xmlEtiqueta['senha']   = $senha;

				$array_digito = (object) $xmlEtiqueta;

				/*
				*
				* GERA O DIGITO VERIFICADOR DAS ETIQUETAS
				*
				*/
				try{
			    	$result = $client->__soapCall("geraDigitoVerificadorEtiquetas", array($array_digito));
			    } catch (Exception $e) {
			    	$response[] = array("resultado" => "false", $e->getMessage());
			    	throw new \Exception($e->getMessage());
				}

			    $resultDigito = $result->return;
			    $i=0;

			    while($etiqueta_inicio <= $etiqueta_final){
			    	if($i == 0){
			    		$sql = "INSERT INTO tbl_etiqueta_servico (servico_correio, etiqueta,digito,fabrica) VALUES ('".$servico."','".$sigla.$etiqueta_inicio.$resultDigito[$i]."BR',".$resultDigito[$i].", {$this->_fabrica})";
			    	}else{
			    		$sql = "INSERT INTO tbl_etiqueta_servico (servico_correio, etiqueta,digito,fabrica) VALUES ('".$servico."','".$sigla.$zero.$etiqueta_inicio.$resultDigito[$i]."BR',".$resultDigito[$i].", {$this->_fabrica})";
			    	}
			    	$query  = $this->_conn->query($sql);

			    	$i++;
					$etiqueta_inicio++;

			    	if (strlen(pg_last_error()) > 0) {
			    		continue; 
	                }else{
	                	$response = array("resultado" => "true");
	                }
			    }
			}

		    if($response != ""){
		    	return $response;
		    }else{
		    	throw new \Exception("Não foi possível gravas as novas etiquetas no sistema!");
		    }
		}catch(Exception $e){
			$response[] = array("resultado" => "false","msg" => $e->getMessage());
			throw new \Exception($e->getMessage());
		}
	}

	function gravaIdPLP($plp,$etiquetas){

		$this->_conn->query("BEGIN TRANSACTION");

		$sql   = "INSERT INTO tbl_lista_postagem (plp_correio) VALUES ('".$plp."') RETURNING lista_postagem";
		$query = $this->_conn->query($sql);

		if (pg_last_error() > 0 ) {
			$res = pg_query($this->_conn,"ROLLBACK TRANSACTION");
			$response = array(
				"resultado"   => false,
				"faultstring" => "Ocorreu um erro ao gravar no banco a nova Pré-Lista de Postagem, tente novamente mais tarde!"
			);
			return $response;

		}else{
			$res   = $query->fetch(\PDO::FETCH_ASSOC);
			$idplp = $res['lista_postagem'];
			$this->_conn->query("COMMIT TRANSACTION");
			$response["resultado"] = true;
		}

		$etiquetas     = explode(",",$etiquetas);
		$countEtiqueta = count($etiquetas);
		$this->_conn->query("BEGIN TRANSACTION");

		for($i=0; $i<$countEtiqueta; $i++){
			$sql = "INSERT INTO tbl_plp_etiqueta (lista_postagem, etiqueta_servico) VALUES (".$idplp.",(SELECT etiqueta_servico FROM tbl_etiqueta_servico WHERE etiqueta = '".$etiquetas[$i]."'))";
			$query = $this->_conn->query($sql);
		}

		if (pg_last_error() > 0 ) {
			$this->_conn->query("ROLLBACK TRANSACTION");
	    	$response = array(
				"resultado"   => false,
				"faultstring" => "Ocorreu um erro ao gravar no banco a nova Pré-Lista de Postagem, tente novamente mais tarde!"
			);
			return $response;

		}else{
			$this->_conn->query("COMMIT TRANSACTION");
			$response = array(
				"resultado" => true,
				"idplp"     => $idplp
			);
		}
		return $response;
	}

	public function headersCorreios(){

		$array_protocolo['protocol_version'] = '1.0';
		$array_protocolo['header'] = 'Connection: Close';
		$http["http"] = $array_protocolo;

		return array(
						"trace" 				=> 1, 
						"connection_timeout" 	=> 30,
	                    "stream_context"		=>	stream_context_create($http)
				);

	}
}
