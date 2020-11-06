<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
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
		AND tbl_linha.fabrica=$login_fabrica LIMIT 1";
if($login_fabrica==24){
	$sql="SELECT familia,
				fabrica,
				produto,
				linha
			FROM tbl_produto 
			JOIN tbl_linha USING(linha) 
			WHERE referencia ilike '$produto_referencia'
			AND tbl_linha.fabrica=$login_fabrica LIMIT 1";
}

$res = pg_exec ($con,$sql);
if (pg_num_rows($res) > 0) {
	$familia        = pg_result ($res,0,'familia') ;
	$linha          = pg_result ($res,0,'linha') ;
	$cod_produto    = pg_result ($res,0,'produto') ;
}


/******************************************************************/



//HD 399969: Em princ�pio este arquivo est� sendo usado apenas para a Black, que faz integridade por LINHA X FAMILIA X DEFEITO RECLAMADO

if ($login_fabrica == 1 && pg_num_rows($res) > 0) {
	$sql = "
	SELECT
	tbl_defeito_reclamado.defeito_reclamado,
	tbl_defeito_reclamado.descricao

	FROM
	tbl_defeito_reclamado

	WHERE
	tbl_defeito_reclamado.fabrica = $login_fabrica
	AND tbl_defeito_reclamado.linha = $linha
	AND tbl_defeito_reclamado.familia = $familia

	ORDER BY
	tbl_defeito_reclamado.descricao;
	";
	$resD = pg_exec ($con,$sql) ;
}
else if (pg_num_rows($res) > 0) {
	$sql = "SELECT  defeito_constatado_por_familia,
					defeito_constatado_por_linha
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica";
	#if ($ip == '201.0.9.216') echo $sql;
	$res = pg_exec ($con,$sql);
	$defeito_constatado_por_familia = pg_result ($res,0,0) ;
	$defeito_constatado_por_linha   = pg_result ($res,0,1) ;
	
	$sql = "SELECT familia FROM tbl_produto  WHERE produto = $cod_produto ";

	$resX = pg_exec ($con,$sql);
	$familia = @pg_result ($resX,0,0);
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
				$resD = pg_exec ($con,$sql) ;


				//Nas fam�lias que n�o tem cadastro de Defeito Reclamado, aparecem as informa��es de todas as fam�lias. (isso ocorre tamb�m em algumas que j� tem o cadastro) - HD 6700
				if (pg_numrows ($resD) == 0) {
					$sql = "SELECT   *
							FROM     tbl_defeito_reclamado
							JOIN     tbl_familia USING (familia)
							WHERE    tbl_familia.fabrica = $login_fabrica
							ORDER BY tbl_defeito_reclamado.descricao;";
					$resD = pg_exec ($con,$sql) ;

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
				$resD = pg_exec ($con,$sql) ;
				
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
			$resD = pg_exec ($con,$sql) ;

			

			//takashi 31/07/2006 a pedido do leandro tectoy, somente defeitos constatados como RECLAMACAO deve aparecer
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
				$resD = pg_exec ($con,$sql);

				
			}
			//takashi 31/07/2006 a pedido do leandro tectoy, somente defeitos constatados como RECLAMACAO deve aparecer
			if (pg_numrows ($resD) == 0) {
				$sql = "SELECT   *
						FROM     tbl_defeito_reclamado
						JOIN     tbl_linha USING (linha)
						WHERE    tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;

				
			}
		}
		
		if ($defeito_constatado_fabrica == "NAO") {
			$sql = "SELECT   *
					FROM     tbl_defeito_reclamado
				  	JOIN     tbl_linha using (linha)
					WHERE    tbl_linha.fabrica = $login_fabrica";
			
			//lenoxx n�o filtra por fam�lia
			if ($login_fabrica <> 11) { $sql = " AND      tbl_linha.linha   = $linha"; }
					//a pedido do leandro tectoy, aparecer� somente RECLAMACAO para posto - TAKASHI 31/7/2006
			if ($login_fabrica == 6) { $sql .= " AND duvida_reclamacao='RC'";}
			
			$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
					//a pedido do leandro tectoy, aparecer� somente RECLAMACAO para posto - TAKASHI 31/7/2006
			$resD = @pg_exec ($con,$sql) ;

			
		}
	}

		//takashi 17/10
	/*	 if($login_fabrica==24){
			$sql = "SELECT   *
				FROM     tbl_defeito_reclamado
				WHERE    fabrica = $login_fabrica order by descricao";
						$resD = @pg_exec ($con,$sql) ;
}  */
  //takashi 17/10
// 		echo "$sql";
}

/***********************************************************************/

if (pg_num_rows($res) > 0) {
	$row = pg_numrows ($resD);
}
else {
	$row = 0;
}

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
   //CABE�ALHO
   Header("Content-type: application/xml; charset=iso-8859-1"); 
}//FECHA IF (row)
echo $xml;
?>
