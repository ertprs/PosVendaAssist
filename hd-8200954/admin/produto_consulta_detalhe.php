<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$produto = $_GET['produto'];

$title = "Informações agrupadas de Produtos";
if (strlen($produto) > 0) {
	$sql = "SELECT  tbl_produto.produto   ,
					tbl_produto.referencia,
					tbl_produto.descricao ,
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
		$admin                    = trim(pg_result($res,0,login));
	}
}
?>


<script language="JavaScript">

function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_info.referencia;
		janela.referencia= document.frm_info.descricao;
		janela.focus();
	}
}
</script>

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
	font-size: x-small;
	font-weight: bold;
	color:#FFFFFF;
	border:0;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border:0;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	border:0;
	font-weight: normal;
}
</style>

</head>
<? include 'cabecalho.php'; ?>
<form name="frm_info" method="post" action="<? $PHP_SELF ?>">

<table width="700" cellpadding="0" cellspacing="0"  align='center'>

	<tr>
		<td  class="menu_top" colspan="2"><b><font color=#FFFFFF>Pesquisar Informações do Produto</font></b></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td style="text-align: center;">Referência</td>
		<td style="text-align: center;">Descrição</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td nowrap style="text-align: center;" ><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_produto (document.frm_info.referencia,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td nowrap style="text-align: center;" ><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_produto (document.frm_info.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan=2 style="text-align: center;">
		<div id="wrapper" align='center'>
			<a href='<?echo $PHP_SELF;?>?listartudo=1&produto=<? echo $produto; ?>'>CLIQUE AQUI PARA LISTAR AS INFORMAÇÕES DO PRODUTO</a>
		</div>
		</td>
	</tr>

</table>
</form>

<?
$listartudo = $_GET['listartudo'];

if ($listartudo == 1 and strlen($produto) > 0){

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
			<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Produto: <?echo $referencia ." - ". $descricao_produto;?></b></font></td>
		</tr>
	</table>
	</center>
	<br>

	<?
	$sql="select * from tbl_subproduto
			where produto_filho=$produto";
	$res=pg_exec($con,$sql);
	if (pg_numrows($res) > 0){

		if ($os_item_subconjunto == 't' AND strlen($produto) > 0) {
			$sql = "SELECT  tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM    tbl_subproduto
					JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
					WHERE   tbl_subproduto.produto_filho = $produto
					ORDER BY tbl_produto.referencia;";
			$res1 = pg_exec($con,$sql);

			if (pg_numrows($res1) > 0) {

				for($a = 0 ; $a < pg_numrows($res1) ; $a++) {
					$sub_produto    = trim(pg_result($res1,$a,produto));
					$sub_referencia = trim(pg_result($res1,$a,referencia));
					$sub_descricao  = trim(pg_result($res1,$a,descricao));

					echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
					echo "<tr height='20' bgcolor='#666666'>\n";
					echo "<td align='center' colspan='2'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><i>Subconjunto: $sub_referencia - $sub_descricao</i></b></font></td>\n";
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
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
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
					echo "<td align='center' colspan='2'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><i>Subconjunto: $sub_referencia - $sub_descricao</i></b></font></td>\n";
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
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
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
							echo "<td align='center' colspan='2'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto: $sub_referencia - $sub_descricao</b></font></td>\n";
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
								echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>\n";
								echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
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

