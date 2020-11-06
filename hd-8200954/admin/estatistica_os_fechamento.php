<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "gerencia";
include "autentica_admin.php";

include "funcoes.php";

$title = "Estatística OS Fechamento";
$layout_menu = "gerencia";

if (isset($_POST['CSV']) && isset($_POST['gerar_excel'])) {
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];
    $origem_fechamento = $_POST["origem_fechamento"];

    $condFechamento = '';
    if ($origem_fechamento == 'mobile') {
        $condFechamento = "AND tbl_os_campo_extra.origem_fechamento = 'mobile'";
    }elseif ($origem_fechamento == 'web') {
        $condFechamento = "AND tbl_os_campo_extra.origem_fechamento IS NULL";
    }

    if ($login_fabrica == 30) {
        $join_esmaltec = "AND tbl_hd_chamado.titulo <> 'Help-Desk Admin'";
    }

    $sql = "SELECT 
                DISTINCT ON(tbl_os.os) tbl_os.os , 
                tbl_os.sua_os , 
                tbl_os.type , 
                tbl_os.cancelada , 
                tbl_os.nota_fiscal , 
                tbl_os.obs, 
                tbl_os.os_numero , 
                tbl_os.admin , 
                sua_os_offline , 
                LPAD(tbl_os.sua_os,20,'0') AS ordem , 
                TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao , 
                TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura , 
                TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento , 
                TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada , 
                TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') as data_nf , 
                tbl_os_produto.serie, 
                tbl_os.consumidor_estado , 
                tbl_os.excluida , 
                tbl_os.motivo_atraso , 
                tbl_os.tipo_os_cortesia , 
                tbl_os.consumidor_revenda , 
                tbl_os.consumidor_nome , 
                tbl_os.consumidor_fone , 
                tbl_os.revenda_nome , 
                tbl_os.tipo_atendimento , 
                tbl_os.os_reincidente AS reincidencia , 
                tbl_os.os_posto , 
                tbl_os.aparencia_produto , 
                tbl_os.tecnico_nome , 
                tbl_os.rg_produto ,
                tbl_os.hd_chamado,
                tbl_os.produto, 
                tbl_hd_chamado_cockpit.dados AS json_kof, 
                tbl_cliente_admin.nome AS cliente_admin_nome, 
                tbl_familia.descricao AS familia_produto, 
                tbl_os_extra.serie_justificativa, 
                tbl_os.consumidor_endereco, 
                tbl_os.consumidor_bairro, 
                tbl_os.consumidor_cep, 
                tbl_os.consumidor_cidade, 
                tbl_routine_schedule_log.file_name AS arquivo_kof, 
                TO_CHAR(tbl_routine_schedule_log.create_at, 'DD/MM/YYYY HH24:MI:SS') AS data_integracao, tbl_os_campo_extra.campos_adicionais, 
                TO_CHAR(tbl_os.exportado, 'DD/MM/YYYY HH24:MI:SS') as exportado, 
                tbl_unidade_negocio.nome AS unidade_negocio, 
                TO_CHAR(tbl_os.data_digitacao, 'YYYY-MM-DD HH24:MI:SS') AS digitacao_hora, 
                TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS digitacao_hora_f, 
                (SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI:SS') FROM tbl_os_modificacao WHERE tbl_os_modificacao.os = tbl_os.os ORDER BY data DESC LIMIT 1) AS ultima_modificacao, 
                tbl_os.data_conserto, 
                distribuidor_principal.descricao AS distribuidor_principal, 
                tbl_defeito_reclamado.descricao AS defeito_reclamado, 
                tbl_os_extra.inicio_atendimento, 
                tbl_os_extra.termino_atendimento, 
                tbl_admin.nome_completo AS admin_nome, 
                regiao_distribuidor_principal.nome AS regiao_distribuidor_principal, 
                ARRAY_TO_STRING(ARRAY( SELECT dc.descricao FROM tbl_os_defeito_reclamado_constatado osdc INNER JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = osdc.defeito_constatado AND dc.fabrica = 158 WHERE osdc.fabrica = 158 AND osdc.os = tbl_os.os AND osdc.defeito_constatado IS NOT NULL ), ', ') AS defeitos_constatados, 
                ARRAY_TO_STRING(ARRAY( SELECT s.descricao FROM tbl_os_defeito_reclamado_constatado oss INNER JOIN tbl_solucao s ON s.solucao = oss.solucao AND s.fabrica = 158 WHERE oss.fabrica = 158 AND oss.os = tbl_os.os AND oss.solucao IS NOT NULL ), ', ') AS solucoes, 
                tbl_tipo_atendimento.descricao , 
                tbl_tipo_atendimento.grupo_atendimento, 
                tbl_posto.posto, 
                tbl_posto_fabrica.codigo_posto , 
                tbl_posto_fabrica.contato_estado , 
                tbl_posto_fabrica.contato_cidade , 
                tbl_posto_fabrica.credenciamento , 
                tbl_posto.nome AS posto_nome , 
                tbl_posto.capital_interior , 
                tbl_posto.estado , 
                tbl_tipo_posto.posto_interno, 
                tbl_os_extra.impressa , 
                tbl_os_extra.obs_adicionais , 
                tbl_os_extra.extrato , 
                tbl_os_extra.os_reincidente , 
                tbl_produto.referencia AS produto_referencia , 
                tbl_produto.descricao AS produto_descricao , 
                tbl_produto.voltagem AS produto_voltagem , 
                tbl_os.status_checkpoint , 
                tbl_status_checkpoint.descricao as status_os,
                distrib.codigo_posto AS codigo_distrib,
                case when tbl_os_campo_extra.origem_fechamento IS NULL
                then 'web'
                else
                tbl_os_campo_extra.origem_fechamento
                end AS origem_fechamento,
                tbl_tipo_posto.descricao as tipo_posto,
                (
                    SELECT c.descricao 
                    FROM tbl_os_defeito_reclamado_constatado odrc
                    INNER JOIN tbl_solucao s ON s.solucao = odrc.solucao AND s.fabrica = {$login_fabrica}
                    INNER JOIN tbl_classificacao c ON c.classificacao = s.classificacao AND c.fabrica = {$login_fabrica}
                    WHERE odrc.os = tbl_os.os
                    ORDER BY c.peso DESC
                    LIMIT 1
                ) AS classificacao 
            FROM tbl_os
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento and tbl_tipo_atendimento.fabrica = {$login_fabrica} 
            JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = {$login_fabrica} 
            LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = {$login_fabrica} 
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.i_fabrica = {$login_fabrica} 
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica and tbl_fabrica.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os and tbl_hd_chamado_extra.posto = tbl_os.posto 
            LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado AND tbl_hd_chamado.titulo <> 'Help-Desk Posto' $join_esmaltec AND tbl_hd_chamado.posto IS NULL 
            LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os 
            LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_distribuidor_sla_posto ON tbl_distribuidor_sla_posto.posto = tbl_posto_fabrica.posto AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_distribuidor_sla ON tbl_os_campo_extra.campos_adicionais LIKE '%unidadeNegocio%' || tbl_distribuidor_sla.unidade_negocio || '%' AND tbl_distribuidor_sla.fabrica = {$login_fabrica} AND tbl_distribuidor_sla.distribuidor_sla = tbl_distribuidor_sla_posto.distribuidor_sla 
            LEFT JOIN tbl_unidade_negocio ON tbl_distribuidor_sla.unidade_negocio = tbl_unidade_negocio.codigo
            LEFT JOIN tbl_posto_distribuidor_sla_default ON tbl_posto_distribuidor_sla_default.posto = tbl_posto_fabrica.posto AND tbl_posto_distribuidor_sla_default.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_distribuidor_sla distribuidor_principal ON distribuidor_principal.distribuidor_sla = tbl_posto_distribuidor_sla_default.distribuidor_sla AND distribuidor_principal.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_cidade regiao_distribuidor_principal ON regiao_distribuidor_principal.cidade = distribuidor_principal.cidade 
            JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_routine_schedule_log ON tbl_routine_schedule_log.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log 
            LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto 
            LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = {$login_fabrica} 
            WHERE tbl_os.fabrica = {$login_fabrica} {$condFechamento}
                AND tbl_os.finalizada between '$data_inicial 00:00:00' and '$data_final 23:59:59'";

    //echo $sql;

    $dataArquivo = date("Ymdhis");
    $arquivo_nome = "EstatisticaFechamento{$dataArquivo}.xls";
    $file     = "xls/{$arquivo_nome}";
    $fileTemp = "/tmp/{$arquivo_nome}";
    $fp     = fopen($fileTemp,'w');

    $head  = "<table>";
    $head .= "<thead>";
    $head .= "<tr>";    
    $head .= "<th>UNIDADE DE NEGÓCIO</th>";
    $head .= "<th>OS</th>";
    $head .= "<th>CLIENTE ADMIN</th>";
    $head .= "<th>PATRIMÔNIO</th>";
    $head .= "<th>SÉRIE</th>";
    $head .= "<th>AB</th>";
    $head .= "<th>DC</th>";        
    $head .= "<th>FC</th>";
    $head .= "<th>DATA ABERTURA KOF</th>";
    $head .= "<th>DATA INÍCIO ATENDIMENTO</th>";
    $head .= "<th>DATA FIM ATENDIMENTO</th>";
    $head .= "<th>C/R</th>";
    $head .= "<th>REGIÃO</th>";
    $head .= "<th>DISTRIBUIDOR</th>";
    $head .= "<th>POSTO</th>";
    $head .= "<th>TIPO DE POSTO</th>";
    $head .= "<th>CEP</th>";
    $head .= "<th>ENDEREÇO</th>";
    $head .= "<th>BAIRRO</th>";
    $head .= "<th>CIDADE</th>";
    $head .= "<th>ESTADO</th>";
    $head .= "<th>CÓDIGO DO CLIENTE</th>";
    $head .= "<th>CLIENTE</th>";
    $head .= "<th>TELEFONE</th>";
    $head .= "<th>TIPO DE ATENDIMENTO</th>";
    $head .= "<th>DEFEITO RECLAMADO</th>";
    $head .= "<th>DEFEITO CONSTATADO</th>";
    $head .= "<th>SOLUÇÃO</th>";
    $head .= "<th>CLASSIFICAÇÃO</th>";
    $head .= "<th>OBSERVAÇÃO</th>";
    $head .= "<th>OBSERVAÇÃO KOF</th>";
    $head .= "<th>ADMIN ÚLTIMA ALTERAÇÃO</th>";
    $head .= "<th>ADMIN FINAL</th>";
    $head .= "<th>FAMÍLIA DO PRODUTO</th>";
    $head .= "<th>STATUS</th>";
    $head .= "<th>NOTA FISCAL</th>";
    $head .= "<th>PRODUTO</th>";
    $head .= "<th>OS KOF</th>";
    $head .= "<th>ARQUIVO ENTRADA</th>";
    $head .= "<th>DATA INTEGRAÇÃO</th>";
    $head .= "<th>ARQUIVO SAÍDA</th>";
    $head .= "<th>DATA SAÍDA</th>";
    //$head .= "<th>SLA</th>";
    $head .= "<th>Origem</th>";
    $head .= "</tr>";
    $head .= "</thead>";
    $head .= "<tbody>";
    fwrite($fp, $head);

    $resxls = pg_query($con, $sql);
    $count = pg_num_rows($resxls);
    for($x = 0; $x < $count; $x++){
        $cor                = "";
        $sua_os             = "";
        $hd_chamado         = "";
        $numero_ativo_res   = "";
        $nota_fiscal        = "";
        $digitacao          = "";
        $abertura           = "";
        $consumidor_revenda = "";
        $fechamento         = "";
        $finalizada         = "";
        $data_conserto      = "";
        $serie              = "";
        $consumidor_nome    = "";
        $consumidor_fone    = "";
        $codigo_posto       = "";
        $posto_nome         = "";
        $produto_referencia = "";
        $produto_descricao  = "";
        $produto_voltagem   = "";
        $marca_logo_nome    = "";
        $situacao_posto     = "";
        $data_nf            = "";
        $cidade_uf          = "";
        $consumidor_cidade  = "";
        $tipo_posto         = "";
        $classificacao      = "";

        $cor   = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
        $os                 = pg_fetch_result($resxls,$x,os);
        $sua_os             = pg_fetch_result($resxls,$x,sua_os);
        $hd_chamado         = pg_fetch_result($resxls,$x,hd_chamado);
        $cidade_posto_xls   = pg_fetch_result($resxls,$x,contato_cidade);
        $estado_posto_xls   = pg_fetch_result($resxls,$x,contato_estado);
        $cidade_uf          = $cidade_posto_xls."/".$estado_posto_xls;
        $nota_fiscal        = pg_fetch_result($resxls,$x,nota_fiscal);
        $digitacao          = pg_fetch_result($resxls,$x,digitacao);
        $abertura           = pg_fetch_result($resxls,$x,abertura);
        $consumidor_revenda = pg_fetch_result($resxls,$x,consumidor_revenda);
        $fechamento         = pg_fetch_result($resxls,$x,fechamento);
        $finalizada         = pg_fetch_result($resxls,$x,finalizada);
        $data_conserto      = @pg_fetch_result($resxls,$x,data_conserto);
        $serie              = pg_fetch_result($resxls,$x,serie);
        $type               = pg_fetch_result($resxls,$x,type);
        $reincidencia       = pg_fetch_result($resxls,$x,reincidencia);
        $consumidor_nome    = pg_fetch_result($resxls,$x,consumidor_nome);
        $excluida           = pg_fetch_result($resxls,$x,excluida);
        $consumidor_fone    = pg_fetch_result($resxls,$x,consumidor_fone);
        $data_nf            = pg_fetch_result($resxls,$x,data_nf);
        $codigo_posto       = pg_fetch_result($resxls,$x,codigo_posto);
        $posto_nome         = pg_fetch_result($resxls,$x,posto_nome);
        $produto_referencia = pg_fetch_result($resxls,$x,produto_referencia);
        $status_os          = pg_fetch_result($resxls,$x,status_os);
        $produto_descricao  = pg_fetch_result($resxls,$x,produto_descricao);
        $produto_voltagem   = pg_fetch_result($resxls,$x,produto_voltagem);
        $status_checkpoint  = pg_fetch_result($resxls,$x,status_checkpoint);
        $marca_logo         = pg_fetch_result($resxls,$x,marca);
        $situacao_posto     = pg_fetch_result($resxls,$x,credenciamento);
        $revenda_nome       = pg_fetch_result($resxls,$x,revenda_nome);
        $obs                = pg_fetch_result($resxls,$x,obs);
        $consumidor_endereco            = pg_fetch_result($resxls,$x, consumidor_endereco);
        $consumidor_numero              = pg_fetch_result($resxls,$x, consumidor_numero);
        $consumidor_estado              = pg_fetch_result($resxls,$x, consumidor_estado);
        $consumidor_cidade              = pg_fetch_result($resxls,$x, consumidor_cidade);
        $nome_consumidor_revenda = ($consumidor_revenda == "C" || empty($consumidor_revenda)) ? $consumidor_nome : $revenda_nome;
        $consumidor_email    = pg_fetch_result($resxls,$x,consumidor_email);
        $revenda_cnpj_tec    = pg_fetch_result($resxls,$x,revenda_cnpj);
        $revenda_nome_tec    = pg_fetch_result($resxls,$x,revenda_nome);
        $json_kof                      = pg_fetch_result($resxls, $x, "json_kof");
        $json_kof                      = json_decode($json_kof, true);
        $cliente_admin_nome            = pg_fetch_result($resxls, $x, "cliente_admin_nome");
        $familia_produto               = pg_fetch_result($resxls, $x, "familia_produto");
        $serie_justificativa           = pg_fetch_result($resxls, $x, "serie_justificativa");
        $consumidor_endereco           = pg_fetch_result($resxls, $x, "consumidor_endereco");
        $consumidor_bairro             = pg_fetch_result($resxls, $x, "consumidor_bairro");
        $consumidor_cep                = pg_fetch_result($resxls, $x, "consumidor_cep");
        $arquivo_kof                   = pg_fetch_result($resxls, $x, "arquivo_kof");
        $data_integracao               = pg_fetch_result($resxls, $x, "data_integracao");
        $campos_adicionais             = pg_fetch_result($resxls, $x, "campos_adicionais");
        $campos_adicionais             = json_decode($campos_adicionais, true);
        $exportado                     = pg_fetch_result($resxls, $x, "exportado");
        $descricao_tipo_atendimento    = pg_fetch_result($resxls, $x, "descricao");
        $unidade_negocio               = pg_fetch_result($resxls, $x, "unidade_negocio");
        $distribuidor_principal        = pg_fetch_result($resxls, $x, "distribuidor_principal");
        $defeito_reclamado             = pg_fetch_result($resxls, $x, "defeito_reclamado");
        $inicio_atendimento            = pg_fetch_result($resxls, $x, "inicio_atendimento");
        $termino_atendimento           = pg_fetch_result($resxls, $x, "termino_atendimento");
        $admin_nome                    = pg_fetch_result($resxls, $x, "admin_nome");
        $regiao_distribuidor_principal = pg_fetch_result($resxls, $x, "regiao_distribuidor_principal");
        $defeitos_constatados          = pg_fetch_result($resxls, $x, "defeitos_constatados");
        $solucoes                      = pg_fetch_result($resxls, $x, "solucoes");
        $tempo_para_defeito            = pg_fetch_result($resxls,$x,tempo_para_defeito);
        $data_saida                    = pg_fetch_result($resxls, $x, "exportado");        
        //$sla = pg_fetch_result($resxls, $x, "SLA");
        $origem_fechamento             = pg_fetch_result($resxls, $x, "origem_fechamento");
        $tipo_posto                    = pg_fetch_result($resxls, $x, "tipo_posto");
        $classificacao                 = pg_fetch_result($resxls, $x, "classificacao");

        $body  = "<tr>";        
        $body .= "<td>{$unidade_negocio}</td>";
        $body .= "<td>{$sua_os}</td>";
        $body .= "<td>{$cliente_admin_nome}</td>";
        $body .= "<td>{$serie_justificativa}</td>";
        $body .= "<td>{$serie}</td>";
        $body .= "<td>{$abertura}</td>";
        $body .= "<td>{$data_conserto}</td>";
        $body .= "<td>{$fechamento}</td>";
        $body .= "<td>{$json_kof['dataAbertura']}</td>";
        $body .= "<td>{$inicio_atendimento}</td>";
        $body .= "<td>{$termino_atendimento}</td>";
        switch ($consumidor_revenda) {
        case "C":
            $body .= "<td>CONS</td>";
            break;

        case "R":
            $body .= "<td>REV</td>";
            break;

        case "":
            $body .= "<td>&nbsp;</td>";
            break;
        }                
        $body .= "<td>{$regiao_distribuidor_principal}</td>";
        $body .= "<td>{$distribuidor_principal}</td>";
        $body .= "<td>{$codigo_posto} - {$posto_nome}</td>";
        $body .= "<td>{$tipo_posto}</td>";
        $body .= "<td>{$consumidor_cep}</td>";
        $body .= "<td>{$consumidor_endereco}</td>";
        $body .= "<td>{$consumidor_bairro}</td>";
        $body .= "<td>{$consumidor_cidade}</td>";
        $body .= "<td>{$consumidor_estado}</td>";
        $body .= "<td>{$json_kof['idCliente']}</td>";
        $body .= "<td>{$nome_consumidor_revenda}</td>";
        $body .= "<td>{$consumidor_fone}</td>";
        $body .= "<td>{$descricao_tipo_atendimento}</td>";
        $body .= "<td>{$defeito_reclamado}</td>";
        $body .= "<td>{$defeitos_constatados}</td>";
        $body .= "<td>{$solucoes}</td>";
        $body .= "<td>{$classificacao}</td>";
        $body .= "<td>{$obs}</td>";
        $body .= "<td>{$json_kof['comentario']}</td>";
        $body .= "<td>{$admin_nome}</td>";
        $body .= "<td>{$admin_nome}</td>";
        $body .= "<td>{$familia_produto}</td>";        
        $body .= "<td>{$status_os}</td>";
        $body .= "<td>{$nota_fiscal}</td>";
        $body .= "<td>{$produto_referencia} - {$produto_descricao}</td>";
        $body .= "<td>{$json_kof['osKof']}</td>";        
        $body .= "<td>{$arquivo_kof}</td>";
        $body .= "<td>{$data_integracao}</td>";
        $body .= "<td>{$campos_adicionais['arquivo_saida_kof']}</td>";
        $body .= "<td>{$data_saida}</td>";
        //$body .= "<td>{$sla}</td>";        
        $body .= "<td>{$origem_fechamento}</td>";
        $body .= "</tr>";
        fwrite($fp, $body);
    }
    $body .= "</tbody></table>";
    fclose($fp);
    if(file_exists($fileTemp)){
        system("mv $fileTemp $file");

        if(file_exists($file)){
            echo $file;
        }
    }
    exit;    
}

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    $data_inicial    = $_POST['data_inicial'];
    $data_final      = $_POST['data_final'];
    $origem_fechamento = $_POST['origem_fechamento'];

    $condFechamento = '';
    if ($origem_fechamento == 'mobile') {
        $condFechamento = "AND tbl_os_campo_extra.origem_fechamento = 'mobile'";
        $tituloTabela = 'Mobile';
    }elseif ($origem_fechamento == 'web') {
        $condFechamento = "AND tbl_os_campo_extra.origem_fechamento IS NULL";
        $tituloTabela = 'Web';
    }

    $sql = "SELECT 
                count(*) AS total,
                tbl_os_campo_extra.origem_fechamento AS origem
            FROM tbl_os 
                LEFT JOIN tbl_os_campo_extra ON(tbl_os.os = tbl_os_campo_extra.os) AND tbl_os_campo_extra.fabrica = {$login_fabrica}
                JOIN tbl_hd_chamado_cockpit ON(tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica} ) 
            WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.posto <> 6359 
                AND tbl_os.finalizada BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
                {$condFechamento}
            GROUP BY tbl_os_campo_extra.origem_fechamento ORDER BY tbl_os_campo_extra.origem_fechamento";

    $res = pg_query($con, $sql);
    $count = pg_num_rows($res);
    if ($count > 0) {
        $table  = '<table class="table table-bordered">';
        $table .= '<theady>';
        $table .= '<tr class="titulo_coluna">';
        if ($origem_fechamento == "") {
            $table .= '<th>Mobile</th>';
            $table .= '<th>Web</th>';
        }else{
            $table .= "<th>{$tituloTabela}</th>";
        }
        $table .= '</tr>';
        $table .= '</theady>';
        $table .= '<tbody>';
        $table .= '<tr>';
        if ($origem_fechamento == "") {
            $table .= '<td>'.pg_fetch_result($res, 0, "total").'</td>';
            $table .= '<td>'.pg_fetch_result($res, 1, "total").'</td>';            
        }else{
            $table .= '<td>'.pg_fetch_result($res, 0, "total").'</td>';
        }
        $table .= '</tr>';
        $table .= '</tbody>';
        $table .= '</table>';

        $parametros = json_encode(
                        array(
                            'CSV' => '1', 
                            'data_inicial' => $data_inicial, 
                            'data_final' => $data_final, 
                            'tipo_fechamento' => $tipo_fechamento
                        )
                    );
        exit(json_encode(array("ok" => utf8_encode($table), "json" => utf8_encode($parametros))));
    }
    exit(json_encode(array('nenhum' => utf8_encode('Não foi encontrado nenhum registro com este filtro!'))));
}

