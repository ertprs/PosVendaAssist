<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

?>
<html>
    <head>
        <title>LOG CONEXAO</title>
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
    $sql = "select procpid,
                   now() - query_start   as query_start,
                   now() - backend_start as backend_start,
                   datname               as database,
                   usename               as usuario,
                   client_addr           as IP,
                   current_query         as SQL,
                   programa,
				   tbl_fabrica.fabrica,
				   tbl_fabrica.nome		 as fabrica_nome,
				   tbl_posto.posto,
				   tbl_posto.nome		 as posto_nome,
				   tbl_login_unico.login_unico,
				   tbl_login_unico.nome	 as login_unico_nome,
                   admin,
                   nome_completo
              from pg_catalog.pg_stat_activity
         left join tbl_log_conexao on pg_catalog.pg_stat_activity.procpid = tbl_log_conexao.pid AND data >= backend_start
         left join tbl_admin using(admin)
		 left join tbl_fabrica on tbl_log_conexao.fabrica=tbl_fabrica.fabrica
		 left join tbl_posto on tbl_log_conexao.posto=tbl_posto.posto
		 left join tbl_login_unico on tbl_log_conexao.login_unico=tbl_login_unico.login_unico
             order by now() - query_start desc";

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
