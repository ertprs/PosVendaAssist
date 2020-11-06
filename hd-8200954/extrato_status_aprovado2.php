<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";


$erro = "";
$tipo     = $_GET['tipo'];
$pendencia = $_GET['pendencia'];

if(strlen($pendencia)>0){
	$sql ="UPDATE tbl_extrato_status set confirmacao_pendente='t'
			WHERE extrato=$pendencia
			and pendente='t'";
	$res = pg_exec($con,$sql);
	$erro = pg_errormessage($con);
	if (strlen($erro) == 0){

		$xsql = "SELECT protocolo from tbl_extrato where extrato=$pendencia";
		$xres = pg_exec($con,$xsql);
		$xprotocolo = pg_result($xres,0,protocolo);

		$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
		$destinatario = "llaterza@blackedecker.com.br"; 
		$assunto      = "Pendência em extrato resolvido"; 
		$mensagem     = "A <BR>Blackedecker<BR><BR>
		Minha pendência do extrato de número $xprotocolo foi resolvida, favor verificar.
<BR><BR>
PA $login_codigo_posto - $login_nome"; 
		$headers="Return-Path: <llaterza@blackedecker.com.br>\nFrom:".$remetente."\nBcc:takashi@telecontrol.com.br \nContent-type: text/html\n"; 
		
	/*	if ( mail($destinatario,$assunto,$mensagem,$headers) ) {
	
		}else{
			echo "erro";
		}*/
	}
$extrato =$pendencia;
}
?>

<html>

<head>

<title>Observação do Status do Extrato</title>

<style>
input {
	BORDER-RIGHT: #888888 1px solid;
	BORDER-TOP: #888888 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: #888888 1px solid;
	BORDER-BOTTOM: #888888 1px solid;
	FONT-FAMILY: Verdana;
	BACKGROUND-COLOR: #f0f0f0
}
.erro {
  color: white;
  text-align: center;
  font: bold 12px Verdana, Arial, Helvetica, sans-serif;
  background-color: #FF0000;
}
.tabela {
    font-family: Verdana, Tahoma, Arial;
    font-size: 10pt;
    text-align: center;
}
</style>

</head>

<body>

<?
// CARREGA DADOS DO EXTRATO
$sql =	"SELECT TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
				TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS data_aprovado ,
				tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
				tbl_posto.nome                                 AS posto_nome    ,
				tbl_extrato.protocolo
		FROM tbl_extrato
		JOIN tbl_posto          ON  tbl_posto.posto           = tbl_extrato.posto
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$data_geracao   = trim(pg_result($res,0,data_geracao));
	$data_aprovado  = trim(pg_result($res,0,data_aprovado));
	$posto_codigo   = trim(pg_result($res,0,posto_codigo));
	$posto_nome     = trim(pg_result($res,0,posto_nome));
	$protocolo      = trim(pg_result($res,0,protocolo));
	$posto_completo = $posto_codigo . " - " . $posto_nome;
}

if (strlen($erro) > 0) {
	$obs = trim($_POST["obs"]);
	echo "<div class='erro'>$erro</div>";
}
?>

<form name="frm_extrato" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="extrato" value="<?echo $extrato?>">
<input type="hidden" name="acao">

<table width='100%' border='0' cellspacing='1' cellpadding='1'  style='font-family: verdana; font-size: 12px'>
	<tr>
		<td ><b>Extrato</b></td>
	<td  width='50%' ><b>Data Geração</b></td>
	</tr>
	<tr>
	<td ><?echo $protocolo; ?></td>
	<td width='50%'><?echo $data_geracao?></td>
	</tr>
</table><BR>

<?

if($tipo=="pendencia"){
$xsql = "SELECT 	tbl_extrato_status.obs,to_char(tbl_extrato_status.data,'DD/MM/YYYY') as data , 
				tbl_extrato_status.pendente,
				tbl_extrato_status.confirmacao_pendente
		FROM tbl_extrato_status 
		WHERE extrato = $extrato and pendente notnull";
$xres = pg_exec($con,$xsql);
//echo "$xsql";
if(pg_numrows($xres)>0){
?>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'  bgcolor='#596D9B'>
<tr>
	<td width='80px' align='center'><font color='#FFFFFF'><b>Situação</b></FONT></td>
	<td><font color='#FFFFFF'><b>Pendência</b></FONT></td>
</tr>
<?	for($x=0;pg_numrows($xres)>$x;$x++){
		$xobs = pg_result($xres,$x,obs);
		$xdata = pg_result($xres,$x,data);
		$xpendente = pg_result($xres,$x,pendente);
		$xconfirmacao_pendente = pg_result($xres,$x,confirmacao_pendente);

if($xpendente=="t" and strlen($xconfirmacao_pendente)==0){$situacao = "Aguardando posto";}
if($xpendente=="f" and $xconfirmacao_pendente=="f"){$situacao = "Resolvido";}
if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
if(strlen($xpendente)==0 and strlen($xconfirmacao_pendente)==0){$situacao = "Observação";}
//if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
		$cor = "#d0e0f6"; 
		if ($x % 2 == 0) $cor = '#efeeea';

		echo "<tr bgcolor='$cor'>";
		echo "<td align='center'><font size='1'>$xdata<BR><B>";
		if($situacao=="Aguardando admin"){ 
			echo "<a href=\"javascript: if (confirm('Confirmo que recebi do PA a pendência faltante') == true) { window.location='$PHP_SELF?resolvido=$extrato'; }\">$situacao </a>";
		}else{
			echo "$situacao";
		}
		echo "</b></font></td>";
		echo "<td ><font size='2'>".nl2br($xobs)."</font></td>";
		echo "</tr>";

	}

?>

</table>
<?
}}
?>
<?
if($tipo=="obs"){
$xsql = "SELECT 	tbl_extrato_status.obs,
				tbl_extrato_status.pendente,
				tbl_extrato_status.confirmacao_pendente
		FROM tbl_extrato_status 
		WHERE extrato = $extrato and pendente is null and confirmacao_pendente is null";
$xres = pg_exec($con,$xsql);
//echo "$xsql";
if(pg_numrows($xres)>0){
?>
<table width='100%' border='0' cellspacing='1' cellpadding='1' style='font-family: verdana; font-size: 12px'  bgcolor='#596D9B'>
<tr>
	<td><font color='#FFFFFF'><b>Tipo</b></FONT></td>
	<td><font color='#FFFFFF'><b>Observação</b></FONT></td>
</tr>
<?
	for($x=0;pg_numrows($xres)>$x;$x++){
		$xobs = pg_result($xres,$x,obs);
		$xpendente = pg_result($xres,$x,pendente);
		$xconfirmacao_pendente = pg_result($xres,$x,confirmacao_pendente);

if($xpendente=="t" and strlen($xconfirmacao_pendente)==0){$situacao = "Aguardando posto";}
if($xpendente=="f" and $xconfirmacao_pendente=="f"){$situacao = "Resolvido";}
if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
if(strlen($xpendente)==0 and strlen($xconfirmacao_pendente)==0){$situacao = "Observação";}
//if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
		echo "<tr>";
		echo "<td bgcolor='#FFFFFF'><font size='2'>OBSERVAÇÃO</font></td>";
		echo "<td bgcolor='#FFFFFF'><font size='2'>$xobs</font></td>";
		echo "</tr>";

	}
echo "</table>";
				//echo "<BR><a href=\"javascript: if (confirm('Confirmo que recebi do PA a pendência faltante') == true) { window.location='$PHP_SELF?pendencia=$extrato'; }\"><font size='1'>Baixar pendência</font></a>";
}
}
?>

</center>

</form>

</body>

</html>
