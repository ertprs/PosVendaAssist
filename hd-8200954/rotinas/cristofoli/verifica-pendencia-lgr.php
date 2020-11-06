<?php
try{
include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

    $fabrica        = 161;
    $fabrica_nome   = "cristofoli";
    
    $sql = "SELECT DISTINCT tbl_extrato.extrato,
                    tbl_extrato.posto,
                    tbl_peca.referencia,
                    tbl_peca.descricao
                    INTO TEMP pendentes
                    FROM tbl_extrato
                    JOIN tbl_os_extra USING(extrato)
                    JOIN tbl_os_produto USING(os)
                    JOIN tbl_produto USING(produto)
                    JOIN tbl_os_item USING(os_produto)
                    JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = 161
                    JOIN tbl_extrato_lgr ON tbl_extrato.extrato = tbl_extrato_lgr.extrato AND tbl_extrato_lgr.peca = tbl_os_item.peca
                    AND tbl_extrato_lgr.qtde > coalesce(tbl_extrato_lgr.qtde_nf,0)
                    LEFT JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao AND tbl_faturamento_item.peca = tbl_extrato_lgr.peca
                    LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.distribuidor = tbl_extrato.posto AND tbl_faturamento.cancelada IS NULL AND tbl_faturamento.fabrica = 161 AND tbl_faturamento.natureza ~ 'Devolu'
                    WHERE tbl_extrato.fabrica = 161
                    AND tbl_extrato.data_geracao::date < CURRENT_DATE - INTERVAL '60 days'
                    AND tbl_produto.fabrica_i = 161
                    AND tbl_produto.linha = 972
                    AND tbl_os_item.peca_obrigatoria IS TRUE
                    AND tbl_faturamento.faturamento IS NULL;";

    $res = pg_query($con, $sql);
    
    if (count(pg_num_rows($res)) > 0){
        $sql_pendentes = "SELECT DISTINCT posto FROM pendentes;";
        $res_pendentes = pg_query($con, $sql_pendentes);
        
        $cabecalho = '<table name=relatorio id=relatorio class=relatorio><thead><tr><td colspan="2">Peças a serem devolvidas</td></tr><tr bgcolor=#3e83c9><th style=text-align:center;>Extrato</th><th style=text-align:center;>Referência</th><th style=text-align:center>Descrição</th></tr></thead><tbody>';

        if(pg_num_rows($res_pendentes) > 0){
            $corpo = "";
            for($i = 0; $i < pg_num_rows($res_pendentes); $i++){
                $posto = pg_fetch_result($res_pendentes, $i, "posto");

                $sql_peca = "SELECT extrato, referencia,descricao FROM pendentes WHERE posto = $posto";
                $res_peca = pg_query($con, $sql_peca);

                if(pg_num_rows($res_peca) > 0){
                    for($p = 0; $p < pg_num_rows($res_peca); $p++){
                        $referencia 	= pg_fetch_result($res_peca, $p, 'referencia');
                        $descricao 		= pg_fetch_result($res_peca, $p, 'descricao');
                        $extrato 		= pg_fetch_result($res_peca, $p, 'extrato');
                    
                        $corpo .= "<tr><td>". $extrato ."</td><td>". $referencia . "</td><td>". $descricao . "</td></tr>";
                    }
                }

                $rodape = "</tbody></table>";
                $tabela = $cabecalho . $corpo . $rodape;
                $insert_comunicado = "INSERT INTO tbl_comunicado(fabrica,posto,obrigatorio_site,tipo,mensagem) VALUES(161,$posto,true,'pendencia_lgr', '$tabela')";
                pg_query($con, $insert_comunicado);
            }
        }

    } 
}catch (Exception $e) {
    echo $e->getMessage();
}
    
?>