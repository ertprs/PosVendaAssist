<?php

error_reporting(E_ALL ^ E_NOTICE);

$fabrica_nome = "dleletronicos";

define('APP', 'Exporta OS - '.$fabrica_nome);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $login_fabrica = 127;
    $vet['fabrica'] = 'dleletronicos';
    $vet['tipo']    = 'exporta-os';
    $vet['dest']    = array('ronald.santos@telecontrol.com.br');
    $vet['log']     = 1;

    $dir = "/tmp/$fabrica_nome/";
    //$dir = "entrada/";

    $phpCron = new PHPCron($login_fabrica, __FILE__); 
    $phpCron->inicio();
    
    $data    = date("d-m-Y-H-i");

    $log_erro  = array();

    $sql = "SELECT tbl_os.os,
                    tbl_os.sua_os,
                    tbl_os.serie,
                    tbl_os.consumidor_nome,
                    tbl_os.consumidor_cpf,
                    tbl_os.consumidor_cep,
                    tbl_os.consumidor_fone,
                    tbl_os.consumidor_endereco,
                    tbl_os.consumidor_numero,
                    tbl_os.consumidor_complemento,
                    tbl_os.consumidor_bairro,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_estado,
                    tbl_os.revenda_nome,
                    tbl_os.revenda_cnpj,
                    tbl_os.cortesia,
                    tbl_os_extra.orientacao_sac,
                    tbl_os.data_nf,
                    tbl_os.nota_fiscal,
                    tbl_os.data_abertura,
                    tbl_os.defeito_reclamado_descricao,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_produto.referencia,
                    tbl_produto.descricao,
                    tbl_os.aparencia_produto,
                    tbl_os.acessorios,
                    tbl_os.consumidor_revenda,
                    tbl_os_campo_extra.campos_adicionais
            FROM tbl_os
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
            JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
            LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_os.exportado IS NULL
            AND tbl_tipo_posto.posto_interno IS TRUE";   
    $res      = pg_query($con, $sql);
    $numrows  = pg_num_rows($res);
    $msg_erro = pg_errormessage($con);

    if (!empty($msg_erro)) {
        $log_erro[] = $msg_erro;
    }

    if (count($log_erro) == 0) {
        
        if (!is_dir($dir)) {

            if (!mkdir($dir)) {
                $log_erro[] ='Erro ao criar diretório do fabricante.';
            }

            if (!chmod($dir, 0777)) {
                $log_erro[] ='Erro ao dar permissão ao diretório';
            }

        }

        $file = $dir.'/os.txt';
        $fp   = fopen($file, 'w');

        if (!is_resource($fp)) {
            $log_erro[] ='Erro ao criar arquivo de exportação';
        }

    if($numrows > 0 && count($log_erro) == 0){
        for ($i = 0; $i < $numrows; $i++) {

            $os                           = pg_fetch_result($res,$i,'os');
            $sua_os                       = pg_fetch_result($res,$i,'sua_os');
            $serie                        = pg_fetch_result($res,$i,'serie');
            $consumidor_nome              = pg_fetch_result($res,$i,'consumidor_nome');
            $consumidor_cpf               = pg_fetch_result($res,$i,'consumidor_cpf');
            $consumidor_cep               = pg_fetch_result($res,$i,'consumidor_cep');
            $consumidor_fone              = pg_fetch_result($res,$i,'consumidor_fone');
            $consumidor_endereco          = pg_fetch_result($res,$i,'consumidor_endereco');
            $consumidor_numero            = pg_fetch_result($res,$i,'consumidor_numero');
            $consumidor_complemento       = pg_fetch_result($res,$i,'consumidor_complemento');
            $consumidor_bairro            = pg_fetch_result($res,$i,'consumidor_bairro');
            $consumidor_cidade            = pg_fetch_result($res,$i,'consumidor_cidade');
            $consumidor_estado            = pg_fetch_result($res,$i,'consumidor_estado');
            $consumidor_email             = pg_fetch_result($res,$i,'consumidor_email');
            $revenda_nome                 = pg_fetch_result($res,$i,'revenda_nome');
            $revenda_cnpj                 = pg_fetch_result($res,$i,'revenda_cnpj');
            $cortesia                     = pg_fetch_result($res,$i,'cortesia');
            $orientacao_sac               = pg_fetch_result($res,$i,'orientacao_sac');
            $data_nf                      = pg_fetch_result($res,$i,'data_nf');
            $nota_fiscal                  = pg_fetch_result($res,$i,'nota_fiscal');
            $data_abertura                = pg_fetch_result($res,$i,'data_abertura');
            $defeito_reclamado_descricao  = pg_fetch_result($res,$i,'defeito_reclamado_descricao');
            $codigo_posto                 = pg_fetch_result($res,$i,'codigo_posto');
            $nome_posto                   = pg_fetch_result($res,$i,'nome');
            $referencia_produto           = pg_fetch_result($res,$i,'referencia');
            $descricao_produto            = pg_fetch_result($res,$i,'descricao');
            $aparencia_produto            = pg_fetch_result($res,$i,'aparencia_produto');
            $acessorios                   = pg_fetch_result($res,$i,'acessorios');
            $consumidor_revenda           = pg_fetch_result($res,$i,'consumidor_revenda');
            $campos_adicionais            = json_decode(pg_fetch_result($res,$i,'campos_adicionais'),true);

            $enviar_os = "";

            if(count($campos_adicionais) > 0){
              foreach ($campos_adicionais as $key => $value) {
                $$key = $value;
              }
            }

            /**
             *
             *  Layout:
             *
             *  CODIGO_POSTO;NOME_POSTO;DATA_ABERTURA;REFERENCIA_PRODUTO;DESCRICAO_PRODUTO;SN;DEFEITO_RECLAMADO;NOME_CONSUMIDOR;CPF;FONE;CEP;ENDERECO;NUMERO;COMPLEMENTO;BAIRRO;CIDADE;ESTADO;EMAIL;NOME_REVENDA;CNPJ_REVENDA;NOTA_FISCAL;DATA_COMPRA;APARENCIA_PRODUTO;ACESSORIOS;CONSUMIDOR;REVENDA;OS_CORTESIA;ORIENTACOES_SAC;ENVIADO_DL;RASTREIO;
             *
             */

              fwrite($fp, $sua_os.";");
              fwrite($fp, $codigo_posto.";");
              fwrite($fp, $nome_posto.";");
              fwrite($fp, $data_abertura.";");
              fwrite($fp, $referencia_produto.";");
              fwrite($fp, $descricao_produto.";");
              fwrite($fp, $serie.";");
              fwrite($fp, $defeito_reclamado_descricao.";");
              fwrite($fp, $consumidor_nome.";");
              fwrite($fp, $consumidor_cpf.";");
              fwrite($fp, $consumidor_fone.";");
              fwrite($fp, $consumidor_cep.";");
              fwrite($fp, $consumidor_endereco.";");
              fwrite($fp, $consumidor_numero.";");
              fwrite($fp, $consumidor_complemento.";");
              fwrite($fp, $consumidor_bairro.";");
              fwrite($fp, $consumidor_cidade.";");
              fwrite($fp, $consumidor_estado.";");
              fwrite($fp, $consumidor_email.";");
              fwrite($fp, $revenda_nome.";");
              fwrite($fp, $revenda_cnpj.";");
              fwrite($fp, $nota_fiscal.";");
              fwrite($fp, $data_nf.";");
              fwrite($fp, $aparencia_produto.";");
              fwrite($fp, $acessorios.";");
              fwrite($fp, $consumidor_revenda.";");
              fwrite($fp, $cortesia.";");
              fwrite($fp, $orientacao_sac.";");
              fwrite($fp, $enviar_os.";");
              fwrite($fp, $codigo_rastreio.";\n");   

              $sqlOS = "UPDATE tbl_os SET exportado = CURRENT_TIMESTAMP WHERE os = $os";
              $resOS = pg_query($con,$sqlOS);
              $msg_erro = pg_errormessage($con);

              if(!empty($msg_erro)){
                $log_erro[] = $msg_erro;
              }
        }
      }
    }


    if (count($log_erro) > 0) {
            $msg = 'Script: '.__FILE__.'<br />' . implode("<br />", $log_erro);
            Log::envia_email($vet, APP, $msg);

    } else {

        Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

    }

    fclose($fp);

    if (file_exists($file) and (filesize($file) > 0)) {

      date_default_timezone_set('America/Sao_Paulo');
      $data_arquivo = date('d_m_Y');

      $destino = '/home/dleletronicos/telecontrol-' . $fabrica_nome . '/'.$data_arquivo.'.txt';

      copy($file, $dir . '/os-' . $data_arquivo . '.txt');
      rename($file, $destino);

    }

    $phpCron->termino();

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

