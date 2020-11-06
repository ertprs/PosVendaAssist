<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once '../class/communicator.class.php';
include 'funcoes.php';

$admin_privilegios="auditoria,gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
    include "autentica_admin.php";
}

$estados = array("AC" => "Acre",        "AL" => "Alagoas",  "AM" => "Amazonas",         "AP" => "Amapá",
                 "BA" => "Bahia",       "CE" => "Ceará",    "DF" => "Distrito Federal", "ES" => "Espírito Santo",
                 "GO" => "Goiás",       "MA" => "Maranhão", "MG" => "Minas Gerais",     "MS" => "Mato Grosso do Sul",
                 "MT" => "Mato Grosso", "PA" => "Pará",     "PB" => "Paraíba",          "PE" => "Pernambuco",
                 "PI" => "Piauí",       "PR" => "Paraná",   "RJ" => "Rio de Janeiro",   "RN" => "Rio Grande do Norte",
                 "RO" => "Rondônia",    "RR" => "Roraima",  "RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
                 "SE" => "Sergipe",     "SP" => "São Paulo","TO" => "Tocantins");

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);
if (strlen(trim($_POST["posto"])) > 0) $posto = trim($_POST["posto"]);
if (strlen(trim($_GET["posto"])) > 0)  $posto = trim($_GET["posto"]);
if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

$filtro_estado = false;
if (strlen(trim($_POST['filtro_estado'])) == 2) $filtro_estado = $_POST['filtro_estado'];

if(strlen($posto)>0){

    $sql = "SELECT tbl_posto.nome         ,
        tbl_posto_fabrica.codigo_posto    ,
        tbl_posto_fabrica.contato_email
    FROM tbl_posto
    JOIN tbl_posto_fabrica USING(posto)
    WHERE fabrica = $login_fabrica
    AND   posto   = $posto ";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {

        $codigo_posto            = trim(pg_fetch_result($res,0,codigo_posto));
        $nome                    = trim(pg_fetch_result($res,0,nome));
        $contato_email           = trim(pg_fetch_result($res,0,contato_email));

        if($login_fabrica==50){
            $sql_cond = " AND     tbl_os.os NOT IN  (
                                                        SELECT interv_reinc.os
                                                        FROM (
                                                                SELECT
                                                                ultima_reinc.os,
                                                                (SELECT status_os FROM tbl_os_status WHERE
                                                                tbl_os_status.fabrica_status = $login_fabrica AND tbl_os_status.os = ultima_reinc.os AND status_os IN (13,19,68,67,70,115,118) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
                                                                FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $login_fabrica AND status_os IN (13,19,68,67,70,115,118) )ultima_reinc
                                                            ) interv_reinc
                                                        WHERE interv_reinc.ultimo_reinc_status IN (13)
                                                    ) ";
        }

        if ($login_fabrica == 15) {
            if (strlen(trim($_POST["data_inicio"])) > 0) $xdata_inicial = trim($_POST["data_inicio"]);
            if (strlen(trim($_GET["data_inicio"])) > 0)  $xdata_inicial = trim($_GET["data_inicio"]);

            if (strlen(trim($_POST["data_fim"])) > 0) $xdata_final = trim($_POST["data_fim"]);
            if (strlen(trim($_GET["data_fim"])) > 0)  $xdata_final = trim($_GET["data_fim"]);

            if(strlen($xdata_inicial) > 0 && strlen($xdata_final) > 0){
                list($ano, $mes, $dia) = explode("-", $xdata_inicial);
                $data_inicio = $dia."/".$mes."/".$ano;

                list($ano, $mes, $dia) = explode("-", $xdata_final);
                $data_fim = $dia."/".$mes."/".$ano;

                if($xdata_inicial > $xdata_final){
                    $msg_erro["msg"][]    = traduz("Data Inicial maior que final");
                    $msg_erro["campos"][] = "data_inicial";
                }

                if(strtotime($xdata_final) > strtotime($xdata_inicial . ' +3 month')){
                    $msg_erro["msg"][]    = traduz("O período não pode maior que 3 meses");
                }
                if (count($msg_erro) == 0) {
                   $sql_cond5 = " AND tbl_os.data_abertura BETWEEN '$xdata_inicial' AND '$xdata_final' ";
                }
            }
        }

        if($novaTelaOs) {
            $joins = " JOIN tbl_os_produto USING (os) JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto ";
            $cond = "";
            $campo = " ,(SELECT count(1) from tbl_os_item join tbl_os_produto using(os_produto) where tbl_os_produto.os = tbl_os.os) as qi ";
            $cond_sem_peca = " and qi = 0 ";
        }else{
            $joins = "JOIN      tbl_produto    ON tbl_produto.produto    = tbl_os.produto and tbl_produto.fabrica_i=$login_fabrica
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os";
            $cond = " AND   tbl_os_produto.os_produto ISNULL ";
        }

        if($login_fabrica == 163){
            $join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
            $cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
        }

        $sql = "SELECT tbl_os.os                                      ,
            tbl_os.sua_os                                              ,
            LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
            TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
            tbl_produto.referencia                                     ,
            tbl_produto.descricao                                      ,
            tbl_produto.referencia_fabrica    AS produto_referencia_fabrica ,
            tbl_produto.voltagem                                       ,
            CASE
                WHEN tbl_os.data_abertura::date < CURRENT_DATE - INTERVAL '30 days' THEN 0
                WHEN tbl_os.data_abertura::date   BETWEEN CURRENT_DATE - INTERVAL '30 days' AND CURRENT_DATE - INTERVAL '16 days' THEN 1
                ELSE 2
            END                                           AS classificacao,
            CURRENT_DATE - tbl_os.data_abertura::date       AS qtde_dias
            $campo
            into temp temp_sem_peca_$login_admin
            FROM      tbl_os
            $joins
            $join_163
            WHERE tbl_os.fabrica = $login_fabrica
            AND   tbl_os.posto   = $posto
            AND   tbl_os.excluida    IS NOT TRUE
            AND   tbl_os.data_fechamento IS NULL
            $cond
            $sql_cond5
            $cond_163
            $sql_cond
            ORDER BY tbl_os.data_abertura, os_ordem;

            select distinct *
            from temp_sem_peca_$login_admin
            where 1=1
            $cond_sem_peca
        ";
        #echo nl2br($sql);exit;
        if($login_fabrica == 6){
            $sql = "SELECT  tbl_os.os                                                  ,
                tbl_os.sua_os                                              ,
                LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
                tbl_produto.referencia_fabrica    AS produto_referencia_fabrica ,
                tbl_produto.referencia                                     ,
                tbl_produto.descricao                                      ,
                tbl_produto.voltagem                                       ,

                CASE
                    WHEN tbl_os.data_abertura::date < CURRENT_DATE - INTERVAL '30 days' THEN 0
                    WHEN tbl_os.data_abertura::date   BETWEEN CURRENT_DATE - INTERVAL '30 days' AND CURRENT_DATE - INTERVAL '21 days' THEN 1
                    ELSE 2
                END                                             AS classificacao,
                CURRENT_DATE - tbl_os.data_abertura::date       AS qtde_dias
            FROM      tbl_os
            JOIN      tbl_produto    ON tbl_produto.produto    = tbl_os.produto
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
            WHERE tbl_os.fabrica = $login_fabrica
            AND   tbl_os.posto   = $posto
            AND   tbl_os.excluida    IS NOT TRUE
            AND   tbl_os_produto.os_produto ISNULL
            AND   tbl_os.data_fechamento ISNULL
            ORDER BY tbl_os.data_abertura, os_ordem";
        }

        $res_posto = pg_query ($con,$sql);

        if ($_POST["gerar_excel"]) {

            if (pg_num_rows($res_posto)>0) {

                $data = date("d-m-Y-H-i");
                $fileName = "auditoria_os_sem_peca_posto_{$data}.xls";
                $file = fopen("/tmp/{$fileName}", "w");

                $head = "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
                $head .= "<tr class='titulo_tabela'>";
                $head .= "<td colspan='6' height='20'><font size='2'>". traduz("Total de OS Abertas sem Lançamento de Peças") . "</font></td>";
                $head .= "</tr>";
                $head .= "<tr class='". traduz("Titulo") ."'>";
                $head .= "<td colspan='4' height='20'><font size='2'>".$codigo_posto." - ".$nome."</font></td>";
                $head .= "<td colspan='2' height='20'>".$contato_email."</a></td>";
                $head .= "</tr>";
                $head .= "<tr class='titulo_coluna'>";
                $head .= "<td ></td>";
                $head .= "<td >". traduz("OS") . "</td>";
                $head .= "<td >". traduz("Abertura") . "</td>";
                $head .= "<td >". traduz("Dias") . "</td>";
                $head .= "<td >". traduz("Produto") . "</td>";
                $head .= "<td >". traduz("Voltagem") . "</td>";
                $head .= "</tr>";

                fwrite($file, $head);
                $body = '';
                $classificacao_anterior = "";
                for ($x=0; $x<pg_num_rows($res_posto);$x++){
                    $os_posto               = trim(pg_fetch_result($res_posto,$x,os));
                    $sua_os_posto           = trim(pg_fetch_result($res_posto,$x,sua_os));
                    $abertura_posto         = trim(pg_fetch_result($res_posto,$x,abertura));
                    $referencia_posto       = trim(pg_fetch_result($res_posto,$x,referencia));
                    $qtde_dias              = trim(pg_fetch_result($res_posto,$x,qtde_dias));
                    $descricao_posto        = trim(pg_fetch_result($res_posto,$x,descricao));
                    $voltagem_posto         = trim(pg_fetch_result($res_posto,$x,voltagem));
                    $classificacao_posto    = trim(pg_fetch_result($res_posto,$x,classificacao));
                    $produto_referencia_fabrica  = trim(pg_fetch_result($res_posto,$x,produto_referencia_fabrica));

                    if($cor=="#F1F4FA"){
                        $cor = '#F7F5F0';
                    }else{
                        $cor = '#F1F4FA';
                    }
                    if ($classificacao_posto == 0) $cor = "#FF0000";
                    if ($classificacao_posto == 1) $cor = "#FFCC00";
                    if ($classificacao_anterior <> $classificacao_posto){
                        $j = 1;
                    }

                    $body .=  "<tr class='";
                    $body .= "'align='center'>";
                    $body .= "<td bgcolor='".$cor."' >".$j."</td>";
                    $body .= "<td bgcolor='".$cor."' >".$sua_os_posto."</a></td>";
                    $body .= "<td bgcolor='".$cor."' >".$abertura_posto."</td>";
                    $body .= "<td bgcolor='".$cor."' >".$qtde_dias."</td>";
                    $body .= "<td bgcolor='".$cor."' align='left'>".$referencia_posto." - ".$descricao_posto."</td>";
                    $body .= "<td bgcolor='".$cor."' >".$voltagem_posto."</td>";
                    $body .= "</tr>";

                    $j = $j+1;
                    $classificacao_anterior = $classificacao_posto;
                }

                $body .= "<tr class='titulo_coluna'>
                            <td colspan='5'>".traduz("Total")."</td>
                            <td style='text-align:right'>".pg_num_rows($res_posto)."</td>
                          </tr>";
                $body .= "</table>";

                $body = $body;
                fwrite($file, $body);
                fclose($file);
                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");
                    echo "xls/{$fileName}";
                }
            }
            exit;
        }
    }
}

