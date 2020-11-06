<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_usuario.php";

$peca = trim($_GET["peca"]); 

$sql = "SELECT tbl_peca.peca, 	tbl_defeito.descricao                   , 
				tbl_defeito.defeito                     , 
				tbl_defeito.codigo_defeito              ,
				tbl_peca_defeito.ativo
		FROM  tbl_peca_defeito 
		JOIN  tbl_defeito using(defeito)
		JOIN  tbl_peca on tbl_peca.peca = tbl_peca_defeito.peca 
		AND   tbl_peca.fabrica = $login_fabrica
		AND   tbl_peca.referencia = '$peca'
		WHERE tbl_defeito.ativo = 't' 
		ORDER BY tbl_peca.descricao, tbl_defeito.descricao";
$resD = pg_exec($con,$sql);
//echo $sql;

if(pg_numrows($resD)>0){
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";               
   //PERCORRE ARRAY            
   for($i=0; $i<pg_numrows($resD); $i++) {  
   
      $solucao    = pg_result($resD,$i,'defeito'); 
	  $descricao = pg_result($resD,$i,'descricao'); 
	  $codigo_defeito = pg_result($resD,$i,'codigo_defeito');
	  $xml .= "<produto>\n";     
      $xml .= "<codigo>".$solucao."</codigo>\n";                  
	  $xml .= "<nome>" ;
	  if ($login_fabrica == 50){
		  $xml .= "$codigo_defeito - ";
	  }
	  $xml .= $descricao."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}

echo $xml;            
?>
