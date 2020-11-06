<?php

use Posvenda\Regras;

$usaSolucao = true;

$regras["os|tipo_atendimento"]["function"] = array(
	"valida_regra_visita_improdutiva", "valida_tipo_atendimento_fabrimar", "remove_auditoria"
);

$regras["os|tipo_atendimento"]["obrigatorio"] = false;

$regras["os|nota_fiscal"] = array(
		"function" => array("valida_nota_fiscal_obrigatoriedade")
);

$regras["os|data_compra"] = array(
		"function" => array("valida_data_compra_obrigatoriedade")
);

$regras["consumidor|cpf"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|cep"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|bairro"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|endereco"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|numero"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|email"] = array(
	"obrigatorio"  => false
);

/* Revenda */

$regras["revenda|cep"] = array(
	"obrigatorio"  => false
);

$regras["revenda|endereco"] = array(
	"obrigatorio"  => true
);

$regras["revenda|numero"] = array(
	"obrigatorio"  => true
);

$regras["revenda|bairro"] = array(
	"obrigatorio"  => true
);

$regras["revenda|telefone"] = array(
	"obrigatorio"  => false
);

/* Produto */

$regras["produto|referencia"] = array(
	"obrigatorio"  => true
);

$regras["produto|descricao"] = array(
	"obrigatorio"  => true
);

$regras["produto|serie"] = array(
	"obrigatorio"  => false
);

$regras["produto|solucao"] = array(
	"obrigatorio"  => false,
	"function" => array("valida_tipo_solucao")
);

$regras["produto|troca_produto"] = array(
	"function" => array(
		"verifica_lancamento_peca"
	)
);

function valida_tipo_solucao(){
	global $con, $campos;


	if(strlen($campos['produto']['solucao']) > 0 ) {
		$solucao = $campos['produto']['solucao'];
		$sql = "select troca_peca from tbl_solucao where solucao = $solucao";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			$troca_peca = pg_fetch_result($res, 0, 'troca_peca');

			if($troca_peca == 't'){

				$pecas = $campos['produto_pecas'];
				$tem_pecas = 0;

				foreach($pecas as $peca){
					if(strlen($peca['referencia']) > 0 ){
						$tem_pecas++;
					}
				}

				if($tem_pecas == 0	){
					throw new Exception("Para essa solução é necessario inserir peças.");
				}

			}
		}
	}


}

function grava_os_fabrica() {
	global $campos;

	$justificativa_adicionais = ($campos["produto"]["troca_produto"] == "t") ? array("troca_produto" => "t") : array("troca_produto" => "f");
	$justificativa_adicionais = json_encode($justificativa_adicionais);
	$construtora = ($campos["os"]["construtora"] != "t") ? "f" : "t";
	$solucao = $campos["produto"]["solucao"] ;
	if(empty($solucao)) $solucao = "null";
	return array(
		"justificativa_adicionais" => "'{$justificativa_adicionais}'",
		"nf_os"                    => "'{$construtora}'",
		"solucao_os"				=> "{$solucao}"
	);
}

function verifica_lancamento_peca() {
	global $campos;

	$tem_pecas = false;

	$pecas = $campos["produto_pecas"];

	foreach ($pecas as $peca) {
		if (!empty($peca["id"])) {
			$tem_pecas = true;
			break;
		}
	}

	if ($campos["produto"]["troca_produto"] == "t" && $tem_pecas === true) {
		throw new Exception("A opção de Troca de Produto não permite o lançamento de Peças na OS");
	}
}

/*
* Auditorias
*/

