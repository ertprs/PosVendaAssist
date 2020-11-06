<?
$regras["produto|serie"] = array(
	"function" => array("valida_numero_serie_elgin_automacao")
);

$regras["produto|defeitos_constatados_multiplos"] = array(
	"function" => array("valida_defeito_constatado")
);

if ($postoInterno) {
	$regras["os|os_elgin_status"] = array(
		"obrigatorio" => true
	);
}

$regras["os|acessorios"] = array(
	"obrigatorio" => true
);

$regras["consumidor|telefone"] = array(
	"obrigatorio" => true
);

$regras["produto_pecas|void"] = array(
	"function" => array("valida_peca_void")
);

$regras["os|fora_garantia"] = array(
    "function" => array("tira_valida_garantia")
);

$regras["os|tipo_atendimento"]["function"] = array("valida_tipo_atendimento_elgin_automacao");

$regras["revenda|nome"] = array(
	"obrigatorio" => false
);

$regras["revenda|cnpj"] = array(
	"obrigatorio" => false
);

$regras["revenda|estado"] = array(
	"obrigatorio" => false
);

$regras["revenda|cidade"] = array(
	"obrigatorio" => false
);

if (!empty($os)) {
    $sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = $os";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
		$valida_anexo = "";
    }
}

if ($areaAdmin === true) {
	$valida_anexo = "";
}

function valida_defeito_constatado(){
	global $con, $campos, $login_fabrica, $msg_erro;

	if(strlen(trim($campos['produto']['defeitos_constatados_multiplos'])) >0) {
		if(strlen(trim($campos['os']['observacoes']))== 0){
			$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
			$msg_erro["campos"][] = "os[observacoes]";
		}
	}
}
function valida_peca_void(){
	global $con, $campos, $login_fabrica, $msg_erro;

	foreach ($campos['produto_pecas'] as $key => $pecas ) {

		if(strlen(trim($pecas['referencia'])) >0) {
			if(strlen(trim($pecas['void']))== 0){
				$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
				$msg_erro["campos"][] = "produto_pecas[".$key."][void]";
			}
		}
	}
}

function valida_tipo_atendimento_elgin_automacao() {
	global $campos, $con, $login_fabrica, $msg_erro;

	$tipo_atendimento       = $campos["os"]["tipo_atendimento"];
	$produto_reparo_fabrica = $campos["produto"]["produto_reparo_fabrica"];

	if (!empty($tipo_atendimento)) {
		$sql = "SELECT fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND fora_garantia IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && empty($produto_reparo_fabrica)) {
			throw new Exception("Para o tipo de atendimento fora de garantia é obrigatório selecionar a opção reparar na fábrica");
		}

		if (!empty($produto_reparo_fabrica)) {
			$nf_envio       = $campos["os"]["nf_envio"];
			$data_nf_envio  = $campos["os"]["data_nf_envio"];
			$valor_nf_envio = $campos["os"]["valor_nf_envio"];

			if (
				empty($nf_envio)
				|| empty($data_nf_envio)
				|| empty($valor_nf_envio)
			) {
				$msg_erro["campos"][] = "os[nf_envio]";
				$msg_erro["campos"][] = "os[data_nf_envio]";
				$msg_erro["campos"][] = "os[valor_nf_envio]";

				throw new Exception("Preencha as informações de nota fiscal de envio para reparo na fábrica");
			} else {
				list($dia, $mes, $ano) = explode("/", $data_nf_envio);

				if (!strtotime("{$ano}-{$mes}-{$dia}")) {
					$msg_erro["campos"][] = "os[data_nf_envio]";

					throw new Exception("Data da Nota Fiscal de Recebimento inválida");
				}
			}
		}
	}
}

$antes_valida_campos = "valida_campos_posto_interno_elgin_automacao";

