<?
echo $PHP_SELF ;

		$remetente_email = "tulio@telecontrol.com.br";
		$posto = "93509 - TELECONTROL";
		$descricao = "Comunicado TESTE";
		#----------- Enviar email de Confirma��o de Leitura -----------#
		$assunto      = "Leitura de Comunicado";
		$corpo        = "O posto $nome_posto leu o comunicado $descricao ";
		$email_origem = "suporte@telecontrol.com.br";
		if ( mail($remetente_email, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ) {
		}else{
			echo "N�o foi poss�vel enviar o email. Por favor entre em contato com a TELECONTROL.";
		}

?>