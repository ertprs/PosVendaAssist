<?

function grava_os_revenda_fabrica()
{

    global $campos;

    return array(
        "quem_abriu_chamado" => (!empty($campos["revenda_contato"])) ? "'{$campos["revenda_contato"]}'" : "null",
    );

}

function grava_os_fabrica()
{

    global $campos;

    return array(
        "consumidor_nome_assinatura" => (!empty($campos["revenda_contato"])) ? "'{$campos["revenda_contato"]}'" : "null",
    );

}

function grava_os_dupla_midea_carrier()
{

    global $campos, $con, $os_revenda, $login_fabrica;

    foreach($campos['produtos'] as $array_produto) {
        if (!empty($array_produto['tipo_atendimento'])) {
            $sql = "SELECT * FROM tbl_tipo_atendimento WHERE tipo_atendimento = {$array_produto['tipo_atendimento']} AND fabrica = {$login_fabrica} AND grupo_atendimento = 'G';
            ";
            $res = pg_query($con, $sql);

            $countOS = "SELECT COUNT(*) FROM tbl_os WHERE sua_os LIKE '{$os_revenda}-%' AND fabrica = {$login_fabrica};";
            $resCountOS = pg_query($con, $countOS);
            $sumTot = pg_fetch_result($resCountOS, 0, 0);

            if (pg_num_rows($res) > 0) {
                $grupo_atendimento = pg_fetch_result($res, 0, "grupo_atendimento");
                if ($grupo_atendimento == 'G') {
                    $countQtdeProd = 1;
                    while($countQtdeProd <= $array_produto['qtde']) {
                        $sua_os = $os_revenda."-".$sumTot;
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
                                data_nf,
                                consumidor_nome_assinatura
                            ) VALUES (
                                {$login_fabrica},
                                '{$sua_os}',
                                now(),
                                {$campos['posto_id']},
                                '".formata_data($campos['data_abertura'])."',
                                {$campos['revenda']},
                                ".((!empty($campos['obs'])) ? "'".$campos['obs']."<br/>OS de Reoperação aberta para Revenda'" : " 'OS de Reoperação aberta para Revenda' ").",
                                'R',
                                (SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao LIKE 'Reopera%'),
                                ".((!empty($login_admin)) ? $login_admin : "null").",
                                ".((!empty($campos['revenda_cnpj'])) ? "'".preg_replace("/[\.\-\/]/", "", $campos['revenda_cnpj'])."'" : "null").",
                                ".((!empty($campos['revenda_nome'])) ? "'".$campos['revenda_nome']."'" : "null").",
                                ".((!empty($campos['revenda_fone'])) ? "'".$campos['revenda_fone']."'" : "null").",
                                {$array_produto['id']},
                                ".((!empty($array_produto['serie'])) ? "'".$array_produto['serie']."'" : "null").",
                                ".((!empty($array_produto['nota_fiscal']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".$array_produto['nota_fiscal']."'" : "null").",
                                ".((!empty($array_produto['data_nf']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".formata_data($array_produto['data_nf'])."'" : "null").",
                                ".((!empty($campos['revenda_contato'])) ? "'".$campos['revenda_contato']."'" : "null")."
                            ) RETURNING os;
                        ";

                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao duplicar a OS Revenda do tipo (Triagem/Reoperação) #1");
                        }

                        $os = pg_fetch_result($res, 0, "os");

                        if (!empty($os)) {
                            $sql = "
                                INSERT INTO tbl_os_produto (
                                    os, produto, serie
                                ) VALUES (
                                    {$os}, {$array_produto['id']}, ".((!empty($array_produto['serie'])) ? "'".$array_produto['serie']."'" : "null")."
                                );
                            ";

                            $res = pg_query($con, $sql);

                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao duplicar a OS Revenda do tipo (Triagem/Reoperação) #2");
                            }

			    $sql = "
                        	INSERT INTO tbl_os_extra (
                            	    os, i_fabrica
                        	) VALUES (
                                    {$os}, {$login_fabrica}
                        	);
                    	    ";

                    	    $res = pg_query($con, $sql);

                    	    if (strlen(pg_last_error()) > 0) {
                        	throw new Exception("Erro ao duplicar a OS Revenda do tipo (Triagem/Reoperação) #3");
                    	    }

                            $sql = "
                                SELECT
                                    o.os,
                                    oce.campos_adicionais,
                                    CASE WHEN oce.os IS NOT NULL THEN 't' ELSE 'f' END AS os_campo_extra
                                FROM tbl_os o
                                LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = {$login_fabrica}
                                JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                                WHERE o.fabrica = {$login_fabrica}
                                AND (JSON_FIELD('os_reoperacao', campos_adicionais) = ''
                                OR oce.os IS NULL)
                                AND o.sua_os LIKE '{$os_revenda}-%'
                                AND o.produto = {$array_produto['id']}
                                AND (ta.descricao = 'Triagem'
                                OR ta.grupo_atendimento = 'G')
                                AND o.nota_fiscal ".((!empty($array_produto['nota_fiscal']) && $array_produto['nota_fiscal'] != 'semNota') ? "= '".$array_produto['nota_fiscal']."'" : "IS NULL")."
                                ORDER BY o.os
                                LIMIT 1;
                            ";

                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                $campos_adicionais  = pg_fetch_result($res, 0, "campos_adicionais");
                                $os_triagem         = pg_fetch_result($res, 0, "os");
                                $os_campo_extra     = pg_fetch_result($res, 0, "os_campo_extra");

                                if (!empty($campos_adicionais)) {
                                    $campos_adicionais = json_decode($campos_adicionais,true);
                                }

                                $campos_adicionais['os_reoperacao'] = $os;
                                $campos_adicionais = json_encode($campos_adicionais);

                                if ($os_campo_extra == 't') {
                                    $insert = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campos_adicionais}' WHERE os = {$os_triagem} AND fabrica = {$login_fabrica};";
                                } else {
                                    $insert = "INSERT INTO tbl_os_campo_extra (os,fabrica,campos_adicionais) VALUES ({$os_triagem},{$login_fabrica},'{$campos_adicionais}');";
                                }

                                $res = pg_query($con, $insert);

                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception("Erro ao duplicar a OS Revenda do tipo (Triagem/Reoperação) #4");
                                }

                            }

                        }

                        /**
                         * Move os anexos do bucket temporario para o bucket da Ordem de Serviço
                         */
                        if (!empty($grava_anexo_os) && function_exists($grava_anexo_os)) {
                            $grava_anexo_os($os);
                        }

                        /**
                         * Auditoria
                         */
                        call_user_func("auditoria", $auditorias, $os);

                        $countQtdeProd++;
                        $sumTot++;

                    }

                    $updateTriagem = "UPDATE tbl_os o SET tipo_atendimento = (SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao = 'Triagem') WHERE fabrica = {$login_fabrica} AND sua_os LIKE '{$os_revenda}'||'-%' AND (SELECT COUNT(*) FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = o.tipo_atendimento AND grupo_atendimento = 'G') > 0;";
                    $resUpdateTriagem = pg_query($con, $updateTriagem);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao duplicar a OS Revenda do tipo (Triagem/Reoperação) #5");
                    }
                }
            }
        }
    }
}

