<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
$btn_acao = $_POST['btn_acao'];

$msg_erro = "";

if(strlen($btn_acao) > 0) {
	$referencia = trim($_POST['referencia']);

	if(strlen($referencia) > 0) {
		$sql="SELECT produto
				FROM tbl_produto
				JOIN tbl_linha using(linha)
				WHERE tbl_linha.fabrica=$login_fabrica
				AND referencia='$referencia'";
		$res=pg_exec($con,$sql);
		if(pg_numrows($res) > 0) {
			$produto=pg_result($res,0,produto);
		}
	} else {
		$msg_erro = traduz("informe.a.referencia.do.produto",$con,$cook_idioma);
	}

	if (strlen($produto) > 0) {
		$sql = "SELECT  tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_produto.voltagem  ,
						tbl_admin.login       ,
						to_char(tbl_produto.data_atualizacao, 'DD/MM/YYYY HH24:MI') AS data_atualizacao
				FROM    tbl_produto
				JOIN    tbl_linha ON tbl_linha.linha = tbl_produto.linha
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_produto.admin
				WHERE   tbl_linha.fabrica   = $login_fabrica
				AND     tbl_produto.produto = $produto;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$produto                  = trim(pg_result($res,0,produto));
			$referencia               = trim(pg_result($res,0,referencia));
			$descricao                = trim(pg_result($res,0,descricao));
			$voltagem                 = trim(pg_result($res,0,voltagem));
			$admin                    = trim(pg_result($res,0,login));
		}
	}
}

$layout_menu = 'pedido';
$title = traduz ("informacoes.agrupadas.de.produtos",$con,$cook_idioma);
include "cabecalho.php";
include "javascript_pesquisas.php" ?>

<script language='javascript' src='ajax.js'></script>


<style>
.titulo {
	text-align: left;
	border:0;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color: #000000;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#FFFFFF;
	border:0;
	background-color: #596D9B
}
</style>

</head>


<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' bgcolor='#FF0000'>";
	echo "<tr>";
	echo "<td align='center' >";
	echo "	<font face='arial, verdana' color='#FFFFFF' size='2'>";
	echo "<B>".$msg_erro."</B>";
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<form name="frm_info" method="post" action="<? $PHP_SELF ?>">
<br>
<table width="400" cellpadding="0" cellspacing="0"  align='center'>
	<tr>
		<td  class="menu_top" colspan="2"><b><font size='2' color='#ffffff'><? fecho ("pesquisar.informacoes.do.produto",$con,$cook_idioma); ?></font></b></td>

	</tr>
    <tr bgcolor="#D9E2EF"><td colspan=2><BR></td></tr>
	<tr bgcolor="#D9E2EF">
		<td style="text-align: left; font-size: 12px; font-family: Verdana">&nbsp;&nbsp;<? fecho("referencia",$con,$cook_idioma); ?></td>
		<td style="text-align: left; font-size: 12px; font-family: Verdana">&nbsp;&nbsp;<? fecho ("descricao",$con,$cook_idioma); ?></td>
	</tr>

	<tr bgcolor="#D9E2EF">
		<td nowrap style="text-align: left;" >&nbsp;&nbsp;<input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_produto (document.frm_info.referencia,document.frm_info.descricao,'referencia',document.frm_info.voltagem)"><IMG SRC="imagens/btn_lupa_novo.gif" ></a></td>
		<td nowrap style="text-align: left;" >&nbsp;&nbsp;<input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_produto (document.frm_info.referencia,document.frm_info.descricao,'descricao',document.frm_info.voltagem)"><IMG SRC="imagens/btn_lupa_novo.gif" ></a></td>
	</tr>
	<tr bgcolor="#D9E2EF"><td colspan=2><BR></td></tr>
	<tr bgcolor="#D9E2EF">
		<td colspan=2 style="text-align: center;">
		<div id="wrapper" align='center'><input class='frm' type="submit" name="btn_acao" value='<? fecho ("clique.aqui.para.listar.as.informacoes",$con,$cook_idioma); ?>'>
		</div>
		</td>
	</tr>
</table>
</form>
<?

