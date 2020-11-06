<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

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

$sql = "SELECT pedir_defeito_reclamado_descricao FROM tbl_fabrica WHERE fabrica = $login_fabrica; ";
$res = pg_exec($con,$sql);
$pedir_defeito_reclamado_descricao = pg_result($res,0,pedir_defeito_reclamado_descricao);

if (strlen($defeito_constatado)>0) {
	if($pedir_defeito_reclamado_descricao == 'f'){

			$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
							tbl_solucao.descricao 
					FROM tbl_diagnostico 
					JOIN tbl_solucao on tbl_diagnostico.solucao=tbl_solucao.solucao
					WHERE tbl_diagnostico.ativo = 't' and tbl_diagnostico.defeito_constatado=$defeito_constatado 
					AND tbl_diagnostico.defeito_reclamado=$defeito_reclamado 
					AND tbl_diagnostico.linha=$produto_linha";
			if(strlen($produto_familia)>0){$sql.=" and tbl_diagnostico.familia=$produto_familia";}
			$sql .=" ORDER BY tbl_solucao.descricao";

	}else{
			$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
							tbl_solucao.descricao 
					FROM tbl_diagnostico 
					JOIN tbl_solucao on tbl_diagnostico.solucao=tbl_solucao.solucao
					WHERE tbl_diagnostico.ativo = 't' and tbl_diagnostico.defeito_constatado=$defeito_constatado 
					AND tbl_diagnostico.linha=$produto_linha";
			if(strlen($produto_familia)>0){$sql.=" and tbl_diagnostico.familia=$produto_familia";}
			$sql .=" ORDER BY tbl_solucao.descricao";

	}

	$resD = pg_exec ($con,$sql) ;


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


