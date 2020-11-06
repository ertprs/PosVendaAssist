<?php
	include "../rotinas/funcoes.php";
	$fabrica 	= $_GET['fabrica'];
	$email 		= $_GET['email'];
	$tipo 		= $_GET['tipo'];
	$email = "ronaldo@telecontrol.com.br";
	$nome_fabrica = ($fabrica == 10) ? "bestway" : "cobimex";

	include_once '../class/email/mailer/class.phpmailer.php';
	/**
	 * instancia a classe PHPMailer no objeto $mailer
	 */
	$mailer = new PHPMailer();
		
	$file = "/home/ronald/public_html/assist/credenciamento/contrato/contrato_$nome_fabrica.{$tipo}";
	
	$mailer->IsSMTP();
	$mailer->IsHTML(true);
	$mailer->AddAddress($email);
	
	$mensagem ='<html>
<body>
	<table border="0" width="700px" height="990px" background="http://urano.telecontrol.com.br/~ronald/assist/admin/imagens_admin/contrato.jpg">
		<tr>
			<td style="padding: 150px 20px 0px 50px;text-align:justify" valign="top">
				Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
				tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
				quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
				consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
				cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
				proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
			</td>
		</tr>
	</table>
</body>
</html>';
	$assunto = "Contrato de Credenciamento";
	$vet['dest'] = $email;

	$mailer->Subject = "Contrato de Credenciamento";
	$mailer->Body    = $mensagem;
	$mailer->CharSet = 'UTF-8';
	$mailer->AddAttachment($file, 'contrato.pdf');
	
	if (!$mailer->Send()){

		$msg = "NO|Falha ao enviar email";

	} else{

		$msg = "OK|Email enviado com sucesso";

	}

	/*$vet['head']  = "MIME-Version: 1.0 \r";
		$vet['head'] .= "Content-type: text/html; charset=iso-8859-1 \r";
		$vet['head'] .= "To: ".$email." \r";
		$vet['head'] .= "From: helpdesk@telecontrol.com.br";
	if (!mail($email, $assunto, $mensagem, $vet['head'])){

		$msg = "NO|Falha ao enviar email";

	} else{

		$msg = "OK|Email enviado com sucesso";

	}*/
	echo $msg;


