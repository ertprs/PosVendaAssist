<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$title = "OSs aberta a mais de 60 dias";
$layout_menu = 'os';
include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<?php
$cond = ($login_fabrica == 1) ? "" : "\nAND   tbl_os.cortesia IS FALSE\n";

$sql = "SELECT  tbl_os.os,
                tbl_os.sua_os,
                to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                tbl_posto_fabrica.codigo_posto
        FROM    tbl_os
        JOIN    tbl_produto         ON  tbl_produto.produto         = tbl_os.produto
                                    AND tbl_produto.fabrica_i       = $login_fabrica
   LEFT JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
        WHERE   tbl_os.fabrica                              = $login_fabrica
        AND     tbl_os.posto                                = $login_posto
        AND     (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
        AND     tbl_os.data_fechamento                      IS NULL
        AND     tbl_os.excluida                             IS FALSE
                $cond
  ORDER BY      tbl_os.data_abertura DESC";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
	echo "<br />";
	echo "<table width='700' align='center'>";
	echo "<caption class='titulo_tabela'>OSs abertas a mais de 60 dias</caption>";
	echo "<tr class='titulo_coluna'>
			<th>OS</th>
			<th>Data Abertura</th>
		  </tr>";

    for($i = 0; $i < pg_num_rows($res); $i++){
    	$os 			= pg_fetch_result($res, $i, 'os');
    	$sua_os 		= pg_fetch_result($res, $i, 'sua_os');
    	$data_abertura 	= pg_fetch_result($res, $i, 'data_abertura');
    	$codigo_posto 	= pg_fetch_result($res, $i, 'codigo_posto');

    	$cor                = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

    	echo "<tr bgcolor='{$cor}'>";
    	echo "<td><a href='os_press.php?os={$os}' target='_blank'>{$codigo_posto}{$sua_os}</a>";
    	echo "<td>{$data_abertura}</td>";
    	echo "</tr>";
    }
    echo "</table>";
}


$sql = "SELECT  tbl_os.os,
				tbl_os.sua_os,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				tbl_posto_fabrica.codigo_posto
        FROM tbl_os
        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        WHERE tbl_os.fabrica = $login_fabrica
        	AND   tbl_os.posto = $login_posto
            AND   (tbl_os.data_abertura + INTERVAL '90 days') <= current_date
            AND   (tbl_os.data_abertura + INTERVAL '60 days') >= current_date
            AND   tbl_os.data_fechamento IS NULL
            AND   tbl_os.excluida IS FALSE
            AND   tbl_os.cortesia IS FALSE
            ORDER BY tbl_os.data_abertura DESC";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
	echo "<br /><br />";
	echo "<table width='700' align='center'>";
	echo "<caption class='titulo_tabela'>OSs abertas a mais de 90 dias</caption>";
	echo "<tr class='titulo_coluna'>
			<th>OS</th>
			<th>Data Abertura</th>
		  </tr>";

    for($i = 0; $i < pg_num_rows($res); $i++){
    	$os 			= pg_fetch_result($res, $i, 'os');
    	$sua_os 		= pg_fetch_result($res, $i, 'sua_os');
    	$data_abertura 	= pg_fetch_result($res, $i, 'data_abertura');
    	$codigo_posto 	= pg_fetch_result($res, $i, 'codigo_posto');

    	$cor                = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

    	echo "<tr bgcolor='{$cor}'>";
    	echo "<td><a href='os_press.php?os={$os}' target='_blank'>{$codigo_posto}{$sua_os}</a>";
    	echo "<td>{$data_abertura}</td>";
    	echo "</tr>";
    }
    echo "</table>";
}


include "rodape.php";
