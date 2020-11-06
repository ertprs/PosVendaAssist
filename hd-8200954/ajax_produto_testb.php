<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
//RECEBE PARaMETRO
// $produto_referencia = $_POST["produto_referencia"];
$produto_referencia = $_GET["produto_referencia"];
//pegar o login fabrica
$sql="SELECT familia,
			fabrica,
			produto,
			linha
		FROM tbl_produto
		JOIN tbl_linha USING(linha)
		WHERE upper(referencia)=upper('$produto_referencia')
		AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
if($login_fabrica==24 and 1==2){
	$sql="SELECT familia,
				fabrica,
				produto,
				linha
			FROM tbl_produto
			JOIN tbl_linha USING(linha)
			WHERE referencia like '$produto_referencia' LIMIT 1";
}
//echo nl2br($sql);
$res = pg_exec ($con,$sql);
$familia        = pg_result ($res,0,'familia') ;
$linha          = pg_result ($res,0,'linha') ;
$login_fabrica  = pg_result ($res,0,'fabrica') ;
$cod_produto    = pg_result ($res,0,'produto') ;
//echo "familia: $sql";


#TELA NOVA A PARTIR DAQUI ----------------


//PROCURA POR LINHA E FAMILIA
	//Validações Latinatec - Linhas sem integridade
/*	if($login_fabrica == 15){
		if($linha == 319) $linha = 315;
		if($linha == 382) $linha = 317;
		if($linha == 401) $linha = 307;
		if($linha == 390) $linha = 317;
	}
*/

if($login_fabrica <> 15){
	$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado),
					tbl_defeito_reclamado.descricao
			FROM tbl_diagnostico
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE tbl_diagnostico.fabrica=$login_fabrica ";
	if(strlen($familia)>0){ 
		$sql .=" AND tbl_diagnostico.familia=$familia ";
	}
	if(strlen($linha)>0 && $login_fabrica <> 42 && $login_fabrica <> 86){
		$sql .=" AND tbl_diagnostico.linha=$linha ";
	}
	$sql .= " and tbl_defeito_reclamado.ativo='t' and tbl_diagnostico.ativo='t' ";
	if($login_fabrica == 52) {
		$sql .= " UNION
			SELECT distinct tbl_familia_defeito_reclamado.defeito_reclamado,
			tbl_defeito_reclamado.descricao
			FROM tbl_familia_defeito_reclamado
			JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica ";
	}
	$sql .= " order by 1 ";
}else{
	$sql = "SELECT DISTINCT(tbl_defeito_reclamado.descricao),
					tbl_defeito_reclamado.defeito_reclamado
			FROM tbl_defeito_reclamado
			WHERE tbl_defeito_reclamado.fabrica=$login_fabrica
			AND tbl_defeito_reclamado.ativo = 't'
			ORDER BY tbl_defeito_reclamado.descricao ";
}
//echo nl2br($sql);
	$resD = pg_exec ($con,$sql) ;
	$row = pg_numrows ($resD);

	if ($login_fabrica==14 or $login_fabrica==66){ // nao faz pesquisa por linha, somente por familia
		$sql = "SELECT  defeito_constatado_por_familia,
						defeito_constatado_por_linha
				FROM    tbl_fabrica
				WHERE   tbl_fabrica.fabrica = $login_fabrica";
		#if ($ip == '201.0.9.216') echo $sql;
		$res = pg_exec ($con,$sql);
		$defeito_constatado_por_familia = pg_result ($res,0,0) ;
		$defeito_constatado_por_linha   = pg_result ($res,0,1) ;
		$defeito_constatado_fabrica = "NAO";
		if ($defeito_constatado_por_familia == 't') {
			$defeito_constatado_fabrica = "SIM";
			$sql = "SELECT	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM     tbl_defeito_reclamado
					JOIN     tbl_familia USING (familia)
					WHERE    tbl_defeito_reclamado.familia = $familia
					AND      tbl_familia.fabrica           = $login_fabrica
					AND      tbl_defeito_reclamado.ativo = 't'
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;
			if (pg_numrows ($resD) == 0) {
			#HD 82470
			$sql = "SELECT DISTINCT tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
						FROM     tbl_defeito_reclamado
						JOIN     tbl_familia USING (familia)
						WHERE    tbl_defeito_reclamado.fabrica = $login_fabrica
						AND      tbl_defeito_reclamado.ativo = 't'
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
			}
		}
		if ($defeito_constatado_por_linha == 't') {
			$defeito_constatado_fabrica = "SIM";
			$sql = "SELECT	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM     tbl_defeito_reclamado
					JOIN     tbl_linha USING (linha)
					WHERE    tbl_defeito_reclamado.linha = $linha
					AND      tbl_linha.fabrica           = $login_fabrica
					AND      tbl_defeito_reclamado.ativo = 't'
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;
			if (pg_numrows ($resD) == 0) {
			$sql = "SELECT	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
						FROM     tbl_defeito_reclamado
						JOIN     tbl_linha USING (linha)
						WHERE    tbl_linha.fabrica = $login_fabrica
						AND      tbl_defeito_reclamado.ativo = 't'
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
			}
		}
		if ($defeito_constatado_fabrica == "NAO") {
			$sql = "SELECT	tbl_defeito_reclamado.defeito_reclamado,
							tbl_defeito_reclamado.descricao
					FROM     tbl_defeito_reclamado
					JOIN     tbl_linha using (linha)
					WHERE    tbl_linha.fabrica = $login_fabrica
					AND      tbl_linha.linha   = $linha
					AND      tbl_defeito_reclamado.ativo = 't'
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = @pg_exec ($con,$sql) ;
		}
	}

//echo "<BR>$sql";
$row = pg_numrows ($resD);
if($row) {
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";
   //PERCORRE ARRAY
   for($i=0; $i<$row; $i++) {
      $defeito_reclamado    = pg_result($resD, $i, 'defeito_reclamado');
	  $descricao = pg_result($resD, $i, 'descricao');
	  $xml .= "<produto>\n";
      $xml .= "<codigo>".$defeito_reclamado."</codigo>\n";
	  $xml .= "<nome>".$descricao."</nome>\n";
      $xml .= "</produto>\n";
   }//FECHA FOR
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1");
}//FECHA IF (row)
echo $xml;
?>
