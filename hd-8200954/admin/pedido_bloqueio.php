<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_cadastrar = trim($_POST['cadastrar']);
$btn_alterar   = trim($_POST['alterar']);
$btn_promocao  = trim($_POST['promocao']);

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
	$xdata_envio  = formata_data(trim($_POST['data_envio']));

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

//HORA ENVIO
	if(($hora_envio < '0' OR $hora_envio > '24') AND $login_fabrica == 1 ){
		$msg_erro = "Verifique a hora de envio.";
	}
	if(($min_envio < '0' OR $min_envio > '59') AND $login_fabrica == 1){
		$msg_erro = "Verifique os minutos de envio.";
	}

//DATAS
	if(strlen($xdata_inicio) == 'null' ){
		$msg_erro = "Verifique a data de início";
	}
	if(strlen($xdata_fim) == 'null' ){
		$msg_erro = "Verifique a data de fim.";
	}
	if(strlen($xdata_envio) == 'null' AND $login_fabrica == 1){
		$msg_erro = "Verifique a data de fim.";
	}

	$hora_inicio = str_pad($hora_inicio, 2, "0", STR_PAD_LEFT);
	$min_inicio  = str_pad($min_inicio, 2, "0", STR_PAD_LEFT);
	$hora_fim    = str_pad($hora_fim, 2, "0", STR_PAD_LEFT);
	$min_fim     = str_pad($min_fim, 2, "0", STR_PAD_LEFT);
	if($login_fabrica == 1){
		$hora_envio  = str_pad($hora_envio, 2, "0", STR_PAD_LEFT);
		$min_envio   = str_pad($min_envio, 2, "0", STR_PAD_LEFT);
	}

	$conteudo = $xdata_inicio." ".$hora_inicio.":". $min_inicio.":00";
	$conteudo .= ";;";
	$conteudo .= $xdata_fim." ".$hora_fim.":". $min_fim.":00";
	$conteudo .= ";;";
	if($login_fabrica == 1){
		$conteudo .= $xdata_envio." ".$hora_envio.":". $min_envio.":00";
		$conteudo .= ";;";
	}
	$conteudo .= $comunicado;

	if(strlen($msg_erro) == 0){
		if($login_fabrica == 1){
			$file = "bloqueio_pedidos/bloqueia_pedido_black.txt";
		}elseif ($login_fabrica == 30) {
			$file = "bloqueio_pedidos/bloqueia_pedido_esmaltec.txt";
		}

		$abrir = fopen($file, "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}
		fclose($abrir); 
	}

	if(strlen($msg_erro) == 0 AND $login_fabrica == 1){
		//email-->
		$email_origem  = "suporte@telecontrol.com.br";
		$email_destino = "telecontrol@telecontrol.com.br";
		$assunto       = "TRAVAR CRONTAB - PEDIDOS BLACK";
		$corpo.="Verificar - Travar crontab\n\n";
		$corpo.="Foi agendando o bloqueio do site para pedidos de peças e acessórios por parte da Assistência.\n";
		$corpo.="Foi digitado a data de liberação do envio dos pedidos para a fábrica.\n\n";
		$corpo.="Data início de bloqueio: $data_inicio - $hora_inicio : $min_inicio h\n\n";
		$corpo.="Data fim de bloqueio: $data_envio - $hora_envio : $min_envio h\n\n";
		$corpo.="________________________\n";
		$corpo.="Telecontrol\n";
		$corpo.="www.telecontrol.com.br\n";
		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";
		@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
		// fim
	}
}


