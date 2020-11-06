<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$admin_privilegios="cadastro";
include 'autentica_admin.php';

$layout_menu = "cadastro";
include "cabecalho.php";
?>

	<style type="text/css">
		.formulario{
			background-color:#D9E2EF;
			font:11px Arial;
			text-align:left;
		}	
	</style>

<?

$pais = $_GET["pais"];
$todos = $_GET["todos"];
if(strlen($pais)>0 or strlen($todos)>0){
	$sql = "SELECT  tbl_produto.produto   ,
			tbl_produto.referencia,
			tbl_produto.descricao ,
			tbl_produto.ativo     ,";
	if(strlen($todos)>0){	$sql .= " tbl_produto_pais.pais , ";}
	$sql .= "(
				SELECT count(peca) 
				FROM tbl_lista_basica 
				WHERE tbl_lista_basica.produto = tbl_produto.produto
			)	AS pecas     ,
			(
				SELECT count(defeito_constatado) 
				FROM tbl_produto_defeito_constatado 
				WHERE tbl_produto_defeito_constatado.produto = tbl_produto.produto
			)	AS vt,
			tbl_produto.origem                         ,
			tbl_produto.voltagem                       ,
			tbl_linha.nome as linha_descricao          ,
			tbl_familia.descricao as familia_descricao ,
			tbl_produto.referencia_fabrica             
		FROM      tbl_produto
		JOIN      tbl_linha        USING(linha)
		LEFT JOIN tbl_produto_pais USING(produto)
		LEFT JOIN tbl_familia      USING(familia)
		WHERE    tbl_linha.fabrica      = $login_fabrica ";
if(strlen($todos)==0){	
	$sql .=	" AND      tbl_produto_pais.pais  = '$pais' ";
}
	$sql .= " ORDER BY tbl_produto.descricao  ";
//if($ip=="201.42.109.216"){ echo $sql;}
$res = pg_exec($con,$sql)	;
if(pg_numrows($res)>0){
		flush();
		
		echo "<table width='700' border='0' cellspacing='1' cellpadding='6' align='center' class='formulario'>";
		echo "<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";

		flush();
				flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/produto_informacoes_pais-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/produto_informacoes_pais-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Produtos - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

	fputs ($fp,"<table width='700' border='1' cellspacing='0' cellpadding='2'align='center' style='border-collapse: collapse' bordercolor='#d2e4fc' >");


	fputs ($fp,"<tr>");
	fputs ($fp,"<td colspan='2' bgcolor='#d2e4fc'><b>PRODUTO</td>");
	fputs ($fp,"<td bgcolor='#d2e4fc'><b>PAIS</td>");
	fputs ($fp,"<td bgcolor='#d2e4fc'><b>ATIVO</td>");
	fputs ($fp,"<td bgcolor='#d2e4fc'><b>Lista Básica</td>");
	fputs ($fp,"<td bgcolor='#d2e4fc'><b>VT</td>");
	fputs ($fp, "<td bgcolor='#d2e4fc'><b>ORIGEM</td>\n");
	fputs ($fp, "<td bgcolor='#d2e4fc'><b>VOLTAGEM</td>\n");
	fputs ($fp, "<td bgcolor='#d2e4fc'><b>LINHA</td>\n");
	fputs ($fp, "<td bgcolor='#d2e4fc'><b>FAMILIA</td>\n");
	fputs ($fp, "<td bgcolor='#d2e4fc'><b>BAR TOOL</td>\n");
	fputs ($fp,"</tr>");

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$ativo      = trim(pg_result($res,$i,ativo));
		$pecas      = trim(pg_result($res,$i,pecas));
		$vt         = trim(pg_result($res,$i,vt));
		$origem     = trim(pg_result($res,$i,origem));
		$voltagem   = trim(pg_result($res,$i,voltagem));
		$linha_descricao    = trim(pg_result($res,$i,linha_descricao));
		$familia_descricao  = trim(pg_result($res,$i,familia_descricao));
		$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));

	if(strlen($todos)>0){	
		$pais         = trim(pg_result($res,$i,pais));
		if(strlen($pais)==0) $pais = "BR";
	}

		$descricao = str_replace ('"','',$descricao);

		
		if($acessorio=='t') $acessorio = 'SIM';
		else                $acessorio = 'NÃO';

		if($ativo=='t')     $ativo     = 'SIM';
		else                $ativo     = 'NÃO';

		if($pecas>0)        $xpecas    = 'SIM';
		else                $xpecas    = '<b>NÃO</b>';

		if($vt>0)           $vt        = 'SIM';
		else                $vt        = '<b>NÃO</b>';

		fputs ($fp,"<tr>");
		
		fputs ($fp,"<td >");
		fputs ($fp,"<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>");
		fputs ($fp,"</td>");
		
		fputs ($fp,"<td align='left'>");
		fputs ($fp,"<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$descricao</font>");
		fputs ($fp,"</td>");

		fputs ($fp,"<td>");
		fputs ($fp,"<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$pais</font>");
		fputs ($fp,"</td>");

		fputs ($fp,"<td>");
		fputs ($fp,"<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$ativo</font>");
		fputs ($fp,"</td>");

		fputs ($fp,"<td>");
		fputs ($fp,"<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$xpecas");
		if($pecas>0) fputs ($fp," - $pecas peca(s)");
		fputs ($fp,"</font>");
		fputs ($fp,"</td>");

		fputs ($fp,"<td>");
		fputs ($fp,"<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$vt</font>");
		fputs ($fp,"</td>");
		// HD 65762
		fputs ($fp, "<td>\n");
		fputs ($fp, "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$origem</font>\n");
		fputs ($fp, "</td>\n");

		fputs ($fp, "<td>\n");
		fputs ($fp, "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>\n");
		fputs ($fp, "</td>\n");

		fputs ($fp, "<td>\n");
		fputs ($fp, "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$linha_descricao</font>\n");
		fputs ($fp, "</td>\n");

		fputs ($fp, "<td>\n");
		fputs ($fp, "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$familia_descricao</font>\n");
		fputs ($fp, "</td>\n");

		fputs ($fp, "<td>\n");
		fputs ($fp, "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia_fabrica</font>\n");
		fputs ($fp, "</td>\n");

		fputs ($fp,"</tr>");
	}
	fputs ($fp,"</table>");
	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);

	$data = date("Y-m-d").".".date("H-i-s");

	/*echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/produto_informacoes_pais-$login_fabrica.$data.xls /tmp/assist/produto_informacoes_pais-$login_fabrica.html`; */

	rename("/tmp/assist/produto_informacoes_pais-$login_fabrica.html", "/www/assist/www/admin/xls/produto_informacoes_pais-$login_fabrica.$data.xls");
	
	echo "<table width='700' border='0' cellspacing='1' cellpadding='6' align='center' class='formulario'>";
	echo "<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/produto_informacoes_pais-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
}
}
?>

</body>
</html>
