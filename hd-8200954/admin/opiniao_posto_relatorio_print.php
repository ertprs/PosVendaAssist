<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

$linha = $_POST['linha'];
if (strlen ($linha) == 0) $linha = $_GET['linha'];

$distribuidor_posto = $_POST['distribuidor_posto'];
if (strlen ($distribuidor_posto) == 0) $distribuidor_posto = $_GET['distribuidor_posto'];

$distribuidor = $_POST['distribuidor'];
if (strlen ($distribuidor) == 0) $distribuidor = $_GET['distribuidor'];

$estado = $_POST['estado'];
if (strlen ($estado) == 0) $estado = $_GET['estado'];


$listardetalhes = $_POST['listardetalhes'];
if (strlen ($listardetalhes) == 0) $listardetalhes = $_GET['listardetalhes'];


$title       = "Opinião Posto";
$cabecalho   = "Impressão da Opinião Posto";

$res = pg_exec ($con,"SELECT opiniao_posto FROM tbl_opiniao_posto WHERE fabrica = $login_fabrica AND ativo IS TRUE ");
if (pg_numrows ($res) == 0) {
	echo "<center><h2>Não existem pesquisas de opinião ativas</h2></center>\n";
	exit;
}
$opiniao_posto = pg_result ($res,0,0);

