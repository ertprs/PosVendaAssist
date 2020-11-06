<?
#--------------------------------------------------------------------------------------
# Este programa grava as peças pedidas para cada RG_ITEM
#--------------------------------------------------------------------------------------
include 'cabecalho-ajax.php';

$login_fabrica = $_COOKIE['cook_fabrica'];
$login_fabrica = str_replace ("'","",$login_fabrica);
$login_fabrica = 45 ;

$produto_rg_item = $_POST['produto_rg_item'];
if (strlen ($produto_rg_item) == 0) $produto_rg_item = $_GET['produto_rg_item'];
$produto_rg_item = str_replace ("'","",$produto_rg_item);


echo `cat /dev/null > /tmp/x1.xxx`;
echo `cat /dev/null > /tmp/x2.xxx`;
echo `cat /dev/null > /tmp/x3.xxx`;
echo `cat /dev/null > /tmp/x4.xxx`;
echo `cat /dev/null > /tmp/x5.xxx`;

echo `echo teste2 >> /tmp/x4.xxx`;


if (strlen ($produto_rg_item) > 0) {
	for ($i = 1 ; $i < 20 ; $i++) {
		$peca = $_POST['peca_' . $i];
		if (strlen ($peca) == 0) $peca = $_GET['peca_' . $i];
		$peca = trim (strtoupper ($peca));
		$peca = str_replace ("'","",$peca);
		list ($referencia) = split ("-",$peca);
		$referencia = trim ($referencia);
		if (strlen ($referencia) == 0) $referencia = $peca;
		if (strlen ($referencia) >  0) {
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$peca = pg_result ($res,0,0);
				$sql = "SELECT * FROM tbl_produto_rg_peca WHERE produto_rg_item = $produto_rg_item ORDER BY produto_rg_peca OFFSET $i - 1 LIMIT 1";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) == 0) {
					$sql = "INSERT INTO tbl_produto_rg_peca (produto_rg_item, peca, qtde) VALUES ($produto_rg_item , $peca , 1)";
					$res = pg_exec ($con,$sql);
				}else{
					$produto_rg_peca = pg_result ($res,0,produto_rg_peca);
					$sql = "UPDATE tbl_produto_rg_peca SET peca = $peca , qtde = 1 WHERE produto_rg_peca = $produto_rg_peca";
					$res = pg_exec ($con,$sql);
				}
			}else{
				echo "<erro>Nao localizada peca $referencia</erro>";
			}
		}else{
			$sql = "SELECT * FROM tbl_produto_rg_peca WHERE produto_rg_item = $produto_rg_item ORDER BY produto_rg_peca OFFSET $i - 1 LIMIT 1";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$produto_rg_peca = pg_result ($res,0,produto_rg_peca);
				$sql = "DELETE FROM tbl_produto_rg_peca WHERE produto_rg_peca = $produto_rg_peca";
				$res = pg_exec ($con,$sql);
			}
		}
	}
}


#--------------------------------------------------------------------------------------
# Grava o Pedido Antecipado
#--------------------------------------------------------------------------------------
$produto_rg = $_POST['produto_rg'];
if (strlen ($produto_rg) == 0) $produto_rg = $_GET['produto_rg'];
$produto_rg = str_replace ("'","",$produto_rg);

$produto = $_POST['produto'];
if (strlen ($produto) == 0) $produto = $_GET['produto'];
$produto = str_replace ("'","",$produto);

if (strlen ($produto_rg) > 0) {
echo `echo aqui > /tmp/x1.xxx`;
echo `echo $produto_rg >> /tmp/x1.xxx`;
	for ($i = 1 ; $i < 20 ; $i++) {
		$peca = $_POST['peca_' . $i];
		if (strlen ($peca) == 0) $peca = $_GET['peca_' . $i];
		$peca = trim (strtoupper ($peca));
		$peca = str_replace ("'","",$peca);
		list ($referencia) = split ("-",$peca);
		$referencia = trim ($referencia);
		if (strlen ($referencia) == 0) $referencia = $peca;

echo `echo $referencia >> /tmp/x1.xxx`;
		if (strlen ($referencia) >  0) {
			$qtde = $_POST['qtde_' . $i];
			if (strlen ($qtde) == 0) $qtde = $_GET['qtde_' . $i];
			$qtde = trim (strtoupper ($qtde));
			$qtde = str_replace ("'","",$qtde);

echo `echo $qtde >> /tmp/x1.xxx`;
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$peca = pg_result ($res,0,0);
				$sql = "SELECT * FROM tbl_produto_rg_pedido WHERE produto_rg = $produto_rg AND produto = $produto ORDER BY produto_rg_pedido OFFSET $i - 1 LIMIT 1";
echo `echo '$sql' >> /tmp/x1.xxx`;
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) == 0) {
					$sql = "INSERT INTO tbl_produto_rg_pedido (produto_rg, produto, peca, qtde) VALUES ($produto_rg, $produto , $peca , $qtde)";
echo `echo '$sql' >> /tmp/x1.xxx`;
					$res = pg_exec ($con,$sql);
$msg = "msg->" . pg_errormessage($conn);
echo `echo '$msg' >> /tmp/x1.xxx`;
				}else{
					$produto_rg_pedido = pg_result ($res,0,produto_rg_pedido);
					$sql = "UPDATE tbl_produto_rg_pedido SET peca = $peca , qtde = $qtde WHERE produto_rg_pedido = $produto_rg_pedido";
					$res = pg_exec ($con,$sql);
				}
			}else{
				echo "<erro>Nao localizada peca $referencia</erro>";
			}
		}else{
			$sql = "SELECT * FROM tbl_produto_rg_pedido WHERE produto_rg = $produto_rg AND produto = $produto ORDER BY produto_rg_pedido OFFSET $i - 1 LIMIT 1";
echo `echo '$sql' >> /tmp/x1.xxx`;
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				$produto_rg_pedido = pg_result ($res,0,produto_rg_pedido);
				$sql = "DELETE FROM tbl_produto_rg_pedido WHERE produto_rg_pedido = $produto_rg_pedido";
echo `echo '$sql' >> /tmp/x1.xxx`;
				$res = pg_exec ($con,$sql);
			}
		}
	}
}


?>