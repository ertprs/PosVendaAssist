<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
//RECEBE PARÃMETRO
//defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
//header("Pragma: no-cache, public");

 
// $produto_referencia = $_POST["produto_referencia"];           
//echo "<BR>constatado ";
$defeito_reclamado = $_GET["defeito_reclamado"];
//echo "<BR>familia ";
$produto_familia = $_GET["produto_familia"]; 
//echo "<BR>linha ";
$produto_linha = $_GET["produto_linha"]; 
	//pegar o login fabrica


	if($login_fabrica <> 15 and $login_fabrica<>30 and $login_fabrica<>50 and $login_fabrica <> 72 and $login_fabrica<>51 and $login_fabrica<>59 and $login_fabrica <> 2){
		$sql ="SELECT DISTINCT
						tbl_diagnostico.defeito_constatado,
						tbl_defeito_constatado.descricao  ,
					tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado=tbl_defeito_constatado.defeito_constatado
				WHERE tbl_diagnostico.defeito_reclamado=$defeito_reclamado
				AND tbl_diagnostico.linha=$produto_linha";
			if(strlen($produto_familia)>0){$sql.=" and tbl_diagnostico.familia=$produto_familia";}
		$sql .=" ORDER BY tbl_defeito_constatado.descricao";
	}else {
		if ($login_fabrica == 2) {
			$sql ="SELECT
					tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao         ,
					tbl_defeito_constatado.codigo
				FROM tbl_defeito_constatado
				WHERE  tbl_defeito_constatado.fabrica = 2
				ORDER BY tbl_defeito_constatado.descricao";
		}
		else {
		$sql ="SELECT DISTINCT 
					tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao         ,
					tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado using(defeito_constatado)
				WHERE   tbl_diagnostico.linha = $produto_linha
				AND   tbl_diagnostico.familia = $produto_familia
				ORDER BY tbl_defeito_constatado.descricao ";
		}
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
   
		$defeito_constatado    = pg_result($resD, $i, 'defeito_constatado');
		$descricao = pg_result($resD, $i, 'descricao'); 
		$codigo    = pg_result($resD, $i, 'codigo'); 
		$xml .= "<produto>\n";     
		$xml .= "<codigo>".$defeito_constatado."</codigo>\n";
		if($login_fabrica==30) $xml .= "<nome>".$codigo."-".$descricao."</nome>\n";
		else                   $xml .= "<nome>".$descricao."</nome>\n";
		$xml .= "</produto>\n";    
   }//FECHA FOR                 
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)                                               
echo $xml;            
?>
