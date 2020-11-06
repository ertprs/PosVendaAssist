<?php

#
# Telecontrol Networking
# www.telecontrol.com.br
# Importacao de Clientes da atlas CODIGO 74
#
#
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$ambiente = "dev";

	$fabrica  = "74" ;
	$login_fabrica  = $fabrica ;
	$origem   = "/home/atlas/atlas-telecontrol";
	$arquivos = "/home/atlas/atlas-telecontrol";

	function limpa_string($dados){
		$retirar = array("-",".", "/", "*", "'");
		$dados = str_replace($retirar, "", $dados);
		return $dados;
	}

	function checa_cnpj($cnpj)
	{
		if ((!is_numeric($cnpj)) or (strlen($cnpj) <> 14))
		{
			return 2;
		}
		else
		{
			$i = 0;
			while ($i < 14)
			{
			$cnpj_d[$i] = substr($cnpj,$i,1);
			$i++;
			}
			$dv_ori = $cnpj[12] . $cnpj[13];
			$soma1 = 0;
			$soma1 = $soma1 + ($cnpj[0] * 5);
			$soma1 = $soma1 + ($cnpj[1] * 4);
			$soma1 = $soma1 + ($cnpj[2] * 3);
			$soma1 = $soma1 + ($cnpj[3] * 2);
			$soma1 = $soma1 + ($cnpj[4] * 9);
			$soma1 = $soma1 + ($cnpj[5] * 8);
			$soma1 = $soma1 + ($cnpj[6] * 7);
			$soma1 = $soma1 + ($cnpj[7] * 6);
			$soma1 = $soma1 + ($cnpj[8] * 5);
			$soma1 = $soma1 + ($cnpj[9] * 4);
			$soma1 = $soma1 + ($cnpj[10] * 3);
			$soma1 = $soma1 + ($cnpj[11] * 2);
			$rest1 = $soma1 % 11;
			if ($rest1 < 2)
			{
				$dv1 = 0;
			}
			else
			{
				$dv1 = 11 - $rest1;
			}
			$soma2 = $soma2 + ($cnpj[0] * 6);
			$soma2 = $soma2 + ($cnpj[1] * 5);
			$soma2 = $soma2 + ($cnpj[2] * 4);
			$soma2 = $soma2 + ($cnpj[3] * 3);
			$soma2 = $soma2 + ($cnpj[4] * 2);
			$soma2 = $soma2 + ($cnpj[5] * 9);
			$soma2 = $soma2 + ($cnpj[6] * 8);
			$soma2 = $soma2 + ($cnpj[7] * 7);
			$soma2 = $soma2 + ($cnpj[8] * 6);
			$soma2 = $soma2 + ($cnpj[9] * 5);
			$soma2 = $soma2 + ($cnpj[10] * 4);
			$soma2 = $soma2 + ($cnpj[11] * 3);
			$soma2 = $soma2 + ($dv1 * 2);
			$rest2 = $soma2 % 11;
			if ($rest2 < 2)
			{
				$dv2 = 0;
			}
			else
			{
				$dv2 = 11 - $rest2;
			}
			$dv_calc = $dv1 . $dv2;
			if ($dv_ori == $dv_calc)
			{
				return 0;
			}
			else
			{
				return 1;
			}
		}
	}

