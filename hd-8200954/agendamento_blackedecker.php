<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

include "funcoes.php";

$erro = "";
$msg  = "";

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);

if ($btn_acao == "CONTINUAR") {

	$codigo_posto = trim($_POST["codigo_posto"]);
	$nome   = trim($_POST["nome"]);
	$contato      = trim($_POST["contato"]);
	$data         = fnc_formata_data_pg(trim($_POST["data"]));
	$email        = trim($_POST["email"]);
	$fone         = trim($_POST["fone"]);
	$cidade       = trim($_POST["cidade"]);
	$estado       = trim($_POST["estado"]);
	$tecnico      = trim($_POST["tecnico"]);
	$segmento     = strtoupper(trim($_POST["dewalt"]));

	if (strlen($nome) == 0)     $erro .= " Informe o nome de sua empresa!<br> ";
	if (strlen($contato) == 0)  $erro .= " Informe o contato dentro da empresa!<br> ";
	if (strlen($email) == 0)    $erro .= " Informe o seu email!<br> ";
	if (strlen($fone) == 0)     $erro .= " Informe o seu telefone!<br> ";
	if (strlen($tecnico) == 0)  $erro .= " Informe o nome do técnico a ser treinado!<br> ";
	if (strlen($segmento) == 0) $erro .= " Informe SEGMENTO que sua empresa trabalha!<br> ";

	if ($data == "null") {
		$erro .= " Informe a data do treinamento!<br> ";
	}else{
		$dia    = substr($data,9,2);
		$mes    = substr($data,6,2);
		$ano    = substr($data,1,4);

		if ($dia <= date("d") AND $mes <= date("m") AND $ano <= date("Y")) {
			$erro .= " Data agendada deve ser maior que a data atual!<br> ";
		}
		$data = $dia."/".$mes."/".$ano;
	}

	if (strlen($erro) > 0) {
		$msg  = " <b>Foi detectado o seguinte erro: </b><br> ";
		$msg .= $erro;
	}else{
		##############################
		# MAIL PARA A BLACK & DECKER #
		##############################
		$from_nome	= $nome;
		$from_email	= $email;

		$to_nome	= "Black&Decker - Treinamentos";
		$to_email	= "ureis@blackedecker.com.br";

		$subject	= "Agendamento de treinamento";

		$mensagem	= "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center'>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Empresa</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$codigo_posto - $nome";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Cidade</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$cidade / $estado";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Contato</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$contato";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Telefone</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$fone";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>E-mail</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$email";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Técnico</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$tecnico";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Data treinamento</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$data";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='20%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "<b>Segmento</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "<td width='80%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "$segmento";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "</table>";

		$cabecalho	= "MIME-Version: 1.0\n";
		$cabecalho	.= "Content-type: text/html; charset=iso-8859-1\n";
		$cabecalho	.= "From:"	.$from_nome.	"<"	.$from_email.	">\n";
		$cabecalho	.= "To:"	.$to_nome.		"<"	.$to_email.		">\n";
		$cabecalho	.= "Return-Path: <"	.$from_email.	">\n";
		$cabecalho	.= "X-Priority: 1\n";
		$cabecalho	.= "X-MSMail-Priority: High\n";
		$cabecalho	.= "X-Mailer: PHP/" . phpversion();

		if ( !mail("" , utf8_encode("$subject") , utf8_encode("$mensagem") , "$cabecalho") ) $msg = " Não foi enviado o e-mail! ";

		$from_nome		= "";
		$from_email		= "";
		$to_nome		= "";
		$to_email		= "";
		$cc_nome		= "";
		$cc_email		= "";
		$subject		= "";
		$mensagem		= "";
		$cabecalho		= "";

		#####################
		# MAIL PARA O POSTO #
		#####################
		$from_nome	= "Black&Decker - Treinamentos";
		$from_email	= "ureis@blackedecker.com.br";

		$to_nome	= $nome;
		$to_email	= $email;

		$subject	= "Agendamento de treinamento";

		$mensagem	= "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center'>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='100%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "Caro(a) <b>$nome</b>";
		$mensagem	.= "</font>";
		$mensagem	.= "</td>";

		$mensagem	.= "</tr>";
		$mensagem	.= "<tr>";

		$mensagem	.= "<td width='100%'align='left'>";
		$mensagem	.= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>";
		$mensagem	.= "Agradecemos seu interesse em participar de nosso treinamento técnico.";
		$mensagem	.= "<br>";
		$mensagem	.= "A data do treinamento será confirmada em breve.";

		$mensagem	.= "</tr>";
		$mensagem	.= "</table>";

		$cabecalho	= "MIME-Version: 1.0\n";
		$cabecalho	.= "Content-type: text/html; charset=iso-8859-1\n";
		$cabecalho	.= "From:"	.$from_nome.	"<"	.$from_email.	">\n";
		$cabecalho	.= "To:"	.$to_nome.		"<"	.$to_email.		">\n";
		$cabecalho	.= "Return-Path: <"	.$from_email.	">\n";
		$cabecalho	.= "X-Priority: 1\n";
		$cabecalho	.= "X-MSMail-Priority: High\n";
		$cabecalho	.= "X-Mailer: PHP/" . phpversion();

		if ( !mail("" , utf8_encode("$subject") , utf8_encode("$mensagem") , "$cabecalho") ) $msg = " Não foi enviado o e-mail! ";

		$tecnico	= "";
		$data		= "";

		$from_nome		= "";
		$from_email		= "";
		$to_nome		= "";
		$to_email		= "";
		$subject		= "";
		$mensagem		= "";
		$cabecalho		= "";

		header ("Location: $PHP_SELF?mail=ok");
	}
}


