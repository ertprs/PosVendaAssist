<?php
$regras["os|qtde_km"] = [];
if (strlen(getValue("os[consumidor_revenda]")) > 0 || strlen($consumidor_revenda) > 0) {
    if ((getValue("os[consumidor_revenda]") == 'C' || $consumidor_revenda == 'C') OR (getValue("os[consumidor_revenda]") == 'S' || $consumidor_revenda == 'S') ) {
        $regras["consumidor|cpf"]["obrigatorio"] = true;
        $regras["consumidor|cep"]["obrigatorio"] = true;
        $regras["consumidor|bairro"]["obrigatorio"] = true;
        $regras["consumidor|endereco"]["obrigatorio"] = true;
        $regras["consumidor|numero"]["obrigatorio"] = true;
        $regras["produto|marca_produto"]["obrigatorio"] = true;
        $regras["revenda|nome"]["obrigatorio"] = false;
        $regras["revenda|cnpj"]["obrigatorio"] = false;
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;
        $regras["revenda|telefone"]["obrigatorio"] = false;
        $regras["revenda|cep"]["obrigatorio"] = false;

        if (getValue("os[consumidor_revenda]") == 'S' || $consumidor_revenda == 'S'){
            $regras["os|nota_fiscal"]["obrigatorio"] = false;
	        $regras["os|data_compra"]["obrigatorio"] = false;
        }
        
    } else {
        $regras["os|nota_fiscal"]["obrigatorio"] = false;
        $regras["os|data_compra"]["obrigatorio"] = false;
        $regras["os|defeito_reclamado"]["obrigatorio"] = false;
        $regras["consumidor|nome"]["obrigatorio"] = false;
        $regras["consumidor|estado"]["obrigatorio"] = false;
        $regras["consumidor|cidade"]["obrigatorio"] = false;
        $regras["consumidor|cpf"]["obrigatorio"] = false;
        $regras["consumidor|cep"]["obrigatorio"] = false;
        $regras["consumidor|bairro"]["obrigatorio"] = false;
        $regras["consumidor|endereco"]["obrigatorio"] = false;
        $regras["consumidor|numero"]["obrigatorio"] = false;
        $regras["produto|marca_produto"]["obrigatorio"] = true;
        $regras["revenda|nome"]["obrigatorio"] = false;
        $regras["revenda|cnpj"]["obrigatorio"] = false;
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;
        $regras["revenda|telefone"]["obrigatorio"] = false;
        $regras["revenda|cep"]["obrigatorio"] = false;
    }
} else {
    $regras["consumidor|cpf"]["obrigatorio"] = true;
    $regras["consumidor|cep"]["obrigatorio"] = true;
    $regras["consumidor|bairro"]["obrigatorio"] = true;
    $regras["consumidor|endereco"]["obrigatorio"] = true;
    $regras["consumidor|numero"]["obrigatorio"] = true;
    $regras["produto|marca_produto"]["obrigatorio"] = true;
    $regras["revenda|nome"]["obrigatorio"] = false;
    $regras["revenda|cnpj"]["obrigatorio"] = false;
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
    $regras["revenda|telefone"]["obrigatorio"] = false;
    $regras["revenda|cep"]["obrigatorio"] = false;
}

$auditorias = array(
    "auditoria_os_reincidente_roca",
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria",
    "auditoria_pecas_excedentes",
    "auditoria_peca_sem_preco_roca",
    "auditoria_solicitacao_troca_produto_roca",
    "auditoria_servico_realizado"
);

$funcoes_fabrica = array(
    "grava_os_campo_extra_fabrica",
    "verifica_estoque_peca_roca"
);

