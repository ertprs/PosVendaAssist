<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/aws/s3_config.php';
include S3CLASS;

try{
    $sql = "
    	SELECT fabrica, count(hd_chamado) AS qtd 
    	FROM tbl_hd_chamado  
    	GROUP BY fabrica  
    	ORDER BY qtd ASC;
    ";
    //151 = mondial
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Linha 21 Erro: ".pg_last_error());
    }

    $ultima_hd = null;

    while ($fabrica = pg_fetch_object($res)) {
        echo "fábrica: {$fabrica->fabrica}\n";
        echo "HDs: {$fabrica->qtd}\n";

        $s3 = new AmazonTC("callcenter", $fabrica->fabrica);

        $sqlHD = "
            SELECT tbl_hd_chamado.hd_chamado, data, qtde 
            FROM tbl_hd_chamado LEFT JOIN tmp_hd_tdocs 
             ON tmp_hd_tdocs.hd_chamado = tbl_hd_chamado.hd_chamado
            WHERE tmp_hd_tdocs.qtde IS NULL
	    AND Extract('Year' from data)  = 2018;
        ";
        $resHD = pg_query($con, $sqlHD);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Linha 40 Erro: ".pg_last_error());
        }

        $i = 1;

        while ($hd = pg_fetch_object($resHD)) {
            $ultima_os = $hd->hd_chamado;

            echo "$i=";

            $anexos = $s3->getObjectList("{$hd->hd_chamado}-", false);
            
            if (!empty($anexos)) {
            	echo count($anexos) . "\n";
                foreach ($anexos as $k => $v) {
                    $metadata = $s3->get_object_metadata($s3->bucket, $v);

                    $arquivo_s3 = $v;

                    $extension  = preg_replace("/.+\./", "", $v);
                    $name       = preg_replace("/\..+/", "", basename($v));
                    $mime       = str_replace("/", "|", $metadata["ContentType"]);
                    $size       = $metadata["Size"];

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/s3/name/{$name}/extension/{$extension}/mime/{$mime}/size/{$size}",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_HTTPHEADER => array(
                            "access-application-key: 32e1ea7c54c0d7c144bc3d3045d8309a5b137af9",
                            "access-env: PRODUCTION",
                            "cache-control: no-cache",
                            "content-type: multipart/form-data",
                        ),
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ($err) {
                        throw new Exception($err);
                    } else {
                        $response = json_decode($response, true);
                    }

                    $s3->copy_object(
                        array("bucket" => $s3->bucket, "filename" => $arquivo_s3),
                        array("bucket" => "br.com.telecontrol.tdocs-devel", "filename" => $response["id"]),
                        array("acl" => $s3::ACL_PRIVATE, "storage" => $s3::STORAGE_STANDARD)
                    );

                    $obs = json_encode([array(
                        "acao"     => "anexar",
                        "filename" => $name.".".$extension,
                        "filesize" => $size,
                        "date"     => date("c"),
                        "fabrica"  => $fabrica->fabrica,
                        "page"     => "rotinas/telecontrol/mv_s3_tdocs_callcenter.php",
                        "source"   => "moved-manually",
                        "usuario"  => array()
                    )]);

                    $insert = "
                        INSERT INTO tbl_tdocs
                        (tdocs_id, fabrica, contexto, situacao, obs, referencia, referencia_id)
                        VALUES
                        ('{$response['id']}', {$fabrica->fabrica}, 'callcenter', 'ativo', '{$obs}', 'callcenter', {$hd->hd_chamado})
                    ";
                    $resInsert = pg_query($con, $insert);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Linha 121 Erro: ".pg_last_error());
                    }
                }
                $count = count($anexos);
                $update = "INSERT INTO tmp_hd_tdocs(hd_chamado, qtde) VALUES ({$hd->hd_chamado}, {$count}) ";
                $resUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Linha 129 Erro: ".pg_last_error());
                }
            } else {
                $update = "INSERT INTO tmp_hd_tdocs(hd_chamado, qtde) VALUES ({$hd->hd_chamado}, 0)";
                $resUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Linha 136 Erro: ".pg_last_error());
                }
            }
        
            $i++;
        }

        echo "\nfinalizado\n\n";
    }
} catch(Exception $e) {
    echo "
        Última OS: {$ultima_os}\n
        Erro: {$e->getMessage()}
    ";

    mail(
        "felipe.eleoterio@telecontrol.com.br",
        "Erro S3 > TDOCS",
        "Última OS: {$ultima_os}\nErro: {$e->getMessage()}"
    );
}
/*
Script
CREATE TABLE tmp_hd_tdocs (
  hd_chamado int NOT NULL ,
  qtde int NOT NULL
);
*/
