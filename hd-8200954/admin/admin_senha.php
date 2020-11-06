<?php

//TELA NOVA ->
header ("Location: admin_senha_n.php");
exit;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';

include_once '../class/email/mailer/class.phpmailer.php';
$mailer = new PHPMailer(); //Class para envio de email com autentica��o no servidor

/***************************
 * Configura colunas:      *
 * recebe fale conosco     *
 * atende chamados postos, *
 * atende callcenter, etc. *
 ***************************/
$fabrica_multinacional    = in_array($login_fabrica, array(20));             // A princ�pio, B&D e Intelbras tamb�m deveriam, mas n�o h� uso por enquanto
$usa_recebe_fale_conosco  = in_array($login_fabrica, array(24, 81));
$usa_atendente_callcenter = in_array($login_fabrica, array(24, 81));
$usa_intervensor          = in_array($login_fabrica, array(24));
$usa_atende_hd_postos     = in_array($login_fabrica, array(1, 3, 30, 74));
$usa_responsavel_postos   = in_array($login_fabrica, array(1, 19, 74));
$abre_os_admin_arr        = in_array($login_fabrica, array(30, 52, 85, 96)); // HD 372098
$usa_altera_pais_produto  = in_array($login_fabrica, array(20));             // HD 374998 - MLG 2011-11-09 Mudei de lugar, vai que algu�m mais quer...

$btn_acao  = strtolower($_POST["btn_acao"]);
$xbtn_acao = strtolower($_POST["xbtn_acao"]);

//$debug = 't';

/************************************
 * Devolve TRUE se a senha � v�lida *
 * (de 6 a 10 caracteres, m�nimo de *
 * 2 letras E 2 d�gitos)            *
 * Mensagem de erro se houve erro   *
 ************************************/
function validaSenhaAdmin($senha, $login) {

	$senha         = strtolower($senha);
	$count_tudo    = 0;
	$count_letras  = 0;
	$count_numeros = 0;
	$tudo          = 'abcdefghijklmnopqrstuvwxyz0123456789';
	$letras        = 'abcdefghijklmnopqrstuvwxyz';
	$numeros       = '0123456789';

	//Confere o m�nimo de 2 letras e dois n�meros

	//- verifica qtd de letras e numeros da senha digitada -//
	$count_letras   = preg_match_all('/[a-z]/i', $senha, $a_letras);
	$count_numeros  = preg_match_all('/[0-9]/',  $senha, $a_nums);
	$count_invalido = preg_match_all('/\W/',     $senha, $a_invalidos);
	if ($debug == 'pwd')
		p_echo("Senha: $senha<br />Letras: $count_letras, d�gitos: $count_numeros");

	if ($count_letras + $count_numeros > 10)   $msg_erro .= "Senha inv�lida, a senha n�o pode ter mais que 10 caracteres para o LOGIN $login <br>";
	if ($count_letras + $count_numeros <  6)   $msg_erro .= "Senha inv�lida, a senha deve conter um m�nimo de 6 caracteres para o LOGIN $login <br>";
	if ($count_letras < 2)  $msg_erro .= "Senha inv�lida, a senha deve ter pelo menos 2 letras para o LOGIN $login <br>";
	if ($count_numeros < 2) $msg_erro .= "Senha inv�lida, a senha deve ter pelo menos 2 n�meros para o LOGIN $login <br>";

	return ($msg_erro != '') ? $msg_erro : true;

}

function createHTMLInput($type, $name, $id, $value, $valor, $status=null, $enabled=true, $attrs='') {

	if (!$type or !$name)	return false;
	if ($id === true) $id = $name; //Usa o valor do "name" para o ID...

	$input = "<input type='$type' name='$name' value='$value'";
	if ($id === true) 		$input .= " id='$name'";
	if (strlen($id) > 1)	$input .= " id='$id'";
	if ($enabled === false)	$input .= ' disabled';

	if ($type == 'radio' or $type == 'checkbox') {
		$input .= ($status === true or $value == $valor) ? " checked":'';
	}

	if($type == 'checkbox'){
		$rel_id = array_reverse((explode("_", $name)));
		$rel = " rel='{$rel_id[0]}' ";
	}

	return "$input $attrs {$rel} />";

}

$ajax = @$_POST['ajax'];
if($ajax == 'ajax'){
	$tipo = $_POST['tipo'];

	if($tipo == "validaChat"){
		$sql = "SELECT qtde_chat FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
		$res = pg_query($con, $sql);

		$qtde_chat = (int) pg_fetch_result($res, 0, 'qtde_chat');

		//$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND data BETWEEN ".date("Y-m-01")." AND ".date("Y-m-t")."  LIMIT 1;";
		$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND DATE_PART('MONTH', data) = DATE_PART('MONTH', CURRENT_TIMESTAMP)  LIMIT 1;";
		$res = pg_query($con, $sql);	

		$qtde_chat_fabrica = (int) pg_fetch_result($res, 0, 'qtde_chat_fabrica');

		echo "{$qtde_chat}|{$qtde_chat_fabrica}";
	}
	
	exit;
}

include "../helpdesk/mlg_funciones.php";



