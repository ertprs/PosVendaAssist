<?php
$admin_privilegios = 'auditoria';
$layout_menu       = 'auditoria';
$title             = 'Devolução Pendente mais de 90 dias';
$plugins           = array("datepicker", "maskedinput", "datatable_responsive", "shadowbox");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'atualizar_nf_retorno') {

        pg_query($con, 'BEGIN');
        $erro_alteracao = false;

        $info_notas = $_POST['info_notas'];
        foreach ($info_notas as $nf_faturamento) {
            $nf_faturamento = explode('_', $nf_faturamento);

            $nota_fiscal      = $nf_faturamento[0];
            $faturamento_item = $nf_faturamento[1];
            $posto            = $nf_faturamento[2];
            $os_extrato       = $nf_faturamento[3];
            $extrato_tela     = $nf_faturamento[4];

            $sql = "SELECT tbl_faturamento.faturamento
                    FROM tbl_faturamento
                        JOIN tbl_fabrica USING(fabrica)
                    WHERE tbl_faturamento.fabrica = $login_fabrica
                        AND tbl_faturamento.nota_fiscal = '$nota_fiscal'
                        AND tbl_faturamento.distribuidor = $posto
                        AND tbl_faturamento.posto = tbl_fabrica.posto_fabrica";

            $res = pg_query($con, $sql);
            if(pg_num_rows($res) > 0){
                $faturamento = pg_fetch_result($res, 0,'faturamento');
            }else{
                /*Se não existir o faturamento, pegar as informações do faturamento de origem para inserir o novo faturamento*/

                $sql = "SELECT
                            tbl_faturamento.total_nota,
                            tbl_faturamento.nota_fiscal,
                            tbl_faturamento.serie,
                            tbl_faturamento.natureza,
                            tbl_faturamento.base_icms,
                            tbl_faturamento.valor_icms,
                            tbl_faturamento.base_ipi,
                            tbl_faturamento.valor_ipi,
                            tbl_faturamento.cfop,
                            tbl_fabrica.posto_fabrica
                        FROM tbl_faturamento
                            JOIN tbl_fabrica USING(fabrica)
                            JOIN tbl_faturamento_item USING(faturamento)
                        WHERE tbl_faturamento_item.faturamento_item = $faturamento_item";
                $res = pg_query($con, $sql);

                $total_nota    = pg_fetch_result($res, 0, 'total_nota');
                $nf_origem     = pg_fetch_result($res, 0, 'nota_fiscal');
                $serie         = pg_fetch_result($res, 0, 'serie');
                $natureza      = pg_fetch_result($res, 0, 'natureza');
                $base_icms     = pg_fetch_result($res, 0, 'base_icms');
                $valor_icms    = pg_fetch_result($res, 0, 'valor_icms');
                $base_ipi      = pg_fetch_result($res, 0, 'base_ipi');
                $valor_ipi     = pg_fetch_result($res, 0, 'valor_ipi');
                $cfop          = pg_fetch_result($res, 0, 'cfop');
                $posto_fabrica = pg_fetch_result($res, 0, 'posto_fabrica');

                if (empty($natureza)) {
                    $natureza = 'null';
                }else{
                    $natureza = "'$natureza'";
                }

                if (empty($base_icms)) {
                    $base_icms = '0';
                }
                if (empty($valor_icms)) {
                    $valor_icms = '0';
                }
                if (empty($base_ipi)) {
                    $base_ipi = '0';
                }
                if (empty($valor_ipi)) {
                    $valor_ipi = '0';
                }

                $sql = "INSERT INTO tbl_faturamento (
                            fabrica,
                            emissao,
                            saida,
                            posto,
                            distribuidor,
                            total_nota,
                            nota_fiscal,
                            serie,
                            natureza,
                            base_icms,
                            valor_icms,
                            base_ipi,
                            valor_ipi,
                            cfop
                        ) VALUES (
                            $login_fabrica,
                            CURRENT_DATE,
                            CURRENT_DATE,
                            $posto_fabrica,
                            $posto,
                            $total_nota,
                            '$nota_fiscal',
                            '$serie',
                            $natureza,
                            $base_icms,
                            $valor_icms,
                            $base_ipi,
                            $valor_ipi,
                            '$cfop'
                        ) RETURNING faturamento";

                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    $erro_alteracao = true;
                    pg_query($con, 'ROLLBACK');
                    break;
                }
                $faturamento = pg_fetch_result($res, 0,'faturamento');
            }

            $sql = "SELECT count(*) AS contador
                    FROM tbl_faturamento_item
                        JOIN tbl_peca on tbl_faturamento_item.peca = tbl_peca.peca
                        JOIN tbl_faturamento USING (faturamento)
                        JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento_item.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca and (tbl_faturamento_item.faturamento = tbl_extrato_lgr.faturamento or tbl_extrato_lgr.faturamento isnull)
                    WHERE tbl_faturamento.fabrica = {$login_fabrica}
                        AND tbl_faturamento_item.extrato_devolucao = {$extrato_tela}
                        AND tbl_faturamento.posto = {$posto}
                        AND (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END) > 0
                        AND (tbl_faturamento.cfop ilike '59%' OR tbl_faturamento.cfop ilike '69%' OR tbl_faturamento_item.cfop ilike '59%' OR tbl_faturamento_item.cfop ilike '69%')
                        AND (tbl_faturamento.distribuidor IS NULL OR tbl_faturamento.distribuidor = 4311 )
                        AND (tbl_peca.produto_acabado IS TRUE OR tbl_peca.devolucao_obrigatoria = 't')
                        AND tbl_faturamento.emissao > '2005-10-01'";
            $res = pg_query($con, $sql);
            if (pg_fetch_result($res, 0, 'contador') - 1 <= 0) {
                $sql = "UPDATE tbl_faturamento
                        SET conferencia = CURRENT_TIMESTAMP, devolucao_concluida = 't'
                        WHERE faturamento = $faturamento
                        AND fabrica = $login_fabrica";

                $res = pg_query ($con,$sql);
                if (strlen(pg_last_error()) > 0) {
                    $erro_alteracao = true;
                    pg_query($con, 'ROLLBACK');
                    break;
                }
            }

            /* Pegar informações dos itens para inserir o faturamento item do faturamento de devolução*/
            $sql = "SELECT
                        tbl_faturamento.nota_fiscal,
                        tbl_faturamento_item.qtde,
                        tbl_faturamento_item.peca,
                        tbl_faturamento_item.preco,
                        tbl_faturamento_item.aliq_icms,
                        tbl_faturamento_item.aliq_ipi,
                        tbl_faturamento_item.base_icms,
                        tbl_faturamento_item.devolucao_obrig,
                        tbl_faturamento_item.valor_icms,
                        tbl_faturamento_item.linha,
                        tbl_faturamento_item.base_ipi,
                        tbl_faturamento_item.valor_ipi,
                        tbl_faturamento_item.sequencia,
                        tbl_faturamento_item.os,
                        tbl_faturamento_item.os_item,
                        tbl_faturamento_item.extrato_devolucao
                    FROM tbl_faturamento_item
                        JOIN tbl_faturamento USING (faturamento)
                        JOIN tbl_peca USING(peca)
                    WHERE tbl_faturamento.fabrica = $login_fabrica
                        AND tbl_faturamento.posto = $posto
                        AND tbl_faturamento_item.faturamento_item = $faturamento_item
                        AND tbl_faturamento.distribuidor IS NULL
                    ORDER BY tbl_faturamento.nota_fiscal";
            $res = pg_query($con, $sql);
            $peca_qtde_total_nf = pg_fetch_result($res, 0, 'qtde');
            $peca_qtde          = pg_fetch_result($res, 0, 'qtde');
            $extrato            = pg_fetch_result($res, 0, 'extrato_devolucao');
            $peca               = pg_fetch_result($res, 0, 'peca');
            $peca_preco         = pg_fetch_result($res, 0, 'preco');
            $peca_aliq_icms     = pg_fetch_result($res, 0, 'aliq_icms');
            $peca_aliq_ipi      = pg_fetch_result($res, 0, 'aliq_ipi');
            $peca_base_icms     = pg_fetch_result($res, 0, 'base_icms');
            $devolucao_obrig    = pg_fetch_result($res, 0, 'devolucao_obrig');
            $peca_valor_icms    = pg_fetch_result($res, 0, 'valor_icms');
            $linha              = pg_fetch_result($res, 0, 'linha');
            $peca_base_ipi      = pg_fetch_result($res, 0, 'base_ipi');
            $peca_valor_ipi     = pg_fetch_result($res, 0, 'valor_ipi');
            $sequencia          = pg_fetch_result($res, 0, 'sequencia');
            $nf_origem          = pg_fetch_result($res, 0, 'nota_fiscal');

            $peca_aliq_icms  = (empty($peca_aliq_icms)) ? 0 : $peca_aliq_icms;
            $peca_aliq_ipi   = (empty($peca_aliq_ipi)) ? 0 : $peca_aliq_ipi;
            $peca_aliq_ipi   = (empty($peca_aliq_ipi)) ? 0 : $peca_aliq_ipi;
            $peca_base_icms  = (empty($peca_base_icms)) ? 0 : $peca_base_icms;
            $peca_valor_icms = (empty($peca_valor_icms)) ? 0 : $peca_valor_icms;
            $peca_valor_ipi  = (empty($peca_valor_ipi)) ? 0 : $peca_valor_ipi;
            $peca_base_ipi   = (empty($peca_base_ipi)) ? 0 : $peca_base_ipi;
            $devolucao_obrig = (empty($devolucao_obrig)) ? 'f' : $devolucao_obrig;

            $os = $os_item = $os_extrato;

            /*Atualizar o extrato LGR com a quandidade pendente que aparece no relatório*/

            $sql = "UPDATE tbl_extrato_lgr
                        SET qtde_nf = COALESCE(qtde_nf,0) + $peca_qtde_total_nf
                    WHERE extrato = $extrato
                        AND peca = $peca";

            pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                $erro_alteracao = true;
                pg_query($con, 'ROLLBACK');
                break;
            }

            /* Inserir novos itens para o faturamento de devolução */
            $sql = "INSERT INTO tbl_faturamento_item(
                        faturamento,
                        peca,
                        qtde,
                        preco,
                        aliq_icms,
                        aliq_ipi,
                        base_icms,
                        valor_icms,
                        base_ipi,
                        valor_ipi,
                        nota_fiscal_origem,
                        extrato_devolucao,
                        devolucao_obrig,
                        os,
                        qtde_inspecionada
                    ) VALUES (
                        $faturamento,
                        $peca,
                        $peca_qtde,
                        $peca_preco,
                        $peca_aliq_icms,
                        $peca_aliq_ipi,
                        $peca_base_icms,
                        $peca_valor_icms,
                        $peca_base_ipi,
                        $peca_valor_ipi,
                        '$nf_origem',
                        $extrato,
                        '$devolucao_obrig',
                        $os,
                        $peca_qtde
                    )";

            pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                $erro_alteracao = true;
                pg_query($con, 'ROLLBACK');
                break;
            }

            /*Retirar a pendencia da peça*/
            $sql = "UPDATE tbl_extrato_lgr
                        SET qtde_pedente_temp = qtde_pedente_temp - $peca_qtde
                    WHERE extrato = $extrato
                        AND peca = $peca";

            pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                $erro_alteracao = true;
                pg_query($con, 'ROLLBACK');
                break;
            }
        }
        if (!$erro_alteracao) {
            pg_query($con, 'COMMIT');
            exit(json_encode(array('ok' => utf8_encode('Baixa realizada com sucesso'))));
        }else{
            exit(json_encode(array('error' => utf8_encode('Ocorreu um erro ao tentar atualizar as notas ficais de retorno'))));
        }
    }
}

