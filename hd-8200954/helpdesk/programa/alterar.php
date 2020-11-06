<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';


if($_GET['acao']) $acao = trim ($_GET['acao']);
if($_GET['programa']) $programa = trim ($_GET['programa']);
	
echo "$programa";
if (strlen ($btn_acao) > 0) {

	if($_POST['aux_programa']) $aux_programa = trim ($_POST['aux_programa']);
	if($_POST['aux_help']) $aux_help = trim ($_POST['aux_help']);


	/*--==VALIDAÇÕES=====================================================--*/

	if (strlen($aux_programa) == 0){
		$msg_erro="Por favor inserir o ENDEREÇO do help/n";
	}
	if (strlen($help) == 0){
		$msg_erro="Por favor inserir o TEXTO do help/n";
	}
	if (strlen($msg_erro) <> 0){
		$sql = "UPDATE tbl_help SET programa = '$aux_programa', help = '$aux_help'";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	}
}
	

//consulta

$sql ="select * from tbl_help where tbl_help.programa='$programa'";
$res = pg_exec ($con,$sql);
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$programa           = pg_result($res,$i,programa);
	$help               = pg_result($res,$i,help);
}
?>

<form name='frm_ajuda' action='alterar.php' method='post' >
<BR>
<table width = '600' align = 'center' cellpadding='0' cellspacing='0' border='0'>
<tr>
		<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td><!-- linha esquerda - 2 linhas -->
		<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Insira o Ajuda</b></td><!-- centro -->
		<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td><!-- linha direita - 2 linhas -->
	</tr>
	<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td><!-- coluna esquerda -->
		<td><!-- meio -->
			<? if (strlen($msg_erro) <> 0){ echo "$msg_erro"; } ?>
			<BR><BR><center>Programa:<input type='text' size='60' name='aux_programa' value='<? echo "$programa"; ?>'></center><BR><BR>
			<center><textarea name='aux_help' cols='50' rows='6' wrap='VIRTUAL'><? echo "$help"; ?></textarea></center><BR><BR>
			<center><input type='submit' name='btn_acao' value='Alterar'></center>
		</td><!-- meio fim -->
		<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td><!-- coluna direita -->
	</tr>
	<tr>
		<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
		<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>
		<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	</tr>
</table>
</form>

