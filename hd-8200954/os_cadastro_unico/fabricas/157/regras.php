<?php

/**
 * Array de regras padrões
 */
$regras["os|defeito_reclamado"]["obrigatorio"] = obrigatoriedade_defeito_reclamado($_POST["os"]["tipo_atendimento"], $login_fabrica);

$regras["consumidor|cpf"]["obrigatorio"] = true;
$regras["consumidor|celular"]["obrigatorio"] = true; 
$grava_defeito_peca  = true;

//$anexos_obrigatorios[] = "notafiscal";
$valida_anexo_boxuploader = "valida_anexo_boxuploader";

if($_POST["os"]["consumidor_revenda"] == "R"){

    $regras["consumidor|nome"]["obrigatorio"]    = false; 
    $regras["consumidor|estado"]["obrigatorio"]  = false; 
    $regras["consumidor|cidade"]["obrigatorio"]  = false; 
    $regras["consumidor|cpf"]["obrigatorio"]  = false; 
    $regras["consumidor|celular"]["obrigatorio"] = false; 

}
$regras["produto|defeito_constatado"]["function"] = array(
    "valida_produto_defeito_constatado_wap",
);

//$pre_funcoes_fabrica = array("anexo_pecas_wap");
$funcoes_fabrica = array("valida_defeito_constatado_peca_wap", "anexo_pecas_wap");

function valida_defeito_constatado_peca_wap() {
    global $con, $login_fabrica, $campos, $msg_erro, $areaAdmin, $os;

    $defeitos_constatados_informados = explode(",", $campos['produto']['defeitos_constatados_multiplos']);

    if (!$areaAdmin && count($defeitos_constatados_informados) > 0) {

        if (count($defeitos_constatados_informados) > 2 && empty($os)) {

            throw new Exception(traduz("Limite máximo de defeitos constatados excedido"));

        }

        foreach ($campos['produto_pecas'] as $chave => $referencia) {
            if (!empty($referencia['referencia'])) {
                $referencias_inseridas[] = $referencia['referencia'];
            }
        }

        $referencias_pecas               = implode("','",$referencias_inseridas);

        foreach ($defeitos_constatados_informados as $defeito_constatado) {

            if (!empty($defeito_constatado)) {
                $sql = "SELECT peca_defeito_constatado
                        FROM tbl_peca_defeito_constatado
                        WHERE fabrica = {$login_fabrica}
                        AND defeito_constatado = {$defeito_constatado}
                        AND peca IN (SELECT peca 
                                    FROM tbl_peca 
                                    WHERE referencia IN ('{$referencias_pecas}')
                                    AND fabrica = {$login_fabrica})";
                $res = pg_query($con, $sql);
                
                if (pg_num_rows($res) == 0) {

                    $sql_desc_defeito = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = {$defeito_constatado}";
                    $res_desc_defeito = pg_query($con, $sql_desc_defeito);

                    throw new Exception("Insira as peças do defeito ".pg_fetch_result($res_desc_defeito, 0, 'descricao').", ou remova o mesmo antes de gravar. Caso encontrar problemas para prosseguir, favor entrar em contato com o fabricante.");

                }
            }

        }

    }

}

/**
 * Função chamada na valida_campos()
 *
 * Função para validar a amarração do defeito constatado com a famí­lia do produto
 */