if(strlen($codigo_posto) > 0) {
        $sql = " SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$codigo_posto' AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0) {
            $posto_consulta = pg_fetch_result($res,0,posto);
        }
}

if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){
    if ($login_fabrica == 15) {
        $data_inicial   = trim($_POST["data_inicio"]);
        $data_final     = trim($_POST["data_fim"]);

        if(strlen($data_inicial) == 0 || strlen($data_final) == 0){
            $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][] = "data";
        }

        if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

            list($dia, $mes, $ano) = explode("/", $data_inicial);
            $xdata_inicial = $ano."-".$mes."-".$dia;

            list($dia, $mes, $ano) = explode("/", $data_final);
            $xdata_final = $ano."-".$mes."-".$dia;

            if($xdata_inicial > $xdata_final){
                $msg_erro["msg"][]    = traduz("Data Inicial maior que final");
                $msg_erro["campos"][] = "data_inicial";
            }

            if(strtotime($xdata_final) > strtotime($xdata_inicial . ' +3 month')){
                $msg_erro["msg"][]    = traduz("O período não pode maior que 3 meses");
            }
            if (count($msg_erro) == 0) {
               $sql_cond4 = " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
            }
        }
    }

    if($login_fabrica==50 or $login_fabrica == 74){
        if($login_fabrica != 74){
            $sql_cond = " AND     tbl_os.os NOT IN  (
                                                    SELECT interv_reinc.os
                                                    FROM (
                                                            SELECT
                                                            ultima_reinc.os,
                                                            (SELECT status_os FROM tbl_os_status WHERE
                                                            tbl_os_status.fabrica_status = $login_fabrica AND
                                                            tbl_os_status.os = ultima_reinc.os AND status_os IN (13,19,68,67,70,115,118) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
                                                            FROM (SELECT DISTINCT os FROM tbl_os_status WHERE
                                                            tbl_os_status.fabrica_status = $login_fabrica AND
                                                            status_os IN (13,19,68,67,70,115,118) ) ultima_reinc
                                                        ) interv_reinc
                                                    WHERE interv_reinc.ultimo_reinc_status IN (13)
                                                ) ";
        }
        if(strlen($posto_consulta) > 0) {
            $sql_cond2 = " AND tbl_os.posto = $posto_consulta ";
        }

    }

    if(in_array($login_fabrica,array(30,50,74))){
        $sql_cond3 = " AND credenciamento <>'DESCREDENCIADO' ";
    }

    if ($login_fabrica == 148) {
        $joinTipoAtendimento = "INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}";
        $whereTipoAtendimento = "AND tbl_tipo_atendimento.entrega_tecnica IS NOT TRUE";
    }

    if($login_fabrica == 138 or $login_fabrica == 153) {
        $joins = " JOIN tbl_os_produto USING (os) JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i=$login_fabrica";
        $cond = "";
        $campo = " ,(SELECT count(1) from tbl_os_item join tbl_os_produto using(os_produto) where tbl_os_produto.os = temp_auditoria_os_aberta_xx_$login_admin.os) as qi ";
        $cond_sem_peca = " and qi = 0 ";
    }else{
        if($novaTelaOs){
            $joins = "JOIN      tbl_produto USING(produto)
                LEFT JOIN tbl_os_produto USING(os)
                LEFT JOIN tbl_os_item USING(os_produto)";
            $cond = " AND   tbl_os_item.os_item ISNULL ";
        }else{
            $joins = "JOIN      tbl_produto USING(produto)
                LEFT JOIN tbl_os_produto USING(os) ";
            $cond = " AND   tbl_os_produto.os_produto ISNULL ";
        }
    }

    if($login_fabrica == 163){
        $join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
        $cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
    }

    $sql = "
        SELECT tbl_os.fabrica, tbl_os.posto, tbl_os.os, tbl_os.data_abertura, tbl_os.produto
        INTO   TEMP temp_auditoria_os_aberta_xx_$login_admin
        FROM   tbl_os
        {$joinTipoAtendimento}
        $join_163
        WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_os.excluida    IS NOT TRUE
        {$whereTipoAtendimento}
        $sql_cond
        $sql_cond2
        $sql_cond4
        $cond_163
        AND tbl_os.data_fechamento IS NULL
        AND tbl_os.finalizada IS NULL;

        SELECT distinct temp_auditoria_os_aberta_xx_$login_admin.fabrica, temp_auditoria_os_aberta_xx_$login_admin.posto, temp_auditoria_os_aberta_xx_$login_admin.os, temp_auditoria_os_aberta_xx_$login_admin.data_abertura $campo
        INTO   TEMP temp_auditoria_os_aberta_$login_admin
        FROM   temp_auditoria_os_aberta_xx_$login_admin
        $joins
        WHERE temp_auditoria_os_aberta_xx_$login_admin.fabrica = $login_fabrica
        $cond;

        CREATE INDEX temp_auditoria_os_aberta_os$login_admin ON temp_auditoria_os_aberta_$login_admin(OS);

        SELECT DISTINCT temp_auditoria_os_aberta.posto,
        count (temp_auditoria_os_aberta.os) AS qtde_5
        INTO TEMP temp_auditoria5_$login_admin
        FROM temp_auditoria_os_aberta_$login_admin AS temp_auditoria_os_aberta
        WHERE temp_auditoria_os_aberta.fabrica = $login_fabrica
        AND temp_auditoria_os_aberta.data_abertura::date > (CURRENT_DATE - INTERVAL '5 days')
        $cond_sem_peca
        GROUP BY temp_auditoria_os_aberta.posto;

        CREATE INDEX temp_auditoria5_POSTO$login_admin ON temp_auditoria5_$login_admin(posto);

        SELECT DISTINCT temp_auditoria_os_aberta.posto,
        count (temp_auditoria_os_aberta.os) AS qtde_15
        INTO TEMP temp_auditoria15_$login_admin
        FROM temp_auditoria_os_aberta_$login_admin AS temp_auditoria_os_aberta
        WHERE temp_auditoria_os_aberta.fabrica = $login_fabrica
        AND temp_auditoria_os_aberta.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '15 days') AND (CURRENT_DATE - INTERVAL '6 days')
        $cond_sem_peca
        GROUP BY temp_auditoria_os_aberta.posto;

        CREATE INDEX temp_auditoria15_POSTO$login_admin ON temp_auditoria15_$login_admin(posto);

        SELECT DISTINCT temp_auditoria_os_aberta.posto,
        count (temp_auditoria_os_aberta.os) AS qtde_30
        INTO TEMP temp_auditoria30_$login_admin
        FROM temp_auditoria_os_aberta_$login_admin AS temp_auditoria_os_aberta
        WHERE temp_auditoria_os_aberta.fabrica = $login_fabrica
        AND temp_auditoria_os_aberta.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '16 days')
        $cond_sem_peca
        GROUP BY temp_auditoria_os_aberta.posto;

        CREATE INDEX temp_auditoria30_POSTO$login_admin ON temp_auditoria30_$login_admin(posto);

        SELECT DISTINCT temp_auditoria_os_aberta.posto,
        count (temp_auditoria_os_aberta.os) AS qtde_30_mais
        INTO TEMP temp_auditoria31_$login_admin
        FROM temp_auditoria_os_aberta_$login_admin AS temp_auditoria_os_aberta
        WHERE temp_auditoria_os_aberta.fabrica = $login_fabrica
        AND temp_auditoria_os_aberta.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days')
        $cond_sem_peca
        GROUP BY temp_auditoria_os_aberta.posto;

        CREATE INDEX temp_auditoria31_POSTO$login_admin ON temp_auditoria31_$login_admin(posto);

        SELECT tbl_posto_fabrica.codigo_posto ,
                tbl_posto_fabrica.contato_email ,
            tbl_posto.posto  ,
            tbl_posto.nome   ,
            tbl_posto.estado ,
            dias_5.qtde_5    ,
            dias_15.qtde_15  ,
            dias_30.qtde_30  ,
            dias_30_mais.qtde_30_mais
        FROM tbl_posto
        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        LEFT JOIN temp_auditoria5_$login_admin  dias_5       ON tbl_posto.posto = dias_5.posto
        LEFT JOIN temp_auditoria15_$login_admin dias_15      ON tbl_posto.posto = dias_15.posto
        LEFT JOIN temp_auditoria30_$login_admin dias_30      ON tbl_posto.posto = dias_30.posto
        LEFT JOIN temp_auditoria31_$login_admin dias_30_mais ON tbl_posto.posto = dias_30_mais.posto
        WHERE (qtde_5 > 0 OR qtde_15 > 0 OR qtde_30 > 0  OR qtde_30_mais > 0 )
        $sql_cond3
    ";

    if ($login_fabrica != 2) {
        $sql .= ($filtro_estado) ? "AND estado = '$filtro_estado' ORDER BY nome" : "ORDER BY estado,nome";
    } else {
        $sql .= " ORDER BY tbl_posto.nome;";
    }
    #zecho nl2br($sql);exit;
    if($login_fabrica == 6){
        $sql = "
            SELECT tbl_os.fabrica, tbl_os.posto, tbl_os.os, tbl_os.data_abertura
            INTO   TEMP temp_auditoria_os_aberta_$login_admin
            FROM   tbl_os
            LEFT JOIN tbl_os_produto USING (os)
            LEFT JOIN tbl_os_item    USING (os_produto)
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_os.excluida    IS NOT TRUE
            AND tbl_os_item.os_item IS NULL
            AND   tbl_os_produto.os_produto ISNULL
            AND tbl_os.data_fechamaneto IS NULL
            AND tbl_os.finalizada   IS NULL;

            CREATE INDEX temp_auditoria_os_aberta_os$login_admin ON temp_auditoria_os_aberta_$login_admin(OS);

            SELECT temp_auditoria_os_aberta_$login_admin.posto,
            count (temp_auditoria_os_aberta_$login_admin.os) AS qtde_5
            INTO TEMP temp_auditoria5_$login_admin
            FROM   temp_auditoria_os_aberta_$login_admin
            WHERE temp_auditoria_os_aberta_$login_admin.fabrica = $login_fabrica
            AND temp_auditoria_os_aberta_$login_admin.data_abertura::date > (CURRENT_DATE - INTERVAL '5 days')
            GROUP BY temp_auditoria_os_aberta_$login_admin.posto;

            CREATE INDEX temp_auditoria5_POSTO$login_admin ON temp_auditoria5_$login_admin(posto);

            SELECT temp_auditoria_os_aberta_$login_admin.posto,
            count (temp_auditoria_os_aberta_$login_admin.os) AS qtde_15
            INTO TEMP temp_auditoria15_$login_admin
            FROM temp_auditoria_os_aberta_$login_admin
            WHERE temp_auditoria_os_aberta_$login_admin.fabrica = $login_fabrica
            AND temp_auditoria_os_aberta_$login_admin.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '20 days') AND (CURRENT_DATE - INTERVAL '6 days')
            GROUP BY temp_auditoria_os_aberta_$login_admin.posto;

            CREATE INDEX temp_auditoria15_POSTO$login_admin ON temp_auditoria15_$login_admin(posto);

            SELECT temp_auditoria_os_aberta_$login_admin.posto,
            count (temp_auditoria_os_aberta_$login_admin.os) AS qtde_30
            INTO TEMP temp_auditoria30_$login_admin
            FROM temp_auditoria_os_aberta_$login_admin
            WHERE temp_auditoria_os_aberta_$login_admin.fabrica = $login_fabrica
            AND temp_auditoria_os_aberta_$login_admin.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days')
            GROUP BY temp_auditoria_os_aberta_$login_admin.posto;

            CREATE INDEX temp_auditoria30_POSTO$login_admin ON temp_auditoria30_$login_admin(posto);

            SELECT temp_auditoria_os_aberta_$login_admin.posto,
            count (temp_auditoria_os_aberta_$login_admin.os) AS qtde_30_mais
            INTO TEMP temp_auditoria31_$login_admin
            FROM temp_auditoria_os_aberta_$login_admin
            WHERE temp_auditoria_os_aberta_$login_admin.fabrica = $login_fabrica
            AND temp_auditoria_os_aberta_$login_admin.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days')
            GROUP BY temp_auditoria_os_aberta_$login_admin.posto;

            CREATE INDEX temp_auditoria31_POSTO$login_admin ON temp_auditoria31_$login_admin(posto);

            SELECT  tbl_posto_fabrica.codigo_posto ,
                tbl_posto_fabrica.contato_email,
                tbl_posto.posto  ,
                tbl_posto.nome   ,
                tbl_posto.estado ,
                dias_5.qtde_5    ,
                dias_15.qtde_15  ,
                dias_30.qtde_30  ,
                dias_30_mais.qtde_30_mais
            FROM tbl_posto
            JOIN tbl_posto_fabrica                               ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN temp_auditoria5_$login_admin  dias_5       ON tbl_posto.posto = dias_5.posto
            LEFT JOIN temp_auditoria15_$login_admin dias_15      ON tbl_posto.posto = dias_15.posto
            LEFT JOIN temp_auditoria30_$login_admin dias_30      ON tbl_posto.posto = dias_30.posto
            LEFT JOIN temp_auditoria31_$login_admin dias_30_mais ON tbl_posto.posto = dias_30_mais.posto
            WHERE (qtde_5 > 0 OR qtde_15 > 0 OR qtde_30 > 0  OR qtde_30_mais > 0 )
            ORDER BY tbl_posto.nome
        ";
    }

    if (count($msg_erro) == 0) {

        $res_geral = pg_query ($con,$sql);

        if ($_POST["gerar_excel"]) {

            if (pg_num_rows($res_geral)>0) {
                $data = date("d-m-Y-H-i");
                $fileName = "auditoria_os_sem_peca_{$data}.xls";
                $file = fopen("/tmp/{$fileName}", "w");

                $head = "";
                if ($filtro_estado){
                    $head .= "<p align='center' style='font-size: 12px'>".traduz("Postos do estado:")."<b>".$estados[$filtro_estado]."</b></p>";
                }
                $head .= "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
                $head .= "<tr class='titulo_tabela'>";

                $title_colspan = (!$filtro_estado) ? 8 : 9;

                $head .= "<td colspan='".$title_colspan."' height='20'><font size='2'>" . traduz("Total de OS Abertas sem Lançamento de Peças") ."</font></td>";
                $head .= "</tr>";
                $head .= "<tr class='titulo_coluna'>";

                if (!$filtro_estado and in_array($login_fabrica,array(24,74,104))) {
                    $head .= "<td>UF</td>";
                }

                $head .= "<td>" . traduz("Código Posto") . "</td>";
                $head .= "<td>" . traduz("Nome Posto") . "</td>";

                $head .= "<td>E-mail</td>";
                $head .= "<td>" . traduz("até 5 dias") . "</td>";

                if($login_fabrica == 6) {
                    $head .= "<td>" . traduz("até 20 dias") . "</td>";
                }else{
                    $head .= "<td>" . traduz("até 15 dias") . "</td>";
                }

                $head .= "<td>" . traduz("até 30 dias") . "</td>";
                $head .= "<td>" . traduz(" 30 dias") . "</td>";
                $head .= "<td>" . traduz("Total") . "</td>";
                $head .= "</tr>\n";

                fwrite($file, $head);
                $body = '';

                $qtde_total_mais_geral = 0;
                for ($x=0; $x<pg_num_rows($res_geral);$x++){
                    $posto_geral          = trim(pg_fetch_result($res_geral,$x,'posto'));
                    $nome_geral           = trim(pg_fetch_result($res_geral,$x,'nome'));
                    $estado_geral         = trim(pg_fetch_result($res_geral,$x,'estado'));
                    $codigo_posto_geral   = trim(pg_fetch_result($res_geral,$x,'codigo_posto'));
                    $contato_email_geral  = trim(pg_fetch_result($res_geral,$x,'contato_email'));
                    $qtde_5_geral         = trim(pg_fetch_result($res_geral,$x,'qtde_5'));
                    $qtde_15_geral        = trim(pg_fetch_result($res_geral,$x,'qtde_15'));
                    $qtde_30_geral        = trim(pg_fetch_result($res_geral,$x,'qtde_30'));
                    $qtde_30_mais_geral   = trim(pg_fetch_result($res_geral,$x,'qtde_30_mais'));
                    $qtd_total_geral = $qtde_5_geral + $qtde_15_geral + $qtde_30_geral + $qtde_30_mais_geral;
                    $qtde_total_mais_geral = $qtde_total_mais_geral + $qtd_total_geral;

                    $cor = ($cor=="#F1F4FA")?'#F7F5F0':'#F1F4FA';

                    $body .= "<tr class='Conteudo' align='center'>";

                    if (!$filtro_estado and in_array($login_fabrica,array(24,74,104))) {
                        $body .= "<td bgcolor='".$cor."' title='".$estados[$estado]."'>".$estado."</td>";
                    }

                    $body .= "<td bgcolor='".$cor."'><a href='".$PHP_SELF."?posto=".$posto_geral."' target='_blank'>".$codigo_posto_geral."</a></td>";
                    $body .= "<td bgcolor='".$cor."' align='left'>".$nome_geral."</td>";
//                     $body .= "<td bgcolor='".$cor."' align='left'><a href='mailto:".$contato_email_geral."'>".$contato_email_geral."</a></td>";
                    $body .= "<td bgcolor='".$cor."' align='left'>$contato_email_geral</td>";
                    $body .= "<td bgcolor='".$cor."'>".$qtde_5_geral."</td>";
                    $body .= "<td bgcolor='".$cor."'>".$qtde_15_geral."</td>";
                    $body .= "<td bgcolor='#FFCC00'>".$qtde_30_geral."</td>";
                    $body .= "<td bgcolor='#FF0000'><font color='#FFFFFF'>".$qtde_30_mais_geral."</font></td>";

                    $total_geral = $qtde_5_geral + $qtde_15_geral +$qtde_30_geral + $qtde_30_mais_geral;
                    $total_qtde_5_geral         += $qtde_5_geral;
                    $total_qtde_15_geral        += $qtde_15_geral;
                    $total_qtde_30_geral        += $qtde_30_geral;
                    $total_qtde_30_mais_geral   += $qtde_30_mais_geral;

                    $body .= "<td bgcolor='".$cor."' >".$total_geral."</td>";

                    $total_geral_geral = $total_geral + $total_geral_geral;

                    $body .= "</tr>\n";
                }

                //$body .= "<tfoot>";

                if($login_fabrica==50) { // HD 57319
                    $body .= "<tr class='Titulo'>
                                <td colspan='3' style='font-size:14px;'><b>".traduz("Subtotal")."</b></td>
                                <td style='font-size:14px;'><b>".$total_qtde_5_geral."</b></td>
                                <td style='font-size:14px;'><b>".$total_qtde_15_geral."</b></td>
                                <td style='font-size:14px;'><b>".$total_qtde_30_geral."</b></td>
                                <td style='font-size:14px;'><b>".$total_qtde_30_mais_geral."</b></td>
                                <td style='font-size:14px;'></td>
                              </tr>";
                }
                $body .= "<tr class='titulo_coluna'>
                            <td colspan='7' style='font-size:14px;'><b>".traduz("Total")."</b></td>
                            <td colspan='1' style='font-size:12px;text-align:right;padding: 0 10px 0 0'><b>".$total_geral_geral."</b></td>
                          </tr>";
                //$body .= "</tfoot>";
                $body .= "</table>";

                $body = $body;
                fwrite($file, $body);
                fclose($file);
                if (file_exists("/tmp/{$fileName}")) {

                    system("mv /tmp/{$fileName} xls/{$fileName}");

                    echo "xls/{$fileName}";
                }
            }
            exit;
        }
    }
}

