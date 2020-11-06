<?php

include_once dirname(__FILE__) . '/../../dbconfig.php';
include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$login_fabrica = 169;
$dir = "/tmp/midea/";

$data_rotina = date("dmY_His");

$sql = "SELECT
            tbl_posto_fabrica.posto,
            tbl_posto.cnpj,
            tbl_posto.nome,
            tbl_posto_fabrica.codigo_posto,
            tbl_tipo_posto.descricao as tipo_posto,
            CASE WHEN tbl_tipo_posto.tipo_revenda = 't' THEN 'SIM' ELSE 'NAO' END AS tipo_revenda,
            tbl_posto_fabrica.latitude,
            tbl_posto_fabrica.longitude,
            tbl_posto_fabrica.credenciamento AS situacao_posto,
            tbl_posto_fabrica.contato_endereco,
            tbl_posto_fabrica.contato_numero,
            tbl_posto_fabrica.contato_complemento,
            tbl_posto_fabrica.contato_bairro,
            tbl_posto_fabrica.contato_cidade,
            tbl_posto_fabrica.contato_estado,
            tbl_posto_fabrica.contato_cep,
            tbl_posto_fabrica.parametros_adicionais
            FROM tbl_posto_fabrica
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            ORDER BY tbl_posto.nome ";

//die(nl2br($sql));
$res            = pg_query($con, $sql);
$contador_res   = pg_numrows($res);
$msg_erro       .= pg_errormessage($con);

$nome_arquivo   = 'postos_midea_'.date("Ymd").'.csv';
$arquivo_nome   = $dir . $nome_arquivo;

if ($contador_res > 0) {
    $arquivo = fopen($arquivo_nome, "w");

    $cabecalho_arquivo = 'cnpj;codigo_posto;nome;tipo_posto;latitude;longitude;'.'situacao_posto'.';'.'contato_endereco'.';'.'contato_numero'.';contato_complemento;contato_bairro;contato_cidade;contato_estado;contato_cep;';

    //utf8_decode('INÍCIO HORÁRIO FUNCIONAMENTO').';'.utf8_decode('FIM HORÁRIO FUNCIONAMENTO')

	fwrite($arquivo, $cabecalho_arquivo); 
	fwrite($arquivo, "\n");

	for($i=0; $i<$contador_res; $i++) {
		$posto                  = pg_fetch_result($res, $i, 'posto');
        $cnpj                  = pg_fetch_result($res, $i, 'cnpj');
        $nome                   = str_replace(",", "", pg_fetch_result($res, $i, 'nome'));
        $codigo_posto           = pg_fetch_result($res, $i, 'codigo_posto');
        $tipo_revenda           = pg_fetch_result($res, $i, 'tipo_revenda');
        $tipo_posto             = pg_fetch_result($res, $i, 'tipo_posto');
        $latitude               = pg_fetch_result($res, $i, 'latitude');
        $longitude              = pg_fetch_result($res, $i, 'longitude');
        $situacao_posto         = pg_fetch_result($res, $i, 'situacao_posto');
        $contato_endereco       = str_replace(",", "", pg_fetch_result($res, $i, 'contato_endereco'));

      
        $contato_numero         = pg_fetch_result($res, $i, 'contato_numero');
        $contato_complemento    = pg_fetch_result($res, $i, 'contato_complemento');
        $contato_bairro         = pg_fetch_result($res, $i, 'contato_bairro');
        $contato_cidade         = pg_fetch_result($res, $i, 'contato_cidade');
        $contato_estado         = pg_fetch_result($res, $i, 'contato_estado');
        $contato_cep            = pg_fetch_result($res, $i, 'contato_cep');
        $parametros_adicionais  = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
        
        $inicio_funcionamento   = $parametros_adicionais['inicio_horario_funcionamento'];
        $fim_funcionamento      = $parametros_adicionais['fim_horario_funcionamento'];        


        if (mb_detect_encoding($contato_bairro, 'utf-8', true)) {
            $contato_bairro = utf8_decode($contato_bairro);
            
            if (mb_detect_encoding($contato_bairro, 'utf-8', true)) {
                $contato_bairro = utf8_decode($contato_bairro);
            }
        }

        if (mb_detect_encoding($contato_cidade, 'utf-8', true)) {
            $contato_cidade = utf8_decode($contato_cidade);
            
            if (mb_detect_encoding($contato_cidade, 'utf-8', true)) {
                $contato_cidade = utf8_decode($contato_cidade);
            }
        }

        if (mb_detect_encoding($contato_endereco, 'utf-8', true)) {
            $contato_endereco = utf8_decode($contato_endereco);
            
            if (mb_detect_encoding($contato_endereco, 'utf-8', true)) {
                $contato_endereco = utf8_decode($contato_endereco);
            }
        }

        if (mb_detect_encoding($nome, 'utf-8', true)) {
            $nome = utf8_decode($nome);
            
            if (mb_detect_encoding($nome, 'utf-8', true)) {
                $nome = utf8_decode($nome);
            }
        }


        $corpo_arquivo = $cnpj . ';'. $codigo_posto .';' . $nome . ';' . $tipo_posto . ';' . $latitude . ';' . $longitude . ';' . $situacao_posto . ';' . $contato_endereco . ';' . $contato_numero . ';' . $contato_complemento . ';' . $contato_bairro . ';' . $contato_cidade . ';' . $contato_estado . ';' . $contato_cep ;

        //. ';' . $inicio_funcionamento . ';' . $fim_funcionamento

		fwrite($arquivo, $corpo_arquivo);
        fwrite($arquivo, "\n"); 
    }

	fclose($arquivo);    

    // Envio utilizando SFTP
    system('lftp sftp:/\/\midea:BR@zil@2019@sftp2.teleperformance.com.br:/sftp_midea -e "put '.$arquivo_nome.'; bye"');
}
