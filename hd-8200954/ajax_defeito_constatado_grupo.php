<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
if(strlen($fabrica)==0)include 'autentica_usuario.php';
else                   $login_fabrica = 45; //usado para login UNICO

//RECEBE PARÃMETRO
//defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia

// $produto_referencia = $_POST["produto_referencia"];
//echo "<BR>constatado ";

$defeito_constatado_grupo = $_GET["defeito_constatado_grupo"]; 
$familia                  = $_GET["familia"]; 

if ($defeito_constatado_grupo) {
	$sql ="SELECT	tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao        
					FROM tbl_defeito_constatado
					JOIN tbl_diagnostico USING (fabrica,defeito_constatado)
					JOIN tbl_posto_fabrica USING(tabela_mao_obra)
					WHERE 
					tbl_defeito_constatado.defeito_constatado_grupo = $defeito_constatado_grupo
					AND tbl_defeito_constatado.ativo
					AND tbl_diagnostico.ativo
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto = $login_posto
					AND tbl_diagnostico.familia = $familia";

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
			$defeito_constatado = pg_result($resD, $i, 'defeito_constatado');
			$descricao					= pg_result($resD, $i, 'descricao'); 
			$descricao = str_replace("&","&amp;",$descricao);
			$xml .= "<produto>\n";
			$xml .= "<codigo>".$defeito_constatado."</codigo>\n";
			$xml .= "<nome>".$descricao."</nome>\n";
			$xml .= "</produto>\n";
		}//FECHA FOR
		$xml.= "</produtos>\n";
		//CABEÇALHO
		header("Content-type: application/xml; charset=iso-8859-1"); 
	}//FECHA IF (row)
	echo $xml;
}
?>