#--=== DATA PARA BACKUP ===================================================
	$data = date('Y-m-d-h-s');

	/* Inicio Processo */ 
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	$conteudo_arquivo = file("$origem/posto.txt");
	$num_linha = 1;
	foreach($conteudo_arquivo as $linha){
		$valores = explode("\t",$linha);
		$log_erro = "";

		$razao				= trim(limpa_string($valores[0]));
		$nome_fantasia		= trim(limpa_string($valores[1]));
		$codigo 			= trim(limpa_string($valores[2]));
		$endereco 			= trim(limpa_string($valores[3]));
		$numero 			= trim(limpa_string($valores[4]));
		$bairro 			= trim(limpa_string($valores[5]));
		$cep 				= trim(limpa_string($valores[6]));
		$cidade 			= trim(limpa_string($valores[7]));
		$estado 			= trim(limpa_string($valores[8]));
		$email 				= trim(limpa_string($valores[9]));
		$telefone 			= trim(limpa_string($valores[10]));
		$contato 			= trim(limpa_string($valores[11]));
		$tipo_posto 		= trim(limpa_string($valores[12]));

		$razao 				= substr($razao,0,60);
		$nome_fantasia 		= substr($nome_fantasia,0,60);
		$endereco 			= substr($endereco,0,50);
		$bairro 			= substr($bairro,0,20);
		$cep 				= substr($cep,0,8);
		$cidade 			= substr($cidade,0,30);
		$estado 			= substr($estado,0,2);
		$email 				= "lower(". substr($email,0,50) .")";
		$telefone 			= substr($telefone,0,30);
		$contato 			= substr($contato,0,30);
		$tipo_posto 		= substr($tipo_posto,0,30);

		$posto = "";

		if(checa_cnpj($codigo)){
			$log .= "Linha $num_linha - O CNPJ $codigo é inválido. \n\n";
			$log_erro = "ok";
		}
			
		if($log_erro == ""){
			### VERIFICA EXISTÊNCIA DO POSTO
			$sql = "SELECT tbl_posto.nome, tbl_posto.posto
					FROM   tbl_posto
					WHERE  tbl_posto.cnpj = '$codigo' ";
			$result =pg_query($con, $sql);	

			### INCLUI O POSTO QUE NÃO EXISTE
			if (pg_num_rows($result) == 0) {
				$sql = "INSERT INTO tbl_posto (
							nome       ,
							nome_fantasia,
							cnpj       ,
							endereco   ,
							numero     ,
							bairro     ,
							cep        ,
							cidade     ,
							estado     ,
							email      ,
							fone       ,
							fax        ,
							contato
						)VALUES(
							'$razao'       ,
							'$nome_fantasia',
							'$codigo'      ,
							'$endereco'    ,
							'$numero'      ,
							'$bairro'      ,
							'$cep'         ,
							'$cidade'      ,
							'$estado'      ,
							'$email'       ,
							'$telefone'    ,
							'$fax'         ,
							'$contato'
						)";
				$result = pg_query($con, $sql);
				
				if (pg_last_error($con) > 0) {
					$msg_erro_interno .= "Erro ao inserir posto";
					$msg_erro_interno .= pg_last_error($con);
				}else{
					$sql = "SELECT posto from tbl_posto where cnpj = '$codigo'";
					$result = pg_query($sql);
					$posto = pg_fetch_result($result, 0, 'posto');

					$log .= "Linha $num_linha - Posto $razao gravado \n\n ";
				}
			} else{
				$razaoSocialPosto = pg_fetch_result($result, 0, nome);
				if ($razaoSocialPosto != $razao) {
					$dadosDivergentes["Base"][]       = "<b>Razão Social:</b> ".$razaoSocialPosto;
					$dadosDivergentes["Fabrica"][]    = "<b>Razão Social:</b> ".$razao;
				}
				$posto = pg_fetch_result($result, 0, 'posto');
				$log .= "Linha $num_linha - O posto $codigo já está cadastrado. \n\n";
			}

			### VERIFICA NA TABELA POSTO-FABRICA EXISTÊNCIA DO POSTO PARA A FÁBRICA
			$sql = "SELECT tbl_posto_fabrica.posto
					FROM   tbl_posto_fabrica
					WHERE  tbl_posto_fabrica.posto   = $posto
					AND    tbl_posto_fabrica.fabrica = 74 ";
			$result = pg_query($con, $sql);

			### INSERE POSTO NA TABELA POSTO-FABRICA
			if (pg_num_rows($result) == 0) {
				$sql = "INSERT INTO tbl_posto_fabrica (
							posto       ,
							fabrica     ,
							senha       ,
							codigo_posto,
							nome_fantasia,
							tipo_posto  ,
							contato_fone_comercial ,
							contato_endereco    ,
							contato_numero      ,
							contato_cidade      ,
							contato_cep		    ,
							contato_estado      							
						) VALUES (
							$posto             ,
							74                 ,
							'*'                ,
							'$codigo'            ,
							'$nome_fantasia'     ,
							221                ,
							'$telefone'          ,
							'$endereco'          ,
							'$numero'            ,
							'$cidade'            ,
							'$cep'               ,
							'$estado'
						);";
				$result = pg_query($con, $sql);
				
				if (pg_num_rows($result) > 0) {
					$msg_erro_interno .= "Erro ao relacionar posto com a fabrica \n\n";
				}
				else{
					$log .= "Linha $num_linha - Posto $codigo gravado para a fabrica. \n\n";
				}

			}else {			
				$log .= "Linha $num_linha - Posto $codigo ja está cadastrado a fabrica. \n\n ";
				
				$posto = pg_fetch_result($result, 0, 'posto');
				$sql = "UPDATE tbl_posto_fabrica SET 
							codigo_posto 			= $codigo             ,
							nome_fantasia			= $nome_fantasia      ,
							tipo_posto				= 221                 ,
							contato_fone_comercial	= $telefone           ,
							contato_endereco		= $endereco           ,
							contato_numero			= $numero             ,
							contato_cidade			= $cidade             ,
							contato_bairro 			= $bairro 			  ,
							contato_email 			= $email 			  ,
							contato_cep				= $cep                ,
							contato_estado			= $estado	
						WHERE posto = $posto AND fabrica = 74
						;";
				$result = pg_query($con, $sql);
				
				if (pg_num_rows($result) > 0) {
					$msg_erro_interno .= "Erro ao atualizar posto na fabrica";
					$msg_erro_interno .= pg_last_error($con);
				}
				else{			
					$log .= "Dados do posto $codigo atualizados. \n\n";
				}
			}
			$num_linha ++;
		}
	}


	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}

	if (count($dadosDivergentes["Base"]) > 0) {
		require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

		$assunto = ucfirst($fabrica_nome) . '  - Importação de postos - Razão Social Divergente';

		$mail = new PHPMailer();
		$mail->IsHTML(true);
		$mail->From     = 'helpdesk@telecontrol.com.br';
		$mail->FromName = 'Telecontrol';
		$mail->AddAddress('suporte@telecontrol.com.br');
		$mail->Subject  = $assunto;
		$conteudo = "<p>Segue a listagem de Postos:</p>\n";
		$conteudo .= "<table border='1' width='100%'>\n";
		$conteudo .= "<tr bgcolor='#d90000' style='color:#fff;'>\n";
		$conteudo .= "<td style='padding:10px;'>Base Telecontrol Razão Social</td>\n";
		$conteudo .= "<td style='padding:10px;'>Enviado pela Fabrica Razão Social</td>\n";
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

	if (!empty($log)) {
		##########################################################
		#               Gerando email de logs                    #
		##########################################################
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "jeffersons@atlas.ind.br, evandro.carlos@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Log do arquivo de importação de postos";
		$mensagem  = "Segue dados da importação de Postos. \n\n ";
		$mensagem  .= "$log";
		mail($para, $assunto, $mensagem, $headers);	
	} 

	if(!empty($msg_erro_interno)){
		##########################################################
		#               Gerando email de erro                    #
		##########################################################
		//system ("mv $origem/serie.txt $origem/serie_$data_sistema.txt");

		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";	    
	   	//$para = "paulos@atlas.ind.br, cicero@atlas.ind.br, alaelcio@atlas.ind.br, helpdesk@telecontrol.com.br";
	    
	    $assunto   = "ATLAS - Log de erro do arquivo de importação de postos";
		$mensagem  = "Segue dados da importação de Postos. \n\n ";
		$mensagem  .= "$msg_erro_interno \n";
		mail($para, $assunto, $mensagem, $headers);	
	}

	$phpCron->termino();

if (file_exists("/home/atlas/atlas-telecontrol/posto.txt")) {
	system("mv /home/atlas/atlas-telecontrol/posto.txt  /tmp/atlas/posto_$data.txt");
}

?>