if ($listardetalhes <> 4){

?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.lnk {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#FF00CC;
}

.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line_2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	color: #596d9b
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<table width='98%'>
<tr class=pesquisa><td><? echo $title; ?></td></tr>
</table>

<?
	$x_distribuidor = $distribuidor_posto;

	if (strlen ($linha) == 0)
		$linha_selecao = " 1=1 " ;
	else
		$linha_selecao = " tbl_posto.posto IN (SELECT tbl_posto_linha.posto FROM tbl_posto_linha WHERE tbl_posto_linha.linha = $linha) " ;

	if (strlen ($estado) == 0)
		$estado_selecao = " 1=1 " ;
	else
		$estado_selecao = " tbl_posto.estado = '$estado' " ;

	if ($distribuidor_posto == 'TODOS')
		$distribuidor_selecao = "1=1" ;

	if ($distribuidor_posto == 'DIRETO')
		$distribuidor_selecao = " tbl_posto_fabrica.distribuidor is NULL " ;

	if ($distribuidor_posto == 'VIA-DISTRIB'){
		if (strlen ($distribuidor) == 0)
			$distribuidor_selecao = " tbl_posto_fabrica.distribuidor notnull " ;
		else
			$distribuidor_selecao = " tbl_posto_fabrica.distribuidor = '$distribuidor'";
	}

	$sql = "SELECT	tbl_posto_fabrica.posto       ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                
			FROM tbl_posto_fabrica 
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
			JOIN (
				SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
				JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
				JOIN tbl_opiniao_posto USING (opiniao_posto) 
				WHERE tbl_opiniao_posto.opiniao_posto = $opiniao_posto
			) resp ON resp.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
			AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND   $linha_selecao
			AND   $estado_selecao
			AND   $distribuidor_selecao
			ORDER BY tbl_posto.nome";
	$res4 = pg_exec ($con,$sql);

	echo "<table width='98%'>\n";
	echo "<tr class=pesquisa><td colspan='2'>RELAÇÃO DOS POSTOS QUE RESPONDERAM</td></tr>\n";
		
	for ($i = 0 ; $i < pg_numrows ($res4) ; $i++) {
		$codigo_posto = pg_result ($res4,$i,codigo_posto);
		$nome_posto   = pg_result ($res4,$i,nome);
		echo "<tr class='menu_top'>\n";
		echo "<td align='left'>&nbsp; $codigo_posto</td>\n";
		echo "<td align='left'><a href='$PHP_SELF?listardetalhes=4&codigo_posto=$codigo_posto'>&nbsp; $nome_posto</a></td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";

}elseif ($listardetalhes == 4){

?>

<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	color:#000000;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

</style>

<table width='98%'>
<tr class='menu_top'><td><? echo $title; ?></td></tr>
</table>

<?
	if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];

	echo "<br>";

	$sql = "SELECT	tbl_opiniao_posto.cabecalho
			FROM tbl_opiniao_posto 
			WHERE opiniao_posto = $opiniao_posto";
	$res = pg_exec($con,$sql);
	$cabecalho = strtoupper(pg_result($res,0,cabecalho));

	$sql = "SELECT	tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto_fabrica.contato_fone_comercial as fone                ,
					tbl_posto.cidade              ,
					tbl_posto.estado              
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica USING (posto)
			WHERE	tbl_posto_fabrica.codigo_posto = '$codigo_posto'
			AND		tbl_posto_fabrica.fabrica = $login_fabrica";
	$res2 = pg_exec($con,$sql);

	$codigo_poto = strtoupper(pg_result($res2,0,codigo_posto));
	$posto_nome  = strtoupper(pg_result($res2,0,nome));
	$fone        = strtoupper(pg_result($res2,0,fone));
	$cidade      = strtoupper(pg_result($res2,0,cidade));
	$estado      = strtoupper(pg_result($res2,0,estado));

?>

	<p>
	<table class="border" width='98%' align='center' border='0' cellpadding="0" cellspacing="1">
	<tr>
		<td class='menu_top'>&nbsp;POSTO: <b><?echo $codigo_posto ?></b></td>
		<td class='menu_top'>&nbsp;<b><?echo $posto_nome ?></b></td>
	</tr>
	<tr>
		<td class='menu_top' align='left'>&nbsp;FONE: <b><?echo $fone?></b></td>
		<td class='menu_top' align='left'>&nbsp;CIDADE/ESTADO: <b><?echo $cidade." / ".$estado;?></b></td>
	</tr>
	</table>
	<br>
	<table class="border" width='98%' align='center' border='0' cellpadding="0" cellspacing="1">
	<TR>
		<TD class='menu_top' width='50%'><div align='center'>&nbsp;<b>PERGUNTAS </b></div></TD>
		<TD class='menu_top' width='50%'><div align='CENTER'>&nbsp;<b>RESPOSTAS </b></div></TD>
	</TR>
	
<?
	$sql = "SELECT  DISTINCT 
					tbl_opiniao_posto_pergunta.pergunta,
					tbl_opiniao_posto_resposta.resposta,
					tbl_opiniao_posto_pergunta.ordem   
			FROM	tbl_opiniao_posto_resposta
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto         = tbl_opiniao_posto_resposta.posto 
									  AND tbl_posto_fabrica.fabrica      = $login_fabrica
									  AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
			JOIN    tbl_opiniao_posto_pergunta ON tbl_opiniao_posto_pergunta.opiniao_posto_pergunta = tbl_opiniao_posto_resposta.opiniao_posto_pergunta
			JOIN    tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
			WHERE   tbl_opiniao_posto.fabrica        = $login_fabrica
			ORDER BY tbl_opiniao_posto_pergunta.ordem;";
	$res2 = pg_exec($con,$sql);

	if (pg_numrows($res2) > 0){
		for ($i=0; $i < pg_numrows($res2); $i++){
			$pergunta = pg_result($res2,$i,pergunta);
			$resposta = pg_result($res2,$i,resposta);
			if ($resposta == 't') $resposta = 'Sim';
			if ($resposta == 'f') $resposta = 'Não';

			echo "<TR>\n";
			echo "	<TD class='menu_top'><div align='left'>&nbsp;$pergunta</div></TD>\n";
			echo "	<TD class='menu_top'><div align='left'>&nbsp;$resposta</div></TD>\n";
			echo "</TR>\n";

		}//for

		echo "<script>window.print();</script>";
	}//if
}

?>

</TABLE>
