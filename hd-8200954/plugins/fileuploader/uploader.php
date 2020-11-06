<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$admin_es = preg_match('/\/admin_es\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$admin_cliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/../../dbconfig.php';
include __DIR__.'/../../includes/dbconnect-inc.php';

include 'TdocsMirror.php';
include 'TdocsChunkedMirror.php';

if ($areaAdmin === true) {
    include __DIR__.'/../../admin/autentica_admin.php';

    $usuarioInformacoes = array(
    	"admin" => $login_admin
    );
} elseif($admin_es){
    include __DIR__.'/../../admin_es/autentica_admin.php';

    $usuarioInformacoes = array(
        "admin" => $login_admin
	);
} elseif($admin_cliente){
    include __DIR__.'/../../admin_cliente/autentica_admin.php';

    $usuarioInformacoes = array(
        "admin" => $login_admin
    );

}else {
    include __DIR__.'/../../autentica_usuario.php';

    $usuarioInformacoes = array(
    	"posto" => $login_posto
    );
}

if(file_exists(dirname(__FILE__) ."/../../os_cadastro_unico/fabricas/$login_fabrica/regras_box_uploader.php")){
    include dirname(__FILE__) . "/../../os_cadastro_unico/fabricas/$login_fabrica/regras_box_uploader.php";
}

pg_close($con);

$tmp = $_FILES['files']['tmp_name'][0];
$name = $_FILES['files']['name'][0];
$size = $_FILES['files']['size'][0];

$contexto = $_GET['context'];
$reference = $_GET['context'];
$reference_id = $_GET['reference_id'];
$current_page = $_GET['current_page'];
$descricao = $_GET['descricao'];
$destiny = "/tmp/" . $name;

$classificacaoImagem = $_POST['classificacao'];

if (isset($valida_extensao_por_tipo)) {

    if (!$valida_extensao_por_tipo($name, $contexto, $classificacaoImagem)) {

        exit(json_encode([
            "exception" => utf8_encode(traduz("Para o tipo de anexo {$classificacaoImagem} são válidas somente as seguintes extensões: ").implode(", ", $arrTiposAceitos[$contexto][$classificacaoImagem]))
        ]));

    }

}

$lastPart = $_POST['lastPart'];
$part = $_POST['part'];
$metadata = $_POST['metadata'];
$uploadId = $_POST['uploadId'];
$current_page = empty($current_page) ? "uploader.php" : $current_page;

if($contexto == "" || $reference_id == ""){
	header('Content-Type: application/json');
	echo json_encode(array("exception" => "Informe o contexto do upload"));
	exit;
}
move_uploaded_file($tmp, $destiny);
$tdocsChunkedMirror = new TdocsChunkedMirror();
$tdocsMirror = new TdocsMirror();
$chunked = (!isset($_POST['lastPart'])) ? false : true;

try {
    if ($chunked == false) {
        $response = $tdocsMirror->post($destiny);

        foreach ($response as $res) {
            foreach ($res as $key => $value) {
                $uniqueId = $value['unique_id'];
            }
        }
    } else {
        $response = json_decode($tdocsChunkedMirror->post($destiny, $uploadId, $part, $lastPart, $metadata), true);
        $uploadId = $response['upload_id'];
        $uniqueId = $response['document']['unique_id'] ? $response['document']['unique_id']: null;
		if($lastPart == 1) {
			$destiny = "/tmp/".$response['file_name'] ;
			$id    = $response['name'] ;
			$name    = $response['file_name'] ;

			$uniqueId = $id;
			$chunked = false;
		}
    }

    unset($destiny);

    if(array_key_exists("exception", $response)){
        header('Content-Type: application/json');
		echo json_encode(array("exception" => "Ocorreu um erro ao realizar o upload: ".$response['message']));
		exit;
    }

    if($_GET['hash_temp'] == "true"){
        $hash_temp = $_GET['reference_id'];
    }else {
        $hash_temp = null;
    }

    if ($response) {
        $con = pg_connect($parametros);

        if (!$chunked || $chunked && $uniqueId) {
            $row = [array(
                "acao" => "anexar",
                "filename" => $name,
                "filesize"=> $size,
                "data" => date("Y-m-d\TH:i:s"),
                "fabrica" => $login_fabrica,
                "usuario" => $usuarioInformacoes,
                "descricao" => $descricao,
                "page" => $current_page,
                "source" => "telecontrol-file-uploader",
                "typeId" => $classificacaoImagem
            )];

            $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id, hash_temp) values($1, $2, $3, $4, $5, $6, $7, $8)";

            if($hash_temp !== NULL){
                $reference_id = 0;
            }

            $stmt = pg_prepare($con,"insert",$sql);
            $result = pg_execute($con,"insert",array(
                $uniqueId,
                $login_fabrica,
                $contexto,
                'ativo',
                json_encode($row),
                $reference,
                $reference_id,
                $hash_temp
            ));

            if (function_exists("replica_anexo_os_revenda")) {
               replica_anexo_os_revenda($uniqueId, $contexto, $row, $reference, $reference_id, $hash_temp);
            }

            if(pg_last_error($con) != false){
                header('Content-Type: application/json');

                echo json_encode(
                    array(
                        "param" => array(
                                        $uniqueId,
                                        $login_fabrica,
                                        $contexto,
                                        'ativo',
                                        json_encode($row),
                                        $reference,
                                        $reference_id,
                                    ),
                        "exception" => utf8_encode("Ocorreu um erro ao realizar o upload: O registro não foi gravado no banco"),
                        "errorLog" => utf8_encode(pg_last_error($con))
                    )
                );
                exit;
            }
        }
    }

	header('Content-Type: application/json');
    if ($response) {
        if ($chunked == false) {
            $responseGET = $tdocsMirror->get($uniqueId);
            $response['unique_id'] = $uniqueId;
            $response['link'] = $responseGET['link'];
            $response['file_name'] = $responseGET['file_name'];

            echo json_encode($response);
        } else {
            if ($lastPart == 1) {
                $response['file_name'] = $_FILES['files']['name'][0];
				$response['file_type'] = $_FILES['files']['type'][0];

            }
            echo json_encode($response);
        }
    }
    
    $con = pg_connect($parametros);

} catch (\Exception $e) {
    echo $e->getMessage();
}

