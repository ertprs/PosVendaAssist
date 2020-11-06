<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/aws/s3_config.php';
include S3CLASS;

try{
    $sql = "
        SELECT fabrica, COUNT(os) AS qtde_os
        FROM tbl_os
	WHERE fabrica = 35
	AND data_digitacao between '2018-11-20 15:00' and '2018-12-31 23:59'
	AND s3_os_nf_key IS NULL
        GROUP BY fabrica
        ORDER BY qtde_os ASC
    ";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Linha 21 Erro: ".pg_last_error());
    }

    $ultima_os = null;

    while ($fabrica = pg_fetch_object($res)) {
        echo "fábrica: {$fabrica->fabrica}\n";
        echo "OSs: {$fabrica->qtde_os}\n";
	
	for($x = 0; $x < ($fabrica->qtde_os / 500); $x++){
        $s3 = new AmazonTC("os", $fabrica->fabrica);

        $sqlOS = "
            SELECT os, data_abertura
            FROM tbl_os
            WHERE fabrica = {$fabrica->fabrica}
	    AND s3_os_nf_key IS NULL
	    AND data_digitacao between '2018-11-20 15:00' and '2018-12-31 23:59'
		LIMIT 500
        ";
        $resOS = pg_query($con, $sqlOS);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Linha 40 Erro: ".pg_last_error());
        }

        $i = 1;

        while ($os = pg_fetch_object($resOS)) {
            $ultima_os = $os->os;

            echo "$i.";

            list($year, $month, $day) = explode("-", $os->data_abertura);

            $anexos = $s3->getObjectList("{$os->os}_", false, $year, $month);

            if (!empty($anexos)) {
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

                    $obs = json_encode(array(
                        "acao"     => "anexar",
                        "filename" => $name.".".$extension,
                        "filesize" => $size,
                        "date"     => date("c"),
                        "fabrica"  => $fabrica->fabrica,
                        "page"     => "rotinas/telecontrol/mv_s3_tdocs.php",
                        "source"   => "moved-manually",
                        "usuario"  => array()
                    ));

                    $insert = "
                        INSERT INTO tbl_tdocs
                        (tdocs_id, fabrica, contexto, situacao, obs, referencia, referencia_id)
                        VALUES
                        ('{$response['id']}', {$fabrica->fabrica}, 'os', 'ativo', '{$obs}', 'os', {$os->os})
                    ";
                    $resInsert = pg_query($con, $insert);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Linha 121 Erro: ".pg_last_error());
                    }
                }

                $update = "UPDATE tbl_os SET s3_os_nf_key = 'ok' WHERE os = {$os->os}";
                $resUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Linha 129 Erro: ".pg_last_error());
                }
            } else {
                $update = "UPDATE tbl_os SET s3_os_nf_key = 'ok' WHERE os = {$os->os}";
                $resUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Linha 136 Erro: ".pg_last_error());
                }
            }
        
	    $i++;
	    echo "\nbloco de OSs finalizados\n";
        }
	}
        echo "\nprocesso finalizado\n\n";
    }
} catch(Exception $e) {
    echo "
        Última OS: {$ultima_os}\n
        Erro: {$e->getMessage()}
    ";

    mail(
        "ronald.santos@telecontrol.com.br",
        "Erro S3 > TDOCS",
        "Última OS: {$ultima_os}\nErro: {$e->getMessage()}"
    );
}