function valida_produto_defeito_constatado_wap() {
    global $con, $login_fabrica, $campos, $defeitoConstatadoMultiplo;

    $produto = $campos["produto"]["id"];
    $defeitos_constatados = array();
    $data_abertura = formata_data($campos["os"]["data_abertura"]);

    if (isset($defeitoConstatadoMultiplo)) {
        $defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
    } else {
        $defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
    }
    
    if (!empty($produto) && count($defeitos_constatados) > 0 && strtotime($data_abertura) > strtotime('2019-12-18')) {
        foreach($defeitos_constatados as $defeito_constatado) {
            if(strlen($defeito_constatado)>0){
                $sql = "SELECT *
                        FROM tbl_diagnostico
                        JOIN tbl_diagnostico_produto ON tbl_diagnostico_produto.diagnostico = tbl_diagnostico.diagnostico
                        WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                        AND tbl_diagnostico.defeito_constatado = {$defeito_constatado}
                        AND tbl_diagnostico_produto.produto = {$produto}";
                $res = pg_query($con, $sql);

                if (!pg_num_rows($res)) {
                    throw new Exception("Defeito constatado não pertence ao produto");
                }
            }
        }
    }
}

$pre_funcoes_fabrica = array("valida_adicao_peca_wap");


/**
 * Função para validar a garantia da peça
 */
function valida_garantia_item_wap() {
    global $con, $login_fabrica, $campos, $msg_erro, $login_privilegios;

    $data_compra    = $campos["os"]["data_compra"];
    $data_abertura  = $campos["os"]["data_abertura"];
    $produto        = $campos["produto"]["id"];
    $pecas          = $campos["produto_pecas"];

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

            if (!empty($peca['id']) && !empty($data_compra) && !empty($data_abertura) && $gera_pedido == 't' && $login_privilegios <> "*") {
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

$valida_garantia_item = "valida_garantia_item_wap";


function valida_adicao_peca_wap() {

    global $con, $campos, $os, $login_fabrica, $login_posto;

    $pecas = $campos['produto_pecas'];

	$login_posto = !empty($login_posto) ? $login_posto : $campos['posto']['id'];
    $sql = " SELECT item_aparencia 
                    FROM tbl_posto_fabrica 
                    WHERE posto = {$login_posto}
                    AND fabrica = {$login_fabrica}";

    $resQueryPosto = pg_query($con, $sql);

    $itemAparenciaPosto = pg_fetch_result($resQueryPosto, 0, 'item_aparencia'); 

    foreach ($pecas as $key => $peca) {

        if (!empty($peca['id'])) {

            $queryPeca = " SELECT item_aparencia, referencia 
                           FROM tbl_peca 
                           WHERE peca = {$peca['id']}";
               
            $resQueryPeca = pg_query($con, $queryPeca);

            $itemAparenciaPeca = pg_fetch_result($resQueryPeca, 0, 'item_aparencia'); 
            
            $itemAparenciaReferencia = pg_fetch_result($resQueryPeca, 0, 'referencia'); 
                
            if ($itemAparenciaPeca == 't') {
                
                if ($campos['os']['consumidor_revenda'] == "R") {
                        
                    if ($itemAparenciaPosto == 'f') {

                        throw new Exception("Posto não autorizado a lançar peça {$itemAparenciaReferencia} do tipo 'Item de Aparência'.");
                    } 

                } else {

                    throw new Exception("Tipo de Peça {$itemAparenciaReferencia} 'Item de Aparência' não é permitido para OS - Consumidor");
                }

            }
        }
    }

} 


function obrigatoriedade_defeito_reclamado($tipo_atendimento, $login_fabrica){
	global $con;
	
	if(empty($tipo_atendimento)){
		return true;
	}else{
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND entrega_tecnica IS TRUE";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			return false;
		}else{
			return true;
		}
	}
}

function auditoria_numero_serie_bloqueado_wap() {

    global $con, $campos, $os, $login_fabrica;

    $produto = $campos['produto']['id'];
    $serie   = $campos['produto']['serie'];

    if (!empty($serie) && filter_var($campos['produto']['serie'], FILTER_SANITIZE_NUMBER_INT) !== '') {
        $sql = "SELECT serie_controle FROM tbl_serie_controle WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$serie}';";
        // $sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$serie}';";
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
                }

            }

        }

    } else {
        throw new Exception("Número de série não informado");
    }

}

