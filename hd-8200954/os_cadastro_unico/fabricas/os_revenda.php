<?php

$os_revenda = $_REQUEST["os_revenda"];

include_once __DIR__ . '../../../class/AuditorLog.php';

#Arquivo com as regras padrões do sistema
include_once __DIR__."/regras_os_revenda.php";

#Arquivo com as regras especificas da fábrica
include_once __DIR__."/{$login_fabrica}/regras_os_revenda.php";

include_once __DIR__.'../../../class/ComunicatorMirror.php';
$comunicatorMirror = new ComunicatorMirror();

#Array de erros
$msg_erro = array(
    'msg'    => array(),
    'campos' => array()
);

if (!empty($os_revenda)) {
    if ($login_fabrica <> 178){
        $cond_excluida = " AND orev.excluida IS NOT TRUE ";
    }

    $seleOCE = '';
    $joinOCE = '';

    if (in_array($login_fabrica, [178])) {
        // tbl_os_campo_extra
        $seleOCE = ', oce.campos_adicionais';
        $joinOCE = 'LEFT JOIN tbl_os_campo_extra AS oce ON oce.os_revenda = orev.os_revenda AND oce.fabrica = '.$login_fabrica;
    }

    $sql = "
        SELECT
            orev.os_revenda,
            orev.sua_os,
            TO_CHAR(orev.data_abertura, 'DD/MM/YYYY') AS data_abertura,
            TO_CHAR(orev.digitacao, 'DD/MM/YYYY') AS orev_digitacao,
            orev.qtde_km,
            orev.obs,
            orev.quem_abriu_chamado AS revenda_contato,
            r.revenda,
            orev.consumidor_revenda,
            r.cnpj AS revenda_cnpj,
            r.nome AS revenda_nome,
            r.endereco AS revenda_endereco,
            r.numero AS revenda_numero,
            r.complemento AS revenda_complemento,
            r.bairro AS revenda_bairro,
            r.cep AS revenda_cep,
            r.cidade AS revenda_cidade,
            r.fone AS revenda_fone,
            r.email AS revenda_email,
            orev.consumidor_cpf,
            orev.consumidor_nome,
            orev.consumidor_endereco,
            orev.consumidor_numero,
            orev.consumidor_complemento,
            orev.data_fechamento AS data_fechamento_os_revenda,
            orev.consumidor_bairro,
            orev.consumidor_cep,
            orev.consumidor_cidade,
            orev.cortesia,
            orev.consumidor_estado,
            orev.obs_causa AS observacao_callcenter,
            orev.obs AS observacao_os_revenda,
            orev.consumidor_fone,
            orev.hd_chamado,
            orev.visita_por_km,
            orev.consumidor_email,
            orev.excluida,
            orev.campos_extra,
            ta.tipo_atendimento,
            ta.descricao AS tipo_atendimento_descricao,
            pf.codigo_posto AS posto_codigo,
            p.posto,
            p.nome AS posto_nome
            {$seleOCE}
        FROM tbl_os_revenda orev
        LEFT JOIN tbl_revenda r USING(revenda)
        LEFT JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
        LEFT JOIN tbl_posto_fabrica pf USING(posto,fabrica)
        LEFT JOIN tbl_posto p USING(posto)
        {$joinOCE}
        WHERE orev.fabrica = {$login_fabrica}
        AND orev.os_revenda = {$os_revenda}
        $cond_excluida
    ";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        
        $tipo_os = pg_fetch_result($res, 0, "consumidor_revenda");
        $campos_extra = pg_fetch_result($res, 0, "campos_extra");
        $campos_extra = json_decode($campos_extra, true);
    
        $hd_chamado_id = pg_fetch_result($res, 0, "hd_chamado");
        $consumidor_nome = pg_fetch_result($res, 0, "consumidor_nome");

        if (in_array($tipo_os, array("A", "S", "C", "E", "I", "P")) OR ($login_fabrica == 178 AND !empty($hd_chamado_id) AND !empty($consumidor_nome))){
            $_RESULT = array(
                "os_revenda"                    => pg_fetch_result($res, 0, "os_revenda"),
                "sua_os"                        => pg_fetch_result($res, 0, "sua_os"),
                "data_abertura"                 => pg_fetch_result($res, 0, "data_abertura"),
                "revenda"                       => pg_fetch_result($res, 0, "revenda"),
                "revenda_cnpj"                  => pg_fetch_result($res, 0, "consumidor_cpf"),
                "revenda_nome"                  => pg_fetch_result($res, 0, "consumidor_nome"),
                "revenda_endereco"              => pg_fetch_result($res, 0, "consumidor_endereco"),
                "revenda_numero"                => pg_fetch_result($res, 0, "consumidor_numero"),
                "revenda_complemento"           => pg_fetch_result($res, 0, "consumidor_complemento"),
                "revenda_bairro"                => pg_fetch_result($res, 0, "consumidor_bairro"),
                "revenda_cep"                   => pg_fetch_result($res, 0, "consumidor_cep"),
                "revenda_cidade"                => pg_fetch_result($res, 0, "consumidor_cidade"),
                "consumidor_revenda"            => pg_fetch_result($res, 0, "consumidor_revenda"),
                "revenda_estado"                => pg_fetch_result($res, 0, "consumidor_estado"),
                "revenda_fone"                  => pg_fetch_result($res, 0, "consumidor_fone"),
                "revenda_email"                 => pg_fetch_result($res, 0, "consumidor_email"),
                "revenda_contato"               => pg_fetch_result($res, 0, "revenda_contato"),
                "tipo_atendimento"              => pg_fetch_result($res, 0, "tipo_atendimento"),
                "tipo_atendimento_descricao"    => pg_fetch_result($res, 0, "tipo_atendimento_descricao"),
                "qtde_km"                       => pg_fetch_result($res, 0, "qtde_km"),
                "visita_por_km"                 => pg_fetch_result($res, 0, "visita_por_km"),
                "hd_chamado"                    => pg_fetch_result($res, 0, "hd_chamado"),
                "posto_id"                      => pg_fetch_result($res, 0, "posto"),
                "posto_codigo"                  => pg_fetch_result($res, 0, "posto_codigo"),
                "posto_nome"                    => pg_fetch_result($res, 0, "posto_nome"),
                "obs"                           => pg_fetch_result($res, 0, "obs"),
                "data_fechamento_os_revenda"    => pg_fetch_result($res, 0, "data_fechamento_os_revenda"),
                "revenda_nome_consumidor"       => pg_fetch_result($res, 0, 'revenda_nome'),
                "revenda_cnpj_consumidor"       => pg_fetch_result($res, 0, 'revenda_cnpj'),
                "orev_digitacao"                => pg_fetch_result($res, 0, "orev_digitacao"),
                "observacao_callcenter"         => pg_fetch_result($res, 0, "observacao_callcenter"),
                "observacao_os_revenda"         => pg_fetch_result($res, 0, "observacao_os_revenda"),
                "os_excluida"                   => pg_fetch_result($res, 0, "excluida"), 
                "os_cortesia"                   => pg_fetch_result($res, 0, "cortesia"),
                "revenda_celular"               => $campos_extra["revenda_celular"],
                "inscricao_estadual"            => $campos_extra["inscricao_estadual"]
            );
            
            if (!empty($_RESULT["revenda"])){
                $sql_revenda = "SELECT r.revenda, r.cep, r.nome, tbl_cidade.estado, tbl_cidade.nome AS nome_cidade, r.bairro, r.fone, r.endereco, r.cnpj, r.numero FROM tbl_revenda r LEFT JOIN tbl_cidade USING(cidade) WHERE revenda =".$_RESULT["revenda"];
                $res_revenda = pg_query($con, $sql_revenda);
                
                if (pg_num_rows($res_revenda) > 0){
                    $_RESULT["dados_revenda_consumidor"] = array(
                        "revenda_nome" => pg_fetch_result($res_revenda, 0, 'nome'),
                        "revenda_endereco" => pg_fetch_result($res_revenda, 0, 'endereco'),
                        "revenda_numero" => pg_fetch_result($res_revenda, 0, 'numero'),
                        "revenda_cnpj" => pg_fetch_result($res_revenda, 0, "cnpj"),
                        "revenda_cep" => pg_fetch_result($res_revenda, 0, "cep"),
                        "revenda_bairro" => pg_fetch_result($res_revenda, 0, "bairro"),
                        "revenda_cidade" => pg_fetch_result($res_revenda, 0, "nome_cidade"),
                        "revenda_estado" => pg_fetch_result($res_revenda, 0, "estado"),
                        "revenda_fone" => pg_fetch_result($res_revenda, 0, "fone"),
                    );
                }
            }

        }else{
            $_RESULT = array(
                "os_revenda"                    => pg_fetch_result($res, 0, "os_revenda"),
                "sua_os"                        => pg_fetch_result($res, 0, "sua_os"),
                "data_abertura"                 => pg_fetch_result($res, 0, "data_abertura"),
                "revenda"                       => pg_fetch_result($res, 0, "revenda"),
                "revenda_cnpj"                  => pg_fetch_result($res, 0, "revenda_cnpj"),
                "revenda_nome"                  => pg_fetch_result($res, 0, "revenda_nome"),
                "revenda_endereco"              => pg_fetch_result($res, 0, "revenda_endereco"),
                "revenda_numero"                => pg_fetch_result($res, 0, "revenda_numero"),
                "revenda_complemento"           => pg_fetch_result($res, 0, "revenda_complemento"),
                "revenda_bairro"                => pg_fetch_result($res, 0, "revenda_bairro"),
                "consumidor_revenda"            => pg_fetch_result($res, 0, "consumidor_revenda"),
                "revenda_cep"                   => pg_fetch_result($res, 0, "revenda_cep"),
                "revenda_cidade"                => pg_fetch_result($res, 0, "revenda_cidade"),
                "revenda_fone"                  => pg_fetch_result($res, 0, "revenda_fone"),
                "revenda_email"                 => pg_fetch_result($res, 0, "revenda_email"),
                "revenda_contato"               => pg_fetch_result($res, 0, "revenda_contato"),
                "tipo_atendimento"              => pg_fetch_result($res, 0, "tipo_atendimento"),
                "tipo_atendimento_descricao"    => pg_fetch_result($res, 0, "tipo_atendimento_descricao"),
                "qtde_km"                       => pg_fetch_result($res, 0, "qtde_km"),
                "visita_por_km"                 => pg_fetch_result($res, 0, "visita_por_km"),
                "hd_chamado"                    => pg_fetch_result($res, 0, "hd_chamado"),
                "posto_id"                      => pg_fetch_result($res, 0, "posto"),
                "posto_codigo"                  => pg_fetch_result($res, 0, "posto_codigo"),
                "posto_nome"                    => pg_fetch_result($res, 0, "posto_nome"),
                "obs"                           => pg_fetch_result($res, 0, "obs"),
                "os_excluida"                   => pg_fetch_result($res, 0, "excluida"),
                "orev_digitacao"                => pg_fetch_result($res, 0, "orev_digitacao"),
                "os_cortesia"                   => pg_fetch_result($res, 0, "cortesia")
            );


            if (in_array($login_fabrica, [169,170])) {
                if (!empty($_RESULT["revenda"])){
                    $sql_revenda = "SELECT r.revenda, r.cep, r.nome, tbl_cidade.estado, tbl_cidade.nome AS nome_cidade, r.email as revenda_email, r.bairro, r.fone, r.endereco, r.cnpj, r.numero FROM tbl_revenda r LEFT JOIN tbl_cidade USING(cidade) WHERE revenda =".$_RESULT["revenda"];
                    $res_revenda = pg_query($con, $sql_revenda);
                    
                    if (pg_num_rows($res_revenda) > 0){
                        $_RESULT["dados_revenda_consumidor"] = array(
                            "revenda_nome" => pg_fetch_result($res_revenda, 0, 'nome'),
                            "revenda_endereco" => pg_fetch_result($res_revenda, 0, 'endereco'),
                            "revenda_numero" => pg_fetch_result($res_revenda, 0, 'numero'),
                            "revenda_cnpj" => pg_fetch_result($res_revenda, 0, "cnpj"),
                            "revenda_cep" => pg_fetch_result($res_revenda, 0, "cep"),
                            "revenda_bairro" => pg_fetch_result($res_revenda, 0, "bairro"),
                            "revenda_cidade" => pg_fetch_result($res_revenda, 0, "nome_cidade"),
                            "revenda_estado" => pg_fetch_result($res_revenda, 0, "estado"),
                            "revenda_fone" => pg_fetch_result($res_revenda, 0, "fone"),
                            "revenda_contato" => pg_fetch_result($res, 0, "revenda_contato"),
                            "revenda_email" => pg_fetch_result($res_revenda, 0, "revenda_email"),
                        );
                    }
                }
            }
            // Campos Adicionais gravados em tbl_os_campo_extra.campos_adicionais
            if (in_array($login_fabrica, [178])) {
                $revenda_complementoNew         = pg_fetch_result($res, 0, "revenda_complemento"); 
                $campos_adicionais              = pg_fetch_result($res, 0, "campos_adicionais");
                $campos_adicionais              = json_decode($campos_adicionais,true);
                $_RESULT['revenda_complemento'] = (empty($revenda_complementoNew)) ? $campos_adicionais["inscricao_estadual"] : $revenda_complementoNew;

                $_RESULT["revenda_celular"] = $campos_extra["revenda_celular"];
                $_RESULT["inscricao_estadual"] = $campos_extra["inscricao_estadual"];

            }

            if (!empty($_RESULT['revenda_cidade'])){
            	$sql_revenda_cidade = "SELECT tbl_cidade.estado, tbl_cidade.nome FROM tbl_cidade WHERE cidade =".$_RESULT["revenda_cidade"];
            	$res_revenda_cidade = pg_query($con, $sql_revenda_cidade);
            	
            	$_RESULT["revenda_cidade"] = pg_fetch_result($res_revenda_cidade, 0, "nome");
            	$_RESULT["revenda_estado"] = pg_fetch_result($res_revenda_cidade, 0, "estado");
            }
        }
        
        $sqlAgendamento = "
            SELECT 
                tecnico, 
                os_revenda, 
                TO_CHAR(data_agendamento, 'DD/MM/YYYY') AS data_agendamento, 
                confirmado AS data_visita_realizada, 
                periodo 
            FROM tbl_tecnico_agenda 
            WHERE fabrica = $login_fabrica 
            AND os_revenda = $os_revenda
            ORDER BY tecnico_agenda DESC LIMIT 1";
        $resAgendamento = pg_query($con, $sqlAgendamento);

        if (pg_num_rows($resAgendamento) > 0){
            $tecnico                = pg_fetch_result($resAgendamento, 0, "tecnico");
            $data_agendamento       = pg_fetch_result($resAgendamento, 0, "data_agendamento");
            $data_visita_realizada  = pg_fetch_result($resAgendamento, 0, "data_visita_realizada");
            $periodo                = pg_fetch_result($resAgendamento, 0, "periodo");

            $_RESULT["agendamento"] = array(
                "os_revenda" => $os_revenda,
                "tecnico" => $tecnico,
                "data_agendamento" => $data_agendamento,
                "data_visita_realizada" => $data_visita_realizada,
                "periodo" => $periodo
            );
        }

        $sqlItens = "
            SELECT 
                DISTINCT ON (COALESCE(o.os, ori.os_revenda_item))
                o.os,
                TO_CHAR(o.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
                o.sua_os,
                o.qtde_km,
                o.excluida AS os_excluida,
                ori.os_revenda_item,
                ori.qtde,
                COALESCE(op.serie, CASE WHEN o.os IS NULL THEN ori.serie ELSE '' END) as serie,
                ori.defeito_reclamado,
                oce.marca,
                oce.campos_adicionais,
                m.nome AS marca_nome,
                ta.tipo_atendimento,
                ta.descricao AS tipo_atendimento_descricao,
                ta.tipo_atendimento AS tipo_atendimento_os,
                taos.descricao AS tipo_atendimento_descricao_os,
                ori.nota_fiscal,
                ori.parametros_adicionais AS parametros_adicionais_os_item,
                COALESCE(o.nota_fiscal, CASE WHEN o.os IS NULL THEN ori.nota_fiscal ELSE '' END) AS nota_fiscal_os,
                TO_CHAR(ori.data_nf, 'DD/MM/YYYY') AS data_nf,
                COALESCE(p.produto, prod_revenda.produto) as produto,
                COALESCE(p.referencia, prod_revenda.referencia) AS produto_referencia,
                COALESCE(p.parametros_adicionais, prod_revenda.parametros_adicionais) AS produto_parametros_adicionais,
                COALESCE(p.descricao, prod_revenda.descricao) AS produto_descricao ";
        if ($login_fabrica == 178){
            $sqlItens .= "
                FROM tbl_os_revenda osr
                JOIN tbl_os_revenda_item ori ON ori.os_revenda = osr.os_revenda
                LEFT JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = ori.tipo_atendimento AND ta.fabrica = $login_fabrica
                LEFT JOIN tbl_os_campo_extra oce ON oce.os_revenda = osr.os_revenda AND oce.os_revenda_item = ori.os_revenda_item
                    AND oce.fabrica = $login_fabrica
                LEFT JOIN tbl_os o ON o.os = oce.os AND o.fabrica = $login_fabrica
                LEFT JOIN tbl_produto p ON p.produto = o.produto AND p.fabrica_i = $login_fabrica
                LEFT JOIN tbl_produto prod_revenda ON ori.produto = prod_revenda.produto
                LEFT JOIN tbl_marca m ON m.marca = oce.marca AND m.fabrica = $login_fabrica
                LEFT JOIN tbl_os_produto op ON op.os = o.os
                LEFT JOIN tbl_tipo_atendimento taos ON taos.tipo_atendimento = o.tipo_atendimento AND taos.fabrica = $login_fabrica
                WHERE ori.os_revenda = $os_revenda
                AND osr.fabrica = $login_fabrica";
        }else{
            $sqlItens .= "
                FROM tbl_os_revenda_item ori
                LEFT JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = ori.tipo_atendimento AND ta.fabrica = $login_fabrica
                LEFT JOIN tbl_os o ON o.sua_os LIKE '{$os_revenda}'||'-%' AND o.fabrica = $login_fabrica 
                LEFT JOIN tbl_produto p ON p.produto = o.produto AND p.fabrica_i = $login_fabrica
                LEFT JOIN tbl_produto prod_revenda ON ori.produto = prod_revenda.produto
                LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = $login_fabrica
                AND oce.os_revenda_item = ori.os_revenda_item
                LEFT JOIN tbl_marca m ON m.marca = oce.marca AND m.fabrica = $login_fabrica
                LEFT JOIN tbl_os_produto op ON op.os = o.os
                LEFT JOIN tbl_tipo_atendimento taos ON taos.tipo_atendimento = o.tipo_atendimento AND taos.fabrica = $login_fabrica
                WHERE ori.os_revenda = {$os_revenda}";
        }
        $resItens = pg_query($con,$sqlItens);
        $countItens = pg_num_rows($resItens);

        if ($countItens > 0) {
            for ($i = 0; $i < $countItens; $i++) {
            // Campos Adicionais gravados em tbl_os_campo_extra.campos_adicionais
            if (in_array($login_fabrica, [178])) {
                $campos_adicionais = pg_fetch_result($resItens, $i, "campos_adicionais");
                $campos_adicionais = json_decode($campos_adicionais,true);
                $_RESULT["consumidor"]["inscricao_estadual"] = $campos_adicionais["inscricao_estadual"];
            }

			if(in_array($login_fabrica, [178]) AND empty(pg_fetch_result($resItens, $i, "sua_os")) ) continue;

                $nota_fiscal = trim(pg_fetch_result($resItens, $i, "nota_fiscal"));
                $os_revenda_item_id = pg_fetch_result($resItens, $i, "os_revenda_item");

                if (strlen($nota_fiscal) > 0) {
                    $_RESULT['notas_fiscais_adicionadas'][] = $nota_fiscal;
                }

                $_RESULT["produtos"][$os_revenda_item_id] = array(
                    "produto_parametros_adicionais" => pg_fetch_result($resItens, $i, "produto_parametros_adicionais"),
                    "qtde"                          => pg_fetch_result($resItens, $i, "qtde"),
                    "serie"                         => pg_fetch_result($resItens, $i, "serie"),
                    "nota_fiscal"                   => $nota_fiscal,
                    "data_nf"                       => pg_fetch_result($resItens, $i, "data_nf"),
                    "id"                            => pg_fetch_result($resItens, $i, "produto"),
                    "referencia"                    => pg_fetch_result($resItens, $i, "produto_referencia"),
                    "os_revenda_item"               => pg_fetch_result($resItens, $i, "os_revenda_item"),
                    "descricao"                     => pg_fetch_result($resItens, $i, "produto_descricao"),
                    "tipo_atendimento"              => pg_fetch_result($resItens, $i, "tipo_atendimento"),
                    "marca"                         => pg_fetch_result($resItens, $i, "marca"),
                    "defeito_reclamado"             => pg_fetch_result($resItens, $i, "defeito_reclamado"),
                    "parametros_adicionais_os_item" => pg_fetch_result($resItens, $i, "parametros_adicionais_os_item")
                );

                $_RESULT["produtos_print"][$i] = array(
                    "os"                            => pg_fetch_result($resItens, $i, "os"),
                    "sua_os"                        => pg_fetch_result($resItens, $i, "sua_os"),
                    "qtde_km"                       => pg_fetch_result($resItens, $i, "qtde_km"),
                    "os_revenda_item"               => pg_fetch_result($resItens, $i, "os_revenda_item"),
                    "qtde"                          => pg_fetch_result($resItens, $i, "qtde"),
                    "serie"                         => pg_fetch_result($resItens, $i, "serie"),
                    "tipo_atendimento"              => pg_fetch_result($resItens, $i, "tipo_atendimento"),
                    "tipo_atendimento_descricao"    => pg_fetch_result($resItens, $i, "tipo_atendimento_descricao"),
                    "tipo_atendimento_os"           => pg_fetch_result($resItens, $i, "tipo_atendimento_os"),
                    "tipo_atendimento_descricao_os" => pg_fetch_result($resItens, $i, "tipo_atendimento_descricao_os"),
                    "nota_fiscal"                   => pg_fetch_result($resItens, $i, "nota_fiscal_os"),
                    "data_nf"                       => pg_fetch_result($resItens, $i, "data_nf"),
                    "id"                            => pg_fetch_result($resItens, $i, "produto"),
                    "referencia"                    => pg_fetch_result($resItens, $i, "produto_referencia"),
                    "descricao"                     => pg_fetch_result($resItens, $i, "produto_descricao"),
                    "marca_nome"                    => pg_fetch_result($resItens, $i, "marca_nome"),
                    "data_fechamento"               => pg_fetch_result($resItens, $i, "data_fechamento"),
                    "os_excluida"                   => pg_fetch_result($resItens, $i, "os_excluida")
                );
            }
            $_RESULT['notas_fiscais_adicionadas'] = implode(",", $_RESULT['notas_fiscais_adicionadas']);
        }
    } else {
        $msg_erro['msg'][] = "OS de Revenda não encontrada";
        $erro_carrega_os_revenda = true;
    }
}else if ($login_fabrica == 183){
    if ($login_tipo_posto_codigo == "Rev"){
        $sql = "
            SELECT
                tbl_posto.nome,
                tbl_posto.cnpj,
                tbl_posto.posto,
                tbl_posto.cep,
                tbl_posto.estado,
                tbl_posto.cidade,
                tbl_posto.bairro,
                tbl_posto.endereco,
                tbl_posto.numero,
                tbl_posto_fabrica.contato_fone_comercial AS fone,
                tbl_posto_fabrica.contato_email AS email,
                tbl_posto_fabrica.contato_complemento AS complemento
            FROM tbl_posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_posto.posto = {$login_posto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $_RESULT = array(
                "revenda"               => pg_fetch_result($res, 0, "posto"),
                "revenda_cnpj"          => pg_fetch_result($res, 0, "cnpj"),
                "revenda_nome"          => pg_fetch_result($res, 0, "nome"),
                "revenda_endereco"      => pg_fetch_result($res, 0, "endereco"),
                "revenda_numero"        => pg_fetch_result($res, 0, "numero"),
                "revenda_bairro"        => pg_fetch_result($res, 0, "bairro"),
                "revenda_cep"           => pg_fetch_result($res, 0, "cep"),
                "revenda_cidade"        => pg_fetch_result($res, 0, "cidade"),
                "revenda_complemento"   => pg_fetch_result($res, 0, "complemento"),
                "revenda_fone"          => pg_fetch_result($res, 0, "fone"),
                "revenda_email"         => pg_fetch_result($res, 0, "email"),
                "posto_id"              => pg_fetch_result($res, 0, "posto")
            );
      
            if (!empty($_RESULT['revenda_cidade'])){
                $sql_revenda_cidade = "
                    SELECT DISTINCT 
                        UPPER(fn_retira_especiais(TRIM(nome))) AS nome, 
                        UPPER(fn_retira_especiais(TRIM(estado))) AS estado 
                    FROM tbl_cidade
                    WHERE UPPER(fn_retira_especiais(TRIM(nome))) = UPPER(fn_retira_especiais(TRIM('{$_RESULT['revenda_cidade']}')))";
                $res_revenda_cidade = pg_query($con, $sql_revenda_cidade);
                
                $_RESULT["revenda_cidade"] = pg_fetch_result($res_revenda_cidade, 0, "nome");
                $_RESULT["revenda_estado"] = pg_fetch_result($res_revenda_cidade, 0, "estado");
            }
        }else{
            $msg_erro['msg'][] = "Revenda não encontrada";
            $erro_carrega_os_revenda = true;
        }
    }
}

