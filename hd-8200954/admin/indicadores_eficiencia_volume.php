<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'');

if ($areaAdminCliente == true) {
    include_once "../dbconfig.php";
    include_once "../includes/dbconnect-inc.php";
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    if (!defined('CA_APP_PATH')) {
        include_once "dbconfig.php";
        include_once "includes/dbconnect-inc.php";
        include "autentica_admin.php";
        include_once "funcoes.php";
    }
}

use Posvenda\DistribuidorSLA;
$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);
$title = "Indicadores SLA/Reincidência";
$layout_menu = "gerencia";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {
    $tipo_busca = $_GET["busca"];

    if (strlen($q) > 3) {
        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

        if ($tipo_busca == "codigo"){
            $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
        }else{
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $sql .= " LIMIT 50 ";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            for ($i = 0; $i < pg_num_rows($res); $i++ ) {
                $cnpj = trim(pg_result($res,$i,cnpj));
                $nome = trim(pg_result($res,$i,nome));
                $codigo_posto = trim(pg_result($res,$i,codigo_posto));
                echo "$codigo_posto|$nome|$cnpj";
                echo "\n";
            }
        }
    }
    exit;
}

if (isset($_POST['CSV']) && isset($_POST['gerar_excel'])) {
  
    $data_inicial     = $_POST["data_inicial"];
    $data_final       = $_POST["data_final"];

    $data_inicial = formata_data($data_inicial);
    $data_final = formata_data($data_final);

    $tipo_atendimento = $_POST["tipo_atendimento"];
    $posto            = $_POST['posto'];
    $unidade_negocio  = $_POST['unidade_negocio'];
    $unidade_negocio  = implode("|", $unidade_negocio);


    $cond_cliente_admin = ($areaAdminCliente == true)? " AND tbl_os.cliente_admin = $login_cliente_admin " : "" ;

    $areaAdminCliente = ($unidade_negocio == "6300" AND $areaAdminCliente !== true) ? true : $areaAdminCliente;
    $cond_posto = "";
    if (strlen($posto) > 0) {
        $cond_posto = " AND tbl_os.posto = $posto ";
    }
    $cond = "";
    if(!empty($tipo_atendimento)) {
        $cond = " AND tbl_os.tipo_atendimento IN (".implode(",", $tipo_atendimento).")";
    }
    if (strlen($unidade_negocio) > 0) {
        $whereUnid = "AND JSON_FIELD('unidadeNegocio',T_OSE.campos_adicionais) ~ '{$unidade_negocio}'";
    }

    if ($login_fabrica == 30) {
        $join_esmaltec = "AND tbl_hd_chamado.titulo <> 'Help-Desk Admin'";
    }

    if ($login_fabrica == 158) {
      $cond_data = "AND SCHED_LOG.create_at BETWEEN '$data_inicial 00:00' AND '$data_final 23:59' ";
    } else {
      $cond_data = "AND tbl_os.data_fechamento BETWEEN '$data_inicial' AND '$data_final' ";
    }
	$distinct = "DISTINCT ON(tbl_os.os)";

    if ($login_fabrica == 158) {
      $cond_hd_chamado = " AND tbl_os.hd_chamado IS NOT NULL ";
      $primeiro_left =  " LEFT JOIN tbl_distribuidor_sla_posto
                                    ON tbl_distribuidor_sla_posto.posto = tbl_posto_fabrica.posto
                                    AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
                          LEFT JOIN tbl_distribuidor_sla
                                    ON tbl_distribuidor_sla.unidade_negocio = JSON_FIELD('unidadeNegocio', T_OSE.campos_adicionais)
                                    AND tbl_distribuidor_sla.fabrica = {$login_fabrica}
                          LEFT JOIN tbl_unidade_negocio 
                                    ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio ";
      

    	$order_by = "ORDER BY tbl_distribuidor_sla.unidade_negocio";
    	$distinct = "";
    	$subselect_inicio = "SELECT DISTINCT ON(x.os) x.* FROM (";
    	$subselect_fim = ") x ORDER BY x.os, x.unidade_negocio";

    } else {
      $primeiro_left = " JOIN tbl_distribuidor_sla_posto
                              ON tbl_distribuidor_sla_posto.posto = tbl_posto_fabrica.posto
                              AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
                         JOIN tbl_distribuidor_sla
                              ON T_OSE.campos_adicionais LIKE '%unidadeNegocio%' || tbl_distribuidor_sla.unidade_negocio || '%'
                              AND tbl_distribuidor_sla.fabrica = {$login_fabrica}
                        JOIN tbl_cidade ON tbl_cidade.cidade = tbl_distribuidor_sla.cidade ";
     
    }

    if ($areaAdminCliente == true) {
        $sql = "$subselect_inicio
		SELECT $distinct
                tbl_os.os,
                tbl_os.sua_os,
                tbl_os.type,
                tbl_os.cancelada,
                tbl_os.nota_fiscal,
                tbl_os.obs,
                tbl_os.os_numero,
                tbl_os.admin,
                sua_os_offline,
                LPAD(tbl_os.sua_os,                20, '0')                 AS ordem,
                TO_CHAR(tbl_os.data_digitacao,     'DD/MM/YYYY H24:MI:SS')            AS digitacao,
                TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS abertura,
                TO_CHAR(tbl_os.data_fechamento,    'DD/MM/YYYY')            AS fechamento,
                TO_CHAR(tbl_os.finalizada,         'DD/MM/YYYY')            AS finalizada,
		TO_CHAR(tbl_os.data_nf,            'DD/MM/YYYY')            AS data_nf,
		tbl_os.data_abertura,
                tbl_os_produto.serie,
                tbl_os.consumidor_estado,
                tbl_os.excluida,
                tbl_os.motivo_atraso,
                tbl_os.tipo_os_cortesia,
                tbl_os.consumidor_revenda,
                tbl_os.consumidor_nome,
                tbl_os.consumidor_fone,
                tbl_os.revenda_nome,
                tbl_os.tipo_atendimento,
                tbl_os.os_reincidente AS reincidencia,
                tbl_os.justificativa_adicionais,
                tbl_os.os_posto,
                tbl_os.aparencia_produto,
                tbl_os.tecnico_nome,
                tbl_os.rg_produto,
                tbl_os.hd_chamado,
                tbl_os.produto,
                tbl_hd_chamado_cockpit.dados AS json_kof,
                tbl_cliente_admin.nome       AS cliente_admin_nome,
                tbl_familia.descricao        AS familia_produto,
                tbl_os_extra.serie_justificativa,
                tbl_os.consumidor_endereco,
                tbl_os.consumidor_bairro,
                tbl_os.consumidor_cep,
                tbl_os.consumidor_cidade,
                SCHED_LOG.file_name AS arquivo_kof,
                T_OSE.campos_adicionais,
                tbl_distribuidor_sla.unidade_negocio,
                TO_CHAR(tbl_os.data_digitacao,   'DD/MM/YYYY HH24:MI:SS') AS data_integracao,
                TO_CHAR(tbl_os.exportado,      'DD/MM/YYYY HH24:MI:SS') as exportado,
                TO_CHAR(tbl_os.data_digitacao, 'YYYY-MM-DD HH24:MI:SS') AS digitacao_hora,
                TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS digitacao_hora_f,
                (
                  SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI:SS')
                    FROM tbl_os_modificacao
                   WHERE tbl_os_modificacao.os = tbl_os.os
                ORDER BY data DESC LIMIT 1)      AS ultima_modificacao,
                tbl_os.data_conserto,
                distribuidor_principal.descricao AS distribuidor_principal,
                tbl_defeito_reclamado.descricao  AS defeito_reclamado,
                tbl_os_extra.inicio_atendimento,
                tbl_os_extra.termino_atendimento,
                tbl_admin.nome_completo AS admin_nome,
                regiao_distribuidor_principal.nome AS regiao_distribuidor_principal,
                ARRAY_TO_STRING(ARRAY(
                    SELECT dc.descricao
                      FROM tbl_os_defeito_reclamado_constatado osdc
                      JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = osdc.defeito_constatado
                                                    AND dc.fabrica = {$login_fabrica}
                     WHERE osdc.fabrica = {$login_fabrica}
                       AND osdc.os      = tbl_os.os
                       AND osdc.defeito_constatado IS NOT NULL
                  ORDER BY osdc.defeito_constatado_reclamado DESC
                     LIMIT 1), ', ')                                   AS defeitos_constatados,
                ARRAY_TO_STRING(ARRAY(
                    SELECT iddc.defeito_constatado
                      FROM tbl_os_defeito_reclamado_constatado iddc
                     WHERE iddc.fabrica = {$login_fabrica}
                       AND iddc.os      = tbl_os.os
                       AND iddc.defeito_constatado IS NOT NULL), ', ') AS ids_defeitos_constatados,
                ARRAY_TO_STRING(ARRAY(
                    SELECT s.descricao
                      FROM tbl_os_defeito_reclamado_constatado oss
                      JOIN tbl_solucao s
                        ON s.solucao = oss.solucao
                       AND s.fabrica = {$login_fabrica}
                      JOIN tbl_classificacao AS classificacao
                        ON classificacao.classificacao = s.classificacao
                       AND classificacao.fabrica       = $login_fabrica
                     WHERE oss.fabrica =  {$login_fabrica}
                       AND oss.os      =  tbl_os.os
                       AND oss.solucao IS NOT NULL
                  ORDER BY classificacao.peso DESC LIMIT 1 ), ', ') AS solucoes,
                T_TA.descricao,
                T_TA.grupo_atendimento,
                tbl_posto.posto,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.contato_estado,
                tbl_posto_fabrica.contato_cidade,
                tbl_posto_fabrica.credenciamento,
                tbl_posto.nome AS posto_nome,
                tbl_posto.capital_interior,
                tbl_posto.estado,
                tbl_tipo_posto.posto_interno,
                tbl_os_extra.impressa,
                tbl_os_extra.obs_adicionais,
                tbl_os_extra.extrato,
                tbl_os_extra.os_reincidente,
                tbl_produto.referencia AS produto_referencia,
                tbl_produto.descricao AS produto_descricao,
                tbl_produto.voltagem AS produto_voltagem,
                tbl_os.status_checkpoint,
                T_CHKPS.descricao as status_os,
                distrib.codigo_posto AS codigo_distrib,
                CASE
                    WHEN tbl_familia.descricao =  'REFRIGERADOR'
                      OR tbl_familia.descricao   =  'VENDING MACHINE'
                    THEN
                        CASE
                            WHEN (
                                 SELECT COUNT(1)
                                   FROM fn_calendario(tbl_os.data_digitacao::DATE, tbl_os_extra.termino_atendimento::DATE )
                                  WHERE nome_dia <> 'Domingo'
                                    AND nome_dia !~ 'bado') - 1 - (
                                 SELECT COUNT(1)
                                   FROM tbl_feriado
                                  WHERE data BETWEEN tbl_os.data_digitacao::date AND tbl_os_extra.termino_atendimento::date
                                    AND fabrica  =  $login_fabrica
                                    AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)
                                    AND ativo IS TRUE
                                ) <= 1
                            THEN 'D+1'
                            WHEN (
                                 SELECT COUNT(1)
                                   FROM fn_calendario(tbl_os.data_digitacao::DATE, tbl_os_extra.termino_atendimento::DATE)
                                  WHERE nome_dia <> 'Domingo'
                                    AND nome_dia !~ 'bado') - 1 - (
                                 SELECT COUNT(1)
                                   FROM tbl_feriado
                                  WHERE data BETWEEN tbl_os.data_digitacao::date AND tbl_os_extra.termino_atendimento::date
                                    AND fabrica  =  $login_fabrica
                                    AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)
                                    AND ativo IS TRUE) BETWEEN 1 AND 2
                            THEN 'D+2'
                            ELSE 'ACIMA DE D+2'
                        END
                    ELSE
                        CASE
                            WHEN (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 between 0 and 3 then 'ATÉ 3 HORAS'
                            WHEN (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 between 3 and 6 then 'ATÉ 6 HORAS'
                            WHEN (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 between 6 and 24 then 'ATÉ 24 horas'
                            ELSE
                                'ACIMA DE 24 HORAS'
                        END
                    END AS SLA,
                CASE 
                  WHEN SCHED_LOG.create_at IS NOT NULL THEN
                    (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - SCHED_LOG.create_at)) / 3600
                  ELSE
                    fn_calcula_previsao_retorno('', '{$dias}', {$login_fabrica})
                    (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao)) / 3600
                END AS horas_diferenca,
                tbl_tipo_posto.descricao AS tipo_posto,
                (
                    SELECT c.descricao
                      FROM tbl_os_defeito_reclamado_constatado odrc
                      JOIN tbl_solucao s ON s.solucao = odrc.solucao AND s.fabrica = {$login_fabrica}
                      JOIN tbl_classificacao c ON c.classificacao = s.classificacao AND c.fabrica = {$login_fabrica}
                     WHERE odrc.os = tbl_os.os
                  ORDER BY c.peso DESC
                     LIMIT 1
                ) AS classificacao
              FROM tbl_os
              JOIN tbl_tipo_atendimento AS T_TA
                ON T_TA.tipo_atendimento      =  tbl_os.tipo_atendimento
               AND T_TA.fabrica               =  {$login_fabrica}
              JOIN tbl_posto
                ON tbl_posto.posto                            =  tbl_os.posto
              JOIN tbl_posto_fabrica
                ON tbl_posto_fabrica.posto                    =  tbl_posto.posto
               AND tbl_posto_fabrica.fabrica                  =  {$login_fabrica}
              JOIN tbl_tipo_posto
                ON tbl_tipo_posto.tipo_posto                  =  tbl_posto_fabrica.tipo_posto
               AND tbl_tipo_posto.fabrica                     =  {$login_fabrica}
              JOIN tbl_status_checkpoint AS T_CHKPS
                ON T_CHKPS.status_checkpoint    =  tbl_os.status_checkpoint
              JOIN tbl_os_produto
                ON tbl_os_produto.os                          =  tbl_os.os
              JOIN tbl_produto
                ON tbl_produto.produto                        =  tbl_os_produto.produto
               AND tbl_produto.fabrica_i                      =  {$login_fabrica}
              JOIN tbl_linha
                ON tbl_produto.linha                          =  tbl_linha.linha
               AND tbl_linha.fabrica                          =  {$login_fabrica}
              JOIN tbl_familia
                ON tbl_produto.familia                        =  tbl_familia.familia
               AND tbl_familia.fabrica                        =  {$login_fabrica}
              JOIN tbl_os_extra
                ON tbl_os_extra.os                            =  tbl_os.os
               AND tbl_os_extra.i_fabrica                     =  {$login_fabrica}
              JOIN tbl_fabrica
                ON tbl_fabrica.fabrica                        =  tbl_os.fabrica
               AND tbl_fabrica.fabrica                        =  {$login_fabrica}
              LEFT
              JOIN tbl_hd_chamado_extra AS T_HDE
                ON T_HDE.os                    =  tbl_os.os
               AND T_HDE.posto                 =  tbl_os.posto
              LEFT
              JOIN tbl_hd_chamado
                ON tbl_hd_chamado.hd_chamado                  =  T_HDE.hd_chamado
               AND tbl_hd_chamado.titulo                      <> 'Help-Desk Posto'
              $join_esmaltec
               AND tbl_hd_chamado.posto IS NULL
              JOIN tbl_os_campo_extra AS T_OSE
                ON T_OSE.os                      =  tbl_os.os
              LEFT
              JOIN tbl_cliente_admin
                ON tbl_cliente_admin.cliente_admin            =  tbl_hd_chamado.cliente_admin
               AND tbl_cliente_admin.fabrica                  =  {$login_fabrica}
              LEFT JOIN tbl_distribuidor_sla_posto
                ON tbl_distribuidor_sla_posto.posto           =  tbl_posto_fabrica.posto
               AND tbl_distribuidor_sla_posto.fabrica         =  {$login_fabrica}
              LEFT JOIN tbl_distribuidor_sla
                ON T_OSE.campos_adicionais LIKE '%unidadeNegocio%' || tbl_distribuidor_sla.unidade_negocio || '%'
               AND tbl_distribuidor_sla.fabrica               =  {$login_fabrica}
              LEFT JOIN tbl_unidade_negocio 
                ON tbl_unidade_negocio.codigo                 = tbl_distribuidor_sla.unidade_negocio
              LEFT JOIN tbl_posto_distribuidor_sla_default
                ON tbl_posto_distribuidor_sla_default.posto   =  tbl_posto_fabrica.posto
               AND tbl_posto_distribuidor_sla_default.fabrica =  {$login_fabrica}
              LEFT JOIN tbl_distribuidor_sla distribuidor_principal
                ON distribuidor_principal.distribuidor_sla    =  tbl_posto_distribuidor_sla_default.distribuidor_sla
               AND distribuidor_principal.fabrica             =  {$login_fabrica}
              LEFT JOIN tbl_defeito_reclamado
                ON tbl_defeito_reclamado.defeito_reclamado    =  tbl_os.defeito_reclamado
               AND tbl_defeito_reclamado.fabrica              =  {$login_fabrica}
              LEFT JOIN tbl_admin
                ON tbl_admin.admin                            =  tbl_os.admin
               AND tbl_admin.fabrica                          =  {$login_fabrica}
              LEFT JOIN tbl_cidade regiao_distribuidor_principal
                ON regiao_distribuidor_principal.cidade       =  distribuidor_principal.cidade
              LEFT JOIN tbl_hd_chamado_cockpit
                ON tbl_hd_chamado_cockpit.hd_chamado          =  tbl_os.hd_chamado
               AND tbl_hd_chamado_cockpit.fabrica             =  {$login_fabrica}
              LEFT JOIN tbl_routine_schedule_log AS SCHED_LOG
                ON SCHED_LOG.routine_schedule_log             =  tbl_hd_chamado_cockpit.routine_schedule_log
              LEFT JOIN tbl_posto_fabrica distrib
                ON tbl_os.digitacao_distribuidor              =  distrib.posto
               AND distrib.fabrica                            =  {$login_fabrica}
             WHERE tbl_os.fabrica                             =  {$login_fabrica} {$cond} {$cond_posto} {$whereUnid}
               {$cond_data}
               AND tbl_os.finalizada IS NOT NULL
               AND tbl_familia.descricao not in ('POST MIX', 'CHOPEIRA')
              $cond_cliente_admin
		          $order_by
	            $subselect_fim";
    } else {
		$sql = "
				SELECT tbl_os.os,
				 tbl_os.sua_os,
				 tbl_os.type,
				 tbl_os.cancelada,
				 tbl_os.nota_fiscal,
				 tbl_os.obs,
				 tbl_os.posto,
				 tbl_os.fabrica,
				 tbl_os.defeito_reclamado,
				 tbl_os.digitacao_distribuidor,
				 tbl_os.data_digitacao,
				 tbl_os.status_checkpoint,
				 tbl_os.os_numero,
				 tbl_os.admin,
				 sua_os_offline,
				 LPAD(tbl_os.sua_os,
				 20,
				 '0') AS ordem,
				 TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS digitacao,
				 TO_CHAR(tbl_os.data_hora_abertura, 'DD/MM/YYYY HH24:MI:SS') AS abertura,
				 TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS fechamento,
				 TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS finalizada,
				 TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
				 tbl_os.data_abertura,
				 tbl_os_produto.serie,
				 tbl_os.consumidor_estado,
				 tbl_os.excluida,
				 tbl_os.motivo_atraso,
				 tbl_os.tipo_os_cortesia,
				 tbl_os.consumidor_revenda,
				 tbl_os.consumidor_nome,
				 tbl_os.consumidor_fone,
				 tbl_os.revenda_nome,
				 tbl_os.tipo_atendimento,
				 tbl_os.os_reincidente AS reincidencia,
				 tbl_os.justificativa_adicionais,
				 tbl_os.os_posto,
				 tbl_os.aparencia_produto,
				 tbl_os.tecnico_nome,
				 tbl_os.rg_produto,
				 tbl_os.hd_chamado,
				 tbl_os.produto,
				 tbl_os.consumidor_endereco,
				 tbl_os.consumidor_bairro,
				 tbl_os.consumidor_cep,
				 tbl_os.consumidor_cidade,
				 TO_CHAR(SCHED_LOG.create_at, 'DD/MM/YYYY HH24:MI:SS') AS data_integracao,
				 TO_CHAR(tbl_os.exportado, 'DD/MM/YYYY HH24:MI:SS') as exportado,
				 TO_CHAR(tbl_os.data_digitacao, 'YYYY-MM-DD HH24:MI:SS') AS digitacao_hora,
				 TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS digitacao_hora_f,
				 tbl_hd_chamado_cockpit.dados AS json_kof,
				 SCHED_LOG.file_name AS arquivo_kof,
				 tbl_os.data_conserto,         
				 create_at
				 into temp tmp_indicadores_$login_admin
				 from tbl_os
				 join tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado          
				and tbl_hd_chamado_cockpit.fabrica = 158  
				join tbl_routine_schedule_log AS SCHED_LOG ON SCHED_LOG.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log  
				join tbl_os_produto using(os)
			 where tbl_os.fabrica                             =  {$login_fabrica} {$cond} {$cond_posto} 
           {$cond_data}
		   AND tbl_os.finalizada IS NOT NULL;


				create index tmp_indicadores1 on tmp_indicadores_$login_admin(os);
				create index tmp_indicadores2 on tmp_indicadores_$login_admin(tipo_atendimento);
				create index tmp_indicadores3 on tmp_indicadores_$login_admin(posto);
				create index tmp_indicadores4 on tmp_indicadores_$login_admin(fabrica);
				create index tmp_indicadores5 on tmp_indicadores_$login_admin(status_checkpoint);
				create index tmp_indicadores6 on tmp_indicadores_$login_admin(produto);
			{$subselect_inicio}

			SELECT {$distinct}
				tbl_os.*,
                tbl_cliente_admin.nome       AS cliente_admin_nome,
                tbl_familia.descricao        AS familia_produto,
                tbl_os_extra.serie_justificativa,
                T_OSE.campos_adicionais,
                tbl_distribuidor_sla.unidade_negocio,
                TO_CHAR(tbl_os.create_at,   'DD/MM/YYYY HH24:MI:SS') AS data_integracao,
                  (
                  SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI:SS')
                    FROM tbl_os_modificacao
                   WHERE tbl_os_modificacao.os = tbl_os.os
                ORDER BY data DESC LIMIT 1)      AS ultima_modificacao,
                distribuidor_principal.descricao AS distribuidor_principal,
                tbl_defeito_reclamado.descricao  AS descricao_defeito_reclamado,
                tbl_os_extra.inicio_atendimento,
                tbl_os_extra.termino_atendimento,
                tbl_admin.nome_completo AS admin_nome,
                regiao_distribuidor_principal.nome AS regiao_distribuidor_principal,
                ARRAY_TO_STRING(ARRAY(
                    SELECT dc.descricao
                      FROM tbl_os_defeito_reclamado_constatado osdc
                      JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = osdc.defeito_constatado
                                                    AND dc.fabrica = $login_fabrica
                     WHERE osdc.fabrica = $login_fabrica
                       AND osdc.os      = tbl_os.os
                       AND osdc.defeito_constatado IS NOT NULL
                  ORDER BY osdc.defeito_constatado_reclamado DESC
                     LIMIT 1), ', ')                                   AS defeitos_constatados,                
                ARRAY_TO_STRING(ARRAY(
                    SELECT iddc.defeito_constatado
                      FROM tbl_os_defeito_reclamado_constatado iddc
                     WHERE iddc.fabrica = $login_fabrica
                       AND iddc.os      = tbl_os.os
                       AND iddc.defeito_constatado IS NOT NULL), ', ') AS ids_defeitos_constatados,
                ARRAY_TO_STRING(ARRAY(
                    SELECT s.descricao
                      FROM tbl_os_defeito_reclamado_constatado oss
                      JOIN tbl_solucao s
                        ON s.solucao = oss.solucao
                       AND s.fabrica = $login_fabrica
                      JOIN tbl_classificacao AS classificacao
                        ON classificacao.classificacao = s.classificacao
                       AND classificacao.fabrica       = $login_fabrica
                     WHERE oss.fabrica =  $login_fabrica
                       AND oss.os      =  tbl_os.os
                       AND oss.solucao IS NOT NULL
                  ORDER BY classificacao.peso DESC LIMIT 1 ), ', ') AS solucoes,
                T_TA.descricao,
				T_TA.grupo_atendimento,
	T_TA.tipo_atendimento,
	T_TA.codigo AS codigp_tipo_atendimento,
				tbl_posto.posto,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.contato_estado,
                tbl_posto_fabrica.contato_cidade,
                tbl_posto_fabrica.credenciamento,
                tbl_posto.nome AS posto_nome,
                tbl_posto.capital_interior,
                tbl_posto.estado,
                tbl_tipo_posto.posto_interno,
                tbl_os_extra.impressa,
                tbl_os_extra.obs_adicionais,
                tbl_os_extra.extrato,
                tbl_os_extra.os_reincidente,
                tbl_produto.referencia AS produto_referencia,
                tbl_produto.descricao AS produto_descricao,
                tbl_produto.voltagem AS produto_voltagem,
                T_CHKPS.descricao as status_os,
                distrib.codigo_posto AS codigo_distrib,
                CASE
                    WHEN tbl_familia.descricao =  'REFRIGERADOR'
                      OR tbl_familia.descricao   =  'VENDING MACHINE'
                    THEN
                        CASE
                            WHEN (
                                 SELECT COUNT(1)
                                   FROM fn_calendario(tbl_os.create_at::DATE, tbl_os_extra.termino_atendimento::DATE )
                                  WHERE nome_dia <> 'Domingo'
                                    AND nome_dia !~ 'bado') - 1 - (
                                 SELECT COUNT(1)
                                   FROM tbl_feriado
                                  WHERE data BETWEEN tbl_os.create_at::date AND tbl_os_extra.termino_atendimento::date
                                    AND fabrica  =  $login_fabrica
                                    AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)
                                    AND ativo IS TRUE
                                ) <= 1
                            THEN 'D+1'
                            WHEN (
                                 SELECT COUNT(1)
                                   FROM fn_calendario(tbl_os.create_at::DATE, tbl_os_extra.termino_atendimento::DATE)
                                  WHERE nome_dia <> 'Domingo'
                                    AND nome_dia !~ 'bado') - 1 - (
                                 SELECT COUNT(1)
                                   FROM tbl_feriado
                                  WHERE data BETWEEN tbl_os.create_at::date AND tbl_os_extra.termino_atendimento::date
                                    AND fabrica  =  $login_fabrica
                                    AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)
                                    AND ativo IS TRUE) BETWEEN 1 AND 2
                            THEN 'D+2'
                            WHEN (
                                 SELECT COUNT(1)
                                   FROM fn_calendario(tbl_os.create_at::DATE, tbl_os_extra.termino_atendimento::DATE)
                                  WHERE nome_dia <> 'Domingo'
                                    AND nome_dia !~ 'bado') - 1 - (
                                 SELECT COUNT(1)
                                   FROM tbl_feriado
                                  WHERE data BETWEEN tbl_os.create_at::date AND tbl_os_extra.termino_atendimento::date
                                    AND fabrica  =  $login_fabrica
                                    AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)
                                    AND ativo IS TRUE) BETWEEN 2 AND 3
                            THEN 'D+3'
                            ELSE 'ACIMA DE D+3'
                        END
                    ELSE
                        CASE
                            WHEN (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.create_at))/3600 between 0 and 3 then 'ATÉ 3 HORAS'
                            WHEN (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.create_at))/3600 between 3 and 6 then 'ATÉ 6 HORAS'
                            WHEN (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.create_at))/3600 between 6 and 24 then 'ATÉ 24 horas'
                            ELSE
                                'ACIMA DE 24 HORAS'
                        END
                    END AS SLA,
                    CASE 
                      WHEN tbl_os.create_at IS NOT NULL THEN
                        (SELECT EXTRACT(EPOCH FROM (SELECT (tbl_os_extra.termino_atendimento::timestamp - tbl_os.create_at::timestamp) - ((SELECT (SELECT COUNT(1) FROM fn_calendario(tbl_os.create_at::date, tbl_os_extra.termino_atendimento::date) WHERE nome_dia = 'Domingo' OR nome_dia ~ 'bado') + (SELECT COUNT(1) FROM tbl_feriado WHERE data BETWEEN tbl_os.create_at::date AND tbl_os_extra.termino_atendimento::date AND fabrica = $login_fabrica AND ATIVO IS TRUE AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6))) || ' days')::interval)) / 3600)
                      ELSE
                        (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao)) / 3600
                    END AS horas_diferenca,
                tbl_tipo_posto.descricao AS tipo_posto,
                (
                    SELECT c.descricao
                      FROM tbl_os_defeito_reclamado_constatado odrc
                      JOIN tbl_solucao s ON s.solucao = odrc.solucao AND s.fabrica = {$login_fabrica}
                      JOIN tbl_classificacao c ON c.classificacao = s.classificacao AND c.fabrica = {$login_fabrica}
                     WHERE odrc.os = tbl_os.os
                  ORDER BY c.peso DESC
                     LIMIT 1
                ) AS classificacao
          FROM tmp_indicadores_$login_admin tbl_os
          JOIN tbl_tipo_atendimento AS T_TA
            ON T_TA.tipo_atendimento      =  tbl_os.tipo_atendimento
           AND T_TA.fabrica               =  {$login_fabrica}
          JOIN tbl_posto
            ON tbl_posto.posto                            =  tbl_os.posto
          JOIN tbl_posto_fabrica
            ON tbl_posto_fabrica.posto                    =  tbl_posto.posto
           AND tbl_posto_fabrica.fabrica                  =  {$login_fabrica}
          JOIN tbl_tipo_posto
            ON tbl_tipo_posto.tipo_posto                  =  tbl_posto_fabrica.tipo_posto
           AND tbl_tipo_posto.fabrica                     =  {$login_fabrica}
          JOIN tbl_status_checkpoint AS T_CHKPS
            ON T_CHKPS.status_checkpoint    =  tbl_os.status_checkpoint
          JOIN tbl_os_produto
            ON tbl_os_produto.os                          =  tbl_os.os
          JOIN tbl_produto
            ON tbl_produto.produto                        =  tbl_os_produto.produto
           AND tbl_produto.fabrica_i                      =  {$login_fabrica}
          JOIN tbl_linha
            ON tbl_produto.linha                          =  tbl_linha.linha
           AND tbl_linha.fabrica                          =  {$login_fabrica}
          JOIN tbl_familia
            ON tbl_produto.familia                        =  tbl_familia.familia
           AND tbl_familia.fabrica                        =  {$login_fabrica}
          JOIN tbl_os_extra
            ON tbl_os_extra.os                            =  tbl_os.os
           AND tbl_os_extra.i_fabrica                     =  {$login_fabrica}
          JOIN tbl_fabrica
            ON tbl_fabrica.fabrica                        =  tbl_os.fabrica
           AND tbl_fabrica.fabrica                        =  {$login_fabrica}
          LEFT
          JOIN tbl_hd_chamado_extra AS T_HDE
            ON T_HDE.os                    =  tbl_os.os
           AND T_HDE.posto                 =  tbl_os.posto
          LEFT
          JOIN tbl_hd_chamado
            ON tbl_hd_chamado.hd_chamado                  =  T_HDE.hd_chamado
           AND tbl_hd_chamado.titulo                      <> 'Help-Desk Posto'
          $join_esmaltec
           AND tbl_hd_chamado.posto IS NULL
          JOIN tbl_os_campo_extra AS T_OSE
            ON T_OSE.os                      =  tbl_os.os
          LEFT
          JOIN tbl_cliente_admin
            ON tbl_cliente_admin.cliente_admin            =  tbl_hd_chamado.cliente_admin
           AND tbl_cliente_admin.fabrica                  =  {$login_fabrica}
          $primeiro_left
          LEFT
          JOIN tbl_posto_distribuidor_sla_default
            ON tbl_posto_distribuidor_sla_default.posto   =  tbl_posto_fabrica.posto
           AND tbl_posto_distribuidor_sla_default.fabrica =  {$login_fabrica}
          LEFT
          JOIN tbl_distribuidor_sla distribuidor_principal
            ON distribuidor_principal.distribuidor_sla    =  tbl_posto_distribuidor_sla_default.distribuidor_sla
           AND distribuidor_principal.fabrica             =  {$login_fabrica}
          LEFT
          JOIN tbl_defeito_reclamado
            ON tbl_defeito_reclamado.defeito_reclamado    =  tbl_os.defeito_reclamado
           AND tbl_defeito_reclamado.fabrica              =  {$login_fabrica}
          LEFT
          JOIN tbl_admin
            ON tbl_admin.admin                            =  tbl_os.admin
           AND tbl_admin.fabrica                          =  {$login_fabrica}
          LEFT
          JOIN tbl_cidade regiao_distribuidor_principal
            ON regiao_distribuidor_principal.cidade       =  distribuidor_principal.cidade
          LEFT
          JOIN tbl_posto_fabrica distrib
            ON tbl_os.digitacao_distribuidor              =  distrib.posto
           AND distrib.fabrica                            =  {$login_fabrica}
		 WHERE tbl_familia.descricao not in ('POST MIX', 'CHOPEIRA')
			{$whereUnid}
	   {$order_by}
	   {$subselect_fim}
	    ";
    	}

    $dataArquivo  = date("Ymdhis");
    $arquivo_nome = "indicadorEficiencia{$dataArquivo}.csv";
    $file         = ADMCLI_BACK."xls/{$arquivo_nome}";
    $fileTemp     = "/tmp/{$arquivo_nome}";
    $fp = fopen($fileTemp,'w');

    $head  = "UNIDADE DE NEGÓCIO;";
    $head .= "OS;";
    $head .= "OS REINCIDENTE?;";
    $head .= "OS REINCIDENTE;";
    $head .= "CLIENTE ADMIN;";
    $head .= "PATRIMÔNIO;";
    $head .= "SÉRIE;";
    $head .= "AB;";
    $head .= "DC;";
    $head .= "FC;";
    $head .= "DATA ABERTURA KOF;";
    $head .= "DATA INÍCIO ATENDIMENTO;";
    $head .= "DATA FIM ATENDIMENTO;";

    if($login_fabrica == 158){
       $head .= "DATA LANÇAMENTO DA PEÇA;";
    }

    $head .= "C/R;";
    $head .= "REGIÃO;";
    $head .= "DISTRIBUIDOR;";
    $head .= "POSTO;";
    $head .= "TIPO DE POSTO;";
    $head .= "CEP;";
    $head .= "ENDEREÇO;";
    $head .= "BAIRRO;";
    $head .= "CIDADE;";
    $head .= "ESTADO;";
    $head .= "CÓDIGO DO CLIENTE;";
    $head .= "CLIENTE;";
    $head .= "TELEFONE;";
    $head .= "TIPO DE ATENDIMENTO;";
    $head .= "DEFEITO RECLAMADO;";
    $head .= "DEFEITO CONSTATADO;";

    if($login_fabrica == 158){
      $head .= "DEFEITO CONSTATADO REINCIDENTE;";
    }

    $head .= "SOLUÇÃO;";
    $head .= "CLASSIFICAÇÃO;";
    $head .= "OBSERVAÇÃO;";
    $head .= "OBSERVAÇÃO KOF;";
    $head .= "ADMIN ÚLTIMA ALTERAÇÃO;";
    $head .= "ADMIN FINAL;";
    $head .= "FAMÍLIA DO PRODUTO;";
    $head .= "STATUS;";
    $head .= "NOTA FISCAL;";
    $head .= "PRODUTO;";
    $head .= "OS KOF;";
    $head .= "ARQUIVO ENTRADA;";
    $head .= "DATA INTEGRAÇÃO;";
    $head .= "ARQUIVO SAÍDA;";
    $head .= "DATA SAÍDA;";
    $head .= "SLA;";
    $head .= "TOTAL HORAS;";
    $head .= "\n";
    fwrite($fp, $head);

    $resxls = pg_query($con, $sql);
    $count = pg_num_rows($resxls);

    $quebra_linha = array("\n", "\r", "<br>", "\nr", "</br>", "  ", ";");

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
        $os_reincidente     = "";
        $reincidencia_reclamado = "";
        $xreincidencia = "";
	      $unidade_negocio = "";
        $data_lancamento_peca = "";

        $os                 =  trim(pg_fetch_result($resxls,$x,os));
        $os_reincidente     =  trim(pg_fetch_result($resxls,$x,os_reincidente));
        $justificativa_adicionais = trim(pg_fetch_result($resxls,$x,justificativa_adicionais));
        $justificativa_adicionais = json_decode($justificativa_adicionais, true);
        $sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
        $hd_chamado         = trim(pg_fetch_result($resxls,$x,hd_chamado));
        $cidade_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_cidade));
        $estado_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_estado));
        $cidade_uf          = $cidade_posto_xls."/".$estado_posto_xls;
        $nota_fiscal        = trim(pg_fetch_result($resxls,$x,nota_fiscal));
        $digitacao          = trim(pg_fetch_result($resxls,$x,digitacao));
	      $abertura           = trim(pg_fetch_result($resxls,$x,abertura));
	      $data_abertura      = trim(pg_fetch_result($resxls,$x,data_abertura));
        $consumidor_revenda = trim(pg_fetch_result($resxls,$x,consumidor_revenda));
        $fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
        $finalizada         = trim(pg_fetch_result($resxls,$x,finalizada));
        $data_conserto      = trim(@pg_fetch_result($resxls,$x,data_conserto));
        $serie              = trim(pg_fetch_result($resxls,$x,serie));
        $serie              = strtoupper($serie);
        $type              = trim(pg_fetch_result($resxls,$x,type));
        $reincidencia       = trim(pg_fetch_result($resxls,$x,reincidencia));
        $consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
        $excluida           = trim(pg_fetch_result($resxls,$x,excluida));
        $consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
        $data_nf            = trim(pg_fetch_result($resxls,$x,data_nf));
        $posto          = pg_fetch_result($resxls,$x,posto);
        $codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
        $posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
        $produto        = pg_fetch_result($resxls,$x,produto);
        $produto_referencia = trim(pg_fetch_result($resxls,$x,produto_referencia));
        $status_os          = trim(pg_fetch_result($resxls,$x,status_os));
        $produto_descricao  = trim(pg_fetch_result($resxls,$x,produto_descricao));
        $produto_voltagem   = trim(pg_fetch_result($resxls,$x,produto_voltagem));
        $status_checkpoint  = trim(pg_fetch_result($resxls,$x,status_checkpoint));
        $marca_logo         = trim(pg_fetch_result($resxls,$x,marca));
        $situacao_posto     = trim(pg_fetch_result($resxls,$x,credenciamento));
        $revenda_nome       = trim(pg_fetch_result($resxls,$x,revenda_nome));
        $obs                = trim(pg_fetch_result($resxls,$x,obs));
        $consumidor_endereco            = pg_fetch_result($resxls,$x, consumidor_endereco);
        $consumidor_numero              = pg_fetch_result($resxls,$x, consumidor_numero);
        $consumidor_estado              = pg_fetch_result($resxls,$x, consumidor_estado);
        $consumidor_cidade              = pg_fetch_result($resxls,$x, consumidor_cidade);
        $nome_consumidor_revenda = ($consumidor_revenda == "C" || empty($consumidor_revenda)) ? $consumidor_nome : $revenda_nome;
        $consumidor_email    = trim(pg_fetch_result($resxls,$x,consumidor_email));
        $revenda_cnpj_tec    = trim(pg_fetch_result($resxls,$x,revenda_cnpj));
        $revenda_nome_tec    = trim(pg_fetch_result($resxls,$x,revenda_nome));
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

        if ($areaAdminCliente == true) {
            $unidade_negocio = $campos_adicionais['unidadeNegocio'];
        }

	if (empty($unidade_negocio)) {
		$xxUnidadeNegocio = null;
	} else {
	        $xUnidadeNegocio = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null,null,$unidade_negocio);
        	$xxUnidadeNegocio = $xUnidadeNegocio[0]["cidade"];
	}

        $distribuidor_principal        = pg_fetch_result($resxls, $x, "distribuidor_principal");
        $defeito_reclamado             = pg_fetch_result($resxls, $x, "defeito_reclamado");
        $descricao_defeito_reclamado   = pg_fetch_result($resxls, $x, "descricao_defeito_reclamado");
        $inicio_atendimento            = pg_fetch_result($resxls, $x, "inicio_atendimento");
        $termino_atendimento           = pg_fetch_result($resxls, $x, "termino_atendimento");
        $admin_nome                    = pg_fetch_result($resxls, $x, "admin_nome");
        $regiao_distribuidor_principal = pg_fetch_result($resxls, $x, "regiao_distribuidor_principal");
        $defeitos_constatados          = pg_fetch_result($resxls, $x, "defeitos_constatados");
        if($login_fabrica == 158){
          if(!empty($os) AND !empty($os_reincidente)){
            $tipo_atendimento_x = pg_fetch_result($resxls, $x, 'codigo_tipo_atendimento');

            if($tipo_atendimento_x != 90){
              $defeitos_constatados_reincidentes_arr = array(); 
              $sql_deifConstComun = "SELECT tbl_defeito_constatado.descricao AS defeitos_constatados_reincidentes
                                        FROM tbl_os_defeito_reclamado_constatado
                                        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado 
                                        JOIN tbl_os_defeito_reclamado_constatado orc ON orc.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado AND orc.os = {$os_reincidente} WHERE tbl_os_defeito_reclamado_constatado.os = {$os}
                                        AND tbl_os_defeito_reclamado_constatado.defeito_constatado IS NOT NULL ";             
              
              $res_deifConstComun = pg_query($con, $sql_deifConstComun);
              $contador_deifConstComun = pg_num_rows($res_deifConstComun);

              for($yy=0; $yy<$contador_deifConstComun; $yy++){
                $defeitos_constatados_reincidentes_arr[] = pg_fetch_result($res_deifConstComun, $yy, "defeitos_constatados_reincidentes");
              }
            } 
          } else {
            $defeitos_constatados_reincidentes_arr = array();
          }
        }
        $ids_defeitos_constatados      = pg_fetch_result($resxls, $x, 'ids_defeitos_constatados');
        $solucoes                      = pg_fetch_result($resxls, $x, "solucoes");
        $horas_diferenca               = pg_fetch_result($resxls, $x, "horas_diferenca");
        $horas_diferenca               = round($horas_diferenca,2);
        $tempo_para_defeito = trim(pg_fetch_result($resxls,$x,tempo_para_defeito));
        $data_saida = pg_fetch_result($resxls, $x, "exportado");
        $sla = pg_fetch_result($resxls, $x, "SLA");
        $tipo_posto = pg_fetch_result($resxls, $x, "tipo_posto");
        $classificacao = pg_fetch_result($resxls, $x, "classificacao");

        $intevalReicidencia = ($areaAdminCliente == true)? "AND tbl_os.finalizada::date >= ('$data_abertura'::date - INTERVAL '45 days')" : "AND tbl_os.finalizada::date >= ('$data_abertura'::date - INTERVAL '90 days')  " ;

    $sqlDefeitoImprodutivo = "SELECT defeito_constatado FROM tbl_os_defeito_reclamado_constatado 
                      JOIN tbl_defeito_constatado USING(defeito_constatado) 
                      JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                      WHERE os = {$os}
                      AND lower(tbl_defeito_constatado_grupo.descricao) = 'improdutivo'
                      LIMIT 1";

    #$resDefeitoImprodutivo = pg_query($con, $sqlDefeitoImprodutivo);

	if($areaAdminCliente == true){
		$sql = "SELECT tbl_os.os
			FROM tbl_os
			INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			WHERE tbl_os.fabrica = {$login_fabrica}
			{$intevalReicidencia}
			AND trim(tbl_os_extra.serie) = '{$serie}'
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.os < {$os}
			ORDER BY tbl_os.data_abertura DESC
			LIMIT 1";
	}else{
		$sql = "SELECT tbl_os.os
			FROM tbl_os
			INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
			INNER JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
			LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = {$login_fabrica} AND lower(tbl_defeito_constatado_grupo.descricao) <> 'improdutivo'
			WHERE tbl_os.fabrica = {$login_fabrica}
			{$intevalReicidencia}
			AND tbl_os.produto = {$produto}
			AND trim(tbl_os_extra.serie_justificativa) = '{$serie_justificativa}'
			AND tbl_os_defeito_reclamado_constatado.defeito_constatado IN({$ids_defeitos_constatados})
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.os < {$os}
			ORDER BY tbl_os.data_abertura DESC
			LIMIT 1";
	}  

    $resR = pg_query($con,$sql);

        if (pg_num_rows($resR) > 0) {

    			$reincidencia_reclamado = pg_fetch_result($resR,0,'os');
    			if (!empty($justificativa_adicionais) AND $areaAdminCliente !== true and $reincidencia_reclamado == $justificativa_adicionais['reincidencia_reclamado']) {
    				$reincidencia_reclamado = $justificativa_adicionais['reincidencia_reclamado'];            
    			}else{
    				$reincidencia_reclamado = pg_fetch_result($resR,0,'os');
    			}
			$xreincidencia = "Sim";

			$sql_deifConstComun = "SELECT tbl_defeito_constatado.descricao AS defeitos_constatados_reincidentes
				FROM tbl_os_defeito_reclamado_constatado
				JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado 
				JOIN tbl_os_defeito_reclamado_constatado orc ON orc.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado 
				AND orc.os = {$reincidencia_reclamado} WHERE tbl_os_defeito_reclamado_constatado.os = {$os}
AND tbl_os_defeito_reclamado_constatado.defeito_constatado IS NOT NULL";

			$res_deifConstComun = pg_query($con, $sql_deifConstComun);
               		$contador_deifConstComun = pg_num_rows($res_deifConstComun);
	        
               		for($yy=0; $yy<$contador_deifConstComun; $yy++){
                 		$defeitos_constatados_reincidentes_arr[] = pg_fetch_result($res_deifConstComun, $yy, "defeitos_constatados_reincidentes")     ;
               		}

        } else {
          $xreincidencia =  "Não";
        }
    if (pg_num_rows($resDefeitoImprodutivo) > 0) {
              $xreincidencia =  "Não";
    }
        /*HD - 6212545*/
        if ($login_fabrica == 158 && strlen($abertura) == 0) {
          list($yi, $mi, $di) = explode("-", $data_abertura);
          $abertura = "$di/$mi/$yi";
        }

        $body  = '"' . str_replace($quebra_linha, "", $xxUnidadeNegocio)       . '";';
        $body .= "{$sua_os};";
        $body .= "{$xreincidencia};";
        if($login_fabrica == 158){
          if($xreincidencia == 'Sim'){            
            $body .= '"' . str_replace($quebra_linha, "", $reincidencia_reclamado) . '";';            
          } else {
            $body .= ";";
          }
        } else {
          $body .= '"' . str_replace($quebra_linha, "", $reincidencia_reclamado) . '";';  
        }  

        if($login_fabrica == 158){

          $sql = "SELECT TO_CHAR(MIN(tbl_os_item.digitacao_item),'DD/MM/YYYY') AS data_lancamento_peca
                  FROM tbl_os_item
                    JOIN tbl_os_produto USING(os_produto)
                    JOIN tbl_pedido USING(pedido)
                    JOIN tbl_tipo_pedido USING(tipo_pedido)
                    JOIN tbl_pedido_item USING(pedido_item)
                  WHERE tbl_os_produto.os = $os 
                        AND tbl_pedido.fabrica = $login_fabrica 
                        AND tbl_tipo_pedido.fabrica = $login_fabrica 
                        AND tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada
                        AND tbl_tipo_pedido.descricao = 'NTP'";

            $res_query = pg_query($con, $sql);
            $count_res = pg_num_rows($res_query);

            if($count_res > 0){
              $data_lancamento_peca = pg_fetch_result($res_query,0,'data_lancamento_peca');
            }   
         }
        
        $body .= "{$cliente_admin_nome};";
        $body .= "{$serie_justificativa};";
        $body .= "{$serie};";
        $body .= "{$abertura};";
        $body .= "{$data_conserto};";
        $body .= "{$fechamento};";
        $body .= "{$json_kof['dataAbertura']};";
        $body .= "{$inicio_atendimento};";
        $body .= "{$termino_atendimento};";
        if($login_fabrica == 158){
          $body .= "{$data_lancamento_peca};";
        }
        
        switch ($consumidor_revenda) {
        case "C":
            $body .= "CONS;";
            break;

        case "R":
            $body .= "REV;";
            break;

        case "":
            $body .= ";";
            break;
        }
        $body .= "{$regiao_distribuidor_principal};";
        $body .= "{$distribuidor_principal};";
        $body .= "{$codigo_posto} - {$posto_nome};";
        $body .= "{$tipo_posto};";
        $body .= "{$consumidor_cep};";
        $body .= '"' . str_replace($quebra_linha, "", $consumidor_endereco)                   . '";';
        $body .= '"' . str_replace($quebra_linha, "", $consumidor_bairro)                     . '";';
        $body .= '"' . str_replace($quebra_linha, "", $consumidor_cidade)                     . '";';
        $body .= "{$consumidor_estado};";
        $body .= "{$json_kof['idCliente']};";
        $body .= '"' . str_replace($quebra_linha, "", $nome_consumidor_revenda)               . '";';
        $body .= "{$consumidor_fone};";
        $body .= '"' . str_replace($quebra_linha, "", $descricao_tipo_atendimento)            . '";';
        if($login_fabrica == 158){
          $body .= '"' . str_replace($quebra_linha, "", $descricao_defeito_reclamado)         . '";';        
        } else {
          $body .= '"' . str_replace($quebra_linha, "", $defeito_reclamado)                   . '";';
        }
        $body .= '"' . str_replace($quebra_linha, "", $defeitos_constatados)                  . '";';
	if($login_fabrica == 158){ 
      		if($xreincidencia != "Sim"){
			$body .= ";";
		}else{	
			$body .= '"' . implode(" | ",$defeitos_constatados_reincidentes_arr)    . '";';
		}
        }
        $body .= '"' . str_replace($quebra_linha, "", $solucoes)                              . '";';
        $body .= "{$classificacao};";
        $body .= '"' . str_replace($quebra_linha, "", $obs)                                   . '";';
        $body .= '"' . str_replace($quebra_linha, "", $json_kof['comentario'])                . '";';
        $body .= "{$admin_nome};";
        $body .= "{$admin_nome};";
        $body .= "{$familia_produto};";
        $body .= "{$status_os};";
        $body .= "{$nota_fiscal};";
        $body .= "{$produto_referencia} - {$produto_descricao};";
        $body .= "{$json_kof['osKof']};";
        $body .= '"' . str_replace($quebra_linha, "", $arquivo_kof)                            . '";';
        $body .= "{$data_integracao};";
        $body .= '"' . str_replace($quebra_linha, "", $campos_adicionais['arquivo_saida_kof']) . '";';
        $body .= "{$data_saida};";
        $body .= "{$sla};";
        $body .= "{$horas_diferenca};";
        $body .= "\n";
        fwrite($fp, $body);
    }

    fclose($fp);
    if(file_exists($fileTemp)){
        system("mv $fileTemp $file");

        if(file_exists($file)){
            echo $file;
        }
    }
    exit;
}

