<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if(strlen($_POST["tipo_linha"]) > 0) $tipo_linha = trim($_POST["tipo_linha"]);
if(strlen($_GET["tipo_linha"]) > 0)  $tipo_linha = trim($_GET["tipo_linha"]);

if(strlen($_POST["submit"]) > 0)     $submit = trim($_POST["submit"]);

if (strlen($submit) > 0) {
	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.cidade, tbl_posto.estado, tbl_posto.fone, tbl_posto.email
			FROM tbl_posto 
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto.posto = $login_posto";
	$res = pg_exec($con,$sql);
	
	$codigo_posto  = pg_result($res,0,codigo_posto);
	$nome          = pg_result($res,0,nome);
	$cidade        = pg_result($res,0,cidade);
	$estado        = pg_result($res,0,estado);
	$fone          = pg_result($res,0,fone);
	$email         = pg_result($res,0,email);
	
	if(strlen($_POST["data"]) > 0) $data = trim($_POST["data"]);
	else                           $data = "data a confirmar";

	$email_origem  = "$email";
	$email_destino = "michel_clemente@blackedecker.com.br";
	$assunto       = "Agendamento de Treinamento $tipo_linha - Site";
	$corpo         = " Em ".date('d/m/Y')."
--------------------------------------------------

Tenho interesse em participar de um dos treinamentos
da linha $tipo_linha em $data.

Posto: $codigo_posto - $nome
Cidade/Estado: $cidade / $estado
Telefone: $fone
email: $email       

--------------------------------------------------

	";
	
	if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem) ){
		echo "Agendamento enviado para a Black & Decker.";
		echo "<a href=\"javascript:window.close();\">Fechar janela</a>";
	}else{
		echo "Não foi possível enviar o email. Por favor entre em contato com a Black & Decker.";
	}
	exit;
}

if (strlen($tipo_linha) > 0) {
	echo "<h2><center>Treinamento Linha $tipo_linha </center></h2>\n";
	echo "<form name=\"form\" method=\"post\" action=\"$PHP_SELF\">\n";
	echo "<table width=\"350\" border=\"0\" cellpadding=\"2\" cellspacing=\"2\" align=\"center\">\n";
	echo "<tr>\n";
	echo "<td height='50'>Para confirmar as datas previstas para treinamento, cofira na tabela abaixo.</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td>Digite a data que você tem interesse em receber o treinamento:</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><INPUT size=\"12\" class ='frm' maxlength=\"10\" TYPE=\"text\" NAME=\"data\" value=\"\"> Formato dd/mm/aaaa</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><input type=\"submit\" name=\"submit\" value=\"Confirmar dados para Inscrição\"></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<input type='hidden' name='tipo_linha' value='$tipo_linha'>\n";
	echo "</form>\n";
	
	if($tipo_linha=="DeWALT") include"treinamento_completo.php";
	if($tipo_linha=="Hammer") include"treinamento_martelos.php";
	if($tipo_linha=="Compressores") include"treinamento_compressores.php";
	exit;
}




$layout_menu = "tecnica";
$title = "Treinamentos";

include "cabecalho.php";

?>

<script language="JavaScript">

function AbreTreinamento(tipo_linha){
	janela = window.open("treinamento.php?tipo_linha=" + tipo_linha,"tela",'scrollbars=yes,width=500,height=600,top=0,left=0');
	janela.focus();
}

</script>
<style type="text/css">

.tbl {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
   text-align:justify;
}
.azul{
	color:#0066CC;
}
.frm {
	BORDER-RIGHT: #888888 1px solid;
	BORDER-TOP: #888888 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: #888888 1px solid;
	BORDER-BOTTOM: #888888 1px solid;
	FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif;
	BACKGROUND-COLOR: #f0f0f0
}
.tblbranca {
	font-family: Arial, Helvetica, sans-serif;
	color:#000000;
	font-size: 11px;
   text-align:justify;
}
</style>


