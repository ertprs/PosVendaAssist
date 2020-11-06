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
if(!function_exists('valida_campos')) {
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
}
if(!function_exists('valida_produtos')) {
function valida_produtos() {
	global $campos, $msg_erro, $login_fabrica;

	$produtos = $campos['produto'];	

	foreach ($produtos as $posicao => $produto) {
		if (empty($produto["id"])) {	
			$contador_produto = $contador_produto + 1;
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

	$contador = count($produtos);

	if(in_array($login_fabrica, array(152)) AND $contador == $contador_produto){		
		$msg_erro["msg"]["produto"] = "Adicione um produto";				
	}	
}
}
#Verifica auditoria_unica
if(!function_exists('verifica_auditoria_unica')) {
function verifica_auditoria_unica($condicao, $os) {
	global $con;

	$sql = "SELECT tbl_auditoria_os.auditoria_status FROM tbl_auditoria_os
			INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
			WHERE os = {$os}
			AND {$condicao}
			ORDER BY data_input DESC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		return true;
	} else {
		return false;
	}
}
}
if(!function_exists('buscaAuditoria')) {
function buscaAuditoria($condicao) {
	global $con, $login_fabrica;

	$sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE $condicao";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		return array("resultado" => true, "auditoria" => pg_fetch_result($res, 0, "auditoria_status"));
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
if(!function_exists('valida_posto_atende_produto_linha')) {
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
}
function valida_celular_os() {
	global $campos;

	$celular = $campos["consumidor"]["celular"];

	if (strlen($celular) > 0) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

		try {
			$celular          = $phoneUtil->parse("+55".$celular, "BR");
			$isValid          = $phoneUtil->isValidNumber($celular);
			$numberType       = $phoneUtil->getNumberType($celular);
			$mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

			if (!$isValid || $numberType != $mobileNumberType) {
				throw new Exception("Número de Celular inválido");
			}
		} catch (\libphonenumber\NumberParseException $e) {
			throw new Exception("Número de Celular inválido");
		}
	}
}
/**
 * Função que verifica se a revenda não existe se não existir grava
 */
if(!function_exists('verifica_revenda')) {
function verifica_revenda() {
	global $con, $campos;

	$revenda     = $campos["revenda"]["id"];
	$nome        = $campos["revenda"]["nome"];
	$cnpj        = preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"]);
	$cep         = preg_replace("/[\-]/", "", $campos["revenda"]["cep"]);
	$cidade      = $campos["revenda"]["cidade"];
	$estado      = $campos["revenda"]["estado"];
	$bairro      = $campos["revenda"]["bairro"];
	$endereco    = $campos["revenda"]["endereco"];
	$numero      = $campos["revenda"]["numero"];
	$complemento = $campos["revenda"]["complemento"];
	$telefone    = $campos["revenda"]["telefone"];

	if (empty($revenda) && !empty($cnpj)) {
		$sql = "SELECT revenda
				FROM tbl_revenda
				WHERE cnpj = '{$cnpj}'";
		$res = pg_query($con, $sql);

		if (strlen($cidade) > 0 && strlen($estado) > 0) {
			$sql_cidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res_cidade = pg_query($con, $sql_cidade);

			if (pg_num_rows($res_cidade) > 0) {
				$cidade = pg_fetch_result($res_cidade, 0, "cidade");
			} else {
				$cidade = "null";
			}
		}

		if (pg_num_rows($res) > 0) {
			$revenda = pg_fetch_result($res, 0, "revenda");

			$sql = "UPDATE tbl_revenda SET
						nome = '{$nome}',
						cep  = '{$cep}',
						cidade  = {$cidade},
						bairro  = '{$bairro}',
						endereco  = '{$endereco}',
						numero  = '{$numero}',
						complemento  = '{$complemento}',
						fone  = '{$telefone}'
					WHERE revenda = {$revenda}";
			$res = pg_query($con, $sql);
		} else {
			$sql = "INSERT INTO tbl_revenda
					(nome, cnpj, cep, cidade, bairro, endereco, numero, complemento, fone)
					VALUES
					('{$nome}', '{$cnpj}', '{$cep}', {$cidade}, '{$bairro}', '{$endereco}', '{$numero}', '{$complemento}', '{$telefone}')
					RETURNING revenda";
			$res = pg_query($con, $sql);

			$revenda = pg_fetch_result($res, 0, "revenda");
		}
	}

	return (empty($revenda)) ? "null" : $revenda;
}
}

/**
 * Função para validação de data de abertura
 */
if(!function_exists('valida_data_abertura')) {
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
}
/**
 * Função para validar o CPF do Consumidor
 */
if(!function_exists('valida_consumidor_cpf')) {
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
}
/**
 * Função que valida o deslocamento
 */
if(!function_exists('valida_deslocamento')) {
function valida_deslocamento() {
	global $campos;

	$qtde_km = $campos["os"]["qtde_km"];

	if (!strlen($qtde_km)) {
		throw new Exception("Por favor clique no botão Calcular KM");
	}
}
}
/**
 * Função para validar anexo
 */
if(!function_exists('valida_anexo')) {
function valida_anexo() {
	global $campos, $msg_erro;

	$count_anexo = array();

	foreach ($campos["anexo"] as $key => $value) {
		if (strlen($value) > 0) {
			$count_anexo[] = "ok";
		}
	}

	if(!count($count_anexo)){
		$msg_erro["msg"][] = "Os anexos são obrigatórios";
	}
}
}
$valida_anexo = "valida_anexo";

/**
 * Função para mover os anexos do bucket temporario para o bucket da Ordem de Serviço
 */
if(!function_exists('grava_anexo')) {
function grava_anexo() {
	global $campos, $s3, $os;

	list($dia, $mes, $ano) = explode("/", getValue("os[data_abertura]"));

	$arquivos = array();

	foreach ($campos["anexo"] as $key => $value) {
		if ($campos["anexo_s3"][$key] != "t" && strlen($value) > 0) {
			$ext = preg_replace("/.+\./", "", $value);

			$arquivos[] = array(
				"file_temp" => $value,
				"file_new"  => "{$os}_{$key}.{$ext}"
			);
		}
	}

	if (count($arquivos) > 0) {
		$s3->moveTempToBucket($arquivos, $ano, $mes, false);
	}
}
}
$grava_anexo = "grava_anexo";

?>
