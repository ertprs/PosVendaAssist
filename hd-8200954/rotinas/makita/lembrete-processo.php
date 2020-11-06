<?php 
/**
 *
 * lembrete_processo.php
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

    $data['login_fabrica']  = 42;
    $data['fabrica_nome']   = 'makita';
    $data['arquivo_log']    = 'lembrete_processo';
    $data['log']            = 2;
    $data['arquivos']       = "/tmp";
    $data['data_sistema']   = Date('Y-m-d');
    $logs                   = array();
    $logs_erro              = array();
    $logs_cliente           = array();
    $erro                   = false;
    $fabrica_qtde_anexos    = 5;

    $login_fabrica          = 42;
 

    if (ENV == 'producao' ) {
        $data['dest']       = 'helpdesk@telecontrol.com.br';
    } else {
        $data['dest']       = 'lucas.carlos@telecontrol.com.br';
    }

    $sql = "SELECT contexto, registro_id as processo, mensagem, data_aviso, contato->>'name' as nome, contato->>'email' as email, contato->>'type' as tipo_alerta 
        from vw_eventos_dia 
       WHERE fabrica = $login_fabrica AND contexto IN(3,4)";
    $res = pg_query($con, $sql);   

    if(strlen(pg_last_error($con))>0){
        $msg_erro = pg_last_error($con);
    }

    for($i=0; $i<pg_num_rows($res); $i++){
        $data_aviso         = pg_fetch_result($res, $i, data_aviso);
        $mensagem           = utf8_decode(pg_fetch_result($res, $i, mensagem));
        $nome               = pg_fetch_result($res, $i, nome);
        $processo           = pg_fetch_result($res, $i, processo);
        $email              = pg_fetch_result($res, $i, email);
        $contexto           = pg_fetch_result($res, $i, contexto);
        
        if($contexto == 3){
            $titulo = "Lembrete de Audiencia";
            $tipo = "Audiência";
        }elseif($contexto == 4){
            $titulo = "Lembrete de Acordo/Sentenca";
            $tipo = "Acordo/Sentença";
        }

        $sql= " WITH dados_processo as (
            select tbl_processo.processo, tbl_motivo_processo.descricao as descricao_motivo_processo, tbl_posto.nome as nome_posto, tbl_posto_fabrica.codigo_posto,  tbl_processo.os, tbl_produto.descricao as descricao_produto, tbl_produto.referencia as referencia_produto, orgao, comarca,  valor_causa, numero_processo, data_sentenca, tbl_processo.data_input, tbl_processo.consumidor_nome
            from tbl_processo             
            left join tbl_os on tbl_os.os = tbl_processo.os and tbl_os.fabrica = $login_fabrica
            left join tbl_produto on tbl_produto.produto = tbl_processo.produto and tbl_produto.fabrica_i = $login_fabrica 
            left join tbl_posto on tbl_posto.posto = tbl_os.posto 
            left join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
            left join tbl_motivo_processo on tbl_motivo_processo.motivo_processo = tbl_processo.motivo_processo and tbl_motivo_processo.fabrica = $login_fabrica
            where processo = $processo
            
        ), dados_item as (
            select processo, data_audiencia1, data_audiencia2, data_acordo, tbl_processo_pedido_cliente.descricao as processo_pedido_cliente_descricao, tbl_proposta_acordo.descricao as proposta_acordo_descricao from tbl_processo_item 
            
            join tbl_processo_pedido_cliente on tbl_processo_pedido_cliente.processo_pedido_cliente = tbl_processo_item.processo_pedido_cliente

            join tbl_proposta_acordo on tbl_proposta_acordo.proposta_acordo = tbl_processo_item.proposta_acordo 

            where processo = $processo 

            and (data_audiencia1 > '$data_aviso' OR data_audiencia2 > '$data_aviso') 
                            order by tbl_processo_item.data_input limit 1 
        ) 

        select * from dados_processo join dados_item on dados_item.processo = dados_processo.processo  ";
        $resDados = pg_query($con, $sql);

        for($a=0; $a<pg_num_rows($resDados);$a++){
            $processo                   = pg_fetch_result($resDados, $a, 'processo');
            $descricao_motivo_processo  = pg_fetch_result($resDados, $a, 'descricao_motivo_processo');
            $nome_posto                 = pg_fetch_result($resDados, $a, 'nome_posto');
            $codigo_posto               = pg_fetch_result($resDados, $a, 'codigo_posto');
            $os                         = pg_fetch_result($resDados, $a, 'os');
            $descricao_produto          = pg_fetch_result($resDados, $a, 'descricao_produto');
            $referencia_produto         = pg_fetch_result($resDados, $a, 'referencia_produto');
            $orgao                      = pg_fetch_result($resDados, $a, 'orgao');
            $comarca                    = pg_fetch_result($resDados, $a, 'comarca');
            $valor_causa                = pg_fetch_result($resDados, $a, 'valor_causa');

            $valor_causa                = number_format($valor_causa, 2, '.', '');

            $consumidor_nome            = pg_fetch_result($resDados, $a, 'consumidor_nome');

            $numero_processo            = pg_fetch_result($resDados, $a, 'numero_processo');
            $data_sentenca              = mostra_data(pg_fetch_result($resDados, $a, data_sentenca));
            $data_audiencia1            = mostra_data(pg_fetch_result($resDados, $a, 'data_audiencia1'));
            $data_audiencia2            = mostra_data(pg_fetch_result($resDados, $a, 'data_audiencia2'));
            $data_acordo                = mostra_data(pg_fetch_result($resDados, $a, 'data_acordo'));
            $processo_pedido_cliente_descricao = pg_fetch_result($resDados, $a, 'processo_pedido_cliente_descricao');
            $proposta_acordo_descricao = pg_fetch_result($resDados, $a, 'proposta_acordo_descricao');

            $data_input         = substr(pg_fetch_result($resDados, $a, data_input),0,10);

        
            //parte de anexo
            list($ano,$mes,$dia) = explode("-",$data_input);
            if(!isset($s3)){
                $s3 = new AmazonTC('processos', (int)$login_fabrica);
            }

            $arr_anexos = array();

            for ($b = 0; $b < $fabrica_qtde_anexos; $b++) {
                $anexos = $s3->getObjectList("{$login_fabrica}_{$processo}_{$b}", false, $ano, $mes);
                $link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);
                system("wget -q -O ".$data['arquivos'].'/'.basename($anexos[0])." '$link' ");

                if(!empty(basename($anexos[0])))  { 
                    $arr_anexos[] = $data['arquivos'].'/'.basename($anexos[0]);
                }
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
    }
    
    if(strlen($msg_erro) >0 ){
        $msg = "Erro ao executar rotina 'Verifica audiência - Makita' ";

        Log::envia_email($data,Date('d/m/Y H:i:s')." - Makita - Erro na verifica audiência", $msg);
    }

?>
