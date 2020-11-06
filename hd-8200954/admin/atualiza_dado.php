<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

// 02/07/2009 Adiciona sistema de login por MD5 desde e-mail

if (strlen($_GET['aniversario'])>0) {
	list($admin,$chave) = explode("h",$_GET['aniversario']);
	$sql = "SELECT md5(admin||login||senha) AS chave_admin,fabrica FROM tbl_admin WHERE admin=$admin";
	$res = pg_query($con,$sql);
	$chave_admin = (pg_num_rows($res)==1) ? pg_fetch_result($res, 0, chave_admin) : "";
	if ($chave == $chave_admin) {
// 	 echo "Admin: $admin<br>\n";
		add_cookie($cookie_login,"cook_admin", $admin);
		add_cookie($cookie_login,"cook_fabrica", pg_fetch_result($res, 0, fabrica));
		set_cookie_login($token_cookie,$cookie_login);
		update_fabrica($token_cookie,$cookie_login['cook_fabrica']);
        // setcookie("cook_admin", $admin);
        // setcookie("cook_fabrica", pg_fetch_result($res, 0, fabrica));
        header("Location: $PHP_SELF?admin=$admin");
        exit;
	}
}

include "autentica_admin.php";
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

.titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
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

.erro {

	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	color:#FF0000;
	background-color: #ffffff;
}
</style>
<?
if(strlen($_POST['btn_acao'])> 0) {
	$fone           = trim($_POST['fone']);
	$email          = trim($_POST['email']);
	$dia_nascimento = trim($_POST['dia_nascimento']);
	$mes_nascimento = trim($_POST['mes_nascimento']);
	if(    strlen($fone) == 0
		or strlen($email)== 0
		or strlen($dia_nascimento) == 0
		or strlen($mes_nascimento) == 0 ) {
		$msg_erro = "Por favor, preenche seu telefone, email, e data de nascimento antes de gravar";
	}
	if($dia_nascimento > 29 and $mes_nascimento == 2){
		$msg_erro = "Dia do nascimento inválido!";
	}
	if($dia_nascimento > 30 and ($mes_nascimento == 4 or $mes_nascimento == 6 or $mes_nascimento == 9 or $mes_nascimento == 11)) {
		$msg_erro = "Dia do nascimento inválido!";
	}
	if(strlen($msg_erro) == 0) {
		$sql = "UPDATE tbl_admin SET
				fone = '$fone',
				email = '$email',
				dia_nascimento = $dia_nascimento,
				mes_nascimento = $mes_nascimento
				WHERE fabrica = $login_fabrica
				AND admin = $login_admin";
		$res= pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro) == 0) {
			echo "<br><br><br><table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
			echo "<caption class='titulo'>Dados atualizados com sucesso!</caption>";
			echo "</table>";
			echo "<script language='JavaScript'>";
			echo "setTimeout(\"window.close();\",5000);";
			echo "</script>";
			exit;
		}
	}
}
?>


