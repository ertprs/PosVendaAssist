<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
//RECEBE PARÃMETRO
//defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia

// $produto_referencia = $_POST["produto_referencia"];
$defeito_reclamado = $_GET["defeito_reclamado"];
$produto_familia   = $_GET["produto_familia"];
$produto_linha     = $_GET["produto_linha"];
$tipo_atendimento  = $_GET["tipo_atendimento"];

$sql = "SELECT pedir_defeito_reclamado_descricao FROM tbl_fabrica WHERE fabrica = $login_fabrica; ";
$res = pg_exec($con,$sql);

$pedir_defeito_reclamado_descricao = pg_result($res,0,'pedir_defeito_reclamado_descricao');

if ($pedir_defeito_reclamado_descricao == 'f') {

	if($login_fabrica == 19){
		$campo = ", regexp_replace(tbl_defeito_constatado.codigo,'\\D','','g')::integer AS codigo";
		$cond_19 = "    AND tbl_diagnostico.defeito_constatado IS NOT NULL
                        AND tbl_diagnostico.familia IS NOT NULL
                        AND tbl_diagnostico.solucao IS NULL
                        AND tbl_diagnostico.defeito_reclamado IS NULL";
	}


	$sql ="SELECT DISTINCT(tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao
					{$campo}
			FROM tbl_diagnostico
                JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                    and tbl_defeito_constatado.ativo <> 'f'
			WHERE tbl_diagnostico.ativo = 't'
			$cond_19
			";

		if ($login_fabrica == 24 or $login_fabrica == 74) {
			$sql.="AND tbl_defeito_constatado.ativo ='t'";
		}

		if($login_fabrica != 95  && $login_fabrica !=86 && $login_fabrica !=74 AND $login_fabrica <> 19) {

			$sql .= " AND tbl_diagnostico.linha = $produto_linha ";
			if ($login_fabrica <> 74 ){
				if(!empty($defeito_reclamado)){
					$sql .="
					  AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado ";
				}
			}

		}

		if (strlen($produto_familia) > 0) {
			$sql.=" AND tbl_diagnostico.familia = $produto_familia";
		}
	if($login_fabrica == 19){
		$sql .=" ORDER BY regexp_replace(tbl_defeito_constatado.codigo,'\\D','','g')::integer ";
	}else{
		$sql .=" ORDER BY tbl_defeito_constatado.descricao";
	}

} else {

	$sql ="SELECT DISTINCT (tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao
			FROM tbl_diagnostico
                JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                    and tbl_defeito_constatado.ativo <> 'f'
			WHERE tbl_diagnostico.linha = $produto_linha
			AND  tbl_diagnostico.ativo = 't'
			AND tbl_diagnostico.familia = $produto_familia
			AND tbl_defeito_constatado.ativo ='t'";

	if ($login_fabrica == 24 or $login_fabrica == 74) {
		$sql.="AND tbl_defeito_constatado.ativo ='t'";
	}

	$sql.="ORDER BY tbl_defeito_constatado.descricao";

}
$resD = pg_exec($con,$sql) ;
$row  = pg_numrows($resD);

if ($row > 0) {
	//XML
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<produtos>\n";
	//PERCORRE ARRAY
	for($i=0; $i<$row; $i++) {
		$defeito_constatado    = pg_result($resD, $i, 'defeito_constatado');
		$descricao             = pg_result($resD, $i, 'descricao');
		$codigo                = pg_result($resD, $i, 'codigo');
		$descricao = str_replace("&","&amp;",$descricao);
		$xml .= "<produto>\n";
		$xml .= "<codigo>".$defeito_constatado."</codigo>\n";
		if($login_fabrica==30){
			$xml .= "<nome>".$codigo."-".$descricao."</nome>\n";
		}else if($login_fabrica == 19){
			$xml .= "<nome>".$codigo."-".$descricao."</nome>\n";
		}else{
			$xml .= "<nome>".$descricao."</nome>\n";
		}
		$xml .= "</produto>\n";
	}//FECHA FOR
	$xml.= "</produtos>\n";
	//CABEÇALHO
	header("Content-type: application/xml; charset=iso-8859-1");
}//FECHA IF (row)
echo $xml;
?>
