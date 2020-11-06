<?php

function envio_email($tipo_email, $id_solicitante, $nome, $fabrica_nome, $cook_login, $posto_email, $esqueci_senha, $mailer, $con){
    $insert_alteracao_senha = null;
    $token = token($email_destino, $fabrica);
    $data = new DateTime();
    $data_solicitacao = $data->format('Y-m-d H:i:s.u');

    if($tipo_email == 'normal'){
        $insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (posto_fabrica, token, data_solicitacao) VALUES ($id_solicitante, '$token', '$data_solicitacao')";
    }else if($tipo_email == 'login_unico'){
        $insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (login_unico, token, data_solicitacao) VALUES ($id_solicitante, '$token', '$data_solicitacao')";
    }
    pg_query($con, $insert_alteracao_senha);

    $email_origem  = "suporte@telecontrol.com.br";
    $email_destino = $posto_email;
    $assunto       = "Telecontrol - " . $esqueci_senha;
    $corpo         = email_senha($tipo_email, $nome, $fabrica_nome, $token, $cook_login);

    $res = $mailer->sendMail(
        $email_destino,
        $assunto,
        utf8_decode($corpo),
        'noreply@tc.id'
    );

    return $res;
}

//  função que cria o e-mail para enviar, com texto diferenciado dependendo do idioma
function email_senha($tipo,$nome, $f_nome, $token, $idioma = 'pt-br') {

	if ($demo = strpos($tipo, 'demo')) {
		$tipo = str_replace('_demo', '', $tipo);
	}

	//if ($demo) echo("Mostrar DEMO <u>$tipo</u> en $idioma");

	if ($tipo == 'normal'){

		switch ($idioma) {
	      case "es":
				$body = "<p>
							<strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
							<br/>
							Apreciado/a $nome,
							<br/>
							Se nos ha solicitado el envío de los datos de acceso (usuario y clave) para acceder a la fábrica $f_nome:
							<br/>
							Para accceder al sistema, puede usar este enlace:
						</p>";
	    	break;
         case "en":
				$body = "<p>
							<strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
							<br/>
							Dear $nome,
							<br/>
							The following login and password has been requested to access the system for {$f_nome}:
							<br/>
							To access the system, use the link below:
						</p>";
	    	break;
	        case "de":
				$body = "<p>
								<strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
								<br/>
								Sehr geehrte $nome,
								<br/>
								Wir bestätgen den Eingang der Beantragung von Login und Kennwort zwecks Zugang zum System der $f_nome GmbH:
								<br/></br>
								Zum Zugang bitte untenstehenden Link anklicken:
							</p>";
			break;
			default:
				$body = "<p>
								Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
								<br/>
								Caro {$nome},
								<br/>
								Foi solicitado a recuperação de senha para acessar o sistema na fábrica ${f_nome}:
								<br/>
								Para recuperar a senha use o link abaixo:
							</p>";
		}

		$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$url_primaria = str_replace('esqueci_senha_new.php', 'alterar_senha.php', $url);

		$body.= $url_primaria."?token=". $token.
			"<br><br>\n".
			"Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
			"</p>";


	}elseif($tipo == 'login_unico'){

		switch ($idioma) {
        case "es":
				$body = "
					<p>
							<strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
							<br/>
							Apreciado/a $nome,
							<br/>
							Se ha solicitado la recuperación de la contraseña de su Login Único:
							<br/>
							Para recuperar su contraseña, abra este enlace:
					</p>";
	    	break;
        case "en":
				$body = "
						<p>
							<strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
							<br/>
							Dear/a $nome,
							<br/>
							You asked for password recovery for your Unique Login:
							<br/>
							To recover your password use the link below:
						</p>";
	    	break;
        case "de":
				$body ="
						<p>
							<strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
							<br/>
							Sehr geehrte $nome,
							<br/>
							Wir bestätgen den Eingang der Beantragung von Login und Kennwort zwecks Zugang zum System der $f_nome GmbH:
							<br/>
							Zum Zugang bitte untenstehenden Link anklicken:
						</p>";
	    	break;
			default:
				$body = "
						<p>
							Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
							<br/>
							Caro $nome,
							<br/>
							Foi solicitada a recuperação de senha para o seu Login Único:
							<br/>
							Para recuperar sua senha use o link abaixo:
						</p>";
		}


		$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$url_primaria = str_replace('esqueci_senha_new.php', 'alterar_senha.php', $url);

		$body.= $url_primaria."?token=". $token.
		 		"<br><br>\n".
		 		"Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
		 		"</p>";


	}

	//Tira os links reais quando solicitado e-mail de demonstração
	if ($demo) {
		$body = preg_replace('/href=[\'"]["\'].+\s/', "href='javascript:void(0);' ", $body);
	}

	return $body;

}

function token($email, $fabrica){
    $token = hash('sha256', $email . ':' . $fabrica . ':' . microtime() . mt_rand());
    return $token;
}

?>