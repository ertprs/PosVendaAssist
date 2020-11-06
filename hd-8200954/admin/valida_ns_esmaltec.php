<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

	if(isset($_POST['ajax_ns'])){

		$produto_referencia	= $_POST['produto_referencia'];
		$serie 		= trim($_POST['numeroSerie']);
		$consumidor_revenda_campo = $_POST['consumidor_revenda'];

		if(strlen(trim($serie))==0){
			$dados['retorno'] = "nao";
			echo json_encode($dados);
			exit;
		}

		$sql = "SELECT tbl_os.os, tbl_os.revenda_nome ,tbl_os.revenda_cnpj, consumidor_revenda, TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') as data_nf, tbl_os.nota_fiscal  
			from tbl_os 
			join tbl_produto on tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = $login_fabrica 
			where tbl_produto.referencia = '$produto_referencia' and tbl_os.serie = '$serie' 
			and tbl_os.excluida is not true and tbl_os.serie is not null 
			and data_abertura between current_date - interval '2 years' and current_date 
			and tbl_os.fabrica = $login_fabrica 
			order by os desc limit 1 ";
		$res = pg_query($con, $sql);
		
		if(pg_num_rows($res)>0){
			$os = pg_fetch_result($res, 0, 'os');
			$revenda_nome = pg_fetch_result($res, 0, 'revenda_nome');
			$revenda_cnpj = pg_fetch_result($res, 0, 'revenda_cnpj');
			$data_nf = pg_fetch_result($res, 0, 'data_nf');
			$nota_fiscal = pg_fetch_result($res, 0, 'nota_fiscal');
			$consumidor_revenda = pg_fetch_result($res, 0, 'consumidor_revenda');

			if($consumidor_revenda == $consumidor_revenda_campo){
				$dados['retorno'] = "ok";
				$dados['os'] = "$os";	
				$dados['nota_fiscal']	 = $nota_fiscal;
				$dados['data_nf'] = $data_nf;
				$dados['revenda_nome'] = $revenda_nome; 
				$dados['revenda_cnpj'] = $revenda_cnpj; 
			}else{
				$dados['retorno'] = "nao";	
			}
		}else{
			$dados['retorno'] = "nao";
		}

		echo json_encode($dados);

		exit;
	}

?>