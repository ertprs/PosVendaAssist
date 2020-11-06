<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_pecas.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.cookie.js" type="text/javascript"></script>
<script src="js/jquery.treeview.js" type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen">
<?
	function existe_foto($dir, $nome) { //BEGIN function existe_foto
//  $dir: diretório onde deveria estar o arquivo
//  $nome:nome do arquivo, SEM EXTENSÃO

    $a_exts = explode(",","jpg,gif,bmp,png,jpeg,JPG,GIF,BMP,PNG,JPEG");
    foreach ($a_exts as $ext) {
    	if (file_exists($dir.$nome.".".$ext)) {
            return $nome.".".$ext;
        }
    }
    return false;
} // END function existe_foto
?>

</head>

<body style="margin: 0px 0px 0px 0px;" >

<br>

<?
$erro    = "";
$campo   = $_GET["campo"];
$tipo    = $_GET["tipo"];
$peca    = $_GET["peca"];
$produto_fornecedor    = $_GET["produto_fornecedor"];

		$peca_pesquisa = str_replace (".","",$campo);
		$peca_pesquisa = str_replace ("-","",$peca_pesquisa);
		$peca_pesquisa = str_replace ("/","",$peca_pesquisa);
		$peca_pesquisa = str_replace (" ","",$peca_pesquisa);

if (strlen($erro) == 0) {

	$sql = "SELECT distinct tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
				FROM   tbl_peca
				JOIN   tbl_lista_basica on tbl_lista_basica.peca = tbl_peca.peca
				JOIN   tbl_produto      on tbl_lista_basica.produto = tbl_produto.produto
				JOIN   tbl_produto_fornecedor on tbl_produto_fornecedor.produto_fornecedor = tbl_produto.produto_fornecedor and tbl_produto_fornecedor.produto_fornecedor IN($produto_fornecedor) 
				WHERE  tbl_peca.referencia like '%$peca_pesquisa%' and tbl_peca.fabrica = $login_fabrica;";
		

$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0) {
		echo "<h1>Peça '$campo' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

	/*if (@pg_numrows($res) == 1) {
		$peca       = trim(pg_result($res,0,peca));
		$referencia = trim(pg_result($res,0,referencia));
		$descricao  = htmlentities(trim(pg_result($res,0,descricao)));
		echo "<script language='JavaScript'>";
		//echo "peca.value='$peca'; referencia.value='$referencia'; descricao.value='$descricao'; this.close();";
		echo "referencia.value='$referencia'; this.close();";
		echo "</script>";
		exit;
	}*/
	$localdir= "/var/www/assist/www/imagens_pecas/$login_fabrica/pequena/";
	$dir     = "/assist/imagens_pecas/$login_fabrica/pequena/";

	if (@pg_numrows($res) > 0) {
		echo "<table width='100%' border='0'>\n";
		for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
			$peca       = trim(pg_result($res,$i,peca));
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = htmlentities(trim(pg_result($res,$i,descricao)));
			echo "<tr>\n";
			echo "<td>\n";
			echo "<font face='Verdana, Tahoma, Arial' size='-1' color='#000000'>";
			echo "<a href=\"javascript: referencia.value='$referencia'; peca.value='$peca'; this.close();\">";
			echo $referencia;
			echo "</a>\n";
			echo "</font>\n";
			echo "</td>\n";
			echo "<td>\n";
			echo "<font face='Verdana, Tahoma, Arial' size='-1' color='#000000'>";
			echo "<a href=\"javascript: referencia.value='$referencia'; peca.value='$peca'; this.close();\">";
			echo $descricao;
			echo "</a>\n";
			echo "</font>\n";
			echo "</td>\n";
			if (false !== ($filename_final = existe_foto($localdir, $peca))) {
				list($width, $height) = getimagesize($localdir.$filename_final);
				$limita = ($height>100)?" height='80'":"";
				echo "<td align='center'><a href='".str_replace("pequena", "media", $dir.$filename_final)."' title='$descricao' class='thickbox'><img src='$dir$filename_final' border='0'$limita></a><input type='hidden' name='peca_imagem' value='$dir$filename_final'>\n</td>\n";
			}else{
				echo "<td align='center'><img src='../imagens_pecas/semimagem.jpg' border='0'></td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
}else{
	echo "<table width='100%' border='0' align='center' class='error'>\n";
	echo "<tr>\n";
	echo "<td align='center'>" . $erro . "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}
?>

</body>
</html>
