<?php

$regras["os|hd_chamado"]["function"] = array("valida_atendimento_midea_carrier");
$regras["os|defeito_reclamado"]["obrigatorio"] = true;
$regras["os|defeito_reclamado_descricao"]["obrigatorio"] = false;
$regras["os|tipo_atendimento"]["function"] = array("valida_tipo_atendimento_peca_obrigatoria_midea_carrier");
$regras["consumidor|celular"]["function"] = array("valida_celular_os_midea");

/**
 * Somente para validar na abertura da OS se já tiver uma OS gravada
 */
if (!empty($os)) {
    $sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = {$os};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $consumidor_revenda = pg_fetch_result($res, 0, "consumidor_revenda");
    }
}

if (strlen(getValue("os[consumidor_revenda]")) > 0 || strlen($consumidor_revenda) > 0) {
    if (getValue("os[consumidor_revenda]") == 'C' || $consumidor_revenda == 'C') {
        $regras["consumidor|cpf"]["obrigatorio"] = true;
        $regras["consumidor|cep"]["obrigatorio"] = true;
        $regras["consumidor|bairro"]["obrigatorio"] = true;
        $regras["consumidor|endereco"]["obrigatorio"] = true;
        $regras["consumidor|numero"]["obrigatorio"] = true;
        $regras["revenda|nome"]["obrigatorio"] = true;
        $regras["revenda|cnpj"]["obrigatorio"] = true;
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;
        $regras["revenda|contato"]["obrigatorio"] = false;
    } else {
        $regras["os|nota_fiscal"]["obrigatorio"] = false;
        $regras["os|data_compra"]["obrigatorio"] = false;
        $regras["os|defeito_reclamado"]["obrigatorio"] = false;
        $regras["consumidor|cpf"]["obrigatorio"] = false;
        $regras["consumidor|cep"]["obrigatorio"] = false;
        $regras["consumidor|bairro"]["obrigatorio"] = false;
        $regras["consumidor|endereco"]["obrigatorio"] = false;
        $regras["consumidor|numero"]["obrigatorio"] = false;
        $regras["revenda|nome"]["obrigatorio"] = true;
        $regras["revenda|cnpj"]["obrigatorio"] = true;
        $regras["revenda|estado"]["obrigatorio"] = true;
        $regras["revenda|cidade"]["obrigatorio"] = true;
        $regras["revenda|contato"]["obrigatorio"] = true;
        $regras["produto|defeito_constatado"]["function"] = array("valida_defeito_constatado_midea_carrier");
    }
} else {
    $regras["consumidor|cpf"]["obrigatorio"] = true;
    $regras["consumidor|cep"]["obrigatorio"] = true;
    $regras["consumidor|bairro"]["obrigatorio"] = true;
    $regras["consumidor|endereco"]["obrigatorio"] = true;
    $regras["consumidor|numero"]["obrigatorio"] = true;
    $regras["revenda|nome"]["obrigatorio"] = true;
    $regras["revenda|cnpj"]["obrigatorio"] = true;
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
    $regras["revenda|contato"]["obrigatorio"] = false;
}

$revenda_obrigatorio = array(
    "revenda|nome" => array( "obrigatorio" => true ),
    "revenda|cnpj" => array( "obrigatorio" => true ),
    "revenda|estado" => array( "obrigatorio" => true ),
    "revenda|cidade" => array( "obrigatorio" => true ),
    "revenda|contato" => array( "obrigatorio" => true )
);

$consumidor_revenda_obrigatorio = array(
    "revenda|nome" => true,
    "revenda|cnpj" => true
);

$regras["produto|serie"] = array(
    "obrigatorio" => true,
    "function" => array("valida_serie_midea_carrier")
);

$auditoria_bloqueia_pedido = "false";
$pre_funcoes_fabrica = array("valida_produto_os_conjunto_midea_carrier", "verifica_valores_adicionais");
$antes_valida_campos = "antes_valida_campos";
$grava_defeito_peca  = true;
$regras_pecas = array(
    'numero_serie' => true,
    'lista_basica' => true,
    'servico_realizado' => true
);

$funcoes_fabrica = array(
    'grava_os_reoperacao', 
    'valida_agendamento_midea_carrier', 
    'valida_rpi_midea_carrier',
    'grava_agendamento_midea_carrier'
);

$grava_multiplos_defeitos = "grava_multiplos_defeitos_midea_carrier";

function grava_os_fabrica() {
    global $campos;

    $justificativa_adicionais = (strlen($campos["os"]["motivo_visita"]) > 0) ? array("motivo_visita" => utf8_encode($campos["os"]["motivo_visita"])) : array("motivo_visita" => "");
    $justificativa_adicionais = json_encode($justificativa_adicionais);

    return array(
        "defeito_reclamado" => (!empty($campos["os"]["defeito_reclamado"])) ? $campos["os"]["defeito_reclamado"] : "null",
        "os_numero" => (!empty($campos["os"]["os_numero"])) ? $campos["os"]["os_numero"] : "null",
        "consumidor_nome_assinatura" => (!empty($campos["revenda"]["contato"])) ? "'{$campos["revenda"]["contato"]}'" : "null",
        "qtde_diaria" => (!empty($campos["os"]["qtde_visita"])) ? $campos['os']['qtde_visita'] : 0,
        "contrato" => (!empty($campos["produto"]["emprestimo"])) ? "'{$campos["produto"]["emprestimo"]}'" : "'f'",
        "justificativa_adicionais" => "'$justificativa_adicionais'"
    );

}

function grava_os_extra_fabrica() {
    global $campos;

    return array(
        "recolhimento" => (!empty($campos["produto"]["retirado_oficina"])) ? "'{$campos["produto"]["retirado_oficina"]}'" : "'f'",
        "baixada" => "null"
    );
}

function valida_produto_os_conjunto_midea_carrier() {
    global $campos, $con, $login_fabrica;

    $os_numero = $campos["os"]["os_numero"];
    $produto = $campos["produto"]["id"];

    if (!empty($os_numero) && !empty($produto)) {
        $sql = "SELECT produto FROM tbl_os_produto WHERE os = {$os_numero}";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) || pg_num_rows($res) == 0) {
            throw new Exception("Erro ao validar produto");
        } else {
            $produto_os_conjunto = pg_fetch_result($res, 0, "produto");

            if ($produto_os_conjunto == $produto) {
                throw new Exception("O produto não pode ser o mesmo da Ordem de Serviço de origem");
            }
        }
    }
}

