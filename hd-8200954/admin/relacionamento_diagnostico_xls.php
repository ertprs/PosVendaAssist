<?
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

	include 'funcoes.php';

	$layout_menu = 'os';
	include "cabecalho.php";

	$data = date ("d-m-Y-H-i");

	echo `mkdir /tmp/assist`;
	echo `chmod 777 /tmp/assist`;
	//echo `rm /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.xls`;
	echo `rm /tmp/assist/integridade-$login_fabrica-$data.html`;
	echo `rm /tmp/assist/integridade-$login_fabrica-$data.xls`;
	echo `rm /var/www/assist/www/download/integridade-$login_fabrica-$data.zip`;

	$fp = fopen ("/tmp/assist/integridade-$login_fabrica-$data.html","w");


	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>Relacionamento de integridade</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");

	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='1' cellspacing='1'>");

	//-------------------------------------- produtos ---------------------------------------------//
	fputs ($fp, "<tr align='center'>");
	fputs ($fp, "<td colspan='5'>Relacionamento de integridade</TD>");
	fputs ($fp, "</tr>\n");
	fputs ($fp, "<tr bgcolor='#0000FF' align='center'>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>LINHA</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>FAMILIA</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>RECLAMADO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>CONSTATADO</FONT></TD>");
	fputs ($fp, "<td nowrap><FONT  COLOR='#FFFFFF'>SOLUÇÃO</FONT></TD>");
	fputs ($fp, "</tr>\n");

	$sql = "SELECT
				tbl_linha.nome as linha,
				tbl_familia.descricao as familia,
				tbl_defeito_reclamado.descricao as reclamacao,
				tbl_defeito_constatado.descricao as constatado,
				tbl_solucao.descricao as solucao
			FROM tbl_diagnostico
			JOIN tbl_linha using(linha)
			JOIN tbl_familia using(familia)
			JOIN tbl_defeito_reclamado using(defeito_reclamado)
			JOIN tbl_defeito_constatado using(defeito_constatado)
			JOIN tbl_solucao using(solucao)
			WHERE tbl_diagnostico.fabrica=$login_fabrica
			ORDER BY 	tbl_linha.nome,
						tbl_familia.descricao,
						tbl_defeito_reclamado.descricao,
						tbl_defeito_constatado.descricao,
						tbl_solucao.descricao";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			fputs ($fp, "<tr>");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,linha))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,familia))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,reclamacao))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,constatado))."</TD>\n");
			fputs($fp,"<TD nowrap>".trim(pg_result($res,$i,solucao))."</TD>\n");
			fputs ($fp, "</tr>\n");
		}
	}

	fputs ($fp, "</table>\n");
	fputs ($fp, "<table height='20'><tr class='menu_top'><td colspan='2' align='left'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td></tr></table>");

	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);

	//gera o xls
	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /tmp/assist/integridade-$login_fabrica-$data.xls /tmp/assist/integridade-$login_fabrica-$data.html`;

	//gera o zip
	echo `cd /tmp/assist/; rm -rf integridade-$login_fabrica-$data.zip; zip -o integridade-$login_fabrica-$data.zip integridade-$login_fabrica-$data.xls > /dev/null`;

	//move o zip para "/var/www/assist/www/download/"
	echo `mv  /tmp/assist/integridade-$login_fabrica-$data.zip /var/www/assist/www/download/integridade-$login_fabrica-$data.zip`;
		
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='../download/integridade-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo</font></a>.</td>";
	echo "</tr>";
	echo "</table>";

?>

<p>

<? include "rodape.php"; ?>
