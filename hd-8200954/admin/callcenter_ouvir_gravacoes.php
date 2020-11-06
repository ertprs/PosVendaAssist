<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include_once '../class/communicator.class.php';
require_once '../helpdesk.inc.php';
require_once '../funcoes.php';
include '../plugins/fileuploader/TdocsMirror.php';


$id_ligacao_gr = $_GET['id_ligacao_gr'];

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://api2.telecontrol.com.br/telefonia/ligacoes/uniqueId/" . $id_ligacao_gr,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "access-application-key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
    "access-env: PRODUCTION"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  exit("Erro ao buscar aúdio");
} else {
	$response = json_decode($response, true);
	$audio = $response['tdocs_audio'];

	if (empty($audio)) {
		exit("Gravação não ncontrada");
	}

	$tdocs = new TdocsMirror;
	$file = $tdocs->get($audio);

	if (empty($file)) {
		exit("Erro ao buscar aúdio");
	} else {
		$file_url = $file['link'];
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary"); 
		header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\""); 
		readfile($file_url);;
		exit;
/*
	?>
		<div style='background:#FFFFFF;height:100%;text-align:center;'><br><br>
			<audio controls>
		  		<source src="<?=$file['link']?>" type="audio/mpeg">  
			</audio>
		</div>
	<?php */
	}
}
?>