function valida_agendamento_midea_carrier() {
    global $con, $login_fabrica, $os, $campos, $areaAdmin;

    if (!$areaAdmin && empty($campos["os"]["os_numero"])) {
        $sql = "
            SELECT
                ta.km_google,
                da.data_agendamento
            FROM tbl_os o
            JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
            LEFT JOIN (
                SELECT
                    ta.data_agendamento,
                    t.posto,
                    ta.os,
                    ta.periodo
                FROM tbl_tecnico_agenda ta
                JOIN tbl_tecnico t USING(tecnico)
                WHERE ta.fabrica = {$login_fabrica}
                AND ta.os = {$os}
                AND ta.confirmado IS NOT NULL
                ORDER BY ta.data_input DESC
                LIMIT 1
            ) da ON da.os = o.os AND da.posto = o.posto
            WHERE o.fabrica = {$login_fabrica}
            AND ta.km_google IS TRUE
            AND o.os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $km_google = pg_fetch_result($res, 0, "km_google");
            $data_agendamento = pg_fetch_result($res, 0, "data_agendamento");

            if ($km_google == 't' && empty($data_agendamento)) {
                throw new Exception("É necessário fazer a confirmação do agendamento para dar continuidade a Ordem de serviço");
            }
        } else {
            $sql = "
                SELECT
                    ta.km_google,
                    da.data_agendamento
                FROM tbl_os o
                JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
                LEFT JOIN (
                    SELECT
                        ta.data_agendamento,
                        ta.tecnico,
                        ta.os,
                        ta.periodo
                    FROM tbl_tecnico_agenda ta
                    WHERE ta.fabrica = {$login_fabrica}
                    AND ta.os = {$os}
                    AND ta.confirmado IS NOT NULL
                    ORDER BY ta.data_input DESC
                    LIMIT 1
                ) da ON da.os = o.os AND da.tecnico IS NULL
                WHERE o.fabrica = {$login_fabrica}
                AND ta.km_google IS TRUE
                AND o.os = {$os}
            ";

            if (pg_num_rows($res) > 0){
                $km_google = pg_fetch_result($res, 0, "km_google");
                $data_agendamento = pg_fetch_result($res, 0, "data_agendamento");
    
                if ($km_google == 't' && empty($data_agendamento)) {
                    throw new Exception("É necessário fazer a confirmação do agendamento para dar continuidade a Ordem de serviço");
                }
            }
        }
    } else if (!$areaAdmin && !empty($campos["os"]["os_numero"])) {
        $sql = "
            SELECT tecnico, data_agendamento, confirmado, ordem, periodo, obs
            FROM tbl_tecnico_agenda
            WHERE fabrica = {$login_fabrica}
            AND os = {$campos['os']['os_numero']}
            AND confirmado IS NOT NULL
            ORDER BY tecnico_agenda DESC
            LIMIT 1
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("É necessário fazer a confirmação do agendamento da Ordem de Serviço de origem");
        } else {
            $agendamento = pg_fetch_assoc($res);

            $sql = "
                SELECT tecnico_agenda
                FROM tbl_tecnico_agenda
                WHERE fabrica = {$login_fabrica}
                AND os = {$os}
                AND data_agendamento = '{$agendamento['data_agendamento']}'
                AND periodo = '{$agendamento['periodo']}'
            ";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                $agendamento["ordem"] = $agendamento["ordem"] + 1;

                $sql = "
                    INSERT INTO tbl_tecnico_agenda
                    (fabrica, os, tecnico, data_agendamento, ordem, confirmado, periodo, obs)
                    VALUES
                    ({$login_fabrica}, {$os}, {$agendamento['tecnico']}, '{$agendamento['data_agendamento']}', {$agendamento['ordem']}, '{$agendamento['confirmado']}', '{$agendamento['periodo']}', E'{$agendamento['obs']}')
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar agendamento #1");
                }
            }
        }
    }

    if ($areaAdmin && verifica_peca_lancada()) {
        $sql = "
            SELECT
                ta.km_google,
                da.data_agendamento
            FROM tbl_os o
            JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
            LEFT JOIN (
                SELECT
                    ta.data_agendamento,
                    t.posto,
                    ta.os,
                    ta.periodo
                FROM tbl_tecnico_agenda ta
                JOIN tbl_tecnico t USING(tecnico)
                WHERE ta.fabrica = {$login_fabrica}
                AND ta.os = {$os}
                AND ta.confirmado IS NOT NULL
                ORDER BY ta.data_input DESC
                LIMIT 1
            ) da ON da.os = o.os AND da.posto = o.posto
            WHERE o.fabrica = {$login_fabrica}
            AND ta.km_google IS TRUE
            AND o.os = {$os};
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $km_google = pg_fetch_result($res, 0, "km_google");
            $data_agendamento = pg_fetch_result($res, 0, "data_agendamento");

            if ($km_google == 't' && empty($data_agendamento)) {
                throw new Exception("É necessário fazer a confirmação do agendamento para lançar peças na Ordem de serviço");
            }
        }
    }
}

function valida_rpi_midea_carrier() {
    global $con, $login_fabrica, $os;

    $sql = "
        SELECT
            o.os,
            rp.rpi,
            f.codigo_validacao_serie
        FROM tbl_os o
        JOIN tbl_os_produto op ON op.os = o.os
        LEFT JOIN tbl_rpi_produto rp ON rp.produto = op.produto AND rp.serie = op.serie AND rp.fabrica = {$login_fabrica}
        JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
        JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND f.codigo_validacao_serie = 'true';
    ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $rpi = pg_fetch_result($res, 0, "rpi");
        $valida_rpi = pg_fetch_result($res, 0, "codigo_validacao_serie");
        if ($valida_rpi == 'true' AND empty($rpi)) {
            throw new Exception("Para dar continuidade nessa Ordem de Serviço é necessário cadastrar o RPI");
        }
    }

}

