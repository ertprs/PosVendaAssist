<?php
$regras["consumidor|cpf"] = array(
    "obrigatorio" => true
); 

if($_REQUEST["os"]["consumidor_revenda"] == "R"){
	$regras["consumidor|telefone"]["obrigatorio"] = false;
	$regras["consumidor|celular"]["obrigatorio"] = false;
	$regras["consumidor|nome"]["obrigatorio"] = false;
	$regras["consumidor|cpf"]["obrigatorio"] = false;
	$regras["consumidor|estado"]["obrigatorio"] = false;
	$regras["consumidor|cidade"]["obrigatorio"] = false;
	$regras["consumidor|email"]["obrigatorio"] = false;
}else{
	$regras["consumidor|celular"]["obrigatorio"] = true;
	$regras["consumidor|telefone"]["obrigatorio"] = true;
}

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}

$regras["consumidor|email"] = array(
    "obrigatorio" => true
); 

$regras["os|defeito_reclamado"] = array(
    "obrigatorio" => false
);

$regras["os|status_orcamento"] = array(
    "obrigatorio" => false,
    "function" => array("valida_status_orcamento")
);

if (verifica_tipo_atendimento() == "Garantia Certificado"){
    $regras["revenda|nome"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]["function"]      = [];
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
} else {
    $regras["revenda|cnpj"] = array(
        "obrigatorio" => true,
        "function" => array("valida_status_orcamento")
    );
}


$anexos_obrigatorios = [];

if (!in_array(verifica_tipo_atendimento(), ["Orçamento","Garantia de Selo","Garantia Cortesia"])){
    $regras["produto|serie"] = array(
        "function" => array("valida_numero_de_serie")
    );
    $valida_garantia = "valida_garantia";
}

if (verifica_tipo_atendimento() == "Orçamento"){
    $regras["os|nota_fiscal"]["obrigatorio"]   = false;
    $regras["os|data_compra"]["obrigatorio"] = false;
    $regras["revenda|nome"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]["function"]      = [];
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
    $regras["produto|serie"]["obrigatorio"] = false;
    $regras_pecas["servico_realizado"] = false;
    $regras["produto|serie"] = array(
        "function" => array()
    );
    $valida_garantia = "";

} else {
    $regras["revenda|cnpj"] = array(
        "obrigatorio" => true,
        "function" => array("valida_revenda_cnpj_mq")
    );
}

if (verifica_tipo_atendimento() == "Garantia de Selo"){
    $regras["os|nota_fiscal"]["obrigatorio"]   = false;
    $regras["os|data_compra"]["obrigatorio"] = false;
    $regras["produto|serie"]["obrigatorio"] = true;
    $valida_garantia = "";
}

if (verifica_tipo_atendimento() == "Garantia Cortesia"){
    $valida_garantia = "";
}

$id_orcamento = 0;
function id_tipo_atendimento_orcamento(){

    global $con, $login_fabrica;

    $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao ILIKE 'Or%amento'";
    $res = pg_query($con, $sql);

    return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, "tipo_atendimento") : 0;

}

/* Insere o ID do tipo atendimento Orçamento para o posto interno */
if(verificaTipoPosto("posto_interno","TRUE") || $areaAdmin == true){

    $id_orcamento = id_tipo_atendimento_orcamento();

}

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

function valida_revenda_cnpj_mq() {
    global $con, $campos;

    $cpf = preg_replace("/\D/", "", $campos["revenda"]["cnpj"]);

    if (strlen($cpf) > 0) {
        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("CPF/CNPJ $cpf é inválido");
        }
    }
}

$valida_anexo = "";
function valida_anexo_mq() {
    global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $areaAdmin;

    if ($fabricaFileUploadOS) {
        $anexo_chave = $campos["anexo_chave"];
    
        if (!empty($anexo_chave) && in_array(verifica_tipo_atendimento(), ["Garantia", "Garantia de Selo", "Garantia Cortesia", "Garantia Certificado", "Garantia Nota"])){
             if (!empty($os)){
                 $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";
             }else{
                 $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";
             }
             $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                         FROM tbl_tdocs 
                         WHERE tbl_tdocs.fabrica = $login_fabrica
                         AND tbl_tdocs.situacao = 'ativo'
                         $cond_tdocs";
             $res_tdocs = pg_query($con,$sql_tdocs);
     
             if (pg_num_rows($res_tdocs) > 0){
     
                 $typeId = pg_fetch_all_columns($res_tdocs);

                 if (!in_array('notafiscal', $typeId)) {
                     throw new Exception(traduz("Obrigatório anexar: nota fiscal do produto"));
                 }
     
             }else{
                throw new Exception(traduz("Obrigatório os seguintes anexos: nota fiscal"));
            }
        }
     }
}

