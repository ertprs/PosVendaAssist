<?php
/**
 * Comunica OS Finalizada
 *
 * - Envia email da quantidade de OS's
 * finalizada no dia anterior, agrupadas
 * por posto

 * @author William Ap. Brandino
 * @version 2014.11.17
 */

error_reporting(E_ALL ^ E_NOTICE);

try {
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/PHPMailer/PHPMailerAutoload.php';
    include dirname(__FILE__) . '/../../class/email/PHPMailer/class.phpmailer.php';

    $vet['fabrica'] = 'bestway';
    $vet['tipo']    = 'pedido';
    $vet['log']     = 2;
    $fabrica        = 81;
    $data_sistema   = Date('Y-m-d');
    $logs_erro              = array();

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

   /**
    * - Será feita a busca das OS's finalizadas
    * na data anterior, ou no intervalo de 3 dias,
    * no caso da rotina rodar na segunda feira
    *
    * @params integer diaSemana
    * @example Se for 1(Segunda), buscar o espaço de três dias
    * Senão, o dia anterior
    */

    $diaSemana = date('w');

    if($diaSemana == 1){
        $sqlEspaco = " tbl_os.data_fechamento BETWEEN (CURRENT_DATE - INTERVAL '3 days')::DATE AND (CURRENT_DATE - INTERVAL '1 day')::DATE";
    }else{
        $sqlEspaco = " tbl_os.data_fechamento = (CURRENT_DATE - INTERVAL '1 day')::DATE";
    }
    

   /**
    * - Verificação de postos
    * que fecharam OS nos dias anteriores
    */

    $sqlPosto = "
        SELECT  DISTINCT
                tbl_posto_fabrica.posto,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome
        FROM    tbl_os
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                    AND tbl_os.os_fechada           IS TRUE
                                    AND tbl_posto_fabrica.fabrica   = $fabrica
                                    AND tbl_os.fabrica              = $fabrica
        JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_posto_fabrica.posto
        JOIN    tbl_os_produto      USING(os)
        JOIN    tbl_os_item         USING(os_produto)
   LEFT JOIN    tbl_pedido          USING(pedido)
   LEFT JOIN    tbl_faturamento     ON tbl_faturamento.pedido = tbl_pedido.pedido
        WHERE   tbl_os.consumidor_revenda = 'R'
        AND     $sqlEspaco
  ORDER BY      tbl_posto_fabrica.posto
    ";
    $resPosto = pg_query($con,$sqlPosto);

    $numPosto = pg_numrows($resPosto);

    if($numPosto > 0){
        $csv = "<table>
            <thead>
                <tr style='background-color:#CCC'>
                    <th>SUA OS</th>
                    <th>PEDIDO</th>
                    <th>DATA PEDIDO</th>
                    <th>DATA FECHAMENTO OS</th>
                    <th>NOTA FISCAL</th>
                </tr>
            </thead>
            <tbody>
        ";
        for($i=0;$i<$numPosto;$i++){
            $posto          = pg_fetch_result($resPosto,$i,posto);
            $posto_codigo   = pg_fetch_result($resPosto,$i,codigo_posto);
            $posto_nome     = pg_fetch_result($resPosto,$i,nome);

            $sqlOs = "
                SELECT  DISTINCT tbl_os.sua_os,
                        tbl_pedido.pedido,
                        TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
                        tbl_faturamento.nota_fiscal,
                        TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
                FROM    tbl_os
                JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                            AND tbl_os.os_fechada           IS TRUE
                                            AND tbl_posto_fabrica.fabrica   = $fabrica
                                            AND tbl_posto_fabrica.posto     = $posto
                                            AND tbl_os.fabrica              = $fabrica
                JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                JOIN    tbl_os_produto      USING(os)
                JOIN    tbl_os_item         USING(os_produto)
           LEFT JOIN    tbl_pedido          USING(pedido)
           LEFT JOIN    tbl_faturamento_item     ON tbl_faturamento_item.pedido = tbl_pedido.pedido and tbl_faturamento_item.peca = tbl_os_item.peca
           LEFT JOIN    tbl_faturamento     ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                WHERE   $sqlEspaco
				AND     tbl_os.consumidor_revenda = 'R'
				AND     tbl_os_item.servico_realizado <> 10655
          ORDER BY      tbl_pedido.pedido,tbl_os.sua_os
            ";
            $resOs = pg_query($con,$sqlOs);

            $csv .= "
                <tr style='background-color:#CCC'>
                    <td colspan='5'>$posto_codigo - $posto_nome</td>
                </tr>
            ";

            for($j=0;$j<pg_numrows($resOs);$j++){
                $sua_os             = pg_fetch_result($resOs,$j,sua_os);
                $pedido             = pg_fetch_result($resOs,$j,pedido);
                $data_pedido        = pg_fetch_result($resOs,$j,data_pedido);
                $data_fechamento    = pg_fetch_result($resOs,$j,data_fechamento);
                $nota_fiscal        = pg_fetch_result($resOs,$j,nota_fiscal);

				$cor = ($j % 2 == 0) ? "#FFF" : "#FCC" ;

				if(empty($data_pedido) and $os_ant == $sua_os) continue;

                $csv .= "
                    <tr style='background-color:$cor'>
                        <td>$sua_os</td>
                        <td>$pedido</td>
                        <td>$data_pedido</td>
                        <td>$data_fechamento</td>
                        <td>$nota_fiscal</td>
                    </tr>
				";
				$os_ant = $sua_os;
            }
        }
        $csv .= "
            </tbody>
        </table>
        ";

        $os_finalizadas = "/tmp/bestway/os_finalizadas.xls";
        $arquivo_log = "/tmp/bestway/log_envio_email_".date('Y-m-d').".err";
        system("mkdir /tmp/bestway/ 2> /dev/null ; chmod 777 /tmp/bestway/");
//         $os_finalizadas = "xls/os_finalizadas.xls";
//         $arquivo_log = "xls/log_envio_email_".date('Y-m-d').".err";
//         system("mkdir xls 2> /dev/null ; chmod 777 xls");

        $excel = fopen($os_finalizadas,'w');
        fwrite($excel,$csv);
        fclose($excel);

        if(file_exists($os_finalizadas)){
            $mail = new PHPMailer;

            $mail->isSMTP();

            $mail->From     = "no-reply@telecontrol.com.br";
            $mail->FromName = "Cron Telecontrol - BESTWAY";
            $mail->addAddress('jader@telecontrol.com.br','Jader Abdo');
            $mail->addCC('marcos.barbante@telecontrol.com.br','Marcos Barbante');
            $mail->addCC('luis.carlos@telecontrol.com.br','Luis Carlos');

//             $mail->addAddress('william.brandino@telecontrol.com.br','William Brandino');
//             $mail->addCC('joao.junior@telecontrol.com.br','João Junior');

            $mail->addAttachment($os_finalizadas);
            $mail->isHTML(true);

            $mail->Subject = "OS finalizadas";
            $mail->Body = "Acesso o anexo com as OS finalizadas anteriormente";

            $mail->Send();
        }
    }
}catch(Exception $e) {
    echo $e->getMessage();
}

?>
