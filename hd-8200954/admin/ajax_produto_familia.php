<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$familia = $_GET["familia"];
$marca   = $_GET["marca"];

$aux_marca = " AND 1=1";
 
if(strlen($marca)>0 and ($login_fabrica==3 or $login_fabrica==81)){
	$aux_marca = " AND tbl_produto.marca = $marca ";
}

if(!empty($familia)){
$sql ="SELECT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto
		FROM tbl_produto
		JOIN tbl_familia USING(familia)
		WHERE tbl_produto.familia = $familia
		AND   tbl_familia.fabrica = $login_fabrica
		AND   tbl_produto.lista_troca IS TRUE
		and (tbl_produto.ativo is true OR tbl_produto.uso_interno_ativo is true)
		$aux_marca
		ORDER BY tbl_produto.referencia";
$resD = pg_exec ($con,$sql);



$row = pg_numrows ($resD);
}
//XML
$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
$xml .= "<produtos>\n";
if(!in_array($login_fabrica, array(30, 101))){
    $xml .= "<produto>\n";
    $xml .= "<codigo>-1</codigo>\n";
    $xml .= "<nome>RESSARCIMENTO FINANCEIRO</nome>\n";
    $xml .= "</produto>\n";
}
if($row) {
	//PERCORRE ARRAY
	for($i=0; $i<$row; $i++) {
	
		if($i == 0){

			if ($login_fabrica == 81) {
				$xml .= "<produto>\n";
				$xml .= "<codigo>-2</codigo>\n";
				$xml .= "<nome>AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA</nome>\n";
				$xml .= "</produto>\n"; 
			}
		}

		$produto    = pg_result($resD, $i, 'produto');
		$referencia = pg_result($resD, $i, 'referencia');
		$descricao  = pg_result($resD, $i, 'descricao');
		$xml .= "<produto>\n";
		$xml .= "<codigo>".$produto."</codigo>\n";
		$xml .= "<nome><![CDATA[$referencia - ".$descricao."]]></nome>\n";
		$xml .= "</produto>\n"; 
	}//FECHA FOR
}//FECHA IF (row)
$xml.= "</produtos>\n";
//CABEÇALHO
Header("Content-type: application/xml; charset=iso-8859-1"); 
echo $xml;
?>