function valida_serie_midea_carrier() {
    global $campos, $con, $login_posto, $login_fabrica;

    $produto = $campos['produto']['id'];
    $produto_serie = $campos['produto']['serie'];

    if (empty($login_posto)) {
        $posto = $campos["posto"]["id"];
    } else {
        $posto = $login_posto;
    }

    if (strlen($produto_serie) > 0) {
        $sql = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND UPPER(codigo_posto) = UPPER('{$produto_serie}') AND posto = {$posto};";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 0) {
            $sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto} AND numero_serie_obrigatorio IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sql = "SELECT * FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND (serie = UPPER('{$produto_serie}') OR serie = UPPER('S{$produto_serie}'));";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {
                    throw new Exception("Número de Série inválido");
                }
            } else {
                $sql = "SELECT * FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND (serie = UPPER('{$produto_serie}') OR serie = UPPER('S{$produto_serie}'));";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {
                    $sql = "SELECT mascara FROM tbl_produto_valida_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto}";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0) {
                        $mascara_valida = false;

                        while ($r = pg_fetch_object($res)) {
                            $mascara = $r->mascara;
                            $mascara = str_replace("L", "[A-Za-z]", $mascara);
                            $mascara = str_replace("N", "[0-9]", $mascara);

                            if (preg_match("/^{$mascara}$/", $produto_serie)) {
                                $mascara_valida = true;
                                break;
                            }
                        }
                        if (!$mascara_valida) {
                            throw new Exception("Número de Série inválido");
                        }
                    } else {
                        throw new Exception("Número de Série inválido");
                    }
                }
            }
        }
    }
}

function antes_valida_campos() {

    global $os, $campos, $con, $login_fabrica, $login_posto, $valida_garantia, $regras, $valida_anexo, $msg_erro, $auditoria_bloqueia_pedido, $_POST, $anexos_obrigatorios;

    $produto = $campos['produto']['id'];

    if (!empty($produto)) {
        $sqlLinhaAuditoria = "
            SELECT l.linha
            FROM tbl_produto p
            INNER JOIN tbl_linha l ON l.linha = p.linha AND l.fabrica = {$login_fabrica}
            WHERE p.produto = {$produto}
            AND l.auditoria_os IS TRUE
        ";
        $resLinhaAuditoria = pg_query($con, $sqlLinhaAuditoria);

        if (pg_num_rows($resLinhaAuditoria) > 0) {
            $auditoria_bloqueia_pedido = "true";
        }
    }

    $tipo_atendimento = $campos['os']['tipo_atendimento'];

    if (strlen($tipo_atendimento) > 0) {
        $sql = "select fora_garantia, km_google, grupo_atendimento , descricao from tbl_tipo_atendimento where fabrica = {$login_fabrica} and tipo_atendimento = {$tipo_atendimento};";
        $res = pg_query($con,$sql);

        $fora_garantia = pg_fetch_result($res, 0, fora_garantia);
        $km_google = pg_fetch_result($res, 0, km_google);
        $grupo_atendimento = pg_fetch_result($res, 0, grupo_atendimento);
        $tipo_atendimento_descricao = pg_fetch_result($res, 0, 'descricao');

        if ($fora_garantia == 't' || $grupo_atendimento == 'G') {
            $valida_garantia = "";
            $anexos_obrigatorios = [];
            $valida_anexo = null;
            $regras["os|nota_fiscal"]["obrigatorio"] = false;
            $regras["os|data_compra"]["obrigatorio"] = false;
        } else {
            $dc = $campos["produto"]["defeitos_constatados_multiplos"];

            if (!empty($dc)) {
                $sqlDc = "SELECT lista_garantia FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND defeito_constatado IN ({$dc});";
                $resDc = pg_query($con, $sqlDc);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao verificar defeito constatado");
                }

                $dc_fora_garantia = false;
                $dc_sem_defeito = false;
                while ($r = pg_fetch_object($resDc)) {
                    if ($r->lista_garantia == "fora_garantia") {
                        $dc_fora_garantia = true;
                    }

                    if ($r->lista_garantia == "sem_defeito") {
                        $dc_sem_defeito = true;
                    }
                }

		$dc_lancado_sem_peca = false;
		if (!empty($os)) {
		    $sqlDefLancado = "SELECT lista_garantia FROM tbl_os_defeito_reclamado_constatado JOIN tbl_defeito_constatado USING(defeito_constatado) WHERE os = {$os} AND lista_garantia IS NOT NULL;";
		    $resDefLancado = pg_query($con, $sqlDefLancado);

		    if (pg_num_rows($resDefLancado) > 0) {
			$dc_lancado_sem_peca = true;
		    }
		}

                if ($dc_fora_garantia && $dc_lancado_sem_peca === false) {
                    $regras["os|data_compra"]["obrigatorio"] = false;
                    $regras["os|nota_fiscal"]["obrigatorio"] = false;
                    $valida_anexo = null;
                    $valida_garantia = null;

                    if (verifica_peca_lancada(false)) {
                        throw new Exception("Para o defeito selecionado não é permitido o lançamento de peças");
                    }
                }

                if (in_array($tipo_atendimento_descricao, ['Triagem','RMA'])) {

                    if (verifica_peca_lancada(false)) {
                        throw new Exception("Para o tipo atendimento $tipo_atendimento_descricao não é permitido o lançamento de peças");
                    }
                }
                if ($dc_sem_defeito && $dc_lancado_sem_peca === false) {
                    if (verifica_peca_lancada(false)) {
                        throw new Exception("Para o defeito selecionado não é permitido o lançamento de peças");
                    }

                    if (empty($campos["produto"]["defeito_peca"])) {
                        $regras["produto|defeito_peca"]["obrigatorio"] = true;
                    }
                }
            }

            if ($grupo_atendimento == "I") {
                $regras["produto|defeito_constatado"] = array(
                    "function" => array("valida_defeito_constatado_peca_lancada")
                );
            }
        }

    }

    $nserie = $campos['produto']['serie'];
    $produto = $campos['produto']['id'];
    $preferencia = $campos['produto']['referencia'];
    $pdescricao = $campos['produto']['descricao'];
    $data_compra = $campos['os']['data_compra'];

    if (strlen($nserie) > 0 && strlen($produto) > 0 && !empty($data_compra)) {
        $data_compra = str_replace('/', '-', $data_compra);
        $xdata_compra = date('Y-m-d', strtotime($data_compra));

        $sql = "SELECT data_fabricacao FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND (serie = UPPER('{$nserie}') OR serie = UPPER('S{$serie}')) AND produto = {$produto};";
        $res = pg_query($con, $sql);

        $datafabricao = pg_fetch_result($res, 0, 'data_fabricacao');

        if(strtotime($xdata_compra) < strtotime($datafabricao)){
            $msg_erro['msg'][] = 'Data da compra não pode ser menor que a data de fabricação';
            $msg_erro['campos'][] = 'os[data_compra]';
        }
    }

    if (strlen($nserie) > 0) {
        $sql = "
            SELECT
                serie
            FROM tbl_numero_serie
            WHERE fabrica = {$login_fabrica}
            AND (serie = UPPER('{$nserie}')
            OR serie = UPPER('S{$nserie}'));
        ";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $_POST['produto']['serie'] = pg_fetch_result($res, 0, "serie");
            $campos['produto']['serie'] = $_POST['produto']['serie'];
        }
    }

    if ($campos['os']['consumidor_revenda'] == "C") {
        if (!strlen($campos['consumidor']['telefone']) && !strlen($campos['consumidor']['celular'])) {
            $msg_erro['msg'][] = 'É necessário informar pelo menos um número de contato do consumidor';
            $msg_erro['campos'][] = 'consumidor[telefone]';
            $msg_erro['campos'][] = 'consumidor[celular]';
        }
    }

    if ($campos['os']['cortesia'] == 't') {
        $regras["os|nota_fiscal"]["obrigatorio"] = false;
        $valida_anexo = null;
    }

}