function auditoria_km() {
	global $con, $login_fabrica, $os, $campos;

	if(!empty($campos["os"]["qtde_km_hidden"])){
		if (verifica_auditoria(array(98, 99, 100), array(98,99,100), $os) === true) {
			if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 98, 'KM alterado manualmente')";
				$res = pg_query($con, $sql);
			} else if($campos["os"]["qtde_km"] > 100) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 98, 'OS aguardando aprovação de KM')";
				$res = pg_query($con, $sql);
			} else {
				$posto = $campos["posto"]["id"];
				$cpf   = $campos["consumidor"]["cpf"];

				if (!empty($cpf)) {
					$sql = "SELECT tbl_os.os
							FROM tbl_os
							INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
							WHERE tbl_os.fabrica = {$login_fabrica}
							AND tbl_os.posto = {$posto}
							AND tbl_os.consumidor_cpf = '".preg_replace("/[\.\-\/]/", "", $cpf)."'
							AND tbl_tipo_atendimento.km_google IS TRUE
							AND tbl_os.os < {$os}
							AND tbl_os.data_digitacao::date = CURRENT_DATE";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						while ($xos = pg_fetch_object($res)) {
							if (verifica_auditoria(array(98, 99, 100), array(98), $xos->os) === true) {
								$sql = "INSERT INTO tbl_os_status
										(os, status_os, observacao)
										VALUES
										({$xos->os}, 98, 'OS aguardando aprovação de KM')";
								$res = pg_query($con, $sql);
							}
						}

						$sql = "INSERT INTO tbl_os_status
								(os, status_os, observacao)
								VALUES
								({$os}, 98, 'OS aguardando aprovação de KM')";
						$res = pg_query($con, $sql);
					}
				}
			}

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_tipo_atendimento() {
	global $con, $os, $login_fabrica, $campos;



	$tipo_atendimento = $campos["os"]["tipo_atendimento"];
	if (!empty($tipo_atendimento)) {
		if ($tipo_atendimento == 201) {
			$sql = "SELECT os_troca FROM tbl_os_troca WHERE fabric = {$login_fabrica} AND os = {$os}";
			$res = pg_query($con, $sql);

			if ($campos["produto"]["troca_produto"] == "t" && verifica_auditoria(array(192,193), array(192,193), $os) === true) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 192, 'Solicitação de troca de produto aguardando aprovação')";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar Ordem de Serviço");
				}
			} else if (!pg_num_rows($res) && verifica_auditoria(array(205,204), array(205,204), $os) === true && verifica_peca_lancada() === true) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 205, 'Solicitação de peça(s) aguardando aprovação')";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar Ordem de Serviço");
				}
			}
		} else if($tipo_atendimento == 200){
			$sql_peca = "UPDATE tbl_os_item SET peca_obrigatoria = 'f' WHERE tbl_os_item.os_produto = (SELECT tbl_os_produto.os_produto FROM tbl_os_produto WHERE tbl_os_produto.os = {$os})";
			$res_peca = pg_query($con,$sql_peca);
			if (verifica_auditoria(array(199, 200, 201), array(199,200), $os) === true) {

				$sql = "INSERT INTO tbl_os_status
				(os, status_os, observacao)
				VALUES
				({$os}, 199, 'OS aguardando aprovação de Auditoria de Análise na fábrica')";

				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}
}

function valida_regra_visita_improdutiva(){

	global $con, $login_fabrica, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (!empty($tipo_atendimento)) {
		$sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = {$tipo_atendimento} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$pecas = $campos["produto_pecas"];
		$cont_pecas = 0;

		for($i = 0; $i < count($pecas); $i++){
			$id = $pecas[$i]["id"];
			if(!empty($id)){
				$cont_pecas++;
			}
		}

		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res, 0, "descricao");

			if($descricao == "Visita improdutiva" && $cont_pecas > 0){
				throw new Exception("Para o Tipo de Atendimento Visita Improdutiva, não é possível gravar Peças");
			}
		}
	}
}


function finaliza_os_fabrimar(){

	global $con, $os, $login_fabrica, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (!empty($tipo_atendimento)) {
		$sql_ta = "SELECT fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
		$res_ta = pg_query($con, $sql_ta);

		if(pg_num_rows($res_ta) > 0){
			$fora_garantia    = pg_fetch_result($res_ta, 0, "fora_garantia");

			if($fora_garantia == "t"){
				finaliza_os();

				$sqlExtrato = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$os}";
				$resExtrato = pg_query($con, $sqlExtrato);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao gravar Ordem de Serviço");
				}
			}
		}
	}
}

