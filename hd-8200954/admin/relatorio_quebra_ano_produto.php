<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$titulo = "RELATÓRIO DE QUEBRA POR ANO - Produtos";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><? echo $titulo; ?></title>
	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<link type="text/css" rel="stylesheet" href="css/css.css">

	<script LANGUAGE="JavaScript">
		window.focus();
	</script>

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

-->
</style>

</head>
<body bgcolor="#FFFFFF" MARGINWIDTH="0" MARGINHEIGHT="0" TOPMARGIN="0" LEFTMARGIN="0">

<TABLE width='550' cellspacing='2' cellpadding='2' border='0'>
<TR>
	<TD class='titPreto14'><h1><? echo $titulo; ?></h1></TD>
</TR>
<TR>
	<TD class='titDatas12'><? echo $_GET['data_inicial'] ." até ".$_GET['data_final']?></TD>
</TR>
</TABLE>

<br>

<TABLE width='550' cellspacing='0' cellpadding='2' border='0'>
<TR>
	<TD class='titChamada10'>REFERÊNCIA</TD>
	<TD class='titChamada10'>PRODUTO</TD>
	<TD class='titChamada10'>OCORRÊNCIAS</TD>
	<!-- <TD class='titChamada10'>%</TD> -->
</TR>

<?
	$mes = $_GET['mes'];

	$data_inicial = trim($_GET["data_inicial"]);
	$fnc          = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		
	$erro = pg_errormessage ($con) ;
		
	if (strlen($erro) == 0) $aux_data_inicial = "'". @pg_result ($fnc,0,0) ." 00:00:00'";

	$data_final = trim($_GET["data_final"]);
	$fnc          = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		
	$erro = pg_errormessage ($con) ;
		
	if (strlen($erro) == 0) $aux_data_final = "'". @pg_result ($fnc,0,0) ." 23:59:59'";

	$sql = "SELECT	tbl_produto.referencia                       ,
					tbl_produto.descricao                        ,
					count(vw_visao_geral_detalhe.produto) AS qtde
			FROM	vw_visao_geral_detalhe
			JOIN	tbl_produto USING (produto)
			WHERE	vw_visao_geral_detalhe.data_digitacao 
			BETWEEN	$aux_data_inicial
			AND		$aux_data_final
			AND		vw_visao_geral_detalhe.fabrica = $login_fabrica
			AND		vw_visao_geral_detalhe.mes     = $mes
			GROUP BY tbl_produto.referencia, 
					tbl_produto.descricao 
			ORDER BY tbl_produto.referencia;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));
			$qtde       = trim(pg_result($res,$i,qtde));

			echo "<TR class='bgTRConteudo1'>";
			echo "	<TD class='conteudo10' align='left'>$referencia</TD>";
			echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
			echo "	<TD class='conteudo10' align='center'>$qtde</TD>";
//			echo "	<TD class='conteudo10' align='right'>". number_format($porc,2,",",".") ."%</TD>";
			echo "</TR>";

		}
	}
?>
</TABLE>

</body>
</html>