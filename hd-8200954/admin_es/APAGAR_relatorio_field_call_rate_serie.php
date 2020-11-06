<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$peca         = $_GET['peca'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];

$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);

$sql = "SELECT descricao FROM tbl_peca WHERE peca = $peca";
$res = pg_exec($con,$sql);
$descricao_peca = pg_result($res,0,descricao);

$aux_data_inicial = substr($data_inicial,8,2)."/".substr($data_inicial,5,2)."/".substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)."/".substr($data_final,5,2)."/".substr($data_final,0,4);

$title = "Números de Série que apresentaram defeito";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style type="text/css">

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	text-align: left;
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
	text-align: left;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

</HEAD>

<BODY>

<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." até ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<table align = 'center'>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PRODUTO: <b><? echo $descricao_produto; ?></b></TD>
		</TR>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PEÇA: <b><? echo $descricao_peca; ?></b></TD>
		</TR>
	</table>
</TABLE>
<BR>

<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
<TR>
	<TD class='titChamada10'>OS</TD>
	<TD class='titChamada10'>ABERTURA</TD>
	<TD class='titChamada10'>POSTO</TD>
	<TD class='titChamada10'># SÉRIE</TD>
</TR>

<?
$sql = "SELECT	tbl_os.sua_os, 
				TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
				tbl_posto_fabrica.codigo_posto ,
				tbl_posto.nome                 ,
				tbl_os.serie                   ,
				tbl_os_item.pedido
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item USING (os_produto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				WHERE tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' 
				AND   tbl_os.fabrica = $login_fabrica 
				AND   tbl_posto.pais = '$login_pais'";

if (strlen($linha) > 0) {
	$sql .= "AND   tbl_produto.linha   = $linha ";
}

$sql .= "		AND   tbl_os_item.peca = $peca
				AND   tbl_os.produto = $produto";

if (strlen($estado) > 0) $sql .= "AND tbl_posto.estado  = '$estado' ";

$sql .= " ORDER BY tbl_os.serie";

	if ($ip == "200.158.65.19") { echo nl2br($sql);}









$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
if (strlen ($produto) > 0) $cond_1 = " tbl_os.produto      = $produto ";
if (strlen ($peca)    > 0) $cond_2 = " tbl_os_item.peca    = $peca ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen ($estado)  > 0) $cond_4 = " tbl_posto.estado    = '$estado' ";

$sql = "SELECT tbl_os.os, tbl_os.sua_os, TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_os.serie
		FROM tbl_os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
		JOIN   (SELECT DISTINCT tbl_os_produto.os
				FROM tbl_os_produto
				JOIN (SELECT tbl_os.os , 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
					AND   tbl_extrato.liberado IS NOT NULL
					AND   tbl_os.excluida IS NOT TRUE
					AND   tbl_os.produto = $produto
					AND   $cond_1
					AND   $cond_3
					AND   $cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				AND   $cond_2
		) fcr1 ON tbl_os.os = fcr1.os
		ORDER BY tbl_os.data_abertura " ;


//if ($ip == "201.0.9.216") { echo '<br><br>'.nl2br($sql);exit; }




$res = pg_exec($con, $sql);
if(pg_numrows($res) > 0){

	for($i=0; $i<pg_numrows($res); $i++){
		$os				= pg_result($res,$i,os);
		$sua_os			= pg_result($res,$i,sua_os);
		$data_abertura	= pg_result($res,$i,data_abertura);
		$codigo_posto	= pg_result($res,$i,codigo_posto);
		$nome			= pg_result($res,$i,nome);
		$serie			= pg_result($res,$i,serie);

		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='left'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
		echo "	<TD class='conteudo10' align='left'>$data_abertura</TD>";
		echo "	<TD class='conteudo10' align='left'>$codigo_posto - $nome</TD>";
		echo "	<TD class='conteudo10' align='left'>$serie</TD>";
		echo "</TR>";

	}
}
?>

</TABLE>

<p>
<center>
Ocorrências: <? echo $i ?>
</center>
<?include ("relatorio_field_call_rate_serie_grafico.php"); ?>
</BODY>
</HTML>