<?
/*
$tipo_linha='DeWALT';
echo "<a href='javascript:AbreTreinamento(\"$tipo_linha\")'>Agendar Treinamento DeWALT</a><br>";
$tipo_linha='Hammer';
echo "<a href='javascript:AbreTreinamento(\"$tipo_linha\")'>Agendar Treinamento Hammer(Martelos)</a><br>";
$tipo_linha='Compressores';
echo "<a href='javascript:AbreTreinamento(\"$tipo_linha\")'>Agendar Treinamento Compressores</a><br>";
*/
?>
<br>
<table width="700" bgcolor="FFFFFF" bordercolor="#FFFF00" border="1" cellspacing="0" cellpadding="0">
	<tr>
		<td>
	<table width="100%"  border="0" cellspacing="2" cellpadding="2" class="tbl" bgcolor="FFFFFF" align="center">
		<tr class="tblbranca ">
			<td width="20%" align="center"style="CURSOR: hand;" onmouseover="this.style.backgroundColor='#FFFF00';this.style.cursor='hand';" onmouseout="this.style.backgroundColor='#ffffff';"><b>Objetivos dos Treinamentos</b></td>
		</tr>
		<tr class="tblbranca ">
			<td width="20%" align="center"style="CURSOR: hand;" onmouseover="this.style.backgroundColor='#FFFF00';this.style.cursor='hand';" onmouseout="this.style.backgroundColor='#ffffff';"><b>Treinamentos e Agendamentos</b></td>
		</tr>
		<tr class="tblbranca ">
			<td width="20%" align="center"style="CURSOR: hand;" onmouseover="this.style.backgroundColor='#FFFF00';this.style.cursor='hand';" onmouseout="this.style.backgroundColor='#ffffff';"><b>Fotos dos Treinamentos</b></td>
		</tr>
		<tr class="tblbranca ">
			<td width="20%" align="center"style="CURSOR: hand;" onmouseover="this.style.backgroundColor='#FFFF00';this.style.cursor='hand';" onmouseout="this.style.backgroundColor='#ffffff';"><b>Informações para Manutenção</b></td>
		</tr>
	</table>
	<table width="600"  border="0" cellspacing="2" cellpadding="2" class="tbl" bgcolor="FFFFFF" align="center">
		<tr>
			<td width="33%" valign="top" bgcolor="#FFCC00">
				<table width="100%" height="100%" cellpadding="0" cellspacing="0" class="tbl">
					<tr>
						<td>
							<b><center>TREINAMENTO DE TODA LINHA DE FERRAMENTAS EL&Eacute;TRICAS DeWALT</center></b><br>
							<b>Tempo de dura&ccedil;&atilde;o:</b> 1 semana - 40hs. <br>
							<b>Descri&ccedil;&atilde;o:</b> Treinamento intensivo de toda linha de ferramentas el&eacute;tricas DeWALT, focado nos processos de montagem e desmontagem dos produtos, defeitos e suas causas, diagn&oacute;stico de falhas e utiliza&ccedil;&atilde;o de ferramentas especiais para manuten&ccedil;&atilde;o.<br>
							<br>
							Este curso lhe prov&ecirc; tamb&eacute;m princ&iacute;pios de eletricidade, caracter&iacute;sticas t&eacute;cnicas e tipos de aplica&ccedil;&otilde;es dos produtos.<br>
							<br>
							<b>Local de realiza&ccedil;&atilde;o:</b> Centro de Excel&ecirc;ncia da Black & Decker do Brasil em Uberaba - MG.<br>
							<br>
							Datas dispon&iacute;veis para o curso em anexo.
						</td>
					</tr>
					<tr>
						<td valign="bottom" align="center"><? $tipo_linha='DeWALT';?>
							<form name="form1" method="post" action='javascript:AbreTreinamento("<? echo $tipo_linha ?>")'>
								<input type="submit" name="submit1" value="Agendar">
							</form>
						</td>
					</tr>
				</table>
			</td>
			<td width="33%" valign="top" bgcolor="#FF9900">
				<table width="100%" height="100%"   cellpadding="0" cellspacing="0" class="tbl">
					<tr>
						<td><center><b>TREINAMENTO DE HAMMER&acute;S</b></center><br>
							<b>Tempo de dura&ccedil;&atilde;o:</b> 3 dias - 24hs <br>
							<b>Descri&ccedil;&atilde;o:</b> Treinamento somente da linha de Martelos DeWALT, focado nos processos de montagem e desmontagem dos martelos, defeitos e suas causas, diagn&oacute;stico de falhas e utiliza&ccedil;&atilde;o de ferramentas especiais para manuten&ccedil;&atilde;o.<br>
							<br>
							<b>Local de realiza&ccedil;&atilde;o:</b> Centro de Excel&ecirc;ncia da Black & Decker do Brasil em Uberaba - MG<br>
							<br>
							Datas dispon&iacute;veis para o curso em anexo.</td>
					</tr>
					<tr>
						<td valign="bottom" align="center"><?$tipo_linha='Hammer';?>
						<form name="form1" method="post" action='javascript:AbreTreinamento("<? echo $tipo_linha ?>")'>
							<input type="submit" name="submit2" value="Agendar">
						</form>
						</td>
					</tr>
				</table>
			</td>
			<td width="33%" valign="top" bgcolor="#FF6600"> <b>
				<table width="100%" height="100%"  cellpadding="0" cellspacing="0" class="tbl">
					<tr>
						<td><b><center>TREINAMENTO DE COMPRESSORES</center></b><br>
							<b>Tempo de dura&ccedil;&atilde;o:</b> 3 dias - 24hs <br>
							<b>Descri&ccedil;&atilde;o:</b> Treinamento focado na linha de compressores DeWALT e Black & Decker. <br>
							Este curso lhe prov&ecirc; princ&iacute;pios de funcionamento e dimensionamento, processos de montagem e desmontagem dos compressores, defeitos e suas causas e diagn&oacute;sticos de falhas.<br>
							<br>
							<b>Local de realiza&ccedil;&atilde;o:</b> Centro de Excel&ecirc;ncia da Black & Decker do Brasil em Uberaba - MG.<br>
							<br>
							Datas dispon&iacute;veis para o curso em anexo. <br>
							<span class="azul"><br>
							obs* O treinamento de compressores &eacute; voltado somente para postos que s&atilde;o autorizados a atender compressores.</span> 
						</td>
					</tr>
					<tr height="10%">
						<td valign="bottom" align="center"><?$tipo_linha='Compressores';?>
							<form name="form1" method="post" action='javascript:AbreTreinamento("<? echo $tipo_linha ?>")'>
								<input type="submit" name="submit3" value="Agendar">
							</form>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<table width="600"  border="0" cellspacing="1" cellpadding="1" class="tbl" align="center">
		<tr class="tblbranca ">
			<td valign="top"><b>DESPESAS DOS TREINAMENTOS:</b><br>
			<br>
			As despesas de hospedagem, refei&ccedil;&atilde;o e translado interno (hotel/cia - cia/hotel), s&atilde;o pagas pela Black & Decker do Brasil, despesas com transporte rodovi&aacute;rio dever&atilde;o ser pagas pelo posto autorizado. <br></td>
		</tr>
		<tr class="tblbranca ">
			<td valign="top"><br><b>OBJETIVO DOS TREINAMENTOS:</b><br>
			<br>
			Os treinamentos DeWALT tem o intuito de buscar uma melhor capacita&ccedil;&atilde;o t&eacute;cnica e atualizar os conhecimentos dos mec&acirc;nicos devido o constante crescimento tecnol&oacute;gico de nossas ferramentas. Um atendimento p&oacute;s venda de qualidade &eacute; de suma import&acirc;ncia para o crescimento de nossa marca nos neg&oacute;cios, devido a isto se torna impressind&iacute;vel a participa&ccedil;&atilde;o de todos os postos autorizados nos treinamentos, para que toda nossa rede seja CERTIFICADA DeWALT. <br>
			Para fazer as reservas de acordo com as datas dispon&iacute;veis em anexo, entre em contato com Michel Clemente atrav&eacute;s dos contatos: fone: (34) 3318-3186 - e-mail: mclemente@blackedecker.com.br, ou para melhores esclarecimentos.<br>
			<br>
			FA&Ccedil;AM J&Aacute; SUAS RESERVAS, POIS AS VAGAS S&Atilde;O LIMITADAS!!!</td>
		</tr>
		<tr class="tblbranca ">
			<td valign="top"><br><strong>COPA DO MUNDO 2006!!!</strong><br>
			<br>
			Para as datas que coinciderem com as datas de jogos do BRASIL, ser&aacute; feito uma pausa no treinamento para que todos possam assistir ao jogo em um ambiente todo preparado para o dia do jogo.
			</td>
		</tr>
	</table>
</td>
</tr>
</table>
</BODY>
</HTML>