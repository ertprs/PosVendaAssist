<?php

$regras["os|solucao"] = array(
	"obrigatorio"  => false,
	"function"     => array("valida_multiplas_solucoes", "verifica_solucao_peca_fujitsu")
);
$regras["produto|serie"]["function"][]          = "verifica_serie";
$regras["os|garantia"]["function"][]            = "verifica_visita_tecnica_garantia";
$regras["subproduto|serie"]["function"][]       = "verifica_serie_subproduto";
$regras["consumidor|contato"]["function"][] 	= "verifica_forma_contato";

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

function verifica_forma_contato() {
	global $campos, $msg_erro;

	if ((empty($campos['consumidor']['telefone']) OR empty($campos['consumidor']['email'])) AND (empty($campos['consumidor']['celular']) OR empty($campos['consumidor']['email']))) {
		$msg_erro["msg"][] = "Preencha todos os campos obrigatórios";

		$msg_erro["campos"][] = "consumidor[celular]";
		$msg_erro["campos"][] = "consumidor[email]";
		$msg_erro["campos"][] = "consumidor[telefone]";
	}

	if (!empty($campos['consumidor']['email'])) {
	    if(!filter_var($_POST['consumidor']['email'],FILTER_VALIDATE_EMAIL)){
	        $msg_erro['campos'][] = 'consumidor[email]';
	        $msg_erro['msg'][] = traduz('Preencha todos os campos obrigatórios');
	    }
	}
}

/**
 * Função para validar se o tipo de atendimento é visita técnica e foi selecionado se a os é de garantia ou não
 */
function verifica_visita_tecnica_garantia() {
	global $con, $login_fabrica, $campos, $msg_erro, $grava_os_item;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];
	$garantia         = $campos["os"]["garantia"];

	if (!empty($tipo_atendimento)) {
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND entrega_tecnica IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && empty($garantia)) {
			$msg_erro["msg"][]    = "Selecione se a Ordem de Serviço atende os requisitos de garantia ou não";
			$msg_erro["campos"][] = "os[garantia]";
		} else if (pg_num_rows($res) && $garantia == "f") {
			$grava_os_item = false;
		}
	}
}

/**
 * Função para validar a garantia do produto
 */
function valida_garantia_fujitsu(){
	global $con, $login_fabrica, $campos, $msg_erro;

	$data_compra   = $campos["os"]["data_compra"];
	$data_abertura = $campos["os"]["data_abertura"];
	$produto       = $campos["produto"]["id"];
	$serie         = $campos["produto"]["serie"];
	$compressor    = $campos["produto"]["troca_compressor"];

	if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
		$sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$garantia = pg_fetch_result($res, 0, "garantia");
		}

		$sql = "SELECT serie_inicial 
				FROM tbl_produto_serie 
				WHERE fabrica = {$login_fabrica} 
				AND produto = {$produto}
				AND serie_inicial > '{$serie}'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$serie_antiga = "sim";
			$garantia = ($compressor == "t") ? 60 : 24;
			
		}
		
		if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
			$msg_erro["msg"][] = "Produto fora de garantia";
		}
		
	}
}

$valida_garantia = "valida_garantia_fujitsu";

/**
 * Função que valida se alguma solução foi selecionada
 */
function valida_multiplas_solucoes() {
	global $campos;

	$solucoes = $campos["produto"]["solucoes_multiplos"];

	if (!strlen($solucoes)) {
		throw new Exception("Preencha todos os campos obrigatórios");
	}

}

