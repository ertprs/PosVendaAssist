<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$title       = "Manutenção de contatos úteis";
$cabecalho   = "Manutenção de contatos úteis";

$btn_acao = $_POST['btn_acao'];

if(strlen($_POST['admin'])>0) $admin = $_POST['admin'];
else                          $admin = $_GET['admin'];

if(strlen($_POST['msg_erro'])>0) $msg_erro = $_POST['msg_erro'];
else                             $msg_erro = $_GET['msg_erro'];

if($btn_acao=="continuar"){
	$nome_completo         = $_POST['nome_completo'];
	$fone                  = $_POST['fone'];
	$email                 = $_POST['email'];
	$responsabilidade      = $_POST['responsabilidade'];
	$tela_inicial_posto    = $_POST['tela_inicial_posto'];
	
	if($tela_inicial_posto=='t') $tela_inicial_posto = 't';
	else                         $tela_inicial_posto = 'f';
	
	if(strlen($admin)>0){
		$sql = "UPDATE tbl_admin SET
					nome_completo      = '$nome_completo'     ,
					fone               = '$fone'              ,
					email              = '$email'             ,
					responsabilidade   = '$responsabilidade'  ,
					tela_inicial_posto = '$tela_inicial_posto'
				WHERE fabrica = $login_fabrica AND admin = $admin";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)==0){
			$msg_erro = "Gravado com sucesso!";
			header ("Location: $PHP_SELF?msg_erro=$msg_erro");
		}
	}
}

//RECARREGA DADOS
if(strlen($admin)>0){
	$sql = "SELECT * FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $admin";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		$admin              = pg_result($res, 0, admin);
		$nome_completo      = pg_result($res, 0, nome_completo);
		$fone               = pg_result($res, 0, fone);
		$email              = pg_result($res, 0, email);
		$responsabilidade   = pg_result($res, 0, responsabilidade);
		$tela_inicial_posto = pg_result($res, 0, tela_inicial_posto);
	}
}
include 'cabecalho.php';

?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
}
.erro {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #ff0000;
}

</style>

<? if(strlen($msg_erro) > 0){ ?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='erro'>
		<?echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	}
echo "<BR>";
?>
	<FORM METHOD="POST" NAME="frm_contato" ACTION="<? echo $PHP_SELF; ?>">
	<INPUT TYPE="hidden" NAME="btn_acao" value="<? echo $btn_acao ?>">
	<INPUT TYPE="hidden" NAME="admin" value="<? echo $admin ?>">
		<table border='0' cellpadding='3' cellspacing='1' style='border-collapse: collapse' bordercolor='#000000'>		<TR class='Titulo'>
			<TD>Nome Completo</TD>
			<TD>Telefone</TD>
			<TD>Email</TD>
		</TR>
		<TR>
				<TD class='Conteudo' align='left' nowrap><INPUT TYPE='text' NAME='nome_completo' VALUE="<? echo $nome_completo?>" size='40'></TD>
				<TD class='Conteudo'><INPUT TYPE='text' NAME='fone' VALUE="<? echo $fone ?>" size='12'></TD>
				<TD class='Conteudo'><INPUT TYPE='text' NAME='email' VALUE="<? echo $email ?>" size='40'></TD>
			</TR>
			<TR>
				<TD class='Titulo'  colspan='3'>Responsabilidade</TD>
			</TR>
			<TR>
				<TD class='Conteudo' align='center' colspan='3'>
					<TEXTAREA NAME='responsabilidade' ROWS='4' COLS='80'><? echo $responsabilidade ?></TEXTAREA>
				</TD>
			</TR>
			<TR>
				<TD class='Conteudo' align='left' colspan='3'><INPUT TYPE='checkbox' NAME='tela_inicial_posto' VALUE='t' <? if($tela_inicial_posto=='t') echo "CHECKED"; ?>>Tela Inicial do Posto</TD>
			</TR>
			<TR>
				<TD class='Conteudo' align='center' colspan='3'>
					<img src='imagens/btn_alterarcinza.gif' style="cursor:pointer" onclick="javascript: if (document.frm_contato.btn_acao.value == '' ) { document.frm_contato.btn_acao.value='continuar' ;  document.frm_contato.submit() } else { alert ('Aguarde Submissão') }" ALT="Gravar" border='0'>
				</TD>
			</TR>
		</table>
	</FORM>
	
	<?
	echo "<BR><BR>";

	//MOSTRA ADMINS CADASTRADOS
	$sql = "SELECT *
			FROM tbl_admin 
			WHERE fabrica = $login_fabrica 
			AND ativo is true
			ORDER BY nome_completo";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		echo "<table border='0' cellpadding='3' cellspacing='1' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<TR class='Titulo'>";
			echo "<TD>Nome Completo</TD>";
			echo "<TD>Telefone</TD>";
			echo "<TD>Email</TD>";
			echo "<TD>Responsabilidade</TD>";
			echo "<TD>Tela Inicial do Posto</TD>";
		echo "</TR>";
		for($i=0; $i<pg_numrows($res); $i++){
			$admin              = pg_result($res, $i, admin);
			$nome_completo      = pg_result($res, $i, nome_completo);
			$fone               = pg_result($res, $i, fone);
			$email              = pg_result($res, $i, email);
			$responsabilidade   = pg_result($res, $i, responsabilidade);
			$tela_inicial_posto = pg_result($res, $i, tela_inicial_posto);
			
			if($tela_inicial_posto=='t') $tela_inicial_posto = "Ativo";
			if($tela_inicial_posto=='f') $tela_inicial_posto = "Inativo";

			$cor = '#CAE4FF';
			if ($i % 2 == 0) $cor = '#E0E0E0';

			echo "<TR bgcolor='$cor'>";
				echo "<TD class='Conteudo' align='left'><A HREF='$PHP_SELF?admin=$admin'>$nome_completo</A></TD>";
				echo "<TD class='Conteudo'>$fone</TD>";
				echo "<TD class='Conteudo'>$email</TD>";
				echo "<TD class='Conteudo' align='left'>$responsabilidade</TD>";
				echo "<TD class='Conteudo' align='center'>$tela_inicial_posto</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}

echo "<BR>";
include "rodape.php"; ?>
