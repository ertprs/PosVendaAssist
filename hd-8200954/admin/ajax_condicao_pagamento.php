<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$codigo_posto = $_GET["codigo_posto"]; 

$sql = "SELECT id_condicao, condicao 
			FROM tbl_black_posto_condicao 
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.fabrica=1 
			AND tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto 
		WHERE tbl_posto_fabrica.codigo_posto = $codigo_posto";
$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
$id_condicao = pg_result($res, 0, id_condicao);
$condicao = pg_result($res,0,condicao);
}else{
$id_condicao = "51";
$condicao = "30DD (sem financeiro)";

}
 //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";
   //PERCORRE ARRAY
	  $xml .= "<produto>\n";
      $xml .= "<codigo>".$id_condicao."</codigo>\n";
	  $xml .= "<nome>".$condicao."</nome>\n";
      $xml .= "</produto>\n";
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
echo $xml;
?>
