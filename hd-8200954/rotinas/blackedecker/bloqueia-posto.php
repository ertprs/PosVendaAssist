<?php
/**
 *
 * bloqueia-postos.php
 *
 * Bloqueia Postos com OS abertas a mais de 6 meses Black&Decker
 *
 * @author  Ronald Santos
 * @version 2012.09.18
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // production Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require dirname(__FILE__) . '/../funcoes.php';

    $data_log['login_fabrica'] = 1;
    $data_log['dest'] = 'helpdesk@telecontrol.com.br';
    $data_log['log'] = 2;

    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	$fabrica = 1;
    $arquivos = "/tmp";
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

    $posto = $argv[1];

	if (ENV == 'teste' ) {
        
        $data_log['dest'] = 'ronald.santos@telecontrol.com.br';

		$destinatarios_clientes = "ronald.santos@telecontrol.com.br";

    } else {

        $data_log['dest'] = 'helpdesk@telecontrol.com.br';

        $destinatarios_clientes = "helpdesk@telecontrol.com.br";
    }

   
    if(strlen($posto) > 0){
       $sql = "SELECT tbl_os.os
                FROM tbl_os
                WHERE tbl_os.fabrica = $fabrica
                AND   tbl_os.posto   = $posto
                AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
		AND   tbl_os.data_fechamento IS NULL
		AND  tbl_os.excluida is FALSE 
		AND   tbl_os.cortesia IS FALSE
        LIMIT 1";

        $res = pg_query ($con,$sql);
		if(pg_num_rows($res) == 0){
			$sqlP = "SELECT observacao,admin,desbloqueio,resolvido FROM tbl_posto_bloqueio WHERE fabrica = $fabrica AND tbl_posto_bloqueio.pedido_faturado is false AND posto = $posto and extrato is false ORDER BY data_input DESC LIMIT 2";
			$resP = pg_query($con,$sqlP);
			if(pg_num_rows($resP) > 0){
				$observacao  = pg_result($resP,0,'observacao');
				$desb        = pg_result($resP,0,'desbloqueio');
       			$admin       = pg_result($resP,0,'admin');
                $resolvido   = pg_result($resP,0,'resolvido');

    			if($observacao !='Posto com bloqueio por possuir extratos pendentes a mais de 60 dias' and $desb == 'f' and ( empty($admin) OR !empty($resolvido) ) ) {
    				$sql = "INSERT INTO tbl_posto_bloqueio(
    						fabrica,
    						posto,
    						desbloqueio,
    						observacao)VALUES(
    						$fabrica,
    						$posto,
    						true,
    						'Desbloqueio automático, posto finalizou todas as OSs');";
    				$res = pg_query ($con,$sql);
    			}else{
    				$observacao  = pg_result($resP,1,'observacao');
    				$desb        = pg_result($resP,1,'desbloqueio');
	       			$admin       = pg_result($resP,1,'admin');
		            $resolvido   = pg_result($resP,1,'resolvido');
					if($observacao !='Posto com bloqueio por possuir extratos pendentes a mais de 60 dias' and $desb == 'f' and ( empty($admin) OR !empty($resolvido) ) ) {
    					$sql = "INSERT INTO tbl_posto_bloqueio(
    							fabrica,
    							posto,
    							desbloqueio,
    							observacao)VALUES(
    							$fabrica,
    							$posto,
    							true,
    							'Desbloqueio automático, posto finalizou todas as OSs');";
    					$res = pg_query ($con,$sql);
    				}
    			}
            }
        }
    }else{

        $sql = "SELECT DISTINCT tbl_os.posto 
            FROM tbl_os
            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$fabrica
            LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $fabrica
            WHERE tbl_os.fabrica = $fabrica
                AND   tbl_os.posto <> 6359
                AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
                AND   tbl_os.data_fechamento IS NULL
                AND   tbl_os.excluida IS FALSE
                AND   tbl_os.cortesia IS FALSE";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){

            for($i = 0; $i < pg_num_rows($res); $i++){

                $posto = pg_fetch_result($res, $i, 'posto');
		        $desb = '';
		        $admin = ''; 
                $sqlP = "SELECT desbloqueio,admin,resolvido,observacao  FROM tbl_posto_bloqueio WHERE fabrica = $fabrica AND tbl_posto_bloqueio.pedido_faturado is false AND posto = $posto and observacao !~ 'tico. Extratos conferidos' and extrato is false ORDER BY data_input DESC LIMIT 1";
                $resP = pg_query($con,$sqlP);
        		if(pg_num_rows($resP) > 0){
        			$desb        = pg_result($resP,0,'desbloqueio');
        			$admin       = pg_result($resP,0,'admin');
                    $resolvido   = pg_result($resP,0,'resolvido');
        		}

                if(pg_num_rows($resP) == 0 or ($desb == 't' and ( empty($admin) OR !empty($resolvido) ) ) ){
                    $sqlB = "INSERT INTO tbl_posto_bloqueio(fabrica,posto,observacao) VALUES($fabrica,$posto,'Posto com bloqueio por possuir OSs abertas a mais de 2 meses')";
                    $resB = pg_query($con,$sqlB);

                    if(!pg_last_error($con)){
                        $log[] = "Posto : $posto bloqueado";
                    }
                }

            }
        }

        $sql = "SELECT  posto, 
                        (SELECT  desbloqueio FROM tbl_posto_bloqueio B WHERE B.fabrica = $fabrica AND B.posto = A.posto AND B.pedido_faturado is false and observacao !~ 'tico. Extratos conferidos' and extrato is false ORDER BY data_input DESC limit 1) AS desbloqueio
                FROM tbl_posto_bloqueio A
                WHERE A.fabrica = $fabrica
                AND A.pedido_faturado is false
                GROUP BY A.posto";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            for($j = 0; $j < pg_num_rows($res); $j++){
                $posto = pg_fetch_result($res, $j, 'posto');
                $desbloqueio = pg_fetch_result($res, $j, 'desbloqueio');

                if($desbloqueio != 't'){
                    $cond = ($fabrica == 1) ? "" : "\nAND   tbl_os.cortesia IS FALSE\n";

                    $sqlP = "SELECT DISTINCT tbl_os.posto 
                        FROM tbl_os
                        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$fabrica
                        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $fabrica
                        WHERE tbl_os.fabrica = $fabrica
                            AND   tbl_os.posto = $posto
                            AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
                            AND   tbl_os.data_fechamento IS NULL
                            AND   tbl_os.excluida IS FALSE
                            $cond ";
                    $resP = pg_query($con,$sqlP);

					if(pg_num_rows($resP) == 0){
						$sqlP = "SELECT observacao , admin, resolvido FROM tbl_posto_bloqueio WHERE fabrica = $fabrica AND tbl_posto_bloqueio.pedido_faturado is false AND posto = $posto and extrato is false ORDER BY data_input DESC LIMIT 1";
						$resP = pg_query($con,$sqlP);
						if(pg_num_rows($resP) > 0){
							$observacao  = pg_result($resP,0,'observacao');
							$admin       = pg_result($resP,0,'admin');
						    $resolvido   = pg_result($resP,0,'resolvido');


						}
						if($observacao !='Posto com bloqueio por possuir extratos pendentes a mais de 60 dias'  and ( empty($admin) OR !empty($resolvido) ) ) {
							$sqlT = "INSERT INTO tbl_posto_bloqueio(
									fabrica,
									posto,
									desbloqueio,
									observacao)VALUES(
									$fabrica,
									$posto,
									true,
									'Desbloqueio automático, posto não possui OSs abertas a mais de 60 dias');";
							$resT = pg_query ($con,$sqlT);
						}
                    }
                }
            }
        }

        $mailer = new PHPMailer();
        $mailer->IsSMTP();
        $mailer->IsHTML();
        $mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");

        $emails = explode(",", $destinatarios_clientes);
        if(count($emails)){
            foreach ($emails as $email) {
                $mailer->AddAddress($email);
            }
        }else{
            $mailer->AddAddress($destinatarios_clientes);
        }

        $arquivo_anexo = Date("d-m-Y")."-LOG-BLOQUEIA_POSTO.TXT";

        $mensagem  = "Logs Bloqueio Postos<br><br>";
        $mensagem  .= "Mensagem segue em anexo!<br><br>";
        $mensagem .= "<br><br>Att.<br>Telecontrol Networking";

        $mailer->Subject = "Logs Bloqueio Postos";

        if(count($logs) > 0){
            $arquivo_completo_anexo = "{$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo_anexo}";
            $anexo = fopen($arquivo_completo_anexo, "w+");
            fputs($anexo,implode("\r\n", $logs));
            fclose($anexo);

            $mailer->Body = $mensagem;
            $mailer->AddAttachment("{$arquivos}/blackedecker/nao_bkp/arquivos/{$arquivo_anexo}");
            $mailer->Send();
        }
    }
	
	$phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    //echo $msg."\r\n";

    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar bloqueio de postos", $msg);
}
