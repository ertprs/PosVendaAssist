<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";
include "autentica_admin.php";

$titulo = "Quebra por peça- Defeitos";

?>

<html>
<head>
<title><?echo $titulo?></title>

</head>

<style type="text/css">
<!--

#externo {
	position: relative;
	width: 664px;
	height: 20px;
	left: 3%;
	border-width: thin;
	border-color: #000000
}

#cab_defeito {
	position: absolute;
	top: 0;
	left: 0;
	width: 90px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_descricao {
	position: absolute;
	top: 0;
	left: 92;
	width: 490px;
	background-color: #EFF5F5;
	text-align: left;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_ocorrencia {
	position: absolute;
	top: 0;
	left: 584;
	width: 80px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}





#res_defeito {
	position: absolute;
	top: 0;
	left: 0;
	width: 90px;
	background-color: #F0EEEE;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_descricao {
	position: absolute;
	top: 0;
	left: 92;
	width: 490px;
	background-color: #F0EEEE;
	text-align: left;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_ocorrencia {
	position: absolute;
	top: 0;
	left: 584;
	width: 80px;
	background-color: #F0EEEE;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}





#tot_defeito {
	position: absolute;
	top: 0;
	left: 0;
	width: 90px;
	background-color: #EFF5F5;
	text-align: left;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#tot_descricao {
	position: absolute;
	top: 0;
	left: 92;
	width: 490px;
	background-color: #EFF5F5;
	text-align: left;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#tot_ocorrencia {
	position: absolute;
	top: 0;
	left: 584;
	width: 80px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

-->
</style>

<script LANGUAGE="JavaScript">
	window.moveTo (15,100);
</script>

<body bgcolor="#ffffff">

<br>

<?
if (strlen($peca) > 0) {
	$xdata_i = $HTTP_GET_VARS["data_i"];
	$xdata_f = $HTTP_GET_VARS["data_f"];
	
	$data_i = substr($xdata_i,8,2) ."/". substr($xdata_i,5,2) ."/". substr($xdata_i,0,4);
	$data_f = substr($xdata_f,8,2) ."/". substr($xdata_f,5,2) ."/". substr($xdata_f,0,4);
	
	$produto = trim($HTTP_GET_VARS["produto"]);
	$peca    = trim($HTTP_GET_VARS["peca"]);
	
	
	$sql = "SELECT * FROM (
			SELECT   vw_quebra_defeito.equipamento                  ,
					 vw_quebra_defeito.ref_equipamento              ,
					 vw_quebra_defeito.nome_equipamento             ,
					 vw_quebra_defeito.peca                         ,
					 vw_quebra_defeito.ref_peca                     ,
					 vw_quebra_defeito.nome_peca                    ,
					 vw_quebra_defeito.codigo_defeito               ,
					 vw_quebra_defeito.nome_defeito                 ,
					 sum(vw_quebra_defeito.ocorrencia) AS ocorrencia
			FROM (
			SELECT   tbl_produto.produto       AS equipamento      ,
					 tbl_produto.referencia    AS ref_equipamento  ,
					 tbl_produto.descricao     AS nome_equipamento ,
					 tbl_peca.peca             AS peca             ,
					 tbl_peca.referencia       AS ref_peca         ,
					 tbl_peca.descricao        AS nome_peca        ,
					 tbl_defeito.defeito       AS codigo_defeito   ,
					 tbl_defeito.descricao     AS nome_defeito     ,
					 count(*)                  AS ocorrencia       ,
					 tbl_extrato.aprovado      AS aprovacao
			FROM     tbl_os_item
			JOIN     tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN     tbl_os         ON tbl_os.os                 = tbl_os_produto.os
			JOIN     tbl_os_extra   ON tbl_os_extra.os           = tbl_os.os
			JOIN     tbl_defeito    ON tbl_defeito.defeito       = tbl_os_item.defeito
			JOIN     tbl_produto    ON tbl_produto.produto       = tbl_os.produto
			JOIN     tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca
			JOIN     tbl_extrato    ON tbl_extrato.extrato       = tbl_os_extra.extrato
			WHERE    tbl_os.finalizada    NOTNULL
			AND      tbl_extrato.aprovado NOTNULL
			GROUP BY tbl_produto.produto   ,
					 tbl_produto.referencia,
					 tbl_produto.descricao ,
					 tbl_peca.peca         ,
					 tbl_peca.referencia   ,
					 tbl_peca.descricao    ,
					 tbl_defeito.defeito   ,
					 tbl_defeito.descricao ,
					 tbl_extrato.aprovado
			) AS  vw_quebra_defeito
			WHERE    vw_quebra_defeito.aprovacao::date BETWEEN '$xdata_i' AND '$xdata_f'
			AND      vw_quebra_defeito.equipamento     = '$produto'
			AND      vw_quebra_defeito.peca            = '$peca'
			GROUP BY vw_quebra_defeito.equipamento     ,
					 vw_quebra_defeito.ref_equipamento ,
					 vw_quebra_defeito.nome_equipamento,
					 vw_quebra_defeito.peca            ,
					 vw_quebra_defeito.ref_peca        ,
					 vw_quebra_defeito.nome_peca       ,
					 vw_quebra_defeito.codigo_defeito  ,
					 vw_quebra_defeito.nome_defeito
			) AS xxx ORDER BY ocorrencia DESC;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='0' cellpadding='2' cellspacing='2' width='95%' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>QUEBRA POR DEFEITO</b></font>";
		echo "</td>";
		
		echo "</tr>";
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>". pg_result($res,0,ref_peca) ." - ". pg_result($res,0,nome_peca) ."</b></font>";
		echo "</td>";
		
		echo "</tr>";
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$data_i até $data_f</font>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
		flush();
		
		echo "<div id='externo'>\n" ;
		
		echo "<div id='cab_defeito'><b>\n" ;
		echo "Defeito";
		echo "</b></div>\n";
		
		echo "<div id='cab_descricao'><b>\n" ;
		echo "Descrição";
		echo "</b></div>\n";
		
		echo "<div id='cab_ocorrencia'><b>\n" ;
		echo "Ocorrência";
		echo "</b></div>\n";
		
		echo "</div>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$defeito    = pg_result($res,$x,codigo_defeito);
			$descricao  = pg_result($res,$x,nome_defeito);
			$ocorrencia = pg_result($res,$x,ocorrencia);
			
			echo "<div id='externo'>\n" ;
			
			echo "<div id='res_defeito'><b>\n" ;
			echo "$defeito";
			echo "</b></div>\n";
			
			echo "<div id='res_descricao'><b>\n" ;
			echo "$descricao";
			echo "</b></div>\n";
			
			echo "<div id='res_ocorrencia'><b>\n" ;
			echo $ocorrencia;
			echo "</b></div>\n";
			
			echo "</div>\n";
		}
		echo "<div id='externo'>\n" ;
		
		echo "<div id='tot_defeito'><b>\n" ;
		echo "TOTAL";
		echo "</b></div>\n";
		
		echo "<div id='tot_descricao'><b>\n" ;
		echo "&nbsp;";
		echo "</b></div>\n";
		
		echo "<div id='tot_ocorrencia'><b>\n" ;
		echo $total_ocorrencia;
		echo "</b></div>\n";
		
		echo "</div>\n";
	}
	
	echo "<table border='0' cellpadding='2' cellspacing='2' width='50%' align='center'>";
	echo "<tr>";
	
	echo "<td width='50%' bgcolor='#FFFFFF' align='center'>";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#0000FF' size='2'>";
	echo "<a href='javascript:window.close()'><b>FECHAR</b></a>";
	echo "</font>";
	echo "</td>";
	
	echo "<td width='50%' bgcolor='#FFFFFF' align='center'>";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#0000FF' size='2'>";
	echo "<a href='javascript:history.back()'><b>VOLTAR</b></a>";
	echo "</font>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";

}
?>

</body>
</html>