<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';

//ALTERADO GUSTAVO HD 5746

$btn_acao = strtolower($_POST["btn_acao"]);

if ($btn_acao == 'gravar'){
	$qtde_item = trim($_POST["qtde_item"]);
	
	for ($i = 0; $i <= $qtde_item; $i ++){
		$pessoa             = trim($_POST['pessoa_'.$i]);
		$senha              = trim($_POST['senha_'.$i]);
		$nome_completo      = trim($_POST['nome_completo_'.$i]);
		$email              = trim($_POST['email_'.$i]);
		$fone               = trim($_POST['fone_'.$i]);
		$ativo              = $_POST['ativo_'.$i];
		$master             = $_POST['master_'.$i];
		$cadastros          = $_POST['cadastros_'.$i];
		$vendas             = $_POST['vendas_'.$i];
		$compra             = $_POST['compra_'.$i];
		$financeiro         = $_POST['financeiro_'.$i];
		$CRM                = $_POST['CRM_'.$i];
		$gerencial          = $_POST['gerencial_'.$i];
		$franqueados        = $_POST['franqueados_'.$i];
		$relatorios         = $_POST['relatorios_'.$i];
		$ecommerce          = $_POST['ecommerce_'.$i];
		$marketing          = $_POST['marketing_'.$i];
		
		//---------------------
		$email = trim (strtolower ($email));
		$senha = trim (strtolower ($senha));

		if($master == 'master') {
			$privilegios = '*';
		}else{
			$privilegios = $cadastros.','.$vendas.',' .$compra.','.$financeiro.','.$CRM.','.$gerencial.',' .$franqueados.',' .$relatorios.',' .$ecommerce.',' .$marketing;
		}

		if (strlen($pessoa) > 0 and strlen($msg_erro) == 0) {
			if ($ativo=='t'){
				$ativo="t";
			}
			else{
				$ativo="f";
			}

			$sql = "UPDATE tbl_pessoa SET
						nome			 = '$nome_completo',
						fone_residencial = '$fone'         ,
						email			 = '$email'
					WHERE tbl_pessoa.pessoa = '$pessoa' and tbl_pessoa.empresa = '$login_empresa'";
			$res = pg_exec($con,$sql);
			//echo $sql.'<BR>';
			$msg_erro = pg_errormessage($con);

			$sql = "UPDATE tbl_empregado SET
						senha			 = '$senha'     ,
						ativo			 = '$ativo'     ,
						privilegios		 = '$privilegios'
					WHERE tbl_empregado.pessoa = '$pessoa' and tbl_empregado.empresa = '$login_empresa'";
			$res = pg_exec($con,$sql);
			//echo $sql.'<BR>';
			$msg_erro = pg_errormessage($con);
		}
	}

	$senha        = trim($_POST['senha_novo']);
	$nome_completo= trim($_POST['nome_completo_novo']);
	$fone         = trim($_POST['fone_novo']);
	$email        = trim($_POST['email_novo']);

	$ativo        = $_POST['ativo_novo'];
	$master       = $_POST['master_novo'];
	$vendas       = $_POST['vendas_novo'];
	$compra       = $_POST['compra_novo'];
	$cadastros    = $_POST['cadastros_novo'];
	$financeiro   = $_POST['financeiro_novo'];
	$CRM          = $_POST['CRM_novo'];
	$gerencial    = $_POST['gerencial_novo'];
	$franqueados  = $_POST['franqueados_novo'];
	$relatorios   = $_POST['relatorios_novo'];
	$ecommerce    = $_POST['ecommerce_novo'];
	$marketing    = $_POST['marketing_novo'];

	$email = trim (strtolower ($email));
	$senha = trim (strtolower ($senha));

	if($master == 'master') {
		$privilegios = '*';
	}else{
		$privilegios = $cadastros.',' .$vendas.',' .$compra.','.$financeiro.','.$CRM.','.$gerencial.',' .$franqueados.',' .$relatorios.',' .$ecommerce.',' .$marketing;
	}
	
	if (strlen ($email_novo) > 0 and $ativo <> 'f') {
		if (strlen(trim($senha)) >= 6) {
			//- verifica qtd de letras e numeros da senha digitada -//
			$senha = strtolower($senha);
			$count_letras  = 0;
			$count_numeros = 0;
			$letras  = 'abcdefghijklmnopqrstuvwxyz';
			$numeros = '0123456789';

			for ($j = 0; $j <= strlen($senha); $j++) {
				if ( strpos($letras, substr($senha, $j, 1)) !== false)
					$count_letras++;
				
				if ( strpos ($numeros, substr($senha, $j, 1)) !== false)
					$count_numeros++;
			}

			if ($count_letras < 2)  $msg_erro .= "Senha inválida, a senha deve ter pelo menos 2 letras para o LOGIN $login<br>";
			if ($count_numeros < 2) $msg_erro .= "Senha inválida, a senha deve ter pelo menos 2 números para o LOGIN $login<br>";
		}else{
			$msg_erro .= "A senha deve conter um mínimo de 6 caracteres para o LOGIN $login<br>";
		}
	}

	if (strlen ($email_novo) > 0 and strlen($msg_erro) == 0) {
		if (strlen($ativo) > 0) $ativo = 't';
		else $ativo = 'f';

		$sql = "INSERT INTO tbl_pessoa (
				empresa          ,
				nome             ,
				email            ,
				fone_residencial
			) VALUES (
				$login_empresa  ,
				'$nome_completo',
				'$email'        ,
				'$fone'
			)";
		$res = pg_exec($con,$sql);
		//echo $sql.'<BR>';
		$msg_erro .= pg_errormessage($con);
		$res    = pg_exec ($con,"SELECT CURRVAL ('tbl_pessoa_pessoa_seq')");
		$pessoa = pg_result ($res,0,0);
		
		if(strpos($msg_erro, 'duplicate key violates unique constraint "tbl_admin_login"'))
			$msg_erro .= "Este usuário já esta cadastrado e não pode ser duplicado.";

		$sql = "INSERT INTO tbl_empregado (
				pessoa   ,
				empresa  ,
				senha    ,
				ativo    ,
				privilegios
			) VALUES (
				'$pessoa'      ,
				'$login_empresa',
				'$senha',
				'$ativo',
				'$privilegios'
				)";
		$res = pg_exec($con,$sql);
		//echo $sql.'<BR>';
		$msg_erro .= pg_errormessage($con);
	}
}

