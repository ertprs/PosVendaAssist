<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include '../class/AuditorLog.php';

$admin_privilegios="cadastros";

if($login_fabrica == 134){
    $tema = traduz("Serviço Realizado");
    $temaPlural = traduz("Serviços Realizados");
    $temaMPlural = traduz("SERVIÇOS REALIZADOS");
    $temaMaiusculo = traduz("SERVIÇO REALIZADO");
}else{
    $tema = traduz("Defeito Constatado");
    $temaPlural = traduz("Defeitos Constatados");
    $temaMPlural = traduz("DEFEITOS CONSTATADOS");
    $temaMaiusculo = traduz("DEFEITO CONSTATADO");
}

function pg_query_with_log($con,$sql){
	$clearSql = trim($sql);
	$clearSql =  preg_replace('@[\t\n ]+@',' ',$clearSql);
	if(preg_match('/^insert/i',$clearSql)){
		$matches = array();
		$regex = '@(insert|INSERT) (into|INTO) (tbl_defeito_constatado).*@';
		if(preg_match($regex,$clearSql)){
			$table = 'tbl_defeito_constatado';
			$column = 'defeito_constatado';
			$res = pg_query($con,$sql);
			//$oid = pg_last_oid($res);
			$oid = pg_fetch_result($res, 0, 0);
			$result = pg_query_params($con,'SELECT '.$column.' FROM '.$table.' WHERE defeito_constatado = $1 LIMIT 1;',array($oid));
			$result = pg_fetch_assoc($result);
			$newId = $result[$column];
			$after = getDefeito($newId);
			logDefeito('insert',$newId,array(),$after);
			return $res;
		}
	}
	if(preg_match('/^update/i',$clearSql)){
		$matches = array();
		$regex = '@(update|UPDATE) (tbl_defeito_constatado) (SET|set)(.*)(WHERE|where)(?<where>.*)@';
		preg_match($regex,$clearSql,$matches);
		$whereClause = $matches['where'];
		$explodeWhere = preg_split('@ (AND|and) @',$whereClause);
		$id = null;
		foreach($explodeWhere as $equals){
			list($column,$value) = explode('=',$equals);
			$column = trim($column);
			$value = trim($value);
			if(!preg_match('@^(tbl_defeito_constatado\.)(defeito_constatado|DEFEITO_CONSTATADO)?$@',$column)){
				continue;
			}
			$id = $value;
		}
		if(empty($id)){
		    return pg_query($con,$sql);
		}
		$before = getDefeito($id);
		$res = pg_query($con,$sql);
		$after = getDefeito($id);
		logDefeito('update',$id,$before,$after);
		return $res;
	}

    return pg_query($con,$sql);

}

$defeito_constatado = (strlen($_GET['defeito_constatado']) > 0) ? trim($_GET['defeito_constatado']) : trim($_POST['defeito_constatado']);

if(isset($_GET['operacao'])){
    if($_GET['operacao'] == 'ok'){
        $msg_success = true;
        $msg_success_excluir = true;
    }
}

if(isset($_GET['excluir'])){
    $msg_success_excluir = false;

    $defeito_constatado = $_GET['excluir'];

    $sql = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE defeito_constatado = {$defeito_constatado} LIMIT 1;";
    $res = pg_query_with_log($con,$sql);
    if(pg_num_rows($res) > 0){
        header("Location: ".$PHP_SELF."?defeito_constatado=$defeito_constatado&msg_excluir=true");
    }else{
        $sql = "SELECT defeito_constatado,codigo FROM tbl_defeito_constatado WHERE defeito_constatado = {$defeito_constatado} AND fabrica = {$login_fabrica};";
        $res = pg_query_with_log($con,$sql);
        if(pg_num_rows($res)){
            $codigo = pg_result($res,0,codigo);
            $defeito_constatado = pg_result($res,0,defeito_constatado);
            $sql = "SELECT defeito FROM tbl_defeito WHERE codigo_defeito = '{$codigo}' AND fabrica = {$login_fabrica};";
            $res = pg_query_with_log($con,$sql);
            if(pg_num_rows($res)){
                $defeito = pg_result($res,0,defeito);

                pg_query_with_log($con,"BEGIN");
                $sql =  "UPDATE tbl_defeito_constatado SET fabrica = 0 WHERE defeito_constatado = {$defeito_constatado} AND fabrica = {$login_fabrica};";
                pg_query_with_log($con,$sql);

                if(!pg_last_error($con)){
                    $sql = "UPDATE tbl_defeito SET fabrica = 0 WHERE defeito = {$defeito} AND fabrica = {$login_fabrica};";
                    pg_query_with_log($con,$sql);
                    if(!pg_last_error($con)){
                        $msg_success = true;
                        $msg_success_excluir = true;
                        pg_query_with_log($con,"COMMIT");
                        header("Location: ".$PHP_SELF."?operacao=ok");

                    }else{
                        $msg_erro['msg'][] = "Erro ao excluir ".$tema;
                        pg_query_with_log($con,"ROLLBACK");
                    }
                }else{
                    $msg_erro['msg'][] = "Erro ao excluir ".$tema;
                    pg_query_with_log($con,"ROLLBACK");
                }

            }

        }else{
            header("Location: ".$PHP_SELF);
        }

    }
}