$email  = $_GET['email'];
if(strlen($email)==0) $email  = $_POST['email'];

if($email =='true'){
    if ($login_fabrica == 15) {
        $e_data_inicial   = trim($_GET["data_inicio"]);
        if(strlen($e_data_inicial)==0) $e_data_inicial  = $_POST['data_inicio'];
        $e_data_final     = trim($_GET["data_fim"]);
        if(strlen($e_data_final)==0) $e_data_final  = $_POST['data_fim'];
        $e_posto = trim($_GET["posto_e"]);
        if(strlen($e_posto)==0) $e_posto  = $_POST['posto_e'];

        if(strlen($e_posto) > 0){
            $titulo = traduz("OS Abertas sem Lançamento de Peças");
        }
    }
    if(strlen($_POST['btn_mail']) > 0) {
        $msg_erro="";
        $titulo   = trim($_POST['titulo']);
        $conteudo = trim($_POST['conteudo']);
        $tipo_os   = trim($_POST['tipo_os']);

        if(strlen($conteudo)==0 AND $login_fabrica <> 15) {
            $msg_erro = traduz("Por favor, digite o conteúdo do E-mail");
        }

        if(strlen($titulo)==0) {
            $msg_erro = traduz("Por favor, digite o assunto do E-mail");
        }

        $auxConteudo = $conteudo;
        if ($login_fabrica == 15 && $tipo_os == "") {
            $anexo = filter_input(INPUT_POST,'anexo');

            if (strlen($anexo) > 0) {
                $auxConteudo .= "
                    <br />
                    <p>". traduz("Segue tabela com as OS abertas sem lançamento de peças") . "</p>
                    <br />
                ";
                $auxConteudo .= $anexo;
            }
// echo $conteudo;exit;
            if(strlen($e_data_inicial) == 0 || strlen($e_data_final) == 0){
                $msg_erro = traduz("Os campos Datas estão inválidos!");
            }

            if(strlen($e_data_inicial) > 0 && strlen($e_data_final) > 0){

                list($dia, $mes, $ano) = explode("-", $e_data_inicial);
                $e_xdata_inicial = $ano."-".$mes."-".$dia;

                list($dia, $mes, $ano) = explode("-", $e_data_final);
                $e_xdata_final = $ano."-".$mes."-".$dia;

                if($e_xdata_inicial > $e_xdata_final){
                    $msg_erro = traduz("Data Inicial maior que final");
                }

                if(strtotime($e_xdata_final) > strtotime($e_xdata_inicial . ' +3 month')){
                    $msg_erro = traduz("O período não pode maior que 3 meses");
                }
                if (strlen($msg_erro) == 0) {
                   $sql_cond_e = " AND tbl_os.data_digitacao BETWEEN '$e_xdata_inicial 00:00:00' AND '$e_xdata_final 23:59:59' ";
                }
            }

            if (strlen($e_posto) > 0 && strtolower($e_posto) !== 'null') {
                $sql_cond_e .= " AND tbl_os.posto = $e_posto ";
            }
        }

        if($tipo_os == 'ate_5') {
            $sql_cond = " AND tbl_os.data_abertura::date > (CURRENT_DATE - INTERVAL '5 days') ";
        }elseif($tipo_os == 'ate_15') {
            $sql_cond = " AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '15 days') AND (CURRENT_DATE - INTERVAL '6 days') ";
        }elseif($tipo_os == 'ate_30') {
            $sql_cond = " AND tbl_os.data_abertura::date BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '16 days') ";
        }elseif($tipo_os =='mais_30') {
            $sql_cond = " AND tbl_os.data_abertura::date < (CURRENT_DATE - INTERVAL '30 days') ";
        }

        if(strlen($msg_erro) == 0) {
            $sqlx=" SELECT email,nome_completo,to_char(current_timestamp,'MDHI24MISS') as data FROM tbl_admin WHERE admin = $login_admin";
            $resx=pg_query($con,$sqlx);
            $email_remetente = pg_fetch_result($resx,0,email);
            $admin_nome      = pg_fetch_result($resx,0,nome_completo);
            $data            = pg_fetch_result($resx,0,data);

            if ($login_fabrica == 148) {
                $join = "INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}";
                $where = "AND tbl_tipo_atendimento.entrega_tecnica IS NOT TRUE";
            }

            if(in_array($login_fabrica, [167, 203])){
                $join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
                $cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
            }


            $sql =" SELECT  tbl_os.posto,count(tbl_os.os) as qtde_os
               INTO TEMP    temp_auditoria_$data
                    FROM    tbl_os
               LEFT JOIN    tbl_os_produto USING (os)
               LEFT JOIN    tbl_os_item    USING (os_produto)
                    {$join}
                    $join_163
                    WHERE   tbl_os.fabrica = $login_fabrica
                    AND     tbl_os.excluida    IS NOT TRUE
                    AND     tbl_os_item.os_item IS NULL
                    AND     tbl_os.finalizada IS NULL
                    AND     tbl_os.os NOT IN  (
                                SELECT  interv_reinc.os
                                FROM    (
                                    SELECT  ultima_reinc.os,
                                            (
                                                SELECT  status_os
                                                FROM    tbl_os_status
                                                WHERE   tbl_os_status.fabrica_status = $login_fabrica
                                                AND     tbl_os_status.os = ultima_reinc.os
                                                AND     status_os IN (13,19,68,67,70,115,118)
                                          ORDER BY      data DESC
                                                LIMIT   1
                                            ) AS ultimo_reinc_status
                                    FROM    (
                                        SELECT  DISTINCT
                                                os
                                        FROM    tbl_os_status
                                        WHERE   tbl_os_status.fabrica_status = $login_fabrica
                                        AND     status_os IN (13,19,68,67,70,115,118)
                                    ) ultima_reinc
                                ) interv_reinc
                                WHERE interv_reinc.ultimo_reinc_status IN (13)
                    )
                    {$where}
                    $cond_163
                    $sql_cond
                    $sql_cond_e
                    GROUP BY tbl_os.posto;

                    CREATE INDEX temp_auditoria_POSTO$data ON temp_auditoria_$data(posto);

                    SELECT  tbl_posto_fabrica.codigo_posto ,
                            tbl_posto.nome                 ,
                            tbl_posto.estado               ,
                            tbl_posto_fabrica.contato_email
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica          ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    LEFT JOIN temp_auditoria_$data  ON tbl_posto.posto = temp_auditoria_$data.posto
                    WHERE (qtde_os > 0 )
                    AND   contato_email IS NOT NULL
                    AND credenciamento <>'DESCREDENCIADO'
                    ORDER BY tbl_posto.nome";

            $res = pg_query ($con,$sql);
            if(pg_num_rows($res) > 0){
                for ( $i = 0 ; $i < pg_num_rows($res) ; $i++ ) {

                    if($i % 20 ==0 and $i > 0) {
                        sleep(5);
                    }
                    $contato_email = trim(pg_fetch_result($res,$i,contato_email));
                    $nome          = pg_fetch_result($res,$i,nome);
                    $codigo_posto  = pg_fetch_result($res,$i,codigo_posto);

                    $remetente    = $email_remetente;
                    $destinatario = $codigo_posto." - ".$nome ." <".$contato_email."> ";
                    $headers="Return-Path: <".$remetente.">\nFrom: $admin_nome <".$remetente.">\nContent-type: text/html\n";

                    $mailTc = new TcComm("smtp@posvenda");
                    if($mailTc->sendMail(
                            $contato_email,
                            utf8_encode($titulo),
                            utf8_encode($auxConteudo),
                            $remetente
                        )){
                        $msg_erro= traduz("Email enviado com sucesso");
                    }



//                     if(mail($destinatario, utf8_encode($titulo), utf8_encode($conteudo), $headers)) {
//                         $msg_erro=" Email enviado com sucesso";
//                     };
                }
            }
        }
    }

    if(strlen($msg_erro) >0){
        echo "<table border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff' width = '300'>";
        echo "<tr>";
        echo "<td valign='middle' align='center' class='error'>";
        echo $msg_erro;
        echo "</td>";
        echo "</tr>";
        echo "</table>";
    }

    if ($login_fabrica == 15) {
        $sqlDados = "
            SELECT  tbl_posto_fabrica.codigo_posto ,
                    tbl_posto.nome                 ,
                    tbl_posto.estado               ,
                    tbl_posto_fabrica.contato_email
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
            WHERE   tbl_posto.posto = $e_posto
        ";
//         exit(nl2br($sqlDados));
        $resDados = pg_query($con,$sqlDados);

        $contato_email = pg_fetch_result($resDados,0,contato_email);
        $nome          = pg_fetch_result($resDados,0,nome);
        $codigo_posto  = pg_fetch_result($resDados,0,codigo_posto);

        $tabelaEmail = "
            <table style='border:1px solid #000;font-size:10px;font-family:tahoma;'>
                <thead>
                    <tr class='titulo_tabela' >
                        <th colspan='6'>". traduz("Total de OS Abertas sem Lançamento de Peças") ."</th>
                    </tr>
                    <tr class='titulo_tabela'>
                        <th colspan='3' height='20'>$codigo_posto - $nome</th>
                        <th colspan='2' height='20'>$contato_email</th>
                    </tr>
                    <tr class='titulo_coluna'>
                        <th>". traduz("OS") ."</th>
                        <th>". traduz("Abertura") ."</th>
                        <th>". traduz("Dias") ."</th>
                        <th>". traduz("Produto") ."</th>
                        <th>". traduz("Voltagem") ."</th>
                    </tr>
                </thead>
                <tbody>
        ";

        $sqlTabela = "
            SELECT  tbl_os.os                                                   ,
                    tbl_os.sua_os                                              ,
                    LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
                    tbl_produto.referencia_fabrica    AS produto_referencia_fabrica ,
                    tbl_produto.referencia                                     ,
                    tbl_produto.descricao                                      ,
                    tbl_produto.voltagem                                       ,
                    CASE
                        WHEN tbl_os.data_abertura::date < CURRENT_DATE - INTERVAL '30 days' THEN 0
                        WHEN tbl_os.data_abertura::date   BETWEEN CURRENT_DATE - INTERVAL '30 days' AND CURRENT_DATE - INTERVAL '16 days' THEN 1
                        ELSE 2
                    END                                           AS classificacao,
                    CURRENT_DATE - tbl_os.data_abertura::date       AS qtde_dias
            FROM    tbl_os
            JOIN    tbl_produto     ON  tbl_produto.produto     = tbl_os.produto
                                    AND tbl_produto.fabrica_i   = $login_fabrica
       LEFT JOIN    tbl_os_produto  ON  tbl_os_produto.os       = tbl_os.os
            WHERE   tbl_os.fabrica              = $login_fabrica
            AND     tbl_os.posto                = $e_posto
            AND     tbl_os.excluida             IS NOT TRUE
            AND     tbl_os.data_fechamento      IS NULL
            AND     tbl_os_produto.os_produto   IS NULL
            AND     tbl_os.data_abertura        BETWEEN '$e_data_inicial' AND '$e_data_final'
        ";
//         exit(nl2br($sqlTabela));
        $resTabela = pg_query($con,$sqlTabela);

        $resultado = pg_fetch_all($resTabela);
        foreach ($resultado as $chave => $campo) {
            $tabelaEmail .= "
                    <tr>
                        <td>".$campo['sua_os']."</td>
                        <td>".$campo['abertura']."</td>
                        <td>".$campo['qtde_dias']."</td>
                        <td>".$campo['referencia']." - ".$campo['descricao']."</td>
                        <td>".$campo['voltagem']."</td>
                    </tr>
            ";
        }
        $tabelaEmail .= "
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan='4'>".traduz("Total:")."</td>
                        <td colspan='4'>".count($resultado)."</td>
                    </tr>
                </tfoot>
            </table>
        ";

    }

    echo "<form name='frm_mail' method='post' action='$PHP_SELF'>";
    echo "<div id='mensagem'>";
    echo "</div>";
    echo "<table width = '350'><tr>";
    echo "<td>". traduz("Tipo OS") ."</td>";
    echo "<td align='left'>";
    echo "<select name='tipo_os' size='1'>";
    if ($login_fabrica == 15 && strlen($e_posto) > 0) {

        echo "<option value=''>". traduz("Todas") ."</option>";
    }
    echo "<option value='ate_5'>". traduz("Até 5 dias") ."</option>";
    echo "<option value='ate_15'>". traduz("Até 15 dias") ."</option>";
    echo "<option value='ate_30'>". traduz("Até 30 dias") ."</option>";
    echo "<option value='mais_30'>". traduz("+ 30 dias") ."</option>";
    echo "</select>";
    echo "</td></tr>";
    echo "<tr>";
    echo "<input type='hidden' name='email' value='true'>";
    echo "<td>". traduz("Assunto") ."</td><td><input type='text' size='40' name='titulo' value='$titulo'>";
    echo "</td></tr>";
    echo "<tr><td valign='top'>";
    echo traduz("Mensagem") . "</td><td> <textarea name='conteudo' ROWS='10' COLS='48' class='input' value='$conteudo'></textarea>";
    echo "</td></tr>";

    if ($login_fabrica == 15 && strlen($e_posto) > 0) {
        echo "
            <tr><td style='vertical-align:top'>" . traduz("Anexo") ."</td><td>
        ";
        echo $tabelaEmail;
        echo "
            </td></tr>
        ";
    }

    echo "<tr><td align='center' colspan='100%'>";
    echo "<input type='hidden' name='btn_mail' value=''>";
    echo "<input type='hidden' name='data_inicio' value='$e_data_inicial'>";
    echo "<input type='hidden' name='data_fim' value='$e_data_final'>";
    echo "<input type='hidden' name='posto_e' value='$e_posto'>";
    echo "<input type='hidden' name='anexo' value='".htmlentities($tabelaEmail,ENT_QUOTES)."'>";
    echo "<input type='button' name='btn_acao' value='".traduz('Enviar E-MAIL')."' onclick=\"javascript: if (document.frm_mail.btn_mail.value == '' ) { document.frm_mail.btn_mail.value='continuar' ;  document.frm_mail.submit(); document.getElementById('mensagem').innerHTML='".traduz('Por favor, não feche esta janela até aparecer a mensagem que foram enviados e-mails com sucesso.')."'; } else { alert ('". traduz("Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.")."') }\" >";
    echo "</td></tr></table>";

    echo "</form>";
    exit;

}