if ($btn_acao == 'gravar' or $xbtn_acao == 'gravar') {

	$qtde_item = trim($_POST["qtde_item"]);

	$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND DATE_PART('MONTH', data) = DATE_PART('MONTH', CURRENT_TIMESTAMP)  LIMIT 1;";
	$res = pg_query($con, $sql);	
	$qtde_chat_fabrica_antigo = (int) pg_fetch_result($res, 0, 'qtde_chat_fabrica');

	//if ($debug=='t') echo pre_echo($_POST);
	if ($debug == 't') echo "<table>";
	for ($i = 0; $i <= $qtde_item; $i ++) {

		$admin                  = getPost("admin_$i");
		$login                  = getPost("login_$i");
		$senha                  = getPost("senha_$i");
		$nome_completo          = getPost("nome_completo_$i");
		$email                  = getPost("email_$i");
		$pais                   = strtoupper(getPost("pais_$i"));
		$fone                   = getPost("fone_$i");
		$cliente_admin          = getPost("cliente_admin_$i");
		$master                 = getPost("master_$i");
		$gerencia               = getPost("gerencia_$i");
		$call_center            = getPost("call_center_$i");
		$cadastros              = getPost("cadastros_$i");
		$info_tecnica           = getPost("info_tecnica_$i");
		$financeiro             = getPost("financeiro_$i");
		$auditoria              = getPost("auditoria_$i");
		$promotor_wanke         = getPost("promotor_wanke_$i"); // HD 685194
		$consulta_os            = getPost("consulta_os_$i")					  ? 't' : 'f';
		$sup_help_desk			= getPost("sup_help_desk_$i")                 ? 't' : 'f';
		$ativo                  = getPost("ativo_$i")                         ? 't' : 'f';
		$cliente_admin_master   = getPost("cliente_admin_master_$i")          ? 't' : 'f';
		$supervisor_call_center = getPost("supervisor_call_center_$i")        ? 't' : 'f';
		$intervensor            = getPost("intervensor_$i")                   ? 't' : 'f';
		$fale_conosco           = getPost("fale_conosco_$i")                  ? 't' : 'f';
		$atendente_callcenter   = getPost("atendente_callcenter_$i")          ? 't' : 'f';//HD 335548
		$admin_sap              = getPost("sap_$i")                           ? 't' : 'f';
		$responsavel_postos     = getPost("responsavel_postos_$i")            ? 't' : 'f';
		$altera_pais_produto    = (getPost("altera_pais_produto_$i") != null) ? 't' : 'f'; // HD 374998
		$responsavel_ti         = getPost("responsavel_ti_$i")                ? 't' : 'f'; // Sem HD, solicita��o do Boaz
		$live_help              = getPost("live_help_$i")                     ? 't' : 'f';

		if (is_numeric($admin)) {

			$sql_confere = "SELECT * FROM tbl_admin WHERE admin = " . anti_injection($admin);
			$res_confere = @pg_query($con, $sql_confere);

			if (is_resource($res_confere)) {

				// Cria uma vari�vel adm_* com cada campo do registro adm_admin, adm_login, etc.
				extract(pg_fetch_assoc($res_confere, 0), EXTR_PREFIX_ALL, 'adm');

				$camposUpdate = array(); //Este array ir� conter os campos a serem atualizados.
				$pais = ($pais == '') ? 'BR' : $pais;
				$privilegios = ($master == 'master') ? '*' :
	   							implode(',',
									array_filter(
										explode(',', "$gerencia,$call_center,$cadastros,$info_tecnica,$financeiro,$auditoria,$promotor_wanke")
									)
								);

				/*	Confere campo por campo se houve alguma altera��o.
					Se foi alterado, adiciona o nome do campo e o novo
					valor num array, j� com o caracteres especiais "escapados"	*/
				if ($admin                  != $adm_admin)                  $camposUpdate['admin']                  = pg_quote($admin, true); // � num�rico!
				if ($login                  != $adm_login)                  $camposUpdate['login']                  = pg_quote($login);
				if ($senha                  != sha1($adm_senha))            $camposUpdate['senha']                  = pg_quote($senha);
				if ($nome_completo          != $adm_nome_completo)          $camposUpdate['nome_completo']          = pg_quote($nome_completo);
				if ($email                  != $adm_email)                  $camposUpdate['email']                  = pg_quote($email);
				if ($cliente_admin          != $adm_cliente_admin)          $camposUpdate['cliente_admin']          = pg_quote($cliente_admin, true);
				if ($pais                   != $adm_pais)                   $camposUpdate['pais']                   = pg_quote($pais);
				if ($fone                   != $adm_fone)                   $camposUpdate['fone']                   = pg_quote($fone);
				if ($ativo                  != $adm_ativo)                  $camposUpdate['ativo']                  = pg_quote($ativo);
				if ($sup_help_desk          != $adm_help_desk_supervisor)   $camposUpdate['help_desk_supervisor']   = pg_quote($sup_help_desk);
				if ($cliente_admin_master   != $adm_cliente_admin_master)   $camposUpdate['cliente_admin_master']   = pg_quote($cliente_admin_master);
				if ($supervisor_call_center != $adm_callcenter_supervisor)  $camposUpdate['callcenter_supervisor']  = pg_quote($supervisor_call_center);
				if ($consulta_os            != $adm_consulta_os)            $camposUpdate['consulta_os']            = pg_quote($consulta_os);
				if ($intervensor            != $adm_intervensor)            $camposUpdate['intervensor']            = pg_quote($intervensor);
				if ($fale_conosco           != $adm_fale_conosco)           $camposUpdate['fale_conosco']           = pg_quote($fale_conosco);
				if ($atendente_callcenter   != $adm_atendente_callcenter)   $camposUpdate['atendente_callcenter']   = pg_quote($atendente_callcenter);
				if ($admin_sap              != $adm_admin_sap)              $camposUpdate['admin_sap']              = pg_quote($admin_sap);
				if ($responsavel_postos     != $adm_responsavel_postos)     $camposUpdate['responsavel_postos']     = pg_quote($responsavel_postos);
				if ($altera_pais_produto    != $adm_altera_pais_produto)    $camposUpdate['altera_pais_produto']    = pg_quote($altera_pais_produto);
				if ($responsavel_ti         != $adm_responsavel_ti)         $camposUpdate['responsavel_ti']         = pg_quote($responsavel_ti);
				if ($privilegios			!= $adm_privilegios)			$camposUpdate['privilegios']			= pg_quote($privilegios);
				if ($live_help              != $adm_live_help)              $camposUpdate['live_help']              = pg_quote($live_help);

				if (strlen($admin) > 0 and $ativo == 't') {

					if (isset($camposUpdate['senha'])) { //S� existe essa chave se alterou a senha...
						if (validaSenhaAdmin($senha, $login) !== true) $msg_erro = validaSenhaAdmin($senha, $login);
					}

				}

				if($live_help == 't' AND (empty($nome_completo) OR !is_email($email))){
					$msg_erro = "Para ser cadastrado no chat os usu�rios devem ter nome e e-mail v�lidos!";
				}

				if (strlen($admin) > 0 and strlen($msg_erro) == 0 and count($camposUpdate)) {
					// a pedido do Boulivar, verificar antes de ativar um usu�rio, se o cliente_admin dele pode abrir OS. HD 372098
					//pre_echo(pg_fetch_assoc($res_confere, 0), "Dados do ADMIN $admin");

					if (is_numeric($cliente_admin) and $ativo == 't') {

						$sql     = "SELECT abre_os_admin FROM tbl_cliente_admin WHERE cliente_admin = $cliente_admin";
						$res     = pg_query($con,$sql);
						$abre_os = pg_fetch_result($res,0,0);

						if ($abre_os == 'f') {
							$sql = "UPDATE tbl_cliente_admin SET abre_os_admin = 't' WHERE cliente_admin = $cliente_admin AND fabrica = $login_fabrica";
							pg_query($con, $sql);
						}

					}

					$listaCamposUpdate = implode(',', array_keys($camposUpdate));
					$valoresParaUpdate = implode(',', array_values($camposUpdate));
					$sql = " UPDATE tbl_admin  ".
							"   SET ($listaCamposUpdate) = ($valoresParaUpdate) ".
							" WHERE admin = $admin";

					if ($debug == 't' and count($camposUpdate)) {
						echo "<tr><td>Update Admin $admin</td><td>";
						pre_echo($camposUpdate);
						echo "</td><td>";
						pre_echo($sql);
						echo "</td></tr>\n"; //continue;
					}

					$res = @pg_query($con,$sql);

					$msg_erro = pg_last_error($con);

					if (strpos($msg_erro, 'duplicate key'))
						$msg_erro = "Este usu�rio j� est� cadastrado e n�o pode ser duplicado.";

					if ($ativo == 'f') {
						/**
						 * @hd 763097 - toda vez que o usuario � inativado, excluir todos os registros na tbl_programa_restrito
						 */
						$sqlDelPR = "DELETE FROM tbl_programa_restrito WHERE admin = $admin AND fabrica = $login_fabrica";
						$qryDelPR = pg_query($con, $sqlDelPR);
					}

				}

			}

		}

	}

	/*if (strpos($login, "-" ) or strpos($login, ".") or strpos($login, "/") or strpos($login, " ")) {
		$msg_erro .= "O LOGIN $login n�o pode ser preenchido com os caracteres:<br> '.'(ponto), '/'(barra), '-'(h�fen),' '(espa�o em branco).<br>";
	}

	if (strlen($login)>50) {
			$msg_erro .= "O campo LOGIN n�o pode ter mais que 50 caracteres.<br>";
	}*/
	$login = '';
	$login                  = strtolower(getPost("login_novo"));
	$senha                  = strtolower(getPost("senha_novo"));
	$nome_completo          = getPost("nome_completo_novo");
	$email                  = getPost("email_novo");
	$pais                   = strtoupper(getPost("pais_novo"));
	$fone                   = getPost("fone_novo");
	$cliente_admin          = getPost("cliente_admin_novo");
	$master                 = getPost("master_novo");
	$sup_help_desk			= getPost("sup_help_desk_$i");
	$gerencia               = getPost("gerencia_novo");
	$call_center            = getPost("call_center_novo");
	$cadastros              = getPost("cadastros_novo");
	$info_tecnica           = getPost("info_tecnica_novo");
	$financeiro             = getPost("financeiro_novo");
	$auditoria              = getPost("auditoria_novo");
	$promotor_wanke         = getPost("promotor_wanke_novo"); // HD 685194
	$consulta_os            = getPost("consulta_os_novo")                   ? 't' : 'f';
	$ativo                  = getPost("ativo_novo")                         ? 't' : 'f';
	$cliente_admin_master   = getPost("cliente_admin_master_novo")          ? 't' : 'f';
	$supervisor_call_center = getPost("supervisor_call_center_novo")        ? 't' : 'f';
	$intervensor            = getPost("intervensor_novo")                   ? 't' : 'f';
	$fale_conosco           = getPost("fale_conosco_novo")                  ? 't' : 'f';
	$atendente_callcenter   = getPost("atendente_callcenter_novo")          ? 't' : 'f';//HD 335548
	$admin_sap              = getPost("sap_novo")                           ? 't' : 'f';
	$responsavel_postos     = getPost("responsavel_postos_novo")            ? 't' : 'f';
	$altera_pais_produto    = (getPost("altera_pais_produto_novo") != null) ? 't' : 'f'; // HD 374998
	$responsavel_ti         = getPost("responsavel_ti_novo")                ? 't' : 'f'; // Sem HD, solicita��o do Boaz
	$live_help              = getPost("live_help")                          ? 't' : 'f';

	$and_supervisor         = (strlen($sup_help_desk) > 0) ? "'$sup_help_desk' ,"   : "'f' ,";

	$privilegios = ($master == 'master') ? '*' :
	   							implode(',',
									array_filter(
										explode(',', "$gerencia,$call_center,$cadastros,$info_tecnica,$financeiro,$auditoria,$promotor_wanke")
									)
								);

	$sql = "SELECT fn_fabrica_chat($login_fabrica, $login_admin);";
	$res = pg_query($con, $sql);
	$envia_email = pg_fetch_result($res, 0, 0);

	if($envia_email == 't'){ //se a fun��o inseri registro envia email para o admin
		$sql = "SELECT nome_completo, email FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica};";
		$res = pg_query($con, $sql);
		$email_admin = pg_fetch_result($res, 0, 'email');

		$sql = "SELECT qtde_chat FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
		$res = pg_query($con, $sql);
		$qtde_chat = (int) pg_fetch_result($res, 0, 'qtde_chat');

		$sql = "SELECT MAX(qtde) AS qtde_chat_fabrica FROM tbl_fabrica_chat WHERE fabrica = $login_fabrica AND DATE_PART('MONTH', data) = DATE_PART('MONTH', CURRENT_TIMESTAMP)  LIMIT 1;";
		$res = pg_query($con, $sql);	
		$qtde_chat_fabrica = (int) pg_fetch_result($res, 0, 'qtde_chat_fabrica');

		$valor_fatura = $qtde_chat_fabrica - $qtde_chat_fabrica_antigo;
		$valor_total_fatura = $qtde_chat_fabrica - $qtde_chat;

		if($valor_fatura > $valor_total_fatura)
			$valor_fatura = $valor_fatura - $qtde_chat;

		$valor_total_fatura	= number_format(($valor_total_fatura) * 200 ,2,",",".");
		$valor_fatura 		= number_format(($valor_fatura) * 200 ,2,",",".");

		if($qtde_chat_fabrica > 4){
			$descricao_total = "Que somado aos outros usu�rios excedentes passa  ao total de R$ {$valor_total_fatura} na fatura mensal.<br />";
		}

		$mensagem = "
				<div>
					Sua empresa cadastrou outro usu�rio no sistema de atendimento do CHAT, estamos incluindo na fatura o valor de R$ {$valor_fatura} mensalmente.<br />
					{$descricao_total}
					Valor referente ao uso concomitante de usu�rios no CHAT, cobrado na fatura mensal sem necessidade de aditivos ao contrato
				</div><br />
				<p>Telecontrol Networking<br>www.telecontrol.com.br</p>";

        $mailer->IsSMTP();
        $mailer->IsHTML();                    
        $mailer->AddAddress($email_admin);
        $mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");
        $mailer->Subject = "AUTORIZA��O DE COBRAN�A / N�MERO M�XIMO DE USU�RIOS EXCEDIDO";
        $mailer->Body = $mensagem;

        $mailer->Send();
	}
	//$msg_erro .= pg_last_error($con);

	if (strlen ($login) > 0 and $ativo <> 'f') {

		if (validaSenhaAdmin($senha, $login) !== true)	$msg_erro = validaSenhaAdmin($senha, $login);
		if (!is_email($email))							$msg_erro.= "E-mail digitado ($email) inv�lido!<br />";
		$pais = (strlen($pais) == 0) ? 'BR' : strtoupper($pais);

		if (strlen($msg_erro) == 0) {

			$camposInsert['fabrica']               = pg_quote($login_fabrica, true);
			$camposInsert['login']                 = pg_quote($login);
			$camposInsert['senha']                 = pg_quote($senha);
			$camposInsert['nome_completo']         = pg_quote($nome_completo);
			$camposInsert['email']                 = pg_quote($email);
			$camposInsert['pais']                  = pg_quote($pais);
			$camposInsert['fone']                  = pg_quote($fone);
			$camposInsert['cliente_admin']         = pg_quote($cliente_admin, true);
			$camposInsert['help_desk_supervisor']  = pg_quote($sup_help_desk);
			$camposInsert['consulta_os']           = pg_quote($consulta_os);
			$camposInsert['ativo']                 = pg_quote($ativo);
			$camposInsert['cliente_admin_master']  = pg_quote($cliente_admin_master);
			$camposInsert['callcenter_supervisor'] = pg_quote($supervisor_call_center);
			$camposInsert['intervensor']           = pg_quote($intervensor);
			$camposInsert['fale_conosco']          = pg_quote($fale_conosco);
			$camposInsert['atendente_callcenter']  = pg_quote($atendente_callcenter);
			$camposInsert['responsavel_postos']    = pg_quote($responsavel_postos);
			$camposInsert['altera_pais_produto']   = pg_quote($altera_pais_produto); /* HD 374998 */
			$camposInsert['responsavel_ti']		   = pg_quote($responsavel_ti);
			$camposInsert['privilegios']           = pg_quote($privilegios);
			$camposInsert['live_help']             = pg_quote($live_help);

			if ($login_fabrica == 3) {
				$camposInsert['admin_sap'] = pg_quote($admin_sap);
			}

			$campos = implode(',', array_keys($camposInsert));
			$valores= implode(',', array_values($camposInsert));

			$sql = "INSERT INTO tbl_admin ($campos)
						VALUES
					($valores)";

			//if ($debug=='t')
			//pre_echo($sql, "Cadastrando um novo Usu�rio"); die;
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

		}

		if (strpos($msg_erro, 'duplicate key'))
			$msg_erro = "Este usu�rio j� est� cadastrado e n�o pode ser duplicado.";


		if (strlen ($msg_erro) == 0) {
			header ("Location: $PHP_SELF");
			exit;
		}

	}
}

