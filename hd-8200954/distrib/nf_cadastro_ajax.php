<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include '../funcoes.php';

$nota_fiscal = trim($_GET['nota_fiscal']);
$fabrica     = trim($_GET['fabrica']);
$ajax        = trim($_GET["ajax"]);
if($ajax=="sim"){
	$sql = "SELECT faturamento 
			FROM tbl_faturamento 
			WHERE fabrica     = $fabrica 
			AND   posto       = $login_posto
			AND   nota_fiscal = '$nota_fiscal'";
	$res = pg_exec ($con,$sql);

	//SE JA EXISTIR O FATURAMENTO, REDIRECIONA PARA A TELA DA NOTA FISCAL
	if(pg_numrows($res)>0) echo "ok|<font color='red'>Nota Fiscal:\"$nota_fiscal\" já cadastrada!</font>";
	else                   echo "ok|<font color='blue'>Nota Fiscal:\"$nota_fiscal\" não cadastrada para este fabricante!</font>";
exit();
}

$fabrica_fornecedor = $_GET['fabrica_fornecedor'];

if ($fabrica_fornecedor != null) {
	$fab_telecontrol = $fabrica_fornecedor;
} else {
	$fab_telecontrol = $telecontrol_distrib;
}

if ($_GET['fabrica'] == 11 || $_GET['fabrica'] == 172) {
	$fab_telecontrol = $_GET['fabrica'];
}

$tipo = $_GET["tipo"];
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	if(strlen($fabrica )==0){
		//Buscar nas fábricas em que a Telecontrol é Distribuidora
		//echo "É necessário selecionar para qual fábrica é a Nota Fiscal!";
		//exit;
	}
	if (strlen($q)>2){

		$sql = "SELECT  peca      ,
						referencia,
						descricao
				FROM tbl_peca
				/*Buscar nas fábricas em que a Telecontrol é Distribuidora
				WHERE fabrica = $fabrica */
				WHERE fabrica in ($fab_telecontrol) ";
		if ($tipo_busca == "referencia"){
			$sql .= " AND (referencia ilike '%$q%' OR referencia_pesquisa ilike '%$q%') ";
		}else{
			$sql .= " AND descricao ilike '%$q%' ";
		}
		$sql .= " UNION (SELECT  produto,
							referencia,
							descricao
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
				/*Buscar nas fábricas em que a Telecontrol é Distribuidora
				WHERE fabrica = $fabrica */
				WHERE fabrica in ($fab_telecontrol) ";

			if ($tipo_busca == "referencia"){
				$sql .= " AND (referencia ilike '%$q%' OR referencia_pesquisa ilike '%$q%') ";
			}else{
				$sql .= " AND descricao ilike '%$q%' ";
			}
		$sql .= "AND tbl_produto.referencia not in ( SELECT  referencia
				FROM tbl_peca
				/*Buscar nas fábricas em que a Telecontrol é Distribuidora
				WHERE fabrica = $fabrica */
				WHERE fabrica in ($fab_telecontrol) ";
		if ($tipo_busca == "referencia"){
			$sql .= " AND (referencia ilike '%$q%' OR referencia_pesquisa ilike '%$q%') ) )";
		}else{
			$sql .= " AND descricao ilike '%$q%' ) )";
		}

		$sql;
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$peca       = trim(pg_result($res,$i,peca));
				$referencia = trim(pg_result($res,$i,referencia));
				$descricao  = trim(pg_result($res,$i,descricao));
				echo $referencia."|".$descricao;
				echo "\n";
			}
		}else{
			$sql = "SELECT  produto,
							referencia,
							descricao
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
				/*Buscar nas fábricas em que a Telecontrol é Distribuidora
				WHERE fabrica = $fabrica */
				WHERE fabrica in ($fab_telecontrol) ";

			if ($tipo_busca == "referencia"){
				$sql .= " AND (referencia ilike '%$q%' OR referencia_pesquisa ilike '%$q%') ";
			}else{
				$sql .= " AND descricao ilike '%$q%' ";
			}
			//echo $sql;
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo $referencia."|".$descricao;
					echo "\n";
				}
			}
		}
	}

}
?>
