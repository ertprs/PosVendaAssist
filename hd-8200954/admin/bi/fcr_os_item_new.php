<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';
include '../funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";

$tema = "Defeito Constatado";

if(isset($produtos))$listar="ok";
$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

$qtde_meses_leadrship = 15;
//include "cabecalho.php";


if($login_fabrica == 35){
    $dados = $_POST['dados'];
    $produtos = $_POST['produtos'];
    $dadosPOST = json_decode($dados, true);

    extract($dadosPOST);
    unset($gerar_excel);

    $aux_data_final = fnc_formata_data_pg($data_final);
    $aux_data_inicial = fnc_formata_data_pg($data_inicial);
}

?>

    <link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../css/tooltips.css" type="text/css" rel="stylesheet" />
    <link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
    <link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

    <!--[if lt IE 10]>
     <link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
    <![endif]-->

    <script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

<script type="text/javascript">
    $(function() {
        $(".produtos").click(function(){
            var texto = $(this).text();
            var tipo_os; 

            switch(texto){
                case 'OS com PECA ajustada':
                    tipo_os = 'ajustada';
                break;
                case 'OS com PRODUTO trocado':
                    tipo_os = 'produto_trocado';
                break;
                case 'OS com PEÇA trocada':
                    tipo_os = 'trocada';
                break;
                case 'OS sem PECA':
                    tipo_os = 'sem_peca';
                break;
            }
            $("#tipo_os").val(tipo_os);
            $( "#form_produtos" ).submit();
            
        });
    });


</script>


<?

echo "<br /> <div class='container'>";

    if ($_GET['origem_tipo']=='defeito') {

        $dc_descricao = $_GET['dr_descricao'];

        $sql = "SELECT * FROM temp_bi_os_sem_peca2_$login_fabrica where dc_descricao = '$dc_descricao' order by posto_nome";
        $res = pg_exec($con,$sql);

         echo "<br><a href='javascript:history.back()'>[Voltar]</a>";
         echo "<TABLE name='relatorio' id='relatorio' align='center' class='table table-striped table-bordered table-hover'>";
         echo "<thead>";
         echo "<tr class='titulo_coluna'>";
         echo "<th><b>OS</b></th>";
         echo "<th><b>Cód. Posto</b></th>";
         echo "<th><b>Posto</b></th>";
         echo "<th><b>Defeito Reclamado</b></th>";
         echo "<th height='15'><b>$tema</b></th>";
         echo "</TR>";
         echo "</thead>";
         echo "<tbody>";

          for ($i=0; $i<pg_numrows($res); $i++){
                                $posto_codigo   = trim(pg_result($res,$i,posto_codigo));
                                $posto_nome     = trim(pg_result($res,$i,posto_nome));
                                $dr_codigo      = trim(pg_result($res,$i,dr_codigo));
                                $dr_descricao   = trim(pg_result($res,$i,dr_descricao));
                                $dc_codigo      = trim(pg_result($res,$i,dc_codigo));
                                $dc_descricao   = trim(pg_result($res,$i,dc_descricao));
                                $os             = trim(pg_result($res,$i,os));
                                $sua_os         = trim(pg_result($res,$i,sua_os));


                echo "<TR>";
                echo "<TD align='left' nowrap><a href='../os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
                echo "<TD align='left' nowrap>$posto_codigo</TD>";
                echo "<TD align='left' >$posto_nome</TD>";
                echo "<TD align='left'>$dr_codigo - $dr_descricao</TD>";
                echo "<TD align='left'>$dc_codigo - $dc_descricao</TD>";
                echo "</TR>";
        }
            $total_pecas = number_format($total_pecas,2,",",".");
            echo "</tbody>";
            echo " </TABLE>";
            echo "<a href='javascript:history.back()'>[Voltar]</a>";

    }


