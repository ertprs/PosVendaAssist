<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

$peca = trim($_GET["peca"]); 
$os = trim($_GET["os"]); 
if($os != NULL){
    $xsql = "SELECT consumidor_revenda from tbl_os where os=$os";
    $xres = pg_exec($con,$xsql);
    $consumidor_revenda = pg_result($xres,0,consumidor_revenda);
} 
if (!empty($os)) {

	$xsql = "SELECT consumidor_revenda from tbl_os where os = $os";
	$xres = pg_exec($con, $xsql);

	$consumidor_revenda = pg_result($xres, 0, 'consumidor_revenda');

}

$sql = "SELECT 	tbl_defeito.descricao      , 
				tbl_defeito.defeito        , 
				tbl_defeito.codigo_defeito ,
				tbl_peca_defeito.ativo
		FROM 	tbl_peca_defeito 
		JOIN 	tbl_defeito           ON tbl_defeito.defeito = tbl_peca_defeito.defeito AND tbl_defeito.fabrica = $login_fabrica
		JOIN 	tbl_peca              ON tbl_peca.peca       = tbl_peca_defeito.peca    AND tbl_peca.fabrica    = $login_fabrica ";

if (!empty($kit_peca) and $kit_peca <>'undefined') {

	$sql .= " JOIN  tbl_kit_peca_peca ON tbl_kit_peca_peca.peca = tbl_peca.peca
			  JOIN  tbl_kit_peca      ON tbl_kit_peca.kit_peca  = tbl_kit_peca_peca.kit_peca AND tbl_kit_peca.kit_peca = $kit_peca ";

} else {

	$sql2 .= " AND tbl_peca.referencia  = '$peca' ";

}

$sql .=	" WHERE tbl_peca_defeito.ativo = 't'
			AND tbl_defeito.ativo      = 't' ";

$sql .= $sql2;

$sql .=	" ORDER BY tbl_peca.descricao, tbl_defeito.descricao ";

$resD = pg_query($con, $sql);
// echo $sql;

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
   if($consumidor_revenda=='R' and $login_fabrica==24){
	$xml .= "<produto>\n";     
      $xml .= "<codigo>196</codigo>\n";                  
	  $xml .= "<nome>Revisão Estoque de Loja</nome>\n";
      $xml .= "</produto>\n";    
	}
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}else if ($login_fabrica == 91){

}else{
	if($login_fabrica != 50 AND $login_fabrica != 91) {
        $rows = 0;

        if ($login_fabrica == '30' and !empty($peca)) {
            $sql = "
                SELECT  tbl_defeito.descricao,
                        tbl_defeito.defeito,
                        tbl_defeito.codigo_defeito
                FROM    tbl_defeito
                JOIN tbl_peca_defeito ON tbl_peca_defeito.defeito = tbl_defeito.defeito
                JOIN tbl_familia_peca ON tbl_familia_peca.familia_peca = tbl_peca_defeito.familia_peca
                JOIN tbl_peca_familia ON tbl_peca_familia.familia_peca = tbl_familia_peca.familia_peca
                JOIN tbl_peca ON tbl_peca.peca = tbl_peca_familia.peca
                WHERE tbl_defeito.fabrica = $login_fabrica
                AND tbl_defeito.ativo = 't'
                AND tbl_peca.referencia = '$peca'
                ORDER BY tbl_defeito.descricao
                ";
            $resD = pg_query($con, $sql);
            $rows = pg_num_rows($resD);
        }

        if ($rows == 0) {
            $sql = "SELECT 	tbl_defeito.descricao                   , 
                            tbl_defeito.defeito                     ,
                            tbl_defeito.codigo_defeito
                    FROM tbl_defeito 
                    WHERE tbl_defeito.fabrica = $login_fabrica
                    AND tbl_defeito.ativo = 't' 
                    ORDER BY tbl_defeito.descricao";
            $resD = pg_exec($con,$sql);
        }
		//echo $sql;
	   //XML
	   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	   $xml .= "<produtos>\n";               

	   //PERCORRE ARRAY            
	   for($i=0; $i<pg_numrows($resD); $i++) {
		  $solucao    = pg_result($resD, $i, 'defeito'); 
		  $descricao = pg_result($resD, $i, 'descricao'); 
		  $codigo_defeito = pg_result($resD, $i, 'codigo_defeito');
		  $xml .= "<produto>\n";     
		  $xml .= "<codigo>".$solucao."</codigo>\n";                  
		  $xml .= "<nome>" ;
		  if ($login_fabrica == 50){
			  $xml .= "$codigo_defeito - ";
		  }
		  $xml .= $descricao."</nome>\n";
		  $xml .= "</produto>\n";    
	   }//FECHA FOR                 

	   if($consumidor_revenda=='R' and $login_fabrica==24){
			$xml .= "<produto>\n";     
			$xml .= "<codigo>196</codigo>\n";                  
			$xml .= "<nome>Revisão Estoque de Loja</nome>\n";
			$xml .= "</produto>\n";    
		}
		$xml.= "</produtos>\n";

	}elseif($login_fabrica == 50){
		$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml .= "<produtos>\n";               
		$xml .= "	<produto>\n";     
		$xml .= "	<codigo> </codigo>\n";                  
		$xml .= "	<nome>Sem defeitos cadastrados, contate o fabricante</nome>\n";
		$xml .= "	</produto>\n";    
		$xml .= "</produtos>\n";
	   //CABEÇALHO
	}else if ($login_fabrica == 91) {

		$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml .= "<produtos>\n";               
		$xml .= "	<produto>\n";     
		$xml .= "	<codigo>0</codigo>\n";                  
		$xml .= "	<nome>Sem defeitos cadastrados</nome>\n";
		$xml .= "	</produto>\n";    
		$xml .= "</produtos>\n";

 	}

	//CABEÇALHO
	Header("Content-type: application/xml; charset=iso-8859-1"); 

}//FECHA IF (row)                                               

echo $xml;            
?>
