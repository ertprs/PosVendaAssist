<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$linha = $_GET["linha"];
  
$sql ="SELECT 	familia   ,
		descricao
	FROM tbl_familia
	WHERE linha = $linha
	AND fabrica = $login_empresa
	ORDER BY descricao";

$resD = pg_exec ($con,$sql) ;

$row = pg_numrows ($resD);

if($row) {
	//XML
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<produtos>\n";
	//PERCORRE ARRAY
	for($i=0; $i<$row; $i++) {
	
		$familia   = pg_result($resD, $i, 'familia');
		$descricao = pg_result($resD, $i, 'descricao');
		$xml .= "<produto>\n";
		$xml .= "<codigo>".$familia."</codigo>\n";
		$xml .= "<nome>".$descricao."</nome>\n";
		$xml .= "</produto>\n"; 
	}//FECHA FOR
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)
echo $xml;
?>