function valida_campos_posto_interno_elgin_automacao() {
	global $msg_erro, $campos;

	if (verifica_tipo_posto("posto_interno", "TRUE") == true) {
		$natureza_operacao = $campos["os"]["natureza_operacao"];
		$tipo_produto      = $campos["os"]["tipo_produto"];

		$nf_envio          = $campos["os"]["nf_envio"];
		$data_nf_envio     = $campos["os"]["data_nf_envio"];
		$valor_nf_envio    = $campos["os"]["valor_nf_envio"];

		$data_nota_fiscal_mo   = $campos["os"]["data_nota_fiscal_mo"];
		$data_nota_fiscal_peca = $campos["os"]["data_nota_fiscal_peca"];

		if (empty($natureza_operacao)) {
			$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
			$msg_erro["campos"][]                 = "os[natureza_operacao]";
		}

		if (empty($tipo_produto)) {
			$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
			$msg_erro["campos"][]                 = "os[tipo_produto]";
		}

		if (empty($nf_envio)) {
			$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
			$msg_erro["campos"][]                 = "os[nf_envio]";
		}

		if (empty($data_nf_envio)) {
			$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
			$msg_erro["campos"][]                 = "os[data_nf_envio]";
		} else {
			list($dia, $mes, $ano) = explode("/", $data_nf_envio);

			if (!strtotime("{$ano}-{$mes}-{$dia}")) {
				$msg_erro["msg"][]    = " Data da Nota Fiscal de Recebimento inválida";
				$msg_erro["campos"][] = "os[data_nf_envio]";
			}
		}

		if (empty($valor_nf_envio)) {
			$msg_erro["msg"]["campo_obrigatorio"] = " Preencha todos os campos obrigatórios";
			$msg_erro["campos"][]                 = "os[valor_nf_envio]";
		}

		if (!empty($data_nota_fiscal_mo)) {
			list($dia, $mes, $ano) = explode("/", $data_nota_fiscal_mo);

			if (!strtotime("{$ano}-{$mes}-{$dia}")) {
				$msg_erro["msg"][]    = " Data da Nota Fiscal de MO inválida";
				$msg_erro["campos"][] = "os[data_nota_fiscal_mo]";
			}
		}

		if (!empty($data_nota_fiscal_peca)) {
			list($dia, $mes, $ano) = explode("/", $data_nota_fiscal_peca);

			if (!strtotime("{$ano}-{$mes}-{$dia}")) {
				$msg_erro["msg"][]    = " Data da Nota Fiscal de Peça inválida";
				$msg_erro["campos"][] = "os[data_nota_fiscal_peca]";
			}
		}
	}
}

function grava_os_fabrica() {
	global $con, $campos, $login_unico_tecnico, $os;

	$qtde_visita     = $campos["os"]["qtde_visita"];
	// $void            = $campos["produto"]["void"];
	$os_elgin_status = $campos["os"]["os_elgin_status"];
	$sem_ns          = $campos["produto"]["sem_ns"];
	$tipo_os         = $campos["os"]["fora_garantia"];

	$return = array();
	$count = true;

	if (!empty($os_elgin_status)) {
		$sql = "SELECT tbl_status_os.status_os FROM tbl_status_os WHERE tbl_status_os.descricao = '{$os_elgin_status}' ";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0 ){
			$status_os_ultimo = pg_fetch_result($res, 0, "status_os");
			$return['status_os_ultimo'] = $status_os_ultimo ; 
			$count = false;
		} else {
			throw new Exception("Erro ao gravar status");
		}
	}

	if (strlen($qtde_visita) > 0) {
		$return['qtde_diaria'] = $qtde_visita ;  
		$count = false;
	}

	if (strlen($sem_ns) > 0) {
		$return['embalagem_original'] = "'{$sem_ns}'" ;  
		$count = false;
	}

	// if (strlen($void) > 0) {
	// 	$return['serie_reoperado'] = "'{$void}'" ;  
	// }

	if(strlen(trim($tipo_os)) > 0){
        $return['tipo_os'] = $tipo_os ;
		$count = false;
    }

    if(strlen(trim($login_unico_tecnico)) > 0 and empty($os)){
        $return['tecnico'] = $login_unico_tecnico ;
		$count = false;
    }
    if ($count){
    	unset($return);
    }
	
	return $return;

}

