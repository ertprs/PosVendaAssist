<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT tbl_posto_fabrica.item_aparencia
		FROM   tbl_posto
		JOIN   tbl_posto_fabrica USING(posto)
		WHERE  tbl_posto.posto           = $login_posto
		AND    tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
//echo "$sql";
if (pg_numrows($res) > 0) {
	$item_aparencia = pg_result($res,0,item_aparencia);
}
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body leftmargin="0" onblur="setTimeout('window.close()',5500);">

<img src="imagens/pesquisa_pecas.gif">

<?
$referencia = trim(strtoupper($_GET["peca"]));
$xreferencia = str_replace(".","",$referencia);
$xreferencia = str_replace(",","",$xreferencia);
$xreferencia = str_replace("-","",$xreferencia);
$xreferencia = str_replace("/","",$xreferencia);
$xreferencia = str_replace(" ","",$xreferencia);

echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>\n";

echo "<br><br>\n";

if (strlen($xreferencia) > 0) {
	$sql =	"SELECT z.peca                                ,
					z.referencia       AS peca_referencia ,
					z.descricao        AS peca_descricao  ,
					z.peca_fora_linha                     ,
					z.de                                  ,
					z.para                                ,
					z.peca_para                           ,
					tbl_peca.descricao AS para_descricao
			FROM (
					SELECT  y.peca               ,
							y.referencia         ,
							y.descricao          ,
							y.peca_fora_linha    ,
							tbl_depara.de        ,
							tbl_depara.para      ,
							tbl_depara.peca_para
					FROM (
							SELECT  x.peca                                      ,
									x.referencia                                ,
									x.descricao                                 ,
									tbl_peca_fora_linha.peca AS peca_fora_linha
							FROM (
									SELECT  tbl_peca.peca       ,
											tbl_peca.referencia ,
											tbl_peca.descricao
									FROM tbl_peca
									WHERE tbl_peca.fabrica = $login_fabrica
									AND   tbl_peca.ativo IS TRUE
									AND   tbl_peca.produto_acabado IS NOT TRUE
									AND   tbl_peca.acessorio IS NOT TRUE";
	if (strlen($xreferencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) = UPPER(TRIM('$xreferencia'))";
	if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
	$sql .= "					) AS x
							LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
						) AS y
					LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
				) AS z
			LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
			ORDER BY z.descricao";
}
$res = pg_exec ($con,$sql);

 //if (getenv("REMOTE_ADDR") == '201.76.85.4') echo nl2br($sql);

//echo "$sql";
if (pg_numrows($res) == 0) {
	echo "<h2>Item '$referencia' não existe, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}
//takashi colocou 12-11 pedido rubia help desk
if (pg_numrows($res) == 1) {
	$peca            = trim(@pg_result($res,0,peca));
	$peca_referencia = trim(@pg_result($res,0,peca_referencia));
	$peca_descricao  = trim(@pg_result($res,0,peca_descricao));
	$peca_descricao  = str_replace('"','',$peca_descricao);
	//takashi colocou 21-11 tirando o ' da referencia
	$peca_descricao  = str_replace("'",'',$peca_descricao);
	$peca_fora_linha = trim(@pg_result($res,0,peca_fora_linha));
	$peca_para       = trim(@pg_result($res,0,peca_para));
	$para            = trim(@pg_result($res,0,para));
	$para_descricao  = trim(@pg_result($res,0,para_descricao));

	
	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$peca_descricao</font>";
		}else{
			echo "<script language='JavaScript'>\n";
			echo "peca_referencia.value='$peca_referencia';";
			echo "descricao.value='$peca_descricao';";
			echo "qtde.focus();";
			echo "this.close();";
			echo "</script>\n";
		}
	if (strlen($peca_fora_linha) > 0) {
			echo "<tr>\n";
			echo "<td colspan='2' nowrap>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>";
			echo "A peça acima é obsoleta, não é mais fornecida";
			echo "</b></font>";
			echo "</td>\n";
			echo "</tr>\n";
		}else{
		//takashi 29-01 HD1116
			if (strlen($para) > 0 and 1==2) {

				echo "<script language='JavaScript'>\n";
				echo "referencia.value='$para';";
				echo "descricao.value='$para_descricao';";
				echo "qtde.focus();";
				echo "this.close();";
				echo "</script>\n";

			}
	}
}exit;
//takashi colocou 12-11 pedido rubia help desk
echo "<table width='100%' border='0'>\n";

for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	$peca            = trim(@pg_result($res,$i,peca));
	$peca_referencia = trim(@pg_result($res,$i,peca_referencia));
	$peca_descricao  = trim(@pg_result($res,$i,peca_descricao));
	$peca_descricao  = str_replace('"','',$peca_descricao);
	$peca_fora_linha = trim(@pg_result($res,$i,peca_fora_linha));
	$peca_para       = trim(@pg_result($res,$i,peca_para));
	$para            = trim(@pg_result($res,$i,para));
	$para_descricao  = trim(@pg_result($res,$i,para_descricao));

	echo "<tr>\n";

	echo "<td nowrap>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$peca_referencia</font>";
	echo "</td>\n";

	echo "<td nowrap>";
	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$peca_descricao</font>";
	}else{
		echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao'; qtde.focus(); this.close();\"><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>$peca_descricao</font></a>";
	}
	echo "</td>\n";
	echo "</tr>\n";
	if (strlen($peca_fora_linha) > 0) {
		echo "<tr>\n";
		echo "<td colspan='2' nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>";
		echo "A peça acima é obsoleta, não é mais fornecida";
		echo "</b></font>";
		echo "</td>\n";
		echo "</tr>\n";

		/* HD 152533 */
		if (strlen($para) > 0) {
			echo "<tr>\n";
			echo "<td colspan='2' nowrap>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>A peça acima mudou Para:</b></font>";
			echo " <a href=\"javascript: referencia.value='$para'; descricao.value='$para_descricao'; qtde.focus(); this.close();\"><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>$para</font></a>";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}else{
		if (strlen($para) > 0) {
			echo "<tr>\n";
			echo "<td colspan='2' nowrap>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>A peça acima mudou Para:</b></font>";
			echo " <a href=\"javascript: referencia.value='$para'; descricao.value='$para_descricao'; qtde.focus(); this.close();\"><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>$para</font></a>";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
}

echo "</table>";
?>

</body>
</html>