function valida_garantia_roca($boolean = false) {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_compra      = $campos["os"]["data_compra"];
    $data_abertura    = $campos["os"]["data_abertura"];
    $produto          = $campos["produto"]["id"];
    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $fora_garantia    = false;
    
    $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento AND fora_garantia = 't'";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){
        $fora_garantia = true;
    }

    $cortesia_roca = verifica_os_cortesia_roca();

    if($cortesia_roca === true){
	$fora_garantia = true;
    }

    if (!empty($produto) && !empty($data_compra) && !empty($data_abertura) AND $fora_garantia === false) {
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

$valida_garantia = "valida_garantia_roca";

function verifica_os_cortesia_roca(){
	global $con, $os, $login_fabrica;

	$sql = "SELECT cortesia FROM tbl_os WHERE os = {$os}";
	$res = pg_query($con, $sql);

	$cortesia_roca = pg_fetch_result($res,0,'cortesia');

	if($cortesia_roca == 't'){
		return true;
	}else{
		return false;
	}
}

$valida_anexo_boxuploader = "";
$valida_anexo = "";

/*
**  Funções Auditorias
*/

function auditoria_os_reincidente_roca() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    if (!empty($campos['os']['nota_fiscal']) && !empty($campos["revenda"]["cnpj"])) {
	$posto = $campos['posto']['id'];
    	$sql = "SELECT tbl_os.os, tbl_os_campo_extra.os_revenda 
            FROM tbl_os JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica 
            WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os} AND tbl_os.os_reincidente IS NOT TRUE";
    	$res = pg_query($con, $sql);
    	if(pg_num_rows($res) > 0){
        	$os_revenda = pg_fetch_result($res, 0, 'os_revenda');
        	$sql = "SELECT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                WHERE tbl_os.fabrica = {$login_fabrica}
                -- AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.posto = $posto
                AND tbl_os.os < {$os}
                AND tbl_os_campo_extra.os_revenda < $os_revenda
                AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
                AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
                AND tbl_os_produto.produto = {$campos['produto']['id']}
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
}

function auditoria_peca_sem_preco_roca (){
    global $con, $campos, $login_fabrica, $msg_erro, $os;

    $produto_pecas = $campos["produto_pecas"];
    $posto_id = $campos["posto"]["id"];
    
    foreach ($produto_pecas as $key => $peca) {
        $peca_id = $peca["id"];

        if (!empty($peca_id)){
            $sql = "
                SELECT ti.preco
                FROM tbl_linha l
                JOIN tbl_posto_linha pl ON pl.linha = l.linha AND pl.posto = {$posto_id}
                JOIN tbl_tabela t ON t.tabela = pl.tabela AND t.fabrica = {$login_fabrica}
                JOIN tbl_tabela_item ti ON ti.tabela = t.tabela AND ti.peca = {$peca_id}
                WHERE l.fabrica = {$login_fabrica} ";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) == 0){
                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");
                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }
                
                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça sem preço%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça sem preço%'")) {
                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, {$auditoria_status}, 'OS em auditoria de peça sem preço')";
                    $res = pg_query($con, $sql);
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar peça na ordem de serviço #AUD001");
                    }
                }
            }
        }
    }
}

function auditoria_servico_realizado (){
    global $con, $campos, $login_fabrica, $msg_erro, $os;

    $produto_pecas = $campos["produto_pecas"];
    
    foreach ($produto_pecas as $key => $peca) {
        $peca_id = $peca["id"];

        if (!empty($peca_id)){
            $sql = "
                SELECT servico_realizado
                FROM tbl_servico_realizado
                WHERE fabrica = $login_fabrica
                AND peca_estoque IS TRUE
                AND servico_realizado = {$peca['servico_realizado']}";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0){
                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");
               
                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }
                
                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%estoque Roca%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%estoque Roca%'")) {
                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, {$auditoria_status}, 'OS em auditoria de serviço realizadoo - Troca de Peça (estoque Roca)')";
                    $res = pg_query($con, $sql);
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar peça na ordem de serviço #AUD001");
                    }
                }
            }
        }
    }
}

#auditoria removida no chamado -> 6870662
// function auditoria_valor_adicional_roca (){ 
//     global $con, $campos, $os, $login_fabrica;
    
//     if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){
        
//         if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais'", $os) === true){
//             $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

//             if($busca["resultado"]){
//                 $auditoria = $busca["auditoria"];

//                 $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais')";
//                 pg_query($con,$sql);

//                 if(strlen(pg_last_error()) > 0){
//                     throw new Exception("Erro ao lançar peça na ordem de serviço #AUD002");
//                 }else{
//                     return true;
//                 }
//             }else{
//                 throw new Exception("Erro ao lançar peça na ordem de serviço #AUD002");
//             }
//         }
//     }
// }

function grava_os_fabrica(){
    global $campos;

    if (!empty($campos["os"]["valor_adicional_valor"]) and !empty($campos["os"]["valor_adicional"])){
        $valor_adicional = $campos["os"]["valor_adicional_valor"];
        $valor = array_values($valor_adicional);
    }
    
    if($campos['produto']['solicita_troca'] == "t"){
    	return array(
    	    "troca_garantia" => "'t'",
    	    "marca" => $campos["produto"]["marca_produto_troca"],
            "defeito_reclamado" => (!empty($campos["os"]["defeito_reclamado"])) ? $campos["os"]["defeito_reclamado"] : "null",
            "defeito_constatado_grupo" => (!empty($campos["produto"]["defeito_constatado_grupo"])) ? $campos["produto"]["defeito_constatado_grupo"] : "null"
        );
    }else{
        return array(
            "defeito_reclamado" => (!empty($campos["os"]["defeito_reclamado"])) ? $campos["os"]["defeito_reclamado"] : "null",
            "valores_adicionais" => (!empty($valor[0])) ? "'{$valor[0]}'" : "null",
            "defeito_constatado_grupo" => (!empty($campos["produto"]["defeito_constatado_grupo"])) ? $campos["produto"]["defeito_constatado_grupo"] : "null"
        );

    }
}

