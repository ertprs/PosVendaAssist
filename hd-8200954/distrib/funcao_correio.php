<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (filter_input(INPUT_GET,'uso') == "admin") {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// // use correios\model\AccessData;
// use PhpSigep\model\Diretoria;
// use PhpSigep\model\Remetente;
// use PhpSigep\model\Etiqueta;
// use PhpSigep\model\ServicoDePostagem;
// use PhpSigep\model\Destinatario;
// use PhpSigep\model\DestinoNacional;
// use PhpSigep\model\ServicoAdicional;
// use PhpSigep\model\Dimensao;
// use PhpSigep\model\ObjetoPostal;
// use PhpSigep\model\PreListaDePostagem;

// $url = "http://webservicescol.correios.com.br/ScolWeb/WebServiceScol?wsdl";
//$url['producao'] = "https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";
$url['producao'] = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

$funcao = $_GET['funcao'];
$resultado = "";

$fabrica = (!in_array($login_fabrica, [11,160,172,187])) ? 81 : $login_fabrica;

switch ($funcao) {
	case 'buscaCliente': $resultado            = buscaCliente($fabrica,$url['producao']);               break;
	case 'buscaEtiquetaBanco': $resultado      = buscaEtiquetaBanco($fabrica);                          break;
	case 'buscaEtiquetaTransportadoraBanco': $resultado = buscaEtiquetaTransportadoraBanco($fabrica);   break;
	case 'calcPrecoPrazo': $resultado          = calcPrecoPrazo($url['producao']);                      break;
	case 'solicitaEtiquetas': $resultado       = solicitaEtiquetas($_GET["fabrica"], $url['producao']); break;
	case 'buscaEmbarque': $resultado           = buscaEmbarque("","Inicio");                            break;
	case 'gerarEtiqueta': $resultado           = gerarEtiqueta($fabrica, $etiqueta);                    break;
	case 'validarEmbarque': $resultado  	   = validarEmbarque();                                     break;
	case 'inserirObjetoPLP': $resultado        = inserirObjetoPLP($url['producao']);                    break;
	case 'removerObjetoPLP': $resultado        = removerObjetoPLP();                                    break;
	case 'gravaIdPLP': $resultado              = gravaIdPLP();                                          break;
	case 'consultarPLP': $resultado            = consultarPLP();                                        break;
	case 'consultaServicoContrato': $resultado = consultaServicoContrato($url['producao']);             break;
	case 'imprimePLP': $resultado 			   = imprimePLP();                                          break;
}

if($resultado != ""){
	echo json_encode($resultado);
}

function consultaBanco($tipoSelect,$aux){
	global $con, $login_posto;

	switch ($tipoSelect) {
		case 'remetente': $sql = "SELECT logo,
				tbl_fabrica.endereco,
				tbl_fabrica.cidade,
				tbl_fabrica.estado,
				tbl_fabrica.fone,
				tbl_fabrica.cep,
				tbl_fabrica.cnpj,
				tbl_fabrica.razao_social FROM tbl_posto_fabrica
					JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
				WHERE tbl_posto_fabrica.fabrica = 81";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)>0) {
				$dados_acesso = array("nome" => retira_acentos(pg_fetch_result($res,0,"razao_social")),
					"cidade" 		=> retira_acentos(pg_fetch_result($res,0,"cidade")),
					"cnpj" 			=> utf8_encode(pg_fetch_result($res,0,"cnpj")),
					"estado" 		=> utf8_encode(pg_fetch_result($res,0,"estado")),
					"fone" 			=> utf8_encode(pg_fetch_result($res,0,"fone")),
					"cep" 			=> utf8_encode(pg_fetch_result($res,0,"cep")),
					"endereco" 		=> retira_acentos(pg_fetch_result($res,0,"endereco")),
					"numero" 		=> "420-A",
					"bairro" 		=> utf8_encode(retira_acentos(pg_fetch_result($res,0,"bairro"))));
			}
			break;

		case 'producao': $sqlAcesso = "";
			$sqlAcesso = "SELECT id_correio AS usuario,
					senha,
					codigo as codigo_administrativo,
					contrato,
					cartao,
					cnpj,
					cep,
					(SELECT date_part('year', current_date)) AS ano
				FROM tbl_fabrica_correios
					JOIN (SELECT fabrica AS fab, cnpj, cep FROM tbl_fabrica
					) AS tbl_fabrica ON tbl_fabrica.fab = tbl_fabrica_correios.fabrica
				WHERE fabrica = $aux";
			$res = pg_query($con, $sqlAcesso);

			if (pg_num_rows($res)>0) {
				$dados_acesso = pg_fetch_array($res);
				
				$senha = pg_fetch_result($res,0,'senha');
				$senha = (in_array($aux,array(10,11,147,153,160,172,187))) ? $senha : "eagojr";

				 $dados_acesso = array("usuario" => pg_fetch_result($res,0,"usuario"),
				 	"senha" 				=> $senha,
				 	"codigo_administrativo" => pg_fetch_result($res,0,"codigo_administrativo"),
				 	"contrato" 				=> pg_fetch_result($res,0,"contrato"),
				 	"cartao" 				=> pg_fetch_result($res,0,"cartao"),
				 	"cnpj" 					=> pg_fetch_result($res,0,"cnpj"),
				 	"cep" 					=> pg_fetch_result($res,0,"cep"),
				 	"ano" 					=> date('Y')//pg_fetch_result($res,0,ano));
				);
				
			}
			break;

		case 'servico': $sqlAcesso = "SELECT tbl_servico_correio.servico_correio,
					descricao,
					codigo,
					chave_servico
				FROM tbl_servico_correio
					JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio = tbl_servico_correio.servico_correio
				WHERE fabrica = $aux AND tbl_servico_correio.ativo = 't'";
			$res = pg_query($con, $sqlAcesso);

			if (pg_num_rows($res)>0) {
				$dados_acesso = $res;
			}
			break;

		case 'posto': $sqlAcesso = "";
				$sqlAcesso = "SELECT TO_CHAR (tbl_embarque.data,'DD/MM') AS data_embarque,
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
				$dados_acesso = array("usuario" => '66494691000135',
					"senha" 				=> 'eagojr',
					"codigo_administrativo" => '14341190',
					"contrato" 				=> '9912358441',
					// "cartao" 				=> '0069835535',
					"cnpj" 					=> '66494691000135',
					"ano" 					=> date('Y'));
				break;
		
		case 'einhell':
			$dados_acesso = array("usuario" 		=> '67647412000199',
				              "senha"                   => 'ir31um',
				              "codigo_administrativo" 	=> '13286013',
				              "contrato"                => '9912329529',
				              "cartao"                  => '0073789933',
				              "cnpj"                    => '67647412000199',
				              "ano"                     => date('Y'));

			break;
		
		case 'aulik':
			$dados_acesso = array("usuario" 		=> '05256426',
				              "senha"                   => 'n4nov',
				              "codigo_administrativo" 	=> '14407655',
				              "contrato"                => '9912361764',
				              "cartao"                  => '0074607855',
				              "cnpj"                    => '05256426000124',
				              "ano"                     => date('Y'));
			break;
		case 'positec':
			$dados_acesso = array("usuario" 		=> '17835389000198',
				              "senha"                   => 'rwzvap',
				              "codigo_administrativo" 	=> '19235690',
				              "contrato"                => '9912471470',
				              "cartao"                  => '0075061430',
				              "cnpj"                    => '17835389000198',
				              "ano"                     => date('Y'));
			break;

		case 'telecontrol':
			$dados_acesso = array("usuario" 		=> '66494691000135',
				              "senha"                   => 'eagojr',
				              "codigo_administrativo" 	=> '14341190',
				              "contrato"                => '9912358441',
				              "cartao"                  => '0069835810',
				              "cnpj"                    => '66494691000135',
				              "ano"                     => date('Y'));
			break;
		case 'positron':
				$dados_acesso = array("usuario" => '84496066000104',
					"senha" 				=> '8so0ll',
					"codigo_administrativo" => '14461323',
					"contrato" 				=> '9912364447',
					// "cartao" 				=> '0071856978',
					"cnpj" 					=> '84496066000104',
					"ano" 					=> date('Y'));
			break;

		case 'desenvolvimento':
				$dados_acesso =  array('usuario'=> 'sigep',
					'senha'			    	=> 'n5f9t8',
					'codigo_administrativo' => '08082650',
					'contrato'				=> '9912208555',
					'cartao'				=> '0057018901',
					'cnpj'			    	=> '34028316000103',
					'ano'			    	=> date('Y'));
			break;

		default: $dados_acesso =  array('usuario'=> 'sigep',
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

/*
*
* BUSCA INFORMAÇÕES DO CONTRATO NO SERVIDOR DOS CORREIOS
*
*/
function buscaCliente($posto,$url){
	$dados_cliente = consultaBanco("posto", $posto);

	$array_request = (object) array('usuario'=> $dados_cliente['usuario'],
		'senha'			   => $dados_cliente['senha'],
		'idContrato'	   => $dados_cliente['contrato'],
		'idCartaoPostagem' => $dados_cliente['cartao']
	);

	// $url = "https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

	try {
		$client = new SoapClient($url, array("trace" => 1, "connection_timeout" => 30,
				'stream_context'=>stream_context_create(
					array('http'=>
					array(
						'protocol_version'=>'1.0',
						'header' => 'Connection: Close'
						)
					)
				)
			));
	} catch (Exception $e) {
		$response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");
    	return $response;
	}

    $result = "";
    try {
		$result = $client->__soapCall("buscaCliente", array($array_request));
	} catch (Exception $e) {
		$response[] = array("resultado" => "false", array($e));
    	return $response;
	}
	$resultServico = $result->return->contratos->cartoesPostagem->servicos;
	$resposta      = array();
	$count         = count($resultServico);

    for($i = 0; $i<$count; $i++){
		$codigo_servico            = str_replace(" ","",$resultServico[$i]->codigo);
		$descricao                 = $resultServico[$i]->descricao;
		$resposta[$codigo_servico] = str_replace("  ","",$resultServico[$i]->descricao);
    	// $resposta[$i] = array("codigo" => $codigo_servico,
    	// 	str_replace(" ","",$resultServico[$i]->codigo) => str_replace("  ","",$resultServico[$i]->descricao));
    }

    if($resposta != ""){
    	return $resposta;
    }else{
    	return "Não existe serviço disponível neste contrato!";
    }
}

/*
*
* BUSCA INFORMAÇÕES DO CONTRATO NO SERVIDOR DOS CORREIOS
*
*/
function buscaServicos($posto, $url){
	global $con;

	$dados_servicos = consultaBanco("servico",$posto);

	if(pg_num_rows($dados_servicos) == 0){
		$result = consultaServicoContrato($url,$posto);

		if($result["resultado"] == false){
			return $result;
		}
		$dados_servicos = consultaBanco("servico",$posto);
	}

	$countServico = pg_num_rows($dados_servicos);

	for($i=0; $i<$countServico; $i++){
		$codigo_servico = pg_fetch_result ($dados_servicos,$i,"codigo");
		$descricao      = pg_fetch_result ($dados_servicos,$i,"descricao");
		$chave_servico  = pg_fetch_result ($dados_servicos,$i,"chave_servico");

    	$resposta[] 	= array("descricao" => $descricao,
			"codigo"        => $codigo_servico,
			"chave_servico" => $chave_servico);
	}

	return $resposta;
}

function consultaServicoContrato($url,$fabrica = null){
	global $con;
	$fabrica = (!in_array($fabrica, [11,123,160,172,187])) ? 81 : $fabrica;
	$msg_erro = "";
	// $dados_cliente = consultaBanco("producao",$posto);

	 if($fabrica == 160){
	 	$array_request = (object) array('usuario'=> "67647412000199", //$dados_cliente['usuario'],
			'senha'			   => "ir31um",
			'idContrato'	   => "9912329529",
	 		'idCartaoPostagem' => "0073789933"
		);
	}else if(in_array($fabrica, [11,172])){
	 	$array_request = (object) array('usuario'=> "05256426", //$dados_cliente['usuario'],
			'senha'			   => "n4nov",
			'idContrato'	   => "9912361764",
	 		'idCartaoPostagem' => "0074607855"
		);
	}else if($fabrica == 10){
 	$array_request = (object) array('usuario'=> "66494691000135", //$dados_cliente['usuario'],
		'senha'			   => "eagojr",
		'idContrato'	   => "9912358441",
 		'idCartaoPostagem' => "0069835810"
	);
	}else if($fabrica == 187){
		$array_request = (object) array('usuario'=> "conair", //$dados_cliente['usuario'],
			'senha'			   => "4md13g",
			'idContrato'	   => "9912390259",
	 		'idCartaoPostagem' => "0074778145"
		);

	}else if($fabrica == 122){
		$array_request = (object) array('usuario'=> "66494691000135", //$dados_cliente['usuario'],
			'senha'                    => "eagojr",
			'idContrato'       => "9912358441",
			'idCartaoPostagem' => "0069835780"
		);
	
	}else if($fabrica == 123){
		$array_request = (object) array('usuario'=> "17835389000198", //$dados_cliente['usuario'],
			                  'senha'                    => "rwzvap",
			                  'idContrato'       => "9912471470",
			                  'idCartaoPostagem' => "0075061430"
			          );

	}else{
		$array_request = (object) array('usuario'=> "66494691000135",
			'senha'			   => "eagojr",
			'idContrato'	   => "9912358441",
			'idCartaoPostagem' => "0069835535");
	 }

	try {
		$client = new SoapClient($url, array("trace" => 1, "connection_timeout" => 30,
				'stream_context'=>stream_context_create(
					array('http'=>
					array(
						'protocol_version'=>'1.0',
						'header' => 'Connection: Close'
						)
					)
				)
			));

	} catch (Exception $e) {
		$response[] = array("resultado" => false, array($e));
    	return $response;
	}

    $result = "";
    try {
		$result = $client->__soapCall("buscaServicos", array($array_request));
	} catch (Exception $e) {
		$response[] = array("resultado" => false, array($e));
    	return $response;
	}

    $resultServico = $result->return;
    $resposta = array();

	if(count($resultServico) > 0){
    	$res = pg_query($con,"BEGIN TRANSACTION");
	    $countServico = count($resultServico);

	    for($i = 0; $i<$countServico; $i++){
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

		    $descricao 	    = $resultServico[$i]->descricao;
		    $chave_servico  = $resultServico[$i]->id;

		    $sql = "SELECT servico_correio FROM tbl_servico_correio JOIN tbl_servico_correio_fabrica USING(servico_correio) WHERE codigo LIKE '$codigo_servico' AND chave_servico = '{$chave_servico}' AND fabrica = $fabrica";
		    $resServicoCorreio = pg_query($con,$sql);
		    
		    if(pg_num_rows($resServicoCorreio) == 0){

			$sql = "SELECT servico_correio FROM tbl_servico_correio WHERE codigo LIKE '$codigo_servico' AND chave_servico = '{$chave_servico}'";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 0){
		   		$sql = "INSERT INTO tbl_servico_correio (descricao, codigo, chave_servico) VALUES ('".$descricao."','".$codigo_servico."','$chave_servico') RETURNING servico_correio";
				$res = pg_query($con, $sql);
			}

		    	if (strlen(pg_last_error()) > 0 ) {
					$msg_erro = "erro";
					break;
				}

				if($msg_erro == ""){
					$servico_correio = pg_fetch_result($res, 0, "servico_correio");

			    	$sql = "INSERT INTO tbl_servico_correio_fabrica (fabrica, servico_correio) VALUES ({$fabrica},{$servico_correio})";
			    	$res = pg_query($con, $sql);

			    	if (strlen(pg_last_error()) > 0 ) {
						$msg_erro = "erro";
						break;
					}
			    }
			}
	    }
	    if($msg_erro == ""){
	    	$res = pg_query ($con,"COMMIT TRANSACTION");
	    	$resposta = array("resultado" => true);
	    }else{
	    	$res = pg_query ($con,"ROLLBACK TRANSACTION");
	    	$resposta = array("resultado" => false,
	    		"faultstring" => utf8_encode("Ocorreu um erro ao gravar os serviços disponíveis do correio no sistema, tente novamente mais tarde!"));
	    }
    }else{
    	unset($resposta);
    	$resposta = array("resultado" => false,
    		"faultstring" => utf8_encode("ERRO ao consultar os serviços no webservice dos correios, tente novamente mais tarde!"));
    }
    return $resposta;
}

/*
*
* REALIZA O LINK DA ETIQUETA COM O NÚMERO DO EMBARQUE
*
*/
function buscaEtiquetaTransportadoraBanco($posto){
	global $con;

	$classCorreios = new \Posvenda\Correios($_GET['fabrica']);

	try {

		$retorno = $classCorreios->etiquetaTransportadora([
			'codigo'           		=> $_GET['codigo'],
			'valor'            		=> $_GET['valor'],
			'peso'             		=> $_GET['peso'],
			'caixa'            		=> $_GET['caixa'],
			'embarque'         		=> $_GET['embarque'],
			'prazo'            		=> $_GET['prazo'],
			'frete_transportadora' 	=> $_GET['frete_transportadora'],
			'fabrica'          		=> $_GET['fabrica'],
		]);

		$response[] = [
			"resultado" => "true",
			"frete_transportadora" => $retorno["frete_transportadora"],
			"etiqueta" => $retorno["etiqueta"]
		];

		return $response;

	} catch (\Exception $e) {
	    
		$response[] = [
			"resultado" => "false",
			"mensagem" => utf8_encode($e->getMessage())
		];

	}
	
	return $response;

}

/*
*
* REALIZA O LINK DA ETIQUETA COM O NÚMERO DO EMBARQUE
*
*/
function buscaEtiquetaBanco($posto){
	global $con;

	$codigo           = $_GET['codigo'];
	$valor            = $_GET['valor'];
	$peso             = $_GET['peso'];
	$caixa            = $_GET['caixa'];
	$embarque         = $_GET['embarque'];
	$prazo            = $_GET['prazo'];
	$etiqueta_servico = $_GET['etiqueta_servico'];
	$fabrica          = $_GET['fabrica'];

	if (in_array($fabrica, [11,122,123,160,172])) {
		if (!empty($embarque)) {
			$sql_garantia = "SELECT embarque FROM tbl_embarque WHERE embarque = $embarque AND fabrica = $fabrica AND garantia IS TRUE";
			$res_garantia = pg_query($con, $sql_garantia);
			if (pg_num_rows($res_garantia) > 0) {
				if (in_array($fabrica, [11,172])) {
					$fabrica_contrato = 11;
				} elseif ($fabrica == 160) {
					$fabrica_contrato = 160;
				} else if ($fabrica == 122){
					$fabrica_contrato = 122;
				}else if ($fabrica == 123) {
					$fabrica_contrato = 123;
				}
			} else {
				$fabrica_contrato = 10;	
			}
		} else {
			if (in_array($fabrica, [11,172])) {
				$fabrica_contrato = 11;
			} elseif ($fabrica == 160) {
				$fabrica_contrato = 160;
			} else if($fabrica == 122) {
				$fabrica_contrato = 122;
			}else if($fabrica == 123){
				$fabrica_contrato = 123;
			}
		}
	}

	/*if($codigo == 40444){
		$fabrica_contrato = 153;
	}else{
		$fabrica_contrato = 81;
	}*/

	if (empty($fabrica_contrato)) {
		$fabrica_contrato = 81;
	}

	if(strripos($peso, ",") == true){
		$peso = str_replace(",", ".", $peso);
	}

	$sql = "SELECT etiqueta_servico, etiqueta FROM tbl_etiqueta_servico
			JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio = tbl_etiqueta_servico.servico_correio
			JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio = tbl_etiqueta_servico.servico_correio
		WHERE tbl_servico_correio_fabrica.fabrica = $fabrica_contrato
			and (tbl_etiqueta_servico.fabrica isnull or tbl_etiqueta_servico.fabrica = $fabrica_contrato)
			AND tbl_servico_correio.codigo = lpad('$codigo',5,'0')
			AND embarque IS NULL ORDER BY etiqueta_servico limit 1";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$etiqueta = pg_fetch_array($res);
		$msg_erro = "";

		$res = pg_query($con,"BEGIN TRANSACTION");

		if($etiqueta_servico != ""){
			$sql = "UPDATE tbl_etiqueta_servico SET embarque = NULL, preco = NULL, peso = NULL, caixa = NULL WHERE etiqueta_servico = ".$etiqueta_servico;
			$res = pg_query($con, $sql);

			if (pg_last_error() > 0 ) {
				$msg_erro = "erro";
			}
		}

		if (empty($prazo)) {
			$prazo = 0;
		}

		$sql = "DELETE FROM tbl_frete_transportadora WHERE embarque = {$embarque}";
		$res = pg_query($con, $sql);

		$sql = "UPDATE tbl_etiqueta_servico SET
				embarque = ".$embarque.",
				preco    = ".$valor.",
				peso     = ".$peso.",
				caixa    = '".$caixa."',
				prazo_entrega = ".$prazo."
			WHERE etiqueta_servico = ".$etiqueta['etiqueta_servico'];
		$res = pg_query($con, $sql);

    	if (pg_last_error() > 0 ) {
			$msg_erro = "erro";
		}

		if($msg_erro == ""){
			$res        = pg_query ($con,"COMMIT TRANSACTION");
			$response[] = array("resultado" => "true",
				"etiqueta"         => $etiqueta['etiqueta'],
				"etiqueta_servico" => $etiqueta['etiqueta_servico']);
	    }else{
	    	$res = pg_query ($con,"ROLLBACK TRANSACTION");
	    	//Erro ao tentar atualizar os dados da etiqueta com as informações do embarque.
			$response[] = array("resultado" => "falseErroBanco",
				"mensagem" => utf8_encode("Ocorreu um erro durante a gravação da etiqueta no sistema, tente novamente mais tarde!"));
	    }

		return $response;
	}else{
		$response[] = array("resultado" => "falseEtiqueta", "mensagem" => utf8_encode("Não há etiqueta disponivel para esse serviço, favor solicitar mais etiqueta!"));
		return $response;
	}
}

/*
*
* SOLICITA UMA QUANTIDADE DE ETIQUETAS AO SERVIDOR DOS CORREIOS
*
*/
function solicitaEtiquetas($posto, $url){
	global $con;

	$fabrica_contrato    = $_GET['fabrica'];
	$servico             = $_GET['servico'];
	$codigo_servico      = $_GET['codigo'];
	$quantidade_etiqueta = $_GET['quantidade'];
	$chave_servico       = $_GET['chave_servico'];

	if($fabrica_contrato == 153){
		$dados_contrato = consultaBanco("positron",$posto);
	}elseif (in_array($fabrica_contrato, [11,172])){
		$dados_contrato = consultaBanco("aulik",$posto);
	}elseif ($fabrica_contrato == 10){
		$dados_contrato = consultaBanco("telecontrol",$posto);
	}else{
		$dados_contrato = consultaBanco("producao",$posto);
	}

	$msg_erro            = "";
	$tipoDestinatario    = 'C';
	$identificador       = $dados_contrato['cnpj'];
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

	// $url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

	try {
		$client = new SoapClient($url, array("trace" => 1, "connection_timeout" => 30,
				'stream_context'=>stream_context_create(
					array('http'=>
					array(
						'protocol_version'=>'1.0',
						'header' => 'Connection: Close'
						)
					)
				)
			));
	} catch (Exception $e) {
		$response[] = array("resultado" => "false", array($e));
    	return $response;
	}


    try{
    	$result = $client->__soapCall("solicitaEtiquetas", array($array_request));
    } catch (Exception $e) {
    	$response[] = array("resultado" => "false", array($e));
    	return $response;
	}

    $resultServico = $result->return;

	$resultServico   = explode(",", $resultServico);
	$etiqueta_inicio = explode(" ", $resultServico[0]);
	$etiqueta_final  = explode(" ", $resultServico[1]);
	$sigla           = substr($etiqueta_inicio[0], 0, 2);
	$etiqueta_inicio = substr($etiqueta_inicio[0], 2);
	$etiqueta_final  = substr($etiqueta_final[0], 2);

    if($etiqueta_inicio <= $etiqueta_final){
    	$res = pg_query($con,"BEGIN TRANSACTION");

	    $xmlEtiqueta;
	    $cont = $etiqueta_inicio;
	    $zero = substr($cont,0,1);

	    if($zero != "0"){
	    	$zero = "";
	    }

	    switch ($quantidade_etiqueta) {
	    	case '10': $xmlEtiqueta['etiquetas'] = array($sigla."".$cont."BR",
	    		$sigla.$zero.($cont+1)."BR",
	    		$sigla.$zero.($cont+2)."BR",
	    		$sigla.$zero.($cont+3)."BR",
	    		$sigla.$zero.($cont+4)."BR",
	    		$sigla.$zero.($cont+5)."BR",
	    		$sigla.$zero.($cont+6)."BR",
	    		$sigla.$zero.($cont+7)."BR",
	    		$sigla.$zero.($cont+8)."BR",
	    		$sigla.$zero.($cont+9)."BR");
	    		break;

	    	case '30': $xmlEtiqueta['etiquetas'] = array($sigla."".$cont."BR",
	    		$sigla.$zero.($cont+1)."BR",
	    		$sigla.$zero.($cont+2)."BR",
	    		$sigla.$zero.($cont+3)."BR",
	    		$sigla.$zero.($cont+4)."BR",
	    		$sigla.$zero.($cont+5)."BR",
	    		$sigla.$zero.($cont+6)."BR",
	    		$sigla.$zero.($cont+7)."BR",
	    		$sigla.$zero.($cont+8)."BR",
	    		$sigla.$zero.($cont+9)."BR",
	    		$sigla.$zero.($cont+10)."BR",
	    		$sigla.$zero.($cont+11)."BR",
	    		$sigla.$zero.($cont+12)."BR",
	    		$sigla.$zero.($cont+13)."BR",
	    		$sigla.$zero.($cont+14)."BR",
	    		$sigla.$zero.($cont+15)."BR",
	    		$sigla.$zero.($cont+16)."BR",
	    		$sigla.$zero.($cont+17)."BR",
	    		$sigla.$zero.($cont+18)."BR",
	    		$sigla.$zero.($cont+19)."BR",
	    		$sigla.$zero.($cont+20)."BR",
	    		$sigla.$zero.($cont+21)."BR",
	    		$sigla.$zero.($cont+22)."BR",
	    		$sigla.$zero.($cont+23)."BR",
	    		$sigla.$zero.($cont+24)."BR",
	    		$sigla.$zero.($cont+25)."BR",
	    		$sigla.$zero.($cont+26)."BR",
	    		$sigla.$zero.($cont+27)."BR",
	    		$sigla.$zero.($cont+28)."BR",
	    		$sigla.$zero.($cont+29)."BR");
	    		break;

	    	case '50': $xmlEtiqueta['etiquetas'] = array($sigla."".$cont."BR",
	    		$sigla.$zero.($cont+1)."BR",
	    		$sigla.$zero.($cont+2)."BR",
	    		$sigla.$zero.($cont+3)."BR",
	    		$sigla.$zero.($cont+4)."BR",
	    		$sigla.$zero.($cont+5)."BR",
	    		$sigla.$zero.($cont+6)."BR",
	    		$sigla.$zero.($cont+7)."BR",
	    		$sigla.$zero.($cont+8)."BR",
	    		$sigla.$zero.($cont+9)."BR",
	    		$sigla.$zero.($cont+10)."BR",
	    		$sigla.$zero.($cont+11)."BR",
	    		$sigla.$zero.($cont+12)."BR",
	    		$sigla.$zero.($cont+13)."BR",
	    		$sigla.$zero.($cont+14)."BR",
	    		$sigla.$zero.($cont+15)."BR",
	    		$sigla.$zero.($cont+16)."BR",
	    		$sigla.$zero.($cont+17)."BR",
	    		$sigla.$zero.($cont+18)."BR",
	    		$sigla.$zero.($cont+19)."BR",
	    		$sigla.$zero.($cont+20)."BR",
	    		$sigla.$zero.($cont+21)."BR",
	    		$sigla.$zero.($cont+22)."BR",
	    		$sigla.$zero.($cont+23)."BR",
	    		$sigla.$zero.($cont+24)."BR",
	    		$sigla.$zero.($cont+25)."BR",
	    		$sigla.$zero.($cont+26)."BR",
	    		$sigla.$zero.($cont+27)."BR",
	    		$sigla.$zero.($cont+28)."BR",
	    		$sigla.$zero.($cont+29)."BR",
	    		$sigla.$zero.($cont+30)."BR",
	    		$sigla.$zero.($cont+31)."BR",
	    		$sigla.$zero.($cont+32)."BR",
	    		$sigla.$zero.($cont+33)."BR",
	    		$sigla.$zero.($cont+34)."BR",
	    		$sigla.$zero.($cont+35)."BR",
	    		$sigla.$zero.($cont+36)."BR",
	    		$sigla.$zero.($cont+37)."BR",
	    		$sigla.$zero.($cont+38)."BR",
	    		$sigla.$zero.($cont+39)."BR",
	    		$sigla.$zero.($cont+40)."BR",
	    		$sigla.$zero.($cont+41)."BR",
	    		$sigla.$zero.($cont+42)."BR",
	    		$sigla.$zero.($cont+43)."BR",
	    		$sigla.$zero.($cont+44)."BR",
	    		$sigla.$zero.($cont+45)."BR",
	    		$sigla.$zero.($cont+46)."BR",
	    		$sigla.$zero.($cont+47)."BR",
	    		$sigla.$zero.($cont+48)."BR",
	    		$sigla.$zero.($cont+49)."BR");
	    		break;
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
	    	$response[] = array("resultado" => "false", array($e));
	    	return $response;
		}

	    $resultDigito = $result->return;
	    $i=0;

	    while($etiqueta_inicio <= $etiqueta_final){
	    	if($i == 0){
	    		$sql = "INSERT INTO tbl_etiqueta_servico (servico_correio, etiqueta,digito,fabrica) VALUES ('".$servico."','".$sigla.$etiqueta_inicio.$resultDigito[$i]."BR',".$resultDigito[$i].", $fabrica_contrato)";
	    	}else{
	    		$sql = "INSERT INTO tbl_etiqueta_servico (servico_correio, etiqueta,digito,fabrica) VALUES ('".$servico."','".$sigla.$zero.$etiqueta_inicio.$resultDigito[$i]."BR',".$resultDigito[$i].", $fabrica_contrato)";
	    	}
	    	$res = pg_query($con, $sql);

	    	if (pg_last_error() > 0 ) {
				$msg_erro = "erro";
				break;
			}
			$i++;
			$etiqueta_inicio++;
	    }

	    if($msg_erro == ""){
			$res                   = pg_query ($con,"COMMIT");
			$response["resultado"] = "true";
	    }else{
			$res        = pg_query ($con,"ROLLBACK");
			$response[] = array("resultado" => "falseErroBanco",
				"faultstring" => "Ocorreu um erro ao gravar as novas etiquetas no banco, tente novamente mais tarde!");
	    }
	}

    if($response != ""){
    	return $response;
    }else{
    	return "Não foi possível gravas as novas etiquetas no sistema!";
    }
}

/*
*
* TELA DE EMBARQUE - REALIZA O CÁLCULO DO FRETE E DO PRAZO DE ENTREGA PARA CADA
	TIPO SERVIÇO DISPONÍVEL PARA O ENVIO DO EMBARUE
*
*/
function calcPrecoPrazo($urlServico){
	global $con;

	$fabrica_contrato = (!in_array($_GET["fabrica"], [11,153,160,172,187])) ? 81 : $_GET["fabrica"];
	$msg_erro = "";


	$servicoDisponivel = buscaServicos($fabrica_contrato, $urlServico);

	if(isset($servicoDisponivel[0]["resultado"])){
		return $servicoDisponivel;
	}

	if($fabrica_contrato == 153){
		$dadosRemetente = consultaBanco("positron",$fabrica_contrato);
	}else if($fabrica_contrato == 160){
		$dadosRemetente = consultaBanco("einhell",$fabrica_contrato);
	}else if(in_array($fabrica_contrato, [11,172])){
		$dadosRemetente = consultaBanco("aulik",$fabrica_contrato);
	}else{
		$dadosRemetente = consultaBanco("producao",$fabrica_contrato);
	}

	$cepOrigem             = "17519255";
	$codigo_administrativo = $dadosRemetente['codigo_administrativo'];
	$senha                 = $dadosRemetente['senha'];

	$caixa 		 = explode(",",$_GET['caixa']);
	$comprimento = $caixa[0];
	$largura 	 = $caixa[1];
	$altura 	 = $caixa[2];
	$peso 	     = $_GET['peso'];
	$valor 		 = $_GET['valor_nota'];
	$cepDestino  = $_GET['cep'];

	if(empty($cepOrigem)){
		$response[] = array(
			"resultado" => "false",
			"mensagem" => "CEP de Origem em branco"
		);
		return $response;
	}

	if(empty($cepDestino)){
		$response[] = array(
			"resultado" => "false",
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
	// if($valor > 999){
	// 	echo gettype($valor);
	// 	echo $valor;
	// 	$valor = $valor * 100;
	// 	$valor_formatado = ($valor%100);
	// 	$valor = str_replace(".","",$valor);
	// 	echo $valor_formatado." VALOR_FORMATADO >>>>>>";
	// }

	$nCdServico = "";
	$countServico = count($servicoDisponivel);

	for($i=0; $i<$countServico; $i++){
		if($i > 0){
			$nCdServico .= ",";
		}

		if($fabrica_contrato == 153 && $servicoDisponivel[$i]["codigo"] == 40436){
			$servicoDisponivel[$i]["codigo"] = 40444;
		}

		$nCdServico .= $servicoDisponivel[$i]['codigo'];
	}

	$valor = ($valor < 20.5) ? 0 : $valor;

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
		$client = new SoapClient($url, array("trace" => 1, "connection_timeout" => 30,
				'stream_context'=>stream_context_create(
					array('http'=>
					array(
						'protocol_version'=>'1.0',
						'header' => 'Connection: Close'
						)
					)
				)
			));
	} catch (Exception $e) {
		$msg_erro = "Erro na conexão com o servidor dos correios. msg: ".$e->getMessage()."<br />";
	}

    $result = "";
	// var_dump($array_request); exit;

    try {
		$result = $client->__soapCall("CalcPrecoPrazo", array($array_request));
	} catch (Exception $e) {
		$msg_erro = "Erro ao realizar a requisição para o correios. msg: ".$e->getMessage()."<br />";
	}

	$resultCalculo  = $result->CalcPrecoPrazoResult->Servicos->cServico;
	// print_r( $resultCalculo );

	$dados_postagem = array();
	$response       = array();
	$valores        = array();
	$countCalculo   = count($resultCalculo);

	$naoCalculaOffline = false;
	if (count($resultCalculo) > 0) {

		$falhaCorreios = true;
		$contErros = 1;
		foreach($resultCalculo as $key => $value) {
			
			$sqlDadosTipo = "SELECT descricao FROM tbl_servico_correio WHERE codigo = '{$value->Codigo}' AND ativo";
			$resDadosTipo = pg_query($con, $sqlDadosTipo);

			$descricaoServico = pg_fetch_result($resDadosTipo, 0, 'descricao');

			if (!empty(trim($value->MsgErro))) {

				$msg_erro .= "Erro ".$contErros.": ".$descricaoServico."- ".$value->MsgErro." <br />";
				$contErros++;

			} else if ((int) $value->Valor == 0) {

				$msg_erro .= "Erro ".$contErros.": ".$descricaoServico."- Falha ao calcular frete <br />";
				$contErros++;

			} else {

				$falhaCorreios = false;

			}

			if ($value->Valor > 0) {
				$naoCalculaOffline = true;
			}

		}

	} else {

		$falhaCorreios = true;
		$msg_erro .= "Erro 1: Sem resposta do webservice dos correios. <br />";

	}

	$classCorreios = new \Posvenda\Correios($fabrica_contrato);

	$dadosCotacaoTransportadora = $classCorreios->cotarFreteBraspress($array_request, $_GET["embarque"]);

	//caso todos os serviços dos correios estejam fora, executa o cálculo offline
	if ($falhaCorreios && !$naoCalculaOffline) {

		try {


			$dadosCotacao = $classCorreios->cotarFreteOffline();

			$dadosCotacao[] = $dadosCotacaoTransportadora;

			return ["erro_correios" => utf8_encode($msg_erro),
					"dados" => $dadosCotacao];

		} catch (\Exception $e) {
		    
			$response = [
				"dados" => [$dadosCotacaoTransportadora],
				"erro_offline"  => utf8_encode($e->getMessage()),
				"erro_correios" => utf8_encode($msg_erro)
			];

		}

		return $response;

	}

	/*
		Valida retorno da solicitação de cotação de frete,
			caso conter erro, retornará a mensagem do erro.
	*/
	if(!empty($msg_erro)){
		$response[] = array(
			"resultado" => "false",
			"mensagem" => $msg_erro
		);
		//return $response;
	}

	for($i = 0; $i<$countCalculo; $i++)	{
		$valorresult = $resultCalculo[$i]->Valor;
		if(strlen($valorresult) == 0 ) {
			continue;
		}

    	if(str_replace(",",".",$resultCalculo[$i]->Valor)){
			$resultCalculo[$i]->Valor = str_replace(",",".",$resultCalculo[$i]->Valor);
		}

		$j = 0;

		while($servicoDisponivel[$j]['codigo'] != $resultCalculo[$i]->Codigo){
			$j++;
		}

		if($fabrica_contrato == 153 && $servicoDisponivel[$j]['codigo'] == 40444){
			$servicoDisponivel[$j]['descricao'] = "SEDEX - POSITRON";
		}

    	$dados_postagem[$i] = array(
			'codigo'        => utf8_encode($resultCalculo[$i]->Codigo),
			'valor'         => $resultCalculo[$i]->Valor,
			'descricao'     => $servicoDisponivel[$j]['descricao'],
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

		$response = array_merge($resultado, $array_sem_valor);
		$response[] = $dadosCotacaoTransportadora;

    	return $response;
    }else{
    	return "Não existe serviço disponível neste contrato!";
    }
}

/*
*
* GERA JSON COM AS INFORMAÇÕES DE CADA EMBARQUE E
	SUA REPECTIVA ETIQUETA PARA SER ENVIADO A CLASSE RESPONSÁVEL
	POR CRIAR O PDF DAS ETIQUETAS
*
*/
function gerarEtiqueta($posto,$etiqueta = null){
	global $result;
	$embarque       = buscaEmbarque($_GET['embarque'],"gerarEtiqueta");
	$fabrica_embarque =(in_array($embarque[0]['fabrica'],[11,172])) ? 'aulik':'producao';

	$dados_contrato = consultaBanco($fabrica_embarque,$embarque[0]['fabrica']);

	// $endereco = consultaCEP($dados_contrato['cep']);
	// var_dump($endereco); exit;
	// if(isset($endereco['end'])){
	// 	$dados_contrato['endereco'] = $endereco['end'];
	// 	$dados_contrato['cidade'] = $endereco['cidade'];
	// 	$dados_contrato['bairro'] = $endereco['bairro'];
	// 	$dados_contrato['cep'] = $endereco['cep'];
	// }

	$remetente = consultaBanco("remetente",$posto);
	$response  = array("contrato" => $dados_contrato,
		"remetente" => $remetente,
		"embarque"  => $embarque);

	if(!$etiqueta) {
		return $response;
	}else{
		$result['dados'] = json_encode($response);
	}
}

/*
*
* BUSCA INFORMAÇÃO DO EMBARQUE PARA GERAÇÃO DA ETIQUETA E PARA A PLP
*
*/
function buscaEmbarque($numero_embarque,$funcao){
	global $con;

	if($numero_embarque == ""){
		$embarque = $_GET['embarque'];
	}else{
		$embarque = $numero_embarque;
	}

	if(strripos($embarque, ",") == true){
		$verifica_embarques = explode(",",$embarque);
	}else{
		$verifica_embarques[] = $embarque;
	}

	$embarque_certo        = "";
	$embarque_sem_etiqueta = "";
	$embarque_sem_nota     = "";
	$countEmbarque         = count($verifica_embarques);

	for($i=0; $i<$countEmbarque; $i++){
		$verifica_embarques[$i] = trim($verifica_embarques[$i]);

		$sql = "SELECT etiqueta_servico FROM tbl_etiqueta_servico
			WHERE tbl_etiqueta_servico.embarque IS NOT NULL
				AND tbl_etiqueta_servico.embarque = '".$verifica_embarques[$i]."'";
		$resEmbarque = pg_query($con,$sql);

		$sql = "";
		$sql = "SELECT embarque FROM tbl_faturamento
			WHERE (nota_fiscal IS NOT NULL AND nota_fiscal <> '000000')
				AND embarque IS NOT NULL
				AND tbl_faturamento.embarque = '".$verifica_embarques[$i]."'";
		$resNota = pg_query($con,$sql);

		if(pg_num_rows($resEmbarque)==0 || pg_num_rows($resNota)==0){
			if (pg_num_rows($resEmbarque)==0) {
				if($embarque_sem_etiqueta != ""){
					$embarque_sem_etiqueta .= ",";
				}
				$embarque_sem_etiqueta .= $verifica_embarques[$i];
			}

			if (pg_num_rows($resNota)==0) {
				if($embarque_sem_nota != ""){
					$embarque_sem_nota .= ",";
				}
				$embarque_sem_nota .= $verifica_embarques[$i];
			}
		}else{
			if($embarque_certo != ""){
				$embarque_certo .= ",";
			}
			$embarque_certo .= $verifica_embarques[$i];
		}
	}

	if($embarque_certo != ""){
		$sql = "";
		$sql = "SELECT tbl_etiqueta_servico.servico_correio,
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
				tbl_posto_fabrica.contato_bairro,
				tbl_faturamento.nota_fiscal,
				replace(to_char(tbl_faturamento.total_nota::real,'99999D99')::text,'.',',') as total_nota,
				tbl_embarque.fabrica
			FROM tbl_etiqueta_servico
				LEFT JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio = tbl_etiqueta_servico.servico_correio
				JOIN tbl_embarque ON tbl_embarque.embarque = tbl_etiqueta_servico.embarque
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_embarque.posto AND tbl_posto_fabrica.fabrica = tbl_embarque.fabrica
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto

				INNER JOIN tbl_faturamento ON tbl_faturamento.embarque = tbl_etiqueta_servico.embarque AND tbl_faturamento.fabrica <> 0
			WHERE tbl_etiqueta_servico.embarque IN (".$embarque_certo.") order by tbl_embarque.fabrica";

			/*
				Comando para formatar os dados da nota_fiscal em float
				replace(to_char(faturamento.total_nota::real,'999D99')::text,'.',',') as total_nota
			*/
//echo $sql;
	$res = pg_query ($con,$sql);

		if (pg_num_rows($res)>0) {
			$countAux = pg_num_rows($res);

			for($i=0; $i<$countAux; $i++){

				if($numero_embarque == ""){
					$dados_embarque[] = array(
						"servico_correio" => pg_fetch_result($res,$i,"servico_correio"),
						"codigo"          => pg_fetch_result($res,$i,"codigo"),
						"chave_servico"   => pg_fetch_result($res,$i,"chave_servico"),
						"descricao"       => trim(pg_fetch_result($res,$i,"descricao")),
						"etiqueta"        => pg_fetch_result($res,$i,"etiqueta"),
						"embarque"        => pg_fetch_result($res,$i,"embarque"),
						"peso"            => pg_fetch_result($res,$i,"peso"),
						"caixa"           => str_replace(",", " x ", pg_fetch_result($res,$i,"caixa")),
						"prazo_entrega"   => pg_fetch_result($res,$i,"prazo_entrega"),
						"fabrica"   => pg_fetch_result($res,$i,"fabrica")
					);
				}else{
					$etiqueta   = pg_fetch_result($res,$i,"etiqueta");
					$caixa      = pg_fetch_result($res,$i,"caixa");
					$caixa      = explode(",",$caixa);
					$cubagem    = ($caixa[0]*$caixa[1]*$caixa[2])/6000;
					$total_nota = pg_fetch_result($res, $i, "total_nota");
					$nome = pg_fetch_result($res,$i,"nome");
					$nome = substr($nome,0,60);

					$dados_embarque[$i] = array(
						"servico_correio" => pg_fetch_result($res,$i,"servico_correio"),
						"codigo"          => pg_fetch_result($res,$i,"codigo"),
						"chave_servico"   => pg_fetch_result($res,$i,"chave_servico"),
						"descricao"       => trim(pg_fetch_result($res,$i,"descricao")),
						"etiqueta"        => $etiqueta,
						"embarque"        => pg_fetch_result($res,$i,"embarque"),
						"nota_fiscal"     => pg_fetch_result($res,$i,"nota_fiscal"),
						"total_nota"      => $total_nota,
						"peso"            => pg_fetch_result($res,$i,"peso"),
						"preco"           => pg_fetch_result($res,$i,"preco"),
						"caixa"           => str_replace(",", " x ", pg_fetch_result($res,$i,"caixa")),
						"comprimento"     => $caixa[0],
						"largura"         => $caixa[1],
						"altura"          => $caixa[2],
						"cubagem"         => $cubagem,
						"prazo_entrega"   => pg_fetch_result($res,$i,"prazo_entrega"),
						"posto"           => pg_fetch_result($res,$i,"posto"),
						"nome"            => retira_acentos(str_replace("&","", $nome)),
						"cidade"          => retira_acentos(pg_fetch_result($res,$i,"contato_cidade")),
						"cnpj"            => pg_fetch_result($res,$i,"cnpj"),
						"estado"          => pg_fetch_result($res,$i,"contato_estado"),
						"fone"            => pg_fetch_result($res,$i,"contato_fone_comercial"),
						"cep"             => pg_fetch_result($res,$i,"contato_cep"),
						"endereco"        => retira_acentos(pg_fetch_result($res,$i,"contato_endereco")),
						"numero"          => pg_fetch_result($res,$i,"contato_numero"),
						"fabrica"          => pg_fetch_result($res,$i,"fabrica"),
						"bairro"          => retira_acentos(pg_fetch_result($res,$i,"contato_bairro"))
					);
					
					$embarque        = pg_fetch_result($res,$i,"embarque");
					if(!empty($embarque)) {
						$sql = "select tbl_hd_chamado_extra.nome,tbl_cidade.nome as cidade, tbl_cidade.estado , cpf, fone, tbl_hd_chamado_extra.cep, endereco, numero, bairro
								from tbl_hd_chamado_extra
								join tbl_pedido_item using(pedido)
								join tbl_embarque_item using(pedido_item)
								join tbl_cidade using(cidade)
								where tbl_embarque_item.embarque = $embarque "; 
						$rese = pg_query($con, $sql);
						if(pg_num_rows($rese) > 0) {
							$nome = pg_fetch_result($rese,0,"nome");
							$nome = substr($nome,0,60);

							$dados_embarque[$i]["nome"]            = retira_acentos(str_replace("&","", $nome));
							$dados_embarque[$i]["cidade"]          = retira_acentos(pg_fetch_result($rese,0,"cidade"));
							$dados_embarque[$i]["cnpj"]            = pg_fetch_result($rese,0,"cpf");
							$dados_embarque[$i]["estado"]          = pg_fetch_result($rese,0,"estado");
							$dados_embarque[$i]["fone"]            = pg_fetch_result($rese,0,"fone");
							$dados_embarque[$i]["cep"]             = pg_fetch_result($rese,0,"cep");
							$dados_embarque[$i]["endereco"]        = retira_acentos(pg_fetch_result($rese,0,"endereco"));
							$dados_embarque[$i]["numero"]          = pg_fetch_result($rese,0,"numero");
							$dados_embarque[$i]["bairro"]          = retira_acentos(pg_fetch_result($rese,0,"bairro"));
						}
					}
				}
			}

			if($numero_embarque == "" && ($embarque_sem_etiqueta != "" || $embarque_sem_nota != "")){
				if($embarque_sem_nota != ""){
					if(strripos($embarque_sem_nota, ",") == true){
						$mensagem = "Os embarques ".$embarque_sem_nota." não foram faturados.";
					}else{
						$mensagem = "O embarque ".$embarque_sem_nota." não foi faturado.";
					}
				}

				if($mensagem != ""){
					$mensagem .= "<br/>";
				}

				if($embarque_sem_etiqueta != ""){
					if(strripos($embarque_sem_etiqueta, ",") == true){
						$mensagem .= "Os embarques ".$embarque_sem_etiqueta." estão sem etiquetas.";
					}else{
						$mensagem .= "O embarque ".$embarque_sem_etiqueta." esta sem etiqueta.";
					}
				}

		    	$response[] = array("resultado" => "false",
					"mensagem" => utf8_encode($mensagem),
					"dados"    => $dados_embarque);
		    	return $response;
			}
			return $dados_embarque;
		}
	}else{
		if($numero_embarque == "" && ($embarque_sem_etiqueta != "" || $embarque_sem_nota != "")){
			if($embarque_sem_nota != ""){
				if(strripos($embarque_sem_nota, ",") == true){
					$mensagem = "Os embarques ".$embarque_sem_nota." não foram faturados.";
				}else{
					$mensagem = "O embarque ".$embarque_sem_nota." não foi faturado.";
				}
			}

			if($mensagem != ""){
				$mensagem .= "<br/>";
			}

			if($embarque_sem_etiqueta != ""){
				if(strripos($embarque_sem_etiqueta, ",") == true){
					$mensagem .= "Os embarques ".$embarque_sem_etiqueta." estão sem etiquetas.";
				}else{
					$mensagem .= "O embarque ".$embarque_sem_etiqueta." esta sem etiqueta.";
				}
			}

	    	$response[] = array("resultado" => "false",
				"mensagem" => utf8_encode($mensagem),
				"dados"    => $dados_embarque);
	    	return $response;
		}
		$response[] = array("resultado" => "false",
			"mensagem" => "Não encontrado embarque(s) ligado com etiqueta");
		return $response;
	}
}

function consultaCEP($cep, $url){
	$array_request = (object) array('consultaCEP' => $cep);

	// $url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

	try {
		$client = new SoapClient($url, array("trace" => 1, "connection_timeout" => 30,
				'stream_context'=>stream_context_create(
					array('http'=>
					array(
						'protocol_version'=>'1.0',
						'header' => 'Connection: Close'
						)
					)
				)
			));
	} catch (Exception $e) {
		$response[] = array("resultado" => "false", array($e));
    	return $response;
	}

    $result = "";

    try{
    	$result = $client->__soapCall("consultaCEP", array($array_request));
    } catch (Exception $e) {
    	$response[] = array("resultado" => "false", array($e));
    	return $response;
	}

    $response = $result->return;
	return $response;
}

/*
*
* BSUCA INFORMAÇÃO NO BANCO DO OBJETO (EMBARQUE) PARA MOSTRAR NA TABELA - TELA GERAR PLP
*
*/
function inserirObjetoPLP($url){
	global $con;

	$array_cartao = array(
		11  => "0074607855",
		81  => "0069835535",
		122 => "0069835780",
		114 => "0069835632",
		123 => "0075061430",
		125 => "0069835667",
		147 => "0073158810",
		153 => "0071856978",
		160 => "0073789933",
		172 => "0074607855",
		187 => "0074778145"
	);

// 	CARTAO	FABRICA	AGENCIA
// 0069835780	122 - WURTH	AGF NOVA MARILIA
// 0069835632	114 - COBIMEX	AGF CORONEL GALDINO
// 0069835535	SPECTRUM	AGF SOMENZARI
// 0069835705	123 - POSITEC	AGF NOVA MARILIA
// 0069835667	125 - SAINT GOBAIN	AGF CORONEL GALDINO

	$plp             = $_GET['plp'];
	$etiqueta        = $_GET['objeto'];
	$etiqueta        = strtoupper($etiqueta);
	$cartao_postagem = $_GET['cartao_postagem'];

	$sql = "SELECT tbl_os_item.fabrica_i AS fabrica
		FROM tbl_etiqueta_servico
			JOIN tbl_embarque_item ON tbl_embarque_item.embarque = tbl_etiqueta_servico.embarque
			JOIN tbl_os_item ON tbl_os_item.os_item              = tbl_embarque_item.os_item
		WHERE tbl_etiqueta_servico.etiqueta = '{$etiqueta}'
			GROUP BY tbl_os_item.fabrica_i";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$fabrica = pg_fetch_result($res, 0, "fabrica");

	} else {

		$sql = "SELECT fabrica FROM tbl_embarque
			WHERE embarque IN (
				SELECT embarque FROM tbl_etiqueta_servico WHERE etiqueta = '$etiqueta'
			)";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$fabrica = pg_fetch_result($res, 0, "fabrica");
		}else{
			$response[] = array(
				"resultado" => "false",
				"mensagem" => utf8_encode("Fábrica não encontrada")
			);
			return $response;
		}
	}

	if(!in_array($fabrica, array(11,114,122,123,125,147,153,160,172,187))){
		$fabrica = 81;
	}

	if(!empty($cartao_postagem)){
		if($cartao_postagem != $array_cartao[$fabrica]){
			$response[] = array(
				"resultado" => "false",
				"mensagem" => utf8_encode("Esta etiqueta ".$etiqueta." pertence a outra fábrica!")
			);
			return $response;
		}

	}else{
		$cartao_postagem = $array_cartao[$fabrica];
	}

	if($plp == "INSERT"){
		$contrato = getStatusCartaoPostagem($fabrica, $url, $array_cartao[$fabrica]);
//		die('123');
	}

	if(($plp == "INSERT" && $contrato == "Normal") || $plp != "INSERT"){
		$sql = "SELECT tbl_etiqueta_servico.*,
				tbl_embarque_posto.cep,
				faturamento.nota_fiscal,
				tbl_servico_correio.descricao
			FROM tbl_etiqueta_servico

				INNER JOIN (SELECT tbl_embarque.embarque, tbl_posto.cep
					FROM tbl_embarque
					JOIN tbl_posto ON tbl_posto.posto = tbl_embarque.distribuidor
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				) AS tbl_embarque_posto ON tbl_embarque_posto.embarque = tbl_etiqueta_servico.embarque

				INNER JOIN (SELECT nota_fiscal, embarque AS faturamento_embarque FROM tbl_faturamento
				) AS faturamento ON faturamento.faturamento_embarque = tbl_etiqueta_servico.embarque

				JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio = tbl_etiqueta_servico.servico_correio
			WHERE etiqueta = '".$etiqueta."'";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)>0) {
			$etiqueta = utf8_encode(pg_fetch_result($res,0,"etiqueta_servico"));

			if($plp == "INSERT"){
				$plp = 1;
			}

			if(pg_fetch_result($res,0,"preco") == null){
				$preco = "0.0";
			}else{
				$preco = pg_fetch_result($res,0,"preco");
			}

			$response[] = array("plp" => $plp,
				"etiqueta"        => pg_fetch_result($res,0,"etiqueta"),
				"embarque"        => pg_fetch_result($res,0,"embarque"),
				"cep"             => pg_fetch_result($res,0,"cep"),
				"nota_fiscal"     => pg_fetch_result($res,0,"nota_fiscal"),
				"preco"           => $preco,
				"peso"            => pg_fetch_result($res,0,"peso"),
				"caixa"           => pg_fetch_result($res,0,"caixa"),
				"prazo_entrega"   => pg_fetch_result($res,0,"prazo_entrega"),
				"digito"          => pg_fetch_result($res,0,"digito"),
				"descricao"       => utf8_encode(trim(pg_fetch_result($res,0,"descricao"))),
				"cartao_postagem" => $cartao_postagem
			);
		}else{
    		$response[] = array("resultado" => "false",
    			"mensagem" => utf8_encode("Esta etiqueta ".$etiqueta." ainda não possui nota fiscal!"));
		}
	}else{
    	$response[] = array("resultado" => "false",
    		"mensagem" => "O contrato com os Correios foi cancelado!");
	}
	return $response;
}

