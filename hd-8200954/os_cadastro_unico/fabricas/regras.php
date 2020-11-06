<?php

/**
 * Variavel que define se irá gravar a os_item ou não
 */
$grava_os_item = true;
$historico_alteracao = false;
$grava_defeito_peca = false;
$grava_os_item_function = "grava_os_item";
$usa_campo_atendimento = true;

$id_hd_chamado_helpdesk = null;

/**
 * Array de regras padrões
 */

$regras = array(
	"posto|id" => array(
		"obrigatorio" => true
	),
	"os|hd_chamado" => array(
		"function" => array("valida_atendimento")
	),
	"os|data_abertura" => array(
		"obrigatorio" => true,
		"regex"       => "date",
		"function"    => array("valida_data_abertura")
	),
	"os|data_compra" => array(
		"obrigatorio" => true,
		"regex"       => "date",
		"function"    => array("valida_data_compra")
	),
	"os|tipo_atendimento" => array(
		"obrigatorio" => true
	),
	"os|qtde_km" => array(
		"function" => array("valida_deslocamento")
	),
	"os|nota_fiscal" => array(
		"obrigatorio" => true
	),
	"os|defeito_reclamado" => array(
		"obrigatorio" => true
	),
	"consumidor|nome" => array(
		"obrigatorio" => true
	),
	"consumidor|cpf" => array(
		"function" => array("valida_consumidor_cpf")
	),
	"consumidor|cep" => array(
		"regex" => "cep"
	),
	"consumidor|cidade" => array(
		"obrigatorio" => true
	),
	"consumidor|estado" => array(
		"obrigatorio" => true
	),
	"consumidor|telefone" => array(
	),
	"consumidor|celular" => array(
		"function" => array("valida_celular_os")
	),
	"consumidor|email" => array(
		"regex" => "email",
	),
	"revenda|nome" => array(
		"obrigatorio" => true
	),
	"revenda|cnpj" => array(
		"obrigatorio" => true,
		"function"    => array("valida_revenda_cnpj")
	),
	"revenda|cidade" => array(
		"obrigatorio" => true
	),
	"revenda|estado" => array(
		"obrigatorio" => true
	),
	"produto|id" => array(
		"obrigatorio" => true,
		"function"    => array("valida_posto_atende_produto_linha")
	),
	"produto|serie" => array(
		"function" => array("valida_numero_de_serie")
	),
	"produto|defeito_constatado" => array(
		"function" => array(
			($usa_linha_defeito_constatado == 't') ? "valida_linha_defeito_constatado" : "valida_familia_defeito_constatado",
			"valida_defeito_constatado_peca_lancada"
		)
	)
);

if ($cook_idioma != "pt-br") {
	unset($regras["consumidor|cep"]["regex"]);
	$regras["consumidor|cpf"]["function"] = [];
	$regras["revenda|cnpj"]["function"] = [];
	$regras["consumidor|cep"]["obrigatorio"] = false;
}

if (isset($fabrica_usa_subproduto)) {
	$regras["subproduto|id"] = array(
		"function" => array("valida_subproduto")
	);

	$regras["subproduto|serie"] = array(
		"function" => array("valida_subproduto_numero_de_serie")
	);
}

$anexos_obrigatorios = ["notafiscal"];

/**
 * Array de regras padrões das peças do produto
 */
$regras_pecas = array(
	"lista_basica" => true,
	"servico_realizado" => true,
	"bloqueada_garantia" => true
);

/**
 * Array de regras padrões das peças do subproduto
 */
$regras_subproduto_pecas = array(
	"lista_basica" => true ,
	"servico_realizado" => true
);

/**
 * Array de regex
 */
$regex = array(
	"date"     => "/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/",
	"cpf"      => "/[0-9]{3}\.[0-9]{3}\.[0-9]{3}\-[0-9]{2}/",
	"cnpj"     => "/[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}\-[0-9]{2}/",
	"cep"      => "/[0-9]{5}\-[0-9]{3}/",
	"email"    => "/\@.[^@.]+\..[^@]+$/"
);
// /^.[^@]+\@.[^@.]+\..[^@]+$/ HD-7649231

/**
 * Array para formatar o nome dos campos dentro da função valida_campos()
 */
$label = array(
	"posto|id"             => traduz("Posto"),
	"os|data_abertura"     => traduz("Data de Abertura"),
	"os|data_compra"       => traduz("Data Compra"),
	"os|tipo_atendimento"  => traduz("Tipo de Atendimento"),
	"os|nota_fiscal"       => traduz("Nota Fiscal"),
	"os|defeito_reclamado" => traduz("Defeito Reclamado"),
	"consumidor|nome"      => traduz("Nome do consumidor"),
	"consumidor|cpf"       => traduz("CPF do consumidor"),
	"consumidor|cep"       => traduz("CEP do consumidor"),
	"consumidor|cidade"    => traduz("Cidade do consumidor"),
	"consumidor|estado"    => traduz("Estado do consumidor"),
	"consumidor|telefone"  => traduz("Telefone do consumidor"),
	"consumidor|email"     => traduz("Email do consumidor"),
	"revenda|nome"         => traduz("Nome da revenda"),
	"revenda|cnpj"         => traduz("CNPJ da revenda"),
	"revenda|telefone"     => traduz("Telefone da revenda"),
	"produto|id"           => traduz("Produto"),
	"produto|serie"        => traduz("Número de Série"),
	"os|anexo_nota_fiscal" => traduz("Anexo da nota fiscal"),
	"anexo"                => traduz("Anexo é obrigatório"),
);

/** NÃO ACABADO   Função para validar numero de série  **/
function valida_numero_de_serie() {
	global $con, $campos, $login_fabrica,$msg_erro;

	$produto_id = $campos["produto"]["id"];
	$produto_serie = $campos["produto"]["serie"];

	if (strlen($produto_id) > 0) {
		$sql = "SELECT produto, JSON_FIELD('pecas_reposicao', parametros_adicionais) AS pecas_reposicao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto_id} AND numero_serie_obrigatorio IS TRUE;";
		$res = pg_query($con,$sql);
		$pecas_reposicao = pg_fetch_result($res, 0, pecas_reposicao);
		if(pg_num_rows($res) > 0 && empty($produto_serie) && $pecas_reposicao !== 't'){
			$msg_erro["msg"]["campo_obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
			$msg_erro["campos"][] = "produto[serie]";
		}
	}
}

function valida_subproduto_numero_de_serie() {
	global $con, $campos, $login_fabrica,$msg_erro;

	$produto_id = $campos["subproduto"]["id"];
	$produto_serie = $campos["subproduto"]["serie"];

	if (strlen($produto_id) > 0) {
		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto_id} AND numero_serie_obrigatorio IS TRUE;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0 && empty($produto_serie)){
			$msg_erro["msg"]["campo_obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
			$msg_erro["campos"][] = "subproduto[serie]";
		}
	}
}

function buscaAuditoria($condicao) {
	global $con;

	$sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE $condicao";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		return array("resultado" => true, "auditoria" => pg_fetch_result($res, 0, "auditoria_status"));
	}
}