if ($_REQUEST['gravar'] == "Gravar") {
    
    $campos = array(
        'os_revenda'                => $_REQUEST['os_revenda'],
        'consumidor_revenda'        => $_REQUEST['consumidor_revenda'],
        'tipo_atendimento'          => $_REQUEST['tipo_atendimento'],
        'qtde_km'                   => $_REQUEST['qtde_km'],
        'qtde_km_hidden'            => $_REQUEST['qtde_km_hidden'],
        'fabrica_id'                => $_REQUEST['fabrica_id'],
        'posto_id'                  => $_REQUEST['posto_id'],
        'posto_codigo'              => $_REQUEST['posto_codigo'],
        'posto_nome'                => $_REQUEST['posto_nome'],
        'sua_os'                    => $_REQUEST['sua_os'],
        'data_abertura'             => $_REQUEST['data_abertura'],
        'notas_fiscais_adicionadas' => $_REQUEST['notas_fiscais_adicionadas'],
        'revenda'                   => $_REQUEST['revenda'],
        'revenda_nome_consumidor'   => $_REQUEST['revenda_nome_consumidor'],
        'revenda_cnpj_consumidor'   => $_REQUEST['revenda_cnpj_consumidor'],
        'revenda_nome'              => $_REQUEST['revenda_nome'],
        'revenda_cnpj'              => $_REQUEST['revenda_cnpj'],
        'revenda_cep'               => (!empty($_REQUEST['revenda_cep'])) ? $_REQUEST['revenda_cep'] : $_REQUEST['revenda_cep_revenda'],
        'revenda_estado'            => $_REQUEST['revenda_estado'],
        'revenda_cidade'            => $_REQUEST['revenda_cidade'],
        'revenda_bairro'            => $_REQUEST['revenda_bairro'],
        'revenda_endereco'          => $_REQUEST['revenda_endereco'],
        'revenda_numero'            => $_REQUEST['revenda_numero'],
        'revenda_complemento'       => $_REQUEST['revenda_complemento'],
        'revenda_fone'              => $_REQUEST['revenda_fone'],
        "revenda_celular"           => $_REQUEST['revenda_celular'],
        'revenda_email'             => $_REQUEST['revenda_email'],
        'revenda_contato'           => $_REQUEST['revenda_contato'],
        "solicitar_deslocamento"    => $_REQUEST['solicitar_deslocamento'],
        "data_agendamento"          => $_REQUEST['data_agendamento'],
        "tecnico"                   => $_REQUEST['tecnico'],
        "periodo_visita"            => $_REQUEST["periodo_visita"],
        "data_visita_realizada"     => $_REQUEST['data_visita_realizada'],
        'produtos'                  => $_REQUEST['produtos'],
        'obs'                       => $_REQUEST['obs'],
        'anexo'                     => $_REQUEST['anexo'],
        'anexo_s3'                  => $_REQUEST['anexo_s3'],
        "anexo_chave"               => $_REQUEST['anexo_chave'],
        "anexo_notas"               => $_REQUEST['anexo_notas'],
        "notas_revenda"             => $_REQUEST['notas_revenda'],
        "os_cortesia"               => $_REQUEST['os_cortesia'],
        "tipo_frete"                => $_REQUEST['tipo_frete'],
        "inscricao_estadual"        => $_REQUEST['inscricao_estadual']
    );
    
    if (isset($antes_valida_campos) && function_exists($antes_valida_campos)) {
        call_user_func($antes_valida_campos);
    }

    /**
     * Validação os campos do formulário
     */
    valida_campos();

    /**
    * Validação de garantia
    */
    if (strlen(trim($valida_garantia)) > 0) {
        $valida_garantia();
    }

    if (!empty($valida_anexo) && function_exists($valida_anexo)) {
        $valida_anexo();
    }
    
    if (!empty($valida_anexo_boxuploader) && function_exists($valida_anexo_boxuploader)) {
        $valida_anexo_boxuploader();
    }

    if (!count($msg_erro["msg"])) {
        try {

            pg_query($con, "BEGIN");

            if (!strlen($os_revenda)) {
                $gravando = true;
            }

            /**
             * Executa funções especificas de cada fabrica
             */

            if (isset($pre_funcoes_fabrica) && !empty($pre_funcoes_fabrica) && is_array($pre_funcoes_fabrica)) {
                foreach ($pre_funcoes_fabrica as $funcao) {
                    if (function_exists($funcao)) {
                        call_user_func($funcao);
                    }
                }
            }

    	    /**
    	     * Verifica sua OS revenda
    	     */
    	    verifica_sua_os();

    	    /**
    	     * Verifica Revenda Informada
    	     */
    	    verifica_revenda();

            /**
             * Grava a Ordem de Serviço Revenda
             */
            grava_os_revenda();

            /*
             * Função para validar regras de adição de produtos por fabricante
             */
            if (function_exists($valida_os_revenda_itens)) {
                call_user_func($valida_os_revenda_itens);
            }

            grava_os_revenda_item();


            /**
             * Move os anexos do bucket temporario para o bucket da Ordem de Serviço Revenda
             */
            if (!empty($grava_anexo) && function_exists($grava_anexo)) {
                $grava_anexo();
            }

            /**
             * Grava a Ordem de Serviço Explodida
             */
            grava_os();
            
            /**
             * Executa funções específicas de cada fábrica
             */
            if (isset($funcoes_fabrica) && !empty($funcoes_fabrica) && is_array($funcoes_fabrica)) {
                foreach ($funcoes_fabrica as $funcao) {
                    if (function_exists($funcao)) {
                        call_user_func($funcao);
                    }
                }
            }

            pg_query($con, "COMMIT;");

            if ($gravando === true) {
                if (isset($funcoes_fabrica_email) && !empty($funcoes_fabrica_email) && is_array($funcoes_fabrica_email)) {
                    foreach ($funcoes_fabrica_email as $funcao) {
                        if (function_exists($funcao)) {
                            call_user_func($funcao);
                        }
                    }
                }
            }

            header("Location: os_revenda_press.php?os_revenda={$os_revenda}");

        } catch (Exception $e) {
            pg_query($con, "ROLLBACK;");
            $msg_erro["msg"][] = $e->getMessage();

            if ($gravando === true) {
                unset($os_revenda);
                unset($os);
            }
        }
    }
}