include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'gerencial') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8px;
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
}

input {
	font-size: 10px;
}

</style>

<?
if($msg_erro){
?>

<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} 
?> 
<p>
	</table>
	</form>
	<p>
<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
	<tr>
		<td align='center'>
			<font face='arial, verdana' color='#596d9b' size='-1'>
				Para incluir um novo administrador preencha com seu email e senha selecionando seus privilégios. <br>
				Para alterar email e senha de um administrador já existente selecione o campo e altere. <br>
				<b>OBS.: Clique em gravar logo após inserir ou alterar a configuração de um administrador.</B>
			</font>
		</td>
	</tr>
</table>

<table width='600' align='center' border='0'>
<tr>
	<td colspan='2'><BR></td>
</tr>
<tr>
	<td width='20' bgcolor='#FFCC00'> &nbsp;</td>
	<td>&nbsp;<font face='arial, verdana' color='#596d9b' size='-1'>Usuários inativos<font></td>
</tr>
</table>

 <? echo "<BR>"; ?>
<form name="frm_admin" method="post" action="<? echo $PHP_SELF ?>">

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class='menu_top'>
 		<td nowrap>NOME COMPLETO</td>
		<td nowrap>FONE</td>
		<td nowrap>EMAIL</td>
		<td nowrap>SENHA</td>
		<td nowrap>ATIVO</td>
		<td nowrap>MASTER</td>
		<td nowrap>CADASTROS</td>
		<td nowrap>VENDAS</td>
		<td nowrap>COMPRA</td>
		<td nowrap>FINANCEIRO</td>
		<td nowrap>CRM</td>
		<td nowrap>GERENCIAL</td> 
		<td nowrap>FRANQUEADOS</td> 
		<td nowrap>RELATORIOS</td> 
		<td nowrap>ECOMMERCE</td> 
		<td nowrap>MARKETING</td> 
	</tr>
	<tr class="table_line">
		<td nowrap bgcolor = #AFC5FE><input type='text' name='nome_completo_novo'        size='20' maxlength='' value='<? echo $nome_completo_novo ?>'></td>
		<td nowrap bgcolor = #AFC5FE><input type='text' name='fone_novo'         size='10' maxlength='' value='<? echo $fone_novo ?>'></td>
		<td nowrap bgcolor = #AFC5FE><input type='text' name='email_novo'        size='20' maxlength='' value='<? echo $email_novo ?>'></td>
		<td nowrap bgcolor = #AFC5FE><input type='text' name='senha_novo'        size='10' maxlength='10' value='<? echo $senha_novo ?>'></td>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='ativo_novo'        value='ativo' <? if ($ativo_novo == 't')      echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='master_novo'       value='master' <? if ($master_novo == 'master')      echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='cadastros_novo'    value='cadastros' <? if ($cadastros_novo == 'cadastros')   echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='vendas_novo'     value='vendas' <? if ($vendas_novo == 'vendas')    echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='compra_novo'  value='compra' <? if ($compra_novo == 'compra') echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='financeiro_novo'   value='financeiro' <? if ($financeiro_novo == 'financeiro')  echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='CRM_novo' value='CRM' <? if ($CRM_novo == 'CRM')echo " checked " ?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='gerencial_novo'    value='gerencial' <? if ($gerencial_novo == 'gerencial')   echo " checked "?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='franqueados_novo'    value='franqueados' <? if ($franqueados_novo == 'franqueados')   echo " checked "?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='relatorios_novo'    value='relatorios' <? if ($relatorios_novo == 'relatorios')   echo " checked "?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='ecommerce_novo'    value='ecommerce' <? if ($ecommerce_novo == 'ecommerce')   echo " checked "?>></TD>
		<td bgcolor = #AFC5FE><input type="checkbox"    name='marketing_novo'    value='marketing' <? if ($marketing_novo == 'marketing')   echo " checked "?>></TD>
	</tr>
	<?
	$sql = "SELECT 
				tbl_pessoa.pessoa ,
				tbl_pessoa.nome  ,
				tbl_pessoa.email ,
				tbl_pessoa.fone_residencial,
				tbl_empregado.senha ,
				tbl_empregado.ativo ,
				tbl_empregado.privilegios 
			FROM tbl_pessoa
			JOIN tbl_empregado USING(pessoa)
			WHERE tbl_pessoa.empresa = $login_empresa
			ORDER BY tbl_pessoa.email";
	$res = pg_exec ($con,$sql);
	for ($i = 0; $i < pg_numrows($res); $i ++){
		$pessoa			=	trim(pg_result ($res,$i,pessoa));
		$senha			=	trim(pg_result ($res,$i,senha));
		$nome_completo	=	trim(pg_result ($res,$i,nome));
		$email			=	trim(pg_result ($res,$i,email));
		$fone			=	trim(pg_result ($res,$i,fone_residencial));
		$privilegios	=	trim(pg_result ($res,$i,privilegios));
		$ativo			=	trim(pg_result ($res,$i,ativo));

		if ($ativo == 'f') $cor = "#FFCC00";
		else $cor = "#FFFFFF";

		echo "<tr class='table_line'>\n";
		echo "<input type='hidden' name='pessoa_$i' value='$pessoa'>\n";
		echo "<input type='hidden' name='ativo_$i' value='$ativo'>\n";

		echo "<td nowrap bgcolor='$cor'><input type='text' name='nome_completo_$i' size='20' maxlength='' value='$nome_completo'";
		if ( $ativo == 'f') echo " readonly ";
		echo "></td>\n";
		echo "<td nowrap bgcolor='$cor'><input type='text' name='fone_$i' size='10' maxlength='' value='$fone'";
		if ( $ativo == 'f') echo " readonly ";
		echo "></td>\n";
		echo "<td nowrap bgcolor='$cor'><input type='text' name='email_$i' size='20' maxlength='' value='$email'";
		if ( $ativo == 'f') echo " readonly ";
		echo "></td>\n";
		echo "<td nowrap bgcolor='$cor'><input type='password' name='senha_$i' size='10' maxlength='20' value='$senha'";
		if ( $ativo == 'f') echo " readonly ";
		echo "></td>\n";
		
		echo "<td bgcolor='$cor'><input type='checkbox' name='ativo_$i' value='t'";
		if ( $ativo == 't') echo " checked";
		echo "> &nbsp;</TD>\n";

		echo "<td bgcolor='$cor'><input type='checkbox' name='master_$i' value='master'";
		if (strpos($privilegios,'*') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo "> &nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='cadastros_$i' value='cadastros'";
		if (strpos($privilegios,'cadastros') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";

		echo "<td bgcolor='$cor'><input type='checkbox' name='vendas_$i' value='vendas'";
		if (strpos($privilegios,'vendas') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='compra_$i' value='compra'";
		if (strpos($privilegios,'compra') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='financeiro_$i' value='financeiro'";
		if (strpos($privilegios,'financeiro') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='CRM_$i' value='CRM'";
		if (strpos($privilegios,'CRM') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='gerencial_$i' value='gerencial'";
		if (strpos($privilegios,'gerencial') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='franqueados_$i' value='franqueados'";
		if (strpos($privilegios,'franqueados') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='relatorios_$i' value='relatorios'";
		if (strpos($privilegios,'relatorios') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='ecommerce_$i' value='ecommerce'";
		if (strpos($privilegios,'ecommerce') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "<td bgcolor='$cor'><input type='checkbox' name='marketing_$i' value='marketing'";
		if (strpos($privilegios,'marketing') !== false) echo " checked";
		if ( $ativo == 'f') echo " disabled ";
		echo ">&nbsp;</TD>\n";
		echo "</tr>\n";

	}?>
	<input type='hidden' name='qtde_item' value="<? echo $i?>">
	

</table> 
<br>
<center>
<input type='hidden' name='btn_acao' value=''>
<INPUT TYPE="button" name="botao" value="Gravar" onclick="javascript: if (document.frm_admin.btn_acao.value == '' ) { document.frm_admin.btn_acao.value='gravar' ; document.frm_admin.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0' style='cursor: pointer'>
</center>
<br>
</form>
<p>

<? include "rodape.php"; ?>