function grava_os_extra_fabrica() {
	global $campos, $con, $login_fabrica;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];
	$garantia         = $campos["os"]["garantia"];

	if (!empty($tipo_atendimento) && !empty($garantia)) {
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND entrega_tecnica IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return array(
				"garantia" => "'{$campos["os"]["garantia"]}'"
			);
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function auditoria_km() {
	global $con, $login_fabrica, $os, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND km_google IS TRUE AND tipo_atendimento = {$tipo_atendimento}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE fabrica = {$login_fabrica}
				AND posto = {$campos['posto']['id']}
				AND (
					UPPER(TO_ASCII(contato_cidade, 'LATIN9')) <> UPPER(TO_ASCII('{$campos['consumidor']['cidade']}', 'LATIN9'))
					OR 
					(
						UPPER(TO_ASCII(contato_cidade, 'LATIN9')) = UPPER(TO_ASCII('{$campos['consumidor']['cidade']}', 'LATIN9'))
						AND UPPER(contato_estado) <> UPPER('{$campos['consumidor']['estado']}')
					)
				)";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && verifica_auditoria(array(98, 99, 100), array(98), $os) === true) {
			if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 98, 'KM alterado manualmente')";
			} else {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 98, 'OS aguardando aprovação de KM')";	
			}
			
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		} else {
			$sql = "UPDATE tbl_os SET qtde_km = 0 WHERE fabrica = {$login_fabrica} AND os = {$os}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}


function verifica_os_reincidente_finalizada_fujitsu($os) {
    global $con, $login_fabrica, $campos;
    
    $posto = $campos['posto']['id'];
    $produto = $campos['produto']['id'];
    $subproduto = $campos['subproduto']['id'];

    $sqlPegaMultiplasOs = "SELECT parametros_adicionais FROM tbl_produto WHERE produto = $produto and fabrica_i = $login_fabrica";
    $resPegaMultiplasOs = pg_query($con, $sqlPegaMultiplasOs);

    if(pg_num_rows($resPegaMultiplasOs) > 0){
    	$parametros_adicionais = pg_fetch_result($resPegaMultiplasOs, 0, parametros_adicionais);
    	$parametros_adicionais = json_decode($parametros_adicionais, true);

    	$multiplas_os_produto = $parametros_adicionais['multiplas_os'];
    }

    if(strlen(trim($subproduto))>0){
    	$sqlPegaMultiplasOs = "SELECT parametros_adicionais FROM tbl_produto WHERE produto = $subproduto and fabrica_i = $login_fabrica";
	    $resPegaMultiplasOs = pg_query($con, $sqlPegaMultiplasOs);

	    if(pg_num_rows($resPegaMultiplasOs) > 0){
	    	$parametros_adicionais = pg_fetch_result($resPegaMultiplasOs, 0, parametros_adicionais);
	    	$parametros_adicionais = json_decode($parametros_adicionais, true);

	    	$multiplas_os_subproduto = $parametros_adicionais['multiplas_os'];
	    }
    }

    if($multiplas_os_subproduto == "TRUE" OR $multiplas_os_produto == "TRUE"){
    	$multiplas_os = "TRUE";
    }else{
    	$multiplas_os = "FALSE";
    }

    if($multiplas_os != "TRUE"){

	    $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND finalizada IS NOT NULL AND data_fechamento IS NOT NULL";
	    $res = pg_query($con, $sql);
	    /*
			verifica os duplicada
		*/
	    if (pg_num_rows($res) > 0) {
	        return true;
	    } else {
	        $sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND posto = {$posto} ";
	        $res = pg_query($con, $sql);

	        if (strlen(pg_num_rows($res))>0){
				$sua_os = pg_fetch_result($res, 0, "sua_os");
				throw new Exception("Já existe uma Ordem de Serviço aberta com os dados informados, os: {$sua_os}");
	        } else {
				return true;
			}
	    }
	}
}



function auditoria_os_reincidente_fujitsu(){
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$serie = $campos["produto"]["serie"];
	$posto = $campos['posto']['id'];
	$produto = $campos['produto']['id'];
	$subproduto = $campos["subproduto"]["id"];

	$cond  			= " AND UPPER(tbl_os_produto.serie) = UPPER('{$serie}') ";
	$cond_produto 	= " AND (tbl_os_produto.produto = $produto ";

	if(strlen($campos["subproduto"]["id"]) > 0){
		$serie_subproduto = $campos["subproduto"]["serie"];
		$cond  = " AND (UPPER(tbl_os_produto.serie) = UPPER('{$serie}') OR UPPER(tbl_os_produto.serie) = UPPER('{$serie_subproduto}') ) ";
		
		$cond_produto .= " OR tbl_os_produto.produto = $subproduto ";
	}
	

	$cond_produto .= " ) ";
	$select = "SELECT tbl_os.os
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura >= (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.os < {$os}
				AND tbl_os.posto = $posto
				$cond
				$cond_produto
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
	$resSelect_fujitsu = pg_query($con, $select);
	

	$os_reincidente_numero = pg_fetch_result($resSelect_fujitsu, 0, "os");
	

	if (pg_num_rows($resSelect_fujitsu) > 0 && verifica_auditoria(array(67, 19), array(67), $os) === true) {
		$os_reincidente_numero = pg_fetch_result($resSelect_fujitsu, 0, "os");


		

		if (verifica_os_reincidente_finalizada_fujitsu($os_reincidente_numero)) {
			$insert = "INSERT INTO tbl_os_status
					(os, status_os, observacao)
					VALUES
					({$os}, 67, 'OS reincidente de número de série')";
			$resInsert = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			} else {
				$os_reincidente = true;
			}
		}
	}
}

/**
 *Função para verificar se existem soluções de Troca de Compressor ou Serpentina
 *Se existir irá procurar se tem alguma peça lançada do Tipo Compressor ou Serpentina
 *Se possuir uma das soluções e não encontrar as peças não deixa abrir a Ordem de Serviço
 */
function verifica_solucao_peca_fujitsu(){
	global $con, $login_fabrica, $campos;

	$solucoes = $campos["produto"]["solucoes_multiplos"];
	$solucoes = explode(",", $solucoes);

	$solucao = implode(",",$solucoes);
	
	if (strlen(trim($solucao)) >0) {
						
			$sql = "SELECT solucao FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND solucao IN({$solucao}) AND UPPER(descricao) ~ 'TROCA DE COMPRESSOR'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$pecas = array_merge($campos["produto_pecas"], $campos["subproduto_pecas"]);

				if (count($pecas) > 0) {
					$compressor = false;

					foreach ($pecas as $key => $peca) {
						$peca_id = $peca["id"];

						if (empty($peca_id)) {
							continue;
						}

						$sql = "SELECT troca_de_peca 
								FROM tbl_servico_realizado 
								WHERE servico_realizado = {$peca['servico_realizado']} 
								AND fabrica = {$login_fabrica}";
						$res = pg_query($con,$sql);
						$troca_peca = pg_fetch_result($res,0,'troca_de_peca');

						$sql = "SELECT peca
								FROM tbl_peca
								WHERE fabrica = {$login_fabrica}
								AND parametros_adicionais ~* '\"tipo_peca\":\"compressor\"'
								AND peca = {$peca_id}";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0 && $troca_peca == "t") {
							$compressor = true;
							break;
						}
					}

					if ($compressor === false) {
						throw new Exception("Para a solução atendimento ao cliente com troca de compressor deve-se lançar um compressor como troca");
					}
				}
			}

			$sql = "SELECT solucao FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND solucao IN({$solucao}) AND UPPER(descricao) ~ 'TROCA DE SERPENTINA'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$pecas = array_merge($campos["produto_pecas"], $campos["subproduto_pecas"]);

				if (count($pecas) > 0) {
					$serpentina = false;

					foreach ($pecas as $key => $peca) {
						$peca_id = $peca["id"];

						if (empty($peca_id)) {
							continue;
						}

						$sql = "SELECT troca_de_peca 
								FROM tbl_servico_realizado 
								WHERE servico_realizado = {$peca['servico_realizado']} 
								AND fabrica = {$login_fabrica}";
						$res = pg_query($con,$sql);
						$troca_peca = pg_fetch_result($res,0,'troca_de_peca');

						$sql = "SELECT peca
								FROM tbl_peca
								WHERE fabrica = {$login_fabrica}
								AND parametros_adicionais ~* 'serpentina'
								AND peca = {$peca_id}";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0 && $troca_peca == 't') {
							$serpentina = true;
							break;
						}
					}

					if ($serpentina === false) {
						throw new Exception("Para a solução atendimento ao cliente com troca de serpentina deve-se lançar uma serpentina como troca");
					}
				}
			}
	}
}

