<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';


$produto_referencia  = $_GET['produto'];
if(strlen($produto_referencia)>0){


	$cond_1 = " 1 = 1 ";
	if(strlen($produto_referencia)>0){
		$sql = "select produto from tbl_produto where referencia = '$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_produto.produto = $produto";
		}
	}else{
		exit;
	}

	$sql = "SELECT distinct tbl_defeito_reclamado.descricao,
							tbl_defeito_reclamado.defeito_reclamado
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado AND tbl_defeito_reclamado.fabrica=$login_fabrica
			JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia AND tbl_produto.fabrica_i=$login_fabrica
			WHERE tbl_diagnostico.fabrica = $login_fabrica
				AND tbl_diagnostico.ativo is true
				AND $cond_1
			ORDER BY tbl_defeito_reclamado.descricao";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		echo "<option value=''></option>";
		for($i=0; $i<pg_numrows($res); $i++){
			$descricao         = pg_result($res,$i,descricao);
			$defeito_reclamado = pg_result($res,$i,defeito_reclamado);
	
			echo "<option value='$defeito_reclamado '>".$descricao."</option>";
		}
	}else{
		echo "<option value=''>Não encontrado.</option>";
	}
}else{
	echo "<option value=''>Não encontrado.</option>";
}
exit;
?>
