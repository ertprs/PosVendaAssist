<?
$regras["revenda_cidade"]["obrigatorio"] = false;
$regras["revenda_estado"]["obrigatorio"] = false;

$regras["data_abertura"] = array(
    "obrigatorio" => true,
    "regex"       => "date",
    "function"    => array("valida_data_abertura_roca")
);

$regras["consumidor_revenda"]["obrigatorio"] = true;

$antes_valida_campos = "antes_valida_campos_roca";

$auditorias_os_revenda = array(
    "auditoria_km_roca",
    "auditoria_produto_fora_garantia_roca"
);

if ($_REQUEST['consumidor_revenda'] == "S" OR $_REQUEST['consumidor_revenda'] == "R"){
    $regras["inscricao_estadual"]["obrigatorio"] = true;
}

$funcoes_fabrica = array(
    "grava_visita_roca",
    "grava_os_explodida_fabrica"
);

$funcoes_fabrica_email = array(
    "envia_email_visita"
);

$auditorias = array(
    "auditoria_os_reincidente_roca",
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria",
    "auditoria_pecas_excedentes",
    "auditoria_peca_sem_preco_roca"
);

$grava_anexo_os = "grava_anexo_os_roca";
$grava_anexo = "grava_anexo_roca";

function grava_anexo_os_roca ($os = null){
    global $campos, $s3, $con, $fabricaFileUploadOS, $login_fabrica, $os_revenda, $msg_erro;

    $os_revenda_campos = $campos["os_revenda"];
    if (empty($os)) {
        return false;
    }

    if (strlen(trim($os_revenda_campos)) == 0){
        $sql_os = "SELECT nota_fiscal FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_os = pg_query($con, $sql_os);
        
        if (pg_num_rows($res_os) > 0){
            $nota_fiscal_os = trim(pg_fetch_result($res_os, 0, 'nota_fiscal'));
        }
        
        $tdocs = new TDocs($con, $login_fabrica, "os");

        if (!$tdocs->insertAnexoRevenda($os, $os_revenda, $nota_fiscal_os)) {
            $msg_erro["msg"][] = traduz("Erro ao gravar anexos da OS#2 $os");
        }
    }
}

function grava_anexo_roca(){
    global $campos, $s3, $os_revenda, $fabricaFileUploadOS, $con, $login_fabrica, $msg_erro;

    $anexo_chave = $campos["anexo_chave"];
    $os_revenda_campos = $campos["os_revenda"];

    if ($anexo_chave != $os_revenda AND strlen(trim($os_revenda_campos)) == 0) {
        $tdocs = new TDocs($con, $login_fabrica, "revenda");

        $sql = "SELECT * 
                FROM tbl_tdocs 
                WHERE hash_temp = '{$anexo_chave}'";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            if (!$tdocs->updateHashTemp($anexo_chave, $os_revenda, "revenda")) {
                $msg_erro["msg"][] = traduz("Erro ao gravar anexos");
            }    
        }
    }
}

function valida_data_abertura_roca() {
    global $campos, $os;

    $data_abertura = $campos["data_abertura"];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        }
    }
}

