<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Detalhe de Pedido de Peças";

$tipo         = trim($_GET["tipo"]);
$pesquisa     = trim($_GET["pesquisa"]);
$fabrica      = trim($_GET["fabrica"]);

$peca         = trim($_GET["peca"]);
$data_inicial = trim($_GET["data_inicial"]);
$data_final   = trim($_GET["data_final"]);
$pedido       = trim($_GET["pedido"]);

?>

<html>
    <head>

        <title><?php echo $title ?></title>
        <link type="text/css" rel="stylesheet" href="css/css.css">
        <script language='javascript' src='../ajax.js'></script>
        <?include "javascript_calendario_new.php"; ?>
        <script type='text/javascript' src='js/jquery.autocomplete.js'></script>
        <link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
        <script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
        <script type='text/javascript' src='js/dimensions.js'></script>
        <!-- <script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script> -->
        <script type="text/javascript" src="js/thickbox.js"></script>
        <link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

    </head>

    <body>

        <?php include 'menu.php';?>

        <center>
            <h1><?php echo $title; ?></h1>

            <?php

            if(strlen($peca) > 0){

                $sql = "SELECT referencia, descricao FROM tbl_peca WHERE peca = {$peca} AND fabrica = {$fabrica}";
                $res = pg_query($con, $sql);

                $referencia = pg_fetch_result($res, 0, "referencia");
                $descricao = pg_fetch_result($res, 0, "descricao");

                echo "Peça: <strong>{$referencia} - {$descricao}</strong>";

            }

            echo "<br /> <br />";

            if($tipo == "os"){

                $sql = "SELECT 
                            DISTINCT tbl_os.os, tbl_os.data_abertura 
                        FROM tbl_os_item 
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
                        INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os 
                        WHERE 
                            tbl_os_item.pedido IN ({$pedido}) 
                            AND tbl_os.fabrica = {$fabrica} 
                            AND tbl_os_item.peca = {$peca}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){

                    echo "<strong>Total de OSs:</strong> ".pg_num_rows($res);

                    echo "<table align='center' border='0' cellspacing='1' cellpadding='5' style='width: 500px;'>";

                        echo "<thead>";
                            echo "<tr bgcolor='#0099CC' style='color: #fff; font-weight: bold; font-size: 16px;'>";
                                echo "<th>OS</th>";
                                echo "<th>Data de Abertura</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tbody>";
                        for($i = 0; $i < pg_num_rows($res); $i++){

                            $os = pg_fetch_result($res, $i, "os");
                            $data_abertura = pg_fetch_result($res, $i, "data_abertura");

                            list($ano, $mes, $dia) = explode("-", $data_abertura);

                            $data_abertura = $dia."/".$mes."/".$ano;

                            $cor = ($i % 2 == 0) ? "#ccc" : "#eee";

                            echo "<tr bgcolor='{$cor}'>";
                                echo "<td align='center' style='width: 50%;'><a href='../os_press.php?os={$os}&verifica_distrib_geral=true' target='_blank'>{$os}</a></td>";
                                echo "<td align='center' style='width: 50%;'>{$data_abertura}</td>";
                            echo "</tr>";

                        }
                        echo "</tbody>";

                    echo "</table>";

                }else{
                    echo "<p>Nenhuma OS encontrada!</p>";
                }

            }else if($tipo == "pedido"){

                if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
                    $cond_data = "BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'";
                }else if(strlen($data_inicial) > 0){
                    $cond_data = ">= '{$data_inicial}'";
                }

                $sql = "SELECT 
                            DISTINCT tbl_pedido_item.pedido   
                        FROM tbl_pedido_item 
                        INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
                        INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_em_garantia IS TRUE 
                        WHERE 
                            tbl_pedido_item.peca = {$peca} 
                            AND tbl_pedido.fabrica = {$fabrica} 
                            AND tbl_pedido_item.pedido IN ({$pedido})";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){

                    echo "<strong>Total de Pedidos:</strong> ".pg_num_rows($res);

                    echo "<table align='center' border='0' cellspacing='1' cellpadding='5' style='width: 500px;'>";

                        echo "<thead>";
                            echo "<tr bgcolor='#0099CC' style='color: #fff; font-weight: bold; font-size: 16px;'>";
                                echo "<th>Pedido(s)</th>";
                            echo "</tr>";
                        echo "</thead>";

                        echo "<tbody>";
                        for($i = 0; $i < pg_num_rows($res); $i++){

                            $pedido = pg_fetch_result($res, $i, "pedido");

                            $cor = ($i % 2 == 0) ? "#ccc" : "#eee";

                            echo "<tr bgcolor='{$cor}'>";
                                echo "<td align='center' style='width: 50%;'><a href='pedido_finalizado.php?pedido=$pedido&fabrica={$fabrica}' target='_blank'>{$pedido}</a></td>";
                            echo "</tr>";

                        }
                        echo "</tbody>";

                    echo "</table>";

                }else{
                    echo "<p>nenhum Pedido encontrado!</p>";
                }

            }

            ?>

        </center>

        <? include "rodape.php"; ?>

    </body>
</html>