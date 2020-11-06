<?

$valida_garantia = "";

include "../../../helpdesk_posto_autorizado/helpdesk.php";

$data_abertura_fixa = true;

$regras["consumidor|cpf"]["obrigatorio"] = true;
$regras["consumidor|cep"]["function"] = array("valida_cep");
$regras["consumidor|cep"]["obrigatorio"] = true;
$regras["consumidor|telefone"]["obrigatorio"] = false;
$regras["consumidor|telefone"]["function"] = array("valida_consumidor_contato");
$regras["consumidor|email"]["obrigatorio"] = false;
$regras["consumidor|celular"]["obrigatorio"] = false;

$regras["produto|serie"] = array(
    "function"     => array("valida_numero_de_serie", "valida_serie_mondial")
);

$regras["os|data_abertura"]["function"] = array("valida_data_180");

/* NÃO VERIFICA SE A PEÇA LANÇADA É DE UMA LINHA QUE O POSTO ATENDE */
$regras["produto|id"]["function"] = array();

$valida_anexo_boxuploader = "valida_anexo_boxuploader";
$valida_anexo = "valida_anexo_defeito_constatado";

$redirecionamento_os = "redirecionamento_os_mondial";

function redirecionamento_os_mondial() {
    global $os_reincidente, $os_reincidente_numero, $grava_os_reincidente, $login_fabrica, $grava_os_reincidente, $nova_os_id, $os, $campos, $areaAdmin, $id_hd_chamado_helpdesk;

    $dentroDaGarantia = valida_garantia(true);

    if (!empty($id_hd_chamado_helpdesk)) {

        header("Location: helpdesk_posto_autorizado_atendimento.php?hd_chamado={$id_hd_chamado_helpdesk}");

    } else {

        if ($os_reincidente === true && $os_reincidente_numero != null AND strlen($grava_os_reincidente) > 0) {

            $grava_os_reincidente($os_reincidente_numero);

        } else {

            $abre_nova_os = \Posvenda\Regras::get("abre_nova_os", "ordem_de_servico", $login_fabrica);

            if ($abre_nova_os == true) {

                if(!empty($nova_os_id)){

                    header("Location: os_press.php?os={$nova_os_id}");

                }else{

                    header("Location: os_press.php?os={$os}");

                }

            }else{

                header("Location: os_press.php?os={$os}");

            }

        }

    }

}