function antes_valida_campos_roca() {
    global $con, $campos, $regras, $msg_erro, $login_fabrica;

    $consumidor_revenda     = $campos["consumidor_revenda"];
    $solicitar_deslocamento = $campos["solicitar_deslocamento"];
    $data_abertura          = $campos["data_abertura"]; 
    $os_cortesia            = $campos["os_cortesia"];

    if ($consumidor_revenda == "C"){
        unset($regras["revenda"]);

        if (!empty($campos["os_revenda"])){
            if (empty($campos["revenda_nome_consumidor"])){
                $msg_erro["campos"][] = "revenda_nome_consumidor";
            }
            if (empty($campos["revenda_cnpj_consumidor"])){
                $msg_erro["campos"][] = "revenda_cnpj_consumidor";
            }
        }

        if (!empty($campos["revenda_nome_consumidor"]) AND !empty($campos["revenda_cnpj_consumidor"])){
            $regras["revenda_cnpj_consumidor"]["function"] = array("valida_revenda_cnpj_roca");
        }

        $regras["revenda_cnpj"]["function"] = array("valida_consumidor_cpf");

        if (empty($campos["revenda_nome"])){
            $msg_erro["campos"][] = "revenda_nome";
        }

        if (empty($campos["revenda_cnpj"])){
            $msg_erro["campos"][] = "revenda_cnpj";
        }

        if (empty($campos["revenda_cep"])){
            $msg_erro["campos"][] = "revenda_cep";
        }

        if (empty($campos["revenda_estado"])){
            $msg_erro["campos"][] = "revenda_estado";
        }

        if (empty($campos["revenda_cidade"])){
            $msg_erro["campos"][] = "revenda_cidade";
        }

        if (empty($campos["revenda_bairro"])){
            $msg_erro["campos"][] = "revenda_bairro";
        }
        
        if (empty($campos["revenda_endereco"])){
            $msg_erro["campos"][] = "revenda_endereco";
        }
        
        if (empty($campos["revenda_numero"])){
            $msg_erro["campos"][] = "revenda_numero";
        }
        
        if (empty($campos["revenda_fone"])){
            $msg_erro["campos"][] = "revenda_fone";
        }
        
        if (empty($campos["revenda_email"])){
            $msg_erro["campos"][] = "revenda_email";
        }
    }

    if ($consumidor_revenda == "S"){
	 if (!empty($campos["revenda_nome_consumidor"]) AND !empty($campos["revenda_cnpj_consumidor"])){
            $regras["revenda_cnpj_consumidor"]["function"] = array("valida_revenda_cnpj_roca");
        }
    }

    if (strlen(trim($campos['os_revenda'])) > 0 AND strlen(trim($campos["consumidor_revenda"])) == 0){
        $msg_erro["campos"][] = "consumidor_revenda";
    }
	if (empty($campos["revenda_cep"])){
		$msg_erro["campos"][] = "revenda_cep";
	}

    $km_google = false;
    $tem_marca = true;
    $fora_garantia = false;
    $tem_def_reclamado = true;
    $tem_tipo_atendimento = true;

    foreach ($campos["produtos"] as $key => $dados) {
        unset($campos["produtos"]["__modelo__"]);
        
        if (strlen($dados["id"]) > 0 OR !empty($dados['qtde'])){
            $tipo_atendimento = $dados["tipo_atendimento"];
            $data_compra      = $dados["data_nf"];
            $produto          = $dados["id"];

            if (!empty($campos['os_revenda']) AND empty($tipo_atendimento)){
                $tem_tipo_atendimento = false;
                $msg_erro["campos"][] = "produto_$key";
            }

            if (!empty($tipo_atendimento)){
                $sql = "SELECT tipo_atendimento, km_google, fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0){
                    $km               = pg_fetch_result($res, 0, "km_google");
                    $at_fora_garantia = pg_fetch_result($res, 0, 'fora_garantia');
                }
            }

            if ($km == "t"){
                $km_google = true;
            }
            
            if (!empty($produto)){
                if (empty($dados["defeito_reclamado"])){
                    $tem_def_reclamado = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["marca"])){
                    $tem_marca = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

        		$cortesia_roca = verifica_os_cortesia_roca($dados['os_revenda_item']);
        		
        		if($cortesia_roca !== true){
        			$sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
        			$res = pg_query($con, $sql);
        			if (pg_num_rows($res) > 0) {
        			    $garantia = pg_fetch_result($res, 0, "garantia");
        			    
        			    if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura)) AND $at_fora_garantia != 't') {
        				    $fora_garantia = true;
        				    $msg_erro["campos"][] = "produto_$key";
        			    }
        			}    
        		}
            }
        }
    } 
        
    if ($tem_tipo_atendimento === false){
        $msg_erro["msg"][] = "Selecione o tipo de atendimento do produto";
    }

    if ($fora_garantia === true AND $os_cortesia != 't' AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Produto Fora de Garantia, Por Favor Selecione o Tipo de Atendimento Fora Garantia";
    }

    if ($tem_marca === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione a marca do produto";
    }

    if ($tem_def_reclamado === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione defeito reclamado do produto";
    }
    
    if($solicitar_deslocamento == "t"){
        if ($km_google === false){
            $msg_erro["msg"][] = "Nenhum tipo de atendimento como Garantia Domicílio selecionado";
        }
        
        if (empty($campos["data_agendamento"])){
            $msg_erro["msg"][] = "Preencha a data de agendamento";
            $msg_erro["campos"][] = "data_agendamento";
        }
    
        if (empty($campos["tecnico"])){
            $msg_erro["msg"][] = "Selecione um técnico";
            $msg_erro["campos"][] = "tecnico";
        }

        if (!strlen($campos["qtde_km_hidden"]) && !strlen($campos["qtde_km"])) {
            $msg_erro["msg"][]    = "O calculo de KM não pode ser vazio.";
            $msg_erro["campos"][] = "qtde_km";
        }

    }else if (empty($solicitar_deslocamento) && $km_google === true){
        $msg_erro["msg"][] = "Por favor selecione o campo solicitar deslocamento e preencha as informações do agendamento";
    }

    if ($campos["solicitar_deslocamento"] == "t" AND !empty($campos["qtde_km"])){
        $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE AND km_google IS TRUE";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0){
            $tipo_atendimento_os_mae = pg_fetch_result($res, 0, "tipo_atendimento");
            $campos["tipo_atendimento"] = $tipo_atendimento_os_mae;
        }
    }
    
    if (count($msg_erro["campos"]) AND $fora_garantia === false){
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
    }
}

function verifica_os_cortesia_roca($os_revenda_item){
	global $con,$campos,$login_fabrica;

	$sql = "SELECT tbl_os.os 
		FROM tbl_os
		JOIN tbl_os_campo_extra USING(os,fabrica) 
		WHERE tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.cortesia IS TRUE
		AND tbl_os_campo_extra.os_revenda_item = {$os_revenda_item}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		return true;
	}else{
		return false;
	}
}

function valida_revenda_cnpj_roca() {
    global $con, $campos;

    if (!empty($campos["revenda_cnpj_consumidor"])){
        $cnpj = preg_replace("/\D/", "", $campos["revenda_cnpj_consumidor"]);
    }else{
        $cnpj = preg_replace("/\D/", "", $campos["revenda_cnpj"]);
    }
    
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

	$sql = "SELECT revenda FROM tbl_revenda WHERE cnpj = '{$cnpj}';";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$campos['revenda'] = pg_fetch_result($res, 0, "revenda");
	}else{
		$sql = "INSERT INTO tbl_revenda(cnpj,nome) values('{$cnpj}','{$campos["revenda_nome_consumidor"]}') RETURNING revenda;";
		$res = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			throw new Exception("Erro ao incluir revenda");
		}else{
			$campos['revenda'] = pg_fetch_result($res, 0, "revenda");
		}
	}
    }
}

function valida_consumidor_cpf() {
    global $con, $campos;

    $cpf = preg_replace("/\D/", "", $campos["revenda_cnpj"]);

    if (strlen($cpf) > 0) {
        $sql = "SELECT fn_valida_cnpj_cpf('{$cpf}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("CPF do Consumidor $cpf é inválido");
        }
    }
}

