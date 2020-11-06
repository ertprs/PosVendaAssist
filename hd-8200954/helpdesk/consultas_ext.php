<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


if(isset($_REQUEST['pesquisa']) > 0) {
	
	$os          = (strlen(trim(strtoupper($_GET['os'])))         == 0) ? trim(strtoupper($_POST['os']))          : trim(strtoupper($_GET['os']));

	$join_cond = " FROM tbl_os ";
	if (strlen($os) > 0) {
		$cond2 .= " AND tbl_os.os = '$os' ";
	}



	$sql = " SELECT count(*)
			$join_cond
			JOIN tbl_produto USING(produto)
			WHERE			$cond2";
	$res = pg_query($con,$sql);
	$qtde = pg_fetch_result($res,0,0);

	$sql = " SELECT tbl_os.os,
					data_abertura,
					data_fechamento,
					tbl_os.sua_os,
					serie,
					referencia,
					descricao,
					nota_fiscal,
					consumidor_nome,
					tbl_fabrica.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_os.fabrica
			$join_cond
			JOIN tbl_produto USING(produto)
			JOIN tbl_fabrica USING(fabrica)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = tbl_os.fabrica
			WHERE  1= 1
			$cond2			ORDER BY tbl_os.fabrica, tbl_os.os ";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$resultados       = pg_fetch_all($res);
		$i = 0;
		echo "{'total':'".$qtde."','resultado': [";
		foreach($resultados as $resultado) {
			$os              = $resultado['os'];
			$data_abertura   = $resultado['data_abertura'];
			$data_fechamento = $resultado['data_fechamento'];
			$sua_os          = $resultado['sua_os'];
			$serie           = $resultado['serie'];
			$referencia      = $resultado['referencia'];
			$descricao       = $resultado['descricao'];
			$nota_fiscal     = $resultado['nota_fiscal'];
			$consumidor_nome = $resultado['consumidor_nome'];
			$fabrica_nome    = $resultado['nome'];
			$fabrica         = $resultado['fabrica'];
			$codigo_posto    = $resultado['codigo_posto'];

			if($fabrica == 1) {
				$os = $codigo_posto."".$os;
			}

			echo ($i >0) ? ",": "";
			echo "{'fabrica':'$fabrica','fabrica_nome':'$fabrica_nome','sua_os':'$sua_os','os': '$os','serie':'$serie','nota_fiscal':'$nota_fiscal','data_abertura':'$data_abertura','data_fechamento':'$data_fechamento','consumidor':'$consumidor_nome','produto':'$referencia-$descricao'}";
			$i++;
		}
		echo "] }";
	}else{
		echo "{'total':'0','sucesso':'false'}";
	}
	
	exit;
}
