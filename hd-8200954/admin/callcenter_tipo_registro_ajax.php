<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

header('Content-Type: text/html; charset=ISO-8859-1');

if (isset($_GET["tipo_registro"])){
	$tipo_registro = trim ($_GET["tipo_registro"]);

	if($tipo_registro=='Contato'){
		echo "<option value='informacao'>Informação</option>";
		echo "<option value='reclamacao'>Reclamação</option>";
		echo "<option value='sugestao'>Sugestão/Elogio</option>";
	}elseif($tipo_registro =='Processo'){
		echo "<option value='reclamacao'>Reclamação</option>";
		echo "<option value='solicitacao'>Solicitação</option>";
	}
	exit;
}


?>