function grava_os_revenda_fabrica() {
    global $con, $login_fabrica, $campos, $areaAdmin;

    $array_dados = array();

        $campos["revenda_cep"] = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
        $campos["revenda_cnpj"] = preg_replace("/\D/", "", $campos["revenda_cnpj"]);

        $campos["revenda_nome"] = str_replace("'", " ", $campos["revenda_nome"]);
        $campos["revenda_bairro"] = str_replace("'", " ", $campos["revenda_bairro"]);
        $campos["revenda_endereco"] = str_replace("'", " ", $campos["revenda_endereco"]);
        $campos["revenda_complemento"] = str_replace("'", " ", $campos["revenda_complemento"]);
        
        $array_dados = array(
            "consumidor_nome" => "'".$campos["revenda_nome"]."'",
            "consumidor_cpf" => "'".$campos["revenda_cnpj"]."'",
            "consumidor_cep" => "'".$campos["revenda_cep"]."'",
            "consumidor_estado" => "'".$campos["revenda_estado"]."'",
            "consumidor_cidade" => "'".$campos["revenda_cidade"]."'",
            "consumidor_bairro" => "'".$campos["revenda_bairro"]."'",
            "consumidor_endereco" => "'".$campos["revenda_endereco"]."'",
            "consumidor_numero" => "'".$campos["revenda_numero"]."'",
            "consumidor_complemento" => "'".$campos["revenda_complemento"]."'",
            "consumidor_fone" => "'".$campos["revenda_fone"]."'",
            "consumidor_email" => "'".$campos["revenda_email"]."'",
            "consumidor_revenda" => "'".$campos["consumidor_revenda"]."'",
            "visita_por_km" => ((!empty($campos['solicitar_deslocamento']) AND $campos["solicitar_deslocamento"] == "t") ? "'".$campos['solicitar_deslocamento']."'" : "null")
        );

    if(!empty($campos['os_revenda'])){
	    $sql = "SELECT campos_extra FROM tbl_os_revenda WHERE os_revenda = {$campos['os_revenda']}";
	    $res = pg_query($con, $sql);
	    
	    if (pg_num_rows($res) > 0){
		$campos_extra = pg_fetch_result($res, 0, "campos_extra");
		$campos_extra = json_decode($campos_extra, true);
	    }
    }

    if (!empty($campos['revenda_celular'])){
        $campos_extra["revenda_celular"] = $campos['revenda_celular'];
        
    }

    if (!empty($campos['inscricao_estadual'])){
        $campos_extra["inscricao_estadual"] = $campos["inscricao_estadual"];
    }

    if($areaAdmin === true){
	    if (!empty($campos["os_cortesia"]) AND $campos["os_cortesia"] == "t"){
		$array_dados["cortesia"] = "'".$campos['os_cortesia']."'";
	    }else{
		$array_dados["cortesia"] = "'f'";
	    }
    }

    if (count($campos_extra)){
        $json_campos_extra = json_encode($campos_extra);
        $array_dados["campos_extra"] = "'".$json_campos_extra."'";
    }
    
    return $array_dados;
}

function grava_os_fabrica() {
    global $campos;
    
        $campos["revenda_cep"] = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
        
        $dados = array(
            "consumidor_nome" => "'".$campos["revenda_nome"]."'",
            "consumidor_cpf" => "'".$campos["revenda_cnpj"]."'",
            "consumidor_cep" => "'".$campos["revenda_cep"]."'",
            "consumidor_estado" => "'".$campos["revenda_estado"]."'",
            "consumidor_cidade" => "'".$campos["revenda_cidade"]."'",
            "consumidor_bairro" => "'".$campos["revenda_bairro"]."'",
            "consumidor_endereco" => "'".$campos["revenda_endereco"]."'",
            "consumidor_numero" => "'".$campos["revenda_numero"]."'",
            "consumidor_complemento" => "'".$campos["revenda_complemento"]."'",
            "consumidor_fone" => "'".$campos["revenda_fone"]."'",
            "consumidor_email" => "'".$campos["revenda_email"]."'"
        );

	if ($campos["consumidor_revenda"] == "R"){
		$campos["revenda"] = "null";
		unset($campos["revenda_nome"]);
		unset($campos["revenda_cnpj"]);
	}
	return $dados;
}

function auditoria_km_roca (){

    global $con, $login_fabrica, $campos, $os_revenda;
    
    $sql = "
        SELECT osr.os_revenda
        FROM tbl_os_revenda osr
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = osr.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        JOIN tbl_posto_fabrica pf ON pf.posto = osr.posto AND pf.fabrica = {$login_fabrica}
        JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
        WHERE osr.fabrica = {$login_fabrica}
        AND osr.os_revenda = {$os_revenda}
        AND ta.fora_garantia IS NOT TRUE
        AND ta.km_google IS TRUE
        AND tp.tipo_revenda IS NOT TRUE ";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0 && verifica_auditoria_unica_revenda("tbl_auditoria_status.km = 't' AND tbl_auditoria_os_revenda.observacao ILIKE '%auditoria de KM%'", $os_revenda) === true) {
        
        $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }
        
        $qtde_km = $campos["qtde_km"];
        $qtde_km_anterior = $campos["qtde_km_hidden"];
        
        if (!strlen($campos["qtde_km_hidden"])) {
            $campos["qtde_km_hidden"] = $campos["qtde_km"];
        }
        
        if ($qtde_km > 60 AND $qtde_km != $campos["qtde_km_hidden"]){
            $sql = "
                INSERT INTO tbl_auditoria_os_revenda (os_revenda, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os_revenda}, {$auditoria_status}, 'OS em auditoria de KM, KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
            ";
        }else if ($qtde_km > 200){
            $sql = "
                INSERT INTO tbl_auditoria_os_revenda (os_revenda, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os_revenda}, {$auditoria_status}, 'OS em auditoria de KM', false);
            ";
        }
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD001");
        }
    }
}

