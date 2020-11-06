<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

//RECEBE PARÃMETRO
//defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia

// $produto_referencia = $_POST["produto_referencia"];
//echo "<BR>constatado ";
$defeito_reclamado = $_GET["defeito_reclamado"];
//echo "<BR>familia ";
$produto_familia = $_GET["produto_familia"]; 
//echo "<BR>linha ";
$produto_linha = $_GET["produto_linha"]; 
//pegar o login fabrica
$tipo_atendimento
if($login_fabrica <> 15){
	$sql ="SELECT 	DISTINCT(tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado=tbl_defeito_constatado.defeito_constatado
			WHERE tbl_diagnostico.defeito_reclamado=$defeito_reclamado
			AND tbl_diagnostico.linha = $produto_linha
			AND tbl_diagnostico.ativo = 't' ";
		if(strlen($produto_familia)>0){$sql.=" and tbl_diagnostico.familia=$produto_familia";}
	$sql .=" ORDER BY tbl_defeito_constatado.descricao";
}else{
	$sql ="SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			WHERE tbl_diagnostico.linha = $produto_linha
			AND tbl_diagnostico.ativo = 't'
			AND tbl_diagnostico.familia = $produto_familia";
			if ($login_fabrica == 19) {
				//hd 3347
				$sql .= " AND tbl_defeito_constatado.defeito_constatado <> 10820 "; 

				//hd 3470
				if ($produto_linha <> 261) $sql .= " AND tbl_defeito_constatado.defeito_constatado <> 10823 "; 
			}
			$sql .= "
			ORDER BY tbl_defeito_constatado.descricao";
}
if ($tipo_atendimento==3){
		$sql = "SELECT tbl_defeito_constatado.* 
				FROM tbl_defeito_constatado 
				WHERE fabrica = $login_fabrica and defeito_constatado in (10021,10546,10547,10548,10549,10550,10551,10552,10545)";
}//hd1414 takashi 07-03-07
$resD = pg_exec ($con,$sql) ;
//echo "$sql";

//echo "<BR>$sql"; 
$row = pg_numrows ($res);
if($row) {
	//XML
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<produtos>\n";
	//PERCORRE ARRAY
	for($i=0; $i<$row; $i++) {

		$defeito_constatado    = pg_result($resD, $i, 'defeito_constatado');
		$descricao             = pg_result($resD, $i, 'descricao'); 
		$xml .= "<produto>\n";
		$xml .= "<codigo>".$defeito_constatado."</codigo>\n";
		$xml .= "<nome>".$descricao."</nome>\n";
		$xml .= "</produto>\n";
	}//FECHA FOR
	$xml.= "</produtos>\n";
	//CABEÇALHO
	Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)
echo $xml;
?>
