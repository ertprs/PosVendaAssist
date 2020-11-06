<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

?>
<html>
    <head>
        <title>LOG CONSULTA LITE</title>
        <style>
            body {
                font: normal 12px arial;
            }
            acronym {
                cursor: help;
            }
        </style>
    </head>
    <body><?php
    $sql = "select *, termino-data from log_os_consulta_lite where termino-data > '00:00:30' order by log_os_consulta_lite desc limit 20";

        $res = pg_query($con, $sql);
        $tot = pg_num_rows($res);

        if ($tot) {
            for ($i = 0; $i < $tot; $i++) {
                $vet[$i] = pg_fetch_assoc($res);
            }
            $col = array_keys($vet[0]);?>
            <table width="100%" cellpadding="5" cellspacing="0" border="1">
                <tr bgcolor="#CCCCCC">
                    <th colspan="100%">Log's da Conexao</th>
                </tr><?php
                echo '<tr>';
                foreach ($col as $k => $v) {
                    $v = ucwords(str_replace('_',' ',$v));
                    echo '<th>'.$v.'</th>';
                }
                echo '</tr>';
                $i = 0;
                foreach ($vet as $key => $value) {
                    $cor = ($i % 2) ? '#FFFFFF' : '#EEEEEE' ;
                    echo '<tr bgcolor="'.$cor.'">';
                    foreach ($value as $k => $v) {
                        if ($k == 'SQL') {
                            echo '<td>'.nl2br($v).'</td>';
                        } else {
                            echo '<td>'.htmlspecialchars($v).'</td>';
                        }
                    }
                    echo '</tr>';
                    $i++;
                }?>
            </table><?php
        }?>
    </body>
</html>