if ($btn_acao == 'gravar2') {

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
	$promotor_wanke         = $_POST['promotor_wanke_'.$i];
	$responsavel_postos     = $_POST['responsavel_postos_'.$i]; #HD 233213
	$altera_pais_produto    = $_POST["altera_pais_produto_$i"]; // HD 374998
	$responsavel_ti         = $_POST["responsavel_ti_$i"]; // HD 374998
	$live_help              = $_POST['live_help_'.$i];

	$login = trim(strtolower($login));
	$senha = trim(strtolower($senha));

	if (strlen($admin) > 0 ) {
		$sql = "UPDATE tbl_admin SET
					senha		      = '$senha'
				WHERE tbl_admin.admin = '$login_admin'";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);
	}

	if (strlen($msg_erro) == 0) {
		header("Location: $PHP_SELF");
		exit;
	}

}

$title       = "Privil�gios para o Administrador";
$cabecalho   = "Cadastro de Postos Autorizados";
$layout_menu = "gerencia";

include 'cabecalho.php';


$sql_nome_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica};";
$res_nome_admin = @pg_query($con, $sql_nome_admin);
$nome_admin 	= @pg_fetch_result($res_nome_admin, 0, 'nome_completo');

?>

<style type="text/css">
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
	margin: auto;
	width: 700px;
}