$valida_garantia = "valida_garantia_midea_carrier";

function valida_garantia_midea_carrier() {
    global $con, $login_fabrica, $campos, $msg_erro;

	$data_compra   = $campos["os"]["data_compra"];
	$data_abertura = $campos["os"]["data_abertura"];
	$produto       = $campos["produto"]["id"];
    $serie         = $campos["produto"]["serie"];

    $cpf_cnpj      = $campos["consumidor"]["cpf"];
    $cpf_cnpj      = preg_replace("/\D/", "", $cpf_cnpj);


	if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
		$sql = "
            SELECT
                p.garantia,
                JSON_FIELD('garantia_estendida', parametros_adicionais) AS garantia_estendida
            FROM tbl_produto p
            WHERE p.fabrica_i = {$login_fabrica}
            AND p.produto = {$produto}
        ";
		$res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $garantia = (integer) pg_fetch_result($res, 0, "garantia");
            $garantia_estendida = (integer) pg_fetch_result($res, 0, "garantia_estendida");

            if (empty($campos["os"]["os_numero"])) {
                $hd_chamado = $campos["os"]["hd_chamado"];
            } else {
                $sqlHdChamado = "
                    SELECT hd_chamado FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$campos['os']['os_numero']}
                ";
                $resHdchamado = pg_query($con, $sqlHdChamado);

                $hd_chamado = pg_fetch_result($resHdchamado, 0, "hd_chamado");
            }

            if (!empty($hd_chamado)) {
                $sqlHdChamado = "
                    SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}
                ";

                $resHdChamado = pg_query($con, $sqlHdChamado);

                $aca = pg_fetch_result($resHdChamado, 0, "array_campos_adicionais");

                if (!empty($aca)) {
                    $aca = json_decode($aca, true);

                    if (!empty($aca["instalador_id"])) {
                        $garantia += $garantia_estendida;

                        $sql = "SELECT
                                    TO_CHAR(r.data_partida, 'DD/MM/YYYY') AS data_partida
                                FROM tbl_rpi r
                                INNER JOIN tbl_rpi_produto rp ON rp.rpi = r.rpi AND rp.fabrica = $login_fabrica
                                WHERE r.fabrica = $login_fabrica
                                AND rp.produto = $produto
                                AND rp.serie = '$serie'
                                AND r.consumidor_cpf = '{$cpf_cnpj}'";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) > 0){
                            $data_compra = pg_fetch_result($res, 0, 'data_partida');
                            $garantia = 12;
                        }
                    }
                }
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

$valida_anexo = "valida_anexo_midea_carrier";

$anexos_obrigatorios = [];

function valida_anexo_midea_carrier() {
    global $campos, $msg_erro, $login_fabrica, $con, $anexos_obrigatorios, $areaAdmin;

    $serie            = $campos["produto"]["serie"];
    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $posto            = $campos["posto"]["id"];

    if ($campos["os"]["consumidor_revenda"] == "C" && !$areaAdmin) {

        if (!empty($serie) && !empty($tipo_atendimento) && !empty($posto)) {
            $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
            $res = pg_query($con, $sql);

            $codigo_posto = pg_fetch_result($res, 0, "codigo_posto");

            $sql = "SELECT fora_garantia, km_google, grupo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
            $res = pg_query($con, $sql);

            $fora_garantia = pg_fetch_result($res, 0, "fora_garantia");
            $km_google = pg_fetch_result($res, 0, "km_google");
            $grupo_atendimento = pg_fetch_result($res, 0, "grupo_atendimento");

            $os_fora_garantia = false;
            if(!empty($campos["produto"]["defeitos_constatados_multiplos"])){
                $defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

                for($i = 0; $i < count($defeitos); $i++){
                    $def = $defeitos[$i];

                    $sql_def = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$def} AND (lista_garantia = 'fora_garantia')";
                    $res_def = pg_query($con, $sql_def);

                    if (pg_num_rows($res_def) > 0) {
                        $os_fora_garantia = true;
                        break;
                    }
                }
            }

            if ($fora_garantia != "t" && !$os_fora_garantia && $grupo_atendimento != "R") {
                if ($grupo_atendimento == "P" && $km_google != "t") {
                    $anexos_obrigatorios = ["notafiscal","produto"];
                } else if ($grupo_atendimento == "P" && $km_google == "t") {
                    $anexos_obrigatorios = ["notafiscal"];
                }
            }
        }

    } else {

        $sql = "SELECT tipo_atendimento
                FROM tbl_tipo_atendimento
                WHERE tipo_atendimento = {$tipo_atendimento}
                AND (descricao = 'Triagem' OR descricao = 'Reoperação')
                AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            $anexos_obrigatorios = ["etiquetaserie"];

        }

    }

}