/**
 *Verifica se existe alguma solução de Outros Atendimentos
 *Se possuir a Ordem de Serviço deverá cair em auditoria
 */
function auditoria_os_solucao_fujitsu(){
	global $con, $login_fabrica, $os, $campos;

	$carga_gas = false;
	$valor_adicional = $campos["os"]["valor_adicional"];

    if (count($valor_adicional) > 0) {
        foreach($valor_adicional as $key => $value) {
            if (preg_match("/CARGA DE G[Áá]S/", strtoupper(utf8_decode($value))) || preg_match("/CARGA DE G[Áá]S/", strtoupper($value))) {
                $carga_gas = true;
                break;
            }
        }
    }

	$outros_atendimentos = false;
	$solucoes = $campos["produto"]["solucoes_multiplos"];
	$solucoes = explode(",", $solucoes);

	if (count($solucoes) > 0) {
		foreach ($solucoes as $solucao) {
			$sql = "SELECT solucao FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND solucao = {$solucao} AND UPPER(descricao) ~ 'OUTROS ATENDIMENTOS'";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$outros_atendimentos = true;
				break;
			}
		}
	}

	if (verifica_auditoria(array(167,64), array(167), $os) === true && $carga_gas === true && $outros_atendimentos === true) {
		$sql = "INSERT INTO tbl_os_status
				(os, status_os, observacao)
				VALUES
				({$os}, 167, 'OS com intervenção de outros atendimentos com carga de gás')";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao lançar ordem de serviço");
		} 
	}
}


