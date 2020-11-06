<?php

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','dev');  // producao Alterar para produção ou algo assim

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

$fabrica = 1;
$qtde_corte = 250;

$arquivos = "/tmp";
$nomeArquivo = "$arquivos/blackedecker/gera-pedido-os.err";

//$origem = "/www/assist/www";
$origem             = "../..";
$arquivo_bloqueia   = "$origem/bloqueia_pedido_black.txt";

if(file_exists($arquivo_bloqueia)){
    $linha = file_get_contents($arquivo_bloqueia);
}
$i=0;
list($data_inicio[$i],$data_fim[$i],$data_envio[$i],$comentario) = explode(";;",$linha);

$sql = "SELECT (current_timestamp >= '$data_inicio[0]') AS inicio ,
                (current_timestamp <= '$data_envio[0]') AS fim ;";
$result = pg_query($con, $sql);

if(pg_num_rows($result)>0){
    $valida_inicio  = pg_fetch_result($result,0, inicio);
    $valida_envio   = pg_fetch_result($result,0, fim);
}

if($valida_inicio == "t" and $valida_envio == "t"){

    #DELETA ARQUIVO PARA BLOQUEIO DOS PEDIDOS DA BLACK $ DECKER
    $arquivonoticia = fopen("/tmp/blackedecker/cron-bloqueado.telecontrol", "w+");# Abrir/criar arquivo para escrita
    fclose($arquivonoticia);

    $arquivo_email = fopen("/www/cgi-bin/blackedecker/email-bloqueio.txt", "w+");

    $dadosEmail = "MIME-Version: 1.0\n";
    $dadosEmail =  "Content-type: text/html; charset=iso-8859-1\n";
    $dadosEmail = "From: Telecontrol <telecontrol\@telecontrol.com.br>\n";
    $dadosEmail = "To: helpdesk\@telecontrol.com.br \n";
    $dadosEmail = "Subject: BLACK - Foi bloqueado a geração de pedidos\n";
    $dadosEmail = "<font face='arial' color='#000000' size='2'>\n";
    $dadosEmail = "EMAIL AUTOMATICO - URGENTE - Foi bloqueada a geração dos pedidos da Black por causa do horário agendado pela rubia, ver arquivos: gera-pedido-os.pl(cgi-bin) e pedido_bloquea.php(admin)\n";
    $dadosEmail = "<br><br>\n";
    $dadosEmail = "<br>\n";
    $dadosEmail = "</font>\n";
    $escreve = fwrite($arquivo_email, "$dadosEmail");
    fclose($arquivo_email);

    system ("cat /tmp/blackedecker/cron-bloqueado.telecontrol >> /www/cgi-bin/blackedecker/email-bloqueio.txt ; cat /www/cgi-bin/blackedecker/email-bloqueio.txt | qmail-inject");

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

}else{

  unlink ("/tmp/blackedecker/cron-bloqueado.telecontrol");

$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();
$data['dest'] = 'helpdesk@telecontrol.com.br';
if (ENV != 'producao') {
    $data['dest'] = `git config --get user.email` ? : $data['dest'];
}

extract($data);

$sql = "SELECT  tbl_posto.posto,
                tbl_os_item.peca,
                tbl_os_item.os_item,
                tbl_os_item.qtde
   INTO TEMP    black_gera_pedido_os
        FROM    tbl_os_item
        JOIN    tbl_os_produto      ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
        JOIN    tbl_os              ON  tbl_os.os                 = tbl_os_produto.os
        JOIN    tbl_posto           ON  tbl_posto.posto           = tbl_os.posto
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica = $fabrica
		LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os 
        WHERE   tbl_os_item.servico_realizado IN (62)
        AND     tbl_os_item.pedido            IS NULL
        AND     tbl_os.validada               IS NOT NULL
        AND     tbl_os.fabrica                = $fabrica
		AND     tbl_os_item.fabrica_i         = $fabrica
		AND		(tbl_auditoria_os.os isnull or tbl_auditoria_os.liberada notnull)
        AND     tbl_os.posto                  <> 6359;

        SELECT DISTINCT posto FROM black_gera_pedido_os";

    $result = pg_query($con, $sql);


    if (strlen(pg_last_error($con))>0) {
        $msg_erro = pg_last_error($con);
    }

    for($i = 0; $i<pg_num_rows($result); $i++){
        $posto = pg_fetch_result($result, $i, posto);
        $erro = 0;
        $multiplo = 0;
        $qtdePedidos = 0;

        $sql = "BEGIN TRANSACTION";
        $resultX = pg_query($con, $sql);

        $sql = " SELECT  count(1)
                FROM  black_gera_pedido_os
                WHERE posto       =  $posto
                GROUP BY peca";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) >0 ){
            $count = pg_num_rows($res);
        }
        if (strlen(pg_last_error($con))> 0) {
            $erro = 1;
            $msg_erro .= pg_last_error($con)."1 - $sql\n";
        }

        $qtdePedidos = ceil($count / $qtde_corte);

        for($b=0; $b<$qtdePedidos; $b++){

            $sql = "INSERT INTO tbl_pedido (
                        posto          ,
                        fabrica        ,
                        tabela         ,
                        condicao       ,
                        tipo_pedido    ,
                        unificar_pedido,
                        pedido_os
                    ) VALUES (
                        $posto  ,
                        $fabrica,
                        109     ,
                        62      ,
                        87      ,
                        't'     ,
                        't'
                    )RETURNING pedido ";
            $resultX = pg_query($con, $sql);

            if (strlen(pg_last_error($con))>0) {
                $erro = 1;
                $msg_erro .= pg_last_error($con) ."2 - $sql\n";
            }else{
                $pedido = pg_result($resultX,0,0);
            }

            $sql = "SELECT
                        os_item,
                        peca,
                        qtde
                    FROM  black_gera_pedido_os
                    WHERE posto       =  $posto
                    OFFSET $multiplo LIMIT $qtde_corte";
            $result2 = pg_query($con, $sql);

            if (strlen(pg_last_error($con))> 0) {
                $erro = 1;
                $msg_erro .= pg_last_error($con)."1 - $sql\n";
            }

            for($a=0; $a<pg_num_rows($result2); $a++) {
                $peca = pg_fetch_result($result2, $a, peca);
                $qtde = pg_fetch_result($result2, $a, qtde);
                $os_item = pg_fetch_result($result2, $a, 'os_item');

                $sql = "INSERT INTO tbl_pedido_item (
                            pedido,
                            peca  ,
                            qtde
                        ) VALUES (
                            $pedido,
                            $peca  ,
                            $qtde
                        ) RETURNING pedido_item";

                $resultX = pg_query($con, $sql);

                if (strlen(pg_last_error($con))>0) {
                    $erro = 1;
                    $msg_erro .= pg_last_error($con) ."2 - $sql\n";
                }else{
                    $pedido_item = pg_fetch_result($resultX, 0, 0);
                    $sql_function = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$fabrica)";
                    pg_query($con, $sql_function);
                }
            }

            $sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
            $resultX = pg_query($con, $sql);
            if (strlen(pg_last_error($con)) > 0) {
                $erro = 1;
                $msg_erro .= pg_last_error($con) ."5 - $sql\n";
            }

            if(strlen(trim($msg_erro))==0 ){
                /*$sql = "SELECT fn_atualiza_os_item_pedido(os_item, $pedido, $fabrica)
                            FROM   black_gera_pedido_os
                            WHERE  posto   = $posto ";
                $resultX = pg_query($con, $sql);

                if (strlen(pg_last_error($con)) > 0) {
                    $erro = 1;
                    $msg_erro .= pg_last_error($con) ."6 - $sql\n";
                }*/
            }

            $multiplo += $qtde_corte;
		}
		if(strlen(trim($msg_erro))>0 ){
			$resultX = pg_query($con,"ROLLBACK TRANSACTION");
		}else{
			$sql = "COMMIT TRANSACTION";
			$resultX = pg_query($con, $sql);
		}

    }

    if(strlen(trim($msg_erro))>0 ){

        $arquivo = fopen("$nomeArquivo", "w+");
        $escreve = fwrite($arquivo, "$msg_erro");
        fclose($arquivo);

        $msg = "Erro na rotina de gera pedido Black&Decker\r\n<br />Descrição do erro: " . $msg_erro ."<hr /><br /><br />";
        Log::envia_email($data,Date('d/m/Y H:i:s')." -  Black&Decker - Erro na geração de pedido(gera_pedido.php)", $msg);
    }
}

$phpCron->termino();
?>