function aprovadoAuditoria($cond_auditoria) {
	global $con,$login_fabrica,$os;

	if (empty($cond_auditoria)) {
		throw new Exception("Erro ao abrir OS - Auditoria não configurada");
	}

	$sql = "SELECT auditoria_os FROM tbl_auditoria_os
			INNER JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
			INNER JOIN tbl_auditoria_status ON tbl_auditoria_os.auditoria_status = tbl_auditoria_status.auditoria_status
			WHERE tbl_auditoria_os.os = {$os}
			AND tbl_auditoria_os.liberada IS NOT NULL
			AND {$cond_auditoria}";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		return true;
	}else{
		return false;
	}
}

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
					if (!strlen($input_valor) && $regra === true) {
						$msg_erro["msg"]["campo_obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
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

/**
 * Função que valida as peças obrigatorias conforme o defeito constatado
 */

function valida_lancar_peca_obrigatorio(){
	global $login_fabrica, $msg_erro, $campos, $con, $defeitoConstatadoMultiplo;

    $pecas_pedido = $campos["produto_pecas"];

    if (isset($defeitoConstatadoMultiplo)) {
		$defeitos_constatados = $campos["produto"]["defeitos_constatados_multiplos"];
	} else {
		$defeitos_constatados = $campos["produto"]["defeito_constatado"];
	}

    if(!empty($defeitos_constatados)) {

        $sql = "
        	SELECT
        		defeito_constatado
            FROM tbl_defeito_constatado
            WHERE fabrica = {$login_fabrica}
            AND defeito_constatado IN ({$defeitos_constatados})
            AND lancar_peca;
        ";

        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0) {
        	$sql_prod = "SELECT produto FROM tbl_produto WHERE produto = ".$campos['produto']['id']." AND troca_obrigatoria IS FALSE";
        	$res_prod = pg_query($con, $sql_prod);

        	if (pg_num_rows($res_prod) > 0) {
	        	if(!verifica_peca_lancada(false) === true){
	        		$msg_erro["msg"]["peca_lancadas"] = "É obrigatório lançar peça para este(s) defeito(s) constatado(s)!";
				}
        	}
        }
    }
}

$valida_lancar_peca_obrigatorio = 'valida_lancar_peca_obrigatorio';


/**
 * Função que valida as peças do produto $regras_pecas
 */
function valida_pecas($nome = "produto_pecas") {
	global $con, $msg_erro, $login_fabrica, $regras_pecas, $regras_subproduto_pecas, $campos, $areaAdmin;
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

			if ($pecasExcedenteLB == true) {
				if($campos_peca['tem_obs'] == 't' && empty($campos_peca["obs"])){
					$msg_erro["msg"]["peca_qtde"] = traduz('favor.informar.o.motivo.da.solicitacao.da.peca.%', null, null, $referencia);
                    			$msg_erro["campos"][] = "{$nome}[{$posicao}]";
                    			continue;
                		}
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
								
								if(isset($campos_peca["subitem"])){
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

								if ($pecas_os[$peca]["qtde"] > $lista_basica_qtde) {
									$msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça maior que a permitida na lista básica");
									$msg_erro["campos"][]                 = "{$nome}[{$posicao}]";
								}
							}
						}
						break;
					case 'servico_realizado':
						if ($regra === true && !empty($peca) && (empty($servico_id) or $servico_id == 'null')) {
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
								$sql_tp = "select descricao from tbl_tipo_atendimento where tipo_atendimento = {$campos["os"]["tipo_atendimento"]} and fabrica = {$login_fabrica} and fora_garantia is not true ";
								$res_tp = pg_query($con, $sql_tp);
								$sql_ge = "SELECT descricao FROM tbl_servico_realizado where servico_realizado = $servico_id and gera_pedido";
								$res_ge = pg_query($con, $sql_ge);
								if(pg_num_rows($res_peca) > 0 AND pg_num_rows($res_tp) > 0 and pg_num_rows($res_ge) > 0){
									$msg_erro["msg"][] = traduz("Peça bloqueada para garantia, entrar em contato com fabricante");
									$msg_erro["campos"][] = "{$nome}[{$posicao}]";
								}
							}
						}
						break;

					case 'peca_subitem':
							$produto  = $campos["produto"]["id"];
							$peca_mae = $campos_peca["subitem"];

							if(!empty($peca_mae)){
								$sql = "SELECT tbl_peca_container.peca_container, 
											   tbl_peca_container.qtde
										FROM tbl_peca_container
									WHERE fabrica  	   = {$login_fabrica}
										AND produto    = {$produto}
										AND peca_filha = {$peca}
										AND peca_mae   = {$peca_mae}";
								$res = pg_query($con, $sql);

								if (pg_num_rows($res) == 0) {
									$msg_erro["msg"][]    = traduz("Peça não consta na lista básica do produto");
									$msg_erro["campos"][] = "{$nome}[{$posicao}]";
									continue;
								
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

									if ($pecas_os[$peca]["qtde"] > $lista_basica_qtde) {
										$msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça maior que a permitida na lista básica");
										$msg_erro["campos"][]                 = "{$nome}[{$posicao}]";
									}
								}
							}
						break;
				}
			}
		}

	}

}

$valida_pecas = "valida_pecas";

/**
 * OBSERVAÇÕES
 *
 * Funções que são chamadas no valida_campos() devem retornar o erro com throw new Exception()
 * Funções de validação que não são chamadas no valida_campos basta adicionar o erro a $msg_erro["msg"]
 * Essas mesmas regras valem para a função valida_pecas()
 */

/**
 * Função para validar a garantia do produto
 */
function valida_garantia($boolean = false, $arrayCampos = []) {
	global $con, $login_fabrica, $campos, $msg_erro;

	if (count($arrayCampos) > 0) {

		$data_compra   = $arrayCampos["data_compra"];
		$data_abertura = $arrayCampos["data_abertura"];
		$produto       = $arrayCampos["produto_id"];

	} else {

		$data_compra   = $campos["os"]["data_compra"];
		$data_abertura = $campos["os"]["data_abertura"];
		$produto       = $campos["produto"]["id"];

	}

	if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
		$sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$garantia = pg_fetch_result($res, 0, "garantia");

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

$valida_garantia = "valida_garantia";

/**
 * Função para validar a garantia da peça
 */
function valida_garantia_item() {
	global $con, $login_fabrica, $campos, $msg_erro;

	$data_compra	= $campos["os"]["data_compra"];
	$data_abertura	= $campos["os"]["data_abertura"];
	$produto		= $campos["produto"]["id"];
	$pecas			= $campos["produto_pecas"];

	if (!empty($produto)) {
		foreach ($pecas as $key => $peca) {
			if (empty($peca["id"])) {
				continue;
			}

			if(!empty($peca['servico_realizado'])) {
				$sql = "SELECT gera_pedido FROM tbl_servico_realizado where servico_realizado = ".$peca['servico_realizado'];
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$gera_pedido = pg_fetch_result($res,0,'gera_pedido');
				}
			}

			if (!empty($peca['id']) && !empty($data_compra) && !empty($data_abertura) && $gera_pedido == 't') {
				$sql = "SELECT referencia, garantia_diferenciada FROM tbl_peca where peca= ".$peca['id'];
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$referencia = pg_fetch_result($res, 0, "referencia");
					$garantia = pg_fetch_result($res, 0, "garantia_diferenciada");

					if($garantia > 0) {
						if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
							$msg_erro["msg"][] = traduz('peca.%.fora.de.garantia', null, null, $referencia);
						}
					}
				}
			}
		}
	}
}

$valida_garantia_item = "valida_garantia_item";

/**
 * Função para validar anexo
 */
function valida_anexo() {
	global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $valida_anexo_boxuploader;

	if (empty($valida_anexo_boxuploader)) {
		if ($fabricaFileUploadOS) {
			$anexo_chave = $campos["anexo_chave"];
			$tdocs = new TDocs($con, $login_fabrica, "os");

			if ($anexo_chave != $os) {
				$anexos = $tdocs->getByHashTemp($anexo_chave);
				if(empty($anexos)) {
					$msg_erro["msg"][] = traduz("Os anexos são obrigatórios #1");
				}
			} else {
				$anexos = $tdocs->getdocumentsByRef($anexo_chave);
				if (empty($anexos->url)) {
					$msg_erro["msg"][] = traduz("Os anexos são obrigatórios #2");
				}
			}

		} else {
			$count_anexo = array();

			foreach ($campos["anexo"] as $key => $value) {
				if (strlen($value) > 0) {
					$count_anexo[] = "ok";
				}
			}

			if(!count($count_anexo)){
				$msg_erro["msg"][] = traduz("Os anexos são obrigatórios #3");
			}
		}
	}
}

$valida_anexo = "valida_anexo";


function valida_anexo_boxuploader() {
	global $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $fabricaFileUploadOS, $campos, $anexos_inseridos, $anexos_obrigatorios;

	$anexos_inseridos = $anexos_inseridos();

	if ($fabricaFileUploadOS) {

		$posto_interno_nao_valida_anexo = \Posvenda\Regras::get("posto_interno_nao_valida_anexo", "ordem_de_servico", $login_fabrica);
		
		if($posto_interno_nao_valida_anexo == true){
			$sql = "SELECT tbl_posto_fabrica.tipo_posto from tbl_tipo_posto
					JOIN tbl_posto_fabrica on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
					WHERE   tbl_posto_fabrica.posto = {$campos["posto"]['id']}
					AND 	tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND     tbl_tipo_posto.posto_interno is not true ";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) == 0) {
				return;
			}
		}

		$nao_valida_anexo_fora_garantia = \Posvenda\Regras::get("nao_valida_anexo_fora_garantia", "ordem_de_servico", $login_fabrica);

		if ($nao_valida_anexo_fora_garantia == true) {

			$tipo_atendimento = $campos["os"]["tipo_atendimento"];

		    $sql = "SELECT tipo_atendimento
		            FROM tbl_tipo_atendimento
		            WHERE fabrica = {$login_fabrica} 
		            AND tipo_atendimento = $tipo_atendimento 
		            AND fora_garantia IS NOT TRUE";
		    $res = pg_query($con, $sql);

		    if (pg_num_rows($res) == 0) {
		    	return;
		    }

		}

		$anexos_pendentes = [];

		foreach ($anexos_obrigatorios as $codigo_anexo) {

			if (!in_array($codigo_anexo, $anexos_inseridos)) {

				$sql = "SELECT nome
						FROM tbl_anexo_tipo
						WHERE codigo = '$codigo_anexo'";
				$res = pg_query($con, $sql);

				$anexos_pendentes[] = pg_fetch_result($res, 0, 'nome');
			}

		}

		if (count($anexos_pendentes) > 0) {

			$msg_erro["msg"][] = traduz("Os seguintes anexos são obrigatórios: ").implode(", ", $anexos_pendentes);

		}

	}

}

