<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
$extrato = $_GET['extrato'];
$login_posto = $_GET['posto'];

if(!empty($login_posto)){
	include "../autentica_usuario.php";
}else{
	include "autentica_admin.php";
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			td{
				text-align: center !important;
			}
		</style>
		<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();
			}); 
		</script>
	</head>
	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>

<?php

$sqlEncontro = "SELECT  to_char(encontro_parcela::date,'DD/MM/YYYY') AS dt_vencimento,
                        encontro_titulo_a_pagar,
                        encontro_valor_liquido,
                        nf_valor_do_encontro_contas
                    FROM tbl_encontro_contas
                    WHERE fabrica = $login_fabrica
                    AND extrato = $extrato";
$resEncontro = pg_query($con,$sqlEncontro);

if(pg_num_rows($resEncontro) > 0){
?>
	<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
		<thead>
			<tr>
				<th align="center">Nº Duplicata</th>
				<th align="center">Data Vencimento</th>
				<th align="center">Valor</th>
				<th align="center">Saldo de D&eacute;bito</th>
			</tr>
		</thead>
		<tbody>

<?php
	for($i = 0; $i < pg_num_rows($resEncontro); $i++){
	    $duplicata      = pg_fetch_result($resEncontro, $i, 'encontro_titulo_a_pagar');
	    $dt_vencimento  = pg_fetch_result($resEncontro, $i, 'dt_vencimento');
	    $valor 		    = pg_fetch_result($resEncontro, $i, 'encontro_valor_liquido');
	    $saldo          = pg_fetch_result($resEncontro, $i, 'nf_valor_do_encontro_contas');
	    $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
			<tr style="background: <?=$cor?>">
			    <td><?=$duplicata?></td>
			    <td><?=$dt_vencimento?></td>
			    <td><?=number_format($valor,2,',','.')?></td>
			    <td><?=number_format($saldo,2,',','.')?></td>
			</tr>
<?php
	}
	echo "</tbody>";
	echo "</table>";
}
?>