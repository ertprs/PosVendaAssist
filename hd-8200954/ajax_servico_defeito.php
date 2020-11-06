<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_usuario.php";

$defeito = trim($_GET["defeito"]);
$peca = trim($_GET["peca"]);

if($login_fabrica == 131) {
	$defeito= preg_replace('/\[|\]/','', $defeito);
	$defeito= str_replace("\\\"","'", $defeito);
	$defeito= str_replace("\"","'", $defeito);
	$sql = "SELECT  defeito
		FROM tbl_defeito
		WHERE fabrica = $login_fabrica
		AND   codigo_defeito in ($defeito)";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0) {
		$defeito = pg_fetch_all_columns($res);
		$defeito = implode(",",$defeito);
	}
}

$sql = "SELECT DISTINCT  tbl_servico_realizado.descricao                   ,
			   tbl_servico_realizado.servico_realizado
			FROM  tbl_defeito_servico_realizado
			JOIN  tbl_servico_realizado using(servico_realizado)
			JOIN  tbl_defeito on tbl_defeito.defeito = tbl_defeito_servico_realizado.defeito
			AND   tbl_defeito_servico_realizado.defeito in ($defeito)
			AND   tbl_defeito.fabrica = $login_fabrica
		   WHERE tbl_defeito.ativo IS TRUE
		   AND   tbl_defeito_servico_realizado.ativo IS TRUE
		   AND   tbl_servico_realizado.ativo IS TRUE
		   ORDER BY tbl_servico_realizado.descricao";


if ($login_fabrica == 120 or $login_fabrica == 201) {
    $sql = "
        SELECT  tbl_servico_realizado.descricao,
                tbl_servico_realizado.servico_realizado
        FROM    tbl_servico_realizado
        JOIN    tbl_peca_defeito USING(servico_realizado)
        JOIN    tbl_peca USING(peca)
        WHERE   defeito                 = $defeito
        AND     tbl_peca.referencia     = '$peca'
        AND     tbl_peca.fabrica        = $login_fabrica
        AND     tbl_peca_defeito.ativo  IS TRUE
    ";
}

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
	  $xml .= html_entity_decode($descricao)."</nome>\n";
      $xml .= "</produto>\n";
   }//FECHA FOR

   $xml.= "</produtos>\n";
   //CABEÇALHO
   header("Content-type: application/xml; charset=iso-8859-1");
}

echo $xml;
?>