$layout_menu = "auditoria";
$title = traduz("AUDITORIA - OS ABERTAS SEM LANÇAMENTO DE PEÇAS");

include 'cabecalho_new.php';

$plugins = array(
    "datepicker",
    "shadowbox",
    "maskedinput",
    "alphanumeric",
    "ajaxform",
    "price_format"
);

include 'plugin_loader.php';
?>
<script language="JavaScript">
$(function() {
    /**
    *Máscara campo Data
    */
    $("#data_inicio").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("#data_fim").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    /**
     * Inicia o shadowbox, obrigatório para a lupa funcionar
     */
    Shadowbox.init();

    /**
     * Evento que chama a função de lupa para a lupa clicada
     */
    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}

function enviaEmail(posto = null) {
    var url = "";
    var login_fabrica = <?=$login_fabrica?>;
    //console.log(login_fabrica);
    if (login_fabrica == 15) {
        var data_inicio = $("#data_inicio").val();
        data_inicio = data_inicio.replace(/\//g,"-");
        var data_fim = $("#data_fim").val();
        data_fim = data_fim.replace(/\//g,"-");

        //console.log(posto);

        url = "<? echo $PHP_SELF;?>?email=true&posto_e="+posto+"&data_inicio="+data_inicio+"&data_fim="+data_fim;
    } else {
        url = "<? echo $PHP_SELF;?>?email=true";
    }

    janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
}

function loadingImage(){
    document.getElementById('imagem_load').style.display = 'block';
}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?></b>
</div>
<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
    <div class="titulo_tabela"><?php echo traduz("Relatório de OS Abertas sem Lançamento de Peças"); ?></div>
    <br>
    <?php
    if ($login_fabrica == 15) {?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class="span2">
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>' >
                <label class="control-label" for="data_inicio"><?php echo traduz("Data Inicio"); ?></label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?=$data_inicio?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
        <div class="span2">
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>' >
                <label class="control-label" for="data_fim"><?php echo traduz("Data Fim"); ?></label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="data_fim" name="data_fim" class="span12" type="text" value="<?=$data_fim?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4"></div>
    </div>
    <?php
    }
    if(in_array($login_fabrica, array(24,50,74)) and strlen($posto) == 0) { // HD 56651
        if ( in_array($login_fabrica,array(50,74)) ) {
            include "javascript_pesquisas.php" ?>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=getValue('codigo_posto')?>" >
                                <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto"); ?></label>
                        <div class='controls controls-row'>
                            <div class='span11 input-append'>
                                <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=getValue('descricao_posto')?>" >&nbsp;
                                <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
        <?php
        }
        if ( in_array($login_fabrica, array(24,74)) ) {?>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='filtro_estado' title="<?php echo traduz("Selecione um estado para consultar só esses postos"); ?>"><?php echo traduz("Estado"); ?></label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <select name="filtro_estado"  class="span10" id="filtro_estado" title="<?php echo traduz("Para filtrar por estado, selecione o Estado"); ?>">
                                <option value=""><?=traduz("Todos")?></option>
                                <?php
                                foreach ($estados as $sigla=>$nome_estado) {
                                    $estado_sel = ($sigla == $filtro_estado) ? " selected":"";
                                    echo "\t\t\t<option value='$sigla'$estado_sel>$nome_estado</option>\n";
                                }?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span6"></div>
        </div>
        <?php
        }
    }?>
    <br />
    <?php
    if(in_array($login_fabrica, array(15,24,50,74))){?>
        <p class="tac">
            <input type="submit" class="btn" name="btn_acao" value="<?php echo traduz("Pesquisar"); ?>" />
        </p>
    <?php
    }else{?>
        <p class="tac">
            <input type="button" onclick="javascript: window.location='<?php echo $PHP_SELF; ?>?btn_acao=ok'" value="<?php echo traduz("Gerar Relatório"); ?>">
        </p>
    <?php
    }
    ?>
    <br />
</FORM>


<!-- Tabela -->
<table width="700" border="0" cellpadding="0" cellspacing="2" align="center"  >
    <tr style="font:12px Arial;">
        <td bgcolor="#FF0000">&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td width='100%' valign="middle" align="left">&nbsp;<?php echo traduz("OS aberta sem lançamento de peças com mais de 30 dias"); ?></td>
    </tr>
    <tr style="font:12px Arial;">
        <td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td width='100%' valign="middle" align="left">
            &nbsp;<?if($login_fabrica==6) echo traduz("OS de 20 a 30 dias aberta sem lançamento de peças");
                                     else echo traduz("OS aberta sem lançamento de peças entre 15 e 30 dias");?>
        </td>
    </tr>
</table>
<br>
<?php 
    $colspan = 6;
    $colspan2 = 2;
    $colspan3 = 5;
    if ($login_fabrica == 171) {
        $colspan = 7;
        $colspan2 = 3;
        $colspan3 = 6;
    }
?>

<?php
//Lista da Consulta
if (isset($res_posto)) {
    if (pg_num_rows($res_posto) > 0) { ?>
        <form name="frm_tab" method="GET" class="form-search form-inline" enctype="multipart/form-data" >
            <table class='table table-striped table-bordered table-hover table-fixed'>
                <thead>
                    <tr class='titulo_tabela' >
                        <th colspan='<?php echo $colspan;?>'><?php echo traduz("Total de OS Abertas sem Lançamento de Peças"); ?></th>
                    </tr>
                    <tr class='titulo_tabela'>
                        <th colspan='4' height='20'><font size='2'><?=$codigo_posto?> - <?=$nome?></font></th>
                        <th colspan='<?php echo $colspan2;?>' height='20'><a href='mailto:$contato_email'><font size='2' color='#E8E8E8'><?=$contato_email?></font></a></th>
                    </tr>
                    <tr class='titulo_coluna'>
                        <th ></th>
                        <th ><?php echo traduz("OS"); ?></th>
                        <th ><?php echo traduz("Abertura"); ?></th>
                        <th ><?php echo traduz("Dias"); ?></th>
                        <?php if ($login_fabrica == 171) {?>
                        <th ><?php echo traduz("Referência Fábrica"); ?></th>
                        <?php }?>
                        <th ><?php echo traduz("Produto"); ?></th>
                        <th ><?php echo traduz("Voltagem"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php

                $total = pg_num_rows($res_posto);

                for ($i=0; $i< $total ; $i++){

                $os             = trim(pg_fetch_result($res_posto,$i,os));
                $sua_os         = trim(pg_fetch_result($res_posto,$i,sua_os));
                $abertura       = trim(pg_fetch_result($res_posto,$i,abertura));
                $referencia     = trim(pg_fetch_result($res_posto,$i,referencia));
                $qtde_dias      = trim(pg_fetch_result($res_posto,$i,qtde_dias));
                $descricao      = trim(pg_fetch_result($res_posto,$i,descricao));
                $voltagem       = trim(pg_fetch_result($res_posto,$i,voltagem));
                $classificacao  = trim(pg_fetch_result($res_posto,$i,classificacao));
                $produto_referencia_fabrica  = trim(pg_fetch_result($res_posto,$i,produto_referencia_fabrica));

                if($classificacao==0){
                    $cor = "style='background-color:#FF0000'";
                }elseif ($classificacao==1) {
                    $cor = "style='background-color:#FFCC00'";
                }else{
                    $cor = "";
                }

                if($classificacao_anterior <> $classificacao){
                    $x = 1;
                }
                ?>
                <tr align='center'>
                    <td <?=$cor?> ><?=$x?></td>
                    <td <?=$cor?> >
                        <a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a>
                    </td>
                    <td <?=$cor?> ><?=$abertura?></td>
                    <td <?=$cor?> ><?=$qtde_dias?></td>
                    <?php if ($login_fabrica == 171) {?>
                    <td <?=$cor?>><?=$produto_referencia_fabrica?></td>
                    <?php }?>
                    <td <?=$cor?> align='left'><?=$referencia?> - <?=$descricao?></td>
                    <td <?=$cor?> ><?=$voltagem?></td>
                </tr>
                <?php

                $x = $x+1;
                $classificacao_anterior = $classificacao;

            }
            ?>
                </tbody>
                <tfoot>
                <tr class='titulo_coluna'>
                    <td colspan='<?php echo $colspan3?>'><?php echo traduz("Total"); ?></td>
                    <td style='text-align:right'><?=$total?></td>
                </tr>
                <?php
                if($login_fabrica==15) {?>
                <tr>
                    <td colspan='5'>&nbsp;</td>
                    <td >
                        <input type='button' onClick="javascript: enviaEmail(<?=$posto?>);" value='<?php echo traduz("Enviar E-mail"); ?>'>
                    </td>
                </tr>
                <?php
                }?>
                </tfoot>
            </table>
        </form>
        <br />
        <?php
        if ($login_fabrica == 15) {
            // $jsonPOST = excelPostToJson($_POST);
            $dados_form = array(
                        "data_inicio"  => $xdata_inicial,
                        "data_fim"    => $xdata_final,
                        // "linha"         => $linha_post,
                        // "marca"         => $marca_post,
                        // "regiao"        => $regiao,
                        // "produto_referencia" => $produto_referencia,
                        // "produto_descricao"  => $produto_descricao,
                        // "codigo_posto"       => $codigo_posto,
                        // "descricao_posto"    => $descricao_posto,
                        // "filtrar_por"   => $filtrar_por,
                        "posto"  => $_GET['posto'],
                        "gerar_excel"   => true
                    );
            //print_r ( $dados_form);
            ?>
            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=json_encode($dados_form)?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt"><?php echo traduz("Gerar Excel"); ?></span>
            </div>
        <?php
        }
    } else { ?>
        <div class="container">
            <div class="alert">
                <h4><?php echo traduz("Nenhum resultado encontrado");?></h4>
            </div>
        </div>
    <?php
    }
}


if (isset($res_geral)) {
    if( pg_num_rows($res_geral) > 0 ){?>
        <form name="frm_tab_geral" method="POST" class="form-search form-inline" enctype="multipart/form-data">
            <b class="obrigatorio pull-right">* <?=traduz('Relatório gerado em ').date("d/m/Y"); ?> as <?=date("H:i")?></b>
            <br>
            <?php

            if ($filtro_estado) {
                echo "<p align='center' style='font-size: 12px'>" . traduz('Postos do estado:') . "<b>" .$estados[$filtro_estado]."</b></p>";
            }
            $title_colspan = (!$filtro_estado) ? 8 : 9;

            $title_colspan = ($login_fabrica == 35) ?  9 : $title_colspan;             
            ?>
            <table class='table table-striped table-bordered table-hover table-normal' align='center'>
                <thead>
                    <tr class='titulo_tabela' >
                        <th colspan="<?=$title_colspan?>"><?php echo traduz("Total de OS Abertas sem Lançamento de Peças"); ?></th>
                    </tr>
                    <tr class='titulo_coluna'>
                        <?php if ( !$filtro_estado and in_array($login_fabrica,array(24,74,104)) ){?>
                            <th>UF</th>
                        <?php } ?>
                        <th><?php echo traduz("Código Posto"); ?></th>
                        <th><?php echo traduz("Nome Posto"); ?></th>
                        <?php if($login_fabrica == 35){ ?>
                        <th><?php echo traduz("Estado"); ?></th>
                        <?php } ?>
                        <th>E-mail</th>
                        <th><?php echo traduz("até 5 dias"); ?></th>
                        <?php
                        if($login_fabrica == 6){ ?>
                            <th><?php echo traduz("até 20 dias"); ?></th>
                        <?php
                        } else { ?>
                            <th><?php echo traduz("até 15 dias"); ?></th>
                        <?php
                        }?>
                        <th><?php echo traduz("até 30 dias"); ?></th>
                        <th><?php echo traduz("+ 30 dias"); ?></th>
                        <th><?php echo traduz("Total"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                for ($i = 0 ; $i < pg_num_rows($res_geral) ; $i++) {
                    $posto          = trim(pg_fetch_result($res_geral,$i,'posto'));
                    $nome           = trim(pg_fetch_result($res_geral,$i,'nome'));
                    $estado         = trim(pg_fetch_result($res_geral,$i,'estado'));
                    $codigo_posto   = trim(pg_fetch_result($res_geral,$i,'codigo_posto'));
                    $contato_email  = trim(pg_fetch_result($res_geral,$i,'contato_email'));
                    $qtde_5         = trim(pg_fetch_result($res_geral,$i,'qtde_5'));
                    $qtde_15        = trim(pg_fetch_result($res_geral,$i,'qtde_15'));
                    $qtde_30        = trim(pg_fetch_result($res_geral,$i,'qtde_30'));
                    $qtde_30_mais   = trim(pg_fetch_result($res_geral,$i,'qtde_30_mais'));
                    ?>
                    <tr align='center'>
                        <?php
                        $link_posto = $PHP_SELF."?posto=".$posto."&data_inicio=".$xdata_inicial."&data_fim=".$xdata_final;
                        if (!$filtro_estado and in_array($login_fabrica,array(24,74,104))){?>
                            <td title="<?=$estados[$estado]?>"><?=$estado?></td>
                        <?php
                        }?>
                        <td >
                            <a href='<?=$link_posto?>' target="_blank"><?=$codigo_posto?></a>
                        </td>
                        <td align='left'><?=$nome?></td>
                        <?php if($login_fabrica == 35){ ?>
                            <td align='center' class="tac"><?=$estado?></td>
                        <?php } ?>
                        <td align='left'>
                            <a href="mailto:<?=$contato_email?>"><?=$contato_email?></a>
                        </td>
                        <td><?=$qtde_5?></td>
                        <td><?=$qtde_15?></td>
                        <td style="background-color:#FFCC00"><?=$qtde_30?></td>
                        <td style="background-color:#FF0000">
                            <font color='#FFFFFF'><?=$qtde_30_mais?></font>
                        </td>
                        <?php

                        $total = $qtde_5 + $qtde_15 +$qtde_30 + $qtde_30_mais;
                        $total_qtde_5         += $qtde_5;
                        $total_qtde_15        += $qtde_15;
                        $total_qtde_30        += $qtde_30;
                        $total_qtde_30_mais   += $qtde_30_mais;
                        $total_geral = $total + $total_geral;

                        ?>
                        <td ><?=$total?></td>
                    </tr>
                <?php
                }?>
                </tbody>
                <tfoot>
                    <?php
                    if($login_fabrica==50) { // HD 57319
                    ?>
                        <tr class='Titulo'>
                            <td colspan='3' ><b>Subtotal</b></td>
                            <td ><b><?=$total_qtde_5?></b></td>
                            <td ><b><?=$total_qtde_15?></b></td>
                            <td ><b><?=$total_qtde_30?></b></td>
                            <td ><b><?=$total_qtde_30_mais?></b></td>
                            <td ></td>
                        </tr>
                    <?php
                    }?>
                    <tr class='titulo_coluna'>
                        <td colspan='7' ><b><?=traduz("Total")?></b></td>
                        <td ><b><?=$total_geral?></b></td>
                    </tr>
                    <?php
                    if($login_fabrica==50 OR $login_fabrica==15) {?>
                        <tr>
                            <td colspan='7'>&nbsp;</td>
                            <td >
                                <input type='button' onClick="javascript: enviaEmail();" value='<?php echo traduz("Enviar E-mail"); ?>'>
                            </td>
                    </tr>
                    <?php
                    }?>
                </tfoot>
            </table>
        </form>
        <br />
        <?php
        if ($login_fabrica == 15) {
            $jsonPOST = excelPostToJson($_POST);
            ?>

            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt"><?php echo traduz("Gerar Excel"); ?></span>
            </div>
        <?php
        }
    } else { ?>
        <div class="container">
            <div class="alert">
                <h4><?php echo traduz("Nenhum resultado encontrado"); ?></h4>
            </div>
        </div>
    <?php
    }
}
?>
<br>
<?php
 include "rodape.php" ;
?>
