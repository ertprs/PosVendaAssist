<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "funcoes.php";


if(strlen($_GET["referencia"])>0){

	$referencia        = trim($_GET["referencia"]);
	$servico_realizado = trim($_GET["servico_realizado"]);

	if (strlen($servico_realizado)==0){
		echo "<option selected></option>";
	}else{
		echo "<option></option>";
	}

	$sql = "SELECT peca, placa
			FROM tbl_peca 
			WHERE fabrica    = $login_fabrica
			AND   referencia = '$referencia'  ";
	$res0 = pg_exec ($con,$sql) ;
	if (pg_numrows ($res0) > 0) {
		$placa = pg_result ($res0,0,placa) ;

		$sql = "SELECT	tbl_servico_realizado.servico_realizado,
						tbl_servico_realizado.descricao
				FROM tbl_servico_realizado
				WHERE tbl_servico_realizado.fabrica = $login_fabrica 
				/*AND   tbl_servico_realizado.ativo IS TRUE */";
		if ($placa != 't'){
			$sql .= " AND tbl_servico_realizado.servico_realizado NOT IN (727,728) ";
		}
		$sql .= "ORDER BY tbl_servico_realizado.descricao " ;
		$res0 = pg_exec ($con,$sql) ;

		for ($x = 0 ; $x < pg_numrows ($res0) ; $x++ ) {
			echo "<option ";
			if ($servico_realizado == pg_result ($res0,$x,servico_realizado)) echo " selected ";
			echo " value='" . pg_result ($res0,$x,servico_realizado) . "'>" ;
			echo pg_result ($res0,$x,descricao) ;
			echo "</option>";
		}
		exit;
	}
}
echo "<option>Selecione a peça</option>";
?>