/*
*
* REMOVE OBJETO LANÇADO NA PLP (PLP AINDA NÃO FECHADA)
*
*/
function removerObjetoPLP(){
	global $con;

	$plp      = $_GET['plp'];
	$etiqueta = $_GET['objeto'];

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_plp_etiqueta
		WHERE lista_postagem = ".$plp."
			AND etiqueta_servico = (SELECT etiqueta_servico FROM tbl_etiqueta_servico WHERE etiqueta = '".$etiqueta."')";
	$resultInsert = pg_query($con, $sql);

	if (pg_last_error() > 0 ) {
		$res        = pg_query ($con,"ROLLBACK TRANSACTION");
		$response[] = array("resultado" => "falseErroBanco",
			"faultstring" => "Ocorreu um erro ao iniciar uma PLP, tente novamente mais tarde!");
	}else{
		$res                   = pg_query ($con,"COMMIT TRANSACTION");
		$response["resultado"] = "true";
	}
	return $response;
}

/*
*
* VERIFICA SE CARTÃO DE POSTAGEM AINDA ESTÁ ATIVO NO CORREIO
*
*/
function getStatusCartaoPostagem($fabrica, $url, $cartao = null){

	if($fabrica == 153){
		$resultado_contrato = consultaBanco("positron", $fabrica);
		$resultado_contrato["cartao"] = "0071856978";
	}elseif (in_array($fabrica, [11,172])) {
		$resultado_contrato = consultaBanco("aulik", $fabrica);
		$resultado_contrato["cartao"] = "0074607855";
	}else{
		$resultado_contrato = consultaBanco("producao", $fabrica);

		if (!empty($cartao)) {
			$resultado_contrato['cartao'] = $cartao;
		}
	}

	$array_request = (object) array(
		'numeroCartaoPostagem' => $resultado_contrato['cartao'],
		'usuario'              => $resultado_contrato['usuario'],
		'senha'                => $resultado_contrato['senha']
	);

//	print_r($array_request);
// $url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

	try {
		$client = new SoapClient($url, array("trace" => 1, "connection_timeout" => 30,
				'stream_context'=>stream_context_create(
					array('http'=>
					array(
						'protocol_version'=>'1.0',
						'header' => 'Connection: Close'
						)
					)
				)
			));
	} catch (Exception $e) {
		$response[] = array("resultado" => "false", array($e));
    	return $response;
	}

    $result = "";
    try {
		$result = $client->__soapCall("getStatusCartaoPostagem", array($array_request));
	} catch (Exception $e) {
		$response[] = array("resultado" => "false", array($e));
    	return $response;
	}
    $result = $result->return;
    return $result;
}