if(isset($_POST["cadastrar_troca"])){

	$data_inicio_troca  = trim($_POST['data_inicio_troca']);
	$hora_inicio_troca  = trim($_POST['hora_inicio_troca']);
	$min_inicio_troca   = trim($_POST['min_inicio_troca']);

	$data_fim_troca    = trim($_POST['data_fim_troca']);
	$hora_fim_troca    = trim($_POST['hora_fim_troca']);
	$min_fim_troca     = trim($_POST['min_fim_troca']);

	$xdata_inicio_troca = formata_data(trim($_POST['data_inicio_troca']));
	$xdata_fim_troca    = formata_data(trim($_POST['data_fim_troca']));

	//HORA INICIO
	if($hora_inicio_troca < '0' OR $hora_inicio_troca > '24'){
		$msg_erro = "Verifique a hora de início.";
	}
	if($min_inicio_troca < '0' OR $min_inicio_troca > '59'){
		$msg_erro = "Verifique os minutos de início.";
	}

//HORA FIM
	if($hora_fim_troca < '0' OR $hora_fim_troca > '24'){
		$msg_erro = "Verifique a hora de fim.";
	}
	if($min_fim_troca < '0' OR $min_fim_troca > '59'){
		$msg_erro = "Verifique os minutos de fim.";
	}

	$hora_inicio_troca = str_pad($hora_inicio_troca, 2, "0", STR_PAD_LEFT);
	$min_inicio_troca  = str_pad($min_inicio_troca, 2, "0", STR_PAD_LEFT);
	$hora_fim_troca    = str_pad($hora_fim_troca, 2, "0", STR_PAD_LEFT);
	$min_fim_troca     = str_pad($min_fim_troca, 2, "0", STR_PAD_LEFT);

	$conteudo = $xdata_inicio_troca." ".$hora_inicio_troca.":". $min_inicio_troca.":00";
	$conteudo .= ";;";
	$conteudo .= $xdata_fim_troca." ".$hora_fim_troca.":". $min_fim_troca.":00";
	$conteudo .= ";;";
	
	if(strlen($msg_erro) == 0){
		$file = "bloqueio_pedidos/bloqueia_pedido_troca_black.txt";	

		$abrir = fopen($file, "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo";
		}else{
			$ok = "Bloqueio cadastrados com sucesso. ";
		}
		fclose($abrir); 
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

	if(strlen($msg_erro) == 0){
		$abrir = fopen("../bloqueio_pedidos/periodo_fiscal.txt", "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}
		fclose($abrir); 
	}
}


if($btn_promocao == 'Cadastrar'){

	$data_inicio_promocao  = trim($_POST['data_inicio_promocao']);
	$hora_inicio_promocao  = trim($_POST['hora_inicio_promocao']);
	$min_inicio_promocao   = trim($_POST['min_inicio_promocao']);

	$data_fim_promocao    = trim($_POST['data_fim_promocao']);
	$hora_fim_promocao    = trim($_POST['hora_fim_promocao']);
	$min_fim_promocao     = trim($_POST['min_fim_promocao']);

	$xdata_inicio_promocao = formata_data(trim($_POST['data_inicio_promocao']));
	$xdata_fim_promocao    = formata_data(trim($_POST['data_fim_promocao']));

	$comunicado_promocao   = trim($_POST['comunicado_promocao']);
	if(strlen($comunicado_promocao) == 0) $msg_erro = "Digite o Comunicado da Promoção.";

	$comunicado_promocao = nl2br($comunicado_promocao);

//HORA INICIO
	if($hora_inicio_promocao < '0' OR $hora_inicio_promocao > '24'){
		$msg_erro = "Verifique a hora de início.";
	}
	if($min_inicio_promocao < '0' OR $min_inicio_promocao > '59'){
		$msg_erro = "Verifique os minutos de início.";
	}

//HORA FIM
	if($hora_fim_promocao < '0' OR $hora_fim_promocao > '24'){
		$msg_erro = "Verifique a hora de fim.";
	}
	if($min_fim_promocao < '0' OR $min_fim_promocao > '59'){
		$msg_erro = "Verifique os minutos de fim.";
	}

	$hora_inicio_promocao = str_pad($hora_inicio_promocao, 2, "0", STR_PAD_LEFT);
	$min_inicio_promocao  = str_pad($min_inicio_promocao, 2, "0", STR_PAD_LEFT);
	$hora_fim_promocao    = str_pad($hora_fim_promocao, 2, "0", STR_PAD_LEFT);
	$min_fim_promocao     = str_pad($min_fim_promocao, 2, "0", STR_PAD_LEFT);

	$conteudo = $xdata_inicio_promocao." ".$hora_inicio_promocao.":". $min_inicio_promocao.":00";
	$conteudo .= ";;";
	$conteudo .= $xdata_fim_promocao." ".$hora_fim_promocao.":". $min_fim_promocao.":00";
	$conteudo .= ";;";
	$conteudo .= $comunicado_promocao;

	if(strlen($msg_erro) == 0){
		$abrir = fopen("../bloqueio_pedidos/libera_promocao_black.txt", "w+");
		if (!fwrite($abrir, $conteudo)) {
			$msg_erro = "Erro escrevendo no arquivo ($filename)";
		}
		fclose($abrir); 
	}
}


$layout_menu = "callcenter";
$title = "BLOQUEIO DE PEDIDOS E LIBERAÇÃO DA PROMOÇÃO";

?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#ff0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucess{
	font: bold 16px "Arial";
	color:#006400;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.formulario2{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.formulario2 tr td{
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
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

<?php include "../js/js_css.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_inicio_troca").datepick({startDate:'01/01/2000'});
		$("#data_fim_troca").datepick({startDate:'01/01/2000'});

		$("#data_inicio").datepick({startDate:'01/01/2000'});
		$("#data_fim").datepick({startDate:'01/01/2000'});
		$("#data_envio").datepick({startDate:'01/01/2000'});
		$("#data_inicio_fiscal").datepick({startDate:'01/01/2000'});
		$("#data_fim_fiscal").datepick({startDate:'01/01/2000'});
		$("#data_inicio_promocao").datepick({startDate:'01/01/2000'});
		$("#data_fim_promocao").datepick({startDate:'01/01/2000'});

		$("#data_inicio").mask("99/99/9999");
		$("#data_fim").mask("99/99/9999");
		$("#data_envio").mask("99/99/9999");
		$("#data_inicio_fiscal").mask("99/99/9999");
		$("#data_fim_fiscal").mask("99/99/9999");
		$("#data_inicio_promocao").mask("99/99/9999");
		$("#data_fim_promocao").mask("99/99/9999");
	});
