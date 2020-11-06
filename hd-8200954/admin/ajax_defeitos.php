<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
//header("Pragma: no-cache, public");

$tipo = $_GET["tipo"]; 
	//pegar o login fabrica
if($tipo=="linha"){
$sql ="SELECT 	linha as id, 
				codigo_linha as codigo,
				nome as descricao
		from tbl_linha 
		where fabrica = $login_fabrica
		and ativo is true
		order by nome";
}
if($tipo=="familia"){
$sql ="SELECT 	familia as id, 
				codigo_familia as codigo,
				descricao as descricao
		from tbl_familia 
		where fabrica = $login_fabrica
		and ativo is true
		order by descricao";
}
if($tipo=="defeito_reclamado"){
$sql ="SELECT 	defeito_reclamado as id, 
				codigo as codigo,
				descricao as descricao
		from tbl_defeito_reclamado
		where fabrica = $login_fabrica 
		and ativo is true
		 AND tbl_defeito_reclamado.duvida_reclamacao <> 'CC' 
		order by descricao";
}
if($tipo=="defeito_constatado"){
$sql ="SELECT 	defeito_constatado as id, 
				codigo as codigo,
				descricao as descricao
		from tbl_defeito_constatado
		where fabrica = $login_fabrica 
		and ativo is true
		order by descricao";
}
if($tipo=="solucao"){
$sql ="SELECT 	solucao as id, 
				codigo as codigo,
				descricao as descricao
		from tbl_solucao
		where fabrica = $login_fabrica 
		and ativo is true
		order by descricao";
}
$resD = pg_exec ($con,$sql) ;
//echo "$sql";

//echo "<BR>$sql"; 
$row = pg_numrows ($resD);    
if($row) {                
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";               
 //PERCORRE ARRAY
   for($i=0; $i<$row; $i++) {
      $id        = pg_result($resD, $i, 'id');
	  $descricao = pg_result($resD, $i, 'descricao'); 
      $codigo    = pg_result($resD, $i, 'codigo');
	  $xml .= "<produto>\n";     
      $xml .= "<codigo>".$id."</codigo>\n";
	  $xml .= "<nome>".$descricao."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)                                               
echo $xml;            
?>