function auditoria_os_reincidente_numero_serie_wap(){
    global $con, $campos, $os, $login_fabrica, $posto, $os_reincidente, $os_reincidente_numero;

    if(!empty($os) && filter_var($campos['produto']['serie'], FILTER_SANITIZE_NUMBER_INT) !== '') {
        $idposto = $posto["id"];

        $sql = "SELECT tbl_os.os FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '1 year')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.os < {$os}
                AND tbl_os.posto = $idposto
                AND tbl_os_produto.serie = '{$campos['produto']['serie']}'
            ORDER BY tbl_os.data_abertura DESC LIMIT 1";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't' AND tbl_auditoria_os.observacao = 'Auditoria de OS Reincidente por Número de Série'", $os) === true){
            $os_reincidente_numero = pg_fetch_result($res, 0, "os");

            if(verifica_os_reincidente_finalizada($os_reincidente_numero) === true){
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca["resultado"]){
                    $auditoria = $busca["auditoria"];

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'Auditoria de OS Reincidente por Número de Série')";
                    pg_query($con, $sql);

                    if(strlen(pg_last_error()) > 0){
                        throw new Exception("Erro ao criar auditoria de reincidente por número de série para a OS");
                        
                    }else{
                        $os_reincidente = true;
                    }
                }else{
                    throw new Exception("Erro ao buscar auditoria reincidente por número de série");
                    
                }
            }
        }
    }
}

function auditoria_os_reincidente_wap(){
    global $con, $campos, $os, $login_fabrica, $posto, $os_reincidente, $os_reincidente_numero;

    if(!empty($os) && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't' AND tbl_auditoria_os.observacao = 'Auditoria de OS Reincidente por Número de Série'", $os) === true){
        $idposto = $posto["id"];
        $cpf     = str_replace(".","",$campos["consumidor"]["cpf"]);
        $cpf     = str_replace("-","",$cpf);

        $sql = "SELECT tbl_os.os FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '1 year')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.os < {$os}
                AND tbl_os.posto = $idposto
                AND tbl_os.consumidor_cpf = '{$cpf}'
                AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
                AND tbl_os_produto.produto = {$campos['produto']['id']}
                AND (
                    SELECT COUNT(*)
                    FROM tbl_os osr
                    WHERE osr.os_numero != tbl_os.os_numero
                    AND tbl_os.os = osr.os
                ) > 0
            ORDER BY tbl_os.data_abertura DESC LIMIT 1";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't' AND tbl_auditoria_os.observacao = 'Auditoria de OS Reincidente por Consumidor, Nota Fiscal e Produto'", $os) === true){
            $os_reincidente_numero = pg_fetch_result($res, 0, "os");

            if(verifica_os_reincidente_finalizada($os_reincidente_numero) === true){
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca["resultado"]){
                    $auditoria = $busca["auditoria"];

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'Auditoria de OS Reincidente por Consumidor, Nota Fiscal e Produto')";
                    pg_query($con, $sql);

                    if(strlen(pg_last_error()) > 0){
                        throw new Exception("Erro ao criar auditoria de reincidente para a OS");
                        
                    }else{
                        $os_reincidente = true;
                    }
                }else{
                    throw new Exception("Erro ao buscar auditoria reincidente");

                }
            }
        }
    }
}

function auditoria_km_wap(){
    global $con, $campos, $os, $login_fabrica;

    if(!empty($campos["os"]["qtde_km"]) && $campos["os"]["qtde_km"] !== "0.00" && verifica_auditoria_unica("tbl_auditoria_status.km = 't'", $os) === true){
        $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

        if($busca["resultado"]){
            $auditoria = $busca["auditoria"];

            if($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]){
                $observacao = "Auditoria de KM, KM alterado manualmente";
            }else{
                $observacao = "Auditoria de KM";
            }

            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, '$observacao')";
            pg_query($con,$sql);

            if(strlen(pg_last_error()) > 0){
                throw new Exception("Erro ao criar auditoria de Km para a OS");
                
            }else{
                return true;
            }
        }else{
            throw new Exception("Erro ao buscar auditoria de km");

        }
    }
}