function retorna_anexos_inseridos() {
	global $campos, $con, $login_fabrica, $os, $fabricaFileUploadOS;

	$anexo_chave 	  = $campos["anexo_chave"];
	$anexos_inseridos = [];

    if (!empty($os)){
        $cond_tdocs = "AND tbl_tdocs.referencia_id = {$os}";
    }else{
        $cond_tdocs = "AND tbl_tdocs.hash_temp = '{$anexo_chave}'";
    }

	$sql = "SELECT obs
			FROM   tbl_tdocs
			WHERE  tbl_tdocs.fabrica = {$login_fabrica}
			AND    tbl_tdocs.situacao = 'ativo'
			{$cond_tdocs}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		while ($dados = pg_fetch_object($res)) {

			$json_obs = json_decode($dados->obs, true);

			$anexos_inseridos[] = $json_obs[0]['typeId'];

		}

	}

	return $anexos_inseridos;

}

$anexos_inseridos = "retorna_anexos_inseridos";


/**
 * Função para mover os anexos do bucket temporario para o bucket da Ordem de Serviço
 */
function grava_anexo() {
	global $campos, $s3, $os, $fabricaFileUploadOS, $con, $login_fabrica, $msg_erro;
	if ($fabricaFileUploadOS) {
		$anexo_chave = $campos["anexo_chave"];

		if ($anexo_chave != $os) {
			$tdocs = new TDocs($con, $login_fabrica, "os");

			$anexos = $tdocs->getByHashTemp($anexo_chave);

			if (!empty($anexos)) {
				if (!$tdocs->updateHashTemp($anexo_chave, $os)) {
					$msg_erro["msg"][] = traduz("Erro ao gravar anexos");
				}
			}
		}
	} else {
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

/**
 * Função que verifica se a peça precisa de anexo na Ordem de Serviço
 * Segundo parâmetro opcional para retornar a quantidade permitida de anexos.
 */
function verifica_peca_anexo($peca, &$qtde=null) {
	$qtde_max_anexos_peca = 'NULL';
	global $con, $login_fabrica, $qtde_max_anexos_peca;

	if (!empty($peca)) {
		$sql = "SELECT parametros_adicionais FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");
			$parametros_adicionais = json_decode($parametros_adicionais, true);

			if ($parametros_adicionais['anexo_os'] === true or $parametros_adicionais['anexo_os'] == 't') {
                $qtde_max_anexos_peca = 1; // padrão 1
				if ($parametros_adicionais['qtde_anexos']) {
					$qtde_max_anexos_peca = $parametros_adicionais['qtde_anexos'];
				}
                return true;
			}
		}
	}
	return false;
}

/**
 * Função para validar anexo de peça
 */
function valida_anexo_peca() {
	global $campos, $msg_erro, $login_fabrica, $con, $fabrica_usa_subproduto;

	$produto = $campos["produto"]["id"];
	$pecas   = $campos["produto_pecas"];
	$anexos  = $campos["anexo_peca"];

	if (!empty($produto)) {
		foreach ($pecas as $key => $peca) {
			if (empty($peca["id"])) {
				continue;
			}

			if (verifica_peca_anexo($peca["id"])) {
				$anexo = $anexos[$produto][$peca["id"]][0];

				if (empty($anexo)) {
					$msg_erro["msg"]["anexo_peca"] = traduz("Os anexos das peças são obrigatórios");
					break;
				}
			}
		}
	}

	if (isset($fabrica_usa_subproduto)) {
		$subproduto       = $campos["subproduto"]["id"];
		$subproduto_pecas = $campos["subproduto_pecas"];
		$anexos           = $campos["anexo_peca"];

		if (!empty($subproduto)) {
			foreach ($subproduto_pecas as $key => $peca) {
				if (empty($peca["id"])) {
					continue;
				}

				if (verifica_peca_anexo($peca["id"])) {
					$anexo = $anexos[$subproduto][$peca["id"]];

					if (empty($anexo)) {
						$msg_erro["msg"]["anexo_peca"] = traduz("Os anexos das peças são obrigatórios");
						break;
					}
				}
			}
		}
	}
}

$valida_anexo_peca = "valida_anexo_peca";

/**
 * Função para mover os anexos das peças do bucket temporario para o bucket dos Itens da Ordem de Serviço
 */
function grava_anexo_peca() {
	// 07-2015 - MLG - Alterando para aceitar vários anexos por peça
	global $campos, $os, $login_fabrica, $con , $qtde_max_anexos_peca;

	$arquivos = array();

	$grava_anexos    = $campos["anexo_peca"];
	$grava_anexos_s3 = $campos["anexo_peca_s3"];

	$sql = "SELECT tbl_os_produto.os_produto, tbl_os_produto.produto, tbl_os_item.os_item, tbl_peca.peca
			  FROM tbl_os_item
			  JOIN tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca  AND tbl_peca.fabrica = {$login_fabrica}
			  JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			  JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os AND tbl_os.fabrica   = {$login_fabrica}
			 WHERE tbl_os.os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		while ($peca_anexo = pg_fetch_object($res)) {
			$qtde_max_anexos_peca = 0;
			if (verifica_peca_anexo($peca_anexo->peca, $qtde_max_anexos_peca)) {

				for ($i=0; $i < $qtde_max_anexos_peca; $i++) {
					$anexo    = $grava_anexos[$peca_anexo->produto][$peca_anexo->peca][$i];
					$anexo_s3 = $grava_anexos_s3[$peca_anexo->produto][$peca_anexo->peca][$i];

					//echo "Info anexo: $anexo | S3? $anexo_s3<br />" ;

					if ($anexo_s3 != "t" && !empty($anexo)) {
						$ext = pathinfo($anexo, PATHINFO_EXTENSION);

						if ($i) { // o primeiro anexo (núm. '0') não usa posição no nome, ara manter a compatibilidade
							$attach_new_name = "{$os}_{$peca_anexo->os_produto}_{$peca_anexo->os_item}_{$i}.{$ext}";
						} else {
							$attach_new_name = "{$os}_{$peca_anexo->os_produto}_{$peca_anexo->os_item}.{$ext}";
						}

						$arquivos[] = array(
							"file_temp" => $anexo,
							"file_new"  => $attach_new_name
						);
					}
				}
			}
		}

        if (count($arquivos) > 0) {
            $s3 = new AmazonTC("os_item", $login_fabrica);
			$s3->moveTempToBucket($arquivos, null, null, false);
        }
	}
}

$grava_anexo_peca = "grava_anexo_peca";

/**
 * Função chamada na valida_campos()
 *
 * Função para validar se o posto atende a linha do produto
 */
function valida_posto_atende_produto_linha() {
	global $con, $login_fabrica, $campos;

	$produto = $campos["produto"]["id"];
	$posto   = $campos["posto"]["id"];

	if (!empty($produto) && !empty($posto)) {
		$sql = "SELECT *
				FROM tbl_posto_fabrica
				INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
				INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
				INNER JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.produto = {$produto}
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_posto_fabrica.posto = {$posto}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Posto não atende a linha do produto selecionado");
		}
	}
}

/**
 * Função chamada na valida_campos()
 *
 * Função para validar a amarração do defeito constatado com a famí­lia do produto
 */
function valida_familia_defeito_constatado() {
	global $con, $login_fabrica, $campos, $defeitoConstatadoMultiplo;

	$produto = $campos["produto"]["id"];
	$defeitos_constatados = array();

	if (isset($defeitoConstatadoMultiplo)) {
		$defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
	} else {
		$defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
	}

	if (!empty($produto) && count($defeitos_constatados) > 0) {
		foreach($defeitos_constatados as $defeito_constatado) {
			if(strlen($defeito_constatado)>0){
				$sql = "SELECT *
						FROM tbl_diagnostico
						INNER JOIN tbl_familia ON tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.familia = tbl_diagnostico.familia
						INNER JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.familia = tbl_familia.familia
						WHERE tbl_diagnostico.fabrica = {$login_fabrica}
						AND tbl_diagnostico.defeito_constatado = {$defeito_constatado}
						AND tbl_produto.produto = {$produto}";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					throw new Exception("Defeito constatado não pertence a famí­lia do produto");
				}
			}
		}
	}
}

/**
 * Função chamada na valida_campos()
 *
 * Função para validar a amarração do defeito constatado com a linha do produto
 */
