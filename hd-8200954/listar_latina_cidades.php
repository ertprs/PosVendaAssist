<?
                 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';          

//RECEBE PARÃMETRO                     
$Estado = $_POST["listEstados"];           

$sql = "SELECT DISTINCT            
					tbl_posto.cidade_pesquisa
		FROM   tbl_posto
		JOIN   tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
		JOIN   tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica 
		WHERE  tbl_posto_fabrica.fabrica = '15'
		AND tbl_posto.estado ILIKE '%$Estado%'
		ORDER BY tbl_posto.cidade_pesquisa";            

$res = pg_exec ($con,$sql);       
$row = pg_numrows ($res);    

//VERIFICA SE VOLTOU ALGO 
if($row) {                
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<cidades>\n";               
   
   //PERCORRE ARRAY            
   for($i=0; $i<$row; $i++) {  
      $cidade    = pg_result($res, $i, 'cidade_pesquisa'); 
	  $xml .= "<cidade>\n";     
      $xml .= "<codigo>".$cidade."</codigo>\n";                  
      $xml .= "</cidade>\n";    
   }//FECHA FOR                 
   
   $xml.= "</cidades>\n";
   
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)                                               

//PRINTA O RESULTADO  
echo $xml;            
?>
