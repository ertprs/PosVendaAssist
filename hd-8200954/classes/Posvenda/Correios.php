<?php
/**
 *
 * @author  Kaique
 * @version 2019.07.30
 *
*/
namespace Posvenda;

use Posvenda\Model\GenericModel as Model;
use SoapClient;

class Correios
{
	private $_pdo;
	private $_sigepClient;
	private $_fabricaContrato;
	private $_dadosContrato;
	private $_peso;
	private $_pesoExcedente;
	private $_comprimento;
	private $_altura;
	private $_largura;
	private $_cepOrigem;
	private $_cepDestino;
	
	private $_urlServico   = 'https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl';
	private $_urlBraspress = 'https://api.braspress.com/v1/cotacao/calcular/json';

	public function __construct($fabricaContrato, $urlServico = null)
	{

		$model = new Model();

		$this->_pdo = $model->getPDO();

		$this->_fabricaContrato = $fabricaContrato;

		if (!empty($urlServico)) {
			$this->_urlServico = $urlServico;
		}

		$this->_sigepClient = new SoapClient($this->_urlServico, [
			"trace" => 1, 
			"exception" => 1
		]);

		$this->setDadosContrato();

	}

	/* -- cotarFreteOffline --
	   L: Local/Região
	   E: Estadual
	   I: Interestadual	
	   N: Nacional (trecho entre capitais)
	   Sobre a query: 
	   origem  - busca o ID da cidade_cep origem de acordo com a faixa de cep
	   destino - busca o ID da cidade_cep destino de acordo com a faixa de cep
	   precificacao_local_divisa (L) - verifica se o trecho entre origem/destino possui 
	   preço de frete local/divisa (tbl_correios_precificacao.origem e tbl_correios_precificacao.destino).
	   Geralmente são cidades próximas à cidade de origem (Ex: marília - garça)
		
	   precificacao_interestadual (E ou I) - quando não encontra preço na tabela de preços local_divisa. 
	   Caso as duas cidades estejam no mesmo estado, mas de regiões diferentes,
	   busca da tabela de preços Estadual (E). Quando ambas estão em estados diferentes e ambas não são
	   capitais, assume a tabela de preço interestadual (I)
	   precificacao_nacional (N) - Quando ambas cidades de origem e destino são capitais. As cidades próximas
	   a uma cidade capital, também são tratadas como capitais pelos correios (Ex: São Paulo - Osasco).
	*/