/*
*
* FECHA PLP E VALIDA ETIQUETAS ANTES DO FECHAMENTO
*
*/
function validarEmbarque(){
	global $con;

	$plp             = $_GET['plp'];
	$etiquetas       = $_GET['etiquetas'];
	$cartao_postagem = $_GET['cartao_postagem'];

	if(strripos($etiquetas,",") ==  true){
		$objetos     = explode(",",$etiquetas);
		$etiquetas   = "";
		$countObjeto = count($objetos);

		for($i=0; $i<$countObjeto; $i++){
			if($i == 0){
				$etiquetas = "'".$objetos[$i]."'";
			}else{
				$etiquetas .= ",'".$objetos[$i]."'";
			}
		}
	}else{
		$etiquetas = "'".$etiquetas."'";
	}

	if(!empty($etiquetas)){
		$sql = "SELECT lista_postagem, tbl_etiqueta_servico.etiqueta FROM tbl_plp_etiqueta
			INNER JOIN tbl_etiqueta_servico ON tbl_etiqueta_servico.etiqueta_servico = tbl_plp_etiqueta.etiqueta_servico
		WHERE etiqueta IN ($etiquetas)";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			while($objeto_etiqueta = pg_fetch_object($res)){
				$etiqueta_lancada .= " Embarque: ".$objeto_etiqueta->embarque." - ".$objeto_etiqueta->etiqueta." ";
			}

			if(pg_num_rows($res) == 1){
				$mensagem = "A etiqueta $etiqueta_lancada já foi utilizada em outra Pré-Lista de Postagem.";
			}else{
				$mensagem = "As etiquetas $etiqueta_lancada já foram utilizadas em outra Pré-Lista de Postagem.";
			}

			$response[] = array(
				"resultado" => "false",
				"mensagem" => utf8_encode($mensagem)
			);
			return $response;
		}
	}

	$response = array(
		"resultado" => "true"
	);

	return $response;
}

