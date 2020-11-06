<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen($_GET["resolvido"])  > 0){
$resolvido= $_GET["resolvido"];
$sql = "UPDATE tbl_extrato_status 
				set pendente = 'f',
					confirmacao_pendente = 'f'
		WHERE extrato=$resolvido 
		and pendente = 't' 
		and confirmacao_pendente = 't'";
$res = pg_exec($con,$sql);
$extrato=$resolvido;
}



if (strlen(trim($_GET["extrato"])) > 0)  $extrato = trim($_GET["extrato"]);
if (strlen(trim($_POST["extrato"])) > 0) $extrato = trim($_POST["extrato"]);
if (strlen(trim($_POST["acao"])) > 0)    $acao = trim($_POST["acao"]);
if (strlen(trim($_POST["pendente"])) > 0) {   
	$pendente = "'". trim($_POST["pendente"])."'";
}else{
$pendente='null';
}

if ($acao == "ALTERAR") {
	$x_obs = trim($_POST["obs"]);
	$pendente = "'t'";
	if (strlen($x_obs) == 0) $erro .= " Preencha o campo Observação. ";
	
	if (strlen($erro) == 0) {
		$sql = "INSERT INTO tbl_extrato_status (
						extrato ,
						obs     ,
						data,
						pendente
					) VALUES (
						$extrato ,
						'$x_obs' ,
						current_timestamp,
						$pendente
				);";
//echo $sql;
/*
pendente = informa para o posto que esta pendente
confirmacao_pendente = admin confirma que a pendecia esta resolvida
*/
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);

		if (strlen($erro) == 0 and $pendente=="'t'") {
		
			$xsql = "SELECT email from tbl_posto join tbl_extrato using(posto) where extrato=$extrato";
			$xres = pg_exec($con,$xsql);
			$xemail_posto = pg_result($xres,0,email);
			
			$xsql = "SELECT protocolo from tbl_extrato where extrato=$extrato";
			$xres = pg_exec($con,$xsql);
			$xprotocolo = pg_result($xres,0,protocolo);

			$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica";
			$xres = pg_exec($con,$xsql);
			$xnome_completo = pg_result($xres,0,nome_completo);
			$xfone = pg_result($xres,0,fone);
			$xemail_admin = pg_result($xres,0,email);

			$remetente    = "Black&Decker <$xemail_admin>"; 
			$destinatario = "$xemail_posto"; 
			$assunto      = "Pendência em extrato"; 
			$mensagem     = "Prezado Posto Autorizado,<BR><BR>
			Você tem pendência para enviar para a Blackedecker do extrato de número $xprotocolo.<BR><BR><b>Pendência</b><BR>$x_obs<BR><BR>
			$xnome_completo<BR>Fone: $xfone"; 
			$headers="Return-Path: <$xemail_admin>\nFrom:".$remetente."\nBcc:$xemail_admin \nContent-type: text/html\n"; 
			
			if ( mail($destinatario,$assunto,$mensagem,$headers) ) {
				/*echo "<script language='JavaScript'>\n";
				echo "window.close();";
				echo "</script>";		*/
			}else{
				echo "erro";
			}
		}
	}
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
				tbl_posto.nome                                 AS posto_nome
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

<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	<tr>
		<td width='100%' colspan="3"><b>Posto</b></td>
	</tr>
	<tr>
		<td width='100%' colspan="3"><?echo substr($posto_completo,0,40)?></td>
	</tr>
	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td  width='50%' colspan="2"><b>Data Geração</b></td>
		<td width='50%'><b>Data Aprovado</b></td>
	</tr>
	<tr>
		<td width='50%' colspan="2"><?echo $data_geracao?></td>
		<td width='50%'><?echo $data_aprovado?></td>
	</tr>
</table><BR>

<?
$xsql = "SELECT 	tbl_extrato_status.obs,
				tbl_extrato_status.pendente,
				tbl_extrato_status.confirmacao_pendente
		FROM tbl_extrato_status 
		WHERE extrato = $extrato and pendente notnull";
$xres = pg_exec($con,$xsql);
//echo "$xsql";
if(pg_numrows($xres)>0){
?>
<table width='100%' border='0' cellspacing='1' cellpadding='1' style='font-family: verdana; font-size: 12px'  bgcolor='#596D9B'>
<tr>
	<td><font size='1' color='#FFFFFF'><b>Situção</b></FONT></td>
	<td><font size='1' color='#FFFFFF'><b>Pendência</b></FONT></td>
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
		echo "<td bgcolor='#FFFFFF'><font size='2'>";
		if($situacao=="Aguardando admin"){ 
			echo "<a href=\"javascript: if (confirm('Confirmo que recebi do PA a pendência faltante') == true) { window.location='$PHP_SELF?resolvido=$extrato'; }\">$situacao </a>";
		}else{
			echo "$situacao";
		}
		echo "</font></td>";
		echo "<td bgcolor='#FFFFFF'><font size='2'>$xobs</font></td>";
		echo "</tr>";

	}
echo "</table>";
				//echo "<BR><a href=\"javascript: if (confirm('Confirmo que recebi do PA a pendência faltante') == true) { window.location='$PHP_SELF?pendencia=$extrato'; }\"><font size='1'>Baixar pendência</font></a>";
}

?>
<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>

	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td width='25%' valign="top"><b>Obs.:</b></td>
		<td width='75%' colspan="2"><textarea name="obs" cols='80' rows='10' ><?echo $obs?></textarea></td>
	</tr>
	
</table>
<br>
<center>
<img border="0" src="imagens_admin/btn_confirmar.gif" style="cursor: hand;" onclick="javascript: if (document.frm_extrato.acao.value == '') { document.frm_extrato.acao.value='ALTERAR'; document.frm_extrato.submit(); }else{ alert('Aguarde Submissão...'); }" alt="Clique aqui para inserir a obs.">
</center>

</form>

</body>

</html>
