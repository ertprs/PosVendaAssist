<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$btn_acao = strtolower($_POST["btn_acao"]);
$xbtn_acao = strtolower($_POST["xbtn_acao"]);

if ($btn_acao == 'gravar' or $xbtn_acao == 'gravar'){

	$qtde_item = trim($_POST["qtde_item"]);

	for ($i = 0; $i <= $qtde_item; $i ++) {

		$admin                  = trim($_POST['admin_'.$i]);
		$login                  = trim($_POST['login_'.$i]);
		$senha                  = $_POST['senha_'.$i];
		$nome_completo          = trim($_POST['nome_completo_'.$i]);
		$email                  = trim($_POST['email_'.$i]);
		$cliente_admin          = trim($_POST['cliente_admin_'.$i]);
		$pais                   = strtoupper(trim($_POST['pais_'.$i]));
		$fone                   = trim($_POST['fone_'.$i]);
		$ativo                  = $_POST['ativo_'.$i];
		$master                 = $_POST['master_'.$i];
		$cliente_admin_master   = $_POST['cliente_admin_master_'.$i];
		$gerencia               = $_POST['gerencia_'.$i];
		$call_center            = $_POST['call_center_'.$i];
		$supervisor_call_center = $_POST['supervisor_call_center_'.$i];
		$cadastros              = $_POST['cadastros_'.$i];
		$info_tecnica           = $_POST['info_tecnica_'.$i];
		$financeiro             = $_POST['financeiro_'.$i];
		$auditoria              = $_POST['auditoria_'.$i];
		$promotor_wanke			= $_POST['promotor_wanke_'.$i]; // HD 685194
		$consulta_os            = ($_POST['consulta_os_'.$i] == 'consulta_os') ? 't' : 'f';
		$intervensor            = (isset($_POST['intervensor_'.$i])) ? 't' : 'f' ;
		$fale_conosco           = (isset($_POST['fale_conosco_'.$i])) ? 't' : 'f' ;
		$atendente_callcenter   = (isset($_POST['atendente_callcenter_'.$i])) ? 't' : 'f' ;//HD 335548
		$admin_sap              = (isset($_POST['sap_'.$i])) ? 't' : 'f' ;
		$reponsavel_postos      = (isset($_POST['responsavel_postos_'.$i])) ? 't' : 'f';
		$altera_pais_produto    = (!empty($_POST["altera_pais_produto_$i"])) ? 'TRUE' : 'FALSE'; // HD 374998

		$login = str_replace(".","",$login);
		$login = str_replace("/","",$login);
		$login = str_replace("-","",$login);
		$login = trim(strtolower($login));

		$senha = trim(strtolower($senha));

		if(strlen($admin) > 0){
			$sql = "SELECT senha FROM tbl_admin WHERE admin = $admin LIMIT 1";
			$res = pg_query($con,$sql);
			
			if(pg_num_rows($res) > 0){
				$_senha = sha1(pg_result($res,0,0));
				if($_senha == $senha){
					$senha = pg_result($res,0,0);
				}
			}
		}

		if ($master == 'master') {
			$privilegios = '*';
		} else {
			$privilegios = implode(',', array_filter(explode(',', "$gerencia,$call_center,$cadastros,$info_tecnica,$financeiro,$auditoria,$promotor_wanke")));
		}

		if (strlen($admin) > 0 and $ativo == 't') {
			if (strlen(trim($senha)) >= 6) {
				//- verifica qtd de letras e numeros da senha digitada -//
				$senha = strtolower($senha);
				$count_letras  = 0;
				$count_numeros = 0;
				$letras  = 'abcdefghijklmnopqrstuvwxyz';
				$numeros = '0123456789';
				//echo strlen($senha); echo $senha;exit;
				for ($j = 0; $j <= strlen($senha); $j++) {
					if (strpos($letras, substr($senha, $j, 1)) !== false)
						$count_letras++;
					if (strpos($numeros, substr($senha, $j, 1)) !== false)
						$count_numeros++;
				}

				if ($count_letras < 2)  $msg_erro .= "$senha - Senha inválida, a senha deve ter pelo menos 2 letras para o LOGIN $login <br>";
				if ($count_numeros < 2) $msg_erro .= "$senha- Senha inválida, a senha deve ter pelo menos 2 números para o LOGIN $login <br>";
			} else {
				$msg_erro .= "A senha deve conter um mínimo de 6 caracteres para o LOGIN $login<br>";
			}

		}

		$count_letras  = 0;
		$count_numeros = 0;

		if (strlen($admin) > 0 and strlen($msg_erro) == 0) {
			if (strlen($ativo) > 0) $ativo = 't';
			else $ativo = 'f';

			if (strlen($cliente_admin_master) > 0) $cliente_admin_master = 't';
			else $cliente_admin_master = 'f';

			if (strlen($supervisor_call_center) > 0) $supervisor_call_center = 't';
			else $supervisor_call_center = 'f';
			
			if (strlen($cliente_admin) == 0) {
				$cliente_admin = 'null';
			}

			// a pedido do boulivar, verificar antes de ativar um usuário, se o cliente_admin dele pode abrir OS. HD 372098

			if( $cliente_admin != 'null' && $ativo == 't') {
				$sql = "SELECT abre_os_admin FROM tbl_cliente_admin WHERE cliente_admin = $cliente_admin";
				$res = pg_query($con,$sql);
				$abre_os = pg_result($res,0,0);
				if ($abre_os == 'f') {
					$sql = "UPDATE tbl_cliente_admin SET abre_os_admin = 't' WHERE cliente_admin = $cliente_admin AND fabrica = $login_fabrica";
					pg_query($con,$sql);
				}
			}

			if (strlen($pais) == 0 ) $pais = 'BR';
			echo $sql = "UPDATE tbl_admin SET
						login                 = '$login'        ,
						senha                 = '$senha'        ,
						nome_completo         = '$nome_completo',
						fone                  = '$fone'         ,
						email                 = '$email'        ,
						ativo                 = '$ativo'        ,
						admin_sap             = '{$admin_sap}'  ,
						fale_conosco          = '$fale_conosco' ,
						intervensor           = '$intervensor' ,
						cliente_admin         = $cliente_admin  ,
						callcenter_supervisor = '$supervisor_call_center',
						privilegios           = '$privilegios',
						consulta_os           = '$consulta_os',
						atendente_callcenter  = '$atendente_callcenter',
						cliente_admin_master  = '$cliente_admin_master',
						responsavel_postos    = '$reponsavel_postos',
						altera_pais_produto   = $altera_pais_produto /* HD 374998 */
					WHERE tbl_admin.admin = '$admin' ";

			$res = @pg_exec($con,$sql);

			$msg_erro = pg_errormessage($con);

			if(strpos($msg_erro, 'duplicate key'))
				$msg_erro = "Este usuário já está cadastrado e não pode ser duplicado.";
		}
	}

	$login        = trim($_POST['login_novo']);
	$senha        = trim($_POST['senha_novo']);
	$nome_completo= trim($_POST['nome_completo_novo']);
		$fone         = trim($_POST['fone_novo']);
	$email        = trim($_POST['email_novo']);
	$pais         = strtoupper(trim($_POST['pais_novo']));

	$ativo			= $_POST['ativo_novo'];
	$master			= $_POST['master_novo'];
	$gerencia		= $_POST['gerencia_novo'];
	$call_center	= $_POST['call_center_novo'];
	$cadastros		= $_POST['cadastros_novo'];
	$info_tecnica	= $_POST['info_tecnica_novo'];
	$financeiro		= $_POST['financeiro_novo'];
	$auditoria		= $_POST['auditoria_novo'];
	$promotor_wanke	= $_POST['promotor_wanke_novo']; // HD 685194
	$admin_sap_novo	= ( isset($_POST['sap_novo']) ) ? 't' : 'f' ;
	$fale_conosco	= ( isset($_POST['fale_conosco']) ) ? 't' : 'f' ;
	$intervensor	= ( isset($_POST['intervensor']) ) ? 't' : 'f' ;
	$login			= trim(strtolower($login));
	$senha			= trim(strtolower($senha));

	$cliente_admin				= trim($_POST['cliente_admin']);
	$cliente_admin_master		= $_POST['cliente_admin_master'];
	$supervisor_call_center		= $_POST['supervisor_call_center_novo'];
	$responsavel_postos_novo	= (isset($_POST['responsavel_postos_novo'])) ? 't' : 'f'  ; #HD 233213
	$altera_pais_produto_novo	= (!empty($_POST["altera_pais_produto_novo"])) ? 'TRUE' : 'FALSE'; // HD 374998


	if($master == 'master') {
		$privilegios = '*';
	} else {
		$privilegios = implode(',', array_filter(explode(',', "$gerencia,$call_center,$cadastros,$info_tecnica,$financeiro,$auditoria,$promotor_wanke")));
		//$privilegios = $gerencia.',' .$call_center.',' .$cadastros.',' .$info_tecnica.',' .$financeiro.',' .$auditoria;
	}
	
	if (strlen ($login_novo) > 0 and $ativo <> 'f') {
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

		} else {
			$msg_erro .= "A senha deve conter um mínimo de 6 caracteres para o LOGIN $login<br>";
		}
	}
	if (strlen ($login_novo) > 0 and strlen($msg_erro) == 0) {

		if (strlen($ativo) > 0) $ativo = 't';
		else $ativo = 'f';

		if (strlen($cliente_admin_master) > 0) $cliente_admin_master = 't';
		else $cliente_admin_master = 'f';

		if (strlen($supervisor_call_center) > 0) $supervisor_call_center = 't';
		else $supervisor_call_center = 'f';

		if (strlen($cliente_admin) == 0) {
			$cliente_admin = 'null';
		}

		if (strlen($pais) == 0) $pais = 'BR';

		$sql = "INSERT INTO tbl_admin (
					fabrica      ,
					login        ,
					senha        ,
					nome_completo,
					email        ,
					pais         ,
					fone         ,
					ativo        ,
					admin_sap	 ,
					callcenter_supervisor  ,
					cliente_admin,
					fale_conosco,
					intervensor,
					privilegios,
					consulta_os,
					cliente_admin_master,
					atendente_callcenter,
					responsavel_postos ,
					altera_pais_produto /* HD 374998 */
				) VALUES (
					$login_fabrica  ,
					'$login'        ,
					'$senha'        ,
					'$nome_completo',
					'$email'        ,
					'$pais'         ,
					'$fone'         ,
					'$ativo'        ,
					'{$admin_sap_novo}'  ,
					'$supervisor_call_center',
					$cliente_admin,
					'$fale_conosco',
					'$intervensor',
					'$privilegios',
					'$consulta_os',
					'$cliente_admin_master',
					'$atendente_callcenter',
					'$responsavel_postos_novo',
					$altera_pais_produto_novo
				)";

		//echo nl2br($sql); die;
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		
		if (strpos($msg_erro, 'duplicate key'))
			$msg_erro = "Este usuário já está cadastrado e não pode ser duplicado.";
	}

	if (strlen ($msg_erro) == 0){
		header ("Location: $PHP_SELF");
		exit;
	}

}

