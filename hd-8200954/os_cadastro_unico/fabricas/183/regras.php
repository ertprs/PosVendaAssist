<?php
//$grava_defeito_peca  = true;
$auditoria_bloqueia_pedido = "true";
$antes_valida_campos = "antes_valida_campos";

$regras["revenda|nome"]["obrigatorio"] = false;
$regras["revenda|cnpj"]["obrigatorio"] = false;
$regras["revenda|cep"]["obrigatorio"] = false;
$regras["revenda|estado"]["obrigatorio"] = false;
$regras["revenda|cidade"]["obrigatorio"] = false;
$regras["revenda|bairro"]["obrigatorio"] = false;
$regras["revenda|endereco"]["obrigatorio"] = false;
$regras["revenda|numero"]["obrigatorio"] = false;
$regras["revenda|complemento"]["obrigatorio"] = false;
$regras["revenda|telefone"]["obrigatorio"] = false;

if (!$areaAdmin){
    $regras["produto|defeitos_constatados_multiplos"] = array(
        "function" => array("valida_defeito_constatado")
    );
}

if (!$areaAdmin){
    if (strlen(trim(getValue("produto[defeitos_constatados_multiplos]"))) > 0 ) {
        $regras["produto|solucao"]["obrigatorio"] = true;
    }

    if (strlen(trim(getValue("produto[defeitos_constatados_multiplos]"))) > 0 ) {
    	$regras["produto|serie"] = array(
    	    "obrigatorio" => true
    	);
    }
}

if ($areaAdmin){
    $regras["produto|serie"] = array(
        "obrigatorio" => false
    );
}



$funcoes_fabrica = array(
    "grava_solucao_itatiaia",
    "valida_estoque_posto_itatiaia",
);

$auditorias = array(
    "auditoria_peca_critica_itatiaia",
    "auditoria_pecas_excedentes_itatiaia",
    "auditoria_os_reincidente_itatiaia",
    "auditoria_numero_de_serie_itatiaia",
    "auditoria_km_itatiaia",
    "auditoria_numero_serie_bloqueado_itatiaia",
    "auditoria_fabrica_itatiaia",
);

if (verifica_tipo_atendimento() == 'Cortesia'){
    $valida_garantia = "";
} 

function verifica_tipo_atendimento() {
    global $con, $login_fabrica;

    if (getValue("os[tipo_atendimento]")) {
        $sqlTipo = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento=".getValue("os[tipo_atendimento]");
        $resTipo = pg_query($con, $sqlTipo);
        $descricaoTipo = pg_fetch_result($resTipo, 0, 'descricao');
        return $descricaoTipo;
    }

}
function antes_valida_campos() {
    global $campos, $con, $login_fabrica, $msg_erro, $os, $areaAdmin;
    
    $peca_sem_preco = false;

    if (!empty($os)){
        if (!$areaAdmin){
            $sql_tta = "
                SELECT 
                    tta.tecnico_agenda,
                    o.hd_chamado
                FROM tbl_os o
                LEFT JOIN tbl_tecnico_agenda tta ON tta.os = o.os AND tta.fabrica = $login_fabrica
                WHERE o.os = $os
                AND o.fabrica = $login_fabrica";
            $res_tta = pg_query($con, $sql_tta);
            if (pg_num_rows($res_tta) > 0){
                $tecnico_agenda = pg_fetch_result($res_tta, 0, "tecnico_agenda");
                $hd_chamado = pg_fetch_result($res_tta, 0, "hd_chamado");
                
                if (!empty($hd_chamado) AND empty($tecnico_agenda)){
                    $msg_erro["msg"][] = "Não é possível fazer alteração na Ordem de Serviço sem realizar um agendamento.";
                }
            }
        }
    }

    $produto_pecas = $campos["produto_pecas"];

    foreach ($produto_pecas as $key => $peca) {
        $peca_id = $peca["id"];

        if (!empty($peca_id)){
            if (empty($peca["codigo_utilizacao"])) {
                $msg_erro["msg"][] = traduz("Preencha o código de utilização da peça")." ".$peca["referencia"];
                $msg_erro["campos"][] = "produto_pecas[$key]";
            }

            $sql = "
                SELECT tbl_tabela_item.preco 
                FROM tbl_tabela JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela 
                WHERE tbl_tabela.fabrica = {$login_fabrica} AND tbl_tabela_item.peca = {$peca_id}
                AND tbl_tabela.tabela_garantia IS TRUE ";
            $res = pg_query($con, $sql);

            $preco_peca = pg_fetch_result($res, 0, "preco");

            if (strlen(trim($preco_peca)) == 0){
                $peca_sem_preco = true;
            }
        }
    }

    if ($peca_sem_preco == true){
        auditoria_peca_sem_preco();
    }
}

