<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

//RECEBE PARÃMETRO                     
$codigo_posto = $_GET["codigo_posto"]; 

//echo "<BR>codigo_posto ";

if ($codigo_posto <> 0){
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto='$codigo_posto'; ";

	$res = pg_exec($con,$sql);
	$posto = pg_result($res,0,posto);

	$sql = "SELECT os, sua_os FROM tbl_os WHERE fabrica = $login_fabrica AND os_fechada IS FALSE
						  AND excluida IS NOT TRUE AND posto=$posto; ";


	$res2 = pg_exec ($con,$sql) ;
	//echo "<BR>$sql"; 
	$row = pg_numrows ($res2);    
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<ordem>\n";
	for($i=0; $i<$row; $i++) {  
   
	  $os = pg_result($res2,$i,os);
	  $sua_os = pg_result($res2,$i,sua_os);
	  $xml .= "<oss>\n";     
	  $xml .= "<os>".$os."</os>\n";                  
	  $xml .= "<sua_os>".$sua_os."</sua_os>\n";
	  $xml .= "</oss>\n";    
   }
   $xml.= "</ordem>\n";                  

echo Header("Content-type: application/xml; charset=iso-8859-1");
}   
$os_busca = $_GET["os"]; 
if ($_GET["os"] <> 0){	
   $sql = "SELECT	tbl_produto.produto as produto,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_desc,
					tbl_os.defeito_reclamado as reclamado,
					tbl_defeito_reclamado.descricao as reclamado_desc,
					tbl_defeito_constatado.defeito_constatado as constatado,
					tbl_defeito_constatado.descricao as constatado_desc,
					tbl_solucao.solucao as solucao,
					tbl_solucao.descricao as solucao_desc,
					tbl_linha.linha    as produto_linha,
					tbl_familia.familia as produto_familia

					FROM tbl_os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
					JOIN tbl_linha   ON tbl_linha.linha = tbl_produto.linha
					JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
					LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica 
					LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND  tbl_defeito_constatado.fabrica = $login_fabrica 
					LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND  tbl_solucao.fabrica = $login_fabrica 
					where tbl_os.os = $os_busca;";

	$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
			$produto_desc = pg_result($res,0,produto_desc);
			$reclamado = pg_result($res,0,reclamado);
			$reclamado_desc = pg_result($res,0,reclamado_desc);
			$constatado = pg_result($res,0,constatado);
			$constatado_desc = pg_result($res,0,constatado_desc);
			$solucao = pg_result($res,0,solucao);
			$solucao_desc = pg_result($res,0,solucao_desc);
			$produto_linha = pg_result($res,0,produto_linha);
			$produto_familia = pg_result($res,0,produto_familia);
			$produto_referencia = pg_result($res,0,produto_referencia);
			
			if(!$produto){$produto=0;}
			if(!$produto_desc){$produto_desc=0;}
			if(!$reclamado){$reclamado=0;}
			if(!$reclamado_desc){$reclamado_desc='selecione uma defeito';}
			if(!$constatado){$constatado=0;}
			if(!$constatado_desc){$constatado_desc='selecione uma defeito';}
			if(!$solucao){$solucao=0;}
			if(!$solucao_desc){$solucao_desc='selecione uma solução';}
			
		}

	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<ordem>\n";

	  $xml .= "<modelo2>\n";     
		  $xml .= "<produto>$produto</produto>\n";
		  $xml .= "<produto_desc>$produto_desc</produto_desc>\n";     
		  $xml .= "<reclamado>$reclamado</reclamado>\n";     
		  $xml .= "<reclamado_desc>$reclamado_desc</reclamado_desc>\n";     
		  $xml .= "<constatado>$constatado</constatado>\n";     
		  $xml .= "<constatado_desc>$constatado_desc</constatado_desc>\n";     
		  $xml .= "<solucao>$solucao</solucao>\n";     
		  $xml .= "<solucao_desc>$solucao_desc</solucao_desc>\n";  
		  $xml .= "<produto_linha>$produto_linha</produto_linha>\n";  
		  $xml .= "<produto_familia>$produto_familia</produto_familia>\n";   
		  $xml .= "<produto_referencia>$produto_referencia</produto_referencia>\n";    
	  $xml .= "</modelo2>\n"; 
   
   
   $xml.= "</ordem>\n";                  

echo Header("Content-type: application/xml; charset=iso-8859-1");
}   

echo $xml;
?>