$funcoes_fabrica = array('grava_os_dupla_midea_carrier');

/*function valida_serie_midea_carrier($linha = null) {
    global $campos, $msg_erro, $con, $login_posto, $login_fabrica;

    $produto = $campos['produtos'][$linha]['id'];
    $produto_serie = $campos['produtos'][$linha]['serie'];

    if (strlen($produto_serie) > 0) {
        $sql = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND UPPER(codigo_posto) = UPPER('{$produto_serie}');";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 0) {
            $sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto} AND numero_serie_obrigatorio IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sql = "SELECT * FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$produto_serie}'";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {
                    $msg_erro["campos"][] = "produto_".$linha;
                    throw new Exception("Número de Série inválido");
                }
            } else {
                $sql = "SELECT * FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$produto_serie}'";
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
                            $msg_erro["campos"][] = "produto_".$linha;
                            throw new Exception("Número de Série inválido");
                        }
                    } else {
                        $msg_erro["campos"][] = "produto_".$linha;
                        throw new Exception("Número de Série inválido");
                    }
                }
            }
        }
    }
}*/

function valida_os_revenda_itens_midea_carrier()
{
    global $con, $login_fabrica, $login_admin, $os_revenda, $campos, $msg_erro, $areaAdmin;

    $array_msg_erro = array();
    foreach ($campos['produtos'] as $key => $array_produto) {
        if ($key !== "__modelo__" && !empty($array_produto['id'])) {
            if (empty($array_produto['qtde']) || empty($array_produto['tipo_atendimento'])) {
                $array_msg_erro[$key] = "É necessário informar o tipo de atendimento e uma quantidade para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                    $msg_erro["campos"][] = "produto_".$key;
            } /*else {
                valida_serie_midea_carrier($key);
            }*/
        }
    }

    if (count($array_msg_erro) > 0) {
        throw new Exception(implode("<br />", $array_msg_erro));
    }
}

$valida_os_revenda_itens = "valida_os_revenda_itens_midea_carrier";

/**
 * Não será gravado o número de série na fábrica só serve para buscar
 */
function remove_numero_serie ()
{
    global $campos;

    foreach ($campos['produtos'] as $key => $array_produto) {
        if (!empty($array_produto['serie'])) {
            $campos['produtos'][$key]['serie'] = null;
        }
    }
}

$pre_funcoes_fabrica = array('remove_numero_serie');

/*function auditoria_numero_serie_coringa_midea_carrier($os = null) {
    global $con, $login_fabrica;

    if (empty($os)) {
        throw new Exception("OS não encontrada para validar a auditoria");
    }

    $sql = "
        SELECT op.serie
        FROM tbl_os o
        JOIN tbl_os_produto op ON op.os = o.os
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND op.serie = pf.codigo_posto
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
            VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de número de série', false);
        ";

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD001");
        }
    }
}

$auditorias = array(
    "auditoria_numero_serie_coringa_midea_carrier",
);*/
