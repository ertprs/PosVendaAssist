<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    $admin_privilegios="call_center";
    include 'autentica_admin.php';
    
    if ( isset($_GET["data_inicial"]) && isset($_GET["data_final"]) && isset($_GET["posto"]) ) {

        $defeito = trim($_GET["data_inicial"]);
        $solucao = trim($_GET["data_final"]);
        $posto_id = trim($_GET["posto"]);

        /*Validações do formulário*/
        if (!strlen($data_inicial) && !strlen($data_final)) {
            $msg_erro["msg"][]    = "Preencha os campos obrigatórios.";
            $msg_erro["campos"][] = "data";
        }else{
            list($di, $mi, $yi) = explode("/", $data_inicial);
            list($df, $mf, $yf) = explode("/", $data_final);

            if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
                $msg_erro["msg"][]    = "Data Inválida";
                $msg_erro["campos"][] = "data";
            } else {
                $aux_data_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
                $aux_data_final   = "{$yf}-{$mf}-{$df} 23:59:59";


                if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                    $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                    $msg_erro["campos"][] = "data";
                }

                if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -6 month')) { 
                    $msg_erro["msg"][]    = "Período não pode ser maior que 6 meses";
                    $msg_erro["campos"][] = "data";
                }
            }
        } /*Fim das validações do formulário*/


        $sql_info = "SELECT
                            tbl_posto.cnpj as posto_cnpj,
                            tbl_posto.nome as posto_razao,
                            tbl_pedido.pedido,
                            SUM(tbl_pedido_item.qtde) AS pedido_qtde_pecas,
                            SUM(((tbl_pedido_item.qtde * tbl_pedido_item.preco) * (1 + (tbl_faturamento_item.aliq_ipi / 100)))) as pedido_soma_total_ipi
                        FROM tbl_pedido
                            JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                            JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
                            JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
                        WHERE tbl_pedido.data BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                            AND tbl_pedido.fabrica = $login_fabrica
                            AND tbl_tipo_pedido.pedido_faturado IS TRUE
                            AND tbl_pedido.status_pedido <> 14
                            AND tbl_posto_fabrica.posto = {$posto_id} 
                        GROUP BY
                            tbl_posto.cnpj,
                            tbl_posto.nome,
                            tbl_pedido.pedido,
                            tbl_pedido.posto;";
        $res_info = pg_query($con, $sql_info);

        //thiago
        //echo nl2br($sql_info);

        if(pg_num_rows($res_info) > 0){

            $desc_defeito = pg_fetch_result($res_info, 0, "posto_cnpj");
            $desc_solucao = pg_fetch_result($res_info, 0, "posto_razao");
            $pedido_id    = pg_fetch_result($res_info, 0, "pedido");
            $desc_produto = pg_fetch_result($res_info, 0, "pedido_qtde_pecas");
            $procedimento = pg_fetch_result($res_info, 0, "pedido_soma_total_ipi");
            ?>

            <!DOCTYPE html>
            <html>
                <head>
                    <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
                    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
                    <script src="bootstrap/js/bootstrap.js"></script>
                    <script>
                    </script>
                </head>
                <body>

                    <?php $height = (strlen($box) > 0 && $box == 1) ? "530px" : "530px"; ?>

                    <div class="container" style="margin: 0 auto; overflow: auto; height: <?php echo $height; ?>; width: 95%;">
                        <div style="width: 99%; margin-top: 20px;" class="response"></div>
                        <table id="resultado_pedidos_faturado_detalhado" class="table table-striped table-bordered table-fixed">
                            <thead>
                                <tr class="titulo_coluna">                                    
                                    <th class="tac">Código</th>
                                    <th class="tac">Posto</th>
                                    <th class="tac">Pedido</th>
                                    <th class="tac">Qtde Peças</th>
                                    <th class="tac">Valor Total Pedidos R$</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?
                                    $total_pedido_soma_total_ipi = 0;
                                    $total_pedido_qtde_pedidos = 0;
                                    $total_pedido_qtde_pecas = 0;

                                    for ($i = 0; $i < pg_num_rows($res_info); $i++) {
                                        $posto_cnpj                  = pg_fetch_result($res_info, $i, 'posto_cnpj');
                                        $posto_id                    = pg_fetch_result($res_info, $i, 'posto_id');
                                        $posto_razao                 = pg_fetch_result($res_info, $i, 'posto_razao');
                                        $pedido_soma_total_ipi       = pg_fetch_result($res_info, $i, 'pedido_soma_total_ipi');
                                        $pedido         = pg_fetch_result($res_info, $i, 'pedido');
                                        $pedido_qtde_pecas = pg_fetch_result($res_info, $i, 'pedido_qtde_pecas');

                                        $total_pedido_soma_total_ipi += $pedido_soma_total_ipi;
                                        $total_pedido_qtde_pedidos += $pedido_qtde_pedidos;
                                        $total_pedido_qtde_pecas += $pedido_qtde_pecas;
                                        ?>
                                        <tr>
                                            <td class='tac'><?=$posto_cnpj;?></td>
                                            <td class='tal'><?=$posto_razao;?></td>
                                            <td class='tac'>
                                                <a href="pedido_admin_consulta.php?pedido=<?=$pedido;?>" target="_blanck"><?=$pedido;?></a>
                                            </td>
                                            <td class='tac'><?=$pedido_qtde_pecas;?></td>
                                            <td class='tar'>R$ <?=number_format($pedido_soma_total_ipi,2,",",".");?></td>
                                        </tr>
                                    <?php
                                    } ?>
                            </tbody>
                            <tfoot>
                                <tr class="titulo_coluna">
                                    <td colspan="3" class="tar">Total</td>                                    
                                    <td class="tac"><?=$total_pedido_qtde_pecas?></td>
                                    <td class="tar">R$ <?=number_format($total_pedido_soma_total_ipi,2,",",".")?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </body>
            </html>
        <?php
        }
    }
?>
