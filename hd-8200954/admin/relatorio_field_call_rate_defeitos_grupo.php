<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "monitora_cabecalho.php";

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$peca         = $_GET['peca'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];
$tipo         = $_GET['tipo'];
$tipo_os = $_GET['tipo_os'];

if($tipo=="produto"){
$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);

}else{
$descricao_produto = "$produto";
}
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

$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
$cond_5 = "1=1";

if (strlen ($produto) > 0) $cond_1 = " tbl_os.produto      = $produto ";
if (strlen ($peca)    > 0) $cond_2 = " tbl_os_item.peca    = $peca ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen ($estado)  > 0) $cond_4 = " tbl_posto.estado    = '$estado' ";
if (strlen ($tipo_os) > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";

$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, count(fcr.defeito) as ocorrencia
		FROM (SELECT tbl_os.os , tbl_os_item.peca,tbl_os_item.defeito,
				( SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1 ) AS status 
				FROM tbl_os 
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
				JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
				join tbl_os_produto on tbl_os.os = tbl_os_produto.os
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND $cond_5
				AND tbl_os.excluida IS NOT TRUE 
				AND tbl_os.produto = $produto
				AND tbl_extrato.fabrica = $login_fabrica 
				and tbl_servico_realizado.troca_de_peca is true 
				and $cond_2
			) fcr 
			JOIN tbl_defeito on tbl_defeito.defeito = fcr.defeito
			WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
			GROUP BY tbl_defeito.descricao
		ORDER BY ocorrencia DESC " ;

if($tipo=="grupo"){
$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, count(fcr.defeito) as ocorrencia
		FROM (SELECT tbl_os.os , tbl_os_item.peca,tbl_os_item.defeito,
				( SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1 ) AS status 
				FROM tbl_os 
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
				JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
				JOIN tbl_produto on tbl_os.produto = tbl_produto.produto 
				join tbl_os_produto on tbl_os.os = tbl_os_produto.os
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND $cond_5
				AND tbl_os.excluida IS NOT TRUE 
				AND tbl_produto.referencia_fabrica = '$produto' 
				AND tbl_extrato.fabrica = $login_fabrica 
				and tbl_servico_realizado.troca_de_peca is true 
				and $cond_2
			) fcr 
			JOIN tbl_defeito on tbl_defeito.defeito = fcr.defeito
			WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
			GROUP BY tbl_defeito.descricao
		ORDER BY ocorrencia DESC " ;

}


$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, count(fcr.defeito) as ocorrencia
		FROM (SELECT tbl_os.os , tbl_os_item.peca,tbl_os_item.defeito
				FROM tbl_os 
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
				JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
				join tbl_os_produto on tbl_os.os = tbl_os_produto.os
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND $cond_5
				AND tbl_os.excluida IS NOT TRUE 
				AND tbl_os.produto = $produto
				AND tbl_extrato.fabrica = $login_fabrica 
				and tbl_servico_realizado.troca_de_peca is true 
				and $cond_2
			) fcr 
			JOIN tbl_defeito on tbl_defeito.defeito = fcr.defeito
			GROUP BY tbl_defeito.descricao
		ORDER BY ocorrencia DESC " ;

if($tipo=="grupo"){
$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, count(fcr.defeito) as ocorrencia
		FROM (SELECT tbl_os.os , tbl_os_item.peca,tbl_os_item.defeito
				FROM tbl_os 
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
				JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
				JOIN tbl_produto on tbl_os.produto = tbl_produto.produto 
				join tbl_os_produto on tbl_os.os = tbl_os_produto.os
				join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND $cond_5
				AND tbl_os.excluida IS NOT TRUE 
				AND tbl_produto.referencia_fabrica = '$produto' 
				AND tbl_extrato.fabrica = $login_fabrica 
				and tbl_servico_realizado.troca_de_peca is true 
				and $cond_2
			) fcr 
			JOIN tbl_defeito on tbl_defeito.defeito = fcr.defeito
			GROUP BY tbl_defeito.descricao
		ORDER BY ocorrencia DESC " ;

}
//echo $sql; 
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
<? include "monitora_rodape.php";?>