<?

/**
 * Array de regras padrões
 */

$regras = array(
    "data_abertura" => array(
        "obrigatorio" => true,
        "regex"       => "date",
        "function"    => array("valida_data_abertura")
    ),
    "data_compra" => array(
        "regex"       => "date",
        "function"    => array("valida_data_compra")
    ),
    "revenda" => array(
        "function" => array("busca_revenda")
    ),
    "revenda_nome" => array(
        "obrigatorio" => true
    ),
    "revenda_cnpj" => array(
        "obrigatorio" => true,
        "function"    => array("valida_revenda_cnpj")
    ),
    "revenda_cidade" => array(
        "obrigatorio" => true
    ),
    "revenda_estado" => array(
        "obrigatorio" => true
    ),
    "revenda_email" => array(
        "regex"       => "email"
    )
);

/**
 * Array de regex
 */
$regex = array(
    "date"     => "/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/",
    "cpf"      => "/[0-9]{3}\.[0-9]{3}\.[0-9]{3}\-[0-9]{2}/",
    "cnpj"     => "/[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}\-[0-9]{2}/",
    "cep"      => "/[0-9]{5}\-[0-9]{3}/",
    "email"    => "/^.[^@]+\@.[^@.]+\..[^@]+$/"
);

/**
 * Array para formatar o nome dos campos dentro da função valida_campos()
 */
$label = array(
    "posto"             => traduz("Posto"),
    "data_abertura"     => traduz("Data de Abertura"),
    "tipo_atendimento"  => traduz("Tipo de Atendimento"),
    "nota_fiscal"       => traduz("Nota Fiscal"),
    "data_nf"           => traduz("Data da Nota Fiscal"),
    "revenda_nome"      => traduz("Nome da revenda"),
    "revenda_cnpj"      => traduz("CNPJ da revenda"),
    "revenda_cep"       => traduz("CEP da revenda"),
    "revenda_estado"    => traduz("Estado da revenda"),
    "revenda_cidade"    => traduz("Cidade da revenda"),
    "revenda_bairro"    => traduz("Bairro da revenda"),
    "revenda_endereco"  => traduz("Endereço da revenda"),
    "revenda_fone"      => traduz("Telefone da revenda"),
    "revenda_email"     => traduz("Email da revenda"),
    "anexo_nota_fiscal" => traduz("Anexo da nota fiscal"),
    "anexo"             => traduz("Anexo é obrigatorio"),
);

$grava_defeito_peca = false;

/**
 * Função que valida os campos da os de acordo com o array $regras
 */
function valida_campos() {
    global $msg_erro, $regras, $campos, $label, $regex;

    foreach ($regras as $campo => $array_regras) {
        $input_valor = $campos[$campo];
        foreach ($array_regras as $tipo_regra => $regra) {
            switch ($tipo_regra) {
                case 'obrigatorio':
                    if (!strlen($input_valor) && $regra === true) {
                        $msg_erro["msg"]["campo_obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
                        $msg_erro["campos"][] = $campo;
                    }
                    break;

                case 'regex':
                    if (!empty($input_valor) && !preg_match($regex[$regra], $input_valor)) {
                        $msg_erro["msg"][] = "{$label[$campo]} inválido";
                        $msg_erro["campos"][] = $campo;
                    }
                    break;

                case 'function':
                    if (is_array($regra)) {
                        foreach ($regra as $function) {
                            try {
                                call_user_func($function);
                            } catch(Exception $e) {
                                $msg_erro["msg"][] = $e->getMessage();
                                $msg_erro["campos"][] = $value;
                            }
                        }
                    }
                    break;
            }
        }
    }
}

function verifica_sua_os() {
    global $con, $login_fabrica, $campos, $os_revenda, $gravando;

    if (!empty($campos['sua_os'])) {
	$sql = "SELECT os_revenda FROM tbl_os_revenda WHERE fabrica = {$login_fabrica} AND sua_os = '{$campos['sua_os']}';";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
	    $os_revenda_gravada = pg_fetch_result($res, 0, "os_revenda");
	    if ($gravando === true) {
		throw new Exception("Número de OS já informado, necessário alterar");
	    } else {
		if ($os_revenda != $os_revenda_gravada) {
		    throw new Exception('Impossível alterar, número de OS já utilizado');
		}
	    }
	}
    }
}

