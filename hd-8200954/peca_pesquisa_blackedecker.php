<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
include 'funcoes.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   tbl_posto_fabrica.item_aparencia
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING(posto)
		WHERE    tbl_posto.posto           = $login_posto
		AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_num_rows ($res) > 0) {
	$item_aparencia = pg_fetch_result($res,0,item_aparencia);
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Pe�as ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
</head>

<body leftmargin="0" onblur="setTimeout('window.close()',5500);">

<br>

<img src="imagens/pesquisa_pecas.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));

$exibe = $_GET['exibe'];

if (preg_match("os_item.php", $exibe)) {
	$cond_libera_garantia = " AND libera_garantia IS NOT TRUE";
}

if (strlen($_GET['produto']) > 0) {
	$produto_referencia = trim($_GET['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$voltagem = trim(strtoupper($_GET["voltagem"]));

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

	if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

	$sql .= "AND    tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$produto_descricao = pg_fetch_result ($res,0,descricao);
		$produto = pg_fetch_result ($res,0,produto);
	}
}

if ($tipo == "tudo") {
	$descricao = trim(strtoupper($_GET["descricao"]));

	echo "<h4>Pesquisando toda a lista b�sica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";

	echo "<br><br>";

	if ($qtde > 0 AND strlen($produto) > 0) {
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
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao
										FROM tbl_peca
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_peca.produto_acabado IS NOT TRUE ";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(tbl_peca.referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca $cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				/*ADD CONDI��O WHERE - HD 20505 - IGOR 20/05/2008 - ESTAVA CADASTRANDO PRODUTOS NOS ITENS DAS OS*/
				WHERE z.referencia not in(
					SELECT referencia
					FROM tbl_produto
					JOIN tbl_linha using(linha)
					WHERE fabrica = $login_fabrica
					AND (troca_faturada is true or troca_garantia is true)
				)

				ORDER BY z.descricao";
	} else {
        echo "Produto sem lista b�sica";
        exit;
	}
	$res = pg_query ($con,$sql);

	if (@pg_num_rows ($res) == 0) {
		echo "<h1>Nenhuma lista b�sica de pe�as encontrada para este produto</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "descricao") {
	$descricao = trim(strtoupper($_GET["descricao"]));

	echo "<h4>Pesquisando por <b>descri��o da pe�a</b>: <i>$descricao</i></h4>";
	echo "<p>";

	//HD 56720 - 16/12/2008
	$produto = str_replace(".","",$produto);
	$produto = str_replace(",","",$produto);
	$produto = str_replace("-","",$produto);
	$produto = str_replace("/","",$produto);
	$produto = str_replace(" ","",$produto);

	if(strlen($produto) > 0){
		$sqlP = "SELECT COUNT(*) AS qtde
				 FROM tbl_lista_basica
				 WHERE tbl_lista_basica.fabrica = $login_fabrica
				 AND produto = $produto";
		$resP = pg_query ($con,$sqlP);
		if(pg_num_rows($resP)>0) $qtde = pg_fetch_result ($resP,0,0);
	}

	if ($qtde > 0 AND strlen($produto) > 0) {

		if ($login_fabrica == 1) {
				$peca_ativa = "";
		}else{
			$peca_ativa = "AND   tbl_peca.ativo IS TRUE ";
		}

		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						z.ativo                              ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.peca_fora_linha    ,
								y.ativo                   ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.ativo                                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_peca.ativo
										FROM tbl_peca
										JOIN tbl_lista_basica using(peca)
										WHERE tbl_peca.fabrica = $login_fabrica
										$peca_ativa
										AND   tbl_lista_basica.produto = $produto
										AND   tbl_peca.produto_acabado IS NOT TRUE ";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca $cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				/*ADD CONDI��O WHERE - HD 20505 - IGOR 20/05/2008 - ESTAVA CADASTRANDO PRODUTOS NOS ITENS DAS OS*/
				WHERE z.referencia not in(
					SELECT referencia
					FROM tbl_produto
					JOIN tbl_linha using(linha)
					WHERE fabrica = $login_fabrica
					AND (troca_faturada is true or troca_garantia is true)
				)
				ORDER BY z.descricao";
	}  else {
        echo "Produto sem lista b�sica";
        exit;
	}
// 	echo nl2br($sql);
	$res = pg_query($con,$sql);
	if (@pg_num_rows($res) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$descricao' n�o existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o c�digo correto</h2>";
		} else {
			echo "<h1>Pe�a '$descricao' n�o encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',3000);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {
	$referencia = trim(strtoupper($_GET["peca"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>refer�ncia da pe�a</b>: <i>$referencia</i></font>";
	echo "<br><br>";

	//HD 56720 - 16/12/2008
	$produto = str_replace(".","",$produto);
	$produto = str_replace(",","",$produto);
	$produto = str_replace("-","",$produto);
	$produto = str_replace("/","",$produto);
	$produto = str_replace(" ","",$produto);
// echo "->>".$produto;
	if(strlen($produto) > 0){
		$sqlP = "SELECT COUNT(*) AS qtde
				 FROM tbl_lista_basica
				 WHERE tbl_lista_basica.fabrica = $login_fabrica
				 AND produto = $produto";
		$resP = pg_query ($con,$sqlP);
		if(pg_num_rows($resP)>0) $qtde = pg_fetch_result ($resP,0,0);
	}

	if ($qtde > 0 and strlen($produto) > 0) {
		if ($login_fabrica == 1) {
				$peca_ativa = "";
		}else{
			$peca_ativa = "AND   tbl_peca.ativo IS TRUE ";
		}

		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.peca_fora_linha                 ,
						z.de                                    ,
						z.para                                 ,
						z.peca_para                        ,
						z.ativo                                ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.peca_fora_linha    ,
								y.ativo                    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.ativo                                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_peca.ativo
										FROM tbl_peca
										JOIN tbl_lista_basica using(peca)
										WHERE tbl_peca.fabrica = $login_fabrica
										$peca_ativa
										AND   tbl_lista_basica.produto = $produto
										AND   tbl_peca.produto_acabado IS NOT TRUE ";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca $cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				/*ADD CONDI��O WHERE - HD 20505 - IGOR 20/05/2008 - ESTAVA CADASTRANDO PRODUTOS NOS ITENS DAS OS*/
				WHERE z.referencia not in(
					SELECT referencia
					FROM tbl_produto
					JOIN tbl_linha using(linha)
					WHERE fabrica = $login_fabrica
					AND (troca_faturada is true or troca_garantia is true)
				)
				ORDER BY z.descricao";
	} else {

        echo "Produto sem lista b�sica";
        exit;
    }
	$res = @pg_query($con,$sql);
	//echo nl2br($sql);
	if (@pg_num_rows($res) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$referencia' n�o existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o c�digo correto</h2>";
		}else{
			echo "<h1>Pe�a '$referencia' n�o encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',3000);";
		echo "</script>";
		exit;
	}
}
?>

<table width='100%' border='1'>
<?php
$tabela_result = "FALSE";
for ( $i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
	$peca            = trim(@pg_fetch_result($res,$i,peca));
	$peca_referencia = trim(@pg_fetch_result($res,$i,peca_referencia));
	$peca_descricao  = trim(@pg_fetch_result($res,$i,peca_descricao));
	$peca_descricao  = str_replace ('"','',$peca_descricao);
	$peca_fora_linha = trim(@pg_fetch_result($res,$i,peca_fora_linha));
	$peca_para       = trim(@pg_fetch_result($res,$i,peca_para));
	$para            = trim(@pg_fetch_result($res,$i,para));
	$para_descricao  = trim(@pg_fetch_result($res,$i,para_descricao));
	$ativo  = trim(@pg_fetch_result($res,$i,ativo));

	if ($peca_fora_linha && $login_fabrica == 1) {
		$sqlr = "SELECT posto
				 FROM 	tbl_posto_fabrica
				 WHERE 	posto = $login_posto
				 AND   	fabrica = $login_fabrica
				 AND   	reembolso_peca_estoque IS NOT TRUE ";

		$resr = pg_query($con, $sqlr);

		if (pg_num_rows($resr) > 0) {
			$sqlq = "SELECT qtde
					FROM 	tbl_estoque_posto
					WHERE 	tbl_estoque_posto.peca = $peca
					AND   	tbl_estoque_posto.posto = $login_posto
					AND   	tbl_estoque_posto.fabrica = $login_fabrica";

			$resq = pg_query($con, $sqlq);
			if (pg_fetch_result($resq, 0, 'qtde') > 0) {
				$peca_fora_linha = '';
			}
		}
	}

	$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
	if (pg_num_rows($resT) == 1) {
		$tabela = pg_fetch_result ($resT,0,0);
		if (strlen($para) > 0) {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
		}else{
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
		}
		$resT = pg_query($con,$sqlT);
		$preco = (pg_num_rows($resT) == 1) ? number_format (pg_fetch_result($resT,0,0),2,",",".") : "";
	}else{
		$preco = "";
	}

	if ($login_fabrica == 1) {
		if ($ativo == 't') {
			$imprime_tabela = "TRUE";
			$tabela_result = "TRUE";
		}else{
			if (pecaInativaBlack($peca)) {
				$imprime_tabela = "TRUE";
				$tabela_result = "TRUE";
			}else{
				$imprime_tabela = "FALSE";
			}
		}
// echo $imprime_tabela;
		if ($imprime_tabela == 'TRUE') {
?>
			<tr>
                <td nowrap>
                <font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><?=$peca_referencia?></font>
			</td>

            <td nowrap>
<?php
			if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
?>
                <font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><?=$peca_descricao?></font>
<?php
			}else{
				echo "<a href=\"javascript: window.opener.referencia.value='$peca_referencia'; window.opener.descricao.value='$peca_descricao';";
				echo " window.opener.preco.value='$preco';";
				echo " this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
			}
			echo "</td>\n";
			echo "<td nowrap>";
			if (strlen($peca_fora_linha) > 0) {
				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
				echo "� obsoleta,<br>n�o � mais fornecida";
				echo "</b></font>";
			}else{
				if (strlen($para) > 0) {
					echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
					echo " <a href=\"javascript: window.opener.referencia.value='$para'; window.opener.descricao.value='$para_descricao'; window.opener.preco.value='$preco'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$para</font></a>";
				}else{
					echo "&nbsp;";
				}
			}
			echo "</td>\n";
			echo "</tr>\n";
		}

	}else{
		echo "<tr>\n";

		if ($login_fabrica == 14) {
			echo "<td nowrap>";
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
			echo "</td>\n";
		}

		echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
		echo "</td>\n";

		echo "<td nowrap>";
		if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
		}else{
			echo "<a href=\"javascript: window.opener.referencia.value='$peca_referencia'; window.opener.descricao.value='$peca_descricao';";
			echo ($login_fabrica == 14) ? " window.opener.posicao.value='$posicao';" : " window.opener.preco.value='$preco';";
			echo " this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
		}
		echo "</td>\n";

		echo "<td nowrap>";
		if (strlen($peca_fora_linha) > 0) {
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
			echo ($login_fabrica == 1) ? "� obsoleta,<br>n�o � mais fornecida" : "Fora de linha";
			echo "</b></font>";
		}else{
			if (strlen($para) > 0) {
				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
				echo " <a href=\"javascript: window.opener.referencia.value='$para'; window.opener.descricao.value='$para_descricao'; window.opener.preco.value='$preco'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$para</font></a>";
			}else{
				echo "&nbsp;";
			}
		}
		echo "</td>\n";
		echo "</tr>\n";
	}
}

echo "</table>\n";
if ($login_fabrica == 1 AND $tabela_result == "FALSE") {
	echo "<h2>Item '$referencia' n�o existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o c�digo correto</h2>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',3000);";
	echo "</script>";
	exit;
}
?>

</body>
</html>
