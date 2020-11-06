<?php 

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/communicator.class.php';


    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';


    define('ENV','producao');

    $fabrica     = 160;
    $data        = date('d-m-Y');

    $phpCron = new PHPCron($fabrica, __FILE__); 
    $phpCron->inicio();

    $vet['fabrica'] = 'Einhell';
    $vet['tipo']    = 'envia_pesquisa';
    $vet['log']     = 1;
    $data = date("Y-m-d-H-m-s");
    $dir                = "/tmp/cadence";
    $file_erro          = "envia_pesquisa_$data.err";
    $file_log           = "envia_pesquisa_$data.log";
    $file_log_email     = "envia_pesquisa_erro_email_$data.err";

    if (ENV == 'producao' ) {
        $emails['dest']       = 'helpdesk@telecontrol.com.br';
        $link_pesquisa = "https://posvenda.telecontrol.com.br/assist/externos/einhell/pesquisa_recadastramento.php?posto=";
    } else {
        $emails['dest']       = 'lucas.carlos@telecontrol.com.br';
        $link_pesquisa = "http://novodevel.telecontrol.com.br/~lucas/chamados/hd-4367698/externos/einhell/pesquisa_recadastramento.php?posto=";
    }

    $sqlPosto = "SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.contato_email, tbl_posto_fabrica.posto from tbl_posto_fabrica 
    join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica  = $fabrica 
    where fabrica = $fabrica and credenciamento = 'CREDENCIADO'  and tbl_posto_fabrica.contato_email is not null and tipo_posto =536 and digita_os ";

    $resPosto = pg_query($con, $sqlPosto);
    for($i=0; $i<pg_num_rows($resPosto);$i++){
    	$posto = pg_fetch_result($resPosto, $i, posto);
    	$nome = pg_fetch_result($resPosto, $i, nome);
    	$codigo_posto = pg_fetch_result($resPosto, $i, codigo_posto);
    	$email = pg_fetch_result($resPosto, $i, contato_email);

    	if(strlen(trim($email))==0){
    		$log_erro .=  "Posto: $codigo_posto - Nome: $nome  Obs: Sem e-mail <br>";
    		continue;
    	}

       	$assunto = "Pesquisa de Recadastramento - Einhell";
    	$mensagem = "Prezado Assistente Técnico, <br><Br>Einhell Brasil tem trabalhado intensamente no sentido de proporcionar um pós-venda cada dia mais dinâmico e
efetivo junto a todos os parceiros de assistência técnica espalhados em todo território nacional. <br><Br> Com objetivo de fortalecermos nossas relações, inclusive visando projetos futuros de médio prazo, estamos realizando
uma pesquisa junto a todas as AT's que se encontram atualmente credenciadas no sistema Telecontrol. <br><Br>Desta forma, pedimos gentilmente, que nos responda sobre três breves situações: <br><Br> 
<a href='$link_pesquisa".md5($posto)."'>Clique aqui para responder a pesquisa</a>";

	$mailTc = new TcComm('smtp@posvenda');
	    $res = $mailTc->sendMail(
	        $email,
	        $assunto,
	        $mensagem,
	        'noreply@telecontrol.com.br'
	    );    	
   }      

   if(strlen(trim($log_erro))>0){
   		$msg = "Erro ao executar rotina de enviar pesquisa para os posto - Einhell \n\n". $log_erro ;
   		Log::envia_email($emails,Date('d/m/Y H:i:s')." - Erro Envia email pesquisa", $msg);

   }



    







/*
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->IsHTML();
    #$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
    $mail->Subject = Date('d/m/Y')." - Alerta de Audiência ";
    $mail->Body = "Alerta de audiências marcadas para o processo de número $numero_processo, favor confirmar presença do preposto. \n Audiências1: $audiencias1\n Audiências2: $audiencias2\n ";
    $mail->AddAddress("$email");
    $mail->Send();*/






?>
