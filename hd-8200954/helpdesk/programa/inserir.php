<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if (strlen ($btn_acao) > 0) {

	if($_POST['programa']) $programa = trim ($_POST['programa']);
	if($_POST['help']) $help = nl2br(trim ($_POST['help']));


	/*--==VALIDAÇÕES=====================================================--*/

	
	if (strlen($programa) == 0){
		$msg_erro="Por favor inserir o ENDEREÇO do help";
	}
	if (strlen($help) == 0){
		$msg_erro="Por favor inserir o TEXTO do help";
	}

	//grava//
	if (strlen($msg_erro) == 0){
		$sql =	"INSERT INTO tbl_help (
						programa                                                     ,
						help                                                         
					) VALUES (
						'$programa'                                                  ,
						'$help'                                                      
				)";
	
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		echo "<script language='JavaScript'>alert('Cadastro efetuado com Sucesso!');</script>";
	}
}
?>

<?

echo "<form name='frm_ajuda' action='$PHP_SELF' method='post' >";
echo "<BR><table width = '600' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' colspan='2'><b>Insira o Ajuda</b></td>";//centro
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
	echo "</tr>";
	
	if (strlen($msg_erro) <> 0){
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td colspan='2'><center>$msg_erro</center></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	echo "</tr>";
	}
	
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td align='right'>";
		echo "<BR>Programa:";
		echo "</td>";
		echo "<td>";
		echo "<center><BR><input type='text' size='60' name='programa'></center>";
		echo "</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	echo "</tr>";
	
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td align='right'>";
		echo "<BR>Help:";
		echo "</td>";
		echo "<td>";
		echo "<center><BR><textarea name='help' cols='50' rows='6' wrap='VIRTUAL'></textarea></center>";
		echo "</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	echo "</tr>";
	
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td align='right' colspan='2'>";
		echo "<BR><center><input type='submit' name='btn_acao' value='Inserir'></center><BR>";
		echo "</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	echo "</tr>";
	
	echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%' colspan='2'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
echo "</table>";
echo "</form>";

?>