function valida_linha_defeito_constatado() {
	global $con, $login_fabrica, $campos, $defeitoConstatadoMultiplo;

	$produto = $campos["produto"]["id"];
	$defeitos_constatados = array();

	if (isset($defeitoConstatadoMultiplo)) {
		$defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
	} else {
		$defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
	}

	if (!empty($produto) && count($defeitos_constatados) > 0) {
		foreach($defeitos_constatados as $defeito_constatado) {
			if(strlen($defeito_constatado)>0){
				$sql = "SELECT *
						FROM tbl_diagnostico
						INNER JOIN tbl_linha ON tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.linha = tbl_diagnostico.linha
						INNER JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.linha = tbl_linha.linha
						WHERE tbl_diagnostico.fabrica = {$login_fabrica}
						AND tbl_diagnostico.defeito_constatado = {$defeito_constatado}
						AND tbl_produto.produto = {$produto}";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					throw new Exception("Defeito constatado não pertence a linha do produto");
				}
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
function verifica_revenda($tipo=null) {
	global $con, $campos, $login_fabrica;

	$revenda     = $campos["revenda"]["id"];
	$nome        = substr(pg_escape_string($con,$campos["revenda"]["nome"]), 0, 50);
	$cnpj        = preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"]);
	$cep         = preg_replace("/[\-]/", "", $campos["revenda"]["cep"]);
	$cidade      = pg_escape_string($con,$campos["revenda"]["cidade"]);
	$estado      = pg_escape_string($con,$campos["revenda"]["estado"]);
	$bairro      = pg_escape_string($con,$campos["revenda"]["bairro"]);
	$endereco    = pg_escape_string($con,$campos["revenda"]["endereco"]);
	$endereco    = substr($endereco,0,60);
	$numero      = $campos["revenda"]["numero"];
	$complemento = pg_escape_string($con,$campos["revenda"]["complemento"]);
	$telefone    = $campos["revenda"]["telefone"];

	if (!empty($cnpj)) {
		$sql = "SELECT revenda
				FROM tbl_revenda
				WHERE cnpj = '{$cnpj}'";
		$res = pg_query($con, $sql);

		if (strlen($cidade) > 0 && strlen($estado) > 0) {
			$sql_cidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND (UPPER(estado) = UPPER('{$estado}') or upper(estado_exterior) = upper('$estado'))";
			$res_cidade = pg_query($con, $sql_cidade);

			if (pg_num_rows($res_cidade) > 0) {
				$cidade_id = pg_fetch_result($res_cidade, 0, "cidade");
			} else {
				if ($login_fabrica == 52) {
					$sql_cidade2 = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade_pesquisa)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
					$res_cidade2 = pg_query($con, $sql_cidade2);
					if (pg_num_rows($res_cidade2) > 0) {
						$cod_ibge2 = pg_fetch_result($res_cidade2, 0, "cod_ibge");
						$sql_cidade3 = "SELECT cidade FROM tbl_cidade WHERE cod_ibge = $cod_ibge2 AND UPPER(estado) = UPPER('{$estado}')";
						$res_cidade3 = pg_query($con, $sql_cidade3);
						if (pg_num_rows($res_cidade3) > 0) {
							$cidade_id = pg_fetch_result($res_cidade3, 0, "cidade");
						}else{
							$cidade_id = "null";
						}
					}
				}else{
					$cidade_id = "null";
			}
		}
	}
		if (empty($cidade_id)) {
			$cidade_id = "null";
		}

		if (pg_num_rows($res) > 0) {
			$revenda = pg_fetch_result($res, 0, "revenda");

			$sql = "UPDATE tbl_revenda SET
						nome = '{$nome}',
						cep  = '{$cep}',
						cidade  = {$cidade_id},
						bairro  = '{$bairro}',
						endereco  = '{$endereco}',
						numero  = '{$numero}',
						complemento  = '{$complemento}',
						fone  = '{$telefone}'
					WHERE revenda = {$revenda};";
			$res = pg_query($con, $sql);
		} else {
			$sql = "INSERT INTO tbl_revenda
					(nome, cnpj, cep, cidade, bairro, endereco, numero, complemento, fone)
					VALUES
					('{$nome}', '{$cnpj}', '{$cep}', {$cidade_id}, '{$bairro}', '{$endereco}', '{$numero}', '{$complemento}', '{$telefone}')
					RETURNING revenda;";
			$res = pg_query($con, $sql);
			$revenda = pg_fetch_result($res, 0, "revenda");
		}

		if($login_fabrica == 52 and $tipo =='cliente_admin') {
			$sql = "SELECT cliente_admin
				FROM tbl_cliente_admin
				WHERE cnpj = '{$cnpj}'";
			$res = pg_query($con, $sql);


			if (pg_num_rows($res) > 0) {
				$revenda = pg_fetch_result($res, 0, "cliente_admin");

				$sql = "UPDATE tbl_cliente_admin SET
					nome = '{$nome}',
					cep  = '{$cep}',
					cidade  = '{$cidade}',
					estado = '$estado',
					bairro  = '{$bairro}',
					endereco  = '{$endereco}',
					numero  = '{$numero}',
					complemento  = '{$complemento}',
					fone  = '{$telefone}'
					WHERE cliente_admin = {$revenda};";
				$res = pg_query($con, $sql);

			} else {
				$sql = "INSERT INTO tbl_cliente_admin
					(nome, cnpj, cep, cidade,estado,  bairro, endereco, numero, complemento, fone)
					VALUES
					('{$nome}', '{$cnpj}', '{$cep}', '{$cidade}','$estado', '{$bairro}', '{$endereco}', '{$numero}', '{$complemento}', '{$telefone}')
					RETURNING cliente_admin;";
				$res = pg_query($con, $sql);
				$revenda = pg_fetch_result($res, 0, "cliente_admin");
			}
		}
	}

	return (empty($revenda)) ? "null" : $revenda;
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
 * Função para validação de data de compra
 */
function valida_data_compra() {
	global $campos;

	$data_compra   = $campos["os"]["data_compra"];
	$data_abertura = $campos["os"]["data_abertura"];

	if (!empty($data_compra)) {
		list($dia, $mes, $ano) = explode("/", $data_compra);
		list($dia_a, $mes_a, $ano_a) = explode("/", $data_abertura);

		if (!checkdate($mes, $dia, $ano)) {
			throw new Exception("Data de compra inválida");
		} else if (!empty($data_abertura) && strtotime("{$ano}-{$mes}-{$dia}") > strtotime("{$ano_a}-{$mes_a}-{$dia_a}")) {
			throw new Exception("Data de compra não pode ser posterior a data de abertura");
		}
	}
}

/**
 * Função para validar o CPF do Consumidor
 */
function valida_consumidor_cpf() {
	global $con, $campos, $cook_idioma;

	if ($cook_idioma == 'pt-br' && !in_array($login_fabrica, [180,181,182])) {

		$cpf = preg_replace("/\D/", "", $campos["consumidor"]["cpf"]);

		if (strlen($cpf) > 0) {
			$sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("CPF/CNPJ $cpf é inválido");
			}
		}

	}
}

/**
 * Função para validar o CNPJ da Revenda
 */
function valida_revenda_cnpj() {
	global $con, $campos;

	$cnpj = preg_replace("/\D/", "", $campos["revenda"]["cnpj"]);

	if (!empty($cnpj)) {
		if(strlen($cnpj) < 14){
			throw new Exception("CNPJ da Revenda é inválido");
		}

		if (strlen($cnpj) > 0) {
			$sql = "SELECT fn_valida_cnpj_cpf('{$cnpj}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("CNPJ da Revenda é inválido");
			}
		}
	}
}

/**
 * Função que valida o deslocamento caso o tipo de atendimento seja de deslocamento
 */
function valida_deslocamento() {
	global $con, $campos, $login_fabrica, $os_revenda;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (strlen($tipo_atendimento) > 0 && $os_revenda != true) {
		$sql = "SELECT tipo_atendimento
				FROM tbl_tipo_atendimento
				WHERE fabrica = {$login_fabrica}
				AND tipo_atendimento = {$tipo_atendimento}
				AND km_google IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$qtde_km = $campos["os"]["qtde_km"];

			if (!strlen($qtde_km)) {
				throw new Exception("Para este tipo de atendimento favor clicar no botão Calcular KM");
			}
		}
	}
}

