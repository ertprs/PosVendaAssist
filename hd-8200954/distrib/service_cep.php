<?

header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';

$formCep = $_POST['formCep'];


$sqlCep = "SELECT * FROM tbl_cep WHERE cep = '".str_replace(".", "", str_replace("-", "", $formCep))."'";

$cmdQuery = pg_query($con,$sqlCep);

$logradouro = pg_fetch_result($cmdQuery, 0, "logradouro");
$bairro = pg_fetch_result($cmdQuery, 0, "bairro");
$cidade = pg_fetch_result($cmdQuery, 0, "cidade");
$estado = pg_fetch_result($cmdQuery, 0, "estado");

$arrayData = array(
	"logradouro" => $logradouro,
	"bairro" => $bairro,
	"cidade" => $cidade,
	"estado" => $estado
	);


echo json_encode($arrayData);


?>