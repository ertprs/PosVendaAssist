<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO - POSTOS POR DISTRIBUIDOR";

include "cabecalho.php";

$sql = "SELECT	distrib_fab.codigo_posto as codigo_distrib,
				distrib.nome as nome_distrib              ,
				posto_fab.codigo_posto                    ,
				posto.nome                                ,
				posto.cidade                              ,
				posto.estado                               
		FROM	tbl_posto posto 
		JOIN	tbl_posto_fabrica posto_fab		 ON posto.posto            = posto_fab.posto 
												AND posto_fab.fabrica      = 3 
		JOIN	tbl_posto_fabrica distrib_fab    ON posto_fab.distribuidor = distrib_fab.posto 
												AND distrib_fab.fabrica    = 3 
		JOIN	tbl_posto distrib				 ON distrib_fab.posto      = distrib.posto 
		WHERE	posto_fab.fabrica = $login_fabrica 
		ORDER BY distrib.nome, 
				 posto.nome;";
$res = pg_exec ($con,$sql);
	
if (pg_numrows($res) > 0) {
	echo "<br><br>";
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript...</font></td>";
	echo "</tr>";
	echo "</table>";

	$data = date ("d/m/Y HH24:MI:SS");
	
	echo `rm /var/www/assist/www/admin/xls/distribuidor-posto-$login_fabrica.xls`;
	
	$fp = fopen ("/tmp/assist/distribuidor-posto-$login_fabrica.html","w");
	
	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>DISTRIBUIDOR / POSTOS - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	
	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");

	// CABECALHO DO POSTO
	fputs ($fp,"<tr>");
	fputs ($fp,"<td COLSPAN=4 bgcolor='#E9F3F3' align='left'><B>RELAÇÃO DE POSTOS POR DISTRIBUIDOR</B></td>");
	fputs ($fp,"</tr>");

	$xcodigo_distrib = 0;

	for ($i=0; $i<pg_numrows($res); $i++){
		$codigo_distrib = trim(pg_result($res,$i,codigo_distrib));
		$nome_distrib   = trim(pg_result($res,$i,nome_distrib));
		$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
		$nome           = trim(pg_result($res,$i,nome));
		$cidade         = trim(pg_result($res,$i,cidade));
		$estado         = trim(pg_result($res,$i,estado));

		if ($xcodigo_distrib <> $codigo_distrib){
			$xcodigo_distrib = $codigo_distrib;
			// DADOS DO DISTRIBUIDOR
			fputs ($fp,"<tr>");
			fputs ($fp,"<td>&nbsp;</td>");
			fputs ($fp,"<td>&nbsp;</td>");
			fputs ($fp,"<td>&nbsp;</td>");
			fputs ($fp,"<td>&nbsp;</td>");
			fputs ($fp,"</tr>");

			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><B>CÓDIGO DISTRIBUIDOR</B></td>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center' COLSPAN='3'><B>NOME DISTRIBUIDOR</B></td>");
			fputs ($fp,"</tr>");

			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>$codigo_distrib</td>");
			fputs ($fp,"<td bgcolor='$cor' align='left' colspan=3>$nome_distrib</td>");
			fputs ($fp,"</tr>");

			// CABECALHO DO POSTO
			fputs ($fp,"<tr>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><B>CÓDIGO POSTO</B></td>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><B>NOME POSTO</B></td>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><B>CIDADE</B></td>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><B>ESTADO</B></td>");
			fputs ($fp,"</tr>");
		}

		$cor = '#FFFFFF';
						
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='$cor' align='center'>$codigo_posto</td>");
		fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$nome</td>");
		fputs ($fp,"<td bgcolor='$cor' align='left'>$cidade</td>");
		fputs ($fp,"<td bgcolor='$cor' align='center'>$estado</td>");
		fputs ($fp,"</tr>");
	}
	fputs ($fp,"</table>");
	
	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);
	
	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/admin/xls/distribuidor-posto-$login_fabrica.xls /tmp/assist/distribuidor-posto-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/distribuidor-posto-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><BR><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar o arquivo para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
	
	echo `rm /tmp/assist/distribuidor-posto-$login_fabrica.html`;
	

}else{
	echo "<br>";
	
	echo "<b>Nenhum resultado encontrado.</b>";
}

flush();

?>

<p>

<!--<a href='relatorio_field_call_rate_serie_xls.php?data_inicial=$aux_data_inicial&data_final=$aux_data_final?linha=$linha&estado=$estado'>xls</a>-->

<? include "rodape.php" ?>