/*
*
* GRAVA O NÚMERO DA PLP QUE FOI RETORNADO PELO SERVIDOR DOS CORREIOS
*
*/
function gravaIdPLP(){
	global $con, $login_posto;

	$plp       = $_GET['idplp'];
	$etiquetas = $_GET['etiquetas'];

	$res = pg_query($con,"BEGIN TRANSACTION");

	$sql = "";
	$sql = "INSERT INTO tbl_lista_postagem (plp_correio) VALUES ('".$plp."') RETURNING lista_postagem";
	$resultInsert = pg_query($con, $sql);

	if (pg_last_error() > 0 ) {
		$res        = pg_query ($con,"ROLLBACK TRANSACTION");
		$response[] = array(
			"resultado"   => "falseErroBanco",
			"faultstring" => "Ocorreu um erro ao gravar no banco a nova Pré-Lista de Postagem, tente novamente mais tarde!"
		);
		return $response;
	}else{
		$res                   = pg_query ($con,"COMMIT TRANSACTION");
		$response["resultado"] = "true";
		$idplp = pg_fetch_result($resultInsert, 0, "lista_postagem");
	}

	$etiquetas     = explode(",",$etiquetas);
	$res           = pg_query($con,"BEGIN TRANSACTION");
	$countEtiqueta = count($etiquetas);

	for($i=0; $i<$countEtiqueta; $i++){
		$sql = "";
		$sql = "INSERT INTO tbl_plp_etiqueta (lista_postagem, etiqueta_servico) VALUES (".$idplp.",(SELECT etiqueta_servico FROM tbl_etiqueta_servico WHERE etiqueta = '".$etiquetas[$i]."'))";
		$resultInsert = pg_query($con, $sql);
	}

	if (pg_last_error() > 0 ) {
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
    	$response[] = array(
			"resultado"   => "falseErroBanco",
			"faultstring" => "Ocorreu um erro ao gravar no banco a nova Pré-Lista de Postagem, tente novamente mais tarde!"
		);
		return $response;
	}else{
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$response[] = array(
			"resultado" => "true",
			"idplp"     => $plp
		);
	}
	return $response;
}