	public function cotarFreteOffline($dadosObjeto) {

		$this->_altura      	= $dadosObjeto->nVlAltura;
		$this->_largura     	= $dadosObjeto->nVlLargura;
		$this->_comprimento 	= $dadosObjeto->nVlComprimento;
		$this->_peso        	= $dadosObjeto->nVlPeso;
		$this->_cepOrigem   	= $dadosObjeto->sCepOrigem;
		$this->_cepDestino  	= $dadosObjeto->sCepDestino;

		//$this->_cepOrigem  = 17511100;
		//$this->_cepDestino = 17055002;

		$this->calculoPeso();
		$sql = "WITH origem AS (

					SELECT tbl_correios_cidade_cep.correios_cidade_cep as cidade,
						   (tbl_correios_capital.correios_capital IS NOT NULL) as is_capital,
						   tbl_cidade.estado
					FROM tbl_correios_cidade_cep
					JOIN tbl_cidade ON tbl_correios_cidade_cep.cidade = tbl_cidade.cidade
					LEFT JOIN tbl_correios_capital ON tbl_correios_capital.estado = tbl_cidade.estado
					AND tbl_correios_cidade_cep.correios_cidade_cep = ANY(tbl_correios_capital.cidades_cep::int[])
					WHERE {$this->_cepOrigem} BETWEEN replace(cep_inicial, '-', '')::int  AND replace(cep_final, '-', '')::int
					LIMIT 1

				), destino AS (

					SELECT tbl_correios_cidade_cep.correios_cidade_cep as cidade,
						   (tbl_correios_capital.correios_capital IS NOT NULL) as is_capital,
						   tbl_cidade.estado
					FROM tbl_correios_cidade_cep
					JOIN tbl_cidade ON tbl_correios_cidade_cep.cidade = tbl_cidade.cidade
					LEFT JOIN tbl_correios_capital ON tbl_correios_capital.estado = tbl_cidade.estado
					AND tbl_correios_cidade_cep.correios_cidade_cep = ANY(tbl_correios_capital.cidades_cep::int[])
					WHERE {$this->_cepDestino} BETWEEN replace(cep_inicial, '-', '')::int  AND replace(cep_final, '-', '')::int
					LIMIT 1

				), precificacao_local_divisa AS (

					SELECT preco,
						   correios_cotacao,
						   tbl_correios_servico.nome as nome_servico,
						   tbl_correios_servico.correios_servico,
						   tbl_correios_servico.codigo,
						   tbl_correios_cotacao.nivel
					FROM tbl_correios_precificacao
					JOIN tbl_correios_cotacao ON tbl_correios_cotacao.nivel = tbl_correios_precificacao.nivel
					JOIN tbl_correios_servico ON tbl_correios_cotacao.servico = tbl_correios_servico.correios_servico
					JOIN origem  ON origem.cidade  = tbl_correios_precificacao.origem
					JOIN destino ON destino.cidade = tbl_correios_precificacao.destino
					WHERE {$this->_peso} BETWEEN peso_inicial AND peso_final

				), precificacao_interestadual AS (

					SELECT preco,
						   correios_cotacao,
						   tbl_correios_servico.nome as nome_servico,
						   tbl_correios_servico.correios_servico,
						   tbl_correios_servico.codigo,
						   tbl_correios_cotacao.nivel
					FROM tbl_correios_origem_destino
					JOIN tbl_correios_cotacao ON tbl_correios_cotacao.nivel = (CASE
																				WHEN LENGTH(tbl_correios_origem_destino.valor) > 1
																				THEN tbl_correios_origem_destino.valor
																				ELSE 'I' || tbl_correios_origem_destino.valor
																			   END)
					JOIN tbl_correios_servico ON tbl_correios_cotacao.servico = tbl_correios_servico.correios_servico
					JOIN origem  ON origem.estado  = tbl_correios_origem_destino.origem_destino[1]
					JOIN destino ON destino.estado = tbl_correios_origem_destino.origem_destino[2]
					WHERE {$this->_peso} BETWEEN peso_inicial AND peso_final
					AND (origem.is_capital IS NOT TRUE OR destino.is_capital IS NOT TRUE)

				), precificacao_nacional AS (

					SELECT preco,
						   correios_cotacao,
						   tbl_correios_servico.nome as nome_servico,
						   tbl_correios_servico.correios_servico,
						   tbl_correios_servico.codigo,
						   tbl_correios_cotacao.nivel
					FROM tbl_correios_origem_destino
					JOIN tbl_correios_cotacao ON tbl_correios_cotacao.nivel = 'N' || tbl_correios_origem_destino.valor
					JOIN tbl_correios_servico ON tbl_correios_cotacao.servico = tbl_correios_servico.correios_servico
					JOIN origem  ON origem.estado  = tbl_correios_origem_destino.origem_destino[1]
					JOIN destino ON destino.estado = tbl_correios_origem_destino.origem_destino[2]
					WHERE {$this->_peso} BETWEEN peso_inicial AND peso_final
					AND origem.is_capital IS TRUE 
					AND destino.is_capital IS TRUE

				)
				SELECT MAX(dados_preco.preco_local_divisa) 	as preco_local_divisa,
					   MAX(dados_preco.preco_interestadual) as preco_interestadual,
					   MAX(dados_preco.preco_nacional) 		as preco_nacional,
					   codigo as codigo_servico,
					   nome_servico
				FROM (

					SELECT precificacao_local_divisa.preco + ($this->_pesoExcedente * valor_excedente.preco) as preco_local_divisa,
						   0 as preco_interestadual,
						   0 as preco_nacional,
						   precificacao_local_divisa.nome_servico,
						   precificacao_local_divisa.codigo
					FROM tbl_correios_cotacao
					JOIN precificacao_local_divisa  ON precificacao_local_divisa.correios_cotacao  = tbl_correios_cotacao.correios_cotacao
					JOIN tbl_correios_cotacao valor_excedente ON precificacao_local_divisa.nivel = valor_excedente.nivel
					AND valor_excedente.servico = precificacao_local_divisa.correios_servico
					AND valor_excedente.peso_inicial IS NULL
					AND valor_excedente.peso_final   IS NULL

						UNION

					SELECT 0 as preco_local_divisa,
						   precificacao_interestadual.preco + ($this->_pesoExcedente * valor_excedente.preco) as preco_interestadual,
						   0 as preco_nacional,
						   precificacao_interestadual.nome_servico,
						   precificacao_interestadual.codigo
					FROM tbl_correios_cotacao
					JOIN precificacao_interestadual ON precificacao_interestadual.correios_cotacao = tbl_correios_cotacao.correios_cotacao
					JOIN tbl_correios_cotacao valor_excedente ON precificacao_interestadual.nivel = valor_excedente.nivel
					AND valor_excedente.servico = precificacao_interestadual.correios_servico
					AND valor_excedente.peso_inicial IS NULL
					AND valor_excedente.peso_final   IS NULL

						UNION

					SELECT 0 as preco_local_divisa,
						   0 as preco_interestadual,
						   precificacao_nacional.preco + ($this->_pesoExcedente * valor_excedente.preco) as preco_nacional,
						   precificacao_nacional.nome_servico,
						   precificacao_nacional.codigo
					FROM tbl_correios_cotacao
					JOIN precificacao_nacional ON precificacao_nacional.correios_cotacao = tbl_correios_cotacao.correios_cotacao
					JOIN tbl_correios_cotacao valor_excedente ON precificacao_nacional.nivel = valor_excedente.nivel
					AND valor_excedente.servico = precificacao_nacional.correios_servico
					AND valor_excedente.peso_inicial IS NULL
					AND valor_excedente.peso_final   IS NULL

				) as dados_preco
				GROUP BY nome_servico, codigo
				";
		$query = $this->_pdo->query($sql);

