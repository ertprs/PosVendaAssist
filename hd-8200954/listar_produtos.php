<?
                 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';          

//RECEBE PARÃMETRO                     
$produto_referencia = $_POST["produto_referencia"];           
/*$produto_referencia = $_GET["produto_referencia"]; */
	$sql="SELECT familia, fabrica FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia='$produto_referencia'";
	$res = pg_exec ($con,$sql);
	$familia = pg_result ($res,0,'familia') ;
	$login_fabrica = pg_result ($res,0,'fabrica') ;
	
	
	$sql = "SELECT  defeito_constatado_por_familia,
					defeito_constatado_por_linha
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$defeito_constatado_por_familia = pg_result ($res,0,'defeito_constatado_por_familia') ;
	$defeito_constatado_por_linha   = pg_result ($res,0,'defeito_constatado_por_linha') ;
	

	
	
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
					JOIN     tbl_familia ON tbl_familia.familia  = tbl_familia_defeito_reclamado.familia
					AND tbl_familia.fabrica                     = $login_fabrica
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
				WHERE    tbl_linha.fabrica = $login_fabrica
				AND      tbl_linha.linha   = $linha";
				//a pedido do leandro tectoy, aparecerá somente RECLAMACAO para posto - TAKASHI 31/7/2006
				if ($login_fabrica == 6) { $sql .= " AND duvida_reclamacao='RC'";}
				$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
				//a pedido do leandro tectoy, aparecerá somente RECLAMACAO para posto - TAKASHI 31/7/2006
		$resD = @pg_exec ($con,$sql) ;
	}
		
// 		echo "$sql";
      
$row = pg_numrows ($resD);    

//VERIFICA SE VOLTOU ALGO 
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

//PRINTA O RESULTADO  
echo $xml;            
?>
