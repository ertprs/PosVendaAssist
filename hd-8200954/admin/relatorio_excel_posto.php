<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$sql = "SELECT codigo_posto        ,
               nome                ,
               desconto            ,
               desconto_acessorio
          FROM tbl_posto 
          JOIN tbl_posto_fabrica using (posto)
         WHERE fabrica=20
		 ORDER BY nome";


$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
    //echo '<pre>';
	echo '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse" bordercolor="#000000">';
    echo '<tr>';
    echo '<td>'.utf8_decode('Código do Posto').'</td>';
    echo '<td>Nome do Posto</td>';
    echo '<td>'.utf8_decode('Desconto de Peças').'</td>';
    echo '<td>'.utf8_decode('Desconto de Acessórios').'</td>';
    echo '</tr>';
    for ($i = 0 ; $i < pg_numrows($res); $i++) {
        $vet[$i] = pg_fetch_array($res,$i,PGSQL_ASSOC);
        echo '<tr>';
        echo '<td>'.$vet[$i]['codigo_posto'].'</td>';
        echo '<td>'.$vet[$i]['nome'].'</td>';
        echo '<td>'.$vet[$i]['desconto'].'</td>';
        echo '<td>'.$vet[$i]['desconto_acessorio'].'</td>';
        echo '</tr>';
    }
    echo '</table>';
    //print_r($vet);
}

?>