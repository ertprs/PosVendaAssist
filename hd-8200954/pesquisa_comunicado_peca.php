<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

/* Rotina para verificação de comunicados por Peça - HD 19052 */

if ($S3_sdk_OK) {
	include_once S3CLASS;
	$s3 = new anexaS3('ve', (int) $login_fabrica);
}

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Comunicados do Peça </title>
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
$referencia = trim($_GET['referencia']);
$comunicado = trim($_GET['comunicado']);

if (strlen($referencia)>0) {

	$sql = "SELECT peca,referencia,descricao
			FROM tbl_peca
			WHERE fabrica = $login_fabrica
			AND referencia = '$referencia' ";
	$res = pg_exec($con,$sql);
	if (pg_numrows ($res) >0 ){
		$peca        = trim(pg_result($res,0,peca));
		$referencia  = trim(pg_result($res,0,referencia));
		$descricao   = trim(pg_result($res,0,descricao));
	}else{
		echo "<h1>Peça <b>'$referencia'</b> não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',1500);";
		echo "</script>";
		exit;
	}

	echo "<center><h2> COMUNICADOS </h2></center>";
	echo "<h4>Pesquisando <b>comunicados</b> para: <i>$referencia - $descricao</i></h4>";
	echo "<p>";
	

	$sql ="SELECT	tbl_comunicado.comunicado,
					tbl_comunicado.tipo,
					tbl_comunicado.extensao,
					tbl_comunicado.mensagem,
					to_char(tbl_comunicado.data,'DD/MM/YYYY') AS data
			FROM  tbl_comunicado 
			LEFT JOIN tbl_comunicado_peca USING(comunicado)
			WHERE tbl_comunicado.fabrica = $login_fabrica
			AND ( tbl_comunicado.peca = $peca OR tbl_comunicado_peca.peca = $peca )
			AND tbl_comunicado.ativo IS TRUE 
			AND ( tbl_comunicado.posto = $login_posto OR tbl_comunicado.posto IS NULL)
			ORDER BY tbl_comunicado.data DESC";

	$res = pg_exec($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Comunicados para o produto <b>'$descricao'</b> não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

	$num = pg_numrows ($res);

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
		$comunicado		= trim(pg_result($res,$i,comunicado));
		$tipo			= trim(pg_result($res,$i,tipo));
		$extensao		= trim(pg_result($res,$i,extensao));
		$mensagem		= trim(pg_result($res,$i,mensagem));
		$data			= trim(pg_result($res,$i,data));

		$cor = '#ffffff';
		if ($i % 2 <> 0) $cor = '#EEEEEE';

		if ($S3_online) {
			$tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
			if ($s3->tipo_anexo != $tipo_s3)
				$s3->set_tipo_anexoS3($tipo_s3);
			$s3->temAnexos($comunicado);

			if ($s3->temAnexo) {
				$com_file = $s3->url;
				$Xcomunicado = "<a href='$com_file' target='_blank'>";
			}

		} else {

			$gif = "comunicados/$comunicado.gif";
			$jpg = "comunicados/$comunicado.jpg";
			$pdf = "comunicados/$comunicado.pdf";
			$doc = "comunicados/$comunicado.doc";
			$rtf = "comunicados/$comunicado.rtf";
			$xls = "comunicados/$comunicado.xls";
			$ppt = "comunicados/$comunicado.ppt";
			$zip = "comunicados/$comunicado.zip";

			if (file_exists($gif) == true) $Xcomunicado= "<a href='comunicados/$comunicado.gif' target='_blank'>";
			if (file_exists($jpg) == true) $Xcomunicado= "<a href='comunicados/$comunicado.jpg' target='_blank'>";
			if (file_exists($cod) == true) $Xcomunicado= "<a href='comunicados/$comunicado.cod' target='_blank'>";
			if (file_exists($xls) == true) $Xcomunicado= "<a href='comunicados/$comunicado.xls' target='_blank'>";
			if (file_exists($rtf) == true) $Xcomunicado= "<a href='comunicados/$comunicado.rtf' target='_blank'>";
			if (file_exists($xls) == true) $Xcomunicado= "<a href='comunicados/$comunicado.xls' target='_blank'>";
			if (file_exists($pdf) == true) $Xcomunicado= "<a href='comunicados/$comunicado.pdf' target='_blank'>";
			if (file_exists($ppt) == true) $Xcomunicado= "<a href='comunicados/$comunicado.ppt' target='_blank'>";
			if (file_exists($zip) == true) $Xcomunicado= "<a href='comunicados/$comunicado.zip' target='_blank'>";
		}
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
	echo "<br><center><a href='javascript:this.close()'>Fechar Janela</a></center>";

}else {
	echo "<h1>Nenhuma peça selecionada!</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
}

?>

</body>
</html>