</script>

<? if(strlen($msg_erro) > 0){?>
	<p class="msg_erro"><b><? echo $msg_erro; ?></b></p>
<?
}
$msg_erro = '';
?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="titulo_tabela" height="30">
		<td align="center">Dados para <u>Bloqueio</u> do Site</td>
	</tr>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<tr >
		<td colspan='9'>&nbsp;</td>
	</tr>
	<tr>
		<td width="70">&nbsp;</td>

		<td>Data Início</td>
		<td>Hora</td>
		<td>Min</td>

		<td><b>&nbsp;</b></td>

		<td>Data Fim</td>
		<td>Hora</td>
		<td>Min</td>
		<td width="50">&nbsp;</td>

	</tr>
	<tr >
		<td>&nbsp;</td>

		<td><input type="text" name="data_inicio" id="data_inicio" size="12" maxlength='10' value="<?echo $data_inicio?>"    class="frm"></td>
		<td><input type="text" name="hora_inicio"    size="2" maxlength='2' value="<?echo $hora_inicio?>"       class="frm"></td>
		<td><input type="text" name="min_inicio"     size="2" maxlength='2' value="<?echo $min_inicio?>"       class="frm"></td>

		<td>&nbsp;</td>

		<td><input type="text" name="data_fim" id="data_fim"  size="12" maxlength='10' value="<?echo $data_fim?>"    class="frm" ></td>
		<td><input type="text" name="hora_fim"       size="2" maxlength='2' value="<?echo $hora_fim?>"       class="frm"></td>
		<td><input type="text" name="min_fim"        size="2" maxlength='2' value="<?echo $min_fim?>"       class="frm"></td>
		<td>&nbsp;</td>

	</tr>
	<tr >
		<td>&nbsp;</td>

		<td colspan='4'>&nbsp;</td>
		<td>Data Envio</td>
		<td>Hora</td>
		<td>Min</td>
		<td>&nbsp;</td>

	</tr>
	<?php
	if($login_fabrica == 1){
	?>
	<tr>
		<td>&nbsp;</td>

		<td colspan='4' align='right'>Envio de pedido para fábrica:</td>
		<td><input type="text" name="data_envio" id="data_envio"       size="12" maxlength='10' value="<?echo $data_envio?>"    class="frm" ></td>
		<td><input type="text" name="hora_envio"       size="2" maxlength='2' value="<?echo $hora_envio?>"       class="frm"></td>
		<td><input type="text" name="min_envio"        size="2" maxlength='2' value="<?echo $min_envio?>"       class="frm"></td>
		<td>&nbsp;</td>

	</tr>
	<?php
	}
	?>
	<tr >
		<td colspan='9'>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan='7'>Comunicado</td>
		<td>&nbsp;</td>
	</tr>

<?	$xxcomunicado = str_replace("<br />", "", $comunicado); ?>
	<tr>
		<td>&nbsp;</td>
		<td colspan='9'><TEXTAREA NAME="comunicado" ROWS="5" COLS="82" class='frm'><? echo $xxcomunicado ?></TEXTAREA></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='9'><INPUT TYPE="submit" name='cadastrar' value='Cadastrar'></td>
	
	</tr>