function valida_defeito_constatado(){
    global $con, $campos, $login_fabrica, $msg_erro;

    if(strlen(trim($campos['produto']['defeitos_constatados_multiplos'])) == 0) {
        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "produto[defeito_constatado]";
    }
}

function grava_solucao_itatiaia() {
	global $con, $os, $campos, $login_fabrica, $areaAdmin;
	
    if (!$areaAdmin){
    	$solucao = $campos["produto"]["solucao"];

    	if (!strlen($solucao)) {
    		throw new Exception("Erro ao gravar solução");
    	}

        $sqlSolucao = "SELECT parametros_adicionais FROM tbl_solucao WHERE fabrica = $login_fabrica AND solucao = $solucao";
        $resSolucao = pg_query($con, $sqlSolucao);
        if (strlen(pg_last_error()) > 0 || pg_num_rows($resSolucao) == 0) {
            throw new Exception("Erro ao gravar solução");
        }  
        $parametrosAdd = json_decode(pg_fetch_result($resSolucao, 0, 'parametros_adicionais'), 1);

        $campoMO = "";
        if (isset($parametrosAdd["mao_de_obra"]) && strlen($parametrosAdd["mao_de_obra"]) > 0) {
            $campoMO = ",mao_de_obra='".$parametrosAdd["mao_de_obra"]."'";
        }

    	$sql = "UPDATE tbl_os SET solucao_os = {$solucao} $campoMO WHERE os = {$os}";
    	$res = pg_query($con, $sql);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro ao gravar solução");
    	}
    }
}

function valida_estoque_posto_itatiaia(){

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
                                $novo_servico_realizado = buscaServicoRealizadoItatiaia("gera_pedido");
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            }
                        }
                    } else {
                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                        if(!$status_estoque){
                            $novo_servico_realizado = buscaServicoRealizadoItatiaia("gera_pedido");
        
                            if(!empty($novo_servico_realizado)){
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                            throw new Exception("O posto não tem estoque suficiente para a Peça {$peca_referencia}");
                            }
                        }else{
                            $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                        }
                    }
                } else {

                    $status_exclusao = $Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);
                    $status_servico = $Os->verificaServicoGeraPedido($servico);

                    if($status_servico == true){
                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                        if($status_estoque == true){
                            $novo_servico_realizado = buscaServicoRealizadoItatiaia("estoque");
                            if(!empty($novo_servico_realizado)){
                                $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }
                        }
                    }
                }
            }
        }
    }
}

function buscaServicoRealizadoItatiaia($tipo) {
    global $login_fabrica, $con;

    switch($tipo){
    
        case "gera_pedido"   :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS NOT TRUE  AND gera_pedido IS TRUE"; break;
        case "estoque"       :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS TRUE  AND gera_pedido IS NOT TRUE"; break;
        case "troca_produto" :  $cond = " AND troca_produto IS TRUE  AND gera_pedido IS TRUE"; break;

    }

    $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE $cond";
    $query = pg_query($con, $sql);
    $res = pg_fetch_all($query);
    return (is_array($res) && count($res) > 0) ? $res[0]['servico_realizado'] : false;
}


