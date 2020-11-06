<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if ($sistema_lingua=='ES') $title = "OS $os Cerrada";
else $title = "OS $os Finalizada";

$layout_menu = 'os';

include "cabecalho.php";

$os = trim($_POST["os"]);
$os = trim($_GET["os"]);

$sql = "SELECT nome,codigo_posto,email FROM tbl_os JOIN tbl_posto_fabrica USING(posto) JOIN tbl_posto USING(POSTO) WHERE os = $os";
//echo $sql;
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$nome           = trim(pg_result($res,$i,nome));
	$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
	$email   = trim(pg_result($res,$i,email));
//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

	$email_origem  = "helpdesk@telecontrol.com.br";
	$email_destino = "pt.garantia@br.bosch.com";
	$assunto       = "Novo OS de Troca de Produto";
	$corpo.="<br>Foi inserido uma nova OS n°$os no sistema TELECONTROL ASSIST.\n\n";
	$corpo.="<br>Chamado n°: $hd_chamado\n\n";
	$corpo.="<br>Codigo do Posto: $codigo_posto<br>Posto: $nome <br>Email: $email\n\n";
	$corpo.="<br><br>Telecontrol\n";
	$corpo.="<br>www.telecontrol.com.br\n";
	$corpo.="<br>_______________________________________________\n";
	$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

	$body_top = "--Message-Boundary\n";
	$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
	$body_top .= "Content-transfer-encoding: 7BIT\n";
	$body_top .= "Content-description: Mail message body\n\n";
//$corpo = $body_top.$corpo;

	if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
		$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
	}else{
		$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
		
	}
}
?>
<style>
.Tabela{
	border:1px solid #d2e4fc;
/*	background-color:<?=$cor;?>;*/
}
</style>
<table width="700" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
	<tr >
		<? if ($sistema_lingua=='ES') { ?>
			<td><img src="imagens/botoes/os.jpg" align='left'> <b><font size='5' color='FF9900'>OS <?=$os?> de câmbio en garantía grabada, favor entrar en contacto con el fabricante para solicitar aprobación.</b></font></td>
		<? } else { ?>
			<td><img src="imagens/botoes/os.jpg" align='left'> <b><font size='5' color='FF9900'>OS <?=$os?> de troca em garantia gravada, favor entrar em contato com o fabricante para solicitar aprovação.</b></font></td>
		<? } ?>
	</tr>
	<tr >
		<? if ($sistema_lingua=='ES') { ?>
			<td><b><font size='1'>* Fue enviado un email para el fabricante su pedido</font></b></td>
		<? } else { ?>
			<td><b><font size='1'>* Foi enviado um email para o fabricante, solicitando seu pedido</font></b></td>
		<? } ?>
	</tr>
</table>
<?include 'rodape.php';?>