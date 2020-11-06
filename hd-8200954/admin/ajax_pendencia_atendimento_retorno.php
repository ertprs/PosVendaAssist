<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

if ($_POST["atuliza_atendimentos"] == "true") {
	$atendente = $cook_admin;

    $sql = "select tbl_hd_chamado_extra.hd_chamado,
		regexp_replace(array_campos_adicionais,E'^.+\"data_retorno\":\"\(\\\\d{4}\-\\\\d{2}\-\\\\d{2}\)\".+$',E'\\\\1')::date as data_retorno 
			INTO TEMP tmp_retorno_$atendente
			FROM tbl_hd_chamado_extra JOIN tbl_hd_chamado USING(hd_chamado) 
			WHERE tbl_hd_chamado.fabrica =$login_fabrica
			AND tbl_hd_chamado.fabrica_responsavel =$login_fabrica
			AND tbl_hd_chamado.status = 'Retorno'
			AND array_campos_adicionais ~* 'data_retorno'
			and tbl_hd_chamado.atendente = $atendente;

			SELECT * FROM tmp_retorno_$atendente
				WHERE data_retorno <= CURRENT_DATE;
";
		//echo ($sql);
	$res = pg_query($con, $sql);

	$rows = pg_num_rows($res);
	$rows = empty($rows) ? 0 : $rows;
	$retorno = array(
		"qtde"         => $rows,
		"atendimentos" => array()
	);

	if ($rows > 0) {
		for ($i = 0; $i < $rows; $i++) { 
			$atendimento             = pg_fetch_result($res, $i, "hd_chamado");
			$xdata_retorno			 = pg_fetch_result($res, $i, "data_retorno");
			list($d, $m, $a) = explode("-", $xdata_retorno);

			$data_retorno = "{$a}/{$m}/{$d}";

				$retorno["atendimentos"][] = array(
				"atendimento"     => $atendimento,
				"data_retorno" 	  => $data_retorno
			);
		}
	}

	function sortFunction( $a ) {
	    return strtotime($a["data_retorno"]);
	}

	usort($retorno["atendimentos"], "sortFunction");

	echo json_encode($retorno);
}

?>