.subtitulo{
	font-family     : verdana;
	font-size       : 16px;
	font-weight     : 700;
	font-style      : normal;
	color           : #FFFFFF;
	text-transform  : none;
	text-decoration : none;
	letter-spacing  : normal;
	word-spacing    : 0;
	line-height     : 20px;
	text-align      : center;
	background-color: #7092BE;
}
.fa {
	max-width: 16px;
	max-height:16px;
}
table.tabela > tbody > tr:hover {
	background-color: #ff9;
	color: #5a6d9c;
}
table.tabela tr td:nth-of-type(3) > input {
	width: 220px;
}

table.tabela td{
	vertical-align: middle !important;
}
</style>

<script type='text/javascript' src="js/jquery-1.6.1.min.js"></script>
<script type='text/javascript' src="../plugins/jquery/apprise/apprise-1.5.min.js"></script>
<link rel="stylesheet" href="../plugins/jquery/apprise/apprise.min.css" type="text/css" />
<script type='text/javascript'>

	$(document).ready(function() {
		$('input[name*="live_help_"]').click(function() {
		  	var id 		= $(this).attr('rel');
		  	var nome 	= $("input[name='nome_completo_"+id+"']").val();
		  	var email 	= $("input[name='email_"+id+"']").val();
		  	var msg_erro 	= 0;

		  	if(nome.length < 3)
		  		msg_erro = 1;

		  	if(!checkMail(email)){
		  		msg_erro = 1
		  	}

		  	if(msg_erro == 1){
		  		$(this).attr("checked",false);
				var pergunta = "Para ser cadastrado no chat o usu�rio deve ter nome e e-mail v�lidos!";
				apprise(pergunta, {
							'animate'	: true
						}
				);
		  	}
		});
	});

	function checkMail(mail){
		var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
		if(typeof(mail) == "string"){
			if(er.test(mail)){ 
				return true; 
			}
		}else if(typeof(mail) == "object"){
			if(er.test(mail.value)){ 
				return true; 
			}
		}else{
			return false;
		}
	}

	function formatCurrency(num) {
	    num = isNaN(num) || num === '' || num === null ? 0.00 : num;
	   	return parseFloat(num).toFixed(2);
	}

	function formSubmit(){
		if (document.frm_admin.btn_acao.value == '' ) { 
			validaChat();
		} else { 
			alert ('Aguarde submiss�o');
		}
	}

	function validaChat(){
		var total_chat_check = $('input[name*="live_help_"]:checked').length;

		$.ajax({
		  	type: "POST",
		 	url:  "<?php echo $_SERVER['PHP_SELF'];?>",
		  	data: "ajax=ajax&tipo=validaChat",
			success: function(retorno){
    			data = retorno.split('|'); 

    			qtde_chat 			= data[0];
    			qtde_chat_fabrica 	= data[1];
    			var total_liberado 	= qtde_chat_fabrica > qtde_chat ? qtde_chat_fabrica : qtde_chat;
    			var diferenca 		= total_chat_check - total_liberado;

    			if(total_chat_check > total_liberado){
    				//var pergunta = "<?php echo $nome_admin;?>, Mensagem....";
					perguntaChat(diferenca, qtde_chat_fabrica, qtde_chat);
    			}else{
				  	document.frm_admin.btn_acao.value='gravar'; 
					document.frm_admin.submit();
    			} 

    			return false;
  			}
		});
	}

	function perguntaChat(diferenca, usuario_cadastrado, numero_permitido){
		var total_chat_check = $('input[name*="live_help_"]:checked').length;

		if(usuario_cadastrado > 0){
			var usuario_acima  = total_chat_check - numero_permitido; 
		}else{
			var usuario_acima  = total_chat_check - numero_permitido;
			usuario_cadastrado = numero_permitido;
		}
		
		var soma_diferenca = formatCurrency(usuario_acima * 200);
		
		var pergunta  = "<div style='text-align: justify; width: 600px;'><b><?php echo $nome_admin;?></b>, voc� excedeu o n�mero m�ximo de usu�rios gratuitos para atendimento on line: <br><br>Sua empresa hoje tem "+usuario_cadastrado+" usu�rios j� cadastrados, "+usuario_acima+" acima do n�mero m�ximo de usu�rios gratuitos, importando na cobran�a mensal de R$ "+soma_diferenca+"</div>";

			pergunta += "<div style='text-align: center; padding: 10px 50px; color: #F00;  width: 500px;'>Ap�s o aceite ser� inclu�do na fatura o valor de R$ 200.00 (duzentos reais) por usu�rio excedente e a cobran�a ser� suspensa somente no m�s subsequente ao cancelamento dessa inclus�o.</div>";

		apprise(pergunta, {
			'verify' 	: true, 
			'textYes'	: 'Concordo!', 
			'textNo'	: 'N�o Concordo',
			'animate'	: true
			}, 
			function(resposta){
			    if(resposta){ 
			    	var pergunta = "<div style='text-align: justify; width: 400px;'>Concordo com a inclus�o desse servi�o, estou ciente dos valores que ser�o cobrados contra nossa empresa, dispensando a necessidade de assinatura de aditivo ao contrato, ficando automaticamente aceita a cobran�a ap�s esta intera��o.<br><br><b>Tem certeza da inclus�o?</b></div>";
			    	
			    	apprise(pergunta, {
						'verify' 	: true, 
						'textYes'	: 'Sim', 
						'textNo'	: 'N�o',
						'animate'	: true
						}, 
						function(resposta){
							if(resposta){
						    	document.frm_admin.btn_acao.value='gravar'; 
								document.frm_admin.submit();
							}else{
								validaChat();
							}
						});
			    } else { 
			    	//alert('nao concordo');
			    	return false;
			    }
		});
	}

	function retiraAcentos(obj) {
		obj.value = obj.value.replace(/\W/g, "");
	}

	$(function() {
		$('#ativo_novo').change(function() {
			if ($(this).is(':checked')) {
				$('.novo').removeAttr('disabled');
			} else {
				$('.novo').attr('disabled','disabled');
			}
		});

		/* title em todos os checkboxes, para facilitar */
		$(':checkbox[name^=ativo_]').attr('title','Usu�rio ativo?');
		$(':checkbox[name^=master_]').attr('title','Usuario MASTER');
		$(':checkbox[name^=sup_help_desk_]').attr('title','Supervisor HelpDesk - Gerencia chamado Telecontrol');
		$(':checkbox[name^=responsavel_ti_]').attr('title','Gerente TI - Respons�vel integra��o, FTP, etc.');
		$(':checkbox[name^=gerencia_]').attr('title','�rea de Gerencia (relat�rios gerenciais, BI, gerenciamneto de Usu�rios...)');
		$(':checkbox[name^=cadastros_]').attr('title','�rea de Cadastros (postos, produtos, pe�as...)');
		$(':checkbox[name^=call_center_]').attr('title','�rea de Call-Center');
		$(':checkbox[name^=supervisor_call_center_]').attr('title','Supervidor do Call-Center');
		$(':checkbox[name^=live_help_]').attr('title','Tira d�vidas via Chat');
		$(':checkbox[name^=info_tecnica_]').attr('title','�rea de Informa��es T�cnicas e Comunicados');
		$(':checkbox[name^=financeiro_]').attr('title','�rea Financeira');
		$(':checkbox[name^=auditoria_]').attr('title','�rea de Auditoria');
		$(':checkbox[name^=promotor_wanke_]').attr('title','Promotor Wanke');
		$(':checkbox[name^=responsavel_postos_]').attr('title','Atende Postos Autorizados');
		$(':checkbox[name^=consulta_os_]').attr('title','Cliente externo consulta OS');
		$(':checkbox[name^=altera_pais_produto_]').attr('title','O usu�rio pode alterar para que pais est� disponibilizado um produto');
		$(':checkbox[name^=sap_]').attr('title','<?=($login_fabrica == 1) ? "Atende HelpDesk do Posto":"Inspetor";?>');
		$(':checkbox[name^=atendente_callcenter_]').attr('title','Usu�rio do Call-Center');
		$(':checkbox[name^=fale_conosco_]').attr('title','Recebe e-mail do "Fale Conosco" integrado com a Telecontrol');
		$(':checkbox[name^=intervensor_]').attr('title','Intervensor de Call-Center');
	});
