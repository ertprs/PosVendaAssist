<?

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'funcao_codigo_barras.php';


montacodigodebarras('0123');
function esquerda($entra,$comp){
	return substr($entra,0,$comp);
}
function direita($entra,$comp){
	return substr($entra,strlen($entra)-$comp,$comp);
}