function auditoria_aplicacao_indevida_wap(){
    global $con, $campos, $os, $login_fabrica;

    $cpfLimpo = str_replace(array(".","/","-"),array("","",""),$campos["consumidor"]["cpf"]);

    if(!empty($os) && strlen($cpfLimpo) == "14"){
        $sql = "SELECT tbl_produto.linha, tbl_linha.auditoria_os FROM tbl_produto
                INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.auditoria_os IS TRUE AND tbl_linha.fabrica = {$login_fabrica}
            WHERE tbl_produto.produto = {$campos['produto']['id']} AND tbl_produto.fabrica_i = {$login_fabrica}";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Aplicação Indevida'", $os) === true){
                $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");
                
                if($busca["resultado"]){
                    $auditoria = $busca["auditoria"];

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Aplicação Indevida')";
                    pg_query($con,$sql);

                    if(strlen(pg_last_error()) > 0){
                        throw new Exception("Erro ao criar auditoria de aplicação indevida para a OS");

                    }else{
                        return true;
                    }
                }else{
                    throw new Exception("Erro ao buscar auditoria aplicação indevida");

                }
            }
        }
    }
}

function auditoria_foto_peca_wap(){
    global $con, $campos, $os, $login_fabrica;

    $produto = $campos["produto"]["id"]." ";
    $pecas   = $campos["produto_pecas"];

    if (!empty($produto)) {
        $entra_auditoria = false;

        foreach ($pecas as $key => $peca) {
            if (empty($peca["id"])) {
                continue;
            }

            if (verifica_peca_anexo($peca["id"])) {

                $sql = "SELECT tbl_auditoria_os.auditoria_os, 
                        tbl_auditoria_os.liberada, 
                        tbl_auditoria_os.reprovada 
                    FROM tbl_auditoria_os 
                        INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status 
                    WHERE tbl_auditoria_os.os = $os 
                        AND observacao = 'OS em Auditoria de Foto de Peça'
                    ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $liberada = pg_fetch_result($res, 0, "liberada");
                    $reprovada = pg_fetch_result($res, 0, "reprovada");

                    if(empty($liberada) && !empty($reprovada)){
                        $entra_auditoria = true;
                    }
                }else{
                    $entra_auditoria = true;
                }
            }
        }

        if($entra_auditoria){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Foto de Peça')";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de foto de peça para a OS");
                    
                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria de foto da peça");

            }
        }
    }
}

function auditoria_valor_adicional(){
    global $con, $campos, $os, $login_fabrica;

    if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){
        
        if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais'", $os) === true){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais')";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de aplicação indevida para a OS");

                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria aplicação indevida");

            }
        }
    }
}