$valida_anexo = "valida_anexo_mq";

if (verificaTipoPosto("posto_interno","TRUE") || $areaAdmin) {
    $regras["os|nota_fiscal"]["obrigatorio"]   = false;
    $valida_anexo = "";
}

$auditorias = array(
    "auditoria_os_reincidente_mq",
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria",
    "auditoria_pecas_excedentes"
);

if (verificaTipoPosto("posto_interno","TRUE")) {

    if (verifica_tipo_atendimento() == 'Orçamento'){
        $funcoes_envia_email = ["envia_email_consumidor"];
    } else {
        $funcoes_fabrica    = ["verifica_estoque_peca_mq","envia_email_consumidor_status_os"];
    }
}else{
	$funcoes_fabrica    = ["verifica_estoque_peca_mq"];
}

$funcoes_preos_atendimento = ['verificaPreOsAtendimento'];

function verificaTipoPosto($tipo, $valor) {
    global $campos, $con, $login_fabrica, $login_posto;

    $id_posto = (strlen($login_posto) > 0) ? $login_posto : $_REQUEST["posto"]["id"];

    $sql = "
        SELECT tbl_tipo_posto.tipo_posto
        FROM tbl_posto_fabrica
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND tbl_posto_fabrica.posto = {$id_posto}
        AND tbl_tipo_posto.{$tipo} IS {$valor}
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
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

function verifica_estoque_peca_mq(){

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
                                $novo_servico_realizado = buscaServicoRealizadoMq("gera_pedido");
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            }
                        }
                    } else {
                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                        if(!$status_estoque){
			    $novo_servico_realizado = buscaServicoRealizadoMq("gera_pedido");
		
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

			 	$novo_servico_realizado = buscaServicoRealizadoMq("estoque");

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

function buscaServicoRealizadoMq($tipo) {
	global $login_fabrica, $con;

	switch($tipo){
	
		case "gera_pedido"   :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS NOT TRUE"; break;
		case "estoque"	     :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS TRUE"; break;
		case "troca_produto" :  $cond = " AND troca_produto IS TRUE"; break;

	}

	$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND gera_pedido IS TRUE $cond";
	$query = pg_query($con, $sql);
	$res = pg_fetch_all($query);
	return (is_array($res) && count($res) > 0) ? $res[0]['servico_realizado'] : false;
}

function grava_os_fabrica(){

    global $campos, $con;

  if ($campos["os"]["consumidor_revenda"] == "R"){

        $campos["consumidor"]["cep"] = preg_replace("/[\-]/", "", $campos["revenda"]["cep"]);
        $campos["consumidor"]["nome"]        = $campos["revenda"]["nome"];
        $campos["consumidor"]["cpf"]         = $campos["revenda"]["cnpj"];
        $campos["consumidor"]["cep"]         = $campos["revenda"]["cep"];
        $campos["consumidor"]["estado"]      = $campos["revenda"]["estado"];
        $campos["consumidor"]["cidade"]      = $campos["revenda"]["cidade"];
        $campos["consumidor"]["bairro"]      = $campos["revenda"]["bairro"];
        $campos["consumidor"]["endereco"]    = $campos["revenda"]["endereco"];
        $campos["consumidor"]["numero"]      = $campos["revenda"]["numero"];
        $campos["consumidor"]["complemento"] = $campos["revenda"]["complemento"];
        $campos["consumidor"]["telefone"]    = $campos["revenda"]["telefone"];
        $campos["consumidor"]["email"]       = $campos["revenda"]["email"];
        $campos["consumidor"]["celular"]     = $campos["revenda"]["celular"];
            
    }

    $campos_bd = array();

    $descricao_status_orcamento = $campos["os"]["status_orcamento"];
    $sql_status = "SELECT status_os FROM tbl_status_os WHERE UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais(trim('{$descricao_status_orcamento}')))";
    $res_status = pg_query($con, $sql_status);

    if(pg_num_rows($res_status) > 0){
        $id_status_os = pg_fetch_result($res_status, 0, 'status_os');
        $campos_bd["status_os_ultimo"] = $id_status_os;
        return $campos_bd;
    }

}

function grava_os_extra_fabrica() {
   
    global $con, $campos, $os, $login_fabrica;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $tipo_de_os       = $campos["os"]["consumidor_revenda"];

    if (in_array($tipo_atendimento, [33004,76967]) && $tipo_de_os == "R") {
        $revenda_email = (!empty($campos["revenda"]["email"]))    ? $campos["revenda"]["email"]    : $campos["revenda_email"];
        $revenda_fone  = (!empty($campos["revenda"]["telefone"])) ? $campos["revenda"]["telefone"] : $campos["revenda_fone"];

        $campos_extra["revenda_email"] = (!empty($revenda_email)) ? $revenda_email : "null";
        $campos_extra["revenda_fone"]  = (!empty($revenda_fone))  ? $revenda_fone  : "null";
        $json_campos_extra             = json_encode($campos_extra);
        $sua_os                        = explode('-', $os);

        
        $sqlSuaOs = "SELECT sua_os FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $resSuaOs = pg_query($con, $sqlSuaOs);
        $sua_os   = (pg_num_rows($resSuaOs) > 0) ? pg_fetch_result($resSuaOs, 0, 'sua_os') : false; 
        $sua_os   = explode('-', $sua_os);

        if (!empty($sua_os)) {
            $sqlUpdate = "UPDATE tbl_os_revenda SET
                    campos_extra = '{$json_campos_extra}'
                WHERE os_revenda = ".$sua_os[0]." AND fabrica = {$login_fabrica}";
            $resUpdate = pg_query($con, $sqlUpdate);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao cadastrar dados da revenda.");
            }
        }
    }

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $descricao = pg_fetch_result($res_status, 0, 'descricao');

            if (verifica_tipo_atendimento() == "Orçamento"){

                $mo_adicional = str_replace(",",".",str_replace(".","", $campos["os"]["valor_adicional_mo"]));

                if (!empty($mo_adicional)){
                    return array(
                        "mao_de_obra_adicional" => $mo_adicional,
                    );
                }
            }
        }
    } 
}