if ($btn_acao == 'gravar2'){

	$admin                  = trim($_POST['admin_'.$i]);
	$login                  = trim($_POST['login_'.$i]);
	$senha                  = trim($_POST['senha_'.$i]);
	$nome_completo          = trim($_POST['nome_completo_'.$i]);
	$email                  = trim($_POST['email_'.$i]);
	$fone                   = trim($_POST['fone_'.$i]);
	$pais                   = strtoupper(trim($_POST['pais_'.$i]));
	
	$ativo                  = $_POST['ativo_'.$i];
	$master                 = $_POST['master_'.$i];
	$cliente_admin_master   = $_POST['cliente_admin_master_'.$i];
	$gerencia               = $_POST['gerencia_'.$i];
	$call_center            = $_POST['call_center_'.$i];
	$supervisor_call_center = $_POST['supervisor_call_center_'.$i];
	$cadastros              = $_POST['cadastros_'.$i];
	$info_tecnica           = $_POST['info_tecnica_'.$i];
	$financeiro             = $_POST['financeiro_'.$i];
	$auditoria              = $_POST['auditoria_'.$i];
	$promotor_wanke			= $_POST['promotor_wanke_'.$i];
	$responsavel_postos     = $_POST['responsavel_postos_'.$i]; #HD 233213
	$altera_pais_produto    = $_POST["altera_pais_produto_$i"]; // HD 374998

	$login = trim(strtolower($login));
	$senha = trim(strtolower($senha));

	if (strlen($admin) > 0 ) {
		$sql = "UPDATE tbl_admin SET
					senha		      = '$senha'       
				WHERE tbl_admin.admin = '$login_admin'";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0){
		header ("Location: $PHP_SELF");
		exit;
	}
}

