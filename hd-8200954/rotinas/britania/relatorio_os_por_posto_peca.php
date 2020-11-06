<?php

include_once dirname(__FILE__) . '/../../dbconfig.php';
include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 3;
$dir = "/tmp/britania";
#$dir = "/home/williamcastro/public_html/";

$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, date("m"), 1, date("Y")));
$data_final   = date("Y-m-t  23:59:59", mktime(0, 0, 0, date("m"), 1, date("Y")));

$sql = "SELECT tbl_os.os ,
        tbl_os.sua_os ,
        tbl_os.consumidor_nome ,
        tbl_os.consumidor_revenda ,
        tbl_os.consumidor_fone ,
        tbl_os.serie ,
        tbl_os.revenda_nome ,
        tbl_os.data_digitacao ,
        tbl_os.data_abertura ,
        tbl_os.data_fechamento ,
        tbl_os.finalizada ,
        tbl_os.data_conserto ,
        tbl_os.data_nf ,
        replace(tbl_os.obs,'\"','') as obs_os ,
        tbl_os.obs_reincidencia ,
        data_abertura::date - tbl_os.data_nf::date AS dias_uso ,
        tbl_os.produto,
        tbl_os.posto,
        tbl_os.defeito_constatado,
        tbl_os.defeito_reclamado,
        tbl_os.solucao_os,
        tbl_os.fabrica,
        tbl_os.excluida,
        tbl_os.cancelada,
        tbl_os.defeito_reclamado_descricao as df_descricao,
        tbl_os.aparencia_produto            AS aparencia_produto,
        tbl_os.acessorios                   AS acessorios,
        tbl_os.troca_garantia_admin,
        tbl_os.tecnico
        INTO temp tmp_os_britania_$fabrica
        FROM tbl_os
        WHERE tbl_os.fabrica = $fabrica
        AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
        AND   tbl_os.excluida IS NOT TRUE
        AND   tbl_os.posto <> 6359;";

$sql .= "
        CREATE INDEX idx_tosb_prod_$fabrica ON tmp_os_britania_$fabrica(produto);
        CREATE INDEX idx_tosb_posto_$fabrica ON tmp_os_britania_$fabrica(posto);
        CREATE INDEX idx_tosb_os_$fabrica ON tmp_os_britania_$fabrica(os);
        CREATE INDEX idx_tosb_tga_$fabrica ON tmp_os_britania_$fabrica(troca_garantia_admin);
        CREATE INDEX idx_tosb_tec_$fabrica ON tmp_os_britania_$fabrica(tecnico);
    ";

