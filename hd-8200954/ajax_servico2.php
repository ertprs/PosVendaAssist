<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';          
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$peca = trim($_GET["peca"]); 
$posto = trim($_GET["posto"]); 

$xxsql = "select tbl_posto_fabrica.garantia_antecipada
			from tbl_posto_fabrica
			where tbl_posto_fabrica.posto = $posto
			and tbl_posto_fabrica.fabrica = $login_fabrica";
			//echo $xxsql;
$xxres = pg_exec($con,$xxsql);
if(pg_numrows($xxres)>0){
	$posto_pede_garantia_antecipada = pg_result($xxres,0,garantia_antecipada);		
}


$sql = "SELECT tbl_servico_realizado.descricao, tbl_servico_realizado.servico_realizado
		from tbl_peca_servico	
		JOIN tbl_peca on tbl_peca_servico.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
		JOIN tbl_servico_realizado on tbl_peca_servico.servico_realizado = tbl_servico_realizado.servico_realizado and tbl_servico_realizado.fabrica =$login_fabrica
 		WHERE tbl_peca.referencia = '$peca'
		AND  tbl_peca_servico.ativo = 't'
		and tbl_servico_realizado.peca_estoque  is not true
		ORDER BY tbl_servico_realizado.descricao";
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
	  $xml .= "<nome>".$descricao."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   if($consumidor_revenda=='R' and $login_fabrica==24){
	$xml .= "<produto>\n";     
      $xml .= "<codigo>196</codigo>\n";                  
	  $xml .= "<nome>Revisão Estoque de Loja</nome>\n";
      $xml .= "</produto>\n";    
	}
	if($login_fabrica==6 and $posto_pede_garantia_antecipada == "t"){
		$rsql = "SELECT servico_realizado, descricao from tbl_servico_realizado where fabrica=$login_fabrica and peca_estoque is true";
		$rres = pg_exec($con,$rsql);
		if(pg_numrows($rres)>0){
			for($r=0;pg_numrows($rres)>$r;$r++){
				$rservico_realizado = pg_result($rres,$r,servico_realizado);
				$rdescricao = pg_result($rres,$r,descricao);
				$xml .= "<produto>\n";     
				$xml .= "<codigo>$rservico_realizado</codigo>\n";                  
				$xml .= "<nome>$rdescricao</nome>\n";
				$xml .= "</produto>\n";  
			}
		}

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
		and tbl_servico_realizado.peca_estoque  is not true
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
	  $xml .= "<nome>".$descricao."</nome>\n";
      $xml .= "</produto>\n";    
   }//FECHA FOR                 
   if($consumidor_revenda=='R' and $login_fabrica==24){
	$xml .= "<produto>\n";     
      $xml .= "<codigo>196</codigo>\n";                  
	  $xml .= "<nome>Revisão Estoque de Loja</nome>\n";
      $xml .= "</produto>\n";    
	}
	if($login_fabrica==6 and $posto_pede_garantia_antecipada == "t"){
		$rsql = "SELECT servico_realizado, descricao from tbl_servico_realizado where fabrica=$login_fabrica and peca_estoque is true";
		$rres = pg_exec($con,$rsql);
		if(pg_numrows($rres)>0){
			for($r=0;pg_numrows($rres)>$r;$r++){
				$rservico_realizado = pg_result($rres,$r,servico_realizado);
				$rdescricao = pg_result($rres,$r,descricao);
				$xml .= "<produto>\n";     
				$xml .= "<codigo>$rservico_realizado</codigo>\n";                  
				$xml .= "<nome>$rdescricao</nome>\n";
				$xml .= "</produto>\n";  
			}
		}

	}
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 

}//FECHA IF (row)                                               

echo $xml;            
?>
