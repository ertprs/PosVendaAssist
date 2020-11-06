<?php

	include dirname(__FILE__) . '/../dbconfig.php';
	include dirname(__FILE__) . '/../includes/dbconnect-inc.php';

	$ambiente = "producao"; /* teste | produção */
	$arquivos = "/www/assist/www/email_upload/";

	$sql = "SELECT 
				tbl_email_fabrica.email_fabrica,
                tbl_email_fabrica.fabrica      ,
                tbl_email_fabrica.linha        ,
                tbl_email_fabrica.assunto      ,
                tbl_email_fabrica.mensagem     ,
                tbl_email_fabrica.de           ,
                tbl_email_fabrica.nome_anexo   ,
                tbl_email_fabrica.pais         ,
                case when tbl_fabrica.razao_social notnull then tbl_fabrica.razao_social else tbl_fabrica.nome end AS fabrica_nome
                FROM tbl_email_fabrica
                JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_email_fabrica.fabrica
                WHERE tbl_email_fabrica.enviado IS NULL 
                AND (current_date - tbl_email_fabrica.data_mensagem::date)::int4 <= 15
                ORDER BY tbl_email_fabrica.email_fabrica";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

    	include "smtp/class.phpmailer.php";

    	for ($i = 0; $i < pg_num_rows($res); $i++) { 
    		
			$email_fabrica = pg_fetch_result($res, $i, "email_fabrica");
			$fabrica       = pg_fetch_result($res, $i, "fabrica");
			$linha         = pg_fetch_result($res, $i, "linha");
			$assunto       = pg_fetch_result($res, $i, "assunto");
			$mensagem      = pg_fetch_result($res, $i, "mensagem");
			$de            = pg_fetch_result($res, $i, "de");
			$nome_anexo    = pg_fetch_result($res, $i, "nome_anexo");
			$pais          = pg_fetch_result($res, $i, "pais");
			$fabrica_nome  = pg_fetch_result($res, $i, "fabrica_nome");

			/* Conf Email */

			$mail = new PHPMailer();
			/*
			$mail->IsSMTP();
			
			$mail->SMTPDebug = 2;
			$mail->SMTPAuth  = true;
			$mail->Host      = "smtp.gmail.com";
			$mail->Port      = 587; 
			$mail->Username  = "noreply@telecontrol.com.br";
			$mail->Password  = "tele6588";
			 */
			$mail->From     = $de;
			$mail->FromName = $fabrica_nome;

			if(strlen($linha) > 0){

				$sql_posto_email = "SELECT 
										tbl_posto.nome,
			                            tbl_posto_fabrica.contato_email AS email
			                        FROM tbl_posto
			                        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
			                        JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
			                        WHERE tbl_posto_fabrica.contato_email IS NOT NULL
			                        	AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			                            AND tbL_posto.pais = '$pais'
			                            ORDER BY tbl_posto.nome";

			}else{

				$sql_posto_email = "SELECT 
										tbl_posto.nome,
			                            tbl_posto_fabrica.contato_email AS email
			                        FROM tbl_posto
			                        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
			                        WHERE tbl_posto_fabrica.contato_email IS NOT NULL
			                        	AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			                            AND tbL_posto.pais = '$pais'
			                            ORDER BY tbl_posto.nome";

			}

			$res_posto_email = pg_query($con, $sql_posto_email);

			for($j = 0; $j < pg_num_rows($res_posto_email); $j++){

				$nome_posto = pg_fetch_result($res_posto_email, $j, "nome");
				$email_posto = ($ambiente == "teste") ? "ronald.santos@telecontrol.com.br" : pg_fetch_result($res_posto_email, $j, "email");

				$mail->Subject = $assunto;
				$mail->IsHTML(true);
				$mail->body = $mensagem;

				$mail->AddAddress($email_posto, $nome_posto);

				if($ambiente != "teste"){

					if(strlen($nome_anexo) > 0){

						if(file_exists($arquivos . $nome_anexo)){

							$mail->AddAttachment($arquivos . $nome_anexo);

						}

					}

				}

				if(!$mail->Send()) {
				   echo "Erro ao Enviar o Email : " . $mail->ErrorInfo;
				} else {
					$sql = "UPDATE tbl_email_fabrica SET enviado = current_timestamp WHERE email_fabrica = $email_fabrica";
					$resS = pg_query($con,$sql);
				   echo "Email enviado com Sucesso!";
				}


			}

    	}

    }

?>
