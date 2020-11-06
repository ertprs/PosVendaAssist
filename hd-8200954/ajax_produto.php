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
$tipo_atendimento    = $_GET["tipo_atendimento"];

if ($login_fabrica == 42) {
	if ($cook_tipo_posto_et == "t") {
		$entrega_tecnica = "t";
	} else if ($cook_entrega_tecnica == "f" or strlen($tipo_atendimento) == 0) {
		$entrega_tecnica = "f";
	} else {
		$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
		$res = pg_query($con, $sql);

		$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
	}
}

//if ($login_fabrica == 52) $produto_referencia = "";
//pegar o login fabrica
$sql="SELECT familia,
			fabrica,
			produto,
			linha
		FROM tbl_produto
		JOIN tbl_linha USING(linha)
		WHERE upper(referencia)=upper('$produto_referencia')
		AND tbl_linha.fabrica = $login_fabrica LIMIT 1";
//echo nl2br($sql);
@$res = pg_exec ($con,$sql);
if (@pg_num_rows($res)>0) {
	$familia        = pg_result ($res,0,'familia') ;
	$linha          = pg_result ($res,0,'linha') ;
	$login_fabrica  = pg_result ($res,0,'fabrica') ;
	$cod_produto    = pg_result ($res,0,'produto') ;
}
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

if($login_fabrica <> 15) {

	$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado),
					tbl_defeito_reclamado.descricao,
					tbl_defeito_reclamado.entrega_tecnica
			FROM tbl_diagnostico
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE tbl_diagnostico.fabrica=$login_fabrica
			AND   tbl_defeito_reclamado.fabrica=$login_fabrica";

	if ($login_fabrica == 42) {
		if ($entrega_tecnica == "t") {
			$sql .= " AND tbl_defeito_reclamado.entrega_tecnica is true ";
		} else if ($entrega_tecnica == "f") {
			$sql .= " AND tbl_defeito_reclamado.entrega_tecnica is false ";
		}
	}

	if (!empty($familia)) { 
		$sql .=" AND tbl_diagnostico.familia=$familia ";
	}

	if (strlen($linha) > 0 && !in_array($login_fabrica, array(42,74,86,94,95,101,115,115,117,120,201))) {
		$sql .=" AND tbl_diagnostico.linha=$linha ";
	}

	$sql .= " and tbl_defeito_reclamado.ativo='t' and tbl_diagnostico.ativo='t' ";

	if ($login_fabrica == 52) {

		$sql .= " UNION
			SELECT distinct tbl_familia_defeito_reclamado.defeito_reclamado,
			tbl_defeito_reclamado.descricao
			FROM tbl_familia_defeito_reclamado
			JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica ";

	}

	$sql .= " order by tbl_defeito_reclamado.descricao ";


	if ($login_fabrica == 52) {
		$sql = "SELECT distinct tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
				FROM tbl_defeito_reclamado
				JOIN tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
				JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia
				WHERE tbl_defeito_reclamado.fabrica = $login_fabrica
				AND tbl_produto.produto = $cod_produto
				AND tbl_defeito_reclamado.ativo='t' ORDER BY tbl_defeito_reclamado.descricao";
	}

} else {

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

	if ($login_fabrica == 14 or $login_fabrica == 66) { // nao faz pesquisa por linha, somente por familia

		$sql = "SELECT  defeito_constatado_por_familia,
						defeito_constatado_por_linha
				FROM    tbl_fabrica
				WHERE   tbl_fabrica.fabrica = $login_fabrica";

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

				$resD = pg_exec ($con,$sql);

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

			$resD = pg_exec($con, $sql) ;

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

$row = pg_numrows ($resD);
if ($row) {
   //XML
   $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
   $xml .= "<produtos>\n";
   //PERCORRE ARRAY
   for ($i = 0; $i < $row; $i++) {
      $defeito_reclamado    = pg_result($resD, $i, 'defeito_reclamado');
	  $descricao = pg_result($resD, $i, 'descricao');
	  $entrega_tecnica = pg_result($resD, $i, "entrega_tecnica");
	  $xml .= "<produto>\n";
      $xml .= "<codigo>".$defeito_reclamado."</codigo>\n";
	  $xml .= "<nome>".$descricao."</nome>\n";
	  $xml .= "<rel>".$entrega_tecnica."</rel>\n";
      $xml .= "</produto>\n";
   }//FECHA FOR
   $xml.= "</produtos>\n";
   //CABEÇALHO
   Header("Content-type: application/xml; charset=iso-8859-1");
}//FECHA IF (row)
echo $xml;
?>
