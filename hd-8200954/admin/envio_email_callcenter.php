<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

require('../class/email/mailer/class.phpmailer.php');

$callcenter = trim($_GET['callcenter']);
if(strlen($callcenter) > 0) {
	$email = $email_remetente = '';
	$sql = " SELECT email
			FROM tbl_hd_chamado_extra
			WHERE hd_chamado = $callcenter";
	$res = @pg_exec($con,$sql);
	if( is_resource($res) && pg_numrows($res) > 0){
		$email = pg_result($res,0,'email');
	}
	$sql = "SELECT email FROM tbl_admin where admin = {$login_admin}";
	$res = @pg_exec($con,$sql);
	if ( is_resource($res) && pg_numrows($res) ) {
		$email_remetente = pg_result($res,0,'email');
	}
}

if ($btn_acao == "confirmar") {

	$callcenter = $_POST["callcenter"];

	if (strlen($_POST["email_remetente"]) > 0) {
		$aux_email_remetente = trim($_POST["email_remetente"]) ;
	}else{
		$msg_erro = "Informe o email do remetente.";
	}
	if (strlen($_POST["email_destinatario"]) > 0) {
		$aux_email_destinatario = trim($_POST["email_destinatario"]) ;
	}else{
		$msg_erro = "Informe o email do destinatário.";
	}
	
	if (strlen($_POST["assunto"]) > 0) {
		$aux_assunto =  trim($_POST["assunto"]) ;
	}else{
		$msg_erro = "Informe o assunto.";
	}

	if (strlen($_POST["mens_corpo"]) > 0) {
		$aux_mens_corpo =  trim($_POST["mens_corpo"]) ;
	}else{
		$msg_erro = "Informe a mensagem.";
	}

	if(strlen($msg_erro) == 0) {
		if( in_array($login_fabrica,array(24,35,81,86))){

			switch ($login_fabrica) {
				case 24:
					$username = 'tc.sac.suggar@gmail.com';
					$senha = 'tcsuggar';
					break;
				case 35:
					$username = 'tc.sac.cadence@gmail.com';
					$senha = 'tccadence';
					break;
				case 81:
					$username = 'tc.sac.bestway@gmail.com';
					$senha = 'tcbestway';
					break;	
				case 86:
					$username = 'tc.sac.famastil@gmail.com';
					$senha = 'tcfamastil';
					break;										
			}

			if($login_fabrica == 24){
				$interacao = "E-mail enviado <b>de:</b> $aux_email_remetente <b>para:</b> $aux_email_destinatario com o <b>assunto:</b> $aux_assunto e <b>mensagem:</b> $aux_mens_corpo ";

				$sql_item = "INSERT INTO tbl_hd_chamado_item (hd_chamado, admin, status_item, interno, comentario) VALUES ($callcenter, $login_admin, 'Aberto', true, '$interacao')";
				$res_item = pg_query($con, $sql_item);				
			}

		    $mailer = new PhpMailer(true);

		    $mailer->IsSMTP();
		    $mailer->Mailer = "smtp";
		    
		    $mailer->Host = 'ssl://smtp.gmail.com';
		    $mailer->Port = '465';
		    $mailer->SMTPAuth = true;
                   
		    $mailer->Username = $username;
		    $mailer->Password = $senha;
		    $mailer->SetFrom($aux_email_remetente, $aux_email_remetente); 
		    $mailer->AddAddress($aux_email_destinatario,$aux_email_destinatario ); 
		    $mailer->Subject = utf8_encode($aux_assunto);
		    $mailer->Body = utf8_encode($aux_mens_corpo);

		    try{
				$mailer->Send();			
				$msg_ok = "Mensagem enviada corretamente!";
				$msg_erro = $msg_ok;			
		    }catch(Exception $e){
				$msg_erro = "Mensagem não enviada";
		    }
		    
		}else{
		  if(mail($aux_email_destinatario, stripslashes(utf8_encode($aux_assunto)), utf8_encode($aux_mens_corpo), "From: ".$aux_email_remetente." \n $body_top " )){
				  $msg_ok = "Mensagem enviada corretamente!";
				  $msg_erro = $msg_ok;
		  }else{
			  $msg_erro = "Mensagem não enviada";
		  }
		}
		
	}
}
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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
	background-color: #ffffff
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
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}
</style>
<? 
if(strlen($msg_erro) > 0){
?>

<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?> 

<form enctype = "multipart/form-data" name = "frm_email" method = "post" action = "<? echo $php_self; ?>">
<table width='600px' align='center' border='0' cellspacing = '1' cellpadding='0' bgcolor='#d9e2ef'>
	<tr>
		<td>
			<table width='600px' align='center' border='0' cellspacing = '2' cellpadding='3' bgcolor='#FFFFFF'>
				<tr class="menu_top" align = center>
					<td colspan="2" class="menu_top">EMAIL REMETENTE</td>
				</tr>
				<tr class="table_line">
					<td colspan="2"  align='center'>
						<input type="text" name="email_remetente" size="50" value="<? echo $email_remetente ?>" readonly="true" class="frm">
					</td>
				</tr>
				<tr class="menu_top" align ='center'>
					<td colspan="2" class="menu_top">EMAIL DESTINATÁRIO</td>
				</tr>
				<tr class="table_line">
					<td colspan="2"  align='center'>
						<input type="text" name="email_destinatario" size="50" value="<? echo $email?>" class="frm">
					</td>
				</tr>
				<tr class="menu_top">
					<td colspan="2" class="menu_top">
						ASSUNTO
					</td>
				</tr>
				<tr class="table_line">
					<td colspan="2"  align='center'>
						<input type="text" name="assunto" size="70" value="<? echo $assunto ?>" class="frm">
					</td>
				</tr>
				<tr>
					<td colspan="2" class="menu_top">
						MENSAGEM
					</td>
				</tr>
				<tr class="table_line">
					<td colspan='2' align = 'center'>
						<textarea name="mens_corpo" rows="8" cols="60" value = "<? echo $mens_corpo ?>" class="frm" ></textarea>
					</td>
				</tr>
				<tr class="table_line">
					<td colspan="2">
						<input type='hidden' name='btn_acao' value=''>
						<input type="hidden" name="callcenter" value="<?=$callcenter?>">
						<img src="imagens_admin/btn_confirmar.gif" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() } else { alert ('aguarde submissão') }" alt="confirmar formulário" border='0' style="cursor:pointer;" onclick="javascript: document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() ;" >
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