function auditoria_produto_fora_garantia_roca() {
    global $con, $login_fabrica, $campos, $os_revenda, $areaAdmin;

    $produtos = $campos["produtos"];
    $fora_garantia  = false;
    $data_abertura = $campos["data_abertura"];

    foreach ($produtos as $key => $value) {
        if (!empty($value["id"]) AND $value["nota_fiscal"] != "semNota"){
            $produto       = $value["id"];
            $data_compra   = $value["data_nf"];
            
            $sql = "SELECT garantia
                    FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $garantia = pg_fetch_result($res, 0, "garantia");
                
                if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                    $fora_garantia = true;
                    break;
                }
            }
        }
    }

    if ($fora_garantia && verifica_auditoria_unica_revenda("tbl_auditoria_status.produto = 't' AND tbl_auditoria_os_revenda.observacao ILIKE '%Produtos fora de garantia%'", $os_revenda) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os_revenda (os_revenda, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os_revenda}, {$auditoria_status}, 'OS em auditoria de Produto, Produtos fora de garantia', true)";
        $res = pg_query($con, $sql);
   
        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD002");
        }
    }
}

function update_os_explodida(){
    global $con, $campos, $os_revenda, $login_fabrica, $_RESULT, $areaAdmin;

    $dadosOsRevendaAntes = $_RESULT;
    $dadosOsRevendaAntes["revenda_cnpj"] = preg_replace("/[\.\-\/]/", "", $dadosOsRevendaAntes['revenda_cnpj']);
    unset(
        $dadosOsRevendaAntes["produtos"], $dadosOsRevendaAntes["produtos_print"], $dadosOsRevendaAntes["os_revenda"], $dadosOsRevendaAntes["sua_os"],  
        $dadosOsRevendaAntes["tipo_atendimento"], $dadosOsRevendaAntes["tipo_atendimento_descricao"], $dadosOsRevendaAntes["notas_fiscais_adicionadas"],
        $dadosOsRevendaAntes["posto_codigo"], $dadosOsRevendaAntes["posto_nome"]
    );
    
    $dadosOsRevendaPost = $campos;
    $dadosOsRevendaPost["revenda_cnpj"] = preg_replace("/[\.\-\/]/", "", $dadosOsRevendaPost['revenda_cnpj']);
    unset(
        $dadosOsRevendaPost["produtos"], $dadosOsRevendaPost["anexo"], $dadosOsRevendaPost["anexo_s3"], $dadosOsRevendaPost["anexo_chave"], $dadosOsRevendaPost["anexo_notas"], 
        $dadosOsRevendaPost["notas_revenda"], $dadosOsRevendaPost["notas_fiscais_adicionadas"], $dadosOsRevendaPost["sua_os"], $dadosOsRevendaPost["os_revenda"], 
        $dadosOsRevendaPost["tipo_atendimento"], $dadosOsRevendaPost["qtde_km_hidden"], $dadosOsRevendaPost["fabrica_id"], $dadosOsRevendaPost["posto_codigo"],
        $dadosOsRevendaPost["posto_nome"]
    );
    $diffOsRevenda = array_diff($dadosOsRevendaPost, $dadosOsRevendaAntes);

    $dadosOsItemAntes = $_RESULT["produtos"];
    $dadosOsItemPost  = $campos["produtos"];
    unset($dadosOsItemPost["__modelo__"]);
    
    $diffOsRevendaItem = array();
    foreach ($dadosOsItemPost as $key => $value) {
        $diffOsRevendaItem[] = array_diff($value, $dadosOsItemAntes[$key]);
    }

    if (count($diffOsRevenda) > 0 OR coun($diffOsRevendaItem) > 0){
        if (count($diffOsRevenda) > 0){
            $sql = " SELECT os, campos_adicionais FROM tbl_os_campo_extra WHERE os_revenda = $os_revenda AND fabrica = $login_fabrica ";
            $res = pg_query($con, $sql);
           
            if (pg_num_rows($res) > 0){
                $array_os = pg_fetch_all_columns($res, 0);
                $campos_extras = pg_fetch_all($res);

                list($dia, $mes, $ano) = explode("/", $dadosOsRevendaPost['data_abertura']);
                
                $dadosOsRevendaPost['data_abertura'] = $ano.'-'.$mes.'-'.$dia;
                $dadosOsRevendaPost['revenda_cep'] = str_replace("-", "", $dadosOsRevendaPost['revenda_cep']);
                $dadosOsRevendaPost['revenda_nome'] = str_replace("'", " ", $dadosOsRevendaPost['revenda_nome']);
                $dadosOsRevendaPost['revenda_cidade'] = str_replace("'", " ", $dadosOsRevendaPost['revenda_cidade']);
                $dadosOsRevendaPost['revenda_endereco'] = str_replace("'", " ", $dadosOsRevendaPost['revenda_endereco']);
                $dadosOsRevendaPost['revenda_bairro'] = str_replace("'", " ", $dadosOsRevendaPost['revenda_bairro']);
                $dadosOsRevendaPost['revenda_complemento'] = str_replace("'", " ", $dadosOsRevendaPost['revenda_complemento']);

                $sqlUpdateOS = "
                    UPDATE tbl_os SET 
                        posto = ".$dadosOsRevendaPost['posto_id'].",
                        data_abertura = '".$dadosOsRevendaPost['data_abertura']."',
                        consumidor_nome = '".$dadosOsRevendaPost['revenda_nome']."',";
                
                if ($campos["consumidor_revenda"] == "R"){       
                    $sqlUpdateOS .="
                        revenda_cnpj = '".$dadosOsRevendaPost['revenda_cnpj']."',
                        revenda_nome = '".$dadosOsRevendaPost['revenda_nome']."',
                        revenda_fone = '".$dadosOsRevendaPost['revenda_fone']."', ";
                }     

                $sqlUpdateOS .= "
                        consumidor_cidade = '".$dadosOsRevendaPost['revenda_cidade']."',
                        consumidor_estado = '".$dadosOsRevendaPost['revenda_estado']."',
                        consumidor_fone = '".$dadosOsRevendaPost['revenda_fone']."',
                        consumidor_celular = '".$dadosOsRevendaPost['revenda_celular']."',
                        obs = '".$dadosOsRevendaPost['obs']."',
                        consumidor_cpf = '".$dadosOsRevendaPost['revenda_cnpj']."',
                        consumidor_revenda = '".$dadosOsRevendaPost['consumidor_revenda']."',
                        consumidor_endereco = '".$dadosOsRevendaPost['revenda_endereco']."',
                        consumidor_numero = '".$dadosOsRevendaPost['revenda_numero']."',
                        consumidor_cep = '".$dadosOsRevendaPost['revenda_cep']."',
                        consumidor_bairro = '".$dadosOsRevendaPost['revenda_bairro']."',
                        consumidor_complemento = '".substr($dadosOsRevendaPost['revenda_complemento'], 0, 25)."',
                        consumidor_email = '".$dadosOsRevendaPost['revenda_email']."'
                    WHERE fabrica = $login_fabrica
                    AND os IN (".implode(',', $array_os).")
                ";
                $resUpdateOs = pg_query($con, $sqlUpdateOS);
                
                foreach ($campos_extras as $key => $value) {
                    $campo_up = $value['campos_adicionais'];
                    $campo_up = json_decode($campo_up, true);
                    $campo_up["inscricao_estadual"] = $dadosOsRevendaPost['inscricao_estadual'];

                    $update_campos_adicionais = json_encode($campo_up);

                    $sql_up = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$update_campos_adicionais}' WHERE os = {$value['os']}";
                    $res_up = pg_query($con, $sql_up);
                }
                
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar ordem de serviço #UP01");
                }
            }
        }
        
        if (count($diffOsRevendaItem) > 0){
            foreach ($dadosOsItemPost as $key => $value) {

                $parametros_adicionais_os_item = array();

                if (!empty($value["instalacao_publica"])){
                    $parametros_adicionais_os_item["instalacao_publica"] = $value["instalacao_publica"];
                }

                if (!empty($value["defeito_constatado"])){
                    $parametros_adicionais_os_item["defeito_constatado"] = $value["defeito_constatado"];
                }
            
                if (!empty($value['info_pecas'])){
                    $parametros_adicionais_os_item["info_pecas"] = $value["info_pecas"];
                }
            
                if (!empty($value['defeito_constatado_grupo'])){
                    $parametros_adicionais_os_item["defeito_constatado_grupo"] = $value["defeito_constatado_grupo"];
                }

                if (count($parametros_adicionais_os_item)){
                    $insert_parametros_adicionais = json_encode($parametros_adicionais_os_item);
                }

                if ($value['nota_fiscal'] == "semNota"){
                    $value['nota_fiscal'] = "";
                }else{
                    $value['nota_fiscal'] = trim($value['nota_fiscal']);
                }
                
                if (!empty($value["os_revenda_item"])){
                    $sqlUpadateORI ="
                        UPDATE tbl_os_revenda_item SET
                            produto = ".( (!empty($value['id'])) ? $value['id'] : "NULL" ).",
                            nota_fiscal = ".( (!empty($value['nota_fiscal'])) ? "'".$value['nota_fiscal']."'" : "NULL" ).",
                            data_nf = ".( (!empty($value['data_nf'])) ? "'".formata_data($value['data_nf'])."'" : "NULL" ).",
                            tipo_atendimento = ".( (!empty($value['tipo_atendimento'])) ? $value['tipo_atendimento'] : "NULL" ).",
                            defeito_reclamado = ".( (!empty($value['defeito_reclamado'])) ? $value['defeito_reclamado'] : "NULL" ).",
                            marca = ".( (!empty($value['marca'])) ? $value['marca'] : "NULL" ).",
                            parametros_adicionais = ".((!empty($insert_parametros_adicionais)) ? "'".$insert_parametros_adicionais."'" : "null")."
                        WHERE os_revenda_item = ".$value['os_revenda_item'];
                    $resUpdateORI = pg_query($con, $sqlUpadateORI);

                    if (strlen(pg_last_error()) > 0){
                        throw new Exception("Erro ao gravar OS #UP02");
                    }

                    $sqlOs = "
                        SELECT oce.os, op.os_produto
                        FROM tbl_os_campo_extra oce 
                        LEFT JOIN tbl_os_produto op ON op.os = oce.os
                        WHERE oce.os_revenda_item = ".$value['os_revenda_item']."
                        AND oce.fabrica = $login_fabrica ";
                    $resOs = pg_query($con, $sqlOs);

                    if (pg_num_rows($resOs) > 0){
                        for ($j=0; $j < pg_num_rows($resOs); $j++) { 

                            $os = pg_fetch_result($resOs, $j, "os");
                            $os_produto = pg_fetch_result($resOs, $j, "os_produto");

                            if (empty($os_produto) AND strlen(trim($value['id'])) > 0){
                                $sql = "
                                    INSERT INTO tbl_os_produto (
                                        os, produto, serie, defeito_constatado
                                    ) VALUES (
                                        {$os}, {$value['id']}, ".((!empty($value['serie'])) ? "'".$value['serie']."'" : "null").",".((!empty($value['defeito_constatado'])) ? "'".$value['defeito_constatado']."'" : "null")."
                                    )RETURNING os_produto;
                                ";
                                $res = pg_query($con, $sql);
                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception("Erro ao gravar a OS Revenda #UP03");
                                }
                                $os_produto = pg_fetch_result($res, 0, "os_produto");
                            }
			    
            			    if($areaAdmin === true){
            				    $campo_cortesia = ", cortesia = ".( (!empty($campos['os_cortesia']) AND $campos['os_cortesia'] == 't') ? "'".$campos['os_cortesia']."'" : "'f'" )."";
            			    }
                            
                            $sqlUpOs = "
                            UPDATE tbl_os SET 
                                produto = ".( (!empty($value['id'])) ? $value['id'] : "NULL" ).",
                                data_nf = ".( (!empty($value['data_nf'])) ? "'".formata_data($value['data_nf'])."'" : "NULL" ).",
                                nota_fiscal = ".( (!empty($value['nota_fiscal'])) ? "'".trim($value['nota_fiscal'])."'" : "NULL" ).",
                                tipo_atendimento = ".( (!empty($value['tipo_atendimento'])) ? $value['tipo_atendimento'] : "NULL" ).",
                				defeito_constatado = ".( (!empty($value['defeito_constatado'])) ? $value['defeito_constatado'] : "NULL" ).",
                                defeito_constatado_grupo = ".( (!empty($value['defeito_constatado_grupo'])) ? $value['defeito_constatado_grupo'] : "NULL" )."
                				$campo_cortesia
                                WHERE os = $os AND fabrica = $login_fabrica 
                                AND finalizada IS NULL";
                            $resUpOs = pg_query($con, $sqlUpOs);
                            
                            if (strlen(pg_last_error()) > 0){
                                throw new Exception("Erro ao gravar OS #UP04");
                            }

                            if (!empty($value['defeito_constatado']) AND strlen($os_produto) > 0){
                                $sqlUpOSP = "UPDATE tbl_os_produto 
                                             SET defeito_constatado = ".$value['defeito_constatado']." 
                                             FROM tbl_os
                                             WHERE os_produto = $os_produto
                                             AND tbl_os.os = tbl_os_produto.os
                                             AND tbl_os.finalizada IS NULL";
                                $resUpOSP = pg_query($con, $sqlUpOSP);
                            }

                            $sqlUpOCE = "
                                UPDATE tbl_os_campo_extra SET 
                                    marca = ".( (!empty($value['marca'])) ? $value['marca'] : "NULL" )."
                                WHERE os = $os";
                            $resUpOCE = pg_query($con, $sqlUpOCE);

                            if (strlen(pg_last_error()) > 0){
                                throw new Exception("Erro ao gravar OS #UP04");
                            }
			
			    if (strlen($os_produto) > 0){

				    $sqlOsItem = "SELECT os_item FROM tbl_os_item WHERE os_produto = $os_produto AND fabrica_i = $login_fabrica";
				    $resOsItem = pg_query($con, $sqlOsItem);

                            	    if (pg_num_rows($resOsItem) == 0){
					
					if (!empty($value["info_pecas"])){
					    $info_pecas = json_decode($value["info_pecas"], true);

					    foreach ($info_pecas as $key_pecas => $value_pecas) {
						$sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$value_pecas['servico_realizado']}";
						$res = pg_query($con, $sql);

						$troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

						if ($troca_de_peca == "t") {
						    $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$value_pecas['id_peca']}";
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

						$sql = "INSERT INTO tbl_os_item (
							    os_produto,
							    peca,
							    qtde,
							    servico_realizado,
							    peca_obrigatoria,
							    admin
							) VALUES (
							    {$os_produto},
							    {$value_pecas['id_peca']},
							    {$value_pecas['qtde_lancada']},
							    {$value_pecas['servico_realizado']},
							    {$devolucao_obrigatoria},
							    {$login_admin}
							) RETURNING os_item";
						$res = pg_query($con, $sql);
						if (strlen(pg_last_error()) > 0) {
						    throw new Exception("Erro ao gravar a OS Revenda #9");
						}

						call_user_func('auditoria_peca_critica', $os, $value);
						call_user_func('auditoria_pecas_excedentes', $os, $value);
						call_user_func('auditoria_peca_sem_preco_roca', $os, $value);
					    }
					}
                            	   }
			     }
                        }
                    }
                }
            }
        }
    }
}

