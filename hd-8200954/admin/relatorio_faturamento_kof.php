<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Relatório de Peças Utilizadas em OS";
$layout_menu = "gerencia";

include "cabecalho_new.php";
use Posvenda\DistribuidorSLA;
$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);

if ($_POST) {
    $data_inicial     = $_POST["data_inicial"];
    $data_final       = $_POST["data_final"];
    $tipo_atendimento = $_POST['tipo_atendimento'];
    $apenas_peca      = $_POST['apenas_peca'];
    $tipo_atendimento = implode(",", $tipo_atendimento);

    if (empty($data_inicial) || empty($data_final)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_inicial";
        $msg_erro["campos"][] = "data_final";
    } else {
        $data_1 = explode('/', $data_inicial);
        $data_2 = explode('/', $data_final);

        $res_1 = checkdate($data_1[1], $data_1[0], $data_1[2]);
        $res_2 = checkdate($data_2[1], $data_2[0], $data_2[2]);
        if ($res_1 != 1) {
            $msg_erro["msg"]["obg"] = "Data inicio inválida<br />";
            $msg_erro["campos"][] = "data_inicial";
        }
        if ($res_2 != 1) {
            $msg_erro["msg"]["obg"] .= "Data final inválida";
            $msg_erro["campos"][] = "data_final";
        }
        if (empty($msg_erro["msg"])) {
            $date = new DateTime($data_1[2]."-".$data_1[1]."-".$data_1[0]);
            $diferenca = $date->diff(new DateTime($data_2[2]."-".$data_2[1]."-".$data_2[0]));

            if ($diferenca->invert == 1) {
                $msg_erro["msg"]["obg"] .= "Data inicial não pode ser maior que a data final";
                $msg_erro["campos"][] = "data_inicial";
                $msg_erro["campos"][] = "data_final";
            }else{
                if ($diferenca->m > 2 or $diferenca->days > 62) {
                    $msg_erro["msg"]["obg"] .= "Não será possível consultar mais de 2 meses";
                    $msg_erro["campos"][] = "data_inicial";
                    $msg_erro["campos"][] = "data_final";
                 }
            }
        }
    }

    $leftJoinOsItem  = " LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i={$login_fabrica}";
    $leftJoinServico = " LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}";
    $whereServico    = "";

    if (strlen($apenas_peca) > 0 ) {
        $leftJoinOsItem  = " JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i={$login_fabrica}";
        $leftJoinServico = " JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}";
        $whereServico = " AND (tbl_servico_realizado.peca_estoque IS TRUE OR (tbl_servico_realizado.gera_pedido IS TRUE AND tbl_pedido_item.peca=tbl_os_item.peca AND tbl_pedido_item.qtde_faturada=tbl_pedido_item.qtde))";
    }

    $whereUnidadeNegocio = "";
    $unidadenegocio = $_POST['unidadenegocio'];
    if (count($unidadenegocio) > 0) {
        foreach ($unidadenegocio as $key => $value) {
            if ($value == "6101") {
                $unidade_negocios[] = "'6107'";
                $unidade_negocios[] = "'6101'";
                $unidade_negocios[] = "'6102'";
                $unidade_negocios[] = "'6103'";
                $unidade_negocios[] = "'6106'";
                $unidade_negocios[] = "'6104'";
                $unidade_negocios[] = "'6108'";
            } else {
                $unidade_negocios[] = "'$value'";
            }
        }
        $whereUnidadeNegocio = "AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) IN (".implode(',', $unidade_negocios).")";
    }

    $xdata_inicial = $data_1[2].'-'.$data_1[1].'-'.$data_1[0];
    $xdata_final   = $data_2[2].'-'.$data_2[1].'-'.$data_2[0];

    if (empty($msg_erro["msg"])) {
        if (strlen($tipo_atendimento) > 0) {
            $whereTpAtendimento = " AND tbl_os.tipo_atendimento IN({$tipo_atendimento})";
        } else {
            $whereTpAtendimento = "";
        }
        $tipo_atendimento = explode(',', $tipo_atendimento);

		$sql = "
				SELECT  os,
                        posto,
                        tipo_atendimento,
                        consumidor_nome,
                        consumidor_estado,
                        data_digitacao,
                        finalizada,
                        tbl_os.obs,
                        fabrica,
                        status_checkpoint,
                        hd_chamado,
						serie_justificativa,
						inicio_atendimento,
                        termino_atendimento,
                        data_conserto
           INTO TEMP    tmp_kof_$login_admin
                FROM    tbl_os
                JOIN    tbl_os_extra USING(os)
                WHERE   tbl_os.fabrica = {$login_fabrica}
                AND     tbl_os_extra.termino_atendimento BETWEEN '{$xdata_inicial} 00:00:00' AND '{$xdata_final} 23:59:59'
				AND		tbl_os.excluida is not true
                AND     tbl_os.data_digitacao >= '2016-10-01 00:00:00' ;

                CREATE INDEX tmp_kof_os on tmp_kof_$login_admin (os) ;
                CREATE INDEX tmp_kof_posto on tmp_kof_$login_admin (posto) ;
                CREATE INDEX tmp_kof_status on tmp_kof_$login_admin (status_checkpoint) ;
                CREATE INDEX tmp_kof_tp on tmp_kof_$login_admin (tipo_atendimento) ;

                SELECT  DISTINCT
                        tbl_os.os,
                        tbl_os.serie_justificativa                                                AS patrimonio,
                        (
                            SELECT  tbl_solucao.descricao
                            FROM    tbl_os_defeito_reclamado_constatado
                            JOIN    tbl_solucao         ON  tbl_solucao.solucao             = tbl_os_defeito_reclamado_constatado.solucao
                                                        AND tbl_solucao.fabrica             = {$login_fabrica}
                            JOIN    tbl_classificacao   ON  tbl_classificacao.classificacao = tbl_solucao.classificacao
                                                        AND tbl_classificacao.fabrica       = {$login_fabrica}
                            WHERE   tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                      ORDER BY      tbl_classificacao.peso DESC
                            LIMIT   1
                        )                                                                               AS solucao,
                        tbl_routine_schedule_log.file_name                                              AS arquivo_entrada,
                        tbl_os_item.qtde,
                        tbl_pedido_item.qtde_cancelada,
                        tbl_pedido_item.qtde_faturada,
                        tbl_familia.descricao                                                           AS familia,
                        REGEXP_REPLACE(tbl_os.obs, '\\s+|\\r|\\n|;', ' ', 'gm')                         AS Observacao ,
                        REGEXP_REPLACE(trim(tbl_os.consumidor_nome), '\\s+|\\r|\\n|;', ' ', 'gm')       AS cliente_os ,
                        JSON_FIELD('osKof',dados)                                                       AS OsKof,
                        JSON_FIELD('protocoloKof',dados)                                                AS ProtocoloKof,
                        JSON_FIELD('idCliente',dados)                                                   AS IdCliente,
                        tbl_os.consumidor_estado                                                        AS cliente_estado,
                        tbl_status_checkpoint.descricao                                                 AS status,
                        TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY')                                    AS data_abertura,
                        CASE WHEN tbl_os.finalizada IS NULL
                             THEN TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY')
                             ELSE TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY')
                        END                                                                             AS data_fechamento,
                        TO_CHAR(tbl_os.inicio_atendimento, 'DD/MM/YYYY HH:mi')                    AS inicio_atendimento,
                        TO_CHAR(tbl_os.termino_atendimento, 'DD/MM/YYYY HH:mi')                   AS fim_atendimento,
                        TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH12:MI:SS AM')                      AS data_abertura_com_hora,
                        tbl_peca.referencia                                                             AS peca_referencia,
                        tbl_os_produto.serie                                                            AS numero_serie,
                        tbl_peca.descricao                                                              AS peca_descricao,
                        tbl_produto.referencia                                                          AS produto_referencia,
                        tbl_os_item.preco,
                        tbl_servico_realizado.descricao                                                 AS servico,
                        tbl_os_item.pedido,
                        tbl_status_pedido.descricao                                                     AS status_pedido,
                        tbl_produto.descricao                                                           AS produto_descricao,
                        (
                            SELECT  tbl_classificacao.descricao
                            FROM    tbl_os_defeito_reclamado_constatado
                            JOIN    tbl_solucao         ON  tbl_solucao.solucao             = tbl_os_defeito_reclamado_constatado.solucao
                                                        AND tbl_solucao.fabrica             = {$login_fabrica}
                            JOIN    tbl_classificacao   ON  tbl_classificacao.classificacao = tbl_solucao.classificacao
                                                        AND tbl_classificacao.fabrica       = {$login_fabrica}
                            WHERE   tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                      ORDER BY      tbl_classificacao.peso DESC
                            LIMIT   1
                        )                                                                               AS classificacao,
                        tbl_tipo_atendimento.descricao                                                  AS tipo_atendimento,
                        tbl_posto_fabrica.centro_custo                                                  AS deposito,
                        tbl_posto_fabrica.conta_contabil                                                AS codigo_fornecedor,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome                                                                  AS tecnico,
                        tbl_peca.unidade,
                        JSON_FIELD('arquivo_saida_kof',tbl_os_campo_extra.campos_adicionais) AS arquivoSaida,
                        replace(JSON_FIELD('data_geracao_arquivo_saida_kof',tbl_os_campo_extra.campos_adicionais),'\\','') AS dataArquivoSaida,
                        JSON_FIELD('unidadeNegocio',tbl_os_campo_extra.campos_adicionais),
                        tbl_unidade_negocio.nome                                                        AS unidade_negocio_descricao,
                        distribuidores.descricao                                                        AS distribuidor,
                        distribuidores.descricao                                                        AS descricao_distribuidor_arquivo
                FROM    tmp_kof_$login_admin tbl_os
                JOIN    tbl_os_produto              ON  tbl_os_produto.os                               = tbl_os.os
                JOIN    tbl_produto                 ON  tbl_produto.produto                             = tbl_os_produto.produto
                                                    AND tbl_produto.fabrica_i                           = {$login_fabrica}
                JOIN    tbl_familia                 ON  tbl_familia.familia                             = tbl_produto.familia
                                                    AND tbl_familia.fabrica                             = {$login_fabrica}
                JOIN    tbl_posto                   ON  tbl_posto.posto                                 = tbl_os.posto
                JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto                         = tbl_posto.posto
                                                    AND tbl_posto_fabrica.fabrica                       = {$login_fabrica}
                JOIN    tbl_status_checkpoint       ON  tbl_status_checkpoint.status_checkpoint         = tbl_os.status_checkpoint
                JOIN    tbl_tipo_atendimento        ON  tbl_tipo_atendimento.tipo_atendimento           = tbl_os.tipo_atendimento
                                                    AND tbl_tipo_atendimento.fabrica                    = {$login_fabrica}
                {$leftJoinOsItem}
           LEFT JOIN    tbl_pedido                  ON  tbl_os_item.pedido                              = tbl_pedido.pedido
                                                    AND tbl_pedido.fabrica                              = {$login_fabrica}
           LEFT JOIN    tbl_status_pedido           ON  tbl_pedido.status_pedido                        = tbl_status_pedido.status_pedido
           LEFT JOIN    tbl_pedido_item             ON  tbl_pedido_item.pedido_item                     = tbl_os_item.pedido_item
                                                    AND tbl_pedido_item.pedido                          = tbl_pedido.pedido
                {$leftJoinServico}
           LEFT JOIN    tbl_peca                    ON  tbl_peca.peca                                   = tbl_os_item.peca
                                                    AND tbl_peca.fabrica                                = {$login_fabrica}
           LEFT JOIN    tbl_os_campo_extra          ON  tbl_os_campo_extra.os                           = tbl_os.os
                                                    AND tbl_os_campo_extra.fabrica                      = {$login_fabrica}
           LEFT JOIN    tbl_hd_chamado              ON  tbl_hd_chamado.hd_chamado                       = tbl_os.hd_chamado
                                                    AND tbl_hd_chamado.fabrica                          = {$login_fabrica}
           LEFT JOIN    tbl_hd_chamado_cockpit      ON  tbl_hd_chamado_cockpit.hd_chamado               = tbl_hd_chamado.hd_chamado
                                                    AND tbl_hd_chamado_cockpit.fabrica                  = {$login_fabrica}
           LEFT JOIN    tbl_routine_schedule_log    ON  tbl_routine_schedule_log.routine_schedule_log   = tbl_hd_chamado_cockpit.routine_schedule_log
           LEFT JOIN    tbl_unidade_negocio         ON  tbl_unidade_negocio.codigo                       = JSON_FIELD('unidadeNegocio',tbl_os_campo_extra.campos_adicionais)
           LEFT JOIN    (
                            SELECT  DISTINCT
                                    unidade_negocio,
                                    cidade
                            FROM    tbl_distribuidor_sla
                            WHERE   fabrica = {$login_fabrica}
                        ) AS unidades               ON  unidades.unidade_negocio                        = JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais)
           LEFT JOIN    tbl_cidade unidade_negocio  ON  unidade_negocio.cidade                          = unidades.cidade
           LEFT JOIN    (
                            SELECT  DISTINCT
                                    centro,
                                    descricao
                            FROM    tbl_distribuidor_sla
                            WHERE   fabrica = {$login_fabrica}
                        ) AS distribuidores         ON  distribuidores.centro                           = JSON_FIELD('centroDistribuidor', tbl_hd_chamado_cockpit.dados)
                WHERE   tbl_os.fabrica = {$login_fabrica}
                {$whereTpAtendimento}
                {$whereUnidadeNegocio}
                {$whereServico}
            ";

        //die(nl2br($sql));
        $resSql = pg_query($con, $sql);
        if (!pg_num_rows($resSql)) {
            $msg_erro["alerta"][] = "Nenhum resultado encontrado";
        }
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div id='alertError' class="alert alert-error no-print" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }
if (count($msg_erro["alerta"]) > 0) { ?>
    <div id='Alert' class="alert no-print" >
        <h4><?=implode("<br />", $msg_erro["alerta"])?></h4>
    </div>
<?php }?>
<div class="row no-print" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario no-print" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
    <br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span2" >
            <div class="control-group" id="gDtInicial">
                <label class="control-label" for="data_inicial">Data Inicial</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input id="data_inicial" name="data_inicial" class="span12 " value="<?=$data_inicial ?>" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" >
            <div class="control-group" id="gDtFinal">
                <label class="control-label" for="data_final">Data Final</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input id="data_final" name="data_final" class="span12 " value="<?=$data_final ?>" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <label class='control-label' for='tipo_atendimento'>Apenas OSs com peças</label>
            <div class='controls controls-row'>
                <input type="checkbox" <?php if ($apenas_peca == 1) {echo 'checked';}?>   name="apenas_peca" value="1" />
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <?php
        $unidadenegocio = $_POST['unidadenegocio'];
    ?>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class='control-group'>
                <label class="control-label" for="unidade_negocio" >Unidade de Negócio</label>
                <div class='controls controls-row'>
                    <select id="unidadenegocio" multiple="multiple" name="unidadenegocio[]" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                                $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();
                                foreach ($distribuidores_disponiveis as $unidadeNegocio) {
                                    if (in_array($unidadeNegocio["unidade_negocio"], array(6102,6103,6104,6105,6106,6107,6108))) {
                                        unset($unidadeNegocio["unidade_negocio"]);
                                        continue;
                                    }
                                    $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
                                }

                                foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                                    $selected = (in_array($unidade, $unidadenegocio)) ? 'SELECTED' : '';
                                    echo "<option value='{$unidade}' {$selected}> {$descricaoUnidade}</option>";
                                }
                            ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class='control-group'>
                <label class='control-label' for='tipo_atendimento'>Tipo Atendimento:</label>
                <div class='controls controls-row'>
                    <select id="tipo_atendimento" name='tipo_atendimento[]' class='span12' multiple="multiple">
                        <option value=''>Selecione</option>
                        <?php
                        $sql = "SELECT tipo_atendimento,descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo;";
                        $res   = pg_exec($con,$sql);

                        for($i = 0; $i < pg_numrows($res); $i++){
                            $tipo_atendimento_id = pg_result($res,$i,tipo_atendimento);
                            $descricao   = pg_result($res,$i,descricao);
                            $retorno .= "<option value={$tipo_atendimento_id} ";
                            if (in_array($tipo_atendimento_id, $tipo_atendimento)) {
                                $retorno .= 'selected';
                            }
                            $retorno .= ">{$descricao}</option>";
                        }
                        echo $retorno;
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <br />
    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>

    <br />