function grava_os_extra_fabrica(){
    global $campos;

    if($campos['produto']['solicita_troca'] == "t"){

	return array(
            "faturamento_cliente_revenda" => "'".$campos["produto"]["enviar_para"]."'"
        );
    }
}

function auditoria_solicitacao_troca_produto_roca(){
    global $con, $login_fabrica, $os, $campos;
    
    $auditarOS = false;
    if($campos['produto']['solicita_troca'] == "t"){

    	$sql = "SELECT cancelada, reprovada
    		FROM tbl_auditoria_os
    		WHERE os = {$os}
    		AND auditoria_status = 3
    		AND lower(fn_retira_especiais(observacao)) = 'auditoria de solicitacao de troca de produto'
    		ORDER BY data_input DESC LIMIT 1;";
    	$res = pg_query($con,$sql);	

    	if(pg_num_rows($res) == 0){
    	    $auditarOS = true;
    	}else{
    	    $cancelada = pg_fetch_result($res,0,'cancelada');
    	    $reprovada = pg_fetch_result($res,0,'reprovada');

    	    if(strlen($cancelada) > 0 || strlen($reprovada) > 0){
                $auditarOS = true;
    	    }
    	}
    }

    if($auditarOS === true){
	    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, paga_mao_obra) VALUES
		           ({$os}, 3, 'Auditoria de solicitação de troca de produto', false)";
        $res = pg_query($con, $sql);
    }
}

$antes_valida_campos = "antes_valida_campos";
function antes_valida_campos() {
    global $campos, $con, $login_fabrica, $msg_erro, $os, $areaAdmin, $regras;
    
    $solicita_troca = $campos["produto"]["solicita_troca"];
    $enviar_para    = $campos["produto"]["enviar_para"];
    $marca          = $campos["produto"]["marca_produto_troca"];
    $fora_linha     = $campos["produto"]["produto_fora_linha"];
    $produto        = $campos["produto"]["id"];
    $produto_troca  = $campos["produto"]["produto_troca"];
    $flag_cortesia  = $campos["os"]["cortesia"];

    if ($flag_cortesia == 't'){
	$regras["os|nota_fiscal"]["obrigatorio"] = false;
        $regras["os|data_compra"]["obrigatorio"] = false;
    }
 
   if (!empty($os)){
        
        if (!$areaAdmin){
            $sql_tta = "
                SELECT 
                    tta.tecnico_agenda,
                    osr.hd_chamado
                FROM tbl_os_campo_extra oce
                JOIN tbl_os_revenda osr ON osr.os_revenda = oce.os_revenda AND osr.fabrica = $login_fabrica
                LEFT JOIN tbl_tecnico_agenda tta ON tta.os_revenda = osr.os_revenda AND tta.fabrica = $login_fabrica
                WHERE oce.os = $os
                AND oce.fabrica = $login_fabrica";
            $res_tta = pg_query($con, $sql_tta);
            if (pg_num_rows($res_tta) > 0){
                $tecnico_agenda = pg_fetch_result($res_tta, 0, "tecnico_agenda");
                $hd_chamado = pg_fetch_result($res_tta, 0, "hd_chamado");
                
                if (!empty($hd_chamado) AND empty($tecnico_agenda)){
                    $msg_erro["msg"][] = "Não é possível fazer alteração na Ordem de Serviço sem realizar um agendamento.";
                }
            }
        }
            
        if (!empty($campos["os"]["hd_chamado"])){
            $sql = "
                SELECT tbl_os_revenda_item.os_revenda_item 
                FROM tbl_os_campo_extra
                JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda_item = tbl_os_campo_extra.os_revenda_item
                WHERE tbl_os_campo_extra.fabrica = {$login_fabrica}
                AND tbl_os_campo_extra.os = {$os}
                AND tbl_os_revenda_item.produto IS NULL";
            $res = pg_query($con, $sql);
           
            if (pg_num_rows($res) > 0) {
                $id_ori = pg_fetch_result($res, 0, "os_revenda_item");

                $up_ori = "UPDATE tbl_os_revenda_item SET produto = {$produto} WHERE os_revenda_item = {$id_ori}";
                $res_ori = pg_query($con, $up_ori);
            }
        }
    }

    if ($solicita_troca == "t" AND !empty($os)){

        $sqlOsTroca = "SELECT * FROM tbl_os_troca WHERE os = {$os}";
        $resOsTroca = pg_query($con, $sqlOsTroca);
        
        if (pg_num_rows($resOsTroca) > 0){
            unset($campos["produto_pecas"]);
        }
        
        if (empty($marca)){
            $msg_erro["campos"][] = "produto[trocar_produto]";
        }

        if (empty($enviar_para)){
            $msg_erro["campos"][] = "produto[enviar_para]";
        }

        if ($fora_linha == "true" AND empty($produto_troca)){
            $msg_erro["campos"][] = "produto[produto_troca]";
        }
    }

    if (count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
    }
}