/**
 * Função para validação de data de abertura
 */
function valida_data_abertura() {
    global $campos, $os;

    $data_abertura = $campos["data_abertura"];

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

    $data_compra   = $campos["data_nf"];
    $data_abertura = $campos["data_abertura"];

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
 * Função para validar o CNPJ da Revenda
 */
function valida_revenda_cnpj() {
    global $con, $campos;

    $cnpj = preg_replace("/\D/", "", $campos["revenda_cnpj"]);

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
 * Função que verifica auditoria unica
 */
function verifica_auditoria_unica($condicao, $os) {
    global $con;
    
    $sql = "SELECT tbl_auditoria_os.auditoria_status FROM tbl_auditoria_os
            INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
            WHERE os = {$os}
            AND {$condicao}
            ORDER BY data_input DESC";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) == 0) {
        return true;
    } else {
        return false;
    }
}

function verifica_auditoria_unica_revenda($condicao, $os) {
    global $con;
    
    $sql = "SELECT tbl_auditoria_os_revenda.auditoria_status FROM tbl_auditoria_os_revenda
            INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os_revenda.auditoria_status
            WHERE os_revenda = {$os}
            AND {$condicao}
            ORDER BY data_input DESC";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) == 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Verifica se a OS deu reincidencia com uma OS finalizada se não estiver bloqueia a abertura da OS
 */
function verifica_os_reincidente_finalizada($os) {
    global $con, $login_fabrica, $campos;
    $posto = $campos['posto_id'];
  
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
        $sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND posto = {$posto} AND excluida IS NOT TRUE";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0 ){
            $sua_os = pg_fetch_result($res, 0, "sua_os");
            throw new Exception("Já existe uma Ordem de Serviço aberta com os dados informados, os: {$sua_os}");
        } else {
            return true;
        }
    }
}

/**
 * Função que busca as auditorias disponível pela condição passada
 */
function buscaAuditoria($condicao) {
    global $con, $login_fabrica;

    $sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE $condicao";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        return array("resultado" => true, "auditoria" => pg_fetch_result($res, 0, "auditoria_status"));
    }
}

