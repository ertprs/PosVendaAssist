<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$produto_referencia= $_GET["produto_referencia"]; 
$subconjunto= $_GET["subconjunto"]; 
if(strlen($produto_referencia)>0){
		$sqlp = "SELECT defeito_constatado_por_familia, defeito_constatado_por_linha FROM tbl_fabrica WHERE fabrica = $login_fabrica";
		$resp = pg_exec ($con,$sqlp);
		$defeito_constatado_por_familia = pg_result ($resp,0,defeito_constatado_por_familia) ;
		$defeito_constatado_por_linha   = pg_result ($resp,0,defeito_constatado_por_linha) ;

		if ($defeito_constatado_por_familia == 't') {
			$sqlf = "SELECT familia 
					FROM tbl_produto 
					JOIN tbl_linha USING(linha) 
					WHERE upper(referencia)=upper('$produto_referencia') 
					AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
			$resf = pg_exec ($con,$sqlf);
			$familia = pg_result ($resf,0,0) ;

			$sql = "SELECT tbl_defeito_constatado.*
					FROM   tbl_familia
					JOIN   tbl_familia_defeito_constatado USING(familia)
					JOIN   tbl_defeito_constatado         USING(defeito_constatado)
					WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
					AND tbl_defeito_constatado.ativo = 't' 
					AND    tbl_familia_defeito_constatado.familia = $familia
					ORDER BY tbl_defeito_constatado.descricao";
		}else{

			if ($defeito_constatado_por_linha == 't') {
				$sqll   = "SELECT linha FROM tbl_produto 
					JOIN tbl_linha USING(linha) 
					WHERE upper(referencia)=upper('$produto_referencia') 
					AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
				$resl   = pg_exec ($con,$sqll);
				$linha = pg_result ($resl,0,0) ;

				$sql = "SELECT tbl_defeito_constatado.*
						FROM   tbl_defeito_constatado
						JOIN   tbl_linha USING(linha)
						WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
						AND    ativo = 't'
						AND    tbl_linha.linha = $linha
						ORDER BY tbl_defeito_constatado.descricao";
			}else{
				$sql = "SELECT tbl_defeito_constatado.*
						FROM   tbl_defeito_constatado
						WHERE  tbl_defeito_constatado.fabrica = $login_fabrica
						AND    ativo = 't' 
						ORDER BY tbl_defeito_constatado.descricao";
			}
		}


$resD = pg_exec ($con,$sql) ;

//echo "<BR>$sql"; 
$row = pg_numrows ($resD);
if($row) {
	//XML
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<produtos>\n";
	//PERCORRE ARRAY
	for($i=0; $i<$row; $i++) {
		$defeito_constatado    = pg_result($resD, $i, 'defeito_constatado');
		$descricao             = pg_result($resD, $i, 'descricao'); 
		$codigo                = pg_result($resD, $i, 'codigo');
		$xml .= "<produto2>\n";
		$xml .= "<codigo2>".$defeito_constatado."</codigo2>\n";
		$xml .= "<nome2>".$codigo." - ".$descricao."</nome2>\n";
		$xml .= "</produto2>\n";
	}//FECHA FOR
	$xml.= "</produtos>\n";
	//CABEÇALHO
	Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)
echo $xml;

}else{
	$sql="SELECT tbl_produto.produto,tbl_produto.descricao
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE upper(referencia)=upper('$subconjunto') 
				AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res)>0){
		$produto               = pg_result ($res,0,'produto') ;
		$produto_descricao     = pg_result ($res,0,'descricao') ;
		
		$sql = "SELECT  tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM    tbl_subproduto
					JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
					WHERE   tbl_subproduto.produto_pai = $produto /*in (select produto from tbl_produto where upper(referencia)=upper('$subconjunto') limit 1)*/
					AND     tbl_produto.ativo IS TRUE
					ORDER BY tbl_produto.referencia;";
		$resD = pg_exec ($con,$sql) ;

		$row = pg_numrows ($resD);

	}

	if($row) {
		//XML
		$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$xml .= "<produtos>\n";
		$xml .= "<produto3>\n";
		$xml .= "<codigo3> </codigo3>\n";
		$xml .= "<nome3> </nome3>\n";
		$xml .= "</produto3>\n";

		//PERCORRE ARRAY
		$xml .= "<produto3>\n";
		$xml .= "<codigo3>".$produto."</codigo3>\n";
		$xml .= "<nome3>".$produto_descricao."</nome3>\n";
		$xml .= "</produto3>\n";

		for ($i = 0 ; $i < pg_numrows ($resD) ; $i++ ) {
				$sub_produto    = trim (pg_result ($resD,$i,produto));
				$sub_referencia = trim (pg_result ($resD,$i,referencia));
				$sub_descricao  = trim (pg_result ($resD,$i,descricao));
				if (substr ($sub_referencia,0,3) == "499" ){
					$sql = "SELECT  tbl_produto.produto   ,
									tbl_produto.referencia,
									tbl_produto.descricao
							FROM    tbl_subproduto
							JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
							WHERE   tbl_subproduto.produto_pai = $sub_produto
							AND     tbl_produto.ativo IS TRUE
							ORDER BY tbl_produto.referencia;";
					$resY = pg_exec ($con,$sql) ;
					$xml.="<optgroup label='" . $sub_referencia . " - " . substr($sub_descricao,0,25) . "'>" ;
					for ($y = 0 ; $y < pg_numrows ($resY) ; $y++ ) {
						$sub_produto    = trim (pg_result ($resY,$y,produto));
						$sub_referencia = trim (pg_result ($resY,$y,referencia));
						$sub_descricao  = trim (pg_result ($resY,$y,descricao));

					$xml .= "<produto3>\n";
					
					$xml .= "<codigo3>".$sub_referencia."</codigo3>\n";
					$xml .= "<nome3>".$sub_referencia." - ".$sub_descricao."</nome3>\n";
					$xml .= "</produto3>\n";
					}
					$xml.="</optgroup>";
				}else{
					$xml .= "<produto3>\n";
					$xml .= "<codigo3>".$sub_referencia."</codigo3>\n";
					$xml .= "<nome3>".$sub_referencia." - ".$sub_descricao."</nome3>\n";
					$xml .= "</produto3>\n";
				}
			}
			$xml.= "</produtos>\n";
		//CABEÇALHO
		Header("Content-type: application/xml; charset=iso-8859-1"); 
		echo $xml;
	}//FECHA IF (row)

}
?>