if ($mail == "ok") {
	$msg = " Sua mensagem foi enviada com sucesso!<br>Se tiver mais técnicos a serem treinados, preencha o formulário novamente. ";
}

$title = "Agendamento de treinamento";
$layout_menu = "tecnica";

include "cabecalho.php";

if (strlen($erro) == 0) {
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome                 ,
					tbl_posto.contato              ,
					tbl_posto.email                ,
					tbl_posto_fabrica.contato_fone_comercial   AS fone,
					tbl_posto.cidade               ,
					tbl_posto.estado
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto.posto = $login_posto;";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$codigo_posto = pg_result($res,0,codigo_posto);
		$nome         = pg_result($res,0,nome);
		$contato      = pg_result($res,0,contato);
		$email        = pg_result($res,0,email);
		$fone         = pg_result($res,0,fone);
		$cidade       = pg_result($res,0,cidade);
		$estado       = pg_result($res,0,estado);
	}
	$data = date("d/m/Y");
}else{
	$posto_codigo = $_POST["posto_codigo"];
	$nome         = $_POST["nome"];
	$contato      = $_POST["contato"];
	$email        = $_POST["email"];
	$fone         = $_POST["fone"];
	$cidade       = $_POST["cidade"];
	$estado       = $_POST["estado"];
	$tecnico      = $_POST["tecnico"];
	$data         = $_POST["data"];
	$dewalt       = $_POST["dewalt"];
}
?>

<br>

<table width="650" border="0" cellpadding="2" cellspacing="2">
<tr>
	<td align="center" width="100%">
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">Por favor, preencha os campos abaixo.</font>
	</td>
</tr>
<tr>
	<td align="center" width="100%">
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><a href="http://www.blackdecker.com.br/xls/treinamento-dewalt-2005.xls" target='_blank'><b>Cronograma de Treinamento DeWalt 2005</b></a><br>Clique acima para ver as datas disponíveis e toda a programação do treinamento.</font>
	</td>
</tr>
</table>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="650" border="0" cellpadding="2" cellspacing="2" class="Error">
<tr>
	<td align="center" width="100%">
		<b><?echo $msg;?></b>
	</td>
</tr>
</table>
<br>
<? } ?>

<form name="frm_treinamento" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="cidade" value="<?echo $cidade?>">
<input type="hidden" name="estado" value="<?echo $estado?>">
<input type="hidden" name="btn_acao" value="">

<table width="650" border="0" cellpadding="2" cellspacing="0">
<tr>
	<td width="16%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Posto</font><br>
		<input type="text" name="codigo_posto" size="10" maxlength="10" value="<?echo $codigo_posto?>">
	</td>
	<td width="42%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Posto</font><br>
		<input type="text" name="nome" size="40" maxlength="50" value="<?echo $nome?>">
	</td>
	<td width="42%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Contato</font><br>
		<input type="text" name="contato" size="40" maxlength="50" value="<?echo $contato?>">
	</td>
</tr>
</table>

<table width="650" border="0" cellpadding="2" cellspacing="0">
<tr>
	<td width="16%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do treinamento</font><br>
		<input type="text" name="data" size="11" maxlength="10" value="<?echo $data?>">
	</td>
	<td width="42%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">E-mail</font><br>
		<input type="text" name="email" size="30" maxlength="50" value="<?echo $email?>">
	</td>
	<td width="42%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone</font><br>
		<input type="text" name="fone" size="15" maxlength="15" value="<?echo $fone?>">
	</td>
</tr>
</table>

<table width="650" border="0" cellpadding="2" cellspacing="2">
<tr>
	<td width="42%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do técnico a ser treinado</font><br>
		<input type="text" name="tecnico" size="30" maxlength="50" value="<?echo $tecnico?>">
	</td>
	<td width="42%">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Atende quais segmentos</font><br>
		<input type="checkbox" name="dewalt" value="dewalt" <? if ($dewalt == "dewalt") echo "checked" ?>> <font size="2" face="Vardana, Tahoma, Geneva, Arial, Helvetica, san-serif"><b>DEWALT</b></font>
	</td>
	<td width="16%">
		&nbsp;
	</td>
</tr>
</table>

<br>

<center><img border="0" src="imagens/btn_continuar.gif" onclick="document.frm_treinamento.btn_acao.value = 'CONTINUAR'; document.frm_treinamento.submit();" style="cursor: hand;"></a></center>

</form>

<? include "rodape.php" ?>