function grava_helpdesk_os() {
    global $login_fabrica, $con, $login_fabrica, $login_admin, $campos, $os, $id_hd_chamado_helpdesk, $login_posto, $areaAdmin;
    
    $dentroDaGarantia = valida_garantia(true);

    $sqlVerificaHelpdesk = "SELECT tbl_hd_chamado.hd_chamado
                            FROM tbl_hd_chamado_extra
                            JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                            WHERE tbl_hd_chamado_extra.os = {$os}
                            AND tbl_hd_chamado.titulo = 'Help-Desk Posto'";
    $resVerificaHelpdesk = pg_query($con, $sqlVerificaHelpdesk);

    if (!$dentroDaGarantia && pg_num_rows($resVerificaHelpdesk) == 0) {

        $postoId    = $campos["posto"]["id"];
        $produtoId  = $campos["produto"]["id"];
        $referencia = $campos["produto"]["referencia"];
        $descricao  = $campos["produto"]["descricao"];
        $consumidor = $campos["consumidor"]["nome"];

        $posto_autorizado_dados = buscarInformacoesPosto($postoId);

        $atendente_responsavel = buscarAtendenteMondial($postoId, 140, $posto_autorizado_dados);

        $sql = "INSERT INTO tbl_hd_chamado
            (
                fabrica,
                fabrica_responsavel,
                atendente,
                admin,
                posto,
                tipo_solicitacao,
                status,
                titulo
            )
            VALUES
            (
                {$login_fabrica},
                {$login_fabrica},
                {$atendente_responsavel},
                {$atendente_responsavel},
                {$postoId},
                140,
                'Ag. Fábrica',
                'Help-Desk Posto'
            )
            RETURNING hd_chamado";
        $res = pg_query($con, $sql);

        $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

        $nome_posto_insert = substr($posto_autorizado_dados['nome'], 0,50);
        $fone = substr($posto_autorizado_dados['contato_fone_comercial'],0,20);

        $sql = "INSERT INTO tbl_hd_chamado_extra
            (
                hd_chamado,
                nome,
                cep,
                cidade,
                endereco,
                numero,
                complemento,
                fone,
                email,
                cpf,
                os,
                produto
            )
            VALUES
            (
                {$hd_chamado},
                '{$nome_posto_insert}',
                '{$posto_autorizado_dados['contato_cep']}',
                COALESCE((SELECT cidade FROM tbl_cidade WHERE LOWER(fn_retira_especiais(nome)) = LOWER(fn_retira_especiais('{$posto_autorizado_dados['cidade']}')) LIMIT 1), NULL),
                '{$posto_autorizado_dados['contato_endereco']}',
                '{$posto_autorizado_dados['contato_numero']}',
                '{$posto_autorizado_dados['contato_complemento']}',
                '$fone',
                '{$posto_autorizado_dados['contato_email']}',
                '{$posto_autorizado_dados['cnpj']}',
                {$os},
                {$produtoId}
            )";
        $res = pg_query($con, $sql);

        $sql = "INSERT INTO tbl_hd_chamado_posto
        (
            hd_chamado,
            usuario_sac,
            nome_cliente
        )
        VALUES
        (
            {$hd_chamado},
            'Posto Autorizado',
            '{$consumidor}'
        )";
        $res = pg_query($con, $sql);

        if ($areaAdmin === true) {
            $admin = $login_admin;
            $posto = "null";
        } else {
            $admin = "null";
            $posto = $login_posto;
        }

        $sql = "INSERT INTO tbl_hd_chamado_item
                (
                hd_chamado,
                admin,
                posto,
                comentario,
                interno,
                status_item
                )
                VALUES
                (
                {$hd_chamado},
                {$admin},
                {$posto},
                'Solicitação de abertura OS Fora de garantia',
                false,
                'Ag. Fábrica'
                ) RETURNING hd_chamado_item";
        $res = pg_query($con, $sql);

        $sql = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status) 
                VALUES ({$os}, 256, 'Aguardando Aprovação Helpdesk', {$login_fabrica})";
        pg_query($con,$sql);

        $id_hd_chamado_helpdesk = $hd_chamado;

        atualiza_status_checkpoint($os, 'Aguardando Analise Helpdesk');

    }

}

