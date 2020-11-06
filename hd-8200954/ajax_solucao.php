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

$numOs = $_GET['num_os'];

	//pegar o login fabrica

//171607
$sql_controla_estoque = "SELECT controla_estoque
                        FROM tbl_posto_fabrica
						WHERE fabrica=$login_fabrica
						AND posto=$login_posto;";

$res_controla_estoque = pg_query($con,$sql_controla_estoque);
$controla_estoque = pg_result($res_controla_estoque,0,'controla_estoque');


#HD 171607
if($login_fabrica == 3 && $controla_estoque != 't'){
    $sql_solucao = " AND tbl_solucao.descricao <> 'Troca de peça do estoque interno'";
}

if(!in_array($login_fabrica,array(74,96))){
	if(empty($defeito_constatado)) {
	   $xml .= "<option value=''>SELECIONE DEFEITO CONSTATADO</option>\n";
	   echo $xml;
           exit;
	}
}

$sql = "SELECT pedir_defeito_reclamado_descricao FROM tbl_fabrica WHERE fabrica = $login_fabrica; ";
$res = pg_exec($con,$sql);
$pedir_defeito_reclamado_descricao = pg_result($res,0,pedir_defeito_reclamado_descricao);

if($pedir_defeito_reclamado_descricao == 'f' ){

	$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
					tbl_solucao.descricao 
			FROM tbl_diagnostico 
			JOIN tbl_solucao on tbl_diagnostico.solucao=tbl_solucao.solucao
			WHERE tbl_diagnostico.fabrica = $login_fabrica 
			 AND tbl_diagnostico.ativo='t'  
			AND   tbl_diagnostico.defeito_constatado = $defeito_constatado
			$sql_solucao ";
			
			if (strlen($defeito_reclamado) > 0) {

				$sql .= " AND tbl_diagnostico.defeito_reclamado = defeito_reclamado"; 
			}

			$sql .= " AND tbl_diagnostico.linha=$produto_linha ";
			if(strlen($produto_familia)>0){
				$sql.=" and tbl_diagnostico.familia=$produto_familia ";
			}
	$sql .=" ORDER BY tbl_solucao.descricao ";


}else{
	if($login_fabrica == 74 or $login_fabrica == 96){
		$sql = "SELECT DISTINCT(tbl_diagnostico.solucao), 
						tbl_solucao.descricao 
					  FROM tbl_diagnostico 
					  JOIN tbl_solucao on tbl_diagnostico.solucao=tbl_solucao.solucao
					WHERE tbl_diagnostico.ativo = 't'
					AND   tbl_diagnostico.fabrica = $login_fabrica
					AND tbl_diagnostico.familia=$produto_familia";
	} else {

		$def = explode("_", $defeito_constatado);

		$def = implode(",", $def);

		$sql ="SELECT DISTINCT(tbl_diagnostico.solucao), 
						tbl_solucao.descricao 
				FROM tbl_diagnostico 
				JOIN tbl_solucao on tbl_diagnostico.solucao=tbl_solucao.solucao
				WHERE	tbl_diagnostico.ativo = 't'
				AND tbl_diagnostico.defeito_constatado IN ($def) 
				$sql_solucao
				AND	tbl_diagnostico.fabrica = $login_fabrica
				AND	tbl_diagnostico.linha=$produto_linha";
		if(strlen($produto_familia)>0){
			$sql.=" and tbl_diagnostico.familia=$produto_familia ";
		}
		$sql .=" ORDER BY tbl_solucao.descricao ";
	}
}

//if (is_numeric($defeito_constatado)) {
	$resD = pg_exec ($con,$sql) ;

if($login_fabrica == 3 and pg_num_rows($resD) ==0 ){
	$sql = "SELECT descricao, solucao FROM tbl_solucao join tbl_os on tbl_os.solucao_os = tbl_solucao.solucao WHERE tbl_solucao.fabrica = $login_fabrica and tbl_os.os = $numOs" ;
	$resD = pg_exec ($con,$sql) ;
}

//}
//echo "<BR>$sql"; 
$row = pg_numrows ($resD);    
if($row) {                
   //XML
   //PERCORRE ARRAY            
   for($i=0; $i<$row; $i++) {  
   
      $solucao    = pg_result($resD, $i, 'solucao'); 
	  $descricao = pg_result($resD, $i, 'descricao'); 
      $xml .= "<option value='$solucao'>".$descricao."</option>\n";                  
   }//FECHA FOR                 
   //CABEÇALHO
}//FECHA IF (row)                                               
echo $xml;            
?>
