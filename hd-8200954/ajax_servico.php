<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';          
include 'autentica_usuario.php';

$peca = trim($_GET["peca"]); 
$sql = "SELECT tbl_servico_realizado.descricao, tbl_servico_realizado.servico_realizado
		from tbl_peca_servico	
		JOIN tbl_peca on tbl_peca_servico.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
		JOIN tbl_servico_realizado on tbl_peca_servico.servico_realizado = tbl_servico_realizado.servico_realizado and tbl_servico_realizado.fabrica =$login_fabrica
 		WHERE tbl_peca.referencia = '$peca'
		AND  tbl_peca_servico.ativo = 't'";
$resD = pg_exec($con,$sql);
//echo $sql;
if(pg_numrows($resD)>0){
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";               
   //PERCORRE ARRAY            
   for($i=0; $i<pg_numrows($resD); $i++) {  
   
      $solucao    = pg_result($resD, $i, 'servico_realizado'); 
	  $descricao = pg_result($resD, $i, 'descricao'); 
	  $xml .= "<produto>\n";     
      $xml .= "<codigo>".$solucao."</codigo>\n";                  
	  $xml .= "<nome>".html_entity_decode($descricao)."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   if($consumidor_revenda=='R' and $login_fabrica==24){
	$xml .= "<produto>\n";     
      $xml .= "<codigo>196</codigo>\n";                  
	  $xml .= "<nome>Revisão Estoque de Loja</nome>\n";
      $xml .= "</produto>\n";    
	}
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}else{
$sql = "SELECT 	tbl_servico_realizado.descricao, 
				tbl_servico_realizado.servico_realizado
		FROM tbl_servico_realizado 
		WHERE tbl_servico_realizado.fabrica = $login_fabrica
		AND tbl_servico_realizado.ativo IS TRUE 
		ORDER BY descricao";
$resD = pg_exec($con,$sql);
//echo $sql;
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";               
   //PERCORRE ARRAY            
   for($i=0; $i<pg_numrows($resD); $i++) {  
   
      $solucao    = pg_result($resD, $i, 'servico_realizado'); 
	  $descricao = pg_result($resD, $i, 'descricao'); 
	  $xml .= "<produto>\n";     
      $xml .= "<codigo>".$solucao."</codigo>\n";                  
	  $xml .= "<nome>".html_entity_decode($descricao)."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   if($consumidor_revenda=='R' and $login_fabrica==24){
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
