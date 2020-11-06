<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';




$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'novo'";


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'análise' ";

$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}



$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'aprovação'";

$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}



$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'resolvido'";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}


?>

<html>
<body>
<TABLE>
<TR>
	<TD colspan="2" bgcolor="FFcc00">ESTATÍSTICAS DE CHAMADAS</TD>
</TR>
<TR>
	<TD bgcolor="FFcc00"><CENTER>Status</CENTER></TD>
	<TD bgcolor="FFcc00"><CENTER>Total</CENTER></TD>
</TR>
<TR bgcolor="eeeeee">
	<TD>Novo</TD>
	<TD><CENTER><? echo $total_novo ?></CENTER></TD>
</TR>
<TR bgcolor="eeeeee">
	<TD>Análise</TD>
	<TD><CENTER><? echo $total_analise ?></CENTER></TD>
</TR>
<TR bgcolor="eeeeee">
	<TD>Aprovação</TD>
	<TD><CENTER><? echo $total_aprovacao ?></CENTER></TD>
</TR>
<TR bgcolor="eeeeee">
	<TD>Resolvido</TD>
	<TD><CENTER><? echo $total_resolvido ?></CENTER></TD></TR>
</TABLE>

</body>
</html>

