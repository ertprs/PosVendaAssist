<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_usuario.php';
$title = "Menu Assistencia Técnica";
$layout_menu = 'tecnica';
?>

<style>
body{
	margin: 0px;
	padding: 0px;
	color: #727272;
	font-weight: normal;
	background-color: #FFFFFF;
	font-size: 12px;
	font-family: Arial, Helvetica, sans-serif;
}
td {	
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
}

img{
	border: none;
}

A:link, A:visited { TEXT-DECORATION: none;  color: #727272;}

A:hover { TEXT-DECORATION: underline;color: #33CCFF; }
.fundo {
	background-image: url(http://img.terra.com.br/i/terramagazine/fundo.jpg);
	background-repeat: repeat-x;
}
.chapeu {
	color: #0099FF;
	padding: 2px;
	margin-bottom: 4px;
	margin-top: 10px;
	background-image: url(http://img.terra.com.br/i/terramagazine/tracejado3.gif);
	background-repeat: repeat-x;
	background-position: bottom;
	font-size: 13px;
	font-weight: bold;
}

.menu {
	font-size: 11px;
}

hr{ 
	height: 1px;
	margin: 15px 0;
	padding: 0;
	border: 0 none;
	background: #ccc;
}

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 13px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 13px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 13px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}
.rodape{
	color: #FFFFFF;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 9px;
	background-color: #FF9900;
	font-weight: bold;
}
.detalhes{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #333399;
}

</style>
<?
include "menu.php";
?>

<!-- ================================================================== -->
<!-- 
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='http://www.telecontrol.com.br/assist/comunicado_mostra.php?tipo=Esquema+El%E9trico' class='menu'>Produtos</a></td>
	<td nowrap class='descricao'>Guia do usuário / caracteristicas técnicas dos produtos</td>
</tr>
 -->
<!-- ================================================================== -->

<!--


-->

<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#efefef'>
		<td rowspan='3' width='20' valign='top'><img src='../imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >Vista Explodida</td>
	</tr>
	<tr bgcolor = '#efefef'><td colspan='2' height='5'></td></tr>
	<tr bgcolor = '#efefef'>
		<td valign='top' class='menu'>
<?
$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
						tbl_familia.descricao                                ,
						tbl_linha.linha                                      ,
						tbl_linha.nome                                       
		FROM    tbl_comunicado 
		JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha       
		LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia 
		WHERE   tbl_linha.fabrica    = $login_fabrica
		AND     tbl_comunicado.ativo IS TRUE
		AND     tbl_comunicado.tipo = 'Vista Explodida'
		AND     tbl_comunicado.produto IS NOT NULL
	UNION
	SELECT DISTINCT null::int4 AS familia                                      ,
					null::text AS descricao                                    ,
					tbl_linha.linha                                      ,
					tbl_linha.nome                                       
		FROM    tbl_comunicado 
		JOIN    tbl_linha ON tbl_comunicado.linha   = tbl_linha.linha
		WHERE   tbl_linha.fabrica    = $login_fabrica
		AND     tbl_comunicado.ativo IS TRUE
		AND     tbl_comunicado.tipo = 'Vista Explodida'
		AND     tbl_comunicado.produto IS NULL
	ORDER BY nome, descricao";
//echo nl2br($sql);
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$linha_anterior = "";
	echo "<dl>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$descricao  = trim(pg_result ($res,$i,descricao));
		$familia    = trim(pg_result ($res,$i,familia))  ;
		$nome       = trim(pg_result ($res,$i,nome))     ;
		$linha      = trim(pg_result ($res,$i,linha))    ;
//		echo "<br>Linha Atual: $linha";
//		echo "<br>Linha Anterior: $linha_anterior";

		if($linha_anterior <> $linha) {
						echo "<br><dt>&nbsp;&nbsp;<b>»</b> <a href='produto_visualiza.php?tipo=Vista+Explodida&linha=$linha'>$nome Linha</a><br></dt>";
		}
		if($login_fabrica<>19){
			echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='produto_visualiza.php?tipo=Vista+Explodida&linha=$linha&familia=$familia'>$descricao</a><br></dd>";
		}
		$linha_anterior = $linha;
//		echo "Linha Aterior recebe: $linha_anterior";
	}
}

?>
<br>
		</td>
		<td rowspan='2'class='detalhes' width='350'>Escolha ao lado a família do produto que deseja consultar a Vista Explodida.</td>
	</tr>
</table>
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#fafafa'>
		<td rowspan='3' width='20' valign='top'><img src='../imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >Esquema Elétrico</td>
	</tr>
	<tr bgcolor = '#fafafa'><td colspan='2' height='5'></td></tr>
	<tr bgcolor = '#fafafa'>
		<td valign='top' class='menu'>
<?
if($login_fabrica===19){
	$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome                                       
			FROM    tbl_comunicado 
			JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
			JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha       
			LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
			WHERE   tbl_linha.fabrica    = $login_fabrica
			AND     tbl_comunicado.ativo IS TRUE
			AND     tbl_comunicado.tipo = 'Esquema Elétrico'
		ORDER BY tbl_linha.nome, tbl_familia.descricao";
	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$linha_anterior = "";
		echo "<dl>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$descricao  = trim(pg_result ($res,$i,descricao));
			$familia    = trim(pg_result ($res,$i,familia))  ;
			$nome       = trim(pg_result ($res,$i,nome))     ;
			$linha      = trim(pg_result ($res,$i,linha))    ;
	//		echo "<br>Linha Atual: $linha";
	//		echo "<br>Linha Anterior: $linha_anterior";
	
			if($linha_anterior <> $linha) {
							echo "<br><dt>&nbsp;&nbsp;<b>»</b> <a href='produto_visualiza.php?tipo=Esquema Elétrico&linha=$linha'>$nome</a><br></dt>";
			}
			echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='produto_visualiza.php?tipo=Vista+Explodida&linha=$linha&familia=$familia'>$descricao</a><br></dd>";
			$linha_anterior = $linha;
	//		echo "Linha Aterior recebe: $linha_anterior";
		}
	}
}else{
	echo "<br><dt>&nbsp;&nbsp;<b>»</b> <a href='produto_visualiza.php?tipo=Esquema Elétrico'>Esquema Elétrico</a><br></dt>";
}
?>
<br>
		</td>
		<td rowspan='2'class='detalhes' width='350'>Escolha ao lado a família do produto que deseja consultar o Esquema Elétrico.</td>
	</tr>
	<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='../imagens/spacer.gif' height='3'></td>
</tr>
</table><br>


</body>
</html>