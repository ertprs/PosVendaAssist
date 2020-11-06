<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

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
$res = pg_exec ($con,$sql);
$familia        = pg_result ($res,0,'familia') ;
$linha          = pg_result ($res,0,'linha') ;
$login_fabrica  = pg_result ($res,0,'fabrica') ;
$cod_produto    = pg_result ($res,0,'produto') ;
//echo "familia: $sql";

/*
$sql = "SELECT  defeito_constatado_por_familia,
				defeito_constatado_por_linha
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$defeito_constatado_por_familia = pg_result ($res,0,'defeito_constatado_por_familia') ;
$defeito_constatado_por_linha   = pg_result ($res,0,'defeito_constatado_por_linha') ;

$defeito_constatado_fabrica = "NAO";

if($login_fabrica==15){
		$sql = "SELECT   *
			FROM     tbl_defeito_reclamado
			JOIN     tbl_linha using (linha)
			WHERE    tbl_linha.fabrica = $login_fabrica
			AND      tbl_linha.linha   = $linha
			ORDER BY tbl_defeito_reclamado.descricao;";
		$resD = @pg_exec ($con,$sql) ;
		if(pg_numrows($resD)==0){
		$sql = "SELECT * 
			FROM tbl_defeito_reclamado 
			JOIN   tbl_linha USING (linha) 
			WHERE  tbl_defeito_reclamado.linha = $familia 
			AND duvida_reclamacao='RC'
			AND tbl_linha.fabrica = $login_fabrica 
			ORDER BY tbl_defeito_reclamado.descricao";
		$resD = pg_exec ($con,$sql);
		}
// echo "aqui";
}else{
	if ($defeito_constatado_por_familia == 't') {
		$defeito_constatado_fabrica = "SIM";
		
		if ($login_fabrica <> 19) {
			$sql = "SELECT   *
					FROM     tbl_defeito_reclamado
					JOIN     tbl_familia USING (familia)
					WHERE    tbl_familia.fabrica = $login_fabrica ";
				if ($login_fabrica == 1) { $sql .= " AND      tbl_defeito_reclamado.linha   = $linha";}
				if(strlen($familia)>0) $sql .= " AND      tbl_defeito_reclamado.familia = $familia";
					$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;
	
			if (pg_numrows ($resD) == 0) {
				$sql = "SELECT   *
						FROM     tbl_defeito_reclamado
						JOIN     tbl_familia USING (familia)
						WHERE    tbl_familia.fabrica = $login_fabrica";
				if ($login_fabrica == 1) { $sql .= " AND      tbl_defeito_reclamado.linha   = $linha";}
					$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
			}
		}else{
			$sql = "SELECT   *
					FROM     tbl_familia_defeito_reclamado
					JOIN     tbl_defeito_reclamado   ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
					AND tbl_defeito_reclamado.fabrica           = $login_fabrica
					JOIN     tbl_familia ON tbl_familia.familia  = tbl_familia_defeito_reclamado.familia
					AND tbl_familia.fabrica                     = $login_fabrica
					WHERE    tbl_familia.familia = $familia
					ORDER BY trim(tbl_defeito_reclamado.codigo)::numeric;";
			$resD = pg_exec ($con,$sql) ;
	// echo "$sql";	
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
					WHERE    tbl_linha.fabrica = $login_fabrica";
					if ($login_fabrica == 15) { $sql .= " AND      tbl_defeito_reclamado.linha   = $linha AND tbl_defeito_reclamado.fabrica=$fabrica";}
					$sql .= "ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;
			
		}
	}
	
	if ($defeito_constatado_fabrica == "NAO") {
		$sql = "SELECT   *
				FROM     tbl_defeito_reclamado
				JOIN     tbl_linha using (linha)
				WHERE    tbl_linha.fabrica = $login_fabrica
				AND      tbl_linha.linha   = $linha";
			if ($login_fabrica == 1) { $sql .= " AND      tbl_defeito_reclamado.linha   = $linha";}
				//a pedido do leandro tectoy, aparecerá somente RECLAMACAO para posto - TAKASHI 31/7/2006
				if ($login_fabrica == 6) { $sql .= " AND duvida_reclamacao='RC'";}
				$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
				//a pedido do leandro tectoy, aparecerá somente RECLAMACAO para posto - TAKASHI 31/7/2006
		$resD = @pg_exec ($con,$sql) ;
	}
}
*/

#TELA NOVA A PARTIR DAQUI ----------------

if(($login_fabrica==6) OR ($login_fabrica==3) OR ($login_fabrica==11) OR ($login_fabrica==24) OR ($login_fabrica==15)){
//PROCURA POR LINHA E FAMILIA
	$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado), 
					tbl_defeito_reclamado.descricao 
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE tbl_diagnostico.fabrica=$login_fabrica ";
	if(strlen($familia)>0){ $sql .=" AND tbl_diagnostico.familia=$familia ";}
	if(strlen($linha)>0){$sql .=" AND tbl_diagnostico.linha=$linha ";}
	$sql .= " and tbl_defeito_reclamado.ativo='t' ORDER BY tbl_defeito_reclamado.descricao";

	$resD = pg_exec ($con,$sql) ;
	$row = pg_numrows ($resD); 
/*
	if($row==0){
//SE RESULTADO FOR ZERO PROCURA SO POR FAMILIA
			$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado), 
					tbl_defeito_reclamado.descricao 
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE tbl_diagnostico.fabrica=$login_fabrica ";
		if(strlen($familia)>0){ $sql .=" AND tbl_diagnostico.familia=$familia ";}
				$sql .= " and tbl_defeito_reclamado.ativo='t' ORDER BY tbl_defeito_reclamado.descricao";
			$resD = pg_exec ($con,$sql) ;
	}
	$row = pg_numrows ($resD); */
/*
	if($row==0){
//SE RESULTADO FOR ZERO PROCURA SO POR LINHA
		$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado), 
				tbl_defeito_reclamado.descricao 
		FROM tbl_diagnostico 
		JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
		WHERE tbl_diagnostico.fabrica=$login_fabrica ";
		if(strlen($linha)>0){$sql .=" AND tbl_diagnostico.linha=$linha ";}
			$sql .= " and tbl_defeito_reclamado.ativo='t' ORDER BY tbl_defeito_reclamado.descricao";
		$resD = pg_exec ($con,$sql) ;
	}*/
}
/*if($login_fabrica==15){
	$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado), 
					tbl_defeito_reclamado.descricao 
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE tbl_diagnostico.fabrica=$login_fabrica ";
	if(strlen($familia)>0){ $sql .=" AND tbl_diagnostico.familia=$familia ";}
	if(strlen($linha)>0){$sql .=" AND tbl_diagnostico.linha=$linha ";}
	$sql .= " and tbl_defeito_reclamado.ativo='t' ORDER BY tbl_defeito_reclamado.descricao";
	$resD = pg_exec ($con,$sql) ;
}*/

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
