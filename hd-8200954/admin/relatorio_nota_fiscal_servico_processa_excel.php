<?php

/**
 * - Arquivo para geração das informações do relatório
 * para geração de arquivo de planilha e autoabertura
 *
 * @author William Ap. Brandino
 */
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (filter_input(INPUT_POST,'download',FILTER_VALIDATE_BOOLEAN)) {
    $data_inicial   = filter_input(INPUT_POST,'data_inicial');
    $data_final     = filter_input(INPUT_POST,'data_final');
    $codigo_posto   = filter_input(INPUT_POST,'codigo_posto');
    $status         = filter_input(INPUT_POST,'status');
    $centro_distribuicao = filter_input(INPUT_POST,'centro_distrib');    

    list($d,$m,$y)      = explode("/",$data_inicial);
    list($dd,$mm,$yy)   = explode("/",$data_final);

    $inicial_data   = $y."-".$m."-".$d;
    $final_data     = $yy."-".$mm."-".$dd;

    if (!empty($codigo_posto)) {
        $cond_posto = " AND tbl_posto_fabrica.codigo_posto = '".$codigo_posto."'";
    }

    $cond_status = ($status == "pendentes" || $status == "")
        ? " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL AND tbl_extrato.nf_recebida IS NOT TRUE "
        : " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL AND tbl_extrato.nf_recebida IS TRUE ";

    /*
     * - Guarda numa TEMPORÁRIA, todos os extratos
     * buscados pelos parâmetros
     */

    if($login_fabrica == 151){
        if($centro_distribuicao != "mk_vazio"){
            $campo_p_adicionais = ",tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao";
            $p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
            $distinct_P_adicionais = " DISTINCT ";
            $join_p_adicionais = " JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica}";
        }

        $campo_agrupado = ", tbl_extrato_agrupado.codigo";
        $join_agrupado  = " LEFT JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato";            
    }

    $sql = "SELECT  tbl_extrato.extrato,
                    tbl_posto.posto,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
                    tbl_extrato.total,
                    tbl_extrato.previsao_pagamento,
                    tbl_extrato_pagamento.serie_nf,
                    tbl_extrato_pagamento.nf_autorizacao
                    $campo_agrupado
       INTO TEMP    tmp_extrato
            FROM    tbl_extrato
            JOIN    tbl_posto ON tbl_posto.posto = tbl_extrato.posto
            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN    tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
            $join_agrupado
            WHERE   tbl_extrato.data_geracao BETWEEN '$inicial_data 00:00:00' AND '$final_data 23:59:59'
            AND     tbl_extrato.fabrica = {$login_fabrica}
                    {$cond_status}
                    {$cond_posto}";
    
    $res = pg_query($con, $sql);

    /*
     * - Usa a temporária para buscar
     * Cada OS e suas respectivas PEÇAS e VALORES
     * de custo e mão-de-obra
     */

    if (in_array($login_fabrica, [151])) {
        $sqlExt = "
            SELECT {$distinct_P_adicionais}
                tbl_os.os,
                tmp_extrato.extrato,
                tbl_os.sua_os,
                (tmp_extrato.codigo_posto || ' - ' || tmp_extrato.nome) AS posto,
                tmp_extrato.data_geracao,
                tmp_extrato.total,
                tmp_extrato.serie_nf,
                tmp_extrato.codigo,
                tmp_extrato.nf_autorizacao,
                CASE WHEN tbl_os.consumidor_revenda = 'C'
                    THEN 'Consumidor'
                    ELSE 'Revenda'
                END AS consumidor_revenda,
                tbl_os.mao_de_obra
                {$campo_p_adicionais}
            FROM tbl_os_extra
            JOIN tmp_extrato ON tmp_extrato.extrato = tbl_os_extra.extrato
            JOIN tbl_os ON tbl_os.os = tbl_os_extra.os
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
            {$join_p_adicionais}
            WHERE tbl_fabrica.fabrica = {$login_fabrica}
            AND tbl_os_extra.extrato IN (SELECT extrato FROM tmp_extrato)
            {$p_adicionais}
        ";
    } else {
        $sqlExt = "
            SELECT  tmp_extrato.extrato,
                    tbl_os.os,
                    tbl_os.sua_os,
                    CASE WHEN tbl_os.consumidor_revenda = 'C'
                         THEN 'Consumidor'
                         ELSE 'Revenda'
                    END AS consumidor_revenda,
                    tbl_os.mao_de_obra,
                    tbl_peca.peca,
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_os_item.qtde,
                    (tbl_os_item.qtde * tbl_os_item.custo_peca) AS valor_peca
       INTO TEMP    tmp_os_pecas
            FROM    tbl_os
            JOIN    tbl_os_extra    USING (os)
            JOIN    tbl_os_produto  USING (os)
            JOIN    tbl_os_item     USING (os_produto)
            JOIN    tbl_peca        USING (peca)
            JOIN    tmp_extrato     USING (extrato)
            WHERE   tbl_os.fabrica = $login_fabrica
        ";
    }

    $resExt = pg_query($con,$sqlExt);

    /*
     * - Monta o arquivo da
     * planilha e prepara para o download
     */

    if (!in_array($login_fabrica, [151])) {
        $sqlExcel = "
            SELECT  tmp_extrato.extrato,
                    (tmp_extrato.codigo_posto || ' - ' || tmp_extrato.nome) AS posto,
                    tmp_extrato.data_geracao,
                    tmp_extrato.total,
                    tmp_extrato.nf_autorizacao,
                    tmp_os_pecas.sua_os,
                    tmp_os_pecas.consumidor_revenda,
                    tmp_os_pecas.mao_de_obra,
                    (tmp_os_pecas.referencia || ' - ' || tmp_os_pecas.descricao) AS peca,
                    tmp_os_pecas.qtde,
                    tmp_os_pecas.valor_peca
            FROM    tmp_extrato
            JOIN    tmp_os_pecas USING(extrato)
        ORDER BY    tmp_extrato.extrato,
                    tmp_os_pecas.os

        ";

        $resExcel = pg_query($con,$sqlExcel);
    }

    $res = (in_array($login_fabrica, [151])) ? $resExt : $resExcel;

    $resultados = pg_fetch_all($res);    

    $tabela = "<table>";

    $extratoAntigo = "";    

    foreach ($resultados as $linha => $valor){        
        if ($valor['extrato'] != $extratoAntigo) {
            if($extratoAntigo != ""){
                $tabela .= "<tr><td colspan='5'></td></tr>";
            }
            $tabela .= "
                <tr>";

            if ($login_fabrica == 151) {
                $tabela .= "<th>Extrato Agrupado</th>";
            }

            $tabela .="
                    <th>Extrato</th>
                    <th>Posto</th>
                    <th>Data Geração</th>
                    <th>Total Extrato</th>
                    <th>NF Serviço</th>";
                    if($login_fabrica == 151) {
                        $tabela .= "<th>Série</th>";
                        $tabela .= "<th>Centro Distribuicao</th>";
                    }

            $tabela .= "</tr>

                <tr>";

                if ($login_fabrica == 151) {
                    $tabela .= "<td>".$valor['codigo']."</td>";
                }

            $tabela .="
                    <td>".$valor['extrato']."</td>
                    <td text-align='center'>".$valor['posto']."</td>
                    <td>".$valor['data_geracao']."</td>
                    <td text-align='right'>R$ ".number_format($valor['total'],2,',','')."</td>
                    <td>".$valor['nf_autorizacao']."</td>";
                    if($login_fabrica == 151) {
                        $tabela .= "<td>".$valor['serie_nf']."</td>";
                        $cd = $valor['centro_distribuicao'];
                        if($cd == "mk_nordeste"){
                            $tabela .= "<td>MK Nordeste</td>";
                        }else if($cd == "mk_sul") {
                            $tabela .= "<td>MK Sul</td>";    
                        } else{
                            $tabela .= "<td>&nbsp;</td>";    
                        }                        
                    }

                $tabela .= "</tr>

                <tr>
                    <th>OS</th>
                    <th>REVENDA / CONSUMIDOR</th>
                    <th>Mão-de-Obra</th>
            ";

            if (!in_array($login_fabrica, [151])) {
                $tabela .= "<th>Peça</th>
                    <th>Qtde</th>
                    <th>Valor Peça</th>
                ";
            }
            $tabela .= "</tr>";
            $extratoAntigo = $valor['extrato'];
        }

        $tabela .= "
            <tr>
                <td>".$valor['sua_os']."</td>
                <td>".$valor['consumidor_revenda']."</td>
                <td text-align='right'>R$ ".number_format($valor['mao_de_obra'],2,',','')."</td>
        ";

        if (!in_array($login_fabrica, [151])) {
            $tabela .= "
                <td text-align='center'>".$valor['peca']."</td>
                <td>".$valor['qtde']."</td>
                <td text-align='right'>R$ ".number_format($valor['valor_peca'],2,',','')."</td>
            ";
        }

        $tabela .= "</tr>";
    }
    $tabela .= "</table>";
    $caminho = "/tmp/".lcfirst($login_fabrica_nome)."/"; 
    #$caminho = "/home/gaspar/public_html/PosVendaAssist/teste/";
    $nome = "relatorio_nota_fiscal_servico_".date('Ymd_his').".xls";
    if (file_put_contents($caminho.$nome, $tabela)) {
        echo "<a href='relatorio_nota_fiscal_servico_abre_excel.php?file=".$nome."' id='link'>".$nome."</a>";        
    } else {
        echo "erro";
    }
    exit;
}