</form>

</div>

<?php
if ($_POST && empty($msg_erro["msg"]) && empty($msg_erro["alerta"])) {

    if (pg_num_rows($resSql) > 0) {

        $csv  = "relatorio-faturamento-kof-{$login_fabrica}-".date("YmdHi").".csv";
        $file = fopen("/tmp/".$csv, "w");
        $titulo = array(
            'os',
            'patrimonio',
            'solucao',
            'arquivo_entrada',
            'qtde',
            'qtde_cancelada',
            'qtde_faturada',
            'familia',
            'observacao',
            'cliente_os',
            'oskof',
            'protocolokof',
            'idcliente',
            'cliente_estado',
            'status',
            'data_abertura',
            'data_fechamento',
            'inicio_atendimento',
            'fim_atendimento',
            'data_abertura_com_hora',
            'peca_referencia',
            'numero_serie',
            'peca_descricao',
            'produto_referencia',
            'preco',
            'servico',
            'pedido',
            'status_pedido',
            'produto_descricao',
            'classificacao',
            'tipo_atendimento',
            'deposito',
            'codigo_fornecedor',
            'codigo_posto',
            'tecnico',
            'unidade',
            'arquivosaida',
            'dataarquivosaida',
            'unidadeNegocio',
            'unidade_negocio_descricao',
            'distribuidor',
            'descricao_distribuidor_arquivo',
        );
        $linhas = implode("§", $titulo)."\r\n";
		fwrite($file,$linhas);

        $contadorresSql = pg_num_rows($resSql);

		for($lt = 0; $lt < $contadorresSql; $lt++) {
			for($gt = 0; $gt < pg_num_fields($resSql); $gt++) {
				fwrite($file,  str_replace(";","",pg_result($resSql, $lt, $gt)));
				if(($gt+1)  == pg_num_fields($resSql))
					fwrite($file,"\r\n");
				else
					fwrite($file,"§");
			}
	    }


        fclose($file);
        system("mv /tmp/{$csv} xls/{$csv}");

    }
?>

    <br />

    <p class="tac no-print" >
        <button type="button" class="btn btn-success download-csv" data-csv="<?=$csv?>" ><i class="icon-download-alt icon-white" ></i> Download CSV</button>
    </p>
<?php
}

$plugins = array(
    "select2",
    "mask"
);

include "plugin_loader.php";
?>
<script>
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
});

$("select").select2();

$("button.download-csv").on("click", function() {
    var csv = $(this).data("csv");

    window.open("xls/"+csv);
});

</script>

<br />

<?php
include "rodape.php";
?>