include "cabecalho_new.php";

$plugins = array(
    "select2",
    "mask"
);

include "plugin_loader.php";
?>
<form method="POST" class="form-search form-inline tc_formulario no-print" id="formPesquisa">
    <div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>
    <div id="Alerta" class="alert" style="display: none;"><h4></h4></div>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span3">
            <div class="control-group" id="gDtInicial">
                <label class="control-label" for="data_inicial">Data Inicial</label>
                <div class="controls controls-row">
                    <div class="span8  ">
                        <h5 class="asteristico">*</h5>
                        <input id="data_inicial" name="data_inicial" class="span12 " value="" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group" id="gDtFinal">
                <label class="control-label" for="data_final">Data Final</label>
                <div class="controls controls-row">
                    <div class="span8  ">
                        <h5 class="asteristico">*</h5>
                        <input id="data_final" name="data_final" class="span12 " value="" type="text">
                    </div>
                </div>
            </div>            
        </div>
        <div class="span4">
            <div class="control-group" id="gTpFechamento">
                <label class="control-label" for="origem_fechamento">Origem do fechamento</label>
                <div class="controls controls-row">
                    <div class="span8 ">
                        <select id="origem_fechamento" name="origem_fechamento" class="span12 ">
                            <option value=""></option>
                            <option value="mobile">Mobile</option>
                            <option value="web">Web</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>        
    </div>    
    </br>
    <center>
        <input type="button" class="btn btn-primary" value="Pesquisar" name='bt_cad_forn' id='bt_cad_forn'>
        <input type="button" class="btn btn-warning" name="Limpar" value="Limpar" id="btnLimpar"/>
        <div id="loading" style="display: none;"><img src="imagens/ajax-azul.gif" /></div>
    </center>
    <br>
