<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

//RECEBE PARaMETRO
// $produto_referencia = $_POST["produto_referencia"];
$produto_referencia = $_GET["produto_referencia"]; 
$tipo_atendimento    = $_GET["tipo_atendimento"];

if ($login_fabrica == 42) {
	$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
	$res = pg_query($con, $sql);

	$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
}
//pegar o login fabrica
$sql="SELECT familia, 
			fabrica, 
			produto, 
			linha 
		FROM tbl_produto 
		JOIN tbl_linha USING(linha) 
		WHERE upper(referencia)=upper('$produto_referencia') 
		AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
if($login_fabrica==24){
	$sql="SELECT familia,
				fabrica,
				produto,
				linha
			FROM tbl_produto 
			JOIN tbl_linha USING(linha) 
			WHERE referencia like '$produto_referencia' LIMIT 1";
}
$res = pg_exec ($con,$sql);
$familia        = pg_result ($res,0,'familia') ;
$linha          = pg_result ($res,0,'linha') ;
$login_fabrica  = pg_result ($res,0,'fabrica') ;
$cod_produto    = pg_result ($res,0,'produto') ;
//echo "familia: $sql";
/*		$nosso_ip = include("../nosso_ip.php");
		if(($ip=='201.43.245.148' OR ($ip==$nosso_ip)) ){
			if($linha == 382) $linha = 317;
			if($linha == 401) $linha = 307;
			if($linha == 390) $linha = 317;
		}
*/
//PROCURA POR LINHA E FAMILIA
if($login_fabrica <> 15){
	$sql = "SELECT DISTINCT(tbl_diagnostico.defeito_reclamado),
					tbl_defeito_reclamado.descricao,
					tbl_defeito_reclamado.entrega_tecnica
			FROM tbl_diagnostico
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE tbl_diagnostico.fabrica=$login_fabrica
			AND   tbl_defeito_reclamado.fabrica=$login_fabrica";

	if ($login_fabrica == 42) {
		if ($entrega_tecnica == "t") {
			$sql .= " AND tbl_defeito_reclamado.entrega_tecnica is true ";
		} else if ($entrega_tecnica == "f") {
			$sql .= " AND tbl_defeito_reclamado.entrega_tecnica is false ";
		}
	}

	if (!empty($familia)) { 
		$sql .=" AND tbl_diagnostico.familia=$familia ";
	}

	if (strlen($linha) > 0 && !in_array($login_fabrica, array(42,74,86,94,95,101,115,116,117,120))) {
		$sql .=" AND tbl_diagnostico.linha=$linha ";
	}

	$sql .= " and tbl_defeito_reclamado.ativo='t' and tbl_diagnostico.ativo='t' ";

	$sql .= " order by tbl_defeito_reclamado.descricao ";

	//echo $sql;
}else{
	$sql = "SELECT DISTINCT(tbl_defeito_reclamado.descricao), 
					tbl_defeito_reclamado.defeito_reclamado 
			FROM tbl_defeito_reclamado 
			WHERE tbl_defeito_reclamado.fabrica = $login_fabrica 
			AND tbl_defeito_reclamado.ativo = 't' 
			ORDER BY tbl_defeito_reclamado.descricao ";
}

$resD = pg_exec ($con,$sql) ;
$row = pg_numrows ($resD); 

$row = pg_numrows ($resD);
if($row) {
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";
   //PERCORRE ARRAY
   for($i=0; $i<$row; $i++) {
	$defeito_reclamado = pg_result($resD, $i, 'defeito_reclamado');
	$descricao         = pg_result($resD, $i, 'descricao');
	$entrega_tecnica   = pg_result($resD, $i, "entrega_tecnica");
	$xml .= "<produto>\n";
    $xml .= "<codigo>".$defeito_reclamado."</codigo>\n";
	$xml .= "<nome>".$descricao."</nome>\n";
	$xml .= "<rel>".$entrega_tecnica."</rel>\n";
    $xml .= "</produto>\n";
   }//FECHA FOR
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)
echo $xml;
?>