if ($listar == "ok") {

   
    $cond_join = "JOIN bi_os ON bi_os.os = BI.os ";

    $sql2 = "SELECT referencia,descricao
            FROM tbl_produto
            JOIN tbl_linha  USING(linha)
            WHERE produto  in ($produtos)
            AND   fabrica = $login_fabrica";
    $res2 = pg_exec ($con,$sql2);

    $produto_referencia = pg_result($res2,0,0);
    $produto_descricao  = pg_result($res2,0,1);

    if(strlen($peca)>0){
        $sql2 = "SELECT referencia,descricao
                FROM tbl_peca
                WHERE peca    = $peca
                AND   fabrica = $login_fabrica";
        $res2 = pg_exec ($con,$sql2);
        $peca_referencia = pg_result($res2,0,0);
        $peca_descricao  = pg_result($res2,0,1);
    }

    echo "<div class='alert alert-success'>";

    //if (strlen($lista_produtos) > 0) {
       /* $sql2 = "SELECT referencia,descricao
                FROM tbl_produto
                JOIN tbl_linha  USING(linha)
                WHERE produto in ($produtos)
                AND   fabrica = $login_fabrica 
                ORDER BY tbl_produto.referencia 

                limit 10 ";
        $res2 = pg_exec ($con,$sql2);
        echo "<h4>Produtos:";
        for($i=0;$i<pg_numrows($res2);$i++){
            $produto_referencia = pg_result($res2,$i,0);
            $produto_descricao  = pg_result($res2,$i,1);
            echo " $produto_referencia / ";
        }
        echo "</h4>";*/
    /*}else{
        echo "<h4>Produto: $produto_referencia - $produto_descricao</h4>";
    }*/

        echo "<h4>Resumo de Todos Produtos</h4>";


    if(strlen($peca)>0) echo "<h5>Peça: $peca_referencia - $peca_descricao</h5>";
    else                echo "<br />";
    if(strlen($data_inicial)>0)echo " <h6> Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b></h6>";
    echo "$mostraMsgLinha $mostraMsgEstado $mostraMsgPais";
    echo "</div>";

    if(strlen($codigo_posto)>0){
        $sql = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   fabrica      = $login_fabrica
                AND     codigo_posto = '$codigo_posto';";
        $res = pg_exec ($con,$sql);
        if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
    }
    
    if (strlen ($linha)    > 0) $cond_1 = " AND   BI.linha   = $linha ";    

    if (strlen ($familia)    > 0) $cond_1 .= " AND   BI.familia   = $familia ";
    if (strlen ($estado)   > 0) $cond_2 = " AND   BI.estado  = '$estado' ";
    if (strlen ($posto)    > 0) $cond_3 = " AND   BI.posto   = $posto ";
    if (strlen ($posto) > 0 AND !empty($exceto_posto)) {
        $cond_3 = " AND   NOT (BI.posto   = $posto) ";
    }
    if (strlen ($produtos)  > 0) $cond_4 = " AND   BI.produto in ($produtos)"; // HD 2003 TAKASHI
    if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
    if (strlen ($marca)    > 0) $cond_7 = " AND   BI.marca   = $marca ";
    if (strlen ($origem)   > 0) $cond_8 = " AND   BI.origem  = '$origem' ";
    if (strlen ($lista_produtos)> 0) {
        $cond_10 = " AND   BI.produto in ($lista_produtos) ";
        $cond_4  = "";
    }

    if (strlen($tipo_data) == 0 ) $tipo_data = 'data_fechamento';
    if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0 AND $tipo_data!="data_fabricacao"){
        $cond_9 = "AND   BI.$tipo_data BETWEEN $aux_data_inicial AND $aux_data_final ";
    }
    
    $produto_descricao   ="tbl_produto.descricao ";
    $join_produto_idioma =" ";
    
    if (count($tipo_posto) > 0) {
        $joinTipoPosto = " LEFT JOIN tbl_tipo_posto USING(tipo_posto) ";
        $condTipoPosto = " AND tbl_tipo_posto.tipo_posto IN(".str_replace("|", ",", $_GET['tipo_posto']).")";
    }

    if(strlen($peca)>0){
        if ($_GET['tipo_os']=='ajustada') {
            $cond12 = "AND tbl_servico_realizado.troca_de_peca is false AND tbl_servico_realizado.troca_produto is false";

        } else if ($_GET['tipo_os']=='trocada') {
            $cond12 = "AND tbl_servico_realizado.troca_de_peca is true";
        } else if ($login_fabrica <> 131){
            $cond12 = "AND tbl_servico_realizado.troca_produto is true";
        }
        //hd-3675052
        if($login_fabrica == 35){
            $joinOS = " JOIN tbl_os ON tbl_os.os = BI.os and tbl_os.fabrica = $login_fabrica ";
            $camposOS = " tbl_os.serie as numero_serie, to_char(tbl_os.data_nf,'DD/MM/YYYY') as data_compra, ";
        }

        $sql = "SELECT DISTINCT  PE.peca                               ,
                        PE.ativo                              ,
                        PE.referencia                         ,
                        PE.descricao                          ,
                        PF.codigo_posto        AS posto_codigo,
                        PO.nome                AS posto_nome  ,
                        BI.os                                 ,
                        BI.custo_peca                         ,
                        BI.sua_os                             ,
                        $camposOS
                        DR.codigo              AS dr_codigo   ,
                        DR.descricao           AS dr_descricao,
                        DC.codigo              AS dc_codigo   ,
                        DC.descricao           AS dc_descricao,
                        DC.defeito_constatado  AS dc_id,
                        bi_os.serie
            FROM      bi_os_item             BI
            $join_macro_linha_item
            JOIN      tbl_peca               PE ON PE.peca               = BI.peca
            JOIN      tbl_posto              PO ON PO.posto              = BI.posto
            JOIN      tbl_posto_fabrica      PF ON PF.posto              = BI.posto
            $joinTipoPosto
            $joinOS
            LEFT JOIN tbl_defeito_reclamado  DR ON DR.defeito_reclamado  = BI.defeito_reclamado AND DR.fabrica = $login_fabrica
            LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
            JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado  = BI.servico_realizado $cond12
            $cond_join
            $condFacricadoJoin
            $join_lenoxx
            $join_tipo_atendimento
            $join_cancelada
            WHERE BI.fabrica = $login_fabrica
            AND   PF.fabrica = $login_fabrica
            AND   BI.peca    = $peca
            $condTipoPosto
            $cond_cancelada
            $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os";
        $res = pg_exec ($con,$sql);
        
        if (pg_numrows($res) > 0) {
            $total = 0;
             echo "<br><a href='javascript:history.back()'>[Voltar]</a>";
            echo "<TABLE name='relatorio' id='relatorio' align='center' class='table table-striped table-bordered table-hover'>";
            echo "<thead>";
            echo "<tr class='titulo_coluna'>";
            echo "<th><b>OS</b></th>";
            echo ($login_fabrica ==85) ? "<TD height='15'><b>Nº Série</b></TD>" : "";
            echo "<th><b>Cód. Posto</b></th>";
            echo "<th><b>Posto</b></th>";

            if($login_fabrica == 42){
                echo "<TD height='15'><b>Nº Série</b></TD>";
            }

            if ($login_fabrica == 50){ #HD 86811 para Colormaq
                echo "<TD height='15'><b>Nº Série</b></TD>";
                echo "<TD height='15'><b>Data Fabricação</b></TD>";
            }
            echo "<th><b>Defeito Reclamado</b></th>";
            
            echo "<th height='15'><b>$tema</b></th>";
            if($login_fabrica == 35){
                echo "<th><b>PO#</b></th>";
                echo "<th><b>Data Compra</b></th>";
            }
            echo "</TR>";
            echo "</thead>";
            echo "<tbody>";

            for ($i=0; $i<pg_numrows($res); $i++){
                $posto_codigo   = trim(pg_result($res,$i,posto_codigo));
                $posto_nome     = trim(pg_result($res,$i,posto_nome));
                $dr_codigo      = trim(pg_result($res,$i,dr_codigo));
                $dr_descricao   = trim(pg_result($res,$i,dr_descricao));
                $dc_id          = trim(pg_result($res,$i,dc_id));
                $dc_codigo      = trim(pg_result($res,$i,dc_codigo));
                $dc_descricao   = trim(pg_result($res,$i,dc_descricao));
                $os             = trim(pg_result($res,$i,os));
                $sua_os         = trim(pg_result($res,$i,sua_os));
                $serie          = trim(pg_result($res,$i,serie));

                if($login_fabrica == 35){
                    $po                 = pg_fetch_result($res, $i, numero_serie);
                    $data_compra        = pg_fetch_result($res, $i, data_compra);
                }

                $total_pecas += $custo_peca;
                $custo_peca   = number_format($custo_peca,2,",",".");
                if($login_fabrica == 50 or $login_fabrica == 5){ // HD 37460

                    if(strlen($dr_codigo) == 0){
                        $sqlx="SELECT defeito_reclamado_descricao, serie from tbl_os where os=$os and fabrica= $login_fabrica";
                    } else { # HD 86811 para Colormaq
                        $sqlx="SELECT serie FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
                    }
                    $resx = pg_exec($con,$sqlx);
                    if(strlen($dr_codigo) == 0){
                        $dr_descricao = pg_result($resx,0,defeito_reclamado_descricao);
                    }
                    $serie        = pg_result($resx,0,serie);
                    $data_fabricacao = "";
                    if(strlen($serie) > 0) {
                        $sqld = "SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
                                FROM tbl_numero_serie
                                WHERE serie = '$serie'";
                        $resd = pg_exec($con,$sqld);
                        if(pg_numrows($resd) > 0) {
                            $data_fabricacao=pg_result($resd,0,data_fabricacao);
                        }
                    }
                }


                echo "<TR>";
                echo "<TD align='left' nowrap><a href='../os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
                echo ($login_fabrica ==85) ? "<TD height='15'>$serie</TD>" : "";
                echo "<TD align='left' nowrap>$posto_codigo</TD>";
                echo "<TD align='left' >$posto_nome</TD>";
                if ($login_fabrica == 50){ #HD 86811 para Colormaq
                    echo "<TD align='left' nowrap>$serie</TD>";
                    echo "<TD align='left' nowrap>$data_fabricacao</TD>";
                }

                if($login_fabrica == 42){
                    echo "<TD align='left' nowrap>$serie</TD>";
                }

                if($login_fabrica == 15 or $login_fabrica == 5 || strlen($dr_descricao) == 0){
                    $sql_dr       = "SELECT defeito_reclamado_descricao FROM tbl_os WHERE os = $os";
                    $res_dr       = pg_exec($con,$sql_dr);
                    $decricao_descricao = pg_result($res_dr,0,'defeito_reclamado_descricao');

                    if(trim($decricao_descricao)){
                        $dr_descricao = $decricao_descricao;
                    }
                }
                if($dc_id == '0' and $BiMultiDefeitoOs =='t') {
                    $sql_dc = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado JOIN tbl_defeito_constatado using(defeito_constatado)
                        WHERE os = $os order by defeito_constatado_reclamado limit 1 ";
                    $res_dc = pg_query($con,$sql_dc);
                    if(pg_num_rows($res_dc) > 0) {
                        $dc_codigo = pg_fetch_result($res_dc, 0, 'codigo');
                        $dc_descricao = pg_fetch_result($res_dc, 0, 'descricao');
                    }
                }

                echo "<TD align='left'>$dr_codigo - $dr_descricao</TD>";
                
                echo "<TD align='left'>$dc_codigo - $dc_descricao</TD>";
                if($login_fabrica == 35){
                    echo "<TD align='left'>$po</TD>";
                    echo "<TD align='left'>$data_compra</TD>";
                }
                echo "</TR>";
            }
            $total_pecas = number_format($total_pecas,2,",",".");
            echo "</tbody>";
            echo " </TABLE>";
            echo "<a href='javascript:history.back()'>[Voltar]</a>";
        }

    }else{
        if (count($tipo_posto) > 0) {
            $joinTipoPosto = " JOIN tbl_posto_fabrica PF ON PF.posto = BI.posto LEFT JOIN tbl_tipo_posto USING(tipo_posto) ";
            $condTipoPosto = " AND tbl_tipo_posto.tipo_posto IN(".implode(",", $tipo_posto).")";
        }

        $sql = "SELECT DISTINCT BI.os, tbl_servico_realizado.troca_de_peca,troca_produto
                    INTO temp tmp_bi_os
                        FROM bi_os BI
                        $facricadoJoin
                        $join_tipo_atendimento
                        $join_cancelada
                        $joinTipoPosto
                        LEFT JOIN bi_os_item ON BI.os = bi_os_item.os
                        LEFT JOIN tbl_servico_realizado ON bi_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                    WHERE BI.fabrica = $login_fabrica
                        $condTipoPosto
                        $cond_lenoxx
                        $cond_cancelada
                        $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11_bi_os
                        $condicao_gelopar ;

                SELECT DISTINCT(os) INTO temp tmp_bi_os_sem_peca FROM tmp_bi_os WHERE troca_de_peca is NULL AND troca_produto is NULL;
                SELECT count( DISTINCT os) FROM tmp_bi_os_sem_peca ; ";

        $res = pg_exec ($con,$sql);

        $os_sem_peca = pg_result($res,0,0);

        $sql = "SELECT DISTINCT(os) INTO TEMP tmp_bi_os_troca_produto FROM tmp_bi_os WHERE troca_produto is TRUE;

                SELECT count( distinct bi_os_item.os) AS com_peca, SUM(bi_os_item.custo_peca) as total_preco
                    FROM tmp_bi_os_troca_produto TBI
                        JOIN bi_os_item ON TBI.os = bi_os_item.os
                        JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.troca_produto is true
                    WHERE bi_os_item.fabrica = $login_fabrica
                    AND bi_os_item.excluida IS NOT TRUE;";
        $res = pg_exec ($con,$sql);

        $os_com_produto = pg_result($res,0,0);

        $total_preco_os_com_produto = number_format(pg_result($res,0,1),2,",",".");
        $total_preco = pg_result($res,0,1);

        $sql = "SELECT DISTINCT(os)
                    INTO temp tmp_bi_os_troca_peca
                    FROM tmp_bi_os
                    WHERE troca_de_peca is TRUE
                    AND troca_produto is not TRUE
                    AND os not IN (SELECT os FROM tmp_bi_os_troca_produto);

                SELECT count( distinct bi_os_item.os) AS com_peca,SUM(bi_os_item.custo_peca) as total_preco
                    FROM tmp_bi_os_troca_peca TBI
                    JOIN bi_os_item ON TBI.os = bi_os_item.os
                    JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = bi_os_item.servico_realizado AND tbl_servico_realizado.troca_de_peca is true
                    JOIN tbl_peca ON bi_os_item.peca = tbl_peca.peca 
                    WHERE bi_os_item.fabrica = $login_fabrica;";

        $res = pg_exec ($con,$sql);

        $os_com_peca = pg_result($res,0,0);
        $total_preco_os_com_peca = number_format(pg_result($res,0,1),2,",",".");
        $total_preco += pg_result($res,0,1);

        $sql = "SELECT distinct (os)
                    INTO temp tmp_bi_os_ajuste
                    FROM tmp_bi_os
                    WHERE troca_de_peca is false
                    AND troca_produto is false
                    AND os not IN (SELECT os FROM tmp_bi_os_troca_produto)
                    AND os not IN (SELECT os FROM tmp_bi_os_troca_peca);

                SELECT count(*) FROM tmp_bi_os_ajuste ;";
        $res = pg_exec ($con,$sql);

        $os_com_ajuste = pg_result($res,0,0);

        $total_quebra = $os_sem_peca+$os_com_peca+$os_com_ajuste+$os_com_produto;

        $porcentagem_os_sem_peca   =  number_format((($os_sem_peca * 100) / $total_quebra),2,",",".");
        $porcentagem_os_com_peca   =  number_format((($os_com_peca * 100) / $total_quebra),2,",",".");
        $porcentagem_os_com_ajuste  = number_format((($os_com_ajuste * 100) / $total_quebra),2,",",".");
        $porcentagem_os_com_produto = number_format((($os_com_produto * 100) / $total_quebra),2,",",".");

        $total_preco = number_format($total_preco,2,",",".");

        ?>
        <br>
        <table id="resumo" align='center' class='table table-striped table-bordered table-hover table-normal' >
            <thead>
                <tr class='titulo_tabela' >
                    <th colspan="9" >Resumo</th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Status</th>
                    <th>Qtde</th>
                    <th>%</th>
                    <th>Custo com peça</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class='tal' style='color:#0088cc; cursor:pointer;'><span class="produtos">OS com PEÇA trocada</span></td>
                    <td class='tac'><?=$os_com_peca;?></td>
                    <td class='tac'><?=$porcentagem_os_com_peca?></td>
                    <td class='tac'>R$ <?=$total_preco_os_com_peca?></td>
                </tr>
                <tr>
                <tr>

            <td class='tal' style='color:#0088cc; cursor:pointer;'><span class="produtos">OS com PRODUTO trocado</span></td>
                    <td class='tac'><?=$os_com_produto;?></td>
                    <td class='tac'><?=$porcentagem_os_com_produto?></td>
                    <td class='tac'>R$ <?=$total_preco_os_com_produto?></td>
                </tr>
                    <td class='tal' style='color:#0088cc; cursor:pointer;'> <span class="produtos">OS com PECA ajustada</span></td>
                    <td class='tac'><?=$os_com_ajuste;?></td>
                    <td class='tac'><?=$porcentagem_os_com_ajuste?></td>
                    <td class='tac'>R$ 0,00</td>
                </tr>
                <tr>
                    <td class='tal' style='color:#0088cc; cursor:pointer;' ><span class="produtos">OS sem PECA</span></td>
                    <td class='tac'><?=$os_sem_peca;?></td>
                    <td class='tac'><?=$porcentagem_os_sem_peca?></td>
                    <td class='tac'>R$ 0,00</td>
                </tr>
                <tr>
                    <td class='tal'><strong>Total</strong></td>
                    <td class='tac'><?=$total_quebra?></td>
                    <td class='tac'>100%</td>
                    <td class='tac'>R$ <?=$total_preco?></td>
                </tr>

                <?php 
                echo "<form id='form_produtos' method='POST' target='_blank' action='os_bi_new.php'>
                        <input type='hidden' name='produtos' value='$produtos'>
                        <input type='hidden' name='data_inicial' value='$data_inicial'>
                        <input type='hidden' name='data_final' value='$data_final'>
                        <input type='hidden' name='tipo_data' id='tipo_data' value='$tipo_data'>
                        <input type='hidden' name='tipo_os' id='tipo_os' value=''>
                    </form></td>";
                ?>
            </tbody>
        </table>
    <?php
    }
}

echo "</div>";



?>


