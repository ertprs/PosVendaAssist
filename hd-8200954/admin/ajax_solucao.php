<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

//RECEBE PARÃMETRO                     
// $produto_referencia = $_POST["produto_referencia"];           
//echo "<BR>constatado ";
$defeito_constatado = $_GET["defeito_constatado"]; 
//echo "<BR>reclamado ";
$defeito_reclamado = $_GET["defeito_reclamado"]; 
//echo "<BR>familia ";
$produto_familia = $_GET["produto_familia"]; 
//echo "<BR>linha ";
$produto_linha = $_GET["produto_linha"]; 
	//pegar o login fabrica
  
if($login_fabrica <> 7 AND $login_fabrica <> 15 AND $login_fabrica<>30 AND $login_fabrica<>50 AND $login_fabrica<>51 AND $login_fabrica <>59 and $login_fabrica <> 2 and $login_fabrica <> 5 and $login_fabrica <> 90 and $login_fabrica <> 96 and $login_fabrica <> 117){
	if ((strlen($defeito_constatado) > 0) and (strlen($produto_linha) > 0 )){
		$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
				tbl_solucao.descricao 
				FROM tbl_diagnostico 
				JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
				WHERE tbl_diagnostico.defeito_constatado = $defeito_constatado 
				AND   tbl_diagnostico.linha=$produto_linha";
		if(strlen($defeito_reclamado)>0){$sql.=" AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado";}
		if(strlen($produto_familia)>0){$sql.=" and tbl_diagnostico.familia=$produto_familia";}
		$sql .=" ORDER BY tbl_solucao.descricao";
	}
} else if($login_fabrica == 96 OR $login_fabrica == 117){
	if (strlen($produto_familia) > 0 ){
		$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
				tbl_solucao.descricao 
				FROM tbl_diagnostico 
				JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
				WHERE tbl_diagnostico.familia=$produto_familia
				ORDER BY tbl_solucao.descricao";
	}
}else{
	if ((strlen($defeito_constatado) > 0) and (strlen($produto_linha) > 0 )){

		$def = explode("_", $defeito_constatado);

		$def = implode(",", $def);

		$sql ="SELECT DISTINCT (tbl_solucao.descricao),
					tbl_diagnostico.solucao
			FROM tbl_diagnostico
			JOIN tbl_solucao on tbl_diagnostico.solucao = tbl_solucao.solucao 
			WHERE tbl_diagnostico.defeito_constatado IN ($def) 
			AND   tbl_diagnostico.linha = $produto_linha ";
		if(strlen($produto_familia)>0) {$sql.=" and tbl_diagnostico.familia=$produto_familia";}
		$sql .=" ORDER BY tbl_solucao.descricao";
	}
}

if (strlen($sql) > 0 ){
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
		$solucao    = pg_result($resD, $i, 'solucao'); 
		$descricao = pg_result($resD, $i, 'descricao'); 
		$xml .= "<produto>\n";    
		$xml .= "<codigo>".$solucao."</codigo>\n";                  
		$xml .= "<nome>".$descricao."</nome>\n";
		$xml .= "</produto>\n";    
	   }//FECHA FOR                 
	   $xml.= "</produtos>\n";
	   //CABEÇALHO
	   Header("Content-type: application/xml; charset=iso-8859-1"); 
	}//FECHA IF (row)                                               
	echo $xml;            
}
?>