function valida_pecas_midea_carrier($nome = "produto_pecas") {

    global $con, $msg_erro, $login_fabrica, $regras_pecas, $regras_subproduto_pecas, $campos;

    if(verifica_peca_lancada(false) === true){

        $pecas_os = array();
        $serie = $campos["produto"]["serie"];
        $produto = $campos["produto"]["id"];

        foreach ($campos[$nome] as $posicao => $campos_peca) {
            $peca       = $campos_peca["id"];
            $cancelada  = $campos_peca["cancelada"];
            $pedido     = $campos_peca["pedido"];
            $referencia = $campos_peca["referencia"];
            $descricao = $campos_peca["descricao"];
            $peca_qtde = $campos_peca["qtde"];

            if (empty($peca)) {
                continue;
            }

            if(strlen(trim($pedido))>0){
                continue;
            }

            if (!empty($peca) && empty($campos_peca["qtde"])) {
                $msg_erro["msg"]["peca_qtde"] = traduz('informe.uma.quantidade.para.a.peca.%', null, null, $referencia);
                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                continue;
            }

            if ($nome == "subproduto_pecas") {
                $regra_validar = $regras_subproduto_pecas;
            } else {
                $regra_validar = $regras_pecas;
            }

            if(isset($campos_peca["defeito_peca"]) && empty($campos_peca["defeito_peca"])){
                $msg_erro["msg"]["peca_qtde"] = traduz('favor.informar.o.defeito.da.peca.%', null, null, $referencia);
                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                continue;
            }

            $pecaEncontrada = false;
            $pecaQtdeOk = false;

            foreach ($regra_validar as $tipo_regra => $regra) {
                if ($tipo_regra == 'numero_serie' && $pecaEncontrada === false && $pecaQtdeOk === false) {
                    if (!empty($serie)) {
                        $sql = "
                            SELECT
                                nsp.qtde
                            FROM tbl_numero_serie_peca nsp
                            JOIN tbl_numero_serie ns ON ns.numero_serie = nsp.numero_serie AND ns.fabrica = {$login_fabrica}
                            WHERE nsp.fabrica = {$login_fabrica}
                            AND (ns.serie = UPPER('{$serie}')
                            OR ns.serie = UPPER('S{$serie}'))
                            AND ns.produto = {$produto}
                            AND nsp.peca = {$peca};
                        ";

                        $res = pg_query($con,$sql);

                        if (pg_num_rows($res) > 0) {
                            $pecaEncontrada = true;
                            $lista_basica_qtde = pg_fetch_result($res, 0, qtde);

                            if (array_key_exists($peca, $pecas_os)) {
                                $pecas_os[$peca]["qtde"] += $peca_qtde;
                            } else {
                                $pecas_os[$peca]["qtde"] = $peca_qtde;
                            }

                            if ($cancelada > 0) {
                                $pecas_os[$peca]["qtde"] -= $cancelada;
                            }

                            if ($pecas_os[$peca]["qtde"] <= $lista_basica_qtde) {
                                $pecaQtdeOk = true;
                            }

                        }
                    }
                }

                if ($tipo_regra == 'lista_basica' && $pecaEncontrada === false && $pecaQtdeOk === false) {
                    if ($nome == "subproduto_pecas") {
                        $produto = $campos["subproduto"]["id"];
                    } else {
                        $produto = $campos["produto"]["id"];
                    }

                    if ($regra == true && !empty($produto)) {
                        $sql = "
                            SELECT
                                qtde
                            FROM tbl_lista_basica
                            WHERE fabrica = {$login_fabrica}
                            AND produto = {$produto}
                            AND peca = {$peca};
                        ";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) > 0) {
                            $pecaEncontrada = true;
                            $lista_basica_qtde = pg_fetch_result($res, 0, qtde);

                            if(array_key_exists($peca, $pecas_os)){
                                $pecas_os[$peca]["qtde"] += $peca_qtde;
                            }else{
                                $pecas_os[$peca]["qtde"] = $peca_qtde;
                            }

                            if($cancelada > 0){
                                $pecas_os[$peca]["qtde"] -= $cancelada;
                            }

                            if ($pecas_os[$peca]["qtde"] <= $lista_basica_qtde) {
                                $pecaQtdeOk = true;
                            }
                        }
                    }
                }

                if ($tipo_regra == 'servico_realizado') {
                    if ($regra === true && !empty($campos_peca["id"]) && empty($campos_peca["servico_realizado"])) {
                        $msg_erro["msg"]["servico_realizado"] = traduz("Selecione o serviço da peça %", null, null, $referencia." - ".$descricao);
                        $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                    }
                }
            }

            if ($pecaEncontrada === false) {
                $msg_erro["msg"]["lista_basica_qtde"] = traduz("Peça % não encontrada na lista básica do produto", null, null, $referencia." - ".$descricao);
                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
            }

            if ($pecaQtdeOk === false) {
                $msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça % maior que a permitida na lista básica", null, null, $referencia." - ".$descricao);
                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
            }

        }
    }
}

$valida_pecas = "valida_pecas_midea_carrier";

function verifica_valores_adicionais()
{
    global $con, $os, $login_fabrica, $campos, $valores_adicionais_gravado;

    if (!empty($os)) {
        $sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os};";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $valores_adicionais = pg_fetch_result($res, 0, valores_adicionais);

            if (strlen($valores_adicionais) > 0) {
                $valores_adicionais_gravado = utf8_decode($valores_adicionais);
                $valores_adicionais_gravado = json_decode($valores_adicionais_gravado, true);
            }
        }
    }
}

function auditoria_troca_obrigatoria_midea_carrier()
{
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;

    $sql = "
        SELECT
            tbl_produto.produto
        FROM tbl_os
        JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os_produto.os = {$os}
        AND tbl_produto.troca_obrigatoria IS TRUE
        AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
        AND tbl_os.consumidor_revenda = 'C';
    ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica(" tbl_auditoria_status.produto = 't' AND tbl_auditoria_os.observacao ILIKE '%troca obrigatória%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Produto de troca obrigatória', {$auditoria_bloqueia_pedido});
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD001");
        }
    }
}

function auditoria_peca_critica_midea_carrier()
{
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
        AND tbl_peca.peca_critica IS TRUE
        AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE;
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
                    AND tbl_peca.peca IN (".implode(", ", $nova_peca).")
                    AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE;
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

function auditoria_pecas_excedentes_midea_carrier()
{
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
                AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                AND tbl_os.consumidor_revenda = 'C';
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
                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
                        ({$os}, {$auditoria_status}, 'OS em auditoria de peças excedentes', {$auditoria_bloqueia_pedido})";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD004");
                    }
                }
            }
        }
    }
}

function auditoria_produto_sem_defeito_midea_carrier()
{
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_os_defeito_reclamado_constatado odrc ON odrc.os = o.os AND odrc.fabrica = {$login_fabrica}
        JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = odrc.defeito_constatado AND dc.fabrica = {$login_fabrica}
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND dc.lista_garantia = 'sem_defeito'
        AND ta.fora_garantia IS NOT TRUE
        AND o.consumidor_revenda = 'C';
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%produto sem defeito%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de produto sem defeito', {$auditoria_bloqueia_pedido});
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD005");
        }
    }
}

