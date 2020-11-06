<?php

include 'connect-ftp.php';
include_once dirname(__FILE__) . '/../../dbconfig.php';

include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';

require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

$fabrica_nome = "roca";
$login_fabrica = 178;
$manda_email = false;

define('APP', 'Cancela Pedido - '.$fabrica_nome);

try {

    $oFabrica = new \Posvenda\Fabrica($login_fabrica);
    $oPedido = new \Posvenda\Pedido($login_fabrica);

    function formataValor($valor){
        $novoValor = str_replace(".", "", $valor);
        $novoValor = str_replace(",", ".", $novoValor);
        return $novoValor;
    }

    $vet['fabrica'] = 'roca';
    $vet['tipo']    = 'cancela-pedido';
    $vet['dest']    = array('maicon.luiz@telecontrol.com.br');
    $vet['log']     = 1;
    
    $log_erro = array();
    $log_dir = '/tmp/' . $fabrica_nome . '/logs';

    $pasta = "/tmp/roca/ftp-pasta-in/saida";
    $nome = "pedidos-cancelados-".date("Y.m.d_H:i").".txt";
    $arquivo = "{$pasta}/{$nome}";
    
    if (!is_dir($pasta)) {
        if (!mkdir($pasta, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
        }
    }
    
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
        }
    }

    $arquivos = ftp_nlist($conn_id, "in/PDC*");

    if (count($arquivos) > 0) {
        $manda_email = true;

        $header = "pedido|peca|arquivo|status\r\n";
        $fp = fopen($arquivo,"w");
        fwrite($fp, $header);

        foreach($arquivos as $arquivoCancela) {
            $arquivoRemoto = "/in/".$arquivoCancela;
            ftp_get($conn_id, $pasta."/".$arquivoCancela, $arquivoRemoto, FTP_BINARY);
            $dados = fopen($pasta."/".$arquivoCancela, "r");

            while (!feof($dados)) {
                $linha = fgets($dados);
                $linha = explode("|", $linha);
                $pedido = (int) $linha[0];
                $referencia = $linha[1];
                $postoCodigo = $linha[2];
                $motivo = utf8_decode($linha[3]);
                $qtdeCancelar = null;

                if (!empty($pedido)) {
                    try {
                        # Layout txt (pedido|peça|cnpjPosto|motivo)
                        $sqlPedido = "
                            SELECT
                                pedido,
                                pedido_item,
                                qtde,
                                COALESCE(qtde_faturada, 0) AS qtde_faturada,
                                COALESCE(qtde_cancelada, 0) AS qtde_cancelada
                            FROM tbl_pedido
                            JOIN tbl_pedido_item USING(pedido)
                            JOIN tbl_peca USING(peca,fabrica)
                            WHERE fabrica = {$login_fabrica}
                            AND pedido = {$pedido}
                            AND referencia = '{$referencia}';
                        ";
                        $resPedido = pg_query($con, $sqlPedido);

                        pg_query($con, "BEGIN;");

                        if (pg_num_rows($resPedido) > 0) {
			    $itemCancelado = false;
			    for ($i = 0; $i < pg_num_rows($resPedido); $i++) {

				if ($itemCancelado === true) {
				    continue;
				}

                            	$pedidoItem     = pg_fetch_result($resPedido, $i, 'pedido_item');
                            	$qtdePedido     = pg_fetch_result($resPedido, $i, 'qtde');
                            	$qtdeFaturada   = pg_fetch_result($resPedido, $i, 'qtde_faturada');
                            	$qtdeCancelada  = pg_fetch_result($resPedido, $i, 'qtde_cancelada');

                            	if ($qtdePedido > ($qtdeCancelada + $qtdeFaturada)) {
                                    $oPedido->cancelaItemPedido($pedido, $pedidoItem, $motivo);
				                    $itemCancelado = true;
                            	} else {
                                    throw new Exception("Pedido {$pedido} e Item {$referencia} sem quantidade disponível para cancelamento");
                            	}
			    }
                        } else {
                            throw new Exception("Pedido {$pedido} e Item {$referencia} não encontrado para cancelamento");
                        }
			
                        pg_query($con, "COMMIT;");
                        if ($itemCancelado === true) {
			    $body = "{$pedido}|{$referencia}|{$arquivoCancela}|Sucesso\r\n";
                            fwrite($fp, $body);
			}

                    } catch (Exception $e) {
                        pg_query($con, "ROLLBACK;");
                        $body = "{$pedido}|{$referencia}|{$arquivoCancela}|{$e->getMessage()}\r\n";
                        fwrite($fp, $body);
                    }
                }
            }

            if (ftp_get($conn_id, $pasta."/".$arquivoCancela, "in/".$arquivoCancela, FTP_BINARY)) {
                ftp_delete($conn_id, "in/".$arquivoCancela);
                ftp_chmod($conn_id, 0777, "in/bkp");
                ftp_put($conn_id, "in/bkp/".$arquivoCancela, $pasta."/".$arquivoCancela, FTP_BINARY);
                unlink($pasta."/".$arquivoCancela);
            }

        }
        fclose($fp);
    }

    if ($manda_email === true) {
        if (is_file($arquivo)) {
            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Cancelamento de pedidos ') . date('d/m/Y H:i');
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            $mail->AddAddress('maicon.luiz@telecontrol.com.br');
            $mail->AddAddress('flavio.zequin@telecontrol.com.br');
            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo arquivo de log da rotina.<br/><br/>";
            $mail->AddAttachment($arquivo, $nome);
        
            if (!$mail->Send()) {
                echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
            } else {
                unlink($arquivo);
            }
        }
    }
    ftp_close($conn_id);
} catch (Exception $e) {
    ftp_close($conn_id);
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	echo $msg;
    Log::envia_email($vet, APP, $msg);
}

