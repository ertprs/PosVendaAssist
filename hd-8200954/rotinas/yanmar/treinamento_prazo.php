<?php

error_reporting(E_ALL);

/*
* Definições
*/
define ('APP', 'Cancelamento Automático de Treinamentos');
$fabrica      = 148;
$fabrica_nome = 'yanmar';
$hoje         = date('Y-m-d');
$ontem        = date('d/m/Y', strtotime('-1 day'));

function date_br($data) {
    return preg_replace('/(\d{4}).(\d\d).(\d\d)/', '$3/$2/$1', $data);
}

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaEmail("manuel.lopez@telecontrol.com.br");

    /**
     * Treinamentos fora de prazo que não tenham o número mínimo de vagas preenchidas,
     * serão cancelados (não exluídos), e os postos que tenham técnicos inscritos irão
     * receber um comunicado informando que o treinamento foi cancelado e o motivo.
     * [Admin que cadastrou o pedido recebe e-mail?]
     **/
    $sql = "SELECT tbl_treinamento.treinamento,
                   titulo, prazo_inscricao, vagas_min,
                   tbl_admin.email,
                   COUNT(treinamento_posto) AS vagas_preenchidas,
                   SUM(CASE confirma_inscricao WHEN TRUE THEN 1 ELSE 0 END) AS confirmadas
               FROM tbl_treinamento
          LEFT JOIN tbl_treinamento_posto USING(treinamento)
               JOIN tbl_admin ON tbl_admin.admin = tbl_treinamento.admin
              WHERE prazo_inscricao < CURRENT_DATE
                AND tbl_treinamento.ativo IS TRUE
              GROUP BY titulo, vagas_min, prazo_inscricao, treinamento, email
             HAVING COUNT(treinamento) < vagas_min
                OR SUM(CASE confirma_inscricao WHEN TRUE THEN 1 ELSE 0 END) < vagas_min";
    $res = pg_query($con, $sql);

    if ($erro = pg_last_error($con)) {
        throw new Exception("Erro ao obter os treinamentos da $fabrica_nome.\n$sql\n$erro");
    }

    if (!pg_num_rows($res)) {
        // $msg_erro = 'Sem treinamentos a serem processados. Fim da rotina.';
        die;
    }

    $treinamentos = pg_fetch_all($res);
    $codigos_treinamento = array();

    // difícil ter "muitos" treinamentos no mesmo dia, mas já está pronto para processar qualquer fábrica e quantidade de treinamentos
    $res_email_posto = pg_prepare($con, 'EMAIL_POSTOS',
        "SELECT DISTINCT posto,contato_email
           FROM tbl_posto_fabrica
           JOIN tbl_treinamento_posto USING(posto)
          WHERE fabrica = $1
            AND treinamento = $2
            AND tbl_treinamento_posto.ativo IS TRUE"
    );


    foreach ($treinamentos as $id => $info) {
        $codigos_treinamento[] = $info['treinamento'];
        extract($info);

        $msg = "O treinamento '$titulo' foi cancelado em ". date_br($hoje) .
            " por não ter preenchido o número mínimo de vagas ($vagas_min) até " .
            date_br($prazo_inscricao). '.';

        $txt_vagas .= ($vagas_preenchidas == 1) ? 'uma inscrição' : "$vagas_preenchidas inscrições";

        if ($vagas_preenchidas != $confirmadas)
            $txt_vagas .= ($vagas_preenchidas > 1) ? " (e destas, foram confirmadas $confirmadas)" : ($confirmadas == 0) ? ' (não confirmada)':'';

        $arr_msg[$treinamento] = array(
            'titulo' => $titulo,
            'posto'  => $msg,
            'admin'  => $msg . "\n\nTeve $txt_vagas para este treinamento.",
            'emails' => '',  // e-mails dos postos inscritos
            'admin_email' => $email,
        );

        $res_emails = pg_execute($con, 'EMAIL_POSTOS', array($fabrica, $treinamento));

        if (pg_num_rows($res_emails)) {
            $emails = pg_fetch_all($res_emails);

            foreach($emails as $email_info)
                if (filter_var($email_info['contato_email'], FILTER_VALIDATE_EMAIL))
                    $tr_email[] = $email_info['contato_email'];

            $arr_msg[$treinamento]['emails'] = $tr_email;
        }
    }

    // Desativa os treinamentos
    $lista_treinos = implode(', ', $codigos_treinamento);
    $res_up = pg_query($con, "UPDATE tbl_treinamento SET ativo = false WHERE treinamento IN($lista_treinos)");

    if (pg_affected_rows($res_up) != count($codigos_treinamento))
        $msg_erro .= "Esperado desativar " . count($codigos_treinamento) . ' treinamentos ('. $lista_treinos .'), mas foram desativados ' . pg_affected_rows($con) . '!';

    // Agora envia o(s) email(s)
    if (count($codigos_treinamento)) {
        foreach ($codigos_treinamento as $treinamento) {

            // E-mail para postos
            echo $to = implode(',', $arr_msg[$treinamento]['emails']);

            /******* TESTES *******/
            if (exec('hostname') == 'ip-10-253-30-227')
                $to   = 'manuel.lopez@telecontrol.com.br'; // TESTES!!!!!
            $subj = "$fabrica_nome - Cancelado Treinamento {$arr_msg[$treinamento]['titulo']}";
            $msg  = $arr_msg[$treinamento]['posto'];
            mail($to, $subj, $msg, "From: no-reply@$fabrica.com.br"); // O e-mail não precisa existir... De fato, melhor que não!

            // E-mail para o admin que criou (ou alterou) o treinamento
            echo $to = $arr_msg[$treinamento]['admin_email'];

            /******* TESTES *******/
            if (exec('hostname') == 'ip-10-253-30-227')
                $to   = 'manuel.lopez@telecontrol.com.br'; // TESTES!!!!!
            $msg  = $arr_msg[$treinamento]['admin'];
            mail($to, $subj, $msg, "From: no-reply@$fabrica.com.br"); // O e-mail não precisa existir... De fato, melhor que não!
        }
    }


    /*
    * Erro
    */
    if (!empty($msg_erro)) {

        $logClass->adicionaLog(array("titulo" => 'Log erro Cancelamento Automático de Treinamentos ' . ucfirst($fabrica))); // Titulo
        $logClass->adicionaLog($msg_erro);

        if ($logClass->enviaEmails() == "199") {
          echo "Log de erro enviado com Sucesso!";
        } else {
          echo $logClass->enviaEmails();
        }

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, $msg_erro_arq . "\n\n");
        fclose($fp);

    }

    /*
    * Cron Término
    */
    $phpCron->termino();

} catch (Exception $e) {
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();

    $logClass->adicionaLog($msg);
    $logClass->enviaEmails();
}

