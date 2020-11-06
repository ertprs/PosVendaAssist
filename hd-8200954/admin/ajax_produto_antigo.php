<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$produto_referencia = $_GET["produto_referencia"]; 

$sql="SELECT familia, 
			fabrica, 
			produto, 
			linha 
		FROM tbl_produto 
		JOIN tbl_linha USING(linha) 
		WHERE upper(referencia)=upper('$produto_referencia') LIMIT 1";
if($login_fabrica==24){
	$sql="SELECT familia,
				fabrica,
				produto,
				linha
			FROM tbl_produto 
			JOIN tbl_linha USING(linha) 
			WHERE referencia ilike '$produto_referencia' LIMIT 1";
}

$res = pg_query ($con,$sql);
$familia        = pg_fetch_result ($res,0,'familia') ;
$linha          = pg_fetch_result ($res,0,'linha') ;
$login_fabrica  = pg_fetch_result ($res,0,'fabrica') ;
$cod_produto    = pg_fetch_result ($res,0,'produto') ;

	$sql = "SELECT  defeito_constatado_por_familia,
					defeito_constatado_por_linha
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$defeito_constatado_por_familia = pg_fetch_result ($res,0,0) ;
	$defeito_constatado_por_linha   = pg_fetch_result ($res,0,1) ;
	
	$sql = "SELECT familia FROM tbl_produto  WHERE produto = $cod_produto ";

	$resX = pg_query ($con,$sql);
	$familia = @pg_fetch_result ($resX,0,0);
	if (strlen ($familia) == 0) $familia = "0";

	if ($login_fabrica <> 5) {
		$defeito_constatado_fabrica = "NAO";
		
		if ($defeito_constatado_por_familia == 't') {
			$defeito_constatado_fabrica = "SIM";
			
			if ($login_fabrica <> 19) {
				$sql = "SELECT   *
						FROM     tbl_defeito_reclamado
						JOIN     tbl_familia USING (familia)
						WHERE    tbl_defeito_reclamado.familia = $familia
						AND      tbl_familia.fabrica           = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_query ($con,$sql) ;

				if (pg_num_rows ($resD) == 0) {
					$sql = "SELECT   *
							FROM     tbl_defeito_reclamado
							JOIN     tbl_familia USING (familia)
							WHERE    tbl_familia.fabrica = $login_fabrica
							ORDER BY tbl_defeito_reclamado.descricao;";
					$resD = pg_query ($con,$sql) ;
				}
			}else{
				$sql = "SELECT   *
						FROM     tbl_familia_defeito_reclamado
						JOIN     tbl_defeito_reclamado   ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
						AND tbl_defeito_reclamado.fabrica           = $login_fabrica
						JOIN     tbl_familia     ON tbl_familia.familia   = tbl_familia_defeito_reclamado.familia
						AND tbl_familia.fabrica = $login_fabrica
						WHERE    tbl_familia.familia = $familia
						ORDER BY trim(tbl_defeito_reclamado.codigo)::numeric;";
				$resD = pg_query ($con,$sql) ;
			}
		}
		
		if ($defeito_constatado_por_linha == 't') {
			$defeito_constatado_fabrica = "SIM";
			
			$sql = "SELECT   *
					FROM     tbl_defeito_reclamado
					JOIN     tbl_linha USING (linha)
					WHERE    tbl_defeito_reclamado.linha = $linha
					AND      tbl_linha.fabrica           = $login_fabrica
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_query ($con,$sql) ;

			if ($login_fabrica == 6) {
				$sql = "SELECT
					defeito_reclamado, 
					descricao 
					FROM tbl_defeito_reclamado 
					JOIN   tbl_linha USING (linha) 
					WHERE  tbl_defeito_reclamado.linha = $familia 
					AND duvida_reclamacao='RC'
					AND tbl_linha.fabrica = $login_fabrica 
					ORDER BY tbl_defeito_reclamado.descricao";
				$resD = pg_query ($con,$sql);
			}

			if (pg_num_rows ($resD) == 0) {
				$sql = "SELECT   *
						FROM     tbl_defeito_reclamado
						JOIN     tbl_linha USING (linha)
						WHERE    tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_query ($con,$sql) ;
			}
		}

		if ($defeito_constatado_fabrica == "NAO") {
			$sql = "SELECT   *
					FROM     tbl_defeito_reclamado
				  	JOIN     tbl_linha using (linha)
					WHERE    tbl_linha.fabrica = $login_fabrica";
			if ($login_fabrica <> 11) { $sql = " AND      tbl_linha.linha   = $linha"; }
			if ($login_fabrica == 6) { $sql .= " AND duvida_reclamacao='RC'";}
			$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = @pg_query ($con,$sql) ;
		}
	}


$row = pg_num_rows ($resD);
if($row) {
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<produtos>\n";
	for($i=0; $i<$row; $i++) {
		$defeito_reclamado    = pg_fetch_result($resD, $i, 'defeito_reclamado');
		$descricao = pg_fetch_result($resD, $i, 'descricao');
		$xml .= "<produto>\n";
		$xml .= "<codigo>".$defeito_reclamado."</codigo>\n";
		$xml .= "<nome>".$descricao."</nome>\n";
		$xml .= "</produto>\n";
	}
	$xml.= "</produtos>\n";
	Header("Content-type: application/xml; charset=iso-8859-1"); 
}
echo $xml;
?>
