<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);

$email_fabrica = $_GET['excluir'];

if (strlen ($email_fabrica) > 0) {
	$sql = "DELETE FROM tbl_email_fabrica 
			WHERE  email_fabrica = $email_fabrica 
			AND    fabrica       = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);
}

$visual_black = "manutencao-admin";
$layout_menu  = "gerencia";
$title        = "MENSAGENS AGENDADAS";

include 'cabecalho.php';

?>

<style type="text/css">
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.titulo_coluna {
	color:#FFFFFF;
	font:bold 11px "Arial";
	text-align:center;
	background:#596D9B;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

</style>

	<form name='frm_mensagem' method='post' action="<? echo $PHP_SELF ?>">
	<TABLE width='700' border='0' cellspacing='1' cellpadding='1' align='center' class="tabela">
		<TR class="titulo_coluna">
			<TD nowrap><b>Linha</b></TD>
			<TD nowrap><b>Assunto</b></TD>
			<TD nowrap><b>E-mail Remetente</b></TD>
			<TD nowrap><b>&nbsp;</b></TD>
		</TR>

<?
	$sql = "SELECT  tbl_email_fabrica.email_fabrica, 
					tbl_email_fabrica.assunto      , 
					tbl_email_fabrica.de           ,
					tbl_linha.nome                 
		FROM		tbl_email_fabrica
		LEFT JOIN	tbl_linha USING (linha)
		WHERE		tbl_email_fabrica.fabrica = $login_fabrica
		AND			tbl_email_fabrica.enviado IS NULL";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$email_fabrica   = trim(pg_result($res,$i,email_fabrica));
			$linha           = trim(pg_result($res,$i,nome));
			$assunto         = trim(pg_result($res,$i,assunto));
			$email_remetente = trim(pg_result($res,$i,de));

			echo "<TR  bgcolor='$cor'>";
			echo "	<TD>$linha</TD>";
			echo "	<TD>$assunto</TD>";
			echo "	<TD>$email_remetente</TD>";
			echo "	<TD><input type=\"button\" onclick=\"if (confirm ('Deseja realmente excluir esta mensagem?') == true) { window.location='$PHP_SELF?excluir=$email_fabrica' }\" style=\"background:url('imagens/btn_excluir.gif'); width:60px; height:15px;\" value=''  />";
			echo"</TR>";
			

		}//fim for
?>
		</TABLE>
		</FORM>
<?
	}else{ ?>
		<tr class="sucesso" >
			<td colspan="4">ATENÇÃO: Não existem e-mails agendados para serem distribuidos</td>
		</tr>		
	<?}
?>

<?include "rodape.php" ?>