if (isset($fabrica_usa_subproduto)) {
	/**
	 * Função que valida o subproduto
	 */
	function valida_subproduto() {
		global $con, $campos, $login_fabrica;

		$subproduto = $campos["subproduto"]["id"];
		$produto    = $campos["produto"]["id"];

		if (strlen($produto) > 0) {
			if (verifica_subproduto($produto) === true) {
				if (!strlen($subproduto)) {
					throw new Exception("Informe o subconjunto do produto");
				} else {
					$sql = "SELECT * FROM tbl_subproduto WHERE (produto_filho = $subproduto AND produto_pai = $produto) OR (produto_filho = $produto AND produto_pai = $subproduto)";
					$res = pg_query($con, $sql);

					if (!pg_num_rows($res)) {
						throw new Exception("O subconjunto informado é inválido");
					}
				}
			}
		}
	}

	/**
	 * Função que valida obrigatoriedade do número de série do subproduto
	 */
	function valida_subproduto_serie_obrigatoria() {
		global $campos;

		$produto = $campos["produto"]["id"];
		$serie   = $campos["subproduto"]["serie"];

		if (strlen($produto) > 0) {
			if (verifica_subproduto($produto) === true) {
				if (!strlen($serie)) {
					throw new Exception("Informe o número de série do subconjunto");
				}
			}
		}
	}

	/**
	 * Função que verifica se o produto tem subproduto
	 */
	function verifica_subproduto($produto) {
		global $con, $login_fabrica;

		if(!empty($produto)) {
			$sql = "( SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem, tbl_produto.ativo
					FROM tbl_subproduto
					INNER JOIN tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
					WHERE tbl_subproduto.produto_pai = {$produto}
					) UNION (
						SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem, tbl_produto.ativo
						FROM tbl_subproduto
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
						WHERE tbl_subproduto.produto_filho = {$produto}
					)";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}
}

/**
 * Função que valida a abertura da pre-os
 */
function valida_atendimento() {
	global $con, $campos, $login_posto, $login_fabrica, $os, $usaProdutoGenerico, $areaAdmin;

	$hd_chamado = $campos["os"]["hd_chamado"];

	if ($areaAdmin === true) {
		$posto = $campos['posto']['id'];
	} else {
		$posto = $login_posto;
	}

	if (strlen($hd_chamado) > 0 && empty($os)) {
		$sql = "
			SELECT
				tbl_hd_chamado.hd_chamado
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
			AND tbl_hd_chamado_extra.posto = {$posto}
			AND tbl_hd_chamado.hd_chamado = {$hd_chamado};
		";

		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Pre-OS não pertence ao Posto Autorizado");
		}

		if (!$usaProdutoGenerico) {
			$sql = "
				SELECT
					tbl_hd_chamado.hd_chamado
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				AND tbl_hd_chamado.hd_chamado = {$hd_chamado}
				AND tbl_hd_chamado_extra.os IS NOT NULL;
			";

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				throw new Exception("Já existe uma Ordem de Serviço aberta para o Atendimento");
			}
		}
	}
}

/**
 * Função que valida ser o número de série existe no banco de dados
 */
function verifica_serie(){
	global $con, $campos, $login_fabrica;

	$produto = $campos["produto"]["id"];
    $serie   = $campos["produto"]["serie"];
    if (!empty($produto) && !empty($serie)) {
		$sql = "SELECT serie FROM tbl_numero_serie WHERE produto = {$produto} AND serie = '{$serie}' AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			throw new Exception("Número de série {$serie} inválido");
		}
	}
}

/**
 * Função que valida ser o número de série do subproduto existe no banco de dados
 */
function verifica_serie_subproduto(){
	global $con, $campos, $login_fabrica;

	if(strlen($campos["subproduto"]["serie"]) > 0){
		$produto = $campos["subproduto"]["id"];
	    $serie   = $campos["subproduto"]["serie"];

	    $sql = "SELECT serie FROM tbl_numero_serie WHERE produto = {$produto} AND lower(serie) = lower('{$serie}') AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			throw new Exception("Número de série {$serie} inválido");
		}
	}
}

/**
 * AUDITORIAS
 */

$auditorias = array(
	"auditoria_os_reincidente",
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes"
);

#Verifica auditoria
function verifica_auditoria($status, $status_not_in, $os) {
	global $con;

	$sql = "SELECT status_os
			FROM tbl_os_status
			WHERE os = {$os}
			AND status_os IN (".implode(", ", $status).")
			ORDER BY data DESC
			LIMIT 1";
	// echo nl2br($sql)."<br>";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0 || !in_array(pg_fetch_result($res, 0, "status_os"), $status_not_in)) {
		return true;
	} else {
		$status_os = pg_fetch_result($res, 0, "status_os");

		if (!in_array($status_os, $status_not_in)) {
			return true;
		} else {
			return false;
		}
	}
}

#Verifica auditoria_unica
function verifica_auditoria_unica($condicao, $os) {
	global $con;

	$sql = "SELECT tbl_auditoria_os.auditoria_status FROM tbl_auditoria_os
			INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
			WHERE os = {$os}
			AND {$condicao}
			AND cancelada IS NULL
			ORDER BY data_input DESC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		return true;
	} else {
		return false;
	}
}

#Verifica se foi lançado peças na Ordem de Serviço
function verifica_peca_lancada($nova = true) {
	/*
		$nova define se irá procurar somente por novas peças lançadas na OS ou não
	*/
	global $campos, $fabrica_usa_subproduto;

	$peca_lancada = false;

	foreach($campos["produto_pecas"] as $key => $value) {
		if ($nova === true) {
			if (!strlen($value["os_item"]) && strlen($value["id"]) > 0) {
				$peca_lancada = true;
				break;
			}
		} else {
			if (strlen($value["id"]) > 0) {
				$peca_lancada = true;
				break;
			}
		}
	}

	if (isset($fabrica_usa_subproduto)) {
		foreach($campos["subproduto_pecas"] as $key => $value) {
			if ($nova == true) {
				if (!strlen($value["os_item"]) && strlen($value["id"]) > 0) {
	            	$peca_lancada = true;
	    	        break;
		        }
			} else {
				if (strlen($value["id"]) > 0) {
	            	$peca_lancada = true;
	    	        break;
		        }
			}
        }
	}
	return $peca_lancada;
}

#Verifica se foi lançado peças na Ordem de Serviço
function pegar_peca_lancada($nova = true) {
	global $campos, $fabrica_usa_subproduto;
	$peca_lancada = "";

	foreach($campos["produto_pecas"] as $key => $value) {
		if ($nova === true) {
			if (!strlen($value["os_item"]) && strlen($value["id"]) > 0) {
				$peca_lancada[] = $value["id"];
				break;
			}
		} else {
			if (strlen($value["id"]) > 0) {
				$peca_lancada[] = $value["id"];
				break;
			}
		}
	}

	if (isset($fabrica_usa_subproduto)) {
		foreach($campos["subproduto_pecas"] as $key => $value) {
			if ($nova == true) {
				if (!strlen($value["os_item"]) && strlen($value["id"]) > 0) {
	            	$peca_lancada[] = $value["id"];
	    	        break;
		        }
			} else {
				if (strlen($value["id"]) > 0) {
	            	$peca_lancada[] = $value["id"];
	    	        break;
		        }
			}
        }
	}

	return $peca_lancada;
}

#Variavel utilizada para redirecionar para  a tela de justificativa da OS reincidente
$os_reincidente = false;
$os_reincidente_numero = null;