function grava_visita_roca() {
    global $con, $login_fabrica, $login_admin, $os_revenda, $campos, $msg_erro, $areaAdmin, $_REQUEST;
    
    $periodo                    = $campos["periodo_visita"];
    $tecnico                    = $campos["tecnico"];
    $data_agendamento           = $campos["data_agendamento"]; 
    $data_visita_realizada      = $campos["data_visita_realizada"]; 
    $solicitar_deslocamento     = $campos["solicitar_deslocamento"]; 

    if ($campos["solicitar_deslocamento"] ==  "t"){

        if (empty($data_agendamento)){
            throw new Exception("Preencha a data de agendamento");
        }

        if (!empty($data_agendamento)){
            list($da, $ma, $ya) = explode("/", $data_agendamento);
            $aux_data_agendamento = "{$ya}-{$ma}-{$da}";
            if (!checkdate($ma, $da, $ya)) {
                throw new Exception("Data agendamento inválida");
            }
        }

        if (!empty($data_visita_realizada)) {
            list($dv, $mv, $yv) = explode("/", $data_visita_realizada);
            list($da, $ma, $ya) = explode("/", $data_agendamento);

            if (!checkdate($mv, $dv, $yv)) {
                throw new Exception("Data da visita realizada inválida");
            } else {
                $aux_visita_realizada = "{$yv}-{$mv}-{$dv}";
                if (strtotime($aux_visita_realizada) < strtotime($aux_data_agendamento)) {
                    throw new Exception("Data da visita realizada não pode ser menor que a data agendamento");
                }
            }
        }

        $sql = "
            SELECT
                tecnico_agenda,
                tecnico,
                periodo,
                TO_CHAR(data_agendamento,'DD/MM/YYYY') AS data_agendamento,
                confirmado,
                data_cancelado,
                (SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} AND os_revenda = {$os_revenda}) AS qtde
            FROM tbl_tecnico_agenda
            WHERE fabrica = $login_fabrica
            AND os_revenda = $os_revenda
            ORDER BY tecnico_agenda DESC LIMIT 1 ";
        $res = pg_query($con, $sql);

        $ordem            = pg_fetch_result($res, 0, "qtde");
        $data_agendada    = pg_fetch_result($res, 0, "data_agendamento");
        $tecnico_agenda   = pg_fetch_result($res, 0, "tecnico_agenda");
        $data_confirmada  = pg_fetch_result($res, 0, "confirmado");
        $periodo_agendado = pg_fetch_result($res, 0, "periodo");
        $tecnico_agendado = pg_fetch_result($res, 0, "tecnico");
        $data_cancelado   = pg_fetch_result($res, 0, "field");
        $ordem += 1;

        $insert = true;

        if ($data_agendada == $data_agendamento AND $tecnico == $tecnico_agendado AND $periodo == $periodo_agendado AND empty($data_confirmada) AND !empty($data_visita_realizada)){
            $insert = false;
        }

        if (pg_num_rows($res) > 0 AND empty($data_cancelado) AND empty($data_confirmada)){
            $insert = false;
        }

        if ($insert === true and pg_num_rows($res) == 0){
            if (!empty($data_visita_realizada)){
                $campo_confirmado = ", confirmado";
                $valor_confirmado = ", '$aux_visita_realizada'";
            }
            $sqlAgenda = "
                INSERT INTO tbl_tecnico_agenda (fabrica,os_revenda,data_agendamento,tecnico,ordem,periodo $campo_confirmado)
                VALUES ({$login_fabrica},{$os_revenda},'$aux_data_agendamento',$tecnico,$ordem,'$periodo' $valor_confirmado)";
            $res = pg_query($con,$sqlAgenda);
        }else{
            if (!empty($aux_visita_realizada)){
                $update_campo_confirmado = ", confirmado = '$aux_visita_realizada'";
            }
            
            $sqlAgenda = "
                UPDATE tbl_tecnico_agenda SET
                    data_agendamento = '$aux_data_agendamento',
                    tecnico = $tecnico,
                    periodo = '$periodo'
                    $update_campo_confirmado
                WHERE fabrica = $login_fabrica
                AND tecnico_agenda = $tecnico_agenda";
            $res = pg_query($con,$sqlAgenda);
        }
        
        if (strlen(pg_last_error()) > 0){
            throw new Exception("Erro ao gravar agendamento");
        }
    }
}

