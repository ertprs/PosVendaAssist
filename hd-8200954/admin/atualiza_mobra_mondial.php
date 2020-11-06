<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

pg_query($con,"BEGIN");

echo $sql = "SELECT posto, valor FROM tmp_mobra_mondial WHERE posto IS NOT NULL";
$res = pg_query($con,$sql);

for($i = 0; $i < pg_num_rows($res); $i++){

	$posto = pg_fetch_result($res, $i, 'posto');
	$valor_mao_obra = pg_fetch_result($res, $i, 'valor');

	$sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica where posto = {$posto} and fabrica = {$login_fabrica}";
	$resParametrosAdicionais = pg_query($con,$sqlParametrosAdicionais);
	$parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais");

	$valor_mao_obra = str_replace(",",".",$valor_mao_obra);

	if(strlen($parametros_adicionais) > 0) {
		$parametros_adicionais                         = json_decode($parametros_adicionais, true);
		$parametros_adicionais['qtde_os_item']         = $_POST['qtde_os_item'];
		$parametros_adicionais['valor_extrato']        = $_POST['valor_extrato'];
		$parametros_adicionais['valor_mao_obra']       = $valor_mao_obra;
		$parametros_adicionais['digito_agencia']       = $_POST['digito_agencia'];
		$parametros_adicionais['digito_conta']         = $_POST['digito_conta'];
		$parametros_adicionais['extrato_mais_3_meses'] = $extrato_mais_3_meses;
		$parametros_adicionais = json_encode($parametros_adicionais);
	}else{
		$parametros_adicionais = json_encode(array(
			"qtde_os_item"         => "",
			"valor_extrato"        => "",
			"valor_mao_obra"       => $valor_mao_obra,
			"extrato_mais_3_meses" => "",
			"digito_agencia"       => "",
			"digito_conta"         => ""
		));
	}

	$sqlParametrosAdicionais = "";
	$sqlParametrosAdicionais = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '".$parametros_adicionais."' WHERE posto = {$posto} and fabrica = {$login_fabrica}";
	pg_query($con,$sqlParametrosAdicionais);

	$sqlFamilia = "DELETE FROM tbl_excecao_mobra WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND familia IS NOT NULL ";
	pg_query($con, $sqlFamilia);

	if(strlen($valor_mao_obra) > 0) {
		$sqlFamilia = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND mao_de_obra_familia < {$valor_mao_obra}";
		$resFamilia = pg_query($con,$sqlFamilia);

		if(pg_num_rows($resFamilia) > 0){

			while($objeto_familia = pg_fetch_object($resFamilia)){
				$sqlFamilia = "INSERT INTO tbl_excecao_mobra (fabrica, posto, familia, mao_de_obra) VALUES 
					($login_fabrica,$posto,".$objeto_familia->familia.",$valor_mao_obra)";

				pg_query($con,$sqlFamilia);
			}
		}
	}
	
	if(strlen(pg_last_error()) > 0){
		$msg_erro[] = pg_last_error();
	}else{
		echo $i." - Posto - ".$posto." atualizado <br />";
	}
}

if(count($msg_erro) > 0){
	pg_query($con,"ROLLBACK");
	echo explode("<br><br>",$msg_erro);
}else{
	pg_query($con,"COMMIT");
}