function auditoria_lancamento_peca_wap(){

    global $con, $campos, $os, $login_fabrica, $areaAdmin, $defeitoConstatadoMultiplo;

    if(strlen($os) > 0 && verifica_peca_lancada(false) === true){

        $pecas = $campos["produto_pecas"];

        $qtde_pecas = 0; 
        unset($array_campos_adicionais);
        foreach ($pecas as $key => $value) {
            
            if(is_numeric($key) && strlen($value["id"]) > 0){
                $qtde_pecas++;
                $array_campos_adicionais[] = (int)$value["id"];
            }

        }

        if($qtde_pecas > 0){

            if (isset($defeitoConstatadoMultiplo)) {
                $defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
            } else {
                $defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
            }

			if (count($defeitos_constatados) == 0) {
				throw new Exception("Para lançar peças é necessário informar o defeito constatado");
			}

            $grava_auditoria = false;
            $sql_campos_adicionais = "SELECT DISTINCT jsonb_array_elements(campos_adicionais->'peca') AS peca 
                                      FROM tbl_auditoria_os 
                                      WHERE os = $os ";
            $res_campos_adicionais = pg_query($con, $sql_campos_adicionais);
            if (pg_num_rows($res_campos_adicionais) > 0) { 
                for ($s=0; $s < pg_num_rows($res_campos_adicionais); $s++) { 
                    $array_campos_adicionais_auditoria[] = pg_fetch_result($res_campos_adicionais, $s, 'peca');
                }
                foreach ($array_campos_adicionais as $peca_array) {
                    if (!in_array($peca_array,$array_campos_adicionais_auditoria)) {
                        $grava_auditoria = true;
                    }
                }
            } else {
                $grava_auditoria = true;
            }

            if ($grava_auditoria) {
                $pecas_id['peca'] = $array_campos_adicionais;
                $campos_adicionais_peca = json_encode($pecas_id);

                $auditoria = 4;

    				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, campos_adicionais) VALUES ($os, $auditoria, 'OS em Auditoria de Lançamento de Peças', '$campos_adicionais_peca')";
    				pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de laçamento de peça para a OS");
                    
                }else{
                    return true;
                }
            }
        }

    }

}

/**
 * Função para validar anexo
 */
function valida_anexo_wap() {
    
    global $campos, $msg_erro;

    $count_anexo = array();

    if($campos["os"]["consumidor_revenda"] != "R"){

        foreach ($campos["anexo"] as $key => $value) {
            if (strlen($value) > 0) {
                $count_anexo[] = "ok";
            }
        }
        // echo count($count_anexo); exit;
        if(count($count_anexo) < 2){
            $msg_erro["msg"][] = "É obrigatório os anexos da nota fiscal e foto do produto";
        }

    }

}

