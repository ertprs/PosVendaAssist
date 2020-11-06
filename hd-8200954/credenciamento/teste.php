<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_cadastrar = trim($_POST['cadastrar']);
$btn_alterar   = trim($_POST['alterar']);

$msg_erro = "";

if($btn_cadastrar == 'Cadastrar'){

	$data_inicio  = trim($_POST['data_inicio']);
	$hora_inicio  = trim($_POST['hora_inicio']);
	$min_inicio   = trim($_POST['min_inicio']);

	$data_fim    = trim($_POST['data_fim']);
	$hora_fim    = trim($_POST['hora_fim']);
	$min_fim     = trim($_POST['min_fim']);

	$data_envio    = trim($_POST['data_envio']);
	$hora_envio    = trim($_POST['hora_envio']);
	$min_envio     = trim($_POST['min_envio']);


	$comunicado  = trim($_POST['comunicado']);
	
	if(strlen($comunicado) == 0) $msg_erro = "Digite o Comunicado.";

	$comunicado = nl2br($comunicado);

	$xdata_inicio = formata_data(trim($_POST['data_inicio']));
	$xdata_fim    = formata_data(trim($_POST['data_fim']));

//HORA INICIO
	if($hora_inicio < '0' OR $hora_inicio > '24'){
		$msg_erro = "Verifique a hora de início.";
	}
	if($min_inicio < '0' OR $min_inicio > '59'){
		$msg_erro = "Verifique os minutos de início.";
	}

//HORA FIM
	if($hora_fim < '0' OR $hora_fim > '24'){
		$msg_erro = "Verifique a hora de fim.";
	}
	if($min_fim < '0' OR $min_fim > '59'){
		$msg_erro = "Verifique os minutos de fim.";
	}

	if(strlen($xdata_inicio) == 'null' ){
		$msg_erro = "Verifique a data de início";
	}
	if(strlen($xdata_fim) == 'null' ){
		$msg_erro = "Verifique a data de fim.";
	}

	$hora_inicio = str_pad($hora_inicio, 2, "0", STR_PAD_LEFT);
	$min_inicio  = str_pad($min_inicio, 2, "0", STR_PAD_LEFT);
	$hora_fim    = str_pad($hora_fim, 2, "0", STR_PAD_LEFT);
	$min_fim     = str_pad($min_fim, 2, "0", STR_PAD_LEFT);

	$conteudo = $xdata_inicio." ".$hora_inicio.":". $min_inicio.":00";
	$conteudo .= "|";
	$conteudo .= $xdata_fim." ".$hora_fim.":". $min_fim.":00";
	$conteudo .= "|";
	$conteudo .= $comunicado;

/*
	if(strlen($msg_erro) == 0){
		$abrir = fopen("/www/assist/www/bloqueia_pedido_black.txt", "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}
		fclose($abrir); 
	}
*/

	if(strlen($data_envio) > 0 OR strlen($hora_envio) > 0 OR strlen($min_envio) > 0){
		$data_inicio_bloqueio  = $data_inicio." ".$hora_inicio.":". $min_inicio.":00";
		$data_fim_bloqueio     = $data_envio." ".$hora_envio.":". $min_envio.":00";

		//HORA ENVIO
		if($hora_envio < '0' OR $hora_envio > '24' OR strlen($hora_envio) == 0){
			$msg_erro = "Verifique a hora de envio.";
		}
		if($min_envio < '0' OR $min_envio > '59' OR strlen($min_envio) == 0){
			$msg_erro = "Verifique os minutos de envio.";
		}

		if(strlen($msg_erro) == 0){
			//email-->
			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte@telecontrol.com.br";
			$assunto       = "TRAVAR CRONTAB - PEDIDOS BLACK";
			$corpo.="Travar crontab\n\n";
			$corpo.="Foi agendando o bloqueio do site para pedidos de peças e acessórios por parte da Assistência.\n";
			$corpo.="Foi digitado a data de liberação do envio dos pedidos para a fábrica.\n\n";
			$corpo.="Data início de bloqueio: $data_inicio_bloqueio\n\n";
			$corpo.="Data fim de bloqueio: $data_fim_bloqueio\n\n";
			$corpo.="________________________\n";
			$corpo.="Telecontrol\n";
			$corpo.="www.telecontrol.com.br\n";
			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
			@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
			// fim
		}
	}
}

