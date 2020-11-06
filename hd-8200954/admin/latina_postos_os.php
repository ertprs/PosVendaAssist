<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
?>
<style type="text/css">


.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Conteudo2 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.Principal{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
</style>
<?
$sql = "SELECT COUNT(OS) as qtde, nome,cnpj
	from tbl_posto
	JOIN tbl_posto_fabrica USING(posto)
	JOIN tbl_os USING(posto)
	WHERE tbl_os.posto IN ( SELECT posto FROM tbl_posto_fabrica WHERE fabrica=15)
	AND   tbl_os.data_abertura >'2006-06-01 00:00:00'
	AND   tbl_posto_fabrica.credenciamento<>'DESCREDENCIADO'
	GROUP BY nome,cnpj";
	
	$resx = pg_exec($con,$sql);
	
	if (pg_numrows($resx) > 0) {
		echo "<TABLE width='50%' border='1' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
		echo "<TR class='Principal'>";

		echo "<TD align='left'>Posto</TD>";
		echo "<TD align='center'>CNPJ</TD>";
		echo "<TD align='center'>QTD OS</TD>";
		echo "</TR>";

		for ($i = 0 ; $i < pg_numrows($resx) ; $i++) {
			$nome = trim(pg_result($resx,$i,nome));
			$cnpj = trim(pg_result($resx,$i,cnpj));
			$qtde  = trim(pg_result($resx,$i,qtde));
			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
			}else{
				$cor = "#F7F5F0";
			}

			echo "<TR class='Conteudo' bgcolor='$cor'>";

			echo "<TD align='left'>$nome</TD>";
			echo "<TD align='left'>$cnpj</TD>";
			echo "<TD align='center'>$qtde</TD>";
			
			echo "</TR>";
		}
			
		echo "</TABLE>";

	}

echo "samuelcsantos@hotmail.com";
?>