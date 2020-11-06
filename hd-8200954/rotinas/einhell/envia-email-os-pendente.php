<?php

error_reporting(E_ALL);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';
    include dirname(__FILE__) . '/../../class/email/PHPMailer/PHPMailerAutoload.php';
	$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

    $fabrica_nome   = "Einhell";
    $fabrica        = 160;
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');

    $ambiente = "prod";

    if ($ambiente == "devel") {
    	$url_base = "http://novodevel.telecontrol.com.br/~kaique/PosVenda";
    } else {
    	$url_base = "https://posvenda.telecontrol.com.br/assist";
    }

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro envio email - Einhell")); // Titulo
    $logClass->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
    //$logClass->adicionaEmail("luiz.munoz@einhell.com");
    
    /*
    * Resgata as OSs em Garantia
    */
    $osClass = new \Posvenda\Os($fabrica);

    $dias = 30;
    $osClass->setDiasEmAberto($dias);

    $postos = $osClass->verificaPostoOsPendenteFechamento();

    if (count($postos) == 0) {
    	exit;
    }

    foreach ($postos as $posto => $value) {

    	$osClass->_model->getPDO()->beginTransaction();

    	$posto           = $value["posto"];
    	$posto_nome      = $value["nome"];
    	$posto_email     = $value["contato_email"]; 
    	//$posto_email     = 'kaique.magalhaes@telecontrol.com.br';
    	$body    = "";

    	try {

	    	$osPendente = $osClass->getOsPendentePosto($posto);
			
	    	$qtde_os = count($osPendente);

	    	$body .= "<table style='border-collapse: collapse;' border=1 align=center>
	    				<tr>
	    					<th align='center' bgcolor='darkblue' style='color: white;'>Ordem de Serviço</th>
	    					<th align='center' bgcolor='darkblue' style='color: white;'>Dias em Aberto</th>
	    					<th align='center' bgcolor='darkblue' style='color: white;'>Referência Produto</th>
	    					<th align='center' bgcolor='darkblue' style='color: white;'>Nome Consumidor</th>
	    					<th align='center' bgcolor='darkblue' style='color: white;'>Contato Consumidor</th>
	    					<th align='center' colspan='2' bgcolor='darkblue' style='color: white;'>Ações</th>
	    				</tr>";

	    			$sqlCom = "INSERT INTO arquivo_acao3_comunicado (posto,fabrica,email,data_enviado) VALUES ($posto, $fabrica, '$posto_email', current_timestamp) RETURNING acao3_comunicado";
					$resCom = pg_query($con, $sqlCom);

					$id_comunicado = pg_fetch_result($resCom, 0, 'acao3_comunicado');
					
					if (empty($id_comunicado)) {
						throw new Exception("Erro ao inserir comunicado");
					}

					$acao3 = [];

			    	foreach ($osPendente as $os => $values) {
			    		$os                  = $values["os"];
			    		$dias_em_aberto      = $values["dias"];
			    		$nome_posto          = $values["nome"];
			    		$referencia_produto  = $values["referencia"];
			    		$nome_consumidor     = $values["consumidor_nome"];
			    		$contato_consumidor  = empty($values["consumidor_fone"]) ? $values["consumidor_celular"] : $values["consumidor_fone"];

			    		$body .= "<tr>
			    					<td align='center'><a href='$url_base/acao_email_os.php?os=$os&acao=manter&validaUsuarioLogadoEmail=true' target='_blank'>".$os."</a></td>
			    				    <td align='center'>".$dias_em_aberto."</td>
			    					<td align='left'> ".$referencia_produto."</td>
			    					<td align='left'> ".$nome_consumidor."</td>
			    					<td align='center'>".$contato_consumidor."</td>
			    					<td align='center'>   <a href='$url_base/acao_email_os.php?os=$os&acao=manter&validaUsuarioLogadoEmail=true' target='_blank'>Manter Aberta</a></td>
			    					<td align='center'>   <a href='$url_base/acao_email_os.php?os=$os&acao=fechar&validaUsuarioLogadoEmail=true' target='_blank'>Fechar</a></td>
			    				  </tr>";


	                    $sqlDados = "INSERT INTO 
								arquivo_acao3_dados (
									acao3_comunicado,
									os,
									link_abrir,
									link_fechar,
									data_resposta,
									justificativa
								) 
							VALUES (
									$id_comunicado,
									$os,
									'$url_base/acao_email_os.php?os=$os&acao=manter&validaUsuarioLogadoEmail=true',
									'$url_base/acao_email_os.php?os=$os&acao=fechar&validaUsuarioLogadoEmail=true',
									null,
									null
								)";

			    		$query  = $pdo->query($sqlDados);

			    	}

	    	$body .= "</table>";

	    	$assunto = "{$fabrica_nome} - OSs abertas a mais de {$dias} dias";

	        $mensagem = "Prezada Assistência {$posto_nome},<br /><br />

						Identificamos que as ordens de serviços da Einhell abaixo estão com o status 'Aguardando Conserto' a mais de 30 dias, caso a ferramenta já tenha sido consertada e devolvida para o consumidor clique em fechar, caso tenha interesse em manter a O.S aberta clique em 'Manter Aberta' e informe o motivo. <br><br>
	                    {$body}
	                    <br />
	                    Atenciosamente<br />
						Suporte Einhell<br>";

	        $mailTc = new TcComm('smtp@posvenda');

	        $res = $mailTc->sendMail(
	            $posto_email,
	            $assunto,
	            $mensagem,
	            'noreply@telecontrol.com.br'
	        );            

	        if ($res) {
	        	$osClass->_model->getPDO()->commit();
	        } else {
	        	throw new Exception("Erro ao enviar email");
	        	
	        }
        } catch (Exception $e){
            /*
            * Rollback
            */
            $osClass->_model->getPDO()->rollBack();

        }
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