$sql .= "SELECT  tmp_os_britania_$fabrica.os         ,
        tmp_os_britania_$fabrica.sua_os              ,
        tmp_os_britania_$fabrica.consumidor_nome     ,
        tmp_os_britania_$fabrica.consumidor_revenda  ,
        tmp_os_britania_$fabrica.consumidor_fone     ,
        tmp_os_britania_$fabrica.serie               ,
        tmp_os_britania_$fabrica.revenda_nome        ,
        tmp_os_britania_$fabrica.data_digitacao      ,
        tmp_os_britania_$fabrica.data_abertura       ,
        tmp_os_britania_$fabrica.data_fechamento     ,
        tmp_os_britania_$fabrica.finalizada          ,
        tmp_os_britania_$fabrica.data_conserto       ,
        tmp_os_britania_$fabrica.data_nf             ,
        tmp_os_britania_$fabrica.obs_os              ,
        tmp_os_britania_$fabrica.obs_reincidencia    ,
        tmp_os_britania_$fabrica.dias_uso            ,
        tmp_os_britania_$fabrica.produto,
        tmp_os_britania_$fabrica.posto,
        tmp_os_britania_$fabrica.defeito_constatado,
        tmp_os_britania_$fabrica.defeito_reclamado,
        tmp_os_britania_$fabrica.solucao_os,
        tmp_os_britania_$fabrica.fabrica,
        tmp_os_britania_$fabrica.excluida,
        tmp_os_britania_$fabrica.cancelada,
        tmp_os_britania_$fabrica.df_descricao,
        tbl_produto.referencia,
        tbl_produto.descricao,
        tbl_produto.linha,
        tbl_produto.familia,
        tbl_produto.marca,
        tbl_posto_fabrica.codigo_posto               ,
        tbl_posto_fabrica.contato_email              ,
        tbl_posto_fabrica.contato_fone_comercial     ,
        ( SELECT tbl_admin.nome_completo
          FROM tbl_admin
          WHERE tbl_posto_fabrica.admin_sap = tbl_admin.admin
        ) admin_sap,
        tbl_posto.nome,
        tbl_posto_fabrica.contato_estado,
        tbl_os_item.digitacao_item,
        tbl_os_item.peca,
        tbl_os_item.servico_realizado,
        tbl_os_item.pedido,
        tbl_linha.nome                               AS nome_linha,
        tbl_familia.descricao                        AS nome_familia,
        troca_admin.login                            AS troca_admin,
        TO_CHAR(data,'dd/mm/yyyy hh:mi') AS data_troca          ,
        setor                            AS setor_troca         ,
        situacao_atendimento             AS situacao_atend_troca,
        tbl_os_troca.observacao          AS observacao_troca    ,
        tbl_peca.referencia             AS peca_referencia_troca ,
        tbl_peca.descricao              AS peca_descricao_troca  ,
        tbl_causa_troca.descricao       AS causa_troca           ,
        tbl_os_troca.modalidade_transporte  AS modalidade_transporte_troca,
        tbl_os_troca.envio_consumidor       AS envio_consumidor_troca,
        tbl_os_extra.orientacao_sac         AS orientacao_sac,
        tbl_os_extra.extrato                AS extrato,
        tmp_os_britania_$fabrica.aparencia_produto,
        tmp_os_britania_$fabrica.acessorios,
        tbl_os_item.obs                     AS obs,
        tbl_tecnico.nome 					AS nome_tecnico,
        (SELECT array(
            SELECT os_status::text
                || '||'
                || status_os::text
                || '||'
                || observacao::text
                || '||'
                || CASE WHEN tbl_admin.login isnull THEN ' ' ELSE tbl_admin.login::text end
                || '||'
                || to_char(data, 'DD/MM/YYYY')
                || '||'
                || CASE WHEN tbl_os_status.admin isnull THEN ' ' ELSE tbl_os_status.admin::text end
            FROM  tbl_os_status
            LEFT JOIN tbl_admin USING(admin)
            WHERE tbl_os_status.os = tmp_os_britania_$fabrica.os
            AND tbl_os_status.fabrica_status = $fabrica
            AND status_os IN (72,73,62,64,65,87,88,116,117)
            ORDER BY data ASC)
        ) AS status_os_dados
    into temp tmp_os_$fabrica
    FROM tmp_os_britania_$fabrica
    JOIN tbl_produto ON tmp_os_britania_$fabrica.produto = tbl_produto.produto and tbl_produto.fabrica_i = $fabrica
    JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica=$fabrica
    JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
    JOIN tbl_posto ON tmp_os_britania_$fabrica.posto   = tbl_posto.posto
    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
    LEFT JOIN tbl_os_extra           ON tbl_os_extra.os = tmp_os_britania_$fabrica.os AND tbl_os_extra.i_fabrica=tmp_os_britania_$fabrica.fabrica
    LEFT JOIN tbl_os_produto         ON tmp_os_britania_$fabrica.os        = tbl_os_produto.os
    LEFT JOIN tbl_os_item            ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = tmp_os_britania_$fabrica.fabrica
    LEFT JOIN tbl_admin troca_admin  ON tmp_os_britania_$fabrica.troca_garantia_admin = troca_admin.admin
    LEFT JOIN tbl_os_troca           ON tbl_os_troca.os = tmp_os_britania_$fabrica.os and tbl_os_troca.fabric = $fabrica
    LEFT JOIN tbl_peca               ON tbl_os_troca.peca = tbl_peca.peca and tbl_peca.fabrica = $fabrica
    LEFT JOIN tbl_causa_troca        ON tbl_os_troca.causa_troca = tbl_causa_troca.causa_troca and tbl_causa_troca.fabrica=$fabrica
    LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tmp_os_britania_$fabrica.tecnico AND tmp_os_britania_$fabrica.posto = tbl_tecnico.posto AND tbl_tecnico.fabrica = $fabrica
    ;
    ";

$sql .= "CREATE INDEX tmp_os_fabrica_os on tmp_os_$fabrica(fabrica,os);";
$sql .= "CREATE INDEX tmp_os_fabrica_os_peca on tmp_os_$fabrica(peca);";
$sql .= "CREATE INDEX tmp_os_fabrica_os_pedido on tmp_os_$fabrica(pedido);";
$sql .= "CREATE INDEX tmp_os_fabrica_os_servico_realizado on tmp_os_$fabrica(servico_realizado);";
$sql .= "CREATE INDEX tmp_os_fabrica_os_posto_excluida on tmp_os_$fabrica(fabrica,os,posto,excluida);";

$res = pg_query($con, $sql);
$msg_erro .= pg_errormessage($con);