<?
	$admin = $_GET['admin'];
	if(strlen($msg_erro) > 0){
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}
		echo "<center><h2 class='erro'>".$msg_erro."</h></center>";

	}
	echo "<form name='frm_admin' method='post' action='$PHP_SELF '>";
	echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
	echo "<caption class='titulo'>Por favor, preenche seu tele fone e email</caption>";
	echo "<tr class='menu_top'>";
	echo "<td nowrap>LOGIN</td>";
	echo "<td nowrap>FONE</td>";
	echo "<td nowrap>EMAIL</td>";
	echo "<td nowrap>DIA NASCIMENTO</td>";
	echo "<td nowrap>MÊS NASCIMENTO</td>";
	echo "</tr>";


	if (pg_numrows($res) > 0 and strlen($_POST['btn_acao']) == 0) {
		$sql = "SELECT *
				FROM tbl_admin
				WHERE fabrica = $login_fabrica
				AND   admin   = $login_admin";
		$res = pg_exec ($con,$sql);
		$admin			=	trim(pg_result ($res,0,admin));
		$login			=	trim(pg_result ($res,0,login));
		$nome_completo	=	trim(pg_result ($res,0,nome_completo));
		$fone			=	trim(pg_result ($res,0,fone));
		$email			=	trim(pg_result ($res,0,email));
		$dia_nascimento =	trim(pg_result ($res,0,dia_nascimento));
		$mes_nascimento =	trim(pg_result ($res,0,mes_nascimento));
	}
		echo "<tr class='table_line'>\n";
		echo "<input type='hidden' name='admin' value='$admin'>\n";
		echo "<td nowrap>$login </td>\n";
		echo "<td nowrap><input type='text' name='fone' size='30' maxlength='30' value='$fone'></td>\n";
		echo "<td nowrap><input type='text' name='email' size='50' maxlength='60' value='$email'></td>\n";
		echo "<td nowrap><select name='dia_nascimento'>
				<option value='1' ";if($dia_nascimento == 1) echo "SELECTED"; echo ">1</option>
				<option value='2' ";if($dia_nascimento == 2) echo "SELECTED"; echo ">2</option>
				<option value='3' ";if($dia_nascimento == 3) echo "SELECTED"; echo ">3</option>
				<option value='4' ";if($dia_nascimento == 4) echo "SELECTED"; echo ">4</option>
				<option value='5' ";if($dia_nascimento == 5) echo "SELECTED"; echo ">5</option>
				<option value='6' ";if($dia_nascimento == 6) echo "SELECTED"; echo ">6</option>
				<option value='7' ";if($dia_nascimento == 7) echo "SELECTED"; echo ">7</option>
				<option value='8' ";if($dia_nascimento == 8) echo "SELECTED"; echo ">8</option>
				<option value='9' ";if($dia_nascimento == 9) echo "SELECTED"; echo ">9</option>
				<option value='10' ";if($dia_nascimento == 10) echo "SELECTED"; echo ">10</option>
				<option value='11' ";if($dia_nascimento == 11) echo "SELECTED"; echo ">11</option>
				<option value='12' ";if($dia_nascimento == 12) echo "SELECTED"; echo ">12</option>
				<option value='13' ";if($dia_nascimento == 13) echo "SELECTED"; echo ">13</option>
				<option value='14' ";if($dia_nascimento == 14) echo "SELECTED"; echo ">14</option>
				<option value='15' ";if($dia_nascimento == 15) echo "SELECTED"; echo ">15</option>
				<option value='16' ";if($dia_nascimento == 16) echo "SELECTED"; echo ">16</option>
				<option value='17' ";if($dia_nascimento == 17) echo "SELECTED"; echo ">17</option>
				<option value='18' ";if($dia_nascimento == 18) echo "SELECTED"; echo ">18</option>
				<option value='19' ";if($dia_nascimento == 19) echo "SELECTED"; echo ">19</option>
				<option value='20' ";if($dia_nascimento == 20) echo "SELECTED"; echo ">20</option>
				<option value='21' ";if($dia_nascimento == 21) echo "SELECTED"; echo ">21</option>
				<option value='22' ";if($dia_nascimento == 22) echo "SELECTED"; echo ">22</option>
				<option value='23' ";if($dia_nascimento == 23) echo "SELECTED"; echo ">23</option>
				<option value='24' ";if($dia_nascimento == 24) echo "SELECTED"; echo ">24</option>
				<option value='25' ";if($dia_nascimento == 25) echo "SELECTED"; echo ">25</option>
				<option value='26' ";if($dia_nascimento == 26) echo "SELECTED"; echo ">26</option>
				<option value='27' ";if($dia_nascimento == 27) echo "SELECTED"; echo ">27</option>
				<option value='28' ";if($dia_nascimento == 28) echo "SELECTED"; echo ">28</option>
				<option value='29' ";if($dia_nascimento == 29) echo "SELECTED"; echo ">29</option>
				<option value='30' ";if($dia_nascimento == 30) echo "SELECTED"; echo ">30</option>
				<option value='31' ";if($dia_nascimento == 31) echo "SELECTED"; echo ">31</option>
				</select></td>";
		echo "<td nowrap><select name='mes_nascimento'>
				<option value='1' ";if($mes_nascimento == 1) echo "SELECTED"; echo ">1</option>
				<option value='2' ";if($mes_nascimento == 2) echo "SELECTED"; echo ">2</option>
				<option value='3' ";if($mes_nascimento == 3) echo "SELECTED"; echo ">3</option>
				<option value='4' ";if($mes_nascimento == 4) echo "SELECTED"; echo ">4</option>
				<option value='5' ";if($mes_nascimento == 5) echo "SELECTED"; echo ">5</option>
				<option value='6' ";if($mes_nascimento == 6) echo "SELECTED"; echo ">6</option>
				<option value='7' ";if($mes_nascimento == 7) echo "SELECTED"; echo ">7</option>
				<option value='8' ";if($mes_nascimento == 8) echo "SELECTED"; echo ">8</option>
				<option value='9' ";if($mes_nascimento == 9) echo "SELECTED"; echo ">9</option>
				<option value='10' ";if($mes_nascimento == 10) echo "SELECTED"; echo ">10</option>
				<option value='11' ";if($mes_nascimento == 11) echo "SELECTED"; echo ">11</option>
				<option value='12' ";if($mes_nascimento == 12) echo "SELECTED"; echo ">12</option>
				</select></td>";
		echo "</tr>\n";
	?>

	<tfoot>
	<tr><td colspan=9 align='center'><input type='hidden' name='btn_acao' value=''><center><img src='imagens/btn_gravar.gif' style='cursor: pointer;' onclick="javascript: if (document.frm_admin.btn_acao.value == '' ) { document.frm_admin.btn_acao.value='gravar2' ; document.frm_admin.submit() } else { alert ('Aguarde submissão') }" ALT='Gravar Formulário' border='0'></center></td></tr></tfoot></table>
	</form>
