<?php
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

function calculaExtrato($extrato) {
	global $con;

	$sql = "select os, sua_os, tbl_produto.linha, tbl_linha.campos_adicionais , posto, regexp_replace(sua_os,'-.+','') as os_revenda from tbl_os join tbl_os_extra using(os) join tbl_produto using(produto) join tbl_linha on tbl_linha.linha = tbl_produto.linha 
		where tbl_os.fabrica = 178 order by sua_os" ;	
	$res = pg_query($con, $sql);

	for($i=0;$i<pg_num_rows($res);$i++) {
		$os         = pg_fetch_result($res, $i, 'os');
		$sua_os     = pg_fetch_result($res, $i, 'sua_os');
		$linha      = pg_fetch_result($res, $i, 'linha');
		$campos_adicionais      = pg_fetch_result($res, $i, 'campos_adicionais');
		$posto      = pg_fetch_result($res, $i, 'posto');
		$os_revenda = pg_fetch_result($res, $i, 'os_revenda');

		$campos_adicionais = json_decode($campos_adicionais , true);
		extract($campos_adicionais);

		$valor_mo = 0;
		if($i == 0 or $os_revenda <> $os_revenda_ant) {
			$sqlx = "select count(1) from tbl_os join tbl_os_extra using(os) where posto = $posto  and regexp_replace(sua_os, '-.+','') = '$os_revenda'";
			$resx = pg_query($con, $sqlx);

			$qtde = pg_fetch_result($resx,0,0); 
			foreach($campos_adicionais as $key => $value) {
				if(in_array($qtde, range($value['qtde_min'], $value['qtde_max']))) {
					$valor_mo  = $value['valor'];
					$sqlmo = "UPDATE tbl_os SET mao_de_obra = $valor_mo from tbl_os_extra WHERE posto = $posto and tbl_os_extra.os = tbl_os.os and regexp_replace(sua_os, '-.+','') = '$os_revenda' and (tbl_os.mao_de_obra = 0 or tbl_os.mao_de_obra isnull) and extrato = $extrato";
						echo $sqlmo;exit;

				}
			}
		}else{
			$valor_mo = 0;
		}

		echo ($valor_mo >0) ? "$valor_mo , $qtde, $os_revenda \n" : "";
		$os_revenda_ant = $os_revenda;
	}


}
echo calculaExtrato();


echo in_array(3, range(1,5))? "teste": "erro";