if (!isset($auditoria_unica)) {
    //Funções antigas

	#Reincidente

	function auditoria_os_reincidente() {
		global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

		$posto = $campos['posto']['id'];

        $sql = "SELECT  os
                FROM    tbl_os
                WHERE   fabrica         = {$login_fabrica}
                AND     os              = {$os}
                AND     os_reincidente  IS NOT TRUE
                AND     cancelada       IS NOT TRUE
        ";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){

			$select = "SELECT tbl_os.os
					FROM tbl_os
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.os < {$os}
					AND tbl_os.posto = $posto
					AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
					AND length(tbl_os.nota_fiscal) > 0
					AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
					AND length(tbl_os.revenda_cnpj) > 0
					AND tbl_os_produto.produto = {$campos['produto']['id']}
					ORDER BY tbl_os.data_abertura DESC
					LIMIT 1";
			$resSelect = pg_query($con, $select);

			if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
				$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

				if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
					$insert = "INSERT INTO tbl_os_status
							(os, status_os, observacao)
							VALUES
							({$os}, 70, 'OS reincidente de cnpj, nota fiscal e produto')";
					$resInsert = pg_query($con, $insert);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço");
					} else {
						$os_reincidente = true;
					}
				}
			}
		}
	}

	#Peça critica
	function auditoria_peca_critica() {
		global $con, $os, $login_fabrica, $qtde_pecas;

		$sql = "SELECT tbl_os_item.os_item
				FROM tbl_os_item
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
				INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
				WHERE tbl_os_produto.os = {$os}
				AND tbl_peca.peca_critica IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && verifica_auditoria(array(62, 64), array(62), $os) === true && verifica_peca_lancada() === true) {
			$sql = "INSERT INTO tbl_os_status
					(os, status_os, observacao)
					VALUES
					({$os}, 62, 'OS em intervenção da fábrica por Peça Crí­tica')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}

	#Troca obrigatória
	function auditoria_troca_obrigatoria() {
		global $con, $os, $login_fabrica;
		$sql = "SELECT tbl_produto.produto
				FROM tbl_os_produto
				INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
				WHERE tbl_os_produto.os = {$os}
				AND tbl_produto.troca_obrigatoria IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && verifica_auditoria(array(62, 64), array(62, 64), $os) === true) {
			$sql = "INSERT INTO tbl_os_status
					(os, status_os, observacao)
					VALUES
					({$os}, 62, 'OS em intervenção da fábrica por Produto de troca obrigatória')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}

	#Peças excedentes
	function auditoria_pecas_excedentes() {
		global $con, $os, $login_fabrica;

		if(verifica_peca_lancada() === true){
			$sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			$qtde_pecas_intervencao = pg_fetch_result($res, 0, "qtde_pecas_intervencao");

			if(!strlen($qtde_pecas_intervencao)){
				$qtde_pecas_intervencao = 0;
			}

			if ($qtde_pecas_intervencao > 0) {
				$sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
						FROM tbl_os_item
						INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
						WHERE tbl_os_produto.os = {$os}";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0 && pg_fetch_result($res, 0, "qtde_pecas") > $qtde_pecas_intervencao && verifica_auditoria(array(118, 187), array(118), $os) === true) {
					$sql = "INSERT INTO tbl_os_status
							(os, status_os, observacao)
							VALUES
							({$os}, 118, 'OS em auditoria de peças excedentes')";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço");
					}
				}
			}
		}
	}
} else {


	#Reincidente
	function auditoria_os_reincidente() {
		global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

		$posto = $campos['posto']['id'];
		$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
		$res = pg_query($con, $sql);

		$condicao_auditoria_os_serie = \Posvenda\Regras::get("condicao_auditoria_produto_serie", "ordem_de_servico", $login_fabrica);
		$condicao_auditoria_os_serie = ($condicao_auditoria_os_serie) ? " AND tbl_os.serie = '".$campos['produto']['serie']."'" : '';

		if(pg_num_rows($res) > 0){

			$sql = "SELECT tbl_os.os
					FROM tbl_os
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.posto = $posto
					AND tbl_os.os < {$os}
					AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
					AND length(tbl_os.nota_fiscal) > 0
					AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
					AND length(tbl_os.revenda_cnpj) > 0
					AND tbl_os_produto.produto = {$campos['produto']['id']}
					$condicao_auditoria_os_serie
					ORDER BY tbl_os.data_abertura DESC
					LIMIT 1";
			$resSelect = pg_query($con, $sql);

			if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
				$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

				if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
					$busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

					if($busca['resultado']){
						$auditoria_status = $busca['auditoria'];
					}

		            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
		                    ({$os}, $auditoria_status, 'OS Reincidente por CNPJ, NOTA FISCAL, PRODUTO')";

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço");
					} else {
						$os_reincidente = true;
					}
				}
			}
		}
	}

	#Peça critica
	function auditoria_peca_critica() {
		global $con, $os, $login_fabrica, $qtde_pecas;

		if ($login_fabrica == 160) {
			$sql_pedido = " SELECT tbl_os_item.peca, tbl_os_item.pedido
							FROM tbl_os_produto
							JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							WHERE tbl_os_produto.os = $os
							AND tbl_os_item.pedido IS NULL
							AND tbl_os_item.fabrica_i = $login_fabrica";
			$res_pedido = pg_query($con, $sql_pedido);
			if (pg_num_rows($res_pedido) == 0){
				return;
			}
		}

		if(verifica_peca_lancada(false) === true){
			$sql = "SELECT tbl_os_item.os_item
					FROM tbl_os_item
					INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
					INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
					INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
					WHERE tbl_os_produto.os = {$os}
					AND tbl_peca.peca_critica IS TRUE";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

				if($busca['resultado']){
					$auditoria_status = $busca['auditoria'];
				}

				if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'", $os) === true) {
	                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
	                    ({$os}, $auditoria_status, 'OS em intervenção da fábrica por Peça Crí­tica')";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço");
					}
				}else if(aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'") && verifica_peca_lancada() === true){
					$nova_peca = pegar_peca_lancada();

					if(count($nova_peca) > 0){
						$sql = "SELECT tbl_os_item.os_item
							FROM tbl_os_item
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
							INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
								AND tbl_servico_realizado.gera_pedido IS TRUE
								AND troca_de_peca IS TRUE
							WHERE tbl_os_produto.os = {$os}
							AND tbl_peca.peca_critica IS TRUE
							AND tbl_peca.peca IN (".implode(", ", $nova_peca).")";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){
			                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
			                    ({$os}, $auditoria_status, 'OS em intervenção da fábrica por Peça Crí­tica')";
							$res = pg_query($con, $sql);

							if (strlen(pg_last_error()) > 0) {
								throw new Exception("Erro ao lançar ordem de serviço");
							}
						}
					}
				}
			}
		}
	}


	#Troca obrigatória
	function auditoria_troca_obrigatoria() {
		global $con, $os, $login_fabrica;

		$sql = "SELECT tbl_produto.produto
				FROM tbl_os_produto
				INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
				WHERE tbl_os_produto.os = {$os}
				AND tbl_produto.troca_obrigatoria IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && verifica_auditoria_unica(" tbl_auditoria_status.produto = 't' AND tbl_auditoria_os.observacao ILIKE '%troca obrigatória%'", $os) === true) {
			$busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

			if($busca['resultado']){
				$auditoria_status = $busca['auditoria'];
			}

            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                ({$os}, $auditoria_status, 'OS em intervenção da fábrica por Produto de troca obrigatória')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}

	#Peças excedentes
	function auditoria_pecas_excedentes() {
		global $con, $os, $login_fabrica, $qtde_pecas;
		
		if(verifica_peca_lancada() === true){

			$sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);

			$qtde_pecas_intervencao = pg_fetch_result($res, 0, "qtde_pecas_intervencao");
			
			if(!strlen($qtde_pecas_intervencao)){
				$qtde_pecas_intervencao = 0;
			}

			if ($qtde_pecas_intervencao > 0) {
				$sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
						FROM tbl_os_item
						INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
						WHERE tbl_os_produto.os = {$os}";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					$qtde_pecas = pg_fetch_result($res, 0, "qtde_pecas");
				}else{
					$qtde_pecas = 0;
				}

				if($qtde_pecas > $qtde_pecas_intervencao){
					$busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

					if($busca['resultado']){
						$auditoria_status = $busca['auditoria'];
					}

					if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'")) {
			            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
			                ({$os}, $auditoria_status, 'OS em auditoria de peças excedentes')";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao lançar ordem de serviço");
						}
					}
				}
			}
		}
	}
}

/**
 * Verifica se a OS deu reincidencia com uma OS finalizada se não estiver bloqueia a abertura da OS
 */

function verifica_os_reincidente_finalizada($os) {
    global $con, $login_fabrica, $campos;
    $posto = $campos['posto']['id'];
/**
 * hd-3467175
 *  - A pedido do Lin, pediu para na regra geral
 * pegar apenas as OS finalizadas e não canceladas,
 * para nas regras individuais serem tratados.
 */

    $sql = "
        SELECT os
        FROM tbl_os
        WHERE fabrica = {$login_fabrica}
        AND os = {$os}
        AND finalizada IS NOT NULL
        AND data_fechamento IS NOT NULL
    ";
    $res = pg_query($con, $sql);
	/*
		verifica os duplicada
	*/
    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        $sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND posto = {$posto} AND excluida IS NOT TRUE AND consumidor_revenda = 'C'";
        $res = pg_query($con, $sql);


        if (pg_num_rows($res) > 0 AND !in_array($login_fabrica,array(139,146,148,131,183,186,194))){
			$sua_os = pg_fetch_result($res, 0, "sua_os");
			throw new Exception("Já existe uma Ordem de Serviço aberta com os dados informados, os: {$sua_os}");
        } else {
			return true;
		}
    }
}

#Descrição do Defeito Constatado
function descricao_defeito($defeito){

	global $con, $login_fabrica;

	$sql = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = {$defeito} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		return pg_fetch_result($res, 0, 'descricao');
	}else{
		return " Não encontrado";
	}

}

function info_defeito($defeito) {
	global $con, $login_fabrica;

	$sql = "SELECT descricao, lancar_peca, lista_garantia, codigo FROM tbl_defeito_constatado WHERE defeito_constatado = {$defeito} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		return array(
			"codigo" => pg_fetch_result($res, 0, 'codigo'),
			"descricao" => pg_fetch_result($res, 0, 'descricao'),
			"lancar_peca" => pg_fetch_result($res, 0, 'lancar_peca'),
			"lista_garantia" => pg_fetch_result($res, 0, 'lista_garantia')
		);
	}else{
		return " Não encontrado";
	}
}

#Descrição da Solução
function descricao_solucao($solucao){

	global $con, $login_fabrica;

	$sql = "SELECT descricao FROM tbl_solucao WHERE solucao = {$solucao} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		return pg_fetch_result($res, 0, 'descricao');
	}else{
		return " Não encontrado";
	}

}

