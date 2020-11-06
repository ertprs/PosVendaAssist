<?php
/**
 *
 * importa-encontro-contas.php
 *
 * Importação de encontro de contas
 *
 * @author  Ronald Santos
 * @version 2014.06.17
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 		= 30;
    $data['fabrica'] 	= 'esmaltec';
    $data['arquivo_log'] 	= 'retorno-pagamento';
	$data['tipo'] 	= 'importa-encontro-contas';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

    if (ENV == 'producao' ) {
	    $data['dest'] 		= 'helpdesk@telecontrol.com.br';
	    $data['dest_cliente']  	= 'helpdesk@telecontrol.com.br';
	    $data['origem']		= "/home/thermosystem/thermosystem-telecontrol/";
    } else {
	    $data['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'ronald.santos@telecontrol.com.br';
	    #$data['origem']		= '/www/cgi-bin/thermosystem/entrada/';
        $data['origem']     = '/home/ronald/perl/esmaltec/entrada/';
    }
    
    $data['file']       = 'telecontrol-retorno-pagamento.txt';
    $data['file2']      = 'telecontrol-retorno-pagamento-lines.txt';

    extract($data);
	
	define('APP', 'Importa Encontro de Contas - '.$fabrica);

    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 0777 {$arquivos}/{$fabrica}/" ); 
    
    if(file_exists($origem.$file)){

        $sql = "DROP TABLE IF EXISTS esmaltec_pagamento;";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "CREATE TABLE esmaltec_pagamento (
                  txt_id_encontro    text,
                  txt_nota_fiscal    text,
                  txt_cpf_cnpj       text,
                  txt_extrato        text,
                  txt_num_oc         text,
                  txt_data_pagamento text,
                  txt_saldo_debito   text,
                  txt_tipo_registro  text
              )";
      $res = pg_query($con,$sql);
      $msg_erro .= pg_errormessage($con);

      $fp = fopen($origem.$file,"r");

      while(!feof($fp)){

        $linha = fgets($fp,4096);
        list($txt_id_encontro,$txt_nota_fiscal,$txt_cpf_cnpj,$txt_extrato,$txt_num_oc,$txt_data_pagamento,$txt_saldo_debito,$txt_tipo_registro) = explode(";",$linha);

        $sql = "INSERT INTO esmaltec_pagamento(
                                                txt_id_encontro,
                                                txt_nota_fiscal,
                                                txt_cpf_cnpj,
                                                txt_extrato,
                                                txt_num_oc,
                                                txt_data_pagamento,
                                                txt_saldo_debito,
                                                txt_tipo_registro
                                              ) VALUES(
                                                '$txt_id_encontro',
                                                '$txt_nota_fiscal',
                                                '$txt_cpf_cnpj',
                                                '$txt_extrato',
                                                '$txt_num_oc',
                                                '$txt_data_pagamento',
                                                '$txt_saldo_debito',
                                                '$txt_tipo_registro'
                                              )";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

      }
      fclose($fp);

        $sql = "UPDATE esmaltec_pagamento SET 
                    txt_id_encontro     = TRIM(txt_id_encontro),
                    txt_nota_fiscal     = TRIM(txt_nota_fiscal),
                    txt_cpf_cnpj        = TRIM(txt_cpf_cnpj),
                    txt_extrato         = TRIM(txt_extrato),
                    txt_num_oc          = TRIM(txt_num_oc),
                    txt_data_pagamento  = TRIM(txt_data_pagamento),
                    txt_saldo_debito    = TRIM(txt_saldo_debito),
                    txt_tipo_registro   = TRIM(txt_tipo_registro)
                    ";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE esmaltec_pagamento SET
                    txt_saldo_debito = REPLACE((REPLACE(txt_saldo_debito,'.','')),',','.')";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE esmaltec_pagamento ADD COLUMN extrato INT4";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE esmaltec_pagamento ADD COLUMN posto INT4";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE esmaltec_pagamento SET
                    extrato = tbl_extrato.extrato,
                    posto = tbl_extrato.posto
                FROM tbl_extrato
                WHERE tbl_extrato.extrato = esmaltec_pagamento.txt_extrato::numeric 
                AND tbl_extrato.fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "DELETE FROM esmaltec_pagamento WHERE extrato isnull";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
      
      if(file_exists($origem.$file2)){

            $sql = "DROP TABLE IF EXISTS esmaltec_pagamento_item;";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "CREATE TABLE esmaltec_pagamento_item (
                  txt_id_encontro    text,
                  txt_duplicata      text,
                  txt_data_vencimento text,
                  txt_desconto       text,
                  txt_valor          text,
                  txt_tipo_registro  text
              )";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $fp = fopen($origem.$file2,"r");

          while(!feof($fp)){

            $linha = fgets($fp,4096);

            list($txt_id_encontro,$txt_duplicata,$txt_data_vencimento,$txt_desconto,$txt_valor,$txt_tipo_registro) = explode(";",$linha);

            $sql = "INSERT INTO esmaltec_pagamento_item(
                                                    txt_id_encontro,
                                                    txt_duplicata,
                                                    txt_data_vencimento,
                                                    txt_desconto,
                                                    txt_valor,
                                                    txt_tipo_registro
                                                  ) VALUES(
                                                    '$txt_id_encontro',
                                                    '$txt_duplicata',
                                                    '$txt_data_vencimento',
                                                    '$txt_desconto',
                                                    '$txt_valor',
                                                    '".trim($txt_tipo_registro)."'
                                                  )"; 
            $res = pg_query($con,$sql);

          }
          fclose($fp);

            $sql = "UPDATE esmaltec_pagamento_item SET 
                    txt_id_encontro     = TRIM(txt_id_encontro),
                    txt_duplicata       = TRIM(txt_duplicata),
                    txt_data_vencimento = TRIM(txt_data_vencimento),
                    txt_desconto        = TRIM(txt_desconto),
                    txt_valor           = TRIM(txt_valor),
                    txt_tipo_registro   = TRIM(txt_tipo_registro)
                    ";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "UPDATE esmaltec_pagamento_item SET
                        txt_valor = REPLACE((REPLACE(txt_valor,'.','')),',','.'),
                        txt_desconto = REPLACE((REPLACE(txt_desconto,'.','')),',','.')";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE esmaltec_pagamento_item ADD COLUMN extrato INT4";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE esmaltec_pagamento_item ADD COLUMN posto INT4";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE esmaltec_pagamento_item ADD COLUMN num_oc INT4";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE esmaltec_pagamento_item ADD COLUMN saldo_debito numeric";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE esmaltec_pagamento_item ADD COLUMN data_pagamento date";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "ALTER TABLE esmaltec_pagamento_item ADD COLUMN nota_fiscal text";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "UPDATE esmaltec_pagamento_item SET
                        extrato = esmaltec_pagamento.extrato,
                        posto   = esmaltec_pagamento.posto,
                        num_oc  = esmaltec_pagamento.txt_num_oc::numeric,
                        nota_fiscal = esmaltec_pagamento.txt_nota_fiscal,
                        saldo_debito = esmaltec_pagamento.txt_saldo_debito::numeric,
                        data_pagamento = esmaltec_pagamento.txt_data_pagamento::date
                    FROM esmaltec_pagamento
                    WHERE esmaltec_pagamento.txt_id_encontro = esmaltec_pagamento_item.txt_id_encontro";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "DELETE FROM esmaltec_pagamento_item WHERE extrato isnull";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            if(empty($msg_erro)){
                $sql = "INSERT INTO tbl_encontro_contas(
                                                        fabrica,
                                                        posto,
                                                        extrato,
                                                        nf_numero_nf,
                                                        nf_valor_do_encontro_contas,
                                                        posto_data_transacao,
                                                        encontro_titulo_a_pagar,
                                                        encontro_valor_liquido,
                                                        encontro_parcela,
                                                        encontro_especie,
                                                        encontro_serie,
                                                        posto_valor_do_encontro_contas
                                                        ) SELECT    $login_fabrica,
                                                                    posto,
                                                                    extrato,
                                                                    nota_fiscal,
                                                                    saldo_debito,
                                                                    data_pagamento,
                                                                    txt_duplicata,
                                                                    txt_valor::numeric,
                                                                    txt_data_vencimento,
                                                                    txt_tipo_registro,
                                                                    num_oc,
                                                                    txt_desconto
                                                        FROM esmaltec_pagamento_item WHERE extrato notnull";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }

            if(empty($msg_erro)){
                $fp = fopen($origem."confirma-retorno-pagamento.txt","w");

                $sql = "SELECT  txt_id_encontro,
                                extrato
                        FROM esmaltec_pagamento_item WHERE extrato notnull";
                $res = pg_query($con,$sql);

                for($x = 0; $x < pg_num_rows($res); $x++){
                    fwrite($fp, pg_fetch_result($res, $x, 'txt_id_encontro').";");
                    fwrite($fp, pg_fetch_result($res, $x, 'extrato')."\n");
                }
                fclose($fp);
            }else{
                $fp = fopen($arquivo_err,"w");
                fwrite($p, $msg_erro);
                fclose($fp);
            }

            #system ("mv ".$origem.$file." /tmp/".$fabrica."/telecontrol-retorno-pagamento-$data_sistema.txt");
            #system ("mv ".$origem.$file2." /tmp/".$fabrica."/telecontrol-retorno-pagamento-lines-$data_sistema.txt");

      }

    }
    exit;
}catch(Exception $e){

}

