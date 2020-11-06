<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


if (strlen($_GET["comunicado"]) > 0) {
	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					tbl_produto.referencia AS prod_referencia        ,
					tbl_produto.descricao  AS prod_descricao         ,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.mensagem                          ,
					tbl_comunicado.tipo                              ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_linha   USING (linha)
			WHERE   tbl_linha.fabrica         = $login_fabrica
			AND     tbl_comunicado.comunicado = $comunicado;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) == 0) {
		$msg_erro = "Comunicado inexistente";
	}else{
		$comunicado           = trim(pg_result($res,0,comunicado));
		$referencia           = trim(pg_result($res,0,prod_referencia));
		$descricao            = trim(pg_result($res,0,prod_descricao));
		$comunicado_descricao = trim(pg_result($res,0,descricao));
		$comunicado_tipo      = trim(pg_result($res,0,tipo));
		$comunicado_mensagem  = trim(pg_result($res,0,mensagem));
		$comunicado_data      = trim(pg_result($res,0,data));
		
		$gif = "/var/www/assist/www/comunicados/$comunicado.gif";
		$jpg = "/var/www/assist/www/comunicados/$comunicado.jpg";
		$pdf = "/var/www/assist/www/comunicados/$comunicado.pdf";
		
		$title = "Comunicados $login_fabrica_nome";
	}
}

$layout_menu = "tecnica";
include 'cabecalho.php';
?>
<style type="text/css">

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;

}

.linha {
	margin: 0px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: left;
	border-bottom: thin dotted #ADD8E6;
		}


</style>

<? if (strlen($msg_erro) > 0) { ?>

<div id="container">
	<H1><? echo $msg_erro; ?></H1>
</div>

<? } ?>

<p>

<?
if ((strlen($comunicado) > 0) && (pg_numrows($res) > 0)) {

	echo "<table class='table' width='400'>";
	echo "<tr>";

	echo "<td align='left'><img src='imagens/cab_comunicado.gif'></td>";

	echo "</tr>";
	echo "<tr>";

	echo "<td align='center'><b>$comunicado_tipo</b>&nbsp;&nbsp;-&nbsp;&nbsp;$comunicado_data</td>";

	echo "</tr>";
	echo "<tr>";

	echo "<td align='center'><b>$descricao</b></td>";

	echo "</tr>";
	echo "<tr>";

	echo "<td align='center'>$comunicado_mensagem</td>";

	echo "</tr>";
	echo "<tr>";

	echo "<td align='center'>&nbsp;</td>";

	echo "</tr>";
	echo "<tr>";

	echo "<td align='left'>";
	if (file_exists($gif) == true) {
		echo "<img src='comunicados/$comunicado.gif'>";
	}

	if (file_exists($jpg) == true) {
		echo "<img src='comunicados/$comunicado.jpg'>";
	}

	if (file_exists($pdf) == true) {
		echo "<font color='#A02828'>Se você não possui o Acrobat Reader&reg;</font> , <a href='http://www.adobe.com/products/acrobat/readstep2.html'>instale agora</a>.";
		echo "<br>";
		echo "Para visualizar o arquivo, <a href='comunicados/$comunicado.pdf' target='_blank'>clique aqui</a>.";
	}
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<br><br><br>";

}

$sql = "SELECT  tbl_comunicado.comunicado                        ,
				tbl_comunicado.tipo                              ,
				to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
		FROM    tbl_comunicado
		JOIN    tbl_produto USING (produto)
		JOIN    tbl_linha   USING (linha)
		WHERE   tbl_linha.fabrica         = $login_fabrica ";

if($comunicado) 
	$sql .= "AND     tbl_comunicado.comunicado <> $comunicado ";

$sql .= "ORDER BY tbl_comunicado.data DESC";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table class='table' width='400' >";
	echo "<tr>";
	
	echo "<td align='left'><img src='imagens/cab_outrosregistrosreferentes.gif'></td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td align='center'><b>$descricao</b></td>";
	
	echo "</tr>";
	echo "</table>";

	echo "<br>";

	echo "<table class='table' width='400'>";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
		$comunicado           = trim(pg_result($res,$x,comunicado));
		$comunicado_tipo      = trim(pg_result($res,$x,tipo));
		$comunicado_data      = trim(pg_result($res,$x,data));

		echo "<tr class='linha'>";
		echo "<td width='100'>$comunicado_data</td>";
		echo "<td class='linha'><a href='$PHP_SELF?comunicado=$comunicado'>$comunicado_tipo</a></td>";

		echo "</tr>";
	}
	echo "</table>";
}
?>

<p>

<?include 'rodape.php';?>