</script><?php

if (strlen($msg_erro) > 0) {?>
	<div class="msg_erro"><?=$msg_erro;?></div><?php
}

$sql = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res = pg_query($con,$sql);

$privilegios = pg_fetch_result($res, 0, 0);

if ($login_privilegios != '*') echo "<center><h1>Voc� n�o tem permiss�o para gerenciar usu�rios.</h1></center>";

if ($abre_os_admin_arr) {
	$th_cliente_admin = "<th>Cliente Admin";
	//$th_cliente_admin.= ($login_fabrica != 19) ? ' Master' : '';
	$th_cliente_admin.= "</th>\n";
	if ($login_fabrica != 96) $th_cliente_admin .= "<th>Cliente Admin Master</th>";
}

if ($usa_atende_hd_postos) {
	$th_hd_posto = "<th>";
	$th_hd_posto.= ($login_fabrica == 1) ? "<abbr title='Atendente de Chamados dos Postos'>SAP</abbr>" : "Inspetor";
	$th_hd_posto.= "</th>\n";
}

if ($usa_responsavel_postos) {
	$th_hd_posto .= "<th>Respons�vel Postos</th>\n";
}

unset($th_fale_conosco); //Come�a do nada...

if ($usa_atendente_callcenter) {
	$th_fale_conosco .= "<th>Atendente CallCenter</th>\n";
}

if ($usa_recebe_fale_conosco) {
	$th_fale_conosco .= "<th>Recebe Fale Conosco</th>\n";
}

if ($usa_intervensor) {
	$th_fale_conosco .= "<th>Intervensor de Callcenter</th>\n";
}

if ($usa_altera_pais_produto)
	$th_altera_pais_prod = '<th title="O usu�rio poder� liberar produtos para outros pa�ses">Altera Pa�s Prod.</th>'."\n"; // HD 374998

if ($fabrica_multinacional)
	$th_pais = "<th>PA�S</th>\n";

if ($login_privilegios == '*')
	$th_sup_hd	= "<th title='Supervisor do Help-Desk'>Sup. Help-Desk</th>\n";

	ob_start();?>
	<tr class='titulo_coluna' style='cursor:default'>
		<th>Login</th>
		<th>Senha</th>
		<th>Nome</th>
		<th>Fone</th>
		<th>Email</th>
		<?=$th_pais . $th_cliente_admin?>
		<th>&nbsp;Ativo&nbsp;</th>
		<th>Master</th>
		<?=$th_sup_hd?>
		<th>&nbsp;Chat&nbsp;</th>
		<th>Gerente TI</th>
		<th>Ger�ncia</th>
		<th>Cadastros</th>
		<th>Call-Center</th>
		<th>Supervisor Call-Center</th>
		<th>Info T�cnica</th>
		<th>Financeiro</th>
		<th>Auditoria</th><?php
		// Campos especficos dos fabricantes
		echo ($login_fabrica == 91)? '<th title="Acesso para promotores, limitado ao Call-Center">Promotor</th>'."\n" : ''; // HD 685194
		echo $th_altera_pais_prod;
		echo $th_hd_posto;
		echo ($login_fabrica == 19) ? "<th>Usu�rio Consulta OS</th>\n" : '';
		echo $th_fale_conosco;	?>
	</tr><?php

	$tbl_headers = ob_get_clean();

