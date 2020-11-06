<?php

try {

	include dirname(__FILE__)."/../../dbconfig.php";
	include dirname(__FILE__)."/../../includes/dbconnect-inc.php";
	include dirname(__FILE__)."/../funcoes.php";

	$login_fabrica = 147;

	system("mkdir /tmp/hitachi/ 2> /dev/null ; chmod 777 /tmp/hitachi/");
	system("mkdir /tmp/hitachi/posto/ 2> /dev/null ; chmod 777 /tmp/hitachi/posto/");

	if ($_serverEnvironment == "production") {
		$emails = array(
			"helpdesk@telecontrol.com.br",
			"amaral@hitachi-koki.com.br"
		);

		$arquivo_dir = "/home/hitachi/pos-vendas/hitachi-telecontrol/postolinha/";
	} else {
		$emails = array(
			"william.lopes@telecontrol.com.br"
		);

		$arquivo_dir = "/home/hitachi/pos-vendas/hitachi-telecontrol/postolinha/";
	 }

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	foreach (glob("{$arquivo_dir}Posto2*") as $arquivo) {
		$erros = array();
		$arquivo_backup = "/tmp/hitachi/posto/".date("YdmHiS")."-importa-posto.txt";
	        $arquivo_erro   = "/tmp/hitachi/posto/erro-".date("YdmHiS")."-importa-posto.txt";

		if(filesize($arquivo) == 0){
			unlink($arquivo);
			continue;
		}

		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		foreach ($conteudo as $linha_numero => $linha) {
			if (empty($linha)) {
				continue;
			}

			$linha_erros = array();
			
			if (!empty($linha)) {

				list (
						$codigo_posto,
						$razao,
						$nome_fantasia,
						$cnpj,
						$ie,
						$endereco,
						$numero,
						$complemento,
						$bairro,
						$cep,
						$cidade,
						$estado,
						$email,
						$telefone,
						$fax,
						$contato,
						$tipo_posto,
						$status,
						$financeira,
						$filial_num,
						$credito,
						$desconto
					) = explode ("\t",$linha);

				$fax_array          = explode("-",$fax);
				$fax_array_1        = str_replace('000','',$fax_array[0]);
				if($fax_array[1][0] == '0') {
					$fax_array_2    = substr($fax_array[1],1);
				}else {
					$fax_array_2    = $fax_array[1];
				}

				if(strtolower($status) == "t") {
					$status = 'CREDENCIADO';
				} else {
					$status = 'DESCREDENCIADO';
				}

				$fax                = $fax_array_1."-".$fax_array_2;
				$codigo_posto       = $codigo_posto.$filial_num;
				$contato            = trim($contato);
				$codigo_posto       = trim($codigo_posto);
				$razao              = trim($razao);
				$nome_fantasia      = trim($nome_fantasia);
				$cnpj               = trim($cnpj);
				$ie                 = trim($ie);
				$endereco           = trim($endereco);
				$numero             = trim($numero);
				$complemento        = trim($complemento);
				$bairro             = trim($bairro);
				$cep                = trim($cep);
				$cidade             = addslashes(trim($cidade));
				$estado             = trim($estado);
				$email              = trim($email);
				$telefone           = trim($telefone);
				$fax                = trim($fax);
				$contato            = trim($contato);
				$financeira         = trim($financeira);
				$tipo_posto         = trim($tipo_posto);
				$desconto 			= trim($desconto);

				if (preg_match("/;/", $email)) {
					$emails = explode(";", $email);
					$email = $emails[0];
				}

				if (preg_match("/,/", $email)) {
                                        $emails = explode(",", $email);
                                        $email = $emails[0];
                                }

				$credito 	= str_replace(",", ".", str_replace(".", "", $credito));
				$desconto 	= str_replace(",", ".", str_replace(".", "", $desconto));

				if(strtoupper($financeira) == 'BLOQUEADO'){
					$financeira = 'f';
				}else{
					$financeira = 't';
				}

				if (empty($codigo_posto))
				{
					$linha_erros[] = "Campo código do posto o não informado";
				}
				if (empty($razao))
				{
					$linha_erros[] = "Campo Razão não informado";
				}
				if (empty($cnpj))
				{
					$linha_erros[] = "Campo cnpj não informado";
				}
				if (empty($endereco))
				{
					$linha_erros[] = "Campo endereco não informado";
				}
				if (empty($cep))
				{
					$linha_erros[] = "Campo cep não informado";
				}
				if (empty($cidade))
				{
					$linha_erros[] = "Campo cidade não informado";
				}
				if (empty($estado))
				{
					$linha_erros[] = "Campo estado não informado";
				}
				if (empty($tipo_posto))
				{
					$linha_erros[] = "Campo tipo_posto não informado";
				}
				if (empty($status))
				{
					$linha_erros[] = "Campo status não informado";
				}
				if (empty($financeira))
				{
					$linha_erros[] = "Campo financeira não informado";
				}
				if (empty($filial_num))
				{
					$linha_erros[] = "Campo filial não informado";
				}
				if (empty($credito))
				{
					$credito = 0;
					//$linha_erros[] = "Campo credito não informado";
				}

				$cnpj = preg_replace("/\D/","",$cnpj);
				$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");

				if (pg_last_error()) {
					$linha_erros[] = "CNPJ inválido:".pg_last_error();
				}

				$sql_posto = "SELECT tbl_posto.nome, tbl_posto.ie, tbl_posto.posto FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
				$query_posto = pg_query($con, $sql_posto);

				if (pg_num_rows($query_posto) == 0) {
					$sql = "INSERT INTO tbl_posto (
											nome,
											nome_fantasia,
											cnpj,
											ie,
											endereco,
											numero,
											complemento,
											bairro,
											cep,
											cidade,
											estado,
											email,
											fone,
											fax,
											contato
										) VALUES (
											(E'$razao'),
											(E'$nome_fantasia'),
											'$cnpj',
											'$ie',
											'$endereco',
											'$numero',
											'$complemento',
											'$bairro',
											'$cep',
											'$cidade',
											'$estado',
											'$email',
											'$telefone',
											'$fax',
											'$contato'
										)";
					$query = pg_query($con, $sql);

					if (pg_last_error()) {
						$linha_erros[] = "Ocorreu um erro de execução ao gravar o posto {$cnpj}";
					}

					$query_posto_id = pg_query($con, "SELECT currval ('seq_posto') AS seq_posto");
					$posto = pg_fetch_result($query_posto_id, 0, 'seq_posto');

				} else {
					$iePosto          = pg_fetch_result($query_posto, 0, ie);
					$razaoSocialPosto = pg_fetch_result($query_posto, 0, nome);
					if ($iePosto != $ie || $razaoSocialPosto != $razao) {
						$dadosDivergentes["Base"][]       = "<b>Razão Social:</b> ".$razaoSocialPosto." <br /><b>IE:</b> ".$iePosto;
						$dadosDivergentes["Fabrica"][]    = "<b>Razão Social:</b> ".$razao." <br /><b>IE:</b> ".$ie;
					}
					$posto = pg_fetch_result($query_posto, 0, 'posto');
				}

				$sql = "SELECT
						    tbl_posto_fabrica.posto
						FROM   tbl_posto_fabrica
						WHERE  tbl_posto_fabrica.posto   = $posto
						AND    tbl_posto_fabrica.fabrica = $login_fabrica";
				$query = pg_query($con, $sql);


				$sql = "SELECT tipo_posto
						FROM tbl_tipo_posto
						WHERE UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais('$tipo_posto'))
						AND fabrica = $login_fabrica ";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res)>0){
					$cod_tipo_posto = pg_fetch_result($res, 0, 'tipo_posto');
				}else{
					$linha_erros[] = "Ocorreu um erro no posto {$cnpj}  de Tipo do posto nao encontrado {$tipo_posto}";
				}

				if (count($linha_erros) > 0) {
					$erros[$linha_numero] = $linha_erros;
				} else {
					if (pg_num_rows($query) == 0) {
						$sql = "INSERT INTO tbl_posto_fabrica (
													posto,
													fabrica,
													senha,
													tipo_posto,
													login_provisorio,
													codigo_posto,
													credenciamento,
													contato_fone_comercial,
													contato_fax,
													contato_endereco ,
													contato_numero,
													contato_complemento,
													contato_bairro,
													contato_cep,
													contato_cidade,
													contato_estado,
													contato_email,
													nome_fantasia,
													contato_nome,
													pedido_faturado,
													credito, 
													desconto
												) VALUES (
													$posto,
													$login_fabrica,
													'*',
													$cod_tipo_posto,
													null,
													'$codigo_posto',
													'$status',
													'$telefone',
													'$fax',
													'$endereco',
													'$numero',
													'$complemento',
													(E'$bairro'),
													'$cep',
													(E'$cidade'),
													'$estado',
													'$email',
													(E'$nome_fantasia'),
													(E'$contato'),
													'$financeira',
													$credito,
													$desconto
												)";
					} else {
						$sql = "UPDATE tbl_posto_fabrica SET
											codigo_posto           = '$codigo_posto',
											contato_endereco       = '$endereco',
											contato_bairro         = (E'$bairro'),
											contato_cep            = '$cep',
											contato_cidade         = (E'$cidade'),
											contato_estado         = '$estado',
											contato_fone_comercial = '$telefone',
											contato_fax            = '$fax',
											credenciamento         = '$status',
											tipo_posto             = $cod_tipo_posto,
											nome_fantasia          = (E'$nome_fantasia'),
											contato_email          = '$email',
											pedido_faturado        = '$financeira',
											credito        		   = $credito,
											desconto			   = $desconto 
									WHERE tbl_posto_fabrica.posto = $posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica";
					}
					$query = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$erros[$linha_numero] = array("Ocorreu um erro de execução ao gravar o Posto {$cnpj}");
					}
				}
			}
		}

		if (count($dadosDivergentes["Base"]) > 0) {
			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
			$assunto =  utf8_decode("Hitachi  - Importação de postos - Razão Social e/ou IE Divergente");

			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From     = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';
			$mail->AddAddress('suporte@telecontrol.com.br');
			$mail->Subject  = $assunto;
			$conteudo = "<p>Segue a listagem de Postos:</p>\n";
			$conteudo .= "<table border='1' width='100%'>\n";
			$conteudo .= "<tr bgcolor='#d90000' style='color:#fff;'>\n";
			$conteudo .= "<td style='padding:10px;'>Base Telecontrol Razão Social / IE</td>\n";
			$conteudo .= "<td style='padding:10px;'>Enviado pela Fabrica Razão Social / IE</td>\n";
			$conteudo .= "</tr>\n";
			
			for ($i=0; $i < count($dadosDivergentes["Base"]); $i++) { 
				$cor = ($i % 2 == 0) ? "#eeeeee" : "#ffffff";
				$conteudo .= "<tr bgcolor='$cor'>\n";
				$conteudo .= "<td style='padding:5px;'>".$dadosDivergentes["Base"][$i]."</td>\n";
				$conteudo .= "<td style='padding:5px;'>".$dadosDivergentes["Fabrica"][$i]."</td>\n";
				$conteudo .= "</tr>\n";
			}

			$conteudo .= "</table>\n";

			$mail->Body = $conteudo;
			$mail->Send();
		}

		if (count($erros) > 0) {
                                        $f_erro = fopen($arquivo_erro, "w");

                                        foreach ($erros as $linha_numero => $linha_erros) {
                                                fwrite($f_erro, "<b style='color: #FF0000;' >Linha {$linha_numero}</b><br />");

                                                fwrite($f_erro, "<ul>");

                                                foreach ($linha_erros as $erro) {
                                                        fwrite($f_erro, "<li>{$erro}</li>");
                                                }

                                                fwrite($f_erro, "</ul>");

                                                fwrite($f_erro, "<br />");
                                        }

                                        fclose($f_erro);

                                        mail(implode(",", $emails), "Telecontrol - Erro na Importação de Posto da Hitachi", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
                                }
                                

		system("mv {$arquivo} {$arquivo_backup}");

	}
	$phpCron->termino();


} catch (Exception $e) {

	$f_erro = fopen($arquivo_erro, "w");
	fwrite($f_erro, "Ocorreu um erro ao executar o script de importar produtos, entrar em contato com o suporte da Telecontrol");
	fclose($f_erro);

	mail(implode(",", $emails), "Telecontrol - Erro na Importação de Posto da Hitachi", file_get_contents($arquivo_erro), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");

	$phpCron->termino();

}