if ($_POST["btn_acao"] == "submit") {
    $orientacao                 = array_values($_POST['orientacao']);
    $codigo                     = trim($_POST["codigo"]);
    $defeito_constatado_grupo   = trim($_POST["defeito_constatado_grupo"]);
    $item_servico               = $_POST['item_servico'];
    $descricao                  = trim($_POST["descricao"]);
    $ativo                      = array_values($_POST["ativo"]);
    $lancar_peca                = array_values($_POST['lancar_peca']);

    if ($login_fabrica == 151) {
        $anexos_obrigatorios = $_POST["anexos_obrigatorios"];
    }    
    if($login_fabrica == 30){
        $auditorLogInserir = new AuditorLog("insert");
        $auditorLogAlterar = new AuditorLog();         
    }
   
    if($login_fabrica == 173){
    	$posicao_peca		= array_values($_POST['posicao_peca']);
    }

    if (in_array($login_fabrica, array(169,170))) {
        $lista_garantia = $_POST["lista_garantia"];
    }

    if (strlen($defeito_constatado_grupo)==0) {
        if ($login_fabrica == 175){
            $msg_erro["msg"][] = traduz("Informe o grupo do defeito");
            $msg_erro["campos"][] = "defeito_constatado_grupo";
        }else{
            $defeito_constatado_grupo = 'null';
        }
    }

    if ($login_fabrica == 142 && !empty($codigo)) {
        if (!empty($defeito_constatado)) {
            $whereId = "AND defeito_constatado NOT IN({$defeito_constatado})";
        }

        $sql = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND UPPER(codigo) = UPPER('{$codigo}') {$whereId}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $msg_erro["msg"][] = traduz("O código informado já está sendo usado");
        }
    }

    if($login_fabrica == 19 AND empty($defeito_constatado)){
        if (!empty($defeito_constatado)) {
            $whereId = "AND defeito_constatado NOT IN({$defeito_constatado})";
        }

        $sql = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND UPPER(codigo) = UPPER('{$codigo}') {$whereId}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $msg_erro["msg"][] = traduz("O código informado já está sendo usado");
        }
    }

    if(strlen($codigo)==0) {
        if($login_fabrica == 35){
            $msg_erro["msg"]["obg"] = traduz("Por favor insira o código do % ", null, null, [$tema]);
            $msg_erro["campos"][] = "codigo";
        }else{
            $codigo = "''";
        }
    } else {
        if (in_array($login_fabrica, array(169,170)) && strlen($defeito_constatado) > 0) {
            if (in_array($codigo, array("CONV","INST"))) {
                $msg_erro["msg"][] = traduz("Defeitos constatados de Conversão/Instalação não podem ser alterados");
            }
        }
        $codigo = "'".$codigo."'";
    }

    if (strlen($descricao) == 0) {
        $msg_erro["msg"]["obg"] = traduz("Por favor insira a descrição do % ", null, null, [$tema]);
        $msg_erro["campos"][] = "descricao";
    }

    if (in_array($login_fabrica,array(30,43,94,115,143,162,165))) {
        $mao_de_obra = trim($_POST["mao_de_obra"]);
        if (strlen($mao_de_obra) == 0 && !in_array($login_fabrica, array(162,165))) {
            $msg_erro["msg"]["obg"] = traduz("Insira o valor da mão-de-obra");
            $msg_erro["campos"][] = "mao_de_obra";
        } else if ($login_fabrica == 165) {
            if(strlen($mao_de_obra) == 0 && $lancar_peca == false){
                $msg_erro["msg"]["obg"] = traduz("Insira o valor da mão-de-obra");
                $msg_erro["campos"][] = "mao_de_obra";
                $readonly = false;
            } else if ($lancar_peca == true) {
                $readonly = true;
                $mao_de_obra = 0;
            }
        }

        if (preg_match("/[a-zA-Z]/",$mao_de_obra)){
            $msg_erro["msg"]["obg"] = traduz("Verifique o valor da mão-de-obra");
            $msg_erro["campos"][] = "mao_de_obra";
        }
        if (preg_match('/[,.]/',$mao_de_obra)){
            $mao_de_obra =  str_replace(',', '.',str_replace('.','',$mao_de_obra));
        }
    }

    if (in_array($login_fabrica, array(173))) {
        $mao_de_obra = "null";
    }

    if (in_array($login_fabrica,array(162))) {
	    if(strlen($mao_de_obra) == 0 && !is_array($lancar_peca)){
		  $msg_erro["msg"]["obg"] = traduz("Marque a opção Lançar Peça ou informe valor de Mão de Obra");
	    }
    }

    if (is_array($ativo)) {
        $ativo = 'true';
    }else{
        $ativo = 'false';
    }

	if (is_array($orientacao)) {
        $orientacao = 'true';
    }else{
        $orientacao = 'false';
    }

    if (is_array($lancar_peca)) {
        $lancar_peca = 'true';
    }else{
        $lancar_peca = 'false';
    }

    $posicao_peca = (is_array($posicao_peca))? 'true' : 'false';
    
    
    if($login_fabrica == 173 && $posicao_peca == 'true'){
	
     	$sqlDcGrupo = "SELECT defeito_constatado_grupo FROM tbl_defeito_constatado_grupo WHERE fabrica = {$login_fabrica} AND grupo_codigo = 'PC'";
    	$resDcGrupo = pg_query($con,$sqlDcGrupo);

    	if(pg_num_rows($resDcGrupo) > 0){
    		$defeito_constatado_grupo = pg_fetch_result($resDcGrupo,0,0);
    	}
    }

    $campos_adicionais = [];
    
    if ($login_fabrica == 151 && !empty($anexos_obrigatorios)) {
        $campos_adicionais["anexos_obrigatorios"] = $anexos_obrigatorios;
    }
    
    if(strlen($defeito_constatado) == 0){

        if ($login_fabrica == 175){
            $sql = "SELECT defeito_constatado
                    FROM tbl_defeito_constatado
                    WHERE fabrica = {$login_fabrica}
                    AND descricao = '{$descricao}'
                    AND codigo = {$codigo}
                    AND defeito_constatado_grupo = {$defeito_constatado_grupo}";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0){
                $msg_erro["msg"][] = traduz("Defeito ja cadastrado");
            }
        }

        if (!count($msg_erro["msg"])) {

            if ($login_fabrica == 151) {
                $campos_adicionais = json_encode($campos_adicionais);
            }

            $res = pg_query_with_log($con,"BEGIN");
            if (in_array($login_fabrica,array(30,43,94,115,143))) {
                $sql = "INSERT INTO tbl_defeito_constatado (
                                descricao,
                                codigo,
                                ativo,
                                lancar_peca,";
                if ($login_fabrica == 30) {
                    $sql .= "
                                mao_de_obra,
                                lista_garantia,
                                versao_lista,
                                esmaltec_item_servico,
                    ";
                } else if (in_array($login_fabrica, array(94,115,143,173))) {
                    $sql .="
                                mao_de_obra,
                                orientacao,
                    ";
                }

                $sql .="
                                admin,
                                data_atualizacao,
                                fabrica";

                $sql .="
                            ) VALUES (
                                '$descricao',
                                $codigo,
                                $ativo,
                                $lancar_peca,";
                if ($login_fabrica == 30) {
                    $sql .= "
                                $mao_de_obra,
                                '$lista_garantia',
                                '$versao_lista',
                                $item_servico,
                    ";
                } else if (in_array($login_fabrica, array(94,115,143,173))) {
                    $sql .= "
                                $mao_de_obra,
                                '$orientacao',
                    ";
                }

                $sql .="
                                $login_admin,
                                current_timestamp,
                                $login_fabrica";

                    $sql .=") RETURNING defeito_constatado;";   
            } else {
                $sql = "INSERT INTO tbl_defeito_constatado (
                                    descricao,
                                    codigo,
                                    ativo,
                                    defeito_constatado_grupo,
                                    fabrica";
                                    if (in_array($login_fabrica,array(162,165))) {
                                        $sql .= ",mao_de_obra";
                                    }
                                    if (in_array($login_fabrica,array(50,86,91,104,108,111,120,125,129,151,157,162,165,167,203)) OR $defeito_constatado_obriga_lancar_peca) { //HD 733415
                                        $sql .= ",lancar_peca";
                                    }
                                    if (in_array($login_fabrica, array(169,170))) {
                                        $sql .= "
                                            , lista_garantia
                                        ";
                                    }
                                    
                                    if ($login_fabrica == 151) {

                                        $sql .= ", campos_adicionais";
                                    }

                            $sql .="
                                ) VALUES (
                                    '$descricao',
                                    $codigo,
                                    $ativo,
                                    $defeito_constatado_grupo,
                                    $login_fabrica";
                                    if (in_array($login_fabrica,array(162,165))) {
                                        $sql .= ",$mao_de_obra";
                                    }

                                    if (in_array($login_fabrica,array(50,86,91,104,108,111,120,125,129,151,157,162,165,167,203)) OR $defeito_constatado_obriga_lancar_peca) { //HD 733415
                                        $sql .= ",$lancar_peca";
                                    }
                                    if (in_array($login_fabrica, array(169,170))) {
                                        $lista_garantia = (!empty($lista_garantia)) ? "'{$lista_garantia}'" : "null";
                                        $sql .= "
                                            , {$lista_garantia}
                                        ";
                                    }
                    
                                    if ($login_fabrica == 151) {
                                        $sql .= ", '{$campos_adicionais}' ";
                                    }
                            $sql .="
                                ) RETURNING defeito_constatado;";
            }

            if($login_fabrica == 131){

                $sqlQ = "SELECT * FROM tbl_defeito_constatado WHERE TRIM(codigo) ILIKE TRIM('{$codigo}') AND fabrica = {$login_fabrica};";

                $res = pg_query_with_log($con,$sqlQ);
                if(pg_num_rows($res) > 0 ){
                    $msg_erro["msg"][] = traduz("Codigo de defeito ja cadastrado");
                }else{

                    $res = pg_query_with_log($con,$sql);
                    if(pg_last_error($con)){
                        $msg_erro["msg"][] = pg_errormessage($con);
                    }


                    if(pg_errormessage($con) == ""){
                        $sql = "INSERT INTO tbl_defeito(descricao,codigo_defeito,fabrica) VALUES ('{$descricao}','{$codigo}',{$login_fabrica})";
                        $res = pg_query_with_log($con,$sql);
                        if(pg_last_error($con)){
                            $msg_erro["msg"][] = pg_errormessage($con);
                        }else{
                            $msg_success = true;
                        }

                    }
                }
            } else {
                //die(nl2br($sql));
                $res = pg_query_with_log($con,$sql);

                if($login_fabrica == 30){
                    $aux_esmaltec_defeito_constatado = pg_fetch_result($res, 0, defeito_constatado);   

                    //$sqlAuditorInserir = "SELECT descricao, codigo, ativo, lancar_peca, mao_de_obra, lista_garantia, versao_lista, esmaltec_item_servico, admin, data_atualizacao, fabrica FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$aux_esmaltec_defeito_constatado} ";

                    $sqlAuditorInserir = "SELECT 
                                            dc.descricao, 
                                            dc.codigo, 
                                            dc.ativo, 
                                            dc.lancar_peca, 
                                            dc.mao_de_obra, 
                                            dc.lista_garantia, 
                                            dc.versao_lista, 
                                            eis.descricao AS esmaltec_item_servico, 
                                            ad.nome_completo, 
                                            TO_CHAR(dc.data_atualizacao, 'DD/MM/YYYY HH24:MI:SS') AS data_atualizacao, 
                                            dc.fabrica 
                                            FROM tbl_defeito_constatado dc
                                            LEFT JOIN tbl_admin ad ON ad.admin = dc.admin
                                            LEFT JOIN tbl_esmaltec_item_servico eis ON eis.esmaltec_item_servico = dc.esmaltec_item_servico
                                            WHERE dc.fabrica = {$login_fabrica}
                                            AND dc.defeito_constatado = {$aux_esmaltec_defeito_constatado}";

                    $auditorLogInserir->retornaDadosSelect($sqlAuditorInserir); 

                    $auditorLogInserir->enviarLog('insert', 'tbl_defeito_constatado',"{$login_fabrica}*{$aux_esmaltec_defeito_constatado}");
                }

                if(pg_last_error($con)){
                    $msg_erro["msg"][] = pg_errormessage($con);
                }else{

                    if (isset($moduloTraducao)) {

                        $xdefeito_constatado = pg_fetch_result($res, 0, 'defeito_constatado');

                        $descricao_espanhol = $_POST['descricao_espanhol'];
                        $descricao_ingles   = $_POST['descricao_ingles'];

                        if (!empty($descricao_espanhol)) {
                            $sqlIdioma = "
                            INSERT INTO tbl_defeito_constatado_idioma (
                                defeito_constatado,
                                descricao,
                                idioma
                            ) VALUES (
                                {$xdefeito_constatado},
                                '{$descricao_espanhol}',
                                'ES'
                            );
                            ";
                            pg_query($con, $sqlIdioma);
                        }

                        if (!empty($descricao_ingles)) {
                            $sqlIdioma = "
                            INSERT INTO tbl_defeito_constatado_idioma (
                                defeito_constatado,
                                descricao,
                                idioma
                            ) VALUES (
                                {$xdefeito_constatado},
                                '{$descricao_ingles}',
                                'en-US'
                            );
                            ";

                            pg_query($con, $sqlIdioma);
                        }
                    }

                    $msg_success = true;
                    if($login_fabrica == 19){
                        $success_msg = traduz("Defeito cadastrado com sucesso");
                    }
                }

            }

        }

    }else{

        if (!count($msg_erro["msg"])) {
            if (in_array($login_fabrica,array(30,43,94,115,143))){
                if($login_fabrica == 30){
                    //$sqlAuditorLogAlterar = "SELECT descricao, mao_de_obra, lista_garantia, versao_lista, admin, data_atualizacao, ativo, lancar_peca, esmaltec_item_servico FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$defeito_constatado}";
                    $sqlAuditorLogAlterar = "SELECT 
                                            dc.descricao, 
                                            dc.codigo, 
                                            dc.ativo, 
                                            dc.lancar_peca, 
                                            dc.mao_de_obra, 
                                            dc.lista_garantia, 
                                            dc.versao_lista, 
                                            eis.descricao AS esmaltec_item_servico, 
                                            ad.nome_completo, 
                                            TO_CHAR(dc.data_atualizacao, 'DD/MM/YYYY HH24:MI:SS') AS data_atualizacao, 
                                            dc.fabrica 
                                            FROM tbl_defeito_constatado dc
                                            LEFT JOIN tbl_admin ad ON ad.admin = dc.admin
                                            LEFT JOIN tbl_esmaltec_item_servico eis ON eis.esmaltec_item_servico = dc.esmaltec_item_servico
                                            WHERE dc.fabrica = {$login_fabrica}
                                            AND dc.defeito_constatado = {$defeito_constatado}";                    

                    //die(nl2br($sqlAuditorLogAlterar));

                    $auditorLogAlterar->retornaDadosSelect($sqlAuditorLogAlterar);
                }

                $sql = "UPDATE  tbl_defeito_constatado
                        SET     descricao       = '$descricao',";
                if (in_array($login_fabrica, array(94,143))) {
                    $sql .= "
                                codigo          = $codigo,
                    ";
                }
                if($login_fabrica == 30 && $_SERVER["SERVER_NAME"] != "conquistar.telecontrol.com.br"){
                    $sql .="
                                mao_de_obra     = $mao_de_obra,
                                lista_garantia  = '$lista_garantia',
                                versao_lista    = '$versao_lista',";
                } else if (in_array($login_fabrica, array(94,115,143,173))) {
                    $sql .="
                                mao_de_obra     = $mao_de_obra,
                                orientacao      = $orientacao,";
                }

                $sql .="
                                admin            = $login_admin,
                                data_atualizacao = current_timestamp,
                                ativo            = $ativo,
                                lancar_peca      = $lancar_peca ";
                if ($login_fabrica == 30) {
                    $sql .= ",
                                esmaltec_item_servico = $item_servico";
                }
                $sql .= " WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
                        AND     tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
            }else{

                if ($login_fabrica == 151) {
                    
                    $getAdicionais = "SELECT campos_adicionais 
                                        FROM tbl_defeito_constatado 
                                        WHERE defeito_constatado = $defeito_constatado";
                    $resAdicionais = pg_query($con, $getAdicionais);

                    $adicionais_original = pg_fetch_result($resAdicionais, 0, "campos_adicionais");

                    $campos = json_decode($adicionais_original,1);
                    $campos['anexos_obrigatorios'] = $anexos_obrigatorios;
                    $adicionais_original = json_encode($campos);
                }

                $sql = "UPDATE  tbl_defeito_constatado
                        SET     descricao   = '$descricao',
                                codigo      = $codigo,
                                ativo       = '$ativo' ";
                if (in_array($login_fabrica,array(50,51,81,86,91,104,108,111,114,120,125,129,151,153,157,162,165,167,203)) OR $defeito_constatado_obriga_lancar_peca) { //HD 733415
                    $sql .= ",  lancar_peca = '$lancar_peca' ";
                }
                
                if ($login_fabrica == 151) {
                    $sql .= ",  campos_adicionais = '$adicionais_original' ";
                }

                if(in_array($login_fabrica,array(162,165)) AND strlen($mao_de_obra) > 0){
                    $sql .= ",  mao_de_obra = $mao_de_obra ";
                }

                if (in_array($login_fabrica, array(169,170))) {
                    $lista_garantia = (!empty($lista_garantia)) ? "'{$lista_garantia}'" : "null";
                    $sql .= "
                        , lista_garantia = {$lista_garantia}
                    ";
                }

                if(in_array($login_fabrica,array(52,158,165,173,175,178))){
                    $sql .= ",  defeito_constatado_grupo = $defeito_constatado_grupo ";
                }
                $sql .= "
                        WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
                        AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
	    }
            //die(nl2br($sql));
            $res = pg_query_with_log($con,$sql);

            if($login_fabrica == 30){
                //$sqlAuditorLogAlterar = "SELECT descricao, mao_de_obra, lista_garantia, versao_lista, admin, data_atualizacao, ativo, lancar_peca, esmaltec_item_servico FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$defeito_constatado}";
                //die(nl2br($sqlAuditorLogAlterar));
                $sqlAuditorLogAlterar = "SELECT 
                                        dc.descricao, 
                                        dc.codigo, 
                                        dc.ativo, 
                                        dc.lancar_peca, 
                                        dc.mao_de_obra, 
                                        dc.lista_garantia, 
                                        dc.versao_lista, 
                                        eis.descricao AS esmaltec_item_servico, 
                                        ad.nome_completo, 
                                        TO_CHAR(dc.data_atualizacao, 'DD/MM/YYYY HH24:MI:SS') AS data_atualizacao, 
                                        dc.fabrica 
                                        FROM tbl_defeito_constatado dc
                                        LEFT JOIN tbl_admin ad ON ad.admin = dc.admin
                                        LEFT JOIN tbl_esmaltec_item_servico eis ON eis.esmaltec_item_servico = dc.esmaltec_item_servico
                                        WHERE dc.fabrica = {$login_fabrica}
                                        AND dc.defeito_constatado = {$defeito_constatado}";                    
                
                $auditorLogAlterar->retornaDadosSelect($sqlAuditorLogAlterar);

                $auditorLogAlterar->enviarLog('update', 'tbl_defeito_constatado',"{$login_fabrica}*{$defeito_constatado}");
            }

            if (strlen(pg_last_error()) > 0) {
                $msg_erro["msg"][] = traduz("Erro ao gravar defeito constatado");
            }

            if($login_fabrica == 131){
                $sql = "UPDATE tbl_defeito SET descricao = '$descricao' WHERE codigo_defeito = $codigo AND fabrica = $login_fabrica;";
                $res = pg_query_with_log($con,$sql);
            }

        }
    }

    if (!pg_last_error() && empty($msg_erro["msg"])) {
        pg_query_with_log($con, "COMMIT");
        if($login_fabrica == 131){
            $msg_success = true;

            $erroAux = implode('', $msg_erro['msg']);
            if(strlen(trim($erroAux)) > 0){
                $_RESULT['codigo']    = $_POST['codigo'];
                $_RESULT['descricao'] = $_POST['descricao'];
            }

        }else{

            if (isset($moduloTraducao)) {

                $descricao_espanhol = $_POST['descricao_espanhol'];
                $descricao_ingles   = $_POST['descricao_ingles'];

                if (!empty($descricao_espanhol)) {

                    $sqlIdioma = "
                    SELECT 
                        tbl_defeito_constatado_idioma.defeito_constatado
                    FROM tbl_defeito_constatado_idioma
                    WHERE defeito_constatado = $defeito_constatado
                    AND idioma = 'ES';
                ";
                
                    $resIdioma = pg_query($con,$sqlIdioma);
                    
                    if (pg_num_rows($resIdioma) > 0) {

                        $sql2 = "
                            UPDATE tbl_defeito_constatado_idioma SET
                                descricao = '{$descricao_espanhol}'
                            WHERE defeito_constatado = {$defeito_constatado}
                            AND idioma = 'ES';
                        ";
                    }else{
                        $sql2 = "
                            INSERT INTO tbl_defeito_constatado_idioma (
                                defeito_constatado,
                                descricao,
                                idioma
                            ) VALUES (
                                {$defeito_constatado},
                                '{$descricao_espanhol}',
                                'ES'
                            );
                        ";
                    }

                    $res = pg_query($con,$sql2);
                }

                if (!empty($descricao_ingles)) {
                    $sqlIdioma = "
                        SELECT 
                            tbl_defeito_constatado_idioma.defeito_constatado
                        FROM tbl_defeito_constatado_idioma
                        WHERE defeito_constatado = $defeito_constatado
                        AND idioma = 'en-US';
                    ";
                
                    $resIdioma = pg_query($con,$sqlIdioma);
                    
                    if (pg_num_rows($resIdioma) > 0) {

                        $sql2 = "
                            UPDATE tbl_defeito_constatado_idioma SET
                                descricao = '{$descricao_espanhol}'
                            WHERE defeito_constatado = {$defeito_constatado}
                            AND idioma = 'en-US';
                        ";
                    }else{
                        $sql2 = "
                            INSERT INTO tbl_defeito_constatado_idioma (
                                defeito_constatado,
                                descricao,
                                idioma
                            ) VALUES (
                                {$defeito_constatado},
                                '{$descricao_ingles}',
                                'en-US'
                            );
                        ";
                    }

                    $res = pg_query($con,$sql2);
                }
            }

            $msg_success = true;
            if($login_fabrica == 19){
                $success_msg = traduz("Defeito alterado com sucesso");
            }
        }

        unset($_POST);
    } else {
        //$msg_erro["msg"][] = "Erro na inclusão/alteração de defeito";
        pg_query_with_log($con, "ROLLBACK");
    }
}