/**
 *  Método de gravação da OS de revenda
 */
function grava_os_revenda()
{
    global $con, $login_fabrica, $login_admin, $os_revenda, $campos, $areaAdmin, $auditorias_os_revenda;
    
    /**
     * Grava tbl_os_revenda
     */
    
    if (function_exists("grava_os_revenda_fabrica")) {
        /**
         * A função grava_os_revenda_fabrica deve ficar dentro do arquivo de regras fábrica
         * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
         */
        $tbl_os_revenda = grava_os_revenda_fabrica();

        if (!empty($os_revenda) && is_array($tbl_os_revenda)) {
            $tbl_os_revenda_update = array();

            foreach ($tbl_os_revenda as $key => $value) {
                $tbl_os_revenda_update[] = "{$key} = {$value}";
            }
        }
    }

    $login_admin = (empty($login_admin)) ? "null" : $login_admin;

    $campos['obs'] = pg_escape_string($campos['obs']);
    
    if (empty($os_revenda)) {
        $sql = "
            INSERT INTO tbl_os_revenda (
                fabrica,
                sua_os,
                data_abertura,
                revenda,
                tipo_atendimento,
                qtde_km,
                obs,
                digitacao,
                posto,
                admin,
                contrato,
                tipo_os,
                valor_adicional_justificativa
                ".((isset($column_tbl_os_revenda)) ? ", ".implode(", ", array_keys($column_tbl_os_revenda)) : "")."
                ".((isset($tbl_os_revenda)) ? ", ".implode(", ", array_keys($tbl_os_revenda)) : "")."
            ) VALUES (
                {$login_fabrica},
                ".((!empty($campos['sua_os'])) ? "'".$campos['sua_os']."'" : "null").",
                '".formata_data($campos['data_abertura'])."',
                ".((!empty($campos['revenda'])) ? "'".$campos['revenda']."'" : "null").",
                ".((!empty($campos['tipo_atendimento'])) ? "'".$campos['tipo_atendimento']."'" : "null").",
                ".((!empty($campos['qtde_km'])) ? "'".number_format($campos['qtde_km'], 2, '.', '')."'" : "null").",
                ".((!empty($campos['obs'])) ? "'".$campos['obs']."'" : "null").",
                current_timestamp,
                {$campos['posto_id']},
                ".((!empty($login_admin)) ? $login_admin : "null").",
                ".((!empty($campos['contrato'])) ? "'".$campos['contrato']."'" : "null").",
                ".((!empty($campos['tipo_os'])) ? "'".$campos['tipo_os']."'" : "null").",
                ".((!empty($campos['valor_adicional_justificativa'])) ? "'".$campos['valor_adicional_justificativa']."'" : "null")."
                ".((isset($column_tbl_os_revenda)) ? ", ".implode(", ", $column_tbl_os_revenda) : "")."
                ".((isset($tbl_os_revenda)) ? ", ".implode(", ", $tbl_os_revenda) : "")."
            ) RETURNING os_revenda;
        ";
    } else {
        $sql = "
            UPDATE tbl_os_revenda SET
                sua_os = ".((!empty($campos['sua_os'])) ? "'".$campos['sua_os']."'" : "null").",
                data_abertura = '".formata_data($campos['data_abertura'])."',
                revenda = ".((!empty($campos['revenda'])) ? $campos['revenda'] : "null").",
                obs = ".((!empty($campos['obs'])) ? "'".$campos['obs']."'" : "null").",
                posto = {$campos['posto_id']},
                admin = ".((!empty($login_admin)) ? $login_admin : "null").",
                tipo_atendimento = ".((!empty($campos['tipo_atendimento'])) ? "'".$campos['tipo_atendimento']."'" : "null").",
                qtde_km = ".((!empty($campos['qtde_km'])) ? "'".number_format($campos['qtde_km'], 2, '.', '')."'" : "null").",
                contrato = ".((!empty($campos['contrato'])) ? "'".$campos['contrato']."'" : "null").",
                tipo_os = ".((!empty($campos['tipo_os'])) ? "'".$campos['tipo_os']."'" : "null").",
                valor_adicional_justificativa = ".((!empty($campos['valor_adicional_justificativa'])) ? "'".$campos['valor_adicional_justificativa']."'" : "null")."
                ".((isset($tbl_os_revenda_update)) ? ", ".implode(", ", $tbl_os_revenda_update) : "")."
            WHERE os_revenda = {$os_revenda};
        ";
        // Essa função fica dentro do arquivo regras_os_revenda da fabrica
        if (function_exists("update_os_explodida")) {
            update_os_explodida();
        }
    }
    $res = pg_query($con, $sql);

    /**
     * Auditoria OS Revenda
     */

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao gravar OS Revenda #1");
    } else if (empty($os_revenda)) {
        $os_revenda = pg_fetch_result($res, 0, 0);
        call_user_func("auditoria_os_revenda", $auditorias_os_revenda, $os_revenda);
    } else if ($login_fabrica == 178 AND !empty($os_revenda)) {
        call_user_func("auditoria_os_revenda", $auditorias_os_revenda, $os_revenda);
    }
}

