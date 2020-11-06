<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';




##### VERIFICAÇÃO SE O POSTO JÁ LEU O COMUNICADO - INÍCIO #####
/*
$sql =	"SELECT tbl_comunicado.comunicado                                       ,
				tbl_comunicado.descricao                                        ,
				tbl_comunicado.mensagem                                         ,
				tbl_comunicado.extensao                                         ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
				tbl_comunicado.produto                                          ,
				tbl_produto.referencia                    AS produto_referencia ,
				tbl_produto.descricao                     AS produto_descricao
		FROM tbl_comunicado
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		LEFT JOIN tbl_comunicado_posto_blackedecker ON  tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
													AND tbl_comunicado_posto_blackedecker.fabrica    = $login_fabrica
													AND tbl_comunicado_posto_blackedecker.posto      = $login_posto
		WHERE tbl_comunicado.fabrica = $login_fabrica
		AND   tbl_comunicado.obrigatorio_site IS TRUE
		AND   tbl_comunicado_posto_blackedecker.posto IS NULL
		ORDER BY tbl_comunicado.data DESC;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	header("Location: comunicado_site.php");
	exit;
}
*/
##### VERIFICAÇÃO SE O POSTO JÁ LEU O COMUNICADO - FIM #####

##### Comunicados Mondial #####
# RETIRADO DIA 02/12/2005 12H - PEDIDO PELA FERNANDA
/*
if (strlen($_COOKIE["ComunicadoMondial20050929"]) == 0 AND $login_fabrica == 5) {
	header("Location: comunicado_mondial_20050929.php");
	exit;
}
*/

##### Comunicados Britânia #####
setcookie("CookieNavegador", "Aceita");
if (strlen($_COOKIE["CookieNavegador"]) > 0) {
/*
if (strlen($_COOKIE["ComunicadoBritania"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050719.php");
	exit;
}

if (strlen($_COOKIE["ComunicadoBritania20050923"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050923.php");
	exit;
}
if (strlen($_COOKIE["ComunicadoBritania20060102"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20060102.php");
	exit;
}
*/
}







/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Telecontrol ASSIST - Gerenciamento de Assistência Técnica";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';


?>
<?


############# Leitura Obrigatória de Comunicados #############

$comunicado_lido = $_GET['comunicado_lido'];
if (strlen ($comunicado_lido) > 0) {
	$sql = "SELECT comunicado 
			FROM tbl_comunicado_posto_blackedecker 
			WHERE comunicado = $comunicado_lido
			AND   posto      = $login_posto";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) == 0){
		$sql = "INSERT INTO tbl_comunicado_posto_blackedecker (comunicado, posto, data_confirmacao) VALUES ($comunicado_lido, $login_posto, CURRENT_TIMESTAMP)";
	}else{
		$sql = "UPDATE tbl_comunicado_posto_blackedecker SET 
					data_confirmacao = CURRENT_TIMESTAMP 
				WHERE  comunicado = $comunicado_lido
				AND    posto      = $login_posto";
	}
	$res = @pg_exec ($con,$sql);

	$sql = "SELECT remetente_email FROM tbl_comunicado WHERE comunicado = $comunicado_lido AND posto IS NOT NULL AND remetente_email IS NOT NULL";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$remetente_email = pg_result ($res,0,0);
		#----------- Enviar email de Confirmação de Leitura -----------#
	}
}

