<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";
include "autentica_admin.php";

$titulo = "Black & Decker - OS´s";
?>

<html>
<head>
<title><?echo $titulo?></title>

</head>

<style type="text/css">
<!--

#externo {
	position: relative;
	width: 706px;
	height: 20px;
	left: 1%;
	border-width: thin;
	border-color: #000000
}

#cab_posto {
	position: absolute;
	top: 0;
	left: 0;
	width: 300px;
	background-color: #EFF5F5;
	text-align: left;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_os {
	position: absolute;
	top: 0;
	left: 302;
	width: 100px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_serie {
	position: absolute;
	top: 0;
	left: 404;
	width: 100px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_abertura {
	position: absolute;
	top: 0;
	left: 506;
	width: 100px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#cab_fechamento {
	position: absolute;
	top: 0;
	left: 608;
	width: 100px;
	background-color: #EFF5F5;
	text-align: center;
	font:80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}




#res_posto {
	position: absolute;
	top: 0;
	left: 0;
	width: 300px;
	background-color: #F0EEEE;
	text-align: left;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_os {
	position: absolute;
	top: 0;
	left: 302;
	width: 100px;
	background-color: #F0EEEE;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_serie {
	position: absolute;
	top: 0;
	left: 404;
	width: 100px;
	background-color: #F0EEEE;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_abertura {
	position: absolute;
	top: 0;
	left: 506;
	width: 100px;
	background-color: #F0EEEE;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

#res_fechamento {
	position: absolute;
	top: 0;
	left: 608;
	width: 100px;
	background-color: #F0EEEE;
	text-align: center;
	font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}
-->
</style>

<script LANGUAGE="JavaScript">
	window.focus();
</script>


<script LANGUAGE="JavaScript">
	function Redirect(produto, peca, data_i, data_f) {
		window.open('rel_visao_geral_defeito.php?produto=' + produto + '&peca=' + peca + '&data_i=' + data_i + '&data_f=' + data_f,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>


<body bgcolor="#ffffff">

<br>

<?
if (strlen($produto) > 0) {
	$xdata_i = $_GET["data_i"];
	$xdata_f = $_GET["data_f"];
	
	$data_i = substr($xdata_i,8,2) ."/". substr($xdata_i,5,2) ."/". substr($xdata_i,0,4);
	$data_f = substr($xdata_f,8,2) ."/". substr($xdata_f,5,2) ."/". substr($xdata_f,0,4);
	
	$produto = trim($_GET["produto"]);
	$estado  = trim($_GET["estado"]);
	
	$sql = "SELECT   tbl_os.os                                                        ,
					 tbl_os.sua_os                                                    ,
					 tbl_posto_fabrica.codigo_posto                                   ,
					 tbl_posto.nome                                                   ,
					 to_char(tbl_os.data_abertura, 'DD/MM/YYYY')   AS data_abertura   ,
					 to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento ,
					 tbl_os.serie                                                     ,
					 tbl_os.codigo_fabricacao                                         ,
					 tbl_os.pecas                                                     ,
					 tbl_os.mao_de_obra                                               ,
					 tbl_produto.referencia                        AS ref_equipamento ,
					 tbl_produto.descricao                         AS nome_equipamento
			FROM     tbl_os
			JOIN     tbl_os_extra      ON tbl_os_extra.os         = tbl_os.os
			JOIN     tbl_produto       ON tbl_produto.produto     = tbl_os.produto
			JOIN     tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato
			JOIN     tbl_posto         ON tbl_posto.posto         = tbl_os.posto 
			JOIN     tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
									   AND tbl_posto_fabrica.fabrica = $login_fabrica 
			WHERE    tbl_os.finalizada    NOTNULL
			AND      tbl_extrato.aprovado::date BETWEEN '$xdata_i' AND '$xdata_f'
			AND      tbl_produto.produto      = '$produto' ";

	if (strlen($estado) > 0) $sql .= " AND tbl_posto.estado = '$estado' ";

	$sql .= "ORDER BY    trim((substr(tbl_os.sua_os,0,6))) ASC,
						length(tbl_os.sua_os)             ASC;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='0' cellpadding='2' cellspacing='2' width='95%' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>OS´s que possuem o equipamento</b></font>";
		echo "</td>";
		
		echo "</tr>";
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>". pg_result($res,0,ref_equipamento) ." - ". pg_result($res,0,nome_equipamento) ."</b></font>";
		echo "</td>";
		
		echo "</tr>";
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$data_i até $data_f</font>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
		
		flush();
		
		echo "<table width='90%' align='center' border='0' cellpadding='2' cellspacing='2'>";
		echo "<tr bgcolor='#003366'>";
		echo "<td align='center'><font color='#ffffff'><b>Posto</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>OS</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>Cód. Fabric.</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>Série</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>Abertura</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>Fechamento</b></font></td>";
		echo "</tr>";


		for ($x = 0; $x < pg_numrows($res); $x++) {
			$posto      = trim(pg_result($res,$x,codigo_posto)) ." - ". substr(trim(pg_result($res,$x,nome)),0,30);
			$sua_os     = pg_result($res,$x,codigo_posto).pg_result($res,$x,sua_os);
			$serie      = pg_result($res,$x,serie);
			$codigo_fabricacao = pg_result($res,$x,codigo_fabricacao);
			$abertura   = pg_result($res,$x,data_abertura);
			$fechamento = pg_result($res,$x,data_fechamento);
			
			echo "<tr style='font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif'>";

			echo "<td><b>";
			echo "$posto";
			echo "</b></td>\n";
			
			echo "<td>";
			echo "$sua_os";
			echo "</td>\n";

			echo "<td>";
			echo "$codigo_fabricacao&nbsp;";
			echo "</td>\n";
			
			echo "<td>";
			echo "$serie&nbsp;";
			echo "</td>\n";
			
			echo "<td>";
			echo "$abertura";
			echo "</td>\n";
			
			echo "<td>";
			echo "$fechamento";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
	}

	echo "<br><br>";

	echo "<table border='0' cellpadding='2' cellspacing='2' width='70%' align='center'>";
	echo "<tr>";
	
	echo "<td width='100%' bgcolor='#FFFFFF' align='center'>";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#0000FF' size='2'>";
	echo "<a href='javascript:window.close()'><b>FECHAR</b></a>";
	echo "</font>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
}
?>

</body>
</html>
