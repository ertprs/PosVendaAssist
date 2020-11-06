<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/aws/s3_config.php';
include S3CLASS;

$v = "/home/paulo/video_desmontagem_e_montagem_immaginare_e_evolux.mp4";
    $s3 = new AmazonTC("co", 90);


ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

try{


    $s = new TDocs($con,90);
                    $extension  = preg_replace("/.+\./", "", $v);
                    $name       = preg_replace("/\..+/", "", basename($v));
                    $mime       = str_replace("/", "|", mime_content_type($v));
                    $size       = filesize($v);

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

					var_dump($s->uploadFileS3($v, $response['id']));

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
exit;
                    $insert = "
                        INSERT INTO tbl_tdocs
                        (tdocs_id, fabrica, contexto, situacao, obs, referencia, referencia_id)
                        VALUES
                        ('{$response['id']}', 90, 'comunicados', 'ativo', '{$obs}', 'comunicados', 4503602)
                    ";
					$resInsert = pg_query($con, $insert);
} catch(Exception $e) {
	echo 'erro';
}
?>
