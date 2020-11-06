<div style="background:transparent;position: relative; height: 500px;width:100%;overflow:auto">
<div style="float:left;width:97%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
</div>
<div style="float:right;width:3%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage('Rodrigo Pedroso')" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
</div>
<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Comunicados do Produto </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<style>
body {
	text-align: center;
	font-family:Arial;
	font-size:12;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}
.titulo_tabela{
	color:#fff;
	font-weight:bold;
}

.tabela{
	font-size:12px;
}


</style>

</head>

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>


<br>

<?
$produto = trim($_GET['produto']);
$descricao = trim ($_GET['descricao']);
$comunicado= trim ($_GET['comunicado']);
if (strlen($produto)>0) {
?>
<div style="float:left;color:#596d9b;width:100%;background:;height:27px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
<?php
	echo "<center>COMUNICADOS</center>";
	echo "<h4>Pesquisando <b>comunicados</b> para: <i>$descricao</i></h4>";
?>
</div>
<?php
	echo "<p>";


	$sql ="SELECT tbl_comunicado.comunicado,
			tbl_comunicado.tipo,
			 tbl_comunicado.extensao,
			 tbl_comunicado.mensagem,
			to_char(tbl_comunicado.data,'DD/MM/YYYY') AS data
		FROM  tbl_comunicado JOIN tbl_produto USING(produto)

		WHERE tbl_produto.referencia = '$produto'";
		if(strlen($comunicado) > 0) {
		$sql.=" AND (tbl_comunicado.tipo='Comunicado' OR tbl_comunicado.tipo='Informativo tecnico')";
		}

		$sql.=" AND tbl_comunicado.fabrica = $login_fabrica
		AND tbl_comunicado.ativo IS TRUE
		OR  (tbl_comunicado.tipo = 'Com. Unico Posto' AND tbl_comunicado.posto = $login_posto AND tbl_comunicado.fabrica = $login_fabrica)
		ORDER BY tbl_comunicado.data DESC;";
		#echo nl2br($sql);
	$res = pg_exec($con,$sql);

	$num = pg_numrows ($res);

//if($ip=="201.26.18.238") echo $sql;
//OR  (tbl_comunicado.tipo = 'Com. Unico Posto' AND tbl_comunicado.posto = $login_posto) coloquei, pois, estava aparecendo de todos os postos. Takashi 11-06-07 HD2734
	if (@pg_numrows ($res) == 0 AND ($login_fabrica==20 or $login_fabrica ==45)) { //HD 100834
		$sqlx = "SELECT tbl_comunicado.comunicado,
						tbl_comunicado.tipo,
						tbl_comunicado.extensao,
						tbl_comunicado.mensagem,
						to_char(tbl_comunicado.data,'DD/MM/YYYY') AS data
				FROM tbl_comunicado
				JOIN tbl_comunicado_produto USING(comunicado)
				JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
				WHERE tbl_produto.referencia = '$produto' ";

				if(strlen($comunicado) > 0) {
				$sqlx.=" AND (tbl_comunicado.tipo='Comunicado' OR tbl_comunicado.tipo='Informativo tecnico')";
				}

				$sqlx.=" AND tbl_comunicado.fabrica = $login_fabrica
				AND tbl_comunicado.ativo IS TRUE
				ORDER BY tbl_comunicado.data DESC;";
				#echo nl2br($sqlx);
		$resx = pg_exec($con, $sqlx);

		if(@pg_numrows ($resx) == 0 ){
			?>
			<div style="float:left;color:#596d9b;width:100%;background:;height:27px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
			<?php
			echo "<h1>Comunicados para o produto <b>'$descricao'</b> não encontrado</h1>";
			?>
			</div>
			<?php
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}else{
			$num = pg_numrows ($resx);
			$res = $resx;
		}
	}else if(@pg_numrows ($res) == 0){
		?>
		<div style="float:left;color:#596d9b;width:100%;background:;height:27px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
		<?php
		echo "<h1>Comunicados para o produto <b>'$descricao'</b> não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		?>
		</div>
		<?php
		exit;
	}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0' class='tabela'>\n";

	echo "<tr bgcolor='#596D9B' class='titulo_tabela'>\n";

	echo "<td>\n";
	echo "Data\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "Nº Comunicado\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "Tipo\n";
	echo "</td>\n";

	echo "<td>\n";
	echo "Descrição\n";
	echo "</td>\n";

	echo "</tr>\n";

	for ( $i = 0 ; $i < $num ; $i++ ) {
		$comunicado	= trim(pg_result($res,$i,comunicado));
		$tipo			= trim(pg_result($res,$i,tipo));
		$extensao		= trim(pg_result($res,$i,extensao));
		$mensagem	= trim(pg_result($res,$i,mensagem));
		$data		= trim(pg_result($res,$i,data));

		$cor = ($i % 2 <> 0) ? '#ffffff' : '#EEEEEE';

		$gif = "comunicados/$comunicado.gif";
		$jpg = "comunicados/$comunicado.jpg";
		$pdf = "comunicados/$comunicado.pdf";
		$doc = "comunicados/$comunicado.doc";
		$rtf = "comunicados/$comunicado.rtf";
		$xls = "comunicados/$comunicado.xls";
		$ppt = "comunicados/$comunicado.ppt";
		$zip = "comunicados/$comunicado.zip";

		if (file_exists($gif) == true) $Xcomunicado= "<a href='comunicados/$comunicado.gif' target='_blank'>";
		if (file_exists($jpg) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.jpg' target='_blank'>";
		if (file_exists($cod) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.cod' target='_blank'>";
		if (file_exists($xls) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.xls' target='_blank'>";
		if (file_exists($rtf) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.rtf' target='_blank'>";
		if (file_exists($xls) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.xls' target='_blank'>";
		if (file_exists($pdf) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.pdf' target='_blank'>";
		if (file_exists($ppt) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.ppt' target='_blank'>";
		if (file_exists($zip) == true) $Xcomunicado=  "<a href='comunicados/$comunicado.zip' target='_blank'>";



		echo "<tr bgcolor='$cor'>\n";

		echo "<td>\n";
		echo "$data\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "$Xcomunicado $comunicado</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "$tipo\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "$mensagem\n";
		echo "</td>\n";

		echo "</tr>\n";

	}
	echo "</table>\n";
}
else {
	echo "<h1>Nenhum produto selecionado!</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
}

?>

</body>
</html>
</div>