</form>
<div class="row-fluid">
    <div class="span12" id="resultPesquisa">        
    </div>
</div>
<center>
    <div id='gerar_excel' class='btn_excel' style="display: none;">
        <input type='hidden' id='jsonPOST' value=''/>
        <span><img src='imagens/excel.png'/></span>
        <span class='txt'>Gerar Arquivo Excel</span>
    </div>
</center>
<?php include "rodape.php"; ?>
<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
});

$('#bt_cad_forn').on('click', function(){
    limpar();
    var data_inicial = $('#data_inicial').val();
    var data_final = $('#data_final').val();
    var tipo_fechamento = $('#tipo_fechamento').val();

    var erro_msg = '';
    if (data_inicial == '') {
        erro_msg = 'Preencha os dados obrigatórios<br />';
        $('#gDtInicial').addClass('error');
    }
    if (data_final == '') {
        erro_msg = 'Preencha os dados obrigatórios<br />';
        $('#gDtFinal').addClass('error');
    }
   
    /* Verifica se a data inicial é maior */
    var data_1 = new Date(data_inicial.replace(/(\d{2})\/(\d{2})\/(\d{4})/,'$2/$1/$3'));
    var data_2 = new Date(data_final.replace(/(\d{2})\/(\d{2})\/(\d{4})/,'$2/$1/$3'));
    if (data_1 == "Invalid Date" || data_1 == null) {
        erro_msg += 'Data inicial não é uma data válida';
        $('#gDtInicial').addClass('error');
    }else if (data_2 == "Invalid Date" || data_2 == null) {
        erro_msg += 'Data final não é uma data válida';
        $('#gDtFinal').addClass('error');
    }else{
        if (data_1 > data_2) {
            erro_msg += 'Data inicial não pode ser maior que a data final';
            $('#gDtFinal').addClass('error');
            $('#gDtInicial').addClass('error');
        }
    }
    if (erro_msg !== '') {
        $('#alertaErro').show().find('h4').html(erro_msg);
    }else{     
        var dataPost = $('#formPesquisa').serialize()+'&ajax=sim';
        $.ajax({
            url: window.location,
            method: 'POST',
            data: dataPost,
            timeout: 60000,
            beforeSend: function(){
                $('#loading').show();
            }            
        }).fail(function(){
            $('#alertaErro').show().find('h4').html('Não foi possível concluir a pesquisa, tempo esgotado!');
            $('#loading').hide();
        }).done(function(data){
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $('#resultPesquisa').html(data.ok);
                $('#jsonPOST').attr('value', data.json);
                $('#gerar_excel').show();
            }else if (data.nenhum !== undefined) {
                $('#Alerta').show().find('h4').html(data.nenhum);
            }
            $('#loading').hide();
        });
    }
});

$('#btnLimpar').on('click',function(){
    $('#formPesquisa').each(function(){
        this.reset();        
    });
    limpar();
});

function limpar(){
    $('#gDtInicial').removeClass('error');
    $('#gDtFinal').removeClass('error');
    $('#alertaErro').hide();
    $('#Alerta').hide();
    $('#gerar_excel').hide();
    $('#resultPesquisa').html('');
}
</script>