if (isset($_POST['ajax'])) {
    $resultado2 = array();
    $retorno = '';
    if (isset($_POST['tipo_atendimento']) && $_POST['tipo_atendimento'] == 'sim') {
        $retorno .= "<select name='tipo_atendimento' class='frm'>";

        $sql = "SELECT tipo_atendimento,descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo;";
        $res   = pg_exec($con,$sql);
        $total = pg_numrows($res);

        for($i = 0; $i < $total; $i++){
            $tipo_atendimento_id = pg_result($res,$i,tipo_atendimento);
            $descricao   = pg_result($res,$i,descricao);
            $retorno .= "<option value={$tipo_atendimento_id}";
            if($tipo_atendimento_id == $tipo_atendimento){
                $retorno .= 'selected';
            }
            $retorno .= ">{$descricao}</option>";
        }
        $retorno .= "</select>";
        exit(json_encode(array("ok" => utf8_encode($retorno))));
    }
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];
    $tipo_atendimento = $_POST["tipo_atendimento"];
    $unidade_negocio = $_POST['unidade_negocio'];

    if (in_array(6300, $unidade_negocio)) {
      if (count($unidade_negocio) > 1) {
        exit(json_encode(array("error" => utf8_encode("A unidade de negócio 6300 - BEBIDAS FRUKI, deve pesquisar separadamente!"), "campo" => "unidade_negocio")));
      }
    }

    $unidade_negocio = implode("|", $unidade_negocio);
    $codigo_posto   = $_POST['codigo_posto'];
    $descricao      = $_POST['posto_nome'];

    if (empty($data_inicial) || empty($data_final)) {
        exit(json_encode(array("error" => utf8_encode("Preencha os campos obrigatórios"), "campo" => "datas")));
    }

    if (empty($data_inicial)) {
        exit(json_encode(array("error" => utf8_encode("Preencha os campos obrigatórios"), "campo" => "data_inicial")));
    }
    if (empty($data_final)) {
        exit(json_encode(array("error" => utf8_encode("Preencha os campos obrigatórios"), "campo" => "data_final")));
    }

    $xdata_inicial = explode('/',$data_inicial);
    $xdata_final   = explode('/',$data_final);

    $xxdata_inicial = $xdata_inicial[2]."-".$xdata_inicial[1]."-".$xdata_inicial[0];
    $xxdata_final   = $xdata_final[2]."-".$xdata_final[1]."-".$xdata_final[0];

    $xdata_inicial = new DateTime($xxdata_inicial);
    $xdata_final   = new DateTime($xxdata_final);

    $dias = $xdata_inicial->diff($xdata_final)->days;

    if($dias > 180) {
        exit(json_encode(array("error" => utf8_encode("Intervalo de data não pode ser maior que 180 dias"))));
    }
    $cond_posto = "";
    if(strlen($codigo_posto) > 0) {
        $sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $posto      = pg_fetch_result($res,0,posto);
            $cond_posto = " AND os.posto = $posto ";
        }
    }

    if(!empty($tipo_atendimento)) {
        $cond = " and os.tipo_atendimento IN(".implode(",", $tipo_atendimento).") ";
    }

    if (strlen($unidade_negocio) > 0) {


        $unidadenegocio = explode("|", $unidade_negocio);
        if (in_array("6101", $unidadenegocio)) {
            array_push($unidadenegocio, 6102,6103,6104,6105,6106,6107,6108);
        }
        $unidade_negocio = implode("|", $unidadenegocio);
        $whereUnid = "AND JSON_FIELD('unidadeNegocio',campos_adicionais) ~'{$unidade_negocio}'";

    }

    $cond_cliente_admin = ($areaAdminCliente == true)? " AND os.cliente_admin = $login_cliente_admin " : "" ;

    $areaAdminCliente = ($unidade_negocio == "6300" AND $areaAdminCliente !== true) ? true : $areaAdminCliente;

    $tabela = "tmp_os_indicadores_eficiencia_produtividade_{$login_admin}_{$login_fabrica}";
    
    $intevalReicidencia = ($areaAdminCliente == true)? "AND tbl_os.data_abertura > (os.data_abertura - INTERVAL '45 days')" : "AND tbl_os.data_abertura > (os.data_abertura - INTERVAL '90 days')" ;

    if ($login_fabrica == 158) {
      $cond_data                      = " AND rsl.create_at BETWEEN '$xxdata_inicial 00:00' and '$xxdata_final 23:59' ";
      $left_join_cockpit              = " LEFT JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado ";
      $left_join_routine_schedule_log = " LEFT JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log ";
    } else {
      $cond_data                       = " AND os.data_fechamento BETWEEN '$xxdata_inicial' and '$xxdata_final' ";
      $inner_join_cockpit              = "";
      $left_join_routine_schedule_log  = "";
    }

    if ($areaAdminCliente == true) {
        $sql = "
            SELECT
                os.os,
                --JSON_FIELD('reincidencia_reclamado', os.justificativa_adicionais) AS os_reincidente,
                CASE WHEN 
                  ( 
                    SELECT COUNT(1)
                        FROM tbl_os
                        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        WHERE tbl_os.fabrica = {$login_fabrica}
                        {$intevalReicidencia}
                        AND tbl_os.serie = os.serie
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_os.os < os.os
                        LIMIT 1
                    ) > 0
                THEN
                  true
                ELSE
                  false
                END AS os_reincidente,
                JSON_FIELD('unidadeNegocio',campos_adicionais)||' - '||unidade_negocio.nome AS unidade_negocio,
                f.descricao AS familia,
                (
                    CASE WHEN os.qtde_km <= 25 THEN
                        'Local'
                    WHEN os.qtde_km <= 75 THEN
                        'Foráneo'
                    ELSE
                        'Rural'
                    END
                ) AS regiao,
                os.data_digitacao AS data_os,
                EXTRACT(
                    EPOCH FROM ose.termino_atendimento - os.data_digitacao
                ) AS intervalo,
                (
                    select count(1) 
                        from fn_calendario(os.data_digitacao::date, ose.termino_atendimento::date) 
                        where nome_dia <> 'Domingo' 
                            and nome_dia !~ 'bado') - 1 - (select count(1) from tbl_feriado where data between os.data_digitacao::date and ose.termino_atendimento::date and fabrica = $login_fabrica AND ativo IS TRUE AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)) as intervalo_dia
                                    
            INTO TEMP TABLE {$tabela}
            FROM tbl_os os
                INNER JOIN tbl_os_extra ose ON ose.os = os.os
                INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
                INNER JOIN tbl_os_produto osp ON osp.os = os.os
                INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
                INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
                {$left_join_cockpit}
                {$left_join_routine_schedule_log}
                LEFT JOIN (
                    SELECT DISTINCT unidade_negocio, cidade
                    FROM tbl_distribuidor_sla
                    WHERE fabrica = {$login_fabrica}
                ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
                LEFT JOIN tbl_cidade unidade_negocio ON unidade_negocio.cidade = unidades.cidade
            WHERE os.fabrica = {$login_fabrica}
                --AND os.hd_chamado IS NOT NULL
                $cond_cliente_admin
                $cond_posto
                $cond
                {$whereUnid}
                AND os.finalizada IS NOT NULL
                {$cond_data}
                AND f.descricao NOT IN ('POST MIX', 'CHOPEIRA');

            SELECT COUNT(distinct os) FROM {$tabela};    ";
    } else {
		$sql = "
			SELECT os.os, osp.produto, data_digitacao, create_at, data_abertura, os.serie,osex.serie_justificativa, posto,os.qtde_km
			into temp tmp_indicadores_$login_admin
			from tbl_os os
			join tbl_os_produto osp using(os)
			JOIN tbl_os_extra osex ON os.os = osex.os
			JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado AND hdc.fabrica = $login_fabrica
			JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
			WHERE os.fabrica = $login_fabrica
			AND os.finalizada IS NOT NULL
		  $cond_posto
		  $cond
			$cond_data;

			create index tmp_os on tmp_indicadores_$login_admin(os);
			create index tmp_produot on tmp_indicadores_$login_admin(produto);

 SELECT distinct os.os
 into temp reinc_$login_admin 
 FROM tbl_os
 INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
 INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
 INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
 INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
 join tmp_indicadores_$login_admin  os on tbl_os.produto = os.produto and tbl_os_extra.serie_justificativa = os.serie_justificativa 
 
 WHERE tbl_os.fabrica = {$login_fabrica}
 {$intevalReicidencia}
 AND tbl_defeito_constatado.defeito_constatado_grupo isnull
 AND tbl_os_defeito_reclamado_constatado.defeito_constatado IN
 (
 SELECT defeito_constatado FROM tbl_os_defeito_reclamado_constatado WHERE os = os.os
 )
 AND tbl_os.excluida IS NOT TRUE
 AND tbl_os.os < os.os
 and tbl_os.data_abertura between ('$xxdata_inicial'::timestamp - INTERVAL '90 days') and '$xxdata_final';

			SELECT
              os.os,
              CASE WHEN 
                  (
			select count(*) from reinc_$login_admin where os = os.os  
                    ) > 0
                THEN
                  true
                ELSE
                  false
                END AS os_reincidente,
              JSON_FIELD('unidadeNegocio',campos_adicionais)||' - '||unidade_negocio.nome AS unidade_negocio,
              f.descricao AS familia,
              (
                  CASE WHEN os.qtde_km <= 25 THEN
                      'Local'
                  WHEN os.qtde_km <= 75 THEN
                      'Foráneo'
                  ELSE
                      'Rural'
                  END
              ) AS regiao,
              os.data_digitacao AS data_os,
              EXTRACT(EPOCH FROM ose.termino_atendimento - os.create_at) AS intervalo,
         (select count(1) from fn_calendario(os.create_at::date, ose.termino_atendimento::date) where nome_dia <> 'Domingo' and nome_dia !~ 'bado') - 1 - (select count(1) from tbl_feriado where data between os.create_at::date and ose.termino_atendimento::date and fabrica = $login_fabrica AND ativo IS TRUE AND DATE_PART('dow', tbl_feriado.data) NOT IN(0, 6)) as intervalo_dia
          INTO TEMP TABLE {$tabela}
          FROM tmp_indicadores_$login_admin os
          INNER JOIN tbl_os_extra ose ON ose.os = os.os
          INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
          INNER JOIN tbl_produto p ON p.produto = os.produto AND p.fabrica_i = {$login_fabrica}
          INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
          JOIN (
                  SELECT DISTINCT unidade_negocio, cidade
                  FROM tbl_distribuidor_sla
                  WHERE fabrica = {$login_fabrica}
              ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
          JOIN tbl_unidade_negocio unidade_negocio ON unidade_negocio.codigo = unidades.unidade_negocio
          WHERE  f.descricao NOT IN ('POST MIX', 'CHOPEIRA')
          {$whereUnid} ;

		  SELECT COUNT(distinct os) FROM {$tabela};
      ";
    }
    //die(nl2br($sql));
    $res = pg_query($con, $sql);

    $TOTALOS = pg_fetch_result($res, 0, 0);

    if (!pg_fetch_result($res, 0, 0)) {
        exit(json_encode(array("alerta" => utf8_encode("Nenhum resultado encontrado"))));
    }

    /*$sql = "
        SELECT
            familia,
            regiao,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '$data_inicial 00:00' and '$data_final 23:59'
        AND regiao = 'Local'
        GROUP BY familia, regiao

        UNION
        SELECT
            familia,
            'Geral' AS regiao,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '$data_inicial 00:00' and '$data_final 23:59'
        GROUP BY familia

        UNION
        SELECT
            familia,
            regiao,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.regiao = {$tabela}.regiao
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '$data_inicial 00:00' and '$data_final 23:59'
        AND regiao = 'Local'
        GROUP BY familia, regiao

        UNION
        SELECT
            familia,
            'Geral' AS regiao,
            AVG(intervalo) AS intervalo,
            COUNT(os) AS qtde_os,
            CASE WHEN familia = 'REFRIGERADOR' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 24
                )
            WHEN familia = 'POST MIX' OR familia = 'CHOPEIRA' THEN
                (
                    SELECT
                        COUNT(x.os)
                    FROM {$tabela} x
                    WHERE x.familia = {$tabela}.familia
                    AND x.data_os between '$data_inicial 00:00' and '$data_final 23:59'
                    AND (x.intervalo / 3600) <= 3
                )
            END AS eficiencia
        FROM {$tabela}
        WHERE data_os between '$data_inicial 00:00' and '$data_final 23:59'
        GROUP BY familia

        ORDER BY familia ASC, regiao ASC
    ";
    $res = pg_query($con, $sql);

    $resultado     = array();
    $produtividade = array();

    array_map(function($r) {
        global $resultado, $produtividade;

        if (!isset($resultado[$r["familia"]][$r["data"]])) {
            $resultado[$r["familia"]][$r["data"]] = array(
                "Local"   => array(),
                "Geral"   => array()
            );
        }

        $resultado[$r["familia"]][$r["data"]][$r["regiao"]] = array(
            "intervalo"  => $r["intervalo"],
            "eficiencia" => $r["eficiencia"],
            "qtde_os"    => $r["qtde_os"]
        );

        if ($r["regiao"] != "Local") {
            $produtividade[$r["familia"]] += $r["qtde_os"];
        }
    }, pg_fetch_all($res));

    $qtd_meses = 0;
    foreach ($resultado as $familia => $f) {
        foreach ($f as $data => $d) {
            if ($data <= 12) { $qtd_meses += 1; }
        }
        break;
    }*/

    $familias = array('REFRIGERADOR','VENDING MACHINE') ;
    if ($areaAdminCliente == true) {
      $familias = array('REFRIGERADOR') ;
    }
    
    foreach($familias as $familia) {
        $TOTALOSREINCIDENTE = 0;
        $TOTALOSCHAMADO = 0;
        $TOTALFAMILIA = 0;
        $resultado3 = array();

        if(in_array($familia, $familias) ) {
            $campo = " case when intervalo_dia <= 1 then 'D+1 (70% META do contrato)'
                         when intervalo_dia between 1 and 2 then 'D+2 (20% META do contrato)'
                         when intervalo_dia between 2 and 3 then 'D+3 (10% META do contrato)'
                         else 'ACIMA DE D+3 (0% META do contrato)' end
                         AS tempo ,
                     case when intervalo_dia <= 1 then '1'
                         when intervalo_dia between 1 and 2 then '2'
                         when intervalo_dia between 2 and 3 then '3'
                         else '4' end
                     AS ordem";
            if ($areaAdminCliente == true) {
                $campo = " case when intervalo_dia <= 1 then 'D+1 (80% META do contrato)'
                         when intervalo_dia between 1 and 2 then 'D+2 (15% META do contrato)'                         
                         else 'ACIMA DE D+2 (5% META do contrato)' end
                         AS tempo ,
                     case when intervalo_dia <= 1 then '1'
                         when intervalo_dia between 1 and 2 then '2'
                         else '3' end
                     AS ordem";
            }
        }else{
            $campo = " case when (intervalo/3600) between 0 and 3 then 'ATE 3 HORAS (90% META do contrato)'
                when (intervalo/3600) between 3 and 6 then 'ATE 6 HORAS (5% META do contrato)'
                when (intervalo/3600) between 6 and 24 then 'ATE 24 HORAS (5% META do contrato)'
                            else 'ACIMA DE 24 HORAS (0% META do contrato)' end
                        AS tempo,
                        case when (intervalo/3600) between 0 and 3 then '1'
                            when (intervalo/3600) between 3 and 6 then '2'
                            when (intervalo/3600) between 6 and 24 then '3'
                            else '3' end
                        AS ordem ";
        }
        $sql = " SELECT
                    count(distinct os) as total,
                    $campo
                    FROM $tabela
                    WHERE familia = '$familia'
                    group by tempo, ordem
                    order by ordem";
        $res = pg_query($con,$sql);
        $total = 0;
        $resultado2 = array();
        array_map(function($r) {
            global $resultado2;

            $resultado2[$r["tempo"]] = array(
                "total"    => (int)$r["total"]
            );

    }, pg_fetch_all($res));

        foreach($resultado2 as $r1 => $v1) {
            $total += $v1['total'];
        }

        $sqlOSRe = "SELECT COUNT(distinct os) FROM {$tabela} WHERE os_reincidente IS TRUE AND familia = '{$familia}';";
        $resOSRe = pg_query($con, $sqlOSRe);
        $TOTALOSREINCIDENTE = pg_fetch_result($resOSRe, 0, 0);

        $sqlOS = "SELECT COUNT(distinct os) FROM {$tabela} WHERE os_reincidente IS FALSE AND familia = '{$familia}';";
        $resOS = pg_query($con, $sqlOS);
        $TOTALOSCHAMADO = pg_fetch_result($resOS, 0, 0);

        $sqlOSTot = "SELECT COUNT(distinct os) FROM {$tabela} WHERE familia = '{$familia}';";
        $resOSTot = pg_query($con, $sqlOSTot);
        $TOTALFAMILIA = pg_fetch_result($resOSTot, 0, 0);

        $resultado3 = array("TOTALCHAMADO" => $TOTALOSCHAMADO, "TOTALFAMILIA" => $TOTALFAMILIA, "TOTALOSREINCIDENDE" => $TOTALOSREINCIDENTE);

        $contador = 1;
        $retorno .= '<table class="table table-bordered">';
        $retorno .= '<thead>';
        $retorno .= '<tr class="titulo_coluna" >';
        $retorno .= '<th>Família</th>';
        $retorno .= '<th>Eficiência</th>';
        $retorno .= "<th>Período de {$data_inicial} até {$data_final}</th>";
        $retorno .= '</tr>';
        $retorno .= '</thead>';
        $retorno .= '<tbody>';
        $retorno .= '<tr>';
        $retorno .= "<td rowspan='5' style='vertical-align: middle; text-align: center;'' nowrap >{$familia}</td>";
        $retorno .= '</tr>';
        foreach($resultado2 as $k1 => $v1) {
            $total_aux = 0;
            $contadorAux = 1;
            $retorno .= "<tr><td>$k1</td>";
            $retorno .= "<td class='tac' >".round($v1['total']/$total*100,2)."%</td>";
            while ($contador > $contadorAux) {
                $retorno .= "<td class='tac'></td>";
                $contadorAux += 1;
            }
            $retorno .= "</tr>";
        }
        $retorno .= '</tbody>';
        $retorno .= '</table>';
        $retorno .= '<table class="table table-bordered" >';
        $retorno .= '<thead>';
        $retorno .= '<tr class="titulo_coluna" >';
        $retorno .= '<th>Família</th>';
        $retorno .= '<th>Volume</th>';
        $retorno .= "<th>Período de {$data_inicial} até {$data_final}</th>";
        $retorno .= '</tr>';
        $retorno .= '</thead>';
        $retorno .= '<tbody>';
        $retorno .= '<tr>';
        $retorno .= "<td rowspan='6' style='vertical-align: middle; text-align: center;' nowrap >{$familia}</td>";
        $retorno .= '</tr>';
        $total_aux = 0;
        
        foreach($resultado2 as $k1 => $v1) {
            $contadorAux = 1;
            $retorno .= "<tr><td>$k1</td>";
            $retorno .= "<td class='tac' >".$v1['total']."</td>";
            $total_aux += $v1['total'];
            /*while ($contador > $contadorAux) {
                $retorno .= "<td class='tac'></td>";
                $contadorAux += 1;
            }*/
            $retorno .= "</tr>";
        }

        $contadorAux = 0;
        $retorno .= '<tr><td> Total </td>';
        $retorno .= "<td class='tac' > {$total_aux} </td>";
        /*while ($contador > $contadorAux) {
            $retorno .= "<td class='tac'></td>";
            $contadorAux += 1;
        }*/
        $retorno .= '</tr>';
        $retorno .= '</tbody>';
        $retorno .= '</table>';
        $retorno .= '<table class="table table-bordered">';
        $retorno .= '<thead>';
        $retorno .= '<tr class="titulo_coluna" >';
        $retorno .= '<th width="21%">Família</th>';
        $retorno .= '<th>Quantidade</th>';
        $retorno .= "<th width='38%''>Período de {$data_inicial} até {$data_final}</th>";
        $retorno .= '</tr>';
        $retorno .= '</thead>';
        $retorno .= '<tbody>';
        $retorno .= '<tr>';
        $retorno .= "<td rowspan='5' style='vertical-align: middle; text-align: center;'' nowrap >{$familia}</td>";
        $retorno .= '</tr>';
        $retorno .= "<tr><td>Total de OS</td>";
        $retorno .= "<td class='tac' >".$resultado3["TOTALFAMILIA"]."</td></tr>";
        $retorno .= "<tr><td>1º Chamado</td>";
        $retorno .= "<td class='tac' >".$resultado3["TOTALCHAMADO"]."</td></tr>";
        $retorno .= "<tr><td>Reincidencia</td>";
        $retorno .= "<td class='tac' >".$resultado3["TOTALOSREINCIDENDE"]."</td></tr>";
        $retorno .= "<tr><td>% de Reincidencia</td>";
        $retorno .= "<td class='tac' >".round($resultado3["TOTALOSREINCIDENDE"]/$resultado3["TOTALFAMILIA"]*100,2)."%</td></tr>";
        $retorno .= '</tbody>';

        $retorno .= '</table>';
        $retorno .= '</tbody>';
        $retorno .= '</table>';
   }
    $parametros = json_encode(
                            array(
                                'CSV' => '1',
                                'data_inicial' => $data_inicial,
                                'data_final' => $data_final,
                                'tipo_atendimento' => $tipo_atendimento,
                                'posto' => $posto,
                                'unidade_negocio' => $_POST['unidade_negocio']
                            )
                        );
    exit(json_encode(array("ok" => utf8_encode($retorno),"json" => utf8_encode($parametros))));
}
include "cabecalho_new.php";

