<?php
/**
 *
 * bloqueia-postos.php
 *
 * Bloqueia Postos com OS abertas com risco de Procon Suggar
 *
 * @author  Ronald Santos
 * @version 2015.06.03
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // production Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require dirname(__FILE__) . '/../funcoes.php';

    $data_log['login_fabrica'] = 24;
    $data_log['dest'] = 'helpdesk@telecontrol.com.br';
    $data_log['log'] = 2;

    date_default_timezone_set('America/Sao_Paulo');
    $log[] = Date('d/m/Y H:i:s ')."Inicio do Programa";

	$login_fabrica = 24;
	
	$phpCron = new PHPCron($login_fabrica, __FILE__); 
	$phpCron->inicio();

	if (ENV == 'teste' ) {
        
        $data_log['dest'] = 'ronald.santos@telecontrol.com.br';

		$destinatarios_clientes = "ronald.santos@telecontrol.com.br";

    } else {

        $data_log['dest'] = 'helpdesk@telecontrol.com.br';

        $destinatarios_clientes = "helpdesk@telecontrol.com.br";
    }
    
    $res = pg_query($con,"BEGIN");

    $sql = "DROP TABLE IF EXISTS tmp_os_procon_suggar";
    $res = pg_query($con,$sql);

    if(strlen(pg_last_error($con)) > 0){
        #echo pg_last_error($con);
        $log_erro[] = "Erro ao apagar a tabela tmp_os_procon_suggar \n ".pg_last_error($con);
    }else{

        $sql = "CREATE TABLE tmp_os_procon_suggar(os integer, posto integer)";
        $res = pg_query($con,$sql);

        if(strlen(pg_last_error($con)) > 0){
            #echo pg_last_error($con);
            $log_erro[] = "Erro ao criar a tabela tmp_os_procon_suggar \n ".pg_last_error($con);
        }else{

            $sql = " INSERT INTO tmp_os_procon_suggar(os,posto)
            SELECT DISTINCT tbl_os.os,
            tbl_os.posto
            FROM tbl_os
            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
            LEFT JOIN tbl_os_produto using(os)
            WHERE tbl_os.fabrica     = $login_fabrica
            AND tbl_os.data_abertura >= '2015-01-01' AND data_digitacao > '2015-01-01 00:00:00'
            AND tbl_os.excluida IS NOT TRUE 
            AND (CURRENT_DATE - tbl_os.data_abertura) BETWEEN 16 and 24
            AND tbl_os.cancelada IS NOT TRUE 
            AND tbl_os.data_fechamento IS NULL
            AND coalesce(tbl_os_produto.os_produto,null) is null";
            $res = pg_query($con,$sql);

            if(strlen(pg_last_error($con)) > 0){
                #echo pg_last_error($con);
                $log_erro[] = "Erro ao inserir OS aberta a mais de 15 dias sem peça na tabela tmp_os_procon_suggar \n ".pg_last_error($con);
            }else{

                $sql = " INSERT INTO tmp_os_procon_suggar(os,posto)
                SELECT DISTINCT tbl_os.os ,
                tbl_os.posto
                FROM tbl_os
                JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_os.data_abertura <= CURRENT_DATE - INTERVAL '25 days'
                AND tbl_os.data_fechamento IS NULL
                AND tbl_os.data_abertura >= '2015-01-01' AND data_digitacao > '2015-01-01 00:00:00'
                AND tbl_os.cancelada IS NOT TRUE 
                AND tbl_os.excluida IS NOT TRUE";
                $res = pg_query($con,$sql);

                if(strlen(pg_last_error($con)) > 0){
                    #echo pg_last_error($con);
                    $log_erro[] = "Erro ao inserir OS aberta a mais de 25 dias na tabela tmp_os_procon_suggar \n ".pg_last_error($con);
                }else{
			
		    $sql = "SELECT distinct tbl_os_interacao.os, tbl_os.posto,(select case when admin notnull and current_date > data + interval '3 days' then 'sim' else 'nao' end as bloqueia from tbl_os_interacao h where h.os = tbl_os.os order by data desc limit 1) as bloqueia_os
			    INTO TEMP tmp_os_interacao_suggar
			    FROM tbl_os_interacao
			    JOIN tbl_os USING(os)
			    WHERE tbl_os_interacao.fabrica= $login_fabrica
			    AND tbl_os_interacao.admin IS NOT NULL
			    AND tbl_os_interacao.interno is false
			    AND tbl_os_interacao.exigir_resposta is true
			    AND tbl_os.excluida is not true
			    AND tbl_os_interacao.data > '2015-01-01 00:00:00'
					AND tbl_os_interacao.data < current_date - interval '3 days'
					AND tbl_os.fabrica = $login_fabrica
			    AND tbl_os.data_fechamento IS NULL ";
		    $res = pg_query($con,$sql);

            $res_dados = [];
            $res_dados = pg_fetch_all(pg_query($con, "SELECT * FROM tmp_os_interacao_suggar WHERE bloqueia_os = 'sim'"));

		    $sql = "INSERT INTO tmp_os_procon_suggar(os,posto)
			    SELECT os,posto FROM tmp_os_interacao_suggar WHERE bloqueia_os = 'sim'";
                    $res = pg_query($con,$sql);

                    if(strlen(pg_last_error($con)) > 0){
                        #echo pg_last_error($con);
                        $log_erro[] = "Erro ao inserir OS pendentes de interação na tabela tmp_os_procon_suggar \n ".pg_last_error($con);
                    }else{

                        $sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = TRUE 
                        FROM tmp_os_procon_suggar
                        WHERE tmp_os_procon_suggar.os = tbl_os_campo_extra.os
                        AND tbl_os_campo_extra.fabrica = $login_fabrica
                        AND os_bloqueada isnull";
                        $res = pg_query($con,$sql);

                        if(strlen(pg_last_error($con)) > 0){
                            #echo pg_last_error($con);
                            $log_erro[] = "Erro ao atualizar o campo  tbl_os_campo_extra.os_bloqueada \n ".pg_last_error($con);
                        }else{

                            $sql = "DELETE FROM tmp_os_procon_suggar USING tbl_os_campo_extra WHERE tmp_os_procon_suggar.os = tbl_os_campo_extra.os AND (tbl_os_campo_extra.os_bloqueada IS FALSE OR tbl_os_campo_extra.os_bloqueada IS TRUE)";
                            $res = pg_query($con,$sql);

                            if(strlen(pg_last_error($con)) > 0){
                                #echo pg_last_error($con);
                                $log_erro[] = "Erro ao excluir registros na tabela tmp_os_procon_suggar \n ".pg_last_error($con);
                            }else{

                                $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,os_bloqueada)
                                SELECT DISTINCT os,$login_fabrica,true FROM tmp_os_procon_suggar";
                                $res = pg_query($con,$sql);

                                if(strlen(pg_last_error($con)) > 0){
                                    #echo pg_last_error($con);
                                    $log_erro[] = "Erro ao inserir inserir registros na tabela tbl_os_campo_extra \n ".pg_last_error($con);
                                }else{

                                    $sql = "UPDATE tbl_posto_bloqueio SET desbloqueio = FALSE
                                    FROM tmp_os_procon_suggar
                                    WHERE tmp_os_procon_suggar.posto = tbl_posto_bloqueio.posto
                                    AND tbl_posto_bloqueio.fabrica = $login_fabrica
                                    AND tbl_posto_bloqueio.desbloqueio IS TRUE";
                                    $res = pg_query($con,$sql);

                                    if(strlen(pg_last_error($con)) > 0){
                                        #echo pg_last_error($con);
                                        $log_erro[] = "Erro ao atualizar o campo  tbl_posto_bloqueio.desbloqueio \n ".pg_last_error($con);
                                    }else{

                                        $sql = "INSERT INTO tbl_posto_bloqueio(fabrica,posto)
                                        SELECT DISTINCT $login_fabrica, tmp_os_procon_suggar.posto FROM tmp_os_procon_suggar
                                        LEFT JOIN tbl_posto_bloqueio ON tbl_posto_bloqueio.posto = tmp_os_procon_suggar.posto AND tbl_posto_bloqueio.fabrica = $login_fabrica
                                        WHERE tbl_posto_bloqueio.posto_bloqueio IS NULL";
                                        $res = pg_query($con,$sql);

                                        if(strlen(pg_last_error($con)) > 0){
                                            #echo pg_last_error($con);
                                            $log_erro[] = "Erro ao inserir inserir registros na tabela tbl_posto_bloqueio \n ".pg_last_error($con);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (count($res_dados) > 0) {
                        $array_postos = [];
                        foreach ($res_dados as $k => $val) {

                            $posto_id = $val['posto'];

                            if (!in_array($posto_id, $array_postos)) {
                                $sql_tem_bloqueio = " SELECT posto, os FROM tbl_posto_bloqueio WHERE posto = $posto_id AND fabrica = $login_fabrica LIMIT 1";
                                $res_tem_bloqueio = pg_query($con, $sql_tem_bloqueio);
                                if (pg_num_rows($res_tem_bloqueio) > 0) {
                                    if (pg_fetch_result($res_tem_bloqueio, 0, 'os') == "t") {
                                        $array_postos[] = $posto_id;
                                        continue;
                                    } else {
                                        $sql_atualiza = " UPDATE tbl_posto_bloqueio SET os = TRUE WHERE fabrica = $login_fabrica AND posto = $posto_id ";
                                    }
                                } else {
                                    $sql_atualiza = " INSERT INTO tbl_posto_bloqueio (fabrica, posto, os) VALUES ($login_fabrica, $posto_id, TRUE) ";
                                }

                                $res_atualiza = pg_query($con, $sql_atualiza);
                                if (pg_last_error()) {
                                    $log_erro[] = "Erro ao inserir/atualizar tbl_posto_bloqueio OS \n ".pg_last_error($con);
                                } else {
                                    $array_postos[] = $posto_id;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if(count($log_erro) > 0){
        $res = pg_query($con,"ROLLBACK");

        $header  = "MIME-Version: 1.0\n";
        $header .= "Content-type: text/html; charset=iso-8859-1\n";
        $header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

        mail("ronald.santos@telecontrol.com.br", "TELECONTROL / SUGGAR ({$data}) - BLOQUEIA POSTO", implode("<br />", $log_erro), $header);

        $fp = fopen("/tmp/suggar/bloqueia_posto.err","w");
        fwrite($fp,implode("<br />", $log_erro));
        fclose($fp);
    }else{
        $res = pg_query($con,"COMMIT");
    }

    $phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();
    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar bloqueio de postos", $msg);
}