/**
 * Método para gravação da OS derivada da OS de revenda
 */
function grava_os_revenda_item()
{
    global $con, $login_fabrica, $login_admin, $os_revenda, $campos, $msg_erro, $areaAdmin, $_REQUEST;

    if (is_array($campos['produtos'])) {
        $inseriuItemNovo = false;

        foreach ($campos['produtos'] as $key => $array_produto) {
            $parametros_adicionais_os_item = array();
            if (empty($array_produto['os_revenda_item']) && $key !== "__modelo__" && !empty($array_produto['referencia']) && empty($array_produto['id'])) {
                $sql = "
                    SELECT
                        referencia,
                        produto,
                        descricao
                    FROM tbl_produto
                    WHERE fabrica_i = {$login_fabrica}
                    AND referencia = '{$array_produto['referencia']}';
                ";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $_REQUEST['produtos'][$key]['id'] = pg_fetch_result($res, 0, "produto");
                    $_REQUEST['produtos'][$key]['referencia'] = pg_fetch_result($res, 0, "referencia");
                    $_REQUEST['produtos'][$key]['descricao'] = pg_fetch_result($res, 0, "descricao");
                    $campos['produtos'][$key]['id'] = $_REQUEST['produtos'][$key]['id'];
                    $campos['produtos'][$key]['referencia'] = $_REQUEST['produtos'][$key]['referencia'];
                    $campos['produtos'][$key]['descricao'] = $_REQUEST['produtos'][$key]['descricao'];
                    $array_produto['id'] = $campos['produtos'][$key]['id'];
                } else {
                    $msg_erro["campos"][] = "produto_".$key;
                    throw new Exception("Produto não encontrado");
                }
            }

            if (empty($array_produto['os_revenda_item']) && $key !== "__modelo__") {

                if (!empty($campos['tipo_atendimento'])){
                    $ins_tipo_atendimento = ((!empty($campos['tipo_atendimento'])) ? $campos['tipo_atendimento'] : "null");
                }else if (!empty($array_produto['tipo_atendimento'])){
                    $ins_tipo_atendimento = ((!empty($array_produto['tipo_atendimento'])) ? $array_produto['tipo_atendimento'] : "null");
                }else{
                    $ins_tipo_atendimento = "null";
                }
                
                if (!empty($array_produto["instalacao_publica"])){
                    $parametros_adicionais_os_item["instalacao_publica"] = $array_produto["instalacao_publica"];
                }    
                
                if (!empty($array_produto["defeito_constatado"])){
                    $parametros_adicionais_os_item["defeito_constatado"] = $array_produto["defeito_constatado"];
                }
                
                if (!empty($array_produto["defeito_constatado_grupo"])){
                    $parametros_adicionais_os_item["defeito_constatado_grupo"] = $array_produto["defeito_constatado_grupo"];
                }

                if (!empty($array_produto['info_pecas'])){
                    $parametros_adicionais_os_item["info_pecas"] = $array_produto["info_pecas"];
                }

                if (!empty($parametros_adicionais_os_item) AND is_array($parametros_adicionais_os_item)){
                    $insert_parametros_adicionais = json_encode($parametros_adicionais_os_item);
                }
                $inseriuItemNovo = true;

                if (!empty($array_produto['id'])){
                    $sql = "
                        INSERT INTO tbl_os_revenda_item (
                            os_revenda,
                            produto,
                            serie,
                            marca,
                            defeito_reclamado,
                            nota_fiscal,
                            data_nf,
                            tipo_atendimento,
                            qtde,
                            parametros_adicionais
                        ) VALUES (
                            {$os_revenda},
                            {$array_produto['id']},
                            ".((!empty($array_produto['serie'])) ? "'".$array_produto['serie']."'" : "null").",
                            ".((!empty($array_produto['marca'])) ? "'".$array_produto['marca']."'" : "null").",
                            ".((!empty($array_produto['defeito_reclamado'])) ? $array_produto['defeito_reclamado'] : "null").",
                            ".((!empty($array_produto['nota_fiscal']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".$array_produto['nota_fiscal']."'" : "null").",
                            ".((!empty($array_produto['data_nf']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".formata_data($array_produto['data_nf'])."'" : "null").",
                            {$ins_tipo_atendimento},
                            {$array_produto['qtde']},
                            ".((!empty($insert_parametros_adicionais)) ? "'".$insert_parametros_adicionais."'" : "null")."
                        ) RETURNING os_revenda_item;
                    ";
                    $res = pg_query($con, $sql);
                }else if ($login_fabrica == 178 AND empty($array_produto['id']) AND !empty($array_produto['qtde'])){
                    $sql = "
                        INSERT INTO tbl_os_revenda_item (
                            os_revenda,
                            produto,
                            serie,
                            marca,
                            defeito_reclamado,
                            nota_fiscal,
                            data_nf,
                            tipo_atendimento,
                            qtde,
                            parametros_adicionais
                        ) VALUES (
                            {$os_revenda},
                            NULL,
                            ".((!empty($array_produto['serie'])) ? "'".$array_produto['serie']."'" : "null").",
                            ".((!empty($array_produto['marca'])) ? $array_produto['marca'] : "null").",
                            ".((!empty($array_produto['defeito_reclamado'])) ? $array_produto['defeito_reclamado'] : "null").",
                            ".((!empty($array_produto['nota_fiscal']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".$array_produto['nota_fiscal']."'" : "null").",
                            ".((!empty($array_produto['data_nf']) && $array_produto['nota_fiscal'] != 'semNota') ? "'".formata_data($array_produto['data_nf'])."'" : "null").",
                            {$ins_tipo_atendimento},
                            {$array_produto['qtde']},
                            ".((!empty($insert_parametros_adicionais)) ? "'".$insert_parametros_adicionais."'" : "null")."
                        ) RETURNING os_revenda_item;
                    ";
                    $res = pg_query($con, $sql);
                }
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["campos"][] = "produto_".$key;
                    throw new Exception("Erro ao gravar a OS Revenda #3");
                }
                $campos['produtos'][$key]['os_revenda_item'] = pg_fetch_result($res, 0, "os_revenda_item");
            }
        }
    }

    $sqlOsRevendaItem = "SELECT os_revenda_item FROM tbl_os_revenda_item WHERE os_revenda = $os_revenda";
    $resOsRevendaItem = pg_query($con, $sqlOsRevendaItem);
    if (pg_num_rows($resOsRevendaItem) > 0){
        $inseriuItemNovo = true;
    }
    
    if ($inseriuItemNovo === false) {
        $msg_erro["campos"][] = "produto_0";
        throw new Exception("É necessário adicionar pelo menos um produto para a gravação da OS revenda");
    }
}

function auditoria_os_revenda($auditorias_os_revenda, $os_revenda) {
    foreach ($auditorias_os_revenda as $auditoria_os_revenda) {
        call_user_func($auditoria_os_revenda, $os_revenda);
    }
}

function auditoria($auditorias,$os,$array_produto) {
    foreach ($auditorias as $auditoria) {
        try {
            call_user_func($auditoria,$os,$array_produto);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