function auditoria_produto_sem_nota_fiscal_midea_carrier()
{
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_os_defeito_reclamado_constatado odrc ON odrc.os = o.os AND odrc.fabrica = {$login_fabrica}
        JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = odrc.defeito_constatado AND dc.fabrica = {$login_fabrica}
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND dc.lista_garantia = 'fora_garantia'
        AND ta.fora_garantia IS NOT TRUE
        AND o.consumidor_revenda = 'C';
    ";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%OS sem nota fiscal%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS sem nota fiscal', {$auditoria_bloqueia_pedido});
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD006");
        }
    }

}

function auditoria_produto_split_nova_os_midea_carrier()
{
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND o.os_numero IS NOT NULL
        AND ta.fora_garantia IS NOT TRUE
        AND o.consumidor_revenda = 'C';
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%OS adicional%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'Auditoria de produto Split - OS adicional', {$auditoria_bloqueia_pedido});
        ";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD007");
        }
    }
}

function auditoria_numero_serie_coringa_midea_carrier()
{
    global $con, $os, $login_fabrica;

    $sql = "
        SELECT op.serie
        FROM tbl_os o
        JOIN tbl_os_produto op ON op.os = o.os
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND UPPER(op.serie) = UPPER(pf.codigo_posto)
        AND ta.fora_garantia IS NOT TRUE;
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND tbl_auditoria_os.observacao ILIKE '%número de série%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.numero_serie = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de número de série', TRUE);
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD008");
        }
    }
}

function auditoria_valor_adicional_midea_carrier()
{
    global $con, $os, $login_fabrica, $campos, $valores_adicionais_gravado, $auditoria_bloqueia_pedido;

    $auditar = false;
    $valores_adicionais = $campos["os"]["valor_adicional"];

    if (count($valores_adicionais) > 0) {
        if (count($valores_adicionais) != count($valores_adicionais_gravado)) {
            $auditar = true;
        } else {
            $servicos_adicionais = array();

            foreach ($valores_adicionais_gravado as $key => $value) {
                $servicos_adicionais[] = key($value);
            }

            foreach ($valores_adicionais as $key => $value) {
                list($servico, $valor) = explode("|", $value);

                if (!in_array($servico, $valores_adicionais_gravado)) {
                    $auditar = true;
                }
            }
        }

        $total = 0;

        foreach($valores_adicionais as $key => $value) {
            list($servico, $valor) = explode("|", $value);
            $total += (double) $valor;
        }
    } else if (!count($valores_adicionais)) {
        $sql = "
            UPDATE tbl_auditoria_os SET
                cancelada = CURRENT_TIMESTAMP,
                justificativa = 'Cancelamento automático, valores adicionais removidos pelo posto autorizado'
            WHERE os = {$os}
            AND observacao ~ 'OS em auditoria de valores adicionais'
            AND liberada IS NULL
            AND reprovada IS NULL
            AND cancelada IS NULL
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD009");
        }
    }

    if ($auditar === true) {
        if ($total > 500) {
            $msg_auditoria = "OS em auditoria de valores adicionais acima de R$ 500,00";
        } else {
            $msg_auditoria = "OS em auditoria de valores adicionais abaixo de R$ 500,00";
        }

        #var_dump($msg_auditoria);exit;

        if (verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.liberada IS NULL AND tbl_auditoria_os.observacao ILIKE '%valores adicionais%'", $os) === true) {
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }

            $sql = "
                INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os}, {$auditoria_status}, '{$msg_auditoria}', {$auditoria_bloqueia_pedido});
            ";
        } else {
            $sql = "
                UPDATE tbl_auditoria_os SET
                    observacao = '{$msg_auditoria}'
                WHERE os = {$os}
                AND observacao ~ 'OS em auditoria de valores adicionais'
                AND liberada IS NULL
                AND reprovada IS NULL
                AND cancelada IS NULL
            ";
        }
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD009");
        }
    }
}

function auditoria_os_reincidente_midea_carrier()
{
    global $con, $os, $login_fabrica, $campos, $os_reincidente, $os_reincidente_numero, $os_reincidente_justificativa, $auditoria_bloqueia_pedido;

    $posto = $campos['posto']['id'];

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_os_produto op ON op.os = o.os
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND UPPER(op.serie) != UPPER(pf.codigo_posto)
        AND ta.fora_garantia IS NOT TRUE
        AND o.os_reincidente IS NOT TRUE
        AND o.excluida IS NOT TRUE
        AND o.consumidor_revenda = 'C';
    ";

    $res = pg_query($con,$sql);

    $os_reincidente_justificativa = false;

    if (pg_num_rows($res) > 0) {

        $select = "
            SELECT tbl_os.os
            FROM tbl_os
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.os < {$os}
            AND tbl_os.posto = {$posto}
            AND (tbl_os_produto.serie = UPPER('{$campos['produto']['serie']}')
            OR tbl_os_produto.serie = UPPER('S{$campos['produto']['serie']}'))
            AND tbl_os_produto.produto = {$campos['produto']['id']}
            AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
            AND tbl_os.consumidor_revenda = 'C'
            ORDER BY tbl_os.data_abertura DESC
            LIMIT 1;
        ";

        $resSelect = pg_query($con,$select);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS reincidente', {$auditoria_bloqueia_pedido});
                ";

                pg_query($con,$sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD010");
                } else {
                    $os_reincidente_justificativa = true;
                    $os_reincidente = true;
                }
            }

        } else if (verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
            $data_compra = formata_data($campos['os']['data_compra']);

            if (!empty($data_compra)) {

                $select = "
                    SELECT
                        tbl_os.os
                    FROM tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '1 year')
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.os < {$os}
                    AND tbl_os.consumidor_cpf = '{$campos['consumidor']['cpf']}'
                    AND tbl_os.data_nf != '{$data_compra}'
                    AND (tbl_os_produto.serie = UPPER('{$campos['produto']['serie']}')
                    OR tbl_os_produto.serie = UPPER('S{$campos['produto']['serie']}'))
                    AND tbl_os_produto.produto = {$campos['produto']['id']}
                    AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                    AND tbl_os.consumidor_revenda = 'C'
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1;
                ";

                $resSelect = pg_query($con,$select);

                if (pg_num_rows($resSelect) > 0) {
                    $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

                    if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                        $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                        if($busca['resultado']){
                            $auditoria_status = $busca['auditoria'];
                        }

                        $sql = "
                            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                            VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS reincidente', {$auditoria_bloqueia_pedido});
                        ";

                        pg_query($con,$sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao lançar ordem de serviço #AUD011");
                        } else {
                            $os_reincidente = true;
                        }
                    }
                }
            }
        }
    }
}

