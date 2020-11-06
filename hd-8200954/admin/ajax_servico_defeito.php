<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

$defeito = trim($_GET["defeito"]);


if($login_fabrica == 131) {
	$defeito= preg_replace('/\[|\]/','', $defeito);
	$defeito= str_replace("\\\"","'", $defeito);
	$defeito= str_replace("\"","'", $defeito);
	$sql = "SELECT  array_to_string(array_agg(defeito), ',')
		FROM tbl_defeito
		WHERE fabrica = $login_fabrica
		AND   codigo_defeito in ($defeito)";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0) {
		$defeito = "(".pg_fetch_result($res,0,0).")";

	}
}

if($login_fabrica == 131){
	$where = " AND   tbl_defeito_servico_realizado.defeito in $defeito ";
}else{
	$where = " AND   tbl_defeito_servico_realizado.defeito = $defeito ";
}

$sql = "SELECT DISTINCT tbl_servico_realizado.descricao                   ,
				tbl_servico_realizado.servico_realizado
			  FROM  tbl_defeito_servico_realizado
			  JOIN  tbl_servico_realizado using(servico_realizado)
			  JOIN  tbl_defeito on tbl_defeito.defeito = tbl_defeito_servico_realizado.defeito

			  AND   tbl_defeito.fabrica = $login_fabrica
			WHERE tbl_defeito.ativo IS TRUE
			AND   tbl_defeito_servico_realizado.ativo IS TRUE
			AND   tbl_servico_realizado.ativo IS TRUE
			$where
			ORDER BY tbl_servico_realizado.descricao";
$resD = pg_exec($con,$sql);
if(pg_numrows($resD)>0){
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";
   //PERCORRE ARRAY
   for($i=0; $i<pg_numrows($resD); $i++) {

      $servico    = pg_result($resD,$i,'servico_realizado');
	  $descricao = pg_result($resD,$i,'descricao');
	  $xml .= "<produto>\n";
      $xml .= "<codigo>".$servico."</codigo>\n";
	  $xml .= "<nome>" ;
	  $xml .= $descricao."</nome>\n";
      $xml .= "</produto>\n";
   }//FECHA FOR

   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1");
}

echo $xml;
?>
