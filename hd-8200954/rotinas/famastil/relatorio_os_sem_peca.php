<?php

	define('APP','OS com peças sem pedidos  - Famastil'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {
		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';
        include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
		$fabrica     = 86;
		$data 		 = date('d-m-Y');
		
		$phpCron = new PHPCron($fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'famastil';
		$vet['tipo']    = 'os';
		$login_fabrica = 86;

        $vet['log']     = 1;

		$msg_erro = array();


        $sql = "SELECT distinct tbl_os.os,
                    tbl_os.data_abertura,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY') as abertura,
                    tbl_os.posto,
                    tbl_posto.nome as posto_nome,
                    tbl_posto.cnpj as posto_cnpj,
                    tbl_os.revenda,
                    tbl_os.consumidor_nome,
                    tbl_produto.descricao as produto,
                    tbl_os.excluida,
                    to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') as digitacao_item,
                    tbl_peca.descricao
            FROM tbl_os
            JOIN tbl_os_produto USING(os)
            JOIN tbl_os_item USING(os_produto)
            JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
            JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
            JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND tbl_os_status.fabrica_status= $login_fabrica
            WHERE tbl_os.fabrica = $login_fabrica
                    AND tbl_os.finalizada IS NULL
                    AND tbl_os.data_fechamento IS NULL
                    AND tbl_os.os_fechada IS FALSE
                    AND tbl_os_item.pedido IS NOT NULL
                    AND tbl_os.excluida IS NOT TRUE
                    AND UPPER(tbl_posto_fabrica.credenciamento) NOT IN ('DESCREDENCIADO')
                    AND tbl_servico_realizado.gera_pedido is true
                    AND tbl_os.data_abertura < current_date - INTERVAL '3 days'
                    AND tbl_os.os NOT IN (SELECT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status= $login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (15,62,64,65,70,139))
            ORDER BY tbl_os.os,
                    tbl_posto.cnpj,
                    tbl_peca.descricao,
                    digitacao_item ,
                    tbl_peca.descricao,
                    tbl_os.revenda,
                    tbl_os.consumidor_nome,
                    tbl_produto.descricao ,
                    tbl_os.posto,
                    tbl_posto.nome ,
                    tbl_os.data_abertura ,
                    tbl_os.excluida;
            
            ";
		$res = pg_query($con,$sql);
//        echo nl2br($sql);
        if (pg_num_rows($res)>0){

            $file = "<table border='1'>
                            <thead>
                                <tr>
                                <th colspan='100%' bgcolor='#333333' style='color #333333 !important;' >
                                    RELATÓRIO OS ABERTAS COM PEÇAS
                                </th>
                            </tr>
                            <tr>
                                  <th bgcolor='#596D98B' style='color #FFFFFF !important;' > OS </th>
                                  <th bgcolor='#596D98B' style='color #FFFFFF !important;' > CODIGO DO POSTO </th>
                                  <th bgcolor='#596D98B' style='color #FFFFFF !important;' > NOME POSTO </th>
                                  <th bgcolor='#596D98B' style='color #FFFFFF !important;' > PEÇA </th>
                                  <th bgcolor='#596D98B' style='color #FFFFFF !important;' > DATA LANÇAMENTO </th>
                                  <th bgcolor='#596D98B' style='color #FFFFFF !important;' > PRODUTO  </th>
                            </tr>
                        </thead>
                        <tbody>    ";

            for($i=0;$i < pg_num_rows($res);$i++){
                $os     = pg_fetch_result($res,$i,'os');
                $cnpj   = pg_fetch_result($res,$i,'posto_cnpj');
                $nome   = pg_fetch_result($res,$i,'posto_nome');
                $peca   = pg_fetch_result($res,$i,'descricao');
                $data   = pg_fetch_result($res,$i,'data_abertura');
                $produto= pg_fetch_result($res,$i,'produto');
                $excluida= pg_fetch_result($res,$i,'excluida');
                if ($excluida == 't'){
                    continue;
                }
                $file.= "
                    <tr>
                        <td>$os</td>
                        <td>$cnpj</td>
                        <td>$nome </td>
                        <td>$peca</td>
                        <td>$data </td>          
                        <td>$produto </td>

                    </tr>
                     ";


            }
            $file .= "</tbody>
                </table>"; 
           
            include '../../class/email/PHPMailer/class.phpmailer.php';
            
            $mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
            $mail->IsSMTP(); // telling the class to use SMTP

            try {
              //$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
              $mail->SMTPAuth   = true;                  // enable SMTP authentication
              $mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
              $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
              $mail->Port       = 465;                   // set the SMTP port for the GMAIL server
              $mail->Username   = "noreply@telecontrol.com.br";  // GMAIL username
              $mail->Password   = "tele6588";            // GMAIL password
              if (ENV == 'producao'){
                  $mail->AddAddress('helpdesk@telecontrol.com.br');
                  $mail->AddAddress('assistencia@famastil.com.br');
                  $mail->AddAddress('assistencia2@famastil.com.br');
                  $mail->AddAddress('deise.bonatto@famastil.com.br');

              }
              else{
                $mail->AddAddress('armando.migliorini@telecontrol.com.br');
                $mail->AddAddress('william.lopes@telecontrol.com.br');
              }
              $mail->SetFrom('noreply@telecontrol.com.br', 'Telecontrol');
              $mail->Subject = Date('d/m/Y').' - Ordens de Serviço Abertas com Peças';
              $mail->MsgHTML($file);
              $mail->Send();
            } catch (Exception $e) {
                echo "Erro e-mail";
            }

        }	
       $phpCron->termino(); 

	}
	catch (Exception $e) {

		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