if (!empty($_GET['defeito_constatado'])){
    $_REQUEST['defeito_constatado'] = $_GET['defeito_constatado'];
    if (in_array($login_fabrica,array(30,43,94,115,143))){
        # HD 23943 - Francisco Ambrozio (23/7/08) - adicionado campos "tabela de preço" e
        #   "versão de tabela de preço" para a Esmaltec
        $sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.codigo,
                        tbl_defeito_constatado.ativo,
                        tbl_defeito_constatado.mao_de_obra,
                        tbl_defeito_constatado.lista_garantia,
                        tbl_defeito_constatado.versao_lista,
                        tbl_defeito_constatado.descricao,
                        tbl_defeito_constatado.lancar_peca,
                        tbl_defeito_constatado.orientacao,
                        tbl_defeito_constatado.esmaltec_item_servico AS item_servico
                FROM    tbl_defeito_constatado
                WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
                AND     tbl_defeito_constatado.defeito_constatado = ".$_REQUEST['defeito_constatado']."
          ORDER BY      tbl_defeito_constatado.ativo DESC,tbl_defeito_constatado.descricao";
    }else{
        $sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.codigo   ,
                        tbl_defeito_constatado.ativo   ,
                        tbl_defeito_constatado.descricao,
                        tbl_defeito_constatado.mao_de_obra,
                        tbl_defeito_constatado.lista_garantia,
                        tbl_defeito_constatado.lancar_peca, 
                        (
                            SELECT descricao 
                            FROM tbl_defeito_constatado_idioma
                            WHERE tbl_defeito_constatado_idioma.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                            AND tbl_defeito_constatado_idioma.idioma = 'ES'
                            LIMIT 1
                        ) as descricao_espanhol,
                        (
                            SELECT descricao 
                            FROM tbl_defeito_constatado_idioma
                            WHERE tbl_defeito_constatado_idioma.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                            AND tbl_defeito_constatado_idioma.idioma = 'en-US'
                            LIMIT 1
                        ) as descricao_ingles";
        if (in_array($login_fabrica, array(52, 165,175,178))) {
            $sql .= ",  defeito_constatado_grupo ";
        }

        if (in_array($login_fabrica,array(86,108,111,129))){
            $sql .= ",  tbl_defeito_constatado.lancar_peca ";
        }
        $sql .= "
                FROM    tbl_defeito_constatado
                WHERE   tbl_defeito_constatado.fabrica            = $login_fabrica
                AND     tbl_defeito_constatado.defeito_constatado = ".$_REQUEST['defeito_constatado']."
          ORDER BY       tbl_defeito_constatado.ativo DESC,tbl_defeito_constatado.descricao";
    }
    $res = pg_query_with_log($con,$sql);

    if (pg_numrows($res) > 0) {
        $_RESULT['defeito_constatado'] = trim(pg_result($res,0,defeito_constatado));
        $_RESULT['codigo']             = trim(pg_result($res,0,codigo));
        $_RESULT['descricao']          = trim(pg_result($res,0,descricao));
        $_RESULT['descricao_espanhol'] = trim(pg_result($res,0,descricao_espanhol));
        $_RESULT['descricao_ingles']   = trim(pg_result($res,0,descricao_ingles));

        if (in_array($login_fabrica, array(52,165,175,178))) {
            $_RESULT['defeito_constatado_grupo'] = trim(pg_result($res,0,defeito_constatado_grupo));
        }

	    if (in_array($login_fabrica,array(30,43,94,115,143,162,165))){
            $_RESULT['mao_de_obra']    = trim(pg_result($res,0,mao_de_obra));
            $_RESULT['mao_de_obra']	   = number_format($_RESULT['mao_de_obra'],2,",",".");
            $_RESULT['lista_garantia'] = trim(pg_result($res,$x,lista_garantia));
            $_RESULT['versao_lista']   = trim(pg_result($res,$x,versao_lista));
            $_RESULT['item_servico']   = trim(pg_result($res,$x,item_servico));
			$_RESULT['orientacao']     = trim(pg_result($res,$x,orientacao));

			if(trim(pg_result($res,0,orientacao)) == 't'){
    	        $_RESULT['orientacao'] = 1;
        	}
        }

        if (in_array($login_fabrica,array(86,91,104,108,111,125,129,120,151,153,157,162,165,167,203)) || $defeito_constatado_obriga_lancar_peca){
            if(trim(pg_result($res,0,lancar_peca)) == 't'){
                $_RESULT['lancar_peca'] = 1;
                $readonly = true;
            }
        }

        if(trim(pg_result($res,0,ativo)) == 't'){
            $_RESULT['ativo'] = 1;
        }

        if (in_array($login_fabrica, array(169,170))) {
            $_RESULT["lista_garantia"] = pg_fetch_result($res, 0, "lista_garantia");
        }
    }
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE ".$temaMPlural;
if(strlen($defeito_constatado) == 0){
    $title_page = traduz("Cadastro");
}else{
    $title_page = traduz("Alteração de Cadastro");
}

include "cabecalho_new.php";
$plugins = array(
    "tooltip",
    "price_format",
    "dataTable",
    "shadowbox",
    "multiselect"
);
include "plugin_loader.php";
?>

<script type="text/javascript">
    $(function (){
        
        $("#anexos_obrigatorios").multiselect({
            selectedText: "selecionados # de #",
        });

        $("#btnPopover").popover();

        $("#btnInativos").click(function(){
            $(".inativo").toggle();
        });

        $.dataTableLoad({ table: "#tabela_defeito_constatado" });
        Shadowbox.init();
        $(document).on("click", ".visualizalog", function() {
            var defeito = $(this).data("defeito");
            Shadowbox.open({
                content:    "relatorio_log_alteracao_new.php?parametro=tbl_defeito_constatado&id="+defeito,
                player: "iframe",                
                width:  1200,
                height: 700
            });
        });

    });

<?php if($login_fabrica == 165){ ?>
    $(function (){
        $("input[name^='lancar_peca']").click(function(){

            if($("input[name^='lancar_peca']").is(':checked')){
                 $('#mao_de_obra').prop('readonly', true);
            }else{
                $('#mao_de_obra').prop('readonly', false);
            }
        });
    });
<?php } ?>
</script>
<style type="text/css">
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.inativo {
    display: none;
}
</style>

<?php

if ($msg_success) {
    if($login_fabrica == 131){
        if($msg_success_excluir == true){
            ?>
            <div class="alert alert-success">
                <h4><?=traduz('Defeito excluído com sucesso')?></h4>
                <?=implode("<br />", $msg_erro["msg"])?>
            </div>
            <?php
        }else{
        ?>
            <div class="alert alert-success">
                <h4><?=traduz('Defeito cadastrado com sucesso')?></h4>
                <?=implode("<br />", $msg_erro["msg"])?>
            </div>
        <?php
        }
    }else{
    ?>
        <div class="alert alert-success">
            <?php if($login_fabrica == 19){ ?>
                <h4><?=$success_msg?></h4>
            <?php }else{?>
                <h4><?=traduz('Defeito cadastrado com sucesso')?></h4>
            <?php } ?>
        </div>
    <?php
    }

}else{
    $msg_erro["msg"] = array_filter($msg_erro["msg"]);

    if(isset($_GET['msg_excluir']) and ($login_fabrica == 131)){
        ?>
        <div class="alert alert-error">
            <h4><?=traduz('O defeito ja está relacionado com uma ou mais ordens de serviço, não pode ser excluído')?></h4>
        </div>
        <?php
    }elseif (count($msg_erro["msg"]) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
    <?php
    }
}
if( $login_fabrica == 101 ) { // HD 677430
        $sql= "SELECT   descricao, defeito_constatado
               FROM     tbl_defeito_constatado
               WHERE    fabrica = $login_fabrica
               AND      orientacao IS TRUE";
        $res = pg_query_with_log($con,$sql);
        $contador_res = pg_num_rows($res);
        if (pg_num_rows($res) ) {
            for($i=0;$i<$contador_res; $i++ ) {
                $defeitos_orientacao[] = pg_result($res,$i,0);
                $defeitos_orientacao_cons[] = pg_result($res,$i,1);
            }
            $defeitos_orientacao = implode (', ',$defeitos_orientacao);
?>
            <div class="texto_avulso">
                <?=traduz('Para o(s) Defeito(s) Constatado(s) <b>%</b> será utilizada Mão de Obra Diferenciada, conforme cadastrado no cadastro de Produtos',null,null,[$defeitos_orientacao])?>
            </div>
<?php
        }
    } //FIM HD 677430
?>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<?
$hiddens = array(
    "defeito_constatado"
);

$inputs = array(
    "codigo" => array(
        "span"      => 4,
        "label"     => traduz("Código"),
        "type"      => "input/text",
        "width"     => 5,
        "maxlength" => 10
    ),
    "descricao" => array(
        "span"      => 4,
        "label"     => traduz("Descrição"),
        "type"      => "input/text",
        "width"     => 12,
        "required"  => true
    )
);

 if(($login_fabrica == 131) and isset($_GET['defeito_constatado'])){
     $inputs['codigo']['readonly'] = true;
 }

if($login_fabrica == 35 or $login_fabrica == 131 or $login_fabrica == 19){
    $add = array("required" => true);
    $inputs["codigo"] += $add;
}

if($login_fabrica == 101 && isset($defeitos_orientacao_cons) && in_array($defeito_constatado, $defeitos_orientacao_cons) ) {
    $ativo = array("values" => true);
    array_push($hiddens,$ativo);
}else if (!in_array($login_fabrica,array(50,51,81,91,108,111,114,120,123,125,129))){

    if($login_fabrica <> 30){
        $inputs["ativo"] = array(
                "span"  => 4,
                "type"  => "checkbox",
                "width" => 4,
                "checks" => array(
                    "1" => traduz("Ativo")
                )
        );
    }
}else{
    $inputs["ativo"] = array(
            "span"  => 4,
            "type"  => "checkbox",
            "width" => 4,
            "checks" => array(
                "1" => traduz("Ativo")
            )
    );
}

if($login_fabrica == 94){
    $inputs["orientacao"] = array(
            "span"  => 4,
            "type"  => "checkbox",
            "width" => 4,
            "checks" => array(
                "1" => traduz("Orientação")
            )
    );
}

if (in_array($login_fabrica,array(50,51,81,86,91,104,108,111,114,120,123,125,129,151,153,157,162,165,167,203)) OR $defeito_constatado_obriga_lancar_peca){
    $inputs["lancar_peca"] = array(
            "span"  => 4,
            "type"  => "checkbox",
            "class" => "tac",
            "width" => 12,
            "checks" => array(
                "1" => traduz("Lançar Peça (Peças no cadastro de OS)")
            ),
            // "popover" => array(
            //     "id" => "btnPopover",
            //     "msg"   => "Marque para definir se este defeito constatado obrigará o lançamento de peças no cadastro de OS"
            // )
    );
}

if($login_fabrica == 173){
    $inputs["posicao_peca"] = array(
            "span"  => 4,
            "type"  => "checkbox",
            "checks" => array(
                "1" => traduz("Posição do componente na placa")
            )
    );
}

if (in_array($login_fabrica, array(169, 170))) {
    $inputs["lista_garantia"] = array(
        "span"  => 10,
        "type"  => "radio",
        "label"     => "Tipo",
        "class" => "tac",
        "width" => 12,
        "radios" => array(
            "" => traduz("Produto em Garantia"),
            "fora_garantia" => traduz("Consumidor sem Nota Fiscal"),
            "sem_defeito"   => traduz("Produto sem Defeito")
        )
    );
}

if (isset($moduloTraducao)) {
    if ($moduloTraducao['es']) {
        $inputs["descricao_espanhol"] = array(
            "span"      => 4,
            "label"     => traduz("Descrição Espanhol"),
            "type"      => "input/text",
            "width"     => 12,
            "required"  => false
        );
    }

    if ($moduloTraducao['en-US']) {
        $inputs["descricao_ingles"] = array(
            "span"      => 4,
            "label"     => traduz("Descrição Inglês"),
            "type"      => "input/text",
            "width"     => 12,
            "required"  => false
        );
    }
}

if (in_array($login_fabrica, array(52, 158, 165, 175, 178))) {
    $inputs["defeito_constatado_grupo"] = array(
        "span"      => 4,
        "label"     => traduz("Grupo"),
        "type"      => "select",
        "width"     => 8,
        "required"  => true,
        "options"  => array()
    );

    $sql = "SELECT  grupo_codigo            ,
                    descricao               ,
                    defeito_constatado_grupo
            FROM    tbl_defeito_constatado_grupo
			WHERE   fabrica = $login_fabrica
			order by descricao";
    $res = pg_query_with_log($con,$sql);
    $contador_res = pg_num_rows($res);

    for ($i=0;$i<$contador_res;$i++) {
        $grupo_codigo               = trim(pg_result($res,$i,grupo_codigo));
        $descricao                  = trim(pg_result($res,$i,descricao));
        $defeito_constatado_grupo   = trim(pg_result($res,$i,defeito_constatado_grupo));

        $inputs["defeito_constatado_grupo"]["options"][$defeito_constatado_grupo] = $descricao;
    }
}

if ($login_fabrica == 151) {
        
    $inputs['anexos_obrigatorios[]'] = array(
      "span"    => 4,
      "id"      => "anexos_obrigatorios",
      "label"   => "Anexos Obrigatórios",
      "type"    => "select",
      "width"   => 10,
      "extra"   => array('multiple' => 'multiple')
    );

    if (isset($_REQUEST['defeito_constatado'])) {

        $getAdicionais = "SELECT campos_adicionais 
                          FROM tbl_defeito_constatado 
                          WHERE defeito_constatado = {$_REQUEST['defeito_constatado']}";

        $res = pg_query($con, $getAdicionais);

        $adicionais_original = pg_fetch_result($res, 0, "campos_adicionais");

        $campos = json_decode($adicionais_original,1);

    }

    $_RESULT['anexos_obrigatorios'] = $campos['anexos_obrigatorios'];

    $queryAnexos = "SELECT anexo_tipo, nome, codigo
                    FROM tbl_anexo_tipo 
                    WHERE fabrica = $login_fabrica 
                    AND ativo = 't' 
                    AND anexo_contexto = 7";

    $res_anexos = pg_query($con,$queryAnexos);

    $adicionais = pg_fetch_all($res_anexos);

    if (pg_num_rows($res) > 0){

        foreach ($adicionais as $anexo) {

            $inputs["anexos_obrigatorios[]"]["options"][$anexo['codigo']] = $anexo['nome'];
        }   
    }

}

if ( ($login_fabrica == 30 && $_SERVER["SERVER_NAME"] != "conquistar.telecontrol.com.br") || in_array($login_fabrica,array(43,94,115,143,162, 165))) {
	$required = ($login_fabrica != 162) ? "true" : "";

	$inputs["mao_de_obra"] = array(
        "span"      => 4,
        "label"     => traduz("Mão de Obra"),
        "type"      => "input/text",
        "width"     => 12,
        "maxlength" => 50,
        "readonly"  => $readonly,
        "required"  => $required,
        "extra" => array(
            "price" => "true"
        )
    );

    if($login_fabrica == 30){
        $inputs["lista_garantia"] = array(
            "span"      => 4,
            "label"     => traduz("Tabela de Preço"),
            "type"      => "input/text",
            "width"     => 12,
            "maxlength" => 20
        );

        $inputs["versao_lista"] = array(
            "span"      => 4,
            "label"     => traduz("Versão de Tabela"),
            "type"      => "input/text",
            "width"     => 12,
            "maxlength" => 20
        );

        $inputs["item_servico"] = array(
            "span"      => 4,
            "label"     => traduz("Item de Serviço"),
            "type"      => "select",
            "width"     => 5,
            "required"  => true,
            "options"  => array()
        );

        $inputs["ativo"] = array(
                "span"  => 4,
                "type"  => "checkbox",
                "width" => 4,
                "checks" => array(
                    "1" => traduz("Ativo")
                )
        );

        $sqlItem = "SELECT  esmaltec_item_servico,
                            descricao
                    FROM    tbl_esmaltec_item_servico
                    WHERE   ativo = 't'";
        $resItem = pg_exec($con,$sqlItem);
        $total = pg_num_rows($resItem);

        for ($j=0;$j<$total;$j++) {
            $item       = trim(pg_result($resItem,$j,esmaltec_item_servico));
            $descricao  = trim(pg_result($resItem,$j,descricao));

            $inputs["item_servico"]["options"][$item] = $descricao;
        }
    }
}

?>
<form name="frm_defeito_constatado" method="post" class="form-search form-inline tc_formulario" action="defeito_constatado_cadastro_com_integridade_novo.php">
    <div class="titulo_tabela "><?=$title_page?></div>
    <br/>
<?
    echo montaForm($inputs, $hiddens);
?>
    <p><br/>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <button class='btn' type="button" onclick="submitForm($(this).parents('form'));"><?=traduz('Gravar')?></button>
<?php
        if (strlen($_GET["defeito_constatado"]) > 0) {
            if($login_fabrica == 131){
                ?>
                <button class='btn btn-danger' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>?excluir=<?=$_GET["defeito_constatado"]?>';"><?=traduz('Excluir')?></button>
                <?php
            }
?>
            <button class='btn btn-warning' type="button" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';"><?=traduz('Limpar')?></button>
<?php
        }
?>
    </p><br/>
    <?php
    if(isFabrica(94,10) && isset($_GET['defeito_constatado']) && is_numeric($_GET['defeito_constatado'])):
            $urlParams = 'table=tbl_defeito_constatado&id='.$_GET['defeito_constatado'].'&limit=5';
    ?>
        <iframe frameborder="0" style="border:none;width:99%;max-height:300px;min-height:0px;height:auto;border-radius: 4px;"  src="relatorio_auditor.php?<?php echo $urlParams;?>">
        </iframe>
    <?php
    endif;
    ?>
</form>
<br />
<div class='alert'><?=traduz('Para efetuar alterações, clique na descrição do % .', null, null, [$tema])?></div>
<center>
    <button id="btnInativos" class="btn">
        Exibir/Esconder Inativos
    </button>
</center>
<br />
<?
if (in_array($login_fabrica,array(30,43,94,115,143))){
    $sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
                    tbl_defeito_constatado.codigo           ,
                    tbl_defeito_constatado.descricao        ,
                    tbl_defeito_constatado.mao_de_obra      ,
                    tbl_defeito_constatado.lista_garantia   ,
                    tbl_defeito_constatado.versao_lista     ,
                    tbl_defeito_constatado.ativo            ,
                    tbl_defeito_constatado.lancar_peca      ,
                    tbl_esmaltec_item_servico.descricao as item_servico,
                    CASE WHEN tbl_defeito_constatado.orientacao IS TRUE
                         THEN 'Sim'
                         ELSE 'Não'
                    END AS orientacao
            FROM    tbl_defeito_constatado
       LEFT JOIN    tbl_linha USING (linha)
       LEFT JOIN    tbl_familia USING (familia)
       LEFT JOIN    tbl_esmaltec_item_servico USING(esmaltec_item_servico)
            WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
      ORDER BY      tbl_defeito_constatado.ativo DESC,tbl_defeito_constatado.linha,
                    tbl_defeito_constatado.familia,
                    tbl_defeito_constatado.descricao;
    ";
}else{
    if ($login_fabrica == 158) {
        $familiasDefeito = "
		, ARRAY_TO_STRING(ARRAY(
			SELECT DISTINCT f.descricao
			FROM tbl_diagnostico d
			INNER JOIN tbl_familia f ON f.familia = d.familia AND f.fabrica = $login_fabrica
			WHERE d.fabrica = $login_fabrica
			AND d.defeito_constatado = tbl_defeito_constatado.defeito_constatado
		), ', ') AS familias
	";
    }

    $sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
                    tbl_defeito_constatado.codigo           ,
                    tbl_defeito_constatado.descricao        ,
                    tbl_defeito_constatado.mao_de_obra      ,
                    tbl_defeito_constatado_grupo.descricao  as grupo_descricao      ,
                    tbl_defeito_constatado.lancar_peca      ,
                    tbl_defeito_constatado.ativo,
                    tbl_defeito_constatado.lista_garantia
		    {$familiasDefeito}
            FROM    tbl_defeito_constatado
       LEFT JOIN    tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
       LEFT JOIN    tbl_linha USING (linha)
       LEFT JOIN    tbl_familia USING (familia)
            WHERE   tbl_defeito_constatado.fabrica = $login_fabrica";

    if($login_fabrica == 50){
        $sql .= " AND tbl_defeito_constatado.ativo IS TRUE ";
    }

    if (in_array($login_fabrica, array(52, 165))) {
        $sql .= "
      ORDER BY      tbl_defeito_constatado.ativo DESC,tbl_defeito_constatado.linha,
                    tbl_defeito_constatado.familia,
                    tbl_defeito_constatado_grupo.descricao,
                    tbl_defeito_constatado.descricao ;
        ";
    }else if  (in_array($login_fabrica,array(108,111))){
        $sql .= "
      ORDER BY       tbl_defeito_constatado.ativo DESC,tbl_defeito_constatado.descricao;
        ";
    }else{
        $sql .="
      ORDER BY      tbl_defeito_constatado.ativo DESC,tbl_defeito_constatado.linha,
                    tbl_defeito_constatado.familia,
                    tbl_defeito_constatado.descricao;
        ";
    }
}
$res = pg_query_with_log($con,$sql);

if (pg_numrows($res) > 0) {

    if (in_array($login_fabrica,array(108,111,162,165,169,170))){
        $colspan = '5';
    }else{
        $colspan = '4';
    }
    $colspan = ( in_array($login_fabrica, array(43,94,143,173)) ) ? 6 : $colspan;
    $colspan = ($login_fabrica==30) ? 7 : $colspan;

    if ($login_fabrica == 165) echo "</div> <div style='padding: 3px !important;'>";
?>
<table id="tabela_defeito_constatado" class="table table-striped table-bordered table-hover table-fixed">
    <thead>
        <tr class="titulo_tabela">
            <th colspan='5'><?=traduz('Relação de %', null, null, [$temaPlural])?></th>
        </tr>
        <tr class="titulo_coluna">
            <th nowrap><?=traduz('Ativo')?></th>
<?
    if (in_array($login_fabrica,array(50,51,81,86,91,104,108,111,114,120,123,125,129,151,153,157,162,167,203)) OR $defeito_constatado_obriga_lancar_peca){
?>
            <th nowrap><?=traduz('Lança peça')?></th>
<?
    }

    if (in_array($login_fabrica, array(169,170))) {
    ?>
        <th>Tipo</th>
    <?php
    }
    ?>
            <th nowrap><?=traduz('Código')?></th>
            <th nowrap><?=traduz('Descrição')?></th>


<?
    if ($login_fabrica == 158) {
?>

	   <th nowrap><?=traduz('Família')?></th>
<?
}
    if (in_array($login_fabrica, array(52,158,165,175,178)) ) {
?>
            <th nowrap><?=traduz('GRUPO')?></th>
<?
    }

    if ($login_fabrica == 94) {
?>
            <th><?=traduz('Orientação')?></th>
<?
    }

    if (in_array($login_fabrica, array(173))) { ?>
        <th><?=traduz('Posição')?></th>
    <?php
    }
    

    # hd 22332 - Mão de obra
    # HD 23943 - Tabela e Versão
    if(in_array($login_fabrica, array(30,43,94,115,143,162,165))) {
?>
            <th nowrap><?=traduz('Mão de Obra')?></th>
<?
        if($login_fabrica == 30) {
?>
            <th nowrap><?=traduz('Tabela de Preço')?></th>
            <th nowrap><?=traduz('Versão da Tabela')?></th>
<?
        }
    }

    if ($moduloTraducao['es']) { ?>
        <th nowrap><?=traduz('Desc. Espanhol')?></th>
    <?php
    }

    if ($moduloTraducao['en-US']) { ?>
        <th nowrap><?=traduz('Desc. Inglês')?></th>
    <?php
    }
    //HD 354959 Início
    if ($login_fabrica == 30) {
?>
            <th><?=traduz('Item de Servico')?></th>
            <th><?=traduz('Auditoria')?></th>
<?
    }
    //  HD 354959 Fim
?>
        </tr>
    </thead>
    <tbody>
<?
    $contador_res_x = pg_numrows($res);
    for ($x = 0 ; $x < $contador_res_x; $x++){
        $defeito_constatado   = trim(pg_result($res,$x,defeito_constatado));
        $descricao            = trim(pg_result($res,$x,descricao));
	    $descricao	          = mb_detect_encoding($descricao,'UTF-8',true) ? utf8_decode($descricao) : $descricao;
        $grupo_descricao      = trim(@pg_result($res,$x,grupo_descricao));
	    $grupo_descrica       = mb_detect_encoding($grupo_descricao,'UTF-8',true) ? utf8_decode($grupo_descricao) : $grupo_descricao;
        $codigo               = trim(pg_result($res,$x,codigo));
        $ativo                = trim(pg_result($res,$x,ativo));

        if ($login_fabrica == 158) {
            $familias = pg_fetch_result($res, $x, "familias");
        }

        if (in_array($login_fabrica, array(169,170))) {
            switch (pg_fetch_result($res,$x,"lista_garantia")) {
                case 'sem_defeito':
                    $lista_garantia = traduz("Produto sem Defeito");
                    break;

                case 'fora_garantia':
                    $lista_garantia = traduz("Consumidor sem Nota Fiscal");
                    break;

                default:
                    $lista_garantia = traduz("Produto em Garantia");
                    break;
            }
        }

        if (in_array($login_fabrica,array(50,51,81,86,91,104,108,111,114,120,123,125,129,151,153,157,162,165,167,203)) OR $defeito_constatado_obriga_lancar_peca){
            $lancar_peca  = pg_result($res,$x,'lancar_peca');

            $lancar_peca  = ($lancar_peca == 't') ? traduz('SIM') : traduz('NÃO');
        }
        $xlancar_peca          = trim(pg_result($res,$x,lancar_peca));
        # hd 22332 - Mão de obra
        # HD 23943 - Tabela e Versão
        if(in_array($login_fabrica, array(30,43,94,115,143,162,165))) {
            $mao_de_obra          = trim(pg_result($res,$x,mao_de_obra));
            $mao_de_obra          = number_format($mao_de_obra,2,',','.');

            if($login_fabrica != 162){
                $lista_garantia       = trim(pg_result($res,$x,lista_garantia));
                $versao_lista         = trim(pg_result($res,$x,versao_lista));
                $item_servico         = trim(pg_result($res,$x,item_servico));
            }
        }
        $classInativo = "";
        if($ativo=='t'){
            $ativo="Sim";
        }else{
            $ativo="Não";
            $classInativo = "inativo";
        }
        if($xlancar_peca=='t'){
            $xlancar_peca="Sim";
        }else{
            $xlancar_peca="Não";
        }

	    $posicao_peca = (strlen($grupo_descricao) > 0) ? "Sim" : "Não";

        $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA"; ?>
        <tr style="background-color:<?=$cor?>" align="center" class="<?= $classInativo ?>">
            <td class="tac">
                <img src="imagens/<?=($ativo == 'Sim') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 'Sim') ? traduz('Defeito ativo') : traduz('Defeito inativo')?>"/>
            </td>
        <?php
        if (in_array($login_fabrica,array(50,51,81,86,91,104,108,111,114,120,123,125,129,151,153,157,162,167,203)) OR $defeito_constatado_obriga_lancar_peca){ ?>
            <td class="tac">
                <img src="imagens/<?=($lancar_peca == 'SIM') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($lancar_peca == 'SIM') ? 'Lançar Peça' : 'Não Lançar Peça'?>"/>
            </td>
        <?php
        }

        if (in_array($login_fabrica, array(169,170))) {
        ?>
            <td class="tac"><?=$lista_garantia?></td>
        <?php
        }
        ?>
            <td nowrap class="tac">
                <a href="<?=$PHP_SELF?>?defeito_constatado=<?=$defeito_constatado?>"><?=$codigo?></a>
            </td>
            <td nowrap align="left">
                <a href="<?=$PHP_SELF?>?defeito_constatado=<?=$defeito_constatado?>"><?=$descricao?></a>
            </td>
<?php

	if ($login_fabrica == 158) {
	?>
		<td><?=$familias?></td>
	<?php
	}
        if (in_array($login_fabrica, array(52,158, 165,175,178))) { ?>
            <td nowrap align="center">
                <a href="<?=$PHP_SELF?>?defeito_constatado=<?=$defeito_constatado?>"><?=$grupo_descricao?></a>
            </td>
        <?php
        }

        if (in_array($login_fabrica, array(94))) {?>
            <td><? echo pg_result($res,$x,'orientacao');?></td>
        <?php
        }

	    if (in_array($login_fabrica, array(173))) {?>
            <td><? echo $posicao_peca;?></td>
        <?php
        }

        # hd 22332 - Mão de obra
        # HD 23943 - Tabela e Versão
        if (in_array($login_fabrica,array(30,43,94,115,143,162,165))){ ?>
            <td nowrap align="center">
                <a href="<?=$PHP_SELF?>?defeito_constatado=<?=$defeito_constatado?>">R$ <? echo number_format($mao_de_obra,2,',','.');?></a>
            </td>
            <?php
            if($login_fabrica == 30) { ?>
            <td nowrap align="center">
                <a href="<?=$PHP_SELF?>?defeito_constatado=<?=$defeito_constatado?>"><?=$lista_garantia?></a>
            </td>
            <td nowrap align="center">
                <a href="<?=$PHP_SELF?>?defeito_constatado=<?=$defeito_constatado?>"><?=$versao_lista?></a>
            </td>
            <?php
            }
        }

        if ($moduloTraducao['es']) {

            $sqlIdiomaEs = "SELECT descricao FROM tbl_defeito_constatado_idioma
                    WHERE idioma = 'ES' AND defeito_constatado = $defeito_constatado";
            $resIdiomaEs = pg_query($con, $sqlIdiomaEs);

            $descricao_espanhol = pg_fetch_result($resIdiomaEs, 0, 'descricao');

        ?>
            <td><?= $descricao_espanhol ?></td>
        <?php
        }

        if ($moduloTraducao['en-US']) {
            $sqlIdiomaEn = "SELECT descricao FROM tbl_defeito_constatado_idioma
                    WHERE idioma = 'en-US' AND defeito_constatado = $defeito_constatado";
            $resIdiomaEn = pg_query($con, $sqlIdiomaEn);

            $descricao_ingles = pg_fetch_result($resIdiomaEn, 0, 'descricao');
        ?>
            <td><?= $descricao_ingles ?></td>
        <?php
        }


        //HD 354959 Início
        if ($login_fabrica == 30) { ?>
            <td><?=$item_servico?></td>
            <td class='tac' nowrap>
                <!-- <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_defeito_constatado&id=<?=$defeito_constatado ?>' class='link-log' name='btnAuditorLog'>Visualizar Log</a> -->
                <a href='#' class='visualizalog link-log' data-defeito="<?=$defeito_constatado ?>" name='btnAuditorLog'>Visualizar Log</a>                
            </td>
        <?php
        }
        //  HD 354959 Fim

?>
        </tr>
<?
    }
?>
    </tbody>
</table>
<br />
<?
}

include "rodape.php";


function getDefeito($idDefeito){
	global $con;
	$sql = 'SELECT * FROM tbl_defeito_constatado WHERE defeito_constatado = $1 LIMIT 1;';
	$params = array(
		$idDefeito
	);
	$result = pg_query_params($con,$sql,$params);
	if($result === false)
		throw new Exception(pg_last_error($con));
	$defeito = pg_fetch_assoc($result);
	return $defeito;
}

function logDefeito($action,$idDefeito,$before,$after){
	require_once __DIR__.'/../classes/api/Client.php';
	global $login_fabrica,$login_admin;
	$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$auditor_ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
    	$auditor_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
	else {
    	$auditor_ip = $_SERVER['REMOTE_ADDR'];
    }
	$client = api\Client::makeTelecontrolClient('auditor','auditor');
	$data = array(
    	'application' => '02b970c30fa7b8748d426f9b9ec5fe70',
        'table' => 'tbl_defeito_constatado',
        'ip_access' => $auditor_ip,
        'owner' => $login_fabrica,
        'action' => $action,
        'program_url' => $url,
        'primary_key' => $idDefeito,
        'user' => $login_admin,
        'user_level' => 'admin',
        'content' => array ("antes" => $before , "depois" => $after)
	);
	$client->post(array(),$data);
}


