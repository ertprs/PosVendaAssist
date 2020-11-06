<html>
<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';

$excluir = $_GET['excluir'];
if($excluir){
		$sql="DELETE from tbl_help where programa='$excluir'";
		$res = pg_exec ($con,$sql);

//		if(!$resp){echo "erro";}
		//echo "$sql";
		echo "<script language='JavaScript'>alert('Excluido com Sucesso!');</script>";
	
}

$alterar = $_GET['alterar'];

//==================================ALTERAR==========================================

if($alterar){ 

	if($_GET['alterar']) $nome_alt = trim ($_GET['alterar']);
//	echo"entrou no alterar<BR>";
//	echo "$nome_pagina - 1<BR>";
//	echo "$nome_alt";
	

/*	if($_POST['programa']) $programa = trim ($_POST['programa']);
	if($_POST['help']) $aux_help = trim ($_POST['help']);
*/

		$sql ="select * from tbl_help where programa='$nome_alt'";
//		echo"<BR>$sql <BR>";
		$res = pg_exec ($con,$sql);
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$aux_programa           = pg_result($res,$i,programa);
			$aux_help               = pg_result($res,$i,help);
		
		
		
	}

echo "<form name='frm_ajuda' action='$PHP_SELF?prog=$alterar' method='post'>";
echo "<BR><table width = '600' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' colspan='2'><b>Insira o Ajuda</b></td>";//centro
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
	echo "</tr>";
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td align='right'>";
		echo "<BR>Programa:";
		echo "</td>";
		echo "<td>";
		echo "<center><BR><input type='text' size='60' name='aux_programa' value='$aux_programa'></center>";
		
		echo "</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	echo "</tr>";
	
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td align='right'>";
		echo "<BR>Help:";
		echo "</td>";
		echo "<td>";
		echo "<center><BR><textarea name='aux_help' cols='50' rows='6' wrap='VIRTUAL'>$aux_help</textarea></center>";
		echo "</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	echo "</tr>";
	
	echo "<TR>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
		echo "<td align='right' colspan='2'>";
		echo "<BR><center><input type='submit' name='btn_acao_alt' value='Alterar'></center><BR>";
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

 
}

		if (strlen ($btn_acao_alt) > 0) {

				if($_POST['aux_programa']) $aux_programa = trim ($_POST['aux_programa']);
				if($_POST['aux_help']) $aux_help = trim ($_POST['aux_help']);
				if($_GET['prog']) $prog = trim ($_GET['prog']);
				$sql = "UPDATE tbl_help SET programa = '$aux_programa', help = '$aux_help' WHERE programa = '$prog'";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
				echo "<script language='JavaScript'>alert('Atualizado com Sucesso!');</script>";
		}



?>


<title>Busca</title>

<body>

<TABLE align='center' cellpadding='0' cellspacing='0' width='600' border='0'>
<!-- =========TOPO DA TABELA===============================INICIO========================== -->
	<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='4' align = 'center' width='100%' style='font-family: arial ; color:#666666'>Busca página</td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>
	</tr>
<!-- =========TOPO DA TABELA===============================FIM============================= -->

		<FORM METHOD=POST ACTION="busca.php">
			<TR align='center'>
				<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
				<TD colspan='4'><BR><INPUT TYPE="text" NAME='pagina'><BR>
				<INPUT TYPE="submit" NAME='buscar' value='Buscar'><br><br></TD>
				<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
			</TR>
		</FORM>
<?

if($buscar){
		
		if($_POST['pagina']) $nome_pagina = trim ($_POST['pagina']);
		
	//	echo"aparece qdo faz a busca";
		
	//	echo"aqui 2";
		$sql = "SELECT 
			programa              ,
			help                  
		FROM tbl_help
		WHERE  tbl_help.programa ILIKE '%$nome_pagina%'";

		$res = pg_exec ($con,$sql); 


		if(@pg_numrows($res) > 0){
		
				echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='dddddd' align='center'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "<TD><B>Programa:</b.</TD>";
				echo "<TD><B>Help:</B></TD>";
				echo "<TD>&nbsp;</TD>";
				echo "<TD>&nbsp;</TD>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "</TR>";
		
	
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$programa           = pg_result($res,$i,programa);
				$help               = pg_result($res,$i,help);
				
				$cor='#F2F7FF';
				if ($i % 2 == 0) $cor = '#FFFFFF';
				?>

				<?
				echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" align='center'>";
				?>
				
					<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
					<TD><? echo "$programa"; ?></TD>
					<TD><? echo "$help"; ?></TD>
					<TD><a href='busca.php?alterar=<?echo "$programa" ?>'>Alterar</a>&nbsp;</TD>
					<TD>&nbsp;<a href='busca.php?excluir=<?echo "$programa" ?>'>Excluir</a></TD>
					<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
				</TR>
			<?	}
		
		}else{ 
		//echo"entra aqui se nao encontra nada";
		?>
				
				<TR>
					<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
					<TD colspan='4' align='center'>Nenhuma página encontrada</TD>
					<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
				</TR>
		<? } }?>

<!-- =============INICIO DA ULTIMA LINHA DA TABELA========================================== -->

<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='4' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>

<!-- =============FIM DA ULTIMA LINHA DA TABELA========================================== -->

</TABLE>
</body>
</html>