<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';          
include 'autentica_usuario.php';

$peca = trim($_GET["peca"]); 
$os = trim($_GET["os"]); 
$xsql = "SELECT consumidor_revenda from tbl_os where os=$os";
$xres = pg_exec($con,$xsql);
$consumidor_revenda = pg_result($xres,0,consumidor_revenda);
  
$sql = "SELECT 	tbl_defeito.descricao                   , 
				tbl_defeito.defeito                     , 
				tbl_peca_defeito.ativo
		FROM tbl_peca_defeito 
		JOIN tbl_defeito using(defeito)
		JOIN tbl_peca on tbl_peca.peca = tbl_peca_defeito.peca 
		AND tbl_peca.fabrica = $login_fabrica
		AND tbl_peca.referencia = '$peca'
		WHERE tbl_peca_defeito.ativo = 't' 
		ORDER BY tbl_peca.descricao, tbl_defeito.descricao";
$resD = pg_exec($con,$sql);
//echo $sql;
if(pg_numrows($resD)>0){
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";               
   //PERCORRE ARRAY            
   for($i=0; $i<pg_numrows($resD); $i++) {  
   
      $solucao    = pg_result($resD, $i, 'defeito'); 
	  $descricao = pg_result($resD, $i, 'descricao'); 
	  $xml .= "<produto>\n";     
      $xml .= "<codigo>".$solucao."</codigo>\n";                  
	  $xml .= "<nome>".$descricao."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   if($consumidor_revenda=='R'){
	$xml .= "<produto>\n";     
      $xml .= "<codigo>196</codigo>\n";                  
	  $xml .= "<nome>Revisão Estoque de Loja</nome>\n";
      $xml .= "</produto>\n";    
	}
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}else{
$sql = "SELECT 	tbl_defeito.descricao                   , 
				tbl_defeito.defeito                     
		FROM tbl_defeito 
		WHERE tbl_defeito.fabrica = $login_fabrica
		AND tbl_defeito.ativo = 't' 
		ORDER BY tbl_defeito.descricao";
$resD = pg_exec($con,$sql);
//echo $sql;
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";               
   //PERCORRE ARRAY            
   for($i=0; $i<pg_numrows($resD); $i++) {  
   
      $solucao    = pg_result($resD, $i, 'defeito'); 
	  $descricao = pg_result($resD, $i, 'descricao'); 
	  $xml .= "<produto>\n";     
      $xml .= "<codigo>".$solucao."</codigo>\n";                  
	  $xml .= "<nome>".$descricao."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   if($consumidor_revenda=='R'){
	$xml .= "<produto>\n";     
      $xml .= "<codigo>196</codigo>\n";                  
	  $xml .= "<nome>Revisão Estoque de Loja</nome>\n";
      $xml .= "</produto>\n";    
	}
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 

}//FECHA IF (row)                                               

echo $xml;            
?>