function buscaServicoRealizadoRoca($tipo) {
    global $login_fabrica, $con;

    switch($tipo){
        case "gera_pedido"   :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS NOT TRUE"; break;
        case "estoque"       :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS TRUE"; break;
        case "troca_produto" :  $cond = " AND troca_produto IS TRUE"; break;
    }

    $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND gera_pedido IS TRUE $cond";
    $query = pg_query($con, $sql);
    $res = pg_fetch_all($query);
    return (is_array($res) && count($res) > 0) ? $res[0]['servico_realizado'] : false;
}

function grava_os_campo_extra_fabrica(){
    global $con, $campos, $login_fabrica, $os;


    $campos_update  = array();
    $marca_produto  = $campos["produto"]["marca_produto"];
    $fora_linha     = $campos["produto"]["produto_fora_linha"];
    $produto_troca  = $campos["produto"]["produto_troca"];

    if (!empty($os)){
        $sql_ori = "SELECT os_revenda_item, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
        $res_ori = pg_query($con, $sql_ori);

        if (pg_num_rows($res_ori) > 0){
            $ori = pg_fetch_result($res_ori, 0, "os_revenda_item");
            $campos_adicionais = pg_fetch_result($res_ori, 0, "campos_adicionais");

            if (!empty($campos_adicionais)){
                $campos_adicionais = json_decode($campos_adicionais, true);
            }

            if (!empty($marca_produto)){
                $up_ori = "UPDATE tbl_os_revenda_item SET marca = $marca_produto WHERE os_revenda_item = $ori";
                $res_up = pg_query($con, $up_ori);
            }

            if (!empty($campos["produto"]["defeito_constatado_grupo"])) {
                $sqlRevItem = "SELECT parametros_adicionais FROM tbl_os_revenda_item WHERE os_revenda_item= $ori";
                
                $resRevItem = pg_query($con, $sqlRevItem);
                if (pg_num_rows($resRevItem) > 0) {

                    $xparametros_adicionais = json_decode(pg_fetch_result($resRevItem, 0, 'parametros_adicionais'), 1);
                    $xparametros_adicionais["defeito_constatado_grupo"] = $campos["produto"]["defeito_constatado_grupo"];
                    $rev_parametros_adicionais = json_encode($xparametros_adicionais);

                    $up_ori = "UPDATE tbl_os_revenda_item SET parametros_adicionais = '{$rev_parametros_adicionais}' WHERE os_revenda_item = $ori";
                    $res_up = pg_query($con, $up_ori);
                }
            }
        }
        
        if (!empty($marca_produto)){
            $campos_update[] = " marca = $marca_produto ";
        }

        if (!empty($campos["os"]["rastreabilidade"])){
            $rastreabilidade = $campos["os"]["rastreabilidade"];
            $campos_adicionais["rastreabilidade"] = $rastreabilidade;
        }

        if (!empty($campos["os"]["instalacao_publica"])){
            $instalacao_publica = $campos["os"]["instalacao_publica"];
            $campos_adicionais["instalacao_publica"] = $instalacao_publica;
        }

        if ($fora_linha == "true" AND !empty($produto_troca)){
            $campos_adicionais["produto_troca_posto"] = $produto_troca;
        }

        if (count($campos_update) > 0){
            $campos_adicionais = json_encode($campos_adicionais);
            $campos_update[] = " campos_adicionais = '$campos_adicionais' ";
        
            $dados_update = implode(",", $campos_update);

            $sql = "UPDATE tbl_os_campo_extra SET $dados_update WHERE os = $os AND fabrica = $login_fabrica";
            $res = pg_query($con, $sql);
        }
    }
}

function verifica_estoque_peca_roca(){

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
                    $sqlEstoque = "SELECT qtde_saida FROM tbl_estoque_posto_movimento WHERE os_item = {$os_item}";
                    $resEstoque = pg_query($con, $sqlEstoque);

                    if (pg_num_rows($resEstoque) > 0) {
                        $qtde_saida = pg_fetch_result($resEstoque, 0, "qtde_saida");
                        $diferenca = $qtde - $qtde_saida;

                        if ($diferenca != 0) {
                            $$Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);

                            $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                            if($status_estoque == false){
                                $novo_servico_realizado = buscaServicoRealizadoRoca("gera_pedido");
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            }
                        }
                    } else {
                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);
                        
                        if(!$status_estoque){
                            $novo_servico_realizado = buscaServicoRealizadoRoca("gera_pedido");
        
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
                            $novo_servico_realizado = buscaServicoRealizadoRoca("estoque");
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
