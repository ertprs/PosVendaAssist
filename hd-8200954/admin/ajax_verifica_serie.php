<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
	
	$produto_referencia = trim($_GET['produto_referencia']);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);

	$produto_serie      = trim($_GET['produto_serie']);

	# HD 231110
	if(!empty($produto_serie) and !empty($produto_referencia)) {
		$referencia_produto = explode('_',$produto_referencia);
		$sql ="SELECT locacao
						FROM tbl_produto
						JOIN tbl_linha USING(linha)
						JOIN tbl_locacao USING(produto)
						WHERE tbl_linha.fabrica = $login_fabrica
						AND   trim(tbl_locacao.serie)::text = '$produto_serie'
						AND   referencia_pesquisa like '$referencia_produto[0]%'
						LIMIT 1";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			echo "erro";
		}else{
			echo "ok";
		}
	}else{
			echo "ok";
	}
	exit;