function solicita_troca_produto_fabrimar() {
	global $con, $os, $campos, $login_fabrica;

	if ($campos["os"]["tipo_atendimento"] == 201 && $campos["produto"]["troca_produto"] == "t" && !empty($os)) {
        $sqlTroca = "
            SELECT  os_troca
            FROM    tbl_os_troca
            WHERE   os = $os
            AND     fabric = $login_fabrica
        ";
        $resTroca = pg_query($con,$sqlTroca);
        if(pg_num_rows($resTroca) == 0){
            $sql = "SELECT tbl_peca.peca
                    FROM tbl_peca
                    INNER JOIN tbl_produto ON tbl_produto.referencia = tbl_peca.referencia AND tbl_produto.fabrica_i = {$login_fabrica}
                    WHERE tbl_peca.fabrica = {$login_fabrica}
                    AND tbl_peca.produto_acabado IS TRUE
                    AND tbl_produto.produto = {$campos['produto']['id']}";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                $sql = "SELECT referencia, descricao, ipi
                        FROM tbl_produto
                        WHERE fabrica_i = {$login_fabrica}
                        AND produto = {$campos['produto']['id']}";
                $res = pg_query($con, $sql);

                $troca_referencia = pg_fetch_result($res, 0, "referencia");
                $troca_descricao  = pg_fetch_result($res, 0, "descricao");
                $troca_ipi        = pg_fetch_result($res, 0, "ipi");

                if (!strlen($troca_ipi)) {
                    $troca_ipi = 0;
                }

                $sql = "INSERT INTO tbl_peca
                        (fabrica, referencia, descricao, ipi, origem, produto_acabado)
                        VALUES
                        ({$login_fabrica}, '{$troca_referencia}', '{$troca_descricao}', {$troca_ipi}, 'NAC', TRUE)
                        RETURNING peca";
                $res = pg_query($con, $sql);

                if (!strlen(pg_last_error())) {
                    $peca = pg_fetch_result($res, 0, "peca");
                } else {
                    throw new Exception("Erro ao solicitar troca do produto");
                }
            } else {
                $peca = pg_fetch_result($res, 0, "peca");
            }

            $sql = "SELECT lista_basica
                    FROM tbl_lista_basica
                    WHERE fabrica = {$login_fabrica}
                    AND produto = {$campos['produto']['id']}
                    AND peca = {$peca}";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                $sql = "INSERT INTO tbl_lista_basica
                        (produto, peca, qtde, fabrica, ativo)
                        VALUES
                        ({$campos['produto']['id']}, {$peca}, 1, {$login_fabrica}, TRUE)";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao solicitar troca do produto");
                }
            }

            $sql = "INSERT INTO tbl_os_troca
                    (os, produto, peca, gerar_pedido, fabric)
                    VALUES
                    ({$os}, {$campos['produto']['id']}, $peca, FALSE, {$login_fabrica})";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao solicitar troca do produto");
            }

            $sql = "SELECT servico_realizado
                    FROM tbl_servico_realizado
                    WHERE fabrica = {$login_fabrica}
                    AND gera_pedido IS TRUE
                    AND troca_produto IS TRUE";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0 || !pg_num_rows($res)) {
                throw new Exception("Erro ao solicitar troca do produto");
            } else {
                $servico_realizado_troca_produto = pg_fetch_result($res, 0, "servico_realizado");
            }

            $sql = "SELECT os_produto FROM tbl_os_produto WHERE os = {$os}";
            $res = pg_query($con, $sql);

            $os_produto = pg_fetch_result($res, 0, "os_produto");

            $sql = "INSERT INTO tbl_os_item
                    (os_produto, peca, qtde, servico_realizado)
                    VALUES
                    ({$os_produto}, {$peca}, 1, {$servico_realizado_troca_produto})";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao solicitar troca do produto");
            }
		} 
		
	}
}

function lgr_fabrimar() {
	global $con, $os, $login_fabrica, $campos;

	$linha_sem_lgr = \Posvenda\Regras::get("linha_sem_lgr", "ordem_de_servico", $login_fabrica);
	$produto_id    = $campos["produto"]["id"];

	$sql = "SELECT linha FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto_id}";
	$res = pg_query($con, $sql);

	$linha = pg_fetch_result($res, 0, "linha");

	if ($linha == $linha_sem_lgr) {
		$sql = "UPDATE tbl_os_item SET
					peca_obrigatoria = 'f'
				FROM tbl_os_produto
				WHERE tbl_os_produto.os = {$os}
				AND tbl_os_item.os_produto = tbl_os_produto.os_produto
				AND tbl_os_item.peca_obrigatoria NOT IN ('f')";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao lançar ordem de serviço");
		}
	}
}

function lgr_cortesia() {
	global $con, $os, $campos, $areaAdmin;

	if ($areaAdmin === true && $campos["os"]["cortesia"] == "t") {
		$sql = "UPDATE tbl_os_item SET
					peca_obrigatoria = 'f'
				FROM tbl_os_produto
				WHERE tbl_os_produto.os = {$os}
				AND tbl_os_item.os_produto = tbl_os_produto.os_produto
				AND tbl_os_item.peca_obrigatoria NOT IN ('f')";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao lançar ordem de serviço");
		}
	}
}

$funcoes_fabrica = array(
	"finaliza_os_fabrimar",
	"solicita_troca_produto_fabrimar",
	"lgr_fabrimar",
	"lgr_cortesia"
);

