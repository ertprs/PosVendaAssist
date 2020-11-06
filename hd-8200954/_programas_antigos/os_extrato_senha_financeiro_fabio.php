<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";
setcookie('acessa_os_extrato', 'NAO');
if (strlen($_POST['btn_acao']) > 0 ) {
	
	if (strlen($_POST['senha_financeiro']) > 0) $senha_financeiro = trim($_POST['senha_financeiro']);
	if(strlen($senha_financeiro)==0) $msg_erro="Preencha com a senha";

	if(strlen($msg_erro)==0){
		$sql = "SELECT senha_financeiro,tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.posto = $login_posto
				AND senha_financeiro = '$senha_financeiro'
				AND senha_financeiro IS NOT NULL
				AND length(senha_financeiro) > 0";
//		echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		$msg_erro .= "se achou ...";
		if (pg_numrows($res) > 0) {
			
			$GLOBALS['acessa_extrato']='SIM';
			
			setcookie('acessa_extrato', 'SIM');
			//se for black vai pra tela de extrato da b&d
			if ($login_fabrica == 1) {
				header ("Location: os_extrato_blackedecker.php");
			}
			//se for britania e distribuidor
			elseif ($login_fabrica == 3) {
				if ($login_e_distribuidor == 't') {
					//echo "aqui nao"; exit;
					header ("Location: new_extrato_distribuidor.php");
					
				}else{
					//echo "aqui foi"; exit;
					header ("Location: new_extrato_posto_novo.php");
					
				}
			}
			else{
				//echo "aqiu nao";exit;
				header ("Location: os_extrato.php");
				exit;
			}
		}else{
			$msg_erro.="Senha incorreta";
		}
	}
}



$layout_menu = "os";
$title = "Senha do Financeiro";

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
echo"Area Restrita Para Pessoal Autorizado!";
echo"</td>";
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
<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>
	<tr >
		<td class="Titulo" >Validação de Senha do financeiro</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE'>
			<TABLE width="100%" border="0" cellspacing="1" cellpadding="0" CLASS='table_line' bgcolor='#F3F8FE'>
				<tr class="Conteudo" >
					<TD colspan="4" style="text-align: center;">
						<br>A área Financeira é restrita, somente com a Senha do Financeiro você poderá acessa-la.
					</TD>
				</tr>
				<TR width='100%'  >
					<td colspan='4'  align='center' height='40'>Senha&nbsp;<INPUT TYPE="password" NAME="senha_financeiro" ><input type="hidden" name="btn_acao" value=""></td>
				</tr>
				<tr class="Conteudo" >
					<TD colspan="4" style="text-align: center;">
						<br><img src='admin/imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='continuar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }" ALT="Acessar Extrato" border='0'>
					</TD>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>