$sql = "SELECT  tbl_comunicado.comunicado   ,
				tbl_comunicado.descricao    ,
				tbl_comunicado.extensao     ,
				tbl_comunicado.mensagem     ,
				TO_CHAR (tbl_comunicado.data, 'DD/MM/YYYY')
		FROM   tbl_comunicado
		LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado AND tbl_comunicado_posto_blackedecker.posto = $login_posto
		WHERE  tbl_comunicado.fabrica = $login_fabrica
		AND    tbl_comunicado.obrigatorio_site
		AND    tbl_comunicado.data >= CURRENT_DATE - INTERVAL '30 days'
		AND   (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
		AND    (tbl_comunicado_posto_blackedecker.data_confirmacao < CURRENT_DATE - INTERVAL '3 DAYS' 
			OR  tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL )";
$res = pg_exec ($con,$sql);

#echo $_SERVER['REMOTE_ADDR'];

if (pg_numrows ($res) > 0 ) {
	echo "<br><br>
	<table style='font-family: verdana, arial ;  font-size: 16px; border-style: dotted; border-width: 2px; border-color: #330000; background-color: #FFFFFF;' width='600' border='0' align='center' cellpadding='0' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<TR class='Titulo'>";
	echo "	<TD colspan='2' align='center' nowrap height='30' ><FONT SIZE='3' COLOR='#FF0000'><B>Existem comunicados de leitura obrigatória!</B></FONT></TD>";
	echo "</TR>";
	echo "</table>";
	echo "<br>
		<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
	echo "<TR align='center' bgcolor='#336699'>";
	echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADOS</B></TD>";
	echo "</TR>";
	echo "<TR align='center' style='font-family: verdana, arial ; font-size: 10px; color:#FFFFFF' bgcolor='#336699'>";
	echo "	<TD><B>Nr.</B></TD>";
	echo "	<TD><B>Descrição</B></TD>";
	echo "	<TD><B>Confirmação</B></TD>";
	echo "</TR>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$comunicado = pg_result ($res,$i,comunicado);
		$extensao   = pg_result ($res,$i,extensao);
		$descricao  = pg_result ($res,$i,descricao);
		$mensagem   = pg_result ($res,$i,mensagem);
		echo "<TR align='center' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "	<TD>$comunicado</TD>";
		echo "	<TD><a href=\"javascript: window.open ('/assist/comunicados/$comunicado.$extensao','_blank', 'toolbar=no, status=no, scrollbars=yes, resizable=yes, width=700, height=500') ; window.location='$PHP_SELF?comunicado_lido=$comunicado' \" ><B>$descricao</B> <br></a> $mensagem</TD>";
		echo "	<TD nowrap><a href=\"javascript: window.location='$PHP_SELF?comunicado_lido=$comunicado' \" ><B>Já li e confirmo</b></TD>";
		echo "</TR>";
	}
	echo "</table>";
	echo "<br><table align='center'>";
	echo "<TR>";
	echo "<TD align='center' colspan='3' nowrap><br><FONT SIZE='2' COLOR='919597'>*Clique no(s) comunicado(s) para acessar o site.</FONT></TD>";
	echo "</TR>";
	echo "</table>";
	exit;
}

#############################################



include 'cabecalho_login.php';





?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<hr>
<h1><? echo $login_nome ?></h1>
<?
	echo "<table width='600' align='center' border='0' align='center'>";
	echo "<tr>";
	echo "<td align='center'>"

?>

<?
	if (trim($login_credenciamento) == "EM DESCREDENCIAMENTO") echo "<div class='error'>$login_credenciamento</div>";
?>

<!-- AQUI VAI INSERIDO OS RELATÓRIOS E OS FORMS -->



<!--
<br>
<center>
<img src='imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Concluída migração para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreensão.
<br>Agora com a migração para o iDC EMBRATEL teremos
<br>um site mais veloz, robusto e confiável.
</font>
<p>
</center>
-->

<?
#------------------------ Média de Peças por OS   e  Custo Médio por OS --------------

#include "custo_medio_include.php";
?>


<div id="container"><h2><IMG SRC="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" ALT="Bem Vindo!!!"></h2></div>
<?
/*
##### VERIFICAÇÃO PARA OS COM PEÇA PARA PREVISÃO DE ENTREGA #####
$sql =	"SELECT COUNT(tbl_os.sua_os) AS qtde_os
		FROM   tbl_os
		JOIN   tbl_os_produto    ON  tbl_os_produto.os       = tbl_os.os
		JOIN   tbl_os_item       ON  tbl_os_item.os_produto  = tbl_os_produto.os_produto
		JOIN   tbl_peca          ON  tbl_peca.peca           = tbl_os_item.peca
		JOIN   tbl_produto       ON  tbl_produto.produto     = tbl_os.produto
		WHERE  tbl_os.fabrica = $login_fabrica
		AND    tbl_os.posto           = $login_posto
		AND    tbl_peca.previsao_entrega > date(current_date + INTERVAL '20 days')
		AND    tbl_os.finalizada ISNULL ;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	if (pg_result($res,0,0) > 0) {
		echo "<div id='mainCol'><div class='contentBlockLeft' style='background-color: #FFE1E1;'>";
		echo "<img src='imagens/esclamachion1.gif'><a href='os_peca_previsao_entrega.php'>Confira as OS com peça para previsão de entrega maior que 20 dias.<br> Foram encontradas ".pg_result($res,0,0)." OS.</a>";
		echo "</div></div>";
	}
}
*/


