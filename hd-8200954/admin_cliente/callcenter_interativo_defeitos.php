<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
include 'autentica_admin.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$ajax_natureza = $_GET['natureza'];
	$produto_referencia  = $_GET['produto'];

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

	 $sql = "SELECT  distinct tbl_defeito_reclamado.descricao, 
					tbl_defeito_reclamado.defeito_reclamado 
			FROM tbl_defeito_reclamado
			JOIN tbl_produto on tbl_defeito_reclamado.linha = tbl_produto.linha
			AND tbl_defeito_reclamado.familia = tbl_produto.familia
			WHERE tbl_defeito_reclamado.fabrica = $login_fabrica
			AND tbl_defeito_reclamado.ativo is true
			AND $cond_1
		 order by tbl_defeito_reclamado.descricao ";
//echo $sql;
if($login_fabrica<>25){
	$sql = "SELECT distinct tbl_defeito_reclamado.descricao,
							tbl_defeito_reclamado.defeito_reclamado
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
			JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia
			WHERE tbl_diagnostico.fabrica = $login_fabrica
			AND tbl_diagnostico.ativo is true
			AND $cond_1
UNION
SELECT distinct tbl_defeito_reclamado.descricao         , 
		tbl_familia_defeito_reclamado.defeito_reclamado
		FROM tbl_familia_defeito_reclamado
		JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica
order by 1";
}
$res = pg_exec($con,$sql);
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

exit;
}
?>