function valida_status_orcamento() {
    global $campos, $msg_erro, $con, $login_fabrica;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $status_orcamento = $campos["os"]["status_orcamento"];

    $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento ";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $descricao = pg_fetch_result($res, 0, 'descricao');

        if($descricao == "Orçamento" AND strlen(trim($status_orcamento)) == 0){
            $msg_erro["msg"][]    = "É obrigatório informar status do Orçamento";
            $msg_erro["campos"][] = "os[status_orcamento]";
        }
    }
}

function auditoria_os_reincidente_mq(){

	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;
    if (verifica_tipo_atendimento() <> "Orçamento") {
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
    				AND tbl_os.excluida IS NOT TRUE
    				AND tbl_os.os < {$os}
    				AND tbl_os.posto = $posto
    				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
    				AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
                    AND tbl_os.serie = '".trim($campos["produto"]["serie"])."'
    				AND tbl_os_produto.produto = {$campos['produto']['id']}
    				ORDER BY tbl_os.data_abertura DESC
    				LIMIT 1";
    		$resSelect = pg_query($con, $select);

    		if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
    			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

    			if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                    $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");
                    
                    if($busca['resultado']){
                        $auditoria_status = $busca['auditoria'];
                    }

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                            ({$os}, $auditoria_status, 'OS Reincidente por CNPJ, NOTA FISCAL, PRODUTO')";
                    $res = pg_query($con,$sql);
                    
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

function envia_email_consumidor() {
    global $con, $login_fabrica, $campos, $os, $externalId, $_REQUEST;

    if ($campos["os"]["envia_orcamento_email"] == 't') {
    include __DIR__."/../../../class/communicator.class.php";

    $mailTc = new TcComm('smtp@posvenda');
    $sql = "select tbl_os.os,
                   tbl_os.sua_os,
                   tbl_os.consumidor_nome,
                   tbl_os.consumidor_email,
                   tbl_os.consumidor_cidade,
                   tbl_os.consumidor_estado,
                   tbl_os.consumidor_fone,
                   tbl_os.consumidor_cpf,
                   tbl_os.consumidor_revenda,
                   tbl_os.consumidor_endereco,
                   tbl_os.consumidor_numero,
                   tbl_os.consumidor_cep,
                   tbl_os.consumidor_complemento,
                   tbl_os.consumidor_bairro,
                   tbl_os.consumidor_celular,
                   tbl_os.consumidor_fone_comercial,
                   tbl_os.consumidor_nome_assinatura,
                   tbl_os.consumidor_fone_recado,
                   tbl_os.tipo_atendimento,
                   TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY HH24:MI:SS') AS finalizada,
                   TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS data_digitacao,
                   TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                   TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
                   tbl_os.revenda_cnpj,
                   tbl_os.revenda_nome,
                   TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
                   tbl_os.revenda_nome,
                   tbl_os.revenda_cnpj,
                   tbl_defeito_reclamado.descricao AS defeito_reclamado_nome,
                   tbl_defeito_constatado.descricao AS defeito_constatado_nome,
                   tbl_os.nota_fiscal,
                   tbl_produto.descricao AS nome_produto,
                   tbl_produto.referencia AS referencia_produto,
                   tbl_status_os.descricao AS status_orcamento,
                   tbl_os_extra.mao_de_obra_adicional,
                   tbl_tipo_atendimento.descricao AS tipo_atendimento_desc,
                   tbl_status_checkpoint.descricao AS status_da_os,
                   tbl_os_revenda.campos_extra AS revenda_campos_extra
             FROM tbl_os 
             JOIN tbl_os_produto USING(os)
             JOIN tbl_os_extra USING(os)
             JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
             JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento  AND tbl_tipo_atendimento.fabrica={$login_fabrica}
        LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica={$login_fabrica}
        LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica={$login_fabrica}
        LEFT JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i={$login_fabrica}
        LEFT JOIN tbl_os_revenda ON tbl_os_revenda.sua_os = SPLIT_PART(tbl_os.sua_os, '-', 1) AND tbl_os_revenda.fabrica = {$login_fabrica}
            WHERE tbl_os.os={$os}
              AND tbl_os.fabrica={$login_fabrica};";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $xos = pg_fetch_result($res, 0, 'os');
        $consumidor_nome                = pg_fetch_result($res, 0, 'consumidor_nome');
        $consumidor_email               = pg_fetch_result($res, 0, 'consumidor_email');
        $consumidor_cidade              = pg_fetch_result($res, 0, 'consumidor_cidade');
        $consumidor_estado              = pg_fetch_result($res, 0, 'consumidor_estado');
        $consumidor_fone                = pg_fetch_result($res, 0, 'consumidor_fone');
        $consumidor_cpf                 = pg_fetch_result($res, 0, 'consumidor_cpf');
        $consumidor_revenda             = pg_fetch_result($res, 0, 'consumidor_revenda');
        $consumidor_endereco            = pg_fetch_result($res, 0, 'consumidor_endereco');
        $consumidor_numero              = pg_fetch_result($res, 0, 'consumidor_numero');
        $consumidor_cep                 = pg_fetch_result($res, 0, 'consumidor_cep');
        $consumidor_complemento         = pg_fetch_result($res, 0, 'consumidor_complemento');
        $consumidor_bairro              = pg_fetch_result($res, 0, 'consumidor_bairro');
        $consumidor_celular             = pg_fetch_result($res, 0, 'consumidor_celular');
        $consumidor_fone_comercial      = pg_fetch_result($res, 0, 'consumidor_fone_comercial');
        $consumidor_nome_assinatura     = pg_fetch_result($res, 0, 'consumidor_nome_assinatura');
        $consumidor_fone_recado         = pg_fetch_result($res, 0, 'consumidor_fone_recado');
        $tipo_atendimento               = pg_fetch_result($res, 0, 'tipo_atendimento_desc');
        $qtde_km                        = pg_fetch_result($res, 0, 'qtde_km');
        $data_abertura                  = pg_fetch_result($res, 0, 'data_abertura');
        $data_digitacao                 = pg_fetch_result($res, 0, 'data_digitacao');
        $data_fechamento                = pg_fetch_result($res, 0, 'data_fechamento');
        $data_nf                        = pg_fetch_result($res, 0, 'data_nf');
        $rev_nome                       = pg_fetch_result($res, 0, 'revenda_nome');
        $rev_cnpj                       = pg_fetch_result($res, 0, 'revenda_cnpj');
        $defeito_reclamado              = pg_fetch_result($res, 0, 'defeito_reclamado_nome');
        $defeito_constatado             = pg_fetch_result($res, 0, 'defeito_constatado_nome');
        $nf                             = pg_fetch_result($res, 0, 'nota_fiscal');
        $data_nf                        = pg_fetch_result($res, 0, 'data_nf');
        $finalizada                     = pg_fetch_result($res, 0, 'finalizada');
        $data_consertado                = pg_fetch_result($res, 0, 'data_consertado');
        $referencia_produto             = pg_fetch_result($res, 0, 'referencia_produto');
        $nome_produto                   = pg_fetch_result($res, 0, 'nome_produto');
        $numero_serie                   = pg_fetch_result($res, 0, 'numero_serie');
        $rg_produto                     = pg_fetch_result($res, 0, 'rg_produto');
        $status_orcamento               = pg_fetch_result($res, 0, 'status_orcamento');
        $status_da_os                   = pg_fetch_result($res, 0, 'status_da_os');
        $mao_de_obra_adicional          = pg_fetch_result($res, 0, 'mao_de_obra_adicional');
        $revenda_campos_extra           = json_decode(pg_fetch_result($res, 0, 'revenda_campos_extra'), true);
        $revenda_email                  = $revenda_campos_extra["revenda_email"];
        $tipo_atendimento               = $campos["os"]["tipo_atendimento"]; 
        $tipo_de_os                     = $campos["os"]["consumidor_revenda"];

        $status_os = $status_da_os;
        $status_oc = $status_orcamento ;
        $os_fabricante = "OS FABRICANTE";

        $xos2 = $xos;
        
        if (in_array($tipo_atendimento, [33004,76967]) && $tipo_de_os == "R") {
            $consumidor_email = (!empty($revenda_email)) ? $revenda_email : $consumidor_email;
            $xos2             = pg_fetch_result($res, 0, 'sua_os');
        }

        if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {

            $assunto    = 'MQ Professional Online - Orçamento';

            $corpoMensagem = '
            <p>Prezado '.$consumidor_nome.'.</p>
            <p>Segue abaixo o orçamento para reparo do produto <b>'.$referencia_produto .' - '. $nome_produto.'</b>.</p><br>
                    <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                        <tbody>
                            <tr>
                                <td rowspan="4" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="300">
                                    <center>
                                        '.$os_fabricante.'<br>&nbsp;
                                        <b><font size="6" color="#C67700">'.$xos2.'</font></b>
                                    </center>
                                </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" height="15" colspan="4">&nbsp;Datas da OS</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Abertura &nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15">&nbsp;'.$data_abertura.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Digitação </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15">&nbsp;'.$data_digitacao.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Fechamento&nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" id="data_fechamento">&nbsp;'.$data_fechamento.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Finalizada </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" id="finalizada">&nbsp;'.$finalizada.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Data da NF </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$data_nf.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Fechado em  </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" id="fechado_em" width="100" height="15">&nbsp;'.$data_fechamento.'      </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">
                                    <b></b><center><b>
                                        '.$status_os.'
                                    </b></center>
                                </td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Consertado &nbsp; </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" colspan="1" id="consertado">&nbsp;&nbsp;'.$data_consertado.'</td>                
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15"></td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                        <tbody>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Tipo de Atendimento</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$tipo_atendimento.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="100">STATUS ORÇAMENTO</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'.$status_oc.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="100">Quantidade de KM</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'.$qtde_km.' KM</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                        <tbody>
                            <tr>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" height="15" colspan="100%">&nbsp;Informações do Produto   </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Referência</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$referencia_produto.' </td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Descrição</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$nome_produto.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">N. de Série   &nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'.$numero_serie.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">RG Produto</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" nowrap="">'.$rg_produto.'</td>
                            </tr>
                       </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                        <tbody>
                            <tr>
                                <td height="15" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%">&nbsp;Defeitos</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Reclamado</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="140">'.$defeito_reclamado.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Defeito Constatado</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp; '.$defeito_constatado.'</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                        <tbody>
                            <tr>
                                <td  style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;Informações sobre o consumidor </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Nome</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300">&nbsp;'.$consumidor_nome.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Telefone Residencial</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$consumidor_fone.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Celular</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300">&nbsp;'.$consumidor_celular.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Telefone Comercial</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_fone_comercial .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" nowrap="">CPF Consumidor</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15"> &nbsp;'. $consumidor_cpf .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">CEP</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_cep .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Endereço</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_endereco .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Número</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_numero .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Complemento</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_complemento .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Bairro</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'. $consumidor_bairro .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Cidade</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_cidade .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Estado</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_estado .'</td>
                            </tr>
                           <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">E-Mail</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_email .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">&nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                        <tbody>
                            <tr>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;Informações Da Revenda</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Nome</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300" 1="">&nbsp;'.$rev_nome.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="80">CNPJ Revenda</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$rev_cnpj.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">NF Número </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$nf.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Data da NF </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$data_nf.'</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                            <tbody>
                                <tr>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: left;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Componente</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">QTD</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Preço unitário</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">PREÇO TOTAL</td>
                                </tr>';
                        $sqlItens = "
                            SELECT tbl_os_item.peca,
                                tbl_os_item.qtde,
                                tbl_peca.referencia,
                                tbl_peca.descricao,
                                tbl_os_item.preco
                            FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_peca USING(peca)
                            WHERE
                                tbl_os.os = $xos
                                AND tbl_os.fabrica = $login_fabrica;";

                        $resItens = pg_query($con,$sqlItens);

                        if (pg_num_rows($resItens) > 0) {
                            $total_pecas = [];
                            foreach (pg_fetch_all($resItens) as $key => $rows) {
                            $total_pecas[] = ($rows['qtde']*$rows['preco']);

                $corpoMensagem .= '
                                <tr>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'. $rows['referencia'] .' - '. $rows['descricao'] .'</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'. $rows['qtde'] .'</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format($rows['preco'], 2, ',', '.') .'</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format(($rows['qtde']*$rows['preco']), 2, ',', '.') .'</td>
                                </tr>';
                            }
                        }
                $corpoMensagem .= '
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor de MÃO DE OBRA</td>
                                    <td  colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format($mao_de_obra_adicional, 2, ',', '.') .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor Total Peças</td>
                                    <td colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format(array_sum($total_pecas), 2, ',', '.') .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor total geral</td>
                                    <td colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format((array_sum($total_pecas)+$mao_de_obra_adicional), 2, ',', '.') .'</td>
                                </tr>';
                $corpoMensagem .= '
                            </tbody>
                        </table>
                    <br><br>

                    <p>Estamos no aguardo da aprovação do orçamento para prosseguir com o reparo do produto.</p><br/>
                    <p><em>Caso aprovem o orçamento, solicitamos que façam deposito na conta abaixo e enviem a imagem do comprovante para o e-mail: sac@mqhair.com.br  junto com o numero da OS.</em></p><br>

                    Banco Itau (341)<br> 
                    Ag: 2000<br>
                    Conta: 91443-8<br>
                    SL ONLINE COMERCIO DE ARTIGOS<br>
                    CNPJ:28.879.206/0001-52<br><br>

                    Não nos responsabilizamos por depósitos feitos em outras contas.

                    <br><br>

                    Disponibilizamos também o pagamento através de cartão de crédito. Neste caso, solicitamos que entrem em contato pelo telefone (11) 3628-8969 e falem com o SAC.
                    <br><br>

                    Em caso de duvidas ficamos a disposição !!
                    <br>

                    <p>Atenciosamente</p>
                    <p>MQ Professional</p>
                    ';

              $mailTc->sendMail(
                $consumidor_email,
                $assunto,
                $corpoMensagem,
                'sac@mqhair.com.br'
            );

        }
    }
    }
}

function envia_email_consumidor_status_os() {
    global $con, $login_fabrica, $campos, $os, $externalId, $status_da_os_antes, $_REQUEST;
    include __DIR__."/../../../class/communicator.class.php";

    $mailTc = new TcComm('smtp@posvenda');
    $sqlStatusOsDepois = "SELECT tbl_status_checkpoint.descricao AS nome_status,tbl_os.consumidor_nome,tbl_os.status_checkpoint, tbl_os.os, tbl_os.consumidor_email
                          FROM tbl_os 
                          JOIN tbl_status_checkpoint USING(status_checkpoint)
                         WHERE tbl_os.os = $os
                           AND tbl_os.fabrica = {$login_fabrica}";
    $resStatusOsDepois = pg_query($con,$sqlStatusOsDepois);

    if (pg_num_rows($resStatusOsDepois) > 0 ) {
        $status_da_os_depois = pg_fetch_result($resStatusOsDepois, 0, 'status_checkpoint');
        $nome_status = pg_fetch_result($resStatusOsDepois, 0, 'nome_status');
        $nome_consumidor = pg_fetch_result($resStatusOsDepois, 0, 'consumidor_nome');
    }

    $mensagemEmail = "";
    if (strlen($status_da_os_antes) == 0 && strlen($status_da_os_depois) > 0) {

        $mensagemEmail = "Prezado <b>{$nome_consumidor}</b>, a Ordem de Serviço teve o status atualizado para <b>{$nome_status}</b>";
    
    } elseif (strlen($status_da_os_antes) > 0 && strlen($status_da_os_depois) > 0) {

        if ($status_da_os_antes <> $status_da_os_depois) {

            $mensagemEmail = "Prezado <b>{$nome_consumidor}</b>, a Ordem de Serviço teve o status atualizado para <b>{$nome_status}</b>";

        }

    }

    if (strlen($mensagemEmail) > 0 && pg_num_rows($resStatusOsDepois) > 0) {
        $xos                            = pg_fetch_result($resStatusOsDepois, 0, 'os');
        $consumidor_email               = pg_fetch_result($resStatusOsDepois, 0, 'consumidor_email');
        $consumidor_email               = pg_fetch_result($resStatusOsDepois, 0, 'consumidor_email');

        if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {

            $assunto = "MQ Professional - Número da O.S. {$xos}";
            
            $mailTc->sendMail(
                $consumidor_email,
                $assunto,
                $mensagemEmail,
                'noreply@telecontrol.com.br'
            );

        }
    }

}

function verificaPreOsAtendimento() {
    global $con, $login_fabrica, $campos, $os;

    $hd_chamado = trim(addslashes($campos['os']['hd_chamado']));
    $oss        = [];

	if(!empty($hd_chamado)) {
		$sql = "SELECT os, produto FROM tbl_hd_chamado_item WHERE hd_chamado = {$hd_chamado} AND produto IS NOT NULL";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			for ($i=0; $i<pg_num_rows($res); $i++) {
				$os_i = pg_fetch_result($res, $i, 'os');

				if (empty($os_i) || !$os_i) {
					return false;
				}

				$oss[] = $os_i;
			}

			$finaliza = finalizaPreOsAtendimento($hd_chamado, $oss);

			return ($finaliza == true) ? true : false; 

		} else {
			return false; 
		}
	}

	return true;
}