function auditoria_km_midea_carrier()
{
    global $con, $os, $login_fabrica, $campos;

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND ta.fora_garantia IS NOT TRUE;
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.km = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria de KM%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $qtde_km = $campos["os"]["qtde_km"];
        $qtde_km_anterior = $campos["os"]["qtde_km_hidden"];

        if (!strlen($campos["os"]["qtde_km_hidden"])) {
            $campos["os"]["qtde_km_hidden"] = $campos["os"]["qtde_km"];
        }

        if ($qtde_km > 150) {
            if ($qtde_km != $campos["os"]["qtde_km_hidden"]){
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM, KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                ";
            }else{
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM', false);
                ";
            }
        } else if ($qtde_km > 59 && $qtde_km != $campos["os"]["qtde_km_hidden"]) {
            $sql = "
                INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM, KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
            ";
        } else if ($qtde_km != $campos["os"]["qtde_km_hidden"]) {
            $programa_insert = $_SERVER['PHP_SELF'];
            $sql = "
                INSERT INTO tbl_os_interacao
                (programa,fabrica, os, comentario, interno)
                VALUES
                ('{$programa_insert}',{$login_fabrica}, {$os}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', 'true');
            ";
        }

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD012");
        }
    }
}

$auditorias = array(
    "auditoria_troca_obrigatoria_midea_carrier",
    "auditoria_peca_critica_midea_carrier",
    "auditoria_pecas_excedentes_midea_carrier",
    /*"auditoria_produto_sem_defeito_midea_carrier",*/
    "auditoria_produto_sem_nota_fiscal_midea_carrier",
    "auditoria_produto_split_nova_os_midea_carrier",
    "auditoria_numero_serie_coringa_midea_carrier",
    "auditoria_valor_adicional_midea_carrier",
    "auditoria_os_reincidente_midea_carrier",
    "auditoria_km_midea_carrier"
);

function grava_os_reincidente_midea_carrier($os_reincidente_numero) {
    global $con, $login_fabrica, $os, $areaAdmin, $os_reincidente_justificativa;

    $sql = "UPDATE tbl_os SET os_reincidente = TRUE WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    $sql = "UPDATE tbl_os_extra SET os_reincidente = {$os_reincidente_numero} WHERE os = {$os}";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao lançar ordem de serviço reincidente");
    }

    if ($os_reincidente_justificativa === true) {
        header("Location: os_motivo_atraso.php?os={$os}&justificativa=ok");
    } else {
        header("Location: os_press.php?os={$os}");
    }
}

$grava_os_reincidente = "grava_os_reincidente_midea_carrier";

function valida_atendimento_midea_carrier() {
	global $con, $campos, $login_posto, $login_fabrica, $os, $usaProdutoGenerico, $areaAdmin;

	$hd_chamado = $campos["os"]["hd_chamado"];

	if ($areaAdmin === true) {
		$posto = $campos['posto']['id'];
	} else {
		$posto = $login_posto;
	}

	if (strlen($hd_chamado) > 0 && empty($os)) {
		$sql = "
			SELECT
				tbl_hd_chamado.hd_chamado
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
			AND tbl_hd_chamado_extra.posto = {$posto}
			AND tbl_hd_chamado.hd_chamado = {$hd_chamado};
		";

		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Pre-OS não pertence ao Posto Autorizado");
		}
	}
}

function valida_defeito_constatado_midea_carrier() {
    global $campos, $defeitoConstatadoMultiplo;

    if (isset($defeitoConstatadoMultiplo)) {
        $defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
    } else {
        $defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
    }

    $defeitos_constatados = array_filter($defeitos_constatados);

    if (count($defeitos_constatados) == 0) {
        throw new Exception("É necessário informar o defeito constatado");
    }
}

function grava_multiplos_defeitos_midea_carrier() {
    global $con, $os, $campos, $login_fabrica;

    $produto_defeito_peca = $campos["produto"]["defeito_peca"];

    if(!empty($campos["produto"]["defeitos_constatados_multiplos"])){

        $defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

        for($i = 0; $i < count($defeitos); $i++){
            $def = $defeitos[$i];
            $colDef = "";
            $colDefVal = "";

            if (!empty($produto_defeito_peca)) {
                $sql = "SELECT defeito FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$def} AND defeito = {$produto_defeito_peca};";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $defeito = pg_fetch_result($res, 0, "defeito");
                    $colDef = ", defeito";
                    $colDefVal = ", {$defeito}";
                }

            }

            $sql_def = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_constatado = {$def};";
            $res_def = pg_query($con, $sql_def);

            if (!pg_num_rows($res_def)) {
                $sql_def = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_constatado, fabrica {$colDef}) VALUES ({$os}, {$def}, {$login_fabrica} {$colDefVal});";
                $res_def = pg_query($con, $sql_def);
            }
        }

    }
}

function finaliza_os_midea_carrier() {
    global $con, $os, $login_posto, $login_fabrica, $campos;

    $sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$campos['os']['tipo_atendimento']};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $tipo_atendimento_descricao = pg_fetch_result($res, 0, "descricao");
    }

    if (in_array($tipo_atendimento_descricao, array('Triagem', 'RMA'))) {

        $atendimento_callcenter == false;

        if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
            include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
            $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
            $classOs = new $className($login_fabrica, $os, $con);
        } else {
            $classOs = new \Posvenda\Os($login_fabrica, $os, $con);
        }

        $classOs->calculaOs();
        $atendimento_callcenter = $classOs->verificaAtendimentoCallcenter($os);
        $classOs->finaliza($con);

        if ($atendimento_callcenter !== false) {
            $classOs->finalizaAtendimentoCallcenter($atendimento_callcenter);
        }
    }
}

$finaliza_os = "finaliza_os_midea_carrier";

