<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$title = "Produtos";
$layout_menu = 'produtos';

//--==================== TIPO POSTO ====================--\\
$sql = "SELECT tbl_posto_fabrica.codigo_posto        ,
				tbl_posto_fabrica.tipo_posto       
		FROM	tbl_posto
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_posto.posto   = $login_posto ";
$res= pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	$tipo_posto            = trim(pg_result($res,0,tipo_posto));
}

$tipo = $_GET['tipo'];

if(empty($tipo)) $tipo = 'Produtos';

$xtipo = utf8_decode($tipo);
//--====================LISTA DE PREÇOS ====================--\\
$sql = "SELECT count(comunicado)AS total_produtos 
		FROM tbl_comunicado 
		WHERE ativo IS TRUE 
		AND tipo = '$tipo'
		AND (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND fabrica    = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
	$total_produtos  = trim(pg_result ($res,0,total_produtos));
}


include "cabecalho.php";
?>
<style>

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



<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#efefef'>
		<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >Produtos - Tabela de Preços</td>
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
		AND     tbl_comunicado.tipo in ('$tipo', '$xtipo')
		AND     tbl_comunicado.produto IS NOT NULL
		AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
	UNION
	SELECT DISTINCT null::int4 AS familia                                      ,
					null::text AS descricao                                    ,
					tbl_linha.linha                                      ,
					tbl_linha.nome                                       
		FROM    tbl_comunicado 
		JOIN    tbl_linha ON tbl_comunicado.linha   = tbl_linha.linha
		WHERE   tbl_linha.fabrica    = $login_fabrica
		AND     tbl_comunicado.ativo IS TRUE
		AND     tbl_comunicado.tipo in ('$tipo', '$xtipo')
		AND     tbl_comunicado.produto IS NULL
		AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
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

		if($linha_anterior <> $linha) {
						echo "<br><dt>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=$tipo&linha=$linha'>$nome </a><br></dt>";
		}
		if($login_fabrica<>19){
			echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=$tipo&linha=$linha&familia=$familia'>$descricao</a><br></dd>";
		}
		$linha_anterior = $linha;

	}
}else{ echo "<br><dt>&nbsp;&nbsp;<b>»</b> Nenhum Cadastrado<br></dt>";}

?>
<br>
		</td>
		<td rowspan='2'class='detalhes' width='350'>Escolha ao lado a linha do produto que deseja consultar.</td>
	</tr>
</table>
<br>


</body>
</html>