function envia_email_visita() {
    global $con, $login_fabrica, $campos, $os_revenda, $msg_erro, $comunicatorMirror;
    
    $data_agendamento = $campos["data_agendamento"];
    $tecnico          = $campos["tecnico"];
    $periodo_visita   = $campos["periodo_visita"];
    $consumidor_email = $campos["revenda_email"];

    if (!empty($data_agendamento) AND !empty($tecnico)){
        $sql = "SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND tecnico = {$tecnico}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $nome_tecnico = pg_fetch_result($res, 0, "nome");
        }

        $sql = "SELECT tbl_posto.nome
                FROM tbl_posto 
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                WHERE tbl_posto.posto = {$campos["posto_id"]}";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) > 0){
            $nome_posto = pg_fetch_result($res, 0, "nome");
            $codigo_posto = pg_fetch_result($res, 0, "codigo_posto");
        }

        if (!empty($consumidor_email)){
            $titulo_email = "Agendamento Ordem Serviço - $os_revenda";
            $corpo_email = "Informamos que o Posto Autorizado $nome_posto, agendou a visita do Técnico: $nome_tecnico " .
            "para reparar o seu produto no dia: $data_agendamento no período da: $periodo_visita .";

            $comunicatorMirror->post($consumidor_email, utf8_encode("$titulo_email"), utf8_encode("$corpo_email"), "smtp@posvenda");
        }
    }
}

