<?php
/**
 * Array de regras padrões
 */
$regras = array(
	"posto|id" => array(
		"obrigatorio" => true
	),
	"os|data_abertura" => array(
		"obrigatorio" => true,
		"regex"       => "date",
		"function"    => array("valida_data_abertura")
	),
	"os|tipo_atendimento" => array(
		"obrigatorio" => true
	),
	"os|qtde_km" => array(
		"function" => array("valida_deslocamento")
	),
	"os|qtde_visitas" => array(
		"obrigatorio" => true
	),
	"consumidor|nome" => array(
		"obrigatorio" => true
	),
	"consumidor|cpf" => array(
		"function" => array("valida_consumidor_cpf"),
		"obrigatorio" => true
	),
	"consumidor|cep" => array(
		"regex" => "cep",
		"obrigatorio" => true,
	),
	"consumidor|cidade" => array(
		"obrigatorio" => true
	),
	"consumidor|estado" => array(
		"obrigatorio" => true
	),
	"consumidor|bairro" => array(
		"obrigatorio" => true
	),
	"consumidor|endereco" => array(
		"obrigatorio" => true
	),
	"consumidor|numero" => array(
		"obrigatorio" => true
	),
	"consumidor|complemento" => array(
		"obrigatorio" => false
	),
	"consumidor|telefone" => array(
		"obrigatorio" => true
	),
	"consumidor|email" => array(
		"regex" => "email",
	)
);

/**
 * Array de regex
 */
$regex = array(
	"date"     => "/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/",
	"cpf"      => "/[0-9]{3}\.[0-9]{3}\.[0-9]{3}\-[0-9]{2}/",
	"cnpj"     => "/[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}\-[0-9]{2}/",
	"cep"      => "/[0-9]{5}\-[0-9]{3}/",
	"email"    => "/^.[^@]+\@.[^@.]+\..[^@]+$/"
);

/**
 * Array para formatar o nome dos campos dentro da função valida_campos()
 */
$label = array(
	"posto|id"             => "Posto",
	"os|data_abertura"     => "Data de Abertura",
	"os|qtde_visitas"      => "Qtde Visitas",
	"consumidor|nome"      => "Nome do consumidor",
	"consumidor|cpf"       => "CPF do consumidor",
	"consumidor|cep"       => "CEP do consumidor",
	"consumidor|cidade"    => "Cidade do consumidor",
	"consumidor|estado"    => "Estado do consumidor",
	"consumidor|telefone"  => "Telefone do consumidor",
	"consumidor|email"     => "Email do consumidor"
);

/**
 * Função que valida os campos da os de acordo com o array $regras
 */
function valida_campos() {
	global $msg_erro, $regras, $campos, $label, $regex;

	foreach ($regras as $campo => $array_regras) {
		list($key, $value) = explode("|", $campo);

		$input_valor = $campos[$key][$value];

		foreach ($array_regras as $tipo_regra => $regra) {
			switch ($tipo_regra) {
				case 'obrigatorio':
					if (empty($input_valor) && $regra === true) {
						$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
						$msg_erro["campos"][]                 = "{$key}[{$value}]";
					}
					break;
				
				case 'regex':
					if (!empty($input_valor) && !preg_match($regex[$regra], $input_valor)) {
						$msg_erro["msg"][]    = "{$label[$campo]} inválido";
						$msg_erro["campos"][] = "{$key}[{$value}]";
					}
					break;

				case 'function':
					if (is_array($regra)) {
						foreach ($regra as $function) {
							try {
								call_user_func($function);
							} catch(Exception $e) {
								$msg_erro["msg"][] = $e->getMessage();
								$msg_erro["campos"][] = "{$key}[{$value}]";
							}
						}
					}
					break;
			}
		}
	}
}

function valida_produtos() {
	global $campos, $msg_erro;

	$produtos = $campos['produto'];

	foreach ($produtos as $posicao => $produto) {
		if (empty($produto["id"])) {
			continue;
		}

		if (empty($produto["qtde"])) {
			$msg_erro["msg"]["produto_qtde"] = "Informe a quantidade do produto";
			$msg_erro["campos"][]            = "produto[{$posicao}]";
			continue;
		}

		try {
			valida_posto_atende_produto_linha($produto["id"]);
		} catch (Exception $e)  {
			$msg_erro["msg"]["produto_linha"] = $e->getMessage();
			$msg_erro["campos"][]             = "produto[{$posicao}]";
			continue;
		}
	}
}

/**
 * OBSERVAÇÕES
 *
 * Funções que são chamadas no valida_campos() devem retornar o erro com throw new Exception()
 * Funções de validação que não são chamadas no valida_campos basta adicionar o erro a $msg_erro["msg"]
 * Essas mesmas regras valem para a função valida_pecas()
 */

/**
 * Função chamada na valida_campos()
 *
 * Função para validar se o posto atende a linha do produto
 */
function valida_posto_atende_produto_linha($produto) {
	global $con, $login_fabrica, $campos;

	$posto = $campos["posto"]["id"];

	if (!empty($produto) && !empty($posto)) {
		$sql = "SELECT * 
				FROM tbl_posto_fabrica
				INNER JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.produto = {$produto}
				INNER JOIN tbl_linha ON tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.linha = tbl_produto.linha
				INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = tbl_linha.linha
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_posto_fabrica.posto = {$posto}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Posto não atende a linha do produto selecionado");
		}
	}
}

/**
 * Função para validação de data de abertura
 */
function valida_data_abertura() {
	global $campos, $os;

	$data_abertura = $campos["os"]["data_abertura"];

	if (!empty($data_abertura) && empty($os)) {
		list($dia, $mes, $ano) = explode("/", $data_abertura);

		if (!checkdate($mes, $dia, $ano)) {
			throw new Exception("Data de abertura inválida");
		} else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 6 days")) {
			throw new Exception("Data de abertura não pode ser anterior a 7 dias");
		}
	}
}

/**
 * Função para validar o CPF do Consumidor
 */
function valida_consumidor_cpf() {
	global $con, $campos;

	$cpf = preg_replace("/\D/", "", $campos["consumidor"]["cpf"]);

	if (strlen($cpf) > 0) {
		$sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("CPF/CNPJ do Consumidor $cpf é inválido");
		}
	}
}

/**
 * Função que valida o deslocamento
 */
function valida_deslocamento() {
	global $campos;

	$qtde_km = $campos["os"]["qtde_km"];

	if (!strlen($qtde_km)) {
		throw new Exception("Por favor clique no botão Calcular KM");
	}
}

?>