</table>
</FORM>

<?php
	if($login_fabrica == 1){
?>
<br>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>		
		<td class="sucess"><center><?php echo $ok ?></center></td>
	</tr>
	<tr class="titulo_tabela" height="30">
		<td align="center">Dados para Bloqueio - pedido de troca</td>
	</tr>
</table>

<table width="700"  align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<tr >
		<td colspan='9'>&nbsp;</td>
	</tr>
	<tr>
		<td width="70">&nbsp;</td>

		<td>Data Início</td>
		<td>Hora</td>
		<td>Min</td>

		<td><b>&nbsp;</b></td>

		<td>Data Fim</td>
		<td>Hora</td>
		<td>Min</td>
		<td width="50">&nbsp;</td>

	</tr>
	<tr >
		<td>&nbsp;</td>

		<td><input type="text" name="data_inicio_troca" id="data_inicio_troca" size="12" maxlength='10' value="<?echo $data_inicio_troca?>"    class="frm"></td>
		<td><input type="text" name="hora_inicio_troca"    size="2" maxlength='2' value="<?echo $hora_inicio_troca?>"       class="frm"></td>
		<td><input type="text" name="min_inicio_troca"     size="2" maxlength='2' value="<?echo $min_inicio_troca?>"       class="frm"></td>

		<td>&nbsp;</td>

		<td><input type="text" name="data_fim_troca" id="data_fim_troca"  size="12" maxlength='10' value="<?echo $data_fim_troca?>"    class="frm" ></td>
		<td><input type="text" name="hora_fim_troca"       size="2" maxlength='2' value="<?echo $hora_fim_troca?>"       class="frm"></td>
		<td><input type="text" name="min_fim_troca"        size="2" maxlength='2' value="<?echo $min_fim_troca?>"       class="frm"></td>
		<td>&nbsp;</td>

	</tr>
	<tr>
		<td> &nbsp;</td>
	</tr>
	<tr>
		<td> &nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='9'><INPUT TYPE="submit" name='cadastrar_troca' value='Cadastrar'></td>
	
	</tr>
	
</table>
</FORM>







<br>

<FORM name="frm_periodo_fiscal" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" >
	<tr class="titulo_tabela" height="30">
		<td align="center">Período Fiscal</td>
	</tr>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<tr>
		<td colspan='9'>&nbsp;</td>
	</tr>
	<tr>
		<td width="120">&nbsp;</td>
		<td>Data Início</td>
		<td>Data Fim</td>
		<td>Hora</td>
		<td>Min</td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr>
		<td width="120">&nbsp;</td>
		<td><input type="text" name="data_inicio_fiscal" id="data_inicio_fiscal"    size="12" maxlength='10' value="<?echo $data_inicio_fiscal?>"    class="frm" ></td>
		<td><input type="text" name="data_fim_fiscal" id="data_fim_fiscal"   size="12" maxlength='10' value="<?echo $data_fim_fiscal?>"       class="frm" ></td>
		<td><input type="text" name="hora_fim_fiscal"       size="2" maxlength='2' value="<?echo $hora_fim_fiscal?>"         class="frm"></td>
		<td><input type="text" name="min_fim_fiscal"        size="2" maxlength='2' value="<?echo $min_fim_fiscal?>"          class="frm"></td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr>
		<td colspan='9'>&nbsp;</td>
	</tr>
	<tr>
		<td colspan='9' align="center"><INPUT TYPE="submit" name='alterar' value='Alterar'></td>
	</tr>

</TABLE>
	
<?	
	$fiscal = fopen("../bloqueio_pedidos/periodo_fiscal.txt", "r");
	$ler_fiscal = fread($fiscal, filesize("../bloqueio_pedidos/periodo_fiscal.txt"));
	fclose($fiscal); 
?>
		<center><font size="1"Último cadastrado:&nbsp;<b><? echo $ler_fiscal; ?></b></font></center>
	
</FORM>

<br>

<form name="frm_libera_promocao" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" style="border:solid 1px;">
	<tr class="texto_avulso" height="30">
		<td align="center"><BR>HD 100300 - Quando liberar a promoção, automaticamente todas as condições de promoção serão mostradas na tela de pedido do posto!</td>
	</tr>
</table>

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<tr class="titulo_tabela">
		<td colspan='9'>Dados para Liberar <u>Promoção</u></td>
	</tr>
	<tr>
		<td width="120">&nbsp;</td>
		<td>Data Início</b></td>
		<td>Hora</td>
		<td>Min</td>

		<td><b>&nbsp;</b></td>

		<td>Data Fim</td>
		<td>Hora</td>
		<td>Min</td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr>
		<td width="120">&nbsp;</td>
		<td><input type="text" name="data_inicio_promocao" id="data_inicio_promocao" size="12" maxlength='10' value="<?echo $data_inicio?>"       class="frm" ></td>
		<td><input type="text" name="hora_inicio_promocao"    size="2"  maxlength='2'  value="<?echo $hora_inicio?>"       class="frm"></td>
		<td><input type="text" name="min_inicio_promocao"     size="2"  maxlength='2'  value="<?echo $min_inicio?>"        class="frm"></td>

		<td>&nbsp;</td>

		<td><input type="text" name="data_fim_promocao" id="data_fim_promocao"  size="12" maxlength='10' value="<?echo $data_fim?>"       class="frm" ></td>
		<td><input type="text" name="hora_fim_promocao"       size="2"  maxlength='2'  value="<?echo $hora_fim?>"       class="frm"></td>
		<td><input type="text" name="min_fim_promocao"        size="2"  maxlength='2'  value="<?echo $min_fim?>"        class="frm"></td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr>
		<td width="120">&nbsp;</td>
		<td colspan='7'><strong>Comunicado Promoção</strong></td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr >
		<td width="120">&nbsp;</td>
		<td colspan='7'><TEXTAREA NAME="comunicado_promocao" ROWS="5" COLS="82" class="frm"><? echo $xcomunicado_promocao ?></TEXTAREA></td>
		<td width="120">&nbsp;</td>
	</tr>
	<tr align='center'>
		<td colspan='9'><INPUT TYPE="submit" name='promocao' value='Cadastrar'></td>
	</tr>
<?
	$abrir = fopen("../bloqueio_pedidos/libera_promocao_black.txt", "r");
	$ler = fread($abrir, filesize("../bloqueio_pedidos/libera_promocao_black.txt"));
	fclose($abrir); 

	$conteudo = explode(";;", $ler);

	$ydata_inicio_promocao = $conteudo[0];
	$ydata_fim_promocao    = $conteudo[1];


	$conteudo_dt        = explode(" ", $ydata_inicio_promocao);
	$dt_inicio_promocao = mostra_data($conteudo_dt[0]);
	$hr_inicio_promocao = $conteudo_dt[1];

	$conteudo_dt     = explode(" ", $ydata_fim_promocao);
	$dt_fim_promocao = mostra_data($conteudo_dt[0]);
	$hr_fim_promocao = $conteudo_dt[1];

?>

	

	<tr>
</table>

		<center><font size="1">Última Promoção Cadastrada: <? echo "&nbsp;<b>$dt_inicio_promocao $hr_inicio_promocao até $dt_fim_promocao $hr_fim_promocao</b>"; ?></font> </center>

</FORM>
<?php
}