function grava_anexo_peca_wap() {
    global $campos, $os, $login_fabrica, $con;

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

            $sql = "SELECT tbl_peca.parametros_adicionais FROM tbl_os_item 
                    JOIN tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca  AND tbl_peca.fabrica = {$login_fabrica}
                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os AND tbl_os.fabrica   = {$login_fabrica}
                WHERE tbl_os.os = {$os}
                AND tbl_peca.peca = {$peca_anexo->peca} ";
                //die(nl2br($sql));
            $resPeca = pg_query($con,$sql);
         
            if(pg_num_rows($resPeca) > 0){
                $array_parametros_adicionais = json_decode(pg_fetch_result($resPeca, 0, "parametros_adicionais"), true);

                if(isset($array_parametros_adicionais["qtde_anexos"]) && $array_parametros_adicionais["qtde_anexos"] > 0){
                    $qtde_max_anexos_peca = $array_parametros_adicionais["qtde_anexos"];
                }
            }   

            if (verifica_peca_anexo($peca_anexo->peca, $qtde_max_anexos_peca)) {

                for ($i=0; $i < $qtde_max_anexos_peca; $i++) {

                    $anexo    = $grava_anexos[$peca_anexo->produto][$peca_anexo->peca][$i];
                    $anexo_s3 = $grava_anexos_s3[$peca_anexo->produto][$peca_anexo->peca][$i];
                        
                    if(empty($anexo)){
                        throw new Exception("Obrigatório anexar todos os anexos de peças.");
                    }

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


function anexo_pecas_wap() {
    global $os, $login_fabrica, $con, $produto, $campos;

    $anexo_chave = $campos["anexo_chave"];


	$qtde_max_anexos_peca = 0;

	$sqlPeca = "SELECT tbl_peca.referencia, tbl_peca.parametros_adicionais FROM tbl_os_item 
					JOIN tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca  AND tbl_peca.fabrica = {$login_fabrica}
					JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os AND tbl_os.fabrica   = {$login_fabrica}                            
					AND tbl_os.os = {$os}";

	//die(nl2br($sqlPeca));
	$resPeca = pg_query($con,$sqlPeca);
 
	if(pg_num_rows($resPeca) > 0){                
		$contador_linha_resPeca = pg_num_rows($resPeca);
		for($x=0; $x<$contador_linha_resPeca; $x++){
			$array_parametros_adicionais = json_decode(pg_fetch_result($resPeca, $x, parametros_adicionais), true);
			$referencia_peca = pg_fetch_result($resPeca, $x, referencia);

			if(isset($array_parametros_adicionais["qtde_anexos"]) && $array_parametros_adicionais["qtde_anexos"] > 0){
				$qtde_max_anexos_peca = $array_parametros_adicionais["qtde_anexos"];
				$contador_qtde_max_anexos_peca += $qtde_max_anexos_peca;
			}

			if(!empty($os) and empty($anexo_chave)){
				$cond_chave_os = " AND referencia_id = {$os} ";
			} else {
				$cond_chave_os = " AND hash_temp = '{$anexo_chave}' ";
			}   

			$sqlTdocs = "SELECT obs
						FROM tbl_tdocs
						WHERE fabrica = {$login_fabrica}
						AND contexto = 'os'
						AND situacao='ativo'
						{$cond_chave_os}";
		   
			//die(nl2br($sqlTdocs));
			$resTdocs = pg_query($con,$sqlTdocs);                
			$contadorTdocs = pg_num_rows($resTdocs);     
			$total = 0;
			if($contadorTdocs > 0){            
				for($y=0; $y<$contadorTdocs; $y++){
					$array_obs = json_decode(pg_fetch_result($resTdocs, $y, 'obs'), true);                                
					if($array_obs[0]['typeId'] == 'peca'){
						$contador_anexo_tdocs = $contador_anexo_tdocs + 1;                    
					}                    
				}
				$total = $contador_qtde_max_anexos_peca - $contador_anexo_tdocs;               
			}

			if($total > 0){
				throw new Exception("Obrigatório anexar todos os anexos de peças.<br>Faltam $total anexos da peça com referência $referencia_peca.");
				break;
			} 
		}                
	} else {
		$contador_qtde_max_anexos_peca = 0;
	}


}

/**
 * Função para validar a garantia do produto
 */
function valida_garantia_wap($boolean = false) {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];
    $serie         = $campos["produto"]["serie"];
    $cpf           = $campos["consumidor"]["cpf"];

    $cpf = str_replace(array(".", "-"), "", $cpf);

    if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {

        $sqlGaranEstendida = "SELECT garantia_mes, data_compra 
                                FROM tbl_cliente_garantia_estendida 
                                WHERE produto = $produto 
                                and fabrica = $login_fabrica 
                                and numero_serie = '$serie' 
                                and cpf = '$cpf' ";
        $resGaranEstendida = pg_query($con, $sqlGaranEstendida);

        if(pg_num_rows($resGaranEstendida)>0){
            $data_venda     = pg_fetch_result($resGaranEstendida, 0, data_compra);
            $garantia_mes   = pg_fetch_result($resGaranEstendida, 0, garantia_mes);
        }

        $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $garantia = pg_fetch_result($res, 0, "garantia");

            //nessa parte assumir os valores da garantia estendida hd-6019022
            if(strlen(trim($data_venda))>0 and strlen(trim($garantia_mes))>0){
                $garantia = $garantia_mes;
                $data_compra = mostra_data($data_venda);
            }            

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

$valida_garantia = "valida_garantia_wap";

if($_POST["os"]["consumidor_revenda"] == "R"){
    $valida_anexo = "valida_anexo_wap";    
}

//$valida_anexo_peca = "anexo_pecas_wap";

//$grava_anexo_peca = "grava_anexo_peca_wap";

$auditorias = array(
    /* "auditoria_km_wap", */
    "auditoria_peca_critica",
    "auditoria_lancamento_peca_wap",
    "auditoria_os_reincidente_numero_serie_wap",
    "auditoria_os_reincidente_wap"
);

?>
