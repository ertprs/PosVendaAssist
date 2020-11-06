<?php 
	
	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	if ($_serverEnvironment == "production") {
            define("ENV", "prod");
    } else {
            define("ENV", "dev");
    }

    /* Inicio Processo */
	//$phpCron = new PHPCron($fabrica, __FILE__);
	//$phpCron->inicio();

    $login_fabrica = 74;
	$msg_erro      = array();


	if (ENV == "prod") {
		try {
			echo "passou no prod";
			$soap = new SoapClient("http://webservice.correios.com.br/service/rastro/Rastro.wsdl", array("trace" => 1, "exception" => 1));
		} catch (Exception $e) {
	        $response[] = array("resultado" => "false", "mensagem" => "ERRO AO CONECTAR SERVIDOR DOS CORREIOS");

	        require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	        $assunto = utf8_decode('Webservice - Correios');

	        $mail = new PHPMailer();
	        $mail->IsHTML(true);
	        $mail->From = 'suporte@telecontrol.com.br';
	        $mail->FromName = 'Telecontrol';
	        $mail->AddAddress('suporte@telecontrol.com.br');
	        

	        $mail->Subject = $assunto;
	        $mail->Body = "ERRO AO CONECTAR SERVIDOR DOS CORREIOS...<br/> Fabrica: Atlas Fogões <br/> Data: ".date('d/m/Y H:i:s')." <br/>";
	        $mail->Send();
	                
	        return $response;
	    }
	} else {
		echo "passou no dev";
		$soap = new SoapClient("http://webservice.correios.com.br/service/rastro/Rastro.wsdl", array("trace" => 1, "exception" => 1));
	}

	echo "passou dos soaps\n\n";

	//pega senha da Fabrica
	$sql_senha = "SELECT fabrica,usuario,senha FROM tbl_fabrica_correios WHERE ativo IS TRUE and fabrica = $login_fabrica";
	echo $sql_senha;
	$res_senha = pg_query($con, $sql_senha);
	if(pg_num_rows($res_senha)>0){
		$usuario 	= pg_fetch_result($res_senha, 0, usuario);
		$senha 		= pg_fetch_result($res_senha, 0, senha);
	}
	//pegar código de rastreio.
	$sql_rastreio = "SELECT conhecimento, faturamento FROM tbl_faturamento 
						WHERE fabrica = $login_fabrica 
						AND conhecimento <> '' 
						AND emissao >= CURRENT_DATE - interval '3 months'";
	//$res_rastreio = pg_query($con, $sql_rastreio);

	if(1==1){
		echo "passou aqui 2";
		//for($i=0; $i<pg_num_rows($res_rastreio); $i++){
		
			$conhecimento = pg_fetch_result($res_rastreio, 0, conhecimento);
			$metodo = "buscaEventos";

		    $buscaEventos = (object) array("usuario" => "$usuario", "senha" => "$senha", "tipo" => "L", "resultado" => "T", "lingua" => 101,"objetos" => "PI846937679BR");

		    echo "<pre>";
		    	print_r($buscaEventos);
		    echo "<pre>";

		    $soapResult = $soap->__soapCall($metodo, array($buscaEventos));

		    echo "<pre>";
		    	print_r($soapResult);
		    echo "<pre>";

		//}
	}


  


    





	/*Término Processo*/
	//$phpCron->termino();


?>