function verifica_garantia_peca_fujitsu(){
	global $con, $login_fabrica, $os, $campos;



	$data_compra   = $campos["os"]["data_compra"];
	$data_abertura = $campos["os"]["data_abertura"];
	$produto       = $campos["produto"]["id"];
	$serie         = $campos["produto"]["serie"];
	$compressor    = $campos["produto"]["troca_compressor"];

	if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
		$sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$garantia = pg_fetch_result($res, 0, "garantia");
		}

		$sql = "SELECT serie_inicial 
				FROM tbl_produto_serie 
				WHERE fabrica = {$login_fabrica} 
				AND produto = {$produto}
				AND serie_inicial > '{$serie}'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$garantia = ($compressor == "t") ? 60 : 24;
			
		}

		if($compressor == "t"){

				$sql = "SELECT tbl_peca.peca
					FROM tbl_os_produto
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
					WHERE tbl_peca.fabrica = {$login_fabrica}
					AND tbl_peca.parametros_adicionais ~* '\"tipo_peca\":\"compressor\"'
					AND tbl_os_produto.os = {$os}";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) == 0 ) {
					throw new Exception("Para garantia de compressor, deve-se lançar um compressor na OS");
			}
				
			$sql = "SELECT 	tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.garantia_diferenciada 
						FROM tbl_os_produto 
						JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
						JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica} 
						WHERE tbl_os_produto.os = {$os}";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){

				for ($i=0; $i < pg_num_rows($res); $i++) {					
					$ref 	 = pg_fetch_result($res, $i, "referencia");
					$desc 	 = pg_fetch_result($res, $i, "descricao");
					$gar_dif = pg_fetch_result($res, $i, "garantia_diferenciada");

					if(!empty($gar_dif) AND $gar_dif < $garantia){
						$pecas[] = "A peça {$ref} - {$desc} está fora da garantia";
					}
				}

				if(count($pecas) > 0){
					$pecas = implode("<br>", $pecas);
					throw new Exception($pecas);
				}else{
					$valores = array("Garantia Compressor" => "t");
					$valores = json_encode($valores);
					grava_valor_adicional($valores,$os);
				}

			}
		}
	}
}

function auditoria_visita_tecnica_fora_garantia() {
	global $con, $login_fabrica, $os, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];
	$garantia         = $campos["os"]["garantia"];

	if (!empty($tipo_atendimento) && $garantia == "f") {
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND entrega_tecnica IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && verifica_auditoria(array(206, 207), array(206), $os) === true) {
			$sql = "INSERT INTO tbl_os_status
					(os, status_os, observacao)
					VALUES
					({$os}, 206, 'OS de visita técnica com produto fora de garantia')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

$auditorias = array(
	"auditoria_os_solucao_fujitsu",
	"auditoria_os_reincidente_fujitsu",
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"auditoria_km",
	"auditoria_visita_tecnica_fora_garantia"
);

function grava_multiplas_solucoes_fujitsu() {
	global $con, $os, $campos, $login_fabrica;

	if(!empty($campos["produto"]["solucoes_multiplos"])){

		$solucoes = explode(",", $campos["produto"]["solucoes_multiplos"]);

		for($i = 0; $i < count($solucoes); $i++){

			$sol = $solucoes[$i];

			$sql_sol = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao = {$sol}";
			$res_sol = pg_query($con, $sql_sol);

			if (!pg_num_rows($res_sol)) {
				$sql_sol = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao,fabrica) VALUES ({$os}, {$sol},{$login_fabrica})";
				$res_sol = pg_query($con, $sql_sol);
			}
		}

	}
}

$funcoes_fabrica = array(
	"grava_multiplas_solucoes_fujitsu",
	"verifica_garantia_peca_fujitsu"
);

?>