if ($btn_acao == 'pesquisar' || $btn_acao == 'listar_todos') {
    $where = '';
    if (!empty($numero_extrato)) {
        $where .= " AND tbl_faturamento_item.extrato_devolucao = $numero_extrato ";
    }else{
        $campos_erro[] = 'numero_extrato';
    }
    if (!empty($data_inicial) && !empty($data_final) && $btn_acao == 'pesquisar') {
        $campos_erro = array();
        $xdata_inicial = implode('-', array_reverse(explode('/', $data_inicial)));
        $xdata_final = implode('-', array_reverse(explode('/', $data_final)));

        $dataComp = new DateTime('-90 days');

        if ($xdata_inicial > $xdata_final) {
            $campos_erro[] = 'data_inicial';
            $campos_erro[] = 'data_final';
            $msg_erro = 'Data inválida para pesquisa';
        } else if ($xdata_final > $dataComp->format('Y-m-d')) {
            $msg_erro = 'Data Final inválida. Limite é de 90 dias anteriores à data de hoje.';
            $campos_erro[] = 'data_final';
        } else {
            $where .= "AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial 00:00' and '$xdata_final 23:59:59' ";
        }
    } else {
        if (empty($numero_extrato)) {
            if (empty($data_inicial)) {
                $campos_erro[] = 'data_inicial';
            }
            if (empty($data_final)) {
                $campos_erro[] = 'data_final';
            }
        }
    }

    if ($baixados) {
            $finalWhere .= "WHERE devolucao_pendente.nota_retorno IS NOT NULL";
            $condNota    = "AND fid.nota_fiscal_origem      <> tbl_faturamento.nota_fiscal";
//         $where .= "AND (tbl_extrato_lgr.qtde - COALESCE(tbl_extrato_lgr.qtde_nf ,0)) = 0";
    } else {

        $finalWhere .= "
            WHERE   devolucao_pendente.peca_pendente > 0
            AND     devolucao_pendente.nota_retorno IS NULL
            AND     devolucao_pendente.extrato_devolucao IN (
                SELECT  tbl_extrato.extrato
                FROM    tbl_extrato
                WHERE   fabrica = $login_fabrica
                AND     (CURRENT_DATE - tbl_extrato.data_geracao::DATE)::INT > 90
            )
        ";
        $condNota    = "AND fid.nota_fiscal_origem      = tbl_faturamento.nota_fiscal";
//         $where .= "AND (tbl_extrato_lgr.qtde - COALESCE(tbl_extrato_lgr.qtde_nf ,0)) > 0";
    }

    if (!empty($posto)) {
        if (!empty($codigo_posto) && !empty($nome_posto)) {
            $where .= " AND tbl_posto.posto = $posto";
        }else{
            unset($posto);
        }
    }

    if (!empty($peca)) {
        if (!empty($referencia_peca) && !empty($descricao_peca)) {
            $where .= " AND tbl_peca.peca = $peca";
        }else{
            unset($peca);
        }
    }

    if (!empty($produto)) {
        if (!empty($referencia_produto) && !empty($descricao_produto)) {
            $where .= " AND (tbl_os_produto.produto = $produto OR (tbl_peca.referencia = '$referencia_produto' AND tbl_peca.produto_acabado IS TRUE))";
        }else{
            unset($produto);
        }
    }

    if ($btn_acao == 'listar_todos') {
        $dateFinal      = new DateTime('-90 days');
        $dateInicial    = new DateTime($dateFinal->format('Y-m-d').'-6 months');

        $dt_inicial = $dateInicial->format('Y-m-d');
        $dt_final   = $dateFinal->format('Y-m-d');

        $data_inicial = $dateInicial->format('d/m/Y');
        $data_final   = $dateFinal->format('d/m/Y');

        $campos_erro = array();
        $where .= " AND tbl_extrato.data_geracao BETWEEN ('$dt_inicial'::DATE || ' 00:00')::TIMESTAMP AND ('$dt_final'::DATE || ' 23:59')::TIMESTAMP";
    }

    $rows = 0;
    if (!count($campos_erro)) {
        $sql = "
            SELECT  * FROM (
                SELECT  tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.extrato_devolucao,
                        tbl_os_produto.os,
                        tbl_posto.posto,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome,
                        tbl_os_produto.serie,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_faturamento_item.pedido,
                        tbl_faturamento.nota_fiscal,
                        SUM(tbl_faturamento_item.qtde)                                              AS qtde_real,
                        tbl_extrato_lgr.qtde - COALESCE(tbl_extrato_lgr.qtde_nf ,0)                 AS qtde_total_item,
                        tbl_extrato_lgr.qtde_nf                                                     AS qtde_total_nf,
                        tbl_extrato_lgr.qtde_pedente_temp                                           AS qtde_pedente_temp,
                        (tbl_extrato_lgr.qtde - COALESCE(tbl_extrato_lgr.qtde_nf,0))                AS peca_pendente,
                        (CURRENT_DATE - tbl_extrato.data_geracao::date)::text||' dias'              AS tempo_pendente,
                        fd.nota_fiscal                                                              AS nota_retorno
                FROM    tbl_faturamento_item
                JOIN    tbl_peca                    USING(peca)
                JOIN    tbl_faturamento             USING(faturamento)
                JOIN    tbl_posto                   USING(posto)
                JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
                JOIN    tbl_extrato_lgr             ON  tbl_extrato_lgr.extrato     = tbl_faturamento_item.extrato_devolucao
                                                    AND tbl_extrato_lgr.peca        = tbl_faturamento_item.peca
                JOIN    tbl_extrato                 ON  tbl_extrato.extrato         = tbl_extrato_lgr.extrato
                                                    AND tbl_extrato.fabrica         = $login_fabrica
                JOIN    tbl_os_item                 ON  tbl_os_item.pedido_item     = tbl_faturamento_item.pedido_item
                JOIN    tbl_os_produto              ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
           LEFT JOIN    tbl_faturamento_item fid    ON  fid.extrato_devolucao       = tbl_extrato.extrato
                                                    AND fid.peca                    = tbl_faturamento_item.peca
													AND fid.nota_fiscal_origem      IS NOT NULL
													$condNota
           LEFT JOIN    tbl_faturamento fd          ON  fd.faturamento              = fid.faturamento
                                                    AND fd.distribuidor             = tbl_faturamento.posto
                                                    AND fd.fabrica                  = $login_fabrica
                WHERE   tbl_faturamento.fabrica = $login_fabrica
                AND     (
                            tbl_faturamento_item.cfop   ILIKE '59%'
                        OR  tbl_faturamento_item.cfop   ILIKE '69%'
                        OR  tbl_faturamento.cfop        ILIKE '59%'
                        OR  tbl_faturamento.cfop        ILIKE '69%'
                        )
                AND     tbl_faturamento.distribuidor IS NULL
                AND     (
                            tbl_peca.produto_acabado        IS TRUE
                        OR  tbl_os_item.peca_obrigatoria    IS TRUE
                        )
                AND     tbl_faturamento.emissao > '2005-10-01'
                $where
          GROUP BY      tbl_faturamento_item.faturamento_item,
                        tbl_faturamento_item.extrato_devolucao,
                        tbl_os_produto.os,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.posto,
                        tbl_posto.nome,
                        tbl_os_produto.serie,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_faturamento_item.pedido,
                        tbl_faturamento.nota_fiscal,
                        tbl_faturamento.fabrica,
                        tbl_extrato_lgr.qtde,
                        qtde_total_nf,
                        qtde_pedente_temp,
                        peca_pendente,
                        tempo_pendente,
                        fd.nota_fiscal
          ORDER BY      tbl_posto.nome
                    ) devolucao_pendente
            $finalWhere";
// exit(nl2br($sql));
        $res_consulta  = pg_query($con, $sql);
        $num_rows      = pg_num_rows($res_consulta);

        if (isset($_POST['gerar_excel'])) {
            $data = date ("dmY");
            $fileName = "devolucao_pendente-{$data}.csv";

            $file = fopen("/tmp/{$fileName}", "w");
            fwrite($file, utf8_encode("Extrato;OS;Posto Autorizado;Número de Série;Item;Pedido;NF;Tempo Pendente\n"));

            for ($i = 0; $i < $num_rows; $i++) {
                $nota_retorno      = pg_fetch_result($res_consulta, $i, 'nota_retorno');
                $extrato_devolucao = pg_fetch_result($res_consulta, $i, 'extrato_devolucao');
                $faturamento_item  = pg_fetch_result($res_consulta, $i, 'faturamento_item');
                $id_posto          = pg_fetch_result($res_consulta, $i, 'posto');
                $os_extrato        = pg_fetch_result($res_consulta, $i, 'os');
                $serie_consulta    = pg_fetch_result($res_consulta, $i, 'serie');
                $pedido_consulta   = pg_fetch_result($res_consulta, $i, 'pedido');
                $nota_fiscal       = pg_fetch_result($res_consulta, $i, 'nota_fiscal');
                $tempo_pendente    = pg_fetch_result($res_consulta, $i, 'tempo_pendente');
                $posto_consulta    = pg_fetch_result($res_consulta, $i, 'codigo_posto').' - '.pg_fetch_result($res_consulta, $i, 'nome');
                $item              = pg_fetch_result($res_consulta, $i, 'referencia').' - '.pg_fetch_result($res_consulta, $i, 'descricao');

                fwrite($file, utf8_encode("$extrato_devolucao;$os_extrato;$posto_consulta;$serie_consulta;$item;$pedido_consulta;$nota_fiscal;$tempo_pendente\n"));
            }

            fclose($file);
            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
            exit;
        }
    }else{
        if (empty($msg_erro)) {
            $msg_erro = 'Preencha os campos obrigatórios';
        }
    }
}