$grava_os_item_function = "grava_os_item_elgin";

function grava_os_item_elgin($os_produto, $subproduto = "produto_pecas") {
   
   	global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

	if($historico_alteracao === true){
		$historico = array();
	}

	foreach ($campos[$subproduto] as $posicao => $campos_peca) {
		if (strlen($campos_peca["id"]) > 0) {

			if (function_exists("grava_custo_peca") ) {
				/**
				 * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
				 * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
				 */
				$custo_peca = grava_custo_peca();
				if($custo_peca==false){
					unset($custo_peca);
				}
			}

			if($historico_alteracao === true){
				include "$login_fabrica/historico_alteracao.php";
			}

			$sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
			$res = pg_query($con, $sql);

			$troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

			if ($troca_de_peca == "t") {
				$sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$campos_peca['id']}";
				$res = pg_query($con, $sql);

				$devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

				if ($devolucao_obrigatoria == "t") {
					$devolucao_obrigatoria = "TRUE";
				} else {
					$devolucao_obrigatoria = "FALSE";
				}
			} else {
				$devolucao_obrigatoria = "FALSE";
			}

			$login_admin = (empty($login_admin)) ? "null" : $login_admin;

			if (empty($campos_peca["os_item"])) {
				$sql = "INSERT INTO tbl_os_item
						(
							os_produto,
							peca,
							qtde,
							servico_realizado,
							peca_obrigatoria,
							admin
							".((isset($campos_peca['void'])) ? ", parametros_adicionais" : "")."
							".((isset($custo_peca)) ? ", custo_peca" : "")."
							".(($grava_defeito_peca == true) ? ", defeito" : "")."
						)
						VALUES
						(
							{$os_produto},
							{$campos_peca['id']},
							{$campos_peca['qtde']},
							{$campos_peca['servico_realizado']},
							{$devolucao_obrigatoria},
							{$login_admin}
							".((isset($campos_peca['void'])) ? ", '".($campos_peca['void'])."'" : "")."
							".((isset($custo_peca)) ? ", '".$custo_peca[$campos_peca['id']]."'" : "")."
							".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "")."
						)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao gravar Ordem de Serviço #9");
				}
			} else {
				$sql = "SELECT tbl_os_item.os_item
						FROM tbl_os_item
						INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
						WHERE tbl_os_item.os_produto = {$os_produto}
						AND tbl_os_item.os_item = {$campos_peca['os_item']}
						AND tbl_os_item.pedido IS NULL
						AND UPPER(tbl_servico_realizado.descricao) NOT IN('CANCELADO', 'TROCA PRODUTO')";
				$res = pg_query($con, $sql);

				if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
					continue;
				}

				if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
					continue;
				}

				if (pg_num_rows($res) > 0) {
					$sql = "UPDATE tbl_os_item SET
								qtde = {$campos_peca['qtde']},
								servico_realizado = {$campos_peca['servico_realizado']}
								".((isset($campos_peca['void'])) ? ", parametros_adicionais = '".$campos_peca['void']."'" : "")."
								".(($grava_defeito_peca == true) ? ", defeito = {$campos_peca['defeito_peca']}" : "")."
							WHERE os_produto = {$os_produto}
							AND os_item = {$campos_peca['os_item']}";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao gravar Ordem de Serviço #10");
					}
				}
			}
		}
	}

	if($historico_alteracao === true){

		if(count($historico) > 0){

			grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);

		}

	}

}


$funcoes_fabrica = array(
	"grava_defeito_constatado_multiplo",
	"verifica_estoque_peca",
	"grava_reparo_fabrica_solicitacao_elgin_automacao",
	"auditoria_os_orcamento_elgin_automacao",
	"grava_comunicado_elgin_automacao",
	"grava_tipo_os_elgin_automacao"
);

