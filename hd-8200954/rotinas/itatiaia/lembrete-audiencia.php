<?php 
/**
 *
 * lembrete-audiencia.php
 *
 * Enviar email para o Responsável 1 dia antes da data de audiência 1 e 2 
 *
 * @author  Lucas Maestro
 * @version 2018-08-25
 *
*/
error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    include_once __DIR__ . "/../../class/aws/s3_config.php";
    include_once S3CLASS;

    $data['login_fabrica']  = 183;
    $data['fabrica_nome']   = 'itatiaia';
    $data['arquivo_log']    = 'lembrete_processo';
    $data['log']            = 2;
    $data['arquivos']       = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs                   = array();
    $logs_erro              = array();
    $logs_cliente           = array();
    $erro                   = false;
    $fabrica_qtde_anexos    = 5;

    $login_fabrica          = 183;
 

    if (ENV == 'producao' ) {
        $data['dest']       = 'helpdesk@telecontrol.com.br';
    } else {
        $data['dest']       = 'guilherme.monteiro@telecontrol.com.br';
    }

    $sql = "
        SELECT DISTINCT
            tbl_processo.processo,
            tbl_processo.os,
            tbl_processo.orgao,
            tbl_processo.comarca,
            tbl_processo.valor_causa, 
            tbl_processo.numero_processo, 
            tbl_processo.data_sentenca,
            tbl_processo.advogado_email,
            tbl_admin.email AS email_admin,
            tbl_processo.data_input,
            tbl_processo.consumidor_nome,
            tbl_produto.descricao as descricao_produto, 
            tbl_produto.referencia as referencia_produto,
            tbl_posto.nome as nome_posto,
            tbl_posto_fabrica.codigo_posto,
            tbl_motivo_processo.descricao as descricao_motivo_processo,
            (current_date + INTERVAL '1 day') AS data_limite_aviso
        FROM tbl_processo             
        JOIN tbl_processo_item ON tbl_processo_item.processo = tbl_processo.processo
        JOIN tbl_admin ON tbl_admin.admin = tbl_processo.admin AND tbl_admin.fabrica = $login_fabrica
        LEFT JOIN tbl_os ON tbl_os.os = tbl_processo.os AND tbl_os.fabrica = $login_fabrica
        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_processo.produto AND tbl_produto.fabrica_i = $login_fabrica
        LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        LEFT JOIN tbl_motivo_processo ON tbl_motivo_processo.motivo_processo = tbl_processo.motivo_processo AND tbl_motivo_processo.fabrica = $login_fabrica
        WHERE tbl_processo.fabrica = $login_fabrica
        AND (tbl_processo_item.data_audiencia1 > current_date OR tbl_processo_item.data_audiencia2 > current_date)";
    $resDados = pg_query($con, $sql);
    
    if (pg_num_rows($resDados) > 0){
    
        for($a = 0; $a < pg_num_rows($resDados); $a++){
            $enviar_email                      = false;
            $processo                          = pg_fetch_result($resDados, $a, 'processo');
            $os                                = pg_fetch_result($resDados, $a, 'os');
            $orgao                             = pg_fetch_result($resDados, $a, 'orgao');
            $comarca                           = pg_fetch_result($resDados, $a, 'comarca');
            $valor_causa                       = pg_fetch_result($resDados, $a, 'valor_causa');
            $valor_causa                       = number_format($valor_causa, 2, '.', '');
            $numero_processo                   = pg_fetch_result($resDados, $a, 'numero_processo');
            $data_sentenca                     = mostra_data(pg_fetch_result($resDados, $a, "data_sentenca"));
            $advogado_email                    = pg_fetch_result($resDados, $a, 'advogado_email');
            $data_input                        = substr(pg_fetch_result($resDados, $a, data_input),0,10);
            $consumidor_nome                   = pg_fetch_result($resDados, $a, 'consumidor_nome');
            $descricao_produto                 = pg_fetch_result($resDados, $a, 'descricao_produto');
            $referencia_produto                = pg_fetch_result($resDados, $a, 'referencia_produto');
            $nome_posto                        = pg_fetch_result($resDados, $a, 'nome_posto');
            $codigo_posto                      = pg_fetch_result($resDados, $a, 'codigo_posto');
            $descricao_motivo_processo         = pg_fetch_result($resDados, $a, 'descricao_motivo_processo');
            $data_limite_aviso                 = pg_fetch_result($resDados, $a, 'data_limite_aviso');
            $email_admin                       = pg_fetch_result($resDados, $a, 'email_admin');

            $sqlProcessoItem = "
                SELECT
                    tbl_processo_item.data_audiencia1::date AS data_valida_audiencia1,
                    tbl_processo_item.data_audiencia2::date AS data_valida_audiencia2,
                    tbl_processo_item.data_audiencia1,
                    tbl_processo_item.data_audiencia2,
                    tbl_processo_item.data_acordo,
                    tbl_processo_pedido_cliente.descricao AS processo_pedido_cliente_descricao,
                    tbl_proposta_acordo.descricao AS proposta_acordo_descricao 
                FROM tbl_processo_item
                JOIN tbl_processo_pedido_cliente ON tbl_processo_pedido_cliente.processo_pedido_cliente = tbl_processo_item.processo_pedido_cliente
                JOIN tbl_proposta_acordo ON tbl_proposta_acordo.proposta_acordo = tbl_processo_item.proposta_acordo
                WHERE tbl_processo_item.processo = $processo
                AND (tbl_processo_item.data_audiencia1 > current_date OR tbl_processo_item.data_audiencia2 > current_date)
                ORDER BY tbl_processo_item.data_input LIMIT 1 ";
            $resProcessoItem = pg_query($con, $sqlProcessoItem);
            
            if (pg_num_rows($resProcessoItem) > 0){
                $data_audiencia1                   = pg_fetch_result($resProcessoItem, 0, 'data_audiencia1');
                $data_audiencia2                   = pg_fetch_result($resProcessoItem, 0, 'data_audiencia2');
                $data_acordo                       = mostra_data(pg_fetch_result($resProcessoItem, 0, 'data_acordo'));
                $processo_pedido_cliente_descricao = pg_fetch_result($resProcessoItem, 0, 'processo_pedido_cliente_descricao');
                $proposta_acordo_descricao         = pg_fetch_result($resProcessoItem, 0, 'proposta_acordo_descricao');
                $data_valida_audiencia1            = pg_fetch_result($resProcessoItem, 0, 'data_valida_audiencia1');
                $data_valida_audiencia2            = pg_fetch_result($resProcessoItem, 0, 'data_valida_audiencia2');
            }
            
            if ((strtotime($data_limite_aviso) == strtotime($data_valida_audiencia1)) OR (strtotime($data_limite_aviso) == strtotime($data_valida_audiencia2))){
                $enviar_email = true;
            }

            $data_audiencia1 = mostra_data($data_audiencia1);
            $data_audiencia2 = mostra_data($data_audiencia2);

            //parte de anexo
            list($ano,$mes,$dia) = explode("-",$data_input);
            
            if(!isset($s3)){
                $s3 = new AmazonTC('processos', (int)$login_fabrica);
            }

            $arr_anexos = array();

            for ($b = 0; $b < $fabrica_qtde_anexos; $b++) {
                $anexos = $s3->getObjectList("{$login_fabrica}_{$processo}_{$b}", false, $ano, $mes);

                if (!empty($anexos)){
                    $link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);
                    system("wget -q -O ".$data['arquivos'].'/'.basename($anexos[0])." '$link' ");

                    if(!empty(basename($anexos[0])))  { 
                        $arr_anexos[] = $data['arquivos'].'/'.basename($anexos[0]);
                    }
                }
            }

            $sql_evento = "
                SELECT 
                    contexto, 
                    registro_id as processo, 
                    mensagem, 
                    data_aviso,
                    contato->>'name' as nome, 
                    contato->>'email' as email, 
                    contato->>'type' as tipo_alerta 
                FROM tbl_evento_alerta
                WHERE fabrica = $login_fabrica 
                AND registro_id = $processo
                AND contexto IN(3,4)
                AND data_aviso = current_date";
            $res_evento = pg_query($con, $sql_evento);   
            
            if (pg_num_rows($res_evento) > 0){
                for ($i=0; $i < pg_num_rows($res_evento); $i++) { 
                    $data_aviso         = pg_fetch_result($res_evento, $i, data_aviso);
                    $mensagem           = utf8_decode(pg_fetch_result($res_evento, $i, mensagem));
                    $nome               = pg_fetch_result($res_evento, $i, nome);
                    $processo           = pg_fetch_result($res_evento, $i, processo);
                    $email              = pg_fetch_result($res_evento, $i, email);
                    $contexto           = pg_fetch_result($res_evento, $i, contexto);
            
                    if($contexto == 3){
                        $titulo = "Lembrete de Audiencia";
                        $tipo = "Audiência";
                    }elseif($contexto == 4){
                        $titulo = "Lembrete de Acordo/Sentenca";
                        $tipo = "Acordo/Sentença";
                    }

                    if($contexto == 3){
                        if(!empty($data_audiencia2)){
                            $data_aud = $data_audiencia2;
                        }else{
                            $data_aud = $data_audiencia1;
                        }
                    }
                    
                    if($contexto == 4){
                        if(!empty($data_sentenca)){
                            $data_aud = $data_sentenca;
                        }else{
                            $data_aud = $data_acordo;
                        }
                    }   

                    $texto_email = "Prezado(a) $nome <br><br> Lembrete de $tipo do processo <b>$numero_processo</b> <br><br>
                    <b>Mensagem:</b> $mensagem <br><br>
                    <table cellspacing='0' cellpadding='0' border='1'>
                        <tr>
                            <td><b>Descricao Motivo Processo</b></td>
                            <td>$descricao_motivo_processo</td>
                        </tr>
                        <tr>
                            <td><b>Orgão</b></td>
                            <td>$orgao</td>                
                        </tr>
                        <tr>
                            <td><b>Comarca</b></td>
                            <td>$comarca</td>                
                        </tr>
                        <tr>
                            <td><b>Valor Causa</b></td>
                            <td>$valor_causa</td>
                        </tr>
                        <tr>
                            <td><b>Nome Autor</b></td>
                            <td>$consumidor_nome</td>
                        </tr>
                        <tr>
                            <td><b>Número do Processo</b></td>
                            <td>$numero_processo</td>                
                        </tr>
                        <tr>
                            <td><b>Processo Pedido Cliente Descrição</b></td>
                            <td>$processo_pedido_cliente_descricao</td>                
                        </tr>
                        <tr>
                            <td><b>Proposta Acordo Descricao</b></td>
                            <td>$proposta_acordo_descricao</td>                
                        </tr>                
                        <tr>
                            <td><b>Posto</b></td>
                            <td>$codigo_posto - $nome_posto</td>                
                        </tr>
                        <tr>
                            <td> <b>Produto</b> </td>
                            <td>$referencia_produto - $descricao_produto</td>
                        </tr>
                        <tr>
                            <td><b>Data $tipo</b></td>
                            <td>$data_aud</td>
                        </tr>
                        <tr>
                            <td> <b>Ordem de Serviço</b> </td>
                            <td>$os</td>
                        </tr>
                    </table> \n\n ";

                    $mail = new PHPMailer(); // nao retirar 
                    $mail->IsHTML();
                    $mail->Subject = "$titulo";
                    $mail->Body = $texto_email;
                    $mail->AddAddress($email);

                    foreach ($arr_anexos as $arquivo) {
                        $mail->AddAttachment($arquivo);                
                    }

                    if (!$mail->Send()) echo 'erro ao enviar email';
                }
            }else{
                
                if ($enviar_email === true){

                    $texto_email = "Prezado(a) <br><br> Lembrete processo <b>$numero_processo</b> <br><br>
                    <table cellspacing='0' cellpadding='0' border='1'>
                        <tr>
                            <td><b>Descricao Motivo Processo</b></td>
                            <td>$descricao_motivo_processo</td>
                        </tr>
                        <tr>
                            <td><b>Orgão</b></td>
                            <td>$orgao</td>                
                        </tr>
                        <tr>
                            <td><b>Comarca</b></td>
                            <td>$comarca</td>                
                        </tr>
                        <tr>
                            <td><b>Valor Causa</b></td>
                            <td>$valor_causa</td>
                        </tr>
                        <tr>
                            <td><b>Nome Autor</b></td>
                            <td>$consumidor_nome</td>
                        </tr>
                        <tr>
                            <td><b>Número do Processo</b></td>
                            <td>$numero_processo</td>                
                        </tr>
                        <tr>
                            <td><b>Processo Pedido Cliente Descrição</b></td>
                            <td>$processo_pedido_cliente_descricao</td>                
                        </tr>
                        <tr>
                            <td><b>Proposta Acordo Descricao</b></td>
                            <td>$proposta_acordo_descricao</td>                
                        </tr>                
                        <tr>
                            <td><b>Posto</b></td>
                            <td>$codigo_posto - $nome_posto</td>                
                        </tr>
                        <tr>
                            <td> <b>Produto</b> </td>
                            <td>$referencia_produto - $descricao_produto</td>
                        </tr>
                        <tr>
                            <td><b>Data $tipo</b></td>
                            <td>$data_aud</td>
                        </tr>
                        <tr>
                            <td> <b>Ordem de Serviço</b> </td>
                            <td>$os</td>
                        </tr>
                    </table> \n\n ";

                    $mail = new PHPMailer(); // nao retirar 
                    $mail->IsHTML();
                    $mail->Subject = "$titulo";
                    $mail->Body = $texto_email;
                    $mail->AddAddress($advogado_email);
                    $mail->AddAddress($email_admin);

                    foreach ($arr_anexos as $arquivo) {
                        $mail->AddAttachment($arquivo);                
                    }

                    if (!$mail->Send()) echo 'erro ao enviar email';
                }
            }
        }  
    }

    if(strlen($msg_erro) >0 ){
        $msg = "Erro ao executar rotina 'Verifica audiência - Makita' ";

        Log::envia_email($data,Date('d/m/Y H:i:s')." - Makita - Erro na verifica audiência", $msg);
    }
?>