		$dados = $query->fetchAll();

		if (count($dados) == 0) {
			throw new \Exception("Erro ao calcular frete offline, favor revisar os dados de endereco ou entrar em contato com o suporte. origem: {$this->_cepOrigem}, destino: {$this->_cepDestino}");
		}

		$dadosFrete = [];
		foreach ($dados as $key => $value) {

			$precoFrete = 0;
			if ($value["preco_local_divisa"] > 0) {

				$precoFrete = $value["preco_local_divisa"];

			} else if ($value["preco_interestadual"] > 0) {

				$precoFrete = $value["preco_interestadual"];

			} else if ($value["preco_nacional"] > 0) {

				$precoFrete = $value["preco_nacional"];

			} else {
				throw new \Exception("Erro ao calcular frete offline, favor revisar os dados de endereco ou entrar em contato com o suporte. origem: {$this->_cepOrigem}, destino: {$this->_cepDestino}");
			}

			if ($value["nome_servico"] == "PAC CONTRATO AGENCIA") {

				$acrescimoContrato = $precoFrete * 0.01;
				$precoFrete += $acrescimoContrato;
				$prazo = 10;

			}

			if ($value["nome_servico"] == "SEDEX CONTRATO AGENCIA") {

				$prazo = 5;

			}

			$dadosFrete[$key] = [
				"valor" => number_format($precoFrete, 2),
				"valor_real" => number_format($precoFrete, 2),
				"descricao" => $value["nome_servico"],
				"codigo" => $value["codigo_servico"],
				"prazo_entrega" => $prazo,
				"fabrica_contrato" => $this->_fabricaContrato
			];
		}
		return $dadosFrete;
	}

	public function calculoPeso() {

		if (empty($this->_altura) || empty($this->_comprimento) || empty($this->_largura) || empty($this->_peso)) {
			throw new \Exception("Dados para cálculo de peso incompletos");
		}

		//calculo peso cubico
		$pesoCubico = ($this->_altura * $this->_comprimento * $this->_largura) / 6000;

		//caso o peso cúbico seja maior que o peso e maior que 5kg, o mesmo deve ser considerado como peso final
		$peso = ($pesoCubico > $this->_peso && $pesoCubico > 5) ? $pesoCubico : $this->_peso;

		/*caso o peso seja maior que 10 kg, 
		  o correios cobra uma taxa X para cada kg adicional 
		  de acordo com a precificação da região (tratativa feita na query) */
		$pesoExcedente = 0;
		if ($peso > 10) {

			$pesoExcedente = ceil($peso - 10);
			$peso = 10;

		}
		//converter para gramas

		$peso *= 1000;
		$this->_peso = $peso;
		$this->_pesoExcedente = $pesoExcedente;

	}

	public function setDadosContrato() {

		$sql = "SELECT tbl_servico_correio.servico_correio,
					   descricao,
					   codigo,
					   chave_servico
			    FROM tbl_servico_correio
			    JOIN tbl_servico_correio_fabrica ON tbl_servico_correio_fabrica.servico_correio = tbl_servico_correio.servico_correio
			    WHERE fabrica = {$this->_fabricaContrato} 
			    AND tbl_servico_correio.ativo IS TRUE";
		$query = $this->_pdo->query($sql);

		$dados = $query->fetchAll();

		if (count($dados) == 0) {
			$this->consultaServicosContrato();
		}

	}

	public function consultaServicosContrato() {

		switch ($this->_fabricaContrato) {
			case 160:
				
				$this->_dadosContrato = (object) [
					'usuario'		   => "67647412000199", //$dados_cliente['usuario'],
					'senha'			   => "ir31um",
					'idContrato'	   => "9912329529",
		 			'idCartaoPostagem' => "0073789933"
				];
				break;
			case 11:
			case 172:
				
				$this->_dadosContrato = (object) [
					'usuario'		   => "05256426", //$dados_cliente['usuario'],
					'senha'			   => "n4nov",
					'idContrato'	   => "9912361764",
		 			'idCartaoPostagem' => "0070109605"
				];
				break;
			case 187:
				$this->_dadosContrato = (object) [
					'usuario'		   => "conair", //$dados_cliente['usuario'],
					'senha'			   => "4md13g",
					'idContrato'	   => "9912390259",
		 			'idCartaoPostagem' => "0074778145"
				];
				break;
			default:
				$this->_dadosContrato = (object) [
					'usuario'		   => "66494691000135",
					'senha'			   => "eagojr",
					'idContrato'	   => "9912358441",
					'idCartaoPostagem' => "0069835535"
				];
				break;
		}

		$retorno = $this->_sigepClient->__soapCall("buscaServicos", [$this->_dadosContrato]);

		$result = $retorno->result;

		foreach ($result as $chave => $valor) {
		}
			 
	}

	public function cotarFreteBraspress($dadosObjeto, $embarque) {

		$dadosPosto = $this->buscaPostoEmbarque($embarque);

	    $curl = curl_init();

        curl_setopt_array($curl, array(
	        CURLOPT_URL => $this->_urlBraspress,
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_ENCODING => "",
	        CURLOPT_MAXREDIRS => 10,
	        CURLOPT_TIMEOUT => 90,
	        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST => "POST",
	        CURLOPT_POSTFIELDS => json_encode([
	           "cnpjRemetente" => 66494691000135,
			   "cnpjDestinatario" => $dadosPosto["cnpj"],
			   "modal" => "R",
			   "tipoFrete" => "1",
			   "cepOrigem" => $dadosObjeto->sCepOrigem,
			   "cepDestino" => $dadosObjeto->sCepDestino,
			   "vlrMercadoria" => $dadosObjeto->nVlValorDeclarado,
			   "peso" => $dadosObjeto->nVlPeso,
			   "volumes" => 1,
			   "cubagem" => [
			   		[
			         "altura" => $dadosObjeto->nVlAltura / 100,
			         "largura" => $dadosObjeto->nVlLargura / 100,
			         "comprimento" => $dadosObjeto->nVlComprimento / 100,
			         "volumes" => 1
			        ]
			    ]
	        ]),
	        CURLOPT_HTTPHEADER => array(
	            "Authorization: Basic QUNBQ0lBRUxFVFJPX1BSRDpaJnQ3NSMjQyZEZ2wkNXUj",
	            "Content-Type: application/json"
            ),
        ));
		
        $response = curl_exec($curl);

        $arrRetorno = json_decode($response, true);

		return [
			"valor" => $arrRetorno["totalFrete"],
			"valor_real" => $arrRetorno["totalFrete"],
			"descricao" => utf8_encode("BRASPRESS - TRANSP. RODOVIÁRIO"),
			"codigo" => "BRASPRESS",
			"prazo_entrega" => (int) $arrRetorno["prazo"],
			"fabrica_contrato" => $this->_fabricaContrato,
			"transportadora" => true
		];

	}

	public function buscaPostoEmbarque($embarque) {

		$sql = "SELECT tbl_posto.cnpj
				FROM tbl_embarque
				JOIN tbl_posto ON tbl_posto.posto = tbl_embarque.posto
				WHERE embarque = {$embarque}";
		$res = $this->_pdo->query($sql);

		return $res->fetch();

	}

	public function etiquetaTransportadora($dados) {

		$idServico = $this->getServicoByCodigo($dados["codigo"], "transportadora");

		$idEtiquetaTransportadora = $this->verificaEtiquetaInserida($dados["embarque"], "transportadora");
		$idEtiquetaCorreios       = $this->verificaEtiquetaInserida($dados["embarque"], "correios");

		$etiqueta = "";
		if (!empty($idEtiquetaTransportadora)) {

			$sql = "UPDATE tbl_frete_transportadora SET
						preco    = ".$dados['valor'].",
						peso     = ".$dados['peso'].",
						caixa    = '".$dados['caixa']."',
						prazo_entrega = ".$dados['prazo'].",
						servico_transportadora = {$idServico}
					WHERE frete_transportadora = {$idEtiquetaTransportadora}";
			$query = $this->_pdo->query($sql);

			$sql = "SELECT codigo_rastreio as etiqueta FROM tbl_frete_transportadora
					WHERE frete_transportadora = {$idEtiquetaTransportadora}";
			$query = $this->_pdo->query($sql);

			$dados = $query->fetchAll();

			$etiqueta = $dados[0]["etiqueta"];

		} else {

			$sql = "INSERT INTO tbl_frete_transportadora (servico_transportadora, preco, peso, caixa, prazo_entrega, embarque) 
					VALUES ({$idServico},".$dados['valor'].",".$dados["peso"].",'".$dados["caixa"]."',".$dados["prazo"].",".$dados["embarque"].")
					RETURNING frete_transportadora";
			$query = $this->_pdo->query($sql);

			$dados = $query->fetchAll();

			$idEtiquetaTransportadora = $dados[0]["frete_transportadora"];

		}

		if (!empty($idEtiquetaCorreios)) {

			$sql = "UPDATE tbl_etiqueta_servico SET 
						embarque = NULL,
						preco 	 = NULL,
						peso 	 = NULL,
						caixa 	 = NULL 
					WHERE etiqueta_servico = {$idEtiquetaCorreios}";
			$query = $this->_pdo->query($sql);

		}

		return [
			"frete_transportadora" => $idEtiquetaTransportadora,
			"etiqueta" => (string) $etiqueta
		];

	}

	public function getServicoByCodigo($codigo, $servico = "correios") {

		if ($servico == "transportadora") {

			$sql = "SELECT servico_transportadora as id_servico
				    FROM tbl_servico_transportadora
				    WHERE ativo
				    AND codigo = '{$codigo}'";

		} else if ($servico == "correios") {

			$sql = "SELECT servico_correio as id_servico
				    FROM tbl_servico_correio
				    WHERE ativo
				    AND codigo = '{$codigo}'";

		} else {

			throw new \Exception("Serviço informado não configurado");

		}

		$query = $this->_pdo->query($sql);

		$dados = $query->fetchAll();

		if (count($dados) == 0) {
			throw new \Exception("Nenhum serviço encontrado para o código {$codigo}");
		}

		return $dados[0]["id_servico"];

	}

	public function verificaEtiquetaInserida($embarque, $servico = "correios") {

		if ($servico == "transportadora") {

			$sql = "SELECT frete_transportadora as id_etiqueta
				    FROM tbl_frete_transportadora
				    WHERE embarque = {$embarque}";

		} else if ($servico == "correios") {

			$sql = "SELECT etiqueta_servico as id_etiqueta
				    FROM tbl_etiqueta_servico
				    WHERE embarque = {$embarque}";

		} else {

			throw new \Exception("Serviço informado não configurado");

		}

		$query = $this->_pdo->query($sql);

		$dados = $query->fetchAll();

		return $dados[0]["id_etiqueta"];

	}

}