if(strlen($btn_acao) > 0 and strlen($produto) > 0) {

	$sql = "SELECT  tbl_fabrica.os_item_subconjunto
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$os_item_subconjunto = pg_result($res,0,0);
		if (strlen($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
	}

	$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao
			FROM   tbl_produto
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE  tbl_produto.produto = $produto
			AND tbl_linha.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);

	$referencia        = pg_result($res,0,referencia);
	$descricao_produto = pg_result($res,0,descricao);

	?>
	<p>
	<center>
	<table width='700' border='0'  cellspacing='1' cellpadding='0'>
		<tr  height='20' bgcolor='#666666'>
			<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><? fecho ("produto",$con,$cook_idioma);?> : <?echo $referencia ." - ". $descricao_produto;?></b></font></td>
		</tr>
	</table>
	</center>
	<br>

	<?
	$sql="SELECT *
			FROM tbl_subproduto
			WHERE produto_filho=$produto";
	$res=pg_exec($con,$sql);
	if (pg_numrows($res) > 0){
		if ($os_item_subconjunto == 't' AND strlen($produto) > 0) {
			$sql = "SELECT  tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM    tbl_subproduto
					JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
					WHERE   tbl_subproduto.produto_filho = $produto
					GROUP BY tbl_produto.produto,tbl_produto.referencia,tbl_produto.descricao
					ORDER BY tbl_produto.referencia;";
			$res1 = pg_exec($con,$sql);

			if (pg_numrows($res1) > 0) {

				for($a = 0 ; $a < pg_numrows($res1) ; $a++) {
					$sub_produto    = trim(pg_result($res1,$a,produto));
					$sub_referencia = trim(pg_result($res1,$a,referencia));
					$sub_descricao  = trim(pg_result($res1,$a,descricao));

					echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
					echo "<tr height='20' bgcolor='#666666'>\n";
					echo "<td align='center' colspan='2'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><i>";
                    fecho("subconjunto",$con,$cook_idioma);
                    echo ": $sub_referencia - $sub_descricao</i></b></font></td>\n";
					echo "</tr>\n";
					echo "</table>\n\n";
					echo "<br>\n\n";

					$sql =	"SELECT DISTINCT tbl_peca.referencia            ,
									tbl_peca.descricao
							FROM    tbl_lista_basica
							JOIN    tbl_peca USING (peca)
							WHERE   tbl_lista_basica.fabrica = $login_fabrica
							AND     tbl_lista_basica.produto = $sub_produto
							AND tbl_lista_basica.ativo IS NOT FALSE
							ORDER BY tbl_peca.referencia";
					$res3 = pg_exec($con,$sql);

					if (pg_numrows($res3) > 0) {
						echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
						echo "<tr height='20' bgcolor='#666666'>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                        fecho ("peca",$con,$cook_idioma);
                        echo "</b></font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                        fecho ("descricao",$con,$cook_idioma);
                        echo "</b></font></td>\n";
						echo "</tr>\n";

						for ($c = 0 ; $c < pg_numrows($res3) ; $c++) {

							$peca      = pg_result ($res3,$c,referencia);
							$descricao = pg_result ($res3,$c,descricao);

							echo "<tr>\n";
							echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>\n";
							echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>\n";
							echo "</tr>\n";
						}
						echo "</table>\n\n";
						echo "<br>\n\n";
					}
				}
			}
		}
	} else {
		if ($os_item_subconjunto == 't' AND strlen($produto) > 0) {
			$sql = "SELECT  tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM    tbl_subproduto
					JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
					WHERE   tbl_subproduto.produto_pai = $produto
					ORDER BY tbl_produto.referencia;";
			$res1 = pg_exec($con,$sql);

			if (pg_numrows($res1) > 0) {

				for($a = 0 ; $a < pg_numrows($res1) ; $a++) {
					$sub_produto    = trim(pg_result($res1,$a,produto));
					$sub_referencia = trim(pg_result($res1,$a,referencia));
					$sub_descricao  = trim(pg_result($res1,$a,descricao));

					echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
					echo "<tr height='20' bgcolor='#666666'>\n";
					echo "<td align='center' colspan='2'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><i>";
                    fecho ("subconjunto",$con,$cook_idioma);
                    echo ": $sub_referencia - $sub_descricao</i></b></font></td>\n";
					echo "</tr>\n";
					echo "</table>\n\n";
					echo "<br>\n\n";

					$sql =	"SELECT DISTINCT tbl_peca.referencia            ,
									tbl_peca.descricao                      ,
									tbl_lista_basica.posicao
							FROM    tbl_lista_basica
							JOIN    tbl_peca USING (peca)
							WHERE   tbl_lista_basica.fabrica = $login_fabrica
							AND     tbl_lista_basica.produto = $sub_produto
							AND tbl_lista_basica.ativo IS NOT FALSE
							ORDER BY tbl_peca.referencia";
					$res3 = pg_exec($con,$sql);

					if (pg_numrows($res3) > 0) {
						echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
						echo "<tr height='20' bgcolor='#666666'>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                        fecho ("peca",$con,$cook_idioma);
                        echo "</b></font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                         fecho ("descricao",$con,$cook_idioma);
                        echo "</b></font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                         fecho ("posicao",$con,$cook_idioma);
                        echo "</b></font></td>\n";
						echo "</tr>\n";

						for ($c = 0 ; $c < pg_numrows($res3) ; $c++) {

							$peca      = pg_result ($res3,$c,referencia);
							$descricao = pg_result ($res3,$c,descricao);
							$posicao = pg_result ($res3,$c,posicao);

							echo "<tr>\n";
							echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>\n";
							echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>\n";
							echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>\n";
							echo "</tr>\n";
						}
						echo "</table>\n\n";
						echo "<br>\n\n";
					}

					$sql = "SELECT  tbl_produto.produto   ,
									tbl_produto.referencia,
									tbl_produto.descricao
							FROM    tbl_subproduto
							JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
							WHERE   tbl_subproduto.produto_pai = $sub_produto
							ORDER BY tbl_produto.referencia;";
					$res2 = pg_exec($con,$sql);

					if (pg_numrows($res2) > 0) {
						for($b = 0 ; $b < pg_numrows($res2) ; $b++) {
							$sub_produto    = trim(pg_result($res2,$b,produto));
							$sub_referencia = trim(pg_result($res2,$b,referencia));
							$sub_descricao  = trim(pg_result($res2,$b,descricao));

							echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
							echo "<tr height='20' bgcolor='#666666'>\n";
							echo "<td align='center' colspan='2'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                            fecho ("subconjunto",$con,$cook_idioma);
                            echo ": $sub_referencia - $sub_descricao</b></font></td>\n";
							echo "</tr>\n";

							$sql =	"SELECT DISTINCT tbl_peca.referencia            ,
											tbl_peca.descricao
									FROM    tbl_lista_basica
									JOIN    tbl_peca USING (peca)
									WHERE   tbl_lista_basica.fabrica = $login_fabrica
									AND     tbl_lista_basica.produto = $sub_produto
									AND tbl_lista_basica.ativo IS NOT FALSE
									ORDER BY tbl_peca.referencia";
							$res3 = pg_exec($con,$sql);

							if (pg_numrows($res3) > 0) {

								echo "<tr height='20' bgcolor='#666666'>\n";
								echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                                fecho ("peca",$con,$cook_idioma);
                                echo "</b></font></td>\n";
								echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
                                fecho ("descricao",$con,$cook_idioma);
                                echo "</b></font></td>\n";
								echo "</tr>\n";

								for ($c = 0 ; $c < pg_numrows($res3) ; $c++) {

									$peca      = pg_result ($res3,$c,referencia);
									$descricao = pg_result ($res3,$c,descricao);

									echo "<tr>\n";
									echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>\n";
									echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>\n";
									echo "</tr>\n";
								}
							}

							echo "</table>\n\n";
							echo "<br>\n\n";

						} # Fecha 2º FOR
					}

				} # Fecha 1º FOR
			}
		}
	}
}

include "rodape.php";
?>