/*
*
* BUSCA AS INFORMAÇÕES PELO NÚMERO DA PLP INFORMADA NA TELA
*
*/
function consultarPLP(){
	global $con;
	$plp = $_GET['plp'];

	$array_cartao = array(
		11  => "0074607855",
		81  => "0069835535",
		122 => "0069835780",
		114 => "0069835632",
		123 => "0075061430",
		125 => "0069835667",
		147 => "0073158810",
		153 => "0071856978",
		160 => "0073789933",
		172 => "0074607855",
		187 => "0074778145"
	);

	/*	CARTAO	FABRICA	AGENCIA

	0069835780	122 - WURTH	AGF NOVA MARILIA
	0069835632	114 - COBIMEX	AGF CORONEL GALDINO
	0069835535	SPECTRUM	AGF SOMENZARI
	0069835705	123 - POSITEC	AGF NOVA MARILIA
	0069835667	125 - SAINT GOBAIN	AGF CORONEL GALDINO
	0071856978  153 - POSITRON
	0070109605  11, 172 Lenoxx

	*/

	$sql = "SELECT tbl_lista_postagem.plp_correio
		FROM tbl_etiqueta_servico 
			JOIN tbl_plp_etiqueta USING(etiqueta_servico)
			JOIN tbl_lista_postagem ON tbl_lista_postagem.lista_postagem = tbl_plp_etiqueta.lista_postagem
		WHERE tbl_etiqueta_servico.etiqueta = '".$plp."'";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$plp = pg_fetch_result($res, 0, plp_correio);
	}

	$sql = "SELECT tbl_lista_postagem.plp_correio,
			tbl_etiqueta_servico.etiqueta_servico,
			tbl_etiqueta_servico.etiqueta,
			tbl_etiqueta_servico.embarque,
			tbl_etiqueta_servico.preco,
			tbl_etiqueta_servico.peso,
			tbl_etiqueta_servico.caixa,
			tbl_etiqueta_servico.prazo_entrega,
			tbl_etiqueta_servico.digito,
			tbl_embarque_posto.cep,
			tbl_faturamento.nota_fiscal,
			tbl_servico_correio.descricao
		FROM tbl_lista_postagem
			JOIN tbl_plp_etiqueta ON tbl_plp_etiqueta.lista_postagem           = tbl_lista_postagem.lista_postagem
			JOIN tbl_etiqueta_servico ON tbl_etiqueta_servico.etiqueta_servico = tbl_plp_etiqueta.etiqueta_servico
			JOIN tbl_servico_correio ON tbl_servico_correio.servico_correio    = tbl_etiqueta_servico.servico_correio
			JOIN tbl_faturamento ON tbl_faturamento.embarque                   = tbl_etiqueta_servico.embarque
			JOIN (SELECT tbl_embarque.embarque, tbl_posto.cep FROM tbl_embarque
				JOIN tbl_posto USING (posto)
			) AS tbl_embarque_posto ON tbl_embarque_posto.embarque = tbl_etiqueta_servico.embarque

		WHERE tbl_lista_postagem.plp_correio = '".$plp."'";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		$embarque = pg_fetch_result($res, 0, "embarque");

		$sql = "SELECT tbl_os_item.fabrica_i FROM tbl_etiqueta_servico
				JOIN tbl_embarque_item ON tbl_embarque_item.embarque = tbl_etiqueta_servico.embarque
				JOIN tbl_os_item ON tbl_os_item.os_item = tbl_embarque_item.os_item
			WHERE tbl_etiqueta_servico.embarque = {$embarque} LIMIT 1";
		$resFabrica = pg_query($con,$sql);

		if(pg_num_rows($resFabrica) > 0){
			$fabrica_plp     = (int) pg_fetch_result($resFabrica, 0, "fabrica_i");
			$cartao_postagem = $array_cartao[$fabrica_plp];
		}else{
			$cartao_postagem = $array_cartao[81];
		}

		$count = pg_num_rows($res);

		for($i=0; $i<$count; $i++){
			$preco = "";

			if(pg_fetch_result($res,$i,preco) == null){
				$preco = "0.0";
			}else{
				$preco = pg_fetch_result($res,$i,"preco");
			}

			$response[] = array(
				"plp"             => pg_fetch_result($res,$i,"plp_correio"),
				"etiqueta"        => pg_fetch_result($res,$i,"etiqueta"),
				"cartao_postagem" => $cartao_postagem,
				"embarque"        => pg_fetch_result($res,$i,"embarque"),
				"cep"             => pg_fetch_result($res,$i,"cep"),
				"nota_fiscal"     => pg_fetch_result($res,$i,"nota_fiscal"),
				"preco"           => $preco,
				"peso"            => pg_fetch_result($res,$i,"peso"),
				"caixa"           => pg_fetch_result($res,$i,"caixa"),
				"prazo_entrega"   => pg_fetch_result($res,$i,"prazo_entrega"),
				"digito"          => pg_fetch_result($res,$i,"digito"),
				"descricao"       => utf8_encode(trim(pg_fetch_result($res,$i,"descricao")))
			);
		}
		return $response;
	}else{
		$response[] = array(
			"resultado" => "false",
			"mensagem"  => utf8_encode("Não foi encontrado nenhuma pré-lista de postagem com esse número!")
		);
		return $response;
	}
}