include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';

?>
<div id="alertas_tela">
    <?php if (!empty($msg_erro)) { ?>
    <div class="alert alert-danger"><h4><?=$msg_erro;?></h4></div>
    <?php } ?>
</div>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<?=$_SERVER['PHP_SELF']?>">
    <div class="titulo_tabela">Devolução Pendente</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('numero_extrato', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='numero_extrato'>Nº do Extrato</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input type="text" name="numero_extrato" value="<?=$numero_extrato?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('data_inicial', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input type="text" name="data_inicial" id="data_inicial" value="<?=$data_inicial?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('data_final', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input type="text" name="data_final" id="data_final" value="<?=$data_final?>">
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='codigo_posto'>Código do Posto</label>
                <div class='controls controls-row'>
                    <input type="text" name="codigo_posto" value="<?=$codigo_posto?>">
                    <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                    <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    <input type="hidden" name="posto" id="posto" value="<?=$posto?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='nome_posto'>Nome do Posto</label>
                <div class='controls controls-row'>
                    <input type="text" name="nome_posto" value="<?=$nome_posto?>">
                    <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                    <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='referencia_peca'>Refêrencia da Peça</label>
                <div class='controls controls-row'>
                    <input type="text" name="referencia_peca" value="<?=$referencia_peca?>">
                    <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                    <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    <input type="hidden" name="peca" id="peca" value="<?=$peca?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='descricao_peca'>Descrição da Peça</label>
                <div class='controls controls-row'>
                    <input type="text" name="descricao_peca" value="<?=$descricao_peca?>">
                    <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                    <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='referencia_produto'>Refêrencia do Produto</label>
                <div class='controls controls-row'>
                    <input type="text" name="referencia_produto" value="<?=$referencia_produto?>">
                    <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                    <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    <input type="hidden" name="produto" id="produto" value="<?=$produto?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='descricao_produto'>Descrição do Produto</label>
                <div class='controls controls-row'>
                    <input type="text" name="descricao_produto" value="<?=$descricao_produto?>">
                    <span class='add-on' rel="lupa" style="<?=$hidden?>"><i class='icon-search' style="cursor: pointer;"></i></span>
                    <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='baixado'>Baixados</label>
                <div class='controls controls-row'>
                    <input type="checkbox" name="baixados" value="t" <?=($baixados == 't') ? 'checked' : '' ?>>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button type="button" class="btn" name="btn_acao_pesquisar" value="">Pesquisar</button>
            <button type="button" class="btn btn-primary" name="btn_acao_listar" value="">Listar Todos</button>
            <input type="hidden" name="btn_acao" id="btn_acao">
        </div>
    </div>
</form>
<?php if ($num_rows > 0) {
    echo "<div class='alert alert-warning' id='alert-loading'><h3>Carregando tabela, aguarde...</h3></div>";
?>
<table id="resultado_faturamento" class='table table-striped table-bordered table-hover table-large table-fixed'>
    <thead>
        <tr class='titulo_coluna'>
            <th>Extrato</th>
            <th>OS</th>
            <th>Posto Autorizado</th>
            <th>Número de Série</th>
            <th>Item</th>
            <th>Pedido</th>
            <th>NF</th>
            <th>Tempo Pendente</th>
            <th>NF de Retorno</th>
        </tr>
    </thead>
    <tbody>
        <?php
        for ($i = 0; $i < $num_rows; $i++) {
            $nota_retorno      = pg_fetch_result($res_consulta, $i, 'nota_retorno');
            $extrato_devolucao = pg_fetch_result($res_consulta, $i, 'extrato_devolucao');
            $faturamento_item  = pg_fetch_result($res_consulta, $i, 'faturamento_item');
            $id_posto          = pg_fetch_result($res_consulta, $i, 'posto');
            $os_extrato        = pg_fetch_result($res_consulta, $i, 'os');
            $serie_consulta    = pg_fetch_result($res_consulta, $i, 'serie');
            $pedido_consulta   = pg_fetch_result($res_consulta, $i, 'pedido');
            $nota_fiscal       = pg_fetch_result($res_consulta, $i, 'nota_fiscal');
            $tempo_pendente    = pg_fetch_result($res_consulta, $i, 'tempo_pendente');
            $posto_consulta    = pg_fetch_result($res_consulta, $i, 'codigo_posto').' - '.pg_fetch_result($res_consulta, $i, 'nome');
            $item              = pg_fetch_result($res_consulta, $i, 'referencia').' - '.pg_fetch_result($res_consulta, $i, 'descricao')
;        ?>
            <tr>
                <td><?="<a href='extrato_consulta_os.php?extrato=$extrato_devolucao' target='_BLANK'>$extrato_devolucao</a>" ?></td>
                <td><?=$os_extrato ?></td>
                <td><?=$posto_consulta ?></td>
                <td><?=$serie_consulta ?></td>
                <td width="20%"><?=$item ?></td>
                <td><?=$pedido_consulta ?></td>
                <td><?=$nota_fiscal ?></td>
                <td><?=$tempo_pendente ?></td>
                <td><?=($nota_retorno !== '' && $nota_retorno !== null) ? $nota_retorno : "<input class='span2' type='text' name='nota_fiscal' data-faturamento_item='$faturamento_item' data-posto='$id_posto' data-os='$os_extrato' data-extrato='$extrato_devolucao'>" ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<br />
<div class="row-fluid">
    <div class="span4"></div>
    <div class="span4 tac">
        <?php if ($_POST['baixados'] !== 't') { ?>
        <button class="btn" name="btn_baixar" id="btn_baixar" value="baixar">Baixar</button>
        <?php } ?>
        <br />
        <?php $jsonPOST = excelPostToJson($_POST); ?>
        <div class="btn_excel" id='gerar_excel'>
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
            <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
        </div>
    </div>
</div>
<?php }elseif ($btn_acao == 'pesquisar' || $btn_acao == 'listar_todos') { ?>
    <div class="alert alert-warning"><h4>Nenhum registro encontrado</h4></div>
<?php } ?>
<script type="text/javascript">
    var table = null;

    $(function(){
        Shadowbox.init();
        var data_final = new Date();
        data_final.setDate(data_final.getDate() - 90);
        var data = data_final.getDate()+"/"+(data_final.getMonth()+1)+"/"+data_final.getFullYear();

        $('#data_inicial').datepicker({startDate:'01/01/2010',maxDate:data}).mask("99/99/9999");
        $('#data_final').datepicker({minDate:'01/01/2010',maxDate:data}).mask("99/99/9999");
        table = $('#resultado_faturamento').DataTable({
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 2 },
                { responsivePriority: 4, targets: 3 },
                { responsivePriority: 5, targets: 4 },
                { responsivePriority: 6, targets: 5 },
                { responsivePriority: 7, targets: 8 },
                { responsivePriority: 8, targets: 6 },
                { responsivePriority: 9, targets: 7 },
            ],
            order: [
                [ 0, 'asc' ]
            ],
            paging: true,
            searching: true,
            info: true,
            language: {
                "zeroRecords": "Nenhum resultado encontrado",
                "sLengthMenu": "_MENU_ Registros por página",
                "sInfoFiltered": " ( filtrando _MAX_ registros ) ",
                "info": "Mostrando de _START_ a _END_ de _TOTAL_ registros",
                "sSearch": "Pesquisar",
                "oPaginate": { "sNext": "Próxima", "sPrevious": "Anterior" },
                "sInfoEmpty": "Mostrando de 0 a _END_ de _TOTAL_ registros",
                "sZeroRecords": "Nenhum registro encontrado"
            }
        });
        if ($('#alert-loading').length) {
            $('#alert-loading').hide();
        }
    });

    $('button[name=btn_acao_pesquisar]').on('click', function(){
        $('#btn_acao').val('pesquisar');
        $(this).parents('form').submit();
    });

    $('button[name=btn_acao_listar]').on('click', function(){
        $('#btn_acao').val('listar_todos');
        $('form[name=frm_familia]').submit();
        $(this).parents('form').submit();
    });

    $('#btn_baixar').on('click', function(){
        var info_notas = [];

        /* VERIFICA TODAS AS LINHAS DO DATATABLE E PEGA OS VALORES DOS INPUTS */
        table.rows().every( function (rowIdx, tableLoop, rowLoop) {
            var cell = table.cell({ row: rowIdx, column: 8 }).node();

            if ($('input', cell).val() !== '' && $('input', cell).val() !== undefined) {
                info_notas.push($('input', cell).val()+'_'+$('input', cell).data('faturamento_item')+'_'+$('input', cell).data('posto')+'_'+$('input', cell).data('os')+'_'+$('input', cell).data('extrato'));

                $('input', cell).hide().after('<label>'+$('input', cell).val()+'</label>');
            }
        });

        loading('show');
        $.ajax({
            url: window.open.href,
            method: 'POST',
            data: { ajax: 'sim', action: 'atualizar_nf_retorno', info_notas: info_notas },
            timeout: 9000
        }).fail(function(){
            loading('hide');
            alert('Ocorreu um erro ao tentar atualizar as notas ficais de retorno, tente novamente mais tarde');
        }).done(function(data){
            loading('hide');
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                $('button[name=btn_acao_listar]').trigger('click');
            }else{
                alert(data.error);
            }
        });
    });

    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    function retorna_posto(retorno){
        $('#posto').val(retorno.posto);
        $('input[name=codigo_posto]').val(retorno.codigo);
        $('input[name=nome_posto]').val(retorno.nome);
    }

    function retorna_peca(retorno){
        $('#peca').val(retorno.peca);
        $('input[name=referencia_peca]').val(retorno.referencia);
        $('input[name=descricao_peca]').val(retorno.descricao.trim());
    }

    function retorna_produto(retorno){
        $('#produto').val(retorno.produto);
        $('input[name=referencia_produto]').val(retorno.referencia);
        $('input[name=descricao_produto]').val(retorno.descricao.trim());
    }
</script>
<?php include 'rodape.php'; ?>
