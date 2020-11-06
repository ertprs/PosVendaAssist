<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";


$tecnico        = $_GET["tecnico"];
$data_ini       = $_GET["data_ini"];
$data_fim       = $_GET["data_fim"];
$tecnico_nome   = $_GET["nome"];
$tipo           = $_GET["tipo"];

?>

<!DOCTYPE html />
<html>
    <head>
        <meta http-equiv=pragma content=no-cache />
        <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
        <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
        <script src="bootstrap/js/bootstrap.js" ></script>
        <script src="plugins/shadowbox_lupa/lupa.js"></script>
        <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
        <script src="plugins/jquery.mask.js"></script>

        <script>

        </script>
    </head>
    <body>
        <div class='titulo_tabela '>Ordens de Serviço do técnico <?=$tecnico_nome?></div>

        <table class="table table-striped table-bordered" >
        <?php
            if($tipo == "os"){
        ?>
            <thead>
                <tr class="titulo_coluna" >
                    <th>OS</th>
                    <th>Produto</th>
                    <th>Data Abertura</th>
                    <th>Data Conserto</th>
                    <th>Data Fechamento</th>
                </tr>
            </thead>
        <?php
            }else{
        ?>
                <th>OS</th>
                <th>Peça</th>
        <?php
            }
        ?>
            <tbody>
                <?php

                if($tipo == "os"){
                    $sqlOs = "SELECT    os, 
                                    referencia,
                                    descricao,
                                    to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                                    to_char(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto,
                                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
                            FROM tbl_os
                            JOIN tbl_os_produto USING(os)
                            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                            WHERE tbl_os.fabrica = {$login_fabrica}
                            AND tbl_os.tecnico = {$tecnico}
                            AND tbl_os.finalizada IS NOT NULL
                            AND tbl_os.data_conserto BETWEEN '$data_ini 00:00:00' and '$data_fim 23:59:59'
                            AND tbl_os.excluida IS NOT TRUE
                            ORDER BY tbl_os.data_abertura DESC";
                }else{
                    $sqlOs = "SELECT tbl_os.os, 
                                    tbl_peca.referencia,
                                    tbl_peca.descricao
                                FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                                WHERE tbl_os.fabrica = {$login_fabrica}
                                AND tbl_os.tecnico = {$tecnico}
                                AND tbl_os.finalizada IS NOT NULL
                                AND tbl_os.data_conserto BETWEEN '$data_ini 00:00:00' and '$data_fim 23:59:59'
                                AND tbl_os.excluida IS NOT TRUE
                                ORDER BY tbl_os.data_abertura DESC";
                }

                $res = pg_query($con, $sqlOs);

                if (pg_num_rows($res) > 0) {

                    while ($os = pg_fetch_object($res)) {

                        if($tipo == "os"){
                    ?>

                            <tr>                                
                                <td class="tac"><a href="os_press.php?os=<?=$os->os?>" target="_blank"><?=$os->os?></a></td>
                                <td><?=$os->referencia?> - <?=$os->descricao?></td>
                                <td class="tac"><?=$os->data_abertura?></td>
                                <td class="tac"><?=$os->data_conserto?></td>
                                <td class="tac"><?=$os->data_fechamento?></td>
                            </tr>

                    <?php
                        }else{
                    ?>
                            <tr>
                                <td class="tac"><a href="os_press.php?os=<?=$os->os?>" target="_blank"><?=$os->os?></a></td>
                                <td><?=$os->referencia?> - <?=$os->descricao?></td>
                            </tr>
                    <?php
                        }
                    }
                }
                ?>
            </tbody>
        </table>
    </body>
</html>