/*
*
* 	IMPRIME PLP QUE JÁ FOI GRAVADO /
*	É UTILIZADO PARA PEGAR AS INFORMAÇÕES DAS ETIQUETAS E DO CONTRATO PARA FECHAMENTO DA PLP
*
*/
function imprimePLP($etiquetas, $cartao_postagem){
	global $con;

	if(strripos($etiquetas,",") ==  true){
		$objetos     = explode(",",$etiquetas);
		$etiquetas   = "";
		$countObjeto = count($objetos);

		for($i=0; $i<$countObjeto; $i++){
			if($i == 0){
				$etiquetas = "'".$objetos[$i]."'";
			}else{
				$etiquetas .= ",'".$objetos[$i]."'";
			}
		}
	}else{
		$etiquetas = "'".$etiquetas."'";
	}
	
	$sql_garantia = "SELECT embarque FROM tbl_embarque WHERE embarque = $embarque AND fabrica = $fabrica AND garantia IS TRUE";

	$sql = "SELECT embarque FROM tbl_etiqueta_servico WHERE etiqueta IN (".$etiquetas.")";
	$res = pg_query($con, $sql);

	$embarque      = "";
	$countEmbarque = pg_num_rows($res);
	$res           = pg_fetch_all($res);

	for($i = 0; $i<$countEmbarque; $i++){
		if($i == 0){
			$embarque = $res[$i]['embarque'];
		}else{
    		$embarque .= ",".$res[$i]['embarque'];
		}
    }

    $array_fabrica = array(
    		"0074607855" => 11,
		"0069835535" => 81,
		"0069835780" => 122,
		"0069835632" => 114,
		"0075061430" => 123,
		"0069835667" => 125,
		"0073158810" => 147,
		"0071856978" => 153,
		"0073789933" => 160,
		"0074607855" => 172,
		"0074778145" => 187
	);

    $embarque = buscaEmbarque($embarque,"fechaPLP");

	if(!in_array($array_fabrica[$cartao_postagem],array(11,123,153,160,172,187))){
		$dados_contrato = consultaBanco("usuario",$array_fabrica[$cartao_postagem]);
	}else if($array_fabrica[$cartao_postagem] == 160){
		$dados_contrato = consultaBanco("einhell",$array_fabrica[$cartao_postagem]);
	}else if($array_fabrica[$cartao_postagem] == 153){
		$dados_contrato = consultaBanco("positron",$array_fabrica[$cartao_postagem]);
	}else if($array_fabrica[$cartao_postagem] == 11 || $array_fabrica[$cartao_postagem] == 172){
		$dados_contrato = consultaBanco("aulik",$array_fabrica[$cartao_postagem]);
	}else if($array_fabrica[$cartao_postagem] == 123){
		$dados_contrato = consultaBanco("positec",$array_fabrica[$cartao_postagem]);	
	}
	/* DESENVOLVIMENTO */
	// $dados_contrato = consultaBanco("desenvolvimento",$posto);

	/* SOMENTE UTILIZAR A VARIÁVEL CARTÃO DE POSTAGEM PARA PRODUÇÃO */
	$dados_contrato["cartao"] = $cartao_postagem;
	$remetente                = consultaBanco("remetente",$array_fabrica[$cartao_postagem]);

	$response = array(
		"contrato"  => $dados_contrato,
		"remetente" => $remetente,
		"embarque"  => $embarque
	);

	return $response;
}

// function acentos ($string) {
//     $array1 = array("á" => "a", "à" => "a", "â" => "a", "ã" => "a", "ä" => "a",
//     	"é" => "e", "è" => "e", "ê" => "e", "ë" => "e",
//     	"í" => "i", "ì" => "i", "î" => "i", "ï" => "i",
//     	"ó" => "o", "ò" => "o", "ô" => "o", "õ" => "o", "ö" => "o",
//     	"ú" => "u", "ù" => "u", "û" => "u", "ü" => "u",
//     	"ç" => "c",
//     	"Á" => "A", "À" => "A", "Â" => "A", "Ã" => "A", "Ä" => "A",
//     	"É" => "E", "È" => "E", "Ê" => "E", "Ë" => "E",
//     	"Í" => "I", "Ì" => "I", "Î" => "I", "Ï" => "I",
//     	"Ó" => "O", "Ò" => "O", "Ô" => "O", "Õ" => "O", "Ö" => "O",
//     	"Ú" => "U", "Ù" => "U", "Û" => "U", "Ü" => "U", "Ç" => "C");
//     $string = strtr(utf8_decode($string), $array1);

//     return $string;
// }

function retira_acentos( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
	return str_replace( $array1, $array2, $texto );
}

?>
