<?php
include __DIR__.'/../../dbconfig.php';
include __DIR__.'/../../includes/dbconnect-inc.php';

include_once __DIR__.'/../../class/ComunicatorMirror.php';

try {
    $envio_manual_cadence = false;
    $os_numero_cadence = "";

    $ComunicatorMirror = new ComunicatorMirror;
    
    $condManual= "";

    if ($argv[1] == 'envio_manual_cadence' || $argv[1] == 'envio_manual_cadence_callcenter') {
        $condManual = "AND fabrica = 35";
    }

    $sqlFabricas = "
        SELECT fabrica, parametros_adicionais
        FROM tbl_fabrica
        WHERE ativo_fabrica IS TRUE
        {$condManual}
        AND parametros_adicionais LIKE '%\"pesquisaSatisfacao\"%'
    ";
    
    $resFabricas = pg_query($con, $sqlFabricas);

    if (pg_num_rows($resFabricas) > 0) {
        while ($fabrica = pg_fetch_object($resFabricas)) {

            if (in_array($fabrica->fabrica, [35,157])) {
                include_once __DIR__."../../../class/sms/sms.class.php";
                $sms = new SMS($fabrica->fabrica);
            }

            if ($argv[1] == 'envio_manual_cadence' || $argv[1] == 'envio_manual_cadence_callcenter') {
                $envio_manual_cadence = true;
        
                if ($argv[1] == 'envio_manual_cadence') {
                    $os_numero_cadence    = $argv[2];
                    $categoriaManual = "'os_email'";
                }

                if ($argv[1] == 'envio_manual_cadence_callcenter') {
                    $callcenter_numero_cadence    = $argv[2];
                    $categoriaManual = "'callcenter_email'";
                }
            } 

            $fabrica->parametros_adicionais = json_decode($fabrica->parametros_adicionais, true);
            
            if (!isset($fabrica->parametros_adicionais['externalId'])) {
                $ComunicatorAccount = 'noreply@tc';
            } else {
                $ComunicatorAccount = $fabrica->parametros_adicionais['externalId'];
            }

            $extraCondition = "";

            if ($argv[1] == "os") {

                $extraCondition = " p.categoria = 'os' ";

            } elseif ($argv[1] == "posto_autorizado") {

                $extraCondition = " p.categoria = 'posto_autorizado'";

            } elseif ($argv[1] == "os_email") {

                $extraCondition = " p.categoria = 'os_email'";

            } else if ($argv[1] == "os_sms") {

                $extraCondition = " p.categoria = 'os_sms' ";

            } else {

                if (!$envio_manual_cadence) {
                    
                    throw new \Exception("É necessário a passagem de um argumento");
                }
            }
        
            if (in_array($fabrica->fabrica, [35,157])) {

                $categorias = "'os_email','callcenter_email', 'os_sms'";
                
                if ($envio_manual_cadence) {

                    $categorias = $categoriaManual;
                }

                   $condCategoria = " 
                        AND (pf.ativo IS TRUE OR p.categoria = 'os_sms')
                        AND (
                            p.categoria in($categorias)
                            OR (
                                p.categoria = 'posto_autorizado'
                                AND (
                                    (
                                            p.repeticao_automatica IS FALSE
                                            AND (p.data_inicial + CAST(p.periodo_envio||' days' AS Interval)) = CURRENT_DATE
                                    )
                                    OR (
                                            p.repeticao_automatica IS TRUE
                                            AND p.data_inicial = CURRENT_DATE
                                    )
                                )
                                AND (
                                    p.data_final IS NULL
                                    OR
                                    p.data_final >= CURRENT_DATE
                                )
                            )
                        )";

                    $joinFormulario = "LEFT JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa";

            } else {

                $condCategoria = " 
                            AND pf.ativo IS TRUE
                            AND (p.categoria = 'os' 
                                OR (p.categoria = 'posto_autorizado'
                                    AND (
                                        (   p.repeticao_automatica IS FALSE
                                            AND (p.data_inicial + CAST(p.periodo_envio||' days' AS Interval)) = CURRENT_DATE
                                        )
                                    OR (
                                        p.repeticao_automatica IS TRUE
                                        AND p.data_inicial = CURRENT_DATE
                                    )
                                )
                                AND (
                                    p.data_final IS NULL
                                    OR
                                    p.data_final >= CURRENT_DATE
                                )
                            )
                        )";
                
                $joinFormulario = "INNER JOIN tbl_pesquisa_formulario pf ON pf.pesquisa = p.pesquisa";

            }

            //AND ({$extraCondition})

            $sqlPesquisas = "
                SELECT 
                    p.pesquisa,
                    pf.pesquisa_formulario,
                    p.categoria, 
                    p.periodo_envio, 
                    p.descricao AS titulo, 
                    p.texto_ajuda AS texto_email,
                    p.repeticao_automatica
                FROM tbl_pesquisa p
                {$joinFormulario}
                WHERE p.fabrica = {$fabrica->fabrica}
                AND p.ativo IS TRUE
                {$condCategoria}
            ";
            $resPesquisas = pg_query($con, $sqlPesquisas);
            
            if (pg_num_rows($resPesquisas) > 0) {

                while ($pesquisa = pg_fetch_object($resPesquisas)) {

                    if (in_array($fabrica->fabrica, [35,157])) {
                        
                        if ($envio_manual_cadence && $pesquisa->categoria == "os_sms") {
                            continue;
                        }

                        switch ($pesquisa->categoria) {
                            case 'callcenter_email':

                                $data_pesquisa = date('Y-m-d', strtotime("-{$pesquisa->periodo_envio} days"));
                                
                                $condManual = "";
                                $distinct   = "";
                                $condData   = " AND (hdi.data BETWEEN '{$data_pesquisa} 00:00:00' AND '{$data_pesquisa} 23:59:59')";
                                $orderBy = " ORDER BY hdi.data DESC";

                                if ($envio_manual_cadence) {
                                    $condManual = "AND hd.hd_chamado = {$callcenter_numero_cadence}";
                                    $condData = "";
                                    $orderBy  = "";
                                    $distinct = "DISTINCT";
                                }

                                $sqlHD = "
                                        SELECT {$distinct} hd.fabrica, 
                                               hd.hd_chamado, 
                                               hde.nome AS consumidor_nome, 
                                               hde.email AS consumidor_email, 
                                                
                                               TO_CHAR(hdi.data, 'DD/MM/YYYY') AS data_finalizacao,
                                               pd.referencia || ' - ' || pd.descricao AS nome_produto
                                          FROM tbl_hd_chamado hd
                                          JOIN tbl_hd_chamado_extra hde ON hde.hd_chamado = hd.hd_chamado
                                          JOIN tbl_hd_chamado_item hdi ON hdi.hd_chamado = hd.hd_chamado AND hdi.status_item='Resolvido'
                                          JOIN tbl_produto pd ON pd.produto = hde.produto
                                         WHERE (hde.email IS NOT NULL AND LENGTH(hde.email) > 0)
                                           {$condData}
                                           {$condManual}
                                         {$orderBy}
                                ";
                                $resHD = pg_query($con, $sqlHD);
                                if (pg_num_rows($resHD) > 0) {
                                    while ($xhdchamado = pg_fetch_object($resHD)) {                                 

                                        if (filter_var($xhdchamado->consumidor_email, FILTER_VALIDATE_EMAIL) && preg_match('/\:link/', $pesquisa->texto_email)) {

                                            if ($envio_manual_cadence) {

                                                $delete =  "DELETE FROM tbl_resposta 
                                                            WHERE resposta = (
                                                                SELECT tbl_resposta.resposta 
                                                                FROM   tbl_resposta 
                                                                JOIN   tbl_pesquisa 
                                                                     ON (tbl_pesquisa.pesquisa = tbl_resposta.pesquisa) 
                                                                WHERE  tbl_pesquisa.categoria = 'callcenter_email'
                                                                AND tbl_resposta.hd_chamado = {$xhdchamado->hd_chamado}
                                                            )";
           
                                                $delete = pg_query($con, $delete);

                                                if (pg_result_error($con)) {
                                                    continue;
                                                }

                                            } else { 

                                                $sqlResposta = "SELECT tbl_resposta.resposta 
                                                                  FROM tbl_resposta 
                                                                  JOIN tbl_pesquisa USING(pesquisa)
                                                                  WHERE tbl_resposta.hd_chamado = {$xhdchamado->hd_chamado}
                                                                  AND tbl_pesquisa.categoria='callcenter_email'";

                                                $resResposta = pg_query($con, $sqlResposta);

                                                if (pg_num_rows($resResposta) > 0) {
                                                    continue;
                                                }
                                            }

                                            pg_query($con, 'BEGIN');
                                            
                                            $insert = "
                                                INSERT INTO tbl_resposta
                                                (hd_chamado, pesquisa, pesquisa_formulario, sem_resposta)
                                                VALUES
                                                              ({$xhdchamado->hd_chamado}, 
                                                {$pesquisa->pesquisa}, 
                                                {$pesquisa->pesquisa_formulario}, TRUE)
                                            ";
                                            $resInsert = pg_query($con, $insert);
                                            
                                            if (!strlen(pg_last_error())) {
                                                $texto_email = $pesquisa->texto_email;
                                                
                                                if (preg_match('/\:protocolo/', $texto_email)) {
                                                    $texto_email = str_replace(':protocolo', $xhdchamado->hd_chamado, $texto_email);
                                                }
                                                
                                                if (preg_match('/\:nome_consumidor_protocolo/', $texto_email)) {
                                                    $texto_email = str_replace(':nome_consumidor_protocolo', $xhdchamado->consumidor_nome, $texto_email);
                                                }
                                                
                                                if (preg_match('/\:nome_produto_protocolo/', $texto_email)) {
                                                    $texto_email = str_replace(':nome_produto_protocolo', $xhdchamado->nome_produto, $texto_email);
                                                }
                                                
                                                $token = sha1($fabrica->fabrica.$xhdchamado->hd_chamado);
                                                
                                                if ($_serverEnvironment == 'development') {
                                                    $url = "https://novodevel.telecontrol.com.br/~williamcastro/chamados/hd-6890195/externos/pesquisa_satisfacao_callcenter_email.php?token={$token}&callcenter={$xhdchamado->hd_chamado}&tipo=email";
                                                } else {
                                                    $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_callcenter_email.php?token={$token}&callcenter={$xhdchamado->hd_chamado}&tipo=email";
                                                }
                                                
                                                $texto_email = str_replace(':link', "<a href='{$url}' target='_blank' >clique aqui</a>", $texto_email);
                                                
                                                $texto_email = str_replace("\n", '<br />', $texto_email);
                                                try {
                                                    $ComunicatorMirror->post(
                                                        $xhdchamado->consumidor_email,
                                                        utf8_encode($pesquisa->titulo),
                                                        utf8_encode($texto_email),
                                                        $ComunicatorAccount,
                                                        'pesquisa@jcsbrasil.com.br'
                                                    );
                                                    

                                                    pg_query($con, 'COMMIT');
                                                } catch (\Exception $e) {
                                                    pg_query($con, 'ROLLBACK');
                                                }
                                            } else {
                                                pg_query($con, 'ROLLBACK');
                                            }
                                        }
                                    }
                                }
                            break;
                            case 'os_email':

                                $data = date('Y-m-d', strtotime("-{$pesquisa->periodo_envio} days"));

                                $data_pesquisa = " AND (o.finalizada BETWEEN '" . $data . " 00:00:00' AND '" .     
                                $data . " 23:59:59') "; 

                                if ($envio_manual_cadence) { 
                                    $data_pesquisa            = "";
                                    $data_pesquisa_finalizada = "";
                                    $numero_os                = " AND o.os = " . $os_numero_cadence . " "; 
                                }
    							
                                $sqlOs = "
                                    SELECT 
                                        o.os,
                                        o.sua_os,
                                        o.consumidor_nome,
                                        TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS data_finalizacao,
                                        p.nome AS posto_autorizado,
                                        o.consumidor_email,
                                        hd.nome nome_consumidor_protocolo,
                                        hd.produto nome_produto_protocolo
                                    FROM tbl_os o
                                    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$fabrica->fabrica}
                                    INNER JOIN tbl_posto p ON p.posto = pf.posto
                                    INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$fabrica->fabrica}
                                    LEFT JOIN tbl_hd_chamado_extra hd ON hd.os = o.os 
                                    WHERE o.fabrica = {$fabrica->fabrica}
                                    {$data_pesquisa}
                                    {$numero_os}
                                    AND o.consumidor_revenda = 'C'
                                    AND (o.consumidor_email IS NOT NULL AND LENGTH(o.consumidor_email) > 0)
                                    AND ta.fora_garantia IS NOT TRUE";
                                $resOs = pg_query($con, $sqlOs);
                                
                                if (pg_num_rows($resOs) > 0) {
                                    while ($os = pg_fetch_object($resOs)) {
                                       
                                        if (filter_var($os->consumidor_email, FILTER_VALIDATE_EMAIL) && preg_match('/\:link/', $pesquisa->texto_email)) {
                              
                                            if ($envio_manual_cadence) {

                                                $delete =  "DELETE FROM tbl_resposta 
                                                            WHERE resposta = (
                                                                SELECT tbl_resposta.resposta 
                                                                FROM   tbl_resposta 
                                                                JOIN   tbl_pesquisa 
                                                                     ON (tbl_pesquisa.pesquisa = tbl_resposta.pesquisa) 
                                                                WHERE  tbl_pesquisa.categoria = 'os_email'
                                                                AND tbl_resposta.os = {$os->os}
                                                            )";
           
                                                $delete = pg_query($con, $delete);

                                                if (pg_result_error($con)) {
                                                    continue;
                                                }

                                            } else { 
                                                $sqlResposta = "SELECT tbl_resposta.resposta 
                                                                  FROM tbl_resposta 
                                                                  JOIN tbl_pesquisa USING(pesquisa)
                                                                  WHERE tbl_resposta.os = {$os->os}
                                                                  AND tbl_pesquisa.categoria='os_email'";
                                                $resResposta = pg_query($con, $sqlResposta);

                                                if (pg_num_rows($resResposta) > 0) {
                                                    continue;
                                                }
                                            }
                                            
                                            pg_query($con, 'BEGIN');
                                            
                                            $insert = "
                                                INSERT INTO tbl_resposta
                                                (os, pesquisa, pesquisa_formulario, sem_resposta)
                                                VALUES
                                                ({$os->os}, {$pesquisa->pesquisa}, {$pesquisa->pesquisa_formulario}, TRUE)
                                            ";
                                            $resInsert = pg_query($con, $insert);
                                            
                                            if (!strlen(pg_last_error())) {
                                                $texto_email = $pesquisa->texto_email;
                                                   
                                                if (preg_match('/\:os/', $texto_email)) {
                                                    $texto_email = str_replace(':os', $os->sua_os, $texto_email);
                                                }
                                                
                                                if (preg_match('/\:finalizacao_os/', $texto_email)) {
                                                    $texto_email = str_replace(':finalizacao_os', $os->data_finalizacao, $texto_email);
                                                }
                                                
                                                if (preg_match('/\:posto_autorizado/', $texto_email)) {
                                                    $texto_email = str_replace(':posto_autorizado', $os->posto_autorizado, $texto_email);
                                                }

                                                if (preg_match('/\:nome_consumidor_os/', $texto_email)) {
                                                    $texto_email = str_replace(':nome_consumidor_os', $os->consumidor_nome, $texto_email);
                                                }

                                                if (preg_match('/\:nome_consumidor_protocolo/', $texto_email)) {
                                                    if (strlen($os->nome_consumidor_protocolo) > 0) {
                                                    $texto_email = str_replace(':nome_consumidor_protocolo', $os->nome_consumidor_protocolo, $texto_email);
                                                    } else {
                                                        $texto_email = str_replace(':nome_consumidor_protocolo', "", $texto_email); 
                                                    }
                                                }

                                                if (preg_match('/\:nome_produto_protocolo/', $texto_email)) {
                                                    if (strlen($os->nome_produto_protocolo) > 0) {
                                                        $texto_email = str_replace(':nome_produto_protocolo', $os->nome_produto_protocolo, $texto_email);
                                                    } else { 
                                                        $texto_email = str_replace(':nome_produto_protocolo', "", $texto_email);
                                                    }
                                                }
                                                
                                                $token = sha1($fabrica->fabrica.$os->os);
                                                
                                                if ($_serverEnvironment == 'development') {
                                                    $url = "https://novodevel.telecontrol.com.br/~williamcastro/chamados/hd-6890195/externos/pesquisa_satisfacao_os_email.php?token={$token}&os={$os->os}&tipo=email";
                                                } else {
                                                    $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_os_email.php?token={$token}&os={$os->os}&tipo=email";
                                                }
                                                
                                                

                                                $texto_email = str_replace(':link', "<a href='{$url}' target='_blank' >clique aqui</a>", $texto_email);
                                                
                                                $texto_email = str_replace("\n", '<br />', $texto_email);

                                                try {
                                                    $ComunicatorMirror->post(
                                                        $os->consumidor_email,
                                                        utf8_encode($pesquisa->titulo),
                                                        utf8_encode($texto_email),
                                                        $ComunicatorAccount,
                                                        'pesquisa@jcsbrasil.com.br'
                                                    );
                                                    
                                                    pg_query($con, 'COMMIT');

                                                } catch (\Exception $e) {
                                                    pg_query($con, 'ROLLBACK');
                                                    throw new Exception($e);
                                                }
                                            } else {
                                                pg_query($con, 'ROLLBACK');
                                                throw new Exception($e);
                                            }
                                        }
                                    }
                                } 
                            break;
                            case 'posto_autorizado':
                                if ($_serverEnvironment == 'development') {
                                    $wherePosto = 'AND pf.posto = 6359';
                                }
                        
                                $sqlPosto = "
                                    SELECT
                                        pf.posto,
                                        p.nome,
                                        pf.contato_email
                                    FROM tbl_posto_fabrica pf
                                    INNER JOIN tbl_posto p ON p.posto = pf.posto
                                    WHERE pf.fabrica = {$fabrica->fabrica}
                                    AND pf.credenciamento = 'CREDENCIADO'
                                    AND pf.contato_email IS NOT NULL
                                    {$wherePosto}
                                ";
                                $resPosto = pg_query($con, $sqlPosto);
                                if (pg_num_rows($resPosto) > 0) {
                                    while ($posto = pg_fetch_object($resPosto)) {

                                        if (filter_var($posto->contato_email, FILTER_VALIDATE_EMAIL) && preg_match('/\:link/', $pesquisa->texto_email)) {
                                            $sqlResposta = "SELECT tbl_resposta.resposta 
                                                              FROM tbl_resposta 
                                                              JOIN tbl_pesquisa USING(pesquisa)
                                                             WHERE tbl_resposta.posto = {$posto->posto}
                                                               AND tbl_pesquisa.categoria='posto_autorizado' 
                                                               AND tbl_resposta.data_input::date = CURRENT_DATE";
                                            $resResposta = pg_query($con, $sqlResposta);
                                            if (pg_num_rows($resResposta) > 0) {
                                                continue;
                                            }
                                            
                                            pg_query($con, 'BEGIN');
                                            
                                            $insert = "
                                                INSERT INTO tbl_resposta
                                                (posto, pesquisa, pesquisa_formulario, sem_resposta)
                                                VALUES
                                                ({$posto->posto}, {$pesquisa->pesquisa}, {$pesquisa->pesquisa_formulario}, TRUE)
                                                RETURNING resposta
                                            ";
                                            $resInsert = pg_query($con, $insert);
                                            
                                            if (!strlen(pg_last_error())) {
                                                $resposta = pg_fetch_result($resInsert, 0, 'resposta');
                                                
                                                $texto_email = $pesquisa->texto_email;
                                                
                                                if (preg_match('/\:posto_autorizado/', $texto_email)) {
                                                    $texto_email = str_replace(':posto_autorizado', $posto->nome, $texto_email);
                                                }
                                                
                                                $token = sha1($fabrica->fabrica.$posto->posto.$resposta);
                                                
                                                if ($_serverEnvironment == 'development') {
                                                    $url = "https://novodevel.telecontrol.com.br/~felipe/chamados/hd-4386788-pesquisa-cadence/externos/pesquisa_satisfacao_posto.php?token={$token}&pesquisa={$resposta}";
                                                } else {
                                                    $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_posto.php?token={$token}&pesquisa={$resposta}";
                                                }
                                                
                                                $texto_email = str_replace(':link', '<a href="'.$url.'" target="_blank" >clique aqui</a>', $texto_email);
                                                
                                                $texto_email = str_replace("\n", '<br />', $texto_email);
                                                
                                                try {
                                                    
                                                    $ComunicatorMirror->post(
                                                        $posto->contato_email,
                                                        utf8_encode($pesquisa->titulo),
                                                        utf8_encode($texto_email),
                                                        $ComunicatorAccount
                                                    );
                                                    
                                                    $sqlComunicado = "INSERT INTO tbl_comunicado (
                                                                                                  fabrica, 
                                                                                                  mensagem, 
                                                                                                  tipo, 
                                                                                                  obrigatorio_site, 
                                                                                                  ativo, 
                                                                                                  posto
                                                                                              )  VALUES(
                                                                                              ".$fabrica->fabrica.",
                                                                                              '$texto_email',
                                                                                              'comunicado',
                                                                                              't',
                                                                                              't',
                                                                                              ".$posto->posto."
                                                                                              )";
                                                    $resComunicado = pg_query($con, $sqlComunicado);
                                                    pg_query($con, 'COMMIT');

                                                } catch (\Exception $e) {
                                                    pg_query($con, 'ROLLBACK');
                                                }
                                            } else {
                                                pg_query($con, 'ROLLBACK');
                                            }
                                        }
                                    }
                                }
                                
                                if ($pesquisa->repeticao_automatica == 't' && $pesquisa->periodo_envio > 0) {
                                    $update = "
                                        UPDATE tbl_pesquisa SET
                                            data_inicial = (data_inicial + CAST(periodo_envio||' days' AS Interval))
                                        WHERE fabrica = {$fabrica->fabrica}
                                        AND pesquisa = {$pesquisa->pesquisa}
                                    ";
                                    $resUpdate = pg_query($con, $update);
                                }
                            break;
                            case 'os_sms':
                                $data_corte = "2019-01-01";

                                if (in_array($fabrica->fabrica, [157])) {
                                    $data_corte = "2019-05-10";//verificar data de corte com admin
                                }

                                $sqlOs = "
                                    SELECT DISTINCT
                                        o.os,
                                        o.sua_os,
                                        o.consumidor_nome,
                                        TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS data_finalizacao,
                                        p.nome AS posto_autorizado,
                                        o.consumidor_celular
                                    FROM tbl_os o
                                    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$fabrica->fabrica}
                                    INNER JOIN tbl_posto p ON p.posto = pf.posto
                                    INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$fabrica->fabrica}
                                    WHERE o.fabrica = {$fabrica->fabrica}
                                    AND o.consumidor_revenda = 'C'
                                    AND (o.consumidor_celular IS NOT NULL AND LENGTH(o.consumidor_celular) > 0)
                                    AND ta.fora_garantia IS NOT TRUE
                                    AND current_timestamp >= o.finalizada + INTERVAL '{$pesquisa->periodo_envio} days'
                                    AND (
                                        SELECT s.sms FROM tbl_sms s
                                        WHERE s.os = o.os
                                        AND s.origem = 'sms_pesquisa'
                                        LIMIT 1
                                    ) IS NULL
                                    AND o.finalizada::date > '{$data_corte}'::date
                                ";

                                $resOs = pg_query($con, $sqlOs);

                                while ($dadosOs = pg_fetch_object($resOs)) {

                                    $consumidor_celular = str_replace(['(',')',' ','-'], "", trim($dadosOs->consumidor_celular));

                                    if ($_serverEnvironment == 'development') {
                                        $consumidor_celular = "14991531120";
                                    }

                                    $texto_sms = $pesquisa->texto_email;

                                    if (preg_match('/\:os/', $texto_sms)) {
                                        $texto_sms = str_replace(':os', $dadosOs->sua_os, $texto_sms);
                                    }
                                    
                                    if (preg_match('/\:finalizacao_os/', $texto_sms)) {
                                        $texto_sms = str_replace(':finalizacao_os', $dadosOs->data_finalizacao, $texto_sms);
                                    }

                                    if (preg_match('/\:os/', $texto_sms)) {
                                        $texto_sms = str_replace(':os', $dadosOs->sua_os, $texto_sms);
                                    }
                                    
                                    if (preg_match('/\:finalizacao_os/', $texto_sms)) {
                                        $texto_sms = str_replace(':finalizacao_os', $dadosOs->data_finalizacao, $texto_sms);
                                    }

                                    if (preg_match('/\:posto_autorizado/', $texto_sms)) {
                                        $texto_sms = str_replace(':posto_autorizado', $dadosOs->posto_autorizado, $texto_sms);
                                    }

                                    if (preg_match('/\:nome_consumidor_os/', $texto_sms)) {
                                        $texto_sms = str_replace(':nome_consumidor_os', $dadosOs->consumidor_nome, $texto_sms);
                                    }

                                    $insert = "
                                            INSERT INTO tbl_resposta (os, pesquisa, sem_resposta)
                                            VALUES ({$dadosOs->os},{$pesquisa->pesquisa}, TRUE)
                                    ";
                                    pg_query($con, $insert);

                                    $enviar  = $sms->enviarMensagem($consumidor_celular,$dadosOs->os,' ',$texto_sms, null, null, 'sms_pesquisa');

                                    if($enviar == false){
                                        $sms->gravarSMSPendente($dadosOs->os);
                                    }

                                }

                            break;
                        }
                    } else {
                        switch ($pesquisa->categoria) {
                            case 'os':
                                $data_pesquisa = date('Y-m-d', strtotime("-{$pesquisa->periodo_envio} days"));
                                
                                $sqlOs = "
                                    SELECT 
                                        o.os,
                                        o.sua_os,
                                        o.consumidor_nome,
                                        TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS data_finalizacao,
                                        p.nome AS posto_autorizado,
                                        o.consumidor_email
                                    FROM tbl_os o
                                    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$fabrica->fabrica}
                                    INNER JOIN tbl_posto p ON p.posto = pf.posto
                                    INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$fabrica->fabrica}
                                    WHERE o.fabrica = {$fabrica->fabrica}
                                    AND (o.finalizada BETWEEN '{$data_pesquisa} 00:00:00' AND '{$data_pesquisa} 23:59:59')
                                    AND o.consumidor_revenda = 'C'
                                    AND (o.consumidor_email IS NOT NULL AND LENGTH(o.consumidor_email) > 0)
                                    AND ta.fora_garantia IS NOT TRUE
                                ";
                                $resOs = pg_query($con, $sqlOs);
                                
                                if (pg_num_rows($resOs) > 0) {
                                    while ($os = pg_fetch_object($resOs)) {
                                        if (filter_var($os->consumidor_email, FILTER_VALIDATE_EMAIL) && preg_match('/\:link/', $pesquisa->texto_email)) {
                                            $sqlResposta = "
                                                SELECT resposta FROM tbl_resposta WHERE os = {$os->os}
                                            ";
                                            $resResposta = pg_query($con, $sqlResposta);
                                            
                                            if (pg_num_rows($resResposta) > 0) {
                                                continue;
                                            }
                                            
                                            pg_query($con, 'BEGIN');
                                            
                                            $insert = "
                                                INSERT INTO tbl_resposta
                                                (os, pesquisa, pesquisa_formulario, sem_resposta)
                                                VALUES
                                                ({$os->os}, {$pesquisa->pesquisa}, {$pesquisa->pesquisa_formulario}, TRUE)
                                            ";
                                            $resInsert = pg_query($con, $insert);
                                            
                                            if (!strlen(pg_last_error())) {
                                                $texto_email = $pesquisa->texto_email;
                                                
                                                if (preg_match('/\:os/', $texto_email)) {
                                                    $texto_email = str_replace(':os', $os->sua_os, $texto_email);
                                                }
                                                
                                                if (preg_match('/\:finalizacao_os/', $texto_email)) {
                                                    $texto_email = str_replace(':finalizacao_os', $os->data_finalizacao, $texto_email);
                                                }

                                                if (preg_match('/\:nome_consumidor_os/', $texto_email)) {
                                                    $texto_email = str_replace(':nome_consumidor_os', $os->consumidor_nome, $texto_email);
                                                }
                                                
                                                if (preg_match('/\:posto_autorizado/', $texto_email)) {
                                                    $texto_email = str_replace(':posto_autorizado', $os->posto_autorizado, $texto_email);
                                                }
                                                
                                                $token = sha1($fabrica->fabrica.$os->os);
                                                
                                                if ($_serverEnvironment == 'development') {
                                                    $url = "http://localhost:8000/externos/pesquisa_satisfacao_os.php?token={$token}&os={$os->os}";
                                                } else {
                                                    $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_os.php?token={$token}&os={$os->os}";
                                                }
                                                
                                                $texto_email = str_replace(':link', "<a href='{$url}' target='_blank' >clique aqui</a>", $texto_email);
                                                
                                                $texto_email = str_replace("\n", '<br />', $texto_email);
                                                
                                                try {
                                                    $ComunicatorMirror->post(
                                                        $os->consumidor_email,
                                                        utf8_encode($pesquisa->titulo),
                                                        utf8_encode($texto_email),
                                                        $ComunicatorAccount
                                                    );
                                                    
                                                    pg_query($con, 'COMMIT');
                                                } catch (\Exception $e) {
                                                    pg_query($con, 'ROLLBACK');
                                                }
                                            } else {
                                                pg_query($con, 'ROLLBACK');
                                            }
                                        }
                                    }
                                }
                            break;
                            case 'posto_autorizado':
                                if ($_serverEnvironment == 'development') {
                                    $wherePosto = 'AND pf.posto = 6359';
                                }
                            
                                $sqlPosto = "
                                    SELECT
                                        pf.posto,
                                        p.nome,
                                        pf.contato_email
                                    FROM tbl_posto_fabrica pf
                                    INNER JOIN tbl_posto p ON p.posto = pf.posto
                                    WHERE pf.fabrica = {$fabrica->fabrica}
                                    AND pf.credenciamento = 'CREDENCIADO'
                                    AND pf.contato_email IS NOT NULL
                                    {$wherePosto}
                                ";
                                $resPosto = pg_query($con, $sqlPosto);
                                
                                if (pg_num_rows($resPosto) > 0) {
                                    while ($posto = pg_fetch_object($resPosto)) {
                                        if (filter_var($posto->contato_email, FILTER_VALIDATE_EMAIL) && preg_match('/\:link/', $pesquisa->texto_email)) {
                                            $sqlResposta = "
                                                SELECT resposta FROM tbl_resposta WHERE posto = {$posto->posto} AND data_input::date = CURRENT_DATE
                                            ";
                                            $resResposta = pg_query($con, $sqlResposta);
                                            
                                            if (pg_num_rows($resResposta) > 0) {
                                                continue;
                                            }
                                            
                                            pg_query($con, 'BEGIN');
                                            
                                            $insert = "
                                                INSERT INTO tbl_resposta
                                                (posto, pesquisa, pesquisa_formulario, sem_resposta)
                                                VALUES
                                                ({$posto->posto}, {$pesquisa->pesquisa}, {$pesquisa->pesquisa_formulario}, TRUE)
                                                RETURNING resposta
                                            ";
                                            $resInsert = pg_query($con, $insert);
                                            
                                            if (!strlen(pg_last_error())) {
                                                $resposta = pg_fetch_result($resInsert, 0, 'resposta');
                                                
                                                $texto_email = $pesquisa->texto_email;
                                                
                                                if (preg_match('/\:posto_autorizado/', $texto_email)) {
                                                    $texto_email = str_replace(':posto_autorizado', $posto->nome, $texto_email);
                                                }
                                                
                                                $token = sha1($fabrica->fabrica.$posto->posto.$resposta);
                                                
                                                if ($_serverEnvironment == 'development') {
                                                    $url = "http://localhost:8000/externos/pesquisa_satisfacao_posto.php?token={$token}&pesquisa={$resposta}";
                                                } else {
                                                    $url = "https://posvenda.telecontrol.com.br/assist/externos/pesquisa_satisfacao_posto.php?token={$token}&pesquisa={$resposta}";
                                                }
                                                
                                                $texto_email = str_replace(':link', "<a href='{$url}' target='_blank' >clique aqui</a>", $texto_email);
                                                
                                                $texto_email = str_replace("\n", '<br />', $texto_email);
                                                
                                                try {
                                                    
                                                    $ComunicatorMirror->post(
                                                        $posto->contato_email,
                                                        utf8_encode($pesquisa->titulo),
                                                        utf8_encode($texto_email),
                                                        $ComunicatorAccount
                                                    );
                                                    
                                                    pg_query($con, 'COMMIT');
                                                } catch (\Exception $e) {
                                                    pg_query($con, 'ROLLBACK');
                                                }
                                            } else {
                                                pg_query($con, 'ROLLBACK');
                                            }
                                        }
                                    }
                                }
                                
                                if ($pesquisa->repeticao_automatica == 't' && $pesquisa->periodo_envio > 0) {
                                    $update = "
                                        UPDATE tbl_pesquisa SET
                                            data_inicial = (data_inicial + CAST(periodo_envio||' days' AS Interval))
                                        WHERE fabrica = {$fabrica->fabrica}
                                        AND pesquisa = {$pesquisa->pesquisa}
                                    ";
                                    $resUpdate = pg_query($con, $update);
                                }
                            break;
                        }
                    }
                }
            }
        }
    }
    
    if ($envio_manual_cadence) {
        echo json_encode(["success" => true]);
    }

} catch (\Exception $e) {

    echo json_encode(["erro" => $e->getMessage()]);
}