#Descrição da Solução
function descricao_solucao_mo($solucao,$produto){

	global $con, $login_fabrica;

	$sql = "SELECT tbl_solucao.descricao, tbl_diagnostico.mao_de_obra
				FROM tbl_diagnostico
				INNER JOIN tbl_produto ON tbl_diagnostico.familia = tbl_produto.familia AND tbl_produto.fabrica_i = {$login_fabrica}
				INNER JOIN tbl_solucao ON tbl_diagnostico.solucao = tbl_solucao.solucao
				WHERE tbl_diagnostico.solucao = {$solucao}
				AND tbl_produto.produto = {$produto}
				AND tbl_diagnostico.fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$descricao = pg_fetch_result($res, 0, 'descricao');
		$mao_de_obra = pg_fetch_result($res, 0, 'mao_de_obra');

		return $descricao . " - Mão de Obra: ".number_format($mao_de_obra,2,',','.');
	}else{
		return " Não encontrado";
	}

}

#Descrição do tipo de atendimento
function descricao_tipo_atendimento($tipo_atendimento) {
	global $con, $login_fabrica;

	$sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
	$res = pg_query($con, $sql);

	return pg_fetch_result($res, 0, "descricao");
}

function valida_defeito_constatado_peca_lancada() {
	global $campos, $defeitoConstatadoMultiplo, $login_fabrica, $areaAdmin;

	if (isset($defeitoConstatadoMultiplo)) {
		$defeito_constatado = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
	}else if ($login_fabrica == 177 AND $areaAdmin === false){
		$defeito_constatado = $campos["produto"]["causa_defeito"];
	} else {
		$defeito_constatado = $campos["produto"]["defeito_constatado"];
	}

	if (verifica_peca_lancada() == true && empty($defeito_constatado)) {
		throw new Exception("Para lançar peças é necessário informar o defeito constatado");
	}
	
}

function grava_defeito_constatado_multiplo() {
	global $con, $os, $campos, $login_fabrica;

	if(!empty($campos["produto"]["defeitos_constatados_multiplos"])){

		$defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

		for($i = 0; $i < count($defeitos); $i++){
			$def = $defeitos[$i];

			$sql_def = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_constatado = {$def}";
			$res_def = pg_query($con, $sql_def);

			if (!pg_num_rows($res_def)) {
				$sql_def = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_constatado, fabrica) VALUES ({$os}, {$def}, $login_fabrica)";
				$res_def = pg_query($con, $sql_def);
			}
		}

	}
}

function get_os_item($os, $peca) {

	global $login_fabrica, $con;

	$sql = "SELECT
			tbl_os_item.os_item
		FROM tbl_os_item
		JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
		WHERE tbl_os_produto.os = {$os}
		AND tbl_os.fabrica = {$login_fabrica}
		AND tbl_os_item.peca = {$peca}";
	$res = pg_query($con, $sql);

	return pg_fetch_result($res, 0, "os_item");

}

function get_liberacao_pedido_os_item($os_item,$os) {

	global $login_fabrica, $con;

	$sql = "SELECT
			tbl_os_item.liberacao_pedido
		FROM tbl_os_item
		JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
		WHERE tbl_os_produto.os = {$os}
		AND tbl_os.fabrica = {$login_fabrica}
		AND tbl_os_item.os_item = {$os_item}";
	$res = pg_query($con, $sql);

	return pg_fetch_result($res, 0, "liberacao_pedido");

}

function get_qtde_os_item($os, $peca, $posto) {

	global $login_fabrica, $con;
	
	$sql = "SELECT
				tbl_estoque_posto_movimento.qtde_saida
			FROM tbl_estoque_posto_movimento
			WHERE tbl_estoque_posto_movimento.os = {$os}
			AND tbl_estoque_posto_movimento.fabrica = {$login_fabrica}
			AND tbl_estoque_posto_movimento.posto = {$posto}
			AND tbl_estoque_posto_movimento.peca = {$peca}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		return 0;
	} else {
		return pg_fetch_result($res, 0, "qtde_saida");
	}

}

function verifica_estoque_peca(){

    global $login_fabrica, $campos, $os, $gravando , $con;
    
    $posto = ($areaAdmin === false) ? $login_posto : $campos["posto"]["id"];

    $Os = new \Posvenda\Os($login_fabrica);

    $status_posto_controla_estoque = $Os->postoControlaEstoque($posto);

    if($status_posto_controla_estoque == true){

        $pecas_pedido = $campos["produto_pecas"];
        $nota_fiscal  = $campos["os"]["nota_fiscal"];
        $data_nf      = $campos["os"]["data_compra"];

        if(!empty($data_nf)){
            list($dia, $mes, $ano) = explode("/", $data_nf);
            $data_nf = $ano."-".$mes."-".$dia;
        }

        foreach ($pecas_pedido as $pecas) {

            if(!empty($pecas["id"])){
                $servico         = $pecas["servico_realizado"];
                $peca            = $pecas["id"];
                $peca_referencia = $pecas["referencia"];
                $qtde            = $pecas["qtde"];

                $os_item         = get_os_item($os, $peca);

                $status_servico = $Os->verificaServicoUsaEstoque($servico);

                if($status_servico == true){
                	$sqlEstoque = "
                		SELECT qtde_saida FROM tbl_estoque_posto_movimento WHERE os_item = {$os_item}
                	";
                	$resEstoque = pg_query($con, $sqlEstoque);

                	if (pg_num_rows($resEstoque) > 0) {
	                	$qtde_saida = pg_fetch_result($resEstoque, 0, "qtde_saida");

	                	$diferenca = $qtde - $qtde_saida;

	                	if ($diferenca != 0) {
	                		$$Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);

		                    $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

		                    if($status_estoque == false){
		                        throw new Exception("O posto não tem estoque suficiente para a Peça {$peca_referencia}");
		                    }else{
		                        $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
		                    }
	                	}
	                } else {
	                	$status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

	                    if(!$status_estoque){
	                        throw new Exception("O posto não tem estoque suficiente para a Peça {$peca_referencia}");
	                    }else{
	                        $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
	                    }
	                }
                } else {
                    $status_exclusao = $Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);
                }

            }

        }

    }

}

function grava_multiplos_defeitos() {
	global $con, $os, $campos, $login_fabrica;

	if(!empty($campos["produto"]["defeitos_constatados_multiplos"])){

		$defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

		for($i = 0; $i < count($defeitos); $i++){
			$def = $defeitos[$i];

			$sql_def = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_constatado = {$def}";
			$res_def = pg_query($con, $sql_def);

			if (!pg_num_rows($res_def)) {
				$sql_def = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_constatado, fabrica) VALUES ({$os}, {$def}, {$login_fabrica})";
				$res_def = pg_query($con, $sql_def);
			}
		}

	}
}

$grava_multiplos_defeitos = "grava_multiplos_defeitos";

/**
 * função que verifica o tipo do posto
 * @param  string $tipo  deve ser o nome da coluna da tbl_tipo_posto
 * @param  string $valor passar TRUE OU FALSE
 * @return boolean
 */
function verifica_tipo_posto($tipo, $valor, $posto_id = null) {
	global $con, $msg_erro, $login_fabrica, $campos, $tipo_posto_multiplo;

	if (is_null($posto_id)) {
		$posto_id = $campos["posto"]["id"];
	}

	if (!strlen($posto_id)) {
		$msg_erro['msg']['erro_tipo_posto'] = traduz("Erro ao verificar tipo do posto");
	}

	if (isset($tipo_posto_multiplo)) {
		$sql = "
			SELECT tbl_tipo_posto.tipo_posto
			FROM tbl_posto_tipo_posto
			INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto
			WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
			AND tbl_posto_tipo_posto.posto = {$posto_id}
			AND tbl_tipo_posto.{$tipo} IS {$valor}
		";
	} else {
		$sql = "
			SELECT tbl_tipo_posto.tipo_posto
			FROM tbl_posto_fabrica
			INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_posto_fabrica.posto = {$posto_id}
			AND tbl_tipo_posto.{$tipo} IS {$valor}
		";
	}

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return true;
	} else {
		return false;
	}
}

function redirecionamento_os() {
	global $os_reincidente, $os_reincidente_numero, $grava_os_reincidente, $login_fabrica, $grava_os_reincidente, $nova_os_id, $os;

	if ($os_reincidente === true && $os_reincidente_numero != null AND strlen($grava_os_reincidente) > 0) {
	    $grava_os_reincidente($os_reincidente_numero);
	} else {
		$abre_nova_os = \Posvenda\Regras::get("abre_nova_os", "ordem_de_servico", $login_fabrica);

		if ($abre_nova_os == true) {
			if(!empty($nova_os_id)){
				header("Location: os_press.php?os={$nova_os_id}");
			}else{
				header("Location: os_press.php?os={$os}");
			}
		}else{
		 	header("Location: os_press.php?os={$os}");
		}
	}
}

