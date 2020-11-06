<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$title = "Menu Assistencia Técnica";
$layout_menu = 'tecnica';



if ($login_fabrica == 42 ) {




header("Location: comunicado_mostra.php?tipo=Vista Explodida");



}


$sql = "SELECT tbl_posto_fabrica.codigo_posto,
               tbl_posto_fabrica.tipo_posto
          FROM tbl_posto
     LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
         WHERE tbl_posto_fabrica.fabrica = $login_fabrica
           AND tbl_posto.posto   = $login_posto ";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	$tipo_posto = trim(pg_result($res,0,'tipo_posto'));
}

include "cabecalho.php";?>

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
	<?
	$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome                                       ,
							tbl_comunicado.tipo
			FROM    tbl_comunicado
			JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto AND tbl_produto.fabrica_i = $login_fabrica
			JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
			WHERE   tbl_comunicado.fabrica    = $login_fabrica
			AND     tbl_comunicado.ativo IS NOT FALSE
			AND     tbl_comunicado.produto IS NOT NULL
		UNION
		SELECT DISTINCT null::int4 AS familia                                      ,
						null::text AS descricao                                    ,
						tbl_linha.linha                                            ,
						tbl_linha.nome                                             ,
						tbl_comunicado.tipo
			FROM    tbl_comunicado
			JOIN    tbl_linha ON tbl_comunicado.linha   = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
			WHERE   tbl_comunicado.fabrica    = $login_fabrica
			AND     tbl_comunicado.ativo IS NOT FALSE
			AND     tbl_comunicado.produto IS NULL
			AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
			AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
		UNION
			SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome                                       ,
							tbl_comunicado.tipo
			FROM    tbl_comunicado
			JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
			WHERE   tbl_comunicado.fabrica    = $login_fabrica
			AND     tbl_comunicado.ativo IS NOT FALSE
			AND     tbl_comunicado.produto IS NULL
		ORDER BY tipo,nome, descricao";
	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	$familia_anterior = "";
	$tipo_anterior  = "";

	if (pg_numrows($res) > 0) {
		for ($i = 0; $i < pg_numrows($res); $i++) {
			$tipo      = trim(pg_result($res,$i,'tipo'));
			$descricao = trim(pg_result($res,$i,'descricao'));
			$familia   = trim(pg_result($res,$i,'familia'));
			$nome      = trim(pg_result($res,$i,'nome'));
			$linha     = trim(pg_result($res,$i,'linha'));

			if($tipo <> $tipo_anterior){
				$tipo_anterior  = $tipo; 
				$familia_anterior = "";
				?>
				<tr bgcolor = '#F7F5F0'>
					<td  width='20' valign='top'><img src='imagens/marca25.gif'></td>
					<td  class="chapeu" colspan='2'>
						<?php echo "$tipo_anterior"; ?>
					</td>
				</tr>
			<? } ?>
			<tr bgcolor="#F1F4FA">
			<td valign='top' class='menu'>
				<?if ($familia_anterior <> $familia) {
					$familia_anterior = $familia;
					echo "<td style='padding-left:30px;font:bold 12px Arial;'><a name='$descricao'>»</a> ";
					echo "<a href='info_tecnica_visualiza.php?tipo=$tipo&familia=$familia'>";
				echo "$descricao";
				echo "</a><br />";
				}
			echo "</td>";
			echo "</tr>";
		}
	}
?>
<table>
