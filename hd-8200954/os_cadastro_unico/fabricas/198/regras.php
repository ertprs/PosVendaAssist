<?php

$regras["consumidor|nome"]["obrigatorio"]          = true;
$regras["consumidor|cpf"]["obrigatorio"]           = true;
$regras["consumidor|cep"]["obrigatorio"]           = true;
$regras["consumidor|estado"]["obrigatorio"]        = true;
$regras["consumidor|cidade"]["obrigatorio"]        = true;
$regras["consumidor|bairro"]["obrigatorio"]        = true;
$regras["consumidor|endereco"]["obrigatorio"]      = true;
$regras["consumidor|numero"]["obrigatorio"]        = true;
$regras["consumidor|email"]["obrigatorio"]         = true; 
$regras["os|data_abertura"]                        = true;

$regras["consumidor|telefone"] = array(
    "obrigatorio" => false,
    "function" => array("valida_consumidor_contato")
);

$regras["produto|serie"] = array(
    "function"     => array("valida_numero_de_serie", "valida_serie_frigelar")
);

$auditorias = array(
    "auditoria_os_reincidente_frigelar",
    "auditoria_peca_critica_frigelar",
    "auditoria_troca_obrigatoria_frigelar",
    "auditoria_km_frigelar"
);

function valida_consumidor_contato() {
        global $campos, $msg_erro, $con, $login_fabrica;

        $tipo_os = $campos["os"]["consumidor_revenda"];
        $posto = $campos["posto"]["id"];
        $email = $campos["consumidor"]["email"];

        if ($tipo_os == "C") {
            $telefone = trim($campos["consumidor"]["telefone"]);
            $celular  = trim($campos["consumidor"]["celular"]);

            $telefone = preg_replace("/[^0-9]/", "",$telefone);
            $celular = preg_replace("/[^0-9]/", "",$celular);

            if (empty($telefone) && empty($celular)) {
                $msg_erro["msg"][]    = "É obrigatório informar Telefone ou Celular";
                $msg_erro["campos"][] = "consumidor[telefone]";
                $msg_erro["campos"][] = "consumidor[celular]";
            }
        }

        $sql = "SELECT contato_email, contato_fone_comercial, contato_cel, contato_fax FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $email_posto = pg_fetch_result($res, 0, 'contato_email');
            $fone_posto = pg_fetch_result($res, 0, 'contato_fone_comercial');
            $fax_posto = pg_fetch_result($res, 0, 'contato_fax');
            $celular_posto = pg_fetch_result($res, 0, 'contato_cel');

            $fone_posto = preg_replace("/[^0-9]/", "",$fone_posto);
            $fax_posto = preg_replace("/[^0-9]/", "",$fax_posto);
            $celular_posto = preg_replace("/[^0-9]/", "",$celular_posto);

            if(strlen(trim($telefone)) > 0){
                if($telefone == $fone_posto OR $telefone == $fax_posto){
                    $msg_erro["msg"][]    = "Telefone inválido";
                    $msg_erro["campos"][] = "consumidor[telefone]";
                }
            }

            if(strlen(trim($celular)) > 0){
                if($celular == $fone_posto OR $celular == $fax_posto){
                    $msg_erro["msg"][]    = "Celular inválido";
                    $msg_erro["campos"][] = "consumidor[celular]";
                }
            }

            if(strlen(trim($celular)) > 0){
                if($celular == $celular_posto OR $celular == $fax_posto OR $celular == $fone_posto){
                    $msg_erro["msg"][]    = "Celular inválido";
                    $msg_erro["campos"][] = "consumidor[celular]";
                }
            }

            if(strlen(trim($email)) > 0){
                if($email == $email_posto){
                    $msg_erro["msg"][]    = "Email inválido.";
                    $msg_erro["campos"][] = "consumidor[email]";
                }
            }
        }
    }