#-------------- Validação Periódica de EMAIL -------------------

$sql = "SELECT tbl_posto.email, nome, tbl_posto.email_validado
		FROM tbl_posto
		WHERE tbl_posto.posto =  $login_posto
		AND ( email_enviado IS NULL  OR email_enviado  < CURRENT_DATE - INTERVAL '1 days' )
		AND ( email_validado IS NULL OR email_validado < CURRENT_DATE - INTERVAL '30 days')";
$res = @pg_exec ($con,$sql);
if (@pg_numrows($res) > 0) {
	$nome  = pg_result ($res,0,nome);
	$email = trim(pg_result ($res,0,email));

	echo "<form name='frm_email' method='post' action='email_altera_envia.php' target='_blank'>";
	echo "<input type='hidden' name='btn_acao'>";
	echo "<fieldset style='border-color: 00CCFF;'>";
	echo "<legend align='center' style='background-color:#3399FF ; border:1px solid #036; ' width='90%' align='center'><font face='Arial, Helvetica, sans-serif' size='+1' color='#ffffff'> Verificação Obrigatória de Email </font> </legend>";
	echo "<br>";
	echo "<center>";
	echo "<font color='#000000' size='2'>Por favor confirme seu endereço de EMAIL no campo abaixo, e <b><i>clique em CONTINUAR</i></b>.<br>Em seguida <b><i>será enviado um email</i></b> para sua caixa de mensagens vindo<b><i> de verificacao@telecontrol.com.br</i></b>, com o <b><i>assunto Verificação de Email</i></b>, e dentro dele <b><i>existe um link que você deve clicar</i></b> para efetuar a operação de atualização e verificação do email.</font><br><br>";
	echo "Email: <input type='text' name='email' size='50' maxlength='50' value='$email'>";
	echo "&nbsp;&nbsp;";
	echo "<img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick='document.frm_email.submit(); window.location.reload( true ); ' style='cursor: hand' alt='Atualiar email'>";
	echo "<br><br>";
	echo "</center>";
	echo "</fieldset>";
	echo "</form>";

	echo "<p>";
}





#----------------- Página de informativos ----------------

switch (trim ($login_fabrica_nome)) {

	case "Dynacom":
		include "news_dynacom.php";
	break;

	case "Britania":
	    /*
		$sql = "SELECT COUNT(*) FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha IN (2,4) WHERE tbl_posto.estado = 'SP' AND tbl_posto.posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$qtde = pg_result ($res,0,0);
		if ($qtde > 0) {
			echo "<font face='arial' size='+1'>A <b>TELECONTROL</b> é seu novo Distribuidor de Peças BRITÂNIA <br>para as linhas de Eletro Portáteis e Linha Branca</font>";
			echo "<p>";
			echo "<font face='arial' size='-1'>Entre em contato conosco pelo email <a href='mailto:distribuidor@telecontrol.com.br'>distribuidor@telecontrol.com.br</a> <br>ou pelo MSN, usando este mesmo endereço de email. <br> Telefone (14) 3433-9009 </font>";

			echo "<p>";
		}
	    */
		include "news_britania.php";
#		echo "<script language='javascript'>window.open ('britania_informativo_2.html','popup2','toolbar=no, location=no, status=nos, scrollbars=no, directories=no, width=300, height=300, top=50, left=100') ; </script>";
	break;

	case "Meteor":
		include "news_meteor.php";
	break;

	case "Mondial":
		include "news_mondial.php";
	break;

	case "Tectoy":
		include "news_tectoy.php";
	break;

	case "Ibratele":
		include "news_ibratele.php";
	break;

	case "Filizola":
		include "news_filizola.php";
	break;

	case "Telecontrol":
		include "news_telecontrol.php";
	break;

	case "Lenoxx":
		include "news_lenoxx.php";
	break;

	case "Intelbras":
		include "news_intelbras.php";
	break;

	case "BlackeDecker":
		include "news_blackdecker.php";
	break;

	case "Latina":
		include "news_latina.php";
	break;
	
	case "Bosch":
		include "news_bosch.php";
	break;

	case "Lorenzetti":
		include "news_lorenzetti.php";
	break;
}
	echo "</td>";
	echo "</tr>";
	echo "</table>";

include "rodape.php";
?>