function finalizaPreOsAtendimento($hd_chamado, $oss_finalizada = null) {
    global $con, $login_fabrica, $campos, $os;

    $hd_chamado = trim(addslashes($hd_chamado));

    if (empty($hd_chamado)) {
        return false;
    }

    $sql = "UPDATE tbl_hd_chamado SET 
                status         = 'Resolvido', 
                resolvido      = CURRENT_TIMESTAMP, 
                data_resolvido = CURRENT_TIMESTAMP
            WHERE hd_chamado   = {$hd_chamado}";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        return false;
    }   

    $comentario     = "Atendimento finalizado, pois foi aberta a Ordem de Serviço.";
    if (!empty($oss_finalizada)) {
        $ossAberta  = implode(',', $oss_finalizada);
        $comentario = (count($oss_finalizada) > 1) ? "Atendimento finalizado, pois foram abertas as Oss: {$ossAberta}" : "Atendimento finalizado, pois foi aberta a OS: {$ossAberta}";        
    }

    $sql = "INSERT INTO tbl_hd_chamado_item (
            hd_chamado,
            data,
            comentario,
            status_item
        ) VALUES (
            {$hd_chamado},
            CURRENT_TIMESTAMP,
            '$comentario',
            'Resolvido'
        )";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        return false;
    }   
}

/**
 * Função para validar a garantia da peça
 */
function valida_garantia_item_MQ() {
    global $con, $login_fabrica, $campos, $msg_erro;

    if (!in_array(verifica_tipo_atendimento(), ["Garantia Cortesia"])){
        $data_compra    = $campos["os"]["data_compra"];
        $data_abertura  = $campos["os"]["data_abertura"];
        $produto        = $campos["produto"]["id"];
        $pecas          = $campos["produto_pecas"];
        $tipo_at        = $campos["os"]["tipo_atendimento"];

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
}

$valida_garantia_item = "valida_garantia_item_MQ";

?>