function valida_anexo_itatiaia() {
	global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $areaAdmin;

	$valor_adicional = $campos['os']['valor_adicional'];

	if ($fabricaFileUploadOS) {
	    $anexo_chave = $campos["anexo_chave"];
 
	    if (!empty($anexo_chave)){
            if (!empty($os)){
                $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";
            }else{
                $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";
            }
			$sql_tdocs = "
                SELECT json_field('typeId',obs) AS typeId 
                FROM tbl_tdocs 
                WHERE tbl_tdocs.fabrica = $login_fabrica
                AND tbl_tdocs.situacao = 'ativo'
                $cond_tdocs";
            $res_tdocs = pg_query($con,$sql_tdocs);
	 
			if (pg_num_rows($res_tdocs) > 0){
	 
                $typeId = pg_fetch_all_columns($res_tdocs);
     
                // if (!in_array('assinatura', $typeId) AND $areaAdmin != true) {
                //     throw new Exception(traduz("Obrigatório anexar: O.S. Assinada"));
                // }
        
                $sqlT = "
                    SELECT json_field('typeId',obs) AS typeId
                    FROM tbl_tdocs 
                    WHERE tbl_tdocs.fabrica = $login_fabrica
                    AND tbl_tdocs.situacao = 'ativo'
                    AND json_field('typeId',obs) = 'valoradicional'
                    $cond_tdocs";
                $resT = pg_query($con,$sqlT);

                if (pg_num_rows($resT) == 0 AND count($valor_adicional) > 0) {
                    throw new Exception(traduz("Obrigatório anexar: Comprovante de Valores Adicionais"));
                }
    		}else{
				if (count($valor_adicional) > 0) {
                    throw new Exception(traduz("Obrigatório o seguinte anexo: Valores Adicionais"));
			    }
            }
		}else{
            if (count($valor_adicional) > 0) {
		 	    throw new Exception(traduz("Obrigatório os seguintes anexos: Valores Adicionais"));
	        }
        }
	}
}

$valida_anexo = "valida_anexo_itatiaia";
function grava_os_extra_fabrica(){
    global $campos;

    return array(
        "obs" => "'".$campos["consumidor"]["ponto_referencia"]."'"
    );
}

function auditoria_pecas_excedentes_itatiaia(){
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;

    if(verifica_peca_lancada() === true){
        $sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
        $res = pg_query($con, $sql);

        $qtde_pecas_intervencao = pg_fetch_result($res, 0, "qtde_pecas_intervencao");

        if(!strlen($qtde_pecas_intervencao)){
            $qtde_pecas_intervencao = 0;
        }

        if ($qtde_pecas_intervencao > 0) {

            $sql = "
                SELECT
                    COUNT(tbl_os_item.os_item) AS qtde_pecas
                FROM tbl_os_item
                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os_produto.os = {$os}
                AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE;
            ";

            $res = pg_query($con, $sql);
            if(pg_num_rows($res) > 0){
                $qtde_pecas = pg_fetch_result($res, 0, "qtde_pecas");
            }else{
                $qtde_pecas = 0;
            }

            if ($qtde_pecas > $qtde_pecas_intervencao) {
                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'")) {
                    
                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de peças excedentes', {$auditoria_bloqueia_pedido});
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD002");
                    }
                }
            }
        }
    }
}

function auditoria_peca_sem_preco(){
    global $con, $os, $login_fabrica, $qtde_pecas, $auditoria_bloqueia_pedido;
    
    $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

    if($busca['resultado']){
        $auditoria_status = $busca['auditoria'];
    }

    if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%sem pre%' AND tbl_auditoria_os.liberada IS NULL", $os) === true) {
        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça sem preço', {$auditoria_bloqueia_pedido});
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD002");
        }
    }
}

