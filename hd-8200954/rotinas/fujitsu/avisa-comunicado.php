<?php
/**
 *
 * avisa-comunicado.php
 *
 * Avisa posto autorizado sobre data treinamento
 *
 * @author Guilherme Monteiro
 * @version 2016.07.21
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao / teste

try{

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    if(ENV == 'producao'){
        $email  = 'guilherme.monteiro@telecontrol.com.br';
    }else{
        $email  = 'guilherme.monteiro@telecontrol.com.br';
    }

    $fabrica = "138";

    /* Inicio Processo */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /* Log */
    $log = new Log2();
    $log->adicionaLog(array("titulo" => "Log erro Geração de Envia Comunicado")); // Titulo

    $log->adicionaEmail("marisa.silvana@telecontrol.com.br");

    /* Pesquisa os treinamentos */

    $sql = "SELECT DISTINCT tbl_treinamento.treinamento,
                    tbl_treinamento.titulo,
                    tbl_treinamento.descricao,
                    TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')   AS data_inicio,
                    TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')      AS data_fim,
                    tbl_linha.nome                                      AS linha_nome,
                    tbl_familia.descricao                               AS familia_descricao,
                    tbl_treinamento.data_inicio - CURRENT_DATE             AS qtde_dias_falta,
                    tbl_posto.nome,
                    tbl_posto.posto,
                    tbl_posto_fabrica.contato_email
                FROM tbl_treinamento
                JOIN tbl_linha   USING(linha)
                JOIN tbl_treinamento_posto ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
                JOIN tbl_posto ON tbl_posto.posto = tbl_treinamento_posto.posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $fabrica
                LEFT JOIN tbl_familia USING(familia)
                /*LEFT JOIN tbl_tecnico ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico*/
                WHERE tbl_treinamento.fabrica = $fabrica
                AND tbl_treinamento.ativo IS TRUE
                AND tbl_treinamento.data_inicio - CURRENT_DATE BETWEEN 0 and 7
		
                /*ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo*/";
    $res = pg_query ($con,$sql);
    $msg_erro .= pg_last_error($con);
    if(strlen($msg_erro) > 0){
        $log->adicionaLog("Erro na consulta de treinamento/posto. arquivo => rotinas/fujitsu/avisa-comunicado.php");
        $log->adicionaLog("linha");
    }
    if(strlen($msg_erro) == 0){
        if(pg_num_rows($res) > 0){
            $rows = pg_num_rows($res);
            for ($i=0; $i < $rows; $i++) {
                $treinamento        = pg_fetch_result($res, $i, 'treinamento');
                $titulo             = pg_fetch_result($res, $i, 'titulo');
                $descricao          = pg_fetch_result($res, $i, 'descricao');
                $data_inicio        = pg_fetch_result($res, $i, 'data_inicio');
                $data_fim           = pg_fetch_result($res, $i, 'data_fim');
                $linha_nome         = pg_fetch_result($res, $i, 'linha_nome');
                $familia_descricao  = pg_fetch_result($res, $i, 'familia_descricao');
                $qtde_dias_falta  = pg_fetch_result($res, $i, 'qtde_dias_falta');
                $nome  = pg_fetch_result($res, $i, 'nome');
                $id_posto = pg_fetch_result($res, $i, 'posto');
                $contato_email  = pg_fetch_result($res, $i, 'contato_email');

                $sql_comunicado = "SELECT comunicado
                                    FROM tbl_comunicado
                                    WHERE posto = $id_posto
                                    AND fabrica = $fabrica
                                    AND descricao ILIKE '%AVISO DE TREINAMENTO%' ";
                $res_comunicado = pg_query($con, $sql_comunicado);
                $msg_erro .= pg_last_error($con);
                if(strlen($msg_erro) > 0){
                    $log->adicionaLog("Erro ao buscar comunicado para o posto: $nome . arquivo => rotinas/fujitsu/avisa-comunicado.php");
                    $log->adicionaLog("linha");
                    $msg_erro .= pg_last_error($con);
                }

                if(strlen($msg_erro) == 0){
                    if(pg_num_rows($res_comunicado) == 0){
                        $sql_insert = "INSERT INTO tbl_comunicado(
                                            descricao,
                                            tipo,
                                            fabrica,
                                            mensagem,
                                            ativo,
                                            posto,
                                            obrigatorio_site
                                        )VALUES(
                                            'Comunicado de Treinamento',
                                            'Comunicado Inicial',
                                            $fabrica,
                                            'ATENÇÃO AVISO DE TREINAMENTO: faltam $qtde_dias_falta dias para o treinamento: $titulo',
                                            true,
                                            $id_posto,
                                            true
                                        );";
                        $res_insert = pg_query($con, $sql_insert);
                        $msg_erro .= pg_last_error($con);
                        if(strlen($msg_erro) > 0){
                            $log->adicionaLog("Erro ao enviar comunicado para o posto: $nome . arquivo => rotinas/fujitsu/avisa-comunicado.php");
                            $log->adicionaLog("linha");
                            $msg_erro .= pg_last_error($con);
                        }

                        if(strlen($msg_erro) == 0){
                            if(strlen($contato_email) > 0){
                                $email_origem  = "helpdesk@telecontrol.com.br";
                                $email_destino = "$contato_email";
                                $assunto       = "TREINAMENTO: $titulo";

                                $corpo.= "Titulo: $titulo <br>\n";
                                $corpo.= "Data Inicío: $data_inicio<br> \n";
                                $corpo.= "Data Término: $data_fim <p>\n";

                                $corpo.="<br><strong>ATENÇÃO</strong>\n\n";

                                $corpo.="<br>Faltam $qtde_dias_falta dias para o treinamento: $titulo\n\n";

                                $corpo.="<br><br><br>Telecontrol\n";
                                $corpo.="<br>www.telecontrol.com.br\n";
                                $corpo.="<br>_______________________________________________\n";
                                $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

                                $body_top = "MIME-Version: 1.0\r\n";
                                $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
                                $body_top .= "From: $email_origem\r\n";

                                if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top ) ){
                                    $msg = "$email";
                                }else{
                                    $log->adicionaLog("Não foi possível enviar o email para o posto: $nome");
                                    $log->adicionaLog("linha");
                                    $msg_erro .= "Não foi possível enviar o email.";
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if(strlen($msg_erro) > 0){
        if($log->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          echo $log->enviaEmails();
        }
    }
    $phpCron->termino();
} catch (Exception $e) {
        echo $e->getMessage();
}
