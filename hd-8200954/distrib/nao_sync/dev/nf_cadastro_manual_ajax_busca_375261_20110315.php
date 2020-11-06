<?
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';

$fabrica = 10;
$q = strtolower($_GET["q"]);
$tipo = $_GET['tipo'];
$os = $_GET['os'];
if (isset($_GET["q"])){

	
	if($tipo == 'posto') {
		$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ILIKE '%$q%' OR tbl_posto.nome_fantasia ILIKE '%$q%')
			AND      tbl_posto_fabrica.fabrica = $fabrica
			ORDER BY tbl_posto.nome";
		$res = pg_query($con,$sql);


		if(pg_num_rows($res)>0) {
			for ( $i = 0 ; $i < @pg_num_rows ($res) ; $i++ ) {
				$cnpj_revenda             = trim(pg_result($res,0,cnpj));
				
				$posto				= trim(pg_fetch_result($res,$i,posto));	
				$nome				= trim(pg_fetch_result($res,$i,nome));	
				$ie_consumidor      = pg_result($res,$i,ie);
				$numero_consumidor  = pg_result($res,$i,numero);
				$consumidor_cep     = pg_result($res,$i,cep);
				$fone_consumidor    = pg_result($res,$i,fone);
				
				echo "$nome|$cnpj_revenda|$ie_consumidor|$consumidor_cep|$numero_consumidor|$fone_consumidor|$posto|";
				echo "\n";
			}
		}else{
			echo "<h2>Se você não conseguir encontrar o destinatário, avise o Ger. Ronaldo para cadastrar na Fábrica Telecontrol o posto para que sirva de Fornecedor (não precisa credenciar como posto, apenas cadastrar).</h2>";
		}
	}
	
	
	
	if($tipo =='transportadora') {
		$sql = "SELECT  DISTINCT transp
				FROM     tbl_faturamento ";
		$sql .= " WHERE transp ilike '%$q%' ";
		$sql .=" AND fabrica in (10,51,81) 
					ORDER BY transp ";
		$res = @pg_query ($con,$sql);

		for ($i=0; $i < pg_num_rows($res); $i++) {
			$transp_nome	= strtoupper(trim(pg_fetch_result($res,$i,transp)));
			echo "$transp_nome\n";
		}
	}
	
	
	
}

if($tipo == 'codigo') {
	$sql = "
		SELECT   
			tbl_os.os				     ,
			tbl_os.sua_os				 ,
			tbl_os.consumidor_nome       ,
			tbl_os.consumidor_cpf        ,
			tbl_os.consumidor_cep        ,
			tbl_os.consumidor_numero     ,
			tbl_os.consumidor_complemento,
			tbl_os.consumidor_fone
		FROM     tbl_os
		WHERE    (tbl_os.sua_os = '$os' OR tbl_os.os = $os) and
		fabrica in (10,81,51)
	";
	$res = pg_query($con,$sql);


	if(pg_num_rows($res)>0) {
		for ( $i = 0 ; $i < @pg_num_rows ($res) ; $i++ ) {
			$os             = trim(pg_result($res,0,os));
			$sua_os				    = trim(pg_fetch_result($res,$i,sua_os));	
			$consumidor_nome	    = trim(pg_fetch_result($res,$i,consumidor_nome));	
			$consumidor_cpf         = pg_result($res,$i,consumidor_cpf);
			$consumidor_cep         = pg_result($res,$i,consumidor_cep);
			$consumidor_numero      = pg_result($res,$i,consumidor_numero);
			$consumidor_complemento = pg_result($res,$i,consumidor_complemento);
			
			echo "$sua_os|$consumidor_nome|$consumidor_cpf|$consumidor_cep|$consumidor_numero|$consumidor_complemento|$consumidor_fone|$os";
			echo "\n";
		}
	}else{
		echo "<h2>Não foi encontrado nenhum resultado para sua pesquisa.</h2>";
	}
} 