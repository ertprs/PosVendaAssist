<?php
$email = "<teste@teste.com>";
function limpa_email ($email) {
// 	var_dump($_GET, $email);
// 	str_replace(array("<",">","'",'"'), array_fill(int start_index, int num, mixed value), mixed subject, [int &count])
	return preg_replace('/[<>"\']/', '_', $email);
}

echo limpa_email($email);

/*
function mail_comunicado ($fabrica, $posto, $from, $to, $title, $body, $sender, $headers = '',
						  $forced = true, $ativo = true) {

	if ($headers==''):
	    $headers  = "Return-Path:$from \nFrom:".$remetente.
					"\nBcc:$r_email \nContent-type: text/html\n";

            if (strlen($xemail) > 0) {  // Se o Porto tiver e-mail cadastrado envia o e-mail
				if (!@mail($destinatario,$assunto,$mensagem,$headers)) { // Se o e-mail n�o foi enviado...
					$remetente    = "MERCURIO FINANCEIRO <helpdesk@telecontrol.com.br>";
					$destinatario = "helpdesk@telecontrol.com.br";
					$assunto      = "EMAIL N�O ENVIADO (SEU EXTRATO FOI LIBERADO) - F�brica $login_fabrica";
					$mensagem     = "* N�O ENVIADO PARA O POSTO $posto ($xemail) *";
					$headers      = "Return-Path: <helpdesk@telecontrol.com.br>\nFrom: ".$remetente."\nContent-type: text/html\n";
					@mail($destinatario,$assunto,$mensagem,$headers);  // ...manda um e-mail de aviso para o Suporte
				}
            }
//  MLG 2009-08-11 - Insere um comunicado de leitura obrigat�ria, tenha ou n�o enviado o e-mail
//          Primeiro confere se j� existe um comunicado para este extrato.
//          Como o comunicado � gravado por aqui com o conte�do da var. $assunto, confere por esse valor mesmo
            $sql = "SELECT comunicado FROM tbl_comunicado
                        WHERE posto     = $posto
                          AND fabrica   = $login_fabrica
                          AND descricao = '$assunto'";
            if (@pg_num_rows(@pg_query($con, $sql)) == 0) {
                $sql = "INSERT INTO tbl_comunicado
                                (mensagem, tipo, descricao, posto, fabrica, obrigatorio_site, ativo) ".
                        "VALUES ('$mensagem', 'Extrato Liberado', $assunto, $posto, $login_fabrica, true, true)";
                $res = pg_query($con,$sql);
                if (!$res) echo "<p style='font-size: 15px;color:white;background-color:red;'>Erro ao gravar o comunicado para o posto $posto!!";
                if ($res)  echo "<p>Comunicado inserido com sucesso.</p>";
            }
        }
}
*/
?>
