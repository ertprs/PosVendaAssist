<?
$title = "Menu Assistencia Técnica";
$layout_menu = 'tecnica';

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
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
	color:#FFFFFF;
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

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 12px Arial;
    color: #FFFFFF;
}

.subtitulo a:link{ color:#FFFFFF; text-decoration:underline;}
.subtitulo a:hover{ color:#000;}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:left;
}


.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<?
$sql_manual = "SELECT tbl_comunicado.tipo,
				count(tbl_comunicado.*) AS qtde
				FROM tbl_comunicado
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
				LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
				LEFT JOIN tbl_produto prod ON prod.produto = tbl_comunicado_produto.produto
				WHERE tbl_comunicado.fabrica = $login_fabrica
				AND tbl_comunicado.ativo IS TRUE 
				AND tbl_comunicado.tipo = 'Manual de Serviço'
				 GROUP BY tbl_comunicado.tipo ORDER BY tbl_comunicado.tipo";
$res_manual = @pg_exec ($con,$sql_manual);
$qtd_manual = @pg_result ($res_manual,0,qtde);
?>

<div class='texto_avulso'>
	Escolha a família que deseja consultar.
</div>
<br />
<table width="700" class='formulario' border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr class='titulo_tabela'>
		<td colspan='2' ><center>
		Manual de Serviço - <? echo $qtd_manual;?> Manuais Disponibilizados</center>
		</td>
	</tr>
	<tr>
		<td class='subtitulo' colspan='100%' align='center'>
		<a href='info_tecnica_comunicado_download_posto.php'>Relatório Posto Autorizado</a>
		</td>
	</tr>
	<tr>
		<td valign='top' align=left class='menu'>

<?
$sql = "
	SELECT
	DISTINCT tbl_familia.familia,
	tbl_familia.descricao,
	tbl_linha.linha,
	tbl_linha.nome                                       

	FROM
	tbl_comunicado 
	JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
	JOIN tbl_linha   ON tbl_produto.linha   = tbl_linha.linha       
	LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia 

	WHERE
	tbl_linha.fabrica    = $login_fabrica
	AND tbl_comunicado.ativo IS NOT FALSE
	AND ((tbl_comunicado.posto = 6359) OR (tbl_comunicado.posto IS NULL)) AND tbl_comunicado.tipo = 'Manual de Serviço'
	AND tbl_comunicado.produto IS NOT NULL

UNION

	SELECT
	DISTINCT
	null::int4 AS familia,
	null::text AS descricao,
	tbl_linha.linha,
	tbl_linha.nome

	FROM 
	tbl_comunicado 
	JOIN tbl_linha ON tbl_comunicado.linha   = tbl_linha.linha

	WHERE 
	tbl_linha.fabrica    = $login_fabrica
	AND tbl_comunicado.ativo IS NOT FALSE
	AND ((tbl_comunicado.posto = 6359) OR (tbl_comunicado.posto IS NULL)) AND tbl_comunicado.tipo = 'Manual de Serviço'
	AND tbl_comunicado.produto IS NULL

UNION

	SELECT
	DISTINCT tbl_familia.familia,
	tbl_familia.descricao,
	tbl_linha.linha,
	tbl_linha.nome

	FROM
	tbl_comunicado 
	JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
	JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
	JOIN tbl_linha   ON tbl_produto.linha   = tbl_linha.linha       
	LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia 

	WHERE
	tbl_linha.fabrica = $login_fabrica
	AND tbl_comunicado.ativo IS NOT FALSE
	AND ((tbl_comunicado.posto = 6359) OR (tbl_comunicado.posto IS NULL))
	AND tbl_comunicado.tipo = 'Manual de Serviço'
	AND tbl_comunicado.produto IS NULL

ORDER BY
nome,
descricao
";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0)
{
	$linha_anterior = "";
	echo "<dl>";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++)
	{
		$descricao  = trim(pg_result ($res,$i,descricao));
		$familia    = trim(pg_result ($res,$i,familia));
		$nome       = trim(pg_result ($res,$i,nome));
		$linha      = trim(pg_result ($res,$i,linha));

		if($linha_anterior <> $linha)
		{
			echo "<br><dt class='titulo_tabela'>&nbsp;&nbsp;<a href='info_tecnica_comunicado_download.php?tipo=Manual de Serviço&linha=$linha' style='color:#FFFFFF;'>";
			echo "$nome";
			echo "</a><br></dt>";
		}

		if($login_fabrica<>19 and $descricao !="")
		{
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<dd style='background:".$cor."; width:620px; height:20px; border-collapse: collapse;border:1px solid #596d9b; padding-top:5px; border-top:0;'>
			&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_comunicado_download.php?tipo=Manual de Serviço&linha=$linha&familia=$familia'>$descricao</a><br></dd>";
		}

		$linha_anterior = $linha;
	}

	echo "<dt><dd>&nbsp;</dd></dt>";
}

?>
<br>
		</td>
		
	</tr>
</table>
<? include "rodape.php"; ?>
</body>
</html>