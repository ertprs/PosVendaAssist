<?
// Tela que solicita a senha na tabela de preço, se houver

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0 ) {
	if (strlen($_GET['senha_tabela_preco']) > 0) 
		$senha_tabela_preco = trim($_GET['senha_tabela_preco']);
	if(strlen($senha_tabela_preco)==0) $msg_erro="Preencha com a senha";

	if(strlen($msg_erro)==0){
		$sql = "SELECT senha_tabela_preco,tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.posto = $login_posto
				AND senha_tabela_preco = '$senha_tabela_preco'
				AND senha_tabela_preco IS NOT NULL";
//		echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			
			$GLOBALS['acessa_tabela_preco']='SIM';
			
			setcookie("acessa_tabela_preco", $acessa_tabela_preco);
			
			//se for intelbras, mostra a PG da intelbras
			if ($login_fabrica == 14) 	{
				header ("Location: tabela_precos_intelbras.php");
				exit;
			}
			else	{
				header ("Location: tabela_precos.php");
				exit;
			}
			
		}else{
			$msg_erro="Senha incorreta";
		}
	}
}



$layout_menu = "preco";
$title = "Senha da Tabela de Preços";

include "cabecalho.php";

?>
<style type="text/css">

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
.Tabela img{
	padding:5px;
	padding-left:15px;
	}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.TituloConsulta {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #D9E2EF;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>


<?

echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
echo "<tr >";
echo "<td bgcolor='FFFFFF'  width='60'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF'>";
echo "Area Restrita Para Pessoal Autorizado!";
echo "</td>";
echo "</tr>";
echo "</table><br>";


	
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF'> $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}
?>

</style>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center'>
<table class='Tabela' width='400px' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>
	<tr >
		<td class="Titulo" >Validação de Senha da Tabela de Preços</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE'>
			<TABLE width="100%" border="0" cellspacing="1" cellpadding="0" CLASS='table_line' bgcolor='#F3F8FE'>
				<tr class="Conteudo" >
					<TD colspan="4" style="text-align: center;">
						<br>A área Tabela de Preço é restrita, somente com a Senha da Tabela de Preço você poderá acessa-la.
					</TD>
				</tr>
				<TR width='100%'  >
					<td colspan='4'  align='center' height='40'>Senha&nbsp;<INPUT TYPE="password" NAME="senha_tabela_preco" ></td>
				</tr>
				<tr class="Conteudo" >
					<TD colspan="4" style="text-align: center;">
						<br><input type="submit" name="btn_acao" value="Acessar">
					</TD>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>