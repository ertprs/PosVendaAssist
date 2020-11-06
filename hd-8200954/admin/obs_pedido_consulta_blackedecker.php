<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";


if (strlen(trim($_GET["pedido"])) > 0)  $pedido = trim($_GET["pedido"]);
if (strlen(trim($_POST["pedido"])) > 0) $extrato = trim($_POST["pedido"]);

if ($acao == "ALTERAR") {
	$x_obs = trim($_POST["obs"]);
	$titulo_email = trim($_POST["titulo_email"]);
	if (strlen($titulo_email) == 0) $erro .= " Preencha o título do email. ";
	if (strlen($x_obs) == 0)		$erro .= " Preencha o campo Observação. ";
	
	if (strlen($erro) == 0) {
		$sql = "UPDATE tbl_pedido set obs='$x_obs' where pedido=$pedido";
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);

		if (strlen($erro) == 0) {
		
			$xsql = "SELECT tbl_posto_fabrica.contato_email as email,
							tbl_posto_fabrica.codigo_posto          ,
							tbl_pedido.pedido_blackedecker          
					from tbl_posto_fabrica
					join tbl_pedido on tbl_posto_fabrica.posto = tbl_pedido.posto 
					where pedido=$pedido
					and   tbl_posto_fabrica.fabrica=$login_fabrica";
			$xres = pg_exec($con,$xsql);
			$xemail_posto  = pg_result($xres,0,email);
			$xcodigo_posto = pg_result($xres,0,codigo_posto);
			$xpedido       = pg_result($xres,0,pedido_blackedecker);

			$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin ";
			$xres = pg_exec($con,$xsql);
			$xnome_completo = pg_result($xres,0,nome_completo);
			$xfone = pg_result($xres,0,fone);
			$xemail_admin = pg_result($xres,0,email);

			$remetente    = "Black&Decker <$xemail_admin>"; 
			$destinatario = "$xemail_posto"; 
			$assunto      = $titulo_email;
			$mensagem     = "Prezado Posto Autorizado,<BR><BR>
			Você recebeu o email de observação sobre o pedido $xpedido. <BR><BR><b>Obs:</b><BR>".nl2br($x_obs)."<BR><BR>"; 
			$headers="Return-Path: <$xemail_admin>\nFrom:".$remetente."\nBcc:$xemail_admin \nContent-type: text/html\n"; 
			
			if ( mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers) ) {
				echo "<div class='sucesso'>Foi mandado o email de observação do pedido para $destinatario</div>";
			}else{
				echo "erro";
			}
		}
	}
}
?>

<html>

<head>

<title>Observação do Pedido</title>
<script type="text/javascript" src="js/BubbleTooltips.js"></script>
<script type="text/javascript">
window.onload=function(){enableTooltips()};
</script>
<style>
.sucesso {
  color: #000000;
  text-align: center;
  font: bold 12px Verdana, Arial, Helvetica, sans-serif;
  background-color: #d0e0f6;
}

.obs {
  color: #000000;
  text-align: center;
  font: bold 12px Verdana, Arial, Helvetica, sans-serif;
  background-color: #efeeea;
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
<form name="frm_pedido" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="pedido" value="<?echo $pedido?>">
<input type="hidden" name="acao">
<?

$sql =	"SELECT TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_abertura  ,
				TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY')     AS data_finalizado ,
				tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
				tbl_posto.nome                                 AS posto_nome   ,
				tbl_pedido.obs                                                
		FROM tbl_pedido
		JOIN tbl_posto          ON  tbl_posto.posto           = tbl_pedido.posto
		JOIN tbl_posto_fabrica  ON  tbl_pedido.posto         = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_pedido.pedido = $pedido;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$data_abertura   = trim(pg_result($res,0,data_abertura));
	$data_finalizado = trim(pg_result($res,0,data_finalizado));
	$posto_codigo    = trim(pg_result($res,0,posto_codigo));
	$posto_nome      = trim(pg_result($res,0,posto_nome));
	$obs             = trim(pg_result($res,0,obs));
	$posto_completo  = $posto_codigo . " - " . $posto_nome;
}

if (strlen($erro) > 0) {
	echo "<div class='erro'>$erro</div>";
}
?>


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
		<td  width='50%' colspan="2"><b>Data Abertura</b></td>
		<td width='50%'><b>Data Finalizado</b></td>
	</tr>
	<tr>
		<td width='50%' colspan="2"><?echo $data_abertura?></td>
		<td width='50%'><?echo $data_finalizado?></td>
	</tr>
</table><BR>
<?
if (strlen($obs) > 0 and strlen($erro)==0) {
	echo "<div class='obs' id='obs'>Obs: $obs</div>";
}
?>
<? if(strlen($obs)==0) { ?>
<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	
	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td width='25%'  nowrap><b>Título do email:</b></td>
		<td width='75%' colspan="2" height="5"><input type="text" name="titulo_email" size='50' maxlength='50' value=<? echo $titulo_email; ?> ></td>
	</tr>
	<tr>
		<td width='25%' valign="top"><b>Obs.:</b></td>
		<td width='75%' colspan="2"><textarea name="obs" cols='50' rows='10' ><?echo $obs?></textarea></td>
	</tr>
	
</table>
<br>

	<center id='botao'>
	<img border="0" src="imagens_admin/btn_confirmar.gif" style="cursor: hand;" onclick="javascript: if (document.frm_pedido.acao.value == '') { document.frm_pedido.acao.value='ALTERAR'; document.frm_pedido.submit(); }else{ botao.innerHTML='Aguarde o processamento...'; }" alt="Clique aqui para inserir a obs.">
	</center>
<? } ?>
</form>
</body>
</html>