function valida_tipo_atendimento_fabrimar() {
	global $con, $campos, $login_fabrica;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (empty($tipo_atendimento) && verifica_peca_lancada() === true) {
		throw new Exception("Para lançar peças selecione um tipo de atendimento");
	}

	if (!empty($tipo_atendimento)) {
		$linha_tipo_atendimento = \Posvenda\Regras::get("linha_tipo_atendimento", "ordem_de_servico", $login_fabrica);

		if (count($linha_tipo_atendimento) > 0) {
			$tipo_atendimento = $campos["os"]["tipo_atendimento"];
			$produto_id       = $campos["produto"]["id"];

			$sql = "SELECT tbl_linha.linha, tbl_linha.nome
					FROM tbl_produto
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE fabrica_i = {$login_fabrica}
					AND produto = {$produto_id}";
			$res = pg_query($con, $sql);

			$linha      = pg_fetch_result($res, 0, "linha");
			$linha_nome = pg_fetch_result($res, 0, "nome");

			if (isset($linha_tipo_atendimento[$linha]) && !in_array($tipo_atendimento, $linha_tipo_atendimento[$linha])) {
				throw new Exception("Para produto da linha {$linha_nome} não pode ser lançado uma ordem de serviço com o tipo de atendimento selecionado");
			}
		}

		if (($troca_peca == true && $troca_produto == true) || $tipo_atendimento == 201) {
			if (verifica_peca_lancada(false) == false && $campos["produto"]["troca_produto"] != "t") {
				throw new Exception("Para o tipo de atendimento selecionado deve ser solicitado peças ou a troca do produto");
			}
		}
	}
}

function auditoria_os_reincidente_fabrimar() {
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$os_construtora = $campos["os"]["construtora"];

	if ($os_construtora != "t") {
		$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$select = "SELECT tbl_os.os
					FROM tbl_os
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.os < {$os}
					AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
					AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
					AND tbl_tipo_atendimento.grupo_atendimento NOT IN('R')
					AND tbl_os_produto.produto = {$campos['produto']['id']}
					AND tbl_os.nf_os IS NOT TRUE
					ORDER BY tbl_os.data_abertura DESC
					LIMIT 1";
			$resSelect = pg_query($con, $select);

			if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
				$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

				if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
					$insert = "INSERT INTO tbl_os_status
							(os, status_os, observacao)
							VALUES
							({$os}, 70, 'OS reincidentee de cnpj, nota fiscal e produto')";
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
}

function auditoria_os_construtora() {
	global $con, $login_fabrica, $os, $campos;

	$os_construtora = $campos["os"]["construtora"];

	if ($os_construtora == "t") {
		if (verifica_auditoria(array(213, 214, 215), array(213,214), $os) === true) {
			$sql = "INSERT INTO tbl_os_status
					(os, status_os, observacao)
					VALUES
					({$os}, 213, 'OS com Construtora pendente de auditoria da fábrica')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_troca_obrigatoria_fabrimar() {
	global $con, $os, $login_fabrica, $campos;

	//não faz auditoria de troca obrigatória para visita improdutiva
	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if ($tipo_atendimento != 198) {
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
}

$auditorias = array(
	"auditoria_km",
	"auditoria_os_reincidente_fabrimar",
	"auditoria_troca_obrigatoria_fabrimar",
	"auditoria_peca_critica",
	"auditoria_tipo_atendimento",
	"auditoria_pecas_excedentes",
	"auditoria_os_construtora"
);

function valida_garantia_fabrimar() {
	global $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];
	$os_construtora   = $campos["os"]["construtora"];

	if (!empty($tipo_atendimento) && $os_construtora != "t") {
		valida_garantia();
	}
}

$valida_garantia = "valida_garantia_fabrimar";

$valida_anexo = "";

$valida_anexo_peca = "";

$regras_pecas["lista_basica"] = false;

function valida_nota_fiscal_obrigatoriedade() {
	global $campos, $msg_erro;

	$os_construtora = $campos["os"]["construtora"];
	$nota_fiscal    = trim($campos["os"]["nota_fiscal"]);

	if ($os_construtora != "t" && empty($nota_fiscal)) {
		$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
		$msg_erro["campos"][]                 = "os[nota_fiscal]";
	}
}

function valida_data_compra_obrigatoriedade() {
	global $campos, $msg_erro, $regex;

	$os_construtora = $campos["os"]["construtora"];
	$data_compra    = trim($campos["os"]["data_compra"]);

	if ($os_construtora != "t") {
		if (empty($data_compra)) {
			$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
			$msg_erro["campos"][]                 = "os[data_compra]";
		}
	}

	if (!empty($data_compra) && !preg_match($regex["date"], $data_compra)) {
		throw new Exception("Data da Compra inválida");
	} else if (!empty($data_compra)) {
		try {
			valida_data_compra();
		} catch(Exception $e) {
			throw new EXception($e->getMessage());
		}
	}
}

function remove_auditoria() {
	global $con, $campos, $auditorias, $login_fabrica;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (!empty($tipo_atendimento)) {
		$sql_ta = "SELECT fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
		$res_ta = pg_query($con, $sql_ta);

		if(pg_num_rows($res_ta) > 0){
			$fora_garantia    = pg_fetch_result($res_ta, 0, "fora_garantia");
		}
	}

	if (empty($tipo_atendimento) || $fora_garantia == "t") {
		$auditorias = array();
	}
}



?>
