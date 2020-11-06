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

$title = "RELATÓRIO DE QUEBRA - DEFEITOS";

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
	<TD class='titChamada10'>DEFEITO</TD>
	<TD class='titChamada10'>OCORRÊNCIAS</TD>
	<TD class='titChamada10'>%</TD>
</TR>

<?
$sql = "SELECT  vw_quebra_defeito.fabrica   ,
				vw_quebra_defeito.produto   ,
				vw_quebra_defeito.peca      ,
				vw_quebra_defeito.referencia,
				vw_quebra_defeito.descricao ,
				vw_quebra_defeito.status_os , ";

if (strlen($linha) > 0)  $sql .= "vw_quebra_defeito.linha, ";
if (strlen($estado) > 0) $sql .= "vw_quebra_defeito.estado, ";

$sql .= "		vw_quebra_defeito.defeito_descricao,
				sum(vw_quebra_defeito.ocorrencia) AS ocorrencia
		FROM (
				SELECT	tbl_os.fabrica     ,
						tbl_produto.produto, ";

if (strlen($linha) > 0)  $sql .= "tbl_linha.linha, ";
if (strlen($estado) > 0) $sql .= "tbl_posto.estado, ";

$sql .= "				tbl_peca.peca                                         ,
						tbl_peca.referencia                                   ,
						tbl_peca.descricao                                    ,
						tbl_os_status.status_os                               ,
						count(tbl_os_item.peca)                  AS ocorrencia,
						date_trunc('day', tbl_os.data_digitacao) AS digitada  ,
						date_trunc('day', tbl_os.finalizada)     AS finalizada,
						tbl_defeito.descricao                    AS defeito_descricao
				FROM    tbl_os
				JOIN    tbl_os_produto   ON tbl_os_produto.os      = tbl_os.os
				JOIN    tbl_os_item      ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN    tbl_defeito      ON tbl_defeito.defeito    = tbl_os_item.defeito
				JOIN    tbl_posto        ON tbl_posto.posto        = tbl_os.posto
				JOIN    tbl_produto      ON tbl_produto.produto    = tbl_os_produto.produto
										AND tbl_os_produto.os      = tbl_os.os
				JOIN    tbl_linha        ON tbl_linha.linha        = tbl_produto.linha 
				JOIN    tbl_peca         ON tbl_os_item.peca       = tbl_peca.peca 
				LEFT JOIN tbl_os_status  ON tbl_os_status.os       = tbl_os.os
				GROUP BY    tbl_os.fabrica     ,
							tbl_os_item.defeito,
							tbl_produto.produto, ";

if (strlen($linha) > 0)  $sql .= "	tbl_linha.linha, ";
if (strlen($estado) > 0) $sql .= "	tbl_posto.estado, ";

$sql .= "					tbl_peca.peca                           ,
							tbl_peca.referencia                     ,
							tbl_peca.descricao                      ,
							tbl_os_status.status_os                 ,
							date_trunc('day', tbl_os.data_digitacao),
							date_trunc('day', tbl_os.finalizada)    ,
							tbl_defeito.descricao
		) AS vw_quebra_defeito
		WHERE vw_quebra_defeito.digitada BETWEEN '$data_inicial' AND '$data_final' 
		AND  (vw_quebra_defeito.status_os NOT IN (13,15) OR vw_quebra_defeito.status_os IS NULL)
		AND   vw_quebra_defeito.fabrica = $login_fabrica ";

if (strlen($linha) > 0)  $sql .= "AND vw_quebra_defeito.linha   = '$linha' ";
if (strlen($estado) > 0) $sql .= "AND vw_quebra_defeito.estado  = '$estado' ";

$sql .= "	AND vw_quebra_defeito.peca = $peca
			AND vw_quebra_defeito.produto = $produto
		GROUP BY    vw_quebra_defeito.fabrica   ,
					vw_quebra_defeito.produto   ,
					vw_quebra_defeito.peca      ,
					vw_quebra_defeito.referencia,
					vw_quebra_defeito.descricao ,
					vw_quebra_defeito.status_os , ";

if (strlen($linha) > 0)  $sql .= "vw_quebra_defeito.linha, ";
if (strlen($estado) > 0) $sql .= "vw_quebra_defeito.estado, ";

$sql .= "vw_quebra_defeito.defeito_descricao
		ORDER BY sum(vw_quebra_defeito.ocorrencia) DESC;";


$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
if (strlen ($produto) > 0) $cond_1 = " tbl_os.produto      = $produto ";
if (strlen ($peca)    > 0) $cond_2 = " tbl_os_item.peca    = $peca ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen ($estado)  > 0) $cond_4 = " tbl_posto.estado    = '$estado' ";


$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia
		FROM tbl_defeito
		JOIN   (SELECT tbl_os_item.defeito, COUNT(*) AS ocorrencia
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os , 
						      (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$sql .="AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_3
						AND   $cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				AND   $cond_2
				GROUP BY tbl_os_item.defeito
		) defeitos ON defeitos.defeito = tbl_defeito.defeito
		ORDER BY defeitos.ocorrencia DESC " ;


//if ($ip == "201.0.9.216") { echo nl2br($sql); }

$res = pg_exec($con, $sql);
if(pg_numrows($res) > 0){

	for ($x = 0; $x < pg_numrows($res); $x++) {
		$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		//$total_mobra      = $total_mobra + pg_result($res,$x,soma_mobra);
		//$total_peca       = $total_peca + pg_result($res,$x,soma_peca);
		//$total_geral      = $total_geral + pg_result($res,$x,soma_total);
	}

	for($i=0; $i<pg_numrows($res); $i++){
		$defeito    = pg_result($res,$i,defeito_descricao);
		$ocorrencia = pg_result($res,$i,ocorrencia);

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}

		$cor = '#ffffee';
		if ($i % 2 == 0) $cor = '#eeffff';

		echo "<TR bgcolor='$cor' style='font-size: 10px ; font-face: verdana'>";
		echo "	<TD align='left'>$defeito</TD>";
		echo "	<TD align='center'>$ocorrencia</TD>";
		echo "	<TD align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "</TR>";
	}
}
?>

</TABLE>
</BODY>
</HTML>