$redirecionamento_os = "redirecionamento_os";

/**
 * Função que grava a OS reincidente e direciona para a tela de OBSERVAÇÂO
 */

function grava_os_reincidente($os_reincidente_numero) {
	global $con, $login_fabrica, $os, $areaAdmin;

	$sql = "UPDATE tbl_os SET os_reincidente = TRUE WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$res = pg_query($con, $sql);

	$sql = "UPDATE tbl_os_extra SET os_reincidente = {$os_reincidente_numero} WHERE os = {$os}";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao lançar ordem de serviço reincidente");
    }

    if ($areaAdmin === false) {
    	header("Location: os_motivo_atraso.php?os={$os}&justificativa=ok");
 	} else {
 		header("Location: os_press.php?os={$os}");
 	}
}

$grava_os_reincidente = "grava_os_reincidente";

function grava_os_campo_extra() {

    global $con, $login_fabrica, $login_admin, $os, $campos, $areaAdmin, $oTcMaps;

    if (strlen($os) > 0) {
        $sqlFabrica = "SELECT JSON_FIELD('usaMobile', parametros_adicionais) AS usaMobile
                         FROM tbl_fabrica
                        WHERE fabrica = {$login_fabrica}";
        $resFabrica = pg_query($con, $sqlFabrica);
        $fabricaUsaMobile = pg_fetch_result($resFabrica, 0, 'usaMobile');

        $sqlPostoFabrica  = "SELECT JSON_FIELD('usaMobile', parametros_adicionais) AS usaMobile
                               FROM tbl_posto_fabrica
                              WHERE fabrica = {$login_fabrica}
                                AND posto = ".$campos["posto"]["id"];
        $resPostoFabrica  = pg_query($con, $sqlPostoFabrica);
        $postoUsaMobile   = pg_fetch_result($resPostoFabrica, 0, 'usaMobile');

        if ($fabricaUsaMobile && $postoUsaMobile) {

            $sqlTipoAtend = "SELECT tipo_atendimento
                               FROM tbl_tipo_atendimento
                              WHERE fabrica = {$login_fabrica}
                                AND tipo_atendimento = ".$campos["os"]["tipo_atendimento"]."
                                AND km_google IS TRUE";
            $resTipoAtend = pg_query($con, $sqlTipoAtend);
            if (pg_num_rows($resTipoAtend) > 0) {

                $sqlXoce = "SELECT campos_adicionais
                              FROM tbl_os_campo_extra
                             WHERE fabrica = {$login_fabrica}
                               AND os = {$os}";
                $resXoce = pg_query($con, $sqlXoce);

                $xendereco   = $campos["consumidor"]["endereco"];
                $xnumero     = $campos["consumidor"]["numero"];
                $xbairro     = $campos["consumidor"]["bairro"];
                $xcidade     = $campos["consumidor"]["cidade"];
                $xestado     = $campos["consumidor"]["estado"];
                $xpais       = "Brasil";
                $xcep        = $campos["consumidor"]["cep"];

                $response    = $oTcMaps->geocode($xendereco, $xnumero, $xbairro, $xcidade, $xestado, $xpais, $xcep);

                $jaTemOsCampoExtra = false;

                if (pg_num_rows($resXoce) > 0) {
                    $campoAdcXoce = json_decode(pg_fetch_result($resXoce, 0, 'campos_adicionais'), 1);
                    $jaTemOsCampoExtra = true;
                }

                $campoAdcXoce["cliente_latitude"]  = $response["latitude"];
                $campoAdcXoce["cliente_longitude"] = $response["longitude"];

                $novoCampoAdcXoce = json_encode($campoAdcXoce);

                if ($jaTemOsCampoExtra) {

                    $sqlOce = "UPDATE tbl_os_campo_extra
                                  SET campos_adicionais = '$novoCampoAdcXoce'
                                WHERE fabrica = {$login_fabrica}
                                  AND os = {$os}";

                } else {

                    $sqlOce = "INSERT INTO tbl_os_campo_extra
                                            (
                                                campos_adicionais,
                                                fabrica,
                                                os
                                            ) VALUES (
                                                '$novoCampoAdcXoce',
                                                {$login_fabrica},
                                                {$os}
                                            )";

                }

                $resOce = pg_query($con, $sqlOce);

                if (strlen(pg_last_error($con)) > 0) {
                    throw new Exception("Erro ao gravar os campo extra");
                }
            }
        }
    }
}
$grava_os_campo_extra = "grava_os_campo_extra";

function envia_sms() {
    global $con, $login_fabrica, $os, $sms, $campos, $login_posto;

    $celular_consumidor = $campos["consumidor"]['celular'];
    $posto = (empty($login_posto)) ? $campos['posto']['id'] : $login_posto;

    if (!empty($celular_consumidor)) {
    	$nome_consumidor = $campos["consumidor"]['nome'];
    	$data_abertura   = $campos["os"]["data_abertura"];


    	$sqlDados = "SELECT tbl_posto.nome as nome_posto,
    						tbl_fabrica.nome as nome_fabrica,
    						tbl_fabrica.site,
    						tbl_posto_fabrica.contato_cel,
    						tbl_produto.descricao AS produto,
    						tbl_posto_fabrica.contato_fone_comercial,
    						tbl_posto_fabrica.contato_fax
    				 FROM tbl_posto_fabrica
    				 JOIN tbl_posto USING(posto)
    				 JOIN tbl_fabrica USING(fabrica)
    				 JOIN tbl_os_produto ON tbl_os_produto.os = $os
    				 JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
    				 AND tbl_produto.fabrica_i = $login_fabrica
    				 WHERE tbl_posto_fabrica.posto = $posto
    				 AND   tbl_posto_fabrica.fabrica = $login_fabrica";
    	$resDados = pg_query($con, $sqlDados);

    	$nome_posto    = pg_fetch_result($resDados, 0, 'nome_posto');
    	$nome_fabrica  = pg_fetch_result($resDados, 0, 'nome_fabrica');
    	$site_fabrica  = pg_fetch_result($resDados, 0, 'site');
    	$produto       = pg_fetch_result($resDados, 0, 'produto');
    	$fone_comercial= pg_fetch_result($resDados, 0, 'contato_fone_comercial');
    	$fone_fax      = pg_fetch_result($resDados, 0, 'contato_fax');
    	$contato_posto = (empty($fone_comercial)) ? substr($fone_fax,0,14) : substr($fone_comercial,0,14);

    	$primeira_palavra = explode(" ",$produto);

    	$msg_sms = "OS {$os} ABERTA em {$data_abertura} para o produto ".$primeira_palavra[0]." na AUTORIZADA {$nome_posto} {$contato_posto}, acompanhe também pelo site {$site_fabrica}";

    	if (strlen($msg_sms) >= 160) {

    		$msg_sms = "OS {$os} ABERTA em {$data_abertura} para o produto ".substr($primeira_palavra[0],0,14)." na AUTORIZADA ".substr($nome_posto,0,14)." {$contato_posto}, acompanhe também pelo site {$site_fabrica}";

    	}

    	$enviar  = $sms->enviarMensagem($celular_consumidor,$os,' ',$msg_sms);

		if($enviar == false){
			$sms->gravarSMSPendente($os);
		}
    }
}

function valida_qtde_lista_basica(){

	global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

	if($areaAdmin == false){

		$sql = "select tbl_os_produto.produto, tbl_peca.referencia, tbl_peca.descricao,
			    os_produto, e.peca,  e.admin , sum(qtde) as qtde
			from tbl_os_produto
			join tbl_os_item e using(os_produto)
			join tbl_peca on tbl_peca.peca = e.peca
			join tbl_servico_realizado on e.servico_realizado = tbl_servico_realizado.servico_realizado
			where os = $os and e.admin is null and tbl_servico_realizado.descricao <> 'Cancelado'
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
$valida_qtde_lista_basica = "valida_qtde_lista_basica";

function os_tem_peca() {
	global $os, $login_fabrica, $con;

	$sql = "SELECT tbl_os_item.os_item
			FROM tbl_os
			JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			WHERE tbl_os.os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return true;
	} else {
		return false;
	}

}

function get_reclamado_id($codigo) {
    global $con, $login_fabrica;

    $sql = "SELECT defeito_reclamado
            FROM tbl_defeito_reclamado
            WHERE codigo = '{$codigo}'
            AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    return pg_fetch_result($res, 0, 'defeito_reclamado');

}

function get_constatado_id($codigo) {
    global $con, $login_fabrica;

    $sql = "SELECT defeito_constatado
            FROM tbl_defeito_constatado
            WHERE codigo = '{$codigo}'
            AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    return pg_fetch_result($res, 0, 'defeito_constatado');

}

?>