function auditoria_os_reincidente_frigelar(){
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
	if(pg_num_rows($res) > 0 && strlen($campos['produto']['id']) > 0){

		$select = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.os < {$os}
				AND tbl_os.posto = $posto
				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
				AND length(tbl_os.nota_fiscal) > 0
				AND tbl_os_produto.produto = {$campos['produto']['id']}
                AND tbl_os.consumidor_revenda != 'R'
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $select);

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
            $busca                 = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

            if ($busca['resultado']) {
                $auditoria_status = $busca['auditoria'];
            }

            $observacao = "OS Reincidente por NOTA FISCAL e PRODUTO. OS Reincidente: ".$os_reincidente_numero;
            $sql        = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES ({$os}, $auditoria_status, '$observacao', true)";
            $res        = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #4");
            } else {
                $os_reincidente = true;
            }
		}
	}
}

function auditoria_peca_critica_frigelar(){
    global $con, $os, $login_fabrica, $qtde_pecas;
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
                VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', 't') ";
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
                        VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', 't');";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD003");
                    }
                }
            }
        }
    }
}

function auditoria_troca_obrigatoria_frigelar() {
	global $con, $os, $login_fabrica;

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

        $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
            ({$os}, $auditoria_status, 'OS em intervenção da fábrica por Produto de troca obrigatória', 't')";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao lançar ordem de serviço");
		}
	}
}

function auditoria_km_frigelar(){
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

            if ($qtde_km >= 100) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM', true);
                ";
            } elseif ($qtde_km <> $qtde_km_anterior AND $qtde_km_anterior > 0) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', true);
                ";
            }
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #AUD012");
            }
        } else {
            if ($qtde_km <> $qtde_km_anterior AND $qtde_km_anterior > 0) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', true);
                    ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD012");
                }
            }
        }
    }
}

function valida_serie_frigelar() {

    global $con, $campos, $login_fabrica;

    $produto = $campos['produto']['id'];
    $serie   = preg_replace("/\-/", "", $campos['produto']['serie']);

    $sql = "SELECT numero_serie_obrigatorio
            FROM tbl_produto WHERE fabrica_i = $login_fabrica 
            AND produto = $produto";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
       $isObrigatorio = pg_fetch_result($res, 0, 'numero_serie_obrigatorio');
       
       if ($isObrigatorio == 't' || $isObrigatorio === true) {
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
                if (empty($produto)) {
                    throw new Exception("Produto não informado");
                } else if (empty($serie)) {
                    throw new Exception("Nº de Série não informado");
                } else {
                    throw new Exception("Produto e/ou Nº de Série não informado");
                }
            }

       }
    }

}

// function auditoria_fabrica_itatiaia(){
//     global $con, $login_fabrica, $os, $campos, $tipo_atendimento_arr;

//     if (count($campos["os"]["valor_adicional"]) > 0) {

//         $auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

//         if($auditoria['resultado']){
//             $auditoria_status = $auditoria['auditoria'];
//         }
//         $sql = "SELECT tbl_auditoria_os.os,
//                        tbl_auditoria_os.auditoria_os,
//                        tbl_auditoria_os.liberada,
//                        tbl_auditoria_os.reprovada
//                   FROM tbl_auditoria_os
//                   JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
//                  WHERE tbl_auditoria_os.os = {$os}
//                    AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
//                    AND tbl_auditoria_os.observacao ILIKE '%Auditoria de F%'";
//         $res = pg_query($con, $sql);
//         if (pg_num_rows($res) == 0) {
//             $sql = "INSERT INTO tbl_auditoria_os (
//                                                     os,
//                                                     auditoria_status,
//                                                     observacao
//                                                 ) VALUES
//                                                 (
//                                                     {$os},
//                                                     $auditoria_status,
//                                                     'Auditoria de Fábrica: Valores Adicionais'
//                                                 )";
//             $res = pg_query($con, $sql);
//             if (strlen(pg_last_error()) > 0) {
//                 throw new Exception("Erro ao lançar ordem de serviço");
//             }
//         }
//     }
// } 