if (strpos($privilegios,'*') === false) {?>
<form method="post" name="frm_admin">
	<input name="btn_acao" type="hidden" value="" />
	<table class='border' id='admins' align='center' border='0' cellpadding='1' cellspacing='1'>
		<thead><?=$tbl_headers?></thead>
		<tbody><?php
	$sql = "SELECT *
              FROM tbl_admin
             WHERE fabrica = $login_fabrica
               AND admin   = $login_admin";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$admin					= trim(pg_fetch_result($res, 0, 'admin'));
		$login					= trim(pg_fetch_result($res, 0, 'login'));
		$senha					= trim(pg_fetch_result($res, 0, 'senha'));
		$nome_completo			= trim(pg_fetch_result($res, 0, 'nome_completo'));
		$fone					= trim(pg_fetch_result($res, 0, 'fone'));
		$email					= trim(pg_fetch_result($res, 0, 'email'));
		$privilegios			= trim(pg_fetch_result($res, 0, 'privilegios'));
		$cliente_admin_master	= trim(pg_fetch_result($res, 0, 'cliente_admin_master'));
		$supervisor_call_center	= trim(pg_fetch_result($res, 0, 'callcenter_supervisor'));
		$fale_conosco			= trim(pg_fetch_result($res, 0, 'fale_conosco'));
		$intervensor			= trim(pg_fetch_result($res, 0, 'intervensor'));
		$atendente_callcenter	= trim(pg_fetch_result($res, 0, 'atendente_callcenter'));	//HD 335548
		$ativo					= trim(pg_fetch_result($res, 0, 'ativo'));
		$admin_sap				= trim(pg_fetch_result($res, 0, 'admin_sap'));
		$live_help				= trim(pg_fetch_result($res, 0, 'live_help'));
		$responsavel_postos     = trim(pg_fetch_result($res, 0, 'responsavel_postos'));		//HD 233213
		$responsavel_ti         = trim(pg_fetch_result($res, 0, 'responsavel_ti'));			//SEM HD
		$altera_pais_produto    = trim(pg_fetch_result($res, 0, 'altera_pais_produto'));	// HD 374998

		if ($debug == 't') {
			echo "<caption>";
				echo "Privil�gios de login: $login_privilegios<br />Privil�gios de usu�rio: $privilegios";
			echo "</caption>\n";
		}

		echo "<tr class='table_line'>\n";
		echo "<input type='hidden' name='admin_$i' value='$admin'>\n";

		echo "<td nowrap>$login </td>\n";
		echo "<td nowrap><input type='password' name='senha_$i'         size='10' maxlength='' value='".sha1($senha)."'></td>\n";
		echo "<td nowrap><input type='text'     name='nome_completo_$i' size='10' maxlength='' value='$nome_completo'></td>\n";
		echo "<td nowrap><input type='text'     name='fone_$i'          size='15' maxlength='' value='$fone'></td>\n";
		echo "<td nowrap><input type='text'     name='email_$i'         size='20' maxlength='' value='$email'></td>\n";

		if ($abre_os_admin_arr) {

			echo "<td nowrap>";
			echo "<select name='cliente_admin'>";
			echo "<option></option>";

			$sql_cliente_admin = "SELECT cliente_admin, nome, cidade
                                    FROM tbl_cliente_admin
                                   WHERE abre_os_admin	IS TRUE
                                     AND fabrica		=  $login_fabrica
                                   ORDER BY nome";

			$res_cliente_admin = pg_query($con,$sql_cliente_admin);
			$total             = pg_num_rows($res_cliente_admin);

			if ($total > 0) {

				for ($w = 0; $w < $total; $w++) {

					$cliente_admin = pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
					$nome          = pg_fetch_result($res_cliente_admin, $w, 'nome');
					$cidade        = pg_fetch_result($res_cliente_admin, $w, 'cidade');
					$nome          = ucwords(strtolower(substr($nome   , 0 , 20)));
					$cidade        = ucwords(strtolower(substr($cidade , 0 , 15)));

					echo "<option value='$cliente_admin'>$nome - $cidade</option>";

				}

			}

			echo "</select>
			</td>";
			if ($login_fabrica != 96) {
				echo '<td>' . createHTMLInput('checkbox', "cliente_admin_master_$i", null, 't', $cliente_admin_master, null, false) . '&nbsp;</td>';
			}

		}

		echo '<td>' . createHTMLInput('checkbox', "ativo_$i",  true, 't',      $ativo,  ($ativo == 't'),       false) . '&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "master_$i", true, 'master', $master, ($master == 'master'), false) . '&nbsp;</td>';

		if ($login_privilegios == '*') {
			echo '<td>' . createHTMLInput('checkbox', "sup_help_desk_$i",  true, 't', $sup_help_desk,  null, false) . '&nbsp;</td>';
		}

		echo '<td>' . createHTMLInput('checkbox', "live_help_$i",  true, 't', $live_help,  null, false) . '&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "responsavel_ti_$i",     null, 't', $responsavel_ti, null, false) . '&nbsp;</td>';

		// Privil�gios
		echo '<td>' . createHTMLInput('checkbox', "gerencia_$i"    , null, 'gerencia'    , null, (strpos($privilegios, 'gerencia')     !== false), false).'&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "cadastros_$i"   , null, 'cadastros'   , null, (strpos($privilegios, 'cadastros')    !== false), false).'&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "call_center_$i" , null, 'call_center' , null, (strpos($privilegios, 'call_center')  !== false), false).'&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "supervisor_call_center_$i", null, 't' , $supervisor_call_center   , null				         , false).'&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "info_tecnica_$i", null, 'info_tecnica', null, (strpos($privilegios, 'info_tecnica') !== false), false).'&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "financeiro_$i"  , null, 'financeiro'  , null, (strpos($privilegios, 'financeiro')   !== false), false).'&nbsp;</td>';
		echo '<td>' . createHTMLInput('checkbox', "auditoria_$i"   , null, 'auditoria'   , null, (strpos($privilegios, 'auditoria')    !== false), false).'&nbsp;</td>';

		if ($login_fabrica == 91)  // HD 685194
			echo '<td>' . createHTMLInput('checkbox', "promotor_wanke_$i", null, 'promotor', $promotor, null, false) . '&nbsp;</td>';

		if ($usa_altera_pais_produto)  // HD 374998
			echo '<td>' . createHTMLInput('checkbox', "altera_pais_produto_$i", null, 't', $altera_pais_produto, null, false) . '&nbsp;</td>';

		if ($usa_atende_hd_postos)
			echo '<td>' . createHTMLInput('checkbox', "sap_$i", "sap_$i", 't', $admin_sap, null, false) . '&nbsp;</td>';

		if ($usa_responsavel_postos)
			echo '<td>' . createHTMLInput('checkbox', "responsavel_postos_$i", null, 't', $responsavel_postos, null, false) . '&nbsp;</td>';

		if ($usa_atendente_callcenter)
			echo '<td>' . createHTMLInput('checkbox', "atendente_callcenter_$i", null, 't', $atendente_callcenter, null, false) . '&nbsp;</td>';

		if ($usa_recebe_fale_conosco)
			echo '<td>' . createHTMLInput('checkbox', "fale_conosco_$i", null, 't', $fale_conosco, null, false) . '&nbsp;</td>';

		if ($usa_intervensor)
			echo '<td>' . createHTMLInput('checkbox', "intervensor_$i", null, 't', $intervensor, null, false) . '&nbsp;</td>';

		if ($login_fabrica == 19)
			echo '<td>' . createHTMLInput('checkbox', "consulta_os_$i", null, 't', $consulta_os, null, false) .  '&nbsp;</td>';

		echo "</tr>\n";

	}?>
	</table>
	</form>
	<table align='center'>
		<tr>
			<td colspan="9" align='center'>
				<input type='hidden' name='btn_acao' value='' />
				<center>
					<img src='imagens/btn_gravar.gif' style='cursor: pointer;' onclick="if (document.frm_admin.btn_acao.value == '' ) { document.frm_admin.btn_acao.value='gravar2' ; document.frm_admin.submit() } else { alert ('Aguarde submiss�o') }" ALT='Gravar Formul�rio' border='0' />
				</center>
			</td>
		</tr>
	</table><?php

} else {

	$enabled = ($ativo == 't'); // Determina se os checkbox v�o estar ativos ou n�o, dependendo do check 'ativo'?>

	<div style="width:700px;padding:1ex 1em;background:#d9e2ef;color:#596d9b;text-align:justify;font: normal normal 12px verdana, arial, sans-serif;margin:auto;">
		Para incluir um novo administrador insira o login e senha desejados e selecione os privil�gios que deseja conceder a este usu�rio.
		Para alterar qualquer informa��o basta clicar sobre o campo desejado e efetuar a troca.<br>
		<b>OBS.: Clique em gravar logo ap�s inserir ou alterar a configura��o de um administrador.</B><br><br>
		O campo <b>LOGIN</b> n�o pode ser preenchido com os caracteres: "."(ponto), "/"(barra), "-"(h�fen)," "(espa�o em branco).
		<br><br> A <b>SENHA</b> deve ter entre 06 e 10 caracteres, sendo ao menos 02 letras (de A � Z) e 02 n�meros (de 0 a 9),
		por exemplo: bra500, tele2007, ou assist0682.
	</div>
	<form name="frm_admin" method="post" action="<? echo $PHP_SELF ?>"><?php

	if ($login_fabrica == 3) {?>
		<br />
		<center>
		<input type="hidden" name="xbtn_acao" value="">
		<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick=" formSubmit();" ALT="Gravar Formul�rio" border='0'>
		<!-- if (document.frm_admin.xbtn_acao.value == '') { document.frm_admin.xbtn_acao.value='gravar'; document.frm_admin.submit() } else { alert ('Aguarde submiss�o') }	 //-->
		</center>
		<br /><?php
	} ?>

	<div class='tableContainer'>
	<table width='700' align='center' border='0' cellpadding="1" cellspacing="1" class="tabela">
		<thead class='fixedHeader'>
			<?=$tbl_headers?>
		</thead>
		<tbody class='scrollContent'>
		<tr bgcolor="#D9E2EF">
			<td nowrap ><input type='text' name='login_novo'         size='20' maxlength='20' value='<?=$login_novo ?>' onkeyup='retiraAcentos(this)'></td>
			<td nowrap ><input type='text' name='senha_novo'         size='15' maxlength='10' value='<?=$senha_novo ?>'></td>
			<td nowrap ><input type='text' name='nome_completo_novo' size='35' maxlength=''   value='<?=$nome_completo_novo ?>'></td>
			<td nowrap ><input type='text' name='fone_novo'          size='20' maxlength='20' value='<?=$fone_novo ?>'></td>
			<td nowrap ><input type='text' name='email_novo'         size='40' maxlength=''   value='<?=$email_novo ?>'></td><?php

			if ($fabrica_multinacional) {
				echo "<td nowrap>";
				echo createHTMLInput('text', 'pais_novo', null, $pais_novo, null, null, false, " size='2' maxlength='2' class='novo'");
				echo "</td>\n";
			}

			if ($abre_os_admin_arr) {
				echo "<td nowrap >";
					echo "<select name='cliente_admin'>";
						echo "<option></option>";

					$sql_cliente_admin = "SELECT cliente_admin,
											nome, cidade
											FROM tbl_cliente_admin
											WHERE fabrica = $login_fabrica
											AND abre_os_admin IS TRUE
											ORDER BY nome";

					$res_cliente_admin = pg_query($con, $sql_cliente_admin);

					if (pg_num_rows($res_cliente_admin) > 0) {

						for ($w = 0; $w < pg_num_rows($res_cliente_admin); $w++) {

							$cliente_admin	= pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
							$nome			= pg_fetch_result($res_cliente_admin, $w, 'nome');
							$cidade			= pg_fetch_result($res_cliente_admin, $w, 'cidade');

							$nome           = ucwords(strtolower(substr($nome,0,20)));
							$cidade         = ucwords(strtolower(substr($cidade,0,15)));

							echo "<option value='$cliente_admin'>$nome - $cidade</option>";

						}

					}
				echo "</select>";
				echo "</td>";

				if ($login_fabrica != 96) {
					echo "<td ><input type='checkbox' name='cliente_admin_master_novo' value='t'";
					if ($cliente_admin_master == 't') echo " checked";
					echo "&nbsp;</TD>\n";
				}

			}

			echo '<td>' . createHTMLInput('checkbox', "ativo_novo", true, 't', $ativo, $enabled, true) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "master_novo", true, 'master', $master_novo, ($master == 'master'), $enabled, "class='novo'") . '&nbsp;</td>';
			if ($login_privilegios == '*')
				echo '<td>' . createHTMLInput('checkbox', "sup_help_desk", true, 't', $sup_help_desk, null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "live_help", null, 't', $live_help, null, $enabled, "class='novo'") . '</td>';
			echo '<td>' . createHTMLInput('checkbox', "responsavel_ti_novo", null, 't', $responsavel_ti, null, $enabled, "class='novo'") . '&nbsp;</td>';

			// Privil�gios
			echo '<td>' . createHTMLInput('checkbox', "gerencia_novo"              , null, 'gerencia'    , $_POST['gerencia_novo'], null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "cadastros_novo"             , null, 'cadastros'   , $_POST['cadastros_novo'],		null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "call_center_novo"           , null, 'call_center' , $_POST['call_center_novo'],	null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "supervisor_call_center_novo", null, 't'           , $supervisor_call_center, null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "info_tecnica_novo"          , null, 'info_tecnica', $_POST['info_tecnica_novo'],	null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "financeiro_novo"            , null, 'financeiro'  , $_POST['financeiro_novo'],	null, $enabled, "class='novo'") . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "auditoria_novo"             , null, 'auditoria'   , $_POST['auditoria_novo'],		null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($login_fabrica == 91)  // HD 685194
				echo '<td>' . createHTMLInput('checkbox', "promotor_wanke_novo", null, 't', $_POST['promotor_wanke_novo'], null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($usa_altera_pais_produto)  // HD 374998
				echo '<td>' . createHTMLInput('checkbox', "altera_pais_produto_novo", null, 't', $altera_pais_produto, null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($usa_atende_hd_postos)
				echo '<td>' . createHTMLInput('checkbox', "sap_novo", "sap_novo", 't', $admin_sap, null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($usa_responsavel_postos)
				echo '<td>' . createHTMLInput('checkbox', "responsavel_postos_novo", null, 't', $_POST['responsavel_postos_novo'], null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($usa_atendente_callcenter)
				echo '<td>' . createHTMLInput('checkbox', "atendente_callcenter_novo", null, 't', $atendente_callcenter_novo, null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($usa_recebe_fale_conosco)
				echo '<td>' . createHTMLInput('checkbox', "fale_conosco_novo", null, 't', $fale_conosco, null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($usa_intervensor)
				echo '<td>' . createHTMLInput('checkbox', "intervensor_novo", null, 't', $intervensor_novo, null, $enabled, "class='novo'") . '&nbsp;</td>';

			if ($login_fabrica == 19)
				echo '<td>' . createHTMLInput('checkbox', "consulta_os_novo", null, 't', $consulta_os, null, $enabled, "class='novo'") .  '&nbsp;</td>';?>

		</tr>
		<tr class='subtitulo'>
			<td colspan='100%'>Usu�rios Ativos</td>
		</tr><?php

		if ($login_admin == 828) {
			$sql = "SELECT *
                      FROM tbl_admin
                     WHERE fabrica =  $login_fabrica
                       AND ativo   IS TRUE
                  ORDER BY ativo DESC, login ;";
		} else {
			$sql = "SELECT *
                      FROM tbl_admin
                     WHERE fabrica = $login_fabrica
                  ORDER BY ativo DESC, login ;";
		}

		$res = pg_query($con,$sql);
		$tot = pg_num_rows($res);

		//echo array2table(pg_fetch_all($res));
		//die();

		for ($i = 0; $i < $tot; $i++) {

			$admin					= trim(pg_fetch_result($res, $i, 'admin'));
			$login					= trim(pg_fetch_result($res, $i, 'login'));
			$senha					= trim(pg_fetch_result($res, $i, 'senha'));
			$nome_completo			= trim(pg_fetch_result($res, $i, 'nome_completo'));
			$email					= trim(pg_fetch_result($res, $i, 'email'));
			$cliente_admin			= trim(pg_fetch_result($res, $i, 'cliente_admin'));
			$cliente_admin_master	= trim(pg_fetch_result($res, $i, 'cliente_admin_master'));
			$pais					= strtoupper(trim(pg_fetch_result($res, $i, 'pais')));
			$fone					= trim(pg_fetch_result($res, $i, 'fone'));
			$ativo					= trim(pg_fetch_result($res, $i, 'ativo'));
			$fale_conosco			= trim(pg_fetch_result($res, $i, 'fale_conosco'));
			$intervensor			= trim(pg_fetch_result($res, $i, 'intervensor'));
			$atendente_callcenter	= trim(pg_fetch_result($res, $i, 'atendente_callcenter'));//HD 335548
			$supervisor_call_center = trim(pg_fetch_result($res, $i, 'callcenter_supervisor'));
			$privilegios			= trim(pg_fetch_result($res, $i, 'privilegios'));
			$consulta_os			= trim(pg_fetch_result($res, $i, 'consulta_os'));
			$admin_sap				= trim(pg_fetch_result($res, $i, 'admin_sap'));
			$live_help				= trim(pg_fetch_result($res, $i, 'live_help'));
			$responsavel_postos     = trim(pg_fetch_result($res, $i, 'responsavel_postos'));//HD 233213
			$altera_pais_produto    = trim(pg_fetch_result($res, $i, 'altera_pais_produto')); // HD 374998
			$sup_help_desk          = trim(pg_fetch_result($res, $i, 'help_desk_supervisor'));
			$responsavel_ti         = trim(pg_fetch_result($res, $i, 'responsavel_ti'));

			if ($ativo == 'f' && strlen($titulo) == 0) {
				$titulo = "Usu�rios Inativos";
				echo "<tr class='subtitulo'><td colspan='100%'>$titulo</td></tr>";
			}

			$foto_admin = '';
			if (file_exists($foto = "admin_fotos/tbl_admin.$admin.jpg")) {
				$foto_admin = "<a href = '$foto'><img class = 'fa' src = '$foto' /></a>";
				$tem_fotos  = true;
			}

			$campo_ativo= ($ativo != 'f');
			$cor		= (!$campo_ativo) ? '#F7F5F0' : '#F1F4FA';
			$ro			= ($campo_ativo) ? '':'readonly';?>
		<tr bgcolor='<?=$cor?>'>
			<td nowrap>
				<input type='hidden' name='admin_<?=$i?>' value='<?=$admin?>'>
				<input type='text' name='login_<?=$i?>' size='20' maxlength='20' value='<?=$login?>'<?=$ro?>>
			</td>
			<td nowrap>
				<input type='password' name='senha_<?=$i?>' size='15' maxlength='20' value='<?=sha1($senha)?>'>
			</td>
			<td nowrap>
				<input type='text' name='nome_completo_<?=$i?>' size='35' maxlength='' value='<?=$nome_completo?>' <?=$ro?>><?=$foto_admin?></td>
			<td nowrap>
				<input type='text' name='fone_<?=$i?>' size='20' maxlength='' value='<?=$fone?>' <?=$ro?>>
			</td>
			<td nowrap>
				<input type='text' name='email_<?=$i?>' size='40' maxlength='' value='<?=$email?>' <?=$ro?>>
			</td><?php
			if ($login_fabrica == 20) {?>
				<td nowrap>
					<input type='text' name='pais_<?=$i?>' size='2' maxlength='2' value='<?=$pais?>'<?=$ro?>>
				</td><?php
			}

			if ($abre_os_admin_arr) {

				if( $ativo == 't' ) {
					echo "<td nowrap>";
						echo "<select name='cliente_admin_$i'>";
							echo "<option></option>";

						$sql_cliente_admin = "SELECT	cliente_admin,
												nome, cidade
												FROM tbl_cliente_admin
												WHERE fabrica = $login_fabrica
												AND abre_os_admin is true
												ORDER BY nome";

						$res_cliente_admin = pg_query($con,$sql_cliente_admin);
						$total = pg_num_rows($res_cliente_admin);

						if ($total > 0) {

							for ($w = 0; $w < $total; $w++) {

								$xcliente_admin = pg_fetch_result($res_cliente_admin, $w, 'cliente_admin');
								$nome           = pg_fetch_result($res_cliente_admin, $w, 'nome');
								$cidade         = pg_fetch_result($res_cliente_admin, $w, 'cidade');
								$nome           = ucwords(strtolower(substr($nome,0,20)));
								$cidade         = ucwords(strtolower(substr($cidade,0,15)));

								echo "<option value='$xcliente_admin'".($xcliente_admin == $cliente_admin ? "SELECTED" : '').">$nome - $cidade</option>";
							}

						}

					echo "</select></td>";
				} else {
					echo "<td nowrap>";
					$sql_cli_admin_inativo = "SELECT nome, tbl_cliente_admin.cliente_admin
                                                FROM tbl_cliente_admin
                                                JOIN tbl_admin USING (cliente_admin)
                                               WHERE tbl_admin.admin   = $admin
                                                 AND tbl_admin.fabrica = $login_fabrica";

					$res_inativo = pg_query($con,$sql_cli_admin_inativo);

					if (pg_num_rows($res_inativo)) {
						echo pg_fetch_result($res_inativo,0,0);
						echo '<input type="hidden" name="cliente_admin_'.$i.'" value="'.pg_fetch_result($res_inativo,0,1).'" />';
					}
					else echo '&nbsp;';

					echo "</td>";

				}

			if ($login_fabrica != 96) {
				echo '<td>' . createHTMLInput('checkbox', "cliente_admin_master_$i", null, 't', $cliente_admin_master, null, $campo_ativo) . '&nbsp;</td>';
			}

		}

		echo '<td>' . createHTMLInput('checkbox', "ativo_$i", null, 't', $ativo, null, true) . '&nbsp;</td>';

		if ($login_privilegios == '*')
			echo '<td>' . createHTMLInput('checkbox', "master_$i", true, 'master', $master, strpos(" $privilegios",'*')>0, $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "sup_help_desk_$i", true, 't', $sup_help_desk, null, $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "live_help_$i", true, 't', $live_help, null, $campo_ativo) . '</td>';
			echo '<td>' . createHTMLInput('checkbox', "responsavel_ti_$i", null, 't', $responsavel_ti, null, $campo_ativo) . '&nbsp;</td>';

			// Privil�gios
			echo '<td>' . createHTMLInput('checkbox', "gerencia_$i"              , null, 'gerencia'    , null, strpos(" $privilegios",'gerencia')>0     , $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "cadastros_$i"             , null, 'cadastros'   , null, strpos(" $privilegios",'cadastro')>0     , $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "call_center_$i"           , null, 'call_center' , null, strpos(" $privilegios",'call_center')>0  , $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "supervisor_call_center_$i", null, 't'           , $supervisor_call_center, null                  , $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "info_tecnica_$i"          , null, 'info_tecnica', null, strpos(" $privilegios",'info_tecnica' )>0, $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "financeiro_$i"            , null, 'financeiro'  , null, strpos(" $privilegios",'financeiro')>0   , $campo_ativo) . '&nbsp;</td>';
			echo '<td>' . createHTMLInput('checkbox', "auditoria_$i"             , null, 'auditoria'   , null, strpos(" $privilegios",'auditoria')>0    , $campo_ativo) . '&nbsp;</td>';

			if ($login_fabrica == 91)  // HD 685194
				echo '<td>' . createHTMLInput('checkbox', "promotor_wanke_$i", null, 'promotor', $promotor, null, $campo_ativo) . '&nbsp;</td>';

			if ($usa_altera_pais_produto)  // HD 374998
				echo '<td>' . createHTMLInput('checkbox', "altera_pais_produto_$i", null, 't', $altera_pais_produto, null, $campo_ativo) . '&nbsp;</td>';

			if ($usa_atende_hd_postos)
				echo '<td>' . createHTMLInput('checkbox', "sap_$i", true, 't', $admin_sap, null, $campo_ativo) . '&nbsp;</td>';

			if ($usa_responsavel_postos)
				echo '<td>' . createHTMLInput('checkbox', "responsavel_postos_$i", null, 't', $responsavel_postos, null, $campo_ativo) . '&nbsp;</td>';

			if ($usa_atendente_callcenter)
				echo '<td>' . createHTMLInput('checkbox', "atendente_callcenter_$i", null, 't', $atendente_callcenter, null, $campo_ativo) . '&nbsp;</td>';

			if ($usa_recebe_fale_conosco)
				echo '<td>' . createHTMLInput('checkbox', "fale_conosco_$i", null, 't', $fale_conosco, null, $campo_ativo) . '&nbsp;</td>';

			if ($usa_intervensor)
				echo '<td>' . createHTMLInput('checkbox', "intervensor_$i", null, 't', $intervensor, null, $campo_ativo) . '&nbsp;</td>';

			if ($login_fabrica == 19)
				echo '<td>' . createHTMLInput('checkbox', "consulta_os_$i", null, 't', $consulta_os, null, $campo_ativo) .  '&nbsp;</td>';

			echo "</tr>\n";
		}?>
		<input type='hidden' name='qtde_item' value="<?=$i?>" />
	</table>
	</div>
	<br />
	<center>
		<input type="hidden" name="btn_acao" value="" /><?php

		//HD 666788 - Funcionalidades por admin
		$sql = "SELECT fabrica FROM tbl_funcionalidade WHERE fabrica=$login_fabrica OR fabrica IS NULL";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {?>
			<input type="button" style="cursor:pointer;" value="Funcionalidades" onclick="window.open('funcionalidades_cadastro.php');" /><?php
		}?>
		<input type="button" style="cursor:pointer;" value="Gravar" onclick="javascript: formSubmit();" alt="Gravar Formul�rio" border='0' />
		<!-- if (document.frm_admin.btn_acao.value == '' ) { document.frm_admin.btn_acao.value='gravar'; document.frm_admin.submit() } else { alert ('Aguarde submiss�o') } //-->
	</center>
	<br />
	</form><?php
	if ($tem_fotos) {?>
		<script src="../js/FancyZoom.js" type="text/javascript"></script>
		<script src="../js/FancyZoomHTML.js" type="text/javascript"></script>
		<script type="text/javascript">
			setupZoom();
		</script><?php
	}

}

include "rodape.php"; ?>