function auditoria_os_reincidente_roca($os, $array_produto) {
    global $con, $login_fabrica, $campos, $os_reincidente, $os_reincidente_numero;

    $posto = $campos['posto_id'];
    $nota_fiscal = trim($array_produto["nota_fiscal"]);
    $revenda_cnpj = $campos["revenda_cnpj"];
    $produto = $array_produto["id"];

    if (!empty($produto)){
        $cond_produto = " AND tbl_os_produto.produto = {$produto} ";
    }

    $sql = "
        SELECT 
            tbl_os.os,
            tbl_os_campo_extra.os_revenda 
        FROM tbl_os 
        JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica 
        WHERE tbl_os.fabrica = {$login_fabrica} 
        AND tbl_os.os = {$os} 
        AND tbl_os.os_reincidente IS NOT TRUE";
    $res = pg_query($con, $sql);
  
    if(pg_num_rows($res) > 0){
        $os_revenda = pg_fetch_result($res, 0, 'os_revenda');
        $sql = "SELECT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.posto = $posto
                AND tbl_os.os < {$os}
                AND tbl_os_campo_extra.os_revenda < $os_revenda
                AND tbl_os.nota_fiscal = '{$nota_fiscal}'
				AND length(tbl_os.nota_fiscal) > 0 
				AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $revenda_cnpj)."'
				AND length(tbl_os.revenda_cnpj) > 0 
                $cond_produto
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

function auditoria_peca_critica($os, $array_produto) {
    global $con, $login_fabrica;
    
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
        }
    }
}