$sql = "SELECT distinct tmp_os_$fabrica.os                              ,
        tmp_os_$fabrica.sua_os                                          ,
        tmp_os_$fabrica.consumidor_nome                                 ,
        tmp_os_$fabrica.consumidor_revenda                              ,
        tmp_os_$fabrica.consumidor_fone                                 ,
        tmp_os_$fabrica.serie                                           ,
        tmp_os_$fabrica.revenda_nome                                    ,
        tmp_os_$fabrica.df_descricao                                    ,
        tmp_os_$fabrica.obs_os                                          ,
        tmp_os_$fabrica.obs_reincidencia                                ,
        tmp_os_$fabrica.fabrica,
        to_char (tmp_os_$fabrica.data_digitacao,'DD/MM/YYYY')  AS data_digitacao,
        to_char (tmp_os_$fabrica.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
        to_char (tmp_os_$fabrica.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
        to_char (tmp_os_$fabrica.finalizada,'DD/MM/YYYY')      AS data_finalizada,
        to_char (tmp_os_$fabrica.data_conserto,'DD/MM/YYYY')   AS data_conserto  ,
        to_char (tmp_os_$fabrica.data_nf,'DD/MM/YYYY')         AS data_nf        ,
        tmp_os_$fabrica.dias_uso                                                 ,
        tbl_marca.nome                                AS marca_nome         ,
        tmp_os_$fabrica.referencia                AS produto_referencia ,
        tmp_os_$fabrica.descricao                 AS produto_descricao  ,
        tbl_peca.referencia                           AS peca_referencia    ,
        tbl_peca.descricao                            AS peca_descricao     ,
        tbl_servico_realizado.descricao               AS servico            ,
        tbl_defeito_constatado.descricao              AS defeito_constatado ,
        tbl_defeito_reclamado.descricao               AS defeito_reclamado  ,
        tbl_solucao.descricao                         AS solucao            ,
        tmp_os_$fabrica.nome_linha                AS linha              ,
        tmp_os_$fabrica.nome_familia              AS familia            ,
        tmp_os_$fabrica.contato_email                                     ,
        tmp_os_$fabrica.contato_fone_comercial                            ,
        tmp_os_$fabrica.admin_sap                                         ,                                       
        TO_CHAR (tmp_os_$fabrica.digitacao_item,'DD/MM/YYYY')  AS data_digitacao_item,
        tmp_os_$fabrica.codigo_posto                                      ,
        tmp_os_$fabrica.nome                           AS nome_posto         ,
        tmp_os_$fabrica.contato_estado              AS estado_posto,
        (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tmp_os_$fabrica.os AND tbl_os_status.fabrica_status=$fabrica ORDER BY os_status DESC LIMIT 1) AS status_os,
        tmp_os_$fabrica.pedido                                                   ,
        tmp_os_$fabrica.troca_admin,
        tmp_os_$fabrica.data_troca          ,
        tmp_os_$fabrica.setor_troca         ,
        tmp_os_$fabrica.situacao_atend_troca,
        tmp_os_$fabrica.observacao_troca    ,
        tmp_os_$fabrica.peca_referencia_troca ,
        tmp_os_$fabrica.peca_descricao_troca  ,
        tmp_os_$fabrica.causa_troca           ,
        tmp_os_$fabrica.modalidade_transporte_troca,
        tmp_os_$fabrica.envio_consumidor_troca,
        tmp_os_$fabrica.orientacao_sac,
        tmp_os_$fabrica.aparencia_produto,
        tmp_os_$fabrica.cancelada,
        tmp_os_$fabrica.acessorios,
        tmp_os_$fabrica.extrato,
        tmp_os_$fabrica.obs,
        tmp_os_$fabrica.nome_tecnico,
        tmp_os_$fabrica.peca,
        tmp_os_$fabrica.status_os_dados
                INTO TEMP tmp_os_os_$fabrica

        FROM tmp_os_$fabrica
        LEFT JOIN tbl_peca               ON tmp_os_$fabrica.peca              = tbl_peca.peca           AND tbl_peca.fabrica = $fabrica
        LEFT JOIN tbl_marca              ON tbl_marca.marca               = tmp_os_$fabrica.marca
        LEFT JOIN tbl_defeito_reclamado  ON tmp_os_$fabrica.defeito_reclamado      = tbl_defeito_reclamado.defeito_reclamado  AND tbl_defeito_reclamado.fabrica = $fabrica
        LEFT JOIN tbl_defeito_constatado ON tmp_os_$fabrica.defeito_constatado     = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $fabrica
        LEFT JOIN tbl_servico_realizado  ON tmp_os_$fabrica.servico_realizado = tbl_servico_realizado.servico_realizado  AND tbl_servico_realizado.fabrica=$fabrica
        LEFT JOIN tbl_solucao            ON tmp_os_$fabrica.solucao_os             = tbl_solucao.solucao AND tbl_solucao.fabrica=$fabrica
        WHERE tmp_os_$fabrica.fabrica = $fabrica;";

$sql .= "CREATE INDEX tmp_os_os_fabrica ON tmp_os_os_$fabrica(fabrica);";
$sql .= "CREATE INDEX tmp_os_os_pedido ON tmp_os_os_$fabrica(pedido);";

$res = pg_query($con, $sql);

$msg_erro .= pg_errormessage($con);

$sql = " SELECT tmp_os_os_$fabrica.*,
                to_char (tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
                 case
                     when tbl_pedido_item.qtde = tbl_pedido_item.qtde_faturada then
                          'FATURADO INTEGRAL'
                     when tbl_pedido_item.qtde = tbl_pedido_item.qtde_cancelada then
                          'CANCELADO TOTAL'
                     when tbl_pedido_item.qtde < tbl_pedido_item.qtde_faturada then
                          'FATURADO PARCIAL'
                     else
                          'AGUARDANDO FATURAMENTO'
                  end     AS status_pedido,
                tbl_pedido.status_pedido as ped_status_pedido
    INTO TEMP tmp_os_pedido_$fabrica
            FROM tmp_os_os_$fabrica
            LEFT JOIN tbl_pedido             ON tmp_os_os_$fabrica.pedido            = tbl_pedido.pedido AND tbl_pedido.fabrica = $fabrica
            LEFT JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tmp_os_os_$fabrica.peca = tbl_pedido_item.peca
            WHERE tmp_os_os_$fabrica.fabrica = $fabrica;";
$res      = pg_query($con, $sql);
$msg_erro .= pg_errormessage($con);
#echo nl2br($sql)."<br><br>";

$sql = "SELECT tbl_faturamento_item.pedido,nota_fiscal,emissao 
    INTO TEMP tmp_faturamento_$fabrica
    FROM tbl_faturamento
    JOIN tbl_faturamento_item USING(faturamento)
    WHERE fabrica=$fabrica
    and tbl_faturamento_Item.pedido in (select pedido from tmp_os_pedido_$fabrica);

    alter table tmp_os_pedido_$fabrica add nota_fiscal character varying(20);
    alter table tmp_os_pedido_$fabrica add emissao date;

    UPDATE tmp_os_pedido_$fabrica SET nota_fiscal = tmp_faturamento_$fabrica.nota_fiscal, emissao = tmp_faturamento_$fabrica.emissao
                      FROM tmp_faturamento_$fabrica 
                      WHERE tmp_faturamento_$fabrica.pedido = tmp_os_pedido_$fabrica.pedido;";
$res      = pg_query($con, $sql);
$msg_erro .= pg_errormessage($con);

$sql ="SELECT * FROM tmp_os_pedido_$fabrica;";
$res      = pg_query($con, $sql);
$msg_erro .= pg_errormessage($con);

#system("mkdir -p $dir");

#$arquivo_nome  = "$dir/os_por_posto_peca_" . date("Ymd") . "_" . $fabrica . ".csv";
#$arquivo_nome2 = "os_por_posto_peca_" . date("Ymd") . "_" . $fabrica . ".csv";
#$arquivo_zip   = "os_por_posto_peca_" . date("Ymd") . "_". $fabrica . ".zip";
$nome_arquivo = "os_por_posto_peca_" . $fabrica . ".csv";
$arquivo_nome  = $dir . $nome_arquivo;

$arquivo = fopen($arquivo_nome, "w");

if (pg_numrows($res) > 0) {

	ob_flush();
	flush();

	fwrite($arquivo, "Sua OS");
    fwrite($arquivo, ";");
	fwrite($arquivo, "Consumidor/Revenda");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Consumidor Nome");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Consumidor Fone");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Número de Série"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Solução"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Data Digitação"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data Abertura");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data Fechamento");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data Finalizada");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data Conserto");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data NF Compra");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Dias de Uso");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Marca");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Produto Referência"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Produto Descrição"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Linha");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Familia");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Peça Referência"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Peça Descrição"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Defeito Reclamado"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Defeito Constatado"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Serviço"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Digitação Item"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Código Posto"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Nome Posto");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Estado");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Nome Revenda");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Nota Fiscal");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Emissão"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Status do Pedido");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Status da OS");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Responsável"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Trocado Por");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Causa da Troca");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Observação Troca"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Justificativa do Posto");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Aparencia geral do aparelho/produto");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Acessórios deixados junto com o aparelho"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Informações sobre o defeito"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Observações da OS"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Orientações do SAC ao Posto Autorizado"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data de Conferencia");
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Previsão de Pagamento"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Nota Fiscal Conferência"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Justificativa do Pedido de Peça"));
	fwrite($arquivo, ";");
	fwrite($arquivo, utf8_decode("Técnico Responsável"));
	fwrite($arquivo, ";");
	fwrite($arquivo, "Pedido");
	fwrite($arquivo, ";");
	fwrite($arquivo, "Data Pedido");
    fwrite($arquivo, ";");
    fwrite($arquivo, "Tipo de Atendimento");
    fwrite($arquivo, ";");
    fwrite($arquivo, "E-mail Posto");
    fwrite($arquivo, ";");
    fwrite($arquivo, "Telefone Posto");
    fwrite($arquivo, ";");
    fwrite($arquivo, "Inspetor");

	fwrite($arquivo, "\n");

	$extrato_anterior = '';

	for ($i = 0; $i < pg_num_rows($res); $i++) {
		$os                          = pg_fetch_result($res, $i, 'os');
		$sua_os                      = pg_fetch_result($res, $i, 'sua_os');
		$pedido                      = pg_fetch_result($res, $i, 'pedido');
		$data_pedido                 = pg_fetch_result($res, $i, 'data_pedido');
		$consumidor_nome             = pg_fetch_result($res, $i, 'consumidor_nome');
		$consumidor_revenda          = pg_fetch_result($res, $i, 'consumidor_revenda');
		$consumidor_fone             = pg_fetch_result($res, $i, 'consumidor_fone');
		$serie                       = pg_fetch_result($res, $i, 'serie');
		$solucao                     = pg_fetch_result($res, $i, 'solucao');
		$data_digitacao              = pg_fetch_result($res, $i, 'data_digitacao');
		$data_abertura               = pg_fetch_result($res, $i, 'data_abertura');
		$data_fechamento             = pg_fetch_result($res, $i, 'data_fechamento');
		$data_finalizada             = pg_fetch_result($res, $i, 'data_finalizada');
		$data_conserto               = pg_fetch_result($res, $i, 'data_conserto');
		$data_nf                     = pg_fetch_result($res, $i, 'data_nf');
		$dias_uso                    = pg_fetch_result($res, $i, 'dias_uso');
		$marca_nome                  = pg_fetch_result($res, $i, 'marca_nome');
		$produto_referencia          = pg_fetch_result($res, $i, 'produto_referencia');
		$produto_descricao           = pg_fetch_result($res, $i, 'produto_descricao');
		$linha                       = pg_fetch_result($res, $i, 'linha');
		$familia                     = pg_fetch_result($res, $i, 'familia');
		$peca_referencia             = pg_fetch_result($res, $i, 'peca_referencia');
		$peca_descricao              = pg_fetch_result($res, $i, 'peca_descricao');
		$servico                     = pg_fetch_result($res, $i, 'servico');
		$defeito_constatado          = pg_fetch_result($res, $i, 'defeito_constatado');
		$defeito_reclamado           = pg_fetch_result($res, $i, 'defeito_reclamado');
		$data_digitacao_item         = pg_fetch_result($res, $i, 'data_digitacao_item');
		$codigo_posto                = pg_fetch_result($res, $i, 'codigo_posto');
		$nome_posto                  = pg_fetch_result($res, $i, 'nome_posto');
		$estado_posto                = pg_fetch_result($res, $i, 'estado_posto');
		$revenda_nome                = pg_fetch_result($res, $i, 'revenda_nome');
		$nota_fiscal                 = pg_fetch_result($res, $i, 'nota_fiscal');
		$emissao                     = pg_fetch_result($res, $i, 'emissao');
		$status_pedido               = pg_fetch_result($res, $i, 'status_pedido');
		$status_os                   = pg_fetch_result($res, $i, 'status_os');
		$troca_admin                 = pg_fetch_result($res, $i, 'troca_admin');
		$data_troca                  = pg_fetch_result($res, $i, 'data_troca');
		$setor_troca                 = pg_fetch_result($res, $i, 'setor_troca');
		$situacao_atend_troca        = pg_fetch_result($res, $i, 'situacao_atend_troca');
		$observacao_troca            = pg_fetch_result($res, $i, 'observacao_troca');
		$peca_referencia_troca       = pg_fetch_result($res, $i, 'peca_referencia_troca');
		$peca_descricao_troca        = pg_fetch_result($res, $i, 'peca_descricao_troca');
		$modalidade_transporte_troca = pg_fetch_result($res, $i, 'modalidade_transporte_troca');
		$envio_consumidor_troca      = pg_fetch_result($res, $i, 'envio_consumidor_troca');
		$orientacao_sac              = pg_fetch_result($res, $i, 'orientacao_sac');
		$causa_troca                 = pg_fetch_result($res, $i, 'causa_troca');
		$aparencia_produto           = pg_fetch_result($res, $i, 'aparencia_produto');
		$acessorios                  = pg_fetch_result($res, $i, 'acessorios');
		$df_descricao                = pg_fetch_result($res, $i, 'df_descricao');
		$obs_os                      = pg_fetch_result($res, $i, 'obs_os');
		$obs_reincidencia            = pg_fetch_result($res, $i, 'obs_reincidencia');
		$extrato                     = pg_fetch_result($res, $i, 'extrato');
		$nome_tecnico                = pg_fetch_result($res, $i, 'nome_tecnico');
		$status_os_dados             = pg_fetch_result($res, $i, 'status_os_dados');
        $contato_email               = pg_fetch_result($res, $i, 'contato_email');
        $contato_fone_comercial      = pg_fetch_result($res, $i, 'contato_fone_comercial');
        $admin_sap                   = pg_fetch_result($res, $i, 'admin_sap');

		$status_os_dados = str_replace("{","",$status_os_dados);
		$status_os_dados = str_replace("}","",$status_os_dados);

        $aux_sql = "SELECT tbl_tipo_atendimento.descricao AS tipo_atendimento FROM tbl_tipo_atendimento JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_os.fabrica = $fabrica WHERE tbl_os.os = $os";
        $aux_res = pg_query($con, $aux_sql);
        $tipo_atendimento = utf8_decode(pg_fetch_result($aux_res, 0, 'tipo_atendimento'));
    
		if (strlen($extrato)>0) {

			$sql_extrato_conferencia = "SELECT 	TO_CHAR(data_conferencia,'dd/mm/yyyy') AS data_conferencia,
												nota_fiscal,
												TO_CHAR(previsao_pagamento,'dd/mm/yyyy') AS previsao_pagamento
										FROM tbl_extrato_conferencia
										WHERE tbl_extrato_conferencia.extrato= $extrato";
			$res_extrato_conferencia = pg_query($con,$sql_extrato_conferencia);

			if ( $extrato != $extrato_anterior and pg_num_rows($res_extrato_conferencia)>0){

				$xdata_conferencia   = pg_result($res_extrato_conferencia,0,'data_conferencia');
				$xnota_fiscal        = pg_result($res_extrato_conferencia,0,'nota_fiscal');
				$xprevisao_pagamento = pg_result($res_extrato_conferencia,0,'previsao_pagamento');

				$xdata_conferencia   = str_replace(";"," ",$xdata_conferencia);
				$xnota_fiscal        = str_replace(";"," ",$xnota_fiscal);
				$xprevisao_pagamento = str_replace(";"," ",$xprevisao_pagamento);
			}
			$extrato_anterior = $extrato;
		} else {
			$xdata_conferencia   = " ";
			$xnota_fiscal        = " ";
			$xprevisao_pagamento = " ";
		}

		$sua_os                      = str_replace(";"," ",$sua_os);
		$consumidor_revenda          = str_replace(";"," ",$consumidor_revenda);
		$pedido                      = str_replace(";"," ",$pedido);
        $data_pedido                 = str_replace(";"," ",$data_pedido);
		$consumidor_nome             = str_replace(";"," ",$consumidor_nome);
		$consumidor_nome             = str_replace("\r"," ",$consumidor_nome);
		$consumidor_fone             = str_replace(";"," ",$consumidor_fone);
		$serie                       = str_replace(";"," ",$serie);
		$solucao                     = str_replace(";"," ",$solucao);
		$data_digitacao              = str_replace(";"," ",$data_digitacao);
		$data_abertura               = str_replace(";"," ",$data_abertura);
		$data_fechamento             = str_replace(";"," ",$data_fechamento);
		$data_finalizada             = str_replace(";"," ",$data_finalizada);
		$data_conserto               = str_replace(";"," ",$data_conserto);
		$data_nf                     = str_replace(";"," ",$data_nf);
		$dias_uso                    = str_replace(";"," ",$dias_uso);
		$marca_nome                  = str_replace(";"," ",$marca_nome);
		$produto_referencia          = str_replace(";"," ",$produto_referencia);
		$produto_descricao           = str_replace(";"," ",$produto_descricao);
		$linha                       = str_replace(";"," ",$linha);
		$familia                     = str_replace(";"," ",$familia);
		$peca_referencia             = str_replace(";"," ",$peca_referencia);
		$peca_descricao              = str_replace(";"," ",$peca_descricao);
		$servico                     = str_replace(";"," ",$servico);
		$defeito_constatado          = str_replace(";"," ",$defeito_constatado);
		$defeito_constatado          = str_replace("("," - ",$defeito_constatado);
		$defeito_constatado          = str_replace(")","",$defeito_constatado);
		$defeito_constatado          = str_replace(",","/",$defeito_constatado);
		$defeito_reclamado           = str_replace(";"," ",$defeito_reclamado);
		$defeito_reclamado           = str_replace("("," - ",$defeito_reclamado);
		$defeito_reclamado           = str_replace(")","",$defeito_reclamado);
		$defeito_reclamado           = str_replace(",","/",$defeito_reclamado);
		$data_digitacao_item         = str_replace(";"," ",$data_digitacao_item);
		$codigo_posto                = str_replace(";"," ",$codigo_posto);
		$nome_posto                  = str_replace(";"," ",$nome_posto);
		$nome_posto           	     = str_replace(",","/",$nome_posto);
		$estado_posto                = str_replace(";"," ",$estado_posto);
		$revenda_nome                = str_replace(";"," ",$revenda_nome);
		$nota_fiscal                 = str_replace(";"," ",$nota_fiscal);
		$emissao                     = str_replace(";"," ",$emissao);
		$status_pedido               = str_replace(";"," ",$status_pedido);
		$status_os                   = str_replace(";"," ",$status_os);
		$troca_admin                 = str_replace(";"," ",$troca_admin);
		$data_troca                  = str_replace(";"," ",$data_troca);
		$setor_troca                 = str_replace(";"," ",$setor_troca);
		$situacao_atend_troca        = str_replace(";"," ",$situacao_atend_troca);
		$peca_referencia_troca       = str_replace(";"," ",$peca_referencia_troca);
		$peca_descricao_troca        = str_replace(";"," ",$peca_descricao_troca);
		$modalidade_transporte_troca = str_replace(";"," ",$modalidade_transporte_troca);
		$envio_consumidor_troca      = str_replace(";"," ",$envio_consumidor_troca);
		$df_descricao                = str_replace(";"," ",$df_descricao);
		$df_descricao                = str_replace("null"," ",$df_descricao);
		$aparencia_produto           = str_replace(";"," ",$aparencia_produto);
		$acessorios                  = str_replace("null"," ",(str_replace (";"," ",$acessorios)));
		$orientacao_sac            	 = str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$orientacao_sac))))));
		$obs_os            			 = str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$obs_os))))));
		$obs_reincidencia            = str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$obs_reincidencia))))));
		$observacao_troca            = str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$observacao_troca))))));

		fwrite($arquivo, $sua_os);
		fwrite($arquivo, ";");
		fwrite($arquivo, $consumidor_revenda);
		fwrite($arquivo, ";");
		fwrite($arquivo, $consumidor_nome);
		fwrite($arquivo, ";");
		fwrite($arquivo, $consumidor_fone);
		fwrite($arquivo, ";");
		fwrite($arquivo, $serie);
		fwrite($arquivo, ";");
		fwrite($arquivo, $solucao);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_digitacao);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_abertura);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_fechamento);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_finalizada);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_conserto);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_nf);
		fwrite($arquivo, ";");
		fwrite($arquivo, $dias_uso);
		fwrite($arquivo, ";");
		fwrite($arquivo, $marca_nome);
		fwrite($arquivo, ";");
		fwrite($arquivo, $produto_referencia);
		fwrite($arquivo, ";");
		fwrite($arquivo, $produto_descricao);
		fwrite($arquivo, ";");
		fwrite($arquivo, $linha);
		fwrite($arquivo, ";");
		fwrite($arquivo, $familia);
		fwrite($arquivo, ";");
		fwrite($arquivo, $peca_referencia);
		fwrite($arquivo, ";");
		fwrite($arquivo, $peca_descricao);
		fwrite($arquivo, ";");
		fwrite($arquivo, $defeito_reclamado);
		fwrite($arquivo, ";");
		fwrite($arquivo, $defeito_constatado );
		fwrite($arquivo, ";");
		fwrite($arquivo, $servico);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_digitacao_item);
		fwrite($arquivo, ";");
		fwrite($arquivo, $codigo_posto);
		fwrite($arquivo, ";");
		fwrite($arquivo, $nome_posto);
		fwrite($arquivo, ";");
		fwrite($arquivo, $estado_posto);
		fwrite($arquivo, ";");
		fwrite($arquivo, $revenda_nome);
		fwrite($arquivo, ";");
		fwrite($arquivo, $nota_fiscal);
		fwrite($arquivo, ";");
		fwrite($arquivo, $emissao);
		fwrite($arquivo, ";");
		fwrite($arquivo, $status_pedido);
		fwrite($arquivo, ";");
		fwrite($arquivo, $status_os);
		fwrite($arquivo, ";");
		fwrite($arquivo, $troca_admin);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_troca);
		fwrite($arquivo, ";");

		if (!empty($peca_referencia_troca)) {
			fwrite($arquivo, $peca_referencia_troca." - ".$peca_descricao_troca);
		} else {
			fwrite($arquivo, " " );
		}

		fwrite($arquivo, ";");
		fwrite($arquivo, $causa_troca);
		fwrite($arquivo, ";");
		fwrite($arquivo, $observacao_troca);
		fwrite($arquivo, ";");
		fwrite($arquivo, $obs_reincidencia);
		fwrite($arquivo, ";");
		fwrite($arquivo, $aparencia_produto);
		fwrite($arquivo, ";");
		fwrite($arquivo, $acessorios);
		fwrite($arquivo, ";");
		fwrite($arquivo, $df_descricao);
		fwrite($arquivo, ";");
		fwrite($arquivo, $obs_os);
		fwrite($arquivo, ";");
		fwrite($arquivo, $orientacao_sac);
		fwrite($arquivo, ";");
		fwrite($arquivo, $xdata_conferencia);
		fwrite($arquivo, ";");
		fwrite($arquivo, $xprevisao_pagamento);
		fwrite($arquivo, ";");
		fwrite($arquivo, $xnota_fiscal);
		fwrite($arquivo, ";");

		if (strlen($status_os_dados) > 0) {

			$status_os_dados = explode('","',$status_os_dados);

			$conteudo = '';

			foreach ($status_os_dados as $status_os_item) {

				$status_os_item = str_replace('"',"",$status_os_item);
				$status_os_item = explode('||',$status_os_item);

				if (($status_os_item[1] == 72 OR  $status_os_item[1] == 64) AND strlen($status_os_item[2]) > 0) {
					$pos = strpos($status_os_item[2], 'Justificativa:');

					if ($pos !== false) {
						$status_os_item[2] = strstr($status_os_item[2],"Justificativa:");
						$status_os_item[2] = str_replace("Justificativa:"," ",$status_os_item[2]);
					}

				}

				$status_os_item[2] = trim($status_os_item[2]);

				if (strlen($status_os_item[2]) == 0 AND $status_os_item[1] == 73) $status_os_item[2] = "Autorizado";
				if (strlen($status_os_item[2]) == 0 AND $status_os_item[1] == 72) $status_os_item[2] = "-";

				if (strlen($status_os_item[3]) > 0) {
					$status_os_item[3] = " ($status_os_item[3])";
				}

				$conteudo.= "Data: $status_os_item[4]     ";
				$status_os_item[1] = trim($status_os_item[1]);

				switch ($status_os_item[1]) {
					case '72':
						$conteudo.= 'Justificativa do Posto: ';
						break;
					case '73':
						$conteudo.= 'Resposta da Fábrica: ';
						break;
					case '62':
						$conteudo.= 'OS em Intervenção ';
						break;
					case '65':
						$conteudo.= 'OS em reparo na Fábrica';
						break;
					case '64':
						$conteudo.= 'Resposta da Fábrica: ';
						break;
					case '87':
					case '88':
					case '116':
					case '116':
						$conteudo.= 'Fábrica: ';
						break;
				}

				$conteudo.= $status_os_item[2] . '     ';
			}

			$conteudo = str_replace("\r"," ",$conteudo);
			$conteudo = str_replace("\t"," ",$conteudo);
			$conteudo = str_replace("<br />"," ",$conteudo);
			$conteudo = str_replace("\n"," ",$conteudo);
			$conteudo = str_replace("null"," ",$conteudo);
			$conteudo = str_replace(";"," ",$conteudo);

			fwrite($arquivo,$conteudo);

		}

		fwrite($arquivo, ";");
		fwrite($arquivo, $nome_tecnico);
		fwrite($arquivo, ";");
		fwrite($arquivo, $pedido);
		fwrite($arquivo, ";");
		fwrite($arquivo, $data_pedido);
		fwrite($arquivo, ";");
		fwrite($arquivo, $tipo_atendimento );
        fwrite($arquivo, ";");     
        fwrite($arquivo, $contato_email);
        fwrite($arquivo, ";");
        fwrite($arquivo, $contato_fone_comercial);
        fwrite($arquivo, ";");
        fwrite($arquivo, $admin_sap);
        fwrite($arquivo, ";");           

		fwrite($arquivo, "\n");
	}

	fclose($arquivo);
    
    #system("cd $dir && zip $arquivo_zip $arquivo_nome2");

	//$h = popen("cd $dir && zip $arquivo_zip $arquivo_nome","r");
	//pclose($h);

    #unlink($arquivo_nome);

    $ftp_server    = "telecontrol.britania.com.br";
    $ftp_user_name = "akacia";
    $ftp_user_pass = "britania2009";
    
    $server_file = "/1160/";

    $conn_id = ftp_connect($ftp_server);
    
    //set_time_limit(0); sertain-t P&D
    //ini_set('max_execution_time', 0);
    if (is_resource($conn_id)) {

	    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

	    ftp_pasv($conn_id, true); 

        if (ftp_put($conn_id,  $server_file . $nome_arquivo , $arquivo_nome, FTP_BINARY)) {
            echo "Sucesso\n";
        } else {
            echo "Erro\n";
        }

	    ftp_close($conn_id);
    } else {
	    $erro_ftp = $conn_id;
    }

}