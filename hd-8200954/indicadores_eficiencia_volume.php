<?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';

include "funcoes.php";
use Posvenda\DistribuidorSLA;

$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);
$distribuidores = $oDistribuidorSLA->SelectUnidadeNegocio();

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
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];
    $unidadenegocio = $_POST['unidadenegocio'];
    if (is_array($unidadenegocio) && count($unidadenegocio) > 0) {

	if (in_array("6101", $unidadenegocio)) {
	    array_push($unidadenegocio, 6102,6103,6104,6105,6106,6107,6108);
	}

	foreach ($unidadenegocio as $key => $value) {
	    $unidade_negocios[] = "'$value'";
	}
	$cond = "AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) IN (".implode(',', $unidade_negocios).")";
    }

    $dataInicial = explode("/", $data_inicial);
    $dataInicial = $dataInicial[2] . "-" . $dataInicial[1] . "-" . $dataInicial[0];

    $dataFinal = explode("/", $data_final);
    $dataFinal = $dataFinal[2] . "-" . $dataFinal[1] . "-" . $dataFinal[0];

    #if ($login_fabrica == 158) {
    #    $cond_data = "AND tbl_os.data_abertura BETWEEN '$data_inicial 00:00' AND '$data_final 23:59' ";
    #} else {
        $cond_data = " AND tbl_os.data_fechamento BETWEEN '$dataInicial 00:00' AND '$dataFinal 23:59' ";
    #}

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
                TO_CHAR(tbl_os.data_hora_abertura,'DD/MM/YYYY HH24:MI:SS') AS abertura , 
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
                JSON_FIELD('unidadeNegocio',tbl_os_campo_extra.campos_adicionais) AS unidade_negocio, 
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
                ARRAY_TO_STRING(ARRAY( SELECT defeito_constatado FROM tbl_os_defeito_reclamado_constatado osdc WHERE osdc.fabrica = 158 AND osdc.os = tbl_os.os AND osdc.defeito_constatado IS NOT NULL ), ', ') AS ids_defeitos_constatados, 
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
                case when tbl_familia.descricao = 'REFRIGERADOR' then
                    case when (
                     select count(1)
                       from fn_calendario(tbl_os.data_abertura::date, tbl_os_extra.termino_atendimento::date )
                      where nome_dia <> 'Domingo'
                        and nome_dia !~ 'bado') - 1 - (
                     select count(1)
                       from tbl_feriado
                      where data between tbl_os.data_abertura and tbl_os_extra.termino_atendimento::date
                        and fabrica  =  $login_fabrica
                        and DATE_PART('dow', tbl_feriado.data) not in(0, 6)
                        and ativo is true
                    ) <= 1
                    then 'D+1'
                    when (
                        select count(1) 
                        from fn_calendario(
                            tbl_os.data_abertura::date, 
                            tbl_os_extra.termino_atendimento::date
                        ) 
                        where nome_dia <> 'Domingo' 
                        and nome_dia !~ 'bado'
                    ) - 1 - (
                        select count(1) 
                        from tbl_feriado 
                        where data between tbl_os.data_abertura and tbl_os_extra.termino_atendimento::date
                        and date_part('dow', data) not in(0, 6)
                        and fabrica = $login_fabrica
                        and ativo is true
                    ) between 1 and 2 
                    then 'D+2'
                    else
                        'ACIMA DE D+2'
                    end
                else
					case 
					when (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 between 0 and 3 then 'ATÉ 3 HORAS'
					when (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 between 3 and 6 then 'ATÉ 6 HORAS'
                    when (EXTRACT(EPOCH FROM tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 between 6 and 24 then 'ATÉ 24 horas'
                    else
                        'ACIMA DE 24 HORAS'
                    end
				end as SLA,
                (extract(epoch from tbl_os_extra.termino_atendimento - tbl_os.data_digitacao))/3600 as horas_diferenca,
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
            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento and tbl_tipo_atendimento.fabrica = {$login_fabrica} 
            JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto 
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = {$login_fabrica} 
            JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = {$login_fabrica} 
            JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = {$login_fabrica} 
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.i_fabrica = {$login_fabrica} 
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica and tbl_fabrica.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os and tbl_hd_chamado_extra.posto = tbl_os.posto 
            LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado AND tbl_hd_chamado.titulo <> 'Help-Desk Posto' AND tbl_hd_chamado.posto IS NULL 
            JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os 
            LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_distribuidor_sla_posto ON tbl_distribuidor_sla_posto.posto = tbl_posto_fabrica.posto AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_distribuidor_sla ON tbl_os_campo_extra.campos_adicionais LIKE '%unidadeNegocio%' || tbl_distribuidor_sla.unidade_negocio || '%' AND tbl_distribuidor_sla.fabrica = {$login_fabrica} AND tbl_distribuidor_sla.distribuidor_sla = tbl_distribuidor_sla_posto.distribuidor_sla 
            LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_distribuidor_sla.cidade 
            LEFT JOIN tbl_posto_distribuidor_sla_default ON tbl_posto_distribuidor_sla_default.posto = tbl_posto_fabrica.posto AND tbl_posto_distribuidor_sla_default.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_distribuidor_sla distribuidor_principal ON distribuidor_principal.distribuidor_sla = tbl_posto_distribuidor_sla_default.distribuidor_sla AND distribuidor_principal.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_cidade regiao_distribuidor_principal ON regiao_distribuidor_principal.cidade = distribuidor_principal.cidade 
            JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica} 
            JOIN tbl_routine_schedule_log ON tbl_routine_schedule_log.routine_schedule_log = tbl_hd_chamado_cockpit.routine_schedule_log 
            LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = {$login_fabrica} 
            WHERE tbl_os.fabrica = {$login_fabrica} {$cond}
                AND tbl_familia.codigo_familia != '05'
                AND tbl_os.posto = $login_posto
                {$cond_data}
		AND tbl_os.finalizada IS NOT NULL";

    $dataArquivo = date("Ymdhis");
    $arquivo_nome = "indicadorEficiencia{$dataArquivo}.csv";
    $file     = "xls/{$arquivo_nome}";
    $fileTemp = "/tmp/{$arquivo_nome}";
    $fp     = fopen($fileTemp,'w');

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
    
    $quebra_linha = array("\n", "<br>", "\nr", "</br>", "  ", ";");

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
        $xreincidencia =  "";

        $cor   = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
        $os                 =  trim(pg_fetch_result($resxls,$x,os));
        $os_reincidente     =  trim(pg_fetch_result($resxls,$x,os_reincidente));
        $sua_os             = trim(pg_fetch_result($resxls,$x,sua_os));
        $hd_chamado         = trim(pg_fetch_result($resxls,$x,hd_chamado));
        $cidade_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_cidade));
        $estado_posto_xls   = trim(pg_fetch_result($resxls,$x,contato_estado));
        $cidade_uf          = $cidade_posto_xls."/".$estado_posto_xls;
        $nota_fiscal        = trim(pg_fetch_result($resxls,$x,nota_fiscal));
        $digitacao          = trim(pg_fetch_result($resxls,$x,digitacao));
        $abertura           = trim(pg_fetch_result($resxls,$x,abertura));
        $consumidor_revenda = trim(pg_fetch_result($resxls,$x,consumidor_revenda));
        $fechamento         = trim(pg_fetch_result($resxls,$x,fechamento));
        $finalizada         = trim(pg_fetch_result($resxls,$x,finalizada));
        $data_conserto      = trim(@pg_fetch_result($resxls,$x,data_conserto));
        $serie              = trim(pg_fetch_result($resxls,$x,serie));
        $type              = trim(pg_fetch_result($resxls,$x,type));
        $reincidencia       = trim(pg_fetch_result($resxls,$x,reincidencia));
        $consumidor_nome    = trim(pg_fetch_result($resxls,$x,consumidor_nome));
        $excluida           = trim(pg_fetch_result($resxls,$x,excluida));
        $consumidor_fone    = trim(pg_fetch_result($resxls,$x,consumidor_fone));
        $data_nf            = trim(pg_fetch_result($resxls,$x,data_nf));
        $codigo_posto       = trim(pg_fetch_result($resxls,$x,codigo_posto));
        $posto_nome         = trim(pg_fetch_result($resxls,$x,posto_nome));
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
	$xUnidadeNegocio = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null,null,$unidade_negocio);
	$xxUnidadeNegocio = $xUnidadeNegocio[0]["cidade"];
        $distribuidor_principal        = pg_fetch_result($resxls, $x, "distribuidor_principal");
        $defeito_reclamado             = pg_fetch_result($resxls, $x, "defeito_reclamado");
        $inicio_atendimento            = pg_fetch_result($resxls, $x, "inicio_atendimento");
        $termino_atendimento           = pg_fetch_result($resxls, $x, "termino_atendimento");
        $admin_nome                    = pg_fetch_result($resxls, $x, "admin_nome");
        $regiao_distribuidor_principal = pg_fetch_result($resxls, $x, "regiao_distribuidor_principal");
        $defeitos_constatados          = pg_fetch_result($resxls, $x, "defeitos_constatados");
        $ids_defeitos_constatados          = pg_fetch_result($resxls, $x, "ids_defeitos_constatados");
        $solucoes                      = pg_fetch_result($resxls, $x, "solucoes");
        $horas_diferenca               = pg_fetch_result($resxls, $x, "horas_diferenca");
        $horas_diferenca               = round($horas_diferenca,2);
        $tempo_para_defeito = trim(pg_fetch_result($resxls,$x,tempo_para_defeito));
        $data_saida = pg_fetch_result($resxls, $x, "exportado");        
        $sla = pg_fetch_result($resxls, $x, "SLA");
        $tipo_posto = pg_fetch_result($resxls, $x, "tipo_posto");
        $classificacao = pg_fetch_result($resxls, $x, "classificacao");

	$sql = "SELECT tbl_os.os
                        FROM tbl_os
                        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                        INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
                        LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND lower(tbl_defeito_constatado_grupo.descricao) = 'improdutivo'
                        WHERE tbl_os.fabrica = {$login_fabrica}
                        AND tbl_defeito_constatado_grupo.defeito_constatado_grupo IS NULL
                        AND tbl_os.data_abertura > ('$abertura'::date - INTERVAL '90 days') 
						AND tbl_os_produto.serie = '{$serie}'	
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os_defeito_reclamado_constatado.defeito_constatado in ($ids_defeitos_constatados)
                        AND tbl_os.posto = $login_posto
                        AND tbl_os.os < {$os}
                        ORDER BY tbl_os.data_abertura DESC
                        LIMIT 1";
	$resR = pg_query($con,$sql);

        if (pg_num_rows($resR) > 0) {
		$xreincidencia = "Sim";
     		$os_reincidente = pg_fetch_result($resR,0,'os');
        }else{
            $xreincidencia =  "Não";
			$os_reincidente = "";
        }


        $body  = '"' . str_replace($quebra_linha, "", $xxUnidadeNegocio) . '";';
        $body .= "{$sua_os};";
        $body .= "{$xreincidencia};";
        $body .= "{$os_reincidente};";
        $body .= "{$cliente_admin_nome};";
        $body .= "{$serie_justificativa};";
        $body .= "{$serie};";
        $body .= "{$abertura};";
        $body .= "{$data_conserto};";
        $body .= "{$fechamento};";
        $body .= "{$json_kof['dataAbertura']};";
        $body .= "{$inicio_atendimento};";
        $body .= "{$termino_atendimento};";
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
        $body .= '"' . str_replace($quebra_linha, "", $consumidor_endereco)                    . '";';
        $body .= '"' . str_replace($quebra_linha, "", $consumidor_bairro)                      . '";';
        $body .= '"' . str_replace($quebra_linha, "", $consumidor_cidade)                      . '";';
        $body .= "{$consumidor_estado};";
        $body .= "{$json_kof['idCliente']};";
        $body .= '"' . str_replace($quebra_linha, "", $nome_consumidor_revenda)                . '";';
        $body .= "{$consumidor_fone};";
        $body .= '"' . str_replace($quebra_linha, "", $descricao_tipo_atendimento)             . '";';
        $body .= '"' . str_replace($quebra_linha, "", $defeito_reclamado)                      . '";';
        $body .= '"' . str_replace($quebra_linha, "", $defeitos_constatados)                   . '";';
        $body .= '"' . str_replace($quebra_linha, "", $solucoes)                               . '";';
        $body .= "{$classificacao};";
        $body .= '"' . str_replace($quebra_linha, "", $obs)                                    . '";';
        $body .= '"' . str_replace($quebra_linha, "", $json_kof['comentario'])                 . '";';
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
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];
    $unidadenegocio = $_POST['unidadenegocio'];


    if (is_array($unidadenegocio) && count($unidadenegocio) > 0) {

        if (in_array("6101", $unidadenegocio)) {
            array_push($unidadenegocio, 6102,6103,6104,6105,6106,6107,6108);
        }

        foreach ($unidadenegocio as $key => $value) {
            $unidade_negocios[] = "'$value'";
        }
        $cond = "AND JSON_FIELD('unidadeNegocio', osce.campos_adicionais) IN (".implode(',', $unidade_negocios).")";
    }

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

    $xdata_inicial = $xdata_inicial[2]."-".$xdata_inicial[1]."-".$xdata_inicial[0];    
    $xdata_final   = $xdata_final[2]."-".$xdata_final[1]."-".$xdata_final[0];

    $xdata_inicial = new DateTime($xdata_inicial);
    $xdata_final   = new DateTime($xdata_final);

    $dias = $xdata_inicial->diff($xdata_final)->days;

    if($dias > 180) {
        exit(json_encode(array("error" => utf8_encode("Intervalo de data não pode ser maior que 180 dias"))));
    }

    $dataInicial = explode("/", $data_inicial);
    $dataInicial = $dataInicial[2] . "-" . $dataInicial[1] . "-" . $dataInicial[0];

    $dataFinal = explode("/", $data_final);
    $dataFinal = $dataFinal[2] . "-" . $dataFinal[1] . "-" . $dataFinal[0];

    #if ($login_fabrica == 158) {
    #    $cond_data = "AND tbl_os.data_abertura BETWEEN '$data_inicial 00:00' AND '$data_final 23:59' ";
    #} else {
        $cond_data = " AND os.data_fechamento BETWEEN '$dataInicial 00:00' AND '$dataFinal 23:59' ";
    #}

    $tabela = "tmp_os_indicadores_eficiencia_produtividade_{$login_posto}_{$login_fabrica}";        
    $sql = "
        SELECT
            os.os,
	    osp.serie,
	     (SELECT tbl_os.os
                        FROM tbl_os
                        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                        INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
                        WHERE tbl_os.fabrica = {$login_fabrica}
                        AND tbl_os.data_abertura > (os.data_abertura - INTERVAL '90 days') 
						AND tbl_os_produto.serie =  osp.serie
                        AND tbl_defeito_constatado.defeito_constatado_grupo isnull
						AND tbl_os_defeito_reclamado_constatado.defeito_constatado IN
                        (
                          SELECT defeito_constatado FROM tbl_os_defeito_reclamado_constatado WHERE os = os.os and defeito_constatado notnull
                        )
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_os.posto = $login_posto
                        AND tbl_os.os < os.os
                        ORDER BY tbl_os.data_abertura DESC
                        LIMIT 1) AS os_reincidente,
            os.posto,
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
            EXTRACT(EPOCH FROM ose.termino_atendimento - os.data_abertura) AS intervalo,
			 (select count(1) from fn_calendario(os.data_abertura::date, ose.termino_atendimento::date ) where nome_dia <> 'Domingo' and nome_dia !~ 'bado') - 1 - (select count(1) from tbl_feriado where data between os.data_abertura and ose.termino_atendimento::date and fabrica = $login_fabrica and ativo and date_part('dow', data) not in(0, 6)) as intervalo_dia
                        
        INTO TEMP TABLE {$tabela}
        FROM tbl_os os
        INNER JOIN tbl_os_extra ose ON ose.os = os.os
        INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
        INNER JOIN tbl_os_produto osp ON osp.os = os.os
        INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
        INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
        INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
        INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
        LEFT JOIN (
                SELECT DISTINCT unidade_negocio, cidade
                FROM tbl_distribuidor_sla
                WHERE fabrica = {$login_fabrica}
            ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
        LEFT JOIN tbl_cidade unidade_negocio ON unidade_negocio.cidade = unidades.cidade
        WHERE os.fabrica = {$login_fabrica}
        AND os.hd_chamado IS NOT NULL
        AND os.posto = $login_posto
        $cond
		AND os.finalizada IS NOT NULL
		{$cond_data}
		AND f.descricao NOT IN ('POST MIX', 'CHOPEIRA');

        SELECT COUNT(distinct os) FROM {$tabela};
	";

	$res = pg_query($con, $sql);
    $TOTALOS = pg_fetch_result($res, 0, 0);

    if (!pg_fetch_result($res, 0, 0)) {
        exit(json_encode(array("alerta" => utf8_encode("Nenhum resultado encontrado"))));
    }

    $familias = array('REFRIGERADOR') ;
    foreach($familias as $familia) {
        $TOTALOSREINCIDENTE = 0;
        $TOTALOSCHAMADO = 0;
        $resultado3 = array();

		if($familia == 'REFRIGERADOR' || $familia == 'VENDING MACHINE') {
			$campo = " case when intervalo_dia <= 1 then 'D+1 (80% META do contrato)'
						 when intervalo_dia between 1 and 2 then 'D+2 (15% META do contrato)'
						 else 'ACIMA DE D+2 (5% META do contrato)' end
						 AS tempo ,
					 case when intervalo_dia <= 1 then '1'
						 when intervalo_dia between 1 and 2 then '2'
						 else '3' end
				     AS ordem";

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

        $sqlOSRe = "SELECT COUNT(distinct os) FROM {$tabela} WHERE os_reincidente notnull AND familia = '$familia';";
        $resOSRe = pg_query($con, $sqlOSRe);
        $TOTALOSREINCIDENTE = pg_fetch_result($resOSRe, 0, 0);

        $sqlOS = "SELECT COUNT(distinct os) FROM {$tabela} WHERE os_reincidente isnull AND familia = '$familia';";
        $resOS = pg_query($con, $sqlOS);
        $TOTALOSCHAMADO = pg_fetch_result($resOS, 0, 0);

        $resultado3 = array("TOTALCHAMADO" => $TOTALOSCHAMADO, "TOTALOS" => $TOTALOS, "TOTALOSREINCIDENDE" => $TOTALOSREINCIDENTE);

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
        $retorno .= "<td class='tac' >".$resultado3["TOTALOS"]."</td></tr>";                                
        $retorno .= "<tr><td>1º Chamado</td>";            
        $retorno .= "<td class='tac' >".$resultado3["TOTALCHAMADO"]."</td></tr>";                                
        $retorno .= "<tr><td>Reincidencia</td>";            
        $retorno .= "<td class='tac' >".$resultado3["TOTALOSREINCIDENDE"]."</td></tr>";                                
        $retorno .= "<tr><td>% de Reincidencia</td>";            
        $retorno .= "<td class='tac' >".round($resultado3["TOTALOSREINCIDENDE"]/$resultado3["TOTALOS"]*100,2)."%</td></tr>";                                
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
                                'unidadenegocio' => $unidadenegocio
                            )
                        );
    exit(json_encode(array("ok" => utf8_encode($retorno),"json" => utf8_encode($parametros))));
}
include_once __DIR__.'/cabecalho_new.php';