$title = "Privilégios para o Administrador";
$cabecalho = "Cadastro de Postos Autorizados";
$layout_menu = "gerencia";
include 'cabecalho.php';
?>

<style type="text/css">
/*
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
*/
input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
}

table.tabela tr td{
font-family: verdana;
font-size: 10px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.subtitulo{
 background-color: #7092BE;
 font-family:bold Arial;
 font-size: 14px;
 color:#FFFFFF;
}
</style><?php

if (strlen($msg_erro) > 0) {?>

	<table width='700px' align='center' border='0' cellspacing="1" cellpadding="0">
		<tr class="msg_erro">
			<td><?=$msg_erro;?></td>
		</tr>
	</table><?php

}

$sql = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res = pg_exec($con,$sql);

$privilegios = pg_result($res,0,0);

$abre_os_admin_arr = array(30,52,85,96); // HD #372098

if (strpos($privilegios,'*') === false) {

	echo "<center><h1>Você não tem permissão para gerenciar usuários.</h1></center>";
	echo "<form name='frm_admin' method='post' action='$PHP_SELF '>";
	echo "<input type='hidden' name='btn_acao' value=''>";

	echo "<table class='border' width='700' align='center' border='0' cellpadding='1' cellspacing='1'>";
	echo "<tr class='menu_top'>";
	
	echo "<td nowrap>Login</td>";
	echo "<td nowrap>Senha</td>";
	echo "<td nowrap>Nome</td>";
	echo "<td nowrap>Fone</td>";
	echo "<td nowrap>Email</td>";
	
	if ( in_array( $login_fabrica, $abre_os_admin_arr ) ) {
		echo "<td nowrap>Cliente Admin</td>";
		if($login_fabrica != 96)
			echo "<td>Cliente Admin Master</td>";
	}
	echo "<td nowrap>Ativo</td>";
	if ($login_fabrica == 1 or $login_fabrica == 30) {
		echo "<td nowrap>";
		echo ($login_fabrica == 1) ?"<abbr title=\"Atendente de Chamados dos Postos\">SAP</abbr>" : "Inspetor";
		echo "</td>";
	}
	echo "<td nowrap>Master</td>";
	echo "<td nowrap>Gerência</td>";
	echo "<td nowrap>Call-Center</td>";
	echo "<td >Supervidor Call-Center</td>";
	
	echo "<td nowrap>Cadastros</td>";
	echo ($login_fabrica == 20) ? '<td title="O usuário poderá liberar produtos para outros países">Altera País Prod.</td>' : ''; // HD 374998
	echo ($login_fabrica == 91) ? '<td title="Acesso para promotores, limitado ao Call-Center">Promotor</td>' : ''; // HD 685194
	echo "<td nowrap>Info Técnica</td>";
	echo "<td nowrap>Financeiro</td>";
	echo "<td nowrap>$login_fabrica - Auditoria</td>";
	echo "</tr>";
		
	$sql = "SELECT *
			FROM tbl_admin 
			WHERE fabrica = $login_fabrica
			AND   admin   = $login_admin";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		$admin					= trim(pg_result($res, 0, 'admin'));
		$login					= trim(pg_result($res, 0, 'login'));
		$senha					= trim(pg_result($res, 0, 'senha'));
		$nome_completo			= trim(pg_result($res, 0, 'nome_completo'));
		$fone					= trim(pg_result($res, 0, 'fone'));
		$email					= trim(pg_result($res, 0, 'email'));
		$privilegios			= trim(pg_result($res, 0, 'privilegios'));
		$cliente_admin_master	= trim(pg_result($res, 0, 'cliente_admin_master'));
		$supervisor_call_center	= trim(pg_result($res, 0, 'callcenter_supervisor'));
		$fale_conosco			= trim(pg_result($res, 0, 'fale_conosco'));
		$intervensor			= trim(pg_result($res, 0, 'intervensor'));
		$atendente_callcenter	= trim(pg_result($res, 0, 'atendente_callcenter'));//HD 335548
		$ativo					= trim(pg_result($res, 0, 'ativo'));
		$admin_sap				= trim(pg_result($res, 0,'admin_sap'));
		$responsavel_postos     = trim(pg_result($res, 0, 'responsavel_postos'));//HD 233213
		$altera_pais_produto    = trim(pg_result($res, 0, 'altera_pais_produto')); // HD 374998
		
		echo "<tr class='table_line'>\n";
		echo "<input type='hidden' name='admin_$i' value='$admin'>\n";
		
		echo "<td nowrap>$login </td>\n";
		echo "<td nowrap><input type='password' name='senha_$i' size='10' maxlength='' value='".sha1($senha)."'></td>\n";
		echo "<td nowrap><input type='text' name='nome_completo_$i' size='10' maxlength='' value='$nome_completo'></td>\n";
		echo "<td nowrap><input type='text' name='fone_$i' size='15' maxlength='' value='$fone'></td>\n";
		echo "<td nowrap><input type='text' name='email_$i' size='20' maxlength='' value='$email'></td>\n";
		
		if ( in_array( $login_fabrica, $abre_os_admin_arr ) ) {

			echo "<td nowrap>";
				echo "<select name='cliente_admin'>";
					echo "<option></option>";

					$sql_cliente_admin = "SELECT cliente_admin,
											nome, cidade
											FROM tbl_cliente_admin 
											WHERE abre_os_admin is true
											AND fabrica = $login_fabrica 
											ORDER BY nome";

				$res_cliente_admin = pg_exec($con,$sql_cliente_admin);
				$total = pg_num_rows($res_cliente_admin);

				if ($total > 0) {

					for ($w = 0; $w < $total; $w++) {

						$cliente_admin	= pg_result($res_cliente_admin, $w, 'cliente_admin');
						$nome			= pg_result($res_cliente_admin, $w, 'nome');
						$cidade			= pg_result($res_cliente_admin, $w, 'cidade');

						$nome           = ucwords(strtolower(substr($nome,0,20)));
						$cidade         = ucwords(strtolower(substr($cidade,0,15)));

						echo "<option value='$cliente_admin'>$nome - $cidade</option>";

					}

				}

			echo "</select>
			</td>";
			if($login_fabrica != 96) {
				echo "<td><input type='checkbox' name='cliente_admin_master_$i' value='t'";
				if ( $cliente_admin_master == 't') echo " checked";
				echo "&nbsp;</TD>\n";
			}

		}

		echo "<td><input type='checkbox' name='ativo_$i' value='t'";
		if ($ativo == 't') echo " checked";
		echo " disabled> &nbsp;</TD>\n";
		if ($login_fabrica == 1 or $login_fabrica == 74) {
			echo "<td><input type='checkbox' name='sap_{$i}' id='sap_{$i}' value='1' disabled></td>";
			echo "<td><input type='checkbox' name='responsavel_postos_{$i}' id='responsavel_postos_{$i}' value='1' disabled></td>"; #HD 233213
		}
		echo "<td><input type='checkbox' name='master_$i' value='master'";
		if (strpos($privilegios,'*') !== false) echo " checked";
		echo " disabled> &nbsp;</TD>\n";

		echo "<td><input type='checkbox' name='gerencia_$i' value='gerencia'";
		if (strpos($privilegios,'gerencia') !== false) echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		echo "<td><input type='checkbox' name='call_center_$i' value='call_center'";
		if (strpos($privilegios,'call_center') !== false) echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		echo "<td><input type='checkbox' name='supervisor_call_center_$i' value='supervisor_call_center'";
		if ($supervisor_call_center=="t") echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		echo "<td><input type='checkbox' name='cadastros_$i' value='cadastros'";
		if (strpos($privilegios,'cadastros') !== false) echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		if ($login_fabrica == 20) { // HD 374998
			echo "<td><input type='checkbox' name='altera_pais_produto_$i' value='t'";
			if ($altera_pais_produto == 't') echo " checked";
			echo " disabled>&nbsp;</TD>\n";
		}
		if ($login_fabrica == 91) { // HD 685194
			echo "<td><input type='checkbox' name='promotor_wanke_$i' value='t'";
			if (strpos($privilegios,'promotor')!==false) echo " checked";
			echo " disabled>&nbsp;</TD>\n";
		}
		echo "<td><input type='checkbox' name='info_tecnica_$i' value='info_tecnica'";
		if (strpos($privilegios,'info_tecnica') !== false) echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		echo "<td><input type='checkbox' name='financeiro_$i' value='financeiro'";
		if (strpos($privilegios,'financeiro') !== false) echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		echo "<td><input type='checkbox' name='auditoria_$i' value='auditoria'";
		if (strpos($privilegios,'auditoria') !== false) echo " checked";
		echo " disabled>&nbsp;</TD>\n";
		echo "</tr>\n";

	}?>
	</table>
	</form>
	<table align='center'>
		<tr>
			<td colspan="9" align='center'>
				<input type='hidden' name='btn_acao' value='' />
				<center>
					<img src='imagens/btn_gravar.gif' style='cursor: pointer;' onclick="if (document.frm_admin.btn_acao.value == '' ) { document.frm_admin.btn_acao.value='gravar2' ; document.frm_admin.submit() } else { alert ('Aguarde submissão') }" ALT='Gravar Formulário' border='0' />
				</center>
			</td>
		</tr>
	</table><?php
} else {?>
	<table width='700' align='center' border='0' bgcolor='#d9e2ef'>
		<tr>
			<td align='left' style="text-align:justify;">
				<font face='arial, verdana' color='#596d9b' size='-1'>
					Para incluir um novo administrador insira o login e senha desejados e selecione os privilégios que deseja conceder a este usuário.
					Para alterar qualquer informação basta clicar sobre o campo desejado e efetuar a troca.<br>
					<b>OBS.: Clique em gravar logo após inserir ou alterar a configuração de um administrador.</B>
					<br><br> A senha deve ter entre 06 e 10 caracteres, sendo ao menos 02 letras (de A à Z) e 02 números (de 0 a 9), por exemplo: bra500, tele2007, ou assist0682.
				</font>
			</td>
		</tr>
	</table>
	<form name="frm_admin" method="post" action="<? echo $PHP_SELF ?>"><?php

	if ($login_fabrica == 3) {?>
		<br />
		<center>
		<input type="hidden" name="xbtn_acao" value="">
		<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="if (document.frm_admin.xbtn_acao.value == '') { document.frm_admin.xbtn_acao.value='gravar'; document.frm_admin.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Formulário" border='0'>
		</center>
		<br /><?php
	} ?>

	<table width='700' align='center' border='0' cellpadding="1" cellspacing="1" class="tabela">
		<tr class='titulo_coluna' style='cursor:default'>
			<td nowrap>Login</td>
			<td nowrap>Senha</td>
			<td nowrap>Nome Completo</td>
			<td nowrap>Fone</td>
			<td nowrap>Email</td><?php
			if ($login_fabrica == 20) { 
				echo "<td nowrap>PAIS</td>";
			}
			if ( in_array( $login_fabrica, $abre_os_admin_arr ) ) {
				echo "<td nowrap>Cliente Admin</td>";
				if($login_fabrica != 96)
					echo "<td>Cliente Admin Master</td>";
			}?>
			<td nowrap>Ativo</td>
			<td nowrap>Master</td>
			<td nowrap>Gerência</td>
			<td nowrap>Call-Center</td>
			<td>Supervisor Call-Center</td>
			<td nowrap>Cadastros</td>
			<?=($login_fabrica == 20) ? '<td title="O usuário poderá liberar produtos para outros países">Altera País Prod.</td>' : ''?>
			<?=($login_fabrica == 91) ? '<td title="Promotor, acesso limitado ao Call-Center">Promotor</td>' : ''?>
			<td nowrap>Info Técnica</td>
			<td nowrap>Financeiro</td>
			<td nowrap>Auditoria</td>
			<?php
			if ($login_fabrica == 19) {
				echo "<td>Usuário Consulta OS</td>";
			}
			if ($login_fabrica == 24) {
				echo "<td>Recebe Fale Conosco</td>";
				echo "<td>Atendente CallCenter</td>";
				echo "<td>Intervensor de Callcenter</td>";
			}
			if ($login_fabrica == 1 or $login_fabrica == 30 or $login_fabrica == 74 or $login_fabrica == 19) {
				if($login_fabrica != 19) {
					echo "<td nowrap>";
					echo ($login_fabrica == 1 or $login_fabrica == 74) ?"<abbr title=\"Atendente de Chamados dos Postos\">SAP</abbr>" : "Inspetor";
				}
				/*HD 233213*/echo "<td nowrap>Responsável<br> Postos</td>";
				echo "</td>";
			}?>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td nowrap ><input type='text' name='login_novo' size='20' maxlength='20' value='<? echo $login_novo ?>' ></td>
			<td nowrap ><input type='text' name='senha_novo' size='15' maxlength='10' value='<? echo $senha_novo ?>'></td>
			<td nowrap ><input type='text' name='nome_completo_novo' size='35' maxlength='' value='<? echo $nome_completo_novo ?>'></td>
			<td nowrap ><input type='text' name='fone_novo' size='20' maxlength='' value='<? echo $fone_novo ?>'></td>
			<td nowrap ><input type='text' name='email_novo' size='40' maxlength='' value='<? echo $email_novo ?>'></td><?php

			if ($login_fabrica == 20) {
				echo "<td nowrap ><input type='text' name='pais_novo' size='2' maxlength='2' value=$pais_novo></td>";
			}

			if ( in_array( $login_fabrica, $abre_os_admin_arr ) ) {
				echo "<td nowrap >";
					echo "<select name='cliente_admin'>";
						echo "<option></option>";

					$sql_cliente_admin = "SELECT cliente_admin,
											nome, cidade
											FROM tbl_cliente_admin
											WHERE fabrica = $login_fabrica
											AND abre_os_admin IS TRUE
											ORDER BY nome";

					$res_cliente_admin = pg_exec($con,$sql_cliente_admin);

					if (pg_num_rows($res_cliente_admin) > 0) {

						for ($w = 0; $w < pg_num_rows($res_cliente_admin); $w++) {

							$cliente_admin	= pg_result($res_cliente_admin, $w, 'cliente_admin');
							$nome			= pg_result($res_cliente_admin, $w, 'nome');
							$cidade			= pg_result($res_cliente_admin, $w, 'cidade');

							$nome           = ucwords(strtolower(substr($nome,0,20)));
							$cidade         = ucwords(strtolower(substr($cidade,0,15)));

							echo "<option value='$cliente_admin'>$nome - $cidade</option>";

						}

					}
				echo "</select>";
				echo "</td>";
				if($login_fabrica != 96) {
					echo "<td ><input type='checkbox' name='cliente_admin_master' value='t'";
					if ($cliente_admin_master == 't') echo " checked";
					echo "&nbsp;</TD>\n";
				}
			}?>
			<td><input type="checkbox" name='ativo_novo' value='ativo' <? if ($ativo_novo == 't') echo " checked " ?>></td>
			<td><input type="checkbox" name='master_novo' value='master' <? if ($master_novo == 'master') echo " checked " ?>></td>
			<td><input type="checkbox" name='gerencia_novo' value='gerencia' <? if ($gerencia_novo == 'gerencia') echo " checked " ?>></td>
			<td><input type="checkbox" name='call_center_novo' value='call_center' <? if ($call_center_novo == 'call_center') echo " checked " ?>></td>
			<td><input type="checkbox" name='supervisor_call_center_novo' value='t' <? if ($supervisor_call_center == 't') echo " checked " ?>></td>
			<td><input type="checkbox" name='cadastros_novo' value='cadastros' <? if ($cadastros_novo == 'cadastros') echo " checked " ?>></td>
			<?if ($login_fabrica == 20) { // HD 374998 ?>
				<td><input type='checkbox' name='altera_pais_produto_novo' value='t' <?=($altera_pais_produto == 't')?'checked':''?>></td>
			<?}?>
			<?if ($login_fabrica == 91) { // HD 685194 ?>
				<td><input type='checkbox' name='promotor_wanke_novo' value='promotor' <?=($promotor_wanke_novo == 'promotor')?'checked':''?>></td>
			<?}?>
			<td><input type="checkbox" name='info_tecnica_novo' value='info_tecnica' <? if ($info_tecnica_novo == 'info_tecnica') echo " checked " ?>></td>
			<td><input type="checkbox" name='financeiro_novo' value='financeiro' <? if ($financeiro_novo == 'financeiro') echo " checked " ?>></td>
			<td><input type="checkbox" name='auditoria_novo' value='auditoria' <? if ($auditoria_novo == 'auditoria') echo " checked "?>></td>
			<?php
			if ($login_fabrica == 19) {?>
				<td><input type="checkbox" name='consulta_os_novo' value='consulta_os_novo' <? if ($consulta_os_novo == 'consulta_os_novo') echo " checked "?>></td>
			<?php
			}	
			if ($login_fabrica == 24) {?>
				<td><input type="checkbox" name='fale_conosco' value='ativo' <? if ($_POST['faleconosco'] == 'ativo') echo " checked " ?>></td>
				<td><input type="checkbox" name='atendente_callcenter' value='ativo' <? if ($_POST['atendente_callcenter'] == 'ativo') echo " checked " ?>></td><?php //HD 335548?>
				<td><input type="checkbox" name='intervensor' value='ativo' <? if ($_POST['intervensor'] == 'ativo') echo " checked " ?>></td><?php
			}
			if ($login_fabrica == 1 or $login_fabrica == 30 or $login_fabrica == 74 or $login_fabrica == 19) {?>
				<?php if($login_fabrica != 19) : ?>
					<td bgcolor="#AFC5FE"><input type="checkbox" name='sap_novo' value='ativo' <? if ($_POST['sap_novo'] == 'ativo') echo " checked " ?>></td>
				<?php endif; ?>
				<!--HD 233213--><td><input type="checkbox" name='responsavel_postos_novo' value='responsavel_postos' <? if ($_POST['responsavel_postos_novo'] == 'responsavel_postos') echo " checked " ?>></td><?php
			} ?>
		</tr><?php

		if ($login_admin == 828) {
			$sql = "SELECT *
				FROM tbl_admin 
				WHERE fabrica = $login_fabrica
				AND ativo IS TRUE
				ORDER BY ativo desc, login ;";
		} else {
			$sql = "SELECT *
				FROM tbl_admin 
				WHERE fabrica = $login_fabrica
				ORDER BY ativo desc, login ;";
		}

		$res = pg_exec($con,$sql);
		$tot = pg_numrows($res);

		echo "<tr class='subtitulo'><td colspan='100%'><font size='3'><b>Usuários Ativos</b></font></td></tr>";

		for ($i = 0; $i < $tot; $i++) {
			$admin					= trim(pg_result($res, $i, 'admin'));
			$login					= trim(pg_result($res, $i, 'login'));
			$senha					= trim(pg_result($res, $i, 'senha'));
			$login					= trim(pg_result($res, $i, 'login'));
			$senha					= trim(pg_result($res, $i, 'senha'));
			$nome_completo			= trim(pg_result($res, $i, 'nome_completo'));
			$email					= trim(pg_result($res, $i, 'email'));
			$cliente_admin			= trim(pg_result($res, $i, 'cliente_admin'));
			$cliente_admin_master	= trim(pg_result($res, $i, 'cliente_admin_master'));
			$pais					= strtoupper(trim(pg_result($res, $i, 'pais')));
			$fone					= trim(pg_result($res, $i, 'fone'));
			$ativo					= trim(pg_result($res, $i, 'ativo'));
			$fale_conosco			= trim(pg_result($res, $i, 'fale_conosco'));
			$intervensor			= trim(pg_result($res, $i, 'intervensor'));
			$atendente_callcenter	= trim(pg_result($res, $i, 'atendente_callcenter'));//HD 335548
			$supervisor_call_center = trim(pg_result($res, $i, 'callcenter_supervisor'));
			$privilegios			= trim(pg_result($res, $i, 'privilegios'));
			$consulta_os			= trim(pg_result($res, $i, 'consulta_os'));
			$admin_sap				= trim(pg_result($res, $i, 'admin_sap'));
			$responsavel_postos     = trim(pg_result($res, $i, 'responsavel_postos'));//HD 233213
			$altera_pais_produto    = trim(pg_result($res, $i, 'altera_pais_produto')); // HD 374998

			if ($ativo == 'f' && strlen($titulo) == 0) {
				$titulo = "Usuários Inativos";
				echo "<tr class='subtitulo'><td colspan='100%'><font size='3'><b>".$titulo."</b></font></td></tr>";
			}

			if ($ativo == 'f') $cor = "#F7F5F0";
			else $cor = "#F1F4FA";

			echo "<tr>\n";
			echo "<td nowrap bgcolor='$cor'>";
			echo "<input type='hidden' name='admin_$i' value='$admin'>\n";
			echo "<input type='text' name='login_$i' size='20' maxlength='20' value='$login'";
			if ($ativo == 'f') echo " readonly ";
			echo "></td>\n";
			echo "<td nowrap bgcolor='$cor'>
				<input type='password' name='senha_$i' size='15' maxlength='20' value='".sha1($senha)."'>";
			echo "</td>\n";
			echo "<td nowrap bgcolor='$cor'><input type='text' name='nome_completo_$i' size='35' maxlength='' value='$nome_completo'";
			if ($ativo == 'f') echo " readonly ";
			echo "></td>\n";
			echo "<td nowrap bgcolor='$cor'><input type='text' name='fone_$i' size='20' maxlength='' value='$fone'";
			if ($ativo == 'f') echo " readonly ";
			echo "></td>\n";
			echo "<td nowrap bgcolor='$cor'><input type='text' name='email_$i' size='40' maxlength='' value='$email'";
			if ($ativo == 'f') echo " readonly ";
			echo "></td>\n";
			if ($login_fabrica == 20) {
				echo "<td nowrap bgcolor='$cor'><input type='text' name='pais_$i' size='2' maxlength='2' value='$pais'";
				if ($ativo == 'f') echo " readonly ";
				echo "></td>\n";
			}
			if ( in_array( $login_fabrica, $abre_os_admin_arr ) ) {

				if( $ativo == 't' ) {
					echo "<td nowrap bgcolor='$cor'>";
						echo "<select name='cliente_admin_$i'>";
							echo "<option></option>";

						$sql_cliente_admin = "SELECT	cliente_admin,
												nome, cidade
												FROM tbl_cliente_admin
												WHERE fabrica = $login_fabrica
												AND abre_os_admin is true
												ORDER BY nome";

						$res_cliente_admin = pg_exec($con,$sql_cliente_admin);
						$total = pg_num_rows($res_cliente_admin);

						if ($total > 0) {

							for ($w = 0; $w < $total; $w++) {

								$xcliente_admin = pg_result($res_cliente_admin, $w, 'cliente_admin');
								$nome           = pg_result($res_cliente_admin, $w, 'nome');
								$cidade         = pg_result($res_cliente_admin, $w, 'cidade');
								$nome           = ucwords(strtolower(substr($nome,0,20)));
								$cidade         = ucwords(strtolower(substr($cidade,0,15)));

								echo "<option value='$xcliente_admin'".($xcliente_admin == $cliente_admin ? "SELECTED" : '').">$nome - $cidade</option>";
							}

						}

					echo "</select></td>";
				}
				else {
					echo "<td nowrap bgcolor='$cor'>";
					$sql_cli_admin_inativo = "SELECT nome, tbl_cliente_admin.cliente_admin
												FROM tbl_cliente_admin
												JOIN tbl_admin USING (cliente_admin)
											  WHERE tbl_admin.admin = $admin
											  AND tbl_admin.fabrica = $login_fabrica";
					$res_inativo = pg_query($con,$sql_cli_admin_inativo);
					#echo $sql_cli_admin_inativo;
					if(pg_num_rows($res_inativo) ) {
						echo pg_result($res_inativo,0,0);
						echo '<input type="hidden" name="cliente_admin_'.$i.'" value="'.pg_result($res_inativo,0,1).'" />';
					}
					else echo '&nbsp;';
					
					echo "</td>";
				
				}
				if($login_fabrica != 96) {
					echo "<td bgcolor='$cor'>";
						echo "<input type='checkbox' name='cliente_admin_master_$i' value='t'";
						if ($cliente_admin_master == 't') echo " checked";
						if ($ativo == 'f') echo " disabled ";
					echo ">&nbsp;</td>\n";
				}
			}

			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='ativo_$i' value='t'";
			if ($ativo == 't') echo " checked";
			echo "> &nbsp;</TD>\n";

			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='master_$i' value='master'";
			if (strpos($privilegios,'*') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo "> &nbsp;</TD>\n";

			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='gerencia_$i' value='gerencia'";
			if (strpos($privilegios,'gerencia') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='call_center_$i' value='call_center'";
			if (strpos($privilegios,'call_center') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='supervisor_call_center_$i' value='t'";
			if ($supervisor_call_center == "t") echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='cadastros_$i' value='cadastros'";
			if (strpos($privilegios,'cadastros') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			if ($login_fabrica == 91) { // HD 374998
				echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='promotor_wanke_$i' value='promotor'";
				if (strpos($privilegios,'promotor') !== false) echo " checked";
				if ($ativo == 'f') echo " disabled ";
				echo ">&nbsp;</TD>\n";
			}
			if ($login_fabrica == 20) { // HD 374998
				echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='altera_pais_produto_$i' value='t'";
				if ($altera_pais_produto == 't') echo " checked";
				echo ">&nbsp;</TD>\n";
			}
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='info_tecnica_$i' value='info_tecnica'";
			if (strpos($privilegios,'info_tecnica') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='financeiro_$i' value='financeiro'";
			if (strpos($privilegios,'financeiro') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='auditoria_$i' value='auditoria'";
			if (strpos($privilegios,'auditoria') !== false) echo " checked";
			if ($ativo == 'f') echo " disabled ";
			echo ">&nbsp;</TD>\n";
			if ($login_fabrica == 19) {
				echo "<td bgcolor='$cor' align='center'><input type='checkbox' name='consulta_os_$i' value='consulta_os'";
					if ($consulta_os == 't') echo " checked";
					if ($ativo == 'f') echo " disabled ";
				echo ">&nbsp;</TD>\n";
			}
			if ($login_fabrica == 24) {

				$check_fale      = ($fale_conosco == 't')         ? 'checked="checked"' : '';
				$check_inter     = ($intervensor == 't')          ? 'checked="checked"' : '';
				$check_atendente = ($atendente_callcenter == 't') ? 'checked="checked"' : '';//HD 335548

				echo "<td bgcolor=\"{$cor}\" align='center'><input type=\"checkbox\" name=\"fale_conosco_$i\" id=\"fale_conosco_$i\" value=\"1\" $check_fale ></td>";
				echo "<td bgcolor=\"{$cor}\" align='center'><input type=\"checkbox\" name=\"atendente_callcenter_$i\" id=\"atendente_callcenter_$i\" value=\"1\" $check_atendente ></td>";//HD 335548
				echo "<td bgcolor=\"{$cor}\" align='center'><input type=\"checkbox\" name=\"intervensor_$i\" id=\"intervensor_$i\" value=\"1\" $check_inter></td>";

			}

			if ($login_fabrica == 1 or $login_fabrica == 30 or $login_fabrica == 74 or $login_fabrica == 19) {
				$ativo_sap = ( $ativo == 'f' ) ? 'disabled' : '' ;
				$check_sap = ( $admin_sap == 't' ) ? 'checked="checked"' : '' ;
				$ativo_responsavel_postos = ($ativo =='f') ? 'disabled' : '';
				$check_resposavel_postos = ($responsavel_postos ==  't') ? 'checked="checked"' : '';
				if($login_fabrica != 19)
					echo "<td bgcolor=\"{$cor}\" align='center'><input type=\"checkbox\" name=\"sap_{$i}\" id=\"sap_{$i}\" value=\"1\" {$check_sap} {$ativo_sap}></td>";
				echo "<td bgcolor=\"{$cor}\" align='center'><input type=\"checkbox\" name=\"responsavel_postos_{$i}\" id=\"responsavel_postos_{$i}\" value=\"1\"{$check_resposavel_postos} {$ativo_responsavel_postos}></td>";
			}

			echo "</tr>\n";

		}?>
		<input type='hidden' name='qtde_item' value="<?=$i?>" />
	</table> 
	<br />
	<center>
		<input type="hidden" name="btn_acao" value="" />
		<?
		//HD 666788 - Funcionalidades por admin
		$sql = "SELECT fabrica FROM tbl_funcionalidade WHERE fabrica=$login_fabrica OR fabrica IS NULL";

		$res = pg_query($con,$sql);

		if 	(pg_num_rows($res)>0){?>
		<input type="button" style="cursor:pointer;" value="Funcionalidades" onclick="window.open('funcionalidades_cadastro.php');" />
		<?}?>
		<input type="button" style="cursor:pointer;" value="Gravar" onclick="if (document.frm_admin.btn_acao.value == '' ) { document.frm_admin.btn_acao.value='gravar'; document.frm_admin.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Formulário" border='0' />
	</center>
	<br />
	</form><?php

}?>

<? include "rodape.php"; ?>
