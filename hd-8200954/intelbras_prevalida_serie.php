<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
#include 'autentica_usuario.php';


$msg_erro = "";
$serie = $_GET['serie'];
$fabrica = $_GET['fabrica'];
/* PRODUTOS O&M que a Adriana enviou no email 20/11/2009
telecontrol=> select produto from tbl_produto where referencia in ('4880581','4880000','4880001','4880582', '4880580', '4880034', '4880006', '4882060', '4882061', '4882062', '4882040', '4882070', '4885039', '4880031', '4880586', '4880585', '4880037', '4882120', '4882080', '4880049', '4880043', '4880052', '4880035', '4880033') and ativo;
 produto
---------
   24324
   23700
   24537
   23699
   23583
   23659
   23750
   24323
   23582
   23717
   23696
   23694
   23697
   23698
   34588
   23695
   35518
   37886
   37933
   37940
   37941
   37942
   37943
   24330
(24 rows)

*/
if (strlen ($serie) > 0) {
	$serie = trim (strtoupper ($serie));
	$sql = "SELECT tbl_numero_serie.*, tbl_produto.descricao, tbl_produto.linha, tbl_produto.familia			FROM tbl_numero_serie 
			JOIN tbl_produto 
			USING (produto) 
			WHERE serie = '$serie' 
			AND fabrica = $fabrica
			/* produto O e M intelbras*/
			AND tbl_produto.produto not in (24324,23700,24537,23699,23583,23659,23750,24323,23582,23717,23696,23694,23697,23698,34588,23695,35518,37886,37933,37940,37941,37942,37943,24330)";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<br>";
		echo "<erro>Número de Série não encontrado</erro>";
		exit;
	}

	$produto            = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia_produto);
	$data_fabricacao    = pg_result ($res,0,data_fabricacao);
	$produto_descricao  = pg_result ($res,0,descricao);
	$key_code           = pg_result ($res,0,key);


	#-------- Para uso do AJAX ----------
	echo	 "<br>";
	echo "<ok>";
	echo $produto . "|" . $produto_referencia . "|" . $data_fabricacao . "|" . $produto_descricao . "|" . $key_code ."|".$serie ;
	echo "</ok>";
	#------------------------------------

}

?>