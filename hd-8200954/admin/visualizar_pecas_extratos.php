<?php

    include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_admin.php";
    include "funcoes.php";

    $layout_menu = "auditoria";
    $title = "RELATORIO PEÇAS POR EXTRATO";
    include "cabecalho_new.php";
    $plugins = array(
        "autocomplete",
        "datepicker",
        "shadowbox",
        "mask",
        "dataTable"
    );

    include("plugin_loader.php");

    $xdata_inicial  = $_GET['data_inicial'];
    $xdata_final    = $_GET['data_final'];
    $xposto          = $_GET['posto'];

    $cond_data  = " AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial 00:00:01' AND '$xdata_final 23:59:59'";
    $cond_posto = " AND tbl_extrato_lgr.posto = $xposto";

	$sql =  "SELECT min(tbl_extrato.extrato) as extrato, tbl_extrato.posto,
			(select min(fi.extrato_devolucao) from  tbl_faturamento
					join tbl_faturamento_item fi using(faturamento)
					where distribuidor = tbl_extrato.posto
					and fabrica = 91
					and conferencia isnull
				) as extrato_sem_conferencia
            FROM tbl_extrato
            JOIN tbl_extrato_lgr  using(extrato)
            JOIN tbl_peca ON tbl_extrato_lgr.peca = tbl_peca.peca
			JOIN tbl_faturamento_item ON tbl_faturamento_item.peca = tbl_extrato_lgr.peca and tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato
			JOIN tbl_os_item USING(pedido, pedido_item) 
            JOIN tbl_posto on tbl_extrato.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica  on tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_extrato.fabrica = $login_fabrica
            AND (tbl_extrato_lgr.qtde_nf is null or tbl_extrato_lgr.qtde_nf = 0)
			AND (tbl_os_item.peca_obrigatoria or tbl_peca.devolucao_obrigatoria)
            AND tbl_extrato.extrato NOT IN (
                SELECT DISTINCT fi.extrato_devolucao
                FROM tbl_faturamento
                JOIN tbl_faturamento_item fi using(faturamento)
                WHERE distribuidor = tbl_extrato.posto
                AND fabrica = $login_fabrica
            )
            $cond_posto
            $cond_data
            GROUP BY tbl_extrato.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome;";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $extrato = trim(pg_fetch_result($res,0,extrato));
        $extrato_sem_conferencia		= trim(pg_fetch_result($res,0,'extrato_sem_conferencia'));
        $cond_ext = ($extrato_sem_conferencia < $extrato and !empty($extrato_sem_conferencia)) ? " and tbl_extrato.extrato > $extrato_sem_conferencia " : " AND tbl_extrato.extrato >= $extrato ";
        $sql2 = "SELECT tbl_extrato.extrato
                FROM tbl_extrato
                JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE tbl_extrato.fabrica = $login_fabrica
                $cond_ext
                AND tbl_extrato.posto = $xposto
                GROUP BY tbl_extrato.extrato,tbl_extrato.posto, tbl_posto.nome,tbl_extrato.data_geracao
                ORDER BY tbl_posto.nome, tbl_extrato.data_geracao;";
        $res2  = pg_query($con,$sql2);

        if(pg_num_rows($res2) > 0){
            $rows2 = pg_num_rows($res2);
            ?>

            <?php
            for ($i=0; $i < $rows2; $i++) {
                $extratos = pg_fetch_result($res2, $i, extrato);
                $sql3 = "SELECT distinct tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_extrato_lgr.qtde,
                            tbl_extrato_lgr.extrato,
                            (
                                SELECT array_to_string(array_agg(tbl_faturamento.nota_fiscal), ',')
                                FROM tbl_faturamento
                                INNER JOIN tbl_faturamento_item AS FI USING(faturamento)
                                WHERE tbl_faturamento.fabrica = $login_fabrica
                                AND FI.extrato_devolucao IN ($extratos)
                                AND FI.peca = tbl_extrato_lgr.peca
                            ) AS nota_fiscal
                            FROM tbl_extrato_lgr
                            JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca AND tbl_peca.fabrica = $login_fabrica
							JOIN tbl_faturamento_item ON tbl_faturamento_item.peca = tbl_extrato_lgr.peca and tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato
							JOIN tbl_os_item USING(pedido, pedido_item) 
                            WHERE tbl_extrato_lgr.extrato = $extratos
                            AND tbl_extrato_lgr.posto = $xposto
							AND (tbl_os_item.peca_obrigatoria or tbl_peca.devolucao_obrigatoria)
                            AND tbl_extrato_lgr.qtde_nf IS NULL";
                $res3 = pg_query($con,$sql3);
                $rows3 = pg_num_rows($res3);
            ?>
                <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
                    <thead>
                        <tr>
                            <th colspan="4" class='titulo_tabela'>Extrato: <?=$extratos?></th>
                        </tr>
                        <tr class='titulo_coluna' >
                            <th>Ref. Peças</th>
                            <th>Descrição</th>
                            <th>Qtde</th>
                            <th>Nota Fiscal</th>
                        </tr>
                    </thead>
                    <tbody>
            <?php
                for ($x=0; $x < $rows3; $x++) {

                    $peca_referencia = pg_fetch_result($res3, $x, referencia);
                    $peca_descricacao = pg_fetch_result($res3, $x, descricao);
                    $qtde = pg_fetch_result($res3, $x, qtde);
                    $nota_fiscal = pg_fetch_result($res3, $x, nota_fiscal);
            ?>
                        <tr>
                            <td class='tac'><?=$peca_referencia?></td>
                            <td><?=$peca_descricacao?></td>
                            <td class='tac'><?=$qtde?></td>
                            <td class='tac'><?=$nota_fiscal?></td>
                        </tr>
            <?php
                }
                echo '</tbody>
                    </table><br/>';
            }
        }
    }
    include 'rodape.php';
?>
