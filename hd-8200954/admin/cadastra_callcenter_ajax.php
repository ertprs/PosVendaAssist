<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$ajax_natureza = $_GET['natureza'];
	$produto_referencia  = $_GET['produto'];

	switch ($ajax_natureza) {
		case 'Reclamação' :
			$duvida_reclamacao = 'RC';
			break;
		case 'Ocorrência' :
			$duvida_reclamacao = 'RC';
			break;
		case 'Defeito' :
			$duvida_reclamacao = 'DF';
			break;
		case 'Informação' :
			$duvida_reclamacao = 'IN';
			break;
		case 'Insatisfação' :
			$duvida_reclamacao = 'IS';
			break;
		case 'Troca do Produto' :
			$duvida_reclamacao = 'TP';
			break;
		case 'Engano' :
			$duvida_reclamacao = 'EN';
			break;
		case 'Outras Áreas' :
			$duvida_reclamacao = 'OA';
			break;
		case 'Email' :
			$duvida_reclamacao = 'RC';
			break;
		case 'Dúvida' :
			$duvida_reclamacao = 'DV';
			break;
	}
	$cond_1 = " 1 = 1 ";
	if(strlen($produto_referencia)>0){
		$sql = "select produto from tbl_produto where referencia = '$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_produto.produto = $produto";
		}
	}

if($duvida_reclamacao == 'RC'){//chamado 1238
	$sql = "SELECT  distinct tbl_defeito_reclamado.descricao, 
					tbl_defeito_reclamado.defeito_reclamado 
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado =  tbl_diagnostico.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica
			JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha
			AND tbl_diagnostico.familia = tbl_produto.familia
			WHERE tbl_diagnostico.fabrica = $login_fabrica
			AND tbl_diagnostico.ativo is true
			AND $cond_1
		 order by tbl_defeito_reclamado.descricao ";
}else{
		$sql = "SELECT *
				FROM   tbl_defeito_reclamado
				WHERE tbl_defeito_reclamado.fabrica = $login_fabrica 
				AND   tbl_defeito_reclamado.duvida_reclamacao = '$duvida_reclamacao'
				AND   tbl_defeito_reclamado.ativo IS TRUE order by descricao ";
}
$res = pg_exec($con,$sql);
if(pg_numrows($res)==0){
		if($login_fabrica<>6){ //caso nao encontre com duvida_Reclamacao nao deve aparecer nada

		$sql = "SELECT *
				FROM   tbl_defeito_reclamado
				WHERE tbl_defeito_reclamado.fabrica = $login_fabrica 
				AND   tbl_defeito_reclamado.ativo IS TRUE order by descricao ";
		$res = pg_exec($con,$sql);
		}
	if(pg_numrows($res)==0){
		echo "<font size='1'>Nenhuma Informação Cadastrada.</font>";
	}else{
		echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='2'>";
		echo "<tr>";
		for($x=0;pg_numrows($res)>$x;$x++){
			$defeito_reclamado = pg_result($res,$x,defeito_reclamado);
			$descricao         = pg_result($res,$x,descricao);
			echo "<td align='left'><input type='radio' name='defeito_reclamado' value='$defeito_reclamado'><font size='1'>$descricao</font></td>";
			if($x%3==0){ echo "</tr><tr>"; }
		}
		echo "</tr>";
		echo "</table>";
	}
}else{
	echo "<table width='100%' border='0' align='center' cellpadding='0' cellspacing='2'>";
    echo "<tr>";
	for($x=0;pg_numrows($res)>$x;$x++){
		$defeito_reclamado = pg_result($res,$x,defeito_reclamado);
		$descricao         = pg_result($res,$x,descricao);
		echo "<td align='left'><input type='radio' name='defeito_reclamado' value='$defeito_reclamado'><font size='1'>$descricao</font></td>";
		if($x%3==0){ echo "</tr><tr>"; }
	}
	echo "</tr>";
	echo "</table>";

}

exit;
}
?>