function grava_os_reoperacao()
{
    global $con, $login_fabrica, $login_admin, $os, $campos, $areaAdmin;

    $sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 && $campos['os']['reoperacao'] == 't') {

        /**
         * Grava tbl_os
         */
        if (function_exists("grava_os_fabrica")) {
            /**
             * A função grava_os_fabrica deve ficar dentro do arquivo de regras fábrica
             * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
             */
            $tbl_os = grava_os_fabrica();

            if (!empty($os) and is_array($tbl_os)) {
                $tbl_os_update = array();

                foreach ($tbl_os as $key => $value) {
                    $tbl_os_update[] = "{$key} = {$value}";
                }
            }
        }

        $res_sua_os = pg_fetch_result($res, 0, "sua_os");

        if (!empty($res_sua_os)) {
            $os_revenda = explode("-", $res_sua_os);
            $os_revenda = $os_revenda[0];
        }

        $sql = "SELECT COUNT(*) FROM tbl_os WHERE sua_os LIKE '{$os_revenda}-%';";
        $res = pg_query($con, $sql);

        $contOrdens = (pg_fetch_result($res, 0, 0)+1);
        $sua_os = $os_revenda.'-'.$contOrdens;

        $sql = "
            INSERT INTO tbl_os (
                fabrica,
                sua_os,
                data_digitacao,
                posto,
                data_abertura,
                revenda,
                obs,
                consumidor_revenda,
                tipo_atendimento,
                admin,
                revenda_cnpj,
                revenda_nome,
                revenda_fone,
                produto,
                serie,
                nota_fiscal,
                data_nf
                ".((isset($tbl_os)) ? ", ".implode(", ", array_keys($tbl_os)) : "")."
            ) VALUES (
                {$login_fabrica},
                '{$sua_os}',
                now(),
                {$campos['posto']['id']},
                '".formata_data($campos['os']['data_abertura'])."',
                {$campos['revenda']['id']},
                ".((!empty($campos['os']['observacoes'])) ? "'".$campos['os']['observacoes']."'" : "null").",
                'R',
                (SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao LIKE 'Reopera%'),
                ".((!empty($login_admin)) ? $login_admin : "null").",
                ".((!empty($campos['revenda']['cnpj'])) ? "'".preg_replace("/[\.\-\/]/", "", $campos['revenda']['cnpj'])."'" : "null").",
                ".((!empty($campos['revenda']['nome'])) ? "'".$campos['revenda']['nome']."'" : "null").",
                ".((!empty($campos['revenda']['telefone'])) ? "'".$campos['revenda']['telefone']."'" : "null").",
                {$campos['produto']['id']},
                ".((!empty($campos['produto']['serie'])) ? "'".$campos['produto']['serie']."'" : "null").",
                ".((!empty($campos['os']['nota_fiscal'])) ? "'".$campos['os']['nota_fiscal']."'" : "null").",
                ".((!empty($campos['os']['data_compra'])) ? "'".formata_data($campos['os']['data_compra'])."'" : "null")."
                ".((isset($tbl_os)) ? ", ".implode(", ", $tbl_os) : "")."
            ) RETURNING os;
        ";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar a OS Reoperação #1");
        }

        $nova_os = pg_fetch_result($res, 0, "os");

        if (!empty($nova_os)) {
            $sql = "
                INSERT INTO tbl_os_produto (
                    os, produto, serie
                ) VALUES (
                    {$nova_os}, {$campos['produto']['id']}, ".((!empty($campos['produto']['serie'])) ? "'".$campos['produto']['serie']."'" : "null")."
                );
            ";

            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao gravar a OS Reoperação #2");
            }

            $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os};";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");

                if (!empty($campos_adicionais)) {
                    $campos_adicionais = json_decode($campos_adicionais,true);
                }

                $campos_adicionais['os_reoperacao'] = $nova_os;
                $campos_adicionais = json_encode($campos_adicionais);

                $insert = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campos_adicionais}' WHERE os = {$os} AND fabrica = {$login_fabrica};";
            } else {
                $campos_adicionais['os_reoperacao'] = $nova_os;
                $campos_adicionais = json_encode($campos_adicionais);

                $insert = "INSERT INTO tbl_os_campo_extra (os,fabrica,campos_adicionais) VALUES ({$os},{$login_fabrica},'{$campos_adicionais}');";
            }

            $res = pg_query($con, $insert);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao gravar a OS Reoperação #3");
            }

        }

        /**
         * Auditoria
         */
        call_user_func("auditoria", $auditorias, $nova_os);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar a OS Reoperação #4");
        }
    }
}

function grava_anexo_os($os = null)
{
    if (empty($os)) {
        return false;
    }

    global $campos, $s3;

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

function valida_tipo_atendimento_peca_obrigatoria_midea_carrier() {
    global $campos, $con;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (!empty($tipo_atendimento)) {
        $sql = "
            SELECT LOWER(descricao) AS descricao
            FROM tbl_tipo_atendimento 
            WHERE tipo_atendimento = $tipo_atendimento 
            AND grupo_atendimento = 'I'
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $descricao = pg_fetch_result($res, 0, 'descricao');

            if ($descricao == "instalação" && verifica_peca_lancada(false)) {
                throw new Exception("Lançamento de peça não habilitado para o tipo de atendimento selecionado");
            } else if (($descricao == "conversão" || $descricao == "instalação e conversão") && !verifica_peca_lancada(false)) {
                throw new Exception("É obrigatório o lançamento de peça para o tipo de atendimento selecionado");
            }
        }
    }
}

function grava_agendamento_midea_carrier() {
    global $con, $areaAdmin, $os, $login_fabrica, $campos, $login_admin;

    if ($areaAdmin) {
        $tipo_atendimento = $campos["os"]["tipo_atendimento"];

        $sql = "
            SELECT km_google 
            FROM tbl_tipo_atendimento 
            WHERE fabrica = {$login_fabrica} 
            AND tipo_atendimento = {$tipo_atendimento}
            AND km_google IS TRUE
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $sql = "
                SELECT tecnico_agenda
                FROM tbl_tecnico_agenda
                WHERE fabrica = {$login_fabrica}
                AND os = {$os}
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0) {
                $sql = "
                    INSERT INTO tbl_Tecnico_agenda
                    (fabrica, admin, os, data_agendamento, ordem, confirmado, periodo)
                    VALUES
                    ({$login_fabrica}, {$login_admin}, {$os}, CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, 'manha')
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar agendamento");
                }
            }
        }
    }
}

function valida_celular_os_midea() {
    global $campos, $os, $login_fabrica, $con, $areaAdmin;

    $celular = $campos["consumidor"]["celular"];

    $validaCel = false;

    if (!empty($os) && !$areaAdmin) {
        $sql = "SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND consumidor_celular IS NOT NULL";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0) {
            $validaCel = true;
        }
    }

    if (strlen($celular) > 0 && ($areaAdmin || $validaCel)) {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        try {
            $celular          = $phoneUtil->parse("+55".$celular, "BR");
            $isValid          = $phoneUtil->isValidNumber($celular);
            $numberType       = $phoneUtil->getNumberType($celular);
            $mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

            if (!$isValid || $numberType != $mobileNumberType) {
                throw new Exception("Número de Celular inválido");
            }
        } catch (\libphonenumber\NumberParseException $e) {
            throw new Exception("Número de Celular inválido");
        }
    }
}