function auditoria_troca_obrigatoria($os, $array_produto) {
    global $con, $login_fabrica;

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

function auditoria_pecas_excedentes($os, $array_produto) {
    global $con, $login_fabrica, $qtde_pecas;
    
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

            if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os)) {
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

function auditoria_peca_sem_preco_roca ($os, $array_produto){
    global $con, $campos, $login_fabrica, $msg_erro;

    $posto_id = $campos['posto_id'];
   

    $param_adicionais = $array_produto["parametros_adicionais"];
    $param_adicionais = json_decode($param_adicionais,true);

    if (!empty($param_adicionais["info_pecas"])){
        $info_pecas = json_decode($param_adicionais["info_pecas"], true);
        
        foreach ($info_pecas as $key => $peca) {
            $peca_id = $peca["id_peca"];

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

                    if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça sem preço%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça sem preço%'", $os)) {
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
}

function grava_os_explodida_fabrica($array_produto){
    global $campos, $con, $login_fabrica, $msg_erro;

    $array_dados = array();
    $campos_os = "";
    $valor_os = "";

    // $ins_tipo_atendimento = ((!empty($array_produto['tipo_atendimento'])) ? $array_produto['tipo_atendimento'] : "null");
    
    foreach ($campos["produtos"] as $key_p => $value_p) {
        if ($value_p["id"] == $array_produto["id"] AND $value_p["os_revenda_item"] == $array_produto["os_revenda_item"]){
            $array_produto["defeito_constatado"] = $value_p["defeito_constatado"];
            $array_produto["defeito_constatado_grupo"] = $value_p["defeito_constatado_grupo"];
            $array_produto["info_pecas"] = $value_p["info_pecas"];
        }
    }
    
    if (!empty($campos["os_cortesia"]) AND $campos["os_cortesia"] == "t"){
        $array_dados = array_merge($array_dados, array("cortesia" => "'".$campos["os_cortesia"]."'"));
    }

    if (!empty($array_produto["defeito_constatado_grupo"])){
        $array_dados = array_merge($array_dados, array("defeito_constatado_grupo" => $array_produto["defeito_constatado_grupo"]));
    }

    if (!empty($array_produto["defeito_constatado"])){
        $array_dados = array_merge($array_dados, array("defeito_constatado" => $array_produto["defeito_constatado"]));
    }
    return $array_dados;
}

function valida_consumidor_revenda () {
    global $campos;

    if (!empty($campos["consumidor_revenda"]) AND ($campos["consumidor_revenda"] == "C" OR $campos["consumidor_revenda"] == "S")){
                       
        if (!empty($campos["revenda_nome_consumidor"]) OR !empty($campos["revenda_cnpj_consumidor"])){
            $campos["revenda_nome"] = $campos["revenda_nome_consumidor"];
            $campos["revenda_cnpj"] = $campos["revenda_cnpj_consumidor"];
            
            if (empty($campos['revenda'])){
                $campos["revenda"] = "null";
            }
        }else{
            $campos["revenda"] = "null";
            $campos["revenda_nome"] = "";
            $campos["revenda_fone"] = "";
            $campos["revenda_cnpj"] = "";
        }
    }
}

function grava_os_item ($os, $os_produto, $os_revenda, $os_revenda_item, $array_produto) {
    global $con, $login_fabrica, $campos, $login_admin;

    unset($instalacao_publica);

    $param_adicionais = $array_produto["parametros_adicionais"];
    $param_adicionais = json_decode($param_adicionais,true);
    
    if (!empty($array_produto["campos_extra"])){
        $campos_extra = $array_produto["campos_extra"];
        $campos_extra = json_decode($campos_extra,true);
        $insert_campo_extra["inscricao_estadual"] = $campos_extra["inscricao_estadual"];
    }
    
    $insert_campo_extra["instalacao_publica"] = $param_adicionais["instalacao_publica"];

    $intert_campo_extra = json_encode($insert_campo_extra);

    $sql = "
        INSERT INTO tbl_os_campo_extra (
            os, fabrica, os_revenda, os_revenda_item, marca, campos_adicionais
        ) VALUES (
            {$os}, {$login_fabrica}, {$os_revenda}, {$os_revenda_item}, ".((!empty($array_produto['marca'])) ? $array_produto['marca'] : "null").",'$intert_campo_extra'
        );
    ";
    $res = pg_query($con, $sql);

    if (!empty($param_adicionais["info_pecas"])){
        $info_pecas = json_decode($param_adicionais["info_pecas"], true);
        
        foreach ($info_pecas as $key_pecas => $value_pecas) {
            $sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$value_pecas['servico_realizado']}";
            $res = pg_query($con, $sql);

            $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

            if ($troca_de_peca == "t") {
                $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$value_pecas['id_peca']}";
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

            $sql = "INSERT INTO tbl_os_item (
                        os_produto,
                        peca,
                        qtde,
                        servico_realizado,
                        peca_obrigatoria,
                        admin
                    ) VALUES (
                        {$os_produto},
                        {$value_pecas['id_peca']},
                        {$value_pecas['qtde_lancada']},
                        {$value_pecas['servico_realizado']},
                        {$devolucao_obrigatoria},
                        {$login_admin}
                    ) RETURNING os_item";
            $res = pg_query($con, $sql);
        }
    }
}