function auditoria_peca_critica_itatiaia(){
    global $con, $os, $login_fabrica, $qtde_pecas, $auditoria_bloqueia_pedido;
    $sql = "
        SELECT
            tbl_os_item.os_item
        FROM tbl_os_item
        JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
        JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os_produto.os = {$os}
        AND tbl_peca.peca_critica IS TRUE;
    ";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'", $os) === true) {

            $sql = "
                INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', {$auditoria_bloqueia_pedido});
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #AUD002");
            }

        } else if (aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'") && verifica_peca_lancada() === true) {
            $nova_peca = pegar_peca_lancada();

            if(count($nova_peca) > 0){
                $sql = "
                    SELECT
                        tbl_os_item.os_item
                    FROM tbl_os_item
                    JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
                    JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                    JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os_produto.os = {$os}
                    AND tbl_peca.peca_critica IS TRUE
                    AND tbl_peca.peca IN (".implode(", ", $nova_peca).");
                ";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', {$auditoria_bloqueia_pedido});";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD003");
                    }
                }
            }
        }
    }
}

function auditoria_os_reincidente_itatiaia(){
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
	if(pg_num_rows($res) > 0 && strlen($campos['produto']['serie']) > 0 && strlen($campos['produto']['id']) > 0){

		$select = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.os < {$os}
				AND tbl_os.posto = $posto
				AND tbl_os.serie =  '{$campos['produto']['serie']}'
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $select);

		if (pg_num_rows($resSelect) > 0) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			$insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ({$os}, 1 , 'OS Reincidente por Número de Série, Produto e Posto')";
			$resInsert = pg_query($con, $insert);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			} else {
				$os_reincidente = true;
			}
		}
	}
}

function auditoria_numero_de_serie_itatiaia(){
    global $con, $campos, $login_fabrica, $os, $msg_erro, $_serverEnvironment;

    $produto            = $campos["produto"]["id"];
    $serie              = $campos["produto"]["serie"];
    $auditoria_status   = 5;
 	$ref_produto = ltrim($campos["produto"]["referencia"], "0");
    $ref_produto = explode("-", $ref_produto);
    $entra_auditoria = false;

    $conteudo = array(
        "Registros" => array(
            array("NumSerie" => $serie)
        )
    );

    $curl = curl_init();

    if ($_serverEnvironment == "development") {
        $curlUrl = "https://piqas.cozinhasitatiaia.com.br/RESTAdapter/BuscarSerial";
        $curlPass = "aXRhYWJhcDpBYmFwMjAxOA==";
    } else {
        $curlUrl = "https://pi.cozinhasitatiaia.com.br/RESTAdapter/BuscarSerial";
        $curlPass = "UElTVVBFUjppdGExMjM0NQ==";
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL => $curlUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => json_encode($conteudo),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic {$curlPass}",
            "Content-Type: application/json"
        ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    $response = json_decode($response,1);

    if (empty($response)){
    	$entra_auditoria = true;
    }else{
        $error = $response["MT_Telecontrol_BuscarSerial_response"]["Produto"]["DescRetorno"];

        $produto_retorno = $response["MT_Telecontrol_BuscarSerial_response"]["Produto"]["NomProduto"];
        $produto_retorno = ltrim($produto_retorno, "0");
        
        if ($ref_produto[0] != $produto_retorno){
        	$entra_auditoria = true;
        }
    }
    curl_close($curl);
    
    if ($entra_auditoria === true){
        $sql = "SELECT * FROM tbl_auditoria_os
                WHERE os = $os
				AND auditoria_status = $auditoria_status";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) == 0) {
            $sql = "INSERT INTO tbl_auditoria_os (
                        os,
                        auditoria_status,
                        observacao
                    ) VALUES (
                        {$os},
                        $auditoria_status,
                        'OS em Auditoria de Número de Série'
                    )";
            $res = pg_query($con, $sql);
        }
    }
}

