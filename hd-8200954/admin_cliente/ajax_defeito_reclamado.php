<?php
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


$referencia = $_REQUEST['referencia'];
$valor_referencia = $_REQUEST['valor']; // Valor para imprimir selected no option

if(strlen($referencia) > 0) {
	$sql = "SELECT 	 
				familia, 
				linha
			FROM 
				tbl_produto
			JOIN 
				tbl_linha USING(linha)
			WHERE 
				(referencia_fabrica = '$referencia' OR referencia = '$referencia')
				AND fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	if(pg_num_rows($res)>0){

		$linha  = pg_fetch_result($res,0,linha);
		$familia = pg_fetch_result($res,0,familia);

		if(in_array($login_fabrica, array(96,191))){
			$sql = "
				SELECT 
					tbl_defeito_reclamado.defeito_reclamado, 
					tbl_defeito_reclamado.descricao
				FROM tbl_defeito_reclamado 
					JOIN tbl_diagnostico ON (tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) 
				WHERE 
					tbl_diagnostico.fabrica = $login_fabrica 
					AND tbl_diagnostico.familia = $familia; ";
		} else if ($login_fabrica == 158) {
			$sql = "
				SELECT 
					tbl_defeito_reclamado.defeito_reclamado, 
					tbl_defeito_reclamado.descricao
				FROM tbl_defeito_reclamado 
					JOIN tbl_diagnostico ON (tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) 
				WHERE 
					tbl_diagnostico.fabrica = $login_fabrica 
					AND tbl_diagnostico.defeito_constatado IS NULL
					AND tbl_diagnostico.solucao IS NULL
					AND tbl_diagnostico.garantia IS TRUE
					AND tbl_defeito_reclamado.ativo IS TRUE
					AND tbl_diagnostico.ativo IS TRUE
					AND tbl_diagnostico.familia = $familia
				ORDER BY tbl_defeito_reclamado.descricao; ";
		} else{
			$sql = "
				SELECT
					defeito_reclamado	,
					descricao
				FROM 
					tbl_defeito_reclamado
				WHERE 
					linha = $linha 
					AND familia = $familia
				ORDER BY descricao ASC;";
		}
		$res = @pg_query($con,$sql);
		
		if(pg_num_rows($res)){
			for($i= 0; $i < pg_num_rows($res); $i++){
				$defeito_reclamado = pg_fetch_result($res,$i,defeito_reclamado);
				$descricao = pg_fetch_result($res,$i,descricao);
				
				$selected = ($valor_referencia == $defeito_reclamado) ? " selected " : "";
				
				echo "<option value='$defeito_reclamado' $selected>$descricao</option>\n";
			}
		}else
			echo "<option value=''> Nenhum defeito encontrado!</option>\n";
	}
}else
		echo "<option value=''> Nenhum defeito encontrado!</option>\n";
?>