function buscarInformacoesPosto($posto) {
    global $con, $login_fabrica;

    if (empty($posto)) {
        throw new Exception("Erro ao buscar informações do posto autorizado");
    }

    $sql = "SELECT
                tbl_posto.nome,
                tbl_posto_fabrica.contato_cep,
                tbl_posto_fabrica.contato_estado,
                COALESCE(replace(tbl_ibge.cidade,'''',''), 'null') AS cidade,
                tbl_posto_fabrica.contato_endereco,
                tbl_posto_fabrica.contato_numero,
                tbl_posto_fabrica.contato_complemento,
                tbl_posto_fabrica.contato_fone_comercial,
                tbl_posto_fabrica.contato_email,
                tbl_posto.cnpj,
                tbl_posto_fabrica.cod_ibge
            FROM tbl_posto_fabrica
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            LEFT JOIN tbl_ibge ON tbl_ibge.cod_ibge = tbl_posto_fabrica.cod_ibge
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$posto}";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        throw new Exception("Posto Autorizado não encontrado");
    }

    return array(
        "nome"                   => pg_fetch_result($res, 0, "nome"),
        "contato_cep"            => pg_fetch_result($res, 0, "contato_cep"),
        "contato_estado"         => pg_fetch_result($res, 0, "contato_estado"),
        "cidade"                 => pg_fetch_result($res, 0, "cidade"),
        "contato_endereco"       => pg_fetch_result($res, 0, "contato_endereco"),
        "contato_numero"         => pg_fetch_result($res, 0, "contato_numero"),
        "contato_complemento"    => pg_fetch_result($res, 0, "contato_complemento"),
        "contato_fone_comercial" => pg_fetch_result($res, 0, "contato_fone_comercial"),
        "contato_email"          => pg_fetch_result($res, 0, "contato_email"),
        "cnpj"                   => pg_fetch_result($res, 0, "cnpj"),
        "cod_ibge"               => pg_fetch_result($res, 0, "cod_ibge")
    );
}

function buscarAtendenteMondial($posto, $tipo_solicitacao, $posto_autorizado_dados) {
    global $con, $login_fabrica;

    if (empty($posto)) {
        throw new Exception("Erro ao buscar atendente, posto autorizado não informado");
    }

    $whereClassificacao = "AND tbl_admin_atendente_estado.hd_classificacao IS NULL";

    $cod_ibge_pa = $posto_autorizado_dados["cod_ibge"];
    $estado_pa   = strtoupper($posto_autorizado_dados["contato_estado"]);

    if(!empty($cod_ibge_pa)) {
        $sql = "SELECT tbl_admin_atendente_estado.admin
            FROM tbl_admin_atendente_estado
            INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
            WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
            AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
            AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge_pa}
            AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
            AND tbl_admin.ativo
            AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
            {$whereClassificacao}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            return pg_fetch_result($res, 0, "admin");
        }

        $sql = "SELECT tbl_admin_atendente_estado.admin
            FROM tbl_admin_atendente_estado
            INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
            WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
            AND tbl_admin_atendente_estado.tipo_solicitacao IS NULL
            AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge_pa}
            AND tbl_admin.ativo
            AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
            AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
        {$whereClassificacao}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            return pg_fetch_result($res, 0, "admin");
        }
    }
    $sql = "SELECT tbl_admin_atendente_estado.admin
            FROM tbl_admin_atendente_estado
            INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
            WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
            AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
            AND tbl_admin_atendente_estado.cod_ibge IS NULL
            AND tbl_admin.ativo
            AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
            AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
            {$whereClassificacao}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return pg_fetch_result($res, 0, "admin");
    }

    $sql = "SELECT tbl_admin_atendente_estado.admin
            FROM tbl_admin_atendente_estado
            INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
            WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
            AND tbl_admin_atendente_estado.tipo_solicitacao IS NULL
            AND tbl_admin_atendente_estado.cod_ibge IS NULL
            AND tbl_admin.ativo
            AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
            AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
            {$whereClassificacao}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return pg_fetch_result($res, 0, "admin");
    }

    throw new Exception("Nenhum atendente encontrado para o estado");
}


function valida_anexo_defeito_constatado() {
    
    global $campos, $con, $anexos_obrigatorios;

    $query = "SELECT campos_adicionais FROM tbl_defeito_constatado WHERE defeito_constatado = " . $campos['produto']['defeito_constatado'];

    $resAnexos = pg_query($con, $query);
    $resAnexos = pg_fetch_result($resAnexos, 0, "campos_adicionais");
    $resAnexos = json_decode($resAnexos, 1);

    $anexos_obrigatorios = $resAnexos["anexos_obrigatorios"];

}

function valida_cep() {
    global $campos, $con;

    $cep = $campos["consumidor"]["cep"];

    if (strlen($cep) > 0) {
        $cep = preg_replace("/\D/", "", $cep);

        $sql = "SELECT cep FROM tbl_cep WHERE cep = '{$cep}'";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("CEP Inválido");
        }
    }
}

function valida_consumidor_contato() {
    global $campos, $msg_erro;

    $tipo_os = $campos["os"]["consumidor_revenda"];

    if ($tipo_os == "C") {
        $telefone = trim($campos["consumidor"]["telefone"]);
        $celular  = trim($campos["consumidor"]["celular"]);

        if (empty($telefone) && empty($celular) && empty($email)) {
            $msg_erro["msg"][]    = "É obrigatório informar Telefone ou Celular";
            $msg_erro["campos"][] = "consumidor[telefone]";
            $msg_erro["campos"][] = "consumidor[celular]";
        }
    }
}

/**
 * Função para validação de data de abertura
 */
function valida_data_180() {
    global $campos, $os;

    $data_abertura = $campos["os"]["data_abertura"];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 180 days")) {
            throw new Exception("Data de abertura não pode ser anterior a 6 meses");
        }
    }
}

$grava_os_item_function = "grava_os_item_mondial";

function grava_os_item_mondial($os_produto, $subproduto = "produto_pecas") {
    global $con, $login_fabrica, $login_admin, $campos;

    foreach ($campos[$subproduto] as $posicao => $campos_peca) {
        if (strlen($campos_peca["id"]) > 0) {

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

            if(!strlen($campos_peca['defeito_peca'])) {
                $defeito_peca = "null";
            } else {
                $defeito_peca = $campos_peca["defeito_peca"];
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
                            admin,
                            defeito
                        )
                        VALUES
                        (
                            {$os_produto},
                            {$campos_peca['id']},
                            {$campos_peca['qtde']},
                            {$campos_peca['servico_realizado']},
                            {$devolucao_obrigatoria},
                            {$login_admin},
                            {$defeito_peca}
                        )";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar Ordem de Serviço #9");
                }
            } else {
                $sql = "SELECT tbl_os_item.os_item
                        FROM tbl_os_item
                        LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                        WHERE tbl_os_item.os_produto = {$os_produto}
                        AND tbl_os_item.os_item = {$campos_peca['os_item']}
                        AND tbl_os_item.pedido IS NULL
                        AND (UPPER(tbl_servico_realizado.descricao) NOT IN('CANCELADO', 'TROCA PRODUTO') or tbl_servico_realizado.servico_realizado isnull)";
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
}

function valida_serie_mondial() {

    global $con, $campos, $login_fabrica;

    $produto = $campos['produto']['id'];
    $serie   = preg_replace("/\-/", "", $campos['produto']['serie']);

    if (!empty($produto) && !empty($serie)) {
        $sql = "SELECT mascara, posicao_versao
                  FROM tbl_produto_valida_serie
                 WHERE produto = {$produto}
                   AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        $mascara_ok = null;
        $versao = null;

        while ($mascara = pg_fetch_object($res)) {
            $regExp = str_replace(array('L','N'), array('[A-Z]', '[0-9]'), $mascara->mascara);

            if (preg_match("/$regExp/i", $serie)) {
                $mascara_ok = $mascara->mascara;

                break;
            }
        }
        if ($mascara_ok != null) {

            if (isset($usa_versao_produto)) {
                $versao = versao_produto_nserie($serie, $mascara->posicao_versao);
            }
            $msg["success"] = true;

        } else {
             throw new Exception("Número de série inválido") ;
        }
    } else {
        throw new Exception("Produto não informado");
    }

}

function versao_produto_nserie($serial, $pos, $length=1) {
    $versao = mt_substr($serial, $pos);

    if ($versao === false) {
        throw new Exception("Máscara/série incorretos");
    }

    return $versao;
}

function verifica_posto_interno(){
    global $con, $login_fabrica, $campos, $auditorias;

    $posto = $campos["posto"]["id"];

    $sql = "SELECT tbl_posto_fabrica.posto
            FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.posto_interno IS TRUE
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$posto}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
	$auditorias = array();
    }
}

function verifica_posto_tipo_revenda(){
    global $con, $login_fabrica, $campos, $auditorias;

    $posto = $campos["posto"]["id"];

    $sql = "SELECT tbl_posto_fabrica.tipo_posto FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.tipo_revenda IS TRUE
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$posto}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
	#print_r($auditorias);
	$auditorias = array();
	#echo "antes<br />depois";
	#print_r($auditorias);
	#exit;
    }
}

function verifica_peca_estoque_mondial(){
    global $con, $login_fabrica, $campos, $os;
    $posto = $campos["posto"]["id"];
    
    if(verifica_peca_lancada() === true){
        foreach ($campos['produto_pecas'] as $key => $value) {

            if($value['id'] != ""){
                if($value['servico_realizado'] != ""){
                    $sql = "SELECT servico_realizado FROM tbl_servico_realizado 
                        WHERE gera_pedido IS TRUE AND troca_de_peca IS TRUE 
                            AND servico_realizado = {$value['servico_realizado']}";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){

                        $sql = "SELECT os_status FROM tbl_os_status 
                            WHERE os = {$os} AND status_os = 205 AND fabrica_status = {$login_fabrica}";
                        $res = pg_query($con,$sql);

                        if( verifica_auditoria(array(203, 204, 205), array(205), $os) === true){

                            $sql = "SELECT qtde FROM tbl_estoque_posto 
                                WHERE fabrica = {$login_fabrica} AND posto = {$posto}
                                    AND peca = {$value['id']} AND qtde > 0";
                            $res = pg_query($con,$sql);

                            if(pg_num_rows($res) > 0){
                                pg_query($con,"BEGIN TRANSACTION");

                                $sql = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status) 
                                VALUES ({$os}, 205, 'Auditoria por solicitação de pedido, contendo a peça em estoque', {$login_fabrica})";
                                pg_query($con,$sql);

                                if (strlen(pg_last_error()) > 0) {
                                    pg_query($con,"ROLLBACK");
                                    throw new Exception("Erro ao gravar Auditoria da Ordem de Serviço!");
                                }else{
                                    pg_query($con,"COMMIT");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

function auditoria_os_reincidente_mondial() {
        global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

        $posto = $campos['posto']['id'];

        $sql = "SELECT os, consumidor_revenda FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
        $res = pg_query($con, $sql);

        $consumidor_revenda = strtoupper(pg_fetch_result($res, 0, "consumidor_revenda"));

        if(pg_num_rows($res) > 0 && $consumidor_revenda == "C"){

            $select = "
                SELECT  tbl_os.os
                FROM    tbl_os
                JOIN    tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                WHERE   tbl_os.fabrica          = $login_fabrica
                AND     tbl_os.data_abertura    > (CURRENT_DATE - INTERVAL '90 days')
                AND     tbl_os.excluida         IS NOT TRUE
                AND     tbl_os.os               < $os
                AND     tbl_os.posto            = $posto
                AND     UPPER(tbl_os.nota_fiscal) = UPPER('".trim($campos['os']['nota_fiscal'])."')                 
                AND     UPPER(tbl_os.serie)       = UPPER('".trim($campos['produto']['serie'])."')
                AND     tbl_os.revenda_cnpj     = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
                AND     tbl_os_produto.produto  = ".$campos['produto']['id']."
          ORDER BY      tbl_os.data_abertura DESC
                LIMIT   1
            ";
            
            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

                if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                    $insert = "INSERT INTO tbl_os_status
                            (os, status_os, observacao)
                            VALUES
                            ({$os}, 70, 'OS reincidente de cnpj, nota fiscal, produto e série')";
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

    function verifica_os_aberta_mondial() {
        global $con, $login_fabrica, $os, $campos;

            $posto = $campos['posto']['id'];

            $select = "
                SELECT  tbl_os.os
                FROM    tbl_os
                JOIN    tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                WHERE   tbl_os.fabrica          = $login_fabrica
                AND     tbl_os.data_abertura    > (CURRENT_DATE - INTERVAL '90 days')
                AND     tbl_os.excluida         IS NOT TRUE
                AND     tbl_os.os               < $os
                AND     tbl_os.posto            = $posto
                AND     UPPER(tbl_os.nota_fiscal) = UPPER('".trim($campos['os']['nota_fiscal'])."')                 
                AND     UPPER(tbl_os.serie)       = UPPER('".trim($campos['produto']['serie'])."')
                AND     tbl_os.revenda_cnpj     = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
                AND     tbl_os_produto.produto  = ".$campos['produto']['id']."
                AND     tbl_os.finalizada IS NULL
                AND     tbl_os.data_fechamento IS NULL
          ORDER BY      tbl_os.data_abertura DESC
                LIMIT   1
            ";

            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0) {
                $os_aberta = pg_fetch_result($resSelect, 0, 'os');

                throw new Exception("Já existe uma Ordem de Serviço aberta com os dados informados, os: {$os_aberta}");
            }   

    }

/*
    Reincidente: produto, nota fiscal e revenda prazo de 90 dias;
    Peças Excedentes: acima de 4 peças
    Produto de troca obrigatória
    peça crítica: padrão
*/

function valida_garantia_peca(){
    global $con, $login_fabrica, $campos, $os;

	$peca =$campos['produto_pecas'][0]['id'];
    $defeito = $campos['produto_pecas'][0]['defeito_peca'];
    $data_compra = $campos['os']['data_compra'];

    if((strlen($peca) > 0) AND (strlen($defeito) > 0)){
        $sql_consulta .= "SELECT tbl_peca_defeito_garantia.garantia, tbl_peca.referencia
                                FROM tbl_peca_defeito_garantia 
                                JOIN tbl_defeito on (tbl_peca_defeito_garantia.defeito = tbl_defeito.defeito)
                                JOIN tbl_peca on (tbl_peca.peca = tbl_peca_defeito_garantia.peca)
                                JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_peca.fabrica)
                                WHERE tbl_fabrica.fabrica = $login_fabrica
                                AND tbl_peca.peca = $peca
                                AND tbl_defeito.defeito = $defeito";

        $res_consulta = pg_query($con, $sql_consulta);

        if (pg_num_rows($res_consulta) > 0) {
            $garantia = pg_fetch_result($res_consulta, 0, 'garantia');
            $referencia = pg_fetch_result($res_consulta, 0, 'referencia');

            $data_atual = (new DateTime());
            $data_compraarray = explode('/', $data_compra);
            $data_compra = new DateTime($data_compraarray[2] .'-'. $data_compraarray[1]. '-'. $data_compraarray[0]);
            $data_compra->modify("+ $garantia month");
            if(($data_atual > $data_compra) == true){
                throw new Exception("Peça {$referencia} está fora da garantia");
            }
        }
    }
    
}

function antes_valida_campos_mondial() {
    global $campos, $valida_garantia_item;

    $dentroDaGarantia = valida_garantia(true);

    if (!$dentroDaGarantia) {

        $valida_garantia_item = "";

    }

}

$antes_valida_campos = "antes_valida_campos_mondial";

$pre_funcoes_fabrica = array(
    "verifica_posto_interno",
    "verifica_posto_tipo_revenda",
    "verifica_troca_produto",
    "valida_garantia_peca"
);

$pos_funcoes_fabrica = [
    "grava_helpdesk_os"
];

$auditorias = array(
    "auditoria_os_reincidente_mondial",
    "auditoria_pecas_excedentes",
    "auditoria_troca_obrigatoria",
    "auditoria_peca_critica",
    "verifica_peca_estoque_mondial"
);

/* Funcções fábrica */

function verifica_troca_produto(){

    global $login_fabrica, $campos, $os, $gravando, $con;

    if(strlen(trim($os))>0){
        $id_produto = $campos['produto']['id'];
        $serie_produto = $campos['produto']['serie'];
        $def_constatado_produto = $campos['produto']['defeito_constatado'];
       
        $sql_verifica_produto = "SELECT produto FROM tbl_os WHERE os = $os AND produto = $id_produto";
        $res_verifica_produto = pg_query($con, $sql_verifica_produto);
        if(pg_num_rows($res_verifica_produto) == 0 ){
            $sql_deleta_produto = "DELETE FROM tbl_os_produto where os = $os";
            $res_deleta_produto = pg_query($con, $sql_deleta_produto);
        }
    }
}

$funcoes_fabrica = array("verifica_estoque_peca","verifica_os_aberta_mondial");