function auditoria_numero_serie_bloqueado_itatiaia(){
    global $con, $campos, $os, $login_fabrica, $_serverEnvironment, $areaAdmin;

    if (!$areaAdmin){
        $produto = $campos['produto']['id'];
        $serie   = $campos['produto']['serie'];
        if (!empty($serie)) {
            $sql = "SELECT serie_controle FROM tbl_serie_controle WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$serie}';";
            $res = pg_query($con, $sql);

            $serie_controle = pg_fetch_result($res, 0, "serie_controle");

            if (strlen($serie_controle) > 0) {

                if (verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Número de Série bloqueado'", $os) === true) {
                    $busca = buscaAuditoria("tbl_auditoria_status.numero_serie = 't'");

                    if ($busca['resultado']) {
                        $auditoria_status = $busca['auditoria'];
                    }

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                                ({$os}, {$auditoria_status}, 'OS em Auditoria de Número de Série bloqueado');";

                    $res = pg_query($con,$sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    } else {
                        $os_numero_serie_bloqueado = true;

                        include_once (__DIR__ . '/../../../class/communicator.class.php');

                        $TcMail = new TcComm($GLOBALS['externalId'], $GLOBALS['externalEmail']);

                        $sql_admin = "SELECT email FROM tbl_admin WHERE fabrica = {$login_fabrica} AND privilegios = '*'";
                        $res_admin = pg_query($con, $sql_admin);
                        
                        if (pg_num_rows($res_admin) > 0){
                            $email_admin = pg_fetch_all_columns($res_admin);
                            $email_admin = implode(";", $email_admin);
                        }
                        
                        if ($_serverEnvironment == "development"){
                            $email_admin = "luis.carloss@telecontrol.com.br;guilherme.monteiro@telecontrol.com.br;";
                        }
                        
                        $assunto  = "Número de serie bloqueado - Número de Serie: $serie - Ordem de Serviço: $os";
                        $mensagem = "Prezado Fabricante <br/> Informamos que o Númedo de serie $serie da Ordem de Serviço: $os está bloqueado.";
                        $enviado  = $TcMail->sendMail($email_admin, $assunto, $mensagem, $externalEmail);
                    }
                }
            }
        } else {
            throw new Exception("Número de série não informado");
        }
    }
}

function auditoria_km_itatiaia(){
    global $con, $os, $login_fabrica, $campos;

    if (!strlen($campos["os"]["qtde_km_hidden"])) {
        $campos["os"]["qtde_km_hidden"] = $campos["os"]["qtde_km"];
    }

    $qtde_km = $campos["os"]["qtde_km"];
    $qtde_km_anterior = $campos["os"]["qtde_km_hidden"];
    $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");
    if($busca['resultado']){
        $auditoria_status = $busca['auditoria'];
    }

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND ta.fora_garantia IS NOT TRUE;
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {


        if (verifica_auditoria_unica("tbl_auditoria_status.km = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria de KM%'", $os) === true) {

            if ($qtde_km >= 250) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM', false);
                ";
            } elseif ($qtde_km > $qtde_km_anterior) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                ";
            }
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #AUD012");
            }
        } else {
            if ($qtde_km > $qtde_km_anterior) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                    ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD012");
                }
            }
        }
    }
}

function auditoria_fabrica_itatiaia(){
    global $con, $login_fabrica, $os, $campos, $tipo_atendimento_arr;

    if (count($campos["os"]["valor_adicional"]) > 0) {

        $auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($auditoria['resultado']){
            $auditoria_status = $auditoria['auditoria'];
        }
        $sql = "SELECT tbl_auditoria_os.os,
                       tbl_auditoria_os.auditoria_os,
                       tbl_auditoria_os.liberada,
                       tbl_auditoria_os.reprovada
                  FROM tbl_auditoria_os
                  JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
                 WHERE tbl_auditoria_os.os = {$os}
                   AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
                   AND tbl_auditoria_os.observacao ILIKE '%Auditoria de F%'";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0) {
            $sql = "INSERT INTO tbl_auditoria_os (
                                                    os,
                                                    auditoria_status,
                                                    observacao
                                                ) VALUES
                                                (
                                                    {$os},
                                                    $auditoria_status,
                                                    'Auditoria de Fábrica: Valores Adicionais'
                                                )";
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}