$plugins = array(
    "select2",
    "autocomplete",
    "shadowbox",
    "mask"
);

include ADMCLI_BACK."plugin_loader.php";

?>
<div class="row-fluid>">
	<? if($areaAdminCliente != true) { ?>
    <?php if ($login_fabrica == 158) { ?>
      <div class="alert">
        Consultas Exclusivas para Unidades de Negócio KOF 
      </div>
      <div class="alert">
        O cálculo de SLA no ambiente ADM é exclusivo para avaliar as Regiões de Atendimento KOF, pelo qual os parâmetros são DATA DE INTEGRAÇÃO (momento que o chamado integra no nosso sistema) e DATA FIM ATENDIMENTO (momento que o Posto Autorizado encerra o chamado)
      </div>
    <?php } else { ?>
      <div class="alert">    
          Para a unidade de negocio <strong>6300 - BEBIDAS FRUKI</strong>, realizar a consulta individualmente!
      </div>
    <?php } ?>
	<? } ?>
</div>

<form method="POST" class="form-search form-inline tc_formulario no-print" id="formPesquisa">
    <div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>
    <div id="Alerta" class="alert" style="display: none;"><h4></h4></div>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span3"></div>
        <div class="span3">
            <div class="control-group" id="gDtInicial">
                <label class="control-label" for="data_inicial">Data Inicial</label>
                <div class="controls controls-row">
                    <div class="span8  ">
                        <h5 class="asteristico">*</h5>
                        <input id="data_inicial" name="data_inicial" class="span12 " maxlength="30" value="" type="text">
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
                        <input id="data_final" name="data_final" class="span12 " maxlength="30" value="" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class='span3'></div>
    </div>
    <?php
    if ($areaAdminCliente != true) {?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <?php if ($login_fabrica == 158) { ?>
        <div class="span4" >
            <div class='control-group'>
                <label class='control-label' for='unidade_negocio'>Unidade de Negócio:</label>
                <div class='controls controls-row'>
                    <select id="unidade_negocio" name='unidade_negocio[]' class='span12' multiple="multiple">
                        <option value=''>Selecione</option>
                            <?php

                                $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

                                foreach ($distribuidores_disponiveis as $unidadeNegocio) {
                                    if (in_array($unidadeNegocio["unidade_negocio"], array(6102,6103,6104,6105,6106,6107,6108,6300,6400,6700,6800,7100,7200))) {
                                        unset($unidadeNegocio["unidade_negocio"]);
                                        continue;
                                    }
                                    $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
                                }

                                foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                                    $selected = (in_array($unidade, getValue("os[unidade_negocio]"))) ? 'SELECTED' : '';
                            ?>
                                <option value="<?= $unidade; ?>" <?= $selected; ?>><?= $descricaoUnidade; ?></option>
                            <?php } ?>
                    </select>
                </div>
            </div>
        </div>
        <?php } ?>
        <div class="span4">
            <div class="control-group ">
                <label class="control-label" for="tipo_atendimento">Tipo Atendimento</label>
                <div class="controls controls-row">
                    <div class="span8 ">
                        <select id="tipo_atendimento" name="tipo_atendimento[]" class="span12 " multiple="multiple">
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
        <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='codigo_posto'>Cod. Posto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
                        <input type="hidden" name="posto" value="<?=$posto?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='descricao_posto'>Nome Posto </label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <?php
    } ?>

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
    $.datepickerLoad(Array("data_final", "data_inicial"));
    Shadowbox.init();
    $.autocompleteLoad(Array("posto"));

    $("span[rel=lupa]").click(function () { $.lupa($(this));});
    $("#unidade_negocio").select2();
    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }


CarregaTipoAtendimento();
$("#tipo_atendimento").select2();

$('#bt_cad_forn').on('click', function(){
    var data_inicial = $('#data_inicial').val();
    var data_final = $('#data_final').val();
    var tipo_atendimento = $('#tipo_atendimento').val();
    var unidade_negocio = $('#unidade_negocio').val();
    var codigo_posto = $('#codigo_posto').val();
    var posto_nome = $('#descricao_posto').val();

    Limpar();

    $.ajax({
        url: window.location,
        method: 'POST',
        data: {ajax: 'sim', codigo_posto:codigo_posto, posto_nome:posto_nome,data_inicial: data_inicial, data_final: data_final, tipo_atendimento: tipo_atendimento,unidade_negocio:unidade_negocio},
        beforeSend: function(){
            $('#loading').show();
        }
    }).fail(function(){
        $('#alertaErro').show().find('h4').html('Não foi possível realizar a pesquisa, tempo esgotado!');
    }).done(function(data){
        data = JSON.parse(data);
        if (data.error !== undefined) {
            if (data.campo !== undefined) {
                if (data.campo == 'data_inicial') {
                    $('#gDtInicial').addClass('error');
                }else{
                    if (data.campo == 'data_inicial') {
                        $('#gDtFinal').addClass('error');
                    }else{
                        if (data.campo == 'datas') {
                            $('#gDtInicial').addClass('error');
                            $('#gDtFinal').addClass('error');
                        }
                    }
                }
            }
            $('#alertaErro').show().find('h4').html(data.error);
        }
        if (data.alerta !== undefined) {
            $('#Alerta').show().find('h4').html(data.alerta);
        }
        if (data.ok !== undefined) {
            $('#resultPesquisa').html(data.ok);
            $('#jsonPOST').attr('value', data.json);
            $('#gerar_excel').show();
        }
        $('#loading').hide();
    });
});

$('#btnLimpar').on('click', function(){
    $('#formPesquisa').each(function(){
        this.reset();
    });
    Limpar();
});

function CarregaTipoAtendimento(){
    $.ajax({
        url: window.location,
        method: 'POST',
        data: {ajax: 'sim', tipo_atendimento: 'sim'},
        timeout: 5000
    }).fail(function(){
        $('#alertaErro').show().find('h4').html('Não foi possível carregar os tipos de atendimento, tempo esgotado!');
    }).done(function(data){
        data = JSON.parse(data);
        if (data.ok !== undefined) {
            $('#tipo_atendimento').html(data.ok);
        }
    });
}

function Limpar(){
    $('#gDtInicial').removeClass('error');
    $('#gDtFinal').removeClass('error');
    $('#resultPesquisa').html('');
    $('#gerar_excel').hide();
    $('#alertaErro').hide();
    $('#Alerta').hide();
}
</script>