function grava_tipo_os_elgin_automacao() {
	global $con, $campos, $login_fabrica, $os;

	$tipo_produto = $campos["os"]["tipo_produto"];

	if (!empty($tipo_produto)) {
		$sql = "
			SELECT tipo_os FROM tbl_tipo_os WHERE descricao = '{$tipo_produto}'
		";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Erro ao gravar o tipo de OS");
		}

		$tipo_os = pg_fetch_result($res, 0, "tipo_os");

		$sql = "
			UPDATE tbl_os SET
				tipo_os = {$tipo_os}
			WHERE fabrica = {$login_fabrica}
			AND os = {$os}
		";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar o tipo de OS");
		}
	}
}

function valida_numero_serie_elgin_automacao() {
	global $con, $campos, $login_fabrica, $msg_erro;

	if($campos['produto']['sem_ns'] != 't'){

		$produto_id = $campos["produto"]["id"];
		$produto_serie = $campos["produto"]["serie"];

		if (strlen($produto_id) > 0) {
			$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto_id AND numero_serie_obrigatorio IS TRUE;";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0 && empty($produto_serie)){
				$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
				$msg_erro["campos"][] = "produto[serie]";
			}
		}
	}
}

function auditoria_km_elgin_automacao(){

	global $con, $login_fabrica, $os, $campos;

	if ($campos["os"]["qtde_km"] >= 100) {
		if (verifica_auditoria_unica("tbl_auditoria_status.km = 't'", $os) === true) {
			$busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

			if($busca['resultado']){
				$auditoria_status = $busca['auditoria'];
			}

			if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
					VALUES ({$os}, {$auditoria_status}, 'Auditoria de KM, KM alterado manualmente')";
			} else {
				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
					VALUES ({$os}, {$auditoria_status}, 'Auditoria de KM')";
			}

			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_visita_elgin_automacao(){
	global $con, $login_fabrica, $os, $campos;

	if ($campos["os"]["qtde_visita"] >= 2) {
		if (verifica_auditoria_unica("tbl_auditoria_status.km = 't' AND tbl_auditoria_os.observacao = 'Auditoria de Visita'", $os) === true) {
			$busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

			if($busca['resultado']){
				$auditoria_status = $busca['auditoria'];
			}

			$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
				VALUES ({$os}, {$auditoria_status}, 'Auditoria de Visita')";

			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_produto_sem_numero_serie_elgin_automacao(){

	global $con, $login_fabrica, $os, $campos, $areaAdmin;

	if ($campos["produto"]["sem_ns"] == "t" && $areaAdmin !== true) {
		if (verifica_auditoria_unica("tbl_auditoria_status.produto = 't' AND tbl_auditoria_os.observacao = 'Auditoria de produto sem número de série'", $os) === true) {
			$busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

			if($busca['resultado']){
				$auditoria_status = $busca['auditoria'];
			}

			$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
				VALUES ({$os}, {$auditoria_status}, 'Auditoria de produto sem número de série')";

			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_os_orcamento_elgin_automacao(){
	global $con, $login_fabrica, $os, $campos;

	if (verifica_tipo_posto("posto_interno", "TRUE") == true) {
		$tipo_atendimento = $campos["os"]["tipo_atendimento"];
		
		if (!empty($tipo_atendimento)) {
			$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE tipo_atendimento = {$tipo_atendimento} and descricao = 'Orçamento' ";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0 && verifica_peca_lancada() == true) {
				if (verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'Auditoria de Orçamento'", $os) === true) {
					$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

					if($busca['resultado']){
						$auditoria_status = $busca['auditoria'];
					}

					$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
						VALUES ({$os}, {$auditoria_status}, 'Auditoria de Orçamento')";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao lançar ordem de serviço");
					}

                    $status = 225; //Emitir Orçamento
                    mudaStatusOsPostoExterno($os,$status);
				}
			}
		}
	}
}

function mudaStatusOsPostoExterno($osInterna, $status){
	global $con, $login_fabrica, $os, $campos;

    $sql = "SELECT os_numero FROM tbl_os WHERE fabrica = $login_fabrica AND os = $osInterna";

    $res = pg_query($con, $sql);

    $osPostoExterno = pg_fetch_result($res, 0, 0);

    if(empty($osPostoExterno)){
        throw new Exception('Erro ao encontrar OS externa');
    }

    $insert = "INSERT INTO tbl_os_status (os, status_os, fabrica_status, observacao) VALUES ($osPostoExterno, $status, $login_fabrica, 'Em Orçamento')";

    $res = pg_query($con, $insert);

    if(strlen(pg_last_error($con)) > 0){
        throw new Exception('Erro ao alterar status da OS externa');
    }
}

function tira_valida_garantia(){
	global $login_fabrica, $campos, $os, $con, $login_admin, $valida_garantia, $valida_garantia_item;

	if($campos['os']['fora_garantia'] == 17 || verifica_tipo_posto("posto_interno", "TRUE") == true){
		
	    $valida_garantia = NULL;
	    $valida_garantia_item = NULL;
	    
	}
}

function grava_os_extra_fabrica() {
	global $campos;

	$produto_reparo_fabrica = $campos["produto"]["produto_reparo_fabrica"];
	$natureza_operacao      = $campos["os"]["natureza_operacao"];
	$observacoes_administrativas      = $campos["os"]["obs_adicionais"];

	return array(
		"recolhimento"     => (!empty($produto_reparo_fabrica)) ? "'{$produto_reparo_fabrica}'" : "null",
		"natureza_servico" => "'{$natureza_operacao}'",
		"obs_adicionais" => "'$observacoes_administrativas'"
	);
}

function grava_os_campo_extra_fabrica() {
	global $campos;

	$nf_envio       = $campos["os"]["nf_envio"];
	$data_nf_envio  = $campos["os"]["data_nf_envio"];
	$valor_nf_envio = $campos["os"]["valor_nf_envio"];

	$nf_retorno = $campos["os"]["nf_retorno"];
	$data_nf_retorno = $campos["os"]["data_nf_retorno"];
	$valor_nf_retorno = $campos["os"]["valor_nf_retorno"];

	$nota_fiscal_mo       = $campos["os"]["nota_fiscal_mo"];
	$data_nota_fiscal_mo  = $campos["os"]["data_nota_fiscal_mo"];
	$valor_nota_fiscal_mo = $campos["os"]["valor_nota_fiscal_mo"];

	$nota_fiscal_peca       = $campos["os"]["nota_fiscal_peca"];
	$data_nota_fiscal_peca  = $campos["os"]["data_nota_fiscal_peca"];
	$valor_nota_fiscal_peca = $campos["os"]["valor_nota_fiscal_peca"];

	if(strlen($nf_envio) > 0 AND strlen($data_nf_envio) > 0){

		list($d,$m,$y) = explode("/",$data_nf_envio);

		if(!checkdate($m, $d, $y)){
			throw new Exception("Data da Nota Fiscal de Envio inválida");
		}

		$return = array();

		$return["nf_envio"]       = $nf_envio;
		$return["data_nf_envio"]  = $data_nf_envio;
		$return["valor_nf_envio"] = $valor_nf_envio;

		$return["nf_retorno"] = $nf_retorno;
		$return["data_nf_retorno"] = $data_nf_retorno;
		$return["valor_nf_retorno"] = $valor_nf_retorno;

		$return["nota_fiscal_mo"]       = $nota_fiscal_mo;
		$return["data_nota_fiscal_mo"]  = $data_nota_fiscal_mo;
		$return["valor_nota_fiscal_mo"] = $valor_nota_fiscal_mo;

		$return["nota_fiscal_peca"]       = $nota_fiscal_peca;
		$return["data_nota_fiscal_peca"]  = $data_nota_fiscal_peca;
		$return["valor_nota_fiscal_peca"] = $valor_nota_fiscal_peca;
	}

	return $return;
}

function grava_reparo_fabrica_solicitacao_elgin_automacao() {
	global $campos, $con, $login_fabrica, $os, $login_posto;

	$status = (empty($login_posto)) ? 219 : 217;
	$obs = (empty($login_posto)) ? "Aguardando Confirmação de Recebimento do Produto" : "Aguardando Aprovação de Reparo na Fábrica";

	if ($campos["produto"]["produto_reparo_fabrica"] == "t") {
		$sql = "SELECT os FROM tbl_os_status WHERE fabrica_status = $login_fabrica AND os = $os AND status_os = {$status}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$sql = "INSERT INTO tbl_os_status (os, status_os, fabrica_status, observacao) VALUES ($os, {$status}, $login_fabrica, '{$obs}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar reparo na fábrica");
			}
		}
	}
}

function grava_custo_peca() {

	global $con, $login_fabrica, $os, $campos;

	$posto = $campos["posto"]["id"];
	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	$sql = "SELECT tipo_atendimento
		FROM tbl_tipo_atendimento
		WHERE fabrica = {$login_fabrica}
		AND tipo_atendimento = {$tipo_atendimento} 
		AND descricao = 'Orçamento' ";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		
		$return = array();

		foreach($campos["produto_pecas"] as $key => $peca) {
			$valor = $peca["valor_total"];
			$return[$peca['id']] = $valor;
		}

		return $return;
	}else{
		return false;
	}
}

function auditoria_os_reincidente_elgin_automacao() {
	if (!verifica_tipo_posto("posto_interno", "TRUE")) {
		auditoria_os_reincidente();
	}
}

function grava_comunicado_elgin_automacao(){

	global $campos, $con, $login_fabrica, $os, $login_admin;

	if(!empty($login_admin)){
		$sql = "SELECT tbl_tipo_posto.tipo_posto, tbl_os.posto
				FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN tbl_posto_tipo_posto ON tbl_posto_tipo_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_posto.fabrica = {$login_fabrica}
				JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica} 
				AND tbl_tipo_posto.posto_interno IS TRUE
				WHERE tbl_os.os = {$os}";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$posto = pg_fetch_result($res, 0, 'posto');
			$msg = "Foi aberta a Ordem de Serviço {$os} para que seja realizada a análise";

			$sql = "INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, posto,obrigatorio_site, ativo)
							VALUES ('{$msg}','Comunicado',{$login_fabrica},{$posto},TRUE, TRUE)";
			$res = pg_query($con, $sql);
		}
	}

}

function auditoria_peca_elgin_automacao() {
    global $con, $os, $login_fabrica, $qtde_pecas;

    if (verifica_tipo_posto("posto_interno", "TRUE") == true) {
        return false;
    }

    if(verifica_peca_lancada(false) === true){
        $sql = "SELECT tbl_os_item.os_item
                FROM tbl_os_item
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                  AND tbl_servico_realizado.gera_pedido IS TRUE
                  AND troca_de_peca IS TRUE
                WHERE tbl_os_produto.os = {$os}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

            if ($busca['resultado']) {
                $auditoria_status = $busca['auditoria'];
            }

            if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao NOT ILIKE '%peça crí­tica%'", $os) === true) {
                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                    ({$os}, $auditoria_status, 'OS em intervenção de Peças')";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                }
            } elseif (aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao NOT ILIKE '%peça crí­tica%'") && verifica_peca_lancada() === true){
                $nova_peca = pegar_peca_lancada();

                if (count($nova_peca) > 0) {
                    $sql = "SELECT tbl_os_item.os_item
                        FROM tbl_os_item
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            AND tbl_servico_realizado.gera_pedido IS TRUE
                            AND troca_de_peca IS TRUE
                        WHERE tbl_os_produto.os = {$os}
                        AND tbl_peca.peca IN (".implode(", ", $nova_peca).")";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                            ({$os}, $auditoria_status, 'OS em intervenção de Peças')";
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

function grava_os_produto_fabrica()
{
    global $campos;

    if (empty($campos["produto"]["id"])) {
        return array();
    }

    return array(
        "produto" => $campos["produto"]["id"]
    );
}

$auditorias = array(
	"auditoria_peca_critica",
    "auditoria_peca_elgin_automacao",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"auditoria_km_elgin_automacao",
	"auditoria_visita_elgin_automacao",
	"auditoria_produto_sem_numero_serie_elgin_automacao",
);

