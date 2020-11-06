<?php 
include __DIR__."/dbconfig.php";
include __DIR__."/includes/dbconnect-inc.php";

include __DIR__."/autentica_usuario.php";

use \Ticket\Ticket; 

if($_POST['exportar'] == true){
	$tecnico_agenda = $_POST["tecnico_agenda"];
	$num_os 		= $_POST['num_os'];

	$classTicket = new Ticket($login_fabrica); 
	$retorno = $classTicket->run($num_os);
	echo json_encode($retorno);
}




?>