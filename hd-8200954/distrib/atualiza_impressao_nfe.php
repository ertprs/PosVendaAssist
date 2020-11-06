<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Atualiza Impressão de NFE... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<?
$chave = $_GET['chave'];
$todos = $_GET['todos'];
if(strlen($todos)>0){
	$sql = "UPDATE tbl_faturamento set data_impressao_nfe =now() 
			WHERE fabrica      = 10
		  AND distribuidor = 4311
		  AND chave_nfe    IS NOT NULL
		  AND status_nfe in (100)
		  AND data_impressao_nfe is null;";
	$res = pg_query($con, $sql);
}else{
	$sql = "UPDATE tbl_faturamento set data_impressao_nfe =now() where chave_nfe =  '$chave'";
	$res = pg_query($con, $sql);
}
echo "<script language=\"JavaScript\">\n";
echo "<!--\n";
echo "window.close();\n";
echo "// -->\n";
echo "</script>\n";
exit;
?>