$plugins = array(
    "select2",
    "autocomplete",
    "shadowbox",
    "mask"
);

include __DIR__."/admin/plugin_loader.php";

?>

<div class="row-fluid>">
    <?php if ($login_fabrica == 158) { ?>
      <div class="alert">
        O cálculo de SLA no ambiente do Posto Autorizado é exclusivo para avaliar o desempenho dos atendimentos, pelo qual os parâmetros são DATA AB (momento que o chamado integra no sistema do Posto Autorizado) e DATA FIM ATENDIMENTO (momento que o Posto Autorizado encerra o chamado)
      </div>
    <?php } ?>
</div>

<form method="POST" class="form-search form-inline tc_formulario no-print" id="formPesquisa">
    <div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>
    <div id="Alerta" class="alert" style="display: none;"><h4></h4></div>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span2">
            <div class="control-group" id="gDtInicial">
                <label class="control-label" for="data_inicial">Data Inicial</label>
                <div class="controls controls-row">
                    <div class="span9">
                        <h5 class="asteristico">*</h5>
                        <input id="data_inicial" name="data_inicial" class="span12 " maxlength="30" value="" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group" id="gDtFinal">
                <label class="control-label" for="data_final">Data Final</label>
                <div class="controls controls-row">
                    <div class="span9 ">
                        <h5 class="asteristico">*</h5>
                        <input id="data_final" name="data_final" class="span12 " maxlength="30" value="" type="text">
                    </div>
                </div>
            </div>            
        </div>

        <div class="span4">
            <div class="control-group ">
                <label class="control-label" for="tipo_atendimento">Unidade de Negócio</label>
                <div class="controls controls-row">
                    <div class="span12">
                    	<select id="unidadenegocio" multiple="multiple" name="unidadenegocio[]" class="span12" >
				<?php
				$unidadesnegocios = $_POST['unidadenegocio'];
				
				$sqlUnidadeNegocio = "
                                SELECT DISTINCT
                                    tbl_distribuidor_sla.unidade_negocio,
                                    MAX(tbl_distribuidor_sla.distribuidor_sla) AS distribuidor_sla,
                                    tbl_distribuidor_sla.unidade_negocio||' - '||tbl_cidade.nome AS cidade
                                FROM tbl_distribuidor_sla_posto
                                LEFT JOIN tbl_distribuidor_sla USING(distribuidor_sla, fabrica)
                                RIGHT JOIN tbl_cidade USING(cidade)
                                WHERE tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
                                AND tbl_distribuidor_sla_posto.posto = {$login_posto}
                                GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_cidade.nome;
                            ";
				$res = pg_query($con,$sqlUnidadeNegocio);
				$distribuidores_disponiveis = pg_fetch_all($res);

				foreach ($distribuidores_disponiveis as $unidadeNegocio) {

					if (in_array($unidadeNegocio["unidade_negocio"], array(6102,6103,6104,6105,6106,6107,6108))) {
						unset($unidadeNegocio["unidade_negocio"]);
						continue;
					}
					$unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
				}

				foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
					$selected = (in_array($unidade, $unidadesnegocios)) ? 'SELECTED' : '';
					echo "<option value='{$unidade}' {$selected}> {$descricaoUnidade}</option>";
				}
				?>
			</select>
		    </div>
                </div>
            </div>
        </div> 
        <div class='span2'></div>
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
<link rel="stylesheet" href="admin/css/multiple-select.css" />
<script src="admin/js/jquery.multiple.select.js"></script>
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
$("#unidadenegocio").select2();

$('#bt_cad_forn').on('click', function(){
    var data_inicial = $('#data_inicial').val();
    var data_final = $('#data_final').val();
    var unidadenegocio = $('#unidadenegocio').val();
    Limpar();

    $.ajax({
        url: window.location,
        method: 'POST',
        data: {ajax: 'sim', data_inicial: data_inicial, data_final: data_final, unidadenegocio: unidadenegocio},
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
$("#gerar_excel").click(function () {
    var json = $.parseJSON($("#jsonPOST").val());
    json["gerar_excel"] = true;

    $.ajax({
        url: "<?=$_SERVER['PHP_SELF']?>",
        type: "POST",
        data: json,
        beforeSend: function(){
            $('#loading').show();
        },
        complete: function (data) {
            window.open(data.responseText, "_blank");

            $('#loading').hide();      
        }
    });
});
</script>
