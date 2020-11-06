<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

include 'funcoes.php';

$erro = "";

if (strlen(trim($_GET["extrato"])) > 0)  $extrato = trim($_GET["extrato"]);
if (strlen(trim($_POST["extrato"])) > 0) $extrato = trim($_POST["extrato"]);
if (strlen(trim($_POST["acao"])) > 0)    $acao = trim($_POST["acao"]);
$apagar = $_GET['apagar'];
$extrato_apagar = $_GET['extrato_apagar'];
if(strlen($apagar)>0 and strlen($extrato_apagar)>0){
	$sql = "SELECT total, protocolo, to_char(data_envio,'DD/MM/YYYY') as data_envio from tbl_extrato join tbl_extrato_financeiro on tbl_extrato.extrato = tbl_extrato_financeiro.extrato where tbl_extrato.extrato = $extrato_apagar and tbl_extrato.fabrica = $login_fabrica";
	//echo $sql; exit;	
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$total = pg_result($res,0,total);
		$protocolo = pg_result($res,0,protocolo);
		$data_envio = pg_result($res,0,data_envio);

		$sql = "DELETE from tbl_extrato_financeiro where extrato = $extrato_apagar";
		$res = pg_exec($con,$sql);
		
		$sql = "UPDATE tbl_extrato_extra SET nota_fiscal_mao_de_obra = null where extrato = $extrato_apagar";
		$res = pg_exec($con,$sql);

		//echo "$sql";
		$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
		$destinatario = "MiPereira@blackedecker.com.br"; 
//		$destinatario = "takashi@telecontrol.com.br"; 
		$assunto      = "Extrato excluido do financeiro"; 
		$mensagem     = "O extrato <B>$protocolo</b>  enviado para o finceiro <b>$data_envio</b> foi reaberto pelo usuário <B>$login_login</b>, favor verificar.<BR> Suporte Telecontrol"; 
		$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nBcc:takashi@telecontrol.com.br \nContent-type: text/html\n"; 
		//mail($destinatario,$assunto,$mensagem,$headers);
	}
}

if ($acao == "ALTERAR") {
	$x_data = trim($_POST["data"]);
	$x_data = fnc_formata_data_pg($x_data);
	if ($x_data != null) {
		$sql = "UPDATE tbl_extrato_financeiro SET data_envio = $x_data
				WHERE  tbl_extrato_financeiro.extrato = $extrato;";
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);
		
		if (strlen($erro) == 0) {
			echo "<script language='JavaScript'>\n";
			//echo "window.parent.location.reload();";
			echo "window.close();";
			echo "</script>";
		}
	}
}
?>

<html>

<head>

<title>Data de envio para o financeiro</title>

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
</style>

</head>

<body>

<?
if (strlen($erro) > 0) {
	$data = trim($_POST["data"]);
	echo "<div class='erro'>$erro</div>";
}
?>

<form name="frm_financeiro" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="extrato" value="<?echo $extrato?>">
<input type="hidden" name="acao">

<center>
<font face="Verdana, Tahoma, Arial" size="2"><b>Informe a nova data</b></font>
<br>
<input type="text" name="data" size="12" maxlength="10" value="<?echo $data?>" class="frm">
<br><br>
<img border="0" src="imagens_admin/btn_confirmar.gif" style="cursor: hand;" onclick="javascript: if (document.frm_financeiro.acao.value == '') { document.frm_financeiro.acao.value='ALTERAR'; document.frm_financeiro.submit(); }else{ alert('Aguarde Submissão...'); }" alt="Clique aqui para alterar a data"><BR><BR>
<B><a href='<?$PHP_SELF;?>?apagar=true&extrato_apagar=<?echo $extrato;?>'>Desejo cancelar o pagamento e voltar o extrato para Extrato Aprovados</a></b>

</center>

</form>

</body>

</html>
