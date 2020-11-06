<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "cabecalho.php";
include "javascript_pesquisas.php";
$title = "Menu Assistencia Técnica";
$layout_menu = 'tecnica';

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

//--===============================================================--\\

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
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #D9E2EF;
}

</style>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
<form name="frm_comunicado" method="get" action="info_tecnica_visualiza_manual.php">
<input type="hidden" name="acao">
<input type="hidden" name="tipo" value="Manual de Serviço">
	<tr class="Titulo">
		<td colspan="5" align="center"><? echo mb_strtoupper(traduz("selecione.os.parametros.para.a.pesquisa",$con,$cook_idioma));?></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" height=50>
		<td>&nbsp;</td>
		<td align="center" nowrap>
			<? fecho("referencia",$con,$cook_idioma);?><br>
			<input type="text" name="produto_referencia" size="20" class="frm">
		<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)" alt="<? fecho("clique.aqui.para.pesquisar.pela.referencia.do.produto",$con,$cook_idioma);?>" style="cursor: hand;">
		</td>
		<td align="center" nowrap>
			<? fecho("descricao",$con,$cook_idioma);?><br>
			<input type="text" name="produto_descricao" size="60" class="frm">
			<input type="hidden" name="produto_voltagem">
			<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)" alt="<? fecho("clique.aqui.para.pesquisar.pela.descricao.do.produto",$con,$cook_idioma);?>" style="cursor: hand;">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF" align="center">
		<td colspan="6"><img src="<? if ($sistema_lingua == "ES") echo "admin_es/imagens_admin/btn_pesquisar_400.gif";else  echo "imagens/btn_pesquisar_400.gif"?>" onClick="document.frm_comunicado.acao.value='PESQUISAR'; document.frm_comunicado.submit();" style="cursor: hand;" alt="<?fecho("preencha.as.opcoes.e.clique.aqui.para.pesquisar",$con,$cook_idioma);?>"></td>
	</tr>
</form>
</table>

<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#efefef'>
		<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >
		Manual de Serviço
		</td>
	</tr>
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
		AND     tbl_comunicado.ativo IS NOT FALSE
		AND ((tbl_comunicado.posto = 6359) OR (tbl_comunicado.posto IS NULL)) AND tbl_comunicado.tipo = 'Manual de Serviço'
		AND     tbl_comunicado.produto IS NOT NULL
	UNION
	SELECT DISTINCT null::int4 AS familia                                      ,
					null::text AS descricao                                    ,
					tbl_linha.linha                                      ,
					tbl_linha.nome                                       
		FROM    tbl_comunicado 
		JOIN    tbl_linha ON tbl_comunicado.linha   = tbl_linha.linha
		WHERE   tbl_linha.fabrica    = $login_fabrica
		AND     tbl_comunicado.ativo IS NOT FALSE
		AND ((tbl_comunicado.posto = 6359) OR (tbl_comunicado.posto IS NULL)) AND tbl_comunicado.tipo = 'Manual de Serviço'
		AND     tbl_comunicado.produto IS NULL
		AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
	UNION 
		SELECT DISTINCT tbl_familia.familia                                  ,
						tbl_familia.descricao                                ,
						tbl_linha.linha                                      ,
						tbl_linha.nome                                       
		FROM    tbl_comunicado 
		JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
		JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
		JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha       
		LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia 
		WHERE   tbl_linha.fabrica    = $login_fabrica
		AND     tbl_comunicado.ativo IS NOT FALSE
		AND ((tbl_comunicado.posto = 6359) OR (tbl_comunicado.posto IS NULL)) AND tbl_comunicado.tipo = 'Manual de Serviço'
		AND     tbl_comunicado.produto IS NULL
	ORDER BY nome, descricao";
//echo nl2br($sql);
$res = pg_exec ($con,$sql);
$contador_res = pg_numrows($res);

if ($contador_res > 0) {
	$linha_anterior = "";
	echo "<dl>";
	for ($i = 0 ; $i < $contador_res; $i++) {
		$descricao  = trim(pg_result ($res,$i,descricao));
		$familia    = trim(pg_result ($res,$i,familia))  ;
		$nome       = trim(pg_result ($res,$i,nome))     ;
		$linha      = trim(pg_result ($res,$i,linha))    ;

		if($linha_anterior <> $linha) {
						echo "<br><dt>&nbsp;&nbsp;<b><a name='$nome'>»</a></b> <a href='info_tecnica_visualiza_manual.php?tipo=Manual de Serviço&linha=$linha'>";
						echo "";
						echo "$nome";
						echo "</a><br></dt>";
		}
		if($login_fabrica<>19){
			echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza_manual.php?tipo=Manual de Serviço&linha=$linha&familia=$familia'>$descricao</a><br></dd>";
		}
		$linha_anterior = $linha;

	}
}

?>
<br>
		</td>
		<td rowspan='2'class='detalhes' width='150'>Escolha a família que deseja consultar.</td>
	</tr>
</table>


</body>
</html>