if($btn_alterar == 'Alterar'){

	$data_inicio_fiscal  = trim($_POST['data_inicio_fiscal']);
	$data_fim_fiscal     = trim($_POST['data_fim_fiscal']);
	$hora_fim_fiscal     = trim($_POST['hora_fim_fiscal']);
	$min_fim_fiscal      = trim($_POST['min_fim_fiscal']);

//HORA INICIO
	if($hora_fim_fiscal < '0' OR $hora_fim_fiscal > '24'){
		$msg_erro = "Verifique a hora do período fiscal.";
	}
	if($min_fim_fiscal < '0' OR $min_fim_fiscal > '59'){
		$msg_erro = "Verifique os minutos do período fiscal.";
	}

	$conteudo = $data_inicio_fiscal." até ". $data_fim_fiscal .", às ". $hora_fim_fiscal ."H". $min_fim_fiscal .".";
//	echo "$conteudo";

/*
	if(strlen($msg_erro) == 0){
		$abrir = fopen("/www/assist/www/periodo_fiscal.txt", "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}
		fclose($abrir); 
	}
*/
}


$layout_menu = "callcenter";
$title = "Relação de Pedidos Lançados";

?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script language="JavaScript">

function formata_data_barra(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}
</script>



<? include "cabecalho.php";?>

<? if(strlen($msg_erro) > 0){?>
	<p align='center' style='font-family: verdana; color: #FF0000; font-size: 14px'><b><? echo $msg_erro; ?></b></p>
<?
}
$msg_erro = '';
?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Entre com os dados para bloqueio do site.</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td><b>Data Inicio</b></td>
		<td><b>Hora</b></td>
		<td><b>Min</b></td>

		<td><b>&nbsp;</b></td>

		<td><b>Data Fim</b></td>
		<td><b>Hora</b></td>
		<td><b>Min</b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td><input type="text" name="data_inicio"    size="12" maxlength='10' value="<?echo $data_inicio?>"    class="frm" onKeyUp="formata_data_barra(this.value,'frm_consulta', 'data_inicio')"></td>
		<td><input type="text" name="hora_inicio"    size="2" maxlength='2' value="<?echo $hora_inicio?>"       class="frm"></td>
		<td><input type="text" name="min_inicio"     size="2" maxlength='2' value="<?echo $min_inicio?>"       class="frm"></td>

		<td>&nbsp;</td>

		<td><input type="text" name="data_fim"       size="12" maxlength='10' value="<?echo $data_fim?>"    class="frm" onKeyUp="formata_data_barra(this.value,'frm_consulta', 'data_fim')"></td>
		<td><input type="text" name="hora_fim"       size="2" maxlength='2' value="<?echo $hora_fim?>"       class="frm"></td>
		<td><input type="text" name="min_fim"        size="2" maxlength='2' value="<?echo $min_fim?>"       class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='4'>&nbsp;</td>
		<td><b>Data envio</b></td>
		<td><b>Hora</b></td>
		<td><b>Min</b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='4' align='right'>Envio de pedido para fábrica:</td>
		<td><input type="text" name="data_envio"       size="12" maxlength='10' value="<?echo $data_envio?>"    class="frm" onKeyUp="formata_data_barra(this.value,'frm_consulta', 'data_envio')"></td>
		<td><input type="text" name="hora_envio"       size="2" maxlength='2' value="<?echo $hora_envio?>"       class="frm"></td>
		<td><input type="text" name="min_envio"        size="2" maxlength='2' value="<?echo $min_envio?>"       class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'><b>Comunicado</b></td>
	</tr>
