<?php

/*
HD 150972
Este arquivo é incluído em outros arquivos, no momento da abertura e fechamento da OS
Algumas variáveis são definidas fora deste arquivo, antes de ser feito o include:
	$os
	$novo_status_os
*/

// HD 337873
$vet_email_fabrica = array(59);

$os_str = strval($os);
$md5_os = md5($os_str[1] . $os_str[3] . $os_str[5]);

$sql = "
SELECT
tbl_fabrica.nome AS fabrica_nome                                       ,
tbl_posto.nome AS posto_nome                                           ,
tbl_posto.fone AS posto_fone                                           ,
tbl_posto.email AS posto_email                                         ,
tbl_os.consumidor_email                                                ,

tbl_os.consumidor_nome                                                 ,
tbl_os.sua_os                                                          ,
TO_CHAR (tbl_os.finalizada,'DD/MM/YYYY as HH24:MI') AS finalizada      ,
TO_CHAR (tbl_os.data_conserto,'DD/MM/YYYY as HH24:MI') AS data_conserto,
tbl_produto.produto  												   ,
tbl_produto.referencia												   ,
tbl_produto.descricao

FROM
tbl_os

JOIN tbl_fabrica ON tbl_os.fabrica=tbl_fabrica.fabrica
JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto

JOIN tbl_produto on tbl_os.produto = tbl_produto.produto

WHERE
tbl_os.os=$os
AND tbl_os.fabrica = $login_fabrica
AND current_date-tbl_os.data_digitacao::date <= 25
AND tbl_os.consumidor_email IS NOT NULL
";

$res = pg_exec($con, $sql);

if (pg_num_rows($res) > 0)
{
	//Bloco de código que gera variáveis com nomes dos campos contendo o conteúdo dos mesmos
	for($i = 0 ; $i < pg_num_fields($res); $i++)
	{
		$campo = pg_field_name($res, $i);
		$$campo = pg_result($res, 0, $campo);
	}

	if ($posto_email == "") $posto_email = "noreply@acompanhamentodeos";

	$titulo = "[" . strtoupper($fabrica_nome) . "] Acompanhamento de Ordem de Serviço";
	$to = $consumidor_email;

	if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
		$erro_email = "Consumidor sem email ";
	}

    if ($login_fabrica == '11') {
        $sqlFone = "SELECT contato_fone_comercial FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and posto = $login_posto ";
        $qryFone = pg_query($con, $sqlFone);

        if (pg_num_rows($qryFone) == 1) {
            $posto_fone = pg_fetch_result($qryFone, 0, 'contato_fone_comercial');
        }
    }

	$message = "
		<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
		\"http://www.w3.org/TR/html4/loose.dtd\">
		<html>
		<head>
		<title>$titulo</title>
		</head>
		<body style='font-family:Lucida Sans Unicode', 'Lucida Grande', sans-serif>
		<table style='border-collapse: collapse;'>
			<tr>
				<td style='font-size: 10pt;' align=left width=740>";
				if ($novo_status_os <> 'CONSERTADO') {
					if ($login_fabrica == 117) {
		                $data_abertura_aux = str_replace("'", "", $data_abertura);
		                if (strpos($data_abertura_aux, '-')) {
			                $data_abertura_aux = explode('-', $data_abertura_aux);
			                $data_abertura_aux = $data_abertura_aux[2]."/".$data_abertura_aux[1]."/".$data_abertura_aux[0];
		                }

						$message .= "Abertura de OS Elgin - $os<br /><br />Foi aberta a ordem de serviço pelo posto: $posto_nome<br />Data: $data_abertura_aux Produto " . str_replace("'", "", $produto_referencia) . " - $produto_descricao<br />Segue abaixo o link para consultar o andamento de sua O.S<br />https://posvenda.telecontrol.com.br/assist/externos/institucional/statusos.html<br /><br />***Não responder este e-mail, pois ele é gerado automaticamente pelo sistema.<br/><br/>Atenciosamente,<br />Serviço Elgin de atendimento";						
					}else{
						$message .= "Caro(a) $consumidor_nome <br><br>
						Você está recebendo esta mensagem porque tem uma ordem de serviço no posto " . strtoupper($posto_nome) . "<br><br>
						O status da OS foi alterado para: $novo_status_os";
						$message .="<br><br>
						Posto Autorizado: $posto_nome<br><br>
						Telefone: $posto_fone";
					}
					

				}else {
					
					if ($login_fabrica == 59) {	 #HD 337873
						$message .= "Consumidor: $consumidor_nome <br><br>

						A Assistência Técnica da Fábrica Sight GPS finalizou o seu atendimento, OS - $sua_os em $data_conserto. <br><br>
						
						O dia e horário acima indicam apenas quando os dados foram recebidos pelo sistema.<br><br>
						
						Seu equipamento está sendo encaminhado ao setor de expedição.<br><br> 

						Produto: [$referencia] " . strtoupper($descricao) ." <br><br>

						Autorizada responsável pelo reparo: " . strtoupper($posto_nome) . "<br><br><br>

						Atenciosamente,<br>
						Departamento Técnico.<br><br><br>
						Mensagem automática: Por favor, não responder este e-mail.";

					}else{
					$message .="<br> Caro $consumidor_nome, <br><br>
					Seu produto encaminhado para conserto  no $posto_nome está disponível para entrega.
					<br><br>
					Informações adicionais podem ser obtidas no Telefone  $posto_fone <br>";
					if ($login_fabrica == 117) {
						$message .="ou no link: https://posvenda.telecontrol.com.br/assist/externos/institucional/statusos.html";
					}
					$message .= "<br><br>
					Atenciosamente, <br><br>

					$posto_nome";
					}
				}
			$message .="</td>
			</tr>
		</table>
		</body>
		</html>";


	$headers  = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";

	$headers .= "To: $to" . "\r\n";
	$headers .= "From: $posto_nome <naoresponder@telecontrol.com.br>";

	if ($to !== false and empty($erro_email))
		mail($to, utf8_encode($titulo), utf8_encode($message), $headers);
}


/*
retirado por solicitacao da ramonna intelbras chamado 165713 waldir
<br>
Para acompanhar o andamento da ordem, acesse o link abaixo:<br>
<br>
<b>http://www.telecontrol.com.br/assist/os_press_consumidor.php?os=$os&chave=$md5_os</b><br>
<br>
Caso prefira, acesse o endereço <b>http://www.telecontrol.com.br/assist/os_press_consumidor.php</b> e digite os dados:<br>
<br>
<b>Número da Ordem de Serviço (OS):</b> $os<br>
<b>Chave de acesso:</b> $md5_os<br>


*/

?>