if($login_fabrica == 1){
	$file = "../bloqueio_pedidos/bloqueia_pedido_black.txt";
}elseif ($login_fabrica == 30) {
	$file = "../bloqueio_pedidos/bloqueia_pedido_esmaltec.txt";
}
$abrir = fopen($file, "r");
$ler = fread($abrir, filesize($file));
fclose($abrir); 

$conteudo = explode(";;", $ler);

$ydata_inicio = $conteudo[0];
$ydata_fim    = $conteudo[1];
if($login_fabrica == 1){
	$ycomentario  = $conteudo[3];
	$ydata_envio  = $conteudo[2];
}else{
	$ycomentario  = $conteudo[2];
}

$ydata_inicio = explode(" ", $ydata_inicio);
$ydata_fim    = explode(" ", $ydata_fim);
$ydata_envio  = explode(" ", $ydata_envio);

$dt_inicio = mostra_data($ydata_inicio[0]);
$hr_inicio = $ydata_inicio[1];

$dt_fim = mostra_data($ydata_fim[0]);
$hr_fim = $ydata_fim[1];

$dt_envio = mostra_data($ydata_envio[0]);
$hr_envio = $ydata_envio[1];

?>
<br>
<TABLE border = '0'  width='700' align='center' cellspacing='1' cellpadding='0' class="formulario2" >
<TR class="titulo_tabela">
	<TD colspan='2' >Último Comunicado Cadastrado</TD>