<?	$xxcomunicado = str_replace("<br />", "", $comunicado); ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'><TEXTAREA NAME="comunicado" ROWS="5" COLS="40"><? echo $xxcomunicado ?></TEXTAREA></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'><INPUT TYPE="submit" name='cadastrar' value='Cadastrar'></td>
	</tr>
</table>
</FORM>




<FORM name="frm_periodo_fiscal" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Período Fiscal.</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td><b>Data Inicio</b></td>
		<td><b>Data Fim</b></td>
		<td><b>Hora</b></td>
		<td><b>Min</b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td><input type="text" name="data_inicio_fiscal"    size="12" maxlength='10' value="<?echo $data_inicio_fiscal?>"    class="frm" onKeyUp="formata_data_barra(this.value,'frm_periodo_fiscal', 'data_inicio_fiscal')"></td>
		<td><input type="text" name="data_fim_fiscal"       size="12" maxlength='10' value="<?echo $data_fim_fiscal?>"       class="frm" onKeyUp="formata_data_barra(this.value,'frm_periodo_fiscal', 'data_fim_fiscal')"></td>
		<td><input type="text" name="hora_fim_fiscal"       size="2" maxlength='2' value="<?echo $hora_fim_fiscal?>"         class="frm"></td>
		<td><input type="text" name="min_fim_fiscal"        size="2" maxlength='2' value="<?echo $min_fim_fiscal?>"          class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='7'><INPUT TYPE="submit" name='alterar' value='Alterar'></td>
	</tr>
	<tr>
<?	
	$fiscal = fopen("/www/assist/www/periodo_fiscal.txt", "r");
	$ler_fiscal = fread($fiscal, filesize("/www/assist/www/periodo_fiscal.txt"));
	fclose($fiscal); 
?>
		<td colspan='7' style='font-size: 10px; font-family: verdana;'>&nbsp;<br>Último cadastrado: <b><? echo $ler_fiscal; ?></b></td>
	</tr>
</TABLE>
</FORM>

<?
$abrir = fopen("/www/assist/www/bloqueia_pedido_black.txt", "r");
$ler = fread($abrir, filesize("/www/assist/www/bloqueia_pedido_black.txt"));
fclose($abrir); 

$conteudo = explode("|", $ler);

$ydata_inicio = $conteudo[0];
$ydata_fim    = $conteudo[1];
$ycomentario  = $conteudo[2];

$ydata_inicio = explode(" ", $ydata_inicio);
$ydata_fim    = explode(" ", $ydata_fim);

$dt_inicio = mostra_data($ydata_inicio[0]);
$hr_inicio = $ydata_inicio[1];

$dt_fim = mostra_data($ydata_fim[0]);
$hr_fim = $ydata_fim[1];

?>
<br>
<TABLE border = '0' class='tabela' width='500' align='center' cellspacing='0' cellpadding='0'>
<TR align='center'>
	<TD style='font-size: 14px; font-family: verdana; color: #FFFFFF' bgcolor='#596D9B' colspan='2'><b>Último comunicado cadastrado.</b></TD>
</TR>
<TR bgcolor='#596D9B' align='center' style='font-size: 12px; color: #FFFFFF'>
	<TD><b>Data Inicio</b></TD>
	<TD><b>Data Fim</b></TD>
</TR>
<TR bgcolor="#FFFFFF" align='center' style='font-size: 10px' height='20'>
	<TD><b><? echo $dt_inicio ." ". $hr_inicio; ?></b></TD>
	<TD><b><? echo $dt_fim ." ". $hr_fim; ?></b></TD>
</TR>
<TR bgcolor='#596D9B' align='center'>
	<TD colspan='2' style='font-size: 12px; color: #FFFFFF'><b>Comunicado</b></TD>
</TR>
<TR bgcolor="#FFFFFF" align='center' style='font-size: 10px'>
	<TD colspan='2'><p align='justify' style='font-size: 10px'><b><? echo $ycomentario; ?></b></p></TD>
</TR>
</TABLE>

<?include "rodape.php" ?>