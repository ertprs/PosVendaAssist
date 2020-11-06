<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');
define('OS_BACK', ($areaAdminCliente == true)?'':'../');

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


if($login_fabrica == 35){
    
    $produtos           = $_POST['produtos'];
    $data_final         = $_POST['data_final'];
    $data_inicial       = $_POST['data_inicial'];
    $tipo_os            = $_POST['tipo_os'];
    $tipo_data          = $_POST['tipo_data'];

    switch ($tipo_os) {
        case 'trocada':
            $tempExec = "tmp_bi_os_troca_peca";
            $texto_tipo_os = 'OS com PEÇA trocada';
            break;
        case 'sem_peca':
            $tempExec = "tmp_bi_os_sem_peca";
            $texto_tipo_os = 'OS sem PECA';
            break;
        case 'ajustada':
            $tempExec = "tmp_bi_os_ajuste";
            $texto_tipo_os = 'OS com PECA ajustada';
            break;      
        case 'produto_trocado':
            $tempExec = "tmp_bi_os_troca_produto";
            $texto_tipo_os = 'OS com PRODUTO trocado';
            break;
    }

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

<?
echo "<br /> <div class='container'>";
echo "<div class='alert alert-success'>";

    echo "<h4>Resumo de $texto_tipo_os</h4>";
    if(strlen($data_inicial)>0)echo " <h6> Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b></h6>";
    echo "</div>";

        $sql = "SELECT DISTINCT BI.os, 
                tbl_servico_realizado.troca_de_peca,
                troca_produto,
                PF.codigo_posto AS posto_codigo,
                PO.nome AS posto_nome, 
                to_char(BI.data_nf,'DD/MM/YYYY') as data_compra, 
                DR.codigo AS dr_codigo ,
                DR.descricao AS dr_descricao,
                DC.codigo AS dc_codigo ,
                DC.descricao AS dc_descricao,
                DC.defeito_constatado AS dc_id,
                BI.serie
                INTO temp tmp_bi_os
                FROM bi_os BI
                JOIN tbl_posto PO ON PO.posto = BI.posto
                JOIN tbl_posto_fabrica PF ON PF.posto = BI.posto and PF.fabrica = $login_fabrica
                LEFT JOIN bi_os_item ON BI.os = bi_os_item.os
                LEFT JOIN tbl_servico_realizado ON bi_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                LEFT JOIN tbl_defeito_reclamado DR ON DR.defeito_reclamado = bi_os_item.defeito_reclamado AND DR.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = bi_os_item.defeito_constatado and DC.fabrica = $login_fabrica
                WHERE BI.fabrica = $login_fabrica
                AND BI.produto in ($produtos) 
                AND BI.pais = 'BR' 
                AND BI.$tipo_data BETWEEN $aux_data_inicial AND $aux_data_final ";
        $res = pg_query($con,$sql);

        $sql_os_sem_peca = "SELECT * INTO temp tmp_bi_os_sem_peca FROM tmp_bi_os WHERE troca_de_peca is NULL AND troca_produto is NULL";
        $res_os_sem_peca = pg_query($con, $sql_os_sem_peca);

        $sql_os_troca_produto = "SELECT * INTO TEMP tmp_bi_os_troca_produto FROM tmp_bi_os WHERE troca_produto is TRUE ";
        $res_os_troca_produto = pg_query($con, $sql_os_troca_produto);

        $sql_os_troca_peca = "SELECT *
                    INTO temp tmp_bi_os_troca_peca
                    FROM tmp_bi_os
                    WHERE troca_de_peca is TRUE
                    AND troca_produto is not TRUE
                    AND os not IN (SELECT os FROM tmp_bi_os_troca_produto) ";
        $res_os_troca_peca = pg_query($con, $sql_os_troca_peca);

        $sql_ajustadas = "SELECT *
                    INTO temp tmp_bi_os_ajuste
                    FROM tmp_bi_os
                    WHERE troca_de_peca is false
                    AND troca_produto is false
                    AND os not IN (SELECT os FROM tmp_bi_os_troca_produto)
                    AND os not IN (SELECT os FROM tmp_bi_os_troca_peca)";
        $res_ajustada = pg_query($con, $sql_ajustadas);

        $sql = "SELECT DISTINCT * FROM $tempExec order by os ";
        $res = pg_query($con, $sql);

        if (pg_numrows($res) > 0) {
            $total = 0;
             
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
                if($login_fabrica == 35){
                    $po                 = pg_fetch_result($res, $i, serie);
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
                echo "<TD align='left' nowrap><a href='".OS_BACK."os_press.php?os=$os' target='_blanck'>$os</a></td>";
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
            
        }


    //}


echo "</div>";