function aprovadoAuditoria($cond_auditoria, $os) {
    global $con,$login_fabrica;

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
 * Função que busca a revenda quando o posto não clica na Lupa
 */
function busca_revenda() {
    global $con, $login_fabrica, $campos;

    $revenda_id = $campos['revenda'];
    $cnpj = preg_replace("/[\.\-\/]/", "", $campos["revenda_cnpj"]);

    if (!empty($cnpj) && empty($revenda_id)) {
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

/**
 * Função que verifica se a revenda não existe se não existir grava
 */
function verifica_revenda() {
    global $con, $campos;

    $revenda            = $campos["revenda"];
    $nome               = $campos["revenda_nome"];
    $cnpj               = preg_replace("/[\.\-\/]/", "", $campos["revenda_cnpj"]);
    $cep                = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
    $cidade             = $campos["revenda_cidade"];
    $estado             = $campos["revenda_estado"];
    $bairro             = $campos["revenda_bairro"];
    $endereco           = $campos["revenda_endereco"];
    $endereco           = substr($endereco,0,60);
    $numero             = $campos["revenda_numero"];
    $complemento        = $campos["revenda_complemento"];
    $telefone           = $campos["revenda_fone"];
    $consumidor_revenda = $campos["consumidor_revenda"];

    if (!empty($cnpj) AND !in_array($consumidor_revenda, array("C", "S"))) {
        $sql = "SELECT revenda, cep FROM tbl_revenda WHERE cnpj = '{$cnpj}';";
        $res = pg_query($con, $sql);

        if (strlen($cidade) > 0 && strlen($estado) > 0) {
            $sql_cidade = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
            $res_cidade = pg_query($con, $sql_cidade);

            if (pg_num_rows($res_cidade) > 0) {
                $cidade = pg_fetch_result($res_cidade, 0, "cidade");
            } else {
                $cidade = "null";
            }
        }

        if (!strlen($cidade)) {
            $cidade = "null";
        }

        if (pg_num_rows($res) > 0) {
            $campos['revenda'] = pg_fetch_result($res, 0, "revenda");
	       $cep_db = trim(pg_fetch_result($res, 0, "cep"));

    	    if (empty($cep_db)) {

                	$sql = "
                        UPDATE tbl_revenda SET
                        	nome = '{$nome}',
                        	cep  = '{$cep}',
                        	cidade  = {$cidade},
                        	bairro  = '{$bairro}',
                        	endereco  = '{$endereco}',
                        	numero  = '{$numero}',
                        	complemento  = '{$complemento}',
                        	fone  = '{$telefone}'
                        WHERE revenda = {$revenda};
                	";
                	$res = pg_query($con, $sql);
    	    }
        } else {
            $sql = "
                INSERT INTO tbl_revenda
                    (nome, cnpj, cep, cidade, bairro, endereco, numero, complemento, fone)
                VALUES
                    ('{$nome}', '{$cnpj}', '{$cep}', {$cidade}, '{$bairro}', '{$endereco}', '{$numero}', '{$complemento}', '{$telefone}')
                RETURNING revenda;
            ";
            $res = pg_query($con, $sql);
            $campos['revenda'] = pg_fetch_result($res, 0, "revenda");
        }
    }
    
    return (empty($campos['revenda'])) ? "null" : $campos['revenda'];
}

function valida_os_revenda_itens()
{
    global $campos, $msg_erro;

    $array_msg_erro = array();
    foreach ($campos['produtos'] as $key => $array_produto) {
        if ($key !== "__modelo__" && !empty($array_produto['id'])) {
            if (empty($array_produto['qtde'])) {
                $array_msg_erro[$key] = "É necessário informar uma quantidade para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                $msg_erro["campos"][] = "produto_".$key;
            }
        }
    }

    if (count($array_msg_erro) > 0) {
        throw new Exception(implode("<br />", $array_msg_erro));
    }
}

$valida_os_revenda_itens = "valida_os_revenda_itens";

/**
 * Função para mover os anexos do bucket temporario para o bucket da Ordem de Serviço Revenda
 */
function grava_anexo()
{
    global $campos, $s3, $os_revenda, $fabricaFileUploadOS, $con, $login_fabrica, $msg_erro;

    if ($fabricaFileUploadOS) {
        $anexo_chave = $campos["anexo_chave"];
        
        if ($anexo_chave != $os_revenda) {
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
    }else{
        list($dia, $mes, $ano) = explode("/", getValue("data_abertura"));
        $arquivos = array();
        foreach ($campos["anexo"] as $key => $value) {
            if ($campos["anexo_s3"][$key] != "t" && strlen($value) > 0) {
                $ext = preg_replace("/.+\./", "", $value);

                $arquivos[] = array(
                    "file_temp" => $value,
                    "file_new"  => "{$os_revenda}_{$key}.{$ext}"
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
 * Função para mover os anexos do bucket temporario para o bucket da Ordens de Serviço originadas
 */
function grava_anexo_os($os = null)
{
    global $campos, $s3, $con, $fabricaFileUploadOS, $login_fabrica, $os_revenda, $msg_erro;

    if (empty($os)) {
        return false;
    }

    if ($fabricaFileUploadOS) {
        $nfs_add   = $campos['notas_fiscais_adicionadas'];
        $hash_temp = $campos['anexo_chave'];
     
        $sql_os = "SELECT nota_fiscal FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_os = pg_query($con, $sql_os);
        if (pg_num_rows($res_os) > 0){
            $nota_fiscal_os = trim(pg_fetch_result($res_os, 0, 'nota_fiscal'));
        }
        
        $tdocs = new TDocs($con, $login_fabrica, "os");

        if (!$tdocs->insertAnexoRevenda($os, $os_revenda, $nota_fiscal_os)) {
            $msg_erro["msg"][] = traduz("Erro ao gravar anexos da OS#2 $os");
        }
        
    }else{
        list($dia, $mes, $ano) = explode("/", getValue("data_abertura"));

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

$grava_anexo_os = "grava_anexo_os";


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

function valida_anexo_boxuploader() {
    global $msg_erro, $fabricaFileUploadOS, $os_revenda, $con, $login_fabrica, $fabricaFileUploadOS, $campos, $anexos_inseridos, $anexos_obrigatorios;

    $anexos_inseridos = $anexos_inseridos();

    if ($fabricaFileUploadOS) {

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
    global $campos, $con, $login_fabrica, $os_revenda, $fabricaFileUploadOS;

    $anexo_chave      = $campos["anexo_chave"];
    $anexos_inseridos = [];

    if (!empty($os_revenda)){
        $cond_tdocs = "AND tbl_tdocs.referencia_id = {$os_revenda}";
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

function grava_os()
{
    global $con, $login_fabrica, $login_admin, $os_revenda, $sua_os_revenda, $campos, $areaAdmin, $auditorias, $grava_anexo_os, $grava_defeito_peca;

    if (empty($os_revenda)) {
    throw new Exception("Erro ao gravar a OS Revenda #8");
    }

    if (!in_array($login_fabrica, [178])) {
        $condAuditoria = "AND (tbl_auditoria_os_revenda.os_revenda IS NULL OR tbl_auditoria_os_revenda.liberada IS NOT NULL)";
    }
    
    $sql = "
        SELECT DISTINCT ON (tbl_os_revenda_item.os_revenda_item)
            tbl_os_revenda.campos_extra,
            tbl_os_revenda_item.produto AS id, 
            tbl_os_revenda_item.os_revenda_item, 
            tbl_os_revenda_item.qtde, 
            tbl_os_revenda_item.serie,
            tbl_os_revenda_item.parametros_adicionais,
            tbl_os_revenda_item.nota_fiscal, 
            tbl_os_revenda_item.tipo_atendimento,
            tbl_os_revenda_item.marca,
            tbl_os_revenda_item.parametros_adicionais::jsonb ->> 'defeito_constatado' AS defeito_constatado,
            tbl_os_revenda.explodida AS os_revenda_explodida,
            tbl_os_revenda_item.explodida AS item_explodida,
            tbl_os_revenda_item.defeito_reclamado,
            tbl_os_revenda_item.data_nf AS data_nf,
            (
                SELECT SUM (tbl_os_revenda_item.qtde) 
                FROM tbl_os_revenda 
                JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
                WHERE tbl_os_revenda.fabrica = {$login_fabrica} AND tbl_os_revenda.os_revenda = {$os_revenda}
                AND tbl_os_revenda_item.explodida = 't'
            ) AS sum_tot
        FROM tbl_os_revenda
        JOIN tbl_os_revenda_item USING(os_revenda)
        LEFT JOIN tbl_auditoria_os_revenda ON tbl_os_revenda.os_revenda = tbl_auditoria_os_revenda.os_revenda
        WHERE tbl_os_revenda.fabrica = {$login_fabrica}
        AND tbl_os_revenda.os_revenda = {$os_revenda}
        AND tbl_os_revenda_item.explodida = 'f' 
        {$condAuditoria}";
    $resOsRevenda = pg_query($con, $sql);

    if (pg_num_rows($resOsRevenda) > 0) {
        $os_revenda_explodida = pg_fetch_result($resOsRevenda,0, "os_revenda_explodida");
        $sum_total = pg_fetch_result($resOsRevenda, 0, "sum_tot");

        /**
         * Grava tbl_os
         */
        if (function_exists("grava_os_fabrica")) {
            /**
             * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
             * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
             */
            $tbl_os = grava_os_fabrica();
            if (!empty($os) && is_array($tbl_os)) {
                $tbl_os_update = array();

                foreach ($tbl_os as $key => $value) {
                    $tbl_os_update[] = "{$key} = {$value}";
                }
            }
        }
        if (empty($os_revenda_explodida)){
            $sumTot = 1;
        }else{
            $sumTot = $sum_total+1;
        }
        $result = pg_fetch_all($resOsRevenda);
        
        unset($campos["produtos"]["__modelo__"]);

        foreach($result as $array_produto) {
            $os_revenda_item = $array_produto["os_revenda_item"];
            $countQtdeProd = 1;
            while($countQtdeProd <= $array_produto['qtde']) {
                $sua_os = $os_revenda."-".$sumTot;
                $observacao_os = $campos['obs'];

                if ($sumTot == 0 && !empty($qtde_km)) {
                    $campoQtdeKm = "qtde_km,";
                    $valorQtdeKm = $qtde_km.",";
                }

                if ($login_fabrica == 174){
                    if (!empty($array_produto['serie']) AND $array_produto['qtde'] > 1){
                        $array_produto['serie'] = "";
                    }
                }

                if (function_exists("grava_os_explodida_fabrica")) {
                    /**
                     * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
                     * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
                     */

                    $campos_tbl_os = grava_os_explodida_fabrica($array_produto);

                    if (!empty($os_revenda) && is_array($campos_tbl_os)) {
                        foreach ($campos_tbl_os as $key => $value) {
                            $tbl_os[$key] = $value;
                        }
                    }
                }
                
                if (in_array($login_fabrica, array(169,170))){
                    $sql_atendimento = "
                        SELECT tipo_atendimento
                        FROM tbl_tipo_atendimento
                        WHERE tipo_atendimento = ".$array_produto['tipo_atendimento']."
                        AND fabrica = {$login_fabrica}
                        AND descricao like 'Reopera%' ";
                    $res_atendimento = pg_query($con, $sql_atendimento);

                    if (pg_num_rows($res_atendimento) > 0){
                        if (empty($observacao_os)){
                            $observacao_os = "OS de Reoperação aberta para Revenda";
                        }else{
                            $observacao_os = $observacao_os."<br/> OS de Reoperação aberta para Revenda";
                        }
                    }else{
                        $observacao_os = $observacao_os;
                    }
                    $ins_tipo_atendimento = ((!empty($array_produto['tipo_atendimento'])) ? $array_produto['tipo_atendimento'] : "null");
                }else if (in_array($login_fabrica, array(178,183))){
                    $ins_tipo_atendimento = ((!empty($array_produto['tipo_atendimento'])) ? $array_produto['tipo_atendimento'] : "null");
                }else{
                    $ins_tipo_atendimento = ((!empty($campos['tipo_atendimento'])) ? $campos['tipo_atendimento'] : "null");
                }
                
                if (function_exists("valida_consumidor_revenda")) {
                    /**
                     * A função valida_consumidor_revenda deve ficar dentro do arquivo de regras fábrica
                    */
                    valida_consumidor_revenda();
                }

                $sql = "
                    INSERT INTO tbl_os (
                        fabrica,
                        validada,
                        sua_os,
                        data_digitacao,
                        posto,
                        data_abertura,
                        revenda,
                        obs,
                        consumidor_revenda,
                        tipo_atendimento,
                        {$campoQtdeKm}
                        admin,
                        revenda_cnpj,
                        revenda_nome,
                        revenda_fone,
                        produto,
                        serie,
                        defeito_reclamado,
                        nota_fiscal,
                        data_nf
                        ".((isset($tbl_os)) ? ", ".implode(", ", array_keys($tbl_os)) : "")."
                    ) VALUES (
                        {$login_fabrica},
                        current_timestamp,
                        '{$sua_os}',
                        now(),
                        {$campos['posto_id']},
                        '".formata_data($campos['data_abertura'])."',
                        {$campos['revenda']},
                        ".((!empty($observacao_os)) ? "'".$observacao_os."'" : "null").",
                        ".((!empty($campos['consumidor_revenda'])) ? "'".$campos['consumidor_revenda']."'" : "'R'").",
                        {$ins_tipo_atendimento},
                        {$valorQtdeKm}
                        ".((!empty($login_admin)) ? $login_admin : "null").",
                        ".((!empty($campos['revenda_cnpj'])) ? "'".preg_replace("/[\.\-\/]/", "", $campos['revenda_cnpj'])."'" : "null").",
                        ".((!empty($campos['revenda_nome'])) ? "'".$campos['revenda_nome']."'" : "null").",
                        ".((!empty($campos['revenda_fone'])) ? "'".$campos['revenda_fone']."'" : "null").",
                        ".((!empty($array_produto['id'])) ? $array_produto['id'] : "null").",
                        ".((!empty($array_produto['serie'])) ? "'".$array_produto['serie']."'" : "null").",
                        ".((!empty($array_produto['defeito_reclamado'])) ? $array_produto['defeito_reclamado'] : "null").",
                        ".((!empty($array_produto['nota_fiscal']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".$array_produto['nota_fiscal']."'" : "null").",
                        ".((!empty($array_produto['data_nf']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".$array_produto['data_nf']."'" : "null")."
                        ".((isset($tbl_os)) ? ", ".implode(", ", $tbl_os) : "")."
                    ) RETURNING os;
                ";
                $res = pg_query($con, $sql);
                
                unset($observacao_os);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar a OS Revenda #4");
                }
                $os = pg_fetch_result($res, 0, "os");

                if (!empty($os)) {
                    if (!empty($array_produto['id'])){
                        $sql = "
                            INSERT INTO tbl_os_produto (
                                os, produto, serie, defeito_constatado
                            ) VALUES (
                                {$os}, {$array_produto['id']}, ".((!empty($array_produto['serie'])) ? "'".$array_produto['serie']."'" : "null").",".((!empty($array_produto['defeito_constatado'])) ? "'".$array_produto['defeito_constatado']."'" : "null")."
                            )RETURNING os_produto;
                        ";
                        $res = pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao gravar a OS Revenda #5");
                        }
                        $os_produto = pg_fetch_result($res, 0, "os_produto");
                    }

                    if (function_exists("grava_os_extra")) {
                        /**
                         * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
                         * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
                         */

                        $campos_tbl_os_extra = grava_os_extra($array_produto);

                        if (!empty($os_revenda) && is_array($campos_tbl_os_extra)) {
                            foreach ($campos_tbl_os_extra as $key => $value) {
                                $tbl_os_extra[$key] = $value;
                            }
                        }
                    }

                    $sql = "
                        INSERT INTO tbl_os_extra (
                            os, i_fabrica
                            ".((isset($tbl_os_extra)) ? ", ".implode(", ", array_keys($tbl_os_extra)) : "")."
                        ) VALUES (
                            {$os}, {$login_fabrica} ".((isset($tbl_os_extra)) ? ", ".implode(", ", $tbl_os_extra) : "")."
                        );
                    ";
                    $res = pg_query($con, $sql);

                    if (function_exists("grava_os_item")) {
                        /**
                         * A função grava_os_item deve ficar dentro do arquivo de regras fábrica
                        */
                        grava_os_item($os, $os_produto, $os_revenda, $os_revenda_item, $array_produto);
                    }
                    
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar a OS Revenda #6");
                    }

                    $sql = "UPDATE tbl_os_revenda_item SET explodida = 't' WHERE os_revenda_item = {$os_revenda_item} AND os_revenda = {$os_revenda}";
                    $res = pg_query($con, $sql);
                    
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar a OS Revenda #8");
                    }
                }

                /**
                 * Move os anexos do bucket temporario para o bucket da Ordem de Serviço
                 */
                
                if (!empty($grava_anexo_os) && function_exists($grava_anexo_os)) {
                    $grava_anexo_os($os);
                }
                
                if (in_array($login_fabrica, array(178))){
                    call_user_func("auditoria", $auditorias, $os, $array_produto);
                }
                $countQtdeProd++;
                $sumTot++;
            }
        }
        
        if (empty($sua_os_revenda)) {
            $upSuaOs = ", sua_os = '{$os_revenda}'";
        }

        $sql = "UPDATE tbl_os_revenda SET explodida = now() {$upSuaOs} WHERE os_revenda = {$os_revenda};";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar a OS Revenda #7");
        }
    }
}
?>
