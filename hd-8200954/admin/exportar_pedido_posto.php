<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$data_inicial = trim($_GET["data_inicial"]);
$data_final   = trim($_GET["data_final"]);
$peca        = trim($_GET["peca"]);

$layout_menu = "auditoria";
$title = "Auditoria -  Peças Pendentes por Estoque";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?

		$sql = "select tbl_posto_fabrica.codigo_posto, tbl_posto.cnpj , tbl_posto.nome, tbl_posto.cidade, tbl_posto.estado,
				CASE WHEN informatizado is true then 'SIM' else 'NAO' END AS informatizado, 
				CASE WHEN branca is true then 'SIM' else 'NAO' END AS branca , 
				CASE WHEN portateis is true then 'SIM' else 'NAO' END AS portateis , 
				CASE WHEN refrigeracao is true then 'SIM' else 'NAO' END AS refrigeracao
				from tbl_pesquisa_suggar
				join tbl_posto on tbl_posto.posto = tbl_pesquisa_suggar.posto
				join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto  and tbl_posto_fabrica.fabrica=24
				order by codigo_posto";
	$res = pg_exec ($con,$sql);
//echo $sql;
	if (pg_numrows($res) > 0) {
		flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/postos_linha-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/postos_linha-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Postos Linha");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		

		fputs ($fp,"<table border='1' cellpadding='0' cellspacing='0'>");
	
		fputs ($fp,"<tr class='Titulo'>");
		fputs ($fp,"<td >Código</td>");
		fputs ($fp,"<td >Cnpj</td>");
		fputs ($fp,"<td >Nome</td>");
		fputs ($fp,"<td >Cidade</td>");
		fputs ($fp,"<td >Estado</td>");
		fputs ($fp,"<td >Informatizado</td>");
		fputs ($fp,"<td >Branca</td>");
		fputs ($fp,"<td >Portáteis</td>");
		fputs ($fp,"<td >Refrigeração</td>");
		fputs ($fp,"</tr>");
	
		$total = pg_numrows($res);

		for ($i=0; $i<pg_numrows($res); $i++){
	
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$cnpj               = trim(pg_result($res,$i,cnpj));
			$nome               = trim(pg_result($res,$i,nome));
			$cidade             = trim(pg_result($res,$i,cidade));
			$estado             = trim(pg_result($res,$i,estado));
			$informatizado      = trim(pg_result($res,$i,informatizado));
			$branca             = trim(pg_result($res,$i,branca));
			$portateis          = trim(pg_result($res,$i,portateis));
			$refrigeracao       = trim(pg_result($res,$i,refrigeracao));
//			$total_total = $total_total + $qtde;
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			
			fputs ($fp,"<tr class='Conteudo'align='center'>");
			fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>");
			fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$cnpj &nbsp;</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$nome</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$cidade</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$estado</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$informatizado</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$branca</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$portateis</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$refrigeracao</td>");

			fputs ($fp,"</tr>");
		}
		fputs ($fp,"</table>");
	}else{
		echo "<br><center>Nenhum resultado encontrado</center>";
	}
	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);
	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/postos_linha-$login_fabrica.$data.xls /tmp/assist/postos_linha-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/postos_linha-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";


?>

<p>

<? include "rodape.php" ?>
