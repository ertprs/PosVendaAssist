<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$extrato = $_GET['extrato'];
if(strlen($extrato)>0){
	$sql = "SELECT tbl_extrato.extrato,
				tbl_posto_fabrica.codigo_posto,
				to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as data_geracao,
				tbl_extrato.total,
				tbl_posto.nome,
				count(tbl_os_extra.os) as qtde
			from tbl_extrato
			join tbl_os_extra on tbl_extrato.extrato = tbl_os_extra.extrato
			join tbl_posto_fabrica on tbl_extrato.posto = tbl_posto_fabrica.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto on tbl_posto.posto = tbl_extrato.posto
			where tbl_extrato.extrato = $extrato
			GROUP BY tbl_extrato.extrato,
			tbl_posto_fabrica.codigo_posto,
			data_geracao,
			tbl_extrato.total,
			tbl_posto.nome";
	$res = pg_exec($con,$sql);
	//echo $sql;
	if(pg_numrows($res)>0){

		$extrato            = trim (pg_result($res,0,extrato));
		$codigo_posto       = trim (pg_result($res,0,codigo_posto));
		$total              = trim (pg_result($res,0,total));
		$total              = number_format ($total,2,',','.');
		$data_geracao       = trim (pg_result($res,0,data_geracao));
		$qtde               = trim (pg_result($res,0,qtde));
		$codigo_posto      .=" - ".trim (pg_result($res,0,nome));
		$sqll = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = $extrato";
		$ress = pg_exec($con,$sqll);
		if(pg_numrows($ress)>0){
			$codigo_posto       = "EXTRATO JA FOI PAGO";
		}
		echo $codigo_posto.";".$data_geracao.";".$total.";".$qtde;
	}
}
?>