</TR>
<TR class="subtitulo">
	<TD><b>Data Início</b></TD>
	<TD><b>Data Fim</b></TD>
</TR>
<TR>
	<TD><b><? echo $dt_inicio ." ". $hr_inicio; ?></b></TD>
	<TD><b><? echo $dt_fim ." ". $hr_fim; ?></b></TD>
</TR>
<TR class="subtitulo">
	<TD colspan='2'><b>Comunicado</b></TD>
</TR>
<TR >
	<TD colspan='2'><p align='justify' style='font-size: 10px'><b><? echo $ycomentario; ?></b></p></TD>
</TR>
<?php
if($login_fabrica == 1){
?>
<TR class="subtitulo">
	<TD colspan='2' ><b>Data do envio dos pedidos</b></TD>
</TR>
<TR>
	<TD colspan='2'><p><b><? echo "$dt_envio às $hr_envio"; ?></b></p></TD>
</TR>
<?php
}
?>
</TABLE>
<?php
if($login_fabrica == 1){
$abrir = fopen("bloqueio_pedidos/libera_promocao_black.txt", "r");
$wler = fread($abrir, filesize("bloqueio_pedidos/libera_promocao_black.txt"));
fclose($abrir); 
echo $wconteudo;
$wconteudo = explode(";;", $wler);

$wdata_inicio = $wconteudo[0];
$wdata_fim    = $wconteudo[1];
$wcomentario  = $wconteudo[2];


$wdata_inicio = explode(" ", $wdata_inicio);
$wdata_fim    = explode(" ", $wdata_fim);
$wdata_envio  = explode(" ", $wdata_envio);

$dt_inicio = mostra_data($wdata_inicio[0]);
$hr_inicio = $wdata_inicio[1];

$dt_fim = mostra_data($wdata_fim[0]);
$hr_fim = $wdata_fim[1];

$dt_envio = mostra_data($wdata_envio[0]);
$hr_envio = $wdata_envio[1];

?>
<br>
<br>
<TABLE border = '0' class='formulario2' width='700' align='center' cellspacing='1' cellpadding='0'>
<TR class="titulo_tabela">
	<TD colspan='2'><b>Última Promoção Cadastrada.</b></TD>
</TR>
<TR class="subtitulo">
	<TD><b>Data Início</b></TD>
	<TD><b>Data Fim</b></TD>
</TR>
<TR >
	<TD><b><? echo $dt_inicio ." ". $hr_inicio; ?></b></TD>
	<TD><b><? echo $dt_fim ." ". $hr_fim; ?></b></TD>
</TR>
<TR class="subtitulo">
	<TD colspan="2"><b>Comunicado</b></TD>
</TR>
<TR >
	<TD colspan='2'><p align='justify' style='font-size: 10px'><b><? echo $wcomentario; ?></b></p></TD>
</TR>
</TABLE>


<?php 

$file = "bloqueio_pedidos/bloqueia_pedido_troca_black.txt";	
$abrir = fopen($file, "r");
$wler = fread($abrir, filesize($file));
fclose($abrir); 
$wconteudo = explode(";;", $wler);

$wdata_inicio = $wconteudo[0];
$wdata_fim    = $wconteudo[1];

$wdata_inicio = explode(" ", $wdata_inicio);
$wdata_fim    = explode(" ", $wdata_fim);

$dt_inicio = mostra_data($wdata_inicio[0]);
$hr_inicio = $wdata_inicio[1];

$dt_fim = mostra_data($wdata_fim[0]);
$hr_fim = $wdata_fim[1];

?>


<br>
<br>
<TABLE border = '0' class='formulario2' width='700' align='center' cellspacing='1' cellpadding='0'>
<TR class="titulo_tabela">
	<TD colspan='2'><b>Último bloqueio - Pedido de Troca.</b></TD>
</TR>
<TR class="subtitulo">
	<TD><b>Data Início</b></TD>
	<TD><b>Data Fim</b></TD>
</TR>
<TR >
	<TD><b><? echo $dt_inicio ." ". $hr_inicio; ?></b></TD>
	<TD><b><? echo $dt_fim ." ". $hr_fim; ?></b></TD>
</TR>
</TABLE>


<?
}
include "rodape.php" 
?>
