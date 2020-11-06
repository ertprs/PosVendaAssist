<?

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

$gera_extrato = trim($_GET["gera_extrato"]);


if ($gera_extrato == 'extrato'){
	$login_fabrica = trim($_GET["login_fabrica"]);
	$login_posto   = trim($_GET["login_posto"]);
}else{
	include_once 'autentica_usuario.php';
}


$msg_erro = "";
$btn_acao = $_POST['btn_acao'];
$linha_form = $_GET['linha_form'];

if (strlen ($btn_acao) > 0 OR strlen ($linha_form) > 0) {
	$cod			= trim($_POST ['cod']);
	$vendedor		= trim($_POST ['vendedor']);
	$cnpj			= trim($_POST ['cnpj']);
	$condpg			= trim($_POST ['condpg']);

	if (strlen ($cod)      == 0) $cod      = trim($_GET ['cod']);
	if (strlen ($vendedor) == 0) $vendedor = trim($_GET ['vendedor']);
	if (strlen ($cnpj)     == 0) $cnpj     = trim($_GET ['cnpj']);
	if (strlen ($condpg)   == 0) $condpg   = trim($_GET ['condpg']);

	$linha_form = $_GET['linha_form'];

	if (strlen ($cnpj) == 0) {
		$posto = trim($_GET ['posto']);
		$sql = "SELECT cnpj 
				FROM tbl_posto 
				WHERE posto = $posto
				";
		$res_jacto = pg_exec ($con,$sql);
		$cnpj = pg_result ($res_jacto,0,0);
	}

	if (strlen ($condpg) == 0) {
		$condicao = trim($_GET ['condicao']);
		$sql = "SELECT codigo_condicao 
				FROM tbl_condicao 
				WHERE condicao = $condicao
				";
		$res_jacto = pg_exec ($con,$sql);
		$condpg = pg_result ($res_jacto,0,0);
	}

	if (strlen ($cod) == 0) {
		$produto_referencia = trim($_GET ['produto_referencia']);

			$sql = "SELECT  tbl_depara.peca_para,
							tbl_depara.para,
							tbl_peca.descricao,
							tbl_peca.referencia
				FROM    tbl_depara
				JOIN    tbl_peca on tbl_peca.referencia = tbl_depara.de 
				AND     tbl_peca.fabrica   = $login_fabrica
				WHERE   tbl_depara.de      = '$produto_referencia'
				AND     tbl_depara.fabrica = $login_fabrica;";
				$res1 = pg_query ($con,$sql);

				if (pg_num_rows($res1) > 0) {
					$xpeca_para          = pg_fetch_result ($res1,0,peca_para);
					$xreferencia_peca_de = pg_fetch_result ($res1,0,referencia);
					$xdescricao_peca_de  = pg_fetch_result ($res1,0,descricao);
					$cod                 = pg_fetch_result ($res1,0,para);
					$cor = "#00B95C";
					$cod_peca = $xpeca_para;
					$mudou = 'SIM';
				}else {
					$cod = $produto_referencia;
				}
	}

	$cod_devolve = $cod;
	
	if(empty($xpeca_para)){
		$sql = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$cod'";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			$cod_peca = pg_result($res,0,peca);
		}
	}

	$sql = "SELECT tbl_tabela_item.preco, 
				   tbl_peca.ipi, 
				   tbl_peca.descricao
				 FROM tbl_tabela_item
				 JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.fabrica = $login_fabrica
				 JOIN tbl_peca ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
				WHERE tbl_tabela_item.peca = $cod_peca";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$preco     = pg_result($res,0,preco);
		$ipi	   = pg_result($res,0,ipi);
		$descricao = pg_result($res,0,descricao);
		$preco_ipi = $preco + ( ($preco * $ipi) / 100);
		
	}
	
	#--------- Determina Condição de Pagamento -----------
	$sql = "SELECT descricao,
				   desconto_financeiro,
				   acrescimo_financeiro,
				   codigo_condicao
				 FROM tbl_condicao 
			WHERE codigo_condicao = '$condpg' 
			AND   fabrica = $login_fabrica
			";
	$res_jacto = pg_exec ($con,$sql);
	if (pg_numrows ($res_jacto) > 0) {
		$condpg_descricao = pg_result ($res_jacto,0,descricao);
		$condpg_descfin   = pg_result ($res_jacto,0,desconto_financeiro);
		$condpg_acresci   = pg_result ($res_jacto,0,acrescimo_financeiro);
		$condpg_codigo    = pg_result ($res_jacto,0,codigo_condicao);
		echo "<br>";
		echo "Condição = $condpg - $condpg_descricao - DescFin $condpg_descfin - Acresci - $condpg_acresci";
		echo "<br>";
	}else{
		echo "<br>";
		echo "condicao $condpag não cadastrada";
		echo "<br>";
	}


	
	#-------- Para uso do AJAX ----------
	echo "<br>";
	echo "<preco>";
	echo number_format ($preco,2,",",".");
	echo "|";
	echo $linha_form;
	echo "|";
	echo "$descricao";
	echo "|";
	echo $mudou;
	echo "|";
	echo $produto_referencia;
	echo "|";
	echo $cod_devolve;
	echo "|";
	echo $ipi;
	echo "|";
	echo number_format ($preco_ipi,2,",",".");
	echo "</preco>";
	#------------------------------------
}

?>
